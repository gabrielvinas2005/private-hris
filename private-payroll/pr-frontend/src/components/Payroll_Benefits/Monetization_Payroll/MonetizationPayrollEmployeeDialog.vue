<template>
  <el-dialog
    v-if="!inline"
    :model-value="modelValue"
    @update:model-value="emit('update:modelValue', $event)"
    width="1000px"
    title="Manage Monetization Employees"
    destroy-on-close
  >
    <div class="panel">
      <div class="panel-header">
        <div class="panel-title">Employee List</div>
        <el-button
          type="primary"
          @click="handleAddEmployees"
          :disabled="readOnly"
        >
          Add Employees
        </el-button>
      </div>

      <div class="filters">
        <el-input
          v-model="searchQuery"
          placeholder="Search by employee name or number"
          clearable
          class="search-input"
        />
        <el-button @click="searchQuery = ''">Reset</el-button>
      </div>

      <el-table :data="paginatedEmployees" border stripe height="420px">
        <el-table-column prop="employee_no" label="Employee #" width="140" />
        <el-table-column prop="name" label="Employee Name" />
        <el-table-column prop="position" label="Position" />
        <el-table-column prop="total_days" label="Total Days" width="120" />
        <el-table-column prop="amount" label="Amount" width="140">
          <template #default="scope"
            >₱{{ Number(scope.row.amount || 0).toLocaleString() }}</template
          >
        </el-table-column>
        <el-table-column label="Remove" width="120">
          <template #default="scope">
            <el-button
              type="danger"
              @click="handleRemove(scope.row)"
              :disabled="readOnly"
            >
              Remove
            </el-button>
          </template>
        </el-table-column>
      </el-table>

      <!-- Pagination -->
      <div
        class="pagination-container"
        v-if="filteredCurrentEmployees.length > 0"
      >
        <div class="pagination-info">
          <span class="text-sm text-gray-600">
            Showing {{ (currentPage - 1) * pageSize + 1 }} to
            {{ Math.min(currentPage * pageSize, totalItems) }} of
            {{ totalItems }} entries
          </span>
        </div>

        <el-pagination
          :current-page="currentPage"
          :page-size="pageSize"
          :page-sizes="[10, 20, 50, 100]"
          :total="totalItems"
          layout="prev, pager, next, sizes"
          @size-change="handleSizeChange"
          @current-change="handleCurrentChange"
          class="pagination"
        />
      </div>
    </div>

    <template #footer>
      <el-button @click="emit('update:modelValue', false)">Close</el-button>
      <el-button
        v-if="unsavedCount > 0 && !readOnly"
        type="primary"
        :loading="loading"
        @click="handleSaveChanges"
      >
        Save Changes ({{ unsavedCount }})
      </el-button>
    </template>

    <!-- Employee Picker Dialog -->
    <el-dialog
      v-model="employeePickerOpen"
      width="900px"
      title="Employee List"
      append-to-body
    >
      <el-input
        v-model="pickerSearch"
        placeholder="Search by name or employee no."
        clearable
        class="search-input"
      />

      <div class="select-all">
        <el-checkbox v-model="selectAll" @change="toggleSelectAll"
          >Select All Employees</el-checkbox
        >
      </div>

      <el-table :data="filteredAvailableEmployees" height="420px" border stripe>
        <el-table-column label="Select" width="80">
          <template #default="scope">
            <el-checkbox
              v-model="scope.row.__selected"
              :label="scope.row.employeeId"
            />
          </template>
        </el-table-column>
        <el-table-column prop="employeeNo" label="Employee No" width="140" />
        <el-table-column prop="name" label="Name" min-width="220" />
        <el-table-column prop="position" label="Position" min-width="200" />
        <el-table-column prop="totalDays" label="Total Days" width="110" />
        <el-table-column label="Amount" width="130">
          <template #default="scope">
            ₱{{ Number(scope.row.amount || 0).toLocaleString() }}
          </template>
        </el-table-column>
      </el-table>

      <template #footer>
        <el-button @click="employeePickerOpen = false">Close</el-button>
        <el-button type="primary" :loading="loading" @click="confirmAddSelected"
          >Add</el-button
        >
      </template>
    </el-dialog>
  </el-dialog>

  <div v-else class="panel inline-panel">
    <div class="panel-header">
      <div class="panel-title">Employee List</div>
      <el-button
        type="primary"
        @click="handleAddEmployees"
        :disabled="readOnly"
      >
        Add Employees
      </el-button>
    </div>

    <div class="filters">
      <el-input
        v-model="searchQuery"
        placeholder="Search by employee name or number"
        clearable
        class="search-input"
      />
      <el-button @click="searchQuery = ''">Reset</el-button>
    </div>

    <el-table :data="paginatedEmployees" border stripe height="420px">
      <el-table-column prop="employee_no" label="Employee #" width="140" />
      <el-table-column prop="name" label="Employee Name" />
      <el-table-column prop="position" label="Position" />
      <el-table-column prop="total_days" label="Total Days" width="120" />
      <el-table-column prop="amount" label="Amount" width="140">
        <template #default="scope">
          ₱{{ Number(scope.row.amount || 0).toLocaleString() }}
        </template>
      </el-table-column>
      <el-table-column label="Remove" width="120">
        <template #default="scope">
          <el-button
            type="danger"
            @click="handleRemove(scope.row)"
            :disabled="readOnly"
          >
            Remove
          </el-button>
        </template>
      </el-table-column>
    </el-table>

    <div
      class="pagination-container"
      v-if="filteredCurrentEmployees.length > 0"
    >
      <div class="pagination-info">
        <span class="text-sm text-gray-600">
          Showing {{ (currentPage - 1) * pageSize + 1 }} to
          {{ Math.min(currentPage * pageSize, totalItems) }} of
          {{ totalItems }} entries
        </span>
      </div>

      <el-pagination
        :current-page="currentPage"
        :page-size="pageSize"
        :page-sizes="[10, 20, 50, 100]"
        :total="totalItems"
        layout="prev, pager, next, sizes"
        @size-change="handleSizeChange"
        @current-change="handleCurrentChange"
        class="pagination"
      />
    </div>

    <div
      v-if="!readOnly && unsavedCount > 0"
      class="panel-footer panel-footer-right"
    >
      <el-button type="primary" :loading="loading" @click="handleSaveChanges">
        Create
      </el-button>
    </div>

    <el-dialog
      v-model="employeePickerOpen"
      width="1300px"
      title="Employee List"
      append-to-body
    >
      <el-input
        v-model="pickerSearch"
        placeholder="Search by name or employee no."
        clearable
        class="search-input"
      />

      <div class="select-all">
        <el-checkbox v-model="selectAll" @change="toggleSelectAll"
          >Select All Employees</el-checkbox
        >
      </div>

      <el-table :data="filteredAvailableEmployees" height="420px" border stripe>
        <el-table-column label="Select" width="80" align="center">
          <template #default="scope">
            <el-checkbox v-model="scope.row.__selected" />
          </template>
        </el-table-column>
        <el-table-column prop="employeeNo" label="Employee No" width="140" />
        <el-table-column prop="name" label="Name" min-width="220" />
        <el-table-column prop="position" label="Position" min-width="200" />
        <el-table-column prop="totalDays" label="Total Days" width="110" />
        <el-table-column label="Amount" width="130">
          <template #default="scope">
            ₱{{ Number(scope.row.amount || 0).toLocaleString() }}
          </template>
        </el-table-column>
      </el-table>

      <template #footer>
        <el-button @click="employeePickerOpen = false">Close</el-button>
        <el-button type="primary" :loading="loading" @click="confirmAddSelected"
          >Add</el-button
        >
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, watch, computed } from "vue";
import { ElMessage } from "element-plus";
import { employeeApi } from "../../../services/api.js";

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  form: { type: Object, required: true },
  employees: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  readOnly: { type: Boolean, default: false },
  inline: { type: Boolean, default: false },
});

