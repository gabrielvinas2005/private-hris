/**
 * Coordinate mappings for PhilHealth PMRF PDF
 *
 * PDF coordinates are in points (72 DPI)
 * Standard US Letter size: 612 x 792 points (8.5" x 11")
 *
 * Y coordinate: 0 is at the BOTTOM of the page, increases upward
 * X coordinate: 0 is at the LEFT of the page, increases rightward
 *
 * To find coordinates:
 * 1. Open PDF in a PDF editor (Adobe Acrobat, PDF.js viewer, etc.)
 * 2. Note the position of each field
 * 3. Convert inches to points: 1 inch = 72 points
 * 4. Adjust Y coordinate: Y = 792 - (distance from top in points)
 */

export const philHealthPMRFCoordinates = {
  // Page 1 coordinates
  page1: {
    // I. PERSONAL DETAILS - Member Information
    member_last_name: {
      x: 50, // Adjust based on actual position
      y: 700, // Distance from bottom (adjust based on actual position)
      fontSize: 10,
      maxWidth: 150,
    },
    member_first_name: {
      x: 220,
      y: 700,
      fontSize: 10,
      maxWidth: 150,
    },
    member_middle_name: {
      x: 380,
      y: 700,
      fontSize: 10,
      maxWidth: 100,
    },
    member_name_extension: {
      x: 490,
      y: 700,
      fontSize: 10,
      maxWidth: 80,
    },

    // Date of Birth
    date_of_birth_month: {
      x: 50,
      y: 680,
      fontSize: 10,
      maxWidth: 30,
    },
    date_of_birth_day: {
      x: 90,
      y: 680,
      fontSize: 10,
      maxWidth: 30,
    },
    date_of_birth_year: {
      x: 130,
      y: 680,
      fontSize: 10,
      maxWidth: 50,
    },

    // Place of Birth
    place_of_birth: {
      x: 200,
      y: 680,
      fontSize: 10,
      maxWidth: 350,
    },

    // PhilHealth Number (PIN) - individual digit boxes
    philhealth_no: {
      x: 50,
      y: 660,
      fontSize: 10,
      maxWidth: 200,
      // For individual digits, you might need separate coordinates
    },

    // TIN Number
    tin_no: {
      x: 50,
      y: 640,
      fontSize: 10,
      maxWidth: 200,
    },

    // Contact Information
    mobile_number: {
      x: 50,
      y: 620,
      fontSize: 10,
      maxWidth: 150,
    },
    email: {
      x: 220,
      y: 620,
      fontSize: 10,
      maxWidth: 250,
    },

    // Permanent Address
    permanent_address: {
      x: 50,
      y: 580,
      fontSize: 9,
      maxWidth: 400,
      multiline: true,
    },
    permanent_zip: {
      x: 460,
      y: 580,
      fontSize: 10,
      maxWidth: 80,
    },

    // Mailing Address
    mailing_address: {
      x: 50,
      y: 540,
      fontSize: 9,
      maxWidth: 400,
      multiline: true,
    },
    mailing_zip: {
      x: 460,
      y: 540,
      fontSize: 10,
      maxWidth: 80,
    },

    // Purpose checkboxes (approximate positions)
    purpose_registration: {
      x: 400,
      y: 750,
      fontSize: 10,
      checkbox: true,
    },
    purpose_updating: {
      x: 480,
      y: 750,
      fontSize: 10,
      checkbox: true,
    },

    // Sex checkboxes
    sex_male: {
      x: 50,
      y: 600,
      fontSize: 10,
      checkbox: true,
    },
    sex_female: {
      x: 100,
      y: 600,
      fontSize: 10,
      checkbox: true,
    },

    // Civil Status checkboxes
    civil_status_single: {
      x: 200,
      y: 600,
      fontSize: 10,
      checkbox: true,
    },
    civil_status_married: {
      x: 250,
      y: 600,
      fontSize: 10,
      checkbox: true,
    },
    // Add more civil status options as needed

    // Citizenship checkboxes
    citizenship_filipino: {
      x: 350,
      y: 600,
      fontSize: 10,
      checkbox: true,
    },
    citizenship_dual: {
      x: 420,
      y: 600,
      fontSize: 10,
      checkbox: true,
    },
    citizenship_foreign: {
      x: 500,
      y: 600,
      fontSize: 10,
      checkbox: true,
    },
  },
  // Page 2 coordinates
  page2: {
    // Signature fields (on page 2 - Member's Signature over Printed Name section)
    signature_last_name: {
      x: 50,
      y: 340,
      fontSize: 10,
      maxWidth: 150,
    },
    signature_first_name: {
      x: 200,
      y: 340,
      fontSize: 10,
      maxWidth: 150,
    },
    signature_middle_name: {
      x: 350,
      y: 340,
      fontSize: 10,
      maxWidth: 100,
    },
    signature_name_extension: {
      x: 450,
      y: 340,
      fontSize: 10,
      maxWidth: 80,
    },
  },
};

