<template>
  <PageScaffold
    title="Mid Year Bonus"
    subtitle="Manage mid-year bonus calculations and processing"
    :breadcrumbs="[
      { label: 'Payroll Module', to: '/' },
      { label: 'Payroll Benefits', to: '/payroll-benefits' },
      { label: 'Mid Year Bonus' },
    ]"
  >
    <template #actions>
      <el-button
        type="success"
        @click="loadBonusData"
        :loading="loading"
        size="default"
        >Refresh</el-button
      >
    </template>

    <!-- Mid Year Bonus Form (Simplified) -->
    <div class="midyear-form-container">
      <el-form
        :model="formData"
        :rules="formRules"
        ref="formRef"
        label-width="185px"
        class="midyear-form"
      >
        <div class="form-row">
          <!-- <el-form-item label="Branch" prop="branch_id">
            <el-select
              v-model="formData.branch_id"
              placeholder="Select Branch"
              style="width: 250px"
              @change="onBranchChange"
            >
              <el-option
                v-for="branch in branches"
                :key="branch.id"
                :label="branch.name"
                :value="branch.id"
              />
            </el-select>
          </el-form-item> -->

          <el-form-item label="Division" prop="department_id">
            <el-select
              v-model="formData.department_id"
              placeholder="Select Division"
              style="width: 350px"
              @change="onDepartmentChange"
            >
              <el-option :key="'all-departments'" label="All" :value="'all'" />
              <el-option
                v-for="dept in departments"
                :key="dept.id"
                :label="dept.name"
                :value="dept.id"
              />
            </el-select>
          </el-form-item>

          <el-form-item label="Year" prop="years">
            <el-select
              v-model="formData.years"
              placeholder="Select Year"
              style="width: 120px"
            >
              <el-option
                v-for="year in availableYears"
                :key="year"
                :label="year"
                :value="year"
              />
            </el-select>
          </el-form-item>

          <!-- <el-form-item label="Cash Gift Incentive Amount">
            <el-input
              v-model="formData.cash_gift_amount"
              placeholder="0.00"
              style="width: 180px"
              type="number"
              step="0.01"
            >
              <template #prepend>₱</template>
            </el-input>
          </el-form-item> -->
        </div>

        <div class="form-actions">
          <el-button
            type="primary"
            @click="processBonus"
            :loading="processing"
            size="large"
          >
            Generate Mid Year Bonus
          </el-button>
          <el-button
            type="success"
            @click="printORS"
            :loading="generatingORS"
            size="large"
          >
            Print ORS
          </el-button>
          <el-button
            type="info"
            @click="printDV"
            :loading="generatingDV"
            size="large"
          >
            Print DV
          </el-button>
        </div>
      </el-form>
    </div>

    <!-- Search and Filter -->
    <div class="filters-container">
      <el-form :inline="true" class="enhanced-filters">
        <el-form-item label="Filter">
          <el-input
            v-model="searchQuery"
            placeholder="Search.."
            clearable
            style="width: 200px"
            @input="handleSearch"
          />
        </el-form-item>
      </el-form>
    </div>

    <!-- Mid Year Bonus List -->
    <MidYear_BonusList
      :data="filteredBonusData"
      :loading="loading"
      :search-query="searchQuery"
      :status-filter="statusFilter"
      @edit="handleEdit"
      @delete="handleDelete"
      @view="handleView"
      @bulk-post="handleBulkPost"
      @bulk-report="handleBulkReport"
      @update="handleUpdate"
    />
  </PageScaffold>
</template>

<script setup>
import { ref, computed, onMounted, watch } from "vue";
import { ElMessage, ElMessageBox } from "element-plus";
import PageScaffold from "../../../components/PageScaffold.vue";
import MidYear_BonusList from "../../../components/Payroll_Benefits/MidYear_Bonus/MidYear_BonusList.vue";
import { useMidYearBonus } from "../../../Composables/useMidYearBonus.js";
import { midYearBonusApi } from "../../../services/api.js";

// Composables
const {
  loading,
  midYearBonusList,
  branches,
  departments,
  years,
  loadMidYearBonusData,
  loadMidYearBonusRecords,
  updateMidYearBonusRecord,
  deleteMidYearBonusRecord,
  processMidYearBonus,
  postMidYearBonus,
  loadReportData,
  generateReport: generateReportApi,
  transformMidYearBonusData,
} = useMidYearBonus();

