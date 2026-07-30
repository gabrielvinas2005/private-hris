<template>
  <Teleport to="body">
    <Transition name="fade">
      <div v-if="visible" class="modal-backdrop" @click.self="handleClose">
        <div class="modal-card" v-loading="loading">
          <div class="modal-header">
            <h3 class="modal-title">Employee payroll breakdown</h3>
            <button class="close-btn" @click="handleClose">
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

          <div class="modal-body" v-if="breakdownData">
            <!-- Employee header -->
            <div class="emp-header" v-if="breakdownData.payroll_summary">
              <div
                class="avatar-lg"
                :style="{
                  background: avatarBg(breakdownData.payroll_summary.name),
                  color: avatarFg(breakdownData.payroll_summary.name),
                }"
              >
                <img
                  v-if="breakdownData.payroll_summary.photo"
                  :src="normalizePhotoSrc(breakdownData.payroll_summary.photo)"
                  class="avatar-img"
                />
                <span v-else>{{
                  getInitials(breakdownData.payroll_summary.name)
                }}</span>
              </div>
              <div>
                <div class="emp-name">
                  {{ breakdownData.payroll_summary.name }}
                </div>
                <div class="emp-pos">
                  {{ breakdownData.payroll_summary.position }}
                </div>
                <div class="emp-meta">
                  <span>{{ breakdownData.payroll_summary.employee_no }}</span> ·
                  <span>{{ breakdownData.payroll_summary.department }}</span>
                </div>
              </div>
            </div>

            <!-- Summary cards -->
            <div class="stat-grid" v-if="breakdownData.payroll_summary">
              <div class="stat-card">
                <div class="stat-label">Gross amount</div>
                <div class="stat-value green">
                  ₱{{ fc(breakdownData.payroll_summary.gross_amount) }}
                </div>
              </div>
              <div class="stat-card">
                <div class="stat-label">Total deductions</div>
                <div class="stat-value amber">
                  ₱{{ fc(displayTotalDeductions) }}
                </div>
              </div>
              <div class="stat-card">
                <div class="stat-label">Net pay</div>
                <div class="stat-value purple">
                  ₱{{ fc(breakdownData.payroll_summary.net_pay) }}
                </div>
              </div>
              <div class="stat-card">
                <div class="stat-label">Basic salary</div>
                <div class="stat-value">
                  ₱{{ fc(breakdownData.payroll_summary.salary) }}
                </div>
              </div>
            </div>

            <!-- Additional income -->
            <div class="section" v-if="breakdownData.incomes?.length > 0">
              <div class="section-header">Additional income</div>
              <table class="mini-table">
                <thead>
                  <tr>
                    <th>Income type</th>
                    <th class="num-col">Amount</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="i in breakdownData.incomes" :key="i.income_name">
                    <td>{{ i.income_name }}</td>
                    <td class="num-td">₱{{ fc(i.amount) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Loans -->
            <div class="section" v-if="breakdownData.loans?.length > 0">
              <div class="section-header">Loans</div>
              <table class="mini-table">
                <thead>
                  <tr>
                    <th>Loan type</th>
                    <th class="num-col">Current payment</th>
                    <th class="num-col">Total paid</th>
                    <th class="num-col">Remaining</th>
                    <th class="num-col">Original</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="l in breakdownData.loans" :key="l.loan_type">
                    <td>{{ l.loan_type }}</td>
                    <td class="num-td">₱{{ fc(l.current_payment) }}</td>
                    <td class="num-td">₱{{ fc(l.total_payment) }}</td>
                    <td class="num-td">₱{{ fc(l.remaining_balance) }}</td>
                    <td class="num-td">₱{{ fc(l.loan_amount) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Standard deductions -->
            <div class="section" v-if="breakdownData.standard_deductions">
              <div class="section-header">Standard deductions</div>
              <div class="deduction-grid">
                <div class="ded-item">
                  <span class="ded-label">Late</span
                  ><span class="ded-val"
                    >₱{{
                      fc(breakdownData.standard_deductions.late_amount)
                    }}</span
                  >
                </div>
                <div class="ded-item" v-if="isContributionEnabled('sss')">
                  <span class="ded-label">SSS</span
                  ><span class="ded-val"
                    >₱{{ fc(breakdownData.standard_deductions.sss) }}</span
                  >
                </div>
                <div class="ded-item">
                  <span class="ded-label">Undertime</span
                  ><span class="ded-val"
                    >₱{{
                      fc(breakdownData.standard_deductions.ut_amount)
                    }}</span
                  >
                </div>
                <div class="ded-item" v-if="isContributionEnabled('pagibig')">
                  <span class="ded-label">Pag-IBIG</span
                  ><span class="ded-val"
                    >₱{{ fc(breakdownData.standard_deductions.pagibig) }}</span
                  >
                </div>
                <div class="ded-item">
                  <span class="ded-label">Absent</span
                  ><span class="ded-val"
                    >₱{{
                      fc(breakdownData.standard_deductions.absent_amount)
                    }}</span
                  >
                </div>
                <div class="ded-item" v-if="isContributionEnabled('philhealth')">
                  <span class="ded-label">PhilHealth</span
                  ><span class="ded-val"
                    >₱{{
                      fc(breakdownData.standard_deductions.philhealth)
                    }}</span
                  >
                </div>
                <div class="ded-item" v-if="isContributionEnabled('gsis')">
                  <span class="ded-label">GSIS</span
                  ><span class="ded-val"
                    >₱{{ fc(breakdownData.standard_deductions.gsis) }}</span
                  >
                </div>
                <div class="ded-item" v-if="isContributionEnabled('tax')">
                  <span class="ded-label">Tax</span
                  ><span class="ded-val"
                    >₱{{ fc(breakdownData.standard_deductions.tax) }}</span
                  >
                </div>
              </div>
            </div>

            <!-- Other deductions -->
            <div
              class="section"
              v-if="breakdownData.other_deductions?.length > 0"
            >
              <div class="section-header">Other deductions</div>
              <table class="mini-table">
                <thead>
                  <tr>
                    <th>Deduction type</th>
                    <th class="num-col">Amount</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="d in breakdownData.other_deductions"
                    :key="d.deduction_name"
                  >
                    <td>{{ d.deduction_name }}</td>
                    <td class="num-td">₱{{ fc(d.amount) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Government contributions & tax (YTD by month) -->
            <div
              class="section"
              v-if="govContributionHistory?.months?.length"
            >
              <button
                type="button"
                class="section-toggle"
                @click="govHistoryExpanded = !govHistoryExpanded"
                :aria-expanded="govHistoryExpanded"
              >
                <span class="section-header">
                  Government contributions &amp; tax paid ({{ govContributionHistory.year }})
                </span>
                <span class="toggle-meta">
                  <span class="toggle-summary">
                    YTD total ₱{{ fc(govContributionHistory.ytd_grand_total) }}
                  </span>
                  <svg
                    class="chevron"
                    :class="{ open: govHistoryExpanded }"
                    width="16"
                    height="16"
                    viewBox="0 0 16 16"
                    fill="none"
                  >
                    <path
                      d="M4 6l4 4 4-4"
                      stroke="currentColor"
                      stroke-width="1.5"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                    />
                  </svg>
                </span>
              </button>

              <div class="ytd-chips">
                <div
                  v-for="col in visibleContributionColumns"
                  :key="col.key"
                  class="ytd-chip"
                >
                  <span class="ytd-chip-label">{{ col.label }}</span>
                  <span class="ytd-chip-val"
                    >₱{{ fc(govContributionHistory.ytd_totals[col.key]) }}</span
                  >
                </div>
              </div>

              <div v-show="govHistoryExpanded" class="history-panel">
                <table class="mini-table history-table">
                  <thead>
                    <tr>
                      <th>Month</th>
                      <th
                        v-for="col in visibleContributionColumns"
                        :key="col.key"
                        class="num-col"
                      >
                        {{ col.label }}
                      </th>
                      <th class="num-col">Total</th>
                    </tr>
                  </thead>
                  <tbody>
                    <template
                      v-for="month in govContributionHistory.months"
                      :key="month.year_month"
                    >
                      <tr
                        class="month-row"
                        :class="{ expanded: expandedMonths.has(month.year_month) }"
                        @click="toggleMonth(month.year_month)"
                      >
                        <td class="month-cell">
                          <span class="month-toggle">
                            <svg
                              class="chevron-sm"
                              :class="{ open: expandedMonths.has(month.year_month) }"
                              width="12"
                              height="12"
                              viewBox="0 0 16 16"
                              fill="none"
                            >
                              <path
                                d="M6 4l4 4-4 4"
                                stroke="currentColor"
                                stroke-width="1.5"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                              />
                            </svg>
                            {{ month.month_label }}
                          </span>
                          <span class="period-count" v-if="month.periods?.length > 1">
                            {{ month.periods.length }} periods
                          </span>
                        </td>
                        <td
                          v-for="col in visibleContributionColumns"
                          :key="col.key"
                          class="num-td"
                        >
                          ₱{{ fc(month[col.key]) }}
                        </td>
                        <td class="num-td month-total">₱{{ fc(month.total) }}</td>
                      </tr>
                      <template v-if="expandedMonths.has(month.year_month)">
                        <tr
                          v-for="period in month.periods"
                          :key="`${month.year_month}-${period.payroll_period_id}`"
                          class="period-row"
                        >
                          <td class="period-label">
                            {{ period.period_label }}
                          </td>
                          <td
                            v-for="col in visibleContributionColumns"
                            :key="col.key"
                            class="num-td period-val"
                          >
                            ₱{{ fc(period[col.key]) }}
                          </td>
                          <td class="num-td period-val">₱{{ fc(period.total) }}</td>
                        </tr>
                      </template>
                    </template>
                  </tbody>
                  <tfoot>
                    <tr class="ytd-row">
                      <td><strong>YTD total</strong></td>
                      <td
                        v-for="col in visibleContributionColumns"
                        :key="col.key"
                        class="num-td"
                      >
                        <strong>₱{{ fc(govContributionHistory.ytd_totals[col.key]) }}</strong>
                      </td>
                      <td class="num-td">
                        <strong>₱{{ fc(govContributionHistory.ytd_grand_total) }}</strong>
                      </td>
                    </tr>
                  </tfoot>
                </table>
              </div>
            </div>

            <!-- Income threshold (compact) -->
            <div
              class="section"
              v-if="breakdownData.income_cap_info?.income_threshold"
            >
              <div class="section-header">
                Income threshold &amp; taxable income
              </div>
              <div class="deduction-grid">
                <div class="ded-item">
                  <span class="ded-label">Benefits threshold</span
                  ><span class="ded-val"
                    >₱{{
                      fc(
                        breakdownData.income_cap_info.income_threshold
                          .taxable_threshold,
                      )
                    }}</span
                  >
                </div>
                <div class="ded-item">
                  <span class="ded-label">Excess income (taxable)</span
                  ><span
                    class="ded-val"
                    :class="
                      breakdownData.income_cap_info.income_threshold
                        .excess_income > 0
                        ? 'red'
                        : ''
                    "
                    >₱{{
                      fc(
                        breakdownData.income_cap_info.income_threshold
                          .excess_income,
                      )
                    }}</span
                  >
                </div>
                <div class="ded-item">
                  <span class="ded-label">YTD total income</span
                  ><span class="ded-val"
                    >₱{{
                      fc(
                        breakdownData.income_cap_info.income_threshold
                          .ytd_gross_income,
                      )
                    }}</span
                  >
                </div>
                <div class="ded-item">
                  <span class="ded-label">Current period total</span
                  ><span class="ded-val"
                    >₱{{
                      fc(
                        breakdownData.income_cap_info.income_threshold
                          .current_period_total_income,
                      )
                    }}</span
                  >
                </div>
              </div>
              <div
                class="threshold-note"
                :class="
                  breakdownData.income_cap_info.income_threshold.excess_income >
                  0
                    ? 'info'
                    : 'ok'
                "
              >
                <span
                  v-if="
                    breakdownData.income_cap_info.income_threshold
                      .excess_income > 0
                  "
                >
                  YTD total income of
                  <strong
                    >₱{{
                      fc(
                        breakdownData.income_cap_info.income_threshold
                          .ytd_gross_income,
                      )
                    }}</strong
                  >
                  exceeds the threshold of
                  <strong
                    >₱{{
                      fc(
                        breakdownData.income_cap_info.income_threshold
                          .taxable_threshold,
                      )
                    }}</strong
                  >. The excess of
                  <strong
                    >₱{{
                      fc(
                        breakdownData.income_cap_info.income_threshold
                          .excess_income,
                      )
                    }}</strong
                  >
                  is taxable.
                </span>
                <span v-else
                  >YTD total income is below the benefits threshold. No excess
                  income is taxable at this time.</span
                >
              </div>
            </div>
          </div>

          <div v-else-if="loading" class="loading-state">
            <div class="spinner"></div>
            <p>Loading breakdown…</p>
          </div>

          <div class="modal-footer">
            <button class="btn-ghost" @click="handleClose">Close</button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch } from "vue";
import { payrollProcessApi } from "../../services/api";

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  payrollPeriodId: { type: [String, Number], default: null },
  employeeId: { type: [String, Number], default: null },
  refreshKey: { type: Number, default: 0 },
});
const emit = defineEmits(["update:modelValue"]);
const visible = computed({
  get: () => props.modelValue,
  set: (v) => emit("update:modelValue", v),
});
const loading = ref(false);
const breakdownData = ref(null);
const govHistoryExpanded = ref(false);
const expandedMonths = ref(new Set());
const fc = (v) =>
  parseFloat(v || 0).toLocaleString("en-US", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
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
  return `data:image/jpeg;base64,${s}`;
};
const AVATAR_PAIRS = [
  { bg: "#E6F1FB", fg: "#0C447C" },
  { bg: "#E1F5EE", fg: "#085041" },
  { bg: "#EEEDFE", fg: "#3C3489" },
  { bg: "#FAECE7", fg: "#712B13" },
  { bg: "#FAEEDA", fg: "#633806" },
];
const avatarBg = (n) =>
  AVATAR_PAIRS[((n || "").charCodeAt(0) || 0) % AVATAR_PAIRS.length].bg;
const avatarFg = (n) =>
  AVATAR_PAIRS[((n || "").charCodeAt(0) || 0) % AVATAR_PAIRS.length].fg;
const contributionVisibility = computed(
  () => breakdownData.value?.contribution_visibility || {},
);
const isContributionEnabled = (key) => {
  const v = contributionVisibility.value?.[key];
  return v === undefined || v === null ? true : !!v;
};
const contributionColumnDefs = [
  { key: "gsis", label: "GSIS", visibilityKey: "gsis" },
  { key: "philhealth", label: "PhilHealth", visibilityKey: "philhealth" },
  { key: "pagibig", label: "Pag-IBIG", visibilityKey: "pagibig" },
  { key: "sss", label: "SSS", visibilityKey: "sss" },
  { key: "tax", label: "Tax", visibilityKey: "tax" },
];
const visibleContributionColumns = computed(() =>
  contributionColumnDefs.filter((col) =>
    isContributionEnabled(col.visibilityKey),
  ),
);
const govContributionHistory = computed(
  () => breakdownData.value?.income_cap_info?.gov_contribution_history ?? null,
);
const toggleMonth = (yearMonth) => {
  const next = new Set(expandedMonths.value);
  if (next.has(yearMonth)) {
    next.delete(yearMonth);
  } else {
    next.add(yearMonth);
  }
  expandedMonths.value = next;
};
const resetHistoryUi = () => {
  govHistoryExpanded.value = false;
  expandedMonths.value = new Set();
};
const toNumber = (value) => {
  if (value === null || value === undefined || value === "") return 0;
  if (typeof value === "number") return Number.isFinite(value) ? value : 0;
  const normalized = String(value).replace(/,/g, "");
  const parsed = Number(normalized);
  return Number.isFinite(parsed) ? parsed : 0;
};
const displayTotalDeductions = computed(() => {
  const data = breakdownData.value || {};
  const standard = data.standard_deductions || {};

  const standardDeductionTotal =
    toNumber(standard.late_amount) +
    toNumber(standard.ut_amount) +
    toNumber(standard.absent_amount) +
    (isContributionEnabled("sss") ? toNumber(standard.sss) : 0) +
    (isContributionEnabled("pagibig") ? toNumber(standard.pagibig) : 0) +
    (isContributionEnabled("philhealth") ? toNumber(standard.philhealth) : 0) +
    (isContributionEnabled("gsis") ? toNumber(standard.gsis) : 0) +
    (isContributionEnabled("tax") ? toNumber(standard.tax) : 0);

  const otherDeductionsTotal = (data.other_deductions || []).reduce(
    (sum, deduction) => sum + toNumber(deduction.amount),
    0,
  );

  return standardDeductionTotal + otherDeductionsTotal;
});
const fetchBreakdown = async ({ forceRefresh = false } = {}) => {
  if (!props.payrollPeriodId || !props.employeeId) return;
  loading.value = true;
  if (forceRefresh) {
    breakdownData.value = null;
  }
  try {
    const res = await payrollProcessApi.getEmployeeBreakdown(
      props.payrollPeriodId,
      props.employeeId,
      {
        refresh: forceRefresh,
        cacheVersion: props.refreshKey,
      },
    );
    if (res.data.success) {
      resetHistoryUi();
      breakdownData.value = res.data.data;
    }
  } catch (e) {
    console.error(e);
  } finally {
    loading.value = false;
  }
};
const handleClose = () => {
  visible.value = false;
  breakdownData.value = null;
  resetHistoryUi();
};
watch(
  () => [
    props.modelValue,
    props.payrollPeriodId,
    props.employeeId,
    props.refreshKey,
  ],
  ([open]) => {
    if (open && props.payrollPeriodId && props.employeeId) {
      fetchBreakdown({ forceRefresh: true });
    }
  },
  { immediate: true },
);
watch(visible, (v) => {
  if (typeof document !== "undefined")
    document.body.style.overflow = v ? "hidden" : "";
});
</script>

<style scoped>
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
  border-radius: 16px;
  width: 100%;
  max-width: 780px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.14);
}
.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 18px 24px;
  border-bottom: 1px solid #f3f4f6;
  flex-shrink: 0;
}
.modal-title {
  font-size: 16px;
  font-weight: 500;
  color: #111827;
  margin: 0;
}
.close-btn {
  background: transparent;
  border: none;
  border-radius: 7px;
  padding: 5px;
  color: #9ca3af;
  cursor: pointer;
  display: flex;
}
.close-btn:hover {
  background: #f3f4f6;
  color: #374151;
}
.modal-body {
  padding: 18px 24px;
  overflow-y: auto;
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 16px;
}
.modal-footer {
  display: flex;
  justify-content: flex-end;
  padding: 14px 24px;
  border-top: 1px solid #f3f4f6;
  flex-shrink: 0;
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

.emp-header {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 14px 16px;
  background: #f9fafb;
  border-radius: 12px;
}
.avatar-lg {
  width: 52px;
  height: 52px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
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
  font-size: 16px;
  font-weight: 500;
  color: #111827;
  margin-bottom: 2px;
}
.emp-pos {
  font-size: 13px;
  color: #6b7280;
  margin-bottom: 2px;
}
.emp-meta {
  font-size: 12px;
  color: #9ca3af;
}

.stat-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 10px;
}
.stat-card {
  background: #f9fafb;
  border-radius: 10px;
  padding: 12px 14px;
}
.stat-label {
  font-size: 11px;
  color: #9ca3af;
  margin-bottom: 4px;
}
.stat-value {
  font-size: 15px;
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

.section {
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.section-header {
  font-size: 12px;
  font-weight: 500;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.mini-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 13px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  overflow: hidden;
}
.mini-table thead {
  background: #f9fafb;
}
.mini-table thead th {
  padding: 8px 12px;
  text-align: left;
  font-weight: 500;
  font-size: 12px;
  color: #6b7280;
  border-bottom: 1px solid #e5e7eb;
}
.mini-table thead .num-col {
  text-align: right;
}
.mini-table tbody tr {
  border-bottom: 1px solid #f3f4f6;
}
.mini-table tbody tr:last-child {
  border-bottom: none;
}
.mini-table td {
  padding: 8px 12px;
  color: #374151;
}
.num-td {
  text-align: right;
  font-family: monospace;
}

.deduction-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  overflow: hidden;
}
.ded-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 9px 14px;
  border-bottom: 1px solid #f3f4f6;
}
.ded-item:nth-last-child(-n + 2) {
  border-bottom: none;
}
.ded-item:nth-child(odd) {
  border-right: 1px solid #f3f4f6;
}
.ded-label {
  font-size: 13px;
  color: #6b7280;
}
.ded-val {
  font-size: 13px;
  font-family: monospace;
  font-weight: 500;
  color: #a32d2d;
}
.ded-val.red {
  color: #a32d2d;
}

