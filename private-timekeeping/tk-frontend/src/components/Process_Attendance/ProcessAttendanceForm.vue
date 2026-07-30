<template>
  <div>
    <div class="compact-form">
      <el-form :model="form" class="inline-form">
        <div class="form-row">
           <div class="form-item">
             <label class="form-label">Payroll Interval</label>
            <el-select v-model.number="form.payroll_interval_id" placeholder="Select interval" style="width: 200px" @change="onIntervalChange">
              <el-option v-for="it in intervals" :key="it.id" :label="it.name" :value="Number(it.id)" />
             </el-select>
           </div>

           <div class="form-item">
             <label class="form-label">Payroll Period</label>
            <el-select v-model.number="form.payroll_period_id" placeholder="Select period" style="width: 300px" :disabled="!form.payroll_interval_id" @change="onPeriodChange">
              <el-option v-for="p in periods" :key="p.id" :label="formatPeriodLabel(p)" :value="Number(p.id)" />
             </el-select>
           </div>

           <div class="form-item">
             <label class="form-label">Preceding Payroll Period</label>
            <el-select v-model.number="form.preceding_payroll_period_id" placeholder="Select preceding period (optional)" style="width: 300px" :disabled="!form.payroll_interval_id" @change="onPrecedingPeriodChange" clearable>
              <el-option v-for="p in filteredPrecedingPeriods" :key="p.id" :label="formatPeriodLabel(p)" :value="Number(p.id)" />
             </el-select>
           </div>

           <!-- Optional: simulate a different "today" for processing only (cutoff, assumed records). Empty = server uses real calendar today (Asia/Manila). -->
           <!-- Restore dev-only: add v-if="isDevMode" on the div below -->
           <div class="form-item dev-date-override-item">
             <label class="form-label">Date To Process</label>
            <div class="dev-date-picker-row">
              <div class="dev-date-picker-wrap">
                <el-date-picker
                  ref="devDatePickerRef"
                  v-model="devDate"
                  type="date"
                  :placeholder="calendarTodayManila"
                  clearable
                  style="width: 200px"
                  format="YYYY-MM-DD"
                  value-format="YYYY-MM-DD"
                  @change="onDevDateChange"
                />
                <div
                  v-if="!dateOverrideGatePassed"
                  class="dev-date-picker-guard"
                  role="button"
                  tabindex="0"
                  aria-label="Date to process — show confirmation"
                  @click.stop="openDateOverrideWarning"
                  @keydown.enter.prevent="openDateOverrideWarning"
                  @keydown.space.prevent="openDateOverrideWarning"
                />
              </div>
            </div>
           </div>

          <div class="form-item">
            <label class="form-label">&nbsp;</label>
            <div class="process-attendance-actions">
              <!-- Process / View dropdown -->
              <el-dropdown trigger="click" :disabled="!canProcess" @command="onProcessCommand">
                <el-button type="primary" :loading="loading || viewLoading">
                  Process Attendance
                  <el-icon class="el-icon--right"><ArrowDown /></el-icon>
                </el-button>
                <template #dropdown>
                  <el-dropdown-menu>
                    <el-dropdown-item command="process">Process Attendance</el-dropdown-item>
                    <el-dropdown-item command="view-processed">View Processed Attendance</el-dropdown-item>
                  </el-dropdown-menu>
                </template>
              </el-dropdown>
                <el-dropdown trigger="click" :disabled="!canProcess" @command="onVlOffsetCommand">
                  <el-button type="warning" :loading="vlOffsetLoading">
                    Auto-Deduct Tardiness to VL
                    <el-icon class="el-icon--right"><ArrowDown /></el-icon>
                  </el-button>
                  <template #dropdown>
                    <el-dropdown-menu>
                      <el-dropdown-item command="auto-deduct">Auto-Deduct Tardiness to VL</el-dropdown-item>
                      <el-dropdown-item command="cancel-all">Cancel all Offsets</el-dropdown-item>
                      <el-dropdown-item command="view-status">View Offsetted Employees and Employees with no Offsets yet</el-dropdown-item>
                    </el-dropdown-menu>
                  </template>
                </el-dropdown>
            </div>  
            <!-- Debug (temporary): show current selection so we can see why button may be disabled -->
            <small v-if="false" style="margin-left:8px;color:#909399;">
              interval: {{ form.payroll_interval_id }} | period: {{ form.payroll_period_id }} | canProcess: {{ canProcess }}
            </small>
            <slot name="extra-actions" />
          </div>
        </div>
      </el-form>
    </div>
    
    <el-divider />
    
    <!-- Processing Progress Dialog -->
    <el-dialog
      v-model="showProgressDialog"
      :title="progressDialogTitle"
      width="640px"
      :close-on-click-modal="false"
      :close-on-press-escape="false"
      :show-close="false"
      align-center
      class="processing-dialog"
    >
      <div class="progress-content">
        <div class="progress-header">
          <el-icon :class="['processing-icon', { 'is-loading': !progressComplete && !progressError }]">
            <Check v-if="progressComplete && !progressError" />
            <Close v-else-if="progressError" />
            <Loading v-else />
          </el-icon>
          <div class="progress-header-text">
            <h3>{{ progressHeaderTitle }}</h3>
            <p v-if="progressSubtitle" class="progress-subtitle">{{ progressSubtitle }}</p>
            <p v-if="progressStepCounter" class="progress-step-counter">{{ progressStepCounter }}</p>
          </div>
        </div>
        
        <div class="progress-steps">
          <div 
            v-for="(step, index) in progressSteps" 
            :key="index"
            class="progress-step"
            :class="{
              'active': step.status === 'active',
              'completed': step.status === 'completed',
              'error': step.status === 'error',
              'skipped': step.status === 'skipped'
            }"
          >
            <div class="step-indicator">
              <el-icon v-if="step.status === 'completed'"><Check /></el-icon>
              <el-icon v-else-if="step.status === 'error'"><Close /></el-icon>
              <el-icon v-else-if="step.status === 'active'"><Loading /></el-icon>
              <el-icon v-else-if="step.status === 'skipped'" class="step-skipped-icon"><CircleCheck /></el-icon>
              <span v-else class="step-number">{{ index + 1 }}</span>
            </div>
            <div class="step-content">
              <div class="step-title">{{ step.title }}</div>
              <div v-if="step.message" class="step-message">{{ step.message }}</div>
              <div
                v-if="index === STEP_INDEX.VALIDATE_AND_CALCULATE && step.status === 'active' && !progressComplete && !progressError"
                class="step-progress-wrap"
              >
                <el-progress
                  :percentage="validateCalcProgressPercent"
                  :indeterminate="validateCalcProgressIndeterminate"
                  :stroke-width="10"
                  striped
                  striped-flow
                />
              </div>
              <div
                v-if="needsReprocess && index === STEP_INDEX.REPROCESS && step.status === 'active' && !progressComplete && !progressError"
                class="step-progress-wrap"
              >
                <el-progress
                  :percentage="reprocessProgressPercent"
                  :indeterminate="reprocessProgressIndeterminate"
                  :stroke-width="10"
                  striped
                  striped-flow
                />
              </div>
            </div>
          </div>
        </div>
        
        <div v-if="progressError" class="error-message">
          <el-alert type="error" :closable="false" show-icon>
            {{ progressError }}
          </el-alert>
        </div>
      </div>
      
      <template #footer>
        <div class="dialog-footer">
          <el-button 
            v-if="progressComplete || progressError"
            type="primary" 
            @click="closeProgressDialog"
          >
            {{ progressError ? 'Close' : 'Done' }}
          </el-button>
        </div>
      </template>
    </el-dialog>

    <!-- VL Offset Progress Dialog (Auto-Deduct / Cancel All) -->
    <el-dialog
      v-model="showVlOffsetProgressDialog"
      :title="vlOffsetProgressDialogTitle"
      width="640px"
      :close-on-click-modal="false"
      :close-on-press-escape="false"
      :show-close="false"
      align-center
      class="processing-dialog"
    >
      <div class="progress-content">
        <div class="progress-header">
          <el-icon :class="['processing-icon', { 'is-loading': !vlOffsetProgressComplete && !vlOffsetProgressError }]">
            <Check v-if="vlOffsetProgressComplete && !vlOffsetProgressError" />
            <Close v-else-if="vlOffsetProgressError" />
            <Loading v-else />
          </el-icon>
          <div class="progress-header-text">
            <h3>{{ vlOffsetProgressHeaderTitle }}</h3>
            <p v-if="vlOffsetProgressSubtitle" class="progress-subtitle">{{ vlOffsetProgressSubtitle }}</p>
            <p v-if="vlOffsetProgressStepCounter" class="progress-step-counter">{{ vlOffsetProgressStepCounter }}</p>
          </div>
        </div>
        <div class="progress-steps">
          <div
            v-for="(step, index) in vlOffsetProgressSteps"
            :key="index"
            class="progress-step"
            :class="{
              'active': step.status === 'active',
              'completed': step.status === 'completed',
              'error': step.status === 'error',
              'skipped': step.status === 'skipped'
            }"
          >
            <div class="step-indicator">
              <el-icon v-if="step.status === 'completed'"><Check /></el-icon>
              <el-icon v-else-if="step.status === 'error'"><Close /></el-icon>
              <el-icon v-else-if="step.status === 'active'"><Loading /></el-icon>
              <el-icon v-else-if="step.status === 'skipped'" class="step-skipped-icon"><CircleCheck /></el-icon>
              <span v-else class="step-number">{{ index + 1 }}</span>
            </div>
            <div class="step-content">
              <div class="step-title">{{ step.title }}</div>
              <div v-if="step.message" class="step-message">{{ step.message }}</div>
            </div>
          </div>
        </div>
        <div v-if="vlOffsetProgressError" class="error-message">
          <el-alert type="error" :closable="false" show-icon>
            {{ vlOffsetProgressError }}
          </el-alert>
        </div>
      </div>
      <template #footer>
        <div class="dialog-footer">
          <el-button
            v-if="vlOffsetProgressComplete || vlOffsetProgressError"
            type="primary"
            @click="closeVlOffsetProgressDialog"
          >
            {{ vlOffsetProgressError ? 'Close' : 'Done' }}
          </el-button>
        </div>
      </template>
    </el-dialog>

    <!-- Offset Status Modal: View Offsetted Employees and Employees with no Offsets yet -->
    <el-dialog
      v-model="showOffsetStatusModal"
      title="View Offsetted Employees and Employees with no Offsets yet"
      width="800px"
      align-center
      destroy-on-close
    >
      <div v-loading="offsetStatusLoading" class="offset-status-content">
        <el-row :gutter="24">
          <el-col :span="12">
            <h4>Employees with Offsets (VL deducted)</h4>
            <el-table :data="offsetStatusData.offsetted" size="small" max-height="320" stripe>
              <el-table-column prop="employee_no" label="Emp No" width="90" />
              <el-table-column prop="name" label="Name" min-width="140" />
              <el-table-column prop="leave_credits" label="VL Credits" width="90" />
            </el-table>
            <p class="offset-status-count">{{ offsetStatusData.offsetted.length }} employee(s)</p>
          </el-col>
          <el-col :span="12">
            <h4>Employees with no Offsets yet (have tardiness/absences)</h4>
            <el-table :data="offsetStatusData.no_offset_yet" size="small" max-height="320" stripe>
              <el-table-column prop="employee_no" label="Emp No" width="90" />
              <el-table-column prop="name" label="Name" min-width="140" />
              <el-table-column prop="leave_credits" label="VL Credits" width="90" />
            </el-table>
            <p class="offset-status-count">{{ offsetStatusData.no_offset_yet.length }} employee(s)</p>
          </el-col>
        </el-row>
      </div>
      <template #footer>
        <el-button type="primary" @click="showOffsetStatusModal = false">Close</el-button>
      </template>
    </el-dialog>

    <!-- Date override: warn before opening the calendar -->
    <el-dialog
      v-model="dateOverrideWarningVisible"
      title="Date to process"
      width="440px"
      align-center
      append-to-body
      :close-on-click-modal="false"
      @closed="onDateOverrideWarningDialogClosed"
    >
      <div class="dev-date-info-content">
        <strong>Date To Process</strong> defaults to today's calendar date
        (<strong>{{ calendarTodayManila }}</strong>). If you wish to process today, don't select a date.
        If you wish to process for a different payroll period, pick the appropriate date corresponding to the payroll period.
      </div>
      <template #footer>
        <el-button @click="onDateOverrideWarningCancel">Cancel</el-button>
        <el-button type="primary" @click="onDateOverrideWarningContinue">Continue</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, watch, computed, onMounted, nextTick } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Loading, Check, Close, CircleCheck, ArrowDown } from '@element-plus/icons-vue'
