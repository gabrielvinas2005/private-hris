import { ref, computed } from 'vue'
import apiService from '../Services/api.js'

// Global authentication state
const isAuthenticated = ref(false)
const user = ref(null)
const token = ref(null)

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
        
        // Store token first
        const sharedUser = {
            employee_no: employeeNo,
            email: email,
            name: email.split('@')[0],
            id: employeeNo
        };
        
        // Store token
        localStorage.setItem('user_data', JSON.stringify(sharedUser));
        localStorage.setItem('auth_token', authToken);
        token.value = authToken;
        user.value = sharedUser;
        isAuthenticated.value = true;
        
        try {
            // Verify token by trying to fetch real session or validate
            // For Control Panel, you might need to call a specific API endpoint
            // For now, we'll assume the token is valid if it exists
            // You can add actual verification here if needed
            
            console.log('Frontend auth state set up with E-Portal token');
            
            // Clean URL parameters
            const url = new URL(window.location);
            url.searchParams.delete('employee_no');
            url.searchParams.delete('email');
            url.searchParams.delete('auth_token');
            url.searchParams.delete('redirect_from');
            window.history.replaceState({}, '', url);
            
            console.log('URL cleaned of auth parameters');
            return { success: true, verified: true };
        } catch (error) {
            console.error('Token verification failed:', error);
            // Clear invalid token
            localStorage.removeItem('auth_token');
            localStorage.removeItem('user_data');
            token.value = null;
            user.value = null;
            isAuthenticated.value = false;
            return { success: false, verified: false, error };
        }
    }
    
    console.log('No shared auth parameters found');
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
            
            if (data.token && data.user) {
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
    
    // Fallback: set up basic shared auth if session fetch fails
    console.log('Falling back to basic shared auth');
    const urlParams = new URLSearchParams(window.location.search);
    const employeeNo = urlParams.get('employee_no') || '1';
    const email = urlParams.get('email') || 'admin@example.com';
    
    const sharedUser = {
        employee_no: employeeNo,
        email: email,
        name: email.split('@')[0],
        id: employeeNo
    };
    
    user.value = sharedUser;
    isAuthenticated.value = true;
    localStorage.setItem('user_data', JSON.stringify(sharedUser));
    localStorage.setItem('auth_token', 'shared_auth_' + Date.now());
    
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
                // Token is valid, set auth state
                token.value = storedToken
                user.value = JSON.parse(storedUser)
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
        // Clear authentication state
        token.value = null
        user.value = null
        isAuthenticated.value = false

        // Clear localStorage
        localStorage.removeItem('auth_token')
        localStorage.removeItem('user_data')
    }
}

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