.threshold-note {
  padding: 10px 14px;
  border-radius: 8px;
  font-size: 13px;
  line-height: 1.5;
}
.threshold-note.ok {
  background: #e1f5ee;
  color: #085041;
}
.threshold-note.info {
  background: #e6f1fb;
  color: #0c447c;
}

.section-toggle {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 0;
  border: none;
  background: transparent;
  cursor: pointer;
  text-align: left;
}
.section-toggle .section-header {
  margin: 0;
}
.toggle-meta {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
}
.toggle-summary {
  font-size: 12px;
  color: #6b7280;
  font-family: monospace;
}
.chevron {
  color: #9ca3af;
  transition: transform 0.2s ease;
}
.chevron.open {
  transform: rotate(180deg);
}
.chevron-sm {
  color: #9ca3af;
  transition: transform 0.2s ease;
  flex-shrink: 0;
}
.chevron-sm.open {
  transform: rotate(90deg);
}

.ytd-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}
.ytd-chip {
  display: flex;
  flex-direction: column;
  gap: 2px;
  background: #f9fafb;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 8px 12px;
  min-width: 88px;
}
.ytd-chip-label {
  font-size: 11px;
  color: #9ca3af;
}
.ytd-chip-val {
  font-size: 13px;
  font-family: monospace;
  font-weight: 500;
  color: #374151;
}

