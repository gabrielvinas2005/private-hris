<template>
  <PageScaffold
  title="Leave Monitoring"
  subtitle="Monitor leave applications, approvals, and leave status tracking"
  :breadcrumbs="[{ label: 'Timekeeping Module', to: '/' }, { label: 'Leave Monitoring' }]"
  >
    <Overview
      :items="overviewItems"
      :clickable-labels="['On Leave Today']"
      @item-click="onOverviewItemClick"
    />

    <SearchFiltersContainer
      :searchValue="filters.search"
      @update:searchValue="val => { filters.search = val; }"
      :filtersValue="filters"
      @update:filtersValue="val => Object.assign(filters, val)"
      :filters="availableFilters"
      :search-placeholder="'Search employee or type...'"
      :filters-loading="loading"
      :search-loading="loading"
      :auto-fetch="true"
      :show-export-section="false"
      @search="handleSearch"
      @filters-change="handleFiltersChange"
    >
      <template #actions>
        <PreviewExport
          :html-content="reportHtmlContent"
          :title="previewTitle"
          :filename="'leave_monitoring_report'"
          :loading="loading"
          :on-excel="handleExcelExport"
          :on-pdf="handlePdfExport"
          :on-word="handleWordExport"
        />
      </template>
    </SearchFiltersContainer>

    <el-tabs v-model="activeTab" @tab-change="onTabChange">
      <el-tab-pane label="Pending" name="pending">
        <LeaveMonitoringTable
          :rows="filteredPending"
          :loading="loading"
          :hideActions="true"
          :allowCancel="false"
          :showViewOnly="true"
          @view="onView"
        />
        <!-- Pagination -->
        <Pagination
          :pagination="paginationData"
          :per-page-options="[10, 25, 50, 100]"
          @page-change="onPageChange"
          @per-page-change="onPerPageChange"
        />
      </el-tab-pane>
      <el-tab-pane label="Approved" name="approved">
        <LeaveMonitoringTable
          :rows="filteredApproved"
          :loading="loading"
          :hideActions="true"
          :allowCancel="false"
          :showViewOnly="true"
          @view="onView"
        />
        <!-- Pagination -->
        <Pagination
          :pagination="paginationData"
          :per-page-options="[10, 25, 50, 100]"
          @page-change="onPageChange"
          @per-page-change="onPerPageChange"
        />
      </el-tab-pane>
      <el-tab-pane label="Disapproved" name="disapproved">
        <LeaveMonitoringTable
          :rows="filteredDisapproved"
          :loading="loading"
          :hideActions="true"
          :showViewOnly="true"
          @view="onView"
        />
        <!-- Pagination -->
        <Pagination
          :pagination="paginationData"
          :per-page-options="[10, 25, 50, 100]"
          @page-change="onPageChange"
          @per-page-change="onPerPageChange"
        />
      </el-tab-pane>
      <el-tab-pane label="Cancelled" name="cancelled">
        <LeaveMonitoringTable
          :rows="filteredCancelled"
          :loading="loading"
          :hideActions="true"
          :showViewOnly="true"
          @view="onView"
        />
        <!-- Pagination -->
        <Pagination
          :pagination="paginationData"
          :per-page-options="[10, 25, 50, 100]"
          @page-change="onPageChange"
          @per-page-change="onPerPageChange"
        />
      </el-tab-pane>
      <el-tab-pane label="Expired" name="expired">
        <LeaveMonitoringTable
          :rows="filteredExpired"
          :loading="loading"
          :hideActions="true"
          :showViewOnly="true"
          @view="onView"
        />
        <!-- Pagination -->
        <Pagination
          :pagination="paginationData"
          :per-page-options="[10, 25, 50, 100]"
          @page-change="onPageChange"
          @per-page-change="onPerPageChange"
        />
      </el-tab-pane>
    </el-tabs>

    <LeaveMonitoringViewer v-model="viewerOpen" :row="selectedRow" />

    <el-dialog
      v-model="leaveTodayDialogOpen"
      title="Employees On Leave Today"
      width="560px"
      destroy-on-close
    >
      <ul v-if="todayOnLeaveEmployees.length" class="names-list">
        <li v-for="emp in todayOnLeaveEmployees" :key="emp.employee_id || emp.id || emp.employee_no">
          <EmployeeDataPopulate :employee="emp" field="namePosition" />
        </li>
      </ul>
      <el-empty v-else description="No employees found." />
      <template #footer>
        <el-button @click="leaveTodayDialogOpen = false">Close</el-button>
      </template>
    </el-dialog>
  </PageScaffold>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import PageScaffold from '../../components/Reusable_Components/PageScaffold.vue'
