import { ref, reactive } from "vue";
import { gsisMemberInfoApi } from "../services/api.js";
import { ElMessage } from "element-plus";

export function useGSISMemberInfo() {
  const loading = ref(false);
  const error = ref(null);

  const formData = reactive({
    employee_id: "",
  });

  const employees = ref([]);

  // Print preview state management
  const uiState = reactive({
    showPrintModal: false,
    previewUrl: "",
    selectedEmployee: null,
  });

  const loadInitialData = async () => {
    try {
      loading.value = true;
      error.value = null;
      const res = await gsisMemberInfoApi.getGSISMemberInfoData();
      const data = res.data.data || {};
      employees.value = data.employees || [];
      return data;
    } catch (err) {
      error.value = err.response?.data?.message || "Failed to load data";
      ElMessage.error(error.value);
      throw err;
    } finally {
      loading.value = false;
    }
  };

  // Load employee details (for future use if needed)
  const loadEmployeeDetails = async (employeeId) => {
    if (!employeeId) {
      return;
    }

    try {
      loading.value = true;
      error.value = null;
      const res = await gsisMemberInfoApi.getEmployeeDetails(employeeId);
      const data = res.data.data || {};
      ElMessage.success("Employee details loaded successfully");
      return data;
    } catch (err) {
      error.value = err.response?.data?.message || "Failed to load employee details";
      ElMessage.error(error.value);
      throw err;
    } finally {
      loading.value = false;
    }
  };

  // Preview report (shows inline preview)
  const previewReport = async () => {
    try {
      loading.value = true;
      error.value = null;

      if (!formData.employee_id) {
        throw new Error("Employee is required");
      }

      // Set selected employee and show modal
      const selectedEmp = employees.value.find((e) => e.id === formData.employee_id);
      uiState.selectedEmployee = selectedEmp;
      uiState.showPrintModal = true;

      // Fetch PDF and create blob URL
      const payload = { ...formData };
      const response = await gsisMemberInfoApi.printGSISMemberInfo(payload);
      const blob = response.data;
      
      if (!blob || blob.size === 0) {
        throw new Error("Generated PDF is empty");
      }

      // Create blob URL for preview
      const url = window.URL.createObjectURL(blob);
      uiState.previewUrl = url;

      ElMessage.success("GSIS Membership Information Sheet preview loaded");
    } catch (err) {
      const serverMessage = err.response?.data?.message || err.message;
      error.value = serverMessage || "Failed to preview report";
      if (err.response?.status === 404) {
        ElMessage.error(
          serverMessage || "No data found for the selected employee"
        );
      } else if (err.response?.status === 422) {
        ElMessage.error("Please complete the required fields");
      } else {
        ElMessage.error(error.value);
      }
      // Close modal on error
      uiState.showPrintModal = false;
      throw err;
    } finally {
      loading.value = false;
    }
  };

  // Generate report (downloads file)
  const generateReport = async () => {
    try {
      loading.value = true;
      error.value = null;

      if (!formData.employee_id) {
        throw new Error("Employee is required");
      }

      const payload = { ...formData };
      const response = await gsisMemberInfoApi.generateGSISMemberInfo(payload);
      const blob = response.data;
      if (!blob || blob.size === 0) {
        throw new Error("Generated PDF is empty");
      }

      const url = window.URL.createObjectURL(blob);
      const link = document.createElement("a");
      link.href = url;
      // Build friendly filename
      const empObj = employees.value.find((e) => e.id === formData.employee_id);
      const empName = empObj?.name || `emp-${formData.employee_id || "unknown"}`;
      const sanitize = (s) => String(s).replace(/[^A-Za-z0-9_\-()]+/g, "");
      link.download = `GSIS_Member_Info_${sanitize(empName)}_${new Date()
        .toISOString()
        .split("T")[0]}.pdf`;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);
      ElMessage.success("GSIS Membership Information Sheet downloaded successfully");
    } catch (err) {
      const serverMessage = err.response?.data?.message || err.message;
      error.value = serverMessage || "Failed to generate report";
      if (err.response?.status === 404) {
        ElMessage.error(
          serverMessage || "No data found for the selected employee"
        );
      } else if (err.response?.status === 422) {
        ElMessage.error("Please complete the required fields");
      } else {
        ElMessage.error(error.value);
      }
      throw err;
    } finally {
      loading.value = false;
    }
  };

  // Download PDF (uses existing preview blob URL)
  const downloadPdf = () => {
    if (!uiState.previewUrl) {
      ElMessage.warning("No preview available to download");
      return;
    }
    const a = document.createElement("a");
    a.href = uiState.previewUrl;
    const empName = uiState.selectedEmployee?.name || `emp-${formData.employee_id || "unknown"}`;
    const sanitize = (s) => String(s).replace(/[^A-Za-z0-9_\-()]+/g, "");
    a.download = `GSIS_Member_Info_${sanitize(empName)}_${new Date()
      .toISOString()
      .split("T")[0]}.pdf`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    ElMessage.success("PDF downloaded successfully");
  };

  // Download Word document
  const downloadWord = async () => {
    try {
      if (!formData.employee_id) {
        ElMessage.warning("Please select an employee");
        return;
      }

      loading.value = true;
      const payload = { ...formData };
      const response = await gsisMemberInfoApi.generateGSISMemberInfoDocx(payload);
      const blob = response.data;
      
      if (!blob || blob.size === 0) {
        throw new Error("Generated document is empty");
      }

      const url = window.URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      const empName = uiState.selectedEmployee?.name || `emp-${formData.employee_id || "unknown"}`;
      const sanitize = (s) => String(s).replace(/[^A-Za-z0-9_\-()]+/g, "");
      a.download = `GSIS_Member_Info_${sanitize(empName)}_${new Date()
        .toISOString()
        .split("T")[0]}.docx`;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      window.URL.revokeObjectURL(url);
      ElMessage.success("Word document downloaded successfully");
    } catch (err) {
      const serverMessage = err.response?.data?.message || err.message;
      error.value = serverMessage || "Failed to download Word document";
      ElMessage.error(error.value);
    } finally {
      loading.value = false;
    }
  };

  // Download Excel document
  const downloadExcel = async () => {
    try {
      if (!formData.employee_id) {
        ElMessage.warning("Please select an employee");
        return;
      }

      loading.value = true;
      const payload = { ...formData };
      const response = await gsisMemberInfoApi.generateGSISMemberInfoExcel(payload);
      const blob = response.data;
      
      if (!blob || blob.size === 0) {
        throw new Error("Generated document is empty");
      }

      const url = window.URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      const empName = uiState.selectedEmployee?.name || `emp-${formData.employee_id || "unknown"}`;
      const sanitize = (s) => String(s).replace(/[^A-Za-z0-9_\-()]+/g, "");
      a.download = `GSIS_Member_Info_${sanitize(empName)}_${new Date()
        .toISOString()
        .split("T")[0]}.xlsx`;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      window.URL.revokeObjectURL(url);
      ElMessage.success("Excel document downloaded successfully");
    } catch (err) {
      const serverMessage = err.response?.data?.message || err.message;
      error.value = serverMessage || "Failed to download Excel document";
      ElMessage.error(error.value);
    } finally {
      loading.value = false;
    }
  };

  // Close preview and cleanup
  const closePreview = () => {
    uiState.showPrintModal = false;
    if (uiState.previewUrl) {
      window.URL.revokeObjectURL(uiState.previewUrl);
      uiState.previewUrl = "";
    }
    uiState.selectedEmployee = null;
  };

  return {
    loading,
    error,
    formData,
    employees,
    loadInitialData,
    loadEmployeeDetails,
    previewReport,
    generateReport,
    uiState,
    downloadPdf,
    downloadWord,
    downloadExcel,
    closePreview,
  };
}

