<template>
  <Teleport to="body">
    <Transition name="fade">
      <div v-if="visible" class="modal-backdrop" @click.self="handleClose">
        <div class="modal-card">
          <div class="modal-header">
            <div>
              <h3 class="modal-title">EWT adjustment</h3>
              <p class="modal-sub">{{ periodLabel }}</p>
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
                <span class="strip-label">Total employees</span>
                <strong>{{ employeesWithAdjustments.length }}</strong>
              </div>
              <div class="strip-item">
                <span class="strip-label">Adjustments made</span>
                <strong>{{ adjustmentCount }}</strong>
              </div>
              <div class="strip-item">
                <span class="strip-label">Total adjustment</span>
                <strong :class="totalAdjustment >= 0 ? 'pos' : 'neg'">
                  {{ totalAdjustment >= 0 ? "+" : "" }}₱{{ formatCurrency(totalAdjustment) }}
                </strong>
              </div>
            </div>

            <!-- Loading state -->
            <div v-if="loading" class="loading-row">
              <div class="spinner" />
              Loading employees…
            </div>

            <template v-else>
              <!-- Instruction -->
              <div class="info-banner">
                <svg width="13" height="13" viewBox="0 0 13 13" fill="none" style="flex-shrink:0">
                  <circle cx="6.5" cy="6.5" r="5" stroke="#185fa5" stroke-width="1.2" />
                  <path d="M6.5 6v3M6.5 4.5h.01" stroke="#185fa5" stroke-width="1.2" stroke-linecap="round" />
                </svg>
                <span>
                  Enter the <strong>2% EWT</strong> amount per employee. Default is 2% of balance after premium is added.
                </span>
              </div>

              <!-- Filters -->
              <div class="filter-bar">
                <div class="search-wrap">
                  <svg width="13" height="13" viewBox="0 0 13 13" fill="none" class="search-icon">
                    <circle cx="5.5" cy="5.5" r="3.5" stroke="currentColor" stroke-width="1.2" />
                    <path d="M8.5 8.5l2.5 2.5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" />
                  </svg>
                  <input v-model="searchQuery" class="search-input" placeholder="Search employees…" />
                </div>
                <select v-model="filterType" class="filter-select">
                  <option value="">All</option>
                  <option value="adjusted">With adjustments</option>
                  <option value="not-adjusted">No adjustments</option>
                </select>
                <button class="btn-ghost" @click="handleClearAll">Clear all</button>
              </div>

              <!-- Table -->
              <div class="table-wrap">
                <table>
                  <thead>
                    <tr>
                      <th>Employee</th>
                      <th class="num-col">Current tax</th>
                      <th class="num-col" style="width:200px">Adjustment</th>
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
                      <td class="num-td muted">₱{{ formatCurrency(emp.tax) }}</td>
                      <td class="num-td">
                        <div class="adj-row">
                          <button class="adj-btn" @click="decreaseAdjustment(emp)">−</button>
                          <input
                            type="number"
                            v-model.number="emp.adjustment"
                            :min="-(emp.tax || 0)"
                            :max="999999"
                            :step="100"
                            class="adj-input"
                          />
                          <button class="adj-btn" @click="increaseAdjustment(emp)">+</button>
                        </div>
                      </td>
                      <td
                        class="num-td"
                        :class="emp.adjustment > 0 ? 'pos' : emp.adjustment < 0 ? 'neg' : ''"
                      >
                        ₱{{ formatCurrency(Math.max(0, emp.tax + (emp.adjustment || 0))) }}
                      </td>
                      <td
                        class="num-td"
                        :class="emp.adjustment > 0 ? 'pos' : emp.adjustment < 0 ? 'neg' : 'muted'"
                      >
                        {{ emp.adjustment > 0 ? "+" : "" }}₱{{ formatCurrency(emp.adjustment || 0) }}
                      </td>
                    </tr>
                    <tr v-if="filteredEmployees.length === 0">
                      <td colspan="5" class="empty-td">No employees found.</td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <!-- Adjustment summary -->
              <div v-if="adjustmentCount > 0" class="adj-summary">
                <div class="adj-sum-item">
                  <span>Employees adjusted</span>
                  <strong>{{ adjustmentCount }}</strong>
                </div>
                <div class="adj-sum-item">
                  <span>Total increase</span>
                  <strong class="pos">+₱{{ formatCurrency(positiveAdjustments) }}</strong>
                </div>
                <div class="adj-sum-item">
                  <span>Total decrease</span>
                  <strong class="neg">−₱{{ formatCurrency(negativeAdjustments) }}</strong>
                </div>
                <div class="adj-sum-item">
                  <span>Net adjustment</span>
                  <strong :class="totalAdjustment >= 0 ? 'pos' : 'neg'">
                    {{ totalAdjustment >= 0 ? "+" : "−" }}₱{{ formatCurrency(Math.abs(totalAdjustment)) }}
                  </strong>
                </div>
              </div>
            </template>
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
import { cosPayrollApi } from "../../services/api.js";
import { ElMessage } from "element-plus";

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  payrollPeriodId: { type: [Number, String], default: null },
  periodLabel: { type: String, default: "" },
});
const emit = defineEmits(["update:modelValue", "adjustments-saved"]);

