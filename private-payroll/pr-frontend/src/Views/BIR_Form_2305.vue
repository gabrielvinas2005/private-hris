<template>
  <PageScaffold
    title="BIR FORM 2305"
    subtitle="Certificate of Update of Exemption and of Employer's and Employee's Information"
    :breadcrumbs="[
      { label: 'Payroll Module', to: '/' },
      { label: 'BIR Form 2305' },
    ]"
  >
    <el-alert
      v-if="hasErrors"
      title="Error!"
      type="error"
      :closable="true"
      @close="clearErrors"
      class="mb-3"
    >
      <ul>
        <li v-for="e in errors" :key="e">{{ e }}</li>
        <li v-if="backendError">{{ backendError }}</li>
      </ul>
    </el-alert>

    <el-card class="box-card">
      <template #header>
        <div class="card-header">
          <span>BIR Form 2305 Information</span>
        </div>
      </template>

      <el-form @submit.prevent="previewReport" label-width="200px">
        <div class="form-actions mb-3">
          <el-button
            type="success"
            @click="previewReport"
            :loading="loading"
            size="large"
            class="mr-3"
          >
            <el-icon><Document /></el-icon>
            Preview Form
          </el-button>
          <el-button
            type="primary"
            @click="generateReport"
            :loading="loading"
            size="large"
            class="mr-3"
          >
            <el-icon><Download /></el-icon>
            Download Form
          </el-button>
          <el-button
            type="success"
            @click="showFieldMapper = true"
            size="large"
            plain
          >
            Visual Field Mapper
          </el-button>
        </div>

        <el-divider />

        <!-- Employee Selection -->
        <h4>Employee Information</h4>
        <el-form-item label-width="0" class="signatory-form-item" required>
          <div class="signatory-field-wrapper">
            <label class="signatory-label">Employee: <span style="color: #f56c6c">*</span></label>
            <el-select
              v-model="formData.employee_id"
              placeholder="Select Employee"
              filterable
              style="width: 50%"
              clearable
              @change="handleEmployeeChange"
            >
              <el-option
                v-for="emp in employees"
                :key="emp.id"
                :label="emp.name"
                :value="emp.id"
              >
                <span>{{ emp.name }}</span>
                <span style="color: #8492a6; font-size: 13px; margin-left: 10px">
                  {{ emp.employee_no }} - {{ emp.position || "N/A" }}
                </span>
              </el-option>
            </el-select>
          </div>
        </el-form-item>

        <el-divider />

        <!-- Signatory Information -->
        <h4>Signatory Information</h4>
        <el-form-item label-width="0" class="signatory-form-item">
          <div class="signatory-field-wrapper">
            <label class="signatory-label">Signatory Name:</label>
            <el-input
              v-model="formData.signatory_name"
              placeholder="Enter signatory name"
              style="width: 50%; margin-left: 40px;"
            />
          </div>
        </el-form-item>
        <el-form-item label-width="0" class="signatory-form-item">
          <div class="signatory-field-wrapper">
            <label class="signatory-label">Signatory Title/Position:</label>
            <el-input
              v-model="formData.signatory_title"
              placeholder="Enter signatory title/position"
              style="width: 50%"
            />
          </div>
        </el-form-item>

        <div class="card-footer">
          <h6>BIR FORM 2305 - Certificate of Update of Exemption</h6>
        </div>
      </el-form>
    </el-card>

    <!-- PDF Field Mapper Dialog -->
    <PDFFieldMapper
      v-model="showFieldMapper"
      @saved="onMappingsSaved"
      :template-path="'/templates/BIR_Form_2305.pdf'"
      :storage-key="'bir_form_2305_mappings'"
      :form-type="'bir_form_2305'"
      :available-fields="birForm2305AvailableFields"
      :coordinates-config="birForm2305Coordinates"
      :get-coordinates-function="getBIRForm2305Coordinates"
      :sample-data-function="getBIRForm2305SampleData"
      :field-to-data-key="birForm2305FieldToDataKey"
    />
  </PageScaffold>
</template>

