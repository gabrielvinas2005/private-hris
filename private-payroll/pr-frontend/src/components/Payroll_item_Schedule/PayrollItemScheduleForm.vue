<template>
  <Teleport to="body">
    <Transition name="fade">
      <div v-if="visible" class="modal-backdrop" @click.self="handleClose">
        <Transition name="slide-up">
          <div
            v-if="visible"
            class="modal-card"
            role="dialog"
            :aria-label="formTitle"
          >
            <!-- Header -->
            <div class="modal-header">
              <div>
                <h2 class="modal-title">{{ formTitle }}</h2>
                <p class="modal-sub">
                  Configure the schedule interval, period, and items.
                </p>
              </div>
              <button class="close-btn" @click="handleClose" aria-label="Close">
                <svg width="15" height="15" viewBox="0 0 15 15" fill="none">
                  <path
                    d="M2 2l11 11M13 2L2 13"
                    stroke="currentColor"
                    stroke-width="1.5"
                    stroke-linecap="round"
                  />
                </svg>
              </button>
            </div>

            <!-- Body -->
            <div class="modal-body">
              <!-- Section: Configuration -->
              <div class="section-label">Schedule configuration</div>

              <div class="field-grid">
                <!-- Payroll interval -->
                <div class="field">
                  <label class="field-label"
                    >Payroll interval <span class="req">*</span></label
                  >
                  <select
                    v-model="formData.payroll_interval_id"
                    class="field-input"
                    @change="handleDropdownChange"
                  >
                    <option value="" disabled>Select interval</option>
                    <option
                      v-for="item in dropdownData.PayrollInterval"
                      :key="item.id"
                      :value="item.id"
                    >
                      {{ item.name }}
                    </option>
                  </select>
                </div>

                <!-- Employment type -->
                <div class="field">
                  <label class="field-label"
                    >Employment type <span class="req">*</span></label
                  >
                  <select
                    v-model="formData.employment_type_id"
                    class="field-input"
                    @change="handleDropdownChange"
                  >
                    <option value="" disabled>Select type</option>
                    <option
                      v-for="item in dropdownData.EmploymentType"
                      :key="item.id"
                      :value="item.id"
                    >
                      {{ item.name }}
                    </option>
                  </select>
                </div>
              </div>

              <!-- Period type tabs -->
              <div class="field" style="margin-bottom: 20px">
                <label class="field-label"
                  >Payroll period type <span class="req">*</span></label
                >
                <div class="period-tab-group" role="tablist">
                  <button
                    role="tab"
                    class="period-tab"
                    :class="{
                      active: activeHalfTab === 'monthly',
                      disabled: !monthlyPeriodId,
                    }"
                    :disabled="!monthlyPeriodId"
                    @click="handleHalfTabChange('monthly')"
                  >
                    Monthly
                  </button>
                  <button
                    role="tab"
                    class="period-tab"
                    :class="{
                      active: activeHalfTab === 'first',
                      disabled: !firstHalfPeriodId,
                    }"
                    :disabled="!firstHalfPeriodId"
                    @click="handleHalfTabChange('first')"
                  >
                    1st half
                  </button>
                  <button
                    role="tab"
                    class="period-tab"
                    :class="{
                      active: activeHalfTab === 'second',
                      disabled: !secondHalfPeriodId,
                    }"
                    :disabled="!secondHalfPeriodId"
                    @click="handleHalfTabChange('second')"
                  >
                    2nd half
                  </button>
                </div>
              </div>

              <!-- Gov't contributions -->
              <div class="field" style="margin-bottom: 20px">
                <label class="field-label">Government contributions</label>
                <div class="contrib-row">
                  <label
                    v-for="key in CONTRIB_KEYS"
                    :key="key.field"
                    class="contrib-chip"
                    :class="{
                      checked: formData[key.field],
                      warned: warnedKeys.has(key.field),
                    }"
                  >
                    <input
                      type="checkbox"
                      v-model="formData[key.field]"
                      class="contrib-checkbox"
                    />
                    {{ key.label }}
                    <span
                      v-if="warnedKeys.has(key.field)"
                      class="warn-dot"
                      title="Also enabled on the other half"
                      >!</span
                    >
                  </label>
                </div>

                <!-- Sibling warning -->
                <div v-if="siblingWarnings.length" class="sibling-banner">
                  <svg
                    width="14"
                    height="14"
                    viewBox="0 0 14 14"
                    fill="none"
                    style="flex-shrink: 0"
                  >
                    <path
                      d="M7 2L1 12h12L7 2z"
                      stroke="#BA7517"
                      stroke-width="1.3"
                      stroke-linejoin="round"
                    />
                    <path
                      d="M7 6v3M7 10.5h.01"
                      stroke="#BA7517"
                      stroke-width="1.3"
                      stroke-linecap="round"
                    />
                  </svg>
                  <div>
                    <span class="banner-title"
                      >Duplicate contributions detected</span
                    >
                    <span class="banner-body">
                      {{
                        siblingWarnings.map((w) => w.contribution).join(", ")
                      }}
                      {{ siblingWarnings.length > 1 ? "are" : "is" }} already
                      enabled on the
                      <strong>{{ siblingWarnings[0]?.siblingPeriod }}</strong>
                      schedule. This may cause double deductions within the same
                      month.
                    </span>
                  </div>
                </div>
              </div>

              <!-- Existing header notice -->
              <div v-if="existingHeader" class="existing-banner">
                <svg
                  width="14"
                  height="14"
                  viewBox="0 0 14 14"
                  fill="none"
                  style="flex-shrink: 0"
                >
                  <circle
                    cx="7"
                    cy="7"
                    r="5.5"
                    stroke="#185fa5"
                    stroke-width="1.3"
                  />
                  <path
                    d="M7 6v4M7 4.5h.01"
                    stroke="#185fa5"
                    stroke-width="1.3"
                    stroke-linecap="round"
                  />
                </svg>
                <span
                  >An existing schedule for this interval, period, and
                  employment type was found. Saving will update it.</span
                >
              </div>

              <!-- Items section -->
              <div class="section-label" style="margin-top: 4px">
                Income &amp; deduction items
              </div>

              <div class="items-container">
                <!-- Income -->
                <div class="items-col">
                  <div class="items-col-header">
                    <span class="items-col-title">Income items</span>
                    <span class="items-count">{{ incomeItems.length }}</span>
                  </div>
                  <div class="items-list" v-if="incomeItems.length > 0">
                    <label
                      v-for="item in incomeItems"
                      :key="item.id"
                      class="item-row"
                      :class="{ active: item.active }"
                    >
                      <input
                        type="checkbox"
                        v-model="item.active"
                        @change="handleIncomeChange"
                        class="item-check"
                      />
                      <span class="item-name">{{ item.name }}</span>
                    </label>
                  </div>
                  <div v-else class="items-empty">
                    No income items available
                  </div>
                </div>

                <div class="items-divider"></div>

                <!-- Deductions -->
                <div class="items-col">
                  <div class="items-col-header">
                    <span class="items-col-title">Deduction items</span>
                    <span class="items-count">{{ deductionItems.length }}</span>
                  </div>
                  <div class="items-list" v-if="deductionItems.length > 0">
                    <label
                      v-for="item in deductionItems"
                      :key="item.id"
                      class="item-row"
                      :class="{ active: item.active }"
                    >
                      <input
                        type="checkbox"
                        v-model="item.active"
                        @change="handleDeductionChange"
                        class="item-check"
                      />
                      <span class="item-name">{{ item.name }}</span>
                    </label>
                  </div>
                  <div v-else class="items-empty">
                    No deduction items available
                  </div>
                </div>
              </div>
            </div>

            <!-- Footer -->
            <div class="modal-footer">
              <button class="btn-ghost" @click="handleClose">Cancel</button>
              <button
                class="btn-primary"
                :disabled="loading"
                @click="handleSubmit"
              >
                <svg
                  v-if="loading"
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
                {{
                  loading
                    ? "Saving…"
                    : isEditing
                      ? "Update schedule"
                      : "Create schedule"
                }}
              </button>
            </div>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed, watch } from "vue";
