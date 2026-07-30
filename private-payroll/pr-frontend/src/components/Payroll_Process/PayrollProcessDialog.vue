<!-- PayrollProcessDialog.vue -->
<template>
  <Teleport to="body">
    <Transition name="fade">
      <div
        v-if="visible"
        class="modal-backdrop"
        @click.self="!isProcessing && !processing && handleClose()"
      >
        <div class="modal-card process-card">
          <div class="modal-header">
            <h3 class="modal-title">{{ dialogTitle }}</h3>
            <button
              class="close-btn"
              :disabled="isProcessing || processing"
              @click="handleClose"
            >
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
            <!-- Processing progress -->
            <div v-if="isProcessing" class="process-status">
              <div class="spinner-row">
                <div class="spinner"></div>
                <span class="spinner-msg"
                  >Processing payroll — please wait. This may take a few
                  minutes.</span
                >
              </div>
              <div class="progress-track">
                <div
                  class="progress-fill"
                  :class="
                    processStatus === 'exception'
                      ? 'err'
                      : processStatus === 'success'
                        ? 'ok'
                        : ''
                  "
                  :style="{ width: processProgress + '%' }"
                ></div>
              </div>
              <div class="progress-label">{{ processProgress }}%</div>
            </div>

            <!-- Period info -->
            <div class="section-card">
              <div class="section-title">Payroll period information</div>
              <div class="info-grid">
                <div class="info-item">
                  <span class="info-label">Period</span
                  ><span class="info-val">{{ batchPeriodLabel || "N/A" }}</span>
                </div>
                <div class="info-item">
                  <span class="info-label">Attendance period</span
                  ><span class="info-val">{{
                    formatDateRange(batchAttendanceStart, batchAttendanceEnd)
                  }}</span>
                </div>
                <div class="info-item">
                  <span class="info-label">Release date</span
                  ><span class="info-val">{{
                    formatDate(batchReleaseDate)
                  }}</span>
                </div>
              </div>
            </div>

            <!-- Tax table -->
            <div class="section-card">
              <div class="section-title">Tax table</div>
              <div v-if="isSemiMonthlyHalfPeriod" class="section-sub tax-fixed-note">
                First / second half payroll always uses
                <strong>annualized fixed withholding</strong> (TRAIN annual
                table → monthly tax ÷ 2 per half). Not affected by attendance.
                The options below apply only to non–semi-monthly payroll runs.
              </div>
              <div v-else class="section-sub">
                Choose which tax table to use for the calculation.
              </div>
              <div class="radio-group" :class="{ disabled: isSemiMonthlyHalfPeriod }">
                <label
                  class="radio-chip"
                  :class="{ active: selectedTaxTable === 1 }"
                >
                  <input
                    type="radio"
                    :value="1"
                    v-model="selectedTaxTable"
                    class="radio-input"
                  />
                  Annual
                </label>
                <label
                  class="radio-chip"
                  :class="{ active: selectedTaxTable === 2 }"
                >
                  <input
                    type="radio"
                    :value="2"
                    v-model="selectedTaxTable"
                    class="radio-input"
                  />
                  Monthly
                </label>
                <label
                  class="radio-chip"
                  :class="{ active: selectedTaxTable === 3 }"
                >
                  <input
                    type="radio"
                    :value="3"
                    v-model="selectedTaxTable"
                    class="radio-input"
                  />
                  Semi-monthly
                </label>
              </div>
            </div>

            <!-- Processing options -->
            <div class="section-card" v-if="!processing">
              <div class="section-title">Processing options</div>
              <div class="section-sub">
                Choose which components to include in this payroll run.
              </div>
              <div class="options-grid">
                <label
                  v-for="opt in optionItems"
                  :key="opt.key"
                  class="option-row"
                  :class="{ active: processOptions[opt.key] }"
                >
                  <div class="option-text">
                    <span class="option-title">{{ opt.title }}</span>
                    <span class="option-desc">{{ opt.description }}</span>
                  </div>
                  <div
                    class="toggle"
                    :class="{ on: processOptions[opt.key] }"
                    @click="processOptions[opt.key] = !processOptions[opt.key]"
                  >
                    <div class="toggle-knob"></div>
                  </div>
                </label>
              </div>
            </div>

            <!-- Validation -->
            <div class="section-card" v-if="!processing">
              <div class="section-title">Validation checks</div>
              <div class="validation-grid">
                <div class="val-item">
                  <span class="val-label">Total employees</span
                  ><strong class="val-value">{{ employeeCount }}</strong>
                </div>
                <div class="val-item">
                  <span class="val-label">GSIS setup</span
                  ><span class="val-tag" :class="gsisSetup ? 'ok' : 'err'">{{
                    gsisSetup ? "Configured" : "Missing"
                  }}</span>
                </div>
                <div class="val-item">
                  <span class="val-label">Tax setup</span
                  ><span class="val-tag" :class="taxSetup ? 'ok' : 'err'">{{
                    taxSetup ? "Configured" : "Missing"
                  }}</span>
                </div>
                <div class="val-item">
                  <span class="val-label">Time data</span
                  ><span
                    class="val-tag"
                    :class="timeDataAvailable ? 'ok' : 'warn'"
                    >{{ timeDataAvailable ? "Available" : "Missing" }}</span
                  >
                </div>
              </div>
            </div>

            <!-- Warnings -->
            <div
              v-for="(w, i) in warnings"
              :key="i"
              class="warning-banner"
              :class="w.type"
            >
              <strong>{{ w.title }}</strong> — {{ w.message }}
            </div>

            <!-- Process log -->
            <div class="section-card" v-if="processLog.length > 0">
              <div class="section-title">Process log</div>
              <div class="log-box">
                <div
                  v-for="(log, i) in processLog"
                  :key="i"
                  class="log-row"
                  :class="log.type"
                >
                  <span class="log-time">{{ log.time }}</span>
                  <span class="log-msg">{{ log.message }}</span>
                </div>
              </div>
            </div>
          </div>

          <div class="modal-footer">
            <button
              class="btn-ghost"
              :disabled="isProcessing || processing"
              @click="handleClose"
            >
              Cancel
            </button>
            <button
              class="btn-primary"
              :disabled="!canProcess"
              @click="handleStartProcess"
            >
              <div class="spinner-sm" v-if="isProcessing || processing"></div>
              {{ isProcessing || processing ? "Processing…" : "Start process" }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch } from "vue";
