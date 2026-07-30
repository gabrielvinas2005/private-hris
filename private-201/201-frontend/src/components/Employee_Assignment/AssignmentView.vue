<template>
  <el-drawer v-model="visible" size="640px" :with-header="false">
    <div class="drawer-wrap">
      <div class="drawer-header">
        <div class="header-left">
          <el-avatar :size="56" :src="avatarUrl" />
          <div class="title-stack">
            <div class="name">{{ summary?.employee_name || 'Employee' }}</div>
            <div class="sub">ID: {{ summary?.employee_no || '—' }}</div>
          </div>
        </div>
        <el-tag type="success" v-if="summary?.nature">{{ summary.nature }}</el-tag>
      </div>

      <el-skeleton v-if="loading" :rows="6" animated />
      <el-empty v-else-if="!summary" description="No data" />

      <template v-else>
        <!-- Appointment Details -->
        <el-card shadow="never" class="mb-12">
          <template #header>
            <div class="card-header">
              <span>Appointment Details</span>
              <el-tag v-if="summary.is_plantilla" type="success" size="small">Plantilla</el-tag>
            </div>
          </template>
          <el-row :gutter="12">
            <el-col :span="12">
              <div class="info-item">
                <div class="label">Nature of Appointment</div>
                <div class="value">{{ summary.nature }}</div>
              </div>
            </el-col>
            <el-col :span="12">
              <div class="info-item">
                <div class="label">Employee</div>
                <div class="value">{{ summary.employee_name }}</div>
              </div>
            </el-col>
          </el-row>
        </el-card>

        <!-- Assignment Information -->
        <el-card shadow="never" class="mb-12">
          <template #header>
            <div class="card-header">Employee Assignment Details</div>
          </template>
          <el-row :gutter="12">
            <el-col :span="12">
              <div class="info-item">
                <div class="label">Branch</div>
                <div class="value">{{ summary.branch }}</div>
              </div>
            </el-col>
            <el-col :span="12">
              <div class="info-item">
                <div class="label">Plantilla Item</div>
                <div class="value">{{ summary.plantilla_code || '—' }}</div>
              </div>
            </el-col>
          </el-row>
          <el-row :gutter="12">
            <el-col :span="12">
              <div class="info-item">
                <div class="label">Department</div>
                <div class="value">{{ summary.department }}</div>
              </div>
            </el-col>
            <el-col :span="12">
              <div class="info-item">
                <div class="label">Salary Grade</div>
                <div class="value">{{ summary.salary_grade_name || '—' }}</div>
              </div>
            </el-col>
          </el-row>
          <el-row :gutter="12">
            <el-col :span="12">
              <div class="info-item">
                <div class="label">Employment Type</div>
                <div class="value">{{ summary.employment_type }}</div>
              </div>
            </el-col>
            <el-col :span="12">
              <div class="info-item">
                <div class="label">Salary Step</div>
                <div class="value">{{ summary.salary_step_name || '—' }}</div>
              </div>
            </el-col>
          </el-row>
          <el-row :gutter="12">
            <el-col :span="12">
              <div class="info-item">
                <div class="label">Payroll Interval</div>
                <div class="value">{{ summary.payroll_interval_name || '—' }}</div>
              </div>
            </el-col>
            <el-col :span="12">
              <div class="info-item">
                <div class="label">Position</div>
                <div class="value">{{ summary.position }}</div>
              </div>
            </el-col>
          </el-row>
          <el-row :gutter="12">
            <el-col :span="12">
              <div class="info-item">
                <div class="label">Date of Assignment</div>
                <div class="value">{{ summary.date_position_appointed || '—' }}</div>
              </div>
            </el-col>
            <el-col :span="12">
              <div class="info-item">
                <div class="label">Date of Effectivity</div>
                <div class="value">{{ summary.date_of_effectivity || '—' }}</div>
              </div>
            </el-col>
          </el-row>
        </el-card>

        <!-- Salary Details -->
        <el-card shadow="never" class="mb-12">
          <template #header>
            <div class="card-header">Salary Details</div>
          </template>
          
          <!-- Old Salary Information -->
          <div class="salary-section">
            <h4 class="section-title">Previous Salary Information</h4>
            <el-row :gutter="12">
              <el-col :span="8">
                <div class="info-item">
                  <div class="label">Old Salary</div>
                  <div class="value strong">{{ currency(summary.old_salary) }}</div>
                </div>
              </el-col>
              <el-col :span="8">
                <div class="info-item">
                  <div class="label">Old GSIS Amount</div>
                  <div class="value">{{ currency(summary.old_gsis_amount) }}</div>
                </div>
              </el-col>
              <el-col :span="8">
                <div class="info-item">
                  <div class="label">Old Pag-Ibig Amount</div>
                  <div class="value">{{ currency(summary.old_pagibig_amount) }}</div>
                </div>
              </el-col>
            </el-row>
            <el-row :gutter="12">
              <el-col :span="8">
                <div class="info-item">
                  <div class="label">Old Tax Amount</div>
                  <div class="value">{{ currency(summary.old_tax_amount) }}</div>
                </div>
              </el-col>
              <el-col :span="8">
                <div class="info-item">
                  <div class="label">Old SSS Amount</div>
                  <div class="value">{{ currency(summary.old_sss_amount) }}</div>
                </div>
              </el-col>
              <el-col :span="8">
                <div class="info-item">
                  <div class="label">Old PhilHealth Amount</div>
                  <div class="value">{{ currency(summary.old_philhealth_amount) }}</div>
                </div>
              </el-col>
            </el-row>
            <el-row :gutter="12">
              <el-col :span="8">
                <div class="info-item">
                  <div class="label">Old Total Deduction</div>
                  <div class="value">{{ currency(oldTotalDeduction) }}</div>
                </div>
              </el-col>
              <el-col :span="8">
                <div class="info-item">
                  <div class="label">Old Net Pay</div>
                  <div class="value">{{ currency(oldNetPay) }}</div>
                </div>
              </el-col>
              <el-col :span="8"></el-col>
            </el-row>
          </div>

          <!-- New Salary Information -->
          <div class="salary-section mt-4">
            <h4 class="section-title">New Salary Information</h4>
            <el-row :gutter="12">
              <el-col :span="8">
                <div class="info-item">
                  <div class="label">New Salary</div>
                  <div class="value strong">{{ currency(summary.new_salary) }}</div>
                </div>
              </el-col>
              <el-col :span="8">
                <div class="info-item">
                  <div class="label">New GSIS Amount</div>
                  <div class="value">{{ currency(summary.new_gsis_amount) }}</div>
                </div>
              </el-col>
              <el-col :span="8">
                <div class="info-item">
                  <div class="label">New Pag-Ibig Amount</div>
                  <div class="value">{{ currency(summary.new_pagibig_amount) }}</div>
                </div>
              </el-col>
            </el-row>
            <el-row :gutter="12">
              <el-col :span="8">
                <div class="info-item">
                  <div class="label">New Tax Amount</div>
                  <div class="value">{{ currency(summary.new_tax_amount) }}</div>
                </div>
              </el-col>
              <el-col :span="8">
                <div class="info-item">
                  <div class="label">New SSS Amount</div>
                  <div class="value">{{ currency(summary.new_sss_amount) }}</div>
                </div>
              </el-col>
              <el-col :span="8">
                <div class="info-item">
                  <div class="label">New PhilHealth Amount</div>
                  <div class="value">{{ currency(summary.new_philhealth_amount) }}</div>
                </div>
              </el-col>
            </el-row>
            <el-row :gutter="12">
              <el-col :span="8">
                <div class="info-item">
                  <div class="label">New Total Deduction</div>
                  <div class="value">{{ currency(newTotalDeduction) }}</div>
                </div>
              </el-col>
              <el-col :span="8">
                <div class="info-item">
                  <div class="label">New Net Pay</div>
                  <div class="value">{{ currency(newNetPay) }}</div>
                </div>
              </el-col>
              <el-col :span="8">
                <div class="info-item">
                  <div class="label">Salary Increase</div>
                  <div class="value" :class="{ 'positive': salaryIncrease > 0, 'negative': salaryIncrease < 0 }">
                    {{ currency(salaryIncrease) }}
                  </div>
                </div>
              </el-col>
            </el-row>
          </div>
        </el-card>
      </template>
    </div>
  </el-drawer>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useAssignment } from '@/composable/useAssignment'