import api, { processAttendanceService } from '../../services/api'
import { authApi } from '../../services/api'
import { useAuth } from '../../Composables/useAuth'
import { minutesToDayFraction } from '@/Composables/useDayFractionConversion'
import { devAuthService } from '../../services/devAuth'

const emit = defineEmits([
  'processed',
  'update:payrollPeriodId',
  'update:precedingPayrollPeriodId',
  'employees-loaded',
  'save-all',
  'reprocess-all',
  'view-processed',
])

const props = defineProps({
  showSaveAll: { type: Boolean, default: false },
  saveAllLoading: { type: Boolean, default: false },
  /** 'regular' → etypes 1,3,5,7 | 'cos' → etypes 2,4,6 | null → all */
  type: { type: String, default: null }
})

const intervals = ref([])
const periods = ref([])
const precedingPeriods = ref([])
const loading = ref(false)
const viewLoading = ref(false)
const vlOffsetLoading = ref(false)

// VL Offset progress dialog (Auto-Deduct / Cancel All)
const showVlOffsetProgressDialog = ref(false)
const vlOffsetProgressSteps = ref([])
const vlOffsetProgressComplete = ref(false)
const vlOffsetProgressError = ref(null)
const vlOffsetProgressContext = ref({ action: '', periodLabel: '' })
const isAlreadyProcessed = ref(false)
const BACKEND_PAGE_SIZE = 1000

// Progress dialog state
const showProgressDialog = ref(false)
const progressSteps = ref([])
const progressError = ref(null)
const progressComplete = ref(false)
const needsReprocess = ref(false)
const progressContext = ref({ periodLabel: '', employeeCount: 0 })

/** Sub-phase while polling process run: cancel allowed only during calculating (matches previous UX). */
const processMonitorPhase = ref('')
const validateCalcProgressPercent = ref(0)
const validateCalcProgressIndeterminate = ref(true)
const reprocessProgressPercent = ref(0)
const reprocessProgressIndeterminate = ref(true)

function resetValidateCalcProgress() {
  processMonitorPhase.value = ''
  validateCalcProgressPercent.value = 0
  validateCalcProgressIndeterminate.value = true
  reprocessProgressPercent.value = 0
  reprocessProgressIndeterminate.value = true
}

const currentProcessRunId = ref(null)

// Dialog title and header (detailed view)
const progressDialogTitle = computed(() => progressComplete.value ? 'Process Attendance — Complete' : progressError.value ? 'Process Attendance — Error' : 'Process Attendance — In Progress')
const progressHeaderTitle = computed(() => {
  if (progressError.value) return 'Processing stopped'
  if (progressComplete.value) return 'Attendance processing completed successfully'
  const active = progressSteps.value.find(s => s.status === 'active')
  return active?.title || 'Processing attendance...'
})
const progressSubtitle = computed(() => {
  const { periodLabel, employeeCount } = progressContext.value
  if (periodLabel && employeeCount > 0) return `${periodLabel} • ${employeeCount} employees`
  if (periodLabel) return periodLabel
  return null
})
const progressStepCounter = computed(() => {
  const total = progressSteps.value.length
  const activeIndex = progressSteps.value.findIndex(s => s.status === 'active')
  const completed = progressSteps.value.filter(s => s.status === 'completed').length
  if (total === 0) return ''
  if (activeIndex >= 0) return `Step ${activeIndex + 1} of ${total}`
  if (completed === total) return `All ${total} steps completed`
  return ''
})

