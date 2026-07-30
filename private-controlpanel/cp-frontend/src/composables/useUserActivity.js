import { ref, reactive, computed } from 'vue'
import ApiService from '../Services/api'

export function useUserActivity() {
    const auditRecords = ref([])
    const loading = ref(false)
    const apiError = ref(false)

    // Pagination and search state
    const currentPage = ref(1)
    const pageSize = ref(10)
    const totalRecords = ref(0)
    const searchQuery = ref('')
    const startDate = ref('')
    const endDate = ref('')

    // Data table state for server-side processing
    const tableData = ref([])
    const tableLoading = ref(false)
    const tableTotal = ref(0)
    const tableFiltered = ref(0)

    async function fetchAuditRecords(params = {}) {
        loading.value = true
        apiError.value = false
        try {
            const res = await ApiService.getAuditRecords(params)
            if (!res || res.success !== true) {
                apiError.value = true
                auditRecords.value = []
                console.warn('Failed to fetch audit records:', res?.message || res)
                return
            }

            const data = res?.data || []

            auditRecords.value = data.map(record => ({
                ...record,
                created_at: new Date(record.created_at).toLocaleString(),
                updated_at: record.updated_at ? new Date(record.updated_at).toLocaleString() : null
            }))

        } catch (error) {
            console.error('Error fetching audit records:', error)
            console.error('Error details:', {
                status: error.response?.status,
                statusText: error.response?.statusText,
                data: error.response?.data,
                message: error.message
            })

            // Log the full response for debugging
            if (error.response?.data) {
                console.error('Server response:', JSON.stringify(error.response.data, null, 2))
            }
            apiError.value = true

            // Show sample data for demonstration when API fails
            auditRecords.value = [
                {
                    id: 1,
                    photo: null,
                    name: 'John Doe',
                    module: 'Control Panel',
                    menu: 'User Management',
                    activity: 'Create',
                    description: 'Created new user account',
                    created_at: new Date().toLocaleString(),
                    updated_at: null
                },
                {
                    id: 2,
                    photo: null,
                    name: 'Jane Smith',
                    module: 'Payroll Setup',
                    menu: 'Cash Gift Table Setup',
                    activity: 'Update',
                    description: 'Updated cash gift table information',
                    created_at: new Date(Date.now() - 3600000).toLocaleString(),
                    updated_at: null
                }
            ]

            // Show user-friendly error message
            if (error.response?.status === 500) {
                console.warn('Server error - showing sample audit data. Please check database connection.')
                console.warn('Possible causes: 1) audits table does not exist, 2) database connection issue, 3) missing migrations')
            }
        } finally {
            loading.value = false
        }
    }

    async function fetchAuditRecordsLazy(params = {}) {
        tableLoading.value = true
        apiError.value = false
        try {
            // Prepare DataTables server-side processing parameters
            const requestParams = {
                start: params.start || 0,
                length: params.length || 10,
                search: {
                    value: params.search || searchQuery.value
                },
                startDate: startDate.value,
                endDate: endDate.value
            }
            const userId = params.userId ?? null
            const res = userId
                ? await ApiService.getUserActivities(userId, requestParams)
                : await ApiService.getAuditRecordsOptimized(requestParams)

            if (!res || res.success !== true) {
                apiError.value = true
                tableData.value = []
                tableTotal.value = 0
                tableFiltered.value = 0
                console.warn('Failed to fetch audit records (lazy):', res?.message || res)
                return
            }

            const responseData = res?.data || {}
            const data = responseData.data || []
            const recordsTotal = responseData.recordsTotal || 0
            const recordsFiltered = responseData.recordsFiltered || 0

            tableData.value = data.map(record => ({
                ...record,
                created_at: new Date(record.created_at).toLocaleString(),
                updated_at: record.updated_at ? new Date(record.updated_at).toLocaleString() : null
            }))

            tableTotal.value = recordsTotal
            tableFiltered.value = recordsFiltered

        } catch (error) {
            console.error('Error fetching audit records:', error)
            console.error('Error details:', {
                status: error.response?.status,
                statusText: error.response?.statusText,
                data: error.response?.data,
                message: error.message
            })

            apiError.value = true

            // Show sample data for demonstration when API fails
            tableData.value = [
                {
                    id: 1,
                    photo: null,
                    name: 'John Doe',
                    module: 'Control Panel',
                    menu: 'User Management',
                    activity: 'Create',
                    description: 'Created new user account',
                    created_at: new Date().toLocaleString(),
                    updated_at: null
                },
                {
                    id: 2,
                    photo: null,
                    name: 'Jane Smith',
                    module: 'Payroll Setup',
                    menu: 'Cash Gift Table Setup',
                    activity: 'Update',
                    description: 'Updated cash gift table information',
                    created_at: new Date(Date.now() - 3600000).toLocaleString(),
                    updated_at: null
                }
            ]

            tableTotal.value = 2
            tableFiltered.value = 2

            if (error.response?.status === 500) {
                console.warn('Server error - showing sample audit data. Please check database connection.')
            }
        } finally {
            tableLoading.value = false
        }
    }

    function setDateRange(start, end) {
        startDate.value = start
        endDate.value = end
    }

    function setSearchQuery(query) {
        searchQuery.value = query
    }

    function resetFilters() {
        searchQuery.value = ''
        startDate.value = ''
        endDate.value = ''
        currentPage.value = 1
    }

    const tableColumns = [
        { key: 'photo', label: 'Photo', width: 80, sortable: false },
        { key: 'name', label: 'User', minWidth: 150, sortable: true },
        { key: 'module', label: 'Module', minWidth: 120, sortable: true },
        { key: 'menu', label: 'Menu', minWidth: 150, sortable: true },
        { key: 'activity', label: 'Activity', minWidth: 100, sortable: true },
        { key: 'description', label: 'Description', minWidth: 200, sortable: true },
        { key: 'created_at', label: 'Date & Time', minWidth: 150, sortable: true }
    ]

    return {
        auditRecords,
        loading,
        apiError,
        fetchAuditRecords,
        // Lazy loading for data table
        tableData,
        tableLoading,
        tableTotal,
        tableFiltered,
        fetchAuditRecordsLazy,
        // Filters and pagination
        currentPage,
        pageSize,
        totalRecords,
        searchQuery,
        startDate,
        endDate,
        setDateRange,
        setSearchQuery,
        resetFilters,
        tableColumns
    }
}
