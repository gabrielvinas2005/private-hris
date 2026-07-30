import { ref, computed } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import apiService from '../Services/api.js'

export function useBranch() {
    // Reactive state
    const branches = ref([])
    const employees = ref([])
    const loading = ref(false)
    const saving = ref(false)
    const deleting = ref(false)

    // Computed properties
    const hasBranches = computed(() => branches.value.length > 0)
    const mainBranches = computed(() => branches.value.filter(branch => branch.is_main_branch))
    const regularBranches = computed(() => branches.value.filter(branch => !branch.is_main_branch))

    // Methods
    async function fetchBranches() {
        loading.value = true
        try {
            const data = await apiService.get('/branches')

            if (data.success) {
                const employeeList = data.data.employees || []
                const employeeMap = new Map(
                    employeeList.map(emp => {
                        const id = parseInt(emp.id)
                        return [id, { ...emp, id }]
                    })
                )

                const rawBranches = data.data.branches || []
                branches.value = rawBranches.map(branch => {
                    const normalizedId = parseInt(branch.id)
                    const branchHeadId = branch.branch_head_id === "0" || branch.branch_head_id === 0 || branch.branch_head_id === null
                        ? null
                        : parseInt(branch.branch_head_id)
                    const branchHead = branchHeadId ? employeeMap.get(branchHeadId) : null

                    return {
                        ...branch,
                        id: normalizedId,
                        is_main_branch: branch.is_main_branch === "1" || branch.is_main_branch === 1 || branch.is_main_branch === true,
                        branch_head_id: branchHeadId,
                        branch_head_name: branchHead?.name || branch.branch_head_name || null
                    }
                })

                employees.value = employeeList.map(emp => ({
                    ...emp,
                    id: parseInt(emp.id)
                }))
                return { success: true, data: data.data }
            } else {
                ElMessage.error('Failed to fetch branch data')
                return { success: false, message: data.message }
            }
        } catch (error) {
            console.error('Error fetching branches:', error)
            ElMessage.error('Error fetching branch data')
            return { success: false, message: error.message }
        } finally {
            loading.value = false
        }
    }

    async function fetchEmployees() {
        try {
            // The employees are already fetched with branches in the index method
            // This method is kept for compatibility but employees should be fetched with branches
            const data = await apiService.get('/branches')

            if (data.success) {
                employees.value = data.data.employees || []
                return { success: true, data: data.data.employees }
            } else {
                ElMessage.error('Failed to fetch employees')
                return { success: false, message: data.message }
            }
        } catch (error) {
            console.error('Error fetching employees:', error)
            ElMessage.error('Error fetching employees')
            return { success: false, message: error.message }
        }
    }

    async function saveBranches(branchesData) {
        saving.value = true
        try {
            const data = await apiService.post('/branches', branchesData)

            if (data.success) {
                ElMessage.success('Branches updated successfully!')
                await fetchBranches() // Refresh the data
                return { success: true, data: data.data }
            } else {
                ElMessage.error(data.message || 'Failed to update branches')
                return { success: false, message: data.message }
            }
        } catch (error) {
            console.error('Error saving branches:', error)
            ElMessage.error('Error updating branches')
            return { success: false, message: error.message }
        } finally {
            saving.value = false
        }
    }

    async function deleteBranch(branchId) {
        deleting.value = true
        try {
            const data = await apiService.delete(`/branches/${branchId}`)

            if (data.success) {
                ElMessage.success('Branch deleted successfully!')
                await fetchBranches() // Refresh the data
                return { success: true }
            } else {
                ElMessage.error(data.message || 'Failed to delete branch')
                return { success: false, message: data.message }
            }
        } catch (error) {
            console.error('Error deleting branch:', error)
            ElMessage.error('Error deleting branch')
            return { success: false, message: error.message }
        } finally {
            deleting.value = false
        }
    }

    async function getBranchForDelete(branchId) {
        try {
            const data = await apiService.get(`/branches/${branchId}/delete`)

            if (data.success) {
                return { success: true, data: data.data }
            } else {
                ElMessage.error('Failed to fetch branch details')
                return { success: false, message: data.message }
            }
        } catch (error) {
            console.error('Error fetching branch for delete:', error)
            ElMessage.error('Error fetching branch details')
            return { success: false, message: error.message }
        }
    }

    async function getBranchForEdit(branchId) {
        try {
            const data = await apiService.get(`/branches/${branchId}/edit`)

            if (data.success) {
                return { success: true, data: data.data }
            } else {
                ElMessage.error('Failed to fetch branch details')
                return { success: false, message: data.message }
            }
        } catch (error) {
            console.error('Error fetching branch for edit:', error)
            ElMessage.error('Error fetching branch details')
            return { success: false, message: error.message }
        }
    }

    // Form validation
    function validateBranchForm(branchData) {
        const errors = {}

        if (!branchData.name || branchData.name.trim() === '') {
            errors.name = 'Branch name is required'
        }

        // Check for duplicate names
        const existingBranch = branches.value.find(branch =>
            branch.name.toLowerCase() === branchData.name.toLowerCase() &&
            branch.id !== branchData.id
        )
        if (existingBranch) {
            errors.name = 'Branch name already exists'
        }

        return {
            isValid: Object.keys(errors).length === 0,
            errors
        }
    }

    // Utility functions
    function getEmployeeName(employeeId) {
        const employee = employees.value.find(emp => emp.id === employeeId)
        return employee ? employee.name : 'Not assigned'
    }

    function getBranchHeadName(branch) {
        if (!branch.branch_head_id) return 'Not assigned'
        return getEmployeeName(branch.branch_head_id)
    }

    function formatBranchData(branchesData) {
        // Convert array of branch objects to the format expected by the backend
        const formattedData = {
            id: [],
            code: [],
            name: [],
            branch_head_id: [],
            is_main_branch: []
        }

        branchesData.forEach(branch => {
            formattedData.id.push(branch.id || null)
            formattedData.code.push(branch.code || '')
            formattedData.name.push(branch.name || '')
            formattedData.branch_head_id.push(branch.branch_head_id || null)
            if (branch.is_main_branch) {
                formattedData.is_main_branch.push(branch.id)
            }
        })

        return formattedData
    }

    // Confirmation dialogs
    async function confirmDeleteBranch(branch) {
        try {
            await ElMessageBox.confirm(
                `Are you sure you want to delete the branch "${branch.name}"? This action cannot be undone.`,
                'Delete Branch',
                {
                    confirmButtonText: 'Delete',
                    cancelButtonText: 'Cancel',
                    type: 'warning',
                    confirmButtonClass: 'el-button--danger'
                }
            )
            return true
        } catch {
            return false
        }
    }

    // Filter and search functions
    function filterBranches(filters = {}) {
        return branches.value.filter(branch => {
            // Type filter
            if (filters.type) {
                if (filters.type === 'main' && !branch.is_main_branch) return false
                if (filters.type === 'regular' && branch.is_main_branch) return false
            }

            // Search filter
            if (filters.search) {
                const searchTerm = filters.search.toLowerCase()
                const name = branch.name?.toLowerCase() || ''
                const code = branch.code?.toLowerCase() || ''
                const headName = getBranchHeadName(branch).toLowerCase()

                if (!name.includes(searchTerm) && !code.includes(searchTerm) && !headName.includes(searchTerm)) {
                    return false
                }
            }

            return true
        })
    }

    return {
        // State
        branches,
        employees,
        loading,
        saving,
        deleting,

        // Computed
        hasBranches,
        mainBranches,
        regularBranches,

        // Methods
        fetchBranches,
        fetchEmployees,
        saveBranches,
        deleteBranch,
        getBranchForDelete,
        getBranchForEdit,
        validateBranchForm,
        getEmployeeName,
        getBranchHeadName,
        formatBranchData,
        confirmDeleteBranch,
        filterBranches
    }
}