import { usePayrollProcess } from "../../Composables/usePayrollProcess";

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  payrollData: { type: [Object, Array], default: () => ({}) },
  employeeCount: { type: Number, default: 0 },
});
const emit = defineEmits(["update:modelValue", "process-complete"]);
const { processing, processPayroll } = usePayrollProcess();

const selectedTaxTable = ref(1);
const visible = ref(false);
const isProcessing = ref(false);
const processProgress = ref(0);
const processStatus = ref("");
const processLog = ref([]);
const warnings = ref([]);
const processOptions = ref({
  includeGsis: true,
  // includeSss: true,
  includePagibig: true,
  includePhilhealth: true,
  includeTax: true,
  includeAttendance: true,
  // includeOvertime: true,
  includeHoliday: true,
});
const optionItems = [
  {
    key: "includeGsis",
    title: "Include GSIS contributions",
    description: "Government Service Insurance System contributions.",
  },
  // {
  //   key: "includeSss",
  //   title: "Include SSS contributions",
  //   description:
  //     "Social Security System contributions for applicable employees.",
  // },
  {
    key: "includePagibig",
    title: "Include Pag-IBIG contributions",
    description: "Home Development Mutual Fund (Pag-IBIG) contributions.",
  },
  {
    key: "includePhilhealth",
    title: "Include PhilHealth contributions",
    description: "PhilHealth contributions for healthcare coverage.",
  },
  {
    key: "includeTax",
    title: "Include tax deductions",
    description:
      "Annualized fixed withholding (monthly tax ÷ 2 per half). Not reduced by attendance.",
  },
  {
    key: "includeAttendance",
    title: "Include attendance deductions",
    description:
      "Late, undertime, and absence deductions using attendance data.",
  },
  // {
  //   key: "includeOvertime",
  //   title: "Process overtime",
  //   description: "Compute approved overtime earnings for the period.",
  // },
  {
    key: "includeHoliday",
    title: "Process holiday pay",
    description: "Apply holiday rates and premium pay where applicable.",
  },
];