<script setup>
import { ref, onMounted, computed } from "vue";
import PageScaffold from "../components/PageScaffold.vue";
import PDFFieldMapper from "../components/PDFFieldMapper.vue";
import { Document, Download, User } from "@element-plus/icons-vue";
import { useBIRForm2305 } from "../Composables/useBIRForm2305.js";
import { ElMessage } from "element-plus";
import {
  birForm2305Coordinates,
  getBIRForm2305Coordinates,
} from "../config/pdfFieldCoordinates.js";

const {
  loading,
  error,
  formData,
  employees,
  company,
  loadInitialData,
  loadEmployeeDetails,
  previewReport,
  generateReport,
} = useBIRForm2305();

const errors = ref([]);
const backendError = computed(() => error.value);
const hasErrors = computed(
  () => errors.value.length > 0 || !!backendError.value
);
const showFieldMapper = ref(false);

// Computed property to get selected employee name
const selectedEmployeeName = computed(() => {
  if (!formData.employee_id) return null;
  const employee = employees.value.find(
    (emp) => emp.id === formData.employee_id
  );
  return employee?.name || null;
});

const clearErrors = () => {
  errors.value = [];
};

const handleEmployeeChange = async (employeeId) => {
  if (employeeId) {
    try {
      await loadEmployeeDetails(employeeId);
    } catch (err) {
      errors.value.push(err.message || "Failed to load employee details");
    }
  } else {
    loadEmployeeDetails(null);
  }
};

// Available fields for BIR Form 2305
const birForm2305AvailableFields = [
  { key: "tin_1", label: "TIN Part 1", page: 1 },
  { key: "tin_2", label: "TIN Part 2", page: 1 },
  { key: "tin_3", label: "TIN Part 3", page: 1 },
  { key: "rdo_code", label: "RDO Code", page: 1 },
  { key: "last_name", label: "Last Name", page: 1 },
  { key: "first_name", label: "First Name", page: 1 },
  { key: "middle_name", label: "Middle Name", page: 1 },
  { key: "date_of_birth_month", label: "DOB - Month", page: 1 },
  { key: "date_of_birth_day", label: "DOB - Day", page: 1 },
  { key: "date_of_birth_year", label: "DOB - Year", page: 1 },
  { key: "residence_address", label: "Residence Address", page: 1 },
  { key: "residence_zip", label: "Residence ZIP", page: 1 },
  { key: "business_address", label: "Business Address", page: 1 },
  { key: "business_zip", label: "Business ZIP", page: 1 },
  { key: "sex_male", label: "Sex: Male", page: 1 },
  { key: "sex_female", label: "Sex: Female", page: 1 },
  { key: "civil_status_single", label: "Civil Status: Single", page: 1 },
  { key: "civil_status_married", label: "Civil Status: Married", page: 1 },
  {
    key: "civil_status_legally_separated",
    label: "Civil Status: Legally Separated",
    page: 1,
  },
  { key: "civil_status_widow", label: "Civil Status: Widow/Widower", page: 1 },
  { key: "employer_tin_1", label: "Employer TIN Part 1", page: 1 },
  { key: "employer_tin_2", label: "Employer TIN Part 2", page: 1 },
  { key: "employer_tin_3", label: "Employer TIN Part 3", page: 1 },
  { key: "employer_rdo", label: "Employer RDO", page: 1 },
  { key: "employer_name", label: "Employer Name", page: 1 },
  { key: "employer_address", label: "Employer Address", page: 1 },
  { key: "employer_zip", label: "Employer ZIP", page: 1 },
  { key: "spouse_tin", label: "Spouse TIN", page: 1 },
  { key: "spouse_last_name", label: "Spouse Last Name", page: 1 },
  { key: "spouse_first_name", label: "Spouse First Name", page: 1 },
  { key: "spouse_middle_name", label: "Spouse Middle Name", page: 1 },
  { key: "dependent_1_last_name", label: "Dependent 1 Last Name", page: 1 },
  { key: "dependent_1_first_name", label: "Dependent 1 First Name", page: 1 },
  { key: "dependent_1_middle_name", label: "Dependent 1 Middle Name", page: 1 },
  { key: "dependent_1_birthdate", label: "Dependent 1 Birthdate", page: 1 },
  { key: "effective_date", label: "Effective Date", page: 1 },
  { key: "certification_date", label: "Certification Date", page: 1 },
  { key: "filer_employee", label: "Filer Type: Employee", page: 1 },
  { key: "filer_self_employed", label: "Filer Type: Self-Employed", page: 1 },
  { key: "signatory_name", label: "Signatory Name", page: 1 },
  { key: "signatory_title", label: "Signatory Title/Position", page: 1 },
];

