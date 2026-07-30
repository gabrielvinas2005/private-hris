import { ref, reactive } from "vue";
import { divisionsFromApi, divisionParams } from "../utils/payrollReportDivisions.js";
import { formatApiError } from "../utils/apiErrorMessage.js";
import { yearEndBonusApi, signatoryApi } from "../services/api.js";
import { ElMessage } from "element-plus";

export function useYearEndBonusReport() {
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
    signatory_3: "",
    signatory_position_3: "",
    signatory_4: "",
    signatory_position_4: "",
    signatory_5: "",
    signatory_position_5: "",
  });

  // Dropdown data
  const branches = ref([]);
  const divisions = ref([]);
  const years = ref([]);
  const availableSignatories = ref([]);

  const handleReportError = (err, fallback) => {
    const msg = formatApiError(err, fallback);
    error.value = msg;
    ElMessage.error(msg);
    throw err;
  };

  const buildReportPayload = () => ({
    years: formData.year,
    ...divisionParams(formData),
    ...signatories,
  });

  // Load initial data
  const loadInitialData = async () => {
    try {
      loading.value = true;
      error.value = null;

      const response = await yearEndBonusApi.getYearEndBonusData();
      const data = response.data.data;

      branches.value = data.branches || [];
      divisions.value = divisionsFromApi(data) || [];

      // Generate years from current year back to 2000
      const currentYear = new Date().getFullYear();
      years.value = Array.from({ length: currentYear - 1999 }, (_, i) => ({
        id: currentYear - i,
        name: (currentYear - i).toString(),
      }));

      // Load generic employee signatory options (name + position)
      try {
        const sigRes = await signatoryApi.getEmployeeOptions();
        const sdata = sigRes.data?.data || {};
        availableSignatories.value = sdata.employee_options || [];
      } catch {
        availableSignatories.value = [];
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
      // Reset signatories if no branch selected
      Object.keys(signatories).forEach((key) => {
        signatories[key] = "";
      });
      return;
    }

    try {
      loading.value = true;
      error.value = null;

      const response =
        await yearEndBonusApi.getYearEndBonusReportData(branchId);
      const data = response.data.data;

      if (data && data.signatories && data.signatories.length > 0) {
        const signatoryData = data.signatories[0];

        // Update signatories with fetched data
        signatories.signatory_1 = signatoryData.signatory_1 || "";
        signatories.signatory_position_1 =
          signatoryData.signatory_position_1 || "";
        signatories.signatory_2 = signatoryData.signatory_2 || "";
        signatories.signatory_position_2 =
          signatoryData.signatory_position_2 || "";
        signatories.signatory_3 = signatoryData.signatory_3 || "";
        signatories.signatory_position_3 =
          signatoryData.signatory_position_3 || "";
        signatories.signatory_4 = signatoryData.signatory_4 || "";
        signatories.signatory_position_4 =
          signatoryData.signatory_position_4 || "";
        signatories.signatory_5 = signatoryData.signatory_5 || "";
        signatories.signatory_position_5 =
          signatoryData.signatory_position_5 || "";
      } else {
        // Reset signatories if no data found
        Object.keys(signatories).forEach((key) => {
          signatories[key] = "";
        });
      }
    } catch (err) {
      error.value = err.response?.data?.message || "Failed to load signatories";
      ElMessage.error("Failed to load signatories");
      // Reset signatories on error
      Object.keys(signatories).forEach((key) => {
        signatories[key] = "";
      });
    } finally {
      loading.value = false;
    }
  };

  // Process year-end bonus
  const processYearEndBonus = async () => {
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

      // Process year-end bonus
      const response = await yearEndBonusApi.processYearEndBonus(requestData);

      ElMessage.success("Year-end bonus processed successfully");
      return response.data;
    } catch (err) {
      error.value =
        err.message ||
        err.response?.data?.message ||
        "Failed to process year-end bonus";
      ElMessage.error("Failed to process year-end bonus");
      throw err;
    } finally {
      loading.value = false;
    }
  };

  // Post year-end bonus
  const postYearEndBonus = async () => {
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

      // Post year-end bonus
      const response = await yearEndBonusApi.postYearEndBonus(requestData);

      ElMessage.success("Year-end bonus posted successfully");
      return response.data;
    } catch (err) {
      error.value =
        err.message ||
        err.response?.data?.message ||
        "Failed to post year-end bonus";
      ElMessage.error("Failed to post year-end bonus");
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
        throw new Error("Division is required.");
      }
      if (!formData.year) {
        throw new Error("Year is required.");
      }

      const response =
        await yearEndBonusApi.generateYearEndBonusReport(buildReportPayload());

      if (response.data.size === 0) {
        throw new Error(
          "Generated PDF is empty - no year-end bonus data found"
        );
      }

      console.log("PDF blob size:", response.data.size);

      // Create download link
      const url = window.URL.createObjectURL(response.data);
      const link = document.createElement("a");
      link.href = url;
      link.download = `year-end-bonus-report-${formData.year}-${new Date().toISOString().split("T")[0]}.pdf`;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);

      ElMessage.success("Year-end bonus report generated successfully");
      return response.data;
    } catch (err) {
      if (!error.value) {
        if (err?.message && !err?.response) {
          error.value = err.message;
          ElMessage.error(err.message);
        } else {
          handleReportError(err, "Failed to generate the year-end bonus report.");
        }
      }
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
        throw new Error("Division is required.");
      }
      if (!formData.year) {
        throw new Error("Year is required.");
      }

      const response =
        await yearEndBonusApi.generateYearEndBonusReport(buildReportPayload());

      if (response.data.size === 0) {
        throw new Error(
          "Generated PDF is empty - no year-end bonus data found"
        );
      }

      // Create blob URL and open in new tab
      const url = window.URL.createObjectURL(response.data);
      window.open(url, "_blank");

      // Clean up the URL after a delay
      setTimeout(() => {
        window.URL.revokeObjectURL(url);
      }, 1000);

      ElMessage.success("Year-end bonus report preview opened");
      return response.data;
    } catch (err) {
      if (!error.value) {
        if (err?.message && !err?.response) {
          error.value = err.message;
          ElMessage.error(err.message);
        } else {
          handleReportError(err, "Failed to preview the year-end bonus report.");
        }
      }
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
    availableSignatories,

    // Methods
    loadInitialData,
    loadSignatories,
    processYearEndBonus,
    postYearEndBonus,
    generateReport,
    previewReport,
    resetForm,
  };
}
