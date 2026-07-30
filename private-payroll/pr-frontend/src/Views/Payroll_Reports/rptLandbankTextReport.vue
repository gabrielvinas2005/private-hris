<template>
  <PageScaffold
    title="Landbank Text Report"
    subtitle="Generate Landbank text file for salary payments (replaces PACSVAL)"
    :breadcrumbs="[
      { label: 'Payroll Module', to: '/' },
      { label: 'Payroll Reports', to: '/payroll-reports' },
      { label: 'Landbank Text Report' },
    ]"
  >
    <template #actions>
      <el-button
        type="primary"
        :loading="loading"
        @click="handleExport"
        :disabled="!canExport"
        >Export</el-button
      >
    </template>

    <ReportParameters
       :divisions="divisions"
      :payrolls="payrollPeriods"
      :divisionId="divisionId"
      :payrollPeriodId="payrollPeriodId"
      @update:divisionId="(v) => (divisionId = v)"
      @update:payrollPeriodId="(v) => (payrollPeriodId = v)"
      @search="handleSearch"
    />

    <el-alert
      type="info"
      show-icon
      title="Instructions"
      class="mb-3"
      description="Select a Division and Payroll Period, then click Search to preview the data. Use Export to download the Landbank text file."
    />

    <!-- Preview Table -->
    <div v-if="previewLoading" class="text-center py-8">
      <el-icon class="is-loading" style="font-size: 24px">
        <Loading />
      </el-icon>
      <p class="mt-2">Loading preview data...</p>
    </div>

    <div v-else-if="previewData.length > 0" class="preview-container">
      <div class="preview-summary mb-3">
        <el-alert
          type="success"
          :closable="false"
          show-icon
        >
          <template #title>
            <span>
              <strong>Division:</strong> {{ previewSummary?.division_name || previewSummary?.department_name }} | 
              <strong>Payroll Period:</strong> {{ previewSummary?.payroll_period }} | 
              <strong>Total Records:</strong> {{ previewSummary?.total_records }}
            </span>
          </template>
        </el-alert>
      </div>

      <el-table
        :data="previewData"
        stripe
        border
        style="width: 100%"
        max-height="600"
        class="preview-table"
      >
        <el-table-column type="index" label="#" width="60" align="center" />
        <el-table-column prop="employee_name" label="Employee Name" min-width="200" />
        <el-table-column prop="account_no" label="Account Number" width="150" align="center">
          <template #default="{ row }">
            {{ row.account_no || '0000000000' }}
          </template>
        </el-table-column>
        <el-table-column prop="net_pay" label="Net Pay" width="150" align="right">
          <template #default="{ row }">
            ₱{{ parseFloat(row.net_pay || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
          </template>
        </el-table-column>
        <el-table-column prop="text_line" label="Text Line (Export Content)" min-width="400">
          <template #default="{ row }">
            <code class="text-line">{{ row.text_line }}</code>
          </template>
        </el-table-column>
      </el-table>
    </div>

    <el-empty
      v-else
      description="Select filters then click Search to preview the data. Use Export to download the Landbank file."
    />
  </PageScaffold>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { Loading } from '@element-plus/icons-vue';
import PageScaffold from '../../components/PageScaffold.vue';
import ReportParameters from '../../components/Payroll_Reports/rptLandbankTextReport/ReportParameters.vue';
import { useLandbankTextReport } from '../../Composables/useLandbankTextReport.js';
import { ElMessage } from 'element-plus';

const {
  loading,
  divisions,
  payrollPeriods,
  previewData,
  previewSummary,
  previewLoading,
  loadInitialData,
  previewReport,
  generateReport,
} = useLandbankTextReport();

const divisionId = ref('');
const payrollPeriodId = ref('');
const canExport = computed(
  () => !!divisionId.value && !!payrollPeriodId.value
);

const handleSearch = async () => {
  if (!canExport.value) {
    ElMessage.warning('Please select Division and Payroll Period.');
    return;
  }
  try {
    await previewReport({
      division_id: divisionId.value,
      payroll_period_id: payrollPeriodId.value,
    });
  } catch (e) {
    // error handled in composable
  }
};

const handleExport = async () => {
  if (!canExport.value) return;
  try {
    await generateReport({
      division_id: divisionId.value,
      payroll_period_id: payrollPeriodId.value,
    });
  } catch (e) {
    // error handled in composable
  }
};

// Clear preview data when filters change
watch([divisionId, payrollPeriodId], () => {
  previewData.value = [];
  previewSummary.value = null;
});

onMounted(async () => {
  await loadInitialData();
});
</script>

<style scoped>
.mb-3 {
  margin-bottom: 12px;
}

.preview-container {
  margin-top: 16px;
}

.preview-summary {
  margin-bottom: 16px;
}

.preview-table {
  margin-top: 16px;
}

.text-line {
  font-family: 'Courier New', monospace;
  font-size: 12px;
  background-color: #f5f5f5;
  padding: 4px 8px;
  border-radius: 4px;
  word-break: break-all;
  display: block;
  white-space: pre-wrap;
}
</style>
