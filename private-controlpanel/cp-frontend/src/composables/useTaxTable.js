import { ref, reactive, computed, nextTick } from 'vue'
import { ElMessage } from 'element-plus'
import ApiService from '../Services/api'

export function useTaxTable() {
    const MAX_TAX_TABLE_ENTRIES = 9
    const taxTables = ref([])
    const loading = ref(false)
    const saving = ref(false)
    const apiError = ref(false)

    // form state
    const formVisible = ref(false)
    const formLoading = ref(false)
    const taxData = ref([])
    
    // Current active tax table type
    const currentType = ref('monthly')

    async function fetchTaxTables(type = null) {
        const tableType = type || currentType.value
        loading.value = true
        apiError.value = false
        try {
            const res = await ApiService.getTaxTables(`?t=${Date.now()}`, tableType)
            console.log('API response:', res)
            
            // Handle different response structures
            let data = []
            if (res?.success && res?.data) {
                data = Array.isArray(res.data) ? res.data : []
            } else if (Array.isArray(res?.data)) {
                data = res.data
            } else if (Array.isArray(res)) {
                data = res
            }
            
            console.log('Tax data:', data)

            // Force update by creating a new array reference
            taxTables.value = data.map(tax => ({
                id: tax.id,
                min_amount: parseFloat(tax.min_amount) || 0,
                max_amount: parseFloat(tax.max_amount) || 0,
                base_tax: parseFloat(tax.base_tax) || 0,
                percentage: parseFloat(tax.percentage) || 0
            }))

            console.log('Processed tax tables:', taxTables.value)
        } catch (error) {
            console.error('Error fetching tax tables:', error)
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
            taxTables.value = [
                { id: 1, min_amount: 0, max_amount: 20833, base_tax: 0, percentage: 0 },
                { id: 2, min_amount: 20833, max_amount: 33332, base_tax: 0, percentage: 15 },
                { id: 3, min_amount: 33333, max_amount: 66666, base_tax: 1875, percentage: 20 },
                { id: 4, min_amount: 66667, max_amount: 166666, base_tax: 8541.80, percentage: 25 },
                { id: 5, min_amount: 166667, max_amount: 666666, base_tax: 33541.80, percentage: 30 },
                { id: 6, min_amount: 666667, max_amount: 999999, base_tax: 183541.80, percentage: 35 }
            ]

            // Show user-friendly error message
            if (error.response?.status === 500) {
                console.warn('Server error - showing sample tax data. Please check database connection.')
                console.warn('Possible causes: 1) tax_tables table does not exist, 2) database connection issue, 3) missing migrations')
            }
        } finally {
            loading.value = false
        }
    }

    function openForm() {
        formVisible.value = true
        formLoading.value = true

        // Initialize tax data with existing data or empty structure
        if (taxTables.value.length > 0) {
            taxData.value = taxTables.value.map(tax => ({
                id: tax.id,
                min_amount: tax.min_amount,
                max_amount: tax.max_amount,
                base_tax: tax.base_tax,
                percentage: tax.percentage
            }))
        } else {
            // Create default empty rows for new tax table
            taxData.value = Array.from({ length: 6 }, (_, index) => ({
                id: null,
                min_amount: '',
                max_amount: '',
                base_tax: '',
                percentage: ''
            }))
        }

        formLoading.value = false
    }

    function addTaxRow() {
        if (taxData.value.length >= MAX_TAX_TABLE_ENTRIES) {
            ElMessage.error(`Maximum entry for tax table is ${MAX_TAX_TABLE_ENTRIES} only.`)
            return
        }
        taxData.value.push({
            id: null,
            min_amount: '',
            max_amount: '',
            base_tax: '',
            percentage: ''
        })
    }

    function removeTaxRow(index) {
        taxData.value.splice(index, 1)
    }

    async function saveTaxTables(type = null) {
        const tableType = type || currentType.value
        saving.value = true
        try {
            // Filter out empty rows
            const validRows = taxData.value.filter(row =>
                row.min_amount !== '' && row.max_amount !== '' &&
                row.percentage !== '' && row.base_tax !== ''
            )

            if (validRows.length === 0) {
                throw new Error('Please fill in at least one tax row')
            }

            if (validRows.length > MAX_TAX_TABLE_ENTRIES) {
                throw new Error(`Maximum entry for tax table is ${MAX_TAX_TABLE_ENTRIES} only.`)
            }

            // Prepare payload in the format expected by backend
            const payload = {
                id: validRows.map(row => row.id),
                min_amount: validRows.map(row => parseFloat(row.min_amount) || 0),
                max_amount: validRows.map(row => parseFloat(row.max_amount) || 0),
                base_tax: validRows.map(row => parseFloat(row.base_tax) || 0),
                percentage: validRows.map(row => parseFloat(row.percentage) || 0)
            }

            console.log('Saving tax tables with payload:', payload)
            const response = await ApiService.saveTaxTables(payload, tableType)
            console.log('Save response:', response)

            if (response && (response.success === true || response.success === undefined)) {
                ElMessage.success(response.message || 'Tax table updated successfully')
                // Close form first
                formVisible.value = false
                // Force refresh the data to reflect changes
                try {
                    // Clear existing data to force re-render
                    taxTables.value = []
                    await nextTick()
                    // Small delay to ensure backend has processed the update
                    await new Promise(resolve => setTimeout(resolve, 500))
                    await fetchTaxTables(tableType)
                    await nextTick()
                    console.log('Tax tables refreshed after save, current count:', taxTables.value.length)
                } catch (fetchError) {
                    console.error('Error refreshing tax tables after save:', fetchError)
                    ElMessage.warning('Data saved but failed to refresh. Please refresh the page manually.')
                }
                console.log('Tax tables saved successfully')
            } else {
                throw new Error(response?.message || 'Failed to save tax tables')
            }
        } catch (error) {
            console.error('Error saving tax tables:', error)
            ElMessage.error(error.message || 'Failed to save tax tables')
            throw error
        } finally {
            saving.value = false
        }
    }

    async function deleteTaxTable(id, type = null) {
        const tableType = type || currentType.value
        try {
            await ApiService.deleteTaxTable(id, tableType)
            await fetchTaxTables(tableType)
        } catch (error) {
            console.error('Error deleting tax table:', error)
        }
    }
    
    function setCurrentType(type) {
        currentType.value = type
    }

    const tableColumns = [
        { key: 'min_amount', label: 'Min Amount', minWidth: 150 },
        { key: 'max_amount', label: 'Max Amount', minWidth: 150 },
        { key: 'base_tax', label: 'Base Tax', minWidth: 150 },
        { key: 'percentage', label: 'Percentage', minWidth: 150 },
        { key: 'actions', label: 'Actions', width: 100, fixed: 'right' }
    ]

    return {
        taxTables,
        loading,
        saving,
        apiError,
        fetchTaxTables,
        // form
        formVisible,
        formLoading,
        taxData,
        openForm,
        addTaxRow,
        removeTaxRow,
        saveTaxTables,
        deleteTaxTable,
        tableColumns,
        // type management
        currentType,
        setCurrentType
    }
}
