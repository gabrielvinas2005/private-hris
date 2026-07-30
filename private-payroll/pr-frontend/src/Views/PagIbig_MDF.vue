<template>
  <PageScaffold
    title="Pag-IBIG MDF"
    subtitle="Member's Data Form"
    :breadcrumbs="[
      { label: 'Payroll Module', to: '/' },
      { label: 'Pag-IBIG MDF' },
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
          <span>Pag-IBIG MDF Information</span>
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

        <!-- Signature of Informant -->
        <h4>Signature of Informant</h4>
        <el-form-item label-width="0" class="signatory-form-item">
          <div class="signatory-field-wrapper">
            <label class="signatory-label">Name:</label>
            <el-input
              v-model="formData.informant_signature"
              placeholder="Auto-filled with employee name"
              style="width: 50%; margin-left: 40px;"
              readonly
            />
          </div>
        </el-form-item>

        <el-divider />

        <!-- Processed By -->
        <h4>Processed By (FOR Pag-IBIG FUND USE ONLY)</h4>
        <el-form-item label-width="0" class="signatory-form-item">
          <div class="signatory-field-wrapper">
            <label class="signatory-label">Name:</label>
            <el-input
              v-model="formData.processed_by_name"
              placeholder="Enter processed by name"
              style="width: 50%; margin-left: 40px;"
            />
          </div>
        </el-form-item>
        <el-form-item label-width="0" class="signatory-form-item">
          <div class="signatory-field-wrapper">
            <label class="signatory-label">Position:</label>
            <el-input
              v-model="formData.processed_by_position"
              placeholder="Enter position/designation"
              style="width: 50%; margin-left: 40px;"
            />
          </div>
        </el-form-item>
        <el-form-item label-width="0" class="signatory-form-item">
          <div class="signatory-field-wrapper">
            <label class="signatory-label">Branch/Unit:</label>
            <el-input
              v-model="formData.processed_by_branch_unit"
              placeholder="Enter branch/unit"
              style="width: 50%; margin-left: 40px;"
            />
          </div>
        </el-form-item>

        <div class="card-footer">
          <h6>PAG-IBIG MEMBER'S DATA FORM</h6>
        </div>
      </el-form>
    </el-card>

    <!-- PDF Field Mapper Dialog -->
    <PDFFieldMapper
      v-model="showFieldMapper"
      @saved="onMappingsSaved"
      :template-path="'/templates/PagIbig_MDF.pdf'"
      :storage-key="'pagibig_mdf_mappings'"
      :form-type="'pagibig_mdf'"
      :available-fields="pagIbigMDFAvailableFields"
      :coordinates-config="pagIbigMDFCoordinates"
      :get-coordinates-function="getPagIbigMDFCoordinates"
      :sample-data-function="getPagIbigMDFSampleData"
      :field-to-data-key="pagIbigMDFFieldToDataKey"
    />
  </PageScaffold>
</template>

<script setup>
import { ref, onMounted, computed } from "vue";
import PageScaffold from "../components/PageScaffold.vue";
import PDFFieldMapper from "../components/PDFFieldMapper.vue";
import { Document, Download } from "@element-plus/icons-vue";
import { usePagIbigMDF } from "../Composables/usePagIbigMDF.js";
import { ElMessage } from "element-plus";
import {
  pagIbigMDFCoordinates,
  getPagIbigMDFCoordinates,
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
} = usePagIbigMDF();

const errors = ref([]);
const backendError = computed(() => error.value);
const hasErrors = computed(
  () => errors.value.length > 0 || !!backendError.value
);
const showFieldMapper = ref(false);

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

