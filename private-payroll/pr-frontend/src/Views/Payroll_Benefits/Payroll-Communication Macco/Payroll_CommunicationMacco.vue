<template>
  <PageScaffold
    title="Reimbursement Communication Expenses"
    subtitle="Manage reimbursement communication expenses for employees"
    :breadcrumbs="[
      { label: 'Payroll Module', to: '/' },
      { label: 'Payroll Benefits', to: '/payroll-benefits' },
      { label: 'Reimbursement Communication Expenses' },
    ]"
  >
    <template #actions>
      <el-button
        type="primary"
        @click="showCreateDialog = true"
        :loading="loading"
        size="default"
      >
        Add Reimbursement
      </el-button>
      <el-button
        type="success"
        @click="loadReimbursementData"
        :loading="loading"
        size="default"
      >
        Refresh
      </el-button>
    </template>

    <!-- Enhanced Filters -->
    <div class="filters-container">
      <el-form :inline="true" class="enhanced-filters">
        <el-form-item label="Search">
          <el-input
            v-model="searchQuery"
            placeholder="Search by division, month, or year"
            clearable
            style="width: 280px"
            @input="handleSearch"
          />
        </el-form-item>
        <el-form-item label="Status">
          <el-select
            v-model="statusFilter"
            placeholder="All Status"
            style="width: 140px"
            @change="handleFilter"
            clearable
            class="status-select"
          >
            <el-option label="All" value="" />
            <el-option label="Posted" value="posted">
              <el-tag type="success" size="small">Posted</el-tag>
            </el-option>
            <el-option label="Draft" value="draft">
              <el-tag type="warning" size="small">Draft</el-tag>
            </el-option>
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleFilter" class="filter-btn">
            Filter
          </el-button>
          <el-button @click="resetFilters" class="reset-btn"> Reset </el-button>
        </el-form-item>
      </el-form>
    </div>

    <!-- Summary Cards -->
    <div class="summary-stats mb-4" v-if="!showCreateDialog">
      <el-row :gutter="16">
        <el-col :span="6">
          <el-card class="stat-card">
            <div class="stat-content">
              <div class="stat-label">Total Entries</div>
              <div class="stat-value">{{ totalEntries }}</div>
            </div>
          </el-card>
        </el-col>

        <el-col :span="6">
          <el-card class="stat-card">
            <div class="stat-content">
              <div class="stat-label">Total Amount</div>
              <div class="stat-value">₱{{ formatCurrency(totalAmount) }}</div>
            </div>
          </el-card>
        </el-col>

        <el-col :span="6">
          <el-card class="stat-card">
            <div class="stat-content">
              <div class="stat-label">Posted Amount</div>
              <div class="stat-value">₱{{ formatCurrency(totalPostedAmount) }}</div>
            </div>
          </el-card>
        </el-col>

        <el-col :span="6">
          <el-card class="stat-card">
            <div class="stat-content">
              <div class="stat-label">Draft Amount</div>
              <div class="stat-value">₱{{ formatCurrency(totalDraftAmount) }}</div>
              <div class="stat-subtext">employees: {{ totalEmployees }}</div>
            </div>
          </el-card>
        </el-col>
      </el-row>
    </div>

    <!-- Create/Edit/Detail Form (Inline) -->
    <ReimbursementCommunicationForm
      v-if="showCreateDialog"
      :reimbursement-data="editData"
      :is-detail-view="isDetailView"
      @saved="handleFormSaved"
      @close="handleFormClose"
      @deleted="handleFormDeleted"
    />

    <!-- Reimbursement Communication Expenses List -->
    <ReimbursementCommunicationList
      v-if="!showCreateDialog"
      ref="listRef"
      :reimbursement-data="filteredReimbursementData"
      :loading="loading"
      @edit="handleEdit"
      @detail="handleDetail"
      @post="handlePost"
      @unpost="handleUnpost"
      @delete="handleDelete"
      @add-new="handleAddNew"
    />
  </PageScaffold>
</template>

