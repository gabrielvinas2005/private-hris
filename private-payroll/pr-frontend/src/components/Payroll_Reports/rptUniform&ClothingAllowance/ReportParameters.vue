<template>
  <el-card class="form-section-card mt-3">
    <template #header>
      <div class="section-header">
        <span style="font-weight: 700; font-size: 17px"
          >Report Information</span
        >
      </div>
    </template>

    <div class="form-fields-content">
      <el-row :gutter="20" class="mb-4">
        <!-- <el-col :span="12">
          <div class="form-field-section">
            <div class="field-label">
              <strong>Branch:</strong>
            </div>
            <el-select
              v-model="formData.branch_id"
              placeholder="Select Branch"
              style="width: 100%"
              clearable
              @change="handleBranchChange"
            >
              <el-option
                v-for="branch in branches"
                :key="branch.id"
                :label="branch.name"
                :value="branch.id"
              />
            </el-select>
          </div>
        </el-col> -->

        <el-col :span="12">
          <div class="form-field-section">
            <div class="field-label">
              <strong>Payroll Period:</strong>
            </div>
            <el-select
              v-model="formData.payroll_period_id"
              placeholder="Select Payroll Period"
              style="width: 100%"
              clearable
            >
              <el-option
                v-for="period in displayPayrollPeriods"
                :key="period.id"
                :label="getPeriodLabel(period)"
                :value="period.id"
              >
                <div class="period-option-row">
                  <span class="period-option-label">{{ getPeriodLabel(period) }}</span>
                  <span class="cutoff-tags-inline">
                    <PayrollCutoffTag
                      v-for="name in period.cutoff_names || []"
                      :key="name"
                      :name="name"
                    />
                  </span>
                </div>
              </el-option>
            </el-select>
          </div>
        </el-col>
      </el-row>
    </div>
  </el-card>
</template>

<script setup>
import { computed } from "vue";
import PayrollCutoffTag from "@/components/Shared/PayrollCutoffTag.vue";

const props = defineProps({
  formData: {
    type: Object,
    required: true,
  },
  payrollPeriods: {
    type: Array,
    default: () => [],
  },
  branches: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(["interval-change", "branch-change"]);

const normalizeCutoffName = (name) => String(name || "").trim();
const cutoffSortKey = (name) => {
  const s = String(name || "").toLowerCase();
  if (s.includes("1st") || s.includes("first")) return 1;
  if (s.includes("2nd") || s.includes("second")) return 2;
  if (s.includes("monthly")) return 3;
  return 9;
};

// One dropdown row per month; both cutoffs shown as tags on the same line
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

const handleIntervalChange = () => {
  emit("interval-change", props.formData.payroll_interval_id);
};

const handleBranchChange = () => {
  emit("branch-change", props.formData.branch_id);
};
</script>

<style scoped>
.form-section-card {
  border: 1px solid #e9ecef;
  border-radius: 15px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.section-header {
  font-weight: 500;
  color: #333;
  font-size: 1rem;
}

.form-fields-content {
  padding: 1rem;
}

.form-field-section {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.field-label {
  font-weight: 500;
  color: #333;
  font-size: 1rem;
  margin-bottom: 4px;
}

.field-label strong {
  font-weight: 600;
}

.mb-4 {
  margin-bottom: 1.5rem;
}

.mt-3 {
  margin-top: 1rem;
}

:deep(.el-select) {
  width: 100%;
}

.period-option-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  width: 100%;
}
.period-option-label {
  flex-shrink: 0;
}
.cutoff-tags-inline {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  flex-shrink: 0;
}
</style>
