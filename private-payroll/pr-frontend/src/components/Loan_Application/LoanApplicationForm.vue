<template>
  <!-- Backdrop -->
  <Teleport to="body">
    <Transition name="fade">
      <div v-if="visible" class="modal-backdrop" @click.self="onCancel">
        <Transition name="slide-up">
          <div
            v-if="visible"
            class="modal-card"
            role="dialog"
            :aria-label="dialogTitle"
          >
            <!-- Header -->
            <div class="modal-header">
              <div>
                <h2 class="modal-title">{{ dialogTitle }}</h2>
                <p class="modal-sub">
                  Fill in the details below and save to apply.
                </p>
              </div>
              <button class="close-btn" @click="onCancel" aria-label="Close">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                  <path
                    d="M3 3l10 10M13 3L3 13"
                    stroke="currentColor"
                    stroke-width="1.5"
                    stroke-linecap="round"
                  />
                </svg>
              </button>
            </div>

            <!-- Body -->
            <div class="modal-body">
              <form @submit.prevent="onSave" novalidate ref="formEl">
                <div class="section-label">Loan application information</div>

                <!-- Employee -->
                <div class="field-row">
                  <div class="field full">
                    <label class="field-label"
                      >Employee name <span class="req">*</span></label
                    >
                    <div
                      class="employee-select-wrap"
                      :class="{
                        'employee-select-wrap--error':
                          touched.employee_id && !form.employee_id,
                      }"
                    >
                      <el-select
                        v-model="form.employee_id"
                        placeholder="Select employee"
                        filterable
                        clearable
                        show-arrow
                        fit-input-width
                        class="employee-el-select"
                        popper-class="loan-app-employee-select-popper"
                        :disabled="isReconstruct || isViewMode"
                        @change="touch('employee_id')"
                        @blur="touch('employee_id')"
                      >
                        <el-option
                          v-for="e in employees"
                          :key="e.id"
                          :label="e.name"
                          :value="e.id"
                        />
                      </el-select>
                    </div>
                    <span
                      v-if="touched.employee_id && !form.employee_id"
                      class="err-msg"
                      >Required</span
                    >
                  </div>
                </div>

                <!-- Loan Type -->
                <div class="field-row">
                  <div class="field full">
                    <label class="field-label"
                      >Loan type <span class="req">*</span></label
                    >
                    <select
                      v-model="form.deduction_id"
                      class="field-input"
                      :disabled="isReconstruct || isViewMode"
                      :class="{
                        error: touched.deduction_id && !form.deduction_id,
                      }"
                      @change="touch('deduction_id')"
                    >
                      <option value="" disabled>Select loan type</option>
                      <option v-for="d in deductions" :key="d.id" :value="d.id">
                        {{ d.name }}
                      </option>
                    </select>
                    <span
                      v-if="touched.deduction_id && !form.deduction_id"
                      class="err-msg"
                      >Required</span
                    >
                  </div>
                </div>

                <!-- Voucher -->
                <div class="field-row">
                  <div class="field full">
                    <label class="field-label"
                      >Voucher number <span class="req">*</span></label
                    >
                    <input
                      v-model.trim="form.voucher_number"
                      class="field-input"
                      placeholder="Enter voucher number"
                      :disabled="isViewMode"
                      :class="{
                        error: touched.voucher_number && !form.voucher_number,
                      }"
                      @blur="touch('voucher_number')"
                    />
                    <span
                      v-if="touched.voucher_number && !form.voucher_number"
                      class="err-msg"
                      >Required</span
                    >
                  </div>
                </div>

                <!-- Amount + Term/Amortization -->
                <div class="field-row two-col">
                  <div class="field">
                    <label class="field-label"
                      >Loan amount <span class="req">*</span></label
                    >
                    <div class="input-prefix-wrap">
                      <span class="input-prefix">₱</span>
                      <input
                        v-model.number="form.loan_amount"
                        type="number"
                        min="0"
                        class="field-input with-prefix"
                        placeholder="0.00"
                        :disabled="isViewMode"
                        :class="{
                          error: touched.loan_amount && !form.loan_amount,
                        }"
                        @blur="touch('loan_amount')"
                      />
                    </div>
                    <span
                      v-if="touched.loan_amount && !form.loan_amount"
                      class="err-msg"
                      >Required</span
                    >
                  </div>

                  <div class="field">
                    <label class="field-label">
                      Term (months)
                      <span class="req">*</span>
                    </label>
                    <input
                      v-model.number="form.term"
                      type="number"
                      min="1"
                      class="field-input"
                      placeholder="e.g. 24"
                      :disabled="isViewMode"
                      :class="{ error: touched.term && !form.term }"
                      @blur="touch('term')"
                    />
                    <span v-if="touched.term && !form.term" class="err-msg"
                      >Required</span
                    >
                  </div>
                </div>

                <!-- Interest + Monthly Amortization -->
                <div class="field-row two-col">
                  <div class="field">
                    <label class="field-label"
                      >Interest rate (% per annum)
                      <span class="req">*</span></label
                    >
                    <div class="input-suffix-wrap">
                      <input
                        v-model.number="form.interest_rate"
                        type="number"
                        min="0"
                        max="100"
                        class="field-input with-suffix"
                        placeholder="e.g. 12.00"
                        :disabled="isViewMode"
                        :class="{
                          error:
                            touched.interest_rate &&
                            form.interest_rate === null,
                        }"
                        @blur="touch('interest_rate')"
                      />
                      <span class="input-suffix">%</span>
                    </div>
                    <span
                      v-if="
                        touched.interest_rate && form.interest_rate === null
                      "
                      class="err-msg"
                      >Required</span
                    >
                  </div>

                  <div class="field">
                    <label class="field-label"
                      >Monthly amortization
                      <span class="auto-tag">auto-calculated</span>
                    </label>
                    <div class="input-prefix-wrap">
                      <span class="input-prefix">₱</span>
                      <input
                        :value="form.loan_amortization ?? ''"
                        disabled
                        class="field-input with-prefix disabled-input"
                        placeholder="—"
                      />
                    </div>
                  </div>
                </div>

                <!-- Dates -->
                <div class="field-row two-col">
                  <div class="field">
                    <label class="field-label"
                      >Effectivity date <span class="req">*</span></label
                    >
                    <input
                      v-model="form.effectivity_date"
                      type="date"
                      :min="isReconstruct ? todayISO : undefined"
                      class="field-input"
                      :disabled="isViewMode"
                      :class="{
                        error:
                          touched.effectivity_date &&
                          (!form.effectivity_date || isInvalidReconstructEffectivity),
                      }"
                      @blur="touch('effectivity_date')"
                    />
                    <span
                      v-if="touched.effectivity_date && !form.effectivity_date"
                      class="err-msg"
                      >Required</span
                    >
                    <span
                      v-else-if="isReconstruct && isInvalidReconstructEffectivity"
                      class="err-msg"
                    >
                      Effectivity date must be today or a future date
                    </span>
                  </div>

                  <div class="field">
                    <label class="field-label">
                      End date
                      <span v-if="!isReconstruct" class="auto-tag"
                        >auto-calculated</span
                      >
                    </label>
                    <input
                      v-model="form.end_date"
                      type="date"
                      class="field-input"
                      :disabled="!isReconstruct || isViewMode"
                      :class="{
                        'disabled-input': !isReconstruct || isViewMode,
                        error:
                          isReconstruct && touched.end_date && !form.end_date,
                      }"
                      @blur="touch('end_date')"
                    />
                    <span
                      v-if="isReconstruct && touched.end_date && !form.end_date"
                      class="err-msg"
                      >Required</span
                    >
                  </div>
                </div>

                <!-- Remarks -->
                <div class="field-row">
                  <div class="field full">
                    <label class="field-label">Remarks</label>
                    <textarea
                      v-model.trim="form.remarks"
                      class="field-input"
                      rows="3"
                      placeholder="Enter any additional remarks or notes…"
                      :disabled="isViewMode"
                    />
                  </div>
                </div>
              </form>
            </div>

            <!-- Footer -->
            <div class="modal-footer">
              <button class="btn-ghost" @click="onCancel">
                {{ isViewMode ? "Close" : "Cancel" }}
              </button>
              <button
                v-if="!isViewMode"
                class="btn-primary"
                :disabled="saving"
                @click="onSave"
              >
                <svg
                  v-if="saving"
                  class="spin-icon"
                  width="14"
                  height="14"
                  viewBox="0 0 14 14"
                  fill="none"
                >
                  <circle
                    cx="7"
                    cy="7"
                    r="5"
                    stroke="currentColor"
                    stroke-width="1.5"
                    stroke-dasharray="20"
                    stroke-dashoffset="10"
                    stroke-linecap="round"
                  />
                </svg>
                {{ saving ? "Saving…" : "Save loan application" }}
              </button>
            </div>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, reactive, watch, computed } from "vue";

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  bootstrap: {
    type: Object,
    default: () => ({ employees: [], deductions: [], loan_app: [] }),
  },
  editId: { type: Number, default: 0 },
  saving: { type: Boolean, default: false },
  mode: { type: String, default: "create" }, // create | view | reconstruct
});

