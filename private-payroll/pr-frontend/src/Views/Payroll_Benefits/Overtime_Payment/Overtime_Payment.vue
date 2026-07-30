<template>
  <PageScaffold
    title="Overtime Payment"
    subtitle="Manage overtime payment calculations and processing"
    :breadcrumbs="[
      { label: 'Payroll Module', to: '/' },
      { label: 'Payroll Benefits', to: '/payroll-benefits' },
      { label: 'Overtime Payment' },
    ]"
  >
    <template #actions v-if="!showForm">
      <button class="btn-ghost" :disabled="loading" @click="loadOvertimeData">
        <svg width="13" height="13" viewBox="0 0 13 13" fill="none">
          <path
            d="M2 6.5A4.5 4.5 0 0 1 9.8 3.2"
            stroke="currentColor"
            stroke-width="1.3"
            stroke-linecap="round"
          />
          <path
            d="M1.5 1.5v2.5h2.5"
            stroke="currentColor"
            stroke-width="1.3"
            stroke-linecap="round"
            stroke-linejoin="round"
          />
        </svg>
        {{ loading ? "Loading…" : "Refresh" }}
      </button>
      <button class="btn-primary" @click="handleAddNew">
        <svg width="13" height="13" viewBox="0 0 13 13" fill="none">
          <path
            d="M6.5 2v9M2 6.5h9"
            stroke="currentColor"
            stroke-width="1.5"
            stroke-linecap="round"
          />
        </svg>
        Add overtime payroll
      </button>
    </template>

    <!-- Summary cards -->
    <div v-if="!showForm" class="summary-grid">
      <div class="stat-card">
        <div class="stat-label">Overtime payrolls</div>
        <div class="stat-value">{{ filteredOvertimeData.length }}</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Total OT amount</div>
        <div class="stat-value">₱{{ formatCurrency(totalOvertimeAmount) }}</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Posted OT amount</div>
        <div class="stat-value green">
          ₱{{ formatCurrency(totalPostedAmount) }}
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Draft OT amount</div>
        <div class="stat-value amber">
          ₱{{ formatCurrency(totalDraftAmount) }}
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div v-if="!showForm" class="filter-bar">
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
          placeholder="Search by period or cut-off…"
        />
      </div>
      <select v-model="statusFilter" class="filter-select">
        <option value="">All statuses</option>
        <option value="posted">Posted</option>
        <option value="draft">Draft</option>
      </select>
      <button class="btn-ghost" @click="resetFilters">Reset</button>
    </div>

    <!-- List -->
    <OvertimePaymentList
      v-if="!showForm"
      ref="listRef"
      :overtime-data="filteredOvertimeData"
      :loading="loading"
      @edit="handleEdit"
      @delete="handleDelete"
      @view-employees="handleViewEmployees"
      @post="handlePost"
      @unpost="handleUnpost"
      @print="handlePrint"
      @tabulate="handleTabulate"
      @remove-employee="handleRemoveEmployee"
      @load-employee-data="handleLoadEmployeeData"
      @add-new="handleAddNew"
    />

    <!-- Form -->
    <OvertimePaymentForm
      v-if="showForm"
      :edit-data="editData"
      :is-detail-view="false"
      @saved="handleFormSaved"
      @close="handleFormClose"
    />

    <!-- Delete confirm modal -->
    <Teleport to="body">
      <Transition name="fade">
        <div
          v-if="deleteTarget"
          class="modal-backdrop"
          @click.self="deleteTarget = null"
        >
          <div class="confirm-modal">
            <div class="confirm-icon">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                <path
                  d="M12 9v4M12 17h.01"
                  stroke="#a32d2d"
                  stroke-width="1.8"
                  stroke-linecap="round"
                />
                <path
                  d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"
                  stroke="#a32d2d"
                  stroke-width="1.5"
                />
              </svg>
            </div>
            <p class="confirm-title">Delete overtime payroll?</p>
            <p class="confirm-body">
              <strong>{{ deleteTarget?.payrollPeriod }}</strong> will be
              permanently removed. This action cannot be undone.
            </p>
            <div class="confirm-actions">
              <button class="btn-ghost" @click="deleteTarget = null">
                Cancel
              </button>
              <button
                class="btn-danger"
                :disabled="loading"
                @click="confirmDelete"
              >
                {{ loading ? "Deleting…" : "Delete" }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </PageScaffold>
</template>

<script setup>
import { ref, onMounted, computed } from "vue";
import PageScaffold from "../../../components/PageScaffold.vue";
import OvertimePaymentList from "../../../components/Payroll_Benefits/Overtime_Payment/OvertimePaymentList.vue";
import OvertimePaymentForm from "../../../components/Payroll_Benefits/Overtime_Payment/OvertimePaymentForm.vue";
import { useOvertimePayroll } from "../../../Composables/useOvertimePayroll.js";

const {
  loading,
  overtimePayrollList,
  loadOvertimePayrollList,
  processOvertimePayroll,
  deleteOvertimePayroll,
  generateReport,
  loadFormData,
  transformOvertimeData,
} = useOvertimePayroll();

const showForm = ref(false);
const editData = ref(null);
const searchQuery = ref("");
const statusFilter = ref("");
const listRef = ref(null);
const deleteTarget = ref(null);

const formatCurrency = (v) =>
  Number(v || 0).toLocaleString("en-PH", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });

