<template>
  <div class="report-parameters">
    <h4>Report Parameters</h4>

    <el-row :gutter="20">
      <el-col :span="12">
        <el-form-item label="Division" required>
          <el-select
            v-model="formData.division_id"
            placeholder="Select Division"
            style="width: 100%"
            filterable
            clearable
            @change="handleFormDataUpdate"
          >
            <el-option
              v-for="division in divisions"
              :key="division.id"
              :label="division.name"
              :value="division.id"
            />
          </el-select>
        </el-form-item>
      </el-col>

      <el-col :span="12">
        <el-form-item label="Payroll Period" required>
          <el-select
            v-model="formData.payroll_period_id"
            placeholder="Select Payroll Period"
            style="width: 100%"
            filterable
            clearable
            @change="handleFormDataUpdate"
          >
            <el-option
              v-for="period in displayPayrollPeriods"
              :key="period.id"
              :label="getPeriodLabel(period)"
              :value="period.id"
            >
              <div class="flex justify-between items-center">
                <span>
                  {{ getPeriodLabel(period) }}
                  <PayrollCutoffTag
                    v-for="name in period.cutoff_names || []"
                    :key="name"
                    :name="name"
                  />
                </span>
              </div>
            </el-option>
          </el-select>
        </el-form-item>
      </el-col>
    </el-row>
  </div>
</template>

<script setup>
import { computed, watch } from "vue";
import PayrollCutoffTag from "@/components/Shared/PayrollCutoffTag.vue";

const props = defineProps({
  formData: {
    type: Object,
    required: true,
  },
  divisions: {
    type: Array,
    default: () => [],
  },
  payrollPeriods: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(["update:formData"]);

const normalizeCutoffName = (name) => String(name || "").trim();
const cutoffSortKey = (name) => {
  const s = String(name || "").toLowerCase();
  if (s.includes("1st") || s.includes("first")) return 1;
  if (s.includes("2nd") || s.includes("second")) return 2;
  if (s.includes("monthly")) return 3;
  return 9;
};

const displayPayrollPeriods = computed(() => {
  const periods = Array.isArray(props.payrollPeriods) ? props.payrollPeriods : [];
  const byMonth = new Map();

  for (const p of periods) {
    const d = p?.release_date ? new Date(p.release_date) : null;
    if (!d || Number.isNaN(d.getTime())) continue;

    const ym = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}`;
    const key = `${p.payroll_interval_id ?? ""}|${ym}`;
    if (!byMonth.has(key)) byMonth.set(key, []);
    byMonth.get(key).push(p);
  }

  const out = [];
  for (const list of byMonth.values()) {
    const sorted = [...list].sort((a, b) => Number(b.id) - Number(a.id));
    const rep = sorted[0];
    const cutoffNames = sorted
      .map((x) => normalizeCutoffName(x.cutoff_name))
      .filter(Boolean);
    const uniqueCutoffs = Array.from(new Set(cutoffNames)).sort(
      (a, b) => cutoffSortKey(a) - cutoffSortKey(b) || a.localeCompare(b),
    );

    out.push({
      id: rep.id,
      name: rep.name,
      release_date: rep.release_date,
      cutoff_names: uniqueCutoffs,
    });
  }

  out.sort((a, b) => new Date(b.release_date) - new Date(a.release_date));
  return out;
});

const getPeriodLabel = (period) => {
  if (!period) return "";
  if (period.release_date) {
    const date = new Date(period.release_date);
    if (!Number.isNaN(date.getTime())) {
      const monthYear = date.toLocaleDateString("en-PH", {
        month: "long",
        year: "numeric",
      });
      return `Monthly (${monthYear})`;
    }
  }
  return period.name || "";
};

const handleFormDataUpdate = () => {
  emit("update:formData", props.formData);
};

watch(
  () => props.formData,
  (newVal) => {
    emit("update:formData", newVal);
  },
  { deep: true },
);
</script>

<style scoped>
.report-parameters {
  margin-bottom: 20px;
}

.report-parameters h4 {
  margin-bottom: 15px;
  color: #333;
  font-weight: 500;
}

.flex {
  display: flex;
}
.justify-between {
  justify-content: space-between;
}
.items-center {
  align-items: center;
}
</style>
