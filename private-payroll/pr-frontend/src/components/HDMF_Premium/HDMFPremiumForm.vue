<template>
  <div class="hdmf-form-shell">
    <!-- Period selector card -->
    <div class="form-card">
      <div class="form-card-header">
        <span class="form-card-title">HDMF Premium Management</span>
        <span v-if="selectedPeriod" class="period-badge">Period selected</span>
      </div>

      <div class="field-row">
        <div class="field">
          <label class="field-label"
            >Payroll period <span class="req">*</span></label
          >
          <select
            v-model="form.payrollPeriodId"
            class="field-input"
            :disabled="loading"
            @change="handlePeriodChange"
          >
            <option value="" disabled>Select payroll period</option>
            <option
              v-for="period in payrollPeriods"
              :key="period.id"
              :value="period.id"
            >
              {{ getPeriodLabel(period) }}
            </option>
          </select>
        </div>

        <div v-if="selectedPeriod" class="period-info-strip">
          <div class="pi-item">
            <span class="pi-label">Period</span>
            <span class="pi-val"
              >{{ selectedPeriod.name
              }}<span v-if="selectedPeriod.cutoff_name" class="cutoff-tag">{{
                selectedPeriod.cutoff_name
              }}</span></span
            >
          </div>
          <div class="pi-item">
            <span class="pi-label">Release date</span>
            <span class="pi-val">{{
              formatDate(selectedPeriod.release_date)
            }}</span>
          </div>
        </div>
      </div>

      <div class="btn-row">
        <button
          class="btn-primary"
          :disabled="!form.payrollPeriodId || loading"
          @click="loadEmployees"
        >
          <svg
            v-if="loading"
            class="spin-icon"
            width="13"
            height="13"
            viewBox="0 0 13 13"
            fill="none"
          >
            <circle
              cx="6.5"
              cy="6.5"
              r="5"
              stroke="currentColor"
              stroke-width="1.5"
              stroke-dasharray="20"
              stroke-dashoffset="10"
              stroke-linecap="round"
            />
          </svg>
          {{ loading ? "Loading…" : "Load employees" }}
        </button>
        <button v-if="selectedPeriod" class="btn-ghost" @click="clearSelection">
          Clear selection
        </button>
        <button
          v-if="hasEmployees"
          class="btn-ghost"
          :disabled="loading"
          @click="refreshEmployees"
        >
          Refresh data
        </button>
      </div>
    </div>

    <!-- Info/rules card -->
    <div class="rules-card">
      <div v-if="!selectedPeriod">
        <div class="rules-title">Instructions</div>
        <ul class="rules-list">
          <li>Select a payroll period to manage HDMF premium contributions</li>
          <li>Only unposted payroll periods are available for editing</li>
          <li>
            You can modify individual employee contributions after loading the
            employee list
          </li>
          <li>
            The system will validate amounts against government-mandated
            minimums
          </li>
        </ul>
      </div>
      <div v-else>
        <div class="rules-title">HDMF Premium rules</div>
        <ul class="rules-list">
          <li class="rule-info">
            Minimum contribution: <strong>₱200.00</strong> (government-mandated)
          </li>
          <li class="rule-warn">
            Net take-home pay must be at least <strong>₱5,000.00</strong>
          </li>
          <li class="rule-danger">
            If validation fails, check employee salary vs. HDMF amount
          </li>
          <li class="rule-danger">
            Select the employee first to be able to modify the amount
          </li>
          <li class="rule-ok">
            Amounts are automatically validated before saving
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from "vue";

const props = defineProps({
  payrollPeriods: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  selectedPeriod: { type: Object, default: null },
  hasEmployees: { type: Boolean, default: false },
});
const emit = defineEmits([
  "period-change",
  "load-employees",
  "clear-selection",
  "refresh-employees",
]);

const form = ref({ payrollPeriodId: null });

const selectedPeriod = computed(() => {
  if (!form.value.payrollPeriodId) return null;
  return (
    props.payrollPeriods.find((p) => p.id === form.value.payrollPeriodId) ||
    null
  );
});

