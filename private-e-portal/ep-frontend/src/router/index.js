import { createRouter, createWebHistory } from 'vue-router';
import home from '../views/home.vue';
import login from '../views/auth/login.vue';
import dashboard from '../views/dashboard.vue';
import ProfileRecords from '../views/profile-records.vue';
import LeaveTime from '../views/leave-time.vue';
import OvertimeScheduling from '../views/overtime-scheduling.vue';
import PayrollCompensation from '../views/payroll-compensation.vue';
import Employee201File from '../views/201-file/index.vue';
import SALNView from '../views/SALN/index.vue';
import CompetenciesView from '../views/Competencies/index.vue';
import AddCompetencyView from '../views/Competencies/add.vue';
import EditCompetencyView from '../views/Competencies/edit.vue';
import EmployeeCompetenciesView from '../views/Competencies/employees.vue';
import MyCompetenciesView from '../views/Competencies/my-competencies.vue';
import EmployeeDetailsView from '../views/Competencies/employee-details.vue';
import LeaveManagementView from '../views/Leaves/index.vue';
import LeaveApplicationView from '../views/Leaves/add.vue';
import MonetizationView from '../views/Monetization/index.vue';
import PassSlipView from '../views/PassSlip/index.vue';
import OBApplicationView from '../views/OB_Application/index.vue';
import DTRListView from '../views/DTR/index.vue';
import DTRDetailView from '../views/DTR/detail.vue';
import DTRTimeLogsView from '../views/DTR/time-logs.vue';
import DTRForApprovalView from '../views/DTR/ForApproval.vue';
import DTRApplicationView from '../views/DTR/Application.vue';
import AccomplishmentApplicationView from '../views/Accomplishment/Application.vue';
import AccomplishmentForApprovalView from '../views/Accomplishment/ForApproval.vue';
import ServiceRenderedApplicationView from '../views/ServiceRendered/Application.vue';
import ServiceRenderedForApprovalView from '../views/ServiceRendered/ForApproval.vue';
import WFHAttendanceView from '../views/WFHAttendance/index.vue';
import WFHApplicationView from '../views/WFHApplication/index.vue';
import OvertimeRequestView from '../views/Overtime_Request/index.vue';
import PayslipListView from '../views/Payslip/index.vue';
import IPCR from '../views/IPCR/index.vue';
import IPCRAgencyHeadApproval from '../views/IPCR/AgencyHeadApproval.vue';
import IPCRHRRecalibration from '../views/IPCR/HRRecalibration.vue';
import PayslipDetailView from '../views/Payslip/detail.vue';
import PayslipPrintView from '../views/Payslip/print.vue';
import PayslipReportListView from '../views/Payslip/report-list.vue';
import OPCR from '../views/OPCR/index.vue';
import DPCR from '../views/DPCR/index.vue';
import NonDTRReview from '../views/NonDTR/index.vue';
import DivisionHeadNonDTRReview from '../views/NonDTR/DivisionHeadReview.vue';
import ApplicantMonitoring from '../views/Applicants/index.vue';
import PanelInterviewRatings from '../views/PanelInterviewRatings/index.vue';
const AnnouncementsView = () => import('../views/Announcement/index.vue');
import OtpVerification from '../views/auth/otp.vue';
import ChangePassword from '../views/auth/change-password.vue';

