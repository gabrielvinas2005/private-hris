import { ref, computed } from 'vue'
import { ElMessage } from 'element-plus'
import { authApi } from '@/services/api'
import { devAuthService } from '@/services/devAuth'

export function useAuth() {
    const user = ref(null)
    const loading = ref(false)
    const isAuthenticated = computed(() => checkAuth())

    // Check for dev authentication
    const checkDevAuth = async () => {
        // Only check in development environment
        if (!devAuthService.shouldUseDevAuth()) {
            return false;
        }

        // Dev auth now requires manual login, so return false to show login form
        return false;
    };

    // Dev login function using regular login with admin validation
    const devLogin = async (credentials) => {
        try {
            loading.value = true;
            // Use the dev login endpoint
            const response = await authApi.devLogin(credentials);

            if (response && response.data) {
                const { token, user: userData } = response.data;

                if (token && userData) {
                    // Store token and user data using dev auth storage
                    const tokenData = {
                        token: String(token),
                        expiresAt: Date.now() + (24 * 60 * 60 * 1000), // 24 hours from now
                        user: userData,
                        isDevAuth: true
                    };

                    localStorage.setItem('dev_auth_token', JSON.stringify(tokenData));
                    user.value = userData;

                    ElMessage.success('Dev login successful! Redirecting to main page...');

                    // Redirect to main page after a short delay
                    setTimeout(() => {
                        window.location.href = '/fix-schedule'
                    }, 1500);

                    return response.data;
                }
            }

            ElMessage.error('Dev login failed - no valid response');
            return null;
        } catch (error) {
            ElMessage.error('Dev login failed: ' + error.message);
            return null;
        } finally {
            loading.value = false;
        }
    };

    // Check for shared authentication parameters
    const checkSharedAuth = async () => {
        const urlParams = new URLSearchParams(window.location.search);
        const employeeNo = urlParams.get('employee_no');
        const email = urlParams.get('email');
        const authToken = urlParams.get('auth_token');
        const redirectFrom = urlParams.get('redirect_from');

        if (employeeNo && email && authToken && redirectFrom === 'e_portal') {
            // Use the passed token directly from E-Portal
            const sharedUser = {
                employee_no: employeeNo,
                email: email,
                name: email.split('@')[0],
                id: employeeNo
            };

            // Store token in the format expected by Timekeeping Module
            const tokenData = {
                token: String(authToken), // Ensure token is a string
                expiresAt: Date.now() + (24 * 60 * 60 * 1000) // 24 hours from now
            };

            // Set up frontend auth state with E-Portal's token
            user.value = sharedUser;
            localStorage.setItem('auth_token', JSON.stringify(tokenData));

            try {
                const response = await authApi.getCurrentUser();
                const payload = response?.data ?? response;
                const currentUser = payload?.user ?? payload?.data?.user;
                if (currentUser) {
                    user.value = currentUser;
                }
            } catch (error) {
                console.error('Timekeeping Module - Failed to hydrate shared auth user from backend:', error);
            }

            // Clean URL parameters
            const url = new URL(window.location);
            url.searchParams.delete('employee_no');
            url.searchParams.delete('email');
            url.searchParams.delete('auth_token');
            url.searchParams.delete('redirect_from');
            window.history.replaceState({}, '', url);

            return true;
        }
        return false;
    };

    // Login function
    const login = async (credentials) => {
        try {
            loading.value = true
            const response = await authApi.login(credentials)

            // Store token and user data from backend response
            // Handle different response formats from backend
            const responseData = response.data || response
            const { token, user: userData } = responseData

            if (!token) {
                throw new Error('No token received from server')
            }

            // For Laravel Sanctum tokens, they typically don't have expiration
            // But we'll set a reasonable expiration time (24 hours)
            const tokenData = {
                token: String(token), // Ensure token is a string
                expiresAt: Date.now() + (24 * 60 * 60 * 1000) // 24 hours from now
            }
            localStorage.setItem('auth_token', JSON.stringify(tokenData))
            user.value = userData

            ElMessage.success('Login successful!')
            return response.data
        } catch (error) {
            console.error('Login failed:', error)
            const errorMessage = error.response?.data?.message || 'Login failed'
            ElMessage.error(errorMessage)
            throw error
        } finally {
            loading.value = false
        }
    }

    // Clear auth data (for token expiration)
    const clearAuth = () => {
        localStorage.removeItem('auth_token')
        localStorage.removeItem('user_email')
        try { localStorage.removeItem('tk_allowed_menus') } catch (_) { }
        // Clear persisted Process Attendance state on logout
        try { localStorage.removeItem('process_attendance_state') } catch (_) { }
        devAuthService.clearDevAuthToken()
        user.value = null
    }

    // Get current user
    const getCurrentUser = async () => {
        try {
            const response = await authApi.getCurrentUser()
            // Support both wrapped and unwrapped API payloads
            const payload = response?.data ?? response
            const current = payload?.user ?? payload?.data?.user
            user.value = current || null
            return current
        } catch (error) {
            console.error('Failed to get current user:', error)
            // Do not auto-clear on 401 here to avoid forcing re-login unless token is expired
            throw error
        }
    }

    const hydrateAuthenticatedUser = async (force = false) => {
        if (!force && user.value) {
            return user.value
        }

        try {
            return await getCurrentUser()
        } catch (error) {
            return null
        }
    }

    // Check if user is authenticated
    const checkAuth = () => {
        // First check for dev auth token
        if (devAuthService.isDevAuthValid()) {
            const tokenData = devAuthService.getDevAuthToken();
            if (tokenData && tokenData.user) {
                user.value = tokenData.user;
                return true;
            }
        }

        // Then check for regular auth token
        const tokenData = localStorage.getItem('auth_token')
        if (!tokenData) return false

        try {
            const parsed = JSON.parse(tokenData)
            // Check if token is expired
            if (parsed.expiresAt && Date.now() > parsed.expiresAt) {
                clearAuth()
                return false
            }
            return !!parsed.token
        } catch (error) {
            // If parsing fails, treat as old format token
            return !!tokenData
        }
    }

    // Dev logout function
    const devLogout = async () => {
        try {
            loading.value = true;
            await devAuthService.devLogout();
            clearAuth();
            ElMessage.success('Dev logout successful!');
        } catch (error) {
            console.error('Dev logout failed:', error);
            ElMessage.error('Dev logout failed');
        } finally {
            loading.value = false;
        }
    };

    // Initialize auth state
    const initAuth = async () => {
        // First check for shared authentication parameters
        if (await checkSharedAuth()) {
            return;
        }

        // Check for dev authentication if in development mode
        if (await checkDevAuth()) {
            return;
        }

        const hasValidToken = checkAuth()

        if (hasValidToken) {
            await hydrateAuthenticatedUser().catch(() => null)
        }
    }

    return {
        // State
        user,
        loading,
        isAuthenticated,

        // Methods
        login,
        devLogin,
        devLogout,
        clearAuth,
        getCurrentUser,
        hydrateAuthenticatedUser,
        checkAuth,
        initAuth,
        checkSharedAuth,
        checkDevAuth
    }
}
