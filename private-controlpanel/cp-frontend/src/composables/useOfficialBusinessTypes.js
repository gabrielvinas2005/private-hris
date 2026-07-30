import { ref, computed } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import apiService from '../Services/api.js'

export function useOfficialBusinessTypes() {
    const rows = ref([])
    const loading = ref(false)
    const saving = ref(false)

    const total = computed(() => rows.value.length)

    async function fetchItems() {
        try {
            loading.value = true
            const res = await apiService.getOfficialBusinessTypes()
            if (res.success) {
                rows.value = (res.data || []).map(item => ({
                    id: item.id ?? null,
                    name: item.name ?? '',
                    active: item.active === true || item.active === 1 || item.active === '1'
                }))
                if (rows.value.length === 0) addBlankRow()
            } else {
                ElMessage.error(res.message || 'Failed to load official business types')
            }
        } catch (e) {
            ElMessage.error('Failed to load official business types')
        } finally {
            loading.value = false
        }
    }

    function addBlankRow() {
        rows.value.push({ id: null, name: '', active: false })
    }

    function buildPayload(list) {
        const payload = { id: [], name: [], active: {} }
        list.forEach(r => {
            const rowId = r.id ?? 0
            payload.id.push(rowId)
            payload.name.push(r.name ?? '')
            if (r.active) payload.active[rowId] = 1
        })
        return payload
    }

    async function saveAll() {
        try {
            saving.value = true
            const res = await apiService.saveOfficialBusinessTypes(buildPayload(rows.value))
            if (res.success) {
                ElMessage.success(res.message || 'Official business types saved')
                await fetchItems()
                return { success: true }
            }
            ElMessage.error(res.message || 'Failed to save official business types')
            return { success: false, errors: res.errors }
        } catch (e) {
            ElMessage.error('Failed to save official business types')
            return { success: false }
        } finally {
            saving.value = false
        }
    }

    async function deleteOne(id) {
        try {
            await ElMessageBox.confirm('Delete this official business type?', 'Confirm Delete', { type: 'warning' })
            const res = await apiService.deleteOfficialBusinessType(id)
            if (res.success) {
                ElMessage.success(res.message || 'Deleted')
                await fetchItems()
                return { success: true }
            }
            ElMessage.error(res.message || 'Failed to delete')
            return { success: false }
        } catch (e) {
            if (e === 'cancel') return { success: false, cancelled: true }
            ElMessage.error('Failed to delete')
            return { success: false }
        }
    }

    return { rows, loading, saving, total, fetchItems, addBlankRow, saveAll, deleteOne }
}


