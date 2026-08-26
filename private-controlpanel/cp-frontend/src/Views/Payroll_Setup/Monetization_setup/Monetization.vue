<template>
  <MainLayout>
    <template #header>
      <div class="page-header"><div class="title">Monetization Setup</div></div>
    </template>

    <el-card shadow="never" class="block-card">
      <MonetizationFilters @edit="handleEdit" />
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
      <MonetizationTable
        :monetization-setup="monetizationSetup"
        :loading="loading"
        :api-error="apiError"
        :column-visibility="columnVisibility"
      />
    </el-card>

    <!-- Edit Monetization Setup Modal -->
    <MonetizationModal
      v-model="formVisible"
      :form-data="formData"
      :form-loading="formLoading"
      :saving="saving"
      @save="handleSave"
      @cancel="handleCancel"
    />
  </MainLayout>
</template>

<script setup>
import { onMounted, ref, computed } from 'vue'
import MainLayout from '../../../Layout/MainLayout.vue'
import { useMonetizationSetup } from '../../../composables/useMonetizationSetup.js'
import { useExport } from '../../../composables/useExport.js'
import { Printer, Download, Document, Setting, ArrowDown } from '@element-plus/icons-vue'
import MonetizationFilters from '../../../components/Payroll_Setup/Monetization_setup/MonetizationFilters.vue'
import MonetizationTable from '../../../components/Payroll_Setup/Monetization_setup/MonetizationTable.vue'
import MonetizationModal from '../../../components/Payroll_Setup/Monetization_setup/MonetizationModal.vue'
import { ElMessage } from 'element-plus'

const {
  monetizationSetup, loading, apiError, fetchMonetizationSetup,
  formVisible, formLoading, formData,
  openForm, saveMonetizationSetup,
  saving
} = useMonetizationSetup()

const { exportPrint, exportExcel, exportPDF } = useExport()

onMounted(() => {
  fetchMonetizationSetup()
})

const columnVisibility = ref({
  serial: true,
  cf_rate: true,
  maximum_number_allowed: true
})

function getColumnLabel(key) {
  const map = {
    serial: '#', 
    cf_rate: 'CF Rate', 
    maximum_number_allowed: 'Maximum Number Allowed'
  }
  return map[key] || key
}

function handleEdit() { 
  openForm() 
}

async function handleSave() {
  try {
    await saveMonetizationSetup()
    ElMessage.success('Monetization setup updated successfully')
  } catch (error) {
    ElMessage.error('Failed to save monetization setup')
  }
}

function handleCancel() {
  formVisible.value = false
}

const exportColumns = [
  { key: 'serial', label: '#' },
  { key: 'cf_rate', label: 'CF Rate', formatter: (row) => Number(row.cf_rate || 0).toFixed(7) },
  { key: 'maximum_number_allowed', label: 'Maximum Number Allowed' }
]

function buildExportPayload() {
  const data = monetizationSetup.value && Object.keys(monetizationSetup.value).length > 0
    ? [{
        ...monetizationSetup.value,
        serial: 1
      }]
    : []

  return {
    title: 'Monetization Setup',
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