<template>
  <PageScaffold
    title="OB Monitoring"
    subtitle="Track and monitor official business activities and approvals"
    :breadcrumbs="[{ label: 'Timekeeping Module', to: '/' }, { label: 'OB Monitoring' }]"
  >
    <Overview
      :items="overviewItems"
      :clickable-labels="['On OB Today']"
      @item-click="onOverviewItemClick"
    />
    
    <!-- Unified Search/Filters/Export -->
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
          :filename="'ob_monitoring_report'"
          :loading="loading"
          :on-excel="handleExcelExport"
          :on-pdf="handlePdfExport"
          :on-word="handleWordExport"
        />
      </template>
    </SearchFiltersContainer>

    <el-tabs v-model="activeTab" @tab-change="onTabChange">
      <el-tab-pane label="For Approval" name="for_approval" />
      <el-tab-pane label="Approved" name="approved" />
      <el-tab-pane label="Disapproved" name="disapproved" />
      <el-tab-pane label="Cancelled" name="cancelled" />
      <el-tab-pane label="Expired" name="expired" />
    </el-tabs>

    <OBTable
      :rows="paginatedData"
      :loading="loading"
      :hideActions="false"
      :allowCancel="false"
      :activeTab="activeTab"
      :budgetOfficer="budgetOfficer"
      @view="viewRow"
    />

    <Pagination
      :pagination="paginationData"
      :per-page-options="[10, 25, 50, 100]"
      @page-change="onPageChange"
      @per-page-change="onPerPageChange"
    />

    <OBViewer v-model="viewerOpen" :record="viewedRecord" />

    <el-dialog
      v-model="obTodayDialogOpen"
      title="Employees On OB Today"
      width="560px"
      destroy-on-close
    >
      <ul v-if="todayOnOBEmployees.length" class="names-list">
        <li v-for="emp in todayOnOBEmployees" :key="emp.employee_id || emp.id || emp.employee_no">
          <EmployeeDataPopulate :employee="emp" field="namePosition" />
        </li>
      </ul>
      <el-empty v-else description="No employees found." />
      <template #footer>
        <el-button @click="obTodayDialogOpen = false">Close</el-button>
      </template>
    </el-dialog>

    <el-empty v-if="!loading && paginatedData.length === 0" description="No official business records found" />
  </PageScaffold>
  
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import PageScaffold from '../../components/Reusable_Components/PageScaffold.vue'
import OBTable from '../../components/OB_Monitoring/OBTable.vue'
import SearchFiltersContainer from '../../components/Reusable_Components/SearchFiltersContainer.vue'
import Pagination from '../../components/Reusable_Components/Pagination.vue'
import PreviewExport from '../../components/Reusable_Components/Preview&Export.vue'
import { obMonitoringService } from '../../services/api'
import { useFilterLogic } from '../../Composables/Filter_Logic.js'
import { ElMessage } from 'element-plus'
import { Clock, CircleCheck, CloseBold, RemoveFilled, Calendar } from '@element-plus/icons-vue'
import Overview from '../../components/Reusable_Components/Overview.vue'
import OBViewer from '../../components/OB_Monitoring/OBViewer.vue'
import EmployeeDataPopulate from '../../components/Reusable_Components/Employee_Data_Populate.vue'
import { formatEmployeeName } from '../../Composables/useNameFormatter'

const route = useRoute()
const router = useRouter()

const loading = ref(false)
// Initialize active tab from URL query or default (align with OT: for_approval, approved, disapproved, cancelled, expired)
const activeTab = ref(route.query.tab || 'for_approval')
const pendingRows = ref([])
const approvedRows = ref([])
const disapprovedRows = ref([])
const cancelledRows = ref([])
const expiredRows = ref([])
const viewerOpen = ref(false)
const viewedRecord = ref(null)
const budgetOfficer = ref({ name: '', position: '' })
const obTodayDialogOpen = ref(false)

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

// Reference data for filters
const departmentsRef = ref([])
const positionsRef = ref([])

// Use filter logic
const { filterEmployees } = useFilterLogic()

const displayedRows = computed(() => {
  switch (activeTab.value) {
    case 'approved':
      return approvedRows.value
    case 'disapproved':
      return disapprovedRows.value
    case 'cancelled':
      return cancelledRows.value
    case 'expired':
      return expiredRows.value
    default:
      return pendingRows.value
  }
})

