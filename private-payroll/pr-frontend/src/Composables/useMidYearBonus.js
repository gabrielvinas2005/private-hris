import { ref, computed } from "vue";
import { midYearBonusApi } from "../services/api.js";
import { divisionParams, divisionsFromApi } from "../utils/payrollReportDivisions.js";
import { ElMessage } from "element-plus";

export function useMidYearBonus() {
  const loading = ref(false);
  const midYearBonusList = ref([]);
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

  // Load mid-year bonus data
  const loadMidYearBonusData = async () => {
    try {
      loading.value = true;
      const response = await midYearBonusApi.getMidYearBonusData();
      const data = response.data.data;

      branches.value = (data.branches || []).filter(
        (branch) => String(branch.id) !== "0"
      );
      departments.value = (divisionsFromApi(data) || []).filter(
        (department) => String(department.id) !== "0"
      );
      midYearBonusList.value = data.midyear_records || [];

      return data;
    } catch (error) {
      console.error("Error loading mid-year bonus data:", error);
      console.error("Error details:", error.response);

      if (error.response?.status === 401) {
        ElMessage.error("Please log in to access mid-year bonus data");
      } else if (error.response?.status === 500) {
        ElMessage.error("Server error. Please check if the backend is running");
      } else if (error.code === "NETWORK_ERROR" || !error.response) {
        ElMessage.error(
          "Cannot connect to server. Please check if the backend is running"
        );
      } else {
        ElMessage.error("Failed to load mid-year bonus data");
      }
      throw error;
    } finally {
      loading.value = false;
    }
  };

  // Update a single mid-year bonus record (bonus_amount)
  const updateMidYearBonusRecord = async (id, data) => {
    try {
      const response = await midYearBonusApi.updateMidYearBonusRecord(id, data);
      ElMessage.success(
        response.data.message || "Mid-year bonus record updated",
      );
      return response.data;
    } catch (error) {
      console.error("Error updating mid-year bonus record:", error);
      ElMessage.error(
        error.response?.data?.message || "Failed to update record",
      );
      throw error;
    }
  };

  // Load mid-year bonus records filtered by department
  const loadMidYearBonusRecords = async (filters) => {
    try {
      loading.value = true;
      const params =
        filters?.department_id === "all"
          ? { department_id: "all", years: filters.years }
          : {
              ...filters,
              ...divisionParams({
                division_id: filters?.division_id ?? filters?.department_id,
                department_id: filters?.department_id,
              }),
            };
      const response = await midYearBonusApi.getMidYearBonusRecords(params);
      const data = response.data.data;

      midYearBonusList.value = data.midyear_records || [];
      return data;
    } catch (error) {
      console.error("Error loading mid-year bonus records:", error);
      ElMessage.error("Failed to load mid-year bonus records");
      throw error;
    } finally {
      loading.value = false;
    }
  };

  // Delete a single mid-year bonus record
  const deleteMidYearBonusRecord = async (id) => {
    try {
      loading.value = true;
      const response = await midYearBonusApi.deleteMidYearBonusRecord(id);
      ElMessage.success(
        response.data.message || "Mid-year bonus record deleted successfully",
      );
      return response.data;
    } catch (error) {
      console.error("Error deleting mid-year bonus record:", error);
      ElMessage.error(
        error.response?.data?.message ||
          "Failed to delete mid-year bonus record",
      );
      throw error;
    } finally {
      loading.value = false;
    }
  };

  // Process mid-year bonus
  const processMidYearBonus = async (data) => {
    try {
      loading.value = true;
      const payload =
        data?.department_id === "all"
          ? data
          : { ...data, ...divisionParams({
              division_id: data?.division_id ?? data?.department_id,
              department_id: data?.department_id,
            }) };
      const response = await midYearBonusApi.processMidYearBonus(payload);
      ElMessage.success(
        response.data.message || "Mid-year bonus processed successfully"
      );
      return response.data;
    } catch (error) {
      console.error("Error processing mid-year bonus:", error);
      ElMessage.error(
        error.response?.data?.message || "Failed to process mid-year bonus"
      );
      throw error;
    } finally {
      loading.value = false;
    }
  };

  // Post mid-year bonus
  const postMidYearBonus = async (data) => {
    try {
      loading.value = true;
      const response = await midYearBonusApi.postMidYearBonus(data);
      ElMessage.success(
        response.data.message || "Mid-year bonus posted successfully"
      );
      return response.data;
    } catch (error) {
      console.error("Error posting mid-year bonus:", error);
      ElMessage.error(
        error.response?.data?.message || "Failed to post mid-year bonus"
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
        await midYearBonusApi.getMidYearBonusReportData(branchId);
      const data = response.data.data;

      branches.value = (data.branches || []).filter(
        (branch) => String(branch.id) !== "0"
      );
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

  // Generate mid-year bonus report
  const generateReport = async (requestData) => {
    try {
      loading.value = true;
      const response =
        await midYearBonusApi.generateMidYearBonusReport(requestData);

      // Create blob and download
      const blob = new Blob([response.data], { type: "application/pdf" });
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement("a");
      link.href = url;
      link.download = `midyear_bonus_report_${requestData.years}_${
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
      ElMessage.error("Failed to generate report");
      throw error;
    } finally {
      loading.value = false;
    }
  };

  // Transform backend data to frontend format
  const transformMidYearBonusData = (backendData) => {
    return backendData.map((item) => ({
      id: item.id,
      employee_id: item.employee_id,
      employee_no: item.employee_no,
      name: item.name,
      department: item.department,
      position: item.position,
      salary: item.salary,
      bonus_amount: item.bonus_amount,
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
    midYearBonusList,
    formData,
    branches,
    departments,
    employees,
    years,

    // Computed
    isFormValid,
    hasSelectedEmployees,

    // Methods
    loadMidYearBonusData,
    loadMidYearBonusRecords,
    updateMidYearBonusRecord,
    deleteMidYearBonusRecord,
    processMidYearBonus,
    postMidYearBonus,
    loadReportData,
    generateReport,
    transformMidYearBonusData,
    resetFormData,
  };
}
