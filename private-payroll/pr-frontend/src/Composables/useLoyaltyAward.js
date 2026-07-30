import { ref, reactive } from "vue";
import { loyaltyAwardApi, signatoryApi } from "../services/api.js";
import { ElMessage } from "element-plus";

export function useLoyaltyAward() {
  const loading = ref(false);
  const error = ref(null);

  // Form data
  const formData = reactive({
    payroll_period_id: "",
  });

  // Signatories data
  const signatories = reactive({
    signatory_1: "",
    signatory_position_1: "",
    description_1: "",
    signatory_2: "",
    signatory_position_2: "",
    description_2: "",
    signatory_3: "",
    signatory_position_3: "",
    description_3: "",
    signatory_4: "",
    signatory_position_4: "",
    description_4: "",
    signatory_5: "",
    signatory_position_5: "",
    description_5: "",
  });

  // Dropdown data
  const branches = ref([]);
  const payrollPeriods = ref([]);
  const availableSignatories = ref([]);

  // Load initial data
  const loadInitialData = async () => {
    try {
      loading.value = true;
      error.value = null;

      const response = await loyaltyAwardApi.getLoyaltyAwardReportData();
      const data = response.data.data;

      branches.value = data.branches || [];
      payrollPeriods.value = data.payroll_period_types || [];

      // Load employee options for signatory dropdown (reuse ATM Letter endpoint)
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

      const response = await loyaltyAwardApi.getLoyaltyAwardSignatories(branchId);
      const data = response.data;

      if (data && data.length > 0) {
        const signatoryData = data[0];

        // Update signatories with fetched data
        signatories.signatory_1 = signatoryData.signatory_1 || "";
        signatories.signatory_position_1 =
          signatoryData.signatory_position_1 || "";
        signatories.description_1 = signatoryData.description_1 || "";

        signatories.signatory_2 = signatoryData.signatory_2 || "";
        signatories.signatory_position_2 =
          signatoryData.signatory_position_2 || "";
        signatories.description_2 = signatoryData.description_2 || "";

        signatories.signatory_3 = signatoryData.signatory_3 || "";
        signatories.signatory_position_3 =
          signatoryData.signatory_position_3 || "";
        signatories.description_3 = signatoryData.description_3 || "";

        signatories.signatory_4 = signatoryData.signatory_4 || "";
        signatories.signatory_position_4 =
          signatoryData.signatory_position_4 || "";
        signatories.description_4 = signatoryData.description_4 || "";

        signatories.signatory_5 = signatoryData.signatory_5 || "";
        signatories.signatory_position_5 =
          signatoryData.signatory_position_5 || "";
        signatories.description_5 = signatoryData.description_5 || "";
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

  // Generate report
  const generateReport = async () => {
    try {
      loading.value = true;
      error.value = null;

      // Validate required fields
      if (!formData.payroll_period_id) {
        throw new Error("Payroll Period is required");
      }

      // Prepare request data
      const requestData = {
        ...formData,
        ...signatories,
      };

      // Generate report
      const response =
        await loyaltyAwardApi.generateLoyaltyAwardReport(requestData);

      if (response.data.size === 0) {
        throw new Error("Generated PDF is empty - no loyalty award data found");
      }

      console.log("PDF blob size:", response.data.size);

      // Create download link
      const url = window.URL.createObjectURL(response.data);
      const link = document.createElement("a");
      link.href = url;
      link.download = `loyalty-award-report-${new Date().toISOString().split("T")[0]}.pdf`;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);

      ElMessage.success("Loyalty Award Report generated successfully");
      return response.data;
    } catch (err) {
      error.value =
        err.message ||
        err.response?.data?.message ||
        "Failed to generate report";
      ElMessage.error("Failed to generate report");
      throw err;
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
      if (!formData.payroll_period_id) {
        throw new Error("Payroll Period is required");
      }

      // Prepare request data
      const requestData = {
        ...formData,
        ...signatories,
      };

      // Generate report for preview
      const response =
        await loyaltyAwardApi.generateLoyaltyAwardReport(requestData);

      if (response.data.size === 0) {
        throw new Error("Generated PDF is empty - no loyalty award data found");
      }

      // Create blob URL and open in new tab
      const url = window.URL.createObjectURL(response.data);
      window.open(url, "_blank");

      // Clean up the URL after a delay
      setTimeout(() => {
        window.URL.revokeObjectURL(url);
      }, 1000);

      ElMessage.success("Loyalty Award Report preview opened");
      return response.data;
    } catch (err) {
      error.value =
        err.message ||
        err.response?.data?.message ||
        "Failed to preview report";
      ElMessage.error("Failed to preview report");
      throw err;
    } finally {
      loading.value = false;
    }
  };

  // Reset form
  const resetForm = () => {
    formData.payroll_period_id = "";
  };

  return {
    // State
    loading,
    error,
    formData,
    signatories,
    branches,
    payrollPeriods,
    availableSignatories,

    // Methods
    loadInitialData,
    loadSignatories,
    generateReport,
    previewReport,
    resetForm,
  };
}
