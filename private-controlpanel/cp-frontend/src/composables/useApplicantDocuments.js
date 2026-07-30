import { ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import ApiService from '../Services/api'

export function useApplicantDocuments() {
    const items = ref([])
    const loading = ref(false)
    const saving = ref(false)

    async function fetchList() {
        loading.value = true
        try {
            const res = await ApiService.getApplicantDocuments()
            if (res && res.success) {
                const data = res.data || []
                items.value = data.map(d => ({
                    ...d,
                    // normalize active from bit/int/string to real boolean
                    active: d.active === true || d.active === 1 || d.active === '1'
                }))
            } else {
                ElMessage.error(res?.message || 'Failed to load applicant documents')
            }
        } catch (error) {
            console.error('Error loading applicant documents:', error)
            ElMessage.error('Failed to load applicant documents')
        } finally {
            loading.value = false
        }
    }

    async function save(item) {
        saving.value = true
        try {
            const payload = {
                name: item.name,
                active: !!item.active
            }
            let res
            if (item.id) {
                res = await ApiService.updateApplicantDocument(item.id, payload)
            } else {
                res = await ApiService.saveApplicantDocument(payload)
            }
            if (res && res.success) {
                ElMessage.success(res.message || 'Saved successfully')
                await fetchList()
                return true
            }
            ElMessage.error(res?.message || 'Failed to save')
            return false
        } catch (error) {
            console.error('Error saving applicant document:', error)
            ElMessage.error('Failed to save applicant document')
            return false
        } finally {
            saving.value = false
        }
    }

    async function remove(id) {
        try {
            await ElMessageBox.confirm('Delete this document?', 'Confirm', { type: 'warning' })
            const res = await ApiService.deleteApplicantDocument(id)
            if (res && res.success) {
                ElMessage.success(res.message || 'Deleted successfully')
                await fetchList()
                return true
            }
            ElMessage.error(res?.message || 'Failed to delete')
            return false
        } catch (error) {
            if (error !== 'cancel') {
                console.error('Error deleting applicant document:', error)
                ElMessage.error('Failed to delete applicant document')
            }
            return false
        }
    }

    return {
        items,
        loading,
        saving,
        fetchList,
        save,
        remove
    }
}

