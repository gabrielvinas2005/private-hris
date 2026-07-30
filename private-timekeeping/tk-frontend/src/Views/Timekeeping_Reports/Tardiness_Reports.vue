  <template>
    <PageScaffold
      :title="getPageTitle()"
      :subtitle="getPageSubtitle()"
      :breadcrumbs="[
        { label: 'Timekeeping Reports', to: '/' }, 
        { label: getPageTitle() }
      ]"
    >
    <SearchFiltersContainer
      :searchValue="searchQuery"
      @update:searchValue="val => { searchQuery = val }"
      :filtersValue="selectedFilters"
      @update:filtersValue="val => Object.assign(selectedFilters, val)"
      :filters="filterConfig"
      :search-placeholder="'Search employee or position...'"
      :filters-loading="loading"
      :search-loading="loading"
      :auto-fetch="true"
      :show-export-section="false"
      @search="handleSearch"
      @filters-change="handleFilterChange"
    />

    <!-- Tardiness Reports Table -->
    <TardinessReportsTable
      :items="tardinessData"
      :loading="loading"
      :pagination="pagination"
      @page-change="handlePageChange"
      @per-page-change="handlePerPageChange"
      @select="handleSelectEmployee"
    />

    <!-- Error Message -->
    <el-alert
      v-if="error"
      :title="error"
      type="error"
      show-icon
      :closable="false"
      class="mt-4"
    />

    <!-- Empty State -->
    <el-empty
      v-if="!loading && !hasData"
      :description="getEmptyStateMessage()"
      class="mt-8"
    >
      <el-button type="primary" @click="handleClearFilters">
        Clear Filters
      </el-button>
    </el-empty>

    <!-- Tardiness Report Viewer -->
    <TardinessReportsViewer
      v-model="showViewer"
      :header="selectedEmployee"
      :rows="selectedEmployeeData"
      :dateFrom="dateFilters.dateFrom"
      :dateTo="dateFilters.dateTo"
      :reportType="currentReportType"
      :loading="viewerLoading"
      @change-date-range="handleDateRangeChange"
    />
  </PageScaffold>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { ElMessage } from 'element-plus'
import PageScaffold from '../../components/Reusable_Components/PageScaffold.vue'
import TardinessReportsTable from '../../components/Timekeeping_Reports/Tardiness_Reports/TardinessReportsTable.vue'
import TardinessReportsViewer from '../../components/Timekeeping_Reports/Tardiness_Reports/TardinessReportsViewer.vue'
import SearchFiltersContainer from '../../components/Reusable_Components/SearchFiltersContainer.vue'
import { useTardinessReports } from '../../Composables/useTardinessReports'
import { formatEmployeeName } from '../../Composables/useNameFormatter'
import { api } from '../../services/api'

// Initialize composable
const {
  loading,
  error,
  tardinessData,
  pagination,
  filters,
  hasData,
  totalRecords,
  fetchTardinessReports,
  getTardinessReportDetails,
  exportTardinessReports,
  updateFilters,
  updatePagination,
  clearFilters
} = useTardinessReports(api)

// Local state
const showViewer = ref(false)
const selectedEmployee = ref(null)
const selectedEmployeeData = ref([])
const viewerLoading = ref(false)
const searchQuery = ref('')
const selectedFilters = ref({ reportType: 'combined' })
const dateFilters = ref({
  dateFrom: '',
  dateTo: ''
})

// Filter configuration
const filterConfig = [
  {
    key: 'reportType',
    label: 'Report Type',
    valueKey: 'value',
    labelKey: 'label',
    options: [
      { value: 'combined', label: 'Late, Undertime and Absences Report' },
      { value: 'late', label: 'Late Report' },
      { value: 'undertime', label: 'Undertime Report' },
      { value: 'absences', label: 'Absences Report' }
    ]
  },
  {
    key: 'department_id',
    label: 'Department',
    valueKey: 'id',
    labelKey: 'name'
  },
  {
    key: 'position_id', 
    label: 'Position',
    valueKey: 'id',
    labelKey: 'name'
  }
]

const summaryData = ref({
  late_employees: 0,
  average_tardiness: 0,
  on_time_rate: 0
})

// Computed properties
const severeTardinessCount = computed(() => {
  return tardinessData.value.filter(item => item.late > 1).length // More than 1 hour late
})

const currentReportType = computed(() => {
  const reportType = selectedFilters.value.reportType || filters.value.reportType
  if (reportType === 'combined') return 'Combined'
  if (reportType === 'undertime') return 'Undertime'
  if (reportType === 'absences') return 'Absences'
  return 'Tardiness'
})

const getPageTitle = () => {
  switch (currentReportType.value) {
    case 'Combined': return 'Late, Undertime and Absences Reports'
    case 'Undertime': return 'Undertime Reports'
    case 'Absences': return 'Absences Reports'
    default: return 'Tardiness Reports'
  }
}

