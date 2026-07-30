<template>
  <div class="pt-shell">
    <!-- Summary cards -->
    <div class="summary-grid">
      <div class="stat-card">
        <div class="stat-label">Total periods</div>
        <div class="stat-value">{{ totalPeriods }}</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Posted</div>
        <div class="stat-value info">{{ postedPeriods }}</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Unposted</div>
        <div class="stat-value danger">{{ unpostedPeriods }}</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Total net pay (posted)</div>
        <div class="stat-value mono">
          <span v-if="totalNetPayLoading">—</span>
          <span v-else>{{
            formatCurrency(totalNetPay, { maximumFractionDigits: 0 })
          }}</span>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="filter-bar">
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
          placeholder="Search payroll periods…"
          @input="handleSearch"
        />
      </div>
      <select
        v-model="statusFilter"
        class="filter-select"
        @change="handleFilter"
      >
        <option value="">All statuses</option>
        <option value="posted">Posted</option>
        <option value="unposted">Unposted</option>
      </select>
      <button class="btn-ghost" :disabled="loading" @click="refreshData">
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
    </div>

    <!-- Table -->
    <div class="table-wrap" v-if="!loading || filteredData.length > 0">
      <table v-if="filteredData.length > 0">
        <thead>
          <tr>
            <th>Payroll period</th>
            <th>Employment types</th>
            <th>Start date</th>
            <th>End date</th>
            <th>Release date</th>
            <th style="text-align: center">Status</th>
            <th style="width: 90px"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in paginatedData" :key="row.id">
            <td class="period-name">{{ row.payroll }}</td>
            <td class="muted">
              {{
                Array.isArray(row.employment_types)
                  ? row.employment_types.map((e) => e.name || e).join(", ")
                  : row.employment_types || "—"
              }}
            </td>
            <td class="muted">{{ formatDate(row.attendance_start_date) }}</td>
            <td class="muted">{{ formatDate(row.attendance_end_date) }}</td>
            <td>
              <div class="release-dates">
                <template
                  v-if="
                    String(row.first_half?.cutoff_name || '')
                      .toLowerCase()
                      .includes('monthly')
                  "
                >
                  <div class="rd-block">
                    <span class="rd-label">1st half</span>
                    <span class="rd-val">{{
                      formatDate(row.first_half.payroll_start_date)
                    }}</span>
                  </div>
                  <span class="rd-sep">·</span>
                  <div class="rd-block">
                    <span class="rd-label">2nd half</span>
                    <span class="rd-val">{{
                      formatDate(row.first_half.payroll_end_date)
                    }}</span>
                  </div>
                </template>
                <template
                  v-else-if="
                    row.first_half?.release_date &&
                    row.second_half?.release_date
                  "
                >
                  <div class="rd-block">
                    <span class="rd-label">1st half</span>
                    <span class="rd-val">{{
                      formatDate(row.first_half.release_date)
                    }}</span>
                  </div>
                  <span class="rd-sep">·</span>
                  <div class="rd-block">
                    <span class="rd-label">2nd half</span>
                    <span class="rd-val">{{
                      formatDate(row.second_half.release_date)
                    }}</span>
                  </div>
                </template>
                <div v-else class="rd-block">
                  <span class="rd-label">{{ getReleaseDateLabel(row) }}</span>
                  <span class="rd-val">{{ getReleaseDateDisplay(row) }}</span>
                </div>
              </div>
            </td>
            <td style="text-align: center">
              <span
                class="status-pill"
                :class="
                  row.posted ? 'posted' : row.partial ? 'partial' : 'unposted'
                "
              >
                {{
                  row.posted ? "Posted" : row.partial ? "Partial" : "Unposted"
                }}
              </span>
            </td>
            <td>
              <button class="btn-view" @click="handleView(row)">
                <svg width="13" height="13" viewBox="0 0 13 13" fill="none">
                  <circle
                    cx="6.5"
                    cy="6.5"
                    r="2.5"
                    stroke="currentColor"
                    stroke-width="1.2"
                  />
                  <path
                    d="M1 6.5C2.5 3 4.5 1.5 6.5 1.5S10.5 3 12 6.5c-1.5 3.5-3.5 5-5.5 5S2.5 10 1 6.5z"
                    stroke="currentColor"
                    stroke-width="1.2"
                  />
                </svg>
                View
              </button>
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
        <p>No payroll periods found</p>
      </div>
    </div>
    <div v-else class="skeleton-wrap">
      <div v-for="i in 4" :key="i" class="skeleton-row" />
    </div>

    <!-- Pagination -->
    <div v-if="filteredData.length > 0" class="pagination">
      <span class="page-info">{{ filteredData.length }} results</span>
      <div class="page-btns">
        <button
          class="page-btn"
          :disabled="currentPage === 1"
          @click="currentPage--"
        >&#8249;</button>
        <button
          v-for="p in totalPages"
          :key="p"
          class="page-btn"
          :class="{ active: p === currentPage }"
          @click="currentPage = p"
        >{{ p }}</button>
        <button
          class="page-btn"
          :disabled="currentPage === totalPages"
          @click="currentPage++"
        >&#8250;</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from "vue";
