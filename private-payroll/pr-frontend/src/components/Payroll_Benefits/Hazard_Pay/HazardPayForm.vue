<template>
  <div class="hazard-pay-form-container">
    <div class="form-header">
      <h3>
        {{
          isEdit ? "Edit Hazard Pay Allowance" : "Create Hazard Pay Allowance"
        }}
      </h3>
      <el-button @click="handleClose" plain>
        <i class="el-icon-arrow-left"></i>
        Back to List
      </el-button>
    </div>
    <el-form
      ref="formRef"
      :model="form"
      :rules="formRules"
      label-width="140px"
      label-position="left"
      v-loading="loading"
      class="hazard-pay-form"
    >
      <div class="form-row">
        <el-form-item label="Division" prop="division_id" required>
          <el-select
            v-model="form.division_id"
            placeholder="Select Division"
            style="width: 280px"
            filterable
            clearable
            :disabled="isPosted"
          >
            <el-option
              v-for="division in divisions"
              :key="division.id"
              :label="division.name"
              :value="division.id"
            />
          </el-select>
        </el-form-item>

        <el-form-item label="Month" prop="month_id" required>
          <el-select
            v-model="form.month_id"
            placeholder="Select Month"
            style="width: 280px"
            :disabled="isPosted"
          >
            <el-option
              v-for="month in months"
              :key="month.id"
              :label="month.name"
              :value="month.id"
            />
          </el-select>
        </el-form-item>

        <el-form-item label="Year" prop="year" required>
          <el-select
            v-model="form.year"
            placeholder="Select Year"
            style="width: 280px"
            :disabled="isPosted"
          >
            <el-option
              v-for="year in yearOptions"
              :key="year"
              :label="year"
              :value="year"
            />
          </el-select>
        </el-form-item>
      </div>

      <!-- <el-form-item v-if="isPosted" label="Status">
        <el-tag type="success" size="large">
          <i class="el-icon-check"></i>
          Posted
        </el-tag>
        <div class="status-note">
          <i class="el-icon-info"></i>
          This hazard pay has been posted and cannot be edited.
        </div>
      </el-form-item> -->
    </el-form>

    <div v-if="hazardIdComputed > 0" class="employee-management">
      <el-divider content-position="left">
        <span class="text-lg font-semibold">Employee Management</span>
      </el-divider>

      <div class="mb-4" v-if="!isPosted && showAddEmployees">
        <el-card
          shadow="never"
          class="border border-gray-200"
          ref="addEmployeesCardRef"
        >
          <template #header>
            <span class="font-medium">Add Employees</span>
          </template>
          <el-table
            :data="availableEmployeesFiltered"
            style="width: 100%"
            @selection-change="handleEmployeeSelection"
            max-height="300"
          >
            <el-table-column type="selection" width="55" />
            <el-table-column
              prop="employee_no"
              label="Employee No"
              width="120"
            />
            <el-table-column prop="name" label="Name" />
            <el-table-column prop="position" label="Position" />
          </el-table>
          <template #footer>
            <div class="flex justify-end items-center">
              <el-button
                type="primary"
                @click="addSelectedEmployees"
                :disabled="selectedEmployees.length === 0"
                :loading="addingEmployees"
              >
                Add Selected Employees
              </el-button>
            </div>
          </template>
        </el-card>
      </div>

      <!-- Current Employees Section -->
      <div>
        <el-card shadow="never" class="border border-gray-200">
          <template #header>
            <div class="flex justify-between items-center">
              <span class="font-medium">Current Employees</span>
              <div class="flex items-center" style="gap: 8px">
                <span style="font-size: 13px; color: #6b7280">Show</span>
                <el-select v-model="pageSize" size="small" style="width: 90px">
                  <el-option
                    v-for="opt in pageSizeOptions"
                    :key="opt"
                    :label="opt + ' entries'"
                    :value="opt"
                  />
                </el-select>
              </div>
            </div>
          </template>

          <el-table
            :data="pagedEmployees"
            style="width: 100%"
            v-loading="loadingEmployees"
            border
            stripe
          >
            <template #empty>
              <el-empty description="No employees added yet" />
            </template>
            <el-table-column
              prop="employeeNo"
              label="Employee No"
              width="120"
            />
            <el-table-column prop="name" label="Name" min-width="200" />
            <el-table-column prop="position" label="Position" min-width="200" />
            <el-table-column label="Percentage" width="150">
              <template #default="scope">
                <el-select
                  v-model="scope.row.hazardPaySetupId"
                  placeholder="Select"
                  style="width: 120px"
                  :disabled="isPosted"
                >
                  <el-option
                    v-for="setup in hazardPaySetup"
                    :key="setup.id"
                    :label="setup.percentage"
                    :value="setup.id"
                  />
                </el-select>
              </template>
            </el-table-column>
            <el-table-column prop="noOfDays" label="No. of Days" width="140">
              <template #default="scope">
                <el-input-number
                  v-model="scope.row.noOfDays"
                  :precision="0"
                  :min="0"
                  :controls="false"
                  :disabled="isPosted"
                />
              </template>
            </el-table-column>
            <el-table-column label="Actions" width="120">
              <template #default="scope">
                <div class="actions-cell">
                  <el-button
                    size="small"
                    type="danger"
                    @click="removeEmployee(scope.row)"
                    :disabled="isPosted"
                  >
                    Remove
                  </el-button>
                </div>
              </template>
            </el-table-column>
          </el-table>

          <!-- Pagination -->
          <div
            class="flex justify-between items-center"
            style="margin-top: 12px"
          >
            <div style="font-size: 13px; color: #6b7280">
              Showing
              <strong>
                {{
                  totalEmployees === 0 ? 0 : (currentPage - 1) * pageSize + 1
                }}
              </strong>
              to
              <strong>
                {{ Math.min(currentPage * pageSize, totalEmployees) }}
              </strong>
              of <strong>{{ totalEmployees }}</strong> entries
            </div>
            <el-pagination
              :current-page="currentPage"
              :page-size="pageSize"
              :total="totalEmployees"
              layout="prev, pager, next"
              background
              small
              @current-change="handlePageChange"
            />
          </div>

          <!-- Validation message for incomplete employees -->
          <!-- <div
            v-if="currentEmployees.length > 0 && !isFormValid"
            class="validation-message"
          >
            <el-alert type="warning" :closable="false" show-icon>
              <template #title>
                Please complete all employee details:
              </template>
              <ul style="margin: 8px 0 0 0; padding-left: 20px">
                <li
                  v-for="emp in currentEmployees.filter(
                    (e) => !e.hazardPaySetupId || !e.noOfDays || e.noOfDays <= 0
                  )"
                  :key="emp.id"
                >
                  <strong>{{ emp.name }} ({{ emp.employeeNo }})</strong> -
                  {{ !emp.hazardPaySetupId ? "Missing Percentage" : "" }}
                  {{
                    !emp.hazardPaySetupId &&
                    (!emp.noOfDays || emp.noOfDays <= 0)
                      ? " and "
                      : ""
                  }}
                  {{
                    !emp.noOfDays || emp.noOfDays <= 0
                      ? "Missing/Invalid No. of Days"
                      : ""
                  }}
                </li>
              </ul>
            </el-alert>
          </div> -->
        </el-card>
      </div>
    </div>

    <div class="form-footer">
      <el-button @click="handleClose" :disabled="loading"> Cancel </el-button>
      <el-button
        v-if="!isPosted"
        type="primary"
        @click="handleSave"
        :loading="saving || savingEmployees"
        :disabled="!isFormValid"
      >
        {{ isEdit ? "Update" : "Create" }}
      </el-button>

      <!-- Helper text for disabled button -->
      <div
        v-if="!isFormValid && currentEmployees.length > 0"
        class="button-helper"
      >
        <small style="color: #909399">
          Complete all employee details to enable
          {{ isEdit ? "Update" : "Create" }}
        </small>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted } from "vue";
