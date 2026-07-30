import { ref, reactive } from 'vue'
import ApiService from '../Services/api.js'

export function useEETERating() {
    const items = ref([])
    const loading = ref(false)
    const saving = ref(false)
    const DEFAULT_ALLOCATIONS = {
        education_rating: 10,
        experience_rating: 25,
        training_rating: 15,
        eligibility_rating: 50
    }

    const formData = reactive({
        education_rating: DEFAULT_ALLOCATIONS.education_rating,
        experience_rating: DEFAULT_ALLOCATIONS.experience_rating,
        training_rating: DEFAULT_ALLOCATIONS.training_rating,
        eligibility_rating: DEFAULT_ALLOCATIONS.eligibility_rating
    })

    const toPercent = (value) => {
        const num = parseFloat(value)
        if (!Number.isFinite(num)) return 0
        return parseFloat(num.toFixed(2))
    }

    const toPayloadValue = (value) => {
        const num = parseFloat(value)
        if (!Number.isFinite(num)) return 0
        return parseFloat(num.toFixed(2))
    }

    // Fetch all EETE ratings
    async function fetchList() {
        loading.value = true
        try {
            const response = await ApiService.getEETERatings()
            if (response && response.success) {
                const source = Array.isArray(response.data) ? response.data : (response.data ? [response.data] : [])
                items.value = source.map(item => {
                    const education = toPercent(item.education_rating)
                    const experience = toPercent(item.experience_rating)
                    const training = toPercent(item.training_rating)
                    const eligibility = toPercent(item.eligibility_rating)
                    const total = education + experience + training + eligibility
                    return {
                        id: parseInt(item.id),
                        education_rating: education,
                        experience_rating: experience,
                        training_rating: training,
                        eligibility_rating: eligibility,
                        total_rating: total,
                        average_rating: total / 4
                    }
                })
            }
        } catch (error) {
            console.error('Error fetching EETE ratings:', error)
        } finally {
            loading.value = false
        }
    }

    // Save EETE rating
    async function saveEETERating(data = null) {
        saving.value = true
        try {
            const payload = data || {
                education_rating: toPayloadValue(formData.education_rating),
                experience_rating: toPayloadValue(formData.experience_rating),
                training_rating: toPayloadValue(formData.training_rating),
                eligibility_rating: toPayloadValue(formData.eligibility_rating)
            }
            const response = await ApiService.saveEETERating(payload)
            if (response && response.success) {
                await fetchList() // Refresh the list
                resetForm()
                return response
            }
        } catch (error) {
            console.error('Error saving EETE rating:', error)
            throw error
        } finally {
            saving.value = false
        }
    }

    // Update EETE rating
    async function updateEETERating(id, data = null) {
        saving.value = true
        try {
            const payload = data || {
                education_rating: toPayloadValue(formData.education_rating),
                experience_rating: toPayloadValue(formData.experience_rating),
                training_rating: toPayloadValue(formData.training_rating),
                eligibility_rating: toPayloadValue(formData.eligibility_rating)
            }
            const response = await ApiService.updateEETERating(id, payload)
            if (response && response.success) {
                await fetchList() // Refresh the list
                resetForm()
                return response
            }
        } catch (error) {
            console.error('Error updating EETE rating:', error)
            throw error
        } finally {
            saving.value = false
        }
    }

    // Delete EETE rating
    async function deleteEETERating(id) {
        try {
            const response = await ApiService.deleteEETERating(id)
            if (response && response.success) {
                await fetchList() // Refresh the list
                return response
            }
        } catch (error) {
            console.error('Error deleting EETE rating:', error)
            throw error
        }
    }

    // Get EETE rating for editing
    async function getEETEForEdit(id) {
        try {
            const response = await ApiService.getEETEForEdit(id)
            if (response && response.success) {
                const data = response.data
                formData.education_rating = toPercent(data.education_rating)
                formData.experience_rating = toPercent(data.experience_rating)
                formData.training_rating = toPercent(data.training_rating)
                formData.eligibility_rating = toPercent(data.eligibility_rating)
                return response
            }
        } catch (error) {
            console.error('Error fetching EETE rating for edit:', error)
            throw error
        }
    }

    // Get form data for adding
    async function getFormData() {
        try {
            const response = await ApiService.getEETEFormData()
            if (response && response.success) {
                return response.data
            }
        } catch (error) {
            console.error('Error fetching form data:', error)
            throw error
        }
    }

    // Calculate total rating
    function calculateTotal() {
        return formData.education_rating + formData.experience_rating +
            formData.training_rating + formData.eligibility_rating
    }

    // Calculate average rating
    function calculateAverage() {
        return calculateTotal()
    }

    // Reset form data
    function resetForm() {
        formData.education_rating = DEFAULT_ALLOCATIONS.education_rating
        formData.experience_rating = DEFAULT_ALLOCATIONS.experience_rating
        formData.training_rating = DEFAULT_ALLOCATIONS.training_rating
        formData.eligibility_rating = DEFAULT_ALLOCATIONS.eligibility_rating
    }

    // Set form data for editing
    function setFormData(data) {
        formData.education_rating = toPercent(data.education_rating)
        formData.experience_rating = toPercent(data.experience_rating)
        formData.training_rating = toPercent(data.training_rating)
        formData.eligibility_rating = toPercent(data.eligibility_rating)
    }

    return {
        items,
        loading,
        saving,
        formData,
        fetchList,
        saveEETERating,
        updateEETERating,
        deleteEETERating,
        getEETEForEdit,
        getFormData,
        calculateTotal,
        calculateAverage,
        resetForm,
        setFormData
    }
}
