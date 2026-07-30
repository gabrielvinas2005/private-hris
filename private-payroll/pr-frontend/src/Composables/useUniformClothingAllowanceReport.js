import { ref, reactive, watch } from "vue";
import { signatoryApi, uniformClothingAllowanceApi } from "../services/api.js";
import { ElMessage } from "element-plus";

// Composable for Uniform & Clothing Allowance Payroll Report (PDF)
export function useUniformClothingAllowanceReport() {
  const loading = ref(false);
  const error = ref(null);

  const formData = reactive({
    payroll_period_id: "",
    branch_id: "",
  });

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
  const SIGNATORIES_STORAGE_KEY =
    "uniform_clothing_allowance_report_signatories_v1";

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

  const payrollPeriods = ref([]);
  const branches = ref([]);

  const loadInitialData = async () => {
    try {
      loading.value = true;
      error.value = null;

      const response =
        await uniformClothingAllowanceApi.getUniformClothingAllowanceReportData();
      const data = response.data?.data || {};

      payrollPeriods.value = Array.isArray(data.pay_periods)
        ? data.pay_periods
        : [];

      const rawBranches = Array.isArray(data.data) ? data.data : [];
      branches.value = rawBranches.map((b) => ({
        id: b.branch || b.name || b,
        name: b.branch || b.name || String(b),
      }));

      // Load employee options for signatory dropdowns (reuse ATM Letter endpoint)
      try {
        const sigRes = await signatoryApi.getEmployeeOptions();
        const sdata = sigRes.data?.data || {};
        availableSignatories.value = Array.isArray(sdata.employee_options)
          ? sdata.employee_options
          : [];
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

  const loadSignatories = async () => {
    try {
      const sigRes = await signatoryApi.getEmployeeOptions();
      const sdata = sigRes.data?.data || {};
      availableSignatories.value = Array.isArray(sdata.employee_options)
        ? sdata.employee_options
        : [];
      return true;
    } catch {
      availableSignatories.value = [];
      return false;
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
            JSON.stringify(value),
          );
        } catch {
          // Ignore quota / storage errors
        }
      },
      { deep: true },
    );
  }

  const buildPrintParams = () => ({
    // Backend historically expects payroll_interval_id, but this is actually payroll_period_id.
    // Send both for compatibility.
    payroll_period_id: formData.payroll_period_id,
    payroll_interval_id: formData.payroll_period_id,
    branch_id: formData.branch_id,
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

  const validateBeforePrint = () => {
    if (!formData.payroll_period_id)
      throw new Error("Payroll Period is required");

    const requiredKeys = [
      "signatory_1",
      "signatory_position_1",
      "signatory_2",
      "signatory_position_2",
      "signatory_3",
      "signatory_position_3",
      "signatory_4",
      "signatory_position_4",
      "signatory_5",
      "signatory_position_5",
    ];
    for (const key of requiredKeys) {
      const value = (signatories[key] || "").toString().trim();
      if (!value) {
        throw new Error("All signatory names and positions are required");
      }
    }
  };

  const previewReport = async () => {
    try {
      loading.value = true;
      error.value = null;
      validateBeforePrint();

      const response =
        await uniformClothingAllowanceApi.generateUniformClothingAllowanceReport(
          buildPrintParams()
        );
      const blob = response.data;
      if (!blob || blob.size === 0) {
        throw new Error("Generated PDF is empty - no data found");
      }
      const url = window.URL.createObjectURL(blob);
      window.open(url, "_blank");
      setTimeout(() => window.URL.revokeObjectURL(url), 1000);
      ElMessage.success("Uniform & Clothing Allowance preview opened");
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

  const generateReport = async () => {
    try {
      loading.value = true;
      error.value = null;
      validateBeforePrint();

      const response =
        await uniformClothingAllowanceApi.generateUniformClothingAllowanceReport(
          buildPrintParams()
        );
      const blob = response.data;
      if (!blob || blob.size === 0) {
        throw new Error("Generated PDF is empty - no data found");
      }
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement("a");
      link.href = url;
      link.download = `uniform-clothing-allowance-${
        new Date().toISOString().split("T")[0]
      }.pdf`;
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
    payrollPeriods,
    branches,
    loadInitialData,
    loadSignatories,
    previewReport,
    generateReport,
  };
}
