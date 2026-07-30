// Mock authentication service for development
class MockAuthService {
    constructor() {
        this.tokenKey = 'mock_token'
        this.userKey = 'mock_user'
    }

    // Generate a mock token
    generateMockToken() {
        return 'mock_token_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9)
    }

    // Mock login (always succeeds)
    async login(credentials) {
        const mockUser = {
            id: 1,
            name: 'Admin User',
            email: credentials.email || 'admin@example.com',
            is_admin: true,
            employee_no: 'EMP001'
        }

        const mockToken = this.generateMockToken()

        // Store mock data
        localStorage.setItem(this.tokenKey, mockToken)
        localStorage.setItem(this.userKey, JSON.stringify(mockUser))

        return {
            success: true,
            data: {
                user: mockUser,
                token: mockToken,
                requires_otp: false,
                next: '/dashboard'
            }
        }
    }

    // Get stored token
    getToken() {
        return localStorage.getItem(this.tokenKey)
    }

    // Get stored user
    getUser() {
        const userData = localStorage.getItem(this.userKey)
        return userData ? JSON.parse(userData) : null
    }

    // Check if authenticated
    isAuthenticated() {
        return !!this.getToken()
    }

    // Logout
    logout() {
        localStorage.removeItem(this.tokenKey)
        localStorage.removeItem(this.userKey)
    }

    // Auto-login for development
    autoLogin() {
        if (!this.isAuthenticated()) {
            return this.login({ email: 'admin@example.com' })
        }
        return Promise.resolve({ success: true })
    }
}

export default new MockAuthService()
