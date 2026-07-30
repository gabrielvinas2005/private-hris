import { ref, computed } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import apiService from '../Services/api.js'

export function useDivision() {
    // State
    const divisions = ref([])
    const employees = ref([])
    const departments = ref([])
    const loading = ref(false)
    const formLoading = ref(false)

    // Computed
    const totalDivisions = computed(() => divisions.value.length)

    // Methods
    async function fetchDivisions() {
        try {
            loading.value = true
            const response = await apiService.getDivisionSetup()

            if (response.success) {
                // Process the data to ensure proper types
                divisions.value = response.data.map(division => ({
                    ...division,
                    id: parseInt(division.id),
                    department_id: parseInt(division.department_id),
                    division_chief_id: division.division_chief_id ? parseInt(division.division_chief_id) : null,
                    active: division.active === "1" || division.active === 1 || division.active === true
                }))
            } else {
                ElMessage.error(response.message || 'Failed to fetch divisions')
            }
        } catch (error) {
            console.error('Error fetching divisions:', error)
            ElMessage.error('Failed to fetch divisions')
        } finally {
            loading.value = false
        }
    }

    async function fetchFormData() {
        try {
            const response = await apiService.getDivisionFormData()

            if (response.success) {
                employees.value = response.data.employees || []
                departments.value = response.data.departments || []
            } else {
                ElMessage.error(response.message || 'Failed to fetch form data')
            }
        } catch (error) {
            console.error('Error fetching form data:', error)
            ElMessage.error('Failed to fetch form data')
        }
    }

    async function saveDivision(divisionData) {
        try {
            formLoading.value = true

            // Prepare data for submission
            const chiefId =
                divisionData.division_chief_id != null &&
                divisionData.division_chief_id !== '' &&
                Number(divisionData.division_chief_id) !== 0
                    ? parseInt(divisionData.division_chief_id, 10)
                    : null

            const submitData = {
                name: divisionData.name,
                department_id: parseInt(divisionData.department_id, 10),
                division_chief_id: chiefId
            }
            // IMPORTANT: Backend uses has('active'), so only send when true
            if (divisionData.active === true) {
                submitData.active = true
            }

            let response
            if (divisionData.id) {
                // Update existing division
                response = await apiService.updateDivisionSetup(divisionData.id, submitData)
            } else {
                // Create new division
                response = await apiService.saveDivisionSetup(submitData)
            }

            if (response.success) {
                ElMessage.success(response.message || 'Division saved successfully')
                await fetchDivisions() // Refresh the list
                return { success: true, data: response.data }
            } else {
                ElMessage.error(response.message || 'Failed to save division')
                return { success: false, errors: response.errors }
            }
        } catch (error) {
            console.error('Error saving division:', error)
            ElMessage.error('Failed to save division')
            return { success: false, error: error.message }
        } finally {
            formLoading.value = false
        }
    }

    async function deleteDivision(divisionId) {
        try {
            await ElMessageBox.confirm(
                'Are you sure you want to delete this division? This action cannot be undone.',
                'Confirm Delete',
                {
                    confirmButtonText: 'Delete',
                    cancelButtonText: 'Cancel',
                    type: 'warning',
                }
            )

            const response = await apiService.deleteDivision(divisionId)

            if (response.success) {
                ElMessage.success(response.message || 'Division deleted successfully')
                await fetchDivisions() // Refresh the list
                return { success: true }
            } else {
                ElMessage.error(response.message || 'Failed to delete division')
                return { success: false }
            }
        } catch (error) {
            if (error === 'cancel') {
                return { success: false, cancelled: true }
            }
            console.error('Error deleting division:', error)
            ElMessage.error('Failed to delete division')
            return { success: false, error: error.message }
        }
    }

    async function getDivisionForEdit(divisionId) {
        try {
            const response = await apiService.getDivisionForEdit(divisionId)

            if (response.success) {
                return {
                    success: true,
                    division: response.data.division,
                    employees: response.data.employees || [],
                    departments: response.data.departments || []
                }
            } else {
                ElMessage.error(response.message || 'Failed to fetch division data')
                return { success: false }
            }
        } catch (error) {
            console.error('Error fetching division for edit:', error)
            ElMessage.error('Failed to fetch division data')
            return { success: false, error: error.message }
        }
    }

    // Filter divisions by search term and type
    function filterDivisions(divisions, searchTerm = '', typeFilter = '') {
        return divisions.filter(division => {
            // Search filter
            if (searchTerm) {
                const searchLower = searchTerm.toLowerCase()
                const matchesSearch =
                    division.name.toLowerCase().includes(searchLower) ||
                    division.code?.toLowerCase().includes(searchLower) ||
                    division.division_chief?.toLowerCase().includes(searchLower) ||
                    division.office?.toLowerCase().includes(searchLower)

                if (!matchesSearch) return false
            }

            // Type filter
            if (typeFilter) {
                if (typeFilter === 'active' && !division.active) return false
                if (typeFilter === 'inactive' && division.active) return false
            }

            return true
        })
    }

    return {
        // State
        divisions,
        employees,
        departments,
        loading,
        formLoading,

        // Computed
        totalDivisions,

        // Methods
        fetchDivisions,
        fetchFormData,
        saveDivision,
        deleteDivision,
        getDivisionForEdit,
        filterDivisions
    }
}
