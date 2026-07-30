<template>
  <Teleport to="body">
    <Transition name="fade">
      <div v-if="visible" class="modal-backdrop" @click.self="handleClose">
        <div class="modal-card">
          <div class="modal-header">
            <div>
              <h3 class="modal-title">Tax adjustment</h3>
              <p class="modal-sub">{{ payrollData?.payroll_period || "" }}</p>
            </div>
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

          <div class="modal-body">
            <!-- Summary strip -->
            <div class="summary-strip">
              <div class="strip-item">
                <span class="strip-label">Total employees</span
                ><strong>{{ employeeForTaxAdjustments?.length || 0 }}</strong>
              </div>
              <div class="strip-item">
                <span class="strip-label">Adjustments made</span
                ><strong>{{ adjustmentCount }}</strong>
              </div>
              <div class="strip-item">
                <span class="strip-label">Total adjustment</span
                ><strong :class="totalAdjustment >= 0 ? 'pos' : 'neg'"
                  >{{ totalAdjustment >= 0 ? "+" : "" }}₱{{
                    formatCurrency(totalAdjustment)
                  }}</strong
                >
              </div>
            </div>

            <!-- Instruction -->
            <div class="info-banner">
              <svg
                width="13"
                height="13"
                viewBox="0 0 13 13"
                fill="none"
                style="flex-shrink: 0"
              >
                <circle
                  cx="6.5"
                  cy="6.5"
                  r="5"
                  stroke="#185fa5"
                  stroke-width="1.2"
                />
                <path
                  d="M6.5 6v3M6.5 4.5h.01"
                  stroke="#185fa5"
                  stroke-width="1.2"
                  stroke-linecap="round"
                />
              </svg>
              <span
                >Enter a <strong>positive</strong> number to increase tax (e.g.,
                100) or a <strong>negative</strong> number to decrease (e.g.,
                -100). Use the ± buttons for quick ₱100 steps.</span
              >
            </div>

            <!-- Filters -->
            <div class="filter-bar">
              <div class="search-wrap">
                <svg
                  width="13"
                  height="13"
                  viewBox="0 0 13 13"
                  fill="none"
                  class="search-icon"
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
                  v-model="searchQuery"
                  class="search-input"
                  placeholder="Search employees…"
                />
              </div>
              <select v-model="filterType" class="filter-select">
                <option value="">All</option>
                <option value="adjusted">With adjustments</option>
                <option value="not-adjusted">No adjustments</option>
              </select>
              <button class="btn-ghost" @click="handleClearAll">
                Clear all
              </button>
            </div>

            <!-- Table -->
            <div class="table-wrap">
              <div class="table-scroll">
                <table>
                  <thead>
                    <tr>
                      <th>Employee</th>
                      <th class="num-col">Current tax</th>
                      <th class="num-col" style="width: 200px">Adjustment</th>
                      <th class="num-col">New tax</th>
                      <th class="num-col">Difference</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr
                      v-for="emp in filteredEmployees"
                      :key="emp.employee_id"
                      :class="{ 'row-adjusted': emp.adjustment !== 0 }"
                    >
                      <td class="emp-name-cell">{{ emp.name }}</td>
                      <td class="num-td muted">
                        ₱{{ formatCurrency(emp.tax) }}
                      </td>
                      <td class="num-td">
                        <div class="adj-row">
                          <button
                            class="adj-btn"
                            @click="decreaseAdjustment(emp)"
                          >
                            −
                          </button>
                          <input
                            type="number"
                            v-model.number="emp.adjustment"
                            :min="-(emp.tax || 0)"
                            :max="999999"
                            :step="100"
                            class="adj-input"
                          />
                          <button
                            class="adj-btn"
                            @click="increaseAdjustment(emp)"
                          >
                            +
                          </button>
                        </div>
                      </td>
                      <td
                        class="num-td"
                        :class="
                          emp.adjustment > 0
                            ? 'pos'
                            : emp.adjustment < 0
                              ? 'neg'
                              : ''
                        "
                      >
                        ₱{{ formatCurrency(getNewTax(emp)) }}
                      </td>
                      <td
                        class="num-td"
                        :class="
                          emp.adjustment > 0
                            ? 'pos'
                            : emp.adjustment < 0
                              ? 'neg'
                              : 'muted'
                        "
                      >
                        {{ emp.adjustment > 0 ? "+" : "" }}₱{{
                          formatCurrency(emp.adjustment || 0)
                        }}
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Adjustment summary -->
            <div v-if="adjustmentCount > 0" class="adj-summary">
              <div class="adj-sum-item">
                <span>Employees adjusted</span
                ><strong>{{ adjustmentCount }}</strong>
              </div>
              <div class="adj-sum-item">
                <span>Total increase</span
                ><strong class="pos"
                  >+₱{{ formatCurrency(positiveAdjustments) }}</strong
                >
              </div>
              <div class="adj-sum-item">
                <span>Total decrease</span
                ><strong class="neg"
                  >−₱{{ formatCurrency(negativeAdjustments) }}</strong
                >
              </div>
              <div class="adj-sum-item">
                <span>Net adjustment</span
                ><strong :class="totalAdjustment >= 0 ? 'pos' : 'neg'"
                  >{{ totalAdjustment >= 0 ? "+" : "−" }}₱{{
                    formatCurrency(Math.abs(totalAdjustment))
                  }}</strong
                >
              </div>
            </div>
          </div>

          <div class="modal-footer">
            <button class="btn-ghost" @click="handleClose">Cancel</button>
            <button
              class="btn-primary"
              :disabled="adjustmentCount === 0 || saving"
              @click="handleSaveAdjustments"
            >
              {{ saving ? "Saving…" : `Save adjustments (${adjustmentCount})` }}
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
  payrollData: { type: Object, default: () => ({}) },
  employeeForTaxAdjustments: { type: Array, default: () => [] },
});
const emit = defineEmits(["update:modelValue", "adjustments-saved"]);
const { adjustTaxAmounts } = usePayrollProcess();

