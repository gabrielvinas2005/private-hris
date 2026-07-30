<template>
  <PageScaffold
    title="Loyalty Award"
    subtitle="Manage loyalty awards for long-serving employees"
    :breadcrumbs="[
      { label: 'Payroll Module', to: '/' },
      { label: 'Payroll Benefits', to: '/payroll-benefits' },
      { label: 'Loyalty Award' },
    ]"
  >
    <template #actions>
      <el-button
        v-if="!showForm"
        type="primary"
        @click="handleAddNew"
        :loading="loading"
        size="default"
      >
        Add Loyalty Award
      </el-button>
      <el-button
        v-if="!showForm"
        type="success"
        @click="loadLoyaltyAwardData"
        :loading="loading"
        size="default"
      >
        Refresh
      </el-button>
    </template>

    <!-- Enhanced Filters -->
    <div v-if="!showForm" class="filters-container">
      <el-form :inline="true" class="enhanced-filters">
        <el-form-item label="Search">
          <el-input
            v-model="searchQuery"
            placeholder="Search by branch or month"
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

    <!-- Loyalty Award List -->
    <LoyaltyAwardList
      v-if="!showForm"
      ref="listRef"
      :loyalty-award-data="filteredLoyaltyAwardData"
      :loading="loading"
      @edit="handleEdit"
      @detail="handleDetail"
      @view-employees="handleViewEmployees"
      @post="handlePost"
      @unpost="handleUnpost"
      @delete="handleDelete"
      @print="handlePrint"
      @print-ors="handlePrintORS"
      @print-dv="handlePrintDV"
      @remove-employee="handleRemoveEmployee"
      @load-employee-data="handleLoadEmployeeData"
      @add-new="handleAddNew"
    />

    <!-- Create/Edit Form -->
    <LoyaltyAwardForm
      v-if="showForm"
      :edit-data="editData"
      :is-detail-view="isDetailView"
      @saved="handleFormSaved"
      @close="handleFormClose"
    />

    <!-- Report Generation Dialog -->
    <LoyaltyAwardReport
      v-model="showReportDialog"
      @report-generated="handleReportGenerated"
      @close="handleReportClose"
    />
  </PageScaffold>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { ElMessageBox } from "element-plus";
import PageScaffold from "../../../components/PageScaffold.vue";
import LoyaltyAwardList from "../../../components/Payroll_Benefits/Loyalty_Award/LoyaltyAwardList.vue";
import LoyaltyAwardForm from "../../../components/Payroll_Benefits/Loyalty_Award/LoyaltyAwardForm.vue";
import LoyaltyAwardReport from "../../../components/Payroll_Benefits/Loyalty_Award/LoyaltyAwardReport.vue";
import { useLoyaltyAward } from "../../../Composables/useLoyaltyAwardBenefits.js";
import { loyaltyAwardApi } from "../../../services/api.js";

// Composables
const {
  loading,
  filteredLoyaltyAwardData,
  searchQuery,
  statusFilter,
  loadLoyaltyAwardList,
  postLoyaltyAward,
  unpostLoyaltyAward,
  deleteLoyaltyAward,
  generateReport,
  loadFormData,
  transformLoyaltyAwardData,
  handleSearch,
  handleFilter,
} = useLoyaltyAward();

// Local state
const showForm = ref(false);
const showReportDialog = ref(false);
const editData = ref(null);
const isDetailView = ref(false);
const listRef = ref(null);

// Methods
const loadLoyaltyAwardData = async () => {
  try {
    await loadLoyaltyAwardList();
  } catch (error) {
    console.error("Error loading loyalty award data:", error);
  }
};

// handleSearch and handleFilter are now provided by the composable

const resetFilters = () => {
  searchQuery.value = "";
  statusFilter.value = "";
};

const handleAddNew = () => {
  editData.value = null;
  isDetailView.value = false;
  showForm.value = true;
};

const handleEdit = (loyaltyAward) => {
  editData.value = loyaltyAward;
  isDetailView.value = false;
  showForm.value = true;
};

