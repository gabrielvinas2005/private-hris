import { ref } from 'vue'
import { ElMessage } from 'element-plus'
import { offBoardingApi } from '@/services/api'

export function useOffBoarding() {
    const loading = ref(false)
    const offBoardings = ref([])
    const formData = ref(null)

    const fetchOffBoardings = async () => {
        try {
            loading.value = true
            const res = await offBoardingApi.getOffBoardings()
            offBoardings.value = res.data.data || []
        } catch (e) {
            ElMessage.error('Failed to load off-boardings')
            throw e
        } finally {
            loading.value = false
        }
    }

    const fetchForm = async (id = 0) => {
        try {
            loading.value = true
            const res = await offBoardingApi.getForm(id)
            formData.value = res.data.data || res.data || {}
            return formData.value
        } catch (e) {
            ElMessage.error('Failed to load form data: ' + (e.response?.data?.message || e.message))
            throw e
        } finally {
            loading.value = false
        }
    }

    const saveOffBoarding = async (id, payload) => {
        try {
            loading.value = true
            const res = await offBoardingApi.save(id, payload)
            ElMessage.success(res.data.message || 'Saved')
            return res.data.data
        } catch (e) {
            const msg = e.response?.data?.message || 'Save failed'
            ElMessage.error(msg)
            throw e
        } finally {
            loading.value = false
        }
    }

    const showOffBoarding = async (id, employeeId) => {
        try {
            loading.value = true
            const res = await offBoardingApi.show(id, employeeId)

            const result = res.data?.data || res.data || null
            return result
        } catch (e) {
            ElMessage.error('Failed to fetch off-boarding: ' + (e.response?.data?.message || e.message))
            throw e
        } finally {
            loading.value = false
        }
    }

    const activateEmployee = async (id, employeeId) => {
        try {
            loading.value = true
            const res = await offBoardingApi.activate(id, employeeId)
            return res.data.data
        } catch (e) {
            ElMessage.error('Failed to get activation data: ' + (e.response?.data?.message || e.message))
            throw e
        } finally {
            loading.value = false
        }
    }

    const rehireEmployee = async (id) => {
        try {
            loading.value = true
            const res = await offBoardingApi.rehire(id)
            ElMessage.success(res.data.message || 'Employee rehired successfully')
            return res.data.data
        } catch (e) {
            const msg = e.response?.data?.message || 'Rehiring failed'
            ElMessage.error(msg)
            throw e
        } finally {
            loading.value = false
        }
    }

    const reactivateEmployee = async (id, employeeId, payload) => {
        try {
            loading.value = true
            const res = await offBoardingApi.reactivate(id, employeeId, payload)
            ElMessage.success(res.data.message || 'Employee reactivated successfully')
            return res.data.data
        } catch (e) {
            const msg = e.response?.data?.message || 'Reactivation failed'
            ElMessage.error(msg)
            throw e
        } finally {
            loading.value = false
        }
    }

    return {
        loading,
        offBoardings,
        formData,
        fetchOffBoardings,
        fetchForm,
        saveOffBoarding,
        showOffBoarding,
        activateEmployee,
        reactivateEmployee,
        rehireEmployee
    }
}
