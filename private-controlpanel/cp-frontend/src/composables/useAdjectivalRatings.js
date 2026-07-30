import { ref } from 'vue'
import ApiService from '../Services/api'

export function useAdjectivalRatings() {
    const items = ref([])
    const loading = ref(false)
    const saving = ref(false)

    async function fetchList() {
        loading.value = true
        try {
            const res = await ApiService.get('/ratings')
            const list = Array.isArray(res.data) ? res.data : []
            items.value = list.map(r => ({
                id: r.id ? parseInt(r.id) : null,
                adjectival_rating: r.adjectival_rating || '',
                numerical_rating1: r.numerical_rating1 ?? null,
                numerical_rating2: r.numerical_rating2 ?? null,
            }))
        } finally {
            loading.value = false
        }
    }

    function addRow() {
        items.value = [...items.value, { id: null, adjectival_rating: '', numerical_rating1: null, numerical_rating2: null }]
    }

    async function remove(row) {
        if (!row.id) {
            items.value = items.value.filter(r => r !== row)
            return { success: true }
        }
        const res = await ApiService.delete(`/ratings/${row.id}`)
        await fetchList()
        return res
    }

    async function saveBulk() {
        saving.value = true
        try {
            const payload = {
                id: items.value.map((r) => r.id ?? ''),
                adjectival_rating: items.value.map((r) => r.adjectival_rating ?? ''),
                numerical_rating1: items.value.map((r) => r.numerical_rating1 ?? ''),
                numerical_rating2: items.value.map((r) => r.numerical_rating2 ?? ''),
            }
            return await ApiService.post('/ratings', payload)
        } finally {
            saving.value = false
        }
    }

    return { items, loading, saving, fetchList, addRow, remove, saveBulk }
}


