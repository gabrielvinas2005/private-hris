<template>
  <Teleport to="body">
    <Transition name="fade">
      <div v-if="visible" class="modal-backdrop" @click.self="handleClose">
        <div class="modal-card">
          <div class="modal-header">
            <div>
              <h3 class="modal-title">Employee leave earned details</h3>
              <p class="modal-sub">
                {{ payrollPeriod }} — leave credits earned for this payroll
                period
              </p>
            </div>
            <button class="close-btn" @click="handleClose">
              <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                <path
                  d="M2 2l10 10M12 2L2 12"
                  stroke="currentColor"
                  stroke-width="1.5"
                  stroke-linecap="round"
                />
              </svg>
            </button>
          </div>

          <div class="modal-body">
            <!-- Summary cards -->
            <div class="stat-grid">
              <div class="stat-card">
                <div class="stat-label">Total employees</div>
                <div class="stat-value">{{ leaveData.length }}</div>
              </div>
              <div class="stat-card">
                <div class="stat-label">Total vacation leave</div>
                <div class="stat-value green">
                  {{ totalVacationLeave.toFixed(3) }}
                </div>
              </div>
              <div class="stat-card">
                <div class="stat-label">Total sick leave</div>
                <div class="stat-value amber">
                  {{ totalSickLeave.toFixed(3) }}
                </div>
              </div>
              <div class="stat-card">
                <div class="stat-label">Total leave days</div>
                <div class="stat-value purple">
                  {{ totalLeaveDays.toFixed(3) }}
                </div>
              </div>
            </div>

            <!-- Filters -->
            <div class="filter-bar">
              <div class="search-wrap">
                <svg
                  width="13"
                  height="13"
                  viewBox="0 0 13 13"
                  fill="none"
                  class="search-icon"
                >
                  <circle
                    cx="5.5"
                    cy="5.5"
                    r="3.5"
                    stroke="currentColor"
                    stroke-width="1.2"
                  />
                  <path
                    d="M8.5 8.5l2.5 2.5"
                    stroke="currentColor"
                    stroke-width="1.2"
                    stroke-linecap="round"
                  />
                </svg>
                <input
                  v-model="searchQuery"
                  class="search-input"
                  placeholder="Search employees…"
                />
              </div>
              <select v-model="departmentFilter" class="filter-select">
                <option value="">All departments</option>
                <option v-for="d in departments" :key="d" :value="d">
                  {{ d }}
                </option>
              </select>
              <div class="action-group">
                <button class="btn-ghost" @click="handlePrint">Print</button>
                <button class="btn-ghost" @click="handleExport">
                  Export
                </button>
                <button
                  class="btn-ghost"
                  :disabled="loading || dataLoading"
                  @click="handleRefresh"
                >
                  {{ dataLoading ? "Refreshing..." : "Refresh" }}
                </button>
              </div>
            </div>

            <!-- Table -->
            <div class="table-wrap">
              <table>
                <thead>
                  <tr>
                    <th style="width: 110px">Employee no.</th>
                    <th>Name</th>
                    <th>Department</th>
                    <th class="num-col">Days</th>
                    <th class="num-col">Vacation leave</th>
                    <th class="num-col">Sick leave</th>
                    <th class="num-col">Total leave</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="emp in filteredLeaveData" :key="emp.employee_no">
                    <td class="mono muted">{{ emp.employee_no }}</td>
                    <td>
                      <div class="emp-cell">
                        <div
                          class="avatar"
                          :style="{
                            background: avatarBg(emp.name),
                            color: avatarFg(emp.name),
                          }"
                        >
                          {{ getInitials(emp.name) }}
                        </div>
                        <div>
                          <div class="emp-name">{{ emp.name }}</div>
                          <div class="emp-pos">{{ emp.position }}</div>
                        </div>
                      </div>
                    </td>
                    <td class="muted">{{ emp.department }}</td>
                    <td class="num-td info">{{ emp.days.toFixed(2) }}</td>
                    <td class="num-td green">
                      {{ emp.vacation_leave.toFixed(3) }}
                    </td>
                    <td class="num-td amber">
                      {{ emp.sick_leave.toFixed(3) }}
                    </td>
                    <td class="num-td purple">
                      {{ (emp.vacation_leave + emp.sick_leave).toFixed(3) }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div class="modal-footer">
            <button class="btn-ghost" @click="handleClose">Close</button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch } from "vue";
import { ElMessage } from "element-plus";
import api from "@/services/api";

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  payrollPeriod: { type: String, default: "" },
  payrollPeriodId: { type: [String, Number], default: null },
  loading: { type: Boolean, default: false },
});
const emit = defineEmits(["update:modelValue", "refresh", "print", "export"]);
const visible = computed({
  get: () => props.modelValue,
  set: (v) => emit("update:modelValue", v),
});

const searchQuery = ref("");
const departmentFilter = ref("");
const leaveData = ref([]);
const dataLoading = ref(false);

const parseNum = (v) => {
  const n = Number(v);
  return Number.isFinite(n) ? n : 0;
};

