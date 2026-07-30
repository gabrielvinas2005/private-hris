import { ref, computed } from 'vue'
import ApiService from '../Services/api'

export function useGsisTable() {
    const gsisTables = ref([])
    const loading = ref(false)
    const saving = ref(false)
    const apiError = ref(false)

    // form state
    const formVisible = ref(false)
    const formLoading = ref(false)
    const formData = ref([])

    async function fetchGsisTables() {
        loading.value = true
        apiError.value = false
        try {
            const res = await ApiService.getGsisTables()
            const data = res?.data || []
            gsisTables.value = data.map(row => ({
                ...row,
                year: parseInt(row.year, 10) || 0,
                multiplier: parseFloat(row.multiplier) || 0,
                employer_share: parseFloat(row.employer_share) || 0,
                employer_share_admin: parseFloat(row.employer_share_admin) || 0,
                effectivity_date: row.effectivity_date || null,
                end_date: row.end_date || null
            }))
        } catch (error) {
            console.error('Error fetching GSIS tables:', error)
            apiError.value = true
            // fallback sample
            const y = new Date().getFullYear()
            gsisTables.value = [{ id: 1, year: y, multiplier: 0.14, employer_share: 0.12, employer_share_admin: 0.02, effectivity_date: '2024-01-01', end_date: '2024-12-31' }]
        } finally {
            loading.value = false
        }
    }

    function openForm() {
        formVisible.value = true
        formLoading.value = true
        if (gsisTables.value.length > 0) {
            formData.value = gsisTables.value.map(r => ({
                id: r.id,
                year: r.year,
                multiplier: r.multiplier,
                employer_share: r.employer_share,
                employer_share_admin: r.employer_share_admin,
                effectivity_date: r.effectivity_date,
                end_date: r.end_date
            }))
        } else {
            formData.value = Array.from({ length: 3 }, () => ({
                id: null, year: '', multiplier: '', employer_share: '', employer_share_admin: '', effectivity_date: '', end_date: ''
            }))
        }
        formLoading.value = false
    }

    function addRow() { formData.value.push({ id: null, year: '', multiplier: '', employer_share: '', employer_share_admin: '', effectivity_date: '', end_date: '' }) }
    function removeRow(index) { formData.value.splice(index, 1) }

    async function saveGsisTables() {
        saving.value = true
        try {
            const validRows = formData.value.filter(r => r.year !== '' && r.multiplier !== '' && r.employer_share !== '' && r.employer_share_admin !== '' && r.effectivity_date !== '' && r.end_date !== '')
            if (validRows.length === 0) throw new Error('Please fill in at least one row')

            const payload = {
                id: validRows.map(r => r.id),
                year: validRows.map(r => parseInt(r.year, 10) || 0),
                multiplier: validRows.map(r => parseFloat(r.multiplier) || 0),
                employer_share: validRows.map(r => parseFloat(r.employer_share) || 0),
                employer_share_admin: validRows.map(r => parseFloat(r.employer_share_admin) || 0),
                effectivity_date: validRows.map(r => r.effectivity_date),
                end_date: validRows.map(r => r.end_date)
            }

            const res = await ApiService.saveGsisTables(payload)
            if (!res?.success) throw new Error(res?.message || 'Failed to save GSIS table')
            await fetchGsisTables()
            formVisible.value = false
        } catch (e) {
            console.error('Error saving GSIS tables:', e)
            throw e
        } finally {
            saving.value = false
        }
    }

    async function deleteGsis(id) {
        try {
            const res = await ApiService.deleteGsis(id)
            if (!res?.success) throw new Error(res?.message || 'Delete failed')
            await fetchGsisTables()
        } catch (e) {
            console.error('Error deleting GSIS:', e)
        }
    }

    const search = ref('')
    const filteredGsisTables = computed(() => gsisTables.value.filter(r => {
        const target = `${r.year}`.toLowerCase()
        return !search.value || target.includes(search.value.toLowerCase())
    }))

    const columnVisibility = ref({ serial: true, year: true, multiplier: true, employer_share: true, employer_share_admin: true, effectivity_date: true, end_date: true, actions: true })

    return {
        gsisTables,
        loading,
        saving,
        apiError,
        fetchGsisTables,
        formVisible,
        formLoading,
        formData,
        openForm,
        addRow,
        removeRow,
        saveGsisTables,
        deleteGsis,
        search,
        filteredGsisTables,
        columnVisibility
    }
}


