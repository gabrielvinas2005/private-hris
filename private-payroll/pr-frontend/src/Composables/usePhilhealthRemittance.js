import { ref, reactive } from "vue";
import { philhealthRemittanceApi } from "../services/api.js";
import { ElMessage } from "element-plus";

export function usePhilhealthRemittance() {
  const loading = ref(false);
  const error = ref(null);

  const formData = reactive({
    payroll_period_id: "",
  });

  const signatories = reactive({
    certified_correct: "",
    position: "",
    accountant_name: "",
    accountant_position: "",
    date: new Date().toISOString().split("T")[0],
  });

  const payrollPeriods = ref([]);
  const employeeOptions = ref([]);

  const loadInitialData = async () => {
    try {
      loading.value = true;
      error.value = null;
      const res = await philhealthRemittanceApi.getPhilhealthRemittanceData();
      const data = res.data.data || {};
      payrollPeriods.value = groupMonthlyPeriods(data.pay_periods || []);
      employeeOptions.value = data.employee_options || [];
      return data;
    } catch (err) {
      error.value = err.response?.data?.message || "Failed to load data";
      ElMessage.error(error.value);
      throw err;
    } finally {
      loading.value = false;
    }
  };

  // Preview report (opens in new tab)
  const previewReport = async () => {
    try {
      loading.value = true;
      error.value = null;

      if (!formData.payroll_period_id)
        throw new Error("Payroll Period is required");

      const selectedPeriod = payrollPeriods.value.find(
        (p) => p.id === formData.payroll_period_id
      );
      const payrollPeriod =
        selectedPeriod?.payroll_period_id || formData.payroll_period_id;
      const payrollPeriod2 = selectedPeriod?.payroll_period_id_2 || "";

      const payload = {
        payroll_period: payrollPeriod,
        payroll_period_2: payrollPeriod2,
        signatory: signatories.certified_correct,
        position: signatories.position,
        accountant_name: signatories.accountant_name,
        accountant_position: signatories.accountant_position,
        date: signatories.date,
      };

      const response =
        await philhealthRemittanceApi.generatePhilhealthRemittanceReport(
          payload
        );
      const blob = response.data;
      if (!blob || blob.size === 0) throw new Error("Generated PDF is empty");

      // Create blob URL and open in new tab
      const url = window.URL.createObjectURL(blob);
      window.open(url, "_blank");

      // Clean up the URL after a delay
      setTimeout(() => {
        window.URL.revokeObjectURL(url);
      }, 1000);

      ElMessage.success("PhilHealth Remittance preview opened");
    } catch (err) {
      const serverMessage = err.response?.data?.message || err.message;
      error.value = serverMessage || "Failed to preview report";
      if (err.response?.status === 404) {
        ElMessage.error(
          serverMessage ||
            "No PhilHealth remittance data found for the selected period"
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

      if (!formData.payroll_period_id)
        throw new Error("Payroll Period is required");

      const selectedPeriod = payrollPeriods.value.find(
        (p) => p.id === formData.payroll_period_id
      );
      const payrollPeriod =
        selectedPeriod?.payroll_period_id || formData.payroll_period_id;
      const payrollPeriod2 = selectedPeriod?.payroll_period_id_2 || "";

      const payload = {
        payroll_period: payrollPeriod,
        payroll_period_2: payrollPeriod2,
        signatory: signatories.certified_correct,
        position: signatories.position,
        accountant_name: signatories.accountant_name,
        accountant_position: signatories.accountant_position,
        date: signatories.date,
      };

      const response =
        await philhealthRemittanceApi.generatePhilhealthRemittanceReport(
          payload
        );
      const blob = response.data;
      if (!blob || blob.size === 0) throw new Error("Generated PDF is empty");

      const url = window.URL.createObjectURL(blob);
      const link = document.createElement("a");
      link.href = url;

      // Build friendly filename: philhealth-remittance-{PeriodName}-{Date}.pdf
      const periodObj = payrollPeriods.value.find(
        (p) => p.id === formData.payroll_period_id
      );
      const periodName =
        periodObj?.name || `period-${formData.payroll_period_id || "unknown"}`;
      const sanitize = (s) => String(s).replace(/[^A-Za-z0-9_\-()]+/g, "");
      const currentDate = new Date().toISOString().split("T")[0];
      link.download = `philhealth-remittance-${sanitize(periodName)}-${currentDate}.pdf`;

      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);
      ElMessage.success("PhilHealth Remittance report downloaded successfully");
    } catch (err) {
      const serverMessage = err.response?.data?.message || err.message;
      error.value = serverMessage || "Failed to generate report";
      if (err.response?.status === 404) {
        ElMessage.error(
          serverMessage ||
            "No PhilHealth remittance data found for the selected period"
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

  return {
    loading,
    error,
    formData,
    signatories,
    payrollPeriods,
    employeeOptions,
    loadInitialData,
    previewReport,
    generateReport,
  };
}

// Group Monthly periods into one option per month, preserving first and second half IDs.
function groupMonthlyPeriods(periods) {
  const groups = {};

  (periods || []).forEach((period) => {
    const name = period?.name || "";
    const lowerName = name.toLowerCase();

    if (!lowerName.includes("monthly")) {
      const id = period?.id;
      if (!id) return;
      groups[`__single__${id}`] = {
        ...period,
        id,
        payroll_period_id: id,
        payroll_period_id_2: "",
        cutoff_labels: period?.cutoff_name ? [period.cutoff_name] : [],
      };
      return;
    }

    const parsedDate = period?.release_date ? new Date(period.release_date) : null;
    const hasValidDate = parsedDate && !Number.isNaN(parsedDate.getTime());
    const monthYear = hasValidDate
      ? parsedDate.toLocaleDateString("en-PH", {
          month: "long",
          year: "numeric",
        })
      : name;
    const key = monthYear;

    if (!groups[key]) {
      groups[key] = {
        id: period.id,
        name: `Monthly (${monthYear})`,
        release_date: period.release_date,
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
      groups[key].first_half_id = period.id;
      groups[key].payroll_period_id = period.id;
    }
  });

  return Object.values(groups).map((group) => {
    if (typeof group.name === "string" && group.name.startsWith("Monthly (")) {
      const cutoff_labels = [];
      if (group.first_half_id && group.second_half_id) {
        cutoff_labels.push("First-Half", "Second-Half");
      } else if (group.first_half_id) {
        cutoff_labels.push(group.explicitFirstHalf ? "First-Half" : "MONTHLY");
      } else if (group.second_half_id) {
        cutoff_labels.push("Second-Half");
      }
      return {
        ...group,
        id: group.payroll_period_id,
        payroll_period_id: group.payroll_period_id,
        payroll_period_id_2: group.second_half_id || "",
        cutoff_labels,
      };
    }

    return group;
  });
}
