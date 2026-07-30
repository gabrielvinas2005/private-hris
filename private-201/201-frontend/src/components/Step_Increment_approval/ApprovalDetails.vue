<template>
  <el-drawer
    v-model="visible"
    :title="title"
    size="50%"
    direction="rtl"
    :close-on-click-modal="false"
  >
    <div v-loading="loading" class="approval-details">
      <div v-if="stepIncrement">
        <!-- Employee Information -->
        <el-card shadow="never" class="mb-4">
          <template #header>
            <div class="flex items-center">
              <el-icon class="mr-2"><User /></el-icon>
              <span>Employee Information</span>
            </div>
          </template>
          
          <div class="employee-info">
            <div class="flex items-center mb-4">
              <el-avatar 
                :src="stepIncrement.photo" 
                :size="64"
                class="mr-4"
              >
                {{ stepIncrement.name?.charAt(0) }}
              </el-avatar>
              <div>
                <h3 class="text-lg font-semibold mb-1">{{ stepIncrement.name }}</h3>
                <p class="text-gray-600">ID: {{ stepIncrement.employee_id }}</p>
              </div>
            </div>
          </div>
        </el-card>

        <!-- Step Increment Details -->
        <el-card shadow="never" class="mb-4">
          <template #header>
            <div class="flex items-center">
              <el-icon class="mr-2"><TrendCharts /></el-icon>
              <span>Step Increment Details</span>
            </div>
          </template>
          
          <el-row :gutter="16">
            <el-col :span="12">
              <div class="detail-item">
                <label class="detail-label">Effectivity Date:</label>
                <span class="detail-value">{{ formatDate(stepIncrement.effectivity_date) }}</span>
              </div>
            </el-col>
            <el-col :span="12">
              <div class="detail-item">
                <label class="detail-label">Status:</label>
                <el-tag 
                  :type="getStatusType(stepIncrement)" 
                  size="small"
                >
                  {{ getStatusText(stepIncrement) }}
                </el-tag>
              </div>
            </el-col>
          </el-row>

          <el-row :gutter="16" class="mt-4">
            <el-col :span="12">
              <div class="detail-item">
                <label class="detail-label">Forwarded Date:</label>
                <span class="detail-value">{{ formatDate(stepIncrement.forwarded_date) }}</span>
              </div>
            </el-col>
            <el-col :span="12">
              <div class="detail-item">
                <label class="detail-label">Approved Date:</label>
                <span class="detail-value">{{ formatDate(stepIncrement.approved_date) }}</span>
              </div>
            </el-col>
          </el-row>
        </el-card>

        <!-- Salary Comparison -->
        <el-card shadow="never" class="mb-4">
          <template #header>
            <div class="flex items-center">
              <el-icon class="mr-2"><Money /></el-icon>
              <span>Salary Comparison</span>
            </div>
          </template>
          
          <div class="salary-comparison">
            <el-row :gutter="24">
              <!-- Current Salary -->
              <el-col :span="12">
                <div class="salary-card current">
                  <div class="salary-header">
                    <h4>Current Salary</h4>
                    <el-tag size="small">
                      {{ getCurrentGradeStep() }}
                    </el-tag>
                  </div>
                  <div class="salary-amount">
                    ₱{{ formatCurrency(stepIncrement?.current_salary || 0) }}
                  </div>
                </div>
              </el-col>
              
              <!-- New Salary -->
              <el-col :span="12">
                <div class="salary-card new">
                  <div class="salary-header">
                    <h4>New Salary</h4>
                    <el-tag type="success" size="small">
                      {{ getNewGradeStep() }}
                    </el-tag>
                  </div>
                  <div class="salary-amount">
                    ₱{{ formatCurrency(stepIncrement?.new_salary || 0) }}
                  </div>
                </div>
              </el-col>
            </el-row>
            
            <!-- Salary Increase -->
            <div class="salary-increase mt-4">
              <el-alert
                :title="`Salary Increase: ₱${formatCurrency(salaryIncrease)}`"
                type="success"
                :closable="false"
                show-icon
              >
                <template #default>
                  <div class="increase-details">
                    <p><strong>Monthly Increase:</strong> ₱{{ formatCurrency(salaryIncrease) }}</p>
                    <p><strong>Annual Increase:</strong> ₱{{ formatCurrency(salaryIncrease * 12) }}</p>
                    <p><strong>Percentage Increase:</strong> {{ increasePercentage }}%</p>
                  </div>
                </template>
              </el-alert>
            </div>
          </div>
        </el-card>

        <!-- Deduction Details -->
        <el-card shadow="never" class="mb-4" v-if="hasDeductions">
          <template #header>
            <div class="flex items-center">
              <el-icon class="mr-2"><Document /></el-icon>
              <span>New Deduction Details</span>
            </div>
          </template>
          
          <el-row :gutter="16">
            <el-col :span="12">
                <div class="detail-item">
                <label class="detail-label">Tax Amount:</label>
                <span class="detail-value">₱{{ formatCurrency(stepIncrement.new_tax_amount) }}</span>
              </div>
              <div class="detail-item">
                <label class="detail-label">GSIS Amount:</label>
                <span class="detail-value">₱{{ formatCurrency(stepIncrement.new_gsis_amount) }}</span>
              </div>
            </el-col>
            <el-col :span="12">
              <div class="detail-item">
                <label class="detail-label">SSS Amount:</label>
                <span class="detail-value">₱{{ formatCurrency(stepIncrement.new_sss_amount) }}</span>
              </div>
              <div class="detail-item">
                <label class="detail-label">Pag-IBIG Amount:</label>
                <span class="detail-value">₱{{ formatCurrency(stepIncrement.new_pagibig_amount) }}</span>
              </div>
            </el-col>
          </el-row>
          
          <el-row :gutter="16" class="mt-2">
            <el-col :span="12">
              <div class="detail-item">
                <label class="detail-label">PhilHealth Amount:</label>
                <span class="detail-value">₱{{ formatCurrency(stepIncrement.new_philhealth_amount) }}</span>
              </div>
            </el-col>
            <el-col :span="12">
              <div class="detail-item">
                <label class="detail-label">Total Deductions:</label>
                <span class="detail-value font-semibold">₱{{ formatCurrency(totalDeductions) }}</span>
              </div>
            </el-col>
          </el-row>
        </el-card>

        <!-- Net Salary Summary -->
        <el-card shadow="never" class="mb-6">
          <template #header>
            <div class="flex items-center">
              <el-icon class="mr-2"><DataAnalysis /></el-icon>
              <span>Net Salary Summary</span>
            </div>
          </template>
          
          <div class="net-salary-summary">
            <el-row :gutter="24">
              <el-col :span="12">
                <div class="summary-item">
                  <label>Current Gross Salary:</label>
                  <span class="amount">₱{{ formatCurrency(stepIncrement?.current_salary || 0) }}</span>
                </div>
                <div class="summary-item">
                  <label>Current Deductions:</label>
                  <span class="amount deduction">₱{{ formatCurrency(currentTotalDeductions) }}</span>
                </div>
                <div class="summary-item total-net">
                  <label>Current Net Salary:</label>
                  <span class="amount">₱{{ formatCurrency(currentNetSalary) }}</span>
                </div>
              </el-col>
              <el-col :span="12">
                <div class="summary-item">
                  <label>New Gross Salary:</label>
                  <span class="amount highlight">₱{{ formatCurrency(stepIncrement?.new_salary || 0) }}</span>
                </div>
                <div class="summary-item">
                  <label>New Deductions:</label>
                  <span class="amount deduction">₱{{ formatCurrency(newTotalDeductions) }}</span>
                </div>
                <div class="summary-item total-net">
                  <label>New Net Salary:</label>
                  <span class="amount highlight">₱{{ formatCurrency(newNetSalary) }}</span>
                </div>
              </el-col>
            </el-row>
            <div class="net-increase mt-3" :class="{ 'negative': netIncrease < 0 }">
              <strong>Net Increase: ₱{{ formatCurrency(netIncrease) }}</strong>
            </div>
          </div>
        </el-card>
      </div>
    </div>

    <!-- Action Buttons -->
    <template #footer>
      <div class="drawer-footer">
        <el-button @click="visible = false">Close</el-button>
        <el-button 
          type="danger" 
          :icon="Close" 
          @click="onReject"
          :loading="loading"
        >
          Reject
        </el-button>
        <el-button 
          type="success" 
          :icon="Check" 
          @click="onApprove"
          :loading="loading"
        >
          Approve
        </el-button>
      </div>
    </template>
  </el-drawer>
