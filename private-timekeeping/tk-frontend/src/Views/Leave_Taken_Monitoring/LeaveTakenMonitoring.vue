<template>
  <PageScaffold
    title="Leave Taken Monitoring"
    subtitle="Track and analyze leave taken by employees over time"
    :breadcrumbs="[{ label: 'Timekeeping Module', to: '/' }, { label: 'Leave Taken Monitoring' }]"
  >
    <SearchFiltersContainer
      :searchValue="filters.search"
      @update:searchValue="val => { filters.search = val }"
      :filtersValue="filters"
      @update:filtersValue="val => Object.assign(filters, val)"
      :filters="availableFilters"
      :search-placeholder="'Search employee or position...'"
      :filters-loading="filtersLoading"
      :search-loading="loading"
      :auto-fetch="false"
      :html-content="reportHtmlContent"
      :preview-title="'Leave Taken Monitoring Report'"
      :filename="'leave_taken_monitoring_report'"
      :loading="loading"
      @search="handleSearch"
      @filters-change="handleFiltersChange"
      @excel="handleExcelExport"
      @pdf="handlePdfExport"
      @word="handleWordExport"
    />

    <LeaveTakenTable 
      :items="items" 
      :loading="loading" 
      :pagination="pagination"
      @select="onSelect"
      @page-change="onPageChange"
      @per-page-change="onPerPageChange"
    />

    <LeaveTakenViewer v-model="viewerOpen" :header="selectedHeader" :rows="leaves" :year="year" :loading="loading" @change-year="onChangeYear" />
  </PageScaffold>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import PageScaffold from '../../components/Reusable_Components/PageScaffold.vue'
import SearchFiltersContainer from '../../components/Reusable_Components/SearchFiltersContainer.vue'
import LeaveTakenTable from '../../components/Leave_Taken_Monitoring/LeaveTakenTable.vue'
import LeaveTakenViewer from '../../components/Leave_Taken_Monitoring/LeaveTakenViewer.vue'
import { leaveTakenMonitoringService, api as rawApi } from '../../services/api'
import { ElMessage } from 'element-plus'

const items = ref([])
const loading = ref(false)
const filtersLoading = ref(false)
const pagination = ref({
  current_page: 1,
  per_page: 10,
  total: 0,
  last_page: 1,
  has_more_pages: false,
  from: 0,
  to: 0
})
const viewerOpen = ref(false)
const selectedHeader = ref(null)
const leaves = ref([])
const year = ref(new Date().getFullYear())

// Reference data for filters
const departmentsRef = ref([])
const positionsRef = ref([])

// Filter state
const filters = ref({
  search: '',
  departmentId: null,
  positionId: null
})

// Available filters configuration
const availableFilters = computed(() => [
  {
    key: 'departmentId',
    label: 'Department',
    options: departmentsRef.value.filter(dept => dept.active !== false),
    valueKey: 'id',
    labelKey: 'name'
  },
  {
    key: 'positionId',
    label: 'Position',
    options: positionsRef.value.filter(pos => pos.active !== false),
    valueKey: 'id',
    labelKey: 'name'
  }
])

// Generate HTML content for preview
const reportHtmlContent = computed(() => {
  if (!items.value.length) return ''
  
  // Generate a simple table HTML for preview
  const headers = ['Employee No', 'Name', 'Position', 'Department', 'Leave Type', 'Start Date', 'End Date', 'Days', 'Status']
  const rows = items.value.map(item => [
    item.employee_no || '',
    item.name || '',
    item.position || '',
    item.department || '',
    item.leave_type || '',
    item.start_date || '',
    item.end_date || '',
    item.days || '',
    item.status || ''
  ])
  
  let html = '<table style="width: 100%; border-collapse: collapse; margin: 20px 0;">'
  
  // Header row
  html += '<thead><tr style="background-color: #f5f5f5;">'
  headers.forEach(header => {
    html += `<th style="border: 1px solid #ddd; padding: 8px; text-align: left;">${header}</th>`
  })
  html += '</tr></thead>'
  
  // Data rows
  html += '<tbody>'
  rows.forEach(row => {
    html += '<tr>'
    row.forEach(cell => {
      html += `<td style="border: 1px solid #ddd; padding: 8px;">${cell}</td>`
    })
    html += '</tr>'
  })
  html += '</tbody>'
  
  html += '</table>'
  
  return html
})

