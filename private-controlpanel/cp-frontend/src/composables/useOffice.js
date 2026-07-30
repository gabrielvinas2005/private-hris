import { ref, computed } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import apiService from '../Services/api.js'

export function useOffice() {
    // State
    const offices = ref([])
    const employees = ref([])
    const branches = ref([])
    const loading = ref(false)
    const formLoading = ref(false)

    // Computed
    const totalOffices = computed(() => offices.value.length)

    // Methods
    async function fetchOffices() {
        try {
            loading.value = true
            const response = await apiService.getOfficeSetup()

            if (response.success) {
                // Process the data to ensure proper types
                offices.value = response.data.map(office => ({
                    ...office,
                    id: parseInt(office.id),
                    branch_id: parseInt(office.branch_id),
                    employee_id: office.employee_id ? parseInt(office.employee_id) : null,
                    is_academic: office.is_academic === "1" || office.is_academic === 1 || office.is_academic === true,
                    active: office.active === "1" || office.active === 1 || office.active === true
                }))
            } else {
                ElMessage.error(response.message || 'Failed to fetch offices')
            }
        } catch (error) {
            console.error('Error fetching offices:', error)
            ElMessage.error('Failed to fetch offices')
        } finally {
            loading.value = false
        }
    }

    async function fetchFormData() {
        try {
            const response = await apiService.getOfficeFormData()

            if (response.success) {
                employees.value = response.data.employees || []
                branches.value = response.data.branches || []
            } else {
                ElMessage.error(response.message || 'Failed to fetch form data')
            }
        } catch (error) {
            console.error('Error fetching form data:', error)
            ElMessage.error('Failed to fetch form data')
        }
    }

    async function saveOffice(officeData) {
        try {
            formLoading.value = true

            // Prepare data for submission
            const supervisorId =
                officeData.employee_id != null &&
                officeData.employee_id !== '' &&
                Number(officeData.employee_id) !== 0
                    ? parseInt(officeData.employee_id, 10)
                    : null

            const submitData = {
                code: officeData.code?.trim(),
                name: officeData.name,
                functionality: officeData.functionality || '',
                branch_id: parseInt(officeData.branch_id, 10),
                employee_id: supervisorId,
                is_academic: !!officeData.is_academic,
                active: !!officeData.active
            }

            if (!submitData.code) {
                ElMessage.error('Office code is required')
                return { success: false, error: 'Office code is required' }
            }

            // Ensure branch_id is not null or 0
            if (!submitData.branch_id || submitData.branch_id === 0) {
                ElMessage.error('Please select a branch')
                return { success: false, error: 'Branch is required' }
            }


            let response
            if (officeData.id) {
                // Update existing office
                response = await apiService.updateOfficeSetup(officeData.id, submitData)
            } else {
                // Create new office
                response = await apiService.saveOfficeSetup(submitData)
            }

            if (response.success) {
                ElMessage.success(response.message || 'Office saved successfully')
                await fetchOffices() // Refresh the list
                return { success: true, data: response.data }
            } else {
                if (response.errors) {
                    const errorMessages = Object.values(response.errors).flat()
                    ElMessage.error(`Validation failed: ${errorMessages.join(', ')}`)
                } else {
                    ElMessage.error(response.message || 'Failed to save office')
                }
                return { success: false, errors: response.errors }
            }

        } catch (error) {
            console.error('Error saving office:', error)
            ElMessage.error('Failed to save office')
            return { success: false, error: error.message }
        } finally {
            formLoading.value = false
        }
    }

    async function deleteOffice(officeId) {
        try {
            await ElMessageBox.confirm(
                'Are you sure you want to delete this office? This action cannot be undone.',
                'Confirm Delete',
                {
                    confirmButtonText: 'Delete',
                    cancelButtonText: 'Cancel',
                    type: 'warning',
                }
            )

            const response = await apiService.deleteOffice(officeId)

            if (response.success) {
                ElMessage.success(response.message || 'Office deleted successfully')
                await fetchOffices() // Refresh the list
                return { success: true }
            } else {
                ElMessage.error(response.message || 'Failed to delete office')
                return { success: false }
            }
        } catch (error) {
            if (error === 'cancel') {
                return { success: false, cancelled: true }
            }
            console.error('Error deleting office:', error)
            ElMessage.error('Failed to delete office')
            return { success: false, error: error.message }
        }
    }

    async function getOfficeForEdit(officeId) {
        try {
            const response = await apiService.getOfficeForEdit(officeId)

            if (response.success) {
                const department = response.data.department
                // Process the department data to ensure proper boolean types
                const processedOffice = {
                    ...department,
                    id: parseInt(department.id),
                    branch_id: parseInt(department.branch_id),
                    employee_id: department.employee_id ? parseInt(department.employee_id) : null,
                    is_academic: department.is_academic === "1" || department.is_academic === 1 || department.is_academic === true,
                    active: department.active === "1" || department.active === 1 || department.active === true
                }
                
                return {
                    success: true,
                    office: processedOffice,
                    employees: response.data.employees || [],
                    branches: response.data.branches || []
                }
            } else {
                ElMessage.error(response.message || 'Failed to fetch office data')
                return { success: false }
            }
        } catch (error) {
            console.error('Error fetching office for edit:', error)
            ElMessage.error('Failed to fetch office data')
            return { success: false, error: error.message }
        }
    }

    // Filter offices by search term and type
    function filterOffices(offices, searchTerm = '', typeFilter = '') {
        return offices.filter(office => {
            // Search filter
            if (searchTerm) {
                const searchLower = searchTerm.toLowerCase()
                const matchesSearch =
                    office.name.toLowerCase().includes(searchLower) ||
                    office.code?.toLowerCase().includes(searchLower) ||
                    office.supervisor?.toLowerCase().includes(searchLower)

                if (!matchesSearch) return false
            }

            // Type filter
            if (typeFilter) {
                if (typeFilter === 'academic' && !office.is_academic) return false
                if (typeFilter === 'non-academic' && office.is_academic) return false
                if (typeFilter === 'active' && !office.active) return false
                if (typeFilter === 'inactive' && office.active) return false
            }

            return true
        })
    }

    return {
        // State
        offices,
        employees,
        branches,
        loading,
        formLoading,

        // Computed
        totalOffices,

        // Methods
        fetchOffices,
        fetchFormData,
        saveOffice,
        deleteOffice,
        getOfficeForEdit,
        filterOffices
    }
}
