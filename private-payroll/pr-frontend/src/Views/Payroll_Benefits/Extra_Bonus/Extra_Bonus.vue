<template>
  <PageScaffold
    title="Extra Bonus Payroll"
    subtitle="Manage extra bonus payroll calculations and processing"
    :breadcrumbs="[
      { label: 'Payroll Module', to: '/' },
      { label: 'Payroll Benefits', to: '/payroll-benefits' },
      { label: 'Extra Bonus Payroll' },
    ]"
  >
    <template #actions>
      <el-button type="primary" @click="showCreateForm" size="default">
        Add Extra Bonus Payroll
      </el-button>
      <el-button
        type="success"
        @click="loadExtraBonusData"
        :loading="loading"
        size="default"
      >
        Refresh
      </el-button>
    </template>

    <!-- Extra Bonus Form -->
    <div v-if="showForm" class="form-section">
      <div class="section-header">
        <h3>{{ isEditing ? "Edit" : "Create" }} Extra Bonus Payroll</h3>
        <el-button type="info" @click="hideForm" size="small">
          Close Form
        </el-button>
      </div>

      <ExtraBonusForm
        ref="extraBonusFormRef"
        :extra-bonus-types="extraBonusTypes"
        :divisions="departments"
        :departments="departments"
        :employees="formEmployees"
        :loading="loading"
        :initial-data="editingRecord"
        @load-employees="handleLoadEmployees"
        @save-payroll="handleSavePayroll"
        @post-payroll="handlePostPayroll"
        @unpost-payroll="handleUnpostPayroll"
      />
    </div>

    <!-- Search and Filter -->
    <div class="filters-container">
      <el-form :inline="true" class="enhanced-filters">
        <el-form-item label="Search">
          <el-input
            v-model="searchQuery"
            placeholder="Search extra bonus type, office, year..."
            clearable
            style="width: 250px"
            @input="handleSearch"
          />
        </el-form-item>
        <el-form-item label="Status">
          <el-select
            v-model="statusFilter"
            placeholder="All Status"
            clearable
            style="width: 120px"
          >
            <el-option label="Draft" value="draft" />
            <el-option label="Posted" value="posted" />
          </el-select>
        </el-form-item>
      </el-form>
    </div>

    <!-- Extra Bonus List -->
    <ExtraBonusList
      ref="extraBonusListRef"
      :data="filteredExtraBonusData"
      :loading="loading"
      :search-query="searchQuery"
      :status-filter="statusFilter"
      @edit="handleEdit"
      @delete="handleDelete"
      @view="handleView"
      @post="handlePost"
      @unpost="handleUnpost"
      @bulk-post="handleBulkPost"
      @bulk-report="handleBulkReport"
      @load-employee-details="handleLoadEmployeeDetails"
    />
  </PageScaffold>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { ElMessage, ElMessageBox } from "element-plus";
import PageScaffold from "../../../components/PageScaffold.vue";
import ExtraBonusList from "../../../components/Payroll_Benefits/Extra_Bonus/ExtraBonusList.vue";
import ExtraBonusForm from "../../../components/Payroll_Benefits/Extra_Bonus/ExtraBonusForm.vue";
import { useExtraBonus } from "../../../Composables/useExtraBonus.js";

// Composables
const {
  loading,
  extraBonusList,
  extraBonusTypes,
  departments,
  employees,
  loadExtraBonusData,
  loadExtraBonusFormData,
  loadEmployees,
  saveExtraBonusPayroll,
  deleteExtraBonusPayroll,
  processExtraBonus,
  bulkPost,
  loadDropdownData,
  transformExtraBonusData,
} = useExtraBonus();

// Reactive data
const searchQuery = ref("");
const statusFilter = ref("");
const showForm = ref(false);
const isEditing = ref(false);
const editingRecord = ref({});
const formEmployees = ref([]);
const extraBonusFormRef = ref(null);
const extraBonusListRef = ref(null);