const getPageSubtitle = () => {
  switch (currentReportType.value) {
    case 'Combined': return 'View late, undertime, and absences in one consolidated report'
    case 'Undertime': return 'Generate and analyze undertime reports and work hour patterns'
    case 'Absences': return 'Generate and analyze absence reports and attendance patterns'
    default: return 'Generate and analyze tardiness reports and attendance patterns'
  }
}

const getEmptyStateMessage = () => {
  switch (currentReportType.value) {
    case 'Combined': return 'No late/undertime/absence data available for the selected period'
    case 'Undertime': return 'No undertime data available for the selected period'
    case 'Absences': return 'No absence data available for the selected period'
    default: return 'No tardiness data available for the selected period'
  }
}

const getReportTypeName = () => {
  switch (currentReportType.value) {
    case 'Combined': return 'Late/Undertime/Absences'
    case 'Undertime': return 'Undertime'
    case 'Absences': return 'Absences'
    default: return 'Tardiness'
  }
}

const formatReportEmployeeName = (item) => {
  const formattedName = formatEmployeeName(item, item?.name ?? item?.employee_name ?? item?.full_name ?? '')
  return formattedName || ''
}

// Methods
const handleSearch = (query) => {
  searchQuery.value = query
  // Update filters with search query
  const newFilters = {
    ...filters.value,
    search: query
  }
  updateFilters(newFilters)
  fetchTardinessReports()
}

const handleReload = () => {
  fetchTardinessReports()
  fetchSummaryData()
}

const handleFilterChange = async (newFilters) => {
  const combinedFilters = {
    ...filters.value,
    ...selectedFilters.value,
    search: searchQuery.value
  }
  updateFilters(combinedFilters)
  await fetchTardinessReports()
  await fetchSummaryData()
}

const handleClearFilters = async () => {
  searchQuery.value = ''
  selectedFilters.value = { reportType: 'combined' }
  clearFilters()
  // enforce default combined in store filters too
  updateFilters({ reportType: 'combined' })
  await fetchTardinessReports()
  await fetchSummaryData()
}

const handlePageChange = async (page) => {
  updatePagination(page)
  await fetchTardinessReports()
}

const handlePerPageChange = async (perPage) => {
  updatePagination({ page: 1, per_page: perPage })
  await fetchTardinessReports()
}

const handleSelectEmployee = async (employee) => {
  selectedEmployee.value = employee
  // Use main report date range in viewer if viewer dates not set
  if (!dateFilters.value.dateFrom && filters.value.date_from) dateFilters.value.dateFrom = filters.value.date_from
  if (!dateFilters.value.dateTo && filters.value.date_to) dateFilters.value.dateTo = filters.value.date_to
  await loadEmployeeTardinessData(employee.id)
  showViewer.value = true
}

// Watch for report type changes and reload data if viewer is open
watch(() => currentReportType.value, async (newReportType, oldReportType) => {
  if (selectedEmployee.value && showViewer.value && newReportType !== oldReportType) {
    console.log('Report type changed, reloading employee data:', newReportType)
    await loadEmployeeTardinessData(selectedEmployee.value.id)
  }
})

const handleDateRangeChange = async (dateRange) => {
  dateFilters.value.dateFrom = dateRange.dateFrom
  dateFilters.value.dateTo = dateRange.dateTo
  // Reload employee data with new date range so backend returns time_data + time_data_adj for selected dates
  if (selectedEmployee.value) {
    await loadEmployeeTardinessData(selectedEmployee.value.id)
  }
}


const loadEmployeeTardinessData = async (employeeId) => {
  viewerLoading.value = true
  try {
    const reportTypeMapping = {
      'Tardiness': 'late',
      'Undertime': 'undertime',
      'Absences': 'absences',
      'Combined': 'combined'
    }
    const backendReportType = reportTypeMapping[currentReportType.value] || 'late'

    // Use date range from viewer first, then from main filters (so backend returns time_data + time_data_adj for selected dates)
    const dateFrom = dateFilters.value.dateFrom || filters.value.date_from || ''
    const dateTo = dateFilters.value.dateTo || filters.value.date_to || ''

    const params = { reportType: backendReportType }
    if (dateFrom) params.date_from = dateFrom
    if (dateTo) params.date_to = dateTo

    const response = await api.get(`/time-data/employee-tardiness/${employeeId}`, { params })

    selectedEmployeeData.value = Array.isArray(response) ? response : []
  } catch (err) {
    console.error('Error loading employee tardiness data:', err)
    ElMessage.error('Failed to load employee tardiness data')
    selectedEmployeeData.value = []
  } finally {
    viewerLoading.value = false
  }
}