</template>

<script setup>
import { computed } from 'vue'
import { 
  User, TrendCharts, Money, Document, DataAnalysis, 
  Check, Close 
} from '@element-plus/icons-vue'
import { ElMessageBox } from 'element-plus'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  stepIncrement: { type: Object, default: null },
  loading: { type: Boolean, default: false }
})

const emit = defineEmits(['update:modelValue', 'approve', 'reject'])

// Computed properties
const visible = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

const title = computed(() => {
  return props.stepIncrement?.name 
    ? `Step Increment Details - ${props.stepIncrement.name}`
    : 'Step Increment Details'
})

const salaryIncrease = computed(() => {
  if (!props.stepIncrement) return 0
  const difference = (props.stepIncrement.new_salary || 0) - (props.stepIncrement.current_salary || 0)
  // Return absolute value to remove negative sign in display
  return Math.abs(difference)
})

const increasePercentage = computed(() => {
  if (!props.stepIncrement || !props.stepIncrement.current_salary || props.stepIncrement.current_salary === 0) return 0
  const difference = (props.stepIncrement.new_salary || 0) - (props.stepIncrement.current_salary || 0)
  // Return absolute value to remove negative sign in display
  return Math.abs((difference / props.stepIncrement.current_salary) * 100).toFixed(2)
})

