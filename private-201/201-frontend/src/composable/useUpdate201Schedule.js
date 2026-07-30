import { ref } from 'vue'
import { ElMessage } from 'element-plus'
import { update201ScheduleApi } from '@/services/api'

export function useUpdate201Schedule() {
    const loading = ref(false)
    const schedules = ref([])
    const currentSchedule = ref(null)

    const fetchSchedules = async () => {
        try {
            loading.value = true
            const res = await update201ScheduleApi.getSchedules()
            schedules.value = res.data.data || res.data || []
            return schedules.value
        } catch (e) {
            ElMessage.error('Failed to load schedules: ' + (e.response?.data?.message || e.message))
            console.error('Fetch error:', e)
            throw e
        } finally {
            loading.value = false
        }
    }

    const fetchSchedule = async (id) => {
        try {
            loading.value = true
            const res = await update201ScheduleApi.getSchedule(id)
            currentSchedule.value = res.data.data || res.data || null
            return currentSchedule.value
        } catch (e) {
            ElMessage.error('Failed to load schedule: ' + (e.response?.data?.message || e.message))
            throw e
        } finally {
            loading.value = false
        }
    }

    const saveSchedule = async (id, data) => {
        try {
            loading.value = true
            const res = await update201ScheduleApi.saveSchedule(id, data)
            ElMessage.success(res.data.message || 'Schedule saved successfully')
            return res.data
        } catch (e) {
            const msg = e.response?.data?.message || 'Save failed'
            ElMessage.error(msg)
            throw e
        } finally {
            loading.value = false
        }
    }

    const updateSchedule = async (id, data) => {
        try {
            loading.value = true
            const res = await update201ScheduleApi.updateSchedule(id, data)
            ElMessage.success(res.data.message || 'Schedule updated successfully')
            return res.data
        } catch (e) {
            const msg = e.response?.data?.message || 'Update failed'
            ElMessage.error(msg)
            throw e
        } finally {
            loading.value = false
        }
    }

    const deleteSchedule = async (id) => {
        try {
            loading.value = true
            const res = await update201ScheduleApi.deleteSchedule(id)
            ElMessage.success(res.data.message || 'Schedule deleted successfully')
            return res.data
        } catch (e) {
            const msg = e.response?.data?.message || 'Delete failed'
            ElMessage.error(msg)
            throw e
        } finally {
            loading.value = false
        }
    }

    return {
        loading,
        schedules,
        currentSchedule,
        fetchSchedules,
        fetchSchedule,
        saveSchedule,
        updateSchedule,
        deleteSchedule
    }
}
