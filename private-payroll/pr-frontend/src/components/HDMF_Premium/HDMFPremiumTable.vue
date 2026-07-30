<template>
  <div class="ht-shell">
    <!-- Summary cards -->
    <div class="summary-grid">
      <div class="stat-card">
        <div class="stat-label">Total employees</div>
        <div class="stat-value info">{{ totalEmployees }}</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Active accounts</div>
        <div class="stat-value green">{{ ActiveAccounts }}</div>
      </div>
    </div>

    <!-- Controls row -->
    <div class="controls-row">
      <div class="search-wrap">
        <svg
          width="14"
          height="14"
          viewBox="0 0 14 14"
          fill="none"
          class="search-icon"
        >
          <circle
            cx="6"
            cy="6"
            r="4"
            stroke="currentColor"
            stroke-width="1.3"
          />
          <path
            d="M9.5 9.5l2.5 2.5"
            stroke="currentColor"
            stroke-width="1.3"
            stroke-linecap="round"
          />
        </svg>
        <input
          v-model="searchQuery"
          class="search-input"
          placeholder="Search by name, ID, or department…"
        />
      </div>
      <div class="page-size-wrap">
        <span class="ps-label">Show</span>
        <select
          v-model="pageSize"
          class="ps-select"
          @change="handlePageSizeChange"
        >
          <option :value="10">10</option>
          <option :value="20">20</option>
          <option :value="50">50</option>
          <option :value="100">100</option>
        </select>
        <span class="ps-label">entries</span>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Loading employees…</p>
    </div>

    <!-- Error -->
    <div v-else-if="error" class="error-banner">
      <svg
        width="14"
        height="14"
        viewBox="0 0 14 14"
        fill="none"
        style="flex-shrink: 0"
      >
        <path
          d="M7 2L1 12h12L7 2z"
          stroke="#a32d2d"
          stroke-width="1.3"
          stroke-linejoin="round"
        />
        <path
          d="M7 6v3M7 10.5h.01"
          stroke="#a32d2d"
          stroke-width="1.3"
          stroke-linecap="round"
        />
      </svg>
      {{ error }}
    </div>

    <!-- Empty -->
    <div v-else-if="!hasEmployees" class="empty-state">
      <svg width="36" height="36" viewBox="0 0 36 36" fill="none">
        <rect
          x="5"
          y="7"
          width="26"
          height="22"
          rx="3"
          stroke="#d1d5db"
          stroke-width="1.5"
        />
        <path
          d="M11 13h14M11 18h9"
          stroke="#d1d5db"
          stroke-width="1.5"
          stroke-linecap="round"
        />
      </svg>
      <p>No employees found for the selected payroll period</p>
    </div>

    <template v-else>
      <!-- Pending changes bar -->
      <div v-if="modifiedEmployeeCount > 0" class="pending-bar">
        <div class="pending-left">
          <span class="pending-count"
            >{{ modifiedEmployeeCount }} employee(s) with pending changes</span
          >
          <!-- Bulk update -->
          <div class="bulk-row">
            <span class="bulk-label">Bulk update amount:</span>
            <div class="bulk-input-wrap">
              <span class="bulk-prefix">₱</span>
              <input
                type="number"
                min="0"
                step="1"
                v-model.number="bulkAmount"
                class="bulk-input"
                placeholder="0.00"
              />
            </div>
            <button
              class="btn-apply"
              :disabled="
                !bulkAmount || bulkAmount <= 0 || selectedEmployeeCount === 0
              "
              @click="applyBulkAmount"
            >
              Apply to selected ({{ selectedEmployeeCount }})
            </button>
            <button class="btn-ghost-sm" @click="resetBulkAmount">Reset</button>
          </div>
        </div>
        <div class="pending-actions">
          <button class="btn-ghost-sm" @click="clearAllModifications">
            Clear all changes
          </button>
          <button class="btn-save" :disabled="saving" @click="saveChanges">
            <svg
              v-if="saving"
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
            {{ saving ? "Saving…" : `Save changes (${modifiedEmployeeCount})` }}
          </button>
        </div>
      </div>

      <!-- Validation error banner -->
      <div v-if="validationError" class="error-banner">
        <svg
          width="14"
          height="14"
          viewBox="0 0 14 14"
          fill="none"
          style="flex-shrink: 0"
        >
          <path
            d="M7 2L1 12h12L7 2z"
            stroke="#a32d2d"
            stroke-width="1.3"
            stroke-linejoin="round"
          />
          <path
            d="M7 6v3M7 10.5h.01"
            stroke="#a32d2d"
            stroke-width="1.3"
            stroke-linecap="round"
          />
        </svg>
        {{ validationError }}
        <button class="toast-close" @click="validationError = ''">
          <svg width="11" height="11" viewBox="0 0 11 11" fill="none">
            <path
              d="M1 1l9 9M10 1L1 10"
              stroke="currentColor"
              stroke-width="1.3"
              stroke-linecap="round"
            />
          </svg>
        </button>
      </div>

      <!-- Pagination info -->
      <div class="table-meta">
        <span class="meta-count">
          Showing {{ paginationInfo.start }}–{{ paginationInfo.end }} of
          {{ filteredEmployees.length }} entries
          <span v-if="searchQuery" class="meta-filter"
            >(filtered by "{{ searchQuery }}")</span
          >
        </span>
      </div>

      <!-- Table -->
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th style="width: 36px">
                <input
                  type="checkbox"
                  class="row-check"
                  :checked="allPageSelected"
                  @change="toggleAllPage"
                />
              </th>
              <th style="width: 110px">Employee ID</th>
              <th>Employee name</th>
              <th>Department</th>
              <th class="num-col">Current amount</th>
              <th class="num-col">Net take-home pay</th>
              <th class="num-col" style="width: 200px">New amount</th>
              <th style="text-align: center; width: 90px">Status</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="row in paginatedEmployees"
              :key="row.employee_id"
              :class="{
                'row-modified': isModified(row),
                'row-selected': selectedEmployeeIds.has(row.employee_id),
              }"
            >
              <td>
                <input
                  type="checkbox"
                  class="row-check"
                  :checked="selectedEmployeeIds.has(row.employee_id)"
                  @change="toggleRow(row)"
                />
              </td>
              <td class="mono muted">{{ row.employee_id }}</td>
              <td class="emp-name">{{ row.name }}</td>
              <td class="muted">{{ getDepartmentLabel(row) }}</td>
              <td class="num-td">₱{{ fmt(row.amount) }}</td>
              <td class="num-td" :class="getNetPayClass(row)">
                <span v-if="hasSalaryData(row)"
                  >₱{{ fmt(calculateNetPay(row)) }}</span
                >
                <span v-else class="needs-salary">Salary data needed</span>
              </td>
              <td class="num-td">
                <div class="amount-input-wrap">
                  <span class="amount-prefix">₱</span>
                  <input
                    type="number"
                    min="0"
                    max="999999.99"
                    step="0.01"
                    class="amount-input"
                    :class="{ 'has-change': isModified(row) }"
                    :value="
                      getNumericValue(row.new_amount) ??
                      getNumericValue(row.amount) ??
                      200
                    "
                    @input="(e) => updateAmount(row, e.target.value)"
                  />
                </div>
              </td>
              <td style="text-align: center">
                <span
                  class="status-pill"
                  :class="row.amount > 0 ? 'active' : 'inactive'"
                >
                  {{ row.amount > 0 ? "Active" : "Inactive" }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="pagination">
        <span class="page-info">{{ filteredEmployees.length }} results</span>
        <div class="page-btns">
          <button
            class="page-btn"
            :disabled="currentPage === 1"
            @click="handlePageChange(currentPage - 1)"
          >
            &#8249;
          </button>
          <button
            v-for="p in totalPages"
            :key="p"
            class="page-btn"
            :class="{ active: p === currentPage }"
            @click="handlePageChange(p)"
          >
            {{ p }}
          </button>
          <button
            class="page-btn"
            :disabled="currentPage === totalPages"
            @click="handlePageChange(currentPage + 1)"
          >
            &#8250;
          </button>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, watch } from "vue";

