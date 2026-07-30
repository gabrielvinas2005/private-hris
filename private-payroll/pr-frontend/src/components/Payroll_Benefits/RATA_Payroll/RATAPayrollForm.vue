<template>
  <div class="rata-payroll-form">
    <div class="form-container">
      <div class="form-header">
        <h3>{{ isEdit ? "Edit RATA Payroll" : "Create RATA Payroll" }}</h3>
        <el-button @click="handleClose" plain>
          <i class="el-icon-arrow-left"></i>
          Back to List
        </el-button>
      </div>
      <!-- Header Form -->
      <el-form
        :model="form"
        :rules="rules"
        ref="formRef"
        label-width="150px"
        class="mb-4"
      >
        <el-row :gutter="20">
          <el-col :span="8">
            <el-form-item label="RATA Type" prop="rata_type_id">
              <el-select
                v-model="form.rata_type_id"
                placeholder="Select RATA Type"
                style="width: 100%"
              >
                <el-option
                  v-for="type in rataTypes"
                  :key="type.id"
                  :label="type.name"
                  :value="Number(type.id)"
                />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="8">
            <el-form-item label="Month" prop="month_id">
              <el-select
                v-model="form.month_id"
                placeholder="Select Month"
                style="width: 100%"
              >
                <el-option
                  v-for="(month, index) in months"
                  :key="index + 1"
                  :label="month"
                  :value="index + 1"
                />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="8">
            <el-form-item label="Year" prop="year_id">
              <el-select
                v-model="form.year_id"
                placeholder="Select Year"
                style="width: 100%"
              >
                <el-option
                  v-for="year in years"
                  :key="year"
                  :label="year"
                  :value="year"
                />
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>
        <!-- <el-row :gutter="20">
          <el-col :span="8">
            <el-form-item label="Year" prop="year_id">
              <el-select
                v-model="form.year_id"
                placeholder="Select Year"
                style="width: 100%"
              >
                <el-option
                  v-for="year in years"
                  :key="year"
                  :label="year"
                  :value="year"
                />
              </el-select>
            </el-form-item>
          </el-col>
        </el-row> -->
      </el-form>

      <!-- Employee Management Section -->
      <div v-if="rataId > 0" class="employee-management">
        <el-divider content-position="left">
          <span class="text-lg font-semibold">Employee Management</span>
        </el-divider>

        <!-- Add Employees Section -->
        <div class="mb-4" v-if="!isPosted">
          <el-card shadow="never" class="border border-gray-200">
            <div class="add-employee-toolbar mb-3">
              <el-input
                v-model="addEmployeeSearchQuery"
                placeholder="Search by name, employee no., plantilla code, or position"
                clearable
                class="add-employee-search"
              />
            </div>
            <el-table
              ref="addEmployeesTableRef"
              :data="filteredAvailableEmployees"
              style="width: 100%"
              row-key="id"
              @selection-change="handleEmployeeSelection"
              max-height="300"
            >
              <el-table-column type="selection" width="55" />
              <el-table-column
                prop="employee_no"
                label="Employee No"
                width="130"
                sortable
              />
              <el-table-column
                prop="name"
                label="Name"
                min-width="200"
                sortable
              />
              <el-table-column
                prop="position"
                label="Position"
                min-width="200"
                sortable
              />
              <el-table-column
                prop="code"
                label="Plantilla Code"
                width="200"
                sortable
              />
            </el-table>
            <template #footer>
              <div class="flex justify-between items-center">
                <span class="font-medium">Add Employees</span>
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
              <span class="font-medium">Current Employees</span>
            </template>

            <el-table
              :data="paginatedCurrentEmployees"
              style="width: 100%"
              v-loading="loadingEmployees"
              border
              stripe
            >
              <el-table-column
                prop="employee_no"
                label="Employee No"
                width="120"
              />
              <el-table-column prop="name" label="Name" min-width="200" />
              <el-table-column
                prop="position"
                label="Position"
                min-width="200"
              />
              <el-table-column label="Representation Amount" min-width="120">
                <template #default="scope">
                  ₱{{ formatNumber(scope.row.ra_amount) }}
                </template>
              </el-table-column>
              <el-table-column label="Transportation Amount" min-width="120">
                <template #default="scope">
                  ₱{{ formatNumber(scope.row.ta_amount) }}
                </template>
              </el-table-column>
              <el-table-column label="Days Absent" min-width="100">
                <template #default="scope">
                  {{ scope.row.no_of_days_absent }}
                </template>
              </el-table-column>
              <el-table-column label="Percentage" width="100">
                <template #default="scope">
                  {{ scope.row.rata_percentage }}%
                </template>
              </el-table-column>
              <el-table-column label="Vehicle Deduction" min-width="130">
                <template #default="scope">
                  <el-input-number
                    v-model="scope.row.use_vehicle_rp_ta"
                    :precision="2"
                    style="width: 100px"
                    :controls="false"
                    :disabled="isPosted"
                    @change="updateEmployeeAmounts(scope.row)"
                  />
                </template>
              </el-table-column>
              <el-table-column label="Net Amount" width="120">
                <template #default="scope">
                  ₱{{ formatNumber(scope.row.net_amount) }}
                </template>
              </el-table-column>
              <el-table-column label="Actions" width="100">
                <template #default="scope">
                  <div class="actions-cell">
                    <el-button
                      size="small"
                      type="danger"
                      :disabled="isPosted"
                      @click="removeEmployee(scope.row)"
                    >
                      Remove
                    </el-button>
                  </div>
                </template>
              </el-table-column>
            </el-table>
            <div class="mt-4 flex justify-end">
              <el-pagination
                background
                layout="total, sizes, prev, pager, next"
                :current-page="currentPage"
                :page-sizes="pageSizeOptions"
                :page-size="pageSize"
                :total="currentEmployees.length"
                @current-change="handleCurrentPageChange"
                @size-change="handlePageSizeChange"
              />
            </div>
          </el-card>
        </div>
      </div>

      <!-- Form Actions -->
      <div class="form-actions">
        <el-button @click="handleClose" :disabled="isPosted">Cancel</el-button>
        <el-button
          type="primary"
          @click="handleSave"
          :loading="saving"
          :disabled="!isFormValid || isPosted"
        >
          {{ isEdit ? "Update" : "Create" }} RATA Payroll
        </el-button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted, nextTick } from "vue";
