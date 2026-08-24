import axios from 'axios'

// Create axios instance with base configuration
const api = axios.create({
    baseURL: import.meta.env.VITE_API_URL || 'http://192.168.0.155:8082/api',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    }
})

// Add request interceptor to include auth token
api.interceptors.request.use(
    (config) => {
        const tokenData = localStorage.getItem('auth_token')
        if (tokenData) {
            try {
                const parsed = JSON.parse(tokenData)
                if (parsed.token) {
                    config.headers.Authorization = `Bearer ${parsed.token}`
                }
            } catch (error) {
                // If parsing fails, treat as old format token
                config.headers.Authorization = `Bearer ${tokenData}`
            }
        }
        return config
    },
    (error) => {
        return Promise.reject(error)
    }
)

// Add response interceptor for error handling
api.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            // Handle token expiration - trigger login modal
            localStorage.removeItem('auth_token')
            // Dispatch custom event to show login modal
            window.dispatchEvent(new CustomEvent('token-expired'))
        }
        return Promise.reject(error)
    }
)

// Authentication API functions
export const authApi = {
    // Login user
    login: (credentials) => api.post('/login', credentials),

    // Logout user
    logout: () => api.post('/logout'),

    // Get current user
    getCurrentUser: () => api.get('/user'),

    // Refresh token
    refreshToken: () => api.post('/refresh')
}

// HR Overview Dashboard API
export const hrOverviewApi = {
    getOverview: () => api.get('/hr-overview')
}

// Access Rights - controls what menus/tabs a user can see
export const accessRightsApi = {
    // Get enabled menus per module for a given user
    get: (userId) => api.get(`/access-rights/${userId}`),
    // Update user menu access (used in Admin pages)
    update: (userId, payload) => api.post(`/access-rights/${userId}`, payload),
}

// Employee API functions
export const employeeApi = {
    // Get all employees
    getEmployees: () => api.get('/employees'),

    // Get employee by ID (for editing)
    getEmployee: (id) => api.get(`/employees/add/${id}`),

    // Create new employee
    createEmployee: (data) => {
        const config = data instanceof FormData ? { headers: { 'Content-Type': 'multipart/form-data' } } : {}
        return api.post('/employees/0/0', data, config)
    },

    // Update employee
    updateEmployee: (id, data) => {
        const config = data instanceof FormData ? { headers: { 'Content-Type': 'multipart/form-data' } } : {}
        return api.post(`/employees/${id}/0`, data, config)
    },

    // Delete employee data (specific type)
    deleteEmployeeData: (typeId, id) => api.delete(`/employees/${typeId}/${id}`),

    // Search employees (for step increment selection)
    searchEmployees: (query) => api.get(`/employees/search?q=${encodeURIComponent(query)}`),

    // Download employee document
    downloadDocument: (id) => api.get(`/employees/${id}/download`)
}

// Employee Assignments / Promotions (frontend naming: Employee Assignments)
export const assignmentApi = {
    // List promotions/assignments
    getAssignments: () => api.get('/promotions'),
    // Form data for create/edit
    getForm: (id = 0) => api.get(`/promotions/add/${id}`),
    // Create or update
    save: (id, payload) => api.post(`/promotions/${id}`, payload),
    // Show single promotion by promotion ID
    show: (id) => api.get(`/promotions/show/${id}`)
}

// Employee Off-boarding
export const offBoardingApi = {
    // List off-boardings
    getOffBoardings: () => api.get('/off-boardings'),
    // Form data for create/edit
    getForm: (id = 0) => api.get(`/off-boardings/add/${id}`),
    // Create or update
    save: (id, payload) => api.post(`/off-boardings/${id}`, payload),
    // Show single off-boarding with employee info
    show: (id, employeeId) => api.get(`/off-boardings/${id}/info/${employeeId}`),
    // Get activation data (reactivate dialog preload)
    activate: (id, employeeId) => api.get(`/off-boardings/${id}/reactivate/${employeeId}`),
    // Reactivate employee (apply reactivation)
    reactivate: (id, employeeId, payload) => api.post(`/off-boardings/${id}/reactivate/${employeeId}`, payload),
    // Rehire (simple toggle on off-boarding record)
    rehire: (id) => api.get(`/off-boardings/rehire/${id}`)
}

