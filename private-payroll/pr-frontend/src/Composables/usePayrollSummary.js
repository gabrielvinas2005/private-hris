import { ref, reactive } from "vue";
import { payrollProcessApi, signatoryApi } from "../services/api.js";
import { ElMessage } from "element-plus";
import { formatApiError } from "../utils/apiErrorMessage.js";

export function usePayrollSummary() {
  const loading = ref(false);
  const error = ref(null);

  // Form data
  const formData = reactive({
    payroll_interval_id: "",
    payroll_period_id: "",
    payroll_period_id_2: "",
    branch_id: "",
    division_id: "",
    column_display_mode: "with_values",
    document_no: "",
    revision: "",
  });

  // Signatories data
  const signatories = reactive({
    signatory_1: "",
    signatory_position_1: "",
    signatory_2: "",
    signatory_position_2: "",
  });

  // Employee options for signatory dropdowns
  const availableSignatories = ref([]);

  // Dropdown data
  const payrollIntervals = ref([]);
  const payrollPeriods = ref([]);
  const branches = ref([]);
  const divisions = ref([]);

  // Load initial data
  const loadInitialData = async () => {
    try {
      loading.value = true;
      error.value = null;

      const response = await payrollProcessApi.getPayrollSummaryData();
      const data = response.data.data;

      payrollIntervals.value = data.payroll_intervals || [];
      branches.value = data.branches || [];
      divisions.value = data.divisions || data.departments || [];

      // Load employee options for signatory dropdown (reuse ATM Letter endpoint)
      try {
        const sigRes = await signatoryApi.getEmployeeOptions();
        const sdata = sigRes.data?.data || {};
        availableSignatories.value = sdata.employee_options || [];
      } catch {
        availableSignatories.value = [];
      }

      return data;
    } catch (err) {
      error.value =
        err.response?.data?.message || "Failed to load initial data";
      ElMessage.error("Failed to load initial data");
      throw err;
    } finally {
      loading.value = false;
    }
  };

  // Load payroll periods by interval
  const loadPayrollPeriods = async (intervalId) => {
    if (!intervalId) {
      payrollPeriods.value = [];
      formData.payroll_period_id = "";
      formData.payroll_period_id_2 = "";
      return;
    }

    try {
      loading.value = true;
      error.value = null;

      const response =
        await payrollProcessApi.getPostedPayrollPeriodsByInterval(intervalId);
      const rawPeriods = response.data.data || [];

      // Determine if this interval is Monthly so we can group
      // first-half and second-half periods into a single "Month Year" option.
      const interval = payrollIntervals.value.find(
        (i) => i.id === intervalId,
      );
      const isMonthly =
        interval &&
        typeof interval.name === "string" &&
        /^monthly\b/i.test(interval.name.trim());

      if (isMonthly) {
        payrollPeriods.value = groupMonthlyPeriods(rawPeriods);
      } else {
        payrollPeriods.value = rawPeriods;
      }

      formData.payroll_period_id = "";
      formData.payroll_period_id_2 = "";
    } catch (err) {
      error.value = formatApiError(err, "Failed to load payroll periods");
      ElMessage.error(error.value);
      payrollPeriods.value = [];
      return [];
    } finally {
      loading.value = false;
    }
  };

  // Preview state
  const previewUrl = ref(null);
  const showPreview = ref(false);

  // Preview report (opens in iframe modal)
  const previewReport = async () => {
    try {
      loading.value = true;
      error.value = null;

      // Validate required fields
      if (!formData.payroll_interval_id) {
        throw new Error("Payroll Interval is required");
      }
      if (!formData.payroll_period_id) {
        throw new Error("Payroll Period is required");
      }

      // Resolve selected period meta (to pass optional second-half id)
      const selectedPeriod = payrollPeriods.value.find(
        (p) => p.id === formData.payroll_period_id,
      );
      if (selectedPeriod) {
        formData.payroll_period_id = selectedPeriod.payroll_period_id || selectedPeriod.id;
        formData.payroll_period_id_2 =
          selectedPeriod.payroll_period_id_2 || "";
      } else {
        formData.payroll_period_id_2 = "";
      }

      // Prepare request data
      const requestData = {
        ...formData,
        ...signatories,
      };

      // Generate report for preview
      const response =
        await payrollProcessApi.generateGeneralPayrollReport(requestData);

      if (response.data.size === 0) {
        throw new Error("Generated PDF is empty - no payroll data found");
      }

      // Create blob URL for iframe preview
      const url = window.URL.createObjectURL(response.data);
      previewUrl.value = url;
      showPreview.value = true;

      ElMessage.success("Payroll Summary Report preview opened");
      return response.data;
    } catch (err) {
      error.value = formatApiError(err, "Failed to preview report");
      ElMessage.error(error.value);
      return null;
    } finally {
      loading.value = false;
    }
  };

  // Close preview
  const closePreview = () => {
    if (previewUrl.value) {
      window.URL.revokeObjectURL(previewUrl.value);
      previewUrl.value = null;
    }
    showPreview.value = false;
  };

  // Download PDF
  const downloadPdf = async () => {
    try {
      loading.value = true;
      error.value = null;

      // Validate required fields
      if (!formData.payroll_interval_id) {
        throw new Error("Payroll Interval is required");
      }
      if (!formData.payroll_period_id) {
        throw new Error("Payroll Period is required");
      }

      const selectedPeriod = payrollPeriods.value.find(
        (p) => p.id === formData.payroll_period_id,
      );
      if (selectedPeriod) {
        formData.payroll_period_id = selectedPeriod.payroll_period_id || selectedPeriod.id;
        formData.payroll_period_id_2 =
          selectedPeriod.payroll_period_id_2 || "";
      } else {
        formData.payroll_period_id_2 = "";
      }

      // Prepare request data
      const requestData = {
        ...formData,
        ...signatories,
      };

      // Generate report
      const response =
        await payrollProcessApi.generateGeneralPayrollReport(requestData);
      const blob = response.data;

      if (blob.size === 0) {
        throw new Error("Generated PDF is empty - no payroll data found");
      }

      // Create download link
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement("a");
      link.href = url;
      link.download = `payroll-summary-${new Date().toISOString().split("T")[0]}.pdf`;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);

      ElMessage.success("PDF downloaded successfully");
      return response.data;
    } catch (err) {
      error.value = formatApiError(err, "Failed to download PDF");
      ElMessage.error(error.value);
      return null;
    } finally {
      loading.value = false;
    }
  };

  // Download DOCX
  const downloadDocx = async () => {
    try {
      loading.value = true;
      error.value = null;

      // Validate required fields
      if (!formData.payroll_interval_id) {
        throw new Error("Payroll Interval is required");
      }
      if (!formData.payroll_period_id) {
        throw new Error("Payroll Period is required");
      }

      const selectedPeriod = payrollPeriods.value.find(
        (p) => p.id === formData.payroll_period_id,
      );
      if (selectedPeriod) {
        formData.payroll_period_id = selectedPeriod.payroll_period_id || selectedPeriod.id;
        formData.payroll_period_id_2 =
          selectedPeriod.payroll_period_id_2 || "";
      } else {
        formData.payroll_period_id_2 = "";
      }

      // Prepare request data
      const requestData = {
        ...formData,
        ...signatories,
      };

      // Generate DOCX
      const response =
        await payrollProcessApi.generateGeneralPayrollReportDocx(requestData);
      const blob = response.data;

      if (blob.size === 0) {
        throw new Error("Generated DOCX is empty - no payroll data found");
      }

      // Create download link
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement("a");
      link.href = url;
      link.download = `payroll-summary-${new Date().toISOString().split("T")[0]}.docx`;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);

      ElMessage.success("DOCX downloaded successfully");
      return response.data;
    } catch (err) {
      error.value = formatApiError(err, "Failed to download DOCX");
      ElMessage.error(error.value);
      return null;
    } finally {
      loading.value = false;
    }
  };

  // Download Excel
  const downloadExcel = async () => {
    try {
      loading.value = true;
      error.value = null;

      // Validate required fields
      if (!formData.payroll_interval_id) {
        throw new Error("Payroll Interval is required");
      }
      if (!formData.payroll_period_id) {
        throw new Error("Payroll Period is required");
      }

      const selectedPeriod = payrollPeriods.value.find(
        (p) => p.id === formData.payroll_period_id,
      );
      if (selectedPeriod) {
        formData.payroll_period_id = selectedPeriod.payroll_period_id || selectedPeriod.id;
        formData.payroll_period_id_2 =
          selectedPeriod.payroll_period_id_2 || "";
      } else {
        formData.payroll_period_id_2 = "";
      }

      // Prepare request data
      const requestData = {
        ...formData,
        ...signatories,
      };

      // Generate Excel
      const response =
        await payrollProcessApi.generateGeneralPayrollReportExcel(requestData);
      const blob = response.data;

      if (blob.size === 0) {
        throw new Error("Generated Excel is empty - no payroll data found");
      }

      // Create download link
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement("a");
      link.href = url;
      link.download = `payroll-summary-${new Date().toISOString().split("T")[0]}.xlsx`;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);

      ElMessage.success("Excel downloaded successfully");
      return response.data;
    } catch (err) {
      error.value = formatApiError(err, "Failed to download Excel");
      ElMessage.error(error.value);
      return null;
    } finally {
      loading.value = false;
    }
  };

  // Generate report (downloads file)
  const generateReport = async () => {
    try {
      loading.value = true;
      error.value = null;

      // Validate required fields
      if (!formData.payroll_interval_id) {
        throw new Error("Payroll Interval is required");
      }
      if (!formData.payroll_period_id) {
        throw new Error("Payroll Period is required");
      }

      const selectedPeriod = payrollPeriods.value.find(
        (p) => p.id === formData.payroll_period_id,
      );
      if (selectedPeriod) {
        formData.payroll_period_id = selectedPeriod.payroll_period_id || selectedPeriod.id;
        formData.payroll_period_id_2 =
          selectedPeriod.payroll_period_id_2 || "";
      } else {
        formData.payroll_period_id_2 = "";
      }

      // Prepare request data
      const requestData = {
        ...formData,
        ...signatories,
      };

      // Generate report
      const response =
        await payrollProcessApi.generateGeneralPayrollReport(requestData);
      // The response.data is already a blob (binary PDF data)
      const blob = response.data;

      if (blob.size === 0) {
        throw new Error("Generated PDF is empty - no payroll data found");
      }

      console.log("PDF blob size:", blob.size);

      // Create download link
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement("a");
      link.href = url;
      link.download = `payroll-summary-${new Date().toISOString().split("T")[0]}.pdf`;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);

      ElMessage.success("Report downloaded successfully");
      return response.data;
    } catch (err) {
      error.value = formatApiError(err, "Failed to generate report");
      ElMessage.error(error.value);
      return null;
    } finally {
      loading.value = false;
    }
  };

  // Reset form
  const resetForm = () => {
    formData.payroll_interval_id = "";
    formData.payroll_period_id = "";
    formData.payroll_period_id_2 = "";
    formData.branch_id = "";
    formData.division_id = "";
    formData.column_display_mode = "with_values";
    formData.document_no = "";
    formData.revision = "";
    payrollPeriods.value = [];
  };

  return {
    // State
    loading,
    error,
    formData,
    signatories,
    availableSignatories,
    payrollIntervals,
    payrollPeriods,
    branches,
    divisions,
    previewUrl,
    showPreview,

    // Methods
    loadInitialData,
    loadPayrollPeriods,
    previewReport,
    generateReport,
    downloadPdf,
    downloadDocx,
    downloadExcel,
    closePreview,
    resetForm,
  };
}

