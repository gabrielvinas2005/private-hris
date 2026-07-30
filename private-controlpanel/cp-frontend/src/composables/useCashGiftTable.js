import { ref, reactive, computed } from 'vue'
import ApiService from '../Services/api'

export function useCashGiftTable() {
    const cashGiftTables = ref([])
    const loading = ref(false)
    const saving = ref(false)
    const apiError = ref(false)

    // form state
    const formVisible = ref(false)
    const formLoading = ref(false)
    const cashGiftData = ref([])

    async function fetchCashGiftTables() {
        loading.value = true
        apiError.value = false
        try {
            const res = await ApiService.getCashGiftTables()
            console.log('API response:', res)
            const data = res?.data || []
            console.log('Cash gift data:', data)

            cashGiftTables.value = data.map(gift => ({
                ...gift,
                months: parseFloat(gift.months) || 0,
                percentage: parseFloat(gift.percentage) || 0
            }))

            console.log('Processed cash gift tables:', cashGiftTables.value)
        } catch (error) {
            console.error('Error fetching cash gift tables:', error)
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
            cashGiftTables.value = [
                { id: 1, months: 1, percentage: 0 },
                { id: 2, months: 2, percentage: 0.10 },
                { id: 3, months: 3, percentage: 0.20 },
                { id: 4, months: 4, percentage: 0.30 },
                { id: 5, months: 5, percentage: 0.40 },
                { id: 6, months: 6, percentage: 0.50 }
            ]

            // Show user-friendly error message
            if (error.response?.status === 500) {
                console.warn('Server error - showing sample cash gift data. Please check database connection.')
                console.warn('Possible causes: 1) cashgift_table table does not exist, 2) database connection issue, 3) missing migrations')
            }
        } finally {
            loading.value = false
        }
    }

    function openForm() {
        formVisible.value = true
        formLoading.value = true

        // Initialize cash gift data with existing data or empty structure
        if (cashGiftTables.value.length > 0) {
            cashGiftData.value = cashGiftTables.value.map(gift => ({
                id: gift.id,
                months: gift.months,
                percentage: gift.percentage
            }))
        } else {
            // Create default empty rows for new cash gift table
            cashGiftData.value = Array.from({ length: 6 }, (_, index) => ({
                id: null,
                months: index + 1,
                percentage: ''
            }))
        }

        formLoading.value = false
    }

    function addCashGiftRow() {
        const maxMonths = cashGiftData.value.length > 0
            ? Math.max(...cashGiftData.value.map(row => row.months || 0))
            : 0
        cashGiftData.value.push({
            id: null,
            months: maxMonths + 1,
            percentage: ''
        })
    }

    function removeCashGiftRow(index) {
        cashGiftData.value.splice(index, 1)
    }

    async function saveCashGiftTables() {
        saving.value = true
        try {
            // Filter out empty rows
            const validRows = cashGiftData.value.filter(row =>
                row.months !== '' && row.percentage !== ''
            )

            if (validRows.length === 0) {
                throw new Error('Please fill in at least one cash gift row')
            }

            // Prepare payload in the format expected by backend
            const payload = {
                id: validRows.map(row => row.id || 0),
                months: validRows.map(row => parseFloat(row.months) || 0),
                percentage: validRows.map(row => parseFloat(row.percentage) || 0)
            }

            console.log('Saving cash gift tables with payload:', payload)
            const response = await ApiService.saveCashGiftTables(payload)
            console.log('Save response:', response)

            if (response.success) {
                await fetchCashGiftTables()
                formVisible.value = false
                console.log('Cash gift tables saved successfully')
            } else {
                throw new Error(response.message || 'Failed to save cash gift tables')
            }
        } catch (error) {
            console.error('Error saving cash gift tables:', error)
            // You can add user notification here if needed
            throw error
        } finally {
            saving.value = false
        }
    }

    async function deleteCashGiftTable(id) {
        try {
            await ApiService.deleteCashGiftTable(id)
            await fetchCashGiftTables()
        } catch (error) {
            console.error('Error deleting cash gift table:', error)
        }
    }

    const tableColumns = [
        { key: 'months', label: 'No. of Aggregate Months of Service', minWidth: 250 },
        { key: 'percentage', label: 'Percentage of Basic Monthly Salary', minWidth: 280 },
        { key: 'actions', label: 'Actions', width: 100, fixed: 'right' }
    ]

    return {
        cashGiftTables,
        loading,
        saving,
        apiError,
        fetchCashGiftTables,
        // form
        formVisible,
        formLoading,
        cashGiftData,
        openForm,
        addCashGiftRow,
        removeCashGiftRow,
        saveCashGiftTables,
        deleteCashGiftTable,
        tableColumns
    }
}
