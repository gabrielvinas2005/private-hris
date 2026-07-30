<template>
  <div class="opf-shell">
    <!-- Form header -->
    <div class="form-top">
      <div>
        <h3 class="form-title">
          {{
            isDetailView
              ? "Overtime payroll details"
              : isEdit
                ? "Edit overtime payroll"
                : "Create overtime payroll"
          }}
        </h3>
        <p class="form-sub">
          Configure interval, period, and employee selection.
        </p>
      </div>
      <button class="btn-back" @click="handleClose">
        <svg width="13" height="13" viewBox="0 0 13 13" fill="none">
          <path
            d="M8 3L4 6.5 8 10"
            stroke="currentColor"
            stroke-width="1.4"
            stroke-linecap="round"
            stroke-linejoin="round"
          />
        </svg>
        Back to list
      </button>
    </div>

    <!-- Loading overlay -->
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Loading…</p>
    </div>

    <div v-else class="form-body">
      <!-- Top fields -->
      <div class="section-card">
        <div class="section-label">Schedule configuration</div>
        <div class="field-grid three-col">
          <div class="field">
            <label class="field-label"
              >Payroll interval <span class="req">*</span></label
            >
            <select
              v-model="formData.payroll_interval_id"
              class="field-input"
              :disabled="isDetailView"
              @change="handleIntervalChange"
            >
              <option value="" disabled>Select interval</option>
              <option v-for="i in payrollIntervals" :key="i.id" :value="i.id">
                {{ i.name }}
              </option>
            </select>
          </div>
          <div class="field">
            <label class="field-label"
              >Payroll period <span class="req">*</span></label
            >
            <el-select
              v-model="formData.payroll_period_id"
              :disabled="isDetailView"
              @change="handlePeriodChange"
              placeholder="Select period"
              style="width: 100%"
            >
              <el-option
                v-for="p in payrollPeriods"
                :key="p.id"
                :value="p.id"
                :label="getPeriodLabel(p)"
              >
                <div class="flex justify-between items-center">
                  <span>
                    {{ getPeriodLabel(p) }}
                    <PayrollCutoffTag
                      v-for="t in p.cutoff_labels ||
                      (p.cutoff_name ? [p.cutoff_name] : [])"
                      :key="t"
                      :name="t"
                    />
                  </span>
                </div>
              </el-option>
            </el-select>
          </div>
          <div class="field">
            <label class="field-label">Date forwarded</label>
            <input
              type="date"
              v-model="formData.date_forwarded"
              class="field-input"
              :disabled="isDetailView"
            />
          </div>
        </div>
      </div>

      <!-- Employee section -->
      <div class="section-card" v-if="formData.payroll_period_id">
        <!-- Summary strip -->
        <div v-if="employees.length" class="emp-summary">
          <div class="emp-sum-item">
            <span class="esi-label">Total employees</span
            ><strong>{{ totalEmployees }}</strong>
          </div>
          <div class="emp-sum-item">
            <span class="esi-label">Total hours</span
            ><strong>{{ formatNumber(totalHours) }}</strong>
          </div>
          <div class="emp-sum-item">
            <span class="esi-label">Total computed earned</span
            ><strong>₱{{ formatCurrency(totalComputedEarned) }}</strong>
          </div>
          <div class="emp-sum-item">
            <span class="esi-label">Total OT adjustment</span
            ><strong>₱{{ formatCurrency(totalOtAdjustment) }}</strong>
          </div>
          <div class="emp-sum-item">
            <span class="esi-label">Total final earned</span
            ><strong>₱{{ formatCurrency(totalEarned) }}</strong>
          </div>
        </div>

        <!-- Section header -->
        <div class="section-row">
          <div class="section-label">Selected employees</div>
          <button
            v-if="!isDetailView"
            class="btn-primary-sm"
            :disabled="!canAddEmployees"
            @click="handleAddEmployeesClick"
          >
            <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
              <path
                d="M6 2v8M2 6h8"
                stroke="currentColor"
                stroke-width="1.5"
                stroke-linecap="round"
              />
            </svg>
            Add employees
          </button>
        </div>

        <!-- Employee table -->
        <div class="table-wrap" v-if="employees.length">
          <table>
            <thead>
              <tr>
                <th style="width: 110px">Employee no.</th>
                <th>Name</th>
                <th>Position</th>
                <th class="num-col">Total hours</th>
                <th class="num-col">Salary</th>
                <th class="num-col">Computed earned</th>
                <th class="num-col">OT adjustment</th>
                <th class="num-col">Final earned</th>
                <th v-if="!isDetailView" style="width: 70px"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="emp in employees" :key="emp.dtl_id">
                <td class="mono muted">{{ emp.employee_no }}</td>
                <td class="emp-name">{{ emp.name }}</td>
                <td class="muted">{{ emp.position }}</td>
                <td class="num-td">{{ emp.total_hours }}</td>
                <td class="num-td">₱{{ formatCurrency(emp.salary) }}</td>
                <td class="num-td">₱{{ formatCurrency(emp.computed_earned) }}</td>
                <td class="num-td">₱{{ formatCurrency(emp.ot_adjustment) }}</td>
                <td class="num-td">₱{{ formatCurrency(emp.earned) }}</td>
                <td v-if="!isDetailView">
                  <button class="act-danger" @click="removeEmployee(emp)">
                    <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
                      <path
                        d="M1.5 3h9M4.5 3V2a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 .5.5v1M10 3l-.5 6.5a.5.5 0 0 1-.5.5H3a.5.5 0 0 1-.5-.5L2 3"
                        stroke="currentColor"
                        stroke-width="1.2"
                        stroke-linecap="round"
                      />
                    </svg>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-else class="empty-emp">No employees added yet.</div>

        <!-- Overtime types summary -->
        <div v-if="overtimeTypes.length" class="ot-types-section">
          <div class="section-label">Overtime types summary</div>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Type</th>
                  <th class="num-col" style="width: 100px">Rate</th>
                  <th class="num-col" style="width: 120px">Total hours</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="ot in overtimeTypes" :key="ot.overtime_type">
                  <td>{{ ot.overtime_type }}</td>
                  <td class="num-td">{{ ot.rate }}%</td>
                  <td class="num-td">{{ ot.total_hours }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- No period selected placeholder -->
      <div v-else class="empty-period">
        <svg width="36" height="36" viewBox="0 0 36 36" fill="none">
          <rect
            x="4"
            y="6"
            width="28"
            height="24"
            rx="3"
            stroke="#d1d5db"
            stroke-width="1.5"
          />
          <path
            d="M10 12h16M10 17h10"
            stroke="#d1d5db"
            stroke-width="1.5"
            stroke-linecap="round"
          />
        </svg>
        <p>Select a payroll interval and period above to manage employees.</p>
      </div>

      <!-- Form actions -->
      <div class="form-actions">
        <button class="btn-ghost" @click="handleClose">Cancel</button>
        <button
          v-if="!isDetailView"
          class="btn-primary"
          :disabled="!isFormValid || loading"
          @click="handleSave"
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
          {{ loading ? "Saving…" : isEdit ? "Update" : "Create" }}
        </button>
      </div>
    </div>

    <!-- Add Employees modal -->
    <Teleport to="body">
      <Transition name="fade">
        <div
          v-if="showEmployeeDialog"
          class="modal-backdrop"
          @click.self="showEmployeeDialog = false"
        >
          <div class="modal-card">
            <div class="modal-header">
              <div>
                <h3 class="modal-title">Select employees</h3>
                <p class="modal-sub">
                  Only employees not already added to this overtime payroll are
                  shown.
                </p>
              </div>
              <button class="close-btn" @click="showEmployeeDialog = false">
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
              <div class="search-wrap">
                <svg
                  width="13"
                  height="13"
                  viewBox="0 0 13 13"
                  fill="none"
                  class="search-icon-sm"
                >
                  <circle
                    cx="5.5"
                    cy="5.5"
                    r="3.5"
                    stroke="currentColor"
                    stroke-width="1.2"
                  />
                  <path
                    d="M8.5 8.5l2.5 2.5"
                    stroke="currentColor"
                    stroke-width="1.2"
                    stroke-linecap="round"
                  />
                </svg>
                <input
                  v-model="employeeSearchQuery"
                  class="search-input"
                  placeholder="Search employees…"
                />
              </div>

              <div class="table-wrap">
                <table>
                  <thead>
                    <tr>
                      <th style="width: 36px">
                        <input
                          type="checkbox"
                          class="row-check"
                          :checked="allDialogSelected"
                          @change="toggleAllDialog"
                        />
                      </th>
                      <th style="width: 100px">Employee no.</th>
                      <th>Name</th>
                      <th>Position</th>
                      <th class="num-col">Total hours</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr
                      v-for="emp in filteredUnselectedEmployees"
                      :key="`${emp.id}-${emp.overtime_type_id}`"
                    >
                      <td>
                        <input
                          type="checkbox"
                          class="row-check"
                          :checked="
                            tempSelectedIds.has(
                              `${emp.id}-${emp.overtime_type_id}`,
                            )
                          "
                          @change="toggleDialogRow(emp)"
                        />
                      </td>
                      <td class="mono muted">{{ emp.employee_no }}</td>
                      <td class="emp-name">{{ emp.name }}</td>
                      <td class="muted">{{ emp.position }}</td>
                      <td class="num-td">{{ emp.total_hours }}</td>
                    </tr>
                    <tr v-if="!filteredUnselectedEmployees.length">
                      <td
                        colspan="5"
                        style="
                          text-align: center;
                          color: #9ca3af;
                          padding: 24px;
                        "
                      >
                        No employees available
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div class="modal-footer">
              <button class="btn-ghost" @click="showEmployeeDialog = false">
                Cancel
              </button>
              <button
                class="btn-primary"
                :disabled="tempSelectedIds.size === 0"
                @click="addSelectedEmployees"
              >
                Add selected ({{ tempSelectedIds.size }})
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, nextTick } from "vue";
import { useOvertimePayroll } from "../../../Composables/useOvertimePayroll.js";
import { payrollProcessApi } from "../../../services/api.js";
import PayrollCutoffTag from "@/components/Shared/PayrollCutoffTag.vue";

