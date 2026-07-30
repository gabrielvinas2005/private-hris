<template>
  <MainLayout>
    <template #header>
      <div class="title">Document Type Setup</div>
    </template>

    <div class="document-type-setup">
      <DocumentTypeFilters :showSave="showSave" :saving="saving" @search="v => (searchTerm = v)" @filter="v => (statusFilter = v)" @add="handleAdd" @save="handleSave" />

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

      <DocumentTypeTable ref="tableRef" :items="pagedItems" :visible="columnVisibility" @delete="confirmDelete" @toggle-active="updateActive" />

      <div style="margin-top: 12px; display: flex; justify-content: flex-end;">
        <el-pagination layout="total, prev, pager, next, jumper" background small :page-size="pageSize" :total="filteredItems.length" v-model:current-page="currentPage" />
      </div>
    </div>
  </MainLayout>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Printer, Download, Document, Setting, ArrowDown } from '@element-plus/icons-vue'
import MainLayout from '../../../Layout/MainLayout.vue'
import DocumentTypeFilters from '../../../components/HR_Setup/Document_Type_Setup/DocumentTypeFilters.vue'
import DocumentTypeTable from '../../../components/HR_Setup/Document_Type_Setup/DocumentTypeTable.vue'
import { useDocumentTypes } from '../../../composables/useDocumentTypes'
import { useExport } from '../../../composables/useExport.js'

const { items, loading, saving, fetchList, addRow, remove, saveBulk } = useDocumentTypes()

const searchTerm = ref('')
const statusFilter = ref('')
const showSave = ref(false)
const pageSize = ref(10)
const currentPage = ref(1)
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
    const matchesSearch = !term || (r.name || '').toLowerCase().includes(term)
    const matchesStatus = !statusFilter.value || (statusFilter.value === 'Active' ? r.active : !r.active)
    return matchesSearch && matchesStatus
  })
})

const pagedItems = computed(() => {
  const start = (currentPage.value - 1) * pageSize.value
  return filteredItems.value.slice(start, start + pageSize.value)
})

watch(filteredItems, () => { currentPage.value = 1 })

function handleAdd() { addRow(); showSave.value = true }
async function handleSave() { const res = await saveBulk(); if (res && res.success) { showSave.value = false; ElMessage.success(res.message || 'Saved') } }
async function updateActive() { const res = await saveBulk(); if (res && res.success) { ElMessage.success(res.message || 'Active status updated') } }
async function confirmDelete(row) { try { await ElMessageBox.confirm('Delete this type?', 'Confirm', { type: 'warning' }); const res = await remove(row); if (res && res.success) ElMessage.success(res.message || 'Deleted') } catch(e){} }

function handleColumnToggle(column) {
  // Column visibility is handled by the checkbox v-model
}

function getColumnLabel(key) {
  const labels = {
    serial: 'Serial Number',
    name: 'Document Type Name',
    active: 'Active Status',
    actions: 'Actions'
  }
  return labels[key] || key
}

// Export functions
function handlePrint() {
  const data = tableRef.value?.getFilteredData() || filteredItems.value
  const columns = [
    { key: 'name', label: 'Document Type Name' },
    { key: 'active', label: 'Active', formatter: (row) => row.active ? 'Yes' : 'No' }
  ]
  exportPrint({ title: 'Document Type Setup', data, columns, columnVisibility: columnVisibility.value })
}

function handleExportExcel() {
  const data = tableRef.value?.getFilteredData() || filteredItems.value
  const columns = [
    { key: 'name', label: 'Document Type Name' },
    { key: 'active', label: 'Active', formatter: (row) => row.active ? 'Yes' : 'No' }
  ]
  exportExcel({ title: 'Document Type Setup', data, columns, columnVisibility: columnVisibility.value })
}

function handleExportPDF() {
  const data = tableRef.value?.getFilteredData() || filteredItems.value
  const columns = [
    { key: 'name', label: 'Document Type Name' },
    { key: 'active', label: 'Active', formatter: (row) => row.active ? 'Yes' : 'No' }
  ]
  exportPDF({ title: 'Document Type Setup', data, columns, columnVisibility: columnVisibility.value })
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

