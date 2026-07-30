<template>
  <div class="cos-entries-shell">
    <!-- Section title -->
    <!-- <div class="entries-header" v-if="periodLabel">
      <h4 class="entries-title">
        COS Payroll entries —
        <span class="period-highlight">{{ periodLabel }}</span>
      </h4>
    </div> -->

    <!-- Skeleton -->
    <div v-if="loading" class="skeleton-wrap">
      <div v-for="i in 5" :key="i" class="skeleton-row" />
    </div>

    <!-- Table -->
    <div v-else-if="employees.length > 0" class="table-wrap">
      <div class="table-scroll">
        <table>
          <thead>
            <tr>
              <th>Employee</th>
              <th>Employee no.</th>
              <th>Attendance period</th>
              <th class="num-col">Gross</th>
              <th class="num-col">Absent</th>
              <th class="num-col">Premium</th>
              <th class="num-col">NVAT</th>
              <th class="num-col">EWT</th>
              <th class="num-col">Deductions</th>
              <th class="num-col">Net pay</th>
              <th>Status</th>
              <th style="width: 130px"></th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="row in employees"
              :key="row.employeeId"
            >
              <td class="emp-name">{{ row.employeeName || "—" }}</td>
              <td class="muted">{{ row.employeeNo || "—" }}</td>
              <td class="muted">{{ row.workDate || "—" }}</td>
              <td class="num-td">{{ fc(row.gross) }}</td>
              <td class="num-td">{{ fc(row.attendanceTotal) }}</td>
              <td class="num-td">{{ fc(row.premium) }}</td>
              <td class="num-td">{{ fc(row.nvat) }}</td>
              <td class="num-td">{{ fc(row.ewt) }}</td>
              <td class="num-td">{{ fc(row.totalDeduction) }}</td>
              <td class="num-td net-pay">{{ fc(row.netPay) }}</td>
              <td>
                <span
                  class="status-pill"
                  :class="approvalClass(row)"
                >
                  {{ row.taskApprovalLabel || "No task" }}
                </span>
              </td>
              <td>
                <button class="btn-view" @click="handleViewDetails(row)">
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
                  Work details
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Empty -->
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
      <p>No COS employees with valid contract and approved work details for this period.</p>
    </div>
  </div>
</template>

<script setup>
defineProps({
  employees: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  periodLabel: { type: String, default: "" },
});

const emit = defineEmits(["view-details"]);

const fc = (value) => {
  const a = Number(value) || 0;
  return new Intl.NumberFormat("en-PH", {
    style: "currency",
    currency: "PHP",
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(a);
};

const handleViewDetails = (employee) => emit("view-details", employee);

const approvalClass = (row) => {
  if (row.isTaskApproved) return "approved";
  if (row.taskApprovalStatus === "pending") return "pending";
  return "missing";
};
</script>

<style scoped>
.cos-entries-shell {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.entries-header {
  margin-bottom: 4px;
}
.entries-title {
  margin: 0;
  font-size: 14px;
  font-weight: 500;
  color: #374151;
}
.period-highlight {
  color: #185fa5;
  font-weight: 600;
}

.table-wrap {
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  overflow: hidden;
}
.table-scroll {
  overflow-x: auto;
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
thead th.num-col {
  text-align: right;
}
tbody td.net-pay {
  color: #085041;
  font-weight: 600;
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
.emp-name {
  font-weight: 500;
}
.muted {
  color: #6b7280;
  font-size: 13px;
}
.num-td {
  text-align: right;
  font-family: monospace;
  font-size: 13px;
  color: #111827;
}

.status-pill {
  display: inline-block;
  padding: 3px 10px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 500;
}
.status-pill.approved {
  background: #e1f5ee;
  color: #085041;
}
.status-pill.pending {
  background: #fef3c7;
  color: #92400e;
}
.status-pill.missing {
  background: #f3f4f6;
  color: #6b7280;
}
.status-pill.disapproved {
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
  white-space: nowrap;
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
</style>