const visible = computed({
  get: () => props.modelValue,
  set: (v) => emit("update:modelValue", v),
});

const loading = ref(false);
const saving = ref(false);
const searchQuery = ref("");
const filterType = ref("");
const employeesWithAdjustments = ref([]);

const formatCurrency = (v) => {
  const n = Number(v || 0);
  return n.toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const loadTaxEmployees = async () => {
  if (!props.payrollPeriodId) return;
  try {
    loading.value = true;
    const res = await cosPayrollApi.getTaxEmployees(props.payrollPeriodId);
    employeesWithAdjustments.value = (res.data?.data || []).map((e) => ({
      ...e,
      adjustment: 0,
    }));
  } catch (e) {
    ElMessage.error("Failed to load employees for tax adjustment.");
    console.error(e);
  } finally {
    loading.value = false;
  }
};

const filteredEmployees = computed(() => {
  let f = employeesWithAdjustments.value;
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase();
    f = f.filter((e) => e.name?.toLowerCase().includes(q));
  }
  if (filterType.value === "adjusted") f = f.filter((e) => e.adjustment !== 0);
  if (filterType.value === "not-adjusted") f = f.filter((e) => !e.adjustment || e.adjustment === 0);
  return f;
});

const adjustmentCount = computed(
  () => employeesWithAdjustments.value.filter((e) => Number(e.adjustment) !== 0).length,
);
const totalAdjustment = computed(() =>
  employeesWithAdjustments.value.reduce((s, e) => s + (e.adjustment || 0), 0),
);
const positiveAdjustments = computed(() =>
  employeesWithAdjustments.value.reduce((s, e) => s + (e.adjustment > 0 ? e.adjustment : 0), 0),
);
const negativeAdjustments = computed(() =>
  employeesWithAdjustments.value.reduce((s, e) => s + (e.adjustment < 0 ? Math.abs(e.adjustment) : 0), 0),
);

const decreaseAdjustment = (e) => {
  e.adjustment = Math.max(-(e.tax || 0), (e.adjustment || 0) - 100);
};
const increaseAdjustment = (e) => {
  e.adjustment = Math.min(999999, (e.adjustment || 0) + 100);
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
    const adjusted = employeesWithAdjustments.value.filter((e) => Number(e.adjustment) !== 0);
    await cosPayrollApi.adjustTax(props.payrollPeriodId, {
      employee_id: adjusted.map((e) => e.employee_id),
      tax_amount: adjusted.map((e) => Math.max(0, Number(e.tax) + Number(e.adjustment))),
    });
    ElMessage.success(`Tax adjustments saved for ${adjusted.length} employee(s).`);
    emit("adjustments-saved");
    handleClose();
  } catch (e) {
    ElMessage.error("Failed to save tax adjustments.");
    console.error(e);
  } finally {
    saving.value = false;
  }
};

