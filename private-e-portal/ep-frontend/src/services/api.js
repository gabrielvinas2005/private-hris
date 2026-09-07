// API service for communicating with Laravel backend
import { resolveApiBaseUrl } from '../config/api.js'

// API Routes Configuration
const API_ROUTES = {
    // Authentication routes
    auth: {
        login: '/login',
        logout: '/logout',
        user: '/user',
        sanctum: '/sanctum/csrf-cookie'
    },
    // Notification routes (using announcements only)

    // Employee 201 File routes
    employee201: {
        get: (userId) => `/201-files/${userId}`,
        update: (userId) => `/201-file-add/${userId}`,
        updates: (userId) => `/201-file-updates/${userId}`,
        addUpdate: (userId, employeeId) => `/201-file-updates-add/${userId}/${employeeId}`,
        submitUpdate: (userId, requestId) => `/201-file-updates-add/${userId}/${requestId}`,
        ipcrView: (id) => `/201-ipcr-view/${id}`,
        schedule: '/update-201-schedule',
        saveSchedule: (id) => `/update-201-schedule/${id}`,
        deleteSchedule: (id) => `/update-201-schedule/${id}`,
        export: '/employee-file/export'
    },

    // Dashboard routes
    dashboard: {
        get: (userId) => `/dashboard/${userId}`,
        announcements: '/announcements'
    },

    // Leave routes
    leave: {
        dashboard: (userId) => `/leaves/${userId}`,
        form: (id, view) => `/leave-application/${id}/${view}`,
        submit: (id) => `/leaves/${id}`,
        delete: (id) => `/leaves/${id}`,
        process: (id, processId, remarks) => `/leaves/${id}/process/${processId}/${encodeURIComponent(remarks || '')}`,
        downloadAttachment: (id) => `/leave-attachment-download/${id}`,
        downloadCancelledDoc: (id) => `/leave-cancel-attachment-download/${id}`,
        print: (id) => `/leaves/${id}/print`,
        credits: '/leave-credits',
        monetization: {
            get: (userId) => `/leave-monetization/${userId}`,
            store: '/leave-monetization',
            delete: (id) => `/leave-monetization/${id}`,
            process: (id, typeId, remarks) => `/leave-monetization/${id}/process/${typeId}/${encodeURIComponent(remarks || '')}`,
            attachments: (id) => `/leave-monetization-attachment/${id}`,
            downloadAttachment: (id) => `/leave-monetization-attachment-download/${id}`
        }
    },

    // Official Business routes
    officialBusiness: {
        get: (userId) => `/official-business-applications/${userId}`,
        store: '/official-business-applications',
        storeUnofficial: '/official-business-applications/unofficial',
        delete: (id) => `/official-business-applications/${id}`,
        approve: (id, remarks) => `/official-business-applications/${id}/approve/${encodeURIComponent(remarks || '')}`,
        disapprove: (id, remarks) => `/official-business-applications/${id}/disapprove/${encodeURIComponent(remarks || '')}`,
        cancel: (id, remarks) => `/official-business-applications/${id}/cancel/${encodeURIComponent(remarks || '')}`,
        attachments: (id) => `/official-business-attachments/${id}`,
        downloadAttachment: (id) => `/official-business-attachment-download/${id}`,
        print: (id) => `/official-business-print/${id}`,
        printUnofficial: (id) => `/unofficial-business-print/${id}`,
        printOrder: (id) => `/order-business-print/${id}`,
        printRequestPickup: (id) => `/request-pickup-print/${id}`,
        printRequestPickupWord: (id) => `/request-pickup-word/${id}`,
        printRequestPickupExcel: (id) => `/request-pickup-excel/${id}`,
        printTravelAuthorityWord: (id) => `/travel-authority-word/${id}`,
        printTravelAuthorityPersonalWord: (id) => `/travel-authority-personal-word/${id}`,
        printTravelOrderLDSDWord: (id) => `/travel-order-ldsd-word/${id}`,
        printOfficialBusinessWord: (id) => `/official-business-word/${id}`,
        printOfficialBusinessExcel: (id) => `/official-business-excel/${id}`,
        getEmployeeInfoForPickup: (userId) => `/employee-info-for-pickup/${userId}`
    },

    // WFH Application routes
    wfhApplication: {
        list: '/wfh-applications',
        get: (id) => `/wfh-applications/${id}`,
        store: '/wfh-applications',
        update: (id) => `/wfh-applications/${id}`,
        cancel: (id) => `/wfh-applications/${id}/cancel`,
        approve: (id) => `/wfh-applications/${id}/approve`,
        disapprove: (id) => `/wfh-applications/${id}/disapprove`
    },

    // Payslip routes
    payslip: {
        list: (userId) => `/payslips/${userId}`,
        details: (employeeId, payrollId) => `/payslips/${employeeId}/view/${payrollId}`,
        print: (employeeId, payrollId) => `/payslips/${employeeId}/print/${payrollId}`
    },

    // Overtime routes
    overtime: {
        get: (userId) => `/overtime-applications/${userId}`,
        store: '/overtime-applications/store',
        update: '/overtime-applications/store',
        delete: (id) => `/overtime-applications/${id}`,
        approve: '/overtime-applications/approve',
        disapprove: (id, remarks) => `/overtime-applications/${id}/disapprove/${encodeURIComponent(remarks || '')}`,
        cancel: (id, empId, remarks) => `/overtime-applications/${id}/cancel/${empId}/${encodeURIComponent(remarks || '')}`,
        attachments: (id) => `/overtime-applications/${id}/attachments`,
        removeAttachment: (id) => `/overtime-applications/attachments/${id}`,
        downloadAttachment: (id) => `/overtime-applications/attachments/${id}/download`,
        downloadApproval: (id) => `/overtime-applications/attachments/${id}/download-approval`,
        monitoring: '/overtime-applications/monitoring',
        authorizationForm: '/overtime-authorization-request',
        authorizationFormWord: '/overtime-authorization-request-word',
        authorizationFormExcel: '/overtime-authorization-request-excel'
    },

    // PDS routes
    pds: {
        download: (employeeId) => `/pds/${employeeId}/download`
    }
}

class ApiService {
    constructor() {
        this.baseURL = resolveApiBaseUrl()
        this.isInitialized = false
    }

    /** Re-resolve base URL (e.g. after opening app from a different host on the LAN). */
    refreshBaseUrl() {
        this.baseURL = resolveApiBaseUrl()
    }

    // Initialize Sanctum authentication (get CSRF cookie)
    // In e_portal/ep-frontend/src/services/api.js, line 140:
    async initSanctum() {
        if (this.isInitialized) return

        try {
            // Call CSRF cookie endpoint at the root, not under /api
            await fetch('/sanctum/csrf-cookie', {  // Remove this.baseURL prefix
                method: 'GET',
                credentials: 'include',
                headers: {
                    'Accept': 'application/json',
                }
            })
            this.isInitialized = true
        } catch (error) {
            console.error('Sanctum initialization failed:', error)
        }
    }

