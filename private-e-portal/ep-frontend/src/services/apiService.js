import axios from 'axios'
import { currentConfig } from '../config/api.js'

// Downloadables API methods
export const downloadablesApiService = {
    async getDownloadables() {
        const response = await apiClient.get('/downloadables')
        return response.data
    },

    async downloadFile(id, fileName) {
        const response = await apiClient.get(`/downloadables/${id}/download`, {
            responseType: 'blob'
        })
        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', fileName)
        document.body.appendChild(link)
        link.click()
        link.parentElement.removeChild(link)
    },

    async previewFile(id) {
        const response = await apiClient.get(`/downloadables/${id}/preview`, {
            responseType: 'blob'
        })
        const contentType = response.headers['content-type'] || 'application/octet-stream'
        const blob = new Blob([response.data], { type: contentType })
        return {
            url: window.URL.createObjectURL(blob),
            contentType,
            size: response.data.size
        }
    }
}

// Training Record API methods
export const trainingRecordApiService = {
    async getRecords() {
        const response = await apiClient.get('/training-records')
        return response.data
    },

    async createRecord(payload) {
        const response = await apiClient.post('/training-records', payload)
        return response.data
    }
}

//Document Request API methods
export const documentRequestApiService = {
    async getRequests() {
        const response = await apiClient.get('/document-requests')
        return response.data
    },

    async submitRequest(payload) {
        const response = await apiClient.post('/document-requests', payload)
        return response.data
    },

    async cancelRequest(id) {
        const response = await apiClient.delete(`/document-requests/${id}`)
        return response.data
    },

    async updateStatus(id, status, remarks = null) {
        const response = await apiClient.patch(`/document-requests/${id}/status`, { status, remarks })
        return response.data
    }
}


// Create axios instance with base configuration
const apiClient = axios.create({
    baseURL: currentConfig.BASE_URL,
    timeout: currentConfig.TIMEOUT,
    headers: currentConfig.DEFAULT_HEADERS
})

// Request interceptor to add auth token
apiClient.interceptors.request.use(
    (config) => {
        const token = localStorage.getItem('auth_token')
        if (token) {
            config.headers.Authorization = `Bearer ${token}`
        }
        return config
    },
    (error) => {
        return Promise.reject(error)
    }
)

// Response interceptor to handle errors
apiClient.interceptors.response.use(
    (response) => {
        return response
    },
    (error) => {
        if (error.response?.status === 401) {
            // Handle unauthorized access
            localStorage.removeItem('auth_token')
            window.location.href = '/login'
        }
        return Promise.reject(error)
    }
)