import { usePayrollItemScheduleForm } from "../../Composables/usePayrollItemSchedule";

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  isEditing: { type: Boolean, default: false },
  editData: { type: Object, default: () => ({}) },
});
const emit = defineEmits(["update:modelValue", "success"]);

const visible = computed({
  get: () => props.modelValue,
  set: (v) => emit("update:modelValue", v),
});

// Lock body scroll
watch(visible, (v) => {
  if (typeof document !== "undefined")
    document.body.style.overflow = v ? "hidden" : "";
});

const {
  formRef,
  loading,
  dropdownData,
  incomeItems,
  deductionItems,
  selectAllIncome,
  selectAllDeduction,
  existingHeader,
  formData,
  rules,
  isIndeterminateIncome,
  isIndeterminateDeduction,
  formTitle,
  siblingWarnings,
  activeHalfTab,
  firstHalfPeriodId,
  secondHalfPeriodId,
  monthlyPeriodId,
  loadDropdownData,
  loadIncomeItems,
  loadDeductionItems,
  loadScheduleHeader,
  handleDropdownChange,
  handleSelectAllIncome,
  handleSelectAllDeduction,
  handleIncomeChange,
  handleDeductionChange,
  handleSubmit,
  handleClose,
  resetForm,
  initForEdit,
  handleHalfTabChange,
} = usePayrollItemScheduleForm(props, emit);

const CONTRIB_KEYS = [
  // { field: "sss", label: "SSS" },
  { field: "gsis", label: "GSIS" },
  { field: "tax", label: "Tax" },
  { field: "philhealth", label: "PhilHealth" },
  { field: "pagibig", label: "Pag-IBIG" },
];

const CONTRIB_KEY_MAP = {
  // SSS: "sss",
  GSIS: "gsis",
  Tax: "tax",
  PhilHealth: "philhealth",
  "Pag-IBIG": "pagibig",
};
const warnedKeys = computed(
  () =>
    new Set(siblingWarnings.value.map((w) => CONTRIB_KEY_MAP[w.contribution])),
);

