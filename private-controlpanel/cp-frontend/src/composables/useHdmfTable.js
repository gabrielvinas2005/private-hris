import { ref, computed } from 'vue'
import ApiService from '../Services/api'

export function useHdmfTable() {
    const hdmfTables = ref([])
    const loading = ref(false)
    const saving = ref(false)
    const apiError = ref(false)

    // form state
    const formVisible = ref(false)
    const formLoading = ref(false)
    const hdmfData = ref([])

    async function fetchHdmfTables() {
        loading.value = true
        apiError.value = false
        try {
            const res = await ApiService.getHdmfTables()
            const data = res?.data || []
            hdmfTables.value = data.map(row => ({
                ...row,
                year: parseInt(row.year, 10) || 0,
                amount: parseFloat(row.amount) || 0
            }))
        } catch (error) {
            console.error('Error fetching HDMF tables:', error)
            apiError.value = true
            // fallback sample
            hdmfTables.value = [
                { id: 1, year: new Date().getFullYear(), amount: 100.00 }
            ]
        } finally {
            loading.value = false
        }
    }

    function openForm() {
        formVisible.value = true
        formLoading.value = true
        if (hdmfTables.value.length > 0) {
            hdmfData.value = hdmfTables.value.map(r => ({ id: r.id, year: r.year, amount: r.amount }))
        } else {
            hdmfData.value = Array.from({ length: 6 }, () => ({ id: null, year: '', amount: '' }))
        }
        formLoading.value = false
    }

    function addRow() {
        hdmfData.value.push({ id: null, year: '', amount: '' })
    }

    function removeRow(index) {
        hdmfData.value.splice(index, 1)
    }

    async function saveHdmfTables() {
        saving.value = true
        try {
            const validRows = hdmfData.value.filter(r => r.year !== '' && r.amount !== '')
            if (validRows.length === 0) throw new Error('Please fill in at least one row')

            const payload = {
                id: validRows.map(r => r.id),
                year: validRows.map(r => parseInt(r.year, 10) || 0),
                amount: validRows.map(r => parseFloat(r.amount) || 0)
            }

            const res = await ApiService.saveHdmfTables(payload)
            if (!res?.success) throw new Error(res?.message || 'Failed to save HDMF table')
            await fetchHdmfTables()
            formVisible.value = false
        } catch (e) {
            console.error('Error saving HDMF tables:', e)
            throw e
        } finally {
            saving.value = false
        }
    }

    async function deleteHdmf(id) {
        try {
            const res = await ApiService.deleteHdmf(id)
            if (!res?.success) throw new Error(res?.message || 'Delete failed')
            await fetchHdmfTables()
        } catch (e) {
            console.error('Error deleting HDMF:', e)
        }
    }

    const filtered = ref('')
    const filteredHdmfTables = computed(() => hdmfTables.value.filter(r => {
        const target = `${r.year}`.toLowerCase()
        return !filtered.value || target.includes(filtered.value.toLowerCase())
    }))

    const columnVisibility = ref({
        serial: true,
        year: true,
        amount: true,
        actions: true
    })

    return {
        hdmfTables,
        loading,
        saving,
        apiError,
        fetchHdmfTables,
        formVisible,
        formLoading,
        hdmfData,
        openForm,
        addRow,
        removeRow,
        saveHdmfTables,
        deleteHdmf,
        filtered,
        filteredHdmfTables,
        columnVisibility
    }
}


