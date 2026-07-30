<template>
  <div class="schedule-shell">
    <!-- Summary cards -->
    <div class="summary-grid">
      <div class="stat-card">
        <div class="stat-label">Total schedules</div>
        <div class="stat-value">{{ totalSchedules }}</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Active schedules</div>
        <div class="stat-value info">{{ activeSchedules }}</div>
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
          placeholder="Search by interval, period, or employment type…"
        />
      </div>
      <select
        v-model="filters.payroll_interval_type_id"
        class="filter-select"
        @change="handleFilterChange"
      >
        <option value="">All intervals</option>
        <option
          v-for="item in dropdownData.PayrollInterval"
          :key="item.id"
          :value="item.id"
        >
          {{ item.name }}
        </option>
      </select>
      <select
        v-model="filters.employment_type_id"
        class="filter-select"
        @change="handleFilterChange"
      >
        <option value="">All employment types</option>
        <option
          v-for="item in dropdownData.EmploymentType"
          :key="item.id"
          :value="item.id"
        >
          {{ item.name }}
        </option>
      </select>
      <span class="records-count">{{ filteredRows.length }} records</span>
    </div>

    <!-- Table -->
    <div class="table-wrap" v-if="!loading || filteredRows.length > 0">
      <table v-if="filteredRows.length > 0">
        <thead>
          <tr>
            <th>Payroll interval</th>
            <th>Period type</th>
            <th>Employment type</th>
            <th>Gov't contributions</th>
            <th>Income items</th>
            <th>Deduction items</th>
            <th style="width: 110px"></th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="row in filteredRows"
            :key="row.id ?? row.payroll_interval_name"
          >
            <!-- Interval -->
            <td>
              <div class="interval-cell">
                <span class="interval-dot"></span>
                {{ row.payroll_interval_name }}
              </div>
            </td>

            <!-- Period type -->
            <td>
              <div class="period-tags">
                <template
                  v-if="
                    Array.isArray(row.merged_ids) && row.merged_ids.length > 1
                  "
                >
                  <span class="period-tag first">1st half</span>
                  <span class="period-tag second">2nd half</span>
                </template>
                <template v-else>
                  <span
                    class="period-tag"
                    :class="periodTagClass(row.payroll_period_name)"
                  >
                    {{ formatPeriodType(row.payroll_period_name) }}
                  </span>
                </template>
              </div>
            </td>

            <!-- Employment type -->
            <td class="muted-cell">{{ row.employment_type_name }}</td>

            <!-- Contributions -->
            <td>
              <div class="contrib-pills">
                <span v-if="row.sss" class="contrib sss">SSS</span>
                <span v-if="row.gsis" class="contrib gsis">GSIS</span>
                <span v-if="row.tax" class="contrib tax">Tax</span>
                <span v-if="row.philhealth" class="contrib phil"
                  >PhilHealth</span
                >
                <span v-if="row.pagibig" class="contrib pag">Pag-IBIG</span>
                <span
                  v-if="
                    !row.sss &&
                    !row.gsis &&
                    !row.tax &&
                    !row.philhealth &&
                    !row.pagibig
                  "
                  class="none-text"
                  >None</span
                >
              </div>
            </td>

            <!-- Income items -->
            <td>
              <div class="count-pills">
                <button
                  class="count-pill primary"
                  @click="viewIncomeItems(row)"
                >
                  <template v-if="row.first_income_count !== undefined"
                    >1st: {{ row.first_income_count }}</template
                  >
                  <template v-else>{{ row.income_count }} items</template>
                </button>
                <button
                  v-if="row.second_income_count !== undefined"
                  class="count-pill second"
                  @click="viewIncomeItems(row)"
                >
                  2nd: {{ row.second_income_count }}
                </button>
              </div>
            </td>

            <!-- Deduction items -->
            <td>
              <div class="count-pills">
                <button
                  class="count-pill primary"
                  @click="viewDeductionItems(row)"
                >
                  <template v-if="row.first_deduction_count !== undefined"
                    >1st: {{ row.first_deduction_count }}</template
                  >
                  <template v-else>{{ row.deduction_count }} items</template>
                </button>
                <button
                  v-if="row.second_deduction_count !== undefined"
                  class="count-pill second"
                  @click="viewDeductionItems(row)"
                >
                  2nd: {{ row.second_deduction_count }}
                </button>
              </div>
            </td>

            <!-- Actions -->
            <td>
              <div class="actions-cell">
                <button class="act-edit" @click="editSchedule(row)">
                  Edit
                </button>
                <button class="act-delete" @click="deleteSchedule(row)">
                  <svg width="13" height="13" viewBox="0 0 13 13" fill="none">
                    <path
                      d="M2 3.5h9M5 3.5V2.5a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 .5.5v1M10.5 3.5l-.6 7a.5.5 0 0 1-.5.5H3.6a.5.5 0 0 1-.5-.5l-.6-7"
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
        <p>No payroll item schedules found</p>
      </div>
    </div>

    <!-- Loading skeleton -->
    <div v-else class="skeleton-wrap">
      <div v-for="i in 4" :key="i" class="skeleton-row" />
    </div>

    <!-- Item details modal -->
    <Teleport to="body">
      <Transition name="fade">
        <div
          v-if="itemDetailsVisible"
          class="modal-backdrop"
          @click.self="itemDetailsVisible = false"
        >
          <div class="mini-modal">
            <div class="mini-modal-header">
              <span class="mini-modal-title"
                >{{ itemType }} items —
                {{ selectedSchedule?.payroll_interval_name }}</span
              >
              <button class="close-btn" @click="itemDetailsVisible = false">
                <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                  <path
                    d="M2 2l10 10M12 2L2 12"
                    stroke="currentColor"
                    stroke-width="1.4"
                    stroke-linecap="round"
                  />
                </svg>
              </button>
            </div>
            <div class="mini-modal-body">
              <div
                v-for="item in itemDetails"
                :key="item.id"
                class="detail-row"
              >
                <span>{{ item.name }}</span>
                <span
                  class="detail-status"
                  :class="item.active ? 'status-active' : 'status-inactive'"
                >
                  {{ item.active ? "Active" : "Inactive" }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Delete confirm modal -->
    <Teleport to="body">
      <Transition name="fade">
        <div
          v-if="deleteDialogVisible"
          class="modal-backdrop"
          @click.self="deleteDialogVisible = false"
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
            <p class="confirm-title">Delete schedule?</p>
            <p class="confirm-body">
              <strong
                >{{ scheduleToDelete?.payroll_interval_name }} ·
                {{ scheduleToDelete?.employment_type_name }}</strong
              >
              will be permanently removed.
            </p>
            <div class="confirm-actions">
              <button class="btn-ghost" @click="deleteDialogVisible = false">
                Cancel
              </button>
              <button class="btn-danger" @click="confirmDelete">Delete</button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <div class="mt-2"><slot name="footer" /></div>
  </div>
</template>

<script setup>
import { usePayrollItemScheduleTable } from "../../Composables/usePayrollItemSchedule";

const emit = defineEmits(["edit", "delete"]);

const {
  loading,
  scheduleData,
  dropdownData,
  filters,
  searchQuery,
  showColumnDialog,
  visibleColumns,
  itemDetailsVisible,
  itemDetails,
  itemType,
  selectedSchedule,
  deleteDialogVisible,
  scheduleToDelete,
  totalSchedules,
  activeSchedules,
  monthlySchedules,
  halfMonthSchedules,
  filteredRows,
  loadDropdownData,
  loadSchedules,
  resetFilters,
  handleFilterChange,
  getColumnLabel,
  handlePrint,
  handleExcel,
  handlePDF,
  toggleColumnVisibility,
  viewIncomeItems,
  viewDeductionItems,
  editSchedule,
  deleteSchedule,
  confirmDelete,
} = usePayrollItemScheduleTable(emit);

const formatPeriodType = (name) => {
  if (!name) return "—";
  return String(name)
    .trim()
    .split(/[\s\-_]+/)
    .map((w) => w.charAt(0).toUpperCase() + w.slice(1).toLowerCase())
    .join(" ");
};
const periodTagClass = (name) => {
  const s = String(name || "").toLowerCase();
  if (s.includes("first")) return "first";
  if (s.includes("second")) return "second";
  return "monthly";
};

defineExpose({ loadSchedules });
</script>

<style scoped>
.schedule-shell {
  display: flex;
  flex-direction: column;
  gap: 0;
}

/* summary */
.summary-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 180px));
  gap: 10px;
  margin-bottom: 20px;
  align-items: center;
  justify-content: center;
  display: flex;
}
.stat-card {
  background: #f8f9fa;
  border-radius: 10px;
  padding: 14px 16px;
  text-align: center;
  width: 250px;
}
.stat-label {
  font-size: 12px;
  color: #6b7280;
  margin-bottom: 5px;
}
.stat-value {
  font-size: 24px;
  font-weight: 500;
  color: #111827;
}
.stat-value.info {
  color: #185fa5;
}

