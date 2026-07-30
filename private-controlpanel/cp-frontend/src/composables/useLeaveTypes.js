import { ref, computed } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import apiService from '../Services/api.js'

export function useLeaveTypes() {
    const rows = ref([])
    const loading = ref(false)
    const saving = ref(false)

    const total = computed(() => rows.value.length)

    async function fetchLeaveTypes() {
        try {
            loading.value = true
            const res = await apiService.getLeaveTypes()
            if (res.success) {
                const toBool = (v) => {
                    if (typeof v === 'boolean') return v
                    if (typeof v === 'number') return v > 0
                    if (typeof v === 'string') {
                        const t = v.trim().toLowerCase()
                        if (t === 'yes' || t === 'true') return true
                        const n = parseInt(v, 10)
                        if (!isNaN(n)) return n > 0
                        return false
                    }
                    return !!v
                }
                rows.value = (res.data || []).map(item => ({
                    id: item.id ?? null,
                    name: item.name ?? '',
                    // Backend seems to use 1/2 lookup (1=No, 2=Yes). Normalize to 0/1 for UI.
                    accrued_id: (parseInt(item.accrued_id ?? 0) === 2) ? 1 : 0,
                    accrual_amount: item.accrual_amount ?? 0,
                    accrual_frequency_id: parseInt(item.accrual_frequency_id ?? 0),
                    leave_balance_policy_id: parseInt(item.leave_balance_policy_id ?? 0),
                    is_editable_id: parseInt(item.is_editable_id ?? 0),
                    // 1=No, 2=Yes
                    is_editable: (parseInt(item.is_editable_id ?? 0) === 2),
                    service_credit: toBool(item.service_credit),
                    is_el: toBool(item.is_el),
                    active: toBool(item.active)
                }))
                if (rows.value.length === 0) addBlankRow()
            } else {
                ElMessage.error(res.message || 'Failed to load leave types')
            }
        } catch (e) {
            ElMessage.error('Failed to load leave types')
        } finally {
            loading.value = false
        }
    }

    function addBlankRow() {
        rows.value.push({
            id: null,
            name: '',
            accrued_id: 0,
            accrual_amount: 0,
            accrual_frequency_id: 0,
            leave_balance_policy_id: 0,
            is_editable_id: 0,
            is_editable: false,
            service_credit: false,
            is_el: false,
            active: false
        })
    }

    function buildPayload(list) {
        const p = {
            id: [],
            name: [],
            accrued_id: [],
            accrual_amount: [],
            accrual_frequency_id: [],
            leave_balance_policy_id: [],
            is_editable_id: [],
            service_credit: [],
            is_el: [],
            active: {}
        }
        list.forEach(r => {
            const rowId = r.id ?? 0
            p.id.push(rowId)
            p.name.push(r.name ?? '')
            // Accrued: backend expects 1=No, 2=Yes
            p.accrued_id.push(r.accrued_id === 1 ? 2 : 1)
            p.accrual_amount.push(r.accrual_amount ?? 0)
            p.accrual_frequency_id.push(r.accrual_frequency_id ?? 0)
            p.leave_balance_policy_id.push(r.leave_balance_policy_id ?? 0)
            // Editable: backend expects lookup id (1=No, 2=Yes)
            p.is_editable_id.push(r.is_editable === true ? 2 : 1)
            if (r.service_credit) p.service_credit.push(rowId)
            if (r.is_el) p.is_el.push(rowId)
            if (r.active) p.active[rowId] = 1
        })
        return p
    }

    async function saveAll() {
        try {
            saving.value = true
            const res = await apiService.saveLeaveTypes(buildPayload(rows.value))
            if (res.success) {
                ElMessage.success(res.message || 'Leave types saved')
                await fetchLeaveTypes()
                return { success: true }
            }
            ElMessage.error(res.message || 'Failed to save leave types')
            return { success: false, errors: res.errors }
        } catch (e) {
            ElMessage.error('Failed to save leave types')
            return { success: false }
        } finally {
            saving.value = false
        }
    }

    async function deleteOne(id) {
        try {
            await ElMessageBox.confirm('Delete this leave type?', 'Confirm Delete', { type: 'warning' })
            const res = await apiService.deleteLeaveType(id)
            if (res.success) {
                ElMessage.success(res.message || 'Deleted')
                await fetchLeaveTypes()
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

    return { rows, loading, saving, total, fetchLeaveTypes, addBlankRow, saveAll, deleteOne }
}


