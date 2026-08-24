import { ref, computed } from 'vue'
import apiService from '../Services/api.js'

// Global authentication state
const isAuthenticated = ref(false)
const user = ref(null)
const token = ref(null)

function hasControlPanelAccess(userData) {
    if (!userData) return false;
    const toBool = (v) => v === true || v === 1 || v === '1' || v === 'true';
    return toBool(userData.with_cpm_access) || toBool(userData.is_admin);
}

// Check for shared authentication parameters
async function checkSharedAuth() {
    const urlParams = new URLSearchParams(window.location.search);
    const employeeNo = urlParams.get('employee_no');
    const email = urlParams.get('email');
    const authToken = urlParams.get('auth_token');
    const redirectFrom = urlParams.get('redirect_from');
    
    console.log('Checking shared auth parameters:', { employeeNo, email, authToken: authToken ? authToken.substring(0, 20) + '...' : null, redirectFrom });
    console.log('Current URL:', window.location.href);
    
    if (employeeNo && email && authToken && redirectFrom === 'e_portal') {
        console.log('Shared auth detected from E-Portal:', { employeeNo, email });
        
        try {
            // Verify user via backend profile endpoint to check CP access rights
            localStorage.setItem('auth_token', authToken);
            const verifyResponse = await apiService.getProfile();
            
            if (verifyResponse.success && verifyResponse.data && verifyResponse.data.user) {
                const verifiedUser = verifyResponse.data.user;
                if (!hasControlPanelAccess(verifiedUser)) {
                    console.warn('Shared auth denied: User lacks Control Panel permissions');
                    localStorage.removeItem('auth_token');
                    localStorage.removeItem('user_data');
                    token.value = null;
                    user.value = null;
                    isAuthenticated.value = false;
                    return { success: false, verified: false, error: 'Access Denied' };
                }

                token.value = authToken;
                user.value = verifiedUser;
                isAuthenticated.value = true;
                localStorage.setItem('user_data', JSON.stringify(verifiedUser));

                // Clean URL parameters
                const url = new URL(window.location);
                url.searchParams.delete('employee_no');
                url.searchParams.delete('email');
                url.searchParams.delete('auth_token');
                url.searchParams.delete('redirect_from');
                window.history.replaceState({}, '', url);

                return { success: true, verified: true };
            }
        } catch (error) {
            console.error('Token verification failed during shared auth:', error);
        }

        // Clean invalid shared auth
        localStorage.removeItem('auth_token');
        localStorage.removeItem('user_data');
        token.value = null;
        user.value = null;
        isAuthenticated.value = false;
        return { success: false, verified: false };
    }
    
    return { success: false, verified: false };
}

// Fetch the real token from backend session
async function fetchRealTokenFromSession() {
    try {
        console.log('Fetching real token from session...');
        
        // Make a request to get the session data (token and user)
        const response = await fetch('/api/shared-auth-session', {
            method: 'GET',
            credentials: 'include', // Include cookies/session
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        });
        
        if (response.ok) {
            const data = await response.json();
            console.log('Session data received:', data);
            
            if (data.token && data.user && hasControlPanelAccess(data.user)) {
                // Store the real token and user data
                token.value = data.token;
                user.value = data.user;
                isAuthenticated.value = true;
                
                localStorage.setItem('auth_token', data.token);
                localStorage.setItem('user_data', JSON.stringify(data.user));
                
                console.log('Real Sanctum token set up for shared auth');
                return true;
            }
        } else {
            console.error('Failed to get session data:', response.status, response.statusText);
        }
    } catch (error) {
        console.error('Error fetching shared auth token:', error);
    }
    
    return false;
}

