<template>
  <PageScaffold
    title="COS Payroll"
    subtitle="Execute and manage payroll processing for Contract of Service employees"
    :breadcrumbs="[
      { label: 'Payroll Module', to: '/' },
      { label: 'COS Payroll' },
    ]"
  >
    <!-- Full-screen processing / posting overlay -->
    <Teleport to="body">
      <Transition name="overlay-fade">
        <div
          v-if="processingOverlay.active"
          class="cos-processing-overlay"
          role="status"
          aria-live="polite"
        >
          <div class="cos-processing-card">
            <div class="cos-processing-spinner" />
            <h3 class="cos-processing-period">
              {{ processingOverlay.periodName }}
            </h3>
            <p class="cos-processing-message">
              {{ processingOverlay.message }}
            </p>
            <p v-if="processingOverlay.subMessage" class="cos-processing-sub">
              {{ processingOverlay.subMessage }}
            </p>
          </div>
        </div>
      </Transition>
    </Teleport>

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
        COS entries
      </button>
    </div>

    <!-- Periods tab -->
    <div v-show="activeTab === 'periods'">
      <COSPayrollPeriodsTable
        :data="cosPayrollPeriods"
        :loading="periodsLoading"
        :processing-period-id="processingOverlay.periodId"
        @view="handleViewSummary"
        @unpost="handleUnpostPayroll"
        @refresh="loadPayrollPeriods"
      />
    </div>

    <!-- COS Entries tab -->
    <div v-show="activeTab === 'summary'">
      <template v-if="selectedPayrollPeriod">
        <div class="half-tabs" v-if="showHalfTabs">
          <button
            class="half-tab"
            :class="{ active: selectedHalf === 'first', disabled: !hasFirstHalfPeriod }"
            :disabled="!hasFirstHalfPeriod"
            @click="handleHalfTabChange('first')"
          >
            First half
          </button>
          <button
            class="half-tab"
            :class="{ active: selectedHalf === 'second', disabled: !hasSecondHalfPeriod }"
            :disabled="!hasSecondHalfPeriod"
            @click="handleHalfTabChange('second')"
          >
            Second half
          </button>
        </div>

        <COSPayrollSummary
          :summary-data="activeHalfPeriod"
          :half-info="halfInfo"
          :selected-half="selectedHalf"
          :employees="employeesForHalf"
          :approved-count="approvedEmployeesForHalf.length"
          :skipped-count="unapprovedEmployeesForHalf.length"
          :processed-for-post="isActiveHalfProcessed"
          :processing="
            processingOverlay.active &&
            processingOverlay.periodId === activeHalfPeriod?.id
          "
          :can-post="canPostActiveHalf"
          @process-payroll="handleProcessPayroll(activeHalfPeriod)"
          @leave-earned-details="handleLeaveEarnedDetails"
          @adjust-tax="handleAdjustTax"
          @post-payroll="handlePostPayroll(activeHalfPeriod)"
        />

        <COSPayrollEntriesTable
          :employees="employeesForHalf"
          :loading="cosLoading"
          :period-label="activeHalfPeriod?.payroll || selectedPayrollPeriod.payroll"
          @view-details="handleViewEmployeeDetails"
        />
      </template>
      <div v-else class="empty-tab">
        <svg width="36" height="36" viewBox="0 0 36 36" fill="none">
          <rect x="5" y="7" width="26" height="22" rx="3" stroke="#d1d5db" stroke-width="1.5" />
          <path d="M11 13h14M11 18h9" stroke="#d1d5db" stroke-width="1.5" stroke-linecap="round" />
        </svg>
        <p>Select a COS payroll period to view entries.</p>
      </div>
    </div>

    <COSPayrollWorkDetailsDialog
      v-model="showWorkDetailsDialog"
      :employee="selectedEmployee"
    />

    <COSPayrollTaxAdjustmentDialog
      v-model="showTaxAdjustmentDialog"
      :payroll-period-id="activeHalfPeriod?.id || selectedPayrollPeriod?.id"
      :period-label="activeHalfPeriod?.payroll || selectedPayrollPeriod?.payroll || ''"
      @adjustments-saved="handleAdjustmentsSaved"
    />
  </PageScaffold>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from "vue";
