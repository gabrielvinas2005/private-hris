import { ref, computed } from 'vue'
import api from '../Services/api.js'

export function useRataPositions() {
    const loading = ref(false)
    const search = ref('')
    const records = ref([])

    // column visibility
    const columnVisibility = ref({
        serial: true,
        code: true,
        position: true,
        ra_amount: true,
        ta_amount: true,
        actions: true
    })

    const visibilityKeys = computed(() => Object.keys(columnVisibility.value))

    const filtered = computed(() => {
        const term = search.value.toLowerCase()
        if (!term) return records.value
        return records.value.filter(r =>
            (r.code || '').toLowerCase().includes(term) ||
            (r.position || '').toLowerCase().includes(term)
        )
    })

    async function fetchRecords() {
        loading.value = true
        const res = await api.getRataPositions()
        if (res && res.success !== false) {
            // backend returns array of {id, plantilla_id, code, position, ra_amount, ta_amount}
            records.value = Array.isArray(res.data) ? res.data : res
        } else {
            // fallback sample
            records.value = []
        }
        loading.value = false
    }

    // modal/form
    const showForm = ref(false)
    const formData = ref({ rows: [] })

    function openForm() {
        // initialize with current rows for editing; allow inline update of amounts
        formData.value = {
            rows: records.value.map(r => ({
                plantilla_id: r.plantilla_id,
                code: r.code,
                position: r.position,
                ra_amount: r.ra_amount ?? 0,
                ta_amount: r.ta_amount ?? 0
            }))
        }
        showForm.value = true
    }

    async function save() {
        // Backend expects parallel arrays by index: plantilla_id[], ra_amount[], ta_amount[]
        const payload = { plantilla_id: [], ra_amount: [], ta_amount: [] }
        for (const row of formData.value.rows) {
            if (row && row.plantilla_id) {
                payload.plantilla_id.push(row.plantilla_id)
                payload.ra_amount.push(Number(row.ra_amount || 0))
                payload.ta_amount.push(Number(row.ta_amount || 0))
            }
        }
        const res = await api.saveRataPositions(payload)
        if (res && res.success !== false) {
            showForm.value = false
            await fetchRecords()
        }
        return res
    }

    return {
        loading,
        search,
        records,
        columnVisibility,
        visibilityKeys,
        filtered,
        showForm,
        formData,
        openForm,
        save,
        fetchRecords
    }
}