const handlePeriodChange = () => {
  if (form.value.payrollPeriodId) emit("period-change", selectedPeriod.value);
  else emit("clear-selection");
};
const loadEmployees = () => {
  if (!form.value.payrollPeriodId) return;
  emit("load-employees", form.value.payrollPeriodId);
};
const clearSelection = () => {
  form.value.payrollPeriodId = null;
  emit("clear-selection");
};
const refreshEmployees = () => {
  if (!form.value.payrollPeriodId) return;
  emit("refresh-employees", form.value.payrollPeriodId);
};
const formatDate = (d) => {
  if (!d) return "";
  return new Date(d).toLocaleDateString("en-PH", {
    year: "numeric",
    month: "long",
    day: "numeric",
  });
};
const getPeriodLabel = (p) =>
  p.cutoff_name ? `${p.name} — ${p.cutoff_name}` : p.name;

onMounted(() => {
  if (props.selectedPeriod)
    form.value.payrollPeriodId = props.selectedPeriod.id;
});
watch(
  () => props.selectedPeriod,
  (p) => {
    form.value.payrollPeriodId = p?.id ?? null;
  },
);
</script>

<style scoped>
.hdmf-form-shell {
  display: grid;
  grid-template-columns: 1fr 320px;
  gap: 16px;
  margin-bottom: 20px;
  align-items: start;
}
@media (max-width: 720px) {
  .hdmf-form-shell {
    grid-template-columns: 1fr;
  }
}

.form-card {
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 18px 20px;
  background: #fff;
  display: flex;
  flex-direction: column;
  gap: 14px;
}
.form-card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.form-card-title {
  font-size: 14px;
  font-weight: 500;
  color: #111827;
}
.period-badge {
  font-size: 11px;
  font-weight: 500;
  padding: 3px 9px;
  border-radius: 4px;
  background: #e1f5ee;
  color: #085041;
}

.field-row {
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.field {
  display: flex;
  flex-direction: column;
  gap: 5px;
}
.field-label {
  font-size: 12px;
  font-weight: 500;
  color: #374151;
}
.req {
  color: #a32d2d;
}
.field-input {
  font-size: 13px;
  padding: 8px 10px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  background: #fff;
  color: #111827;
  outline: none;
  width: 100%;
}
.field-input:focus {
  border-color: #6b7280;
}
.field-input:disabled {
  background: #f9fafb;
  color: #9ca3af;
  cursor: not-allowed;
}

.period-info-strip {
  display: flex;
  gap: 20px;
  padding: 10px 14px;
  background: #f9fafb;
  border-radius: 8px;
  flex-wrap: wrap;
}
.pi-item {
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.pi-label {
  font-size: 11px;
  color: #9ca3af;
}
.pi-val {
  font-size: 13px;
  font-weight: 500;
  color: #111827;
  display: flex;
  align-items: center;
  gap: 6px;
}
.cutoff-tag {
  font-size: 11px;
  font-weight: 500;
  padding: 2px 7px;
  border-radius: 4px;
  background: #e6f1fb;
  color: #0c447c;
}

.btn-row {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}
.btn-primary {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: #409eff;
  color: #fff;
  border: none;
  border-radius: 8px;
  padding: 8px 16px;
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
.btn-ghost {
  background: transparent;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 8px 14px;
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

.rules-card {
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 16px 18px;
  background: #f9fafb;
}
.rules-title {
  font-size: 12px;
  font-weight: 500;
  color: #374151;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: 10px;
}
.rules-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 7px;
}
.rules-list li {
  font-size: 13px;
  color: #6b7280;
  padding-left: 16px;
  position: relative;
}
.rules-list li::before {
  content: "·";
  position: absolute;
  left: 4px;
  color: #d1d5db;
}
.rule-info::before {
  content: "ℹ";
  color: #185fa5;
}
.rule-warn::before {
  content: "⚠";
  color: #ba7517;
}
.rule-danger::before {
  content: "!";
  color: #a32d2d;
  font-weight: 700;
}
.rule-ok::before {
  content: "✓";
  color: #085041;
  font-weight: 700;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
.spin-icon {
  animation: spin 0.8s linear infinite;
}
</style>
