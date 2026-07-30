<template>
  <MainLayout>
    <template #header>
      <div class="page-header"><div class="title">Tax Table Setup</div></div>
    </template>

    <el-card shadow="never" class="block-card">
      <el-tabs v-model="activeTab" @tab-change="handleTabChange">
        <el-tab-pane label="Semi-Monthly Tax Table" name="semi-monthly"></el-tab-pane>
        <el-tab-pane label="Monthly Tax Table" name="monthly"></el-tab-pane>
        <el-tab-pane label="Annual Tax Table" name="annual"></el-tab-pane>
      </el-tabs>
    </el-card>

    <el-card shadow="never" class="block-card">
      <div class="filters-row">
        <div class="left">
          <el-input v-model="search" placeholder="Search by income range..." clearable class="search-input" />
        </div>
        <div class="actions">
          <el-button type="primary" @click="handleEdit">Edit Tax Table</el-button>
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
        <el-alert
          title="API Error"
          type="warning"
          description="Unable to fetch tax data from server. Showing sample data for demonstration."
          show-icon
          :closable="false"
        />
      </div>
      <el-table v-else :data="filteredTaxTables" border style="width:100%">
        <el-table-column v-if="columnVisibility.serial" label="#" width="60" align="center">
          <template #default="{ $index }">{{ $index + 1 }}</template>
        </el-table-column>

        <el-table-column v-if="columnVisibility.percentage" prop="percentage" label="Percentage" min-width="120" sortable>
          <template #default="{ row }">
            {{ formatPercentage(row.percentage) }}
          </template>
        </el-table-column>
        <el-table-column v-if="columnVisibility.min_amount" prop="min_amount" label="Minimum" min-width="150" sortable>
          <template #default="{ row }">
            ₱{{ formatNumber(row.min_amount) }}
          </template>
        </el-table-column>
        <el-table-column v-if="columnVisibility.max_amount" prop="max_amount" label="Maximum" min-width="150" sortable>
          <template #default="{ row }">
            ₱{{ formatNumber(row.max_amount) }}
          </template>
        </el-table-column>
        <el-table-column v-if="columnVisibility.base_tax" prop="base_tax" label="Base Tax" min-width="150" sortable>
          <template #default="{ row }">
            ₱{{ formatNumber(row.base_tax) }}
          </template>
        </el-table-column>

        <el-table-column v-if="columnVisibility.actions" label="Actions" width="100" fixed="right" align="center">
          <template #default="{ row }">
            <el-popconfirm title="Delete this tax record?" @confirm="handleDelete(row)" confirm-button-text="Delete" cancel-button-text="Cancel">
              <template #reference><el-button size="small" type="danger">Delete</el-button></template>
            </el-popconfirm>
          </template>
        </el-table-column>
      </el-table>
      
      <div v-if="!loading && filteredTaxTables.length === 0" class="no-data">
        <el-empty description="No tax records found" />
      </div>
    </el-card>

    <!-- Edit Tax Table Modal -->
    <el-dialog v-model="formVisible" :title="getDialogTitle()" width="1200px" append-to-body>
      <div v-loading="formLoading">
        <div class="tax-form-header">
          <h4>{{ getTableTypeLabel() }} Configuration</h4>
          <el-button type="primary" size="small" :disabled="taxData.length >= 9" @click="addTaxRow">Add Row</el-button>
        </div>

        <el-alert
          class="validation-alert"
          title="Validation reminders"
          type="warning"
          show-icon
          :closable="false"
          description="Rules: (1) Each row must have Percentage, Minimum, Maximum, and Base Tax to be saved. (2) At least one complete row is required to save. (3) Maximum of 9 tax table entries only."
        />
        
        <div class="tax-table-container">
          <el-table :data="taxData" border style="width:100%" max-height="500">
            <el-table-column label="Percentage" width="150">
              <template #default="{ row, $index }">
                <el-input v-model="row.percentage" placeholder="0.00" type="number" step="0.01">
                  <template #append>%</template>
                </el-input>
              </template>
            </el-table-column>
            <el-table-column label="Minimum" width="180">
              <template #default="{ row, $index }">
                <el-input v-model="row.min_amount" placeholder="0.00" type="number" step="0.01">
                  <template #prepend>₱</template>
                </el-input>
              </template>
            </el-table-column>
            <el-table-column label="Maximum" width="180">
              <template #default="{ row, $index }">
                <el-input v-model="row.max_amount" placeholder="0.00" type="number" step="0.01">
                  <template #prepend>₱</template>
                </el-input>
              </template>
            </el-table-column>
            <el-table-column label="Base Tax" width="180">
              <template #default="{ row, $index }">
                <el-input v-model="row.base_tax" placeholder="0.00" type="number" step="0.01">
                  <template #prepend>₱</template>
                </el-input>
              </template>
            </el-table-column>
            <el-table-column label="Actions" width="100" align="center">
              <template #default="{ row, $index }">
                <el-button size="small" type="danger" @click="removeTaxRow($index)">Remove</el-button>
              </template>
            </el-table-column>
          </el-table>
        </div>

        <div class="dialog-actions">
          <el-button @click="formVisible = false">Cancel</el-button>
          <el-button type="primary" :loading="saving" @click="() => saveTaxTables(activeTab)">Save Changes</el-button>
        </div>
      </div>
    </el-dialog>
  </MainLayout>
