<template>
  <PageScaffold
    title="ATM Letter for Landbank"
    subtitle="Generate ATM letter for Landbank with pre-inputted signatory and personnel information"
    :breadcrumbs="[
      { label: 'Payroll Module', to: '/' },
      { label: 'Payroll Reports', to: '/payroll-reports' },
      { label: 'ATM Letter for Landbank' },
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
          <span>ATM Letter for Landbank Information</span>
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
            class="mr-3"
          >
            <el-icon><Download /></el-icon>
            Download PDF
          </el-button>
        </div>

        <el-divider />

        <ReportParameters
          :form-data="formData"
           :divisions="divisions"
          :payroll-periods="payrollPeriods"
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
          <h6>ATM LETTER FOR LANDBANK</h6>
        </div>
      </el-form>
    </el-card>

    <!-- Inline Print Preview -->
    <div v-if="uiState.showPrintModal" class="mt-6">
      <el-card shadow="never">
        <div class="flex items-center justify-between mb-3">
          <div class="text-base font-semibold">Print Preview</div>
          <div class="flex items-center gap-2">
            <span class="text-xs text-slate-600 mr-1">Download as:</span>
            <el-button size="small" type="danger" @click="downloadPdf">PDF</el-button>
            <el-button size="small" @click="closePreview"><el-icon><Close /></el-icon></el-button>
          </div>
        </div>
        <div v-if="uiState.previewUrl" class="border rounded overflow-hidden" style="height: 680px; max-width: 100%;">
          <iframe :src="uiState.previewUrl" class="w-full h-full" style="max-width: 100%;"></iframe>
        </div>
        <div v-else class="text-center text-gray-500 py-10">Loading preview...</div>
      </el-card>
    </div>
  </PageScaffold>
</template>

<script setup>
import { ref, onMounted, computed } from "vue";
import PageScaffold from "../../components/PageScaffold.vue";
import { Document, Download, Close } from "@element-plus/icons-vue";
import ReportParameters from "../../components/Payroll_Reports/rptAtmLetterLandbank/ReportParameters.vue";
import SignatoriesSection from "../../components/Payroll_Reports/rptAtmLetterLandbank/SignatoriesSection.vue";
import { useAtmLetterLandbank } from "../../Composables/useAtmLetterLandbank.js";

const {
  loading,
  error,
  formData,
  signatories,
  defaultSignatoryPlaceholders,
  divisions,
  payrollPeriods,
  employeeOptions,
  loadInitialData,
  previewReport,
  generatePdfReport,
  uiState,
  downloadPdf,
  closePreview,
} = useAtmLetterLandbank();

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
.mr-3 {
  margin-right: 1rem;
}
.mt-6 {
  margin-top: 1.5rem;
}
.flex {
  display: flex;
}
.items-center {
  align-items: center;
}
.justify-between {
  justify-content: space-between;
}
.gap-2 {
  gap: 0.5rem;
}
.text-base {
  font-size: 1rem;
  line-height: 1.5rem;
}
.font-semibold {
  font-weight: 600;
}
.text-xs {
  font-size: 0.75rem;
  line-height: 1rem;
}
.text-slate-600 {
  color: #475569;
}
.text-gray-500 {
  color: #6b7280;
}
.text-center {
  text-align: center;
}
.py-10 {
  padding-top: 2.5rem;
  padding-bottom: 2.5rem;
}
.border {
  border-width: 1px;
  border-style: solid;
  border-color: #e5e7eb;
}
.rounded {
  border-radius: 0.25rem;
}
.overflow-hidden {
  overflow: hidden;
}
.w-full {
  width: 100%;
}
.h-full {
  height: 100%;
}
</style>
