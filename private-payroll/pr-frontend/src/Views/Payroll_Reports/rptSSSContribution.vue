<template>
  <PageScaffold
    title="SSS Contribution Report"
    subtitle="Monthly SSS remittance data for employer and employee contributions"
    :breadcrumbs="[{ label: 'Payroll Module', to: '/' }, { label: 'Reports' }, { label: 'SSS Contribution' }]"
  >
    <!-- Filters -->
    <div class="filter-card">
      <div class="filter-row">
        <div class="field">
          <label class="field-label">Month</label>
          <select v-model="form.month" class="field-select">
            <option v-for="(m, i) in months" :key="i" :value="i + 1">{{ m }}</option>
          </select>
        </div>
        <div class="field">
          <label class="field-label">Year</label>
          <select v-model="form.year" class="field-select">
            <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
          </select>
        </div>
        <div class="field">
          <label class="field-label">Payroll Interval</label>
          <select v-model="form.interval_id" class="field-select">
            <option value="">All Intervals</option>
            <option value="1">Semi-Monthly</option>
            <option value="2">Monthly</option>
          </select>
        </div>
        <div class="field">
          <label class="field-label">Department</label>
          <select v-model="form.department_id" class="field-select">
            <option value="">All Departments</option>
            <option v-for="d in departments" :key="d.id" :value="d.id">{{ d.name }}</option>
          </select>
        </div>
      </div>
      <div class="filter-actions">
        <button class="btn-primary" :disabled="loading" @click="handleLoad">
          <span v-if="loading" class="spinner"></span>
          {{ loading ? 'Loading…' : 'Generate Report' }}
        </button>
        <button class="btn-outline" :disabled="!records.length" @click="handlePrint">Print / PDF</button>
        <button class="btn-outline" :disabled="!records.length" @click="handleExcel">Export Excel</button>
      </div>
    </div>

    <!-- Summary -->
    <div v-if="summary" class="summary-row">
      <div class="scard">
        <p class="scard-label">Total Employees</p>
        <p class="scard-val">{{ summary.total_employees ?? 0 }}</p>
      </div>
      <div class="scard">
        <p class="scard-label">Employee Contributions</p>
        <p class="scard-val blue">₱{{ fmt(summary.total_ee_contribution) }}</p>
      </div>
      <div class="scard">
        <p class="scard-label">Employer Contributions</p>
        <p class="scard-val green">₱{{ fmt(summary.total_er_contribution) }}</p>
      </div>
      <div class="scard">
        <p class="scard-label">EC Contributions</p>
        <p class="scard-val purple">₱{{ fmt(summary.total_ec_contribution) }}</p>
      </div>
      <div class="scard">
        <p class="scard-label">Total Remittance</p>
        <p class="scard-val orange">₱{{ fmt(summary.total_remittance) }}</p>
      </div>
    </div>

    <!-- Table -->
    <div class="table-card">
      <div class="table-toolbar">
        <p class="table-label">
          SSS Contributions — {{ months[(form.month ?? 1) - 1] }} {{ form.year }}
        </p>
        <input v-model="search" class="tbl-search" placeholder="Search employee…" />
      </div>
      <div v-if="loading" class="tbl-state">Generating report…</div>
      <div v-else-if="!filteredRecords.length" class="tbl-state">
        Select filters and click <strong>Generate Report</strong>.
      </div>
      <div v-else class="tbl-wrap">
        <table class="data-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Employee No.</th>
              <th>Employee Name</th>
              <th>SSS No.</th>
              <th>Monthly Salary Credit</th>
              <th>EE Contribution</th>
              <th>ER Contribution</th>
              <th>EC Contribution</th>
              <th>Total</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(r, idx) in filteredRecords" :key="r.id">
              <td class="muted">{{ idx + 1 }}</td>
              <td class="mono">{{ r.employee_no }}</td>
              <td>{{ r.name }}</td>
              <td class="mono">{{ r.sss_no ?? '—' }}</td>
              <td class="amount">₱{{ fmt(r.monthly_salary_credit) }}</td>
              <td class="amount blue-text">₱{{ fmt(r.ee_contribution) }}</td>
              <td class="amount green-text">₱{{ fmt(r.er_contribution) }}</td>
              <td class="amount purple-text">₱{{ fmt(r.ec_contribution) }}</td>
              <td class="amount font-bold">₱{{ fmt(r.total_contribution) }}</td>
            </tr>
          </tbody>
          <tfoot>
            <tr class="tfoot-row">
              <td colspan="4" class="tfoot-label">TOTAL</td>
              <td class="amount">₱{{ fmt(colTotal('monthly_salary_credit')) }}</td>
              <td class="amount blue-text">₱{{ fmt(colTotal('ee_contribution')) }}</td>
              <td class="amount green-text">₱{{ fmt(colTotal('er_contribution')) }}</td>
              <td class="amount purple-text">₱{{ fmt(colTotal('ec_contribution')) }}</td>
              <td class="amount font-bold">₱{{ fmt(colTotal('total_contribution')) }}</td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </PageScaffold>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import axios from "axios";
import { ElMessage } from "element-plus";
import PageScaffold from "../../components/PageScaffold.vue";

const API = import.meta.env.VITE_API_URL || "";

const loading = ref(false);
const records = ref([]);
const summary = ref(null);
const departments = ref([]);
const search = ref("");

const months = ["January","February","March","April","May","June","July","August","September","October","November","December"];
const years = computed(() => { const y = new Date().getFullYear(); return Array.from({ length: 6 }, (_, i) => y - i); });

const form = ref({
  month: new Date().getMonth() + 1,
  year: new Date().getFullYear(),
  interval_id: "",
  department_id: "",
});

