import { ref } from 'vue'
import { examinationApi } from '@/services/api'

export function useExamination() {
    const loading = ref(false)
    const error = ref(null)

    // Examination setup
    const exams = ref([])
    const examForm = ref({
        exam_set: null,
        exam_instruction: null,
        exam_duration: null,
        passing_criteria: null,
        weighted_allocation: null,
        with_video_recording: false,
        category_id: 0,
        exam_type_id: null
    })
    const examCategories = ref([])
    const examTypes = ref([])

    // Schedules
    const schedules = ref([])
    const scheduleForm = ref({
        exam_id: null,
        exam_date_from: null,
        exam_date_to: null,
        exam_time_from: null,
        exam_time_to: null
    })
    const examDetails = ref([])
    const applicants = ref([])
    const applicantPositions = ref([])
    const examinees = ref([])

    const fetchExams = async () => {
        loading.value = true
        error.value = null
        try {
            const { data } = await examinationApi.index()
            exams.value = data?.data || []
        } catch (e) {
            error.value = e
        } finally {
            loading.value = false
        }
    }

    const loadExamForm = async (id = 0) => {
        loading.value = true
        error.value = null
        try {
            const { data } = await examinationApi.add(id)
            const payload = data?.data || {}
            const list = payload.exams || []
            const first = Array.isArray(list) ? list[0] : list
            examForm.value = {
                exam_set: first?.exam_set ?? null,
                exam_instruction: first?.exam_instruction ?? null,
                exam_duration: first?.exam_duration ?? null,
                passing_criteria: first?.passing_criteria ?? null,
                weighted_allocation: first?.weighted_allocation ?? null,
                with_video_recording: first?.with_video_recording ?? false,
                category_id: first?.category_id ?? 0,
                exam_type_id: first?.exam_type_id ?? null
            }
            examCategories.value = payload.exam_categories || []
            examTypes.value = payload.exam_types || []
            return first?.id ?? 0
        } catch (e) {
            error.value = e
            return 0
        } finally {
            loading.value = false
        }
    }

    const saveExam = async (id, form) => {
        loading.value = true
        error.value = null
        try {
            const { data } = await examinationApi.store(id, form)
            return data?.data?.id
        } catch (e) {
            error.value = e
            // surface backend message if any
            console.error('Save exam failed:', e.response?.data || e.message)
            throw e
        } finally {
            loading.value = false
        }
    }

    const deleteExam = async (id) => {
        loading.value = true
        error.value = null
        try {
            await examinationApi.destroy(id)
        } catch (e) {
            error.value = e
            throw e
        } finally {
            loading.value = false
        }
    }

    const addPositions = async (examId, ids, selected) => {
        // backend expects arrays id[] and select[]
        const payload = { id: ids, select: selected }
        loading.value = true
        error.value = null
        try {
            await examinationApi.addPosition(examId, payload)
        } catch (e) {
            error.value = e
            throw e
        } finally {
            loading.value = false
        }
    }

    const removePosition = async (rowId) => {
        loading.value = true
        error.value = null
        try {
            await examinationApi.deletePosition(rowId)
        } catch (e) {
            error.value = e
            throw e
        } finally {
            loading.value = false
        }
    }

    const fetchSchedules = async () => {
        loading.value = true
        error.value = null
        try {
            const { data } = await examinationApi.schedules()
            schedules.value = data?.data || []
        } catch (e) {
            error.value = e
        } finally {
            loading.value = false
        }
    }

    const loadScheduleForm = async (id = 0) => {
        loading.value = true
        error.value = null
        try {
            const { data } = await examinationApi.schedulesAdd(id)
            const payload = data?.data || {}
            const list = payload.exam_schedules || []
            const first = Array.isArray(list) ? list[0] : list
            scheduleForm.value = {
                exam_id: first?.exam_id ?? null,
                exam_date_from: first?.exam_date_from ?? null,
                exam_date_to: first?.exam_date_to ?? null,
                exam_time_from: first?.exam_time_from ?? null,
                exam_time_to: first?.exam_time_to ?? null
            }
            // other bundles
            // exams for selection
            // and details for viewing
            examDetails.value = payload.exam_details || []
            applicants.value = payload.applicants || []
            applicantPositions.value = payload.applicant_positions || []
            examinees.value = payload.examinees || []
        } catch (e) {
            error.value = e
            throw e
        } finally {
            loading.value = false
        }
    }

    const saveSchedule = async (id, form) => {
        loading.value = true
        error.value = null
        try {
            const { data } = await examinationApi.schedulesStore(id, form)
            return data?.data?.id
        } catch (e) {
            error.value = e
            throw e
        } finally {
            loading.value = false
        }
    }

    const processSchedule = async (id, typeId) => {
        loading.value = true
        error.value = null
        try {
            await examinationApi.scheduleProcess(id, typeId)
        } catch (e) {
            error.value = e
            throw e
        } finally {
            loading.value = false
        }
    }

    const deleteSchedule = async (id) => {
        loading.value = true
        error.value = null
        try {
            await examinationApi.deleteSchedule(id)
        } catch (e) {
            error.value = e
            throw e
        } finally {
            loading.value = false
        }
    }

    const addExamineesToSchedule = async (scheduleId, ids, selected) => {
        const payload = { id: ids, select: selected }
        loading.value = true
        error.value = null
        try {
            await examinationApi.addExaminees(scheduleId, payload)
        } catch (e) {
            error.value = e
            throw e
        } finally {
            loading.value = false
        }
    }

    const deleteExaminee = async (rowId) => {
        loading.value = true
        error.value = null
        try {
            await examinationApi.deleteExaminee(rowId)
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
        // lists
        exams,
        schedules,
        // forms
        examForm,
        scheduleForm,
        // bundles
        examCategories,
        examTypes,
        examDetails,
        applicants,
        applicantPositions,
        examinees,
        // actions
        fetchExams,
        loadExamForm,
        saveExam,
        deleteExam,
        addPositions,
        removePosition,
        fetchSchedules,
        loadScheduleForm,
        saveSchedule,
        processSchedule,
        deleteSchedule,
        addExamineesToSchedule,
        deleteExaminee
    }
}


