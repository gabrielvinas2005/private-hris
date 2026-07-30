<template>
  <PageScaffold
    title="COC Monitoring"
    subtitle="Monitor Compensatory Overtime Credits per month"
    :breadcrumbs="[{ label: 'Timekeeping Module', to: '/' }, { label: 'COC Monitoring' }]"
  >
    <SearchFiltersContainer
      :searchValue="filters.search"
      @update:searchValue="val => { filters.search = val }"
      :filtersValue="filters"
      @update:filtersValue="val => Object.assign(filters, val)"
      :filters="availableFilters"
      :search-placeholder="'Search employee or month...'"
      :filters-loading="loading"
      :search-loading="loading"
      :auto-fetch="true"
      :html-content="reportHtmlContent"
      :preview-title="previewTitle"
      :filename="'coc_monitoring_report'"
      :loading="loading"
      @search="handleSearch"
      @filters-change="handleFiltersChange"
      @excel="handleExcelExport"
      @pdf="handlePdfExport"
      @word="handleWordExport"
    >
    </SearchFiltersContainer>

    <COCDetailsTable 
      :rows="paginatedData" 
      :loading="loading"
    />

    <!-- Pagination -->
    <Pagination
      :pagination="paginationData"
      :per-page-options="[10, 25, 50, 100]"
      @page-change="onPageChange"
      @per-page-change="onPerPageChange"
    />
  </PageScaffold>
</template>

<script setup>
import PageScaffold from '../../components/Reusable_Components/PageScaffold.vue'
import SearchFiltersContainer from '../../components/Reusable_Components/SearchFiltersContainer.vue'
import Pagination from '../../components/Reusable_Components/Pagination.vue'
import { ref, computed, onMounted, watch } from 'vue'
import { ElMessage } from 'element-plus'
import { cocMonitoringService } from '../../services/api'
import { useFilterLogic } from '../../Composables/Filter_Logic'
import COCDetailsTable from '../../components/COC_Monitoring/COCDetailsTable.vue'

const loading = ref(false)
const rows = ref([])
const summary = ref({})
const months = ref([])
// Filter and pagination state
const currentCalendarYear = new Date().getFullYear()
const normalizeNumericFilter = value => {
  if (value === null || value === undefined || value === '') return null
  const num = Number(value)
  return Number.isNaN(num) ? null : num
}
const filters = ref({ 
  search: '', 
  positionId: null, 
  departmentId: null,
  monthId: null
})
const currentPage = ref(1)
const perPage = ref(10)

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
  },
  {
    key: 'monthId',
    label: 'Month',
    options: months.value,
    valueKey: 'id',
    labelKey: 'name'
  }
])

// Normalize employee data for consistent field names
const normalizeEmployees = (employees) => {
  return employees.map(emp => {
    const monthId = emp.month_id ?? emp.monthId ?? emp.months_num ?? null
    const year = emp.year ?? emp.years ?? null
    return {
      ...emp,
      name: emp.name || '',
      employee_no: emp.employee_no || '',
      position: emp.position || '',
      department: emp.department || '',
      position_id: emp.position_id || null,
      department_id: emp.department_id || null,
      monthId: monthId != null ? Number(monthId) : null,
      year: year != null ? Number(year) : null
    }
  })
}

// Filtered rows based on search and filters
// Note: Month filtering is now done on the backend, so rows.value already contains filtered data
const filteredRows = computed(() => {
  const normalized = normalizeEmployees(rows.value)
  const { monthId, ...frontendFilters } = filters.value
  return filterEmployees(normalized, frontendFilters)
})

// Get unique employees for pagination count
const uniqueEmployees = computed(() => {
  const employeeIds = new Set(filteredRows.value.map(r => r.employee_id))
  return Array.from(employeeIds)
})

// Paginated data (need to get unique employees and map back to their rows)
const paginatedData = computed(() => {
  const start = (currentPage.value - 1) * perPage.value
  const paginatedEmployeeIds = uniqueEmployees.value.slice(start, start + perPage.value)
  return filteredRows.value.filter(r => paginatedEmployeeIds.includes(r.employee_id))
})

// Pagination data for component
const paginationData = computed(() => ({
  current_page: currentPage.value,
  per_page: perPage.value,
  total: uniqueEmployees.value.length,
  from: uniqueEmployees.value.length ? (currentPage.value - 1) * perPage.value + 1 : 0,
  to: Math.min(currentPage.value * perPage.value, uniqueEmployees.value.length)
}))

// Get selected month name for title
const selectedMonthName = computed(() => {
  if (!filters.value.monthId) return ''
  const month = months.value.find(m => Number(m.id) === Number(filters.value.monthId))
  return month ? (month.name || '') : ''
})

// Preview title with month
const previewTitle = computed(() => {
  if (selectedMonthName.value) {
    return `COC Monitoring Report - ${selectedMonthName.value}`
  }
  return 'COC Monitoring Report'
})

