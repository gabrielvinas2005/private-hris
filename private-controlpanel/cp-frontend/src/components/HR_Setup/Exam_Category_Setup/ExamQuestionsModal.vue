<template>
  <el-dialog
    v-model="visible"
    width="900px"
    :title="`Questionnaire - ${category?.name || 'Exam Category'}`"
    :before-close="handleClose"
    destroy-on-close
  >
    <div class="questions-modal">
      <div class="header-row">
        <el-select
          v-model="localSubcategoryId"
          placeholder="Select subcategory"
          clearable
          filterable
          style="min-width: 260px"
          @change="onSelectSubcategory"
        >
          <el-option
            v-for="sub in subcategories"
            :key="sub.id"
            :label="sub.sub_category || sub.name"
            :value="sub.id"
          />
        </el-select>
        <div class="header-actions">
          <div v-if="localSubcategoryId && selectedSubcategoryLimit !== null" class="limit-indicator">
            {{ questionsCount }} / {{ selectedSubcategoryLimit }} questions
            <span v-if="limitReached" class="limit-reached-text">(limit reached)</span>
          </div>
          <el-button
            type="primary"
            :icon="Plus"
            @click="openQuestionForm"
            :disabled="!localSubcategoryId || limitReached"
          >
          Add Question
          </el-button>
        </div>
      </div>

      <el-empty v-if="!subcategories.length" description="No subcategories available. Please add one first." />

      <div v-else>
        <el-table :data="questions" v-loading="loading" border stripe height="250" class="questions-table">
          <el-table-column prop="question_code" label="Code" width="140" />
          <el-table-column prop="question" label="Question" min-width="200" show-overflow-tooltip />
          <el-table-column prop="correct_answer" label="Correct Answer">
            <template #default="{ row }">
              <el-tag type="success">{{ row.correct_answer || 'Not set' }}</el-tag>
            </template>
          </el-table-column>
          <el-table-column label="Actions" width="120" align="center">
            <template #default="{ row }">
              <el-button type="danger" size="small" plain :icon="Delete" @click="onDeleteQuestion(row.id)">
                Delete
              </el-button>
            </template>
          </el-table-column>
        </el-table>

        <el-collapse v-model="questionFormVisible" class="question-form">
          <el-collapse-item name="form">
            <template #title>
              <span class="collapse-title">{{ questionForm.question_id ? 'Edit Question' : 'Add New Question' }}</span>
            </template>
            <el-form :model="questionForm" label-position="top" ref="questionFormRef">
              <el-row :gutter="20">
                <el-col :span="8">
                  <el-form-item label="Question Code" required>
                    <el-input v-model="questionForm.question_code" placeholder="Enter question code" />
                  </el-form-item>
                </el-col>
                <el-col :span="16">
                  <el-form-item label="Question" required>
                    <el-input
                      type="textarea"
                      v-model="questionForm.question"
                      placeholder="Enter question text"
                      :rows="3"
                    />
                  </el-form-item>
                </el-col>
              </el-row>
            <el-form-item v-if="!isEssaySubcategory" label="Choices" required>
              <div class="choices-list">
                <div
                  v-for="(choice, index) in questionForm.choices"
                  :key="choice.id"
                  class="choice-row"
                >
                  <el-radio v-model="questionForm.correct_choice_id" :label="choice.id" class="choice-radio">
                    Correct
                  </el-radio>
                  <el-input
                    v-model="choice.text"
                    :placeholder="`Choice ${index + 1}`"
                    class="choice-input"
                  />
                  <el-button
                    v-if="questionForm.choices.length > 2"
                    type="danger"
                    size="small"
                    circle
                    plain
                    :icon="Close"
                    @click="removeChoice(index)"
                  />
                </div>
                <el-button type="default" size="small" plain @click="addChoice" :icon="Plus">
                  Add Choice
                </el-button>
              </div>
            </el-form-item>
              <div class="form-actions">
                <el-button @click="resetQuestionForm">Cancel</el-button>
                <el-button type="primary" @click="submitQuestion">Save Question</el-button>
              </div>
            </el-form>
          </el-collapse-item>
        </el-collapse>
      </div>
    </div>
  </el-dialog>
</template>

<script setup>
import { ref, reactive, watch, computed } from 'vue'
import { Plus, Delete, Close } from '@element-plus/icons-vue'
import { ElMessage } from 'element-plus'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  category: { type: Object, default: null },
  subcategories: { type: Array, default: () => [] },
  selectedSubcategoryId: { type: [Number, String, null], default: null },
  questions: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  resetKey: { type: Number, default: 0 }
})

const emit = defineEmits(['update:modelValue', 'close', 'select-subcategory', 'save-question', 'delete-question'])

const visible = ref(false)
const localSubcategoryId = ref(null)
const questionFormVisible = ref([])
const questionFormRef = ref(null)

