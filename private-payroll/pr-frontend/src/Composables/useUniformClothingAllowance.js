import { ref, computed } from "vue";
import { uniformClothingAllowanceApi } from "../services/api.js";
import { divisionsFromApi } from "../utils/payrollReportDivisions.js";
import { ElMessage } from "element-plus";

export function useUniformClothingAllowance() {
  const loading = ref(false);
  const allowanceList = ref([]);
  const formData = ref({});
  const branches = ref([]);
  const months = ref([]);
  const divisions = ref([]);
  const employees = ref([]);
  const selectedEmployees = ref([]);
  const payPeriods = ref([]);

  // Computed properties
  const isFormValid = computed(() => {
    return (
      formData.value.division_id &&
      formData.value.year &&
      formData.value.month_id
    );
  });

  const hasSelectedEmployees = computed(() => {
    return selectedEmployees.value && selectedEmployees.value.length > 0;
  });

  // Available years for dropdown
  const availableYears = computed(() => {
    const currentYear = new Date().getFullYear();
    const years = [];

    // Generate years from 2000 to current year + 5
    for (let year = 2000; year <= currentYear + 5; year++) {
      years.push(year);
    }
    return years;
  });

  // Load uniform clothing allowance list
  const loadAllowanceList = async () => {
    try {
      loading.value = true;
      const response =
        await uniformClothingAllowanceApi.getUniformClothingAllowanceList();
      allowanceList.value = response.data.data || [];
      return response.data;
    } catch (error) {
      console.error("Error loading uniform clothing allowance list:", error);
      ElMessage.error("Failed to load uniform clothing allowance list");
      throw error;
    } finally {
      loading.value = false;
    }
  };

  // Load form data for add/edit
  const loadFormData = async (id = 0, options = {}) => {
    try {
      loading.value = true;
      const response =
        await uniformClothingAllowanceApi.getUniformClothingAllowanceFormData(
          id,
          options,
        );
      const payload = response.data?.data ?? response.data ?? {};
      const headerRows = Array.isArray(payload.data) ? payload.data : [];

      // Only set form data if we're editing an existing record
      // For new records, don't overwrite existing form data
      if (id > 0) {
        const header = headerRows[0] || {};
        formData.value = {
          ...header,
          id: header.id ?? id,
          division_id: header.department_id ?? header.division_id ?? null,
        };
      } else {
        const currentYear = new Date().getFullYear();
        formData.value = {
          ...formData.value,
          year: currentYear,
          month_id: null,
        };
      }

      // Always load dropdown data
      branches.value = (payload.branch || []).filter(
        (branch) => String(branch.id) !== "0",
      );
      months.value = payload.months || [];
      divisions.value = divisionsFromApi(payload) || [];
      employees.value = payload.employees || [];

      if (!options.preserveSelectedEmployees) {
        selectedEmployees.value = payload.t_employees || [];
      }

      return payload;
    } catch (error) {
      console.error("Error loading form data:", error);
      ElMessage.error("Failed to load form data");
      throw error;
    } finally {
      loading.value = false;
    }
  };

  // Save uniform clothing allowance
  const saveAllowance = async (id, data) => {
    try {
      loading.value = true;
      const response =
        await uniformClothingAllowanceApi.saveUniformClothingAllowance(
          id,
          data,
        );
      ElMessage.success(
        response.data.message ||
          "Uniform clothing allowance saved successfully",
      );
      return response.data;
    } catch (error) {
      console.error("Error saving uniform clothing allowance:", error);
      ElMessage.error(
        error.response?.data?.message ||
          "Failed to save uniform clothing allowance",
      );
      throw error;
    } finally {
      loading.value = false;
    }
  };

  // Add employees to allowance
  const addEmployees = async (id, employeeData) => {
    try {
      loading.value = true;
      const response =
        await uniformClothingAllowanceApi.addEmployeesToUniformClothingAllowance(
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

  // Delete allowance
  const deleteAllowance = async (id) => {
    try {
      loading.value = true;
      const response =
        await uniformClothingAllowanceApi.deleteUniformClothingAllowance(id);
      ElMessage.success(
        response.data.message ||
          "Uniform clothing allowance deleted successfully",
      );
      return response.data;
    } catch (error) {
      console.error("Error deleting allowance:", error);
      ElMessage.error(
        error.response?.data?.message ||
          "Failed to delete uniform clothing allowance",
      );
      throw error;
    } finally {
      loading.value = false;
    }
  };

  // Remove employee from allowance
  const removeEmployee = async (id) => {
    try {
      loading.value = true;
      const response =
        await uniformClothingAllowanceApi.removeEmployeeFromUniformClothingAllowance(
          id,
        );
      ElMessage.success("Employee removed successfully");
      return response.data;
    } catch (error) {
      console.error("Error removing employee:", error);
      ElMessage.error("Failed to remove employee");
      throw error;
    } finally {
      loading.value = false;
    }
  };

  // Post allowance
  const postAllowance = async (id) => {
    try {
      loading.value = true;
      const response =
        await uniformClothingAllowanceApi.postUniformClothingAllowance(id);
      ElMessage.success(
        response.data.message ||
          "Uniform clothing allowance posted successfully",
      );
      return response.data;
    } catch (error) {
      console.error("Error posting allowance:", error);
      ElMessage.error(
        error.response?.data?.message || "Failed to post allowance",
      );
      throw error;
    } finally {
      loading.value = false;
    }
  };

  // Unpost allowance
  const unpostAllowance = async (id) => {
    try {
      loading.value = true;
      const response =
        await uniformClothingAllowanceApi.unpostUniformClothingAllowance(id);
      ElMessage.success(
        response.data.message ||
          "Uniform clothing allowance unposted successfully",
      );
      return response.data;
    } catch (error) {
      console.error("Error unposting allowance:", error);
      ElMessage.error(
        error.response?.data?.message || "Failed to unpost allowance",
      );
      throw error;
    } finally {
      loading.value = false;
    }
  };

  // Load report data
  const loadReportData = async () => {
    try {
      loading.value = true;
      const response =
        await uniformClothingAllowanceApi.getUniformClothingAllowanceReportData();
      const data = response.data.data;

      payPeriods.value = data.pay_periods || [];

      return data;
    } catch (error) {
      console.error("Error loading report data:", error);
      ElMessage.error("Failed to load report data");
      throw error;
    } finally {
      loading.value = false;
    }
  };

  // Generate report
  const generateReport = async (requestData) => {
    try {
      loading.value = true;
      const response =
        await uniformClothingAllowanceApi.generateUniformClothingAllowanceReport(
          requestData,
        );

      // Create blob and download
      const blob = new Blob([response.data], { type: "application/pdf" });
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement("a");
      link.href = url;
      link.download = `uniform_clothing_allowance_${requestData.branch_id}_${requestData.payroll_interval_id}_${new Date().toISOString().split("T")[0]}.pdf`;
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
  const transformAllowanceData = (backendData) => {
    return backendData.map((item) => {
      // Convert string '0'/'1' to boolean properly
      const isPosted =
        item.posted === "1" || item.posted === 1 || item.posted === true;

      return {
        id: item.id,
        branch: item.branch,
        month: item.month,
        monthNumber:
          parseInt(item.month_number ?? item.month_id ?? item.month, 10) ||
          null,
        year: item.year,
        posted: isPosted,
        status: isPosted ? "posted" : "draft",
      };
    });
  };

  // Transform employee data for display
  const transformEmployeeData = (backendData) => {
    return backendData.map((item) => ({
      id: item.id,
      employeeId: item.employee_id,
      employeeNo: item.employee_no,
      name: item.name,
      position: item.position,
      division: item.division ?? item.department,
      department: item.division ?? item.department,
      branch: item.branch,
      employmentType: item.employment_type,
      photo: item.photo,
      uniformClothingDetailsId: item.uniform_clothing_details_id,
      clothRate: item.cloth_rate || 0,
      uniformRate: item.uniform_rate || 0,
      totalAmount: (item.cloth_rate || 0) + (item.uniform_rate || 0),
    }));
  };

  // Reset form data
  const resetFormData = () => {
    const currentYear = new Date().getFullYear();
    formData.value = {
      id: 0,
      branch_id: null,
      division_id: null,
      month_id: null,
      year: currentYear, // Auto-select current year
      posted: null,
      active: null,
    };
    employees.value = [];
    selectedEmployees.value = [];
  };

  return {
    // State
    loading,
    allowanceList,
    formData,
    branches,
    months,
    divisions,
    employees,
    selectedEmployees,
    payPeriods,

    // Computed
    isFormValid,
    hasSelectedEmployees,
    availableYears,

    // Methods
    loadAllowanceList,
    loadFormData,
    saveAllowance,
    addEmployees,
    removeEmployee,
    deleteAllowance,
    postAllowance,
    unpostAllowance,
    loadReportData,
    generateReport,
    transformAllowanceData,
    transformEmployeeData,
    resetFormData,
  };
}
