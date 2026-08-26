<template>
  <MainLayout>
    <template #header>
      <div class="page-header"><div class="title">GSIS Table Setup</div></div>
    </template>

    <el-card shadow="never" class="block-card">
      <div class="filters-row">
        <div class="left">
          <el-input v-model="search" placeholder="Search by year..." clearable class="search-input" />
        </div>
        <div class="actions">
          <el-button type="primary" @click="handleEdit">Add GSIS Table</el-button>
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
        <el-alert title="API Error" type="warning" description="Unable to fetch data from server. Showing sample data for demonstration." show-icon :closable="false" />
      </div>
      <el-table v-else :data="filteredGsisTables" border style="width:100%">
        <el-table-column v-if="columnVisibility.serial" label="#" width="60" align="center">
          <template #default="{ $index }">{{ $index + 1 }}</template>
        </el-table-column>

        <el-table-column v-if="columnVisibility.year" prop="year" label="Year" min-width="120" sortable />
        <el-table-column v-if="columnVisibility.multiplier" prop="multiplier" label="Multiplier" min-width="140" sortable>
          <template #default="{ row }">{{ formatNumber(row.multiplier) }}</template>
        </el-table-column>
        <el-table-column v-if="columnVisibility.employer_share" prop="employer_share" label="Employer Share" min-width="160" sortable>
          <template #default="{ row }">{{ formatNumber(row.employer_share) }}</template>
        </el-table-column>
        <el-table-column v-if="columnVisibility.employer_share_admin" prop="employer_share_admin" label="Employer Share Admin" min-width="200" sortable>
          <template #default="{ row }">{{ formatNumber(row.employer_share_admin) }}</template>
        </el-table-column>
        <el-table-column v-if="columnVisibility.effectivity_date" prop="effectivity_date" label="Effectivity Date" min-width="160" sortable />
        <el-table-column v-if="columnVisibility.end_date" prop="end_date" label="End Date" min-width="160" sortable />

        <el-table-column v-if="columnVisibility.actions" label="Actions" width="100" fixed="right" align="center">
          <template #default="{ row }">
            <el-popconfirm title="Delete this record?" @confirm="handleDelete(row)" confirm-button-text="Delete" cancel-button-text="Cancel">
              <template #reference><el-button size="small" type="danger">Delete</el-button></template>
            </el-popconfirm>
          </template>
        </el-table-column>
      </el-table>

      <div v-if="!loading && filteredGsisTables.length === 0" class="no-data">
        <el-empty description="No records found" />
      </div>
    </el-card>

    <!-- Edit Modal -->
    <el-dialog v-model="formVisible" title="Add GSIS Table" width="1100px" append-to-body>
      <div v-loading="formLoading">
        <el-alert
          title="One entry per year only"
          type="warning"
          description="You can only create a single GSIS configuration per year. Adding a row with an existing year will update that year's configuration."
          show-icon
          :closable="false"
          class="error-message"
        />
        <div class="tax-form-header">
          <h4>GSIS Configuration</h4>
          <el-button type="primary" size="small" @click="addRow">Add Row</el-button>
        </div>

        <div class="tax-table-container">
          <el-table :data="formData" border style="width:100%" max-height="500">
            <el-table-column label="Year" width="120">
              <template #default="{ row }">
                <el-input v-model="row.year" placeholder="YYYY" type="number" />
              </template>
            </el-table-column>
            <el-table-column label="Multiplier" width="140">
              <template #default="{ row }">
                <el-input v-model="row.multiplier" placeholder="0.00" type="number" step="0.01" />
              </template>
            </el-table-column>
            <el-table-column label="Employer Share" width="160">
              <template #default="{ row }">
                <el-input v-model="row.employer_share" placeholder="0.00" type="number" step="0.01" />
              </template>
            </el-table-column>
            <el-table-column label="Employer Share Admin" width="200">
              <template #default="{ row }">
                <el-input v-model="row.employer_share_admin" placeholder="0.00" type="number" step="0.01" />
              </template>
            </el-table-column>
            <el-table-column label="Effectivity Date" width="180">
              <template #default="{ row }">
                <el-date-picker v-model="row.effectivity_date" type="date" placeholder="YYYY-MM-DD" format="YYYY-MM-DD" value-format="YYYY-MM-DD" />
              </template>
            </el-table-column>
            <el-table-column label="End Date" width="180">
              <template #default="{ row }">
                <el-date-picker v-model="row.end_date" type="date" placeholder="YYYY-MM-DD" format="YYYY-MM-DD" value-format="YYYY-MM-DD" />
              </template>
            </el-table-column>
            <el-table-column label="Actions" width="100" align="center">
              <template #default="{ $index }">
                <el-button size="small" type="danger" @click="removeRow($index)">Remove</el-button>
              </template>
            </el-table-column>
          </el-table>
        </div>

        <div class="dialog-actions">
          <el-button @click="formVisible = false">Cancel</el-button>
          <el-button type="primary" :loading="saving" @click="saveGsisTables">Save Changes</el-button>
        </div>
      </div>
    </el-dialog>
  </MainLayout>
</template>

<script setup>
import { onMounted } from 'vue'
import MainLayout from '../../../Layout/MainLayout.vue'
import { useGsisTable } from '../../../composables/useGsisTable.js'
import { useExport } from '../../../composables/useExport.js'
import { Printer, Download, Document, Setting, ArrowDown } from '@element-plus/icons-vue'

const {
  gsisTables, loading, apiError, fetchGsisTables,
  formVisible, formLoading, formData,
  openForm, addRow, removeRow, saveGsisTables, deleteGsis,
  saving, search, filteredGsisTables, columnVisibility
} = useGsisTable()

const { exportPrint, exportExcel, exportPDF } = useExport()

onMounted(fetchGsisTables)

function getColumnLabel(key) {
  const map = { serial: '#', year: 'Year', multiplier: 'Multiplier', employer_share: 'Employer Share', employer_share_admin: 'Employer Share Admin', effectivity_date: 'Effectivity Date', end_date: 'End Date', actions: 'Actions' }
  return map[key] || key
}

function formatNumber(value) {
  if (!value && value !== 0) return '0.00'
  return parseFloat(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function handleEdit() { openForm() }
async function handleDelete(row) { await deleteGsis(row.id) }

const exportColumns = [
  { key: 'serial', label: '#' },
  { key: 'year', label: 'Year' },
  { key: 'multiplier', label: 'Multiplier' },
  { key: 'employer_share', label: 'Employer Share' },
  { key: 'employer_share_admin', label: 'Employer Share Admin' },
  { key: 'effectivity_date', label: 'Effectivity Date' },
  { key: 'end_date', label: 'End Date' }
]

function buildExportPayload() {
  const data = filteredGsisTables.value.map((row, index) => ({
    ...row,
    serial: index + 1
  }))
  return {
    title: 'GSIS Table Setup',
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
.tax-form-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
.tax-form-header h4 { margin: 0; color: #409eff; font-size: 16px; font-weight: 600; }
.tax-table-container { margin-bottom: 16px; }
.error-message { margin-bottom: 16px; }
</style>