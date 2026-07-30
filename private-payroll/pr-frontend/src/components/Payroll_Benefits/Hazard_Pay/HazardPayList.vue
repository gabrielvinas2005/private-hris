<template>
  <div class="hazard-pay-list benefit-table-card">
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
        <!-- <el-table-column type="selection" width="60" align="center">
          <template #header>
            <el-checkbox v-model="selectAll" @change="handleSelectAll" />
          </template>
        </el-table-column> -->

        <el-table-column prop="department" label="Division" min-width="300">
          <template #default="scope">
            <div class="department-cell">
              <div class="department-name">{{ scope.row.department }}</div>
            </div>
          </template>
        </el-table-column>

        <el-table-column prop="month" label="Month" width="120" align="center">
          <template #default="scope">
            <strong>{{ scope.row.month }}</strong>
          </template>
        </el-table-column>

        <el-table-column prop="year" label="Year" width="100" align="center">
          <template #default="scope">
            <div class="year-cell">
              <span class="year-text">{{ scope.row.year }}</span>
            </div>
          </template>
        </el-table-column>

        <el-table-column
          prop="status"
          label="Status"
          width="120"
          align="center"
        >
          <template #default="scope">
            <el-tag :type="scope.row.posted ? 'success' : 'warning'">
              {{ scope.row.posted ? "Posted" : "Draft" }}
            </el-tag>
          </template>
        </el-table-column>

        <el-table-column
          label="Actions"
          min-width="280"
          fixed="right"
          align="center"
        >
          <template #default="scope">
            <div class="action-buttons">
              <!-- Always show Details button -->
              <el-button
                type="primary"
                plain
                @click="$emit('view-details', scope.row)"
              >
                <i class="el-icon-view"></i>
                Details
              </el-button>

              <!-- Show different buttons based on posted status -->
              <template v-if="scope.row.posted">
                <!-- Posted: Only show Details and Unpost -->
                <el-button
                  type="danger"
                  plain
                  @click="$emit('unpost', scope.row.id, 2)"
                  :loading="loading"
                >
                  <i class="el-icon-close"></i>
                  Unpost
                </el-button>
              </template>
              <template v-else>
                <!-- Not Posted: Show Details, Edit, Post, and Delete -->
                <el-button
                  type="primary"
                  plain
                  @click="$emit('edit', scope.row)"
                >
                  <i class="el-icon-edit"></i>
                  Edit
                </el-button>

                <el-button
                  type="success"
                  plain
                  @click="$emit('post', scope.row.id, 1)"
                  :loading="loading"
                >
                  <i class="el-icon-check"></i>
                  Post
                </el-button>

                <el-button
                  type="danger"
                  plain
                  @click="$emit('delete', scope.row)"
                >
                  <i class="el-icon-delete"></i>
                  Delete
                </el-button>
              </template>
            </div>
          </template>
        </el-table-column>

        <el-table-column
          label="Reports"
          min-width="200"
          fixed="right"
          align="center"
        >
          <template #default="scope">
            <div class="report-buttons">
              <el-button
                size="small"
                type="primary"
                plain
                @click="$emit('print', scope.row)"
                :disabled="!scope.row.posted"
              >
                <i class="el-icon-printer"></i>
                Print
              </el-button>

              <el-button
                size="small"
                type="success"
                plain
                @click="$emit('tabulate', scope.row)"
                :disabled="!scope.row.posted"
              >
                <i class="el-icon-document"></i>
                Tabulate
              </el-button>
            </div>
          </template>
        </el-table-column>
      </el-table>
    </div>

    <!-- Pagination -->
    <div class="pagination-container" v-if="hazardPayData.length > 0">
      <div class="pagination-info">
        <span class="text-sm text-gray-600">
          Showing {{ (currentPage - 1) * pageSize + 1 }} to
          {{ Math.min(currentPage * pageSize, totalItems) }} of
          {{ totalItems }} entries
        </span>
      </div>

      <el-pagination
        :model:current-page="currentPage"
        :model:page-size="pageSize"
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
      v-if="hazardPayData.length === 0 && !loading"
      description="No hazard pay records found"
      class="empty-state benefit-table-empty"
    >
      <template #image>
        <i class="el-icon-document" style="font-size: 64px; color: #c0c4cc"></i>
      </template>
    </el-empty>
  </div>
