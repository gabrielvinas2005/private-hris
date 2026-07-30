<template>
  <PageScaffold
    title="RATA Payroll"
    subtitle="Manage Representation and Transportation Allowance (RATA)"
    :breadcrumbs="[
      { label: 'Payroll Module', to: '/' },
      { label: 'Payroll Benefits', to: '/payroll-benefits' },
      { label: 'RATA Payroll' },
    ]"
  >
    <template #actions>
      <el-button v-if="!showForm" type="primary" @click="handleCreate">
        Create RATA Payroll
      </el-button>
    </template>

    <!-- Search and Filter Controls -->
    <el-form :inline="true" class="mb-4">
      <!-- Branch filter — not used (single branch only)
      <el-form-item label="Branch">
        <el-select
          v-model="filters.branchId"
          placeholder="All Branches"
          clearable
          style="width: 200px"
          @change="loadRATAPayrollList"
        >
          <el-option label="All Branches" value="" />
          <el-option
            v-for="branch in branches"
            :key="branch.id"
            :label="branch.name"
            :value="branch.id"
          />
        </el-select>
      </el-form-item>
      -->
      <el-form-item label="Month">
        <el-select
          v-model="filters.monthId"
          placeholder="All Months"
          clearable
          style="width: 150px"
          @change="loadRATAPayrollList"
        >
          <el-option label="All Months" value="" />
          <el-option
            v-for="(month, index) in months"
            :key="index + 1"
            :label="month"
            :value="index + 1"
          />
        </el-select>
      </el-form-item>
      <el-form-item label="Year">
        <el-select
          v-model="filters.yearId"
          placeholder="All Years"
          clearable
          style="width: 120px"
          @change="loadRATAPayrollList"
        >
          <el-option label="All Years" value="" />
          <el-option
            v-for="year in years"
            :key="year"
            :label="year"
            :value="year"
          />
        </el-select>
      </el-form-item>
      <el-form-item>
        <el-button
          type="primary"
          @click="loadRATAPayrollList"
          :loading="loading"
        >
          Search
        </el-button>
      </el-form-item>
    </el-form>

    <!-- RATA Payroll List Component -->
    <RATAPayrollList
      v-if="!showForm"
      :key="listKey"
      :rows="rataPayrollList"
      :loading="loading"
      :error="error"
      :filters="filters"
      :clear-error="clearError"
      :process-fn="processRATAPayroll"
      :delete-fn="deleteRATAPayroll"
      @edit="handleEdit"
      @detail="handleDetail"
    />

    <!-- RATA Payroll Form (inline) -->
    <RATAPayrollForm
      v-else
      :rata-id="Number(selectedRATAPayrollId)"
      @saved="handleFormSaved"
      @close="handleFormClose"
    />
  </PageScaffold>
</template>

<script setup>
import { ref, reactive, onMounted } from "vue";
import PageScaffold from "../../../components/PageScaffold.vue";
import RATAPayrollList from "../../../components/Payroll_Benefits/RATA_Payroll/RATAPayrollList.vue";
import RATAPayrollForm from "../../../components/Payroll_Benefits/RATA_Payroll/RATAPayrollForm.vue";
import { useRATAPayroll } from "../../../Composables/useRATAPayroll.js";

// State
const showForm = ref(false);
const listKey = ref(0);
const selectedRATAPayrollId = ref(0);
const selectedRATAPayroll = ref(null);

// Lifted composable/state
const {
  loading,
  error,
  rataPayrollList,
  loadRATAPayrollList: loadList,
  processRATAPayroll,
  deleteRATAPayroll,
  clearError,
} = useRATAPayroll();

// Filters and options (branch filter disabled — single branch only)
const filters = reactive({
  monthId: "",
  yearId: "",
});
const years = ref([]);
const months = [
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

const generateYears = () => {
  const currentYear = new Date().getFullYear();
  years.value = [];
  for (let i = currentYear - 5; i <= currentYear + 5; i++) {
    years.value.push(i);
  }
};

const loadRATAPayrollList = async () => {
  await loadList();
};

onMounted(() => {
  generateYears();
  loadRATAPayrollList();
});

// Methods
const handleCreate = () => {
  selectedRATAPayrollId.value = 0;
  selectedRATAPayroll.value = null;
  showForm.value = true;
};

const handleEdit = (rataPayroll) => {
  selectedRATAPayrollId.value = rataPayroll.id;
  selectedRATAPayroll.value = rataPayroll;
  showForm.value = true;
};

const handleDetail = (rataPayroll) => {
  // Mirror Uniform behavior: open detail view (form) for the posted record
  selectedRATAPayrollId.value = rataPayroll.id;
  selectedRATAPayroll.value = rataPayroll;
  showForm.value = true;
};

const handleFormSaved = (newId) => {
  // If a new header was created, keep the form open to manage employees
  if (Number(newId) > 0 && selectedRATAPayrollId.value === 0) {
    selectedRATAPayrollId.value = Number(newId);
    return;
  }
  // Otherwise (update), close and refresh list
  showForm.value = false;
  selectedRATAPayrollId.value = 0;
  listKey.value++;
};

const handleFormClose = () => {
  selectedRATAPayrollId.value = 0;
  selectedRATAPayroll.value = null;
  showForm.value = false;
};
</script>

<style scoped>
.mb-3 {
  margin-bottom: 12px;
}
</style>
