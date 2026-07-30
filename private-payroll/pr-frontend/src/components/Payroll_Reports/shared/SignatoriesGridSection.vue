<template>
  <el-card class="signatories-card mt-3">
    <template #header>
      <div class="signatories-header">
        <span style="font-weight: 700; font-size: 17px">{{ title }}</span>
      </div>
    </template>

    <div class="signatories-content">
      <el-row :gutter="20" class="mb-4">
        <el-col
          v-for="section in sections"
          :key="section.index"
          :span="section.span ?? 12"
          :offset="section.offset ?? 0"
        >
          <div class="form-section" :style="{ minHeight: `${minHeight}px` }">
            <div class="certification-label">
              <strong>{{ section.title }}</strong>
            </div>
            <el-form-item
              label="Name:"
              label-width="60px"
              :required="!!section.required"
            >
              <el-select
                :model-value="signatories[`signatory_${section.index}`]"
                filterable
                clearable
                :placeholder="placeholder"
                @change="(val) => onSignatoryChange(section.index, val)"
              >
                <el-option
                  v-for="opt in employeeOptions"
                  :key="opt.id"
                  :label="opt.name"
                  :value="opt.name"
                />
              </el-select>
            </el-form-item>
            <el-form-item label="Position:" label-width="60px">
              <el-input
                :model-value="signatories[`signatory_position_${section.index}`]"
                disabled
              />
            </el-form-item>
          </div>
        </el-col>
      </el-row>
    </div>
  </el-card>
</template>

<script setup>
const props = defineProps({
  title: {
    type: String,
    default: "Signatories",
  },
  signatories: {
    type: Object,
    required: true,
  },
  employeeOptions: {
    type: Array,
    default: () => [],
  },
  sections: {
    type: Array,
    required: true,
  },
  placeholder: {
    type: String,
    default: "Select signatory",
  },
  minHeight: {
    type: Number,
    default: 200,
  },
});

const onSignatoryChange = (index, selectedName) => {
  const employee = (props.employeeOptions || []).find(
    (item) => item.name === selectedName,
  );

  if (!employee) {
    props.signatories[`signatory_${index}`] = selectedName || "";
    props.signatories[`signatory_position_${index}`] = "";
    return;
  }

  props.signatories[`signatory_${index}`] = employee.name;
  props.signatories[`signatory_position_${index}`] = employee.position || "";
};
</script>

<style scoped>
.signatories-card {
  border: 1px solid #e0e0e0;
  border-radius: 25px;
}

.signatories-header {
  font-weight: 500;
  color: #333;
}

.signatories-content {
  padding: 1rem;
}

.certification-label {
  padding: 0.5rem 0;
  color: #333;
  font-size: 0.875rem;
  line-height: 1.4;
}

.certification-label strong {
  font-weight: 600;
}

.form-section {
  margin-bottom: 0;
  background: white;
  border-radius: 15px;
  padding: 16px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  border: 1px solid #e9ecef;
  height: fit-content;
  display: flex;
  flex-direction: column;
}

.form-section .certification-label {
  margin-bottom: 16px;
  padding: 8px 0;
  border-bottom: 1px solid #f0f0f0;
  flex-shrink: 0;
}

.form-section .el-form-item {
  margin-bottom: 12px;
}

.form-section .el-form-item:last-child {
  margin-bottom: 0;
}

.mb-4 {
  margin-bottom: 1.5rem;
}

.mt-3 {
  margin-top: 1rem;
}

@media (max-width: 768px) {
  .signatories-content .el-row .el-col {
    margin-bottom: 16px;
  }

  .form-section {
    min-height: auto !important;
  }
}

:deep(.el-form-item__label) {
  font-weight: 500;
  color: #333;
}
</style>
