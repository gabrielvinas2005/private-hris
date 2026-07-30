<template>
  <PageScaffold
    title="Leave Credit Card Monitoring"
    subtitle="Track and analyze leave credits by employees over time"
    :breadcrumbs="[{ label: 'Timekeeping Module', to: '/' }, { label: 'Leave Credit Card Monitoring' }]"
  >
    <div class="toolbar-row">
      <EmployeeSearchbar
        v-model="filters.search"
        :loading="loading"
        :placeholder="'Search employee or position...'"
        :show-reload-button="false"
        @search="handleSearch"
        @reload="loadList"
      />

      <!-- Filters for Position and Department -->
      <Filters
        v-model="filters"
        :filters="availableFilters"
        :loading="loading"
        @filter-change="handleFiltersChange"
      />
      <div class="flex-spacer"></div>
      <el-button type="primary" @click="showBeginningBalanceSetup = true">
        <el-icon><Setting /></el-icon>
        Set Beginning Balances
      </el-button>
      <el-button type="primary" @click="openBulkSaveDialog">
        Save All Employee's Leave Credits
      </el-button>
      <PreviewExport 
        :html-content="reportHtmlContent"
        :title="'Leave Credit Card Monitoring Report'"
        :filename="'leave_credit_card_monitoring_report'"
        :on-excel="handleExcelExport"
        :on-pdf="handlePdfExport"
        :on-word="handleWordExport"
        :loading="loading"
      />
    </div>

    <LeaveCreditCardTable 
      :items="items" 
      :loading="loading" 
      :pagination="pagination"
      @view="onView"
      @page-change="onPageChange"
      @per-page-change="onPerPageChange"
    />

    <!-- Lazy-loaded viewer: show quick loading dialog while chunk loads -->
    <component
      :is="LeaveCreditCardViewerComponent"
      v-if="LeaveCreditCardViewerComponent"
      v-model="viewer"
      :header="header"
      :rows="viewerRows"
      :beginning-balances="beginningBalances"
      :time-data="viewerTimeData"
      :year="year"
      :loading="viewerOpeningLoading"
      @change-year="onChangeYear"
      @change-leave-type="onLeaveTypeChange"
      @credits-saved="onCreditsSaved"
    />
    <el-dialog
      v-else
      v-model="viewer"
      title="Leave Credit Card Information"
      width="1200px"
      destroy-on-close
      align-center
      append-to-body
    >
      <div style="padding: 36px; text-align: center;">
        <el-spin />
        <div style="margin-top: 10px; color: #606266;">
          Loading leave credit card viewer...
        </div>
      </div>
    </el-dialog>

    <!-- Beginning Balance Setup Dialog -->
    <BeginningBalanceSetup
      v-model="showBeginningBalanceSetup"
      :employees="employeesForBeginningBalance"
      :leave-types="leaveTypes"
      @saved="handleBeginningBalanceSaved"
    />

    <!-- Bulk Save All Employees' Leave Credits -->
    <el-dialog
      v-model="bulkSaveDialogVisible"
      title="Save All Employee's Leave Credits"
      width="900px"
      destroy-on-close
      align-center
      append-to-body
    >
      <div v-if="bulkPreviewLoading" class="bulk-loading-panel">
        <el-spin />
        <div class="bulk-loading-title">
          Loading current month's leave credits for all employees
          <span v-if="bulkPreviewEmployeeProgress.totalEmployees">
            ({{ bulkPreviewEmployeeProgress.processed }} out of {{ bulkPreviewEmployeeProgress.totalEmployees }})
          </span>
          ...
        </div>
        <el-progress
          :percentage="bulkPreviewProgressPercentage"
          :stroke-width="14"
          status="success"
        />
        <div class="bulk-loading-current" v-if="bulkPreviewCurrentEmployee">
          Processing: <strong>{{ bulkPreviewCurrentEmployee }}</strong>
          <span v-if="bulkPreviewCurrentLeaveType">- {{ bulkPreviewCurrentLeaveType }}</span>
        </div>
        <div class="bulk-loading-list">
          <div
            v-for="employee in bulkPreviewEmployeeLogs"
            :key="employee.employee_id"
            class="bulk-loading-item"
          >
            <div class="bulk-loading-item-name" :class="employee.status">
              {{ employee.employee_name }}
            </div>
          </div>
        </div>
      </div>
      <div v-else>
        <div style="margin-bottom: 8px; font-size: 13px; color: #606266;">
          Showing earned leave credits for the current month ({{ bulkCurrentMonthLabel }}).
        </div>
        <el-table
          :data="bulkPreviewRows"
          height="400"
          v-loading="bulkSaveSaving"
          size="small"
        >
          <el-table-column prop="employee_name" label="Employee" min-width="220" />
          <el-table-column prop="leave_type_name" label="Leave Type" min-width="180" />
          <el-table-column
            prop="credit"
            label="Leave Credit (Current Month)"
            min-width="200"
          >
            <template #default="{ row }">
              {{ Number(row.credit || 0).toFixed(3) }}
            </template>
          </el-table-column>
        </el-table>

        <div style="margin-top: 16px;" v-if="bulkSaveSaving || bulkPreviewRows.length">
          <el-progress
            v-if="bulkSaveSaving"
            :percentage="bulkProgressPercentage"
            :stroke-width="14"
            status="success"
          />
          <div style="margin-top: 8px; font-size: 12px; color: #606266;">
            <span v-if="bulkSaveSaving">
              Updating {{ bulkProgress.processed }} / {{ bulkProgress.total }} record(s)
              (success: {{ bulkProgress.success }}, failed: {{ bulkProgress.failed }})
            </span>
            <span v-else>
              Total records ready to update: {{ bulkPreviewRows.length }}
            </span>
          </div>
        </div>
      </div>

      <template #footer>
        <el-button @click="bulkSaveDialogVisible = false" :disabled="bulkSaveSaving">
          Close
        </el-button>
        <el-button
          type="primary"
          @click="handleBulkSave"
          :loading="bulkSaveSaving"
          :disabled="bulkPreviewLoading || !bulkPreviewRows.length"
        >
          Update Leave Credits
        </el-button>
      </template>
    </el-dialog>
  </PageScaffold>
