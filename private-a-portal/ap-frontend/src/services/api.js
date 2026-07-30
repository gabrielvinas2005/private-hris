// API Service - Centralized endpoint management
import axios from "axios";

// Base configuration - using proxy
const API_BASE = import.meta.env.VITE_API_URL || "/api";

// Create axios instance with interceptors
const apiClient = axios.create({
  baseURL: API_BASE,
  timeout: 10000,
  withCredentials: true,
  headers: {
    "Content-Type": "application/json",
    "X-Requested-With": "XMLHttpRequest",
  },
});

// Request interceptor
apiClient.interceptors.request.use((config) => {
  const tokenData = localStorage.getItem("auth_token");
  if (tokenData) {
    try {
      // Try to parse as JSON (new format)
      const parsed = JSON.parse(tokenData);
      if (parsed.token) {
        // Check if token is expired
        if (parsed.expiresAt && Date.now() > parsed.expiresAt) {
          localStorage.removeItem("auth_token");
          localStorage.removeItem("user_data");
          return config;
        }
        config.headers.Authorization = `Bearer ${parsed.token}`;
      }
    } catch (error) {
      // If parsing fails, treat as old format (plain string token)
      config.headers.Authorization = `Bearer ${tokenData}`;
    }
  }
  return config;
});

// Response interceptor
apiClient.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      // Don't redirect on login endpoint - let the login component handle the error
      // Check if this is a login request (wrong email/password should not cause redirect)
      const requestUrl = error.config?.url || '';
      const isLoginEndpoint = requestUrl.includes('/login') || 
                              requestUrl.endsWith('/login');
      
      if (!isLoginEndpoint) {
        localStorage.removeItem("auth_token");
        localStorage.removeItem("user_data");
        window.location.href = "/login";
      }
    }
    return Promise.reject(error);
  },
);

// API endpoints (obscured names)
const endpoints = {
  // User registration
  submitApplication: "/applicant-registration",

  // // Positions
  // getPositions: '/vacancies',
  // getPositionInfo: '/position-info',

  // Authentication
  login: "/login",
  logout: "/logout",
  profile: "/profile",
  pdsData: "/pds/{employee_no}",
  pdsUpdate: "/pds-update/{employee_no}",
  refresh: "/refresh",

  // Vacancies/Plantillas
  vacancies: "/vacancies",
  positionInfo: "/position-info/{id}/{type_id}",

  // PDS Management
  pdStore: "/applicant-pds-store",

  // Dropdown Data
  civilStatus: "/dropdown-data/civil-status",
  bloodTypes: "/dropdown-data/blood-types",
  namePrefixes: "/dropdown-data/name-prefixes",
  nameSuffixes: "/dropdown-data/name-suffixes",
  citizenships: "/dropdown-data/citizenships",
  religions: "/dropdown-data/religions",
  documentTypes: "/dropdown-data/document-types",

  // Address Data
  //     regions: '/dropdown-data/regions',
  //     provinces: '/dropdown-data/provinces',
  //     municipalities: '/dropdown-data/municipalities',
  //     barangays: '/dropdown-data/barangays'
};