.history-panel {
  margin-top: 4px;
}
.history-table .month-row {
  cursor: pointer;
}
.history-table .month-row:hover {
  background: #f9fafb;
}
.history-table .month-row.expanded {
  background: #f3f4f6;
}
.month-cell {
  vertical-align: middle;
}
.month-toggle {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-weight: 500;
  color: #374151;
}
.period-count {
  display: block;
  font-size: 11px;
  color: #9ca3af;
  margin-top: 2px;
  padding-left: 18px;
}
.period-row {
  background: #fafafa;
}
.period-label {
  padding: 6px 12px 6px 28px;
  font-size: 12px;
  color: #6b7280;
}
.period-val {
  font-size: 12px;
  color: #6b7280;
}
.month-total {
  font-weight: 500;
}
.history-table tfoot .ytd-row {
  background: #f9fafb;
  border-top: 2px solid #e5e7eb;
}
.history-table tfoot td {
  padding: 10px 12px;
}

.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px;
  gap: 14px;
  color: #9ca3af;
  font-size: 13px;
  flex: 1;
}
.spinner {
  width: 28px;
  height: 28px;
  border: 3px solid #e5e7eb;
  border-top-color: #185fa5;
  border-radius: 50%;
  animation: spin 0.9s linear infinite;
}
@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
</style>