</template>

<script setup>
import { onMounted, ref, computed, watch, shallowRef, markRaw } from 'vue'
import { Setting } from '@element-plus/icons-vue'
import PageScaffold from '@/components/Reusable_Components/PageScaffold.vue'
import EmployeeSearchbar from '@/components/Reusable_Components/EmployeeSearchbar.vue'
import Filters from '@/components/Reusable_Components/Filters.vue'
import PreviewExport from '@/components/Reusable_Components/Preview&Export.vue'
import LeaveCreditCardTable from '@/components/Leave_Credit_Card_Monitoring/LeaveCreditCardTable.vue'
import BeginningBalanceSetup from '@/components/Leave_Credits/BeginningBalanceSetup.vue'
import { leaveCreditCardService, leaveCreditsService, api } from '@/services/api'
import { useFilterLogic } from '@/Composables/Filter_Logic'
import { formatEmployeeName } from '@/Composables/useNameFormatter'
import { ElMessage } from 'element-plus'

/** LWOP (13) is not selectable in leave credit UIs */
const filterLeaveTypesForSelection = (arr) =>
  (arr || []).filter((lt) => Number(lt?.id) !== 13)
const EXCLUDED_BULK_LEAVE_TYPE_ID = 13

const items = ref([])
const loading = ref(false)
const leaveTypes = ref([])
const employeesWithCredits = ref([])
const showBeginningBalanceSetup = ref(false)

// Filter state
const filters = ref({ 
  search: '', 
  positionId: null, 
  departmentId: null 
})

const pagination = ref({
  current_page: 1,
  per_page: 10,
  total: 0,
  last_page: 1,
  has_more_pages: false,
  from: 0,
  to: 0
})

// Use filter logic
const { filterEmployees } = useFilterLogic()

// Available filters configuration
const availableFilters = computed(() => [
  {
    key: 'positionId',
    label: 'Position',
    valueKey: 'id',
    labelKey: 'name'
  },
  {
    key: 'departmentId',
    label: 'Department',
    valueKey: 'id',
    labelKey: 'name'
  }
])

const viewer = ref(false)
const header = ref(null)
const viewerRows = ref([])
const beginningBalances = ref([])
const viewerTimeData = ref([])
const year = ref(new Date().getFullYear())
const viewerOpeningLoading = ref(false)

// Lazy-load the viewer component so the dialog can show a loader immediately
const LeaveCreditCardViewerComponent = shallowRef(null)
const ensureViewerComponentLoaded = async () => {
  if (LeaveCreditCardViewerComponent.value) return
  const mod = await import('@/components/Leave_Credit_Card_Monitoring/LeaveCreditCardViewer.vue')
  LeaveCreditCardViewerComponent.value = markRaw(mod.default)
}

