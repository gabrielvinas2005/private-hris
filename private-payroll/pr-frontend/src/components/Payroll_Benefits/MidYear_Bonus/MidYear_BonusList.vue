<template>
  <div class="midyear-bonus-list benefit-table-card">
    <!-- Disclaimer -->
    <div class="midyear-disclaimer">
      <span
        >The aggregate of 4 months service rendered is at fixed value of 120
        days.</span
      >
    </div>

    <!-- Enhanced Table with better styling -->
    <div class="table-container benefit-table-container">
      <el-table
        :data="paginatedData"
        style="width: 100%"
        v-loading="loading"
        @selection-change="handleSelectionChange"
        :header-cell-style="{
          background: '#f8fafc',
          color: '#6b7280',
          fontWeight: '500',
          borderBottom: '1px solid #e5e7eb',
        }"
        :cell-style="{ borderBottom: '1px solid #f3f4f6', color: '#374151' }"
        stripe
        class="enhanced-table benefit-table"
      >
        <el-table-column type="selection" width="60" align="center">
          <template #header>
            <el-checkbox v-model="selectAll" @change="handleSelectAll" />
          </template>
        </el-table-column>

        <el-table-column
          prop="photo"
          label=""
          width="72"
          align="center"
          class-name="avatar-col"
        >
          <template #default="scope">
            <div class="photo-cell">
              <el-avatar
                v-if="scope.row.photo"
                :src="scope.row.photo"
                :size="32"
                shape="circle"
              />
              <el-avatar
                v-else
                :size="32"
                shape="circle"
                class="default-avatar"
              >
                <span class="avatar-initials">
                  {{ getInitials(scope.row.name) }}
                </span>
              </el-avatar>
            </div>
          </template>
        </el-table-column>

        <el-table-column
          prop="employee_no"
          label="Employee No"
          min-width="120"
          class-name="mono-col"
          show-overflow-tooltip
        >
          <template #default="scope">
            <div class="employee-cell">
              <div class="employee-no">
                {{ scope.row.employee_no || "N/A" }}
              </div>
            </div>
          </template>
        </el-table-column>

        <el-table-column
          prop="name"
          label="Employee"
          min-width="230"
          class-name="employee-main-col"
          show-overflow-tooltip
        >
          <template #default="scope">
            <div class="name-cell">
              <div class="employee-name">{{ scope.row.name || "N/A" }}</div>
            </div>
          </template>
        </el-table-column>

        <el-table-column
          prop="department"
          label="Division"
          min-width="260"
          class-name="department-col"
          show-overflow-tooltip
        >
          <template #default="scope">
            <div class="department-cell">
              <div class="department-name">
                {{ scope.row.department || "N/A" }}
              </div>
            </div>
          </template>
        </el-table-column>

        <el-table-column
          prop="position"
          label="Position"
          min-width="210"
          class-name="position-col"
          show-overflow-tooltip
        >
          <template #default="scope">
            <div class="position-cell">
              <div class="position-name">{{ scope.row.position || "N/A" }}</div>
            </div>
          </template>
        </el-table-column>

        <el-table-column label="Service" min-width="110" align="left">
          <template #default="scope">
            <div class="service-cell">
              <span class="service-text">{{
                formatServiceCompact(scope.row.days_present)
              }}</span>
            </div>
          </template>
        </el-table-column>

        <el-table-column
          prop="salary"
          label="Monthly Salary"
          min-width="140"
          align="right"
          class-name="num-col"
        >
          <template #default="scope">
            <div class="salary-cell">
              <span class="salary-amount"
                >₱{{ formatCurrency(scope.row.salary) }}</span
              >
            </div>
          </template>
        </el-table-column>

        <el-table-column
          prop="bonus_amount"
          label="Bonus Amount"
          min-width="130"
          align="right"
          class-name="num-col bonus-col"
        >
          <template #default="scope">
            <div class="bonus-cell">
              <span
                class="bonus-amount"
                style="color: #059669; font-weight: 700"
                >₱{{ formatCurrency(scope.row.bonus_amount) }}</span
              >
            </div>
          </template>
        </el-table-column>

        <el-table-column
          label="Actions"
          min-width="170"
          align="center"
          fixed="right"
          class-name="actions-col"
        >
          <!-- <template #default="scope">
            <div class="action-buttons">
              <el-button
                type="primary"
                size="small"
                @click="viewDetails(scope.row)"
                title="View Details"
              >
                Details
              </el-button>
              <el-button
                type="danger"
                size="small"
                @click="deleteRecord(scope.row)"
                title="Delete"
                v-if="scope.row.status !== 'posted'"
              >
                Delete
              </el-button>
            </div>
          </template> -->
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
      <h3>No Mid-Year Bonus Records Found</h3>
      <p>
        No bonus records match your current filters. Try adjusting your search
        criteria.
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
            icon="el-icon-check"
            @click="bulkPost"
            :disabled="selectedRecords.some((r) => r.status === 'posted')"
          >
            Post Selected
          </el-button>
          <el-button
            type="primary"
            icon="el-icon-document"
            @click="bulkGenerateReport"
          >
            Generate Report
          </el-button>
          <el-button type="info" icon="el-icon-close" @click="clearSelection">
            Clear Selection
          </el-button>
        </div>
      </div>
    </div>

    <!-- View Details Dialog -->
    <el-dialog
      v-model="showDetailsDialog"
      title="Mid-Year Bonus Details"
      width="600px"
      :close-on-click-modal="false"
      @closed="cancelDetailsEdit"
    >
      <div v-if="selectedRecord" class="details-content">
        <div class="detail-row">
          <label>Employee Number:</label>
          <span>{{ selectedRecord.employee_no || "N/A" }}</span>
        </div>
        <div class="detail-row">
          <label>Employee Name:</label>
          <span>{{ selectedRecord.name || "N/A" }}</span>
        </div>
        <div class="detail-row">
          <label>Department:</label>
          <span>{{ selectedRecord.department || "N/A" }}</span>
        </div>
        <div class="detail-row">
          <label>Position:</label>
          <span>{{ selectedRecord.position || "N/A" }}</span>
        </div>
        <div class="detail-row">
          <label>Basic Salary:</label>
          <span>₱{{ formatCurrency(selectedRecord.salary) }}</span>
        </div>
        <div class="detail-row">
          <label>Service rendered:</label>
          <span class="service-detail">{{
            formatServiceDuration(selectedRecord.days_present)
          }}</span>
        </div>
        <div class="detail-row">
          <label>Bonus Amount:</label>
          <span class="bonus-highlight detail-value-with-edit">
            <el-button
              v-if="selectedRecord.id != null && !detailsEditMode"
              type="primary"
              link
              size="small"
              class="detail-edit-btn"
              @click="startDetailsEdit"
            >
              Edit
            </el-button>
            <el-input-number
              v-if="detailsEditMode"
              v-model="editBonusAmount"
              :min="0"
              :precision="2"
              :controls="false"
              size="small"
              class="detail-amount-input"
            />
            <span v-else
              >₱{{ formatCurrency(selectedRecord.bonus_amount) }}</span
            >
          </span>
        </div>
        <div v-if="detailsEditMode" class="detail-row detail-actions">
          <label></label>
          <span class="detail-action-buttons">
            <el-button size="small" @click="cancelDetailsEdit"
              >Cancel</el-button
            >
            <el-button type="primary" size="small" @click="saveDetailsAmounts">
              Save
            </el-button>
          </span>
        </div>
        <div class="detail-row">
          <label>Year:</label>
          <span>{{ selectedRecord.years }}</span>
        </div>
        <div class="detail-row">
          <label>Status:</label>
          <el-tag
            :type="selectedRecord.status === 'posted' ? 'success' : 'warning'"
            size="small"
          >
            {{ selectedRecord.status === "posted" ? "Posted" : "Draft" }}
          </el-tag>
        </div>
      </div>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, computed, watch } from "vue";
