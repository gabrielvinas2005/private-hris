<template>
  <div class="ps-shell">
    <!-- Header -->
    <div class="ps-header">
      <div>
        <h3 class="ps-title">
          {{ summaryData?.payroll_period || "Payroll Summary" }}
        </h3>
        <p class="ps-sub">Detailed breakdown of payroll calculations</p>
      </div>
      <div class="ps-actions">
        <div class="action-row">
          <button
            class="btn-action"
            :disabled="!canProcessPayroll || processing"
            @click="handleProcessPayroll"
          >
            <svg width="13" height="13" viewBox="0 0 13 13" fill="none">
              <path
                d="M2 6.5A4.5 4.5 0 0 1 9.8 3.2"
                stroke="currentColor"
                stroke-width="1.3"
                stroke-linecap="round"
              />
              <path
                d="M1.5 1.5v2.5h2.5"
                stroke="currentColor"
                stroke-width="1.3"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
            </svg>
            {{ processButtonText }}
          </button>
          <button class="btn-action" @click="handleLeaveEarnedDetails">
            <svg width="13" height="13" viewBox="0 0 13 13" fill="none">
              <rect
                x="1.5"
                y="2"
                width="10"
                height="9"
                rx="1.5"
                stroke="currentColor"
                stroke-width="1.2"
              />
              <path
                d="M4 1v2M9 1v2M1.5 5h10"
                stroke="currentColor"
                stroke-width="1.2"
                stroke-linecap="round"
              />
            </svg>
            Leave earned details
          </button>
          <button
            class="btn-action"
            :disabled="!canAdjustTax"
            @click="handleAdjustTax"
          >
            <svg width="13" height="13" viewBox="0 0 13 13" fill="none">
              <path
                d="M9.5 1.5l2 2-7 7H2.5v-2l7-7z"
                stroke="currentColor"
                stroke-width="1.2"
                stroke-linejoin="round"
              />
            </svg>
            Adjust tax
          </button>
          <button class="btn-action" @click="handleSalaryAdjust">
            <svg width="13" height="13" viewBox="0 0 13 13" fill="none">
              <circle
                cx="6.5"
                cy="6.5"
                r="5"
                stroke="currentColor"
                stroke-width="1.2"
              />
              <path
                d="M6.5 4v5M4.5 6.5h4"
                stroke="currentColor"
                stroke-width="1.2"
                stroke-linecap="round"
              />
            </svg>
            Salary adjustment
          </button>
        </div>
        <div class="action-row">
          <button
            class="btn-post"
            :class="{ unpost: isPayrollPosted }"
            :disabled="posting"
            @click="handlePostPayrollClick"
          >
            <span v-if="posting" class="btn-spinner" aria-hidden="true"></span>
            <svg
              v-else
              width="13"
              height="13"
              viewBox="0 0 13 13"
              fill="none"
            >
              <path
                d="M2 7l3 3 6-6"
                stroke="currentColor"
                stroke-width="1.5"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
            </svg>
            {{ postButtonText }}
          </button>
          <div class="export-wrap" ref="exportRef">
            <button class="btn-action" @click="exportOpen = !exportOpen">
              <svg width="13" height="13" viewBox="0 0 13 13" fill="none">
                <path
                  d="M1.5 9v2.5h10V9M6.5 1v7M4 5l2.5 3L9 5"
                  stroke="currentColor"
                  stroke-width="1.2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                />
              </svg>
              Export
              <svg width="10" height="10" viewBox="0 0 10 10" fill="none">
                <path
                  d="M2 4l3 3 3-3"
                  stroke="currentColor"
                  stroke-width="1.2"
                  stroke-linecap="round"
                />
              </svg>
            </button>
            <div v-if="exportOpen" class="export-dropdown">
              <button
                class="export-item"
                @click="
                  handlePrintReport();
                  exportOpen = false;
                "
              >
                Print PDF
              </button>
              <button
                class="export-item"
                @click="
                  handlePrintORS();
                  exportOpen = false;
                "
              >
                Print ORS
              </button>
              <button
                class="export-item"
                @click="
                  handlePrintDV();
                  exportOpen = false;
                "
              >
                Print DV
              </button>
            </div>
          </div>
          <button
            class="btn-ghost-sm"
            @click="handleRefresh"
            :disabled="loading"
          >
            <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
              <path
                d="M1.5 6A4.5 4.5 0 0 1 9.3 2.7"
                stroke="currentColor"
                stroke-width="1.2"
                stroke-linecap="round"
              />
              <path
                d="M1 1v3h3"
                stroke="currentColor"
                stroke-width="1.2"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
            </svg>
            Refresh
          </button>
        </div>
      </div>
    </div>

    <!-- Stat cards -->
    <div class="stat-grid">
      <div class="stat-card">
        <div class="stat-label">Total employees</div>
        <div class="stat-value">{{ totalEmployees }}</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Total gross</div>
        <div class="stat-value green">
          ₱{{ formatCurrency(totalGrossAmount) }}
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Total deductions</div>
        <div class="stat-value amber">
          ₱{{ formatCurrency(totalDeductions) }}
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Total net pay</div>
        <div class="stat-value purple">
          ₱{{ formatCurrency(totalNetAmount) }}
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="filter-bar">
      <div class="search-wrap">
        <svg
          width="14"
          height="14"
          viewBox="0 0 14 14"
          fill="none"
          class="search-icon"
        >
          <circle
            cx="6"
            cy="6"
            r="4"
            stroke="currentColor"
            stroke-width="1.3"
          />
          <path
            d="M9.5 9.5l2.5 2.5"
            stroke="currentColor"
            stroke-width="1.3"
            stroke-linecap="round"
          />
        </svg>
        <input
          v-model="searchQuery"
          class="search-input"
          placeholder="Search employees…"
          @input="handleSearch"
        />
      </div>
      <select
        v-model="divisionFilter"
        class="filter-select"
        @change="handleFilter"
      >
        <option value="">All divisions</option>
        <option v-for="div in divisions" :key="div.id" :value="div.id">
          {{ div.name }}
        </option>
      </select>
      <div class="filter-right">
        <button
          class="view-toggle"
          :class="{ active: !showLowNetOnly }"
          @click="handleShowAll"
        >
          Show all
        </button>
        <button
          class="view-toggle danger"
          :class="{ active: showLowNetOnly }"
          @click="handleShowLessThan5000"
        >
          Below ₱5,000
        </button>
      </div>
    </div>

    <!-- Low net pay alert -->
    <div v-if="lowNetPayCount > 0 && !isPayrollPosted" class="low-net-banner">
      <svg
        width="14"
        height="14"
        viewBox="0 0 14 14"
        fill="none"
        style="flex-shrink: 0"
      >
        <path
          d="M7 2L1 12h12L7 2z"
          stroke="#a32d2d"
          stroke-width="1.3"
          stroke-linejoin="round"
        />
        <path
          d="M7 6v3M7 10.5h.01"
          stroke="#a32d2d"
          stroke-width="1.3"
          stroke-linecap="round"
        />
      </svg>
      <span
        >Posting blocked: <strong>{{ lowNetPayCount }} employee(s)</strong> have
        net pay below ₱5,000. Apply a salary adjustment and reprocess before
        posting.</span
      >
    </div>

    <!-- Payroll table -->
    <div class="payroll-table-wrap" v-if="filteredPayrolls.length > 0">
      <div class="payroll-table-scroll">
        <table class="payroll-table">
          <thead>
            <tr>
              <th class="sticky-col" style="width: 60px">DIV</th>
              <th class="sticky-col emp-col">Employee</th>
              <th class="num-col">Basic salary</th>
              <th class="num-col">OT pay</th>
              <th class="num-col">PERA</th>
              <th class="num-col">Total income</th>
              <th class="num-col">Gross</th>
              <th class="num-col">Late</th>
              <th class="num-col">Undertime</th>
              <th class="num-col">Absent</th>
              <th class="num-col">Preceding</th>
              <th class="num-col">Adj. OT</th>
              <th class="num-col">GSIS</th>
              <!-- <th class="num-col">SSS</th> -->
              <th class="num-col">Pag-IBIG</th>
              <th class="num-col">PhilHealth</th>
              <th class="num-col">Tax</th>
              <th class="num-col">Loan ded.</th>
              <th class="num-col">Total ded.</th>
              <th class="num-col">1H Net</th>
              <th class="num-col">2H Net</th>
              <th class="num-col sticky-right">Net pay</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="row in paginatedPayrolls"
              :key="`${summaryData?.id}-${row.employee_id}`"
              @click="handleRowClick(row)"
              class="payroll-row"
            >
              <td class="sticky-col div-cell">
                {{ getAcronym(row.department) }}
              </td>
              <td class="sticky-col emp-td">
                <div class="emp-cell">
                  <div
                    class="avatar"
                    :style="{
                      background: avatarBg(row.name),
                      color: avatarFg(row.name),
                    }"
                  >
                    <img
                      v-if="row.photo"
                      :src="normalizePhotoSrc(row.photo)"
                      class="avatar-img"
                    />
                    <span v-else>{{ getInitials(row.name) }}</span>
                  </div>
                  <div>
                    <div class="emp-name">{{ row.name }}</div>
                    <div class="emp-pos">{{ row.position }}</div>
                  </div>
                </div>
              </td>
              <td class="num-td">{{ fc(row.salary) }}</td>
              <td class="num-td">{{ fc(row.ot_pay) }}</td>
              <td class="num-td">{{ fc(row.pera_monthly) }}</td>
              <td class="num-td">
                {{ fc(row.total_income_display ?? row.total_income) }}
              </td>
              <td class="num-td">{{ fc(getDisplayGross(row)) }}</td>
              <td class="num-td">{{ fc(row.late_amount) }}</td>
              <td class="num-td">{{ fc(row.ut_amount) }}</td>
              <td class="num-td">{{ fc(row.absent_amount) }}</td>
              <td class="num-td">
                {{ fc(row.preceding_period_adjustment ?? 0) }}
              </td>
              <td class="num-td">{{ fc(row.adjustment_amount_ot_holiday) }}</td>
              <td class="num-td">
                {{ fc(getDisplayGovtContribution(row, "gsis")) }}
              </td>
              <!-- <td class="num-td">
                {{ fc(getDisplayGovtContribution(row, "sss")) }}
              </td> -->
              <td class="num-td">
                {{ fc(getDisplayGovtContribution(row, "pagibig")) }}
              </td>
              <td class="num-td">
                {{ fc(getDisplayGovtContribution(row, "philhealth")) }}
              </td>
              <td class="num-td">
                {{ fc(getDisplayGovtContribution(row, "tax")) }}
              </td>
              <td class="num-td">{{ fc(calculateOtherDeductions(row)) }}</td>
              <td class="num-td" style="color: #a32d2d">
                {{ fc(getDisplayTotalDeduction(row)) }}
              </td>
              <td class="num-td" style="color: #085041">
                {{ fc(row.first_half_net_pay) }}
              </td>
              <td class="num-td" style="color: #085041">
                {{ fc(row.second_half_net_pay) }}
              </td>
              <td
                class="num-td sticky-right"
                :class="{ 'low-net': parseNumericValue(row.net_pay) < 5000 }"
                style="color: #3c3489"
              >
                {{ fc(row.net_pay) }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Pagination bar -->
    <div class="pagination-bar" v-if="filteredPayrolls.length > 0">
      <div class="rows-per-page">
        <span class="pag-label">Rows per page</span>
        <select v-model="rowsPerPage" class="pag-select">
          <option v-for="n in rowsPerPageOptions" :key="n" :value="n">
            {{ n }}
          </option>
        </select>
      </div>

      <div class="pag-info">
        {{ (currentPage - 1) * rowsPerPage + 1 }}–{{
          Math.min(currentPage * rowsPerPage, filteredPayrolls.length)
        }}
        of {{ filteredPayrolls.length }}
      </div>

      <div class="pag-controls">
        <button
          class="pag-btn"
          :disabled="currentPage === 1"
          @click="currentPage = 1"
        >
          <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
            <path
              d="M9 2L5 6l4 4M5 2L1 6l4 4"
              stroke="currentColor"
              stroke-width="1.3"
              stroke-linecap="round"
              stroke-linejoin="round"
            />
          </svg>
        </button>
        <button
          class="pag-btn"
          :disabled="currentPage === 1"
          @click="currentPage--"
        >
          <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
            <path
              d="M7 2L3 6l4 4"
              stroke="currentColor"
              stroke-width="1.3"
              stroke-linecap="round"
              stroke-linejoin="round"
            />
          </svg>
        </button>

        <button
          v-for="p in totalPages"
          :key="p"
          class="pag-btn pag-num"
          :class="{ 'pag-active': p === currentPage }"
          @click="currentPage = p"
          v-show="p === 1 || p === totalPages || Math.abs(p - currentPage) <= 1"
        >
          {{ p }}
        </button>

        <button
          class="pag-btn"
          :disabled="currentPage === totalPages"
          @click="currentPage++"
        >
          <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
            <path
              d="M5 2l4 4-4 4"
              stroke="currentColor"
              stroke-width="1.3"
              stroke-linecap="round"
              stroke-linejoin="round"
            />
          </svg>
        </button>
        <button
          class="pag-btn"
          :disabled="currentPage === totalPages"
          @click="currentPage = totalPages"
        >
          <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
            <path
              d="M3 2l4 4-4 4M7 2l4 4-4 4"
              stroke="currentColor"
              stroke-width="1.3"
              stroke-linecap="round"
              stroke-linejoin="round"
            />
          </svg>
        </button>
      </div>
    </div>

    <div v-else class="empty-state">
      <svg width="36" height="36" viewBox="0 0 36 36" fill="none">
        <rect
          x="5"
          y="7"
          width="26"
          height="22"
          rx="3"
          stroke="#d1d5db"
          stroke-width="1.5"
        />
        <path
          d="M11 13h14M11 18h9"
          stroke="#d1d5db"
          stroke-width="1.5"
          stroke-linecap="round"
        />
      </svg>
      <p>No payroll data available. Process the payroll first.</p>
    </div>

    <!-- Employee breakdown dialog -->
    <EmployeePayrollBreakdownDialog
      v-model="breakdownDialogVisible"
      :payroll-period-id="currentPayrollPeriodId"
      :employee-id="selectedEmployeeId"
      :refresh-key="summaryCacheVersion"
    />

    <!-- Signatory dialog (for export) -->
    <Teleport to="body">
      <Transition name="fade">
        <div
          v-if="signatoryDialogVisible"
          class="modal-backdrop"
          @click.self="cancelSignatoryDialog"
        >
          <div class="modal-card">
            <div class="modal-header">
              <h3 class="modal-title">
                Confirm signatories — {{ activeExportActionLabel }}
              </h3>
              <button class="close-btn" @click="cancelSignatoryDialog">
                <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                  <path
                    d="M2 2l10 10M12 2L2 12"
                    stroke="currentColor"
                    stroke-width="1.5"
                    stroke-linecap="round"
                  />
                </svg>
              </button>
            </div>
            <div class="modal-body">
              <p class="modal-desc">
                Provide the names that should appear on the exported report.
              </p>
              <div class="field-grid">
                <div class="field">
                  <label class="field-label">Prepared by</label>
                  <select v-model="preparedByEmployeeId" class="field-input">
                    <option value="">Select employee</option>
                    <option
                      v-for="e in employeeOptions"
                      :key="e.id"
                      :value="e.id"
                    >
                      {{ e.name }}
                    </option>
                  </select>
                </div>
                <div class="field">
                  <label class="field-label">Position</label>
                  <input
                    :value="signatoryForm.prepared_by_position"
                    disabled
                    class="field-input disabled-input"
                  />
                </div>
                <div class="field">
                  <label class="field-label">Approved by</label>
                  <select v-model="approvedByEmployeeId" class="field-input">
                    <option value="">Select employee</option>
                    <option
                      v-for="e in employeeOptions"
                      :key="e.id"
                      :value="e.id"
                    >
                      {{ e.name }}
                    </option>
                  </select>
                </div>
                <div class="field">
                  <label class="field-label">Position</label>
                  <input
                    :value="signatoryForm.approved_by_position"
                    disabled
                    class="field-input disabled-input"
                  />
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button class="btn-ghost" @click="cancelSignatoryDialog">
                Cancel
              </button>
              <button
                class="btn-primary"
                :disabled="exportActionLoading"
                @click="confirmSignatorySelection"
              >
                {{
                  exportActionLoading
                    ? "Exporting…"
                    : `Export ${activeExportActionLabel}`
                }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import {
  ref,
  computed,
  watch,
  reactive,
  onMounted,
  onBeforeUnmount,
} from "vue";
import { usePayrollProcess } from "../../Composables/usePayrollProcess";
import EmployeePayrollBreakdownDialog from "./EmployeePayrollBreakdownDialog.vue";

const props = defineProps({
  summaryData: { type: Object, default: () => ({}) },
  summaryCacheVersion: { type: Number, default: 0 },
  loading: { type: Boolean, default: false },
  posting: { type: Boolean, default: false },
});
const emit = defineEmits([
  "leave-earned-details",
  "adjust-tax",
  "salary-adjust",
  "print-summary",
  "export-data",
  "refresh",
  "show-all",
  "show-less-than-5000",
  "process-payroll",
  "post-payroll",
  "unpost-payroll",
]);

const {
  generatePayrollReport,
  generateORSReport,
  generateDVReport,
  processing,
} = usePayrollProcess();

const searchQuery = ref("");
const divisionFilter = ref("");
const showLowNetOnly = ref(false);
const exportOpen = ref(false);
const exportRef = ref(null);
const signatoryDialogVisible = ref(false);
const exportActionLoading = ref(false);
const pendingExportAction = ref(null);
const pendingPayrollPeriodId = ref(null);
const breakdownDialogVisible = ref(false);
const selectedEmployeeId = ref(null);
const currentPayrollPeriodId = ref(null);
const preparedByEmployeeId = ref("");
const approvedByEmployeeId = ref("");
const signatoryForm = reactive({
  prepared_by: "",
  prepared_by_position: "",
  approved_by: "",
  approved_by_position: "",
});

const payrolls = computed(() => props.summaryData?.payrolls || []);
const parseNumericValue = (v) => {
  if (v === null || v === undefined || v === "") return 0;
  const n = parseFloat(v);
  return isNaN(n) ? 0 : n;
};
const formatCurrency = (a) =>
  parseNumericValue(a).toLocaleString("en-US", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
const fc = (v) => "₱" + formatCurrency(v);
const getInitials = (n) =>
  !n
    ? "??"
    : n
        .split(" ")
        .map((x) => x[0])
        .join("")
        .toUpperCase()
        .slice(0, 2);
const normalizePhotoSrc = (p) => {
  if (!p) return "";
  const s = String(p).trim();
  if (!s) return "";
  if (s.startsWith("data:")) return s;
  if (s.startsWith("/9j/") || /^[A-Za-z0-9+/]+={0,2}$/.test(s))
    return `data:image/jpeg;base64,${s}`;
  return s;
};
const getAcronym = (t) => {
  if (!t) return "";
  const sw = ["OF", "THE", "AND", "IN"];
  return (
    t
      .trim()
      .split(/\s+/)
      .map((w) => w.toUpperCase())
      .filter((w) => !sw.includes(w))
      .map((w) => w[0] || "")
      .filter(Boolean)
      .join("") ||
    t.trim().toUpperCase()[0] ||
    ""
  );
};

const AVATAR_PAIRS = [
  { bg: "#E6F1FB", fg: "#0C447C" },
  { bg: "#E1F5EE", fg: "#085041" },
  { bg: "#EEEDFE", fg: "#3C3489" },
  { bg: "#FAECE7", fg: "#712B13" },
  { bg: "#FAEEDA", fg: "#633806" },
];
const nameIdx = (n) => ((n || "").charCodeAt(0) || 0) % AVATAR_PAIRS.length;
const avatarBg = (n) => AVATAR_PAIRS[nameIdx(n)].bg;
const avatarFg = (n) => AVATAR_PAIRS[nameIdx(n)].fg;

const getDisplayGovtContribution = (row, field) => {
  if (!row || !field) return 0;
  return parseNumericValue(row[field]);
};
const getDisplayTotalDeduction = (row) => {
  if (!row) return 0;
  return parseNumericValue(row.total_deduction);
};
const calculateOtherDeductions = (row) => {
  if (row?.other_deductions !== undefined && row?.other_deductions !== null)
    return parseNumericValue(row.other_deductions);
  const td = getDisplayTotalDeduction(row);
  const sum =
    parseNumericValue(row?.late_amount) +
    parseNumericValue(row?.ut_amount) +
    parseNumericValue(row?.absent_amount) +
    parseNumericValue(row?.preceding_period_adjustment) +
    getDisplayGovtContribution(row, "gsis") +
    getDisplayGovtContribution(row, "sss") +
    getDisplayGovtContribution(row, "pagibig") +
    getDisplayGovtContribution(row, "philhealth") +
    getDisplayGovtContribution(row, "tax");
  return Math.max(0, td - sum);
};
const isSecondHalfView = computed(() => {
  const l =
    props.summaryData?.payroll_period ||
    props.summaryData?.selected_period?.payroll ||
    "";
  return (
    String(l).toLowerCase().includes("2nd") ||
    String(l).toLowerCase().includes("second")
  );
});
const getDisplayGross = (row) => {
  const g = parseNumericValue(row?.gross_amount);
  return isSecondHalfView.value ? g + parseNumericValue(row?.pera_monthly) : g;
};

const totalEmployees = computed(() => {
  const eligible =
    props.summaryData?.eligible_employee_count ??
    props.summaryData?.time_data_employee_count ??
    0;
  return Math.max(payrolls.value.length, eligible);
});
const totalGrossAmount = computed(() =>
  payrolls.value.reduce((s, r) => s + getDisplayGross(r), 0),
);
const totalNetAmount = computed(() =>
  payrolls.value.reduce((s, r) => s + parseNumericValue(r.net_pay), 0),
);
const totalDeductions = computed(() =>
  payrolls.value.reduce((s, r) => s + getDisplayTotalDeduction(r), 0),
);
const divisions = computed(() => {
  const fromApi = props.summaryData?.divisions;
  if (Array.isArray(fromApi) && fromApi.length) {
    return fromApi
      .map((d) => ({ id: d.id, name: d.name }))
      .sort((a, b) => a.name.localeCompare(b.name));
  }

  const map = new Map();
  payrolls.value.forEach((p) => {
    if (p.division_id == null || p.division_id === "") return;
    const id = String(p.division_id);
    if (!map.has(id)) {
      map.set(id, {
        id: p.division_id,
        name: p.division_name || p.department || `Division ${id}`,
      });
    }
  });
  return Array.from(map.values()).sort((a, b) => a.name.localeCompare(b.name));
});
const lowNetPayCount = computed(
  () =>
    payrolls.value.filter((r) => parseNumericValue(r.net_pay) < 5000).length,
);
const isPayrollPosted = computed(() => {
  const toBool = (v) => v === true || v === 1 || v === "1" || v === "true";
  return (
    toBool(props.summaryData?.selected_period?.posted) ??
    toBool(props.summaryData?.data?.[0]?.posted)
  );
});
const canAdjustTax = computed(() => !isPayrollPosted.value);
const canProcessPayroll = computed(() => !isPayrollPosted.value);
const postButtonText = computed(() =>
  isPayrollPosted.value ? "Unpost payroll" : "Post payroll",
);
const processButtonText = computed(() => {
  const s = props.summaryData?.processing_status;
  return s?.is_processed ? "Reprocess" : "Process";
});

const filteredPayrolls = computed(() => {
  let f = payrolls.value;
  if (showLowNetOnly.value)
    f = f.filter((r) => parseNumericValue(r.net_pay) < 5000);
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase();
    f = f.filter(
      (r) =>
        r.name?.toLowerCase().includes(q) ||
        r.employee_no?.toLowerCase().includes(q) ||
        r.position?.toLowerCase().includes(q),
    );
  }
  if (divisionFilter.value !== "")
    f = f.filter(
      (r) => String(r.division_id) === String(divisionFilter.value),
    );
  return f;
});

const rowsPerPageOptions = [10, 25, 50, 100];
const rowsPerPage = ref(10);
const currentPage = ref(1);
const totalPages = computed(() =>
  Math.max(1, Math.ceil(filteredPayrolls.value.length / rowsPerPage.value)),
);
const paginatedPayrolls = computed(() => {
  const start = (currentPage.value - 1) * rowsPerPage.value;
  return filteredPayrolls.value.slice(start, start + rowsPerPage.value);
});

watch(filteredPayrolls, () => {
  currentPage.value = 1;
});
watch(rowsPerPage, () => {
  currentPage.value = 1;
});

const employeeOptions = computed(() => {
  const u = new Map();
  payrolls.value.forEach((e) => {
    if (!u.has(e.employee_id))
      u.set(e.employee_id, {
        id: e.employee_id,
        name: e.name,
        position: e.position,
      });
  });
  return Array.from(u.values()).sort((a, b) => a.name.localeCompare(b.name));
});

const handleSearch = () => {};
const handleFilter = () => {};
const handleShowAll = () => {
  searchQuery.value = "";
  divisionFilter.value = "";
  showLowNetOnly.value = false;
  emit("show-all");
};
const handleShowLessThan5000 = () => {
  searchQuery.value = "";
  divisionFilter.value = "";
  showLowNetOnly.value = true;
  emit("show-less-than-5000");
};
const handleRefresh = () => emit("refresh");
const handleLeaveEarnedDetails = () => emit("leave-earned-details");
const handleAdjustTax = () => emit("adjust-tax");
const handleSalaryAdjust = () => emit("salary-adjust");

const getCurrentPayrollPeriodInfo = () => ({
  payrollPeriodId:
    props.summaryData?.id ||
    props.summaryData?.selected_period?.id ||
    props.summaryData?.data?.[0]?.id ||
    null,
  payrollLabel:
    props.summaryData?.payroll_period ||
    props.summaryData?.selected_period?.payroll ||
    "Payroll Period",
});
const handleProcessPayroll = () => {
  const { payrollPeriodId, payrollLabel } = getCurrentPayrollPeriodInfo();
  if (!payrollPeriodId) return;
  emit("process-payroll", { id: payrollPeriodId, payroll: payrollLabel });
};
const handlePostPayrollClick = () => {
  const { payrollPeriodId, payrollLabel } = getCurrentPayrollPeriodInfo();
  if (!payrollPeriodId) return;
  const p = { id: payrollPeriodId, payroll: payrollLabel };
  isPayrollPosted.value ? emit("unpost-payroll", p) : emit("post-payroll", p);
};

const exportActionLabelMap = {
  pdf: "Payroll Report",
  ors: "ORS Payroll Report",
  dv: "DV Payroll Report",
};
const activeExportActionLabel = computed(
  () => exportActionLabelMap[pendingExportAction.value] || "Export",
);

const handlePrintReport = async () => {
  const { payrollPeriodId } = getCurrentPayrollPeriodInfo();
  if (!payrollPeriodId) return;
  pendingPayrollPeriodId.value = payrollPeriodId;
  pendingExportAction.value = "pdf";
  await confirmSignatorySelection();
};
const handlePrintORS = async () => {
  const { payrollPeriodId } = getCurrentPayrollPeriodInfo();
  if (!payrollPeriodId) return;
  pendingPayrollPeriodId.value = payrollPeriodId;
  pendingExportAction.value = "ors";
  await confirmSignatorySelection();
};
const handlePrintDV = async () => {
  const { payrollPeriodId } = getCurrentPayrollPeriodInfo();
  if (!payrollPeriodId) return;
  pendingPayrollPeriodId.value = payrollPeriodId;
  pendingExportAction.value = "dv";
  await confirmSignatorySelection();
};

const confirmSignatorySelection = async () => {
  exportActionLoading.value = true;
  try {
    const payload = { ...signatoryForm };
    if (pendingExportAction.value === "pdf")
      await generatePayrollReport(pendingPayrollPeriodId.value, payload);
    else if (pendingExportAction.value === "ors")
      await generateORSReport(pendingPayrollPeriodId.value, payload);
    else if (pendingExportAction.value === "dv")
      await generateDVReport(pendingPayrollPeriodId.value, payload);
    emit("print-summary");
  } catch (e) {
    console.error(e);
  } finally {
    exportActionLoading.value = false;
    signatoryDialogVisible.value = false;
    pendingExportAction.value = null;
    pendingPayrollPeriodId.value = null;
  }
};
const cancelSignatoryDialog = () => {
  signatoryDialogVisible.value = false;
  pendingExportAction.value = null;
  pendingPayrollPeriodId.value = null;
};

const initSignatoryForm = () => {
  const src =
    props.summaryData?.signatories || props.summaryData?.selected_period || {};
  signatoryForm.prepared_by = src.prepared_by || "";
  signatoryForm.prepared_by_position = src.prepared_by_position || "";
  signatoryForm.approved_by = src.approved_by || "";
  signatoryForm.approved_by_position = src.approved_by_position || "";
  const pm = employeeOptions.value.find(
    (e) => e.name?.toLowerCase() === signatoryForm.prepared_by.toLowerCase(),
  );
  preparedByEmployeeId.value = pm?.id || "";
  if (pm && !signatoryForm.prepared_by_position)
    signatoryForm.prepared_by_position = pm.position || "";
  const am = employeeOptions.value.find(
    (e) => e.name?.toLowerCase() === signatoryForm.approved_by.toLowerCase(),
  );
  approvedByEmployeeId.value = am?.id || "";
  if (am && !signatoryForm.approved_by_position)
    signatoryForm.approved_by_position = am.position || "";
};
watch(() => props.summaryData, initSignatoryForm, { immediate: true });
watch(preparedByEmployeeId, (id) => {
  const e = employeeOptions.value.find((x) => x.id === id);
  signatoryForm.prepared_by = e?.name || "";
  signatoryForm.prepared_by_position = e?.position || "";
});
watch(approvedByEmployeeId, (id) => {
  const e = employeeOptions.value.find((x) => x.id === id);
  signatoryForm.approved_by = e?.name || "";
  signatoryForm.approved_by_position = e?.position || "";
});

const handleRowClick = (row) => {
  const id = props.summaryData?.id || props.summaryData?.data?.[0]?.id || null;
  if (!id) return;
  selectedEmployeeId.value = row.employee_id;
  currentPayrollPeriodId.value = id;
  breakdownDialogVisible.value = true;
};

// close export dropdown on outside click
const handleOutsideClick = (e) => {
  if (exportRef.value && !exportRef.value.contains(e.target))
    exportOpen.value = false;
};
onMounted(() => document.addEventListener("click", handleOutsideClick));
onBeforeUnmount(() =>
  document.removeEventListener("click", handleOutsideClick),
);
</script>

<style scoped>
.ps-shell {
  display: flex;
  flex-direction: column;
  gap: 16px;
}
.ps-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  padding: 18px 20px;
  background: #f9fafb;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  gap: 16px;
  flex-wrap: wrap;
}
.ps-title {
  font-size: 18px;
  font-weight: 500;
  color: #111827;
  margin: 0 0 4px;
}
.ps-sub {
  font-size: 13px;
  color: #9ca3af;
  margin: 0;
}
.ps-actions {
  display: flex;
  flex-direction: column;
  gap: 8px;
  align-items: flex-end;
}
.action-row {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  justify-content: flex-end;
}
.btn-action {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  background: transparent;
  border: 1px solid #e5e7eb;
  border-radius: 7px;
  padding: 6px 12px;
  font-size: 12px;
  color: #374151;
  cursor: pointer;
  white-space: nowrap;
}
.btn-action:hover:not(:disabled) {
  background: #f3f4f6;
}
.btn-action:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}
.btn-post {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  background: #409eff;
  color: #fff;
  border: none;
  border-radius: 7px;
  padding: 6px 14px;
  font-size: 12px;
  font-weight: 500;
  cursor: pointer;
}
.btn-post:hover {
  opacity: 0.88;
}
.btn-post.unpost {
  background: #7f2020;
}

