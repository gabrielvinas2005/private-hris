import { ref, reactive, computed } from 'vue'
import ApiService from '../Services/api'

export function useMidYearBonusTable() {
    const midYearBonusTables = ref([])
    const loading = ref(false)
    const saving = ref(false)
    const apiError = ref(false)

    // form state
    const formVisible = ref(false)
    const formLoading = ref(false)
    const midYearBonusData = ref([])

    async function fetchMidYearBonusTables() {
        loading.value = true
        apiError.value = false
        try {
            const res = await ApiService.getMidYearBonusTables()
            console.log('API response:', res)
            const data = res?.data || []
            console.log('Mid year bonus data:', data)

            midYearBonusTables.value = data.map(bonus => ({
                ...bonus,
                months: parseFloat(bonus.months) || 0,
                percentage: parseFloat(bonus.percentage) || 0
            }))

            console.log('Processed mid year bonus tables:', midYearBonusTables.value)
        } catch (error) {
            console.error('Error fetching mid year bonus tables:', error)
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
            midYearBonusTables.value = [
                { id: 1, months: 1, percentage: 0 },
                { id: 2, months: 2, percentage: 0.10 },
                { id: 3, months: 3, percentage: 0.20 },
                { id: 4, months: 4, percentage: 0.30 },
                { id: 5, months: 5, percentage: 0.40 },
                { id: 6, months: 6, percentage: 0.50 }
            ]

            // Show user-friendly error message
            if (error.response?.status === 500) {
                console.warn('Server error - showing sample mid year bonus data. Please check database connection.')
                console.warn('Possible causes: 1) midyear_table table does not exist, 2) database connection issue, 3) missing migrations')
            }
        } finally {
            loading.value = false
        }
    }

    function openForm() {
        formVisible.value = true
        formLoading.value = true

        // Initialize mid year bonus data with existing data or empty structure
        if (midYearBonusTables.value.length > 0) {
            midYearBonusData.value = midYearBonusTables.value.map(bonus => ({
                id: bonus.id,
                months: bonus.months,
                percentage: bonus.percentage
            }))
        } else {
            // Create default empty rows for new mid year bonus table
            midYearBonusData.value = Array.from({ length: 6 }, (_, index) => ({
                id: null,
                months: index + 1,
                percentage: ''
            }))
        }

        formLoading.value = false
    }

    function addMidYearBonusRow() {
        const maxMonths = midYearBonusData.value.length > 0
            ? Math.max(...midYearBonusData.value.map(row => row.months || 0))
            : 0
        midYearBonusData.value.push({
            id: null,
            months: maxMonths + 1,
            percentage: ''
        })
    }

    function removeMidYearBonusRow(index) {
        midYearBonusData.value.splice(index, 1)
    }

    async function saveMidYearBonusTables() {
        saving.value = true
        try {
            // Filter out empty rows
            const validRows = midYearBonusData.value.filter(row =>
                row.months !== '' && row.percentage !== ''
            )

            if (validRows.length === 0) {
                throw new Error('Please fill in at least one mid year bonus row')
            }

            // Prepare payload in the format expected by backend
            const payload = {
                id: validRows.map(row => row.id || 0),
                months: validRows.map(row => parseFloat(row.months) || 0),
                percentage: validRows.map(row => parseFloat(row.percentage) || 0)
            }

            console.log('Saving mid year bonus tables with payload:', payload)
            const response = await ApiService.saveMidYearBonusTables(payload)
            console.log('Save response:', response)

            if (response.success) {
                await fetchMidYearBonusTables()
                formVisible.value = false
                console.log('Mid year bonus tables saved successfully')
            } else {
                throw new Error(response.message || 'Failed to save mid year bonus tables')
            }
        } catch (error) {
            console.error('Error saving mid year bonus tables:', error)
            // You can add user notification here if needed
            throw error
        } finally {
            saving.value = false
        }
    }

    async function deleteMidYearBonusTable(id) {
        try {
            await ApiService.deleteMidYearBonusTable(id)
            await fetchMidYearBonusTables()
        } catch (error) {
            console.error('Error deleting mid year bonus table:', error)
        }
    }

    const tableColumns = [
        { key: 'months', label: 'No. of Aggregate Months of Service', minWidth: 250 },
        { key: 'percentage', label: 'Percentage of Basic Monthly Salary', minWidth: 280 },
        { key: 'actions', label: 'Actions', width: 100, fixed: 'right' }
    ]

    return {
        midYearBonusTables,
        loading,
        saving,
        apiError,
        fetchMidYearBonusTables,
        // form
        formVisible,
        formLoading,
        midYearBonusData,
        openForm,
        addMidYearBonusRow,
        removeMidYearBonusRow,
        saveMidYearBonusTables,
        deleteMidYearBonusTable,
        tableColumns
    }
}
