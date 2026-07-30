<template>
  <div class="ol-shell">
    <!-- Loading skeleton -->
    <div v-if="loading" class="skeleton-wrap">
      <div v-for="i in 5" :key="i" class="skeleton-row" />
    </div>

    <template v-else>
      <div class="table-wrap" v-if="overtimeData.length > 0">
        <table>
          <thead>
            <tr>
              <th>Payroll period</th>
              <th style="width: 130px">Cut-off</th>
              <th style="width: 120px">Release date</th>
              <th style="width: 120px">Start date</th>
              <th style="width: 120px">End date</th>
              <th style="text-align: center; width: 90px">Status</th>
              <th style="width: 230px"></th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="row in paginatedData"
              :key="row.id"
              :class="{ 'row-posted': isPosted(row) }"
            >
              <td class="period-name">{{ row.payrollPeriod }}</td>
              <td class="muted">{{ row.cutOff }}</td>
              <td class="muted">{{ formatDate(row.releaseDate) }}</td>
              <td class="muted">{{ formatDate(row.attendanceStartDate) }}</td>
              <td class="muted">{{ formatDate(row.attendanceEndDate) }}</td>
              <td style="text-align: center">
                <span
                  class="status-pill"
                  :class="isPosted(row) ? 'posted' : 'draft'"
                >
                  {{ isPosted(row) ? "Posted" : "Draft" }}
                </span>
              </td>
              <td>
                <div class="actions-cell">
                  <button class="act-btn" @click="$emit('view-employees', row)">
                    Detail
                  </button>
                  <button
                    v-if="!isPosted(row)"
                    class="act-btn"
                    @click="$emit('edit', row)"
                  >
                    Edit
                  </button>
                  <button
                    v-if="!isPosted(row)"
                    class="act-btn danger"
                    @click="$emit('delete', row)"
                  >
                    <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
                      <path
                        d="M1.5 3h9M4.5 3V2a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 .5.5v1M10 3l-.5 6.5a.5.5 0 0 1-.5.5H3a.5.5 0 0 1-.5-.5L2 3"
                        stroke="currentColor"
                        stroke-width="1.2"
                        stroke-linecap="round"
                      />
                    </svg>
                  </button>
                  <button
                    class="act-btn"
                    :class="isPosted(row) ? 'unpost' : 'post'"
                    :disabled="processingIds.includes(row.id)"
                    @click="handleTogglePosting(row)"
                  >
                    {{
                      processingIds.includes(row.id)
                        ? "…"
                        : isPosted(row)
                          ? "Unpost"
                          : "Post"
                    }}
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination -->
        <div class="pagination">
          <span class="page-info">
            Showing {{ (listPage - 1) * listPageSize + 1 }}–{{
              Math.min(listPage * listPageSize, totalListItems)
            }}
            of {{ totalListItems }} results
          </span>
          <div class="page-btns">
            <button
              class="page-btn"
              :disabled="listPage === 1"
              @click="handleListPageChange(listPage - 1)"
            >
              &#8249;
            </button>
            <button
              v-for="p in totalPages"
              :key="p"
              class="page-btn"
              :class="{ active: p === listPage }"
              @click="handleListPageChange(p)"
            >
              {{ p }}
            </button>
            <button
              class="page-btn"
              :disabled="listPage === totalPages"
              @click="handleListPageChange(listPage + 1)"
            >
              &#8250;
            </button>
          </div>
        </div>
      </div>

      <!-- Empty state -->
      <div v-else class="empty-state">
        <svg width="40" height="40" viewBox="0 0 40 40" fill="none">
          <rect
            x="6"
            y="8"
            width="28"
            height="24"
            rx="3"
            stroke="#d1d5db"
            stroke-width="1.5"
          />
          <path
            d="M12 15h16M12 20h10"
            stroke="#d1d5db"
            stroke-width="1.5"
            stroke-linecap="round"
          />
        </svg>
        <p>No overtime payroll records found</p>
        <button class="btn-primary" @click="$emit('add-new')">
          <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
            <path
              d="M6 2v8M2 6h8"
              stroke="currentColor"
              stroke-width="1.5"
              stroke-linecap="round"
            />
          </svg>
          Add first overtime payroll
        </button>
      </div>
    </template>

    <!-- Employee details slide-panel (teleported) -->
    <Teleport to="body">
      <Transition name="fade">
        <div
          v-if="showEmployeePanel"
          class="modal-backdrop"
          @click.self="showEmployeePanel = false"
        >
          <div class="modal-card">
            <div class="modal-header">
              <div>
                <h3 class="modal-title">Employee details</h3>
                <p class="modal-sub">
                  {{ selectedPayroll?.payrollPeriod }} ·
                  {{ selectedPayroll?.cutOff }}
                </p>
              </div>
              <button class="close-btn" @click="showEmployeePanel = false">
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
              <!-- Summary strip -->
              <div class="emp-summary">
                <div class="emp-sum-item">
                  <span class="esi-label">Employees</span
                  ><strong>{{ totalEmployees }}</strong>
                </div>
                <div class="emp-sum-item">
                  <span class="esi-label">Total hours</span
                  ><strong>{{ totalHours.toFixed(2) }}</strong>
                </div>
                <div class="emp-sum-item">
                  <span class="esi-label">Total earned</span
                  ><strong
                    >₱{{
                      totalEarned.toLocaleString("en-PH", {
                        minimumFractionDigits: 2,
                      })
                    }}</strong
                  >
                </div>
              </div>

              <!-- Employee table -->
              <div class="table-wrap" v-if="employeeData.length">
                <table>
                  <thead>
                    <tr>
                      <th style="width: 100px">Employee no.</th>
                      <th>Name</th>
                      <th>Position</th>
                      <th class="num-col">Total hours</th>
                      <th class="num-col">Salary</th>
                      <th class="num-col">Earned</th>
                      <th
                        v-if="!isPosted(selectedPayroll)"
                        style="width: 80px"
                      ></th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr
                      v-for="emp in employeeData"
                      :key="emp.dtlId || emp.employeeNo"
                    >
                      <td class="mono muted">{{ emp.employeeNo }}</td>
                      <td class="emp-name">{{ emp.name }}</td>
                      <td class="muted">{{ emp.position }}</td>
                      <td class="num-td">{{ emp.totalHours }}</td>
                      <td class="num-td">
                        ₱{{
                          emp.salary?.toLocaleString("en-PH", {
                            minimumFractionDigits: 2,
                          })
                        }}
                      </td>
                      <td class="num-td">
                        ₱{{
                          emp.earned?.toLocaleString("en-PH", {
                            minimumFractionDigits: 2,
                          })
                        }}
                      </td>
                      <td v-if="!isPosted(selectedPayroll)">
                        <button
                          class="act-btn danger"
                          @click="handleRemoveEmployee(emp)"
                        >
                          <svg
                            width="12"
                            height="12"
                            viewBox="0 0 12 12"
                            fill="none"
                          >
                            <path
                              d="M1.5 3h9M4.5 3V2a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 .5.5v1M10 3l-.5 6.5a.5.5 0 0 1-.5.5H3a.5.5 0 0 1-.5-.5L2 3"
                              stroke="currentColor"
                              stroke-width="1.2"
                              stroke-linecap="round"
                            />
                          </svg>
                        </button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div v-else class="empty-state" style="padding: 32px 0">
                <p>No employee data available</p>
              </div>
            </div>

            <div class="modal-footer">
              <button class="btn-ghost" @click="showEmployeePanel = false">
                Close
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, watch } from "vue";
import { useOvertimePayroll } from "../../../Composables/useOvertimePayroll.js";