import { ElMessage, ElMessageBox } from "element-plus";
import { useHazardPay } from "../../../Composables/useHazardPay.js";

// Props
const props = defineProps({
  // Accept both Number and String to be resilient to parent usage like hazard-id="10"
  hazardId: {
    type: [Number, String],
    default: 0,
  },
});

// Emits
const emit = defineEmits(["saved", "close"]);

// Composable
const {
  loading,
  hazardPayList,
  hazardPaySetup,
  formData: composableForm,
  divisions,
  months,
  employees,
  loadHazardPayForm,
  loadHazardPaySetup,
  saveHazardPay,
  saveHazardPaySilent,
  saveHazardPayEmployees,
  saveHazardPayEmployeesSilent,
  removeHazardPayEmployee,
  transformEmployeeData,
  resetFormData,
} = useHazardPay();

// Local state
const formRef = ref();
const saving = ref(false);
const addingEmployees = ref(false);
const savingEmployees = ref(false);
const loadingEmployees = ref(false);
const addEmployeesCardRef = ref(null);
const showAddEmployees = ref(true);

// Form rules (prevents runtime warning and enables basic validation)
const formRules = reactive({
  division_id: [
    { required: true, message: "Division is required", trigger: "change" },
  ],
  month_id: [
    { required: true, message: "Month is required", trigger: "change" },
  ],
  year: [{ required: true, message: "Year is required", trigger: "change" }],
});

