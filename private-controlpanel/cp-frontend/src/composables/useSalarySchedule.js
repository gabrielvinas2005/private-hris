import { ref, reactive, computed } from 'vue'
import ApiService from '../Services/api'
import { ElMessage } from 'element-plus'

export function useSalarySchedule() {
    const schedules = ref([])
    const loading = ref(false)
    const saving = ref(false)

    // form state
    const formVisible = ref(false)
    const formLoading = ref(false)
    const form = reactive({
        id: null,
        name: '',
        enabling_law: '',
        effectivity: '',
        active: false
    })
    const options = reactive({
        grades: [],
        steps: []
    })
    const scheduleDetails = ref([])

    async function fetchSchedules() {
        loading.value = true
        try {
            const res = await ApiService.getSalarySchedules()
            const data = res?.data || []
            schedules.value = data.map(schedule => ({
                ...schedule,
                active: schedule.active === true || schedule.active === 1 || schedule.active === "1"
            }))
        } catch (error) {
            schedules.value = []
        } finally {
            loading.value = false
        }
    }

    async function openForm(id = null) {
        formVisible.value = true
        formLoading.value = true
        try {
            // Load form options (grades and steps)
            const formRes = await ApiService.getSalaryScheduleForm()
            const formData = formRes?.data || {}
            options.grades = formData.grade || []
            options.steps = formData.step || []

            if (id) {
                // Load existing schedule for editing
                const editRes = await ApiService.getSalaryScheduleForEdit(id)
                const editData = editRes?.data || {}
                const schedule = editData.salary_schedule || {}
                const details = editData.salary_schedule_details || []

                form.id = schedule.id
                form.name = schedule.name || ''
                form.enabling_law = schedule.enabling_law || ''
                form.effectivity = schedule.effectivity || ''
                form.active = schedule.active === true || schedule.active === 1 || schedule.active === "1"

                // Convert details to editable format
                scheduleDetails.value = details.map(detail => ({
                    id: detail.id,
                    salary_grade_id: detail.salary_grade_id,
                    salary_step_id: detail.salary_step_id,
                    amount: detail.amount || ''
                }))
            } else {
                // Reset form for new schedule
                form.id = null
                form.name = ''
                form.enabling_law = ''
                form.effectivity = ''
                form.active = false
                scheduleDetails.value = []
            }
        } catch (error) {
            // Handle error silently
        } finally {
            formLoading.value = false
        }
    }

    function addScheduleDetail() {
        scheduleDetails.value.push({
            id: null,
            salary_grade_id: null,
            salary_step_id: null,
            amount: ''
        })
    }

    function removeScheduleDetail(index) {
        const detail = scheduleDetails.value[index]
        if (!detail) return

        if (!detail.id) {
            scheduleDetails.value.splice(index, 1)
            return
        }

        ApiService.deleteSalaryScheduleDetail(detail.id)
            .then((res) => {
                if (res?.success) {
                    scheduleDetails.value.splice(index, 1)
                    ElMessage.success('Salary detail removed')
                } else {
                    ElMessage.error(res?.message || 'Failed to delete salary detail')
                }
            })
            .catch(() => {
                ElMessage.error('Failed to delete salary detail')
            })
    }

    async function saveForm() {
        saving.value = true
        try {
            // Prepare payload
            const payload = {
                name: form.name,
                enabling_law: form.enabling_law,
                effectivity: form.effectivity,
                active: form.active
            }

            // Add schedule details
            const validDetails = scheduleDetails.value.filter(detail =>
                detail.salary_grade_id && detail.salary_step_id && detail.amount
            )

            if (validDetails.length > 0) {
                payload.salary_grade_id = validDetails.map(d => d.salary_grade_id)
                payload.salary_step_id = validDetails.map(d => d.salary_step_id)
                payload.amount = validDetails.map(d => d.amount)
            }

            if (form.id) {
                // Update existing
                await ApiService.updateSalarySchedule(form.id, payload)
            } else {
                // Create new
                await ApiService.saveSalarySchedule(payload)
            }

            await fetchSchedules()
            formVisible.value = false
        } catch (error) {
            // Handle error
        } finally {
            saving.value = false
        }
    }

    async function deleteSchedule(id) {
        try {
            await ApiService.deleteSalaryScheduleDetail(id)
            await fetchSchedules()
        } catch (error) {
            // Handle error
        }
    }

    const tableColumns = [
        { key: 'name', label: 'Name', minWidth: 200 },
        { key: 'enabling_law', label: 'Enabling Law', minWidth: 200 },
        { key: 'effectivity', label: 'Effectivity', minWidth: 150 },
        { key: 'active', label: 'Active', width: 100 },
        { key: 'actions', label: 'Actions', width: 200, fixed: 'right' }
    ]

    return {
        schedules,
        loading,
        saving,
        fetchSchedules,
        // form
        formVisible,
        formLoading,
        form,
        options,
        scheduleDetails,
        openForm,
        addScheduleDetail,
        removeScheduleDetail,
        saveForm,
        deleteSchedule,
        tableColumns
    }
}