// VL Offset progress dialog computed
const vlOffsetProgressDialogTitle = computed(() =>
  vlOffsetProgressComplete.value
    ? `${vlOffsetProgressContext.value.action} — Complete`
    : vlOffsetProgressError.value
      ? `${vlOffsetProgressContext.value.action} — Error`
      : `${vlOffsetProgressContext.value.action} — In Progress`
)
const vlOffsetProgressHeaderTitle = computed(() => {
  if (vlOffsetProgressError.value) return 'Processing stopped'
  if (vlOffsetProgressComplete.value) return `${vlOffsetProgressContext.value.action} completed successfully`
  const active = vlOffsetProgressSteps.value.find(s => s.status === 'active')
  return active?.title || vlOffsetProgressContext.value.action + '...'
})
const vlOffsetProgressSubtitle = computed(() => vlOffsetProgressContext.value.periodLabel || null)
const vlOffsetProgressStepCounter = computed(() => {
  const total = vlOffsetProgressSteps.value.length
  const activeIndex = vlOffsetProgressSteps.value.findIndex(s => s.status === 'active')
  const completed = vlOffsetProgressSteps.value.filter(s => s.status === 'completed').length
  if (total === 0) return ''
  if (activeIndex >= 0) return `Step ${activeIndex + 1} of ${total}`
  if (completed === total) return `All ${total} steps completed`
  return ''
})

const form = ref({
  payroll_interval_id: null,
  payroll_period_id: null,
  preceding_payroll_period_id: null,
})

watch(
  () => form.value.preceding_payroll_period_id,
  (v) => {
    emit('update:precedingPayrollPeriodId', v != null && v !== '' ? Number(v) : null)
  },
  { immediate: true }
)

// Dev Mode state
// Show dev date when running in development (e.g. localhost) or when dev auth is valid
const isDevMode = computed(() => import.meta.env.DEV || devAuthService.isDevAuthValid())
const devDate = ref(null)
const devDatePickerRef = ref(null)
const dateOverrideWarningVisible = ref(false)
/** After Continue, the date picker opens normally until the page is reloaded. */
const dateOverrideGatePassed = ref(false)
/** When true, open the date panel after the warning dialog has fully closed (focus/modal released). */
const shouldOpenDevDatePickerAfterWarning = ref(false)

function openDateOverrideWarning() {
  dateOverrideWarningVisible.value = true
}

function onDateOverrideWarningCancel() {
  shouldOpenDevDatePickerAfterWarning.value = false
  dateOverrideWarningVisible.value = false
}

function onDateOverrideWarningContinue() {
  shouldOpenDevDatePickerAfterWarning.value = true
  dateOverrideGatePassed.value = true
  dateOverrideWarningVisible.value = false
  // Try opening as soon as the guard unmounts; dialog may still be closing.
  nextTick(() => {
    devDatePickerRef.value?.handleOpen?.()
  })
}

function onDateOverrideWarningDialogClosed() {
  if (!shouldOpenDevDatePickerAfterWarning.value) return
  shouldOpenDevDatePickerAfterWarning.value = false
  // Always open once the dialog transition is done so the calendar appears reliably.
  nextTick(() => {
    devDatePickerRef.value?.handleOpen?.()
  })
}

/** Wall-clock calendar date in Asia/Manila (matches server real_today); not sent as override unless user picks another date. */
const calendarTodayManila = computed(() => {
  try {
    const parts = new Intl.DateTimeFormat('en-CA', {
      timeZone: 'Asia/Manila',
      year: 'numeric',
      month: '2-digit',
      day: '2-digit'
    }).formatToParts(new Date())
    const y = parts.find((p) => p.type === 'year')?.value
    const m = parts.find((p) => p.type === 'month')?.value
    const d = parts.find((p) => p.type === 'day')?.value
    if (y && m && d) return `${y}-${m}-${d}`
  } catch (_) {}
  return new Date().toISOString().split('T')[0]
})

const canProcess = computed(() => !!form.value.payroll_interval_id && !!form.value.payroll_period_id)

// Filter out the currently selected payroll period from preceding periods
const filteredPrecedingPeriods = computed(() => {
  if (!form.value.payroll_period_id) {
    return precedingPeriods.value
  }
  return precedingPeriods.value.filter(p => p.id !== form.value.payroll_period_id)
})

// Helper function to update progress step
function updateProgressStep(index, status, message = '') {
  if (progressSteps.value[index]) {
    progressSteps.value[index].status = status
    progressSteps.value[index].message = message
  }
}

// Build detailed step list: validate+calculate merged; reprocess path includes "Reprocessing all employees"
const STEP_INDEX = {
  VALIDATE_AND_CALCULATE: 0,
  LOAD_EMPLOYEES: 1,
  REPROCESS: 2
}

// Helper function to reset progress (call after we know needsReprocess)
function resetProgress() {
  resetValidateCalcProgress()
  if (needsReprocess.value) {
    progressSteps.value = [
      { title: 'Validating and Calculating Attendance', status: 'pending', message: 'This may take a while. Please Wait...' },
      { title: 'Loading employee attendance data', status: 'pending', message: 'Fetching list of employees and their attendance for this period.' },
      { title: 'Reprocessing all employees', status: 'pending', message: 'Recalculating attendance for every employee in the period.' },
      { title: 'Saving results...', status: 'pending', message: 'Saving results into attendance summary.' },
      { title: 'Finalizing', status: 'pending', message: 'Refreshing data and updating the view.' }
    ]
  } else {
    progressSteps.value = [
      { title: 'Validating and Calculating Attendance', status: 'pending', message: 'This may take a while. Please Wait...' },
      { title: 'Loading employee attendance data...', status: 'pending', message: 'Fetching list of employees and their attendance for this period.' },
      { title: 'Saving results...', status: 'pending', message: 'Saving results into attendance summary.' },
      { title: 'Finalizing...', status: 'pending', message: 'Refreshing data and updating the view.' }
    ]
  }
  progressError.value = null
  progressComplete.value = false
}

// Reset to initial single step (before we know needsReprocess)
function resetProgressInitial() {
  resetValidateCalcProgress()
  progressSteps.value = [
    { title: 'Validating and Calculating Attendance', status: 'active', message: 'This may take a while. Please Wait...' }
  ]
  progressError.value = null
  progressComplete.value = false
}

const wait = (ms) => new Promise((resolve) => setTimeout(resolve, ms))

