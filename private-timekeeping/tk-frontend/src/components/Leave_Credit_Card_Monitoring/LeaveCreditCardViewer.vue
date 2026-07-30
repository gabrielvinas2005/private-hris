<template>
  <el-dialog
    v-model="visible"
    title="Leave Credit Card Information"
    width="1200px"
    destroy-on-close
    align-center
    append-to-body
    :show-close="true"
    :close-on-click-modal="true"
    :close-on-press-escape="true"
  >
    <div class="viewer-content">
    <TableLoadingOverlay :loading="isViewerLoading" text="Loading leave credits..." />
    <!-- Header Information -->
    <div v-if="header" class="header-info">
      <div class="info-grid">
        <div class="info-item">
          <span class="label">Name:</span>
          <div class="name-with-photo">
            <EmployeeDataPopulate :employee="header" field="photo" />
            <span class="value name-value">
              <EmployeeDataPopulate :employee="header" field="name" />
            </span>
          </div>
        </div>
        <div class="info-item">
          <span class="label">Position:</span>
          <span class="value">{{ header.position }}</span>
        </div>
        <div class="info-item">
          <span class="label">Department:</span>
          <span class="value">{{ header.department || 'N/A' }}</span>
        </div>
        <div class="info-item">
          <span class="label">Leave Type:</span>
          <el-select 
            v-model="selectedLeaveTypeId" 
            size="small" 
            class="leave-type-select" 
            @change="onLeaveTypeChange"
            :loading="leaveTypesLoading"
            placeholder="Select leave type"
          >
            <el-option 
              v-for="lt in leaveTypesForDropdown" 
              :key="lt.id" 
              :label="lt.name" 
              :value="lt.id" 
            />
          </el-select>
        </div>
        <div class="info-item">
          <span class="label">Year:</span>
        <el-date-picker
          v-model="selectedYear"
          type="year"
          size="small"
          class="year-select"
          value-format="YYYY"
          format="YYYY"
          placeholder="Select year"
          @change="onYearChange"
        />
        </div>
      </div>
    </div>

    <!-- Form Layout -->
    <div class="form-container">
      <!-- Left Section: Month and Days -->
      <div class="left-section">
        <div class="form-section">
          <div class="section-header">
            <div class="header-cell">Month</div>
            <div class="header-cell">Days Present</div>
            <div class="header-cell">Days Absent</div>
          </div>
          <div class="section-body">
            <div v-for="(row, index) in displayRows" :key="index" class="data-row">
              <div class="data-cell">{{ row.month }}</div>
              <div class="data-cell">{{ row.days_present }}</div>
              <div class="data-cell">{{ row.days_absent }}</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Section: Leave Type Table -->
      <div class="right-section">
        <div class="leave-section" v-if="selectedLeaveType">
          <div class="leave-header">{{ selectedLeaveType.name }}</div>
          <div class="leave-subheader">
            <div class="subheader-cell">Month</div>
            <div class="subheader-cell">Balance</div>
            <div class="subheader-cell">Taken</div>
            <div class="subheader-cell">Accrued</div>
            <div class="subheader-cell">Earned</div>
          </div>
          <div class="leave-body">
            <div v-for="(row, index) in displayRows" :key="index" class="leave-row">
              <div class="leave-cell">{{ row.month }}</div>
              <!-- For CTO / service-credit leaves, show the current month's balance (not balance_previous) -->
              <div class="leave-cell">
                {{ formatNumber(isServiceCreditLeave ? row.balance : row.balance_previous) }}
              </div>
              <div class="leave-cell">{{ formatNumber(row.with_pay) }}</div>
              <div class="leave-cell">{{ formatNumber(row.accrued) }}</div>
              <div class="leave-cell">{{ formatNumber(row.earned) }}</div>
            </div>
          </div>
        </div>
        <div v-else class="leave-section">
          <div class="leave-header">No Leave Type Selected</div>
          <div class="leave-body" style="padding: 20px; text-align: center; color: #909399;">
            Please select a leave type to view credit card information
          </div>
        </div>
      </div>
    </div>
    </div>

    <template #footer>
      <div class="dialog-footer">
        <el-button @click="handleClose">Close</el-button>
        <el-button 
          type="primary" 
          @click="saveCurrentMonthCredits" 
          :loading="saving"
          :disabled="!canSaveCurrentMonth"
        >
          {{ saveButtonLabel }}
        </el-button>
      </div>
    </template>
  </el-dialog>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { leaveCreditCardService, leaveCreditsService } from '@/services/api'
