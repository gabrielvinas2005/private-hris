import { ref, computed } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import apiService from '../Services/api.js'

export function useOvertimeTypes() {
    const rows = ref([])
    const loading = ref(false)
    const saving = ref(false)

    const total = computed(() => rows.value.length)

    async function fetchOvertimeTypes() {
        try {
            loading.value = true
            const res = await apiService.getOvertimeTypes()
            if (res.success) {
                rows.value = (res.data || []).map(item => ({
                    id: item.id ?? null,
                    name: item.name ?? '',
                    rate: item.rate ?? '',
                    // nd_from/nd_to removed per request
                    // nd_rating removed per request
                    min_ot: item.min_ot ?? '',
                    max_ot: item.max_ot ?? '',
                    active: item.active === true || item.active === 1 || item.active === '1'
                }))
                if (rows.value.length === 0) addBlankRow()
            } else {
                ElMessage.error(res.message || 'Failed to load overtime types')
            }
        } catch (e) {
            ElMessage.error('Failed to load overtime types')
        } finally {
            loading.value = false
        }
    }

    function addBlankRow() {
        rows.value.push({
            id: null,
            name: '',
            rate: '',
            // nd_from/nd_to removed
            // nd_rating removed
            min_ot: '',
            max_ot: '',
            active: false
        })
    }

    async function saveAll() {
        try {
            saving.value = true
            // Backend expects arrays keyed by field name
            const payload = buildPayload(rows.value)
            const res = await apiService.saveOvertimeTypes(payload)
            if (res.success) {
                ElMessage.success(res.message || 'Overtime types saved')
                await fetchOvertimeTypes()
                return { success: true }
            }
            ElMessage.error(res.message || 'Failed to save overtime types')
            return { success: false, errors: res.errors }
        } catch (e) {
            ElMessage.error('Failed to save overtime types')
            return { success: false }
        } finally {
            saving.value = false
        }
    }

    function buildPayload(list) {
        const payload = {
            id: [],
            name: [],
            rate: [],
            nd_from: [],
            nd_to: [],
            nd_rating: [],
            min_ot: [],
            max_ot: [],
            active: {}
        }
        list.forEach((row, idx) => {
            payload.id.push(row.id ?? 0)
            payload.name.push(row.name ?? '')
            payload.rate.push(row.rate ?? '')
            // keep array structure aligned with backend; send empty strings
            payload.nd_from.push('')
            payload.nd_to.push('')
            payload.nd_rating.push('')
            payload.min_ot.push(row.min_ot ?? '')
            payload.max_ot.push(row.max_ot ?? '')
            if (row.active) {
                // Backend checks isset($data['active'][$id])
                const key = row.id ?? 0
                payload.active[key] = true
            }
        })
        return payload
    }

    async function deleteOne(id) {
        try {
            await ElMessageBox.confirm('Delete this overtime type?', 'Confirm Delete', { type: 'warning' })
            const res = await apiService.deleteOvertimeType(id)
            if (res.success) {
                ElMessage.success(res.message || 'Deleted')
                await fetchOvertimeTypes()
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

    return {
        rows,
        loading,
        saving,
        total,
        fetchOvertimeTypes,
        addBlankRow,
        saveAll,
        deleteOne
    }
}


