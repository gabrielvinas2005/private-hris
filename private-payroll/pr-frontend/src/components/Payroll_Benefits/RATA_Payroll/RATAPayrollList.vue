<template>
  <div class="rata-payroll-list benefit-table-card">
    <div class="export-buttons">
      <el-button @click="handlePrint" :icon="Printer" plain> Print </el-button>
      <el-button @click="handleExcel" :icon="Download" plain> Excel </el-button>
      <el-button @click="handlePDF" :icon="Document" plain> PDF </el-button>
      <el-button @click="toggleColumnVisibility" :icon="Setting" plain>
        Column Visibility
      </el-button>
    </div>

    <!-- RATA Payroll Table -->
    <div class="table-container benefit-table-container">
      <el-table
        :data="paginatedDisplayedRows"
        style="width: 100%"
        v-loading="loading"
        stripe
        border
        class="benefit-table"
        @sort-change="handleSortChange"
      >
        <el-table-column prop="id" label="ID" width="80" sortable />
        <!-- <el-table-column prop="branch" label="Branch" min-width="150" sortable>
          <template #default="scope">
            {{ scope.row.branch || "N/A" }}
          </template>
        </el-table-column> -->
        <el-table-column
          prop="period"
          label="Period"
          width="180"
          sortable="custom"
        >
          <template #default="scope">
            {{ scope.row.month }} {{ scope.row.year_id }}
          </template>
        </el-table-column>
        <el-table-column
          prop="rata_type_id"
          label="Type"
          min-width="200"
          sortable
        >
          <template #default="scope">
            {{ scope.row.rata_type_name || `Type #${scope.row.rata_type_id}` }}
          </template>
        </el-table-column>
        <el-table-column
          prop="posted"
          label="Status"
          align="center"
          width="120"
          sortable
        >
          <template #default="scope">
            <el-tag :type="scope.row.posted ? 'success' : 'warning'">
              {{ scope.row.posted ? "Posted" : "Draft" }}
            </el-tag>
          </template>
        </el-table-column>

        <el-table-column
          label="Actions"
          min-width="220"
          align="center"
          fixed="right"
        >
          <template #default="scope">
            <div class="actions-cell">
              <!-- View/Report shown when posted -->
              <el-button
                plain
                type="primary"
                @click="$emit('detail', scope.row)"
              >
                <el-icon><Document /></el-icon>
                Detail
              </el-button>

              <!-- Edit only when not posted -->
              <el-button
                v-if="!scope.row.posted"
                plain
                type="success"
                @click="$emit('edit', scope.row)"
              >
                <el-icon><Edit /></el-icon>
                Edit
              </el-button>

              <!-- Post/Unpost -->
              <el-button
                plain
                :type="scope.row.posted ? 'warning' : 'success'"
                @click="togglePosting(scope.row)"
                :loading="processing[scope.row.id]"
              >
                <el-icon><Check /></el-icon>
                {{ scope.row.posted ? "Unpost" : "Post" }}
              </el-button>

              <!-- Delete -->
              <el-button
                plain
                type="danger"
                @click="handleDelete(scope.row)"
                :loading="deletingId === scope.row.id"
              >
                <el-icon><Delete /></el-icon>
                Delete
              </el-button>
            </div>
          </template>
        </el-table-column>
      </el-table>

      <!-- Pagination -->
      <div class="pagination-container" v-if="displayedRows.length > 0">
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
        v-if="!loading && (rows || []).length === 0"
        description="No RATA payroll records found"
      />

      <!-- Error Alert -->
      <el-alert
        v-if="error"
        :title="error"
        type="error"
        show-icon
        closable
        @close="clearError && clearError()"
        class="mt-4"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from "vue";
import { ElMessage, ElMessageBox } from "element-plus";
import {
  Edit,
  Check,
  Document,
  Printer,
  Download,
  Setting,
  Delete,
} from "@element-plus/icons-vue";

// Emits
defineEmits(["edit", "detail", "delete"]);

// Props from parent view
const props = defineProps({
  rows: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  error: { type: [String, Object], default: "" },
  filters: { type: Object, default: () => ({}) },
  clearError: { type: Function, default: null },
  processFn: { type: Function, default: null },
  deleteFn: { type: Function, default: null },
});

