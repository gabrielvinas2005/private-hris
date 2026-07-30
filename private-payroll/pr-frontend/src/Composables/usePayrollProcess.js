import { ref, computed } from "vue";
import { ElMessage, ElMessageBox, ElNotification } from "element-plus";
import { payrollProcessApi } from "../services/api";

export function usePayrollProcess() {
  const loading = ref(false);
  const processing = ref(false);
  const payrollPeriods = ref([]);
  const payrollSummary = ref(null);
  const payrollDetails = ref([]);
  const payrollAdjustments = ref([]);
  const summaryData = ref(null);
  const summaryCacheVersion = ref(0);

  // Get all payroll periods
  const getPayrollPeriods = async () => {
    try {
      loading.value = true;
      const response = await payrollProcessApi.getPayrollPeriods();
      payrollPeriods.value = response.data.data || [];
      return response.data.data || [];
    } catch (error) {
      console.error("Error fetching payroll periods:", error);
      ElMessage.error("Failed to load payroll periods");
      throw error;
    } finally {
      loading.value = false;
    }
  };

  // Process payroll for a specific period
  const processPayroll = async (payrollPeriodId, options = {}) => {
    try {
      processing.value = true;
      const response = await payrollProcessApi.processPayroll(
        payrollPeriodId,
        options,
      );
      ElMessage.success("Payroll processed successfully!");
      const cacheVersion = response.data?.data?.cache_version;
      await getPayrollSummary(payrollPeriodId, {
        forceRefresh: true,
        cacheVersion,
      });
      return response.data;
    } catch (error) {
      console.error("Error processing payroll:", error);
      const errorMessage =
        error.response?.data?.message || "Failed to process payroll";
      ElMessage.error(errorMessage);
      throw error;
    } finally {
      processing.value = false;
    }
  };

  // Get payroll summary for a specific period
  const getPayrollSummary = async (payrollPeriodId, options = {}) => {
    const { forceRefresh = false, cacheVersion = null } = options;
    try {
      loading.value = true;
      if (forceRefresh) {
        summaryData.value = null;
      }
      const response = await payrollProcessApi.getPayrollSummary(
        payrollPeriodId,
        {
          refresh: forceRefresh,
          cacheVersion: cacheVersion ?? summaryCacheVersion.value,
        },
      );
      summaryData.value = response.data.data;
      const nextVersion =
        response.data?.data?.cache_version ?? summaryCacheVersion.value;
      summaryCacheVersion.value = nextVersion;
      return response.data.data;
    } catch (error) {
      console.error("Error fetching payroll summary:", error);
      ElMessage.error("Failed to load payroll summary");
      throw error;
    } finally {
      loading.value = false;
    }
  };

  // Post/Unpost payroll
  const togglePayrollPosting = async (payrollPeriodId, typeId) => {
    const action = typeId === 1 ? "post" : "unpost";
    try {
      console.log(
        `Attempting to ${action} payroll period ${payrollPeriodId} with typeId ${typeId}`,
      );
      const response = await payrollProcessApi.togglePayrollPosting(
        payrollPeriodId,
        typeId,
      );
      console.log(`API response for ${action}:`, response.data);
      ElMessage.success(`Payroll ${action}ed successfully!`);
      return response.data;
    } catch (error) {
      const errorMessage =
        error.response?.data?.message || `Failed to ${action} payroll`;
      ElNotification({
        title: "Payroll Posting Blocked",
        message: errorMessage,
        type: "error",
        duration: 6000,
      });
    }
  };

  // Generate payroll print report
  const generatePayrollReport = async (payrollPeriodId, params = {}) => {
    try {
      const response = await payrollProcessApi.generatePayrollReport(
        payrollPeriodId,
        params,
      );

      // Create blob link to download
      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement("a");
      link.href = url;
      link.setAttribute(
        "download",
        `payroll_report_${payrollPeriodId}_${new Date().toISOString().split("T")[0]}.pdf`,
      );
      document.body.appendChild(link);
      link.click();
      link.remove();
      window.URL.revokeObjectURL(url);

      ElMessage.success("Payroll report generated successfully!");
      return response.data;
    } catch (error) {
      console.error("Error generating payroll report:", error);
      ElMessage.error("Failed to generate payroll report");
      throw error;
    }
  };

  // Generate tabulated payroll report
  const generateTabulatedReport = async (payrollPeriodId) => {
    try {
      const response =
        await payrollProcessApi.generateTabulatedReport(payrollPeriodId);

      // Create blob link to download
      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement("a");
      link.href = url;
      link.setAttribute(
        "download",
        `payroll_tabulate_${payrollPeriodId}_${new Date().toISOString().split("T")[0]}.pdf`,
      );
      document.body.appendChild(link);
      link.click();
      link.remove();
      window.URL.revokeObjectURL(url);

      ElMessage.success("Tabulated report generated successfully!");
      return response.data;
    } catch (error) {
      console.error("Error generating tabulated report:", error);
      ElMessage.error("Failed to generate tabulated report");
      throw error;
    }
  };

  // Generate ORS (Obligation Request) report
  const generateORSReport = async (payrollPeriodId, params = {}) => {
    try {
      const response = await payrollProcessApi.generateORSReport(
        payrollPeriodId,
        params,
      );

      // Create blob link to download
      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement("a");
      link.href = url;
      link.setAttribute(
        "download",
        `ors_payroll_${payrollPeriodId}_${new Date().toISOString().split("T")[0]}.pdf`,
      );
      document.body.appendChild(link);
      link.click();
      link.remove();
      window.URL.revokeObjectURL(url);

      ElMessage.success("ORS report generated successfully!");
      return response.data;
    } catch (error) {
      console.error("Error generating ORS report:", error);
      ElMessage.error("Failed to generate ORS report");
      throw error;
    }
  };

  // Generate DV (Disbursement Voucher) report
  const generateDVReport = async (payrollPeriodId, params = {}) => {
    try {
      const response = await payrollProcessApi.generateDVReport(
        payrollPeriodId,
        params,
      );

      // Create blob link to download
      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement("a");
      link.href = url;
      link.setAttribute(
        "download",
        `dv_payroll_${payrollPeriodId}_${new Date().toISOString().split("T")[0]}.pdf`,
      );
      document.body.appendChild(link);
      link.click();
      link.remove();
      window.URL.revokeObjectURL(url);

      ElMessage.success("DV report generated successfully!");
      return response.data;
    } catch (error) {
      console.error("Error generating DV report:", error);
      ElMessage.error("Failed to generate DV report");
      throw error;
    }
  };

  // Get payroll summary data for reports
  const getPayrollSummaryData = async () => {
    try {
      const response = await payrollProcessApi.getPayrollSummaryData();
      return response.data.data;
    } catch (error) {
      console.error("Error fetching payroll summary data:", error);
      ElMessage.error("Failed to load payroll summary data");
      throw error;
    }
  };

  // Generate general payroll report
  const generateGeneralPayrollReport = async (requestData) => {
    try {
      const response =
        await payrollProcessApi.generateGeneralPayrollReport(requestData);

      // Create blob link to download
      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement("a");
      link.href = url;
      link.setAttribute("download", "payroll_summary_report.pdf");
      document.body.appendChild(link);
      link.click();
      link.remove();
      window.URL.revokeObjectURL(url);

      ElMessage.success("General payroll report generated successfully!");
      return response.data;
    } catch (error) {
      console.error("Error generating general payroll report:", error);
      ElMessage.error("Failed to generate general payroll report");
      throw error;
    }
  };

  // Generate detailed payroll report
  const generateDetailedPayrollReport = async (requestData) => {
    try {
      const response =
        await payrollProcessApi.generateDetailedPayrollReport(requestData);

      // Create blob link to download
      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement("a");
      link.href = url;
      link.setAttribute("download", "payroll_summary_details_report.pdf");
      document.body.appendChild(link);
      link.click();
      link.remove();
      window.URL.revokeObjectURL(url);

      ElMessage.success("Detailed payroll report generated successfully!");
      return response.data;
    } catch (error) {
      console.error("Error generating detailed payroll report:", error);
      ElMessage.error("Failed to generate detailed payroll report");
      throw error;
    }
  };

  // Adjust tax amounts for employees
  const adjustTaxAmounts = async (payrollPeriodId, adjustmentData) => {
    try {
      const response = await payrollProcessApi.adjustTaxAmounts(
        payrollPeriodId,
        adjustmentData,
      );
      ElMessage.success("Tax amounts adjusted successfully!");
      return response.data;
    } catch (error) {
      console.error("Error adjusting tax amounts:", error);
      const errorMessage =
        error.response?.data?.message || "Failed to adjust tax amounts";
      ElMessage.error(errorMessage);
      throw error;
    }
  };

  // Load salary adjustments for a payroll period
  const getSalaryAdjustments = async (payrollPeriodId) => {
    try {
      const response =
        await payrollProcessApi.getSalaryAdjustments(payrollPeriodId);
      return response.data.data || [];
    } catch (error) {
      console.error("Error loading salary adjustments:", error);
      const errorMessage =
        error.response?.data?.message || "Failed to load salary adjustments";
      ElMessage.error(errorMessage);
      throw error;
    }
  };

  // Save salary adjustments for a payroll period
  const saveSalaryAdjustments = async (payrollPeriodId, adjustments) => {
    try {
      const payload = { adjustments };
      const response = await payrollProcessApi.saveSalaryAdjustments(
        payrollPeriodId,
        payload,
      );
      ElMessage.success("Salary adjustments saved successfully!");
      return response.data;
    } catch (error) {
      console.error("Error saving salary adjustments:", error);
      const errorMessage =
        error.response?.data?.message || "Failed to save salary adjustments";
      ElMessage.error(errorMessage);
      throw error;
    }
  };

  // Confirmation dialog for processing payroll
  const confirmProcessPayroll = async (payrollPeriod) => {
    try {
      await ElMessageBox.confirm(
        `Are you sure you want to process payroll for ${payrollPeriod.payroll}? This action cannot be undone.`,
        "Confirm Payroll Processing",
        {
          confirmButtonText: "Process",
          cancelButtonText: "Cancel",
          type: "warning",
        },
      );
      return await processPayroll(payrollPeriod.id);
    } catch (error) {
      if (error !== "cancel") {
        throw error;
      }
    }
  };

  // Confirmation dialog for posting payroll
  const confirmPostPayroll = async (payrollPeriod) => {
    try {
      await ElMessageBox.confirm(
        `Are you sure you want to post payroll for ${payrollPeriod.payroll}? This will finalize the payroll.`,
        "Confirm Payroll Posting",
        {
          confirmButtonText: "Post",
          cancelButtonText: "Cancel",
          type: "warning",
        },
      );
      return await togglePayrollPosting(payrollPeriod.id, 1);
    } catch (error) {
      if (error !== "cancel") {
        throw error;
      }
    }
  };

  // Confirmation dialog for unposting payroll
  const confirmUnpostPayroll = async (payrollPeriod) => {
    try {
      await ElMessageBox.confirm(
        `Are you sure you want to unpost payroll for ${payrollPeriod.payroll}? This will make the payroll editable again.`,
        "Confirm Payroll Unposting",
        {
          confirmButtonText: "Unpost",
          cancelButtonText: "Cancel",
          type: "warning",
        },
      );
      return await togglePayrollPosting(payrollPeriod.id, 0);
    } catch (error) {
      if (error !== "cancel") {
        throw error;
      }
    }
  };

  // Computed properties
  const processedPeriods = computed(() =>
    payrollPeriods.value.filter((period) => period.posted),
  );

  const unprocessedPeriods = computed(() =>
    payrollPeriods.value.filter((period) => !period.posted),
  );

  const totalEmployees = computed(
    () => summaryData.value?.payrolls?.length || 0,
  );

  const totalGrossAmount = computed(
    () =>
      summaryData.value?.payrolls?.reduce(
        (sum, payroll) => sum + (payroll.gross_amount || 0),
        0,
      ) || 0,
  );

  const totalNetAmount = computed(
    () =>
      summaryData.value?.payrolls?.reduce(
        (sum, payroll) => sum + (payroll.net_pay || 0),
        0,
      ) || 0,
  );

  const totalDeductions = computed(
    () =>
      summaryData.value?.payrolls?.reduce(
        (sum, payroll) => sum + (payroll.total_deduction || 0),
        0,
      ) || 0,
  );

  return {
    // State
    loading,
    processing,
    payrollPeriods,
    payrollSummary,
    payrollDetails,
    payrollAdjustments,
    summaryData,
    summaryCacheVersion,

    // Computed
    processedPeriods,
    unprocessedPeriods,
    totalEmployees,
    totalGrossAmount,
    totalNetAmount,
    totalDeductions,

    // Methods
    getPayrollPeriods,
    processPayroll,
    getPayrollSummary,
    togglePayrollPosting,
    generatePayrollReport,
    generateTabulatedReport,
    generateORSReport,
    generateDVReport,
    getPayrollSummaryData,
    generateGeneralPayrollReport,
    generateDetailedPayrollReport,
    adjustTaxAmounts,
    getSalaryAdjustments,
    saveSalaryAdjustments,

    // Confirmation methods
    confirmProcessPayroll,
    confirmPostPayroll,
    confirmUnpostPayroll,
  };
}