const props = defineProps({ modelValue: { type: Boolean, default: false }, id: { type: Number, required: true } })
const emit = defineEmits(['update:modelValue'])

const visible = computed({ get: () => props.modelValue, set: v => emit('update:modelValue', v) })
const { showAssignment, formData } = useAssignment()
const detail = ref(null)
const loading = ref(false)

const summary = computed(() => {
  const d = detail.value
  if (!d) return null
  
  // The EmployeePromotionController@show returns { promotion: {...}, summary: {...} }
  // Use summary first as it has clean, resolved data
  if (d.summary) {
    return d.summary
  }
  
  // Fallback to promotion data if summary is not available
  if (d.promotion) {
    const p = d.promotion
    return {
      employee_name: p.employee_name || '—',
      employee_no: p.employee_no || '—',
      nature: p.nature_name || p.nature || '—',
      position: p.position_name || p.position || '—',
      department: p.department_name || p.department || '—',
      branch: p.branch_name || p.branch || '—',
      employment_type: p.employment_type_name || p.employment_type || '—',
      date_of_effectivity: p.date_of_effectivity || '—',
      date_position_appointed: p.date_position_appointed || '—',
      is_plantilla: p.is_plantilla || false,
      old_salary: p.old_salary || 0,
      new_salary: p.new_salary || p.salary || 0,
      old_tax_amount: p.old_tax_amount || 0,
      old_gsis_amount: p.old_gsis_amount || 0,
      old_sss_amount: p.old_sss_amount || 0,
      old_pagibig_amount: p.old_pagibig_amount || 0,
      old_philhealth_amount: p.old_philhealth_amount || 0,
      new_tax_amount: p.new_tax_amount || 0,
      new_gsis_amount: p.new_gsis_amount || 0,
      new_sss_amount: p.new_sss_amount || 0,
      new_pagibig_amount: p.new_pagibig_amount || 0,
      new_philhealth_amount: p.new_philhealth_amount || 0
    }
  }
  
  return null
})

