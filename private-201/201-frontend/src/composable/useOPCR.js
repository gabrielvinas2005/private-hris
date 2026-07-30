import { ref } from 'vue'
import { ElMessage } from 'element-plus'
import { opcrApi } from '@/services/api'

export function useOPCR() {
    const loading = ref(false)
    const opcrRatings = ref([])
    const formData = ref(null)
    const reviewData = ref([])
    const currentOPCR = ref(null)

    const fetchOPCRRatings = async () => {
        try {
            loading.value = true
            const res = await opcrApi.getOPCRRatings()
            opcrRatings.value = res.data.data || res.data || []
            return opcrRatings.value
        } catch (e) {
            ElMessage.error('Failed to load OPCR ratings: ' + (e.response?.data?.message || e.message))
            console.error('Fetch error:', e)
            throw e
        } finally {
            loading.value = false
        }
    }

    const fetchFormData = async (id = 0) => {
        try {
            loading.value = true
            const res = await opcrApi.getFormData(id)
            formData.value = res.data.data || res.data || {}
            currentOPCR.value = formData.value.opcr_ratings?.[0] || null
            return formData.value
        } catch (e) {
            ElMessage.error('Failed to load form data: ' + (e.response?.data?.message || e.message))
            throw e
        } finally {
            loading.value = false
        }
    }

    const saveOPCR = async (id, data) => {
        try {
            loading.value = true
            const res = await opcrApi.saveOPCR(id, data)
            ElMessage.success(res.data.message || 'OPCR saved successfully')
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
            const res = await opcrApi.getReviewData(id)
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
            const res = await opcrApi.getAdjectivalRating(rating)
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
            const res = await opcrApi.saveRatings(id, data)
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

    const deleteOPCR = async (id) => {
        try {
            loading.value = true
            const res = await opcrApi.destroy(id)
            ElMessage.success(res.data.message || 'OPCR deleted successfully')
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
        opcrRatings,
        formData,
        reviewData,
        currentOPCR,
        fetchOPCRRatings,
        fetchFormData,
        saveOPCR,
        fetchReviewData,
        getAdjectivalRating,
        saveRatings,
        deleteOPCR
    }
}