import { ElMessage, ElMessageBox } from 'element-plus'
import TableLoadingOverlay from '../Reusable_Components/TableLoadingOverlay.vue'
import EmployeeDataPopulate from '../Reusable_Components/Employee_Data_Populate.vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  header: { type: Object, default: null },
  rows: { type: Array, default: () => [] },
  beginningBalances: { type: Array, default: () => [] },
  timeData: { type: Array, default: () => [] },
  year: { type: Number, default: () => new Date().getFullYear() },
  loading: { type: Boolean, default: false }
})

const emit = defineEmits(['update:modelValue', 'change-year', 'change-leave-type', 'credits-saved'])

const visible = computed({
  get: () => props.modelValue,
  set: (val) => emit('update:modelValue', val)
})

const selectedYear = ref(String(props.year))
const selectedLeaveTypeId = ref(null)
const leaveTypes = ref([])
const leaveTypesLoading = ref(false)
const saving = ref(false)
const isViewerLoading = computed(() => props.loading || leaveTypesLoading.value)
/** LWOP — not shown in this dialog's leave type list (id 13) */
const EXCLUDED_DROPDOWN_LEAVE_TYPE_ID = 13
const leaveTypeNumericId = (lt) => Number(lt?.id ?? lt?.leave_type_id)

const leaveTypesForDropdown = computed(() =>
  leaveTypes.value.filter((lt) => leaveTypeNumericId(lt) !== EXCLUDED_DROPDOWN_LEAVE_TYPE_ID)
)

const LEAVE_POLICY_RESET_YEARLY = 1
const LEAVE_POLICY_MAX_DAYS_PER_AVAILMENT = 2
const LEAVE_POLICY_CARRY_YEARLY = 3
const ACCRUAL_FREQUENCY_NONE = 0
const ACCRUAL_FREQUENCY_MONTHLY = 1
const ACCRUAL_FREQUENCY_YEARLY = 2

// Load leave types on mount and when header changes
onMounted(async () => {
  await loadLeaveTypes()
})

watch(() => props.header?.id, async (newId) => {
  if (newId) {
    await loadLeaveTypes()
  }
}, { immediate: false })

const loadLeaveTypes = async () => {
  leaveTypesLoading.value = true
  try {
    const response = await leaveCreditCardService.getLeaveTypes()
    let typesArray = []
    if (Array.isArray(response)) {
      typesArray = response
    } else if (Array.isArray(response?.data)) {
      typesArray = response.data
    }

    // Filter to only show active leave types (active = 1); omit LWOP (id 13) from UI
    leaveTypes.value = typesArray.filter((lt) => {
      if (leaveTypeNumericId(lt) === EXCLUDED_DROPDOWN_LEAVE_TYPE_ID) return false
      if (lt.active === undefined || lt.active === null) return true
      return lt.active === 1 || lt.active === true || lt.active === '1'
    })

    const allowedIds = new Set(leaveTypes.value.map((lt) => leaveTypeNumericId(lt)))
    const needDefault =
      leaveTypes.value.length > 0 &&
      (selectedLeaveTypeId.value == null ||
        !allowedIds.has(Number(selectedLeaveTypeId.value)))
    if (needDefault) {
      const first = leaveTypes.value[0]
      selectedLeaveTypeId.value = first?.id ?? null
      if (first != null) emit('change-leave-type', first.id)
    }
  } catch (err) {
    ElMessage.error(err?.message || 'Failed to load leave types')
    leaveTypes.value = [] // Ensure empty array on error
  } finally {
    leaveTypesLoading.value = false
  }
}

const selectedLeaveType = computed(() => {
  const id = selectedLeaveTypeId.value
  return leaveTypes.value.find(lt => Number(lt.id) === Number(id)) || null
})

// Detect if the selected leave type is a service-credit / CTO-type leave
const isServiceCreditLeave = computed(() => {
  if (!selectedLeaveType.value) return false
  const serviceCreditFlag = Number(selectedLeaveType.value.service_credit)
  if (serviceCreditFlag === 1) return true
  const leaveTypeId = selectedLeaveType.value.id
  const name = (selectedLeaveType.value.name || '').toLowerCase()

  // Known CTO / service-credit IDs
  if ([4, 10].includes(leaveTypeId)) return true

  // Fallback by name
  if (name.includes('cto') || name.includes('compensatory time-off')) return true

  return false
})

