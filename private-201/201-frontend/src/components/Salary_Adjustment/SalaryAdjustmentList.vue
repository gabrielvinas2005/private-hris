<template>
  <div class="salary-adjustment-list">
    <!-- Metrics Cards -->
    <el-row :gutter="16" class="mb-6">
      <el-col :span="6">
        <el-card shadow="hover" class="metric-card">
          <el-statistic title="Available Schedules" :value="salarySchedules.length" />
          <template #suffix>
            <el-icon class="metric-icon schedules"><Calendar /></el-icon>
          </template>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover" class="metric-card">
          <el-statistic title="Active Schedules" :value="activeSchedulesCount" />
          <template #suffix>
            <el-icon class="metric-icon active"><CircleCheck /></el-icon>
          </template>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover" class="metric-card">
          <el-statistic title="Last Processed" :value="lastProcessedDate" />
          <template #suffix>
            <el-icon class="metric-icon last"><Clock /></el-icon>
          </template>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover" class="metric-card">
          <el-statistic title="Total Employees" :value="totalEmployees" />
          <template #suffix>
            <el-icon class="metric-icon employees"><User /></el-icon>
          </template>
        </el-card>
      </el-col>
    </el-row>

    <!-- Process Salary Adjustment -->
    <el-card shadow="never" class="mb-4">
      <template #header>
        <div class="flex justify-between items-center">
          <span class="font-medium">Process Salary Adjustment</span>
          <el-tag type="info">Bulk Processing</el-tag>
        </div>
      </template>
      
      <el-alert
        title="Salary Adjustment Processing"
        type="warning"
        :closable="false"
        class="mb-4"
      >
        <template #default>
          <p><strong>Important:</strong> This will process salary adjustments for all active plantilla employees.</p>
          <ul class="mt-2 ml-4">
            <li>Updates employee salaries based on the selected salary schedule</li>
            <li>Recalculates deductions (GSIS, PhilHealth, Pag-IBIG, Tax)</li>
            <li>Creates salary adjustment records for tracking</li>
            <li>Cannot be undone - please review carefully before processing</li>
          </ul>
        </template>
      </el-alert>

      <el-form :model="processForm" label-position="top">
        <el-row :gutter="16">
          <el-col :span="16">
            <el-form-item label="Select Salary Schedule" required>
              <el-select
                v-model="processForm.salary_schedule_id"
                placeholder="Choose salary schedule to apply"
                style="width: 100%"
                size="large"
                :loading="loading"
                @change="onScheduleSelect"
              >
                <el-option
                  v-for="schedule in salarySchedules"
                  :key="schedule.id"
                  :label="`${schedule.name} (${formatDate(schedule.effectivity)})`"
                  :value="schedule.id"
                >
                  <div>
                    <div class="font-medium">{{ schedule.name }}</div>
                    <div class="text-sm text-gray-500">
                      Effective: {{ formatDate(schedule.effectivity) }}
                    </div>
                  </div>
                </el-option>
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="8">
            <el-form-item label=" " class="pt-6">
              <el-button 
                type="primary" 
                size="large"
                :icon="Setting"
                :disabled="!processForm.salary_schedule_id"
                :loading="processing"
                @click="onProcessSalaryAdjustment"
                style="width: 100%"
              >
                {{ processing ? 'Processing...' : 'Process Salary Adjustment' }}
              </el-button>
            </el-form-item>
          </el-col>
        </el-row>
      </el-form>
    </el-card>

    <!-- Salary Schedules List -->
    <el-card shadow="never">
      <template #header>
        <div class="flex justify-between items-center">
          <span class="font-medium">Available Salary Schedules</span>
          <div class="action-buttons">
            <el-button :icon="Refresh" @click="onRefresh" :loading="loading">
              Refresh
            </el-button>
          </div>
        </div>
      </template>

      <el-table 
        v-loading="loading"
        :data="filteredSchedules" 
        border 
        stripe
        :height="tableHeight"
      >
        <el-table-column prop="name" label="Schedule Name" min-width="200">
          <template #default="{ row }">
            <div class="font-medium">{{ row.name }}</div>
          </template>
        </el-table-column>

        <el-table-column prop="effectivity" label="Effectivity Date" width="150">
          <template #default="{ row }">
            {{ formatDate(row.effectivity) }}
          </template>
        </el-table-column>

        <el-table-column prop="active" label="Status" width="100">
          <template #default="{ row }">
            <el-tag :type="row.active ? 'success' : 'info'" size="small">
              {{ row.active ? 'Active' : 'Inactive' }}
            </el-tag>
          </template>
        </el-table-column>

        <el-table-column prop="created_at" label="Created Date" width="150">
          <template #default="{ row }">
            {{ formatDate(row.created_at) }}
          </template>
        </el-table-column>

        <el-table-column label="Actions" width="150" fixed="right">
          <template #default="{ row }">
            <div class="action-buttons">
              <el-button 
                type="primary" 
                size="small" 
                :icon="View"
                @click="onViewSchedule(row)"
                title="View Details"
              />
              <el-button 
                type="success" 
                size="small" 
                :icon="Setting"
                @click="onProcessSingle(row)"
                :disabled="!row.active"
                title="Process This Schedule"
              />
            </div>
          </template>
        </el-table-column>
      </el-table>

      <!-- Empty State -->
      <el-empty 
        v-if="!loading && filteredSchedules.length === 0"
        description="No salary schedules available"
      >
        <el-button type="primary" @click="onRefresh">Refresh</el-button>
      </el-empty>
    </el-card>

    <!-- Processing Progress Tracker Modal -->
    <el-dialog
      v-model="showProgressDialog"
      title="Processing Salary Adjustment"
      width="60%"
      :close-on-click-modal="false"
      :close-on-press-escape="false"
      :show-close="false"
    >
      <div class="progress-tracker">
        <div class="progress-header mb-4">
          <h3 class="text-lg font-semibold mb-2">Processing in progress...</h3>
          <p class="text-gray-600">Please wait while we process salary adjustments for all employees.</p>
        </div>

        <!-- Progress Steps -->
        <el-steps :active="currentStep" finish-status="success" align-center class="mb-6">
          <el-step title="Initializing" :description="stepDescriptions[0]" />
          <el-step title="Fetching Employees" :description="stepDescriptions[1]" />
          <el-step title="Processing Salaries" :description="stepDescriptions[2]" />
          <el-step title="Calculating Deductions" :description="stepDescriptions[3]" />
          <el-step title="Saving Adjustments" :description="stepDescriptions[4]" />
          <el-step title="Finalizing" :description="stepDescriptions[5]" />
        </el-steps>

        <!-- Progress Bar -->
        <div class="progress-bar-container mb-4">
          <div class="flex justify-between mb-2">
            <span class="text-sm font-medium">Overall Progress</span>
            <span class="text-sm text-gray-600">{{ Math.round((currentStep / 5) * 100) }}%</span>
          </div>
          <el-progress 
            :percentage="Math.round((currentStep / 5) * 100)" 
            :status="currentStep === 5 ? 'success' : ''"
            :stroke-width="20"
          />
        </div>

        <!-- Current Status -->
        <div class="current-status">
          <el-alert
            :title="currentStatusMessage"
            :type="currentStep === 5 ? 'success' : 'info'"
            :closable="false"
            show-icon
          />
        </div>

        <!-- Processing Stats (if available) -->
        <div v-if="processingStats.total > 0" class="processing-stats mt-4">
          <el-row :gutter="16">
            <el-col :span="8">
              <el-statistic title="Total Employees" :value="processingStats.total" />
            </el-col>
            <el-col :span="8">
              <el-statistic title="Processed" :value="processingStats.processed" />
            </el-col>
            <el-col :span="8">
              <el-statistic title="Remaining" :value="processingStats.total - processingStats.processed" />
            </el-col>
          </el-row>
        </div>
      </div>

      <template #footer>
        <el-button 
          v-if="currentStep < 5" 
          disabled
          :loading="true"
        >
          Processing...
        </el-button>
        <el-button 
          v-else
          type="primary"
          @click="closeProgressDialog"
        >
          View Results
        </el-button>
      </template>
    </el-dialog>

    <!-- Processing Result Dialog -->
    <el-dialog
      v-model="showResultDialog"
      title="Processing Results"
      width="50%"
      :close-on-click-modal="false"
    >
      <div v-if="processingResult">
        <el-alert
          :title="`Successfully processed ${processingResult.processed_count} out of ${processingResult.total_employees} employees`"
          type="success"
          :closable="false"
          class="mb-4"
        />
        
        <div v-if="processingResult.errors && processingResult.errors.length > 0">
          <h4 class="mb-2">Processing Errors:</h4>
          <el-alert
            v-for="(error, index) in processingResult.errors"
            :key="index"
            :title="error"
            type="warning"
            :closable="false"
            class="mb-2"
          />
        </div>
      </div>
      
      <template #footer>
        <el-button @click="showResultDialog = false">Close</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { 
  Calendar, CircleCheck, Clock, User, Setting, Refresh, View
} from '@element-plus/icons-vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { useSalaryAdjustment } from '@/composable/useSalaryAdjustment'

