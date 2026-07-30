import { ref } from 'vue'
import { ElMessage } from 'element-plus'
import { dpcrApi } from '@/services/api'

export function useDPCR() {
    const loading = ref(false)
    const dpcrRatings = ref([])
    const formData = ref(null)
    const reviewData = ref([])
    const currentDPCR = ref(null)

    const fetchDPCRRatings = async () => {
        try {
            loading.value = true
            const res = await dpcrApi.getDPCRRatings()
            dpcrRatings.value = res.data.data || res.data || []
            return dpcrRatings.value
        } catch (e) {
            ElMessage.error('Failed to load DPCR ratings: ' + (e.response?.data?.message || e.message))
            console.error('Fetch error:', e)
            throw e
        } finally {
            loading.value = false
        }
    }

    const fetchFormData = async (id = 0) => {
        try {
            loading.value = true
            const res = await dpcrApi.getFormData(id)
            formData.value = res.data.data || res.data || {}
            currentDPCR.value = formData.value.dpcr_ratings?.[0] || null
            return formData.value
        } catch (e) {
            ElMessage.error('Failed to load form data: ' + (e.response?.data?.message || e.message))
            throw e
        } finally {
            loading.value = false
        }
    }

    const saveDPCR = async (id, data) => {
        try {
            loading.value = true
            const res = await dpcrApi.saveDPCR(id, data)
            ElMessage.success(res.data.message || 'DPCR saved successfully')
            return res.data
        } catch (e) {
            const msg = e.response?.data?.message || 'Save failed'
            ElMessage.error(msg)
            throw e
        } finally {
            loading.value = false
        }
    }

    const fetchReviewData = async (id) => {
        try {
            loading.value = true
            const res = await dpcrApi.getReviewData(id)
            reviewData.value = res.data.data || res.data || []
            return reviewData.value
        } catch (e) {
            ElMessage.error('Failed to load review data: ' + (e.response?.data?.message || e.message))
            throw e
        } finally {
            loading.value = false
        }
    }

    const saveRating = async (id, data) => {
        try {
            loading.value = true
            const res = await dpcrApi.saveRating(id, data)
            ElMessage.success(res.data.message || 'Ratings saved successfully')
            return res.data
        } catch (e) {
            const msg = e.response?.data?.message || 'Save ratings failed'
            ElMessage.error(msg)
            throw e
        } finally {
            loading.value = false
        }
    }

    const deleteDPCR = async (id) => {
        try {
            loading.value = true
            const res = await dpcrApi.destroy(id)
            ElMessage.success(res.data.message || 'DPCR deleted successfully')
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
        dpcrRatings,
        formData,
        reviewData,
        currentDPCR,
        fetchDPCRRatings,
        fetchFormData,
        saveDPCR,
        fetchReviewData,
        saveRating,
        deleteDPCR
    }
}
