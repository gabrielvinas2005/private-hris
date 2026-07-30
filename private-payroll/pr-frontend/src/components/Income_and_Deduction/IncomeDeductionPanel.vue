<template>
  <div class="panel">
    <!-- Context bar -->
    <div class="context-bar">
      <div class="context-group">
        <label class="ctx-label">Payroll period</label>
        <el-select
          :model-value="selectedPayrollPeriodKey"
          @change="handlePayrollPeriodChange"
          class="wide"
          style="width: 280px"
          placeholder="Select Payroll Period"
          :teleported="false"
        >
          <template #suffix>
            <span
              v-if="selectedPayrollPeriodOption?.kind === 'pair'"
              class="cutoff-tags-inline cutoff-tags-suffix"
            >
              <PayrollCutoffTag
                v-for="name in selectedPayrollPeriodOption.cutoff_names || []"
                :key="name"
                :name="name"
              />
            </span>
          </template>

          <el-option
            v-for="p in payrollPeriods"
            :key="p.key"
            :value="p.key"
            :label="getPeriodLabel(p)"
          >
            <div class="period-option-row">
              <span class="period-option-label">{{ getPeriodLabel(p) }}</span>
              <span v-if="p.kind === 'pair'" class="cutoff-tags-inline">
                <PayrollCutoffTag
                  v-for="name in p.cutoff_names || []"
                  :key="name"
                  :name="name"
                />
              </span>
            </div>
          </el-option>
        </el-select>
      </div>

      <div class="context-group">
        <label class="ctx-label">Employment type</label>
        <select
          :value="selectedEmploymentTypeId ?? ''"
          class="ctx-select"
          @change="handleEmploymentTypeChange"
        >
          <option disabled value="">Select type</option>
          <option v-for="e in employmentTypes" :key="e.id" :value="e.id">
            {{ e.name }}
          </option>
        </select>
      </div>

      <div class="context-group">
        <label class="ctx-label">{{
          activeTab === "income" ? "Income item" : "Deduction item"
        }}</label>
        <select
          v-if="activeTab === 'income'"
          :value="selectedIncomeId ?? ''"
          class="ctx-select wide"
          @change="handleIncomeItemChange"
        >
          <option disabled value="">Select income</option>
          <option v-for="i in incomeList" :key="i.id" :value="i.id">
            {{ i.name }}
          </option>
        </select>
        <select
          v-else
          :value="selectedDeductionId ?? ''"
          class="ctx-select wide"
          @change="handleDeductionItemChange"
        >
          <option disabled value="">Select deduction</option>
          <option v-for="d in deductionList" :key="d.id" :value="d.id">
            {{ d.name }}
          </option>
        </select>
      </div>
    </div>

    <!-- Tabs + actions row -->
    <div class="tabs-action-row">
      <div class="tab-group" role="tablist">
        <button
          role="tab"
          class="tab-btn"
          :class="{ active: activeTab === 'income' }"
          @click="switchTab('income')"
        >
          Income
        </button>
        <button
          role="tab"
          class="tab-btn"
          :class="{ active: activeTab === 'deduction' }"
          @click="switchTab('deduction')"
        >
          Deductions
        </button>
      </div>

      <div class="action-right">
        <!-- Set amount widget -->
        <div class="set-amount-wrap">
          <label class="set-amount-label">Set all amounts to</label>
          <div class="set-input-group">
            <span class="set-prefix">₱</span>
            <input
              v-model.number="setAmount"
              type="number"
              min="0"
              step="0.01"
              class="set-input"
              placeholder="0.00"
              @keydown.enter.prevent="applySetAmount"
            />
            <button
              class="set-apply-btn"
              @click="applySetAmount"
              title="Apply to all rows"
            >
              Apply
            </button>
          </div>
        </div>

        <!-- Search -->
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
            v-model="search"
            class="search-input"
            placeholder="Search employee…"
          />
        </div>

        <label
          v-if="
            (activeTab === 'income' &&
              isPeraIncome &&
              selectedPayrollPeriodOption?.kind === 'pair') ||
            (activeTab === 'deduction' && isEaDeduction)
          "
          class="retain-wrap"
        >
          <input v-model="retainNextMonth" type="checkbox" />
          <span>RETAIN to next month</span>
        </label>

        <!-- Save -->
        <button class="btn-save" :disabled="loading" @click="handleSave">
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
          <svg v-else width="14" height="14" viewBox="0 0 14 14" fill="none">
            <path
              d="M2 7l3.5 3.5L12 3"
              stroke="currentColor"
              stroke-width="1.5"
              stroke-linecap="round"
              stroke-linejoin="round"
            />
          </svg>
          {{
            loading
              ? "Saving…"
              : activeTab === "income"
                ? "Save incomes"
                : "Save deductions"
          }}
        </button>
      </div>
    </div>

    <!-- Table -->
    <div class="table-wrap">
      <!-- Loading skeleton -->
      <div v-if="loading && filteredRows.length === 0" class="skeleton-wrap">
        <div v-for="i in 8" :key="i" class="skeleton-row" />
      </div>

      <template v-else>
        <table v-if="filteredRows.length > 0">
          <thead>
            <tr>
              <th style="width: 44px">#</th>
              <th>Employee name</th>
              <th>Department</th>
              <th style="width: 180px; text-align: right">Amount (₱)</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(row, idx) in filteredRows"
              :key="row.id ?? idx"
              :class="{ 'row-modified': row.amount > 0 }"
            >
              <td class="row-num">{{ idx + 1 }}</td>
              <td class="emp-name-cell">
                <div class="emp-cell">
                  <div
                    class="avatar"
                    :style="{ background: avatarBg(row), color: avatarFg(row) }"
                  >
                    {{ getInitials(row) }}
                  </div>
                  <span
                    >{{ row.last_name }}, {{ row.first_name }}
                    {{ row.suffix || "" }}</span
                  >
                </div>
              </td>
              <td class="department-cell">{{ row.department || "—" }}</td>
              <td class="amount-cell">
                <div class="amount-input-wrap">
                  <span class="amount-prefix">₱</span>
                  <input
                    v-model.number="row.amount"
                    type="number"
                    min="0"
                    step="0.01"
                    class="amount-input"
                    :class="{ 'has-value': row.amount > 0 }"
                    placeholder="0.00"
                  />
                </div>
              </td>
            </tr>
          </tbody>
        </table>

        <div v-else class="empty-state">
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
          <p>No employees found. Adjust your filters above.</p>
        </div>
      </template>
    </div>

    <!-- Footer summary -->
    <div class="footer-summary" v-if="filteredRows.length > 0">
      <span class="summary-count">{{ filteredRows.length }} employees</span>
      <span class="summary-divider">·</span>
      <span class="summary-total">
        Total: <strong>₱{{ totalAmount }}</strong>
      </span>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from "vue";