const getLeaveBalancePolicyId = (leaveType) => {
  return Number(leaveType?.leave_balance_policy_id) || 0
}

const getAccrualFrequencyId = (leaveType) => {
  return Number(leaveType?.accrual_frequency_id)
}

const getLeaveTypeAccrualAmount = (leaveType) => {
  return Number(leaveType?.accrual_amount) || 0
}

const applyAccrualFrequency = (leaveType, monthId, monthlyAccrued) => {
  const frequencyId = getAccrualFrequencyId(leaveType)
  if (frequencyId === ACCRUAL_FREQUENCY_NONE) return 0
  if (frequencyId === ACCRUAL_FREQUENCY_YEARLY) {
    return monthId === 1 ? getLeaveTypeAccrualAmount(leaveType) : 0
  }
  // Default behavior: monthly accrual
  return monthlyAccrued
}

const normalizeJanuaryForResetPolicy = (leaveType, monthId, balancePrevious, accrued) => {
  const leavePolicyId = getLeaveBalancePolicyId(leaveType)
  const accrualFrequencyId = getAccrualFrequencyId(leaveType)
  if (monthId !== 1 || leavePolicyId !== LEAVE_POLICY_RESET_YEARLY) {
    return { balancePrevious, accrued }
  }

  // Reset-yearly + yearly grant: January starts from configured yearly amount.
  // Accrued is zeroed to avoid double-counting the annual grant.
  if (accrualFrequencyId === ACCRUAL_FREQUENCY_YEARLY) {
    return {
      balancePrevious: getLeaveTypeAccrualAmount(leaveType),
      accrued: 0
    }
  }

  return { balancePrevious: 0, accrued }
}

// Check if the selected leave type is Sick Leave or Vacation Leave
// Supports multiple ID schemes and name matching
const isSickOrVacationLeave = computed(() => {
  if (!selectedLeaveType.value) return false
  const leaveTypeName = (selectedLeaveType.value.name || '').toLowerCase()
  const leaveTypeId = selectedLeaveType.value.id
  
  // Check by ID: 1, 2, or 16
  // NOTE: 3 (Special Privilege Leave) must not use Sick/Vacation accrual override.
  if ([1, 2, 16].includes(leaveTypeId)) return true
  
  // Check by name
  if (leaveTypeName.includes('sick leave') || leaveTypeName.includes('vacation leave')) return true
  
  return false
})

const onLeaveTypeChange = (leaveTypeId) => {
  if (!leaveTypeId) return
  emit('change-leave-type', leaveTypeId)
}

watch(() => props.year, (newYear) => {
  selectedYear.value = String(newYear)
})

const onYearChange = (year) => {
  const yearNumber = Number(year)
  if (!yearNumber) return
  selectedYear.value = String(yearNumber)
  emit('change-year', yearNumber)
}

const handleClose = () => {
  visible.value = false
}

// Get current month (1-12)
const currentMonthId = computed(() => {
  const now = new Date()
  return now.getMonth() + 1 // getMonth() returns 0-11, we need 1-12
})

// Current month name and year for the button label (always today's month/year)
const currentMonthName = computed(() => {
  const m = allMonthsTemplate.find(mo => mo.month_id === currentMonthId.value)
  return m ? m.month : ''
})
const currentYearForSave = computed(() => new Date().getFullYear())

const saveButtonLabel = computed(() => {
  const month = currentMonthName.value
  const year = currentYearForSave.value
  return month && year ? `Save ${month} ${year}'s Credits` : 'Save Current Month\'s Credits'
})

// Check if we can save the current month's credits (save applies to current month/year, all active leave types)
const canSaveCurrentMonth = computed(() => {
  if (!props.header?.id) return false
  if (!leaveTypes.value || leaveTypes.value.length === 0) return false
  const currentYear = new Date().getFullYear()
  if (Number(selectedYear.value) !== currentYear) return false
  return true
})