import { ElMessage, ElMessageBox } from "element-plus";
import { useRATAPayroll } from "../../../Composables/useRATAPayroll.js";

// Props
const props = defineProps({
  rataId: {
    type: Number,
    default: 0,
  },
});

// Emits
const emit = defineEmits(["saved", "close"]);

// Composables
const {
  loading,
  error,
  rataFormData,
  loadRATAPayrollFormData,
  saveRATAPayroll,
  addEmployeesToRATAPayroll,
  updateRATAPayrollEmployees,
  removeEmployeeFromRATAPayroll,
  clearError,
} = useRATAPayroll();

// State
const formRef = ref();
const saving = ref(false);
const addingEmployees = ref(false);
const loadingEmployees = ref(false);

// Form data
const form = reactive({
  rata_type_id: "",
  month_id: "",
  year_id: "",
});

// Available data
const months = ref([]);
const years = ref([]);
const availableEmployees = ref([]);
const addEmployeeSearchQuery = ref("");
const addEmployeesTableRef = ref(null);
const currentEmployees = ref([]);
const currentPage = ref(1);
const pageSize = ref(10);
const pageSizeOptions = [10, 20, 30, 50];
const selectedEmployees = ref([]);

const rataTypes = ref([]);

const isEdit = computed(() => props.rataId > 0);
const isPosted = computed(() => {
  // derive from first current employee row or stored form state if present
  if (currentEmployees.value && currentEmployees.value.length > 0) {
    const any = currentEmployees.value[0];
    return (
      any.posted === true ||
      any.posted === 1 ||
      any.posted === "1" ||
      any.posted === "true"
    );
  }
  return false;
});

const isFormValid = computed(() => {
  return form.rata_type_id && form.month_id && form.year_id;
});

const paginatedCurrentEmployees = computed(() => {
  const start = (currentPage.value - 1) * pageSize.value;
  const end = start + pageSize.value;
  return currentEmployees.value.slice(start, end);
});

const filteredAvailableEmployees = computed(() => {
  const q = addEmployeeSearchQuery.value.trim().toLowerCase();
  const rows = availableEmployees.value || [];
  if (!q) return rows;
  return rows.filter((row) => {
    const name = String(row.name ?? "").toLowerCase();
    const no = String(row.employee_no ?? "").toLowerCase();
    const code = String(row.code ?? "").toLowerCase();
    const position = String(row.position ?? "").toLowerCase();
    return (
      name.includes(q) ||
      no.includes(q) ||
      code.includes(q) ||
      position.includes(q)
    );
  });
});

// Months
const monthsList = [
  "January",
  "February",
  "March",
  "April",
  "May",
  "June",
  "July",
  "August",
  "September",
  "October",
  "November",
  "December",
];

// Form validation rules
const rules = {
  rata_type_id: [
    { required: true, message: "Please select RATA type", trigger: "change" },
  ],
  month_id: [
    { required: true, message: "Please select month", trigger: "change" },
  ],
  year_id: [
    { required: true, message: "Please select year", trigger: "change" },
  ],
};

