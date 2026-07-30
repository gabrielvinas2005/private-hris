<template>
  <el-drawer
    v-model="visible"
    :title="title"
    size="50%"
    direction="rtl"
    :close-on-click-modal="false"
  >
    <div v-loading="loading" class="schedule-details">
      <div v-if="schedule">
        <!-- Schedule Information -->
        <el-card shadow="never" class="mb-4">
          <template #header>
            <div class="flex items-center">
              <el-icon class="mr-2"><Calendar /></el-icon>
              <span>Schedule Information</span>
            </div>
          </template>
          
          <div class="detail-grid">
            <div class="detail-item">
              <label class="detail-label">Schedule Name:</label>
              <span class="detail-value">{{ schedule.name }}</span>
            </div>
            
            <div class="detail-item">
              <label class="detail-label">Effectivity Date:</label>
              <span class="detail-value">{{ formatDate(schedule.effectivity) }}</span>
            </div>
            
            <div class="detail-item">
              <label class="detail-label">Status:</label>
              <el-tag :type="schedule.active ? 'success' : 'info'" size="small">
                {{ schedule.active ? 'Active' : 'Inactive' }}
              </el-tag>
            </div>
            
            <div class="detail-item">
              <label class="detail-label">Created Date:</label>
              <span class="detail-value">{{ formatDate(schedule.created_at) }}</span>
            </div>
          </div>
        </el-card>

        <!-- Processing Information -->
        <el-card shadow="never" class="mb-4">
          <template #header>
            <div class="flex items-center">
              <el-icon class="mr-2"><Setting /></el-icon>
              <span>Processing Information</span>
            </div>
          </template>
          
          <el-alert
            title="What happens when you process this schedule?"
            type="info"
            :closable="false"
            class="mb-4"
          >
            <template #default>
              <ul class="processing-info">
                <li>All active plantilla employees will be affected</li>
                <li>Employee salaries will be updated based on their current grade and step</li>
                <li>Deductions will be recalculated (GSIS, PhilHealth, Pag-IBIG, Tax)</li>
                <li>Salary adjustment records will be created for tracking</li>
                <li>Service records will be updated with new salary information</li>
              </ul>
            </template>
          </el-alert>

          <div class="processing-stats">
            <el-row :gutter="16">
              <el-col :span="8">
                <el-statistic title="Estimated Affected Employees" :value="affectedEmployeesCount" />
              </el-col>
              <el-col :span="8">
                <el-statistic title="Last Processing" :value="lastProcessedDate" />
              </el-col>
              <el-col :span="8">
                <el-statistic title="Processing Status" :value="processingStatus" />
              </el-col>
            </el-row>
          </div>
        </el-card>

        <!-- Affected Employees List -->
        <el-card shadow="never" class="mb-4" v-if="affectedEmployees.length > 0">
          <template #header>
            <div class="flex items-center">
              <el-icon class="mr-2"><User /></el-icon>
              <span>Affected Employees ({{ affectedEmployees.length }})</span>
            </div>
          </template>
          
          <el-table 
            :data="affectedEmployees" 
            border 
            stripe
            max-height="400"
            v-loading="loadingEmployees"
          >
            <el-table-column type="index" label="#" width="50" />
            <el-table-column prop="employee_no" label="Employee No." width="120" />
            <el-table-column prop="name" label="Employee Name" min-width="200" />
            <el-table-column prop="department" label="Department" min-width="150" />
            <el-table-column prop="position" label="Position" min-width="150" />
            <el-table-column prop="salary_grade" label="Grade" width="100" />
            <el-table-column prop="salary_step" label="Step" width="100" />
            <el-table-column prop="current_salary" label="Current Salary" width="130" align="right">
              <template #default="{ row }">
                ₱{{ formatCurrency(row.current_salary) }}
              </template>
            </el-table-column>
            <el-table-column prop="new_salary" label="New Salary" width="130" align="right">
              <template #default="{ row }">
                <span class="text-success font-medium">₱{{ formatCurrency(row.new_salary) }}</span>
              </template>
            </el-table-column>
          </el-table>
        </el-card>

        <!-- Prerequisites Check -->
        <el-card shadow="never" class="mb-6">
          <template #header>
            <div class="flex items-center">
              <el-icon class="mr-2"><CircleCheck /></el-icon>
              <span>Prerequisites Check</span>
            </div>
          </template>
          
          <div class="prerequisites">
            <div class="prerequisite-item">
              <el-icon class="prerequisite-icon success"><CircleCheck /></el-icon>
              <span>Salary schedule is active</span>
            </div>
            
            <div class="prerequisite-item">
              <el-icon class="prerequisite-icon success"><CircleCheck /></el-icon>
              <span>GSIS multipliers configured</span>
            </div>
            
            <div class="prerequisite-item">
              <el-icon class="prerequisite-icon success"><CircleCheck /></el-icon>
              <span>PhilHealth rates configured</span>
            </div>
            
            <div class="prerequisite-item">
              <el-icon class="prerequisite-icon success"><CircleCheck /></el-icon>
              <span>Tax tables configured</span>
            </div>
            
            <div class="prerequisite-item">
              <el-icon class="prerequisite-icon success"><CircleCheck /></el-icon>
              <span>Active employees found</span>
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
          type="primary" 
          :icon="Setting" 
          @click="onProcessSchedule"
          :loading="loading"
          :disabled="!schedule?.active"
        >
          Process This Schedule
        </el-button>
      </div>
    </template>
  </el-drawer>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { 
  Calendar, Setting, CircleCheck, User
} from '@element-plus/icons-vue'
import { ElMessageBox } from 'element-plus'
import { useSalaryAdjustment } from '@/composable/useSalaryAdjustment'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  schedule: { type: Object, default: null },
  loading: { type: Boolean, default: false }
})

