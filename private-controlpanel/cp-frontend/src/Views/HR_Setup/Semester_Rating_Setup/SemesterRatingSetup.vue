<template>
  <MainLayout>
    <template #header>
      <div class="title">Semester Rating Setup</div>
    </template>

    <!-- Filters Section -->
    <SemesterRatingFilters
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
    <SemesterRatingTable
      ref="tableRef"
      :items="filteredItems"
      :loading="loading"
      :visible="columnVisibility"
      @edit="handleEdit"
      @delete="handleDelete"
    />

    <!-- Modal -->
    <SemesterRatingModal
      v-model="showModal"
      :form-data="formData"
      :saving="saving"
      :is-edit="isEdit"
      @submit="handleSubmit"
      @close="handleCloseModal"
    />
  </MainLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Printer, Download, Document, Setting, ArrowDown } from '@element-plus/icons-vue'
import MainLayout from '../../../Layout/MainLayout.vue'
import SemesterRatingFilters from '../../../components/HR_Setup/Semester_Rating_Setup/SemesterRatingFilters.vue'
import SemesterRatingTable from '../../../components/HR_Setup/Semester_Rating_Setup/SemesterRatingTable.vue'
import SemesterRatingModal from '../../../components/HR_Setup/Semester_Rating_Setup/SemesterRatingModal.vue'
import { useSemesterRating } from '../../../composables/useSemesterRating.js'
import { useExport } from '../../../composables/useExport.js'

const {
  items,
  loading,
  saving,
  formData,
  fetchList,
  addSemesterRating,
  updateSemesterRating,
  deleteSemesterRating,
  getSemesterRatingForEdit,
  resetForm,
  setFormData
} = useSemesterRating()

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

function handleAdd() {
  resetForm()
  isEdit.value = false
  editingId.value = null
  showModal.value = true
}

function handleEdit(row) {
  setFormData(row)
  isEdit.value = true
  editingId.value = row.id
  showModal.value = true
}

async function handleSubmit(data) {
  try {
    let response
    if (isEdit.value) {
      response = await updateSemesterRating(editingId.value, data)
    } else {
      response = await addSemesterRating(data)
    }

    if (response && response.success) {
      ElMessage.success(response.message || 'Semester rating saved successfully')
      showModal.value = false
      resetForm()
    }
  } catch (error) {
    console.error('Error saving semester rating:', error)
    ElMessage.error('Failed to save semester rating')
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

    const response = await deleteSemesterRating(row.id)
    if (response && response.success) {
      ElMessage.success(response.message || 'Semester rating deleted successfully')
    }
  } catch (error) {
    if (error !== 'cancel') {
      console.error('Error deleting semester rating:', error)
      ElMessage.error('Failed to delete semester rating')
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
  exportPrint({ title: 'Semester Rating Setup', data, columns, columnVisibility: columnVisibility.value })
}

function handleExportExcel() {
  const data = tableRef.value?.getFilteredData() || filteredItems.value
  const columns = [
    { key: 'name', label: 'Name' },
    { key: 'active', label: 'Status', formatter: (row) => row.active ? 'Active' : 'Inactive' }
  ]
  exportExcel({ title: 'Semester Rating Setup', data, columns, columnVisibility: columnVisibility.value })
}

function handleExportPDF() {
  const data = tableRef.value?.getFilteredData() || filteredItems.value
  const columns = [
    { key: 'name', label: 'Name' },
    { key: 'active', label: 'Status', formatter: (row) => row.active ? 'Active' : 'Inactive' }
  ]
  exportPDF({ title: 'Semester Rating Setup', data, columns, columnVisibility: columnVisibility.value })
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
