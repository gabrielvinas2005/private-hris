<template>
  <el-form
    ref="formRef"
    :model="formData"
    :rules="rules"
    label-width="280px"
    label-position="top"
    class="eete-form"
  >
    <!-- Education Rating Field -->
    <el-form-item label="Education Rating Weighted Allocation" prop="education_rating">
      <el-input
        v-model="formData.education_rating"
        :disabled="loading"
        placeholder="Enter percentage e.g. 10"
        style="width: 100%"
      />
      <div class="help-text">Recommended CSC allocation: 10%</div>
    </el-form-item>

    <!-- Experience Rating Field -->
    <el-form-item label="Experience Rating Weighted Allocation" prop="experience_rating" required>
      <el-input
        v-model="formData.experience_rating"
        :disabled="loading"
        placeholder="Enter percentage e.g. 25"
        style="width: 100%"
      />
      <div class="help-text">Recommended CSC allocation: 25%</div>
    </el-form-item>

    <!-- Training Rating Field -->
    <el-form-item label="Training Rating Weighted Allocation" prop="training_rating" required>
      <el-input
        v-model="formData.training_rating"
        :disabled="loading"
        placeholder="Enter percentage e.g. 15"
        style="width: 100%"
      />
      <div class="help-text">Recommended CSC allocation: 15%</div>
    </el-form-item>

    <!-- Eligibility Rating Field -->
    <el-form-item label="Eligibility Rating Weighted Allocation" prop="eligibility_rating" required>
      <el-input
        v-model="formData.eligibility_rating"
        :disabled="loading"
        placeholder="Enter percentage e.g. 50"
        style="width: 100%"
      />
      <div class="help-text">Recommended CSC allocation: 50%</div>
    </el-form-item>

    <!-- Rating Summary -->
    <el-form-item label="Rating Summary">
      <div class="rating-summary">
        <el-card shadow="hover">
          <div class="summary-row">
            <span class="summary-label">Total Weighted Allocation:</span>
            <el-tag type="danger" size="large">{{ formatTotalPercent() }}% / 100%</el-tag>
          </div>
          <div class="summary-row">
            <span class="summary-label">Overall Compliance:</span>
            <el-tag :type="getAverageTagType(calculateAverage())" size="large">
              {{ formatAverage() }}% / 100%
            </el-tag>
          </div>
          <div class="summary-row">
            <span class="summary-label">Rating Status:</span>
            <el-tag :type="getStatusTagType(calculateAverage())" size="large">
              {{ getRatingStatus(calculateAverage()) }}
            </el-tag>
          </div>
        </el-card>
      </div>
    </el-form-item>

    <!-- Form Actions -->
    <el-form-item>
      <div class="form-actions">
        <el-button
          type="primary"
          @click="handleSubmit"
          :loading="loading"
          :icon="Check"
        >
          {{ isEdit ? 'Update' : 'Save' }}
        </el-button>
        <el-button
          @click="handleCancel"
          :disabled="loading"
          :icon="Close"
        >
          Cancel
        </el-button>
      </div>
    </el-form-item>
  </el-form>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { ElMessage } from 'element-plus'
import { Check, Close } from '@element-plus/icons-vue'

