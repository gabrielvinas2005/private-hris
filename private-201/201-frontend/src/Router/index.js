import { createRouter, createWebHistory } from 'vue-router'

// ===== MAIN MODULE =====
const HRmodule = () => import('../Views/HRModule.vue')

// ===== EMPLOYEE MANAGEMENT =====
const EmployeeRecords = () => import('../Views/Employee_Records/Employee_records.vue')
const EmployeeAssignment = () => import('../Views/Employee_Assignment/Employee_Assignment.vue')
const OffBoarding = () => import('../Views/Off_Boarding/Off_Boarding.vue')
const StepIncrement = () => import('../Views/Step_Increment/Step_Increment.vue')
const StepIncrementApproval = () => import('../Views/Step_Increment_approval/StepIncrement_approval.vue')
const SalaryAdjustment = () => import('../Views/Salary_Adjustment/Salary_Adjustment.vue')
const IPCR = () => import('../Views/IPCR/IPCR.vue')
const OPCR = () => import('../Views/OPCR/OPCR.vue')
const DPCR = () => import('../Views/DPCR/DPCR.vue')
const Update201 = () => import('../Views/Update_201_Schedule/Update_201sched.vue')
const ExportEmployeeData = () => import('../Views/Export_employee_data/Export_employeeData.vue')
const VacantPositionPosting = () => import('../Views/Vacant_positioning_posting/Vacant_Positioningposting.vue')
const LengthOfService = () => import('../Views/Length_of_service/Length_Of_Service.vue')

// ===== HR REPORTS =====
const HRReports = () => import('../Views/HR_Reports/HR_Reports.vue')
const PersonalDataSheet = () => import('../Views/HR_Reports/PersonalDataSheet.vue')
const NOSI = () => import('../Views/HR_Reports/NOSI.vue')
const NOSA = () => import('../Views/HR_Reports/NOSA.vue')
const TerminalLeaveEndorsement = () => import('../Views/HR_Reports/TerminalLeave_Endorsement.vue')
const TravelAbroadEndorsement = () => import('../Views/HR_Reports/Travel_Abroad_Endrosement.vue')
const PlantillaReport = () => import('../Views/HR_Reports/Plantilla_report.vue')
const Requestforpublicaiton = () => import('../Views/HR_Reports/Requestforpublicaiton.vue')
const BirthdaySummary = () => import('../Views/HR_Reports/BirthdaySummary.vue')
const NewlyHiredAndPromoted = () => import('../Views/HR_Reports/Newlyhiredandpromoted.vue')


// ===== CERTIFICATES =====
const CertificatesEmployee = () => import('../Views/Certificate/Employee_certificate.vue')
const CertificatesComp = () => import('../Views/Certificate/CertificateOfCompsensation.vue')
const CertificatesMedical = () => import('../Views/Certificate/Medical_Certificate.vue')
const ServiceRecord = () => import('../Views/Certificate/Service_record.vue')
const ResignationAcceptance = () => import('../Views/Certificate/AcceptanceOfResignation.vue')
const AcceptanceOfRetirement = () => import('../Views/Certificate/AcceptanceOfRetirement.vue')
const LastDayService = () => import('../Views/Certificate/LastDayofServiceCert.vue')
const NoPendingCert = () => import('../Views/Certificate/NoPendingCert.vue')
const ATMRequestCert = () => import('../Views/Certificate/ATMRequestCert.vue')
const CertAppearance = () => import('../Views/Certificate/CertOfAppearance.vue')
const OJTCert = () => import('../Views/Certificate/OJTCert.vue')
const ClearanceCertificate = () => import('../Views/Certificate/Clearance_cert.vue')
const TransferofLeaveCredits = () => import('../Views/Certificate/TransferofLeaveCredits.vue')
const AcceptanceLetterIntern = () => import('../Views/Certificate/AcceptanceLetterIntern.vue')
const AcceptanceLetter = () => import('../Views/Certificate/AcceptanceLetter.vue')
const CertOfCompletion = () => import('../Views/Certificate/CertOfCompletion.vue')
const CertOfLastSalary = () => import('../Views/Certificate/CertofLastSalary.vue')
const CertOfSalaryDeduction = () => import('../Views/Certificate/CertofSalaryDeduction.vue')
const CertOfRenderedService = () => import('../Views/Certificate/CertofRenderedService.vue')
const COSCertificate = () => import('../Views/Certificate/COScert.vue')
const COSContractCert = () => import('../Views/Certificate/COSContractCert.vue')
const COSNonDisclosureAgreement = () => import('../Views/Certificate/COSNonDisclosureAgreement.vue')