// Bulk save all employees' leave credits (current month)
const bulkSaveDialogVisible = ref(false)
const bulkPreviewLoading = ref(false)
const bulkSaveSaving = ref(false)
const bulkPreviewRows = ref([])
const bulkProgress = ref({
  total: 0,
  processed: 0,
  success: 0,
  failed: 0
})

const bulkPreviewEmployeeProgress = ref({
  totalEmployees: 0,
  processed: 0
})
const bulkPreviewCurrentEmployee = ref('')
const bulkPreviewCurrentLeaveType = ref('')
const bulkPreviewEmployeeLogs = ref([])
let bulkPreviewPulseTimer = null

const bulkCurrentMonthLabel = computed(() => {
  const now = new Date()
  return now.toLocaleString('default', { month: 'long', year: 'numeric' })
})

const bulkProgressPercentage = computed(() => {
  if (!bulkProgress.value.total) return 0
  return Math.round((bulkProgress.value.processed / bulkProgress.value.total) * 100)
})

const bulkPreviewProgressPercentage = computed(() => {
  const total = bulkPreviewEmployeeProgress.value.totalEmployees
  const processed = bulkPreviewEmployeeProgress.value.processed
  if (!total) return 0
  return Math.round((processed / total) * 100)
})

const stopBulkPreviewPulse = () => {
  if (bulkPreviewPulseTimer) {
    clearInterval(bulkPreviewPulseTimer)
    bulkPreviewPulseTimer = null
  }
}

const startBulkPreviewPulse = (employees) => {
  stopBulkPreviewPulse()
  if (!Array.isArray(employees) || employees.length === 0) return

  let visualProcessed = 0
  let pointer = 0

  bulkPreviewPulseTimer = setInterval(() => {
    if (!bulkPreviewLoading.value) {
      stopBulkPreviewPulse()
      return
    }

    const maxVisualProcessed = Math.max(0, employees.length - 1)
    if (visualProcessed < maxVisualProcessed) {
      visualProcessed += 1
      bulkPreviewEmployeeProgress.value.processed = visualProcessed
    }

    const emp = employees[pointer % employees.length]
    bulkPreviewCurrentEmployee.value = emp?.name || `Employee #${emp?.id || ''}`
    bulkPreviewCurrentLeaveType.value = ''
    pointer += 1

    bulkPreviewEmployeeLogs.value = bulkPreviewEmployeeLogs.value.map((log, idx) => {
      if (idx < visualProcessed) return { ...log, status: 'completed' }
      if (idx === visualProcessed) return { ...log, status: 'processing' }
      return { ...log, status: 'pending' }
    })
  }, 180)
}

// --- Leave Credit Card helpers (kept consistent with LeaveCreditCardViewer.vue) ---
function toDate(value) {
  if (!value) return null
  const v = typeof value === 'string'
    ? value.replace(' .000', '').replace('.000', '').replace(' ', 'T')
    : value
  const d = new Date(v)
  return isNaN(d.getTime()) ? null : d
}

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
  if (dayCount <= 0) return 0
  if (dayCount >= 30) return 1.250
  return accrualTable[dayCount] || 0
}

const calculateAccrualByAbsences = (year, month, daysAbsent) => {
  const daysForAccrual = 30 - (Number(daysAbsent) || 0)
  return getAccrualByDays(daysForAccrual)
}

const ACCRUAL_FREQUENCY_NONE = 0
const ACCRUAL_FREQUENCY_MONTHLY = 1
const ACCRUAL_FREQUENCY_YEARLY = 2

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
  return monthlyAccrued
}

const LEAVE_POLICY_RESET_YEARLY = 1

const getLeaveBalancePolicyId = (leaveType) => {
  return Number(leaveType?.leave_balance_policy_id) || 0
}

const normalizeJanuaryForResetPolicy = (leaveType, monthId, balancePrevious, accrued) => {
  const leavePolicyId = getLeaveBalancePolicyId(leaveType)
  const accrualFrequencyId = getAccrualFrequencyId(leaveType)
  if (monthId !== 1 || leavePolicyId !== LEAVE_POLICY_RESET_YEARLY) {
    return { balancePrevious, accrued }
  }

  if (accrualFrequencyId === ACCRUAL_FREQUENCY_YEARLY) {
    return {
      balancePrevious: getLeaveTypeAccrualAmount(leaveType),
      accrued: 0
    }
  }

  return { balancePrevious: 0, accrued }
}

const isSickOrVacationType = (leaveType) => {
  const leaveTypeName = (leaveType?.name || '').toLowerCase()
  // NOTE: 3 (Special Privilege Leave) must not use Sick/Vacation accrual override.
  return [1, 2, 16].includes(leaveType?.id) ||
    leaveTypeName.includes('sick leave') ||
    leaveTypeName.includes('vacation leave')
}