const loadLeaveEarnedData = async () => {
  if (!props.payrollPeriodId) {
    leaveData.value = [];
    return;
  }
  dataLoading.value = true;
  try {
    const { data } = await api.get(
      `/global/employee-leave-earned/${props.payrollPeriodId}`,
    );
    const list = Array.isArray(data) ? data : [];
    leaveData.value = list.map((row) => ({
      employee_no: row.employee_no || "",
      name: row.name || "—",
      position: row.position || "—",
      department: row.department || "—",
      days: parseNum(row.days_present),
      vacation_leave: parseNum(row.vl_earned),
      sick_leave: parseNum(row.sl_earned),
      photo: row.photo || "",
    }));
  } catch (error) {
    console.error("Failed to load leave earned details:", error);
    ElMessage.error("Failed to load leave earned details");
  } finally {
    dataLoading.value = false;
  }
};

const departments = computed(() => {
  const s = new Set();
  leaveData.value.forEach((e) => {
    if (e.department) s.add(e.department);
  });
  return Array.from(s).sort();
});
const filteredLeaveData = computed(() => {
  let f = leaveData.value;
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase();
    f = f.filter(
      (e) =>
        e.name?.toLowerCase().includes(q) ||
        e.employee_no?.toLowerCase().includes(q),
    );
  }
  if (departmentFilter.value)
    f = f.filter((e) => e.department === departmentFilter.value);
  return f;
});
const totalVacationLeave = computed(() =>
  leaveData.value.reduce((s, e) => s + parseNum(e.vacation_leave), 0),
);
const totalSickLeave = computed(() =>
  leaveData.value.reduce((s, e) => s + parseNum(e.sick_leave), 0),
);
const totalLeaveDays = computed(
  () => totalVacationLeave.value + totalSickLeave.value,
);

const getInitials = (n) =>
  !n
    ? "??"
    : n
        .split(" ")
        .map((x) => x[0])
        .join("")
        .toUpperCase()
        .slice(0, 2);
const AVATAR_PAIRS = [
  { bg: "#E6F1FB", fg: "#0C447C" },
  { bg: "#E1F5EE", fg: "#085041" },
  { bg: "#EEEDFE", fg: "#3C3489" },
  { bg: "#FAECE7", fg: "#712B13" },
  { bg: "#FAEEDA", fg: "#633806" },
];
const avatarBg = (n) =>
  AVATAR_PAIRS[((n || "").charCodeAt(0) || 0) % AVATAR_PAIRS.length].bg;
const avatarFg = (n) =>
  AVATAR_PAIRS[((n || "").charCodeAt(0) || 0) % AVATAR_PAIRS.length].fg;

const handleClose = () => {
  visible.value = false;
};
const handleRefresh = async () => {
  await loadLeaveEarnedData();
  emit("refresh");
};
const handlePrint = () => {
  const rows = filteredLeaveData.value;
  if (!rows.length) {
    ElMessage.warning("No leave earned data to print");
    return;
  }
  const tableRows = rows
    .map(
      (emp) => `
      <tr>
        <td>${emp.employee_no}</td>
        <td>${emp.name}</td>
        <td>${emp.department}</td>
        <td style="text-align:right;">${parseNum(emp.days).toFixed(2)}</td>
        <td style="text-align:right;">${parseNum(emp.vacation_leave).toFixed(3)}</td>
        <td style="text-align:right;">${parseNum(emp.sick_leave).toFixed(3)}</td>
        <td style="text-align:right;">${(
          parseNum(emp.vacation_leave) + parseNum(emp.sick_leave)
        ).toFixed(3)}</td>
      </tr>`,
    )
    .join("");

  const printWindow = window.open("", "_blank", "width=1100,height=800");
  if (!printWindow) return;
  printWindow.document.write(`
    <html>
      <head>
        <title>Leave earned details</title>
        <style>
          body { font-family: Arial, sans-serif; padding: 16px; }
          h2 { margin: 0 0 8px 0; }
          p { margin: 0 0 16px 0; color: #666; }
          table { width: 100%; border-collapse: collapse; font-size: 12px; }
          th, td { border: 1px solid #ddd; padding: 8px; }
          th { background: #f5f5f5; text-align: left; }
        </style>
      </head>
      <body>
        <h2>Employee leave earned details</h2>
        <p>${props.payrollPeriod || ""}</p>
        <table>
          <thead>
            <tr>
              <th>Employee no.</th>
              <th>Name</th>
              <th>Department</th>
              <th>Days</th>
              <th>Vacation leave</th>
              <th>Sick leave</th>
              <th>Total leave</th>
            </tr>
          </thead>
          <tbody>${tableRows}</tbody>
        </table>
      </body>
    </html>
  `);
  printWindow.document.close();
  printWindow.focus();
  printWindow.print();
  emit("print");
};
const handleExport = () => {
  const rows = filteredLeaveData.value;
  if (!rows.length) {
    ElMessage.warning("No leave earned data to export");
    return;
  }
  const escapeCsv = (value) => `"${String(value ?? "").replace(/"/g, '""')}"`;
  const header = [
    "Employee No",
    "Name",
    "Department",
    "Days",
    "Vacation Leave",
    "Sick Leave",
    "Total Leave",
  ].join(",");
  const body = rows
    .map((emp) =>
      [
        escapeCsv(emp.employee_no),
        escapeCsv(emp.name),
        escapeCsv(emp.department),
        parseNum(emp.days).toFixed(2),
        parseNum(emp.vacation_leave).toFixed(3),
        parseNum(emp.sick_leave).toFixed(3),
        (parseNum(emp.vacation_leave) + parseNum(emp.sick_leave)).toFixed(3),
      ].join(","),
    )
    .join("\n");
  const csv = `${header}\n${body}`;
  const blob = new Blob([csv], { type: "text/csv;charset=utf-8;" });
  const url = URL.createObjectURL(blob);
  const link = document.createElement("a");
  link.href = url;
  link.download = `leave_earned_${props.payrollPeriodId || "period"}.csv`;
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  URL.revokeObjectURL(url);
  ElMessage.success("Leave earned data exported");
  emit("export");
};
watch(visible, (v) => {
  if (typeof document !== "undefined")
    document.body.style.overflow = v ? "hidden" : "";
  if (v) loadLeaveEarnedData();
});
watch(
  () => props.payrollPeriodId,
  () => {
    if (visible.value) loadLeaveEarnedData();
  },
);
</script>

