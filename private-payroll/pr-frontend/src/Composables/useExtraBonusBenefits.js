import { ref, reactive, watch } from "vue";
import { divisionsFromApi, divisionParams } from "../utils/payrollReportDivisions.js";
import { formatApiError } from "../utils/apiErrorMessage.js";
import { extraBonusApi } from "../services/api.js";
import { ElMessage } from "element-plus";

const SIGNATORIES_STORAGE_KEY = "rptExtraBonusPayroll_signatories";

// Composable dedicated to Extra Bonus Benefits Report
export function useExtraBonusBenefits() {
  const loading = ref(false);
  const error = ref(null);

  // Form data used by ReportParameters.vue
  const formData = reactive({
    extra_bonus_type_id: "",
    division_id: "",
    year: "",
  });

  // Signatories used by SignatoriesSection.vue (note: underscore keys for UI)
  const signatories = reactive({
    signatory_1: "",
    signatory_position_1: "",
    signatory_2: "",
    signatory_position_2: "",
    signatory_3: "",
    signatory_position_3: "",
    signatory_4: "",
    signatory_position_4: "",
    signatory_5: "",
    signatory_position_5: "",
  });

  // Dropdowns
  const divisions = ref([]);
  const extraBonusTypes = ref([]);
  const years = ref([]);
  const signatoryOptions = ref([]);

  // Generate years from current year down to 2005
  const generateYears = () => {
    const currentYear = new Date().getFullYear();
    const minYear = 2005;
    const list = [];
    for (let y = currentYear; y >= minYear; y -= 1) {
      list.push({ id: y, name: String(y) });
    }
    years.value = list;
    // Don't auto-select year - let user choose
  };

  // Restore signatories from localStorage
  const restoreSignatoriesFromStorage = () => {
    try {
      const stored = localStorage.getItem(SIGNATORIES_STORAGE_KEY);
      if (stored) {
        const parsed = JSON.parse(stored);
        Object.keys(parsed).forEach((key) => {
          if (signatories.hasOwnProperty(key)) {
            signatories[key] = parsed[key] || "";
          }
        });
      }
    } catch {
      // Ignore parse errors
    }
  };

  // Persist signatories to localStorage whenever they change
  watch(
    signatories,
    () => {
      try {
        localStorage.setItem(SIGNATORIES_STORAGE_KEY, JSON.stringify({ ...signatories }));
      } catch {
        // Ignore quota errors
      }
    },
    { deep: true }
  );

  // Load initial data for Extra Bonus Benefits Report
  const loadInitialData = async () => {
    try {
      loading.value = true;
      error.value = null;

      // Load Extra Bonus report form data to get divisions, extra bonus types, and signatory options
      const response = await extraBonusApi.getReportData();
      const data = response.data?.data || {};

      divisions.value = divisionsFromApi(data) || [];
      extraBonusTypes.value = data.extra_bonuses || [];
      signatoryOptions.value = data.signatory_options || [];

      generateYears();
      restoreSignatoriesFromStorage();

      return data;
    } catch (err) {
      error.value =
        err.response?.data?.message || "Failed to load initial data";
      ElMessage.error(error.value);
      throw err;
    } finally {
      loading.value = false;
    }
  };

  // Validate that all 5 signatories (name + position) are filled
  const validateSignatories = () => {
    for (let i = 1; i <= 5; i++) {
      const name = signatories[`signatory_${i}`];
      const position = signatories[`signatory_position_${i}`];
      if (!name || !position) {
        return `Please select signatory ${i} (name and position must be filled).`;
      }
    }
    return null;
  };

  const handleReportError = (err, fallback) => {
    const msg = formatApiError(err, fallback);
    error.value = msg;
    ElMessage.error(msg);
    throw err;
  };

  // Map UI signatories to controller print parameters
  const buildPrintParams = () => ({
    extra_bonus_type_id: formData.extra_bonus_type_id,
    year_id: formData.year,
    ...divisionParams(formData),
    // Controller expects signatory_1..signatory_5 (with underscores)
    signatory_1: signatories.signatory_1,
    signatory_position_1: signatories.signatory_position_1,
    signatory_2: signatories.signatory_2,
    signatory_position_2: signatories.signatory_position_2,
    signatory_3: signatories.signatory_3,
    signatory_position_3: signatories.signatory_position_3,
    signatory_4: signatories.signatory_4,
    signatory_position_4: signatories.signatory_position_4,
    signatory_5: signatories.signatory_5,
    signatory_position_5: signatories.signatory_position_5,
  });

  // Preview report (open PDF in new tab)
  const previewReport = async () => {
    try {
      loading.value = true;
      error.value = null;

      if (
        !formData.extra_bonus_type_id ||
        !formData.division_id ||
        !formData.year
      ) {
        throw new Error("Extra Bonus Type, Division, and Year are required.");
      }

      const signatoryError = validateSignatories();
      if (signatoryError) {
        throw new Error(signatoryError);
      }

      const response = await extraBonusApi.generateReport(buildPrintParams());

      const blob = response.data;
      if (!blob || blob.size === 0) {
        throw new Error(
          "The report file is empty. No extra bonus payroll data was found for the selected Division, bonus type, and year.",
        );
      }

      const url = window.URL.createObjectURL(blob);
      window.open(url, "_blank");
      setTimeout(() => window.URL.revokeObjectURL(url), 1000);

      ElMessage.success("Extra Bonus preview opened");
      return blob;
    } catch (err) {
      if (!error.value) {
        if (err?.message && !err?.response) {
          error.value = err.message;
          ElMessage.error(err.message);
        } else {
          handleReportError(err, "Failed to preview the extra bonus payroll report.");
        }
      }
    } finally {
      loading.value = false;
    }
  };

  // Download report
  const generateReport = async () => {
    try {
      loading.value = true;
      error.value = null;

      if (
        !formData.extra_bonus_type_id ||
        !formData.division_id ||
        !formData.year
      ) {
        throw new Error("Extra Bonus Type, Division, and Year are required.");
      }

      const signatoryError = validateSignatories();
      if (signatoryError) {
        throw new Error(signatoryError);
      }

      const response = await extraBonusApi.generateReport(buildPrintParams());
      const blob = response.data;
      if (!blob || blob.size === 0) {
        throw new Error(
          "The report file is empty. No extra bonus payroll data was found for the selected Division, bonus type, and year.",
        );
      }

      const url = window.URL.createObjectURL(blob);
      const link = document.createElement("a");
      link.href = url;
      link.download = `extra-bonus-report-${formData.extra_bonus_type_id}-${formData.division_id}-${formData.year}-${new Date().toISOString().split("T")[0]}.pdf`;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);

      ElMessage.success("Report downloaded successfully");
      return blob;
    } catch (err) {
      if (!error.value) {
        if (err?.message && !err?.response) {
          error.value = err.message;
          ElMessage.error(err.message);
        } else {
          handleReportError(err, "Failed to generate the extra bonus payroll report.");
        }
      }
    } finally {
      loading.value = false;
    }
  };

  return {
    loading,
    error,
    formData,
    signatories,
    divisions,
    extraBonusTypes,
    years,
    signatoryOptions,
    loadInitialData,
    previewReport,
    generateReport,
  };
}