const emit = defineEmits(["update:modelValue", "save"]);

const visible = ref(false);
watch(
  () => props.modelValue,
  (v) => (visible.value = v),
);
watch(visible, (v) => emit("update:modelValue", v));

// Lock body scroll when open
watch(visible, (v) => {
  if (typeof document !== "undefined") {
    document.body.style.overflow = v ? "hidden" : "";
  }
});

const employees = computed(() => props.bootstrap?.employees || []);
const deductions = computed(() => {
  return (props.bootstrap?.deductions || []).filter((d) => {
    const hasValidId =
      d && d.id !== undefined && d.id !== null && String(d.id) !== "0";
    const hasName = typeof d?.name === "string" && d.name.trim() !== "";
    return hasValidId && hasName;
  });
});
const isReconstruct = computed(() => props.mode === "reconstruct");
const isViewMode = computed(() => props.mode === "view");

const dialogTitle = computed(() =>
  props.mode === "reconstruct"
    ? "Reconstruct loan application"
    : props.mode === "view"
      ? "Loan application details"
      : "Add loan application",
);

const formEl = ref();
const form = reactive({
  deduction_id: null,
  employee_id: null,
  loan_amount: null,
  interest_rate: null,
  term: null,
  loan_amortization: null,
  remarks: "",
  effectivity_date: "",
  end_date: "",
  voucher_number: "",
});