import SearchFiltersContainer from '../../components/Reusable_Components/SearchFiltersContainer.vue'
import Pagination from '../../components/Reusable_Components/Pagination.vue'
import PreviewExport from '../../components/Reusable_Components/Preview&Export.vue'
import LeaveMonitoringTable from '../../components/Leave_Monitoring/LeaveMonitoringTable.vue'
import LeaveMonitoringViewer from '../../components/Leave_Monitoring/LeaveMonitoringViewer.vue'
import { leaveMonitoringService, api as rawApi } from '../../services/api'
import { notify } from '../../services/notify'
import { ElMessage } from 'element-plus'
import { Clock, CircleCheck, CloseBold, RemoveFilled, Calendar } from '@element-plus/icons-vue'
import Overview from '../../components/Reusable_Components/Overview.vue'
import { useFilterLogic } from '@/Composables/Filter_Logic'
import { usePDFPreview } from '@/Composables/usePDFPreview'
import { useBackendReportExport } from '@/Composables/useBackendReportExport'
import EmployeeDataPopulate from '../../components/Reusable_Components/Employee_Data_Populate.vue'

const route = useRoute()
const router = useRouter()

const rows = ref([])
const loading = ref(false)
const search = ref('')
// Initialize active tab from URL query or default
const activeTab = ref(route.query.tab || 'pending')
const viewerOpen = ref(false)
const selectedRow = ref(null)
const leaveTodayDialogOpen = ref(false)

// Pagination state
const currentPage = ref(1)
const perPage = ref(10)

// Filter state
const filters = ref({ 
  search: '', 
  positionId: null, 
  departmentId: null,
  month: null,
  year: null
})

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
  },
  {
    key: 'month',
    label: 'Month',
    valueKey: 'value',
    labelKey: 'label',
    options: [
      { value: null, label: 'All Months' },
      { value: 1, label: 'January' },
      { value: 2, label: 'February' },
      { value: 3, label: 'March' },
      { value: 4, label: 'April' },
      { value: 5, label: 'May' },
      { value: 6, label: 'June' },
      { value: 7, label: 'July' },
      { value: 8, label: 'August' },
      { value: 9, label: 'September' },
      { value: 10, label: 'October' },
      { value: 11, label: 'November' },
      { value: 12, label: 'December' }
    ]
  },
  {
    key: 'year',
    label: 'Year',
    valueKey: 'value',
    labelKey: 'label',
    options: (() => {
      const currentYear = new Date().getFullYear()
      const years = []
      // Show current year and 5 years before (6 years total)
      for (let i = currentYear; i >= currentYear - 5; i--) {
        years.push({ value: i, label: String(i) })
      }
      return years
    })()
  }
])

const { filterEmployees } = useFilterLogic()
const { downloadDOCX } = usePDFPreview()
const { exportToExcel } = useBackendReportExport()

const fmt = (v) => v ? new Date(v).toLocaleDateString() : ''

const toBool = (v) => v === true || v === 1 || v === '1'

