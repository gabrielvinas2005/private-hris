<template>
  <MainLayout>
    <template #header>
      <div class="page-header"><div class="title">PhilHealth Table Setup</div></div>
    </template>

    <el-card shadow="never" class="block-card">
      <div class="filters-row">
        <div class="left">
          <el-input v-model="search" placeholder="Search by year..." clearable class="search-input" />
        </div>
        <div class="actions">
          <el-button type="primary" @click="handleEdit">Add PhilHealth Table</el-button>
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
      <el-table v-else :data="filteredPhilTables" border style="width:100%">
        <el-table-column v-if="columnVisibility.serial" label="#" width="60" align="center">
          <template #default="{ $index }">{{ $index + 1 }}</template>
        </el-table-column>

        <el-table-column v-if="columnVisibility.year" prop="year" label="Year" min-width="120" sortable />
        <el-table-column v-if="columnVisibility.multiplier" prop="multiplier" label="Multiplier" min-width="140" sortable>
          <template #default="{ row }">{{ formatNumber(row.multiplier) }}</template>
        </el-table-column>
        <el-table-column v-if="columnVisibility.income_floor" prop="income_floor" label="Income Floor" min-width="160" sortable>
          <template #default="{ row }">₱{{ formatNumber(row.income_floor) }}</template>
        </el-table-column>
        <el-table-column v-if="columnVisibility.income_ceiling" prop="income_ceiling" label="Income Ceiling" min-width="160" sortable>
          <template #default="{ row }">₱{{ formatNumber(row.income_ceiling) }}</template>
        </el-table-column>
        <el-table-column v-if="columnVisibility.fix_rate" prop="fix_rate" label="Fix Rate" min-width="140" sortable>
          <template #default="{ row }">{{ formatNumber(row.fix_rate) }}</template>
        </el-table-column>

        <el-table-column v-if="columnVisibility.actions" label="Actions" width="100" fixed="right" align="center">
          <template #default="{ row }">
            <el-popconfirm title="Delete this record?" @confirm="handleDelete(row)" confirm-button-text="Delete" cancel-button-text="Cancel">
              <template #reference><el-button size="small" type="danger">Delete</el-button></template>
            </el-popconfirm>
          </template>
        </el-table-column>
      </el-table>

      <div v-if="!loading && filteredPhilTables.length === 0" class="no-data">
        <el-empty description="No records found" />
      </div>
    </el-card>

    <!-- Edit Modal -->
    <el-dialog v-model="formVisible" title="Add PhilHealth Table" width="1000px" append-to-body>
      <div v-loading="formLoading">
        <el-alert
          title="One entry per year only"
          type="warning"
          description="You can only create a single PhilHealth configuration per year. Adding a row with an existing year will update that year's configuration."
          show-icon
          :closable="false"
          class="error-message"
        />
        <div class="tax-form-header">
          <h4>PhilHealth Configuration</h4>
          <el-button type="primary" size="small" @click="addRow">Add Row</el-button>
        </div>

        <div class="tax-table-container">
          <el-table :data="formData" border style="width:100%" max-height="500">
            <el-table-column label="Year" width="120">
              <template #default="{ row }">
                <el-input v-model="row.year" placeholder="YYYY" type="number" />
              </template>
            </el-table-column>
            <el-table-column label="Multiplier" width="160">
              <template #default="{ row }">
                <el-input v-model="row.multiplier" placeholder="0.00" type="number" step="0.01" />
              </template>
            </el-table-column>
            <el-table-column label="Income Floor" width="220">
              <template #default="{ row }">
                <el-input v-model="row.income_floor" placeholder="0.00" type="number" step="0.01">
                  <template #prepend>₱</template>
                </el-input>
              </template>
            </el-table-column>
            <el-table-column label="Income Ceiling" width="220">
              <template #default="{ row }">
                <el-input v-model="row.income_ceiling" placeholder="0.00" type="number" step="0.01">
                  <template #prepend>₱</template>
                </el-input>
              </template>
            </el-table-column>
            <el-table-column label="Fix Rate" width="160">
              <template #default="{ row }">
                <el-input v-model="row.fix_rate" placeholder="0.00" type="number" step="0.01" />
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
          <el-button type="primary" :loading="saving" @click="savePhilTables">Save Changes</el-button>
        </div>
      </div>
    </el-dialog>
  </MainLayout>
</template>

<script setup>
import { onMounted } from 'vue'
import MainLayout from '../../../Layout/MainLayout.vue'
import { usePhilhealthTable } from '../../../composables/usePhilhealthTable.js'
import { useExport } from '../../../composables/useExport.js'
import { Printer, Download, Document, Setting, ArrowDown } from '@element-plus/icons-vue'

const {
  philTables, loading, apiError, fetchPhilTables,
  formVisible, formLoading, formData,
  openForm, addRow, removeRow, savePhilTables, deletePhil,
  saving, search, filteredPhilTables, columnVisibility
} = usePhilhealthTable()

const { exportPrint, exportExcel, exportPDF } = useExport()

onMounted(fetchPhilTables)

function getColumnLabel(key) {
  const map = { serial: '#', year: 'Year', multiplier: 'Multiplier', income_floor: 'Income Floor', income_ceiling: 'Income Ceiling', fix_rate: 'Fix Rate', actions: 'Actions' }
  return map[key] || key
}

function formatNumber(value) {
  if (!value && value !== 0) return '0.00'
  return parseFloat(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function handleEdit() { openForm() }
async function handleDelete(row) { await deletePhil(row.id) }

const exportColumns = [
  { key: 'serial', label: '#' },
  { key: 'year', label: 'Year' },
  { key: 'multiplier', label: 'Multiplier' },
  { key: 'income_floor', label: 'Income Floor' },
  { key: 'income_ceiling', label: 'Income Ceiling' },
  { key: 'fix_rate', label: 'Fix Rate' }
]

function buildExportPayload() {
  const data = filteredPhilTables.value.map((row, index) => ({
    ...row,
    serial: index + 1
  }))
  return {
    title: 'PhilHealth Table Setup',
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