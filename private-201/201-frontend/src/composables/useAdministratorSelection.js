import { ref } from 'vue'
import { adminSelectApi } from '@/services/api'

export function useAdministratorSelection() {
    const loading = ref(false)
    const error = ref(null)

    const positions = ref([])
    const applicants = ref([])
    const applicantPositions = ref([])
    const biDocuments = ref([])
    const selectedPositionId = ref(0)
    const selectedPositionType = ref('plantilla')

    const fetchPositions = async () => {
        loading.value = true
        error.value = null
        try {
            const { data } = await adminSelectApi.list()
            positions.value = data?.data || []
        } catch (e) {
            error.value = e
            console.error('Error fetching positions:', e)
        } finally {
            loading.value = false
        }
    }

    const fetchApplicants = async (positionId, type = 'plantilla') => {
        loading.value = true
        error.value = null
        try {
            selectedPositionId.value = positionId
            selectedPositionType.value = type === 'non_plantilla' ? 'non_plantilla' : 'plantilla'
            const { data } = await adminSelectApi.listbyPosition(positionId, selectedPositionType.value)
            const payload = data?.data || {}
            applicants.value = payload.interviews || []
            applicantPositions.value = payload.applicant_positions || []
            biDocuments.value = payload.bi_documents || []
        } catch (e) {
            error.value = e
            console.error('Error fetching applicants:', e)
        } finally {
            loading.value = false
        }
    }

    const refetchApplicants = async () => {
        if (!selectedPositionId.value) return
        await fetchApplicants(selectedPositionId.value, selectedPositionType.value)
    }

    const appointApplicant = async (applicantId) => {
        loading.value = true
        error.value = null
        try {
            await adminSelectApi.appoint(applicantId, {
                positionAppliedId: selectedPositionId.value,
                type: selectedPositionType.value === 'non_plantilla' ? 'non_plantilla' : undefined,
            })
        } catch (e) {
            error.value = e
            throw e
        } finally {
            loading.value = false
        }
    }

    return {
        loading,
        error,
        positions,
        applicants,
        applicantPositions,
        biDocuments,
        selectedPositionId,
        selectedPositionType,
        fetchPositions,
        fetchApplicants,
        refetchApplicants,
        appointApplicant
    }
}
