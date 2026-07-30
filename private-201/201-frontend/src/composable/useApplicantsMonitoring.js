import { ref } from 'vue'
import { ElMessage } from 'element-plus'
import { applicantsRecordsApi } from '../services/api.js'

export function useApplicantsMonitoring() {
  // Start true so the list does not flash "No Data" before the first fetch (and before fetchStatuses finishes).
  const loading = ref(true)
  const applicants = ref([])
  const statuses = ref([])

  const fetchAll = async () => {
    try {
      loading.value = true
      const { data } = await applicantsRecordsApi.index()
      applicants.value = data?.applicants || data?.data?.applicants || []
    } catch (err) {
      console.error(err)
      ElMessage.error('Failed to load applicants monitoring')
    } finally {
      loading.value = false
    }
  }

  const fetchStatuses = async () => {
    try {
      const { data } = await applicantsRecordsApi.applicationStatuses()
      statuses.value = data?.statuses || data?.data?.statuses || []
    } catch (err) {
      console.error(err)
      ElMessage.error('Failed to load application statuses')
    }
  }

  const updateStatus = async (applicantId, positionAppliedId, applicationStatusId) => {
    try {
      loading.value = true
      await applicantsRecordsApi.updateStatus({
        applicant_id: applicantId,
        position_applied_id: positionAppliedId,
        application_status_id: applicationStatusId,
      })
      ElMessage.success('Status updated')
      await fetchAll()
    } catch (err) {
      console.error(err)
      ElMessage.error('Failed to update status')
    } finally {
      loading.value = false
    }
  }

  const fetchProgress = async (applicantId, positionAppliedId = null) => {
    try {
      const { data } = await applicantsRecordsApi.progress(applicantId, positionAppliedId)
      return data?.steps || data?.data?.steps || []
    } catch (err) {
      console.error(err)
      ElMessage.error('Failed to load applicant progress')
      return []
    }
  }

  return { loading, applicants, statuses, fetchAll, fetchStatuses, updateStatus, fetchProgress }
}

