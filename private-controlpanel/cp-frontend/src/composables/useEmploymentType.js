import { ref, computed } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import apiService from '../Services/api.js'

export function useEmploymentType() {
    // State
    const employmentTypes = ref([])
    const loading = ref(false)
    const formLoading = ref(false)

    // Computed
    const totalEmploymentTypes = computed(() => employmentTypes.value.length)

    // Methods
    async function fetchEmploymentTypes() {
        try {
            loading.value = true
            const response = await apiService.getEmploymentTypeSetup()

            if (response.success) {
                // Process the data to ensure proper types
                employmentTypes.value = response.data.map(employmentType => ({
                    ...employmentType,
                    id: parseInt(employmentType.id),
                    with_end_contract: employmentType.with_end_contract === "1" || employmentType.with_end_contract === 1 || employmentType.with_end_contract === true,
                    active: employmentType.active === "1" || employmentType.active === 1 || employmentType.active === true
                }))
            } else {
                ElMessage.error(response.message || 'Failed to fetch employment types')
            }
        } catch (error) {
            console.error('Error fetching employment types:', error)
            ElMessage.error('Failed to fetch employment types')
        } finally {
            loading.value = false
        }
    }

    async function fetchFormData() {
        try {
            const response = await apiService.getEmploymentTypeFormData()

            if (response.success) {
                // Employment type form data is simple - just return success
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

    async function saveEmploymentType(employmentTypeData) {
        try {
            formLoading.value = true

            // Prepare data for submission
            const submitData = {
                name: employmentTypeData.name,
                with_end_contract: employmentTypeData.with_end_contract,
                active: employmentTypeData.active
            }

            let response
            if (employmentTypeData.id) {
                // Update existing employment type
                response = await apiService.updateEmploymentTypeSetup(employmentTypeData.id, submitData)
            } else {
                // Create new employment type
                response = await apiService.saveEmploymentTypeSetup(submitData)
            }

            if (response.success) {
                ElMessage.success(response.message || 'Employment type saved successfully')
                await fetchEmploymentTypes() // Refresh the list
                return { success: true, data: response.data }
            } else {
                ElMessage.error(response.message || 'Failed to save employment type')
                return { success: false, errors: response.errors }
            }
        } catch (error) {
            console.error('Error saving employment type:', error)
            ElMessage.error('Failed to save employment type')
            return { success: false, error: error.message }
        } finally {
            formLoading.value = false
        }
    }

    async function deleteEmploymentType(employmentTypeId) {
        try {
            await ElMessageBox.confirm(
                'Are you sure you want to delete this employment type? This action cannot be undone.',
                'Confirm Delete',
                {
                    confirmButtonText: 'Delete',
                    cancelButtonText: 'Cancel',
                    type: 'warning',
                }
            )

            const response = await apiService.deleteEmploymentType(employmentTypeId)

            if (response.success) {
                ElMessage.success(response.message || 'Employment type deleted successfully')
                await fetchEmploymentTypes() // Refresh the list
                return { success: true }
            } else {
                ElMessage.error(response.message || 'Failed to delete employment type')
                return { success: false }
            }
        } catch (error) {
            if (error === 'cancel') {
                return { success: false, cancelled: true }
            }
            console.error('Error deleting employment type:', error)
            ElMessage.error('Failed to delete employment type')
            return { success: false, error: error.message }
        }
    }

    async function getEmploymentTypeForEdit(employmentTypeId) {
        try {
            const response = await apiService.getEmploymentTypeForEdit(employmentTypeId)

            if (response.success) {
                return {
                    success: true,
                    employmentType: response.data
                }
            } else {
                ElMessage.error(response.message || 'Failed to fetch employment type data')
                return { success: false }
            }
        } catch (error) {
            console.error('Error fetching employment type for edit:', error)
            ElMessage.error('Failed to fetch employment type data')
            return { success: false, error: error.message }
        }
    }

    // Filter employment types by search term and status
    function filterEmploymentTypes(employmentTypes, searchTerm = '', statusFilter = '') {
        return employmentTypes.filter(employmentType => {
            // Search filter
            if (searchTerm) {
                const searchLower = searchTerm.toLowerCase()
                const matchesSearch =
                    employmentType.name.toLowerCase().includes(searchLower)

                if (!matchesSearch) return false
            }

            // Status filter
            if (statusFilter) {
                if (statusFilter === 'active' && !employmentType.active) return false
                if (statusFilter === 'inactive' && employmentType.active) return false
                if (statusFilter === 'with_contract' && !employmentType.with_end_contract) return false
                if (statusFilter === 'without_contract' && employmentType.with_end_contract) return false
            }

            return true
        })
    }

    return {
        // State
        employmentTypes,
        loading,
        formLoading,

        // Computed
        totalEmploymentTypes,

        // Methods
        fetchEmploymentTypes,
        fetchFormData,
        saveEmploymentType,
        deleteEmploymentType,
        getEmploymentTypeForEdit,
        filterEmploymentTypes
    }
}