const props = defineProps({
  salarySchedules: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  lastProcessedDate: { type: String, default: null }
})

const emit = defineEmits(['refresh', 'process-adjustment', 'view-schedule'])

// Reactive data
const processing = ref(false)
const showResultDialog = ref(false)
const showProgressDialog = ref(false)
const processingResult = ref(null)
const tableHeight = ref('400px')

// Progress tracking
const currentStep = ref(0)
const stepDescriptions = ref([
  'Preparing to process...',
  'Loading employee data...',
  'Applying salary adjustments...',
  'Computing deductions (GSIS, PhilHealth, Tax)...',
  'Saving changes to database...',
  'Completing process...'
])
const currentStatusMessage = ref('Initializing salary adjustment process...')
const processingStats = ref({
  total: 0,
  processed: 0
})

const processForm = ref({
  salary_schedule_id: null
})

// Computed properties
const filteredSchedules = computed(() => {
  return props.salarySchedules || []
})

const activeSchedulesCount = computed(() => {
  return filteredSchedules.value.filter(schedule => schedule.active).length
})

const selectedSchedule = ref(null)
const scheduleMetrics = ref(null)

const lastProcessedDate = computed(() => {
  // Priority: selected schedule's last processed date > global last processed date > fallback
  if (scheduleMetrics.value?.last_processed_date) {
    return formatDate(scheduleMetrics.value.last_processed_date)
  }
  if (props.lastProcessedDate) {
    return formatDate(props.lastProcessedDate)
  }
  if (filteredSchedules.value.length === 0) return 'N/A'
  
  // Fallback to schedule created date if no processing date available
  const sortedSchedules = [...filteredSchedules.value].sort((a, b) => 
    new Date(b.created_at) - new Date(a.created_at)
  )
  
  return formatDate(sortedSchedules[0]?.created_at)
})

