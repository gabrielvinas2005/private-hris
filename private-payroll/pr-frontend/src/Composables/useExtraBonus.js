import { ref, computed } from "vue";
import { extraBonusApi } from "../services/api.js";
import { divisionsFromApi } from "../utils/payrollReportDivisions.js";
import { ElMessage } from "element-plus";

export function useExtraBonus() {
  const loading = ref(false);
  const extraBonusList = ref([]);
  const formData = ref({});
  const extraBonusTypes = ref([]);
  const departments = ref([]);
  const employees = ref([]);
  const years = ref([]);

  // Computed properties
  const isFormValid = computed(() => {
    return (
      formData.value.extra_bonus_type_id &&
      formData.value.department_id &&
      formData.value.year_id
    );
  });

  const hasSelectedEmployees = computed(() => {
    return employees.value && employees.value.length > 0;
  });

  // Load extra bonus data
  const loadExtraBonusData = async () => {
    try {
      loading.value = true;
      const response = await extraBonusApi.getExtraBonusData();
      const data = response.data.data;

      // The backend returns the list directly, not nested in extra_bonus_payrolls
      extraBonusList.value = data || [];

      return data;
    } catch (error) {
      console.error("Error loading extra bonus data:", error);
      console.error("Error details:", error.response);

      if (error.response?.status === 401) {
        ElMessage.error("Please log in to access extra bonus data");
      } else if (error.response?.status === 500) {
        ElMessage.error("Server error. Please check if the backend is running");
      } else if (error.code === "NETWORK_ERROR" || !error.response) {
        ElMessage.error(
          "Cannot connect to server. Please check if the backend is running",
        );
      } else {
        ElMessage.error("Failed to load extra bonus data");
      }
      throw error;
    } finally {
      loading.value = false;
    }
  };

  // Load extra bonus form data for editing
  const loadExtraBonusFormData = async (extraBonusId) => {
    try {
      loading.value = true;
      const response = await extraBonusApi.getExtraBonusFormData(extraBonusId);
      const data = response.data.data;

      extraBonusTypes.value = data.extra_bonuses || [];
      departments.value = divisionsFromApi(data) || [];
      employees.value = data.employees || [];

      // Set form data for editing
      if (data.extra_bonus_payrolls && data.extra_bonus_payrolls.length > 0) {
        const payroll = data.extra_bonus_payrolls[0];
        formData.value = {
          extra_bonus_id: payroll.id,
          extra_bonus_type_id: payroll.extra_bonus_type_id,
          department_id: payroll.department_id,
          year_id: payroll.year_id,
          is_posted: payroll.is_posted,
        };
      }

      return data;
    } catch (error) {
      console.error("Error loading extra bonus form data:", error);
      ElMessage.error("Failed to load extra bonus form data");
      throw error;
    } finally {
      loading.value = false;
    }
  };

  // Load employees for extra bonus
  const loadEmployees = async (extraBonusTypeId, departmentId, yearId) => {
    try {
      loading.value = true;
      const response = await extraBonusApi.loadEmployees(
        extraBonusTypeId,
        departmentId,
        yearId,
      );
      const data = response.data.data;

      employees.value = data || [];
      return data;
    } catch (error) {
      console.error("Error loading employees:", error);
      ElMessage.error("Failed to load employees");
      throw error;
    } finally {
      loading.value = false;
    }
  };

  // Save extra bonus payroll
  const saveExtraBonusPayroll = async (data) => {
    try {
      loading.value = true;
      const response = await extraBonusApi.saveExtraBonusPayroll(data);
      ElMessage.success(
        response.data.message || "Extra bonus payroll saved successfully",
      );
      return response.data;
    } catch (error) {
      console.error("Error saving extra bonus payroll:", error);
      ElMessage.error(
        error.response?.data?.message || "Failed to save extra bonus payroll",
      );
      throw error;
    } finally {
      loading.value = false;
    }
  };

  // Delete extra bonus payroll (draft only)
  const deleteExtraBonusPayroll = async (extraBonusId) => {
    try {
      loading.value = true;
      const response = await extraBonusApi.deleteExtraBonusPayroll(extraBonusId);
      ElMessage.success(
        response.data?.message || "Extra bonus payroll deleted successfully",
      );
      return response.data;
    } catch (error) {
      ElMessage.error(
        error.response?.data?.message || "Failed to delete extra bonus payroll",
      );
      throw error;
    } finally {
      loading.value = false;
    }
  };

  // Process extra bonus (post/unpost)
  const processExtraBonus = async (extraBonusId, typeId) => {
    try {
      const response = await extraBonusApi.processExtraBonus(
        extraBonusId,
        typeId,
      );
      ElMessage.success(
        response.data.message || "Extra bonus processed successfully",
      );
      return response.data;
    } catch (error) {
      console.error("Error processing extra bonus:", error);
      ElMessage.error(
        error.response?.data?.message || "Failed to process extra bonus",
      );
      throw error;
    }
  };

  // Bulk post extra bonus payroll records
  const bulkPost = async (records) => {
    try {
      if (!records || records.length === 0) {
        ElMessage.warning("No records selected for bulk post");
        return;
      }

      loading.value = true;
      const response = await extraBonusApi.bulkPost(records);
      ElMessage.success(
        response.data.message || "Bulk post completed successfully",
      );
      return response.data;
    } catch (error) {
      console.error("Error bulk posting extra bonus:", error);
      ElMessage.error(
        error.response?.data?.message || "Failed to bulk post extra bonus",
      );
      throw error;
    } finally {
      loading.value = false;
    }
  };

  // Load dropdown data (extra bonus types and departments)
  const loadDropdownData = async () => {
    try {
      loading.value = true;
      const response = await extraBonusApi.getReportData();
      const data = response.data.data;

      extraBonusTypes.value = data.extra_bonuses || [];
      departments.value = divisionsFromApi(data) || [];
      return data;
    } catch (error) {
      console.error("Error loading dropdown data:", error);
      ElMessage.error("Failed to load dropdown data");
      throw error;
    } finally {
      loading.value = false;
    }
  };

  // Load report data
  const loadReportData = async () => {
    try {
      loading.value = true;
      const response = await extraBonusApi.getReportData();
      const data = response.data.data;

      extraBonusTypes.value = data.extra_bonuses || [];
      departments.value = data.departments || [];
      return data;
    } catch (error) {
      console.error("Error loading report data:", error);
      ElMessage.error("Failed to load report data");
      throw error;
    } finally {
      loading.value = false;
    }
  };

  // Generate extra bonus report
  const generateReport = async (requestData) => {
    try {
      loading.value = true;
      const response = await extraBonusApi.generateReport(requestData);

      // Create blob and download
      const blob = new Blob([response.data], { type: "application/pdf" });
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement("a");
      link.href = url;
      link.download = `extra_bonus_report_${requestData.extra_bonus_type_id}_${requestData.department_id}_${requestData.year_id}_${
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
  const transformExtraBonusData = (backendData) => {
    if (!backendData || !Array.isArray(backendData)) {
      return [];
    }

    return backendData.map((item) => {
      // Convert string '0'/'1' to boolean properly
      const isPosted =
        item.is_posted === "1" ||
        item.is_posted === 1 ||
        item.is_posted === true;

      return {
        id: item.id,
        extra_bonus_type: item.extra_bonus_type,
        department: item.department,
        year_id: item.year_id,
        is_posted: isPosted,
        status: isPosted ? "posted" : "draft",
      };
    });
  };

  // Reset form data
  const resetFormData = () => {
    formData.value = {
      extra_bonus_id: 0,
      extra_bonus_type_id: null,
      department_id: null,
      year_id: new Date().getFullYear(),
      is_posted: false,
    };
    employees.value = [];
  };

  return {
    // State
    loading,
    extraBonusList,
    formData,
    extraBonusTypes,
    departments,
    employees,
    years,

    // Computed
    isFormValid,
    hasSelectedEmployees,

    // Methods
    loadExtraBonusData,
    loadExtraBonusFormData,
    loadEmployees,
    saveExtraBonusPayroll,
    deleteExtraBonusPayroll,
    processExtraBonus,
    bulkPost,
    loadDropdownData,
    loadReportData,
    generateReport,
    transformExtraBonusData,
    resetFormData,
  };
}