const mapRow = (r) => {
  const [rangeStart, rangeEnd] = parseRangeFromCovered(r.date_covered)
  const normalizedDateFrom = normalizeDate(r.date_from) || rangeStart
  const normalizedDateTo = normalizeDate(r.date_to) || rangeEnd
  const dateRangeSort = (normalizedDateFrom || normalizedDateTo)?.getTime() || 0

  return {
    id: r.id,
    employee_id: r.employee_id,
    photo: r.photo,
    employee_no: r.employee_no,
    position_id: r.position_id,
    department_id: r.department_id,
    name: r.name,
    position: r.position,
    department: r.department,
    leave_type_id: r.leave_type_id,
    leave_type: r.leave_type,
    balance: r.balance,
    days: r.days,
    day_type: r.day_type,
    date_covered: r.date_covered,
    // Always base date range on backend date_from/date_to if provided
    dateFrom: normalizedDateFrom,
    dateTo: normalizedDateTo,
    dateRangeSort,
    reason: r.reason,
    remarks: r.remarks,
    processed_date: r.processed_date,
    processed_date_2: r.processed_date_2,
    processed_date_3: r.processed_date_3,
    processed_date_4: r.processed_date_4,
    approver_1: r.approver_1,
    approver_2: r.approver_2,
    approver_3: r.approver_3,
    approver_4: r.approver_4,
    approved: toBool(r.approved),
    approved_2: toBool(r.approved_2),
    approved_3: toBool(r.approved_3),
    disapproved: toBool(r.disapproved),
    disapproved_2: toBool(r.disapproved_2),
    disapproved_3: toBool(r.disapproved_3),
    is_cancel: toBool(r.is_cancel),
    is_cancel_2: toBool(r.is_cancel_2),
    with_pay: r.with_pay,
    without_pay: r.without_pay,
    cancelled_by_name: r.cancelled_by_name,
    cancelled_by_name_2: r.cancelled_by_name_2,
    canceled_date: r.canceled_date,
    canceled_date_2: r.canceled_date_2,
    canceled_remarks: r.canceled_remarks,
    canceled_remarks_2: r.canceled_remarks_2,
    approved_remarks: r.approved_remarks,
    disapproved_remarks: r.disapproved_remarks,
    approved_2_remarks: r.approved_2_remarks,
    disapproved_2_remarks: r.disapproved_2_remarks,
    approved_3_remarks: r.approved_3_remarks,
    disapproved_3_remarks: r.disapproved_3_remarks,
    attachment_name: r.attachment_name,
    // Derive simple status flags based on fields present in monitoring payload
    status: deriveStatus({
      approved: r.approved,
      approved_2: r.approved_2,
      approved_3: r.approved_3,
      disapproved: r.disapproved,
      disapproved_2: r.disapproved_2,
      disapproved_3: r.disapproved_3,
      is_cancel: r.is_cancel,
      is_cancel_2: r.is_cancel_2
    }, {
      dateFrom: normalizedDateFrom,
      dateTo: normalizedDateTo,
      date_covered: r.date_covered
    })
  }
}

function deriveStatus(r, ctx) {
  // Status derivation logic:
  // - Cancelled: is_cancel or is_cancel_2 is true
  // - Disapproved: disapproved, disapproved_2, or disapproved_3 is true
  // - Approved: all configured approver levels are approved (dynamic)
  //   (level is considered configured when both approval/disapproval flags are non-null)
  // - Expired: coverage date has passed AND not fully approved
  // - Pending: everything else
  
  const isTrue = (v) => v === true || v === 1 || v === '1'
  const isConfigured = (approveFlag, disapproveFlag) =>
    approveFlag !== null && approveFlag !== undefined &&
    disapproveFlag !== null && disapproveFlag !== undefined

  if (isTrue(r.is_cancel) || isTrue(r.is_cancel_2)) return 'cancelled'
  if (isTrue(r.disapproved) || isTrue(r.disapproved_2) || isTrue(r.disapproved_3)) return 'disapproved'

  const level1Approved = isTrue(r.approved)
  const level2Required = isConfigured(r.approved_2, r.disapproved_2)
  const level3Required = isConfigured(r.approved_3, r.disapproved_3)
  const level2Approved = isTrue(r.approved_2)
  const level3Approved = isTrue(r.approved_3)

  if (
    level1Approved &&
    (!level2Required || level2Approved) &&
    (!level3Required || level3Approved)
  ) return 'approved'
  
  // Expired: coverage date passed (whether stuck at level 1, 2, or 3)
  if (hasCoveragePassed(ctx)) return 'expired'
  
  // Otherwise it's pending
  return 'pending'
}

// Removed fallback parsing from date_covered; rely on date_from/date_to values

function normalizeDate(value) {
  if (value instanceof Date && !isNaN(value)) return value
  if (typeof value === 'string') {
    const trimmed = value.trim()
    const mmddyyyy = trimmed.match(/^(\d{2})[\/-](\d{2})[\/-](\d{4})$/)
    if (mmddyyyy) {
      const mm = parseInt(mmddyyyy[1], 10)
      const dd = parseInt(mmddyyyy[2], 10)
      const yyyy = parseInt(mmddyyyy[3], 10)
      const constructed = new Date(yyyy, mm - 1, dd)
      if (!isNaN(constructed)) return constructed
    }
    const parsed = new Date(trimmed)
    if (!isNaN(parsed)) return parsed
  }
  if (typeof value === 'number') {
    const parsed = new Date(value)
    if (!isNaN(parsed)) return parsed
  }
  return null
}

function parseRangeFromCovered(covered) {
  if (!covered) return [null, null]
  const parts = String(covered).split(' - ')
  if (parts.length !== 2) return [null, null]
  const [startRaw, endRaw] = parts
  return [normalizeDate(startRaw), normalizeDate(endRaw)]
}

