import axios from "axios";

/** Same-origin /api in Docker/LAN; localhost only for Vite dev. */
function resolveApiBaseUrl() {
  const envUrl = import.meta?.env?.VITE_API_URL?.trim();
  if (envUrl) {
    if (envUrl.startsWith("/")) {
      return envUrl.replace(/\/$/, "") || "/api";
    }
    if (import.meta.env.DEV) {
      return envUrl.replace(/\/$/, "");
    }
    // Production builds must not call localhost or internal Docker hostnames.
    return "/api";
  }
  if (import.meta.env.DEV) {
    return "http://localhost:8003/api";
  }
  return "/api";
}

const baseURL = resolveApiBaseUrl();

const api = axios.create({
  baseURL,
  withCredentials: true,
});

let suppress401Clear = false;

/** Skip clearing auth_token on 401 while E-Portal SSO exchange is in progress. */
export function setSuppress401Clear(value) {
  suppress401Clear = Boolean(value);
}

api.interceptors.request.use((config) => {
  const tokenData = localStorage.getItem("auth_token");
  if (tokenData) {
    try {
      const parsed = JSON.parse(tokenData);
      const token = parsed.token;
      if (token) {
        config.headers.Authorization = `Bearer ${token}`;
      }
    } catch (error) {
      // If parsing fails, treat as old format token
      config.headers.Authorization = `Bearer ${tokenData}`;
    }
  }
  return config;
});

// Response interceptor to handle 401 errors and blob error responses
api.interceptors.response.use(
  (response) => response,
  async (error) => {
    if (error.response?.status === 401 && !suppress401Clear) {
      localStorage.removeItem("auth_token");
      window.dispatchEvent(new CustomEvent("token-expired"));
    }

    if (
      error.config?.responseType === "blob" &&
      error.response?.data instanceof Blob
    ) {
      try {
        const text = await error.response.data.text();
        const json = JSON.parse(text);
        error.response.data = json;
        if (error.response.headers) {
          error.response.headers["content-type"] = "application/json";
        }
      } catch (e) {}
    }

    return Promise.reject(error);
  },
);

export const authApi = {
  login: (credentials) => api.post("/login", credentials),

  sharedAuth: (payload) => api.post("/shared-auth", payload),

  sharedAuthQuery: (params) => api.get("/shared-auth", { params }),

  logout: () => api.post("/logout"),

  getCurrentUser: () => api.get("/user"),

  refreshToken: () => api.post("/refresh"),
};

export const payrollItemScheduleApi = {
  getDropdownData: () => api.get("/payroll-item-schedules"),

  saveSchedule: (data) => api.post("/payroll-item-schedules", data),

  getIncomeItems: (payrollIntervalId, payrollPeriodId, employmentTypeId) =>
    api.get(
      `/payroll-item-schedules/${payrollIntervalId}/${payrollPeriodId}/${employmentTypeId}/incomes`,
    ),

  getDeductionItems: (payrollIntervalId, payrollPeriodId, employmentTypeId) =>
    api.get(
      `/payroll-item-schedules/${payrollIntervalId}/${payrollPeriodId}/${employmentTypeId}/deductions`,
    ),

  getScheduleHeader: (payrollIntervalId, payrollPeriodId, employmentTypeId) =>
    api.get(
      `/payroll-item-schedules/${payrollIntervalId}/${payrollPeriodId}/${employmentTypeId}/header`,
    ),

  deleteSchedule: (id) => api.delete(`/payroll-item-schedules/${id}`),
};

export const payrollIncomeDeductionApi = {
  getBootstrap: () => api.get("/payroll-income-and-deductions"),

  getIncomeList: (payrollPeriodId, employmentTypeId) =>
    api.get(
      `/payroll-income-and-deductions/${payrollPeriodId}/${employmentTypeId}/incomes`,
    ),

  getDeductionList: (payrollPeriodId, employmentTypeId) =>
    api.get(
      `/payroll-income-and-deductions/${payrollPeriodId}/${employmentTypeId}/deductions`,
    ),

  getEmployeeIncome: (payrollPeriodId, employmentTypeId, incomeId) =>
    api.get(
      `/payroll-income-and-deductions/${payrollPeriodId}/${employmentTypeId}/${incomeId}/employee-income`,
    ),

  getEmployeeIncomeMerged: (
    payrollPeriodId,
    secondaryPayrollPeriodId,
    employmentTypeId,
    incomeId,
  ) =>
    api.get(
      `/payroll-income-and-deductions/${payrollPeriodId}/${employmentTypeId}/${incomeId}/employee-income-merged/${secondaryPayrollPeriodId}`,
    ),

  getEmployeePreviousIncome: (payrollPeriodId, employmentTypeId, incomeId) =>
    api.get(
      `/payroll-income-and-deductions/${payrollPeriodId}/${employmentTypeId}/${incomeId}/employee-previous-income`,
    ),

  getEmployeeDeduction: (payrollPeriodId, employmentTypeId, deductionId) =>
    api.get(
      `/payroll-income-and-deductions/${payrollPeriodId}/${employmentTypeId}/${deductionId}/employee-deduction`,
    ),

  getEmployeePreviousDeduction: (
    payrollPeriodId,
    employmentTypeId,
    deductionId,
  ) =>
    api.get(
      `/payroll-income-and-deductions/${payrollPeriodId}/${employmentTypeId}/${deductionId}/employee-previous-deduction`,
    ),

  getEmployeeList: (payrollPeriodId, employmentTypeId, typeId, itemId) =>
    api.get(
      `/payroll-income-and-deductions/${payrollPeriodId}/${employmentTypeId}/${typeId}/${itemId}/employee-list`,
    ),

  // Save
  saveIncome: (payload) => api.post("/payroll-income", payload),
  saveDeduction: (payload) => api.post("/payroll-deduction", payload),
};

