import { ref } from "vue";
import axios from "axios";
import { ElMessage, ElMessageBox } from "element-plus";

const API = import.meta.env.VITE_API_URL || "";

export function useThirteenthMonth() {
  const loading     = ref(false);
  const processing  = ref(false);
  const records     = ref([]);
  const payrollPeriods = ref([]);
  const departments = ref([]);
  const summary     = ref(null);

  // ── Fetch helpers ──────────────────────────────────────────────────────

  async function fetchPayrollPeriods() {
    try {
      loading.value = true;
      const { data } = await axios.get(`${API}/api/payroll-periods`);
      payrollPeriods.value = data?.data ?? data ?? [];
    } catch (e) {
      ElMessage.error("Failed to load payroll periods.");
    } finally {
      loading.value = false;
    }
  }

  async function fetchDepartments() {
    try {
      const { data } = await axios.get(`${API}/api/departments`);
      departments.value = data?.data ?? data ?? [];
    } catch (e) {
      console.error("Failed to load departments:", e);
    }
  }

  async function fetchRecords(params = {}) {
    try {
      loading.value = true;
      const { data } = await axios.get(`${API}/api/thirteenth-month-pay`, { params });
      records.value  = data?.data ?? data ?? [];
      summary.value  = data?.summary ?? null;
    } catch (e) {
      ElMessage.error("Failed to load 13th month pay records.");
    } finally {
      loading.value = false;
    }
  }

  // ── Compute ────────────────────────────────────────────────────────────

  async function computeThirteenthMonth(payload) {
    try {
      processing.value = true;
      await ElMessageBox.confirm(
        `Generate 13th month pay for ${payload.year}? This will compute based on the total basic salary earned for the calendar year.`,
        "Confirm Computation",
        { confirmButtonText: "Compute", cancelButtonText: "Cancel", type: "warning" },
      );
      const { data } = await axios.post(`${API}/api/thirteenth-month-pay/compute`, payload);
      ElMessage.success(data?.message ?? "13th month pay computed successfully.");
      await fetchRecords({ year: payload.year, department_id: payload.department_id });
      return { success: true, data: data?.data };
    } catch (e) {
      if (e !== "cancel") {
        ElMessage.error(e?.response?.data?.message ?? "Computation failed.");
      }
      return { success: false };
    } finally {
      processing.value = false;
    }
  }

  // ── Post / Unpost ──────────────────────────────────────────────────────

  async function postRecords(ids) {
    try {
      processing.value = true;
      const { data } = await axios.post(`${API}/api/thirteenth-month-pay/post`, { ids });
      ElMessage.success(data?.message ?? "Posted successfully.");
      return true;
    } catch (e) {
      ElMessage.error(e?.response?.data?.message ?? "Post failed.");
      return false;
    } finally {
      processing.value = false;
    }
  }

  // ── Export ─────────────────────────────────────────────────────────────

  async function exportPdf(params = {}) {
    try {
      const response = await axios.get(`${API}/api/thirteenth-month-pay/export/pdf`, {
        params, responseType: "blob",
      });
      const url  = URL.createObjectURL(new Blob([response.data], { type: "application/pdf" }));
      const link = Object.assign(document.createElement("a"), { href: url, download: `13th_month_pay_${params.year ?? ""}.pdf` });
      document.body.appendChild(link);
      link.click();
      link.remove();
      URL.revokeObjectURL(url);
    } catch (e) {
      ElMessage.error("Failed to export PDF.");
    }
  }

  async function exportExcel(params = {}) {
    try {
      const response = await axios.get(`${API}/api/thirteenth-month-pay/export/excel`, {
        params, responseType: "blob",
      });
      const url  = URL.createObjectURL(new Blob([response.data]));
      const link = Object.assign(document.createElement("a"), { href: url, download: `13th_month_pay_${params.year ?? ""}.xlsx` });
      document.body.appendChild(link);
      link.click();
      link.remove();
      URL.revokeObjectURL(url);
    } catch (e) {
      ElMessage.error("Failed to export Excel.");
    }
  }

  return {
    loading, processing, records, payrollPeriods, departments, summary,
    fetchPayrollPeriods, fetchDepartments, fetchRecords,
    computeThirteenthMonth, postRecords, exportPdf, exportExcel,
  };
}
