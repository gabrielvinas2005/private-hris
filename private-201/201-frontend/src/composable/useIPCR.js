import { ref } from 'vue'
import { ElMessage } from 'element-plus'
import { ipcrApi } from '@/services/api'

export function useIPCR() {
    const loading = ref(false)
    const ipcrRatings = ref([])
    const formData = ref(null)
    const reviewData = ref([])
    const currentIPCR = ref(null)

    const fetchIPCRRatings = async () => {
        try {
            loading.value = true
            const res = await ipcrApi.getIPCRRatings()
            ipcrRatings.value = res.data.data || res.data || []
            return ipcrRatings.value
        } catch (e) {
            ElMessage.error('Failed to load IPCR ratings: ' + (e.response?.data?.message || e.message))
            console.error('Fetch error:', e)
            throw e
        } finally {
            loading.value = false
        }
    }

    const fetchFormData = async (id = 0) => {
        try {
            loading.value = true
            const res = await ipcrApi.getFormData(id)
            formData.value = res.data.data || res.data || {}
            currentIPCR.value = formData.value.ipcr_ratings?.[0] || null
            return formData.value
        } catch (e) {
            ElMessage.error('Failed to load form data: ' + (e.response?.data?.message || e.message))
            throw e
        } finally {
            loading.value = false
        }
    }

    const saveIPCR = async (id, data) => {
        try {
            loading.value = true
            const res = await ipcrApi.saveIPCR(id, data)
            ElMessage.success(res.data.message || 'IPCR saved successfully')
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
            const res = await ipcrApi.getReviewData(id)
            reviewData.value = res.data.data || res.data || []
            return reviewData.value
        } catch (e) {
            ElMessage.error('Failed to load review data: ' + (e.response?.data?.message || e.message))
            throw e
        } finally {
            loading.value = false
        }
    }

    const getAdjectivalRating = async (rating) => {
        try {
            const res = await ipcrApi.getAdjectivalRating(rating)
            const data = res.data.data || res.data || []
            return data.length > 0 ? data[0].adjectival_rating : ''
        } catch (e) {
            console.error('Failed to get adjectival rating:', e)
            return ''
        }
    }

    const saveRatings = async (id, data) => {
        try {
            loading.value = true
            const res = await ipcrApi.saveRatings(id, data)
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

    const deleteIPCR = async (id) => {
        try {
            loading.value = true
            const res = await ipcrApi.destroy(id)
            ElMessage.success(res.data.message || 'IPCR deleted successfully')
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
        ipcrRatings,
        formData,
        reviewData,
        currentIPCR,
        fetchIPCRRatings,
        fetchFormData,
        saveIPCR,
        fetchReviewData,
        getAdjectivalRating,
        saveRatings,
        deleteIPCR
    }
}
