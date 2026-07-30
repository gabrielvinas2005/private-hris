import { ref } from 'vue'
import ApiService from '../Services/api'

export function useDocumentTypes() {
    const items = ref([])
    const loading = ref(false)
    const saving = ref(false)

    async function fetchList() {
        loading.value = true
        try {
            const res = await ApiService.get('/document-types')
            const list = Array.isArray(res.data) ? res.data : []
            items.value = list.map(r => ({ id: r.id ? parseInt(r.id) : 0, name: r.name || '', active: r.active === 1 || r.active === true || r.active === '1' }))
        } finally {
            loading.value = false
        }
    }

    function addRow() {
        items.value = [...items.value, { id: 0, name: '', active: true }]
    }

    async function remove(row) {
        if (!row.id) {
            items.value = items.value.filter(r => r !== row)
            return { success: true }
        }
        const res = await ApiService.delete(`/document-types/${row.id}`)
        await fetchList()
        return res
    }

    async function saveBulk() {
        saving.value = true
        try {
            const rows = (items.value || []).map(r => ({ id: r.id ?? 0, name: r.name ?? '', active: !!r.active }))
            const ids = rows.map(r => r.id)
            const names = rows.map(r => r.name)
            const payload = {
                id: [0, ...ids],
                name: ['', ...names],
                active: rows.reduce((acc, r) => { if (r.active) acc[r.id ?? 0] = 1; return acc }, {})
            }
            return await ApiService.post('/document-types', payload)
        } finally {
            saving.value = false
        }
    }

    return { items, loading, saving, fetchList, addRow, remove, saveBulk }
}