.btn-post:disabled {
  opacity: 0.75;
  cursor: not-allowed;
}

.btn-spinner {
  width: 13px;
  height: 13px;
  display: inline-block;
  border-radius: 999px;
  border: 2px solid rgba(255, 255, 255, 0.55);
  border-top-color: rgba(255, 255, 255, 1);
  animation: btnSpin 0.8s linear infinite;
}

.btn-post.unpost .btn-spinner {
  border-color: rgba(255, 255, 255, 0.35);
  border-top-color: rgba(255, 255, 255, 0.95);
}

@keyframes btnSpin {
  to {
    transform: rotate(360deg);
  }
}
.btn-ghost-sm {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  background: transparent;
  border: 1px solid #e5e7eb;
  border-radius: 7px;
  padding: 5px 10px;
  font-size: 12px;
  color: #9ca3af;
  cursor: pointer;
}
.btn-ghost-sm:hover:not(:disabled) {
  background: #f3f4f6;
  color: #374151;
}
.export-wrap {
  position: relative;
}
.export-dropdown {
  position: absolute;
  right: 0;
  top: calc(100% + 4px);
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 4px;
  z-index: 100;
  min-width: 140px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}
.export-item {
  display: block;
  width: 100%;
  text-align: left;
  padding: 7px 12px;
  font-size: 12px;
  color: #374151;
  background: transparent;
  border: none;
  border-radius: 5px;
  cursor: pointer;
}
.export-item:hover {
  background: #f3f4f6;
}

