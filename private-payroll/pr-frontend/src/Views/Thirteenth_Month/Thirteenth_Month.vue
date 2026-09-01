<template>
  <PageScaffold
    title="13th Month Pay"
    subtitle="Compute and release mandated 13th month pay for all regular employees"
    :breadcrumbs="[{ label: 'Payroll Module', to: '/' }, { label: '13th Month Pay' }]"
  >
    <!-- Compute Form -->
    <div class="form-card">
      <div class="form-card-header">
        <CalendarDays class="form-icon" />
        <div>
          <p class="form-card-title">Compute 13th Month Pay</p>
          <p class="form-card-sub">Based on total basic salary earned within the calendar year (Jan – Dec).</p>
        </div>
      </div>
      <div class="form-row">
        <div class="field">
          <label class="field-label">Year</label>
          <select v-model="form.year" class="field-select">
            <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
          </select>
        </div>
        <div class="field">
          <label class="field-label">Department</label>
          <select v-model="form.department_id" class="field-select">
            <option value="">All Departments</option>
            <option v-for="d in departments" :key="d.id" :value="d.id">{{ d.name }}</option>
          </select>
        </div>
        <div class="field">
          <label class="field-label">Employment Type</label>
          <select v-model="form.employment_type_id" class="field-select">
            <option value="">All Types</option>
            <option value="1">Regular</option>
            <option value="2">Probationary</option>
          </select>
        </div>
      </div>
      <div class="form-actions">
        <button class="btn-primary" :disabled="processing" @click="handleCompute">
          <span v-if="processing" class="spinner"></span>
          {{ processing ? 'Computing…' : 'Compute 13th Month Pay' }}
        </button>
        <button class="btn-outline" :disabled="loading" @click="handleLoad">
          {{ loading ? 'Loading…' : 'Load Records' }}
        </button>
      </div>
    </div>

    <!-- Summary Cards -->
    <div v-if="summary" class="summary-row">
      <div class="summary-card">
        <p class="scard-label">Total Employees</p>
        <p class="scard-val">{{ summary.total_employees ?? 0 }}</p>
      </div>
      <div class="summary-card">
        <p class="scard-label">Total 13th Month Amount</p>
        <p class="scard-val green">₱{{ formatAmount(summary.total_amount) }}</p>
      </div>
      <div class="summary-card">
        <p class="scard-label">Posted</p>
        <p class="scard-val blue">{{ summary.posted_count ?? 0 }}</p>
      </div>
      <div class="summary-card">
        <p class="scard-label">Pending</p>
        <p class="scard-val amber">{{ summary.pending_count ?? 0 }}</p>
      </div>
    </div>

    <!-- Records Table -->
    <div class="table-card">
      <div class="table-header">
        <p class="table-title">Employee Records</p>
        <div class="table-actions">
          <input v-model="search" class="tbl-search" placeholder="Search employee…" />
          <button class="btn-outline sm" :disabled="!selectedIds.length" @click="handlePost">
            Post Selected ({{ selectedIds.length }})
          </button>
          <button class="btn-outline sm" @click="handleExportPdf">PDF</button>
          <button class="btn-outline sm" @click="handleExportExcel">Excel</button>
        </div>
      </div>

      <div v-if="loading" class="tbl-loading">Loading records…</div>
      <div v-else-if="!filteredRecords.length && !loading" class="tbl-empty">
        No records found. Compute 13th month pay or adjust filters.
      </div>
      <div v-else class="tbl-wrap">
        <table class="data-table">
          <thead>
            <tr>
              <th><input type="checkbox" @change="toggleAll" /></th>
              <th>Employee No.</th>
              <th>Name</th>
              <th>Department</th>
              <th>Total Basic Salary (Jan–Dec)</th>
              <th>13th Month Amount</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="r in filteredRecords" :key="r.id">
              <td><input type="checkbox" v-model="selectedIds" :value="r.id" /></td>
              <td class="mono">{{ r.employee_no }}</td>
              <td>{{ r.name }}</td>
              <td>{{ r.department }}</td>
              <td class="amount">₱{{ formatAmount(r.total_basic_salary) }}</td>
              <td class="amount green-text">₱{{ formatAmount(r.thirteenth_month_amount) }}</td>
              <td>
                <span class="badge" :class="r.posted ? 'badge-green' : 'badge-amber'">
                  {{ r.posted ? 'Posted' : 'Pending' }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </PageScaffold>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { CalendarDays } from "lucide-vue-next";
import PageScaffold from "../../components/PageScaffold.vue";
import { useThirteenthMonth } from "../../Composables/useThirteenthMonth.js";

const {
  loading, processing, records, departments, summary,
  fetchDepartments, fetchRecords, computeThirteenthMonth, postRecords, exportPdf, exportExcel,
} = useThirteenthMonth();

const search = ref("");
const selectedIds = ref([]);
const form = ref({
  year: new Date().getFullYear(),
  department_id: "",
  employment_type_id: "",
});

const years = computed(() => {
  const y = new Date().getFullYear();
  return Array.from({ length: 6 }, (_, i) => y - i);
});

const filteredRecords = computed(() => {
  const q = search.value.trim().toLowerCase();
  return q
    ? records.value.filter((r) => r.name?.toLowerCase().includes(q) || r.employee_no?.toLowerCase().includes(q))
    : records.value;
});

const formatAmount = (v) =>
  Number(v ?? 0).toLocaleString("en-PH", { minimumFractionDigits: 2, maximumFractionDigits: 2 });

function toggleAll(e) {
  selectedIds.value = e.target.checked ? filteredRecords.value.map((r) => r.id) : [];
}

async function handleCompute() {
  await computeThirteenthMonth({ ...form.value });
}
async function handleLoad() {
  await fetchRecords({ year: form.value.year, department_id: form.value.department_id || undefined });
}
async function handlePost() {
  const ok = await postRecords(selectedIds.value);
  if (ok) { selectedIds.value = []; await handleLoad(); }
}
async function handleExportPdf() {
  await exportPdf({ year: form.value.year, department_id: form.value.department_id || undefined });
}
async function handleExportExcel() {
  await exportExcel({ year: form.value.year, department_id: form.value.department_id || undefined });
}

onMounted(async () => {
  await fetchDepartments();
  await fetchRecords({ year: form.value.year });
});
</script>

<style scoped>
.form-card {
  background: #fff; border: 1px solid #e8edf5; border-radius: 14px;
  padding: 24px; margin-bottom: 20px;
}
.form-card-header {
  display: flex; align-items: flex-start; gap: 14px; margin-bottom: 20px;
}
.form-icon { width: 28px; height: 28px; color: #9333ea; flex-shrink: 0; margin-top: 2px; }
.form-card-title { font-size: 15px; font-weight: 700; color: #1e293b; margin: 0 0 4px; }
.form-card-sub   { font-size: 12px; color: #64748b; margin: 0; }
.form-row { display: flex; flex-wrap: wrap; gap: 16px; margin-bottom: 20px; }
.field { display: flex; flex-direction: column; gap: 6px; }
.field-label { font-size: 12px; font-weight: 600; color: #475569; }
.field-select {
  padding: 8px 12px; border: 1px solid #e2e8f0; border-radius: 8px;
  font-size: 13px; color: #1e293b; background: #f8fafc; min-width: 160px;
}
.form-actions { display: flex; gap: 12px; }
.btn-primary {
  display: inline-flex; align-items: center; gap: 8px;
  padding: 10px 22px; background: #3b5eff; color: #fff;
  border: none; border-radius: 10px; font-size: 13px; font-weight: 600;
  cursor: pointer; transition: background 0.15s;
}
.btn-primary:hover:not(:disabled) { background: #2946d9; }
.btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }
.btn-outline {
  padding: 10px 18px; background: transparent; color: #475569;
  border: 1px solid #e2e8f0; border-radius: 10px; font-size: 13px;
  font-weight: 500; cursor: pointer; transition: all 0.15s;
}
.btn-outline:hover:not(:disabled) { border-color: #3b5eff; color: #3b5eff; }
.btn-outline.sm { padding: 7px 14px; font-size: 12px; border-radius: 8px; }
.btn-outline:disabled { opacity: 0.5; cursor: not-allowed; }

.spinner {
  width: 14px; height: 14px; border-radius: 50%;
  border: 2px solid rgba(255,255,255,0.3); border-top-color: #fff;
  animation: spin 0.7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

.summary-row { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 14px; margin-bottom: 20px; }
.summary-card { background: #fff; border: 1px solid #e8edf5; border-radius: 12px; padding: 16px 20px; }
.scard-label { font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.06em; margin: 0 0 6px; }
.scard-val   { font-size: 22px; font-weight: 700; color: #1e293b; margin: 0; }
.scard-val.green { color: #16a34a; }
.scard-val.blue  { color: #3b5eff; }
.scard-val.amber { color: #d97706; }

.table-card { background: #fff; border: 1px solid #e8edf5; border-radius: 14px; overflow: hidden; }
.table-header { display: flex; align-items: center; justify-content: space-between; padding: 18px 20px; border-bottom: 1px solid #f1f5f9; flex-wrap: wrap; gap: 12px; }
.table-title { font-size: 14px; font-weight: 700; color: #1e293b; margin: 0; }
.table-actions { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
.tbl-search { padding: 7px 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 12px; color: #374151; width: 180px; }
.tbl-loading, .tbl-empty { padding: 48px; text-align: center; color: #94a3b8; font-size: 13px; }
.tbl-wrap { overflow-x: auto; }
.data-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.data-table th { padding: 12px 16px; background: #f8fafc; color: #64748b; font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; text-align: left; border-bottom: 1px solid #e8edf5; }
.data-table td { padding: 12px 16px; border-bottom: 1px solid #f1f5f9; color: #374151; vertical-align: middle; }
.data-table tr:last-child td { border-bottom: none; }
.data-table tr:hover td { background: #fafbff; }
.mono { font-family: monospace; color: #475569; }
.amount { text-align: right; font-weight: 500; }
.green-text { color: #16a34a; }
.badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
.badge-green { background: #dcfce7; color: #15803d; }
.badge-amber { background: #fef9c3; color: #92400e; }
</style>