const routes = [
    {
        path: '/',
        component: home,
        meta: { requiresAuth: false }
    },
    {
        path: '/login',
        component: login,
        meta: { requiresAuth: false, requiresGuest: true }
    },
    {
        path: '/verify-otp',
        component: OtpVerification,
        meta: { requiresAuth: false }
    },
    {
        path: '/change-password',
        component: ChangePassword,
        meta: { requiresAuth: false }
    },

    // Protected routes - require authentication
    {
        path: '/dashboard',
        component: dashboard,
        meta: { requiresAuth: true }
    },
    {
        path: '/profile-records',
        component: ProfileRecords,
        meta: { requiresAuth: true }
    },
    {
        path: '/201-file',
        component: Employee201File,
        meta: { requiresAuth: true }
    },
    {
        path: '/201-file/:id',
        component: Employee201File,
        meta: { requiresAuth: true }
    },
    {
        path: '/saln',
        component: SALNView,
        meta: { requiresAuth: true }
    },
    {
        path: '/leave-time',
        component: LeaveTime,
        meta: { requiresAuth: true }
    },
    {
        path: '/leave-monetization',
        component: MonetizationView,
        meta: { requiresAuth: true }
    },
    {
        path: '/overtime-scheduling',
        component: OvertimeScheduling,
        meta: { requiresAuth: true }
    },
    {
        path: '/payroll-compensation',
        component: PayrollCompensation,
        meta: { requiresAuth: true }
    },
    {
        path: '/competencies',
        component: CompetenciesView,
        meta: { requiresAuth: true }
    },
    {
        path: '/competencies/add',
        component: AddCompetencyView,
        meta: { requiresAuth: true }
    },
    {
        path: '/competencies/edit/:id',
        component: EditCompetencyView,
        meta: { requiresAuth: true }
    },
    {
        path: '/competencies/employees',
        component: EmployeeCompetenciesView,
        meta: { requiresAuth: true }
    },
    {
        path: '/competencies/my-competencies',
        component: MyCompetenciesView,
        meta: { requiresAuth: true }
    },
    {
        path: '/competencies/employees/:id/details',
        component: EmployeeDetailsView,
        meta: { requiresAuth: true }
    },
    {
        path: '/leaves',
        component: LeaveManagementView,
        meta: { requiresAuth: true }
    },
    {
        path: '/leaves/add',
        component: LeaveApplicationView,
        meta: { requiresAuth: true }
    },
    {
        path: '/leaves/edit/:id',
        component: LeaveApplicationView,
        meta: { requiresAuth: true }
    },
    {
        path: '/leaves/view/:id',
        component: LeaveApplicationView,
        meta: { requiresAuth: true }
    },
    {
        path: '/pass-slip',
        component: PassSlipView,
        meta: { requiresAuth: true }
    },
    {
        path: '/ob-application',
        component: OBApplicationView,
        meta: { requiresAuth: true }
    },
    {
        path: '/dtr',
        component: DTRListView,
        meta: { requiresAuth: true }
    },
    {
        path: '/dtr/view/:employeeId/:dtrId',
        component: DTRDetailView,
        meta: { requiresAuth: true }
    },
    {
        path: '/dtr/time-logs/:employeeId',
        component: DTRTimeLogsView,
        meta: { requiresAuth: true }
    },
    {
        path: '/dtr/application',
        component: DTRApplicationView,
        meta: { requiresAuth: true }
    },
    {
        path: '/dtr/for-approval',
        component: DTRForApprovalView,
        meta: { requiresAuth: true }
    },
    {
        path: '/wfh-attendance',
        component: WFHAttendanceView,
        meta: { requiresAuth: true }
    },
    {
        path: '/accomplishment/application',
        component: AccomplishmentApplicationView,
        meta: { requiresAuth: true }
    },
    {
        path: '/accomplishment/for-approval',
        component: AccomplishmentForApprovalView,
        meta: { requiresAuth: true }
    },
    {
        path: '/service-rendered/application',
        component: ServiceRenderedApplicationView,
        meta: { requiresAuth: true }
    },
    {
        path: '/service-rendered/for-approval',
        component: ServiceRenderedForApprovalView,
        meta: { requiresAuth: true }
    },
    {
        path: '/review-dtr',
        component: NonDTRReview,
        meta: { requiresAuth: true }
    },
    {
        path: '/division-head-non-dtr',
        component: DivisionHeadNonDTRReview,
        meta: { requiresAuth: true }
    },
    {
        path: '/wfh-application',
        component: WFHApplicationView,
        meta: { requiresAuth: true, tabMenuAccess: ['WFH Application'] }
    },
    {
        path: '/overtime-request',
        component: OvertimeRequestView,
        meta: { requiresAuth: true }
    },
    {
        path: '/overtime-monitoring',
        component: OvertimeRequestView,
        meta: { requiresAuth: true }
    },
    {
        path: '/payslip',
        component: PayslipListView,
        meta: { requiresAuth: true }
    },
    {
        path: '/ipcr',
        component: IPCR,
        meta: { requiresAuth: true }
    },
    {
        path: '/ipcr/agency-head-approval',
        component: IPCRAgencyHeadApproval,
        meta: { requiresAuth: true }
    },
    {
        path: '/ipcr/hr-recalibration',
        component: IPCRHRRecalibration,
        meta: { requiresAuth: true }
    },
    {
        path: '/opcr',
        component: OPCR,
        meta: { requiresAuth: true }
    },
    {
        path: '/dpcr',
        component: DPCR,
        meta: { requiresAuth: true }
    },
    {
        path: '/payslip/view/:employeeId/:payslipId',
        component: PayslipDetailView,
        meta: { requiresAuth: true }
    },
    {
        path: '/payslip/print/:employeeId/:payslipId',
        component: PayslipPrintView,
        meta: { requiresAuth: true }
    },
    {
        path: '/payslip/report',
        component: PayslipReportListView,
        meta: { requiresAuth: true }
    },
    {
        path: '/announcements',
        component: AnnouncementsView,
        meta: { requiresAuth: true }
    },
    {
        path: '/applicants',
        component: ApplicantMonitoring,
        meta: { requiresAuth: true }
    },
    {
        path: '/panel-interview-ratings',
        component: PanelInterviewRatings,
        meta: { requiresAuth: true }
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

function normalizeTabMenuLabel(s) {
    if (s === null || typeof s === 'undefined') return ''
    return String(s).trim().toLowerCase().replace(/\s+/g, ' ')
}

function parseStoredUser() {
    try {
        const raw = localStorage.getItem('user_data')
        if (!raw) return null
        const u = JSON.parse(raw)
        return u && typeof u === 'object' ? u : null
    } catch {
        return null
    }
}

/** User must finish email OTP + password change before using the portal */
function needsPasswordOnboarding(user) {
    if (!user) return false
    return !user.has_change_password
}

// Enhanced auth guard to be consistent with your dashboard
router.beforeEach(async (to, from, next) => {
    const user = parseStoredUser()
    const authToken = localStorage.getItem('auth_token')
    const tempToken = localStorage.getItem('temp_token')

    const isAuthenticated = !!(user || authToken)
    const pendingOnboarding = needsPasswordOnboarding(user)

    // Enforce onboarding step order on public auth routes
    if (pendingOnboarding) {
        if (to.path === '/change-password' && !tempToken) {
            return next('/verify-otp')
        }
        if (to.path === '/verify-otp' && tempToken) {
            return next('/change-password')
        }
    }

    // Fully onboarded users should not stay on OTP / first-time password screens
    if (!pendingOnboarding && isAuthenticated && (to.path === '/verify-otp' || to.path === '/change-password')) {
        return next('/dashboard')
    }

    // Routes that require authentication
    if (to.meta.requiresAuth && !isAuthenticated) {
        return next('/login')
    }

    if (to.meta.requiresAuth && pendingOnboarding) {
        const target = tempToken ? '/change-password' : '/verify-otp'
        if (to.path !== target) {
            return next(target)
        }
    }

    // Routes that require guest (not authenticated) - like login page
    if (to.meta.requiresGuest && isAuthenticated) {
        if (pendingOnboarding) {
            return next()
        }
        return next('/dashboard')
    }

    // Employee Portal tab access (`access` + `menus`, module_id = 1) — blocks deep links without rights
    if (isAuthenticated && to.meta.tabMenuAccess) {
        const required = Array.isArray(to.meta.tabMenuAccess)
            ? to.meta.tabMenuAccess
            : [to.meta.tabMenuAccess]
        try {
            const ApiService = (await import('../services/api.js')).default
            await ApiService.initSanctum()
            const res = await ApiService.getUserTabAccess()
            const menus = Array.isArray(res?.data?.menus) ? res.data.menus : []
            localStorage.setItem('user_tab_access', JSON.stringify({ menus }))

            const allowedKeys = menus.map((m) => normalizeTabMenuLabel(m?.menu_key))
            const allowedNames = menus.map((m) => normalizeTabMenuLabel(m?.menu))
            const hasAccess = required.some((label) => {
                const k = normalizeTabMenuLabel(label)
                return k && (allowedKeys.includes(k) || allowedNames.includes(k))
            })
            if (!hasAccess) {
                return next('/dashboard')
            }
        } catch (e) {
            // If the access API is unreachable, do not block navigation (availability).
            // Sidebar still refreshes from API when MainLayout loads.
        }
    }

    return next()
})

export default router;