.stat-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 10px;
}
.stat-card {
  background: #f9fafb;
  border-radius: 10px;
  padding: 14px 16px;
}
.stat-label {
  font-size: 12px;
  color: #6b7280;
  margin-bottom: 5px;
}
.stat-value {
  font-size: 18px;
  font-weight: 500;
  color: #111827;
}
.stat-value.green {
  color: #085041;
}
.stat-value.amber {
  color: #a32d2d;
}
.stat-value.purple {
  color: #3c3489;
}

.filter-bar {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}
.search-wrap {
  position: relative;
  flex: 1;
  min-width: 200px;
}
.search-icon {
  position: absolute;
  left: 9px;
  top: 50%;
  transform: translateY(-50%);
  color: #9ca3af;
  pointer-events: none;
}
.search-input {
  width: 100%;
  font-size: 13px;
  padding: 7px 10px 7px 30px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  outline: none;
  color: #111827;
}
.search-input:focus {
  border-color: #6b7280;
}
.filter-select {
  font-size: 13px;
  padding: 7px 10px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  background: #fff;
  color: #111827;
  outline: none;
  min-width: 160px;
}
.filter-right {
  display: flex;
  gap: 6px;
  margin-left: auto;
}
.view-toggle {
  background: transparent;
  border: 1px solid #e5e7eb;
  border-radius: 7px;
  padding: 6px 12px;
  font-size: 12px;
  color: #6b7280;
  cursor: pointer;
}
.view-toggle.active {
  background: #409eff;
  color: #fff;
  border-color: #409eff;
}
.view-toggle.danger.active {
  background: #a32d2d;
  border-color: #a32d2d;
  color: #fff;
}

