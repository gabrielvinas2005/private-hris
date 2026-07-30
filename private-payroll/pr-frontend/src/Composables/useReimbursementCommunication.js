import { ref, computed } from "vue";
import { reimbursementCommunicationApi } from "../services/api.js";
import { divisionsFromApi } from "../utils/payrollReportDivisions.js";
import { ElMessage } from "element-plus";

export function useReimbursementCommunication() {
  const loading = ref(false);
  const reimbursementList = ref([]);
  const formData = ref({});
  const divisions = ref([]);
  const employees = ref([]);
  const selectedEmployees = ref([]);
  const months = ref([]);

  // Ensure reimbursementList is always an array
  if (!Array.isArray(reimbursementList.value)) {
    reimbursementList.value = [];
  }

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

  // Load reimbursement communication expenses list
  const loadReimbursementList = async () => {
    try {
      loading.value = true;
      const response =
        await reimbursementCommunicationApi.getReimbursementCommunicationList();

      // Check if the response has the expected structure
      if (response.data && response.data.data) {
        // If data is nested under 'data'
        reimbursementList.value = response.data.data || [];
      } else if (Array.isArray(response.data)) {
        // If data is directly an array
        reimbursementList.value = response.data || [];
      } else {
        // If data is an object with other structure
        console.warn("Unexpected response structure:", response.data);
        reimbursementList.value = [];
      }

      return response.data;
    } catch (error) {
      console.error(
        "Error loading reimbursement communication expenses list:",
        error,
      );
      ElMessage.error(
        "Failed to load reimbursement communication expenses list",
      );
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
        await reimbursementCommunicationApi.getReimbursementCommunicationFormData(
          id,
          options,
        );

      // Backends in this app commonly wrap payload in { data: { ... } }
      // Normalize so we can read consistently like other modules (e.g., Loyalty Award)
      const payload = response.data?.data ?? response.data ?? {};
      const data = payload.data ?? [];

      if (Array.isArray(data) && data.length > 0) {
        formData.value = {
          id: data[0].id || 0,
          division_id: data[0].department_id || null,
          month_id: data[0].month_id || null,
          year: data[0].year || null,
          posted: data[0].posted || false,
        };

        // Set employee details if editing
        if (id > 0) {
          selectedEmployees.value = data.map((emp) => ({
            id: emp.reimbursement_dtl,
            employee_id: emp.employee_id,
            full_name: emp.full_name,
            position: emp.position,
            prepaid_invoice_no: emp.prepaid_invoice_no || 0,
            prepaid_amount: Number(emp.prepaid_amount) || 0,
            postpaid_invoice_no: emp.postpaid_invoice_no || 0,
            postpaid_amount: Number(emp.postpaid_amount) || 0,
          }));
        }
      } else {
        // Initialize form data for new record
        formData.value = {
          id: 0,
          division_id: null,
          month_id: null,
          year: new Date().getFullYear(),
          posted: false,
        };
        selectedEmployees.value = [];
      }

      divisions.value = divisionsFromApi(payload) || [];
      employees.value = payload.employees || [];

      if (months.value.length === 0) {
        await loadMonthsData();
      }

      // Ensure dropdowns are reactive and not empty on first open
      if (!Array.isArray(divisions.value)) divisions.value = [];
      if (!Array.isArray(employees.value)) employees.value = [];

      return response.data;
    } catch (error) {
      console.error("Error loading form data:", error);
      ElMessage.error("Failed to load form data");
      throw error;
    } finally {
      loading.value = false;
    }
  };

  // Save reimbursement communication expenses
  const saveReimbursement = async (id, data) => {
    try {
      loading.value = true;
      const response =
        await reimbursementCommunicationApi.saveReimbursementCommunication(
          id,
          data,
        );
      ElMessage.success(
        response.data.message ||
          "Reimbursement communication expenses saved successfully",
      );
      return response.data;
    } catch (error) {
      // Prefer showing backend message (e.g., duplicate header) like other modules
      const backendMessage =
        error?.response?.data?.message ||
        error?.response?.data?.errors ||
        error?.message;
      console.error(
        "Error saving reimbursement communication expenses:",
        backendMessage,
      );
      ElMessage.error(
        typeof backendMessage === "string"
          ? backendMessage
          : "Failed to save reimbursement communication expenses",
      );
      throw error;
    } finally {
      loading.value = false;
    }
  };

  // Add employees to reimbursement communication expenses
  const addEmployees = async (id, employeeData) => {
    try {
      loading.value = true;
      const response =
        await reimbursementCommunicationApi.addEmployeesToReimbursementCommunication(
          id,
          employeeData,
        );
      ElMessage.success(
        response.data.message || "Employees added successfully",
      );
      return response.data;
    } catch (error) {
      console.error("Error adding employees:", error);
      ElMessage.error("Failed to add employees");
      throw error;
    } finally {
      loading.value = false;
    }
  };

  // Remove employee from reimbursement communication expenses
  const removeEmployee = async (id) => {
    try {
      loading.value = true;
      const response =
        await reimbursementCommunicationApi.removeEmployeeFromReimbursementCommunication(
          id,
        );
      ElMessage.success(
        response.data.message || "Employee removed successfully",
      );
      return response.data;
    } catch (error) {
      console.error("Error removing employee:", error);
      ElMessage.error("Failed to remove employee");
      throw error;
    } finally {
      loading.value = false;
    }
  };

  // Process reimbursement communication expenses (post/unpost)
  const processReimbursement = async (id, typeId, data) => {
    try {
      loading.value = true;

      const response =
        await reimbursementCommunicationApi.processReimbursementCommunication(
          id,
          typeId,
          data,
        );

      const message =
        typeId === 1 ? "Posted successfully" : "Unposted successfully";
      ElMessage.success(response.data.message || message);
      return response.data;
    } catch (error) {
      const backendMessage =
        error?.response?.data?.message ||
        error?.response?.data?.errors ||
        error?.message;
      console.error("Error processing reimbursement:", backendMessage);
      ElMessage.error(
        typeof backendMessage === "string"
          ? backendMessage
          : "Failed to process reimbursement",
      );
      throw error;
    } finally {
      loading.value = false;
    }
  };

  // Delete reimbursement communication (header and details)
  const deleteReimbursement = async (id) => {
    try {
      loading.value = true;
      const response =
        await reimbursementCommunicationApi.deleteReimbursementCommunication(
          id,
        );
      ElMessage.success(response.data?.message || "Deleted successfully");
      return response.data;
    } catch (error) {
      const backendMessage =
        error?.response?.data?.message ||
        error?.response?.data?.errors ||
        error?.message;
      console.error("Error deleting reimbursement:", backendMessage);
      ElMessage.error(
        typeof backendMessage === "string"
          ? backendMessage
          : "Failed to delete",
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
        await reimbursementCommunicationApi.getReimbursementCommunicationReportData();
      return response.data;
    } catch (error) {
      console.error("Error loading report data:", error);
      ElMessage.error("Failed to load report data");
      throw error;
    } finally {
      loading.value = false;
    }
  };

  // Generate PDF report
  const generateReport = async (requestData) => {
    try {
      loading.value = true;
      const response =
        await reimbursementCommunicationApi.generateReimbursementCommunicationReport(
          requestData,
        );

      // Create blob and download
      const blob = new Blob([response.data], { type: "application/pdf" });
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement("a");
      link.href = url;
      link.download = `reimbursement_communication_expenses_${requestData.division_id ?? requestData.department_id}_${requestData.month_id}_${requestData.year}_${new Date().toISOString().split("T")[0]}.pdf`;
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

  // Transform data for display
  const transformReimbursementData = (data) => {
    // Ensure data is an array before calling map
    if (!Array.isArray(data)) {
      console.warn("transformReimbursementData: data is not an array:", data);
      return [];
    }

    return data.map((item) => {
      // Normalize boolean posted like other modules (e.g., Loyalty Award)
      const isPosted =
        item.posted === true ||
        item.posted === 1 ||
        item.posted === "1" ||
        item.posted === "true";

      const totalAmount = Number(item.total_amount ?? 0);

      const divisionName = item.division ?? item.department ?? "";

      return {
        ...item,
        division: divisionName,
        department: divisionName,
        posted: isPosted,
        status: isPosted ? "Posted" : "Draft",
        statusType: isPosted ? "success" : "warning",
        totalAmount,
      };
    });
  };

  // Load months data
  const loadMonthsData = async () => {
    try {
      // You can add an API call here if months are loaded from backend
      // For now, we'll use static months data
      months.value = [
        { id: 1, name: "January" },
        { id: 2, name: "February" },
        { id: 3, name: "March" },
        { id: 4, name: "April" },
        { id: 5, name: "May" },
        { id: 6, name: "June" },
        { id: 7, name: "July" },
        { id: 8, name: "August" },
        { id: 9, name: "September" },
        { id: 10, name: "October" },
        { id: 11, name: "November" },
        { id: 12, name: "December" },
      ];
    } catch (error) {
      console.error("Error loading months data:", error);
    }
  };

  // Reset form data
  const resetFormData = () => {
    formData.value = {
      id: 0,
      division_id: null,
      month_id: null,
      year: new Date().getFullYear(),
      posted: false,
    };
    selectedEmployees.value = [];
  };

  return {
    // State
    loading,
    reimbursementList,
    formData,
    divisions,
    employees,
    selectedEmployees,
    months,

    // Computed
    isFormValid,
    hasSelectedEmployees,

    // Methods
    loadReimbursementList,
    loadFormData,
    saveReimbursement,
    addEmployees,
    removeEmployee,
    processReimbursement,
    deleteReimbursement,
    loadReportData,
    generateReport,
    transformReimbursementData,
    resetFormData,
    loadMonthsData,
  };
}
