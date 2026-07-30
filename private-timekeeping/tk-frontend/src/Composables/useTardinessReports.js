import { ref, computed } from 'vue'

export function useTardinessReports(api) {
  const loading = ref(false)
  const error = ref(null)
  const tardinessData = ref([])
  const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0
  })
  const filters = ref({
    date_from: '',
    date_to: '',
    employee_id: '',
    department_id: '',
    position_id: '',
    branch_id: '',
    search: '',
    year: new Date().getFullYear()
  })

  // Fetch tardiness reports data from time_data table
  const fetchTardinessReports = async (params = {}) => {
    loading.value = true
    error.value = null
    
    try {
      // Clean up null/empty values before building query params
      const cleanParams = {}
      const allParams = { ...filters.value, ...params }
      
      for (const [key, value] of Object.entries(allParams)) {
        // Only include non-null, non-empty values (except reportType which should always be included if present)
        if (value !== null && value !== undefined && value !== '' && value !== 'null') {
          cleanParams[key] = value
        }
      }
      
      // Always include pagination params
      cleanParams.page = pagination.value.current_page
      cleanParams.per_page = pagination.value.per_page
      
      const queryParams = new URLSearchParams(cleanParams).toString()

      const response = await api.get(`/time-data/tardiness-reports?${queryParams}`)
      
      // Handle the response structure: { data: [...], current_page: 1, ... }
      tardinessData.value = response.data?.data || response.data || []
      pagination.value = {
        current_page: response.data?.current_page || response.current_page || 1,
        last_page: response.data?.last_page || response.last_page || 1,
        per_page: response.data?.per_page || response.per_page || 10,
        total: response.data?.total || response.total || 0
      }
    } catch (err) {
      error.value = err.message || 'Failed to fetch tardiness reports'
      console.error('Error fetching tardiness reports:', err)
    } finally {
      loading.value = false
    }
  }

  // Get tardiness report details from time_data
  const getTardinessReportDetails = async (id) => {
    loading.value = true
    error.value = null
    
    try {
      const response = await api.get(`/time-data/${id}`)
      return response
    } catch (err) {
      error.value = err.message || 'Failed to fetch tardiness report details'
      console.error('Error fetching tardiness report details:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  // Export tardiness reports
  const exportTardinessReports = async (format = 'pdf', params = {}) => {
    loading.value = true
    error.value = null
    
    try {
      // Clean up null/empty values before building query params
      const cleanParams = {}
      const allParams = { ...filters.value, ...params }
      
      for (const [key, value] of Object.entries(allParams)) {
        // Only include non-null, non-empty values
        if (value !== null && value !== undefined && value !== '' && value !== 'null') {
          cleanParams[key] = value
        }
      }
      
      cleanParams.format = format
      const queryParams = new URLSearchParams(cleanParams).toString()

      const response = await api.get(`/time-data/tardiness-export?${queryParams}`, {
        responseType: 'blob'
      })
      
      // Create download link
      const blob = new Blob([response], { 
        type: format === 'pdf' ? 'application/pdf' : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
      })
      const url = window.URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = url
      link.download = `tardiness-reports-${new Date().toISOString().split('T')[0]}.${format}`
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
      window.URL.revokeObjectURL(url)
    } catch (err) {
      error.value = err.message || 'Failed to export tardiness reports'
      console.error('Error exporting tardiness reports:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  // Update filters
  const updateFilters = (newFilters) => {
    filters.value = { ...filters.value, ...newFilters }
    pagination.value.current_page = 1 // Reset to first page
  }

  // Update pagination: number = new page only; object = { page?, current_page?, per_page? }
  const updatePagination = (update) => {
    if (typeof update === 'number' && !Number.isNaN(update)) {
      pagination.value = { ...pagination.value, current_page: update }
      return
    }
    if (update && typeof update === 'object' && !Array.isArray(update)) {
      const next = { ...pagination.value }
      if (update.page != null) next.current_page = Number(update.page) || 1
      if (update.current_page != null) next.current_page = Number(update.current_page) || 1
      if (update.per_page != null) next.per_page = Number(update.per_page) || 10
      pagination.value = next
    }
  }

  // Clear filters
  const clearFilters = () => {
    filters.value = {
      date_from: '',
      date_to: '',
      employee_id: '',
      department_id: '',
      position_id: '',
      branch_id: '',
      search: ''
    }
    pagination.value.current_page = 1
  }

  // Computed properties
  const hasData = computed(() => tardinessData.value.length > 0)
  const totalPages = computed(() => pagination.value.last_page)
  const currentPage = computed(() => pagination.value.current_page)
  const totalRecords = computed(() => pagination.value.total)

  return {
    // State
    loading,
    error,
    tardinessData,
    pagination,
    filters,
    
    // Computed
    hasData,
    totalPages,
    currentPage,
    totalRecords,
    
    // Methods
    fetchTardinessReports,
    getTardinessReportDetails,
    exportTardinessReports,
    updateFilters,
    updatePagination,
    clearFilters
  }
}
