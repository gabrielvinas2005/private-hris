import { ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import apiService from '../Services/api.js'

export function usePromotionTypes() {
    const items = ref([])
    const loading = ref(false)
    const saving = ref(false)

    async function fetchList() {
        try {
            loading.value = true
            const res = await apiService.getPromotionTypes()
            if (res.success) {
                items.value = (res.data || []).map(r => ({
                    ...r,
                    active: r.active === 1 || r.active === '1' || r.active === true
                }))
            } else {
                ElMessage.error(res.message || 'Failed to load promotion types')
            }
        } catch (e) {
            console.error('load promotion types', e)
            ElMessage.error('Failed to load promotion types')
        } finally {
            loading.value = false
        }
    }

    async function saveBulk() {
        try {
            saving.value = true
            // API expects arrays: name[], id[] and active[ID]
            const payload = { name: [], id: [], active: {} }
            items.value.forEach(row => {
                payload.name.push(row.name || '')
                payload.id.push(row.id || 0)
                if (row.active) payload.active[row.id || 0] = true
            })
            const res = await apiService.savePromotionTypes(payload)
            if (res.success) {
                ElMessage.success(res.message || 'Saved successfully')
                await fetchList()
                return { success: true }
            }
            const msg = res.errors ? Object.values(res.errors)[0][0] : res.message
            ElMessage.error(msg || 'Failed to save')
            return { success: false }
        } catch (e) {
            console.error('save promotion types', e)
            ElMessage.error('Failed to save')
            return { success: false }
        } finally {
            saving.value = false
        }
    }

    async function remove(id) {
        try {
            await ElMessageBox.confirm('Delete this promotion type?', 'Confirm', { type: 'warning' })
            const res = await apiService.deletePromotionType(id)
            if (res.success) {
                ElMessage.success(res.message || 'Deleted')
                await fetchList()
                return { success: true }
            }
            ElMessage.error(res.message || 'Failed to delete')
            return { success: false }
        } catch (e) {
            if (e !== 'cancel') {
                console.error('delete promotion type', e)
                ElMessage.error('Failed to delete')
            }
            return { success: false }
        }
    }

    function addRow() {
        items.value.push({ id: 0, name: '', active: true })
    }

    return { items, loading, saving, fetchList, saveBulk, remove, addRow }
}


