import { createRouter, createWebHistory } from 'vue-router'

const TKmodule = () => import('../Views/TKModule.vue')
const FixSchedule = () => import('../Views/Fix_Schedule/Fix_Schedule.vue')
const ShiftingSchedule = () => import('../Views/Shifting_Schedule/Shifting_Schedule.vue')
const AssignFixSchedule = () => import('../Views/Assign_Fix_Schedule/AssignFixSchedule.vue')
const LeaveCredits = () => import('../Views/Leave_Credits/Leave_Credits.vue')
const LeaveMonitoring = () => import('../Views/Leave_Monitoring/Leave_Monitoring.vue')
// const LeaveTakenMonitoring = () => import('../Views/Leave_Taken_Monitoring/LeaveTakenMonitoring.vue') // Hidden - redundant with Leave Credit Card Monitoring
const LeaveCreditMonitoring = () => import('../Views/Leave_Credits/Leave_Credit_Card_Monitoring.vue')
const OBMonitoring = () => import('../Views/OB_Monitoring/OB_Monitoring.vue')
const PassSlipMonitoring = () => import('../Views/Pass_Slip_Monitoring/Pass_Slip_Monitoring.vue')

const OTMonitoring = () => import('../Views/OT_Monitoring/OT_Monitoring.vue')
const COCMonitoring = () => import('../Views/COC_Monitoring/COC_Monitoring.vue')
const WorkSuspension = () => import('../Views/Work_Suspension/Work_Suspension.vue')
const BiometricsData = () => import('../Views/Biometrics_Data/Biometrics_Data.vue')
const ProcessAttendance = () => import('../Views/Process_Attendance/Process_Attendance.vue')
const TardinessReports = () => import('../Views/Timekeeping_Reports/Tardiness_Reports.vue')

// Report Components (ReportView removed)

const routes = [
    { path: '/', name: 'tk', component: TKmodule, meta: { title: 'Timekeeping Module' } },
    { path: '/tk', redirect: '/' },
    { path: '/fix-schedule', name: 'fix-schedule', component: FixSchedule },
    { path: '/shifting-schedule', name: 'shifting-schedule', component: ShiftingSchedule },
    { path: '/assign-fix-schedule', name: 'assign-fix-schedule', component: AssignFixSchedule },
    { path: '/leave-credits', name: 'leave-credits', component: LeaveCredits },
    { path: '/leave-monitoring', name: 'leave-monitoring', component: LeaveMonitoring },
    // { path: '/leave-taken-monitoring', name: 'leave-taken-monitoring', component: LeaveTakenMonitoring }, // Hidden - redundant with Leave Credit Card Monitoring
    { path: '/leave-credit-monitoring', name: 'leave-credit-monitoring', component: LeaveCreditMonitoring },
    { path: '/ob-monitoring', name: 'ob-monitoring', component: OBMonitoring },
    { path: '/pass-slip-monitoring', name: 'pass-slip-monitoring', component: PassSlipMonitoring },
    { path: '/ot-monitoring', name: 'ot-monitoring', component: OTMonitoring },
    { path: '/coc-monitoring', name: 'coc-monitoring', component: COCMonitoring },
    { path: '/work-suspension', name: 'work-suspension', component: WorkSuspension },
    { path: '/biometrics-data', name: 'biometrics-data', component: BiometricsData },
    { path: '/process-attendance', name: 'process-attendance', component: ProcessAttendance },
    { path: '/tardiness-reports', name: 'tardiness-reports', component: TardinessReports },
    
    // Report routes removed
    // removed report-page route (component deleted)
    
    { path: '/:pathMatch(.*)*', redirect: '/tk' }
]

const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
    routes
})

// No report components exported

export default router


