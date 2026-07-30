import { createRouter, createWebHistory } from "vue-router";
import {
  bootstrapAuth,
  ensurePayrollAccessUser,
  isEPortalSharedAuth,
} from "../Composables/useAuth";
import { canAccessPayrollRoute } from "../config/payrollAccess";

const PayrollModule = () => import("../Views/PayrollModule.vue");
const PayrollPeriod = () =>
  import("../Views/Payroll_Period/Payroll_Period.vue");
const PayrollItemSchedule = () =>
  import("../Views/Payroll_item_schedule/PayrollItemSchedule.vue");
const IncomeDeduction = () =>
  import("../Views/Income_and_Deduction/Income&Deduction.vue");
const HDMFPremium = () => import("../Views/HDMF_Premium/HDMF_Premium.vue");
const LoanApplication = () =>
  import("../Views/Loan_Application/Loan_Application.vue");
const PayrollProcess = () =>
  import("../Views/Payroll_Process/Payroll_Process.vue");
const COSPayroll = () => import("../Views/COS_Payroll/COS_Payroll.vue");

// Payroll Benefits imports
const PayrollBenefitsLayout = () =>
  import("../Views/Payroll_Benefits/PayrollBenefitsLayout.vue");
const BenefitsIndexRedirect = () =>
  import("../Views/Payroll_Benefits/BenefitsIndexRedirect.vue");
const OvertimePayment = () =>
  import("../Views/Payroll_Benefits/Overtime_Payment/Overtime_Payment.vue");
const UniformClothingAllowance = () =>
  import("../Views/Payroll_Benefits/Uniform_and_ClothingAllowance/Unif&ClothingAllow.vue");
const PayrollCommunicationMacco = () =>
  import("../Views/Payroll_Benefits/Payroll-Communication Macco/Payroll_CommunicationMacco.vue");
const LoyaltyAward = () =>
  import("../Views/Payroll_Benefits/Loyalty_Award/Loyalty_Award.vue");
const RATAPayroll = () =>
  import("../Views/Payroll_Benefits/RATA_Payroll/RATAPayroll.vue");
const HazardPay = () =>
  import("../Views/Payroll_Benefits/Hazard_Pay/Hazard_Pay.vue");

const MonetizationPayroll = () =>
  import("../Views/Payroll_Benefits/Monetization_Payroll/MonetizationPayroll.vue");
const MidYearBonus = () =>
  import("../Views/Payroll_Benefits/MidYear_Bonus/MidYearBonus.vue");
const YearEndBonus = () =>
  import("../Views/Payroll_Benefits/YearEnd_Bonus/YearEndBonus.vue");
const ExtraBonus = () =>
  import("../Views/Payroll_Benefits/Extra_Bonus/Extra_Bonus.vue");
const RetirementBenefits = () =>
  import("../Views/Payroll_Benefits/Retirement_Benefits/RetirementBenefits.vue");

// Payroll Reports imports
const PayrollSummary = () =>
  import("../Views/Payroll_Reports/rptPayrollSummary.vue");
const PayrollSummaryDetailed = () =>
  import("../Views/Payroll_Reports/rptPayrollSummaryDetailed.vue");
const PayslipReport = () => import("../Views/Payroll_Reports/rptPayslip.vue");
const LoyaltyAwardReport = () =>
  import("../Views/Payroll_Reports/rptLoyaltyAward.vue");
const PayrollCommunicationMaccoReport = () =>
  import("../Views/Payroll_Reports/rptPayrollCommunicationMacco.vue");
const BankRemittanceReport = () =>
  import("../Views/Payroll_Reports/rptBankRemittance.vue");
const PhilhealthRemittanceReport = () =>
  import("../Views/Payroll_Reports/rptPhilhealthRemittance.vue");
const PagIbigContributionReport = () =>
  import("../Views/Payroll_Reports/rptPagIbigContribution.vue");
const PagIbigLoanReport = () =>
  import("../Views/Payroll_Reports/rptPagIbigLoan.vue");
const GSISRemittanceReport = () =>
  import("../Views/Payroll_Reports/rptGSISRemittance.vue");
const OvertimePaymentReport = () =>
  import("../Views/Payroll_Reports/rptOvertimePayment.vue");
const UniformClothingAllowanceReport = () =>
  import("../Views/Payroll_Reports/rptUniform&ClothingAllowance.vue");

const HazardPayReport = () =>
  import("../Views/Payroll_Reports/rptHazardPay.vue");

const ExtraBonusReport = () =>
  import("../Views/Payroll_Reports/rptExtraBonusPayroll.vue");
const RataPayrollReport = () =>
  import("../Views/Payroll_Reports/rptRataPayroll.vue");
const MonetizationPayrollReport = () =>
  import("../Views/Payroll_Reports/rptMonetizationPayroll.vue");
