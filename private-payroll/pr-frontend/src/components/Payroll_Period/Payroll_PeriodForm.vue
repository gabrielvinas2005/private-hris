<template>
  <Transition name="fade">
    <div v-if="visible" class="modal-backdrop" @click.self="close">
      <div class="modal-card">
        <!-- Header -->
        <div class="modal-header">
          <p class="modal-title">{{ formTitle }}</p>
          <button class="close-btn" @click="close">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
              <path
                d="M12 4L4 12M4 4l8 8"
                stroke="currentColor"
                stroke-width="1.5"
                stroke-linecap="round"
              />
            </svg>
          </button>
        </div>

        <!-- Body -->
        <div v-if="form" class="modal-body">
          <!-- Row 1: Basic Info + Release Info -->
          <div class="two-section-row">
            <div class="section-card">
              <p class="section-title">Basic Information</p>
              <div class="field-grid two-col">
                <div class="field">
                  <label class="field-label"
                    >Payroll Interval <span class="req">*</span></label
                  >
                  <el-select
                    v-model="form.payroll_interval_id"
                    placeholder="Payroll Interval"
                    class="custom-select"
                    popper-class="payroll-select-popper"
                  >
                    <el-option
                      v-for="i in intervals"
                      :key="i.id"
                      :label="i.name"
                      :value="i.id"
                    />
                  </el-select>
                </div>

                <div class="field">
                  <label class="field-label"
                    >Payroll Cut-off <span class="req">*</span></label
                  >
                  <el-select
                    v-model="form.payroll_cutoff_id"
                    placeholder="Cut-off period"
                    class="custom-select"
                    popper-class="payroll-select-popper"
                  >
                    <el-option
                      v-for="c in cutoffs"
                      :key="c.id"
                      :label="c.name"
                      :value="c.id"
                    />
                  </el-select>
                </div>
              </div>
            </div>

            <div class="section-card">
              <p class="section-title">Release Information</p>
              <div class="field-grid two-col">
                <template v-if="Number(form.payroll_cutoff_id) !== 2">
                  <div class="field" style="flex: 1 1 100%">
                    <label class="field-label"
                      >Payout Release Date <span class="req">*</span></label
                    >
                    <el-date-picker
                      v-model="form.release_date"
                      type="date"
                      value-format="YYYY-MM-DD"
                      placeholder="Select release date"
                      class="custom-date-picker"
                      popper-class="payroll-date-popper"
                      size="default"
                    />
                  </div>
                </template>

                <template v-else>
                  <div class="field">
                    <label class="field-label"
                      >First Half Release Date <span class="req">*</span></label
                    >
                    <el-date-picker
                      v-model="form.release_date_first_half"
                      type="date"
                      value-format="YYYY-MM-DD"
                      placeholder="Select 1st half date"
                      class="custom-date-picker"
                      popper-class="payroll-date-popper"
                      size="default"
                    />
                  </div>
                  <div class="field">
                    <label class="field-label"
                      >Second Half Release Date
                      <span class="req">*</span></label
                    >
                    <el-date-picker
                      v-model="form.release_date_second_half"
                      type="date"
                      value-format="YYYY-MM-DD"
                      placeholder="Select 2nd half date"
                      class="custom-date-picker"
                      popper-class="payroll-date-popper"
                      size="default"
                    />
                  </div>
                </template>
              </div>
            </div>
          </div>

          <!-- Row 2: Attendance Period + Cut-off Period -->
          <div class="two-section-row">
            <div class="section-card">
              <p class="section-title">Attendance Period</p>
              <div class="field-grid two-col">
                <div class="field">
                  <label class="field-label"
                    >Attendance Start Date <span class="req">*</span></label
                  >
                  <el-date-picker
                    v-model="form.attendance_start_date"
                    type="date"
                    value-format="YYYY-MM-DD"
                    placeholder="Select start date"
                    class="custom-date-picker"
                    popper-class="payroll-date-popper"
                    size="default"
                  />
                </div>
                <div class="field">
                  <label class="field-label"
                    >Attendance End Date <span class="req">*</span></label
                  >
                  <el-date-picker
                    v-model="form.attendance_end_date"
                    type="date"
                    value-format="YYYY-MM-DD"
                    placeholder="Select end date"
                    class="custom-date-picker"
                    popper-class="payroll-date-popper"
                    size="default"
                  />
                </div>
              </div>
            </div>

            <div class="section-card">
              <p class="section-title">Cut-off Period</p>
              <div class="field-grid two-col">
                <div class="field">
                  <label class="field-label"
                    >Payout First Half <span class="req">*</span></label
                  >
                  <el-date-picker
                    v-model="form.payroll_start_date"
                    type="date"
                    value-format="YYYY-MM-DD"
                    placeholder="Select start date"
                    class="custom-date-picker"
                    popper-class="payroll-date-popper"
                    :disabled="isPayoutFirstHalfDisabled"
                    size="default"
                  />
                </div>
                <div class="field">
                  <label class="field-label"
                    >Payout Second Half <span class="req">*</span></label
                  >
                  <el-date-picker
                    v-model="form.payroll_end_date"
                    type="date"
                    value-format="YYYY-MM-DD"
                    placeholder="Select end date"
                    class="custom-date-picker"
                    popper-class="payroll-date-popper"
                    size="default"
                  />
                </div>
              </div>
            </div>
          </div>

          <!-- Employment Types -->
          <div class="section-card">
            <p class="section-title">Employment Types</p>
            <div class="checkbox-group">
              <label
                v-for="empType in employmentTypes"
                :key="empType.id"
                class="check-chip"
                :class="{
                  checked:
                    form.employment_type_ids &&
                    form.employment_type_ids.includes(Number(empType.id)),
                  disabled: isEmploymentTypeDisabled(empType),
                }"
              >
                <input
                  type="checkbox"
                  class="check-hidden"
                  :value="Number(empType.id)"
                  v-model="form.employment_type_ids"
                  :disabled="isEmploymentTypeDisabled(empType)"
                  @change="handleEmploymentTypeChange"
                />
                {{ empType.name }}
              </label>
            </div>

            <div v-if="isContractOfServiceSelected" class="info-banner">
              <svg
                width="16"
                height="16"
                viewBox="0 0 16 16"
                fill="none"
                style="flex-shrink: 0; margin-top: 1px"
              >
                <circle
                  cx="8"
                  cy="8"
                  r="7"
                  stroke="#378ADD"
                  stroke-width="1.2"
                />
                <path
                  d="M8 7v4M8 5.5v.5"
                  stroke="#378ADD"
                  stroke-width="1.4"
                  stroke-linecap="round"
                />
              </svg>
              <span
                ><strong>Contract of Service selected</strong> — Cannot be
                combined with other employment types.</span
              >
            </div>
          </div>

          <!-- Status -->
          <div class="section-card">
            <p class="section-title">Status</p>
            <div class="checkbox-group">
              <label class="check-chip" :class="{ checked: form.active }">
                <input
                  type="checkbox"
                  class="check-hidden"
                  v-model="form.active"
                />
                Set Active
              </label>
            </div>
          </div>
        </div>

        <!-- Loading fallback -->
        <div
          v-else
          class="modal-body"
          style="
            align-items: center;
            justify-content: center;
            min-height: 200px;
          "
        >
          <p style="color: #6b7280; font-size: 13px; margin: 0">Loading...</p>
        </div>

        <!-- Footer -->
        <div class="modal-footer">
          <button class="btn-ghost" @click="close">Cancel</button>
          <button class="btn-primary" :disabled="saving" @click="onSubmit">
            <svg
              v-if="!saving"
              width="14"
              height="14"
              viewBox="0 0 14 14"
              fill="none"
            >
              <path
                d="M2 7l3.5 3.5L12 3.5"
                stroke="white"
                stroke-width="1.6"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
            </svg>
            <svg
              v-else
              class="spin-icon"
              width="14"
              height="14"
              viewBox="0 0 14 14"
              fill="none"
            >
              <circle
                cx="7"
                cy="7"
                r="5.5"
                stroke="white"
                stroke-width="1.5"
                stroke-dasharray="20 14"
              />
            </svg>
            {{ saving ? "Saving..." : "Save Payroll Period" }}
          </button>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { computed, watch, onMounted } from "vue";