import { ElMessage } from "element-plus";
import PageScaffold from "../../components/PageScaffold.vue";
import COSPayrollPeriodsTable from "../../components/COS_Payroll/COSPayrollPeriodsTable.vue";
import COSPayrollSummary from "../../components/COS_Payroll/COSPayrollSummary.vue";
import COSPayrollEntriesTable from "../../components/COS_Payroll/COSPayrollEntriesTable.vue";
import COSPayrollWorkDetailsDialog from "../../components/COS_Payroll/COSPayrollWorkDetailsDialog.vue";
import COSPayrollTaxAdjustmentDialog from "../../components/COS_Payroll/COSPayrollTaxAdjustmentDialog.vue";
import { useCOSPayroll } from "../../Composables/useCOSPayroll";
import { useCOSPayrollPeriods } from "../../Composables/useCOSPayrollPeriods";
import { cosPayrollApi } from "../../services/api";

const {
  loading: periodsLoading,
  periods: cosPayrollPeriods,
  loadPeriods,
} = useCOSPayrollPeriods();

const {
  loading: cosLoading,
  employeesForHalf,
  employeesAllForHalf,
  halfInfo,
  selectedHalf,
  hasFirstHalfPeriod,
  hasSecondHalfPeriod,
  loadEntriesForPeriod,
  runProcess,
  setSelectedHalf,
  resolveHalfPeriodId,
} = useCOSPayroll();

const activeTab = ref("periods");
const selectedPayrollPeriod = ref(null);
const showWorkDetailsDialog = ref(false);
const showTaxAdjustmentDialog = ref(false);
const selectedEmployee = ref(null);
const processedPeriodIds = ref(new Set());

const markPeriodProcessed = (periodId) => {
  const id = Number(periodId);
  if (!id) return;
  const next = new Set(processedPeriodIds.value);
  next.add(id);
  processedPeriodIds.value = next;
};

const showHalfTabs = computed(
  () => hasFirstHalfPeriod.value || hasSecondHalfPeriod.value,
);

const activeHalfPeriod = computed(() => {
  const halfPeriodId = resolveHalfPeriodId(selectedHalf.value);
  if (halfPeriodId) {
    const match = cosPayrollPeriods.value.find(
      (p) => Number(p.id) === Number(halfPeriodId),
    );
    if (match) return match;
  }
  return selectedPayrollPeriod.value;
});

const isActiveHalfProcessed = computed(() => {
  const id = Number(activeHalfPeriod.value?.id);
  return id > 0 && processedPeriodIds.value.has(id);
});

const canPostActiveHalf = computed(() => {
  const hasApproved = employeesForHalf.value.some((row) => row.isTaskApproved);
  return isActiveHalfProcessed.value && hasApproved;
});

const approvedEmployeesForHalf = computed(() => employeesForHalf.value);

const unapprovedEmployeesForHalf = computed(() =>
  employeesAllForHalf.value.filter((row) => !row.isTaskApproved || row.isOnHold),
);

const handleHalfTabChange = (half) => {
  if (selectedHalf.value === half) return;
  setSelectedHalf(half);
};

const processingOverlay = reactive({
  active: false,
  periodId: null,
  periodName: "",
  message: "",
  subMessage: "",
});

const showOverlay = (periodId, periodName, message, subMessage = "") => {
  processingOverlay.active = true;
  processingOverlay.periodId = periodId;
  processingOverlay.periodName = periodName;
  processingOverlay.message = message;
  processingOverlay.subMessage = subMessage;
};

const updateOverlayMessage = (message, subMessage = "") => {
  processingOverlay.message = message;
  if (subMessage !== undefined) processingOverlay.subMessage = subMessage;
};

const hideOverlay = () => {
  processingOverlay.active = false;
  processingOverlay.periodId = null;
  processingOverlay.periodName = "";
  processingOverlay.message = "";
  processingOverlay.subMessage = "";
};

const loadPayrollPeriods = async () => {
  try {
    await loadPeriods();
  } catch (error) {
    console.error("Error loading COS payroll periods:", error);
  }
};

const handleViewSummary = (payrollPeriod) => {
  selectedPayrollPeriod.value = payrollPeriod;
  activeTab.value = "summary";
  loadEntriesForPeriod(payrollPeriod.id);
};

const handleProcessPayroll = async (payrollPeriod) => {
  const periodName = payrollPeriod.payroll || "Selected period";
  showOverlay(payrollPeriod.id, periodName, "Loading COS employees…", "Applying attendance deductions and COS fees");
  try {
    selectedPayrollPeriod.value = payrollPeriod;
    await runProcess(payrollPeriod.id);
    activeTab.value = "summary";
    markPeriodProcessed(payrollPeriod.id);
    hideOverlay();
    ElMessage.success("COS payroll processed. You can now Post.");
  } catch (error) {
    hideOverlay();
    console.error("Error processing COS payroll:", error);
    ElMessage.error(error.response?.data?.message || "Failed to process COS payroll for this period.");
  }
};

