import { ref, computed, watch } from 'vue'
import { ElMessage } from 'element-plus'
import apiService from '../Services/api.js'

export function useTimekeepingSetup() {
    const employmentTypes = ref([])
    const selectedEmploymentTypeId = ref(null)
    const form = ref({ work_days: 0, work_hours: 0, with_holiday_pay: false })
    const loading = ref(false)
    const saving = ref(false)
    const employmentTypeSchedules = ref({})

    const hasSelection = computed(() => !!selectedEmploymentTypeId.value)

    function normalizeSchedule(data = {}) {
        return {
            work_days: Number(data.work_days ?? 0),
            work_hours: Number(data.work_hours ?? 0),
            with_holiday_pay: data.with_holiday_pay === true || data.with_holiday_pay === 1 || data.with_holiday_pay === '1'
        }
    }

    function setSchedule(typeId, data = {}) {
        employmentTypeSchedules.value = {
            ...employmentTypeSchedules.value,
            [typeId]: normalizeSchedule(data)
        }
    }

    function applyScheduleToForm(typeId) {
        if (!typeId) {
            form.value.work_days = 0
            form.value.work_hours = 0
            form.value.with_holiday_pay = false
            return
        }
        const schedule = employmentTypeSchedules.value[typeId] || normalizeSchedule()
        form.value.work_days = schedule.work_days
        form.value.work_hours = schedule.work_hours
        form.value.with_holiday_pay = schedule.with_holiday_pay
    }

    async function fetchEmploymentTypes() {
        try {
            loading.value = true
            const res = await apiService.getTimekeepingEmploymentTypes()
            if (res.success) {
                const types = res.data || []
                employmentTypes.value = types

                if (!types.length) {
                    employmentTypeSchedules.value = {}
                    selectedEmploymentTypeId.value = null
                    applyScheduleToForm(null)
                    return
                }

                const scheduleMap = {}
                types.forEach(type => {
                    scheduleMap[type.id] = normalizeSchedule({
                        work_days: type.tk_work_days,
                        work_hours: type.tk_work_hours,
                        with_holiday_pay: type.tk_with_holiday_pay
                    })
                })
                employmentTypeSchedules.value = scheduleMap

                if (!selectedEmploymentTypeId.value && employmentTypes.value.length > 0) {
                    selectedEmploymentTypeId.value = employmentTypes.value[0].id
                } else if (selectedEmploymentTypeId.value) {
                    applyScheduleToForm(selectedEmploymentTypeId.value)
                }
            } else {
                ElMessage.error(res.message || 'Failed to load employment types')
            }
        } catch (e) {
            ElMessage.error('Failed to load employment types')
        } finally {
            loading.value = false
        }
    }

    watch(selectedEmploymentTypeId, (newVal) => {
        if (newVal) {
            if (!employmentTypeSchedules.value[newVal]) {
                setSchedule(newVal, {})
            }
            applyScheduleToForm(newVal)
        }
    })

    async function save() {
        if (!selectedEmploymentTypeId.value) {
            ElMessage.warning('Please select an employment type')
            return { success: false }
        }
        try {
            saving.value = true
            const payload = {
                employment_type_id: selectedEmploymentTypeId.value,
                work_days: form.value.work_days,
                work_hours: form.value.work_hours,
                with_holiday_pay: form.value.with_holiday_pay
            }
            const res = await apiService.saveTimekeepingSetup(payload)
            if (res.success) {
                setSchedule(selectedEmploymentTypeId.value, form.value)
                ElMessage.success(res.message || 'Timekeeping setup saved')
                return { success: true }
            }
            ElMessage.error(res.message || 'Failed to save timekeeping setup')
            return { success: false, errors: res.errors }
        } catch (e) {
            ElMessage.error('Failed to save timekeeping setup')
            return { success: false }
        } finally {
            saving.value = false
        }
    }

    return {
        employmentTypes,
        employmentTypeSchedules,
        selectedEmploymentTypeId,
        form,
        loading,
        saving,
        hasSelection,
        fetchEmploymentTypes,
        save
    }
}