const props = defineProps({
  employees: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  error: { type: String, default: null },
  payrollPeriodId: { type: [Number, String], default: null },
});
const emit = defineEmits(["save", "selection-change"]);

const saving = ref(false);
const selectedEmployeeIds = ref(new Set());
const searchQuery = ref("");
const bulkAmount = ref(null);
const currentPage = ref(1);
const pageSize = ref(10);
const validationError = ref("");

const hasEmployees = computed(
  () => Array.isArray(props.employees) && props.employees.length > 0,
);
const selectedEmployeeCount = computed(() => selectedEmployeeIds.value.size);

const getNumericValue = (v) => {
  if (v === null || v === undefined) return null;
  if (typeof v === "number") return isNaN(v) ? null : v;
  if (typeof v === "string") {
    const p = parseFloat(v);
    return isNaN(p) ? null : p;
  }
  return null;
};

const fmt = (v) =>
  Number(v || 0).toLocaleString("en-PH", { minimumFractionDigits: 2 });

const totalEmployees = computed(() =>
  Array.isArray(props.employees) ? props.employees.length : 0,
);
const ActiveAccounts = computed(() =>
  Array.isArray(props.employees)
    ? props.employees.filter((e) => e?.amount > 0).length
    : 0,
);

const getDepartmentLabel = (e) => {
  if (!e) return "—";
  const d = e.department;
  const name = typeof d === "string" ? d : d?.name || d?.department_name || "";
  return name || e.department_name || e.division_name || e.division || "—";
};