const loadList = async (page = 1, perPage = 10, search = '', positionId = null, departmentId = null) => {
  loading.value = true
  try {
    const params = {
      page,
      per_page: perPage,
      search
    }
    
    // Add filter parameters if they exist
    if (positionId) params.position_id = positionId
    if (departmentId) params.department_id = departmentId
    
    const response = await leaveCreditCardService.listEmployees(params)
    items.value = response.data || []
    pagination.value = response.pagination || pagination.value
  } finally {
    loading.value = false
  }
}

const handleSearch = (searchValue) => {
  filters.value.search = searchValue
  // Debounce search to avoid too many API calls
  clearTimeout(searchTimeout.value)
  searchTimeout.value = setTimeout(() => {
    loadList(1, pagination.value.per_page, searchValue, filters.value.positionId, filters.value.departmentId)
  }, 500)
}

const handleFiltersChange = (newFilters) => {
  filters.value = {
    ...filters.value,
    ...newFilters
  }
  loadList(1, pagination.value.per_page, filters.value.search, filters.value.positionId, filters.value.departmentId)
}

const onPageChange = (page) => {
  loadList(page, pagination.value.per_page, filters.value.search, filters.value.positionId, filters.value.departmentId)
}

const onPerPageChange = (perPage) => {
  loadList(1, perPage, filters.value.search, filters.value.positionId, filters.value.departmentId)
}

// Load leave types for report
const loadLeaveTypesForReport = async () => {
  try {
    const types = await leaveCreditsService.loadAllLeaveTypes()
    const raw = Array.isArray(types) ? types : (types?.leave_types || [])
    leaveTypes.value = filterLeaveTypesForSelection(raw)
  } catch (e) {
    console.error('Error loading leave types for report:', e)
    leaveTypes.value = []
  }
}

// Load employees with leave credits for report
const loadEmployeesWithCreditsForReport = async () => {
  try {
    const filterParams = {
      search: filters.value.search,
      departmentId: filters.value.departmentId,
      employmentTypeId: null, // Include all employment types for report
      onlyActive: true,
      export: true // full list for report
    }
    
    const rows = await leaveCreditsService.listAllLeaveCredits(filterParams)
    
    // Group into employees with leave_credits[]
    const map = new Map()
    for (const r of rows) {
      if (!map.has(r.employee_id)) {
        map.set(r.employee_id, {
          id: r.employee_id,
          employee_no: r.employee_no,
          name: r.name,
          position: r.position_name || r.position_id,
          department: r.department_name || r.department_id,
          leave_credits: []
        })
      }
      map.get(r.employee_id).leave_credits.push({
        leave_type_id: r.leave_type_id,
        leave_type_name: r.leave_type_name || '',
        credits: Number(r.credits || 0)
      })
    }
    employeesWithCredits.value = Array.from(map.values())
  } catch (e) {
    console.error('Error loading employees with credits for report:', e)
    employeesWithCredits.value = []
  }
}

// Generate HTML content for preview - formatted per employee
const reportHtmlContent = computed(() => {
  if (!employeesWithCredits.value.length) {
    return '<p style="padding: 20px; text-align: center; color: #909399;">No data available for report. Please apply filters or wait for data to load.</p>'
  }
  
  let html = '<div style="padding: 20px; font-family: Arial, sans-serif;">'
  
  employeesWithCredits.value.forEach((employee, index) => {
    // Employee header
    html += `<div style="margin-bottom: 30px; page-break-inside: avoid;">`
    html += `<h3 style="margin: 0 0 10px 0; font-size: 16px; font-weight: bold; color: #303133;">${index + 1}. ${employee.name}</h3>`
    
    // Employee details (optional - can be removed if not needed)
    html += `<div style="margin-bottom: 10px; font-size: 12px; color: #606266;">`
    html += `<span>Employee No: ${employee.employee_no || 'N/A'}</span>`
    if (employee.position) html += ` | <span>Position: ${employee.position}</span>`
    if (employee.department) html += ` | <span>Department: ${employee.department}</span>`
    html += `</div>`
    
    // Leave credits list
    if (employee.leave_credits && employee.leave_credits.length > 0) {
      html += `<div style="margin-left: 20px; margin-top: 8px;">`
      employee.leave_credits.forEach(credit => {
        const creditsValue = credit.credits.toFixed(2)
        html += `<div style="margin-bottom: 5px; font-size: 14px; color: #303133;">`
        html += `<span style="font-weight: 500;">${credit.leave_type_name || 'Unknown Leave Type'}</span>`
        html += ` - <span style="font-weight: 600;">${creditsValue}</span>`
        html += `</div>`
      })
      html += `</div>`
    } else {
      html += `<div style="margin-left: 20px; margin-top: 8px; font-size: 14px; color: #909399; font-style: italic;">No leave credits available</div>`
    }
    
    html += `</div>`
    
    // Add separator line between employees (except last one)
    if (index < employeesWithCredits.value.length - 1) {
      html += `<hr style="border: none; border-top: 1px solid #e4e7ed; margin: 20px 0;" />`
    }
  })
  
  html += '</div>'
  
  return html
})