async function monitorProcessProgress(processRunId, payrollPeriodId) {
  let fallbackSwitchedToCalculating = false
  const startedAt = Date.now()
  processMonitorPhase.value = 'validating'
  validateCalcProgressIndeterminate.value = true
  validateCalcProgressPercent.value = 0
  let latestProgress = null
  // Debug logging (throttled) so we can see why Docker stalls (running + null progress fields).
  let lastDebugLogAt = 0
  let lastDebugSignature = ''

  while (true) {
    try {
      // Single source of truth for the progress bar:
      // always read from attendance_process_runs.
      const latestRes = await processAttendanceService.getLatestProcessProgress({
        payroll_period_id: payrollPeriodId,
        process_run_id: processRunId,
      })
      const progress = latestRes?.data?.data ?? latestRes?.data ?? latestRes
      latestProgress = progress
      const status = String(progress?.status || '').trim().toLowerCase()
      const phase = String(progress?.phase || '').trim().toLowerCase()
      const currentDate = progress?.current_date
      const currentDateIndex = Number(progress?.current_date_index || 0)
      const totalDates = Number(progress?.total_dates || 0)
      const currentEmployeeIndex = Number(progress?.current_employee_index || 0)
      const totalEmployees = Number(progress?.total_employees || 0)

      const combinedMessage = 'This may take a while. Please Wait...'

      // ---- Debug: detect "stuck" signature (running but no progress fields) ----
      // In Docker, this often means the async worker never started/crashed, so the row stays "running"
      // but never updates phase/date counters.
      const elapsedMs = Date.now() - startedAt
      const hasAnyProgressFields =
        !!phase ||
        !!currentDate ||
        totalDates > 0 ||
        currentDateIndex > 0 ||
        totalEmployees > 0 ||
        currentEmployeeIndex > 0
      const stuckSignature = [
        status,
        phase || '(null)',
        currentDate || '(null)',
        String(progress?.current_date_index ?? '(null)'),
        String(progress?.total_dates ?? '(null)'),
        String(progress?.current_employee_index ?? '(null)'),
        String(progress?.total_employees ?? '(null)'),
      ].join('|')

      // Log immediately after the first poll and then:
      // - every ~6s while still stuck, OR
      // - any time the signature changes (phase/date counters start moving)
      const shouldLog =
        elapsedMs < 2500 ||
        (Date.now() - lastDebugLogAt) >= 6000 ||
        stuckSignature !== lastDebugSignature

      if (shouldLog) {
        lastDebugLogAt = Date.now()
        lastDebugSignature = stuckSignature
        if (status === 'running' && !hasAnyProgressFields && elapsedMs >= 2500) {
          console.warn('[ProcessAttendance][ProgressPoll] running but no progress fields yet — background worker likely not running (common in Docker). Check backend logs: attendance-async-worker.log or [ProcessAttendance][Async] Inline worker', {
            processRunId,
            payrollPeriodId,
            elapsedMs,
            progress,
          })
        } else {
          console.debug('[ProcessAttendance][ProgressPoll]', {
            processRunId,
            payrollPeriodId,
            elapsedMs,
            status,
            phase: phase || null,
            currentDate,
            currentDateIndex: progress?.current_date_index ?? null,
            totalDates: progress?.total_dates ?? null,
            currentEmployeeIndex: progress?.current_employee_index ?? null,
            totalEmployees: progress?.total_employees ?? null,
            progress_message: progress?.progress_message ?? null,
          })
        }
      }

      // Primary source: attendance_process_runs numeric progress fields.
      // If date counters are moving, treat as calculating even when phase text lags.
      const hasDateProgress = totalDates > 0 && currentDateIndex > 0

      if (hasDateProgress || phase === 'calculating') {
        processMonitorPhase.value = 'calculating'
        validateCalcProgressIndeterminate.value = false
        const td = Math.max(1, totalDates)
        const di = Math.max(0, Number(currentDateIndex) || 0)
        let pct =
          totalDates > 0
            ? Math.min(99, Math.round((di / td) * 100))
            : Math.min(90, 5 + Math.floor((Date.now() - startedAt) / 500))
        validateCalcProgressPercent.value = pct
        updateProgressStep(STEP_INDEX.VALIDATE_AND_CALCULATE, 'active', combinedMessage)
        fallbackSwitchedToCalculating = true
      } else if (phase === 'validating') {
        processMonitorPhase.value = 'validating'
        validateCalcProgressIndeterminate.value = true
        validateCalcProgressPercent.value = 0
        updateProgressStep(
          STEP_INDEX.VALIDATE_AND_CALCULATE,
          'active',
          combinedMessage
        )
      } else if (!fallbackSwitchedToCalculating && Date.now() - startedAt >= 3000) {
        // Fallback when progress API lags: assume calculating and creep percent.
        processMonitorPhase.value = 'calculating'
        validateCalcProgressIndeterminate.value = false
        validateCalcProgressPercent.value = Math.min(90, 5 + Math.floor((Date.now() - startedAt) / 400))
        updateProgressStep(STEP_INDEX.VALIDATE_AND_CALCULATE, 'active', combinedMessage)
        fallbackSwitchedToCalculating = true
      }

      if (status === 'completed') {
        validateCalcProgressIndeterminate.value = false
        validateCalcProgressPercent.value = 100
        break
      }
      if (status === 'failed' || status === 'cancelled') {
        const msg = progress?.progress_message || (status === 'cancelled' ? 'Attendance processing cancelled' : 'Attendance processing failed')
        const terminalErr = new Error(msg)
        terminalErr.__terminal = true
        throw terminalErr
      }
    } catch (err) {
      if (err?.__terminal) throw err
      console.error('[ProcessAttendance][ProgressPoll] polling request failed', {
        processRunId,
        payrollPeriodId,
        error: err,
        message: err?.message,
        status: err?.response?.status ?? err?.status ?? null,
        data: err?.response?.data ?? null,
      })
      // keep UI responsive even if occasional progress fetch fails
      if (!fallbackSwitchedToCalculating && Date.now() - startedAt >= 3000) {
        processMonitorPhase.value = 'calculating'
        validateCalcProgressIndeterminate.value = false
        validateCalcProgressPercent.value = Math.min(90, 5 + Math.floor((Date.now() - startedAt) / 400))
        updateProgressStep(STEP_INDEX.VALIDATE_AND_CALCULATE, 'active', 'This may take a while. Please Wait...')
        fallbackSwitchedToCalculating = true
      }
    }

    await wait(1200)
  }

  processMonitorPhase.value = ''
  validateCalcProgressIndeterminate.value = false
  validateCalcProgressPercent.value = 100

  return latestProgress
}

async function monitorReprocessProgress(processRunId, payrollPeriodId) {
  const startedAt = Date.now()
  let latestProgress = null

  while (true) {
    try {
      const latestRes = await processAttendanceService.getLatestProcessProgress({
        payroll_period_id: payrollPeriodId,
        process_run_id: processRunId,
      })
      const progress = latestRes?.data?.data ?? latestRes?.data ?? latestRes
      latestProgress = progress
      const status = String(progress?.status || '').trim().toLowerCase()
      const currentDateIndex = Number(progress?.current_date_index || 0)
      const totalDates = Number(progress?.total_dates || 0)

      reprocessProgressIndeterminate.value = false
      if (totalDates > 0 && currentDateIndex > 0) {
        reprocessProgressPercent.value = Math.min(99, Math.round((currentDateIndex / totalDates) * 100))
      } else {
        reprocessProgressPercent.value = Math.min(90, 5 + Math.floor((Date.now() - startedAt) / 600))
      }

      if (status === 'completed') break
      if (status === 'failed' || status === 'cancelled') {
        throw new Error(progress?.progress_message || (status === 'cancelled' ? 'Reprocess cancelled' : 'Reprocess failed'))
      }
    } catch (_) {
      // Keep UI responsive during occasional polling hiccups.
      reprocessProgressIndeterminate.value = false
      reprocessProgressPercent.value = Math.min(90, 5 + Math.floor((Date.now() - startedAt) / 600))
    }
    await wait(1200)
  }

  reprocessProgressIndeterminate.value = false
  reprocessProgressPercent.value = 100
  return latestProgress
}

// Helper function to close progress dialog
function closeProgressDialog() {
  showProgressDialog.value = false
  resetProgress()
  currentProcessRunId.value = null
}

// VL Offset progress helpers
function resetVlOffsetProgress(action, steps) {
  vlOffsetProgressSteps.value = steps
  vlOffsetProgressComplete.value = false
  vlOffsetProgressError.value = null
  vlOffsetProgressContext.value = { action, periodLabel: formatPeriodLabel(periods.value.find(p => p.id === form.value.payroll_period_id) || {}) }
}
function updateVlOffsetProgressStep(index, status, message = '') {
  if (vlOffsetProgressSteps.value[index]) {
    vlOffsetProgressSteps.value[index].status = status
    vlOffsetProgressSteps.value[index].message = message
  }
}
function closeVlOffsetProgressDialog() {
  showVlOffsetProgressDialog.value = false
  vlOffsetProgressComplete.value = false
  vlOffsetProgressError.value = null
  vlOffsetLoading.value = false
}