// touched state for inline validation
const touched = reactive({});
const touch = (key) => {
  touched[key] = true;
};

const zeroToNull = (v) => (v === 0 || v === "0" ? null : (v ?? null));
const todayISO = new Date().toISOString().split("T")[0];
const isInvalidReconstructEffectivity = computed(
  () =>
    isReconstruct.value &&
    Boolean(form.effectivity_date) &&
    form.effectivity_date < todayISO,
);

watch(
  () => props.bootstrap,
  (b) => {
    // reset touched on bootstrap change
    Object.keys(touched).forEach((k) => delete touched[k]);
    const arr = Array.isArray(b?.loan_app) ? b.loan_app : [];
    const data = arr[0] || {};
    Object.assign(form, {
      deduction_id: zeroToNull(data.deduction_id),
      employee_id: zeroToNull(data.employee_id),
      loan_amount: data.loan_amount ?? null,
      interest_rate: data.interest_rate ?? null,
      term: data.term ?? null,
      loan_amortization: data.loan_amortization ?? null,
      remarks: data.remarks ?? "",
      effectivity_date: data.effectivity_date ?? "",
      end_date: data.end_date ?? "",
      voucher_number: data.voucher_number ?? "",
    });
  },
  { immediate: true },
);

// --- Helpers ---
const toEndOfNextMonth = (date = new Date()) => {
  const d = new Date(date.getFullYear(), date.getMonth() + 2, 0);
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")}`;
};
const addMonths = (start, months) => {
  const d = start ? new Date(start) : new Date();
  const t = new Date(d.getFullYear(), d.getMonth() + months + 1, 0);
  return `${t.getFullYear()}-${String(t.getMonth() + 1).padStart(2, "0")}-${String(t.getDate()).padStart(2, "0")}`;
};

