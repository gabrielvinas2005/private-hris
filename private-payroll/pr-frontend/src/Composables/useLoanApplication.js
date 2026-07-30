import { ref, computed } from "vue";
import { ElMessage } from "element-plus";
import api from "../services/api";

// Main composable for Loan Application management
export function useLoanApplication() {
  const loading = ref(false);
  const rows = ref([]);
  const categories = ref([]);
  const filters = ref({
    employee: "",
    category: "",
    status: "",
  });
  const loanCategories = ref([]);

  const filteredRows = computed(() => {
    let filtered = rows.value;
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    if (filters.value.employee) {
      const query = filters.value.employee.toLowerCase();
      filtered = filtered.filter((row) =>
        row.name?.toLowerCase().includes(query),
      );
    }

    if (filters.value.category) {
      filtered = filtered.filter(
        (row) => String(row.deduction_id) === filters.value.category,
      );
    }

    if (filters.value.status === "active") {
      filtered = filtered.filter((row) => {
        return row.active === true || row.active === 1 || row.active === "1";
      });
    }

    if (filters.value.status === "overdue") {
      filtered = filtered.filter((row) => {
        const balance = Number(row.balance || 0);
        if (balance <= 0) return false;
        if (!row.end_date) return false;
        const endDate = new Date(row.end_date);
        if (Number.isNaN(endDate.getTime())) return false;
        endDate.setHours(0, 0, 0, 0);
        return endDate < today;
      });
    }

    if (filters.value.status === "has_balance") {
      filtered = filtered.filter((row) => Number(row.balance || 0) > 0);
    }

    return filtered;
  });

  const loadList = async () => {
    try {
      loading.value = true;
      const response = await api.get("/loan-applications");

      const responseData = response.data.data || {};
      rows.value = responseData.loans || [];

      const allCategories = [
        ...(responseData.gsis || []),
        ...(responseData.pagibig || []),
        ...(responseData.other || []),
      ];
      loanCategories.value = allCategories;
      categories.value = allCategories;
    } catch (error) {
      console.error("Error loading loan applications:", error);
      ElMessage.error("Failed to load loan applications");
    } finally {
      loading.value = false;
    }
  };

  const loadFormBootstrap = async (id) => {
    try {
      const response = await api.get(`/loan-applications/${id}/add`);
      return response.data.data || {};
    } catch (error) {
      console.error("Error loading form bootstrap:", error);
      ElMessage.error("Failed to load form data");
      return { employees: [], deductions: [], loan_app: [] };
    }
  };

  const loadReconstructBootstrap = async (id) => {
    try {
      const response = await api.get(`/loan-applications/${id}/reconstruct`);
      return response.data.data || {};
    } catch (error) {
      console.error("Error loading reconstruct bootstrap:", error);
      ElMessage.error("Failed to load reconstruct data");
      return { employees: [], deductions: [], loan_app: [] };
    }
  };

  const save = async (payload, id) => {
    try {
      const url = id > 0 ? `/loan-applications/${id}` : "/loan-applications/0";
      const response = await api.post(url, payload);
      ElMessage.success("Loan application saved successfully");
      return response.data;
    } catch (error) {
      console.error("Error saving loan application:", error);
      const errorMessage =
        error.response?.data?.error ||
        error.response?.data?.message ||
        error.message ||
        "Failed to save loan application";
      ElMessage.error(errorMessage);
      throw error;
    }
  };

  const reconstructSave = async (id, payload) => {
    try {
      const response = await api.post(
        `/loan-applications/${id}/reconstruct`,
        payload,
      );
      ElMessage.success("Loan application reconstructed successfully");
      return response.data;
    } catch (error) {
      console.error("Error reconstructing loan application:", error);
      const rawError =
        error.response?.data?.error ||
        error.response?.data?.message ||
        error.message ||
        "Failed to reconstruct loan application";
      const errorMessage = String(rawError).includes(
        "loan_applications_voucher_number_unique",
      )
        ? "Voucher number already exists. Use a new voucher number."
        : rawError;
      ElMessage.error(errorMessage);
      throw error;
    }
  };

  const resetFilters = () => {
    filters.value = {
      employee: "",
      category: "",
      status: "",
    };
  };

  return {
    loading,
    rows,
    categories,
    filters,
    loanCategories,
    filteredRows,
    loadList,
    loadFormBootstrap,
    loadReconstructBootstrap,
    save,
    reconstructSave,
    resetFilters,
  };
}