const filteredOvertimeData = computed(() => {
  let f = overtimePayrollList.value;
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase();
    f = f.filter(
      (i) =>
        i.payroll_period?.toLowerCase().includes(q) ||
        i.cut_off?.toLowerCase().includes(q),
    );
  }
  if (statusFilter.value === "posted") f = f.filter((i) => i.posted);
  if (statusFilter.value === "draft") f = f.filter((i) => !i.posted);
  return transformOvertimeData(f);
});

const totalOvertimeAmount = computed(() =>
  filteredOvertimeData.value.reduce((s, r) => s + (r.totalAmount || 0), 0),
);
const totalPostedAmount = computed(() =>
  filteredOvertimeData.value
    .filter((r) => r.posted)
    .reduce((s, r) => s + (r.totalAmount || 0), 0),
);
const totalDraftAmount = computed(() =>
  filteredOvertimeData.value
    .filter((r) => !r.posted)
    .reduce((s, r) => s + (r.totalAmount || 0), 0),
);

const loadOvertimeData = async () => {
  try {
    await loadOvertimePayrollList();
  } catch (e) {
    console.error(e);
  }
};
const resetFilters = () => {
  searchQuery.value = "";
  statusFilter.value = "";
};
const handleAddNew = () => {
  editData.value = null;
  showForm.value = true;
};
const handleEdit = (ot) => {
  editData.value = ot;
  showForm.value = true;
};
const handleDelete = (ot) => {
  deleteTarget.value = ot;
};
const confirmDelete = async () => {
  if (!deleteTarget.value) return;
  try {
    await deleteOvertimePayroll(deleteTarget.value.id);
    deleteTarget.value = null;
    await loadOvertimeData();
  } catch (e) {
    console.error(e);
  }
};
const handleViewEmployees = async (ot) => {
  if (listRef.value) await listRef.value.showEmployees(ot);
};
const handlePost = async (id, typeId) => {
  try {
    await processOvertimePayroll(id, typeId);
    await loadOvertimeData();
  } catch (e) {
    console.error(e);
  }
};
const handleUnpost = async (id, typeId) => {
  try {
    await processOvertimePayroll(id, typeId);
    await loadOvertimeData();
  } catch (e) {
    console.error(e);
  }
};
const handlePrint = async (ot) => {
  try {
    await generateReport({ payroll_period_id: ot.payrollPeriodId });
  } catch (e) {
    console.error(e);
  }
};
const handleTabulate = async (ot) => {
  try {
    await generateReport({
      payroll_period_id: ot.payrollPeriodId,
      type: "tabulated",
    });
  } catch (e) {
    console.error(e);
  }
};
const handleRemoveEmployee = () => {
  loadOvertimeData();
};
const handleLoadEmployeeData = async (payrollId, cb) => {
  try {
    const r = await loadFormData(payrollId);
    cb(r.employees);
  } catch (e) {
    console.error(e);
  }
};
const handleFormSaved = () => {
  loadOvertimeData();
};
const handleFormClose = () => {
  editData.value = null;
  showForm.value = false;
};

onMounted(loadOvertimeData);
</script>

<style scoped>
.summary-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 10px;
  margin-bottom: 20px;
}
.stat-card {
  background: #f8f9fa;
  border-radius: 10px;
  padding: 14px 16px;
}
.stat-label {
  font-size: 12px;
  color: #6b7280;
  margin-bottom: 5px;
}
.stat-value {
  font-size: 20px;
  font-weight: 500;
  color: #111827;
}
.stat-value.green {
  color: #085041;
}
.stat-value.amber {
  color: #633806;
}

.filter-bar {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 16px;
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
.filter-select {
  font-size: 13px;
  padding: 7px 10px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  background: #fff;
  color: #111827;
  outline: none;
  min-width: 140px;
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
.btn-ghost {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: transparent;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 7px 14px;
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

/* Delete confirm */
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
  transition: opacity 0.18s;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
.confirm-modal {
  background: #fff;
  border-radius: 14px;
  padding: 28px 24px;
  width: 100%;
  max-width: 360px;
  text-align: center;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.14);
}
.confirm-icon {
  margin: 0 auto 14px;
}
.confirm-title {
  font-size: 16px;
  font-weight: 500;
  color: #111827;
  margin: 0 0 8px;
}
.confirm-body {
  font-size: 13px;
  color: #6b7280;
  margin: 0 0 20px;
  line-height: 1.5;
}
.confirm-actions {
  display: flex;
  gap: 10px;
  justify-content: center;
}
.btn-danger {
  background: #a32d2d;
  color: #fff;
  border: none;
  border-radius: 8px;
  padding: 9px 20px;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
}
.btn-danger:hover:not(:disabled) {
  background: #7f2020;
}
.btn-danger:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>