// Reactive data
const searchQuery = ref("");
const statusFilter = ref("");
const processing = ref(false);
const generatingORS = ref(false);
const generatingDV = ref(false);
const formRef = ref(null);

// Form data
const formData = ref({
  branch_id: null,
  department_id: null,
  years: new Date().getFullYear(),
  // cash_gift_amount: 0.0,
});

const formRules = {
  department_id: [
    {
      required: true,
      message: "Please select a department",
      trigger: "change",
    },
  ],
  years: [
    { required: true, message: "Please select a year", trigger: "change" },
  ],
};

// Computed properties
const availableYears = computed(() => {
  const currentYear = new Date().getFullYear();
  const years = [];
  for (let i = currentYear - 5; i <= currentYear + 5; i++) {
    years.push(i);
  }
  return years;
});

const filteredBonusData = computed(() => {
  let filtered = midYearBonusList.value;

  // Apply search filter
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    filtered = filtered.filter(
      (item) =>
        (item.employee_no && item.employee_no.toLowerCase().includes(query)) ||
        (item.name && item.name.toLowerCase().includes(query)) ||
        (item.department && item.department.toLowerCase().includes(query)) ||
        (item.years && item.years.toString().includes(query)),
    );
  }

  return filtered;
});

// Methods
const loadBonusData = async () => {
  try {
    await loadMidYearBonusData();
  } catch (error) {
    console.error("Failed to load bonus data:", error);
  }
};

const handleSearch = () => {};

const onBranchChange = () => {
  formData.value.department_id = null;

  midYearBonusList.value = [];
};

const onDepartmentChange = async () => {
  if (formData.value.department_id && formData.value.years) {
    try {
      const filters = {
        department_id: formData.value.department_id,
        years: formData.value.years,
      };
      if (formData.value.branch_id) {
        filters.branch_id = formData.value.branch_id;
      }
      await loadMidYearBonusRecords(filters);
    } catch (error) {
      console.error("Failed to load department records:", error);
    }
  }
};

const processBonus = async () => {
  if (!formRef.value) return;

  try {
    await formRef.value.validate();

    const deptLabel =
      formData.value.department_id === "all"
        ? "all departments"
        : "the selected department";
    await ElMessageBox.confirm(
      `Are you sure you want to generate mid-year bonus for ${deptLabel} for year ${formData.value.years}?`,
      "Confirm Process",
      {
        confirmButtonText: "Generate",
        cancelButtonText: "Cancel",
        type: "warning",
      },
    );

    processing.value = true;
    await processMidYearBonus(formData.value);
    {
      const filters = {
        department_id: formData.value.department_id,
        years: formData.value.years,
      };
      if (formData.value.branch_id) {
        filters.branch_id = formData.value.branch_id;
      }
      await loadMidYearBonusRecords(filters);
    }
  } catch (error) {
    if (error !== "cancel") {
      console.error("Validation failed:", error);
    }
  } finally {
    processing.value = false;
  }
};

const printORS = async () => {
  if (!formData.value.department_id || !formData.value.years) {
    ElMessage.warning(
      "Please select department and year before printing ORS.",
    );
    return;
  }

  try {
    generatingORS.value = true;
    const params = {
      department_id: formData.value.department_id,
    };
    if (formData.value.branch_id) {
      params.branch_id = formData.value.branch_id;
    }
    const response = await midYearBonusApi.generateORSReport(
      formData.value.years,
      params,
    );
    const blob = new Blob([response.data], { type: "application/pdf" });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = url;
    link.download = `ors_midyear_${formData.value.years}_${
      new Date().toISOString().split("T")[0]
    }.pdf`;
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
  } catch (error) {
    console.error("Failed to print ORS:", error);
    ElMessage.error(
      error.response?.data?.message || "Failed to generate ORS report.",
    );
  } finally {
    generatingORS.value = false;
  }
};

