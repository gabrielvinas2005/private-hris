import { PDFDocument, rgb, StandardFonts } from "pdf-lib";
import {
  philHealthPMRFCoordinates,
  getFieldCoordinates,
  birForm2305Coordinates,
  getBIRForm2305Coordinates,
  pagIbigMDFCoordinates,
  getPagIbigMDFCoordinates,
} from "../config/pdfFieldCoordinates.js";

function mergeMappingsIntoConfig(mappings, config) {
  for (const [fieldKey, savedMapping] of Object.entries(mappings)) {
    const page = savedMapping.page || 1;
    const pageKey = `page${page}`;

    if (config[pageKey] && config[pageKey][fieldKey]) {
      config[pageKey][fieldKey] = {
        ...config[pageKey][fieldKey],
        ...savedMapping,
        charSpacing:
          savedMapping.charSpacing ?? config[pageKey][fieldKey].charSpacing,
      };
    } else if (savedMapping.x !== undefined && savedMapping.y !== undefined) {
      if (!config[pageKey]) {
        config[pageKey] = {};
      }
      config[pageKey][fieldKey] = savedMapping;
    }
  }
}

async function loadSavedMappings() {
  try {
    try {
      const { pdfFieldMappingApi } = await import("../services/api.js");
      const response = await pdfFieldMappingApi.getMappings("philhealth_pmrf");
      if (response.data?.success && response.data?.data?.mappings) {
        const mappings = response.data.data.mappings;
        if (Object.keys(mappings).length > 0) {
          mergeMappingsIntoConfig(mappings, philHealthPMRFCoordinates);
          return;
        }
      }
    } catch (apiError) {
      console.warn(
        "[pdffiller] Failed to load from JSON file, trying localStorage:",
        apiError
      );
    }

    const saved = localStorage.getItem("philhealth_pmrf_mappings");
    if (!saved) return;

    const mappings = JSON.parse(saved);
    mergeMappingsIntoConfig(mappings, philHealthPMRFCoordinates);
  } catch (e) {
  }
}

loadSavedMappings();

export async function loadPDFTemplate() {
  const templateUrl = "/templates/philhealth_PMRF.pdf";
  const response = await fetch(templateUrl);

  if (!response.ok) {
    throw new Error(`Failed to load PDF template: ${response.statusText}`);
  }

  const pdfBytes = await response.arrayBuffer();
  const pdfDoc = await PDFDocument.load(pdfBytes);
  return pdfDoc;
}

export async function getPDFFormFields() {
  const pdfDoc = await loadPDFTemplate();
  const form = pdfDoc.getForm();
  const fields = form.getFields();

  const fieldInfo = fields.map((field) => {
    const type = field.constructor.name;
    const name = field.getName();
    return { type, name };
  });

  return fieldInfo;
}

