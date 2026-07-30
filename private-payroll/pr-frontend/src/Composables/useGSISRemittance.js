import { ref, reactive } from "vue";
import { divisionsFromApi, divisionParams } from "../utils/payrollReportDivisions.js";
import { formatApiError } from "../utils/apiErrorMessage.js";
import { gsisRemittanceApi } from "../services/api.js";
import { ElMessage } from "element-plus";

export function useGSISRemittance() {
  const loading = ref(false);
  const error = ref(null);

  const formData = reactive({
    payroll_interval_id: "",
    payroll_period_id: "",
    division_id: "",
  });

  const signatories = reactive({
    certified_correct: "",
    position: "",
  });

  const payrollIntervals = ref([]);
  const payrollPeriods = ref([]);
  const divisions = ref([]);
  // Keep signature with API parity; not used here
  const membershipPrograms = ref([]);

  const handleReportError = (err, fallback) => {
    const msg = formatApiError(err, fallback);
    error.value = msg;
    ElMessage.error(msg);
    throw err;
  };

  const loadInitialData = async () => {
    try {
      loading.value = true;
      error.value = null;

      const res = await gsisRemittanceApi.getGSISRemittanceData();
      const data = res.data?.data || {};
      payrollIntervals.value = data.payroll_intervals || [];
      divisions.value = divisionsFromApi(data) || [];
      return data;
    } catch (err) {
      handleReportError(err, "Failed to load GSIS remittance form data.");
    } finally {
      loading.value = false;
    }
  };

  const loadPayrollPeriods = async (
    intervalId,
    departmentId = formData.division_id
  ) => {
    try {
      if (!intervalId) {
        payrollPeriods.value = [];
        return [];
      }
      // If department is not yet selected, clear periods and return
      if (!departmentId) {
        payrollPeriods.value = [];
        return [];
      }
      loading.value = true;
      error.value = null;
      // Load only periods that have GSIS data for selected interval + department
      const res = await gsisRemittanceApi.getGSISPayrollPeriods(
        intervalId,
        departmentId
      );
      const list = res.data?.data || [];
      const formatLabel = (d, intervalIdForLabel) => {
        try {
          const dt = new Date(d);
          if (Number.isNaN(dt.getTime())) return String(d || "");
          const base = dt.toLocaleString("en-US", {
            month: "long",
            year: "numeric",
          });
          const intervalName =
            payrollIntervals.value.find((i) => i.id === intervalIdForLabel)
              ?.name || "";
          return intervalName ? `${base} (${intervalName})` : base;
        } catch (_) {
          return String(d || "");
        }
      };
      payrollPeriods.value = list.map((p) => ({
        ...p,
        label: formatLabel(p.release_date, intervalId),
      }));
      return list;
    } catch (err) {
      handleReportError(err, "Failed to load payroll periods for the selected Division.");
    } finally {
      loading.value = false;
    }
  };

  const previewReport = async () => {
    try {
      loading.value = true;
      error.value = null;

      if (!formData.payroll_interval_id)
        throw new Error("Payroll Interval is required");
      if (!formData.payroll_period_id)
        throw new Error("Payroll Period is required");
      if (!formData.division_id) throw new Error("Division is required.");

      const payload = {
        payroll_interval_id: formData.payroll_interval_id,
        payroll_period_id: formData.payroll_period_id,
        ...divisionParams(formData),
        certified_correct: signatories.certified_correct,
        position: signatories.position,
      };

      const response =
        await gsisRemittanceApi.generateGSISRemittanceReport(payload);
      const blob = response.data;
      if (!blob || blob.size === 0) {
        throw new Error(
          "The report file is empty. No GSIS data was found for the selected Division and payroll period.",
        );
      }
      const url = window.URL.createObjectURL(blob);
      window.open(url, "_blank");
      ElMessage.success("Preview opened in a new tab");
    } catch (err) {
      if (!error.value) {
        if (err?.message && !err?.response) {
          error.value = err.message;
          ElMessage.error(err.message);
        } else {
          handleReportError(err, "Failed to preview the GSIS remittance report.");
        }
      }
    } finally {
      loading.value = false;
    }
  };

  const generateReport = async () => {
    try {
      loading.value = true;
      error.value = null;

      if (!formData.payroll_interval_id)
        throw new Error("Payroll Interval is required");
      if (!formData.payroll_period_id)
        throw new Error("Payroll Period is required");
      if (!formData.division_id) throw new Error("Division is required.");

      const payload = {
        payroll_interval_id: formData.payroll_interval_id,
        payroll_period_id: formData.payroll_period_id,
        ...divisionParams(formData),
        certified_correct: signatories.certified_correct,
        position: signatories.position,
      };

      const response =
        await gsisRemittanceApi.generateGSISRemittanceReport(payload);
      const blob = response.data;
      if (!blob || blob.size === 0) {
        throw new Error(
          "The report file is empty. No GSIS data was found for the selected Division and payroll period.",
        );
      }

      const url = window.URL.createObjectURL(blob);
      const link = document.createElement("a");
      link.href = url;

      const intervalObj = payrollIntervals.value.find(
        (i) => i.id === formData.payroll_interval_id
      );
      const periodObj = payrollPeriods.value.find(
        (p) => p.id === formData.payroll_period_id
      );
      const deptObj = divisions.value.find(
        (d) => d.id === formData.division_id
      );
      const sanitize = (s) =>
        String(s || "").replace(/[^A-Za-z0-9_\-()]+/g, "");
      const currentDate = new Date().toISOString().split("T")[0];
      link.download = `gsis-remittance-${sanitize(intervalObj?.name)}-${sanitize(periodObj?.name)}-${sanitize(deptObj?.name)}-${currentDate}.pdf`;

      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);
      ElMessage.success("GSIS Remittance Report downloaded successfully");
    } catch (err) {
      if (!error.value) {
        if (err?.message && !err?.response) {
          error.value = err.message;
          ElMessage.error(err.message);
        } else {
          handleReportError(err, "Failed to generate the GSIS remittance report.");
        }
      }
    } finally {
      loading.value = false;
    }
  };

  return {
    loading,
    error,
    formData,
    signatories,
    payrollIntervals,
    payrollPeriods,
    divisions,
    membershipPrograms,
    loadInitialData,
    loadPayrollPeriods,
    previewReport,
    generateReport,
  };
}