const hasDeductions = computed(() => {
  if (!props.stepIncrement) return false
  return props.stepIncrement.new_tax_amount || 
         props.stepIncrement.new_gsis_amount || 
         props.stepIncrement.new_sss_amount || 
         props.stepIncrement.new_pagibig_amount || 
         props.stepIncrement.new_philhealth_amount
})

const currentTotalDeductions = computed(() => {
  if (!props.stepIncrement) return 0
  return (Number(props.stepIncrement.current_tax_amount) || 0) +
         (Number(props.stepIncrement.current_gsis_amount) || 0) +
         (Number(props.stepIncrement.current_sss_amount) || 0) +
         (Number(props.stepIncrement.current_pagibig_amount) || 0) +
         (Number(props.stepIncrement.current_philhealth_amount) || 0)
})

const newTotalDeductions = computed(() => {
  if (!props.stepIncrement) return 0
  return (Number(props.stepIncrement.new_tax_amount) || 0) +
         (Number(props.stepIncrement.new_gsis_amount) || 0) +
         (Number(props.stepIncrement.new_sss_amount) || 0) +
         (Number(props.stepIncrement.new_pagibig_amount) || 0) +
         (Number(props.stepIncrement.new_philhealth_amount) || 0)
})

const totalDeductions = computed(() => {
  // For display purposes, use new deductions
  return newTotalDeductions.value
})

const currentNetSalary = computed(() => {
  if (!props.stepIncrement) return 0
  const currentSalary = Number(props.stepIncrement.current_salary) || 0
  const currentDeductions = currentTotalDeductions.value
  return currentSalary - currentDeductions
})

const newNetSalary = computed(() => {
  if (!props.stepIncrement) return 0
  const newSalary = Number(props.stepIncrement.new_salary) || 0
  const newDeductions = newTotalDeductions.value
  return Math.max(0, newSalary - newDeductions) // Ensure net is not negative
})

const netIncrease = computed(() => {
  return newNetSalary.value - currentNetSalary.value
})