// HDMF Premium APIs
export const hdmfPremiumApi = {
  // Get payroll periods for HDMF premium
  getPayrollPeriods: () => api.get("/pagibig-payroll"),

  // Load employees for specific payroll period
  loadEmployees: (payrollPeriodId) =>
    api.get(`/pagibig-payroll/${payrollPeriodId}/employees`),

  // Save/update HDMF premium data
  savePremium: (data) => api.post("/pagibig-payroll/employees", data),

  // Validate amount before saving
  validateAmount: (employeeId, amount, payrollPeriodId) =>
    api.get(
      `/pagibig-payroll/${employeeId}/${amount}/${payrollPeriodId}/validate`,
    ),
};

// Loan Application APIs
export const loanApplicationApi = {
  // Load list of loan applications and category filters
  list: () => api.get("/loan-applications"),

  // Load form bootstrap data (employees, deductions, existing when editing)
  add: (id = 0) => api.get(`/loan-applications/${id}/add`),

  // Create or update a loan application
  save: (payload, id = 0) => api.post(`/loan-applications/${id}`, payload),

  // Reconstruct an existing loan application (deactivate old, create new)
  reconstructForm: (id) => api.get(`/loan-applications/${id}/reconstruct`),
  reconstructSave: (id, payload) =>
    api.post(`/loan-applications/${id}/reconstruct`, payload),

  // Basic validation endpoint (controller returns 0/1)
  validate: (id) => api.get(`/loan-applications/${id}/validation`),
};

// COS Payroll (Contract of Service, non-DTR based)
export const cosPayrollApi = {
  // List COS payroll periods (Contract of Service employment type)
  getPeriods: () => api.get("/cos-payroll-periods"),

  // Optional helper to inspect raw COS non-DTR entries
  getEntries: (params = {}) => api.get("/cos-payroll", { params }),

  // Get COS employees/non-DTR entries for a specific payroll period
  getEmployeesByPeriod: (payrollPeriodId) =>
    api.get(`/cos-payroll/${payrollPeriodId}/employees`),

  // Process COS payroll: load employees/entries and compute contributions, gross, net pay
  processPeriod: (payrollPeriodId) =>
    api.post(`/cos-payroll/${payrollPeriodId}/process`),

  // Post / Unpost COS payroll period (independent of main payroll process)
  postPeriod: (payrollPeriodId) =>
    api.post(`/cos-payroll/${payrollPeriodId}/post`),
  unpostPeriod: (payrollPeriodId) =>
    api.post(`/cos-payroll/${payrollPeriodId}/unpost`),

  // Download attachment file
  downloadAttachment: (attachmentId) =>
    api.get(`/cos-payroll/attachments/${attachmentId}/download`, {
      responseType: "blob",
    }),

  // Tax adjustment
  getTaxEmployees: (payrollPeriodId) =>
    api.get(`/cos-payroll/${payrollPeriodId}/tax-employees`),
  adjustTax: (payrollPeriodId, data) =>
    api.post(`/cos-payroll/${payrollPeriodId}/adjust-tax`, data),
};

