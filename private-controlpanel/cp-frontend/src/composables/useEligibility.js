import { ref, computed } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import apiService from '../Services/api.js'

export function useEligibility() {
    // State
    const eligibilities = ref([])
    const loading = ref(false)
    const formLoading = ref(false)

    // Computed
    const totalEligibilities = computed(() => eligibilities.value.length)

    // Methods
    async function fetchEligibilities() {
        try {
            loading.value = true
            const response = await apiService.getEligibilitySetup()

            if (response.success) {
                // Process the data to ensure proper types
                eligibilities.value = response.data.map(eligibility => ({
                    ...eligibility,
                    id: parseInt(eligibility.id),
                    active: eligibility.active === "1" || eligibility.active === 1 || eligibility.active === true
                }))
            } else {
                ElMessage.error(response.message || 'Failed to fetch eligibilities')
            }
        } catch (error) {
            console.error('Error fetching eligibilities:', error)
            ElMessage.error('Failed to fetch eligibilities')
        } finally {
            loading.value = false
        }
    }

    async function fetchFormData() {
        try {
            const response = await apiService.getEligibilityFormData()

            if (response.success) {
                // Eligibility form data is simple - just return success
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

    async function saveEligibility(eligibilityData) {
        try {
            formLoading.value = true

            // Prepare data for submission
            const submitData = {
                name: eligibilityData.name
            }
            // Backend checks has('active'), include only when true
            if (eligibilityData.active === true) {
                submitData.active = true
            }

            let response
            if (eligibilityData.id) {
                // Update existing eligibility
                response = await apiService.updateEligibilitySetup(eligibilityData.id, submitData)
            } else {
                // Create new eligibility
                response = await apiService.saveEligibilitySetup(submitData)
            }

            if (response.success) {
                ElMessage.success(response.message || 'Eligibility saved successfully')
                await fetchEligibilities() // Refresh the list
                return { success: true, data: response.data }
            } else {
                ElMessage.error(response.message || 'Failed to save eligibility')
                return { success: false, errors: response.errors }
            }
        } catch (error) {
            console.error('Error saving eligibility:', error)
            ElMessage.error('Failed to save eligibility')
            return { success: false, error: error.message }
        } finally {
            formLoading.value = false
        }
    }

    async function deleteEligibility(eligibilityId) {
        try {
            await ElMessageBox.confirm(
                'Are you sure you want to delete this eligibility? This action cannot be undone.',
                'Confirm Delete',
                {
                    confirmButtonText: 'Delete',
                    cancelButtonText: 'Cancel',
                    type: 'warning',
                }
            )

            const response = await apiService.deleteEligibility(eligibilityId)

            if (response.success) {
                ElMessage.success(response.message || 'Eligibility deleted successfully')
                await fetchEligibilities() // Refresh the list
                return { success: true }
            } else {
                ElMessage.error(response.message || 'Failed to delete eligibility')
                return { success: false }
            }
        } catch (error) {
            if (error === 'cancel') {
                return { success: false, cancelled: true }
            }
            console.error('Error deleting eligibility:', error)
            ElMessage.error('Failed to delete eligibility')
            return { success: false, error: error.message }
        }
    }

    async function getEligibilityForEdit(eligibilityId) {
        try {
            const response = await apiService.getEligibilityForEdit(eligibilityId)

            if (response.success) {
                return {
                    success: true,
                    eligibility: response.data
                }
            } else {
                ElMessage.error(response.message || 'Failed to fetch eligibility data')
                return { success: false }
            }
        } catch (error) {
            console.error('Error fetching eligibility for edit:', error)
            ElMessage.error('Failed to fetch eligibility data')
            return { success: false, error: error.message }
        }
    }

    // Filter eligibilities by search term and status
    function filterEligibilities(eligibilities, searchTerm = '', statusFilter = '') {
        return eligibilities.filter(eligibility => {
            // Search filter
            if (searchTerm) {
                const searchLower = searchTerm.toLowerCase()
                const matchesSearch =
                    eligibility.name.toLowerCase().includes(searchLower)

                if (!matchesSearch) return false
            }

            // Status filter
            if (statusFilter) {
                if (statusFilter === 'active' && !eligibility.active) return false
                if (statusFilter === 'inactive' && eligibility.active) return false
            }

            return true
        })
    }

    return {
        // State
        eligibilities,
        loading,
        formLoading,

        // Computed
        totalEligibilities,

        // Methods
        fetchEligibilities,
        fetchFormData,
        saveEligibility,
        deleteEligibility,
        getEligibilityForEdit,
        filterEligibilities
    }
}
