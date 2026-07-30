// API Configuration
export const API_CONFIG = {
    // Base API URL - can be overridden by environment variables
    BASE_URL: import.meta.env.VITE_API_URL || 'http://localhost:8001/api',

    // Timeout settings
    TIMEOUT: 30000,

    // Default headers
    DEFAULT_HEADERS: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    },

    // File upload settings
    MAX_FILE_SIZE: 10 * 1024 * 1024, // 10MB
    ALLOWED_FILE_TYPES: [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    ]
}

// Get environment-specific configuration
export const getApiConfig = () => {
    // Try to get from environment variables
    let baseUrl = API_CONFIG.BASE_URL

    // Check for Vite environment variables (VITE_*)
    if (typeof import.meta !== 'undefined' && import.meta.env) {
        if (import.meta.env.VITE_API_URL) {
            baseUrl = import.meta.env.VITE_API_URL
        } else if (import.meta.env.VITE_APP_API_URL) {
            baseUrl = import.meta.env.VITE_APP_API_URL
        } else if (import.meta.env.VUE_APP_API_URL) {
            baseUrl = import.meta.env.VUE_APP_API_URL
        }
    }

    // Check for Vue CLI environment variables (VUE_APP_*)
    if (typeof process !== 'undefined' && process.env && process.env.VUE_APP_API_URL) {
        baseUrl = process.env.VUE_APP_API_URL
    }

    return {
        ...API_CONFIG,
        BASE_URL: baseUrl
    }
}

/**
 * Resolve API base URL at runtime so LAN clients do not call their own localhost.
 * - Relative `/api` → same host as the page (use with Vite proxy in dev).
 * - `http://localhost:8000/api` on `192.168.x.x:8080` → `http://192.168.x.x:8000/api`.
 */
export function resolveApiBaseUrl() {
    let base = getApiConfig().BASE_URL || '/api'

    if (typeof window === 'undefined') {
        return base
    }

    if (base.startsWith('/')) {
        return `${window.location.origin}${base}`
    }

    try {
        const url = new URL(base)
        const pageHost = window.location.hostname
        const isLocalApiHost = url.hostname === 'localhost' || url.hostname === '127.0.0.1'
        const isLocalPageHost = pageHost === 'localhost' || pageHost === '127.0.0.1'

        if (isLocalApiHost && !isLocalPageHost) {
            url.hostname = pageHost
            return url.toString().replace(/\/$/, '')
        }
    } catch (_) {
        // fall through
    }

    return base
}

// Export the current configuration
export const currentConfig = getApiConfig()