// Step Increment - Using EmployeeStepIncrementController routes (the correct ones)
export const stepIncrementApi = {
    // List all step increment periods
    index: () => api.get('/step-increments'),

    // Get form data for creating new step increment
    add: () => api.get('/step-increments/add'),

    // Edit existing step increment for specific month/year
    edit: (monthId, yearId) => api.get(`/step-increments/edit/${monthId}/${yearId}`),

    // Load employees eligible for step increment in specific month/year
    loadEmployees: (monthId, yearId) => api.get(`/step-increments/${monthId}/${yearId}/employees`),

    // Load existing step increment data for specific month/year (for viewing/editing)
    loadStepIncrementData: (monthId, yearId) => api.get(`/step-increments/edit/${monthId}/${yearId}`),

    // Get salary details for specific employee
    getEmployeeSalary: (stepIncrementId, employeeId) => api.get(`/step-increments/${stepIncrementId}/employee/${employeeId}/salary`),

    // Save/create step increment
    store: (payload) => api.post('/step-increments', payload),

    // Forward step increment for specific month/year
    forward: (monthId, yearId) => api.get(`/step-increments/${monthId}/${yearId}/forward`),

    // Forward to approver
    forwardApprover: (monthId, yearId) => api.get(`/step-increments/${monthId}/${yearId}/forward-approver`),

    // Get step increments for processing
    forProcess: (monthId, yearId) => api.get(`/step-increments/${monthId}/${yearId}/for-process`),

    // Approve step increment
    approve: (monthId, yearId) => api.get(`/step-increments/${monthId}/${yearId}/approved`),

    // Reject/disapprove step increment
    reject: (monthId, yearId, remarks) => api.get(`/step-increments/${monthId}/${yearId}/disapproved/${remarks}`),

    // Print step increment report
    print: (monthId, yearId) => api.get(`/step-increments/${monthId}/${yearId}/print`),

    // Delete specific employee from step increment
    deleteEmployee: (stepIncrementId) => api.delete(`/step-increments/${stepIncrementId}/employee`),

    // Get step increment approval process
    getApprovalProcess: () => api.get('/step-increment-approval')
}

// Step Increment Approval API (using StepIncrementController individual approval methods)
export const stepIncrementApprovalApi = {
    // Get all pending step increments for approval
    // Use consolidated approval endpoint that lists forwarded periods
    getPendingApprovals: () => api.get('/step-increment-approval'),

    // Approve step increment
    approve: (id) => api.post(`/step-increment-approval/${id}/approve`),

    // Reject step increment
    reject: (id) => api.post(`/step-increment-approval/${id}/reject`)
}

// Salary Adjustment API
export const salaryAdjustmentApi = {
    // Get all salary schedules
    getSalarySchedules: () => api.get('/salary-adjustments'),

    // Process salary adjustment for all employees
    processSalaryAdjustment: (data) => api.post('/salary-adjustments', data),

    // Get affected employees for a salary schedule
    getAffectedEmployees: (salaryScheduleId) => api.get(`/salary-adjustments/${salaryScheduleId}/affected-employees`),

    // Get already processed employees for a salary schedule
    getProcessedEmployees: (salaryScheduleId) => api.get(`/salary-adjustments/${salaryScheduleId}/processed-employees`),

    // Get employees missing salary adjustment records (with reason guidance)
    getUnprocessedEmployees: (salaryScheduleId) => api.get(`/salary-adjustments/${salaryScheduleId}/unprocessed-employees`)
}

// IPCR API
export const ipcrApi = {
    // Get all IPCR ratings
    getIPCRRatings: () => api.get('/ipcr'),

    // Get form data for creating/editing IPCR
    getFormData: (id = 0) => api.get(`/ipcr/add/${id}`),

    // Create or update IPCR
    saveIPCR: (id, data) => api.post(`/ipcr/${id}`, data),

    // Get IPCR review data
    getReviewData: (id) => api.get(`/ipcr/${id}/review`),

    // Get adjectival rating for numerical rating
    getAdjectivalRating: (rating) => api.get(`/ipcr-adjective/${rating}`),

    // Save employee ratings
    saveRatings: (id, data) => api.post(`/ipcr/${id}/rating`, data),

    // Summary of ratings PDF
    summaryRatings: (id) => api.get(`/ipcr/${id}/summary-ratings`, { responseType: 'blob' }),

    // PMT calibration PDF
    pmtCalibration: (id) => api.get(`/ipcr/${id}/pmt-calibration`, { responseType: 'blob' }),

    // Delete IPCR
    destroy: (id) => api.delete(`/ipcr/${id}`)
}

// OPCR API
export const opcrApi = {
    // Get all OPCR ratings
    getOPCRRatings: () => api.get('/opcr'),

    // Get form data for creating/editing OPCR
    getFormData: (id = 0) => api.get(`/opcr/add/${id}`),

    // Create or update OPCR
    saveOPCR: (id, data) => api.post(`/opcr/${id}`, data),

    // Get OPCR review data
    getReviewData: (id) => api.get(`/opcr/${id}/review`),

    // Get adjectival rating for numerical rating
    getAdjectivalRating: (rating) => api.get(`/opcr-adjective/${rating}`),

    // Save office head ratings
    saveRatings: (id, data) => api.post(`/opcr/${id}/rating`, data),

    // Delete OPCR
    destroy: (id) => api.delete(`/opcr/${id}`)
}

// DPCR API
export const dpcrApi = {
    // Get all DPCR ratings
    getDPCRRatings: () => api.get('/dpcr'),

    // Get form data for creating/editing DPCR
    getFormData: (id = 0) => api.get(`/dpcr/add/${id}`),

    // Create or update DPCR
    saveDPCR: (id, data) => api.post(`/dpcr/${id}`, data),

    // Get DPCR review data
    getReviewData: (id) => api.get(`/dpcr/${id}/review`),

    // Save department head ratings
    saveRating: (id, data) => api.post(`/dpcr/${id}/rating`, data),

    // Delete DPCR
    destroy: (id) => api.delete(`/dpcr/${id}`)
}

