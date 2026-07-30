<template>
  <el-dialog 
    v-model="visible" 
    :title="`Examination Results - ${applicantData?.name || ''}`"
    width="80%"
    top="5vh"
    destroy-on-close
  >
    <div v-if="loading" class="flex justify-center items-center h-64">
      <el-icon class="is-loading">
        <Loading />
      </el-icon>
    </div>
    
    <div v-else-if="examData" class="exam-container">
      <!-- List of All Exams -->
      <div v-if="examData.exam?.length > 0" class="section">
        <h3 class="section-title">Examinations Taken ({{ examData.exam.length }})</h3>
        
        <el-tabs v-model="activeExamTab" type="border-card">
          <el-tab-pane 
            v-for="(exam, index) in examData.exam" 
            :key="exam.applicant_examination_id"
            :label="getExamTabLabel(exam, index)"
            :name="String(exam.applicant_examination_id)"
          >
            <!-- Exam Overview -->
            <div class="exam-details">
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="field-label">Examination Set:</label>
                  <span class="field-value">{{ exam.exam_set || 'N/A' }}</span>
                </div>
                <div>
                  <label class="field-label">Category:</label>
                  <span class="field-value">{{ exam.category || 'N/A' }}</span>
                </div>
                <div>
                  <label class="field-label">Exam Date:</label>
                  <span class="field-value">{{ formatDateRange(exam.exam_date_from, exam.exam_date_to) }}</span>
                </div>
                <div>
                  <label class="field-label">Exam Time:</label>
                  <span class="field-value">{{ formatTimeRange(exam.exam_time_from, exam.exam_time_to) }}</span>
                </div>
                <div>
                  <label class="field-label">Duration:</label>
                  <span class="field-value">{{ exam.exam_duration || 'N/A' }} minutes</span>
                </div>
                <div>
                  <label class="field-label">Passing Score:</label>
                  <span class="field-value">{{ exam.passing_criteria || 'N/A' }}</span>
                </div>
                <div class="col-span-2">
                  <label class="field-label">Instructions:</label>
                  <div class="field-value">{{ exam.exam_instruction || 'No instructions provided' }}</div>
                </div>
                <!-- <div>
                  <label class="field-label">Overall Rating:</label>
                  <span class="field-value rating" :class="getRatingClass(exam.exam_rating)">
                    {{ exam.exam_rating || 0 }}
                  </span>
                </div> -->
              </div>
            </div>

            <!-- Exam Results by Category for this specific exam -->
            <div v-if="getExamSubCategories(exam.applicant_examination_id)?.length > 0" class="mt-4">
              <h4 class="subsection-title">Detailed Results by Category</h4>
              <el-table :data="getExamSubCategories(exam.applicant_examination_id)" size="small" border>
                <el-table-column prop="sub_category" label="Sub Category" />
                <el-table-column prop="difficulty_level" label="Difficulty Level" />
                <el-table-column prop="total_items" label="Total Items" width="120" align="center" />
                <el-table-column prop="total_correct" label="Correct Answers" width="140" align="center" />
                <el-table-column label="Score" width="100" align="center">
                  <template #default="{ row }">
                    <span :class="getScoreClass(row.total_correct, row.total_items)">
                      {{ row.total_correct }} / {{ row.total_items }} 
                    </span>
                  </template>
                </el-table-column>
              </el-table>
            </div>
            <div v-else class="mt-4">
              <el-empty description="No detailed results available for this exam" :image-size="80" />
            </div>
          </el-tab-pane>
        </el-tabs>
      </div>

      <!-- No Exam Data -->
      <div v-else class="no-data">
        <el-empty description="No examination data available for this applicant" />
      </div>
    </div>

    <template #footer>
      <div class="dialog-footer">
        <el-button @click="visible = false">Close</el-button>
      </div>
    </template>
  </el-dialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { adminSelectApi } from '@/services/api'
import { ElMessage } from 'element-plus'
import { Loading } from '@element-plus/icons-vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  applicantData: { type: Object, default: null }
})

const emit = defineEmits(['update:modelValue'])

