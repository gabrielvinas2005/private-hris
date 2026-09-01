<template>
  <PageScaffold
    title="Final Pay"
    subtitle="Compute and release final pay for separated employees — integrated with offboarding clearance"
    :breadcrumbs="[{ label: 'Payroll Module', to: '/' }, { label: 'Final Pay' }]"
  >
    <!-- Filters -->
    <div class="filter-bar">
      <input v-model="search" class="search-input" placeholder="Search employee name or ID…" />
      <select v-model="statusFilter" class="filter-select">
        <option value="">All Statuses</option>
        <option value="pending">Pending Computation</option>
        <option value="computed">Computed — Awaiting Release</option>
        <option value="released">Released</option>
      </select>
      <button class="btn-outline" :disabled="loading" @click="loadEmployees">
        {{ loading ? 'Refreshing…' : 'Refresh' }}
      </button>
    </div>

    <!-- Info Banner -->
    <div class="info-banner">
      <CircleCheckBig class="banner-icon" />
      <p>
        Only employees with a <strong>completed offboarding clearance</strong> appear here.
        Final pay includes unpaid salary, pro-rated 13th month, unused leave conversion, and remaining loans/deductions.
      </p>
    </div>

    <!-- Employee List -->
    <div class="table-card">
      <div v-if="loading" class="tbl-state">Loading separated employees…</div>
      <div v-else-if="!filteredEmployees.length" class="tbl-state">
        No employees pending final pay computation.
      </div>
      <div v-else class="tbl-wrap">
        <table class="data-table">
          <thead>
            <tr>
              <th>Employee No.</th>
              <th>Name</th>
              <th>Department</th>
              <th>Separation Date</th>
              <th>Reason</th>
              <th>Status</th>
              <th>Net Final Pay</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="emp in filteredEmployees" :key="emp.employee_id">
              <td class="mono">{{ emp.employee_no }}</td>
              <td class="font-semibold">{{ emp.name }}</td>
              <td>{{ emp.department }}</td>
              <td>{{ formatDate(emp.separation_date) }}</td>
              <td>{{ emp.reason }}</td>
              <td>
                <span class="badge" :class="statusClass(emp.final_pay_status)">
                  {{ statusLabel(emp.final_pay_status) }}
                </span>
              </td>
              <td class="amount">
                <span v-if="emp.net_final_pay != null" class="green-text">
                  ₱{{ formatAmount(emp.net_final_pay) }}
                </span>
                <span v-else class="muted">—</span>
              </td>
              <td>
                <div class="action-btns">
                  <button
                    v-if="emp.final_pay_status !== 'released'"
                    class="act-btn primary"
                    :disabled="computing"
                    @click="handleCompute(emp)"
                  >Compute</button>
                  <button
                    v-if="emp.final_pay_status === 'computed'"
                    class="act-btn success"
                    :disabled="computing"
                    @click="handleRelease(emp)"
                  >Release</button>
                  <button
                    v-if="emp.final_pay_status === 'released'"
                    class="act-btn outline"
                    @click="handlePayslip(emp)"
                  >Payslip</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Breakdown Dialog -->
    <div v-if="showBreakdown" class="overlay" @click.self="showBreakdown = false">
      <div class="breakdown-dialog">
        <div class="bd-header">
          <h3>Final Pay Breakdown — {{ activeEmployee?.name }}</h3>
          <button class="bd-close" @click="showBreakdown = false">✕</button>
        </div>
        <div class="bd-body">
          <div v-for="item in breakdownItems" :key="item.label" class="bd-row" :class="{ 'bd-deduction': item.isDeduction }">
            <span class="bd-label">{{ item.label }}</span>
            <span class="bd-amount" :class="item.isDeduction ? 'red-text' : 'green-text'">
              {{ item.isDeduction ? '-' : '+' }}₱{{ formatAmount(item.amount) }}
            </span>
          </div>
          <div class="bd-row bd-total">
            <span class="bd-label">Net Final Pay</span>
            <span class="bd-amount green-text">₱{{ formatAmount(activeEmployee?.net_final_pay) }}</span>
          </div>
        </div>
        <div class="bd-footer">
          <button class="btn-primary" @click="handleRelease(activeEmployee)" :disabled="activeEmployee?.final_pay_status === 'released'">
            Release Final Pay
          </button>
          <button class="btn-outline" @click="showBreakdown = false">Close</button>
        </div>
      </div>
    </div>
  </PageScaffold>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { CircleCheckBig } from "lucide-vue-next";
import PageScaffold from "../../components/PageScaffold.vue";
import { useFinalPay } from "../../Composables/useFinalPay.js";

const { loading, computing, employees, fetchSeparatedEmployees, computeFinalPay, releaseFinalPay, generatePayslip } = useFinalPay();

const search = ref("");
const statusFilter = ref("");
const showBreakdown = ref(false);
const activeEmployee = ref(null);
const breakdownItems = ref([]);

const filteredEmployees = computed(() => {
  const q = search.value.trim().toLowerCase();
  return employees.value.filter((e) => {
    const matchQ = !q || e.name?.toLowerCase().includes(q) || e.employee_no?.toLowerCase().includes(q);
    const matchS = !statusFilter.value || e.final_pay_status === statusFilter.value;
    return matchQ && matchS;
  });
});