// Update 201 Schedule API
export const update201ScheduleApi = {
    // Get all update 201 schedules
    getSchedules: () => api.get('/update-201-schedule'),

    // Create or update schedule
    saveSchedule: (id, data) => api.post(`/update-201-schedule/${id}`, data),

    // Get specific schedule (assuming this route exists based on controller)
    getSchedule: (id) => api.get(`/update-201-schedule/${id}`),

    // Get create form data (assuming this route exists based on controller)
    getCreateForm: () => api.get('/update-201-schedule/create'),

    // Get edit form data (assuming this route exists based on controller)
    getEditForm: (id) => api.get(`/update-201-schedule/${id}/edit`),

    // Update schedule (assuming this route exists based on controller)
    updateSchedule: (id, data) => api.put(`/update-201-schedule/${id}`, data),

    // Delete schedule (assuming this route exists based on controller)
    deleteSchedule: (id) => api.delete(`/update-201-schedule/${id}`),

    // Get employees with pending 201 updates for a schedule
    getEmployees: (id) => api.get(`/update-201-schedule/${id}/employees`)
}

// Employee Request API (for approving 201 updates)
export const employeeRequestApi = {
    // Approve employee request (type_id = 1 for approve, 2 for disapprove)
    approveRequest: (id, typeId, data = {}) => api.post(`/review-201-updates/${id}/approve/${typeId}`, data),

    // Get employee request review data
    getReviewData: (id) => api.get(`/review-201-updates/${id}/review`)
}

// Export Employee Data API
export const exportEmployeeDataApi = {
    // Get employee data for export
    getEmployeeData: () => api.get('/export-employee-data'),

    // Export employee data in different formats
    exportData: (format, filters = {}) => api.get('/export-employee-data', {
        params: { format, ...filters },
        responseType: ['pdf', 'xlsx'].includes(format) ? 'blob' : 'json'
    })
}

// Vacant Position Posting API
export const vacantPositionApi = {
    // Get all vacant positions
    getVacantPositions: () => api.get('/vacant-position-posting'),

    // Get vacant position details
    getVacantPositionDetails: (id, type = 'plantilla') => api.get(`/vacant-position-details/${id}`, { params: { type } }),

    // Process vacant position (approve/disapprove/cancel)
    processVacantPosition: (id, processId, type = 'plantilla') =>
        api.post(`/vacant-position-process/${id}/${processId}`, null, { params: { type } })
}

// Length of Service API
export const lengthOfServiceApi = {
    // Get all length of service records
    getLengthOfServiceRecords: () => api.get('/length-of-service')
}

// Personal Data Sheet API
export const personalDataSheetApi = {
    // Get all employees for PDS selection
    getEmployees: () => api.get('/pds'),

    // Generate PDS PDF for specific employee
    generatePDF: (employeeId) => api.post('/pds/print', { employee: employeeId }, { responseType: 'blob' }),

    // Download PDS PDF for specific employee
    downloadPDF: (employeeId) => api.get(`/pds/${employeeId}/download`, { responseType: 'blob' })
}

// NOSI (Notice of Salary Step Increment) API
export const nosiApi = {
    // Get all employees with approved step increments
    getEmployees: () => api.get('/nosi'),

    // Generate NOSI PDF with form data
    generatePDF: (formData) => api.post('/nosi/print', formData, { responseType: 'blob' })
}

// NOSA (Notice of Salary Adjustment) API
export const nosaApi = {
    // Get all plantilla employees and salary schedules
    getEmployeesAndSchedules: () => api.get('/nosa'),

    // Generate NOSA PDF with form data
    generatePDF: (formData) => api.post('/nosa/print', formData, { responseType: 'blob' })
}

// Terminal Leave Endorsement API
export const terminalLeaveEndorsementApi = {
    // Get all employees for terminal leave endorsement
    getEmployees: () => api.get('/terminal-leave-endorsements'),

    // Get endorsement data for specific employee  
    getEndorsementData: (employeeId) => api.post('/terminal-leave-endorsements/data', { employee: employeeId }),

    // Generate Terminal Leave Endorsement PDF
    generatePDF: (formData) => api.post('/terminal-leave-endorsements/print', formData, { responseType: 'blob' })
}

// Plantilla Report API
export const plantillaReportApi = {
    // Get all plantilla data
    getPlantillaData: () => api.get('/plantilla-reports'),

    // Generate Plantilla Report PDF (vacant or occupied)
    generatePDF: (statusId) => api.post('/plantilla-reports/print', { status_id: statusId }, { responseType: 'blob' })
}

// Employee Certificate API
export const employeeCertificateApi = {
    // Get all employees for certificate generation
    getEmployees: () => api.get('/employee-certificates'),

    // Get form structure for certificate creation
    getCreateForm: () => api.get('/employee-certificates/create'),

    // Get specific employee certificate data
    getEmployeeData: (employeeId) => api.get(`/employee-certificates/${employeeId}`),

    // Generate Employee Certificate PDF
    generatePDF: (formData) => api.post('/employee-certificates/print', formData, { responseType: 'blob' }),

    // Generate Employee Certificate Word
    generateWord: (formData) => api.post('/employee-certificates/word', formData, { responseType: 'blob' })
}

// Salary Deduction Certificate API
export const salaryDeductionCertificateApi = {
    getEmployees: () => api.get('/salary-deduction/employees'),
    generatePDF: (formData) => api.post('/salary-deduction/print', formData, { responseType: 'blob' }),
    generateWord: (formData) => api.post('/salary-deduction/word', formData, { responseType: 'blob' })
}

