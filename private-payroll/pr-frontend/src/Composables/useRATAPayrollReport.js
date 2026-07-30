import { ref, reactive } from "vue";
import { rataPayrollApi, signatoryApi } from "../services/api.js";
import { ElMessage } from "element-plus";

// Composable dedicated to RATA Payroll Report
export function useRATAPayrollReport() {
  const loading = ref(false);
  const error = ref(null);

  // Form data used by ReportParameters.vue
  const formData = reactive({
    rata_payroll_id: "",
    branch_id: "",
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
  const rataPayrolls = ref([]);
  const branches = ref([]);
  const availableSignatories = ref([]);

  // Load initial data for RATA Payroll Report
  const loadInitialData = async () => {
    try {
      loading.value = true;
      error.value = null;

      const response = await rataPayrollApi.getRATAReportData();
      const data = response.data?.data || {};

      rataPayrolls.value = Array.isArray(data.rata_payroll)
        ? data.rata_payroll
        : [];

      // Load signatories if available
      if (data.signatories) {
        branches.value = data.signatories.map((s) => ({
          id: s.id,
          name: s.name,
        }));
      }

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
      ElMessage.error(error.value);
      throw err;
    } finally {
      loading.value = false;
    }
  };

  // Load signatories (placeholder - RATA doesn't use branch-based signatories)
  const loadSignatories = async () => {
    // RATA signatories are static, no need to load from backend
    return true;
  };

  // Build print parameters for the report
  const buildPrintParams = () => ({
    rata_payroll_id: formData.rata_payroll_id,
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
      if (!formData.rata_payroll_id) {
        throw new Error("Please select a RATA Payroll");
      }

      const printParams = buildPrintParams();

      const response =
        await rataPayrollApi.generateRATAPayrollReport(printParams);

      // Create blob and download
      const blob = new Blob([response.data], { type: "application/pdf" });
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement("a");
      link.href = url;

      // Extract filename from response headers or use default
      const contentDisposition = response.headers["content-disposition"];
      let filename = "rata_payroll_report.pdf";
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

      ElMessage.success("RATA Payroll Report generated successfully!");
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
      if (!formData.rata_payroll_id) {
        throw new Error("Please select a RATA Payroll");
      }

      const printParams = buildPrintParams();

      // For preview, we'll generate the report and open in new tab
      const response =
        await rataPayrollApi.generateRATAPayrollReport(printParams);

      // Create blob and open in new tab
      const blob = new Blob([response.data], { type: "application/pdf" });
      const url = window.URL.createObjectURL(blob);

      // Open in new tab
      window.open(url, "_blank");

      // Clean up URL after a delay
      setTimeout(() => {
        window.URL.revokeObjectURL(url);
      }, 1000);

      ElMessage.success("RATA Payroll Report preview opened!");
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
    formData.rata_payroll_id = "";
    formData.branch_id = "";
  };

  return {
    // State
    loading,
    error,
    formData,
    signatories,
    rataPayrolls,
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