// Export functions
async function handleExcelExport() {
  try {
    const { API_BASE_URL } = await import('../../config/api')
    
    const reportData = {
      report_type: 'leave_credit_card_monitoring',
      data: {
        employees: employeesWithCredits.value || []
      },
      filename: 'leave_credit_card_monitoring_report'
    }
    
    // Get auth token
    const rawAuthToken = localStorage.getItem('auth_token')
    const rawDevToken = localStorage.getItem('dev_auth_token')
    let token = null
    if (rawAuthToken) {
      try {
        const parsed = JSON.parse(rawAuthToken)
        token = parsed?.token || rawAuthToken
      } catch (_) {
        token = rawAuthToken
      }
    } else if (rawDevToken) {
      try {
        const parsed = JSON.parse(rawDevToken)
        token = parsed?.token || rawDevToken
      } catch (_) {
        token = rawDevToken
      }
    }
    
    const response = await fetch(`${API_BASE_URL}/reports/excel`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ...(token ? { 'Authorization': `Bearer ${token}` } : {})
      },
      credentials: 'include',
      body: JSON.stringify(reportData)
    })
    
    if (!response.ok) {
      throw new Error('Failed to generate Excel report')
    }
    
    const blob = await response.blob()
    const url = URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = 'leave_credit_card_monitoring_report.xlsx'
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    URL.revokeObjectURL(url)
  } catch (err) {
    console.error('Excel export failed:', err)
    ElMessage.error('Failed to export Excel report')
  }
}

async function handlePdfExport() {
  // PDF export is handled by PreviewExport component using the HTML content
}

async function handleWordExport() {
  try {
    const { API_BASE_URL } = await import('../../config/api')
    
    const reportData = {
      report_type: 'leave_credit_card_monitoring',
      data: {
        employees: employeesWithCredits.value || []
      },
      filename: 'leave_credit_card_monitoring_report'
    }
    
    // Get auth token
    const rawAuthToken = localStorage.getItem('auth_token')
    const rawDevToken = localStorage.getItem('dev_auth_token')
    let token = null
    if (rawAuthToken) {
      try {
        const parsed = JSON.parse(rawAuthToken)
        token = parsed?.token || rawAuthToken
      } catch (_) {
        token = rawAuthToken
      }
    } else if (rawDevToken) {
      try {
        const parsed = JSON.parse(rawDevToken)
        token = parsed?.token || rawDevToken
      } catch (_) {
        token = rawDevToken
      }
    }
    
    const response = await fetch(`${API_BASE_URL}/reports/docx`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ...(token ? { 'Authorization': `Bearer ${token}` } : {})
      },
      credentials: 'include',
      body: JSON.stringify(reportData)
    })
    
    if (!response.ok) {
      throw new Error('Failed to generate Word report')
    }
    
    const blob = await response.blob()
    const url = URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = 'leave_credit_card_monitoring_report.docx'
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    URL.revokeObjectURL(url)
  } catch (err) {
    console.error('Word export failed:', err)
    ElMessage.error('Failed to export Word report')
  }
}

const searchTimeout = ref(null)

