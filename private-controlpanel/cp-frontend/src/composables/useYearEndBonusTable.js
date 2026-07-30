import { ref, reactive, computed } from 'vue'
import ApiService from '../Services/api'

export function useYearEndBonusTable() {
    const yearEndBonusTables = ref([])
    const loading = ref(false)
    const saving = ref(false)
    const apiError = ref(false)

    // form state
    const formVisible = ref(false)
    const formLoading = ref(false)
    const yearEndBonusData = ref([])

    async function fetchYearEndBonusTables() {
        loading.value = true
        apiError.value = false
        try {
            const res = await ApiService.getYearEndBonusTables()
            console.log('API response:', res)
            const data = res?.data || []
            console.log('Year end bonus data:', data)

            yearEndBonusTables.value = data.map(bonus => ({
                ...bonus,
                months: parseFloat(bonus.months) || 0,
                percentage: parseFloat(bonus.percentage) || 0,
                cash_gift: parseFloat(bonus.cash_gift) || 0
            }))

            console.log('Processed year end bonus tables:', yearEndBonusTables.value)
        } catch (error) {
            console.error('Error fetching year end bonus tables:', error)
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
            yearEndBonusTables.value = [
                { id: 1, months: 1, percentage: 0 },
                { id: 2, months: 2, percentage: 0.10 },
                { id: 3, months: 3, percentage: 0.20 },
                { id: 4, months: 4, percentage: 0.30 },
                { id: 5, months: 5, percentage: 0.40 },
                { id: 6, months: 6, percentage: 0.50 }
            ]

            // Show user-friendly error message
            if (error.response?.status === 500) {
                console.warn('Server error - showing sample year end bonus data. Please check database connection.')
                console.warn('Possible causes: 1) yearend_table table does not exist, 2) database connection issue, 3) missing migrations')
            }
        } finally {
            loading.value = false
        }
    }

    function openForm() {
        formVisible.value = true
        formLoading.value = true

        // Initialize year end bonus data with existing data or empty structure
        if (yearEndBonusTables.value.length > 0) {
            yearEndBonusData.value = yearEndBonusTables.value.map(bonus => ({
                id: bonus.id,
                months: bonus.months,
                percentage: bonus.percentage,
                cash_gift: bonus.cash_gift
            }))
        } else {
            // Create default empty rows for new year end bonus table
            yearEndBonusData.value = Array.from({ length: 6 }, (_, index) => ({
                id: null,
                months: index,
                percentage: '',
                cash_gift: 5000
            }))
        }

        formLoading.value = false
    }

    function addYearEndBonusRow() {
        const maxMonths = yearEndBonusData.value.length > 0
            ? Math.max(...yearEndBonusData.value.map(row => row.months || 0))
            : 0
        yearEndBonusData.value.push({
            id: null,
            months: maxMonths + 1,
            percentage: '',
            cash_gift: 5000
        })
    }

    function removeYearEndBonusRow(index) {
        yearEndBonusData.value.splice(index, 1)
    }

    async function saveYearEndBonusTables() {
        saving.value = true
        try {
            // Filter out empty rows
            const validRows = yearEndBonusData.value.filter(row =>
                row.months !== '' && row.percentage !== ''
            )

            if (validRows.length === 0) {
                throw new Error('Please fill in at least one year end bonus row')
            }

            // Prepare payload in the format expected by backend
            const payload = {
                id: validRows.map(row => row.id || 0),
                months: validRows.map(row => parseFloat(row.months) || 0),
                percentage: validRows.map(row => parseFloat(row.percentage) || 0),
                cash_gift: validRows.map(row => parseFloat(row.cash_gift) || 5000)
            }

            console.log('Saving year end bonus tables with payload:', payload)
            const response = await ApiService.saveYearEndBonusTables(payload)
            console.log('Save response:', response)

            if (response.success) {
                await fetchYearEndBonusTables()
                formVisible.value = false
                console.log('Year end bonus tables saved successfully')
            } else {
                throw new Error(response.message || 'Failed to save year end bonus tables')
            }
        } catch (error) {
            console.error('Error saving year end bonus tables:', error)
            // You can add user notification here if needed
            throw error
        } finally {
            saving.value = false
        }
    }

    async function deleteYearEndBonusTable(id) {
        try {
            await ApiService.deleteYearEndBonusTable(id)
            await fetchYearEndBonusTables()
        } catch (error) {
            console.error('Error deleting year end bonus table:', error)
        }
    }

    const tableColumns = [
        { key: 'months', label: 'No. of Aggregate Months of Service', minWidth: 250 },
        { key: 'percentage', label: 'Percentage of Basic Monthly Salary', minWidth: 280 },
        { key: 'cash_gift', label: 'Cash Gift', minWidth: 160 },
        { key: 'actions', label: 'Actions', width: 100, fixed: 'right' }
    ]

    return {
        yearEndBonusTables,
        loading,
        saving,
        apiError,
        fetchYearEndBonusTables,
        // form
        formVisible,
        formLoading,
        yearEndBonusData,
        openForm,
        addYearEndBonusRow,
        removeYearEndBonusRow,
        saveYearEndBonusTables,
        deleteYearEndBonusTable,
        tableColumns
    }
}
