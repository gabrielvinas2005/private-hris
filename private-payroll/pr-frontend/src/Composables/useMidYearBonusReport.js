import { ref, reactive, watch } from "vue";
import { divisionsFromApi, divisionParams } from "../utils/payrollReportDivisions.js";
import { formatApiError } from "../utils/apiErrorMessage.js";
import { midYearBonusApi } from "../services/api.js";
import { ElMessage } from "element-plus";

const buildPrintRequest = (formData, signatories) => ({
  branch_id: formData.branch_id || undefined,
  ...divisionParams(formData),
  years: formData.year,
  signatory_1: signatories.signatory_1 || "",
  signatory_position_1: signatories.signatory_position_1 || "",
  signatory_2: signatories.signatory_2 || "",
  signatory_position_2: signatories.signatory_position_2 || "",
});

export function useMidYearBonusReport() {
  const loading = ref(false);
  const error = ref(null);

  // Form data
  const formData = reactive({
    branch_id: "",
    division_id: "",
    year: "",
  });

  // Signatories data
  const signatories = reactive({
    signatory_1: "",
    signatory_position_1: "",
    signatory_2: "",
    signatory_position_2: "",
  });

  // Dropdown data
  const branches = ref([]);
  const divisions = ref([]);
  const years = ref([]);
  const signatoryOptions = ref([]);

  const getStorageKey = (branchId) =>
    `rptMidYearBonus_signatories_${branchId || "default"}`;

  const resetSignatories = () => {
    Object.keys(signatories).forEach((key) => {
      signatories[key] = "";
    });
  };

  const restoreSignatoriesFromStorage = (branchId) => {
    try {
      const stored = localStorage.getItem(getStorageKey(branchId));
      if (!stored) return;
      const parsed = JSON.parse(stored);
      Object.keys(signatories).forEach((key) => {
        if (Object.prototype.hasOwnProperty.call(parsed, key)) {
          signatories[key] = parsed[key] || "";
        }
      });
    } catch {
      // ignore parse errors
    }
  };

  // Persist signatories per branch so user selections don't disappear
  watch(
    () => ({
      ...signatories,
      branch_id: formData.branch_id,
    }),
    (val) => {
      if (!val.branch_id) return;
      try {
        const { branch_id, ...rest } = val;
        localStorage.setItem(
          getStorageKey(branch_id),
          JSON.stringify(rest)
        );
      } catch {
        // ignore storage errors
      }
    },
    { deep: true }
  );

  // Load initial data
  const loadInitialData = async () => {
    try {
      loading.value = true;
      error.value = null;

      const response = await midYearBonusApi.getMidYearBonusData();
      const data = response.data.data;

      branches.value = data.branches || [];
      divisions.value = divisionsFromApi(data) || [];
      signatoryOptions.value = data.employee_options || [];

      // Generate years from current year back to 2000
      const currentYear = new Date().getFullYear();
      years.value = Array.from({ length: currentYear - 1999 }, (_, i) => ({
        id: currentYear - i,
        name: (currentYear - i).toString(),
      }));

      // Try to restore previously selected signatories for the current branch
      if (formData.branch_id) {
        restoreSignatoriesFromStorage(formData.branch_id);
      }

      return data;
    } catch (err) {
      error.value =
        err.response?.data?.message || "Failed to load initial data";
      ElMessage.error("Failed to load initial data");
      throw err;
    } finally {
      loading.value = false;
    }
  };

  // Load signatories for selected branch
  const loadSignatories = async (branchId) => {
    if (!branchId) {
      resetSignatories();
      // Reload all employees when branch is cleared
      try {
        const response = await midYearBonusApi.getMidYearBonusData();
        const data = response.data.data;
        signatoryOptions.value = data.employee_options || [];
      } catch (err) {
        // Keep existing options if reload fails
        console.error("Failed to reload employee options:", err);
      }
      return;
    }

    try {
      loading.value = true;
      error.value = null;

      const response =
        await midYearBonusApi.getMidYearBonusReportData(branchId);
      const data = response.data.data;

      // Only override options when the API actually returns some;
      // otherwise keep the existing (global) list so the dropdown
      // never becomes empty after selecting report information.
      if (data?.employee_options && data.employee_options.length > 0) {
        signatoryOptions.value = data.employee_options;
      }

      if (data && data.signatories && data.signatories.length > 0) {
        const signatoryData = data.signatories[0];

        // Update signatories with fetched data
        signatories.signatory_1 = signatoryData.signatory_1 || "";
        signatories.signatory_position_1 =
          signatoryData.signatory_position_1 || "";
        signatories.signatory_2 = signatoryData.signatory_2 || "";
        signatories.signatory_position_2 =
          signatoryData.signatory_position_2 || "";
      } else {
        // If there are no saved signatories for this branch,
        // fall back to any locally stored user selections
        restoreSignatoriesFromStorage(branchId);
      }
    } catch (err) {
      error.value = err.response?.data?.message || "Failed to load signatories";
      ElMessage.error("Failed to load signatories");
      // On error, keep existing signatories and options so user input is preserved
    } finally {
      loading.value = false;
    }
  };

  // Process mid-year bonus
  const processMidYearBonus = async () => {
    try {
      loading.value = true;
      error.value = null;

      // Validate required fields
      if (!formData.division_id) {
        throw new Error("Department is required");
      }
      if (!formData.year) {
        throw new Error("Year is required");
      }

      // Prepare request data
      const requestData = {
        branch_id: formData.branch_id,
        division_id: formData.division_id,
        years: formData.year,
      };

      // Process mid-year bonus
      const response = await midYearBonusApi.processMidYearBonus(requestData);

      ElMessage.success("Mid-year bonus processed successfully");
      return response.data;
    } catch (err) {
      error.value =
        err.message ||
        err.response?.data?.message ||
        "Failed to process mid-year bonus";
      ElMessage.error("Failed to process mid-year bonus");
      throw err;
    } finally {
      loading.value = false;
    }
  };

  // Post mid-year bonus
  const postMidYearBonus = async () => {
    try {
      loading.value = true;
      error.value = null;

      // Validate required fields
      if (!formData.division_id) {
        throw new Error("Department is required");
      }
      if (!formData.year) {
        throw new Error("Year is required");
      }

      // Prepare request data
      const requestData = {
        branch_id: formData.branch_id,
        division_id: formData.division_id,
        years: formData.year,
      };

      // Post mid-year bonus
      const response = await midYearBonusApi.postMidYearBonus(requestData);

      ElMessage.success("Mid-year bonus posted successfully");
      return response.data;
    } catch (err) {
      error.value =
        err.message ||
        err.response?.data?.message ||
        "Failed to post mid-year bonus";
      ElMessage.error("Failed to post mid-year bonus");
      throw err;
    } finally {
      loading.value = false;
    }
  };

  // Generate report
  const generateReport = async () => {
    try {
      loading.value = true;
      error.value = null;

      // Validate required fields
      if (!formData.division_id) {
        throw new Error("Division is required");
      }
      if (!formData.year) {
        throw new Error("Year is required");
      }
      if (!signatories.signatory_1 || !signatories.signatory_position_1) {
        throw new Error(
          "Certifying Officer name and position are required before generating the report"
        );
      }
      if (!signatories.signatory_2 || !signatories.signatory_position_2) {
        throw new Error(
          "Approving Officer name and position are required before generating the report"
        );
      }

      const requestData = buildPrintRequest(formData, signatories);

      const response =
        await midYearBonusApi.generateMidYearBonusReport(requestData);

      if (response.data.size === 0) {
        throw new Error(
          "Generated PDF is empty - no mid-year bonus data found"
        );
      }

      console.log("PDF blob size:", response.data.size);

      // Create download link
      const url = window.URL.createObjectURL(response.data);
      const link = document.createElement("a");
      link.href = url;
      link.download = `mid-year-bonus-report-${formData.year}-${new Date().toISOString().split("T")[0]}.pdf`;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);

      ElMessage.success("Mid-year bonus report generated successfully");
      return response.data;
    } catch (err) {
      const message = formatApiError(err, "Failed to generate report");
      error.value = message;
      ElMessage.error(message);
    } finally {
      loading.value = false;
    }
  };

  // Preview report (opens in new tab)
  const previewReport = async () => {
    try {
      loading.value = true;
      error.value = null;

      // Validate required fields
      if (!formData.division_id) {
        throw new Error("Division is required");
      }
      if (!formData.year) {
        throw new Error("Year is required");
      }
      if (!signatories.signatory_1 || !signatories.signatory_position_1) {
        throw new Error(
          "Certifying Officer name and position are required before previewing the report"
        );
      }
      if (!signatories.signatory_2 || !signatories.signatory_position_2) {
        throw new Error(
          "Approving Officer name and position are required before previewing the report"
        );
      }

      const requestData = buildPrintRequest(formData, signatories);

      const response =
        await midYearBonusApi.generateMidYearBonusReport(requestData);

      if (response.data.size === 0) {
        throw new Error(
          "Generated PDF is empty - no mid-year bonus data found"
        );
      }

      // Create blob URL and open in new tab
      const url = window.URL.createObjectURL(response.data);
      window.open(url, "_blank");

      // Clean up the URL after a delay
      setTimeout(() => {
        window.URL.revokeObjectURL(url);
      }, 1000);

      ElMessage.success("Mid-year bonus report preview opened");
      return response.data;
    } catch (err) {
      const message = formatApiError(err, "Failed to preview report");
      error.value = message;
      ElMessage.error(message);
    } finally {
      loading.value = false;
    }
  };

  // Reset form
  const resetForm = () => {
    formData.branch_id = "";
    formData.division_id = "";
    formData.year = "";
  };

  return {
    // State
    loading,
    error,
    formData,
    signatories,
    branches,
    divisions,
    years,
    signatoryOptions,

    // Methods
    loadInitialData,
    loadSignatories,
    processMidYearBonus,
    postMidYearBonus,
    generateReport,
    previewReport,
    resetForm,
  };
}
