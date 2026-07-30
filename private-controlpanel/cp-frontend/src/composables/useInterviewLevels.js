import { ref, computed } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import apiService from '../Services/api.js'

export function useInterviewLevels() {
  const interviewLevels = ref([])
  const loading = ref(false)
  const formLoading = ref(false)

  const totalInterviewLevels = computed(() => interviewLevels.value.length)

  async function fetchInterviewLevels() {
    try {
      loading.value = true
      const response = await apiService.getInterviewLevels()

      if (response.success) {
        interviewLevels.value = (response.data || []).map((lvl) => ({
          ...lvl,
          id: parseInt(lvl.id),
          active: lvl.active === '1' || lvl.active === 1 || lvl.active === true
        }))
      } else {
        ElMessage.error(response.message || 'Failed to fetch interview levels')
      }
    } catch (error) {
      console.error('Error fetching interview levels:', error)
      ElMessage.error('Failed to fetch interview levels')
    } finally {
      loading.value = false
    }
  }

  async function saveInterviewLevel(levelData) {
    try {
      formLoading.value = true

      const payload = {
        interview_level: levelData.interview_level,
        active: !!levelData.active
      }

      let response
      if (levelData.id) {
        response = await apiService.updateInterviewLevel(levelData.id, payload)
      } else {
        response = await apiService.saveInterviewLevel(payload)
      }

      if (response.success) {
        ElMessage.success(response.message || 'Interview level saved successfully')
        await fetchInterviewLevels()
        return { success: true, data: response.data }
      }

      ElMessage.error(response.message || 'Failed to save interview level')
      return { success: false, errors: response.errors }
    } catch (error) {
      console.error('Error saving interview level:', error)
      ElMessage.error('Failed to save interview level')
      return { success: false, error: error.message }
    } finally {
      formLoading.value = false
    }
  }

  async function deleteInterviewLevel(levelId) {
    try {
      await ElMessageBox.confirm(
        'Are you sure you want to delete this interview level? This action cannot be undone.',
        'Confirm Delete',
        { confirmButtonText: 'Delete', cancelButtonText: 'Cancel', type: 'warning' }
      )

      const response = await apiService.deleteInterviewLevel(levelId)

      if (response.success) {
        ElMessage.success(response.message || 'Interview level deleted successfully')
        await fetchInterviewLevels()
        return { success: true }
      }

      ElMessage.error(response.message || 'Failed to delete interview level')
      return { success: false }
    } catch (error) {
      if (error === 'cancel') return { success: false, cancelled: true }
      console.error('Error deleting interview level:', error)
      ElMessage.error('Failed to delete interview level')
      return { success: false, error: error.message }
    }
  }

  async function getInterviewLevelForEdit(levelId) {
    try {
      const response = await apiService.getInterviewLevelForEdit(levelId)
      if (response.success) {
        return { success: true, level: response.data }
      }
      ElMessage.error(response.message || 'Failed to fetch interview level data')
      return { success: false }
    } catch (error) {
      console.error('Error fetching interview level for edit:', error)
      ElMessage.error('Failed to fetch interview level data')
      return { success: false, error: error.message }
    }
  }

  return {
    interviewLevels,
    loading,
    formLoading,
    totalInterviewLevels,
    fetchInterviewLevels,
    saveInterviewLevel,
    deleteInterviewLevel,
    getInterviewLevelForEdit
  }
}

