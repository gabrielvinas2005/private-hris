import { ref, computed } from 'vue'
import ApiService from '../Services/api'

export function useSalaryGrade() {
    const grades = ref([])
    const loading = ref(false)
    const saving = ref(false)
    const apiError = ref(false)

    const formVisible = ref(false)
    const formLoading = ref(false)
    const formData = ref([])

    async function fetchGrades() {
        loading.value = true
        apiError.value = false
        try {
            const res = await ApiService.getSalaryGrades()
            const data = res?.data || []
            grades.value = data.map(row => ({ id: row.id, name: row.name, active: row.active === 1 || row.active === true || row.active === '1' }))
        } catch (e) {
            console.error('Error loading salary grades:', e)
            apiError.value = true
            grades.value = []
        } finally {
            loading.value = false
        }
    }

    function openForm() {
        formVisible.value = true
        formLoading.value = true
        // Initialize with a single empty input row
        formData.value = [{ id: null, name: '', active: true }]
        formLoading.value = false
    }

    function addRow() { formData.value.push({ id: null, name: '', active: true }) }
    function removeRow(index) { formData.value.splice(index, 1) }

    async function saveGrades() {
        saving.value = true
        try {
            const validRows = formData.value.filter(r => r.name && r.name.trim() !== '')
            if (validRows.length === 0) throw new Error('Please fill in at least one grade name')

            const ids = validRows.map((r) => r.id ?? 0)
            const names = validRows.map((r) => r.name.trim())
            const activeStates = validRows.map((r) => !!r.active)

            const payload = { id: ids, name: names, active: activeStates }

            const res = await ApiService.saveSalaryGrades(payload)
            if (!res?.success) throw new Error(res?.message || 'Failed to save salary grades')
            await fetchGrades()
            formVisible.value = false
        } catch (e) {
            console.error('Error saving salary grades:', e)
            throw e
        } finally {
            saving.value = false
        }
    }

    async function deleteGrade(id) {
        try {
            const res = await ApiService.deleteSalaryGrade(id)
            if (!res?.success) throw new Error(res?.message || 'Delete failed')
            await fetchGrades()
        } catch (e) {
            console.error('Error deleting salary grade:', e)
        }
    }

    const search = ref('')
    const filteredGrades = computed(() => grades.value.filter(r => !search.value || r.name.toLowerCase().includes(search.value.toLowerCase())))
    const columnVisibility = ref({ serial: true, name: true, active: true, actions: true })

    return {
        grades, loading, saving, apiError,
        fetchGrades,
        formVisible, formLoading, formData,
        openForm, addRow, removeRow, saveGrades, deleteGrade,
        search, filteredGrades, columnVisibility
    }
}


