import { ref, reactive } from 'vue'
import { ElMessage } from 'element-plus'
import { applicantQualificationApi, applicantRecordsApi } from '../services/api.js'

export function useApplicantQualification() {
    const loading = ref(false)
    const applicantsLoading = ref(false)
    const saving = ref(false)

    const plantillaPositions = ref([])
    const nonPlantillaPositions = ref([])

    const selectedPosition = reactive({
        id: null,
        name: '',
        type: 1,
        code: '',
        unit: '',
        grade: '',
        step: '',
        department: '',
        publication_from: '',
        publication_to: '',
        education: '',
        experience: '',
        training: '',
        eligibility: '',
        program: '',
        // enriched arrays from backend
        educations: [],
        employments: [],
        examinations: [],
        trainings_arr: [],
        competencies_arr: [],
        qualification_text: ''
    })
    const applicants = ref([])

    const showApplicants = ref(false)
    const showApplicantInfo = ref(false)

    const applicantInfo = ref(null)

    // Rating form
    const ratingForm = reactive({
        applicant_id: null,
        education_rating: null,
        experience_rating: null,
        training_rating: null,
        eligibility_rating: null,
        reviewed_status_id: null, // 2 = Approved/Qualified, others per backend
        is_education_passed: null,
        is_experience_passed: null,
        is_training_passed: null,
        is_eligibility_passed: null,
        interview: null,
        bonus: null
    })

    const fetchPositions = async () => {
        try {
            loading.value = true
            const { data } = await applicantQualificationApi.getPositions()
            // success wrapper → data: { data: [...], non_plantillas: [...] }
            plantillaPositions.value = data?.data?.data || data?.data || []
            nonPlantillaPositions.value = data?.data?.non_plantillas || data?.non_plantillas || []
        } catch (err) {
            console.error(err)
            ElMessage.error('Failed to load positions')
        } finally {
            loading.value = false
        }
    }

    const openApplicants = async (position, type) => {
        // Prefer plantilla/non-plantilla primary key based on what the API returns
        selectedPosition.id = position.id || position.plantilla_id || position.position_id
        selectedPosition.name = position.position || position.name || position.code || ''
        selectedPosition.type = type
        selectedPosition.code = position.code || ''
        selectedPosition.unit = position.unit || ''
        selectedPosition.grade = position.grade || position.salary_grade || ''
        selectedPosition.step = position.step || position.salary_step || ''
        selectedPosition.department = position.department || ''
        selectedPosition.publication_from = position.publication_from || ''
        selectedPosition.publication_to = position.publication_to || ''
        selectedPosition.education = position.education || ''
        selectedPosition.experience = position.experience || ''
        selectedPosition.training = position.training || ''
        selectedPosition.eligibility = position.eligibility || ''
        selectedPosition.program = position.program || position.description || position.qualification || ''
        
        await loadApplicants()
        showApplicants.value = true
    }

    const loadApplicants = async () => {
        if (!selectedPosition.id) return
        try {
            applicantsLoading.value = true
            // Try to get applicants with EETE ratings included first
            let resp
            try {
                resp = await applicantQualificationApi.getApplicantsWithRatings(selectedPosition.id, selectedPosition.type)
            } catch (ratingsError) {
                // Fallback to regular endpoint
                resp = await applicantQualificationApi.getApplicantsByPosition(selectedPosition.id, selectedPosition.type)
            }
            let rows = []
            const payload = resp?.data
            // map position qualifications from payload if present
            const bundle = payload?.data || payload
            if (bundle) {
                // Normalize educations to consistent keys
                if (Array.isArray(bundle.educations)) {
                    selectedPosition.educations = bundle.educations.map((e) => ({
                        academic_level: e?.academic_level || e?.academic || e?.level || e?.education || e?.academicLevel || e?.academic_level_name || e?.name || '',
                        program: e?.program || e?.course || e?.program_name || e?.degree || e?.description || e?.name || '',
                        graduated_year: e?.graduated_year || e?.year || e?.graduation_year || e?.graduatedYear || ''
                    }))
                } else {
                    selectedPosition.educations = []
                }
                selectedPosition.employments = Array.isArray(bundle.employments) ? bundle.employments : []
                selectedPosition.examinations = Array.isArray(bundle.examinations) ? bundle.examinations : []
                selectedPosition.trainings_arr = Array.isArray(bundle.trainings) ? bundle.trainings : []
                selectedPosition.competencies_arr = Array.isArray(bundle.competency) ? bundle.competency : []
                selectedPosition.qualification_text = bundle.qualification || ''
            }
            if (Array.isArray(payload)) {
                rows = payload
            } else if (Array.isArray(payload?.data)) {
                rows = payload.data
            } else if (Array.isArray(payload?.applicants)) {
                rows = payload.applicants
            } else if (Array.isArray(payload?.applicants?.data)) {
                rows = payload.applicants.data
            } else if (Array.isArray(payload?.data?.applicants)) {
                rows = payload.data.applicants
            }

            // Fallback: ApplicantsController (plantilla only)
            if ((!Array.isArray(rows) || rows.length === 0) && selectedPosition.type === 1) {
                const listResp = await applicantRecordsApi.listApplicants(selectedPosition.id)
                const listPayload = listResp?.data
                if (Array.isArray(listPayload)) rows = listPayload
                else if (Array.isArray(listPayload?.data)) rows = listPayload.data
            }

            applicants.value = rows
        } catch (err) {
            console.error(err)
            ElMessage.error('Failed to load applicants')
        } finally {
            applicantsLoading.value = false
        }
    }

    const openApplicantInfo = async (applicantId) => {
        try {
            applicantsLoading.value = true
            const { data } = await applicantQualificationApi.getApplicantInfo(applicantId)
            applicantInfo.value = data
            // Prime rating form
            ratingForm.applicant_id = applicantId
            showApplicantInfo.value = true
        } catch (err) {
            console.error(err)
            ElMessage.error('Failed to load applicant info')
        } finally {
            applicantsLoading.value = false
        }
    }

    const submitRating = async () => {
        try {
            saving.value = true
            
        // Complete payload with all required fields
        // Honor the chosen reviewed_status_id (2 = Qualified, 4 = For Reference)
        // and explicitly disable email sending via send_email flag
        const payload = {
            applicant_id: ratingForm.applicant_id,
            education_rating: ratingForm.education_rating,
            experience_rating: ratingForm.experience_rating,
            training_rating: ratingForm.training_rating,
            eligibility_rating: ratingForm.eligibility_rating,
            reviewed_status_id: ratingForm.reviewed_status_id ?? 2,
            interview: ratingForm.interview || 0,
            bonus: ratingForm.bonus || 0,
            // Use explicit pass/fail flags coming from the dropdown
            is_education_passed: ratingForm.is_education_passed ?? 0,
            is_experience_passed: ratingForm.is_experience_passed ?? 0,
            is_training_passed: ratingForm.is_training_passed ?? 0,
            is_eligibility_passed: ratingForm.is_eligibility_passed ?? 0,
            send_email: false
        }
            
            
            
            // Validate applicant_id before making the API call
            if (!payload.applicant_id) {
                throw new Error('Applicant ID is required but not found')
            }
            
            // Try multiple approaches to save the rating
            const approaches = [
                { name: 'No Email', method: () => applicantQualificationApi.saveRatingNoEmail(ratingForm.applicant_id, payload) },
                { name: 'Alternative Endpoint', method: () => applicantQualificationApi.saveRatingAlt(ratingForm.applicant_id, payload) },
                { name: 'PUT Method', method: () => applicantQualificationApi.updateRating(ratingForm.applicant_id, payload) },
                { name: 'Regular POST', method: () => applicantQualificationApi.saveRating(ratingForm.applicant_id, payload) }
            ]
            
            for (const approach of approaches) {
                try {
                    const response = await approach.method()
                    ElMessage.success('Rating saved successfully')
                    // Optimistically update in-memory list so UI reflects immediately
                    try {
                        const idx = applicants.value.findIndex(a => Number(a.id) === Number(ratingForm.applicant_id))
                        if (idx !== -1) {
                            const updated = { ...applicants.value[idx] }
                            updated.education_rating = Number(payload.education_rating)
                            updated.experience_rating = Number(payload.experience_rating)
                            updated.training_rating = Number(payload.training_rating)
                            updated.eligibility_rating = Number(payload.eligibility_rating)
                            updated.status_id = Number(payload.reviewed_status_id)
                            applicants.value.splice(idx, 1, updated)
                        }
                    } catch (optimisticErr) {
                    
                    }
                    return // Exit early if successful
                } catch (error) {
                    
                    
                    // Check if this is an email error (but not a database error)
                    if (error.response?.data?.message?.includes('Cannot send message without a sender address') ||
                        error.response?.data?.message?.includes('Swift_TransportException') ||
                        error.response?.data?.message?.includes('emails.applicant_rating')) {
                        
                        ElMessage.warning('Rating may have been saved (email notification failed - please verify)')
                        return // Exit early as this might be a success
                    }
                    
                    // If it's not an email error, continue to next approach
                }
            }
            
            // If all approaches failed, throw the last error
            throw new Error('All save approaches failed')
        } catch (err) {
            
            
            // Check if the error is related to email sending issues
            if (err.response?.data?.message?.includes('emails.applicant_rating') || 
                err.response?.data?.message?.includes('View [emails.applicant_rating] not found') ||
                err.response?.data?.message?.includes('Cannot send message without a sender address') ||
                err.response?.data?.message?.includes('Swift_TransportException')) {
                
                ElMessage.warning('Rating may have been saved (email notification failed - please verify)')
            } else {
                ElMessage.error('Failed to save rating')
            }
        } finally {
            saving.value = false
        }
    }

    const processApplicant = async ({ applicantId, positionAppliedId, isPlantilla, actionType }) => {
        try {
            saving.value = true
            await applicantQualificationApi.processApplicant(applicantId, positionAppliedId, isPlantilla, actionType)
            ElMessage.success('Action processed')
            await loadApplicants()
        } catch (err) {
            console.error(err)
            ElMessage.error('Failed to process action')
        } finally {
            saving.value = false
        }
    }

    const getZipInfo = async (positionId, positionName) => {
        const { data } = await applicantQualificationApi.getZip(positionId, positionName)
        return data
    }

    return {
        // state
        loading,
        applicantsLoading,
        saving,
        plantillaPositions,
        nonPlantillaPositions,
        selectedPosition,
        applicants,
        showApplicants,
        showApplicantInfo,
        applicantInfo,
        ratingForm,

        // methods
        fetchPositions,
        openApplicants,
        loadApplicants,
        openApplicantInfo,
        submitRating,
        processApplicant,
        getZipInfo
    }
}