const visible = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

const loading = ref(false)
const examData = ref(null)
const activeExamTab = ref('')

// Watch for dialog open and fetch exam data
watch(visible, (newVal) => {
  if (newVal && props.applicantData) {
    fetchExamData()
  }
})

const fetchExamData = async () => {
  if (!props.applicantData?.applicant_id) return
  
  loading.value = true
  try {
    const { data } = await adminSelectApi.exam(props.applicantData.applicant_id)
    examData.value = data.data
    // Set active tab to the first exam (latest)
    if (examData.value?.exam?.length > 0) {
      activeExamTab.value = String(examData.value.exam[0].applicant_examination_id)
    }
  } catch (error) {
    console.error('Error fetching exam data:', error)
    ElMessage.error('Failed to load exam data')
  } finally {
    loading.value = false
  }
}

const getExamSubCategories = (applicantExaminationId) => {
  if (!examData.value?.exam_total_sub_categories) return []
  return examData.value.exam_total_sub_categories.filter(
    item => item.applicant_examination_id === applicantExaminationId
  )
}

const getExamTabLabel = (exam, index) => {
  // Use exam type name if available, otherwise use category
  if (exam.exam_type_name) {
    return `${exam.exam_type_name}`
  } else if (exam.category) {
    return `${exam.category}`
  } else {
    return `Exam ${index + 1}`
  }
}

// Helper functions
const formatDateRange = (dateFrom, dateTo) => {
  if (!dateFrom) return 'N/A'
  if (dateFrom === dateTo) return new Date(dateFrom).toLocaleDateString()
  return `${new Date(dateFrom).toLocaleDateString()} - ${new Date(dateTo).toLocaleDateString()}`
}

const formatTimeRange = (timeFrom, timeTo) => {
  if (!timeFrom) return 'N/A'
  if (timeFrom === timeTo) return timeFrom
  return `${timeFrom} - ${timeTo}`
}

const getPercentage = (correct, total) => {
  if (!total || total === 0) return 0
  return Math.round((correct / total) * 100)
}

const getRatingClass = (rating) => {
  if (!rating) return 'text-gray-500'
  if (rating >= 80) return 'text-green-600 font-semibold'
  if (rating >= 60) return 'text-yellow-600 font-semibold'
  return 'text-red-600 font-semibold'
}

const getScoreClass = (correct, total) => {
  const percentage = getPercentage(correct, total)
  if (percentage >= 80) return 'text-green-600 font-semibold'
  if (percentage >= 60) return 'text-yellow-600 font-semibold'
  return 'text-red-600 font-semibold'
}

const handlePrint = () => {
  ElMessage.info('Print functionality will be implemented')
}
</script>

<style scoped>
.exam-container {
  max-height: 70vh;
  overflow-y: auto;
}

.section {
  margin-bottom: 24px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 16px;
}

.section-title {
  font-size: 16px;
  font-weight: 600;
  margin-bottom: 16px;
  color: #374151;
  border-bottom: 2px solid #3b82f6;
  padding-bottom: 8px;
}

.field-label {
  font-weight: 500;
  color: #6b7280;
  display: block;
  margin-bottom: 4px;
}

.field-value {
  color: #111827;
  display: block;
  margin-bottom: 12px;
}

.rating {
  font-size: 18px;
  font-weight: 600;
}

.grid {
  display: grid;
}

.grid-cols-2 {
  grid-template-columns: repeat(2, 1fr);
}

.gap-4 {
  gap: 16px;
}

.col-span-2 {
  grid-column: span 2;
}

.no-data {
  text-align: center;
  padding: 40px;
}

.text-green-600 { color: #16a34a; }
.text-yellow-600 { color: #ca8a04; }
.text-red-600 { color: #dc2626; }
.text-gray-500 { color: #6b7280; }
.font-semibold { font-weight: 600; }

.exam-details {
  padding: 16px 0;
}

.subsection-title {
  font-size: 14px;
  font-weight: 600;
  margin-bottom: 12px;
  color: #374151;
}

.mt-4 {
  margin-top: 16px;
}
</style>