export async function fillPhilHealthPDF(data, mappings = {}) {
  await loadSavedMappings();

  const pdfDoc = await loadPDFTemplate();

  const form = pdfDoc.getForm();
  const allFields = form.getFields();

  const hasFormFields = allFields.length > 0;

  const pages = pdfDoc.getPages();
  const firstPage = pages[0];
  const secondPage = pages.length > 1 ? pages[1] : null;
  const { width, height } = firstPage.getSize();

  const helveticaFont = await pdfDoc.embedFont(StandardFonts.Helvetica);
  const helveticaBoldFont = await pdfDoc.embedFont(StandardFonts.HelveticaBold);

  // Load mapped fields from saved mappings
  const mappedFields = new Set();
  const fieldPageMap = {};

  try {
    const { pdfFieldMappingApi } = await import("../services/api.js");
    const response = await pdfFieldMappingApi.getMappings("philhealth_pmrf");
    if (response.data?.success && response.data?.data?.mappings) {
      const mappings = response.data.data.mappings;
      Object.keys(mappings).forEach((key) => {
        mappedFields.add(key);
        if (mappings[key].page) {
          fieldPageMap[key] = mappings[key].page;
        }
      });
    }
  } catch (apiError) {
    const savedMappings = localStorage.getItem("philhealth_pmrf_mappings");
    if (savedMappings) {
      try {
        const mappings = JSON.parse(savedMappings);
        Object.keys(mappings).forEach((key) => {
          mappedFields.add(key);
          if (mappings[key].page) {
            fieldPageMap[key] = mappings[key].page;
          }
        });
      } catch (e) {
        // Ignore parse errors
      }
    }
  }

  let successCount = 0;
  let failCount = 0;
  let skippedCount = 0;

  const fieldMappings = {
    member_last_name: "last_name",
    member_first_name: "first_name",
    member_middle_name: "middle_name",
    member_name_extension: "name_extension",

    mother_first_name: "mother_first_name",
    mother_middle_name: "mother_middle_name",
    mother_last_name: "mother_last_name",
    spouse_first_name: "spouse_first_name",
    spouse_middle_name: "spouse_middle_name",
    spouse_last_name: "spouse_last_name",

    // Date of birth (split into components) - all use same data key
    date_of_birth_month: "date_of_birth",
    date_of_birth_day: "date_of_birth",
    date_of_birth_year: "date_of_birth",

    child_name: "child_name",
    child_birthdate: "child_birthdate",
    child_middlename: "child_middlename",
    child_lastname: "child_lastname",

    place_of_birth: "place_of_birth",
    philhealth_no: "philhealth_no",
    tin_no: "tin_no",
    mobile_number: "mobile_number",
    email: "email",
    permanent_address: "permanent_address",
    permanent_zip: "permanent_zip",
    mailing_address: "mailing_address",
    mailing_zip: "mailing_zip",

    signature_last_name: "last_name",
    signature_first_name: "first_name",
    signature_middle_name: "middle_name",
    signature_name_extension: "name_extension",
    signatory_name: "signatory_name",
  };

  for (const [coordKey, dataKey] of Object.entries(fieldMappings)) {
    try {
      // Skip fields that are not mapped (if mappings exist)
      if (mappedFields.size > 0 && !mappedFields.has(coordKey)) {
        skippedCount++;
        continue;
      }

      // If signatory_name is mapped, skip the old individual signature fields
      if (
        mappedFields.has("signatory_name") &&
        (coordKey === "signature_last_name" ||
          coordKey === "signature_first_name" ||
          coordKey === "signature_middle_name" ||
          coordKey === "signature_name_extension")
      ) {
        skippedCount++;
        continue;
      }

      const isSignatureField = coordKey.startsWith("signature_");
      let coords = null;
      let pageNumber = 1;
      let targetPage = firstPage;
      let pageHeight = height;

      if (isSignatureField) {
        const coordsPage2 = getFieldCoordinates(coordKey, 2);
        if (coordsPage2 && secondPage) {
          coords = coordsPage2;
          pageNumber = 2;
          targetPage = secondPage;
          pageHeight = secondPage.getSize().height;
        } else {
          // Fallback to page 1 if not found on page 2
          coords = getFieldCoordinates(coordKey, 1);
        }
      } else {
        coords = getFieldCoordinates(coordKey, 1);
        if (!coords) {
          const coordsPage2 = getFieldCoordinates(coordKey, 2);
          if (coordsPage2 && secondPage) {
            coords = coordsPage2;
            pageNumber = 2;
            targetPage = secondPage;
            pageHeight = secondPage.getSize().height;
          }
        }
      }

      if (!coords) {
        failCount++;
        continue;
      }

      const value = data[dataKey] || "";
      if (!value) {
        continue;
      }

      // Convert from standard coordinates (top-left = 0,0) to PDF coordinates (bottom-left = 0,0)
      // Standard: Y=0 at top, Y=792 at bottom
      // PDF: Y=0 at bottom, Y=792 at top
      const pdfX = coords.x; // X stays the same
      const pdfY = pageHeight - coords.y; // Y is inverted (use actual height, not hardcoded 792)

      // Handle date of birth specially - draw each character with spacing
      if (coordKey.includes("date_of_birth")) {
        // Parse date and place components
        const dateParts = parseDateOfBirth(data.date_of_birth);
        if (dateParts) {
          let text = "";
          if (coordKey.includes("month")) {
            text = dateParts.month;
          } else if (coordKey.includes("day")) {
            text = dateParts.day;
          } else if (coordKey.includes("year")) {
            text = dateParts.year;
          }

          if (text) {
            const fontSize = coords.fontSize || 10;
            const charSpacing = coords.charSpacing || 16;

            let currentX = pdfX;
            for (let i = 0; i < text.length; i++) {
              const char = text[i];
              const charWidth = helveticaFont.widthOfTextAtSize(char, fontSize);
              const charCenterX = currentX + (charSpacing - charWidth) / 2;

              targetPage.drawText(char, {
                x: charCenterX,
                y: pdfY,
                size: fontSize,
                font: helveticaFont,
              });
              currentX += charSpacing;
            }
            successCount++;
          }
        }
      } else if (coordKey === "child_birthdate") {
        const textValue = String(value).toUpperCase();
        const fontSize = coords.fontSize || 8;

        targetPage.drawText(textValue, {
          x: pdfX,
          y: pdfY,
          size: fontSize,
          font: helveticaFont,
          maxWidth: coords.maxWidth || 200,
        });

        successCount++;
      } else {
        const textValue = String(value).toUpperCase();
        const fontSize = coords.fontSize || 10;

        const isBoxedField =
          coordKey.includes("philhealth_no") || coordKey.includes("tin_no");

        if (isBoxedField && coords.charSpacing) {
          const charSpacing = coords.charSpacing;
          const cleanText = textValue.replace(/[-\s]/g, "");

          let currentX = pdfX;
          for (let i = 0; i < cleanText.length; i++) {
            const char = cleanText[i];
            const charWidth = helveticaFont.widthOfTextAtSize(char, fontSize);
            const charCenterX = currentX + (charSpacing - charWidth) / 2;

            targetPage.drawText(char, {
              x: charCenterX,
              y: pdfY,
              size: fontSize,
              font: helveticaFont,
            });
            currentX += charSpacing;
          }
        } else if (coords.multiline && textValue.length > 40) {
          const words = textValue.split(" ");
          const lines = [];
          let currentLine = "";

          for (const word of words) {
            if ((currentLine + word).length > 50) {
              if (currentLine) lines.push(currentLine.trim());
              currentLine = word + " ";
            } else {
              currentLine += word + " ";
            }
          }
          if (currentLine) lines.push(currentLine.trim());

          lines.forEach((line, index) => {
            targetPage.drawText(line, {
              x: pdfX,
              y: pdfY + index * fontSize * 1.2,
              size: fontSize,
              font: helveticaFont,
              maxWidth: coords.maxWidth || 400,
            });
          });
        } else {
          targetPage.drawText(textValue, {
            x: pdfX,
            y: pdfY,
            size: fontSize,
            font: helveticaFont,
            maxWidth: coords.maxWidth || 200,
          });
        }

        successCount++;
      }
    } catch (e) {
      failCount++;
    }
  }

  try {
    if (data.purpose === "registration") {
      const coords = getFieldCoordinates("purpose_registration", 1);
      if (coords) {
        const pdfY = height - coords.y; // Convert to PDF coordinates
        drawCheckbox(firstPage, coords.x, pdfY, helveticaFont);
        successCount++;
      }
    } else if (data.purpose === "updating") {
      const coords = getFieldCoordinates("purpose_updating", 1);
      if (coords) {
        const pdfY = height - coords.y; // Convert to PDF coordinates
        drawCheckbox(firstPage, coords.x, pdfY, helveticaFont);
        successCount++;
      }
    }

    if (data.sex) {
      const sexValue = String(data.sex).toLowerCase();
      const coordKey = sexValue === "male" ? "sex_male" : "sex_female";
      const coords = getFieldCoordinates(coordKey, 1);
      if (coords) {
        const pdfY = height - coords.y; // Convert to PDF coordinates
        drawCheckbox(firstPage, coords.x, pdfY, helveticaFont);
        successCount++;
      }
    }

    // Civil Status
    if (data.civil_status) {
      const statusValue = String(data.civil_status)
        .toLowerCase()
        .replace(" ", "_");
      const coordKey = `civil_status_${statusValue}`;
      const coords = getFieldCoordinates(coordKey, 1);
      if (coords) {
        const pdfY = height - coords.y; // Convert to PDF coordinates
        drawCheckbox(firstPage, coords.x, pdfY, helveticaFont);
        successCount++;
      }
    }

    if (data.citizenship) {
      const citizenshipValue = String(data.citizenship)
        .toLowerCase()
        .replace(" ", "_");
      const coordKey = `citizenship_${citizenshipValue}`;
      const coords = getFieldCoordinates(coordKey, 1);
      if (coords) {
        const pdfY = height - coords.y; // Convert to PDF coordinates
        drawCheckbox(firstPage, coords.x, pdfY, helveticaFont);
        successCount++;
      }
    }
  } catch (e) {
  }

  console.log(
    `[PhilHealth PMRF] PDF Fill Summary: ${successCount} filled, ${failCount} failed, ${skippedCount} skipped (not mapped)`
  );

  const filledPdfBytes = await pdfDoc.save();
  return new Blob([filledPdfBytes], { type: "application/pdf" });
}