// Rendered Service Certificate API
export const renderedServiceCertificateApi = {
    getEmployees: () => api.get('/rendered-service/employees'),
    generatePDF: (formData) => api.post('/rendered-service/print', formData, { responseType: 'blob' })
}

// COS Certificate API
export const cosCertApi = {
    getEmployees: () => api.get('/cos-cert/employees'),
    generatePDF: (formData) => api.post('/cos-cert/print', formData, { responseType: 'blob' }),
    generateWord: (formData) => api.post('/cos-cert/word', formData, { responseType: 'blob' })
}

// COS Contract Certificate API
export const cosContractCertificateApi = {
    getEmployees: () => api.get('/cos-contract-cert/employees'),
    getSignatories: () => api.get('/cos-contract-cert/signatories'),
    generatePDF: (formData) => api.post('/cos-contract-cert/print', formData, { responseType: 'blob' }),
    generateWord: (formData) => api.post('/cos-contract-cert/word', formData, { responseType: 'blob' })
}

// COS Non-Disclosure Agreement API
export const cosNDAApi = {
    getEmployees: () => api.get('/cos-nda/employees'),
    getSignatories: () => api.get('/cos-nda/signatories'),
    generatePDF: (formData) => api.post('/cos-nda/print', formData, { responseType: 'blob' }),
    generateWord: (formData) => api.post('/cos-nda/word', formData, { responseType: 'blob' })
}

// Employee Certificate of Compensation API
export const employeeCompensationCertificateApi = {
    // Get all employees for compensation certificate
    getEmployees: () => api.get('/employee-compensation-certificates'),

    // Get form structure
    getCreateForm: () => api.get('/employee-compensation-certificates/create'),

    // Get detailed compensation data for specific employee
    getEmployeeData: (employeeId) => api.get(`/employee-compensation-certificates/${employeeId}`),

    // Generate Compensation Certificate PDF
    generatePDF: (formData) => api.post('/employee-compensation-certificates/print', formData, { responseType: 'blob' }),

    // Generate Compensation Certificate Word
    generateWord: (formData) => api.post('/employee-compensation-certificates/word', formData, { responseType: 'blob' })
}

// Employee Medical Certificate API
export const employeeMedicalCertificateApi = {
    getEmployees: () => api.get('/employee-medical-certificates'),
    getCreateForm: () => api.get('/employee-medical-certificates/create'),
    getEmployeeData: (employeeId) => api.get(`/employee-medical-certificates/${employeeId}`),
    generatePDF: (formData) => api.post('/employee-medical-certificates/print', formData, { responseType: 'blob' })
}

// Service Record API
export const serviceRecordApi = {
    getEmployees: () => api.get('/service-record'),
    generatePDF: (formData) => api.post('/service-record/print', formData, { responseType: 'blob' }),
    generateExcel: (formData) => api.post('/service-record/excel', formData, { responseType: 'blob' })
}

// Acceptance of Resignation API
export const acceptanceOfResignationApi = {
    getEmployees: () => api.get('/acceptance-of-resignation'),
    generatePDF: (formData) => api.post('/acceptance-of-resignation/print', formData, { responseType: 'blob' }),
    generateWord: (formData) => api.post('/acceptance-of-resignation/word', formData, { responseType: 'blob' }),
    previewWord: (formData) => api.post('/acceptance-of-resignation/word-preview', formData, { responseType: 'blob' })
}

// Acceptance of Retirement API
export const acceptanceOfRetirementApi = {
    getEmployees: () => api.get('/acceptance-of-retirement'),
    generatePDF: (formData) => api.post('/acceptance-of-retirement/print', formData, { responseType: 'blob' }),
    generateWord: (formData) => api.post('/acceptance-of-retirement/word', formData, { responseType: 'blob' }),
    previewWord: (formData) => api.post('/acceptance-of-retirement/word-preview', formData, { responseType: 'blob' })
}

// Acceptance Letter Intern API
export const acceptanceLetterInternApi = {
    getAll: () => api.get('/acceptance-letter-intern'),
    getStudents: (id) => api.get(`/acceptance-letter-intern/${id}/students`),
    store: (formData) => api.post('/acceptance-letter-intern/store', formData),
    updateStudentStatus: (studentId, isActive) => api.put(`/acceptance-letter-intern/students/${studentId}/status`, { is_active: isActive }),
    generatePDF: (formData) => api.post('/acceptance-letter-intern/print', formData, { responseType: 'blob' }),
    generateWord: (formData) => api.post('/acceptance-letter-intern/word', formData, { responseType: 'blob' }),
    previewWord: (formData) => api.post('/acceptance-letter-intern/word-preview', formData, { responseType: 'blob' })
}

// Certificate of Completion API
export const certOfCompletionApi = {
    getActiveStudents: () => api.get('/cert-of-completion/students'),
    generatePDF: (formData) => api.post('/cert-of-completion/print', formData, { responseType: 'blob' }),
    generateWord: (formData) => api.post('/cert-of-completion/word', formData, { responseType: 'blob' })
}

// Certificate of Last Salary API
export const certOfLastSalaryApi = {
    getEmployees: () => api.get('/cert-of-last-salary/employees'),
    getEmployeeDetails: (id) => api.get(`/cert-of-last-salary/employees/${id}`),
    generatePDF: (formData) => api.post('/cert-of-last-salary/print', formData, { responseType: 'blob' })
}

