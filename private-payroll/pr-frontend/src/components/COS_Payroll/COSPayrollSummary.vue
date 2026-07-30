<template>
  <div class="cos-summary-shell">
    <!-- Header -->
    <div class="cs-header">
      <div>
        <h3 class="cs-title">{{ periodLabel || "COS Payroll Summary" }}</h3>
        <p class="cs-sub">
          {{ selectedHalfLabel }}: gross is monthly salary ÷ 2; attendance
          {{ formatAttendanceRange }}; +20% premium added, then 3% NVAT and 2% EWT on balance + premium
        </p>
      </div>
      <div class="cs-actions-wrap">
        <div class="cs-actions">
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
            Adjust EWT
          </button>
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
            {{ processing ? "Processing…" : "Process" }}
          </button>

          <button
            v-if="!isPosted"
            class="btn-post"
            :disabled="processing || !canPost"
            @click="handlePostPayroll"
          >
            <svg width="13" height="13" viewBox="0 0 13 13" fill="none">
              <path
                d="M2 7l3 3 6-6"
                stroke="currentColor"
                stroke-width="1.5"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
            </svg>
            Post
          </button>
        </div>
        <p
          v-if="!isPosted"
          class="post-hint"
          :class="{
            success: canPost,
            warning: processedForPost && approvedCount === 0,
          }"
        >
          {{ postHint }}
        </p>
      </div>
    </div>

    <!-- Stat cards -->
    <div class="cs-stats-grid">
      <div class="cs-stat-card">
        <div class="cs-stat-label">Total employees</div>
        <div class="cs-stat-value">{{ totalEmployees }}</div>
      </div>
      <div class="cs-stat-card">
        <div class="cs-stat-label">Total gross</div>
        <div class="cs-stat-value gross">{{ fc(totalGrossAmount) }}</div>
      </div>
      <div class="cs-stat-card">
        <div class="cs-stat-label">Total deductions</div>
        <div class="cs-stat-value deduction">{{ fc(totalDeductions) }}</div>
      </div>
      <div class="cs-stat-card">
        <div class="cs-stat-label">Total net pay</div>
        <div class="cs-stat-value net">{{ fc(totalNetPay) }}</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
  summaryData: { type: Object, default: () => ({}) },
  halfInfo: { type: Object, default: null },
  selectedHalf: { type: String, default: "first" },
  employees: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  processing: { type: Boolean, default: false },
  canPost: { type: Boolean, default: false },
  approvedCount: { type: Number, default: 0 },
  skippedCount: { type: Number, default: 0 },
  processedForPost: { type: Boolean, default: false },
});

const emit = defineEmits([
  "process-payroll",
  "leave-earned-details",
  "adjust-tax",
  "post-payroll",
]);

const periodLabel = computed(
  () => props.summaryData?.payroll ?? props.summaryData?.payroll_period ?? "",
);

const selectedHalfLabel = computed(() =>
  props.selectedHalf === "second" ? "Second half" : "First half",
);

const formatAttendanceRange = computed(() => {
  const info = props.halfInfo;
  const useSecond = props.selectedHalf === "second";
  const start = useSecond
    ? info?.second_half_attendance_start ?? props.summaryData?.attendance_start_date
    : info?.first_half_attendance_start ?? props.summaryData?.attendance_start_date;
  const end = useSecond
    ? info?.second_half_attendance_end ?? props.summaryData?.attendance_end_date
    : info?.first_half_attendance_end ?? props.summaryData?.attendance_end_date;
  if (!start || !end) return "payroll period dates";
  const fmt = (d) => {
    const [y, m, day] = String(d).split("T")[0].split("-");
    return `${m}/${day}/${y}`;
  };
  return `${fmt(start)} – ${fmt(end)}`;
});

const isPosted = computed(() => {
  const v = props.summaryData?.posted;
  return v === true || v === 1 || v === "1";
});

const canProcessPayroll = computed(() => !isPosted.value);
const canAdjustTax = computed(() => !isPosted.value);

