import { ref, computed } from 'vue'
import api from '../Services/api.js'

export function useRataTable() {
    const loading = ref(false)
    const search = ref('')
    const rows = ref([])

    const columnVisibility = ref({
        serial: true,
        day_from: true,
        day_to: true,
        percentage: true,
        actions: true
    })

    const visibilityKeys = computed(() => Object.keys(columnVisibility.value))

    const filtered = computed(() => {
        const term = search.value.toLowerCase()
        if (!term) return rows.value
        return rows.value.filter(r =>
            String(r.day_from ?? '').toLowerCase().includes(term) ||
            String(r.day_to ?? '').toLowerCase().includes(term) ||
            String(r.percentage ?? '').toLowerCase().includes(term)
        )
    })

    async function fetchRows() {
        loading.value = true
        const res = await api.getRataTable()
        if (res && res.success !== false) {
            rows.value = Array.isArray(res.data) ? res.data : res
        } else {
            rows.value = []
        }
        loading.value = false
    }

    // modal
    const showForm = ref(false)
    const form = ref({ items: [] })

    function openForm() {
        // existing + one empty row
        const mapped = rows.value.map(r => ({ id: r.id ?? null, day_from: r.day_from ?? '', day_to: r.day_to ?? '', percentage: r.percentage ?? '' }))
        mapped.push({ id: null, day_from: '', day_to: '', percentage: '' })
        form.value = { items: mapped }
        showForm.value = true
    }

    function addRow() {
        form.value.items.push({ id: null, day_from: '', day_to: '', percentage: '' })
    }
    function removeRow(index) {
        form.value.items.splice(index, 1)
    }

    async function save() {
        // Backend expects arrays indexed from 1 with potential null id slots; it validates entries and allows insert/update.
        const id = [null]
        const day_from = [null]
        const day_to = [null]
        const percentage = [null]
        for (const item of form.value.items) {
            id.push(item.id ?? null)
            day_from.push(item.day_from === '' ? null : Number(item.day_from))
            day_to.push(item.day_to === '' ? null : Number(item.day_to))
            percentage.push(item.percentage === '' ? null : Number(item.percentage))
        }
        const payload = { id, day_from, day_to, percentage }
        const res = await api.saveRataTable(payload)
        if (res && res.success !== false) {
            showForm.value = false
            await fetchRows()
        }
        return res
    }

    async function deleteRow(rec) {
        if (!rec?.id) return { success: false, message: 'Missing id' }
        const res = await api.deleteRataTable(rec.id)
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


