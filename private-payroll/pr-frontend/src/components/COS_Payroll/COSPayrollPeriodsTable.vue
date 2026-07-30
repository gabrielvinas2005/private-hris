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
          {{ formatCurrency(totalNetPayPosted, { maximumFractionDigits: 0 }) }}
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="filter-bar">
      <div class="search-wrap">
        <svg width="14" height="14" viewBox="0 0 14 14" fill="none" class="search-icon">
          <circle cx="6" cy="6" r="4" stroke="currentColor" stroke-width="1.3" />
          <path d="M9.5 9.5l2.5 2.5" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" />
        </svg>
        <input
          v-model="searchQuery"
          class="search-input"
          placeholder="Search payroll periods…"
          @input="handleSearch"
        />
      </div>
      <select v-model="statusFilter" class="filter-select" @change="handleFilter">
        <option value="">All statuses</option>
        <option value="posted">Posted</option>
        <option value="unposted">Unposted</option>
      </select>
      <button class="btn-ghost" :disabled="loading" @click="refreshData">
        <svg width="13" height="13" viewBox="0 0 13 13" fill="none">
          <path d="M2 6.5A4.5 4.5 0 0 1 9.8 3.2" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" />
          <path d="M1.5 1.5v2.5h2.5" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round" />
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
            <th>Employment type</th>
            <th>Start date</th>
            <th>End date</th>
            <th>Release date</th>
            <th style="text-align: center">Status</th>
            <th style="width: 130px"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in filteredData" :key="row.id">
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
                  v-if="row.first_half?.release_date && row.second_half?.release_date"
                >
                  <div class="rd-block">
                    <span class="rd-label">1st half</span>
                    <span class="rd-val">{{ formatDate(row.first_half.release_date) }}</span>
                  </div>
                  <span class="rd-sep">·</span>
                  <div class="rd-block">
                    <span class="rd-label">2nd half</span>
                    <span class="rd-val">{{ formatDate(row.second_half.release_date) }}</span>
                  </div>
                </template>
                <div v-else class="rd-block">
                  <span class="rd-label">{{ getReleaseDateLabel(row) }}</span>
                  <span class="rd-val">{{ getReleaseDateDisplay(row) }}</span>
                </div>
              </div>
            </td>
            <td style="text-align: center">
              <span class="status-pill" :class="row.posted ? 'posted' : 'unposted'">
                {{ row.posted ? "Posted" : "Unposted" }}
              </span>
            </td>
            <td>
              <div class="action-cell">
                <button class="btn-view" @click="handleView(row)">
                  <svg width="13" height="13" viewBox="0 0 13 13" fill="none">
                    <circle cx="6.5" cy="6.5" r="2.5" stroke="currentColor" stroke-width="1.2" />
                    <path d="M1 6.5C2.5 3 4.5 1.5 6.5 1.5S10.5 3 12 6.5c-1.5 3.5-3.5 5-5.5 5S2.5 10 1 6.5z" stroke="currentColor" stroke-width="1.2" />
                  </svg>
                  View
                </button>
                <button
                  v-if="row.posted"
                  class="btn-unpost"
                  :disabled="!!processingPeriodId"
                  @click="handleUnpost(row)"
                >
                  <svg width="13" height="13" viewBox="0 0 13 13" fill="none">
                    <path d="M11 2L2 11M2 2l9 9" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" />
                  </svg>
                  Unpost
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
      <div v-else class="empty-state">
        <svg width="36" height="36" viewBox="0 0 36 36" fill="none">
          <rect x="5" y="7" width="26" height="22" rx="3" stroke="#d1d5db" stroke-width="1.5" />
          <path d="M11 13h14M11 18h9" stroke="#d1d5db" stroke-width="1.5" stroke-linecap="round" />
        </svg>
        <p>No COS payroll periods found</p>
      </div>
    </div>
    <div v-else class="skeleton-wrap">
      <div v-for="i in 4" :key="i" class="skeleton-row" />
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from "vue";

const props = defineProps({
  data: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  processingPeriodId: { type: [Number, String], default: null },
});

const emit = defineEmits(["view", "unpost", "refresh"]);

const searchQuery = ref("");
const statusFilter = ref("");

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

const totalPeriods = computed(() => props.data.length);
const postedPeriods = computed(() => props.data.filter((r) => r.posted).length);
const unpostedPeriods = computed(() => props.data.filter((r) => !r.posted).length);
const totalNetPayPosted = computed(() =>
  props.data
    .filter((r) => r.posted)
    .reduce((sum, r) => sum + (Number(r.total_net_pay) || 0), 0),
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
  if (row.first_half?.release_date && !row.second_half?.release_date) return "1st half";
  if (row.second_half?.release_date) return "2nd half";
  return "Release";
};

const getReleaseDateDisplay = (row) => {
  const f = row.first_half?.release_date;
  const s = row.second_half?.release_date;
  if (f && s) return `${formatDate(f)} / ${formatDate(s)}`;
  return formatDate(f || s || row.release_date) || "—";
};

const formatCurrency = (value, opts = {}) => {
  const a = Number(value) || 0;
  const max = typeof opts.maximumFractionDigits === "number" ? opts.maximumFractionDigits : 2;
  const min = typeof opts.minimumFractionDigits === "number"
    ? Math.min(opts.minimumFractionDigits, max)
    : Math.min(2, max);
  return new Intl.NumberFormat("en-PH", {
    style: "currency",
    currency: "PHP",
    minimumFractionDigits: min,
    maximumFractionDigits: max,
  }).format(a);
};

const handleSearch = () => {};
const handleFilter = () => {};
const refreshData = () => emit("refresh");
const handleView = (row) => emit("view", row);
const handleUnpost = (row) => emit("unpost", row);
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
  box-sizing: border-box;
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
.status-pill.unposted {
  background: #faece7;
  color: #712b13;
}

.action-cell {
  display: flex;
  align-items: center;
  gap: 6px;
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
  white-space: nowrap;
}
.btn-view:hover {
  background: #f3f4f6;
}
.btn-unpost {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  background: transparent;
  border: 1px solid #fca5a5;
  border-radius: 6px;
  padding: 5px 12px;
  font-size: 12px;
  color: #b91c1c;
  cursor: pointer;
  white-space: nowrap;
}
.btn-unpost:hover:not(:disabled) {
  background: #fef2f2;
}
.btn-unpost:disabled {
  opacity: 0.4;
  cursor: not-allowed;
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
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
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
</style>
