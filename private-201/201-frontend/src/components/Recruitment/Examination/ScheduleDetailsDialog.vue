<template>
  <el-dialog :model-value="visible" title="Schedule Details" width="1000px" @update:model-value="$emit('update:visible', $event)">
    <el-card shadow="never">
      <template #header>
        <span class="font-bold">Examination Schedule Information</span>
      </template>
      <el-form :model="scheduleForm" label-width="160px">
        <el-form-item label="Online Exam Set">
          <el-select v-model="scheduleForm.exam_id" placeholder="Select Exam" style="width: 100%">
            <el-option v-for="e in exams" :key="e.id" :label="e.exam_set" :value="e.id" />
          </el-select>
        </el-form-item>
        <el-row :gutter="12">
          <el-col :span="12">
            <el-form-item label="Exam Start Date">
              <el-date-picker v-model="scheduleForm.exam_date_from" type="date" placeholder="Start Date" style="width:100%" value-format="YYYY-MM-DD" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="Exam End Date">
              <el-date-picker v-model="scheduleForm.exam_date_to" type="date" placeholder="End Date" style="width:100%" value-format="YYYY-MM-DD" />
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="12">
          <el-col :span="12">
            <el-form-item label="Exam Time From">
              <el-time-picker v-model="scheduleForm.exam_time_from" placeholder="Time From" style="width:100%" format="HH:mm" value-format="HH:mm:ss" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="Exam Time To">
              <el-time-picker v-model="scheduleForm.exam_time_to" placeholder="Time To" style="width:100%" format="HH:mm" value-format="HH:mm:ss" />
            </el-form-item>
          </el-col>
        </el-row>
        <el-form-item>
          <el-button type="primary" :loading="saving" @click="$emit('save', scheduleForm)">Save</el-button>
          <el-button type="danger" :disabled="!scheduleForm.id" @click="$emit('delete', schedule)">Delete Schedule</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <el-card class="mt-4" shadow="never">
      <template #header>
        <span class="font-bold">Exam Details</span>
      </template>
      <el-table :data="examDetails" size="small" stripe>
        <el-table-column prop="category" label="Category" />
        <el-table-column prop="sub_category" label="Subcategory" />
        <el-table-column prop="difficulty_level" label="Difficulty Level" width="160" />
        <el-table-column prop="existing_questions" label="Existing Questions" width="180" />
        <el-table-column prop="position" label="Position" />
      </el-table>
    </el-card>

    <el-card class="mt-4" shadow="never">
      <template #header>
        <div class="flex justify-between items-center">
          <span class="font-bold">Examinees</span>
          <el-button type="primary" size="small" @click="$emit('tag')">Tag Applicant</el-button>
        </div>
      </template>
      <el-table :data="examinees" size="small" stripe>
        <el-table-column prop="applicant_no" label="Applicant ID" width="160" />
        <el-table-column prop="name" label="Name" />
        <el-table-column prop="position" label="Position Applied" />
        <el-table-column label="Status" width="160">
          <template #default="{ row }">
            <template v-if="isPsychSchedule && psychResultLabel(row)">
              <el-tag :type="psychResultLabel(row) === 'passed' ? 'success' : 'danger'" size="small">
                {{ psychResultLabel(row) === 'passed' ? 'Passed' : 'Failed' }}
              </el-tag>
            </template>
            <template v-else-if="isComplete(row)">
              <el-tag v-if="Number(row.essay_reviewed) === 1" type="success" size="small">Submitted</el-tag>
              <el-tag v-else type="warning" size="small">Needs Essay Review</el-tag>
            </template>
            <el-tag v-else type="info" size="small">Not taken</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="Actions" :width="isPsychSchedule ? 280 : 220">
          <template #default="{ row }">
            <div class="action-icons">
              <span
                v-if="isPsychSchedule"
                class="action-icon pass"
                :class="{ disabled: psychTaggingId === row.id }"
                role="button"
                tabindex="0"
                @click="psychTaggingId !== row.id && tagPsychResult(row, 'passed')"
              >
                <el-icon><EditPen /></el-icon>
                <span>Pass</span>
              </span>
              <span
                v-if="isPsychSchedule"
                class="action-icon fail"
                :class="{ disabled: psychTaggingId === row.id }"
                role="button"
                tabindex="0"
                @click="psychTaggingId !== row.id && tagPsychResult(row, 'failed')"
              >
                <el-icon><EditPen /></el-icon>
                <span>Fail</span>
              </span>
              <span
                v-if="!isPsychSchedule && isComplete(row)"
                class="action-icon view"
                role="button"
                tabindex="0"
                @click="openAnswers(row)"
              >
                <el-icon><View /></el-icon>
                <span>View Answers</span>
              </span>
              <span
                v-if="!isPsychSchedule && isComplete(row) && Number(row.essay_reviewed) !== 1"
                class="action-icon review"
                role="button"
                tabindex="0"
                @click="openEssayReview(row)"
              >
                <el-icon><EditPen /></el-icon>
                <span>Review Essays</span>
              </span>
              <span
                class="action-icon remove"
                role="button"
                tabindex="0"
                @click="$emit('remove', row)"
              >
                <el-icon><Delete /></el-icon>
                <span>Remove</span>
              </span>
            </div>
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <!-- View Applicant Answers Dialog -->
    <el-dialog
      v-model="showAnswersDialog"
      :title="answersTitle"
      width="900px"
      destroy-on-close
      @closed="answersData = []; selectedExamineeForAnswers = null"
    >
      <div v-loading="answersLoading" class="min-h-200">
        <el-table v-if="answersData.length" :data="answersData" size="small" stripe max-height="400">
          <el-table-column type="index" label="#" width="50" />
          <el-table-column prop="question_text" label="Question" min-width="200" show-overflow-tooltip />
          <el-table-column label="Applicant Answer" min-width="220" show-overflow-tooltip>
            <template #default="{ row }">
              <span v-if="row.answer_text && String(row.answer_text).trim().length">
                {{ row.answer_text }}
              </span>
              <span v-else>
                {{ row.applicant_choice_text }}
              </span>
            </template>
          </el-table-column>
          <el-table-column prop="correct_answer_text" label="Correct Answer" width="180" show-overflow-tooltip />
          <el-table-column label="Essay Review" width="160">
            <template #default="{ row }">
              <span v-if="isEssayRow(row)">
                {{
                  Number.isFinite(Number(essayReviewSelection[String(row.question_id)]))
                    ? `${Number(essayReviewSelection[String(row.question_id)])}%`
                    : '-'
                }}
              </span>
              <span v-else>-</span>
            </template>
          </el-table-column>
          <el-table-column label="Result" width="100">
            <template #default="{ row }">
              <el-tag v-if="isAnswerCorrect(row)" type="success" size="small">Correct</el-tag>
              <el-tag v-else-if="isAnswerUnanswered(row)" type="info" size="small">Unanswered</el-tag>
              <el-tag v-else type="danger" size="small">Wrong</el-tag>
            </template>
          </el-table-column>
        </el-table>
        <div v-if="hasEssayRows" class="mt-4 flex justify-end">
          <el-button type="primary" size="small" :loading="essayReviewSaving" @click="saveEssayReview">
            Save Essay Review
          </el-button>
        </div>
        <p v-else-if="!answersLoading" class="text-gray-500">No answers to display.</p>
      </div>
    </el-dialog>

    <!-- Essay Review Dialog -->
    <el-dialog
      v-model="showEssayReviewDialog"
      title="Essay Review"
      width="800px"
      destroy-on-close
    >
      <div v-loading="essayReviewLoading" class="min-h-200 essay-review-wrap">
        <div v-if="essayOnlyRows.length" class="essay-review-summary">
          <el-tag type="info" effect="plain">Essay items: {{ essayOnlyRows.length }}</el-tag>
          <el-tag type="success" effect="plain">Reviewed: {{ reviewedEssayCount }}</el-tag>
          <el-tag type="warning" effect="plain">Pending: {{ essayOnlyRows.length - reviewedEssayCount }}</el-tag>
        </div>

        <div v-if="essayOnlyRows.length" class="essay-review-list">
          <el-card v-for="(row, idx) in essayOnlyRows" :key="row.question_id || idx" shadow="never" class="essay-card">
            <template #header>
              <div class="essay-card-header">
                <span class="essay-qno">Essay {{ idx + 1 }}</span>
                <el-tag size="small" effect="plain">
                  {{ Number.isFinite(Number(essayReviewSelection[String(row.question_id)])) ? `${essayReviewSelection[String(row.question_id)]}%` : 'pending' }}
                </el-tag>
              </div>
            </template>

            <div class="essay-field">
              <label>Question</label>
              <div class="essay-text">{{ row.question_text || '-' }}</div>
            </div>

            <div class="essay-field">
              <label>Applicant Answer</label>
              <div class="essay-answer">{{ row.answer_text || 'No answer submitted.' }}</div>
            </div>

            <div class="essay-field">
              <label>Grade (0-100)</label>
              <el-input-number
                v-model="essayReviewSelection[String(row.question_id)]"
                :min="0"
                :max="100"
                :precision="0"
                controls-position="right"
                style="width: 180px"
              />
            </div>
          </el-card>
        </div>

        <p v-else-if="!essayReviewLoading" class="text-gray-500">No essay answers to review.</p>
      </div>
      <template #footer>
        <el-button @click="showEssayReviewDialog = false">Close</el-button>
        <el-button
          type="primary"
          :loading="essayReviewSaving"
          :disabled="essayOnlyRows.length > 0 && reviewedEssayCount < essayOnlyRows.length"
          @click="saveEssayReview"
        >
          Save Review
        </el-button>
      </template>
    </el-dialog>
  </el-dialog>
