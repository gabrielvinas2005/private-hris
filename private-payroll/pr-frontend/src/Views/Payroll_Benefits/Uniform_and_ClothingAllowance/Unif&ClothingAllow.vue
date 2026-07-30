<template>
  <PageScaffold
    title="Uniform and Clothing Allowance"
    subtitle="Manage uniform and clothing allowance for employees"
    :breadcrumbs="[
      { label: 'Payroll Module', to: '/' },
      { label: 'Payroll Benefits', to: '/payroll-benefits' },
      { label: 'Uniform and Clothing Allowance' },
    ]"
  >
    <template #actions>
      <el-button
        type="primary"
        @click="showCreateDialog = true"
        :loading="loading"
        size="default"
      >
        Add Uniform Allowance
      </el-button>
      <el-button
        type="success"
        @click="loadAllowanceData"
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
            placeholder="Search by branch, month, or year"
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

    <!-- Create/Edit/Detail Form (Inline) -->
    <UniformClothingAllowanceForm
      v-if="showCreateDialog"
      :allowance-data="editData"
      :is-detail-view="isDetailView"
      @saved="handleFormSaved"
      @close="handleFormClose"
    />

    <!-- Uniform Allowance List -->
    <UniformClothingAllowanceList
      v-if="!showCreateDialog"
      ref="listRef"
      :allowance-data="filteredAllowanceData"
      :loading="loading"
      @edit="handleEdit"
      @detail="handleDetail"
      @post="handlePost"
      @unpost="handleUnpost"
      @delete="handleDelete"
      @add-new="handleAddNew"
      @print-ors="handlePrintORS"
      @print-dv="handlePrintDV"
    />
  </PageScaffold>
</template>

<script setup>
import { ref, onMounted, computed } from "vue";
import { ElMessageBox } from "element-plus";
import PageScaffold from "../../../components/PageScaffold.vue";
import UniformClothingAllowanceList from "../../../components/Payroll_Benefits/Uniform_and_ClothingAllowance/UniformClothingAllowanceList.vue";
import UniformClothingAllowanceForm from "../../../components/Payroll_Benefits/Uniform_and_ClothingAllowance/UniformClothingAllowanceForm.vue";
import { useUniformClothingAllowance } from "../../../Composables/useUniformClothingAllowance.js";
import { uniformClothingAllowanceApi } from "../../../services/api.js";

// Composables
const {
  loading,
  allowanceList,
  loadAllowanceList,
  transformAllowanceData,
  postAllowance,
  unpostAllowance,
  deleteAllowance,
} = useUniformClothingAllowance();

// Local state
const showCreateDialog = ref(false);
const editData = ref(null);
const isDetailView = ref(false);
const searchQuery = ref("");
const statusFilter = ref("");
const listRef = ref(null);

// Computed properties
const filteredAllowanceData = computed(() => {
  let filtered = allowanceList.value;

  // Apply search filter
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    filtered = filtered.filter(
      (item) =>
        item.branch?.toLowerCase().includes(query) ||
        item.month?.toLowerCase().includes(query) ||
        item.year?.toString().includes(query)
    );
  }

  // Apply status filter
  if (statusFilter.value) {
    const isPosted = statusFilter.value === "posted";
    filtered = filtered.filter((item) => item.posted === isPosted);
  }

  return transformAllowanceData(filtered);
});

// Methods
const loadAllowanceData = async () => {
  try {
    await loadAllowanceList();
  } catch (error) {
    console.error("Error loading allowance data:", error);
  }
};

const handleSearch = () => {
  // Search is handled by computed property
};

const handleFilter = () => {
  // Filtering is handled by computed property
};

const resetFilters = () => {
  searchQuery.value = "";
  statusFilter.value = "";
};

const handleAddNew = () => {
  editData.value = null;
  isDetailView.value = false;
  showCreateDialog.value = true;
};

const handleEdit = (allowance) => {
  editData.value = allowance;
  isDetailView.value = false;
  showCreateDialog.value = true;
};

const handleDetail = (allowance) => {
  editData.value = allowance;
  isDetailView.value = true;
  showCreateDialog.value = true;
};

const handlePost = async (id) => {
  try {
    await postAllowance(id);
    await loadAllowanceData(); // Refresh the list

    // Force reactivity update by creating a new array reference
    const currentData = [...allowanceList.value];
    allowanceList.value = [];
    allowanceList.value = currentData;
  } catch (error) {
    console.error("Error posting allowance:", error);
  }
};