import { usePayrollPeriodForm } from "../../Composables/usePayrollPeriod";

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  editId: { type: Number, default: 0 },
});
const emit = defineEmits(["update:modelValue", "saved"]);

const visible = computed({
  get: () => props.modelValue,
  set: (v) => emit("update:modelValue", v),
});

const {
  formRef,
  saving,
  intervals,
  cutoffs,
  employmentTypes,
  releaseMonth,
  form,
  rules,
  formTitle,
  isPayoutFirstHalfDisabled,
  isEmploymentTypeDisabled,
  handleEmploymentTypeChange,
  isContractOfServiceSelected,
  resetForm,
  loadFormData,
  close,
  onSubmit,
} = usePayrollPeriodForm(props, emit);

watch(
  () => props.modelValue,
  async (v) => {
    if (v) await loadFormData();
  },
);

watch(
  () => props.editId,
  async () => {
    if (props.modelValue) await loadFormData();
  },
);

onMounted(() => {
  if (props.modelValue) loadFormData();
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
  max-width: 760px;
  max-height: 92vh;
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
  gap: 14px;
}
.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  padding: 14px 24px;
  border-top: 1px solid #f3f4f6;
  flex-shrink: 0;
}
.section-card {
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  padding: 14px 16px;
}
.section-title {
  font-size: 12px;
  font-weight: 500;
  color: #374151;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin: 0 0 12px;
}
.two-section-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 14px;
}
.field-grid {
  display: flex;
  gap: 14px;
  flex-wrap: wrap;
}
.field-grid.two-col > .field {
  flex: 1;
  min-width: 0;
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
  box-sizing: border-box;
  height: 36px;
}
.field-input:focus {
  border-color: #6b7280;
}