/* filter bar */
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
  min-width: 150px;
}
.filter-select:focus {
  border-color: #6b7280;
}
.records-count {
  font-size: 12px;
  color: #9ca3af;
  margin-left: auto;
  white-space: nowrap;
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
  background: #f9fafb;
}
td {
  padding: 11px 14px;
  color: #111827;
  vertical-align: middle;
}
.muted-cell {
  color: #6b7280;
}

/* interval */
.interval-cell {
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 500;
}
.interval-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #185fa5;
  flex-shrink: 0;
}

/* period tags */
.period-tags {
  display: flex;
  gap: 5px;
  flex-wrap: wrap;
}
.period-tag {
  display: inline-block;
  padding: 3px 9px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 500;
}
.period-tag.monthly {
  background: #e6f1fb;
  color: #0c447c;
}
.period-tag.first {
  background: #e6f1fb;
  color: #0c447c;
}
.period-tag.second {
  background: #faeeda;
  color: #633806;
}

/* contributions */
.contrib-pills {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
}
.contrib {
  display: inline-block;
  padding: 3px 7px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 500;
}
.contrib.sss {
  background: #e6f1fb;
  color: #0c447c;
}
.contrib.gsis {
  background: #e1f5ee;
  color: #085041;
}
.contrib.tax {
  background: #faeeda;
  color: #633806;
}
.contrib.phil {
  background: #eeedfe;
  color: #3c3489;
}
.contrib.pag {
  background: #faece7;
  color: #712b13;
}
.none-text {
  font-size: 12px;
  color: #d1d5db;
  font-style: italic;
}

