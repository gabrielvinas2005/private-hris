import { ref, reactive, watch } from "vue";
import { payslipReportApi, signatoryApi } from "../services/api.js";
import { ElMessage } from "element-plus";

export function usePayslipReport() {
  const loading = ref(false);
  const error = ref(null);

  const formData = reactive({
    payroll_interval_id: "",
    payroll_period_id: "",
    payroll_period_id_2: "",
    division_id: "",
    employee: "",
  });

  const signatories = reactive({
    certified_correct: "",
    position: "",
  });

  const payrollIntervals = ref([]);
  const payrollPeriods = ref([]);
  const divisions = ref([]);
  const employees = ref([]);
  const allEmployees = ref([]);
  const availableSignatories = ref([]);

  const loadInitialData = async () => {
    try {
      loading.value = true;
      error.value = null;
      const res = await payslipReportApi.getPayslipReportData();
      const data = res.data.data || {};
      payrollIntervals.value = data.intervals || [];
      divisions.value = data.divisions || data.departments || [];
      allEmployees.value =
        (data.employees && data.employees.data) || data.employees || [];
      filterEmployeesByDivision(formData.division_id);
      // preload payroll periods list if backend provided
      payrollPeriods.value = groupMonthlyPeriods(data.pay_periods || []);

      // Load employee options for signatory dropdown (reuse ATM Letter endpoint)
      try {
        const sigRes = await signatoryApi.getEmployeeOptions();
        const sdata = sigRes.data?.data || {};
        availableSignatories.value = sdata.employee_options || [];
      } catch {
        availableSignatories.value = [];
      }

      // Also preload periods list from backend if present
      // Otherwise, will be loaded based on interval
      return data;
    } catch (err) {
      error.value = err.response?.data?.message || "Failed to load data";
      ElMessage.error(error.value);
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const extractPeriodList = (response) => {
    const payload = response?.data;
    if (Array.isArray(payload)) return payload;
    if (Array.isArray(payload?.data)) return payload.data;
    return [];
  };

  const loadPayrollPeriods = async (intervalId) => {
    if (!intervalId) {
      payrollPeriods.value = [];
      formData.payroll_period_id = "";
      formData.payroll_period_id_2 = "";
      return;
    }
    if (!formData.employee) {
      payrollPeriods.value = [];
      formData.payroll_period_id = "";
      formData.payroll_period_id_2 = "";
      return;
    }
    try {
      loading.value = true;
      error.value = null;
      const resp = await payslipReportApi.getEmployeePeriods(
        formData.employee,
        intervalId,
      );
      const raw = extractPeriodList(resp);
      payrollPeriods.value = groupMonthlyPeriods(raw);
      if (payrollPeriods.value.length === 0 && raw.length > 0) {
        payrollPeriods.value = groupMonthlyPeriods(raw, { fallbackUngrouped: true });
      }
      if (payrollPeriods.value.length === 0) {
        ElMessage.warning(
          "No posted payroll periods found for this employee. Confirm payroll is posted and a summary exists for them.",
        );
      } else if (raw[0]?.payroll_interval_id) {
        const resolvedInterval = Number(raw[0].payroll_interval_id);
        if (
          resolvedInterval &&
          String(formData.payroll_interval_id) !== String(resolvedInterval)
        ) {
          formData.payroll_interval_id = resolvedInterval;
        }
      }
      formData.payroll_period_id = "";
      formData.payroll_period_id_2 = "";
    } catch (err) {
      error.value =
        err.response?.data?.message || "Failed to load payroll periods";
      ElMessage.error(error.value);
      payrollPeriods.value = [];
      throw err;
    } finally {
      loading.value = false;
    }
  };

  // Preview report (opens in new tab)
  const previewReport = async () => {
    // Open blank window synchronously to avoid popup blocker
    const previewWindow = window.open("", "_blank");
    if (!previewWindow) {
      ElMessage.error("Please allow popups for this site to preview reports");
      return;
    }

    try {
      loading.value = true;
      error.value = null;

      if (!formData.payroll_interval_id)
        throw new Error("Payroll Interval is required");
      if (!formData.payroll_period_id)
        throw new Error("Payroll Period is required");

      // If the selected option is a grouped month, translate into 1st/2nd half IDs.
      const selectedPeriod = payrollPeriods.value.find(
        (p) => p.id === formData.payroll_period_id
      );
      if (selectedPeriod) {
        formData.payroll_period_id =
          selectedPeriod.payroll_period_id || selectedPeriod.id;
        formData.payroll_period_id_2 = selectedPeriod.payroll_period_id_2 || "";
      } else {
        formData.payroll_period_id_2 = "";
      }

      // Show loading message in the preview window
      previewWindow.document.write(`
        <html>
          <head><title>Loading Payslip...</title></head>
          <body style="display: flex; justify-content: center; align-items: center; height: 100vh; font-family: Arial, sans-serif;">
            <div style="text-align: center;">
              <p>Generating payslip preview...</p>
              <p style="color: #666; font-size: 14px;">Please wait...</p>
            </div>
          </body>
        </html>
      `);

      const payload = { ...formData, ...signatories };
      const response = await payslipReportApi.printPayslipReport(payload);
      const blob = response.data;
      if (!blob || blob.size === 0) throw new Error("Generated PDF is empty");

      // Create blob URL and update the preview window
      const url = window.URL.createObjectURL(blob);
      previewWindow.location.href = url;

      // Don't revoke URL immediately - let the browser handle cleanup when tab closes
      // The URL will be automatically cleaned up when the window is closed

      ElMessage.success("Payslip preview opened");
    } catch (err) {
      // Close the preview window if there was an error
      if (previewWindow && !previewWindow.closed) {
        previewWindow.close();
      }
      const serverMessage = err.response?.data?.message || err.message;
      error.value = serverMessage || "Failed to preview report";
      if (err.response?.status === 404) {
        ElMessage.error(
          serverMessage || "No payslip data found for the selected criteria"
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

  // Generate report (downloads file)
  const generateReport = async () => {
    try {
      loading.value = true;
      error.value = null;

      if (!formData.payroll_interval_id)
        throw new Error("Payroll Interval is required");
      if (!formData.payroll_period_id)
        throw new Error("Payroll Period is required");

      // If the selected option is a grouped month, translate into 1st/2nd half IDs.
      const selectedPeriod = payrollPeriods.value.find(
        (p) => p.id === formData.payroll_period_id
      );
      if (selectedPeriod) {
        formData.payroll_period_id =
          selectedPeriod.payroll_period_id || selectedPeriod.id;
        formData.payroll_period_id_2 = selectedPeriod.payroll_period_id_2 || "";
      } else {
        formData.payroll_period_id_2 = "";
      }

      const payload = { ...formData, ...signatories };
      const response = await payslipReportApi.printPayslipReport(payload);
      const blob = response.data;
      if (!blob || blob.size === 0) throw new Error("Generated PDF is empty");

      const url = window.URL.createObjectURL(blob);
      const link = document.createElement("a");
      link.href = url;
      // Build friendly filename: payslip-{EmployeeName}-{PeriodName}.pdf
      const empObj = employees.value.find((e) => e.id === formData.employee);
      const empName = empObj?.name || `emp-${formData.employee || "unknown"}`;
      const periodObj = payrollPeriods.value.find(
        (p) => p.id === formData.payroll_period_id
      );
      const periodName =
        periodObj?.name || `period-${formData.payroll_period_id || "unknown"}`;
      const sanitize = (s) => String(s).replace(/[^A-Za-z0-9_\-()]+/g, "");
      link.download = `payslip-${sanitize(empName)}-${sanitize(periodName)}.pdf`;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);
      ElMessage.success("Payslip downloaded successfully");
    } catch (err) {
      const serverMessage = err.response?.data?.message || err.message;
      error.value = serverMessage || "Failed to generate report";
      if (err.response?.status === 404) {
        ElMessage.error(
          serverMessage || "No payslip data found for the selected criteria"
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

  // Filter employees by selected division (cascading dropdown)
  const filterEmployeesByDivision = (divisionId) => {
    const source = allEmployees.value || [];
    if (!divisionId) {
      employees.value = [];
      return;
    }
    employees.value = source.filter(
      (e) => String(e.division_id) === String(divisionId),
    );
  };

  const applyEmployeePayrollInterval = (employeeId) => {
    if (!employeeId) return;
    const emp = allEmployees.value.find(
      (e) => String(e.id) === String(employeeId),
    );
    const empInterval = Number(emp?.payroll_interval_id);
    if (empInterval > 0) {
      formData.payroll_interval_id = empInterval;
    }
  };

  const onDivisionChange = (divisionId) => {
    formData.division_id = divisionId ?? "";
    filterEmployeesByDivision(formData.division_id);
    formData.employee = "";
    payrollPeriods.value = [];
    formData.payroll_period_id = "";
    formData.payroll_period_id_2 = "";
  };

  watch(
    () => formData.division_id,
    (newVal) => {
      filterEmployeesByDivision(newVal);
      if (
        formData.employee &&
        !employees.value.some((e) => String(e.id) === String(formData.employee))
      ) {
        formData.employee = "";
      }
    },
  );

  watch(
    () => formData.employee,
    async (employeeId) => {
      if (!employeeId) {
        payrollPeriods.value = [];
        formData.payroll_period_id = "";
        formData.payroll_period_id_2 = "";
        return;
      }
      applyEmployeePayrollInterval(employeeId);
      if (formData.payroll_interval_id) {
        await loadPayrollPeriods(formData.payroll_interval_id);
      }
    },
  );

  watch(
    () => formData.payroll_interval_id,
    async (intervalId, prevIntervalId) => {
      if (!formData.employee || !intervalId) {
        if (!intervalId) {
          payrollPeriods.value = [];
          formData.payroll_period_id = "";
          formData.payroll_period_id_2 = "";
        }
        return;
      }
      if (String(intervalId) !== String(prevIntervalId)) {
        await loadPayrollPeriods(intervalId);
      }
    },
  );

  return {
    loading,
    error,
    formData,
    signatories,
    payrollIntervals,
    payrollPeriods,
    divisions,
    onDivisionChange,
    employees,
    availableSignatories,
    loadInitialData,
    loadPayrollPeriods,
    previewReport,
    generateReport,
  };
}

const normalizeCutoffName = (name) => String(name || "").trim();
const cutoffSortKey = (name) => {
  const s = String(name || "").toLowerCase();
  if (s.includes("1st") || s.includes("first")) return 1;
  if (s.includes("2nd") || s.includes("second")) return 2;
  if (s.includes("monthly")) return 3;
  return 9;
};

const cutoffLabelFromName = (name) => {
  const s = String(name || "").toLowerCase();
  if (s.includes("1st") || s.includes("first")) return "1st Half";
  if (s.includes("2nd") || s.includes("second")) return "2nd Half";
  if (s.includes("monthly")) return "Monthly";
  return normalizeCutoffName(name);
};

// One option per release month; carries 1st/2nd half period IDs when available.
function groupMonthlyPeriods(periods, options = {}) {
  const { fallbackUngrouped = false } = options;
  const byMonth = new Map();

  for (const period of periods || []) {
    const id = period?.id;
    if (!id) continue;

    const d = period?.release_date ? new Date(period.release_date) : null;
    if (!d || Number.isNaN(d.getTime())) {
      byMonth.set(`__single__${id}`, {
        ...period,
        id,
        name: period.name || `Period ${id}`,
        payroll_period_id: id,
        payroll_period_id_2: "",
        cutoff_labels: period.cutoff_name ? [period.cutoff_name] : [],
        release_date: period.release_date,
      });
      continue;
    }

    const ym = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}`;
    const key = `${period.payroll_interval_id ?? ""}|${ym}`;
    if (!byMonth.has(key)) byMonth.set(key, []);
    byMonth.get(key).push(period);
  }

  const out = [];
  for (const entry of byMonth.values()) {
    if (!Array.isArray(entry)) {
      out.push(entry);
      continue;
    }

    const sorted = [...entry].sort((a, b) => Number(b.id) - Number(a.id));
    const rep = sorted[0];
    const d = new Date(rep.release_date);
    const monthYear = d.toLocaleDateString("en-PH", {
      month: "long",
      year: "numeric",
    });

    let firstHalfId = null;
    let secondHalfId = null;
    const cutoffLabels = [];

    for (const p of sorted) {
      const label = cutoffLabelFromName(p.cutoff_name || p.name);
      const lower = label.toLowerCase();
      if (lower.includes("1st") || lower.includes("first")) {
        firstHalfId = p.id;
      } else if (lower.includes("2nd") || lower.includes("second")) {
        secondHalfId = p.id;
      } else if (!firstHalfId) {
        firstHalfId = p.id;
      }
      if (label) cutoffLabels.push(label);
    }

    const uniqueCutoffs = Array.from(new Set(cutoffLabels)).sort(
      (a, b) => cutoffSortKey(a) - cutoffSortKey(b) || a.localeCompare(b),
    );

    const payrollPeriodId = firstHalfId || rep.id;
    out.push({
      id: payrollPeriodId,
      name: `Monthly (${monthYear})`,
      release_date: rep.release_date,
      payroll_period_id: payrollPeriodId,
      payroll_period_id_2: secondHalfId && secondHalfId !== payrollPeriodId ? secondHalfId : "",
      first_half_id: firstHalfId,
      second_half_id: secondHalfId,
      cutoff_labels: uniqueCutoffs,
      cutoff_name: uniqueCutoffs[0] || "",
    });
  }

  out.sort((a, b) => {
    const da = a.release_date ? new Date(a.release_date).getTime() : 0;
    const db = b.release_date ? new Date(b.release_date).getTime() : 0;
    return db - da;
  });

  if (out.length === 0 && fallbackUngrouped) {
    return (periods || []).map((p) => ({
      ...p,
      id: p.id,
      payroll_period_id: p.id,
      payroll_period_id_2: "",
      cutoff_labels: p.cutoff_name ? [p.cutoff_name] : [],
    }));
  }

  return out;
}