const totalEmployees = computed(() => {
  if (scheduleMetrics.value?.total_employees) {
    return scheduleMetrics.value.total_employees
  }
  return '---'
})

// Methods
const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const onRefresh = () => {
  emit('refresh')
}

const { fetchAffectedEmployees, processSalaryAdjustment } = useSalaryAdjustment()

const onScheduleSelect = async (scheduleId) => {
  if (scheduleId) {
    try {
      const data = await fetchAffectedEmployees(scheduleId)
      scheduleMetrics.value = {
        total_employees: data.total_employees || 0,
        last_processed_date: data.last_processed_date,
        total_processed: data.total_processed || 0
      }
      selectedSchedule.value = props.salarySchedules.find(s => s.id === scheduleId)
    } catch (error) {
      console.error('Failed to load schedule metrics:', error)
      scheduleMetrics.value = null
    }
  } else {
    scheduleMetrics.value = null
    selectedSchedule.value = null
  }
}

const onViewSchedule = (schedule) => {
  emit('view-schedule', schedule)
}

const onProcessSingle = async (schedule) => {
  processForm.value.salary_schedule_id = schedule.id
  await onProcessSalaryAdjustment()
}

// Progress tracking functions
const updateProgressStep = (step, message) => {
  currentStep.value = step
  currentStatusMessage.value = message || stepDescriptions.value[step]
}

const resetProgress = () => {
  currentStep.value = 0
  currentStatusMessage.value = 'Initializing salary adjustment process...'
  processingStats.value = { total: 0, processed: 0 }
}

const simulateProgress = async (minDuration = 3000) => {
  const startTime = Date.now()
  
  // Step 0: Initializing
  updateProgressStep(0, 'Preparing to process salary adjustments...')
  await new Promise(resolve => setTimeout(resolve, 300))
  
  // Step 1: Fetching Employees
  updateProgressStep(1, 'Fetching active plantilla employees...')
  await new Promise(resolve => setTimeout(resolve, 500))
  
  // Step 2: Processing Salaries
  updateProgressStep(2, 'Applying salary adjustments based on schedule...')
  await new Promise(resolve => setTimeout(resolve, 800))
  
  // Step 3: Calculating Deductions
  updateProgressStep(3, 'Calculating GSIS, PhilHealth, Pag-IBIG, and Tax deductions...')
  await new Promise(resolve => setTimeout(resolve, 1000))
  
  // Step 4: Saving Adjustments
  updateProgressStep(4, 'Saving salary adjustments to database...')
  await new Promise(resolve => setTimeout(resolve, 800))
  
  // Step 5: Finalizing
  updateProgressStep(5, 'Processing completed successfully!')
  
  // Ensure minimum duration is met for better UX
  const elapsed = Date.now() - startTime
  if (elapsed < minDuration) {
    await new Promise(resolve => setTimeout(resolve, minDuration - elapsed))
  }
  
  await new Promise(resolve => setTimeout(resolve, 500))
}

