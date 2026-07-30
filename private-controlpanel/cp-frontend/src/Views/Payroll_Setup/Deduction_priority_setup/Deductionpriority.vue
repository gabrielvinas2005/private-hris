<template>
  <MainLayout>
    <template #header>
      <div class="page-header"><div class="title">Deduction Priority Setup</div></div>
    </template>

    <el-card shadow="never" class="block-card">
      <div class="filters-row">
        <div class="left">
          <el-input v-model="search" placeholder="Search by name..." clearable class="search-input" />
        </div>
        <div class="actions">
          <el-button type="primary" @click="openForm">Add Priority</el-button>
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
      <el-table v-else :data="filteredPriorities" border style="width:100%">
        <el-table-column v-if="columnVisibility.serial" label="#" width="60" align="center">
          <template #default="{ $index }">{{ $index + 1 }}</template>
        </el-table-column>
        <el-table-column v-if="columnVisibility.name" prop="deduction" label="Deduction Name" min-width="240" />
        <el-table-column v-if="columnVisibility.priority" prop="priority" label="Priority" width="160" align="center" />
      </el-table>
    </el-card>

    <el-dialog v-model="formVisible" title="Add Deduction Priority" width="700px" append-to-body>
      <div v-loading="formLoading">
        <div class="section-header">
          <h4>New Priority</h4>
          <el-button type="primary" size="small" @click="addRow">Add Row</el-button>
        </div>
        <el-table :data="formData" border style="width:100%">
          <el-table-column label="Deduction Name" min-width="360">
            <template #default="{ row }">
              <el-input v-model="row.deduction" placeholder="Enter deduction name" />
            </template>
          </el-table-column>
          <el-table-column label="Priority" width="160" align="center">
            <template #default="{ row }">
              <el-input v-model="row.priority" placeholder="1" type="number" min="1" />
            </template>
          </el-table-column>
          <el-table-column label="Actions" width="100" align="center">
            <template #default="{ $index }"><el-button size="small" type="danger" @click="removeRow($index)">Remove</el-button></template>
          </el-table-column>
        </el-table>
        <div class="dialog-actions">
          <el-button @click="formVisible = false">Cancel</el-button>
          <el-button type="primary" :loading="saving" @click="savePriorities">Save Changes</el-button>
        </div>
      </div>
    </el-dialog>
  </MainLayout>
</template>

<script setup>
import { onMounted, computed } from 'vue'
import MainLayout from '../../../Layout/MainLayout.vue'
import { useDeductionPriority } from '../../../composables/useDeductionPriority.js'
import { useExport } from '../../../composables/useExport.js'
import { Printer, Download, Document, Setting, ArrowDown } from '@element-plus/icons-vue'

const {
  priorities, loading, apiError, fetchPriorities,
  formVisible, formLoading, formData,
  openForm, addRow, removeRow, savePriorities,
  saving, search, filteredPriorities, columnVisibility
} = useDeductionPriority()

const { exportPrint, exportExcel, exportPDF } = useExport()

onMounted(fetchPriorities)

const visibilityKeys = computed(() => Object.keys(columnVisibility.value))

function getColumnLabel(key) {
  const map = { serial: '#', name: 'Deduction Name', priority: 'Priority', actions: 'Actions' }
  return map[key] || key
}

const exportColumns = [
  { key: 'serial', label: '#' },
  { key: 'deduction', label: 'Deduction Name' },
  { key: 'priority', label: 'Priority' }
]

function buildExportPayload() {
  const data = filteredPriorities.value.map((row, index) => ({
    ...row,
    serial: index + 1
  }))
  return {
    title: 'Deduction Priority Setup',
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
.loading-placeholder { padding: 20px; }
.section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
.dialog-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 16px; }
.export-row { display: flex; justify-content: space-between; align-items: center; }
.export-buttons { display: flex; gap: 10px; }
</style>