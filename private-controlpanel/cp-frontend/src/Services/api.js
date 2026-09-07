import { API_CONFIG } from '../config/api.js'

// Base API configuration
const API_BASE_URL = API_CONFIG.BASE_URL

// API service class
class ApiService {
    constructor() {
        this.baseURL = API_BASE_URL
        this.defaultHeaders = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        }
    }

    // Get headers
    getHeaders() {
        const headers = { ...this.defaultHeaders }

        // Add authentication token if available
        const token = localStorage.getItem('auth_token')
        if (token) {
            headers['Authorization'] = `Bearer ${token}`
        }

        return headers
    }

    // Build query string from params (supports nested objects like search[value])
    buildQueryString(params = {}) {
        const pairs = []

        const add = (key, value) => {
            if (value === undefined || value === null || value === '') return
            pairs.push(`${encodeURIComponent(key)}=${encodeURIComponent(String(value))}`)
        }

        const walk = (prefix, obj) => {
            if (obj === undefined || obj === null) return
            if (Array.isArray(obj)) {
                obj.forEach((v, i) => walk(`${prefix}[${i}]`, v))
                return
            }
            if (typeof obj === 'object') {
                Object.keys(obj).forEach(k => walk(prefix ? `${prefix}[${k}]` : k, obj[k]))
                return
            }
            add(prefix, obj)
        }

        walk('', params)
        return pairs.length ? `?${pairs.join('&')}` : ''
    }

    // Generic request method
    async request(endpoint, options = {}) {
        const query = options.params ? this.buildQueryString(options.params) : ''
        const url = `${this.baseURL}${endpoint}${query}`
        const config = {
            ...options
        }

        // Params are already encoded into the URL
        if (config.params) delete config.params

        // Ensure session cookies are sent (Laravel auth middleware)
        if (!config.credentials) {
            config.credentials = 'include'
        }

        // Set headers if not already set
        if (!config.headers) {
            config.headers = this.getHeaders()
        }

        try {
            const response = await fetch(url, config)

            // Try to parse JSON if possible; otherwise, read text
            const contentType = response.headers.get('content-type') || ''
            let payload
            if (contentType.includes('application/json')) {
                try {
                    payload = await response.json()
                } catch (e) {
                    payload = { success: false, message: 'Invalid JSON response' }
                }
            } else {
                const text = await response.text()
                payload = {
                    success: false,
                    message: this.normalizeErrorMessage(text, response.status),
                    status: response.status
                }
            }

            // Always return the payload so callers can handle errors centrally
            if (!response.ok) {
                return payload
            }

            return payload
        } catch (error) {
            console.error(`API request failed for ${endpoint}:`, error)
            return { success: false, message: error.message || 'Network error' }
        }
    }

    normalizeErrorMessage(text = '', status = 0) {
        const body = (text || '').trim()
        const tooLargePattern = /too large|post too large|content length exceeded|upload_max_filesize|entity too large/i

        if (status === 413 || tooLargePattern.test(body)) {
            return 'Uploaded file is too large. Maximum allowed size is 10 MB.'
        }

        if (body && !body.startsWith('<') && !body.includes('<!DOCTYPE') && body.length <= 500) {
            return body
        }

        if (body && tooLargePattern.test(body)) {
            return 'Uploaded file is too large. Maximum allowed size is 10 MB.'
        }

        if (status >= 500) {
            return 'Server error. Please try again or use a smaller file (max 10 MB).'
        }

        return 'Unable to complete the request. If you uploaded a file, ensure it is under 10 MB.'
    }

    // GET request
    async get(endpoint, options = {}) {
        return this.request(endpoint, {
            method: 'GET',
            ...options
        })
    }

    // POST request
    async post(endpoint, data, options = {}) {
        const config = {
            method: 'POST',
            ...options
        }

        // Handle FormData vs JSON
        if (data instanceof FormData) {
            // For FormData, set headers but exclude Content-Type (browser will set it with boundary)
            const headers = this.getHeaders()
            delete headers['Content-Type'] // Remove Content-Type for FormData
            config.headers = { ...headers, ...options.headers }
            config.body = data
        } else {
            config.headers = { ...this.getHeaders(), ...options.headers }
            config.body = JSON.stringify(data)
        }

        return this.request(endpoint, config)
    }

    // PUT request
    async put(endpoint, data, options = {}) {
        return this.request(endpoint, {
            method: 'PUT',
            body: JSON.stringify(data),
            ...options
        })
    }

    // PATCH request
    async patch(endpoint, data, options = {}) {
        return this.request(endpoint, {
            method: 'PATCH',
            body: JSON.stringify(data),
            ...options
        })
    }

    // DELETE request
    async delete(endpoint, options = {}) {
        return this.request(endpoint, {
            method: 'DELETE',
            ...options
        })
    }

    // Authentication methods (disabled for now)
    // async login(credentials) {
    //   return this.post('/login', credentials)
    // }

    // async logout() {
    //   return this.post('/logout')
    // }

    // async getProfile() {
    //   return this.get('/profile')
    // }

    // async refreshToken() {
    //   return this.post('/refresh')
    // }

    // User management methods (matching backend routes)
    async getUsers() {
        return this.get('/users')
    }

    async addUsers(userData) {
        return this.post('/users', userData)
    }

    // Note: Update and delete routes not found in backend, will add when available
    // async updateUser(userId, userData) {
    //   return this.put(`/users/${userId}`, userData)
    // }

    // async deleteUser(userId) {
    //   return this.delete(`/users/${userId}`)
    // }

    // async toggleUserLock(userId) {
    //   return this.patch(`/users/${userId}/toggle-lock`)
    // }

    async resetUserPassword(userId, newPassword = null) {
        return this.post('/users/reset-password', { user_id: userId, new_password: newPassword })
    }

    // Employee methods
    async getAvailableEmployees() {
        return this.get('/users') // This endpoint returns both users and employees
    }

    // Authentication methods
    async login(credentials) {
        console.log('API Service: Login request to /login with:', credentials)
        const response = await this.post('/login', credentials)
        console.log('API Service: Login response:', response)
        return response
    }

    async devLogin(email = null) {
        console.log('API Service: Dev login request to /dev-login with email:', email)
        const data = email ? { email } : {}
        const response = await this.post('/dev-login', data)
        console.log('API Service: Dev login response:', response)
        return response
    }

    async logout() {
        const response = await this.post('/logout')
        // Clear stored token
        localStorage.removeItem('auth_token')
        localStorage.removeItem('user_data')
        return response
    }

    async getProfile() {
        return this.get('/user')
    }

    // Access Rights methods
    async getAccessRights(userId) {
        return this.get(`/access-rights/${userId}`)
    }

    async updateAccessRights(userId, data) {
        return this.post(`/access-rights/${userId}`, data)
    }

    // HR Setup methods
    async getCompanySetup() {
        return this.get('/companies')
    }

    async saveCompanySetup(companyData) {
        return this.post('/companies', companyData)
    }

    async getBranchSetup() {
        return this.get('/branches')
    }

    async saveBranchSetup(branchData) {
        return this.post('/branches', branchData)
    }

    async deleteBranch(branchId) {
        return this.delete(`/branches/${branchId}`)
    }

    async getBranchForDelete(branchId) {
        return this.get(`/branches/${branchId}/delete`)
    }

    async getBranchForEdit(branchId) {
        return this.get(`/branches/${branchId}/edit`)
    }

    // Office/Department Setup methods
    async getOfficeSetup() {
        return this.get('/departments')
    }

    async getOfficeFormData() {
        return this.get('/departments/create')
    }

    async saveOfficeSetup(officeData) {
        return this.post('/departments', officeData)
    }

    async updateOfficeSetup(officeId, officeData) {
        return this.patch(`/departments/${officeId}`, officeData)
    }

    async deleteOffice(officeId) {
        return this.delete(`/departments/${officeId}`)
    }

    async getOfficeForEdit(officeId) {
        return this.get(`/departments/${officeId}/edit`)
    }

    async getOfficeDetails(officeId) {
        return this.get(`/departments/${officeId}`)
    }

    // Division Setup methods
    async getDivisionSetup() {
        return this.get('/divisions')
    }

    async getDivisionFormData() {
        return this.get('/divisions/create')
    }

    async saveDivisionSetup(divisionData) {
        return this.post('/divisions', divisionData)
    }

    async updateDivisionSetup(divisionId, divisionData) {
        return this.patch(`/divisions/${divisionId}`, divisionData)
    }

    async deleteDivision(divisionId) {
        return this.delete(`/divisions/${divisionId}`)
    }

    async getDivisionForEdit(divisionId) {
        return this.get(`/divisions/${divisionId}/edit`)
    }

    async getDivisionDetails(divisionId) {
        return this.get(`/divisions/${divisionId}`)
    }

    // Section Setup methods
    async getSectionSetup() {
        return this.get('/sections')
    }

    async getSectionFormData() {
        return this.get('/sections/create')
    }

    async saveSectionSetup(sectionData) {
        return this.post('/sections', sectionData)
    }

    async updateSectionSetup(sectionId, sectionData) {
        return this.patch(`/sections/${sectionId}`, sectionData)
    }

    async deleteSection(sectionId) {
        return this.delete(`/sections/${sectionId}`)
    }

    async getSectionForEdit(sectionId) {
        return this.get(`/sections/${sectionId}/edit`)
    }

    async getSectionDetails(sectionId) {
        return this.get(`/sections/${sectionId}`)
    }

    // Eligibility Setup methods
    async getEligibilitySetup() {
        return this.get('/eligibilities')
    }

    async getEligibilityFormData() {
        return this.get('/eligibilities/create')
    }

    async saveEligibilitySetup(eligibilityData) {
        return this.post('/eligibilities', eligibilityData)
    }

    async updateEligibilitySetup(eligibilityId, eligibilityData) {
        return this.patch(`/eligibilities/${eligibilityId}`, eligibilityData)
    }

    async deleteEligibility(eligibilityId) {
        return this.delete(`/eligibilities/${eligibilityId}`)
    }

    async getEligibilityForEdit(eligibilityId) {
        return this.get(`/eligibilities/${eligibilityId}/edit`)
    }

    async getEligibilityDetails(eligibilityId) {
        return this.get(`/eligibilities/${eligibilityId}`)
    }

    // Interview Setup (Interview Levels) methods
    async getInterviewLevels() {
        return this.get('/interview-levels')
    }

    async saveInterviewLevel(levelData) {
        return this.post('/interview-levels', levelData)
    }

    async updateInterviewLevel(levelId, levelData) {
        return this.patch(`/interview-levels/${levelId}`, levelData)
    }

    async deleteInterviewLevel(levelId) {
        return this.delete(`/interview-levels/${levelId}`)
    }

    async getInterviewLevelForEdit(levelId) {
        return this.get(`/interview-levels/${levelId}/edit`)
    }

    // Employment Type Setup methods
    async getEmploymentTypeSetup() {
        return this.get('/employment-types')
    }

    async getEmploymentTypeFormData() {
        return this.get('/employment-types/create')
    }

    async saveEmploymentTypeSetup(employmentTypeData) {
        return this.post('/employment-types', employmentTypeData)
    }

    async updateEmploymentTypeSetup(employmentTypeId, employmentTypeData) {
        return this.patch(`/employment-types/${employmentTypeId}`, employmentTypeData)
    }

    async deleteEmploymentType(employmentTypeId) {
        return this.delete(`/employment-types/${employmentTypeId}`)
    }

    async getEmploymentTypeForEdit(employmentTypeId) {
        return this.get(`/employment-types/${employmentTypeId}/edit`)
    }

    async getEmploymentTypeDetails(employmentTypeId) {
        return this.get(`/employment-types/${employmentTypeId}`)
    }

    // Specialization Setup methods
    async getSpecializationSetup() {
        return this.get('/learnings')
    }

    async getSpecializationFormData() {
        return this.get('/learnings/create')
    }

    async saveSpecializationSetup(specializationData) {
        return this.post('/learnings', specializationData)
    }

    async updateSpecializationSetup(specializationId, specializationData) {
        return this.patch(`/learnings/${specializationId}`, specializationData)
    }

    async deleteSpecialization(specializationId) {
        return this.delete(`/learnings/${specializationId}`)
    }

    async getSpecializationForEdit(specializationId) {
        return this.get(`/learnings/${specializationId}/edit`)
    }

    async getSpecializationDetails(specializationId) {
        return this.get(`/learnings/${specializationId}`)
    }

    // Downloadable Docs (HR) methods
    async getDownloadableDocs() {
        return this.get('/downloadable-forms')
    }

    async saveDownloadableDoc(data) {
        // data can be FormData (for upload)
        return this.post('/downloadable-forms', data)
    }

    async updateDownloadableDoc(id, data) {
        // Use POST with method override when sending FormData
        if (data instanceof FormData) {
            data.append('_method', 'PATCH')
            return this.post(`/downloadable-forms/${id}`, data)
        }
        return this.patch(`/downloadable-forms/${id}`, data)
    }

    async deleteDownloadableDoc(id) {
        return this.delete(`/downloadable-forms/${id}`)
    }

    // Position Setup methods
    async getPositionSetup() {
        return this.get('/positions')
    }

    async getPositionFormData() {
        return this.get('/positions/create')
    }

    async savePositionSetup(positionData) {
        return this.post('/positions', positionData)
    }

    async updatePositionSetup(positionId, positionData) {
        return this.patch(`/positions/${positionId}`, positionData)
    }

    async deletePosition(positionId) {
        return this.delete(`/positions/${positionId}`)
    }

    async getPositionForEdit(positionId) {
        return this.get(`/positions/${positionId}/edit`)
    }

    async getPositionDetails(positionId) {
        return this.get(`/positions/${positionId}`)
    }

    // Plantilla Setup methods
    async getPlantillaSetup() {
        return this.get('/plantillas')
    }

    async getRecentPlantillas(limit = 5) {
        return this.get('/plantillas/recent', { params: { limit } })
    }

    async getPlantillaFormData() {
        return this.get('/plantillas/create')
    }

    async savePlantillaSetup(plantillaData) {
        return this.post('/plantillas', plantillaData)
    }

    async updatePlantillaSetup(plantillaId, plantillaData) {
        return this.patch(`/plantillas/${plantillaId}`, plantillaData)
    }

    async deletePlantilla(plantillaId) {
        return this.delete(`/plantillas/${plantillaId}`)
    }

    async getPlantillaForEdit(plantillaId) {
        return this.get(`/plantillas/${plantillaId}/edit`)
    }

    async getPlantillaDetails(plantillaId) {
        return this.get(`/plantillas/${plantillaId}`)
    }

    async checkPlantillaCode(code, excludeId = null) {
        const data = { code }
        if (excludeId) {
            data.exclude_id = excludeId
        }
        return this.post('/plantillas/check-code', data)
    }

    // Promotion Types Setup methods
    async getPromotionTypes() {
        return this.get('/promotion-types')
    }
    async savePromotionTypes(payload) {
        return this.post('/promotion-types', payload)
    }
    async getPromotionTypeForDelete(id) {
        return this.get(`/promotion-types/${id}/delete`)
    }
    async deletePromotionType(id) {
        return this.delete(`/promotion-types/${id}`)
    }
    async getPromotionType(id) {
        return this.get(`/promotion-types/${id}`)
    }
    async updatePromotionType(id, payload) {
        return this.patch(`/promotion-types/${id}`, payload)
    }

    // Off-Boarding Types
    async getOffboardingTypes() {
        return this.get('/offboarding-types')
    }
    async saveOffboardingTypes(payload) {
        return this.post('/offboarding-types', payload)
    }
    async getOffboardingTypeForDelete(id) {
        return this.get(`/offboarding-types/${id}/delete`)
    }
    async deleteOffboardingType(id) {
        return this.delete(`/offboarding-types/${id}`)
    }

    // Non-Plantilla Setup methods
    async getNonPlantillaSetup() {
        return this.get('/non-plantillas')
    }
    async getNonPlantillaFormData(id = 0) {
        // backend expects /non-plantillas/{id}/add for add form
        return this.get(`/non-plantillas/${id}/add`)
    }
    async saveNonPlantillaSetup(payload, id = 0) {
        // backend uses POST /non-plantillas/{id}
        return this.post(`/non-plantillas/${id}`, payload)
    }
    async deleteNonPlantilla(id) {
        return this.delete(`/non-plantillas/${id}`)
    }
    async getNonPlantillaForEdit(id) {
        return this.get(`/non-plantillas/${id}`)
    }

    // Semester Rating methods
    async getSemesterRatings() {
        return this.get('/semester-ratings')
    }
    async getSemesterRatingFormData() {
        return this.get('/semester-ratings/add')
    }
    async saveSemesterRating(payload) {
        return this.post('/semester-ratings', payload)
    }
    async updateSemesterRating(id, payload) {
        return this.patch(`/semester-ratings/${id}`, payload)
    }
    async deleteSemesterRating(id) {
        return this.delete(`/semester-ratings/${id}`)
    }
    async getSemesterRatingForEdit(id) {
        return this.get(`/semester-ratings/${id}/edit`)
    }

    // Competencies methods
    async getCompetencies() {
        return this.get('/competencies')
    }
    async getCompetencyFormData() {
        return this.get('/competencies/create')
    }
    async saveCompetency(payload) {
        return this.post('/competencies', payload)
    }
    async updateCompetency(id, payload) {
        return this.patch(`/competencies/${id}`, payload)
    }
    async deleteCompetency(typeId, id) {
        return this.delete(`/competencies/${typeId}/${id}`)
    }
    async getCompetencyForEdit(id) {
        return this.get(`/competencies/${id}/edit`)
    }
    async getCompetencyForDelete(typeId, id) {
        return this.get(`/competencies/${typeId}/${id}/delete`)
    }

    // EETE Rating methods
    async getEETERatings() {
        return this.get('/eete-rating-setup')
    }
    async getEETEFormData() {
        return this.get('/eete-rating-setup/create')
    }
    async saveEETERating(payload) {
        return this.post(`/eete-rating`, payload)
    }
    async updateEETERating(id, payload) {
        return this.put(`/eete-rating/${id}`, payload)
    }
    async deleteEETERating(id) {
        return this.delete(`/eete-rating/${id}`)
    }
    async getEETEForEdit(id) {
        return this.get(`/eete-rating/${id}/edit`)
    }

    // Exam Category methods
    async getExamCategories() {
        return this.get('/exam-category-setup')
    }
    async getExamCategoryFormData(id) {
        return this.get(`/exam-category-setup/${id}/add`)
    }
    async saveExamCategory(id, payload) {
        return this.post(`/exam-category-setup/${id}`, payload)
    }
    async deleteExamCategory(id) {
        return this.delete(`/exam-category-setup/${id}`)
    }
    async deleteSubCategory(id) {
        return this.get(`/exam-sub-category/${id}/delete`)
    }
    async getSubCategoryPositions(id) {
        return this.get(`/exam-sub-categories/${id}/positions`)
    }
    async addPositions(id, payload) {
        return this.post(`/exam-sub-categories/${id}/positions`, payload)
    }
    async getSubCategoryQuestions(id) {
        return this.get(`/exam-sub-categories/${id}/questions`)
    }
    async addQuestions(id, questionId, payload) {
        return this.post(`/exam-sub-categories/${id}/questions/${questionId}`, payload)
    }
    async deleteQuestion(id) {
        return this.get(`/exam-sub-categories/${id}/questions/delete`)
    }
    async deleteChoice(id) {
        return this.get(`/exam-sub-categories/${id}/choice/delete`)
    }
    async getQuestionForEdit(id) {
        return this.get(`/exam-sub-categories/${id}/choice/get-delete`)
    }

    // Time Keeping methods
    async getOvertimeTypes() {
        // Backend exposes OvertimeTypeController@index on /overtime-types
        return this.get('/overtime-types')
    }

    async saveOvertimeTypes(payload) {
        // OvertimeTypeController@store expects POST to /overtime-types
        return this.post('/overtime-types', payload)
    }

    async deleteOvertimeType(id) {
        // OvertimeTypeController@destroy expects DELETE /overtime-types/{id}
        return this.delete(`/overtime-types/${id}`)
    }

    async getHolidayTypes() {
        return this.get('/holiday-types')
    }

    async saveHolidayTypes(payload) {
        return this.post('/holiday-types', payload)
    }

    async deleteHolidayType(id) {
        return this.delete(`/holiday-types/${id}`)
    }

    // Payroll methods
    async getPayrollPeriods() {
        return this.get('/payroll-setup/payroll-periods')
    }

    async getPayrollItems() {
        return this.get('/payroll-setup/payroll-items')
    }

    // Generic CRUD methods for any endpoint
    async fetchData(endpoint) {
        return this.get(endpoint)
    }

    async createData(endpoint, data) {
        return this.post(endpoint, data)
    }

    async updateData(endpoint, data) {
        return this.put(endpoint, data)
    }

    async removeData(endpoint) {
        return this.delete(endpoint)
    }

    // Holidays
    async getHolidays() {
        return this.get('/holidays')
    }

    async saveHolidays(payload) {
        return this.post('/holidays', payload)
    }

    async deleteHoliday(id) {
        return this.delete(`/holidays/${id}`)
    }

    // Time Keeping Setup
    async getTimekeepingEmploymentTypes() {
        // GET list of active employment types
        return this.get('/time-keeping-setups')
    }
    async saveTimekeepingSetup(payload) {
        // POST update
        return this.post('/time-keeping-setups/update', payload)
    }
    async getTimekeepingSetupFor(employmentTypeId) {
        // GET specific data for employment type
        return this.get(`/time-keeping-setups/${employmentTypeId}/data`)
    }

    // Leave Approvers
    async getLeaveApprovers() {
        return this.get('/leave-approvers')
    }
    async getLeaveApproverForm(id) {
        return this.get(`/leave-approvers/${id}/add`)
    }
    async saveLeaveApprover(id, payload) {
        return this.post(`/leave-approvers/${id}`, payload)
    }
    async deleteLeaveApprover(id) {
        return this.delete(`/leave-approvers/${id}`)
    }
    async getApproverDepartments(branchId) {
        return this.get(`/leave-approvers/${branchId}/departments`)
    }
    async getApproverDivisions(deptId) {
        return this.get(`/leave-approvers/${deptId}/divisions`)
    }
    async getApproverSections(divId) {
        return this.get(`/leave-approvers/${divId}/sections`)
    }
    async getApproverSubordinates(departmentId, id, typeId, leaveTypeId = null) {
        const params = {}
        if (leaveTypeId != null && leaveTypeId !== '' && Number(leaveTypeId) > 0) {
            params.leave_type_id = leaveTypeId
        }
        return this.get(`/leave-approvers/${departmentId}/${id}/${typeId}/subordinates`, { params })
    }
    async addApproverSubordinates(id, typeId, payload) {
        return this.post(`/leave-approvers/${id}/${typeId}/subordinates`, payload)
    }
    async deleteApproverSubordinate(employeeId) {
        return this.post(`/leave-approvers/${employeeId}/subordinates`)
    }

    // Salary Schedules
    async getSalarySchedules() {
        return this.get('/salary-schedules')
    }
    async getSalaryScheduleForm() {
        return this.get('/salary-schedules/create')
    }
    async saveSalarySchedule(payload) {
        return this.post('/salary-schedules', payload)
    }
    async getSalaryScheduleForEdit(id) {
        return this.get(`/salary-schedules/${id}/edit`)
    }
    async updateSalarySchedule(id, payload) {
        return this.patch(`/salary-schedules/${id}`, payload)
    }
    async getSalaryScheduleForDelete(id) {
        return this.get(`/salary-schedules/${id}/delete`)
    }
    async deleteSalaryScheduleDetail(id) {
        return this.delete(`/salary-schedules/${id}`)
    }
    async getSalaryScheduleDetails(id) {
        return this.get(`/salary-schedules/${id}`)
    }

    // Tax Tables
    async getTaxTables(query = '', type = 'monthly') {
        const separator = query.includes('?') ? '&' : '?'
        const suffix = query ? `${query}${separator}type=${type}` : `?type=${type}`
        return this.get(`/tax-tables${suffix}`)
    }
    async saveTaxTables(payload, type = 'monthly') {
        return this.post('/tax-tables', { ...payload, type })
    }
    async getTaxTableForDelete(id, type = 'monthly') {
        return this.get(`/tax-tables/${id}/delete?type=${type}`)
    }
    async deleteTaxTable(id, type = 'monthly') {
        return this.delete(`/tax-tables/${id}?type=${type}`)
    }
    async getTaxTableDetails(id, type = 'monthly') {
        return this.get(`/tax-tables/${id}?type=${type}`)
    }

    // Overtime Tax Tables
    async getOvertimeTaxTables(year = null) {
        const endpoint = year ? `/overtime-tax-table/${year}/load` : '/overtime-tax-table'
        return this.get(endpoint)
    }
    async saveOvertimeTaxTables(payload) {
        return this.post('/overtime-tax-table', payload)
    }
    async deleteOvertimeTaxTable(id) {
        return this.get(`/overtime-tax-table/${id}/delete`)
    }
    async loadOvertimeTaxForYear(year) {
        return this.get(`/overtime-tax-table/${year}/load`)
    }

    // Mid Year Bonus Tables
    async getMidYearBonusTables() {
        return this.get('/midyear-tables')
    }
    async saveMidYearBonusTables(payload) {
        return this.post('/midyear-tables', payload)
    }
    async deleteMidYearBonusTable(id) {
        return this.delete(`/midyear-tables/${id}`)
    }
    async getMidYearBonusForDelete(id) {
        return this.get(`/midyear-tables/${id}/delete`)
    }

    // Year End Bonus Tables
    async getYearEndBonusTables() {
        return this.get('/yearend-tables')
    }
    async saveYearEndBonusTables(payload) {
        return this.post('/yearend-tables', payload)
    }
    async deleteYearEndBonusTable(id) {
        return this.delete(`/yearend-tables/${id}`)
    }
    async getYearEndBonusForDelete(id) {
        return this.get(`/yearend-tables/${id}/delete`)
    }

    // Cash Gift Tables
    async getCashGiftTables() {
        return this.get('/cashgift-tables')
    }
    async saveCashGiftTables(payload) {
        return this.post('/cashgift-tables', payload)
    }
    async deleteCashGiftTable(id) {
        return this.delete(`/cashgift-tables/${id}`)
    }
    async getCashGiftForDelete(id) {
        return this.get(`/cashgift-tables/${id}/delete`)
    }

    // Monetization Setup
    async getMonetizationSetups() {
        return this.get('/monetization-setups')
    }
    async saveMonetizationSetup(id, payload) {
        return this.post(`/monetization-setups/${id}`, payload)
    }

    // Applicant Documents
    async getApplicantDocuments() {
        return this.get('/applicant-documents')
    }
    async saveApplicantDocument(payload) {
        return this.post('/applicant-documents', payload)
    }
    async updateApplicantDocument(id, payload) {
        return this.patch(`/applicant-documents/${id}`, payload)
    }
    async deleteApplicantDocument(id) {
        return this.delete(`/applicant-documents/${id}`)
    }

    // Audit/User Activity
    async getAuditRecords(params = {}) {
        return this.get('/audits', { params })
    }
    async getAuditRecordsOptimized(params = {}) {
        return this.get('/audits/optimize', { params })
    }
    async getUserActivities(userId, params = {}) {
        return this.get(`/audits/user/${userId}`, { params })
    }

    // HDMF (Pag-IBIG) Table
    async getHdmfTables() {
        return this.get('/pagibig-setup')
    }
    async saveHdmfTables(payload) {
        return this.post('/pagibig-setup', payload)
    }
    async getHdmfForDelete(id) {
        return this.get(`/pagibig-setup/${id}/delete`)
    }
    async deleteHdmf(id) {
        return this.delete(`/pagibig-setup/${id}`)
    }

    // PhilHealth Table
    async getPhilhealthTables() {
        return this.get('/philhealth-tables')
    }
    async savePhilhealthTables(payload) {
        return this.post('/philhealth-tables', payload)
    }
    async getPhilhealthForDelete(id) {
        return this.get(`/philhealth-tables/${id}/delete`)
    }
    async deletePhilhealth(id) {
        return this.delete(`/philhealth-tables/${id}`)
    }

    // Salary Steps
    async getSalarySteps() {
        return this.get('/salary-steps')
    }
    async saveSalarySteps(payload) {
        return this.post('/salary-steps', payload)
    }
    async getSalaryStepForDelete(id) {
        return this.get(`/salary-steps/${id}/delete`)
    }
    async deleteSalaryStep(id) {
        return this.delete(`/salary-steps/${id}`)
    }

    // Salary Grades
    async getSalaryGrades() {
        return this.get('/salary-grades')
    }
    async saveSalaryGrades(payload) {
        return this.post('/salary-grades', payload)
    }
    async getSalaryGradeForDelete(id) {
        return this.get(`/salary-grades/${id}/delete`)
    }
    async deleteSalaryGrade(id) {
        return this.delete(`/salary-grades/${id}`)
    }

    // Income Setup
    async getIncomes() {
        return this.get('/income-types')
    }
    async saveIncomes(payload) {
        return this.post('/income-types', payload)
    }
    async deleteIncome(id) {
        return this.delete(`/income-types/${id}`)
    }

    // Deduction Setup
    async getDeductions() {
        return this.get('/deduction-types')
    }
    async saveDeductions(payload) {
        return this.post('/deduction-types', payload)
    }
    async deleteDeduction(id) {
        return this.delete(`/deduction-types/${id}`)
    }

    // Leave Types
    async getLeaveTypes() {
        return this.get('/leave-types')
    }

    async saveLeaveTypes(payload) {
        return this.post('/leave-types', payload)
    }

    async deleteLeaveType(id) {
        return this.delete(`/leave-types/${id}`)
    }

    // Official Business Types
    async getOfficialBusinessTypes() {
        return this.get('/official-business-types')
    }

    async saveOfficialBusinessTypes(payload) {
        return this.post('/official-business-types', payload)
    }

    async deleteOfficialBusinessType(id) {
        return this.delete(`/official-business-types/${id}`)
    }

    // Deduction Priority Setup
    async getDeductionPriorities() {
        return this.get('/deduction-priorities')
    }
    async saveDeductionPriorities(payload) {
        return this.post('/deduction-priorities', payload)
    }

    // Payroll Interval Setup
    async getPayrollIntervals() {
        return this.get('/payroll-intervals')
    }
    async savePayrollIntervals(payload) {
        return this.post('/payroll-intervals', payload)
    }
    async deletePayrollInterval(id) {
        return this.delete(`/payroll-intervals/${id}`)
    }
    async updatePayrollIntervalActive(id, payload) {
        return this.patch(`/payroll-intervals/${id}/active`, payload)
    }

    // Payroll Cutoff Setup
    async getPayrollCutoffs() {
        return this.get('/payroll-cutoffs')
    }
    async getPayrollCutoffForm(id = 0) {
        return this.get(`/payroll-cutoffs/${id}/add`)
    }
    async savePayrollCutoff(id = 0, payload) {
        return this.post(`/payroll-cutoffs/${id}`, payload)
    }

    // Loyalty Award Setup
    async getLoyaltyAwards() {
        return this.get('/loyalty-award-setup')
    }
    async saveLoyaltyAwards(payload) {
        return this.post('/loyalty-award-setup', payload)
    }
    async getLoyaltyAwardForDelete(id) {
        return this.get(`/loyalty-award-setup/${id}/delete`)
    }
    async deleteLoyaltyAward(id) {
        return this.delete(`/loyalty-award-setup/${id}`)
    }

    // Uniform & Clothing Allowance Setup
    async getUniformClothing() {
        return this.get('/uniform-clothing-setup')
    }
    async saveUniformClothing(payload) {
        return this.post('/uniform-clothing-setup', payload)
    }
    async getUniformClothingForDelete(id) {
        return this.get(`/uniform-clothing-setup/${id}/delete`)
    }
    async deleteUniformClothing(id) {
        return this.delete(`/uniform-clothing-setup/${id}`)
    }

    // RATA Positions Setup
    async getRataPositions() {
        return this.get('/rata-positions')
    }
    async saveRataPositions(payload) {
        return this.post('/rata-positions', payload)
    }

    // RATA Table Setup
    async getRataTable() {
        return this.get('/rata-table')
    }
    async saveRataTable(payload) {
        return this.post('/rata-table', payload)
    }
    async getRataTableForDelete(id) {
        return this.get(`/rata-table/${id}/delete`)
    }
    async deleteRataTable(id) {
        // backend uses GET for delete
        return this.get(`/rata-table/${id}/delete`)
    }

    // Hazard Pay Setup
    async getHazardTable() {
        return this.get('/hazard-pay')
    }
    async saveHazardTable(payload) {
        return this.post('/hazard-pay', payload)
    }
    async getHazardForDelete(id) {
        return this.get(`/hazard-pay/${id}/delete`)
    }
    async deleteHazard(id) {
        return this.delete(`/hazard-pay/${id}`)
    }
    async getHazardList() {
        return this.get('/hazard-pay/list')
    }
    async getHazardForm(id = 0) {
        return this.get(`/hazard-pay/${id}/add`)
    }
    async saveHazardHeader(id = 0, payload) {
        return this.post(`/hazard-pay/${id}`, payload)
    }
    async saveHazardEmployees(id, payload) {
        return this.post(`/hazard-pay/${id}/employees`, payload)
    }
    async deleteHazardEmployee(id) {
        return this.get(`/hazard-pay/${id}/employees/delete`)
    }
    async processHazard(id, typeId) {
        return this.post(`/hazard-pay/${id}/${typeId}/process`)
    }
}

// Create and export a singleton instance
const apiService = new ApiService()

// Export both the class and instance
export default apiService
export { ApiService }

// Convenience exports for common operations
export const {
    get,
    post,
    put,
    patch,
    delete: deleteRequest,
    getUsers,
    addUsers,
    getAvailableEmployees,
    login,
    devLogin,
    logout,
    getProfile,
    getAccessRights,
    updateAccessRights,
    fetchData,
    createData,
    updateData,
    removeData
} = apiService
