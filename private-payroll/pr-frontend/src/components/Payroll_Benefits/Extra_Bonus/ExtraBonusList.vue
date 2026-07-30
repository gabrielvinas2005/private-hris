<template>
  <div class="extra-bonus-list benefit-table-card">
    <!-- Enhanced Table with better styling -->
    <div class="table-container benefit-table-container">
      <el-table
        :data="paginatedData"
        border
        style="width: 100%"
        v-loading="loading"
        @selection-change="handleSelectionChange"
        :header-cell-style="{
          background: '#f8fafc',
          color: '#374151',
          fontWeight: '600',
          borderBottom: '2px solid #e5e7eb',
        }"
        :cell-style="{ borderBottom: '1px solid #f3f4f6' }"
        stripe
        class="enhanced-table benefit-table"
      >
        <el-table-column type="selection" width="60" align="center">
          <template #header>
            <el-checkbox v-model="selectAll" @change="handleSelectAll" />
          </template>
        </el-table-column>

        <el-table-column
          prop="extra_bonus_type"
          label="Extra Bonus Type"
          min-width="200"
        >
          <template #default="scope">
            <div class="bonus-type-cell">
              <div class="bonus-type-name">
                {{ scope.row.extra_bonus_type || "N/A" }}
              </div>
            </div>
          </template>
        </el-table-column>

        <el-table-column prop="department" label="division" min-width="180">
          <template #default="scope">
            <div class="department-cell">
              <div class="department-name">
                {{ scope.row.department || "N/A" }}
              </div>
            </div>
          </template>
        </el-table-column>

        <el-table-column prop="year_id" label="Year" width="100" align="center">
          <template #default="scope">
            <strong>{{ scope.row.year_id || "N/A" }}</strong>
          </template>
        </el-table-column>

        <el-table-column
          prop="status"
          label="Status"
          width="120"
          align="center"
        >
          <template #default="scope">
            <el-tag
              :type="scope.row.status === 'posted' ? 'success' : 'warning'"
            >
              {{ scope.row.status === "posted" ? "Posted" : "Draft" }}
            </el-tag>
          </template>
        </el-table-column>

        <el-table-column
          label="Actions"
          min-width="200"
          align="center"
          fixed="right"
        >
          <template #default="scope">
            <div class="action-buttons">
              <el-button
                type="primary"
                plain
                @click="viewDetails(scope.row)"
                title="View Details"
              >
                Details
              </el-button>
              <el-button
                v-if="scope.row.status !== 'posted'"
                type="success"
                plain
                @click="postRecord(scope.row)"
                title="Post"
              >
                Post
              </el-button>

              <el-button
                type="success"
                plain
                @click="editRecord(scope.row)"
                title="Edit"
                v-if="scope.row.status !== 'posted'"
              >
                Edit
              </el-button>
              <el-button
                v-else
                type="warning"
                plain
                @click="unpostRecord(scope.row)"
                title="Unpost"
              >
                Unpost
              </el-button>
              <el-button
                type="danger"
                plain
                @click="deleteRecord(scope.row)"
                title="Delete"
                v-if="scope.row.status !== 'posted'"
              >
                Delete
              </el-button>
            </div>
          </template>
        </el-table-column>
      </el-table>
    </div>

    <!-- Pagination -->
    <div class="pagination-container" v-if="filteredData.length > 0">
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

    <!-- Empty State -->
    <div v-if="!loading && filteredData.length === 0" class="empty-state">
      <div class="empty-icon">
        <i class="el-icon-document"></i>
      </div>
      <h3>No Extra Bonus Records Found</h3>
      <p>
        No bonus records match your current filters. Try adjusting your search
        criteria or create a new extra bonus payroll.
      </p>
    </div>

    <!-- Bulk Actions -->
    <div v-if="selectedRecords.length > 0" class="bulk-actions">
      <div class="bulk-actions-content">
        <span class="selected-count">
          {{ selectedRecords.length }} record(s) selected
        </span>
        <div class="bulk-buttons">
          <el-button
            type="success"
            plain
            icon="el-icon-check"
            @click="bulkPost"
            :disabled="selectedRecords.some((r) => r.status === 'posted')"
          >
            Post Selected
          </el-button>
          <el-button
            type="primary"
            plain
            icon="el-icon-document"
            @click="bulkGenerateReport"
          >
            Generate Report
          </el-button>
          <el-button
            type="info"
            plain
            icon="el-icon-close"
            @click="clearSelection"
          >
            Clear Selection
          </el-button>
        </div>
      </div>
    </div>

    <!-- View Details Dialog -->
    <el-dialog
      v-model="showDetailsDialog"
      title="Extra Bonus Details"
      width="900px"
      :close-on-click-modal="false"
    >
      <div v-if="selectedRecord" class="details-content">
        <!-- Basic Information -->
        <div class="details-section">
          <h4>Payroll Information</h4>
          <div class="detail-row">
            <label>Extra Bonus Type:</label>
            <span>{{ selectedRecord.extra_bonus_type || "N/A" }}</span>
          </div>
          <div class="detail-row">
            <label>Office:</label>
            <span>{{ selectedRecord.department || "N/A" }}</span>
          </div>
          <div class="detail-row">
            <label>Year:</label>
            <span>{{ selectedRecord.year_id || "N/A" }}</span>
          </div>
          <div class="detail-row">
            <label>Status:</label>
            <el-tag
              :type="selectedRecord.status === 'posted' ? 'success' : 'warning'"
            >
              {{ selectedRecord.status === "posted" ? "Posted" : "Draft" }}
            </el-tag>
          </div>
        </div>

        <!-- Employee List -->
        <div class="details-section">
          <h4>Employee List</h4>
          <div v-if="loadingEmployees" class="loading-state">
            <el-icon class="is-loading"><Loading /></el-icon>
            <span>Loading employees...</span>
          </div>
          <div v-else-if="employeeDetails.length === 0" class="empty-state">
            <p>No employees found for this payroll.</p>
          </div>
          <div v-else class="employee-details-table">
            <el-table
              :data="employeeDetails"
              border
              style="width: 100%"
              :header-cell-style="{
                background: '#f8fafc',
                color: '#374151',
                fontWeight: '600',
                borderBottom: '2px solid #e5e7eb',
              }"
              :cell-style="{ borderBottom: '1px solid #f3f4f6' }"
              stripe
            >
              <el-table-column
                prop="employee_no"
                label="Employee #"
                width="120"
              >
                <template #default="scope">
                  <div class="employee-no">
                    {{ scope.row.employee_no || "N/A" }}
                  </div>
                </template>
              </el-table-column>

              <el-table-column
                prop="name"
                label="Employee Name"
                min-width="200"
              >
                <template #default="scope">
                  <div class="employee-name">{{ scope.row.name || "N/A" }}</div>
                </template>
              </el-table-column>

              <el-table-column prop="position" label="Position" min-width="180">
                <template #default="scope">
                  <div class="position-name">
                    {{ scope.row.position || "N/A" }}
                  </div>
                </template>
              </el-table-column>

              <el-table-column
                prop="salary"
                label="Monthly Salary"
                width="150"
                align="right"
              >
                <template #default="scope">
                  <span class="salary-amount"
                    >₱{{ formatCurrency(scope.row.salary) }}</span
                  >
                </template>
              </el-table-column>

              <el-table-column
                prop="amount"
                label="Bonus Amount"
                width="150"
                align="right"
              >
                <template #default="scope">
                  <span class="bonus-amount"
                    >₱{{ formatCurrency(scope.row.amount) }}</span
                  >
                </template>
              </el-table-column>
            </el-table>

            <!-- Summary -->
            <div class="summary-section">
              <div class="summary-item">
                <label>Total Employees:</label>
                <span>{{ employeeDetails.length }}</span>
              </div>
              <div class="summary-item">
                <label>Total Bonus Amount:</label>
                <span class="total-amount"
                  >₱{{ formatCurrency(totalBonusAmount) }}</span
                >
              </div>
            </div>
          </div>
        </div>
      </div>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, computed, watch } from "vue";