import { ElMessageBox } from "element-plus";
import { useIncomeDeduction } from "@/Composables/useIncomeDeduction";
import PayrollCutoffTag from "@/components/Shared/PayrollCutoffTag.vue";

const {
  loading,
  payrollPeriods,
  employmentTypes,
  selectedPayrollPeriodKey,
  selectedPayrollPeriodOption,
  selectedEmploymentTypeId,
  activeTab,
  incomeList,
  deductionList,
  selectedIncomeId,
  selectedDeductionId,
  search,
  setAmount,
  retainNextMonth,
  isPeraIncome,
  isEaDeduction,
  rows,
  filteredRows,
  bootstrap,
  loadItems,
  loadRows,
  applySetAmount,
  save,
} = useIncomeDeduction();

const baselineSignature = ref("");

const getPeriodLabel = (p) => {
  // Keep the dropdown label clean; show coverage via cutoff tags below.
  return p.cutoff_name ? `${p.name} - ${p.cutoff_name}` : p.name;
};

const normalizeRows = (list) =>
  (list || []).map((row) => ({
    employee_id: Number(row.employee_id),
    amount: Number(row.amount || 0),
  }));

const buildCurrentSignature = () =>
  JSON.stringify({
    tab: activeTab.value,
    retain: Boolean(retainNextMonth.value),
    rows: normalizeRows(rows.value),
  });

const markBaseline = () => {
  baselineSignature.value = buildCurrentSignature();
};

const hasUnsavedChanges = computed(
  () =>
    baselineSignature.value &&
    baselineSignature.value !== buildCurrentSignature(),
);

const loadRowsAndMarkBaseline = async () => {
  await loadRows();
  markBaseline();
};

const loadItemsRowsAndMarkBaseline = async () => {
  await loadItems();
  await loadRowsAndMarkBaseline();
};

const toNullableNumber = (value) => {
  if (value === null || value === undefined || value === "") return null;
  const n = Number(value);
  return Number.isNaN(n) ? null : n;
};

const confirmBeforeContextSwitch = async () => {
  if (!hasUnsavedChanges.value) return true;

  try {
    await ElMessageBox.confirm(
      "You have unsaved changes. Do you want to save first before switching?",
      "Unsaved changes",
      {
        confirmButtonText: "Save first",
        cancelButtonText: "Continue without saving",
        distinguishCancelAndClose: true,
        type: "warning",
      },
    );
    await save();
    markBaseline();
    return true;
  } catch (error) {
    if (error === "cancel") return true;
    return false;
  }
};

const switchTab = async (tab) => {
  activeTab.value = tab;
  await loadItemsRowsAndMarkBaseline();
};

