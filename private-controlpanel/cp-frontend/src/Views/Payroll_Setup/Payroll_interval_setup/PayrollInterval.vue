<template>
  <MainLayout>
    <template #header>
      <div class="page-header"><div class="title">Payroll Interval Setup</div></div>
    </template>

    <el-card shadow="never" class="block-card">
      <div class="filters-row">
        <div class="left">
          <el-input v-model="search" placeholder="Search by name..." clearable class="search-input" />
        </div>
        <div class="actions">
          <el-button type="primary" @click="handleAdd">Add Interval</el-button>
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
              <el-dropdown-item v-for="(val,key) in columnVisibility" :key="key" disabled>
                <el-checkbox v-model="columnVisibility[key]">{{ getColumnLabel(key) }}</el-checkbox>
              </el-dropdown-item>
            </el-dropdown-menu>
          </template>
        </el-dropdown>
      </div>
    </el-card>

    <el-card shadow="hover">
      <div v-if="loading" class="loading-placeholder">
        <el-skeleton :rows="6" animated />
      </div>
      <div v-else-if="apiError" class="error-message">
        <el-alert title="API Error" type="warning" description="Unable to fetch data from server." show-icon :closable="false" />
      </div>
      <el-table v-else :data="filteredIntervals" border style="width:100%">
        <el-table-column v-if="columnVisibility.serial" label="#" width="60" align="center">
          <template #default="{ $index }">{{ $index + 1 }}</template>
        </el-table-column>
        <el-table-column v-if="columnVisibility.name" prop="name" label="Name" min-width="220" sortable />
        <el-table-column v-if="columnVisibility.day_interval" prop="day_interval" label="Day Interval" min-width="140" />
        <el-table-column v-if="columnVisibility.month_frequency" prop="month_frequency" label="Month Frequency" min-width="160" />
        <el-table-column v-if="columnVisibility.year_frequency" prop="year_frequency" label="Year Frequency" min-width="160" />
        <el-table-column v-if="columnVisibility.active" label="Status" width="140" align="center">
          <template #default="{ row }">
            <el-switch
              :model-value="row.active"
              active-text="Active"
              inactive-text="Inactive"
              @change="() => handleToggleActive(row)"
            />
          </template>
        </el-table-column>
        <el-table-column v-if="columnVisibility.actions" label="Actions" width="100" fixed="right" align="center">
          <template #default="{ row }">
            <el-popconfirm title="Delete this interval?" @confirm="handleDelete(row)" confirm-button-text="Delete" cancel-button-text="Cancel">
              <template #reference><el-button size="small" type="danger">Delete</el-button></template>
            </el-popconfirm>
          </template>
        </el-table-column>
      </el-table>

      <div v-if="!loading && filteredIntervals.length === 0" class="no-data">
        <el-empty description="No payroll intervals found" />
      </div>
    </el-card>

    <el-dialog v-model="formVisible" title="Add Payroll Interval" width="900px" append-to-body>
      <div v-loading="formLoading">
        <div class="section-header">
          <h4>New Interval</h4>
          <el-button type="primary" size="small" @click="addRow">Add Row</el-button>
        </div>
        <el-table :data="formData" border style="width:100%" max-height="360">
          <el-table-column label="Name" min-width="240">
            <template #default="{ row }"><el-input v-model="row.name" placeholder="Enter name" /></template>
          </el-table-column>
          <el-table-column label="Day Interval" width="140">
            <template #default="{ row }"><el-input v-model="row.day_interval" placeholder="0" type="number" min="0" /></template>
          </el-table-column>
          <el-table-column label="Month Frequency" width="160">
            <template #default="{ row }"><el-input v-model="row.month_frequency" placeholder="0" type="number" min="0" /></template>
          </el-table-column>
          <el-table-column label="Year Frequency" width="160">
            <template #default="{ row }"><el-input v-model="row.year_frequency" placeholder="0" type="number" min="0" /></template>
          </el-table-column>
          <el-table-column label="Actions" width="120" align="center">
            <template #default="{ $index }"><el-button size="small" type="danger" @click="removeRow($index)">Remove</el-button></template>
          </el-table-column>
        </el-table>
        <div class="dialog-actions">
          <el-button @click="formVisible = false">Cancel</el-button>
          <el-button type="primary" :loading="saving" @click="saveIntervals">Save Changes</el-button>
        </div>
      </div>
    </el-dialog>
  </MainLayout>
</template>

<script setup>
import { onMounted } from 'vue'
import MainLayout from '../../../Layout/MainLayout.vue'
import { usePayrollInterval } from '../../../composables/usePayrollInterval.js'
import { useExport } from '../../../composables/useExport.js'
import { Printer, Download, Document, Setting, ArrowDown } from '@element-plus/icons-vue'

const {
  intervals, loading, apiError, fetchIntervals,
  formVisible, formLoading, formData,
  openForm, addRow, removeRow, saveIntervals, deleteInterval, toggleIntervalActive,
  saving, search, filteredIntervals, columnVisibility
} = usePayrollInterval()

const { exportPrint, exportExcel, exportPDF } = useExport()

onMounted(fetchIntervals)

function handleAdd() { openForm() }
async function handleDelete(row) { await deleteInterval(row.id) }
async function handleToggleActive(row) { await toggleIntervalActive(row) }

function getColumnLabel(key) {
  const map = { serial: '#', name: 'Name', day_interval: 'Day Interval', month_frequency: 'Month Frequency', year_frequency: 'Year Frequency', active: 'Status', actions: 'Actions' }
  return map[key] || key
}

const exportColumns = [
  { key: 'serial', label: '#' },
  { key: 'name', label: 'Name' },
  { key: 'day_interval', label: 'Day Interval' },
  { key: 'month_frequency', label: 'Month Frequency' },
  { key: 'year_frequency', label: 'Year Frequency' },
  { key: 'active', label: 'Status' }
]

function buildExportPayload() {
  const data = filteredIntervals.value.map((row, index) => ({
    ...row,
    serial: index + 1,
    active: row.active ? 'Active' : 'Inactive'
  }))
  return {
    title: 'Payroll Interval Setup',
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
.no-data { padding: 40px; text-align: center; }
.dialog-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 24px; }
.section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
.section-header h4 { margin: 0; color: #409eff; font-size: 15px; font-weight: 600; }
</style>