function formatDate(d) {
  if (!d) return "—";
  return new Date(d).toLocaleDateString("en-PH", { year: "numeric", month: "short", day: "numeric" });
}
function formatAmount(v) {
  return Number(v ?? 0).toLocaleString("en-PH", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
function statusLabel(s) {
  return { pending: "Pending", computed: "Computed", released: "Released" }[s] ?? s;
}
function statusClass(s) {
  return { pending: "badge-amber", computed: "badge-blue", released: "badge-green" }[s] ?? "badge-gray";
}

async function loadEmployees() {
  await fetchSeparatedEmployees();
}

async function handleCompute(emp) {
  const res = await computeFinalPay(emp.employee_id);
  if (res.success) {
    activeEmployee.value = { ...emp, ...res.data };
    breakdownItems.value = res.data?.breakdown ?? [];
    showBreakdown.value = true;
    await loadEmployees();
  }
}
async function handleRelease(emp) {
  const ok = await releaseFinalPay(emp.employee_id);
  if (ok) { showBreakdown.value = false; await loadEmployees(); }
}
async function handlePayslip(emp) {
  await generatePayslip(emp.employee_id);
}

onMounted(loadEmployees);
</script>

<style scoped>
.filter-bar { display: flex; gap: 12px; flex-wrap: wrap; align-items: center; margin-bottom: 16px; }
.search-input { padding: 9px 14px; border: 1px solid #e2e8f0; border-radius: 10px; font-size: 13px; color: #374151; width: 240px; }
.filter-select { padding: 9px 12px; border: 1px solid #e2e8f0; border-radius: 10px; font-size: 13px; color: #374151; background: #f8fafc; }
.btn-outline { padding: 9px 18px; border: 1px solid #e2e8f0; border-radius: 10px; font-size: 13px; font-weight: 500; color: #475569; cursor: pointer; background: transparent; transition: all 0.15s; }
.btn-outline:hover:not(:disabled) { border-color: #3b5eff; color: #3b5eff; }
.btn-outline:disabled { opacity: 0.5; cursor: not-allowed; }
.btn-primary { padding: 10px 22px; background: #3b5eff; color: #fff; border: none; border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer; }
.btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }

.info-banner { display: flex; align-items: flex-start; gap: 12px; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 12px; padding: 14px 18px; margin-bottom: 20px; font-size: 13px; color: #1e40af; line-height: 1.5; }
.banner-icon { width: 20px; height: 20px; flex-shrink: 0; margin-top: 1px; }

.table-card { background: #fff; border: 1px solid #e8edf5; border-radius: 14px; overflow: hidden; }
.tbl-state { padding: 48px; text-align: center; color: #94a3b8; font-size: 13px; }
.tbl-wrap { overflow-x: auto; }
.data-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.data-table th { padding: 12px 14px; background: #f8fafc; color: #64748b; font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 1px solid #e8edf5; text-align: left; }
.data-table td { padding: 12px 14px; border-bottom: 1px solid #f1f5f9; color: #374151; vertical-align: middle; }
.data-table tr:last-child td { border-bottom: none; }
.data-table tr:hover td { background: #fafbff; }
.mono { font-family: monospace; color: #475569; }
.font-semibold { font-weight: 600; }
.amount { text-align: right; font-weight: 500; }
.green-text { color: #16a34a; }
.red-text { color: #dc2626; }
.muted { color: #94a3b8; }
.badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
.badge-amber { background: #fef9c3; color: #92400e; }
.badge-blue  { background: #eff6ff; color: #1d4ed8; }
.badge-green { background: #dcfce7; color: #15803d; }
.badge-gray  { background: #f1f5f9; color: #64748b; }

.action-btns { display: flex; gap: 6px; }
.act-btn { padding: 5px 12px; border-radius: 7px; font-size: 12px; font-weight: 600; cursor: pointer; border: 1px solid transparent; transition: all 0.15s; }
.act-btn.primary { background: #3b5eff; color: #fff; }
.act-btn.primary:hover:not(:disabled) { background: #2946d9; }
.act-btn.success { background: #16a34a; color: #fff; }
.act-btn.success:hover:not(:disabled) { background: #15803d; }
.act-btn.outline { background: transparent; border-color: #e2e8f0; color: #475569; }
.act-btn.outline:hover { border-color: #3b5eff; color: #3b5eff; }
.act-btn:disabled { opacity: 0.5; cursor: not-allowed; }

/* Breakdown Dialog */
.overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.4); z-index: 9999; display: flex; align-items: center; justify-content: center; }
.breakdown-dialog { background: #fff; border-radius: 16px; width: 480px; max-width: 95vw; box-shadow: 0 20px 60px rgba(0,0,0,0.2); }
.bd-header { display: flex; align-items: center; justify-content: space-between; padding: 20px 24px; border-bottom: 1px solid #f1f5f9; }
.bd-header h3 { font-size: 15px; font-weight: 700; color: #1e293b; margin: 0; }
.bd-close { background: none; border: none; font-size: 18px; color: #94a3b8; cursor: pointer; }
.bd-body { padding: 20px 24px; }
.bd-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f8fafc; font-size: 13px; }
.bd-total { border-top: 2px solid #e2e8f0; border-bottom: none; margin-top: 8px; padding-top: 12px; font-weight: 700; font-size: 15px; }
.bd-label { color: #475569; }
.bd-amount { font-weight: 600; }
.bd-footer { display: flex; gap: 10px; padding: 16px 24px; border-top: 1px solid #f1f5f9; }
</style>