// Combined function that processes, reprocesses, and saves all
async function processAttendance() {
  if (!canProcess.value) return
  
  loading.value = true
  showProgressDialog.value = true

  currentProcessRunId.value =
    typeof crypto !== 'undefined' && crypto.randomUUID ? crypto.randomUUID() : `run_${Date.now()}_${Math.random().toString(16).slice(2)}`

  const selectedPeriod = periods.value.find(p => p.id === form.value.payroll_period_id)
  progressContext.value = { periodLabel: selectedPeriod ? formatPeriodLabel(selectedPeriod) : '', employeeCount: 0 }

  try {
    // Ensure auth is initialized (triggers DevAuth/SharedAuth on backend)
    try { await authApi.getCurrentUser() } catch (_) {}

    const payload = {
      payroll_interval_id: Number(form.value.payroll_interval_id),
      payroll_period_id: Number(form.value.payroll_period_id),
      preceding_payroll_period_id: form.value.preceding_payroll_period_id ? Number(form.value.preceding_payroll_period_id) : null,
      process_run_id: currentProcessRunId.value,
      // Dev date override (restriction disabled – sent whenever devDate is set)
      // Restore dev-only: use (isDevMode.value && devDate.value ? ... : {})
      ...(devDate.value ? { dev_date_override: devDate.value } : {})
    }

    if (!payload.payroll_interval_id || !payload.payroll_period_id) {
      throw new Error('Select both Payroll Interval and Payroll Period')
    }

    // Determine if this period already has summary rows before kicking off async processing.
    const periodStatus = await processAttendanceService.checkPayrollPeriodStatus(payload.payroll_period_id)
    const periodStatusData = periodStatus?.data ?? periodStatus
    const alreadyProcessed = !!periodStatusData?.has_summary_records
    const existingRecordsCount = alreadyProcessed ? 1 : 0
    needsReprocess.value = alreadyProcessed && existingRecordsCount > 0

    // Attendance Step A/B (async worker + polling):
    // A) Validate attendance scope
    // B) Calculate attendance with date/employee progress
    resetProgressInitial()
    const processStartRes = await processAttendanceService.process(payload)
    const processStartData = processStartRes?.data?.data ?? processStartRes?.data ?? processStartRes
    console.info('[ProcessAttendance][ProcessStart] POST /process-attendance response', {
      processRunId: payload.process_run_id,
      payrollPeriodId: payload.payroll_period_id,
      queued: processStartData?.queued ?? null,
      workerBuild: processStartData?.worker_build ?? null,
      response: processStartData,
    })
    if (!processStartData?.worker_build) {
      console.warn('[ProcessAttendance][ProcessStart] worker_build missing — backend may be running an old Docker image. Rebuild the backend container.')
    }
    await monitorProcessProgress(
      payload.process_run_id,
      payload.payroll_period_id
    )

    // Build full step list and mark validate+calculate step completed
    resetProgress()
    updateProgressStep(
      STEP_INDEX.VALIDATE_AND_CALCULATE,
      'completed',
      alreadyProcessed ? 'Period already processed; recalculating if you continue.' : 'Validation and attendance calculation completed.'
    )
    
    if (alreadyProcessed && existingRecordsCount > 0) {
      // Check if payroll period is posted and has time_data_summary records
      const selectedPeriod = periods.value.find(p => p.id === payload.payroll_period_id)
      const isPosted = selectedPeriod && (selectedPeriod.posted === 1 || selectedPeriod.posted === true)
      
      // Build warning message
      let warningMessage = 'This payroll period has already been processed. Processing again will recalculate attendance data. Offsetted time records would not be affected.'
      
      if (isPosted) {
        warningMessage += '\n\n⚠️ WARNING: This payroll period is already POSTED and has been saved. Reprocessing may affect existing payroll data. Proceed with caution!'
      }
      
      warningMessage += '\n\nAre you sure you want to continue?'
      
      // Show confirmation dialog before proceeding with reprocess
      try {
        await ElMessageBox.confirm(
          warningMessage,
          'Warning: Reprocess Attendance',
          {
            confirmButtonText: 'Yes, Continue',
            cancelButtonText: 'No, Cancel',
            type: 'warning',
          }
        )
      } catch {
        // User cancelled
        loading.value = false
        showProgressDialog.value = false
        resetProgress()
        return
      }
      
      ElMessage.info('Attendance data has already been processed. Reprocessing all employees...')
    } else {
      ElMessage.success('Attendance processed successfully')
    }
    
    // Step: Loading employee attendance data
    const loadStep = STEP_INDEX.LOAD_EMPLOYEES
    updateProgressStep(loadStep, 'active', 'Fetching employee list and attendance data for this period…')
    const res = await processAttendanceService.getEmployeeAttendanceData(payload.payroll_period_id, { page: 1, per_page: BACKEND_PAGE_SIZE })
    const employees = res?.data ?? res
    const employeesArray = Array.isArray(employees) ? employees : []
    progressContext.value = { ...progressContext.value, employeeCount: employeesArray.length }
    updateProgressStep(loadStep, 'completed', `Loaded ${employeesArray.length} employees`)

    // Step (reprocess path only): Reprocessing all employees
    const reprocessStep = STEP_INDEX.REPROCESS
    const saveStepIndex = needsReprocess.value ? 3 : 2
    const finalizeStepIndex = needsReprocess.value ? 4 : 3

    if (needsReprocess.value) {
      updateProgressStep(reprocessStep, 'active', 'Recalculating attendance for every employee in the period…')
      reprocessProgressIndeterminate.value = true
      reprocessProgressPercent.value = 0
      try {
        let reprocessResponse
        if (typeof processAttendanceService.reprocessAll === 'function') {
          const reprocessPayload = {
            payroll_period_id: payload.payroll_period_id,
            process_run_id: currentProcessRunId.value,
            preceding_payroll_period_id: form.value.preceding_payroll_period_id
              ? Number(form.value.preceding_payroll_period_id)
              : null,
            // Restore dev-only: (isDevMode.value && devDate.value ? { dev_date_override: devDate.value } : {})
            ...(devDate.value ? { dev_date_override: devDate.value } : {})
          }
          await processAttendanceService.reprocessAll(reprocessPayload)
        } else {
          ElMessage.warning('Reprocess service method missing. Using fallback API call.')
          const fallbackPayload = {
            payroll_period_id: payload.payroll_period_id,
            process_run_id: currentProcessRunId.value,
            preceding_payroll_period_id: form.value.preceding_payroll_period_id
              ? Number(form.value.preceding_payroll_period_id)
              : null,
            // Restore dev-only: (isDevMode.value && devDate.value ? ... : {})
            ...(devDate.value ? { dev_date_override: devDate.value } : {})
          }
          await api.post('/process-attendance/reprocess-all', fallbackPayload)
        }
        reprocessResponse = await monitorReprocessProgress(
          currentProcessRunId.value,
          payload.payroll_period_id
        )
        reprocessProgressIndeterminate.value = false
        reprocessProgressPercent.value = 100
        updateProgressStep(reprocessStep, 'completed', reprocessResponse?.message || 'Successfully reprocessed all employees')
        ElMessage.success(reprocessResponse?.message || 'Successfully reprocessed all employees')
      } catch (reprocessError) {
        reprocessProgressIndeterminate.value = false
        updateProgressStep(reprocessStep, 'error', 'Reprocess encountered an issue, continuing…')
        ElMessage.warning('Reprocess step encountered an issue, but continuing…')
      }
    }
    // When !needsReprocess there is no Reprocess step in the list (only 4 steps).

    // Step: Saving attendance calculations to summary (with per-employee progress)
    if (employeesArray.length > 0) {
      updateProgressStep(saveStepIndex, 'active', `Saving attendance data and transferring to payroll... (0 of ${employeesArray.length} employees)`)
      let successCount = 0
      let warningCount = 0
      let failedCount = 0
      let saved = 0

      for (const employee of employeesArray) {
        try {
          const savePayload = {
            payroll_period_id: payload.payroll_period_id,
            employee_id: employee.employee_id || employee.id,
            daily_rate: parseFloat(employee.daily_rate || 0),
            days_covered: parseInt(employee.days_covered || 0),
            // Use day fractions for storage (CSC compliance)
            // Explicitly convert using lookup table if possible, fallback to existing field
            total_late: employee.total_late_day_fraction || minutesToDayFraction(employee.total_late * 60) || 0,
            late_amount: parseFloat(employee.late_amount || 0),
            total_undertime: employee.total_undertime_day_fraction || minutesToDayFraction(employee.total_undertime * 60) || 0,
            undertime_amount: parseFloat(employee.undertime_amount || 0),
            total_absent: parseFloat(employee.total_absent || 0),
            absent_amount: parseFloat(employee.absent_amount || 0),
            total_amount: parseFloat(employee.total_amount || 0),
            hours_worked: parseFloat(employee.total_work_hours || 0),
            working_hours: parseFloat(employee.total_work_hours || 0),
            work_hours: parseFloat(employee.total_work_hours || 0),
            overtime_pay: parseFloat(employee.ot_pay || 0),
            adjustment_amount: parseFloat(employee.adjustment_amount || 0),
            adjustment_period_id: employee.adjustment_period_id || null
          }
          
          const saveResponse = await processAttendanceService.save(savePayload)
          saved++
          updateProgressStep(saveStepIndex, 'active', `Saving attendance data and transferring to payroll...  (${saved} of ${employeesArray.length} employees)`)
          if (saveResponse && saveResponse.type === 'warning') {
            warningCount++
          } else {
            successCount++
          }
        } catch (saveError) {
          saved++
          updateProgressStep(saveStepIndex, 'active', `Saving attendance data and transferring to payroll... (${saved} of ${employeesArray.length} employees)`)
          if (saveError.response && saveError.response.data && saveError.response.data.type === 'warning') {
            warningCount++
          } else {
            failedCount++
          }
        }
      }
      updateProgressStep(saveStepIndex, 'completed', `Saved ${successCount} employees to payroll summary${failedCount > 0 ? `; ${failedCount} failed.` : '.'}`)
      // Show summary message
      if (successCount > 0) {
        ElMessage.success(`Successfully saved ${successCount} employee time records!`)
      }
      if (warningCount > 0) {
        ElMessage.warning(`${warningCount} employees were already processed`)
      }
      if (failedCount > 0) {
        ElMessage.error(`${failedCount} employees failed to save. Please retry or contact support.`)
      }
    } else {
      updateProgressStep(saveStepIndex, 'completed', 'No employees in this period.')
    }

    // Finalizing
    updateProgressStep(finalizeStepIndex, 'active', 'Refreshing data and updating the view…')
    
    // Emit events and load final data
    emit('processed', { ...form.value })
    emit('reprocess-all', { ...form.value })
    
    // Reload employee attendance data after all operations
    const finalRes = await processAttendanceService.getEmployeeAttendanceData(payload.payroll_period_id, { page: 1, per_page: BACKEND_PAGE_SIZE })
    const finalEmployees = finalRes?.data ?? finalRes
    emit('employees-loaded', Array.isArray(finalEmployees) ? finalEmployees : [])
    
    updateProgressStep(finalizeStepIndex, 'completed', 'Processing completed successfully.')
    progressComplete.value = true
    
    // Update the already processed flag since we just processed it
    isAlreadyProcessed.value = true
    
    ElMessage.success('Process Attendance completed successfully')
  } catch (e) {
    // Extract error message from various possible error formats
    let errorMessage = 'Failed to process attendance'
    
    if (e?.response?.data?.message) {
      errorMessage = e.response.data.message
    } else if (e?.data?.message) {
      errorMessage = e.data.message
    } else if (e?.message) {
      errorMessage = e.message
    } else if (typeof e === 'string') {
      errorMessage = e
    }
    
    // Handle validation errors
    if (e?.response?.data?.errors) {
      const errors = e.response.data.errors
      const errorMessages = Object.values(errors).flat()
      errorMessage = errorMessages.join(', ') || errorMessage
    }
    
    // Find the current active step and mark it as error
    const activeStepIndex = progressSteps.value.findIndex(step => step.status === 'active')
    if (activeStepIndex !== -1) {
      updateProgressStep(activeStepIndex, 'error', errorMessage)
    }
    
    progressError.value = errorMessage
    ElMessage.error(errorMessage)
    emit('employees-loaded', [])
  } finally {
    loading.value = false
  }
}