const handlePayrollPeriodChange = async (newPeriodKey) => {
  if (newPeriodKey === selectedPayrollPeriodKey.value) return;
  const shouldContinue = await confirmBeforeContextSwitch();
  if (!shouldContinue) return;
  selectedPayrollPeriodKey.value = newPeriodKey;
};

const handleEmploymentTypeChange = async (event) => {
  const nextValue = toNullableNumber(event.target.value);
  if (nextValue === selectedEmploymentTypeId.value) return;
  const shouldContinue = await confirmBeforeContextSwitch();
  if (!shouldContinue) {
    event.target.value = selectedEmploymentTypeId.value ?? "";
    return;
  }
  selectedEmploymentTypeId.value = nextValue;
};

const handleIncomeItemChange = async (event) => {
  const nextValue = toNullableNumber(event.target.value);
  if (nextValue === selectedIncomeId.value) return;
  const shouldContinue = await confirmBeforeContextSwitch();
  if (!shouldContinue) {
    event.target.value = selectedIncomeId.value ?? "";
    return;
  }
  selectedIncomeId.value = nextValue;
};

const handleDeductionItemChange = async (event) => {
  const nextValue = toNullableNumber(event.target.value);
  if (nextValue === selectedDeductionId.value) return;
  const shouldContinue = await confirmBeforeContextSwitch();
  if (!shouldContinue) {
    event.target.value = selectedDeductionId.value ?? "";
    return;
  }
  selectedDeductionId.value = nextValue;
};

const handleSave = async () => {
  await save();
  markBaseline();
};

onMounted(async () => {
  await bootstrap();
  await loadItemsRowsAndMarkBaseline();
});

watch([selectedPayrollPeriodKey, selectedEmploymentTypeId], async () => {
  await loadItemsRowsAndMarkBaseline();
});

watch([selectedIncomeId, selectedDeductionId], async () => {
  await loadRowsAndMarkBaseline();
});

// --- Avatar helpers ---
const AVATAR_PAIRS = [
  { bg: "#E6F1FB", fg: "#0C447C" },
  { bg: "#E1F5EE", fg: "#085041" },
  { bg: "#EEEDFE", fg: "#3C3489" },
  { bg: "#FAECE7", fg: "#712B13" },
  { bg: "#FAEEDA", fg: "#633806" },
];
const nameIdx = (row) =>
  ((row.last_name || "").charCodeAt(0) || 0) % AVATAR_PAIRS.length;
const avatarBg = (row) => AVATAR_PAIRS[nameIdx(row)].bg;
const avatarFg = (row) => AVATAR_PAIRS[nameIdx(row)].fg;
const getInitials = (row) => {
  const l = (row.last_name || "").slice(0, 1).toUpperCase();
  const f = (row.first_name || "").slice(0, 1).toUpperCase();
  return l + f || "??";
};

// --- Footer total ---
const totalAmount = computed(() => {
  const sum = (filteredRows.value || []).reduce(
    (s, r) => s + Number(r.amount || 0),
    0,
  );
  return sum.toLocaleString(undefined, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
});
</script>

<style scoped>
.panel {
  display: flex;
  flex-direction: column;
  gap: 0;
}

/* context bar */
.context-bar {
  display: flex;
  gap: 20px;
  align-items: flex-end;
  padding: 0 0 20px;
  flex-wrap: wrap;
  border-bottom: 1px solid #f3f4f6;
  margin-bottom: 16px;
}
.context-group {
  display: flex;
  flex-direction: column;
  gap: 2px;
}
:deep(.el-select-dropdown__item) {
  padding: 10px 14px;
  height: auto;
  line-height: 1.4;
}

.cutoff-tags-inline {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  flex-shrink: 0;
}
.cutoff-tags-suffix {
  margin-left: 4px;
  margin-right: 2px;
}
.period-option-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  width: 100%;
}
.period-option-label {
  flex: 1;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.ctx-label {
  font-size: 12px;
  font-weight: 500;
  color: #6b7280;
}
.ctx-select {
  font-size: 13px;
  padding: 7px 10px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  background: #fff;
  color: #111827;
  outline: none;
  min-width: 140px;
  cursor: pointer;
}
.ctx-select.wide {
  min-width: 380px;
}
.ctx-select:focus {
  border-color: #6b7280;
}

/* Element Plus select: avoid double border (wrapper provides border) */
.ctx-el-select {
  min-width: 140px;
}
.ctx-el-select.wide {
  min-width: 380px;
}
.ctx-el-select :deep(.el-input__wrapper) {
  border-radius: 8px;
  box-shadow: 0 0 0 1px #e5e7eb inset;
  padding: 0 10px;
  background: #fff;
}
.ctx-el-select :deep(.el-input__inner) {
  font-size: 13px;
  color: #111827;
}
.ctx-el-select :deep(.el-input__wrapper.is-focus) {
  box-shadow: 0 0 0 1px #6b7280 inset;
}
.ctx-el-select :deep(.el-input__suffix) {
  gap: 4px; /* tighter spacing between tags and arrow */
}

/* tabs + action row */
.tabs-action-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 14px;
  gap: 12px;
  flex-wrap: wrap;
}
.tab-group {
  display: flex;
  gap: 0;
  border-bottom: 2px solid #f3f4f6;
}
.tab-btn {
  background: transparent;
  border: none;
  border-bottom: 2px solid transparent;
  margin-bottom: -2px;
  padding: 8px 18px;
  font-size: 13px;
  font-weight: 500;
  color: #9ca3af;
  cursor: pointer;
  transition:
    color 0.15s,
    border-color 0.15s;
}
.tab-btn.active {
  color: #111827;
  border-bottom-color: #111827;
}
.tab-btn:hover:not(.active) {
  color: #374151;
}