// Available fields for Pag-IBIG MDF
const pagIbigMDFAvailableFields = [
  { key: "member_last_name", label: "Member Last Name", page: 1 },
  { key: "member_first_name", label: "Member First Name", page: 1 },
  { key: "member_middle_name", label: "Member Middle Name", page: 1 },
  { key: "member_name_extension", label: "Member Name Extension", page: 1 },
  { key: "father_last_name", label: "Father Last Name", page: 1 },
  { key: "father_first_name", label: "Father First Name", page: 1 },
  { key: "father_middle_name", label: "Father Middle Name", page: 1 },
  { key: "mother_last_name", label: "Mother Last Name", page: 1 },
  { key: "mother_first_name", label: "Mother First Name", page: 1 },
  { key: "mother_middle_name", label: "Mother Middle Name", page: 1 },
  { key: "spouse_last_name", label: "Spouse Last Name", page: 1 },
  { key: "spouse_first_name", label: "Spouse First Name", page: 1 },
  { key: "spouse_middle_name", label: "Spouse Middle Name", page: 1 },
  { key: "date_of_birth_month", label: "DOB - Month", page: 1 },
  { key: "date_of_birth_day", label: "DOB - Day", page: 1 },
  { key: "date_of_birth_year", label: "DOB - Year", page: 1 },
  { key: "place_of_birth", label: "Place of Birth", page: 1 },
  { key: "sex_male", label: "Sex: Male", page: 1 },
  { key: "sex_female", label: "Sex: Female", page: 1 },
  { key: "marital_status_single", label: "Marital Status: Single", page: 1 },
  { key: "marital_status_married", label: "Marital Status: Married", page: 1 },
  {
    key: "marital_status_widow",
    label: "Marital Status: Widow/Widower",
    page: 1,
  },
  {
    key: "marital_status_annulled",
    label: "Marital Status: Annulled",
    page: 1,
  },
  {
    key: "marital_status_legally_separated",
    label: "Marital Status: Legally Separated",
    page: 1,
  },
  { key: "citizenship", label: "Citizenship", page: 1 },
  { key: "tin_1", label: "TIN Part 1", page: 1 },
  { key: "tin_2", label: "TIN Part 2", page: 1 },
  { key: "tin_3", label: "TIN Part 3", page: 1 },
  { key: "sss_no", label: "SSS Number", page: 1 },
  { key: "gsis_no", label: "GSIS Number", page: 1 },
  { key: "sss_gsis_no", label: "SSS/GSIS Number", page: 1 },
  { key: "employee_no", label: "Employee Number", page: 1 },
  { key: "height", label: "Height", page: 1 },
  { key: "weight", label: "Weight", page: 1 },
  { key: "mobile_no", label: "Mobile Number", page: 1 },
  { key: "email", label: "Email", page: 1 },
  {
    key: "permanent_address_unit_room_floor",
    label: "Permanent Address - Unit/Room/Floor",
    page: 1,
  },
  {
    key: "permanent_address_building_name",
    label: "Permanent Address - Building Name",
    page: 1,
  },
  {
    key: "permanent_address_lot_block_phase_house",
    label: "Permanent Address - Lot/Block/Phase/House No.",
    page: 1,
  },
  {
    key: "permanent_address_street_name",
    label: "Permanent Address - Street Name",
    page: 1,
  },
  {
    key: "permanent_address_subdivision",
    label: "Permanent Address - Subdivision",
    page: 1,
  },
  {
    key: "permanent_address_barangay",
    label: "Permanent Address - Barangay",
    page: 1,
  },
  {
    key: "permanent_address_municipality_city",
    label: "Permanent Address - Municipality/City",
    page: 1,
  },
  {
    key: "permanent_address_province_state_country",
    label: "Permanent Address - Province/State/Country",
    page: 1,
  },
  {
    key: "permanent_address_zip",
    label: "Permanent Address - ZIP Code",
    page: 1,
  },
  {
    key: "permanent_address",
    label: "Permanent Address (Legacy - Full)",
    page: 1,
  },
  {
    key: "present_address_unit_room_floor",
    label: "Present Address - Unit/Room/Floor",
    page: 1,
  },
  {
    key: "present_address_building_name",
    label: "Present Address - Building Name",
    page: 1,
  },
  {
    key: "present_address_lot_block_phase_house",
    label: "Present Address - Lot/Block/Phase/House No.",
    page: 1,
  },
  {
    key: "present_address_street_name",
    label: "Present Address - Street Name",
    page: 1,
  },
  {
    key: "present_address_subdivision",
    label: "Present Address - Subdivision",
    page: 1,
  },
  {
    key: "present_address_barangay",
    label: "Present Address - Barangay",
    page: 1,
  },
  {
    key: "present_address_municipality_city",
    label: "Present Address - Municipality/City",
    page: 1,
  },
  {
    key: "present_address_province_state_country",
    label: "Present Address - Province/State/Country",
    page: 1,
  },
  { key: "present_address_zip", label: "Present Address - ZIP Code", page: 1 },
  { key: "present_address", label: "Present Address (Legacy - Full)", page: 1 },
  { key: "employer_name", label: "Employer Name", page: 2 },
  { key: "employer_address", label: "Employer Address", page: 2 },
  { key: "employer_zip", label: "Employer ZIP", page: 2 },
  { key: "monthly_compensation", label: "Monthly Compensation", page: 2 },
  { key: "date_employed", label: "Date Employed", page: 2 },
  { key: "dependent_1_last_name", label: "Dependent 1 Last Name", page: 2 },
  { key: "dependent_1_first_name", label: "Dependent 1 First Name", page: 2 },
  { key: "dependent_1_middle_name", label: "Dependent 1 Middle Name", page: 2 },
  { key: "dependent_1_birthdate", label: "Dependent 1 Birthdate", page: 2 },
  { key: "informant_signature", label: "Signature of Informant", page: 2 },
  { key: "processed_by_name", label: "Processed By - Name", page: 2 },
  { key: "processed_by_position", label: "Processed By - Position", page: 2 },
  { key: "processed_by_branch_unit", label: "Processed By - Branch/Unit", page: 2 },
];