// Header form
const form = reactive({
  id: 0,
  division_id: null,
  month_id: null,
  year: new Date().getFullYear(),
  posted: false,
});

// Inline employee management state
const availableEmployees = ref([]);
const currentEmployees = ref([]);
const selectedEmployees = ref([]);

const availableEmployeesFiltered = computed(() => {
  const takenIds = new Set(
    (currentEmployees.value || []).map(
      (e) => e.id || e.employee_id || e.employeeNo,
    ),
  );
  const list = (availableEmployees.value || []).filter((emp) => {
    const id = emp.id || emp.employee_id || emp.employee_no;
    return id && !takenIds.has(id);
  });
  // Sort by name (case-insensitive)
  return [...list].sort((a, b) => {
    const na = (a.name || "").trim().toLowerCase();
    const nb = (b.name || "").trim().toLowerCase();
    return na.localeCompare(nb);
  });
});

const currentEmployeeRows = computed(() => {
  return (currentEmployees.value || []).filter(
    (e) => e && (e.id || e.employeeNo || e.name),
  );
});

// Pagination state and derived rows
const currentPage = ref(1);
const pageSize = ref(10);
const pageSizeOptions = [10, 25, 50, 100];

const totalEmployees = computed(() => currentEmployeeRows.value.length);
const pagedEmployees = computed(() => {
  const start = (currentPage.value - 1) * pageSize.value;
  const end = start + pageSize.value;
  return currentEmployeeRows.value.slice(start, end);
});

const handlePageChange = (page) => {
  currentPage.value = page;
};

const hazardIdComputed = computed(() => Number(props.hazardId || form.id || 0));
const isEdit = computed(() => hazardIdComputed.value > 0);
const isPosted = computed(() => !!form.posted);

const isFormValid = computed(() => {
  // Check basic form fields
  const basicFieldsValid = form.division_id && form.month_id && form.year;

  // If there are employees, check that all have required fields set
  if (currentEmployees.value.length > 0) {
    const allEmployeesValid = currentEmployees.value.every(
      (e) => e.hazardPaySetupId && e.noOfDays && e.noOfDays > 0,
    );
    return basicFieldsValid && allEmployeesValid;
  }

  return basicFieldsValid;
});

const yearOptions = computed(() => {
  const currentYear = new Date().getFullYear();
  const years = [];
  for (let i = currentYear - 5; i <= currentYear + 5; i++) years.push(i);
  return years;
});