.action-right {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}
.retain-wrap {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  color: #374151;
  background: #f9fafb;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 7px 10px;
  white-space: nowrap;
}

/* set amount */
.set-amount-wrap {
  display: flex;
  align-items: center;
  gap: 8px;
}
.set-amount-label {
  font-size: 12px;
  color: #6b7280;
  white-space: nowrap;
}
.set-input-group {
  display: flex;
  align-items: center;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  overflow: hidden;
}
.set-prefix {
  font-size: 13px;
  color: #9ca3af;
  padding: 0 6px 0 10px;
  background: #f9fafb;
  border-right: 1px solid #e5e7eb;
  line-height: 34px;
}
.set-input {
  font-size: 13px;
  padding: 7px 8px;
  border: none;
  outline: none;
  width: 80px;
  color: #111827;
  background: #fff;
}
.set-apply-btn {
  padding: 0 12px;
  font-size: 12px;
  font-weight: 500;
  background: #f3f4f6;
  border: none;
  border-left: 1px solid #e5e7eb;
  color: #374151;
  cursor: pointer;
  height: 34px;
  transition: background 0.12s;
}
.set-apply-btn:hover {
  background: #e5e7eb;
}

/* search */
.search-wrap {
  position: relative;
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
  font-size: 13px;
  padding: 7px 10px 7px 30px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  background: #fff;
  color: #111827;
  outline: none;
  width: 180px;
}
.search-input:focus {
  border-color: #6b7280;
}

/* save button */
.btn-save {
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
  white-space: nowrap;
}
.btn-save:hover:not(:disabled) {
  opacity: 0.88;
}
.btn-save:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* table */
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
  padding: 10px 14px;
  text-align: left;
  font-weight: 500;
  font-size: 12px;
  color: #6b7280;
  border-bottom: 1px solid #e5e7eb;
  white-space: nowrap;
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
tbody tr.row-modified:hover {
  background: #dcfce7;
}
td {
  padding: 9px 14px;
  color: #111827;
  vertical-align: middle;
}

.row-num {
  font-size: 12px;
  color: #d1d5db;
  text-align: center;
}
.emp-name-cell {
  min-width: 200px;
}
.department-cell {
  color: #6b7280;
  font-size: 13px;
}

.emp-cell {
  display: flex;
  align-items: center;
  gap: 9px;
}
.avatar {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 10px;
  font-weight: 500;
  flex-shrink: 0;
}

/* amount input */
.amount-cell {
  text-align: right;
}
.amount-input-wrap {
  display: inline-flex;
  align-items: center;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  overflow: hidden;
}
.amount-prefix {
  font-size: 12px;
  color: #9ca3af;
  padding: 0 6px 0 8px;
  background: #f9fafb;
  border-right: 1px solid #e5e7eb;
  line-height: 30px;
}
.amount-input {
  font-size: 13px;
  padding: 5px 8px;
  border: none;
  outline: none;
  width: 100px;
  text-align: right;
  color: #111827;
  background: #fff;
  font-family: monospace;
}
.amount-input:focus {
  background: #eff6ff;
}
.amount-input.has-value {
  color: #185fa5;
  font-weight: 500;
  background: #eff6ff;
}
.amount-input-wrap:focus-within {
  border-color: #6b7280;
}

/* footer */
.footer-summary {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 2px 0;
  font-size: 13px;
  color: #6b7280;
}
.summary-divider {
  color: #d1d5db;
}
.summary-total strong {
  color: #111827;
}

/* skeleton */
.skeleton-wrap {
  display: flex;
  flex-direction: column;
  gap: 1px;
}
.skeleton-row {
  height: 44px;
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

/* empty */
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

/* spinner */
@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
.spin-icon {
  animation: spin 0.8s linear infinite;
}
</style>