// Initialize authentication state from localStorage
async function initializeAuth() {
    console.log('Initializing authentication...');
    
    // First check for shared authentication parameters
    const sharedAuthResult = await checkSharedAuth();
    if (sharedAuthResult.success && sharedAuthResult.verified) {
        console.log('Shared auth successful - skipping other checks');
        return true;
    }
    
    // Check localStorage for any existing auth
    const storedToken = localStorage.getItem('auth_token')
    const storedUser = localStorage.getItem('user_data')

    if (storedToken && storedUser) {
        try {
            // Verify token is still valid by making an authenticated request
            console.log('Found stored token, verifying with backend...')
            const verifyResponse = await apiService.getProfile()
            
            if (verifyResponse.success && verifyResponse.data && verifyResponse.data.user) {
                const fetchedUser = verifyResponse.data.user
                if (!hasControlPanelAccess(fetchedUser)) {
                    console.warn('Access denied: User does not have Control Panel permissions')
                    localStorage.removeItem('auth_token')
                    localStorage.removeItem('user_data')
                    token.value = null
                    user.value = null
                    isAuthenticated.value = false
                    return false
                }

                // Token is valid and user has CP access, set auth state
                token.value = storedToken
                user.value = fetchedUser
                isAuthenticated.value = true
                console.log('Token verified - user authenticated:', user.value)
                return true;
            } else {
                // Token is invalid, clear it
                console.log('Token verification failed - clearing invalid auth')
                localStorage.removeItem('auth_token')
                localStorage.removeItem('user_data')
                token.value = null
                user.value = null
                isAuthenticated.value = false
                return false;
            }
        } catch (error) {
            console.error('Error verifying token:', error)
            // Token verification failed, clear invalid data
            localStorage.removeItem('auth_token')
            localStorage.removeItem('user_data')
            token.value = null
            user.value = null
            isAuthenticated.value = false
            return false;
        }
    }
    
    console.log('No authentication found - user needs to login')
    isAuthenticated.value = false
    return false;
}


// Login function
async function login(credentials) {
    try {
        const response = await apiService.login(credentials)

        if (response.success) {
            if (!hasControlPanelAccess(response.data.user)) {
                return { success: false, message: 'Access Denied: You do not have permission to access the Control Panel.' }
            }

            // Store authentication data
            token.value = response.data.token
            user.value = response.data.user
            isAuthenticated.value = true

            // Persist to localStorage
            localStorage.setItem('auth_token', response.data.token)
            localStorage.setItem('user_data', JSON.stringify(response.data.user))

            return { success: true, data: response.data }
        } else {
            return { success: false, message: response.message || 'Login failed' }
        }
    } catch (error) {
        console.error('Login error:', error)
        return { success: false, message: error.message || 'Login failed' }
    }
}

// Logout function
async function logout() {
    try {
        // Call logout API if authenticated
        if (isAuthenticated.value) {
            await apiService.logout()
        }
    } catch (error) {
        console.error('Logout error:', error)
    } finally {
        // Broadcast logout to all other open CP tabs
        localStorage.setItem('cp_logout_event', Date.now().toString())
        localStorage.removeItem('cp_logout_event')

        // Clear authentication state
        token.value = null
        user.value = null
        isAuthenticated.value = false

        // Clear localStorage
        localStorage.removeItem('auth_token')
        localStorage.removeItem('user_data')
    }
}

// ─── Cross-tab session synchronization ───────────────────────────────────────
// Listen for logout events from any tab of this portal (cp_logout_event)
// or from the Employee Portal (ep_logout_event), and also for raw token removal.
if (typeof window !== 'undefined') {
    window.addEventListener('storage', (event) => {
        if (
            (event.key === 'cp_logout_event' || event.key === 'ep_logout_event') &&
            event.newValue
        ) {
            // Another tab triggered a logout — clear this tab's auth state
            token.value = null
            user.value = null
            isAuthenticated.value = false
            localStorage.removeItem('auth_token')
            localStorage.removeItem('user_data')
        }
        if (event.key === 'auth_token' && !event.newValue && isAuthenticated.value) {
            // auth_token was removed in another tab
            token.value = null
            user.value = null
            isAuthenticated.value = false
        }
    })
}
// ─────────────────────────────────────────────────────────────────────────────

// Check if user is authenticated
const isLoggedIn = computed(() => isAuthenticated.value)

// Get current user
const currentUser = computed(() => user.value)

// Get current token
const authToken = computed(() => token.value)

export function useAuth() {
    return {
        // State
        isAuthenticated: isLoggedIn,
        user: currentUser,
        token: authToken,

        // Methods
        login,
        logout,
        initializeAuth,
        checkSharedAuth
    }
}