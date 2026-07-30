import { ref, computed, reactive } from "vue";
import { retirementBenefitsApi } from "../services/api.js";
import { ElMessage } from "element-plus";

export function useRetirementBenefits() {
  const loading = ref(false);
  const departments = ref([]);
  const retirees = ref([]);
  const loadingRetirees = ref(false);

  // Print preview state management
  const uiState = reactive({
    showPrintModal: false,
    previewUrl: "",
  });

  // Load retirement benefits data
  const loadRetirementBenefitsData = async () => {
    try {
      loading.value = true;
      const response = await retirementBenefitsApi.getRetirementBenefitsData();
      const data = response.data.data;

      departments.value = (data.departments || []).filter(
        (department) => String(department.id) !== "0"
      );

      return data;
    } catch (error) {
      console.error("Error loading retirement benefits data:", error);
      console.error("Error details:", error.response);

      if (error.response?.status === 401) {
        ElMessage.error("Please log in to access retirement benefits data");
      } else if (error.response?.status === 500) {
        ElMessage.error("Server error. Please check if the backend is running");
      } else if (error.code === "NETWORK_ERROR" || !error.response) {
        ElMessage.error(
          "Cannot connect to server. Please check if the backend is running"
        );
      } else {
        ElMessage.error("Failed to load retirement benefits data");
      }
      throw error;
    } finally {
      loading.value = false;
    }
  };

  // Preview BP FORM 205 PDF (shows inline preview)
  const previewBPForm205 = async (formData) => {
    try {
      loading.value = true;
      const response = await retirementBenefitsApi.generatePdf(formData, {
        responseType: "blob",
      });

      // Create blob URL for preview
      const blob = new Blob([response.data], { type: "application/pdf" });
      if (!blob || blob.size === 0) {
        throw new Error("Generated PDF is empty");
      }

      // Set preview URL and show modal
      const url = window.URL.createObjectURL(blob);
      uiState.previewUrl = url;
      uiState.showPrintModal = true;

      ElMessage.success("BP FORM 205 preview loaded");
      return response;
    } catch (error) {
      console.error("Error previewing BP FORM 205:", error);
      if (error.response?.status === 401) {
        ElMessage.error("Please log in to preview BP FORM 205");
      } else if (error.response?.status === 422) {
        ElMessage.error(
          error.response?.data?.message || "Validation error. Please check your inputs."
        );
      } else if (error.response?.status === 500) {
        ElMessage.error("Server error. Please try again later");
      } else {
        ElMessage.error("Failed to preview BP FORM 205");
      }
      // Close modal on error
      uiState.showPrintModal = false;
      throw error;
    } finally {
      loading.value = false;
    }
  };

  // Generate BP FORM 205 PDF (downloads file)
  const generateBPForm205 = async (formData) => {
    try {
      loading.value = true;
      const response = await retirementBenefitsApi.generatePdf(formData, {
        responseType: "blob",
      });

      // Create blob URL and download
      const blob = new Blob([response.data], { type: "application/pdf" });
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement("a");
      link.href = url;

      // Extract filename from Content-Disposition header or use default
      const contentDisposition = response.headers["content-disposition"];
      let filename = "bp_form_205_retirement_benefits.pdf";
      if (contentDisposition) {
        const filenameMatch = contentDisposition.match(/filename="?(.+)"?/i);
        if (filenameMatch) {
          filename = filenameMatch[1];
        }
      }
      link.setAttribute("download", filename);
      document.body.appendChild(link);
      link.click();
      link.remove();
      window.URL.revokeObjectURL(url);

      ElMessage.success("BP FORM 205 generated successfully");
      return response;
    } catch (error) {
      console.error("Error generating BP FORM 205:", error);
      if (error.response?.status === 401) {
        ElMessage.error("Please log in to generate BP FORM 205");
      } else if (error.response?.status === 422) {
        ElMessage.error(
          error.response?.data?.message || "Validation error. Please check your inputs."
        );
      } else if (error.response?.status === 500) {
        ElMessage.error("Server error. Please try again later");
      } else {
        ElMessage.error("Failed to generate BP FORM 205");
      }
      throw error;
    } finally {
      loading.value = false;
    }
  };

  // Load retirees for a fiscal year
  const loadRetirees = async (fiscalYear) => {
    try {
      loadingRetirees.value = true;
      const response = await retirementBenefitsApi.getRetirees(fiscalYear);
      retirees.value = response.data.data.retirees || [];
      ElMessage.success(`Loaded ${retirees.value.length} retiree(s)`);
      return response.data.data;
    } catch (error) {
      console.error("Error loading retirees:", error);
      ElMessage.error("Failed to load retirees data");
      throw error;
    } finally {
      loadingRetirees.value = false;
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
    const sanitize = (s) => String(s).replace(/[^A-Za-z0-9_\-()]+/g, "");
    a.download = `BP_FORM_205_Retirement_Benefits_${sanitize(new Date().toISOString().split("T")[0])}.pdf`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    ElMessage.success("PDF downloaded successfully");
  };

  // Download Word document
  const downloadWord = async (formData) => {
    try {
      if (!formData.fiscal_year) {
        ElMessage.warning("Please select a fiscal year");
        return;
      }

      if (retirees.value.length === 0) {
        ElMessage.warning("Please load retirees first");
        return;
      }

      loading.value = true;
      const payload = { ...formData };
      const response = await retirementBenefitsApi.generateDocx(payload, {
        responseType: "blob",
      });
      
      const blob = response.data;
      
      if (!blob || blob.size === 0) {
        throw new Error("Generated document is empty");
      }

      const url = window.URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      
      // Extract filename from Content-Disposition header or use default
      const contentDisposition = response.headers["content-disposition"];
      let filename = `bp_form_205_retirement_benefits_${formData.fiscal_year}_${new Date().toISOString().split("T")[0]}.docx`;
      if (contentDisposition) {
        const filenameMatch = contentDisposition.match(/filename="?(.+)"?/i);
        if (filenameMatch) {
          filename = filenameMatch[1];
        }
      }
      
      a.download = filename;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      window.URL.revokeObjectURL(url);
      ElMessage.success("Word document downloaded successfully");
    } catch (err) {
      console.error("Error downloading Word document:", err);
      const serverMessage = err.response?.data?.message || err.message;
      if (err.response?.status === 401) {
        ElMessage.error("Please log in to download Word document");
      } else if (err.response?.status === 422) {
        ElMessage.error(
          err.response?.data?.message || "Validation error. Please check your inputs."
        );
      } else if (err.response?.status === 500) {
        ElMessage.error("Server error. Please try again later");
      } else {
        ElMessage.error(serverMessage || "Failed to download Word document");
      }
      throw err;
    } finally {
      loading.value = false;
    }
  };

  // Download Excel document
  const downloadExcel = async (formData) => {
    try {
      if (!formData.fiscal_year) {
        ElMessage.warning("Please select a fiscal year");
        return;
      }

      if (retirees.value.length === 0) {
        ElMessage.warning("Please load retirees first");
        return;
      }

      loading.value = true;
      const payload = { ...formData };
      const response = await retirementBenefitsApi.generateExcel(payload, {
        responseType: "blob",
      });
      
      const blob = response.data;
      
      if (!blob || blob.size === 0) {
        throw new Error("Generated document is empty");
      }

      const url = window.URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      
      // Extract filename from Content-Disposition header or use default
      const contentDisposition = response.headers["content-disposition"];
      let filename = `bp_form_205_retirement_benefits_${formData.fiscal_year}_${new Date().toISOString().split("T")[0]}.xlsx`;
      if (contentDisposition) {
        const filenameMatch = contentDisposition.match(/filename="?(.+)"?/i);
        if (filenameMatch) {
          filename = filenameMatch[1];
        }
      }
      
      a.download = filename;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      window.URL.revokeObjectURL(url);
      ElMessage.success("Excel document downloaded successfully");
    } catch (err) {
      console.error("Error downloading Excel document:", err);
      const serverMessage = err.response?.data?.message || err.message;
      if (err.response?.status === 401) {
        ElMessage.error("Please log in to download Excel document");
      } else if (err.response?.status === 422) {
        ElMessage.error(
          err.response?.data?.message || "Validation error. Please check your inputs."
        );
      } else if (err.response?.status === 500) {
        ElMessage.error("Server error. Please try again later");
      } else {
        ElMessage.error(serverMessage || "Failed to download Excel document");
      }
      throw err;
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
  };

  return {
    loading,
    departments,
    retirees,
    loadingRetirees,
    loadRetirementBenefitsData,
    loadRetirees,
    previewBPForm205,
    generateBPForm205,
    uiState,
    downloadPdf,
    downloadWord,
    downloadExcel,
    closePreview,
  };
}

