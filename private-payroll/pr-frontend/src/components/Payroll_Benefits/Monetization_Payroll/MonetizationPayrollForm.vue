<template>
  <div class="monetization-form-container">
    <div class="form-header">
      <h3>
        {{
          localForm.id
            ? "Edit Monetization Payroll"
            : "Create Monetization Payroll"
        }}
      </h3>
      <el-button @click="handleClose" plain> Back to List </el-button>
    </div>

    <div class="dialog-description">
      Select the month and year for the monetization payroll header.
      You can add employees after saving.
    </div>

    <el-form
      ref="formRef"
      :model="localForm"
      :rules="rules"
      label-width="140px"
      label-position="left"
      class="monetization-form"
      :disabled="loading"
    >
      <div class="form-row">
        <!-- <el-form-item label="Branch" prop="branch_id" required>
          <el-select
            v-model="localForm.branch_id"
            placeholder="Select branch"
            filterable
            style="width: 280px"
          >
            <el-option
              v-for="b in branches"
              :key="b.id"
              :label="b.name"
              :value="b.id"
            />
          </el-select>
        </el-form-item> -->

        <el-form-item label="Month" prop="month_id" required>
          <el-select
            v-model="localForm.month_id"
            placeholder="Select month"
            style="width: 280px"
          >
            <el-option
              v-for="m in months"
              :key="m.id"
              :label="m.name"
              :value="m.id"
            />
          </el-select>
        </el-form-item>

        <el-form-item label="Year" prop="year_id" required>
          <el-select
            v-model="localForm.year_id"
            placeholder="Select Year"
            style="width: 180px"
          >
            <el-option
              v-for="year in yearOptions"
              :key="year"
              :label="year"
              :value="year"
            />
          </el-select>
        </el-form-item>
      </div>
    </el-form>

    <div class="form-footer">
      <el-button @click="handleClose" :disabled="loading">Cancel</el-button>
      <el-button type="primary" :loading="loading" @click="saveHeader">
        {{ localForm.id ? "Update" : "Create" }}
      </el-button>
    </div>
  </div>
</template>

<script setup>
import { computed, reactive, ref, watch } from "vue";

const props = defineProps({
  form: { type: Object, required: true },
  branches: { type: Array, default: () => [] },
  months: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
});

const emit = defineEmits(["save-header", "close"]);

const currentYear = new Date().getFullYear();
const minYear = currentYear - 5;
const maxYear = currentYear + 5;

const localForm = reactive({
  id: 0,
  branch_id: null,
  month_id: null,
  year_id: currentYear,
});

watch(
  () => props.form,
  (val) => {
    localForm.id = val.id || 0;
    localForm.branch_id = val.branch_id || null;
    localForm.month_id = val.month_id || null;
    localForm.year_id = val.year_id || currentYear;
  },
  { immediate: true, deep: true },
);

const formRef = ref();

const rules = {
  month_id: [
    {
      required: true,
      message: "Month is required",
      trigger: "change",
    },
  ],
  year_id: [
    {
      required: true,
      message: "Year is required",
      trigger: "change",
    },
  ],
};

const yearOptions = computed(() => {
  const years = [];
  for (let y = minYear; y <= maxYear; y += 1) {
    years.push(y);
  }
  return years;
});

const saveHeader = () => {
  if (!formRef.value) {
    emit("save-header", { ...localForm });
    return;
  }
  formRef.value.validate((valid) => {
    if (!valid) return;
    emit("save-header", { ...localForm });
  });
};

const handleClose = () => {
  emit("close");
};
</script>

<style scoped>
.monetization-form-container {
  background: #ffffff;
  border-radius: 12px;
  padding: 24px;
  margin-bottom: 20px;
  box-shadow:
    0 1px 3px 0 rgba(0, 0, 0, 0.1),
    0 1px 2px 0 rgba(0, 0, 0, 0.06);
  border: 1px solid #e5e7eb;
}

.form-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
  padding-bottom: 12px;
  border-bottom: 1px solid #e5e7eb;
}

.form-header h3 {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
  color: #374151;
}

.dialog-description {
  margin-bottom: 12px;
  font-size: 13px;
  color: #6b7280;
}

.form-row {
  display: flex;
  gap: 20px;
  margin-top: 8px;
}

.form-row .el-form-item {
  flex: 1;
}

.monetization-form :deep(.el-form-item) {
  margin-bottom: 14px;
}

.form-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  padding-top: 16px;
  border-top: 1px solid #e5e7eb;
  margin-top: 16px;
}

.form-footer .el-button {
  border-radius: 8px;
  font-weight: 600;
  padding: 8px 18px;
}

@media (max-width: 768px) {
  .monetization-form-container {
    padding: 16px;
  }

  .form-row {
    flex-direction: column;
    gap: 12px;
  }

  .form-footer {
    flex-direction: column;
  }

  .form-footer .el-button {
    width: 100%;
  }
}
</style>
