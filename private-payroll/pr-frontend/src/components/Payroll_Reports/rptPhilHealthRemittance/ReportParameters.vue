<template>
  <div class="container">
    <el-form label-width="140px" class="centered-form">
      <el-row :gutter="16" justify="center">
        <el-col :span="12">
          <el-card class="groupCard">
            <div class="groupHeader">Payroll Period</div>
            <el-form-item label="Payroll Period" required>
              <el-select
                v-model="localForm.payroll_period_id"
                placeholder="Select Period"
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

            <el-divider />

            <div class="groupHeader">Signatories</div>
            <el-form-item label="Certified Correct">
              <el-select
                v-model="selectedSignatoryId"
                filterable
                clearable
                placeholder="Select signatory"
                @change="handleSignatorySelect"
              >
                <el-option
                  v-for="option in employeeOptions"
                  :key="option.id"
                  :label="option.name"
                  :value="option.id"
                />
              </el-select>
            </el-form-item>
            <el-form-item label="Position/Designation">
              <el-input v-model="localSignatories.position" disabled />
            </el-form-item>
            <el-form-item label="Accountant">
              <el-input
                v-model="localSignatories.accountant_name"
                placeholder="Enter accountant name"
              />
            </el-form-item>
            <el-form-item label="Accountant Position">
              <el-input
                v-model="localSignatories.accountant_position"
                placeholder="Enter accountant position"
              />
            </el-form-item>
            <el-form-item label="Date">
              <el-date-picker
                v-model="localSignatories.date"
                type="date"
                value-format="YYYY-MM-DD"
                format="MMMM DD, YYYY"
                placeholder="Select date"
                style="width: 100%"
              />
            </el-form-item>
          </el-card>
        </el-col>
      </el-row>
    </el-form>
  </div>
</template>

<script setup>
import { reactive, ref, watch } from "vue";
import PayrollCutoffTag from "@/components/Shared/PayrollCutoffTag.vue";

const props = defineProps({
  formData: { type: Object, required: true },
  payrollPeriods: { type: Array, default: () => [] },
  signatories: { type: Object, required: true },
  employeeOptions: { type: Array, default: () => [] },
});

const emit = defineEmits(["update:formData", "update:signatories"]);

const localForm = reactive({ ...props.formData });
const localSignatories = reactive({
  certified_correct: props.signatories.certified_correct || "",
  position: props.signatories.position || "",
  accountant_name: props.signatories.accountant_name || "",
  accountant_position: props.signatories.accountant_position || "",
  date: props.signatories.date || "",
});
const selectedSignatoryId = ref(null);

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

const handleSignatorySelect = (value) => {
  if (!value) {
    selectedSignatoryId.value = null;
    localSignatories.certified_correct = "";
    localSignatories.position = "";
    return;
  }

  const selected = props.employeeOptions.find((option) => option.id === value);
  selectedSignatoryId.value = value;
  localSignatories.certified_correct = selected?.name || "";
  localSignatories.position = selected?.position || "";
};

const syncSelectedSignatory = () => {
  if (!localSignatories.certified_correct) {
    selectedSignatoryId.value = null;
    return;
  }

  const option = props.employeeOptions.find(
    (opt) => opt.name === localSignatories.certified_correct,
  );
  selectedSignatoryId.value = option ? option.id : null;
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
    syncSelectedSignatory();
  },
  { deep: true },
);

watch(
  () => props.employeeOptions,
  () => {
    syncSelectedSignatory();
  },
  { deep: true, immediate: true },
);
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