// Field to data key mapping for preview
const pagIbigMDFFieldToDataKey = {
  member_last_name: "last_name",
  member_first_name: "first_name",
  member_middle_name: "middle_name",
  member_name_extension: "name_extension",
  father_last_name: "father_last_name",
  father_first_name: "father_first_name",
  father_middle_name: "father_middle_name",
  mother_last_name: "mother_last_name",
  mother_first_name: "mother_first_name",
  mother_middle_name: "mother_middle_name",
  spouse_last_name: "spouse_last_name",
  spouse_first_name: "spouse_first_name",
  spouse_middle_name: "spouse_middle_name",
  date_of_birth_month: "date_of_birth",
  date_of_birth_day: "date_of_birth",
  date_of_birth_year: "date_of_birth",
  place_of_birth: "place_of_birth",
  citizenship: "citizenship",
  tin_1: "tin_no",
  tin_2: "tin_no",
  tin_3: "tin_no",
  sss_no: "sss_no",
  gsis_no: "gsis_no",
  sss_gsis_no: "sss_gsis_no",
  employee_no: "employee_no",
  height: "height",
  weight: "weight",
  mobile_no: "mobile_no",
  email: "email",
  permanent_address_unit_room_floor: "permanent_address_unit_room_floor",
  permanent_address_building_name: "permanent_address_building_name",
  permanent_address_lot_block_phase_house:
    "permanent_address_lot_block_phase_house",
  permanent_address_street_name: "permanent_address_street_name",
  permanent_address_subdivision: "permanent_address_subdivision",
  permanent_address_barangay: "permanent_address_barangay",
  permanent_address_municipality_city: "permanent_address_municipality_city",
  permanent_address_province_state_country:
    "permanent_address_province_state_country",
  permanent_address_zip: "permanent_address_zip",
  permanent_address: "permanent_address",
  present_address_unit_room_floor: "present_address_unit_room_floor",
  present_address_building_name: "present_address_building_name",
  present_address_lot_block_phase_house:
    "present_address_lot_block_phase_house",
  present_address_street_name: "present_address_street_name",
  present_address_subdivision: "present_address_subdivision",
  present_address_barangay: "present_address_barangay",
  present_address_municipality_city: "present_address_municipality_city",
  present_address_province_state_country:
    "present_address_province_state_country",
  present_address_zip: "present_address_zip",
  present_address: "present_address",
  employer_name: "employer_name",
  employer_address: "employer_address",
  employer_zip: "employer_zip",
  monthly_compensation: "monthly_compensation",
  date_employed: "date_employed",
  dependent_1_last_name: "dependent_1_last_name",
  dependent_1_first_name: "dependent_1_first_name",
  dependent_1_middle_name: "dependent_1_middle_name",
  dependent_1_birthdate: "dependent_1_birthdate",
  informant_signature: "informant_signature",
  processed_by_name: "processed_by_name",
  processed_by_position: "processed_by_position",
  processed_by_branch_unit: "processed_by_branch_unit",
};

// Sample data function for preview
function getPagIbigMDFSampleData() {
  return {
    last_name: "DELA CRUZ",
    first_name: "JUAN",
    middle_name: "SANTOS",
    name_extension: "Jr",
    date_of_birth: "1990-01-15",
    place_of_birth: "MANILA, PHILIPPINES",
    sex: "male",
    civil_status: "married",
    citizenship: "Filipino",
    tin_no: "123456789",
    sss_no: "1234567890",
    gsis_no: "12345678901",
    employee_no: "EMP001",
    height: "170",
    weight: "70",
    mobile_no: "09123456789",
    email: "juan.delacruz@example.com",
    permanent_address_unit_room_floor: "123",
    permanent_address_building_name: "312",
    permanent_address_lot_block_phase_house: "123",
    permanent_address_street_name: "MAIN STREET",
    permanent_address_subdivision: "SAMPLE",
    permanent_address_barangay: "BRGY. SANTA CRUZ",
    permanent_address_municipality_city: "MANILA",
    permanent_address_province_state_country: "NCR",
    permanent_address_zip: "1000",
    permanent_address: "123 MAIN STREET, BRGY. SANTA CRUZ, MANILA",
    present_address_unit_room_floor: "123",
    present_address_building_name: "312",
    present_address_lot_block_phase_house: "123",
    present_address_street_name: "MAIN STREET",
    present_address_subdivision: "SAMPLE1",
    present_address_barangay: "BRGY. SANTA ANA",
    present_address_municipality_city: "MANILA",
    present_address_province_state_country: "NCR",
    present_address_zip: "1000",
    present_address: "123 MAIN STREET, BRGY. SANTA CRUZ, MsANILA",
    employer_name: "ABC COMPANY INC.",
    employer_address: "789 EMPLOYER STREET",
    employer_zip: "1002",
    monthly_compensation: "50000",
    date_employed: "January, 2020",
    father_last_name: "DELA CRUZ",
    father_first_name: "JOSE",
    father_middle_name: "REYES",
    mother_last_name: "SANTOS",
    mother_first_name: "MARIA",
    mother_middle_name: "CRUZ",
    spouse_last_name: "DELA CRUZ",
    spouse_first_name: "MARIA",
    spouse_middle_name: "REYES",
    dependent_1_last_name: "DELA CRUZ",
    dependent_1_first_name: "JANE",
    dependent_1_middle_name: "SANTOS",
    dependent_1_birthdate: "01-01-2020",
    informant_signature: "JUAN SANTOS DELA CRUZ",
    processed_by_name: "MARIA SANTOS",
    processed_by_position: "HR OFFICER",
    processed_by_branch_unit: "MAIN BRANCH",
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