// Save current month's earned credits for ALL active leave types in one go (not just the dropdown selection)
const saveCurrentMonthCredits = async () => {
  try {
    if (!props.header?.id) {
      ElMessage.warning('No employee selected')
      return
    }

    if (!leaveTypes.value || leaveTypes.value.length === 0) {
      ElMessage.warning('No leave types available')
      return
    }

    const currentYear = currentYearForSave.value
    const currentMonthTemplate = allMonthsTemplate.find(m => m.month_id === currentMonthId.value)
    if (!currentMonthTemplate) {
      ElMessage.warning('Invalid current month')
      return
    }
    const monthName = currentMonthTemplate.month

    // Fetch beginning balances for ALL active leave types (parent only passes data for selected dropdown type)
    saving.value = true
    let allBeginningBalances = []
    try {
      const detailsPromises = leaveTypes.value.map(lt =>
        leaveCreditCardService.loadDetails(props.header.id, { leave_type_id: lt.id, year: currentYear })
      )
      const detailsResults = await Promise.all(detailsPromises)
      for (const res of detailsResults) {
        const balances = res?.beginning_balances ?? res?.beginningBalances ?? []
        if (Array.isArray(balances)) allBeginningBalances = allBeginningBalances.concat(balances)
      }
    } catch (err) {
      ElMessage.error(err?.message || 'Failed to load data for all leave types')
      saving.value = false
      return
    }

    // Days absent for current month (from time data; same for all leave types)
    const currentMonthRow = displayRows.value.find(row => row.month_id === currentMonthId.value)
    const daysAbsent = (currentMonthRow && (currentMonthRow.days_absent !== undefined && currentMonthRow.days_absent !== null))
      ? currentMonthRow.days_absent
      : 0

    // Days absent per month (independent of leave type). We reuse displayRows which already
    // computed days_absent from time data; this keeps the accrual override consistent.
    const daysAbsentByMonth = new Map()
    displayRows.value.forEach(r => {
      if (r && r.month_id) daysAbsentByMonth.set(Number(r.month_id), Number(r.days_absent) || 0)
    })

    const isSickOrVacationType = (leaveType) => {
      const leaveTypeName = (leaveType?.name || '').toLowerCase()
      return [1, 2, 16].includes(Number(leaveType?.id)) ||
        leaveTypeName.includes('sick leave') ||
        leaveTypeName.includes('vacation leave')
    }

    const isServiceCreditType = (leaveType) => {
      const id = Number(leaveType?.id)
      const name = (leaveType?.name || '').toLowerCase()
      return [4, 10].includes(id) || name.includes('cto') || name.includes('compensatory time-off')
    }

    // Compute "Earned" for a leave type using the same logic as the table.
    const computeEarnedForTypeMonth = (leaveType, monthId) => {
      const ltId = Number(leaveType.id)
      const rowsForType = allBeginningBalances
        .filter(b => Number(b.leave_type_id) === ltId)
        .map(b => ({ ...b, month_id: Number(b.month_id) }))
        .sort((a, b) => (a.month_id || 0) - (b.month_id || 0))

      if (rowsForType.length === 0) return null

      const targetRow = rowsForType.find(r => r.month_id === monthId)
      const leavePolicyId = getLeaveBalancePolicyId(leaveType)
      // CTO / service-credit: balance_previous is 0 for all months; use backend "earned" for this month.
      if (isServiceCreditType(leaveType) && targetRow) {
        return Number(targetRow.earned) || 0
      }
      // Maximum-days-per-availment: rely on backend per-month values directly.
      if (leavePolicyId === LEAVE_POLICY_MAX_DAYS_PER_AVAILMENT && targetRow) {
        return Number(targetRow.earned) || 0
      }

      // Find first month where a beginning balance exists (standard leave types)
      let firstMonthWithBalance = null
      for (let m = 1; m <= 12; m++) {
        const br = rowsForType.find(r => r.month_id === m)
        if (br && (Number(br.balance_previous) || 0) > 0) {
          firstMonthWithBalance = m
          break
        }
      }
      if (firstMonthWithBalance === null) return 0

      const startRow = rowsForType.find(r => r.month_id === firstMonthWithBalance)
      let runningBalance = Number(startRow?.balance_previous) || 0

      for (let m = firstMonthWithBalance; m <= monthId; m++) {
        const row = rowsForType.find(r => r.month_id === m)
        if (!row) continue

        const taken = Number(row.with_pay) || 0
        const withoutPay = Number(row.without_pay) || 0
        let balancePrevious = (m === firstMonthWithBalance)
          ? (Number(row.balance_previous) || 0)
          : runningBalance

        let accrued = Number(row.accrued) || 0
        if (isSickOrVacationType(leaveType)) {
          accrued = calculateAccrualByAbsences(currentYear, m, daysAbsentByMonth.get(m) || 0)
        }
        accrued = applyAccrualFrequency(leaveType, m, accrued)
        const januaryNormalized = normalizeJanuaryForResetPolicy(leaveType, m, balancePrevious, accrued)
        balancePrevious = januaryNormalized.balancePrevious
        accrued = januaryNormalized.accrued

        const earned = (balancePrevious - taken) + accrued
        const balance = Math.max(0, earned - withoutPay)
        runningBalance = balance

        if (m === monthId) return earned
      }

      return null
    }

    // Build earned values for ALL leave types for the current month
    const creditsToSave = []
    for (const leaveType of leaveTypes.value) {
      // Use the same running-balance computation as the table to get the correct Earned for this month.
      const earned = computeEarnedForTypeMonth(leaveType, currentMonthId.value)
      if (earned !== undefined && earned !== null) {
        creditsToSave.push({
          leave_type_id: leaveType.id,
          leave_type_name: leaveType.name,
          earned: earned
        })
      }
    }

    if (creditsToSave.length === 0) {
      ElMessage.warning(`No earned credits found for ${monthName}`)
      return
    }

    // Build confirmation message with all leave types and their earned values
    const creditsSummary = creditsToSave
      .map(c => `  • ${c.leave_type_name}: ${c.earned.toFixed(2)}`)
      .join('<br>')
    
    const confirmMessage = `Saving will replace the following credits based on ${monthName} ${selectedYear.value}'s Earned Leave for ${props.header.name}:<br><br>${creditsSummary}<br><br>Do you want to continue?`
    
    // Confirm with user
    const confirmed = await ElMessageBox.confirm(
      confirmMessage,
      'Confirm Save All Leave Types',
      {
        confirmButtonText: 'Save All',
        cancelButtonText: 'Cancel',
        type: 'warning',
        dangerouslyUseHTMLString: true
      }
    ).catch(() => false)
    
    if (!confirmed) return
    
    saving.value = true
    
    // Save each leave type's credits
    let savedCount = 0
    const failedLeaveTypes = []
    for (const credit of creditsToSave) {
      try {
        await leaveCreditsService.saveCreditsBulk(
          credit.leave_type_id,
          [props.header.id],
          [credit.earned]
        )
        savedCount++
      } catch (err) {
        failedLeaveTypes.push(credit.leave_type_name)
        // Continue with other leave types even if one fails
      }
    }
    
    if (savedCount > 0) {
      ElMessage.success(`Successfully saved credits for ${savedCount} leave type(s)`)
      // Emit event to notify parent to refresh data
      emit('credits-saved', { employeeId: props.header.id, month: currentMonthId.value, year: selectedYear.value })
    } else {
      ElMessage.error('Failed to save any leave credits')
    }
    
    if (failedLeaveTypes.length > 0) {
      ElMessage.warning(`Failed to save: ${failedLeaveTypes.join(', ')}`)
    }
  } catch (error) {
    ElMessage.error(error?.message || 'Failed to save leave credits')
  } finally {
    saving.value = false
  }
}