const closeProgressDialog = () => {
  showProgressDialog.value = false
  resetProgress()
  // Show result dialog
  if (processingResult.value) {
    showResultDialog.value = true
  }
}

const onProcessSalaryAdjustment = async () => {
  if (!processForm.value.salary_schedule_id) {
    ElMessage.warning('Please select a salary schedule first')
    return
  }

  try {
    const selectedSchedule = filteredSchedules.value.find(
      s => s.id === processForm.value.salary_schedule_id
    )
    
    await ElMessageBox.confirm(
      `Are you sure you want to process salary adjustment using "${selectedSchedule?.name}"? This action cannot be undone and will affect all active plantilla employees.`,
      'Confirm Salary Adjustment Processing',
      {
        confirmButtonText: 'Process',
        cancelButtonText: 'Cancel',
        type: 'warning'
      }
    )
    
    // Reset and show progress dialog
    resetProgress()
    showProgressDialog.value = true
    processing.value = true
    
    // Get total employees count for stats (if available)
    if (scheduleMetrics.value?.total_employees) {
      processingStats.value.total = scheduleMetrics.value.total_employees
    }
    
    try {
      // Start both processing and progress simulation in parallel
      const processingPromise = processSalaryAdjustment(processForm.value.salary_schedule_id, false)
      const progressPromise = simulateProgress(4000) // Minimum 4 seconds for better UX
      
      // Wait for actual processing to complete first
      const result = await processingPromise
      
      // Update stats with actual result
      if (result) {
        processingStats.value.total = result.total_employees || processingStats.value.total
        processingStats.value.processed = result.processed_count || 0
      }
      
      // Wait for progress simulation to complete (ensures progress tracker finishes)
      await progressPromise
      
      // Store result for result dialog
      processingResult.value = result
      
      // Show success message after progress completes
      const message = result?.message || 'Salary adjustment processed successfully'
      ElMessage.success(`${message}. Processed: ${result.processed_count || 0}/${result.total_employees || 0} employees`)
      
      // Show error warnings if any
      if (result.errors && result.errors.length > 0) {
        result.errors.forEach(error => {
          ElMessage.warning(error)
        })
      }
      
      // Small delay before showing completion
      await new Promise(resolve => setTimeout(resolve, 1000))
      
      // Emit refresh event for parent to reload schedules
      emit('refresh')
      
    } catch (error) {
      // If processing fails, update progress to show error
      currentStatusMessage.value = `Processing failed: ${error.response?.data?.message || error.message || 'Unknown error'}`
      // Show error message
      ElMessage.error(error.response?.data?.message || error.message || 'Processing failed')
      // Wait a bit to show the error message
      await new Promise(resolve => setTimeout(resolve, 2000))
      throw error
    } finally {
      processing.value = false
    }
    
    // Clear form
    processForm.value.salary_schedule_id = null
    
  } catch (error) {
    if (error !== 'cancel') {
      console.error('Processing failed:', error)
      // Close progress dialog on error after showing error message
      setTimeout(() => {
        showProgressDialog.value = false
        resetProgress()
      }, 2000)
    } else {
      // User cancelled - close progress dialog immediately
      showProgressDialog.value = false
      resetProgress()
    }
  }
}
</script>

<style scoped>
.salary-adjustment-list {
  padding: 0;
}

.metric-card {
  text-align: center;
}

.metric-card .el-statistic__content {
  font-size: 1.5rem;
  font-weight: bold;
}

.metric-icon {
  font-size: 1.5rem;
  margin-left: 8px;
}

.metric-icon.schedules { color: #3498db; }
.metric-icon.active { color: #27ae60; }
.metric-icon.last { color: #f39c12; }
.metric-icon.employees { color: #9b59b6; }

.action-buttons {
  display: flex;
  gap: 4px;
  justify-content: center;
}

.action-buttons .el-button {
  margin-left: 0;
}

.el-alert ul {
  list-style-type: disc;
  padding-left: 1rem;
}

.el-alert li {
  margin-bottom: 0.25rem;
}

.progress-tracker {
  padding: 20px 0;
}

.progress-header {
  text-align: center;
}

.progress-bar-container {
  margin: 20px 0;
}

.current-status {
  margin: 20px 0;
}

.processing-stats {
  padding: 20px;
  background: #f5f7fa;
  border-radius: 8px;
}

</style>
