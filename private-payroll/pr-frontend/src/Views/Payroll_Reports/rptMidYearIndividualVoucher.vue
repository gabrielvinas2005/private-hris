<template>
  <PageScaffold
    title="Mid-Year Individual Voucher Report"
    subtitle="Generate individual vouchers for mid-year bonus"
    :breadcrumbs="[
      { label: 'Payroll Module', to: '/' },
      { label: 'Payroll Reports', to: '/payroll-reports' },
      { label: 'Mid-Year Bonus Reports', to: '/mid-year-bonus-report-hub' },
      { label: 'Individual Voucher' },
    ]"
  >
    <el-card class="box-card">
      <template #header>
        <div class="card-header">
          <span>Mid-Year Individual Voucher Report</span>
        </div>
      </template>

      <el-alert
        v-if="error"
        :title="error"
        type="error"
        :closable="true"
        @close="error = null"
        class="mb-3"
      />

      <el-form label-width="140px">
        <el-row :gutter="20">
          <el-col :span="8" :xs="24">
            <el-form-item label="Division" required>
              <el-select
                v-model="formData.division_id"
                placeholder="Select Division"
                style="width: 100%"
                clearable
                :loading="loading"
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
          </el-col>

          <el-col :span="8" :xs="24">
            <el-form-item label="Employee" required>
              <el-select
                v-model="formData.employee_id"
                placeholder="Select Employee"
                style="width: 100%"
                clearable
                filterable
                :loading="loading"
                :disabled="!formData.division_id"
              >
                <el-option
                  v-for="emp in employees"
                  :key="emp.id"
                  :label="emp.name"
                  :value="emp.id"
                />
              </el-select>
            </el-form-item>
          </el-col>

          <el-col :span="8" :xs="24">
            <el-form-item label="Year" required>
              <el-select
                v-model="formData.years"
                placeholder="Select Year"
                style="width: 100%"
                clearable
                :loading="loading"
              >
                <el-option
                  v-for="year in years"
                  :key="year.years"
                  :label="year.years"
                  :value="year.years"
                />
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>

        <el-divider />

        <h4 style="margin-bottom: 15px; color: #333">Signatories</h4>

        <el-row :gutter="20">
          <el-col :span="8">
            <el-form-item label="Certifying Officer" required>
              <el-select
                v-model="selectedCertifyingOfficerId"
                filterable
                clearable
                placeholder="Search employee"
                style="width: 100%"
                @change="handleCertifyingOfficerSelect"
              >
                <el-option
                  v-for="option in employeeOptions"
                  :key="option.id"
                  :label="option.name"
                  :value="option.id"
                />
              </el-select>
            </el-form-item>
            <el-form-item label="Position">
              <el-input
                v-model="signatories.certifying_officer_position"
                placeholder="Position"
                disabled
              />
            </el-form-item>
          </el-col>

          <el-col :span="8">
            <el-form-item label="Accountant" required>
              <el-select
                v-model="selectedAccountantId"
                filterable
                clearable
                placeholder="Search employee"
                style="width: 100%"
                @change="handleAccountantSelect"
              >
                <el-option
                  v-for="option in employeeOptions"
                  :key="option.id"
                  :label="option.name"
                  :value="option.id"
                />
              </el-select>
            </el-form-item>
            <el-form-item label="Position">
              <el-input
                v-model="signatories.accountant_position"
                placeholder="Position"
                disabled
              />
            </el-form-item>
          </el-col>

          <el-col :span="8">
            <el-form-item label="Approving Officer" required>
              <el-select
                v-model="selectedApprovingOfficerId"
                filterable
                clearable
                placeholder="Search employee"
                style="width: 100%"
                @change="handleApprovingOfficerSelect"
              >
                <el-option
                  v-for="option in employeeOptions"
                  :key="option.id"
                  :label="option.name"
                  :value="option.id"
                />
              </el-select>
            </el-form-item>
            <el-form-item label="Position">
              <el-input
                v-model="signatories.approving_officer_position"
                placeholder="Position"
                disabled
              />
            </el-form-item>
          </el-col>
        </el-row>

        <el-divider />

        <el-form-item>
          <el-button
            type="success"
            :loading="loading"
            :disabled="!canGenerateReport"
            @click="previewReport"
            class="mr-3"
          >
            Preview Report
          </el-button>
          <el-button
            type="primary"
            :loading="loading"
            :disabled="!canGenerateReport"
            @click="generateReport"
          >
            Generate Report
          </el-button>
          <el-button @click="goBack">Back to Reports</el-button>
        </el-form-item>
      </el-form>
    </el-card>
  </PageScaffold>
</template>

<script setup>
import { computed, onMounted, ref } from "vue";
import { useRouter } from "vue-router";
import PageScaffold from "../../components/PageScaffold.vue";
import { useMidYearIndividualVoucher } from "../../Composables/useMidYearIndividualVoucher.js";

const router = useRouter();

const {
  loading,
  error,
  formData,
  divisions,
  years,
  employees,
  employeeOptions,
  signatories,
  loadInitialData,
  generateReport,
  previewReport,
} = useMidYearIndividualVoucher();

const selectedCertifyingOfficerId = ref(null);
const selectedAccountantId = ref(null);
const selectedApprovingOfficerId = ref(null);

const handleCertifyingOfficerSelect = (value) => {
  selectedCertifyingOfficerId.value = value ?? null;
  if (!value) {
    signatories.certifying_officer_name = "";
    signatories.certifying_officer_position = "";
    return;
  }
  const selected = employeeOptions.value.find((option) => option.id === value);
  signatories.certifying_officer_name = selected?.name || "";
  signatories.certifying_officer_position = selected?.position || "";
};

const handleAccountantSelect = (value) => {
  selectedAccountantId.value = value ?? null;
  if (!value) {
    signatories.accountant_name = "";
    signatories.accountant_position = "";
    return;
  }
  const selected = employeeOptions.value.find((option) => option.id === value);
  signatories.accountant_name = selected?.name || "";
  signatories.accountant_position = selected?.position || "";
};

const handleApprovingOfficerSelect = (value) => {
  selectedApprovingOfficerId.value = value ?? null;
  if (!value) {
    signatories.approving_officer_name = "";
    signatories.approving_officer_position = "";
    return;
  }
  const selected = employeeOptions.value.find((option) => option.id === value);
  signatories.approving_officer_name = selected?.name || "";
  signatories.approving_officer_position = selected?.position || "";
};

const canGenerateReport = computed(() => {
  return formData.division_id && formData.employee_id && formData.years;
});

const handleDivisionChange = () => {
  // Employee filtering is handled automatically by the composable watch
};

const goBack = () => {
  router.push("/mid-year-bonus-report-hub");
};

onMounted(async () => {
  try {
    await loadInitialData();
  } catch (err) {
    console.error("Failed to load initial data:", err);
  }
});
</script>

<style scoped>
.box-card {
  box-shadow:
    0 0 1px rgba(0, 0, 0, 0.125),
    0 1px 3px rgba(0, 0, 0, 0.2);
  border: 1px solid #dee2e6;
  border-radius: 25px;
}

.card-header {
  border-bottom: 1px solid #dee2e6;
  padding: 0.75rem 1.25rem;
  font-weight: 500;
  color: #333;
}

.mb-3 {
  margin-bottom: 1rem;
}
</style>