const modifiedEmployeeCount = computed(() => {
  if (!Array.isArray(props.employees)) return 0;
  return props.employees.filter((e) => {
    if (!e) return false;
    const cur = getNumericValue(e.amount) ?? 0;
    const nxt = getNumericValue(e.new_amount) ?? cur;
    return nxt !== cur;
  }).length;
});
const isModified = (row) => {
  const cur = getNumericValue(row.amount) ?? 0;
  const nxt = getNumericValue(row.new_amount) ?? cur;
  return nxt !== cur;
};

const filteredEmployees = computed(() => {
  const list = Array.isArray(props.employees) ? props.employees : [];
  if (!searchQuery.value) return list;
  const q = searchQuery.value.toLowerCase();
  return list.filter(
    (e) =>
      e?.name?.toLowerCase().includes(q) ||
      e?.employee_id?.toString().toLowerCase().includes(q) ||
      getDepartmentLabel(e).toLowerCase().includes(q),
  );
});
const totalPages = computed(() =>
  Math.max(1, Math.ceil(filteredEmployees.value.length / pageSize.value)),
);
const paginatedEmployees = computed(() => {
  const s = (currentPage.value - 1) * pageSize.value;
  return filteredEmployees.value.slice(s, s + pageSize.value);
});
const paginationInfo = computed(() => {
  const total = filteredEmployees.value.length;
  const start = total === 0 ? 0 : (currentPage.value - 1) * pageSize.value + 1;
  const end = Math.min(currentPage.value * pageSize.value, total);
  return { start, end, total };
});

// All-page checkbox
const allPageSelected = computed(
  () =>
    paginatedEmployees.value.length > 0 &&
    paginatedEmployees.value.every((r) =>
      selectedEmployeeIds.value.has(r.employee_id),
    ),
);
const toggleAllPage = () => {
  if (allPageSelected.value)
    paginatedEmployees.value.forEach((r) =>
      selectedEmployeeIds.value.delete(r.employee_id),
    );
  else
    paginatedEmployees.value.forEach((r) =>
      selectedEmployeeIds.value.add(r.employee_id),
    );
  emitSelection();
};
const toggleRow = (row) => {
  if (selectedEmployeeIds.value.has(row.employee_id))
    selectedEmployeeIds.value.delete(row.employee_id);
  else selectedEmployeeIds.value.add(row.employee_id);
  emitSelection();
};
const emitSelection = () => {
  const sel = (props.employees || []).filter((e) =>
    selectedEmployeeIds.value.has(e.employee_id),
  );
  emit("selection-change", sel);
};

