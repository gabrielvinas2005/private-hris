// Centralized API base URL — Timekeeping module always targets port 8080

function detectApiBase() {
  // On localhost, always use port 8080 (TK Backend) regardless of any env var.
  // This prevents stale VITE_API_URL values (e.g. 8000 from E-Portal) from
  // misdirecting requests during local development.
  if (typeof window !== 'undefined') {
    const { protocol, hostname } = window.location
    if (hostname === 'localhost' || hostname === '127.0.0.1') {
      return `${protocol}//${hostname}:8080/api`
    }
  }

  // Non-browser (build tools / SSR) safe default
  if (typeof window === 'undefined') {
    return 'http://localhost:8080/api'
  }

  // IP-based / production: use explicit env var, then hostname fallback
  const envUrl = import.meta.env.VITE_API_URL
  if (envUrl && envUrl.trim() !== '') {
    return envUrl.trim()
  }

  const { protocol, hostname } = window.location
  return `${protocol}//${hostname}/api`
}

export const API_BASE_URL = detectApiBase()

export function apiUrl(path = '') {
  if (!path.startsWith('/')) path = `/${path}`
  return `${API_BASE_URL}${path}`
}

