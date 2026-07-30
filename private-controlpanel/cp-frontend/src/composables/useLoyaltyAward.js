import { ref, computed } from 'vue'
import ApiService from '../Services/api'

export function useLoyaltyAward() {
    const awards = ref([])
    const loading = ref(false)
    const saving = ref(false)
    const apiError = ref(false)

    const formVisible = ref(false)
    const formLoading = ref(false)
    const formData = ref([{ id: 0, years_of_service: '', cash_award: '' }])

    async function fetchAwards() {
        loading.value = true
        apiError.value = false
        try {
            const res = await ApiService.getLoyaltyAwards()
            const data = res?.data || []
            awards.value = data.map(row => ({ id: row.id, years_of_service: Number(row.years_of_service) || 0, cash_award: Number(row.cash_award) || 0, cash_token: Number(row.cash_token) || 0 }))
        } catch (e) {
            console.error('Error loading loyalty awards:', e)
            apiError.value = true
            awards.value = []
        } finally {
            loading.value = false
        }
    }

    function openForm() {
        formVisible.value = true
        formLoading.value = true
        formData.value = [{ id: 0, years_of_service: '', cash_award: '' }]
        formLoading.value = false
    }

    function addRow() { formData.value.push({ id: 0, years_of_service: '', cash_award: '' }) }
    function removeRow(index) { formData.value.splice(index, 1) }

    async function saveAwards() {
        saving.value = true
        try {
            const validRows = formData.value.filter(r => r.years_of_service !== '' && r.cash_award !== '')
            if (validRows.length === 0) throw new Error('Please fill in at least one row')

            // Backend loop starts at index 1, so prepend a dummy element at index 0
            const ids = [0, ...validRows.map(r => r.id ?? 0)]
            const years = [null, ...validRows.map(r => Number(r.years_of_service))]
            const cash = [null, ...validRows.map(r => Number(r.cash_award))]

            const payload = { id: ids, years_of_service: years, cash_award: cash }

            const res = await ApiService.saveLoyaltyAwards(payload)
            if (!res?.success) throw new Error(res?.message || 'Failed to save loyalty awards')
            await fetchAwards()
            formVisible.value = false
        } catch (e) {
            console.error('Error saving loyalty awards:', e)
            throw e
        } finally {
            saving.value = false
        }
    }

    async function deleteAward(id) {
        try {
            const res = await ApiService.deleteLoyaltyAward(id)
            if (!res?.success) throw new Error(res?.message || 'Delete failed')
            await fetchAwards()
        } catch (e) {
            console.error('Error deleting loyalty award:', e)
        }
    }

    const search = ref('')
    const filteredAwards = computed(() => awards.value.filter(r => !search.value || String(r.years_of_service).includes(search.value)))
    const columnVisibility = ref({ serial: true, years_of_service: true, cash_award: true, cash_token: true, actions: true })

    return {
        awards, loading, saving, apiError,
        fetchAwards,
        formVisible, formLoading, formData,
        openForm, addRow, removeRow, saveAwards, deleteAward,
        search, filteredAwards, columnVisibility
    }
}