const handleDetail = (loyaltyAward) => {
  editData.value = loyaltyAward;
  isDetailView.value = true;
  showForm.value = true;
};

const handleViewEmployees = async (loyaltyAward) => {
  if (listRef.value) {
    await listRef.value.showEmployees(loyaltyAward);
  }
};

const handlePost = async (loyaltyAward) => {
  try {
    await postLoyaltyAward(loyaltyAward.id);
    // Refresh the list to get updated data
    await loadLoyaltyAwardData();
    // Apply current filters to update the filtered data
    handleFilter();
  } catch (error) {
    console.error("Error posting loyalty award:", error);
  }
};

const handleUnpost = async (loyaltyAward) => {
  try {
    await unpostLoyaltyAward(loyaltyAward.id);
    // Refresh the list to get updated data
    await loadLoyaltyAwardData();
    // Apply current filters to update the filtered data
    handleFilter();
  } catch (error) {
    console.error("Error unposting loyalty award:", error);
  }
};

const handleDelete = async (loyaltyAward) => {
  try {
    // Show confirmation dialog
    const confirmed = await ElMessageBox.confirm(
      `Are you sure you want to delete the loyalty award for ${loyaltyAward.branch} - ${loyaltyAward.month} ${loyaltyAward.year}?`,
      "Confirm Delete",
      {
        confirmButtonText: "Delete",
        cancelButtonText: "Cancel",
        type: "warning",
        confirmButtonClass: "el-button--danger",
      }
    );

    if (confirmed) {
      await deleteLoyaltyAward(loyaltyAward.id);
      // Refresh the list to get updated data
      await loadLoyaltyAwardData();
      // Apply current filters to update the filtered data
      handleFilter();
    }
  } catch (error) {
    if (error !== "cancel") {
      console.error("Error deleting loyalty award:", error);
    }
  }
};

const handlePrint = async (loyaltyAward) => {
  try {
    const requestData = {
      branch_id: loyaltyAward.branch_id,
      payroll_period_id: loyaltyAward.id,
    };
    await generateReport(requestData);
  } catch (error) {
    console.error("Error generating report:", error);
  }
};

const handleRemoveEmployee = (employee) => {
  // Refresh the employee data after removal
  if (listRef.value) {
    loadLoyaltyAwardData();
  }
};

const handleLoadEmployeeData = async (loyaltyAwardId, callback) => {
  try {
    const response = await loadFormData(loyaltyAwardId);
    callback(response.employees);
  } catch (error) {
    console.error("Error loading employee data:", error);
  }
};

const handleFormSaved = () => {
  loadLoyaltyAwardData(); // Refresh the list
  showForm.value = false; // Close form after saving
};

const handleFormClose = () => {
  editData.value = null;
  isDetailView.value = false;
  showForm.value = false;
};

const handleReportGenerated = () => {
  // Report was generated successfully
};

const handleReportClose = () => {
  // Report dialog was closed
};

// ORS/DV printing
const handlePrintORS = async (row) => {
  try {
    const resp = await loyaltyAwardApi.generateORSReport(row.id);
    const blob = new Blob([resp.data], { type: "application/pdf" });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = `ors_loyalty_${row.id}_${new Date().toISOString().split("T")[0]}.pdf`;
    document.body.appendChild(a);
    a.click();
    a.remove();
    window.URL.revokeObjectURL(url);
  } catch (e) {
    console.error("Failed to print ORS (Loyalty)", e);
  }
};

const handlePrintDV = async (row) => {
  try {
    const resp = await loyaltyAwardApi.generateDVReport(row.id);
    const blob = new Blob([resp.data], { type: "application/pdf" });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = `dv_loyalty_${row.id}_${new Date().toISOString().split("T")[0]}.pdf`;
    document.body.appendChild(a);
    a.click();
    a.remove();
    window.URL.revokeObjectURL(url);
  } catch (e) {
    console.error("Failed to print DV (Loyalty)", e);
  }
};

onMounted(() => {
  loadLoyaltyAwardData();
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

/* Fade transition for list/form switching */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