function hasCoveragePassed(row) {
  if (!row) return false
  const [rangeStart, rangeEnd] = parseRangeFromCovered(row.date_covered)
  const endDate = normalizeDate(row.dateTo) || rangeEnd
  const fallbackStart = normalizeDate(row.dateFrom) || rangeStart
  const finalizedEnd = endDate || fallbackStart
  if (!finalizedEnd) return false
  const endOfDay = new Date(finalizedEnd.getFullYear(), finalizedEnd.getMonth(), finalizedEnd.getDate(), 23, 59, 59, 999)
  return Date.now() > endOfDay.getTime()
}

// Get tab name for title
const tabDisplayName = computed(() => {
  switch (activeTab.value) {
    case 'pending': return 'Pending'
    case 'approved': return 'Approved'
    case 'disapproved': return 'Disapproved'
    case 'cancelled': return 'Cancelled'
    case 'expired': return 'Expired'
    default: return 'Leave Monitoring'
  }
})

// Preview title with tab name
const previewTitle = computed(() => {
  return `Leave Monitoring Report - ${tabDisplayName.value}`
})

// Generate HTML content for preview with proper layout
const reportHtmlContent = computed(() => {
  if (!filteredRows.value.length) {
    return '<p style="padding: 16px; text-align: center; color: #909399;">No data available for report. Please apply filters or wait for data to load.</p>'
  }
  
  // Get rows for current tab
  let currentTabRows = []
  switch (activeTab.value) {
    case 'pending':
      currentTabRows = filteredRows.value.filter(r => r.status === 'pending')
      break
    case 'approved':
      currentTabRows = filteredRows.value.filter(r => r.status === 'approved')
      break
    case 'disapproved':
      currentTabRows = filteredRows.value.filter(r => r.status === 'disapproved')
      break
    case 'cancelled':
      currentTabRows = filteredRows.value.filter(r => r.status === 'cancelled')
      break
    case 'expired':
      currentTabRows = filteredRows.value.filter(r => r.status === 'expired')
      break
    default:
      currentTabRows = filteredRows.value
  }
  
  if (!currentTabRows.length) {
    return '<p style="padding: 16px; text-align: center; color: #909399;">No data available for this tab.</p>'
  }
  
  // Group leave applications by employee so we can show multiple entries per person like OT report
  const groupedByEmployee = currentTabRows.reduce((acc, leave) => {
    const key = leave.employee_id || `${leave.employee_no || ''}-${leave.name || ''}`
    if (!acc[key]) {
      acc[key] = {
        employee: leave,
        applications: []
      }
    }
    acc[key].applications.push(leave)
    return acc
  }, {})
  
  const employeeGroups = Object.values(groupedByEmployee).sort((a, b) => {
    const nameA = (a.employee?.name || '').toLowerCase()
    const nameB = (b.employee?.name || '').toLowerCase()
    return nameA.localeCompare(nameB)
  })
  
  let html = '<div style="padding: 12px 20px; font-family: Arial, sans-serif;">'
  
  // Report Title with tab name
  html += '<div style="text-align: center; margin-bottom: 12px;">'
  html += '<h2 style="margin: 0 0 4px 0; font-size: 20px; font-weight: bold; color: #0f172a;">'
  html += `Leave Monitoring Report - ${tabDisplayName.value}`
  html += '</h2>'
  html += '</div>'
  
  employeeGroups.forEach((group, groupIndex) => {
    const employee = group.employee
    const applications = group.applications || []
    
    html += '<div style="margin-bottom: 16px;">'
    html += `<div style="font-size: 15px; font-weight: 700; color: #111827; margin-bottom: 4px;">${groupIndex + 1}. ${employee?.name || 'N/A'}</div>`
    
    html += '<div style="display: flex; flex-wrap: wrap; gap: 6px 16px; font-size: 12px; color: #475467; margin-bottom: 8px;">'
    html += `<span>Employee No: ${employee?.employee_no || 'N/A'}</span>`
    if (employee?.position) html += `<span>Position: ${employee.position}</span>`
    if (employee?.department) html += `<span>Department: ${employee.department}</span>`
    html += '</div>'
    
    applications.forEach((leave, leaveIndex) => {
      if (applications.length > 1) {
        html += `<div style="font-size: 12px; font-weight: 600; color: #2563eb; margin-bottom: 4px;">Application ${leaveIndex + 1}</div>`
      }
      
      html += '<div style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px 14px; background: #ffffff; page-break-inside: avoid;">'
      
      // Core leave info
      html += formatLeaveField('Leave Type', leave.leave_type)
      html += formatLeaveField('Date Range', formatLeaveDateRange(leave))
      html += formatLeaveField('Days', formatLeaveDays(leave))
      html += formatLeaveField('Day Type', leave.day_type)
      html += formatLeaveField('Reason', leave.reason)
      html += formatLeaveField('Remarks', leave.remarks)
      html += formatLeaveField('Status', formatStatus(leave.status))
      
      // Approvers
      html += formatApproverField('Approver 1', leave.approver_1, leave.processed_date)
      html += formatApproverField('Approver 2', leave.approver_2, leave.processed_date_2)
      html += formatApproverField('Approver 3', leave.approver_3, leave.processed_date_3)
      html += formatApproverField('Approver 4', leave.approver_4, leave.processed_date_4)
      
      // Cancellation details
      if (leave.is_cancel || leave.is_cancel_2) {
        html += '<div style="margin-top: 6px; padding-top: 6px; border-top: 1px dashed #e5e7eb;">'
        html += formatLeaveField('Cancelled By (1)', leave.cancelled_by_name)
        html += formatLeaveField('Cancel Date (1)', leave.canceled_date ? formatDateForReport(leave.canceled_date) : '')
        html += formatLeaveField('Cancel Remarks (1)', leave.canceled_remarks)
        html += formatLeaveField('Cancelled By (2)', leave.cancelled_by_name_2)
        html += formatLeaveField('Cancel Date (2)', leave.canceled_date_2 ? formatDateForReport(leave.canceled_date_2) : '')
        html += formatLeaveField('Cancel Remarks (2)', leave.canceled_remarks_2)
        html += '</div>'
      }
      
      html += '</div>'
    })
    
    html += '</div>'
    
    if (groupIndex < employeeGroups.length - 1) {
      html += '<hr style="border: none; border-top: 1px solid #e4e7ed; margin: 8px 0;" />'
    }
  })
  
  html += '</div>'
  
  return html
})