<script setup>
import { ref, onMounted, computed } from "vue";
import { ElMessageBox } from "element-plus";
import PageScaffold from "../../../components/PageScaffold.vue";
import ReimbursementCommunicationList from "../../../components/Payroll_Benefits/Payroll_CommunicationMacco/ReimbursementCommunicationList.vue";
import ReimbursementCommunicationForm from "../../../components/Payroll_Benefits/Payroll_CommunicationMacco/ReimbursementCommunicationForm.vue";
import { useReimbursementCommunication } from "../../../Composables/useReimbursementCommunication.js";

// Composables
const {
  loading,
  reimbursementList,
  loadReimbursementList,
  transformReimbursementData,
  processReimbursement,
  deleteReimbursement,
} = useReimbursementCommunication();

// Local state
const showCreateDialog = ref(false);
const editData = ref(null);
const isDetailView = ref(false);
const searchQuery = ref("");
const statusFilter = ref("");
const listRef = ref(null);

// Computed properties
const filteredReimbursementData = computed(() => {
  let filtered = reimbursementList.value;

  // Ensure filtered is an array
  if (!Array.isArray(filtered)) {
    console.warn(
      "filteredReimbursementData: reimbursementList is not an array:",
      filtered
    );
    return [];
  }

  // Apply search filter
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    filtered = filtered.filter(
      (item) =>
        item.division?.toLowerCase().includes(query) ||
        item.department?.toLowerCase().includes(query) ||
        item.month?.toLowerCase().includes(query) ||
        item.year?.toString().includes(query)
    );
  }

  // Apply status filter
  if (statusFilter.value) {
    const isPosted = statusFilter.value === "posted";
    filtered = filtered.filter((item) => {
      const itemIsPosted =
        item.posted === true ||
        item.posted === 1 ||
        item.posted === "1" ||
        item.posted === "true";
      return itemIsPosted === isPosted;
    });
  }

  return transformReimbursementData(filtered);
});

