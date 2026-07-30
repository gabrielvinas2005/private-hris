<template>
  <PageScaffold
    title="Monetization Payroll"
    subtitle="Manage monetization payroll headers and employees"
    :breadcrumbs="[
      { label: 'Payroll Module', to: '/' },
      { label: 'Payroll Benefits', to: '/payroll-benefits' },
      { label: 'Monetization Payroll' },
    ]"
  >
    <template #actions>
      <el-button
        v-if="!showForm"
        type="primary"
        @click="handleAddNew"
        :loading="loading"
      >
        Add Monetization Payroll
      </el-button>
      <el-button
        v-if="!showForm"
        type="success"
        @click="loadList()"
        :loading="loading"
      >
        Refresh
      </el-button>
    </template>

    <!-- Filters (match Hazard Pay UI) -->
    <div v-if="!showForm" class="filters-container">
      <el-form :inline="true" class="enhanced-filters">
        <el-form-item label="Search">
          <el-input
            v-model="searchQuery"
            placeholder="Search by branch or period"
            clearable
            class="search-input"
          />
        </el-form-item>
        <el-form-item label="Status">
          <el-select
            v-model="statusFilter"
            placeholder="All Status"
            class="status-select"
            clearable
          >
            <el-option label="All Status" value="" />
            <el-option label="Posted" value="posted" />
            <el-option label="Unposted" value="unposted" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" plain @click="handleFilter"
            >Filter</el-button
          >
        </el-form-item>
        <el-form-item>
          <el-button type="danger" plain @click="resetFilters">Reset</el-button>
        </el-form-item>
      </el-form>
    </div>

    <MonetizationPayrollList
      v-if="!showForm"
      :rows="filteredRows"
      :loading="loading"
      @details="handleDetails"
      @edit="handleEdit"
      @post="handlePost"
      @unpost="handleUnpost"
      @delete="handleDelete"
      @print-ors="handlePrintORS"
      @print-dv="handlePrintDV"
    />

    <MonetizationPayrollForm
      v-else
      :branches="branches"
      :months="months"
      :form="formData"
      :loading="loading"
      @save-header="handleSaveHeader"
      @close="handleFormClose"
    />

    <MonetizationPayrollEmployeeDialog
      v-if="showForm && showEmployeeDialog"
      :inline="true"
      :form="formData"
      :employees="employees"
      :loading="loading"
      :read-only="isViewOnly"
      @save-employees="handleSaveEmployees"
      @remove-employee="handleRemoveEmployee"
    />
  </PageScaffold>
</template>

<script setup>
import { ref, onMounted, computed } from "vue";
import { ElMessage } from "element-plus";
import PageScaffold from "../../../components/PageScaffold.vue";
import MonetizationPayrollList from "../../../components/Payroll_Benefits/Monetization_Payroll/MonetizationPayrollList.vue";
import MonetizationPayrollForm from "../../../components/Payroll_Benefits/Monetization_Payroll/MonetizationPayrollForm.vue";
import MonetizationPayrollEmployeeDialog from "../../../components/Payroll_Benefits/Monetization_Payroll/MonetizationPayrollEmployeeDialog.vue";
import { useMonetizationPayroll } from "../../../Composables/useMonetizationPayroll.js";
import { monetizationPayrollApi } from "../../../services/api.js";

const {
  loading,
  payrollList,
  branches,
  employees,
  months,
  formData,
  loadList,
  loadForm,
  saveHeader,
  saveEmployees,
  removeEmployee,
  processPosting,
  generateReport,
  resetFormData,
  transformList,
} = useMonetizationPayroll();

const showForm = ref(false);
const showEmployeeDialog = ref(false);
const isViewOnly = ref(false);
const isEditMode = ref(false);
const searchQuery = ref("");
const statusFilter = ref("");

const filteredRows = computed(() => {
  let rows = transformList(payrollList.value);
  // status filter
  if (statusFilter.value) {
    const wantPosted = statusFilter.value === "posted";
    rows = rows.filter((r) => r.posted === wantPosted);
  }
  // search by branch or month/year text
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase();
    rows = rows.filter(
      (r) =>
        (r.branch || "").toLowerCase().includes(q) ||
        (r.month || "").toLowerCase().includes(q) ||
        String(r.year || "").includes(q),
    );
  }
  return rows;
});

