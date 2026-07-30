import { ref, reactive, computed } from 'vue'
import ApiService from '../Services/api'

export function useOvertimeTaxTable() {
    const overtimeTaxTables = ref([])
    const loading = ref(false)
    const saving = ref(false)
    const apiError = ref(false)
    const currentYear = ref(new Date().getFullYear())

    // form state
    const formVisible = ref(false)
    const formLoading = ref(false)
    const overtimeTaxData = ref([])

    async function fetchOvertimeTaxTables(year = null) {
        loading.value = true
        apiError.value = false
        try {
            const targetYear = year || currentYear.value
            const res = await ApiService.getOvertimeTaxTables(targetYear)
            console.log('API response:', res)
            const data = res?.data || []
            console.log('Overtime tax data:', data)

            overtimeTaxTables.value = data.map(tax => ({
                ...tax,
                amount_from: parseFloat(tax.amount_from) || 0,
                amount_to: parseFloat(tax.amount_to) || 0,
                percentage: parseFloat(tax.percentage) || 0
            }))

            console.log('Processed overtime tax tables:', overtimeTaxTables.value)
        } catch (error) {
            console.error('Error fetching overtime tax tables:', error)
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
            overtimeTaxTables.value = [
                { id: 1, amount_from: 0, amount_to: 1000, percentage: 0, fiscal_year: currentYear.value },
                { id: 2, amount_from: 1001, amount_to: 2000, percentage: 5, fiscal_year: currentYear.value },
                { id: 3, amount_from: 2001, amount_to: 3000, percentage: 10, fiscal_year: currentYear.value },
                { id: 4, amount_from: 3001, amount_to: 5000, percentage: 15, fiscal_year: currentYear.value },
                { id: 5, amount_from: 5001, amount_to: 10000, percentage: 20, fiscal_year: currentYear.value }
            ]

            // Show user-friendly error message
            if (error.response?.status === 500) {
                console.warn('Server error - showing sample overtime tax data. Please check database connection.')
                console.warn('Possible causes: 1) overtime_taxes table does not exist, 2) database connection issue, 3) missing migrations')
            }
        } finally {
            loading.value = false
        }
    }

    function openForm() {
        formVisible.value = true
        formLoading.value = true

        // Initialize overtime tax data with existing data or empty structure
        if (overtimeTaxTables.value.length > 0) {
            overtimeTaxData.value = overtimeTaxTables.value.map(tax => ({
                id: tax.id,
                amount_from: tax.amount_from,
                amount_to: tax.amount_to,
                percentage: tax.percentage
            }))
        } else {
            // Create default empty rows for new overtime tax table
            overtimeTaxData.value = Array.from({ length: 5 }, (_, index) => ({
                id: null,
                amount_from: '',
                amount_to: '',
                percentage: ''
            }))
        }

        formLoading.value = false
    }

    function addOvertimeTaxRow() {
        overtimeTaxData.value.push({
            id: null,
            amount_from: '',
            amount_to: '',
            percentage: ''
        })
    }

    function removeOvertimeTaxRow(index) {
        overtimeTaxData.value.splice(index, 1)
    }

    async function saveOvertimeTaxTables() {
        saving.value = true
        try {
            // Filter out empty rows
            const validRows = overtimeTaxData.value.filter(row =>
                row.amount_from !== '' && row.amount_to !== '' && row.percentage !== ''
            )

            if (validRows.length === 0) {
                throw new Error('Please fill in at least one overtime tax row')
            }

            // Prepare payload in the format expected by backend
            const payload = {
                id: validRows.map(row => row.id || 0),
                amount_from: validRows.map(row => parseFloat(row.amount_from) || 0),
                amount_to: validRows.map(row => parseFloat(row.amount_to) || 0),
                percentage: validRows.map(row => parseFloat(row.percentage) || 0),
                overtime_fiscal_year: currentYear.value
            }

            console.log('Saving overtime tax tables with payload:', payload)
            const response = await ApiService.saveOvertimeTaxTables(payload)
            console.log('Save response:', response)

            if (response.success) {
                await fetchOvertimeTaxTables()
                formVisible.value = false
                console.log('Overtime tax tables saved successfully')
            } else {
                throw new Error(response.message || 'Failed to save overtime tax tables')
            }
        } catch (error) {
            console.error('Error saving overtime tax tables:', error)
            // You can add user notification here if needed
            throw error
        } finally {
            saving.value = false
        }
    }

    async function deleteOvertimeTaxTable(id) {
        try {
            await ApiService.deleteOvertimeTaxTable(id)
            await fetchOvertimeTaxTables()
        } catch (error) {
            console.error('Error deleting overtime tax table:', error)
        }
    }

    async function loadOvertimeTaxForYear(year) {
        try {
            const res = await ApiService.loadOvertimeTaxForYear(year)
            const data = res?.data || []

            overtimeTaxTables.value = data.map(tax => ({
                ...tax,
                amount_from: parseFloat(tax.amount_from) || 0,
                amount_to: parseFloat(tax.amount_to) || 0,
                percentage: parseFloat(tax.percentage) || 0
            }))

            currentYear.value = year
        } catch (error) {
            console.error('Error loading overtime tax for year:', error)
            throw error
        }
    }

    const tableColumns = [
        { key: 'amount_from', label: 'Amount From', minWidth: 150 },
        { key: 'amount_to', label: 'Amount To', minWidth: 150 },
        { key: 'percentage', label: 'Percentage', minWidth: 150 },
        { key: 'actions', label: 'Actions', width: 100, fixed: 'right' }
    ]

    return {
        overtimeTaxTables,
        loading,
        saving,
        apiError,
        currentYear,
        fetchOvertimeTaxTables,
        // form
        formVisible,
        formLoading,
        overtimeTaxData,
        openForm,
        addOvertimeTaxRow,
        removeOvertimeTaxRow,
        saveOvertimeTaxTables,
        deleteOvertimeTaxTable,
        loadOvertimeTaxForYear,
        tableColumns
    }
}
