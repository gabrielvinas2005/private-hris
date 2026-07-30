import { ref } from 'vue'
import { ElMessage } from 'element-plus'
import { vacantPositionApi } from '@/services/api'

export function useVacantPosition() {
    const loading = ref(false)
    const vacantPositions = ref([])
    const positionDetails = ref(null)
    const processingLoading = ref(false)

    // Fetch all vacant positions
    const fetchVacantPositions = async () => {
        try {
            loading.value = true
            const res = await vacantPositionApi.getVacantPositions()
            vacantPositions.value = res.data.data || res.data || []
            return vacantPositions.value
        } catch (e) {
            ElMessage.error('Failed to load vacant positions: ' + (e.response?.data?.message || e.message))
            console.error('Fetch error:', e)
            throw e
        } finally {
            loading.value = false
        }
    }

    // Fetch vacant position details
    const fetchVacantPositionDetails = async (id, type = 'plantilla') => {
        try {
            loading.value = true
            const res = await vacantPositionApi.getVacantPositionDetails(id, type)
            positionDetails.value = res.data.data || res.data || null
            return positionDetails.value
        } catch (e) {
            ElMessage.error('Failed to load position details: ' + (e.response?.data?.message || e.message))
            console.error('Fetch details error:', e)
            throw e
        } finally {
            loading.value = false
        }
    }

    // Process vacant position (approve/disapprove/cancel)
    const processVacantPosition = async (id, processId, type = 'plantilla', actionName = 'processed') => {
        try {
            processingLoading.value = true
            const res = await vacantPositionApi.processVacantPosition(id, processId, type)

            ElMessage.success(res.data.message || `Position ${actionName} successfully`)

            // Refresh the positions list
            await fetchVacantPositions()

            return res.data
        } catch (e) {
            ElMessage.error(`Failed to ${actionName.toLowerCase()} position: ` + (e.response?.data?.message || e.message))
            console.error('Process error:', e)
            throw e
        } finally {
            processingLoading.value = false
        }
    }

    // Helper methods for specific actions
    const approvePosition = async (id, type = 'plantilla') => {
        return await processVacantPosition(id, 1, type, 'approved')
    }

    const disapprovePosition = async (id, type = 'plantilla') => {
        return await processVacantPosition(id, 2, type, 'disapproved')
    }

    const cancelPosition = async (id, type = 'plantilla') => {
        return await processVacantPosition(id, 3, type, 'cancelled')
    }

    return {
        loading,
        vacantPositions,
        positionDetails,
        processingLoading,
        fetchVacantPositions,
        fetchVacantPositionDetails,
        processVacantPosition,
        approvePosition,
        disapprovePosition,
        cancelPosition
    }
}
