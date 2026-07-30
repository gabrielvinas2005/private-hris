<template>
  <MainLayout>
    <template #header>
      <div class="page-header"><div class="title">Overtime Tax Table Setup</div></div>
    </template>

    <el-card shadow="never" class="block-card">
      <OvertimeTaxTableFilters
        :search="search"
        :current-year="currentYear"
        @search-change="handleSearchChange"
        @year-change="handleYearChange"
        @edit="handleEdit"
      />
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
      <OvertimeTaxTableTable
        :overtime-tax-tables="overtimeTaxTables"
        :loading="loading"
        :api-error="apiError"
        :search="search"
        :column-visibility="columnVisibility"
        @delete="handleDelete"
      />
    </el-card>

    <!-- Edit Overtime Tax Table Modal -->
    <OvertimeTaxTableModal
      v-model="formVisible"
      :overtime-tax-data="overtimeTaxData"
      :form-loading="formLoading"
      :saving="saving"
      :current-year="currentYear"
      @save="handleSave"
      @cancel="handleCancel"
      @year-change="handleYearChange"
      @add-row="handleAddRow"
      @remove-row="handleRemoveRow"
    />
  </MainLayout>
</template>

<script setup>
import { onMounted, ref, computed } from 'vue'
import MainLayout from '../../../Layout/MainLayout.vue'
import { useOvertimeTaxTable } from '../../../composables/useOvertimeTaxTable.js'
import { useExport } from '../../../composables/useExport.js'
import { Printer, Download, Document, Setting, ArrowDown } from '@element-plus/icons-vue'
import OvertimeTaxTableFilters from '../../../components/Payroll_Setup/Overtime_taxtable_setup/OvertimeTaxTableFilters.vue'
import OvertimeTaxTableTable from '../../../components/Payroll_Setup/Overtime_taxtable_setup/OvertimeTaxTableTable.vue'
import OvertimeTaxTableModal from '../../../components/Payroll_Setup/Overtime_taxtable_setup/OvertimeTaxTableModal.vue'
import { ElMessage } from 'element-plus'

const {
  overtimeTaxTables, loading, apiError, currentYear, fetchOvertimeTaxTables,
  formVisible, formLoading, overtimeTaxData,
  openForm, addOvertimeTaxRow, removeOvertimeTaxRow, saveOvertimeTaxTables, deleteOvertimeTaxTable,
  loadOvertimeTaxForYear, saving
} = useOvertimeTaxTable()

const { exportPrint, exportExcel, exportPDF } = useExport()

onMounted(() => {
  fetchOvertimeTaxTables()
})

const search = ref('')

const columnVisibility = ref({
  serial: true,
  amount_from: true,
  amount_to: true,
  percentage: true,
  fiscal_year: true,
  actions: true
})

function getColumnLabel(key) {
  const map = {
    serial: '#', 
    amount_from: 'Amount From', 
    amount_to: 'Amount To', 
    percentage: 'Percentage',
    fiscal_year: 'Fiscal Year',
    actions: 'Actions'
  }
  return map[key] || key
}

function handleSearchChange(newSearch) {
  search.value = newSearch
}

async function handleYearChange(year) {
  try {
    await loadOvertimeTaxForYear(year)
    ElMessage.success(`Loaded overtime tax data for ${year}`)
  } catch (error) {
    ElMessage.error('Failed to load overtime tax data for the selected year')
  }
}

function handleEdit() { 
  openForm() 
}

async function handleDelete(row) { 
  try {
    await deleteOvertimeTaxTable(row.id)
    ElMessage.success('Overtime tax record deleted successfully')
  } catch (error) {
    ElMessage.error('Failed to delete overtime tax record')
  }
}

async function handleSave() {
  try {
    await saveOvertimeTaxTables()
    ElMessage.success('Overtime tax table updated successfully')
  } catch (error) {
    ElMessage.error('Failed to save overtime tax table')
  }
}

function handleCancel() {
  formVisible.value = false
}

function handleAddRow() {
  addOvertimeTaxRow()
}

function handleRemoveRow(index) {
  removeOvertimeTaxRow(index)
}

const exportData = computed(() => {
  if (!search.value) return overtimeTaxTables.value
  const needle = search.value.toLowerCase()
  return overtimeTaxTables.value.filter(tax => {
    const amountFrom = tax.amount_from?.toString().toLowerCase() || ''
    const amountTo = tax.amount_to?.toString().toLowerCase() || ''
    const percentage = tax.percentage?.toString().toLowerCase() || ''
    const fiscalYear = tax.fiscal_year?.toString().toLowerCase() || ''
    return amountFrom.includes(needle) ||
      amountTo.includes(needle) ||
      percentage.includes(needle) ||
      fiscalYear.includes(needle)
  })
})

const exportColumns = [
  { key: 'serial', label: '#' },
  { key: 'amount_from', label: 'Amount From' },
  { key: 'amount_to', label: 'Amount To' },
  { key: 'percentage', label: 'Percentage' },
  { key: 'fiscal_year', label: 'Fiscal Year' }
]

function buildExportPayload() {
  const data = exportData.value.map((row, index) => ({
    ...row,
    serial: index + 1
  }))
  return {
    title: 'Overtime Tax Table Setup',
    data,
    columns: exportColumns,
    columnVisibility: columnVisibility.value
  }
}

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
.export-row { display: flex; justify-content: space-between; align-items: center; }
.export-buttons { display: flex; gap: 10px; }
</style>