    // Helper method to make HTTP requests
    async request(endpoint, options = {}) {
        // ALWAYS initialize Sanctum before making requests
        await this.initSanctum()

        const url = `${this.baseURL}${endpoint}`

        const { skipAuth, ...fetchOptions } = options
        const pathOnly = String(endpoint).split('?')[0]
        const omitBearer = Boolean(skipAuth) || pathOnly === '/verify-otp'
        const token = omitBearer ? null : localStorage.getItem('auth_token')

        // Get CSRF token from cookie
        const getCSRFToken = () => {
            const name = 'XSRF-TOKEN='
            const decodedCookie = decodeURIComponent(document.cookie)
            const cookieArray = decodedCookie.split(';')
            for (let i = 0; i < cookieArray.length; i++) {
                let cookie = cookieArray[i]
                while (cookie.charAt(0) === ' ') {
                    cookie = cookie.substring(1)
                }
                if (cookie.indexOf(name) === 0) {
                    return cookie.substring(name.length, cookie.length)
                }
            }
            return null
        }

        const csrfToken = getCSRFToken()

        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest', // Required for Laravel
                ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
                ...(csrfToken ? { 'X-XSRF-TOKEN': csrfToken } : {}), // CSRF token
                ...fetchOptions.headers
            },
            credentials: 'include' // ESSENTIAL for session cookies
        }

        // Ensure headers are deep-merged so defaults (like Content-Type) are preserved
        const config = {
            ...defaultOptions,
            ...fetchOptions,
            headers: { ...defaultOptions.headers, ...(fetchOptions.headers || {}) }
        }

        // If sending FormData, let the browser set the Content-Type with boundary
        if (config.body instanceof FormData) {
            if (config.headers && 'Content-Type' in config.headers) {
                delete config.headers['Content-Type']
            }
        }

        const maxRetries = 3
        let attempt = 0

        while (true) {
            try {
                const response = await fetch(url, config)

                if (response.status === 401) {
                    throw new Error('Authentication required')
                }

                // Normalize successful empty responses (e.g., 204 No Content)
                if (response.ok && (response.status === 204 || response.headers.get('Content-Length') === '0')) {
                    return { success: true }
                }

                if (response.status === 429 && attempt < maxRetries) {
                    const retryAfter = parseInt(response.headers.get('Retry-After') || '0', 10)
                    const backoffMs = retryAfter > 0 ? retryAfter * 1000 : Math.min(4000, 500 * Math.pow(2, attempt))
                    attempt += 1
                    await new Promise(r => setTimeout(r, backoffMs))
                    continue
                }

                if (!response.ok) {
                    // Try to surface backend error details to help debugging
                    let details = ''
                    try {
                        const ct = response.headers.get('Content-Type') || ''
                        if (ct.includes('application/json')) {
                            const errJson = await response.json()
                            details = errJson?.message || errJson?.error || JSON.stringify(errJson)
                        } else {
                            details = await response.text()
                        }
                    } catch (_) {
                        // ignore parsing errors
                    }
                    throw new Error(`HTTP error! status: ${response.status}${details ? ` - ${details}` : ''}`)
                }

                // Handle blob responses (for PDFs, etc.)
                if (options.responseType === 'blob') {
                    return await response.blob()
                }

                // Attempt to parse JSON; if empty or invalid, treat as success
                try {
                    const data = await response.json()
                    return data ?? { success: true }
                } catch (_) {
                    return { success: true }
                }
            } catch (error) {
                if (attempt < maxRetries && /NetworkError|Failed to fetch/i.test(String(error))) {
                    attempt += 1
                    const backoffMs = Math.min(4000, 500 * Math.pow(2, attempt))
                    await new Promise(r => setTimeout(r, backoffMs))
                    continue
                }
                console.error('API request failed:', error)
                throw error
            }
        }
    }

    // Authentication methods
    async login(email, password) {
        await this.initSanctum()

        return this.request('/login', {
            method: 'POST',
            body: JSON.stringify({ email, password })
        })
    }

    async logout() {
        return this.request('/logout', {
            method: 'POST'
        })
    }

    // Check authentication status - FIXED
    async checkAuth() {
        try {
            return this.request('/user') // ✅ Fixed route
        } catch (error) {
            if (error.message === 'Authentication required') {
                return null
            }
            throw error
        }
    }

    // Get current user profile - FIXED
    async getCurrentUser() {
        try {
            return this.request('/user') // ✅ Fixed route
        } catch (error) {
            if (error.message === 'Authentication required') {
                return null
            }
            throw error
        }
    }

    // Tab access (controls which internal Vue tabs/sections are visible)
    async getUserTabAccess() {
        return this.request('/user-tab-access')
    }

    async getApproverPipelineAccess(userId) {
        return this.request(`/approver-pipeline-access/${userId}`)
    }

    async getDtrApproverAccess(userId) {
        return this.request(`/review-daily-time-records/${userId}`)
    }

    async approveDtrRequest(id) {
        return this.request(`/review-daily-time-records/${id}/approve/2`, { method: 'POST' })
    }

    async disapproveDtrRequest(id) {
        return this.request(`/review-daily-time-records/${id}/approve/3`, { method: 'POST' })
    }

    async getWfhApplications() {
        return this.request('/wfh-applications')
    }

    async approveWfhApplication(id) {
        return this.request(`/wfh-applications/${id}/approve`, { method: 'POST' })
    }

    async disapproveWfhApplication(id, reason = '') {
        return this.request(`/wfh-applications/${id}/disapprove`, {
            method: 'POST',
            body: JSON.stringify({ disapproved_reason: reason })
        })
    }

    async getServiceRenderedApprovals(userId) {
        return this.request(`/review-service-rendered/${userId}`)
    }

    async approveServiceRendered(id) {
        return this.request(`/review-service-rendered/${id}/approve/2`, { method: 'POST' })
    }

    async disapproveServiceRendered(id) {
        return this.request(`/review-service-rendered/${id}/approve/3`, { method: 'POST' })
    }


    // Employee 201 file methods
    async getEmployee201File(userId) {
        return this.request(`/201-files/${userId}`)
    }

    async getEmployeeFileUpdate(userId) {
        return this.request(`/201-file-add/${userId}`)
    }

    async get201FileUpdates(userId) {
        return this.request(`/201-file-updates/${userId}`)
    }

    async add201FileUpdate(userId, employeeId) {
        return this.request(`/201-file-updates-add/${userId}/${employeeId}`)
    }

    async submit201FileUpdate(userId, requestId, data) {
        return this.request(`/201-file-updates-add/${userId}/${requestId}`, {
            method: 'POST',
            body: JSON.stringify(data)
        })
    }

    async getIPCRView(id) {
        return this.request(`/201-ipcr-view/${id}`)
    }

    async autosavePDS(employeeId, formData) {
        return this.request(`/201-file-autosave/${employeeId}`, {
            method: 'POST',
            body: JSON.stringify(formData)
        })
    }

    async uploadProfilePhoto(photoData) {
        let body
        if (photoData instanceof FormData) {
            body = photoData
        } else {
            body = JSON.stringify({ photo: photoData })
        }
        return this.request('/profile/upload-photo', {
            method: 'POST',
            body
        })
    }

    // Employee IPCR API methods
    async checkDivisionChiefAccess() {
        return this.request('/employee-ipcr/check-access')
    }

    async getEmployeeIPCRList() {
        return this.request('/employee-ipcr')
    }

    async getEmployeeIPCRFormData() {
        return this.request('/employee-ipcr/form-data')
    }

    async getEmployeeIPCR(id) {
        return this.request(`/employee-ipcr/${id}`)
    }

    async saveEmployeeIPCR(data) {
        const method = data.id && data.id > 0 ? 'PUT' : 'POST'
        const url = data.id && data.id > 0 ? `/employee-ipcr/${data.id}` : '/employee-ipcr'
        return this.request(url, {
            method,
            body: JSON.stringify(data)
        })
    }

    async deleteEmployeeIPCR(id) {
        return this.request(`/employee-ipcr/${id}`, {
            method: 'DELETE'
        })
    }

    async saveIPCRRecalibration(ipcrId, recalibrationLevel, data) {
        return this.request(`/employee-ipcr/${ipcrId}/recalibrate`, {
            method: 'POST',
            body: JSON.stringify({
                recalibration_level: recalibrationLevel,
                ...data
            })
        })
    }

    async getIPCRAgencyHeadApprovals() {
        return this.request('/employee-ipcr/agency-head-approvals')
    }

    async getIPCRHRRecalibrations() {
        return this.request('/employee-ipcr/hr-recalibrations')
    }

    async processIPCRAgencyHeadApproval(id, data) {
        return this.request(`/employee-ipcr/${id}/agency-head-approval`, {
            method: 'POST',
            body: JSON.stringify(data)
        })
    }

    async downloadEmployeeIPCRPDF(id) {
        try {
            const token = localStorage.getItem('auth_token')
            const res = await fetch(`${this.baseURL}/employee-ipcr/${id}/print`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/pdf',
                    'Authorization': token ? `Bearer ${token}` : ''
                },
                credentials: 'include'
            })
            if (!res.ok) return null
            return await res.blob()
        } catch (e) {
            return null
        }
    }

    // Employee OPCR API methods
    async checkDivisionChiefAccessOPCR() {
        return this.request('/employee-opcr/check-access')
    }

    async getEmployeeOPCRList() {
        return this.request('/employee-opcr')
    }

    async getEmployeeOPCRFormData() {
        return this.request('/employee-opcr/form-data')
    }

    async getEmployeeOPCR(id) {
        return this.request(`/employee-opcr/${id}`)
    }

    async saveEmployeeOPCR(data) {
        const method = data.id && data.id > 0 ? 'PUT' : 'POST'
        const url = data.id && data.id > 0 ? `/employee-opcr/${data.id}` : '/employee-opcr'
        return this.request(url, {
            method,
            body: JSON.stringify(data)
        })
    }

    async deleteEmployeeOPCR(id) {
        return this.request(`/employee-opcr/${id}`, {
            method: 'DELETE'
        })
    }

    async saveOPCRRecalibration(opcrId, recalibrationLevel, data) {
        return this.request(`/employee-opcr/${opcrId}/recalibrate`, {
            method: 'POST',
            body: JSON.stringify({
                recalibration_level: recalibrationLevel,
                ...data
            })
        })
    }

    // Employee DPCR API methods
    async checkDPCRAccess() {
        return this.request('/employee-dpcr/check-access')
    }

    async getEmployeeDPCRList() {
        return this.request('/employee-dpcr')
    }

    async getEmployeeDPCR(id) {
        return this.request(`/employee-dpcr/${id}`)
    }

    async getEmployeeDPCRFormData() {
        return this.request('/employee-dpcr/form-data')
    }

    async saveEmployeeDPCR(data) {
        const method = data.id && data.id > 0 ? 'PUT' : 'POST'
        const url = data.id && data.id > 0 ? `/employee-dpcr/${data.id}` : '/employee-dpcr'
        return this.request(url, {
            method,
            body: JSON.stringify(data)
        })
    }

    async deleteEmployeeDPCR(id) {
        return this.request(`/employee-dpcr/${id}`, {
            method: 'DELETE'
        })
    }

    async saveDPCRRecalibration(dpcrId, recalibrationLevel, data) {
        return this.request(`/employee-dpcr/${dpcrId}/recalibrate`, {
            method: 'POST',
            body: JSON.stringify({
                recalibration_level: recalibrationLevel,
                ...data
            })
        })
    }

    // Employee Non DTR (Contract of Service Accomplishment) methods
    async checkEmployeeNonDTRAccess() {
        return this.request('/employee-non-dtr/check-access')
    }

    async getEmployeeNonDTRList() {
        return this.request('/employee-non-dtr')
    }

    async getEmployeeNonDTRPayrollPeriods() {
        return this.request('/employee-non-dtr/payroll-periods')
    }

    async getEmployeeNonDTRFormData(payrollPeriodId = null) {
        const query = payrollPeriodId ? `?payroll_period_id=${payrollPeriodId}` : ''
        return this.request(`/employee-non-dtr/form-data${query}`)
    }

    async getEmployeeNonDTR(id) {
        return this.request(`/employee-non-dtr/${id}`)
    }

    async createEmployeeNonDTR(data) {
        return this.request('/employee-non-dtr', {
            method: 'POST',
            body: JSON.stringify(data)
        })
    }

    async submitEmployeeNonDTR(id, data) {
        return this.request(`/employee-non-dtr/${id}/submit`, {
            method: 'POST',
            body: JSON.stringify(data)
        })
    }

    async updateEmployeeNonDTR(id, data) {
        return this.request(`/employee-non-dtr/${id}`, {
            method: 'PUT',
            body: JSON.stringify(data)
        })
    }

    async deleteEmployeeNonDTR(id) {
        return this.request(`/employee-non-dtr/${id}`, {
            method: 'DELETE'
        })
    }

    async uploadNonDTRAttachment(formData) {
        return this.request('/employee-non-dtr/upload-attachment', {
            method: 'POST',
            body: formData,
            isFormData: true
        })
    }

    async deleteNonDTRAttachment(id) {
        return this.request(`/employee-non-dtr/attachment/${id}`, {
            method: 'DELETE'
        })
    }

    async getServiceRenderedFormData() {
        return this.request('/service-rendered/form-data')
    }

    async printServiceRenderedCertificate(payload) {
        try {
            await this.initSanctum()
            const token = localStorage.getItem('auth_token')
            const xsrfCookie = typeof document !== 'undefined'
                ? (document.cookie.split('; ').find(c => c.startsWith('XSRF-TOKEN=')) || '').split('=')[1]
                : null

            const response = await fetch(`${this.baseURL}/service-rendered/print`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/pdf',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...(token ? { Authorization: `Bearer ${token}` } : {}),
                    ...(xsrfCookie ? { 'X-XSRF-TOKEN': decodeURIComponent(xsrfCookie) } : {})
                },
                credentials: 'include',
                body: JSON.stringify(payload)
            })

            if (!response.ok) {
                let details = ''
                try {
                    const errJson = await response.json()
                    details = errJson?.message || JSON.stringify(errJson?.errors || errJson)
                } catch {
                    try { details = await response.text() } catch { /* ignore */ }
                }
                throw new Error(details || `HTTP ${response.status}`)
            }

            const blob = await response.blob()
            return this.normalizePdfBlob(blob)
        } catch (error) {
            console.error('Failed to generate certificate of rendered service:', error)
            throw new Error(error?.message || 'Failed to generate certificate')
        }
    }

    normalizePdfBlob(blob) {
        if (!blob) return null
        if (blob.type === 'application/pdf') {
            return blob
        }
        return new Blob([blob], { type: 'application/pdf' })
    }

    async printAccomplishmentReport(id) {
        try {
            await this.initSanctum()
            const token = localStorage.getItem('auth_token')
            const response = await fetch(`${this.baseURL}/employee-non-dtr/${id}/print`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/pdf',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...(token ? { Authorization: `Bearer ${token}` } : {})
                },
                credentials: 'include'
            })

            if (!response.ok) {
                throw new Error('Failed to generate accomplishment report')
            }

            return await response.blob()
        } catch (error) {
            throw error
        }
    }

    // Division Head Non DTR methods
    async getDivisionHeadNonDTRList() {
        return this.request('/division-head-non-dtr')
    }

    async approveDivisionHeadNonDTR(id, data) {
        return this.request(`/division-head-non-dtr/${id}/approve`, {
            method: 'POST',
            body: JSON.stringify(data)
        })
    }

    async downloadEmployeeOPCRPDF(id) {
        try {
            const token = localStorage.getItem('auth_token')
            const res = await fetch(`${this.baseURL}/employee-opcr/${id}/print`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/pdf',
                    'Authorization': token ? `Bearer ${token}` : ''
                },
                credentials: 'include'
            })
            if (!res.ok) return null
            return await res.blob()
        } catch (e) {
            return null
        }
    }

    async downloadEmployeeDPCRPDF(id) {
        try {
            const token = localStorage.getItem('auth_token')
            const res = await fetch(`${this.baseURL}/employee-dpcr/${id}/print`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/pdf',
                    'Authorization': token ? `Bearer ${token}` : ''
                },
                credentials: 'include'
            })
            if (!res.ok) return null
            return await res.blob()
        } catch (e) {
            return null
        }
    }

    async get201Schedule() {
        return this.request('/update-201-schedule')
    }

    async save201Schedule(id, data) {
        const method = id === 0 ? 'POST' : 'PUT'
        return this.request(`/update-201-schedule/${id}`, {
            method,
            body: JSON.stringify(data)
        })
    }

    async delete201Schedule(id) {
        return this.request(`/update-201-schedule/${id}`, {
            method: 'DELETE'
        })
    }

    // Export employee data
    async exportEmployeeData() {
        return this.request('/employee-file/export')
    }

    // Dashboard: get dashboard data
    async getDashboardData(userId) {
        return this.request(`/dashboard/${userId}`)
    }

    // Announcements
    async getAnnouncements() {
        return this.request('/announcements')
    }

    async createAnnouncement(data) {
        return this.request('/announcements', {
            method: 'POST',
            body: JSON.stringify(data)
        })
    }

    async getAnnouncementEmployees() {
        return this.request('/announcements/employees')
    }

    // Leave: load dashboard for a user
    async getLeaveDashboard(userId) {
        return this.request(`/leaves/${userId}`)
    }

    // Leave: load application form data (id=0 add, id>0 edit/view). view: 0 add/edit, 1 view
    async getLeaveForm(id, view) {
        return this.request(`/leave-application/${id}/${view}`)
    }

    // Leave: create/update application. id=0 for create.
    async submitLeave(id, formData) {
        await this.initSanctum()
        const url = `${this.baseURL}/leaves/${id}`
        const token = localStorage.getItem('auth_token')
        // Read XSRF token cookie set by /sanctum/csrf-cookie and forward it as header
        const xsrfCookie = typeof document !== 'undefined'
            ? (document.cookie.split('; ').find(c => c.startsWith('XSRF-TOKEN=')) || '').split('=')[1]
            : null
        const xsrfHeader = xsrfCookie ? { 'X-XSRF-TOKEN': decodeURIComponent(xsrfCookie) } : {}
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
                ...xsrfHeader
            },
            body: formData,
            credentials: 'include'
        })
        if (!res.ok) {
            let details = ''
            try { details = await res.text() } catch { }
            throw new Error(`HTTP error! status: ${res.status}${details ? ` - ${details}` : ''}`)
        }
        // Some endpoints may return empty/varied body; normalize to include success flag
        try {
            const data = await res.json()
            if (data && typeof data.success !== 'undefined') {
                if (data.success === false) {
                    const msg = data.message || data.error || data?.data?.message || 'Failed to submit leave'
                    throw new Error(msg)
                }
                return data
            }
            return { success: true, data }
        } catch (err) {
            // Preserve explicit backend/business errors; only default to success
            // when response body is not JSON and request itself succeeded.
            if (err instanceof Error) throw err
            return { success: true }
        }
    }

    // Leave: delete application
    async deleteLeave(id) {
        return this.request(`/leaves/${id}`, { method: 'DELETE' })
    }

    // Leave: process (1 approve, 2 disapprove, 3 cancel approve tab, 4 cancel)
    async processLeave(id, processId, remarks) {
        // remarks passed in path per backend
        return this.request(`/leaves/${id}/process/${processId}/${encodeURIComponent(remarks || '')}`)
    }

    // Leave: download attachments
    async downloadLeaveAttachment(id) {
        await this.initSanctum()
        const url = `${this.baseURL}/leave-attachment-download/${id}`
        const res = await fetch(url, { method: 'GET', credentials: 'include' })
        if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`)
        const blob = await res.blob()
        const link = document.createElement('a')
        const objectUrl = URL.createObjectURL(blob)
        link.href = objectUrl
        link.download = `leave_attachment_${id}`
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
        URL.revokeObjectURL(objectUrl)
        return true
    }

    async downloadLeaveCancelledDoc(id) {
        await this.initSanctum()
        const url = `${this.baseURL}/leave-cancel-attachment-download/${id}`
        const res = await fetch(url, { method: 'GET', credentials: 'include' })
        if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`)
        const blob = await res.blob()
        const link = document.createElement('a')
        const objectUrl = URL.createObjectURL(blob)
        link.href = objectUrl
        link.download = `leave_cancelled_${id}`
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
        URL.revokeObjectURL(objectUrl)
        return true
    }

    async uploadLeaveCancelAttachment(leaveId, formData) {
        await this.initSanctum()
        formData.append('leave_header_id', leaveId)
        return this.request('/leave-cancel-attachment', {
            method: 'POST',
            body: formData,
            headers: {} // Let browser set Content-Type with boundary for FormData
        })
    }

    async printLeave(id) {
        try {
            await this.initSanctum()
            const url = `${this.baseURL}/leaves/${id}/print`

            const token = localStorage.getItem('auth_token')

            const xsrfCookie = typeof document !== 'undefined'
                ? (document.cookie.split('; ').find(c => c.startsWith('XSRF-TOKEN=')) || '').split('=')[1]
                : null

            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/pdf',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
                    ...(xsrfCookie ? { 'X-XSRF-TOKEN': decodeURIComponent(xsrfCookie) } : {})
                },
                credentials: 'include'
            })

            if (!response.ok) {
                const errorText = await response.text()
                throw new Error(`Print failed: ${response.status} - ${errorText}`)
            }

            const blob = await response.blob()
            return blob
        } catch (error) {
            throw error
        }
    }

    // Leave Credits: get all active types and balances for current employee via existing index
    async getLeaveCredits() {
        return this.request('/leave-credits')
    }

    // Leave Monetization: load data for current user (by user id)
    async getLeaveMonetization(userId) {
        return this.request(`/leave-monetization/${userId}`)
    }

    // Leave Monetization: create or update monetization (FormData with files)
    async storeLeaveMonetization(id, formData) {
        await this.initSanctum()
        const url = `${this.baseURL}/leave-monetization`
        const token = localStorage.getItem('auth_token')
        const xsrfCookie = typeof document !== 'undefined'
            ? (document.cookie.split('; ').find(c => c.startsWith('XSRF-TOKEN=')) || '').split('=')[1]
            : null
        const xsrfHeader = xsrfCookie ? { 'X-XSRF-TOKEN': decodeURIComponent(xsrfCookie) } : {}
        // ensure id is present in body if backend expects it; controller reads from body for various fields
        formData.append('leave_monetization_id', id || 0)
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
                ...xsrfHeader
            },
            body: formData,
            credentials: 'include'
        })
        if (!res.ok) {
            const text = await res.text().catch(() => '')
            throw new Error(`HTTP error! status: ${res.status} - ${text}`)
        }
        try { return await res.json() } catch { return { success: true } }
    }

    // Leave Monetization: delete
    async deleteLeaveMonetization(id) {
        return this.request(`/leave-monetization/${id}`, { method: 'DELETE' })
    }

    // Leave Monetization: approve/disapprove (type_id: 1 approve, 2 disapprove), remarks required
    async processLeaveMonetization(id, typeId, remarks = '') {
        // backend uses GET /leave-monetization/{id}/process/{type_id}/{remarks}
        const encoded = encodeURIComponent(remarks || '')
        return this.request(`/leave-monetization/${id}/process/${typeId}/${encoded}`)
    }

    // Leave Monetization: attachments list and download
    async getLeaveMonetizationAttachments(monetizationId) {
        return this.request(`/leave-monetization-attachment/${monetizationId}`)
    }

    async downloadLeaveMonetizationAttachment(attachmentId) {
        await this.initSanctum()
        const url = `${this.baseURL}/leave-monetization-attachment-download/${attachmentId}`
        const token = localStorage.getItem('auth_token')
        const xsrfCookie = typeof document !== 'undefined'
            ? (document.cookie.split('; ').find(c => c.startsWith('XSRF-TOKEN=')) || '').split('=')[1]
            : null
        const xsrfHeader = xsrfCookie ? { 'X-XSRF-TOKEN': decodeURIComponent(xsrfCookie) } : {}

        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/octet-stream',
                'X-Requested-With': 'XMLHttpRequest',
                ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
                ...xsrfHeader
            },
            credentials: 'include'
        })

        if (!response.ok) {
            throw new Error(`Download failed: ${response.status}`)
        }

        const blob = await response.blob()
        const objectUrl = URL.createObjectURL(blob)

        const link = document.createElement('a')
        link.href = objectUrl
        link.download = `attachment_${attachmentId}`
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
        URL.revokeObjectURL(objectUrl)

        return { success: true }
    }

    // Official Business Applications: load dashboard/list for a user
    async getOfficialBusiness(userId) {
        return this.request(`/official-business-applications/${userId}`)
    }

    // Official Business Applications: create/update official/travel order (FormData)
    async storeOfficialBusiness(formData) {
        await this.initSanctum()
        const url = `${this.baseURL}/official-business-applications`
        const token = localStorage.getItem('auth_token')
        const xsrfCookie = typeof document !== 'undefined'
            ? (document.cookie.split('; ').find(c => c.startsWith('XSRF-TOKEN=')) || '').split('=')[1]
            : null
        const xsrfHeader = xsrfCookie ? { 'X-XSRF-TOKEN': decodeURIComponent(xsrfCookie) } : {}
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
                ...xsrfHeader
            },
            body: formData,
            credentials: 'include'
        })
        if (!res.ok) {
            const text = await res.text().catch(() => '')
            throw new Error(`HTTP error! status: ${res.status} - ${text}`)
        }
        try { return await res.json() } catch { return { success: true } }
    }

    // Official Business Applications: create/update travel authority (FormData)
    async storeUnofficialBusiness(formData) {
        await this.initSanctum()
        const url = `${this.baseURL}/official-business-applications/unofficial`
        const token = localStorage.getItem('auth_token')
        const xsrfCookie = typeof document !== 'undefined'
            ? (document.cookie.split('; ').find(c => c.startsWith('XSRF-TOKEN=')) || '').split('=')[1]
            : null
        const xsrfHeader = xsrfCookie ? { 'X-XSRF-TOKEN': decodeURIComponent(xsrfCookie) } : {}
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
                ...xsrfHeader
            },
            body: formData,
            credentials: 'include'
        })
        if (!res.ok) {
            const text = await res.text().catch(() => '')
            throw new Error(`HTTP error! status: ${res.status} - ${text}`)
        }
        try { return await res.json() } catch { return { success: true } }
    }

    async getTATypes() {
        return this.request('/ta-types')
    }

    async getTOTypes() {
        return this.request('/to-types')
    }

    async getEmployeeInfoForPickup(userId) {
        return this.request(`/employee-info-for-pickup/${userId}`)
    }

    async deleteOfficialBusiness(id) {
        return this.request(`/official-business-applications/${id}`, { method: 'DELETE' })
    }

    async approveOfficialBusiness(id, remarks = '') {
        const encoded = encodeURIComponent(remarks || '')
        return this.request(`/official-business-applications/${id}/approve/${encoded}`)
    }

    async disapproveOfficialBusiness(id, remarks = '') {
        const encoded = encodeURIComponent(remarks || '')
        return this.request(`/official-business-applications/${id}/disapprove/${encoded}`)
    }

    async cancelOfficialBusiness(id, remarks = '') {
        const encoded = encodeURIComponent(remarks || '')
        return this.request(`/official-business-applications/${id}/cancel/${encoded}`)
    }

    async listOfficialBusinessAttachments(obId) {
        return this.request(`/official-business-attachments/${obId}`)
    }

    async getBudgetOfficers() {
        return this.request('/official-business/budget-officers')
    }

    async downloadOfficialBusinessAttachment(attachmentId) {
        const url = `${this.baseURL}/official-business-attachment-download/${attachmentId}`
        window.open(url, '_blank')
    }

    async printOfficialBusiness(id, unofficial = false) {
        try {
            await this.initSanctum()
            const path = unofficial ? '/unofficial-business-print' : '/official-business-print'
            const url = `${this.baseURL}${path}/${id}`

            const token = localStorage.getItem('auth_token')
            const xsrfCookie = typeof document !== 'undefined'
                ? (document.cookie.split('; ').find(c => c.startsWith('XSRF-TOKEN=')) || '').split('=')[1]
                : null

            const headers = {
                ...(token ? { Authorization: `Bearer ${token}` } : {}),
                ...(xsrfCookie ? { 'X-XSRF-TOKEN': decodeURIComponent(xsrfCookie) } : {}),
                'Accept': 'application/pdf',
                'X-Requested-With': 'XMLHttpRequest'
            }

            const response = await fetch(url, {
                method: 'GET',
                headers,
                credentials: 'include',
                cache: 'no-store'
            })
            if (!response.ok) {
                let details = ''
                try { details = await response.text() } catch { }
                throw new Error(`HTTP ${response.status}${details ? ` - ${details}` : ''}`)
            }
            const blob = await response.blob()
            const blobUrl = window.URL.createObjectURL(blob)
            return blobUrl
        } catch (error) {
            console.error('Failed to print Official Business:', error)
            throw new Error(`Failed to print Official Business: ${error?.message || 'Unknown error'}`)
        }
    }

    async printRequestPickup(id) {
        try {
            await this.initSanctum()
            const url = `${this.baseURL}/request-pickup-print/${id}`

            const token = localStorage.getItem('auth_token')
            const xsrfCookie = typeof document !== 'undefined'
                ? (document.cookie.split('; ').find(c => c.startsWith('XSRF-TOKEN=')) || '').split('=')[1]
                : null

            const headers = {
                ...(token ? { Authorization: `Bearer ${token}` } : {}),
                ...(xsrfCookie ? { 'X-XSRF-TOKEN': decodeURIComponent(xsrfCookie) } : {}),
                'Accept': 'application/pdf',
                'X-Requested-With': 'XMLHttpRequest'
            }

            const response = await fetch(url, {
                method: 'GET',
                headers,
                credentials: 'include'
            })

            if (!response.ok) {
                const text = await response.text().catch(() => '')
                throw new Error(`HTTP error! status: ${response.status} - ${text}`)
            }

            const blob = await response.blob()
            return URL.createObjectURL(blob)
        } catch (error) {
            console.error('Error printing request pickup:', error)
            throw error
        }
    }

    async downloadRequestPickupWord(id) {
        try {
            await this.initSanctum()
            const url = `${this.baseURL}/request-pickup-word/${id}`

            const token = localStorage.getItem('auth_token')
            const xsrfCookie = typeof document !== 'undefined'
                ? (document.cookie.split('; ').find(c => c.startsWith('XSRF-TOKEN=')) || '').split('=')[1]
                : null

            const headers = {
                ...(token ? { Authorization: `Bearer ${token}` } : {}),
                ...(xsrfCookie ? { 'X-XSRF-TOKEN': decodeURIComponent(xsrfCookie) } : {}),
                'Accept': 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'X-Requested-With': 'XMLHttpRequest'
            }

            const response = await fetch(url, {
                method: 'GET',
                headers,
                credentials: 'include',
                cache: 'no-store'
            })

            if (!response.ok) {
                const text = await response.text().catch(() => '')
                throw new Error(`HTTP ${response.status}${text ? ` - ${text}` : ''}`)
            }

            const blob = await response.blob()
            const blobUrl = window.URL.createObjectURL(blob)
            const a = document.createElement('a')
            a.href = blobUrl
            a.download = `Request_for_Pickup_${id}.docx`
            document.body.appendChild(a)
            a.click()
            document.body.removeChild(a)
            window.URL.revokeObjectURL(blobUrl)
        } catch (error) {
            console.error('Failed to download Request for Pickup Word:', error)
            throw error
        }
    }

    async downloadRequestPickupExcel(id) {
        try {
            await this.initSanctum()
            const url = `${this.baseURL}/request-pickup-excel/${id}`

            const token = localStorage.getItem('auth_token')
            const xsrfCookie = typeof document !== 'undefined'
                ? (document.cookie.split('; ').find(c => c.startsWith('XSRF-TOKEN=')) || '').split('=')[1]
                : null

            const headers = {
                ...(token ? { Authorization: `Bearer ${token}` } : {}),
                ...(xsrfCookie ? { 'X-XSRF-TOKEN': decodeURIComponent(xsrfCookie) } : {}),
                'Accept': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'X-Requested-With': 'XMLHttpRequest'
            }

            const response = await fetch(url, {
                method: 'GET',
                headers,
                credentials: 'include',
                cache: 'no-store'
            })

            if (!response.ok) {
                const text = await response.text().catch(() => '')
                throw new Error(`HTTP error! status: ${response.status} - ${text}`)
            }

            const blob = await response.blob()
            const blobUrl = window.URL.createObjectURL(blob)
            const a = document.createElement('a')
            a.href = blobUrl
            a.download = `Request_for_Pickup_${id}.xlsx`
            document.body.appendChild(a)
            a.click()
            document.body.removeChild(a)
            window.URL.revokeObjectURL(blobUrl)
        } catch (error) {
            console.error('Failed to download Request for Pickup Excel:', error)
            throw error
        }
    }

    async downloadTravelAuthorityWord(id) {
        try {
            await this.initSanctum()
            const url = `${this.baseURL}/travel-authority-word/${id}`

            const token = localStorage.getItem('auth_token')
            const xsrfCookie = typeof document !== 'undefined'
                ? (document.cookie.split('; ').find(c => c.startsWith('XSRF-TOKEN=')) || '').split('=')[1]
                : null

            const headers = {
                ...(token ? { Authorization: `Bearer ${token}` } : {}),
                ...(xsrfCookie ? { 'X-XSRF-TOKEN': decodeURIComponent(xsrfCookie) } : {}),
                'Accept': 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'X-Requested-With': 'XMLHttpRequest'
            }

            const response = await fetch(url, {
                method: 'GET',
                headers,
                credentials: 'include',
                cache: 'no-store'
            })
            if (!response.ok) {
                let details = ''
                try { details = await response.text() } catch { }
                throw new Error(`HTTP ${response.status}${details ? ` - ${details}` : ''}`)
            }
            const blob = await response.blob()
            const blobUrl = window.URL.createObjectURL(blob)
            const a = document.createElement('a')
            a.href = blobUrl
            a.download = `Travel_Authority_${id}.docx`
            document.body.appendChild(a)
            a.click()
            document.body.removeChild(a)
            window.URL.revokeObjectURL(blobUrl)
        } catch (error) {
            console.error('Failed to download Travel Authority Word:', error)
            throw error
        }
    }

    async downloadTravelAuthorityPersonalWord(id) {
        try {
            await this.initSanctum()
            const url = `${this.baseURL}/travel-authority-personal-word/${id}`

            const token = localStorage.getItem('auth_token')
            const xsrfCookie = typeof document !== 'undefined'
                ? (document.cookie.split('; ').find(c => c.startsWith('XSRF-TOKEN=')) || '').split('=')[1]
                : null

            const headers = {
                ...(token ? { Authorization: `Bearer ${token}` } : {}),
                ...(xsrfCookie ? { 'X-XSRF-TOKEN': decodeURIComponent(xsrfCookie) } : {}),
                'Accept': 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'X-Requested-With': 'XMLHttpRequest'
            }

            const response = await fetch(url, {
                method: 'GET',
                headers,
                credentials: 'include',
                cache: 'no-store'
            })
            if (!response.ok) {
                let details = ''
                try { details = await response.text() } catch { }
                throw new Error(`HTTP ${response.status}${details ? ` - ${details}` : ''}`)
            }
            const blob = await response.blob()
            const blobUrl = window.URL.createObjectURL(blob)
            const a = document.createElement('a')
            a.href = blobUrl
            a.download = `Travel_Authority_Personal_${id}.docx`
            document.body.appendChild(a)
            a.click()
            document.body.removeChild(a)
            window.URL.revokeObjectURL(blobUrl)
        } catch (error) {
            console.error('Failed to download Travel Authority Personal Word:', error)
            throw error
        }
    }

    async downloadTravelOrderLDSDWord(id) {
        try {
            await this.initSanctum()
            const url = `${this.baseURL}/travel-order-ldsd-word/${id}`

            const token = localStorage.getItem('auth_token')
            const xsrfCookie = typeof document !== 'undefined'
                ? (document.cookie.split('; ').find(c => c.startsWith('XSRF-TOKEN=')) || '').split('=')[1]
                : null

            const headers = {
                ...(token ? { Authorization: `Bearer ${token}` } : {}),
                ...(xsrfCookie ? { 'X-XSRF-TOKEN': decodeURIComponent(xsrfCookie) } : {}),
                'Accept': 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'X-Requested-With': 'XMLHttpRequest'
            }

            const response = await fetch(url, {
                method: 'GET',
                headers,
                credentials: 'include',
                cache: 'no-store'
            })
            if (!response.ok) {
                let details = ''
                try { details = await response.text() } catch { }
                throw new Error(`HTTP ${response.status}${details ? ` - ${details}` : ''}`)
            }
            const blob = await response.blob()
            const blobUrl = window.URL.createObjectURL(blob)
            const a = document.createElement('a')
            a.href = blobUrl
            a.download = `Travel_Order_LDSD_${id}.docx`
            document.body.appendChild(a)
            a.click()
            document.body.removeChild(a)
            window.URL.revokeObjectURL(blobUrl)
        } catch (error) {
            console.error('Failed to download Travel Order LDSD Word:', error)
            throw error
        }
    }

    async downloadOfficialBusinessWord(id) {
        try {
            await this.initSanctum()
            const url = `${this.baseURL}/official-business-word/${id}`

            const token = localStorage.getItem('auth_token')
            const xsrfCookie = typeof document !== 'undefined'
                ? (document.cookie.split('; ').find(c => c.startsWith('XSRF-TOKEN=')) || '').split('=')[1]
                : null

            const headers = {
                ...(token ? { Authorization: `Bearer ${token}` } : {}),
                ...(xsrfCookie ? { 'X-XSRF-TOKEN': decodeURIComponent(xsrfCookie) } : {}),
                'Accept': 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'X-Requested-With': 'XMLHttpRequest'
            }

            const response = await fetch(url, {
                method: 'GET',
                headers,
                credentials: 'include',
                cache: 'no-store'
            })
            if (!response.ok) {
                let details = ''
                try { details = await response.text() } catch { }
                throw new Error(`HTTP ${response.status}${details ? ` - ${details}` : ''}`)
            }
            const blob = await response.blob()
            const blobUrl = window.URL.createObjectURL(blob)
            const a = document.createElement('a')
            a.href = blobUrl
            a.download = `Pass_Slip_${id}.docx`
            document.body.appendChild(a)
            a.click()
            document.body.removeChild(a)
            window.URL.revokeObjectURL(blobUrl)
        } catch (error) {
            console.error('Failed to download Pass Slip Word:', error)
            throw error
        }
    }

    async downloadOfficialBusinessExcel(id) {
        try {
            await this.initSanctum()
            const url = `${this.baseURL}/official-business-excel/${id}`

            const token = localStorage.getItem('auth_token')
            const xsrfCookie = typeof document !== 'undefined'
                ? (document.cookie.split('; ').find(c => c.startsWith('XSRF-TOKEN=')) || '').split('=')[1]
                : null

            const headers = {
                ...(token ? { Authorization: `Bearer ${token}` } : {}),
                ...(xsrfCookie ? { 'X-XSRF-TOKEN': decodeURIComponent(xsrfCookie) } : {}),
                'Accept': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'X-Requested-With': 'XMLHttpRequest'
            }

            const response = await fetch(url, {
                method: 'GET',
                headers,
                credentials: 'include',
                cache: 'no-store'
            })
            if (!response.ok) {
                let details = ''
                try { details = await response.text() } catch { }
                throw new Error(`HTTP ${response.status}${details ? ` - ${details}` : ''}`)
            }
            const blob = await response.blob()
            const blobUrl = window.URL.createObjectURL(blob)
            const a = document.createElement('a')
            a.href = blobUrl
            a.download = `Pass_Slip_${id}.xlsx`
            document.body.appendChild(a)
            a.click()
            document.body.removeChild(a)
            window.URL.revokeObjectURL(blobUrl)
        } catch (error) {
            console.error('Failed to download Pass Slip Excel:', error)
            throw error
        }
    }

    async printTravelOrder(id) {
        try {
            await this.initSanctum()
            const url = `${this.baseURL}/order-business-print/${id}`

            const token = localStorage.getItem('auth_token')
            const xsrfCookie = typeof document !== 'undefined'
                ? (document.cookie.split('; ').find(c => c.startsWith('XSRF-TOKEN=')) || '').split('=')[1]
                : null

            const headers = {
                ...(token ? { Authorization: `Bearer ${token}` } : {}),
                ...(xsrfCookie ? { 'X-XSRF-TOKEN': decodeURIComponent(xsrfCookie) } : {}),
                'Accept': 'application/pdf',
                'X-Requested-With': 'XMLHttpRequest'
            }

            const response = await fetch(url, {
                method: 'GET',
                headers,
                credentials: 'include',
                cache: 'no-store'
            })
            if (!response.ok) {
                let details = ''
                try { details = await response.text() } catch { }
                throw new Error(`HTTP ${response.status}${details ? ` - ${details}` : ''}`)
            }
            const blob = await response.blob()
            const blobUrl = window.URL.createObjectURL(blob)
            return blobUrl
        } catch (error) {
            console.error('Failed to print Travel Order:', error)
            throw new Error(`Failed to print Travel Order: ${error?.message || 'Unknown error'}`)
        }
    }

    // Payslip API methods
    async getPayslipList(userId) {
        return this.request(`/payslips/${userId}`)
    }

    // Holiday Calendar API methods
    async getHolidayCalendar(year) {
        return this.request(`/holidays/calendar/${year}`)
    }

    async getPayslipDetails(employeeId, payrollId) {
        return this.request(`/payslips/${employeeId}/view/${payrollId}`)
    }

    async printPayslip(employeeId, payrollId) {
        try {
            await this.initSanctum()
            const url = `${this.baseURL}/payslips/${employeeId}/print/${payrollId}`

            const token = localStorage.getItem('auth_token')
            const xsrfCookie = typeof document !== 'undefined'
                ? (document.cookie.split('; ').find(c => c.startsWith('XSRF-TOKEN=')) || '').split('=')[1]
                : null

            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/pdf',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
                    ...(xsrfCookie ? { 'X-XSRF-TOKEN': decodeURIComponent(xsrfCookie) } : {})
                },
                credentials: 'include'
            })

            if (!response.ok) {
                const errorText = await response.text()
                throw new Error(`Print failed: ${response.status} - ${errorText}`)
            }

            const blob = await response.blob()
            return blob
        } catch (error) {
            throw error
        }
    }

    // Overtime Application API methods
    async getOvertimeData(userId) {
        return this.request(API_ROUTES.overtime.get(userId))
    }

    async addOvertimeApplication(formData) {
        return this.request(API_ROUTES.overtime.store, {
            method: 'POST',
            body: formData
        })
    }

    async updateOvertimeApplication(id, formData) {
        return this.request(API_ROUTES.overtime.update, {
            method: 'POST',
            body: formData
        })
    }

    async deleteOvertimeApplication(id) {
        return this.request(API_ROUTES.overtime.delete(id), { method: 'DELETE' })
    }

    async approveOvertimeApplication(id, empId, remarks = '') {
        return this.request(API_ROUTES.overtime.approve, {
            method: 'POST',
            body: JSON.stringify({
                id: id,
                emp_id: empId,
                remarks: remarks
            })
        })
    }

    async disapproveOvertimeApplication(id, remarks = '') {
        return this.request(API_ROUTES.overtime.disapprove(id, remarks))
    }

    async cancelOvertimeApplication(id, empId, remarks = '') {
        return this.request(API_ROUTES.overtime.cancel(id, empId, remarks))
    }

    async getOvertimeAttachments(overtimeId) {
        return this.request(API_ROUTES.overtime.attachments(overtimeId))
    }

    async removeOvertimeAttachment(attachmentId) {
        return this.request(API_ROUTES.overtime.removeAttachment(attachmentId), { method: 'DELETE' })
    }

    async downloadOvertimeAttachment(attachmentId) {
        const url = `${this.baseURL}${API_ROUTES.overtime.downloadAttachment(attachmentId)}`
        window.open(url, '_blank')
    }

    async downloadOvertimeApproval(attachmentId) {
        const url = `${this.baseURL}${API_ROUTES.overtime.downloadApproval(attachmentId)}`
        window.open(url, '_blank')
    }

    async getOvertimeMonitoring() {
        return this.request(API_ROUTES.overtime.monitoring)
    }

    async getOvertimeAuthorizationForm(ids) {
        try {
            await this.initSanctum()
            const url = `${this.baseURL}${API_ROUTES.overtime.authorizationForm}`

            const token = localStorage.getItem('auth_token')
            const xsrfCookie = typeof document !== 'undefined'
                ? (document.cookie.split('; ').find(c => c.startsWith('XSRF-TOKEN=')) || '').split('=')[1]
                : null

            const headers = {
                ...(token ? { Authorization: `Bearer ${token}` } : {}),
                ...(xsrfCookie ? { 'X-XSRF-TOKEN': decodeURIComponent(xsrfCookie) } : {}),
                'Accept': 'application/pdf',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }

            const response = await fetch(url, {
                method: 'POST',
                headers,
                credentials: 'include',
                cache: 'no-store',
                body: JSON.stringify({ ids })
            })

            if (!response.ok) {
                let details = ''
                try { details = await response.text() } catch { }
                throw new Error(`HTTP ${response.status}${details ? ` - ${details}` : ''}`)
            }
            const blob = await response.blob()
            return blob
        } catch (error) {
            console.error('Failed to generate Overtime Authorization Request form:', error)
            throw error
        }
    }

    async downloadOvertimeAuthorizationWord(ids) {
        try {
            await this.initSanctum()
            const url = `${this.baseURL}${API_ROUTES.overtime.authorizationFormWord}`

            const token = localStorage.getItem('auth_token')
            const xsrfCookie = typeof document !== 'undefined'
                ? (document.cookie.split('; ').find(c => c.startsWith('XSRF-TOKEN=')) || '').split('=')[1]
                : null

            const headers = {
                ...(token ? { Authorization: `Bearer ${token}` } : {}),
                ...(xsrfCookie ? { 'X-XSRF-TOKEN': decodeURIComponent(xsrfCookie) } : {}),
                'Accept': 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }

            const response = await fetch(url, {
                method: 'POST',
                headers,
                credentials: 'include',
                cache: 'no-store',
                body: JSON.stringify({ ids })
            })

            if (!response.ok) {
                let details = ''
                try { details = await response.text() } catch { }
                throw new Error(`HTTP ${response.status}${details ? ` - ${details}` : ''}`)
            }

            const blob = await response.blob()
            const blobUrl = window.URL.createObjectURL(blob)
            const a = document.createElement('a')
            a.href = blobUrl
            a.download = 'Overtime_Authorization_Request.docx'
            document.body.appendChild(a)
            a.click()
            document.body.removeChild(a)
            window.URL.revokeObjectURL(blobUrl)
        } catch (error) {
            console.error('Failed to download Overtime Authorization Request Word document:', error)
            throw error
        }
    }

    async downloadOvertimeAuthorizationExcel(ids) {
        try {
            await this.initSanctum()
            const url = `${this.baseURL}${API_ROUTES.overtime.authorizationFormExcel}`

            const token = localStorage.getItem('auth_token')
            const xsrfCookie = typeof document !== 'undefined'
                ? (document.cookie.split('; ').find(c => c.startsWith('XSRF-TOKEN=')) || '').split('=')[1]
                : null

            const headers = {
                ...(token ? { Authorization: `Bearer ${token}` } : {}),
                ...(xsrfCookie ? { 'X-XSRF-TOKEN': decodeURIComponent(xsrfCookie) } : {}),
                'Accept': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }

            const response = await fetch(url, {
                method: 'POST',
                headers,
                credentials: 'include',
                cache: 'no-store',
                body: JSON.stringify({ ids })
            })

            if (!response.ok) {
                let details = ''
                try { details = await response.text() } catch { }
                throw new Error(`HTTP ${response.status}${details ? ` - ${details}` : ''}`)
            }

            const blob = await response.blob()
            const blobUrl = window.URL.createObjectURL(blob)
            const a = document.createElement('a')
            a.href = blobUrl
            a.download = 'Overtime_Authorization_Request.xlsx'
            document.body.appendChild(a)
            a.click()
            document.body.removeChild(a)
            window.URL.revokeObjectURL(blobUrl)
        } catch (error) {
            console.error('Failed to download Overtime Authorization Request Excel document:', error)
            throw error
        }
    }

    // Personal Data Sheet (PDS)
    async downloadPDS(employeeId) {
        try {
            await this.initSanctum()
            const url = `${this.baseURL}/pds/${employeeId}/download`
            const token = localStorage.getItem('auth_token')
            const xsrfCookie = typeof document !== 'undefined'
                ? (document.cookie.split('; ').find(c => c.startsWith('XSRF-TOKEN=')) || '').split('=')[1]
                : null
            const headers = {
                'Accept': 'application/pdf',
                'X-Requested-With': 'XMLHttpRequest',
                ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
                ...(xsrfCookie ? { 'X-XSRF-TOKEN': decodeURIComponent(xsrfCookie) } : {})
            }
            const res = await fetch(url, { method: 'GET', headers, credentials: 'include' })
            if (!res.ok) {
                const text = await res.text().catch(() => '')
                throw new Error(`HTTP error! status: ${res.status}${text ? ` - ${text}` : ''}`)
            }
            const blob = await res.blob()
            const objectUrl = URL.createObjectURL(blob)
            const link = document.createElement('a')
            link.href = objectUrl
            link.download = `PDS_${employeeId}.pdf`
            document.body.appendChild(link)
            link.click()
            document.body.removeChild(link)
            URL.revokeObjectURL(objectUrl)
            return { success: true }
        } catch (error) {
            console.error('Error downloading PDS:', error)
            throw error
        }
    }

    // Notification methods
    async getUserNotifications(showArchived = false) {
        const query = showArchived ? '?show_archived=1' : ''
        return this.request(`/notifications${query}`)
    }

    async markNotificationAsRead(notificationId) {
        return this.request(`/notifications/${notificationId}/read`, {
            method: 'POST'
        })
    }

    async markAllNotificationsAsRead() {
        return this.request('/notifications/read-all', {
            method: 'POST'
        })
    }

    async archiveNotification(notificationId) {
        return this.request(`/notifications/${notificationId}/archive`, {
            method: 'POST'
        })
    }

    async archiveAllReadNotifications() {
        return this.request('/notifications/archive-all-read', {
            method: 'POST'
        })
    }

    // Applicant Monitoring API methods
    async getApplicantVacancies() {
        return this.request('/applicants')
    }

    async getApplicantList(plantillaId) {
        return this.request(`/applicant-list/${plantillaId}`)
    }

    async getApplicantInfo(id, plantillaId) {
        return this.request(`/applicant-info/${id}/${plantillaId}`)
    }

    async getApplicantAttachmentBlob(attachmentId) {
        // Returns a Blob (PDF/image/etc.) so UI can render a preview via blob URL.
        return this.request(`/applicant-resume/${attachmentId}`, {
            method: 'GET',
            responseType: 'blob',
            // Let backend decide actual content type; Accept is just a hint.
            headers: { Accept: '*/*' }
        })
    }

    // Panel Interview Rating (Panelist UI)
    async getPanelInterviews() {
        // Backend ignores the {id} parameter and uses Auth::user(), but route requires it.
        return this.request('/panel-interviews/0')
    }

    async getPanelInterviewApplicantRating(applicantId, employeeId, interviewId) {
        return this.request(`/panel-interviews/0/applicant-rating/${applicantId}/${employeeId}/${interviewId}`)
    }

    async submitPanelInterviewApplicantRating(ratingId, payload) {
        return this.request(`/panel-interviews/0/applicant-rating/${ratingId}`, {
            method: 'POST',
            body: JSON.stringify(payload)
        })
    }

    async getPanelInterviewAttachments(interviewId, employeeId, applicantId) {
        return this.request(`/panel-interviews/0/attachments/${interviewId}/${employeeId}/${applicantId}`)
    }

    async uploadPanelInterviewAttachment({ interviewId, employeeId, applicantId, file }) {
        const formData = new FormData()
        formData.append('interview_id', interviewId)
        formData.append('employee_id', employeeId)
        formData.append('applicant_id', applicantId)
        formData.append('file', file)

        return this.request('/panel-interviews/0/attachments', {
            method: 'POST',
            body: formData,
            // Let browser set Content-Type with multipart boundary
            headers: {}
        })
    }

    async previewPanelInterviewAttachment(attachmentId) {
        // Returns a Blob for inline preview
        return this.request(`/panel-interviews/0/attachment/${attachmentId}`, {
            method: 'GET',
            responseType: 'blob',
            headers: { Accept: '*/*' }
        })
    }

    async markPanelInterviewDone({ interviewId, employeeId, applicantId }) {
        const body = { interview_id: interviewId, employee_id: employeeId, applicant_id: applicantId }
        return this.request('/panel-interviews/0/done', {
            method: 'POST',
            body: JSON.stringify(body)
        })
    }

    async changeApplicantStatus(id, statusId) {
        return this.request(`/applicants/${id}/change-status/${statusId}`, {
            method: 'POST'
        })
    }

    async markApplicantNotQualified(id) {
        return this.request(`/applicant-not-qualified/${id}`, {
            method: 'POST'
        })
    }

    async markApplicantWillNotProceed(id) {
        return this.request(`/applicant-will-not-proceed/${id}`, {
            method: 'POST'
        })
    }

    async markApplicantProceed(id) {
        return this.request(`/applicant-proceed/${id}`, {
            method: 'POST'
        })
    }

    async markApplicantForHiring(id, plantillaId) {
        return this.request(`/applicant-for-hiring/${id}/${plantillaId}`, {
            method: 'POST'
        })
    }
}

export default new ApiService()
export { API_ROUTES }