// ===== RECRUITMENT REPORTS =====
const RecAppointment = () => import('../Views/Recruitment_Reports/Appointment_cert.vue')
const RecAssumption = () => import('../Views/Recruitment_Reports/Assumption_of_duty.vue')
const RecOath = () => import('../Views/Recruitment_Reports/OathOfOffice.vue')
const RecWorkExperienceSheet = () => import('../Views/HR_Reports/WorkExperienceSheet.vue')
const RecPositionDescription = () => import('../Views/Recruitment_Reports/PositionDescription.vue')
const RecRegretLetter = () => import('../Views/Recruitment_Reports/Regretletter.vue')

// ===== RECRUITMENT PROCESS =====
const RecAdministratorSelection = () => import('../Views/Recruitment/Administrator_Selection/AdministratorSelection.vue')
const RecApplicantQualification = () => import('../Views/Recruitment/Applicant_Qualification/Applicant_Qualification.vue')
const RecApplicantShortlisting = () => import('../Views/Recruitment/Applicant_Shortlisting/Applicant_Shortlisting.vue')
const RecHRDDPerfReview = () => import('../Views/Recruitment/HRRD_Perf_Review/HRDDReview.vue')
const RecExamination = () => import('../Views/Recruitment/Examination/Examination.vue')
const RecPanelInterviewSetup = () => import('../Views/Recruitment/Panel_Interview_Setup/PanelInterviewSetup.vue')
const RecApplicantsRecords = () => import('../Views/Recruitment/Applicants_Monitoring/ApplicantsMonitoring.vue')