const emit = defineEmits(['update:modelValue', 'process-schedule'])

const { fetchAffectedEmployees } = useSalaryAdjustment()
const affectedEmployees = ref([])
const loadingEmployees = ref(false)
const scheduleData = ref(null)

// Computed properties
const visible = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

const title = computed(() => {
  return props.schedule?.name 
    ? `Schedule Details - ${props.schedule.name}`
    : 'Schedule Details'
})

const affectedEmployeesCount = computed(() => {
  if (scheduleData.value?.total_employees) {
    return scheduleData.value.total_employees
  }
  return affectedEmployees.value.length || '---'
})

const lastProcessedDate = computed(() => {
  if (scheduleData.value?.last_processed_date) {
    return formatDate(scheduleData.value.last_processed_date)
  }
  return 'Never'
})

const processingStatus = computed(() => {
  if (scheduleData.value?.total_processed && scheduleData.value.total_processed > 0) {
    return 'Processed'
  }
  return 'Ready'
})

// Watch for schedule changes to load affected employees
watch(() => props.schedule, async (newSchedule) => {
  if (newSchedule && newSchedule.id) {
    await loadAffectedEmployees(newSchedule.id)
  } else {
    affectedEmployees.value = []
    scheduleData.value = null
  }
}, { immediate: true })

// Methods
const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const formatCurrency = (amount) => {
  if (!amount) return '0.00'
  return Number(amount).toLocaleString('en-US', { 
    minimumFractionDigits: 2, 
    maximumFractionDigits: 2 
  })
}

const loadAffectedEmployees = async (scheduleId) => {
  try {
    loadingEmployees.value = true
    const data = await fetchAffectedEmployees(scheduleId)
    affectedEmployees.value = data.employees || []
    scheduleData.value = {
      total_employees: data.total_employees || 0,
      last_processed_date: data.last_processed_date,
      total_processed: data.total_processed || 0
    }
  } catch (error) {
    console.error('Failed to load affected employees:', error)
    affectedEmployees.value = []
    scheduleData.value = null
  } finally {
    loadingEmployees.value = false
  }
}

const onProcessSchedule = async () => {
  try {
    await ElMessageBox.confirm(
      `Are you sure you want to process salary adjustment using "${props.schedule.name}"? This action cannot be undone and will affect all active plantilla employees.`,
      'Confirm Processing',
      {
        confirmButtonText: 'Process',
        cancelButtonText: 'Cancel',
        type: 'warning'
      }
    )
    emit('process-schedule', props.schedule)
    visible.value = false
  } catch {
    // User cancelled
  }
}
</script>

<style scoped>
.schedule-details {
  padding: 0 16px;
}

.detail-grid {
  display: grid;
  gap: 16px;
}

.detail-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 0;
  border-bottom: 1px solid #f0f0f0;
}

.detail-item:last-child {
  border-bottom: none;
}

.detail-label {
  font-weight: 500;
  color: #606266;
  min-width: 140px;
}

.detail-value {
  color: #303133;
  font-weight: 500;
}

.processing-info {
  list-style-type: disc;
  padding-left: 1rem;
  margin: 0;
}

.processing-info li {
  margin-bottom: 0.5rem;
  color: #606266;
}

.processing-stats {
  margin-top: 16px;
  padding: 16px;
  background: #f8f9fa;
  border-radius: 8px;
}

.prerequisites {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.prerequisite-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 0;
}

.prerequisite-icon {
  font-size: 1.2rem;
}

.prerequisite-icon.success {
  color: #67c23a;
}

.prerequisite-icon.warning {
  color: #e6a23c;
}

.prerequisite-icon.error {
  color: #f56c6c;
}

.drawer-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  padding: 16px;
  border-top: 1px solid #e4e7ed;
}

.text-success {
  color: #67c23a;
  font-weight: 500;
}
</style>