// Generate HTML content for preview with proper layout
const reportHtmlContent = computed(() => {
  if (!filteredRows.value.length) {
    return '<p style="padding: 2px; text-align: center; color: #909399;">No data available for report. Please apply filters or wait for data to load.</p>'
  }
  
  // Get unique employees - filteredRows already contains data filtered by month
  const employeeMap = new Map()
  filteredRows.value.forEach(item => {
    if (!employeeMap.has(item.employee_id)) {
      employeeMap.set(item.employee_id, item)
    }
  })
  
  const employees = Array.from(employeeMap.values())
  
  let html = '<div style="padding: 2px; font-family: Arial, sans-serif;">'
  
  // Report Title with selected month
  html += '<div style="text-align: center; margin-bottom: 2px; page-break-inside: avoid;">'
  html += '<h2 style="margin: 0 0 1px 0; font-size: 20px; font-weight: bold; color: #303133;">'
  html += 'COC Monitoring Report'
  if (selectedMonthName.value) {
    html += ` - ${selectedMonthName.value}`
  }
  html += '</h2>'
  html += '</div>'
  
  // Employee data - formatted per employee with all details
  employees.forEach((employee, index) => {
    html += '<div style="margin-bottom: 2px; page-break-inside: avoid;">'
    
    // Employee name and details
    html += `<h3 style="margin: 0 0 1px 0; font-size: 16px; font-weight: bold; color: #303133;">${index + 1}. ${employee.name || 'N/A'}</h3>`
    
    // Employee details
    html += '<div style="margin-bottom: 1px; font-size: 12px; color: #606266;">'
    html += `<span>Employee No: ${employee.employee_no || 'N/A'}</span>`
    if (employee.position) html += ` | <span>Position: ${employee.position}</span>`
    if (employee.department) html += ` | <span>Department: ${employee.department}</span>`
    html += '</div>'
    
    // COC Details in a structured format
    html += '<div style="margin-left: 8px; margin-top: 1px;">'
    
    // Month
    if (employee.months) {
      html += '<div style="margin-bottom: 1px; font-size: 14px; color: #303133; line-height: 1.2;">'
      html += '<span style="font-weight: 500;">Month:</span>'
      html += ` <span style="font-weight: 600;">${employee.months}</span>`
      html += '</div>'
    }
    
    // Year
    if (employee.year) {
      html += '<div style="margin-bottom: 1px; font-size: 14px; color: #303133; line-height: 1.2;">'
      html += '<span style="font-weight: 500;">Year:</span>'
      html += ` <span style="font-weight: 600;">${employee.year}</span>`
      html += '</div>'
    }
    
    // Accrued Hours
    html += '<div style="margin-bottom: 1px; font-size: 14px; color: #303133; line-height: 1.2;">'
    html += '<span style="font-weight: 500;">Accrued (hrs):</span>'
    html += ` <span style="font-weight: 600;">${Number(employee.carryover || 0).toFixed(2)}</span>`
    html += '</div>'
    
    // Cumulative Hours
    html += '<div style="margin-bottom: 1px; font-size: 14px; color: #303133; line-height: 1.2;">'
    html += '<span style="font-weight: 500;">Cumulative (hrs):</span>'
    html += ` <span style="font-weight: 600;">${Number(employee.total_hours || 0).toFixed(2)}</span>`
    html += '</div>'
    
    // Leave Used (days)
    const leaveUsedDays = employee.total_leave_days ?? (employee.total_leave_hours != null ? Number(employee.total_leave_hours) / 8 : 0)
    html += '<div style="margin-bottom: 1px; font-size: 14px; color: #303133; line-height: 1.2;">'
    html += '<span style="font-weight: 500;">Leave Used (days):</span>'
    html += ` <span style="font-weight: 600;">${Number(leaveUsedDays || 0).toFixed(3)}</span>`
    html += '</div>'
    
    // Remaining Balance
    html += '<div style="margin-bottom: 1px; font-size: 14px; color: #303133; line-height: 1.2;">'
    html += '<span style="font-weight: 500;">Remaining (hrs):</span>'
    html += ` <span style="font-weight: 600;">${Number(employee.remaining_balance || 0).toFixed(2)}</span>`
    html += '</div>'
    
    // Converted Days
    html += '<div style="margin-bottom: 1px; font-size: 14px; color: #303133; line-height: 1.2;">'
    html += '<span style="font-weight: 500;">Converted (days):</span>'
    html += ` <span style="font-weight: 600;">${Number(employee.converted_days || 0).toFixed(4)}</span>`
    html += '</div>'
    
    html += '</div>'
    html += '</div>'
    
    // Add separator line between employees (except last one)
    if (index < employees.length - 1) {
      html += '<hr style="border: none; border-top: 1px solid #e4e7ed; margin: 2px 0;" />'
    }
  })
  
  html += '</div>'
  
  return html
})