const computeAmortization = () => {
  const P = Number(form.loan_amount || 0);
  const n = Number(form.term || 0);
  const a = Number(form.interest_rate ?? 0);
  if (!P || !n) {
    form.loan_amortization = null;
    return;
  }
  const r = a > 0 ? a / 12 / 100 : 0;
  if (r === 0) {
    form.loan_amortization = Number((P / n).toFixed(2));
    return;
  }
  form.loan_amortization = Number(
    ((P * r) / (1 - Math.pow(1 + r, -n))).toFixed(2),
  );
};
watch(
  () => [form.loan_amount, form.term, form.interest_rate, props.mode],
  computeAmortization,
);

watch(
  () => props.mode,
  (m) => {
    if (m !== "reconstruct") {
      if (!form.effectivity_date) form.effectivity_date = toEndOfNextMonth();
      if (form.term && form.effectivity_date)
        form.end_date = addMonths(form.effectivity_date, form.term - 1);
    }
  },
  { immediate: true },
);

watch(
  () => [form.effectivity_date, form.term, props.mode],
  () => {
    if (isReconstruct.value) return;
    if (form.effectivity_date && form.term)
      form.end_date = addMonths(form.effectivity_date, form.term - 1);
  },
);

// --- Validate ---
const validate = () => {
  if (isViewMode.value) return false;
  const fields = [
    "employee_id",
    "deduction_id",
    "loan_amount",
    "voucher_number",
    "effectivity_date",
  ];
  if (isReconstruct.value) fields.push("interest_rate", "term", "end_date");
  else fields.push("interest_rate", "term");
  fields.forEach(touch);
  const hasRequiredFields = fields.every(
    (f) => form[f] !== null && form[f] !== "" && form[f] !== undefined,
  );
  if (!hasRequiredFields) return false;
  if (isReconstruct.value && isInvalidReconstructEffectivity.value) return false;
  return true;
};

const onCancel = () => {
  visible.value = false;
};
const onSave = async () => {
  if (isViewMode.value) return;
  if (!validate()) return;
  const payload = {
    ...form,
    deduction_id: form.deduction_id ?? 0,
    employee_id: form.employee_id ?? 0,
  };
  if (!payload.deduction_id) return;
  emit("save", payload);
};
</script>

<style scoped>
/* backdrop + transitions */
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.35);
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
.slide-up-enter-active {
  transition:
    transform 0.22s ease,
    opacity 0.22s;
}
.slide-up-leave-active {
  transition:
    transform 0.18s ease,
    opacity 0.18s;
}
.slide-up-enter-from {
  transform: translateY(16px);
  opacity: 0;
}
.slide-up-leave-to {
  transform: translateY(8px);
  opacity: 0;
}

/* modal card */
.modal-card {
  background: #fff;
  border-radius: 16px;
  width: 100%;
  max-width: 680px;
  max-height: 92vh;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
}

