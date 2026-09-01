import { ref } from "vue";
import axios from "axios";
import { ElMessage, ElMessageBox } from "element-plus";

const API = import.meta.env.VITE_API_URL || "";

export function useFinalPay() {
  const loading    = ref(false);
  const computing  = ref(false);
  const employees  = ref([]);   // separated employees pending final pay
  const breakdowns = ref([]);   // computed breakdown per employee

  // ── Load separated employees (linked from offboarding) ────────────────

  async function fetchSeparatedEmployees(params = {}) {
    try {
      loading.value = true;
      const { data } = await axios.get(`${API}/api/final-pay/employees`, { params });
      employees.value = data?.data ?? data ?? [];
    } catch (e) {
      ElMessage.error("Failed to load separated employees.");
    } finally {
      loading.value = false;
    }
  }

  // ── Compute final pay for a specific employee ─────────────────────────

  async function computeFinalPay(employeeId, payload = {}) {
    try {
      computing.value = true;
      const { data } = await axios.post(`${API}/api/final-pay/compute/${employeeId}`, payload);
      ElMessage.success("Final pay computed successfully.");
      return { success: true, data: data?.data };
    } catch (e) {
      ElMessage.error(e?.response?.data?.message ?? "Computation failed.");
      return { success: false };
    } finally {
      computing.value = false;
    }
  }

  // ── Approve / release final pay ────────────────────────────────────────

  async function releaseFinalPay(employeeId) {
    try {
      await ElMessageBox.confirm(
        "Release and post final pay for this employee? This action cannot be undone.",
        "Confirm Release",
        { confirmButtonText: "Release", cancelButtonText: "Cancel", type: "warning" },
      );
      computing.value = true;
      const { data } = await axios.post(`${API}/api/final-pay/release/${employeeId}`);
      ElMessage.success(data?.message ?? "Final pay released.");
      return true;
    } catch (e) {
      if (e !== "cancel") ElMessage.error(e?.response?.data?.message ?? "Release failed.");
      return false;
    } finally {
      computing.value = false;
    }
  }

  // ── Generate payslip / report ─────────────────────────────────────────

  async function generatePayslip(employeeId) {
    try {
      const response = await axios.get(`${API}/api/final-pay/${employeeId}/payslip`, {
        responseType: "blob",
      });
      const url  = URL.createObjectURL(new Blob([response.data], { type: "application/pdf" }));
      const link = Object.assign(document.createElement("a"), {
        href: url, download: `final_pay_payslip_${employeeId}.pdf`,
      });
      document.body.appendChild(link);
      link.click();
      link.remove();
      URL.revokeObjectURL(url);
    } catch (e) {
      ElMessage.error("Failed to generate final pay payslip.");
    }
  }

  return {
    loading, computing, employees, breakdowns,
    fetchSeparatedEmployees, computeFinalPay, releaseFinalPay, generatePayslip,
  };
}
