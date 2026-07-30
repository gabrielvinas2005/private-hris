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
              <strong>Extra Bonus Type:</strong>
            </div>
            <el-select
              v-model="formData.extra_bonus_type_id"
              placeholder="Select Extra Bonus Type"
              style="width: 100%"
              clearable
            >
              <el-option
                v-for="extraBonusType in extraBonusTypes"
                :key="extraBonusType.id"
                :label="extraBonusType.name"
                :value="extraBonusType.id"
              />
            </el-select>
          </div>
        </el-col>

        <el-col :span="12">
          <div class="form-field-section">
            <div class="field-label">
              <strong>Year:</strong>
            </div>
            <el-select
              v-model="formData.year"
              placeholder="Select Year"
              style="width: 100%"
              clearable
            >
              <el-option
                v-for="year in years"
                :key="year.id"
                :label="year.name"
                :value="year.id"
              />
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
              <el-option
                v-for="division in divisions"
                 :key="division.id"
                 :label="division.name"
                 :value="division.id"
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
  divisions: {
    type: Array,
    default: () => [],
  },
  extraBonusTypes: {
    type: Array,
    default: () => [],
  },
  years: {
    type: Array,
    default: () => [],
  },
});
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
