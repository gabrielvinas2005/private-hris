import { ref, computed } from "vue";
import api from "../services/api.js";

// HDMF Premium API endpoints
export const hdmfPremiumApi = {
  // Get payroll periods for dropdown
  getPayrollPeriods: () => api.get("/pagibig-payroll"),

  // Load employees for specific payroll period
  loadEmployees: (payrollPeriodId) =>
    api.get(`/pagibig-payroll/${payrollPeriodId}/employees`),

  // Save/update HDMF premium data
  savePremium: (data) => api.post("/pagibig-payroll/employees", data),

  // Validate amount before saving
  validateAmount: (employeeId, amount, payrollPeriodId) =>
    api.get(
      `/pagibig-payroll/${employeeId}/${amount}/${payrollPeriodId}/validate`
    ),
};

export function useHDMFPremium() {
  const loading = ref(false);
  const error = ref(null);
  const payrollPeriods = ref([]);
  const selectedPayrollPeriod = ref(null);
  const employees = ref([]);
  const selectedEmployees = ref([]);
  const premiumData = ref([]);

  // Computed properties
  const hasSelectedPeriod = computed(
    () => selectedPayrollPeriod.value !== null
  );
  const hasEmployees = computed(() => employees.value.length > 0);
  const selectedEmployeeCount = computed(() => selectedEmployees.value.length);

  // Load payroll periods
  const loadPayrollPeriods = async () => {
    try {
      loading.value = true;
      error.value = null;
      const response = await hdmfPremiumApi.getPayrollPeriods();
      payrollPeriods.value = response.data.data || [];
    } catch (err) {
      error.value =
        err.response?.data?.message || "Failed to load payroll periods";
      console.error("Error loading payroll periods:", err);
    } finally {
      loading.value = false;
    }
  };

  // Load employees for selected payroll period
  const loadEmployees = async (payrollPeriodId) => {
    try {
      loading.value = true;
      error.value = null;
      const response = await hdmfPremiumApi.loadEmployees(payrollPeriodId);
      const data = response.data.data;
      employees.value = data.pagibig_payrolls || [];
      // Find the period object from payrollPeriods array instead of just storing the ID
      // Use loose equality to handle string/number mismatches
      const period = payrollPeriods.value.find(
        (p) => String(p.id) === String(payrollPeriodId)
      );
      selectedPayrollPeriod.value = period || null;
    } catch (err) {
      error.value = err.response?.data?.message || "Failed to load employees";
      console.error("Error loading employees:", err);
    } finally {
      loading.value = false;
    }
  };

  // Validate amount for an employee
  const validateAmount = async (employeeId, amount, payrollPeriodId) => {
    try {
      const response = await hdmfPremiumApi.validateAmount(
        employeeId,
        amount,
        payrollPeriodId
      );
      return response.data.success;
    } catch (err) {
      error.value = err.response?.data?.message || "Amount validation failed";
      return false;
    }
  };

  // Save premium data
  const savePremium = async (data) => {
    try {
      loading.value = true;
      error.value = null;
      const response = await hdmfPremiumApi.savePremium(data);
      return response.data;
    } catch (err) {
      error.value =
        err.response?.data?.message || "Failed to save premium data";
      console.error("Error saving premium:", err);
      throw err;
    } finally {
      loading.value = false;
    }
  };

  // Select/deselect employee
  const toggleEmployeeSelection = (employee) => {
    const index = selectedEmployees.value.findIndex(
      (emp) => emp.employee_id === employee.employee_id
    );
    if (index > -1) {
      selectedEmployees.value.splice(index, 1);
    } else {
      selectedEmployees.value.push({
        ...employee,
        amount: employee.amount || 200, // Default amount
      });
    }
  };

  // Check if employee is selected
  const isEmployeeSelected = (employeeId) => {
    return selectedEmployees.value.some(
      (emp) => emp.employee_id === employeeId
    );
  };

  // Update amount for selected employee
  const updateEmployeeAmount = (employeeId, amount) => {
    const employee = selectedEmployees.value.find(
      (emp) => emp.employee_id === employeeId
    );
    if (employee) {
      employee.amount = parseFloat(amount) || 0;
    }
  };

  // Clear selections
  const clearSelections = () => {
    selectedEmployees.value = [];
    selectedPayrollPeriod.value = null;
    employees.value = [];
  };

  // Reset state
  const reset = () => {
    loading.value = false;
    error.value = null;
    payrollPeriods.value = [];
    selectedPayrollPeriod.value = null;
    employees.value = [];
    selectedEmployees.value = [];
    premiumData.value = [];
  };

  return {
    // State
    loading,
    error,
    payrollPeriods,
    selectedPayrollPeriod,
    employees,
    selectedEmployees,
    premiumData,

    // Computed
    hasSelectedPeriod,
    hasEmployees,
    selectedEmployeeCount,

    // Methods
    loadPayrollPeriods,
    loadEmployees,
    validateAmount,
    savePremium,
    toggleEmployeeSelection,
    isEmployeeSelected,
    updateEmployeeAmount,
    clearSelections,
    reset,
  };
}