// Last Day of Service (Offboarding Certificate) API
export const lastDayOfServiceApi = {
    getEmployees: () => api.get('/offboarding-certificates'),
    generatePDF: (formData) => api.post('/offboarding-certificates/print', formData, { responseType: 'blob' }),
    generateWord: (formData) => api.post('/offboarding-certificates/word', formData, { responseType: 'blob' })
}

// No Pending Certificate API
export const noPendingCertificateApi = {
    getEmployees: () => api.get('/no-pending-certificates'),
    generatePDF: (formData) => api.post('/no-pending-certificates/print', formData, { responseType: 'blob' }),
    generateWord: (formData) => api.post('/no-pending-certificates/word', formData, { responseType: 'blob' }),
    generateExcel: (formData) => api.post('/no-pending-certificates/excel', formData, { responseType: 'blob' })
}

// Clearance Certificate API
export const clearanceCertificateApi = {
    list: () => api.get('/clearance-cert'),
    getEmployees: () => api.get('/clearance-cert/employees'),
    getClearingOfficers: () => api.get('/clearance-cert/clearing-officers'),
    getPurposes: () => api.get('/clearance-cert/purposes'),
    create: (payload) => api.post('/clearance-cert', payload),
    print: (id, queryParams = '') => api.get(`/clearance-cert/print?id=${id}${queryParams ? '&' + queryParams : ''}`, { responseType: 'blob' }),
    generateWord: (id, queryParams = '') => api.get(`/clearance-cert/word?id=${id}${queryParams ? '&' + queryParams : ''}`, { responseType: 'blob' }),
    delete: (id) => api.delete(`/clearance-cert/${id}`)
}

// Transfer Leave Credits API
export const transferLeaveCreditsApi = {
    getEmployees: () => api.get('/transfer-leave-credits/employees'),
    getLeaveCredits: (employeeId) => api.get(`/transfer-leave-credits/employee/${employeeId}/credits`),
    create: (payload) => api.post('/transfer-leave-credits', payload),
    list: () => api.get('/transfer-leave-credits'),
    print: (id, params) => api.get(`/transfer-leave-credits/${id}/print`, { params, responseType: 'blob' }),
    generateWord: (id, params) => api.get(`/transfer-leave-credits/${id}/word`, { params, responseType: 'blob' })
}

// ATM Request Certificate API
export const atmRequestCertificateApi = {
    getEmployees: () => api.get('/atm-request-certificates'),
    generatePDF: (formData) => api.post('/atm-request-certificates/print', formData, { responseType: 'blob' }),
    generateWord: (formData) => api.post('/atm-request-certificates/word', formData, { responseType: 'blob' })
}

// Appearance Certificate API
export const appearanceCertificateApi = {
    getEmployees: () => api.get('/appearance-certificates'),
    generatePDF: (formData) => api.post('/appearance-certificates/print', formData, { responseType: 'blob' }),
    generateWord: (formData) => api.post('/appearance-certificates/word', formData, { responseType: 'blob' })
}

// Acceptance Letter API
export const acceptanceLetterApi = {
    getApplicants: () => api.get('/acceptance-letter/applicants'),
    getSignatories: () => api.get('/acceptance-letter/signatories'),
    getDepartments: () => api.get('/acceptance-letter/departments'),
    generatePDF: (formData) => api.post('/acceptance-letter/print', formData, { responseType: 'blob' }),
    generateWord: (formData) => api.post('/acceptance-letter/word', formData, { responseType: 'blob' }),
    sendEmail: (formData) => api.post('/acceptance-letter/send-email', formData)
}

// OJT Certificate API
export const ojtCertificateApi = {
    getAll: () => api.get('/ojt-certificates'),
    getFormData: () => api.get('/ojt-certificates/create'),
    create: (payload) => api.post('/ojt-certificates', payload),
    edit: (id) => api.get(`/ojt-certificates/${id}/edit`),
    update: (id, payload) => api.patch(`/ojt-certificates/${id}`, payload),
    print: (id) => api.get(`/ojt-certificates/${id}/print`, { responseType: 'blob' })
}

// Applicant (Appointment) Certificate API
export const applicantCertificateApi = {
    getEmployees: () => api.get('/appointment-certificate'),
    generatePDF: (formData) => api.post('/appointment-certificate/print', formData, { responseType: 'blob' }),
    generateWord: (formData) => api.post('/appointment-certificate/word', formData, { responseType: 'blob' }),
    sendEmail: (formData) => api.post('/appointment-certificate/send-email', formData)
}

// Assumption of Duty API
export const assumptionOfDutyApi = {
    getEmployees: () => api.get('/assumption-of-duty'),
    generatePDF: (formData) => api.post('/assumption-of-duty/print', formData, { responseType: 'blob' }),
    downloadDocx: (form) => api.post('/assumption-of-duty/download-docx-v2', form, { responseType: 'blob' }),
    sendEmail: (formData) => api.post('/assumption-of-duty/send-email', formData)
}

// Oath of Office API
export const oathOfOfficeApi = {
    getEmployees: () => api.get('/oath-of-office'),
    generatePDF: (formData) => api.post('/oath-of-office/print', formData, { responseType: 'blob' }),
    generateWord: (formData) => api.post('/oath-of-office/word', formData, { responseType: 'blob' })
}