const handleUnpost = async (id) => {
  try {
    await unpostAllowance(id);
    await loadAllowanceData(); // Refresh the list

    // Force reactivity update by creating a new array reference
    const currentData = [...allowanceList.value];
    allowanceList.value = [];
    allowanceList.value = currentData;
  } catch (error) {
    console.error("Error unposting allowance:", error);
  }
};

const handleDelete = async (allowance) => {
  try {
    await ElMessageBox.confirm(
      `Delete the uniform allowance for ${allowance.branch} (${allowance.month} ${allowance.year})? This action cannot be undone.`,
      "Delete Uniform Allowance",
      {
        confirmButtonText: "Delete",
        cancelButtonText: "Cancel",
        type: "warning",
      }
    );
    await deleteAllowance(allowance.id);
    await loadAllowanceData();
  } catch (error) {
    if (error !== "cancel") {
      console.error("Error deleting uniform allowance:", error);
    }
  }
};

const handleFormSaved = () => {
  showCreateDialog.value = false;
  editData.value = null;
  loadAllowanceData(); // Refresh the list
};

const handleFormClose = () => {
  showCreateDialog.value = false;
  editData.value = null;
  isDetailView.value = false;
};

// Reports
const handlePrintORS = async (row) => {
  try {
    const resp = await uniformClothingAllowanceApi.generateORSReport(row.id);
    const blob = new Blob([resp.data], { type: "application/pdf" });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = url;
    link.download = `ors_clothing_${row.id}_${new Date().toISOString().split("T")[0]}.pdf`;
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
  } catch (e) {
    console.error("Failed to print ORS (Clothing)", e);
  }
};

const handlePrintDV = async (row) => {
  try {
    const resp = await uniformClothingAllowanceApi.generateDVReport(row.id);
    const blob = new Blob([resp.data], { type: "application/pdf" });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = url;
    link.download = `dv_clothing_${row.id}_${new Date().toISOString().split("T")[0]}.pdf`;
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
  } catch (e) {
    console.error("Failed to print DV (Clothing)", e);
  }
};

onMounted(() => {
  loadAllowanceData();
});
</script>

<style scoped>
/* Filters Container */
.filters-container {
  background: #ffffff;
  border-radius: 12px;
  padding: 20px;
  margin-bottom: 20px;
  box-shadow:
    0 1px 3px 0 rgba(0, 0, 0, 0.1),
    0 1px 2px 0 rgba(0, 0, 0, 0.06);
  border: 1px solid #e5e7eb;
}

.enhanced-filters {
  margin: 0;
}

.enhanced-filters :deep(.el-form-item) {
  margin-right: 20px;
  margin-bottom: 0;
}

.enhanced-filters :deep(.el-form-item__label) {
  font-weight: 600;
  color: #374151;
  font-size: 14px;
}

/* Enhanced Select Styling */
.status-select :deep(.el-input__inner) {
  border-radius: 8px;
  border: 1px solid #d1d5db;
  transition: all 0.2s ease;
  font-size: 14px;
}

.status-select :deep(.el-input__inner:focus) {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Enhanced Button Styling */
.filter-btn {
  border-radius: 8px;
  font-weight: 600;
  padding: 10px 20px;
  transition: all 0.2s ease;
}

.filter-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(59, 130, 246, 0.3);
}

.reset-btn {
  border-radius: 8px;
  font-weight: 600;
  padding: 10px 20px;
  border: 1px solid #d1d5db;
  color: #6b7280;
  transition: all 0.2s ease;
}

.reset-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
  border-color: #9ca3af;
  color: #374151;
}

/* Utility Classes */
.mb-3 {
  margin-bottom: 12px;
}

/* Responsive Design */
@media (max-width: 768px) {
  .filters-container {
    padding: 16px;
  }

  .enhanced-filters :deep(.el-form-item) {
    margin-right: 0;
    margin-bottom: 16px;
    width: 100%;
  }

  .search-input {
    width: 100% !important;
  }

  .status-select {
    width: 100% !important;
  }

  .enhanced-filters :deep(.el-form-item__content) {
    width: 100%;
  }
}

/* Animation for filter container */
.filters-container {
  animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
