<template>
  <div class="pt-shell">
    <!-- Summary cards -->
    <div class="summary-grid">
      <div class="stat-card">
        <div class="stat-label">Total periods</div>
        <div class="stat-value">{{ totalPeriods }}</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Active periods</div>
        <div class="stat-value info">{{ activePeriods }}</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Inactive periods</div>
        <div class="stat-value muted">{{ inactivePeriods }}</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Posted periods</div>
        <div class="stat-value green">{{ postedPeriods }}</div>
      </div>
    </div>

    <!-- Filter bar -->
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
          placeholder="Search periods by interval, cut-off, or dates…"
        />
      </div>
      <select v-model="statusFilter" class="filter-select">
        <option value="">All statuses</option>
        <option value="YES">Active</option>
        <option value="NO">Inactive</option>
      </select>
      <select v-model="postedFilter" class="filter-select">
        <option value="">All posted</option>
        <option value="YES">Posted</option>
        <option value="NO">Unposted</option>
      </select>
      <button class="btn-ghost" :disabled="loading" @click="$emit('refresh')">
        <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
          <path
            d="M1.5 6A4.5 4.5 0 0 1 9.3 2.7"
            stroke="currentColor"
            stroke-width="1.2"
            stroke-linecap="round"
          />
          <path
            d="M1 1v3h3"
            stroke="currentColor"
            stroke-width="1.2"
            stroke-linecap="round"
            stroke-linejoin="round"
          />
        </svg>
        {{ loading ? "Loading…" : "Refresh" }}
      </button>
      <div class="export-wrap" ref="exportRef">
        <button class="export-btn" @click="exportOpen = !exportOpen">
          <svg width="13" height="13" viewBox="0 0 13 13" fill="none">
            <path
              d="M1.5 9v2.5h10V9M6.5 1v7M4 5l2.5 3L9 5"
              stroke="currentColor"
              stroke-width="1.2"
              stroke-linecap="round"
              stroke-linejoin="round"
            />
          </svg>
          Export
          <svg width="10" height="10" viewBox="0 0 10 10" fill="none">
            <path
              d="M2 4l3 3 3-3"
              stroke="currentColor"
              stroke-width="1.2"
              stroke-linecap="round"
            />
          </svg>
        </button>
        <div v-if="exportOpen" class="export-dropdown">
          <button class="export-item" @click="handlePrint(); exportOpen = false">Print</button>
          <button class="export-item" @click="handleExcel(); exportOpen = false">Excel</button>
          <button class="export-item" @click="handlePDF(); exportOpen = false">PDF</button>
        </div>
      </div>
    </div>

    <!-- Table -->
    <div class="table-wrap" v-if="!loading || filteredRows.length > 0">
      <table v-if="filteredRows.length > 0">
        <thead>
          <tr>
            <th>Payroll period</th>
            <th>Employment types</th>
            <th>Start date</th>
            <th>End date</th>
            <th>Release date</th>
            <th style="text-align: center">Status</th>
            <th style="width: 120px"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in paginatedRows" :key="row.id">
            <td class="period-name">{{ getPayrollPeriodLabel(row) }}</td>
            <td class="muted">
              {{
                Array.isArray(row.employment_types)
                  ? row.employment_types.map((et) => et.name || et).join(", ")
                  : row.employment_types || "—"
              }}
            </td>
            <td class="muted">
              {{ formatDateShort(row.attendance_start_date) }}
            </td>
            <td class="muted">
              {{ formatDateShort(row.attendance_end_date) }}
            </td>
            <td>
              <div class="release-dates">
                <template v-if="row.periods?.length > 1">
                  <div
                    v-for="(p, idx) in sortedReleasePeriods(row)"
                    :key="p.id"
                    class="rd-block-wrap"
                  >
                    <span v-if="idx > 0" class="rd-sep">·</span>
                    <div class="rd-block">
                      <span class="rd-label">{{
                        idx === 0 ? "1st half" : "2nd half"
                      }}</span>
                      <span class="rd-val">{{
                        formatDateShort(p.release_date)
                      }}</span>
                    </div>
                  </div>
                </template>
                <template
                  v-else-if="
                    String(row.periods?.[0]?.payroll_cutoff || '')
                      .toLowerCase()
                      .includes('monthly')
                  "
                >
                  <div class="rd-block-wrap">
                    <div class="rd-block">
                      <span class="rd-label">1st half</span
                      ><span class="rd-val">{{
                        formatDateShort(row.periods?.[0]?.payroll_start_date)
                      }}</span>
                    </div>
                    <span class="rd-sep">·</span>
                    <div class="rd-block">
                      <span class="rd-label">2nd half</span
                      ><span class="rd-val">{{
                        formatDateShort(row.periods?.[0]?.payroll_end_date)
                      }}</span>
                    </div>
                  </div>
                </template>
                <template v-else>
                  <div class="rd-block">
                    <span class="rd-label">Release</span
                    ><span class="rd-val">{{
                      getReleaseDateDisplay(row)
                    }}</span>
                  </div>
                </template>
              </div>
            </td>
            <td style="text-align: center">
              <span
                class="status-pill"
                :class="
                  row.posted === 'YES' || row.posted === true
                    ? 'posted'
                    : 'unposted'
                "
              >
                {{
                  row.posted === "YES" || row.posted === true
                    ? "Posted"
                    : "Unposted"
                }}
              </span>
            </td>
            <td>
              <div class="actions-cell">
                <button class="btn-view" @click="onViewClick(row)">
                  <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
                    <circle
                      cx="6"
                      cy="6"
                      r="2.5"
                      stroke="currentColor"
                      stroke-width="1.2"
                    />
                    <path
                      d="M1 6c1.3-3 3-4.5 5-4.5S10.7 3 12 6c-1.3 3-3 4.5-5 4.5S2.3 9 1 6z"
                      stroke="currentColor"
                      stroke-width="1.2"
                    />
                  </svg>
                  View
                </button>
                <button class="btn-delete" @click="handleDelete(row)">
                  <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
                    <path
                      d="M1.5 3h9M4.5 3V2a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 .5.5v1M10 3l-.5 6.5a.5.5 0 0 1-.5.5H3a.5.5 0 0 1-.5-.5L2 3"
                      stroke="currentColor"
                      stroke-width="1.2"
                      stroke-linecap="round"
                    />
                  </svg>
                </button>
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
        <p>No payroll periods found</p>
      </div>
    </div>
    <div v-else class="skeleton-wrap">
      <div v-for="i in 5" :key="i" class="skeleton-row" />
    </div>

    <!-- Pagination -->
    <div v-if="filteredRows.length > 0" class="pagination">
      <span class="page-info">{{ filteredRows.length }} results</span>
      <div class="page-btns">
        <button
          class="page-btn"
          :disabled="currentPage === 1"
          @click="handleCurrentPageChange(currentPage - 1)"
        >
          &#8249;
        </button>
        <button
          v-for="p in Math.ceil(filteredRows.length / pageSize)"
          :key="p"
          class="page-btn"
          :class="{ active: p === currentPage }"
          @click="handleCurrentPageChange(p)"
        >
          {{ p }}
        </button>
        <button
          class="page-btn"
          :disabled="currentPage === Math.ceil(filteredRows.length / pageSize)"
          @click="handleCurrentPageChange(currentPage + 1)"
        >
          &#8250;
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from "vue";
import { usePayrollPeriodTable } from "../../Composables/usePayrollPeriod";