import { ElMessage, ElMessageBox } from "element-plus";
import { Loading } from "@element-plus/icons-vue";

const props = defineProps({
  data: {
    type: Array,
    default: () => [],
  },
  loading: {
    type: Boolean,
    default: false,
  },
  searchQuery: {
    type: String,
    default: "",
  },
  statusFilter: {
    type: String,
    default: "",
  },
});

const emit = defineEmits([
  "edit",
  "delete",
  "view",
  "post",
  "unpost",
  "bulk-post",
  "bulk-report",
  "load-employee-details",
]);

// Reactive data
const selectedRecords = ref([]);
const selectAll = ref(false);
const showDetailsDialog = ref(false);
const selectedRecord = ref(null);
const employeeDetails = ref([]);
const loadingEmployees = ref(false);

// Computed properties
const filteredData = computed(() => {
  let filtered = props.data;

  // Apply search filter
  if (props.searchQuery) {
    const query = props.searchQuery.toLowerCase();
    filtered = filtered.filter(
      (item) =>
        (item.extra_bonus_type &&
          item.extra_bonus_type.toLowerCase().includes(query)) ||
        (item.department && item.department.toLowerCase().includes(query)) ||
        (item.year_id && item.year_id.toString().includes(query))
    );
  }

  // Apply status filter
  if (props.statusFilter) {
    filtered = filtered.filter((item) => item.status === props.statusFilter);
  }

  return filtered;
});

