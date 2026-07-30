import { ref, reactive, computed } from "vue";
import { divisionsFromApi, divisionParams } from "../utils/payrollReportDivisions.js";
import { reimbursementCommunicationApi, signatoryApi } from "../services/api.js";
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

/** Normalize API month rows so el-select value/label types match (id as number). */
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

export function useReimbursementCommunicationReport() {
  const loading = ref(false);
  const error = ref(null);

  const formData = reactive({
    division_id: "",
    month_id: "",
    year: "",
  });

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

  const divisions = ref([]);
  const months = ref([]);
  const years = ref([]);
  const availableSignatories = ref([]);
  const benefitRecords = ref([]);

  const periodsForDivision = (divisionId) => {
    if (!divisionId) return [];
    const id = Number(divisionId);
    return benefitRecords.value.filter(
      (r) => Number(r.resolved_division_id ?? r.division_id) === id,
    );
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

  const generateYears = () => {
    const currentYear = new Date().getFullYear();
    const minYear = 2005;
    const list = [];
    for (let y = currentYear; y >= minYear; y -= 1) {
      list.push({ id: y, name: y.toString() });
    }
    years.value = list;
  };

  const generateMonths = () => {
    months.value = normalizeMonths([]);
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

  const loadInitialData = async () => {
    try {
      loading.value = true;
      error.value = null;

      const response =
        await reimbursementCommunicationApi.getReimbursementCommunicationReportData();
      const data = response.data?.data || {};

      divisions.value = divisionsFromApi(data) || [];
      benefitRecords.value = data.records ?? data.data ?? [];
      if (!Array.isArray(benefitRecords.value)) {
        benefitRecords.value = [];
      }

      if (divisions.value.length === 0) {
        ElMessage.warning(
          "No payroll communication Macco records with employees found. Create a record under Payroll Benefits → Payroll Communication Macco first.",
        );
      }

      months.value = normalizeMonths(data.months || []);

      generateYears();

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
      if (months.value.length === 0) {
        generateMonths();
      }
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const validateBeforePrint = () => {
    if (!formData.division_id) throw new Error("Division is required");
    if (!formData.month_id) throw new Error("Month is required");
    if (!formData.year) throw new Error("Year is required");
  };

  const buildPrintParams = () => ({
    ...divisionParams(formData),
    month_id: formData.month_id,
    year: formData.year,
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

  const previewReport = async () => {
    try {
      loading.value = true;
      error.value = null;

      validateBeforePrint();
      const params = buildPrintParams();
      const response =
        await reimbursementCommunicationApi.generateReimbursementCommunicationReport(
          params,
        );

      const blob = response.data;
      if (!blob || blob.size === 0) {
        throw new Error(
          "Generated PDF is empty - no payroll communication Macco data found",
        );
      }

      const url = window.URL.createObjectURL(blob);
      window.open(url, "_blank");
      setTimeout(() => window.URL.revokeObjectURL(url), 1000);

      ElMessage.success("Report preview opened successfully");
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

  const generateReport = async () => {
    try {
      loading.value = true;
      error.value = null;

      validateBeforePrint();
      const params = buildPrintParams();
      const response =
        await reimbursementCommunicationApi.generateReimbursementCommunicationReport(
          params,
        );

      const blob = response.data;
      if (!blob || blob.size === 0) {
        throw new Error(
          "Generated PDF is empty - no payroll communication Macco data found",
        );
      }

      const url = window.URL.createObjectURL(blob);
      const link = document.createElement("a");
      link.href = url;
      link.download = `reimbursement_communication_macco_${params.division_id}_${params.month_id}_${params.year}_${new Date().toISOString().split("T")[0]}.pdf`;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);

      ElMessage.success("Report generated successfully");
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
    benefitRecords,
    loadInitialData,
    onDivisionChange,
    applyDivisionPeriod,
    previewReport,
    generateReport,
  };
}