const exportOpen = ref(false);
const exportRef = ref(null);

function onClickOutside(e) {
  if (exportRef.value && !exportRef.value.contains(e.target)) {
    exportOpen.value = false;
  }
}
onMounted(() => document.addEventListener("mousedown", onClickOutside));
onBeforeUnmount(() => document.removeEventListener("mousedown", onClickOutside));

const props = defineProps({
  rows: { type: Array, default: () => [] },
  flatRowCount: { type: Number, default: null },
  loading: { type: Boolean, default: false },
});
const emit = defineEmits(["view", "edit", "deleted", "refresh"]);

const {
  searchQuery,
  statusFilter,
  postedFilter,
  totalPeriods,
  activePeriods,
  inactivePeriods,
  postedPeriods,
  filteredRows,
  paginatedRows,
  currentPage,
  pageSize,
  handlePageSizeChange,
  handleCurrentPageChange,
  handlePrint,
  handleExcel,
  handlePDF,
  handleDelete: deleteHandler,
} = usePayrollPeriodTable(props);

const handleDelete = async (row) => {
  try {
    await deleteHandler(row);
    emit("deleted");
  } catch {}
};

function getPayrollPeriodLabel(row) {
  if (row.label) return row.label;
  const interval = row.payroll_interval || "Payroll";
  const dateStr = row.payroll_date || row.release_date;
  if (dateStr) {
    try {
      const d = new Date(dateStr);
      return `${interval} (${d.toLocaleDateString("en-US", { month: "long", year: "numeric" })})`;
    } catch {
      return `${interval} (${dateStr})`;
    }
  }
  return interval;
}

function onViewClick(row) {
  if (row.periods && row.periods.length) emit("view", row);
  else emit("edit", row);
}

function formatDateShort(d) {
  if (!d) return "—";
  return new Date(d).toLocaleDateString("en-US", {
    year: "numeric",
    month: "short",
    day: "numeric",
  });
}
function sortedReleasePeriods(row) {
  if (!row.periods?.length) return [];
  return [...row.periods].sort((a, b) =>
    (a.attendance_start_date || "").localeCompare(
      b.attendance_start_date || "",
    ),
  );
}
function getReleaseDateDisplay(row) {
  if (row.periods && row.periods.length > 1)
    return sortedReleasePeriods(row)
      .map((p) => formatDateShort(p.release_date))
      .join(" / ");
  return formatDateShort(row.release_date);
}
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
.stat-value.muted {
  color: #9ca3af;
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
  min-width: 130px;
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
.export-wrap {
  position: relative;
}
.export-btn {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  background: transparent;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 6px 12px;
  font-size: 12px;
  color: #374151;
  cursor: pointer;
  white-space: nowrap;
}
.export-btn:hover {
  background: #f3f4f6;
}
.export-dropdown {
  position: absolute;
  right: 0;
  top: calc(100% + 4px);
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 4px;
  z-index: 100;
  min-width: 120px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}
.export-item {
  display: block;
  width: 100%;
  text-align: left;
  padding: 7px 12px;
  font-size: 12px;
  color: #374151;
  background: transparent;
  border: none;
  border-radius: 5px;
  cursor: pointer;
}
.export-item:hover {
  background: #f3f4f6;
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
.rd-block-wrap {
  display: inline-flex;
  align-items: center;
  gap: 8px;
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

.actions-cell {
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
}
.btn-view:hover {
  background: #f3f4f6;
}
.btn-delete {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: transparent;
  border: none;
  padding: 6px;
  border-radius: 6px;
  color: #9ca3af;
  cursor: pointer;
}
.btn-delete:hover {
  background: #fee2e2;
  color: #a32d2d;
}

.skeleton-wrap {
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.skeleton-row {
  height: 52px;
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