watch(
  () => props.employees,
  (list) => {
    if (Array.isArray(list)) {
      list.forEach((e) => {
        if (e && e.new_amount === undefined) {
          const a = getNumericValue(e.amount);
          e.new_amount = a !== null ? a : 200;
        } else if (e) {
          e.new_amount = getNumericValue(e.new_amount) ?? 200;
        }
      });
      currentPage.value = 1;
    }
  },
  { immediate: true },
);
watch(searchQuery, () => {
  currentPage.value = 1;
  selectedEmployeeIds.value.clear();
  emitSelection();
});

const handlePageChange = (p) => {
  currentPage.value = p;
};
const handlePageSizeChange = () => {
  currentPage.value = 1;
  selectedEmployeeIds.value.clear();
  emitSelection();
};

const updateAmount = (row, value) => {
  row.new_amount = getNumericValue(value) ?? 0;
};
const applyBulkAmount = () => {
  if (!bulkAmount.value || bulkAmount.value <= 0) return;
  const sel = (props.employees || []).filter((e) =>
    selectedEmployeeIds.value.has(e.employee_id),
  );
  sel.forEach((e) => {
    e.new_amount = bulkAmount.value;
  });
};
const resetBulkAmount = () => {
  bulkAmount.value = null;
};

const getSalaryData = (e) => {
  if (!e) return null;
  for (const f of [
    e.salary,
    e.salary_amount,
    e.total_salary,
    e.total_gross,
    e.gross_pay,
  ]) {
    const v = getNumericValue(f);
    if (v !== null && v > 0) return v;
  }
  return null;
};
const hasSalaryData = (e) => getSalaryData(e) !== null;
const calculateNetPay = (e) => {
  const salary = getSalaryData(e);
  if (salary === null) return null;
  const tax = getNumericValue(e.tax_amount) ?? getNumericValue(e.tax) ?? 0;
  const gsis = getNumericValue(e.gsis_amount) ?? getNumericValue(e.gsis) ?? 0;
  const ph =
    getNumericValue(e.philhealth_amount) ?? getNumericValue(e.philhealth) ?? 0;
  const hdmf = getNumericValue(e.new_amount) ?? getNumericValue(e.amount) ?? 0;
  return Math.max(0, salary - (tax + gsis + ph + hdmf));
};
const getNetPayClass = (e) => {
  const n = calculateNetPay(e);
  if (n === null) return "net-unknown";
  if (n < 5000) return "net-low";
  if (n < 10000) return "net-mid";
  return "net-ok";
};

const clearAllModifications = () => {
  if (!Array.isArray(props.employees)) return;
  props.employees.forEach((e) => {
    if (e) e.new_amount = getNumericValue(e.amount) ?? 200;
  });
};

const getModifiedEmployees = () => {
  if (!Array.isArray(props.employees)) return [];
  return props.employees.filter((e) => {
    if (!e) return false;
    const cur = getNumericValue(e.amount) ?? 0;
    const nxt = getNumericValue(e.new_amount) ?? cur;
    return nxt !== cur;
  });
};

const validateAmounts = () => {
  const modified = getModifiedEmployees();
  const invalid = modified.filter((e) => {
    const a = getNumericValue(e.new_amount) ?? 0;
    return a < 0 || a > 999999.99;
  });
  if (invalid.length) {
    validationError.value = "Please enter valid amounts (0 – 999,999.99).";
    return false;
  }
  const belowMin = modified.filter((e) => {
    const a = getNumericValue(e.new_amount) ?? 0;
    return a > 0 && a < 200;
  });
  if (belowMin.length) {
    validationError.value =
      "HDMF amount must be at least ₱200.00 (government-mandated minimum).";
    return false;
  }
  const lowNet = modified.filter((e) => {
    const n = calculateNetPay(e);
    return n !== null && n < 5000;
  });
  if (lowNet.length) {
    validationError.value = `${lowNet.length} employee(s) would have net take-home pay below ₱5,000. Please reduce their HDMF amounts.`;
    return false;
  }
  return true;
};

