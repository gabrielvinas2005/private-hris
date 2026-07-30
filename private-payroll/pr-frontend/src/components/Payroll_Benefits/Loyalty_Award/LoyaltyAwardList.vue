<template>
  <div class="loyalty-award-list benefit-table-card">
    <!-- Enhanced Table with better styling -->
    <div class="export-buttons">
      <el-button @click="handlePrint" :icon="Printer" plain> Print </el-button>
      <el-button @click="handleExcel" :icon="Download" plain> Excel </el-button>
      <el-button @click="handlePDF" :icon="Document" plain> PDF </el-button>
      <el-button @click="toggleColumnVisibility" :icon="Setting" plain>
        Column Visibility
      </el-button>
    </div>
    <div class="table-container benefit-table-container">
      <el-table
        :data="paginatedListData"
        border
        style="width: 100%"
        v-loading="loading"
        @selection-change="handleSelectionChange"
        :row-class-name="getRowClassName"
        stripe
        class="enhanced-table benefit-table"
      >
        <!-- <el-table-column prop="branch" label="Branch" min-width="200">
          <template #default="scope">
            <div class="branch-cell">
              <div class="branch-name">{{ scope.row.branch }}</div>
            </div>
          </template>
        </el-table-column> -->

        <el-table-column prop="month" label="Month" width="130" align="center">
          <template #default="scope">
            {{ scope.row.month }}
          </template>
        </el-table-column>

        <el-table-column prop="year" label="Year" width="130" align="center">
          <template #default="scope">
            <div class="year-cell">
              <i class="el-icon-date year-icon"></i>
              <span class="year-text">{{ scope.row.year }}</span>
            </div>
          </template>
        </el-table-column>

        <el-table-column
          prop="status"
          label="Status"
          width="110"
          align="center"
        >
          <template #default="scope">
            <el-tag
              :type="isPosted(scope.row) ? 'success' : 'danger'"
              effect="light"
            >
              {{ isPosted(scope.row) ? "Posted" : "Draft" }}
            </el-tag>
          </template>
        </el-table-column>

        <el-table-column
          label="Actions"
          min-width="200"
          fixed="right"
          align="center"
        >
          <template #default="scope">
            <div class="action-buttons">
              <!-- View Button - Always visible -->
              <el-button
                type="info"
                @click="$emit('view-employees', scope.row)"
                plain
              >
                View
              </el-button>

              <!-- Edit Button - Only show when NOT posted -->
              <el-button
                v-if="!isPosted(scope.row)"
                type="primary"
                @click="$emit('edit', scope.row)"
                plain
              >
                Edit
              </el-button>

              <!-- Post/Unpost Button -->
              <el-button
                :type="isPosted(scope.row) ? 'warning' : 'success'"
                @click="handleTogglePosting(scope.row)"
                plain
                :loading="processingIds.includes(scope.row.id)"
              >
                {{ isPosted(scope.row) ? "Unpost" : "Post" }}
              </el-button>

              <!-- Delete Button - Only show when NOT posted -->
              <el-button
                v-if="!isPosted(scope.row)"
                type="danger"
                @click="handleDelete(scope.row)"
                plain
                :loading="processingIds.includes(scope.row.id)"
              >
                Delete
              </el-button>
            </div>
          </template>
        </el-table-column>

        <el-table-column
          label="Reports"
          min-width="130"
          fixed="right"
          align="center"
        >
          <template #default="scope">
            <div class="report-buttons">
              <el-button
                type="primary"
                plain
                @click="$emit('print-ors', scope.row)"
                >Print ORS</el-button
              >
              <el-button
                type="primary"
                plain
                @click="$emit('print-dv', scope.row)"
                >Print DV</el-button
              >
            </div>
          </template>
        </el-table-column>
      </el-table>
    </div>

    <!-- Pagination -->
    <div class="pagination-container" v-if="loyaltyAwardData.length > 0">
      <div class="pagination-info">
        <span class="text-sm text-gray-600">
          Showing {{ (listPage - 1) * listPageSize + 1 }} to
          {{ Math.min(listPage * listPageSize, totalListItems) }} of
          {{ totalListItems }} entries
        </span>
      </div>
      <el-pagination
        :current-page="listPage"
        :page-size="listPageSize"
        :page-sizes="[10, 20, 50, 100]"
        :total="totalListItems"
        layout="prev, pager, next, sizes"
        @size-change="handleListSizeChange"
        @current-change="handleListPageChange"
        class="pagination"
      />
    </div>

    <!-- Enhanced Empty State -->
    <div v-if="!loading && loyaltyAwardData.length === 0" class="empty-state">
      <el-empty description="No loyalty award records found" :image-size="120">
        <template #image>
          <i class="el-icon-medal empty-icon"></i>
        </template>
        <el-button type="primary" @click="$emit('add-new')">
          <i class="el-icon-plus"></i>
          Add First Loyalty Award
        </el-button>
      </el-empty>
    </div>

    <!-- Employee Details Dialog -->
    <el-dialog
      v-model="showEmployeeDialog"
      title="Employee Details"
      width="60%"
      :close-on-click-modal="false"
      class="employee-details-dialog"
    >
      <div v-if="selectedLoyaltyAward" class="employee-details-content">
        <div class="mb-4">
          <h3>
            {{ selectedLoyaltyAward.branch }} -
            {{ selectedLoyaltyAward.month }} {{ selectedLoyaltyAward.year }}
          </h3>
          <p class="text-gray-600">
            Status: {{ selectedLoyaltyAward.posted ? "Posted" : "Draft" }}
          </p>
        </div>

        <!-- Summary Cards -->
        <div class="summary-stats mb-4" v-if="employeeData.length">
          <el-row :gutter="16">
            <el-col :span="6">
              <el-card class="stat-card">
                <div class="stat-content">
                  <div class="stat-label">Total Employees</div>
                  <div class="stat-value">{{ totalEmployees }}</div>
                </div>
              </el-card>
            </el-col>

            <el-col :span="6">
              <el-card class="stat-card">
                <div class="stat-content">
                  <div class="stat-label">Total Cash Award</div>
                  <div class="stat-value">
                    ₱{{ formatCurrency(totalCashAward) }}
                  </div>
                </div>
              </el-card>
            </el-col>

            <el-col :span="6">
              <el-card class="stat-card">
                <div class="stat-content">
                  <div class="stat-label">Average Years</div>
                  <div class="stat-value">{{ averageYears.toFixed(1) }}</div>
                  <div class="stat-subtext">years of service</div>
                </div>
              </el-card>
            </el-col>

            <el-col :span="6">
              <el-card class="stat-card">
                <div class="stat-content">
                  <div class="stat-label">Award Setup Status</div>
                  <div class="stat-value">{{ employeesWithAward }}</div>
                  <div class="stat-subtext">
                    missing: {{ employeesMissingAward }}
                  </div>
                </div>
              </el-card>
            </el-col>
          </el-row>
        </div>

        <!-- Search and Filter Controls -->
        <div class="employee-controls mb-4">
          <div class="search-section">
            <el-input
              v-model="employeeSearchQuery"
              placeholder="Search employees..."
              clearable
              style="width: 300px"
              prefix-icon="el-icon-search"
              @input="handleEmployeeSearch"
            />
          </div>
          <div class="pagination-info">
            <span class="text-sm text-gray-600">
              Showing {{ (currentPage - 1) * pageSize + 1 }} to
              {{
                Math.min(currentPage * pageSize, filteredEmployeeData.length)
              }}
              of {{ filteredEmployeeData.length }} employees
            </span>
          </div>
        </div>

        <!-- Employee Table with Pagination -->
        <div class="employee-table-container">
          <el-table
            :data="paginatedEmployeeData"
            style="width: 100%"
            stripe
            border
            max-height="500"
            v-loading="loadingEmployees"
          >
            <el-table-column
              prop="employeeNo"
              label="Employee No"
              min-width="120"
            />
            <el-table-column prop="name" label="Employee Name" width="200" />
            <el-table-column prop="position" label="Position" min-width="250" />
            <el-table-column
              prop="years"
              label="Years of Service"
              width="160"
              align="center"
            >
              <template #default="scope">
                <el-tag type="info" size="small"
                  >{{ scope.row.years }} years</el-tag
                >
              </template>
            </el-table-column>
            <el-table-column
              prop="cashAward"
              label="Cash Award"
              width="120"
              align="right"
            >
              <template #default="scope">
                ₱{{ scope.row.cashAward?.toLocaleString() || "0" }}
              </template>
            </el-table-column>
            <el-table-column label="Actions" width="120" align="center">
              <template #default="scope">
                <div class="actions-cell">
                  <el-button
                    size="small"
                    type="danger"
                    @click="handleRemoveEmployee(scope.row)"
                    :disabled="selectedLoyaltyAward.posted"
                  >
                    Remove
                  </el-button>
                </div>
              </template>
            </el-table-column>
          </el-table>
        </div>

        <!-- Pagination Controls -->
        <div class="pagination-container mt-4">
          <el-pagination
            :current-page="currentPage"
            :page-size="pageSize"
            :page-sizes="[10, 20, 50, 100]"
            :total="filteredEmployeeData.length"
            layout="total, sizes, prev, pager, next, jumper"
            @size-change="handleSizeChange"
            @current-change="handleCurrentChange"
            class="employee-pagination"
          />
        </div>
      </div>

      <template #footer>
        <div class="dialog-footer">
          <div class="footer-info">
            <span class="text-sm text-gray-600">
              Total: {{ filteredEmployeeData.length }} employees
            </span>
          </div>
          <el-button @click="showEmployeeDialog = false">Close</el-button>
        </div>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, computed, watch } from "vue";