/* el-date-picker overrides to match .field-input */
:deep(.custom-select) {
  width: 100% !important;
}
:deep(.custom-select .el-select__wrapper) {
  min-height: 36px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  box-shadow: none !important;
  padding: 0 10px;
}
:deep(.custom-select .el-select__wrapper:hover) {
  border-color: #6b7280;
}
:deep(.custom-select .el-select__wrapper.is-focused) {
  border-color: #6b7280;
  box-shadow: none !important;
}
:deep(.custom-date-picker) {
  width: 100% !important;
}
:deep(.custom-date-picker .el-input__wrapper) {
  font-size: 13px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  background: #fff;
  box-shadow: none !important;
  padding: 0 10px;
  height: 36px;
  transition: border-color 0.15s;
}
:deep(.custom-date-picker .el-input__wrapper:hover) {
  border-color: #6b7280;
}
:deep(.custom-date-picker .el-input__wrapper.is-focus) {
  border-color: #6b7280;
  box-shadow: none !important;
}
:deep(.custom-date-picker .el-input__inner) {
  font-size: 13px;
  color: #111827;
  height: 100%;
}
:deep(.custom-date-picker .el-input__prefix) {
  color: #9ca3af;
}

.checkbox-group {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}
.check-chip {
  display: inline-flex;
  align-items: center;
  padding: 6px 12px;
  border: 1px solid #e5e7eb;
  border-radius: 7px;
  font-size: 13px;
  color: #6b7280;
  cursor: pointer;
  transition: all 0.12s;
  user-select: none;
}
.check-chip.checked {
  background: #e1f5ee;
  border-color: #9fe1cb;
  color: #085041;
  font-weight: 500;
}
.check-chip.disabled {
  opacity: 0.4;
  cursor: not-allowed;
}
.check-hidden {
  display: none;
}
.info-banner {
  display: flex;
  gap: 10px;
  align-items: flex-start;
  background: #e6f1fb;
  border: 1px solid #b5d4f4;
  border-radius: 8px;
  padding: 10px 14px;
  font-size: 12px;
  color: #0c447c;
  margin-top: 10px;
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
@media (max-width: 600px) {
  .two-section-row {
    grid-template-columns: 1fr;
  }
}
</style>
<style>
.el-picker__popper.payroll-date-popper,
.payroll-date-popper {
  z-index: 20000 !important;
}
.el-select__popper.payroll-select-popper,
.payroll-select-popper {
  z-index: 20001 !important;
}
</style>
