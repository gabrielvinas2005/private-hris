<template>
  <MainLayout>
    <template #header>
      <div class="title">EETE Rating Setup</div>
    </template>

    <!-- Filters Section -->
    <EETEFilters
      :show-save="showSave"
      :saving="saving"
      @search="handleSearch"
      @rating-filter="handleRatingFilter"
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
    <EETETable
      ref="tableRef"
      :items="filteredItems"
      :loading="loading"
      :visible="columnVisibility"
      @edit="handleEdit"
      @delete="handleDelete"
    />

    <!-- Modal -->
    <EETEModal
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
import EETEFilters from '../../../components/HR_Setup/EETE_Rating_Setup/EETEFilters.vue'
import EETETable from '../../../components/HR_Setup/EETE_Rating_Setup/EETETable.vue'
import EETEModal from '../../../components/HR_Setup/EETE_Rating_Setup/EETEModal.vue'
import { useEETERating } from '../../../composables/useEETERating.js'
import { useExport } from '../../../composables/useExport.js'

const {
  items,
  loading,
  saving,
  formData,
  fetchList,
  saveEETERating,
  updateEETERating,
  deleteEETERating,
  getEETEForEdit,
  getFormData,
  calculateTotal,
  calculateAverage,
  resetForm,
  setFormData
} = useEETERating()

// Local state
const searchTerm = ref('')
const ratingFilter = ref('')
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
  education: true,
  experience: true,
  training: true,
  eligibility: true,
  actions: true
})

// Computed properties
const filteredItems = computed(() => {
  let filtered = items.value

  // Apply search filter (search by individual ratings)
  if (searchTerm.value) {
    const search = searchTerm.value.toLowerCase()
    filtered = filtered.filter(item =>
      item.education_rating.toString().includes(search) ||
      item.experience_rating.toString().includes(search) ||
      item.training_rating.toString().includes(search) ||
      item.eligibility_rating.toString().includes(search)
    )
  }

  // Apply rating range filter (based on education rating as primary)
  if (ratingFilter.value) {
    const [min, max] = ratingFilter.value.split('-').map(Number)
    filtered = filtered.filter(item => {
      const education = item.education_rating
      return education >= min && education <= max
    })
  }

  return filtered
})

// Methods
function handleSearch(term) {
  searchTerm.value = term
}

function handleRatingFilter(range) {
  ratingFilter.value = range
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
    await getEETEForEdit(row.id)
    isEdit.value = true
    editingId.value = row.id
    showModal.value = true
  } catch (error) {
    console.error('Error loading EETE rating for edit:', error)
    ElMessage.error('Failed to load EETE rating data')
  }
}

async function handleSubmit(data) {
  try {
    let response
    if (isEdit.value) {
      response = await updateEETERating(editingId.value, data)
    } else {
      response = await saveEETERating(data)
    }

    if (response && response.success) {
      ElMessage.success(response.message || 'EETE rating saved successfully')
      showModal.value = false
      resetForm()
    }
  } catch (error) {
    console.error('Error saving EETE rating:', error)
    ElMessage.error('Failed to save EETE rating')
  }
}

async function handleDelete(row) {
  try {
    await ElMessageBox.confirm(
      `Are you sure you want to delete this EETE rating?`,
      'Confirm Delete',
      {
        confirmButtonText: 'Delete',
        cancelButtonText: 'Cancel',
        type: 'warning'
      }
    )

    const response = await deleteEETERating(row.id)
    if (response && response.success) {
      ElMessage.success(response.message || 'EETE rating deleted successfully')
    }
  } catch (error) {
    if (error !== 'cancel') {
      console.error('Error deleting EETE rating:', error)
      ElMessage.error('Failed to delete EETE rating')
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
    education: 'Education',
    experience: 'Experience',
    training: 'Training',
    eligibility: 'Eligibility',
    actions: 'Actions'
  }
  return labels[key] || key
}

// Export functions
function handlePrint() {
  const data = tableRef.value?.getFilteredData() || filteredItems.value
  const columns = [
    { key: 'education_rating', label: 'Education' },
    { key: 'experience_rating', label: 'Experience' },
    { key: 'training_rating', label: 'Training' },
    { key: 'eligibility_rating', label: 'Eligibility' }
  ]
  exportPrint({ title: 'EETE Rating Setup', data, columns, columnVisibility: columnVisibility.value })
}

function handleExportExcel() {
  const data = tableRef.value?.getFilteredData() || filteredItems.value
  const columns = [
    { key: 'education_rating', label: 'Education' },
    { key: 'experience_rating', label: 'Experience' },
    { key: 'training_rating', label: 'Training' },
    { key: 'eligibility_rating', label: 'Eligibility' }
  ]
  exportExcel({ title: 'EETE Rating Setup', data, columns, columnVisibility: columnVisibility.value })
}

function handleExportPDF() {
  const data = tableRef.value?.getFilteredData() || filteredItems.value
  const columns = [
    { key: 'education_rating', label: 'Education' },
    { key: 'experience_rating', label: 'Experience' },
    { key: 'training_rating', label: 'Training' },
    { key: 'eligibility_rating', label: 'Eligibility' }
  ]
  exportPDF({ title: 'EETE Rating Setup', data, columns, columnVisibility: columnVisibility.value })
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