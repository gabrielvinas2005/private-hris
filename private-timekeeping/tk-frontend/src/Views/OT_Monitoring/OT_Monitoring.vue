<template>
  <PageScaffold
    title="OT Monitoring"
    subtitle="Monitor overtime hours, approvals, and overtime management"
    :breadcrumbs="[{ label: 'Timekeeping Module', to: '/' }, { label: 'OT Monitoring' }]"
  >
    <Overview
      :items="overviewItems"
      :clickable-labels="['Will OT Today']"
      @item-click="onOverviewItemClick"
    />

    <!-- Unified Search/Filters Container -->
    <SearchFiltersContainer
      :searchValue="filters.search"
      @update:searchValue="val => { filters.search = val }"
      :filtersValue="filters"
      @update:filtersValue="val => Object.assign(filters, val)"
      :filters="availableFilters"
      :search-placeholder="'Search employee, position, or department...'"
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
          :filename="'ot_monitoring_report'"
          :loading="loading"
          :on-excel="handleExcelExport"
          :on-pdf="handlePdfExport"
          :on-word="handleWordExport"
        />
      </template>
    </SearchFiltersContainer>

    <el-tabs v-model="active" @tab-change="onTabChange">
      <el-tab-pane label="For Approval" name="for_approval">
        <OTTable
          v-if="loading || paginatedData.length > 0"
          :rows="paginatedData"
          :loading="loading"
          :activeTab="active"
          :executiveDirector="executiveDirector"
          hide-actions
          @view="onView"
        />

        <Pagination
          v-if="loading || paginatedData.length > 0"
          :pagination="paginationData"
          :per-page-options="[10, 25, 50, 100]"
          @page-change="onPageChange"
          @per-page-change="onPerPageChange"
        />

        <el-empty v-if="!loading && paginatedData.length === 0" description="No overtime records found" />
      </el-tab-pane>
      <el-tab-pane label="Approved" name="approved">
        <OTTable 
          v-if="loading || paginatedData.length > 0"
          :rows="paginatedData" 
          :loading="loading" 
          :activeTab="active"
          :executiveDirector="executiveDirector"
          hide-actions
          @view="onView"
        />
        
        <Pagination
          v-if="loading || paginatedData.length > 0"
          :pagination="paginationData"
          :per-page-options="[10, 25, 50, 100]"
          @page-change="onPageChange"
          @per-page-change="onPerPageChange"
        />
        <el-empty v-if="!loading && paginatedData.length === 0" description="No overtime records found" />
      </el-tab-pane>
      <el-tab-pane label="Disapproved" name="disapproved">
        <OTTable v-if="loading || paginatedData.length > 0" :rows="paginatedData" :loading="loading" :activeTab="active" :executiveDirector="executiveDirector" hide-actions @view="onView" />
        
        <Pagination
          v-if="loading || paginatedData.length > 0"
          :pagination="paginationData"
          :per-page-options="[10, 25, 50, 100]"
          @page-change="onPageChange"
          @per-page-change="onPerPageChange"
        />
        <el-empty v-if="!loading && paginatedData.length === 0" description="No overtime records found" />
      </el-tab-pane>
      <el-tab-pane label="Cancelled" name="cancelled">
        <OTTable v-if="loading || paginatedData.length > 0" :rows="paginatedData" :loading="loading" :activeTab="active" :executiveDirector="executiveDirector" hide-actions @view="onView" />
        
        <Pagination
          v-if="loading || paginatedData.length > 0"
          :pagination="paginationData"
          :per-page-options="[10, 25, 50, 100]"
          @page-change="onPageChange"
          @per-page-change="onPerPageChange"
        />
        <el-empty v-if="!loading && paginatedData.length === 0" description="No overtime records found" />
      </el-tab-pane>
      <el-tab-pane label="Expired" name="expired">
        <OTTable v-if="loading || paginatedData.length > 0" :rows="paginatedData" :loading="loading" :activeTab="active" :executiveDirector="executiveDirector" hide-actions @view="onView" />
        
        <Pagination
          v-if="loading || paginatedData.length > 0"
          :pagination="paginationData"
          :per-page-options="[10, 25, 50, 100]"
          @page-change="onPageChange"
          @per-page-change="onPerPageChange"
        />
        <el-empty v-if="!loading && paginatedData.length === 0" description="No overtime records found" />
      </el-tab-pane>
    </el-tabs>

    <OTViewer v-model="viewerOpen" :record="viewedRecord" />

    <el-dialog
      v-model="otTodayDialogOpen"
      title="Employees Will OT Today"
      width="560px"
      destroy-on-close
    >
      <ul v-if="todayOTEmployees.length" class="names-list">
        <li v-for="emp in todayOTEmployees" :key="emp.employee_id || emp.id || emp.employee_no">
          <EmployeeDataPopulate :employee="emp" field="namePosition" />
        </li>
      </ul>
      <el-empty v-else description="No employees found." />
      <template #footer>
        <el-button @click="otTodayDialogOpen = false">Close</el-button>
      </template>
    </el-dialog>
  </PageScaffold>
  