const MidYearBonusReportHub = () =>
  import("../Views/Payroll_Reports/rptMidYearBonusHub.vue");
const MidYearBonusReport = () =>
  import("../Views/Payroll_Reports/rptMidYearBonus.vue");
const AtmMidYearBonusReport = () =>
  import("../Views/Payroll_Reports/rptATMMidYearBonus.vue");
const MidYearIndividualVoucherReport = () =>
  import("../Views/Payroll_Reports/rptMidYearIndividualVoucher.vue");
const MidYearVoucherReport = () =>
  import("../Views/Payroll_Reports/rptMidYearVoucher.vue");
const YearEndBonusReport = () =>
  import("../Views/Payroll_Reports/rptYearEndBonus.vue");
const SubsistenceReport = () =>
  import("../Views/Payroll_Reports/rptSUBSISTENCE.vue");
const LandbankTextReport = () =>
  import("../Views/Payroll_Reports/rptLandbankTextReport.vue");
const AtmLetterLandbank = () =>
  import("../Views/Payroll_Reports/rptAtmLetterLandbank.vue");
const BIRForm2305 = () => import("../Views/BIR_Form_2305.vue");
const GSISMemberInfo = () => import("../Views/GSIS_Member_Info.vue");
const PhilHealthPMRF = () => import("../Views/PhilHealth_PMRF.vue");
const PagIbigMDF = () => import("../Views/PagIbig_MDF.vue");

