<template>
  <div class="extra-bonus-form">
    <!-- Form Container -->
    <div class="form-container">
      <el-form
        :model="formData"
        :rules="formRules"
        ref="formRef"
        label-width="160px"
        class="bonus-form"
      >
        <div class="form-row">
          <el-form-item label="Extra Bonus Type" prop="extra_bonus_type_id">
            <el-select
              v-model="formData.extra_bonus_type_id"
              placeholder="Select Extra Bonus Type"
              style="width: 250px"
              @change="onExtraBonusTypeChange"
            >
              <el-option
                v-for="type in extraBonusTypes"
                :key="type.id"
                :label="type.name"
                :value="type.id"
              />
            </el-select>
          </el-form-item>

          <el-form-item label="Division" prop="department_id">
            <el-select
              v-model="formData.department_id"
              placeholder="Select Division"
              style="width: 280px"
              filterable
              clearable
              @change="onDepartmentChange"
            >
              <el-option
                :key="'all-divisions'"
                label="All"
                :value="'all'"
              />
              <el-option
                v-for="division in divisionOptions"
                :key="division.id"
                :label="division.name"
                :value="division.id"
              />
            </el-select>
          </el-form-item>

          <el-form-item label="Year" prop="year_id">
            <el-select
              v-model="formData.year_id"
              placeholder="Select Year"
              style="width: 120px"
              @change="onYearChange"
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

        <div class="form-actions">
          <el-tooltip
            v-if="formData.department_id === 'all'"
            content="Select a specific office to save the payroll"
            placement="top"
          >
            <span>
              <el-button
                type="success"
                @click="savePayroll"
                :loading="saving"
                icon="el-icon-check"
                size="default"
                :disabled="true"
              >
                Save Payroll
              </el-button>
            </span>
          </el-tooltip>
          <el-button
            v-else
            type="success"
            @click="savePayroll"
            :loading="saving"
            icon="el-icon-check"
            size="default"
            :disabled="!hasEmployees || !hasValidAmounts"
          >
            Save Payroll
          </el-button>
          <el-button
            v-if="
              formData.extra_bonus_id &&
              formData.extra_bonus_id > 0 &&
              !formData.is_posted
            "
            type="warning"
            @click="postPayroll"
            :loading="posting"
            icon="el-icon-upload"
            size="default"
          >
            Post Payroll
          </el-button>
          <el-button
            v-if="
              formData.extra_bonus_id &&
              formData.extra_bonus_id > 0 &&
              formData.is_posted
            "
            type="info"
            @click="unpostPayroll"
            :loading="unposting"
            icon="el-icon-download"
            size="default"
          >
            Unpost Payroll
          </el-button>
        </div>
      </el-form>
    </div>

    <!-- Employee List -->
    <div v-if="employees.length > 0" class="employee-list-container">
      <div class="list-header">
        <h3>Employee List</h3>
        <span class="employee-count">{{ employees.length }} employee(s)</span>
      </div>

      <div class="table-container">
        <el-table
          :data="employees"
          border
          style="width: 100%"
          v-loading="loadingEmployees"
          :header-cell-style="{
            background: '#f8fafc',
            color: '#374151',
            fontWeight: '600',
            borderBottom: '2px solid #e5e7eb',
          }"
          :cell-style="{ borderBottom: '1px solid #f3f4f6' }"
          stripe
          class="employee-table"
        >
          <el-table-column prop="employee_no" label="Employee #" width="120">
            <template #default="scope">
              <div class="employee-no">
                {{ scope.row.employee_no || "N/A" }}
              </div>
            </template>
          </el-table-column>

          <el-table-column prop="name" label="Employee Name" min-width="200">
            <template #default="scope">
              <div class="employee-name">{{ scope.row.name || "N/A" }}</div>
            </template>
          </el-table-column>

          <el-table-column prop="position" label="Position" min-width="180">
            <template #default="scope">
              <div class="position-name">{{ scope.row.position || "N/A" }}</div>
            </template>
          </el-table-column>

          <el-table-column
            prop="salary"
            label="Monthly Salary"
            width="150"
            align="right"
          >
            <template #default="scope">
              <span class="salary-amount"
                >₱{{ formatCurrency(scope.row.salary) }}</span
              >
            </template>
          </el-table-column>

          <el-table-column
            prop="amount"
            label="Amount"
            min-width="100"
            align="right"
          >
            <template #default="scope">
              <el-input
                v-model="scope.row.amount"
                type="number"
                step="0.01"
                min="0"
                placeholder="0.00"
                style="min-width: 120px"
                @input="validateAmount(scope.row)"
                :disabled="formData.is_posted"
              >
                <template #prepend>₱</template>
              </el-input>
            </template>
          </el-table-column>
        </el-table>
      </div>

      <!-- Summary -->
      <div class="summary-section">
        <div class="summary-item">
          <label>Total Employees:</label>
          <span>{{ employees.length }}</span>
        </div>
        <div class="summary-item">
          <label>Total Amount:</label>
          <span class="total-amount">₱{{ formatCurrency(totalAmount) }}</span>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div
      v-if="!loadingEmployees && employees.length === 0 && formSelectionMade"
      class="empty-state"
    >
      <div class="empty-icon">
        <i class="el-icon-user"></i>
      </div>
      <h3>No Employees Found</h3>
      <p>
        No employees found for the selected criteria. Please check your
        selection or try different filters.
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from "vue";
import { ElMessage, ElMessageBox } from "element-plus";