// Generate HTML content for preview based on report type
const reportHtmlContent = computed(() => {
  if (!tardinessData.value.length) return ''
  
  let headers = []
  let rows = []
  
  // Generate headers and rows based on report type
  switch (currentReportType.value) {
    case 'Tardiness':
      headers = ['Employee No', 'Name', 'Position', 'Department', 'Date', 'Time In', 'Time Out', 'Late Minutes']
      rows = tardinessData.value.map(item => [
        item.employee_no || '',
        formatReportEmployeeName(item),
        item.position || '',
        item.department || item.department_name || '',
        item.date || '',
        item.time_in || item.am_in || '',
        item.time_out || item.pm_out || '',
        formatLateTimeForExport(item.late || 0)
      ])
      break
      
    case 'Undertime':
      headers = ['Employee No', 'Name', 'Position', 'Department', 'Date', 'Time In', 'Time Out', 'Undertime Minutes']
      rows = tardinessData.value.map(item => [
        item.employee_no || '',
        formatReportEmployeeName(item),
        item.position || '',
        item.department || item.department_name || '',
        item.date || '',
        item.time_in || item.am_in || '',
        item.time_out || item.pm_out || '',
        formatLateTimeForExport(item.undertime || 0)
      ])
      break
      
    case 'Absences':
      headers = ['Employee No', 'Name', 'Position', 'Department', 'Date', 'Absent Status']
      rows = tardinessData.value.map(item => [
        item.employee_no || '',
        formatReportEmployeeName(item),
        item.position || '',
        item.department || item.department_name || '',
        item.date || '',
        (item.absent && (item.absent > 0 || item.absent === 1 || item.absent === '1')) ? 'Absent' : 'Present'
      ])
      break
      
    case 'Combined':
      headers = ['Employee No', 'Name', 'Position', 'Department', 'Date', 'Time In', 'Time Out', 'Late Minutes', 'Undertime Minutes', 'Absent Status']
      rows = tardinessData.value.map(item => [
        item.employee_no || '',
        formatReportEmployeeName(item),
        item.position || '',
        item.department || item.department_name || '',
        item.date || '',
        item.time_in || item.am_in || '',
        item.time_out || item.pm_out || '',
        formatLateTimeForExport(item.late || 0),
        formatLateTimeForExport(item.undertime || 0),
        (item.absent && (item.absent > 0 || item.absent === 1 || item.absent === '1')) ? 'Absent' : 'Present'
      ])
      break
      
    default:
      headers = ['Employee No', 'Name', 'Position', 'Department', 'Date', 'Time In', 'Time Out', 'Late Minutes']
      rows = tardinessData.value.map(item => [
        item.employee_no || '',
        formatReportEmployeeName(item),
        item.position || '',
        item.department || item.department_name || '',
        item.date || '',
        item.time_in || item.am_in || '',
        item.time_out || item.pm_out || '',
        formatLateTimeForExport(item.late || 0)
      ])
  }
  
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

// Helper function to format late/undertime for export
const formatLateTimeForExport = (hours) => {
  if (!hours || hours <= 0) return '0'
  const h = Math.floor(hours)
  const m = Math.round((hours - h) * 60)
  if (h > 0 && m > 0) {
    return `${h} hrs ${m} mins`
  } else if (h > 0) {
    return `${h} hrs`
  } else {
    return `${m} mins`
  }
}

const handleExportExcel = async () => {
  try {
    await exportTardinessReports('excel', filters.value)
    const reportTypeName = getReportTypeName()
    ElMessage.success(`${reportTypeName} report exported as Excel`)
  } catch (err) {
    ElMessage.error(`Failed to export report: ${err.message}`)
  }
}

const handleExportPdf = async () => {
  try {
    await exportTardinessReports('pdf', filters.value)
    const reportTypeName = getReportTypeName()
    ElMessage.success(`${reportTypeName} report exported as PDF`)
  } catch (err) {
    ElMessage.error(`Failed to export report: ${err.message}`)
  }
}

const handleExportWord = async () => {
  try {
    await exportTardinessReports('word', filters.value)
    const reportTypeName = getReportTypeName()
    ElMessage.success(`${reportTypeName} report exported as Word`)
  } catch (err) {
    ElMessage.error(`Failed to export report: ${err.message}`)
  }
}

const fetchSummaryData = async () => {
  try {
    const response = await api.get('/time-data/tardiness-summary', {
      params: filters.value
    })
    summaryData.value = response.data || {}
  } catch (err) {
    console.error('Error fetching summary data:', err)
  }
}

// Initialize data
onMounted(async () => {
  await fetchTardinessReports()
  await fetchSummaryData()
})
</script>

<style scoped>
.search-filters-export-row {
          display: flex;
          align-items: center;
          gap: 16px;
          margin-bottom: 16px;
          flex-wrap: wrap;
        }

        .search-filters-export-row :deep(.employee-searchbar) {
          flex: 0 0 auto;
          margin-bottom: 0;
        }

        .search-filters-export-row :deep(.filters-container) {
          flex: 1;
          margin-bottom: 0;
        }

        .search-filters-export-row :deep(.filters-row) {
          gap: 12px;
        }

        .search-filters-export-row :deep(.filter-dropdowns) {
          gap: 12px;
        }

        /* Responsive adjustments */
        @media (max-width: 1200px) {
          .search-filters-export-row {
            flex-direction: column;
            align-items: stretch;
            gap: 12px;
          }

          .search-filters-export-row :deep(.filters-container) {
            flex: none;
          }
        }

        @media (max-width: 768px) {
          .search-filters-export-row {
            gap: 8px;
          }
        }

</style>