const props = defineProps({
  editData: { type: Object, default: null },
  isDetailView: { type: Boolean, default: false },
});
const emit = defineEmits(["saved", "close"]);

const {
  loading,
  formData,
  payrollPeriods,
  payrollIntervals,
  employees,
  employeeUnselected,
  overtimeTypes,
  isFormValid,
  loadFormData,
  saveOvertimePayroll,
  addEmployees,
  resetFormData,
} = useOvertimePayroll();

const isEdit = computed(() => props.editData && props.editData.id > 0);
const showEmployeeDialog = ref(false);
const employeeSearchQuery = ref("");
const tempSelectedIds = ref(new Set());

const totalEmployees = computed(() => employees.value.length);
const totalHours = computed(() =>
  employees.value.reduce((s, e) => s + (Number(e.total_hours) || 0), 0),
);
const totalComputedEarned = computed(() =>
  employees.value.reduce((s, e) => s + (Number(e.computed_earned) || 0), 0),
);
const totalOtAdjustment = computed(() =>
  employees.value.reduce((s, e) => s + (Number(e.ot_adjustment) || 0), 0),
);
const totalEarned = computed(() =>
  employees.value.reduce((s, e) => s + (Number(e.earned) || 0), 0),
);

const formatCurrency = (v) =>
  Number(v || 0).toLocaleString("en-PH", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
const formatNumber = (v) =>
  Number(v || 0).toLocaleString("en-PH", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
const canAddEmployees = computed(
  () =>
    !!formData.value?.payroll_interval_id &&
    !!formData.value?.payroll_period_id,
);

const getPeriodLabel = (p) => {
  if (!p) return "";
  const cutoff = String(p.cutoff_name || "").trim();
  if (p.release_date) {
    const d = new Date(p.release_date);
    if (!isNaN(d.getTime())) {
      const my = d.toLocaleDateString("en-PH", {
        month: "long",
        year: "numeric",
      });
      return cutoff ? `Monthly (${my}) — ${cutoff}` : `Monthly (${my})`;
    }
  }
  return String(p.name || "");
};

const ensureHeaderForEmployeeAdd = async () => {
  if (Number(formData.value?.id || 0) > 0) return;
  const result = await saveOvertimePayroll(0, {
    payroll_interval_id: formData.value.payroll_interval_id,
    payroll_period_id: formData.value.payroll_period_id,
    date_forwarded: formData.value.date_forwarded,
  });
  const newId = result?.data?.id ?? result?.id ?? 0;
  if (!newId) throw new Error("Unable to create overtime payroll header");
  formData.value.id = newId;
  await loadFormData(newId);
};

const handleAddEmployeesClick = async () => {
  if (!canAddEmployees.value) return;
  try {
    await ensureHeaderForEmployeeAdd();
    tempSelectedIds.value.clear();
    showEmployeeDialog.value = true;
  } catch (e) {
    console.error(e);
  }
};

const filteredUnselectedEmployees = computed(() => {
  const selectedKeys = new Set(
    employees.value.map((e) => `${e.employee_id}-${e.overtime_type_id}`),
  );
  const base = (employeeUnselected.value || []).filter(
    (e) => !selectedKeys.has(`${e.id}-${e.overtime_type_id}`),
  );
  if (!employeeSearchQuery.value) return base;
  const q = employeeSearchQuery.value.toLowerCase();
  return base.filter(
    (e) =>
      String(e.employee_no).toLowerCase().includes(q) ||
      String(e.name).toLowerCase().includes(q) ||
      String(e.position).toLowerCase().includes(q),
  );
});

const allDialogSelected = computed(
  () =>
    filteredUnselectedEmployees.value.length > 0 &&
    filteredUnselectedEmployees.value.every((e) =>
      tempSelectedIds.value.has(`${e.id}-${e.overtime_type_id}`),
    ),
);
const toggleAllDialog = () => {
  if (allDialogSelected.value)
    filteredUnselectedEmployees.value.forEach((e) =>
      tempSelectedIds.value.delete(`${e.id}-${e.overtime_type_id}`),
    );
  else
    filteredUnselectedEmployees.value.forEach((e) =>
      tempSelectedIds.value.add(`${e.id}-${e.overtime_type_id}`),
    );
};
const toggleDialogRow = (emp) => {
  const key = `${emp.id}-${emp.overtime_type_id}`;
  if (tempSelectedIds.value.has(key)) tempSelectedIds.value.delete(key);
  else tempSelectedIds.value.add(key);
};

const addSelectedEmployees = async () => {
  if (tempSelectedIds.value.size === 0) return;
  const selected = filteredUnselectedEmployees.value.filter((e) =>
    tempSelectedIds.value.has(`${e.id}-${e.overtime_type_id}`),
  );
  const employeeData = {
    id: selected.map((e) => e.id),
    overtime_type_id: selected.map((e) => e.overtime_type_id),
    select: selected.map((e) => e.id + e.overtime_type_id),
  };
  try {
    await addEmployees(formData.value.id, employeeData);
    await loadFormData(formData.value.id);
    tempSelectedIds.value.clear();
    showEmployeeDialog.value = false;
  } catch (e) {
    console.error(e);
  }
};

const removeEmployee = (emp) => {
  const idx = employees.value.findIndex((e) => e.dtl_id === emp.dtl_id);
  if (idx > -1) employees.value.splice(idx, 1);
};

const handleIntervalChange = () => {};
const handlePeriodChange = () => {};

watch(
  () => [formData.value.payroll_interval_id, formData.value.date_forwarded],
  async ([intervalId, date]) => {
    if (!intervalId) return;
    try {
      const res =
        await payrollProcessApi.getPostedPayrollPeriodsByInterval(intervalId);
      payrollPeriods.value = res.data?.data || res.data || [];
      if (date) {
        const d = new Date(date);
        const month = d.toLocaleString("en-US", { month: "long" });
        const year = String(d.getFullYear());
        const match = payrollPeriods.value.find((p) => {
          const l = String(p.name || p.payroll_period || "");
          return (
            l.toLowerCase().includes(month.toLowerCase()) && l.includes(year)
          );
        });
        if (match) formData.value.payroll_period_id = match.id;
      }
    } catch {}
  },
  { immediate: false },
);

const initializeForm = async () => {
  try {
    if (isEdit.value) await loadFormData(props.editData.id);
    else {
      resetFormData();
      await loadFormData(0);
    }
  } catch (e) {
    console.error(e);
  }
};

const handleSave = async () => {
  try {
    const saveData = {
      payroll_interval_id: formData.value.payroll_interval_id,
      payroll_period_id: formData.value.payroll_period_id,
      date_forwarded: formData.value.date_forwarded,
    };
    const result = await saveOvertimePayroll(formData.value.id, saveData);
    const newId = result?.data?.id ?? result?.id ?? formData.value.id;
    if (newId && newId !== formData.value.id) formData.value.id = newId;
    emit("saved");
    if (formData.value.id > 0) await loadFormData(formData.value.id);
  } catch (e) {
    console.error(e);
  }
};

const handleClose = () => {
  emit("close");
  resetFormData();
};
watch(showEmployeeDialog, (v) => {
  if (typeof document !== "undefined")
    document.body.style.overflow = v ? "hidden" : "";
});
onMounted(async () => {
  await initializeForm();
});
</script>

<style scoped>
.opf-shell {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

/* Form top */
.form-top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
}
.form-title {
  font-size: 18px;
  font-weight: 500;
  color: #111827;
  margin: 0 0 3px;
}
.form-sub {
  font-size: 13px;
  color: #9ca3af;
  margin: 0;
}
.btn-back {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: transparent;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 7px 14px;
  font-size: 13px;
  color: #374151;
  cursor: pointer;
}
.btn-back:hover {
  background: #f3f4f6;
}

/* Section cards */
.section-card {
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 16px 18px;
  display: flex;
  flex-direction: column;
  gap: 14px;
}
.section-label {
  font-size: 11px;
  font-weight: 500;
  color: #9ca3af;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}
.section-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

/* Fields */
.field-grid {
  display: grid;
  gap: 14px;
}
.field-grid.three-col {
  grid-template-columns: 1fr 1fr 1fr;
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

/* Employee summary strip */
.emp-summary {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
  gap: 10px;
  padding: 12px 14px;
  background: #f9fafb;
  border-radius: 10px;
}
.emp-sum-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
  background: #ffffff;
  border: 1px solid #eef2f7;
  border-radius: 8px;
  padding: 10px 12px;
  min-height: 58px;
}
.esi-label {
  font-size: 11px;
  color: #9ca3af;
  line-height: 1.2;
}
.emp-sum-item strong {
  font-size: 15px;
  font-weight: 500;
  color: #111827;
  line-height: 1.2;
  word-break: break-word;
}

/* Table */
.table-wrap {
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  overflow: hidden;
}
table {
  width: 100%;
  border-collapse: collapse;
  font-size: 13px;
}
thead {
  background: #f9fafb;
}
thead th {
  padding: 9px 14px;
  text-align: left;
  font-weight: 500;
  font-size: 12px;
  color: #6b7280;
  border-bottom: 1px solid #e5e7eb;
  white-space: nowrap;
}
thead .num-col {
  text-align: right;
}
tbody tr {
  border-bottom: 1px solid #f3f4f6;
}
tbody tr:last-child {
  border-bottom: none;
}
tbody tr:hover {
  background: #fafafa;
}
td {
  padding: 9px 14px;
  color: #111827;
  vertical-align: middle;
}
.mono {
  font-family: monospace;
  font-size: 12px;
}
.muted {
  color: #6b7280;
}
.emp-name {
  font-weight: 500;
}
.num-td {
  text-align: right;
  font-family: monospace;
}
.row-check {
  width: 14px;
  height: 14px;
  cursor: pointer;
  accent-color: #111827;
}
.act-danger {
  background: transparent;
  border: none;
  padding: 5px 6px;
  border-radius: 6px;
  color: #9ca3af;
  cursor: pointer;
  display: flex;
  align-items: center;
}
.act-danger:hover {
  background: #fee2e2;
  color: #a32d2d;
}

/* OT types section */
.ot-types-section {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

/* Empty states */
.empty-emp {
  font-size: 13px;
  color: #9ca3af;
  text-align: center;
  padding: 20px 0;
}
.empty-period {
  text-align: center;
  padding: 40px 0;
  color: #9ca3af;
  font-size: 14px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
}
.empty-period svg {
  display: block;
}

/* Form actions */
.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  padding-top: 8px;
  border-top: 1px solid #f3f4f6;
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
.btn-primary-sm {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  background: #409eff;
  color: #fff;
  border: none;
  border-radius: 7px;
  padding: 6px 12px;
  font-size: 12px;
  font-weight: 500;
  cursor: pointer;
}
.btn-primary-sm:hover:not(:disabled) {
  opacity: 0.88;
}
.btn-primary-sm:disabled {
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

/* Loading */
.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px;
  gap: 12px;
  color: #9ca3af;
  font-size: 13px;
}
.spinner {
  width: 24px;
  height: 24px;
  border: 3px solid #e5e7eb;
  border-top-color: #185fa5;
  border-radius: 50%;
  animation: spin 0.9s linear infinite;
}

/* Modal */
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
  max-width: 700px;
  max-height: 88vh;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.14);
}
.modal-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  padding: 18px 24px;
  border-bottom: 1px solid #f3f4f6;
  flex-shrink: 0;
}
.modal-title {
  font-size: 16px;
  font-weight: 500;
  color: #111827;
  margin: 0 0 3px;
}
.modal-sub {
  font-size: 13px;
  color: #9ca3af;
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
  padding: 16px 24px;
  overflow-y: auto;
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  padding: 14px 24px;
  border-top: 1px solid #f3f4f6;
  flex-shrink: 0;
}

/* Search in modal */
.search-wrap {
  position: relative;
}
.search-icon-sm {
  position: absolute;
  left: 8px;
  top: 50%;
  transform: translateY(-50%);
  color: #9ca3af;
  pointer-events: none;
}
.search-input {
  width: 100%;
  font-size: 13px;
  padding: 7px 10px 7px 28px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  outline: none;
  color: #111827;
}
.search-input:focus {
  border-color: #6b7280;
}

.flex {
  display: flex;
}
.justify-between {
  justify-content: space-between;
}
.items-center {
  align-items: center;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
.spin-icon {
  animation: spin 0.8s linear infinite;
}
@media (max-width: 680px) {
  .field-grid.three-col {
    grid-template-columns: 1fr;
  }
}
</style>