// Payroll Process APIs
export const payrollProcessApi = {
  // Get all payroll periods for processing
  getPayrollPeriods: () => api.get("/payroll-process"),

  // Process payroll for a specific period
  processPayroll: (payrollPeriodId, options = {}) =>
    api.post(`/payroll-process/${payrollPeriodId}`, options),

  // Get payroll summary for a specific period
  getPayrollSummary: (payrollPeriodId, options = {}) => {
    const params = { _: Date.now() };
    if (options.refresh) params.refresh = 1;
    if (options.cacheVersion != null) params.cv = options.cacheVersion;
    return api.get(`/payroll-process/${payrollPeriodId}/summary`, {
      params,
      headers: {
        "Cache-Control": "no-cache, no-store",
        Pragma: "no-cache",
      },
    });
  },

  // Get employee payroll breakdown
  getEmployeeBreakdown: (payrollPeriodId, employeeId, options = {}) => {
    const params = { _: Date.now() };
    if (options.refresh) params.refresh = 1;
    if (options.cacheVersion != null) params.cv = options.cacheVersion;
    return api.get(
      `/payroll-process/${payrollPeriodId}/employee/${employeeId}/breakdown`,
      {
        params,
        headers: {
          "Cache-Control": "no-cache, no-store",
          Pragma: "no-cache",
        },
      },
    );
  },

  // Post/Unpost payroll
  togglePayrollPosting: (payrollPeriodId, typeId) =>
    api.post(`/payroll-process/${payrollPeriodId}/${typeId}/posting`),

  generatePayrollReport: (payrollPeriodId, params = {}) =>
    api.get(`/payroll-process/${payrollPeriodId}/print-basic`, {
      responseType: "blob",
      params,
    }),

  // Generate tabulated payroll report
  generateTabulatedReport: (payrollPeriodId) =>
    api.get(`/payroll-process/${payrollPeriodId}/print`, {
      responseType: "blob",
    }),

  // Generate ORS (Obligation Request) report
  generateORSReport: (payrollPeriodId, params = {}) =>
    api.get(`/ors-payroll-report/${payrollPeriodId}`, {
      responseType: "blob",
      params,
    }),

  // Generate DV (Disbursement Voucher) report
  generateDVReport: (payrollPeriodId, params = {}) =>
    api.get(`/dv-payroll-report/${payrollPeriodId}`, {
      responseType: "blob",
      params,
    }),

  // Get payroll summary data for reports
  getPayrollSummaryData: () => api.get("/payroll-summary"),

  // Get payroll summary detail data for reports
  getPayrollSummaryDetailData: () => api.get("/payroll-summary-detail"),

  // Generate general payroll report
  generateGeneralPayrollReport: (requestData) =>
    api.get("/payroll-summary/print", {
      params: requestData,
      responseType: "blob",
    }),

  // Generate general payroll report DOCX
  generateGeneralPayrollReportDocx: (requestData) =>
    api.get("/payroll-summary/print-docx", {
      params: requestData,
      responseType: "blob",
    }),

  // Generate general payroll report Excel
  generateGeneralPayrollReportExcel: (requestData) =>
    api.get("/payroll-summary/print-excel", {
      params: requestData,
      responseType: "blob",
    }),

  // Generate detailed payroll report
  generateDetailedPayrollReport: (requestData) =>
    api.get("/payroll-summary-with-details/print", {
      params: requestData,
      responseType: "blob",
    }),

  // Adjust tax amounts for employees
  adjustTaxAmounts: (payrollPeriodId, adjustmentData) =>
    api.post(`/tax-amount-adjustment/${payrollPeriodId}`, adjustmentData),

  // Salary adjustments per employee for a payroll period
  getSalaryAdjustments: (payrollPeriodId) =>
    api.get(`/payroll-process/${payrollPeriodId}/salary-adjustments`),
  saveSalaryAdjustments: (payrollPeriodId, payload) =>
    api.post(`/payroll-process/${payrollPeriodId}/salary-adjustments`, payload),

  // Get payroll periods by interval
  getPayrollPeriodsByInterval: (intervalId) =>
    api.get(`/global/payroll-period/${intervalId}`),

  // Get posted payroll periods by interval
  getPostedPayrollPeriodsByInterval: (intervalId) =>
    api.get(`/global/payroll-period-posted/${intervalId}`),
};

// Overtime Payroll APIs
export const overtimePayrollApi = {
  // Get overtime payroll list (calls load method)
  getOvertimePayrollList: () => api.get("/overtime-payroll"),

  // Get overtime payroll form data (for add/edit)
  getOvertimePayrollFormData: (id = 0) =>
    api.get(`/overtime-payroll/${id}/add`),

  // Create/Update overtime payroll
  saveOvertimePayroll: (id, data) => api.post(`/overtime-payroll/${id}`, data),

  // Add employees to overtime payroll
  addEmployeesToOvertimePayroll: (id, employeeData) =>
    api.post(`/overtime-payroll/${id}/employees`, employeeData),

  // Remove employee from overtime payroll
  removeEmployeeFromOvertimePayroll: (id) =>
    api.delete(`/overtime-payroll/${id}/employees`),

  // Delete overtime payroll header (and its details on backend)
  deleteOvertimePayroll: (id) => api.delete(`/overtime-payroll/${id}`),

  // Post/Unpost overtime payroll
  processOvertimePayroll: (id, typeId) =>
    api.post(`/overtime-payroll/${id}/${typeId}/posting`),

  // Get overtime payroll process data (periods and intervals)
  getOvertimePayrollProcessData: () => api.get("/overtime-payroll/process"),

  // Generate overtime payroll PDF report
  generateOvertimePayrollReport: (requestData) =>
    api.get("/overtime-payroll/print", {
      params: requestData,
      responseType: "blob",
    }),

  // Generate ORS (Obligation Request) for overtime payroll by payroll period id
  generateORSReport: (payrollPeriodId) =>
    api.get(`/ors-overtime-report/${payrollPeriodId}`, {
      responseType: "blob",
    }),

  // Generate DV (Disbursement Voucher) for overtime payroll by payroll period id
  generateDVReport: (payrollPeriodId) =>
    api.get(`/dv-overtime-report/${payrollPeriodId}`, { responseType: "blob" }),
};