// DTR API methods
export const dtrApiService = {
    // Get today status summary
    async getTodayStatus(userId) {
        try {
            const response = await apiClient.get(`/daily-time-records/today-status/${userId}`)
            return response.data
        } catch (error) {
            console.error('Error fetching today status:', error)
            throw error
        }
    },

    // Perform web clock punch
    async webClockPunch(payload) {
        try {
            const response = await apiClient.post('/daily-time-records/web-clock', payload)
            return response.data
        } catch (error) {
            console.error('Error recording web clock punch:', error)
            throw error
        }
    },

    /**
     * Fetch portal feature configuration from Control Panel backend.
     * Returns { pass_slip_monthly_limit, enable_web_clock, require_selfie, enforce_geofence }.
     * Falls back to safe defaults if the request fails so the portal always remains functional.
     */
    async getPortalTimekeepingSettings() {
        const defaults = {
            enable_web_clock: true,
            enable_biometric: true,
            require_selfie: true,
            enforce_geofence: true,
        }
        try {
            const cpBaseUrl = import.meta.env.VITE_CP_API_URL || 'http://localhost:8002/api'
            const response = await axios.get(`${cpBaseUrl}/portal-settings/timekeeping`, { timeout: 5000 })
            const data = response.data?.data || {}
            return {
                enable_web_clock: data.enable_web_clock ?? defaults.enable_web_clock,
                enable_biometric: data.enable_biometric ?? defaults.enable_biometric,
                require_selfie:   data.require_selfie   ?? defaults.require_selfie,
                enforce_geofence: data.enforce_geofence ?? defaults.enforce_geofence,
            }
        } catch (error) {
            console.warn('Portal settings fetch failed — using defaults:', error.message)
            return defaults
        }
    },

    // Get DTR list for employee (by user ID)
    async getDTRList(userId) {
        try {
            const response = await apiClient.get(`/daily-time-records/${userId}`)
            return response.data
        } catch (error) {
            console.error('Error fetching DTR list:', error)
            throw error
        }
    },

    // Get DTR list for employee (by employee number - preferred method)
    async getDTRListByEmployeeNo(employeeNo) {
        try {
            const response = await apiClient.get(`/daily-time-records/employee/${employeeNo}`)
            return response.data
        } catch (error) {
            console.error('Error fetching DTR list by employee number:', error)
            throw error
        }
    },

    // Get DTR list - smart method that uses employee number if available, otherwise user ID
    async getDTRListSmart(userId, employeeNo = null) {
        try {
            // If we have employee number, use it directly (more efficient)
            if (employeeNo) {
                // Using employee number for DTR lookup
                return await this.getDTRListByEmployeeNo(employeeNo)
            }

            // Fallback to user ID
            // Using user ID for DTR lookup
            return await this.getDTRList(userId)
        } catch (error) {
            console.error('Error fetching DTR list:', error)
            throw error
        }
    },

    // Get DTR detail view
    async getDTRDetail(employeeId, payrollPeriodId) {
        try {
            const response = await apiClient.get(`/daily-time-records/${employeeId}/employee/${payrollPeriodId}`)
            return response.data
        } catch (error) {
            console.error('Error fetching DTR detail:', error)
            throw error
        }
    },
    async getDTRById(employeeId, payrollPeriodId) {
        return this.getDTRDetail(employeeId, payrollPeriodId)
    },

    // Print DTR as PDF
    async printDTR(employeeId, payrollPeriodId) {
        try {
            const response = await apiClient.get(`/daily-time-records/${employeeId}/print/${payrollPeriodId}`, {
                responseType: 'blob'
            })
            return response.data
        } catch (error) {
            console.error('Error printing DTR:', error)
            throw error
        }
    },

    // Get employee logs view
    async getEmployeeLogs(employeeId) {
        try {
            const response = await apiClient.get(`/daily-time-records/${employeeId}/logs`)
            return response.data
        } catch (error) {
            console.error('Error fetching employee logs:', error)
            throw error
        }
    },

    // Get time logs for specific date range
    async getTimeLogs(employeeId, fromDate, toDate, options = {}) {
        try {
            const params = {}
            if (options.skipBioSync) {
                params.skip_bio_sync = 1
            }

            const response = await apiClient.get(
                `/daily-time-records/${employeeId}/logs/${fromDate}/${toDate}`,
                {
                    params,
                    timeout: options.skipBioSync ? 60000 : undefined
                }
            )
            return response.data
        } catch (error) {
            console.error('Error fetching time logs:', error)
            throw error
        }
    },

    // Store DTR logs with attachments
    async storeDTRLogs(employeeId, formData) {
        try {
            const response = await apiClient.post(`/daily-time-records/${employeeId}`, formData, {
                withCredentials: true
            })
            return response.data
        } catch (error) {
            const details = error?.response?.data || error?.message
            console.error('Error storing DTR logs:', details)
            throw error
        }
    },

    // List employee DTR correction applications
    async getDTRApplications(userId) {
        try {
            const response = await apiClient.get(`/dtr-applications/${userId}`)
            return response.data
        } catch (error) {
            console.error('Error loading DTR applications:', error)
            throw error
        }
    },

    // Payroll periods for new DTR application
    async getDTRApplicationPayrollPeriods(employeeId) {
        try {
            const response = await apiClient.get(`/dtr-applications/employee/${employeeId}/payroll-periods`)
            return response.data
        } catch (error) {
            console.error('Error loading payroll periods:', error)
            throw error
        }
    },

    // Submit DTR application with payroll period and attachment
    async submitDTRApplication(employeeId, formData) {
        try {
            const response = await apiClient.post(`/dtr-applications/employee/${employeeId}`, formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            })
            return response.data
        } catch (error) {
            console.error('Error submitting DTR application:', error)
            throw error
        }
    },

    async updateDTRApplication(requestId, formData) {
        try {
            const response = await apiClient.post(`/dtr-applications/request/${requestId}`, formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            })
            return response.data
        } catch (error) {
            console.error('Error updating DTR application:', error)
            throw error
        }
    },

    // Download application-level DTR attachment
    async downloadApplicationAttachment(requestId) {
        try {
            const response = await apiClient.get(`/dtr-applications/request/${requestId}/download`)
            return response.data
        } catch (error) {
            console.error('Error downloading application attachment:', error)
            throw error
        }
    },

    async downloadApprovedDtr(requestId) {
        try {
            const response = await apiClient.get(`/dtr-applications/request/${requestId}/approved-dtr`)
            return response.data
        } catch (error) {
            console.error('Error downloading approved DTR:', error)
            throw error
        }
    },

    // Check if current user is a DTR approver (lightweight)
    async checkDtrApproverAccess(userId) {
        try {
            const response = await apiClient.get(`/dtr-approver-access/${userId}`)
            return response.data
        } catch (error) {
            console.error('Error checking DTR approver access:', error)
            throw error
        }
    },

    // Load DTR requests for approvers
    async loadDTRRequests(userId) {
        try {
            const response = await apiClient.get(`/review-daily-time-records/${userId}`)
            return response.data
        } catch (error) {
            console.error('Error loading DTR requests:', error)
            throw error
        }
    },

    // Review DTR request
    async reviewDTRRequest(requestId) {
        try {
            const response = await apiClient.get(`/review-daily-time-records/${requestId}/add`)
            return response.data
        } catch (error) {
            console.error('Error reviewing DTR request:', error)
            throw error
        }
    },

    // Approve/disapprove DTR request (type 1 = save time log corrections via POST)
    async approveDTRRequest(requestId, typeId, data = {}) {
        try {
            const url = `/review-daily-time-records/${requestId}/approve/${typeId}`
            const response = Number(typeId) === 1
                ? await apiClient.post(url, data, {
                    headers: { 'Content-Type': 'multipart/form-data' }
                })
                : await apiClient.get(url)
            return response.data
        } catch (error) {
            console.error('Error approving DTR request:', error)
            throw error
        }
    },

    // Download DTR attachment
    async downloadAttachment(timeDataId) {
        try {
            const response = await apiClient.get(`/review-daily-time-records/${timeDataId}/download`)
            return response.data
        } catch (error) {
            console.error('Error downloading attachment:', error)
            throw error
        }
    }
}

