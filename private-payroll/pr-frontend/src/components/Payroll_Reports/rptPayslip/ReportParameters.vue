<template>
  <div>
    <el-form label-width="140px">
      <el-row :gutter="16">
        <el-col :span="12">
          <el-card class="groupCard">
            <div class="groupHeader">Employee Filter</div>

            <el-form-item label="Division">
              <el-select
                v-model="localForm.division_id"
                placeholder="Select Division"
                clearable
                @change="onDivisionChange"
              >
                <el-option
                  v-for="d in divisions"
                  :key="d.id"
                  :label="d.name"
                  :value="d.id"
                />
              </el-select>
            </el-form-item>

            <el-form-item label="Employee">
              <el-select
                v-model="localForm.employee"
                placeholder="Select Employee"
                filterable
                clearable
                :disabled="!localForm.division_id"
                @change="onEmployeeChange"
              >
                <el-option
                  v-for="e in employees"
                  :key="e.id"
                  :label="e.name"
                  :value="e.id"
                />
              </el-select>
            </el-form-item>
          </el-card>
        </el-col>

        <el-col :span="12">
          <el-card class="groupCard">
            <div class="groupHeader">Payroll Period</div>

            <el-form-item label="Payroll Interval" required>
              <el-select
                v-model="localForm.payroll_interval_id"
                placeholder="Select Interval"
                @change="onIntervalChange"
              >
                <el-option
                  v-for="i in payrollIntervals"
                  :key="i.id"
                  :label="i.name"
                  :value="i.id"
                />
              </el-select>
            </el-form-item>

            <el-form-item label="Payroll Period" required>
              <el-select
                v-model="localForm.payroll_period_id"
                placeholder="Select Period"
                :disabled="!localForm.employee || !localForm.payroll_interval_id"
                filterable
                clearable
              >
                <el-option
                  v-for="p in payrollPeriods"
                  :key="p.id"
                  :value="p.id"
                  :label="getPeriodLabel(p)"
                >
                  <div class="periodOption">
                    <span class="periodLabel">{{ getPeriodLabel(p) }}</span>
                    <span
                      class="periodTags"
                      v-if="
                        (p.cutoff_labels && p.cutoff_labels.length) ||
                        p.cutoff_name
                      "
                    >
                      <PayrollCutoffTag
                        v-for="t in p.cutoff_labels ||
                        (p.cutoff_name ? [p.cutoff_name] : [])"
                        :key="t"
                        :name="t"
                        size="small"
                      />
                    </span>
                  </div>
                </el-option>
              </el-select>
            </el-form-item>
          </el-card>
        </el-col>
      </el-row>
    </el-form>
  </div>
</template>

<script setup>
import { reactive, watch } from "vue";
import PayrollCutoffTag from "../../Shared/PayrollCutoffTag.vue";

const props = defineProps({
  formData: { type: Object, required: true },
  payrollIntervals: { type: Array, default: () => [] },
  payrollPeriods: { type: Array, default: () => [] },
  divisions: { type: Array, default: () => [] },
  employees: { type: Array, default: () => [] },
});

const emit = defineEmits([
  "update:formData",
  "interval-change",
  "division-change",
  "employee-change",
]);

const localForm = reactive({ ...props.formData });

watch(
  () => localForm,
  () => {
    emit("update:formData", { ...localForm });
  },
  { deep: true },
);

watch(
  () => props.formData,
  (val) => {
    Object.assign(localForm, val || {});
  },
  { deep: true },
);

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
  return period.name || period.release_date || "";
};

const onIntervalChange = () => {
  localForm.payroll_period_id = "";
  emit("interval-change");
};

const onDivisionChange = (divisionId) => {
  localForm.employee = "";
  localForm.payroll_period_id = "";
  emit("division-change", divisionId);
};

const onEmployeeChange = () => {
  localForm.payroll_period_id = "";
  emit("employee-change");
};
</script>

<style scoped>
.groupCard {
  border: 1px solid #e0e0e0;
  border-radius: 25px;
}

.groupHeader {
  font-weight: 600;
  color: #333;
  padding: 0.5rem 0;
  font-size: 1rem;
  line-height: 1.4;
}

.groupCard {
  border: 1px solid #e0e0e0;
  border-radius: 25px;
}

.groupCard .groupHeader {
  margin-bottom: 16px;
  padding: 8px 0;
  border-bottom: 1px solid #f0f0f0;
  flex-shrink: 0;
}

.periodOption {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.periodLabel {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.periodTags {
  display: inline-flex;
  align-items: center;
  flex-wrap: nowrap;
}
</style>