const onView = async (emp) => {
  viewer.value = true
  viewerOpeningLoading.value = true
  try {
    // Load viewer chunk + API data in parallel
    const loadComponentPromise = ensureViewerComponentLoaded()

    const loadDataPromise = (async () => {
      // Load leave types first to get default
      const leaveTypesRes = await leaveCreditCardService.getLeaveTypes()
      const leaveTypesArray = filterLeaveTypesForSelection(
        Array.isArray(leaveTypesRes) ? leaveTypesRes : (leaveTypesRes?.data || [])
      )
      const defaultLeaveTypeId = leaveTypesArray.length > 0 ? leaveTypesArray[0].id : null

      if (!defaultLeaveTypeId) {
        throw new Error('No active leave types found')
      }

      // Load details with default leave type
      currentLeaveTypeId.value = defaultLeaveTypeId
      const res = await leaveCreditCardService.loadDetails(emp.id, {
        leave_type_id: defaultLeaveTypeId,
        year: year.value
      })

      header.value = res.header
      viewerRows.value = res.table || []
      beginningBalances.value = res.beginningBalances || res.beginning_balances || []
      viewerTimeData.value = res.time_data || res.timeData || []
    })()

    await Promise.all([loadComponentPromise, loadDataPromise])
  } catch (e) {
    ElMessage.error(e?.message || 'Error loading details')
    viewer.value = false
  } finally {
    viewerOpeningLoading.value = false
  }
}

const currentLeaveTypeId = ref(null)

const onChangeYear = async (y) => {
  year.value = y
  
  if (!header.value?.id) {
    return
  }
  
  // Use current leave type or get first available
  let leaveTypeId = currentLeaveTypeId.value
  if (!leaveTypeId) {
    const leaveTypesRes = await leaveCreditCardService.getLeaveTypes()
    const leaveTypesArray = filterLeaveTypesForSelection(
      Array.isArray(leaveTypesRes) ? leaveTypesRes : (leaveTypesRes?.data || [])
    )
    leaveTypeId = leaveTypesArray.length > 0 ? leaveTypesArray[0].id : null
  }
  
  if (!leaveTypeId) return
  
  try {
    const res = await leaveCreditCardService.loadDetails(header.value.id, {
      leave_type_id: leaveTypeId,
      year: y
    })
    
    viewerRows.value = res.table || []
    beginningBalances.value = res.beginning_balances || []
    viewerTimeData.value = res.time_data || []
  } catch (e) {
    console.error('Error loading year data:', e)
  }
}

const onLeaveTypeChange = async (leaveTypeId) => {
  currentLeaveTypeId.value = leaveTypeId
  
  if (!header.value?.id) return
  
  try {
    viewerOpeningLoading.value = true
    const res = await leaveCreditCardService.loadDetails(header.value.id, {
      leave_type_id: leaveTypeId,
      year: year.value
    })
    
    viewerRows.value = res.table || []
    beginningBalances.value = res.beginning_balances || []
    viewerTimeData.value = res.time_data || []
  } catch (e) {
    console.error('Error loading leave type data:', e)
  } finally {
    viewerOpeningLoading.value = false
  }
}

const onCreditsSaved = async () => {
  // Reload the employee list to update leave credits in the table
  await loadList(
    pagination.value.current_page,
    pagination.value.per_page,
    filters.value.search,
    filters.value.positionId,
    filters.value.departmentId
  )
  
  // Also reload the current employee's details if viewer is open
  if (header.value?.id && currentLeaveTypeId.value) {
    try {
      const res = await leaveCreditCardService.loadDetails(header.value.id, {
        leave_type_id: currentLeaveTypeId.value,
        year: year.value
      })
      
      viewerRows.value = res.table || []
      beginningBalances.value = res.beginning_balances || []
      viewerTimeData.value = res.time_data || []
    } catch (e) {
      console.error('Error reloading employee details:', e)
    }
  }
}

// Load all employees for BeginningBalanceSetup
const allEmployeesForBeginningBalance = ref([])

