import { ref, computed } from "vue";
import { rataPayrollApi } from "../services/api.js";

export function useRATAPayroll() {
  const loading = ref(false);
  const error = ref(null);
  const rataPayrollList = ref([]);
  const rataFormData = ref(null);
  const rataReportData = ref(null);

  // RATA Positions Management
  const loadRATAPositions = async () => {
    try {
      loading.value = true;
      error.value = null;
      const response = await rataPayrollApi.getRATAPositions();
      return response.data;
    } catch (err) {
      error.value =
        err.response?.data?.message || "Failed to load RATA positions";
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const saveRATAPositions = async (data) => {
    try {
      loading.value = true;
      error.value = null;
      const response = await rataPayrollApi.saveRATAPositions(data);
      return response.data;
    } catch (err) {
      error.value =
        err.response?.data?.message || "Failed to save RATA positions";
      throw err;
    } finally {
      loading.value = false;
    }
  };

  // RATA Table Management
  const loadRATATable = async () => {
    try {
      loading.value = true;
      error.value = null;
      const response = await rataPayrollApi.getRATATable();
      return response.data;
    } catch (err) {
      error.value = err.response?.data?.message || "Failed to load RATA table";
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const saveRATATable = async (data) => {
    try {
      loading.value = true;
      error.value = null;
      const response = await rataPayrollApi.saveRATATable(data);
      return response.data;
    } catch (err) {
      error.value = err.response?.data?.message || "Failed to save RATA table";
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const deleteRATATableRecord = async (id) => {
    try {
      loading.value = true;
      error.value = null;
      const response = await rataPayrollApi.deleteRATATable(id);
      return response.data;
    } catch (err) {
      error.value =
        err.response?.data?.message || "Failed to delete RATA table record";
      throw err;
    } finally {
      loading.value = false;
    }
  };

  // RATA Payroll Management
  const loadRATAPayrollList = async () => {
    try {
      loading.value = true;
      error.value = null;
      const response = await rataPayrollApi.getRATAPayrollList();
      // Normalize list like Macco: ensure boolean 'posted' correctness
      const raw = response.data.data || [];
      const normalized = raw.map((item) => {
        const isPosted =
          item.posted === true ||
          item.posted === 1 ||
          item.posted === "1" ||
          item.posted === "true";
        return { ...item, posted: !!isPosted };
      });
      rataPayrollList.value = normalized;
      return response.data;
    } catch (err) {
      error.value =
        err.response?.data?.message || "Failed to load RATA payroll list";
      console.error("RATA Payroll List Error:", err);
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const loadRATAPayrollFormData = async (id = 0) => {
    try {
      loading.value = true;
      error.value = null;
      const response = await rataPayrollApi.getRATAPayrollFormData(id);
      // Normalize like other modules: backend often wraps in { data: {...} }
      const payload = response.data?.data ?? response.data ?? {};
      rataFormData.value = payload;
      return payload;
    } catch (err) {
      error.value =
        err.response?.data?.message || "Failed to load RATA payroll form data";
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const saveRATAPayroll = async (id, data) => {
    try {
      loading.value = true;
      error.value = null;
      const response = await rataPayrollApi.saveRATAPayroll(id, data);
      return response.data;
    } catch (err) {
      error.value =
        err.response?.data?.message || "Failed to save RATA payroll";
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const addEmployeesToRATAPayroll = async (id, employeeData) => {
    try {
      loading.value = true;
      error.value = null;
      const response = await rataPayrollApi.addEmployeesToRATAPayroll(
        id,
        employeeData,
      );
      return response.data;
    } catch (err) {
      error.value =
        err.response?.data?.message ||
        "Failed to add employees to RATA payroll";
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const updateRATAPayrollEmployees = async (id, employeeData) => {
    try {
      loading.value = true;
      error.value = null;
      const response = await rataPayrollApi.updateRATAPayrollEmployees(
        id,
        employeeData,
      );
      return response.data;
    } catch (err) {
      error.value =
        err.response?.data?.message ||
        "Failed to update RATA payroll employees";
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const removeEmployeeFromRATAPayroll = async (detailId) => {
    try {
      loading.value = true;
      error.value = null;
      const response =
        await rataPayrollApi.removeEmployeeFromRATAPayroll(detailId);
      return response.data;
    } catch (err) {
      error.value =
        err.response?.data?.message ||
        "Failed to remove employee from RATA payroll";
      console.error("Remove Employee Error:", err);
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const processRATAPayroll = async (id, typeId) => {
    try {
      loading.value = true;
      error.value = null;
      const response = await rataPayrollApi.processRATAPayroll(id, typeId);
      return response.data;
    } catch (err) {
      error.value =
        err.response?.data?.message || "Failed to process RATA payroll";
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const deleteRATAPayroll = async (id) => {
    try {
      loading.value = true;
      error.value = null;
      const response = await rataPayrollApi.deleteRATAPayroll(id);
      await loadRATAPayrollList();
      return response.data;
    } catch (err) {
      error.value =
        err.response?.data?.message || "Failed to delete RATA payroll";
      throw err;
    } finally {
      loading.value = false;
    }
  };

  // RATA Report Management
  const loadRATAReportData = async () => {
    try {
      loading.value = true;
      error.value = null;
      const response = await rataPayrollApi.getRATAReportData();
      rataReportData.value = response.data;
      return response.data;
    } catch (err) {
      error.value =
        err.response?.data?.message || "Failed to load RATA report data";
      console.error("RATA Report Data Error:", err);
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const generateRATAPayrollReport = async (requestData) => {
    try {
      loading.value = true;
      error.value = null;
      const response =
        await rataPayrollApi.generateRATAPayrollReport(requestData);

      // Create blob and download
      const blob = new Blob([response.data], { type: "application/pdf" });
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement("a");
      link.href = url;
      link.download = `rata_payroll_report_${requestData.rata_payroll_id}_${new Date().toISOString().split("T")[0]}.pdf`;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);

      return response.data;
    } catch (err) {
      error.value =
        err.response?.data?.message || "Failed to generate RATA payroll report";
      throw err;
    } finally {
      loading.value = false;
    }
  };

  // Computed properties
  const isLoaded = computed(() => rataPayrollList.value.length > 0);
  const hasFormData = computed(() => rataFormData.value !== null);
  const hasReportData = computed(() => rataReportData.value !== null);

  // Utility functions
  const clearError = () => {
    error.value = null;
  };

  const resetData = () => {
    rataPayrollList.value = [];
    rataFormData.value = null;
    rataReportData.value = null;
    error.value = null;
  };

  return {
    // State
    loading,
    error,
    rataPayrollList,
    rataFormData,
    rataReportData,

    // RATA Positions
    loadRATAPositions,
    saveRATAPositions,

    // RATA Table
    loadRATATable,
    saveRATATable,
    deleteRATATableRecord,

    // RATA Payroll
    loadRATAPayrollList,
    loadRATAPayrollFormData,
    saveRATAPayroll,
    addEmployeesToRATAPayroll,
    updateRATAPayrollEmployees,
    removeEmployeeFromRATAPayroll,
    processRATAPayroll,
    deleteRATAPayroll,

    // RATA Reports
    loadRATAReportData,
    generateRATAPayrollReport,

    // Computed
    isLoaded,
    hasFormData,
    hasReportData,

    // Utilities
    clearError,
    resetData,
  };
}
