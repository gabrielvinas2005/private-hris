import { ref, reactive, watch } from "vue";
import { divisionsFromApi, divisionParams } from "../utils/payrollReportDivisions.js";
import {
  overtimePayrollApi,
  payrollProcessApi,
  signatoryApi,
} from "../services/api.js";
import { ElMessage } from "element-plus";

// Composable dedicated to Overtime Payment report
export function useOvertimePayment() {
  const loading = ref(false);
  const error = ref(null);

  // Form data used by ReportParameters.vue
  const formData = reactive({
    payroll_interval_id: "",
    payroll_period_id: "",
    branch_id: "",
    division_id: "",
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

  // Employee options for signatory dropdowns
  const availableSignatories = ref([]);

  // Local storage key for persisting signatories
  const SIGNATORIES_STORAGE_KEY = "overtime_payment_signatories_v1";

  const hydrateSignatoriesFromStorage = () => {
    if (typeof window === "undefined") return;
    try {
      const raw = window.localStorage.getItem(SIGNATORIES_STORAGE_KEY);
      if (!raw) return;
      const saved = JSON.parse(raw);
      if (saved && typeof saved === "object") {
        Object.assign(signatories, saved);
      }
    } catch {
      // Ignore malformed local storage entries
    }
  };

  // Initial hydration
  hydrateSignatoriesFromStorage();

  // Dropdowns
  const payrollIntervals = ref([]);
  const payrollPeriods = ref([]);
  const branches = ref([]);
  const divisions = ref([]);

  // Load intervals and initially available periods for OT processing
  const loadInitialData = async () => {
    try {
      loading.value = true;
      error.value = null;

      // Load OT-specific process data (intervals + posted periods)
      const response = await overtimePayrollApi.getOvertimePayrollProcessData();
      const data = response.data?.data || {};

      payrollIntervals.value = data.payroll_intervals || [];
      payrollPeriods.value = data.periods || [];

      // Also load global branches and departments used across reports
      try {
        const globals = await payrollProcessApi.getPayrollSummaryData();
        const gdata = globals.data?.data || {};
        branches.value = gdata.branches || [];
        divisions.value = divisionsFromApi(gdata);
      } catch (innerErr) {
        // Keep page usable even if globals fail
        branches.value = [];
        divisions.value = [];
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

  // Load posted OT periods and filter by selected interval
  const loadPayrollPeriods = async (intervalId) => {
    try {
      loading.value = true;
      error.value = null;

      const res = await overtimePayrollApi.getOvertimePayrollProcessData();
      const all = res.data?.data?.periods || [];
      payrollPeriods.value = intervalId
        ? all.filter(
            (p) => String(p.payroll_interval_id) === String(intervalId)
          )
        : all;

      // Reset selected period if no longer valid
      const stillValid = payrollPeriods.value.some(
        (p) => String(p.id) === String(formData.payroll_period_id)
      );
      if (!stillValid) formData.payroll_period_id = "";
    } catch (err) {
      error.value =
        err.response?.data?.message || "Failed to load payroll periods";
      ElMessage.error(error.value);
      payrollPeriods.value = [];
      throw err;
    } finally {
      loading.value = false;
    }
  };

  // Persist signatories locally so the form is pre-filled next time
  if (typeof window !== "undefined") {
    watch(
      () => ({ ...signatories }),
      (value) => {
        try {
          window.localStorage.setItem(
            SIGNATORIES_STORAGE_KEY,
            JSON.stringify(value)
          );
        } catch {
          // Ignore quota / storage errors
        }
      },
      { deep: true }
    );
  }

  // Map UI signatories (underscore) to controller expected parameters (no underscore)
  const buildPrintParams = () => ({
    payroll_period_id: formData.payroll_period_id,
    // Controller expects signatory1..signatory5
    signatory1: signatories.signatory_1,
    signatory2: signatories.signatory_2,
    signatory3: signatories.signatory_3,
    signatory4: signatories.signatory_4,
    signatory5: signatories.signatory_5,
    // positions (kept for future use if backend adds them)
    signatory_position_1: signatories.signatory_position_1,
    signatory_position_2: signatories.signatory_position_2,
    signatory_position_3: signatories.signatory_position_3,
    signatory_position_4: signatories.signatory_position_4,
    signatory_position_5: signatories.signatory_position_5,
    // optional filters (not used by backend currently)
    branch_id: formData.branch_id,
    division_id:
      String(formData.division_id || "").toLowerCase() === "all"
        ? ""
        : formData.division_id,
  });

  // Preview report (open PDF in new tab)
  const previewReport = async () => {
    try {
      loading.value = true;
      error.value = null;

      if (!formData.payroll_period_id) {
        throw new Error("Payroll Period is required");
      }

      const response =
        await overtimePayrollApi.generateOvertimePayrollReport(
          buildPrintParams()
        );

      const blob = response.data;
      if (!blob || blob.size === 0) {
        throw new Error("Generated PDF is empty - no overtime data found");
      }

      const url = window.URL.createObjectURL(blob);
      window.open(url, "_blank");
      setTimeout(() => window.URL.revokeObjectURL(url), 1000);

      ElMessage.success("Overtime Payment preview opened");
      return blob;
    } catch (err) {
      const message =
        err.response?.data?.message ||
        err.message ||
        "Failed to preview report";
      error.value = message;
      ElMessage.error(message);
      throw err;
    } finally {
      loading.value = false;
    }
  };

  // Download report
  const generateReport = async () => {
    try {
      loading.value = true;
      error.value = null;

      if (!formData.payroll_period_id) {
        throw new Error("Payroll Period is required");
      }

      const response =
        await overtimePayrollApi.generateOvertimePayrollReport(
          buildPrintParams()
        );
      const blob = response.data;
      if (!blob || blob.size === 0) {
        throw new Error("Generated PDF is empty - no overtime data found");
      }

      const url = window.URL.createObjectURL(blob);
      const link = document.createElement("a");
      link.href = url;
      link.download = `overtime-payment-${new Date().toISOString().split("T")[0]}.pdf`;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);

      ElMessage.success("Report downloaded successfully");
      return blob;
    } catch (err) {
      const message =
        err.response?.data?.message ||
        err.message ||
        "Failed to generate report";
      error.value = message;
      ElMessage.error(message);
      throw err;
    } finally {
      loading.value = false;
    }
  };

  return {
    loading,
    error,
    formData,
    signatories,
    availableSignatories,
    payrollIntervals,
    payrollPeriods,
    branches,
    divisions,
    loadInitialData,
    loadPayrollPeriods,
    previewReport,
    generateReport,
  };
}