export function useLoanApplicationTable(props) {
  const searchQuery = ref("");
  const statusFilter = ref("");
  const loanTypeFilter = ref("");
  const showColumnDialog = ref(false);

  const visibleColumns = ref({
    employeeName: true,
    loanType: true,
    voucherNumber: true,
    amount: true,
    amortization: true,
    payment: true,
    balance: true,
    effectivityDate: true,
    endDate: true,
    status: true,
  });

  const totalLoans = computed(() => (props?.rows || []).length);
  const approvedLoans = computed(
    () =>
      (props?.rows || []).filter(
        (row) => row.is_approve === true || row.is_approve === "true",
      ).length,
  );
  const pendingLoans = computed(
    () =>
      (props?.rows || []).filter(
        (row) => row.is_approve === false || row.is_approve === "false",
      ).length,
  );

  const filteredRows = computed(() => {
    let filtered = props?.rows || [];
    if (searchQuery.value) {
      const query = searchQuery.value.toLowerCase();
      filtered = filtered.filter(
        (row) =>
          row.name?.toLowerCase().includes(query) ||
          row.loan?.toLowerCase().includes(query) ||
          row.voucher_number?.toLowerCase().includes(query),
      );
    }
    if (statusFilter.value) {
      filtered = filtered.filter((row) => {
        const isApproved = row.is_approve === true || row.is_approve === "true";
        return statusFilter.value === "approved" ? isApproved : !isApproved;
      });
    }
    if (loanTypeFilter.value) {
      filtered = filtered.filter((row) => row.loan === loanTypeFilter.value);
    }
    return filtered;
  });

  const formatDate = (dateString) => {
    if (!dateString) return "-";
    const date = new Date(dateString);
    return date.toLocaleDateString("en-US", {
      year: "numeric",
      month: "short",
      day: "numeric",
    });
  };

  const formatCurrency = (val) => {
    const n = Number(val || 0);
    return n.toLocaleString(undefined, {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    });
  };

  const getColumnLabel = (key) => {
    const labels = {
      employeeName: "Employee Name",
      loanType: "Loan Type",
      voucherNumber: "Voucher No.",
      amount: "Amount",
      amortization: "Amortization",
      payment: "Payment",
      balance: "Balance",
      effectivityDate: "Effectivity Date",
      endDate: "End Date",
      status: "Status",
    };
    return labels[key] || key;
  };

  const handlePrint = async () => {
    try {
      ElMessage.info("Generating print document...");

      // Use the API service to get the PDF content for printing
      const response = await api.get("/loan-applications/export/pdf", {
        responseType: "blob",
      });

      // Create blob and open in new window for printing
      const blob = new Blob([response.data], { type: "application/pdf" });
      const url = window.URL.createObjectURL(blob);

      // Open PDF in new window and trigger print
      const printWindow = window.open(url, "_blank", "width=1200,height=800");

      // Wait for PDF to load and then trigger print dialog
      printWindow.onload = () => {
        setTimeout(() => {
          printWindow.print();
          // Clean up the URL after printing
          setTimeout(() => {
            window.URL.revokeObjectURL(url);
          }, 1000);
        }, 1000);
      };

      ElMessage.success("Print dialog opened successfully!");
    } catch (error) {
      console.error("Print error:", error);
      ElMessage.error("Failed to generate print document. Please try again.");
    }
  };

  const handleExcel = async () => {
    try {
      ElMessage.info("Generating Excel file...");

      // Use the API service to download the Excel file
      const response = await api.get("/loan-applications/export/excel", {
        responseType: "blob",
      });

      // Create blob and download
      const blob = new Blob([response.data], {
        type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
      });
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement("a");
      link.href = url;
      link.download = `loan_applications_${
        new Date().toISOString().split("T")[0]
      }.xlsx`;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);

      ElMessage.success("Excel file downloaded successfully!");
    } catch (error) {
      console.error("Excel export error:", error);
      ElMessage.error("Failed to export Excel file. Please try again.");
    }
  };

  const handlePDF = async () => {
    try {
      ElMessage.info("Generating PDF file...");

      // Use the API service to download the PDF file
      const response = await api.get("/loan-applications/export/pdf", {
        responseType: "blob",
      });

      // Create blob and download
      const blob = new Blob([response.data], { type: "application/pdf" });
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement("a");
      link.href = url;
      link.download = `loan_applications_${
        new Date().toISOString().split("T")[0]
      }.pdf`;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);

      ElMessage.success("PDF file downloaded successfully!");
    } catch (error) {
      console.error("PDF export error:", error);
      ElMessage.error("Failed to export PDF file. Please try again.");
    }
  };

  const toggleColumnVisibility = () => {
    showColumnDialog.value = true;
  };

  return {
    searchQuery,
    statusFilter,
    loanTypeFilter,
    showColumnDialog,
    visibleColumns,
    totalLoans,
    approvedLoans,
    pendingLoans,
    filteredRows,
    formatDate,
    formatCurrency,
    getColumnLabel,
    handlePrint,
    handleExcel,
    handlePDF,
    toggleColumnVisibility,
  };
}

// Default export
export default useLoanApplication;
