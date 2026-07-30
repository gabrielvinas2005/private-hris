<template>
  <div class="container">
    <el-form label-width="140px" class="centered-form">
      <el-row :gutter="16" justify="center">
        <el-col :span="12">
          <el-card class="groupCard">
            <div class="groupHeader">Report Information</div>

            <el-form-item label="Payroll Period" required>
              <el-select
                v-model="localForm.payroll_period_id"
                placeholder="Select Period"
                required
              >
                <el-option
                  v-for="p in payrollPeriods"
                  :key="p.id"
                  :label="getPeriodLabel(p)"
                  :value="p.id"
                >
                  <div class="flex justify-between items-center">
                    <span>
                      {{ getPeriodLabel(p) }}
                      <PayrollCutoffTag
                        v-for="t in p.cutoff_labels ||
                        (p.cutoff_name ? [p.cutoff_name] : [])"
                        :key="t"
                        :name="t"
                      />
                    </span>
                  </div>
                </el-option>
              </el-select>
            </el-form-item>

            <!-- Contribution report does not require selecting a loan type -->
          </el-card>
        </el-col>
      </el-row>
    </el-form>
  </div>
</template>

<script setup>
import { reactive, watch } from "vue";
import PayrollCutoffTag from "@/components/Shared/PayrollCutoffTag.vue";

const props = defineProps({
  formData: { type: Object, required: true },
  signatories: { type: Object, required: true },
  payrollPeriods: { type: Array, default: () => [] },
  membershipPrograms: { type: Array, default: () => [] },
});

const emit = defineEmits(["update:formData", "update:signatories"]);

const localForm = reactive({ ...props.formData });
const localSignatories = reactive({ ...props.signatories });

const getPeriodLabel = (period) => {
  if (!period) return "";
  if (period.name && typeof period.name === "string") {
    return period.name;
  }
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

watch(
  () => localSignatories,
  () => {
    emit("update:signatories", { ...localSignatories });
  },
  { deep: true },
);

watch(
  () => props.signatories,
  (val) => {
    Object.assign(localSignatories, val || {});
  },
  { deep: true },
);

// No interval change handling needed for bank remittance
</script>

<style scoped>
.container {
  display: flex;
  justify-content: center;
  width: 100%;
}

.centered-form {
  width: 100%;
}

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
  margin-bottom: 16px;
  padding: 8px 0;
  border-bottom: 1px solid #f0f0f0;
  flex-shrink: 0;
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