// Get authentication state
const { isAuthenticated } = useAuth()

// Handle dev date change (restriction disabled – works in production too)
function onDevDateChange(date) {
  // Restore dev-only: if (!isDevMode.value) return

  if (date) {
    ElMessage.success(`Date To Process set to: ${date}`)
  } else {
    ElMessage.info('Dev date override cleared')
    dateOverrideGatePassed.value = false
  }
}

// Only load intervals when authenticated
onMounted(async () => {
  if (isAuthenticated.value) {
    await loadIntervals()
    return
  }
  // Dev/Shared auth bootstrap: ping /user to trigger backend middleware (DevAuth/SharedAuth)
  try {
    await authApi.getCurrentUser()
    await loadIntervals()
  } catch (_) {
    // still unauthenticated; wait for auth watcher
  }
})

// Watch for authentication changes
watch(isAuthenticated, (authenticated) => {
  if (authenticated && intervals.value.length === 0) {
    loadIntervals()
  }
})

/** One row per payroll period id (API used to duplicate via employment-type join). */
function uniquePeriodsById(list) {
  if (!Array.isArray(list)) return []
  const byId = new Map()
  for (const p of list) {
    const id = Number(p?.id)
    if (id && !byId.has(id)) byId.set(id, p)
  }
  return [...byId.values()]
}

function formatPeriodLabel(p) {
  if (!p) return ''
  
  // Use the formatted name from backend and append payroll cutoff name when available
  if (p.name) {
    const base = String(p.name).trim()
    if (p.cutoff_name) {
      return `${base} - ${p.cutoff_name}`
    }
    return base
  }

  const start = p.attendance_start_date || p.date_from || p.start_date
  const end = p.attendance_end_date || p.date_to || p.end_date
  if (start && end) {
    return `${formatDate(start)} - ${formatDate(end)}`
  }
  if (start) return formatDate(start)
  if (p.id != null) return `Payroll Period #${p.id}`
  return 'Unknown payroll period'
}

async function loadIntervals() {
  loading.value = true
  try {
    const res = await processAttendanceService.index()
    intervals.value = res?.intervals ?? []
  } catch (e) {
    ElMessage.error(e.message || 'Failed to load intervals')
  } finally {
    loading.value = false
  }
}

async function loadPeriods(intervalId) {
  if (!intervalId) { periods.value = []; return }
  loading.value = true
  try {
    // Use the new dedicated endpoint for payroll periods
    const res = await processAttendanceService.getPayrollPeriods(intervalId, props.type)
    const list = res?.data ?? res
    periods.value = Array.isArray(list) ? list : []
  } catch (e) {
    ElMessage.error(e.message || 'Failed to load payroll periods')
  } finally {
    loading.value = false
  }
}

async function loadPrecedingPeriods(intervalId) {
  if (!intervalId) { precedingPeriods.value = []; return }
  loading.value = true
  try {
    // Use the same endpoint for preceding periods (same type filter)
    const res = await processAttendanceService.getPayrollPeriods(intervalId, props.type)
    const list = res?.data ?? res
    precedingPeriods.value = uniquePeriodsById(Array.isArray(list) ? list : [])
  } catch (e) {
    ElMessage.error(e.message || 'Failed to load preceding payroll periods')
  } finally {
    loading.value = false
  }
}

async function onIntervalChange(val) {
  form.value.payroll_period_id = null
  form.value.preceding_payroll_period_id = null
  isAlreadyProcessed.value = false
  await Promise.all([loadPeriods(val), loadPrecedingPeriods(val)])
  // Clear table and overview when interval changes
  emit('employees-loaded', [])
  // Clear processed state in parent to reset overview
  emit('view-processed', {
    employees: [],
    daysPresent: {}
  })
  // Don't persist selection when changing intervals
}

async function onPeriodChange(periodId) {
  if (!periodId) {
    isAlreadyProcessed.value = false
    // Clear table when period is cleared
    emit('employees-loaded', [])
    // Clear processed state in parent
    emit('view-processed', {
      employees: [],
      daysPresent: {}
    })
    // Clear preceding period when current period is cleared
    form.value.preceding_payroll_period_id = null
    return
  }
  
  // Clear preceding period when current period changes to avoid invalid selections
  // The user will need to reselect a valid preceding period
  if (form.value.preceding_payroll_period_id === periodId) {
    form.value.preceding_payroll_period_id = null
  }
  
  emit('update:payrollPeriodId', periodId)
  
  // Check if this payroll period is already processed
  await checkIfAlreadyProcessed(periodId)
  
  // Clear table and overview when period changes - data will only load when "View Processed Attendance" is clicked
  emit('employees-loaded', [])
  // Clear processed state in parent to reset overview
  emit('view-processed', {
    employees: [],
    daysPresent: {},
  })
  
  // Don't persist selection when changing periods
}