// Helper functions for report formatting
function formatDateForReport(value) {
  if (!value) return 'N/A'
  let d = null
  if (value instanceof Date) {
    d = value
  } else {
    d = normalizeDate(value)
  }
  if (!d || isNaN(d.getTime())) return 'N/A'
  const monthNames = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'
  ]
  const month = monthNames[d.getMonth()]
  const day = d.getDate()
  const year = d.getFullYear()
  return `${month} ${day}, ${year}`
}

// Export functions
function handleExcelExport() {
  try {
    const all = filteredRows.value || []
    let currentTabRows = []
    switch (activeTab.value) {
      case 'pending':
        currentTabRows = all.filter(r => r.status === 'pending')
        break
      case 'approved':
        currentTabRows = all.filter(r => r.status === 'approved')
        break
      case 'disapproved':
        currentTabRows = all.filter(r => r.status === 'disapproved')
        break
      case 'cancelled':
        currentTabRows = all.filter(r => r.status === 'cancelled')
        break
      case 'expired':
        currentTabRows = all.filter(r => r.status === 'expired')
        break
      default:
        currentTabRows = all
    }

    if (!currentTabRows.length) {
      ElMessage.warning('No data available for Excel export.')
      return
    }

    const filename = `leave_monitoring_report_${activeTab.value}_${new Date().toISOString().slice(0, 10)}`
    exportToExcel('leave_monitoring', {
      tab_display_name: tabDisplayName.value,
      leaves: currentTabRows
    }, filename)
  } catch (e) {
    ElMessage.error(`Excel export failed: ${e?.message || e}`)
  }
}

function handlePdfExport() {
  ElMessage.info('PDF export functionality will be implemented')
}

function handleWordExport() {
  try {
    const all = filteredRows.value || []
    let currentTabRows = []
    switch (activeTab.value) {
      case 'pending':
        currentTabRows = all.filter(r => r.status === 'pending')
        break
      case 'approved':
        currentTabRows = all.filter(r => r.status === 'approved')
        break
      case 'disapproved':
        currentTabRows = all.filter(r => r.status === 'disapproved')
        break
      case 'cancelled':
        currentTabRows = all.filter(r => r.status === 'cancelled')
        break
      case 'expired':
        currentTabRows = all.filter(r => r.status === 'expired')
        break
      default:
        currentTabRows = all
    }

    if (!currentTabRows.length) {
      ElMessage.warning('No data available for DOCX export.')
      return
    }

    const reportData = {
      report_type: 'leave_monitoring',
      data: {
        tab_display_name: tabDisplayName.value,
        leaves: currentTabRows
      },
      paper_size: 'A4',
      orientation: 'portrait'
    }

    const filename = `leave_monitoring_report_${activeTab.value}_${new Date().toISOString().slice(0, 10)}`
    downloadDOCX(reportData, filename)
  } catch (e) {
    ElMessage.error(`DOCX export failed: ${e?.message || e}`)
  }
}