const props = defineProps({
  extraBonusTypes: {
    type: Array,
    default: () => [],
  },
  divisions: {
    type: Array,
    default: () => [],
  },
  /** @deprecated use divisions — kept for backward compatibility */
  departments: {
    type: Array,
    default: () => [],
  },
  employees: {
    type: Array,
    default: () => [],
  },
  loading: {
    type: Boolean,
    default: false,
  },
  initialData: {
    type: Object,
    default: () => ({}),
  },
});

const emit = defineEmits([
  "load-employees",
  "save-payroll",
  "post-payroll",
  "unpost-payroll",
]);

const divisionOptions = computed(() =>
  props.divisions?.length ? props.divisions : props.departments,
);

// Reactive data
const formRef = ref(null);
const loadingEmployees = ref(false);
const saving = ref(false);
const posting = ref(false);
const unposting = ref(false);
const employees = ref([]);

// Form data
const formData = ref({
  extra_bonus_id: 0,
  extra_bonus_type_id: null,
  department_id: null,
  year_id: new Date().getFullYear(),
  is_posted: false,
});

const formRules = {
  extra_bonus_type_id: [
    {
      required: true,
      message: "Please select an extra bonus type",
      trigger: "change",
    },
  ],
  department_id: [
    { required: true, message: "Please select a division", trigger: "change" },
  ],
  year_id: [
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

const canLoadEmployees = computed(() => {
  return (
    formData.value.extra_bonus_type_id &&
    formData.value.department_id &&
    formData.value.year_id
  );
});

const hasEmployees = computed(() => {
  return employees.value && employees.value.length > 0;
});

const hasValidAmounts = computed(() => {
  return employees.value.some(
    (emp) => emp.amount && parseFloat(emp.amount) > 0
  );
});

const totalAmount = computed(() => {
  return employees.value.reduce((total, emp) => {
    const amount = parseFloat(emp.amount) || 0;
    return total + amount;
  }, 0);
});

const formSelectionMade = computed(() => {
  return canLoadEmployees.value;
});

// Methods
const formatCurrency = (amount) => {
  if (!amount) return "0.00";
  return parseFloat(amount).toLocaleString("en-US", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
};

const validateAmount = (employee) => {
  const amount = parseFloat(employee.amount) || 0;
  if (amount < 0) {
    employee.amount = 0;
  }
};

const onExtraBonusTypeChange = async () => {
  // Clear employees when extra bonus type changes
  employees.value = [];
  // Auto-load employees if all fields are selected
  await autoLoadEmployees();
};

const onDepartmentChange = async () => {
  employees.value = [];
  if (formData.value.department_id === "all") {
    return;
  }
  await autoLoadEmployees();
};

const onYearChange = async () => {
  // Clear employees when year changes
  employees.value = [];
  // Auto-load employees if all fields are selected
  await autoLoadEmployees();
};

const autoLoadEmployees = async () => {
  if (!canLoadEmployees.value) {
    return;
  }

  try {
    loadingEmployees.value = true;
    emit("load-employees", {
      extra_bonus_type_id: formData.value.extra_bonus_type_id,
      department_id: formData.value.department_id,
      year_id: formData.value.year_id,
    });
  } catch (error) {
    console.error("Failed to auto-load employees:", error);
  } finally {
    loadingEmployees.value = false;
  }
};

const savePayroll = async () => {
  if (!formRef.value) return;

  try {
    await formRef.value.validate();

    if (!hasEmployees.value) {
      ElMessage.warning("Please load employees first");
      return;
    }

    if (!hasValidAmounts.value) {
      ElMessage.warning("Please enter at least one valid amount");
      return;
    }

    const employeeData = employees.value.filter(
      (emp) => parseFloat(emp.amount) > 0
    );

    if (employeeData.length === 0) {
      ElMessage.warning("No employees with valid amounts to save");
      return;
    }

    await ElMessageBox.confirm(
      `Are you sure you want to save the extra bonus payroll for ${employeeData.length} employee(s)?`,
      "Confirm Save",
      {
        confirmButtonText: "Save",
        cancelButtonText: "Cancel",
        type: "warning",
      }
    );

    saving.value = true;

    const payrollData = {
      extra_bonus_id: formData.value.extra_bonus_id,
      extra_bonus_type_id: formData.value.extra_bonus_type_id,
      department_id: formData.value.department_id,
      year_id: formData.value.year_id,
      employee_id: employeeData.map((emp) => emp.employee_id),
      amount: employeeData.map((emp) => parseFloat(emp.amount)),
    };

    emit("save-payroll", payrollData);
  } catch (error) {
    if (error !== "cancel") {
      console.error("Validation failed:", error);
    }
  } finally {
    saving.value = false;
  }
};

const postPayroll = async () => {
  try {
    await ElMessageBox.confirm(
      "Are you sure you want to post this extra bonus payroll? This action cannot be undone.",
      "Confirm Post",
      {
        confirmButtonText: "Post",
        cancelButtonText: "Cancel",
        type: "warning",
      }
    );

    posting.value = true;
    emit("post-payroll", {
      extra_bonus_id: formData.value.extra_bonus_id,
      type_id: 1, // 1 for post
    });
  } catch {
    // User cancelled
  } finally {
    posting.value = false;
  }
};

const unpostPayroll = async () => {
  try {
    await ElMessageBox.confirm(
      "Are you sure you want to unpost this extra bonus payroll?",
      "Confirm Unpost",
      {
        confirmButtonText: "Unpost",
        cancelButtonText: "Cancel",
        type: "warning",
      }
    );

    unposting.value = true;
    emit("unpost-payroll", {
      extra_bonus_id: formData.value.extra_bonus_id,
      type_id: 0, // 0 for unpost
    });
  } catch {
    // User cancelled
  } finally {
    unposting.value = false;
  }
};

// Initialize form data
const initializeForm = () => {
  if (props.initialData && Object.keys(props.initialData).length > 0) {
    formData.value = { ...formData.value, ...props.initialData };
  }
};

// Watch for prop changes
watch(
  () => props.employees,
  (newEmployees) => {
    employees.value = [...newEmployees];
  },
  { immediate: true }
);

watch(
  () => props.initialData,
  (newData) => {
    if (newData && Object.keys(newData).length > 0) {
      formData.value = { ...formData.value, ...newData };
    }
  },
  { immediate: true }
);

// Expose methods for parent component
defineExpose({
  formData,
  resetForm: () => {
    formData.value = {
      extra_bonus_id: 0,
      extra_bonus_type_id: null,
      department_id: null,
      year_id: new Date().getFullYear(),
      is_posted: false,
    };
    employees.value = [];
  },
  setEmployees: (newEmployees) => {
    employees.value = [...newEmployees];
  },
  setFormData: (newFormData) => {
    formData.value = { ...formData.value, ...newFormData };
  },
});

// Lifecycle
onMounted(() => {
  initializeForm();
});
</script>

<style scoped>
.extra-bonus-form {
  background: #ffffff;
  border-radius: 12px;
  overflow: hidden;
  box-shadow:
    0 1px 3px 0 rgba(0, 0, 0, 0.1),
    0 1px 2px 0 rgba(0, 0, 0, 0.06);
}

.form-container {
  padding: 24px;
  border-bottom: 1px solid #e5e7eb;
}

.bonus-form {
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
  flex-wrap: wrap;
}

.employee-list-container {
  padding: 24px;
}

.list-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.list-header h3 {
  margin: 0;
  color: #374151;
  font-weight: 600;
}

.employee-count {
  background: #e5e7eb;
  color: #374151;
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
}

.table-container {
  overflow-x: auto;
  margin-bottom: 20px;
}

.employee-table {
  font-size: 14px;
}

.employee-table :deep(.el-table__header) {
  background: #f8fafc;
}

.employee-table :deep(.el-table__row:hover) {
  background: #f8fafc;
}

.employee-no {
  font-weight: 600;
  color: #1f2937;
  font-size: 13px;
}

.employee-name {
  font-weight: 500;
  color: #374151;
}

.position-name {
  color: #6b7280;
  font-size: 13px;
}

.salary-amount {
  font-weight: 600;
  color: #374151;
}

.summary-section {
  display: flex;
  justify-content: flex-end;
  gap: 30px;
  padding: 16px 20px;
  background: #f8fafc;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
}

.summary-item {
  display: flex;
  align-items: center;
  gap: 8px;
}

.summary-item label {
  font-weight: 600;
  color: #374151;
}

.summary-item span {
  color: #6b7280;
}

.total-amount {
  font-weight: 700;
  color: #059669 !important;
  font-size: 16px;
}

.empty-state {
  text-align: center;
  padding: 60px 20px;
  color: #6b7280;
}

.empty-icon {
  font-size: 48px;
  color: #d1d5db;
  margin-bottom: 16px;
}

.empty-state h3 {
  margin: 0 0 8px 0;
  color: #374151;
  font-weight: 600;
}

.empty-state p {
  margin: 0;
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

  .list-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }

  .summary-section {
    flex-direction: column;
    gap: 12px;
  }
}
</style>