/* count pills */
.count-pills {
  display: flex;
  gap: 5px;
  flex-wrap: wrap;
}
.count-pill {
  display: inline-block;
  padding: 3px 9px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 500;
  cursor: pointer;
  border: none;
}
.count-pill.primary {
  background: #e6f1fb;
  color: #0c447c;
}
.count-pill.primary:hover {
  background: #b5d4f4;
}
.count-pill.second {
  background: #faeeda;
  color: #633806;
}
.count-pill.second:hover {
  background: #fac775;
}

/* actions */
.actions-cell {
  display: flex;
  align-items: center;
  gap: 6px;
}
.act-edit {
  background: transparent;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  padding: 4px 10px;
  font-size: 12px;
  color: #374151;
  cursor: pointer;
}
.act-edit:hover {
  background: #f3f4f6;
}
.act-delete {
  background: transparent;
  border: none;
  padding: 5px 6px;
  border-radius: 6px;
  color: #9ca3af;
  cursor: pointer;
  display: flex;
  align-items: center;
}
.act-delete:hover {
  background: #fee2e2;
  color: #a32d2d;
}

/* skeleton */
.skeleton-wrap {
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.skeleton-row {
  height: 48px;
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

/* modals */
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

.mini-modal {
  background: #fff;
  border-radius: 12px;
  width: 100%;
  max-width: 380px;
  max-height: 70vh;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}
.mini-modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 16px;
  border-bottom: 1px solid #f3f4f6;
}
.mini-modal-title {
  font-size: 14px;
  font-weight: 500;
  color: #111827;
}
.mini-modal-body {
  overflow-y: auto;
  padding: 8px 0;
}
.detail-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 8px 16px;
  border-bottom: 1px solid #f9fafb;
  font-size: 13px;
}
.detail-row:last-child {
  border-bottom: none;
}
.detail-status {
  font-size: 11px;
  font-weight: 500;
  padding: 2px 8px;
  border-radius: 4px;
}
.status-active {
  background: #e1f5ee;
  color: #085041;
}
.status-inactive {
  background: #f3f4f6;
  color: #9ca3af;
}

.confirm-modal {
  background: #fff;
  border-radius: 14px;
  padding: 28px 24px;
  width: 100%;
  max-width: 360px;
  text-align: center;
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

.close-btn {
  background: transparent;
  border: none;
  padding: 4px;
  border-radius: 6px;
  color: #9ca3af;
  cursor: pointer;
  display: flex;
}
.close-btn:hover {
  background: #f3f4f6;
  color: #374151;
}
.btn-ghost {
  background: transparent;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 8px 16px;
  font-size: 13px;
  color: #6b7280;
  cursor: pointer;
}
.btn-ghost:hover {
  background: #f3f4f6;
}
.btn-danger {
  background: #a32d2d;
  color: #fff;
  border: none;
  border-radius: 8px;
  padding: 8px 20px;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
}
.btn-danger:hover {
  background: #7f2020;
}

.mt-2 {
  margin-top: 8px;
}
</style>
