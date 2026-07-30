import { ref, computed } from 'vue'
import ApiService from '../Services/api'

export function useUniformClothing() {
    const records = ref([])
    const loading = ref(false)
    const saving = ref(false)
    const apiError = ref(false)

    const formVisible = ref(false)
    const formLoading = ref(false)
    const formData = ref([{ id: 0, description: '', cloth_rate: '', year: '' }])

    async function fetchRecords() {
        loading.value = true
        apiError.value = false
        try {
            const res = await ApiService.getUniformClothing()
            const data = res?.data || []
            records.value = data.map(r => ({ id: r.id, description: r.description, cloth_rate: Number(r.cloth_rate) || 0, year: Number(r.year) || 0 }))
        } catch (e) {
            console.error('Error loading uniform & clothing:', e)
            apiError.value = true
            records.value = []
        } finally {
            loading.value = false
        }
    }

    function openForm() {
        formVisible.value = true
        formLoading.value = true
        formData.value = [{ id: 0, description: '', cloth_rate: '', year: '' }]
        formLoading.value = false
    }

    function addRow() { formData.value.push({ id: 0, description: '', cloth_rate: '', year: '' }) }
    function removeRow(index) { formData.value.splice(index, 1) }

    async function saveRecords() {
        saving.value = true
        try {
            const validRows = formData.value.filter(r => r.description && r.description.trim() !== '' && r.cloth_rate !== '' && r.year !== '')
            if (validRows.length === 0) throw new Error('Please fill in at least one row')

            const ids = validRows.map(r => r.id ?? 0)
            const descriptions = validRows.map(r => r.description.trim())
            const clothRates = validRows.map(r => Number(r.cloth_rate))
            const years = validRows.map(r => Number(r.year))

            const payload = { id: ids, description: descriptions, cloth_rate: clothRates, year: years }

            const res = await ApiService.saveUniformClothing(payload)
            if (!res?.success) throw new Error(res?.message || 'Failed to save records')
            await fetchRecords()
            formVisible.value = false
        } catch (e) {
            console.error('Error saving uniform & clothing:', e)
            throw e
        } finally {
            saving.value = false
        }
    }

    async function deleteRecord(id) {
        try {
            const res = await ApiService.deleteUniformClothing(id)
            if (!res?.success) throw new Error(res?.message || 'Delete failed')
            await fetchRecords()
        } catch (e) {
            console.error('Error deleting record:', e)
        }
    }

    const search = ref('')
    const filteredRecords = computed(() => records.value.filter(r => !search.value || r.description.toLowerCase().includes(search.value.toLowerCase())))
    const columnVisibility = ref({ serial: true, description: true, cloth_rate: true, year: true, actions: true })

    return {
        records, loading, saving, apiError,
        fetchRecords,
        formVisible, formLoading, formData,
        openForm, addRow, removeRow, saveRecords, deleteRecord,
        search, filteredRecords, columnVisibility
    }
}


