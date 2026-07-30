<template>
  <div class="uniform-clothing-form">
    <div class="form-container">
      <div class="form-header">
        <h3>
          {{
            props.isDetailView
              ? "Uniform Allowance Details"
              : isEdit
                ? "Edit Uniform Allowance"
                : "Create Uniform Allowance"
          }}
        </h3>
        <el-button @click="handleClose" plain>
          <i class="el-icon-arrow-left"></i>
          Back to List
        </el-button>
      </div>

      <el-form
        ref="formRef"
        :model="formData"
        :rules="formRules"
        label-width="140px"
        v-loading="loading"
        class="allowance-form"
        :disabled="props.isDetailView"
      >
        <div class="form-row">
          <!-- <el-form-item label="Branch" prop="branch_id">
            <el-select
              v-model="formData.branch_id"
              placeholder="Select Branch"
              style="width: 280px"
            >
              <el-option
                v-for="branch in branches"
                :key="branch.id"
                :label="branch.name"
                :value="branch.id"
              />
            </el-select>
          </el-form-item> -->

          <el-form-item label="Division" prop="division_id">
            <el-select
              v-model="formData.division_id"
              placeholder="Select Division"
              style="width: 280px"
              clearable
              @change="handleDivisionChange"
            >
              <el-option
                v-for="division in divisions"
                :key="division.id"
                :label="division.name"
                :value="division.id"
              />
            </el-select>
          </el-form-item>

          <el-form-item label="Month" prop="month_id">
            <el-select
              v-model="formData.month_id"
              placeholder="Select Month"
              style="width: 280px"
            >
              <el-option
                v-for="month in months"
                :key="month.id"
                :label="month.name"
                :value="month.id"
              />
            </el-select>
          </el-form-item>

          <el-form-item label="Year" prop="year">
            <el-select
              v-model="formData.year"
              placeholder="Select Year"
              style="width: 280px"
              clearable
            >
              <el-option
                v-for="year in availableYears"
                :key="year"
                :label="year"
                :value="year"
              />
            </el-select>
          </el-form-item>
        </div>

        <!-- <div v-if="!props.isDetailView" class="form-actions">
          <el-button @click="handleClose">Cancel</el-button>
          <el-button
            type="primary"
            @click="handleSave"
            :loading="loading"
            :disabled="!isFormValid"
          >
            {{ isEdit ? "Update Allowance" : "Save Allowance" }}
          </el-button>
        </div> -->
      </el-form>
    </div>

    <!-- Employee Management Section -->
    <div v-if="formData.id > 0" class="employee-management-section">
      <div class="employee-header">
        <h4>Employee Management</h4>
        <div v-if="!props.isDetailView" class="employee-controls">
          <el-button
            type="primary"
            @click="openAddEmployeeDialog"
            :disabled="loadingEmployees"
          >
            <i class="el-icon-plus"></i>
            Add Employee
          </el-button>
        </div>
      </div>

      <div class="summary-stats mb-4" v-if="selectedEmployees.length">
        <el-row :gutter="16">
          <el-col :span="8">
            <el-card class="stat-card">
              <div class="stat-content">
                <div class="stat-label">Total Employees</div>
                <div class="stat-value">{{ totalEmployees }}</div>
              </div>
            </el-card>
          </el-col>

          <el-col :span="8">
            <el-card class="stat-card">
              <div class="stat-content">
                <div class="stat-label">Total Clothing Allowance</div>
                <div class="stat-value">
                  ₱{{ formatCurrency(totalAllowance) }}
                </div>
              </div>
            </el-card>
          </el-col>
        </el-row>
      </div>

      <!-- Employee Table -->
      <div class="employee-table-container">
        <el-table
          :data="selectedEmployees"
          border
          v-loading="loadingEmployees"
          class="employee-table"
        >
          <!-- <el-table-column prop="photo" label="Photo" width="80" align="center">
            <template #default="scope">
              <div class="employee-photo">
                <img
                  v-if="scope.row.photo"
                  :src="scope.row.photo"
                  :alt="scope.row.name"
                  class="photo-img"
                />
                <i v-else class="el-icon-user-solid photo-placeholder"></i>
              </div>
            </template>
          </el-table-column> -->

          <el-table-column
            prop="employee_no"
            label="Employee No."
            width="auto"
          />
          <el-table-column prop="name" label="Name" width="auto" />
          <el-table-column
            prop="employment_type"
            label="Employment Type"
            width="auto"
          />
          <el-table-column prop="position" label="Position" width="auto" />
          <el-table-column prop="division" label="Division" width="auto" />

          <el-table-column
            prop="cloth_rate"
            label="Clothing Allowance"
            width="auto"
            align="right"
          >
            <template #default="scope">
              <span class="amount-display"
                >₱{{ formatCurrency(scope.row.cloth_rate || 0) }}</span
              >
            </template>
          </el-table-column>

          <el-table-column
            v-if="!props.isDetailView"
            label="Actions"
            width="auto"
            align="center"
            fixed="right"
          >
            <template #default="scope">
              <div class="actions-cell">
                <el-button
                  type="danger"
                  size="small"
                  @click="removeEmployeeFromAllowance(scope.row)"
                  :loading="removingEmployeeId === scope.row.id"
                >
                  Remove
                </el-button>
              </div>
            </template>
          </el-table-column>
        </el-table>

        <!-- Empty State -->
        <div
          v-if="!loadingEmployees && selectedEmployees.length === 0"
          class="empty-employees"
        >
          <el-empty
            :description="
              props.isDetailView
                ? 'No employees in this allowance'
                : 'No employees added yet'
            "
          >
            <el-button
              v-if="!props.isDetailView"
              type="primary"
              @click="openAddEmployeeDialog"
            >
              <i class="el-icon-plus"></i>
              Add First Employee
            </el-button>
          </el-empty>
        </div>
      </div>
    </div>

    <div v-if="!props.isDetailView" class="form-actions">
      <el-button @click="handleClose">Cancel</el-button>
      <el-button
        type="primary"
        @click="handleSave"
        :loading="loading"
        :disabled="!isFormValid"
      >
        {{ isEdit ? "Update Allowance" : "Save Allowance" }}
      </el-button>
    </div>

    <!-- Add Employee Dialog -->
    <el-dialog
      v-model="showAddEmployeeDialog"
      title="Add Employee to Uniform Allowance"
      width="70%"
      :before-close="closeAddEmployeeDialog"
    >
      <div class="add-employee-content">
        <el-input
          v-model="employeeSearchQuery"
          placeholder="Search employees by name or employee number..."
          @input="searchEmployees"
          class="employee-search"
          prefix-icon="el-icon-search"
        />

        <el-table
          ref="employeeSelectionTable"
          :data="filteredEmployees"
          border
          v-loading="loadingEmployees"
          class="employee-selection-table"
          style="margin-top: 16px"
          @selection-change="handleSelectionChange"
        >
          <el-table-column type="selection" width="55" align="center" />

          <el-table-column prop="photo" label="Photo" width="80" align="center">
            <template #default="scope">
              <div class="employee-photo">
                <img
                  v-if="scope.row.photo"
                  :src="normalizePhotoSrc(scope.row.photo)"
                  :alt="scope.row.name"
                  class="photo-img"
                />
                <i v-else class="el-icon-user-solid photo-placeholder"></i>
              </div>
            </template>
          </el-table-column>

          <el-table-column
            prop="employee_no"
            label="Employee No."
            width="auto"
          />
          <el-table-column prop="name" label="Name" width="auto" />
          <el-table-column
            prop="employment_type"
            label="Employment Type"
            width="auto"
          />
          <el-table-column prop="division" label="Division" width="auto" />
          <el-table-column prop="position" label="Position" width="auto" />
        </el-table>
      </div>

      <template #footer>
        <div class="dialog-footer">
          <div class="selection-info">
            <span
              v-if="selectedEmployeesToAdd.length > 0"
              class="selection-count"
            >
              {{ selectedEmployeesToAdd.length }} employee(s) selected
            </span>
          </div>
          <div class="dialog-actions">
            <el-button @click="closeAddEmployeeDialog">Cancel</el-button>
            <el-button
              type="primary"
              @click="addSelectedEmployees"
              :disabled="selectedEmployeesToAdd.length === 0"
            >
              <i class="el-icon-plus"></i>
              Add Selected Employees ({{ selectedEmployeesToAdd.length }})
            </el-button>
          </div>
        </div>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from "vue";