export const serviceRenderedApiService = {
    async getApplications(userId) {
        const response = await apiClient.get(`/service-rendered-applications/${userId}`)
        return response.data
    },

    async submitApplication(payload) {
        const response = await apiClient.post('/service-rendered-applications', payload)
        return response.data
    },

    async updateApplication(requestId, payload) {
        const response = await apiClient.put(`/service-rendered-applications/${requestId}`, payload)
        return response.data
    },

    async checkApproverAccess(userId) {
        const response = await apiClient.get(`/service-rendered-approver-access/${userId}`)
        return response.data
    },

    async loadRequests(userId) {
        const response = await apiClient.get(`/review-service-rendered/${userId}`)
        return response.data
    },

    async reviewRequest(requestId) {
        const response = await apiClient.get(`/review-service-rendered/${requestId}/add`)
        return response.data
    },

    async approveRequest(requestId, typeId) {
        const response = await apiClient.get(`/review-service-rendered/${requestId}/approve/${typeId}`)
        return response.data
    },

    async downloadCertificate(requestId) {
        const response = await apiClient.get(`/service-rendered-applications/${requestId}/certificate`)
        return response.data
    }
}

export const accomplishmentApiService = {
    async getApplications(userId) {
        try {
            const response = await apiClient.get(`/accomplishment-applications/${userId}`)
            return response.data
        } catch (error) {
            console.error('Error fetching accomplishment applications:', error)
            throw error
        }
    },

    async checkApproverAccess(userId) {
        try {
            const response = await apiClient.get(`/accomplishment-approver-access/${userId}`)
            return response.data
        } catch (error) {
            console.error('Error checking accomplishment approver access:', error)
            throw error
        }
    },

    async loadRequests(userId) {
        try {
            const response = await apiClient.get(`/review-accomplishment-reports/${userId}`)
            return response.data
        } catch (error) {
            console.error('Error loading accomplishment requests:', error)
            throw error
        }
    },

    async reviewRequest(requestId) {
        try {
            const response = await apiClient.get(`/review-accomplishment-reports/${requestId}/add`)
            return response.data
        } catch (error) {
            console.error('Error reviewing accomplishment request:', error)
            throw error
        }
    },

    async approveRequest(requestId, typeId) {
        try {
            const response = await apiClient.get(`/review-accomplishment-reports/${requestId}/approve/${typeId}`)
            return response.data
        } catch (error) {
            console.error('Error approving accomplishment request:', error)
            throw error
        }
    },

    async downloadApprovedReport(taskId) {
        try {
            const response = await apiClient.get(`/accomplishment-applications/report/${taskId}/approved-report`)
            return response.data
        } catch (error) {
            console.error('Error downloading approved accomplishment report:', error)
            throw error
        }
    },

    async downloadAttachment(attachmentId) {
        try {
            const response = await apiClient.get(`/employee-non-dtr/attachment/${attachmentId}/download`, {
                responseType: 'blob'
            })
            return response.data
        } catch (error) {
            console.error('Error downloading accomplishment attachment:', error)
            throw error
        }
    }
}

