<template>
  <div class="reimbursement-communication-list benefit-table-card">
    <!-- Enhanced Data Table -->
    <div class="export-buttons">
      <el-button @click="handlePrint" :icon="Printer" plain> Print </el-button>
      <el-button @click="handleExcel" :icon="Download" plain> Excel </el-button>
      <el-button @click="handlePDF" :icon="Document" plain> PDF </el-button>
      <el-button @click="toggleColumnVisibility" :icon="Setting" plain>
        Column Visibility
      </el-button>
    </div>
    <div class="benefit-table-container">
      <el-table
        :data="paginatedData"
        v-loading="loading"
        style="width: 100%"
        class="enhanced-table benefit-table"
        :row-class-name="getRowClassName"
        border
        stripe
      >
        <!-- Division Column -->
        <el-table-column
          prop="division"
          label="Division"
          min-width="180"
          sortable="custom"
          show-overflow-tooltip
        >
          <template #default="scope">
            <div class="division-cell">
              {{ scope.row.division ?? scope.row.department }}
            </div>
          </template>
        </el-table-column>

        <!-- Month Column -->
        <el-table-column
          prop="month"
          label="Month"
          width="120"
          sortable="custom"
          show-overflow-tooltip
        >
          <template #default="scope">
            <div class="month-cell">
              <span class="month-text">{{ scope.row.month }}</span>
            </div>
          </template>
        </el-table-column>

        <el-table-column
          prop="year"
          label="Year"
          width="120"
          sortable="custom"
          show-overflow-tooltip
        >
          <template #default="scope">
            <div class="year-cell">
              <span class="year-text">{{ scope.row.year }}</span>
            </div>
          </template>
        </el-table-column>

        <!-- Status Column -->
        <el-table-column
          label="Status"
          width="100"
          sortable="custom"
          align="center"
        >
          <template #default="scope">
            <el-tag
              :type="
                scope.row.posted === true ||
                scope.row.posted === 1 ||
                scope.row.posted === '1' ||
                scope.row.posted === 'true'
                  ? 'success'
                  : 'warning'
              "
              effect="light"
            >
              {{
                scope.row.posted === true ||
                scope.row.posted === 1 ||
                scope.row.posted === "1" ||
                scope.row.posted === "true"
                  ? "Posted"
                  : "Draft"
              }}
            </el-tag>
          </template>
        </el-table-column>

        <!-- Employee Count Column -->
        <el-table-column label="Employees" width="100" align="center">
          <template #default="scope">
            <el-tag effect="light" class="employee-badge">
              {{ scope.row.employeeCount ?? scope.row.employee_count ?? 0 }}
            </el-tag>
          </template>
        </el-table-column>

        <!-- Total Amount Column -->
        <el-table-column
          label="Total Amount"
          width="150"
          align="right"
          sortable="custom"
          sort-by="total_amount"
        >
          <template #default="scope">
            <div class="amount-cell">
              <span class="amount-value"
                >₱{{
                  Number(
                    scope.row.totalAmount ?? scope.row.total_amount ?? 0,
                  ).toLocaleString()
                }}</span
              >
            </div>
          </template>
        </el-table-column>

        <!-- Actions Column -->
        <el-table-column
          label="Actions"
          min-width="130"
          fixed="right"
          align="center"
        >
          <template #default="scope">
            <div class="action-buttons">
              <!-- View Details Button -->
              <el-button
                type="info"
                @click="handleDetail(scope.row)"
                :icon="View"
                plain
              >
                View
              </el-button>

              <!-- Edit Button (only for draft) -->
              <el-button
                v-if="!scope.row.posted"
                type="primary"
                @click="handleEdit(scope.row)"
                :icon="Edit"
                plain
              >
                Edit
              </el-button>

              <!-- Post/Unpost Button -->
              <el-button
                :type="scope.row.posted ? 'warning' : 'success'"
                @click="handlePost(scope.row)"
                :icon="scope.row.posted ? Close : Check"
                plain
              >
                {{ scope.row.posted ? "Unpost" : "Post" }}
              </el-button>

              <!-- Delete Button (only for draft) -->
              <el-button
                v-if="!isPosted(scope.row)"
                type="danger"
                plain
                @click="handleDelete(scope.row)"
                :icon="Delete"
              >
                Delete
              </el-button>
            </div>
          </template>
        </el-table-column>
      </el-table>
    </div>

    <!-- Pagination -->
    <div class="pagination-container" v-if="reimbursementData.length > 0">
      <div class="pagination-info">
        <span class="text-sm text-gray-600">
          Showing {{ (currentPage - 1) * pageSize + 1 }} to
          {{ Math.min(currentPage * pageSize, totalItems) }} of
          {{ totalItems }} entries
        </span>
      </div>
      <el-pagination
        :current-page="currentPage"
        :page-size="pageSize"
        :page-sizes="[10, 20, 50, 100]"
        :total="totalItems"
        layout="prev, pager, next, sizes"
        @size-change="handleSizeChange"
        @current-change="handleCurrentChange"
        class="pagination"
      />
    </div>

    <!-- Empty State -->
    <el-empty
      v-if="!loading && reimbursementData.length === 0"
      description="No reimbursement communication expenses found"
      :image-size="120"
      class="benefit-table-empty"
    >
      <template #description>
        <p>No reimbursement communication expenses records found.</p>
        <p>Click "Add Reimbursement" to create a new record.</p>
      </template>
    </el-empty>

    <!-- Loading State -->
    <div v-if="loading" class="loading-container">
      <el-skeleton :rows="5" animated />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from "vue";