</template>

<script setup>
import { onMounted, ref, computed } from 'vue'
import MainLayout from '../../../Layout/MainLayout.vue'
import { useTaxTable } from '../../../composables/useTaxTable.js'
import { useExport } from '../../../composables/useExport.js'
import { Printer, Download, Document, Setting, ArrowDown } from '@element-plus/icons-vue'

const {
  taxTables, loading, apiError, fetchTaxTables,
  formVisible, formLoading, taxData,
  openForm, addTaxRow, removeTaxRow, saveTaxTables, deleteTaxTable,
  saving, currentType, setCurrentType
} = useTaxTable()

const activeTab = ref('semi-monthly')

onMounted(() => {
  setCurrentType('semi-monthly')
  fetchTaxTables('semi-monthly')
})

function handleTabChange(tabName) {
  setCurrentType(tabName)
  fetchTaxTables(tabName)
}

function getTableTypeLabel() {
  const labels = {
    'semi-monthly': 'Semi-Monthly Tax Table',
    'monthly': 'Monthly Tax Table',
    'annual': 'Annual Tax Table'
  }
  return labels[activeTab.value] || 'Tax Table'
}

function getDialogTitle() {
  return `Edit ${getTableTypeLabel()}`
}

const search = ref('')
const { exportPrint, exportExcel, exportPDF } = useExport()

const columnVisibility = ref({
  serial: true,
  min_amount: true,
  max_amount: true,
  base_tax: true,
  percentage: true,
  actions: true
})

function getColumnLabel(key) {
  const map = {
    serial: '#', 
    min_amount: 'Minimum', 
    max_amount: 'Maximum', 
    base_tax: 'Base Tax',
    percentage: 'Percentage',
    actions: 'Actions'
  }
  return map[key] || key
}

function formatNumber(value) {
  if (!value && value !== 0) return '0.00'
  return parseFloat(value).toLocaleString('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  })
}

function formatPercentage(value) {
  if (!value && value !== 0) return '0.00'
  return parseFloat(value).toFixed(2)
}

const filteredTaxTables = computed(() => taxTables.value.filter(tax => {
  const target = `${tax.min_amount || ''} ${tax.max_amount || ''}`.toLowerCase()
  return !search.value || target.includes(search.value.toLowerCase())
}))

const exportColumns = [
  { key: 'serial', label: '#' },
  { key: 'percentage', label: 'Percentage', formatter: (row) => formatPercentage(row.percentage) },
  { key: 'min_amount', label: 'Minimum', formatter: (row) => `₱${formatNumber(row.min_amount)}` },
  { key: 'max_amount', label: 'Maximum', formatter: (row) => `₱${formatNumber(row.max_amount)}` },
  { key: 'base_tax', label: 'Base Tax', formatter: (row) => `₱${formatNumber(row.base_tax)}` }
]

function buildExportPayload() {
  const data = filteredTaxTables.value.map((row, index) => ({
    ...row,
    serial: index + 1
  }))
  return {
    title: `${getTableTypeLabel()} - Tax Table Setup`,
    data,
    columns: exportColumns,
    columnVisibility: columnVisibility.value
  }
}

function handleEdit() { openForm() }
async function handleDelete(row) { await deleteTaxTable(row.id, activeTab.value) }

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
.search-input { width: 380px; }
.actions { display: flex; gap: 8px; }
.export-row { display: flex; justify-content: space-between; align-items: center; }
.export-buttons { display: flex; gap: 10px; }
.loading-placeholder { padding: 20px; }
.no-data { padding: 40px; text-align: center; }
.dialog-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 24px; }
.tax-form-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
.tax-form-header h4 { margin: 0; color: #409eff; font-size: 16px; font-weight: 600; }
.tax-table-container { margin-bottom: 16px; }
.validation-alert { margin-bottom: 16px; }
.error-message { margin-bottom: 16px; }
</style>