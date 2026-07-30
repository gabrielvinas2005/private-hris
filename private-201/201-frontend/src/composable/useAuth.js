import { ref, computed } from 'vue'
import { ElMessage } from 'element-plus'
import { authApi } from '@/services/api'


export function useAuth() {
    const user = ref(null)
    const loading = ref(false)
    const isAuthenticated = computed(() => !!localStorage.getItem('auth_token'))

    // Check for shared authentication parameters and verify token
    const checkSharedAuth = async () => {
        const urlParams = new URLSearchParams(window.location.search);
        const employeeNo = urlParams.get('employee_no');
        const email = urlParams.get('email');
        const authToken = urlParams.get('auth_token');
        const redirectFrom = urlParams.get('redirect_from');
        
        console.log('HR Module - Checking shared auth parameters:', { 
            employeeNo, 
            email, 
            authToken: authToken ? authToken.substring(0, 20) + '...' : null, 
            redirectFrom 
        });
        console.log('HR Module - Current URL:', window.location.href);
        
        if (employeeNo && email && authToken && redirectFrom === 'e_portal') {
            console.log('HR Module - Shared auth detected from E-Portal:', { employeeNo, email });
            
            // Store token first
            const tokenData = {
                token: authToken,
                expiresAt: Date.now() + (24 * 60 * 60 * 1000) // 24 hours from now
            };
            localStorage.setItem('auth_token', JSON.stringify(tokenData));
            
            try {
                // Verify token by fetching current user
                const response = await authApi.getCurrentUser();
                const userData = response.data.data?.user || response.data.user;
                
                if (userData) {
                    user.value = userData;
                    console.log('HR Module - Token verified successfully');
                    
                    // Clean URL parameters
                    const url = new URL(window.location);
                    url.searchParams.delete('employee_no');
                    url.searchParams.delete('email');
                    url.searchParams.delete('auth_token');
                    url.searchParams.delete('redirect_from');
                    window.history.replaceState({}, '', url);
                    
                    console.log('HR Module - URL cleaned of auth parameters');
                    return { success: true, verified: true };
                } else {
                    throw new Error('No user data received');
                }
            } catch (error) {
                console.error('HR Module - Token verification failed:', error);
                // Clear invalid token
                localStorage.removeItem('auth_token');
                user.value = null;
                return { success: false, verified: false, error };
            }
        }
        
        console.log('HR Module - No shared auth parameters found');
        return { success: false, verified: false };
    };

    // Login function
    const login = async (credentials) => {
        try {
            loading.value = true
            const response = await authApi.login(credentials)

            // Store token and user data from backend response
            // Handle different response formats from backend
            const responseData = response.data.data || response.data
            const { token, user: userData } = responseData

            if (!token) {
                throw new Error('No token received from server')
            }

            // For Laravel Sanctum tokens, they typically don't have expiration
            // But we'll set a reasonable expiration time (24 hours)
            const tokenData = {
                token: token,
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
        user.value = null
    }

    // Get current user
    const getCurrentUser = async () => {
        try {
            const response = await authApi.getCurrentUser()
            user.value = response.data.data.user
            return response.data.data.user
        } catch (error) {
            console.error('Failed to get current user:', error)
            // If token is invalid, clear auth data
            if (error.response?.status === 401) {
                clearAuth()
            }
            throw error
        }
    }

    // Check if user is authenticated
    const checkAuth = () => {
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

    // Initialize auth state - now async
    const initAuth = async () => {
        // First check for shared authentication parameters
        const sharedAuthResult = await checkSharedAuth();
        if (sharedAuthResult.success && sharedAuthResult.verified) {
            console.log('HR Module - Shared authentication successful');
            return true;
        }
        
        // Check existing token
        const token = localStorage.getItem('auth_token')
        if (token) {
            try {
                // Verify existing token
                await getCurrentUser();
                return true;
            } catch (error) {
                console.error('HR Module - Token verification failed:', error);
                // If fetching user fails, clear invalid token
                clearAuth()
                return false;
            }
        }
        
        return false;
    }

    return {
        // State
        user,
        loading,
        isAuthenticated,

        // Methods
        login,
        clearAuth,
        getCurrentUser,
        checkAuth,
        initAuth,
        checkSharedAuth
    }
}
