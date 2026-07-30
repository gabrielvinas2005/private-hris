<template>
  <div class="uniform-clothing-allowance-list benefit-table-card">
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
        :data="paginatedData"
        border
        style="width: 100%"
        v-loading="loading"
        stripe
        class="enhanced-table benefit-table"
      >
        <!-- <el-table-column prop="branch" label="Branch" min-width="200" /> -->
        <el-table-column
          prop="monthNumber"
          label="Month"
          width="140"
          align="center"
          sortable
        >
          <template #default="scope">
            {{ scope.row.month }}
          </template>
        </el-table-column>
        <el-table-column prop="year" label="Year" width="100" align="center" />
        <el-table-column
          prop="status"
          label="Status"
          min-width="70"
          align="center"
        >
          <template #default="scope">
            <el-tag
              :type="scope.row.posted ? 'success' : 'warning'"
              effect="light"
            >
              {{ scope.row.posted ? "Posted" : "Draft" }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column
          label="Actions"
          min-width="180"
          fixed="right"
          align="center"
        >
          <template #default="scope">
            <div class="action-buttons">
              <el-button
                plain
                type="primary"
                @click.stop="$emit('detail', scope.row)"
              >
                <i class="el-icon-view"></i>
                Detail
              </el-button>

              <el-button
                v-if="!scope.row.posted"
                plain
                type="success"
                @click.stop="$emit('edit', scope.row)"
              >
                <i class="el-icon-edit"></i>
                Edit
              </el-button>

              <el-button
                v-if="!scope.row.posted"
                plain
                type="danger"
                @click.stop="$emit('delete', scope.row)"
              >
                <i class="el-icon-delete"></i>
                Delete
              </el-button>

              <el-button
                plain
                :type="scope.row.posted ? 'warning' : 'success'"
                @click.stop="handleTogglePosting(scope.row)"
                :loading="processingIds.includes(scope.row.id)"
              >
                <i
                  :class="
                    scope.row.posted ? 'el-icon-refresh-left' : 'el-icon-check'
                  "
                ></i>
                {{ scope.row.posted ? "Unpost" : "Post" }}
              </el-button>
            </div>
          </template>
        </el-table-column>
        <el-table-column
          label="Reports"
          width="300"
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
    <div class="pagination-container" v-if="allowanceData.length > 0">
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

    <!-- Enhanced Empty State -->
    <div
      v-if="!loading && allowanceData.length === 0"
      class="empty-state benefit-table-empty"
    >
      <el-empty
        description="No uniform allowance records found"
        :image-size="120"
      >
        <template #image>
          <i class="el-icon-document-copy empty-icon"></i>
        </template>
        <el-button type="primary" @click="$emit('add-new')">
          <i class="el-icon-plus"></i>
          Add First Uniform Allowance
        </el-button>
      </el-empty>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from "vue";
import {
  Search,
  Printer,
  Download,
  Document,
  Setting,
} from "@element-plus/icons-vue";

const props = defineProps({
  allowanceData: {
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
  "post",
  "unpost",
  "delete",
  "add-new",
  "detail",
  "print",
  "print-ors",
  "print-dv",
  "export-excel",
  "export-pdf",
  "toggle-columns",
]);

// Local state
const processingIds = ref([]);
const currentPage = ref(1);
const pageSize = ref(10);
const totalItems = computed(() => props.allowanceData.length);
const paginatedData = computed(() => {
  const start = (currentPage.value - 1) * pageSize.value;
  const end = start + pageSize.value;
  return props.allowanceData.slice(start, end);
});
const handleSizeChange = (newSize) => {
  pageSize.value = newSize;
  currentPage.value = 1;
};
const handleCurrentChange = (newPage) => {
  currentPage.value = newPage;
};
watch(
  () => props.allowanceData,
  () => {
    currentPage.value = 1;
  },
  { deep: true },
);

// Methods
const handleRowClick = (row) => {
  emit("edit", row);
};

const handleTogglePosting = async (row) => {
  processingIds.value.push(row.id);

  try {
    await emit(row.posted ? "unpost" : "post", row.id);
  } finally {
    processingIds.value = processingIds.value.filter((id) => id !== row.id);
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
.uniform-clothing-allowance-list {
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

.enhanced-table ::deep(.el-table__header th) {
  background: #f8fafc !important;
  border-bottom: 2px solid #e5e7eb;
  color: #374151;
  font-weight: 700 !important;
  font-size: 14px;
  padding: 16px 12px;
}

.enhanced-table :deep(.el-table__body tr) {
  transition: all 0.2s ease;
}

.enhanced-table :deep(.el-table__body tr:hover) {
  background-color: #f9fafb !important;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.enhanced-table :deep(.el-table__body td) {
  padding: 16px 12px;
  border-bottom: 1px solid #f3f4f6;
  vertical-align: middle;
}

/* Action Buttons Styling */
.action-buttons {
  display: flex;
  gap: 6px;
  justify-content: center;
  align-items: center;
  flex-wrap: nowrap; /* Keep buttons side-by-side on zoom */
  white-space: nowrap;
}

.action-buttons .el-button {
  border-radius: 6px;
  font-size: 12px;
  font-weight: 500;
  padding: 6px 12px;
  transition: all 0.2s ease;
}

.action-buttons .el-button:hover {
  transform: translateY(-1px);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.action-buttons .el-button.is-loading {
  transform: none;
  box-shadow: none;
}

.action-buttons .el-button i {
  margin-right: 4px;
  font-size: 12px;
}

/* Status Tag Styling */
::deep(.el-tag) {
  font-weight: 500;
  font-size: 11px;
  padding: 4px 8px;
  border: none;
  min-width: 60px;
  text-align: center;
}

::deep(.el-tag--success:not(.is-light)) {
  background-color: #10b981;
  color: white;
}

::deep(.el-tag--warning:not(.is-light)) {
  background-color: #f59e0b;
  color: white;
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
  .action-buttons {
    flex-direction: row; /* Avoid vertical stacking */
    gap: 6px;
  }

  .action-buttons .el-button {
    width: auto;
    min-width: 80px;
  }
}

.action-buttons :deep(.el-button__content) {
  white-space: nowrap;
}

@media (max-width: 768px) {
  .enhanced-table :deep(.el-table__header th),
  .enhanced-table :deep(.el-table__body td) {
    padding: 12px 8px;
    font-size: 13px;
  }
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

/* Export Buttons */
.export-buttons {
  display: flex;
  gap: 5px;
  margin: 14px 0 14px 14px;
  flex-wrap: wrap;
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
