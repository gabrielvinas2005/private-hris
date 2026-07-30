<template>
  <div class="reimbursement-communication-form">
    <div class="form-container">
      <div class="form-header">
        <h3>
          {{
            props.isDetailView
              ? "Reimbursement Communication Expenses Details"
              : isEdit
                ? "Edit Reimbursement Communication Expenses"
                : "Create Reimbursement Communication Expenses"
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
        class="reimbursement-form"
        :disabled="props.isDetailView"
      >
        <div class="form-row">
          <el-form-item label="Division" prop="division_id">
            <el-select
              v-model="formData.division_id"
              placeholder="Select Division"
              style="width: 280px"
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
            <template v-if="props.isDetailView">
              <el-input
                :model-value="selectedMonthName"
                readonly
                style="width: 280px"
              />
            </template>
            <template v-else>
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
            </template>
          </el-form-item>

          <el-form-item label="Year" prop="year">
            <el-select
              v-model="formData.year"
              placeholder="Select Year"
              style="width: 280px"
              filterable
              clearable
            >
              <el-option
                v-for="yr in yearOptions"
                :key="yr"
                :label="yr"
                :value="yr"
              />
            </el-select>
          </el-form-item>
        </div>

        <!-- Employee Selection Section -->
        <div v-if="!props.isDetailView" class="employee-section">
          <div class="section-header">
            <h4>Employee Selection</h4>
            <el-button
              type="primary"
              @click="showEmployeeDialog = true"
              :disabled="!isFormValid"
            >
              <i class="el-icon-plus"></i>
              Add Employees
            </el-button>
          </div>

          <!-- Selected Employees Table -->
          <div v-if="selectedEmployees.length > 0" class="table-container">
            <div class="table-header">
              <div class="show-entries">
                <span>Show entries:</span>
                <el-select
                  v-model="pageSize"
                  style="width: 100px; margin-left: 8px"
                  @change="currentPage = 1"
                >
                  <el-option label="10" :value="10" />
                  <el-option label="25" :value="25" />
                  <el-option label="50" :value="50" />
                  <el-option label="100" :value="100" />
                </el-select>
              </div>
            </div>
            <el-table
              :data="paginatedEmployees"
              style="width: 100%"
              class="employee-table"
            >
              <el-table-column
                prop="full_name"
                label="Employee Name"
                min-width="150"
              />
              <el-table-column
                prop="position"
                label="Position"
                min-width="200"
              />
              <el-table-column label="Prepaid Invoice No" width="150">
                <template #default="scope">
                  <el-input-number
                    v-model="scope.row.prepaid_invoice_no"
                    :min="0"
                    :precision="0"
                    :controls="false"
                    style="width: 100%"
                  />
                </template>
              </el-table-column>
              <el-table-column label="Prepaid Amount" width="150">
                <template #default="scope">
                  <el-input-number
                    v-model="scope.row.prepaid_amount"
                    :min="0"
                    :precision="2"
                    :controls="false"
                    style="width: 100%"
                  />
                </template>
              </el-table-column>
              <el-table-column label="Postpaid Invoice No" width="150">
                <template #default="scope">
                  <el-input-number
                    v-model="scope.row.postpaid_invoice_no"
                    :min="0"
                    :precision="0"
                    :controls="false"
                    style="width: 100%"
                  />
                </template>
              </el-table-column>
              <el-table-column label="Postpaid Amount" width="150">
                <template #default="scope">
                  <el-input-number
                    v-model="scope.row.postpaid_amount"
                    :min="0"
                    :precision="2"
                    :controls="false"
                    style="width: 100%"
                  />
                </template>
              </el-table-column>
              <el-table-column label="Total Amount" width="120">
                <template #default="scope">
                  ₱{{ formatCurrency(lineTotal(scope.row)) }}
                </template>
              </el-table-column>
              <el-table-column label="Actions" width="120">
                <template #default="scope">
                  <div class="actions-cell">
                    <el-button
                      type="danger"
                      @click="removeEmployeeFromList(scope.row.id)"
                      :disabled="props.isDetailView"
                    >
                      Remove
                    </el-button>
                  </div>
                </template>
              </el-table-column>
            </el-table>
            <div class="pagination-container">
              <el-pagination
                :current-page="currentPage"
                :page-size="pageSize"
                :total="selectedEmployees.length"
                :page-sizes="[10, 25, 50, 100]"
                layout="total, prev, pager, next, jumper"
                @size-change="handleSizeChange"
                @current-change="handleCurrentChange"
              />
            </div>
          </div>

          <el-empty v-else description="No employees selected" />
        </div>

        <!-- Employee Details Display (for detail view) -->
        <div
          v-if="props.isDetailView && selectedEmployees.length > 0"
          class="employee-details"
        >
          <h4>Employee Details</h4>
          <el-table :data="selectedEmployees" style="width: 100%">
            <el-table-column
              prop="full_name"
              label="Employee Name"
              width="250"
            />
            <el-table-column prop="position" label="Position" min-width="200" />
            <el-table-column
              prop="prepaid_invoice_no"
              label="Prepaid Invoice No"
              width="150"
            />
            <el-table-column
              prop="prepaid_amount"
              label="Prepaid Amount"
              width="150"
            >
              <template #default="scope">
                ₱{{ formatCurrency(scope.row.prepaid_amount) }}
              </template>
            </el-table-column>
            <el-table-column
              prop="postpaid_invoice_no"
              label="Postpaid Invoice No"
              width="150"
            />
            <el-table-column
              prop="postpaid_amount"
              label="Postpaid Amount"
              width="150"
            >
              <template #default="scope">
                ₱{{ formatCurrency(scope.row.postpaid_amount) }}
              </template>
            </el-table-column>
            <el-table-column label="Total Amount" width="120">
              <template #default="scope">
                ₱{{ formatCurrency(lineTotal(scope.row)) }}
              </template>
            </el-table-column>
          </el-table>
        </div>

        <!-- Summary Section -->
        <div v-if="selectedEmployees.length > 0" class="summary-section">
          <div class="summary-stats">
            <el-row :gutter="16">
              <el-col :span="6">
                <el-card class="stat-card">
                  <div class="stat-content">
                    <div class="stat-label">Total Employees</div>
                    <div class="stat-value">{{ selectedEmployees.length }}</div>
                  </div>
                </el-card>
              </el-col>

              <el-col :span="6">
                <el-card class="stat-card">
                  <div class="stat-content">
                    <div class="stat-label">Total Prepaid</div>
                    <div class="stat-value">
                      ₱{{ formatCurrency(totalPrepaidAmount) }}
                    </div>
                  </div>
                </el-card>
              </el-col>

              <el-col :span="6">
                <el-card class="stat-card">
                  <div class="stat-content">
                    <div class="stat-label">Total Postpaid</div>
                    <div class="stat-value">
                      ₱{{ formatCurrency(totalPostpaidAmount) }}
                    </div>
                  </div>
                </el-card>
              </el-col>

              <el-col :span="6">
                <el-card class="stat-card">
                  <div class="stat-content">
                    <div class="stat-label">Grand Total</div>
                    <div class="stat-value">
                      ₱{{ formatCurrency(grandTotal) }}
                    </div>
                  </div>
                </el-card>
              </el-col>
            </el-row>
          </div>
        </div>

        <!-- Form Actions -->
        <div class="form-actions">
          <el-button @click="handleClose">Cancel</el-button>
          <el-button
            v-if="!props.isDetailView"
            type="primary"
            @click="handleSave"
            :loading="loading"
            :disabled="!isFormValid || selectedEmployees.length === 0"
          >
            {{ isEdit ? "Update" : "Create" }}
          </el-button>
          <el-button
            v-if="!props.isDetailView && isEdit && selectedEmployees.length > 0"
            type="success"
            @click="handleProcess"
            :loading="loading"
          >
            {{ formData.posted ? "Unpost" : "Post" }}
          </el-button>
          <el-button
            v-if="!props.isDetailView && isEdit"
            type="danger"
            :loading="loading"
            @click="showDeleteDialog = true"
          >
            Delete
          </el-button>
        </div>
      </el-form>
    </div>

    <!-- Employee Selection Dialog -->
    <el-dialog
      v-model="showEmployeeDialog"
      title="Select Employees"
      width="60%"
      style="border-radius: 10px"
      :close-on-click-modal="false"
      class="employee-selection-dialog"
    >
      <div class="employee-dialog">
        <el-alert
          type="info"
          :closable="false"
          show-icon
          class="mb-3"
          title="Only employees under the selected division (and not yet added) are shown here."
        />

        <div class="employee-dialog-header">
          <el-input
            v-model="employeeSearchQuery"
            placeholder="Search by name or position"
            clearable
            style="max-width: 360px"
            prefix-icon="el-icon-search"
          />
          <div class="employee-dialog-meta">
            <span class="meta-text">
              Available: {{ filteredEmployees.length }}
            </span>
            <span class="meta-text">
              Selected: {{ tempSelectedEmployees.length }}
            </span>
          </div>
        </div>

        <el-table
          :data="filteredEmployees"
          @selection-change="handleEmployeeSelection"
          style="width: 100%"
          max-height="400"
          stripe
          border
        >
          <el-table-column type="selection" width="55" />
          <el-table-column prop="full_name" label="Employee Name" width="300" />
          <el-table-column prop="position" label="Position" min-width="220" />
          <el-table-column
            prop="division"
            label="Division"
            min-width="200"
          />
        </el-table>
      </div>

      <template #footer>
        <el-button @click="showEmployeeDialog = false">Cancel</el-button>
        <el-button
          type="primary"
          @click="addSelectedEmployees"
          :disabled="tempSelectedEmployees.length === 0"
        >
          Add Selected ({{ tempSelectedEmployees.length }})
        </el-button>
      </template>
    </el-dialog>

    <!-- Delete Confirmation Dialog -->
    <el-dialog
      v-model="showDeleteDialog"
      title="Delete Reimbursement"
      width="420px"
      :close-on-click-modal="false"
    >
      <p>
        Are you sure you want to delete this reimbursement process? This action
        will remove the header and all employee details and cannot be undone.
      </p>
      <template #footer>
        <el-button @click="showDeleteDialog = false">Cancel</el-button>
        <el-button type="danger" :loading="loading" @click="confirmDelete">
          Delete
        </el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from "vue";
import { useReimbursementCommunication } from "../../../Composables/useReimbursementCommunication.js";
import { ElMessage } from "element-plus";

const props = defineProps({
  reimbursementData: {
    type: Object,
    default: null,
  },
  isDetailView: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(["saved", "close", "deleted"]);

// Composables
const {
  loading,
  formData,
  divisions,
  employees,
  selectedEmployees,
  months,
  isFormValid,
  loadFormData,
  saveReimbursement,
  addEmployees,
  removeEmployee,
  processReimbursement,
  deleteReimbursement,
  resetFormData,
} = useReimbursementCommunication();

// Local state
const formRef = ref(null);
const isEdit = ref(false);
const showEmployeeDialog = ref(false);
const showDeleteDialog = ref(false);
const employeeSearchQuery = ref("");
const tempSelectedEmployees = ref([]);
const yearOptions = ref([]);

// Pagination state
const currentPage = ref(1);
const pageSize = ref(10);

// Form validation rules
const formRules = {
  division_id: [
    { required: true, message: "Please select division", trigger: "change" },
  ],
  month_id: [
    { required: true, message: "Please select month", trigger: "change" },
  ],
  year: [{ required: true, message: "Please enter year", trigger: "blur" }],
};

// Computed properties
const filteredEmployees = computed(() => {
  const selectedIds = new Set(
    (selectedEmployees.value || [])
      .map((e) => Number(e.employee_id))
      .filter((id) => Number.isFinite(id)),
  );

  const base = (employees.value || []).filter(
    (emp) => !selectedIds.has(Number(emp.id)),
  );

  const sorted = [...base].sort((a, b) => {
    const an = (a?.full_name ?? "").toString();
    const bn = (b?.full_name ?? "").toString();
    return an.localeCompare(bn, "en", { sensitivity: "base" });
  });

  if (!employeeSearchQuery.value) return sorted;

  const query = employeeSearchQuery.value.toLowerCase();
  return sorted.filter(
    (emp) =>
      emp.full_name.toLowerCase().includes(query) ||
      emp.position.toLowerCase().includes(query),
  );
});

const totalPrepaidAmount = computed(() => {
  return selectedEmployees.value.reduce((sum, emp) => {
    const amount = Number(emp.prepaid_amount || 0);
    return Number(sum) + (isNaN(amount) ? 0 : amount);
  }, 0);
});

const totalPostpaidAmount = computed(() => {
  return selectedEmployees.value.reduce((sum, emp) => {
    const amount = Number(emp.postpaid_amount || 0);
    return Number(sum) + (isNaN(amount) ? 0 : amount);
  }, 0);
});

const grandTotal = computed(() => {
  return Number(totalPrepaidAmount.value) + Number(totalPostpaidAmount.value);
});

const toAmount = (value) => {
  const n = Number(value);
  return Number.isFinite(n) ? n : 0;
};

const lineTotal = (row) =>
  toAmount(row?.prepaid_amount) + toAmount(row?.postpaid_amount);

const formatCurrency = (value) => {
  return toAmount(value).toLocaleString(undefined, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
};

// Month name for detail view
const selectedMonthName = computed(() => {
  const match = months.value.find(
    (m) => Number(m.id) === Number(formData.value.month_id),
  );
  return match?.name ?? String(formData.value.month_id ?? "");
});

// Paginated employees
const paginatedEmployees = computed(() => {
  const start = (currentPage.value - 1) * pageSize.value;
  const end = start + pageSize.value;
  return selectedEmployees.value.slice(start, end);
});

// Methods
const handleClose = () => {
  resetFormData();
  emit("close");
};

const handleDivisionChange = async () => {
  const currentId = Number(formData.value.id || 0);
  const divisionId = formData.value.division_id;
  const monthId = formData.value.month_id;
  const year = formData.value.year;

  if (currentId === 0) {
    selectedEmployees.value = [];
  }

  employeeSearchQuery.value = "";
  tempSelectedEmployees.value = [];

  await loadFormData(currentId, { divisionId });

  formData.value.division_id = divisionId;
  formData.value.month_id = monthId;
  formData.value.year = year;
};

const handleSave = async () => {
  try {
    if (!formRef.value) return;

    await formRef.value.validate();

    // Save reimbursement header
    const response = await saveReimbursement(formData.value.id, {
      division_id: formData.value.division_id,
      department_id: formData.value.division_id,
      month_id: formData.value.month_id,
      year: formData.value.year,
    });

    const newId = response.id ?? response.data?.id ?? response?.data?.data?.id;
    if (newId) {
      formData.value.id = newId;

      // Add employees if any selected
      if (selectedEmployees.value.length > 0) {
        // Keep user's entered amounts keyed by employee_id before we reload
        const amountsByEmployeeId = selectedEmployees.value.reduce(
          (acc, emp) => {
            acc[emp.employee_id] = {
              prepaid_invoice_no: emp.prepaid_invoice_no || 0,
              prepaid_amount: emp.prepaid_amount || 0,
              postpaid_invoice_no: emp.postpaid_invoice_no || 0,
              postpaid_amount: emp.postpaid_amount || 0,
            };
            return acc;
          },
          {},
        );

        const employeeData = {
          id: selectedEmployees.value.map((emp) => emp.employee_id),
          select: selectedEmployees.value.map((emp) => emp.employee_id),
        };

        // 1) Insert detail rows
        await addEmployees(newId, employeeData);

        // 2) Reload to get created reimbursement detail IDs
        await loadFormData(newId);

        // 3) Merge the user's entered amounts back into the freshly loaded rows
        selectedEmployees.value = selectedEmployees.value.map((emp) => ({
          ...emp,
          ...(amountsByEmployeeId[emp.employee_id] || {}),
        }));

        // 4) Persist amounts (do NOT post yet). The backend requires detail IDs in 'id'
        const processPayload = {
          id: selectedEmployees.value.map((emp) => emp.id), // reimbursement detail ids
          prepaid_invoice_no: selectedEmployees.value.map(
            (emp) => emp.prepaid_invoice_no || 0,
          ),
          prepaid_amount: selectedEmployees.value.map(
            (emp) => emp.prepaid_amount || 0,
          ),
          postpaid_invoice_no: selectedEmployees.value.map(
            (emp) => emp.postpaid_invoice_no || 0,
          ),
          postpaid_amount: selectedEmployees.value.map(
            (emp) => emp.postpaid_amount || 0,
          ),
        };

        // typeId 0 keeps it unposted while saving amounts
        await processReimbursement(newId, 0, processPayload);
      }

      ElMessage.success(
        "Reimbursement communication expenses saved successfully",
      );
      emit("saved", response);
    }
  } catch (error) {
    console.error("Error saving reimbursement:", error);
  }
};

const handleProcess = async () => {
  try {
    if (selectedEmployees.value.length === 0) {
      ElMessage.warning("Please add employees first");
      return;
    }

    const employeeData = {
      id: selectedEmployees.value.map((emp) => emp.id),
      prepaid_invoice_no: selectedEmployees.value.map(
        (emp) => emp.prepaid_invoice_no || 0,
      ),
      prepaid_amount: selectedEmployees.value.map(
        (emp) => emp.prepaid_amount || 0,
      ),
      postpaid_invoice_no: selectedEmployees.value.map(
        (emp) => emp.postpaid_invoice_no || 0,
      ),
      postpaid_amount: selectedEmployees.value.map(
        (emp) => emp.postpaid_amount || 0,
      ),
    };

    const typeId = formData.value.posted ? 0 : 1; // 1 for post, 0 for unpost
    await processReimbursement(formData.value.id, typeId, employeeData);

    formData.value.posted = !formData.value.posted;
    ElMessage.success(
      `Reimbursement ${formData.value.posted ? "posted" : "unposted"} successfully`,
    );
  } catch (error) {
    console.error("Error processing reimbursement:", error);
  }
};

const handleDelete = async () => {
  try {
    if (!formData.value?.id) return;
    const deletedId = formData.value.id;
    await deleteReimbursement(deletedId);
    resetFormData();
    ElMessage.success("Reimbursement deleted");
    emit("deleted", deletedId);
  } catch (error) {
    // handled in composable
  }
};

const confirmDelete = async () => {
  await handleDelete();
  showDeleteDialog.value = false;
};

const handleEmployeeSelection = (selection) => {
  tempSelectedEmployees.value = selection;
};

const addSelectedEmployees = () => {
  const existingIds = new Set(
    selectedEmployees.value.map((e) => e.employee_id),
  );

  const newEmployees = tempSelectedEmployees.value
    .filter((emp) => !existingIds.has(emp.id))
    .map((emp) => ({
      id: 0,
      employee_id: emp.id,
      full_name: emp.full_name,
      position: emp.position,
      prepaid_invoice_no: 0,
      prepaid_amount: 0,
      postpaid_invoice_no: 0,
      postpaid_amount: 0,
    }));

  selectedEmployees.value = [...selectedEmployees.value, ...newEmployees];

  showEmployeeDialog.value = false;
  tempSelectedEmployees.value = [];
  employeeSearchQuery.value = "";
};

const removeEmployeeFromList = async (id) => {
  try {
    if (id > 0) {
      await removeEmployee(id);
    }

    selectedEmployees.value = selectedEmployees.value.filter(
      (emp) => emp.id !== id,
    );

    // Reset to first page if current page becomes empty
    const totalPages = Math.ceil(
      selectedEmployees.value.length / pageSize.value,
    );
    if (currentPage.value > totalPages && totalPages > 0) {
      currentPage.value = totalPages;
    } else if (totalPages === 0) {
      currentPage.value = 1;
    }
  } catch (error) {
    console.error("Error removing employee:", error);
  }
};

// Pagination handlers
const handleSizeChange = (val) => {
  pageSize.value = val;
  currentPage.value = 1;
};

const handleCurrentChange = (val) => {
  currentPage.value = val;
};

// Watch for props changes
watch(
  () => props.reimbursementData,
  (newData) => {
    if (newData) {
      isEdit.value = true;
      formData.value = { ...newData };
    } else {
      isEdit.value = false;
      resetFormData();
    }
    // Reset pagination when data changes
    currentPage.value = 1;
  },
  { immediate: true },
);

// Watch for selectedEmployees changes to reset pagination if needed
watch(
  () => selectedEmployees.value.length,
  () => {
    const totalPages = Math.ceil(
      selectedEmployees.value.length / pageSize.value,
    );
    if (currentPage.value > totalPages && totalPages > 0) {
      currentPage.value = totalPages;
    } else if (totalPages === 0) {
      currentPage.value = 1;
    }
  },
);

// Load form data on mount
onMounted(async () => {
  try {
    // Build year options (2015 -> current year)
    const currentYear = new Date().getFullYear();
    const startYear = 2015;
    const years = [];
    for (let y = currentYear; y >= startYear; y--) years.push(y);
    yearOptions.value = years;

    await loadFormData(props.reimbursementData?.id || 0);

    if (divisions.value.length === 0) {
      console.warn("No divisions loaded, trying to reload form data");
      await loadFormData(0);
    }
  } catch (error) {
    console.error("Error loading form data:", error);
  }
});
</script>

<style scoped>
.reimbursement-communication-form {
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
}

.form-container {
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

.form-row {
  display: flex;
  gap: 24px;
  margin-bottom: 24px;
}

.employee-section {
  margin: 24px 0;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.section-header h4 {
  margin: 0;
  color: #303133;
  font-size: 16px;
  font-weight: 600;
}

.table-container {
  margin-bottom: 16px;
}

.table-header {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  margin-bottom: 12px;
  padding: 8px 0;
}

.show-entries {
  display: flex;
  align-items: center;
  font-size: 14px;
  color: #606266;
}

.employee-table {
  margin-bottom: 16px;
}

.pagination-container {
  display: flex;
  justify-content: flex-end;
  margin-top: 16px;
  padding-top: 16px;
  border-top: 1px solid #e4e7ed;
}

.employee-details h4 {
  margin: 0 0 16px 0;
  color: #303133;
  font-size: 16px;
  font-weight: 600;
}

.summary-section {
  margin: 24px 0;
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

.mb-3 {
  margin-bottom: 1rem;
}

.employee-dialog-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 12px;
}

.employee-dialog-meta {
  display: flex;
  gap: 12px;
  color: #6b7280;
  font-size: 13px;
  white-space: nowrap;
}

.meta-text {
  font-weight: 600;
}

.summary-content {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
}

.summary-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 0;
}

.summary-item.total {
  font-weight: 600;
  font-size: 16px;
  color: #409eff;
  padding-top: 12px;
}

.summary-item .label {
  color: #606266;
  margin-right: 6px;
}

.summary-item .value {
  color: #303133;
  font-weight: 500;
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

.employee-dialog {
  max-height: 500px;
}

.search-form {
  margin-bottom: 16px;
}

.reimbursement-form {
  max-width: 100%;
}

@media (max-width: 768px) {
  .form-row {
    flex-direction: column;
    gap: 16px;
  }

  .summary-content {
    grid-template-columns: 1fr;
  }
}
</style>