const routes = [
  {
    path: "/",
    name: "payroll",
    component: PayrollModule,
    meta: { title: "Payroll Module" },
  },
  { path: "/tk", redirect: "/" },

  // Main Payroll Routes
  { path: "/payroll-period", name: "payroll-period", component: PayrollPeriod },
  {
    path: "/payroll-item-schedule",
    name: "payroll-item-schedule",
    component: PayrollItemSchedule,
  },
  {
    path: "/income-deduction",
    name: "income-deduction",
    component: IncomeDeduction,
  },
  { path: "/hdmf-premium", name: "hdmf-premium", component: HDMFPremium },
  {
    path: "/loan-application",
    name: "loan-application",
    component: LoanApplication,
  },
  {
    path: "/payroll-process",
    name: "payroll-process",
    component: PayrollProcess,
  },
  {
    path: "/cos-payroll",
    name: "cos-payroll",
    component: COSPayroll,
  },

  // Payroll Benefits routes
  {
    path: "/payroll-benefits",
    component: PayrollBenefitsLayout,
    children: [
      {
        path: "",
        name: "payroll-benefits-index",
        component: BenefitsIndexRedirect,
      },
      {
        path: "overtime-payment",
        name: "overtime-payment",
        component: OvertimePayment,
      },
      {
        path: "uniform-clothing-allowance",
        name: "uniform-clothing-allowance",
        component: UniformClothingAllowance,
      },
      {
        path: "payroll-communication-macco",
        name: "payroll-communication-macco",
        component: PayrollCommunicationMacco,
      },
      {
        path: "loyalty-award",
        name: "loyalty-award",
        component: LoyaltyAward,
      },
      {
        path: "rata-payroll",
        name: "rata-payroll",
        component: RATAPayroll,
      },
      { path: "hazard-pay", name: "hazard-pay", component: HazardPay },
      {
        path: "monetization-payroll",
        name: "monetization-payroll",
        component: MonetizationPayroll,
      },
      {
        path: "mid-year-bonus",
        name: "mid-year-bonus",
        component: MidYearBonus,
      },
      {
        path: "year-end-bonus",
        name: "year-end-bonus",
        component: YearEndBonus,
      },
      { path: "extra-bonus", name: "extra-bonus", component: ExtraBonus },
      {
        path: "retirement-benefits",
        name: "retirement-benefits",
        component: RetirementBenefits,
      },
    ],
  },

  // Legacy direct routes for backward compatibility
  {
    path: "/overtime-payment",
    redirect: "/payroll-benefits/overtime-payment",
  },
  {
    path: "/uniform-clothing-allowance",
    redirect: "/payroll-benefits/uniform-clothing-allowance",
  },
  {
    path: "/payroll-communication-macco",
    redirect: "/payroll-benefits/payroll-communication-macco",
  },
  {
    path: "/loyalty-award",
    redirect: "/payroll-benefits/loyalty-award",
  },
  {
    path: "/rata-payroll",
    redirect: "/payroll-benefits/rata-payroll",
  },
  {
    path: "/hazard-pay",
    redirect: "/payroll-benefits/hazard-pay",
  },
  {
    path: "/monetization-payroll",
    redirect: "/payroll-benefits/monetization-payroll",
  },
  {
    path: "/mid-year-bonus",
    redirect: "/payroll-benefits/mid-year-bonus",
  },
  {
    path: "/year-end-bonus",
    redirect: "/payroll-benefits/year-end-bonus",
  },
  {
    path: "/extra-bonus",
    redirect: "/payroll-benefits/extra-bonus",
  },
  {
    path: "/retirement-benefits",
    redirect: "/payroll-benefits/retirement-benefits",
  },

  // Payroll Reports routes
  {
    path: "/payroll-summary-report",
    name: "payroll-summary-report",
    component: PayrollSummary,
  },
  {
    path: "/payroll-summary-detailed-report",
    name: "payroll-summary-detailed-report",
    component: PayrollSummaryDetailed,
  },
  { path: "/payslip-report", name: "payslip-report", component: PayslipReport },
  {
    path: "/loyalty-award-report",
    name: "loyalty-award-report",
    component: LoyaltyAwardReport,
  },
  {
    path: "/payroll-communication-macco-report",
    name: "payroll-communication-macco-report",
    component: PayrollCommunicationMaccoReport,
  },
  {
    path: "/bank-remittance-report",
    name: "bank-remittance-report",
    component: BankRemittanceReport,
  },
  {
    path: "/philhealth-remittance-report",
    name: "philhealth-remittance-report",
    component: PhilhealthRemittanceReport,
  },
  {
    path: "/pag-ibig-contribution-report",
    name: "pag-ibig-contribution-report",
    component: PagIbigContributionReport,
  },
  {
    path: "/pag-ibig-loan-report",
    name: "pag-ibig-loan-report",
    component: PagIbigLoanReport,
  },
  {
    path: "/gsis-remittance-report",
    name: "gsis-remittance-report",
    component: GSISRemittanceReport,
  },
  {
    path: "/overtime-payment-report",
    name: "overtime-payment-report",
    component: OvertimePaymentReport,
  },
  {
    path: "/uniform-clothing-allowance-report",
    name: "uniform-clothing-allowance-report",
    component: UniformClothingAllowanceReport,
  },

  {
    path: "/hazard-pay-report",
    name: "hazard-pay-report",
    component: HazardPayReport,
  },

  {
    path: "/extra-bonus-report",
    name: "extra-bonus-report",
    component: ExtraBonusReport,
  },
  {
    path: "/rata-payroll-report",
    name: "rata-payroll-report",
    component: RataPayrollReport,
  },
  {
    path: "/monetization-payroll-report",
    name: "monetization-payroll-report",
    component: MonetizationPayrollReport,
  },
  {
    path: "/mid-year-bonus-report-hub",
    name: "mid-year-bonus-report-hub",
    component: MidYearBonusReportHub,
  },
  {
    path: "/mid-year-bonus-report",
    name: "mid-year-bonus-report",
    component: MidYearBonusReport,
  },
  {
    path: "/atm-mid-year-bonus-report",
    name: "atm-mid-year-bonus-report",
    component: AtmMidYearBonusReport,
  },
  {
    path: "/mid-year-individual-voucher-report",
    name: "mid-year-individual-voucher-report",
    component: MidYearIndividualVoucherReport,
  },
  {
    path: "/mid-year-voucher-report",
    name: "mid-year-voucher-report",
    component: MidYearVoucherReport,
  },
  {
    path: "/year-end-bonus-report",
    name: "year-end-bonus-report",
    component: YearEndBonusReport,
  },
  {
    path: "/subsistence-report",
    name: "subsistence-report",
    component: SubsistenceReport,
  },
  {
    path: "/landbank-text-report",
    name: "landbank-text-report",
    component: LandbankTextReport,
  },
  {
    path: "/atm-letter-landbank",
    name: "atm-letter-landbank",
    component: AtmLetterLandbank,
  },
  {
    path: "/bir-form-2305",
    name: "bir-form-2305",
    component: BIRForm2305,
  },
  {
    path: "/gsis-member-info",
    name: "gsis-member-info",
    component: GSISMemberInfo,
  },
  {
    path: "/philhealth-pmrf",
    name: "philhealth-pmrf",
    component: PhilHealthPMRF,
  },
  {
    path: "/pag-ibig-mdf",
    name: "pagibig-mdf",
    component: PagIbigMDF,
  },

  { path: "/:pathMatch(.*)*", redirect: "/" },
];

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
});

router.beforeEach(async (to, _from, next) => {
  if (to.name == null) {
    return next();
  }

  if (isEPortalSharedAuth()) {
    await bootstrapAuth();
  }

  if (!localStorage.getItem("auth_token")) {
    return next();
  }
  const u = await ensurePayrollAccessUser();
  if (!u) {
    if (to.name === "payroll") {
      return next();
    }
    return next({ name: "payroll" });
  }
  if (!canAccessPayrollRoute(to, u)) {
    return next({ name: "payroll" });
  }
  return next();
});

export default router;