const batchPeriods = computed(() => {
  if (Array.isArray(props.payrollData)) return props.payrollData;
  if (props.payrollData?.id) return [props.payrollData];
  return [];
});
const periodDisplayLabel = (p) => {
  if (!p) return "Payroll Period";
  const base = p.payroll || "Payroll Period";
  const cutoff = p.cutoff_name || p.cutoffName;
  return cutoff ? `${base} — ${cutoff}` : base;
};
const batchPeriodLabel = computed(() =>
  periodDisplayLabel(batchPeriods.value[0]),
);
const isSemiMonthlyHalfPeriod = computed(() => {
  const p = batchPeriods.value[0];
  if (!p) return false;
  const label = `${p.payroll || ""} ${p.cutoff_name || p.cutoffName || ""}`.toLowerCase();
  return (
    label.includes("first-half") ||
    label.includes("first half") ||
    label.includes("1st") ||
    label.includes("second-half") ||
    label.includes("second half") ||
    label.includes("2nd")
  );
});
const batchAttendanceStart = computed(
  () =>
    batchPeriods.value
      .map((p) => p?.attendance_start_date)
      .filter(Boolean)
      .sort()[0] || null,
);
const batchAttendanceEnd = computed(() => {
  const a = batchPeriods.value
    .map((p) => p?.attendance_end_date)
    .filter(Boolean)
    .sort();
  return a[a.length - 1] || null;
});
const batchReleaseDate = computed(() => {
  const a = batchPeriods.value
    .map((p) => p?.release_date)
    .filter(Boolean)
    .sort();
  return a[a.length - 1] || null;
});
const dialogTitle = computed(() =>
  isProcessing.value || processing.value
    ? "Processing payroll"
    : "Process payroll",
);
const gsisSetup = computed(() => true);
const taxSetup = computed(() => true);
const timeDataAvailable = computed(() => props.employeeCount > 0);
const canProcess = computed(
  () =>
    gsisSetup.value &&
    taxSetup.value &&
    timeDataAvailable.value &&
    !isProcessing.value &&
    !processing.value &&
    warnings.value.length === 0,
);

const formatDate = (d) => {
  if (!d) return "N/A";
  return new Date(d).toLocaleDateString("en-US", {
    year: "numeric",
    month: "short",
    day: "numeric",
  });
};
const formatDateRange = (s, e) =>
  !s || !e ? "N/A" : `${formatDate(s)} – ${formatDate(e)}`;
const addLog = (msg, type = "info") =>
  processLog.value.push({
    time: new Date().toLocaleTimeString(),
    message: msg,
    type,
  });
const validateSetup = () => {
  warnings.value = [];
  if (!gsisSetup.value)
    warnings.value.push({
      title: "GSIS setup missing",
      message: "Configure GSIS settings before processing.",
      type: "error",
    });
  if (!taxSetup.value)
    warnings.value.push({
      title: "Tax setup missing",
      message: "Configure tax settings before processing.",
      type: "error",
    });
  if (!timeDataAvailable.value)
    warnings.value.push({
      title: "Time data missing",
      message: "No time data found. Process attendance first.",
      type: "warning",
    });
  if (props.employeeCount === 0)
    warnings.value.push({
      title: "No employees",
      message: "No active employees found for this period.",
      type: "warning",
    });
};