const loadAllEmployeesForBeginningBalance = async () => {
  try {
    // Fetch ALL employees (not just those with leave credits)
    const employeesResponse = await api.get('/employees')
    const allEmployees = Array.isArray(employeesResponse?.data) ? employeesResponse.data : (Array.isArray(employeesResponse) ? employeesResponse : [])
    
    // Also fetch existing leave credits to merge with employee data
    const filterParams = {
      search: '',
      departmentId: null,
      employmentTypeId: null,
      onlyActive: true,
      export: true // full list for card monitoring
    }
    
    const rows = await leaveCreditsService.listAllLeaveCredits(filterParams)
    
    // Create a map of employees with their leave credits
    const employeesMap = new Map()
    
    // Initialize all employees first
    allEmployees.forEach(emp => {
      const firstName = emp.first_name ?? emp.firstname ?? emp.firstName ?? null
      const middleName = emp.middle_name ?? emp.middlename ?? emp.middleName ?? null
      const lastName = emp.last_name ?? emp.lastname ?? emp.lastName ?? null
      employeesMap.set(emp.id, {
        id: emp.id,
        employee_id: emp.id,
        employee_no: emp.employee_no,
        name: formatEmployeeName({ first_name: firstName, middle_name: middleName, last_name: lastName, name: emp.name }),
        first_name: firstName,
        middle_name: middleName,
        last_name: lastName,
        position: emp.position,
        department: emp.department,
        department_name: emp.department,
        employment_type_name: emp.employment_type,
        photo: emp.photo,
        leave_credits: []
      })
    })
    
    // Merge leave credits data for employees who have them
    rows.forEach(r => {
      if (employeesMap.has(r.employee_id)) {
        const employee = employeesMap.get(r.employee_id)
        if (!employee.first_name && r.first_name) employee.first_name = r.first_name
        if (!employee.middle_name && r.middle_name) employee.middle_name = r.middle_name
        if (!employee.last_name && r.last_name) employee.last_name = r.last_name
        employee.name = formatEmployeeName({
          first_name: employee.first_name,
          middle_name: employee.middle_name,
          last_name: employee.last_name,
          name: employee.name || r.name,
        })
        employee.leave_credits.push({
          id: r.id,
          employee_id: r.employee_id,
          leave_type_id: r.leave_type_id,
          leave_type_name: r.leave_type_name || '',
          credits: Number(r.credits || 0),
          created_at: r.created_at,
          updated_at: r.updated_at,
        })
      }
    })
    
    allEmployeesForBeginningBalance.value = Array.from(employeesMap.values())
  } catch (e) {
    console.error('Error loading all employees for beginning balance:', e)
    allEmployeesForBeginningBalance.value = []
  }
}

// Computed property for employees to pass to BeginningBalanceSetup
const employeesForBeginningBalance = computed(() => {
  return allEmployeesForBeginningBalance.value
})

const handleBeginningBalanceSaved = () => {
  // Reload data after beginning balances are saved
  loadList(
    pagination.value.current_page,
    pagination.value.per_page,
    filters.value.search,
    filters.value.positionId,
    filters.value.departmentId
  )
  // Also reload report data
  loadEmployeesWithCreditsForReport()
  // Reload all employees for beginning balance
  loadAllEmployeesForBeginningBalance()
  // Reload viewer if open
  if (header.value?.id && currentLeaveTypeId.value) {
    onCreditsSaved()
  }
}

const openBulkSaveDialog = async () => {
  bulkSaveDialogVisible.value = true
  await prepareBulkPreviewRows()
}

const prepareBulkPreviewRows = async () => {
  bulkPreviewLoading.value = true
  bulkPreviewRows.value = []
  bulkProgress.value = { total: 0, processed: 0, success: 0, failed: 0 }
  bulkPreviewEmployeeProgress.value = { totalEmployees: 0, processed: 0 }
  bulkPreviewCurrentEmployee.value = ''
  bulkPreviewCurrentLeaveType.value = ''
  bulkPreviewEmployeeLogs.value = []

  try {
    const currentYear = new Date().getFullYear()
    const currentMonth = new Date().getMonth() + 1

    // Ensure we have the full employees list
    if (!allEmployeesForBeginningBalance.value.length) {
      await loadAllEmployeesForBeginningBalance()
    }
    const employees = allEmployeesForBeginningBalance.value || []
    if (!employees.length) {
      ElMessage.warning('No employees found for bulk save')
      return
    }

    bulkPreviewEmployeeProgress.value.totalEmployees = employees.length
    bulkPreviewEmployeeProgress.value.processed = 0

    bulkPreviewEmployeeLogs.value = employees.map(emp => ({
      employee_id: emp.id,
      employee_name: emp.name || `Employee #${emp.id}`,
      status: 'pending',
      processedLeaveTypes: 0,
      totalLeaveTypes: 0,
      creditsFound: 0
    }))
    bulkPreviewCurrentEmployee.value = 'Preparing bulk data on server'
    bulkPreviewCurrentLeaveType.value = ''
    startBulkPreviewPulse(employees)

    const rows = await leaveCreditCardService.bulkPreview({
      year: currentYear,
      current_month: currentMonth,
      employee_ids: employees.map(emp => emp.id)
    })

    const employeeNameMap = new Map(employees.map(emp => [Number(emp.id), emp.name || `Employee #${emp.id}`]))
    const rowsWithNames = rows
      .filter(row => Number(row.leave_type_id) !== EXCLUDED_BULK_LEAVE_TYPE_ID)
      .map(row => ({
        ...row,
        employee_name: employeeNameMap.get(Number(row.employee_id)) || row.employee_name || `Employee #${row.employee_id}`
      }))

    const rowsByEmployee = new Map()
    rowsWithNames.forEach(row => {
      const key = Number(row.employee_id)
      if (!rowsByEmployee.has(key)) rowsByEmployee.set(key, [])
      rowsByEmployee.get(key).push(row)
    })

    bulkPreviewEmployeeLogs.value = bulkPreviewEmployeeLogs.value.map(log => {
      const count = rowsByEmployee.get(Number(log.employee_id))?.length || 0
      return {
        ...log,
        status: 'completed',
        processedLeaveTypes: count,
        totalLeaveTypes: count,
        creditsFound: count
      }
    })

    bulkPreviewEmployeeProgress.value.processed = employees.length
    bulkPreviewRows.value = rowsWithNames
    bulkProgress.value.total = rowsWithNames.length
  } finally {
    stopBulkPreviewPulse()
    bulkPreviewCurrentEmployee.value = ''
    bulkPreviewCurrentLeaveType.value = ''
    bulkPreviewLoading.value = false
  }
}

