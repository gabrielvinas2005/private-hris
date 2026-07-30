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
              <strong>RATA Payroll:</strong>
            </div>
            <el-select
              v-model="formData.rata_payroll_id"
              placeholder="Select RATA Payroll"
              style="width: 100%"
              clearable
              @change="handleRATAPayrollChange"
            >
              <el-option
                v-for="payroll in rataPayrolls"
                :key="payroll.id"
                :label="payroll.name"
                :value="payroll.id"
              />
            </el-select>
          </div>
        </el-col>
      </el-row>
    </div>
  </el-card>
</template>

<script setup>
const props = defineProps({
  formData: {
    type: Object,
    required: true,
  },
  rataPayrolls: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(["rata-payroll-change"]);

const handleRATAPayrollChange = () => {
  emit("rata-payroll-change", props.formData.rata_payroll_id);
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
</style>