</template>

<script setup>
import PageScaffold from '../../components/Reusable_Components/PageScaffold.vue'
import SearchFiltersContainer from '../../components/Reusable_Components/SearchFiltersContainer.vue'
import Pagination from '../../components/Reusable_Components/Pagination.vue'
import PreviewExport from '../../components/Reusable_Components/Preview&Export.vue'
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import OTTable from '../../components/OT_Monitoring/OTTable.vue'
import OTViewer from '../../components/OT_Monitoring/OTViewer.vue'
import Overview from '../../components/Reusable_Components/Overview.vue'
import EmployeeDataPopulate from '../../components/Reusable_Components/Employee_Data_Populate.vue'
import { otMonitoringService } from '../../services/api'
import { useFilterLogic } from '../../Composables/Filter_Logic'
import { formatEmployeeName } from '../../Composables/useNameFormatter'

const route = useRoute()
const router = useRouter()

// Initialize active tab from URL query or default
const active = ref(route.query.tab || 'for_approval')
const loading = ref(false)
const search = ref('')
const rows = ref({ forApproval: [], approved: [], disapproved: [], cancelled: [], expired: [] })
const viewerOpen = ref(false)
const viewedRecord = ref(null)
const otTodayDialogOpen = ref(false)

// Filter and pagination state
const filters = ref({
  search: '',
  positionId: null,
  departmentId: null,
  month: null,
  year: null
})

const currentPage = ref(1)
const perPage = ref(10)

// Use filter logic
const { filterEmployees } = useFilterLogic()

const counts = computed(() => {
  const today = new Date()
  const start = new Date(today.getFullYear(), today.getMonth(), today.getDate())
  const end = new Date(today.getFullYear(), today.getMonth(), today.getDate(), 23, 59, 59, 999)
  
  const isTodayWithin = (fromStr, toStr, dateStr) => {
    const from = toDate(fromStr) || toDate(dateStr)
    const to = toDate(toStr) || toDate(dateStr)
    if (!from || !to) return false
    return from <= end && to >= start
  }
  
  const empSet = new Set()
  for (const r of rows.value.approved || []) {
    if (isTodayWithin(r.date_time_from, r.date_time_to, r.date)) {
      if (r.employee_id) empSet.add(r.employee_id)
    }
  }
  
  return {
    pending: (rows.value.forApproval || []).length,
    approved: (rows.value.approved || []).length,
    disapproved: (rows.value.disapproved || []).length,
    cancelled: (rows.value.cancelled || []).length,
    expired: (rows.value.expired || []).length,
    willOTToday: empSet.size,
  }
})

const todayOTEmployees = computed(() => {
  const today = new Date()
  const start = new Date(today.getFullYear(), today.getMonth(), today.getDate())
  const end = new Date(today.getFullYear(), today.getMonth(), today.getDate(), 23, 59, 59, 999)

  const isTodayWithin = (fromStr, toStr, dateStr) => {
    const from = toDate(fromStr) || toDate(dateStr)
    const to = toDate(toStr) || toDate(dateStr)
    if (!from || !to) return false
    return from <= end && to >= start
  }

  const map = new Map()
  for (const row of rows.value.approved || []) {
    if (!isTodayWithin(row.date_time_from, row.date_time_to, row.date)) continue
    const key = String(row.employee_id || row.id || row.employee_no || row.name || row.full_name || '')
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
  { value: counts.value.expired, label: 'Expired', icon: 'Clock', type: 'expired' },
  { value: counts.value.willOTToday, label: 'Will OT Today', icon: 'Calendar', type: 'today' },
]))

function onOverviewItemClick(item) {
  if (item?.label === 'Will OT Today') {
    otTodayDialogOpen.value = true
  }
}

