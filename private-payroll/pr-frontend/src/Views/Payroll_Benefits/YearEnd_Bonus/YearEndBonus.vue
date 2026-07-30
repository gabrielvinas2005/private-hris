<template>
  <PageScaffold
    title="Year End Bonus"
    subtitle="Manage year-end bonus calculations and processing"
    :breadcrumbs="[
      { label: 'Payroll Module', to: '/' },
      { label: 'Payroll Benefits', to: '/payroll-benefits' },
      { label: 'Year End Bonus' },
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

    <!-- Year End Bonus Form (Simplified) -->
    <div class="yearend-form-container">
      <el-form
        :model="formData"
        :rules="formRules"
        ref="formRef"
        label-width="185px"
        class="yearend-form"
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
              @change="onDivisionChange"
            >
              <el-option :key="'all-divisions'" label="All" :value="'all'" />
              <el-option
                v-for="division in departments"
                :key="division.id"
                :label="division.name"
                :value="division.id"
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

          <el-form-item label="Cash Gift Incentive Amount">
            <el-input
              v-model="formData.cash_gift_amount"
              placeholder="0.00"
              style="width: 180px"
              type="number"
              step="0.01"
            >
              <template #prepend>₱</template>
            </el-input>
          </el-form-item>
        </div>

        <div class="form-actions">
          <el-button
            type="primary"
            @click="processBonus"
            :loading="processing"
            size="large"
          >
            Generate Year End Bonus
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

    <!-- Year End Bonus List -->
    <YearEnd_BonusList
      ref="yearEndListRef"
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
import YearEnd_BonusList from "../../../components/Payroll_Benefits/YearEnd_Bonus/YearEnd_BonusList.vue";
import { useYearEndBonus } from "../../../Composables/useYearEndBonus.js";
import { divisionParams } from "../../../utils/payrollReportDivisions.js";
import { yearEndBonusApi } from "../../../services/api.js";

// Composables
const {
  loading,
  yearEndBonusList,
  branches,
  departments,
  years,
  loadYearEndBonusData,
  loadYearEndBonusRecords,
  updateYearEndBonusRecord,
  processYearEndBonus,
  postYearEndBonus,
  loadReportData,
  generateReport: generateReportApi,
  transformYearEndBonusData,
} = useYearEndBonus();

// Reactive data
const searchQuery = ref("");
const processing = ref(false);
const generatingORS = ref(false);
const generatingDV = ref(false);
const generatingReport = ref(false);
const yearEndListRef = ref(null);
const formRef = ref(null);
const statusFilter = ref("");

// Form data
const formData = ref({
  branch_id: null,
  department_id: null,
  years: new Date().getFullYear(),
  cash_gift_amount: 0.0,
});

