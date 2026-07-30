<template>
  <MainLayout>
    <template #header>
      <div class="page-header"><div class="title">Year End Bonus Table Setup</div></div>
    </template>

    <el-card shadow="never" class="block-card">
      <YearEndBonusFilters
        :search="search"
        @search-change="handleSearchChange"
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
      <YearEndBonusTable
        :year-end-bonus-tables="yearEndBonusTables"
        :loading="loading"
        :api-error="apiError"
        :search="search"
        :column-visibility="columnVisibility"
        @delete="handleDelete"
      />
    </el-card>

    <!-- Edit Year End Bonus Table Modal -->
    <YearEndBonusModal
      v-model="formVisible"
      :year-end-bonus-data="yearEndBonusData"
      :form-loading="formLoading"
      :saving="saving"
      @save="handleSave"
      @cancel="handleCancel"
      @add-row="handleAddRow"
      @remove-row="handleRemoveRow"
    />
  </MainLayout>
</template>

<script setup>
import { onMounted, ref, computed } from 'vue'
import MainLayout from '../../../Layout/MainLayout.vue'
import { useYearEndBonusTable } from '../../../composables/useYearEndBonusTable.js'
import { useExport } from '../../../composables/useExport.js'
import { Printer, Download, Document, Setting, ArrowDown } from '@element-plus/icons-vue'
import YearEndBonusFilters from '../../../components/Payroll_Setup/YearEndBonus_table_setup/YearEndBonusFilters.vue'
import YearEndBonusTable from '../../../components/Payroll_Setup/YearEndBonus_table_setup/YearEndBonusTable.vue'
import YearEndBonusModal from '../../../components/Payroll_Setup/YearEndBonus_table_setup/YearEndBonusModal.vue'
import { ElMessage } from 'element-plus'

const {
  yearEndBonusTables, loading, apiError, fetchYearEndBonusTables,
  formVisible, formLoading, yearEndBonusData,
  openForm, addYearEndBonusRow, removeYearEndBonusRow, saveYearEndBonusTables, deleteYearEndBonusTable,
  saving
} = useYearEndBonusTable()

const { exportPrint, exportExcel, exportPDF } = useExport()

onMounted(() => {
  fetchYearEndBonusTables()
})

const search = ref('')

const columnVisibility = ref({
  serial: true,
  months: true,
  percentage: true,
  cash_gift: true,
  actions: true
})

function getColumnLabel(key) {
  const map = {
    serial: '#', 
    months: 'No. of Aggregate Months of Service', 
    percentage: 'Percentage of Basic Monthly Salary',
    cash_gift: 'Cash Gift',
    actions: 'Actions'
  }
  return map[key] || key
}

function handleSearchChange(newSearch) {
  search.value = newSearch
}

function handleEdit() { 
  openForm() 
}

async function handleDelete(row) { 
  try {
    await deleteYearEndBonusTable(row.id)
    ElMessage.success('Year end bonus record deleted successfully')
  } catch (error) {
    ElMessage.error('Failed to delete year end bonus record')
  }
}

async function handleSave() {
  try {
    await saveYearEndBonusTables()
    ElMessage.success('Year end bonus table updated successfully')
  } catch (error) {
    ElMessage.error('Failed to save year end bonus table')
  }
}

function handleCancel() {
  formVisible.value = false
}

function handleAddRow() {
  addYearEndBonusRow()
}

function handleRemoveRow(index) {
  removeYearEndBonusRow(index)
}

const exportData = computed(() => {
  if (!search.value) return yearEndBonusTables.value
  const needle = search.value.toLowerCase()
  return yearEndBonusTables.value.filter(item => {
    const months = item.months?.toString().toLowerCase() || ''
    const percentage = item.percentage?.toString().toLowerCase() || ''
    return months.includes(needle) || percentage.includes(needle)
  })
})

const exportColumns = [
  { key: 'serial', label: '#' },
  { key: 'months', label: 'No. of Aggregate Months of Service' },
  { key: 'percentage', label: 'Percentage of Basic Monthly Salary' },
  { key: 'cash_gift', label: 'Cash Gift' }
]

function buildExportPayload() {
  const data = exportData.value.map((row, index) => ({
    ...row,
    serial: index + 1
  }))
  return {
    title: 'Year End Bonus Table Setup',
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