import { ref, computed, watch } from 'vue'
import { ElMessage } from 'element-plus'
import apiService from '../Services/api.js'

export function useTimekeepingSetup() {
    const employmentTypes = ref([])
    const selectedEmploymentTypeId = ref(null)
    const form = ref({
        enable_web_clock: true,
        enable_biometric: true,
        require_selfie: true,
        enforce_geofence: true,
    })
    const loading = ref(false)
    const saving = ref(false)
    const employmentTypeSchedules = ref({})

    const hasSelection = computed(() => !!selectedEmploymentTypeId.value)

    function normalizeSchedule(data = {}) {
        return {
            enable_web_clock: data.enable_web_clock === true || data.enable_web_clock === 1 || data.enable_web_clock === '1' || data.enable_web_clock == null,
            enable_biometric: data.enable_biometric === true || data.enable_biometric === 1 || data.enable_biometric === '1' || data.enable_biometric == null,
            require_selfie:   data.require_selfie === true || data.require_selfie === 1 || data.require_selfie === '1' || data.require_selfie == null,
            enforce_geofence: data.enforce_geofence === true || data.enforce_geofence === 1 || data.enforce_geofence === '1' || data.enforce_geofence == null,
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
            form.value.enable_web_clock = true
            form.value.enable_biometric = true
            form.value.require_selfie   = true
            form.value.enforce_geofence = true
            return
        }
        const schedule = employmentTypeSchedules.value[typeId] || normalizeSchedule()
        form.value.enable_web_clock = schedule.enable_web_clock
        form.value.enable_biometric = schedule.enable_biometric
        form.value.require_selfie   = schedule.require_selfie
        form.value.enforce_geofence = schedule.enforce_geofence
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
                        enable_web_clock: type.tk_enable_web_clock,
                        enable_biometric: type.tk_enable_biometric,
                        require_selfie:   type.tk_require_selfie,
                        enforce_geofence: type.tk_enforce_geofence,
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
                enable_web_clock:   form.value.enable_web_clock,
                enable_biometric:   form.value.enable_biometric,
                require_selfie:     form.value.require_selfie,
                enforce_geofence:   form.value.enforce_geofence,
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
