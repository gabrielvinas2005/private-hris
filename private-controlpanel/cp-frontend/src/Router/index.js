import { createRouter, createWebHistory } from 'vue-router'

const ControlPanel = () => import('../Views/ControlPanel/ControlPanel.vue')
const UserList = () => import('../Views/UserList/UserList.vue')
const UserActivity = () => import('../Views/UserActivity/UserActivity.vue')
const HRSetup = () => import('../Views/HR_Setup/HRSetup.vue')
const HRCompany = () => import('../Views/HR_Setup/Company_Setup/CompanySetup.vue')
const HRBranch = () => import('../Views/HR_Setup/Branch_Setup/BranchSetup.vue')
const HROffice = () => import('../Views/HR_Setup/Office_Setup/OfficeSetup.vue')
const HRDivision = () => import('../Views/HR_Setup/Division_Setup/DivisionSetup.vue')
const HRSection = () => import('../Views/HR_Setup/Section_Setup/SectionSetup.vue')
const HREligibility = () => import('../Views/HR_Setup/Eligibility_Setup/EligibilitySetup.vue')
const HREmploymentType = () => import('../Views/HR_Setup/Employment_Type_Setup/EmploymentTypeSetup.vue')
const HRSpecialization = () => import('../Views/HR_Setup/Specialization_Setup/SpecializationSetup.vue')
const HRPosition = () => import('../Views/HR_Setup/Position_Setup/PositionSetup.vue')
const HRPlantila = () => import('../Views/HR_Setup/Plantila_Setup/PlantilaSetup.vue')
const HRNonPlantila = () => import('../Views/HR_Setup/Non_Plantila_Setup/NonPlantilaSetup.vue')
const HRPromotionTypes = () => import('../Views/HR_Setup/Promotion_Type_Setup/PromotionTypesSetup.vue')
const HROffBoardingTypes = () => import('../Views/HR_Setup/OffBoarding_Types/OffboardingTypesSetup.vue')
const HRIpcrRatings = () => import('../Views/HR_Setup/IPCR_Ratings/IpcrRatings.vue')
const HRDocumentNo = () => import('../Views/HR_Setup/Document_No_Setup/DocumentNoSetup.vue')
const HRDocumentType = () => import('../Views/HR_Setup/Document_Type_Setup/DocumentTypeSetup.vue')
const HRSemesterRating = () => import('../Views/HR_Setup/Semester_Rating_Setup/SemesterRatingSetup.vue')
const HRCompetencies = () => import('../Views/HR_Setup/Competencies_Setup/CompetenciesSetup.vue')
const HREeteRating = () => import('../Views/HR_Setup/EETE_Rating_Setup/EeteRatingSetup.vue')
const HRExamCategory = () => import('../Views/HR_Setup/Exam_Category_Setup/ExamCategorySetup.vue')
const HRPMT = () => import('../Views/HR_Setup/PMT_Setup/PMTSetup.vue')
const HRDownloadableDocs = () => import('../Views/HR_Setup/Downloadable_Docs/DownloadableDocsSetup.vue')
const HRInterviewSetup = () => import('../Views/HR_Setup/Interview_Setup/InterviewSetup.vue')
const TimeKeepingSetup = () => import('../Views/TimeKeeping_Setup/TimeKeepingSetup.vue')
const TKOvertimeTypes = () => import('../Views/TimeKeeping_Setup/Overtime_Types_setup/OvertimeTypesSetup.vue')
const TKHolidayTypes = () => import('../Views/TimeKeeping_Setup/Holiday_Types_setup/HolidayTypesSetup.vue')
const TKHolidays = () => import('../Views/TimeKeeping_Setup/Holidays_setup/HolidaysSetup.vue')
const TKLeaveTypes = () => import('../Views/TimeKeeping_Setup/Leave_Types_setup/LeaveTypesSetup.vue')
const TKOfficialBusinessTypes = () => import('../Views/TimeKeeping_Setup/OB_setup/OfficialBusinessTypesSetup.vue')
const TKTimeKeeping = () => import('../Views/TimeKeeping_Setup/Time_keeping_setup/TimeKeepingItemsSetup.vue')
const TKBiometric = () => import('../Views/TimeKeeping_Setup/Biometric_Setup/BiometricSetup.vue')
const TKApprovers = () => import('../Views/TimeKeeping_Setup/Approvers_Setup/ApproversSetup.vue')
const PayrollSetup = () => import('../Views/Payroll_Setup/PayrollSetup.vue')
const CashGiftTableSetup = () => import('../Views/Payroll_Setup/CashGift_table_setup/CashGift.vue')
const MonetizationSetup = () => import('../Views/Payroll_Setup/Monetization_setup/Monetization.vue')
const SalaryScheduleSetup = () => import('../Views/Payroll_Setup/Salary_schedule_setup/SalarySchedule.vue')
const TaxTableSetup = () => import('../Views/Payroll_Setup/Tax_table_setup/TaxTable.vue')
const HDMFTableSetup = () => import('../Views/Payroll_Setup/HDMF_table_setup/HDMF.vue')
const PhilhealthTableSetup = () => import('../Views/Payroll_Setup/Philhealth_table_setup/Philhealthtable.vue')
const GSISTableSetup = () => import('../Views/Payroll_Setup/GSIS_table_setup/GSIS.vue')
const SalaryStepSetup = () => import('../Views/Payroll_Setup/Salary_step_setup/SalaryStep.vue')
const SalaryGradeSetup = () => import('../Views/Payroll_Setup/Salary_grade_setup/SalaryGrade.vue')
const IncomeSetup = () => import('../Views/Payroll_Setup/Income_setup/Income.vue')
const DeductionSetup = () => import('../Views/Payroll_Setup/Deduction_setup/Deduction.vue')
const DeductionPrioritySetup = () => import('../Views/Payroll_Setup/Deduction_priority_setup/Deductionpriority.vue')
const PayrollIntervalSetup = () => import('../Views/Payroll_Setup/Payroll_interval_setup/PayrollInterval.vue')
const PayrollCutoffSetup = () => import('../Views/Payroll_Setup/Payroll_cutoff_setup/PayrollCutoff.vue')
const LoyaltyAwardSetup = () => import('../Views/Payroll_Setup/Loyalty_award_setup/LoyaltyAward.vue')
const UniformAndClothingAllowanceSetup = () => import('../Views/Payroll_Setup/UniformAndClothingAllowance_setup/Uniform&ClothingAllowance.vue')
const RataPositionsSetup = () => import('../Views/Payroll_Setup/Rata_positions_setup/RataPositions.vue')
const RataTableSetup = () => import('../Views/Payroll_Setup/Rata_table_setup/RataTable.vue')
const HazardPaySetup = () => import('../Views/Payroll_Setup/Hazard_pay_setup/Hazardpay.vue')
const OvertimeTaxTableSetup = () => import('../Views/Payroll_Setup/Overtime_taxtable_setup/OvertimeTaxTable.vue')
const MidYearBonusTableSetup = () => import('../Views/Payroll_Setup/MidYearBonus_table_setup/MidYearBonus.vue')
const YearEndBonusTableSetup = () => import('../Views/Payroll_Setup/YearEndBonus_table_setup/YearEndBonus.vue')
const ApplicantDocs = () => import('../Views/HR_Setup/Applicant_docs/applicantdocs.vue')


