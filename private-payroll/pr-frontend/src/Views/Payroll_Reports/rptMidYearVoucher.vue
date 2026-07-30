<template>
  <PageScaffold
    title="Mid-Year Voucher Report"
    subtitle="Generate vouchers for mid-year bonus"
    :breadcrumbs="[
      { label: 'Payroll Module', to: '/' },
      { label: 'Payroll Reports', to: '/payroll-reports' },
      { label: 'Mid-Year Bonus Reports', to: '/mid-year-bonus-report-hub' },
      { label: 'Voucher' },
    ]"
  >
    <el-alert
      v-if="error"
      title="Error!"
      type="error"
      :closable="true"
      @close="error = null"
      class="mb-3"
    >
      {{ error }}
    </el-alert>

    <el-card class="box-card">
      <template #header>
        <div class="card-header">
          <span>Mid-Year Voucher Report</span>
        </div>
      </template>

      <el-form @submit.prevent="generateReport" label-width="140px">
        <el-form-item label="Department" required>
          <el-select
            v-model="formData.division_id"
            placeholder="Select Division"
            style="width: 100%"
            clearable
          >
            <el-option label="All Departments" value="all" />
            <el-option
              v-for="dept in departments"
              :key="dept.id"
              :label="dept.name"
              :value="dept.id"
            />
          </el-select>
        </el-form-item>

        <el-form-item label="Year" required>
          <el-select
            v-model="formData.years"
            placeholder="Select Year"
            style="width: 100%"
            clearable
          >
            <el-option
              v-for="year in years"
              :key="year.years || year.id || year"
              :label="year.years || year.name || year"
              :value="year.years || year.id || year"
            />
          </el-select>
        </el-form-item>

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
            type="primary"
            @click="generateReport"
            :loading="loading"
            :disabled="!formData.years"
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
import { reactive, ref, onMounted } from "vue";
import { useRouter } from "vue-router";
import { ElMessage } from "element-plus";
import { midYearBonusApi } from "../../services/api.js";
import PageScaffold from "../../components/PageScaffold.vue";

const router = useRouter();

const loading = ref(false);
const error = ref(null);
const divisions = ref([]);
const years = ref([]);
const employeeOptions = ref([]);

const formData = reactive({
  division_id: "",
  years: "",
});

const signatories = reactive({
  certifying_officer_name: "",
  certifying_officer_position: "",
  accountant_name: "",
  accountant_position: "",
  approving_officer_name: "",
  approving_officer_position: "",
});

const selectedCertifyingOfficerId = ref(null);
const selectedAccountantId = ref(null);
const selectedApprovingOfficerId = ref(null);

const goBack = () => {
  router.push("/mid-year-bonus-report-hub");
};

const loadInitialData = async () => {
  try {
    loading.value = true;
    error.value = null;

    // Use the same endpoint as ATM Mid-Year Bonus since it has the same data structure
    const response = await midYearBonusApi.getMidYearBonusData();
    const data = response.data.data;

    divisions.value = data.departments || [];
    employeeOptions.value = data.employee_options || [];

    // Generate years from current year back to 2000
    const currentYear = new Date().getFullYear();
    years.value = Array.from({ length: currentYear - 1999 }, (_, i) => ({
      years: currentYear - i,
      name: (currentYear - i).toString(),
    }));
  } catch (err) {
    error.value =
      err.response?.data?.message || err.message || "Failed to load data";
    ElMessage.error(error.value);
  } finally {
    loading.value = false;
  }
};

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

const generateReport = async () => {
  if (!formData.years) {
    ElMessage.warning("Please select Year");
    return;
  }

  try {
    loading.value = true;
    error.value = null;

    const requestData = {
      years: formData.years,
      certifying_officer_name: signatories.certifying_officer_name,
      certifying_officer_position: signatories.certifying_officer_position,
      accountant_name: signatories.accountant_name,
      accountant_position: signatories.accountant_position,
      approving_officer_name: signatories.approving_officer_name,
      approving_officer_position: signatories.approving_officer_position,
    };

    const response = await midYearBonusApi.generateDVReport(requestData);

    // Create download link for PDF
    const blob = new Blob([response.data], { type: "application/pdf" });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = url;
    link.download = `dv_midyear_${formData.years}_${
      new Date().toISOString().split("T")[0]
    }.pdf`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);

    ElMessage.success("Mid-Year Voucher Report generated successfully");
  } catch (err) {
    error.value =
      err.response?.data?.message || err.message || "Failed to generate report";
    ElMessage.error(error.value);
  } finally {
    loading.value = false;
  }
};

onMounted(async () => {
  await loadInitialData();
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