const postHint = computed(() => {
  if (!props.processedForPost) {
    return "Run Process first to enable Post.";
  }
  if (props.approvedCount === 0) {
    return "No employees with a valid contract and supervisor-approved work details. Post is disabled until tasks are approved.";
  }
  if (props.skippedCount > 0) {
    return `Process complete. ${props.approvedCount} employee(s) with valid contract and approved work details will be posted. ${props.skippedCount} employee(s) on hold or without approval will be skipped.`;
  }
  return "Process complete. You can now Post this period.";
});

const totalEmployees = computed(() => {
  const ids = new Set(
    (props.employees ?? []).map((emp) => emp.employeeId).filter(Boolean),
  );
  return ids.size;
});

const parseNumeric = (val) => {
  if (val == null || val === "") return 0;
  const n = parseFloat(val);
  return Number.isNaN(n) ? 0 : n;
};

const totalGrossAmount = computed(() =>
  (props.employees ?? []).reduce(
    (sum, emp) => sum + parseNumeric(emp.gross),
    0,
  ),
);

const totalNetPay = computed(() =>
  (props.employees ?? []).reduce(
    (sum, emp) => sum + parseNumeric(emp.netPay),
    0,
  ),
);

const totalDeductions = computed(() =>
  (props.employees ?? []).reduce(
    (sum, emp) => sum + parseNumeric(emp.totalDeduction),
    0,
  ),
);

const fc = (amount) => {
  const num = parseNumeric(amount);
  return new Intl.NumberFormat("en-PH", {
    style: "currency",
    currency: "PHP",
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(num);
};

const handleProcessPayroll = () => emit("process-payroll");
const handleAdjustTax = () => emit("adjust-tax");
const handlePostPayroll = () => emit("post-payroll");
</script>

<style scoped>
.cos-summary-shell {
  display: flex;
  flex-direction: column;
  gap: 16px;
  margin-bottom: 20px;
}

.cs-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
  background: #f9fafb;
  border-radius: 12px;
  padding: 14px 16px;
}
.cs-title {
  margin: 0 0 4px;
  font-size: 18px;
  font-weight: 600;
  color: #111827;
}
.cs-sub {
  margin: 0;
  font-size: 13px;
  color: #6b7280;
}
.cs-actions-wrap {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 6px;
}
.cs-actions {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}
.btn-post {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: #111827;
  border: none;
  border-radius: 7px;
  padding: 7px 16px;
  font-size: 13px;
  color: #fff;
  cursor: pointer;
  white-space: nowrap;
  transition: opacity 0.1s;
}
.btn-post:hover:not(:disabled) {
  opacity: 0.85;
}
.btn-post:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}
.post-hint {
  margin: 0;
  font-size: 12px;
  color: #9ca3af;
}
.post-hint.success {
  color: #059669;
}
.post-hint.warning {
  color: #b45309;
}
.btn-action {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: #fff;
  border: 1px solid #d1d5db;
  border-radius: 7px;
  padding: 7px 14px;
  font-size: 13px;
  color: #374151;
  cursor: pointer;
  white-space: nowrap;
  transition: background 0.1s;
}
.btn-action:hover:not(:disabled) {
  background: #f3f4f6;
}
.btn-action:disabled {
  opacity: 0.45;
  cursor: not-allowed;
}

.cs-stats-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 10px;
}
@media (max-width: 768px) {
  .cs-stats-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}
.cs-stat-card {
  background: #f8f9fa;
  border-radius: 10px;
  padding: 14px 16px;
}
.cs-stat-label {
  font-size: 12px;
  color: #6b7280;
  margin-bottom: 5px;
}
.cs-stat-value {
  font-size: 18px;
  font-weight: 600;
  color: #111827;
  font-family: monospace;
}
.cs-stat-value.gross {
  color: #059669;
}
.cs-stat-value.deduction {
  color: #b45309;
}
.cs-stat-value.net {
  color: #185fa5;
}
</style>