/* header */
.modal-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  padding: 20px 24px 16px;
  border-bottom: 1px solid #f3f4f6;
  flex-shrink: 0;
}
.modal-title {
  font-size: 17px;
  font-weight: 500;
  color: #111827;
  margin: 0;
}
.modal-sub {
  font-size: 13px;
  color: #9ca3af;
  margin: 3px 0 0;
}
.close-btn {
  background: transparent;
  border: none;
  border-radius: 8px;
  padding: 6px;
  color: #9ca3af;
  cursor: pointer;
  display: flex;
  align-items: center;
}
.close-btn:hover {
  background: #f3f4f6;
  color: #374151;
}

/* body */
.modal-body {
  padding: 20px 24px;
  overflow-y: auto;
  flex: 1;
}
.section-label {
  font-size: 11px;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: #9ca3af;
  margin-bottom: 16px;
}

/* fields */
.field-row {
  margin-bottom: 14px;
}
.field-row.two-col {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 14px;
}
.field {
  display: flex;
  flex-direction: column;
  gap: 5px;
}
.field.full {
  width: 100%;
}

.field-label {
  font-size: 13px;
  font-weight: 500;
  color: #374151;
  display: flex;
  align-items: center;
  gap: 4px;
}
.req {
  color: #a32d2d;
}
.auto-tag {
  font-size: 11px;
  font-weight: 400;
  color: #9ca3af;
  background: #f3f4f6;
  padding: 1px 6px;
  border-radius: 4px;
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
  transition: border-color 0.15s;
  font-family: inherit;
}
.field-input:focus {
  border-color: #6b7280;
}
.field-input.error {
  border-color: #e24b4a;
}
.field-input.disabled-input {
  background: #f9fafb;
  color: #9ca3af;
  cursor: not-allowed;
}
.field-input:disabled {
  background: #f3f4f6;
  color: #9ca3af;
  cursor: not-allowed;
}

textarea.field-input {
  resize: vertical;
  min-height: 72px;
  line-height: 1.5;
}
select.field-input {
  appearance: auto;
}

/* Searchable employee picker (Element Plus) — align with .field-input */
.employee-select-wrap {
  width: 100%;
}
.employee-el-select {
  width: 100%;
}
.employee-select-wrap--error :deep(.el-select__wrapper) {
  box-shadow: 0 0 0 1px #e24b4a inset;
}
.employee-select-wrap :deep(.el-select__wrapper) {
  min-height: 38px;
  border-radius: 8px;
  font-size: 13px;
  font-family: inherit;
}
.employee-select-wrap :deep(.el-select.is-disabled .el-select__wrapper) {
  background: #f3f4f6;
  box-shadow: 0 0 0 1px #e5e7eb inset;
}
.employee-select-wrap
  :deep(.el-select.is-disabled .el-select__selected-item) {
  color: #9ca3af;
}

/* prefix/suffix inputs */
.input-prefix-wrap,
.input-suffix-wrap {
  position: relative;
}
.input-prefix,
.input-suffix {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  font-size: 13px;
  color: #9ca3af;
  pointer-events: none;
}
.input-prefix {
  left: 10px;
}
.input-suffix {
  right: 10px;
}
.field-input.with-prefix {
  padding-left: 22px;
}
.field-input.with-suffix {
  padding-right: 28px;
}

.err-msg {
  font-size: 11px;
  color: #a32d2d;
}

/* footer */
.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  padding: 16px 24px;
  border-top: 1px solid #f3f4f6;
  flex-shrink: 0;
}
.btn-primary {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: #409eff;
  color: #fff;
  border: none;
  border-radius: 8px;
  padding: 9px 20px;
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
  padding: 9px 16px;
  font-size: 13px;
  color: #6b7280;
  cursor: pointer;
}
.btn-ghost:hover {
  background: #f3f4f6;
}

/* loading spinner */
@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
.spin-icon {
  animation: spin 0.8s linear infinite;
}
</style>

<!-- Popper is teleported to body; modal z-index is 9999 — raise dropdown above it -->
<style>
.loan-app-employee-select-popper.el-select__popper,
.loan-app-employee-select-popper {
  z-index: 10050 !important;
}
</style>
