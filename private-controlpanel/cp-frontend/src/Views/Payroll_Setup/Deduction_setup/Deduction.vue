<template>
  <MainLayout>
    <template #header>
      <div class="page-header"><div class="title">Deduction Types Setup</div></div>
    </template>

    <el-card shadow="never" class="block-card">
      <div class="filters-row">
        <div class="left">
          <el-input v-model="search" placeholder="Search by name..." clearable class="search-input" />
        </div>
        <div class="actions">
          <el-button type="primary" @click="handleAdd">Add Deduction</el-button>
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
      <el-table v-else :data="filteredDeductions" border style="width:100%">
        <el-table-column v-if="columnVisibility.serial" label="#" width="60" align="center">
          <template #default="{ $index }">{{ $index + 1 }}</template>
        </el-table-column>
        <el-table-column v-if="columnVisibility.name" prop="name" label="Deduction Name" min-width="220" sortable />
        <el-table-column v-if="columnVisibility.uacs" prop="uacs" label="UACS" min-width="160" />
        <el-table-column v-if="columnVisibility.mfo_pap" prop="mfo_pap" label="MFO/PAP" min-width="160" />
        <el-table-column v-if="columnVisibility.is_sss" prop="is_sss" label="SSS" width="100" align="center">
          <template #default="{ row }"><el-tag :type="row.is_sss ? 'success' : 'info'">{{ row.is_sss ? 'Yes' : 'No' }}</el-tag></template>
        </el-table-column>
        <el-table-column v-if="columnVisibility.is_philhealth" prop="is_philhealth" label="Philhealth" width="120" align="center">
          <template #default="{ row }"><el-tag :type="row.is_philhealth ? 'success' : 'info'">{{ row.is_philhealth ? 'Yes' : 'No' }}</el-tag></template>
        </el-table-column>
        <el-table-column v-if="columnVisibility.is_pagibig" prop="is_pagibig" label="Pag-IBIG" width="110" align="center">
          <template #default="{ row }"><el-tag :type="row.is_pagibig ? 'success' : 'info'">{{ row.is_pagibig ? 'Yes' : 'No' }}</el-tag></template>
        </el-table-column>
        <el-table-column v-if="columnVisibility.is_bank" prop="is_bank" label="Bank" width="100" align="center">
          <template #default="{ row }"><el-tag :type="row.is_bank ? 'success' : 'info'">{{ row.is_bank ? 'Yes' : 'No' }}</el-tag></template>
        </el-table-column>
        <el-table-column v-if="columnVisibility.active" prop="active" label="Active" width="110" align="center">
          <template #default="{ row }"><el-tag :type="row.active ? 'success' : 'info'">{{ row.active ? 'Active' : 'Inactive' }}</el-tag></template>
        </el-table-column>
        <el-table-column v-if="columnVisibility.actions" label="Actions" width="100" fixed="right" align="center">
          <template #default="{ row }">
            <el-popconfirm title="Delete this deduction?" @confirm="handleDelete(row)" confirm-button-text="Delete" cancel-button-text="Cancel">
              <template #reference><el-button size="small" type="danger">Delete</el-button></template>
            </el-popconfirm>
          </template>
        </el-table-column>
      </el-table>

      <div v-if="!loading && filteredDeductions.length === 0" class="no-data">
        <el-empty description="No deductions found" />
      </div>
    </el-card>

    <!-- Add Modal -->
    <el-dialog v-model="formVisible" title="Add Deduction" width="1000px" append-to-body>
      <div v-loading="formLoading">
        <div class="section-header">
          <h4>New Deduction</h4>
          <el-button type="primary" size="small" @click="addRow">Add Row</el-button>
        </div>
        <el-table :data="formData" border style="width:100%" max-height="320">
          <el-table-column label="Deduction Name" min-width="260">
            <template #default="{ row }"><el-input v-model="row.name" placeholder="Enter deduction name" /></template>
          </el-table-column>
          <el-table-column label="UACS" min-width="160">
            <template #default="{ row }"><el-input v-model="row.uacs" placeholder="UACS" /></template>
          </el-table-column>
          <el-table-column label="MFO/PAP" min-width="160">
            <template #default="{ row }"><el-input v-model="row.mfo_pap" placeholder="MFO/PAP" /></template>
          </el-table-column>
          <el-table-column label="SSS" width="90" align="center">
            <template #default="{ row }"><el-switch v-model="row.is_sss" /></template>
          </el-table-column>
          <el-table-column label="Philhealth" width="110" align="center">
            <template #default="{ row }"><el-switch v-model="row.is_philhealth" /></template>
          </el-table-column>
          <el-table-column label="Pag-IBIG" width="110" align="center">
            <template #default="{ row }"><el-switch v-model="row.is_pagibig" /></template>
          </el-table-column>
          <el-table-column label="Bank" width="90" align="center">
            <template #default="{ row }"><el-switch v-model="row.is_bank" /></template>
          </el-table-column>
          <el-table-column label="Active" width="100" align="center">
            <template #default="{ row }"><el-switch v-model="row.active" /></template>
          </el-table-column>
          <el-table-column label="Actions" width="120" align="center">
            <template #default="{ $index }"><el-button size="small" type="danger" @click="removeRow($index)">Remove</el-button></template>
          </el-table-column>
        </el-table>

        <div class="section-header" style="margin-top:16px;">
          <h4>Existing Deductions</h4>
        </div>
        <el-table :data="deductions" border style="width:100%" max-height="260">
          <el-table-column label="#" width="60" align="center"><template #default="{ $index }">{{ $index + 1 }}</template></el-table-column>
          <el-table-column prop="name" label="Deduction Name" min-width="260" />
          <el-table-column prop="uacs" label="UACS" min-width="160" />
          <el-table-column prop="mfo_pap" label="MFO/PAP" min-width="160" />
          <el-table-column prop="is_sss" label="SSS" width="90" align="center"><template #default="{ row }"><el-tag :type="row.is_sss ? 'success' : 'info'">{{ row.is_sss ? 'Yes' : 'No' }}</el-tag></template></el-table-column>
          <el-table-column prop="is_philhealth" label="Philhealth" width="110" align="center"><template #default="{ row }"><el-tag :type="row.is_philhealth ? 'success' : 'info'">{{ row.is_philhealth ? 'Yes' : 'No' }}</el-tag></template></el-table-column>
          <el-table-column prop="is_pagibig" label="Pag-IBIG" width="110" align="center"><template #default="{ row }"><el-tag :type="row.is_pagibig ? 'success' : 'info'">{{ row.is_pagibig ? 'Yes' : 'No' }}</el-tag></template></el-table-column>
          <el-table-column prop="is_bank" label="Bank" width="90" align="center"><template #default="{ row }"><el-tag :type="row.is_bank ? 'success' : 'info'">{{ row.is_bank ? 'Yes' : 'No' }}</el-tag></template></el-table-column>
          <el-table-column prop="active" label="Active" width="100" align="center"><template #default="{ row }"><el-tag :type="row.active ? 'success' : 'info'">{{ row.active ? 'Active' : 'Inactive' }}</el-tag></template></el-table-column>
        </el-table>

        <div class="dialog-actions">
          <el-button @click="formVisible = false">Cancel</el-button>
          <el-button type="primary" :loading="saving" @click="saveDeductions">Save Changes</el-button>
        </div>
      </div>
    </el-dialog>
  </MainLayout>