// Loaders
const loadFormData = async () => {
  try {
    loadingEmployees.value = true;

    // Load hazard pay setup options for percentage dropdown
    await loadHazardPaySetup();

    const data = await loadHazardPayForm(props.hazardId || 0, {
      division_id: form.division_id,
    });

    // Populate dropdowns (divisions, months from composable)

    // Initialize header
    if (isEdit.value) {
      const header = composableForm.value || {};
      const normalizePosted = (v) =>
        v === true || v === 1 || v === "1" || v === "true";
      form.id = header.id || props.hazardId;
      form.division_id = header.division_id || header.department_id || null;
      form.month_id = header.month_id || null;
      form.year = header.year || new Date().getFullYear();
      form.posted = normalizePosted(header.posted);
    } else {
      form.id = 0;
      form.division_id = null;
      form.month_id = null;
      form.year = new Date().getFullYear();
      form.posted = false;
    }

    // Employees
    const raw = employees.value || [];
    const details = transformEmployeeData(raw);

    if (isEdit.value) {
      // Only show rows already added to this header (have hazardDtl)
      currentEmployees.value = details.filter((e) => !!e.hazardDtl);
      // Available are those provided by backend as selectable; prefer normalized key from composable
      let availPayload =
        (data && (data.hazard_employees || data.available_employees)) ||
        raw.filter((r) => !r.hazard_dtl);

      if ((!availPayload || availPayload.length === 0) && form.division_id) {
        const refreshed = await loadHazardPayForm(props.hazardId || 0, {
          division_id: form.division_id,
        });
        availPayload =
          refreshed?.hazard_employees || refreshed?.available_employees || [];
      }
      availableEmployees.value = (availPayload || []).map((r) => ({
        // Normalize fields for the Add Employees table
        id: r.employee_id ?? r.id,
        employee_id: r.employee_id ?? r.id,
        employee_no: r.employee_no ?? r.employeeNo ?? "",
        name: r.name ?? r.employee_name ?? "",
        position: r.position ?? r.job_title ?? "",
        hazard_pay_setup_id: r.hazard_pay_setup_id ?? r.hazardPaySetupId,
        noOfDays: r.no_of_days ?? r.noOfDays ?? 0,
        salaryGradeId: r.salary_grade_id ?? r.salaryGradeId ?? 1,
        salaryStepId: r.salary_step_id ?? r.salaryStepId ?? 1,
      }));
    } else {
      currentEmployees.value = [];
      // For new header, backend gives pool to choose from; prefer normalized key
      let availPayload =
        (data && (data.hazard_employees || data.available_employees)) || raw;
      if ((!availPayload || availPayload.length === 0) && form.division_id) {
        const refreshed = await loadHazardPayForm(0, {
          division_id: form.division_id,
        });
        availPayload =
          refreshed?.hazard_employees || refreshed?.available_employees || [];
      }
      availableEmployees.value = (availPayload || []).map((r) => ({
        id: r.employee_id ?? r.id,
        employee_id: r.employee_id ?? r.id,
        employee_no: r.employee_no ?? r.employeeNo ?? "",
        name: r.name ?? r.employee_name ?? "",
        position: r.position ?? r.job_title ?? "",
        hazard_pay_setup_id: r.hazard_pay_setup_id ?? r.hazardPaySetupId,
        noOfDays: r.no_of_days ?? r.noOfDays ?? 0,
        salaryGradeId: r.salary_grade_id ?? r.salaryGradeId ?? 1,
        salaryStepId: r.salary_step_id ?? r.salaryStepId ?? 1,
      }));
    }
  } catch (err) {
    console.error("Failed to load hazard pay form data:", err);
    ElMessage.error("Failed to load form data");
  } finally {
    loadingEmployees.value = false;
  }
};

// Actions
const handleSave = async () => {
  try {
    if (!formRef.value) return;
    await formRef.value.validate();
    saving.value = true;

    // Save the header first (suppress backend message)
    const result = await saveHazardPaySilent(props.hazardId || 0, {
      ...form,
      posted: 0,
    });

    const newId = result.id ?? result.data?.id ?? result?.data?.data?.id;
    form.id = newId || form.id;

    // If there are employees, save them (validation already done via button state)
    if (currentEmployees.value.length > 0) {
      savingEmployees.value = true;
      const payload = {
        employees: currentEmployees.value.map((e) => ({
          id: e.id,
          hazardPaySetupId: e.hazardPaySetupId,
          noOfDays: e.noOfDays,
          salaryGradeId: e.salaryGradeId || 1,
          salaryStepId: e.salaryStepId || 1,
        })),
      };
      await saveHazardPayEmployeesSilent(newId || form.id, payload);
    }

    // Determine if this is a create or update operation
    const wasInitiallyNew = props.hazardId === 0 && !form.id;
    const action = wasInitiallyNew ? "created" : "updated";
    const employeeText =
      currentEmployees.value.length > 0
        ? ` and ${currentEmployees.value.length} employee(s)`
        : "";
    ElMessage.success(`Hazard pay ${action} successfully${employeeText}`);

    // After save, ensure newly created headers are treated as unposted unless backend says otherwise
    if (!isEdit.value) {
      form.posted = false;
    }
    emit("saved", newId || 0);
  } catch (err) {
    console.error("Failed to save hazard pay:", err);
    ElMessage.error(err.response?.data?.message || "Failed to save hazard pay");
  } finally {
    saving.value = false;
    savingEmployees.value = false;
  }
};

