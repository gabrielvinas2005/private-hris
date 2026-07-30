import { ref } from 'vue'
import { ElMessage } from 'element-plus'
import { assignmentApi } from '@/services/api'

export function useAssignment() {
    const loading = ref(false)
    const assignments = ref([])
    const formData = ref(null)

    const fetchAssignments = async () => {
        try {
            loading.value = true
            const res = await assignmentApi.getAssignments()
            assignments.value = res.data.data || []
        } catch (e) {
            ElMessage.error('Failed to load assignments')
            throw e
        } finally {
            loading.value = false
        }
    }

    const fetchForm = async (id = 0) => {
        try {
            loading.value = true
            const res = await assignmentApi.getForm(id)
            formData.value = res.data.data || res.data || {}
            return formData.value
        } catch (e) {
            ElMessage.error('Failed to load form data: ' + (e.response?.data?.message || e.message))
            throw e
        } finally {
            loading.value = false
        }
    }

    




    const saveAssignment = async (id, payload) => {
        try {
            loading.value = true
            const res = await assignmentApi.save(id, payload)
            ElMessage.success(res.data.message || 'Saved')
            return res.data.data
        } catch (e) {
            const msg = e.response?.data?.message || 'Save failed'
            ElMessage.error(msg)
            throw e
        } finally {
            loading.value = false
        }
    }

    const showAssignment = async (id) => {
        try {
            loading.value = true
            const res = await assignmentApi.show(id)

            // The backend returns { success: true, data: { promotion: {...}, summary: {...} } }
            const result = res.data?.data || res.data || null
            return result
        } catch (e) {
            ElMessage.error('Failed to fetch assignment: ' + (e.response?.data?.message || e.message))
            throw e
        } finally {
            loading.value = false
        }
    }

    return { loading, assignments, formData, fetchAssignments, fetchForm, saveAssignment, showAssignment }
}


