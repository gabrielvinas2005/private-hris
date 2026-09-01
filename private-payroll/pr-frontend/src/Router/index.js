import { createRouter, createWebHistory } from "vue-router";
import {
  bootstrapAuth,
  ensurePayrollAccessUser,
  isEPortalSharedAuth,
} from "../Composables/useAuth";
import { canAccessPayrollRoute } from "../config/payrollAccess";

// Core Module Views
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

// Bonuses & Overtime Layout
const PayrollBonusesLayout = () =>
  import("../Views/Payroll_Benefits/PayrollBenefitsLayout.vue");
const BonusesIndexRedirect = () =>
  import("../Views/Payroll_Benefits/BenefitsIndexRedirect.vue");
const OvertimePayment = () =>
  import("../Views/Payroll_Benefits/Overtime_Payment/Overtime_Payment.vue");

// Bonus Views (kept, hidden from nav per user request)
const MidYearBonus = () =>
  import("../Views/Payroll_Benefits/MidYear_Bonus/MidYearBonus.vue");
const YearEndBonus = () =>
  import("../Views/Payroll_Benefits/YearEnd_Bonus/YearEndBonus.vue");
const ExtraBonus = () =>
  import("../Views/Payroll_Benefits/Extra_Bonus/Extra_Bonus.vue");

// NEW: 13th Month Pay
const ThirteenthMonthPay = () =>
  import("../Views/Thirteenth_Month/Thirteenth_Month.vue");

// NEW: Final Pay
const FinalPay = () => import("../Views/Final_Pay/Final_Pay.vue");

// Payroll Reports
const PayrollSummary = () =>
  import("../Views/Payroll_Reports/rptPayrollSummary.vue");
const PayrollSummaryDetailed = () =>
  import("../Views/Payroll_Reports/rptPayrollSummaryDetailed.vue");
const PayslipReport = () => import("../Views/Payroll_Reports/rptPayslip.vue");
const BankRemittanceReport = () =>
  import("../Views/Payroll_Reports/rptBankRemittance.vue");
const PhilhealthRemittanceReport = () =>
  import("../Views/Payroll_Reports/rptPhilhealthRemittance.vue");
const PagIbigContributionReport = () =>
  import("../Views/Payroll_Reports/rptPagIbigContribution.vue");
const PagIbigLoanReport = () =>
  import("../Views/Payroll_Reports/rptPagIbigLoan.vue");
const OvertimePaymentReport = () =>
  import("../Views/Payroll_Reports/rptOvertimePayment.vue");
const MidYearBonusReport = () =>
  import("../Views/Payroll_Reports/rptMidYearBonus.vue");
const YearEndBonusReport = () =>
  import("../Views/Payroll_Reports/rptYearEndBonus.vue");
const ExtraBonusReport = () =>
  import("../Views/Payroll_Reports/rptExtraBonusPayroll.vue");

// NEW: SSS Contribution Report
const SSSContributionReport = () =>
  import("../Views/Payroll_Reports/rptSSSContribution.vue");

const routes = [
  {
    path: "/",
    name: "payroll",
    component: PayrollModule,
    meta: { title: "Payroll Module" },
  },
  { path: "/tk", redirect: "/" },

  // ── Core Setup ──────────────────────────────────────────────────────────
  {
    path: "/payroll-period",
    name: "payroll-period",
    component: PayrollPeriod,
  },
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
  {
    path: "/hdmf-premium",
    name: "hdmf-premium",
    component: HDMFPremium,
  },
  {
    path: "/loan-application",
    name: "loan-application",
    component: LoanApplication,
  },

  // ── Payroll Execution ────────────────────────────────────────────────────
  {
    path: "/payroll-process",
    name: "payroll-process",
    component: PayrollProcess,
  },

  // ── 13th Month Pay ───────────────────────────────────────────────────────
  {
    path: "/thirteenth-month-pay",
    name: "thirteenth-month-pay",
    component: ThirteenthMonthPay,
  },

  // ── Final Pay ────────────────────────────────────────────────────────────
  {
    path: "/final-pay",
    name: "final-pay",
    component: FinalPay,
  },

  // ── Bonuses & Overtime ───────────────────────────────────────────────────
  {
    path: "/payroll-bonuses",
    component: PayrollBonusesLayout,
    children: [
      {
        path: "",
        name: "payroll-bonuses-index",
        component: BonusesIndexRedirect,
      },
      {
        path: "overtime-payment",
        name: "overtime-payment",
        component: OvertimePayment,
      },
      {
        path: "extra-bonus",
        name: "extra-bonus",
        component: ExtraBonus,
      },
      // Hidden but kept for backward compatibility
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
    ],
  },

  // Legacy direct route redirects
  { path: "/overtime-payment", redirect: "/payroll-bonuses/overtime-payment" },
  { path: "/extra-bonus", redirect: "/payroll-bonuses/extra-bonus" },
  { path: "/mid-year-bonus", redirect: "/payroll-bonuses/mid-year-bonus" },
  { path: "/year-end-bonus", redirect: "/payroll-bonuses/year-end-bonus" },
  // Old payroll-benefits routes → redirect to payroll-bonuses
  { path: "/payroll-benefits", redirect: "/payroll-bonuses" },
  {
    path: "/payroll-benefits/overtime-payment",
    redirect: "/payroll-bonuses/overtime-payment",
  },
  {
    path: "/payroll-benefits/extra-bonus",
    redirect: "/payroll-bonuses/extra-bonus",
  },
  {
    path: "/payroll-benefits/mid-year-bonus",
    redirect: "/payroll-bonuses/mid-year-bonus",
  },
  {
    path: "/payroll-benefits/year-end-bonus",
    redirect: "/payroll-bonuses/year-end-bonus",
  },

  // ── Payroll Reports ──────────────────────────────────────────────────────
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
  {
    path: "/payslip-report",
    name: "payslip-report",
    component: PayslipReport,
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
    path: "/overtime-payment-report",
    name: "overtime-payment-report",
    component: OvertimePaymentReport,
  },
  {
    path: "/sss-contribution-report",
    name: "sss-contribution-report",
    component: SSSContributionReport,
  },
  {
    path: "/mid-year-bonus-report",
    name: "mid-year-bonus-report",
    component: MidYearBonusReport,
  },
  {
    path: "/year-end-bonus-report",
    name: "year-end-bonus-report",
    component: YearEndBonusReport,
  },
  {
    path: "/extra-bonus-report",
    name: "extra-bonus-report",
    component: ExtraBonusReport,
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
