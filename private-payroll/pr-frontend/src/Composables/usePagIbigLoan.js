import { ref, reactive } from "vue";
import { pagIbigLoanApi } from "../services/api.js";
import { ElMessage } from "element-plus";
import { uniquePayPeriods } from "../utils/payrollReportDivisions.js";
import { formatApiError } from "../utils/apiErrorMessage.js";

const formatReportDate = (value) => {
  if (!value) return "";
  if (value instanceof Date) {
    return value.toISOString().split("T")[0];
  }
  return String(value).slice(0, 10);
};

const isBlank = (value) =>
  value === null ||
  value === undefined ||
  String(value).trim() === "";

export function usePagIbigLoan() {
  const loading = ref(false);
  const error = ref(null);

  const formData = reactive({
    payroll_period_id: "",
    membership_program: "", // deduction id for selected loan type
  });

  const signatories = reactive({
    signatory: "",
    accsignatory: "",
    position: "",
    date: new Date().toISOString().split("T")[0],
  });

  const payrollPeriods = ref([]);
  const membershipPrograms = ref([]);

  const validateBeforePrint = () => {
    if (!formData.payroll_period_id) {
      throw new Error("Payroll Period is required.");
    }
    if (!formData.membership_program) {
      throw new Error("Loan Type is required.");
    }
    if (isBlank(signatories.signatory)) {
      throw new Error("Authorized Representative is required.");
    }
    if (isBlank(signatories.accsignatory)) {
      throw new Error("OIC-Municipal Accountant is required.");
    }
    if (isBlank(signatories.position)) {
      throw new Error("Position/Designation is required.");
    }
    if (!signatories.date) {
      throw new Error("Date is required.");
    }
  };

  const buildPrintPayload = () => ({
    payroll_period: formData.payroll_period_id,
    membership_program: formData.membership_program,
    signatory: String(signatories.signatory).trim(),
    accsignatory: String(signatories.accsignatory).trim(),
    position: String(signatories.position).trim(),
    date: formatReportDate(signatories.date),
  });

  const handleReportError = (err, fallback) => {
    const msg = formatApiError(err, fallback);
    error.value = msg;
    ElMessage.error(msg);
    throw err;
  };

  const loadInitialData = async () => {
    try {
      loading.value = true;
      error.value = null;

      const res = await pagIbigLoanApi.getPagIbigLoanFormData();
      const data = res.data?.data || {};

      payrollPeriods.value = uniquePayPeriods(data.pay_periods);
      membershipPrograms.value = data.membership_programs || [];

      return data;
    } catch (err) {
      handleReportError(err, "Failed to load Pag-IBIG loan report data.");
    } finally {
      loading.value = false;
    }
  };

  const previewReport = async () => {
    try {
      loading.value = true;
      error.value = null;

      validateBeforePrint();
      const payload = buildPrintPayload();

      const response =
        await pagIbigLoanApi.generatePagIbigLoanDeductionReport(payload);
      const blob = response.data;
      if (!blob || blob.size === 0) {
        throw new Error(
          "The report file is empty. No loan deductions were found for the selected period and loan type.",
        );
      }

      const url = window.URL.createObjectURL(blob);
      window.open(url, "_blank");
      ElMessage.success("Preview opened in a new tab");
    } catch (err) {
      if (!error.value) {
        handleReportError(err, "Failed to preview the Pag-IBIG loan report.");
      }
    } finally {
      loading.value = false;
    }
  };

  const generateReport = async () => {
    try {
      loading.value = true;
      error.value = null;

      validateBeforePrint();
      const payload = buildPrintPayload();

      const response =
        await pagIbigLoanApi.generatePagIbigLoanDeductionReport(payload);

      const blob = response.data;
      if (!blob || blob.size === 0) {
        throw new Error(
          "The report file is empty. No loan deductions were found for the selected period and loan type.",
        );
      }

      const url = window.URL.createObjectURL(blob);
      const link = document.createElement("a");
      link.href = url;

      const periodObj = payrollPeriods.value.find(
        (p) => p.id === formData.payroll_period_id,
      );
      const periodName =
        periodObj?.name || `period-${formData.payroll_period_id || "unknown"}`;
      const loanObj = membershipPrograms.value.find(
        (m) => m.id === formData.membership_program,
      );
      const loanName = loanObj?.name || "loan";
      const sanitize = (s) => String(s).replace(/[^A-Za-z0-9_\-()]+/g, "");
      const currentDate = new Date().toISOString().split("T")[0];
      link.download = `pagibig-loan-deduction-${sanitize(loanName)}-${sanitize(periodName)}-${currentDate}.pdf`;

      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);
      ElMessage.success(
        "Pag-IBIG Loan Deduction Report downloaded successfully",
      );
    } catch (err) {
      if (!error.value) {
        handleReportError(err, "Failed to generate the Pag-IBIG loan report.");
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
    payrollPeriods,
    membershipPrograms,
    loadInitialData,
    previewReport,
    generateReport,
  };
}
