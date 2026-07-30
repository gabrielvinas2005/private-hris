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
              <strong>Loyalty Award Period:</strong>
            </div>
            <el-select
              v-model="formData.payroll_period_id"
              placeholder="Select Loyalty Award Period"
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
                    <PayrollCutoffTag :name="period.cutoff_name" />
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
  font-size: 0.875rem;
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
