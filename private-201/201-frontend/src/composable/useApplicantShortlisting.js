import { ref } from 'vue'
import { ElMessage } from 'element-plus'
import { applicantShortlistingApi } from '../services/api.js'

export function useApplicantShortlisting() {
    const loading = ref(false)
    const processing = ref(false)
    /** Stable key for the shortlisted row whose Proceed request is in flight (matches rowListKey in UI). */
    const proceedingKey = ref(null)
    const applicants = ref([])
    const shortlisted = ref([])

    const fetchAll = async () => {
        try {
            loading.value = true
            const { data } = await applicantShortlistingApi.index()
            // Expect { applicants: [...], shortlisted: [...] }
            applicants.value = data?.applicants || data?.data?.applicants || []
            shortlisted.value = data?.shortlisted || data?.data?.shortlisted || []
        } catch (err) {
            console.error(err)
            ElMessage.error('Failed to load shortlisting data')
        } finally {
            loading.value = false
        }
    }

    const add = async (id, rating, statusId = 1, positionAppliedId = null) => {
        try {
            processing.value = true
            await applicantShortlistingApi.add(id, rating, statusId, positionAppliedId)
            ElMessage.success('Applicant shortlisted')
            await fetchAll()
        } catch (err) {
            console.error(err)
            ElMessage.error('Failed to shortlist applicant')
        } finally {
            processing.value = false
        }
    }

    const remove = async (shortlistedId) => {
        try {
            processing.value = true
            await applicantShortlistingApi.remove(shortlistedId)
            ElMessage.success('Removed from shortlist')
            await fetchAll()
        } catch (err) {
            console.error(err)
            ElMessage.error('Failed to remove shortlisted applicant')
        } finally {
            processing.value = false
        }
    }

    const proceedNextStep = async (applicantId, positionAppliedId = null) => {
        const key = `${applicantId}_${positionAppliedId ?? ''}`
        try {
            proceedingKey.value = key
            await applicantShortlistingApi.proceed(applicantId, positionAppliedId)
            ElMessage.success('Applicant moved to next step')
            await fetchAll()
        } catch (err) {
            console.error(err)
            ElMessage.error('Failed to proceed applicant to next step')
        } finally {
            proceedingKey.value = null
        }
    }

    return { loading, processing, proceedingKey, applicants, shortlisted, fetchAll, add, remove, proceedNextStep }
}