</template>

<script setup>
import { ref, computed, watch } from "vue";

// Props
const props = defineProps({
  hazardPayData: {
    type: Array,
    default: () => [],
  },
  loading: {
    type: Boolean,
    default: false,
  },
});

// Emits
const emit = defineEmits([
  "view-details",
  "edit",
  "post",
  "unpost",
  "delete",
  "print",
  "tabulate",
  "selection-change",
  "page-change",
  "size-change",
]);

// Local state
const selectAll = ref(false);
const selectedRows = ref([]);
const currentPage = ref(1);
const pageSize = ref(10);

// Computed
const totalItems = computed(() => props.hazardPayData.length);

const paginatedData = computed(() => {
  const start = (currentPage.value - 1) * pageSize.value;
  const end = start + pageSize.value;
  return props.hazardPayData.slice(start, end);
});

// Methods
const handleSelectAll = (checked) => {
  if (checked) {
    selectedRows.value = [...props.hazardPayData];
  } else {
    selectedRows.value = [];
  }
  emit("selection-change", selectedRows.value);
};

const handleSelectionChange = (selection) => {
  selectedRows.value = selection;
  selectAll.value = selection.length === props.hazardPayData.length;
  emit("selection-change", selection);
};

const handleSizeChange = (newSize) => {
  pageSize.value = newSize;
  currentPage.value = 1;
  emit("size-change", newSize);
};

const handleCurrentChange = (newPage) => {
  currentPage.value = newPage;
  emit("page-change", newPage);
};

// Watch for data changes
watch(
  () => props.hazardPayData,
  () => {
    currentPage.value = 1;
    selectAll.value = false;
    selectedRows.value = [];
  },
  { deep: true },
);
</script>

<style scoped>
.hazard-pay-list {
  background: #ffffff;
  border-radius: 12px;
  overflow: hidden;
  box-shadow:
    0 1px 3px 0 rgba(0, 0, 0, 0.1),
    0 1px 2px 0 rgba(0, 0, 0, 0.06);
}

.table-container {
  overflow-x: auto;
}

.enhanced-table {
  border-radius: 12px;
  overflow: hidden;
}

.enhanced-table :deep(.el-table__header) {
  background: #f8fafc;
}

.enhanced-table :deep(.el-table__body tr:hover) {
  background: #f8fafc;
}

.department-cell {
  padding: 4px 0;
}

.department-name {
  font-weight: 500;
  color: #374151;
  font-size: 14px;
}

.year-cell {
  display: flex;
  align-items: center;
  justify-content: center;
}

.year-text {
  font-weight: 500;
  color: #374151;
  font-size: 14px;
}

.action-buttons {
  display: flex;
  gap: 8px;
  flex-wrap: nowrap; /* Keep buttons side-by-side on zoom */
  justify-content: center;
  white-space: nowrap;
}

.report-buttons {
  display: flex;
  gap: 8px;
  flex-wrap: nowrap; /* Keep buttons side-by-side on zoom */
  justify-content: center;
  white-space: nowrap;
}

.action-buttons .el-button,
.report-buttons .el-button {
  border-radius: 6px;
  font-weight: 500;
  font-size: 12px;
  padding: 6px 12px;
  transition: all 0.2s ease;
}

.action-buttons .el-button:hover,
.report-buttons .el-button:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
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

.empty-state {
  padding: 60px 20px;
  background: #ffffff;
}

/* Responsive Design */
@media (max-width: 768px) {
  .action-buttons,
  .report-buttons {
    flex-direction: row; /* Avoid vertical stacking */
    gap: 4px;
  }

  .action-buttons .el-button,
  .report-buttons .el-button {
    width: auto;
    margin: 0;
    min-width: 80px;
  }

  .pagination-container {
    flex-direction: column;
    gap: 16px;
    align-items: stretch;
  }

  .pagination {
    justify-content: center;
  }
}

/* Animation */
.enhanced-table {
  animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Prevent Element Plus button internals from wrapping */
.action-buttons :deep(.el-button__content),
.report-buttons :deep(.el-button__content) {
  white-space: nowrap;
}
</style>