async function onPrecedingPeriodChange(precedingPeriodId) {
  if (!precedingPeriodId) {
    // Cleared the preceding period selection
    ElMessage.info('Preceding payroll period cleared')
    return
  }
  
  // Check if user somehow selected the same period as current period
  if (precedingPeriodId === form.value.payroll_period_id) {
    ElMessage.warning('Preceding period cannot be the same as the current payroll period')
    form.value.preceding_payroll_period_id = null
    return
  }
  
  // Validate that preceding period is before the current period
  if (form.value.payroll_period_id) {
    const currentPeriod = periods.value.find(p => p.id === form.value.payroll_period_id)
    const precedingPeriod = precedingPeriods.value.find(p => p.id === precedingPeriodId)
    
    if (currentPeriod && precedingPeriod) {
      const currentStart = new Date(currentPeriod.attendance_start_date || currentPeriod.date_from || currentPeriod.start_date)
      const precedingStart = new Date(precedingPeriod.attendance_start_date || precedingPeriod.date_from || precedingPeriod.start_date)
      
      if (precedingStart >= currentStart) {
        ElMessage.warning('Preceding period should be before the current payroll period')
        form.value.preceding_payroll_period_id = null
        return
      }
    }
  }
  
  const selectedPrecedingPeriod = precedingPeriods.value.find(p => Number(p.id) === Number(precedingPeriodId))
  const selectedLabel = formatPeriodLabel(selectedPrecedingPeriod || { id: precedingPeriodId })
  ElMessage.success(`Preceding period selected: ${selectedLabel}`)
}

// Check if payroll period has saved processed rows (time_data_summary).
// Uses the lightweight period-status API — not getEmployeeAttendanceData (slow; wrong for this check).
async function checkIfAlreadyProcessed(payrollPeriodId) {
  if (!payrollPeriodId) {
    isAlreadyProcessed.value = false
    return
  }

  try {
    const status = await processAttendanceService.checkPayrollPeriodStatus(payrollPeriodId)
    const payload = status?.data ?? status
    isAlreadyProcessed.value = !!payload?.has_summary_records
  } catch (_) {
    isAlreadyProcessed.value = false
  }
}

// Auto-deduct tardiness/absences to VL for all employees in the selected period (bulk Apply Offset)
async function autoDeductTardinessToVL() {
  if (!canProcess.value) return
  if (!isAlreadyProcessed.value) {
    ElMessage.error('Period selected is not processed yet! Process the Period first.')
    return
  }
  try {
    await ElMessageBox.confirm(
      'This will apply offset (deduct tardiness/undertime from Vacation Leave) for all employees in the selected payroll period who have unoffset tardiness/undertime. Employees with insufficient VL credits will be skipped. Continue?',
      'Auto-Deduct Tardiness to VL',
      { confirmButtonText: 'Yes, run', cancelButtonText: 'Cancel', type: 'warning' }
    )
  } catch {
    return
  }
  vlOffsetLoading.value = true
  showVlOffsetProgressDialog.value = true
  resetVlOffsetProgress('Auto-Deduct Tardiness to VL', [
    { title: 'Validating payroll period', status: 'pending', message: 'Checking payroll period and employee list…' },
    { title: 'Applying offset for all employees', status: 'pending', message: 'Deducting tardiness/undertime from Vacation Leave credits for each employee…' },
    { title: 'Finalizing', status: 'pending', message: 'Updating attendance view…' }
  ])
  try {
    updateVlOffsetProgressStep(0, 'active', 'Validating payroll period and identifying employees with unoffset deductions…')
    updateVlOffsetProgressStep(0, 'completed', 'Validation completed.')
    updateVlOffsetProgressStep(1, 'active', 'Applying offset for all employees with tardiness/absences. Please wait…')
    const res = await processAttendanceService.applyOffsetAll(Number(form.value.payroll_period_id))
    const data = res?.data ?? res
    const processed = data?.employees_processed ?? 0
    const skipped = data?.employees_skipped ?? 0
    const totalDays = data?.total_offset_days ?? 0
    const errors = data?.errors ?? []
    updateVlOffsetProgressStep(1, 'completed', processed > 0 ? `Offset applied for ${processed} employee(s); ${Number(totalDays).toFixed(3)} days deducted.` : 'No offsets applied.')
    updateVlOffsetProgressStep(2, 'active', 'Refreshing attendance data…')
    if (processed > 0) {
      emit('processed', { ...form.value })
      emit('reprocess-all', { ...form.value })
    }
    updateVlOffsetProgressStep(2, 'completed', 'View updated.')
    vlOffsetProgressComplete.value = true
    if (processed > 0) {
      ElMessage.success(res?.message || `Offset applied for ${processed} employee(s); ${Number(totalDays).toFixed(3)} days deducted from VL.${skipped > 0 ? ` ${skipped} skipped.` : ''}`)
    } else if (skipped > 0 && processed === 0) {
      ElMessage.warning(res?.message || `No offsets applied. ${skipped} employee(s) skipped (e.g. insufficient VL).`)
      if (errors.length > 0) {
        console.warn('Auto-deduct skipped/errors:', errors)
      }
    } else {
      ElMessage.info(res?.message || 'No employees with unoffset tardiness/absences in this period.')
    }
  } catch (e) {
    const msg = e?.response?.data?.message || e?.message || 'Auto-deduct failed'
    vlOffsetProgressError.value = msg
    updateVlOffsetProgressStep(1, 'error', msg)
    ElMessage.error(msg)
  } finally {
    vlOffsetLoading.value = false
  }
}

// Dropdown command handler for VL Offset actions
function onVlOffsetCommand(command) {
  if (command === 'auto-deduct') {
    autoDeductTardinessToVL()
  } else if (command === 'cancel-all') {
    cancelAllOffsets()
  } else if (command === 'view-status') {
    openOffsetStatusModal()
  }
}

// Dropdown command handler for Process / View actions
function onProcessCommand(command) {
  if (!canProcess.value) return
  if (command === 'process') {
    processAttendance()
  } else if (command === 'view-processed') {
    if (!isAlreadyProcessed.value) {
      ElMessage.error('Period selected is not processed yet! Process the Period first.')
      return
    }
    viewProcessedAttendance()
  }
}

// Cancel all offsets for the selected period
async function cancelAllOffsets() {
  if (!canProcess.value) return
  if (!isAlreadyProcessed.value) {
    ElMessage.error('Period selected is not processed yet! Process the Period first.')
    return
  }
  try {
    await ElMessageBox.confirm(
      'This will cancel all offsets in the selected payroll period and restore Vacation Leave credits to all affected employees. Continue?',
      'Cancel all Offsets',
      { confirmButtonText: 'Yes, cancel all', cancelButtonText: 'Cancel', type: 'warning' }
    )
  } catch {
    return
  }
  vlOffsetLoading.value = true
  showVlOffsetProgressDialog.value = true
  resetVlOffsetProgress('Cancel all Offsets', [
    { title: 'Validating payroll period', status: 'pending', message: 'Checking payroll period…' },
    { title: 'Cancelling all offsets', status: 'pending', message: 'Restoring late, undertime, and absent deductions and VL credits for all employees…' },
    { title: 'Finalizing', status: 'pending', message: 'Updating attendance view…' }
  ])
  try {
    updateVlOffsetProgressStep(0, 'active', 'Validating payroll period and identifying offset records…')
    updateVlOffsetProgressStep(0, 'completed', 'Validation completed.')
    updateVlOffsetProgressStep(1, 'active', 'Cancelling all offsets and restoring VL credits. Please wait…')
    const res = await processAttendanceService.cancelOffsetAll(Number(form.value.payroll_period_id))
    const data = res?.data ?? res
    const cancelled = data?.records_cancelled ?? 0
    const failed = data?.records_failed ?? 0
    const vlRestored = data?.total_vl_restored ?? 0
    updateVlOffsetProgressStep(1, 'completed', cancelled > 0 ? `Cancelled ${cancelled} record(s); ${Number(vlRestored).toFixed(3)} days restored to VL.` : 'No offsets to cancel.')
    updateVlOffsetProgressStep(2, 'active', 'Refreshing attendance data…')
    if (cancelled > 0) {
      emit('processed', { ...form.value })
      emit('reprocess-all', { ...form.value })
    }
    updateVlOffsetProgressStep(2, 'completed', 'View updated.')
    vlOffsetProgressComplete.value = true
    if (cancelled > 0) {
      ElMessage.success(res?.message || `Cancelled offsets for ${cancelled} record(s); ${Number(vlRestored).toFixed(3)} days restored to VL.${failed > 0 ? ` ${failed} failed.` : ''}`)
    } else {
      ElMessage.info(res?.message || 'No offsets to cancel in this period.')
    }
  } catch (e) {
    const msg = e?.response?.data?.message || e?.message || 'Cancel all offsets failed'
    vlOffsetProgressError.value = msg
    updateVlOffsetProgressStep(1, 'error', msg)
    ElMessage.error(msg)
  } finally {
    vlOffsetLoading.value = false
  }
}

