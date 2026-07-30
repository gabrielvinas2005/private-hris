<template>
  <PageScaffold
    title="Retirement Benefits"
    subtitle="BP FORM 205: List of Retirees for Payment of Terminal Leave and Retirement Gratuity Benefits"
    :breadcrumbs="[
      { label: 'Payroll Module', to: '/' },
      { label: 'Payroll Benefits', to: '/payroll-benefits' },
      { label: 'Retirement Benefits' },
    ]"
  >
    <template #actions>
      <el-button
        type="success"
        @click="loadData"
        :loading="loading"
        size="default"
        >Refresh</el-button
      >
    </template>

    <!-- Retirement Benefits Form -->
    <div class="retirement-form-container">
      <el-form
        :model="formData"
        :rules="formRules"
        ref="formRef"
        label-width="185px"
        class="retirement-form"
      >
        <div class="form-row">
          <el-form-item label="Year of Retirement" prop="fiscal_year">
            <el-select
              v-model="formData.fiscal_year"
              placeholder="Select Year of Retirement"
              style="width: 150px"
            >
              <el-option
                v-for="year in availableYears"
                :key="year"
                :label="year"
                :value="year"
              />
            </el-select>
          </el-form-item>
          <!-- <el-form-item>
            <el-button
              type="primary"
              @click="handleLoadRetirees"
              :loading="loadingRetirees"
              :disabled="!formData.fiscal_year"
            >
              Load Retirees
            </el-button>
          </el-form-item> -->
        </div>

        <div class="form-row">
          <el-form-item label="Prepared By">
            <el-input
              v-model="formData.prepared_by_name"
              placeholder="Name"
              style="width: 300px"
            />
          </el-form-item>
          <el-form-item label="Position">
            <el-input
              v-model="formData.prepared_by_position"
              placeholder="Position"
              style="width: 300px"
            />
          </el-form-item>
        </div>

        <div class="form-row">
          <el-form-item label="Approved By">
            <el-input
              v-model="formData.approved_by_name"
              placeholder="Name"
              style="width: 300px"
            />
          </el-form-item>
          <el-form-item label="Position">
            <el-input
              v-model="formData.approved_by_position"
              placeholder="Position"
              style="width: 300px"
            />
          </el-form-item>
          <el-form-item label="Date">
            <el-date-picker
              v-model="formData.date"
              type="date"
              placeholder="Select date"
              format="YYYY-MM-DD"
              value-format="YYYY-MM-DD"
              style="width: 200px"
            />
          </el-form-item>
          <el-form-item>
            <el-button
              type="primary"
              @click="handleLoadRetirees"
              :loading="loadingRetirees"
              :disabled="!formData.fiscal_year"
            >
              Load Retirees
            </el-button>
          </el-form-item>
        </div>
      </el-form>
    </div>

    <!-- Retirees Table -->
    <div v-if="retirees.length > 0" class="retirees-table-container">
      <el-card shadow="never" class="retirees-card">
        <template #header>
          <div class="card-header">
            <span>Retirees for FY {{ formData.fiscal_year }}</span>
            <span class="retirees-count"
              >({{ retirees.length }} retiree(s))</span
            >
          </div>
        </template>

        <!-- Search and Filter -->
        <div class="filters-container-inline">
          <el-form :inline="true" class="enhanced-filters">
            <el-form-item label="Filter">
              <el-input
                v-model="searchQuery"
                placeholder="Search retirees..."
                clearable
                style="width: 200px"
                @input="handleSearch"
              />
            </el-form-item>
          </el-form>
        </div>

        <el-table
          :data="filteredRetirees"
          stripe
          border
          style="width: 100%"
          :default-sort="{ prop: 'retirement_date', order: 'ascending' }"
        >
          <el-table-column prop="name" label="Name" width="200" sortable />
          <el-table-column prop="employee_no" label="Employee No" width="120" />
          <el-table-column prop="position" label="Position" mid-width="180" />
          <el-table-column
            prop="department"
            label="Department"
            min-width="150"
          />
          <el-table-column
            prop="retirement_date"
            label="Retirement Date"
            width="130"
            sortable
          >
            <template #default="{ row }">
              {{ formatDate(row.retirement_date) }}
            </template>
          </el-table-column>
          <el-table-column
            prop="highest_monthly_salary"
            label="Monthly Salary"
            width="130"
            align="right"
          >
            <template #default="{ row }">
              {{ formatCurrency(row.highest_monthly_salary) }}
            </template>
          </el-table-column>
          <el-table-column
            prop="vl_credits"
            label="VL Credits"
            width="100"
            align="right"
          />
          <el-table-column
            prop="sl_credits"
            label="SL Credits"
            width="100"
            align="right"
          />
          <el-table-column
            prop="terminal_leave_amount"
            label="Terminal Leave"
            width="130"
            align="right"
          >
            <template #default="{ row }">
              {{ formatCurrency(row.terminal_leave_amount) }}
            </template>
          </el-table-column>
          <el-table-column
            prop="retirement_gratuity_amount"
            label="Gratuity Amount"
            width="140"
            align="right"
          >
            <template #default="{ row }">
              {{ formatCurrency(row.retirement_gratuity_amount) }}
            </template>
          </el-table-column>
          <el-table-column
            prop="is_gsis_member"
            label="GSIS Member"
            width="110"
            align="center"
          >
            <template #default="{ row }">
              <el-tag
                :type="row.is_gsis_member ? 'success' : 'info'"
                size="small"
              >
                {{ row.is_gsis_member ? "Yes" : "No" }}
              </el-tag>
            </template>
          </el-table-column>
        </el-table>

        <div class="table-actions">
          <el-button
            type="info"
            @click="previewBPForm205"
            :loading="generatingReport"
            :disabled="retirees.length === 0"
            size="default"
          >
            Preview Report
          </el-button>
        </div>
      </el-card>
    </div>

    <!-- Empty State -->
    <div v-else-if="!loadingRetirees" class="empty-state-container">
      <el-empty
        description="No retirees found. Please select a fiscal year and click 'Load Retirees'."
      />
    </div>

    <!-- Inline Print Preview -->
    <div v-if="uiState.showPrintModal" class="mt-6">
      <el-card shadow="never">
        <div class="flex items-center justify-between mb-3">
          <div class="text-base font-semibold">Print Preview</div>
          <div class="flex items-center gap-2">
            <span class="text-xs text-slate-600 mr-1">Download as:</span>
            <el-button size="small" type="danger" @click="downloadPdf">PDF</el-button>
            <el-button size="small" type="primary" @click="handleDownloadWord">DOCX</el-button>
            <el-button size="small" type="success" @click="handleDownloadExcel">Excel</el-button>
            <el-button size="small" @click="closePreview"><el-icon><Close /></el-icon></el-button>
          </div>
        </div>
        <div v-if="uiState.previewUrl" class="border rounded overflow-hidden" style="height: 680px; max-width: 100%;">
          <iframe :src="uiState.previewUrl" class="w-full h-full" style="max-width: 100%;"></iframe>
        </div>
        <div v-else class="text-center text-gray-500 py-10">Loading preview...</div>
      </el-card>
    </div>
  </PageScaffold>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { ElMessage, ElMessageBox } from "element-plus";
