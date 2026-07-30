<template>
  <MainLayout>
    <template #header>
      <div class="title">Competencies Setup</div>
    </template>

    <!-- Filters Section -->
    <CompetenciesFilters
      :show-save="showSave"
      :saving="saving"
      @search="handleSearch"
      @status-filter="handleStatusFilter"
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

    <!-- Table Section -->
    <CompetenciesTable
      ref="tableRef"
      :items="filteredItems"
      :loading="loading"
      :visible="columnVisibility"
      @edit="handleEdit"
      @delete="handleDelete"
    />

    <!-- Modal -->
    <CompetenciesModal
      v-model="showModal"
      :form-data="formData"
      :saving="saving"
      :is-edit="isEdit"
      @submit="handleSubmit"
      @close="handleCloseModal"
      @add-subcompetency="handleAddSubcompetency"
      @remove-subcompetency="handleRemoveSubcompetency"
    />
  </MainLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Printer, Download, Document, Setting, ArrowDown } from '@element-plus/icons-vue'
import MainLayout from '../../../Layout/MainLayout.vue'
import CompetenciesFilters from '../../../components/HR_Setup/Competencies_Setup/CompetenciesFilters.vue'
import CompetenciesTable from '../../../components/HR_Setup/Competencies_Setup/CompetenciesTable.vue'
import CompetenciesModal from '../../../components/HR_Setup/Competencies_Setup/CompetenciesModal.vue'
import { useCompetencies } from '../../../composables/useCompetencies.js'
import { useExport } from '../../../composables/useExport.js'

const {
  items,
  loading,
  saving,
  formData,
  fetchList,
  addCompetency,
  updateCompetency,
  deleteCompetency,
  getCompetencyForEdit,
  getFormData,
  addSubcompetencyRow,
  removeSubcompetencyRow,
  deleteSubcompetency,
  resetForm,
  setFormData
} = useCompetencies()

// Local state
const searchTerm = ref('')
const statusFilter = ref('')
const showModal = ref(false)
const showSave = ref(false)
const isEdit = ref(false)
const editingId = ref(null)
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

// Computed properties
const filteredItems = computed(() => {
  let filtered = items.value

  // Apply search filter
  if (searchTerm.value) {
    const search = searchTerm.value.toLowerCase()
    filtered = filtered.filter(item =>
      item.name.toLowerCase().includes(search)
    )
  }

  // Apply status filter
  if (statusFilter.value) {
    if (statusFilter.value === 'active') {
      filtered = filtered.filter(item => item.active)
    } else if (statusFilter.value === 'inactive') {
      filtered = filtered.filter(item => !item.active)
    }
  }

  return filtered
})

// Methods
function handleSearch(term) {
  searchTerm.value = term
}

function handleStatusFilter(status) {
  statusFilter.value = status
}

async function handleAdd() {
  try {
    await getFormData()
    resetForm()
    isEdit.value = false
    editingId.value = null
    showModal.value = true
  } catch (error) {
    console.error('Error loading form data:', error)
    ElMessage.error('Failed to load form data')
  }
}

async function handleEdit(row) {
  try {
    await getCompetencyForEdit(row.id)
    isEdit.value = true
    editingId.value = row.id
    showModal.value = true
  } catch (error) {
    console.error('Error loading competency for edit:', error)
    ElMessage.error('Failed to load competency data')
  }
}

async function handleSubmit(data) {
  try {
    let response
    if (isEdit.value) {
      response = await updateCompetency(editingId.value, data)
    } else {
      response = await addCompetency(data)
    }

    if (response && response.success) {
      ElMessage.success(response.message || 'Competency saved successfully')
      showModal.value = false
      resetForm()
    }
  } catch (error) {
    console.error('Error saving competency:', error)
    ElMessage.error('Failed to save competency')
  }
}

async function handleDelete(row) {
  try {
    await ElMessageBox.confirm(
      `Are you sure you want to delete "${row.name}"?`,
      'Confirm Delete',
      {
        confirmButtonText: 'Delete',
        cancelButtonText: 'Cancel',
        type: 'warning'
      }
    )

    // Note: The backend expects type_id and id for deletion
    // type_id = 2 for main competency deletion
    const response = await deleteCompetency(2, row.id)
    if (response && response.success) {
      ElMessage.success(response.message || 'Competency deleted successfully')
    }
  } catch (error) {
    if (error !== 'cancel') {
      console.error('Error deleting competency:', error)
      ElMessage.error('Failed to delete competency')
    }
  }
}

function handleCloseModal() {
  showModal.value = false
  resetForm()
  isEdit.value = false
  editingId.value = null
}

function handleSave() {
  // This would be used for bulk operations if needed
  ElMessage.info('Save functionality not implemented yet')
}

function handleColumnToggle(column) {
  // Column visibility is handled by the checkbox v-model
}

function getColumnLabel(key) {
  const labels = {
    serial: 'Serial Number',
    name: 'Name',
    active: 'Status',
    actions: 'Actions'
  }
  return labels[key] || key
}

// Export functions
function handlePrint() {
  const data = tableRef.value?.getFilteredData() || filteredItems.value
  const columns = [
    { key: 'name', label: 'Name' },
    { key: 'active', label: 'Status', formatter: (row) => row.active ? 'Active' : 'Inactive' }
  ]
  exportPrint({ title: 'Competencies Setup', data, columns, columnVisibility: columnVisibility.value })
}

function handleExportExcel() {
  const data = tableRef.value?.getFilteredData() || filteredItems.value
  const columns = [
    { key: 'name', label: 'Name' },
    { key: 'active', label: 'Status', formatter: (row) => row.active ? 'Active' : 'Inactive' }
  ]
  exportExcel({ title: 'Competencies Setup', data, columns, columnVisibility: columnVisibility.value })
}

function handleExportPDF() {
  const data = tableRef.value?.getFilteredData() || filteredItems.value
  const columns = [
    { key: 'name', label: 'Name' },
    { key: 'active', label: 'Status', formatter: (row) => row.active ? 'Active' : 'Inactive' }
  ]
  exportPDF({ title: 'Competencies Setup', data, columns, columnVisibility: columnVisibility.value })
}

function handleAddSubcompetency() {
  addSubcompetencyRow()
}

async function handleRemoveSubcompetency(index) {
  const subcompetency = formData.subcompetencies[index]
  
  // If it's an existing subcompetency (has an ID), delete it from database
  if (subcompetency && subcompetency.id) {
    try {
      await ElMessageBox.confirm(
        `Are you sure you want to delete "${subcompetency.name}"?`,
        'Confirm Delete',
        {
          confirmButtonText: 'Delete',
          cancelButtonText: 'Cancel',
          type: 'warning'
        }
      )

      const response = await deleteSubcompetency(subcompetency.id)
      if (response && response.success) {
        ElMessage.success('Subcompetency deleted successfully')
        // Remove from form after successful deletion
        removeSubcompetencyRow(index)
      }
    } catch (error) {
      if (error !== 'cancel') {
        console.error('Error deleting subcompetency:', error)
        ElMessage.error('Failed to delete subcompetency')
      }
    }
  } else {
    // If it's a new subcompetency (no ID), just remove from form
    removeSubcompetencyRow(index)
  }
}

// Lifecycle
onMounted(() => {
  fetchList()
})
</script>

<style scoped>
.title {
  font-weight: 600;
  font-size: 24px;
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