watch(visible, (newVal) => {
  if (newVal) {
    loadDropdownData();
    if (props.isEditing && props.editData) initForEdit(props.editData);
  }
});
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
  transform: translateY(14px);
  opacity: 0;
}
.slide-up-leave-to {
  transform: translateY(8px);
  opacity: 0;
}

/* card */
.modal-card {
  background: #fff;
  border-radius: 16px;
  width: 100%;
  max-width: 720px;
  max-height: 92vh;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.14);
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
  margin-bottom: 14px;
}

/* fields */
.field-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 14px;
  margin-bottom: 16px;
}
.field {
  display: flex;
  flex-direction: column;
  gap: 5px;
}
.field-label {
  font-size: 13px;
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

/* period tabs */
.period-tab-group {
  display: flex;
  gap: 0;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  overflow: hidden;
  width: fit-content;
}
.period-tab {
  background: #fff;
  border: none;
  padding: 7px 18px;
  font-size: 13px;
  color: #6b7280;
  cursor: pointer;
  transition:
    background 0.12s,
    color 0.12s;
  border-right: 1px solid #e5e7eb;
}
.period-tab:last-child {
  border-right: none;
}
.period-tab.active {
  background: #409eff;
  color: #fff;
}
.period-tab:hover:not(.active):not(.disabled) {
  background: #f3f4f6;
  color: #374151;
}
.period-tab.disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

/* contributions */
.contrib-row {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 10px;
}
.contrib-chip {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  border-radius: 6px;
  border: 1px solid #e5e7eb;
  background: #f9fafb;
  font-size: 12px;
  font-weight: 500;
  color: #6b7280;
  cursor: pointer;
  user-select: none;
  transition:
    background 0.12s,
    border-color 0.12s,
    color 0.12s;
}
.contrib-chip:hover {
  background: #f3f4f6;
  border-color: #d1d5db;
}
.contrib-chip.checked {
  background: #e1f5ee;
  border-color: #9fe1cb;
  color: #085041;
}
.contrib-chip.checked:hover {
  background: #c9ede0;
}
.contrib-chip.warned {
  border-color: #fac775;
  background: #faeeda;
  color: #633806;
}
.contrib-checkbox {
  width: 13px;
  height: 13px;
  flex-shrink: 0;
  cursor: pointer;
  accent-color: #085041;
}
.warn-dot {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 14px;
  height: 14px;
  border-radius: 50%;
  background: #ba7517;
  color: #fff;
  font-size: 9px;
  font-weight: 700;
}

/* banners */
.sibling-banner {
  display: flex;
  gap: 10px;
  align-items: flex-start;
  background: #faeeda;
  border: 1px solid #fac775;
  border-radius: 8px;
  padding: 10px 12px;
  margin-top: 6px;
}
.banner-title {
  display: block;
  font-size: 12px;
  font-weight: 500;
  color: #633806;
  margin-bottom: 3px;
}
.banner-body {
  font-size: 12px;
  color: #7d5a00;
  line-height: 1.5;
}

.existing-banner {
  display: flex;
  gap: 10px;
  align-items: flex-start;
  background: #e6f1fb;
  border: 1px solid #b5d4f4;
  border-radius: 8px;
  padding: 10px 12px;
  margin-bottom: 20px;
  font-size: 12px;
  color: #0c447c;
  line-height: 1.5;
}

/* items */
.items-container {
  display: flex;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  overflow: hidden;
  height: 320px;
}
.items-col {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-width: 0;
}
.items-divider {
  width: 1px;
  background: #e5e7eb;
  flex-shrink: 0;
}

.items-col-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 14px;
  background: #f9fafb;
  border-bottom: 1px solid #e5e7eb;
  flex-shrink: 0;
}
.items-col-title {
  font-size: 12px;
  font-weight: 500;
  color: #374151;
}
.items-count {
  font-size: 11px;
  color: #9ca3af;
  background: #f3f4f6;
  padding: 2px 7px;
  border-radius: 10px;
}

.items-list {
  flex: 1;
  overflow-y: auto;
  padding: 6px;
}
.items-list::-webkit-scrollbar {
  width: 4px;
}
.items-list::-webkit-scrollbar-track {
  background: transparent;
}
.items-list::-webkit-scrollbar-thumb {
  background: #e5e7eb;
  border-radius: 2px;
}

.item-row {
  display: flex;
  align-items: center;
  gap: 9px;
  padding: 7px 10px;
  border-radius: 6px;
  cursor: pointer;
  transition: background 0.1s;
  margin-bottom: 2px;
  border: 1px solid transparent;
}
.item-row:hover {
  background: #f3f4f6;
}
.item-row.active {
  background: #e1f5ee;
  border-color: #9fe1cb;
}
.item-check {
  width: 14px;
  height: 14px;
  flex-shrink: 0;
  cursor: pointer;
  accent-color: #085041;
}
.item-name {
  font-size: 13px;
  color: #374151;
}
.item-row.active .item-name {
  color: #085041;
  font-weight: 500;
}

.items-empty {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100%;
  font-size: 13px;
  color: #d1d5db;
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
@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
.spin-icon {
  animation: spin 0.8s linear infinite;
}
</style>