const handleStartProcess = async () => {
  try {
    isProcessing.value = true;
    processProgress.value = 0;
    processStatus.value = "";
    addLog("Starting payroll process…");
    const opts = processOptions.value;
    const steps = [
      { show: true, msg: "Validating employee data…" },
      { show: true, msg: "Calculating basic salaries…" },
      { show: opts.includeGsis, msg: "Processing GSIS contributions…" },
      // { show: opts.includeSss, msg: "Processing SSS contributions…" },
      { show: opts.includePagibig, msg: "Processing Pag-IBIG contributions…" },
      {
        show: opts.includePhilhealth,
        msg: "Processing PhilHealth contributions…",
      },
      { show: opts.includeTax, msg: "Calculating tax deductions…" },
      {
        show: opts.includeAttendance,
        msg: "Processing attendance deductions…",
      },
      // { show: opts.includeOvertime, msg: "Processing overtime…" },
      { show: opts.includeHoliday, msg: "Processing holiday pay…" },
      { show: true, msg: "Computing net pay…" },
      { show: true, msg: "Finalizing payroll…" },
    ].filter((s) => s.show);
    for (let i = 0; i < steps.length; i++) {
      addLog(steps[i].msg);
      processProgress.value = Math.round(((i + 1) / steps.length) * 100);
      await new Promise((r) => setTimeout(r, 800));
    }
    const p = batchPeriods.value[0];
    if (!p?.id) {
      throw new Error("No payroll period selected.");
    }
    addLog(`Processing payroll: ${periodDisplayLabel(p)}`);
    await processPayroll(p.id, {
      ...opts,
      taxTable: isSemiMonthlyHalfPeriod.value ? 1 : selectedTaxTable.value,
      useAnnualizedTax: isSemiMonthlyHalfPeriod.value,
    });
    addLog("Payroll processing completed successfully!", "success");
    processStatus.value = "success";
    processProgress.value = 100;
    emit("process-complete");
    setTimeout(() => handleClose(), 2000);
  } catch (e) {
    addLog(`Error: ${e.message}`, "error");
    processStatus.value = "exception";
  }
};

