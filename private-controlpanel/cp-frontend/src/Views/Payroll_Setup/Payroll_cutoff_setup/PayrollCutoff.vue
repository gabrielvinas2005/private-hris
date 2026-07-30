<template>
  <MainLayout>
    <template #header>
      <div class="page-header"><div class="title">Payroll Cut-off Setup</div></div>
    </template>

    <el-card shadow="never" class="block-card">
      <div class="filters-row">
        <div class="left">
          <el-input v-model="search" placeholder="Search by cutoff name..." clearable class="search-input" />
        </div>
        <div class="actions">
          <el-button type="primary" @click="() => openForm(0)">Add Cut-off</el-button>
        </div>
      </div>
    </el-card>

    <el-card shadow="never" class="block-card">
      <div class="export-row">
        <div class="export-buttons">
          <el-button @click="handlePrint"><el-icon><Printer /></el-icon> Print</el-button>
          <el-button @click="handleExportCsv"><el-icon><Download /></el-icon> Excel</el-button>
          <el-button @click="handleExportPdf"><el-icon><Document /></el-icon> PDF</el-button>
        </div>
        <el-dropdown trigger="click">
          <el-button>
            <el-icon><Setting /></el-icon>
            Column Visibility
            <el-icon class="el-icon--right"><ArrowDown /></el-icon>
          </el-button>
          <template #dropdown>
            <el-dropdown-menu class="column-visibility">
              <el-dropdown-item v-for="key in visibilityKeys" :key="key" disabled>
                <el-checkbox @click.stop v-model="columnVisibility[key]">{{ getColumnLabel(key) }}</el-checkbox>
              </el-dropdown-item>
            </el-dropdown-menu>
          </template>
        </el-dropdown>
      </div>
    </el-card>

    <el-card shadow="hover">
      <div v-if="loading" class="loading-placeholder">
        <el-skeleton :rows="5" animated />
      </div>
      <div v-else-if="apiError" class="error-message">
        <el-alert title="API Error" type="warning" description="Unable to fetch data from server." show-icon :closable="false" />
      </div>
      <el-table v-else :data="filteredCutoffs" border style="width:100%">
        <el-table-column v-if="columnVisibility.serial" label="#" width="60" align="center">
          <template #default="{ $index }">{{ $index + 1 }}</template>
        </el-table-column>
        <el-table-column v-if="columnVisibility.payroll_cutoff" prop="payroll_cutoff" label="Payroll Cut-off" min-width="240" />
        <el-table-column v-if="columnVisibility.payroll_interval" prop="payroll_interval" label="Payroll Interval" min-width="240" />
      </el-table>
    </el-card>

    <el-dialog v-model="formVisible" title="Add / Edit Cut-off" width="600px" append-to-body>
      <div v-loading="formLoading">
        <el-form label-width="160px">
          <el-form-item label="Cut-off Name">
            <el-input v-model="form.name" placeholder="Enter cut-off name" />
          </el-form-item>
          <el-form-item label="Payroll Interval">
            <el-select v-model="form.payroll_interval_id" placeholder="Select interval" filterable style="width:100%">
              <el-option v-for="i in intervals" :key="i.id" :label="i.name" :value="i.id" />
            </el-select>
          </el-form-item>
        </el-form>
        <div class="dialog-actions">
          <el-button @click="formVisible = false">Cancel</el-button>
          <el-button type="primary" :loading="saving" @click="saveCutoff">Save</el-button>
        </div>
      </div>
    </el-dialog>
  </MainLayout>
</template>

<script setup>
import { onMounted, computed } from 'vue'
import MainLayout from '../../../Layout/MainLayout.vue'
import { usePayrollCutoff } from '../../../composables/usePayrollCutoff.js'
import { useExport } from '../../../composables/useExport.js'
import { Printer, Download, Document, Setting, ArrowDown } from '@element-plus/icons-vue'

const {
  cutoffs, intervals, loading, apiError, fetchCutoffs,
  formVisible, formLoading, form,
  openForm, saveCutoff,
  saving, search, filteredCutoffs, columnVisibility
} = usePayrollCutoff()

const { exportPrint, exportExcel, exportPDF } = useExport()

onMounted(fetchCutoffs)

const visibilityKeys = computed(() => Object.keys(columnVisibility.value))

function getColumnLabel(key) {
  const map = { serial: '#', payroll_cutoff: 'Payroll Cut-off', payroll_interval: 'Payroll Interval', actions: 'Actions' }
  return map[key] || key
}

const exportColumns = [
  { key: 'serial', label: '#' },
  { key: 'payroll_cutoff', label: 'Payroll Cut-off' },
  { key: 'payroll_interval', label: 'Payroll Interval' }
]

function buildExportPayload() {
  const data = filteredCutoffs.value.map((row, index) => ({
    ...row,
    serial: index + 1
  }))
  return {
    title: 'Payroll Cut-off Setup',
    data,
    columns: exportColumns,
    columnVisibility: columnVisibility.value
  }
}

function handlePrint() { exportPrint(buildExportPayload()) }
function handleExportCsv() { exportExcel(buildExportPayload()) }
function handleExportPdf() { exportPDF(buildExportPayload()) }
</script>

<style scoped>
.page-header { display: flex; align-items: center; }
.title { font-weight: 600; font-size: 18px; }
.block-card { margin-bottom: 12px; }
.filters-row { display: flex; justify-content: space-between; align-items: center; }
.left { display: flex; gap: 10px; align-items: center; }
.search-input { width: 280px; }
.actions { display: flex; gap: 8px; }
.export-row { display: flex; justify-content: space-between; align-items: center; }
.export-buttons { display: flex; gap: 10px; }
.loading-placeholder { padding: 20px; }
.dialog-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 16px; }
</style>