const props = defineProps({
  overtimeData: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
});
const emit = defineEmits([
  "edit",
  "delete",
  "view-employees",
  "post",
  "unpost",
  "print",
  "tabulate",
  "remove-employee",
  "add-new",
  "load-employee-data",
  "selection-change",
]);

const { removeEmployee, transformEmployeeData } = useOvertimePayroll();

// Pagination
const listPage = ref(1);
const listPageSize = ref(10);
const totalListItems = computed(() => props.overtimeData.length);
const totalPages = computed(() =>
  Math.max(1, Math.ceil(totalListItems.value / listPageSize.value)),
);
const paginatedData = computed(() => {
  const s = (listPage.value - 1) * listPageSize.value;
  return props.overtimeData.slice(s, s + listPageSize.value);
});
const handleListPageChange = (p) => {
  listPage.value = p;
};
watch(
  () => props.overtimeData,
  () => {
    listPage.value = 1;
  },
  { deep: true },
);

// State
const showEmployeePanel = ref(false);
const selectedPayroll = ref(null);
const employeeData = ref([]);
const processingIds = ref([]);

const totalEmployees = computed(() => employeeData.value.length);
const totalHours = computed(() =>
  employeeData.value.reduce((s, r) => s + (Number(r.totalHours) || 0), 0),
);
const totalEarned = computed(() =>
  employeeData.value.reduce((s, r) => s + (Number(r.earned) || 0), 0),
);

