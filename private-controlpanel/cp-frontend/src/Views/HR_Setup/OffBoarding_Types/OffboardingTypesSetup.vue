<template>
  <MainLayout>
    <template #header>
      <div class="title">Off-Boarding Types Setup</div>
    </template>

    <div class="offboarding-setup">
      <OffboardingFilters
        :showSave="showSave"
        @search="v => (searchTerm = v)"
        @filter="v => (statusFilter = v)"
        @add="handleAdd"
        @save="handleSave"
      />

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

      <OffboardingTable
        ref="tableRef"
        :items="filteredItems"
        :visible="columnVisibility"
        @delete="confirmDelete"
        @toggle-active="saveBulk"
      />
    </div>
  </MainLayout>
</template>

<script setup>
import { onMounted, computed, ref } from 'vue'
import { Printer, Download, Document, Setting, ArrowDown } from '@element-plus/icons-vue'
import MainLayout from '../../../Layout/MainLayout.vue'
import OffboardingFilters from '../../../components/HR_Setup/OffBoarding_Types/OffboardingFilters.vue'
import OffboardingTable from '../../../components/HR_Setup/OffBoarding_Types/OffboardingTable.vue'
import { useOffboardingTypes } from '../../../composables/useOffboardingTypes.js'
import { useExport } from '../../../composables/useExport.js'

const { items, loading, saving, fetchList, saveBulk, remove, addRow } = useOffboardingTypes()
const searchTerm = ref('')
const statusFilter = ref('')
const showSave = ref(false)
const tableRef = ref(null)

// Export composable
const { exportPrint, exportExcel, exportPDF } = useExport()

// Column visibility
const columnVisibility = ref({
  serial: true,
  name: true,
  active: true,
  actions: true
})

const filteredItems = computed(() => {
  const term = (searchTerm.value || '').toLowerCase()
  return (items.value || []).filter(r => {
    const matchesSearch = !term || (r.name && r.name.toLowerCase().includes(term))
    const matchesStatus = !statusFilter.value || (statusFilter.value === 'Active' ? r.active : !r.active)
    return matchesSearch && matchesStatus
  })
})

async function confirmDelete(row) {
  await remove(row.id)
}

function handleAdd() {
  addRow()
  showSave.value = true
}

async function handleSave() {
  const res = await saveBulk()
  if (res && res.success) {
    showSave.value = false
  }
}

function handleColumnToggle(column) {
  // Column visibility is handled by the checkbox v-model
}

function getColumnLabel(key) {
  const labels = {
    serial: 'Serial Number',
    name: 'Name',
    active: 'Active',
    actions: 'Actions'
  }
  return labels[key] || key
}

// Export functions
function handlePrint() {
  const data = tableRef.value?.getFilteredData() || filteredItems.value
  const columns = [
    { key: 'name', label: 'Name' },
    { key: 'active', label: 'Active', formatter: (row) => row.active ? 'Yes' : 'No' }
  ]
  exportPrint({ title: 'Off-Boarding Types Setup', data, columns, columnVisibility: columnVisibility.value })
}

function handleExportExcel() {
  const data = tableRef.value?.getFilteredData() || filteredItems.value
  const columns = [
    { key: 'name', label: 'Name' },
    { key: 'active', label: 'Active', formatter: (row) => row.active ? 'Yes' : 'No' }
  ]
  exportExcel({ title: 'Off-Boarding Types Setup', data, columns, columnVisibility: columnVisibility.value })
}

function handleExportPDF() {
  const data = tableRef.value?.getFilteredData() || filteredItems.value
  const columns = [
    { key: 'name', label: 'Name' },
    { key: 'active', label: 'Active', formatter: (row) => row.active ? 'Yes' : 'No' }
  ]
  exportPDF({ title: 'Off-Boarding Types Setup', data, columns, columnVisibility: columnVisibility.value })
}

onMounted(fetchList)
</script>

<style scoped>
.title { 
  font-weight: 600; 
  font-size: 1.5rem; 
  color: #303133; 
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

 