// Field to data key mapping for preview
const birForm2305FieldToDataKey = {
  tin_1: "tin_no",
  tin_2: "tin_no",
  tin_3: "tin_no",
  rdo_code: "rdo_code",
  last_name: "last_name",
  first_name: "first_name",
  middle_name: "middle_name",
  date_of_birth_month: "date_of_birth",
  date_of_birth_day: "date_of_birth",
  date_of_birth_year: "date_of_birth",
  residence_address: "residence_address",
  residence_zip: "residence_zip",
  business_address: "business_address",
  business_zip: "business_zip",
  employer_tin_1: "employer_tin",
  employer_tin_2: "employer_tin",
  employer_tin_3: "employer_tin",
  employer_rdo: "employer_rdo",
  employer_name: "employer_name",
  employer_address: "employer_address",
  employer_zip: "employer_zip",
  spouse_tin: "spouse_tin",
  spouse_last_name: "spouse_last_name",
  spouse_first_name: "spouse_first_name",
  spouse_middle_name: "spouse_middle_name",
  dependent_1_last_name: "dependent_1_last_name",
  dependent_1_first_name: "dependent_1_first_name",
  dependent_1_middle_name: "dependent_1_middle_name",
  dependent_1_birthdate: "dependent_1_birthdate",
  effective_date: "effective_date",
  certification_date: "certification_date",
  signatory_name: "signatory_name",
  signatory_title: "signatory_title",
};

// Sample data function for preview
function getBIRForm2305SampleData() {
  return {
    tin_no: "123456789",
    rdo_code: "001",
    last_name: "DELA CRUZ",
    first_name: "JUAN",
    middle_name: "SANTOS",
    date_of_birth: "1990-01-15",
    sex: "male",
    civil_status: "married",
    filer_type: "employee",
    residence_address: "123 MAIN STREET, BRGY. SANTA CRUZ",
    residence_zip: "1000",
    business_address: "456 BUSINESS AVENUE",
    business_zip: "1001",
    employer_tin: "987654321000",
    employer_rdo: "002",
    employer_name: "ABC COMPANY INC.",
    employer_address: "789 EMPLOYER STREET",
    employer_zip: "1002",
    spouse_tin: "111222333444",
    spouse_last_name: "DELA CRUZ",
    spouse_first_name: "MARIA",
    spouse_middle_name: "REYES",
    dependent_1_last_name: "DELA CRUZ",
    dependent_1_first_name: "JANE",
    dependent_1_middle_name: "SANTOS",
    dependent_1_birthdate: "2020-01-01",
    effective_date: "2024-01-01",
    certification_date: "2024-01-15",
    signatory_name: "JUAN DELA CRUZ",
    signatory_title: "HR MANAGER",
  };
}

const onMappingsSaved = (mappings) => {
  ElMessage.success("Mappings saved!");
};

onMounted(async () => {
  try {
    await loadInitialData();
  } catch (err) {
    errors.value.push(err.message || "Failed to load initial data");
  }
});
</script>

<style scoped>
.mb-3 {
  margin-bottom: 1rem;
}
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
.form-actions {
  display: flex;
  justify-content: flex-start;
  align-items: center;
  padding: 1rem 0;
  gap: 1rem;
}
.card-footer {
  background-color: #f8f9fa;
  border-top: 1px solid #dee2e6;
  padding: 0.75rem 1.25rem;
  margin-top: 1rem;
}
.card-footer h6 {
  margin: 0;
  font-size: 0.875rem;
  color: #6c757d;
  font-weight: 500;
}
.mr-3 {
  margin-right: 1rem;
}
h4 {
  color: #333;
  font-weight: 500;
  margin-top: 1rem;
}
.signatory-form-item {
  display: flex;
  justify-content: center;
}
.signatory-field-wrapper {
  display: flex;
  align-items: center;
  gap: 12px;
  width: 100%;
  justify-content: center;
}
.signatory-label {
  font-size: 14px;
  color: #606266;
  white-space: nowrap;
}
</style>