// API service methods
export const ApiService = {
  // Authentication
  async login(credentials) {
    return apiClient.post(endpoints.login, credentials, {
      timeout: 60000,
    });
  },

  async logout() {
    return apiClient.post(endpoints.logout);
  },

  async getProfile() {
    return apiClient.get(endpoints.profile);
  },

  async getPDSDataApi(employee_no) {
    return apiClient.get(
      endpoints.pdsData.replace("{employee_no}", employee_no),
    );
  },
  async updatePDSDataApi(employee_no, formData) {
    return apiClient.post(
      endpoints.pdsUpdate.replace("{employee_no}", employee_no),
      formData,
    );
  },

  async refreshToken() {
    return apiClient.post(endpoints.refresh);
  },

  // OTP Methods
  async verifyOTP(otpCode) {
    return apiClient.post("/verify-otp", { otp: otpCode });
  },

  async resendOTP() {
    return apiClient.post("/resend-otp");
  },

  // Password Management
  async changePassword(passwordData) {
    return apiClient.post("/change-password", passwordData);
  },

  async requestPasswordReset(email) {
    return apiClient.post(
      "/password/forgot",
      { email },
      {
        timeout: 60000,
      },
    );
  },

  async resetPassword(payload) {
    return apiClient.post("/password/reset", payload);
  },

  // Registration
  async submitRegistration(formData) {
    return apiClient.post(endpoints.submitApplication, formData, {
      headers: {
        "Content-Type": "multipart/form-data",
      },
      timeout: 0, // No timeout - registration may take longer due to email sending
    });
  },

  // Dashboard & Profile
  // async fetchApplicantPage() {
  //     return apiClient.get(endpoints.applicantPage)
  // },

  // // Positions & Vacancies
  // async getVacancies() {
  //     return apiClient.get(endpoints.getPositions)
  // },

  // async getPositionDetails(id, typeId) {
  //     return apiClient.get(`${endpoints.getPositionInfo}/${id}/${typeId}`)
  // },

  // // Applications
  // async getApplicationForm(id, plantillaId) {
  //     return apiClient.get(`${endpoints.applicantAdd}/${id}/${plantillaId}`)
  // },

  // async submitApplication(id, plantillaId, formData) {
  //     return apiClient.post(`${endpoints.applicantAdd}/${id}/${plantillaId}`, formData, {
  //         headers: {
  //             'Content-Type': 'multipart/form-data'
  //         }
  //     })
  // },

  // PDS (Personal Data Sheet) Management
  async storePDS(id, formData) {
    return apiClient.post(`${endpoints.pdStore}/${id}`, formData, {
      headers: {
        "Content-Type": "multipart/form-data",
      },
      timeout: 60000,
    });
  },

  // PDS Questionnaire
  async getPDSQuestions(id) {
    return apiClient.get(`/applicant-pds-questions/${id}`, {
      timeout: 30000,
    });
  },

  async storePDSQuestionnaire(id, formData) {
    return apiClient.post(`/applicant-pds-questionnaire/${id}`, formData, {
      timeout: 30000,
    });
  },

  async deletePDS(typeId, id) {
    return apiClient.get(`${endpoints.pdsDelete}/${typeId}/${id}`);
  },

  async destroyPDS(typeId, id) {
    return apiClient.delete(`${endpoints.pdsDestroy}/${typeId}/${id}`);
  },

  // Document Management
  async downloadDocument(id) {
    return apiClient.get(`${endpoints.download}/${id}`, {
      responseType: "blob",
    });
  },

  // Examination System
  async getExamIntro(id) {
    return apiClient.get(`${endpoints.examIntro}/${id}`);
  },

  async getExamPage(id) {
    return apiClient.get(`${endpoints.examPage}/${id}`);
  },

  async autoSaveExam(applicantExaminationId, answers) {
    return apiClient.post(
      `${endpoints.examAutoSave}/${applicantExaminationId}`,
      answers,
    );
  },

  async submitExam(applicantExaminationId, answers) {
    return apiClient.post(
      `${endpoints.examSubmit}/${applicantExaminationId}`,
      answers,
    );
  },

  async getExamResult(applicantExaminationId) {
    return apiClient.get(`${endpoints.examResult}/${applicantExaminationId}`);
  },

  // Test endpoints
  // async testEmail() {
  //     return apiClient.get(endpoints.testEmail)
  // },

  // PDS Management
  // async savePDSSection(pdsData) {
  //     return apiClient.post(endpoints.pdStore, pdsData)
  // },

  // async getPDSData() {
  //     return apiClient.get('/api/applicant-pds')
  // },

  async getCivilStatus() {
    return apiClient.get(endpoints.civilStatus);
  },

  async getGenders() {
    return apiClient.get("/dropdown-data/genders");
  },

  async getBloodTypes() {
    return apiClient.get(endpoints.bloodTypes);
  },

  async getNamePrefixes() {
    return apiClient.get(endpoints.namePrefixes);
  },

  async getNameSuffixes() {
    return apiClient.get(endpoints.nameSuffixes);
  },

  async getCitizenships() {
    return apiClient.get(endpoints.citizenships);
  },

  async getReligions() {
    return apiClient.get(endpoints.religions);
  },

  async getDocumentTypes() {
    return apiClient.get(endpoints.documentTypes);
  },

  // Vacancies/Plantillas
  async getVacancies() {
    return apiClient.get(endpoints.vacancies);
  },

  async getPositionInfo(id, typeId) {
    return apiClient.get(
      endpoints.positionInfo.replace("{id}", id).replace("{type_id}", typeId),
    );
  },

  // Applicant Portal Methods
  async getApplicantPage() {
    return apiClient.get("/applicant-page", {
      timeout: 60000,
    });
  },

  async markNotificationsRead(type) {
    return apiClient.post("/applicant-notifications/read", { type });
  },

  async getApplicantProgress() {
    return apiClient.get("/applicant-progress", {
      timeout: 30000,
    });
  },

  async applyForPosition(applicantId, positionId, isPlantilla) {
    return apiClient.get(
      `/applicant-apply/${applicantId}/${positionId}/${isPlantilla}`,
    );
  },

  async checkPlantillaRequirements(plantillaId) {
    return apiClient.get(`/plantilla-requirement-check/${plantillaId}`);
  },

  async withdrawApplication(applicationId) {
    return apiClient.delete(`/applicant-application/${applicationId}`);
  },

  async deletePDS(typeId, id) {
    return apiClient.get(`/applicant-delete/${typeId}/${id}`);
  },

  async destroyPDS(typeId, id) {
    return apiClient.delete(`/applicant-destroy/${typeId}/${id}`);
  },

  async downloadDocument(id) {
    return apiClient.get(`/applicant-download/${id}`, {
      responseType: "blob",
    });
  },

  // Examination Methods
  async getExamIntro(id) {
    return apiClient.get(`/exam-intro/${id}`);
  },

  async getExamPage(id) {
    return apiClient.get(`/exam-page/${id}`);
  },

  async autoSaveExam(applicantExaminationId, answers) {
    return apiClient.post(`/exam-auto-save/${applicantExaminationId}`, answers);
  },

  async submitExam(applicantExaminationId, answers) {
    return apiClient.post(`/exam-submit/${applicantExaminationId}`, answers);
  },

  async submitExternalExam(applicantExaminationId) {
    return apiClient.post(`/exam-external-submit/${applicantExaminationId}`);
  },

  async getExamResult(applicantExaminationId) {
    return apiClient.get(`/exam-result/${applicantExaminationId}`);
  },

  // PDS (Personal Data Sheet) Methods
  async getPDSData(employeeNo) {
    return apiClient.get(`/pds/${employeeNo}`);
  },

  async updatePDSData(employeeNo, formData) {
    return apiClient.post(`/pds-update/${employeeNo}`, formData, {
      headers: {
        "Content-Type": "multipart/form-data",
      },
    });
  },

  // PDS Section Management
  async savePDSSection(sectionType, data) {
    return apiClient.post(`/pds-section/${sectionType}`, data);
  },

  async getPDSSection(sectionType, id) {
    return apiClient.get(`/pds-section/${sectionType}/${id}`);
  },

  async deletePDSSection(sectionType, id) {
    return apiClient.delete(`/pds-section/${sectionType}/${id}`);
  },

  // Applications
  // Download application package (resume/PDS doc) by application id (applicant_details.id).
  async downloadApplication(id) {
    return apiClient.get(`/applicant-application-download/${id}`, {
      responseType: "blob",
    });
  },

  // Work Experience Report Methods
  async generateWorkExperiencePreview(workExperienceId) {
    return apiClient.post(
      "/work-experience/preview",
      { work_experience_id: workExperienceId },
      { responseType: "blob" },
    );
  },

  async downloadWorkExperiencePDF(workExperienceId) {
    return apiClient.post(
      "/work-experience/download-pdf",
      { work_experience_id: workExperienceId },
      { responseType: "blob" },
    );
  },

  async downloadWorkExperienceWord(workExperienceId) {
    return apiClient.post(
      "/work-experience/download-word",
      { work_experience_id: workExperienceId },
      { responseType: "blob" },
    );
  },

  async downloadWorkExperienceDocx(workExperienceId) {
    return apiClient.post(
      "/work-experience/download-docx",
      { work_experience_id: workExperienceId },
      { responseType: "blob" },
    );
  },

  // Job Offer Methods
  async acceptJobOffer(applicationId) {
    return apiClient.post(`/applicant/job-offer/${applicationId}/accept`);
  },

  async rejectJobOffer(applicationId) {
    return apiClient.post(`/applicant/job-offer/${applicationId}/reject`);
  },
};

export default ApiService;

export const authApi = {
  async login(credentials) {
    return ApiService.login(credentials);
  },

  async logout() {
    return ApiService.logout();
  },

  async getCurrentUser() {
    return ApiService.getProfile();
  },
};