const formRules = {
  department_id: [
    {
      required: true,
      message: "Please select a division",
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
  let filtered = yearEndBonusList.value;

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
    await loadYearEndBonusData();
  } catch (error) {
    console.error("Failed to load bonus data:", error);
  }
};

const handleSearch = () => {
  // Search is handled by computed property
};

const onBranchChange = () => {
  // Reset department when branch changes
  formData.value.department_id = null;
  // Clear the bonus records when branch changes
  yearEndBonusList.value = [];
};

const loadRecordsForSelection = async () => {
  if (!formData.value.department_id || !formData.value.years) {
    yearEndBonusList.value = [];
    return;
  }

  try {
    const filters = {
      department_id: formData.value.department_id,
      years: formData.value.years,
    };
    if (formData.value.branch_id) {
      filters.branch_id = formData.value.branch_id;
    }
    await loadYearEndBonusRecords(filters);
  } catch (error) {
    console.error("Failed to load division records:", error);
  }
};

const onDivisionChange = async () => {
  await loadRecordsForSelection();
};

const processBonus = async () => {
  if (!formRef.value) return;

  try {
    await formRef.value.validate();

    await ElMessageBox.confirm(
      `Are you sure you want to generate year-end bonus for the selected branch and department for year ${formData.value.years}?`,
      "Confirm Process",
      {
        confirmButtonText: "Generate",
        cancelButtonText: "Cancel",
        type: "warning",
      },
    );

    processing.value = true;
    const payload = {
      department_id: formData.value.department_id,
      years: formData.value.years,
      cash_gift: Number(formData.value.cash_gift_amount || 0),
    };
    if (formData.value.branch_id) {
      payload.branch_id = formData.value.branch_id;
    }
    await processYearEndBonus(payload);
    await loadRecordsForSelection();
  } catch (error) {
    if (error !== "cancel") {
      console.error("Validation failed:", error);
    }
  } finally {
    processing.value = false;
  }
};

const printORS = async () => {
  if (!formData.value.years) {
    ElMessage.warning("Please select a year before printing ORS.");
    return;
  }

  try {
    generatingORS.value = true;
    const resp = await yearEndBonusApi.generateORSReport(formData.value.years);
    const blob = new Blob([resp.data], { type: "application/pdf" });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = url;
    link.download = `ors_yearend_${formData.value.years}_${
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
  if (!formData.value.years) {
    ElMessage.warning("Please select a year before printing DV.");
    return;
  }

  try {
    generatingDV.value = true;
    const resp = await yearEndBonusApi.generateDVReport(formData.value.years);
    const blob = new Blob([resp.data], { type: "application/pdf" });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = url;
    link.download = `dv_yearend_${formData.value.years}_${
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
    ElMessage.info(
      "Delete functionality needs to be implemented in the backend",
    );
  } catch (error) {
    console.error("Failed to delete record:", error);
  }
};

const handleView = (record) => {
  console.log("View record:", record);
};

const handleUpdate = async (payload) => {
  if (!payload?.id) return;
  try {
    await updateYearEndBonusRecord(payload.id, {
      bonus_amount: payload.bonus_amount,
      cash_gift_amount: payload.cash_gift_amount,
    });
    if (formData.value.department_id && formData.value.years) {
      await loadRecordsForSelection();
    }
  } catch (error) {
    console.error("Failed to update record:", error);
  }
};

const buildDivisionYearPayload = (records = []) => {
  const payload = {
    years: formData.value.years,
    ...divisionParams({
      division_id: formData.value.department_id,
      department_id: formData.value.department_id,
    }),
  };
  const branchId =
    formData.value.branch_id ||
    records.find((r) => r.branch_id)?.branch_id ||
    null;
  if (branchId) {
    payload.branch_id = branchId;
  }
  return payload;
};

const handleBulkPost = async (records) => {
  if (!formData.value.department_id || formData.value.department_id === "all") {
    ElMessage.warning("Select a specific division before posting.");
    return;
  }
  if (!formData.value.years) {
    ElMessage.warning("Select a year before posting.");
    return;
  }
  if (!records.length) {
    ElMessage.warning("No draft records selected for posting.");
    return;
  }

  try {
    await ElMessageBox.confirm(
      `Post year-end bonus for ${records.length} selected employee(s) in this division and year?`,
      "Confirm Bulk Post",
      {
        confirmButtonText: "Post",
        cancelButtonText: "Cancel",
        type: "warning",
      },
    );

    await postYearEndBonus(buildDivisionYearPayload(records));
    await loadRecordsForSelection();
    yearEndListRef.value?.clearSelection?.();
  } catch (error) {
    if (error !== "cancel") {
      console.error("Failed to bulk post:", error);
    }
  }
};

const handleBulkReport = async (records) => {
  if (!formData.value.department_id || formData.value.department_id === "all") {
    ElMessage.warning(
      "Select a specific division before generating the report.",
    );
    return;
  }
  if (!formData.value.years) {
    ElMessage.warning("Select a year before generating the report.");
    return;
  }
  if (!records.length) {
    ElMessage.warning("Select at least one employee record.");
    return;
  }

  try {
    generatingReport.value = true;
    await generateReportApi({
      ...buildDivisionYearPayload(records),
      signatory_1: "",
      signatory_position_1: "",
      signatory_2: "",
      signatory_position_2: "",
      signatory_3: "",
      signatory_position_3: "",
      signatory_4: "",
      signatory_position_4: "",
      signatory_5: "",
      signatory_position_5: "",
    });
  } catch (error) {
    console.error("Failed to generate bulk report:", error);
  } finally {
    generatingReport.value = false;
  }
};

// Watchers
watch(
  () => formData.value.years,
  async () => {
    await loadRecordsForSelection();
  },
);

// Lifecycle
onMounted(async () => {
  await loadBonusData();
});
</script>

<style scoped>
/* Year End Bonus Form Container */
.yearend-form-container {
  background: #ffffff;
  border-radius: 12px;
  padding: 24px;
  margin-bottom: 20px;
  box-shadow:
    0 1px 3px 0 rgba(0, 0, 0, 0.1),
    0 1px 2px 0 rgba(0, 0, 0, 0.06);
  border: 1px solid #e5e7eb;
}

.yearend-form {
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
