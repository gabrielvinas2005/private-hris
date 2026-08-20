// Lightweight fetch wrapper for API calls
import { API_BASE_URL } from '../config/api';
import { useAuth } from '@/Composables/useAuth'


async function request(path, options = {}) {
  const { method = 'GET', headers = {}, body, params, ...otherOptions } = options;
  const opts = {
    method,
    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', ...headers },
    credentials: 'include'
  };

  // Attach Authorization header from localStorage token if available
  try {
    const rawAuthToken = localStorage.getItem('auth_token');
    const rawDevToken = localStorage.getItem('dev_auth_token');

    let token = null;
    const extractToken = (raw) => {
      if (!raw) return null;
      try {
        const parsed = JSON.parse(raw);
        return parsed?.token || raw;
      } catch (_) {
        return raw;
      }
    };

    token = extractToken(rawDevToken) || extractToken(rawAuthToken);

    if (token) {
      opts.headers = { ...opts.headers, 'Authorization': `Bearer ${token}` };
    }
  } catch (_) {
    // ignore storage errors
  }
  if (body !== undefined) {
    opts.body = typeof body === 'string' ? body : JSON.stringify(body);
  }
  // Build URL with query params for GET requests
  let url = `${API_BASE_URL}${path}`;
  if (method === 'GET') {
    // Use params if provided, otherwise use other options (excluding method, headers, body, params)
    const queryParams = params || (Object.keys(otherOptions).length > 0 ? otherOptions : null);
    
    if (queryParams && typeof queryParams === 'object') {
    const usp = new URLSearchParams();
      for (const [key, value] of Object.entries(queryParams)) {
      if (value !== undefined && value !== null && value !== '') {
        // Handle arrays by appending multiple values with the same key
        if (Array.isArray(value)) {
          value.forEach(item => {
            if (item !== undefined && item !== null && item !== '') {
              usp.append(key + '[]', String(item));
            }
          });
        } else {
          usp.append(key, String(value));
        }
      }
    }
    const qs = usp.toString();
    if (qs) {
      url += (url.includes('?') ? '&' : '?') + qs;
      }
    }
  }

  const res = await fetch(url, opts);
  const contentType = res.headers.get('content-type') || '';
  const data = contentType.includes('application/json') ? await res.json() : await res.text();
  if (!res.ok) {
    // Create error object with response data for better error handling
    const error = new Error(data && data.message ? data.message : res.statusText || 'Request failed');
    error.response = { status: res.status, statusText: res.statusText, data };
    error.status = res.status;
    throw error;
  }
  return data;
}

export const api = {
  get: (path, options) => request(path, { ...options, method: 'GET' }),
  post: (path, body, options) => request(path, { ...options, method: 'POST', body }),
  put: (path, body, options) => request(path, { ...options, method: 'PUT', body }),
  delete: (path, options) => request(path, { ...options, method: 'DELETE' }),
};

export const authApi = {
  // Login user
  login: (credentials) => api.post('/login', credentials),

  // Dev login (manual admin login)
  devLogin: (credentials) => api.post('/dev-login', credentials),

  // Access rights
  getAccessRights: (userId, params) => api.get(`/access-rights/${userId}`, { params }),

  // Logout user
  logout: () => api.post('/logout'),

  // Get current user
  getCurrentUser: () => api.get('/user'),

  // Refresh token
  refreshToken: () => api.post('/refresh')
}

// Bind composables to the api client
import { useFixSchedule } from '../Composables/useFixSchedule'
import { useShiftSchedule } from '../Composables/useShiftSchedule'
import { useAssignFixSchedule } from '../Composables/useAssignFixSchedule'
import { useLeaveCredits } from '../Composables/useLeaveCredits'
import { useLeaveMonitoring } from '../Composables/useLeaveMonitoring'
import { useLeaveTakenMonitoring } from '../Composables/useLeaveTakenMonitoring'
import { useLeaveCreditCardMonitoring } from '../Composables/useLeaveCreditCardMonitoring'
import { useOBMonitoring } from '../Composables/useOBMonitoring'
import { usePassSlipMonitoring } from '../Composables/usePassSlipMonitoring'
import { useOTMonitoring } from '../Composables/useOTMonitoring'
import { useCOCMonitoring } from '../Composables/useCOCMonitoring'
import { useWorkSuspension } from '../Composables/useWorkSuspension'
import { useBiometrics } from '../Composables/useBiometrics'
import { useProcessAttendance } from '../Composables/useProcessAttendance'
import { useTardinessReports } from '../Composables/useTardinessReports'
export const fixScheduleService = useFixSchedule(api)
export const shiftScheduleService = useShiftSchedule(api)
export const assignFixScheduleService = useAssignFixSchedule(api)
export const leaveCreditsService = useLeaveCredits(api)
export const leaveMonitoringService = useLeaveMonitoring(api)
export const leaveTakenMonitoringService = useLeaveTakenMonitoring(api)
export const leaveCreditCardService = useLeaveCreditCardMonitoring(api)
export const obMonitoringService = useOBMonitoring(api)
export const passSlipMonitoringService = usePassSlipMonitoring(api)
export const otMonitoringService = useOTMonitoring(api)
export const cocMonitoringService = useCOCMonitoring(api)
export const workSuspensionService = useWorkSuspension(api)
export const biometricsService = useBiometrics(api)
// Process Attendance Service - includes reprocessAll method
export const processAttendanceService = useProcessAttendance(api)
export const tardinessReportsService = useTardinessReports(api)

export default api;