// Congratulatory Letter API
export const congratulatoryLetterApi = {
    getEmployees: () => api.get('/congratulatory-letter'),
    generatePDF: (formData) => api.post('/congratulatory-letter/print', formData, { responseType: 'blob' })
}

// EETE Rating Setup (EETEController)
export const eeteRatingSetupApi = {
    getSetup: () => api.get('/eete-rating-setup')
}

// Applicant Qualification (ApplicantHiringController)
export const applicantQualificationApi = {
    // List plantilla and non-plantilla positions with counts
    getPositions: () => api.get('/applicant-hiring'),

    // List applicants for a specific position
    getApplicantsByPosition: (id, type) => api.get(`/applicant-hiring/${id}/${type}`),

    // Get applicants with EETE ratings included
    getApplicantsWithRatings: (id, type) => api.get(`/applicant-hiring/${id}/${type}?include_ratings=true`),

    // Get full info bundle for an applicant
    getApplicantInfo: (id) => api.get(`/applicant-hiring-info/${id}`),

    // Save EETE rating/review
    saveRating: (id, payload) => api.post(`/applicant-eete-rating/${id}`, payload),

    // Save EETE rating without email notification (alternative endpoint)
    saveRatingNoEmail: (id, payload) => api.post(`/applicant-eete-rating/${id}?no_email=1`, payload),

    // Try using PUT method as alternative
    updateRating: (id, payload) => api.put(`/applicant-eete-rating/${id}`, payload),

    // Try different endpoint structure
    saveRatingAlt: (id, payload) => api.post(`/applicant-eete-rating-save/${id}`, payload),

    // Process applicant: hire or not qualified
    processApplicant: (applicantId, positionAppliedId, isPlantilla, actionType) =>
        api.get(`/applicant-process/${applicantId}/${positionAppliedId}/${isPlantilla}/${actionType}`),

    // Single attachment info/download
    getResume: (attachmentId) => api.get(`/applicant-resume/${attachmentId}`),

    // Zip download info
    getZip: (positionId, positionName) => api.get(`/applicant-download-zip/${positionId}/${encodeURIComponent(positionName)}`)
}

// Applicant Records (ApplicantsController) for detailed applicant lists
export const applicantRecordsApi = {
    // List applicants by plantilla id (SSR-like endpoint)
    listApplicants: (plantillaId) => api.get(`/applicant-list/${plantillaId}`),
    // Get applicant info bound to plantilla id
    applicantInfo: (applicantId, plantillaId) => api.get(`/applicant-info/${applicantId}/${plantillaId}`)
}

// Applicant Shortlisting API
export const applicantShortlistingApi = {
    index: () => api.get('/applicant-shortlisting'),
    add: (id, rating, statusId, positionAppliedId) => {
        const url = positionAppliedId
            ? `/applicant-shortlisting/add/${id}/${rating}/${statusId}/${positionAppliedId}`
            : `/applicant-shortlisting/add/${id}/${rating}/${statusId}`
        return api.get(url)
    },
    proceed: (applicantId, positionAppliedId) => {
        const url = positionAppliedId
            ? `/applicant-shortlisting/proceed/${applicantId}/${positionAppliedId}`
            : `/applicant-shortlisting/proceed/${applicantId}`
        return api.post(url)
    },
    remove: (shortlistedId) => api.delete(`/applicant-shortlisting/${shortlistedId}`)
}



// Recruitment: Applicants Records
export const applicantsRecordsApi = {
    index: () => api.get('/applicants-records'),
    applicationStatuses: () => api.get('/applicants-records/application-statuses'),
    updateStatus: (payload) => api.post('/applicants-records/update-status', payload),
    progress: (applicantId, positionAppliedId) => {
        const url = positionAppliedId
            ? `/applicants-records/progress/${applicantId}/${positionAppliedId}`
            : `/applicants-records/progress/${applicantId}`
        return api.get(url)
    },
}

// Examination API (ExaminationController)
export const examinationApi = {
    // Examination setup list
    index: () => api.get('/examination-setup'),

    // Load form data for add/edit
    add: (id = 0) => api.get(`/examination-setup/add/${id}`),

    // Save examination setup header
    store: (id, payload) => api.post(`/examination-setup/${id}`, payload),

    // Delete examination setup header
    destroy: (id) => api.delete(`/examination-setup/${id}`),

    // Add positions to exam
    addPosition: (id, payload) => api.post(`/examination-setup/${id}/position`, payload),

    // Delete position from exam
    deletePosition: (id) => api.delete(`/examination-setup/${id}/position`),

    // Questionnaires (type scoped)
    questionnaires: (id, typeId) => api.get(`/examination-setup/${id}/questionnaire/${typeId}`),

    // Category item count (for passing score validation)
    categoryItemsCount: (categoryId) => api.get(`/examination-categories/${categoryId}/items-count`),

    // Schedules list
    schedules: () => api.get('/examination-schedules'),

    // Load schedule form data
    schedulesAdd: (id = 0) => api.get(`/examination-schedules/add/${id}`),

    // Save schedule
    schedulesStore: (id, payload) => api.post(`/examination-schedules/${id}`, payload),

    // Post/Unpost schedule (typeId: 1=post, else=unpost)
    scheduleProcess: (id, typeId) => api.get(`/examination-schedules/${id}/process/${typeId}`),

    // Schedule result
    schedulesResult: (id) => api.get(`/examination-schedules/${id}/result`),

    // Add examinees to schedule
    addExaminees: (id, payload) => api.post(`/examination-schedules/${id}/examinees`, payload),

    // Delete examinee
    deleteExaminee: (id) => api.delete(`/examination-schedules/${id}/examinees`),

    // Delete entire examination schedule
    deleteSchedule: (id) => api.delete(`/examination-schedules/${id}`),

    // Get applicant exam answers (for schedule details View Answers)
    examineeAnswers: (applicantExaminationId) => api.get(`/applicant-examination-answers/${applicantExaminationId}`),
    // HR review for essay answers
    reviewExamineeAnswers: (applicantExaminationId, payload) =>
        api.post(`/applicant-examination-answers/${applicantExaminationId}/review`, payload),
    // Manual result tagging for psych examinees
    tagExamineeResult: (applicantExaminationId, payload) =>
        api.post(`/applicant-examination-answers/${applicantExaminationId}/tag-result`, payload)
}