// Payslip Report APIs
export const payslipReportApi = {
  // Load initial data for payslip report (intervals, departments, employees, periods)
  getPayslipReportData: () => api.get("/payment-slip"),

  // Generate payslip PDF report
  printPayslipReport: (requestData) =>
    api.post("/payment-slip/print", requestData, {
      responseType: "blob",
    }),

  // Get employee-bound payroll periods for selected interval
  getEmployeePeriods: (employeeId, intervalId) =>
    api.get(`/payment-slip/${employeeId}/${intervalId}/periods`),
};

// Generic signatory / employee-options APIs
export const signatoryApi = {
  // Reuse ATM Letter endpoint which returns employee_options (id, name, position)
  getEmployeeOptions: () => api.get("/atm-letter-landbank"),
};

// Uniform Clothing Allowance APIs
export const uniformClothingAllowanceApi = {
  // Get uniform clothing allowance list
  getUniformClothingAllowanceList: () => api.get("/uniform-clothing"),

  // Get uniform clothing allowance form data (for add/edit)
  getUniformClothingAllowanceFormData: (id = 0, options = {}) => {
    const params = {};
    if (options.branchId) params.branch_id = options.branchId;
    if (options.divisionId) params.division_id = options.divisionId;
    if (options.departmentId) params.department_id = options.departmentId;
    return api.get(`/uniform-clothing/${id}/add`, { params });
  },

  // Create/Update uniform clothing allowance
  saveUniformClothingAllowance: (id, data) =>
    api.post(`/uniform-clothing/${id}`, data),

  // Add employees to uniform clothing allowance
  addEmployeesToUniformClothingAllowance: (id, employeeData) =>
    api.post(`/uniform-clothing/${id}/employees`, employeeData),

  // Remove employee from uniform clothing allowance
  removeEmployeeFromUniformClothingAllowance: (id) =>
    api.delete(`/uniform-clothing/${id}/details`),

  deleteUniformClothingAllowance: (id) => api.delete(`/uniform-clothing/${id}`),

  // Post uniform clothing allowance
  postUniformClothingAllowance: (id) =>
    api.post(`/uniform-clothing/${id}/post`),

  // Unpost uniform clothing allowance
  unpostUniformClothingAllowance: (id) =>
    api.get(`/uniform-clothing/${id}/unpost`),

  // Get uniform clothing allowance report data
  getUniformClothingAllowanceReportData: () =>
    api.get("/uniform-clothing-allowance-report"),

  // Generate uniform clothing allowance PDF report
  generateUniformClothingAllowanceReport: (requestData) =>
    api.post("/uniform-clothing-allowance-report/print", requestData, {
      responseType: "blob",
    }),

  // Generate ORS for clothing allowance (by header id)
  generateORSReport: (id) =>
    api.get(`/ors-clothing-report/${id}`, { responseType: "blob" }),

  // Generate DV for clothing allowance (by header id)
  generateDVReport: (id) =>
    api.get(`/dv-clothing-report/${id}`, { responseType: "blob" }),
};

// Loyalty Award APIs
export const loyaltyAwardApi = {
  // Get loyalty award list
  getLoyaltyAwardList: () => api.get("/loyalty-awards"),

  // Get loyalty award form data (for add/edit)
  getLoyaltyAwardFormData: (id = 0) => api.get(`/loyalty-awards/${id}/add`),

  // Create/Update loyalty award
  saveLoyaltyAward: (id, data) => api.post(`/loyalty-awards/${id}`, data),

  // Post loyalty award
  postLoyaltyAward: (id) => api.post(`/loyalty-awards/${id}/post`),

  // Unpost loyalty award
  unpostLoyaltyAward: (id) => api.get(`/loyalty-awards/${id}/unpost`),

  // Delete loyalty award
  deleteLoyaltyAward: (id) => api.post(`/loyalty-awards/${id}/delete`),

  // Get loyalty award employees for specific month/branch/year
  getLoyaltyAwardEmployees: (monthId, branchId, year, payrollId = 0) =>
    api.get(`/global/loyalty-award-employee/${monthId}/${branchId}/${year}`, {
      params: payrollId ? { payroll_id: payrollId } : {},
    }),

  // Get loyalty award report data
  getLoyaltyAwardReportData: () => api.get("/loyalty-award-report"),

  // Generate loyalty award PDF report
  generateLoyaltyAwardReport: (requestData) =>
    api.post("/loyalty-award-report/print", requestData, {
      responseType: "blob",
    }),

  // Generate ORS for loyalty award (by header id)
  generateORSReport: (id) =>
    api.get(`/ors-loyalty-report/${id}`, { responseType: "blob" }),

  // Generate DV for loyalty award (by header id)
  generateDVReport: (id) =>
    api.get(`/dv-loyalty-report/${id}`, { responseType: "blob" }),

  // Get loyalty award signatories by branch
  getLoyaltyAwardSignatories: (branchId) =>
    api.get(`/global/loyalty-award-signatory/${branchId}`),
};

