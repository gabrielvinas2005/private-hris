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
        <el-col :span="12">
          <div class="form-field-section">
            <div class="field-label">
              <strong>Division:</strong>
            </div>
            <el-select
              v-model="formData.division_id"
              placeholder="Select Division"
              style="width: 100%"
              clearable
              @change="handleDivisionChange"
            >
              <el-option
                v-for="division in divisions"
                :key="division.id"
                :label="division.name"
                :value="division.id"
              />
            </el-select>
          </div>
        </el-col>

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
  divisions: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(["interval-change", "division-change"]);

const normalizeCutoffName = (name) => String(name || "").trim();
const cutoffSortKey = (name) => {
  const s = String(name || "").toLowerCase();
  if (s.includes("1st") || s.includes("first")) return 1;
  if (s.includes("2nd") || s.includes("second")) return 2;
  if (s.includes("monthly")) return 3;
  return 9;
};

// One option per month; 1st/2nd Half tags shown inline
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

const handleDivisionChange = () => {
  emit("division-change", props.formData.division_id);
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
  font-size: 0.875rem;
  margin-bottom: 4px;
}

.field-label strong {
  font-weight: 600;
}

.mb-4 {
  margin-bottom: 1.5rem;
}

:deep(.el-select) {
  width: 100%;
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