import { usePayrollProcess } from "../../Composables/usePayrollProcess";
import { payrollProcessApi } from "../../services/api";

const props = defineProps({
  data: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
});
const emit = defineEmits([
  "view",
  "process",
  "post",
  "unpost",
  "refresh",
  "print",
  "export",
  "print-report",
  "tabulated-report",
]);

const { processing, confirmUnpostPayroll, generatePayrollReport } =
  usePayrollProcess();

const searchQuery = ref("");
const statusFilter = ref("");
const totalNetPay = ref(0);
const totalNetPayLoading = ref(false);
const periodNetStats = ref({});
const currentPage = ref(1);
const pageSize = ref(10);

const filteredData = computed(() => {
  let f = props.data;
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase();
    f = f.filter(
      (r) =>
        r.payroll?.toLowerCase().includes(q) ||
        r.attendance_start_date?.includes(q) ||
        r.attendance_end_date?.includes(q),
    );
  }
  if (statusFilter.value === "posted") f = f.filter((r) => r.posted);
  if (statusFilter.value === "unposted") f = f.filter((r) => !r.posted);
  return f;
});

const totalPages = computed(() =>
  Math.max(1, Math.ceil(filteredData.value.length / pageSize.value)),
);
const paginatedData = computed(() => {
  const start = (currentPage.value - 1) * pageSize.value;
  return filteredData.value.slice(start, start + pageSize.value);
});

const totalPeriods = computed(() => props.data.length);
const postedPeriods = computed(() => props.data.filter((r) => r.posted).length);
const unpostedPeriods = computed(
  () => props.data.filter((r) => !r.posted).length,
);

const formatDate = (d) => {
  if (!d) return "—";
  return new Date(d).toLocaleDateString("en-US", {
    year: "numeric",
    month: "short",
    day: "numeric",
  });
};
const getReleaseDateLabel = (row) => {
  if (row.first_half?.release_date && !row.second_half?.release_date)
    return "1st half";
  if (row.second_half?.release_date) return "2nd half";
  return "Release";
};
const getReleaseDateDisplay = (row) => {
  const f = row.first_half?.release_date,
    s = row.second_half?.release_date;
  if (f && s) return `${formatDate(f)} / ${formatDate(s)}`;
  return formatDate(f || s || row.release_date) || "—";
};
const formatCurrency = (value, opts = {}) => {
  const a = Number(value) || 0;
  const max =
    typeof opts.maximumFractionDigits === "number"
      ? opts.maximumFractionDigits
      : 2;
  const min =
    typeof opts.minimumFractionDigits === "number"
      ? Math.min(opts.minimumFractionDigits, max)
      : Math.min(2, max);
  return new Intl.NumberFormat("en-PH", {
    style: "currency",
    currency: "PHP",
    minimumFractionDigits: min,
    maximumFractionDigits: max,
  }).format(a);
};
const handleSearch = () => { currentPage.value = 1; };
const handleFilter = () => { currentPage.value = 1; };
const refreshData = () => emit("refresh");
const handleView = (row) => emit("view", row);

