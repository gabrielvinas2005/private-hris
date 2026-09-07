import { ref, computed } from 'vue'
import ApiService from '../Services/api'

export function useDeduction() {
    const deductions = ref([])
    const loading = ref(false)
    const saving = ref(false)
    const apiError = ref(false)

    const formVisible = ref(false)
    const formLoading = ref(false)
    const formData = ref([{ id: null, name: '', uacs: '', mfo_pap: '', is_sss: false, is_philhealth: false, is_pagibig: false, is_bank: false, active: true }])

    async function fetchDeductions() {
        loading.value = true
        apiError.value = false
        try {
            const res = await ApiService.getDeductions()
            const data = res?.data || []
            deductions.value = data.map(row => ({
                id: row.id,
                name: row.name,
                uacs: row.uacs || '',
                mfo_pap: row.mfo_pap || '',
                is_sss: row.is_sss === 1 || row.is_sss === true || row.is_sss === '1',
                is_philhealth: row.is_philhealth === 1 || row.is_philhealth === true || row.is_philhealth === '1',
                is_pagibig: row.is_pagibig === 1 || row.is_pagibig === true || row.is_pagibig === '1',
                is_bank: row.is_bank === 1 || row.is_bank === true || row.is_bank === '1',
                active: row.active === 1 || row.active === true || row.active === '1'
            }))
        } catch (e) {
            console.error('Error loading deductions:', e)
            apiError.value = true
            deductions.value = []
        } finally {
            loading.value = false
        }
    }

    function openForm() {
        formVisible.value = true
        formLoading.value = true
        formData.value = [{ id: null, name: '', uacs: '', mfo_pap: '', is_sss: false, is_philhealth: false, is_pagibig: false, is_bank: false, active: true }]
        formLoading.value = false
    }

    function addRow() { formData.value.push({ id: null, name: '', uacs: '', mfo_pap: '', is_sss: false, is_philhealth: false, is_pagibig: false, is_bank: false, active: true }) }
    function removeRow(index) { formData.value.splice(index, 1) }

    async function saveDeductions() {
        saving.value = true
        try {
            const validRows = formData.value.filter(r => r.name && r.name.trim() !== '')
            if (validRows.length === 0) throw new Error('Please fill in at least one deduction name')

            const ids = validRows.map((r) => r.id ?? 0)
            const names = validRows.map((r) => r.name.trim())
            const uacs = validRows.map((r) => r.uacs ?? '')
            const mfo_pap = validRows.map((r) => r.mfo_pap ?? '')
            const isSSS = validRows.map((r) => !!r.is_sss)
            const isPH = validRows.map((r) => !!r.is_philhealth)
            const isPagibig = validRows.map((r) => !!r.is_pagibig)
            const isBank = validRows.map((r) => !!r.is_bank)
            const active = validRows.map((r) => !!r.active)

            const payload = { id: ids, name: names, uacs, mfo_pap, is_sss: isSSS, is_philhealth: isPH, is_pagibig: isPagibig, is_bank: isBank, active }

            const res = await ApiService.saveDeductions(payload)
            if (!res?.success) throw new Error(res?.message || 'Failed to save deductions')
            await fetchDeductions()
            formVisible.value = false
        } catch (e) {
            console.error('Error saving deductions:', e)
            throw e
        } finally {
            saving.value = false
        }
    }

    async function deleteDeduction(id) {
        try {
            const res = await ApiService.deleteDeduction(id)
            if (!res?.success) throw new Error(res?.message || 'Delete failed')
            await fetchDeductions()
        } catch (e) {
            console.error('Error deleting deduction:', e)
        }
    }

    const search = ref('')
    const filteredDeductions = computed(() => deductions.value.filter(r => !search.value || r.name.toLowerCase().includes(search.value.toLowerCase())))
    const columnVisibility = ref({ serial: true, name: true, uacs: true, mfo_pap: true, is_sss: true, is_philhealth: true, is_pagibig: true, is_bank: true, active: true, actions: true })

    return {
        deductions, loading, saving, apiError,
        fetchDeductions,
        formVisible, formLoading, formData,
        openForm, addRow, removeRow, saveDeductions, deleteDeduction,
        search, filteredDeductions, columnVisibility
    }
}


