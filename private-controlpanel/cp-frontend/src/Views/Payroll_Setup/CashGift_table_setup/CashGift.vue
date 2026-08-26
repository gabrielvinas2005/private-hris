<template>
  <MainLayout>
    <template #header>
      <div class="page-header"><div class="title">Cash Gift Table Setup</div></div>
    </template>

    <el-card shadow="never" class="block-card">
      <CashGiftFilters
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
      <CashGiftTable
        :cash-gift-tables="cashGiftTables"
        :loading="loading"
        :api-error="apiError"
        :search="search"
        :column-visibility="columnVisibility"
        @delete="handleDelete"
      />
    </el-card>

    <!-- Edit Cash Gift Table Modal -->
    <CashGiftModal
      v-model="formVisible"
      :cash-gift-data="cashGiftData"
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
import { useCashGiftTable } from '../../../composables/useCashGiftTable.js'
import { useExport } from '../../../composables/useExport.js'
import { Printer, Download, Document, Setting, ArrowDown } from '@element-plus/icons-vue'
import CashGiftFilters from '../../../components/Payroll_Setup/CashGift_table_setup/CashGiftFilters.vue'
import CashGiftTable from '../../../components/Payroll_Setup/CashGift_table_setup/CashGiftTable.vue'
import CashGiftModal from '../../../components/Payroll_Setup/CashGift_table_setup/CashGiftModal.vue'
import { ElMessage } from 'element-plus'

const {
  cashGiftTables, loading, apiError, fetchCashGiftTables,
  formVisible, formLoading, cashGiftData,
  openForm, addCashGiftRow, removeCashGiftRow, saveCashGiftTables, deleteCashGiftTable,
  saving
} = useCashGiftTable()

const { exportPrint, exportExcel, exportPDF } = useExport()

onMounted(() => {
  fetchCashGiftTables()
})

const search = ref('')

const columnVisibility = ref({
  serial: true,
  months: true,
  percentage: true,
  actions: true
})

function getColumnLabel(key) {
  const map = {
    serial: '#', 
    months: 'No. of Aggregate Months of Service', 
    percentage: 'Percentage of Basic Monthly Salary',
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
    await deleteCashGiftTable(row.id)
    ElMessage.success('Cash gift record deleted successfully')
  } catch (error) {
    ElMessage.error('Failed to delete cash gift record')
  }
}

async function handleSave() {
  try {
    await saveCashGiftTables()
    ElMessage.success('Cash gift table updated successfully')
  } catch (error) {
    ElMessage.error('Failed to save cash gift table')
  }
}

function handleCancel() {
  formVisible.value = false
}

function handleAddRow() {
  addCashGiftRow()
}

function handleRemoveRow(index) {
  removeCashGiftRow(index)
}

const exportData = computed(() => {
  if (!search.value) return cashGiftTables.value
  const needle = search.value.toLowerCase()
  return cashGiftTables.value.filter(gift => {
    const months = gift.months?.toString().toLowerCase() || ''
    const percentage = gift.percentage?.toString().toLowerCase() || ''
    return months.includes(needle) || percentage.includes(needle)
  })
})

const exportColumns = [
  { key: 'serial', label: '#' },
  { key: 'months', label: 'No. of Aggregate Months of Service' },
  { key: 'percentage', label: 'Percentage of Basic Monthly Salary' }
]

function buildExportPayload() {
  const data = exportData.value.map((row, index) => ({
    ...row,
    serial: index + 1
  }))
  return {
    title: 'Cash Gift Table Setup',
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