// Export functions
function handleExcelExport() {
  ElMessage.info('Excel export functionality will be implemented')
}

function handlePdfExport() {
  ElMessage.info('PDF export functionality will be implemented')
}

function handleWordExport() {
  ElMessage.info('Word export functionality will be implemented')
}

const load = async (page = 1, perPage = 10, q = '', filterParams = {}) => {
  loading.value = true
  try {
    const params = { 
      page, 
      per_page: perPage, 
      search: q,
      ...filterParams
    }
    const res = await leaveTakenMonitoringService.listEmployees(params)
    items.value = res.data || []
    pagination.value = res.pagination || pagination.value
  } catch (err) {
    ElMessage.error(err.message)
  } finally {
    loading.value = false
  }
}

const onSelect = async (employee) => {
  loading.value = true
  try {
    const res = await leaveTakenMonitoringService.loadEmployee(employee.id, year.value)
    selectedHeader.value = res.header
    leaves.value = res.leaves
    viewerOpen.value = true
  } catch (err) {
    ElMessage.error(err.message)
  } finally {
    loading.value = false
  }
}

// Load reference data for filters
const loadReferenceData = async () => {
  try {
    filtersLoading.value = true
    const [depRes, posRes] = await Promise.all([
      rawApi.get('/departments').catch(() => ({ data: [] })),
      rawApi.get('/positions').catch(() => ({ data: [] }))
    ])
    
    departmentsRef.value = Array.isArray(depRes) ? depRes : (depRes?.data || depRes?.departments || [])
    positionsRef.value = Array.isArray(posRes) ? posRes : (posRes?.data || posRes?.positions || [])
  } catch (err) {
    console.error('Failed to load reference data:', err)
    ElMessage.error('Failed to load filter options')
  } finally {
    filtersLoading.value = false
  }
}

onMounted(async () => {
  // Load reference data first
  await loadReferenceData()
  // Then load the main data
  await load(1, pagination.value.per_page, filters.value.search, getFilterParams())
})

const onPageChange = (page) => {
  pagination.value.current_page = page
  load(page, pagination.value.per_page, filters.value.search, getFilterParams())
}

const onPerPageChange = (perPage) => {
  pagination.value.per_page = perPage
  pagination.value.current_page = 1
  load(1, perPage, filters.value.search, getFilterParams())
}

const handleSearch = (searchValue) => {
  filters.value.search = searchValue
  pagination.value.current_page = 1
  load(1, pagination.value.per_page, searchValue, getFilterParams())
}

const handleFiltersChange = (newFilters) => {
  filters.value = {
    ...filters.value,
    ...newFilters
  }
  pagination.value.current_page = 1
  load(1, pagination.value.per_page, filters.value.search, getFilterParams())
}

const getFilterParams = () => {
  const params = {}
  // Convert to number and check if it's a valid ID (not null, undefined, or empty string)
  if (filters.value.departmentId !== null && filters.value.departmentId !== undefined && filters.value.departmentId !== '') {
    params.department_id = Number(filters.value.departmentId)
  }
  if (filters.value.positionId !== null && filters.value.positionId !== undefined && filters.value.positionId !== '') {
    params.position_id = Number(filters.value.positionId)
  }
  return params
}

const onChangeYear = async (y) => {
  year.value = y
  if (viewerOpen.value && selectedHeader.value?.id) {
    await onSelect({ id: selectedHeader.value.id })
  }
}
</script>

<style scoped>
.mb-3 { margin-bottom: 12px; }
.mr-2 { margin-right: 8px; }
.filters { display: flex; align-items: center; }
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