// RATA Payroll APIs
export const rataPayrollApi = {
  // Get RATA positions setup
  getRATAPositions: () => api.get("/rata-positions"),

  // Save/Update RATA positions
  saveRATAPositions: (data) => api.post("/rata-positions", data),

  // Get RATA table data
  getRATATable: () => api.get("/rata-table"),

  // Save/Update RATA table
  saveRATATable: (data) => api.post("/rata-table", data),

  // Delete RATA table record
  deleteRATATable: (id) => api.get(`/rata-table/${id}/delete`),

  // Get RATA payroll list
  getRATAPayrollList: () => api.get("/rata-payroll"),

  // Get RATA payroll form data (for add/edit)
  getRATAPayrollFormData: (id = 0) => api.get(`/rata-payroll/${id}/add`),

  // Create/Update RATA payroll
  saveRATAPayroll: (id, data) => api.post(`/rata-payroll/${id}`, data),

  // Add employees to RATA payroll
  addEmployeesToRATAPayroll: (id, employeeData) =>
    api.post(`/rata-payroll/${id}/employees`, employeeData),

  // Update RATA payroll employees
  updateRATAPayrollEmployees: (id, employeeData) =>
    api.post(`/rata-payroll/${id}/update`, employeeData),

  // Remove employee from RATA payroll
  removeEmployeeFromRATAPayroll: (id) =>
    api.get(`/rata-payroll/${id}/employees/delete`),

  // Delete RATA payroll (header and details)
  deleteRATAPayroll: (id) => api.get(`/rata-payroll/${id}/delete`),

  // Post/Unpost RATA payroll
  processRATAPayroll: (id, typeId) =>
    api.post(`/rata-payroll/${id}/${typeId}/process`),

  // Get RATA report data
  getRATAReportData: () => api.get("/rata-payroll-report"),

  // Generate RATA payroll PDF report
  generateRATAPayrollReport: (requestData) =>
    api.post("/rata-payroll/print", requestData, {
      responseType: "blob",
    }),

  // Generate ORS for RATA payroll (by header id)
  generateORSReport: (id) =>
    api.get(`/ors-rata-report/${id}`, { responseType: "blob" }),

  // Generate DV for RATA payroll (by header id)
  generateDVReport: (id) =>
    api.get(`/dv-rata-report/${id}`, { responseType: "blob" }),
};

// Hazard Pay APIs
export const hazardPayApi = {
  // Get hazard pay setup table
  getHazardPaySetup: () => api.get("/hazard-pay"),

  // Save/Update hazard pay setup
  saveHazardPaySetup: (data) => api.post("/hazard-pay", data),

  // Delete hazard pay setup record
  deleteHazardPaySetup: (id) => api.get(`/hazard-pay/${id}/delete`),

  // Get hazard pay list
  getHazardPayList: () => api.get("/hazard-pay/list"),

  // Get hazard pay form data (for add/edit)
  getHazardPayFormData: (id = 0, params = {}) =>
    api.get(`/hazard-pay/${id}/add`, { params }),

  // Create/Update hazard pay
  saveHazardPay: (id, data) => api.post(`/hazard-pay/${id}`, data),

  // Add employees to hazard pay
  addEmployeesToHazardPay: (id, employeeData) =>
    api.post(`/hazard-pay/${id}/employees`, employeeData),

  // Remove employee from hazard pay
  removeEmployeeFromHazardPay: (id) =>
    api.get(`/hazard-pay/${id}/employees/delete`),

  // Post/Unpost hazard pay
  processHazardPay: (id, typeId) =>
    api.post(`/hazard-pay/${id}/${typeId}/process`),

  // Delete hazard pay header (and all related employees)
  deleteHazardPayHeader: (id) => api.delete(`/hazard-pay/${id}/header`),

  // Get hazard pay report data
  getHazardPayReportData: () => api.get("/hazard-pay-report"),

  // Generate hazard pay PDF report
  generateHazardPayReport: (requestData) =>
    api.get("/hazard-pay/print", {
      params: requestData,
      responseType: "blob",
    }),
};

// Employees directory (for selection modals, etc.)
export const employeeApi = {
  // params may include: search, department_id, page, per_page
  list: (params) => api.get("/employees", { params }),
};