const saveChanges = async () => {
  validationError.value = "";
  const modified = getModifiedEmployees();
  if (!modified.length) return;
  if (!validateAmounts()) return;
  try {
    saving.value = true;
    const payload = {
      payroll_period_id: props.payrollPeriodId,
      employee_id: modified.map((e) => e.employee_id),
      amount: modified.map((e) => getNumericValue(e.new_amount) ?? 0),
    };
    emit("save", payload);
  } catch (e) {
    console.error(e);
  } finally {
    saving.value = false;
  }
};

defineExpose({
  clearAllSelections: () => {
    selectedEmployeeIds.value.clear();
    emitSelection();
  },
  selectedRows: computed(() =>
    (props.employees || []).filter((e) =>
      selectedEmployeeIds.value.has(e.employee_id),
    ),
  ),
});
</script>

<style scoped>
.ht-shell {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.summary-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 180px));
  gap: 30px;
  justify-content: center;
}
.stat-card {
  background: #f8f9fa;
  border-radius: 10px;
  padding: 14px 16px;
  width: 200px;
  text-align: center;
}
.stat-label {
  font-size: 12px;
  color: #6b7280;
  margin-bottom: 5px;
}
.stat-value {
  font-size: 22px;
  font-weight: 500;
  color: #111827;
}
.stat-value.info {
  color: #185fa5;
}
.stat-value.green {
  color: #085041;
}

.controls-row {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}
.search-wrap {
  position: relative;
  flex: 1;
  min-width: 220px;
}
.search-icon {
  position: absolute;
  left: 9px;
  top: 50%;
  transform: translateY(-50%);
  color: #9ca3af;
  pointer-events: none;
}
.search-input {
  width: 100%;
  font-size: 13px;
  padding: 7px 10px 7px 30px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  outline: none;
  color: #111827;
}
.search-input:focus {
  border-color: #6b7280;
}
.page-size-wrap {
  display: flex;
  align-items: center;
  gap: 6px;
}
.ps-label {
  font-size: 13px;
  color: #6b7280;
  white-space: nowrap;
}
.ps-select {
  font-size: 13px;
  padding: 6px 8px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  background: #fff;
  color: #111827;
  outline: none;
}

.loading-state {
  text-align: center;
  padding: 40px;
  color: #9ca3af;
  font-size: 13px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
}
.spinner {
  width: 24px;
  height: 24px;
  border: 3px solid #e5e7eb;
  border-top-color: #185fa5;
  border-radius: 50%;
  animation: spin 0.9s linear infinite;
}
.empty-state {
  text-align: center;
  padding: 48px 0;
  color: #9ca3af;
  font-size: 14px;
}
.empty-state svg {
  margin: 0 auto 12px;
  display: block;
}
.error-banner {
  display: flex;
  align-items: center;
  gap: 10px;
  background: #faece7;
  border: 1px solid #f0997b;
  border-radius: 8px;
  padding: 10px 14px;
  font-size: 13px;
  color: #712b13;
}
.toast-close {
  background: transparent;
  border: none;
  padding: 2px;
  color: #712b13;
  cursor: pointer;
  display: flex;
  margin-left: auto;
  border-radius: 4px;
}
.toast-close:hover {
  background: #f0997b;
}

