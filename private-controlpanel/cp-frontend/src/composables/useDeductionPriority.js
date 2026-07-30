import { ref, computed } from 'vue'
import ApiService from '../Services/api'

export function useDeductionPriority() {
    const priorities = ref([])
    const loading = ref(false)
    const saving = ref(false)
    const apiError = ref(false)

    const formVisible = ref(false)
    const formLoading = ref(false)
    const formData = ref([{ id: null, deduction: '', priority: '' }])

    async function fetchPriorities() {
        loading.value = true
        apiError.value = false
        try {
            const res = await ApiService.getDeductionPriorities()
            const data = res?.data || []
            priorities.value = data.map(row => ({ id: row.id, deduction: row.deduction ?? '', priority: row.priority }))
        } catch (e) {
            console.error('Error loading priorities:', e)
            apiError.value = true
            priorities.value = []
        } finally {
            loading.value = false
        }
    }

    function openForm() {
        formVisible.value = true
        formLoading.value = true
        formData.value = [{ id: null, deduction: '', priority: '' }]
        formLoading.value = false
    }

    function addRow() { formData.value.push({ id: null, deduction: '', priority: '' }) }
    function removeRow(index) { formData.value.splice(index, 1) }

    async function savePriorities() {
        saving.value = true
        try {
            const validRows = formData.value.filter(r => r.deduction && r.deduction.trim() !== '' && r.priority !== '' && r.priority !== null && r.priority !== undefined)
            if (validRows.length === 0) throw new Error('Please fill in at least one row with name and priority')

            const ids = validRows.map((r) => r.id ?? 0)
            const names = validRows.map((r) => r.deduction.trim())
            const prios = validRows.map((r) => Number(r.priority))

            const payload = { id: ids, name: names, deduction: names, priority: prios }

            const res = await ApiService.saveDeductionPriorities(payload)
            if (!res?.success) throw new Error(res?.message || 'Failed to save priorities')
            await fetchPriorities()
            formVisible.value = false
        } catch (e) {
            console.error('Error saving priorities:', e)
            throw e
        } finally {
            saving.value = false
        }
    }

    const search = ref('')
    const filteredPriorities = computed(() => priorities.value.filter(r => !search.value || (r.deduction ?? '').toLowerCase().includes(search.value.toLowerCase())))
    const columnVisibility = ref({ serial: true, name: true, priority: true, actions: false })

    return {
        priorities, loading, saving, apiError,
        fetchPriorities,
        formVisible, formLoading, formData,
        openForm, addRow, removeRow, savePriorities,
        search, filteredPriorities, columnVisibility
    }
}