const formatDate = (d) => {
  if (!d) return "—";
  const dt = new Date(d);
  if (isNaN(dt.getTime())) return "—";
  return dt.toLocaleDateString("en-US", {
    month: "short",
    day: "2-digit",
    year: "numeric",
  });
};
const isPosted = (row) => {
  if (!row) return false;
  const v = row.posted;
  return v === true || v === 1 || v === "1" || v === "true";
};

const handleTogglePosting = (row) => {
  const typeId = isPosted(row) ? 0 : 1;
  processingIds.value.push(row.id);
  const done = () => {
    processingIds.value = processingIds.value.filter((id) => id !== row.id);
  };
  try {
    emit(isPosted(row) ? "unpost" : "post", row.id, typeId);
  } finally {
    setTimeout(done, 2000);
  }
};

const handleRemoveEmployee = async (emp) => {
  try {
    await removeEmployee(emp.dtlId);
    emit("remove-employee", emp);
    if (selectedPayroll.value) await loadEmployeeData(selectedPayroll.value.id);
  } catch (e) {
    console.error(e);
  }
};

const loadEmployeeData = async (payrollId) => {
  emit("load-employee-data", payrollId, (data) => {
    employeeData.value = transformEmployeeData(data);
  });
};

const showEmployees = async (payroll) => {
  selectedPayroll.value = payroll;
  showEmployeePanel.value = true;
  await loadEmployeeData(payroll.id);
};

defineExpose({ showEmployees });
</script>

<style scoped>
.ol-shell {
  display: flex;
  flex-direction: column;
  gap: 0;
}

/* Table */
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
tbody tr.row-posted {
  background: #f8fffe;
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
.mono {
  font-family: monospace;
  font-size: 12px;
}
.emp-name {
  font-weight: 500;
}
.num-td {
  text-align: right;
  font-family: monospace;
  font-size: 12px;
}

/* Status pills */
.status-pill {
  display: inline-block;
  padding: 3px 9px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 500;
}
.status-pill.posted {
  background: #e1f5ee;
  color: #085041;
}
.status-pill.draft {
  background: #faeeda;
  color: #633806;
}

/* Action buttons */
.actions-cell {
  display: flex;
  align-items: center;
  gap: 6px;
  flex-wrap: nowrap;
}
.act-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
  background: transparent;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  padding: 4px 10px;
  font-size: 12px;
  color: #374151;
  cursor: pointer;
  white-space: nowrap;
}
.act-btn:hover:not(:disabled) {
  background: #f3f4f6;
}
.act-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}
.act-btn.danger:hover {
  background: #fee2e2;
  color: #a32d2d;
  border-color: #fecaca;
}
.act-btn.post {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
  background: #fff;
  color: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  padding: 4px 10px;
  font-size: 12px;
  color: #374151;
  cursor: pointer;
  white-space: nowrap;
}
.act-btn.post:hover {
  background: #409eff;
  color: #fff;
  opacity: 0.88;
}
.act-btn.unpost {
  border-color: #fecaca;
  color: #a32d2d;
}
.act-btn.unpost:hover {
  background: #fee2e2;
}

/* Pagination */
.pagination {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 14px;
  border-top: 1px solid #f3f4f6;
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

/* Skeleton */
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

/* Empty */
.empty-state {
  text-align: center;
  padding: 48px 0;
  color: #9ca3af;
  font-size: 14px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
}
.empty-state svg {
  display: block;
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
}
.btn-primary:hover {
  opacity: 0.88;
}

/* Employee panel modal */
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
  max-width: 860px;
  max-height: 88vh;
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
.btn-ghost:hover {
  background: #f3f4f6;
}

/* Employee summary strip */
.emp-summary {
  display: flex;
  gap: 20px;
  padding: 12px 16px;
  background: #f9fafb;
  border-radius: 10px;
  flex-wrap: wrap;
}
.emp-sum-item {
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.esi-label {
  font-size: 11px;
  color: #9ca3af;
}
.emp-sum-item strong {
  font-size: 16px;
  font-weight: 500;
  color: #111827;
}
</style>
