import { ref, reactive } from "vue";
import { divisionsFromApi, divisionParams } from "../utils/payrollReportDivisions.js";
import { formatApiError } from "../utils/apiErrorMessage.js";
import { ElMessage } from "element-plus";
import api from "../services/api";

const buildAtmLetterRequest = (formData, signatories) => ({
  ...divisionParams(formData),
  years: formData.years,
  signatory_name: signatories.signatory_name,
  signatory_position: signatories.signatory_position,
  personnel_name: signatories.personnel_name,
  personnel_position: signatories.personnel_position,
});

export function useATMMidYearBonus() {
  const loading = ref(false);
  const error = ref(null);

  // Form data
  const formData = reactive({
    division_id: "",
    years: "",
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
  const years = ref([]);
  const employeeOptions = ref([]);

  /**
   * Load initial data
   */
  const loadInitialData = async () => {
    try {
      loading.value = true;
      error.value = null;

      const response = await api.get("/atm-letter-midyear");

      if (response.data.success) {
        divisions.value = divisionsFromApi(response.data.data) || [];
        years.value = response.data.data.years || [];
        employeeOptions.value = response.data.data.employee_options || [];

        if (divisions.value.length === 0) {
          ElMessage.warning(
            "No mid-year bonus records found. Generate mid-year bonus under Payroll Benefits first.",
          );
        }

        // Store default signatory values as placeholders only (not actual values)
        if (response.data.data.default_signatory) {
          Object.assign(
            defaultSignatoryPlaceholders.value,
            response.data.data.default_signatory
          );
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

  /**
   * Preview report (opens PDF in new tab)
   */
  const previewReport = async () => {
    if (!formData.division_id || !formData.years) {
      ElMessage.warning("Please select Division and Year");
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
        "/atm-letter-midyear/pdf",
        buildAtmLetterRequest(formData, signatories),
        {
          responseType: "blob",
        }
      );

      const blob = new Blob([response.data], { type: "application/pdf" });
      const url = window.URL.createObjectURL(blob);

      window.open(url, "_blank");

      setTimeout(() => {
        window.URL.revokeObjectURL(url);
      }, 1000);

      ElMessage.success("ATM Letter preview opened");
    } catch (err) {
      const message = formatApiError(err, "Failed to preview ATM Letter");
      error.value = message;
      ElMessage.error(message);
    } finally {
      loading.value = false;
    }
  };

  /**
   * Generate and download PDF report
   */
  const generatePdfReport = async () => {
    if (!formData.division_id || !formData.years) {
      ElMessage.warning("Please select Division and Year");
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
        "/atm-letter-midyear/pdf",
        buildAtmLetterRequest(formData, signatories),
        {
          responseType: "blob",
        }
      );

      // Create download link for PDF
      const blob = new Blob([response.data], { type: "application/pdf" });
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement("a");
      link.href = url;
      link.download = `atm_letter_midyear_${formData.division_id}_${
        formData.years
      }_${new Date().toISOString().split("T")[0]}.pdf`;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);

      ElMessage.success("ATM Letter PDF downloaded successfully");
    } catch (err) {
      const message = formatApiError(err, "Failed to generate PDF");
      error.value = message;
      ElMessage.error(message);
    } finally {
      loading.value = false;
    }
  };

  return {
    loading,
    error,
    formData,
    signatories,
    defaultSignatoryPlaceholders,
    divisions,
    years,
    employeeOptions,
    loadInitialData,
    previewReport,
    generatePdfReport,
  };
}