// Panel Interview API (InterviewController)
export const interviewApi = {
    // Interview Schedules (Setup)
    index: () => api.get('/interview-schedules'),
    add: (id = 0) => api.get(`/interview-schedules/add/${id}`),
    store: (id, payload) => api.post(`/interview-schedules/${id}`, payload),
    addPanel: (id, payload) => api.post(`/interview-schedules/${id}/panel`, payload),
    deletePanel: (rowId) => api.delete(`/interview-schedules/${rowId}/panel`),
    addApplicant: (id, payload) => api.post(`/interview-schedules/${id}/applicant`, payload),
    deleteApplicant: (rowId) => api.delete(`/interview-schedules/${rowId}/applicant`),
    process: (id, typeId) => api.get(`/interview-schedules/${id}/process/${typeId}`),
    delete: (id) => api.delete(`/interview-schedules/${id}`),
    downloadPanelRatingDocument: (ratingId) =>
        api.get(`/interview-schedules/panel-rating-document/${ratingId}`, { responseType: 'blob' }),

    // Panel routes (panel execution views)
    panelIndex: (id) => api.get(`/panel-interviews/${id}`),
    panelPds: (id, applicantId) => api.get(`/panel-interviews/${id}/applicant-pds/${applicantId}`),
    panelExam: (id, applicantId) => api.get(`/panel-interviews/${id}/applicant-exam/${applicantId}`),
    panelRatingMeta: (id, applicantId, employeeId, interviewId) => api.get(`/panel-interviews/${id}/applicant-rating/${applicantId}/${employeeId}/${interviewId}`),
    submitPanelRating: (id, ratingId, payload) => api.post(`/panel-interviews/${id}/applicant-rating/${ratingId}`, payload),
}

// HRDD Review API (HRDDReviewController)
export const hrddApi = {
    positions: () => api.get('/hrdd-review-per-position'),
    list: (positionId, type = 'plantilla') =>
        api.get(`/hrdd-review/${positionId}`, {
            params: type === 'non_plantilla' ? { type: 'non_plantilla' } : {},
        }),
    pds: (applicantId) => api.get(`/hrdd-review/pds/${applicantId}`),
    exam: (applicantId) => api.get(`/hrdd-review/exam/${applicantId}`),
    forwardMeta: (applicantId) => api.get(`/hrdd-review/forward/${applicantId}`),
    submitForward: (applicantId, payload) => api.post(`/hrdd-review/forward/${applicantId}`, payload, { headers: { 'Content-Type': 'multipart/form-data' } }),
    saveBatch: (payload) => api.post('/hrdd-review', payload, { headers: { 'Content-Type': 'multipart/form-data' } }),
    deleteDoc: (id, typeId) => api.delete(`/hrdd-review/docs/${id}/${typeId}`)
}

// Administrator Selection API (AdministratorSelectionController)
export const adminSelectApi = {
    // Get all positions with forwarded applicants
    list: () => api.get('/administrator-selection-per-position'),

    // Get applicants for a specific position (type disambiguates plantilla vs non_plantilla id collisions)
    listbyPosition: (positionID, type = 'plantilla') =>
        api.get(`/administrator-selection/${positionID}`, {
            params: type === 'non_plantilla' ? { type: 'non_plantilla' } : {},
        }),

    // Get PDS data for an applicant
    pds: (applicantID) => api.get(`/administrator-selection/${applicantID}/pds`),

    // Get exam results for an applicant
    exam: (applicantID) => api.get(`/administrator-selection/${applicantID}/exam`),

    // Get HRDD rating and documents for an applicant
    hrrdRating: (applicantID) => api.get(`/administrator-selection/${applicantID}/hrdd-rating`),

    // Download document (typeID: 1=BI, 2=BR)
    download: (applicantID, typeID) => api.get(`/administrator-selection/${applicantID}/download/${typeID}`, { responseType: 'blob' }),

    // Appoint an applicant (pass position context when id can collide between plantilla / non_plantilla)
    appoint: (applicantID, options = {}) => {
        const params = {}
        if (options.positionAppliedId != null && options.positionAppliedId !== '') {
            params.position_applied_id = options.positionAppliedId
        }
        if (options.type === 'non_plantilla') {
            params.type = 'non_plantilla'
        }
        return api.get(`/administrator-selection/${applicantID}/appoint`, { params })
    },

    // Upload board resolution documents
    brUpload: (applicantID, formData) => api.post(`/administrator-selection/${applicantID}/br-upload`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
    }),

    // Print CS Form 5
    print: (formData) => api.post('/cs-form5-print', formData, { responseType: 'blob' })
}