const props = defineProps({
  formData: {
    type: Object,
    default: () => ({
      education_rating: 0,
      experience_rating: 0,
      training_rating: 0,
      eligibility_rating: 0
    })
  },
  loading: {
    type: Boolean,
    default: false
  },
  isEdit: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['submit', 'cancel'])

const formRef = ref(null)

// Form validation rules
const MAX_TOTAL = 100
const MAX_PERCENT = 100

const rules = reactive({
  education_rating: [
    { required: true, message: 'Education rating is required', trigger: 'blur' },
    { validator: validateAllocation, trigger: 'blur' }
  ],
  experience_rating: [
    { required: true, message: 'Experience rating is required', trigger: 'blur' },
    { validator: validateAllocation, trigger: 'blur' }
  ],
  training_rating: [
    { required: true, message: 'Training rating is required', trigger: 'blur' },
    { validator: validateAllocation, trigger: 'blur' }
  ],
  eligibility_rating: [
    { required: true, message: 'Eligibility rating is required', trigger: 'blur' },
    { validator: validateAllocation, trigger: 'blur' }
  ]
})

const toNumber = (value) => {
  const num = parseFloat(value)
  return Number.isFinite(num) ? num : 0
}

function validateAllocation(rule, value, callback) {
  const num = toNumber(value)
  if (num < 0) {
    callback(new Error('Allocation cannot be negative'))
    return
  }
  if (num > MAX_PERCENT) {
    callback(new Error(`Allocation cannot exceed ${MAX_PERCENT}%`))
    return
  }
  callback()
}

function calculateTotal(limit = MAX_TOTAL) {
  const values = [
    props.formData.education_rating,
    props.formData.experience_rating,
    props.formData.training_rating,
    props.formData.eligibility_rating
  ]
  const sum = values.map(toNumber).reduce((acc, curr) => acc + curr, 0)
  if (Number.isFinite(limit) && limit > 0) {
    return Math.min(Math.max(sum, 0), limit)
  }
  return sum
}

function calculateAverage() {
  return calculateTotal(MAX_TOTAL)
}

function formatAverage() {
  const avg = calculateAverage()
  return Number.isFinite(avg) ? avg.toFixed(2) : '0.00'
}

function formatTotalPercent() {
  return calculateTotal(MAX_TOTAL).toFixed(2)
}

function normalizedAverage(value) {
  return Number.isFinite(value) ? value : 0
}

function getAverageTagType(average) {
  const avg = normalizedAverage(average)
  if (avg >= 80) return 'success'
  if (avg >= 60) return 'warning'
  return 'danger'
}

function getStatusTagType(average) {
  const avg = normalizedAverage(average)
  if (avg >= 80) return 'success'
  if (avg >= 60) return 'warning'
  return 'danger'
}

function getRatingStatus(average) {
  const avg = normalizedAverage(average)
  if (avg >= 80) return 'Excellent'
  if (avg >= 60) return 'Good'
  if (avg >= 40) return 'Fair'
  return 'Poor'
}

function handleSubmit() {
  if (!formRef.value) return
  
  formRef.value.validate((valid) => {
    if (valid) {
      const total = calculateTotal()
      if (Math.abs(total - MAX_TOTAL) > 0.01) {
        ElMessage.error('CSC EETE allocation requires the total weighted allocation to equal 100%.')
        return
      }
      emit('submit', { ...props.formData })
    }
  })
}

function handleCancel() {
  emit('cancel')
}
</script>

<style scoped>
.form-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  width: 100%;
}

.eete-form {
  padding: 20px;
}

.eete-form .el-form-item {
  margin-bottom: 24px;
}

.eete-form .el-form-item__label {
  font-weight: 600;
  color: #303133;
  font-size: 14px;
  margin-bottom: 8px;
}

.rating-summary {
  width: 100%;
}

.help-text {
  font-size: 12px;
  color: #909399;
  margin-top: 4px;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.summary-row:last-child {
  margin-bottom: 0;
}

.summary-label {
  font-weight: 500;
  color: #606266;
}

:deep(.el-form-item__label) {
  font-weight: 500;
  color: #606266;
}

:deep(.el-input) {
  width: 100%;
}

:deep(.el-input__inner) {
  font-size: 14px;
  color: #606266;
}

:deep(.el-card__body) {
  padding: 16px;
}

:deep(.el-button) {
  border-radius: 6px;
  font-weight: 500;
}

:deep(.el-button--primary) {
  background-color: #409eff;
  border-color: #409eff;
}

:deep(.el-button--primary:hover) {
  background-color: #66b1ff;
  border-color: #66b1ff;
}
</style>
