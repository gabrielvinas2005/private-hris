<template>
  <MainLayout>
    <template #header>
      <div class="page-header"><div class="title">Loyalty Award Setup</div></div>
    </template>

    <el-card shadow="never" class="block-card">
      <div class="filters-row">
        <div class="left">
          <el-input v-model="search" placeholder="Search by year..." clearable class="search-input" />
        </div>
        <div class="actions">
          <el-button type="primary" @click="openForm">Add Award</el-button>
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
      <el-table v-else :data="filteredAwards" border style="width:100%">
        <el-table-column v-if="columnVisibility.serial" label="#" width="60" align="center">
          <template #default="{ $index }">{{ $index + 1 }}</template>
        </el-table-column>
        <el-table-column v-if="columnVisibility.years_of_service" prop="years_of_service" label="Years of Service" min-width="180" />
        <el-table-column v-if="columnVisibility.cash_award" prop="cash_award" label="Cash Award" min-width="160">
          <template #default="{ row }">₱{{ formatNumber(row.cash_award) }}</template>
        </el-table-column>
        <el-table-column v-if="columnVisibility.cash_token" prop="cash_token" label="Cash Token" min-width="160">
          <template #default="{ row }">₱{{ formatNumber(row.cash_token) }}</template>
        </el-table-column>
        <el-table-column v-if="columnVisibility.actions" label="Actions" width="100" fixed="right" align="center">
          <template #default="{ row }">
            <el-popconfirm title="Delete this award?" @confirm="handleDelete(row)" confirm-button-text="Delete" cancel-button-text="Cancel">
              <template #reference><el-button size="small" type="danger">Delete</el-button></template>
            </el-popconfirm>
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <el-dialog v-model="formVisible" title="Add Loyalty Award" width="800px" append-to-body>
      <div v-loading="formLoading">
        <div class="section-header">
          <h4>New Award</h4>
          <el-button type="primary" size="small" @click="addRow">Add Row</el-button>
        </div>
        <el-table :data="formData" border style="width:100%" max-height="320">
          <el-table-column label="Years of Service" width="180">
            <template #default="{ row }"><el-input v-model="row.years_of_service" placeholder="0" type="number" min="0" /></template>
          </el-table-column>
          <el-table-column label="Cash Award" width="200">
            <template #default="{ row }"><el-input v-model="row.cash_award" placeholder="0.00" type="number" step="0.01" min="0"><template #prepend>₱</template></el-input></template>
          </el-table-column>
          <el-table-column label="Actions" width="120" align="center">
            <template #default="{ $index }"><el-button size="small" type="danger" @click="removeRow($index)">Remove</el-button></template>
          </el-table-column>
        </el-table>
        <div class="dialog-actions">
          <el-button @click="formVisible = false">Cancel</el-button>
          <el-button type="primary" :loading="saving" @click="saveAwards">Save Changes</el-button>
        </div>
      </div>
    </el-dialog>
  </MainLayout>
</template>

<script setup>
import { onMounted, computed } from 'vue'
import MainLayout from '../../../Layout/MainLayout.vue'
import { useLoyaltyAward } from '../../../composables/useLoyaltyAward.js'
import { useExport } from '../../../composables/useExport.js'
import { Printer, Download, Document, Setting, ArrowDown } from '@element-plus/icons-vue'

const {
  awards, loading, apiError, fetchAwards,
  formVisible, formLoading, formData,
  openForm, addRow, removeRow, saveAwards, deleteAward,
  saving, search, filteredAwards, columnVisibility
} = useLoyaltyAward()

const { exportPrint, exportExcel, exportPDF } = useExport()

onMounted(fetchAwards)

function formatNumber(value) {
  if (!value && value !== 0) return '0.00'
  return Number(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

async function handleDelete(row) { await deleteAward(row.id) }

const visibilityKeys = computed(() => Object.keys(columnVisibility.value))
function getColumnLabel(key) {
  const map = { serial: '#', years_of_service: 'Years of Service', cash_award: 'Cash Award', cash_token: 'Cash Token', actions: 'Actions' }
  return map[key] || key
}

const exportColumns = [
  { key: 'serial', label: '#' },
  { key: 'years_of_service', label: 'Years of Service' },
  { key: 'cash_award', label: 'Cash Award', formatter: (row) => `₱${formatNumber(row.cash_award)}` },
  { key: 'cash_token', label: 'Cash Token', formatter: (row) => `₱${formatNumber(row.cash_token)}` }
]

function buildExportPayload() {
  const data = filteredAwards.value.map((row, index) => ({
    ...row,
    serial: index + 1
  }))
  return {
    title: 'Loyalty Award Setup',
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
.dialog-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 16px; }
.section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
.section-header h4 { margin: 0; color: #409eff; font-size: 15px; font-weight: 600; }
</style>