// Monetization Payroll APIs
export const monetizationPayrollApi = {
  // Get monetization payroll list
  getMonetizationPayrollList: () => api.get("/monetization-payroll"),

  // Get monetization payroll form data (for add/edit)
  getMonetizationPayrollFormData: (id = 0) =>
    api.get(`/monetization-payroll/${id}/add`),

  // Create/Update monetization payroll header
  saveMonetizationPayroll: (id, data) =>
    api.post(`/monetization-payroll/${id}`, data),

  // Add employees to monetization payroll
  addEmployeesToMonetizationPayroll: (id, employeeData) =>
    api.post(`/monetization-payroll/${id}/employees`, employeeData),

  // Remove employee from monetization payroll
  removeEmployeeFromMonetizationPayroll: (id) =>
    api.delete(`/monetization-payroll/${id}/employees`),

  // Delete monetization payroll header (and related data)
  deleteMonetizationPayroll: (id) => api.delete(`/monetization-payroll/${id}`),

  // Post/Unpost monetization payroll
  processMonetizationPayroll: (id, typeId) =>
    api.post(`/monetization-payroll/${id}/${typeId}/process`),

  // Get monetization payroll report data (signatories, etc.)
  getMonetizationPayrollReportData: () =>
    api.get("/monetization-payroll/report"),

  // Generate monetization payroll PDF report
  generateMonetizationPayrollReport: (requestData) =>
    api.get("/monetization-payroll/print", {
      params: requestData,
      responseType: "blob",
    }),

  // Generate ORS for monetization payroll (by header id)
  generateORSReport: (id) =>
    api.get(`/ors-monetization-report/${id}`, { responseType: "blob" }),

  // Generate DV for monetization payroll (by header id)
  generateDVReport: (id) =>
    api.get(`/dv-monetization-report/${id}`, { responseType: "blob" }),

  // Get monetization payroll signatories by branch (using generic signatory endpoint)
  getMonetizationPayrollSignatories: (branchId) =>
    api.get(`/global/monetization-payroll-signatory/${branchId}`),
};

// Mid Year Bonus APIs
export const midYearBonusApi = {
  // Get mid-year bonus data
  getMidYearBonusData: () => api.get("/process-midyear"),

  // Get mid-year bonus records filtered by department
  getMidYearBonusRecords: (params) => api.get("/midyear-records", { params }),

  // Update a single mid-year bonus record (bonus_amount)
  updateMidYearBonusRecord: (id, data) =>
    api.patch(`/midyear-records/${id}`, data),

  // Delete a single mid-year bonus record
  deleteMidYearBonusRecord: (id) => api.delete(`/midyear-records/${id}`),

  // Process mid-year bonus
  processMidYearBonus: (data) => api.post("/process-midyear", data),

  // Post mid-year bonus
  postMidYearBonus: (data) => api.post("/midyear/post", data),

  // Get mid-year bonus report data
  getMidYearBonusReportData: (branchId) =>
    api.get("/midyear-report", { params: { branch_id: branchId } }),

  // Generate mid-year bonus report PDF
  generateMidYearBonusReport: (requestData) =>
    api.post("/midyear/print", requestData, {
      responseType: "blob",
    }),

  // Generate ORS for mid-year bonus (by year id)
  generateORSReport: (yearId, params = {}) =>
    api.get(`/ors-midyear-report/${yearId}`, {
      params,
      responseType: "blob",
    }),

  // Generate DV for mid-year bonus (by year id with signatories)
  generateDVReport: (data) =>
    api.post(`/dv-midyear-report`, data, { responseType: "blob" }),
};

// Year End Bonus APIs
export const yearEndBonusApi = {
  // Get year-end bonus data
  getYearEndBonusData: () => api.get("/process-yearend"),

  // Get year-end bonus records filtered by department
  getYearEndBonusRecords: (params) => api.get("/yearend-records", { params }),

  // Update a single year-end bonus record (bonus_amount, cash_gift_amount)
  updateYearEndBonusRecord: (id, data) =>
    api.patch(`/yearend-records/${id}`, data),

  // Process year-end bonus
  processYearEndBonus: (data) => api.post("/process-yearend", data),

  // Post year-end bonus
  postYearEndBonus: (data) => api.post("/yearend/post", data),

  // Get year-end bonus report data
  getYearEndBonusReportData: (branchId) => api.get("/yearend-report"),

  // Generate year-end bonus PDF report
  generateYearEndBonusReport: (requestData) =>
    api.post("/yearend/print", requestData, {
      responseType: "blob",
    }),

  // Generate ORS for year-end bonus (by year id)
  generateORSReport: (yearId) =>
    api.get(`/ors-yearend-report/${yearId}`, { responseType: "blob" }),

  // Generate DV for year-end bonus (by year id)
  generateDVReport: (yearId) =>
    api.get(`/dv-yearend-report/${yearId}`, { responseType: "blob" }),
};

// Retirement Benefits APIs
export const retirementBenefitsApi = {
  // Get retirement benefits data
  getRetirementBenefitsData: () => api.get("/retirement-benefits"),

  // Get retirees for a specific fiscal year
  getRetirees: (fiscalYear) =>
    api.get("/retirement-benefits/retirees", {
      params: { fiscal_year: fiscalYear },
    }),

  // Generate BP FORM 205 PDF
  generatePdf: (data, config = {}) =>
    api.post("/retirement-benefits/generate-pdf", data, config),

  // Generate BP FORM 205 DOCX
  generateDocx: (data, config = {}) =>
    api.post("/retirement-benefits/generate-docx", data, config),

  // Generate BP FORM 205 Excel
  generateExcel: (data, config = {}) =>
    api.post("/retirement-benefits/generate-excel", data, config),
};