/* Pending bar */
.pending-bar {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  padding: 12px 16px;
  background: #e6f1fb;
  border: 1px solid #b5d4f4;
  border-radius: 10px;
  gap: 14px;
  flex-wrap: wrap;
}
.pending-left {
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.pending-count {
  font-size: 13px;
  font-weight: 500;
  color: #0c447c;
}
.bulk-row {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}
.bulk-label {
  font-size: 12px;
  color: #185fa5;
  white-space: nowrap;
}
.bulk-input-wrap {
  display: inline-flex;
  align-items: center;
  border: 1px solid #b5d4f4;
  border-radius: 7px;
  overflow: hidden;
  background: #fff;
}
.bulk-prefix {
  font-size: 12px;
  color: #9ca3af;
  padding: 0 5px 0 8px;
  background: #f3f4f6;
  border-right: 1px solid #e5e7eb;
  line-height: 30px;
}
.bulk-input {
  font-size: 13px;
  font-family: monospace;
  padding: 5px 8px;
  border: none;
  outline: none;
  width: 80px;
  color: #111827;
}
.btn-apply {
  background: #185fa5;
  color: #fff;
  border: none;
  border-radius: 7px;
  padding: 6px 12px;
  font-size: 12px;
  font-weight: 500;
  cursor: pointer;
  white-space: nowrap;
}
.btn-apply:hover:not(:disabled) {
  opacity: 0.88;
}
.btn-apply:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}
.btn-ghost-sm {
  background: transparent;
  border: 1px solid #b5d4f4;
  border-radius: 7px;
  padding: 5px 12px;
  font-size: 12px;
  color: #185fa5;
  cursor: pointer;
}
.btn-ghost-sm:hover {
  background: #fff;
}
.pending-actions {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
  align-self: flex-start;
}
.btn-save {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: #111827;
  color: #fff;
  border: none;
  border-radius: 8px;
  padding: 8px 16px;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  white-space: nowrap;
}
.btn-save:hover:not(:disabled) {
  opacity: 0.88;
}
.btn-save:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Table meta */
.table-meta {
  font-size: 12px;
  color: #9ca3af;
}
.meta-filter {
  color: #185fa5;
}

/* Table */
.table-wrap {
  border: 1px solid #e5e7eb;
  border-radius: 12px;
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
  transition: background 0.1s;
}
tbody tr:last-child {
  border-bottom: none;
}
tbody tr:hover {
  background: #fafafa;
}
tbody tr.row-modified {
  background: #f0fdf4;
}
tbody tr.row-selected {
  background: #eff6ff;
}
tbody tr.row-modified.row-selected {
  background: #e0f7e9;
}
td {
  padding: 10px 14px;
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
.needs-salary {
  font-size: 11px;
  color: #9ca3af;
  font-style: italic;
  font-family: inherit;
}

/* Net pay colors */
.net-ok {
  color: #085041;
  font-weight: 500;
}
.net-mid {
  color: #633806;
  font-weight: 500;
}
.net-low {
  color: #a32d2d;
  font-weight: 500;
}
.net-unknown {
  color: #9ca3af;
  font-style: italic;
}

/* Amount input */
.amount-input-wrap {
  display: inline-flex;
  align-items: center;
  border: 1px solid #e5e7eb;
  border-radius: 7px;
  overflow: hidden;
  float: right;
}
.amount-prefix {
  font-size: 12px;
  color: #9ca3af;
  padding: 0 5px 0 8px;
  background: #f9fafb;
  border-right: 1px solid #e5e7eb;
  line-height: 30px;
}
.amount-input {
  font-size: 12px;
  font-family: monospace;
  text-align: right;
  padding: 5px 8px;
  border: none;
  outline: none;
  width: 90px;
  color: #111827;
  background: #fff;
}
.amount-input:focus {
  background: #eff6ff;
}
.amount-input.has-change {
  background: #f0fdf4;
  color: #085041;
  font-weight: 500;
}
.amount-input-wrap:focus-within {
  border-color: #6b7280;
}

/* Status */
.status-pill {
  display: inline-block;
  padding: 3px 9px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 500;
}
.status-pill.active {
  background: #e1f5ee;
  color: #085041;
}
.status-pill.inactive {
  background: #f3f4f6;
  color: #9ca3af;
}

/* Row checkbox */
.row-check {
  width: 14px;
  height: 14px;
  cursor: pointer;
  accent-color: #111827;
}

/* Pagination */
.pagination {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 4px 0 0;
}
.page-info {
  font-size: 12px;
  color: #9ca3af;
}
.page-btns {
  display: flex;
  gap: 4px;
}
.page-btn {
  width: 28px;
  height: 28px;
  border-radius: 6px;
  border: 1px solid #e5e7eb;
  background: transparent;
  font-size: 12px;
  color: #6b7280;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}
.page-btn.active {
  background: #111827;
  color: #fff;
  border-color: #111827;
}
.page-btn:hover:not(.active):not(:disabled) {
  background: #f3f4f6;
}
.page-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
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