const listPage = ref(1);
const listPageSize = ref(10);
const totalListItems = computed(() => filteredData.value.length);
const paginatedData = computed(() => {
  const start = (listPage.value - 1) * listPageSize.value;
  const end = start + listPageSize.value;
  return filteredData.value.slice(start, end);
});
const handleListSizeChange = (newSize) => {
  listPageSize.value = newSize;
  listPage.value = 1;
};
const handleListPageChange = (newPage) => {
  listPage.value = newPage;
};
watch(
  () => [props.data, props.searchQuery, props.statusFilter],
  () => {
    listPage.value = 1;
  },
  { deep: true }
);

const totalBonusAmount = computed(() => {
  return employeeDetails.value.reduce((total, emp) => {
    const amount = parseFloat(emp.amount) || 0;
    return total + amount;
  }, 0);
});

// Methods
const formatCurrency = (amount) => {
  if (!amount) return "0.00";
  return parseFloat(amount).toLocaleString("en-US", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
};
const handleSelectionChange = (selection) => {
  selectedRecords.value = selection;
  selectAll.value =
    selection.length === filteredData.value.length &&
    filteredData.value.length > 0;
};

const handleSelectAll = (checked) => {
  if (checked) {
    selectedRecords.value = [...filteredData.value];
  } else {
    selectedRecords.value = [];
  }
};

const viewDetails = async (record) => {
  selectedRecord.value = record;
  showDetailsDialog.value = true;

  // Load employee details for this payroll
  try {
    loadingEmployees.value = true;
    employeeDetails.value = [];
    emit("load-employee-details", record);
  } catch (error) {
    console.error("Failed to load employee details:", error);
    employeeDetails.value = [];
  } finally {
    loadingEmployees.value = false;
  }

  emit("view", record);
};

const editRecord = (record) => {
  emit("edit", record);
};

const postRecord = async (record) => {
  try {
    await ElMessageBox.confirm(
      `Are you sure you want to post the extra bonus payroll for ${record.extra_bonus_type} - ${record.department}?`,
      "Confirm Post",
      {
        confirmButtonText: "Post",
        cancelButtonText: "Cancel",
        type: "warning",
      }
    );
    emit("post", record);
  } catch {
    // User cancelled
  }
};

const unpostRecord = async (record) => {
  try {
    await ElMessageBox.confirm(
      `Are you sure you want to unpost the extra bonus payroll for ${record.extra_bonus_type} - ${record.department}?`,
      "Confirm Unpost",
      {
        confirmButtonText: "Unpost",
        cancelButtonText: "Cancel",
        type: "warning",
      }
    );
    emit("unpost", record);
  } catch {
    // User cancelled
  }
};

const deleteRecord = async (record) => {
  try {
    await ElMessageBox.confirm(
      `Are you sure you want to delete the extra bonus payroll for ${record.extra_bonus_type} - ${record.department}?`,
      "Confirm Delete",
      {
        confirmButtonText: "Delete",
        cancelButtonText: "Cancel",
        type: "warning",
      }
    );
    emit("delete", record);
  } catch {
    // User cancelled
  }
};

const bulkPost = () => {
  const draftRecords = selectedRecords.value.filter(
    (r) => r.status !== "posted"
  );
  if (draftRecords.length === 0) {
    ElMessage.warning("No draft records selected for posting");
    return;
  }
  emit("bulk-post", draftRecords);
};

const bulkGenerateReport = () => {
  emit("bulk-report", selectedRecords.value);
};

const clearSelection = () => {
  selectedRecords.value = [];
  selectAll.value = false;
};

// Expose methods for parent component
defineExpose({
  setEmployeeDetails: (employees) => {
    employeeDetails.value = employees || [];
  },
  setLoadingEmployees: (loading) => {
    loadingEmployees.value = loading;
  },
});

// Watch for data changes to reset selection
watch(
  () => props.data,
  () => {
    selectedRecords.value = [];
    selectAll.value = false;
  }
);
</script>

<style scoped>
.extra-bonus-list {
  background: #ffffff;
}

.table-container {
  overflow-x: auto;
}

.enhanced-table {
  font-size: 14px;
}

.enhanced-table :deep(.el-table__header) {
  background: #f8fafc;
}

.enhanced-table :deep(.el-table__row:hover) {
  background: #f8fafc;
}

.bonus-type-cell,
.department-cell {
  padding: 4px 0;
}

.bonus-type-name {
  font-weight: 500;
  color: #374151;
}

.department-name {
  color: #6b7280;
  font-size: 13px;
}

.year-cell {
  display: flex;
  justify-content: center;
  align-items: center;
}

.year-badge {
  background: #e5e7eb;
  color: #374151;
  padding: 4px 8px;
  border-radius: 4px;
  font-weight: 600;
  font-size: 12px;
}

.action-buttons {
  display: flex;
  gap: 4px;
  justify-content: center;
  flex-wrap: nowrap; /* Keep buttons side-by-side on zoom */
  white-space: nowrap;
}

.action-buttons .el-button {
  padding: 6px;
  min-width: 32px;
}

.action-buttons :deep(.el-button__content) {
  white-space: nowrap;
}

.empty-state {
  text-align: center;
  padding: 60px 20px;
  color: #6b7280;
}

.empty-icon {
  font-size: 48px;
  color: #d1d5db;
  margin-bottom: 16px;
}

.empty-state h3 {
  margin: 0 0 8px 0;
  color: #374151;
  font-weight: 600;
}

.empty-state p {
  margin: 0;
  font-size: 14px;
}

.bulk-actions {
  background: #f3f4f6;
  border-top: 1px solid #e5e7eb;
  padding: 12px 20px;
}

.bulk-actions-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.selected-count {
  font-weight: 600;
  color: #374151;
  font-size: 14px;
}

.bulk-buttons {
  display: flex;
  gap: 8px;
}

.details-content {
  padding: 20px 0;
}

.details-section {
  margin-bottom: 30px;
}

.details-section h4 {
  margin: 0 0 16px 0;
  color: #374151;
  font-weight: 600;
  font-size: 16px;
  border-bottom: 2px solid #e5e7eb;
  padding-bottom: 8px;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 0;
  border-bottom: 1px solid #f3f4f6;
}

.detail-row:last-child {
  border-bottom: none;
}

.detail-row label {
  font-weight: 600;
  color: #374151;
  min-width: 140px;
}

.detail-row span {
  color: #6b7280;
  text-align: right;
}

.loading-state {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 40px;
  color: #6b7280;
}

.loading-state .el-icon {
  margin-right: 8px;
  font-size: 18px;
}

.employee-details-table {
  margin-top: 16px;
}

.employee-no {
  font-weight: 600;
  color: #1f2937;
  font-size: 13px;
}

.employee-name {
  font-weight: 500;
  color: #374151;
}

.position-name {
  color: #6b7280;
  font-size: 13px;
}

.salary-amount {
  font-weight: 600;
  color: #374151;
}

.bonus-amount {
  font-weight: 700;
  color: #059669;
}

.summary-section {
  display: flex;
  justify-content: flex-end;
  gap: 30px;
  padding: 16px 20px;
  background: #f8fafc;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
  margin-top: 16px;
}

.summary-item {
  display: flex;
  align-items: center;
  gap: 8px;
}

.summary-item label {
  font-weight: 600;
  color: #374151;
}

.summary-item span {
  color: #6b7280;
}

.total-amount {
  font-weight: 700;
  color: #059669 !important;
  font-size: 16px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .bulk-actions-content {
    flex-direction: column;
    gap: 12px;
    align-items: stretch;
  }

  .bulk-buttons {
    justify-content: center;
  }

  .detail-row {
    flex-direction: column;
    align-items: flex-start;
    gap: 4px;
  }

  .detail-row span {
    text-align: left;
  }

  .action-buttons {
    gap: 2px;
  }

  .action-buttons .el-button {
    font-size: 12px;
    padding: 4px;
  }
}

.pagination-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  background: #f8fafc;
  border-top: 1px solid #e5e7eb;
}

.pagination-info {
  color: #6b7280;
  font-size: 14px;
}

.pagination {
  margin: 0;
}
</style>