/**
 * Helper function to get coordinates for a field
 */
export function getFieldCoordinates(fieldName, page = 1) {
  const pageKey = `page${page}`;
  return philHealthPMRFCoordinates[pageKey]?.[fieldName] || null;
}

/**
 * NOTE: These coordinates are PLACEHOLDERS
 */

// ========== BIR Form 2305 Coordinates ==========

export const birForm2305Coordinates = {
  page1: {
    // TIN (3 groups)
    tin_1: { x: 50, y: 700, fontSize: 10, charSpacing: 15, maxWidth: 50 },
    tin_2: { x: 150, y: 700, fontSize: 10, charSpacing: 15, maxWidth: 50 },
    tin_3: { x: 250, y: 700, fontSize: 10, charSpacing: 15, maxWidth: 50 },

    // RDO Code
    rdo_code: { x: 400, y: 700, fontSize: 10, maxWidth: 100 },

    // Taxpayer Name
    last_name: { x: 50, y: 680, fontSize: 10, maxWidth: 200 },
    first_name: { x: 260, y: 680, fontSize: 10, maxWidth: 200 },
    middle_name: { x: 470, y: 680, fontSize: 10, maxWidth: 100 },

    // Date of Birth (split into components like PhilHealth)
    date_of_birth_month: {
      x: 50,
      y: 660,
      fontSize: 10,
      charSpacing: 10,
      maxWidth: 30,
    },
    date_of_birth_day: {
      x: 90,
      y: 660,
      fontSize: 10,
      charSpacing: 10,
      maxWidth: 30,
    },
    date_of_birth_year: {
      x: 130,
      y: 660,
      fontSize: 10,
      charSpacing: 10,
      maxWidth: 50,
    },

    // Addresses
    residence_address: {
      x: 50,
      y: 640,
      fontSize: 9,
      maxWidth: 400,
      multiline: true,
    },
    residence_zip: {
      x: 460,
      y: 640,
      fontSize: 10,
      charSpacing: 30,
      maxWidth: 80,
    },
    business_address: {
      x: 50,
      y: 600,
      fontSize: 9,
      maxWidth: 400,
      multiline: true,
    },
    business_zip: { x: 460, y: 600, fontSize: 10, maxWidth: 80 },

    // Sex checkboxes
    sex_male: { x: 400, y: 660, fontSize: 10, checkbox: true },
    sex_female: { x: 450, y: 660, fontSize: 10, checkbox: true },

    // Civil Status checkboxes
    civil_status_single: { x: 50, y: 580, fontSize: 10, checkbox: true },
    civil_status_married: { x: 150, y: 580, fontSize: 10, checkbox: true },
    civil_status_legally_separated: {
      x: 250,
      y: 580,
      fontSize: 10,
      checkbox: true,
    },
    civil_status_widow: { x: 350, y: 580, fontSize: 10, checkbox: true },

    // Employer Information
    employer_tin_1: {
      x: 50,
      y: 500,
      fontSize: 10,
      charSpacing: 12,
      maxWidth: 50,
    },
    employer_tin_2: {
      x: 150,
      y: 500,
      fontSize: 10,
      charSpacing: 12,
      maxWidth: 50,
    },
    employer_tin_3: {
      x: 250,
      y: 500,
      fontSize: 10,
      charSpacing: 12,
      maxWidth: 50,
    },
    employer_rdo: { x: 400, y: 500, fontSize: 10, maxWidth: 100 },
    employer_name: { x: 50, y: 480, fontSize: 10, maxWidth: 400 },
    employer_address: {
      x: 50,
      y: 460,
      fontSize: 9,
      maxWidth: 400,
      multiline: true,
    },
    employer_zip: { x: 460, y: 460, fontSize: 10, maxWidth: 80 },

    // Spouse Information
    spouse_tin: { x: 50, y: 420, fontSize: 10, charSpacing: 12, maxWidth: 150 },
    spouse_last_name: { x: 50, y: 400, fontSize: 10, maxWidth: 150 },
    spouse_first_name: { x: 210, y: 400, fontSize: 10, maxWidth: 150 },
    spouse_middle_name: { x: 370, y: 400, fontSize: 10, maxWidth: 100 },

    // Dependents (example for first dependent)
    dependent_1_last_name: { x: 50, y: 360, fontSize: 9, maxWidth: 120 },
    dependent_1_first_name: { x: 180, y: 360, fontSize: 9, maxWidth: 120 },
    dependent_1_middle_name: { x: 310, y: 360, fontSize: 9, maxWidth: 100 },
    dependent_1_birthdate: {
      x: 420,
      y: 360,
      fontSize: 9,
      charSpacing: 10,
      maxWidth: 120,
    },

    // Effective Date
    effective_date: {
      x: 400,
      y: 720,
      fontSize: 10,
      charSpacing: 12,
      maxWidth: 150,
    },

    // Certification Date
    certification_date: {
      x: 400,
      y: 300,
      fontSize: 10,
      charSpacing: 12,
      maxWidth: 150,
    },

    // Type of Filer checkboxes
    filer_employee: { x: 50, y: 720, fontSize: 10, checkbox: true },
    filer_self_employed: { x: 200, y: 720, fontSize: 10, checkbox: true },
  },
};

