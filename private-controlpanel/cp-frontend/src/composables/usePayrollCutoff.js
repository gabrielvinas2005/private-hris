import { ref, computed } from 'vue'
import ApiService from '../Services/api'

export function usePayrollCutoff() {
    const cutoffs = ref([])
    const intervals = ref([])
    const loading = ref(false)
    const saving = ref(false)
    const apiError = ref(false)

    const formVisible = ref(false)
    const formLoading = ref(false)
    const form = ref({ id: 0, name: '', payroll_interval_id: '' })

    async function fetchCutoffs() {
        loading.value = true
        apiError.value = false
        try {
            const res = await ApiService.getPayrollCutoffs()
            const data = res?.data || []
            cutoffs.value = data.map(row => ({ id: row.id, payroll_cutoff: row.payroll_cutoff, payroll_interval: row.payroll_interval }))
        } catch (e) {
            console.error('Error loading payroll cutoffs:', e)
            apiError.value = true
            cutoffs.value = []
        } finally {
            loading.value = false
        }
    }

    async function openForm(id = 0) {
        formVisible.value = true
        formLoading.value = true
        try {
            const res = await ApiService.getPayrollCutoffForm(id)
            intervals.value = res?.data?.intervals || []
            const current = (res?.data?.cutoffs && res.data.cutoffs[0]) || { id: 0, name: '', payroll_interval_id: '' }
            // When backend sends 0 for new, show empty placeholder instead of 0
            const intervalId = current.payroll_interval_id ? current.payroll_interval_id : ''
            form.value = { id: current.id ?? 0, name: current.name ?? '', payroll_interval_id: intervalId }
        } catch (e) {
            console.error('Error loading cutoff form:', e)
        } finally {
            formLoading.value = false
        }
    }

    async function saveCutoff() {
        saving.value = true
        try {
            const payload = { name: form.value.name, payroll_interval_id: form.value.payroll_interval_id }
            const res = await ApiService.savePayrollCutoff(form.value.id ?? 0, payload)
            if (!res?.success) throw new Error(res?.message || 'Failed to save payroll cutoff')
            await fetchCutoffs()
            formVisible.value = false
        } catch (e) {
            console.error('Error saving payroll cutoff:', e)
            throw e
        } finally {
            saving.value = false
        }
    }

    const search = ref('')
    const filteredCutoffs = computed(() => cutoffs.value.filter(r => !search.value || r.payroll_cutoff.toLowerCase().includes(search.value.toLowerCase())))
    const columnVisibility = ref({ serial: true, payroll_cutoff: true, payroll_interval: true, actions: false })

    return {
        cutoffs, intervals, loading, saving, apiError,
        fetchCutoffs,
        formVisible, formLoading, form,
        openForm, saveCutoff,
        search, filteredCutoffs, columnVisibility
    }
}