// Methods
const loadFormData = async (rataIdOverride = null) => {
  try {
    loadingEmployees.value = true;
    addEmployeeSearchQuery.value = "";
    selectedEmployees.value = [];
    const idToLoad = rataIdOverride !== null ? rataIdOverride : props.rataId;
    const data = await loadRATAPayrollFormData(idToLoad);

    if (data) {
      const payload = data;
      months.value = monthsList;
      years.value = generateYears();
      rataTypes.value = payload.rata_types || [];

      if (idToLoad > 0) {
        // Load existing data for editing (or newly created payroll with ID)
        const existingData =
          payload.data && payload.data.length > 0 ? payload.data[0] : null;
        if (existingData) {
          // Normalize numeric ids so selects match their option values and display labels
          form.rata_type_id =
            existingData.rata_type_id != null
              ? Number(existingData.rata_type_id)
              : "";
          form.month_id =
            existingData.month_id != null ? Number(existingData.month_id) : "";
          form.year_id =
            existingData.year_id != null ? Number(existingData.year_id) : "";
        }

        // Load current employees with proper type conversion.
        // Filter out blank rows: backend leftJoin returns one row with null employee when there are no details.
        const validRows = (payload.data || []).filter(
          (employee) =>
            (employee.dtl_id != null && employee.dtl_id !== 0) ||
            (employee.employee_no != null &&
              String(employee.employee_no).trim() !== "") ||
            (employee.name != null && String(employee.name).trim() !== ""),
        );
        currentEmployees.value = validRows.map((employee) => ({
          ...employee,
          use_vehicle_rp_ta: Number(employee.use_vehicle_rp_ta) || 0,
          ra_amount: Number(employee.ra_amount) || 0,
          ta_amount: Number(employee.ta_amount) || 0,
          net_amount: Number(employee.net_amount) || 0,
          no_of_days_absent: Number(employee.no_of_days_absent) || 0,
          rata_percentage: Number(employee.rata_percentage) || 100,
        }));
        currentPage.value = 1;
        // Also load available employees to allow adding more
        availableEmployees.value = payload.rata_employees || [];
      } else {
        // New payroll - load available employees
        availableEmployees.value = payload.rata_employees || [];
      }
    }
  } catch (err) {
    console.error("Failed to load form data:", err);
    ElMessage.error("Failed to load form data");
  } finally {
    loadingEmployees.value = false;
  }
};

const generateYears = () => {
  const currentYear = new Date().getFullYear();
  const years = [];
  for (let i = currentYear - 5; i <= currentYear + 5; i++) {
    years.push(i);
  }
  return years;
};

const handleSave = async () => {
  try {
    if (!formRef.value) return;

    await formRef.value.validate();

    saving.value = true;

    const payload = {
      rata_type_id: form.rata_type_id,
      month_id: form.month_id,
      year_id: form.year_id,
    };
    const result = await saveRATAPayroll(props.rataId, payload);

    // Extract new header id and inform parent to keep form open and load employees
    const newId = result.id ?? result.data?.id ?? result?.data?.data?.id;
    const headerId = newId || props.rataId || 0;

    // If we have a header ID and employees, also save employee updates
    if (headerId > 0 && currentEmployees.value.length > 0) {
      try {
        const employeeData = {
          rata_detail_id: currentEmployees.value.map((emp) => emp.dtl_id),
          plantilla_id: currentEmployees.value.map((emp) => emp.plantilla_id),
          use_vehicle_rp_ta: currentEmployees.value.map(
            (emp) => emp.use_vehicle_rp_ta || 0,
          ),
          no_of_days_absent: currentEmployees.value.map(
            (emp) => emp.no_of_days_absent || 0,
          ),
          rata_percentage: currentEmployees.value.map(
            (emp) => emp.rata_percentage || 100,
          ),
        };

        await updateRATAPayrollEmployees(headerId, employeeData);

        // Reload form data to get recalculated values from backend
        await loadFormData(headerId);
      } catch (err) {
        console.error("Failed to save employee updates:", err);
        ElMessage.warning(
          "Header saved but employee updates failed: " +
            (err.response?.data?.message || "Failed to save employee updates"),
        );
      }
    }

    ElMessage.success(result.message || "RATA payroll saved successfully");

    emit("saved", headerId);
  } catch (err) {
    console.error("Failed to save RATA payroll:", err);
    ElMessage.error(
      err.response?.data?.message || "Failed to save RATA payroll",
    );
  } finally {
    saving.value = false;
  }
};

const handleEmployeeSelection = (selection) => {
  selectedEmployees.value = selection;
};

const handleCurrentPageChange = (page) => {
  currentPage.value = page;
};

