<template>
  <MainLayout>
    <template #header><div class="page-header"><div class="title">Leave Types Setup</div></div></template>

    <el-card shadow="never" class="block-card">
      <div class="filters-row">
        <div class="left">
          <el-input v-model="search" placeholder="Search by name..." clearable class="search-input" />
          <el-select v-model="statusFilter" placeholder="Filter by status" clearable class="status-select">
            <el-option label="Active" value="active" />
            <el-option label="Inactive" value="inactive" />
          </el-select>
        </div>
        <div class="actions">
          <el-button type="primary" @click="handleAdd">Add Leave Type</el-button>
          <el-button type="success" :loading="saving" @click="handleSave">Save Changes</el-button>
        </div>
      </div>
    </el-card>

    <el-card shadow="never" class="block-card">
      <div class="export-row">
        <div class="export-buttons">
          <el-button @click="handlePrint"><el-icon><Printer /></el-icon> Print</el-button>
          <el-button @click="handleExportExcel"><el-icon><Download /></el-icon> Excel</el-button>
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
      <el-table :data="filteredRows" v-loading="loading" border style="width:100%">
        <el-table-column v-if="columnVisibility.serial" label="#" width="60" align="center">
          <template #default="{ $index }">{{ $index + 1 }}</template>
        </el-table-column>

        <el-table-column v-if="columnVisibility.name" label="Name" min-width="200">
          <template #default="{ row }"><el-input v-model="row.name" placeholder="Name" /></template>
        </el-table-column>

        <el-table-column v-if="columnVisibility.accrued_id" label="Accrued" width="140" align="center">
          <template #default="{ row }">
            <el-select v-model="row.accrued_id" placeholder="Select" class="w-100">
              <el-option label="No" :value="0" />
              <el-option label="Yes" :value="1" />
            </el-select>
          </template>
        </el-table-column>

        <el-table-column v-if="columnVisibility.accrual_amount" label="Accrual Amount" width="160" align="center">
          <template #default="{ row }"><el-input v-model.number="row.accrual_amount" placeholder="Amount" /></template>
        </el-table-column>

        <el-table-column v-if="columnVisibility.accrual_frequency_id" label="Frequency" width="180" align="center">
          <template #default="{ row }">
            <el-select v-model="row.accrual_frequency_id" placeholder="Select frequency" class="w-100">
              <el-option label="None" :value="0" />
              <el-option label="Monthly" :value="1" />
              <el-option label="Yearly" :value="2" />
            </el-select>
          </template>
        </el-table-column>

        <el-table-column v-if="columnVisibility.leave_balance_policy_id" label="Balance Policy" width="220" align="center">
          <template #default="{ row }">
            <el-select v-model="row.leave_balance_policy_id" placeholder="Select policy" class="w-100">
              <el-option label="Reset balance every start of year" :value="1" />
              <el-option label="Maximum days per availment" :value="2" />
              <el-option label="Carries balance every start of year" :value="3" />
            </el-select>
          </template>
        </el-table-column>

        <el-table-column v-if="columnVisibility.is_editable_id" label="Editable" width="120" align="center">
          <template #default="{ row }"><el-switch v-model="row.is_editable" @change="handleToggle(row)" /></template>
        </el-table-column>

        <el-table-column v-if="columnVisibility.service_credit" label="Service Credit" width="140" align="center">
          <template #default="{ row }"><el-switch v-model="row.service_credit" @change="handleToggle(row)" /></template>
        </el-table-column>

        <el-table-column v-if="columnVisibility.is_el" label="Emergency Leave" width="120" align="center">
          <template #default="{ row }"><el-switch v-model="row.is_el" @change="handleToggle(row)" /></template>
        </el-table-column>

        <el-table-column v-if="columnVisibility.active" label="Active" width="120" align="center">
          <template #default="{ row }"><el-switch v-model="row.active" @change="handleToggle(row)" /></template>
        </el-table-column>

        <el-table-column v-if="columnVisibility.actions" label="Actions" width="120" fixed="right" align="center">
          <template #default="{ row }">
            <el-popconfirm title="Delete this leave type?" @confirm="handleDelete(row)" confirm-button-text="Delete" cancel-button-text="Cancel">
              <template #reference><el-button type="danger" size="small">Delete</el-button></template>
            </el-popconfirm>
          </template>
        </el-table-column>
      </el-table>
    </el-card>
  </MainLayout>