import PageScaffold from "../../../components/PageScaffold.vue";
import { Close } from "@element-plus/icons-vue";
import { useRetirementBenefits } from "../../../Composables/useRetirementBenefits.js";

// Composables
const {
  loading,
  departments,
  retirees,
  loadingRetirees,
  loadRetirementBenefitsData,
  loadRetirees,
  previewBPForm205: previewBPForm205Api,
  generateBPForm205,
  uiState,
  downloadPdf,
  downloadWord,
  downloadExcel,
  closePreview,
} = useRetirementBenefits();

// Reactive data
const searchQuery = ref("");
const generatingReport = ref(false);
const formRef = ref(null);
const currentYear = new Date().getFullYear();

// Form data
const formData = ref({
  fiscal_year: currentYear,
  prepared_by_name: "MARIA ANTONIETTE S. ZOILO",
  prepared_by_position: "Administrative Officer V",
  approved_by_name: "NELLY NITA N. DILLERA",
  approved_by_position: "Executive Director",
  date: new Date().toISOString().split("T")[0],
});

const formRules = {
  fiscal_year: [
    {
      required: true,
      message: "Please select a fiscal year",
      trigger: "change",
    },
  ],
};

// Computed properties
const availableYears = computed(() => {
  const years = [];
  for (let i = currentYear - 5; i <= currentYear + 5; i++) {
    years.push(i);
  }
  return years;
});