// Extra Bonus APIs
export const extraBonusApi = {
  // Get extra bonus payroll list
  getExtraBonusData: () => api.get("/payroll-extra-bonus"),

  // Get extra bonus form data (for creating/editing)
  getExtraBonusFormData: (extraBonusId) =>
    api.get(`/payroll-extra-bonus/${extraBonusId}/add`),

  // Load employees for extra bonus
  loadEmployees: (extraBonusTypeId, departmentId, yearId) =>
    api.get(
      `/payroll-extra-bonus/${extraBonusTypeId}/${departmentId}/${yearId}/employees`,
    ),

  // Save extra bonus payroll
  saveExtraBonusPayroll: (data) => api.post("/payroll-extra-bonus", data),

  // Process extra bonus (post/unpost)
  processExtraBonus: (extraBonusId, typeId) =>
    api.get(`/payroll-extra-bonus/${extraBonusId}/${typeId}/process`),

  // Bulk post extra bonus payroll records
  bulkPost: (records) =>
    api.post("/payroll-extra-bonus/bulk-post", { records }),

  // Delete extra bonus payroll header and details (draft only)
  deleteExtraBonusPayroll: (extraBonusId) =>
    api.delete(`/payroll-extra-bonus/${extraBonusId}`),

  // Get report data
  getReportData: () => api.get("/payroll-extra-bonus/report"),

  // Generate extra bonus PDF report
  generateReport: (requestData) =>
    api.get("/payroll-extra-bonus/print", {
      params: requestData,
      responseType: "blob",
    }),
};

// Reimbursement Communication Expenses APIs
export const reimbursementCommunicationApi = {
  // Get reimbursement communication expenses list
  getReimbursementCommunicationList: () =>
    api.get("/reimbursement-report", { params: { t: Date.now() } }),

  // Get reimbursement communication expenses form data (for add/edit)
  getReimbursementCommunicationFormData: (id = 0, options = {}) => {
    const params = {};
    if (options.divisionId) params.division_id = options.divisionId;
    if (options.departmentId) params.department_id = options.departmentId;
    return api.get(`/reimbursement/${id}/add`, { params });
  },

  // Create/Update reimbursement communication expenses
  saveReimbursementCommunication: (id, data) =>
    api.post(`/reimbursement/${id}`, data),

  // Add employees to reimbursement communication expenses
  addEmployeesToReimbursementCommunication: (id, employeeData) =>
    api.post(`/reimbursement/${id}/employees`, employeeData),

  // Remove employee from reimbursement communication expenses
  removeEmployeeFromReimbursementCommunication: (id) =>
    api.get(`/reimbursement/${id}/employees/delete`),

  // Process reimbursement communication expenses (post/unpost)
  processReimbursementCommunication: (id, typeId, data) =>
    api.post(`/reimbursement/${id}/${typeId}/process`, data),

  // Delete reimbursement communication header (and its details)
  deleteReimbursementCommunication: (id) =>
    api.get(`/reimbursement/${id}/delete`),

  // Get reimbursement communication expenses report data
  getReimbursementCommunicationReportData: () =>
    api.get("/reimbursement-set-report"),

  // Generate reimbursement communication expenses PDF report
  generateReimbursementCommunicationReport: (requestData) =>
    api.post("/reimbursement-report/print", requestData, {
      responseType: "blob",
    }),
};

// Bank Remittance APIs
export const bankRemittanceApi = {
  // Get bank remittance report data
  getBankRemittanceData: () => api.get("/bank-remittance"),

  // Generate bank remittance PDF report
  generateBankRemittanceReport: (requestData) =>
    api.post("/bank-remittance/print", requestData, {
      responseType: "blob",
    }),
};

// PhilHealth Remittance APIs
export const philhealthRemittanceApi = {
  // Get philhealth remittance report data
  getPhilhealthRemittanceData: () => api.get("/philhealth-remittance"),

  // Generate philhealth remittance PDF report
  generatePhilhealthRemittanceReport: (requestData) =>
    api.post("/philhealth-remittance/print", requestData, {
      responseType: "blob",
    }),
};

// GSIS Remittance APIs
export const gsisRemittanceApi = {
  // Get gsis remittance report data (intervals, departments)
  getGSISRemittanceData: () => api.get("/gsis-remittance"),

  // Get payroll periods that have GSIS data for interval + department
  getGSISPayrollPeriods: (intervalId, divisionId) =>
    api.get("/gsis-remittance/periods", {
      params: {
        payroll_interval_id: intervalId,
        division_id: divisionId,
        department_id: divisionId,
      },
    }),

  // Generate gsis remittance PDF report
  generateGSISRemittanceReport: (requestData) =>
    api.post("/gsis-remittance/print", requestData, {
      responseType: "blob",
    }),
};

// PagIbig Loan APIs
export const pagIbigLoanApi = {
  // Get pagibig loan report data
  getPagIbigLoanData: () => api.get("/pagibig-loan"),

  // Generate mandatory pagibig loan PDF report
  generateMandatoryPagIbigLoanReport: (requestData) =>
    api.post("/print/mandatory", requestData, {
      responseType: "blob",
    }),

  // Generate MP2 pagibig loan PDF report
  generateMP2PagIbigLoanReport: (requestData) =>
    api.post("/print/mp2", requestData, {
      responseType: "blob",
    }),

  // Get pagibig loan form data
  getPagIbigLoanFormData: () => api.get("/pag-ibig-loan"),

  // Generate pagibig loan deduction PDF report
  generatePagIbigLoanDeductionReport: (requestData) =>
    api.post("/loan/print", requestData, {
      responseType: "blob",
    }),
};

