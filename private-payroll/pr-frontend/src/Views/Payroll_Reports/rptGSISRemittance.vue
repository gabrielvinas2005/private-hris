<template>
  <PageScaffold
    title="GSIS Remittance Report"
    subtitle="Generate GSIS contribution remittance reports"
    :breadcrumbs="[
      { label: 'Payroll Module', to: '/' },
      { label: 'Payroll Reports', to: '/payroll-reports' },
      { label: 'GSIS Remittance Report' },
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
          <span>GSIS Remittance Information</span>
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
            @click="generateReport"
            :loading="loading"
            size="large"
          >
            <el-icon><Download /></el-icon>
            Download Report
          </el-button>
        </div>

        <el-divider />

        <ReportParameters
          :form-data="formData"
          :signatories="signatories"
           :divisions="divisions"
          :payroll-intervals="payrollIntervals"
          :payroll-periods="payrollPeriods"
          @update:formData="onUpdateFormData"
        />

        <SignatoriesSection
          :signatories="signatories"
          @update:signatories="onUpdateSignatories"
        />

        <el-divider />

        <div class="card-footer">
          <h6>GSIS REMITTANCE</h6>
        </div>
      </el-form>
    </el-card>
  </PageScaffold>
</template>

<script setup>
import { ref, onMounted, computed } from "vue";
import PageScaffold from "../../components/PageScaffold.vue";
import { Document, Download } from "@element-plus/icons-vue";
import ReportParameters from "../../components/Payroll_Reports/rptGSISRemittance/ReportParameters.vue";
import SignatoriesSection from "../../components/Payroll_Reports/rptGSISRemittance/SignatoriesSection.vue";
import { useGSISRemittance } from "../../Composables/useGSISRemittance.js";

const {
  loading,
  error,
  formData,
  signatories,
  payrollPeriods,
  loadInitialData,
  previewReport,
  generateReport,
  payrollIntervals,
  loadPayrollPeriods,
  divisions,
} = useGSISRemittance();

const errors = ref([]);
const backendError = computed(() => error.value);
const hasErrors = computed(
  () => errors.value.length > 0 || !!backendError.value
);

const clearErrors = () => {
  errors.value = [];
};

const onUpdateFormData = async (newVal) => {
  const prevInterval = formData.payroll_interval_id;
  const prevDept = formData.division_id;
  Object.assign(formData, newVal || {});
  const intervalChanged =
    newVal &&
    newVal.payroll_interval_id &&
    newVal.payroll_interval_id !== prevInterval;
  const departmentChanged =
    newVal && newVal.division_id && newVal.division_id !== prevDept;
  if (intervalChanged || departmentChanged) {
    await loadPayrollPeriods(
      formData.payroll_interval_id,
      formData.division_id
    );
  }
};

const onUpdateSignatories = (newVal) => {
  Object.assign(signatories, newVal || {});
};

// previewReport is now handled by the composable

onMounted(async () => {
  try {
    await loadInitialData();
    if (formData.payroll_interval_id) {
      await loadPayrollPeriods(formData.payroll_interval_id);
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
