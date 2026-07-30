<template>
  <PageScaffold
    title="Payroll Process"
    subtitle="Execute and manage payroll processing workflows"
    :breadcrumbs="[
      { label: 'Payroll Module', to: '/' },
      { label: 'Payroll Process' },
    ]"
  >
    <!-- Tab bar -->
    <div class="tab-bar">
      <button
        class="tab-btn"
        :class="{ active: activeTab === 'periods' }"
        @click="activeTab = 'periods'"
      >
        Payroll periods
      </button>
      <button
        class="tab-btn"
        :class="{
          active: activeTab === 'summary',
          disabled: !selectedPayrollPeriod,
        }"
        :disabled="!selectedPayrollPeriod"
        @click="selectedPayrollPeriod && (activeTab = 'summary')"
      >
        Payroll summary
      </button>
    </div>

    <!-- Periods tab -->
    <div v-show="activeTab === 'periods'">
      <PayrollProcessTable
        :data="groupedPayrollPeriods"
        :loading="loading"
        @view="handleViewSummary"
        @process="handleProcessPayroll"
        @post="handlePostPayroll"
        @unpost="handleUnpostPayroll"
        @print-report="handlePrintReport"
        @tabulated-report="handleTabulatedReport"
        @refresh="loadPayrollPeriods"
        @print="handlePrintSelected"
        @export="handleExportSelected"
      />
    </div>

    <!-- Summary tab -->
    <div v-show="activeTab === 'summary'">
      <div v-if="selectedPayrollPeriod">
        <!-- Half selector -->
        <div class="half-tabs" v-if="!isSelectedMonthly">
          <button
            class="half-tab"
            :class="{
              active: selectedHalf === 'first',
              disabled: !selectedMonth?.first_half,
            }"
            :disabled="!selectedMonth?.first_half || switchingHalf"
            @click="handleHalfTabChange('first')"
          >
            <span
              v-if="switchingHalf && selectedHalf !== 'first'"
              class="half-tab-spinner"
              aria-hidden="true"
            ></span>
            First half
          </button>
          <button
            class="half-tab"
            :class="{
              active: selectedHalf === 'second',
              disabled: !selectedMonth?.second_half,
            }"
            :disabled="!selectedMonth?.second_half || switchingHalf"
            @click="handleHalfTabChange('second')"
          >
            <span
              v-if="switchingHalf && selectedHalf !== 'second'"
              class="half-tab-spinner"
              aria-hidden="true"
            ></span>
            Second half
          </button>
        </div>
        <div class="half-tabs" v-else>
          <button class="half-tab active">Monthly</button>
        </div>

        <PayrollProcessSummary
          :summary-data="{
            ...(summaryData || {}),
            selected_period: selectedPayrollPeriod,
          }"
          :summary-cache-version="summaryCacheVersion"
          :loading="loading"
          :posting="posting"
          @leave-earned-details="handleLeaveEarnedDetails"
          @adjust-tax="handleAdjustTax"
          @salary-adjust="handleSalaryAdjust"
          @process-payroll="handleSummaryProcessPayroll"
          @print-summary="handlePrintSummary"
          @export-data="handleExportData"
          @refresh="loadPayrollSummary"
          @show-all="handleShowAll"
          @show-less-than-5000="handleShowLessThan5000"
          @post-payroll="handlePostPayroll"
          @unpost-payroll="handleUnpostPayroll"
        />
      </div>
      <div v-else class="empty-tab">
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
        <p>Select a payroll period to view the summary.</p>
      </div>
    </div>

    <!-- Dialogs -->
    <PayrollProcessDialog
      v-model="showProcessDialog"
      :payroll-data="processDialogPayrollData"
      :employee-count="effectiveEmployeeCount"
      @process-complete="handleProcessComplete"
    />
    <PayrollAdjustmentDialog
      v-model="showAdjustmentDialog"
      :payroll-data="summaryData"
      :employee-for-tax-adjustments="
        summaryData?.employee_for_tax_adjustments || []
      "
      @adjustments-saved="handleAdjustmentsSaved"
    />
    <PayrollSalaryAdjustmentDialog
      v-model="showSalaryAdjustmentDialog"
      :payroll-period-id="selectedPayrollPeriod?.id"
      :employees="summaryData?.payrolls || []"
      @saved="handleSalaryAdjustmentsSaved"
    />
    <LeaveEarnedDetailsDialog
      v-model="showLeaveEarnedDialog"
      :payroll-period="selectedPayrollPeriod?.payroll || ''"
      :payroll-period-id="selectedPayrollPeriod?.id"
      :loading="loading"
      @refresh="loadPayrollSummary"
      @print="handlePrintLeaveEarned"
      @export="handleExportLeaveEarned"
    />
  </PageScaffold>
