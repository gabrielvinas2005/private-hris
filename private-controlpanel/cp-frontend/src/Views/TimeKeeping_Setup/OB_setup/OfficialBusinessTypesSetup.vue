<template>
  <MainLayout>
    <template #header><div class="page-header"><div class="title">Official Business Types Setup</div></div></template>

    <el-card shadow="never" class="block-card">
      <div class="filters-row">
        <div class="left">
          <el-input v-model="search" placeholder="Search by name..." clearable class="search-input" />
        </div>
        <div class="actions">
          <el-button type="primary" @click="handleAdd">Add Type</el-button>
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

        <el-table-column v-if="columnVisibility.name" label="Name" min-width="260">
          <template #default="{ row }"><el-input v-model="row.name" placeholder="Name" /></template>
        </el-table-column>

        <el-table-column v-if="columnVisibility.active" label="Active" width="120" align="center">
          <template #default="{ row }"><el-switch v-model="row.active" @change="handleToggle(row)" /></template>
        </el-table-column>

        <el-table-column v-if="columnVisibility.actions" label="Actions" width="120" fixed="right" align="center">
          <template #default="{ row }">
            <el-popconfirm title="Delete this type?" @confirm="handleDelete(row)" confirm-button-text="Delete" cancel-button-text="Cancel">
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
import { useOfficialBusinessTypes } from '../../../composables/useOfficialBusinessTypes.js'
import { useExport } from '../../../composables/useExport.js'
import { Printer, Download, Document, Setting, ArrowDown } from '@element-plus/icons-vue'

const { rows, loading, saving, total, fetchItems, addBlankRow, saveAll, deleteOne } = useOfficialBusinessTypes()
onMounted(fetchItems)

const search = ref('')
const columnVisibility = ref({ serial: true, name: true, active: true, actions: true })
function getColumnLabel(key) {
  const map = { serial: '#', name: 'Name', active: 'Active', actions: 'Actions' }
  return map[key] || key
}
const filteredRows = computed(() => rows.value.filter(r => !search.value || (r.name || '').toLowerCase().includes(search.value.toLowerCase())))

const { exportPrint, exportExcel, exportPDF } = useExport()

const exportColumns = [
  { key: 'serial', label: '#' },
  { key: 'name', label: 'Name' },
  { key: 'active', label: 'Status', formatter: row => (row.active ? 'Active' : 'Inactive') }
]

function buildExportPayload() {
  const data = filteredRows.value.map((row, index) => ({
    ...row,
    serial: index + 1
  }))
  return {
    title: 'Official Business Types Setup',
    data,
    columns: exportColumns,
    columnVisibility: columnVisibility.value
  }
}

function handleAdd() { addBlankRow() }
async function handleSave() { await saveAll() }
async function handleDelete(row) {
  if (!row.id) { const i = rows.value.indexOf(row); if (i >= 0) rows.value.splice(i, 1); return }
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
.actions { display: flex; gap: 8px; }
.export-row { display: flex; justify-content: space-between; align-items: center; }
.export-buttons { display: flex; gap: 10px; }
</style>