function formatLeaveField(label, value) {
  if (value === undefined || value === null || value === '') return ''
  return `
    <div style="display: flex; gap: 8px; font-size: 13px; line-height: 1.3; margin-bottom: 4px;">
      <span style="font-weight: 500; color: #475467; min-width: 110px;">${label}:</span>
      <span style="font-weight: 600; color: #111827; flex: 1;">${value}</span>
    </div>
  `
}

function formatLeaveDateRange(leave) {
  if (!leave) return 'N/A'
  const [startFromCovered, endFromCovered] = parseRangeFromCovered(leave.date_covered)
  const start = leave.dateFrom || startFromCovered
  const end = leave.dateTo || endFromCovered || start
  if (!start && !end) return leave.date_covered || 'N/A'
  if (start && !end) return formatDateForReport(start)
  if (!start && end) return formatDateForReport(end)
  return `${formatDateForReport(start)} - ${formatDateForReport(end)}`
}

function formatLeaveDays(leave) {
  if (!leave) return ''
  const days = leave.days ?? leave.balance
  if (days === undefined || days === null || days === '') return ''
  const numeric = Number(days)
  if (isNaN(numeric)) return days
  return `${numeric.toFixed(2)}`
}

function formatStatus(status) {
  if (!status) return ''
  return status.charAt(0).toUpperCase() + status.slice(1)
}

function formatApproverField(label, approver, processedDate) {
  if (!approver) return ''
  const processed = processedDate ? ` (${formatDateForReport(processedDate)})` : ''
  return formatLeaveField(label, `${approver}${processed}`)
}

const load = async () => {
  loading.value = true
  try {
    const data = await leaveMonitoringService.loadApprovals()
    rows.value = Array.isArray(data) ? data.map(mapRow) : (Array.isArray(data?.data) ? data.data.map(mapRow) : [])
  } catch (err) {
    notify.error(err.message)
  } finally {
    loading.value = false
  }
}


// Filtered employees based on search and filters
const filteredRows = computed(() => {
  // Exclude month and year from filterEmployees since we handle them separately
  const { month, year, ...otherFilters } = filters.value
  let filtered = filterEmployees(rows.value, otherFilters)
  
  // Get the selected month and year
  const selectedMonth = month
  const selectedYear = year
  
  // Only apply date filtering if at least one filter is NOT null
  const hasMonthFilter = selectedMonth !== null && selectedMonth !== undefined
  const hasYearFilter = selectedYear !== null && selectedYear !== undefined
  
  if (hasMonthFilter || hasYearFilter) {
    filtered = filtered.filter(row => {
      const dateFrom = row.dateFrom
      const dateTo = row.dateTo || dateFrom // If no dateTo, use dateFrom
      
      if (!dateFrom) return false
      
      // Case 1: Only year is selected (All Months + Specific Year)
      if (hasYearFilter && !hasMonthFilter) {
        const yearStart = new Date(selectedYear, 0, 1) // Jan 1
        const yearEnd = new Date(selectedYear, 11, 31, 23, 59, 59) // Dec 31
        
        // Check if leave period overlaps with the year
        return dateFrom <= yearEnd && dateTo >= yearStart
      }
      
      // Case 2: Both month and year are selected (Specific Month + Specific Year)
      if (hasMonthFilter && hasYearFilter) {
        // Get the first and last day of the selected month
        const monthStart = new Date(selectedYear, selectedMonth - 1, 1)
        const monthEnd = new Date(selectedYear, selectedMonth, 0, 23, 59, 59) // Last day of month
        
        // Check if leave period overlaps with the selected month
        return dateFrom <= monthEnd && dateTo >= monthStart
      }
      
      // Case 3: Only month is selected (Specific Month + All Years)
      if (hasMonthFilter && !hasYearFilter) {
        const fromMonth = dateFrom.getMonth() + 1
        const toMonth = dateTo.getMonth() + 1
        
        // Check if the leave period includes the selected month
        if (dateFrom.getFullYear() === dateTo.getFullYear()) {
          // Same year: check if selected month is between from and to months
          return selectedMonth >= fromMonth && selectedMonth <= toMonth
        } else {
          // Different years: check if selected month is in the range
          // Need to check if the month falls within the leave period
          const fromYear = dateFrom.getFullYear()
          const toYear = dateTo.getFullYear()
          
          // If leave spans multiple years, check each year
          for (let year = fromYear; year <= toYear; year++) {
            let startMonth = (year === fromYear) ? fromMonth : 1
            let endMonth = (year === toYear) ? toMonth : 12
            
            if (selectedMonth >= startMonth && selectedMonth <= endMonth) {
              return true
            }
          }
          return false
        }
      }
      
      return true
    })
  }
  
  // Case 4: All Months + All Years - return all filtered records (no date filtering)
  return filtered
})