const handleClose = () => {
  visible.value = false;
  emit("update:modelValue", false);
  isProcessing.value = false;
  processProgress.value = 0;
  processStatus.value = "";
  processLog.value = [];
  warnings.value = [];
};
watch(
  () => props.modelValue,
  (v) => {
    visible.value = v;
    if (v) validateSetup();
  },
);
watch(visible, (v) => emit("update:modelValue", v));
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
  max-width: 680px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
}
.process-card {
  max-width: 700px;
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
.close-btn:hover:not(:disabled) {
  background: #f3f4f6;
  color: #374151;
}
.modal-body {
  padding: 20px 24px;
  overflow-y: auto;
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 14px;
}
.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  padding: 16px 24px;
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
.btn-ghost:hover:not(:disabled) {
  background: #f3f4f6;
}
.btn-ghost:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
.btn-primary {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: #409eff;
  color: #fff;
  border: none;
  border-radius: 8px;
  padding: 8px 20px;
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

.section-card {
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  padding: 14px 16px;
}
.section-title {
  font-size: 13px;
  font-weight: 500;
  color: #111827;
  margin-bottom: 4px;
}
.section-sub {
  font-size: 12px;
  color: #9ca3af;
  margin-bottom: 12px;
}
.info-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 10px;
}
.info-item {
  display: flex;
  flex-direction: column;
  gap: 3px;
}
.info-label {
  font-size: 11px;
  color: #9ca3af;
  font-weight: 500;
}
.info-val {
  font-size: 13px;
  color: #111827;
  font-weight: 500;
}

.tax-fixed-note {
  color: #374151;
  line-height: 1.45;
}
.radio-group.disabled {
  opacity: 0.55;
  pointer-events: none;
}
.radio-group {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}
.radio-chip {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 7px 14px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  font-size: 13px;
  color: #6b7280;
  cursor: pointer;
  transition: all 0.12s;
}
.radio-chip.active {
  background: #409eff;
  color: #fff;
  border-color: #409eff;
}
.radio-input {
  display: none;
}

.options-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 8px;
  margin-top: 10px;
}
.option-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 12px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  cursor: pointer;
  transition: border-color 0.12s;
  gap: 12px;
}
.option-row.active {
  border-color: #9fe1cb;
  background: #f0fdf4;
}
.option-text {
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.option-title {
  font-size: 13px;
  font-weight: 500;
  color: #111827;
}
.option-desc {
  font-size: 11px;
  color: #9ca3af;
}
.toggle {
  width: 34px;
  height: 20px;
  border-radius: 10px;
  background: #e5e7eb;
  position: relative;
  cursor: pointer;
  flex-shrink: 0;
  transition: background 0.15s;
}
.toggle.on {
  background: #085041;
}
.toggle-knob {
  position: absolute;
  top: 2px;
  left: 2px;
  width: 16px;
  height: 16px;
  border-radius: 50%;
  background: #fff;
  transition: left 0.15s;
}
.toggle.on .toggle-knob {
  left: 16px;
}

.validation-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 10px;
  margin-top: 10px;
}
.val-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  gap: 6px;
}
.val-label {
  font-size: 11px;
  color: #6b7280;
}
.val-value {
  font-size: 20px;
  font-weight: 500;
  color: #111827;
}
.val-tag {
  font-size: 11px;
  font-weight: 500;
  padding: 3px 8px;
  border-radius: 4px;
}
.val-tag.ok {
  background: #e1f5ee;
  color: #085041;
}
.val-tag.err {
  background: #faece7;
  color: #712b13;
}
.val-tag.warn {
  background: #faeeda;
  color: #633806;
}

.warning-banner {
  padding: 10px 14px;
  border-radius: 8px;
  font-size: 13px;
}
.warning-banner.error {
  background: #faece7;
  color: #712b13;
  border: 1px solid #f0997b;
}
.warning-banner.warning {
  background: #faeeda;
  color: #633806;
  border: 1px solid #fac775;
}

.log-box {
  max-height: 180px;
  overflow-y: auto;
  background: #f9fafb;
  border-radius: 6px;
  padding: 10px;
  margin-top: 8px;
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.log-row {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  font-size: 12px;
}
.log-time {
  color: #9ca3af;
  min-width: 72px;
  flex-shrink: 0;
}
.log-msg {
  flex: 1;
}
.log-row.info .log-msg {
  color: #185fa5;
}
.log-row.success .log-msg {
  color: #085041;
}
.log-row.error .log-msg {
  color: #a32d2d;
}

.process-status {
  background: #e6f1fb;
  border: 1px solid #b5d4f4;
  border-radius: 10px;
  padding: 14px 16px;
}
.spinner-row {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 12px;
}
.spinner {
  width: 24px;
  height: 24px;
  border: 3px solid #b5d4f4;
  border-top-color: #185fa5;
  border-radius: 50%;
  animation: spin 0.9s linear infinite;
  flex-shrink: 0;
}
.spinner-sm {
  width: 14px;
  height: 14px;
  border: 2px solid rgba(255, 255, 255, 0.4);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin 0.9s linear infinite;
}
.spinner-msg {
  font-size: 13px;
  color: #0c447c;
}
.progress-track {
  height: 6px;
  background: #b5d4f4;
  border-radius: 3px;
  overflow: hidden;
}
.progress-fill {
  height: 100%;
  border-radius: 3px;
  background: #185fa5;
  transition: width 0.4s ease;
}
.progress-fill.ok {
  background: #085041;
}
.progress-fill.err {
  background: #a32d2d;
}
.progress-label {
  font-size: 12px;
  color: #185fa5;
  text-align: right;
  margin-top: 4px;
}
@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
</style>