// Normalize employee data for consistent field names
const normalizeEmployees = (employees) => {
  return employees.map(emp => ({
    ...emp,
    name: formatEmployeeName(emp),
    employee_no: emp.employee_no || '',
    position: emp.position || '',
    department: emp.department || '',
    position_id: emp.position_id || null,
    department_id: emp.department_id || null
  }))
}

// Filter available filters configuration
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
      for (let i = currentYear; i >= currentYear - 5; i--) {
        years.push({ value: i, label: String(i) })
      }
      return years
    })()
  }
])

// Get current tab data
const currentTabData = computed(() => {
  switch (active.value) {
    case 'approved':
      return rows.value.approved || []
    case 'disapproved':
      return rows.value.disapproved || []
    case 'cancelled':
      return rows.value.cancelled || []
    case 'expired':
      return rows.value.expired || []
    default:
      return rows.value.forApproval || []
  }
})

// Filtered rows based on search and filters
const filteredRows = computed(() => {
  const normalized = normalizeEmployees(currentTabData.value)
  
  // Exclude month and year from filterEmployees since we handle them separately
  const { month, year, ...otherFilters } = filters.value
  let filtered = filterEmployees(normalized, otherFilters)
  
  // Get the selected month and year
  const selectedMonth = month
  const selectedYear = year
  
  // Only apply date filtering if at least one filter is NOT null
  const hasMonthFilter = selectedMonth !== null && selectedMonth !== undefined
  const hasYearFilter = selectedYear !== null && selectedYear !== undefined
  
  if (hasMonthFilter || hasYearFilter) {
    filtered = filtered.filter(row => {
      const dateFrom = toDate(row.date_time_from) || toDate(row.date)
      const dateTo = toDate(row.date_time_to) || dateFrom
      
      if (!dateFrom) return false
      
      // Case 1: Only year is selected (All Months + Specific Year)
      if (hasYearFilter && !hasMonthFilter) {
        const yearStart = new Date(selectedYear, 0, 1)
        const yearEnd = new Date(selectedYear, 11, 31, 23, 59, 59)
        return dateFrom <= yearEnd && dateTo >= yearStart
      }
      
      // Case 2: Both month and year are selected (Specific Month + Specific Year)
      if (hasMonthFilter && hasYearFilter) {
        const monthStart = new Date(selectedYear, selectedMonth - 1, 1)
        const monthEnd = new Date(selectedYear, selectedMonth, 0, 23, 59, 59)
        return dateFrom <= monthEnd && dateTo >= monthStart
      }
      
      // Case 3: Only month is selected (Specific Month + All Years)
      if (hasMonthFilter && !hasYearFilter) {
        const fromMonth = dateFrom.getMonth() + 1
        const toMonth = dateTo.getMonth() + 1
        
        if (dateFrom.getFullYear() === dateTo.getFullYear()) {
          return selectedMonth >= fromMonth && selectedMonth <= toMonth
        } else {
          const fromYear = dateFrom.getFullYear()
          const toYear = dateTo.getFullYear()
          
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
  
  return filtered
})

// Paginated data
const paginatedData = computed(() => {
  const start = (currentPage.value - 1) * perPage.value
  const end = start + perPage.value
  return filteredRows.value.slice(start, end)
})

// Pagination data for component
const paginationData = computed(() => ({
  current_page: currentPage.value,
  per_page: perPage.value,
  total: filteredRows.value.length,
  from: filteredRows.value.length > 0 ? ((currentPage.value - 1) * perPage.value) + 1 : 0,
  to: Math.min(currentPage.value * perPage.value, filteredRows.value.length)
}))

// Get tab name for title
const tabDisplayName = computed(() => {
  switch (active.value) {
    case 'for_approval': return 'For Approval'
    case 'approved': return 'Approved'
    case 'disapproved': return 'Disapproved'
    case 'cancelled': return 'Cancelled'
    case 'expired': return 'Expired'
    default: return 'OT Monitoring'
  }
})

// Preview title with tab name
const previewTitle = computed(() => {
  return `OT Monitoring Report - ${tabDisplayName.value}`
})

// Generate HTML content for preview with proper layout
const reportHtmlContent = computed(() => {
  if (!filteredRows.value.length) {
    return '<p style="padding: 2px; text-align: center; color: #909399;">No data available for report. Please apply filters or wait for data to load.</p>'
  }
  
  let html = '<div style="padding: 2px; font-family: Arial, sans-serif;">'
  
  // Report Title with tab name
  html += '<div style="text-align: center; margin-bottom: 2px; page-break-inside: avoid;">'
  html += '<h2 style="margin: 0 0 1px 0; font-size: 20px; font-weight: bold; color: #303133;">'
  html += `OT Monitoring Report - ${tabDisplayName.value}`
  html += '</h2>'
  html += '</div>'
  
  // Group OT records by employee
  const groupedByEmployee = {}
  filteredRows.value.forEach(ot => {
    const empKey = ot.employee_id || `${ot.employee_no}_${ot.first_name}_${ot.last_name}`
    if (!groupedByEmployee[empKey]) {
      groupedByEmployee[empKey] = {
        employee: ot,
        otRecords: []
      }
    }
    groupedByEmployee[empKey].otRecords.push(ot)
  })
  
  const employeeGroups = Object.values(groupedByEmployee)
  
  // Employee data - formatted per employee with all their OT applications
  employeeGroups.forEach((group, empIndex) => {
    const employee = group.employee
    const otRecords = group.otRecords
    
    html += '<div style="margin-bottom: 2px; page-break-inside: avoid;">'
    
    // Employee name and details (shown once per employee)
    const employeeName = formatEmployeeName(employee) || 'N/A'
    html += `<h3 style="margin: 0 0 1px 0; font-size: 16px; font-weight: bold; color: #303133;">${empIndex + 1}. ${employeeName}</h3>`
    
    // Employee details
    html += '<div style="margin-bottom: 1px; font-size: 12px; color: #606266;">'
    html += `<span>Employee No: ${employee.employee_no || 'N/A'}</span>`
    if (employee.position) html += ` | <span>Position: ${employee.position}</span>`
    if (employee.department) html += ` | <span>Department: ${employee.department}</span>`
    html += '</div>'
    
    // Display all OT applications for this employee
    otRecords.forEach((ot, otIndex) => {
      // OT application header (if multiple applications)
      if (otRecords.length > 1) {
        html += `<div style="margin-top: 2px; margin-bottom: 1px; font-size: 13px; font-weight: 600; color: #606266;">Application ${otIndex + 1}:</div>`
      }
      
      // OT Details in a structured format
      html += '<div style="margin-left: 8px; margin-top: 1px;">'
      
      // Date Filed
      if (ot.created_at) {
        html += '<div style="margin-bottom: 1px; font-size: 14px; color: #303133; line-height: 1.2;">'
        html += '<span style="font-weight: 500;">Date Filed:</span>'
        html += ` <span style="font-weight: 600;">${formatDateForReport(ot.created_at)}</span>`
        html += '</div>'
      }
      
      // Overtime Type
      html += '<div style="margin-bottom: 1px; font-size: 14px; color: #303133; line-height: 1.2;">'
      html += '<span style="font-weight: 500;">Overtime Type:</span>'
      html += ` <span style="font-weight: 600;">${getOvertimeTypeDisplayForReport(ot)}</span>`
      html += '</div>'
      
      // Overtime Date
      if (ot.date) {
        html += '<div style="margin-bottom: 1px; font-size: 14px; color: #303133; line-height: 1.2;">'
        html += '<span style="font-weight: 500;">Overtime Date:</span>'
        html += ` <span style="font-weight: 600;">${formatDateForReport(ot.date)}</span>`
        html += '</div>'
      }
      
      // Time Range
      if (ot.date_time_from || ot.date_time_to) {
        html += '<div style="margin-bottom: 1px; font-size: 14px; color: #303133; line-height: 1.2;">'
        html += '<span style="font-weight: 500;">Time Range:</span>'
        html += ` <span style="font-weight: 600;">${formatTimeForReport(ot.date_time_from)} - ${formatTimeForReport(ot.date_time_to)}</span>`
        html += '</div>'
      }
      
      // Total Hours
      html += '<div style="margin-bottom: 1px; font-size: 14px; color: #303133; line-height: 1.2;">'
      html += '<span style="font-weight: 500;">Total Hours:</span>'
      html += ` <span style="font-weight: 600;">${Number(ot.total_hours || 0).toFixed(2)} hours</span>`
      html += '</div>'
      
      // Remarks
      if (ot.remarks) {
        html += '<div style="margin-bottom: 1px; font-size: 14px; color: #303133; line-height: 1.2;">'
        html += '<span style="font-weight: 500;">Remarks:</span>'
        html += ` <span style="font-weight: 600;">${ot.remarks}</span>`
        html += '</div>'
      }
      
      // Status
      if (ot.status) {
        html += '<div style="margin-bottom: 1px; font-size: 14px; color: #303133; line-height: 1.2;">'
        html += '<span style="font-weight: 500;">Status:</span>'
        html += ` <span style="font-weight: 600;">${ot.status.charAt(0).toUpperCase() + ot.status.slice(1)}</span>`
        html += '</div>'
      }
      
      // Approvers
      if (ot.approver_1) {
        html += '<div style="margin-bottom: 1px; font-size: 14px; color: #303133; line-height: 1.2;">'
        html += '<span style="font-weight: 500;">Approver 1:</span>'
        html += ` <span style="font-weight: 600;">${ot.approver_1}</span>`
        if (ot.processed_date) {
          html += ` <span style="color: #606266;">(${formatDateForReport(ot.processed_date)})</span>`
        }
        html += '</div>'
      }
      
      if (ot.approver_2) {
        html += '<div style="margin-bottom: 1px; font-size: 14px; color: #303133; line-height: 1.2;">'
        html += '<span style="font-weight: 500;">Approver 2:</span>'
        html += ` <span style="font-weight: 600;">${ot.approver_2}</span>`
        if (ot.processed_date_2) {
          html += ` <span style="color: #606266;">(${formatDateForReport(ot.processed_date_2)})</span>`
        }
        html += '</div>'
      }
      
      if (ot.approver_3) {
        html += '<div style="margin-bottom: 1px; font-size: 14px; color: #303133; line-height: 1.2;">'
        html += '<span style="font-weight: 500;">Approver 3:</span>'
        html += ` <span style="font-weight: 600;">${ot.approver_3}</span>`
        if (ot.processed_date_3) {
          html += ` <span style="color: #606266;">(${formatDateForReport(ot.processed_date_3)})</span>`
        }
        html += '</div>'
      }
      
      if (ot.approver_4) {
        html += '<div style="margin-bottom: 1px; font-size: 14px; color: #303133; line-height: 1.2;">'
        html += '<span style="font-weight: 500;">Approver 4:</span>'
        html += ` <span style="font-weight: 600;">${ot.approver_4}</span>`
        html += '</div>'
      }
      
      html += '</div>'
      
      // Add separator between applications (if multiple)
      if (otIndex < otRecords.length - 1) {
        html += '<hr style="border: none; border-top: 1px solid #e0e0e0; margin: 2px 0;" />'
      }
    })
    
    html += '</div>'
    
    // Add separator line between employees (except last one)
    if (empIndex < employeeGroups.length - 1) {
      html += '<hr style="border: none; border-top: 1px solid #e4e7ed; margin: 2px 0;" />'
    }
  })
  
  html += '</div>'
  
  return html
})

// Helper functions for report formatting
function formatDateForReport(value) {
  if (!value) return 'N/A'
  const d = toDate(value)
  if (!d) return 'N/A'
  const monthNames = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'
  ]
  const month = monthNames[d.getMonth()]
  const day = d.getDate()
  const year = d.getFullYear()
  return `${month} ${day}, ${year}`
}

function formatTimeForReport(value) {
  if (!value) return 'N/A'
  const d = toDate(value)
  if (!d) return 'N/A'
  let hours = d.getHours()
  const minutes = d.getMinutes().toString().padStart(2, '0')
  const isPM = hours >= 12
  const meridiem = isPM ? 'PM' : 'AM'
  hours = hours % 12
  if (hours === 0) hours = 12
  return `${hours}:${minutes} ${meridiem}`
}

function getOvertimeTypeDisplayForReport(row) {
  if (!row) return 'Regular Overtime'
  const overtimeTypeId = row.overtime_type_id
  const serviceCredits = row.service_credits
  const isServiceCredits = serviceCredits === true || serviceCredits === 1 || serviceCredits === '1' || serviceCredits === 'true'
  
  if (overtimeTypeId === 3 && isServiceCredits) {
    return 'Regular Overtime (to COC)'
  }
  
  return row.overtime_type_name || 'Regular Overtime'
}

// Export functions
async function handleExcelExport() {
  try {
    const { API_BASE_URL } = await import('../../config/api')
    
    // filteredRows already contains the correct tab's data (from currentTabData)
    // No need to filter again by status as the data is already separated by tab
    const currentTabRows = filteredRows.value || []
    
    const reportData = {
      report_type: 'ot_monitoring',
      data: {
        tab: active.value,
        tab_display_name: tabDisplayName.value,
        ot_records: currentTabRows
      },
      filename: `ot_monitoring_${active.value}_report`
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
    link.download = `ot_monitoring_${active.value}_report.xlsx`
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
    
    // filteredRows already contains the correct tab's data (from currentTabData)
    // No need to filter again by status as the data is already separated by tab
    const currentTabRows = filteredRows.value || []
    
    const reportData = {
      report_type: 'ot_monitoring',
      data: {
        tab: active.value,
        tab_display_name: tabDisplayName.value,
        ot_records: currentTabRows
      },
      filename: `ot_monitoring_${active.value}_report`
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
    link.download = `ot_monitoring_${active.value}_report.docx`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    URL.revokeObjectURL(url)
  } catch (err) {
    console.error('Word export failed:', err)
    ElMessage.error('Failed to export Word report')
  }
}

const filtered = computed(() => {
  if (!search.value) return rows.value
  const q = search.value.toLowerCase()
  const filterFn = (r) =>
    `${formatEmployeeName(r) || ''} ${r.remarks || ''} ${r.overtime_type_name || ''}`
      .toLowerCase()
      .includes(q)
  return {
    forApproval: (rows.value.forApproval || []).filter(filterFn),
    approved: (rows.value.approved || []).filter(filterFn),
    disapproved: (rows.value.disapproved || []).filter(filterFn),
    cancelled: (rows.value.cancelled || []).filter(filterFn),
    expired: (rows.value.expired || []).filter(filterFn),
  }
})

// Executive Director data (position_id = 37)
const executiveDirector = ref({ name: '', position: '' })

async function load() {
  loading.value = true
  try {
    const data = await otMonitoringService.fetchMonitoring()
    
    // Unwrap ApiResponse data if present
    const payload = data && data.data ? data.data : data
    // Back-end returns keys: for_approval, approved, disapproved, cancelled, expired, executive_director
    rows.value = {
      forApproval: payload?.for_approval || [],
      approved: payload?.approved || [],
      disapproved: payload?.disapproved || [],
      cancelled: payload?.cancelled || [],
      expired: payload?.expired || [],
    }
    
    // Set Executive Director from API response
    if (payload?.executive_director) {
      executiveDirector.value = {
        name: payload.executive_director.name || '',
        position: payload.executive_director.position || ''
      }
    }
  } catch (e) {
    ElMessage.error(e.message || 'Failed to load OT monitoring')
  } finally {
    loading.value = false
  }
}


// Event handlers
const handleSearch = (searchValue) => {
  filters.value.search = searchValue
  currentPage.value = 1
}

const handleFiltersChange = (newFilters) => {
  filters.value = { ...filters.value, ...newFilters }
  currentPage.value = 1
}

const onPageChange = (page) => {
  currentPage.value = page
}

const onPerPageChange = (newPerPage) => {
  perPage.value = newPerPage
  currentPage.value = 1
}

const onTabChange = (tabName) => {
  currentPage.value = 1
  // Update URL query parameter to preserve tab state
  router.push({ query: { ...route.query, tab: tabName || active.value } })
}

async function onView(row) {
  try {
    // Fetch detailed record from API (backend returns { success, message, data: overtime_application })
    const response = await otMonitoringService.getOvertimeApplication(row.id)
    const payload = response?.data ?? response
    // Unwrap: API wraps the OT record in payload.data
    viewedRecord.value = payload?.data ?? payload
    viewerOpen.value = true
  } catch (error) {
    console.error('Error fetching OT record details:', error)
    console.log('Falling back to using existing row data:', row)
    viewedRecord.value = row
    viewerOpen.value = true
  }
}

// View-only: OT Monitoring is for viewing and monitoring only

function toDate(value) {
  if (!value) return null
  const v = typeof value === 'string' ? value.replace(' .000', '').replace('.000', '').replace(' ', 'T') : value
  const d = new Date(v)
  return isNaN(d.getTime()) ? null : d
}

// Sync tab with URL on mount and route changes (for browser back/forward or direct URL access)
watch(() => route.query.tab, (newTab) => {
  if (newTab && ['for_approval', 'approved', 'disapproved', 'cancelled', 'expired'].includes(newTab) && active.value !== newTab) {
    active.value = newTab
    currentPage.value = 1
  }
}, { immediate: true })

onMounted(() => {
  // Set initial tab from URL if present
  if (route.query.tab) {
    active.value = route.query.tab
  }
  load()
})
</script>

<style scoped>
.toolbar-row {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 16px;
  flex-wrap: wrap;
}

@media (max-width: 768px) {
  .toolbar-row {
    flex-direction: column;
    align-items: stretch;
    gap: 12px;
  }
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

