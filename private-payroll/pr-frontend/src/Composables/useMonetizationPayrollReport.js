import { ref, reactive } from "vue";
import { monetizationPayrollApi, signatoryApi } from "../services/api.js";
import { ElMessage } from "element-plus";

// Composable dedicated to Monetization Payroll Report
export function useMonetizationPayrollReport() {
  const loading = ref(false);
  const error = ref(null);

  // Form data used by ReportParameters.vue
  const formData = reactive({
    payroll_period_id: "",
    branch_id: "",
  });

  // Signatories used by SignatoriesSection.vue
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
    description_1: "",
    description_2: "",
    description_3: "",
    description_4: "",
    description_5: "",
  });

  // Dropdowns
  const payrollPeriods = ref([]);
  const branches = ref([]);
  const availableSignatories = ref([]);

  // Load initial data for Monetization Payroll Report
  const loadInitialData = async () => {
    try {
      loading.value = true;
      error.value = null;

      const response =
        await monetizationPayrollApi.getMonetizationPayrollReportData();
      const data = response.data?.data || {};

      payrollPeriods.value = Array.isArray(data.monetization_payroll)
        ? data.monetization_payroll
        : [];

      // Load branches if provided (kept for compatibility)
      if (Array.isArray(data.branches)) {
        branches.value = data.branches;
      }

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
      ElMessage.error(error.value);
      throw err;
    } finally {
      loading.value = false;
    }
  };

  // Load signatories by branch
  const loadSignatories = async (branchId) => {
    try {
      if (!branchId) return;

      loading.value = true;
      error.value = null;

      // Load signatories for the specific branch
      const response =
        await monetizationPayrollApi.getMonetizationPayrollSignatories(
          branchId
        );
      const data = response.data?.data || {};

      if (data.signatory_1) {
        signatories.signatory_1 = data.signatory_1;
        signatories.signatory_position_1 = data.signatory_position_1 || "";
        signatories.description_1 = data.description_1 || "";
      }
      if (data.signatory_2) {
        signatories.signatory_2 = data.signatory_2;
        signatories.signatory_position_2 = data.signatory_position_2 || "";
        signatories.description_2 = data.description_2 || "";
      }
      if (data.signatory_3) {
        signatories.signatory_3 = data.signatory_3;
        signatories.signatory_position_3 = data.signatory_position_3 || "";
        signatories.description_3 = data.description_3 || "";
      }
      if (data.signatory_4) {
        signatories.signatory_4 = data.signatory_4;
        signatories.signatory_position_4 = data.signatory_position_4 || "";
        signatories.description_4 = data.description_4 || "";
      }
      if (data.signatory_5) {
        signatories.signatory_5 = data.signatory_5;
        signatories.signatory_position_5 = data.signatory_position_5 || "";
        signatories.description_5 = data.description_5 || "";
      }

      return data;
    } catch (err) {
      error.value = err.response?.data?.message || "Failed to load signatories";
      ElMessage.error(error.value);
      throw err;
    } finally {
      loading.value = false;
    }
  };

  // Build print parameters for the report
  const buildPrintParams = () => ({
    payroll_id: formData.payroll_period_id,
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

  // Generate PDF report
  const generateReport = async () => {
    try {
      loading.value = true;
      error.value = null;

      // Validate required fields
      if (!formData.payroll_period_id) {
        throw new Error("Please select a Monetization Payroll Period");
      }

      const printParams = buildPrintParams();

      const response =
        await monetizationPayrollApi.generateMonetizationPayrollReport(
          printParams
        );

      // Create blob and download
      const blob = new Blob([response.data], { type: "application/pdf" });
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement("a");
      link.href = url;

      // Extract filename from response headers or use default
      const contentDisposition = response.headers["content-disposition"];
      let filename = "monetization_payroll_report.pdf";
      if (contentDisposition) {
        const filenameMatch = contentDisposition.match(/filename="(.+)"/);
        if (filenameMatch) {
          filename = filenameMatch[1];
        }
      }

      link.download = filename;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);

      ElMessage.success("Monetization Payroll Report generated successfully!");
    } catch (err) {
      error.value =
        err.response?.data?.message ||
        err.message ||
        "Failed to generate report";
      ElMessage.error(error.value);
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
        throw new Error("Please select a Monetization Payroll Period");
      }

      const printParams = buildPrintParams();

      // For preview, we'll generate the report and open in new tab
      const response =
        await monetizationPayrollApi.generateMonetizationPayrollReport(
          printParams
        );

      // Create blob and open in new tab
      const blob = new Blob([response.data], { type: "application/pdf" });
      const url = window.URL.createObjectURL(blob);

      // Open in new tab
      window.open(url, "_blank");

      // Clean up URL after a delay
      setTimeout(() => {
        window.URL.revokeObjectURL(url);
      }, 1000);

      ElMessage.success("Monetization Payroll Report preview opened!");
    } catch (err) {
      error.value =
        err.response?.data?.message ||
        err.message ||
        "Failed to preview report";
      ElMessage.error(error.value);
      throw err;
    } finally {
      loading.value = false;
    }
  };

  // Reset form data
  const resetForm = () => {
    formData.payroll_period_id = "";
    formData.branch_id = "";
  };

  return {
    // State
    loading,
    error,
    formData,
    signatories,
    payrollPeriods,
    branches,
    availableSignatories,

    // Methods
    loadInitialData,
    loadSignatories,
    generateReport,
    previewReport,
    resetForm,
  };
}