</template>

<script setup>
import { reactive, watch, ref, computed } from 'vue'
import { ElMessage } from 'element-plus'
import { View, EditPen, Delete } from '@element-plus/icons-vue'
import { examinationApi } from '@/services/api'

const props = defineProps({
  visible: { type: Boolean, default: false },
  schedule: { type: Object, default: () => null },
  examDetails: { type: Array, default: () => [] },
  exams: { type: Array, default: () => [] },
  examinees: { type: Array, default: () => [] },
  saving: { type: Boolean, default: false }
})

defineEmits(['update:visible', 'tag', 'save', 'delete', 'remove'])

const scheduleForm = reactive({
  id: null,
  exam_id: null,
  exam_date_from: '',
  exam_date_to: '',
  exam_time_from: '',
  exam_time_to: ''
})

const showAnswersDialog = ref(false)
const selectedExamineeForAnswers = ref(null)
const answersData = ref([])
const answersLoading = ref(false)
const essayReviewSaving = ref(false)
const psychTaggingId = ref(null)
const essayReviewSelection = reactive({})
const showEssayReviewDialog = ref(false)
const essayReviewLoading = ref(false)
const essayOnlyRows = computed(() => (answersData.value || []).filter((r) => isEssayRow(r)))
const reviewedEssayCount = computed(() => {
  return essayOnlyRows.value.filter((row) => {
    const v = Number(essayReviewSelection[String(row.question_id)])
    return Number.isFinite(v)
  }).length
})