// Computed properties
const filteredExtraBonusData = computed(() => {
  let filtered = transformExtraBonusData(extraBonusList.value);

  // Apply search filter
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    filtered = filtered.filter(
      (item) =>
        (item.extra_bonus_type &&
          item.extra_bonus_type.toLowerCase().includes(query)) ||
        (item.department && item.department.toLowerCase().includes(query)) ||
        (item.year_id && item.year_id.toString().includes(query)),
    );
  }

  // Apply status filter
  if (statusFilter.value) {
    filtered = filtered.filter((item) => item.status === statusFilter.value);
  }

  return filtered;
});

// Methods
const handleSearch = () => {
  // Search is handled by computed property
};

const showCreateForm = () => {
  isEditing.value = false;
  editingRecord.value = {};
  formEmployees.value = [];
  showForm.value = true;

  // Reset form if it exists
  if (extraBonusFormRef.value) {
    extraBonusFormRef.value.resetForm();
  }
};

const hideForm = () => {
  showForm.value = false;
  isEditing.value = false;
  editingRecord.value = {};
  formEmployees.value = [];
};

const handleLoadEmployees = async (params) => {
  try {
    const employeeData = await loadEmployees(
      params.extra_bonus_type_id,
      params.department_id,
      params.year_id,
    );
    formEmployees.value = employeeData;

    // Set employees in the form component
    if (extraBonusFormRef.value) {
      extraBonusFormRef.value.setEmployees(employeeData);
    }
  } catch (error) {
    console.error("Failed to load employees:", error);
    formEmployees.value = [];
  }
};

const handleSavePayroll = async (payrollData) => {
  try {
    const result = await saveExtraBonusPayroll(payrollData);

    // Update form data with the returned ID
    if (extraBonusFormRef.value && result.data.extra_bonus_id) {
      extraBonusFormRef.value.setFormData({
        extra_bonus_id: result.data.extra_bonus_id,
      });
    }

    // Refresh the list
    await loadExtraBonusData();

    ElMessage.success("Extra bonus payroll saved successfully!");
  } catch (error) {
    console.error("Failed to save payroll:", error);
  }
};

const handlePostPayroll = async (data) => {
  try {
    await processExtraBonus(data.extra_bonus_id, data.type_id);

    // Update form data
    if (extraBonusFormRef.value) {
      extraBonusFormRef.value.setFormData({
        is_posted: true,
      });
    }

    // Refresh the list
    await loadExtraBonusData();

    ElMessage.success("Extra bonus payroll posted successfully!");
  } catch (error) {
    console.error("Failed to post payroll:", error);
  }
};

const handleUnpostPayroll = async (data) => {
  try {
    await processExtraBonus(data.extra_bonus_id, data.type_id);

    // Update form data
    if (extraBonusFormRef.value) {
      extraBonusFormRef.value.setFormData({
        is_posted: false,
      });
    }

    // Refresh the list
    await loadExtraBonusData();

    ElMessage.success("Extra bonus payroll unposted successfully!");
  } catch (error) {
    console.error("Failed to unpost payroll:", error);
  }
};

const handleEdit = async (record) => {
  try {
    isEditing.value = true;
    editingRecord.value = record;

    // Load form data for editing
    const formData = await loadExtraBonusFormData(record.id);
    formEmployees.value = formData.employees || [];

    showForm.value = true;

    // Set form data
    if (extraBonusFormRef.value) {
      extraBonusFormRef.value.setFormData({
        extra_bonus_id: record.id,
        extra_bonus_type_id:
          formData.extra_bonus_payrolls?.[0]?.extra_bonus_type_id,
        department_id: formData.extra_bonus_payrolls?.[0]?.department_id,
        year_id: formData.extra_bonus_payrolls?.[0]?.year_id,
        is_posted: formData.extra_bonus_payrolls?.[0]?.is_posted || false,
      });
      extraBonusFormRef.value.setEmployees(formData.employees || []);
    }
  } catch (error) {
    console.error("Failed to load record for editing:", error);
  }
};

const handleDelete = async (record) => {
  try {
    await deleteExtraBonusPayroll(record.id);
    await loadExtraBonusData();

    if (showForm.value && editingRecord.value?.id === record.id) {
      hideForm();
    }
  } catch (error) {
    console.error("Failed to delete record:", error);
  }
};