// Paginated data for current tab
const paginatedData = computed(() => {
  const start = (currentPage.value - 1) * perPage.value
  const end = start + perPage.value
  return filteredRows.value.slice(start, end)
})

// Pagination data for Pagination component
const paginationData = computed(() => {
  let total = 0
  if (activeTab.value === 'pending') {
    total = filteredRows.value.filter(r => r.status === 'pending').length
  } else if (activeTab.value === 'approved') {
    total = filteredRows.value.filter(r => r.status === 'approved').length
  } else if (activeTab.value === 'disapproved') {
    total = filteredRows.value.filter(r => r.status === 'disapproved').length
  } else if (activeTab.value === 'cancelled') {
    total = filteredRows.value.filter(r => r.status === 'cancelled').length
  } else if (activeTab.value === 'expired') {
    total = filteredRows.value.filter(r => r.status === 'expired').length
  } else {
    total = filteredRows.value.length
  }
  
  const from = total === 0 ? 0 : (currentPage.value - 1) * perPage.value + 1
  const to = Math.min(currentPage.value * perPage.value, total)
  
  return {
    current_page: currentPage.value,
    per_page: perPage.value,
    total: total,
    from: from,
    to: to
  }
})

const filteredPending = computed(() => {
  const pending = filteredRows.value.filter(r => r.status === 'pending')
  const start = (currentPage.value - 1) * perPage.value
  const end = start + perPage.value
  return pending.slice(start, end)
})

const filteredApproved = computed(() => {
  const approved = filteredRows.value.filter(r => r.status === 'approved')
  const start = (currentPage.value - 1) * perPage.value
  const end = start + perPage.value
  return approved.slice(start, end)
})

const filteredDisapproved = computed(() => {
  const disapproved = filteredRows.value.filter(r => r.status === 'disapproved')
  const start = (currentPage.value - 1) * perPage.value
  const end = start + perPage.value
  return disapproved.slice(start, end)
})

const filteredCancelled = computed(() => {
  const cancelled = filteredRows.value.filter(r => r.status === 'cancelled')
  const start = (currentPage.value - 1) * perPage.value
  const end = start + perPage.value
  return cancelled.slice(start, end)
})

const filteredExpired = computed(() => {
  const expired = filteredRows.value.filter(r => r.status === 'expired')
  const start = (currentPage.value - 1) * perPage.value
  const end = start + perPage.value
  return expired.slice(start, end)
})

const handleSearch = (searchValue) => {
  filters.value.search = searchValue
  currentPage.value = 1 // Reset to first page when searching
}

const handleFiltersChange = (newFilters) => {
  filters.value = {
    ...filters.value,
    ...newFilters
  }
  currentPage.value = 1 // Reset to first page when filtering
}

// Pagination handlers
function onPageChange(page) {
  currentPage.value = page
}

function onPerPageChange(newPerPage) {
  perPage.value = newPerPage
  currentPage.value = 1 // Reset to first page when changing per page
}

// View-only: Leave Monitoring is for viewing and monitoring only
const onView = (row) => {
  selectedRow.value = row
  viewerOpen.value = true
}

