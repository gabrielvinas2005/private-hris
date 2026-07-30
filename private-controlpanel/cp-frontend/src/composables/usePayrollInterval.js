import { ref, computed } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import ApiService from '../Services/api'

export function usePayrollInterval() {
    const intervals = ref([])
    const loading = ref(false)
    const saving = ref(false)
    const apiError = ref(false)

    const formVisible = ref(false)
    const formLoading = ref(false)
    const formData = ref([{ id: null, name: '', day_interval: '', month_frequency: '', year_frequency: '' }])

    async function fetchIntervals() {
        loading.value = true
        apiError.value = false
        try {
            const res = await ApiService.getPayrollIntervals()
            const data = res?.data || []
            intervals.value = data.map(row => ({
                id: row.id,
                name: row.name,
                day_interval: Number(row.day_interval) || 0,
                month_frequency: Number(row.month_frequency) || 0,
                year_frequency: Number(row.year_frequency) || 0,
                active: row.active === true || row.active === 1 || row.active === '1'
            }))
        } catch (e) {
            console.error('Error loading payroll intervals:', e)
            apiError.value = true
            intervals.value = []
        } finally {
            loading.value = false
        }
    }

    function openForm() {
        formVisible.value = true
        formLoading.value = true
        formData.value = [{ id: null, name: '', day_interval: '', month_frequency: '', year_frequency: '' }]
        formLoading.value = false
    }

    function addRow() { formData.value.push({ id: null, name: '', day_interval: '', month_frequency: '', year_frequency: '' }) }
    function removeRow(index) { formData.value.splice(index, 1) }

    async function saveIntervals() {
        saving.value = true
        try {
            const validRows = formData.value.filter(r => r.name && r.name.trim() !== '' && r.day_interval !== '' && r.month_frequency !== '' && r.year_frequency !== '')
            if (validRows.length === 0) throw new Error('Please fill in at least one complete row')

            const ids = validRows.map((r) => r.id ?? 0)
            const names = validRows.map((r) => r.name.trim())
            const dayIntervals = validRows.map((r) => Number(r.day_interval))
            const monthFrequencies = validRows.map((r) => Number(r.month_frequency))
            const yearFrequencies = validRows.map((r) => Number(r.year_frequency))

            const payload = {
                id: ids,
                name: names,
                day_interval: dayIntervals,
                month_frequency: monthFrequencies,
                year_frequency: yearFrequencies
            }

            const res = await ApiService.savePayrollIntervals(payload)
            if (!res?.success) throw new Error(res?.message || 'Failed to save payroll intervals')
            await fetchIntervals()
            formVisible.value = false
        } catch (e) {
            console.error('Error saving payroll intervals:', e)
            throw e
        } finally {
            saving.value = false
        }
    }

    async function deleteInterval(id) {
        try {
            await ElMessageBox.confirm(
                'Are you sure you want to delete this payroll interval?',
                'Confirm Deletion',
                {
                    confirmButtonText: 'Delete',
                    cancelButtonText: 'Cancel',
                    type: 'warning'
                }
            )

            const res = await ApiService.deletePayrollInterval(id)
            if (!res?.success) throw new Error(res?.message || 'Delete failed')
            ElMessage.success(res?.message || 'Payroll interval deleted successfully')
            await fetchIntervals()
        } catch (e) {
            if (e !== 'cancel') {
                console.error('Error deleting payroll interval:', e)
                ElMessage.error(e?.message || 'Failed to delete payroll interval')
            }
        }
    }

    async function toggleIntervalActive(row) {
        const nextValue = !row.active
        row.active = nextValue
        try {
            const res = await ApiService.updatePayrollIntervalActive(row.id, { active: nextValue })
            if (!res?.success) throw new Error(res?.message || 'Failed to update status')
            ElMessage.success(res?.message || 'Payroll interval status updated')
        } catch (e) {
            row.active = !nextValue
            console.error('Error updating payroll interval status:', e)
            ElMessage.error(e?.message || 'Failed to update payroll interval status')
        }
    }

    const search = ref('')
    const filteredIntervals = computed(() => intervals.value.filter(r => !search.value || r.name.toLowerCase().includes(search.value.toLowerCase())))
    const columnVisibility = ref({ serial: true, name: true, day_interval: true, month_frequency: true, year_frequency: true, active: true, actions: true })

    return {
        intervals, loading, saving, apiError,
        fetchIntervals,
        formVisible, formLoading, formData,
        openForm, addRow, removeRow, saveIntervals, deleteInterval, toggleIntervalActive,
        search, filteredIntervals, columnVisibility
    }
}