const handleView = (record) => {
  console.log("View record:", record);
};

const handleLoadEmployeeDetails = async (record) => {
  try {
    // Load employee details for the specific payroll record
    const formData = await loadExtraBonusFormData(record.id);
    const employeeData = formData.employees || [];

    // Set the employee details in the list component
    if (extraBonusListRef.value) {
      extraBonusListRef.value.setEmployeeDetails(employeeData);
      extraBonusListRef.value.setLoadingEmployees(false);
    }
  } catch (error) {
    console.error("Failed to load employee details:", error);
    // Set empty data and stop loading
    if (extraBonusListRef.value) {
      extraBonusListRef.value.setEmployeeDetails([]);
      extraBonusListRef.value.setLoadingEmployees(false);
    }
  }
};

const handlePost = async (record) => {
  try {
    await processExtraBonus(record.id, 1); // 1 for post

    // Force refresh the list data
    await loadExtraBonusData(); // Refresh the list

    // Force reactive update by creating a new array reference
    const currentData = [...extraBonusList.value];
    extraBonusList.value = [];
    extraBonusList.value = currentData;
  } catch (error) {
    console.error("Failed to post record:", error);
  }
};

const handleUnpost = async (record) => {
  try {
    await processExtraBonus(record.id, 0); // 0 for unpost

    // Force refresh the list data
    await loadExtraBonusData(); // Refresh the list

    // Force reactive update by creating a new array reference
    const currentData = [...extraBonusList.value];
    extraBonusList.value = [];
    extraBonusList.value = currentData;
  } catch (error) {
    console.error("Failed to unpost record:", error);
  }
};

const handleBulkPost = async (records) => {
  try {
    if (records.length === 0) {
      ElMessage.warning("No records selected for posting");
      return;
    }

    await ElMessageBox.confirm(
      `Are you sure you want to post ${records.length} record(s)?`,
      "Confirm Bulk Post",
      {
        confirmButtonText: "OK",
        cancelButtonText: "Cancel",
        type: "warning",
      },
    );

    await bulkPost(records);
    await loadExtraBonusData(); // Reload the list
    if (extraBonusListRef.value) {
      extraBonusListRef.value.clearSelection();
    }
  } catch (error) {
    if (error !== "cancel") {
      console.error("Failed to bulk post:", error);
    }
  }
};

const handleBulkReport = async (records) => {
  try {
    if (records.length === 0) {
      ElMessage.warning("No records selected for report");
      return;
    }

    ElMessage.info(
      `Generate report for ${records.length} record(s) - Feature coming soon`,
    );
    // TODO: Implement bulk report generation
    // This would require a backend endpoint to generate a bulk report
  } catch (error) {
    console.error("Failed to generate bulk report:", error);
  }
};

// Lifecycle
onMounted(async () => {
  try {
    // Load dropdown data first
    await loadDropdownData();
    // Then load the extra bonus list
    await loadExtraBonusData();
  } catch (error) {
    console.error("Failed to load initial data:", error);
  }
});
</script>

<style scoped>
/* Form Section */
.form-section {
  background: #ffffff;
  border-radius: 12px;
  margin-bottom: 20px;
  box-shadow:
    0 1px 3px 0 rgba(0, 0, 0, 0.1),
    0 1px 2px 0 rgba(0, 0, 0, 0.06);
  border: 1px solid #e5e7eb;
  overflow: hidden;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 24px;
  border-bottom: 1px solid #e5e7eb;
  background: #f8fafc;
}

.section-header h3 {
  margin: 0;
  color: #374151;
  font-weight: 600;
  font-size: 18px;
}

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

/* Responsive adjustments */
@media (max-width: 768px) {
  .section-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }

  .enhanced-filters {
    display: flex;
    flex-direction: column;
    gap: 16px;
  }

  .enhanced-filters :deep(.el-form-item) {
    margin-right: 0;
    width: 100%;
  }

  .enhanced-filters :deep(.el-select),
  .enhanced-filters :deep(.el-input) {
    width: 100% !important;
  }
}
</style>