const rows = computed(() => props.rows || []);
const loading = computed(() => props.loading);
const error = computed(() => props.error);
const filters = computed(() => props.filters || {});

// State
const processing = ref({});
const deletingId = ref(null);
const sortState = ref({ prop: "", order: "" });
const currentPage = ref(1);
const pageSize = ref(10);

// Months
const months = [
  "January",
  "February",
  "March",
  "April",
  "May",
  "June",
  "July",
  "August",
  "September",
  "October",
  "November",
  "December",
];

// Methods
const togglePosting = async (row) => {
  try {
    const action = row.posted ? "unpost" : "post";
    const typeId = row.posted ? 0 : 1;

    await ElMessageBox.confirm(
      `Are you sure you want to ${action} this RATA payroll?`,
      "Confirm Action",
      {
        confirmButtonText: action === "post" ? "Post" : "Unpost",
        cancelButtonText: "Cancel",
        type: "warning",
      },
    );

    processing.value[row.id] = true;

    const result = props.processFn
      ? await props.processFn(row.id, typeId)
      : { message: `RATA payroll ${action}ed` };

    // Inform and optimistically update local row state like Macco
    ElMessage.success(
      result.message || `RATA payroll ${action}ed successfully`,
    );

    // Toggle local state without full reload
    const newPosted = typeId === 1; // 1 => post, 0 => unpost
    row.posted = newPosted;

    // Ensure reactivity by updating the array entry
    const idx = rows.value.findIndex((r) => r.id === row.id);
    if (idx !== -1) rows.value[idx] = { ...rows.value[idx], posted: newPosted };
  } catch (err) {
    if (err !== "cancel") {
      console.error(
        `Failed to ${row.posted ? "unpost" : "post"} RATA payroll:`,
        err,
      );
      ElMessage.error(
        err.response?.data?.message ||
          `Failed to ${row.posted ? "unpost" : "post"} RATA payroll`,
      );
    }
  } finally {
    processing.value[row.id] = false;
  }
};

const handleDelete = async (row) => {
  if (!props.deleteFn) return;
  try {
    const message = row.posted
      ? `This RATA payroll (${row.month} ${row.year_id}) is posted. Are you sure you want to delete it? This will remove it from the database.`
      : `Are you sure you want to delete this RATA payroll (${row.month} ${row.year_id})? This action cannot be undone.`;
    await ElMessageBox.confirm(message, "Delete RATA Payroll", {
      confirmButtonText: "Delete",
      cancelButtonText: "Cancel",
      type: "warning",
    });

    deletingId.value = row.id;
    await props.deleteFn(row.id);
    ElMessage.success("RATA payroll deleted successfully");
    // List is refreshed by composable after delete
  } catch (err) {
    if (err !== "cancel") {
      console.error("Failed to delete RATA payroll:", err);
      ElMessage.error(
        err.response?.data?.message || "Failed to delete RATA payroll",
      );
    }
  } finally {
    deletingId.value = null;
  }
};

const formatDate = (dateString) => {
  if (!dateString) return "N/A";
  return new Date(dateString).toLocaleDateString();
};

const handleSortChange = ({ prop, order }) => {
  try {
    if (!order) {
      sortState.value = { prop: "", order: "" };
      return;
    }

    sortState.value = { prop, order };
    const direction = order === "ascending" ? 1 : -1;

    const monthIndex = (m) => {
      const idx = months.findIndex(
        (mm) => String(mm).toLowerCase() === String(m).toLowerCase(),
      );
      return idx === -1 ? 0 : idx;
    };

    const sorter = (a, b) => {
      let va;
      let vb;

      if (prop === "period") {
        // Sort by year then month
        const ay = Number(a.year_id || 0);
        const by = Number(b.year_id || 0);
        if (ay !== by) return (ay - by) * direction;
        return (monthIndex(a.month) - monthIndex(b.month)) * direction;
      }

      if (prop === "rata_type_id") {
        va = Number(a.rata_type_id || 0);
        vb = Number(b.rata_type_id || 0);
        return (va - vb) * direction;
      }

      if (prop === "posted") {
        va = a.posted ? 1 : 0;
        vb = b.posted ? 1 : 0;
        return (va - vb) * direction;
      }

      if (prop === "created_at") {
        va = a.created_at ? new Date(a.created_at).getTime() : 0;
        vb = b.created_at ? new Date(b.created_at).getTime() : 0;
        return (va - vb) * direction;
      }

      // default string compare
      va = String(a[prop] ?? "").toLowerCase();
      vb = String(b[prop] ?? "").toLowerCase();
      if (va < vb) return -1 * direction;
      if (va > vb) return 1 * direction;
      return 0;
    };
    // Sorting is applied in computed displayedRows
  } catch (e) {
    console.error("Sort error:", e);
  }
};