// Create template for all 12 months
const allMonthsTemplate = [
  { month: 'January', month_id: 1 },
  { month: 'February', month_id: 2 },
  { month: 'March', month_id: 3 },
  { month: 'April', month_id: 4 },
  { month: 'May', month_id: 5 },
  { month: 'June', month_id: 6 },
  { month: 'July', month_id: 7 },
  { month: 'August', month_id: 8 },
  { month: 'September', month_id: 9 },
  { month: 'October', month_id: 10 },
  { month: 'November', month_id: 11 },
  { month: 'December', month_id: 12 }
]

// Map backend rows to viewer rows matching required columns
const displayRows = computed(() => {
  const backendData = Array.isArray(props.rows) ? props.rows : []
  const beginningBalances = Array.isArray(props.beginningBalances) ? props.beginningBalances : []
  const targetYear = Number(selectedYear.value) || new Date().getFullYear()
  const timeDataEntries = Array.isArray(props.timeData) ? props.timeData : []

  const timeDataByMonth = new Map()
  timeDataEntries.forEach((entry) => {
    if (!entry || !entry.date) return
    const dateObj = toDate(entry.date)
    if (!dateObj) return
    if (dateObj.getFullYear() !== targetYear) return
    const monthId = dateObj.getMonth() + 1
    if (!timeDataByMonth.has(monthId)) timeDataByMonth.set(monthId, [])
    timeDataByMonth.get(monthId).push({
      absent: Number(entry.absent) || 0,
      leave: Number(entry.leave) || 0,
      work_hours: entry.work_hours !== null && entry.work_hours !== undefined ? Number(entry.work_hours) : null
    })
  })
  
  // Create a map of backend data by month_id for quick lookup
  const dataMap = new Map()
  backendData.forEach((r) => {
    if (r.month_id) {
      const monthId = Number(r.month_id)
      dataMap.set(monthId, r)
    }
  })
  
  // Create a map of beginning balance data by month_id for the selected leave type
  // Use Number() so backend number 10 matches select value "10" or 10
  const selectedLtId = Number(selectedLeaveTypeId.value)
  const balanceMap = new Map()
  beginningBalances.forEach((b) => {
    if (b.month_id != null && Number(b.leave_type_id) === selectedLtId) {
      const monthId = Number(b.month_id)
      balanceMap.set(monthId, b)
    }
  })

  // For service-credit leave (e.g. leave_type 10 CTO), Balance comes only from backend:
  // sum of approved overtime_applications.total_hours (converted to days) per month.
  const useServiceCreditBackendValues = isServiceCreditLeave.value
  const selectedLeaveTypeData = leaveTypes.value.find(lt => Number(lt.id) === selectedLtId) || null
  const selectedLeavePolicyId = getLeaveBalancePolicyId(selectedLeaveTypeData)
  const useMaxAvailmentBackendValues = selectedLeavePolicyId === LEAVE_POLICY_MAX_DAYS_PER_AVAILMENT

  // Find the first month where the employee has a beginning balance (balance_previous > 0)
  // Months before this should show Accrued = 0 and Earned = 0 (false data otherwise)
  let firstMonthWithBalance = null
  if (!useServiceCreditBackendValues && !useMaxAvailmentBackendValues) {
    for (let m = 1; m <= 12; m++) {
      const br = balanceMap.get(m)
      if (br && (Number(br.balance_previous) || 0) > 0) {
        firstMonthWithBalance = m
        break
      }
    }
  }
  const hasBeginningBalanceInYear = firstMonthWithBalance !== null

  // Create all 12 months with proper data mapping.
  // For service-credit leave we use backend balance/accrued/earned directly (from OT total_hours).
  // Otherwise we compute a running balance so each month's Earned becomes next month's Balance Previous.
  let runningBalance = 0
  if (!useServiceCreditBackendValues && !useMaxAvailmentBackendValues && firstMonthWithBalance !== null) {
    const startRow = balanceMap.get(firstMonthWithBalance)
    runningBalance = Number(startRow?.balance_previous) || 0
  }

  const result = []
  for (const monthTemplate of allMonthsTemplate) {
    const backendRow = dataMap.get(monthTemplate.month_id)
    const balanceRow = balanceMap.get(monthTemplate.month_id)
    const monthlyTimeEntries = timeDataByMonth.get(monthTemplate.month_id) || []

    let computedDaysPresent = 0
    let computedDaysAbsent = 0
    let computedDaysOnLeave = 0

    if (monthlyTimeEntries.length > 0) {
      // Days Present: count days where work_hours > 0 OR leave > 0 (days on leave count as present)
      computedDaysPresent = monthlyTimeEntries.filter(entry => {
        const workHours = entry.work_hours !== null && entry.work_hours !== undefined ? Number(entry.work_hours) : 0
        const leave = Number(entry.leave) || 0
        return workHours > 0 || leave > 0
      }).length
      computedDaysAbsent = monthlyTimeEntries.filter(entry => entry.absent === 1).length
      computedDaysOnLeave = monthlyTimeEntries.filter(entry => entry.leave === 1).length
    }

    // Calculate days present (use computed if available, otherwise fallback)
    const finalDaysPresent = monthlyTimeEntries.length > 0
      ? computedDaysPresent
      : (Number(backendRow?.days_present) || Number(balanceRow?.days_present) || 0)

    const finalDaysAbsent = monthlyTimeEntries.length > 0
      ? computedDaysAbsent
      : (Number(backendRow?.absent) || Number(balanceRow?.absent) || 0)

    // If no beginning balance exists in this year OR this month is before the first balance month,
    // Accrued and Earned must stay 0.
    const isBeforeFirstBalanceMonth = !hasBeginningBalanceInYear || monthTemplate.month_id < firstMonthWithBalance

    // "Taken" is based on leave_headers with matching leave_type_id (already calculated by backend)
    const taken = Number(balanceRow?.with_pay) || 0
    const withoutPay = Number(balanceRow?.without_pay) || 0

    let balancePrevious
    let accrued
    let earned
    let balance

    if ((useServiceCreditBackendValues || useMaxAvailmentBackendValues) && balanceRow) {
      // Leave type 10 (CTO): use backend values from overtime_applications.total_hours (converted to days)
      balancePrevious = Number(balanceRow.balance_previous) || 0
      accrued = Number(balanceRow.accrued) || 0
      earned = Number(balanceRow.earned) || 0
      balance = Number(balanceRow.balance) || 0
    } else {
      // Standard leave: running balance and accrual logic
      balancePrevious = isBeforeFirstBalanceMonth
        ? 0
        : ((monthTemplate.month_id === firstMonthWithBalance)
            ? (Number(balanceRow?.balance_previous) || 0)
            : runningBalance)

      accrued = isBeforeFirstBalanceMonth ? 0 : (Number(balanceRow?.accrued) || 0)
      if (!isBeforeFirstBalanceMonth && isSickOrVacationLeave.value) {
        accrued = calculateAccrualByAbsences(targetYear, monthTemplate.month_id, finalDaysAbsent)
      }
      accrued = isBeforeFirstBalanceMonth ? 0 : applyAccrualFrequency(selectedLeaveTypeData, monthTemplate.month_id, accrued)
      if (!isBeforeFirstBalanceMonth) {
        const januaryNormalized = normalizeJanuaryForResetPolicy(
          selectedLeaveTypeData,
          monthTemplate.month_id,
          balancePrevious,
          accrued
        )
        balancePrevious = januaryNormalized.balancePrevious
        accrued = januaryNormalized.accrued
      }

      earned = isBeforeFirstBalanceMonth ? 0 : (balancePrevious - taken) + accrued
      balance = Math.max(0, earned - withoutPay)
    }

    if (!useServiceCreditBackendValues && !useMaxAvailmentBackendValues && !isBeforeFirstBalanceMonth) {
      runningBalance = balance
    }

    result.push({
      month: monthTemplate.month,
      month_id: monthTemplate.month_id,
      days_present: finalDaysPresent,
      days_absent: finalDaysAbsent,
      days_on_leave: computedDaysOnLeave,
      with_pay: taken,
      without_pay: withoutPay,
      balance_previous: balancePrevious,
      accrued,
      earned,
      balance
    })
  }
  
  return result
})

