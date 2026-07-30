import { ref, reactive } from 'vue'
import ApiService from '../Services/api.js'

export function useSemesterRating() {
    const items = ref([])
    const loading = ref(false)
    const saving = ref(false)
    const formData = reactive({
        name: '',
        active: true
    })

    // Fetch all semester ratings
    async function fetchList() {
        loading.value = true
        try {
            const response = await ApiService.getSemesterRatings()
            if (response && response.success) {
                items.value = response.data.map(item => ({
                    id: parseInt(item.id),
                    name: item.name || '',
                    active: item.active === true || item.active === 1 || item.active === '1'
                }))
            }
        } catch (error) {
            console.error('Error fetching semester ratings:', error)
        } finally {
            loading.value = false
        }
    }

    // Add new semester rating
    async function addSemesterRating(data = null) {
        saving.value = true
        try {
            const payload = data || {
                name: formData.name,
                active: formData.active
            }
            const response = await ApiService.saveSemesterRating(payload)
            if (response && response.success) {
                await fetchList() // Refresh the list
                resetForm()
                return response
            }
        } catch (error) {
            console.error('Error adding semester rating:', error)
            throw error
        } finally {
            saving.value = false
        }
    }

    // Update existing semester rating
    async function updateSemesterRating(id, data = null) {
        saving.value = true
        try {
            const payload = data || {
                name: formData.name,
                active: formData.active
            }
            const response = await ApiService.updateSemesterRating(id, payload)
            if (response && response.success) {
                await fetchList() // Refresh the list
                resetForm()
                return response
            }
        } catch (error) {
            console.error('Error updating semester rating:', error)
            throw error
        } finally {
            saving.value = false
        }
    }

    // Delete semester rating
    async function deleteSemesterRating(id) {
        try {
            const response = await ApiService.deleteSemesterRating(id)
            if (response && response.success) {
                await fetchList() // Refresh the list
                return response
            }
        } catch (error) {
            console.error('Error deleting semester rating:', error)
            throw error
        }
    }

    // Get semester rating for editing
    async function getSemesterRatingForEdit(id) {
        try {
            const response = await ApiService.getSemesterRatingForEdit(id)
            if (response && response.success) {
                const data = response.data.semester_rating
                formData.name = data.name || ''
                formData.active = data.active === true || data.active === 1 || data.active === '1'
                return response
            }
        } catch (error) {
            console.error('Error fetching semester rating for edit:', error)
            throw error
        }
    }

    // Reset form data
    function resetForm() {
        formData.name = ''
        formData.active = true
    }

    // Set form data for editing
    function setFormData(data) {
        formData.name = data.name || ''
        formData.active = data.active === true || data.active === 1 || data.active === '1'
    }

    return {
        items,
        loading,
        saving,
        formData,
        fetchList,
        addSemesterRating,
        updateSemesterRating,
        deleteSemesterRating,
        getSemesterRatingForEdit,
        resetForm,
        setFormData
    }
}