.low-net-banner {
  display: flex;
  gap: 10px;
  align-items: center;
  background: #faece7;
  border: 1px solid #f0997b;
  border-radius: 8px;
  padding: 10px 14px;
  font-size: 13px;
  color: #712b13;
}

/* Wide payroll table */
.payroll-table-wrap {
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  overflow: hidden;
}
.payroll-table-scroll {
  overflow-x: auto;
}
.payroll-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 12px;
  white-space: nowrap;
}
.payroll-table thead {
  background: #f9fafb;
}
.payroll-table thead th {
  padding: 9px 12px;
  text-align: left;
  font-weight: 500;
  font-size: 11px;
  color: #6b7280;
  border-bottom: 1px solid #e5e7eb;
}
.payroll-table thead .num-col {
  text-align: right;
}
.payroll-table tbody tr {
  border-bottom: 1px solid #f3f4f6;
  cursor: pointer;
  transition: background 0.1s;
}
.payroll-table tbody tr:last-child {
  border-bottom: none;
}
.payroll-table tbody tr:hover {
  background: #f9fafb;
}
.payroll-table td {
  padding: 10px 12px;
  color: #374151;
  vertical-align: middle;
}
.sticky-col {
  position: sticky;
  left: 0;
  background: #f9fafb;
  z-index: 2;
}
.emp-col {
  left: 60px !important;
  min-width: 220px;
}
.sticky-right {
  position: sticky;
  right: 0;
  background: #f9fafb;
  z-index: 2;
  border-left: 1px solid #e5e7eb;
}
.payroll-table tbody tr {
  border-bottom: 1px solid #f3f4f6;
  cursor: pointer;
  transition: background 0.1s;
  background: #ffffff;
}
.payroll-table thead th.sticky-col,
.payroll-table thead th.sticky-right,
.payroll-row:hover .emp-td {
  background: #f9fafb;
  z-index: 3;
}
.div-cell {
  font-size: 11px;
  color: #9ca3af;
  font-weight: 500;
}
.num-td {
  text-align: right;
  font-family: monospace;
  font-size: 12px;
}
.low-net {
  color: #a32d2d;
  font-weight: 500;
}
.emp-td {
  min-width: 220px;
  left: 60px;
  background: #f9fafb;
}
.emp-cell {
  display: flex;
  align-items: center;
  gap: 8px;
}
.avatar {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 10px;
  font-weight: 500;
  flex-shrink: 0;
  overflow: hidden;
}
.avatar-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.emp-name {
  font-weight: 500;
  font-size: 12px;
  color: #111827;
}
.emp-pos {
  font-size: 11px;
  color: #9ca3af;
}
.payroll-row:hover .sticky-col,
.payroll-row:hover .sticky-right {
  background: #f9fafb;
}