function parseDateOfBirth(dateString) {
  if (!dateString) return null;

  const parts = dateString.split(/[-\/]/);
  if (parts.length === 3) {
    return {
      month: parts[0].padStart(2, "0"),
      day: parts[1].padStart(2, "0"),
      year: parts[2],
    };
  }

  return null;
}

function drawCheckbox(page, x, y, font) {
  const size = 8;
  page.drawText("X", {
    x: x,
    y: y,
    size: size,
    font: font,
    color: rgb(0, 0, 0),
  });
}

async function loadBIRForm2305SavedMappings() {
  try {
    try {
      const { pdfFieldMappingApi } = await import("../services/api.js");
      const response = await pdfFieldMappingApi.getMappings("bir_form_2305");
      if (response.data?.success && response.data?.data?.mappings) {
        const mappings = response.data.data.mappings;
        if (Object.keys(mappings).length > 0) {
          mergeMappingsIntoConfig(mappings, birForm2305Coordinates);
          return;
        }
      }
    } catch (apiError) {
      console.warn(
        "[pdffiller] Failed to load from JSON file, trying localStorage:",
        apiError
      );
    }

    // Fallback to localStorage (for backward compatibility)
    const saved = localStorage.getItem("bir_form_2305_mappings");
    if (!saved) return;

    const mappings = JSON.parse(saved);
    mergeMappingsIntoConfig(mappings, birForm2305Coordinates);
  } catch (e) {
  }
}

loadBIRForm2305SavedMappings();