<style scoped>
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
  transition: opacity 0.2s;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
.modal-card {
  background: #fff;
  border-radius: 16px;
  width: 100%;
  max-width: 900px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.14);
}
.modal-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  padding: 18px 24px;
  border-bottom: 1px solid #f3f4f6;
  flex-shrink: 0;
}
.modal-title {
  font-size: 16px;
  font-weight: 500;
  color: #111827;
  margin: 0 0 3px;
}
.modal-sub {
  font-size: 13px;
  color: #9ca3af;
  margin: 0;
}
.close-btn {
  background: transparent;
  border: none;
  border-radius: 7px;
  padding: 5px;
  color: #9ca3af;
  cursor: pointer;
  display: flex;
}
.close-btn:hover {
  background: #f3f4f6;
  color: #374151;
}
.modal-body {
  padding: 16px 24px;
  overflow-y: auto;
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 14px;
}
.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  padding: 14px 24px;
  border-top: 1px solid #f3f4f6;
  flex-shrink: 0;
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
.btn-ghost:hover:not(:disabled) {
  background: #f3f4f6;
}
.btn-ghost:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
.stat-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 10px;
}
.stat-card {
  background: #f9fafb;
  border-radius: 10px;
  padding: 12px 14px;
}
.stat-label {
  font-size: 11px;
  color: #9ca3af;
  margin-bottom: 4px;
}
.stat-value {
  font-size: 18px;
  font-weight: 500;
  color: #111827;
}
.stat-value.green {
  color: #085041;
}
.stat-value.amber {
  color: #633806;
}
.stat-value.purple {
  color: #3c3489;
}
.filter-bar {
  display: flex;
  gap: 10px;
  align-items: center;
  flex-wrap: wrap;
}
.search-wrap {
  position: relative;
  flex: 1;
  min-width: 180px;
}
.search-icon {
  position: absolute;
  left: 8px;
  top: 50%;
  transform: translateY(-50%);
  color: #9ca3af;
  pointer-events: none;
}
.search-input {
  width: 100%;
  font-size: 13px;
  padding: 7px 10px 7px 28px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  outline: none;
  color: #111827;
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
.action-group {
  display: flex;
  gap: 6px;
  margin-left: auto;
}
.table-wrap {
  border: 1px solid #e5e7eb;
  border-radius: 10px;
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
  padding: 9px 14px;
  text-align: left;
  font-weight: 500;
  font-size: 12px;
  color: #6b7280;
  border-bottom: 1px solid #e5e7eb;
}
thead .num-col {
  text-align: right;
}
tbody tr {
  border-bottom: 1px solid #f3f4f6;
  transition: background 0.1s;
}
tbody tr:last-child {
  border-bottom: none;
}
tbody tr:hover {
  background: #fafafa;
}
td {
  padding: 10px 14px;
  color: #111827;
  vertical-align: middle;
}
.mono {
  font-family: monospace;
  font-size: 12px;
}
.muted {
  color: #9ca3af;
}
.num-td {
  text-align: right;
  font-family: monospace;
  font-weight: 500;
}
.num-td.info {
  color: #185fa5;
}
.num-td.green {
  color: #085041;
}
.num-td.amber {
  color: #633806;
}
.num-td.purple {
  color: #3c3489;
}
.emp-cell {
  display: flex;
  align-items: center;
  gap: 9px;
}
.avatar {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 10px;
  font-weight: 500;
  flex-shrink: 0;
}
.emp-name {
  font-weight: 500;
  font-size: 13px;
}
.emp-pos {
  font-size: 11px;
  color: #9ca3af;
}
</style>