// Work Experience API functions
export const workExperienceApi = {
    // Get all work experiences
    getAll: () => api.get('/work-experiences'),

    // Get work experience data
    getData: (employeeId) => api.get(`/work-experience/${employeeId}`),

    // Generate work experience sheet preview
    generatePreview: (formData) => api.post('/work-experience/preview', formData, { responseType: 'blob' }),

    // Download work experience PDF
    downloadPDF: (formData) => api.post('/work-experience/download', formData, { responseType: 'blob' }),
    downloadWord: (formData) => api.post('/work-experience/download-word', formData, { responseType: 'blob' }),
    downloadDocx: (formData) => api.post('/work-experience/download-docx', formData, { responseType: 'blob' })
}

// Position Description API
export const positionDescriptionApi = {
    // Get all positions for position description
    getPositions: () => api.get('/position-description/positions'),

    // Generate Position Description PDF
    generatePDF: (formData) => api.post('/position-description/print', formData, { responseType: 'blob' }),

    // Download Position Description DOCX
    downloadDocx: (formData) => api.post('/position-description/download-docx', formData, { responseType: 'blob' }),
    downloadDocxV2: (formData) => api.post('/position-description/download-docx-v2', formData, { responseType: 'blob' }),

    // PDF Records Management
    getPdfRecords: () => api.get('/position-description/pdf-records'),
    getPdfRecord: (id) => api.get(`/position-description/pdf-records/${id}`),
    createPdfRecord: (data) => api.post('/position-description/pdf-records', data),
    updatePdfRecord: (id, data) => api.put(`/position-description/pdf-records/${id}`, data),
    deletePdfRecord: (id) => api.delete(`/position-description/pdf-records/${id}`),

    // SODAR Records Management
    addSodarRecord: (data) => api.post('/position-description/sodar', data),
    deleteSodarRecord: (id) => api.delete(`/position-description/sodar/${id}`),

    // Competencies Management
    addCoreCompetency: (data) => api.post('/position-description/core-competencies', data),
    deleteCoreCompetency: (id) => api.delete(`/position-description/core-competencies/${id}`),
    addLeadershipCompetency: (data) => api.post('/position-description/leadership-competencies', data),
    deleteLeadershipCompetency: (id) => api.delete(`/position-description/leadership-competencies/${id}`),
    getCompetencyLevels: () => api.get('/position-description/competency-levels'),
    getPlantillaItemNumber: (positionId) => api.get(`/position-description/plantilla-item-number/${positionId}`),

    // Supervised Positions Management
    addSupervisedPosition: (data) => api.post('/position-description/supervised-positions', data),
    deleteSupervisedPosition: (id) => api.delete(`/position-description/supervised-positions/${id}`),

    // Work Experience Management
    getWorkExperience: (referenceId = null) => {
        const url = referenceId
            ? `/position-description/work-experience/${referenceId}`
            : '/position-description/work-experience'
        return api.get(url)
    },
    addWorkExperience: (data) => api.post('/position-description/work-experience', data),

    // Additional Data Sources
    getEmployees: () => api.get('/employees'),
    getApplicants: () => api.get('/position-description/applicants'),
    getSalaryGrades: () => api.get('/salary-grades')
}

// Request for Publication API
export const requestForPublicationApi = {
    getPlantillas: () => api.get('/request-for-publication/plantillas'),
    getFormData: () => api.get('/request-for-publication/form-data'),
    list: () => api.get('/request-for-publication'),
    create: (payload) => api.post('/request-for-publication', payload),
    print: (payload) => api.post('/request-for-publication/print', payload, { responseType: 'blob' }),
    saveHrmoEmail: (payload) => api.put('/request-for-publication/hrmo-email', payload)
}

// Birthday Summary API
export const birthdaySummaryApi = {
    generatePDF: (month = null) => {
        const payload = month ? { month } : {}
        return api.post('/birthday-summary/print', payload, { responseType: 'blob' })
    }
}

export const regretLetterApi = {
    index: () => api.get('/regret-letter'),
    generateLetter: (id) => api.get(`/regret-letter/${id}/generate`, { responseType: 'blob' }),
    generateWord: (id) => api.get(`/regret-letter/${id}/word`, { responseType: 'blob' })
}


export const newlyHiredAndPromotedApi = {
    index: () => api.get('/newly-hired-and-promotions'),
    generatePDF: () =>
        api.post('/newly-hired-and-promotions/print', {}, { responseType: 'blob' }),
    generateWord: () =>
        api.post('/newly-hired-and-promotions/word', {}, { responseType: 'blob' }),
    generateExcel: () =>
        api.post('/newly-hired-and-promotions/excel', {}, { responseType: 'blob' }),
}

// Announcement API functions for HR Overview & Management
export const announcementApi = {
    getAnnouncements: () => api.get('/announcements'),
    createAnnouncement: (data) => api.post('/announcements', data),
    getAnnouncementEmployees: () => api.get('/announcements/employees')
}

export default api