const visible = computed({
  get: () => props.modelValue,
  set: (v) => emit("update:modelValue", v),
});
const saving = ref(false);
const searchQuery = ref("");
const filterType = ref("");
const employeesWithAdjustments = ref([]);

const formatCurrency = (v) => {
  const n = Number(v || 0);
  return n.toLocaleString("en-US", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
};
const parseNum = (v) => {
  const n = Number(v);
  return Number.isFinite(n) ? n : 0;
};
const getNewTax = (emp) =>
  Math.max(0, parseNum(emp?.tax) + parseNum(emp?.adjustment));
const initializeEmployees = () => {
  employeesWithAdjustments.value = (props.employeeForTaxAdjustments || []).map(
    (e) => ({ ...e, tax: parseNum(e.tax), adjustment: 0 }),
  );
};
const filteredEmployees = computed(() => {
  let f = employeesWithAdjustments.value;
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase();
    f = f.filter((e) => e.name?.toLowerCase().includes(q));
  }
  if (filterType.value === "adjusted") f = f.filter((e) => e.adjustment !== 0);
  if (filterType.value === "not-adjusted")
    f = f.filter((e) => !e.adjustment || e.adjustment === 0);
  return f;
});
const adjustmentCount = computed(
  () =>
    employeesWithAdjustments.value.filter(
      (e) =>
        e.adjustment !== undefined &&
        e.adjustment !== null &&
        Number(e.adjustment) !== 0,
    ).length,
);
const totalAdjustment = computed(() =>
  employeesWithAdjustments.value.reduce((s, e) => s + (e.adjustment || 0), 0),
);
const positiveAdjustments = computed(() =>
  employeesWithAdjustments.value.reduce(
    (s, e) => s + (e.adjustment > 0 ? e.adjustment : 0),
    0,
  ),
);
const negativeAdjustments = computed(() =>
  employeesWithAdjustments.value.reduce(
    (s, e) => s + (e.adjustment < 0 ? Math.abs(e.adjustment) : 0),
    0,
  ),
);
const decreaseAdjustment = (e) => {
  e.adjustment = Math.max(-parseNum(e.tax), parseNum(e.adjustment) - 100);
};
const increaseAdjustment = (e) => {
  e.adjustment = Math.min(999999, parseNum(e.adjustment) + 100);
};
const handleClearAll = () => {
  employeesWithAdjustments.value.forEach((e) => (e.adjustment = 0));
};
const handleClose = () => {
  visible.value = false;
  searchQuery.value = "";
  filterType.value = "";
};
const handleSaveAdjustments = async () => {
  try {
    saving.value = true;
    const adjusted = employeesWithAdjustments.value.filter(
      (e) =>
        e.adjustment !== undefined &&
        e.adjustment !== null &&
        Number(e.adjustment) !== 0,
    );
    await adjustTaxAmounts(props.payrollData.id, {
      employee_id: adjusted.map((e) => e.employee_id),
      tax_amount: adjusted.map((e) => getNewTax(e)),
    });
    emit("adjustments-saved");
    handleClose();
  } catch (e) {
    console.error(e);
  } finally {
    saving.value = false;
  }
};
watch(
  () => props.modelValue,
  (v) => {
    if (v) initializeEmployees();
  },
);
watch(
  () => props.employeeForTaxAdjustments,
  () => {
    if (visible.value) initializeEmployees();
  },
  { deep: true },
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
  max-width: 860px;
  max-height: 90vh;
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
  padding: 18px 24px;
  overflow: hidden;
  flex: 1;
  min-height: 0;
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

.summary-strip {
  display: flex;
  gap: 20px;
  padding: 12px 16px;
  background: #f9fafb;
  border-radius: 10px;
  flex-shrink: 0;
}
.strip-item {
  display: flex;
  flex-direction: column;
  gap: 3px;
}
.strip-label {
  font-size: 11px;
  color: #9ca3af;
}
.strip-item strong {
  font-size: 16px;
  font-weight: 500;
  color: #111827;
}
.strip-item strong.pos {
  color: #085041;
}
.strip-item strong.neg {
  color: #a32d2d;
}

.info-banner {
  display: flex;
  gap: 10px;
  align-items: flex-start;
  background: #e6f1fb;
  border: 1px solid #b5d4f4;
  border-radius: 8px;
  padding: 10px 14px;
  font-size: 13px;
  color: #0c447c;
  flex-shrink: 0;
}

.filter-bar {
  display: flex;
  gap: 10px;
  align-items: center;
  flex-wrap: wrap;
  flex-shrink: 0;
}
.search-wrap {
  position: relative;
  flex: 1;
  min-width: 180px;
}
.search-icon {
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
.filter-select {
  font-size: 13px;
  padding: 7px 10px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  background: #fff;
  color: #111827;
  outline: none;
  min-width: 150px;
}
.btn-ghost {
  background: transparent;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 7px 14px;
  font-size: 13px;
  color: #6b7280;
  cursor: pointer;
}
.btn-ghost:hover {
  background: #f3f4f6;
}
.btn-primary {
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
  background: #e5e7eb;
  color: #6b7280;
  cursor: not-allowed;
}

.table-wrap {
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  overflow: hidden;
  flex: 1;
  min-height: 0;
  display: flex;
  flex-direction: column;
}
.table-scroll {
  flex: 1;
  min-height: 0;
  max-height: 52vh;
  overflow: auto;
  position: relative;
  isolation: isolate;
}
table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  font-size: 13px;
}
thead th {
  padding: 9px 14px;
  text-align: left;
  font-weight: 500;
  font-size: 12px;
  color: #6b7280;
  background: #f9fafb;
  border-bottom: 1px solid #e5e7eb;
  position: sticky;
  top: 0;
  z-index: 2;
  box-shadow: 0 1px 0 #e5e7eb;
}
thead .num-col {
  text-align: right;
}
tbody tr {
  border-bottom: 1px solid #f3f4f6;
  transition: background 0.1s;
}
tbody tr:last-child {
  border-bottom: none;
}
tbody tr:hover {
  background: #fafafa;
}
tbody tr.row-adjusted {
  background: #f0fdf4;
}
td {
  padding: 10px 14px;
  color: #111827;
  vertical-align: middle;
}
.emp-name-cell {
  font-weight: 500;
}
.num-td {
  text-align: right;
  font-family: monospace;
}
.muted {
  color: #9ca3af;
}
.pos {
  color: #085041;
  font-weight: 500;
}
.neg {
  color: #a32d2d;
  font-weight: 500;
}

.adj-row {
  display: flex;
  align-items: center;
  gap: 4px;
  justify-content: flex-end;
}
.adj-btn {
  width: 26px;
  height: 26px;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  background: #f9fafb;
  font-size: 14px;
  color: #374151;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.adj-btn:hover {
  background: #e5e7eb;
}
.adj-input {
  width: 80px;
  font-size: 12px;
  font-family: monospace;
  text-align: right;
  padding: 5px 6px;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  outline: none;
  color: #111827;
}
.adj-input:focus {
  border-color: #6b7280;
}

.adj-summary {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 10px;
  padding: 14px 16px;
  background: #f9fafb;
  border-radius: 10px;
  flex-shrink: 0;
}
.adj-sum-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  gap: 4px;
  font-size: 12px;
  color: #6b7280;
}
.adj-sum-item strong {
  font-size: 15px;
  color: #111827;
}
.adj-sum-item strong.pos {
  color: #085041;
}
.adj-sum-item strong.neg {
  color: #a32d2d;
}
</style>