const totalItems = computed(() => displayedRows.value.length);

const paginatedDisplayedRows = computed(() => {
  const start = (currentPage.value - 1) * pageSize.value;
  const end = start + pageSize.value;
  return displayedRows.value.slice(start, end);
});

const displayedRows = computed(() => {
  let data = [...(rows.value || [])];

  // Helpers declared before use
  const monthIndex = (m) => {
    const idx = months.findIndex(
      (mm) => String(mm).toLowerCase() === String(m).toLowerCase(),
    );
    return idx === -1 ? 0 : idx;
  };
  const monthToId = (m) => monthIndex(m) + 1;

  // Filter by branch
  // if (filters.value?.branchId) {
  //   data = data.filter(
  //     (r) => String(r.branch) === String(filters.value.branchId),
  //   );
  // }
  // Filter by month
  if (filters.value?.monthId) {
    data = data.filter(
      (r) =>
        Number(r.month_id || monthToId(r.month)) ===
        Number(filters.value.monthId),
    );
  }
  // Filter by year
  if (filters.value?.yearId) {
    data = data.filter(
      (r) => Number(r.year_id) === Number(filters.value.yearId),
    );
  }

  // Apply sort
  const { prop, order } = sortState.value;
  if (!prop || !order) return data;

  const direction = order === "ascending" ? 1 : -1;

  return data.sort((a, b) => {
    if (prop === "period") {
      const ay = Number(a.year_id || 0);
      const by = Number(b.year_id || 0);
      if (ay !== by) return (ay - by) * direction;
      return (monthIndex(a.month) - monthIndex(b.month)) * direction;
    }
    if (prop === "rata_type_id") {
      return (
        (Number(a.rata_type_id || 0) - Number(b.rata_type_id || 0)) * direction
      );
    }
    if (prop === "posted") {
      return ((a.posted ? 1 : 0) - (b.posted ? 1 : 0)) * direction;
    }
    if (prop === "created_at") {
      const va = a.created_at ? new Date(a.created_at).getTime() : 0;
      const vb = b.created_at ? new Date(b.created_at).getTime() : 0;
      return (va - vb) * direction;
    }
    const va = String(a[prop] ?? "").toLowerCase();
    const vb = String(b[prop] ?? "").toLowerCase();
    if (va < vb) return -1 * direction;
    if (va > vb) return 1 * direction;
    return 0;
  });
});

watch(
  () => [rows.value, filters.value],
  () => {
    currentPage.value = 1;
  },
  { deep: true },
);

const handleSizeChange = (newSize) => {
  pageSize.value = newSize;
  currentPage.value = 1;
};

const handleCurrentChange = (newPage) => {
  currentPage.value = newPage;
};

// Export methods
const handlePrint = () => {
  console.log("Print functionality not implemented");
};

const handleExcel = () => {
  console.log("Excel export functionality not implemented");
};

const handlePDF = () => {
  console.log("PDF export functionality not implemented");
};

const toggleColumnVisibility = () => {
  console.log("Column visibility toggle functionality not implemented");
};
</script>

<style scoped>
.rata-payroll-list {
  width: 100%;
  background: #ffffff;
  border-radius: 12px;
  box-shadow:
    0 1px 3px 0 rgba(0, 0, 0, 0.1),
    0 1px 2px 0 rgba(0, 0, 0, 0.06);
  overflow: hidden;
}

.mb-4 {
  margin-bottom: 16px;
}

.mt-4 {
  margin-top: 16px;
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

.actions-cell {
  display: flex;
  gap: 6px;
  justify-content: center;
  align-items: center;
  flex-wrap: nowrap;
  white-space: nowrap;
}

.actions-cell :deep(.el-button__content) {
  white-space: nowrap;
}
</style>