const handleBulkSave = async () => {
  if (!bulkPreviewRows.value.length) {
    ElMessage.warning('No leave credits to update')
    return
  }

  bulkSaveSaving.value = true
  bulkProgress.value.processed = 0
  bulkProgress.value.success = 0
  bulkProgress.value.failed = 0

  // Group rows by leave type so we can use saveCreditsBulk per leave type
  const groups = new Map()
  for (const row of bulkPreviewRows.value) {
    if (Number(row.leave_type_id) === EXCLUDED_BULK_LEAVE_TYPE_ID) continue
    if (!groups.has(row.leave_type_id)) {
      groups.set(row.leave_type_id, {
        leave_type_name: row.leave_type_name,
        employee_ids: [],
        credits: []
      })
    }
    const g = groups.get(row.leave_type_id)
    g.employee_ids.push(row.employee_id)
    g.credits.push(row.credit)
  }

  try {
    for (const [leaveTypeId, group] of groups.entries()) {
      try {
        await leaveCreditsService.saveCreditsBulk(leaveTypeId, group.employee_ids, group.credits)
        bulkProgress.value.success += group.employee_ids.length
      } catch (e) {
        bulkProgress.value.failed += group.employee_ids.length
      } finally {
        bulkProgress.value.processed = bulkProgress.value.success + bulkProgress.value.failed
      }
    }

    if (bulkProgress.value.success > 0) {
      ElMessage.success(`Successfully updated ${bulkProgress.value.success} leave credit record(s)`)

      // Reload list and report data
      await loadList(
        pagination.value.current_page,
        pagination.value.per_page,
        filters.value.search,
        filters.value.positionId,
        filters.value.departmentId
      )
      await loadEmployeesWithCreditsForReport()
    }

    if (bulkProgress.value.failed > 0) {
      ElMessage.warning(`Failed to update ${bulkProgress.value.failed} leave credit record(s)`)
    }
  } finally {
    bulkSaveSaving.value = false
  }
}

// Watch filters to reload report data
watch(() => [filters.value.search, filters.value.departmentId], () => {
  loadEmployeesWithCreditsForReport()
}, { deep: true })

onMounted(async () => {
  await loadList()
  await loadLeaveTypesForReport()
  await loadEmployeesWithCreditsForReport()
  await loadAllEmployeesForBeginningBalance()
})
</script>

<style scoped>
.mb-3 { margin-bottom: 12px; }
.mr-2 { margin-right: 8px; }
.filters { display: flex; align-items: center; }
.toolbar-row { display: flex; align-items: center; gap: 12px; flex-wrap: nowrap; }
.flex-spacer { flex: 1; }
.bulk-loading-panel { padding: 24px; text-align: center; }
.bulk-loading-title { margin-top: 8px; color: #606266; margin-bottom: 12px; }
.bulk-loading-current { margin-top: 10px; margin-bottom: 10px; color: #303133; font-size: 13px; }
.bulk-loading-list { margin-top: 8px; border: 1px solid #ebeef5; border-radius: 6px; max-height: 280px; overflow: auto; text-align: left; background: #fff; }
.bulk-loading-item { padding: 8px 12px; border-bottom: 1px solid #f2f6fc; }
.bulk-loading-item:last-child { border-bottom: none; }
.bulk-loading-item-name { font-size: 13px; color: #303133; font-weight: 500; }
.bulk-loading-item-name.pending { color: #909399; }
.bulk-loading-item-name.processing { color: #e6a23c; }
.bulk-loading-item-name.completed { color: #67c23a; }
@media (max-width: 768px) { .toolbar-row { flex-wrap: wrap; } }
</style>