// Export functions
async function handleExcelExport() {
  try {
    const { API_BASE_URL } = await import('../../config/api')
    
    // filteredRows already contains data filtered by month filter
    const currentTabRows = filteredRows.value || []
    
    const reportData = {
      report_type: 'coc_monitoring',
      data: {
        month_name: selectedMonthName.value || '',
        coc_records: currentTabRows
      },
      filename: `coc_monitoring_${selectedMonthName.value ? selectedMonthName.value.toLowerCase().replace(/\s+/g, '_') : 'report'}_report`
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
    link.download = `coc_monitoring_${selectedMonthName.value ? selectedMonthName.value.toLowerCase().replace(/\s+/g, '_') : 'report'}_report.xlsx`
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
    
    // filteredRows already contains data filtered by month filter
    const currentTabRows = filteredRows.value || []
    
    const reportData = {
      report_type: 'coc_monitoring',
      data: {
        month_name: selectedMonthName.value || '',
        coc_records: currentTabRows
      },
      filename: `coc_monitoring_${selectedMonthName.value ? selectedMonthName.value.toLowerCase().replace(/\s+/g, '_') : 'report'}_report`
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
    link.download = `coc_monitoring_${selectedMonthName.value ? selectedMonthName.value.toLowerCase().replace(/\s+/g, '_') : 'report'}_report.docx`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    URL.revokeObjectURL(url)
  } catch (err) {
    console.error('Word export failed:', err)
    ElMessage.error('Failed to export Word report')
  }
}

// Function to set default month to current month
const setDefaultMonth = () => {
  if (filters.value.monthId) return
  if (!months.value.length) return
  
  const now = new Date()
  const currentMonthIndex = now.getMonth() + 1
  const currentMonthName = now.toLocaleString('en-US', { month: 'long' })
  
  let foundMonth = months.value.find(m => Number(m.id) === currentMonthIndex)
  if (!foundMonth) {
    foundMonth = months.value.find(m => 
      (m.name || '').toLowerCase() === currentMonthName.toLowerCase() ||
      (m.abbrv || '').toLowerCase() === currentMonthName.substring(0, 3).toLowerCase()
    )
  }
  
  const fallbackMonth = months.value[0]?.id ?? null
  filters.value.monthId = normalizeNumericFilter(foundMonth?.id ?? fallbackMonth)
}

async function load() {
  loading.value = true
  try {
    const monthsList = await cocMonitoringService.fetchMonths()
    // Ensure month IDs are numbers for proper matching with el-select
    months.value = (monthsList || []).map(m => ({
      ...m,
      id: Number(m.id)
    }))
    
    // Set current month as default if not already set
    setDefaultMonth()
    
    // Fetch details with month filter (always use current year)
    const currentYear = new Date().getFullYear()
    const selectedMonthId = normalizeNumericFilter(filters.value.monthId)
    const details = await cocMonitoringService.fetchDetails(selectedMonthId, currentYear)
    const { coc_details, summary: meta } = details || {}
    rows.value = coc_details || []
    summary.value = meta || {}
  } catch (e) {
    ElMessage.error(e.message || 'Failed to load COC details')
  } finally {
    loading.value = false
  }
}

// Watch months array and set default month when it becomes available
watch(() => months.value, (newMonths) => {
  if (newMonths && newMonths.length > 0 && !filters.value.monthId) {
    setDefaultMonth()
  }
}, { immediate: true })

// Watch monthId changes and reload data
watch(() => normalizeNumericFilter(filters.value.monthId), async (newVal, oldVal) => {
  if (newVal !== oldVal && oldVal !== undefined) {
    await reloadData()
  }
})

// Event handlers
const handleSearch = (searchValue) => {
  filters.value.search = searchValue
  currentPage.value = 1
}

const handleFiltersChange = async (newFilters) => {
  filters.value = {
    ...filters.value,
    ...newFilters,
    monthId: newFilters.monthId !== undefined ? normalizeNumericFilter(newFilters.monthId) : filters.value.monthId
  }
  currentPage.value = 1
  
  // Watcher will handle reloading data when monthId changes
}

// Reload COC details data with current filters
const reloadData = async () => {
  loading.value = true
  try {
    const currentYear = new Date().getFullYear()
    const selectedMonthId = normalizeNumericFilter(filters.value.monthId)
    const details = await cocMonitoringService.fetchDetails(selectedMonthId, currentYear)
    const { coc_details, summary: meta } = details || {}
    rows.value = coc_details || []
    summary.value = meta || {}
  } catch (e) {
    ElMessage.error(e.message || 'Failed to reload COC details')
  } finally {
    loading.value = false
  }
}

const onPageChange = (page) => {
  currentPage.value = page
}

const onPerPageChange = (perPageValue) => {
  perPage.value = perPageValue
  currentPage.value = 1
}

onMounted(async () => {
  await load()
})
</script>

<style scoped>
.mb-3 { margin-bottom: 12px; }
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
</style>

