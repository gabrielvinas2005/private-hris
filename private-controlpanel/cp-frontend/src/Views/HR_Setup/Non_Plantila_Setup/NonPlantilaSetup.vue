<template>
  <MainLayout>
    <template #header>
      <div class="title">Non-Plantila Setup</div>
    </template>
    <div class="non-plantila-setup">
      <!-- Header + controls -->
      <NonPlantilaFilters
        :departments="formOptions.departments"
        @search="v => (searchTerm = v)"
        @filter="f => { statusFilter = f.status; departmentFilter = f.department }"
        @add="openAdd"
      />

      <!-- Statistics cards -->
      <div class="stats">
        <el-card class="stat-card" shadow="never">
          <div class="stat-number">{{ totalCount }}</div>
          <div class="stat-label">TOTAL NON-PLANTILLAS</div>
        </el-card>
        <el-card class="stat-card" shadow="never">
          <div class="stat-number">{{ activeCount }}</div>
          <div class="stat-label">ACTIVE NON-PLANTILLAS</div>
        </el-card>
        <el-card class="stat-card" shadow="never">
          <div class="stat-number">{{ totalVacant }}</div>
          <div class="stat-label">VACANT SLOTS</div>
        </el-card>
        <el-card class="stat-card" shadow="never">
          <div class="stat-number">{{ inactiveCount }}</div>
          <div class="stat-label">INACTIVE</div>
        </el-card>
      </div>

      <!-- Export and Column Visibility Section -->
      <div class="export-section">
        <el-row :gutter="20" class="export-row">
          <el-col :span="12">
            <div class="export-buttons">
              <el-button type="default" :icon="Printer" @click="handlePrint">Print</el-button>
              <el-button type="default" :icon="Download" @click="handleExportExcel">Excel</el-button>
              <el-button type="default" :icon="Document" @click="handleExportPDF">PDF</el-button>
            </div>
          </el-col>
          <el-col :span="12">
            <div class="column-visibility">
              <el-dropdown @command="handleColumnToggle">
                <el-button type="default" :icon="Setting">
                  Column Visibility
                  <el-icon class="el-icon--right"><ArrowDown /></el-icon>
                </el-button>
                <template #dropdown>
                  <el-dropdown-menu>
                    <el-dropdown-item
                      v-for="(visible, key) in columnVisibility"
                      :key="key"
                      :command="key"
                    >
                      <el-checkbox
                        v-model="columnVisibility[key]"
                        @change="handleColumnToggle(key)"
                      >
                        {{ getColumnLabel(key) }}
                      </el-checkbox>
                    </el-dropdown-item>
                  </el-dropdown-menu>
                </template>
              </el-dropdown>
            </div>
          </el-col>
        </el-row>
      </div>

      <el-card shadow="never" class="card">
        <NonPlantilaTable ref="tableRef" :items="filteredItems" :visible="columnVisibility" @edit="openEdit" @delete="confirmDelete" />
      </el-card>

      <NonPlantilaModal
        v-model="showModal"
        :record="current"
        :form-options="formOptions"
        :loading="formLoading"
        @save="handleSave"
      />
    </div>
  </MainLayout>
  
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { Printer, Download, Document, Setting, ArrowDown } from '@element-plus/icons-vue'
import MainLayout from '../../../Layout/MainLayout.vue'
import NonPlantilaTable from '../../../components/HR_Setup/Non_Plantila_Setup/NonPlantilaTable.vue'
import NonPlantilaModal from '../../../components/HR_Setup/Non_Plantila_Setup/NonPlantilaModal.vue'
import NonPlantilaFilters from '../../../components/HR_Setup/Non_Plantila_Setup/NonPlantilaFilters.vue'
import { useNonPlantilla } from '../../../composables/useNonPlantilla.js'
import { useExport } from '../../../composables/useExport.js'

const { items, loading, formLoading, formOptions, fetchList, fetchFormData, save, remove } = useNonPlantilla()

const showModal = ref(false)
const current = ref(null)
const tableRef = ref(null)

// Controls
const searchTerm = ref('')
const statusFilter = ref('')
const departmentFilter = ref('')

// Export composable
const { exportPrint, exportExcel, exportPDF } = useExport()

const filteredItems = computed(() => {
  const term = (searchTerm.value || '').toLowerCase()
  return (items.value || []).filter(r => {
    const matchesSearch = !term ||
      (r.position && r.position.toLowerCase().includes(term)) ||
      (r.department && r.department.toLowerCase().includes(term))
    const matchesStatus = !statusFilter.value || r.status === statusFilter.value
    const matchesDept = !departmentFilter.value || r.department === departmentFilter.value
    return matchesSearch && matchesStatus && matchesDept
  })
})