const handleFilter = () => {};
const resetFilters = () => {
  searchQuery.value = "";
  statusFilter.value = "";
};

const handleAddNew = async () => {
  resetFormData();
  await loadForm(0);
  isViewOnly.value = false;
  isEditMode.value = false;
  showForm.value = true;
  showEmployeeDialog.value = false;
};

const handleEdit = async (row) => {
  await loadForm(row.id);
  isViewOnly.value = false;
  isEditMode.value = true;
  showForm.value = true;
  showEmployeeDialog.value = true;
};

const handleDetails = async (row) => {
  await loadForm(row.id);
  isViewOnly.value = true;
  isEditMode.value = false;
  showForm.value = true;
  showEmployeeDialog.value = true;
};

const handleSaveHeader = async (payload) => {
  try {
    const isUpdate = Boolean(payload.id);
    const result = await saveHeader(payload.id || 0, payload);
    const headerId = result?.id || payload.id;
    await loadForm(headerId);
    await loadList();

    ElMessage.success(
      isUpdate
        ? "Monetization payroll updated successfully."
        : "Monetization payroll created successfully.",
    );

    // After saving header, keep inline form visible and show
    // the employee list panel below it so the user can add/view employees.
    showForm.value = true;
    isViewOnly.value = false;
    isEditMode.value = false;
    showEmployeeDialog.value = true;
  } catch (err) {
    const msg =
      err?.response?.data?.message ||
      err?.message ||
      "Failed to save monetization payroll.";
    ElMessage.error(msg);
  }
};

const handleOpenEmployees = async (header) => {
  await loadForm(header.id);
  isViewOnly.value = false;
  showEmployeeDialog.value = true;
};

const handleSaveEmployees = async (data) => {
  const { headerId, ...payload } = data;
  await saveEmployees(headerId, payload);
  await loadForm(headerId);
  await loadList();
};

const handleRemoveEmployee = async (detailId) => {
  await removeEmployee(detailId);
  await loadForm(formData.value.id);
};

const handlePost = async (row) => {
  await loadForm(row.id);
  const details = Array.isArray(formData.value.data) ? formData.value.data : [];
  if (details.length === 0) {
    ElMessage.error("Unable to post: please add employees first.");
    return;
  }
  await processPosting(row.id, 1);
  await loadList();
};

const handleUnpost = async (row) => {
  await processPosting(row.id, 0);
  await loadList();
};

const handleDelete = async (row) => {
  if (row.posted) {
    ElMessage.error("Cannot delete a posted monetization payroll. Please unpost it first.");
    return;
  }
  // Lightweight confirm without adding new UI deps
  if (!window.confirm("Are you sure you want to delete this monetization payroll?")) {
    return;
  }
  try {
    await monetizationPayrollApi.deleteMonetizationPayroll(row.id);
    ElMessage.success("Monetization payroll deleted successfully.");
    await loadList();
  } catch (err) {
    const msg =
      err?.response?.data?.message ||
      err?.message ||
      "Failed to delete monetization payroll.";
    ElMessage.error(msg);
  }
};

const handlePrintORS = async (row) => {
  const response = await monetizationPayrollApi.generateORSReport(row.id);
  const blob = new Blob([response.data], { type: "application/pdf" });
  const url = window.URL.createObjectURL(blob);
  window.open(url, "_blank");
};

const handlePrintDV = async (row) => {
  const response = await monetizationPayrollApi.generateDVReport(row.id);
  const blob = new Blob([response.data], { type: "application/pdf" });
  const url = window.URL.createObjectURL(blob);
  window.open(url, "_blank");
};

const handleFormClose = () => {
  showForm.value = false;
  isViewOnly.value = false;
  isEditMode.value = false;
};

onMounted(() => {
  loadList();
});
</script>

<style scoped>
.mb-3 {
  margin-bottom: 12px;
}
.filters-container {
  background: #ffffff;
  border-radius: 12px;
  padding: 12px 16px;
  margin-bottom: 16px;
  border: 1px solid #e5e7eb;
}
.status-chip {
  margin-left: 8px;
}
.enhanced-filters :deep(.el-form-item) {
  margin-right: 16px;
  margin-bottom: 0;
}
.search-input {
  width: 280px;
}
.status-select :deep(.el-input__inner) {
  border-radius: 8px;
}
</style>
