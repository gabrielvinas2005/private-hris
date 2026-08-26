<template>
  <MainLayout>
    <template #header>
      <div class="title">Exam Category Setup</div>
    </template>

    <!-- Filters Section -->
    <ExamCategoryFilters
      :categories="items"
      :show-save="showSave"
      :saving="saving"
      @search="handleSearch"
      @category-filter="handleCategoryFilter"
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
    <ExamCategoryTable
      :items="filteredItems"
      :loading="loading"
      :visible="columnVisibility"
      @edit="handleEdit"
      @view-subcategories="handleViewSubcategories"
      @view-questions="handleViewQuestions"
      @delete="handleDelete"
    />

    <!-- Modal -->
    <ExamCategoryModal
      v-model="showModal"
      :form-data="formData"
      :difficulty-levels="difficultyLevels"
      :saving="saving"
      :is-edit="isEdit"
      @submit="handleSubmit"
      @close="handleCloseModal"
      @add-subcategory="handleAddSubcategory"
      @remove-subcategory="handleRemoveSubcategory"
    />

    <ExamQuestionsModal
      v-model="showQuestionsModal"
      :category="questionModalState.category"
      :subcategories="questionModalState.subcategories"
      :selected-subcategory-id="questionModalState.selectedSubcategoryId"
      :questions="questionModalState.questions"
      :loading="questionModalState.loading"
      :reset-key="questionFormResetKey"
      @select-subcategory="handleSelectQuestionSubcategory"
      @save-question="handleSaveQuestion"
      @delete-question="handleDeleteQuestion"
      @close="handleCloseQuestionsModal"
    />

    <ExamSubcategoriesModal
      v-model="showSubcategoriesModal"
      :category="subcategoriesModalState.category"
      :subcategories="subcategoriesModalState.subcategories"
      :loading="subcategoriesModalState.loading"
      @close="handleCloseSubcategoriesModal"
    />
  </MainLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Printer, Download, Document, Setting, ArrowDown } from '@element-plus/icons-vue'
import MainLayout from '../../../Layout/MainLayout.vue'
import ExamCategoryFilters from '../../../components/HR_Setup/Exam_Category_Setup/ExamCategoryFilters.vue'
import ExamCategoryTable from '../../../components/HR_Setup/Exam_Category_Setup/ExamCategoryTable.vue'
import ExamCategoryModal from '../../../components/HR_Setup/Exam_Category_Setup/ExamCategoryModal.vue'
import ExamQuestionsModal from '../../../components/HR_Setup/Exam_Category_Setup/ExamQuestionsModal.vue'
import ExamSubcategoriesModal from '../../../components/HR_Setup/Exam_Category_Setup/ExamSubcategoriesModal.vue'
import { useExamCategory } from '../../../composables/useExamCategory.js'
import { useExport } from '../../../composables/useExport.js'

const {
  items,
  loading,
  saving,
  formData,
  fetchList,
  getFormData,
  saveExamCategory,
  deleteExamCategory,
  addSubcategory,
  removeSubcategory,
  deleteSubcategory,
  getSubCategoryPositions,
  addPositions,
  getSubCategoryQuestions,
  addQuestions,
  deleteQuestion,
  deleteChoice,
  getQuestionForEdit,
  fetchCategoryDetails,
  resetForm,
  setFormData
} = useExamCategory()

// Local state
const searchTerm = ref('')
const categoryFilter = ref('')
const showModal = ref(false)
const showSubcategoriesModal = ref(false)
const showQuestionsModal = ref(false)
const showSave = ref(false)
const isEdit = ref(false)
const editingId = ref(null)
const difficultyLevels = ref([])
const tableRef = ref(null)
const subcategoriesModalState = reactive({
  category: null,
  subcategories: [],
  loading: false
})
const questionFormResetKey = ref(0)
const questionModalState = reactive({
  category: null,
  subcategories: [],
  selectedSubcategoryId: null,
  questions: [],
  loading: false
})

// Export composable
const { exportPrint, exportExcel, exportPDF } = useExport()

// Column visibility
const columnVisibility = ref({
  serial: true,
  code: true,
  name: true,
  description: true,
  actions: true
})

// Computed properties
const filteredItems = computed(() => {
  let filtered = items.value

  // Apply search filter
  if (searchTerm.value) {
    const search = searchTerm.value.toLowerCase()
    filtered = filtered.filter(item =>
      item.name.toLowerCase().includes(search) ||
      item.description.toLowerCase().includes(search) ||
      item.category_code.toLowerCase().includes(search)
    )
  }

  // Apply category filter
  if (categoryFilter.value) {
    filtered = filtered.filter(item => item.id === categoryFilter.value)
  }

  return filtered
})

