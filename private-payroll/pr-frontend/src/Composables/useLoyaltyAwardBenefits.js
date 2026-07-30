import { ref, computed } from "vue";
import { loyaltyAwardApi } from "../services/api.js";
import { ElMessage } from "element-plus";

export function useLoyaltyAward() {
  const loading = ref(false);
  const loyaltyAwardList = ref([]);
  const filteredLoyaltyAwardData = ref([]);
  const formData = ref({});
  const branches = ref([]);
  const months = ref([]);
  const employees = ref([]);
  const loyaltyAwardSetup = ref([]);
  const maxYear = ref(0);
  const minYear = ref(0);

  // Search and filter
  const searchQuery = ref("");
  const statusFilter = ref("");

  // Computed properties
  const isFormValid = computed(() => {
    return formData.value.year && formData.value.month_id;
  });

  const hasSelectedEmployees = computed(() => {
    return employees.value && employees.value.length > 0;
  });

  // Load loyalty award list
  const loadLoyaltyAwardList = async () => {
    try {
      loading.value = true;
      const response = await loyaltyAwardApi.getLoyaltyAwardList();
      loyaltyAwardList.value = response.data.data || [];

      filteredLoyaltyAwardData.value = transformLoyaltyAwardData(
        loyaltyAwardList.value,
      );

      return response.data;
    } catch (error) {
      console.error("Error loading loyalty award list:", error);
      console.error("Error details:", error.response);

      // Check if it's an authentication error
      if (error.response?.status === 401) {
        ElMessage.error("Please log in to access loyalty award data");
      } else if (error.response?.status === 500) {
        ElMessage.error("Server error. Please check if the backend is running");
      } else if (error.code === "NETWORK_ERROR" || !error.response) {
        ElMessage.error(
          "Cannot connect to server. Please check if the backend is running",
        );
      } else {
        ElMessage.error("Failed to load loyalty award list");
      }
      throw error;
    } finally {
      loading.value = false;
    }
  };

  // Load form data for add/edit
  const loadFormData = async (id = 0) => {
    try {
      loading.value = true;
      const response = await loyaltyAwardApi.getLoyaltyAwardFormData(id);
      const data = response.data.data;

      // For new records (id = 0), initialize with empty values
      if (id === 0) {
        const currentYear = new Date().getFullYear();
        formData.value = {
          id: 0,
          month_id: null,
          year: currentYear, // Auto-select current year
          posted: false,
        };
      } else {
        formData.value = data.data[0] || {};
      }

      branches.value = data.branch || [];
      months.value = data.months || [];

      const raw = data.employees || [];
      employees.value = raw.map((emp) => ({
        ...emp,
        loyaltyAwardSetupId:
          emp.loyalty_award_setup_id != null
            ? Number(emp.loyalty_award_setup_id)
            : emp.loyaltyAwardSetupId != null
              ? Number(emp.loyaltyAwardSetupId)
              : null,
      }));

      loyaltyAwardSetup.value = data.loyalty_award_setup || [];
      maxYear.value = data.max_year || 0;
      minYear.value = data.min_year || 0;

      return data;
    } catch (error) {
      console.error("Error loading form data:", error);
      console.error("Error details:", error.response);

      // Check if it's an authentication error
      if (error.response?.status === 401) {
        ElMessage.error("Please log in to access loyalty award data");
      } else if (error.response?.status === 500) {
        ElMessage.error("Server error. Please check if the backend is running");
      } else if (error.code === "NETWORK_ERROR" || !error.response) {
        ElMessage.error(
          "Cannot connect to server. Please check if the backend is running",
        );
      } else {
        ElMessage.error("Failed to load form data");
      }
      throw error;
    } finally {
      loading.value = false;
    }
  };

  // Load employees for a specific month, branch, and year
  const loadEmployeesForAward = async (monthId, branchId, year, payrollId = 0) => {
    try {
      loading.value = true;
      const response = await loyaltyAwardApi.getLoyaltyAwardEmployees(
        monthId,
        branchId ?? 0,
        year,
        payrollId,
      );
      const data = response.data.data;

      const raw = data.employees || [];
      employees.value = raw.map((emp) => ({
        ...emp,
        loyaltyAwardSetupId:
          emp.loyalty_award_setup_id != null
            ? Number(emp.loyalty_award_setup_id)
            : emp.loyaltyAwardSetupId != null
              ? Number(emp.loyaltyAwardSetupId)
              : null,
      }));
      maxYear.value = data.max_year || 0;
      minYear.value = data.min_year || 0;

      return data;
    } catch (error) {
      console.error("Error loading employees for award:", error);
      ElMessage.error("Failed to load employees for award");
      throw error;
    } finally {
      loading.value = false;
    }
  };

  // Save loyalty award
  const saveLoyaltyAward = async (id, data) => {
    try {
      loading.value = true;
      const response = await loyaltyAwardApi.saveLoyaltyAward(id, data);
      ElMessage.success(
        response.data.message || "Loyalty award saved successfully",
      );
      return response.data;
    } catch (error) {
      console.error("Error saving loyalty award:", error);
      const firstValidationError = Object.values(
        error.response?.data?.errors || {},
      )?.[0]?.[0];
      ElMessage.error(
        firstValidationError ||
          error.response?.data?.message ||
          "Failed to save loyalty award",
      );
      throw error;
    } finally {
      loading.value = false;
    }
  };

  // Post loyalty award
  const postLoyaltyAward = async (id) => {
    try {
      loading.value = true;
      const response = await loyaltyAwardApi.postLoyaltyAward(id);
      ElMessage.success(
        response.data.message || "Loyalty award posted successfully",
      );
      return response.data;
    } catch (error) {
      console.error("Error posting loyalty award:", error);
      ElMessage.error(
        error.response?.data?.message || "Failed to post loyalty award",
      );
      throw error;
    } finally {
      loading.value = false;
    }
  };

  // Unpost loyalty award
  const unpostLoyaltyAward = async (id) => {
    try {
      loading.value = true;
      const response = await loyaltyAwardApi.unpostLoyaltyAward(id);
      ElMessage.success(
        response.data.message || "Loyalty award unposted successfully",
      );
      return response.data;
    } catch (error) {
      console.error("Error unposting loyalty award:", error);
      ElMessage.error(
        error.response?.data?.message || "Failed to unpost loyalty award",
      );
      throw error;
    } finally {
      loading.value = false;
    }
  };

  // Delete loyalty award
  const deleteLoyaltyAward = async (id) => {
    try {
      loading.value = true;
      const response = await loyaltyAwardApi.deleteLoyaltyAward(id);
      ElMessage.success(
        response.data.message || "Loyalty award deleted successfully",
      );
      return response.data;
    } catch (error) {
      console.error("Error deleting loyalty award:", error);
      ElMessage.error(
        error.response?.data?.message || "Failed to delete loyalty award",
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
      const response = await loyaltyAwardApi.getLoyaltyAwardReportData();
      const data = response.data.data;

      branches.value = data.branches || [];
      // PayrollPeriodType is for report periods, but loyalty award uses months/years
      return data;
    } catch (error) {
      console.error("Error loading report data:", error);
      ElMessage.error("Failed to load report data");
      throw error;
    } finally {
      loading.value = false;
    }
  };

  // Generate loyalty award report
  const generateReport = async (requestData) => {
    try {
      loading.value = true;
      const response =
        await loyaltyAwardApi.generateLoyaltyAwardReport(requestData);

      // Create blob and download
      const blob = new Blob([response.data], { type: "application/pdf" });
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement("a");
      link.href = url;
      link.download = `loyalty_award_report_${new Date().toISOString().split("T")[0]}.pdf`;
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
  const transformLoyaltyAwardData = (backendData) => {
    return backendData.map((item) => {
      // Ensure posted is properly converted to boolean
      const isPosted = Boolean(
        item.posted && item.posted !== 0 && item.posted !== "0",
      );

      return {
        id: item.id,
        branch: item.branch,
        month: item.month,
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
      employeeId: item.id,
      employeeNo: item.employee_no,
      name: item.name,
      position: item.position,
      department: item.department,
      branch: item.branch,
      employmentType: item.employment_type,
      years: item.years,
      loyaltyAwardSetupId: item.loyalty_award_setup_id,
      cashAward: item.cash_award,
      photo: item.photo,
      email: item.email,
    }));
  };

  // Reset form data
  const resetFormData = () => {
    formData.value = {
      id: 0,
      month_id: null,
      year: new Date().getFullYear(),
      posted: false,
      active: true,
    };
    employees.value = [];
  };

  // Search functionality
  const handleSearch = () => {
    if (!searchQuery.value) {
      filteredLoyaltyAwardData.value = transformLoyaltyAwardData(
        loyaltyAwardList.value,
      );
      return;
    }

    const query = searchQuery.value.toLowerCase();
    const filtered = loyaltyAwardList.value.filter(
      (item) =>
        item.branch?.toLowerCase().includes(query) ||
        item.month?.toLowerCase().includes(query) ||
        item.year?.toString().includes(query),
    );
    filteredLoyaltyAwardData.value = transformLoyaltyAwardData(filtered);
  };

  // Filter functionality
  const handleFilter = () => {
    let filtered = loyaltyAwardList.value;

    if (statusFilter.value) {
      const isPosted = statusFilter.value === "posted";
      filtered = filtered.filter((item) => item.posted === isPosted);
    }

    if (searchQuery.value) {
      const query = searchQuery.value.toLowerCase();
      filtered = filtered.filter(
        (item) =>
          item.branch?.toLowerCase().includes(query) ||
          item.month?.toLowerCase().includes(query) ||
          item.year?.toString().includes(query),
      );
    }

    filteredLoyaltyAwardData.value = transformLoyaltyAwardData(filtered);
  };

  // Reset form (alias for resetFormData)
  const resetForm = resetFormData;

  return {
    // State
    loading,
    loyaltyAwardList,
    filteredLoyaltyAwardData,
    formData,
    branches,
    months,
    employees,
    loyaltyAwardSetup,
    maxYear,
    minYear,
    searchQuery,
    statusFilter,

    // Computed
    isFormValid,
    hasSelectedEmployees,

    // Methods
    loadLoyaltyAwardList,
    loadFormData,
    loadEmployeesForAward,
    saveLoyaltyAward,
    postLoyaltyAward,
    unpostLoyaltyAward,
    deleteLoyaltyAward,
    loadReportData,
    generateReport,
    transformLoyaltyAwardData,
    transformEmployeeData,
    resetFormData,
    handleSearch,
    handleFilter,
    resetForm,
  };
}
