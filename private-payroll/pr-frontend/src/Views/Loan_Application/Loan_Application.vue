<template>
  <PageScaffold
    title="Loan Application"
    subtitle="Process and manage employee loan applications"
    :breadcrumbs="[
      { label: 'Payroll Module', to: '/' },
      { label: 'Loan Application' },
    ]"
  >
    <template #actions>
      <button class="btn-primary" @click="openCreate">
        <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
          <path
            d="M7 2v10M2 7h10"
            stroke="currentColor"
            stroke-width="1.5"
            stroke-linecap="round"
          />
        </svg>
        Add Loan Application
      </button>
    </template>

    <!-- Summary Cards -->
    <div class="summary-grid">
      <div class="stat-card">
        <div class="stat-label">Total Principal</div>
        <div class="stat-value">{{ formatCurrency(totalPrincipal) }}</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Outstanding Balance</div>
        <div class="stat-value info">{{ formatCurrency(totalBalance) }}</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Overdue Loans</div>
        <div class="stat-value" :class="{ danger: overdueCount > 0 }">
          {{ overdueCount }} Loans
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Active Employees</div>
        <div class="stat-value">{{ activeEmployees }}</div>
      </div>
    </div>

    <!-- Filters -->
    <div class="filter-row">
      <input
        v-model="filters.employee"
        class="filter-input"
        placeholder="Search employee..."
        @keyup.enter="applyFilters"
      />
      <select v-model="filters.category" class="filter-select">
        <option value="">All loan types</option>
        <option
          v-for="opt in loanCategories"
          :key="`${opt.group}-${opt.id}`"
          :value="String(opt.id)"
        >
          {{ opt.name }}
        </option>
      </select>
      <select v-model="filters.status" class="filter-select">
        <option value="">All status</option>
        <option value="active">Active</option>
        <option value="overdue">Overdue</option>
        <option value="has_balance">Has Balance</option>
      </select>
      <button class="btn-primary" @click="applyFilters">Search</button>
      <button class="btn-ghost" @click="resetFilters">Reset</button>
      <!-- <div class="export-group">
        <button class="export-btn" @click="handlePrint">Print</button>
        <button class="export-btn" @click="handleExcel">Excel</button>
        <button class="export-btn" @click="handlePDF">PDF</button>
      </div> -->
    </div>

    <LoanApplicationTable
      :rows="filteredRows"
      :loading="loading"
      @view="onView"
      @reconstruct="onReconstruct"
    />

    <LoanApplicationForm
      v-model="showForm"
      :bootstrap="formBootstrap"
      :edit-id="editId"
      :saving="saving"
      :mode="formMode"
      @save="submitForm"
    />
  </PageScaffold>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import PageScaffold from "../../components/PageScaffold.vue";
import LoanApplicationTable from "../../components/Loan_Application/LoanApplicationTable.vue";
import LoanApplicationForm from "../../components/Loan_Application/LoanApplicationForm.vue";
import useLoanApplication from "../../Composables/useLoanApplication";

const {
  loading,
  rows,
  filters,
  loanCategories,
  filteredRows,
  loadList,
  loadFormBootstrap,
  loadReconstructBootstrap,
  save,
  reconstructSave,
  resetFilters,
} = useLoanApplication();

const showForm = ref(false);
const formBootstrap = ref({ employees: [], deductions: [], loan_app: [] });
const editId = ref(0);
const saving = ref(false);
const formMode = ref("create");

// --- summary computed ---
const totalPrincipal = computed(() =>
  (rows.value || []).reduce((s, r) => s + Number(r.loan_amount || 0), 0),
);
const totalBalance = computed(() =>
  (rows.value || []).reduce((s, r) => s + Number(r.balance || 0), 0),
);
const overdueCount = computed(() => {
  const today = new Date();
  today.setHours(0, 0, 0, 0);

  return (rows.value || []).filter((r) => {
    const balance = Number(r.balance || 0);
    if (balance <= 0) return false;

    if (!r.end_date) return false;
    const endDate = new Date(r.end_date);
    if (Number.isNaN(endDate.getTime())) return false;
    endDate.setHours(0, 0, 0, 0);

    return endDate < today;
  }).length;
});
const activeEmployees = computed(
  () => new Set((rows.value || []).map((r) => r.employee_id)).size,
);

const formatCurrency = (val) => {
  const n = Number(val || 0);
  return (
    "₱" +
    n.toLocaleString(undefined, {
      minimumFractionDigits: 0,
      maximumFractionDigits: 0,
    })
  );
};

// --- actions ---
const openCreate = async () => {
  editId.value = 0;
  formMode.value = "create";
  formBootstrap.value = await loadFormBootstrap(0);
  showForm.value = true;
};
const onView = (row) => {
  editId.value = Number(row.id);
  formMode.value = "view";
  loadFormBootstrap(row.id).then((data) => {
    formBootstrap.value = data;
    showForm.value = true;
  });
};
const onReconstruct = async (row) => {
  editId.value = Number(row.id);
  formMode.value = "reconstruct";
  formBootstrap.value = await loadReconstructBootstrap(row.id);
  showForm.value = true;
};
const submitForm = async (payload) => {
  try {
    saving.value = true;
    if (formMode.value === "reconstruct" && editId.value) {
      await reconstructSave(editId.value, payload);
    } else {
      await save(payload, 0);
    }
    showForm.value = false;
    await loadList();
  } catch (e) {
    // surface server-side validation
  } finally {
    saving.value = false;
  }
};

const applyFilters = () => {};
const handlePrint = () => {};
const handleExcel = () => {};
const handlePDF = () => {};

onMounted(loadList);
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
  color: #1a1b1b;
  margin-bottom: 6px;
}
.stat-value {
  font-size: 20px;
  font-weight: 500;
  color: #111827;
}
.stat-value.info {
  color: #185fa5;
}
.stat-value.danger {
  color: #a32d2d;
}

.filter-row {
  display: flex;
  gap: 10px;
  align-items: center;
  margin-bottom: 16px;
  flex-wrap: wrap;
}
.filter-input,
.filter-select {
  font-size: 13px;
  padding: 7px 10px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  background: #fff;
  color: #111827;
  outline: none;
}
.filter-input {
  min-width: 200px;
}
.filter-select {
  min-width: 160px;
}
.filter-input:focus,
.filter-select:focus {
  border-color: #6b7280;
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
  white-space: nowrap;
}
.btn-primary:hover {
  opacity: 0.88;
}

.btn-ghost {
  background: transparent;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 8px 14px;
  font-size: 13px;
  color: #6b7280;
  cursor: pointer;
}
.btn-ghost:hover {
  background: #f3f4f6;
}

.export-group {
  display: flex;
  gap: 6px;
  margin-left: auto;
}
.export-btn {
  background: transparent;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 6px 12px;
  font-size: 12px;
  color: #6b7280;
  cursor: pointer;
}
.export-btn:hover {
  background: #f3f4f6;
}
</style>