const emit = defineEmits([
  "update:modelValue",
  "save-employees",
  "remove-employee",
]);

const selectedIds = ref([]);
const employeePickerOpen = ref(false);
const searchQuery = ref("");
const pickerSearch = ref("");
const selectAll = ref(false);
const availableEmployees = ref([]);
const isFetching = ref(false);
const hasChanges = ref(false);
const currentPage = ref(1);
const pageSize = ref(10);

watch(
  () => props.modelValue,
  (open) => {
    if (open) {
      selectedIds.value = [];
      currentPage.value = 1;
    }
  },
);

watch(
  () => searchQuery.value,
  () => {
    currentPage.value = 1;
  },
);

const handleAddEmployees = async () => {
  employeePickerOpen.value = true;
  await fetchAvailableEmployees();
};

const filteredCurrentEmployees = computed(() => {
  const list = props.form.data || [];
  if (!searchQuery.value) return list;
  const q = searchQuery.value.toLowerCase();
  return list.filter(
    (r) =>
      (r.employee_no || "").toLowerCase().includes(q) ||
      (r.name || "").toLowerCase().includes(q),
  );
});

const totalItems = computed(() => filteredCurrentEmployees.value.length);

const paginatedEmployees = computed(() => {
  const start = (currentPage.value - 1) * pageSize.value;
  const end = start + pageSize.value;
  return filteredCurrentEmployees.value.slice(start, end);
});

const handleSizeChange = (newSize) => {
  pageSize.value = newSize;
  currentPage.value = 1;
};

const handleCurrentChange = (newPage) => {
  currentPage.value = newPage;
};

