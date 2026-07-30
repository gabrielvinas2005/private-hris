import { ref, computed } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import apiService from '../Services/api.js'

export function useSpecialization() {
    // State
    const specializations = ref([])
    const loading = ref(false)
    const formLoading = ref(false)

    // Computed
    const totalSpecializations = computed(() => specializations.value.length)

    // Methods
    async function fetchSpecializations() {
        try {
            loading.value = true
            const response = await apiService.getSpecializationSetup()

            if (response.success) {
                // Process the data to ensure proper types
                specializations.value = response.data.map(specialization => ({
                    ...specialization,
                    id: parseInt(specialization.id),
                    active: specialization.active === "1" || specialization.active === 1 || specialization.active === true
                }))
            } else {
                ElMessage.error(response.message || 'Failed to fetch specializations')
            }
        } catch (error) {
            console.error('Error fetching specializations:', error)
            ElMessage.error('Failed to fetch specializations')
        } finally {
            loading.value = false
        }
    }

    async function fetchFormData() {
        try {
            const response = await apiService.getSpecializationFormData()

            if (response.success) {
                // Specialization form data is simple - just return success
                return { success: true }
            } else {
                ElMessage.error(response.message || 'Failed to fetch form data')
                return { success: false }
            }
        } catch (error) {
            console.error('Error fetching form data:', error)
            ElMessage.error('Failed to fetch form data')
            return { success: false }
        }
    }

    async function saveSpecialization(specializationData) {
        try {
            formLoading.value = true

            // Prepare data for submission
            const submitData = {
                name: specializationData.name
            }
            // Backend checks has('active'), include only when true
            if (specializationData.active === true) {
                submitData.active = true
            }

            let response
            if (specializationData.id) {
                // Update existing specialization
                response = await apiService.updateSpecializationSetup(specializationData.id, submitData)
            } else {
                // Create new specialization
                response = await apiService.saveSpecializationSetup(submitData)
            }

            if (response.success) {
                ElMessage.success(response.message || 'Specialization saved successfully')
                await fetchSpecializations() // Refresh the list
                return { success: true, data: response.data }
            } else {
                ElMessage.error(response.message || 'Failed to save specialization')
                return { success: false, errors: response.errors }
            }
        } catch (error) {
            console.error('Error saving specialization:', error)
            ElMessage.error('Failed to save specialization')
            return { success: false, error: error.message }
        } finally {
            formLoading.value = false
        }
    }

    async function deleteSpecialization(specializationId) {
        try {
            await ElMessageBox.confirm(
                'Are you sure you want to delete this specialization? This action cannot be undone.',
                'Confirm Delete',
                {
                    confirmButtonText: 'Delete',
                    cancelButtonText: 'Cancel',
                    type: 'warning',
                }
            )

            const response = await apiService.deleteSpecialization(specializationId)

            if (response.success) {
                ElMessage.success(response.message || 'Specialization deleted successfully')
                await fetchSpecializations() // Refresh the list
                return { success: true }
            } else {
                ElMessage.error(response.message || 'Failed to delete specialization')
                return { success: false }
            }
        } catch (error) {
            if (error === 'cancel') {
                return { success: false, cancelled: true }
            }
            console.error('Error deleting specialization:', error)
            ElMessage.error('Failed to delete specialization')
            return { success: false, error: error.message }
        }
    }

    async function getSpecializationForEdit(specializationId) {
        try {
            const response = await apiService.getSpecializationForEdit(specializationId)

            if (response.success) {
                return {
                    success: true,
                    specialization: response.data
                }
            } else {
                ElMessage.error(response.message || 'Failed to fetch specialization data')
                return { success: false }
            }
        } catch (error) {
            console.error('Error fetching specialization for edit:', error)
            ElMessage.error('Failed to fetch specialization data')
            return { success: false, error: error.message }
        }
    }

    // Filter specializations by search term and status
    function filterSpecializations(specializations, searchTerm = '', statusFilter = '') {
        return specializations.filter(specialization => {
            // Search filter
            if (searchTerm) {
                const searchLower = searchTerm.toLowerCase()
                const matchesSearch =
                    specialization.name.toLowerCase().includes(searchLower)

                if (!matchesSearch) return false
            }

            // Status filter
            if (statusFilter) {
                if (statusFilter === 'active' && !specialization.active) return false
                if (statusFilter === 'inactive' && specialization.active) return false
            }

            return true
        })
    }

    return {
        // State
        specializations,
        loading,
        formLoading,

        // Computed
        totalSpecializations,

        // Methods
        fetchSpecializations,
        fetchFormData,
        saveSpecialization,
        deleteSpecialization,
        getSpecializationForEdit,
        filterSpecializations
    }
}
