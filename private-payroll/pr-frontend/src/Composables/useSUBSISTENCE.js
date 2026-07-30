import { ref, reactive } from "vue";
import {
  divisionsFromApi,
  divisionParams,
  uniquePayPeriods,
} from "../utils/payrollReportDivisions.js";
import { subsistenceApi, signatoryApi } from "../services/api.js";
import { ElMessage } from "element-plus";

// Composable for Subsistence Report (PDF)
export function useSUBSISTENCE() {
  const loading = ref(false);
  const error = ref(null);

  const formData = reactive({
    payroll_period_id: "",
    division_id: "",
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

  const payrollPeriods = ref([]);
  const divisions = ref([]);
  const availableSignatories = ref([]);

  const loadInitialData = async () => {
    try {
      loading.value = true;
      error.value = null;

      const response = await subsistenceApi.getSubsistenceReportData();
      const data = response.data?.data || {};

      payrollPeriods.value = uniquePayPeriods(data.pay_periods);

      divisions.value = divisionsFromApi(data);

      // Load generic employee signatory options (name + position)
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

  const loadSignatories = async () => true;

  const buildPrintParams = () => ({
    payroll_interval_id: formData.payroll_period_id,
    ...divisionParams(formData),
    signatory_1: signatories.signatory_1,
    signatory_position_1: signatories.signatory_position_1,
    signatory_2: signatories.signatory_2,
    signatory_position_2: signatories.signatory_position_2,
    signatory_3: signatories.signatory_3,
    signatory_position_3: signatories.signatory_position_3,
    signatory_4: signatories.signatory_4,
    signatory_position_4: signatories.signatory_position_4,
    signatory_5: signatories.signatory_5,
    signatory_position_5: signatories.signatory_position_5,
  });

  const validateBeforePrint = () => {
    if (!formData.division_id) throw new Error("Department is required");
    if (!formData.payroll_period_id)
      throw new Error("Payroll Period is required");

    const requiredKeys = [
      "signatory_1",
      "signatory_position_1",
      "signatory_2",
      "signatory_position_2",
      "signatory_3",
      "signatory_position_3",
      "signatory_4",
      "signatory_position_4",
      "signatory_5",
      "signatory_position_5",
    ];
    for (const key of requiredKeys) {
      const value = (signatories[key] || "").toString().trim();
      if (!value) {
        throw new Error("All signatory names and positions are required");
      }
    }
  };

  const previewReport = async () => {
    try {
      loading.value = true;
      error.value = null;
      validateBeforePrint();

      const response =
        await subsistenceApi.generateSubsistenceReport(buildPrintParams());
      const blob = response.data;
      if (!blob || blob.size === 0) {
        throw new Error("Generated PDF is empty - no data found");
      }
      const url = window.URL.createObjectURL(blob);
      window.open(url, "_blank");
      setTimeout(() => window.URL.revokeObjectURL(url), 1000);
      ElMessage.success("Subsistence report preview opened");
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

  const generateReport = async () => {
    try {
      loading.value = true;
      error.value = null;
      validateBeforePrint();

      const response =
        await subsistenceApi.generateSubsistenceReport(buildPrintParams());
      const blob = response.data;
      if (!blob || blob.size === 0) {
        throw new Error("Generated PDF is empty - no data found");
      }
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement("a");
      link.href = url;
      link.download = `subsistence-${new Date().toISOString().split("T")[0]}.pdf`;
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
    payrollPeriods,
    divisions,
    availableSignatories,
    loadInitialData,
    loadSignatories,
    previewReport,
    generateReport,
  };
}

export default useSUBSISTENCE;