const filteredRecords = computed(() => {
  const q = search.value.trim().toLowerCase();
  return q ? records.value.filter((r) => r.name?.toLowerCase().includes(q) || r.employee_no?.toLowerCase().includes(q)) : records.value;
});

const fmt = (v) => Number(v ?? 0).toLocaleString("en-PH", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const colTotal = (key) => filteredRecords.value.reduce((s, r) => s + Number(r[key] ?? 0), 0);

async function fetchDepartments() {
  try {
    const { data } = await axios.get(`${API}/api/departments`);
    departments.value = data?.data ?? data ?? [];
  } catch (e) { console.error(e); }
}

async function handleLoad() {
  try {
    loading.value = true;
    const params = { ...form.value };
    if (!params.interval_id) delete params.interval_id;
    if (!params.department_id) delete params.department_id;
    const { data } = await axios.get(`${API}/api/sss-contribution-report`, { params });
    records.value = data?.data ?? data ?? [];
    summary.value = data?.summary ?? null;
  } catch (e) {
    ElMessage.error("Failed to load SSS contribution report.");
  } finally {
    loading.value = false;
  }
}

async function handlePrint() {
  try {
    const params = { ...form.value, format: "pdf" };
    const res = await axios.get(`${API}/api/sss-contribution-report/print`, { params, responseType: "blob" });
    const url = URL.createObjectURL(new Blob([res.data], { type: "application/pdf" }));
    window.open(url, "_blank");
  } catch (e) { ElMessage.error("Failed to generate PDF."); }
}

async function handleExcel() {
  try {
    const params = { ...form.value, format: "excel" };
    const res = await axios.get(`${API}/api/sss-contribution-report/print`, { params, responseType: "blob" });
    const url  = URL.createObjectURL(new Blob([res.data]));
    const link = Object.assign(document.createElement("a"), { href: url, download: `sss_contribution_${form.value.month}_${form.value.year}.xlsx` });
    document.body.appendChild(link); link.click(); link.remove(); URL.revokeObjectURL(url);
  } catch (e) { ElMessage.error("Failed to export Excel."); }
}

onMounted(fetchDepartments);
</script>

<style scoped>
.filter-card { background: #fff; border: 1px solid #e8edf5; border-radius: 14px; padding: 20px 24px; margin-bottom: 20px; }
.filter-row { display: flex; flex-wrap: wrap; gap: 16px; margin-bottom: 16px; }
.field { display: flex; flex-direction: column; gap: 5px; }
.field-label { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; }
.field-select { padding: 8px 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 13px; background: #f8fafc; color: #1e293b; min-width: 150px; }
.filter-actions { display: flex; gap: 10px; flex-wrap: wrap; }
.btn-primary { display: inline-flex; align-items: center; gap: 8px; padding: 9px 20px; background: #3b5eff; color: #fff; border: none; border-radius: 9px; font-size: 13px; font-weight: 600; cursor: pointer; }
.btn-primary:hover:not(:disabled) { background: #2946d9; }
.btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }
.btn-outline { padding: 9px 16px; border: 1px solid #e2e8f0; border-radius: 9px; font-size: 13px; font-weight: 500; color: #475569; cursor: pointer; background: transparent; }
.btn-outline:hover:not(:disabled) { border-color: #3b5eff; color: #3b5eff; }
.btn-outline:disabled { opacity: 0.5; cursor: not-allowed; }
.spinner { width: 13px; height: 13px; border-radius: 50%; border: 2px solid rgba(255,255,255,0.3); border-top-color: #fff; animation: spin 0.7s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

.summary-row { display: grid; grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)); gap: 12px; margin-bottom: 20px; }
.scard { background: #fff; border: 1px solid #e8edf5; border-radius: 12px; padding: 14px 18px; }
.scard-label { font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.06em; margin: 0 0 5px; }
.scard-val { font-size: 20px; font-weight: 700; color: #1e293b; margin: 0; }
.scard-val.blue { color: #2563eb; }
.scard-val.green { color: #16a34a; }
.scard-val.purple { color: #9333ea; }
.scard-val.orange { color: #ea580c; }

.table-card { background: #fff; border: 1px solid #e8edf5; border-radius: 14px; overflow: hidden; }
.table-toolbar { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; border-bottom: 1px solid #f1f5f9; flex-wrap: wrap; gap: 10px; }
.table-label { font-size: 14px; font-weight: 700; color: #1e293b; margin: 0; }
.tbl-search { padding: 7px 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 12px; color: #374151; width: 200px; }
.tbl-state { padding: 48px; text-align: center; color: #94a3b8; font-size: 13px; }
.tbl-wrap { overflow-x: auto; }
.data-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.data-table th { padding: 11px 14px; background: #f8fafc; color: #64748b; font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.04em; border-bottom: 1px solid #e8edf5; text-align: left; white-space: nowrap; }
.data-table td { padding: 11px 14px; border-bottom: 1px solid #f1f5f9; color: #374151; vertical-align: middle; }
.data-table tr:last-child td { border-bottom: none; }
.data-table tr:hover td { background: #fafbff; }
.tfoot-row td { background: #f8fafc; font-weight: 700; border-top: 2px solid #e2e8f0; border-bottom: none; }
.tfoot-label { color: #475569; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; }
.mono { font-family: monospace; color: #475569; font-size: 12px; }
.muted { color: #94a3b8; font-size: 12px; }
.amount { text-align: right; font-weight: 500; white-space: nowrap; }
.font-bold { font-weight: 700; }
.blue-text { color: #2563eb; }
.green-text { color: #16a34a; }
.purple-text { color: #9333ea; }
</style>
