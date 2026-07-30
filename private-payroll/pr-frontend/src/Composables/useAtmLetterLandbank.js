import { ref, reactive } from "vue";
import {
  divisionsFromApi,
  divisionParams,
  uniquePayPeriods,
} from "../utils/payrollReportDivisions.js";
import { formatApiError } from "../utils/apiErrorMessage.js";
import { ElMessage } from "element-plus";
import api from "../services/api";

export function useAtmLetterLandbank() {
  const loading = ref(false);
  const error = ref(null);

  // Form data
  const formData = reactive({
    division_id: "",
    payroll_period_id: "",
  });

  // Signatories data
  const signatories = reactive({
    signatory_name: "",
    signatory_position: "",
    personnel_name: "",
    personnel_position: "",
  });

  // Default signatory placeholders (for display only, not actual values)
  const defaultSignatoryPlaceholders = ref({
    signatory_name: "",
    signatory_position: "",
    personnel_name: "",
    personnel_position: "",
  });

  // Data arrays
  const divisions = ref([]);
  const payrollPeriods = ref([]);
  const employeeOptions = ref([]);

  // Print preview state management
  const uiState = reactive({
    showPrintModal: false,
    previewUrl: "",
    selectedDepartment: null,
    selectedPayroll: null,
  });

  /**
   * Load initial data
   */
  const loadInitialData = async () => {
    try {
      loading.value = true;
      error.value = null;

      const response = await api.get("/atm-letter-landbank");

      if (response.data.success) {
        divisions.value = divisionsFromApi(response.data.data);
        payrollPeriods.value = uniquePayPeriods(response.data.data.payrolls);
        employeeOptions.value = response.data.data.employee_options || [];

        // Store default signatory values as placeholders only (not actual values)
        if (response.data.data.default_signatory) {
          Object.assign(
            defaultSignatoryPlaceholders.value,
            response.data.data.default_signatory
          );

          // Pre-fill personnel information
          signatories.personnel_name =
            response.data.data.default_signatory.personnel_name || "";
          signatories.personnel_position =
            response.data.data.default_signatory.personnel_position || "";
        }
      } else {
        throw new Error(response.data.message || "Failed to load data");
      }
    } catch (err) {
      error.value =
        err.response?.data?.message || err.message || "Failed to load data";
      ElMessage.error(error.value);
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const buildRequestPayload = () => ({
    payroll_period_id: formData.payroll_period_id,
    ...divisionParams(formData),
    signatory_name: signatories.signatory_name,
    signatory_position: signatories.signatory_position,
    personnel_name: signatories.personnel_name,
    personnel_position: signatories.personnel_position,
  });

  /**
   * Preview report (shows inline preview)
   */
  const previewReport = async () => {
    if (!formData.division_id || !formData.payroll_period_id) {
      ElMessage.warning("Please select Division and Payroll Period");
      return;
    }

    if (!signatories.signatory_name || !signatories.personnel_name) {
      ElMessage.warning("Please fill in all signatory information");
      return;
    }

    try {
      loading.value = true;
      error.value = null;

      // Set selected department and payroll for display
      const selectedDept = divisions.value.find(
        (d) => d.id === formData.division_id
      );
      const selectedPay = payrollPeriods.value.find(
        (p) => p.id === formData.payroll_period_id
      );
      uiState.selectedDepartment = selectedDept;
      uiState.selectedPayroll = selectedPay;
      uiState.showPrintModal = true;

      // Fetch PDF and create blob URL
      const response = await api.post("/atm-letter-landbank/pdf", buildRequestPayload(), {
        responseType: "blob",
      });

      const blob = new Blob([response.data], { type: "application/pdf" });

      if (!blob || blob.size === 0) {
        throw new Error("Generated PDF is empty");
      }

      // Create blob URL for preview
      const url = window.URL.createObjectURL(blob);
      uiState.previewUrl = url;

      ElMessage.success("ATM Letter preview loaded");
    } catch (err) {
      error.value = formatApiError(err, "Failed to preview ATM Letter");
      ElMessage.error(error.value);
      // Close modal on error
      uiState.showPrintModal = false;
      throw err;
    } finally {
      loading.value = false;
    }
  };

  /**
   * Generate and download PDF report
   */
  const generatePdfReport = async () => {
    if (!formData.division_id || !formData.payroll_period_id) {
      ElMessage.warning("Please select Division and Payroll Period");
      return;
    }

    if (!signatories.signatory_name || !signatories.personnel_name) {
      ElMessage.warning("Please fill in all signatory information");
      return;
    }

    try {
      loading.value = true;
      error.value = null;

      const response = await api.post("/atm-letter-landbank/pdf", buildRequestPayload(), {
        responseType: "blob",
      });

      // Create download link for PDF
      const blob = new Blob([response.data], { type: "application/pdf" });
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement("a");
      link.href = url;
      link.download = `atm_letter_landbank_${formData.division_id}_${
        formData.payroll_period_id
      }_${new Date().toISOString().split("T")[0]}.pdf`;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);

      ElMessage.success("ATM Letter PDF downloaded successfully");
    } catch (err) {
      error.value = formatApiError(err, "Failed to generate PDF");
      ElMessage.error(error.value);
    } finally {
      loading.value = false;
    }
  };

  /**
   * Generate and download Word report
   */
  const generateWordReport = async () => {
    if (!formData.division_id || !formData.payroll_period_id) {
      ElMessage.warning("Please select Division and Payroll Period");
      return;
    }

    if (!signatories.signatory_name || !signatories.personnel_name) {
      ElMessage.warning("Please fill in all signatory information");
      return;
    }

    try {
      loading.value = true;
      error.value = null;

      const response = await api.post(
        "/atm-letter-landbank/word",
        buildRequestPayload(),
        {
          responseType: "blob",
        }
      );

      // Create download link for Word document
      const blob = new Blob([response.data], {
        type: "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
      });
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement("a");
      link.href = url;
      link.download = `atm_letter_landbank_${formData.division_id}_${
        formData.payroll_period_id
      }_${new Date().toISOString().split("T")[0]}.docx`;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);

      ElMessage.success("ATM Letter Word document downloaded successfully");
    } catch (err) {
      error.value = formatApiError(err, "Failed to generate Word document");
      ElMessage.error(error.value);
    } finally {
      loading.value = false;
    }
  };

  /**
   * Download PDF (uses existing preview blob URL)
   */
  const downloadPdf = () => {
    if (!uiState.previewUrl) {
      ElMessage.warning("No preview available to download");
      return;
    }
    const a = document.createElement("a");
    a.href = uiState.previewUrl;
    const deptName =
      uiState.selectedDepartment?.name ||
      `dept-${formData.division_id || "unknown"}`;
    const payrollName =
      uiState.selectedPayroll?.name ||
      `payroll-${formData.payroll_period_id || "unknown"}`;
    const sanitize = (s) => String(s).replace(/[^A-Za-z0-9_\-()]+/g, "");
    a.download = `atm_letter_landbank_${sanitize(deptName)}_${sanitize(payrollName)}_${
      new Date().toISOString().split("T")[0]
    }.pdf`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    ElMessage.success("PDF downloaded successfully");
  };

  /**
   * Download Word document (from preview)
   */
  const downloadWord = async () => {
    if (!formData.division_id || !formData.payroll_period_id) {
      ElMessage.warning("Please select Division and Payroll Period");
      return;
    }

    if (!signatories.signatory_name || !signatories.personnel_name) {
      ElMessage.warning("Please fill in all signatory information");
      return;
    }

    try {
      loading.value = true;
      const response = await api.post(
        "/atm-letter-landbank/word",
        buildRequestPayload(),
        {
          responseType: "blob",
        }
      );

      const blob = new Blob([response.data], {
        type: "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
      });

      if (!blob || blob.size === 0) {
        throw new Error("Generated document is empty");
      }

      const url = window.URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      const deptName =
        uiState.selectedDepartment?.name ||
        `dept-${formData.division_id || "unknown"}`;
      const payrollName =
        uiState.selectedPayroll?.name ||
        `payroll-${formData.payroll_period_id || "unknown"}`;
      const sanitize = (s) => String(s).replace(/[^A-Za-z0-9_\-()]+/g, "");
      a.download = `atm_letter_landbank_${sanitize(deptName)}_${sanitize(payrollName)}_${
        new Date().toISOString().split("T")[0]
      }.docx`;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      window.URL.revokeObjectURL(url);
      ElMessage.success("Word document downloaded successfully");
    } catch (err) {
      error.value = formatApiError(err, "Failed to download Word document");
      ElMessage.error(error.value);
    } finally {
      loading.value = false;
    }
  };

  /**
   * Close preview and cleanup
   */
  const closePreview = () => {
    uiState.showPrintModal = false;
    if (uiState.previewUrl) {
      window.URL.revokeObjectURL(uiState.previewUrl);
      uiState.previewUrl = "";
    }
    uiState.selectedDepartment = null;
    uiState.selectedPayroll = null;
  };

  return {
    loading,
    error,
    formData,
    signatories,
    defaultSignatoryPlaceholders,
    divisions,
    payrollPeriods,
    employeeOptions,
    loadInitialData,
    previewReport,
    generatePdfReport,
    generateWordReport,
    uiState,
    downloadPdf,
    downloadWord,
    closePreview,
  };
}
