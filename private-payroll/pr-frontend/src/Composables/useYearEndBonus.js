import { ref, computed } from "vue";
import { divisionsFromApi, divisionParams } from "../utils/payrollReportDivisions.js";
import { formatApiError } from "../utils/apiErrorMessage.js";
import { yearEndBonusApi } from "../services/api.js";
import { ElMessage } from "element-plus";

export function useYearEndBonus() {
  const loading = ref(false);
  const yearEndBonusList = ref([]);
  const formData = ref({});
  const branches = ref([]);
  const departments = ref([]);
  const employees = ref([]);
  const years = ref([]);

  // Computed properties
  const isFormValid = computed(() => {
    return (
      formData.value.branch_id &&
      formData.value.department_id &&
      formData.value.years
    );
  });

  const hasSelectedEmployees = computed(() => {
    return employees.value && employees.value.length > 0;
  });

  // Load year-end bonus data
  const loadYearEndBonusData = async () => {
    try {
      loading.value = true;
      const response = await yearEndBonusApi.getYearEndBonusData();
      const data = response.data.data;

      branches.value = (data.branches || []).filter(
        (branch) => String(branch.id) !== "0"
      );
      departments.value = (divisionsFromApi(data) || []).filter(
        (division) => String(division.id) !== "0",
      );
      yearEndBonusList.value = data.yearend_records || [];

      return data;
    } catch (error) {
      console.error("Error loading year-end bonus data:", error);
      console.error("Error details:", error.response);

      if (error.response?.status === 401) {
        ElMessage.error("Please log in to access year-end bonus data");
      } else if (error.response?.status === 500) {
        ElMessage.error("Server error. Please check if the backend is running");
      } else if (error.code === "NETWORK_ERROR" || !error.response) {
        ElMessage.error(
          "Cannot connect to server. Please check if the backend is running"
        );
      } else {
        ElMessage.error("Failed to load year-end bonus data");
      }
      throw error;
    } finally {
      loading.value = false;
    }
  };

  // Update a single year-end bonus record (bonus_amount, cash_gift_amount)
  const updateYearEndBonusRecord = async (id, data) => {
    try {
      const response = await yearEndBonusApi.updateYearEndBonusRecord(id, data);
      ElMessage.success(
        response.data.message || "Year-end bonus record updated"
      );
      return response.data;
    } catch (error) {
      console.error("Error updating year-end bonus record:", error);
      ElMessage.error(
        error.response?.data?.message || "Failed to update record"
      );
      throw error;
    }
  };

  // Load year-end bonus records filtered by department
  const buildRecordFilters = (filters) => {
    const params = {
      years: filters.years,
      ...divisionParams({
        division_id: filters.department_id ?? filters.division_id,
        department_id: filters.department_id ?? filters.division_id,
      }),
    };
    if (filters.branch_id) {
      params.branch_id = filters.branch_id;
    }
    return params;
  };

  const loadYearEndBonusRecords = async (filters) => {
    try {
      loading.value = true;
      const params = buildRecordFilters(filters);
      console.log("Loading year-end bonus records with filters:", params);
      const response = await yearEndBonusApi.getYearEndBonusRecords(params);
      console.log("Full response:", response);
      const data = response.data.data;
      console.log("Received data:", data);

      yearEndBonusList.value = (data.yearend_records || []).map((row) => ({
        ...row,
        posted: Boolean(row.posted),
        status: row.posted ? "posted" : "draft",
      }));
      console.log("Updated yearEndBonusList:", yearEndBonusList.value);
      return data;
    } catch (error) {
      console.error("Error loading year-end bonus records:", error);
      console.error("Error response:", error.response);
      console.error("Error status:", error.response?.status);
      console.error("Error data:", error.response?.data);

      let errorMessage = "Failed to load year-end bonus records";
      if (error.response?.data?.message) {
        errorMessage = error.response.data.message;
      } else if (error.response?.status === 422) {
        errorMessage = "Validation error: Please check your selections";
      } else if (error.response?.status === 500) {
        errorMessage = "Server error: Please try again later";
      }

      ElMessage.error(errorMessage);
      throw error;
    } finally {
      loading.value = false;
    }
  };

  // Process year-end bonus
  const processYearEndBonus = async (data) => {
    try {
      loading.value = true;
      const response = await yearEndBonusApi.processYearEndBonus(data);
      ElMessage.success(
        response.data.message || "Year-end bonus processed successfully"
      );
      return response.data;
    } catch (error) {
      console.error("Error processing year-end bonus:", error);
      ElMessage.error(
        error.response?.data?.message || "Failed to process year-end bonus"
      );
      throw error;
    } finally {
      loading.value = false;
    }
  };

  // Post year-end bonus
  const postYearEndBonus = async (data) => {
    try {
      loading.value = true;
      const response = await yearEndBonusApi.postYearEndBonus(data);
      ElMessage.success(
        response.data.message || "Year-end bonus posted successfully"
      );
      return response.data;
    } catch (error) {
      console.error("Error posting year-end bonus:", error);
      ElMessage.error(
        error.response?.data?.message || "Failed to post year-end bonus"
      );
      throw error;
    } finally {
      loading.value = false;
    }
  };

  // Load report data
  const loadReportData = async (branchId) => {
    try {
      loading.value = true;
      const response =
        await yearEndBonusApi.getYearEndBonusReportData(branchId);
      const data = response.data.data;

      branches.value = data.branches || [];
      years.value = data.years || [];
      return data;
    } catch (error) {
      console.error("Error loading report data:", error);
      ElMessage.error("Failed to load report data");
      throw error;
    } finally {
      loading.value = false;
    }
  };

  // Generate year-end bonus report
  const generateReport = async (requestData) => {
    try {
      loading.value = true;
      const response =
        await yearEndBonusApi.generateYearEndBonusReport(requestData);

      // Create blob and download
      const blob = new Blob([response.data], { type: "application/pdf" });
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement("a");
      link.href = url;
      link.download = `yearend_bonus_report_${requestData.years}_${
        new Date().toISOString().split("T")[0]
      }.pdf`;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);

      ElMessage.success("Report generated successfully");
      return response.data;
    } catch (error) {
      console.error("Error generating report:", error);
      const msg = formatApiError(
        error,
        "Failed to generate the year-end bonus report.",
      );
      ElMessage.error(msg);
      throw error;
    } finally {
      loading.value = false;
    }
  };

  // Transform backend data to frontend format
  const transformYearEndBonusData = (backendData) => {
    return backendData.map((item) => ({
      id: item.id,
      employee_id: item.employee_id,
      employee_no: item.employee_no,
      name: item.name,
      department: item.department,
      position: item.position,
      salary: item.salary,
      bonus_amount: item.bonus_amount,
      cash_gift_incentive: item.cash_gift_incentive,
      cash_gift_amount: item.cash_gift_amount,
      total_amount: item.total_amount,
      years: item.years,
      branch_id: item.branch_id,
      department_id: item.department_id,
      posted: item.posted || false,
      status: item.posted ? "posted" : "draft",
    }));
  };

  // Reset form data
  const resetFormData = () => {
    formData.value = {
      branch_id: null,
      department_id: null,
      years: new Date().getFullYear(),
    };
    employees.value = [];
  };

  return {
    // State
    loading,
    yearEndBonusList,
    formData,
    branches,
    departments,
    employees,
    years,

    // Computed
    isFormValid,
    hasSelectedEmployees,

    // Methods
    loadYearEndBonusData,
    loadYearEndBonusRecords,
    updateYearEndBonusRecord,
    processYearEndBonus,
    postYearEndBonus,
    loadReportData,
    generateReport,
    transformYearEndBonusData,
    resetFormData,
  };
}
