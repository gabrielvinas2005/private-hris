/**
 * Route names → menus.menu_key from API `payroll_menu_keys` (AccessRightsController).
 * `fallback` allows routes when user holds that menu_key (e.g. payroll_process).
 */

export const PAYROLL_ROUTE_ACCESS = {
  payroll: { allow: true },

  "payroll-period": { keys: ["payroll_period", "pay_period"] },
  "payroll-item-schedule": {
    keys: [
      "payitem_schedule",
      "payroll_item_schedule",
      "payroll_items_schedule",
    ],
  },
  "income-deduction": {
    keys: [
      "payroll_income_deductions",
      "payroll_income_deduction",
      "income_deduction",
      "income_deductions",
    ],
  },
  "hdmf-premium": { keys: ["hdmf_premium", "hdmf-premium"] },
  "loan-application": { keys: ["loan_applications", "loan_application"] },
  "payroll-process": { keys: ["payroll_process"] },
  "cos-payroll": { keys: ["cos_payroll", "cos-payroll"] },
  "payroll-benefits": { keys: ["payroll_benefits", "payroll_benefit"] },

  "overtime-payment": {
    keys: ["overtime_payroll_process", "payroll_benefits", "payroll_benefit"],
  },
  "uniform-clothing-allowance": {
    keys: ["uniform_clothing", "payroll_benefits", "payroll_benefit"],
  },
  "payroll-communication-macco": {
    keys: ["payroll_benefits", "payroll_benefit"],
    fallback: "payroll_process",
  },
  "loyalty-award": {
    keys: ["loyalty_award", "payroll_benefits", "payroll_benefit"],
  },
  "rata-payroll": {
    keys: ["rata_payroll", "payroll_benefits", "payroll_benefit"],
  },
  "hazard-pay": {
    keys: ["payroll_benefits", "payroll_benefit"],
    fallback: "payroll_process",
  },
  "monetization-payroll": {
    keys: ["monetization_payroll", "payroll_benefits", "payroll_benefit"],
  },
  "mid-year-bonus": {
    keys: [
      "process_midyear",
      "process_mid_year",
      "payroll_benefits",
      "payroll_benefit",
    ],
  },
  "year-end-bonus": {
    keys: [
      "process_yearend",
      "process_year_end",
      "payroll_benefits",
      "payroll_benefit",
    ],
  },
  "extra-bonus": {
    keys: ["payroll_benefits", "payroll_benefit"],
    fallback: "payroll_process",
  },
  "retirement-benefits": {
    keys: ["payroll_benefits", "payroll_benefit"],
    fallback: "payroll_process",
  },

  "payroll-summary-report": { fallback: "payroll_process" },
  "payroll-summary-detailed-report": { fallback: "payroll_process" },
  "payslip-report": { fallback: "payroll_process" },
  "loyalty-award-report": { fallback: "payroll_process" },
  "payroll-communication-macco-report": { fallback: "payroll_process" },
  "bank-remittance-report": { fallback: "payroll_process" },
  "philhealth-remittance-report": { fallback: "payroll_process" },
  "pag-ibig-contribution-report": { fallback: "payroll_process" },
  "pag-ibig-loan-report": { fallback: "payroll_process" },
  "gsis-remittance-report": { fallback: "payroll_process" },
  "overtime-payment-report": { fallback: "payroll_process" },
  "uniform-clothing-allowance-report": {
    keys: [
      "uniform_clothing_report",
      "uniform_clothing",
      "payroll_benefits",
      "payroll_benefit",
    ],
    fallback: "payroll_process",
  },
  "hazard-pay-report": { fallback: "payroll_process" },
  "extra-bonus-report": { fallback: "payroll_process" },
  "rata-payroll-report": {
    keys: ["rata_payroll_report", "rata_payroll"],
    fallback: "payroll_process",
  },
  "monetization-payroll-report": {
    keys: ["monetization_payroll_report", "monetization_payroll"],
    fallback: "payroll_process",
  },
  "mid-year-bonus-report-hub": {
    keys: ["midyear_report"],
    fallback: "payroll_process",
  },
  "mid-year-bonus-report": {
    keys: ["midyear_report"],
    fallback: "payroll_process",
  },
  "atm-mid-year-bonus-report": {
    keys: ["midyear_report"],
    fallback: "payroll_process",
  },
  "mid-year-individual-voucher-report": {
    keys: ["midyear_report"],
    fallback: "payroll_process",
  },
  "mid-year-voucher-report": {
    keys: ["midyear_report"],
    fallback: "payroll_process",
  },
  "year-end-bonus-report": {
    keys: ["yearend_report", "process_yearend", "process_year_end"],
    fallback: "payroll_process",
  },
  "subsistence-report": { fallback: "payroll_process" },
  "landbank-text-report": { fallback: "payroll_process" },
  "atm-letter-landbank": { fallback: "payroll_process" },
  "bir-form-2305": { fallback: "payroll_process" },
  "gsis-member-info": { fallback: "payroll_process" },
  "philhealth-pmrf": { fallback: "payroll_process" },
  "pagibig-mdf": { fallback: "payroll_process" },
};

export const TIME_KEEPING_NAV_NAMES = [
  "payroll-period",
  "payroll-item-schedule",
  "income-deduction",
  "loan-application",
  "payroll-process",
];