const answersTitle = computed(() => {
  const a = selectedExamineeForAnswers.value
  if (!a) return 'Applicant Answers'
  return `Answers: ${a.name || a.applicant_no || 'Applicant'}`
})

const isPsychSchedule = computed(() => {
  const examSet = String(props.schedule?.exam_set || '').toLowerCase()
  const category = String(props.schedule?.exam_category || '').toLowerCase()
  return examSet.includes('psych') || category.includes('psych')
})

const isComplete = (row) => {
  if (!row) return false
  const v = row.is_complete
  return v === true || v === 1 || v === '1'
}

// Backend may return correct/unanswered as number, string, or boolean (e.g. SQL Server BIT)
const isAnswerCorrect = (row) => {
  if (!row) return false
  const c = row.correct
  if (c === 1 || c === true || c === '1') return true
  if (Number(c) === 1) return true
  // Fallback: if applicant answer text matches correct answer text, treat as correct
  const applicant = (row.applicant_choice_text ?? '').toString().trim()
  const correct = (row.correct_answer_text ?? '').toString().trim()
  if (applicant && correct && applicant === correct) return true
  return false
}

const isAnswerUnanswered = (row) => {
  if (!row) return false
  const u = row.unanswered
  if (u === 1 || u === true || u === '1') return true
  if (Number(u) === 1) return true
  return false
}

const isEssayRow = (row) => {
  const sub = String(row?.sub_category || '').toLowerCase()
  return sub.includes('essay')
}

const hasEssayRows = computed(() => (answersData.value || []).some((r) => isEssayRow(r)))

const psychResultLabel = (row) => {
  if (!row || !isComplete(row)) return ''
  const rating = Number(row.exam_rating ?? 0)
  if (!Number.isFinite(rating)) return ''
  return rating >= 50 ? 'passed' : 'failed'
}

const normalizeResult = (row) => {
  const savedEssayScore = readEssayScore(row)
  if (savedEssayScore !== null) return savedEssayScore
  if (isAnswerUnanswered(row)) return null
  return isAnswerCorrect(row) ? 100 : 0
}

const readEssayScore = (row) => {
  if (!row) return null
  const raw = row.essay_score
  if (raw === null || raw === undefined || raw === '') return null
  const n = Number(raw)
  return Number.isFinite(n) ? n : null
}

const openAnswers = async (row) => {
  if (!row || !row.id) return
  selectedExamineeForAnswers.value = row
  showAnswersDialog.value = true
  answersData.value = []
  answersLoading.value = true
  try {
    const { data } = await examinationApi.examineeAnswers(row.id)
    const payload = data?.data || data
    answersData.value = payload?.answers || []
    // initialize review selection from current backend state
    Object.keys(essayReviewSelection).forEach((k) => delete essayReviewSelection[k])
    ;(answersData.value || []).forEach((ans) => {
      if (isEssayRow(ans)) {
        essayReviewSelection[String(ans.question_id)] = normalizeResult(ans)
      }
    })
  } catch (e) {
    console.error('Failed to load exam answers:', e)
    answersData.value = []
  } finally {
    answersLoading.value = false
  }
}