const handlePostPayroll = async (payrollPeriod) => {
  const periodName = payrollPeriod.payroll || "Selected period";
  showOverlay(payrollPeriod.id, periodName, "Posting payroll period…", "Locking period for payslips");
  const steps = [
    { msg: "Posting payroll period…", sub: "Locking period for payslips" },
    { msg: "Generating payslips for employees…", sub: "Creating payroll summaries" },
    { msg: "Finalizing…", sub: "Almost done" },
  ];
  let stepIndex = 0;
  const intervalId = setInterval(() => {
    if (!processingOverlay.active) return;
    const step = steps[stepIndex % steps.length];
    updateOverlayMessage(step.msg, step.sub);
    stepIndex++;
  }, 1400);
  try {
    selectedPayrollPeriod.value = payrollPeriod;
    const response = await cosPayrollApi.postPeriod(payrollPeriod.id);
    clearInterval(intervalId);
    hideOverlay();
    const data = response.data?.data || {};
    const count = data.employees_processed || 0;
    const skipped = data.employees_skipped || 0;
    const backendMessage = response.data?.message;
    ElMessage.success(
      backendMessage ||
        (skipped > 0
          ? `COS payroll posted for ${count} approved employee(s). ${skipped} employee(s) without approval were skipped.`
          : `COS payroll posted. ${count} employee(s) can now view their payslips.`),
    );
    await loadPayrollPeriods();
  } catch (error) {
    clearInterval(intervalId);
    hideOverlay();
    console.error("Error posting COS payroll:", error);
    ElMessage.error(error.response?.data?.message || "Failed to post COS payroll period.");
  }
};

const handleUnpostPayroll = async (payrollPeriod) => {
  showOverlay(payrollPeriod.id, payrollPeriod.payroll || "Selected period", "Unposting payroll period…", "Reverting payslip lock");
  try {
    await cosPayrollApi.unpostPeriod(payrollPeriod.id);
    hideOverlay();
    ElMessage.success("COS payroll period unposted successfully.");
    await loadPayrollPeriods();
  } catch (error) {
    hideOverlay();
    console.error("Error unposting COS payroll:", error);
    ElMessage.error(error.response?.data?.message || "Failed to unpost period.");
  }
};

const handleViewEmployeeDetails = (employee) => {
  selectedEmployee.value = employee;
  showWorkDetailsDialog.value = true;
};

const handleLeaveEarnedDetails = () => {
  ElMessage.info("Leave Earned Details for COS payroll – coming soon.");
};

const handleAdjustTax = () => {
  showTaxAdjustmentDialog.value = true;
};

const handleAdjustmentsSaved = async () => {
  const period = activeHalfPeriod.value;
  if (period?.id) {
    await runProcess(period.id);
    markPeriodProcessed(period.id);
  }
};

onMounted(() => {
  loadPayrollPeriods();
});
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
  transition: color 0.15s, border-color 0.15s;
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
  transition: background 0.12s, color 0.12s;
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
.half-tab.disabled,
.half-tab:disabled {
  opacity: 0.55;
  cursor: not-allowed;
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

/* Processing overlay */
.cos-processing-overlay {
  position: fixed;
  inset: 0;
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(4px);
}
.cos-processing-card {
  background: #fff;
  border-radius: 16px;
  padding: 40px 48px;
  text-align: center;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
  min-width: 360px;
}
.cos-processing-spinner {
  width: 56px;
  height: 56px;
  margin: 0 auto 24px;
  border: 4px solid #e8e8e8;
  border-top-color: #111827;
  border-radius: 50%;
  animation: cos-spin 0.9s linear infinite;
}
@keyframes cos-spin {
  to { transform: rotate(360deg); }
}
.cos-processing-period {
  margin: 0 0 12px;
  font-size: 18px;
  font-weight: 600;
  color: #111827;
}
.cos-processing-message {
  margin: 0 0 8px;
  font-size: 15px;
  color: #374151;
}
.cos-processing-sub {
  margin: 0;
  font-size: 13px;
  color: #9ca3af;
}
.overlay-fade-enter-active,
.overlay-fade-leave-active {
  transition: opacity 0.2s ease;
}
.overlay-fade-enter-from,
.overlay-fade-leave-to {
  opacity: 0;
}
</style>