const handlePageSizeChange = (size) => {
  pageSize.value = size;
  currentPage.value = 1;
};

const addSelectedEmployees = async () => {
  if (selectedEmployees.value.length === 0) {
    ElMessage.warning("Please select employees to add");
    return;
  }

  try {
    addingEmployees.value = true;

    const employeeData = {
      id: selectedEmployees.value.map((emp) => emp.id),
      plantilla_id: selectedEmployees.value.map((emp) => emp.plantilla_id),
      select: selectedEmployees.value.map((emp) => emp.id),
    };

    // Ensure we have a header id; if editing, it's props.rataId; if just created, use form.rata_id fallback from backend if present
    const headerId = props.rataId || form.id || form.rata_id || 0;
    const result = await addEmployeesToRATAPayroll(headerId, employeeData);

    ElMessage.success(result.message || "Employees added successfully");

    // Reload form data to show updated employee list
    await loadFormData();

    // Clear selection
    selectedEmployees.value = [];
  } catch (err) {
    console.error("Failed to add employees:", err);
    ElMessage.error(err.response?.data?.message || "Failed to add employees");
  } finally {
    addingEmployees.value = false;
  }
};

const updateEmployeeAmounts = (employee) => {
  // Recalculate net amount
  const totalEarned = employee.ra_amount + employee.ta_amount;
  employee.net_amount = totalEarned - (employee.use_vehicle_rp_ta || 0);
  employee.total_deduction = employee.use_vehicle_rp_ta || 0;
};

const removeEmployee = async (employee) => {
  try {
    await ElMessageBox.confirm(
      `Are you sure you want to remove ${employee.name} from this RATA payroll?`,
      "Confirm Removal",
      {
        confirmButtonText: "Remove",
        cancelButtonText: "Cancel",
        type: "warning",
      },
    );

    await removeEmployeeFromRATAPayroll(employee.dtl_id);

    ElMessage.success("Employee removed successfully");

    // Reload form data
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

const saveEmployeeUpdates = async () => {
  if (currentEmployees.value.length === 0) return;

  try {
    const employeeData = {
      rata_detail_id: currentEmployees.value.map((emp) => emp.dtl_id),
      plantilla_id: currentEmployees.value.map((emp) => emp.plantilla_id),
      use_vehicle_rp_ta: currentEmployees.value.map(
        (emp) => emp.use_vehicle_rp_ta || 0,
      ),
      no_of_days_absent: currentEmployees.value.map(
        (emp) => emp.no_of_days_absent || 0,
      ),
      rata_percentage: currentEmployees.value.map(
        (emp) => emp.rata_percentage || 100,
      ),
    };

    await updateRATAPayrollEmployees(props.rataId, employeeData);

    ElMessage.success("Employee updates saved successfully");
    // Notify parent to close and refresh list after update
    emit("saved", 0);
  } catch (err) {
    console.error("Failed to save employee updates:", err);
    ElMessage.error(
      err.response?.data?.message || "Failed to save employee updates",
    );
  }
};

const handleClose = () => {
  // Save employee updates before closing if there are changes
  if (isEdit.value && currentEmployees.value.length > 0) {
    saveEmployeeUpdates();
  }

  // Reset form
  Object.keys(form).forEach((key) => {
    form[key] = "";
  });

  // Clear data
  addEmployeeSearchQuery.value = "";
  availableEmployees.value = [];
  currentEmployees.value = [];
  selectedEmployees.value = [];
  currentPage.value = 1;
  pageSize.value = 10;

  clearError();
  emit("close");
};

const formatNumber = (number) => {
  return Number(number || 0).toLocaleString("en-US", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
};

// Watchers
watch(
  () => props.rataId,
  () => {
    loadFormData();
  },
);

watch(addEmployeeSearchQuery, async () => {
  selectedEmployees.value = [];
  await nextTick();
  addEmployeesTableRef.value?.clearSelection?.();
});

// Lifecycle
onMounted(() => {
  loadFormData();
});
</script>

<style scoped>
.rata-payroll-form {
  padding: 20px;
}

.mb-3 {
  margin-bottom: 12px;
}

.mb-4 {
  margin-bottom: 16px;
}

.add-employee-search {
  max-width: 420px;
}

.mt-4 {
  margin-top: 16px;
}

.employee-management {
  margin-top: 20px;
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

.form-container {
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
  padding: 24px;
}

.form-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
  padding-bottom: 16px;
  border-bottom: 1px solid #e4e7ed;
}

.form-header h3 {
  margin: 0;
  color: #303133;
  font-size: 18px;
  font-weight: 600;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 24px;
  padding-top: 16px;
  border-top: 1px solid #e4e7ed;
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