const handleEmployeeSelection = (selection) => {
  selectedEmployees.value = selection;
};

const addSelectedEmployees = async () => {
  if (selectedEmployees.value.length === 0) {
    ElMessage.warning("Please select employees to add");
    return;
  }
  try {
    addingEmployees.value = true;

    // Move selected employees to current list (like RATA pattern)
    const existingIds = new Set(
      currentEmployees.value.map((e) => e.id || e.employee_id),
    );
    const toAdd = selectedEmployees.value
      .filter((e) => !existingIds.has(e.id || e.employee_id))
      .map((r) => ({
        id: r.id || r.employee_id,
        employee_id: r.id || r.employee_id,
        employeeNo: r.employee_no || r.employeeNo || "",
        name: r.name || r.employee_name || "",
        position: r.position || r.job_title || "",
        hazardPaySetupId: r.hazardPaySetupId || r.hazard_pay_setup_id || null,
        noOfDays: r.noOfDays || 0,
        salaryGradeId: r.salaryGradeId || 1,
        salaryStepId: r.salaryStepId || 1,
      }));

    currentEmployees.value = [...currentEmployees.value, ...toAdd];
    ElMessage.success(
      `${toAdd.length} employee(s) added to the list. Set Percentage and No. of Days for each employee, then click "${isEdit.value ? "Update" : "Create"}" to save.`,
    );
    selectedEmployees.value = [];
  } catch (err) {
    console.error("Failed to add employees:", err);
    ElMessage.error(err.response?.data?.message || "Failed to add employees");
  } finally {
    addingEmployees.value = false;
  }
};

const removeEmployee = async (employee) => {
  try {
    await ElMessageBox.confirm(
      `Are you sure you want to remove ${employee.name} from this Hazard Pay?`,
      "Confirm Removal",
      {
        confirmButtonText: "Remove",
        cancelButtonText: "Cancel",
        type: "warning",
      },
    );
    await removeHazardPayEmployee(employee.hazardDtl);
    ElMessage.success("Employee removed successfully");
    await loadFormData();
  } catch (err) {
    if (err !== "cancel") {
      console.error("Failed to remove employee:", err);
      ElMessage.error(
        err.response?.data?.message || "Failed to remove employee",
      );
    }
  }
};

const handleClose = () => {
  resetFormData();
  availableEmployees.value = [];
  currentEmployees.value = [];
  selectedEmployees.value = [];
  showAddEmployees.value = true;
  emit("close");
};

const normalizeAvailableEmployee = (r) => ({
  id: r.employee_id ?? r.id,
  employee_id: r.employee_id ?? r.id,
  employee_no: r.employee_no ?? r.employeeNo ?? "",
  name: r.name ?? r.employee_name ?? "",
  position: r.position ?? r.job_title ?? "",
  hazard_pay_setup_id: r.hazard_pay_setup_id ?? r.hazardPaySetupId,
  noOfDays: r.no_of_days ?? r.noOfDays ?? 0,
  salaryGradeId: r.salary_grade_id ?? r.salaryGradeId ?? 1,
  salaryStepId: r.salary_step_id ?? r.salaryStepId ?? 1,
});

const fetchAvailableEmployeesByDivision = async () => {
  if (!form.division_id) {
    availableEmployees.value = [];
    return;
  }
  try {
    const refreshed = await loadHazardPayForm(hazardIdComputed.value || 0, {
      division_id: form.division_id,
    });
    const payload =
      refreshed?.hazard_employees || refreshed?.available_employees || [];
    availableEmployees.value = (payload || []).map(normalizeAvailableEmployee);
  } catch (e) {
    console.error("Failed to load employees by division:", e);
    availableEmployees.value = [];
  }
};

const scrollToAddEmployees = () => {
  const el = addEmployeesCardRef.value?.$el || addEmployeesCardRef.value;
  if (el && typeof el.scrollIntoView === "function") {
    el.scrollIntoView({ behavior: "smooth", block: "start" });
  }
};

// Watchers / lifecycle
watch(
  () => props.hazardId,
  () => {
    loadFormData();
  },
);

watch(
  () => form.division_id,
  (newDivisionId) => {
    if (hazardIdComputed.value > 0 && !isPosted.value) {
      fetchAvailableEmployeesByDivision();
    } else if (newDivisionId != null && newDivisionId !== "") {
      fetchAvailableEmployeesByDivision();
    } else {
      availableEmployees.value = [];
    }
  },
);

