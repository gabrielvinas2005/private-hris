<template>
  <PageScaffold
    title="ATM Letter for Mid-Year Bonus"
    subtitle="Generate ATM letter for Mid-Year Bonus with pre-inputted signatory and personnel information"
    :breadcrumbs="[
      { label: 'Payroll Module', to: '/' },
      { label: 'Payroll Reports', to: '/payroll-reports' },
      { label: 'Mid-Year Bonus Reports', to: '/mid-year-bonus-report-hub' },
      { label: 'ATM Letter for Mid-Year Bonus' },
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
        <li v-for="e in errors" :key="e">{{ e }}</li>
        <li v-if="backendError">{{ backendError }}</li>
      </ul>
    </el-alert>

    <el-card class="box-card">
      <template #header>
        <div class="card-header">
          <span>ATM Letter for Mid-Year Bonus</span>
        </div>
      </template>

      <el-form @submit.prevent="previewReport" label-width="140px">
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
            @click="generatePdfReport"
            :loading="loading"
            size="large"
          >
            <el-icon><Download /></el-icon>
            Download PDF
          </el-button>
        </div>

        <el-divider />

        <ReportParameters
          :form-data="formData"
           :divisions="divisions"
          :years="years"
          @update:formData="onUpdateFormData"
        />

        <el-divider />

        <SignatoriesSection
          :signatories="signatories"
          :placeholders="defaultSignatoryPlaceholders"
          :employee-options="employeeOptions"
          @update:signatories="onUpdateSignatories"
        />

        <el-divider />

        <div class="card-footer">
          <h6>ATM LETTER FOR MID-YEAR BONUS</h6>
        </div>
      </el-form>
    </el-card>
  </PageScaffold>
</template>

<script setup>
import { ref, onMounted, computed } from "vue";
import PageScaffold from "../../components/PageScaffold.vue";
import { Document, Download } from "@element-plus/icons-vue";
import ReportParameters from "../../components/Payroll_Reports/rptATMMidYearBonus/ReportParameters.vue";
import SignatoriesSection from "../../components/Payroll_Reports/rptATMMidYearBonus/SignatoriesSection.vue";
import { useATMMidYearBonus } from "../../Composables/useATMMidYearBonus.js";

const {
  loading,
  error,
  formData,
  signatories,
  defaultSignatoryPlaceholders,
  divisions,
  years,
  employeeOptions,
  loadInitialData,
  previewReport,
  generatePdfReport,
} = useATMMidYearBonus();

const errors = ref([]);
const backendError = computed(() => error.value);
const hasErrors = computed(
  () => errors.value.length > 0 || !!backendError.value
);

const clearErrors = () => {
  errors.value = [];
};

const onUpdateFormData = (newVal) => {
  Object.assign(formData, newVal || {});
};

const onUpdateSignatories = (newVal) => {
  Object.assign(signatories, newVal || {});
};

onMounted(async () => {
  try {
    await loadInitialData();
  } catch (err) {
    errors.value.push(err.message || "Failed to load initial data");
  }
});
</script>

<style scoped>
.mb-3 {
  margin-bottom: 1rem;
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
</style>