function toDate(value) {
  if (!value) return null
  const v = typeof value === 'string' ? value.replace(' .000', '').replace('.000', '').replace(' ', 'T') : value
  const d = new Date(v)
  return isNaN(d.getTime()) ? null : d
}

// Get total days in a month for a given year and month
const getDaysInMonth = (year, month) => {
  // month is 1-12
  return new Date(year, month, 0).getDate()
}

// Accrual lookup table based on days (days present = total days - days absent)
// Returns the accrual amount for both Vacation Leave and Sick Leave
const getAccrualByDays = (days) => {
  const accrualTable = {
    1: 0.042,
    2: 0.083,
    3: 0.125,
    4: 0.167,
    5: 0.208,
    6: 0.250,
    7: 0.292,
    8: 0.333,
    9: 0.375,
    10: 0.417,
    11: 0.458,
    12: 0.500,
    13: 0.542,
    14: 0.583,
    15: 0.625,
    16: 0.667,
    17: 0.708,
    18: 0.750,
    19: 0.792,
    20: 0.833,
    21: 0.875,
    22: 0.917,
    23: 0.958,
    24: 1.000,
    25: 1.042,
    26: 1.083,
    27: 1.125,
    28: 1.167,
    29: 1.208,
    30: 1.250
  }
  
  const dayCount = Math.floor(Number(days) || 0)
  // If days is 0 or less, return 0
  if (dayCount <= 0) return 0
  // If days is 30 or more, return 1.250 (max accrual from table)
  if (dayCount >= 30) return 1.250
  // Return the accrual from the table
  return accrualTable[dayCount] || 0
}