const salaryIncrease = computed(() => {
  if (!summary.value) return 0
  return Number(summary.value.new_salary || 0) - Number(summary.value.old_salary || 0)
})

// Deduction calculations
const oldTotalDeduction = computed(() => {
  if (!summary.value) return 0
  const tax = Number(summary.value.old_tax_amount) || 0
  const gsis = Number(summary.value.old_gsis_amount) || 0
  const sss = Number(summary.value.old_sss_amount) || 0
  const pagibig = Number(summary.value.old_pagibig_amount) || 0
  const philhealth = Number(summary.value.old_philhealth_amount) || 0
  return tax + gsis + sss + pagibig + philhealth
})

const newTotalDeduction = computed(() => {
  if (!summary.value) return 0
  const tax = Number(summary.value.new_tax_amount) || 0
  const gsis = Number(summary.value.new_gsis_amount) || 0
  const sss = Number(summary.value.new_sss_amount) || 0
  const pagibig = Number(summary.value.new_pagibig_amount) || 0
  const philhealth = Number(summary.value.new_philhealth_amount) || 0
  return tax + gsis + sss + pagibig + philhealth
})

const oldNetPay = computed(() => {
  if (!summary.value) return 0
  const salary = Number(summary.value.old_salary) || 0
  return salary - oldTotalDeduction.value
})

const newNetPay = computed(() => {
  if (!summary.value) return 0
  const salary = Number(summary.value.new_salary) || 0
  return salary - newTotalDeduction.value
})

const currency = (val) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(val || 0))
const avatarUrl = 'https://cube.elemecdn.com/3/7c/3ed689499777db3d2947606ee76bcpng.png'

const { fetchForm } = useAssignment()

const loadDetail = async () => {
  if (!visible.value || !props.id) {
    return
  }
  loading.value = true
  try {
    // Load both the assignment detail and form data for lookups
    const [data, _] = await Promise.all([
      showAssignment(props.id),
      fetchForm(0) // Load form data for lookups
    ])
    
    // If we have promotion data but missing deduction amounts, try to get them from current employee data
    if (data && (data.summary || data.promotion)) {
      const promotion = data.promotion || data.summary
      const hasDeductions = promotion.old_gsis_amount || promotion.old_tax_amount || promotion.old_sss_amount || 
                           promotion.old_pagibig_amount || promotion.old_philhealth_amount
      
      if (!hasDeductions && promotion.employee_id) {
        try {
          // Import employeeApi here to avoid circular imports
          const { employeeApi } = await import('@/services/api')
          const empRes = await employeeApi.getEmployee(promotion.employee_id)
          const empData = empRes.data?.employee_info?.[0] || empRes.data?.data?.employee || empRes.data?.data || empRes.data
          
          if (empData) {
            // Update the summary with current employee deduction data
            if (data.summary) {
              data.summary.old_tax_amount = empData.tax_amount || 0
              data.summary.old_gsis_amount = empData.gsis_amount || 0
              data.summary.old_sss_amount = empData.sss_amount || 0
              data.summary.old_pagibig_amount = empData.pagibig_amount || 0
              data.summary.old_philhealth_amount = empData.philhealth_amount || 0
            }
            // Also update promotion object
            if (data.promotion) {
              data.promotion.old_tax_amount = empData.tax_amount || 0
              data.promotion.old_gsis_amount = empData.gsis_amount || 0
              data.promotion.old_sss_amount = empData.sss_amount || 0
              data.promotion.old_pagibig_amount = empData.pagibig_amount || 0
              data.promotion.old_philhealth_amount = empData.philhealth_amount || 0
            }
          }
        } catch (e) {
          // Could not fetch employee deduction data, continue with existing data
        }
      }
    }
    
    detail.value = data || null
  } catch (e) {
    detail.value = null
  } finally {
    loading.value = false
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
.info-item .strong{font-weight:700;color:#111827}
.chips{display:flex;flex-wrap:wrap;gap:8px}
.mb-12{margin-bottom:12px}
.positive{color:#16a34a;font-weight:600}
.negative{color:#dc2626;font-weight:600}
.salary-section{margin-bottom:20px}
.section-title{font-size:14px;font-weight:600;color:#374151;margin-bottom:12px;border-bottom:1px solid #e5e7eb;padding-bottom:6px}
.mt-4{margin-top:16px}
</style>