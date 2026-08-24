// API Configuration
export const API_CONFIG = {
    // Base URL for API requests
    BASE_URL: import.meta.env.VITE_API_URL || 'http://localhost:8006/api',

    // Default timeout for requests (in milliseconds)
    TIMEOUT: 30000,

    // Retry configuration
    RETRY_ATTEMPTS: 3,
    RETRY_DELAY: 1000,

    // Endpoints
    ENDPOINTS: {
        // Authentication
        LOGIN: '/login',
        LOGOUT: '/logout',
        PROFILE: '/profile',
        REFRESH: '/refresh',

        // Users
        USERS: '/users',
        USER_BY_ID: (id) => `/users/${id}`,
        USER_TOGGLE_LOCK: (id) => `/users/${id}/toggle-lock`,
        USER_RESET_PASSWORD: (id) => `/users/${id}/reset-password`,

        // HR Setup
        HR_COMPANY: '/companies',
        HR_BRANCH: '/branches',
        HR_OFFICE: '/departments',
        HR_DIVISION: '/divisions',
        HR_SECTION: '/sections',
        HR_EMPLOYMENT_TYPE: '/employment-types',
        HR_SPECIALIZATION: '/learnings',
        HR_POSITION: '/positions',
        HR_PROMOTION_TYPES: '/hr-setup/promotion-types',
        HR_OFF_BOARDING_TYPES: '/hr-setup/off-boarding-types',
        HR_DOCUMENT_NO: '/hr-setup/document-no',
        HR_DOCUMENT_TYPE: '/hr-setup/document-type',
        HR_COMPETENCIES: '/competencies',

        // Time Keeping
        TK_OVERTIME_TYPES: '/timekeeping-setup/overtime-types',
        TK_HOLIDAY_TYPES: '/timekeeping-setup/holiday-types',
        TK_HOLIDAYS: '/timekeeping-setup/holidays',
        TK_LEAVE_TYPES: '/timekeeping-setup/leave-types',
        TK_OFFICIAL_BUSINESS_TYPES: '/timekeeping-setup/official-business-types',
        TK_TIME_KEEPING: '/timekeeping-setup/time-keeping',
        TK_BIOMETRIC: '/timekeeping-setup/biometric',
        TK_APPROVERS: '/timekeeping-setup/approvers',

        // Payroll
        PAYROLL_PERIODS: '/payroll-setup/payroll-periods',
        PAYROLL_ITEMS: '/payroll-setup/payroll-items',
        PAYROLL_BENEFITS: '/payroll-setup/payroll-benefits',
        PAYROLL_PROCESS: '/payroll-setup/payroll-process',
        PAYROLL_REPORTS: '/payroll-setup/payroll-reports',
    }
}

// Environment configuration
export const ENV_CONFIG = {
    IS_DEVELOPMENT: import.meta.env.DEV,
    IS_PRODUCTION: import.meta.env.PROD,
    APP_NAME: import.meta.env.VITE_APP_NAME || 'Control Panel',
    APP_VERSION: import.meta.env.VITE_APP_VERSION || '1.0.0',
    DEBUG: import.meta.env.VITE_DEBUG === 'true',
}
