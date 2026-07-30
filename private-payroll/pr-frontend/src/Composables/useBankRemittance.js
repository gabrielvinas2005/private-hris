import { ref, reactive } from "vue";
import {
  divisionsFromApi,
  divisionParams,
  uniquePayPeriods,
} from "../utils/payrollReportDivisions.js";
import { bankRemittanceApi } from "../services/api.js";
import { ElMessage } from "element-plus";

export function useBankRemittance() {
  const loading = ref(false);
  const error = ref(null);

  // Form data
  const formData = reactive({
    division_id: "",
    payroll_period_id: "",
    deduction: "",
  });

  // Signatories data
  const signatories = reactive({
    certified_correct: "",
    position: "",
    date: new Date().toISOString().split("T")[0], // Today's date in YYYY-MM-DD format
  });

  // Dropdown data
  const divisions = ref([]);
  const payrollPeriods = ref([]);
  const bankLoanTypes = ref([]);

  // Load initial data
  const loadInitialData = async () => {
    try {
      loading.value = true;
      error.value = null;

      const response = await bankRemittanceApi.getBankRemittanceData();
      const data = response.data.data;

      divisions.value = divisionsFromApi(data) || [];
      payrollPeriods.value = uniquePayPeriods(data.pay_periods);
      bankLoanTypes.value = data.deductions || [];

      // Set default signatory if provided
      if (data.signatory) {
        signatories.certified_correct =
          data.signatory.signatory || signatories.certified_correct;
        signatories.position = data.signatory.position || signatories.position;
        signatories.date = data.signatory.date || signatories.date;
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

  // Preview report (opens in new tab)
  const previewReport = async () => {
    try {
      loading.value = true;
      error.value = null;

      // Validate required fields
      if (!formData.division_id) {
        throw new Error("Department is required");
      }
      if (!formData.payroll_period_id) {
        throw new Error("Payroll Period is required");
      }
      if (!formData.deduction) {
        throw new Error("Bank Loan Type is required");
      }
      // Add validation for signatory fields
      if (
        !signatories.certified_correct ||
        (typeof signatories.certified_correct === "string" &&
          signatories.certified_correct.trim() === "")
      ) {
        throw new Error("Certified Correct is required");
      }
      if (
        !signatories.position ||
        (typeof signatories.position === "string" &&
          signatories.position.trim() === "")
      ) {
        throw new Error("Position/Designation is required");
      }
      if (!signatories.date) {
        throw new Error("Date is required");
      }

      // Prepare request data
      const requestData = {
        department: formData.division_id,
        payroll_period: formData.payroll_period_id,
        deduction: formData.deduction,
        signatory: signatories.certified_correct,
        position: signatories.position,
        date: signatories.date,
      };

      // Generate report
      const response =
        await bankRemittanceApi.generateBankRemittanceReport(requestData);

      if (response.data.size === 0) {
        throw new Error(
          "Generated PDF is empty - no bank remittance data found"
        );
      }

      // Create URL and open in new tab
      const url = window.URL.createObjectURL(response.data);
      window.open(url, "_blank");

      // Clean up URL after a delay
      setTimeout(() => {
        window.URL.revokeObjectURL(url);
      }, 1000);

      ElMessage.success("Bank Remittance Report preview opened");
      return response.data;
    } catch (err) {
      // Improved error handling for different status codes
      if (err.response) {
        const status = err.response.status;
        const contentType = err.response.headers?.["content-type"] || "";

        // Handle JSON error responses (validation, not found, etc.)
        if (contentType.includes("application/json")) {
          const errorData = err.response.data;

          if (status === 404) {
            error.value =
              errorData?.message ||
              "Route not found. Please check if the API endpoint is correct.";
            ElMessage.error(error.value);
          } else if (status === 400) {
            error.value =
              errorData?.message ||
              "No bank remittance data found for the specified criteria.";
            ElMessage.error(error.value);
          } else if (status === 422) {
            const validationErrors = errorData?.errors;
            if (validationErrors) {
              const errorMessages = Object.values(validationErrors).flat();
              error.value = errorMessages.join(", ") || "Validation failed";
              ElMessage.error(error.value);
            } else {
              error.value = errorData?.message || "Validation failed";
              ElMessage.error(error.value);
            }
          } else if (status === 401) {
            error.value = "Authentication required. Please log in again.";
            ElMessage.error(error.value);
          } else {
            error.value =
              errorData?.message || err.message || "Failed to preview report";
            ElMessage.error(error.value);
          }
        } else {
          // Handle blob/other response types
          error.value =
            err.response?.data?.message ||
            err.message ||
            "Failed to preview report";
          ElMessage.error(error.value);
        }
      } else if (err.request) {
        // Request was made but no response received
        error.value =
          "No response from server. Please check your network connection.";
        ElMessage.error(error.value);
      } else {
        // Something else happened
        error.value = err.message || "Failed to preview report";
        ElMessage.error(error.value);
      }
      throw err;
    } finally {
      loading.value = false;
    }
  };

  // Generate report (download)
  const generateReport = async () => {
    try {
      loading.value = true;
      error.value = null;

      // Validate required fields
      if (!formData.division_id) {
        throw new Error("Department is required");
      }
      if (!formData.payroll_period_id) {
        throw new Error("Payroll Period is required");
      }
      if (!formData.deduction) {
        throw new Error("Bank Loan Type is required");
      }
      // Add validation for signatory fields
      if (
        !signatories.certified_correct ||
        (typeof signatories.certified_correct === "string" &&
          signatories.certified_correct.trim() === "")
      ) {
        throw new Error("Certified Correct is required");
      }
      if (
        !signatories.position ||
        (typeof signatories.position === "string" &&
          signatories.position.trim() === "")
      ) {
        throw new Error("Position/Designation is required");
      }
      if (!signatories.date) {
        throw new Error("Date is required");
      }

      // Prepare request data
      const requestData = {
        department: formData.division_id,
        payroll_period: formData.payroll_period_id,
        deduction: formData.deduction,
        signatory: signatories.certified_correct,
        position: signatories.position,
        date: signatories.date,
      };

      // Generate report
      const response =
        await bankRemittanceApi.generateBankRemittanceReport(requestData);

      if (response.data.size === 0) {
        throw new Error(
          "Generated PDF is empty - no bank remittance data found"
        );
      }

      // Create download link
      const url = window.URL.createObjectURL(response.data);
      const link = document.createElement("a");
      link.href = url;
      link.download = `bank-remittance-report-${new Date().toISOString().split("T")[0]}.pdf`;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);

      ElMessage.success("Bank Remittance Report downloaded successfully");
      return response.data;
    } catch (err) {
      // Improved error handling for different status codes
      if (err.response) {
        const status = err.response.status;
        const contentType = err.response.headers?.["content-type"] || "";

        // Handle JSON error responses (validation, not found, etc.)
        if (contentType.includes("application/json")) {
          const errorData = err.response.data;

          if (status === 404) {
            error.value =
              errorData?.message ||
              "Route not found. Please check if the API endpoint is correct.";
            ElMessage.error(error.value);
          } else if (status === 400) {
            error.value =
              errorData?.message ||
              "No bank remittance data found for the specified criteria.";
            ElMessage.error(error.value);
          } else if (status === 422) {
            const validationErrors = errorData?.errors;
            if (validationErrors) {
              const errorMessages = Object.values(validationErrors).flat();
              error.value = errorMessages.join(", ") || "Validation failed";
              ElMessage.error(error.value);
            } else {
              error.value = errorData?.message || "Validation failed";
              ElMessage.error(error.value);
            }
          } else if (status === 401) {
            error.value = "Authentication required. Please log in again.";
            ElMessage.error(error.value);
          } else {
            error.value =
              errorData?.message || err.message || "Failed to generate report";
            ElMessage.error(error.value);
          }
        } else {
          // Handle blob/other response types
          error.value =
            err.response?.data?.message ||
            err.message ||
            "Failed to generate report";
          ElMessage.error(error.value);
        }
      } else if (err.request) {
        // Request was made but no response received
        error.value =
          "No response from server. Please check your network connection.";
        ElMessage.error(error.value);
      } else {
        // Something else happened
        error.value = err.message || "Failed to generate report";
        ElMessage.error(error.value);
      }
      throw err;
    } finally {
      loading.value = false;
    }
  };

  // Reset form
  const resetForm = () => {
    formData.division_id = "";
    formData.payroll_period_id = "";
    formData.deduction = "";
    signatories.certified_correct = "";
    signatories.position = "";
    signatories.date = new Date().toISOString().split("T")[0];
  };

  return {
    // State
    loading,
    error,
    formData,
    signatories,
    divisions,
    payrollPeriods,
    bankLoanTypes,

    // Methods
    loadInitialData,
    previewReport,
    generateReport,
    resetForm,
  };
}
