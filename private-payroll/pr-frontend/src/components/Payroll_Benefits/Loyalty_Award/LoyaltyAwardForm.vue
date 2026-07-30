<template>
  <div class="loyalty-award-form">
    <div class="form-container">
      <!-- Form Header -->
      <div class="form-header">
        <h3>
          {{
            props.isDetailView
              ? "Loyalty Award Details"
              : isEdit
                ? "Edit Loyalty Award"
                : "Create Loyalty Award"
          }}
        </h3>
        <el-button @click="handleClose" type="danger" plain>
          <i class="el-icon-arrow-left"></i>
          Back to List
        </el-button>
      </div>

      <el-form
        ref="formRef"
        :model="formData"
        :rules="formRules"
        label-width="150px"
        v-loading="loading"
        class="loyalty-form"
        :disabled="props.isDetailView"
      >
        <el-row :gutter="20">
          <!-- <el-col :span="8">
            <el-form-item label="Branch" prop="branch_id">
              <el-select
                v-model="formData.branch_id"
                placeholder="Select Branch"
                style="width: 100%"
                clearable
                @change="handleBranchChange"
              >
                <el-option
                  v-for="branch in branches"
                  :key="branch.id"
                  :label="branch.name"
                  :value="branch.id"
                />
              </el-select>
            </el-form-item>
          </el-col> -->
          <el-col :span="8">
            <el-form-item label="Month" prop="month_id">
              <el-select
                v-model="formData.month_id"
                placeholder="Select Month"
                style="width: 100%"
                clearable
                @change="handleMonthChange"
              >
                <el-option
                  v-for="month in months"
                  :key="month.id"
                  :label="month.name"
                  :value="month.id"
                />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="8">
            <el-form-item label="Year" prop="year">
              <el-select
                v-model="formData.year"
                placeholder="Select Year"
                style="width: 100%"
                clearable
                @change="handleYearChange"
              >
                <el-option
                  v-for="year in availableYears"
                  :key="year"
                  :label="year"
                  :value="year"
                />
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>

        <!-- Employee Management Section -->
        <div>
          <el-divider content-position="left">Employee Management</el-divider>

          <div class="employee-management-header">
            <h4>Selected Employees</h4>
            <el-button
              v-if="!props.isDetailView"
              type="primary"
              @click="openAddEmployeeDialog"
              :disabled="!formData.month_id || !formData.year"
            >
              <i class="el-icon-plus"></i>
              {{ formData.id > 0 ? "Add Employee" : "Add Employee" }}
            </el-button>
          </div>

          <div
            v-if="!employees || employees.length === 0"
            class="empty-employee-state"
          >
            <div class="empty-content">
              <i class="el-icon-user-solid empty-icon"></i>
              <p class="empty-text">
                <el-empty
                  description="Select month and year to see eligible employees"
                />
              </p>
              <el-button
                v-if="!props.isDetailView && formData.id > 0"
                type="primary"
                @click="openAddEmployeeDialog"
                :disabled="!formData.month_id || !formData.year"
                size="small"
              >
                Add First Employee
              </el-button>
            </div>
          </div>

          <el-table
            v-else-if="employees && employees.length > 0"
            :data="employees"
            style="width: 100%"
            max-height="300"
          >
            <el-table-column
              prop="employee_no"
              label="Employee No"
              width="120"
            />
            <el-table-column prop="name" label="Name" width="200" />
            <el-table-column prop="position" label="Position" min-width="150" />
            <el-table-column
              prop="department"
              label="Department"
              min-width="150"
            />
            <el-table-column
              prop="date_hired"
              label="Date Hired"
              min-width="150"
            >
              <template #default="scope">
                {{ formatDateHired(scope.row.date_hired) }}
              </template>
            </el-table-column>
            <el-table-column
              prop="years"
              label="Years of Service"
              width="120"
              align="center"
            >
              <template #default="scope">
                <el-tag type="info" size="small"
                  >{{ scope.row.years }} years</el-tag
                >
              </template>
            </el-table-column>
            <el-table-column
              prop="cashAward"
              label="Cash Award"
              width="120"
              align="right"
            >
              <template #default="scope">
                ₱{{ formatCashAward(scope.row) }}
              </template>
            </el-table-column>
            <el-table-column label="Award Setup" width="200">
              <template #default="scope">
                <el-select
                  v-model="scope.row.loyaltyAwardSetupId"
                  placeholder="Select Award"
                  size="small"
                  style="width: 100%"
                  :disabled="props.isDetailView"
                  clearable
                >
                  <el-option
                    v-for="setup in loyaltyAwardSetup"
                    :key="setup.id"
                    :label="`${setup.years_of_service} years - ₱${setup.cash_award?.toLocaleString() || '0'}`"
                    :value="Number(setup.id)"
                  />
                </el-select>
              </template>
            </el-table-column>
            <el-table-column label="Actions" width="100">
              <template #default="scope">
                <div class="actions-cell">
                  <el-button
                    v-if="!props.isDetailView"
                    size="small"
                    type="danger"
                    @click="removeEmployee(scope.$index)"
                  >
                    Remove
                  </el-button>
                </div>
              </template>
            </el-table-column>
          </el-table>

          <!-- Summary Section -->
          <div v-if="employees && employees.length > 0" class="mt-4">
            <el-divider content-position="left">Summary</el-divider>
            <div class="summary-stats">
              <el-row :gutter="16">
                <el-col :span="6">
                  <el-card class="stat-card">
                    <div class="stat-content">
                      <div class="stat-label">Total Employees</div>
                      <div class="stat-value">{{ employees.length }}</div>
                    </div>
                  </el-card>
                </el-col>

                <el-col :span="6">
                  <el-card class="stat-card">
                    <div class="stat-content">
                      <div class="stat-label">Total Cash Award</div>
                      <div class="stat-value">
                        ₱{{ formatCurrency(totalCashAward) }}
                      </div>
                    </div>
                  </el-card>
                </el-col>

                <el-col :span="6">
                  <el-card class="stat-card">
                    <div class="stat-content">
                      <div class="stat-label">Average Years</div>
                      <div class="stat-value">
                        {{ averageYears.toFixed(1) }}
                      </div>
                      <div class="stat-subtext">years of service</div>
                    </div>
                  </el-card>
                </el-col>

                <el-col :span="6">
                  <el-card class="stat-card">
                    <div class="stat-content">
                      <div class="stat-label">Award Setup Status</div>
                      <div class="stat-value">{{ employeesWithAward }}</div>
                      <div class="stat-subtext">
                        missing: {{ employeesMissingAward }}
                      </div>
                    </div>
                  </el-card>
                </el-col>
              </el-row>
            </div>
          </div>
        </div>
      </el-form>

      <!-- Form Actions -->
      <div v-if="!props.isDetailView" class="form-actions">
        <el-button @click="handleClose" type="danger">Cancel</el-button>
        <el-button
          type="primary"
          @click="handleSave"
          :loading="loading"
          :disabled="!isFormValid"
        >
          {{ isEdit ? "Update" : formData.id > 0 ? "Update" : "Save" }}
        </el-button>
      </div>
    </div>

    <!-- Add Employee Dialog -->
    <el-dialog
      v-model="showAddEmployeeDialog"
      title="Add Employee to Loyalty Award"
      width="60%"
      :close-on-click-modal="false"
    >
      <div class="dialog-content">
        <div class="search-section">
          <el-input
            v-model="searchQuery"
            placeholder="Search employees..."
            clearable
            style="width: 100%; margin-bottom: 16px"
            @input="filterEmployees"
          >
            <template #prefix>
              <i class="el-icon-search"></i>
            </template>
          </el-input>
        </div>

        <el-divider content-position="left">
          <h4 style="font-size: 16px; font-weight: 600; color: #1f2937">
            List of Eligible Employees
          </h4>
        </el-divider>

        <el-table
          ref="employeeSelectionTable"
          :data="filteredAvailableEmployees"
          style="width: 100%"
          max-height="400"
          @selection-change="handleSelectionChange"
          v-loading="loadingEmployees"
        >
          <el-table-column type="selection" width="55" />
          <el-table-column prop="employee_no" label="Employee No" width="120" />
          <el-table-column prop="name" label="Name" width="200" />
          <el-table-column
            prop="department"
            label="Department"
            min-width="150"
          />
          <el-table-column prop="position" label="Position" width="200" />
          <el-table-column
            prop="years"
            label="Years of Service"
            width="150"
            align="center"
          >
            <template #default="scope">
              <el-tag
                type="primary"
                size="medium"
                style="color: #000; font-weight: 800"
                >{{ scope.row.years }} years</el-tag
              >
            </template>
          </el-table-column>
        </el-table>
      </div>

      <template #footer>
        <div class="dialog-footer">
          <div class="selection-info">
            <span v-if="selectedEmployeesToAdd.length > 0">
              {{ selectedEmployeesToAdd.length }} employee(s) selected
            </span>
          </div>
          <div class="dialog-actions">
            <el-button @click="closeAddEmployeeDialog">Cancel</el-button>
            <el-button
              type="primary"
              @click="addSelectedEmployees"
              :disabled="selectedEmployeesToAdd.length === 0"
              :loading="loadingEmployees"
            >
              Add Selected ({{ selectedEmployeesToAdd.length }})
            </el-button>
          </div>
        </div>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, computed, watch, nextTick, onMounted } from "vue";
