<template>
  <PageScaffold
    title="Mid Year Bonus Report"
    subtitle="Generate mid-year bonus reports"
    :breadcrumbs="[
      { label: 'Payroll Module', to: '/' },
      { label: 'Payroll Reports', to: '/payroll-reports' },
      { label: 'Mid-Year Bonus Reports', to: '/mid-year-bonus-report-hub' },
      { label: 'Mid Year Bonus Payroll Report' },
    ]"
  >
    <el-alert
      v-if="hasErrors"
      title="Error!"
      type="error"
      :closable="true"
      @close="clearErrors"
      class="mb-3"
    >
      <ul>
        <li v-for="error in errors" :key="error">{{ error }}</li>
        <li v-if="error">{{ error }}</li>
      </ul>
    </el-alert>

    <el-alert
      v-if="warningMessage"
      title="Warning!"
      type="warning"
      :closable="true"
      @close="warningMessage = ''"
      class="mb-3"
    >
      {{ warningMessage }}
    </el-alert>

    <!-- Main Form Card -->
    <el-card class="box-card">
      <template #header>
        <div class="card-header">
          <span>Mid Year Bonus Report</span>
        </div>
      </template>

      <el-form @submit.prevent="previewReport" label-width="120px">
        <div class="form-actions mb-3">
          <el-button
            type="success"
            @click="previewReport"
            :loading="loading"
            size="large"
            class="mr-3"
          >
            <el-icon><Document /></el-icon>
            Preview Report
          </el-button>
          <el-button
            type="primary"
            @click="generateReport"
            :loading="loading"
            size="large"
          >
            <el-icon><Download /></el-icon>
            Download Report
          </el-button>
        </div>

        <el-divider />

        <!-- Report Parameters Component -->
        <ReportParameters
          :form-data="formData"
          :divisions="divisions"
          :years="years"
          @year-change="handleYearChange"
          @division-change="handleDivisionChange"
        />

        <!-- Signatories Section Component -->
        <SignatoriesSection
          :signatories="signatories"
          :signatory-options="signatoryOptions"
        />

        <el-divider />

        <div class="card-footer">
          <h6>Mid Year Bonus Report</h6>
        </div>
      </el-form>
    </el-card>
  </PageScaffold>
</template>

<script setup>
import { ref, onMounted, computed } from "vue";
import { useMidYearBonusReport } from "../../Composables/useMidYearBonusReport.js";
import { Document, Download } from "@element-plus/icons-vue";
import PageScaffold from "../../components/PageScaffold.vue";
import ReportParameters from "../../components/Payroll_Reports/rptMidYearBonus/ReportParameters.vue";
import SignatoriesSection from "../../components/Payroll_Reports/rptMidYearBonus/SignatoriesSection.vue";

// Use the composable
const {
  loading,
  error,
  formData,
  signatories,
  branches,
  divisions,
  years,
  signatoryOptions,
  loadInitialData,
  loadSignatories,
  generateReport,
  previewReport,
} = useMidYearBonusReport();

// Local state
const errors = ref([]);
const warningMessage = ref("");

// Computed properties
const hasErrors = computed(() => errors.value.length > 0 || !!error.value);

// Methods
const clearErrors = () => {
  errors.value = [];
};

const removeCookie = () => {
  // Implementation for removing cookie if needed
  console.log("Removing cookie...");
};

const handleYearChange = async () => {
  // No need for year change handling in mid-year bonus
  console.log("Year change not applicable for mid-year bonus");
};

const handleDivisionChange = async () => {
  if (formData.branch_id) {
    await loadSignatories(formData.branch_id);
  }
};

// Lifecycle
onMounted(async () => {
  try {
    await loadInitialData();

    // Auto-load signatories when a branch is already selected or only one branch exists
    if (!formData.branch_id && branches.value.length === 1) {
      formData.branch_id = branches.value[0].id;
      await loadSignatories(formData.branch_id);
    } else if (formData.branch_id) {
      await loadSignatories(formData.branch_id);
    }
  } catch (err) {
    errors.value.push(err.message || "Failed to load initial data");
  }
});
</script>

<style scoped>
.mb-3 {
  margin-bottom: 1rem;
}

.mb-4 {
  margin-bottom: 1.5rem;
}

.mt-4 {
  margin-top: 1.5rem;
}

.box-card {
  box-shadow:
    0 0 1px rgba(0, 0, 0, 0.125),
    0 1px 3px rgba(0, 0, 0, 0.2);
  border: 1px solid #dee2e6;
  border-radius: 25px;
}

.card-header {
  border-bottom: 1px solid #dee2e6;
  padding: 0.75rem 1.25rem;
  font-weight: 500;
  color: #333;
}

.form-actions {
  display: flex;
  justify-content: flex-start;
  align-items: center;
  padding: 1rem 0;
  gap: 1rem;
}

.card-footer {
  background-color: #f8f9fa;
  border-top: 1px solid #dee2e6;
  padding: 0.75rem 1.25rem;
  margin-top: 1rem;
}

.card-footer h6 {
  margin: 0;
  font-size: 0.875rem;
  color: #6c757d;
  font-weight: 500;
}

:deep(.el-card__body) {
  padding: 1.25rem;
}

:deep(.el-form-item__label) {
  font-weight: 500;
  color: #333;
}

:deep(.el-select) {
  width: 100%;
}

:deep(.el-input__inner) {
  border-radius: 4px;
}

:deep(.el-input__inner:focus) {
  border-color: #80bdff;
  box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

:deep(.el-button--primary) {
  background-color: #007bff;
  border-color: #007bff;
}

:deep(.el-button--primary:hover) {
  background-color: #0056b3;
  border-color: #004085;
}

:deep(.el-alert) {
  border-radius: 4px;
}

:deep(.el-alert--error) {
  background-color: #f8d7da;
  border-color: #f5c6cb;
  color: #721c24;
}

:deep(.el-alert--warning) {
  background-color: #fff3cd;
  border-color: #ffeaa7;
  color: #856404;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .content {
    padding: 0 0.5rem;
  }
}
</style>