export async function loadBIRForm2305Template() {
  const templateUrl = "/templates/BIR_Form_2305.pdf";
  const response = await fetch(templateUrl);

  if (!response.ok) {
    throw new Error(`Failed to load PDF template: ${response.statusText}`);
  }

  const pdfBytes = await response.arrayBuffer();
  const pdfDoc = await PDFDocument.load(pdfBytes);
  return pdfDoc;
}

export async function fillBIRForm2305PDF(data) {
  await loadBIRForm2305SavedMappings();

  let mappedFields = new Set();
  try {
    const saved = localStorage.getItem("bir_form_2305_mappings");
    if (saved) {
      const mappings = JSON.parse(saved);
      mappedFields = new Set(Object.keys(mappings));
      console.log(
        "[BIR Form 2305] Mapped fields from localStorage:",
        Array.from(mappedFields)
      );
    } else {
      console.log("[BIR Form 2305] No saved mappings found in localStorage");
    }
  } catch (e) {
    console.error("[BIR Form 2305] Error loading mapped fields:", e);
  }

  const pdfDoc = await loadBIRForm2305Template();

  const pages = pdfDoc.getPages();
  const firstPage = pages[0];
  const { width, height } = firstPage.getSize();

  const helveticaFont = await pdfDoc.embedFont(StandardFonts.Helvetica);
  const helveticaBoldFont = await pdfDoc.embedFont(StandardFonts.HelveticaBold);

  let successCount = 0;
  let failCount = 0;
  let skippedCount = 0;

  const fieldMappings = {
    tin_1: "tin_no",
    tin_2: "tin_no",
    tin_3: "tin_no",

    // RDO Code
    rdo_code: "rdo_code",

    // Taxpayer Name
    last_name: "last_name",
    first_name: "first_name",
    middle_name: "middle_name",

    date_of_birth_month: "date_of_birth",
    date_of_birth_day: "date_of_birth",
    date_of_birth_year: "date_of_birth",

    // Addresses
    residence_address: "residence_address",
    residence_zip: "residence_zip",
    business_address: "business_address",
    business_zip: "business_zip",

    employer_tin_1: "employer_tin",
    employer_tin_2: "employer_tin",
    employer_tin_3: "employer_tin",
    employer_rdo: "employer_rdo",
    employer_name: "employer_name",
    employer_address: "employer_address",
    employer_zip: "employer_zip",

    // Spouse Information
    spouse_tin: "spouse_tin",
    spouse_last_name: "spouse_last_name",
    spouse_first_name: "spouse_first_name",
    spouse_middle_name: "spouse_middle_name",

    dependent_1_last_name: "dependent_1_last_name",
    dependent_1_first_name: "dependent_1_first_name",
    dependent_1_middle_name: "dependent_1_middle_name",
    dependent_1_birthdate: "dependent_1_birthdate",

    effective_date: "effective_date",

    certification_date: "certification_date",

    // Signatory Information
    signatory_name: "signatory_name",
    signatory_title: "signatory_title",
  };

  for (const [coordKey, dataKey] of Object.entries(fieldMappings)) {
    try {
      if (mappedFields.size > 0 && !mappedFields.has(coordKey)) {
        skippedCount++;
        continue;
      }

      const coords = getBIRForm2305Coordinates(coordKey, 1);

      if (!coords) {
        failCount++;
        continue;
      }

      let value = data[dataKey] || "";

      if (coordKey.startsWith("tin_") || coordKey.startsWith("employer_tin_")) {
        const tinValue = String(value).replace(/[-\s]/g, "");
        const part = coordKey.includes("_1")
          ? tinValue.substring(0, 3)
          : coordKey.includes("_2")
            ? tinValue.substring(3, 6)
            : tinValue.substring(6, 9);
        value = part;
      }

      if (!value) {
        continue;
      }

      const pdfX = coords.x;
      const pdfY = height - coords.y;

      if (
        coordKey.includes("date_of_birth") &&
        !coordKey.includes("dependent")
      ) {
        const dateParts = parseDate(value);
        if (dateParts) {
          let text = "";
          if (coordKey.includes("month")) {
            text = dateParts.month;
          } else if (coordKey.includes("day")) {
            text = dateParts.day;
          } else if (coordKey.includes("year")) {
            text = dateParts.year;
          }

          if (text) {
            const fontSize = coords.fontSize || 10;
            const charSpacing = coords.charSpacing || 16;

            // Draw each character separately with spacing
            let currentX = pdfX;
            for (let i = 0; i < text.length; i++) {
              const char = text[i];
              // Center each character in its box
              const charWidth = helveticaFont.widthOfTextAtSize(char, fontSize);
              const charCenterX = currentX + (charSpacing - charWidth) / 2;

              firstPage.drawText(char, {
                x: charCenterX,
                y: pdfY,
                size: fontSize,
                font: helveticaFont,
              });
              currentX += charSpacing;
            }
            successCount++;
          }
        }
      } else if (
        coordKey.includes("birthdate") ||
        coordKey === "effective_date" ||
        coordKey === "certification_date"
      ) {
        const dateParts = parseDate(value);
        if (dateParts) {
          const dateStr = `${dateParts.month}/${dateParts.day}/${dateParts.year}`;
          const fontSize = coords.fontSize || 10;
          const charSpacing = coords.charSpacing || 12;

          let currentX = pdfX;
          for (let i = 0; i < dateStr.length; i++) {
            const char = dateStr[i];
            if (char === "/") {
              currentX += charSpacing;
              continue;
            }
            const charWidth = helveticaFont.widthOfTextAtSize(char, fontSize);
            const charCenterX = currentX + (charSpacing - charWidth) / 2;

            firstPage.drawText(char, {
              x: charCenterX,
              y: pdfY,
              size: fontSize,
              font: helveticaFont,
            });
            currentX += charSpacing;
          }
          successCount++;
        }
        } else if (coordKey.includes("tin_") && coords.charSpacing) {
        const fontSize = coords.fontSize || 10;
        const charSpacing = coords.charSpacing;

        let currentX = pdfX;
        for (let i = 0; i < value.length; i++) {
          const char = value[i];
          const charWidth = helveticaFont.widthOfTextAtSize(char, fontSize);
          const charCenterX = currentX + (charSpacing - charWidth) / 2;

          firstPage.drawText(char, {
            x: charCenterX,
            y: pdfY,
            size: fontSize,
            font: helveticaFont,
          });
          currentX += charSpacing;
        }
        successCount++;
      } else {
        const textValue = String(value).toUpperCase();
        const fontSize = coords.fontSize || 10;

        const isAddressField =
          coordKey.includes("address") && !coordKey.includes("zip");

        const drawOptions = {
          x: pdfX,
          y: pdfY,
          size: fontSize,
          font: helveticaFont,
        };

        if (!isAddressField) {
          drawOptions.maxWidth = coords.maxWidth || 200;
        }

        firstPage.drawText(textValue, drawOptions);
        successCount++;
      }
    } catch (e) {
      failCount++;
    }
  }

  try {
    if (data.sex) {
      const sexValue = String(data.sex).toLowerCase();
      const coordKey =
        sexValue === "male" || sexValue === "m" ? "sex_male" : "sex_female";
      const coords = getBIRForm2305Coordinates(coordKey, 1);
      if (coords) {
        const pdfY = height - coords.y;
        drawCheckbox(firstPage, coords.x, pdfY, helveticaFont);
        successCount++;
      }
    }

    // Civil Status
    if (data.civil_status) {
      const statusValue = String(data.civil_status)
        .toLowerCase()
        .replace(" ", "_");
      const coordKey = `civil_status_${statusValue}`;
      const coords = getBIRForm2305Coordinates(coordKey, 1);
      if (coords) {
        const pdfY = height - coords.y;
        drawCheckbox(firstPage, coords.x, pdfY, helveticaFont);
        successCount++;
      }
    }

    if (data.filer_type) {
      const filerType = String(data.filer_type).toLowerCase();
      const coordKey =
        filerType === "employee" ? "filer_employee" : "filer_self_employed";
      const coords = getBIRForm2305Coordinates(coordKey, 1);
      if (coords) {
        const pdfY = height - coords.y;
        drawCheckbox(firstPage, coords.x, pdfY, helveticaFont);
        successCount++;
      }
    }
  } catch (e) {
  }

  console.log(
    `[BIR Form 2305] PDF Fill Summary: ${successCount} filled, ${failCount} failed, ${skippedCount} skipped (not mapped)`
  );

  const filledPdfBytes = await pdfDoc.save();
  return new Blob([filledPdfBytes], { type: "application/pdf" });
}