</template>

<script setup>
import { ref, onMounted, computed } from "vue";
import PageScaffold from "../../components/PageScaffold.vue";
import PayrollProcessTable from "../../components/Payroll_Process/PayrollProcessTable.vue";
import PayrollProcessSummary from "../../components/Payroll_Process/PayrollProcessSummary.vue";
import PayrollProcessDialog from "../../components/Payroll_Process/PayrollProcessDialog.vue";
import PayrollAdjustmentDialog from "../../components/Payroll_Process/PayrollAdjustmentDialog.vue";
import PayrollSalaryAdjustmentDialog from "../../components/Payroll_Process/PayrollSalaryAdjustmentDialog.vue";
import LeaveEarnedDetailsDialog from "../../components/Payroll_Process/LeaveEarnedDetailsDialog.vue";
import { usePayrollProcess } from "../../Composables/usePayrollProcess";

const {
  loading,
  processing,
  payrollPeriods,
  summaryData,
  getPayrollPeriods,
  getPayrollSummary,
  summaryCacheVersion,
  getPayrollSummaryData,
  generateGeneralPayrollReport,
  generateDetailedPayrollReport,
  confirmPostPayroll,
  confirmUnpostPayroll,
} = usePayrollProcess();

const activeTab = ref("periods");
const selectedMonth = ref(null);
const selectedHalf = ref("first");
const selectedPayrollPeriod = ref(null);
const processDialogPayrollData = ref(null);
const showProcessDialog = ref(false);
const showAdjustmentDialog = ref(false);
const showSalaryAdjustmentDialog = ref(false);
const showLeaveEarnedDialog = ref(false);
const posting = ref(false);
const switchingHalf = ref(false);

const normalizeCutoffName = (p) =>
  String(p?.cutoff_name || p?.cutoffName || "")
    .trim()
    .toLowerCase();
const isMonthlyPeriod = (p) => normalizeCutoffName(p).includes("monthly");
const isSelectedMonthly = computed(() => {
  const p = selectedPayrollPeriod.value;
  const m = selectedMonth.value;
  return (
    isMonthlyPeriod(p) ||
    isMonthlyPeriod(m?.first_half) ||
    isMonthlyPeriod(m?.second_half)
  );
});

const effectiveEmployeeCount = computed(() => {
  const sd = summaryData.value;
  const eligible =
    sd?.eligible_employee_count ?? sd?.time_data_employee_count ?? 0;
  const fromPayrolls = sd?.payrolls?.length ?? 0;
  return Math.max(eligible, fromPayrolls);
});

const groupedPayrollPeriods = computed(() => {
  const periods = payrollPeriods.value || [];
  const map = new Map();
  periods.forEach((p) => {
    const releaseDate = p.release_date ? new Date(p.release_date) : null;
    const year = releaseDate ? releaseDate.getFullYear() : null;
    const month = releaseDate ? releaseDate.getMonth() + 1 : null;
    const key = `${p.payroll_interval_id || 0}-${year}-${month}`;
    let entry = map.get(key);
    if (!entry) {
      entry = {
        id: key,
        payroll_interval_id: p.payroll_interval_id || 0,
        year,
        month,
        payroll: p.payroll,
        employment_types: p.employment_types || [],
        employee_count: 0,
        attendance_start_date: p.attendance_start_date,
        attendance_end_date: p.attendance_end_date,
        release_date: p.release_date,
        first_half: null,
        second_half: null,
        periods_in_month: [],
        period_ids: [],
        posted: false,
        partial: false,
      };
      map.set(key, entry);
    }
    const c = String(p.cutoff_name || "").toLowerCase();
    if (isMonthlyPeriod(p)) {
      // Combined monthly cutoff row — not a semi-monthly half.
    } else if (c.includes("2nd") || c.includes("second")) {
      if (
        !entry.second_half ||
        Number(p.employee_count || 0) >
          Number(entry.second_half.employee_count || 0)
      ) {
        entry.second_half = p;
      }
    } else if (c.includes("1st") || c.includes("first")) {
      if (
        !entry.first_half ||
        Number(p.employee_count || 0) >
          Number(entry.first_half.employee_count || 0)
      ) {
        entry.first_half = p;
      }
    } else if (!entry.first_half) {
      entry.first_half = p;
    }
    entry.periods_in_month.push(p);
    entry.period_ids.push(p.id);
    entry.employee_count = Math.max(
      entry.employee_count,
      Number(p.employee_count || 0),
    );
    const starts = [entry.attendance_start_date, p.attendance_start_date]
      .filter(Boolean)
      .sort();
    if (starts.length) entry.attendance_start_date = starts[0];
    const ends = [entry.attendance_end_date, p.attendance_end_date]
      .filter(Boolean)
      .sort();
    if (ends.length) entry.attendance_end_date = ends[ends.length - 1];
    const releases = [entry.release_date, p.release_date]
      .filter(Boolean)
      .sort();
    if (releases.length) entry.release_date = releases[releases.length - 1];
  });
  const result = Array.from(map.values()).map((entry) => {
    const sorted = [...(entry.periods_in_month || [])].sort((a, b) =>
      (a.attendance_start_date || "").localeCompare(
        b.attendance_start_date || "",
      ),
    );
    const first = entry.first_half || sorted[0] || null;
    const second = entry.second_half || sorted[1] || null;
    const all = [first, second].filter(Boolean);
    const anyPosted = all.some((per) => per?.posted);
    const allPosted = all.length > 0 && all.every((per) => per?.posted);
    return {
      ...entry,
      first_half: first,
      second_half: second,
      posted: allPosted,
      partial: anyPosted && !allPosted,
      employment_types:
        first?.employment_types || second?.employment_types || [],
    };
  });
  result.sort((a, b) =>
    !a.release_date || !b.release_date
      ? 0
      : new Date(a.release_date) - new Date(b.release_date),
  );
  return result;
});