import { useLoyaltyAward } from "../../../Composables/useLoyaltyAwardBenefits.js";
import {
  Search,
  Printer,
  Download,
  Document,
  Setting,
} from "@element-plus/icons-vue";

const props = defineProps({
  loyaltyAwardData: {
    type: Array,
    default: () => [],
  },
  loading: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits([
  "edit",
  "detail",
  "view-employees",
  "post",
  "unpost",
  "delete",
  "print",
  "print-ors",
  "print-dv",
  "remove-employee",
  "add-new",
  "load-employee-data",
  "export-excel",
  "export-pdf",
  "toggle-columns",
  "selection-change",
]);

const { transformEmployeeData } = useLoyaltyAward();

// Local state
const showEmployeeDialog = ref(false);
const selectedLoyaltyAward = ref(null);
const employeeData = ref([]);
const processingIds = ref([]);
const generatingIds = ref([]);
const selectAll = ref(false);
const selectedRows = ref([]);

// List pagination (main table)
const listPage = ref(1);
const listPageSize = ref(10);
const totalListItems = computed(() => props.loyaltyAwardData.length);
const paginatedListData = computed(() => {
  const start = (listPage.value - 1) * listPageSize.value;
  const end = start + listPageSize.value;
  return props.loyaltyAwardData.slice(start, end);
});
const handleListSizeChange = (newSize) => {
  listPageSize.value = newSize;
  listPage.value = 1;
};
const handleListPageChange = (newPage) => {
  listPage.value = newPage;
};

watch(
  () => props.loyaltyAwardData,
  () => {
    listPage.value = 1;
  },
  { deep: true },
);

// Pagination and search state (employee dialog)
const currentPage = ref(1);
const pageSize = ref(20);
const employeeSearchQuery = ref("");
const loadingEmployees = ref(false);

const formatCurrency = (value) => {
  const n = Number(value ?? 0);
  return (Number.isFinite(n) ? n : 0).toLocaleString(undefined, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
};

// Computed properties for filtering and pagination
const filteredEmployeeData = computed(() => {
  if (!employeeSearchQuery.value) return employeeData.value;

  const query = employeeSearchQuery.value.toLowerCase();
  return employeeData.value.filter(
    (emp) =>
      emp.name?.toLowerCase().includes(query) ||
      emp.employeeNo?.toLowerCase().includes(query) ||
      emp.position?.toLowerCase().includes(query),
  );
});

const paginatedEmployeeData = computed(() => {
  const start = (currentPage.value - 1) * pageSize.value;
  const end = start + pageSize.value;
  return filteredEmployeeData.value.slice(start, end);
});

const totalEmployees = computed(() => (employeeData.value || []).length);

const totalCashAward = computed(() => {
  return (employeeData.value || []).reduce((sum, emp) => {
    return sum + Number(emp.cashAward || 0);
  }, 0);
});

const averageYears = computed(() => {
  const list = employeeData.value || [];
  if (!list.length) return 0;
  const total = list.reduce((sum, emp) => sum + Number(emp.years || 0), 0);
  return total / list.length;
});

const employeesWithAward = computed(() => {
  return (employeeData.value || []).filter((e) => !!e.loyaltyAwardSetupId)
    .length;
});

const employeesMissingAward = computed(() => {
  return (employeeData.value || []).filter((e) => !e.loyaltyAwardSetupId)
    .length;
});

const handleSelectionChange = (selection) => {
  selectedRows.value = selection;
  selectAll.value =
    selection.length === props.loyaltyAwardData.length &&
    props.loyaltyAwardData.length > 0;
  emit("selection-change", selection);
};

const handleSelectAll = (checked) => {
  if (checked) {
    selectedRows.value = [...props.loyaltyAwardData];
  } else {
    selectedRows.value = [];
  }
  emit("selection-change", selectedRows.value);
};

const handleTogglePosting = async (row) => {
  processingIds.value.push(row.id);

  try {
    await emit(row.posted ? "unpost" : "post", row);
  } finally {
    processingIds.value = processingIds.value.filter((id) => id !== row.id);
  }
};

const isPosted = (row) => {
  if (!row) return false;
  const value = row.posted;
  return value === true || value === 1 || value === "1" || value === "true";
};

// Row class name handler
const getRowClassName = ({ row }) => {
  return isPosted(row) ? "posted-row" : "draft-row";
};

const handleDelete = async (row) => {
  processingIds.value.push(row.id);

  try {
    await emit("delete", row);
  } finally {
    processingIds.value = processingIds.value.filter((id) => id !== row.id);
  }
};

const handleGenerateReport = async (row) => {
  generatingIds.value.push(row.id);

  try {
    await emit("print", row);
  } finally {
    generatingIds.value = generatingIds.value.filter((id) => id !== row.id);
  }
};

const handleRemoveEmployee = async (employee) => {
  try {
    emit("remove-employee", employee);
    // Reload employee data
    if (selectedLoyaltyAward.value) {
      await loadEmployeeData(selectedLoyaltyAward.value.id);
    }
  } catch (error) {
    console.error("Error removing employee:", error);
  }
};

const loadEmployeeData = async (loyaltyAwardId) => {
  emit("load-employee-data", loyaltyAwardId, (data) => {
    employeeData.value = transformEmployeeData(data);
  });
};

const showEmployees = async (loyaltyAward) => {
  selectedLoyaltyAward.value = loyaltyAward;
  showEmployeeDialog.value = true;
  currentPage.value = 1;
  employeeSearchQuery.value = "";
  await loadEmployeeData(loyaltyAward.id);
};

// Pagination and search handlers
const handleSizeChange = (newSize) => {
  pageSize.value = newSize;
  currentPage.value = 1;
};

const handleCurrentChange = (newPage) => {
  currentPage.value = newPage;
};

const handleEmployeeSearch = () => {
  currentPage.value = 1;
};

// Export methods
const handlePrint = () => {
  emit("print");
};

const handleExcel = () => {
  emit("export-excel");
};

const handlePDF = () => {
  emit("export-pdf");
};

const toggleColumnVisibility = () => {
  emit("toggle-columns");
};

// Expose method to parent component
defineExpose({
  showEmployees,
});
</script>

<style scoped>
.loyalty-award-list {
  width: 100%;
}

.table-container {
  border-radius: 12px;
  overflow: hidden;
}

.enhanced-table {
  border-radius: 12px;
}

.enhanced-table :deep(.el-table__header) {
  background: #f8fafc;
}

.enhanced-table :deep(.el-table__header th) {
  background: #f8fafc !important;
  border-bottom: 2px solid #e5e7eb;
  color: #374151;
  font-weight: 600;
  font-size: 14px;
  padding: 16px 12px;
}

.enhanced-table :deep(.el-table__body tr) {
  transition: all 0.2s ease;
}

.enhanced-table :deep(.el-table__body tr:hover) {
  background-color: #f9fafb !important;
  transform: translateY(-1px);
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.enhanced-table :deep(.el-table__body td) {
  padding: 16px 12px;
  border-bottom: 1px solid #f3f4f6;
  vertical-align: middle;
}

.summary-stats {
  margin-top: 12px;
}

.stat-card {
  border-radius: 10px;
  box-shadow:
    0 1px 2px rgba(0, 0, 0, 0.05),
    0 1px 3px rgba(0, 0, 0, 0.1);
  border: none;
}

.stat-content {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.stat-label {
  font-size: 12px;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.stat-value {
  font-size: 20px;
  font-weight: 700;
  color: #111827;
}

.stat-subtext {
  font-size: 12px;
  color: #9ca3af;
}

/* Branch Cell Styling */
.branch-cell {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.branch-name {
  font-weight: 600;
  color: #1f2937;
  font-size: 14px;
}

.branch-id {
  font-size: 12px;
  color: #6b7280;
  font-weight: 400;
}

/* Year Cell Styling */
.year-cell {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  flex-direction: column;
}

.year-icon {
  color: #6b7280;
  font-size: 14px;
}

.year-text {
  font-size: 13px;
  color: #374151;
  font-weight: 500;
}

/* Action Buttons Styling */
.action-buttons,
.report-buttons {
  display: flex;
  gap: 6px;
  justify-content: center;
  align-items: center;
  flex-wrap: nowrap; /* Keep buttons side-by-side on zoom */
  white-space: nowrap;
}

.action-buttons .el-button,
.report-buttons .el-button {
  min-width: 80px;
  height: 32px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 500;
  padding: 6px 12px;
  transition: all 0.2s ease;
}

.action-buttons .el-button:hover,
.report-buttons .el-button:hover {
  transform: translateY(-1px);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.action-buttons .el-button.is-loading,
.report-buttons .el-button.is-loading {
  transform: none;
  box-shadow: none;
}

.action-buttons .el-button i,
.report-buttons .el-button i {
  margin-right: 4px;
  font-size: 12px;
}

/* Row styling */
.posted-row {
  background-color: #f0f9ff;
}

.draft-row {
  background-color: #ffffff;
}

/* Empty State Styling */
.empty-state {
  padding: 60px 20px;
  text-align: center;
}

.empty-icon {
  font-size: 64px;
  color: #d1d5db;
  margin-bottom: 16px;
}

/* Loading State */
:deep(.el-loading-mask) {
  background-color: rgba(255, 255, 255, 0.9);
  backdrop-filter: blur(4px);
}

/* Responsive Design */
@media (max-width: 1200px) {
  .action-buttons,
  .report-buttons {
    flex-direction: row; /* Avoid vertical stacking */
    gap: 6px;
  }

  .action-buttons .el-button,
  .report-buttons .el-button {
    width: auto;
    min-width: 80px;
  }
}

/* Prevent Element Plus button internals from wrapping */
.action-buttons :deep(.el-button__content),
.report-buttons :deep(.el-button__content) {
  white-space: nowrap;
}

@media (max-width: 768px) {
  .enhanced-table :deep(.el-table__header th),
  .enhanced-table :deep(.el-table__body td) {
    padding: 12px 8px;
    font-size: 13px;
  }

  .branch-cell {
    gap: 2px;
  }

  .branch-name {
    font-size: 13px;
  }

  .branch-id {
    font-size: 11px;
  }
}

/* Utility Classes */
.mb-4 {
  margin-bottom: 1rem;
}

.text-gray-600 {
  color: #6b7280;
}

/* Animation for table rows */
@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.enhanced-table :deep(.el-table__body tr) {
  animation: slideIn 0.3s ease-out;
}

/* Enhanced checkbox styling */
:deep(.el-checkbox) {
  margin-right: 0;
}

:deep(.el-checkbox__input.is-checked .el-checkbox__inner) {
  background-color: #3b82f6;
  border-color: #3b82f6;
}

/* Tooltip styling */
:deep(.el-tooltip__popper) {
  background-color: #1f2937;
  color: white;
  border-radius: 8px;
  font-size: 12px;
  padding: 8px 12px;
}

/* Employee Details Dialog Styling */
.employee-details-dialog :deep(.el-dialog) {
  border-radius: 12px;
  overflow: hidden;
  justify-content: center;
}

.employee-details-dialog :deep(.el-dialog__body) {
  padding: 20px 24px;
  max-height: 80vh;
  overflow-y: auto;
}

.employee-details-content {
  min-height: 400px;
}

.employee-controls {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 0;
  border-bottom: 1px solid #e5e7eb;
}

.search-section {
  display: flex;
  align-items: center;
  gap: 12px;
}

.pagination-info {
  color: #6b7280;
  font-size: 14px;
}

.employee-table-container {
  margin: 16px 0;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.employee-table-container :deep(.el-table) {
  border-radius: 8px;
}

.employee-table-container :deep(.el-table__header) {
  background: #f8fafc;
}

.employee-table-container :deep(.el-table__header th) {
  background: #f8fafc !important;
  color: #374151;
  font-weight: 600;
  border-bottom: 2px solid #e5e7eb;
}

.employee-table-container :deep(.el-table__body tr:hover) {
  background-color: #f9fafb !important;
}

.pagination-container {
  display: flex;
  justify-content: center;
  padding: 16px 0;
  border-top: 1px solid #e5e7eb;
}

.employee-pagination {
  margin: 0;
}

.employee-pagination :deep(.el-pagination__total) {
  color: #6b7280;
  font-weight: 500;
}

.employee-pagination :deep(.el-pagination__sizes) {
  margin-right: 16px;
}

.employee-pagination :deep(.el-pagination__jump) {
  margin-left: 16px;
}

.dialog-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 0;
  border-top: 1px solid #e5e7eb;
}

.footer-info {
  color: #6b7280;
  font-size: 14px;
}

/* Responsive design for employee dialog */
@media (max-width: 768px) {
  .employee-details-dialog :deep(.el-dialog) {
    width: 95% !important;
    margin: 0 auto;
  }

  .employee-controls {
    flex-direction: column;
    gap: 12px;
    align-items: stretch;
  }

  .search-section {
    width: 100%;
  }

  .search-section .el-input {
    width: 100% !important;
  }

  .pagination-info {
    text-align: center;
  }

  .employee-table-container :deep(.el-table__body td) {
    padding: 8px 4px;
    font-size: 12px;
  }

  .employee-table-container :deep(.el-table__header th) {
    padding: 8px 4px;
    font-size: 12px;
  }
}

/* Enhanced scrollbar styling */
.employee-details-dialog :deep(.el-dialog__body)::-webkit-scrollbar {
  width: 8px;
}

.employee-details-dialog :deep(.el-dialog__body)::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 4px;
}

.employee-details-dialog :deep(.el-dialog__body)::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}

.employee-details-dialog
  :deep(.el-dialog__body)::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

/* Export Buttons */
.export-buttons {
  display: flex;
  gap: 5px;
  margin: 14px 0 14px 14px;

  flex-wrap: wrap;
}

.export-buttons .el-button {
  border-radius: 8px;
  font-weight: 500;
}

.actions-cell {
  display: flex;
  gap: 6px;
  align-items: center;
  justify-content: center;
  flex-wrap: nowrap;
  white-space: nowrap;
}

.actions-cell :deep(.el-button__content) {
  white-space: nowrap;
}
</style>