import {
  View,
  Printer,
  Download,
  Document,
  Setting,
  Edit,
  Close,
  Check,
  Delete,
} from "@element-plus/icons-vue";

const props = defineProps({
  reimbursementData: {
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
  "post",
  "unpost",
  "delete",
  "add-new",
  "print",
  "export-excel",
  "export-pdf",
  "toggle-columns",
]);

// Computed properties
const sortedData = computed(() => {
  return [...props.reimbursementData].sort((a, b) => {
    // Sort by created date descending by default
    return new Date(b.created_at || 0) - new Date(a.created_at || 0);
  });
});

// Pagination
const currentPage = ref(1);
const pageSize = ref(10);
const totalItems = computed(() => sortedData.value.length);
const paginatedData = computed(() => {
  const start = (currentPage.value - 1) * pageSize.value;
  const end = start + pageSize.value;
  return sortedData.value.slice(start, end);
});
const handleSizeChange = (newSize) => {
  pageSize.value = newSize;
  currentPage.value = 1;
};
const handleCurrentChange = (newPage) => {
  currentPage.value = newPage;
};
watch(
  () => props.reimbursementData,
  () => {
    currentPage.value = 1;
  },
  { deep: true }
);

// Methods
const handleEdit = (row) => {
  emit("edit", row);
};

const isPosted = (row) => {
  if (!row) return false;
  const v = row.posted;
  return v === true || v === 1 || v === "1" || v === "true";
};

const handleDetail = (row) => {
  emit("detail", row);
};

const handleDelete = (row) => {
  emit("delete", row);
};

const handlePost = (row) => {
  if (row.posted) {
    emit("unpost", row);
  } else {
    emit("post", row);
  }
};

const handleAddNew = (row) => {
  emit("add-new", row);
};

const handleSortChange = ({ column, prop, order }) => {
  // Handle sorting if needed
  console.log("Sort changed:", { column, prop, order });
};

const getRowClassName = ({ row }) => {
  if (row.posted) {
    return "posted-row";
  }
  return "draft-row";
};

const formatDate = (dateString) => {
  if (!dateString) return "-";

  try {
    const date = new Date(dateString);
    return date.toLocaleDateString("en-US", {
      month: "short",
      day: "numeric",
      year: "numeric",
    });
  } catch (error) {
    return "-";
  }
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
</script>

<style scoped>
.reimbursement-communication-list {
  background: #fff;
}

.month-cell {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
}

.month-text {
  font-weight: 500;
  color: #303133;
}

.year-text {
  font-size: 12px;
  font-weight: 500;
  color: #909399;
  margin-top: 2px;
}

.amount-cell {
  display: flex;
  justify-content: flex-end;
  align-items: center;
}

.amount-value {
  font-weight: 600;
  color: #303133;
  font-size: 14px;
}

.date-cell {
  font-size: 12px;
  color: #606266;
}

.action-buttons {
  display: flex;
  gap: 4px;
  flex-wrap: nowrap; /* Keep buttons side-by-side on zoom */
  justify-content: center;
  white-space: nowrap;
}

.employee-badge {
  margin: 0;
}

.loading-container {
  padding: 20px;
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

/* Button styling */
:deep(.el-button--small) {
  padding: 5px 12px;
  font-size: 12px;
}

:deep(.el-button--small .el-icon) {
  margin-right: 4px;
}

/* Responsive design */
@media (max-width: 768px) {
  .action-buttons {
    flex-direction: row; /* Avoid vertical stacking */
    gap: 2px;
  }

  .action-buttons .el-button {
    width: auto;
    margin: 0;
  }
}

/* Prevent Element Plus button internals from wrapping */
.action-buttons :deep(.el-button__content) {
  white-space: nowrap;
}

@media (max-width: 480px) {
  :deep(.el-table__body-wrapper) {
    overflow-x: auto;
  }

  .enhanced-table {
    min-width: 600px;
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