const fetchAvailableEmployees = async () => {
  try {
    isFetching.value = true;
    // Prefer monetization employees from the loaded form (already filtered by approval)
    let rows = Array.isArray(props.form.monetization_employees)
      ? props.form.monetization_employees
      : [];
    if (!rows || rows.length === 0) {
      const params = {
        search: pickerSearch.value || "",
        page: 1,
        per_page: 50,
        plantilla_only: 1,
      };
      const response = await employeeApi.list(params);
      rows = response.data?.data || response.data || [];
    }
    const existingMonetizationIds = new Set(
      (props.form.data || [])
        .map((r) => r.monetization_id)
        .filter((id) => id != null),
    );

    availableEmployees.value = rows
      .filter(
        (e) =>
          !existingMonetizationIds.has(
            e.monetization_id || e.monetizationId || null,
          ),
      )
      .map((e) => ({
        employeeId: e.id || e.employee_id,
        employeeNo: e.employee_no || e.employeeNo,
        name: e.name || `${e.first_name ?? ""} ${e.last_name ?? ""}`.trim(),
        position: e.position?.name || e.position || "",
        totalDays: e.total_days ?? e.totalDays ?? 0,
        amount: e.amount ?? 0,
        monetizationId: e.monetization_id || e.monetizationId || null,
        __selected: false,
      }));
  } catch (err) {
    console.error("Failed to fetch employees:", err);
    ElMessage.error("Failed to load employees");
  } finally {
    isFetching.value = false;
  }
};

const filteredAvailableEmployees = computed(() => {
  if (!pickerSearch.value) return availableEmployees.value;
  const q = pickerSearch.value.toLowerCase();
  return availableEmployees.value.filter(
    (e) =>
      e.name?.toLowerCase().includes(q) ||
      e.employeeNo?.toLowerCase().includes(q) ||
      e.position?.toLowerCase().includes(q),
  );
});

const toggleSelectAll = (checked) => {
  filteredAvailableEmployees.value.forEach((e) => (e.__selected = checked));
};

const confirmAddSelected = () => {
  const newlySelected = availableEmployees.value.filter((e) => e.__selected);
  if (newlySelected.length === 0) {
    employeePickerOpen.value = false;
    return;
  }

  // Map to table schema used by props.form.data for immediate display
  const toAdd = newlySelected
    .map((e) => ({
      dtl_id: null,
      employee_no: e.employeeNo,
      name: e.name,
      position: e.position,
      total_days: e.totalDays ?? 0,
      amount: e.amount ?? 0,
      employee_id: e.employeeId,
      monetization_id: e.monetizationId || null,
    }))
    .filter((r) => r.employee_no && r.name);

  const existingMonetizationIds = new Set(
    (props.form.data || [])
      .map((r) => r.monetization_id)
      .filter((id) => id != null),
  );
  toAdd.forEach((r) => {
    if (!existingMonetizationIds.has(r.monetization_id)) {
      (props.form.data || (props.form.data = [])).push(r);
    }
  });

  hasChanges.value = true;
  employeePickerOpen.value = false;
};

const handleRemove = (row) => {
  if (row && row.dtl_id) {
    emit("remove-employee", row.dtl_id);
    return;
  }
  // Local removal for newly added (unsaved) rows
  const list = props.form.data || [];
  const index = list.findIndex((r) =>
    row.monetization_id
      ? r.monetization_id === row.monetization_id
      : r.employee_no === row.employee_no,
  );
  if (index !== -1) {
    list.splice(index, 1);
  }
  hasChanges.value = true;
};

const unsavedCount = computed(
  () => (props.form.data || []).filter((r) => !r.dtl_id).length,
);

const handleSaveChanges = () => {
  const newRows = (props.form.data || []).filter((r) => !r.dtl_id);
  if (newRows.length === 0) return;

  const monetizationIds = newRows
    .map((r) => r.monetization_id)
    .filter((v) => v != null);
  const employeeIds = newRows
    .map((r) => r.employee_id)
    .filter((v) => v != null);

  if (monetizationIds.length === 0 || employeeIds.length === 0) {
    ElMessage.error("Selected employees are missing monetization records.");
    return;
  }

  const payload = {
    headerId: props.form.id,
    monetization_id: monetizationIds,
    select: monetizationIds,
    id: employeeIds,
  };

  emit("save-employees", payload);
  hasChanges.value = false;
};
</script>

<style scoped>
.panel {
  border: 1px solid #e5e7eb;
  border-radius: 8px;
}
.panel-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  border-bottom: 1px solid #e5e7eb;
}
.panel-title {
  font-weight: 600;
}
.filters {
  display: flex;
  gap: 8px;
  padding: 12px 16px;
  border-bottom: 1px solid #f3f4f6;
}
.search-input {
  width: 360px;
}
.select-all {
  margin: 8px 0 12px 0;
}
.pagination-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px;
  background: #f8fafc;
  border-top: 1px solid #e5e7eb;
}
.pagination-info {
  color: #6b7280;
  font-size: 14px;
}
.pagination {
  margin: 0;
}

.panel-footer {
  padding: 12px 16px;
  border-top: 1px solid #e5e7eb;
  background-color: #f9fafb;
}

.panel-footer-right {
  display: flex;
  justify-content: flex-end;
}
</style>