const questionForm = reactive({
  question_id: 0,
  question_code: '',
  question: '',
  choices: [],
  correct_choice_id: null
})

const selectedSubcategory = computed(() => {
  const id = localSubcategoryId.value
  if (!id) return null
  return (props.subcategories || []).find(s => String(s.id) === String(id)) || null
})

const isEssaySubcategory = computed(() => !!selectedSubcategory.value?.is_essay)

const selectedSubcategoryLimit = computed(() => {
  const raw = selectedSubcategory.value?.existing_questions
  if (raw === undefined || raw === null || raw === '') return null
  const n = Number(raw)
  return Number.isFinite(n) ? n : null
})

const questionsCount = computed(() => (props.questions || []).length)
const limitReached = computed(() => {
  if (!localSubcategoryId.value) return false
  if (selectedSubcategoryLimit.value === null) return false
  return questionsCount.value >= selectedSubcategoryLimit.value
})

const uniqueChoiceId = () => `choice_${Date.now()}_${Math.random().toString(36).slice(2, 7)}`

function initializeChoices() {
  questionForm.choices = Array.from({ length: 4 }).map(() => ({
    id: uniqueChoiceId(),
    text: ''
  }))
  questionForm.correct_choice_id = questionForm.choices[0].id
}

function openQuestionForm() {
  if (!localSubcategoryId.value) {
    ElMessage.warning('Please select a subcategory first.')
    return
  }
  if (limitReached.value) {
    ElMessage.warning(`You already reached the limit of ${selectedSubcategoryLimit.value} questions for this subcategory.`)
    return
  }
  resetQuestionForm()
  questionFormVisible.value = ['form']
}

function resetQuestionForm() {
  questionForm.question_id = 0
  questionForm.question_code = `Q-${Date.now()}`
  questionForm.question = ''
  initializeChoices()
  questionFormVisible.value = []
}

function addChoice() {
  questionForm.choices.push({ id: uniqueChoiceId(), text: '' })
}

function removeChoice(index) {
  const removed = questionForm.choices.splice(index, 1)
  if (removed.length && removed[0].id === questionForm.correct_choice_id) {
    questionForm.correct_choice_id = questionForm.choices[0]?.id || null
  }
}

function submitQuestion() {
  // Enforce limit on create (updates allowed)
  if (questionForm.question_id === 0 && limitReached.value) {
    ElMessage.warning(`You can only create up to ${selectedSubcategoryLimit.value} questions for this subcategory.`)
    return
  }
  if (isEssaySubcategory.value) {
    // Essay questions: no choices required
    if (!questionForm.question.trim()) {
      ElMessage.error('Question text is required.')
      return
    }
    emit('save-question', {
      question_id: questionForm.question_id,
      question_code: questionForm.question_code,
      question: questionForm.question,
      choices: [],
      correct_choice_id: null
    })
    return
  }
  if (!questionForm.question.trim()) {
    ElMessage.error('Question text is required.')
    return
  }
  if (questionForm.choices.some(choice => !choice.text.trim())) {
    ElMessage.error('All choices must have text.')
    return
  }
  if (!questionForm.correct_choice_id) {
    ElMessage.error('Please select a correct answer.')
    return
  }
  emit('save-question', {
    ...questionForm,
    choices: questionForm.choices.map(choice => ({
      id: choice.id,
      text: choice.text
    }))
  })
}

function onSelectSubcategory(val) {
  emit('select-subcategory', val)
}

function onDeleteQuestion(questionId) {
  emit('delete-question', questionId)
}

function handleClose() {
  emit('close')
  visible.value = false
}

watch(
  () => props.modelValue,
  (val) => {
    visible.value = val
    if (val) {
      localSubcategoryId.value = props.selectedSubcategoryId || props.subcategories?.[0]?.id || null
      if (!questionForm.choices.length) {
        initializeChoices()
        questionForm.question_code = `Q-${Date.now()}`
      }
    }
  },
  { immediate: true }
)

watch(
  () => props.selectedSubcategoryId,
  (val) => {
    localSubcategoryId.value = val
    if (!val) questionFormVisible.value = []
  }
)

watch(
  () => props.resetKey,
  () => {
    if (visible.value) {
      resetQuestionForm()
    }
  }
)

watch(visible, (val) => {
  emit('update:modelValue', val)
})
</script>

<style scoped>
.questions-modal {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.header-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 12px;
}

.limit-indicator {
  font-size: 12px;
  color: #606266;
  white-space: nowrap;
}

.limit-reached-text {
  color: #f56c6c;
  margin-left: 6px;
}

.questions-table {
  margin-top: 8px;
}

.question-form {
  margin-top: 16px;
}

.choices-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.choice-row {
  display: flex;
  align-items: center;
  gap: 8px;
}

.choice-input {
  flex: 1;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 12px;
}

.collapse-title {
  font-weight: 600;
  color: #303133;
}
</style>