const routes = [
    // ===== DEFAULT ROUTE =====
    { path: '/', redirect: '/hr' },

    // ===== MAIN MODULE =====
    { path: '/hr', name: 'hr', component: HRmodule, meta: { title: 'Overview' } },

    // ===== EMPLOYEE MANAGEMENT ROUTES =====
    { path: '/employee-records', name: 'employee-records', component: EmployeeRecords },
    { path: '/employee-assignments', name: 'employee-assignments', component: EmployeeAssignment },
    { path: '/off-boarding', name: 'off-boarding', component: OffBoarding },
    { path: '/step-increment', name: 'step-increment', component: StepIncrement },
    { path: '/step-increment-approval', name: 'step-increment-approval', component: StepIncrementApproval },
    { path: '/salary-adjustment', name: 'salary-adjustment', component: SalaryAdjustment },
    { path: '/ipcr', name: 'ipcr', component: IPCR },
    { path: '/opcr', name: 'opcr', component: OPCR },
    { path: '/dpcr', name: 'dpcr', component: DPCR },
    { path: '/update-201-schedule', name: 'update-201-schedule', component: Update201 },
    { path: '/export-employee-data', name: 'export-employee-data', component: ExportEmployeeData },
    { path: '/vacant-position-posting', name: 'vacant-position-posting', component: VacantPositionPosting },
    { path: '/length-of-service', name: 'length-of-service', component: LengthOfService },

    // ===== HR REPORTS ROUTES =====
    { path: '/hr-reports', name: 'hr-reports', component: HRReports },
    { path: '/hr-reports/personal-data-sheet', name: 'hr-personal-data-sheet', component: PersonalDataSheet },
    { path: '/hr-reports/nosi', name: 'hr-nosi', component: NOSI },
    { path: '/hr-reports/nosa', name: 'hr-nosa', component: NOSA },
    { path: '/hr-reports/terminal-leave-endorsement', name: 'hr-terminal-leave', component: TerminalLeaveEndorsement },
    { path: '/hr-reports/travel-abroad-endorsement', name: 'hr-travel-abroad', component: TravelAbroadEndorsement },
    { path: '/hr-reports/plantilla-report', name: 'hr-plantilla-report', component: PlantillaReport },
    { path: '/hr-reports/request-for-publicaiton', name: 'hr-request-for-publicaiton', component: Requestforpublicaiton },
    { path: '/hr-reports/birthday-summary', name: 'hr-birthday-summary', component: BirthdaySummary },
    { path: '/hr-reports/cos-contract-cert', name: 'hr-cos-contract-cert', component: COSContractCert },
    { path: '/hr-reports/cos-non-disclosure-agreement', name: 'hr-cos-non-disclosure-agreement', component: COSNonDisclosureAgreement },
    { path: '/hr-reports/newly-hired-and-promoted', name: 'hr-newly-hired-and-promoted', component: NewlyHiredAndPromoted },

    // ===== CERTIFICATES ROUTES =====
    { path: '/certificates/employee', name: 'cert-employee', component: CertificatesEmployee },
    { path: '/certificates/cos-certificate', name: 'cert-cos-certificate', component: COSCertificate },
    { path: '/certificates/cos-contract', name: 'cert-cos-contract', component: COSContractCert },
    { path: '/certificates/compensation', name: 'cert-comp', component: CertificatesComp },
    { path: '/certificates/medical', name: 'cert-medical', component: CertificatesMedical },
    { path: '/certificates/service-record', name: 'cert-service-record', component: ServiceRecord },
    { path: '/certificates/acceptance-resignation', name: 'cert-resignation', component: ResignationAcceptance },
    { path: '/certificates/acceptance-of-retirement', name: 'cert-acceptance-of-retirement', component: AcceptanceOfRetirement },
    { path: '/certificates/last-day-service', name: 'cert-last-day', component: LastDayService },
    { path: '/certificates/no-pending', name: 'cert-no-pending', component: NoPendingCert },
    { path: '/certificates/atm-request', name: 'cert-atm', component: ATMRequestCert },
    { path: '/certificates/appearance', name: 'cert-appearance', component: CertAppearance },
    { path: '/certificates/ojt', name: 'cert-ojt', component: OJTCert },
    { path: '/certificates/clearance-certificate', name: 'cert-clearance', component: ClearanceCertificate },
    { path: '/certificates/transfer-of-leave-credit', name: 'cert-transfer-of-leave-credit', component: TransferofLeaveCredits },
    { path: '/certificates/acceptance-letter-intern', name: 'cert-acceptance-letter-intern', component: AcceptanceLetterIntern },
    { path: '/certificates/acceptance-letter', name: 'cert-acceptance-letter', redirect: '/recruitment-reports/acceptance-letter' },
    { path: '/certificates/cert-of-completion', name: 'cert-cert-of-completion', component: CertOfCompletion },
    { path: '/certificates/cert-of-last-salary', name: 'cert-cert-of-last-salary', component: CertOfLastSalary },
    { path: '/certificates/cert-of-salary-deduction', name: 'cert-cert-of-salary-deduction', component: CertOfSalaryDeduction },
    { path: '/certificates/cert-of-rendered-service', name: 'cert-cert-of-rendered-service', component: CertOfRenderedService },

    // ===== RECRUITMENT PROCESS ROUTES =====
    { path: '/recruitment/applicant-qualification', name: 'rec-applicant-qualification', component: RecApplicantQualification },
    { path: '/recruitment/applicant-shortlisting', name: 'rec-applicant-shortlisting', component: RecApplicantShortlisting },
    { path: '/recruitment/applicants-records', name: 'rec-applicants-records', component: RecApplicantsRecords },
    { path: '/recruitment/hrdd-perf-review', name: 'rec-hrdd-perf-review', component: RecHRDDPerfReview },
    { path: '/recruitment/examination', name: 'rec-examination', component: RecExamination },
    { path: '/recruitment/panel-interview-setup', name: 'rec-panel-interview-setup', component: RecPanelInterviewSetup },
    { path: '/recruitment/administrator-selection', name: 'rec-administrator-selection', component: RecAdministratorSelection },

    // ===== RECRUITMENT REPORTS ROUTES =====
    { path: '/recruitment-reports/appointment-cert', name: 'rec-appointment', component: RecAppointment },
    { path: '/recruitment-reports/assumption-of-duty', name: 'rec-assumption', component: RecAssumption },
    { path: '/recruitment-reports/oath-of-office', name: 'rec-oath', component: RecOath },
    { path: '/recruitment-reports/acceptance-letter', name: 'rec-acceptance-letter', component: AcceptanceLetter },
    { path: '/recruitment-reports/work-experience-sheet', name: 'rec-work-experience-sheet', component: RecWorkExperienceSheet },
    { path: '/recruitment-reports/position-description', name: 'rec-position-description', component: RecPositionDescription },
    { path: '/recruitment-reports/regret-letter', name: 'rec-regret-letter', component: RecRegretLetter },

    // ===== CATCH-ALL ROUTE =====
    { path: '/:pathMatch(.*)*', redirect: '/hr' }
]

const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
    routes
})

export default router