import { useUniformClothingAllowance } from "../../../Composables/useUniformClothingAllowance.js";
import { normalizePhotoSrc } from "../../../utils/employeePhoto.js";
import { ElMessage, ElMessageBox } from "element-plus";

const props = defineProps({
  allowanceData: {
    type: Object,
    default: () => ({}),
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
  divisions,
  employees,
  selectedEmployees,
  availableYears,
  loadFormData,
  saveAllowance,
  addEmployees,
  removeEmployee,
  isFormValid,
  resetFormData,
} = useUniformClothingAllowance();

const formRef = ref();
const employeeSelectionTable = ref();

// Employee management state
const showAddEmployeeDialog = ref(false);
const employeeSearchQuery = ref("");
const filteredEmployees = ref([]);
const selectedEmployeesToAdd = ref([]);
const removingEmployeeId = ref(null);
const loadingEmployees = ref(false);
const isEdit = computed(() => props.allowanceData && props.allowanceData.id);

const formRules = {
  division_id: [
    { required: true, message: "Please select a division", trigger: "blur" },
  ],
  month_id: [
    { required: true, message: "Please select a month", trigger: "blur" },
  ],
  year: [{ required: true, message: "Please enter a year", trigger: "blur" }],
};

const handleSave = async () => {
  try {
    await formRef.value.validate();

    const data = {
      division_id: formData.value.division_id,
      department_id: formData.value.division_id,
      month_id: formData.value.month_id,
      year: formData.value.year,
    };

    await ElMessageBox.confirm(
      isEdit.value
        ? "Are you sure you want to update this allowance?"
        : "Are you sure you want to save this allowance?",
      isEdit.value ? "Confirm Update" : "Confirm Save",
      {
        confirmButtonText: isEdit.value ? "Update" : "Save",
        cancelButtonText: "Cancel",
        type: "warning",
      },
    );

    const result = await saveAllowance(
      isEdit.value ? props.allowanceData.id : 0,
      data,
    );
    emit("saved", result);
    handleClose();
  } catch (error) {
    if (error === "cancel") return;
    console.error("Error saving allowance:", error);
  }
};

const handleClose = () => {
  resetFormData();
  formRef.value?.resetFields();
  formRef.value?.clearValidate();
  emit("close");
};

const handleDivisionChange = async (divisionId) => {
  const allowanceId = Number(formData.value.id || props.allowanceData?.id || 0);
  const resolvedDivisionId = divisionId ?? formData.value.division_id;
  const monthId = formData.value.month_id;
  const year = formData.value.year;

  if (!resolvedDivisionId) return;

  if (allowanceId === 0) {
    selectedEmployees.value = [];
  }

  await loadFormData(allowanceId, {
    divisionId: resolvedDivisionId,
    preserveSelectedEmployees: allowanceId > 0,
  });

  formData.value.division_id = resolvedDivisionId;
  formData.value.month_id = monthId;
  formData.value.year = year;
};

const loadFormDataForEdit = async () => {
  try {
    const recordId = Number(props.allowanceData?.id || 0);
    if (recordId > 0) {
      await loadFormData(recordId);
    } else {
      resetFormData();
      await loadFormData(0);
    }
  } catch (error) {
    console.error("Error loading form data:", error);
  }
};

// Employee management methods
const formatCurrency = (amount) => {
  if (!amount) return "0.00";
  return parseFloat(amount).toLocaleString("en-US", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
};

const searchEmployees = () => {
  if (!employeeSearchQuery.value) {
    filteredEmployees.value = employees.value || [];
    return;
  }

  const query = employeeSearchQuery.value.toLowerCase();
  filteredEmployees.value = (employees.value || []).filter(
    (emp) =>
      emp.name?.toLowerCase().includes(query) ||
      emp.employee_no?.toLowerCase().includes(query),
  );
};

const handleSelectionChange = (selection) => {
  // Update selectedEmployeesToAdd based on checkbox selection
  selectedEmployeesToAdd.value = selection.map((emp) => ({
    ...emp,
  }));
};

const addSelectedEmployees = async () => {
  if (selectedEmployeesToAdd.value.length === 0) return;

  try {
    loadingEmployees.value = true;

    // Prepare data in the format expected by the backend
    const employeeData = {
      m_header_id: formData.value.id,
      m_year: formData.value.year,
      employee_id: selectedEmployeesToAdd.value.map((emp) => emp.id),
    };

    await addEmployees(formData.value.id, employeeData);

    // Refresh employee list
    await loadFormData(formData.value.id);

    closeAddEmployeeDialog();
    ElMessage.success(
      `${selectedEmployeesToAdd.value.length} employee(s) added successfully`,
    );
  } catch (error) {
    console.error("Error adding employees:", error);
  } finally {
    loadingEmployees.value = false;
  }
};

const removeEmployeeFromAllowance = async (employee) => {
  try {
    await ElMessageBox.confirm(
      `Are you sure you want to remove ${employee.name} from this allowance?`,
      "Confirm Removal",
      {
        confirmButtonText: "Remove",
        cancelButtonText: "Cancel",
        type: "warning",
      },
    );

    removingEmployeeId.value = employee.id;
    await removeEmployee(employee.uniform_clothing_details_id);

    // Refresh employee list
    await loadFormData(formData.value.id);

    ElMessage.success("Employee removed successfully");
  } catch (error) {
    if (error !== "cancel") {
      console.error("Error removing employee:", error);
    }
  } finally {
    removingEmployeeId.value = null;
  }
};

const totalEmployees = computed(() => (selectedEmployees.value || []).length);

const totalAllowance = computed(() => {
  return (selectedEmployees.value || []).reduce((sum, emp) => {
    return sum + Number(emp.cloth_rate || 0);
  }, 0);
});

const openAddEmployeeDialog = async () => {
  try {
    loadingEmployees.value = true;

    if (!formData.value.division_id) {
      ElMessage.warning(
        "Please select a division first before adding employees",
      );
      return;
    }

    await loadFormData(formData.value.id || 0, {
      divisionId: formData.value.division_id,
    });

    // Initialize filtered employees with fresh data
    filteredEmployees.value = employees.value || [];

    // Clear any previous selections
    selectedEmployeesToAdd.value = [];
    employeeSearchQuery.value = "";

    // Open the dialog
    showAddEmployeeDialog.value = true;
  } catch (error) {
    console.error("Error loading employees:", error);
    ElMessage.error("Failed to load employees");
  } finally {
    loadingEmployees.value = false;
  }
};

const closeAddEmployeeDialog = () => {
  showAddEmployeeDialog.value = false;
  employeeSearchQuery.value = "";
  selectedEmployeesToAdd.value = [];
  filteredEmployees.value = employees.value || [];

  // Clear table selection
  if (employeeSelectionTable.value) {
    employeeSelectionTable.value.clearSelection();
  }
};

// Reload employees after first save (new record gets an id)
watch(
  () => formData.value.id,
  (newId, oldId) => {
    if (newId > 0 && !oldId) {
      loadFormData(newId);
    }
  },
);

onMounted(() => {
  loadFormDataForEdit();
  // Clear any validation errors when form first loads
  setTimeout(() => {
    formRef.value?.clearValidate();
  }, 100);
});
</script>

<style scoped>
.uniform-clothing-form {
  background: #ffffff;
  border-radius: 12px;
  overflow: hidden;
  box-shadow:
    0 1px 3px 0 rgba(0, 0, 0, 0.1),
    0 1px 2px 0 rgba(0, 0, 0, 0.06);
  margin-bottom: 20px;
}

.form-container {
  padding: 24px;
}

.form-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.form-header h3 {
  margin: 0;
  color: #374151;
  font-weight: 600;
  font-size: 18px;
}

.allowance-form {
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
  justify-content: flex-end;
  gap: 12px;
  margin-top: 24px;
  margin-bottom: 24px;
  margin-right: 24px;
  padding-top: 16px;
  border-top: 1px solid #e4e7ed;
}

.summary-stats {
  margin: 16px 0 20px 350px;
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
  justify-content: center;
  align-items: center;
}

.stat-label {
  font-size: 13px;
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

/* Responsive Design */
@media (max-width: 768px) {
  .form-row {
    flex-direction: column;
    gap: 16px;
  }

  .form-row .el-form-item {
    width: 100%;
  }

  .form-row .el-select,
  .form-row .el-input-number {
    width: 100% !important;
  }

  .form-actions {
    justify-content: center;
  }
}

/* Employee Management Section */
.employee-management-section {
  margin-top: 32px;
  padding-top: 24px;
  border-top: 1px solid #e5e7eb;
}

.employee-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.employee-header h4 {
  margin: 0;
  color: #374151;
  font-size: 18px;
  font-weight: 600;
}

.employee-table-container {
  background: #ffffff;
  border-radius: 8px;
  overflow: hidden;
}

.employee-table {
  width: 100%;
}

.employee-photo {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto;
}

.photo-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: 50%;
}

.photo-placeholder {
  font-size: 24px;
  color: #9ca3af;
}

.amount-display {
  font-weight: 600;
  color: #059669;
}

.empty-employees {
  padding: 40px 20px;
  text-align: center;
}

/* Add Employee Dialog */
.add-employee-content {
  max-height: 60vh;
  overflow-y: auto;
}

.employee-search {
  width: 100%;
}

.employee-selection-table {
  cursor: pointer;
}

.employee-selection-table .el-table__row:hover {
  background-color: #f3f4f6;
}

.employee-selection-table .el-table__row.el-table__row--striped {
  background-color: #f9fafb;
}

.employee-selection-table .el-table__row.el-table__row--striped:hover {
  background-color: #f3f4f6;
}

/* Selected row styling */
.employee-selection-table .el-table__row.current-row {
  background-color: #dbeafe !important;
}

.employee-selection-table .el-table__row.current-row:hover {
  background-color: #bfdbfe !important;
}

/* Checkbox column styling */
.employee-selection-table .el-table__header .el-checkbox {
  margin-right: 0;
}

.employee-selection-table .el-table__body .el-checkbox {
  margin-right: 0;
}

.dialog-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
}

.selection-info {
  flex: 1;
}

.selection-count {
  color: #059669;
  font-weight: 600;
  font-size: 14px;
}

.dialog-actions {
  display: flex;
  gap: 12px;
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