import { useLoyaltyAward } from "../../../Composables/useLoyaltyAwardBenefits.js";
import { ElMessage } from "element-plus";
import { loyaltyAwardApi } from "../../../services/api.js";

const props = defineProps({
  editData: {
    type: Object,
    default: null,
  },
  isDetailView: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(["saved", "close"]);

const {
  loading,
  formData,
  branches,
  months,
  employees,
  loyaltyAwardSetup,
  maxYear,
  minYear,
  isFormValid,
  loadFormData,
  loadEmployeesForAward,
  saveLoyaltyAward,
  resetFormData,
} = useLoyaltyAward();

// Local state
const isEdit = computed(() => props.editData && props.editData.id > 0);
const formRef = ref(null);
const availableEmployees = ref([]);
const selectedEmployeesToAdd = ref([]);
const loadingEmployees = ref(false);
const showAddEmployeeDialog = ref(false);
const searchQuery = ref("");
const employeeSelectionTable = ref(null);

const formRules = {
  month_id: [
    { required: true, message: "Please select month", trigger: "blur" },
  ],
  year: [{ required: true, message: "Please select year", trigger: "blur" }],
};

// Computed properties
const availableYears = computed(() => {
  const currentYear = new Date().getFullYear();
  const years = [];

  for (let year = 2000; year <= currentYear + 5; year++) {
    years.push(year);
  }
  return years;
});

const getEmployeeCashAward = (employee) => {
  if (!employee) return 0;

  const directValue =
    employee.cashAward ?? employee.cash_award ?? employee.cashaward;

  if (directValue !== undefined && directValue !== null) {
    const numeric = Number(directValue);
    return Number.isFinite(numeric) ? numeric : 0;
  }

  if (employee.loyaltyAwardSetupId) {
    const setup = loyaltyAwardSetup.value.find(
      (s) => s.id === employee.loyaltyAwardSetupId,
    );
    if (setup?.cash_award !== undefined && setup?.cash_award !== null) {
      const numeric = Number(setup.cash_award);
      return Number.isFinite(numeric) ? numeric : 0;
    }
  }

  return 0;
};

const formatCashAward = (employee) => {
  return getEmployeeCashAward(employee).toLocaleString(undefined, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
};

const formatCurrency = (value) => {
  const n = Number(value ?? 0);
  return (Number.isFinite(n) ? n : 0).toLocaleString(undefined, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
};

const totalCashAward = computed(() => {
  if (!employees.value || employees.value.length === 0) return 0;
  return employees.value.reduce(
    (sum, emp) => sum + getEmployeeCashAward(emp),
    0,
  );
});

const averageYears = computed(() => {
  if (!employees.value || employees.value.length === 0) return 0;
  const totalYears = employees.value.reduce(
    (sum, emp) => sum + (emp.years || 0),
    0,
  );
  return totalYears / employees.value.length;
});

const employeesWithAward = computed(() => {
  return (employees.value || []).filter((e) => !!e.loyaltyAwardSetupId).length;
});

const employeesMissingAward = computed(() => {
  return (employees.value || []).filter((e) => !e.loyaltyAwardSetupId).length;
});

const formatDateHired = (date) => {
  if (!date) return "-";
  const d = new Date(date);
  if (Number.isNaN(d.getTime())) return date;
  return d.toLocaleDateString("en-PH", {
    year: "numeric",
    month: "long",
    day: "numeric",
  });
};

const filteredAvailableEmployees = computed(() => {
  if (!searchQuery.value) return availableEmployees.value;
  const query = searchQuery.value.toLowerCase();
  return availableEmployees.value.filter(
    (emp) =>
      emp.name?.toLowerCase().includes(query) ||
      emp.employee_no?.toLowerCase().includes(query),
  );
});

// Watch for editData changes
watch(
  () => props.editData,
  async (newVal) => {
    if (newVal !== null) {
      await loadFormData(newVal.id);
    }
  },
  { immediate: true },
);

// Initialize form when component mounts
onMounted(async () => {
  try {
    if (isEdit.value && props.editData) {
      await loadFormData(props.editData.id);
    } else {
      // For new records, load form data with ID 0
      await loadFormData(0);
      // Clear any existing employees for new records
      employees.value = [];
      availableEmployees.value = [];
      selectedEmployeesToAdd.value = [];
      searchQuery.value = "";
    }
  } catch (error) {
    console.error("Error initializing form:", error);
  }
});

const loadEligibleEmployees = async () => {
  try {
    loadingEmployees.value = true;
    const data = await loadEmployeesForAward(
      formData.value.month_id,
      0,
      formData.value.year,
      formData.value.id,
    );
    // Auto-populate eligible employees for new loyalty awards
    if (
      formData.value.id === 0 &&
      data.employees &&
      data.employees.length > 0
    ) {
      employees.value = data.employees.map((emp) => {
        // Find the exact match for the employee's years of service
        const matchingSetup = loyaltyAwardSetup.value.find(
          (setup) => setup.years_of_service === emp.years,
        );

        // If no exact match, don't assign a default - let user select
        return {
          ...emp,
          loyaltyAwardSetupId: matchingSetup?.id || null,
        };
      });
    }
  } catch (error) {
    console.error("Error loading eligible employees:", error);
    ElMessage.error("Failed to load eligible employees");
  } finally {
    loadingEmployees.value = false;
  }
};

const getMonthName = (monthId) => {
  const month = months.value.find((m) => m.id === monthId);
  return month ? month.name : "";
};

const handleBranchChange = async () => {
  // Auto-load eligible employees when all required fields are filled
  if (formData.value.month_id && formData.value.year) {
    await loadEligibleEmployees();
  }
};

const handleMonthChange = async () => {
  // Auto-load eligible employees when all required fields are filled
  if (formData.value.month_id && formData.value.year) {
    await loadEligibleEmployees();
  }
};

const handleYearChange = async () => {
  // Auto-load eligible employees when all required fields are filled
  if (formData.value.month_id && formData.value.year) {
    await loadEligibleEmployees();
  }
};

const openAddEmployeeDialog = async () => {
  if (
    !formData.value.month_id ||
    !formData.value.year
  ) {
    ElMessage.warning("Please select month and year first");
    return;
  }

  try {
    loadingEmployees.value = true;
    const data = await loadEmployeesForAward(
      formData.value.month_id,
      0,
      formData.value.year,
      formData.value.id,
    );
    availableEmployees.value = data.employees || [];
    showAddEmployeeDialog.value = true;
  } catch (error) {
    console.error("Error loading available employees:", error);
    ElMessage.error("Failed to load employees");
  } finally {
    loadingEmployees.value = false;
  }
};

const closeAddEmployeeDialog = () => {
  showAddEmployeeDialog.value = false;
  searchQuery.value = "";
  selectedEmployeesToAdd.value = [];
  if (employeeSelectionTable.value) {
    employeeSelectionTable.value.clearSelection();
  }
};

const handleSelectionChange = (selection) => {
  selectedEmployeesToAdd.value = selection;
};

const filterEmployees = () => {
  // Filter is handled by computed property
};

const addSelectedEmployees = async () => {
  if (selectedEmployeesToAdd.value.length === 0) return;

  try {
    // Ensure employees array is initialized
    if (!employees.value) {
      employees.value = [];
    }

    // Add selected employees to the main employees list
    selectedEmployeesToAdd.value.forEach((emp) => {
      // Check if employee is already in the list
      const exists = employees.value.some((e) => e.id === emp.id);
      if (!exists) {
        employees.value.push({
          ...emp,
          loyaltyAwardSetupId: loyaltyAwardSetup.value[0]?.id,
        });
      }
    });

    closeAddEmployeeDialog();
    ElMessage.success(
      `${selectedEmployeesToAdd.value.length} employee(s) added successfully`,
    );
  } catch (error) {
    console.error("Error adding employees:", error);
    ElMessage.error("Failed to add employees");
  }
};

const removeEmployee = (index) => {
  if (employees.value && employees.value.length > index) {
    employees.value.splice(index, 1);
  }
};

const handleSave = async () => {
  if (!formRef.value) return;

  try {
    const isNewRecord = formData.value.id === 0;
    await formRef.value.validate();

    const saveData = {
      month_id: formData.value.month_id,
      year: formData.value.year,
      employee_id: (employees.value || []).map((emp) => emp.id),
      loyalty_award_setup_id: (employees.value || []).map(
        (emp) => emp.loyaltyAwardSetupId,
      ),
    };

    const result = await saveLoyaltyAward(formData.value.id, saveData);

    // If this was a new loyalty award, update the form data with the returned ID
    if (isNewRecord && result?.data?.id) {
      formData.value.id = result.data.id;
    }

    // Don't close the form immediately for new records - let user add employees
    if (isNewRecord) {
      ElMessage.success(
        "Loyalty Award created successfully! You can now add employees.",
      );
    } else {
      emit("saved");
      handleClose();
    }
  } catch (error) {
    console.error("Error saving loyalty award:", error);
  }
};

const handleClose = () => {
  // Clear form validation errors
  if (formRef.value) {
    formRef.value.clearValidate();
  }
  emit("close");
  resetFormData();
  // Clear all employee-related data
  employees.value = [];
  availableEmployees.value = [];
  selectedEmployeesToAdd.value = [];
  searchQuery.value = "";
  showAddEmployeeDialog.value = false;
  // Reset form data ID to 0 for next use
  formData.value.id = 0;
};
</script>

<style scoped>
/* Form Container */
.loyalty-award-form {
  width: 100%;
  background: #ffffff;
  border-radius: 12px;
  box-shadow:
    0 1px 3px 0 rgba(0, 0, 0, 0.1),
    0 1px 2px 0 rgba(0, 0, 0, 0.06);
  border: 1px solid #e5e7eb;
  margin-top: 20px;
  overflow: hidden;
}

.form-container {
  padding: 24px;
}

/* Form Header */
.form-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
  padding-bottom: 16px;
  border-bottom: 2px solid #f3f4f6;
  background: #f8fafc;
  margin: -24px -24px 24px -24px;
  padding: 20px 24px;
}

.form-header h3 {
  margin: 0;
  font-size: 20px;
  font-weight: 600;
  color: #1f2937;
}

.form-header .el-button {
  border-radius: 8px;
  font-weight: 500;
  padding: 8px 16px;
}

/* Form Actions */
.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 24px;
  padding-top: 16px;
  border-top: 2px solid #f3f4f6;
}

.form-actions .el-button {
  border-radius: 8px;
  font-weight: 500;
  padding: 10px 20px;
}

.form-actions .el-button--primary {
  background-color: #3b82f6;
  border-color: #3b82f6;
}

.form-actions .el-button--primary:hover {
  background-color: #2563eb;
  border-color: #2563eb;
}

.employee-selection-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.employee-selection-header h4 {
  margin: 0;
  color: #303133;
  font-size: 16px;
  font-weight: 600;
}

.employee-actions {
  display: flex;
  justify-content: center;
  margin-top: 16px;
}

.mt-3 {
  margin-top: 12px;
}

.mt-4 {
  margin-top: 16px;
}

h4 {
  margin-bottom: 10px;
  color: #303133;
}

/* Enhanced form styling */
:deep(.el-form-item__label) {
  font-weight: 600;
  color: #374151;
}

:deep(.el-input__inner),
:deep(.el-select .el-input__inner) {
  border-radius: 8px;
  transition: all 0.2s ease;
}

:deep(.el-input__inner:focus),
:deep(.el-select .el-input__inner:focus) {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Table styling */
:deep(.el-table) {
  border-radius: 8px;
  overflow: hidden;
}

:deep(.el-table__header) {
  background: #f8fafc;
}

:deep(.el-table__header th) {
  background: #f8fafc !important;
  color: #374151;
  font-weight: 600;
  border-bottom: 2px solid #e5e7eb;
}

:deep(.el-table__body tr:hover) {
  background-color: #f9fafb !important;
}

/* Statistic styling */
:deep(.el-statistic__content) {
  color: #1f2937;
}

:deep(.el-statistic__title) {
  color: #6b7280;
  font-weight: 500;
}

/* Button styling */
.el-button {
  border-radius: 6px;
  font-weight: 500;
  transition: all 0.2s ease;
}

.el-button:hover {
  transform: translateY(-1px);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

/* Responsive design */
@media (max-width: 768px) {
  .employee-selection-header {
    flex-direction: column;
    gap: 12px;
    align-items: stretch;
  }

  .employee-actions {
    flex-direction: column;
  }
}

/* Employee Management Styling */
.employee-management-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.employee-management-header h4 {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
  color: #1f2937;
}

.empty-employee-state {
  text-align: center;
  padding: 40px 20px;
  background: #f9fafb;
  border-radius: 8px;
  border: 1px solid #5e6063;
}

.empty-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
}

.empty-icon {
  font-size: 48px;
  color: #9ca3af;
}

.empty-text {
  margin: 0;
  color: #6b7280;
  font-size: 16px;
}

/* Dialog Styling */
.dialog-content {
  padding: 0;
}

.search-section {
  margin-bottom: 16px;
}

.dialog-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 0;
}

.selection-info {
  color: #6b7280;
  font-size: 14px;
}

.dialog-actions {
  display: flex;
  gap: 12px;
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

.actions-cell {
  display: flex;
  gap: 6px;
  align-items: center;
  justify-content: center;
  flex-wrap: nowrap;
  white-space: nowrap;
}

.actions-cell :deep(.el-button__content) {
  white-space: nowrap;
}
</style>
