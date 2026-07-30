import { ref } from "vue";
import { cosPayrollApi } from "../services/api.js";
import { ElMessage } from "element-plus";

export function useCOSPayrollPeriods() {
  const loading = ref(false);
  const periods = ref([]);

  const loadPeriods = async () => {
    try {
      loading.value = true;
      const response = await cosPayrollApi.getPeriods();
      const raw = response.data?.data || [];
      // Normalize `posted` to a real boolean so the UI
      // doesn't treat string "0"/"1" as truthy.
      periods.value = raw.map((p) => ({
        ...p,
        posted: Number(p.posted) === 1,
      }));
      return periods.value;
    } catch (error) {
      console.error("Error loading COS payroll periods:", error);
      ElMessage.error(
        error.response?.data?.message || "Failed to load COS payroll periods"
      );
      throw error;
    } finally {
      loading.value = false;
    }
  };

  return {
    loading,
    periods,
    loadPeriods,
  };
}

