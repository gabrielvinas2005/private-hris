import { ref } from 'vue'
import { ElMessage } from 'element-plus'
import { lengthOfServiceApi } from '@/services/api'

export function useLengthOfService() {
    const loading = ref(false)
    const lengthOfServiceRecords = ref([])

    // Fetch all length of service records
    const fetchLengthOfServiceRecords = async () => {
        try {
            loading.value = true
            const res = await lengthOfServiceApi.getLengthOfServiceRecords()
            lengthOfServiceRecords.value = res.data.data || res.data || []
            return lengthOfServiceRecords.value
        } catch (e) {
            ElMessage.error('Failed to load length of service records: ' + (e.response?.data?.message || e.message))
            console.error('Fetch error:', e)
            throw e
        } finally {
            loading.value = false
        }
    }

    return {
        loading,
        lengthOfServiceRecords,
        fetchLengthOfServiceRecords
    }
}
