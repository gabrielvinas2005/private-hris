import { ref, computed } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import apiService from '../Services/api.js'

export function usePosition() {
    // State
    const positions = ref([])
    const loading = ref(false)
    const formLoading = ref(false)

    // Computed
    const totalPositions = computed(() => positions.value.length)

    // Methods
    async function fetchPositions() {
        try {
            loading.value = true
            const response = await apiService.getPositionSetup()

            if (response.success) {
                // Process the data to ensure proper types
                positions.value = response.data.map(position => ({
                    ...position,
                    id: parseInt(position.id),
                    name: (position.name || '').trim(), // Normalize name field
                    is_administrative_position: position.is_administrative_position === "1" || position.is_administrative_position === 1 || position.is_administrative_position === true,
                    active: position.active === "1" || position.active === 1 || position.active === true
                }))
            } else {
                ElMessage.error(response.message || 'Failed to fetch positions')
            }
        } catch (error) {
            console.error('Error fetching positions:', error)
            ElMessage.error('Failed to fetch positions')
        } finally {
            loading.value = false
        }
    }

    async function fetchFormData() {
        try {
            const response = await apiService.getPositionFormData()

            if (response.success) {
                // Position form data includes field definitions
                return { success: true, fields: response.data.fields }
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

    async function savePosition(positionData) {
        try {
            formLoading.value = true

            // Prepare data for submission
            const submitData = {
                name: positionData.name,
                is_administrative_position: positionData.is_administrative_position || false,
                active: positionData.active || false
            }

            let response
            if (positionData.id) {
                // Update existing position
                response = await apiService.updatePositionSetup(positionData.id, submitData)
            } else {
                // Create new position
                response = await apiService.savePositionSetup(submitData)
            }

            if (response.success) {
                ElMessage.success(response.message || 'Position saved successfully')
                await fetchPositions() // Refresh the list
                return { success: true, data: response.data }
            } else {
                ElMessage.error(response.message || 'Failed to save position')
                return { success: false, errors: response.errors }
            }
        } catch (error) {
            console.error('Error saving position:', error)
            ElMessage.error('Failed to save position')
            return { success: false, error: error.message }
        } finally {
            formLoading.value = false
        }
    }

    async function deletePosition(positionId) {
        try {
            await ElMessageBox.confirm(
                'Are you sure you want to delete this position? This action cannot be undone.',
                'Confirm Delete',
                {
                    confirmButtonText: 'Delete',
                    cancelButtonText: 'Cancel',
                    type: 'warning',
                }
            )

            const response = await apiService.deletePosition(positionId)

            if (response.success) {
                ElMessage.success(response.message || 'Position deleted successfully')
                await fetchPositions() // Refresh the list
                return { success: true }
            } else {
                ElMessage.error(response.message || 'Failed to delete position')
                return { success: false }
            }
        } catch (error) {
            if (error === 'cancel') {
                return { success: false, cancelled: true }
            }
            console.error('Error deleting position:', error)
            ElMessage.error('Failed to delete position')
            return { success: false, error: error.message }
        }
    }

    async function getPositionForEdit(positionId) {
        try {
            const response = await apiService.getPositionForEdit(positionId)

            if (response.success) {
                const position = response.data.position
                // Ensure boolean values are properly converted
                if (position) {
                    position.is_administrative_position = position.is_administrative_position === "1" || position.is_administrative_position === 1 || position.is_administrative_position === true
                    position.active = position.active === "1" || position.active === 1 || position.active === true
                }
                return {
                    success: true,
                    position: position,
                    competency: response.data.competency,
                    grouped_arr: response.data.grouped_arr
                }
            } else {
                ElMessage.error(response.message || 'Failed to fetch position data')
                return { success: false }
            }
        } catch (error) {
            console.error('Error fetching position for edit:', error)
            ElMessage.error('Failed to fetch position data')
            return { success: false, error: error.message }
        }
    }

    // Filter positions by search term and status
    function filterPositions(positions, searchTerm = '', statusFilter = '', typeFilter = '') {
        return positions.filter(position => {
            // Search filter
            if (searchTerm) {
                const searchLower = searchTerm.toLowerCase()
                const matchesSearch =
                    position.name.toLowerCase().includes(searchLower) ||
                    (position.code && position.code.toLowerCase().includes(searchLower))

                if (!matchesSearch) return false
            }

            // Status filter
            if (statusFilter) {
                if (statusFilter === 'active' && !position.active) return false
                if (statusFilter === 'inactive' && position.active) return false
            }

            // Type filter
            if (typeFilter) {
                if (typeFilter === 'administrative' && !position.is_administrative_position) return false
                if (typeFilter === 'non-administrative' && position.is_administrative_position) return false
            }

            return true
        })
    }

    return {
        // State
        positions,
        loading,
        formLoading,

        // Computed
        totalPositions,

        // Methods
        fetchPositions,
        fetchFormData,
        savePosition,
        deletePosition,
        getPositionForEdit,
        filterPositions
    }
}
