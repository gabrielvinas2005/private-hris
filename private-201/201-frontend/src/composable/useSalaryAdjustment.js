import { ref } from 'vue'
import { ElMessage } from 'element-plus'
import { salaryAdjustmentApi } from '@/services/api'

export function useSalaryAdjustment() {
    const loading = ref(false)
    const salarySchedules = ref([])

    const lastProcessedDate = ref(null)

    const fetchSalarySchedules = async () => {
        try {
            loading.value = true
            const res = await salaryAdjustmentApi.getSalarySchedules()
            const data = res.data.data || res.data || {}
            
            // Handle both old format (array) and new format (object with schedules)
            if (Array.isArray(data)) {
                salarySchedules.value = data
            } else {
                salarySchedules.value = data.schedules || []
                lastProcessedDate.value = data.last_processed_date || null
            }
            
            return salarySchedules.value
        } catch (e) {
            ElMessage.error('Failed to load salary schedules: ' + (e.response?.data?.message || e.message))
            console.error('Fetch error:', e)
            throw e
        } finally {
            loading.value = false
        }
    }

    const processSalaryAdjustment = async (salaryScheduleId, showMessages = true) => {
        try {
            loading.value = true
            const res = await salaryAdjustmentApi.processSalaryAdjustment({
                salary_schedule_id: salaryScheduleId
            })

            const result = res.data.data || res.data || {}
            const message = res.data.message || 'Salary adjustment processed successfully'

            // Only show messages if explicitly requested (for progress tracker, we'll show them later)
            if (showMessages) {
                ElMessage.success(`${message}. Processed: ${result.processed_count}/${result.total_employees} employees`)

                if (result.errors && result.errors.length > 0) {
                    result.errors.forEach(error => {
                        ElMessage.warning(error)
                    })
                }
            }

            return result
        } catch (e) {
            const msg = e.response?.data?.message || 'Processing failed'
            // Always show error messages
            if (showMessages) {
                ElMessage.error(msg)
            }
            throw e
        } finally {
            loading.value = false
        }
    }

    const fetchAffectedEmployees = async (salaryScheduleId) => {
        try {
            loading.value = true
            const res = await salaryAdjustmentApi.getAffectedEmployees(salaryScheduleId)
            return res.data.data || res.data || {}
        } catch (e) {
            ElMessage.error('Failed to load affected employees: ' + (e.response?.data?.message || e.message))
            console.error('Fetch error:', e)
            throw e
        } finally {
            loading.value = false
        }
    }

    const fetchProcessedEmployees = async (salaryScheduleId) => {
        try {
            loading.value = true
            const res = await salaryAdjustmentApi.getProcessedEmployees(salaryScheduleId)
            return res.data.data || res.data || {}
        } catch (e) {
            ElMessage.error('Failed to load processed employees: ' + (e.response?.data?.message || e.message))
            console.error('Fetch error:', e)
            throw e
        } finally {
            loading.value = false
        }
    }

    const fetchUnprocessedEmployees = async (salaryScheduleId) => {
        try {
            loading.value = true
            const res = await salaryAdjustmentApi.getUnprocessedEmployees(salaryScheduleId)
            return res.data.data || res.data || {}
        } catch (e) {
            ElMessage.error('Failed to load unprocessed employees: ' + (e.response?.data?.message || e.message))
            console.error('Fetch error:', e)
            throw e
        } finally {
            loading.value = false
        }
    }

    return {
        loading,
        salarySchedules,
        lastProcessedDate,
        fetchSalarySchedules,
        processSalaryAdjustment,
        fetchAffectedEmployees,
        fetchProcessedEmployees,
        fetchUnprocessedEmployees
    }
}