// const PayrollSetup = () => import('../Views/Payroll_Setup/PayrollSetup.vue')
// const PayrollPeriod = () => import('../Views/Payroll_Setup/Payroll_Period/PayrollPeriod.vue')
// const PayrollItemSchedule = () => import('../Views/Payroll_Setup/Payroll_item_schedule/PayrollItemSchedule.vue')
// const IncomeAndDeductions = () => import('../Views/Payroll_Setup/Income_and_deductions/IncomeAndDeductions.vue')
// const HDMFPremium = () => import('../Views/Payroll_Setup/HDMF_Premium/HDMF_Premium.vue')
// const LoanApplication = () => import('../Views/Payroll_Setup/Loan_Application/Loan_Application.vue')
// const Pacsval = () => import('../Views/Payroll_Setup/Pacsval/Pacsval.vue')
// const PayrollProcess = () => import('../Views/Payroll_Setup/Payroll_Process/Payroll_Process.vue')
// const PayrollBenefits = () => import('../Views/Payroll_Setup/Payroll_Benefits/Payroll_Benefits.vue')


const routes = [
    {
        path: '/',
        name: 'control-panel',
        component: ControlPanel,
        meta: { title: 'Control Panel' }
    },
    {
        path: '/users',
        name: 'users',
        component: UserList
    },
    {
        path: '/activity',
        name: 'activity',
        component: UserActivity
    },
    {
        path: '/hr-setup',
        name: 'hr-setup',
        component: HRSetup
    },
    //HR set up
    { path: '/hr-setup/company', name: 'hr-company', component: HRCompany },
    { path: '/hr-setup/branch', name: 'hr-branch', component: HRBranch },
    { path: '/hr-setup/office', name: 'hr-office', component: HROffice },
    { path: '/hr-setup/division', name: 'hr-division', component: HRDivision },
    { path: '/hr-setup/section', name: 'hr-section', component: HRSection },
    { path: '/hr-setup/eligibility', name: 'hr-eligibility', component: HREligibility },
    { path: '/hr-setup/employment-type', name: 'hr-employment-type', component: HREmploymentType },
    { path: '/hr-setup/specialization', name: 'hr-specialization', component: HRSpecialization },
    { path: '/hr-setup/position', name: 'hr-position', component: HRPosition },
    { path: '/hr-setup/plantila', name: 'hr-plantila', component: HRPlantila },
    { path: '/hr-setup/non-plantila', name: 'hr-non-plantila', component: HRNonPlantila },
    { path: '/hr-setup/promotion-types', name: 'hr-promotion-types', component: HRPromotionTypes },
    { path: '/hr-setup/off-boarding-types', name: 'hr-off-boarding-types', component: HROffBoardingTypes },
    { path: '/hr-setup/ipcr-ratings', name: 'hr-ipcr-ratings', component: HRIpcrRatings },
    { path: '/hr-setup/document-no', name: 'hr-document-no', component: HRDocumentNo },
    { path: '/hr-setup/document-type', name: 'hr-document-type', component: HRDocumentType },
    { path: '/hr-setup/semester-rating', name: 'hr-semester-rating', component: HRSemesterRating },
    { path: '/hr-setup/competencies', name: 'hr-competencies', component: HRCompetencies },
    { path: '/hr-setup/eete-rating', name: 'hr-eete-rating', component: HREeteRating },
    { path: '/hr-setup/exam-category', name: 'hr-exam-category', component: HRExamCategory },
    { path: '/hr-setup/pmt', name: 'hr-pmt', component: HRPMT },
    { path: '/hr-setup/downloadable-docs', name: 'hr-downloadable-docs', component: HRDownloadableDocs },
    { path: '/hr-setup/interview-setup', name: 'hr-interview-setup', component: HRInterviewSetup },
    {
        path: '/timekeeping-setup',
        name: 'timekeeping-setup',
        component: TimeKeepingSetup
    },
    { path: '/hr-setup/applicant-documents', name: 'hr-applicant-documents', component: ApplicantDocs },
    //time keeping
    { path: '/timekeeping-setup/overtime-types', name: 'tk-overtime-types', component: TKOvertimeTypes },
    { path: '/timekeeping-setup/holiday-types', name: 'tk-holiday-types', component: TKHolidayTypes },
    { path: '/timekeeping-setup/holidays', name: 'tk-holidays', component: TKHolidays },
    { path: '/timekeeping-setup/leave-types', name: 'tk-leave-types', component: TKLeaveTypes },
    { path: '/timekeeping-setup/official-business-types', name: 'tk-official-business-types', component: TKOfficialBusinessTypes },
    { path: '/timekeeping-setup/time-keeping', name: 'tk-time-keeping', component: TKTimeKeeping },
    { path: '/timekeeping-setup/biometric', name: 'tk-biometric', component: TKBiometric },
    { path: '/timekeeping-setup/approvers', name: 'tk-approvers', component: TKApprovers },
    {
        path: '/payroll-setup',
        name: 'payroll-setup',
        component: PayrollSetup
    },
    //payroll setup
    { path: '/payroll-setup/salary-schedule-setup', name: 'payroll-salary-schedule', component: SalaryScheduleSetup },
    { path: '/payroll-setup/tax-table-setup', name: 'payroll-tax-table', component: TaxTableSetup, alias: ['/payroll-setup/Tax Table Setup'] },
    { path: '/payroll-setup/HDMF Table Setup', name: 'payroll-hdmf-table', component: HDMFTableSetup },
    { path: '/payroll-setup/Philhealth Table Setup', name: 'payroll-philhealth-table', component: PhilhealthTableSetup },
    { path: '/payroll-setup/GSIS Table Setup', name: 'payroll-gsis-table', component: GSISTableSetup },
    { path: '/payroll-setup/Salary Step Setup', name: 'payroll-salary-step', component: SalaryStepSetup },
    {
        path: '/payroll-setup/salary-grade-setup',
        name: 'payroll-salary-grade',
        component: SalaryGradeSetup,
        alias: '/payroll-setup/Salary%20Grade%20Setup'
    },
    {
        path: '/payroll-setup/income-setup',
        name: 'payroll-income',
        component: IncomeSetup,
        alias: '/payroll-setup/Income%20Setup'
    },
    {
        path: '/payroll-setup/deduction-setup',
        name: 'payroll-deduction',
        component: DeductionSetup,
        alias: '/payroll-setup/Deduction%20Setup'
    },
    {
        path: '/payroll-setup/deduction-priority-setup',
        name: 'payroll-deduction-priority',
        component: DeductionPrioritySetup,
        alias: '/payroll-setup/Deduction%20Priority%20Setup'
    },
    {
        path: '/payroll-setup/payroll-interval-setup',
        name: 'payroll-payroll-interval',
        component: PayrollIntervalSetup,
        alias: '/payroll-setup/Payroll%20Interval%20Setup'
    },
    {
        path: '/payroll-setup/payroll-cutoff-setup',
        name: 'payroll-payroll-cutoff',
        component: PayrollCutoffSetup,
        alias: '/payroll-setup/Payroll%20Cut-off%20Setup'
    },
    {
        path: '/payroll-setup/loyalty-award-setup',
        name: 'payroll-loyalty-award',
        component: LoyaltyAwardSetup,
        alias: '/payroll-setup/Loyalty%20Award%20Setup'
    },
    {
        path: '/payroll-setup/uniform-and-clothing-allowance-setup',
        name: 'payroll-uniform-and-clothing-allowance',
        component: UniformAndClothingAllowanceSetup,
        alias: '/payroll-setup/Uniform%20and%20Clothing%20Allowance%20Setup'
    },
    {
        path: '/payroll-setup/rata-positions-setup',
        name: 'payroll-rata-positions',
        component: RataPositionsSetup,
        alias: '/payroll-setup/RATA%20Positions%20Setup'
    },
    {
        path: '/payroll-setup/rata-table-setup',
        name: 'payroll-rata-table',
        component: RataTableSetup,
        alias: '/payroll-setup/RATA%20Table%20Setup'
    },
    {
        path: '/payroll-setup/hazard-pay-setup',
        name: 'payroll-hazard-pay',
        component: HazardPaySetup,
        alias: '/payroll-setup/Hazard%20Pay%20Setup'
    },
    { path: '/payroll-setup/overtime-tax-table-setup', name: 'payroll-overtime-tax-table', component: OvertimeTaxTableSetup, alias: ['/payroll-setup/Overtime Tax Table Setup'] },
    {
        path: '/payroll-setup/mid-year-bonus-table-setup',
        name: 'payroll-mid-year-bonus-table',
        component: MidYearBonusTableSetup,
        alias: '/payroll-setup/Mid%20Year%20Bonus%20Table%20Setup'
    },
    {
        path: '/payroll-setup/year-end-bonus-table-setup',
        name: 'payroll-year-end-bonus-table',
        component: YearEndBonusTableSetup,
        alias: '/payroll-setup/Year%20End%20Bonus%20Table%20Setup'
    },
    {
        path: '/payroll-setup/cash-gift-table-setup',
        name: 'payroll-cash-gift-table',
        component: CashGiftTableSetup,
        alias: '/payroll-setup/Cash%20Gift%20Table%20Setup'
    },
    {
        path: '/payroll-setup/monetization-setup',
        name: 'payroll-monetization',
        component: MonetizationSetup,
        alias: '/payroll-setup/Monetization%20Setup'
    }

]

const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
    routes
})

export default router


