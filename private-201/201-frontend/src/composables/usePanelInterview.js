import { ref } from 'vue'
import { interviewApi } from '@/services/api'

export function usePanelInterview() {
    const loading = ref(false)
    const error = ref(null)

    const emptyForm = () => ({
        panel_group: null,
        interview_location: null,
        description: null,
        panel_group_level: 0,
        start_date: '',
        end_date: '',
        start_time: '',
        end_time: ''
    })

    const interviews = ref([])
    const form = ref(emptyForm())
    const levels = ref([])
    const panels = ref([])
    const applicants = ref([])
    const panelSelect = ref([])
    const applicantSelect = ref([])
    const applicantPositions = ref([])
    const interviewLevelName = ref('')
    const panelRatingBreakdown = ref([])
    const panelReviewAttachments = ref([])

    const resetForm = () => {
        // Clear any previous record values immediately (prevents "Add New" from showing stale data)
        form.value = emptyForm()
        panels.value = []
        applicants.value = []
        panelSelect.value = []
        applicantSelect.value = []
        applicantPositions.value = []
        interviewLevelName.value = ''
        panelRatingBreakdown.value = []
        panelReviewAttachments.value = []
    }

    const fetchList = async () => {
        loading.value = true
        error.value = null
        try {
            const { data } = await interviewApi.index()
            interviews.value = data?.data?.inteview_schedules || []
        } catch (e) {
            error.value = e
        } finally {
            loading.value = false
        }
    }

    const loadForm = async (id = 0) => {
        // Always reset first, so even if the request fails the UI is clean.
        resetForm()
        loading.value = true
        error.value = null
        try {
            const { data } = await interviewApi.add(id)
            const payload = data?.data || {}
            const list = payload.interview_data || []
            const first = Array.isArray(list) ? list[0] : list
            form.value = {
                ...emptyForm(),
                panel_group: first?.panel_group ?? null,
                interview_location: first?.interview_location ?? null,
                description: first?.description ?? null,
                panel_group_level: first?.panel_group_level ?? 0,
                start_date: first?.start_date || '',
                end_date: first?.end_date || '',
                start_time: first?.start_time || '',
                end_time: first?.end_time || ''
            }
            levels.value = payload.interview_levels || []
            panels.value = payload.interview_panels || []
            applicants.value = payload.interview_applicants || []
            panelSelect.value = payload.panel_select || []
            applicantSelect.value = payload.applicant_select || []
            applicantPositions.value = payload.applicant_positions || []
            interviewLevelName.value = payload.interview_level_name ?? ''
            panelRatingBreakdown.value = payload.panel_rating_breakdown ?? []
            panelReviewAttachments.value = payload.panel_review_attachments ?? []
            return first?.id ?? 0
        } catch (e) {
            error.value = e
            return 0
        } finally {
            loading.value = false
        }
    }

    const saveHeader = async (id, payload) => {
        loading.value = true
        error.value = null
        try {
            const { data } = await interviewApi.store(id, payload)
            return data?.data?.interview_id || 0
        } catch (e) {
            error.value = e
            throw e
        } finally {
            loading.value = false
        }
    }

    const addPanels = async (interviewId, ids, selected) => {
        loading.value = true
        error.value = null
        try {
            await interviewApi.addPanel(interviewId, { id: ids, select: selected })
        } catch (e) {
            error.value = e
            throw e
        } finally {
            loading.value = false
        }
    }

    const addApplicants = async (interviewId, ids, selected) => {
        loading.value = true
        error.value = null
        try {
            await interviewApi.addApplicant(interviewId, { id: ids, select: selected })
        } catch (e) {
            error.value = e
            throw e
        } finally {
            loading.value = false
        }
    }

    const deletePanel = async (rowId) => {
        loading.value = true
        error.value = null
        try {
            await interviewApi.deletePanel(rowId)
        } catch (e) {
            error.value = e
            throw e
        } finally {
            loading.value = false
        }
    }

    const deleteApplicant = async (rowId) => {
        loading.value = true
        error.value = null
        try {
            await interviewApi.deleteApplicant(rowId)
        } catch (e) {
            error.value = e
            throw e
        } finally {
            loading.value = false
        }
    }

    const deleteInterview = async (id) => {
        loading.value = true
        error.value = null
        try {
            await interviewApi.delete(id)
        } catch (e) {
            error.value = e
            throw e
        } finally {
            loading.value = false
        }
    }

    const processInterview = async (id, typeId) => {
        loading.value = true
        error.value = null
        try {
            await interviewApi.process(id, typeId)
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
        interviews,
        form,
        levels,
        panels,
        applicants,
        panelSelect,
        applicantSelect,
        applicantPositions,
        interviewLevelName,
        panelRatingBreakdown,
        panelReviewAttachments,
        fetchList,
        resetForm,
        loadForm,
        saveHeader,
        addPanels,
        addApplicants,
        deletePanel,
        deleteApplicant,
        deleteInterview,
        processInterview
    }
}