const printDV = async () => {
  if (!formData.value.department_id || !formData.value.years) {
    ElMessage.warning(
      "Please select department and year before printing DV.",
    );
    return;
  }

  try {
    generatingDV.value = true;
    const requestData = {
      years: formData.value.years,
      department_id: formData.value.department_id,
    };
    if (formData.value.branch_id) {
      requestData.branch_id = formData.value.branch_id;
    }
    const response = await midYearBonusApi.generateDVReport(requestData);
    const blob = new Blob([response.data], { type: "application/pdf" });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = url;
    link.download = `dv_midyear_${formData.value.years}_${
      new Date().toISOString().split("T")[0]
    }.pdf`;
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
  } catch (error) {
    console.error("Failed to print DV:", error);
    ElMessage.error(
      error.response?.data?.message ||
        "Failed to generate disbursement voucher report.",
    );
  } finally {
    generatingDV.value = false;
  }
};

const handleEdit = (record) => {
  console.log("Edit record:", record);
};

const handleDelete = async (record) => {
  try {
    if (!record?.id) {
      ElMessage.error("Invalid record selected for deletion.");
      return;
    }

    await deleteMidYearBonusRecord(record.id);

    // Reload current department records so the table reflects the change
    if (
      formData.value.branch_id &&
      formData.value.department_id &&
      formData.value.years
    ) {
      await loadMidYearBonusRecords({
        branch_id: formData.value.branch_id,
        department_id: formData.value.department_id,
        years: formData.value.years,
      });
    } else {
      // Fallback: remove from local list if filters are not complete
      midYearBonusList.value = midYearBonusList.value.filter(
        (item) => item.id !== record.id,
      );
    }
  } catch (error) {
    console.error("Failed to delete record:", error);
    // Error toast is already handled in composable; this is just a safety net
  }
};

const handleView = (record) => {
  console.log("View record:", record);
};

const handleUpdate = async (payload) => {
  if (!payload?.id) return;
  try {
    await updateMidYearBonusRecord(payload.id, {
      bonus_amount: payload.bonus_amount,
    });
    if (
      formData.value.branch_id &&
      formData.value.department_id &&
      formData.value.years
    ) {
      await loadMidYearBonusRecords({
        branch_id: formData.value.branch_id,
        department_id: formData.value.department_id,
        years: formData.value.years,
      });
    }
  } catch (error) {
    console.error("Failed to update record:", error);
  }
};

const handleBulkPost = async (records) => {
  try {
    ElMessage.info(
      `Bulk post for ${records.length} records needs to be implemented`,
    );
  } catch (error) {
    console.error("Failed to bulk post:", error);
  }
};

const handleBulkReport = async (records) => {
  try {
    ElMessage.info(
      `Bulk report for ${records.length} records needs to be implemented`,
    );
  } catch (error) {
    console.error("Failed to bulk report:", error);
  }
};

// Watchers
watch(
  () => formData.value.years,
  async (newYear) => {
    // Reload records when year changes
    if (formData.value.department_id && newYear) {
      try {
        const filters = {
          department_id: formData.value.department_id,
          years: newYear,
        };
        if (formData.value.branch_id) {
          filters.branch_id = formData.value.branch_id;
        }
        await loadMidYearBonusRecords(filters);
      } catch (error) {
        console.error("Failed to load records for new year:", error);
      }
    }
  },
);

// Lifecycle
onMounted(async () => {
  await loadBonusData();
});
</script>

<style scoped>
.midyear-form-container {
  background: #ffffff;
  border-radius: 12px;
  padding: 24px;
  margin-bottom: 20px;
  box-shadow:
    0 1px 3px 0 rgba(0, 0, 0, 0.1),
    0 1px 2px 0 rgba(0, 0, 0, 0.06);
  border: 1px solid #e5e7eb;
}

.midyear-form {
  margin: 0;
}

.form-row {
  display: flex;
  align-items: flex-end;
  margin-bottom: 20px;
  flex-wrap: wrap;
  gap: 25px;
}

.form-row .el-form-item {
  margin-bottom: 0;
  flex-shrink: 0;
}

.form-row .el-form-item__label {
  font-weight: 600;
  color: #374151;
  font-size: 14px;
}

.form-actions {
  display: flex;
  gap: 16px;
  justify-content: flex-start;
  padding-top: 16px;
  border-top: 1px solid #e5e7eb;
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
  .form-row {
    flex-direction: column;
    align-items: stretch;
    gap: 16px;
  }

  .form-row .el-form-item {
    width: 100%;
  }

  .form-row .el-select,
  .form-row .el-input {
    width: 100% !important;
  }

  .form-actions {
    flex-direction: column;
    gap: 12px;
  }

  .form-actions .el-button {
    width: 100%;
  }
}
</style>