onMounted(() => {
  loadFormData();
});

// Reset page when data or page size changes
watch(
  () => [currentEmployeeRows.value.length, pageSize.value],
  () => {
    currentPage.value = 1;
  },
);

watch(
  () => [availableEmployeesFiltered.value.length, isPosted.value],
  ([len, posted]) => {
    if (len === 0) {
      selectedEmployees.value = [];
    }
    showAddEmployees.value = len > 0 && !posted;
  },
  { immediate: true },
);
</script>

<style scoped>
.hazard-pay-form-container {
  background: #ffffff;
  border-radius: 12px;
  padding: 24px;
  margin-bottom: 20px;
  box-shadow:
    0 1px 3px 0 rgba(0, 0, 0, 0.1),
    0 1px 2px 0 rgba(0, 0, 0, 0.06);
  border: 1px solid #e5e7eb;
}

.form-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
  padding-bottom: 16px;
  border-bottom: 1px solid #e5e7eb;
}

.form-header h3 {
  margin: 0;
  font-size: 20px;
  font-weight: 600;
  color: #374151;
}

.form-row {
  display: flex;
  gap: 20px;
  margin-bottom: 24px;
}

.form-row .el-form-item {
  flex: 1;
}

.hazard-pay-form {
  padding: 0;
}

.hazard-pay-form :deep(.el-form-item) {
  margin-bottom: 24px;
}

.hazard-pay-form :deep(.el-form-item__label) {
  font-weight: 600;
  color: #374151;
  font-size: 14px;
}

.hazard-pay-form :deep(.el-input__inner),
.hazard-pay-form :deep(.el-select .el-input__inner) {
  border-radius: 8px;
  transition: all 0.2s ease;
  font-size: 14px;
  padding: 12px 16px;
}

.hazard-pay-form :deep(.el-input__inner:focus),
.hazard-pay-form :deep(.el-select .el-input__inner:focus) {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.status-note {
  margin-top: 8px;
  padding: 8px 12px;
  background: #f0f9ff;
  border: 1px solid #bae6fd;
  border-radius: 6px;
  color: #0369a1;
  font-size: 13px;
  display: flex;
  align-items: center;
  gap: 6px;
}

.status-note i {
  color: #0284c7;
}

.form-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  padding: 20px 0 0 0;
  border-top: 1px solid #e5e7eb;
  margin-top: 24px;
}

.form-footer .el-button {
  border-radius: 8px;
  font-weight: 600;
  padding: 10px 20px;
  transition: all 0.2s ease;
}

.form-footer .el-button:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

.form-footer .el-button--primary {
  background: #3b82f6;
  border-color: #3b82f6;
}

.form-footer .el-button--primary:hover {
  background: #2563eb;
  border-color: #2563eb;
  box-shadow: 0 4px 8px rgba(59, 130, 246, 0.3);
}

.form-footer .el-button--success {
  background: #10b981;
  border-color: #10b981;
}

.form-footer .el-button--success:hover {
  background: #059669;
  border-color: #059669;
  box-shadow: 0 4px 8px rgba(16, 185, 129, 0.3);
}

/* Animation */
.hazard-pay-form-container {
  animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(-20px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

/* Responsive Design */
@media (max-width: 768px) {
  .hazard-pay-form-container {
    padding: 16px;
  }

  .form-row {
    flex-direction: column;
    gap: 16px;
  }

  .form-header {
    flex-direction: column;
    align-items: stretch;
    gap: 16px;
  }

  .form-header h3 {
    text-align: center;
  }

  .form-footer {
    flex-direction: column;
    gap: 8px;
  }

  .form-footer .el-button {
    width: 100%;
  }
}

.employee-management {
  margin-top: 20px;
}

.validation-message {
  margin-top: 16px;
}

.button-helper {
  margin-top: 8px;
  text-align: center;
}

.text-lg {
  font-size: 1.125rem;
}

.font-semibold {
  font-weight: 600;
}

.border-gray-200 {
  border-color: #e5e7eb;
}

.flex {
  display: flex;
}

.justify-between {
  justify-content: space-between;
}

.items-center {
  align-items: center;
}

.font-medium {
  font-weight: 500;
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