// Offset Status modal state
const showOffsetStatusModal = ref(false)
const offsetStatusLoading = ref(false)
const offsetStatusData = ref({ offsetted: [], no_offset_yet: [] })

async function openOffsetStatusModal() {
  if (!canProcess.value) return
  if (!isAlreadyProcessed.value) {
    ElMessage.error('Period selected is not processed yet! Process the Period first.')
    return
  }
  showOffsetStatusModal.value = true
  offsetStatusLoading.value = true
  offsetStatusData.value = { offsetted: [], no_offset_yet: [] }
  try {
    const res = await processAttendanceService.getOffsetEmployeeSummary(Number(form.value.payroll_period_id))
    const data = res?.data ?? res
    offsetStatusData.value = {
      offsetted: data?.offsetted ?? [],
      no_offset_yet: data?.no_offset_yet ?? []
    }
  } catch (e) {
    ElMessage.error(e?.response?.data?.message || e?.message || 'Failed to load offset status')
    showOffsetStatusModal.value = false
  } finally {
    offsetStatusLoading.value = false
  }
}

// View processed attendance without reprocessing
async function viewProcessedAttendance() {
  if (!canProcess.value) return
  if (!isAlreadyProcessed.value) {
    ElMessage.error('Period selected is not processed yet! Process the Period first.')
    return
  }
  
  viewLoading.value = true
  
  try {
    // Ensure time_data rows within the attendance window are assigned to this payroll period
    await processAttendanceService.syncTimeDataPayrollPeriod({
      payroll_period_id: Number(form.value.payroll_period_id),
    })
    
    // Load all data needed for the overview: employees and days present
    const [res, daysPresent] = await Promise.all([
      processAttendanceService.getEmployeeAttendanceData(form.value.payroll_period_id, { page: 1, per_page: BACKEND_PAGE_SIZE }),
      processAttendanceService.getDaysPresentSummary(form.value.payroll_period_id)
    ])
    
    const employees = res?.data ?? res
    const employeesArray = Array.isArray(employees) ? employees : []
    
    if (employeesArray.length > 0) {
      // Emit event with all data needed for overview
      emit('view-processed', {
        employees: employeesArray,
        daysPresent: daysPresent || {}
      })
      ElMessage.success(`Loaded ${employeesArray.length} processed employee records`)
    } else {
      ElMessage.info('No processed attendance data found for this payroll period')
      emit('view-processed', {
        employees: [],
        daysPresent: {}
      })
    }
  } catch (e) {
    ElMessage.error(e?.message || 'Failed to load processed attendance data')
    emit('view-processed', {
      employees: [],
      daysPresent: {}
    })
  } finally {
    viewLoading.value = false
  }
}

// Legacy functions kept for backward compatibility but not used in UI
// They are replaced by the combined processAttendance function

watch(() => form.value.payroll_interval_id, (val) => {
  if (!val) periods.value = []
})

</script>

<style scoped>
.compact-form {
  padding: 8px 0;
}

.inline-form {
  margin: 0;
}

.form-row {
  display: flex;
  align-items: flex-end;
  gap: 8px;
  flex-wrap: wrap;
}

.form-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 0;
}

.form-label {
  font-size: 14px;
  font-weight: 500;
  color: #606266;
  margin: 0;
  white-space: nowrap;
}

.dev-date-hint {
  margin: 6px 0 0;
  font-size: 12px;
  color: #909399;
  line-height: 1.4;
  max-width: 320px;
}

.dev-date-picker-row {
  display: flex;
  align-items: center;
  gap: 8px;
}

.dev-date-picker-wrap {
  position: relative;
  display: inline-block;
  vertical-align: middle;
}

.dev-date-picker-guard {
  position: absolute;
  inset: 0;
  z-index: 2;
  cursor: pointer;
  border-radius: 4px;
}

.dev-date-info-content {
  font-size: 12px;
  color: #606266;
  line-height: 1.4;
}

/* Responsive adjustments */
@media (max-width: 1200px) {
  .form-row {
    gap: 6px;
  }
  
  .form-item:first-child .el-select {
    width: 180px !important;
  }
  
  .form-item:nth-child(2) .el-select {
    width: 220px !important;
  }
}

@media (max-width: 768px) {
  .form-row {
    flex-direction: column;
    align-items: stretch;
    gap: 8px;
  }
  
  .form-item {
    width: 100%;
  }
  
  .form-item .el-select {
    width: 100% !important;
  }
  
  .form-item .el-button {
    width: 100%;
  }
}

/* Processing Progress Dialog Styles */
.processing-dialog :deep(.el-dialog__body) {
  padding: 24px;
}

.progress-content {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.process-attendance-actions {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 8px 12px;
}

.offset-status-content h4 {
  margin: 0 0 10px 0;
  font-size: 14px;
  color: #303133;
}
.offset-status-count {
  margin: 8px 0 0 0;
  font-size: 12px;
  color: #909399;
}
.process-attendance-hint {
  font-size: 12px;
  color: #909399;
  max-width: 320px;
}
:deep(.process-attendance-tooltip) {
  text-align: left;
  padding: 4px 0;
}
:deep(.process-attendance-tooltip ul) {
  margin: 6px 0 0 0;
  padding-left: 18px;
}
:deep(.process-attendance-tooltip li) {
  margin: 2px 0;
}

.progress-header {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding-bottom: 16px;
  border-bottom: 2px solid #e4e7ed;
}
.progress-header-text {
  flex: 1;
  min-width: 0;
}
.progress-subtitle {
  margin: 4px 0 0 0;
  font-size: 13px;
  color: #606266;
  font-weight: 500;
}
.progress-step-counter {
  margin: 2px 0 0 0;
  font-size: 12px;
  color: #909399;
}

.processing-icon {
  font-size: 24px;
  color: #409eff;
}

.processing-icon.is-loading {
  animation: rotate 2s linear infinite;
}

@keyframes rotate {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.progress-header h3 {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
  color: #303133;
}

.progress-steps {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.progress-step {
  display: flex;
  align-items: flex-start;
  gap: 16px;
  padding: 12px;
  border-radius: 8px;
  transition: all 0.3s ease;
}

.progress-step.pending {
  background: #f5f7fa;
  opacity: 0.6;
}

.progress-step.active {
  background: #ecf5ff;
  border: 1px solid #b3d8ff;
}

.progress-step.completed {
  background: #f0f9ff;
  border: 1px solid #b3e5fc;
}

.progress-step.error {
  background: #fef0f0;
  border: 1px solid #fbc4c4;
}

.step-indicator {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  font-weight: 600;
  font-size: 14px;
  transition: all 0.3s ease;
}

.progress-step.pending .step-indicator {
  background: #e4e7ed;
  color: #909399;
}

.progress-step.active .step-indicator {
  background: #409eff;
  color: white;
}

.progress-step.completed .step-indicator {
  background: #67c23a;
  color: white;
}

.progress-step.error .step-indicator {
  background: #f56c6c;
  color: white;
}

.progress-step.skipped .step-indicator {
  background: #e4e7ed;
  color: #909399;
}
.step-skipped-icon {
  font-size: 18px;
}

.step-number {
  display: inline-block;
}

.step-content {
  flex: 1;
  min-width: 0;
}

.step-title {
  font-size: 15px;
  font-weight: 600;
  color: #303133;
  margin-bottom: 4px;
}

.progress-step.pending .step-title {
  color: #909399;
}

.step-message {
  font-size: 13px;
  color: #606266;
  margin-top: 4px;
}

.progress-step.active .step-message {
  color: #409eff;
  font-weight: 500;
}

.progress-step.completed .step-message {
  color: #67c23a;
}

.progress-step.error .step-message {
  color: #f56c6c;
}

.error-message {
  margin-top: 8px;
}

.dialog-footer {
  display: flex;
  justify-content: flex-end;
  padding-top: 16px;
  border-top: 1px solid #e4e7ed;
}
</style>


