<template>
  <el-drawer v-model="visible" size="640px" :with-header="false">
    <div class="drawer-wrap">
      <div class="drawer-header">
        <div class="header-left">
          <el-avatar :size="56" :src="stepIncrementDetail?.photo ? `data:image/jpeg;base64,${stepIncrementDetail.photo}` : avatarUrl" />
          <div class="title-stack">
            <div class="name">{{ stepIncrementDetail?.employee_name || 'Employee' }}</div>
            <div class="sub">Step Increment Details</div>
          </div>
        </div>
        <el-tag type="success">Processed</el-tag>
      </div>

      <el-skeleton v-if="loading" :rows="6" animated />
      <el-empty v-else-if="!stepIncrementDetail" description="No data" />

      <template v-else>
        <!-- Step Increment Information -->
        <el-card shadow="never" class="mb-12">
          <template #header>
            <div class="card-header">Step Increment Information</div>
          </template>
          <el-row :gutter="12">
            <el-col :span="12">
              <div class="info-item">
                <div class="label">Effectivity Date</div>
                <div class="value">{{ formatDate(stepIncrementDetail.effectivity_date) }}</div>
              </div>
            </el-col>
            <el-col :span="12">
              <div class="info-item">
                <div class="label">Employee Name</div>
                <div class="value">{{ stepIncrementDetail.employee_name }}</div>
              </div>
            </el-col>
          </el-row>
        </el-card>

        <!-- Current Salary Information -->
        <el-card shadow="never" class="mb-12">
          <template #header>
            <div class="card-header">Previous Salary Information</div>
          </template>
          <el-row :gutter="12">
            <el-col :span="8">
              <div class="info-item">
                <div class="label">Salary Grade</div>
                <div class="value">{{ getCurrentSalaryGradeName(stepIncrementDetail.current_salary_grade_id) }}</div>
              </div>
            </el-col>
            <el-col :span="8">
              <div class="info-item">
                <div class="label">Salary Step</div>
                <div class="value">{{ getCurrentSalaryStepName(stepIncrementDetail.current_salary_step_id) }}</div>
              </div>
            </el-col>
            <el-col :span="8">
              <div class="info-item">
                <div class="label">Monthly Salary</div>
                <div class="value">₱{{ formatCurrency(stepIncrementDetail.current_salary) }}</div>
              </div>
            </el-col>
          </el-row>
        </el-card>

        <!-- New Salary Information -->
        <el-card shadow="never" class="mb-12">
          <template #header>
            <div class="card-header">New Salary Information</div>
          </template>
          <el-row :gutter="12">
            <el-col :span="8">
              <div class="info-item">
                <div class="label">Salary Grade</div>
                <div class="value">{{ getNewSalaryGradeName(stepIncrementDetail.new_salary_grade_id) }}</div>
              </div>
            </el-col>
            <el-col :span="8">
              <div class="info-item">
                <div class="label">Salary Step</div>
                <div class="value">{{ getNewSalaryStepName(stepIncrementDetail.new_salary_step_id) }}</div>
              </div>
            </el-col>
            <el-col :span="8">
              <div class="info-item">
                <div class="label">Monthly Salary</div>
                <div class="value">₱{{ formatCurrency(stepIncrementDetail.new_salary) }}</div>
              </div>
            </el-col>
          </el-row>
          
          <!-- Salary Increase -->
          <el-row :gutter="12" class="mt-3">
            <el-col :span="24">
              <div class="salary-increase">
                <div class="increase-label">Salary Increase</div>
                <div class="increase-amount">₱{{ formatCurrency(salaryIncrease) }}</div>
                <div class="increase-percentage">({{ increasePercentage }}% increase)</div>
              </div>
            </el-col>
          </el-row>
        </el-card>

        <!-- Deduction Details -->
        <el-card shadow="never" class="mb-12">
          <template #header>
            <div class="card-header">New Deduction Details</div>
          </template>
          <el-row :gutter="12">
            <el-col :span="12">
              <div class="info-item">
                <div class="label">Tax Amount</div>
                <div class="value">₱{{ formatCurrency(stepIncrementDetail.new_tax_amount) }}</div>
              </div>
            </el-col>
            <el-col :span="12">
              <div class="info-item">
                <div class="label">GSIS Amount</div>
                <div class="value">₱{{ formatCurrency(stepIncrementDetail.new_gsis_amount) }}</div>
              </div>
            </el-col>
          </el-row>
          <el-row :gutter="12">
            <el-col :span="12">
              <div class="info-item">
                <div class="label">SSS Amount</div>
                <div class="value">₱{{ formatCurrency(stepIncrementDetail.new_sss_amount) }}</div>
              </div>
            </el-col>
            <el-col :span="12">
              <div class="info-item">
                <div class="label">Pag-ibig Amount</div>
                <div class="value">₱{{ formatCurrency(stepIncrementDetail.new_pagibig_amount) }}</div>
              </div>
            </el-col>
          </el-row>
          <el-row :gutter="12">
            <el-col :span="12">
              <div class="info-item">
                <div class="label">PhilHealth Amount</div>
                <div class="value">₱{{ formatCurrency(stepIncrementDetail.new_philhealth_amount) }}</div>
              </div>
            </el-col>
            <el-col :span="12">
              <div class="info-item">
                <div class="label">Total Deductions</div>
                <div class="value total-deductions">₱{{ formatCurrency(totalDeductions) }}</div>
              </div>
            </el-col>
          </el-row>
        </el-card>

        <!-- Net Salary Summary -->
        <el-card shadow="never" class="mb-12">
          <template #header>
            <div class="card-header">Salary Summary</div>
          </template>
          <div class="salary-summary">
            <div class="summary-row">
              <span class="summary-label">Gross Salary:</span>
              <span class="summary-value">₱{{ formatCurrency(stepIncrementDetail.new_salary) }}</span>
            </div>
            <div class="summary-row">
              <span class="summary-label">Total Deductions:</span>
              <span class="summary-value">₱{{ formatCurrency(totalDeductions) }}</span>
            </div>
            <div class="summary-row total">
              <span class="summary-label">Net Salary:</span>
              <span class="summary-value">₱{{ formatCurrency(netSalary) }}</span>
            </div>
          </div>
        </el-card>
      </template>
    </div>
  </el-drawer>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useStepIncrement } from '@/composable/useStepIncrement'
