<!-- PayrollSalaryAdjustmentDialog.vue -->
<template>
  <Teleport to="body">
    <Transition name="fade">
      <div v-if="visible" class="modal-backdrop" @click.self="handleClose">
        <div class="modal-card">
          <div class="modal-header">
            <div>
              <h3 class="modal-title">Salary adjustment</h3>
              <p class="modal-sub">
                Adjustments apply on top of computed net pay and take effect on
                next process run.
              </p>
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
                >Enter a <strong>positive</strong> amount to add extra pay
                (e.g., 1500) or a <strong>negative</strong> amount to reduce pay
                (e.g., -500).</span
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
              <div class="filter-right">
                <button
                  type="button"
                  class="view-toggle"
                  :class="{ active: !showLowNetOnly }"
                  @click="handleShowAll"
                >
                  Show all
                </button>
                <button
                  type="button"
                  class="view-toggle danger"
                  :class="{ active: showLowNetOnly }"
                  @click="handleShowBelow5000"
                >
                  Below ₱5,000
                </button>
              </div>
            </div>

            <div class="table-wrap" v-if="!loading">
              <div class="table-scroll">
                <table>
                  <thead>
                    <tr>
                      <th style="width: 110px">Employee no.</th>
                      <th>Employee name</th>
                      <th class="num-col">Basic salary</th>
                      <th class="num-col">Net pay</th>
                      <th class="num-col">Current adjustment</th>
                      <th class="num-col" style="width: 180px">New adjustment</th>
                      <th>Remarks</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr
                      v-for="row in filteredEmployees"
                      :key="row.employee_id"
                      :class="{
                        'row-mod': row.amount !== row.initial_adjustment,
                      }"
                    >
                      <td class="mono muted">{{ row.employee_no }}</td>
                      <td class="emp-name">{{ row.name }}</td>
                      <td class="num-td muted">₱{{ fc(row.base_salary) }}</td>
                      <td
                        class="num-td"
                        :class="{ 'low-net': row.net_pay < 5000 }"
                      >
                        ₱{{ fc(row.net_pay) }}
                      </td>
                      <td class="num-td muted">
                        ₱{{ fc(row.initial_adjustment) }}
                      </td>
                      <td class="num-td">
                        <div class="adj-row">
                          <span class="adj-prefix">₱</span>
                          <input
                            type="number"
                            v-model.number="row.amount"
                            :step="100"
                            class="adj-input"
                            placeholder="0.00"
                          />
                        </div>
                      </td>
                      <td>
                        <input
                          type="text"
                          v-model="row.remarks"
                          class="remark-input"
                          placeholder="Optional…"
                          maxlength="500"
                        />
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div v-else class="loading-rows">
              <div v-for="i in 6" :key="i" class="skeleton-row" />
            </div>
          </div>

          <div class="modal-footer">
            <button class="btn-ghost" @click="handleClose">Cancel</button>
            <button
              class="btn-primary"
              :disabled="!hasChanges || !payrollPeriodId || saving"
              @click="handleSave"
            >
              {{ saving ? "Saving…" : "Save adjustments" }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed, ref, watch } from "vue";
import { usePayrollProcess } from "../../Composables/usePayrollProcess";

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  payrollPeriodId: { type: [Number, String], default: null },
  employees: { type: Array, default: () => [] },
});
const emit = defineEmits(["update:modelValue", "saved"]);
const { getSalaryAdjustments, saveSalaryAdjustments } = usePayrollProcess();

const visible = computed({
  get: () => props.modelValue,
  set: (v) => emit("update:modelValue", v),
});
const loading = ref(false);
const saving = ref(false);
const rows = ref([]);
const searchQuery = ref("");
const filterType = ref("");
const showLowNetOnly = ref(false);
const parseNetPay = (v) => {
  const n = Number(v);
  return Number.isNaN(n) ? 0 : n;
};
const fc = (v) =>
  Number(v || 0).toLocaleString("en-US", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });

const buildRows = (adj = []) => {
  const map = new Map((adj || []).map((a) => [a.employee_id, a]));
  rows.value = (props.employees || []).map((emp) => {
    const existing = map.get(emp.employee_id);
    const initial =
      existing && typeof existing.amount === "number"
        ? existing.amount
        : Number(emp.salary_adj_amount || 0);
    return {
      employee_id: emp.employee_id,
      employee_no: emp.employee_no,
      name: emp.name,
      base_salary: Number(emp.salary || 0),
      net_pay: parseNetPay(emp.net_pay),
      initial_adjustment: Number(initial || 0),
      amount: Number(initial || 0),
      remarks: existing?.remarks || "",
    };
  });
};
const loadData = async () => {
  if (!props.payrollPeriodId) {
    buildRows([]);
    return;
  }
  try {
    loading.value = true;
    const adj = await getSalaryAdjustments(props.payrollPeriodId);
    buildRows(adj);
  } catch {
    buildRows([]);
  } finally {
    loading.value = false;
  }
};
const filteredEmployees = computed(() => {
  let f = rows.value;
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase();
    f = f.filter((e) => e.name?.toLowerCase().includes(q));
  }
  if (filterType.value === "adjusted") f = f.filter((e) => e.amount !== 0);
  if (filterType.value === "not-adjusted")
    f = f.filter((e) => !e.amount || e.amount === 0);
  if (showLowNetOnly.value)
    f = f.filter((e) => parseNetPay(e.net_pay) < 5000);
  return f;
});
const hasChanges = computed(() =>
  rows.value.some(
    (r) =>
      Number(r.amount || 0) !== Number(r.initial_adjustment || 0) ||
      (r.remarks || "").trim().length > 0,
  ),
);
const handleClose = () => emit("update:modelValue", false);
const handleClearAll = () => {
  searchQuery.value = "";
  filterType.value = "";
  showLowNetOnly.value = false;
};
const handleShowAll = () => {
  showLowNetOnly.value = false;
};
const handleShowBelow5000 = () => {
  showLowNetOnly.value = true;
};
const handleSave = async () => {
  if (!props.payrollPeriodId) return;
  try {
    saving.value = true;
    await saveSalaryAdjustments(
      props.payrollPeriodId,
      rows.value.map((r) => ({
        employee_id: r.employee_id,
        amount: Number(r.amount || 0),
        remarks: (r.remarks || "").trim() || null,
      })),
    );
    emit("saved");
    emit("update:modelValue", false);
  } finally {
    saving.value = false;
  }
};
watch(visible, (v) => {
  if (v) {
    showLowNetOnly.value = false;
    loadData();
  }
});
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
  max-width: 900px;
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
.btn-ghost {
  background: transparent;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 8px 16px;
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
tbody tr.row-mod {
  background: #f0fdf4;
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
  color: #9ca3af;
}
.num-td {
  text-align: right;
  font-family: monospace;
}
.emp-name {
  font-weight: 500;
}
.adj-row {
  display: inline-flex;
  align-items: center;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  overflow: hidden;
}
.adj-prefix {
  font-size: 12px;
  color: #9ca3af;
  padding: 0 6px 0 8px;
  background: #f9fafb;
  border-right: 1px solid #e5e7eb;
  line-height: 30px;
}
.adj-input {
  font-size: 12px;
  font-family: monospace;
  text-align: right;
  padding: 5px 8px;
  border: none;
  outline: none;
  width: 90px;
  color: #111827;
}
.remark-input {
  width: 100%;
  font-size: 12px;
  padding: 5px 8px;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  outline: none;
  color: #374151;
}
.remark-input:focus {
  border-color: #6b7280;
}
.loading-rows {
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.skeleton-row {
  height: 44px;
  border-radius: 8px;
  background: linear-gradient(90deg, #f3f4f6 25%, #e5e7eb 50%, #f3f4f6 75%);
  background-size: 200% 100%;
  animation: shimmer 1.4s infinite;
}
@keyframes shimmer {
  0% {
    background-position: 200% 0;
  }
  100% {
    background-position: -200% 0;
  }
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
.filter-right {
  display: flex;
  gap: 6px;
  margin-left: auto;
}
.view-toggle {
  background: transparent;
  border: 1px solid #e5e7eb;
  border-radius: 7px;
  padding: 6px 12px;
  font-size: 12px;
  color: #6b7280;
  cursor: pointer;
  white-space: nowrap;
}
.view-toggle.active {
  background: #409eff;
  color: #fff;
  border-color: #409eff;
}
.view-toggle.danger.active {
  background: #a32d2d;
  border-color: #a32d2d;
  color: #fff;
}
.num-td.low-net {
  color: #a32d2d;
  font-weight: 500;
}
</style>