export const PAYROLL_CPM_NAV_NAMES = ["hdmf-premium", "cos-payroll"];

export const BENEFIT_ROUTES = [
  {
    name: "overtime-payment",
    label: "Overtime Payment",
    path: "/payroll-benefits/overtime-payment",
  },
  {
    name: "uniform-clothing-allowance",
    label: "Uniform & Clothing Allowance",
    path: "/payroll-benefits/uniform-clothing-allowance",
  },
  {
    name: "payroll-communication-macco",
    label: "Payroll Communication Macco",
    path: "/payroll-benefits/payroll-communication-macco",
  },
  {
    name: "loyalty-award",
    label: "Loyalty Award",
    path: "/payroll-benefits/loyalty-award",
  },
  {
    name: "rata-payroll",
    label: "RATA Payroll",
    path: "/payroll-benefits/rata-payroll",
  },
  {
    name: "hazard-pay",
    label: "Hazard Pay",
    path: "/payroll-benefits/hazard-pay",
  },
  {
    name: "monetization-payroll",
    label: "Monetization Payroll",
    path: "/payroll-benefits/monetization-payroll",
  },
  {
    name: "mid-year-bonus",
    label: "Mid Year Bonus",
    path: "/payroll-benefits/mid-year-bonus",
  },
  {
    name: "year-end-bonus",
    label: "Year End Bonus",
    path: "/payroll-benefits/year-end-bonus",
  },
  {
    name: "extra-bonus",
    label: "Extra Bonus",
    path: "/payroll-benefits/extra-bonus",
  },
  {
    name: "retirement-benefits",
    label: "Retirement Benefits",
    path: "/payroll-benefits/retirement-benefits",
  },
];

export const BENEFIT_ROUTE_NAMES = BENEFIT_ROUTES.map((r) => r.name);

export const REPORT_ROUTE_NAMES = [
  "payroll-summary-report",
  "payroll-summary-detailed-report",
  "payslip-report",
  "loyalty-award-report",
  "payroll-communication-macco-report",
  "bank-remittance-report",
  "philhealth-remittance-report",
  "pag-ibig-contribution-report",
  "pag-ibig-loan-report",
  "gsis-remittance-report",
  "overtime-payment-report",
  "uniform-clothing-allowance-report",
  "hazard-pay-report",
  "extra-bonus-report",
  "rata-payroll-report",
  "monetization-payroll-report",
  "mid-year-bonus-report-hub",
  "mid-year-bonus-report",
  "atm-mid-year-bonus-report",
  "mid-year-individual-voucher-report",
  "mid-year-voucher-report",
  "year-end-bonus-report",
  "subsistence-report",
  "landbank-text-report",
  "atm-letter-landbank",
  "bir-form-2305",
  "gsis-member-info",
  "philhealth-pmrf",
  "pagibig-mdf",
];

export function isPayrollAdmin(user) {
  if (!user) return false;
  const a = user.is_admin;
  if (a === true || a === 1) return true;
  if (a === false || a === 0) return false;
  if (a == null) return false;
  if (typeof a === "string") {
    const s = a.trim().toLowerCase();
    if (s === "1" || s === "true") return true;
    if (s === "0" || s === "false" || s === "") return false;
  }
  return false;
}

function normalizePayrollMenuKeys(user) {
  const k = user?.payroll_menu_keys;
  const raw = Array.isArray(k)
    ? k
    : k && typeof k === "object"
      ? Object.values(k)
      : [];
  return raw
    .filter(Boolean)
    .map((v) => String(v).trim().toLowerCase())
    .filter(Boolean);
}

function expandKeyVariants(value) {
  const key = String(value || "")
    .trim()
    .toLowerCase();
  if (!key) return [];
  const underscore = key.replace(/-/g, "_");
  const hyphen = key.replace(/_/g, "-");
  return Array.from(new Set([key, underscore, hyphen]));
}

function hasAnyMenuKey(userKeys, allowedKeys) {
  for (const k of allowedKeys || []) {
    for (const variant of expandKeyVariants(k)) {
      if (userKeys.has(variant)) return true;
    }
  }
  return false;
}

export function payrollNavVisible(user, spec) {
  if (!spec) return false;
  if (spec.allow) return true;
  if (!user) return false;
  if (isPayrollAdmin(user)) return true;
  const keys = new Set(normalizePayrollMenuKeys(user));
  if (hasAnyMenuKey(keys, spec.keys)) return true;
  if (spec.fallback && hasAnyMenuKey(keys, [spec.fallback])) return true;
  return false;
}

export function canAccessPayrollRoute(to, user) {
  if (!to?.name) return true;
  if (to.name === "payroll-benefits-index") {
    return canSeeBenefitsSection(user);
  }
  const spec = PAYROLL_ROUTE_ACCESS[to.name];
  if (!spec) return false;
  return payrollNavVisible(user, spec);
}

export function canSeeBenefitsSection(user) {
  if (
    payrollNavVisible(user, { keys: ["payroll_benefits", "payroll_benefit"] })
  ) {
    return true;
  }
  return showAnyNamedRoutes(user, BENEFIT_ROUTE_NAMES);
}

export function showAnyNamedRoutes(user, names) {
  return names.some((n) => payrollNavVisible(user, PAYROLL_ROUTE_ACCESS[n]));
}