const extractSummaryTotals = (summary) => {
  return (summary?.payrolls || []).reduce(
    (acc, p) => {
      const n = Number(p?.net_pay ?? 0) || 0;
      if (!isNaN(n)) {
        acc.totalNetPay += n;
        acc.totalEmployees += 1;
      }
      return acc;
    },
    { totalNetPay: 0, totalEmployees: 0 },
  );
};
const fetchNetStatsForPeriods = async (periods = []) => {
  const posted = periods.filter(
    (p) => p?.posted && Array.isArray(p.period_ids) && p.period_ids.length,
  );
  if (!posted.length) {
    periodNetStats.value = {};
    totalNetPay.value = 0;
    return;
  }
  totalNetPayLoading.value = true;
  try {
    const entries = await Promise.all(
      posted.flatMap((m) =>
        m.period_ids.map(async (id) => {
          try {
            const res = await payrollProcessApi.getPayrollSummary(id);
            const t = extractSummaryTotals(res?.data?.data || {});
            return t.totalEmployees > 0 ? [id, t] : [id, null];
          } catch {
            return [id, null];
          }
        }),
      ),
    );
    const stats = {};
    let agg = 0;
    entries.forEach(([id, d]) => {
      if (d) {
        stats[id] = d;
        agg += d.totalNetPay;
      }
    });
    periodNetStats.value = stats;
    totalNetPay.value = agg;
  } finally {
    totalNetPayLoading.value = false;
  }
};
watch(
  () => props.data,
  (p) => fetchNetStatsForPeriods(p || []),
  { immediate: true, deep: true },
);
</script>

<style scoped>
.pt-shell {
  display: flex;
  flex-direction: column;
  gap: 0;
}
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
.stat-value.info {
  color: #185fa5;
}
.stat-value.danger {
  color: #a32d2d;
}
.stat-value.mono {
  font-family: monospace;
  font-size: 16px;
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
.btn-ghost {
  background: transparent;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 7px 14px;
  font-size: 13px;
  color: #6b7280;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}
.btn-ghost:hover:not(:disabled) {
  background: #f3f4f6;
}
.btn-ghost:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

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
  background: #f9fafb;
}
td {
  padding: 12px 14px;
  color: #111827;
  vertical-align: middle;
}
.period-name {
  font-weight: 500;
}
.muted {
  color: #6b7280;
  font-size: 13px;
}

.release-dates {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}
.rd-block {
  display: inline-flex;
  flex-direction: column;
  padding: 3px 8px;
  background: #f3f4f6;
  border-radius: 6px;
}
.rd-label {
  font-size: 10px;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: #9ca3af;
}
.rd-val {
  font-size: 12px;
  font-weight: 500;
  color: #374151;
}
.rd-sep {
  color: #d1d5db;
  font-weight: bold;
}

.status-pill {
  display: inline-block;
  padding: 3px 10px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 500;
}
.status-pill.posted {
  background: #e1f5ee;
  color: #085041;
}
.status-pill.partial {
  background: #faeeda;
  color: #633806;
}
.status-pill.unposted {
  background: #faece7;
  color: #712b13;
}

.btn-view {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  background: transparent;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  padding: 5px 12px;
  font-size: 12px;
  color: #374151;
  cursor: pointer;
}
.btn-view:hover {
  background: #f3f4f6;
}

.skeleton-wrap {
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.skeleton-row {
  height: 56px;
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

.pagination {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 0 0;
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
  background: #409eff;
  color: #fff;
  border-color: #409eff;
}
.page-btn:hover:not(.active):not(:disabled) {
  background: #f3f4f6;
}
.page-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}
</style>