// Column visibility state
const columnVisibility = ref({ 
  position: true, 
  department: true, 
  salary: true, 
  vacant: true, 
  status: true, 
  actions: true 
})

// Stats
const totalCount = computed(() => items.value.length)
const activeCount = computed(() => items.value.filter(r => r.status === 'Active').length)
const inactiveCount = computed(() => items.value.filter(r => r.status === 'Inactive').length)
const totalVacant = computed(() => items.value.reduce((s, r) => s + (Number(r.vacant) || 0), 0))

function openAdd() {
  current.value = null
  showModal.value = true
}

async function openEdit(row) {
  current.value = { ...row }
  showModal.value = true
}

async function handleSave(payload, id) {
  const res = await save(payload, id || 0)
  if (res.success) showModal.value = false
}

async function confirmDelete(row) {
  await remove(row.id)
}

function handleColumnToggle(column) {
  // Column visibility is handled by the checkbox v-model
}

function getColumnLabel(key) {
  const labels = {
    position: 'Position',
    department: 'Department',
    salary: 'Salary',
    vacant: 'Vacant',
    status: 'Status',
    actions: 'Actions'
  }
  return labels[key] || key
}

// Export functions
function handlePrint() {
  const data = tableRef.value?.getFilteredData() || filteredItems.value
  const columns = [
    { key: 'position', label: 'Position' },
    { key: 'department', label: 'Department' },
    { key: 'salary', label: 'Salary' },
    { key: 'vacant', label: 'Vacant' },
    { key: 'status', label: 'Status' }
  ]
  exportPrint({ title: 'Non-Plantilla Setup', data, columns, columnVisibility: columnVisibility.value })
}

function handleExportExcel() {
  const data = tableRef.value?.getFilteredData() || filteredItems.value
  const columns = [
    { key: 'position', label: 'Position' },
    { key: 'department', label: 'Department' },
    { key: 'salary', label: 'Salary' },
    { key: 'vacant', label: 'Vacant' },
    { key: 'status', label: 'Status' }
  ]
  exportExcel({ title: 'Non-Plantilla Setup', data, columns, columnVisibility: columnVisibility.value })
}

function handleExportPDF() {
  const data = tableRef.value?.getFilteredData() || filteredItems.value
  const columns = [
    { key: 'position', label: 'Position' },
    { key: 'department', label: 'Department' },
    { key: 'salary', label: 'Salary' },
    { key: 'vacant', label: 'Vacant' },
    { key: 'status', label: 'Status' }
  ]
  exportPDF({ title: 'Non-Plantilla Setup', data, columns, columnVisibility: columnVisibility.value })
}

onMounted(async () => {
  await Promise.all([ fetchList(), fetchFormData(0) ])
})
</script>

<style scoped>
.title { 
  font-weight: 600; 
  font-size: 1.5rem; 
  color: #303133; 
}

.card { 
  margin-top: 10px; 
}

.export-section {
  background: #fff;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  margin-bottom: 20px;
}

.export-row {
  align-items: center;
}

.export-buttons {
  display: flex;
  gap: 12px;
}

.column-visibility {
  display: flex;
  justify-content: flex-end;
}

.toolbar { 
  display: flex; 
  justify-content: space-between; 
  align-items: center; 
  margin-bottom: 12px; 
}

.toolbar-controls { 
  display: flex; 
  gap: 10px; 
  align-items: center; 
}

.toolbar-search { 
  width: 320px; 
}

.toolbar-select { 
  width: 200px; 
}

.stats { 
  display: grid; 
  grid-template-columns: repeat(4, 1fr); 
  gap: 16px; 
  margin-bottom: 16px; 
}

.stat-card { 
  text-align: center; 
}

.stat-number { 
  font-size: 2rem; 
  font-weight: bold; 
  color: #409eff; 
}

.stat-label { 
  color: #606266; 
  text-transform: uppercase; 
  font-size: 12px; 
  letter-spacing: .5px; 
}

.actions { 
  display: flex; 
  justify-content: space-between; 
  align-items: center; 
  margin: 10px 0; 
}

:deep(.el-button) {
  border-radius: 6px;
  font-weight: 500;
}

:deep(.el-button--default) {
  background-color: #fff;
  border-color: #dcdfe6;
  color: #606266;
}

:deep(.el-button--default:hover) {
  background-color: #f5f7fa;
  border-color: #c0c4cc;
}

:deep(.el-dropdown-menu__item) {
  padding: 8px 20px;
}

:deep(.el-checkbox) {
  margin-right: 0;
}
</style>

