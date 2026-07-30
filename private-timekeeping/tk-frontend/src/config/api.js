// Centralized API base URL with dynamic detection for dev/IP/prod environments

function detectApiBase() {
  // 1) Explicit env config wins (build-time)
  const envUrl = import.meta.env.VITE_API_URL
  if (envUrl && envUrl.trim() !== '') {
    return envUrl.trim()
  }

  // 2) Fallbacks based on current window location
  if (typeof window === 'undefined') {
    // Safe default for non-browser contexts (build tools, SSR, etc.)
    return 'http://localhost:8001/api'
  }

  const { protocol, hostname } = window.location

  // Local development: Vite on 5173, backend on 8085
  if (hostname === 'localhost' || hostname === '127.0.0.1') {
    return `${protocol}//${hostname}:8001/api`
  }

  // IP-based / production: assume backend is on same host under /api
  return `${protocol}//${hostname}/api`
}

export const API_BASE_URL = detectApiBase()

export function apiUrl(path = '') {
  if (!path.startsWith('/')) path = `/${path}`
  return `${API_BASE_URL}${path}`
}

