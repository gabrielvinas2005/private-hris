import { ref, reactive } from "vue";
import { pagIbigContributionApi } from "../services/api.js";
import { ElMessage } from "element-plus";

export function usePagIbigContribution() {
  const loading = ref(false);
  const error = ref(null);

  const formData = reactive({
    payroll_period_id: "",
    deduction: "", // No default value - let user select
  });

  const signatories = reactive({
    signatory: "",
    accsignatory: "",
    position: "",
    date: new Date().toISOString().split("T")[0],
  });

  const payrollPeriods = ref([]);
  const membershipPrograms = ref([]);
  const employeeOptions = ref([]);

  const loadInitialData = async () => {
    try {
      loading.value = true;
      error.value = null;

      // Use the new PagIbigContributionController report method
      const res = await pagIbigContributionApi.getPagIbigContributionData();
      const data = res.data.data || {};

      payrollPeriods.value = groupMonthlyPeriods(data.pay_periods || []);
      membershipPrograms.value = [];
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
      // No contribution type required for contribution report
      if (!signatories.signatory)
        throw new Error("Authorized Representative is required");

      if (!signatories.position)
        throw new Error("Position/Designation is required");

      const selectedPeriod = payrollPeriods.value.find(
        (p) => p.id === formData.payroll_period_id,
      );
      const payrollPeriod =
        selectedPeriod?.payroll_period_id || formData.payroll_period_id;
      const payrollPeriod2 = selectedPeriod?.payroll_period_id_2 || "";

      const payload = {
        payroll_period: payrollPeriod,
        payroll_period_2: payrollPeriod2,
        signatory: signatories.signatory,
        position: signatories.position,
        date: signatories.date,
      };

      console.log("Preview Payload being sent:", payload);

      // Use the mandatory print method from PagIbigLoanController
      const response =
        await pagIbigContributionApi.generatePagIbigContributionReport(payload);

      const blob = response.data;
      if (!blob || blob.size === 0) throw new Error("Generated PDF is empty");

      // Create blob URL and open in new tab
      const url = window.URL.createObjectURL(blob);
      window.open(url, "_blank");

      // Clean up the URL after a delay
      setTimeout(() => {
        window.URL.revokeObjectURL(url);
      }, 1000);

      ElMessage.success("Pag-IBIG Contribution Report preview opened");
    } catch (err) {
      console.error("Preview Report Error:", err);
      console.error("Response data:", err.response?.data);

      let serverMessage = err.message;

      // Try to extract error message from blob response
      if (err.response?.data instanceof Blob) {
        try {
          const text = await err.response.data.text();
          console.error("Blob response text:", text);
          const parsed = JSON.parse(text);
          serverMessage = parsed.message || parsed.error || text;
        } catch (e) {
          console.error("Could not parse blob response:", e);
          serverMessage = "Server returned an error";
        }
      } else {
        serverMessage = err.response?.data?.message || err.message;
      }

      error.value = serverMessage || "Failed to preview report";
      ElMessage.error(`Preview failed: ${serverMessage}`);
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
      // No contribution type required for contribution report
      if (!signatories.signatory)
        throw new Error("Authorized Representative is required");
      if (!signatories.position)
        throw new Error("Position/Designation is required");

      const selectedPeriod = payrollPeriods.value.find(
        (p) => p.id === formData.payroll_period_id,
      );
      const payrollPeriod =
        selectedPeriod?.payroll_period_id || formData.payroll_period_id;
      const payrollPeriod2 = selectedPeriod?.payroll_period_id_2 || "";

      const payload = {
        payroll_period: payrollPeriod,
        payroll_period_2: payrollPeriod2,
        signatory: signatories.signatory,
        accsignatory: signatories.accsignatory,
        position: signatories.position,
        date: signatories.date,
      };

      console.log("Generate Payload being sent:", payload);

      // Use the mandatory print method from PagIbigLoanController
      const response =
        await pagIbigContributionApi.generatePagIbigContributionReport(payload);

      const blob = response.data;
      if (!blob || blob.size === 0) throw new Error("Generated PDF is empty");

      const url = window.URL.createObjectURL(blob);
      const link = document.createElement("a");
      link.href = url;

      // Build friendly filename
      const periodObj = payrollPeriods.value.find(
        (p) => p.id === formData.payroll_period_id,
      );
      const periodName =
        periodObj?.name || `period-${formData.payroll_period_id || "unknown"}`;
      const sanitize = (s) => String(s).replace(/[^A-Za-z0-9_\-()]+/g, "");
      const currentDate = new Date().toISOString().split("T")[0];
      link.download = `pagibig-contribution-report-${sanitize(periodName)}-${currentDate}.pdf`;

      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);
      ElMessage.success("Pag-IBIG Contribution Report downloaded successfully");
    } catch (err) {
      console.error("Generate Report Error:", err);
      console.error("Response data:", err.response?.data);

      let serverMessage = err.message;

      // Try to extract error message from blob response
      if (err.response?.data instanceof Blob) {
        try {
          const text = await err.response.data.text();
          console.error("Blob response text:", text);
          const parsed = JSON.parse(text);
          serverMessage = parsed.message || parsed.error || text;
        } catch (e) {
          console.error("Could not parse blob response:", e);
          serverMessage = "Server returned an error";
        }
      } else {
        serverMessage = err.response?.data?.message || err.message;
      }

      error.value = serverMessage || "Failed to generate report";
      ElMessage.error(`Download failed: ${serverMessage}`);
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
    membershipPrograms,
    employeeOptions,
    loadInitialData,
    previewReport,
    generateReport,
  };
}

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

    const parsedDate = period?.release_date
      ? new Date(period.release_date)
      : null;
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