export function getBIRForm2305Coordinates(fieldName, page = 1) {
  const pageKey = `page${page}`;
  return birForm2305Coordinates[pageKey]?.[fieldName] || null;
}

// Pag-IBIG MDF Coordinates
export const pagIbigMDFCoordinates = {
  page1: {
    // Member's Name
    member_last_name: { x: 50, y: 700, fontSize: 10, maxWidth: 150 },
    member_first_name: { x: 220, y: 700, fontSize: 10, maxWidth: 150 },
    member_middle_name: { x: 380, y: 700, fontSize: 10, maxWidth: 100 },
    member_name_extension: { x: 490, y: 700, fontSize: 10, maxWidth: 80 },

    // Father's Name
    father_last_name: { x: 50, y: 680, fontSize: 10, maxWidth: 150 },
    father_first_name: { x: 220, y: 680, fontSize: 10, maxWidth: 150 },
    father_middle_name: { x: 380, y: 680, fontSize: 10, maxWidth: 100 },

    // Mother's Maiden Name
    mother_last_name: { x: 50, y: 660, fontSize: 10, maxWidth: 150 },
    mother_first_name: { x: 220, y: 660, fontSize: 10, maxWidth: 150 },
    mother_middle_name: { x: 380, y: 660, fontSize: 10, maxWidth: 100 },

    // Spouse Name
    spouse_last_name: { x: 50, y: 640, fontSize: 10, maxWidth: 150 },
    spouse_first_name: { x: 220, y: 640, fontSize: 10, maxWidth: 150 },
    spouse_middle_name: { x: 380, y: 640, fontSize: 10, maxWidth: 100 },

    // Date of Birth (split into components)
    date_of_birth_month: { x: 50, y: 620, fontSize: 10, charSpacing: 10 },
    date_of_birth_day: { x: 80, y: 620, fontSize: 10, charSpacing: 10 },
    date_of_birth_year: { x: 110, y: 620, fontSize: 10, charSpacing: 10 },

    // Place of Birth
    place_of_birth: { x: 200, y: 620, fontSize: 10, maxWidth: 300 },

    // Sex
    sex_male: { x: 50, y: 600, fontSize: 10 },
    sex_female: { x: 120, y: 600, fontSize: 10 },

    // Marital Status
    marital_status_single: { x: 200, y: 600, fontSize: 10 },
    marital_status_married: { x: 280, y: 600, fontSize: 10 },
    marital_status_widow: { x: 360, y: 600, fontSize: 10 },
    marital_status_annulled: { x: 440, y: 600, fontSize: 10 },
    marital_status_legally_separated: { x: 520, y: 600, fontSize: 10 },

    // Citizenship
    citizenship: { x: 50, y: 580, fontSize: 10, maxWidth: 150 },

    // TIN (split into parts)
    tin_1: { x: 250, y: 580, fontSize: 10, charSpacing: 15 },
    tin_2: { x: 300, y: 580, fontSize: 10, charSpacing: 15 },
    tin_3: { x: 350, y: 580, fontSize: 10, charSpacing: 15 },

    // SSS/GSIS Number
    sss_no: { x: 450, y: 580, fontSize: 10, maxWidth: 120 },
    gsis_no: { x: 450, y: 560, fontSize: 10, maxWidth: 120 },
    sss_gsis_no: { x: 450, y: 580, fontSize: 10, maxWidth: 120 },

    // Employee Number
    employee_no: { x: 50, y: 540, fontSize: 10, maxWidth: 150 },

    // Height and Weight
    height: { x: 250, y: 540, fontSize: 10, maxWidth: 80 },
    weight: { x: 350, y: 540, fontSize: 10, maxWidth: 80 },

    // Contact Details
    mobile_no: { x: 50, y: 520, fontSize: 10, maxWidth: 150 },
    email: { x: 220, y: 520, fontSize: 10, maxWidth: 200 },

    // Permanent Address - First Row
    permanent_address_unit_room_floor: {
      x: 50,
      y: 500,
      fontSize: 9,
      maxWidth: 100,
    },
    permanent_address_building_name: {
      x: 160,
      y: 500,
      fontSize: 9,
      maxWidth: 120,
    },
    permanent_address_lot_block_phase_house: {
      x: 290,
      y: 500,
      fontSize: 9,
      maxWidth: 120,
    },
    permanent_address_street_name: {
      x: 420,
      y: 500,
      fontSize: 9,
      maxWidth: 130,
    },

    // Permanent Address - Second Row
    permanent_address_subdivision: {
      x: 50,
      y: 480,
      fontSize: 9,
      maxWidth: 100,
    },
    permanent_address_barangay: { x: 160, y: 480, fontSize: 9, maxWidth: 100 },
    permanent_address_municipality_city: {
      x: 270,
      y: 480,
      fontSize: 9,
      maxWidth: 120,
    },
    permanent_address_province_state_country: {
      x: 400,
      y: 480,
      fontSize: 9,
      maxWidth: 100,
    },
    permanent_address_zip: { x: 510, y: 480, fontSize: 9, maxWidth: 50 },

    // Present Address - First Row
    present_address_unit_room_floor: {
      x: 50,
      y: 460,
      fontSize: 9,
      maxWidth: 100,
    },
    present_address_building_name: {
      x: 160,
      y: 460,
      fontSize: 9,
      maxWidth: 120,
    },
    present_address_lot_block_phase_house: {
      x: 290,
      y: 460,
      fontSize: 9,
      maxWidth: 120,
    },
    present_address_street_name: { x: 420, y: 460, fontSize: 9, maxWidth: 130 },

    // Present Address - Second Row
    present_address_subdivision: { x: 50, y: 440, fontSize: 9, maxWidth: 100 },
    present_address_barangay: { x: 160, y: 440, fontSize: 9, maxWidth: 100 },
    present_address_municipality_city: {
      x: 270,
      y: 440,
      fontSize: 9,
      maxWidth: 120,
    },
    present_address_province_state_country: {
      x: 400,
      y: 440,
      fontSize: 9,
      maxWidth: 100,
    },
    present_address_zip: { x: 510, y: 440, fontSize: 9, maxWidth: 50 },

    // Legacy fields for backward compatibility
    present_address: { x: 50, y: 440, fontSize: 10, maxWidth: 500 },
    present_zip: { x: 560, y: 440, fontSize: 10, maxWidth: 80 },

    // Employer Information
    employer_name: { x: 50, y: 420, fontSize: 10, maxWidth: 300 },
    employer_address: { x: 50, y: 400, fontSize: 10, maxWidth: 500 },
    employer_zip: { x: 560, y: 400, fontSize: 10, maxWidth: 80 },
    monthly_compensation: { x: 50, y: 380, fontSize: 10, maxWidth: 150 },
    date_employed: { x: 250, y: 380, fontSize: 10, maxWidth: 150 },

    // Dependent Information
    dependent_1_last_name: { x: 50, y: 360, fontSize: 10, maxWidth: 150 },
    dependent_1_first_name: { x: 220, y: 360, fontSize: 10, maxWidth: 150 },
    dependent_1_middle_name: { x: 380, y: 360, fontSize: 10, maxWidth: 100 },
    dependent_1_birthdate: { x: 500, y: 360, fontSize: 10, maxWidth: 120 },
  },
};

export function getPagIbigMDFCoordinates(fieldKey, page = 1) {
  const pageKey = `page${page}`;
  return pagIbigMDFCoordinates[pageKey]?.[fieldKey] || null;
}