// Calculate accrual based on 30 days minus days absent
// For Sick Leave and Vacation Leave only
const calculateAccrualByAbsences = (year, month, daysAbsent) => {
  // New rule: always base accrual on 30 days
  // daysForAccrual = 30 - daysAbsent
  const daysForAccrual = 30 - (Number(daysAbsent) || 0)
  
  return getAccrualByDays(daysForAccrual)
}

// Calculate leave balance (earned - used)
const calculateLeaveBalance = (earned, used) => {
  const earnedNum = Number(earned || 0)
  const usedNum = Number(used || 0)
  return Math.max(0, earnedNum - usedNum)
}

// Format numbers for display
const formatNumber = (value) => {
  if (value === null || value === undefined || value === '') return '-'
  const num = Number(value)
  return isNaN(num) ? '-' : num.toFixed(3)
}
</script>

<style scoped>
.viewer-content {
  position: relative;
  min-height: 120px;
}

/* Header Information */
.header-info {
  margin-bottom: 20px;
  padding: 16px;
  background-color: #f5f7fa;
  border-radius: 6px;
  border: 1px solid #e4e7ed;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 16px;
  align-items: center;
}

.leave-type-select {
  width: 100%;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.info-item .label {
  font-size: 12px;
  color: #606266;
  font-weight: 500;
}

.info-item .value {
  font-size: 14px;
  color: #303133;
  font-weight: 600;
}

.name-with-photo {
  display: flex;
  align-items: center;
  gap: 8px;
}

.name-with-photo :deep(.photo-container) {
  flex-shrink: 0;
}

.name-value :deep(.name) {
  font-size: 14px;
  color: #303133;
  font-weight: 600;
}

.year-select {
  width: 100%;
}

/* Form Container */
.form-container {
  display: flex;
  gap: 20px;
  min-height: 500px;
}

/* Left Section */
.left-section {
  flex: 0 0 300px;
}

.form-section {
  border: 2px solid #ddd;
  border-radius: 4px;
  overflow: hidden;
}

.section-header {
  display: grid;
  grid-template-columns: 1.5fr 1fr 1fr;
  background-color: #f0f0f0;
  border-bottom: 1px solid #ddd;
}

.header-cell {
  padding: 12px 8px;
  font-weight: 600;
  font-size: 14px;
  text-align: center;
  border-right: 1px solid #ddd;
}

.header-cell:last-child {
  border-right: none;
}

.section-body {
  background-color: #fff;
}

.data-row {
  display: grid;
  grid-template-columns: 1.5fr 1fr 1fr;
  border-bottom: 1px solid #ddd;
}

.data-row:last-child {
  border-bottom: none;
}

.data-cell {
  padding: 12px 8px;
  text-align: center;
  font-size: 14px;
  border-right: 1px solid #ddd;
  min-height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.data-cell:last-child {
  border-right: none;
}

/* Right Section */
.right-section {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.leave-section {
  border: 2px solid #ddd;
  border-radius: 4px;
  overflow: hidden;
  flex: 1;
}

.leave-header {
  background-color: #ddd;
  padding: 12px;
  font-weight: 600;
  font-size: 14px;
  text-align: center;
  border-bottom: 2px solid #ddd;
}

.leave-subheader {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr 1fr 1fr;
  background-color: #e8e8e8;
  border-bottom: 1px solid #ddd;
}

.subheader-cell {
  padding: 8px;
  font-weight: 500;
  font-size: 12px;
  text-align: center;
  border-right: 1px solid #ddd;
}

.subheader-cell:last-child {
  border-right: none;
}

.leave-body {
  background-color: #fff;
  min-height: 200px;
}

.leave-row {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr 1fr 1fr;
  border-bottom: 1px solid #ddd;
}

.leave-row:last-child {
  border-bottom: none;
}

.compensatory-subheader {
  grid-template-columns: 1fr 1fr 1fr;
}

.compensatory-row {
  grid-template-columns: 1fr 1fr 1fr;
}

.leave-cell {
  padding: 12px 8px;
  text-align: center;
  font-size: 14px;
  border-right: 1px solid #ddd;
  min-height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.leave-cell:last-child {
  border-right: none;
}

/* Dialog Footer */
.dialog-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  width: 100%;
}

/* Responsive adjustments */
@media (max-width: 1200px) {
  .form-container {
    flex-direction: column;
  }
  
  .left-section {
    flex: none;
  }
  
  .right-section {
    flex-direction: row;
    gap: 10px;
  }
  
  .leave-section {
    flex: 1;
  }
}
</style>