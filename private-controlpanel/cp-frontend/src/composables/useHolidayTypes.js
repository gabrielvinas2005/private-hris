import { ref, computed } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import apiService from '../Services/api.js'

export function useHolidayTypes() {
    const rows = ref([])
    const loading = ref(false)
    const saving = ref(false)

    const total = computed(() => rows.value.length)

    async function fetchHolidayTypes() {
        try {
            loading.value = true
            const res = await apiService.getHolidayTypes()
            if (res.success) {
                rows.value = (res.data || []).map(item => ({
                    id: item.id ?? null,
                    name: item.name ?? '',
                    rate: item.rate ?? 0,
                    active: item.active === true || item.active === 1 || item.active === '1',
                    absent_with_pay: item.absent_with_pay === true || item.absent_with_pay === 1 || item.absent_with_pay === '1'
                }))
                if (rows.value.length === 0) addBlankRow()
            } else {
                ElMessage.error(res.message || 'Failed to load holiday types')
            }
        } catch (e) {
            ElMessage.error('Failed to load holiday types')
        } finally {
            loading.value = false
        }
    }

    function addBlankRow() {
        rows.value.push({ id: null, name: '', rate: 0, active: false, absent_with_pay: false })
    }

    function buildPayload(list) {
        const payload = { id: [], name: [], rate: [], active: [], absent_with_pay: [] }
        list.forEach((r, i) => {
            payload.id.push(r.id ?? 0)
            payload.name.push(r.name ?? '')
            payload.rate.push(r.rate ?? 0)
            // Controller expects arrays of ids in active/absent_with_pay
            if (r.active) payload.active.push(r.id ?? 0)
            if (r.absent_with_pay) payload.absent_with_pay.push(r.id ?? 0)
        })
        return payload
    }

    async function saveAll() {
        try {
            saving.value = true
            const res = await apiService.saveHolidayTypes(buildPayload(rows.value))
            if (res.success) {
                ElMessage.success(res.message || 'Holiday types saved')
                await fetchHolidayTypes()
                return { success: true }
            }
            ElMessage.error(res.message || 'Failed to save holiday types')
            return { success: false, errors: res.errors }
        } catch (e) {
            ElMessage.error('Failed to save holiday types')
            return { success: false }
        } finally {
            saving.value = false
        }
    }

    async function deleteOne(id) {
        try {
            await ElMessageBox.confirm('Delete this holiday type?', 'Confirm Delete', { type: 'warning' })
            const res = await apiService.deleteHolidayType(id)
            if (res.success) {
                ElMessage.success(res.message || 'Deleted')
                await fetchHolidayTypes()
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
        fetchHolidayTypes,
        addBlankRow,
        saveAll,
        deleteOne
    }
}


