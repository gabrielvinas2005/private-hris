import { ref, computed } from 'vue'
import api from '../Services/api.js'

export function useHazardPay() {
    const loading = ref(false)
    const search = ref('')
    const rows = ref([])

    const columnVisibility = ref({
        serial: true,
        salary_from: true,
        salary_to: true,
        percentage: true,
        actions: true
    })

    const visibilityKeys = computed(() => Object.keys(columnVisibility.value))

    const filtered = computed(() => {
        const term = search.value.toLowerCase()
        if (!term) return rows.value
        return rows.value.filter(r =>
            String(r.salary_from ?? '').toLowerCase().includes(term) ||
            String(r.salary_to ?? '').toLowerCase().includes(term) ||
            String(r.percentage ?? '').toLowerCase().includes(term)
        )
    })

    async function fetchRows() {
        loading.value = true
        const res = await api.getHazardTable()
        if (res && res.success !== false) {
            rows.value = Array.isArray(res.data) ? res.data : res
        } else {
            rows.value = []
        }
        loading.value = false
    }

    // Table setup add/edit modal
    const showForm = ref(false)
    const form = ref({ items: [] })

    function openForm() {
        const mapped = rows.value.map(r => ({ id: r.id ?? null, salary_from: r.salary_from ?? '', salary_to: r.salary_to ?? '', percentage: r.percentage ?? '' }))
        mapped.push({ id: null, salary_from: '', salary_to: '', percentage: '' })
        form.value = { items: mapped }
        showForm.value = true
    }

    function addRow() { form.value.items.push({ id: null, salary_from: '', salary_to: '', percentage: '' }) }
    function removeRow(i) { form.value.items.splice(i, 1) }

    async function save() {
        // Backend expects arrays with 1-based indexing
        const id = [null]
        const salary_from = [null]
        const salary_to = [null]
        const percentage = [null]
        for (const it of form.value.items) {
            id.push(it.id ?? null)
            salary_from.push(it.salary_from === '' ? null : Number(it.salary_from))
            salary_to.push(it.salary_to === '' ? null : Number(it.salary_to))
            percentage.push(it.percentage === '' ? null : Number(it.percentage))
        }
        const payload = { id, salary_from, salary_to, percentage }
        const res = await api.saveHazardTable(payload)
        if (res && res.success !== false) { showForm.value = false; await fetchRows() }
        return res
    }

    async function deleteRow(row) {
        if (!row?.id) return { success: false, message: 'Missing id' }
        const res = await api.deleteHazard(row.id)
        if (res && res.success !== false) await fetchRows()
        return res
    }

    return {
        loading, search, rows, filtered,
        columnVisibility, visibilityKeys,
        showForm, form, openForm, addRow, removeRow, save, deleteRow,
        fetchRows
    }
}


