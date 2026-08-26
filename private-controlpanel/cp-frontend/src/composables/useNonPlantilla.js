import { ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import apiService from '../Services/api.js'

export function useNonPlantilla() {
    const items = ref([])
    const loading = ref(false)
    const formLoading = ref(false)
    const formOptions = ref({ positions: [], departments: [], employee_types: [] })

    async function fetchList() {
        try {
            loading.value = true
            const res = await apiService.getNonPlantillaSetup()
            if (res.success) {
                items.value = res.data || []
            } else {
                ElMessage.error(res.message || 'Failed to load non-plantillas')
            }
        } catch (e) {
            console.error('load non-plantillas', e)
            ElMessage.error('Failed to load non-plantillas')
        } finally {
            loading.value = false
        }
    }

    async function fetchFormData(id = 0) {
        try {
            const res = await apiService.getNonPlantillaFormData(id)
            if (res.success) {
                formOptions.value = {
                    positions: res.data.positions || [],
                    departments: res.data.departments || [],
                    employee_types: res.data.employee_types || []
                }
                return { success: true, data: res.data }
            }
            ElMessage.error(res.message || 'Failed to load form')
            return { success: false }
        } catch (e) {
            console.error('load form non-plantilla', e)
            ElMessage.error('Failed to load form')
            return { success: false }
        }
    }

    async function save(payload, id = 0) {
        try {
            formLoading.value = true
            const toInt = v => v === '' || v === null || v === undefined ? null : parseInt(v)
            const body = {
                position_id: toInt(payload.position_id),
                department_id: toInt(payload.department_id),
                salary: Number(payload.salary || 0),
                vacant: Number(payload.vacant || 0),
                publication_from: payload.publication_from || '',
                publication_to: payload.publication_to || '',
                description: payload.description || '',
                qualification: payload.qualification || '',
                eligibility: payload.eligibility || '',
                education: payload.education || '',
                experience: payload.experience || '',
                training: payload.training || '',
                employee_type_id: toInt(payload.employee_type_id),
                number_of_months: Number(payload.number_of_months || 0),
                active: payload.active === true
            }
            const res = await apiService.saveNonPlantillaSetup(body, id || 0)
            if (res.success) {
                ElMessage.success(res.message || 'Saved successfully')
                await fetchList()
                return { success: true }
            }
            const msg = res.errors ? Object.values(res.errors)[0][0] : res.message
            ElMessage.error(msg || 'Failed to save')
            return { success: false, errors: res.errors }
        } catch (e) {
            console.error('save non-plantilla', e)
            ElMessage.error('Failed to save')
            return { success: false }
        } finally {
            formLoading.value = false
        }
    }

    async function remove(id) {
        try {
            await ElMessageBox.confirm('Delete this record?', 'Confirm', { type: 'warning' })
            const res = await apiService.deleteNonPlantilla(id)
            if (res.success) {
                ElMessage.success(res.message || 'Deleted')
                await fetchList()
                return { success: true }
            }
            ElMessage.error(res.message || 'Failed to delete')
            return { success: false }
        } catch (e) {
            if (e !== 'cancel') {
                console.error('delete non-plantilla', e)
                ElMessage.error('Failed to delete')
            }
            return { success: false }
        }
    }

    return { items, loading, formLoading, formOptions, fetchList, fetchFormData, save, remove }
}


