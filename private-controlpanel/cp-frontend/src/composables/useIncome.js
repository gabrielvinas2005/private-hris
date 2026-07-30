import { ref, computed } from 'vue'
import ApiService from '../Services/api'

export function useIncome() {
    const incomes = ref([])
    const loading = ref(false)
    const saving = ref(false)
    const apiError = ref(false)

    const formVisible = ref(false)
    const formLoading = ref(false)
    const formData = ref([{ id: null, name: '', is_taxable: true, is_time_related: false, active: true }])

    async function fetchIncomes() {
        loading.value = true
        apiError.value = false
        try {
            const res = await ApiService.getIncomes()
            const data = res?.data || []
            incomes.value = data.map(row => ({
                id: row.id,
                name: row.name,
                is_taxable: row.is_taxable === 1 || row.is_taxable === true || row.is_taxable === '1',
                is_time_related: row.is_time_related === 1 || row.is_time_related === true || row.is_time_related === '1',
                active: row.active === 1 || row.active === true || row.active === '1'
            }))
        } catch (e) {
            console.error('Error loading incomes:', e)
            apiError.value = true
            incomes.value = []
        } finally {
            loading.value = false
        }
    }

    function openForm() {
        formVisible.value = true
        formLoading.value = true
        formData.value = [{ id: null, name: '', is_taxable: true, is_time_related: false, active: true }]
        formLoading.value = false
    }

    function addRow() { formData.value.push({ id: null, name: '', is_taxable: true, is_time_related: false, active: true }) }
    function removeRow(index) { formData.value.splice(index, 1) }

    async function saveIncomes() {
        saving.value = true
        try {
            const validRows = formData.value.filter(r => r.name && r.name.trim() !== '')
            if (validRows.length === 0) throw new Error('Please fill in at least one income name')

            const ids = validRows.map((r) => r.id ?? 0)
            const names = validRows.map((r) => r.name.trim())
            const isTaxableStates = validRows.map((r) => !!r.is_taxable)
            const isTimeRelatedStates = validRows.map((r) => !!r.is_time_related)
            const activeStates = validRows.map((r) => !!r.active)

            const payload = {
                id: ids,
                name: names,
                is_taxable: isTaxableStates,
                is_time_related: isTimeRelatedStates,
                active: activeStates
            }

            const res = await ApiService.saveIncomes(payload)
            if (!res?.success) throw new Error(res?.message || 'Failed to save incomes')
            await fetchIncomes()
            formVisible.value = false
        } catch (e) {
            console.error('Error saving incomes:', e)
            throw e
        } finally {
            saving.value = false
        }
    }

    async function deleteIncome(id) {
        try {
            const res = await ApiService.deleteIncome(id)
            if (!res?.success) throw new Error(res?.message || 'Delete failed')
            await fetchIncomes()
        } catch (e) {
            console.error('Error deleting income:', e)
        }
    }

    const search = ref('')
    const filteredIncomes = computed(() => incomes.value.filter(r => !search.value || r.name.toLowerCase().includes(search.value.toLowerCase())))
    const columnVisibility = ref({ serial: true, name: true, is_taxable: true, is_time_related: true, active: true, actions: true })

    return {
        incomes, loading, saving, apiError,
        fetchIncomes,
        formVisible, formLoading, formData,
        openForm, addRow, removeRow, saveIncomes, deleteIncome,
        search, filteredIncomes, columnVisibility
    }
}