// Methods
const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const formatCurrency = (amount) => {
  const num = Number(amount) || 0
  return num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

const getCurrentGradeStep = () => {
  if (!props.stepIncrement) return 'Grade N/A - Step N/A'
  
  const gradeId = props.stepIncrement.current_salary_grade_id
  const stepId = props.stepIncrement.current_salary_step_id
  const gradeName = props.stepIncrement.current_salary_grade
  const stepName = props.stepIncrement.current_salary_step
  
  const grade = gradeName || (gradeId ? `Grade ${gradeId}` : 'Grade N/A')
  const step = stepName || (stepId ? `Step ${stepId}` : 'Step N/A')
  return `${grade} - ${step}`
}

const getNewGradeStep = () => {
  if (!props.stepIncrement) return 'Grade N/A - Step N/A'
  
  const gradeId = props.stepIncrement.new_salary_grade_id
  const stepId = props.stepIncrement.new_salary_step_id
  const gradeName = props.stepIncrement.new_salary_grade
  const stepName = props.stepIncrement.new_salary_step
  
  const grade = gradeName || (gradeId ? `Grade ${gradeId}` : 'Grade N/A')
  const step = stepName || (stepId ? `Step ${stepId}` : 'Step N/A')
  return `${grade} - ${step}`
}

const getStatusType = (stepIncrement) => {
  if (!stepIncrement) return 'info'
  
  // Check explicitly for true/1 values (not just truthy)
  const isApproved = stepIncrement.is_approved === true || stepIncrement.is_approved === 1 || stepIncrement.is_approved === '1'
  const isDisapproved = stepIncrement.is_disapproved === true || stepIncrement.is_disapproved === 1 || stepIncrement.is_disapproved === '1'
  const isForwarded = stepIncrement.is_forwarded === true || stepIncrement.is_forwarded === 1 || stepIncrement.is_forwarded === '1'
  
  if (isApproved) return 'success'
  if (isDisapproved) return 'danger'
  if (isForwarded) return 'warning'
  return 'info'
}

const getStatusText = (stepIncrement) => {
  if (!stepIncrement) return 'Draft'
  
  // Check explicitly for true/1 values (not just truthy)
  // Only return "Approved" if explicitly true/1, otherwise check other statuses
  const isApproved = stepIncrement.is_approved === true || stepIncrement.is_approved === 1 || stepIncrement.is_approved === '1'
  const isDisapproved = stepIncrement.is_disapproved === true || stepIncrement.is_disapproved === 1 || stepIncrement.is_disapproved === '1'
  const isForwarded = stepIncrement.is_forwarded === true || stepIncrement.is_forwarded === 1 || stepIncrement.is_forwarded === '1'
  
  // Priority: Approved > Disapproved > Forwarded > Draft
  if (isApproved) return 'Approved'
  if (isDisapproved) return 'Rejected'
  if (isForwarded) return 'Pending Approval'
  return 'Draft'
}

const onApprove = async () => {
  try {
    await ElMessageBox.confirm(
      `Are you sure you want to approve this step increment for ${props.stepIncrement.name}?`,
      'Confirm Approval',
      {
        confirmButtonText: 'Approve',
        cancelButtonText: 'Cancel',
        type: 'success'
      }
    )
    emit('approve', props.stepIncrement)
  } catch {
    // User cancelled
  }
}

const onReject = async () => {
  try {
    await ElMessageBox.confirm(
      `Are you sure you want to reject this step increment for ${props.stepIncrement.name}?`,
      'Confirm Rejection',
      {
        confirmButtonText: 'Reject',
        cancelButtonText: 'Cancel',
        type: 'error'
      }
    )
    emit('reject', props.stepIncrement)
  } catch {
    // User cancelled
  }
}
</script>

<style scoped>
.approval-details {
  padding: 0 16px;
}

.employee-info h3 {
  margin: 0;
  color: #303133;
}

.detail-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 0;
  border-bottom: 1px solid #f0f0f0;
}

.detail-item:last-child {
  border-bottom: none;
}

.detail-label {
  font-weight: 500;
  color: #606266;
  min-width: 120px;
}

.detail-value {
  color: #303133;
  font-weight: 500;
}

.salary-comparison {
  padding: 16px 0;
}

.salary-card {
  border: 2px solid #e4e7ed;
  border-radius: 8px;
  padding: 16px;
  text-align: center;
  transition: all 0.3s ease;
}

.salary-card.current {
  border-color: #909399;
  background: #f4f4f5;
}

.salary-card.new {
  border-color: #67c23a;
  background: #f0f9ff;
}

.salary-header {
  margin-bottom: 12px;
}

.salary-header h4 {
  margin: 0 0 8px 0;
  color: #303133;
}

.salary-amount {
  font-size: 1.5rem;
  font-weight: bold;
  color: #303133;
}

.salary-increase {
  text-align: center;
}

.increase-details p {
  margin: 4px 0;
}

.net-salary-summary {
  padding: 16px;
  background: #f8f9fa;
  border-radius: 8px;
}

.summary-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 6px 0;
  border-bottom: 1px solid #f0f0f0;
}

.summary-item:last-child {
  border-bottom: none;
}

.summary-item.total-net {
  padding-top: 8px;
  margin-top: 8px;
  border-top: 2px solid #e5e7eb;
  font-weight: 600;
}

.summary-item label {
  font-weight: 500;
  color: #606266;
}

.summary-item .amount {
  font-size: 1rem;
  font-weight: bold;
  color: #303133;
}

.summary-item .amount.highlight {
  color: #67c23a;
}

.summary-item .amount.deduction {
  color: #f56c6c;
  font-weight: 500;
}

.net-increase {
  text-align: center;
  font-size: 1.2rem;
  color: #67c23a;
  padding: 12px;
  background: #f0f9ff;
  border-radius: 6px;
}

.net-increase.negative {
  color: #f56c6c;
  background: #fef0f0;
}

.drawer-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  padding: 16px;
  border-top: 1px solid #e4e7ed;
}
</style>