// Methods
function handleSearch(term) {
  searchTerm.value = term
}

function handleCategoryFilter(categoryId) {
  categoryFilter.value = categoryId
}

function formatDifficultyLevels(levels = []) {
  return (levels || []).map(level => {
    const normalizedId = Number(level.id) || level.id
    return {
      ...level,
      id: normalizedId,
      display_label: level.name || level.difficulty_level || `Level ${normalizedId}`
    }
  })
}

async function handleAdd() {
  try {
    const formData = await getFormData(0)
    difficultyLevels.value = formatDifficultyLevels(formData.exam_diff_levels || [])
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
    const formData = await getFormData(row.id)
    difficultyLevels.value = formatDifficultyLevels(formData.exam_diff_levels || [])
    isEdit.value = true
    editingId.value = row.id
    showModal.value = true
  } catch (error) {
    console.error('Error loading exam category for edit:', error)
    ElMessage.error('Failed to load exam category data')
  }
}

async function handleDelete(row) {
  try {
    await ElMessageBox.confirm(
      `Are you sure you want to delete "${row.name}"? This will also remove linked examination setups, subcategories, and questions.`,
      'Delete Exam Category',
      {
        confirmButtonText: 'Delete',
        cancelButtonText: 'Cancel',
        type: 'warning'
      }
    )

    const response = await deleteExamCategory(row.id)
    if (response && response.success) {
      ElMessage.success(response.message || 'Exam category deleted successfully')
    }
  } catch (error) {
    if (error !== 'cancel') {
      console.error('Error deleting exam category:', error)
      ElMessage.error(error?.message || 'Failed to delete exam category')
    }
  }
}

async function handleSubmit(data) {
  try {
    let response
    if (isEdit.value && editingId.value) {
      response = await saveExamCategory(editingId.value, data)
    } else {
      response = await saveExamCategory(0, data)
    }

    if (response && response.success) {
      ElMessage.success(response.message || 'Exam category saved successfully')
      showModal.value = false
      resetForm()
    }
  } catch (error) {
    console.error('Error saving exam category:', error)
    ElMessage.error('Failed to save exam category')
  }
}

function mapDifficultyLabels(levels = []) {
  return levels.reduce((acc, level) => {
    if (level && level.id !== undefined) {
      acc[level.id] = level.difficulty_level || level.name || `Level ${level.id}`
    }
    return acc
  }, {})
}

function normalizeSubcategories(list = [], difficultyMap = {}) {
  return list.map(sub => ({
    id: Number(sub.id) || 0,
    sub_category_code: sub.sub_category_code || '',
    sub_category: sub.sub_category || sub.name || '',
    difficulty_level: Number(sub.difficulty_level) || 1,
    difficulty_label: difficultyMap[Number(sub.difficulty_level)] || `Level ${sub.difficulty_level || 1}`,
    existing_questions: Number(sub.existing_questions) || 0,
    is_essay: sub.is_essay === true || sub.is_essay === 1 || sub.is_essay === '1'
  }))
}

async function handleViewSubcategories(row) {
  try {
    subcategoriesModalState.loading = true
    const details = await fetchCategoryDetails(row.id)
    const difficultyMap = mapDifficultyLabels(details?.exam_diff_levels || [])
    subcategoriesModalState.category = row
    subcategoriesModalState.subcategories = normalizeSubcategories(details?.sub_categories || [], difficultyMap)
    showSubcategoriesModal.value = true
  } catch (error) {
    console.error('Error loading subcategories:', error)
    ElMessage.error('Failed to load subcategories for this category')
  } finally {
    subcategoriesModalState.loading = false
  }
}

async function handleViewQuestions(row) {
  try {
    const details = await fetchCategoryDetails(row.id)
    const difficultyMap = mapDifficultyLabels(details?.exam_diff_levels || [])
    const subcategories = normalizeSubcategories(details?.sub_categories || [], difficultyMap)
    if (!subcategories.length) {
      ElMessage.warning('Add at least one subcategory before managing questions.')
      return
    }
    questionModalState.category = row
    questionModalState.subcategories = subcategories
    questionModalState.selectedSubcategoryId = subcategories[0]?.id || null
    showQuestionsModal.value = true
    if (questionModalState.selectedSubcategoryId) {
      await loadSubcategoryQuestions(questionModalState.selectedSubcategoryId)
    }
  } catch (error) {
    console.error('Error loading questions:', error)
    ElMessage.error('Failed to load questions for this category')
  }
}

