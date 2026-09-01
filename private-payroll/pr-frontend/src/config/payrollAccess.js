/**
 * Route names → menus.menu_key from API `payroll_menu_keys` (AccessRightsController).
 * `fallback` allows routes when user holds that menu_key (e.g. payroll_process).
 *
 * Private HRIS — public-sector features removed:
 *   GSIS, plantilla, COS payroll, BIR Form 2305, PMRF, MDF,
 *   loyalty award, RATA, hazard pay, monetization, uniform allowance,
 *   retirement benefits, subsistence, landbank, communication macco.
 */

export const PAYROLL_ROUTE_ACCESS = {
  payroll: { allow: true },

  // ── Core Setup ──────────────────────────────────────────────────────────
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

  // ── Payroll Execution ───────────────────────────────────────────────────
  "payroll-process": { keys: ["payroll_process"] },

  // ── 13th Month Pay ──────────────────────────────────────────────────────
  "thirteenth-month-pay": {
    keys: [
      "thirteenth_month_pay",
      "thirteenth_month",
      "13th_month_pay",
      "payroll_process",
    ],
  },

  // ── Final Pay ───────────────────────────────────────────────────────────
  "final-pay": {
    keys: ["final_pay", "final_pay_computation"],
    fallback: "payroll_process",
  },

  // ── Bonuses & Overtime ──────────────────────────────────────────────────
  "payroll-bonuses": { keys: ["payroll_bonuses", "payroll_bonus", "payroll_benefits", "payroll_benefit"] },

  "overtime-payment": {
    keys: [
      "overtime_payroll_process",
      "payroll_bonuses",
      "payroll_bonus",
      "payroll_benefits",
      "payroll_benefit",
    ],
  },
  "extra-bonus": {
    keys: ["payroll_bonuses", "payroll_bonus", "payroll_benefits", "payroll_benefit"],
    fallback: "payroll_process",
  },
  // Hidden from nav but kept for backward-compat — retain same access rules
  "mid-year-bonus": {
    keys: [
      "process_midyear",
      "process_mid_year",
      "payroll_bonuses",
      "payroll_bonus",
      "payroll_benefits",
      "payroll_benefit",
    ],
  },
  "year-end-bonus": {
    keys: [
      "process_yearend",
      "process_year_end",
      "payroll_bonuses",
      "payroll_bonus",
      "payroll_benefits",
      "payroll_benefit",
    ],
  },

  // ── Payroll Reports ─────────────────────────────────────────────────────
  "payroll-summary-report": { fallback: "payroll_process" },
  "payroll-summary-detailed-report": { fallback: "payroll_process" },
  "payslip-report": { fallback: "payroll_process" },
  "bank-remittance-report": { fallback: "payroll_process" },
  "philhealth-remittance-report": { fallback: "payroll_process" },
  "pag-ibig-contribution-report": { fallback: "payroll_process" },
  "pag-ibig-loan-report": { fallback: "payroll_process" },
  "overtime-payment-report": { fallback: "payroll_process" },
  "sss-contribution-report": { fallback: "payroll_process" },
  "mid-year-bonus-report": {
    keys: ["midyear_report"],
    fallback: "payroll_process",
  },
  "year-end-bonus-report": {
    keys: ["yearend_report", "process_yearend", "process_year_end"],
    fallback: "payroll_process",
  },
  "extra-bonus-report": { fallback: "payroll_process" },
};

// ── Nav groupings ───────────────────────────────────────────────────────────

/** Routes shown in the core payroll-setup nav block */
export const CORE_SETUP_NAV_NAMES = [
  "payroll-period",
  "payroll-item-schedule",
  "income-deduction",
  "hdmf-premium",
  "loan-application",
];

/** Routes shown in the payroll-execution nav block */
export const PAYROLL_EXECUTION_NAV_NAMES = [
  "payroll-process",
  "thirteenth-month-pay",
  "final-pay",
];

/** Legacy compatibility — maps old names to new groupings */
export const TIME_KEEPING_NAV_NAMES = CORE_SETUP_NAV_NAMES;
export const PAYROLL_CPM_NAV_NAMES = PAYROLL_EXECUTION_NAV_NAMES;

/** Bonus & Overtime child routes (visible ones only) */
export const BONUS_ROUTES = [
  {
    name: "overtime-payment",
    label: "Overtime Payment",
    path: "/payroll-bonuses/overtime-payment",
  },
  {
    name: "extra-bonus",
    label: "Other Bonuses",
    path: "/payroll-bonuses/extra-bonus",
  },
];

/** Legacy name kept for BenefitsIndexRedirect / PayrollBenefitsLayout compatibility */
export const BENEFIT_ROUTES = BONUS_ROUTES;
export const BENEFIT_ROUTE_NAMES = BONUS_ROUTES.map((r) => r.name);

/** All visible report routes */
export const REPORT_ROUTE_NAMES = [
  "payroll-summary-report",
  "payroll-summary-detailed-report",
  "payslip-report",
  "bank-remittance-report",
  "philhealth-remittance-report",
  "pag-ibig-contribution-report",
  "pag-ibig-loan-report",
  "sss-contribution-report",
  "overtime-payment-report",
  "mid-year-bonus-report",
  "year-end-bonus-report",
  "extra-bonus-report",
];

// ── Access helpers ───────────────────────────────────────────────────────────

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
  if (to.name === "payroll-bonuses-index") {
    return canSeeBonusesSection(user);
  }
  const spec = PAYROLL_ROUTE_ACCESS[to.name];
  if (!spec) return false;
  return payrollNavVisible(user, spec);
}

export function canSeeBonusesSection(user) {
  if (
    payrollNavVisible(user, {
      keys: [
        "payroll_bonuses",
        "payroll_bonus",
        "payroll_benefits",
        "payroll_benefit",
      ],
    })
  ) {
    return true;
  }
  return showAnyNamedRoutes(user, BENEFIT_ROUTE_NAMES);
}

/** Legacy alias */
export const canSeeBenefitsSection = canSeeBonusesSection;

export function showAnyNamedRoutes(user, names) {
  return names.some((n) => payrollNavVisible(user, PAYROLL_ROUTE_ACCESS[n]));
}
