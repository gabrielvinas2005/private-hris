import { ref, computed } from 'vue'
import ApiService from '../Services/api'

export function useSalaryStep() {
    const steps = ref([])
    const loading = ref(false)
    const saving = ref(false)
    const apiError = ref(false)

    const formVisible = ref(false)
    const formLoading = ref(false)
    const formData = ref([])

    async function fetchSteps() {
        loading.value = true
        apiError.value = false
        try {
            const res = await ApiService.getSalarySteps()
            const data = res?.data || []
            steps.value = data.map(row => ({
                id: row.id,
                name: row.name,
                active: row.active === 1 || row.active === true || row.active === '1'
            }))
        } catch (e) {
            console.error('Error loading salary steps:', e)
            apiError.value = true
            steps.value = []
        } finally {
            loading.value = false
        }
    }

    function openForm() {
        formVisible.value = true
        formLoading.value = true
        if (steps.value.length > 0) {
            formData.value = steps.value.map(r => ({ id: r.id, name: r.name, active: r.active }))
        } else {
            formData.value = Array.from({ length: 5 }, (_, i) => ({ id: null, name: '', active: true }))
        }
        // always append one empty row for adding
        formData.value.push({ id: null, name: '', active: true })
        formLoading.value = false
    }

    function addRow() { formData.value.push({ id: null, name: '', active: true }) }
    function removeRow(index) { formData.value.splice(index, 1) }

    async function saveSteps() {
        saving.value = true
        try {
            const validRows = formData.value.filter(r => r.name && r.name.trim() !== '')
            if (validRows.length === 0) throw new Error('Please fill in at least one step name')

            // Backend expects checkbox-like behavior: active[id] present means true, omitted means false
            const ids = validRows.map((r) => (r.id ?? 0))
            const names = validRows.map((r) => r.name.trim())
            const activeMap = {}
            validRows.forEach((r) => {
                const key = r.id ?? 0
                if (r.active) {
                    activeMap[key] = true
                }
            })

            const payload = { id: ids, name: names, active: activeMap }

            const res = await ApiService.saveSalarySteps(payload)
            if (!res?.success) throw new Error(res?.message || 'Failed to save salary steps')
            await fetchSteps()
            formVisible.value = false
        } catch (e) {
            console.error('Error saving salary steps:', e)
            throw e
        } finally {
            saving.value = false
        }
    }

    async function deleteStep(id) {
        try {
            const res = await ApiService.deleteSalaryStep(id)
            if (!res?.success) throw new Error(res?.message || 'Delete failed')
            await fetchSteps()
        } catch (e) {
            console.error('Error deleting salary step:', e)
        }
    }

    const search = ref('')
    const filteredSteps = computed(() => steps.value.filter(r => !search.value || r.name.toLowerCase().includes(search.value.toLowerCase())))
    const columnVisibility = ref({ serial: true, name: true, active: true, actions: true })

    return {
        steps, loading, saving, apiError,
        fetchSteps,
        formVisible, formLoading, formData,
        openForm, addRow, removeRow, saveSteps, deleteStep,
        search, filteredSteps, columnVisibility
    }
}