import { ElMessage } from 'element-plus'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  id: { type: Number, required: true }
})
const emit = defineEmits(['update:modelValue'])

const visible = computed({ 
  get: () => props.modelValue, 
  set: v => emit('update:modelValue', v) 
})

const { showStepIncrement, fetchForm, loading, stepIncrementDetail, formData } = useStepIncrement()
const avatarUrl = 'https://cube.elemecdn.com/3/7c/3ed689499777db3d2947606ee76bcpng.png'

// Computed properties
const salaryIncrease = computed(() => {
  if (!stepIncrementDetail.value) return 0
  const current = parseFloat(stepIncrementDetail.value.current_salary) || 0
  const newSalary = parseFloat(stepIncrementDetail.value.new_salary) || 0
  const difference = newSalary - current
  // Return absolute value to remove negative sign in display
  return Math.abs(difference)
})

const increasePercentage = computed(() => {
  if (!stepIncrementDetail.value) return 0
  const current = parseFloat(stepIncrementDetail.value.current_salary) || 0
  if (current === 0) return 0
  const newSalary = parseFloat(stepIncrementDetail.value.new_salary) || 0
  const difference = newSalary - current
  // Return absolute value to remove negative sign in display
  return Math.abs((difference / current) * 100).toFixed(2)
})

const totalDeductions = computed(() => {
  if (!stepIncrementDetail.value) return 0
  const tax = parseFloat(stepIncrementDetail.value.new_tax_amount) || 0
  const gsis = parseFloat(stepIncrementDetail.value.new_gsis_amount) || 0
  const sss = parseFloat(stepIncrementDetail.value.new_sss_amount) || 0
  const pagibig = parseFloat(stepIncrementDetail.value.new_pagibig_amount) || 0
  const philhealth = parseFloat(stepIncrementDetail.value.new_philhealth_amount) || 0
  return tax + gsis + sss + pagibig + philhealth
})

const netSalary = computed(() => {
  if (!stepIncrementDetail.value) return 0
  const gross = parseFloat(stepIncrementDetail.value.new_salary) || 0
  return gross - totalDeductions.value
})

// Lookup functions
const getCurrentSalaryGradeName = (id) => formData.value?.salary_grades?.find(g => g.id == id)?.name || '—'
const getCurrentSalaryStepName = (id) => formData.value?.salary_steps?.find(s => s.id == id)?.name || '—'
const getNewSalaryGradeName = (id) => formData.value?.salary_grades?.find(g => g.id == id)?.name || '—'
const getNewSalaryStepName = (id) => formData.value?.salary_steps?.find(s => s.id == id)?.name || '—'

// Utility functions
const formatCurrency = (amount) => {
  const num = parseFloat(amount) || 0
  return num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

const formatDate = (date) => {
  if (!date) return '—'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const loadDetail = async () => {
  if (!visible.value || !props.id) return
  
  try {
    await Promise.all([
      showStepIncrement(props.id),
      fetchForm(0) // Load form data for lookups
    ])
  } catch (e) {
    ElMessage.error('Failed to load step increment details.')
  }
}

watch(visible, () => loadDetail())
watch(() => props.id, () => loadDetail())
</script>

<style scoped>
.drawer-wrap{padding:16px}
.drawer-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:12px}
.header-left{display:flex;align-items:center;gap:12px}
.title-stack{display:flex;flex-direction:column}
.name{font-weight:600;font-size:16px;color:#1f2937}
.sub{font-size:12px;color:#6b7280}
.card-header{font-weight:600;color:#374151}
.info-item{margin-bottom:8px}
.info-item .label{font-size:12px;color:#64748b}
.info-item .value{font-size:14px;color:#1f2937}
.mb-12{margin-bottom:12px}
.mt-3{margin-top:12px}

.salary-increase {
  text-align: center;
  padding: 16px;
  background: linear-gradient(135deg, #67c23a 0%, #85ce61 100%);
  border-radius: 8px;
  color: white;
}
.increase-label {
  font-size: 14px;
  margin-bottom: 4px;
}
.increase-amount {
  font-size: 24px;
  font-weight: 700;
  margin-bottom: 4px;
}
.increase-percentage {
  font-size: 12px;
  opacity: 0.9;
}

.total-deductions {
  font-weight: 600;
  color: #f56c6c;
}

.salary-summary {
  padding: 16px;
  background: #f8f9fa;
  border-radius: 8px;
}
.summary-row {
  display: flex;
  justify-content: space-between;
  margin-bottom: 12px;
}
.summary-row.total {
  border-top: 2px solid #e5e7eb;
  padding-top: 12px;
  font-size: 16px;
  font-weight: 700;
}
.summary-label {
  color: #64748b;
}
.summary-value {
  color: #1f2937;
  font-weight: 600;
}
</style>
