import { ref, computed } from 'vue'
import ApiService from '../Services/api'

export function usePhilhealthTable() {
    const philTables = ref([])
    const loading = ref(false)
    const saving = ref(false)
    const apiError = ref(false)

    // form state
    const formVisible = ref(false)
    const formLoading = ref(false)
    const formData = ref([])

    async function fetchPhilTables() {
        loading.value = true
        apiError.value = false
        try {
            const res = await ApiService.getPhilhealthTables()
            const data = res?.data || []
            philTables.value = data.map(row => ({
                ...row,
                year: parseInt(row.year, 10) || 0,
                multiplier: parseFloat(row.multiplier) || 0,
                income_floor: parseFloat(row.income_floor) || 0,
                income_ceiling: parseFloat(row.income_ceiling) || 0,
                fix_rate: parseFloat(row.fix_rate) || 0
            }))
        } catch (error) {
            console.error('Error fetching PhilHealth tables:', error)
            apiError.value = true
            // fallback sample
            const y = new Date().getFullYear()
            philTables.value = [{ id: 1, year: y, multiplier: 0.04, income_floor: 10000, income_ceiling: 60000, fix_rate: 0 }]
        } finally {
            loading.value = false
        }
    }

    function openForm() {
        formVisible.value = true
        formLoading.value = true
        // Start with existing rows (for visibility) and always append one empty row for adding
        formData.value = (philTables.value.length > 0
            ? philTables.value.map(r => ({
                id: r.id,
                year: r.year,
                multiplier: r.multiplier,
                income_floor: r.income_floor,
                income_ceiling: r.income_ceiling,
                fix_rate: r.fix_rate
            }))
            : [])

        // Ensure there's at least one empty row to add
        formData.value.push({ id: null, year: '', multiplier: '', income_floor: '', income_ceiling: '', fix_rate: '' })
        formLoading.value = false
    }

    function addRow() { formData.value.push({ id: null, year: '', multiplier: '', income_floor: '', income_ceiling: '', fix_rate: '' }) }
    function removeRow(index) { formData.value.splice(index, 1) }

    async function savePhilTables() {
        saving.value = true
        try {
            const validRows = formData.value.filter(r => r.year !== '' && r.multiplier !== '' && r.income_floor !== '' && r.income_ceiling !== '' && r.fix_rate !== '')
            if (validRows.length === 0) throw new Error('Please fill in at least one row')

            const payload = {
                id: validRows.map(r => r.id),
                year: validRows.map(r => parseInt(r.year, 10) || 0),
                multiplier: validRows.map(r => parseFloat(r.multiplier) || 0),
                income_floor: validRows.map(r => parseFloat(r.income_floor) || 0),
                income_ceiling: validRows.map(r => parseFloat(r.income_ceiling) || 0),
                fix_rate: validRows.map(r => parseFloat(r.fix_rate) || 0)
            }

            const res = await ApiService.savePhilhealthTables(payload)
            if (!res?.success) throw new Error(res?.message || 'Failed to save PhilHealth table')
            await fetchPhilTables()
            formVisible.value = false
        } catch (e) {
            console.error('Error saving PhilHealth tables:', e)
            throw e
        } finally {
            saving.value = false
        }
    }

    async function deletePhil(id) {
        try {
            const res = await ApiService.deletePhilhealth(id)
            if (!res?.success) throw new Error(res?.message || 'Delete failed')
            await fetchPhilTables()
        } catch (e) {
            console.error('Error deleting PhilHealth:', e)
        }
    }

    const search = ref('')
    const filteredPhilTables = computed(() => philTables.value.filter(r => {
        const target = `${r.year}`.toLowerCase()
        return !search.value || target.includes(search.value.toLowerCase())
    }))

    const columnVisibility = ref({ serial: true, year: true, multiplier: true, income_floor: true, income_ceiling: true, fix_rate: true, actions: true })

    return {
        philTables,
        loading,
        saving,
        apiError,
        fetchPhilTables,
        formVisible,
        formLoading,
        formData,
        openForm,
        addRow,
        removeRow,
        savePhilTables,
        deletePhil,
        search,
        filteredPhilTables,
        columnVisibility
    }
}