// Group posted payroll periods for Monthly interval into one option per month,
// each carrying first-half and second-half period IDs when available.
function groupMonthlyPeriods(periods) {
  const groups = {};

  periods.forEach((period) => {
    const name = period.name || "";
    const monthMatch = name.match(/\(([^)]+)\)/);
    const monthYear = monthMatch ? monthMatch[1] : name;
    const key = monthYear;
    const lowerName = name.toLowerCase();

    if (!groups[key]) {
      groups[key] = {
        id: period.id,
        name: monthYear,
        payroll_period_id: period.id,
        payroll_period_id_2: "",
        first_half_id: null,
        second_half_id: null,
        explicitFirstHalf: false,
      };
    }

    if (lowerName.includes("first") || lowerName.includes("1st")) {
      groups[key].first_half_id = period.id;
      groups[key].payroll_period_id = period.id;
      groups[key].explicitFirstHalf = true;
    } else if (lowerName.includes("second") || lowerName.includes("2nd")) {
      groups[key].second_half_id = period.id;
    } else {
      // Single monthly row (name has no 1st/2nd half); keep ID on first_half for API use
      groups[key].first_half_id = period.id;
      groups[key].payroll_period_id = period.id;
    }
  });

  return Object.values(groups).map((group) => {
    const cutoff_labels = [];
    if (group.first_half_id && group.second_half_id) {
      cutoff_labels.push("First-Half", "Second-Half");
    } else if (group.first_half_id) {
      cutoff_labels.push(
        group.explicitFirstHalf ? "First-Half" : "MONTHLY",
      );
    } else if (group.second_half_id) {
      cutoff_labels.push("Second-Half");
    }

    return {
      id: group.payroll_period_id,
      name: `Monthly (${group.name})`,
      payroll_period_id: group.payroll_period_id,
      payroll_period_id_2: group.second_half_id || "",
      cutoff_labels,
    };
  });
}
