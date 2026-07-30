import { ref, computed } from "vue";
import { ElMessage } from "element-plus";
import { hazardPayApi } from "../services/api.js";
import {
  divisionsFromApi,
  divisionParams,
} from "../utils/payrollReportDivisions.js";

export function useHazardPay() {
  const loading = ref(false);
  const hazardPayList = ref([]);
  const hazardPaySetup = ref([]);
  const formData = ref({});
  const divisions = ref([]);
  const departments = ref([]);
  const months = ref([]);
  const employees = ref([]);
  const hazardPayDetails = ref([]);

  // Computed properties
  const isFormValid = computed(() => {
    return (
      (formData.value.division_id || formData.value.department_id) &&
      formData.value.month_id &&
      formData.value.year
    );
  });

  const hasSelectedEmployees = computed(() => {
    return employees.value && employees.value.length > 0;
  });

  // Load hazard pay list
  const loadHazardPayList = async () => {
    try {
      loading.value = true;
      const response = await hazardPayApi.getHazardPayList();
      hazardPayList.value = response.data.data || [];
      return response.data;
    } catch (error) {
      console.error("Error loading hazard pay list:", error);
      ElMessage.error("Failed to load hazard pay list");
      throw error;
    } finally {
      loading.value = false;
    }
  };

  // Load hazard pay setup table
  const loadHazardPaySetup = async () => {
    try {
      loading.value = true;
      const response = await hazardPayApi.getHazardPaySetup();
      hazardPaySetup.value = response.data.data || [];
      return response.data;
    } catch (error) {
      console.error("Error loading hazard pay setup:", error);
      ElMessage.error("Failed to load hazard pay setup");
      throw error;
    } finally {
      loading.value = false;
    }
  };

  // Load form data for add/edit (normalized like RATA composable)
  const loadHazardPayForm = async (id = 0, options = {}) => {
    try {
      loading.value = true;
      const response = await hazardPayApi.getHazardPayFormData(
        id,
        divisionParams(options),
      );
      const raw = response.data?.data ?? {};

      // Populate shared refs
      divisions.value = divisionsFromApi(raw);
      departments.value = divisions.value;
      months.value = raw.months || [];
      employees.value = raw.data || [];
      hazardPayDetails.value = raw.data || [];

      // Initialize header formData
      if (id !== 0 && Array.isArray(raw.data) && raw.data.length > 0) {
        formData.value = {
          id: raw.data[0].id,
          division_id: raw.data[0].department_id,
          department_id: raw.data[0].department_id,
          month_id: raw.data[0].month_id,
          year: raw.data[0].year,
          posted: raw.data[0].posted,
        };
      } else {
        formData.value = {
          id: 0,
          division_id: options.division_id || "",
          department_id: options.division_id || "",
          month_id: "",
          year: new Date().getFullYear(),
          posted: false,
        };
      }

      // Normalize available employees pool to a single key, like RATA's rata_employees
      const availablePool =
        raw.available_employees ||
        raw.employees_available ||
        raw.hazard_employees ||
        raw.rata_employees ||
        raw.employees ||
        raw.available ||
        [];

      // Return normalized payload mirroring RATA composable style
      return {
        ...raw,
        hazard_employees: availablePool,
      };
    } catch (error) {
      console.error("Error loading hazard pay form:", error);
      ElMessage.error("Failed to load hazard pay form");
      throw error;
    } finally {
      loading.value = false;
    }
  };

  // Save hazard pay header
  const saveHazardPay = async (id, data) => {
    try {
      loading.value = true;
      const response = await hazardPayApi.saveHazardPay(id, {
        ...data,
        ...divisionParams(data),
      });
      ElMessage.success(
        response.data.message || "Hazard pay saved successfully"
      );
      return response.data;
    } catch (error) {
      console.error("Error saving hazard pay:", error);
      ElMessage.error(
        error.response?.data?.message || "Failed to save hazard pay"
      );
      throw error;
    } finally {
      loading.value = false;
    }
  };

  // Save hazard pay header (without showing message)
  const saveHazardPaySilent = async (id, data) => {
    try {
      loading.value = true;
      const response = await hazardPayApi.saveHazardPay(id, {
        ...data,
        ...divisionParams(data),
      });
      return response.data;
    } catch (error) {
      console.error("Error saving hazard pay:", error);
      ElMessage.error(
        error.response?.data?.message || "Failed to save hazard pay"
      );
      throw error;
    } finally {
      loading.value = false;
    }
  };

  // Save hazard pay employees
  const saveHazardPayEmployees = async (id, employeeData) => {
    try {
      loading.value = true;

      // Transform employee data to match backend format
      const transformedData = {
        hazard_pay_setup_id: [],
        id: [],
        no_of_days: [],
        salary_grade_id: [],
        salary_step_id: [],
        select: [],
      };

      // Process each employee (frontend validation ensures all required fields are set)
      if (employeeData.employees && employeeData.employees.length > 0) {
        employeeData.employees.forEach((emp) => {
          transformedData.hazard_pay_setup_id.push(emp.hazardPaySetupId);
          transformedData.id.push(emp.id);
          transformedData.no_of_days.push(emp.noOfDays);
          transformedData.salary_grade_id.push(emp.salaryGradeId || 1); // Default to 1 if not provided
          transformedData.salary_step_id.push(emp.salaryStepId || 1); // Default to 1 if not provided
          transformedData.select.push(emp.hazardPaySetupId);
        });
      }

      const response = await hazardPayApi.addEmployeesToHazardPay(
        id,
        transformedData
      );
      ElMessage.success(
        response.data.message || "Employees added successfully"
      );
      return response.data;
    } catch (error) {
      console.error("Error adding employees:", error);
      ElMessage.error(
        error.response?.data?.message || "Failed to add employees"
      );
      throw error;
    } finally {
      loading.value = false;
    }
  };

  // Save hazard pay employees (without showing message)
  const saveHazardPayEmployeesSilent = async (id, employeeData) => {
    try {
      loading.value = true;

      // Transform employee data to match backend format
      const transformedData = {
        hazard_pay_setup_id: [],
        id: [],
        no_of_days: [],
        salary_grade_id: [],
        salary_step_id: [],
        select: [],
      };

      // Process each employee (frontend validation ensures all required fields are set)
      if (employeeData.employees && employeeData.employees.length > 0) {
        employeeData.employees.forEach((emp) => {
          transformedData.hazard_pay_setup_id.push(emp.hazardPaySetupId);
          transformedData.id.push(emp.id);
          transformedData.no_of_days.push(emp.noOfDays);
          transformedData.salary_grade_id.push(emp.salaryGradeId || 1); // Default to 1 if not provided
          transformedData.salary_step_id.push(emp.salaryStepId || 1); // Default to 1 if not provided
          transformedData.select.push(emp.hazardPaySetupId);
        });
      }

      const response = await hazardPayApi.addEmployeesToHazardPay(
        id,
        transformedData
      );
      return response.data;
    } catch (error) {
      console.error("Error adding employees:", error);
      ElMessage.error(
        error.response?.data?.message || "Failed to add employees"
      );
      throw error;
    } finally {
      loading.value = false;
    }
  };

  // Remove employee from hazard pay
  const removeHazardPayEmployee = async (id) => {
    try {
      loading.value = true;
      const response = await hazardPayApi.removeEmployeeFromHazardPay(id);
      ElMessage.success(
        response.data.message || "Employee removed successfully"
      );
      return response.data;
    } catch (error) {
      console.error("Error removing employee:", error);
      ElMessage.error(
        error.response?.data?.message || "Failed to remove employee"
      );
      throw error;
    } finally {
      loading.value = false;
    }
  };

  // Process hazard pay (post/unpost)
  const processHazardPay = async (id, typeId) => {
    try {
      loading.value = true;
      const response = await hazardPayApi.processHazardPay(id, typeId);
      const action = typeId == 1 ? "posted" : "unposted";
      ElMessage.success(`Hazard pay ${action} successfully`);
      return response.data;
    } catch (error) {
      console.error("Error processing hazard pay:", error);
      ElMessage.error(
        error.response?.data?.message || "Failed to process hazard pay"
      );
      throw error;
    } finally {
      loading.value = false;
    }
  };

  // Delete hazard pay header (and all related employees)
  const deleteHazardPayHeader = async (id) => {
    try {
      loading.value = true;
      const response = await hazardPayApi.deleteHazardPayHeader(id);
      ElMessage.success(
        response.data.message || "Hazard pay record deleted successfully"
      );
      return response.data;
    } catch (error) {
      console.error("Error deleting hazard pay:", error);
      ElMessage.error(
        error.response?.data?.message || "Failed to delete hazard pay record"
      );
      throw error;
    } finally {
      loading.value = false;
    }
  };

  // Save hazard pay setup
  const saveHazardPaySetup = async (data) => {
    try {
      loading.value = true;
      const response = await hazardPayApi.saveHazardPaySetup(data);
      ElMessage.success(
        response.data.message || "Hazard pay setup saved successfully"
      );
      return response.data;
    } catch (error) {
      console.error("Error saving hazard pay setup:", error);
      ElMessage.error(
        error.response?.data?.message || "Failed to save hazard pay setup"
      );
      throw error;
    } finally {
      loading.value = false;
    }
  };

  // Delete hazard pay setup
  const deleteHazardPaySetup = async (id) => {
    try {
      loading.value = true;
      const response = await hazardPayApi.deleteHazardPaySetup(id);
      ElMessage.success(
        response.data.message || "Hazard pay setup deleted successfully"
      );
      return response.data;
    } catch (error) {
      console.error("Error deleting hazard pay setup:", error);
      ElMessage.error(
        error.response?.data?.message || "Failed to delete hazard pay setup"
      );
      throw error;
    } finally {
      loading.value = false;
    }
  };

  // Generate hazard pay report
  const generateHazardPayReport = async (requestData) => {
    try {
      loading.value = true;
      const response = await hazardPayApi.generateHazardPayReport(requestData);

      // Create blob and download
      const blob = new Blob([response.data], { type: "application/pdf" });
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement("a");
      link.href = url;
      link.download = `hazard_pay_report_${requestData.department_id}_${
        requestData.month_id
      }_${requestData.year}_${new Date().toISOString().split("T")[0]}.pdf`;
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

  // Load report data
  const loadHazardPayReport = async () => {
    try {
      loading.value = true;
      const response = await hazardPayApi.getHazardPayReportData();
      return response.data.data;
    } catch (error) {
      console.error("Error loading hazard pay report:", error);
      ElMessage.error("Failed to load hazard pay report");
      throw error;
    } finally {
      loading.value = false;
    }
  };

  // Transform backend data to frontend format
  const transformHazardPayData = (backendData) => {
    return backendData.map((item) => {
      // Ensure posted is a boolean
      const posted = Boolean(
        item.posted === true || item.posted === 1 || item.posted === "1"
      );

      return {
        id: item.id,
        division: item.division || item.department,
        department: item.division || item.department,
        month: item.month,
        year: item.year,
        division_id: item.department_id,
        department_id: item.department_id,
        month_id: item.month_id,
        posted: posted,
        status: posted ? "posted" : "unposted",
      };
    });
  };

  // Transform employee data for display
  const transformEmployeeData = (backendData) => {
    return backendData.map((item) => ({
      id: item.employee_id,
      hazardDtl: item.hazard_dtl,
      employeeNo: item.employee_no,
      name: item.name,
      nameSuffix: item.name_suffix,
      position: item.position,
      // Coerce potentially string values from backend to numbers to satisfy Element Plus inputs
      noOfDays:
        item.no_of_days === null || item.no_of_days === undefined
          ? 0
          : Number(item.no_of_days),
      salaryGradeId:
        item.salary_grade_id === null || item.salary_grade_id === undefined
          ? undefined
          : Number(item.salary_grade_id),
      salaryStepId:
        item.salary_step_id === null || item.salary_step_id === undefined
          ? undefined
          : Number(item.salary_step_id),
      hazardPaySetupId:
        item.hazard_pay_setup_id === null ||
        item.hazard_pay_setup_id === undefined
          ? null
          : Number(item.hazard_pay_setup_id),
      salary: item.salary,
    }));
  };

  // Reset form data
  const resetFormData = () => {
    formData.value = {
      id: 0,
      division_id: "",
      department_id: "",
      month_id: "",
      year: new Date().getFullYear(),
      posted: false,
    };
    employees.value = [];
    hazardPayDetails.value = [];
  };

  return {
    // State
    loading,
    hazardPayList,
    hazardPaySetup,
    formData,
    divisions,
    departments,
    months,
    employees,
    hazardPayDetails,

    // Computed
    isFormValid,
    hasSelectedEmployees,

    // Methods
    loadHazardPayList,
    loadHazardPaySetup,
    loadHazardPayForm,
    saveHazardPay,
    saveHazardPaySilent,
    saveHazardPayEmployees,
    saveHazardPayEmployeesSilent,
    removeHazardPayEmployee,
    processHazardPay,
    deleteHazardPayHeader,
    saveHazardPaySetup,
    deleteHazardPaySetup,
    generateHazardPayReport,
    loadHazardPayReport,
    transformHazardPayData,
    transformEmployeeData,
    resetFormData,
  };
}
