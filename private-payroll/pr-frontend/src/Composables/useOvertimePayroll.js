import { ref, computed } from "vue";
import { overtimePayrollApi } from "../services/api.js";
import { ElMessage } from "element-plus";

export function useOvertimePayroll() {
  const loading = ref(false);
  const overtimePayrollList = ref([]);
  const formData = ref({});
  const payrollPeriods = ref([]);
  const payrollIntervals = ref([]);
  const employees = ref([]);
  const employeeUnselected = ref([]);
  const overtimeTypes = ref([]);
  const otTax = ref([]);

  // Computed properties
  const isFormValid = computed(() => {
    return (
      formData.value.payroll_period_id && formData.value.payroll_interval_id
    );
  });

  const hasSelectedEmployees = computed(() => {
    return employees.value && employees.value.length > 0;
  });

  // Load overtime payroll list
  const loadOvertimePayrollList = async () => {
    try {
      loading.value = true;
      const response = await overtimePayrollApi.getOvertimePayrollList();
      overtimePayrollList.value = response.data.data || [];
      return response.data;
    } catch (error) {
      console.error("Error loading overtime payroll list:", error);
      ElMessage.error("Failed to load overtime payroll list");
      throw error;
    } finally {
      loading.value = false;
    }
  };

  // Load form data for add/edit
  const loadFormData = async (id = 0) => {
    try {
      loading.value = true;
      const response = await overtimePayrollApi.getOvertimePayrollFormData(id);
      const data = response.data.data;

      formData.value = data.data[0] || {};

      if (!formData.value || id === 0 || Number(formData.value.id || 0) === 0) {
        formData.value.payroll_interval_id = "";
        formData.value.payroll_period_id = "";
      }
      payrollIntervals.value = data.payroll_intervals || [];
      payrollPeriods.value = data.payroll_periods || [];
      employees.value = data.employees || [];
      employeeUnselected.value = data.employee_unselected || [];
      overtimeTypes.value = data.overtime_types || [];
      otTax.value = data.ot_tax || [];

      return data;
    } catch (error) {
      console.error("Error loading form data:", error);
      ElMessage.error("Failed to load form data");
      throw error;
    } finally {
      loading.value = false;
    }
  };

  // Load process data (periods and intervals for processing)
  const loadProcessData = async () => {
    try {
      loading.value = true;
      const response = await overtimePayrollApi.getOvertimePayrollProcessData();
      const data = response.data.data;

      payrollIntervals.value = data.payroll_intervals || [];
      payrollPeriods.value = data.periods || [];

      return data;
    } catch (error) {
      console.error("Error loading process data:", error);
      ElMessage.error("Failed to load process data");
      throw error;
    } finally {
      loading.value = false;
    }
  };

  // Save overtime payroll
  const saveOvertimePayroll = async (id, data) => {
    try {
      loading.value = true;
      const response = await overtimePayrollApi.saveOvertimePayroll(id, data);
      ElMessage.success(
        response.data.message || "Overtime payroll saved successfully",
      );
      return response.data;
    } catch (error) {
      console.error("Error saving overtime payroll:", error);
      ElMessage.error(
        error.response?.data?.message || "Failed to save overtime payroll",
      );
      throw error;
    } finally {
      loading.value = false;
    }
  };

  // Add employees to overtime payroll
  const addEmployees = async (id, employeeData) => {
    try {
      loading.value = true;
      const response = await overtimePayrollApi.addEmployeesToOvertimePayroll(
        id,
        employeeData,
      );
      ElMessage.success(
        response.data.message || "Employees added successfully",
      );
      return response.data;
    } catch (error) {
      console.error("Error adding employees:", error);
      ElMessage.error(
        error.response?.data?.message || "Failed to add employees",
      );
      throw error;
    } finally {
      loading.value = false;
    }
  };

  // Remove employee from overtime payroll
  const removeEmployee = async (id) => {
    try {
      loading.value = true;
      const response =
        await overtimePayrollApi.removeEmployeeFromOvertimePayroll(id);
      ElMessage.success(
        response.data.message || "Employee removed successfully",
      );
      return response.data;
    } catch (error) {
      console.error("Error removing employee:", error);
      ElMessage.error(
        error.response?.data?.message || "Failed to remove employee",
      );
      throw error;
    } finally {
      loading.value = false;
    }
  };

  // Process overtime payroll (post/unpost)
  const processOvertimePayroll = async (id, typeId) => {
    try {
      loading.value = true;
      const response = await overtimePayrollApi.processOvertimePayroll(
        id,
        typeId,
      );
      const action = typeId == 1 ? "posted" : "unposted";
      ElMessage.success(`Overtime payroll ${action} successfully`);
      return response.data;
    } catch (error) {
      console.error("Error processing overtime payroll:", error);
      ElMessage.error(
        error.response?.data?.message || "Failed to process overtime payroll",
      );
      throw error;
    } finally {
      loading.value = false;
    }
  };

  // Delete overtime payroll
  const deleteOvertimePayroll = async (id) => {
    try {
      loading.value = true;
      const response = await overtimePayrollApi.deleteOvertimePayroll(id);
      ElMessage.success(
        response.data.message || "Overtime payroll deleted successfully",
      );
      return response.data;
    } catch (error) {
      console.error("Error deleting overtime payroll:", error);
      ElMessage.error(
        error.response?.data?.message || "Failed to delete overtime payroll",
      );
      throw error;
    } finally {
      loading.value = false;
    }
  };

  // Generate overtime payroll report
  const generateReport = async (requestData) => {
    try {
      loading.value = true;
      const response =
        await overtimePayrollApi.generateOvertimePayrollReport(requestData);

      // Create blob and download
      const blob = new Blob([response.data], { type: "application/pdf" });
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement("a");
      link.href = url;
      link.download = `overtime_payroll_${requestData.payroll_period_id}_${new Date().toISOString().split("T")[0]}.pdf`;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);

      ElMessage.success("Report generated successfully");
      return response.data;
    } catch (error) {
      console.error("Error generating report:", error);
      ElMessage.error("Failed to generate report");
      throw error;
    } finally {
      loading.value = false;
    }
  };

  // Transform backend data to frontend format
  const transformOvertimeData = (backendData) => {
    return backendData.map((item) => ({
      id: item.id,
      payrollPeriodId: item.payroll_period_id,
      payrollPeriod: item.payroll_period,
      cutOff: item.cut_off,
      releaseDate: item.release_date,
      attendanceStartDate: item.attendance_start_date,
      attendanceEndDate: item.attendance_end_date,
      posted:
        item.posted === true ||
        item.posted === 1 ||
        item.posted === "1" ||
        item.posted === "true",
      totalAmount: Number(item.total_earned ?? 0),
      status:
        item.posted === true ||
        item.posted === 1 ||
        item.posted === "1" ||
        item.posted === "true"
          ? "posted"
          : "draft",
    }));
  };

  // Transform employee data for display
  const transformEmployeeData = (backendData) => {
    return backendData.map((item) => ({
      id: item.id,
      employeeId: item.employee_id,
      employeeNo: item.employee_no,
      name: item.name,
      position: item.position,
      totalHours: item.total_hours,
      salary: item.salary,
      overtimeTypeId: item.overtime_type_id,
      dtlId: item.dtl_id,
      photo: item.photo,
      // Add calculated fields
      computedEarned: Number(item.computed_earned ?? 0),
      otAdjustment: Number(item.ot_adjustment ?? 0),
      earned: Number(item.earned ?? 0),
    }));
  };

  // Reset form data
  const resetFormData = () => {
    formData.value = {
      id: 0,
      payroll_period_id: "",
      payroll_interval_id: "",
      date_forwarded: "",
      attendance_start_date: "",
      attendance_end_date: "",
      posted: 0,
    };
    employees.value = [];
    employeeUnselected.value = [];
  };

  return {
    // State
    loading,
    overtimePayrollList,
    formData,
    payrollPeriods,
    payrollIntervals,
    employees,
    employeeUnselected,
    overtimeTypes,
    otTax,

    // Computed
    isFormValid,
    hasSelectedEmployees,

    // Methods
    loadOvertimePayrollList,
    loadFormData,
    loadProcessData,
    saveOvertimePayroll,
    addEmployees,
    removeEmployee,
    processOvertimePayroll,
    deleteOvertimePayroll,
    generateReport,
    transformOvertimeData,
    transformEmployeeData,
    resetFormData,
  };
}