// Normalize employee data for consistent field names
const normalizeEmployees = (employees) => {
  return employees.map(emp => {
    const filedDate = toDate(emp.created_at)
    const coverageStart = toDate(emp.date_time_from) || toDate(emp.date)
    const coverageEnd = toDate(emp.date_time_to) || coverageStart

    return {
      ...emp,
      name: formatEmployeeName(emp),
      employee_no: emp.employee_no || '',
      position: emp.position || '',
      department: emp.department || '',
      position_id: emp.position_id || null,
      department_id: emp.department_id || null,
      filedSort: filedDate ? filedDate.getTime() : 0,
      dateRangeSort: coverageStart ? coverageStart.getTime() : 0,
      dateRangeEndSort: coverageEnd ? coverageEnd.getTime() : 0
    }
  })
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

// Filtered rows based on search and filters
const filteredRows = computed(() => {
  const normalized = normalizeEmployees(displayedRows.value)
  
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
  switch (activeTab.value) {
    case 'for_approval': return 'For Approval'
    case 'approved': return 'Approved'
    case 'disapproved': return 'Disapproved'
    case 'cancelled': return 'Cancelled'
    case 'expired': return 'Expired'
    default: return 'OB Monitoring'
  }
})

// Preview title with tab name
const previewTitle = computed(() => {
  return `OB Monitoring Report - ${tabDisplayName.value}`
})

// Generate HTML content for preview with proper layout
const reportHtmlContent = computed(() => {
  if (!filteredRows.value.length) {
    return '<p style="padding: 5px; text-align: center; color: #909399;">No data available for report. Please apply filters or wait for data to load.</p>'
  }
  
  let html = '<div style="padding: 5px; font-family: Arial, sans-serif;">'
  
  // Report Title with tab name
  html += '<div style="text-align: center; margin-bottom: 5px; page-break-inside: avoid;">'
  html += '<h2 style="margin: 0 0 3px 0; font-size: 20px; font-weight: bold; color: #303133;">'
  html += `OB Monitoring Report - ${tabDisplayName.value}`
  html += '</h2>'
  html += '</div>'
  
  // Group OB records by employee
  const groupedByEmployee = {}
  filteredRows.value.forEach(ob => {
    const empKey = ob.employee_id || `${ob.employee_no}_${ob.name}`
    if (!groupedByEmployee[empKey]) {
      groupedByEmployee[empKey] = {
        employee: ob,
        obRecords: []
      }
    }
    groupedByEmployee[empKey].obRecords.push(ob)
  })
  
  const employeeGroups = Object.values(groupedByEmployee)
  
  // Employee data - formatted per employee with all their OB applications
  employeeGroups.forEach((group, empIndex) => {
    const employee = group.employee
    const obRecords = group.obRecords
    
    html += '<div style="margin-bottom: 5px; page-break-inside: avoid;">'
    
    // Employee name and details (shown once per employee)
    html += `<h3 style="margin: 0 0 3px 0; font-size: 16px; font-weight: bold; color: #303133;">${empIndex + 1}. ${employee.name || 'N/A'}</h3>`
    
    // Employee details
    html += '<div style="margin-bottom: 4px; font-size: 12px; color: #606266;">'
    html += `<span>Employee No: ${employee.employee_no || 'N/A'}</span>`
    if (employee.position) html += ` | <span>Position: ${employee.position}</span>`
    if (employee.department) html += ` | <span>Department: ${employee.department}</span>`
    html += '</div>'
    
    // Display all OB applications for this employee
    obRecords.forEach((ob, obIndex) => {
      // OB application header (if multiple applications)
      if (obRecords.length > 1) {
        html += `<div style="margin-top: 4px; margin-bottom: 2px; font-size: 13px; font-weight: 600; color: #606266;">Application ${obIndex + 1}:</div>`
      }
      
      // OB Details in a structured format
      html += '<div style="margin-left: 8px; margin-top: 2px;">'
      
      // Date and Time Covered
      const dateFrom = toDate(ob.date_time_from) || toDate(ob.date)
      const dateTo = toDate(ob.date_time_to) || toDate(ob.date)
      if (dateFrom && dateTo) {
        const dateStr = formatDateRangeForReport(dateFrom, dateTo)
        const timeStr = formatTimeRangeForReport(dateFrom, dateTo)
        html += '<div style="margin-bottom: 2px; font-size: 14px; color: #303133; line-height: 1.3;">'
        html += '<span style="font-weight: 500;">Date and Time Covered:</span>'
        html += ` <span style="font-weight: 600;">${dateStr} • ${timeStr}</span>`
        html += '</div>'
      }
      
      // Purpose
      if (ob.purpose) {
        html += '<div style="margin-bottom: 2px; font-size: 14px; color: #303133; line-height: 1.3;">'
        html += '<span style="font-weight: 500;">Purpose:</span>'
        html += ` <span style="font-weight: 600;">${ob.purpose}</span>`
        html += '</div>'
      }
      
      // Client
      if (ob.client) {
        html += '<div style="margin-bottom: 2px; font-size: 14px; color: #303133; line-height: 1.3;">'
        html += '<span style="font-weight: 500;">Client:</span>'
        html += ` <span style="font-weight: 600;">${ob.client}</span>`
        html += '</div>'
      }
      
      // Filed Date
      if (ob.created_at) {
        html += '<div style="margin-bottom: 2px; font-size: 14px; color: #303133; line-height: 1.3;">'
        html += '<span style="font-weight: 500;">Filed:</span>'
        html += ` <span style="font-weight: 600;">${formatDateTimeForReport(ob.created_at)}</span>`
        html += '</div>'
      }
      
      // Status
      if (ob.status) {
        html += '<div style="margin-bottom: 2px; font-size: 14px; color: #303133; line-height: 1.3;">'
        html += '<span style="font-weight: 500;">Status:</span>'
        html += ` <span style="font-weight: 600;">${ob.status}</span>`
        html += '</div>'
      }
      
      // Approvers
      if (ob.approver_1) {
        html += '<div style="margin-bottom: 2px; font-size: 14px; color: #303133; line-height: 1.3;">'
        html += '<span style="font-weight: 500;">Approver 1:</span>'
        html += ` <span style="font-weight: 600;">${ob.approver_1}</span>`
        if (ob.processed_date) {
          html += ` <span style="color: #606266;">(${formatDateTimeForReport(ob.processed_date)})</span>`
        }
        html += '</div>'
      }
      
      if (ob.approver_2) {
        html += '<div style="margin-bottom: 2px; font-size: 14px; color: #303133; line-height: 1.3;">'
        html += '<span style="font-weight: 500;">Approver 2:</span>'
        html += ` <span style="font-weight: 600;">${ob.approver_2}</span>`
        if (ob.processed_date_2) {
          html += ` <span style="color: #606266;">(${formatDateTimeForReport(ob.processed_date_2)})</span>`
        }
        html += '</div>'
      }
      
      if (ob.approver_3) {
        html += '<div style="margin-bottom: 2px; font-size: 14px; color: #303133; line-height: 1.3;">'
        html += '<span style="font-weight: 500;">Approver 3:</span>'
        html += ` <span style="font-weight: 600;">${ob.approver_3}</span>`
        if (ob.processed_date_3) {
          html += ` <span style="color: #606266;">(${formatDateTimeForReport(ob.processed_date_3)})</span>`
        }
        html += '</div>'
      }
      
      if (ob.approver_4) {
        html += '<div style="margin-bottom: 2px; font-size: 14px; color: #303133; line-height: 1.3;">'
        html += '<span style="font-weight: 500;">Approver 4:</span>'
        html += ` <span style="font-weight: 600;">${ob.approver_4}</span>`
        html += '</div>'
      }
      
      html += '</div>'
      
      // Add separator between applications (if multiple)
      if (obIndex < obRecords.length - 1) {
        html += '<hr style="border: none; border-top: 1px solid #e0e0e0; margin: 4px 0;" />'
      }
    })
    
    html += '</div>'
    
    // Add separator line between employees (except last one)
    if (empIndex < employeeGroups.length - 1) {
      html += '<hr style="border: none; border-top: 1px solid #e4e7ed; margin: 4px 0;" />'
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

function formatDateRangeForReport(from, to) {
  if (!from || !to) return 'N/A'
  
  // Check if dates are on the same day
  const sameDay = from.getDate() === to.getDate() && 
                  from.getMonth() === to.getMonth() && 
                  from.getFullYear() === to.getFullYear()
  
  const monthNames = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'
  ]
  
  if (sameDay) {
    const month = monthNames[from.getMonth()]
    return `${month} ${from.getDate()}, ${from.getFullYear()}`
  }
  
  const sameMonth = from.getMonth() === to.getMonth() && from.getFullYear() === to.getFullYear()
  if (sameMonth) {
    const month = monthNames[from.getMonth()]
    return `${month} ${from.getDate()} - ${to.getDate()}, ${from.getFullYear()}`
  }
  
  const fromStr = `${monthNames[from.getMonth()]} ${from.getDate()}, ${from.getFullYear()}`
  const toStr = `${monthNames[to.getMonth()]} ${to.getDate()}, ${to.getFullYear()}`
  return `${fromStr} - ${toStr}`
}

function formatTimeRangeForReport(from, to) {
  if (!from || !to) return 'N/A'
  let fromHours = from.getHours()
  const fromMinutes = from.getMinutes().toString().padStart(2, '0')
  const fromIsPM = fromHours >= 12
  const fromMeridiem = fromIsPM ? 'PM' : 'AM'
  fromHours = fromHours % 12
  if (fromHours === 0) fromHours = 12
  const fromTime = `${fromHours}:${fromMinutes} ${fromMeridiem}`
  
  let toHours = to.getHours()
  const toMinutes = to.getMinutes().toString().padStart(2, '0')
  const toIsPM = toHours >= 12
  const toMeridiem = toIsPM ? 'PM' : 'AM'
  toHours = toHours % 12
  if (toHours === 0) toHours = 12
  const toTime = `${toHours}:${toMinutes} ${toMeridiem}`
  
  return `${fromTime} - ${toTime}`
}

function formatDateTimeForReport(value) {
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
  let hours = d.getHours()
  const minutes = d.getMinutes().toString().padStart(2, '0')
  const isPM = hours >= 12
  const meridiem = isPM ? 'PM' : 'AM'
  hours = hours % 12
  if (hours === 0) hours = 12
  return `${month} ${day}, ${year} ${hours}:${minutes} ${meridiem}`
}

// Export functions
async function handleExcelExport() {
  try {
    const { API_BASE_URL } = await import('../../config/api')
    
    // filteredRows already contains the correct tab's data (from displayedRows)
    // No need to filter again by status as the data is already separated by tab
    const currentTabRows = filteredRows.value || []
    
    const reportData = {
      report_type: 'ob_monitoring',
      data: {
        tab: activeTab.value,
        tab_display_name: tabDisplayName.value,
        ob_records: currentTabRows
      },
      filename: `ob_monitoring_${activeTab.value.toLowerCase()}_report`
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
    link.download = `ob_monitoring_${activeTab.value.toLowerCase()}_report.xlsx`
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
    
    // filteredRows already contains the correct tab's data (from displayedRows)
    // No need to filter again by status as the data is already separated by tab
    const currentTabRows = filteredRows.value || []
    
    const reportData = {
      report_type: 'ob_monitoring',
      data: {
        tab: activeTab.value,
        tab_display_name: tabDisplayName.value,
        ob_records: currentTabRows
      },
      filename: `ob_monitoring_${activeTab.value.toLowerCase()}_report`
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
    link.download = `ob_monitoring_${activeTab.value.toLowerCase()}_report.docx`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    URL.revokeObjectURL(url)
  } catch (err) {
    console.error('Word export failed:', err)
    ElMessage.error('Failed to export Word report')
  }
}

// Helper function to deduplicate OB entries by id
function deduplicateOBEntries(entries) {
  if (!Array.isArray(entries)) return []
  const seen = new Set()
  return entries.filter(entry => {
    const id = entry?.id
    if (id === undefined || id === null) return true // Keep entries without id (shouldn't happen, but just in case)
    if (seen.has(id)) return false
    seen.add(id)
    return true
  })
}

function toBoolFlag(v) {
  return v === true || v === 1 || v === '1'
}

function isConfiguredLevel(approvedFlag, disapprovedFlag) {
  return approvedFlag !== null && approvedFlag !== undefined &&
    disapprovedFlag !== null && disapprovedFlag !== undefined
}

function isOBApprovedDynamic(row) {
  const level1Approved = toBoolFlag(row?.approved)
  const level2Required = isConfiguredLevel(row?.approved_2, row?.disapproved_2)
  const level3Required = isConfiguredLevel(row?.approved_3, row?.disapproved_3)
  const level2Approved = toBoolFlag(row?.approved_2)
  const level3Approved = toBoolFlag(row?.approved_3)

  return level1Approved &&
    (!level2Required || level2Approved) &&
    (!level3Required || level3Approved)
}

function isOBDisapproved(row) {
  return toBoolFlag(row?.disapproved) || toBoolFlag(row?.disapproved_2) || toBoolFlag(row?.disapproved_3)
}

function isOBCancelled(row) {
  return toBoolFlag(row?.is_cancel) || toBoolFlag(row?.is_cancel_2) || toBoolFlag(row?.is_cancel_3)
}

function isOBExpired(row) {
  if (!row) return false
  const from = toDate(row.date_time_from) || toDate(row.date)
  const to = toDate(row.date_time_to) || from
  if (!from || !to) return false
  const now = new Date()
  return to < now
}

function classifyOBStatus(row) {
  if (isOBCancelled(row)) return 'cancelled'
  if (isOBDisapproved(row)) return 'disapproved'
  if (isOBApprovedDynamic(row)) return 'approved'
  if (isOBExpired(row)) return 'expired'
  return 'for_approval'
}

async function loadData() {
  loading.value = true
  try {
    const [res, deptRes, posRes] = await Promise.all([
      obMonitoringService.fetchMonitoring(),
      obMonitoringService.api?.get('/departments') || Promise.resolve({ data: [] }),
      obMonitoringService.api?.get('/positions') || Promise.resolve({ data: [] })
    ])
    
    // Normalize and dynamically re-bucket rows so monitoring follows configured approver levels.
    const payload = res?.data || {}
    const mergedRows = deduplicateOBEntries([
      ...(payload.ForapprovalEmployeeOB || []),
      ...(payload.ApprovedEmployeeOB || []),
      ...(payload.DisapprovedEmployeeOB || []),
      ...(payload.CancelledEmployeeOB || []),
      ...(payload.ExpiredEmployeeOB || []),
    ])

    pendingRows.value = mergedRows.filter((r) => classifyOBStatus(r) === 'for_approval')
    approvedRows.value = mergedRows.filter((r) => classifyOBStatus(r) === 'approved')
    disapprovedRows.value = mergedRows.filter((r) => classifyOBStatus(r) === 'disapproved')
    cancelledRows.value = mergedRows.filter((r) => classifyOBStatus(r) === 'cancelled')
    expiredRows.value = mergedRows.filter((r) => classifyOBStatus(r) === 'expired')
    budgetOfficer.value = {
      name: res?.data?.budget_officer || '',
      position: res?.data?.budget_officer_position || ''
    }
    
    // Load reference data
    departmentsRef.value = deptRes?.data || []
    positionsRef.value = posRes?.data || []
  } catch (e) {
    ElMessage.error(e?.message || 'Failed to load OB monitoring')
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
  router.push({ query: { ...route.query, tab: tabName || activeTab.value } })
}

// View-only: OB Monitoring is for viewing and monitoring only

function viewRow(row) {
  viewedRecord.value = row
  viewerOpen.value = true
}

// Sync tab with URL on mount and route changes (for browser back/forward or direct URL access)
watch(() => route.query.tab, (newTab) => {
  if (newTab && ['for_approval', 'approved', 'disapproved', 'cancelled', 'expired'].includes(newTab) && activeTab.value !== newTab) {
    activeTab.value = newTab
    currentPage.value = 1
  }
}, { immediate: true })

const tabQueryToName = { Pending: 'for_approval', Approved: 'approved', Disapproved: 'disapproved', Cancelled: 'cancelled' }

onMounted(() => {
  // Set initial tab from URL if present (normalize legacy tab names to new names)
  const q = route.query.tab
  if (q) {
    activeTab.value = tabQueryToName[q] || (['for_approval', 'approved', 'disapproved', 'cancelled', 'expired'].includes(q) ? q : 'for_approval')
  }
  loadData()
})

function toDate(value) {
  if (!value) return null
  const v = typeof value === 'string' ? value.replace(' .000', '').replace('.000', '').replace(' ', 'T') : value
  const d = new Date(v)
  return isNaN(d.getTime()) ? null : d
}

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
  for (const r of approvedRows.value) {
    if (isTodayWithin(r.date_time_from, r.date_time_to, r.date)) {
      if (r.employee_id) empSet.add(r.employee_id)
    }
  }
  return {
    pending: pendingRows.value.length,
    approved: approvedRows.value.length,
    disapproved: disapprovedRows.value.length,
    cancelled: cancelledRows.value.length,
    expired: expiredRows.value.length,
    todayOnOB: empSet.size,
  }
})

const todayOnOBEmployees = computed(() => {
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
  for (const row of approvedRows.value) {
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
  { value: counts.value.todayOnOB, label: 'On OB Today', icon: 'Calendar', type: 'today' },
]))

function onOverviewItemClick(item) {
  if (item?.label === 'On OB Today') {
    obTodayDialogOpen.value = true
  }
}
</script>

<style scoped>
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