function parseDate(dateString) {
  if (!dateString) return null;

  // Handle YYYY-MM-DD format (from backend)
  if (
    typeof dateString === "string" &&
    dateString.match(/^\d{4}-\d{2}-\d{2}$/)
  ) {
    const parts = dateString.split("-");
    return {
      month: parts[1],
      day: parts[2],
      year: parts[0],
    };
  }

  const date = new Date(dateString);
  if (isNaN(date.getTime())) return null;

  return {
    month: String(date.getMonth() + 1).padStart(2, "0"),
    day: String(date.getDate()).padStart(2, "0"),
    year: String(date.getFullYear()),
  };
}

export async function loadPagIbigMDFSavedMappings() {
  try {
    try {
      const { pdfFieldMappingApi } = await import("../services/api.js");
      const response = await pdfFieldMappingApi.getMappings("pagibig_mdf");
      if (response.data?.success && response.data?.data?.mappings) {
        const mappings = response.data.data.mappings;
        if (Object.keys(mappings).length > 0) {
          mergeMappingsIntoConfig(mappings, pagIbigMDFCoordinates);
          return;
        }
      }
    } catch (apiError) {
      console.warn(
        "[pdffiller] Failed to load from JSON file, trying localStorage:",
        apiError
      );
    }

    const saved = localStorage.getItem("pagibig_mdf_mappings");
    if (!saved) return;

    const mappings = JSON.parse(saved);
    mergeMappingsIntoConfig(mappings, pagIbigMDFCoordinates);
  } catch (e) {
  }
}

