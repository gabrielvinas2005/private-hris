<template>
  <MainLayout>
    <template #header>
      <div class="page-header"><div class="title">HDMF (Pag-IBIG) Table Setup</div></div>
    </template>

    <el-card shadow="never" class="block-card">
      <div class="filters-row">
        <div class="left">
          <el-input v-model="search" placeholder="Search by year..." clearable class="search-input" />
        </div>
        <div class="actions">
          <el-button type="primary" @click="handleEdit">Edit Table</el-button>
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
        <el-skeleton :rows="5" animated />
      </div>
      <div v-else-if="apiError" class="error-message">
        <el-alert title="API Error" type="warning" description="Unable to fetch HDMF data from server. Showing sample data for demonstration." show-icon :closable="false" />
      </div>
      <el-table v-else :data="filteredHdmfTables" border style="width:100%">
        <el-table-column v-if="columnVisibility.serial" label="#" width="60" align="center">
          <template #default="{ $index }">{{ $index + 1 }}</template>
        </el-table-column>

        <el-table-column v-if="columnVisibility.year" prop="year" label="Year" min-width="120" sortable />
        <el-table-column v-if="columnVisibility.amount" prop="amount" label="Amount" min-width="150" sortable>
          <template #default="{ row }">₱{{ formatNumber(row.amount) }}</template>
        </el-table-column>

        <el-table-column v-if="columnVisibility.actions" label="Actions" width="100" fixed="right" align="center">
          <template #default="{ row }">
            <el-popconfirm title="Delete this record?" @confirm="handleDelete(row)" confirm-button-text="Delete" cancel-button-text="Cancel">
              <template #reference><el-button size="small" type="danger">Delete</el-button></template>
            </el-popconfirm>
          </template>
        </el-table-column>
      </el-table>

      <div v-if="!loading && filteredHdmfTables.length === 0" class="no-data">
        <el-empty description="No records found" />
      </div>
    </el-card>

    <!-- Edit Modal -->
    <el-dialog v-model="formVisible" title="Edit HDMF Table" width="800px" append-to-body>
      <div v-loading="formLoading">
        <div class="tax-form-header">
          <h4>HDMF Configuration</h4>
          <el-button type="primary" size="small" @click="addRow">Add Row</el-button>
        </div>

        <div class="tax-table-container">
          <el-table :data="hdmfData" border style="width:100%" max-height="500">
            <el-table-column label="Year" width="180">
              <template #default="{ row }">
                <el-input v-model="row.year" placeholder="YYYY" type="number" />
              </template>
            </el-table-column>
            <el-table-column label="Amount" width="220">
              <template #default="{ row }">
                <el-input v-model="row.amount" placeholder="0.00" type="number" step="0.01">
                  <template #prepend>₱</template>
                </el-input>
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
          <el-button type="primary" :loading="saving" @click="saveHdmfTables">Save Changes</el-button>
        </div>
      </div>
    </el-dialog>
  </MainLayout>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import MainLayout from '../../../Layout/MainLayout.vue'
import { useHdmfTable } from '../../../composables/useHdmfTable.js'
import { useExport } from '../../../composables/useExport.js'
import { Printer, Download, Document, Setting, ArrowDown } from '@element-plus/icons-vue'

const {
  hdmfTables, loading, apiError, fetchHdmfTables,
  formVisible, formLoading, hdmfData,
  openForm, addRow, removeRow, saveHdmfTables, deleteHdmf,
  saving, filtered, filteredHdmfTables, columnVisibility
} = useHdmfTable()

onMounted(fetchHdmfTables)

const search = filtered
const { exportPrint, exportExcel, exportPDF } = useExport()

function getColumnLabel(key) {
  const map = { serial: '#', year: 'Year', amount: 'Amount', actions: 'Actions' }
  return map[key] || key
}

function formatNumber(value) {
  if (!value && value !== 0) return '0.00'
  return parseFloat(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

const exportColumns = [
  { key: 'serial', label: '#' },
  { key: 'year', label: 'Year' },
  { key: 'amount', label: 'Amount', formatter: (row) => `₱${formatNumber(row.amount)}` }
]

function buildExportPayload() {
  const data = filteredHdmfTables.value.map((row, index) => ({
    ...row,
    serial: index + 1
  }))
  return {
    title: 'HDMF (Pag-IBIG) Table Setup',
    data,
    columns: exportColumns,
    columnVisibility: columnVisibility.value
  }
}

function handleEdit() { openForm() }
async function handleDelete(row) { await deleteHdmf(row.id) }

function handlePrint() {
  exportPrint(buildExportPayload())
}
function handleExportCsv() {
  exportExcel(buildExportPayload())
}
function handleExportPdf() {
  exportPDF(buildExportPayload())
}
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