// Employee API methods
export const employeeApiService = {
    // Get employee information
    async getEmployeeInfo(userId) {
        try {
            const response = await apiClient.get(`/employees/${userId}`)
            return response.data
        } catch (error) {
            console.error('Error fetching employee info:', error)
            throw error
        }
    }
}

// WFH Attendance API methods
export const wfhAttendanceApiService = {
    // Get employee information for WFH attendance
    async getEmployeeInfo(userId) {
        try {
            const response = await apiClient.get(`/wfh-attendance/${userId}/employee-info`)
            return response.data
        } catch (error) {
            console.error('Error fetching employee info for WFH attendance:', error)
            throw error
        }
    },

    // Get payroll periods for employee
    async getPayrollPeriods(employeeId) {
        try {
            const response = await apiClient.get(`/wfh-attendance/${employeeId}/payroll-periods`)
            return response.data
        } catch (error) {
            console.error('Error fetching payroll periods:', error)
            throw error
        }
    },

    // Get time data for specific period
    async getTimeData(employeeId, payrollPeriodId) {
        try {
            const response = await apiClient.get(`/wfh-attendance/${employeeId}/time-data/${payrollPeriodId}`)
            return response.data
        } catch (error) {
            console.error('Error fetching WFH time data:', error)
            throw error
        }
    },

    // Store WFH attendance data
    async store(employeeId, data) {
        try {
            const response = await apiClient.post(`/wfh-attendance/${employeeId}`, data)
            return response.data
        } catch (error) {
            console.error('Error storing WFH attendance:', error)
            throw error
        }
    },

    // Get attendance summary
    async getAttendanceSummary(employeeId, payrollPeriodId) {
        try {
            const response = await apiClient.get(`/wfh-attendance/${employeeId}/summary/${payrollPeriodId}`)
            return response.data
        } catch (error) {
            console.error('Error fetching WFH attendance summary:', error)
            throw error
        }
    },

    // Delete attendance record
    async deleteRecord(employeeId, timeDataId) {
        try {
            const response = await apiClient.delete(`/wfh-attendance/${employeeId}/record/${timeDataId}`)
            return response.data
        } catch (error) {
            console.error('Error deleting WFH attendance record:', error)
            throw error
        }
    },

    // Get today's WFH status
    async getTodayStatus(employeeId, params = {}) {
        try {
            const response = await apiClient.get(`/wfh-attendance/${employeeId}/today-status`, {
                params: params // Pass query parameters
            })
            return response.data
        } catch (error) {
            console.error('Error fetching today\'s WFH status:', error)
            throw error
        }
    },

    // Time In
    async timeIn(employeeId, data = {}) {
        try {
            const response = await apiClient.post(`/wfh-attendance/${employeeId}/time-in`, data)
            return response.data
        } catch (error) {
            console.error('Error recording time in:', error)
            throw error
        }
    },

    // Time Out
    async timeOut(employeeId, data = {}) {
        try {
            const response = await apiClient.post(`/wfh-attendance/${employeeId}/time-out`, data)
            return response.data
        } catch (error) {
            console.error('Error recording time out:', error)
            throw error
        }
    },

    // Break In
    async breakIn(employeeId, data = {}) {
        try {
            const response = await apiClient.post(`/wfh-attendance/${employeeId}/break-in`, data)
            return response.data
        } catch (error) {
            console.error('Error recording break in:', error)
            throw error
        }
    },

    // Break Out
    async breakOut(employeeId, data = {}) {
        try {
            const response = await apiClient.post(`/wfh-attendance/${employeeId}/break-out`, data)
            return response.data
        } catch (error) {
            console.error('Error recording break out:', error)
            throw error
        }
    }
}

// Auth API methods
export const authApiService = {
    // Login
    async login(credentials) {
        try {
            const response = await apiClient.post('/login', credentials)
            return response.data
        } catch (error) {
            console.error('Error during login:', error)
            throw error
        }
    },

    // Logout
    async logout() {
        try {
            const response = await apiClient.post('/logout')
            return response.data
        } catch (error) {
            console.error('Error during logout:', error)
            throw error
        }
    },

    // Get current user
    async getCurrentUser() {
        try {
            const response = await apiClient.get('/user')
            return response.data
        } catch (error) {
            console.error('Error fetching current user:', error)
            throw error
        }
    }
}

export default apiClient