watch(
  () => props.modelValue,
  (v) => { if (v) loadTaxEmployees(); },
);
watch(visible, (v) => {
  if (typeof document !== "undefined") document.body.style.overflow = v ? "hidden" : "";
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
.fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from,
.fade-leave-to { opacity: 0; }

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
.modal-title { font-size: 16px; font-weight: 500; color: #111827; margin: 0 0 3px; }
.modal-sub { font-size: 13px; color: #9ca3af; margin: 0; }
.close-btn {
  background: transparent; border: none; border-radius: 7px;
  padding: 5px; color: #9ca3af; cursor: pointer; display: flex;
}
.close-btn:hover { background: #f3f4f6; color: #374151; }

.modal-body {
  padding: 18px 24px; overflow-y: auto; flex: 1;
  display: flex; flex-direction: column; gap: 14px;
}
.modal-footer {
  display: flex; justify-content: flex-end; gap: 10px;
  padding: 14px 24px; border-top: 1px solid #f3f4f6; flex-shrink: 0;
}

.loading-row {
  display: flex; align-items: center; gap: 10px;
  font-size: 13px; color: #6b7280; padding: 24px 0;
}
.spinner {
  width: 18px; height: 18px; border: 2px solid #e5e7eb;
  border-top-color: #374151; border-radius: 50%;
  animation: spin 0.8s linear infinite; flex-shrink: 0;
}
@keyframes spin { to { transform: rotate(360deg); } }

.summary-strip {
  display: flex; gap: 20px; padding: 12px 16px;
  background: #f9fafb; border-radius: 10px;
}
.strip-item { display: flex; flex-direction: column; gap: 3px; }
.strip-label { font-size: 11px; color: #9ca3af; }
.strip-item strong { font-size: 16px; font-weight: 500; color: #111827; }
.strip-item strong.pos { color: #085041; }
.strip-item strong.neg { color: #a32d2d; }

.info-banner {
  display: flex; gap: 10px; align-items: flex-start;
  background: #e6f1fb; border: 1px solid #b5d4f4;
  border-radius: 8px; padding: 10px 14px;
  font-size: 13px; color: #0c447c;
}

.filter-bar { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
.search-wrap { position: relative; flex: 1; min-width: 180px; }
.search-icon {
  position: absolute; left: 8px; top: 50%;
  transform: translateY(-50%); color: #9ca3af; pointer-events: none;
}
.search-input {
  width: 100%; font-size: 13px; padding: 7px 10px 7px 28px;
  border: 1px solid #e5e7eb; border-radius: 8px; outline: none; color: #111827;
}
.filter-select {
  font-size: 13px; padding: 7px 10px; border: 1px solid #e5e7eb;
  border-radius: 8px; background: #fff; color: #111827; outline: none; min-width: 150px;
}
.btn-ghost {
  background: transparent; border: 1px solid #e5e7eb; border-radius: 8px;
  padding: 7px 14px; font-size: 13px; color: #6b7280; cursor: pointer;
}
.btn-ghost:hover { background: #f3f4f6; }
.btn-primary {
  background: #409eff; color: #fff; border: none;
  border-radius: 8px; padding: 9px 20px; font-size: 13px; font-weight: 500; cursor: pointer;
}
.btn-primary:hover:not(:disabled) { opacity: 0.88; }
.btn-primary:disabled { opacity: 0.5; background: #e5e7eb; color: #6b7280; cursor: not-allowed; }

.table-wrap { border: 1px solid #e5e7eb; border-radius: 10px; overflow: hidden; }
table { width: 100%; border-collapse: collapse; font-size: 13px; }
thead { background: #f9fafb; }
thead th {
  padding: 9px 14px; text-align: left; font-weight: 500;
  font-size: 12px; color: #6b7280; border-bottom: 1px solid #e5e7eb;
}
thead .num-col { text-align: right; }
tbody tr { border-bottom: 1px solid #f3f4f6; transition: background 0.1s; }
tbody tr:last-child { border-bottom: none; }
tbody tr:hover { background: #fafafa; }
tbody tr.row-adjusted { background: #f0fdf4; }
td { padding: 10px 14px; color: #111827; vertical-align: middle; }
.emp-name-cell { font-weight: 500; }
.num-td { text-align: right; font-family: monospace; }
.muted { color: #9ca3af; }
.pos { color: #085041; font-weight: 500; }
.neg { color: #a32d2d; font-weight: 500; }
.empty-td { text-align: center; color: #9ca3af; padding: 24px; }

.adj-row { display: flex; align-items: center; gap: 4px; justify-content: flex-end; }
.adj-btn {
  width: 26px; height: 26px; border: 1px solid #e5e7eb; border-radius: 6px;
  background: #f9fafb; font-size: 14px; color: #374151; cursor: pointer;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.adj-btn:hover { background: #e5e7eb; }
.adj-input {
  width: 80px; font-size: 12px; font-family: monospace;
  text-align: right; padding: 5px 6px;
  border: 1px solid #e5e7eb; border-radius: 6px; outline: none; color: #111827;
}
.adj-input:focus { border-color: #6b7280; }

.adj-summary {
  display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px;
  padding: 14px 16px; background: #f9fafb; border-radius: 10px;
}
.adj-sum-item {
  display: flex; flex-direction: column; align-items: center;
  text-align: center; gap: 4px; font-size: 12px; color: #6b7280;
}
.adj-sum-item strong { font-size: 15px; color: #111827; }
.adj-sum-item strong.pos { color: #085041; }
.adj-sum-item strong.neg { color: #a32d2d; }
</style>