export async function fillPagIbigMDFPDF(data) {
  try {
    await loadPagIbigMDFSavedMappings();

    const templateBytes = await fetch("/templates/PagIbig_MDF.pdf").then(
      (res) => res.arrayBuffer()
    );
    const pdfDoc = await PDFDocument.load(templateBytes);
    const pages = pdfDoc.getPages();
    const firstPage = pages[0];
    const secondPage = pages.length > 1 ? pages[1] : null;
    const { width, height } = firstPage.getSize();
    const secondPageHeight = secondPage ? secondPage.getSize().height : height;

    const helveticaFont = await pdfDoc.embedFont(StandardFonts.Helvetica);
    const helveticaBoldFont = await pdfDoc.embedFont(
      StandardFonts.HelveticaBold
    );

    const mappedFields = new Set();
    const fieldPageMap = {};

    try {
      const { pdfFieldMappingApi } = await import("../services/api.js");
      const response = await pdfFieldMappingApi.getMappings("pagibig_mdf");
      if (response.data?.success && response.data?.data?.mappings) {
        const mappings = response.data.data.mappings;
        Object.keys(mappings).forEach((key) => {
          mappedFields.add(key);
          if (mappings[key].page) {
            fieldPageMap[key] = mappings[key].page;
          }
        });
      }
    } catch (apiError) {
      const savedMappings = localStorage.getItem("pagibig_mdf_mappings");
      if (savedMappings) {
        try {
          const mappings = JSON.parse(savedMappings);
          Object.keys(mappings).forEach((key) => {
            mappedFields.add(key);
            if (mappings[key].page) {
              fieldPageMap[key] = mappings[key].page;
            }
          });
        } catch (e) {
          // Ignore parse errors
        }
      }
    }

    let successCount = 0;
    let failCount = 0;
    let skippedCount = 0;

    const fieldMappings = {
      member_last_name: "last_name",
      member_first_name: "first_name",
      member_middle_name: "middle_name",
      member_name_extension: "name_extension",
      father_last_name: "father_last_name",
      father_first_name: "father_first_name",
      father_middle_name: "father_middle_name",
      mother_last_name: "mother_last_name",
      mother_first_name: "mother_first_name",
      mother_middle_name: "mother_middle_name",
      spouse_last_name: "spouse_last_name",
      spouse_first_name: "spouse_first_name",
      spouse_middle_name: "spouse_middle_name",
      date_of_birth_month: "date_of_birth",
      date_of_birth_day: "date_of_birth",
      date_of_birth_year: "date_of_birth",
      place_of_birth: "place_of_birth",
      citizenship: "citizenship",
      tin_1: "tin_no",
      tin_2: "tin_no",
      tin_3: "tin_no",
      sss_no: "sss_no",
      gsis_no: "gsis_no",
      sss_gsis_no: "sss_gsis_no",
      employee_no: "employee_no",
      height: "height",
      weight: "weight",
      mobile_no: "mobile_no",
      email: "email",
      // Permanent Address sub-fields
      permanent_address_unit_room_floor: "permanent_address_unit_room_floor",
      permanent_address_building_name: "permanent_address_building_name",
      permanent_address_lot_block_phase_house:
        "permanent_address_lot_block_phase_house",
      permanent_address_street_name: "permanent_address_street_name",
      permanent_address_subdivision: "permanent_address_subdivision",
      permanent_address_barangay: "permanent_address_barangay",
      permanent_address_municipality_city:
        "permanent_address_municipality_city",
      permanent_address_province_state_country:
        "permanent_address_province_state_country",
      permanent_address_zip: "permanent_address_zip",
      permanent_address: "permanent_address",
      present_address_unit_room_floor: "present_address_unit_room_floor",
      present_address_building_name: "present_address_building_name",
      present_address_lot_block_phase_house:
        "present_address_lot_block_phase_house",
      present_address_street_name: "present_address_street_name",
      present_address_subdivision: "present_address_subdivision",
      present_address_barangay: "present_address_barangay",
      present_address_municipality_city: "present_address_municipality_city",
      present_address_province_state_country:
        "present_address_province_state_country",
      present_address_zip: "present_address_zip",
      present_address: "present_address",
      employer_name: "employer_name",
      employer_address: "employer_address",
      employer_zip: "employer_zip",
      monthly_compensation: "monthly_compensation",
      date_employed: "date_employed",
      dependent_1_last_name: "dependent_1_last_name",
      dependent_1_first_name: "dependent_1_first_name",
      dependent_1_middle_name: "dependent_1_middle_name",
      dependent_1_birthdate: "dependent_1_birthdate",
      informant_signature: "informant_signature",
      processed_by_name: "processed_by_name",
      processed_by_position: "processed_by_position",
      processed_by_branch_unit: "processed_by_branch_unit",
    };

    for (const [coordKey, dataKey] of Object.entries(fieldMappings)) {
      try {
        if (mappedFields.size > 0 && !mappedFields.has(coordKey)) {
          skippedCount++;
          continue;
        }

        // Skip individual fields if combined field is mapped
        if (
          (coordKey === "sss_no" || coordKey === "gsis_no") &&
          mappedFields.has("sss_gsis_no")
        ) {
          skippedCount++;
          continue;
        }

        const pageNumber = fieldPageMap[coordKey] || 1;
        let coords = getPagIbigMDFCoordinates(coordKey, pageNumber);

        if (!coords) {
          coords = getPagIbigMDFCoordinates(coordKey, 1);
          if (!coords && secondPage) {
            coords = getPagIbigMDFCoordinates(coordKey, 2);
          }
        }

        if (!coords) {
          failCount++;
          continue;
        }

        // Prefer SSS over GSIS when both are mapped
        if (coordKey === "gsis_no" && mappedFields.has("sss_no")) {
          skippedCount++;
          continue;
        }

        // Determine target page and height based on saved mapping or coordinate lookup
        const actualPageNumber = fieldPageMap[coordKey] || (coords ? 1 : 1);
        const targetPage =
          actualPageNumber === 2 && secondPage ? secondPage : firstPage;
        const pageHeight =
          actualPageNumber === 2 && secondPage ? secondPageHeight : height;

        let value;
        if (coordKey === "sss_gsis_no") {
          const sssValue = data.sss_no ? String(data.sss_no).trim() : "";
          const gsisValue = data.gsis_no ? String(data.gsis_no).trim() : "";
          value = sssValue || gsisValue || "";
        } else {
          value = data[dataKey] || "";
        }
        if (!value) continue;

        const pdfX = coords.x;
        const pdfY = pageHeight - coords.y;

        if (
          coordKey.includes("date_of_birth") &&
          !coordKey.includes("dependent")
        ) {
          const dateParts = parseDate(value);
          if (dateParts) {
            let text = "";
            if (coordKey.includes("month")) text = dateParts.month;
            else if (coordKey.includes("day")) text = dateParts.day;
            else if (coordKey.includes("year")) text = dateParts.year;

            if (text) {
              const fontSize = coords.fontSize || 10;
              const charSpacing = coords.charSpacing || 10;
              let currentX = pdfX;
              for (let i = 0; i < text.length; i++) {
                const char = text[i];
                const charWidth = helveticaFont.widthOfTextAtSize(
                  char,
                  fontSize
                );
                const charCenterX = currentX + (charSpacing - charWidth) / 2;
                targetPage.drawText(char, {
                  x: charCenterX,
                  y: pdfY,
                  size: fontSize,
                  font: helveticaFont,
                });
                currentX += charSpacing;
              }
              successCount++;
            }
          }
        } else if (coordKey.includes("birthdate")) {
          const dateParts = parseDate(value);
          if (dateParts) {
            const dateStr = `${dateParts.month}/${dateParts.day}/${dateParts.year}`;
            const fontSize = coords.fontSize || 10;
            const charSpacing = coords.charSpacing || 12;
            let currentX = pdfX;
            for (let i = 0; i < dateStr.length; i++) {
              const char = dateStr[i];
              if (char === "/") {
                currentX += charSpacing;
                continue;
              }
              const charWidth = helveticaFont.widthOfTextAtSize(char, fontSize);
              const charCenterX = currentX + (charSpacing - charWidth) / 2;
              targetPage.drawText(char, {
                x: charCenterX,
                y: pdfY,
                size: fontSize,
                font: helveticaFont,
              });
              currentX += charSpacing;
            }
            successCount++;
          }
        } else if (coordKey.startsWith("tin_")) {
          const tinValue = String(value).replace(/[-\s]/g, "");
          const part = coordKey.includes("_1")
            ? tinValue.substring(0, 3)
            : coordKey.includes("_2")
              ? tinValue.substring(3, 6)
              : tinValue.substring(6, 9);

          if (coords.charSpacing) {
            const fontSize = coords.fontSize || 10;
            const charSpacing = coords.charSpacing;
            let currentX = pdfX;
            for (let i = 0; i < part.length; i++) {
              const char = part[i];
              const charWidth = helveticaFont.widthOfTextAtSize(char, fontSize);
              const charCenterX = currentX + (charSpacing - charWidth) / 2;
              targetPage.drawText(char, {
                x: charCenterX,
                y: pdfY,
                size: fontSize,
                font: helveticaFont,
              });
              currentX += charSpacing;
            }
            successCount++;
          } else {
            targetPage.drawText(part, {
              x: pdfX,
              y: pdfY,
              size: coords.fontSize || 10,
              font: helveticaFont,
            });
            successCount++;
          }
        } else if (coordKey === "sss_gsis_no") {
          const textValue = String(value).replace(/[-\s]/g, "");

          if (coords.charSpacing) {
            const fontSize = coords.fontSize || 10;
            const charSpacing = coords.charSpacing;
            let currentX = pdfX;
            for (let i = 0; i < textValue.length; i++) {
              const char = textValue[i];
              const charWidth = helveticaFont.widthOfTextAtSize(char, fontSize);
              const charCenterX = currentX + (charSpacing - charWidth) / 2;
              targetPage.drawText(char, {
                x: charCenterX,
                y: pdfY,
                size: fontSize,
                font: helveticaFont,
              });
              currentX += charSpacing;
            }
            successCount++;
          } else {
            targetPage.drawText(textValue, {
              x: pdfX,
              y: pdfY,
              size: coords.fontSize || 10,
              font: helveticaFont,
              maxWidth: coords.maxWidth || 200,
              color: rgb(0, 0, 0),
            });
            successCount++;
          }
        } else if (coordKey === "sss_no" || coordKey === "gsis_no") {
          const textValue = String(value).replace(/[-\s]/g, "");

          if (coords.charSpacing) {
            const fontSize = coords.fontSize || 10;
            const charSpacing = coords.charSpacing;
            let currentX = pdfX;
            for (let i = 0; i < textValue.length; i++) {
              const char = textValue[i];
              const charWidth = helveticaFont.widthOfTextAtSize(char, fontSize);
              const charCenterX = currentX + (charSpacing - charWidth) / 2;
              targetPage.drawText(char, {
                x: charCenterX,
                y: pdfY,
                size: fontSize,
                font: helveticaFont,
              });
              currentX += charSpacing;
            }
            successCount++;
          } else {
            targetPage.drawText(textValue, {
              x: pdfX,
              y: pdfY,
              size: coords.fontSize || 10,
              font: helveticaFont,
              maxWidth: coords.maxWidth || 200,
              color: rgb(0, 0, 0),
            });
            successCount++;
          }
        } else if (coordKey === "employee_no") {
          const textValue = String(value).toUpperCase();

          if (coords.charSpacing) {
            const fontSize = coords.fontSize || 10;
            const charSpacing = coords.charSpacing;
            let currentX = pdfX;
            for (let i = 0; i < textValue.length; i++) {
              const char = textValue[i];
              const charWidth = helveticaFont.widthOfTextAtSize(char, fontSize);
              const charCenterX = currentX + (charSpacing - charWidth) / 2;
              targetPage.drawText(char, {
                x: charCenterX,
                y: pdfY,
                size: fontSize,
                font: helveticaFont,
              });
              currentX += charSpacing;
            }
            successCount++;
          } else {
            targetPage.drawText(textValue, {
              x: pdfX,
              y: pdfY,
              size: coords.fontSize || 10,
              font: helveticaFont,
              maxWidth: coords.maxWidth || 200,
              color: rgb(0, 0, 0),
            });
            successCount++;
          }
        } else {
          const textValue =
            coordKey === "email"
              ? String(value).toLowerCase()
              : String(value).toUpperCase();
          const fontSize = coords.fontSize || 10;
          const isAddressField =
            coordKey.includes("address") && !coordKey.includes("zip");

          targetPage.drawText(textValue, {
            x: pdfX,
            y: pdfY,
            size: fontSize,
            font: helveticaFont,
            maxWidth: isAddressField ? undefined : coords.maxWidth || 200,
            color: rgb(0, 0, 0),
          });
          successCount++;
        }
      } catch (e) {
        failCount++;
      }
    }

    try {
      if (data.sex) {
        const sexValue = String(data.sex).toLowerCase();
        const coordKey =
          sexValue === "male" || sexValue === "m" ? "sex_male" : "sex_female";
        if (mappedFields.size === 0 || mappedFields.has(coordKey)) {
          const pageNumber = fieldPageMap[coordKey] || 1;
          const coords = getPagIbigMDFCoordinates(coordKey, pageNumber);
          if (coords) {
            const targetPage =
              pageNumber === 2 && secondPage ? secondPage : firstPage;
            const pageHeight =
              pageNumber === 2 && secondPage ? secondPageHeight : height;
            const pdfY = pageHeight - coords.y;
            drawCheckbox(targetPage, coords.x, pdfY, helveticaFont);
            successCount++;
          }
        }
      }

      if (data.civil_status) {
        const statusValue = String(data.civil_status)
          .toLowerCase()
          .replace(" ", "_");
        let coordKey = `marital_status_${statusValue}`;
        if (statusValue === "widow" || statusValue === "widower")
          coordKey = "marital_status_widow";
        if (mappedFields.size === 0 || mappedFields.has(coordKey)) {
          const pageNumber = fieldPageMap[coordKey] || 1;
          const coords = getPagIbigMDFCoordinates(coordKey, pageNumber);
          if (coords) {
            const targetPage =
              pageNumber === 2 && secondPage ? secondPage : firstPage;
            const pageHeight =
              pageNumber === 2 && secondPage ? secondPageHeight : height;
            const pdfY = pageHeight - coords.y;
            drawCheckbox(targetPage, coords.x, pdfY, helveticaFont);
            successCount++;
          }
        }
      }
    } catch (e) {
    }

    const filledPdfBytes = await pdfDoc.save();
    return new Blob([filledPdfBytes], { type: "application/pdf" });
  } catch (error) {
    console.error("[Pag-IBIG MDF] Error filling PDF:", error);
    throw error;
  }
}
