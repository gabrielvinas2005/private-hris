<template>
  <MainLayout>
    <template #header>
      <div class="title">Document Number Setup</div>
    </template>

    <div class="document-no-setup">
      <DocumentNoFilters
        :showSave="hasNewRows"
        :saving="saving"
        :canSave="canSave"
        @search="v => (searchTerm = v)"
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

      <DocumentNoTable
        ref="tableRef"
        :items="pagedItems"
        :visible="columnVisibility"
        :serial-start="serialStart"
        @delete="confirmDelete"
      />

      <div style="margin-top: 12px; display: flex; justify-content: flex-end;">
        <el-pagination
          layout="total, prev, pager, next, jumper"
          background
          small
          :page-size="pageSize"
          :total="filteredItems.length"
          v-model:current-page="currentPage"
        />
      </div>
    </div>
  </MainLayout>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Printer, Download, Document, Setting, ArrowDown } from '@element-plus/icons-vue'
import MainLayout from '../../../Layout/MainLayout.vue'
import DocumentNoFilters from '../../../components/HR_Setup/Document_No_Setup/DocumentNoFilters.vue'
import DocumentNoTable from '../../../components/HR_Setup/Document_No_Setup/DocumentNoTable.vue'
import { useDocumentNumbers } from '../../../composables/useDocumentNumbers'
import { useExport } from '../../../composables/useExport.js'

const { items, loading, saving, fetchList, addRow, remove, saveBulk } = useDocumentNumbers()

const searchTerm = ref('')
const pageSize = ref(10)
const currentPage = ref(1)
const tableRef = ref(null)

// Export composable
const { exportPrint, exportExcel, exportPDF } = useExport()

// Column visibility
const columnVisibility = ref({
  serial: true,
  name: true,
  rd_doc_no: true,
  rd_revision: true,
  co_doc_no: true,
  co_revision: true,
  actions: true
})

const filteredItems = computed(() => {
  const term = (searchTerm.value || '').toLowerCase()
  return (items.value || []).filter(r => !term || (r.name || '').toLowerCase().includes(term))
})

const pagedItems = computed(() => {
  const start = (currentPage.value - 1) * pageSize.value
  return filteredItems.value.slice(start, start + pageSize.value)
})
const serialStart = computed(() => ((currentPage.value - 1) * pageSize.value) + 1)

watch(filteredItems, () => { currentPage.value = 1 })

const hasNewRows = computed(() => {
  return (items.value || []).some(r => !r.id)
})

const canSave = computed(() => {
  const allItems = items.value || []
  const newRows = allItems.filter(r => !r.id)
  if (newRows.length === 0) return true

  const isNonEmpty = (v) => (v ?? '').toString().trim().length > 0
  const isRowCompletelyEmpty = (row) => (
    !isNonEmpty(row.name) &&
    !isNonEmpty(row.rd_document_number) &&
    !isNonEmpty(row.rd_revision) &&
    !isNonEmpty(row.co_document_number) &&
    !isNonEmpty(row.co_revision)
  )
  const areAllFieldsFilled = (row) => (
    isNonEmpty(row.name) &&
    isNonEmpty(row.rd_document_number) &&
    isNonEmpty(row.rd_revision) &&
    isNonEmpty(row.co_document_number) &&
    isNonEmpty(row.co_revision)
  )

  const anyNewRowHasValues = newRows.some(r => !isRowCompletelyEmpty(r))
  if (!anyNewRowHasValues) return false
  return newRows.every(r => areAllFieldsFilled(r))
})

function handleAdd() { addRow() }
async function handleSave() {
  const res = await saveBulk()
  if (res && res.success) {
    ElMessage.success(res.message || 'Document numbers saved')
    await fetchList() // Refresh so new temp rows are replaced with saved rows (with ids)
    return
  }
  ElMessage.error(res?.message || 'Failed to save document numbers')
}
async function confirmDelete(row) {
  try { await ElMessageBox.confirm('Delete this document?', 'Confirm', { type: 'warning' });
    const res = await remove(row); if (res && res.success) ElMessage.success(res.message || 'Deleted')
  } catch(e) { /* cancelled */ }
}

function handleColumnToggle(column) {
  // Column visibility is handled by the checkbox v-model
}

function getColumnLabel(key) {
  const labels = {
    serial: 'Serial Number',
    name: 'Name',
    rd_doc_no: 'RD Doc No.',
    rd_revision: 'RD Revision',
    co_doc_no: 'CO Doc No.',
    co_revision: 'CO Revision',
    actions: 'Actions'
  }
  return labels[key] || key
}

// Export functions
function handlePrint() {
  const data = tableRef.value?.getFilteredData() || filteredItems.value
  const columns = [
    { key: 'name', label: 'Name' },
    { key: 'rd_document_number', label: 'RD Doc No.' },
    { key: 'rd_revision', label: 'RD Revision' },
    { key: 'co_document_number', label: 'CO Doc No.' },
    { key: 'co_revision', label: 'CO Revision' }
  ]
  exportPrint({ title: 'Document Number Setup', data, columns, columnVisibility: columnVisibility.value })
}

function handleExportExcel() {
  const data = tableRef.value?.getFilteredData() || filteredItems.value
  const columns = [
    { key: 'name', label: 'Name' },
    { key: 'rd_document_number', label: 'RD Doc No.' },
    { key: 'rd_revision', label: 'RD Revision' },
    { key: 'co_document_number', label: 'CO Doc No.' },
    { key: 'co_revision', label: 'CO Revision' }
  ]
  exportExcel({ title: 'Document Number Setup', data, columns, columnVisibility: columnVisibility.value })
}

function handleExportPDF() {
  const data = tableRef.value?.getFilteredData() || filteredItems.value
  const columns = [
    { key: 'name', label: 'Name' },
    { key: 'rd_document_number', label: 'RD Doc No.' },
    { key: 'rd_revision', label: 'RD Revision' },
    { key: 'co_document_number', label: 'CO Doc No.' },
    { key: 'co_revision', label: 'CO Revision' }
  ]
  exportPDF({ title: 'Document Number Setup', data, columns, columnVisibility: columnVisibility.value })
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