const counts = computed(() => {
  const today = new Date()
  const isSameDayOrBetween = (d1, d2, t) => {
    if (!(d1 instanceof Date) || isNaN(d1) || !(d2 instanceof Date) || isNaN(d2)) return false
    const start = new Date(d1.getFullYear(), d1.getMonth(), d1.getDate())
    const end = new Date(d2.getFullYear(), d2.getMonth(), d2.getDate(), 23, 59, 59, 999)
    return t >= start && t <= end
  }
  const pending = filteredRows.value.filter(r => r.status === 'pending').length
  const approved = filteredRows.value.filter(r => r.status === 'approved').length
  const disapproved = filteredRows.value.filter(r => r.status === 'disapproved').length
  const cancelled = filteredRows.value.filter(r => r.status === 'cancelled').length
  const expired = filteredRows.value.filter(r => r.status === 'expired').length
  const empSet = new Set()
  const allApprovedItems = filteredRows.value.filter(r => r.status === 'approved')
  allApprovedItems.forEach(r => {
    const d1 = r.date_from ? new Date(r.date_from) : (r.dateFrom || null)
    const d2 = r.date_to ? new Date(r.date_to) : (r.dateTo || null)
    if (isSameDayOrBetween(d1 || r.dateFrom, d2 || r.dateTo, today)) {
      empSet.add(r.employee_id)
    }
  })
  return { pending, approved, disapproved, cancelled, expired, todayOnLeave: empSet.size }
})

const todayOnLeaveEmployees = computed(() => {
  const today = new Date()
  const isSameDayOrBetween = (d1, d2, t) => {
    if (!(d1 instanceof Date) || isNaN(d1) || !(d2 instanceof Date) || isNaN(d2)) return false
    const start = new Date(d1.getFullYear(), d1.getMonth(), d1.getDate())
    const end = new Date(d2.getFullYear(), d2.getMonth(), d2.getDate(), 23, 59, 59, 999)
    return t >= start && t <= end
  }

  const map = new Map()
  for (const row of filteredRows.value) {
    if (row.status !== 'approved') continue
    const d1 = row.date_from ? new Date(row.date_from) : (row.dateFrom || null)
    const d2 = row.date_to ? new Date(row.date_to) : (row.dateTo || null)
    if (!isSameDayOrBetween(d1 || row.dateFrom, d2 || row.dateTo, today)) continue
    const key = String(row.employee_id || row.id || row.employee_no || row.name || '')
    if (!key) continue
    if (!map.has(key)) map.set(key, row)
  }
  return Array.from(map.values())
})

const overviewItems = computed(() => ([
  { value: counts.value.pending, label: 'Pending', icon: 'Clock', type: 'pending' },
  { value: counts.value.approved, label: 'Approved', icon: 'CircleCheck', type: 'approved' },
  { value: counts.value.disapproved, label: 'Disapproved', icon: 'CloseBold', type: 'disapproved' },
  { value: counts.value.cancelled, label: 'Cancelled', icon: 'RemoveFilled', type: 'cancelled' },
  { value: counts.value.expired, label: 'Expired', icon: 'Warning', type: 'expired' },
  { value: counts.value.todayOnLeave, label: 'On Leave Today', icon: 'Calendar', type: 'today' },
]))

function onOverviewItemClick(item) {
  if (item?.label === 'On Leave Today') {
    leaveTodayDialogOpen.value = true
  }
}

// Handle tab change event
const onTabChange = (tabName) => {
  currentPage.value = 1
  // Update URL query parameter to preserve tab state
  if (route.query.tab !== tabName) {
    router.push({ query: { ...route.query, tab: tabName } })
  }
}

// Sync tab with URL on mount and route changes (for browser back/forward or direct URL access)
watch(() => route.query.tab, (newTab) => {
  if (newTab && ['pending', 'approved', 'disapproved', 'cancelled', 'expired'].includes(newTab) && activeTab.value !== newTab) {
    activeTab.value = newTab
    currentPage.value = 1
  }
}, { immediate: true })

onMounted(() => {
  // Set initial tab from URL if present
  if (route.query.tab) {
    activeTab.value = route.query.tab
  }
  load()
})
</script>

<style scoped>
.mb-3 { margin-bottom: 12px; }
.filters { display: flex; align-items: center; }
.filters .mr-2 { margin-right: 8px; max-width: 320px; }
.emp { display: flex; align-items: center; gap: 10px; }
.emp .avatar { width: 36px; height: 36px; border-radius: 50%; object-fit: cover; }
.name { font-weight: 600; }
.muted { color: #666; font-size: 12px; }
.toolbar-row {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: nowrap;
}
.flex-spacer { flex: 1; }
@media (max-width: 768px) {
  .toolbar-row { flex-wrap: wrap; }
}

.names-list {
  list-style: none;
  padding-left: 0;
  margin: 0;
  max-height: 52vh;
  overflow-y: auto;
}

.names-list li {
  padding: 8px 0;
  border-bottom: 1px solid #f2f3f5;
}
</style>