</template>

<script setup>
import { onMounted } from 'vue'
import MainLayout from '../../../Layout/MainLayout.vue'
import { useDeduction } from '../../../composables/useDeduction.js'
import { useExport } from '../../../composables/useExport.js'
import { Printer, Download, Document, Setting, ArrowDown } from '@element-plus/icons-vue'

const {
  deductions, loading, apiError, fetchDeductions,
  formVisible, formLoading, formData,
  openForm, addRow, removeRow, saveDeductions, deleteDeduction,
  saving, search, filteredDeductions, columnVisibility
} = useDeduction()

const { exportPrint, exportExcel, exportPDF } = useExport()

onMounted(fetchDeductions)

function handleAdd() { openForm() }
async function handleDelete(row) { await deleteDeduction(row.id) }

function getColumnLabel(key) {
  const map = { serial: '#', name: 'Deduction Name', uacs: 'UACS', mfo_pap: 'MFO/PAP', is_sss: 'SSS', is_philhealth: 'Philhealth', is_pagibig: 'Pag-IBIG', is_bank: 'Bank', active: 'Active', actions: 'Actions' }
  return map[key] || key
}

const exportColumns = [
  { key: 'serial', label: '#' },
  { key: 'name', label: 'Deduction Name' },
  { key: 'uacs', label: 'UACS' },
  { key: 'mfo_pap', label: 'MFO/PAP' },
  { key: 'is_sss', label: 'SSS', formatter: (row) => row.is_sss ? 'Yes' : 'No' },
  { key: 'is_philhealth', label: 'Philhealth', formatter: (row) => row.is_philhealth ? 'Yes' : 'No' },
  { key: 'is_pagibig', label: 'Pag-IBIG', formatter: (row) => row.is_pagibig ? 'Yes' : 'No' },
  { key: 'is_bank', label: 'Bank', formatter: (row) => row.is_bank ? 'Yes' : 'No' },
  { key: 'active', label: 'Active', formatter: (row) => row.active ? 'Active' : 'Inactive' }
]

function buildExportPayload() {
  const data = filteredDeductions.value.map((row, index) => ({
    ...row,
    serial: index + 1
  }))
  return {
    title: 'Deduction Types Setup',
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