const openEssayReview = async (row) => {
  if (!row || !row.id) return
  selectedExamineeForAnswers.value = row
  showEssayReviewDialog.value = true
  essayReviewLoading.value = true
  try {
    const { data } = await examinationApi.examineeAnswers(row.id)
    const payload = data?.data || data
    answersData.value = payload?.answers || []
    Object.keys(essayReviewSelection).forEach((k) => delete essayReviewSelection[k])
    ;(answersData.value || []).forEach((ans) => {
      if (isEssayRow(ans)) {
        essayReviewSelection[String(ans.question_id)] = normalizeResult(ans)
      }
    })
  } catch (e) {
    console.error('Failed to load exam answers for review:', e)
    answersData.value = []
  } finally {
    essayReviewLoading.value = false
  }
}

const saveEssayReview = async () => {
  const examinee = selectedExamineeForAnswers.value
  if (!examinee?.id) return

  const answers = (answersData.value || [])
    .filter((row) => isEssayRow(row))
    .map((row) => ({
      question_id: Number(row.question_id),
      grade: Number(essayReviewSelection[String(row.question_id)]),
    }))

  if (!answers.length || answers.some((a) => !Number.isFinite(a.grade))) {
    ElMessage.warning('Please provide a grade for all essay answers.')
    return
  }

  essayReviewSaving.value = true
  try {
    await examinationApi.reviewExamineeAnswers(examinee.id, { answers })
    ElMessage.success('Essay review saved.')
    await openAnswers(examinee)
  } catch (e) {
    console.error('Failed to save essay review:', e)
    ElMessage.error('Failed to save essay review.')
  } finally {
    essayReviewSaving.value = false
  }
}

const tagPsychResult = async (row, result) => {
  if (!row?.id || !result) return
  psychTaggingId.value = row.id
  try {
    await examinationApi.tagExamineeResult(row.id, { result })
    row.exam_rating = result === 'passed' ? 100 : 0
    row.is_complete = 1
    ElMessage.success(`Examinee marked as ${result}.`)
  } catch (e) {
    console.error('Failed to tag psych result:', e)
    ElMessage.error('Failed to tag psych result.')
  } finally {
    psychTaggingId.value = null
  }
}

watch(() => props.schedule, (v) => {
  if (v) {
    scheduleForm.id = v.id ?? null
    scheduleForm.exam_id = v.exam_id ?? null
    scheduleForm.exam_date_from = v.exam_date_from || ''
    scheduleForm.exam_date_to = v.exam_date_to || ''
    scheduleForm.exam_time_from = v.exam_time_from || ''
    scheduleForm.exam_time_to = v.exam_time_to || ''
  }
}, { immediate: true })
</script>

<style scoped>
.mt-4 { margin-top: 1rem; }
.font-bold { font-weight: 700; }
.mb-2 { margin-bottom: .5rem; }
.flex { display: flex; }
.justify-between { justify-content: space-between; }
.items-center { align-items: center; }
.min-h-200 { min-height: 200px; }
.text-gray-500 { color: #909399; }
.essay-review-wrap { display: flex; flex-direction: column; gap: 12px; }
.essay-review-summary { display: flex; gap: 8px; flex-wrap: wrap; }
.essay-review-list { display: flex; flex-direction: column; gap: 10px; max-height: 420px; overflow-y: auto; padding-right: 4px; }
.essay-card { border: 1px solid #ebeef5; }
.essay-card-header { display: flex; justify-content: space-between; align-items: center; }
.essay-qno { font-weight: 700; }
.essay-field { display: flex; flex-direction: column; gap: 6px; margin-bottom: 10px; }
.essay-field label { font-size: 12px; color: #606266; font-weight: 600; text-transform: uppercase; letter-spacing: .02em; }
.essay-text { background: #f5f7fa; border: 1px solid #ebeef5; border-radius: 6px; padding: 10px; color: #303133; }
.essay-answer { background: #fcfcfd; border: 1px solid #dcdfe6; border-radius: 6px; padding: 10px; color: #303133; line-height: 1.5; white-space: pre-wrap; }
.action-icons { display: flex; flex-direction: column; gap: 6px; align-items: flex-start; }
.action-icon { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; cursor: pointer; user-select: none; }
.action-icon.view { color: #409eff; }
.action-icon.review { color: #e6a23c; }
.action-icon.pass { color: #67c23a; }
.action-icon.fail { color: #f56c6c; }
.action-icon.remove { color: #f56c6c; }
.action-icon:hover { opacity: 0.8; }
.action-icon.disabled { opacity: 0.5; pointer-events: none; }
</style>