const formatCurrency = (value) => {
  const n = Number(value ?? 0);
  return (Number.isFinite(n) ? n : 0).toLocaleString(undefined, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
};

const totalEntries = computed(() => filteredReimbursementData.value.length);

const totalAmount = computed(() => {
  return filteredReimbursementData.value.reduce(
    (sum, row) => sum + Number(row.totalAmount ?? 0),
    0
  );
});

const totalPostedAmount = computed(() => {
  return filteredReimbursementData.value
    .filter((r) => r.posted === true)
    .reduce((sum, row) => sum + Number(row.totalAmount ?? 0), 0);
});

const totalDraftAmount = computed(() => {
  return filteredReimbursementData.value
    .filter((r) => !r.posted)
    .reduce((sum, row) => sum + Number(row.totalAmount ?? 0), 0);
});

const totalEmployees = computed(() => {
  return filteredReimbursementData.value.reduce((sum, row) => {
    const count = Number(row.employeeCount ?? row.employee_count ?? 0);
    return sum + (Number.isFinite(count) ? count : 0);
  }, 0);
});

// Methods
const loadReimbursementData = async () => {
  try {
    await loadReimbursementList();

    // Ensure reimbursementList is an array after loading
    if (!Array.isArray(reimbursementList.value)) {
      console.warn(
        "reimbursementList is not an array after loading, initializing as empty array"
      );
      reimbursementList.value = [];
    }
  } catch (error) {
    console.error("Error loading reimbursement data:", error);
    // Ensure we have an empty array even on error
    reimbursementList.value = [];
  }
};

const handleSearch = () => {
  // Search is handled by computed property
};

const handleFilter = () => {
  // Filter is handled by computed property
};

const resetFilters = () => {
  searchQuery.value = "";
  statusFilter.value = "";
};

const handleFormSaved = (data) => {
  showCreateDialog.value = false;
  editData.value = null;
  isDetailView.value = false;
  loadReimbursementData();
};

const handleFormClose = () => {
  showCreateDialog.value = false;
  editData.value = null;
  isDetailView.value = false;
};

const handleFormDeleted = (deletedId) => {
  // Optimistically remove from current list for instant UI feedback
  if (Array.isArray(reimbursementList.value)) {
    reimbursementList.value = reimbursementList.value.filter(
      (item) => Number(item.id) !== Number(deletedId)
    );
  }
  // Close form and ensure fresh data
  showCreateDialog.value = false;
  editData.value = null;
  isDetailView.value = false;
  // Reload to keep counts/totals accurate
  loadReimbursementData();
};

const handleEdit = (row) => {
  editData.value = row;
  isDetailView.value = false;
  showCreateDialog.value = true;
};

const handleDetail = (row) => {
  editData.value = row;
  isDetailView.value = true;
  showCreateDialog.value = true;
};

const handlePost = async (row) => {
  try {
    // Send minimal data for status change only
    const employeeData = {};

    await processReimbursement(row.id, 1, employeeData); // 1 for post
    // Optimistic UI: flip locally, then refresh
    row.posted = true;
    await loadReimbursementData();
  } catch (error) {
    console.error("Error posting reimbursement:", error);
  }
};

const handleUnpost = async (row) => {
  try {
    // Send minimal data for status change only
    const employeeData = {};

    await processReimbursement(row.id, 0, employeeData); // 0 for unpost
    // Optimistic UI: flip locally, then refresh
    row.posted = false;
    await loadReimbursementData();
  } catch (error) {
    console.error("Error unposting reimbursement:", error);
  }
};

const handleDelete = async (row) => {
  const id = row?.id;
  if (id == null) return;
  try {
    await ElMessageBox.confirm(
      "Are you sure you want to delete this reimbursement? This cannot be undone.",
      "Delete Reimbursement",
      {
        confirmButtonText: "Delete",
        cancelButtonText: "Cancel",
        type: "warning",
      }
    );
    await deleteReimbursement(id);
    if (editData.value && Number(editData.value.id) === Number(id)) {
      showCreateDialog.value = false;
      editData.value = null;
      isDetailView.value = false;
    }
    await loadReimbursementData();
  } catch (error) {
    if (error !== "cancel") {
      console.error("Error deleting reimbursement:", error);
    }
  }
};

const handleAddNew = (row) => {
  editData.value = row;
  isDetailView.value = false;
  showCreateDialog.value = true;
};

onMounted(() => {
  loadReimbursementData();
});
</script>

<style scoped>
.mb-4 {
  margin-bottom: 1.5rem;
}

.filters-container {
  background: #f8f9fa;
  padding: 16px;
  border-radius: 8px;
  margin-bottom: 20px;
  border: 1px solid #e4e7ed;
}

.enhanced-filters {
  margin: 0;
}

.enhanced-filters .el-form-item {
  margin-bottom: 0;
  margin-right: 16px;
}

.status-select {
  min-width: 140px;
}

.filter-btn,
.reset-btn {
  margin-left: 8px;
}

.filter-btn {
  background-color: #409eff;
  border-color: #409eff;
  color: #fff;
}

.filter-btn:hover {
  background-color: #66b1ff;
  border-color: #66b1ff;
}

.reset-btn {
  background-color: #909399;
  border-color: #909399;
  color: #fff;
}

.reset-btn:hover {
  background-color: #a6a9ad;
  border-color: #a6a9ad;
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

/* Responsive design */
@media (max-width: 768px) {
  .filters-container {
    padding: 12px;
  }

  .enhanced-filters {
    flex-direction: column;
    align-items: stretch;
  }

  .enhanced-filters .el-form-item {
    margin-right: 0;
    margin-bottom: 12px;
  }

  .enhanced-filters .el-form-item:last-child {
    margin-bottom: 0;
  }

  .filter-btn,
  .reset-btn {
    margin-left: 0;
    margin-right: 8px;
  }
}

@media (max-width: 480px) {
  .filters-container {
    padding: 8px;
  }

  .enhanced-filters .el-form-item {
    margin-bottom: 8px;
  }
}
</style>
