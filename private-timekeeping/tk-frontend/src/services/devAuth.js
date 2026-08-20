import { API_BASE_URL } from '../config/api';

export class DevAuthService {
    constructor() {
        this.baseUrl = API_BASE_URL;
    }

    // Check if dev auth should be used
    shouldUseDevAuth() {
        if (import.meta.env.MODE !== 'development' && import.meta.env.MODE !== 'dev') {
            return false;
        }
        return import.meta.env.VITE_DEV_AUTH_ENABLED !== 'false';
    }

    // Clean fetch for dev authentication (avoids header encoding issues)
    async cleanFetch(url, options = {}) {
        const defaultOptions = {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            credentials: 'include'
        };

        const mergedOptions = {
            ...defaultOptions,
            ...options,
            headers: {
                ...defaultOptions.headers,
                ...options.headers
            }
        };

        try {
            const response = await fetch(url, mergedOptions);
            const contentType = response.headers.get('content-type') || '';
            const data = contentType.includes('application/json') ? await response.json() : await response.text();
            
            if (!response.ok) {
                const message = data && data.message ? data.message : response.statusText;
                throw new Error(message || 'Request failed');
            }
            
            return data;
        } catch (error) {
            console.error('DevAuth clean fetch error:', error);
            throw error;
        }
    }

    // Dev login using clean fetch with credentials
    async devLogin(credentials) {
        try {
            console.log('DevAuth Service - Attempting dev login with credentials...');
            
            const url = `${this.baseUrl}/dev-login`;
            const response = await this.cleanFetch(url, {
                body: JSON.stringify(credentials)
            });
            
            if (response && response.data) {
                console.log('DevAuth Service - Dev login successful');
                return response.data;
            }
            
            console.log('DevAuth Service - Dev login failed - no valid response');
            return null;
        } catch (error) {
            console.log('DevAuth Service - Dev login failed:', error.message);
            return null;
        }
    }

    // Store dev auth token
    storeDevAuthToken(authData) {
        if (authData && authData.token && authData.user) {
            const tokenData = {
                token: String(authData.token),
                expiresAt: Date.now() + (24 * 60 * 60 * 1000), // 24 hours from now
                user: authData.user,
                isDevAuth: true
            };
            
            localStorage.setItem('dev_auth_token', JSON.stringify(tokenData));
            console.log('DevAuth Service - Token stored successfully');
        }
    }

    // Get dev auth token
    getDevAuthToken() {
        try {
            const tokenData = localStorage.getItem('dev_auth_token');
            if (!tokenData) return null;

            const parsed = JSON.parse(tokenData);
            
            // Check if token is expired
            if (parsed.expiresAt && Date.now() > parsed.expiresAt) {
                this.clearDevAuthToken();
                return null;
            }
            
            return parsed;
        } catch (error) {
            console.error('DevAuth Service - Error parsing token:', error);
            this.clearDevAuthToken();
            return null;
        }
    }

    // Check if dev auth is valid
    isDevAuthValid() {
        const tokenData = this.getDevAuthToken();
        return !!(tokenData && tokenData.token && tokenData.user);
    }

    // Clear dev auth token
    clearDevAuthToken() {
        localStorage.removeItem('dev_auth_token');
        console.log('DevAuth Service - Token cleared');
    }

    // Regular logout using clean fetch
    async devLogout() {
        try {
            console.log('DevAuth Service - Attempting dev logout...');
            
            const tokenData = this.getDevAuthToken();
            if (!tokenData) {
                console.log('DevAuth Service - No dev token found for logout');
                return true;
            }

            const url = `${this.baseUrl}/logout`;
            const response = await this.cleanFetch(url, {
                headers: {
                    'Authorization': `Bearer ${tokenData.token}`
                }
            });
            
            console.log('DevAuth Service - Dev logout successful');
            this.clearDevAuthToken();
            return true;
        } catch (error) {
            console.log('DevAuth Service - Dev logout failed:', error.message);
            // Clear token anyway
            this.clearDevAuthToken();
            return false;
        }
    }
}

// Export singleton instance
export const devAuthService = new DevAuthService();