const filteredRetirees = computed(() => {
  if (!searchQuery.value) return retirees.value;

  const query = searchQuery.value.toLowerCase();
  return retirees.value.filter(
    (retiree) =>
      retiree.name?.toLowerCase().includes(query) ||
      retiree.employee_no?.toLowerCase().includes(query) ||
      retiree.position?.toLowerCase().includes(query) ||
      retiree.department?.toLowerCase().includes(query)
  );
});

// Methods
const loadData = async () => {
  try {
    await loadRetirementBenefitsData();
  } catch (error) {
    console.error("Failed to load data:", error);
  }
};

const handleLoadRetirees = async () => {
  if (!formData.value.fiscal_year) {
    ElMessage.warning("Please select a fiscal year first");
    return;
  }

  try {
    await loadRetirees(formData.value.fiscal_year);
  } catch (error) {
    console.error("Failed to load retirees:", error);
  }
};

const handleSearch = () => {
  // Search is handled by computed property
};

const formatDate = (dateString) => {
  if (!dateString) return "-";
  const date = new Date(dateString);
  return date.toLocaleDateString("en-US", {
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
  });
};

const formatCurrency = (amount) => {
  if (!amount && amount !== 0) return "-";
  return new Intl.NumberFormat("en-US", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(amount);
};

const previewBPForm205 = async () => {
  if (!formRef.value) return;

  try {
    await formRef.value.validate();

    if (retirees.value.length === 0) {
      ElMessage.warning("Please load retirees first");
      return;
    }

    generatingReport.value = true;
    await previewBPForm205Api({
      fiscal_year: formData.value.fiscal_year,
      prepared_by_name: formData.value.prepared_by_name,
      prepared_by_position: formData.value.prepared_by_position,
      approved_by_name: formData.value.approved_by_name,
      approved_by_position: formData.value.approved_by_position,
      date: formData.value.date,
    });
  } catch (error) {
    console.error("Failed to preview BP FORM 205:", error);
  } finally {
    generatingReport.value = false;
  }
};

const printBPForm205 = async () => {
  if (!formRef.value) return;

  try {
    await formRef.value.validate();

    if (retirees.value.length === 0) {
      ElMessage.warning("Please load retirees first");
      return;
    }

    await ElMessageBox.confirm(
      `Are you sure you want to download BP FORM 205 for fiscal year ${formData.value.fiscal_year}?`,
      "Confirm Download PDF",
      {
        confirmButtonText: "Download",
        cancelButtonText: "Cancel",
        type: "info",
      }
    );

    generatingReport.value = true;
    await generateBPForm205({
      fiscal_year: formData.value.fiscal_year,
      prepared_by_name: formData.value.prepared_by_name,
      prepared_by_position: formData.value.prepared_by_position,
      approved_by_name: formData.value.approved_by_name,
      approved_by_position: formData.value.approved_by_position,
      date: formData.value.date,
    });
  } catch (error) {
    if (error !== "cancel") {
      console.error("Failed to download BP FORM 205:", error);
    }
  } finally {
    generatingReport.value = false;
  }
};

const handleDownloadWord = async () => {
  if (!formRef.value) return;

  try {
    await formRef.value.validate();

    if (retirees.value.length === 0) {
      ElMessage.warning("Please load retirees first");
      return;
    }

    generatingReport.value = true;
    await downloadWord({
      fiscal_year: formData.value.fiscal_year,
      prepared_by_name: formData.value.prepared_by_name,
      prepared_by_position: formData.value.prepared_by_position,
      approved_by_name: formData.value.approved_by_name,
      approved_by_position: formData.value.approved_by_position,
      date: formData.value.date,
    });
  } catch (error) {
    console.error("Failed to download BP FORM 205 DOCX:", error);
  } finally {
    generatingReport.value = false;
  }
};

const handleDownloadExcel = async () => {
  if (!formRef.value) return;

  try {
    await formRef.value.validate();

    if (retirees.value.length === 0) {
      ElMessage.warning("Please load retirees first");
      return;
    }

    generatingReport.value = true;
    await downloadExcel({
      fiscal_year: formData.value.fiscal_year,
      prepared_by_name: formData.value.prepared_by_name,
      prepared_by_position: formData.value.prepared_by_position,
      approved_by_name: formData.value.approved_by_name,
      approved_by_position: formData.value.approved_by_position,
      date: formData.value.date,
    });
  } catch (error) {
    console.error("Failed to download BP FORM 205 Excel:", error);
  } finally {
    generatingReport.value = false;
  }
};

// Lifecycle
onMounted(async () => {
  await loadData();
});
</script>

<style scoped>
/* Retirement Benefits Form Container */
.retirement-form-container {
  background: #ffffff;
  border-radius: 12px;
  padding: 24px;
  margin-bottom: 20px;
  box-shadow:
    0 1px 3px 0 rgba(0, 0, 0, 0.1),
    0 1px 2px 0 rgba(0, 0, 0, 0.06);
  border: 1px solid #e5e7eb;
}

.retirement-form {
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

.retirees-table-container {
  background: #ffffff;
  border-radius: 12px;
  margin-bottom: 20px;
  box-shadow:
    0 1px 3px 0 rgba(0, 0, 0, 0.1),
    0 1px 2px 0 rgba(0, 0, 0, 0.06);
  border: 1px solid #e5e7eb;
}

.retirees-card {
  border: none;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-weight: 600;
  font-size: 16px;
}

.retirees-count {
  color: #909399;
  font-size: 14px;
  font-weight: normal;
}

.filters-container-inline {
  margin-bottom: 16px;
}

.empty-state-container {
  background: #ffffff;
  border-radius: 12px;
  padding: 40px;
  margin-bottom: 20px;
  box-shadow:
    0 1px 3px 0 rgba(0, 0, 0, 0.1),
    0 1px 2px 0 rgba(0, 0, 0, 0.06);
  border: 1px solid #e5e7eb;
}

.table-actions {
  padding-top: 16px;
  border-top: 1px solid #e5e7eb;
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  margin-top: 20px;
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

/* Print Preview Styles */
.mt-6 {
  margin-top: 1.5rem;
}
.flex {
  display: flex;
}
.items-center {
  align-items: center;
}
.justify-between {
  justify-content: space-between;
}
.gap-2 {
  gap: 0.5rem;
}
.text-base {
  font-size: 1rem;
  line-height: 1.5rem;
}
.font-semibold {
  font-weight: 600;
}
.text-xs {
  font-size: 0.75rem;
  line-height: 1rem;
}
.text-slate-600 {
  color: #475569;
}
.text-gray-500 {
  color: #6b7280;
}
.text-center {
  text-align: center;
}
.py-10 {
  padding-top: 2.5rem;
  padding-bottom: 2.5rem;
}
.border {
  border-width: 1px;
  border-style: solid;
  border-color: #e5e7eb;
}
.rounded {
  border-radius: 0.25rem;
}
.overflow-hidden {
  overflow: hidden;
}
.w-full {
  width: 100%;
}
.h-full {
  height: 100%;
}
.mr-1 {
  margin-right: 0.25rem;
}
.mb-3 {
  margin-bottom: 0.75rem;
}
</style>
