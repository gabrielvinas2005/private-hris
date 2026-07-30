<template>
  <el-card class="signatories-card mt-3">
    <template #header>
      <div class="signatories-header">
        <span class="signatories-title">Signatories</span>
      </div>
    </template>

    <div class="signatories-content">
      <el-row :gutter="20">
        <el-col :span="12" :xs="24">
          <div class="form-section">
            <div class="certification-label">
              <strong>Certifying Officer</strong>
              <p class="certification-copy">
                I CERTIFY on my official oath that the above Payroll is correct
                and that the services have been duly rendered as stated.
              </p>
            </div>
            <el-form-item label="Name">
              <el-select
                v-model="signatories.signatory_1"
                filterable
                clearable
                allow-create
                default-first-option
                placeholder="Search or type employee name"
                @change="(value) => handleNameChange(1, value)"
                style="width: 100%"
              >
                <el-option
                  v-for="option in signatoryOptions"
                  :key="option.id"
                  :label="option.name"
                  :value="option.name"
                >
                  <span>{{ option.name }}</span>
                </el-option>
              </el-select>
            </el-form-item>
            <el-form-item label="Position">
              <el-input
                v-model="signatories.signatory_position_1"
                placeholder="Position will auto-fill when employee is selected"
                disabled
              />
            </el-form-item>
          </div>
        </el-col>

        <el-col :span="12" :xs="24">
          <div class="form-section">
            <div class="certification-label">
              <strong>Approving Officer</strong>
              <p class="certification-copy">
                APPROVED, payable for appropriation for Php
                ________________________.
              </p>
            </div>
            <el-form-item label="Name">
              <el-select
                v-model="signatories.signatory_2"
                filterable
                clearable
                allow-create
                default-first-option
                placeholder="Search or type employee name"
                @change="(value) => handleNameChange(2, value)"
                style="width: 100%"
              >
                <el-option
                  v-for="option in signatoryOptions"
                  :key="option.id"
                  :label="option.name"
                  :value="option.name"
                >
                  <span>{{ option.name }}</span>
                </el-option>
              </el-select>
            </el-form-item>
            <el-form-item label="Position">
              <el-input
                v-model="signatories.signatory_position_2"
                placeholder="Position will auto-fill when employee is selected"
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
import { watch } from "vue";

const props = defineProps({
  signatories: {
    type: Object,
    required: true,
  },
  signatoryOptions: {
    type: Array,
    default: () => [],
  },
});

const handleNameChange = (slot, value) => {
  if (!value) {
    props.signatories[`signatory_position_${slot}`] = "";
    return;
  }

  // Check if the name matches an employee option
  const matchingEmployee = props.signatoryOptions.find(
    (opt) => opt.name === value
  );

  if (matchingEmployee) {
    // Auto-fill position when employee is selected
    props.signatories[`signatory_position_${slot}`] =
      matchingEmployee.position || "";
  }
  // If it doesn't match, user typed a custom name - position stays as is
};

// Watch for external changes to sync position if name matches an employee
watch(
  () => [
    props.signatoryOptions,
    props.signatories.signatory_1,
    props.signatories.signatory_2,
  ],
  () => {
    [1, 2].forEach((slot) => {
      const currentName = props.signatories[`signatory_${slot}`];
      const currentPosition = props.signatories[`signatory_position_${slot}`];

      if (currentName && !currentPosition) {
        // Only auto-fill if position is empty
        const matchingEmployee = props.signatoryOptions.find(
          (opt) => opt.name === currentName
        );
        if (matchingEmployee) {
          props.signatories[`signatory_position_${slot}`] =
            matchingEmployee.position || "";
        }
      }
    });
  },
  { deep: true }
);
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

.signatories-title {
  font-weight: 700;
  font-size: 17px;
}

.signatories-content {
  padding: 1rem;
}

.form-section {
  background: white;
  border-radius: 15px;
  padding: 16px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  border: 1px solid #e9ecef;
  min-height: 220px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.certification-label {
  color: #333;
  font-size: 0.9rem;
  line-height: 1.4;
}

.certification-copy {
  margin: 4px 0 0;
  color: #5f6368;
  font-size: 0.85rem;
}

:deep(.el-form-item) {
  margin-bottom: 8px;
}

:deep(.el-form-item__label) {
  font-weight: 500;
  color: #333;
}

:deep(.el-select) {
  width: 100%;
}

.mt-3 {
  margin-top: 1rem;
}

@media (max-width: 768px) {
  .form-section {
    margin-bottom: 16px;
  }
}
</style>