const resolveCurrentHalfPeriod = () => {
  if (!selectedMonth.value) return null;
  const m = selectedMonth.value;
  const preferred =
    selectedHalf.value === "monthly"
      ? m.first_half || m.second_half || null
      : selectedHalf.value === "second"
        ? m.second_half || m.first_half || null
        : m.first_half || m.second_half || null;

  if (!preferred?.id) return preferred;

  // Always resolve from the latest list so `posted` reflects real-time updates.
  const latest = (payrollPeriods.value || []).find(
    (p) => String(p.id) === String(preferred.id),
  );
  return latest || preferred;
};

const loadPayrollPeriods = async () => {
  try {
    await getPayrollPeriods();
  } catch (e) {
    console.error(e);
  }
};
const loadPayrollSummary = async ({ forceRefresh = false } = {}) => {
  if (!selectedPayrollPeriod.value) return;
  try {
    await getPayrollSummary(selectedPayrollPeriod.value.id, { forceRefresh });
  } catch (e) {
    console.error(e);
  }
};
const switchHalf = async (half) => {
  try {
    switchingHalf.value = true;
    selectedHalf.value = half;
    selectedPayrollPeriod.value = resolveCurrentHalfPeriod();
    if (selectedPayrollPeriod.value) {
      await loadPayrollSummary({ forceRefresh: true });
    }
  } finally {
    switchingHalf.value = false;
  }
};

