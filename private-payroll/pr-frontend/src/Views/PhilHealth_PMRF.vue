<template>
  <PageScaffold
    title="PHILHEALTH PMRF"
    subtitle="PhilHealth Member Registration Form"
    :breadcrumbs="[
      { label: 'Payroll Module', to: '/' },
      { label: 'PhilHealth PMRF' },
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
          <span>PhilHealth PMRF Information</span>
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

        <!-- Purpose -->
        <el-form-item label-width="0" class="signatory-form-item" required>
          <div class="signatory-field-wrapper">
            <label class="signatory-label">Purpose: <span style="color: #f56c6c">*</span></label>
            <el-radio-group v-model="formData.purpose">
              <el-radio label="registration">Registration</el-radio>
              <el-radio label="updating">Updating/Amendment</el-radio>
            </el-radio-group>
          </div>
        </el-form-item>

        <!-- Additional Fields (Optional - can be filled manually) -->
        <el-form-item label-width="0" class="signatory-form-item">
          <div class="signatory-field-wrapper">
            <label class="signatory-label">Place of Birth:</label>
            <el-input
              v-model="formData.place_of_birth"
              placeholder="City/Municipality/Province/Country"
              style="width: 50%"
            />
          </div>
        </el-form-item>

        <el-form-item label-width="0" class="signatory-form-item">
          <div class="signatory-field-wrapper">
            <label class="signatory-label">Citizenship:</label>
            <el-select v-model="formData.citizenship" style="width: 50%">
              <el-option label="FILIPINO" value="FILIPINO" />
              <el-option label="DUAL CITIZEN" value="DUAL CITIZEN" />
              <el-option label="FOREIGN NATIONAL" value="FOREIGN NATIONAL" />
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
              style="width: 50%"
            />
          </div>
        </el-form-item>

        <div class="card-footer">
          <h6>PHILHEALTH MEMBER REGISTRATION FORM (PMRF)</h6>
          <p style="font-size: 12px; color: #666; margin-top: 5px">
            Select an employee to auto-populate the form. You can modify any
            fields before generating the PDF.
          </p>
        </div>
      </el-form>
    </el-card>

    <!-- PDF Field Mapper Dialog -->
    <PDFFieldMapper
      v-model="showFieldMapper"
      @saved="onMappingsSaved"
      :template-path="'/templates/philhealth_PMRF.pdf'"
      :storage-key="'philhealth_pmrf_mappings'"
      :form-type="'philhealth_pmrf'"
      :available-fields="philHealthAvailableFields"
      :coordinates-config="philHealthPMRFCoordinates"
      :get-coordinates-function="getFieldCoordinates"
      :sample-data-function="getPhilHealthSampleData"
      :field-to-data-key="philHealthFieldToDataKey"
    />
  </PageScaffold>
</template>

<script setup>
import { ref, onMounted, computed } from "vue";
import PageScaffold from "../components/PageScaffold.vue";
import PDFFieldMapper from "../components/PDFFieldMapper.vue";
import { Document, Download } from "@element-plus/icons-vue";
import { usePhilHealthPMRF } from "../Composables/usePhilHealthPMRF.js";
import { ElMessage } from "element-plus";
import {
  philHealthPMRFCoordinates,
  getFieldCoordinates,
} from "../config/pdfFieldCoordinates.js";

const {
  loading,
  error,
  formData,
  employees,
  loadInitialData,
  loadEmployeeDetails,
  previewReport,
  generateReport,
} = usePhilHealthPMRF();

const errors = ref([]);
const backendError = computed(() => error.value);
const hasErrors = computed(
  () => errors.value.length > 0 || !!backendError.value
);
const showFieldMapper = ref(false);

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

const clearErrors = () => {
  errors.value = [];
};

