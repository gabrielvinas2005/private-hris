<template>
  <div class="benefit-table-card">
    <div class="benefit-table-container">
      <el-table
        :data="paginatedData"
        border
        stripe
        style="width: 100%"
        v-loading="loading"
        class="benefit-table"
      >
        <!-- <el-table-column prop="branch" label="Branch" align="center" /> -->
        <el-table-column prop="month" label="Month" align="center" />
        <el-table-column prop="year" label="Year" width="100" align="center" />
        <el-table-column label="Status" min-width="120" align="center">
          <template #default="scope">
            <el-tag :type="scope.row.posted ? 'success' : 'danger'">{{
              scope.row.posted ? "Posted" : "Draft"
            }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="Actions" min-width="260" align="center">
          <template #default="scope">
            <div class="benefit-table-actions">
              <el-button type="info" plain @click="$emit('details', scope.row)">
                Details
              </el-button>

              <template v-if="scope.row.posted">
                <el-button
                  type="warning"
                  plain
                  @click="$emit('unpost', scope.row)"
                >
                  Unpost
                </el-button>
              </template>
              <template v-else>
                <el-button
                  type="primary"
                  plain
                  @click="$emit('edit', scope.row)"
                >
                  Edit
                </el-button>
                <el-button
                  type="success"
                  plain
                  @click="$emit('post', scope.row)"
                >
                  Post
                </el-button>
                <el-button
                  type="danger"
                  plain
                  @click="$emit('delete', scope.row)"
                  >Delete</el-button
                >
              </template>
            </div>
          </template>
        </el-table-column>

        <el-table-column label="Print" min-width="200" align="center">
          <template #default="scope">
            <div class="benefit-table-actions">
              <el-button
                type="primary"
                plain
                @click="$emit('print-ors', scope.row)"
              >
                Print ORS
              </el-button>
              <el-button
                type="primary"
                plain
                @click="$emit('print-dv', scope.row)"
              >
                Print DV
              </el-button>
            </div>
          </template>
        </el-table-column>
      </el-table>
    </div>

    <div class="pagination-container" v-if="rows && rows.length > 0">
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

    <el-empty
      v-if="!loading && (!rows || rows.length === 0)"
      description="No records"
      class="benefit-table-empty"
    />
  </div>
</template>

<script setup>
import { ref, computed, watch } from "vue";

const props = defineProps({
  rows: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
});
defineEmits([
  "details",
  "edit",
  "post",
  "unpost",
  "delete",
  "print-ors",
  "print-dv",
]);

const currentPage = ref(1);
const pageSize = ref(10);
const totalItems = computed(() => (props.rows || []).length);
const paginatedData = computed(() => {
  const list = props.rows || [];
  const start = (currentPage.value - 1) * pageSize.value;
  const end = start + pageSize.value;
  return list.slice(start, end);
});
const handleSizeChange = (newSize) => {
  pageSize.value = newSize;
  currentPage.value = 1;
};
const handleCurrentChange = (newPage) => {
  currentPage.value = newPage;
};
watch(
  () => props.rows,
  () => {
    currentPage.value = 1;
  },
  { deep: true },
);
</script>

<style scoped>
.benefit-table-card {
  width: 100%;
  background: #ffffff;
  border-radius: 12px;
  box-shadow:
    0 1px 3px 0 rgba(0, 0, 0, 0.1),
    0 1px 2px 0 rgba(0, 0, 0, 0.06);
  overflow: hidden;
}

.benefit-table-container {
  border-radius: 12px;
  overflow: hidden;
}

.benefit-table {
  border-radius: 12px;
}

.benefit-table :deep(.el-table__header th) {
  background: #f8fafc !important;
  border-bottom: 2px solid #e5e7eb;
  color: #374151;
  font-weight: 600;
  font-size: 14px;
  padding: 14px 12px;
}

.benefit-table :deep(.el-table__body td) {
  padding: 14px 12px;
  border-bottom: 1px solid #f3f4f6;
  vertical-align: middle;
}

.benefit-table :deep(.el-table__body tr:hover) {
  background-color: #f9fafb !important;
}

.benefit-table-actions {
  display: flex;
  gap: 6px;
  justify-content: center;
  align-items: center;
  flex-wrap: nowrap; /* Keep buttons side-by-side on zoom */
}

.benefit-table-actions .el-button {
  min-width: 80px;
  height: 32px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 500;
  padding: 6px 12px;
  transition: all 0.2s ease;
}

.benefit-table-actions .el-button:hover {
  transform: translateY(-1px);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.benefit-table-empty {
  padding: 40px 16px;
}

@media (max-width: 768px) {
  .benefit-table-actions {
    flex-direction: row; /* Avoid vertical stacking */
    gap: 6px;
  }

  .benefit-table-actions .el-button {
    width: auto;
    min-width: 80px;
  }
}

/* Prevent Element Plus button internals from wrapping */
.benefit-table-actions :deep(.el-button__content) {
  white-space: nowrap;
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