</template>

<script setup>
import { onMounted, ref, computed } from 'vue'
import MainLayout from '../../../Layout/MainLayout.vue'
import { useLeaveTypes } from '../../../composables/useLeaveTypes.js'
import { useExport } from '../../../composables/useExport.js'
import { Printer, Download, Document, Setting, ArrowDown } from '@element-plus/icons-vue'

const { rows, loading, saving, fetchLeaveTypes, addBlankRow, saveAll, deleteOne } = useLeaveTypes()
onMounted(fetchLeaveTypes)

const search = ref('')
const statusFilter = ref('')

const filteredRows = computed(() => rows.value.filter(r => {
  const matchesSearch = !search.value || (r.name || '').toLowerCase().includes(search.value.toLowerCase())
  const matchesStatus = !statusFilter.value || (statusFilter.value === 'active' ? r.active : !r.active)
  return matchesSearch && matchesStatus
}))

const { exportPrint, exportExcel, exportPDF } = useExport()

const exportColumns = [
  { key: 'serial', label: '#' },
  { key: 'name', label: 'Name' },
  { key: 'accrued_id', label: 'Accrued', formatter: row => (row.accrued_id ? 'Yes' : 'No') },
  { key: 'accrual_amount', label: 'Accrual Amount' },
  { key: 'accrual_frequency_id', label: 'Frequency', formatter: row => frequencyLabel(row.accrual_frequency_id) },
  { key: 'leave_balance_policy_id', label: 'Balance Policy', formatter: row => balancePolicyLabel(row.leave_balance_policy_id) },
  { key: 'is_editable_id', label: 'Editable', formatter: row => (row.is_editable ? 'Yes' : 'No') },
  { key: 'service_credit', label: 'Service Credit', formatter: row => (row.service_credit ? 'Yes' : 'No') },
  { key: 'is_el', label: 'EL', formatter: row => (row.is_el ? 'Yes' : 'No') },
  { key: 'active', label: 'Status', formatter: row => (row.active ? 'Active' : 'Inactive') }
]

function frequencyLabel(value) {
  const map = { 0: 'None', 1: 'Monthly', 2: 'Yearly' }
  return map[value] || ''
}

function balancePolicyLabel(value) {
  const map = {
    1: 'Reset balance every start of year',
    2: 'Maximum days per availment',
    3: 'Carries balance every start of year'
  }
  return map[value] || ''
}

function buildExportPayload() {
  const data = filteredRows.value.map((row, index) => ({
    ...row,
    serial: index + 1
  }))
  return {
    title: 'Leave Types Setup',
    data,
    columns: exportColumns,
    columnVisibility: columnVisibility.value
  }
}

const columnVisibility = ref({
  serial: true, name: true, accrued_id: true, accrual_amount: true, accrual_frequency_id: true,
  leave_balance_policy_id: true, is_editable_id: true, service_credit: true, is_el: true, active: true, actions: true
})
function getColumnLabel(key) {
  const map = {
    serial: '#', name: 'Name', accrued_id: 'Accrued', accrual_amount: 'Accrual Amount', accrual_frequency_id: 'Frequency',
    leave_balance_policy_id: 'Balance Policy', is_editable_id: 'Editable', service_credit: 'Service Credit', is_el: 'EL', active: 'Active', actions: 'Actions'
  }
  return map[key] || key
}

function handleAdd() { addBlankRow() }
async function handleSave() { await saveAll() }
async function handleDelete(row) {
  if (!row.id) {
    const i = rows.value.indexOf(row)
    if (i >= 0) rows.value.splice(i, 1)
    return
  }
  await deleteOne(row.id)
}
async function handleToggle() { await saveAll() }

function handlePrint() {
  exportPrint(buildExportPayload())
}
function handleExportExcel() {
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
.status-select { width: 220px; }
.actions { display: flex; gap: 8px; }
.export-row { display: flex; justify-content: space-between; align-items: center; }
.export-buttons { display: flex; gap: 10px; }
.column-visibility { padding: 8px 12px; }
.w-100 { width: 100%; }
</style>

