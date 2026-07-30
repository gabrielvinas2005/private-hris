<template>
  <PageScaffold
    title="Hazard Pay Allowance"
    subtitle="Manage hazard pay allowance for employees working in hazardous conditions"
    :breadcrumbs="[
      { label: 'Payroll Module', to: '/' },
      { label: 'Payroll Benefits', to: '/payroll-benefits' },
      { label: 'Hazard Pay Allowance' },
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
        Add Hazard Pay Allowance
      </el-button>
      <el-button
        v-if="!showForm"
        type="success"
        @click="loadHazardPayData"
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
            placeholder="Search by department or period"
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
            <el-option label="Unposted" value="unposted">
              <el-tag type="warning" size="small">Unposted</el-tag>
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

    <!-- Hazard Pay List -->
    <HazardPayList
      v-if="!showForm"
      ref="listRef"
      :hazard-pay-data="filteredHazardPayData"
      :loading="loading"
      @view-details="handleViewDetails"
      @edit="handleEdit"
      @post="handlePost"
      @unpost="handleUnpost"
      @delete="handleDelete"
      @print="handlePrint"
      @tabulate="handleTabulate"
    />

    <!-- Inline Create/Edit Form -->
    <HazardPayForm
      v-else
      :hazard-id="selectedHazardPayId"
      @saved="handleFormSaved"
      @close="handleFormClose"
    />

    <!-- Employee Management Dialog (legacy) -->
    <!-- Removed in favor of inline management within the form -->
  </PageScaffold>
</template>

<script setup>
import { ref, onMounted, computed } from "vue";
import { ElMessageBox } from "element-plus";
import PageScaffold from "../../../components/PageScaffold.vue";
import HazardPayList from "../../../components/Payroll_Benefits/Hazard_Pay/HazardPayList.vue";
import HazardPayForm from "../../../components/Payroll_Benefits/Hazard_Pay/HazardPayForm.vue";
import { useHazardPay } from "../../../Composables/useHazardPay.js";

// Composables
const {
  loading,
  hazardPayList,
  loadHazardPayList,
  loadHazardPaySetup,
  loadHazardPayForm,
  processHazardPay,
  deleteHazardPayHeader,
  generateHazardPayReport,
  transformHazardPayData,
} = useHazardPay();

// Local state
const showForm = ref(false);
const selectedHazardPayId = ref(0);
const searchQuery = ref("");
const statusFilter = ref("");
const listRef = ref(null);

// Computed properties
const filteredHazardPayData = computed(() => {
  // First transform the data
  let transformed = transformHazardPayData(hazardPayList.value);

  // Apply search filter
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    transformed = transformed.filter(
      (item) =>
        item.department?.toLowerCase().includes(query) ||
        item.month?.toLowerCase().includes(query) ||
        item.year?.toString().includes(query)
    );
  }

  // Apply status filter
  if (statusFilter.value) {
    const isPosted = statusFilter.value === "posted";
    transformed = transformed.filter((item) => item.posted === isPosted);
  }

  return transformed;
});

// Methods
const loadHazardPayData = async () => {
  try {
    await loadHazardPayList();
    await loadHazardPaySetup();
  } catch (error) {
    console.error("Error loading hazard pay data:", error);
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

const handleAddNew = async () => {
  try {
    selectedHazardPayId.value = 0;
    await loadHazardPayForm(0);
    showForm.value = true;
  } catch (error) {
    console.error("Error loading form data for new hazard pay:", error);
  }
};

const handleEdit = (hazardPay) => {
  selectedHazardPayId.value = hazardPay.id;
  showForm.value = true;
};

const handleViewDetails = async (hazardPay) => {
  try {
    selectedHazardPayId.value = hazardPay.id;
    await loadHazardPayForm(hazardPay.id);
    showForm.value = true;
  } catch (error) {
    console.error("Error loading hazard pay details:", error);
  }
};

const handlePost = async (id, typeId) => {
  try {
    await processHazardPay(id, typeId);
    // Add a small delay to ensure database update is complete
    await new Promise((resolve) => setTimeout(resolve, 500));
    // Force refresh the data
    await loadHazardPayList();
    await loadHazardPaySetup();
  } catch (error) {
    console.error("Error posting hazard pay:", error);
  }
};

const handleUnpost = async (id, typeId) => {
  try {
    await processHazardPay(id, typeId);
    // Add a small delay to ensure database update is complete
    await new Promise((resolve) => setTimeout(resolve, 500));
    // Force refresh the data
    await loadHazardPayList();
    await loadHazardPaySetup();
  } catch (error) {
    console.error("Error unposting hazard pay:", error);
  }
};

const handleDelete = async (hazardPay) => {
  try {
    // Show confirmation dialog
    await ElMessageBox.confirm(
      `Are you sure you want to delete the hazard pay record for ${hazardPay.department} - ${hazardPay.month} ${hazardPay.year}? This action cannot be undone and will delete all associated employees.`,
      "Confirm Delete",
      {
        confirmButtonText: "Delete",
        cancelButtonText: "Cancel",
        type: "warning",
        confirmButtonClass: "el-button--danger",
      }
    );

    // If confirmed, delete the record
    await deleteHazardPayHeader(hazardPay.id);
    // Force refresh the data
    await loadHazardPayList();
    await loadHazardPaySetup();
  } catch (error) {
    if (error !== "cancel") {
      console.error("Error deleting hazard pay:", error);
    }
  }
};

const handlePrint = async (hazardPay) => {
  try {
    const requestData = {
      department_id: hazardPay.department_id,
      month_id: hazardPay.month_id,
      year: hazardPay.year,
    };
    await generateHazardPayReport(requestData);
  } catch (error) {
    console.error("Error generating report:", error);
  }
};

const handleTabulate = async (hazardPay) => {
  try {
    // For now, we'll use the same report generation
    // You can implement a separate tabulated report later
    const requestData = {
      department_id: hazardPay.department_id,
      month_id: hazardPay.month_id,
      year: hazardPay.year,
      type: "tabulated",
    };
    await generateHazardPayReport(requestData);
  } catch (error) {
    console.error("Error generating tabulated report:", error);
  }
};

const handleFormSaved = async (newId) => {
  try {
    // If a new header was created, keep the form open to manage employees
    if (Number(newId) > 0 && selectedHazardPayId.value === 0) {
      selectedHazardPayId.value = Number(newId);
      return;
    }
    // Otherwise (update), close and refresh list
    showForm.value = false;
    selectedHazardPayId.value = 0;
    await loadHazardPayData();
  } catch (error) {
    console.error("Error after saving hazard pay:", error);
  }
};

const handleFormClose = () => {
  selectedHazardPayId.value = 0;
  showForm.value = false;
};

// Legacy dialog-based employee management removed in inline flow

onMounted(() => {
  loadHazardPayData();
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