// PagIbig Contribution APIs
export const pagIbigContributionApi = {
  // Get contribution report data
  getPagIbigContributionData: () => api.get("/pagibig-contribution"),

  // Generate contribution PDF report
  generatePagIbigContributionReport: (requestData) =>
    api.post("/pagibig-contribution/print", requestData, {
      responseType: "blob",
    }),
};

export default api;

// Subsistence Report APIs
export const subsistenceApi = {
  // Load initial data (departments and payroll periods)
  getSubsistenceReportData: () => api.get("/subsistence-report"),

  // Generate subsistence report PDF (preview/download)
  generateSubsistenceReport: (requestData) =>
    api.post("/subsistence-report/print", requestData, {
      responseType: "blob",
    }),
};

// BIR Form 2305 APIs
export const birForm2305Api = {
  // Load initial data (employees and company info)
  getBIRForm2305Data: () => api.get("/bir-form-2305"),

  // Get employee details for auto-population
  getEmployeeDetails: (employeeId) =>
    api.get(`/bir-form-2305/employee/${employeeId}`),

  // Generate BIR Form 2305 PDF (preview/download)
  generateBIRForm2305: (requestData) =>
    api.post("/bir-form-2305/print", requestData, {
      responseType: "blob",
    }),
};

// PhilHealth PMRF APIs
export const philHealthPMRFApi = {
  // Load initial data (employees)
  getPhilHealthPMRFData: () => api.get("/philhealth-pmrf"),

  // Get employee details for auto-population
  getEmployeeDetails: (employeeId) =>
    api.get(`/philhealth-pmrf/employee/${employeeId}`),

  // Generate PhilHealth PMRF PDF (preview/download)
  generatePhilHealthPMRF: (requestData) =>
    api.post("/philhealth-pmrf/print", requestData, {
      responseType: "blob",
    }),
};

// Pag-IBIG MDF APIs
export const pagIbigMDFApi = {
  // Load initial data (employees)
  getPagIbigMDFData: () => api.get("/pagibig-mdf"),

  // Get employee details for auto-population
  getEmployeeDetails: (employeeId) =>
    api.get(`/pagibig-mdf/employee/${employeeId}`),
};

// PDF Field Mappings APIs
export const pdfFieldMappingApi = {
  // Get mappings for a form type
  getMappings: (formType) => api.get(`/pdf-field-mappings/${formType}`),

  // Save mappings for a form type
  saveMappings: (formType, mappings) =>
    api.post(`/pdf-field-mappings/${formType}`, { mappings }),
};

// GSIS Membership Information Sheet APIs
export const gsisMemberInfoApi = {
  // Load initial data (employees)
  getGSISMemberInfoData: () => api.get("/gsis-member-info"),

  // Get employee details for auto-population
  getEmployeeDetails: (employeeId) =>
    api.get(`/gsis-member-info/employee/${employeeId}`),

  // Generate GSIS Membership Information Sheet PDF (preview/download)
  generateGSISMemberInfo: (requestData) =>
    api.post("/gsis-member-info/print", requestData, {
      responseType: "blob",
    }),

  // Print GSIS Member Info PDF (for preview)
  printGSISMemberInfo: async (requestData) => {
    // Get auth token
    const tokenData = localStorage.getItem("auth_token");
    let token = null;
    if (tokenData) {
      try {
        const parsed = JSON.parse(tokenData);
        token = parsed.token;
      } catch (error) {
        token = tokenData;
      }
    }

    // Get XSRF token from cookies
    const xsrfCookie = document.cookie
      .split("; ")
      .find((row) => row.startsWith("XSRF-TOKEN="));
    const xsrfToken = xsrfCookie
      ? decodeURIComponent(xsrfCookie.split("=")[1])
      : null;

    const headers = {
      Accept: "application/pdf",
      "Content-Type": "application/json",
      "X-Requested-With": "XMLHttpRequest",
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
      ...(xsrfToken ? { "X-XSRF-TOKEN": xsrfToken } : {}),
    };

    const url = `${api.defaults.baseURL}/gsis-member-info/print`;
    const response = await fetch(url, {
      method: "POST",
      headers,
      credentials: "include",
      cache: "no-store",
      body: JSON.stringify(requestData),
    });

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}`);
    }

    const blob = await response.blob();
    return { data: blob };
  },

  // Generate GSIS Member Info DOCX (Word)
  generateGSISMemberInfoDocx: (requestData) =>
    api.post("/gsis-member-info/docx", requestData, {
      responseType: "blob",
    }),

  // Generate GSIS Member Info Excel (placeholder - implement when backend is ready)
  generateGSISMemberInfoExcel: async (requestData) => {
    // Placeholder - will be implemented when backend Excel endpoint is available
    throw new Error("Excel export not yet implemented");
  },
};
