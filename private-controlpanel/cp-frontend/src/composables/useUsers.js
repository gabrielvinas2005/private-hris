import { ref, computed } from 'vue'
import { ElMessage } from 'element-plus'
import apiService from '../Services/api.js'

export function useUsers() {
    // Reactive state
    const users = ref([])
    const availableEmployees = ref([])
    const loading = ref(false)
    const loadingEmployees = ref(false)

    // Computed properties
    const activeUsers = computed(() =>
        users.value.filter(user => {
            const isLocked = !!user.locked
            const hasExpiration = !!user.with_expiration
            const expDate = user.expiration_date instanceof Date
                ? user.expiration_date
                : (user.expiration_date ? new Date(user.expiration_date) : null)
            const isExpired = hasExpiration && expDate && expDate <= new Date()
            return !isLocked && !isExpired
        })
    )

    const lockedUsers = computed(() =>
        users.value.filter(user => user.locked)
    )

    const expiredUsers = computed(() =>
        users.value.filter(user => {
            const hasExpiration = !!user.with_expiration
            const expDate = user.expiration_date instanceof Date
                ? user.expiration_date
                : (user.expiration_date ? new Date(user.expiration_date) : null)
            return hasExpiration && expDate && expDate <= new Date()
        })
    )

    const adminUsers = computed(() =>
        users.value.filter(user => user.is_admin)
    )

    const regularUsers = computed(() =>
        users.value.filter(user => !user.is_admin)
    )

    // Methods
    async function fetchUsers() {
        loading.value = true
        try {
            const data = await apiService.getUsers()

            if (data.success) {
                const toBool = (v) => !!(v === true || v === 1 || v === '1' || v === 'true' || v === 'on')
                users.value = (data.data.users || []).map(u => ({
                    ...u,
                    locked: toBool(u.locked),
                    is_admin: toBool(u.is_admin),
                    with_expiration: toBool(u.with_expiration),
                    access_all_branches: toBool(u.access_all_branches),
                    is_notify: toBool(u.is_notify),
                    with_hrm_access: toBool(u.with_hrm_access),
                    with_hrt_access: toBool(u.with_hrt_access),
                    with_hrp_access: toBool(u.with_hrp_access),
                    with_cpm_access: toBool(u.with_cpm_access),
                    with_ld_access: toBool(u.with_ld_access),
                    with_mig_access: toBool(u.with_mig_access),
                    with_ep_access: toBool(u.with_ep_access),
                    expiration_date: u.expiration_date ? new Date(u.expiration_date) : null,
                }))
                return { success: true, data: data.data.users }
            } else {
                ElMessage.error('Failed to fetch users')
                return { success: false, message: data.message }
            }
        } catch (error) {
            console.error('Error fetching users:', error)
            ElMessage.error('Error fetching users')
            return { success: false, message: error.message }
        } finally {
            loading.value = false
        }
    }

    async function fetchAvailableEmployees() {
        loadingEmployees.value = true
        try {
            const data = await apiService.getAvailableEmployees()

            if (data.success) {
                availableEmployees.value = data.data.employees || []
                return { success: true, data: data.data.employees }
            } else {
                ElMessage.error('Failed to fetch available employees')
                return { success: false, message: data.message }
            }
        } catch (error) {
            console.error('Error fetching employees:', error)
            ElMessage.error('Error fetching available employees')
            return { success: false, message: error.message }
        } finally {
            loadingEmployees.value = false
        }
    }

    async function addUsers(userData) {
        try {
            const data = await apiService.addUsers(userData)

            if (data.success) {
                ElMessage.success('Users added successfully!')
                await fetchUsers() // Refresh the list
                return { success: true }
            } else {
                ElMessage.error(data.message || 'Failed to add users')
                return { success: false, message: data.message }
            }
        } catch (error) {
            console.error('Error adding users:', error)
            ElMessage.error('Error adding users')
            return { success: false, message: error.message }
        }
    }

    // Note: These methods are commented out as the backend routes don't exist yet
    // async function updateUser(userId, userData) {
    //   // Will be implemented when backend route is available
    // }

    // async function deleteUser(userId) {
    //   // Will be implemented when backend route is available
    // }

    // async function toggleUserLock(userId) {
    //   // Will be implemented when backend route is available
    // }

    async function resetUserPassword(userId, newPassword = null) {
        try {
            const data = await apiService.resetUserPassword(userId, newPassword)
            if (data.success) {
                ElMessage.success(data.message || 'Password reset successfully!')
                return { success: true, message: data.message, newPassword: data.data?.new_password }
            } else {
                ElMessage.error(data.message || 'Failed to reset password')
                return { success: false, message: data.message }
            }
        } catch (error) {
            console.error('Error resetting password:', error)
            ElMessage.error('Error resetting password')
            return { success: false, message: error.message }
        }
    }

    // Filter and search functions
    function filterUsers(filters = {}) {
        return users.value.filter(user => {
            // Role filter
            if (filters.role) {
                const isAdmin = user.is_admin
                if (filters.role === 'admin' && !isAdmin) return false
                if (filters.role === 'user' && isAdmin) return false
            }

            // Status filter
            if (filters.status) {
                const isLocked = user.locked
                const isExpired = user.with_expiration && user.expiration_date <= new Date()

                if (filters.status === 'active' && (isLocked || isExpired)) return false
                if (filters.status === 'locked' && !isLocked) return false
                if (filters.status === 'expired' && !isExpired) return false
            }

            // Search filter
            if (filters.search) {
                const searchTerm = filters.search.toLowerCase()
                const name = user.name?.toLowerCase() || ''
                const email = user.email?.toLowerCase() || ''
                const empNo = user.employee_no?.toLowerCase() || ''

                if (!name.includes(searchTerm) && !email.includes(searchTerm) && !empNo.includes(searchTerm)) {
                    return false
                }
            }

            return true
        })
    }

    return {
        // State
        users,
        availableEmployees,
        loading,
        loadingEmployees,

        // Computed
        activeUsers,
        lockedUsers,
        expiredUsers,
        adminUsers,
        regularUsers,

        // Methods
        fetchUsers,
        fetchAvailableEmployees,
        addUsers,
        resetUserPassword,
        filterUsers
    }
}
