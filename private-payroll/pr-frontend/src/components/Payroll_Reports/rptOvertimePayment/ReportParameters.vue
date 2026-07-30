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
              <strong>Payroll Interval:</strong>
            </div>
            <el-select
              v-model="formData.payroll_interval_id"
              @change="handleIntervalChange"
              placeholder="Select Payroll Interval"
              style="width: 100%"
              clearable
            >
              <el-option
                v-for="interval in payrollIntervals"
                :key="interval.id"
                :label="interval.name"
                :value="interval.id"
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
                v-for="period in payrollPeriods"
                :key="period.id"
                :label="getPeriodLabel(period)"
                :value="period.id"
              >
                <div class="flex justify-between items-center">
                  <span>
                    {{ getPeriodLabel(period) }}
                    <PayrollCutoffTag
                      v-for="t in period.cutoff_labels ||
                      (period.cutoff_name ? [period.cutoff_name] : [])"
                      :key="t"
                      :name="t"
                    />
                  </span>
                </div>
              </el-option>
            </el-select>
          </div>
        </el-col>
      </el-row>

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
            >
              <el-option label="All Division" value="all" />
              <el-option
                v-for="division in sortedDivisions"
                 :key="division.id"
                 :label="division.name"
                 :value="division.id"
              />
            </el-select>
          </div>
        </el-col>

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
  payrollIntervals: {
    type: Array,
    default: () => [],
  },
  payrollPeriods: {
    type: Array,
    default: () => [],
  },
  branches: {
    type: Array,
    default: () => [],
  },
  divisions: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(["interval-change"]);

const sortedDivisions = computed(() => {
  const list = Array.isArray(props.divisions) ? [...props.divisions] : [];
  const filtered = list.filter(
    (d) => d && String(d.id ?? "") !== "all" && String(d.name ?? "") !== "",
  );
  filtered.sort((a, b) =>
    String(a.name || "").localeCompare(String(b.name || ""), "en", {
      sensitivity: "base",
    }),
  );
  return filtered;
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
  // Fallback: if backend sends "Monthly (Month YYYY - First-Half)", strip the cutoff text
  const raw = String(period.name || "");
  // Remove " - <anything>Half" inside the parentheses, keep the closing ')'
  const stripped = raw.replace(
    /\s*-\s*(?:first|second|1st|2nd)[^)]+half\s*\)/i,
    ")",
  );
  return stripped || raw;
};

const handleIntervalChange = () => {
  emit("interval-change", props.formData.payroll_interval_id);
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
