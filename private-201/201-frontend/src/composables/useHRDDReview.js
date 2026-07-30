import { ref, toRaw } from 'vue'
import { hrddApi } from '@/services/api'

export function useHRDDReview() {
    const loading = ref(false)
    const error = ref(null)

    const positions = ref([])
    const applicants = ref([])
    const applicantPositions = ref([])
    const biDocuments = ref([])

    const selectedPositionId = ref(0)
    /** 'plantilla' | 'non_plantilla' — must match selected row for API id disambiguation */
    const selectedPositionType = ref('plantilla')

    const fetchPositions = async () => {
        loading.value = true
        error.value = null
        try {
            const { data } = await hrddApi.positions()
            positions.value = data?.data || []
        } catch (e) {
            error.value = e
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
            const { data } = await hrddApi.list(positionId, selectedPositionType.value)
            const payload = data?.data || {}
            applicants.value = payload.interviews || []
            applicantPositions.value = payload.applicant_positions || []
            biDocuments.value = payload.bi_documents || []
        } catch (e) {
            error.value = e
            console.log(e)
        } finally {
            loading.value = false
        }
    }

    const refetchApplicants = async () => {
        if (!selectedPositionId.value) return
        await fetchApplicants(selectedPositionId.value, selectedPositionType.value)
    }

    const saveBatch = async (positionAppliedId, ids, acceptedIds, rejectedIds, ratings, attachmentsMap, biDocumentsMap = {}) => {
        const form = new FormData()
        
        // Debug logging
        console.log('saveBatch called with:', { positionAppliedId, ids, acceptedIds, rejectedIds, ratings, attachmentsMap, biDocumentsMap })
        
        if (!ids || !Array.isArray(ids)) {
            throw new Error('Invalid ids parameter')
        }
        if (!ratings || !Array.isArray(ratings)) {
            throw new Error('Invalid ratings parameter')
        }
        if (!positionAppliedId) {
            throw new Error('Invalid positionAppliedId parameter')
        }

        form.append('position_applied_id', positionAppliedId)
        form.append('is_plantilla', selectedPositionType.value === 'non_plantilla' ? '0' : '1')
        
        ids.forEach((id) => form.append('id[]', id))
        ratings.forEach((val, idx) => form.append('hrdd_rating[]', val ?? 0))
        
        // Handle attachmentsMap safely
        if (attachmentsMap && typeof attachmentsMap === 'object') {
            try {
                // Convert reactive object to plain object if needed
                const attachments = toRaw(attachmentsMap)
                Object.entries(attachments).forEach(([applicantId, files]) => {
                    if (files && Array.isArray(files)) {
                        files.forEach((f) => {
                            if (f instanceof File) {
                                form.append(`attachments[${applicantId}][]`, f)
                            }
                        })
                    }
                })
            } catch (error) {
                console.warn('Error processing attachments:', error)
            }
        }
        
        // Handle BI documents separately
        if (biDocumentsMap && typeof biDocumentsMap === 'object') {
            try {
                // Convert reactive object to plain object if needed
                const biDocs = toRaw(biDocumentsMap)
                Object.entries(biDocs).forEach(([applicantId, files]) => {
                    if (files && Array.isArray(files)) {
                        files.forEach((f) => {
                            if (f instanceof File) {
                                form.append(`bi_documents[${applicantId}][]`, f)
                            }
                        })
                    }
                })
            } catch (error) {
                console.warn('Error processing BI documents:', error)
            }
        }
        
        if (acceptedIds && acceptedIds.length) {
            acceptedIds.forEach((id) => form.append('select[]', id))
        }
        if (rejectedIds && rejectedIds.length) {
            rejectedIds.forEach((id) => form.append('reject[]', id))
        }
        loading.value = true
        error.value = null
        try {
            await hrddApi.saveBatch(form)
        } catch (e) {
            error.value = e
            throw e
        } finally {
            loading.value = false
        }
    }

    const forwardApplicant = async (applicantId, rating, biFiles = [], brFiles = []) => {
        const form = new FormData()
        form.append('hr_performance_rating', rating ?? 0)
            ; (biFiles || []).forEach((f) => form.append('bi_document[]', f))
            ; (brFiles || []).forEach((f) => form.append('board_resolution_document[]', f))
        loading.value = true
        error.value = null
        try {
            await hrddApi.submitForward(applicantId, form)
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
        saveBatch,
        forwardApplicant
    }
}