import { ElMessage, ElMessageBox } from "element-plus";

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
  "bulk-post",
  "bulk-report",
  "update",
]);

// Reactive data
const selectedRecords = ref([]);
const selectAll = ref(false);
const showDetailsDialog = ref(false);
const selectedRecord = ref(null);
const detailsEditMode = ref(false);
const editBonusAmount = ref(0);

// List pagination
const listPage = ref(1);
const listPageSize = ref(10);

// Computed properties
const filteredData = computed(() => {
  let filtered = props.data;

  // Apply search filter
  if (props.searchQuery) {
    const query = props.searchQuery.toLowerCase();
    filtered = filtered.filter(
      (item) =>
        (item.employee_no && item.employee_no.toLowerCase().includes(query)) ||
        (item.name && item.name.toLowerCase().includes(query)) ||
        (item.department && item.department.toLowerCase().includes(query)) ||
        (item.position && item.position.toLowerCase().includes(query)) ||
        (item.years && item.years.toString().includes(query)),
    );
  }

  // Apply status filter
  if (props.statusFilter) {
    filtered = filtered.filter((item) => item.status === props.statusFilter);
  }

  return filtered;
});

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
  { deep: true },
);

// Methods
const formatCurrency = (amount) => {
  if (amount == null || amount === "") return "0.00";
  return parseFloat(amount).toLocaleString("en-US", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
};

const getInitials = (name) => {
  if (!name) return "NA";
  return name
    .split(/[,\s]+/)
    .filter(Boolean)
    .map((part) => part[0])
    .join("")
    .toUpperCase()
    .slice(0, 2);
};

/** Format days_present as "X months Y days" (compact for table) */
const formatServiceCompact = (daysPresent) => {
  const d = Number(daysPresent) || 0;
  if (d === 0) return "0 d";
  const months = Math.floor(d / 30);
  const days = d % 30;
  if (months === 0) return `${days} d`;
  if (days === 0) return `${months} mo`;
  return `${months} mo ${days} d`;
};

/** Full format for details: "1 month 2 days (which is 32 days_present)" */
const formatServiceDuration = (daysPresent) => {
  const d = Number(daysPresent) || 0;
  if (d === 0) return "0 d";
  const months = Math.floor(d / 30);
  const days = d % 30;
  if (months === 0) {
    return `${d} d`;
  }
  if (days === 0) {
    return `${months} mo`;
  }
  return `${months} mo ${days} d`;
};

const startDetailsEdit = () => {
  if (!selectedRecord.value) return;
  editBonusAmount.value = Number(selectedRecord.value.bonus_amount) || 0;
  detailsEditMode.value = true;
};

const cancelDetailsEdit = () => {
  detailsEditMode.value = false;
};

const saveDetailsAmounts = async () => {
  if (!selectedRecord.value || selectedRecord.value.id == null) return;
  try {
    await ElMessageBox.confirm(
      "Are you sure you want to change? Processing again will reset the amounts. Continue?",
      "Confirm change",
      {
        confirmButtonText: "Continue",
        cancelButtonText: "Cancel",
        type: "warning",
      },
    );
  } catch {
    return;
  }
  emit("update", {
    id: selectedRecord.value.id,
    bonus_amount: Number(editBonusAmount.value) || 0,
  });
  detailsEditMode.value = false;
  showDetailsDialog.value = false;
};

const formatPercentage = (value) => {
  if (value === null || value === undefined || value === "") return "0%";

  if (typeof value === "number") {
    return `${value.toFixed(2)}%`;
  }

  const numeric = parseFloat(String(value).replace("%", ""));
  if (Number.isNaN(numeric)) {
    return value || "0%";
  }

  return `${numeric.toFixed(2)}%`;
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

const viewDetails = (record) => {
  selectedRecord.value = record;
  detailsEditMode.value = false;
  showDetailsDialog.value = true;
  emit("view", record);
};

const editRecord = (record) => {
  emit("edit", record);
};

const deleteRecord = async (record) => {
  try {
    await ElMessageBox.confirm(
      `Are you sure you want to delete the mid-year bonus record for ${record.name}?`,
      "Confirm Delete",
      {
        confirmButtonText: "Delete",
        cancelButtonText: "Cancel",
        type: "warning",
      },
    );
    emit("delete", record);
  } catch {
    // User cancelled
  }
};

const bulkPost = () => {
  const draftRecords = selectedRecords.value.filter(
    (r) => r.status !== "posted",
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

// Watch for data changes to reset selection
watch(
  () => props.data,
  () => {
    selectedRecords.value = [];
    selectAll.value = false;
  },
);
</script>

<style scoped>
.midyear-bonus-list {
  background: #ffffff;
}

.midyear-disclaimer {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 16px;
  margin-bottom: 16px;
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  border-radius: 8px;
  font-size: 13px;
  color: #1e40af;
}

.disclaimer-icon {
  font-size: 16px;
}

.service-cell {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.service-text {
  font-weight: 500;
  color: #374151;
}

.service-days {
  font-size: 12px;
  color: #6b7280;
}

.detail-value-with-edit {
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.detail-edit-btn {
  margin-right: 4px;
  vertical-align: middle;
}

.detail-amount-input {
  width: 140px;
}

.detail-amount-input :deep(.el-input__wrapper) {
  font-weight: 600;
}

.service-detail {
  color: #6b7280;
  font-size: 14px;
}

.detail-actions {
  padding-top: 16px;
  margin-top: 8px;
  border-top: 1px solid #e5e7eb;
}

.detail-action-buttons {
  display: flex;
  gap: 8px;
}

.table-container {
  overflow-x: auto;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  background: #fff;
}

.enhanced-table {
  font-size: 12px;
  min-width: 1300px;
}

.enhanced-table :deep(.el-table__header) {
  background: #f8fafc;
}

.enhanced-table :deep(.el-table__header th) {
  padding: 10px 12px;
  font-size: 11px;
  letter-spacing: 0.2px;
}

.enhanced-table :deep(.el-table__cell) {
  padding: 10px 12px;
}

.enhanced-table :deep(.el-table__row:hover) {
  background: #f8fafc;
}

.employee-cell,
.name-cell,
.department-cell,
.position-cell {
  padding: 4px 0;
}

.default-avatar {
  background: #e6f1fb;
  color: #0c447c;
  font-weight: 600;
}

.avatar-initials {
  font-size: 11px;
  letter-spacing: 0.2px;
}

.employee-no {
  font-weight: 600;
  color: #1f2937;
  font-size: 12px;
  font-family: monospace;
}

.employee-name {
  font-weight: 500;
  color: #111827;
  font-size: 12px;
}

.department-name,
.position-name {
  color: #6b7280;
  font-size: 12px;
  line-height: 1.35;
}

.salary-cell {
  text-align: right;
}

.bonus-cell {
  text-align: right;
}

.salary-amount {
  font-weight: 600;
  color: #374151;
}

.bonus-amount {
  font-weight: 700;
  color: #059669;
}

.year-cell {
  display: flex;
  justify-content: center;
  align-items: center;
}

.action-buttons {
  display: flex;
  gap: 6px;
  justify-content: center;
  flex-wrap: nowrap; /* Keep buttons side-by-side on zoom */
  white-space: nowrap;
}

.action-buttons .el-button {
  min-width: 64px;
  font-size: 11px;
  padding: 5px 10px;
  border-radius: 7px;
}

.action-buttons :deep(.el-button__content) {
  white-space: nowrap;
}

.enhanced-table :deep(.num-col .cell) {
  text-align: right;
  font-family: monospace;
  font-size: 12px;
}

.enhanced-table :deep(.employee-main-col .cell),
.enhanced-table :deep(.department-col .cell),
.enhanced-table :deep(.position-col .cell) {
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

.bonus-highlight {
  color: #059669 !important;
  font-weight: 700;
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