const handleViewSummary = (row) => {
  selectedMonth.value = row;
  selectedHalf.value = isMonthlyPeriod(row?.first_half)
    ? "monthly"
    : row?.first_half
      ? "first"
      : "second";
  selectedPayrollPeriod.value = resolveCurrentHalfPeriod();
  activeTab.value = "summary";
  loadPayrollSummary({ forceRefresh: true });
};
const handleProcessPayroll = async (p) => {
  selectedPayrollPeriod.value = p;
  processDialogPayrollData.value = p;
  showProcessDialog.value = true;
  await loadPayrollSummary();
};
const handlePostPayroll = async (p) => {
  try {
    posting.value = true;
    selectedPayrollPeriod.value = p;
    activeTab.value = "summary";
    await loadPayrollSummary();
    const ok = await confirmPostPayroll(p);
    if (!ok) return;
    // Optimistic UI: flip status immediately, then sync after reloads.
    if (selectedPayrollPeriod.value?.id === p.id) {
      selectedPayrollPeriod.value = { ...selectedPayrollPeriod.value, posted: true };
    }
    await loadPayrollPeriods();
    const updated = payrollPeriods.value.find((x) => x.id === p.id);
    if (updated) {
      selectedPayrollPeriod.value = updated;
      await loadPayrollSummary();
    }
  } catch {}
  finally {
    posting.value = false;
  }
};
const handleUnpostPayroll = async (p) => {
  try {
    posting.value = true;
    // Ensure the summary button reflects the action immediately.
    selectedPayrollPeriod.value = p;
    const ok = await confirmUnpostPayroll(p);
    if (!ok) return;
    // Optimistic UI: flip status immediately, then sync after reloads.
    selectedPayrollPeriod.value = { ...(selectedPayrollPeriod.value || {}), posted: false };
    await loadPayrollPeriods();
    const updated = payrollPeriods.value.find(
      (x) => String(x.id) === String(p.id),
    );
    if (updated) {
      selectedPayrollPeriod.value = updated;
    }
    await loadPayrollSummary();
  } catch (e) {
    console.error(e);
  } finally {
    posting.value = false;
  }
};
const handlePrintReport = (p) => console.log("Print report:", p);
const handleTabulatedReport = (p) => console.log("Tabulated report:", p);
const handlePrintSelected = (rows) => console.log("Print selected:", rows);
const handleExportSelected = (rows) => console.log("Export selected:", rows);
const handleLeaveEarnedDetails = () => {
  showLeaveEarnedDialog.value = true;
};
const handleAdjustTax = () => {
  showAdjustmentDialog.value = true;
};
const handleSalaryAdjust = () => {
  showSalaryAdjustmentDialog.value = true;
};
const handlePrintSummary = () => console.log("Print summary");
const handleExportData = () => console.log("Export data");
const handleShowAll = () => console.log("Show all");
const handleShowLessThan5000 = () => console.log("Show < 5000");
const handlePrintLeaveEarned = () => console.log("Print leave earned");
const handleExportLeaveEarned = () => console.log("Export leave earned");
const handleProcessComplete = async () => {
  await loadPayrollPeriods();
  if (selectedMonth.value)
    selectedPayrollPeriod.value = resolveCurrentHalfPeriod();
  if (selectedPayrollPeriod.value) {
    await loadPayrollSummary({ forceRefresh: true });
  }
  processDialogPayrollData.value = selectedPayrollPeriod.value;
};
const handleAdjustmentsSaved = () => loadPayrollSummary({ forceRefresh: true });
const handleSalaryAdjustmentsSaved = () =>
  loadPayrollSummary({ forceRefresh: true });
const handleSummaryProcessPayroll = () => {
  const p = resolveCurrentHalfPeriod();
  if (!p?.id) return;
  processDialogPayrollData.value = p;
  selectedPayrollPeriod.value = p;
  showProcessDialog.value = true;
};
const handleHalfTabChange = async (name) => {
  await switchHalf(name);
};

onMounted(loadPayrollPeriods);
</script>

<style scoped>
.tab-bar {
  display: flex;
  gap: 0;
  border-bottom: 2px solid #f3f4f6;
  margin-bottom: 20px;
}
.tab-btn {
  background: transparent;
  border: none;
  border-bottom: 2px solid transparent;
  margin-bottom: -2px;
  padding: 10px 20px;
  font-size: 13px;
  font-weight: 500;
  color: #9ca3af;
  cursor: pointer;
  transition:
    color 0.15s,
    border-color 0.15s;
}
.tab-btn.active {
  color: #111827;
  border-bottom-color: #409eff;
}
.tab-btn:hover:not(.active):not(.disabled) {
  color: #374151;
}
.tab-btn.disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.half-tabs {
  display: flex;
  gap: 0;
  margin-bottom: 16px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  overflow: hidden;
  width: fit-content;
}
.half-tab {
  background: #fff;
  border: none;
  border-right: 1px solid #e5e7eb;
  padding: 7px 18px;
  font-size: 13px;
  color: #6b7280;
  cursor: pointer;
  transition:
    background 0.12s,
    color 0.12s;
}
.half-tab:last-child {
  border-right: none;
}
.half-tab.active {
  background: #409eff;
  color: #fff;
}
.half-tab:hover:not(.active):not(.disabled) {
  background: #f3f4f6;
}
.half-tab.disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.half-tab:disabled {
  opacity: 0.55;
  cursor: not-allowed;
}

.half-tab-spinner {
  width: 12px;
  height: 12px;
  display: inline-block;
  margin-right: 8px;
  border-radius: 999px;
  border: 2px solid rgba(107, 114, 128, 0.35);
  border-top-color: rgba(107, 114, 128, 0.95);
  animation: halfSpin 0.8s linear infinite;
  vertical-align: -2px;
}

.half-tab.active .half-tab-spinner {
  border-color: rgba(255, 255, 255, 0.35);
  border-top-color: rgba(255, 255, 255, 0.95);
}

@keyframes halfSpin {
  to {
    transform: rotate(360deg);
  }
}

.empty-tab {
  text-align: center;
  padding: 60px 0;
  color: #9ca3af;
  font-size: 14px;
}
.empty-tab svg {
  margin: 0 auto 12px;
  display: block;
}
</style>