// Available fields for PhilHealth PMRF
const philHealthAvailableFields = [
  { key: "member_last_name", label: "Last Name", page: 1 },
  { key: "member_first_name", label: "First Name", page: 1 },
  { key: "member_middle_name", label: "Middle Name", page: 1 },
  { key: "member_name_extension", label: "Name Extension", page: 1 },
  { key: "mother_last_name", label: "Mother's Last Name", page: 1 },
  { key: "mother_first_name", label: "Mother's First Name", page: 1 },
  { key: "mother_middle_name", label: "Mother's Middle Name", page: 1 },
  { key: "spouse_last_name", label: "Spouse's Last Name", page: 1 },
  { key: "spouse_first_name", label: "Spouse's First Name", page: 1 },
  { key: "spouse_middle_name", label: "Spouse's Middle Name", page: 1 },
  { key: "date_of_birth_month", label: "DOB - Month", page: 1 },
  { key: "date_of_birth_day", label: "DOB - Day", page: 1 },
  { key: "date_of_birth_year", label: "DOB - Year", page: 1 },
  { key: "place_of_birth", label: "Place of Birth", page: 1 },
  { key: "philhealth_no", label: "PhilHealth No", page: 1 },
  { key: "tin_no", label: "TIN No", page: 1 },
  { key: "mobile_number", label: "Mobile Number", page: 1 },
  { key: "email", label: "Email", page: 1 },
  { key: "permanent_address", label: "Permanent Address", page: 1 },
  { key: "permanent_zip", label: "Permanent ZIP", page: 1 },
  { key: "mailing_address", label: "Mailing Address", page: 1 },
  { key: "mailing_zip", label: "Mailing ZIP", page: 1 },
  { key: "purpose_registration", label: "Purpose: Registration", page: 1 },
  { key: "purpose_updating", label: "Purpose: Updating", page: 1 },
  { key: "sex_male", label: "Sex: Male", page: 1 },
  { key: "sex_female", label: "Sex: Female", page: 1 },
  { key: "civil_status_single", label: "Civil Status: Single", page: 1 },
  { key: "civil_status_married", label: "Civil Status: Married", page: 1 },
  { key: "citizenship_filipino", label: "Citizenship: Filipino", page: 1 },
  { key: "citizenship_dual", label: "Citizenship: Dual", page: 1 },
  { key: "citizenship_foreign", label: "Citizenship: Foreign", page: 1 },
  { key: "child_name", label: "Child Name", page: 1 },
  { key: "child_birthdate", label: "Child Birthdate", page: 1 },
  { key: "child_middlename", label: "Child Middlename", page: 1 },
  { key: "child_lastname", label: "Child Lastname", page: 1 },
  { key: "signature_last_name", label: "Signatory Last Name", page: 2 },
  { key: "signature_first_name", label: "Signatory First Name", page: 2 },
  { key: "signature_middle_name", label: "Signatory Middle Name", page: 2 },
  {
    key: "signature_name_extension",
    label: "Signatory Name Extension",
    page: 2,
  },
  { key: "signatory_name", label: "Signatory Name", page: 2 },
];

// Field to data key mapping for preview
const philHealthFieldToDataKey = {
  member_last_name: "last_name",
  member_first_name: "first_name",
  member_middle_name: "middle_name",
  member_name_extension: "name_extension",
  place_of_birth: "place_of_birth",
  philhealth_no: "philhealth_no",
  tin_no: "tin_no",
  mobile_number: "mobile_number",
  email: "email",
  permanent_address: "permanent_address",
  permanent_zip: "permanent_zip",
  mailing_address: "mailing_address",
  mailing_zip: "mailing_zip",
  mother_last_name: "mother_last_name",
  mother_first_name: "mother_first_name",
  mother_middle_name: "mother_middle_name",
  spouse_last_name: "spouse_last_name",
  spouse_first_name: "spouse_first_name",
  spouse_middle_name: "spouse_middle_name",
  child_name: "child_name",
  child_birthdate: "child_birthdate",
  child_middlename: "child_middlename",
  child_lastname: "child_lastname",
  signature_last_name: "signature_last_name",
  signature_first_name: "signature_first_name",
  signature_middle_name: "signature_middle_name",
  signature_name_extension: "signature_name_extension",
  signatory_name: "signatory_name",
};

// Sample data function for preview
function getPhilHealthSampleData() {
  return {
    last_name: "DELA CRUZ",
    first_name: "JUAN",
    middle_name: "SANTOS",
    name_extension: "JR",
    date_of_birth: "01-15-1990",
    place_of_birth: "MANILA",
    philhealth_no: "12-345678901-2",
    tin_no: "123-456-789-000",
    mobile_number: "09123456789",
    email: "juan.delacruz@example.com",
    permanent_address: "123 MAIN STREET, BRGY. SANTA CRUZ",
    permanent_zip: "1000",
    mailing_address: "123 MAIN STREET, BRGY. SANTA CRUZ",
    mailing_zip: "1000",
    purpose: "registration",
    sex: "female",
    civil_status: "married",
    citizenship: "foreign",
    mother_last_name: "REYES",
    mother_first_name: "MARIA",
    mother_middle_name: "LOPEZ",
    spouse_last_name: "DELA CRUZ",
    spouse_first_name: "ANA",
    spouse_middle_name: "CRUZ",
    child_name: "JANE",
    child_birthdate: "01-01-2020",
    child_middlename: "DOE",
    child_lastname: "SMITH",
    signature_last_name: "DELA CRUZ",
    signature_first_name: "JUAN",
    signature_middle_name: "SANTOS",
    signature_name_extension: "JR",
    signatory_name: "JUAN DELA CRUZ",
  };
}

const onMappingsSaved = (mappings) => {
  ElMessage.success(
    "Mappings saved! Remember to copy the JSON and update pdfFieldCoordinates.js"
  );
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
