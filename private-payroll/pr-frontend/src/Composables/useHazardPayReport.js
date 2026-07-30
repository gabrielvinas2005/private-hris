import { ref, reactive, computed } from "vue";
import { divisionsFromApi, divisionParams } from "../utils/payrollReportDivisions.js";
import { hazardPayApi, signatoryApi } from "../services/api.js";
import { ElMessage } from "element-plus";

const MONTH_NAMES = [
  "January",
  "February",
  "March",
  "April",
  "May",
  "June",
  "July",
  "August",
  "September",
  "October",
  "November",
  "December",
];

function normalizeMonths(list) {
  if (!Array.isArray(list) || list.length === 0) {
    return MONTH_NAMES.map((name, index) => ({
      id: index + 1,
      name,
    }));
  }

  return list.map((m, index) => {
    const id = Number(m.id ?? index + 1);
    return {
      id,
      name: m.name || MONTH_NAMES[id - 1] || `Month ${id}`,
      abbrv: m.abbrv,
    };
  });
}

// Composable dedicated to Hazard Pay Report
export function useHazardPayReport() {
  const loading = ref(false);
  const error = ref(null);

  // Form data used by ReportParameters.vue
  const formData = reactive({
    division_id: "",
    month_id: "",
    year: "",
  });

  // Signatories used by SignatoriesSection.vue (note: underscore keys for UI)
  const signatories = reactive({
    signatory_1: "",
    signatory_position_1: "",
    signatory_2: "",
    signatory_position_2: "",
    signatory_3: "",
    signatory_position_3: "",
    signatory_4: "",
    signatory_position_4: "",
    signatory_5: "",
    signatory_position_5: "",
  });

  // Dropdowns
  const divisions = ref([]);
  const months = ref([]);
  const years = ref([]);
  const availableSignatories = ref([]);
  const hazardRecords = ref([]);

  const periodsForDivision = (divisionId) => {
    if (!divisionId) return [];
    const id = Number(divisionId);
    return hazardRecords.value.filter(
      (r) => Number(r.resolved_division_id ?? r.division_id) === id,
    );
  };

  const applyDivisionPeriod = (divisionId) => {
    const periods = periodsForDivision(divisionId);
    if (!periods.length) {
      formData.month_id = "";
      formData.year = "";
      return;
    }

    const latest = [...periods].sort(
      (a, b) =>
        Number(b.year) - Number(a.year) ||
        Number(b.month_id) - Number(a.month_id),
    )[0];

    formData.month_id = Number(latest.month_id);
    formData.year = Number(latest.year);
  };

  const onDivisionChange = (divisionId) => {
    if (!divisionId) {
      formData.month_id = "";
      formData.year = "";
      return;
    }
    applyDivisionPeriod(divisionId);
  };

  const filteredMonths = computed(() => {
    if (!formData.division_id) return months.value;
    const monthIds = new Set(
      periodsForDivision(formData.division_id).map((p) => Number(p.month_id)),
    );
    return months.value.filter((m) => monthIds.has(Number(m.id)));
  });

  const filteredYears = computed(() => {
    if (!formData.division_id) return years.value;
    const yearIds = new Set(
      periodsForDivision(formData.division_id).map((p) => Number(p.year)),
    );
    return years.value.filter((y) => yearIds.has(Number(y.id)));
  });

  // Generate years from current year down to 2005
  const generateYears = () => {
    const currentYear = new Date().getFullYear();
    const minYear = 2005;
    const list = [];
    for (let y = currentYear; y >= minYear; y -= 1) {
      list.push({ id: y, name: String(y) });
    }
    years.value = list;
    // Don't auto-select year - let user choose
  };

  // Load initial data for Hazard Pay Report
  const loadInitialData = async () => {
    try {
      loading.value = true;
      error.value = null;

      const response = await hazardPayApi.getHazardPayReportData();
      const data = response.data?.data || {};

      divisions.value = divisionsFromApi(data) || [];
      hazardRecords.value = data.records ?? data.data ?? data.hazard ?? [];
      if (!Array.isArray(hazardRecords.value)) {
        hazardRecords.value = [];
      }

      if (divisions.value.length === 0) {
        ElMessage.warning(
          "No hazard pay records with employees found. Create a record under Payroll Benefits → Hazard Pay first.",
        );
      }

      months.value = normalizeMonths(data.months || []);
      generateYears();

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
      ElMessage.error(error.value);
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const validateBeforePrint = () => {
    if (!formData.division_id) {
      throw new Error("Division is required");
    }
    if (!formData.month_id || !formData.year) {
      throw new Error(
        "No hazard pay period found for the selected division. Choose another division or create hazard pay data first.",
      );
    }
  };

  // Map UI signatories (underscore) to controller expected parameters (no underscore)
  const buildPrintParams = () => ({
    ...divisionParams(formData),
    month_id: formData.month_id,
    year: formData.year,
    // Controller expects signatory1..signatory5 plus optional positions
    signatory1: signatories.signatory_1,
    signatory2: signatories.signatory_2,
    signatory3: signatories.signatory_3,
    signatory4: signatories.signatory_4,
    signatory5: signatories.signatory_5,
    signatory_position_1: signatories.signatory_position_1,
    signatory_position_2: signatories.signatory_position_2,
    signatory_position_3: signatories.signatory_position_3,
    signatory_position_4: signatories.signatory_position_4,
    signatory_position_5: signatories.signatory_position_5,
  });

  // Preview report (open PDF in new tab)
  const previewReport = async () => {
    try {
      loading.value = true;
      error.value = null;

      validateBeforePrint();

      const response =
        await hazardPayApi.generateHazardPayReport(buildPrintParams());

      const blob = response.data;
      if (!blob || blob.size === 0) {
        throw new Error("Generated PDF is empty - no hazard pay data found");
      }

      const url = window.URL.createObjectURL(blob);
      window.open(url, "_blank");
      setTimeout(() => window.URL.revokeObjectURL(url), 1000);

      ElMessage.success("Hazard Pay preview opened");
      return blob;
    } catch (err) {
      const message =
        err.response?.data?.message ||
        err.message ||
        "Failed to preview report";
      error.value = message;
      ElMessage.error(message);
      throw err;
    } finally {
      loading.value = false;
    }
  };

  // Download report
  const generateReport = async () => {
    try {
      loading.value = true;
      error.value = null;

      validateBeforePrint();

      const response =
        await hazardPayApi.generateHazardPayReport(buildPrintParams());
      const blob = response.data;
      if (!blob || blob.size === 0) {
        throw new Error("Generated PDF is empty - no hazard pay data found");
      }

      const url = window.URL.createObjectURL(blob);
      const link = document.createElement("a");
      link.href = url;
      link.download = `hazard-pay-report-${formData.division_id}-${formData.month_id}-${formData.year}-${new Date().toISOString().split("T")[0]}.pdf`;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);

      ElMessage.success("Report downloaded successfully");
      return blob;
    } catch (err) {
      const message =
        err.response?.data?.message ||
        err.message ||
        "Failed to generate report";
      error.value = message;
      ElMessage.error(message);
      throw err;
    } finally {
      loading.value = false;
    }
  };

  return {
    loading,
    error,
    formData,
    signatories,
    divisions,
    months,
    years,
    filteredMonths,
    filteredYears,
    availableSignatories,
    loadInitialData,
    onDivisionChange,
    previewReport,
    generateReport,
  };
}
