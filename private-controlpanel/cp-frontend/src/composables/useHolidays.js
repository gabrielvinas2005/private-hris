import { ref, computed } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import apiService from '../Services/api.js'

export function useHolidays() {
    const rows = ref([])
    const loading = ref(false)
    const saving = ref(false)
    const formData = ref({ holiday_types: [], branches: [] })

    const total = computed(() => rows.value.length)

    async function fetchHolidays() {
        try {
            loading.value = true
            const res = await apiService.getHolidays()
            if (res.success) {
                const data = res.data || {}
                rows.value = (data.holidays || []).map(item => ({
                    id: item.id ?? null,
                    name: item.name ?? '',
                    holiday_type: item.holiday_type ?? 0,
                    branch_id: item.branch ?? 0,
                    date: item.date ?? null,
                    active: item.active === true || item.active === 1 || item.active === '1'
                }))
                formData.value.holiday_types = data.holiday_types || []
                formData.value.branches = data.branches || []
                if (rows.value.length === 0) addBlankRow()
            } else {
                ElMessage.error(res.message || 'Failed to load holidays')
            }
        } catch (e) {
            ElMessage.error('Failed to load holidays')
        } finally {
            loading.value = false
        }
    }

    function addBlankRow() {
        rows.value.push({ id: null, name: '', holiday_type: 0, branch_id: 0, date: null, active: false })
    }

    function buildPayload(list) {
        const payload = { id: [], name: [], holiday_type: [], branch_id: [], date: [], active: [] }
        list.forEach(r => {
            payload.id.push(r.id ?? 0)
            payload.name.push(r.name ?? '')
            payload.holiday_type.push(r.holiday_type ?? 0)
            payload.branch_id.push(r.branch_id ?? 0)
            payload.date.push(r.date ?? null)
            if (r.active) payload.active.push(r.id ?? 0)
        })
        return payload
    }

    async function saveAll() {
        try {
            saving.value = true
            const res = await apiService.saveHolidays(buildPayload(rows.value))
            if (res.success) {
                ElMessage.success(res.message || 'Holidays saved')
                await fetchHolidays()
                return { success: true }
            }
            ElMessage.error(res.message || 'Failed to save holidays')
            return { success: false, errors: res.errors }
        } catch (e) {
            ElMessage.error('Failed to save holidays')
            return { success: false }
        } finally {
            saving.value = false
        }
    }

    async function deleteOne(id) {
        try {
            await ElMessageBox.confirm('Delete this holiday?', 'Confirm Delete', { type: 'warning' })
            const res = await apiService.deleteHoliday(id)
            if (res.success) {
                ElMessage.success(res.message || 'Deleted')
                await fetchHolidays()
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

    return { rows, loading, saving, formData, total, fetchHolidays, addBlankRow, saveAll, deleteOne }
}


