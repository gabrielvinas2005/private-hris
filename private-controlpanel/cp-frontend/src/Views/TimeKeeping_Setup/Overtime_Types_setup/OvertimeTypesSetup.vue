<template>
  <MainLayout>
    <template #header>
      <div class="page-header">
        <div class="title">Overtime Types Setup</div>
      </div>
    </template>

    <!-- Block 1: Filters + Add/Save -->
    <el-card shadow="never" class="block-card">
      <div class="filters-row">
        <div class="left">
          <el-input
            v-model="search"
            placeholder="Search overtime types..."
            clearable
            class="search-input"
          />
          <el-select v-model="statusFilter" placeholder="Filter by status" clearable class="status-select">
            <el-option label="Active" value="active" />
            <el-option label="Inactive" value="inactive" />
          </el-select>
        </div>
        <div class="actions">
          <el-button type="primary" @click="handleAdd">Add Overtime Type</el-button>
          <el-button type="success" :loading="saving" @click="handleSave">Save Changes</el-button>
        </div>
      </div>
    </el-card>

    <!-- Block 2: Export + Column Visibility -->
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
          <template #default="{ row }">
            <el-input v-model="row.name" placeholder="Name" />
          </template>
        </el-table-column>

        <el-table-column v-if="columnVisibility.rate" label="Rate" width="120" align="center">
          <template #default="{ row }">
            <el-input v-model="row.rate" placeholder="Rate" />
          </template>
        </el-table-column>

        

        

        <el-table-column v-if="columnVisibility.min_ot" label="Min OT" width="120" align="center">
          <template #default="{ row }">
            <el-input v-model="row.min_ot" placeholder="Min OT" />
          </template>
        </el-table-column>

        <el-table-column v-if="columnVisibility.max_ot" label="Max OT" width="120" align="center">
          <template #default="{ row }">
            <el-input v-model="row.max_ot" placeholder="Max OT" />
          </template>
        </el-table-column>

        <el-table-column v-if="columnVisibility.active" label="Active" width="100" align="center">
          <template #default="{ row }">
            <el-switch v-model="row.active" @change="handleToggle(row)" />
          </template>
        </el-table-column>

        <el-table-column v-if="columnVisibility.actions" label="Actions" width="120" fixed="right" align="center">
          <template #default="{ row }">
            <el-popconfirm title="Delete this row?" @confirm="handleDelete(row)" confirm-button-text="Delete" cancel-button-text="Cancel">
              <template #reference>
                <el-button type="danger" size="small">Delete</el-button>
              </template>
            </el-popconfirm>
          </template>
        </el-table-column>
      </el-table>
    </el-card>
  </MainLayout>
</template>

<script setup>
import { onMounted, ref, computed } from 'vue'
import { useOvertimeTypes } from '../../../composables/useOvertimeTypes.js'
import MainLayout from '../../../Layout/MainLayout.vue'
import { useExport } from '../../../composables/useExport.js'
import { Printer, Download, Document, Setting, ArrowDown } from '@element-plus/icons-vue'

const { rows, loading, saving, fetchOvertimeTypes, addBlankRow, saveAll, deleteOne } = useOvertimeTypes()

onMounted(fetchOvertimeTypes)

// filters
const search = ref('')
const statusFilter = ref('')

const filteredRows = computed(() => {
  return rows.value.filter(r => {
    const matchesSearch = !search.value || (r.name || '').toLowerCase().includes(search.value.toLowerCase())
    const matchesStatus = !statusFilter.value || (statusFilter.value === 'active' ? r.active : !r.active)
    return matchesSearch && matchesStatus
  })
})

const { exportPrint, exportExcel, exportPDF } = useExport()

const exportColumns = [
  { key: 'serial', label: '#' },
  { key: 'name', label: 'Name' },
  { key: 'rate', label: 'Rate' },
  { key: 'min_ot', label: 'Min OT' },
  { key: 'max_ot', label: 'Max OT' },
  { key: 'active', label: 'Status', formatter: row => (row.active ? 'Active' : 'Inactive') }
]

function buildExportPayload() {
  const data = filteredRows.value.map((row, index) => ({
    ...row,
    serial: index + 1
  }))
  return {
    title: 'Overtime Types Setup',
    data,
    columns: exportColumns,
    columnVisibility: columnVisibility.value
  }
}

// column visibility
const columnVisibility = ref({
  serial: true,
  name: true,
  rate: true,
  min_ot: true,
  max_ot: true,
  active: true,
  actions: true,
})

function getColumnLabel(key) {
  const map = {
    serial: '#',
    name: 'Name',
    rate: 'Rate',
    min_ot: 'Min OT',
    max_ot: 'Max OT',
    active: 'Active',
    actions: 'Actions'
  }
  return map[key] || key
}

function handleAdd() {
  addBlankRow()
}

async function handleSave() {
  await saveAll()
}

async function handleDelete(row) {
  if (!row.id) {
    const idx = rows.value.indexOf(row)
    if (idx >= 0) rows.value.splice(idx, 1)
    return
  }
  await deleteOne(row.id)
}

async function handleToggle(row) {
  // Persist the toggle immediately
  await saveAll()
}

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
.page-header {
  display: flex;
  align-items: center;
}
.title { font-weight: 600; font-size: 18px; }
.filters-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin: 10px 0 14px 0;
}
.block-card { margin-bottom: 12px; }
.left { display: flex; gap: 10px; align-items: center; }
.search-input { width: 380px; }
.status-select { width: 220px; }
.actions { display: flex; gap: 8px; }

.export-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}
.export-buttons { display: flex; gap: 10px; }
.column-visibility { padding: 8px 12px; }
</style>