.empty-state {
  text-align: center;
  padding: 48px 0;
  color: #9ca3af;
  font-size: 14px;
}
.empty-state svg {
  margin: 0 auto 12px;
  display: block;
}

/* modal */
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.3);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  padding: 20px;
}
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
.modal-card {
  background: #fff;
  border-radius: 14px;
  width: 100%;
  max-width: 560px;
  overflow: hidden;
}
.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 20px;
  border-bottom: 1px solid #f3f4f6;
}
.modal-title {
  font-size: 15px;
  font-weight: 500;
  color: #111827;
  margin: 0;
}
.modal-body {
  padding: 16px 20px;
}
.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  padding: 14px 20px;
  border-top: 1px solid #f3f4f6;
}
.modal-desc {
  font-size: 13px;
  color: #9ca3af;
  margin: 0 0 14px;
}
.field-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}
.field {
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.field-label {
  font-size: 12px;
  font-weight: 500;
  color: #374151;
}
.field-input {
  font-size: 13px;
  padding: 7px 10px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  outline: none;
  color: #111827;
}
.field-input:focus {
  border-color: #6b7280;
}
.disabled-input {
  background: #f9fafb;
  color: #9ca3af;
  cursor: not-allowed;
}
.close-btn {
  background: transparent;
  border: none;
  padding: 5px;
  border-radius: 6px;
  color: #9ca3af;
  cursor: pointer;
  display: flex;
}
.close-btn:hover {
  background: #f3f4f6;
  color: #374151;
}
.btn-ghost {
  background: transparent;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 8px 16px;
  font-size: 13px;
  color: #6b7280;
  cursor: pointer;
}
.btn-ghost:hover {
  background: #f3f4f6;
}
.btn-primary {
  background: #409eff;
  color: #fff;
  border: none;
  border-radius: 8px;
  padding: 8px 18px;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
}
.btn-primary:hover:not(:disabled) {
  opacity: 0.88;
}
.btn-primary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Pagination */
.pagination-bar {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 16px;
  padding: 10px 4px;
  flex-wrap: wrap;
}
.rows-per-page {
  display: flex;
  align-items: center;
  gap: 8px;
}
.pag-label {
  font-size: 12px;
  color: #6b7280;
  white-space: nowrap;
}
.pag-select {
  font-size: 12px;
  padding: 4px 8px;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  background: #fff;
  color: #111827;
  outline: none;
  cursor: pointer;
}
.pag-info {
  font-size: 12px;
  color: #6b7280;
  white-space: nowrap;
}
.pag-controls {
  display: flex;
  align-items: center;
  gap: 3px;
}
.pag-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 28px;
  height: 28px;
  padding: 0 6px;
  background: transparent;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  font-size: 12px;
  color: #374151;
  cursor: pointer;
  transition: background 0.1s;
}
.pag-btn:hover:not(:disabled) {
  background: #f3f4f6;
}
.pag-btn:disabled {
  opacity: 0.35;
  cursor: not-allowed;
}
.pag-active {
  background: #409eff !important;
  color: #fff !important;
  border-color: #409eff !important;
}
</style>