async function loadSubcategoryQuestions(subcategoryId) {
  try {
    questionModalState.loading = true
    const details = await getSubCategoryQuestions(subcategoryId)
    questionModalState.questions = (details?.questions || []).map(item => ({
      id: item.id,
      question_code: item.question_code,
      question: item.question,
      correct_answer: item.correct_answer
    }))
  } catch (error) {
    console.error('Error fetching subcategory questions:', error)
    ElMessage.error('Failed to fetch questions for this subcategory')
  } finally {
    questionModalState.loading = false
  }
}

async function handleSelectQuestionSubcategory(subcategoryId) {
  questionModalState.selectedSubcategoryId = subcategoryId
  if (subcategoryId) {
    await loadSubcategoryQuestions(subcategoryId)
  } else {
    questionModalState.questions = []
  }
}

async function handleSaveQuestion(payload) {
  try {
    if (!questionModalState.selectedSubcategoryId) {
      ElMessage.warning('Select a subcategory before saving a question.')
      return
    }

    const selectedSub = (questionModalState.subcategories || []).find(
      s => String(s.id) === String(questionModalState.selectedSubcategoryId)
    )
    const isEssay = !!(selectedSub && (selectedSub.is_essay === true || selectedSub.is_essay === 1 || selectedSub.is_essay === '1'))

    const formDataPayload = new FormData()
    formDataPayload.append('question_code', payload.question_code)
    formDataPayload.append('question', payload.question)

    if (!isEssay) {
      payload.choices.forEach(choice => {
        formDataPayload.append('choice_details[]', choice.text)
        formDataPayload.append('choice_id[]', choice.id || 0)
      })

      formDataPayload.append('is_correct_answer[]', payload.correct_choice_id)
    }

    const response = await addQuestions(
      questionModalState.selectedSubcategoryId,
      payload.question_id || 0,
      formDataPayload
    )

    if (response && response.success) {
      ElMessage.success(response.message || 'Question saved successfully')
      questionFormResetKey.value += 1
      await loadSubcategoryQuestions(questionModalState.selectedSubcategoryId)
    }
  } catch (error) {
    console.error('Error saving question:', error)
    ElMessage.error('Failed to save question')
  }
}

async function handleDeleteQuestion(questionId) {
  try {
    await ElMessageBox.confirm(
      'Are you sure you want to delete this question? This action cannot be undone.',
      'Delete Question',
      { type: 'warning' }
    )
    const response = await deleteQuestion(questionId)
    if (response && response.success) {
      ElMessage.success('Question deleted successfully')
      if (questionModalState.selectedSubcategoryId) {
        await loadSubcategoryQuestions(questionModalState.selectedSubcategoryId)
      }
    }
  } catch (error) {
    if (error !== 'cancel') {
      console.error('Error deleting question:', error)
      ElMessage.error('Failed to delete question')
    }
  }
}

function handleCloseQuestionsModal() {
  showQuestionsModal.value = false
  questionModalState.category = null
  questionModalState.subcategories = []
  questionModalState.selectedSubcategoryId = null
  questionModalState.questions = []
}

function handleCloseSubcategoriesModal() {
  showSubcategoriesModal.value = false
  subcategoriesModalState.category = null
  subcategoriesModalState.subcategories = []
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
    code: 'Category Code',
    name: 'Category Name',
    description: 'Description',
    actions: 'Actions'
  }
  return labels[key] || key
}

// Export functions
function handlePrint() {
  const data = tableRef.value?.getFilteredData() || filteredItems.value
  const columns = [
    { key: 'category_code', label: 'Category Code' },
    { key: 'name', label: 'Category Name' },
    { key: 'description', label: 'Description', formatter: (row) => row.description || 'No description' }
  ]
  exportPrint({ title: 'Exam Category Setup', data, columns, columnVisibility: columnVisibility.value })
}

function handleExportExcel() {
  const data = tableRef.value?.getFilteredData() || filteredItems.value
  const columns = [
    { key: 'category_code', label: 'Category Code' },
    { key: 'name', label: 'Category Name' },
    { key: 'description', label: 'Description', formatter: (row) => row.description || 'No description' }
  ]
  exportExcel({ title: 'Exam Category Setup', data, columns, columnVisibility: columnVisibility.value })
}

function handleExportPDF() {
  const data = tableRef.value?.getFilteredData() || filteredItems.value
  const columns = [
    { key: 'category_code', label: 'Category Code' },
    { key: 'name', label: 'Category Name' },
    { key: 'description', label: 'Description', formatter: (row) => row.description || 'No description' }
  ]
  exportPDF({ title: 'Exam Category Setup', data, columns, columnVisibility: columnVisibility.value })
}

function handleAddSubcategory() {
  addSubcategory()
}

function handleRemoveSubcategory(index) {
  removeSubcategory(index)
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