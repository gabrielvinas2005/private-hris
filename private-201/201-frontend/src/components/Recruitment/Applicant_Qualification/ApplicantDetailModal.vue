<template>
  <el-dialog
    :model-value="visible"
    :title="`Applicant Details - ${applicantInfo?.first_name || ''} ${applicantInfo?.last_name || ''}`"
    width="90%"
    :close-on-click-modal="false"
    @close="$emit('update:visible', false)"
    @update:model-value="$emit('update:visible', $event)"
  >
    <div v-if="applicantInfo" class="space-y-6">
      <!-- Applicant Profile Section -->
      <el-card shadow="never">
        <div class="flex items-start space-x-6">
          <el-avatar 
            :size="80" 
            :src="applicantInfo.photo ? `data:image/jpeg;base64,${applicantInfo.photo}` : null"
          >
            <i class="el-icon-user" />
          </el-avatar>
          <div class="flex-1">
            <h3 class="text-xl font-bold mb-2">{{ applicantInfo.first_name }} {{ applicantInfo.middle_name }} {{ applicantInfo.last_name }}</h3>
            <p class="text-gray-600 mb-4">APPLICANT</p>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <p><strong>Address:</strong> {{ applicantInfo.address || 'N/A' }}</p>
                <p><strong>Gender:</strong> {{ applicantInfo.gender || 'N/A' }}</p>
                <p><strong>Birth Date:</strong> {{ applicantInfo.birth_date || 'N/A' }}</p>
              </div>
              <div>
                <p><strong>Age:</strong> {{ applicantInfo.age || 'N/A' }}</p>
                <p><strong>Mobile No.:</strong> {{ applicantInfo.mobile_no || 'N/A' }}</p>
                <p><strong>Email:</strong> {{ applicantInfo.email || 'N/A' }}</p>
              </div>
            </div>
          </div>
        </div>
      </el-card>

      <!-- EETE Rating Section -->
      <el-card shadow="never">
        <template #header>
          <div class="flex justify-between items-center">
            <span class="font-bold">EETE Rating</span>
            <el-button type="primary" size="small" text @click="openEeteSetup">View EETE Rating Setup</el-button>
          </div>
        </template>
        
        <p class="text-sm text-gray-500 mb-3">
          Set each category as <strong>Passed</strong> or <strong>Failed</strong> based on EETE criteria.
        </p>
        <el-form :model="eeteForm" label-width="120px" class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <el-form-item label="Education: *">
              <el-select v-model="eeteForm.education" placeholder="Select" style="width: 100%">
                <el-option label="Passed" value="passed" />
                <el-option label="Failed" value="failed" />
              </el-select>
            </el-form-item>
            <el-form-item label="Experience: *">
              <el-select v-model="eeteForm.experience" placeholder="Select" style="width: 100%">
                <el-option label="Passed" value="passed" />
                <el-option label="Failed" value="failed" />
              </el-select>
            </el-form-item>
            <el-form-item label="Training: *">
              <el-select v-model="eeteForm.training" placeholder="Select" style="width: 100%">
                <el-option label="Passed" value="passed" />
                <el-option label="Failed" value="failed" />
              </el-select>
            </el-form-item>
            <el-form-item label="Eligibility: *">
              <el-select v-model="eeteForm.eligibility" placeholder="Select" style="width: 100%">
                <el-option label="Passed" value="passed" />
                <el-option label="Failed" value="failed" />
              </el-select>
            </el-form-item>
          </div>
        </el-form>
      </el-card>

      <!-- Update Applicant Status Section -->
      <el-card shadow="never">
        <template #header>
          <span class="font-bold">Update Applicant Status</span>
        </template>
        
        <el-form :model="statusForm" label-width="150px">
          <el-form-item label="Status:">
            <el-select v-model="statusForm.status" placeholder="Select Status" style="width: 200px">
              <el-option label="Qualified" value="qualified" />
              <el-option label="For Reference" value="for_reference" />
            </el-select>
          </el-form-item>
        </el-form>
      </el-card>

      <!-- PDS (Personal Data Sheet) Section -->
      <el-card shadow="never">
        <template #header>
          <span class="font-bold text-lg">Applicant Personal Data Sheet</span>
        </template>
        
        <ApplicantPDSDisplay 
          :applicant-info="applicantInfo" 
          :pds-data="pdsForm" 
        />

        <!-- Bottom Navigation Tabs -->
          <div class="pds-bottom-tabs">
            <el-tabs v-model="activePdsTab" class="pds-navigation-tabs">
              <!-- Family Tab -->
              <el-tab-pane label="Family" name="family">
                <div class="tab-content">
                  <h3 class="tab-title">Family Information</h3>
                  <el-descriptions title="Parents & Spouse" border size="small" class="mb-3">
                    <el-descriptions-item label="Father's Name">
                      {{ [pdsForm.father_first_name, pdsForm.father_middle_name, pdsForm.father_last_name].filter(Boolean).join(' ') || 'N/A' }}
                    </el-descriptions-item>
                    <el-descriptions-item label="Mother's Maiden Name">
                      {{ [pdsForm.mother_first_name, pdsForm.mother_middle_name, pdsForm.mother_surname].filter(Boolean).join(' ') || 'N/A' }}
                    </el-descriptions-item>
                    <el-descriptions-item label="Spouse Name">
                      {{ [pdsForm.spouse_first_name, pdsForm.spouse_middle_name, pdsForm.spouse_last_name].filter(Boolean).join(' ') || 'N/A' }}
                    </el-descriptions-item>
                    <el-descriptions-item label="Spouse Occupation">
                      {{ pdsForm.spouse_occupation || 'N/A' }}
                    </el-descriptions-item>
                    <el-descriptions-item label="Spouse Employer">
                      {{ pdsForm.spouse_employer || 'N/A' }}
                    </el-descriptions-item>
                  </el-descriptions>

                  <h4 class="tab-subtitle">Children</h4>
                  <el-table
                    :data="pdsForm.children || []"
                    size="small"
                    stripe
                    empty-text="No children recorded"
                  >
                    <el-table-column prop="child_name" label="Name" />
                    <el-table-column prop="child_birthdate" label="Birthdate" width="140" />
                  </el-table>
                </div>
              </el-tab-pane>

              <!-- Education Tab -->
              <el-tab-pane label="Education" name="education">
                <div class="tab-content">
                  <h3 class="tab-title">Educational Background</h3>
                  <el-table
                    :data="pdsForm.education || []"
                    size="small"
                    stripe
                    empty-text="No educational background recorded"
                  >
                    <el-table-column prop="school_name" label="School / Institution" />
                    <el-table-column label="Degree / Course" min-width="160" show-overflow-tooltip>
                      <template #default="{ row }">
                        {{ row.program ?? row.degree ?? '' }}
                      </template>
                    </el-table-column>
                    <el-table-column prop="from" label="From" width="120" />
                    <el-table-column prop="to" label="To" width="120" />
                    <el-table-column prop="graduated_year" label="Year Graduated" width="140" />
                    <el-table-column label="Honors / Awards" min-width="140" show-overflow-tooltip>
                      <template #default="{ row }">
                        {{ row.honors ?? row.honors_received ?? '' }}
                      </template>
                    </el-table-column>
                  </el-table>
                </div>
              </el-tab-pane>

              <!-- Work Experience Tab -->
              <el-tab-pane label="Work Experience" name="work_experience">
                <div class="tab-content">
                  <h3 class="tab-title">Work Experience</h3>
                  <el-table
                    :data="pdsForm.work_experience || []"
                    size="small"
                    stripe
                    empty-text="No work experience recorded"
                  >
                    <el-table-column prop="position" label="Position" min-width="160" />
                    <el-table-column prop="work_company" label="Company / Agency" min-width="180" />
                    <el-table-column prop="work_start_date" label="From" width="120" />
                    <el-table-column prop="work_end_date" label="To" width="120" />
                    <el-table-column prop="duration" label="Duration" width="120" />
                  </el-table>
                </div>
              </el-tab-pane>

              <!-- Eligibility Tab -->
              <el-tab-pane label="Eligibility" name="eligibility">
                <div class="tab-content">
                  <h3 class="tab-title">Civil Service Eligibility</h3>
                  <el-table
                    :data="pdsForm.eligibility || []"
                    size="small"
                    stripe
                    empty-text="No eligibility examinations recorded"
                  >
                    <el-table-column label="Eligibility">
                      <template #default="{ row }">
                        {{ row.eligibility_description || row.eligibility || '' }}
                      </template>
                    </el-table-column>
                    <el-table-column prop="exam_rating" label="Rating" width="120" />
                    <el-table-column prop="exam_date" label="Date of Exam" width="160" />
                    <el-table-column prop="place_of_exam" label="Place of Examination" />
                    <el-table-column prop="license_number" label="License No." width="140" />
                    <el-table-column prop="date_released" label="Date Released" width="160" />
                  </el-table>
                </div>
              </el-tab-pane>

              <!-- Trainings Tab -->
              <el-tab-pane label="Trainings" name="trainings">
                <div class="tab-content">
                  <h3 class="tab-title">Training Programs</h3>
                  <el-table :data="pdsForm.trainings || []" size="small" stripe>
                    <el-table-column prop="training" label="Title of Training" />
                    <el-table-column prop="training_from" label="From" width="120" />
                    <el-table-column prop="training_to" label="To" width="120" />
                    <el-table-column prop="hours" label="Number of Hours" width="120" />
                    <el-table-column prop="sponsored_by" label="Conducted/Sponsored By" />
                    <el-table-column prop="learning" label="Type of LD" width="100" />
                  </el-table>
                </div>
              </el-tab-pane>

              <!-- Voluntary Work Tab -->
              <el-tab-pane label="Voluntary Work" name="voluntary_work">
                <div class="tab-content">
                  <h3 class="tab-title">Voluntary Work or Involvement</h3>
                  <el-table
                    :data="pdsForm.voluntary_work || []"
                    size="small"
                    stripe
                    empty-text="No voluntary work recorded"
                  >
                    <el-table-column prop="organization" label="Organization" />
                    <el-table-column prop="org_position" label="Position / Role" />
                    <el-table-column prop="org_from" label="From" width="120" />
                    <el-table-column prop="org_to" label="To" width="120" />
                    <el-table-column prop="org_hours" label="Hours" width="100" />
                  </el-table>
                </div>
              </el-tab-pane>

              <!-- Recognitions Tab -->
              <el-tab-pane label="Recognitions" name="recognitions">
                <div class="tab-content">
                  <h3 class="tab-title">Special Skills and Recognitions</h3>
                  <el-table
                    :data="pdsForm.recognitions || []"
                    size="small"
                    stripe
                    empty-text="No recognitions recorded"
                  >
                    <el-table-column prop="recognation" label="Recognition" />
                  </el-table>
                </div>
              </el-tab-pane>

              <!-- Skills Tab -->
              <el-tab-pane label="Skills" name="skills">
                <div class="tab-content">
                  <h3 class="tab-title">Skills and Competencies</h3>
                  <el-table
                    :data="pdsForm.skills || []"
                    size="small"
                    stripe
                    empty-text="No skills recorded"
                  >
                    <el-table-column prop="skill" label="Skill" />
                    <el-table-column prop="description" label="Description" />
                    <el-table-column prop="level" label="Level" width="120" />
                  </el-table>
                </div>
              </el-tab-pane>

              <!-- Memberships Tab -->
              <el-tab-pane label="Memberships" name="memberships">
                <div class="tab-content">
                  <h3 class="tab-title">Professional Memberships</h3>
                  <el-table
                    :data="pdsForm.memberships || []"
                    size="small"
                    stripe
                    empty-text="No memberships recorded"
                  >
                    <el-table-column prop="membership" label="Membership" />
                  </el-table>
                </div>
              </el-tab-pane>

              <!-- References Tab -->
              <el-tab-pane label="References" name="references">
                <div class="tab-content">
                  <h3 class="tab-title">Character References</h3>
                  <el-table
                    :data="pdsForm.references || []"
                    size="small"
                    stripe
                    empty-text="No references recorded"
                  >
                    <el-table-column prop="ref_name" label="Name" min-width="220" />
                    <el-table-column prop="ref_occupation" label="Position" min-width="220" />
                    <el-table-column prop="ref_address" label="Address" min-width="220" />
                    <el-table-column prop="ref_contact_no" label="Contact No." min-width="220" />
                    <el-table-column label="Email" min-width="220" align="right" header-align="right">
                      <template #default="{ row }">
                        <span class="ref-email">{{ row.ref_email }}</span>
                      </template>
                    </el-table-column>
                  </el-table>
                </div>
              </el-tab-pane>

              <!-- Dependents Tab -->
              <el-tab-pane label="Dependents" name="dependents">
                <div class="tab-content">
                  <h3 class="tab-title">Dependents Information</h3>
                  <el-table
                    :data="pdsForm.dependents || []"
                    size="small"
                    stripe
                    empty-text="No dependents recorded"
                  >
                    <el-table-column prop="name" label="Name" />
                    <el-table-column prop="relationship" label="Relationship" />
                    <el-table-column prop="birthdate" label="Birthdate" width="140" />
                  </el-table>
                </div>
              </el-tab-pane>

              <!-- Documents Tab -->
              <el-tab-pane label="Documents" name="documents">
                <div class="tab-content">
                  <h3 class="tab-title">Supporting Documents</h3>
                  <el-table
                    :data="pdsForm.documents || []"
                    size="small"
                    stripe
                    empty-text="No documents recorded"
                  >
                    <el-table-column label="Type">
                      <template #default="{ row }">
                        <!-- Attachments DB uses `name` for the document type/label -->
                        <span>{{ row?.document_type || row?.document_type_name || row?.name || 'N/A' }}</span>
                      </template>
                    </el-table-column>
                    <el-table-column label="Description">
                      <template #default="{ row }">
                        <span>{{ row?.description || 'No description' }}</span>
                      </template>
                    </el-table-column>
                    <el-table-column label="File Name" width="200">
                      <template #default="{ row }">
                        <el-button 
                          v-if="row.attachment_name && row.employee_document_id" 
                          type="text" 
                          size="small" 
                          @click="openDocumentModal(row)"
                        >
                          {{ row.attachment_name }}
                        </el-button>
                        <span v-else class="text-gray-400">N/A</span>
                      </template>
                    </el-table-column>
                  </el-table>
                </div>
              </el-tab-pane>

              <!-- PDS Questionnaires Tab -->
              <el-tab-pane label="PDS Questionnaires" name="pds_questionnaires">
                <div class="tab-content">
                  <h3 class="tab-title">PDS Questionnaires</h3>
                  <el-table
                    :data="pdsForm.pds_questionnaires || []"
                    size="small"
                    stripe
                    empty-text="No questionnaire responses recorded"
                  >
                    <el-table-column prop="questions" label="Question" min-width="260" />
                    <el-table-column prop="is_yes" label="Yes" width="80" align="center">
                      <template #default="{ row }">
                        <input
                          type="radio"
                          :checked="row.is_yes == 1 || row.is_yes == '1' || row.is_yes === true"
                          disabled
                          class="pds-radio"
                        />
                      </template>
                    </el-table-column>
                    <el-table-column prop="is_no" label="No" width="80" align="center">
                      <template #default="{ row }">
                        <input
                          type="radio"
                          :checked="row.is_no == 1 || row.is_no == '1' || row.is_no === true"
                          disabled
                          class="pds-radio"
                        />
                      </template>
                    </el-table-column>
                    <el-table-column prop="yes_details" label="Details" />
                  </el-table>
                </div>
              </el-tab-pane>
            </el-tabs>
          </div>
      </el-card>
    </div>

    <template #footer>
      <div class="flex justify-end space-x-2">
        <el-button @click="$emit('update:visible', false)">Close</el-button>
        <el-button type="primary" :loading="saving" @click="saveReview">Save Review</el-button>
      </div>
    </template>
  </el-dialog>

  <!-- EETE Rating Setup dialog -->
  <el-dialog
    v-model="eeteSetupDialogVisible"
    title="EETE Rating Setup"
    width="500px"
    destroy-on-close
  >
    <div v-loading="eeteSetupLoading">
      <p v-if="!eeteSetupData.length && !eeteSetupLoading" class="text-gray-500">No EETE rating configuration found.</p>
      <el-table v-else :data="eeteSetupData" border stripe>
        <el-table-column prop="education_rating" label="Education (max)" width="120" align="center" />
        <el-table-column prop="experience_rating" label="Experience (max)" width="120" align="center" />
        <el-table-column prop="training_rating" label="Training (max)" width="120" align="center" />
        <el-table-column prop="eligibility_rating" label="Eligibility (max)" width="120" align="center" />
      </el-table>
      <p class="mt-3 text-sm text-gray-500">Each category is scored 0–100. Total maximum is 400.</p>
    </div>
  </el-dialog>

  <!-- Document Preview Modal -->
  <el-dialog
    v-model="showDocumentModal"
    :title="`Document: ${currentDocument?.attachment_name || 'Preview'}`"
    width="90%"
    :close-on-click-modal="false"
    destroy-on-close
  >
    <div v-if="documentError" style="text-align: center; padding: 40px; background-color: #fef0f0; border: 1px solid #f56c6c; border-radius: 4px;">
      <el-icon :size="64" style="color: #f56c6c; margin-bottom: 20px;">
        <Warning />
      </el-icon>
      <p style="color: #606266; margin-bottom: 20px;">
        Failed to load document preview. Please ensure the file exists and is accessible.
      </p>
      <el-button type="primary" @click="downloadDocument">
        Download File Instead
      </el-button>
    </div>
    <div v-else-if="documentLoading" style="text-align: center; padding: 40px; background-color: #f0f2f5; border-radius: 4px;">
      <el-icon class="is-loading" :size="48" style="color: #409eff;">
        <Loading />
      </el-icon>
      <p style="margin-top: 20px; color: #909399;">Loading preview...</p>
    </div>
    <div v-else-if="documentPreviewUrl" style="text-align: center; min-height: 500px; background-color: #f0f2f5; border-radius: 4px;">
      <!-- PDF Preview -->
      <iframe
        v-if="documentFileType === 'pdf'"
        :src="documentPreviewUrl"
        style="width: 100%; height: 70vh; border: none;"
        frameborder="0"
        @error="documentError = true"
      />
      <!-- Image Preview -->
      <img
        v-else-if="documentFileType === 'image'"
        :src="documentPreviewUrl"
        style="max-width: 100%; max-height: 70vh; object-fit: contain;"
        alt="Document Preview"
        @error="documentError = true"
      />
      <!-- Excel files - show download link -->
      <div v-else-if="documentFileType === 'excel'" style="padding: 40px;">
        <el-icon :size="64" style="color: #67C23A; margin-bottom: 20px;">
          <Document />
        </el-icon>
        <p style="color: #606266; margin-bottom: 10px; font-weight: 600;">
          Excel File
        </p>
        <p style="color: #909399; margin-bottom: 20px; font-size: 14px;">
          Excel files cannot be previewed directly in the browser.
        </p>
        <el-button type="success" @click="downloadDocument">
          <el-icon class="mr-1"><Download /></el-icon>
          Download Excel File
        </el-button>
      </div>
      <!-- Other file types - show download link -->
      <div v-else style="padding: 40px;">
        <el-icon :size="64" style="color: #909399; margin-bottom: 20px;">
          <Document />
        </el-icon>
        <p style="color: #606266; margin-bottom: 20px;">
          This file type cannot be previewed in the browser.
        </p>
        <el-button type="primary" @click="downloadDocument">
          Download File
        </el-button>
      </div>
    </div>
    <template #footer>
      <el-button @click="showDocumentModal = false">Close</el-button>
      <el-button 
        v-if="currentDocument?.employee_document_id && documentFileType !== 'other'" 
        type="primary" 
        @click="downloadDocument"
      >
        Download
      </el-button>
    </template>
  </el-dialog>
</template>

<script setup>
import { ref, reactive, watch, defineProps, defineEmits } from 'vue'
import { ElMessage } from 'element-plus'
import { adminSelectApi, eeteRatingSetupApi, applicantQualificationApi } from '@/services/api'
import api from '@/services/api'
import { Document, Download, Loading, Warning } from '@element-plus/icons-vue'
import ApplicantPDSDisplay from './ApplicantPDSDisplay.vue'

const props = defineProps({
  visible: { type: Boolean, default: false },
  applicantInfo: { type: Object, default: () => null },
  saving: { type: Boolean, default: false }
})

const emit = defineEmits(['update:visible', 'save-review'])

const activePdsTab = ref('family')
const pdsLoading = ref(false)

// EETE Rating Setup dialog
const eeteSetupDialogVisible = ref(false)
const eeteSetupLoading = ref(false)
const eeteSetupData = ref([])

async function openEeteSetup() {
  eeteSetupDialogVisible.value = true
  eeteSetupLoading.value = true
  eeteSetupData.value = []
  try {
    const res = await eeteRatingSetupApi.getSetup()
    const payload = res?.data?.data ?? res?.data ?? res
    const list = Array.isArray(payload) ? payload : (payload ? [payload] : [])
    eeteSetupData.value = list
  } catch (e) {
    ElMessage.error(e?.response?.data?.message || 'Failed to load EETE Rating Setup')
    eeteSetupData.value = []
  } finally {
    eeteSetupLoading.value = false
  }
}

// EETE Rating Form (dropdown: passed / failed per category)
const eeteForm = reactive({
  education: null,
  experience: null,
  training: null,
  eligibility: null
})

// Status Form
const statusForm = reactive({
  status: 'qualified'
})

// Helper to create an empty PDS object
const createEmptyPds = () => ({
  employee_id: null,
  applicant_id: '',
  prefix: '',
  first_name: '',
  middle_name: '',
  last_name: '',
  suffix: '',
  birth_place: '',
  gender: '',
  birth_date: '',
  civil_status: '',
  age: null,
  citizenship: '',
  height: 0,
  religion: '',
  weight: 0,
  blood_type: '',
  email: '',
  mobile_no: '',
  telephone_no: '',
  tin_no: '',
  gsis_no: '',
  sss_no: '',
  hdmf_premium_no: '',
  philhealth_no: '',
  current_region: '',
  current_province: '',
  current_municipality: '',
  current_barangay: '',
  current_house_no: '',
  current_street: '',
  current_village: '',
  permanent_region: '',
  permanent_province: '',
  permanent_municipality: '',
  permanent_barangay: '',
  permanent_house_no: '',
  permanent_street: '',
  permanent_village: '',
  has_dual_citizenship: false,
  dual_citizenship_type: 'by_birth',
  dual_citizenship_country: '',
  // Family information
  father_prefix: '',
  father_first_name: '',
  father_middle_name: '',
  father_last_name: '',
  father_suffix: '',
  mother_prefix: '',
  mother_first_name: '',
  mother_middle_name: '',
  mother_surname: '',
  mother_suffix: '',
  spouse_prefix: '',
  spouse_first_name: '',
  spouse_middle_name: '',
  spouse_last_name: '',
  spouse_suffix: '',
  spouse_occupation: '',
  spouse_employer: '',
  spouse_work_address: '',
  children: [],
  // Tab data arrays
  education: [],
  work_experience: [],
  eligibility: [],
  trainings: [],
  voluntary_work: [],
  recognitions: [],
  skills: [],
  memberships: [],
  references: [],
  dependents: [],
  documents: [],
  pds_questionnaires: [],
  // PDS questionnaires (flags)
  convicted_crime: 'no',
  crime_details: '',
  separated_service: 'no',
  separation_details: '',
  administrative_charge: 'no',
  charge_details: ''
})

// PDS Form (backed by API data)
const pdsForm = reactive(createEmptyPds())

// Utility to build applicant full name
const fullName = (first, middle, last) => {
  return [first, middle, last].filter(Boolean).join(' ')
}

const getLookupNameById = (options, id) => {
  const items = Array.isArray(options) ? options : []
  const targetId = Number(id)
  if (!targetId) return ''
  const match = items.find((item) => Number(item?.id) === targetId)
  return match?.name || ''
}

// Load detailed PDS-style info for the selected applicant (reuse Administrator Selection PDS API)
const loadApplicantPds = async () => {
  const src = props.applicantInfo || {}
  const applicantId =
    src.id ??
    src.applicant_id ??
    src.applicantId ??
    src.ID

  if (!applicantId) {
    return
  }

  pdsLoading.value = true
  try {
    // Prefer ApplicantHiringController::Info payload (includes attachments DB documents)
    const { data } = await applicantQualificationApi.getApplicantInfo(applicantId)
    const payload = data?.data || data || {}

    const employee = Array.isArray(payload.employee_info)
      ? (payload.employee_info[0] || {})
      : (payload.employee_info || {})

    // Basic identity - store employee_id for document access
    pdsForm.employee_id = employee.id || null
    pdsForm.applicant_id = employee.employee_no || src.applicant_no || `APP-${employee.id || applicantId}`
    pdsForm.first_name = employee.first_name || src.first_name || ''
    pdsForm.middle_name = employee.middle_name || src.middle_name || ''
    pdsForm.last_name = employee.last_name || src.last_name || ''
    pdsForm.gender = employee.gender || ''
    pdsForm.birth_date = employee.birthdate || src.birth_date || ''
    pdsForm.age = employee.age || src.age || null
    pdsForm.birth_place = employee.birth_place || ''
    pdsForm.civil_status =
      employee.civil_status ||
      src.civil_status ||
      getLookupNameById(payload.civil_status, employee.civil_status_id || src.civil_status_id)
    pdsForm.citizenship =
      employee.citizenship ||
      src.citizenship ||
      getLookupNameById(payload.citizenships, employee.citizenship_id || src.citizenship_id)
    pdsForm.religion = employee.religion || ''
    pdsForm.blood_type = employee.blood_type || ''
    pdsForm.height = employee.height || 0
    pdsForm.weight = employee.weight || 0

    // Contact
    pdsForm.email = employee.email || src.email || ''
    pdsForm.mobile_no = employee.mobile_no || src.mobile_no || ''
    pdsForm.telephone_no = employee.telephone_no || ''

    // Government IDs
    pdsForm.tin_no = employee.tin_no || ''
    pdsForm.gsis_no = employee.gsis_no || ''
    pdsForm.sss_no = employee.sss_no || ''
    pdsForm.hdmf_premium_no = employee.pagibig_no || ''
    pdsForm.philhealth_no = employee.philhealth_no || ''

    // Address mapping (current = RA, permanent = PA)
    pdsForm.current_region = employee.ra_region || ''
    pdsForm.current_province = employee.ra_province || ''
    pdsForm.current_municipality = employee.ra_city || ''
    pdsForm.current_barangay = employee.ra_barangay || ''
    pdsForm.current_house_no = employee.ra_house_no || ''
    pdsForm.current_street = employee.ra_street || ''
    pdsForm.current_village = employee.ra_village || ''

    pdsForm.permanent_region = employee.pa_region || ''
    pdsForm.permanent_province = employee.pa_province || ''
    pdsForm.permanent_municipality = employee.pa_city || ''
    pdsForm.permanent_barangay = employee.pa_barangay || ''
    pdsForm.permanent_house_no = employee.pa_house_no || ''
    pdsForm.permanent_street = employee.pa_street || ''
    pdsForm.permanent_village = employee.pa_village || ''

    // Family
    pdsForm.father_first_name = employee.father_first_name || ''
    pdsForm.father_middle_name = employee.father_middle_name || ''
    pdsForm.father_last_name = employee.father_last_name || ''

    pdsForm.mother_first_name = employee.mother_first_name || ''
    pdsForm.mother_middle_name = employee.mother_middle_name || ''
    pdsForm.mother_surname = employee.mother_last_name || ''

    pdsForm.spouse_first_name = employee.spouse_first_name || ''
    pdsForm.spouse_middle_name = employee.spouse_middle_name || ''
    pdsForm.spouse_last_name = employee.spouse_last_name || ''
    pdsForm.spouse_occupation = employee.spouse_occupation || ''
    pdsForm.spouse_employer = employee.spouse_employer || ''
    pdsForm.spouse_work_address = employee.spouse_business_address || ''

    // Arrays for each tab (fallback to empty arrays)
    pdsForm.children = Array.isArray(payload.children) ? payload.children : []
    pdsForm.education = Array.isArray(payload.educations) ? payload.educations : []
    pdsForm.work_experience = Array.isArray(payload.employments) ? payload.employments : []
    pdsForm.eligibility = Array.isArray(payload.examinations) ? payload.examinations : []
    pdsForm.trainings = Array.isArray(payload.trainings) ? payload.trainings : []
    pdsForm.voluntary_work = Array.isArray(payload.organizations) ? payload.organizations : []
    pdsForm.recognitions = Array.isArray(payload.recognitions) ? payload.recognitions : []
    pdsForm.skills = Array.isArray(payload.skills) ? payload.skills : []
    pdsForm.memberships = Array.isArray(payload.memberships) ? payload.memberships : []
    pdsForm.references = Array.isArray(payload.references) ? payload.references : []
    pdsForm.dependents = Array.isArray(payload.dependents) ? payload.dependents : []
    pdsForm.documents = Array.isArray(payload.documents) ? payload.documents : []
    pdsForm.pds_questionnaires = Array.isArray(payload.quesionaires) ? payload.quesionaires : []
  } catch (error) {
    // eslint-disable-next-line no-console
    console.error('Failed to load applicant PDS', error)
    ElMessage.error('Failed to load applicant PDS details.')
  } finally {
    pdsLoading.value = false
  }
}

// Watch for applicant info changes to populate basic fields + EETE ratings
watch(() => props.applicantInfo, (newInfo) => {
  if (newInfo) {
    // Basic visible info for the top card
    pdsForm.applicant_id = newInfo.applicant_id || pdsForm.applicant_id || `APP-${newInfo.id || ''}`
    pdsForm.first_name = newInfo.first_name || pdsForm.first_name
    pdsForm.middle_name = newInfo.middle_name || pdsForm.middle_name
    pdsForm.last_name = newInfo.last_name || pdsForm.last_name
    // Don't override gender from employee data - it comes from employees table via loadApplicantPds
    // pdsForm.gender = newInfo.gender || pdsForm.gender
    pdsForm.birth_date = newInfo.birth_date || pdsForm.birth_date
    pdsForm.age = newInfo.age || pdsForm.age
    pdsForm.email = newInfo.email || pdsForm.email
    pdsForm.mobile_no = newInfo.mobile_no || pdsForm.mobile_no

    // Initialize EETE dropdowns from pass flags (with numeric fallback)
    const toPassFail = (flag, rating) => {
      if (flag === 1 || flag === '1' || flag === true) return 'passed'
      if (flag === 0 || flag === '0' || flag === false) return 'failed'
      if (rating !== null && rating !== undefined && rating !== '') {
        const num = Number(rating)
        if (!Number.isNaN(num)) {
          return num >= 50 ? 'passed' : 'failed'
        }
      }
      return null
    }

    eeteForm.education = toPassFail(newInfo.is_education_passed, newInfo.education_rating)
    eeteForm.experience = toPassFail(newInfo.is_experience_passed, newInfo.experience_rating)
    eeteForm.training = toPassFail(newInfo.is_training_passed, newInfo.training_rating)
    eeteForm.eligibility = toPassFail(newInfo.is_eligibility_passed, newInfo.eligibility_rating)
  }
}, { immediate: true })

// When modal opens, load full PDS bundle for tabs
watch(() => props.visible, (val) => {
  if (val) {
    loadApplicantPds()
  }
})

const saveReview = async () => {
  const reviewData = {
    eeteRating: { ...eeteForm },
    status: (statusForm.status || 'qualified').toLowerCase().trim(),
    pdsData: { ...pdsForm }
  }
  
  emit('save-review', reviewData)
}

// Document preview modal state
const showDocumentModal = ref(false)
const currentDocument = ref(null)
const documentPreviewUrl = ref('')
const documentFileType = ref('')
const documentLoading = ref(false)
const documentError = ref(false)

// Helper function to normalize server URLs
const normalizeServerUrl = (url) => {
  if (!url) return ''
  try {
    const apiBaseUrl = import.meta.env.VITE_API_URL || 'http://localhost:8000'
    const serverBaseUrl = apiBaseUrl.replace(/\/api$/, '')
    
    if (/^https?:\/\//i.test(url)) {
      const parsed = new URL(url)
      if (serverBaseUrl && parsed.origin !== serverBaseUrl) {
        return `${serverBaseUrl}${parsed.pathname}${parsed.search}`
      }
      return url
    }
  } catch (error) {
    console.warn('Failed to parse preview URL, using raw value:', url, error)
  }
  const apiBaseUrl = import.meta.env.VITE_API_URL || 'http://localhost:8000'
  const serverBaseUrl = apiBaseUrl.replace(/\/api$/, '')
  const cleanPath = url.startsWith('/') ? url : `/${url}`
  return serverBaseUrl ? `${serverBaseUrl}${cleanPath}` : cleanPath
}

// Helper function to get file type from filename
const getFileType = (filename) => {
  if (!filename) return 'other'
  const ext = filename.split('.').pop()?.toLowerCase() || ''
  if (ext === 'pdf') return 'pdf'
  if (['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'].includes(ext)) return 'image'
  if (['xlsx', 'xls', 'csv'].includes(ext)) return 'excel'
  if (['docx', 'doc'].includes(ext)) return 'word'
  return 'other'
}

// Open document preview modal
const openDocumentModal = async (row) => {
  if (!row.employee_document_id || !row.attachment_name) return

  // Get employee_id from the row or from pdsForm (loaded from employee_info)
  const employeeId = row.employee_id || pdsForm.employee_id

  if (!employeeId) {
    ElMessage.error('Employee ID not found. Please refresh and try again.')
    return
  }

  currentDocument.value = {
    employee_document_id: row.employee_document_id,
    attachment_name: row.attachment_name,
    description: row.description,
    document_type: row.document_type,
    employee_id: employeeId,
    // Attachments DB fields (new storage)
    file_content: row.file_content || null,
    file_type: row.file_type || null
  }

  documentFileType.value = getFileType(row.attachment_name)
  documentPreviewUrl.value = ''
  documentLoading.value = true
  documentError.value = false
  showDocumentModal.value = true

  try {
    // 1) Fast path: file is stored directly in the attachments DB (base64)
    if (row.file_content) {
      try {
        const base64Content = row.file_content
        const contentType =
          row.file_type ||
          (documentFileType.value === 'pdf'
            ? 'application/pdf'
            : documentFileType.value === 'image'
              ? 'image/jpeg'
              : 'application/octet-stream')

        const byteCharacters = atob(base64Content)
        const byteNumbers = new Array(byteCharacters.length)
        for (let i = 0; i < byteCharacters.length; i++) {
          byteNumbers[i] = byteCharacters.charCodeAt(i)
        }
        const byteArray = new Uint8Array(byteNumbers)
        const blob = new Blob([byteArray], { type: contentType })

        if (blob.size === 0) {
          throw new Error('File is empty.')
        }

        documentPreviewUrl.value = URL.createObjectURL(blob)
        documentLoading.value = false
        return
      } catch (e) {
        console.error('Failed to build preview from attachments DB content, falling back to legacy endpoints:', e)
        // fall through to legacy logic
      }
    }

    // 2) Legacy storage paths (zipped / DOCS folder)
    if (documentFileType.value === 'pdf' || documentFileType.value === 'image' || documentFileType.value === 'excel') {
      // Check if document is in zipped format
      // Zipped format paths contain: \employee_documents\{employee_no}\HRMS\{document_type_id}\{employee_document_id}
      const isZippedFormat = row.path && row.path.includes('HRMS')
      
      console.log('=== Document Preview Debug ===')
      console.log('Document ID:', row.employee_document_id)
      console.log('Employee ID:', row.employee_id)
      console.log('Attachment name:', row.attachment_name)
      console.log('Path from DB:', row.path)
      console.log('Is zipped format:', isZippedFormat)
      console.log('Full row:', row)
      
      if (isZippedFormat) {
        // Document is stored in zipped format - use preview endpoint
        try {
          const previewResponse = await api.get(`/employee-documents/${row.employee_document_id}/preview`)
          
          if (previewResponse.data?.success && previewResponse.data?.data?.preview_url) {
            const url = previewResponse.data.data.preview_url
            documentPreviewUrl.value = normalizeServerUrl(url)
            console.log('Document preview URL from preview endpoint (zipped format):', documentPreviewUrl.value)
            return // Success, exit early
          } else {
            throw new Error('Preview URL not available in response')
          }
        } catch (previewError) {
          console.error('Preview endpoint failed:', previewError.response?.data?.message)
          throw new Error(previewError.response?.data?.message || 'Failed to load document from preview endpoint')
        }
      } else {
        // Document is stored in simple DOCS format - use download endpoint
        try {
          const downloadResponse = await api.get(`/employees/${row.employee_document_id}/download`)
          
          if (downloadResponse.data?.success && downloadResponse.data?.data?.file_content) {
            // Convert base64 to blob
            const base64Content = downloadResponse.data.data.file_content
            const contentType = downloadResponse.data.data.content_type || (documentFileType.value === 'pdf' ? 'application/pdf' : 'image/jpeg')
            
            // Convert base64 to blob
            const byteCharacters = atob(base64Content)
            const byteNumbers = new Array(byteCharacters.length)
            for (let i = 0; i < byteCharacters.length; i++) {
              byteNumbers[i] = byteCharacters.charCodeAt(i)
            }
            const byteArray = new Uint8Array(byteNumbers)
            const blob = new Blob([byteArray], { type: contentType })
            
            if (blob.size === 0) {
              throw new Error('File is empty.')
            }
            documentPreviewUrl.value = URL.createObjectURL(blob)
            console.log('Document blob URL created from download endpoint (DOCS format):', documentPreviewUrl.value)
          } else {
            throw new Error('No file content in response.')
          }
        } catch (downloadError) {
          console.error('Download endpoint failed:', downloadError.response?.data?.message)
          // If simple format fails, try zipped format as fallback
          try {
            const previewResponse = await api.get(`/employee-documents/${row.employee_document_id}/preview`)
            if (previewResponse.data?.success && previewResponse.data?.data?.preview_url) {
              const url = previewResponse.data.data.preview_url
              documentPreviewUrl.value = normalizeServerUrl(url)
              console.log('Document preview URL from fallback preview endpoint:', documentPreviewUrl.value)
            } else {
              throw new Error('Preview URL not available in response')
            }
          } catch (previewError) {
            throw new Error(downloadError.response?.data?.message || previewError.response?.data?.message || 'Failed to load document')
          }
        }
      }
    } else {
      // For other types, just show the download option
      documentPreviewUrl.value = ''
    }
  } catch (error) {
    console.error('Error fetching document for preview:', error)
    console.error('Response status:', error.response?.status)
    console.error('Response data:', error.response?.data)
    console.error('Document row data:', row)
    
    const errorMessage = error.response?.data?.message || error.message || 'Failed to preview document'
    documentError.value = true
    documentPreviewUrl.value = ''
    ElMessage.error(errorMessage)
  } finally {
    documentLoading.value = false
  }
}

// Download document
const downloadDocument = async () => {
  if (!currentDocument.value?.employee_document_id) return

  try {
    // 1) If we already have file_content from the attachments DB, build and download directly
    if (currentDocument.value.file_content) {
      const base64Content = currentDocument.value.file_content
      const fileName = currentDocument.value.attachment_name || 'document'
      const contentType = currentDocument.value.file_type || 'application/octet-stream'

      const byteCharacters = atob(base64Content)
      const byteNumbers = new Array(byteCharacters.length)
      for (let i = 0; i < byteCharacters.length; i++) {
        byteNumbers[i] = byteCharacters.charCodeAt(i)
      }
      const byteArray = new Uint8Array(byteNumbers)
      const blob = new Blob([byteArray], { type: contentType })

      const url = URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = url
      link.download = fileName
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
      URL.revokeObjectURL(url)

      ElMessage.success('Document downloaded successfully')
      return
    }

    // 2) Legacy endpoints for zipped / DOCS storage
    // Try the simpler download endpoint first (returns base64)
    try {
      const response = await api.get(`/employees/${currentDocument.value.employee_document_id}/download`)
      
      if (response.data?.success && response.data?.data?.file_content) {
        const base64Content = response.data.data.file_content
        const fileName = response.data.data.filename || currentDocument.value.attachment_name
        const contentType = response.data.data.content_type || 'application/octet-stream'
        
        // Convert base64 to blob
        const byteCharacters = atob(base64Content)
        const byteNumbers = new Array(byteCharacters.length)
        for (let i = 0; i < byteCharacters.length; i++) {
          byteNumbers[i] = byteCharacters.charCodeAt(i)
        }
        const byteArray = new Uint8Array(byteNumbers)
        const blob = new Blob([byteArray], { type: contentType })
        
        // Create download link
        const url = URL.createObjectURL(blob)
        const link = document.createElement('a')
        link.href = url
        link.download = fileName
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
        URL.revokeObjectURL(url)
        
        ElMessage.success('Document downloaded successfully')
        return // Success, exit early
      }
    } catch (downloadError) {
      console.log('Simple download endpoint failed, trying employee-documents endpoint:', downloadError.response?.data?.message)
      // Fall through to try employee-documents endpoint
    }
    
    // Fallback: Use employee-documents download endpoint (for zipped files)
    const response = await api.get(`/employee-documents/${currentDocument.value.employee_document_id}/download`)
    
    if (response.data?.success && response.data?.data) {
      // Backend may return download_url or file_path
      let downloadUrl = response.data.data.download_url || response.data.data.file_path
      const fileName = response.data.data.file_name || currentDocument.value.attachment_name
      
      if (downloadUrl) {
        // Normalize the URL
        const baseUrl = import.meta.env.VITE_API_URL || 'http://localhost:8000'
        const normalizedUrl = downloadUrl.startsWith('http') 
          ? downloadUrl 
          : downloadUrl.startsWith('/') 
            ? `${baseUrl.replace('/api', '')}${downloadUrl}`
            : `${baseUrl.replace('/api', '')}/${downloadUrl}`
        
        // Create download link
        const link = document.createElement('a')
        link.href = normalizedUrl
        link.download = fileName
        link.setAttribute('target', '_blank')
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
        
        ElMessage.success('Document download started')
      } else {
        throw new Error('No download URL in response.')
      }
    } else {
      throw new Error('No file content in response.')
    }
  } catch (error) {
    console.error('Error downloading document:', error)
    ElMessage.error(error.response?.data?.message || 'Failed to download document')
  }
}

// Clean up blob URLs when modal closes
watch(() => showDocumentModal.value, (val) => {
  if (!val && documentPreviewUrl.value) {
    URL.revokeObjectURL(documentPreviewUrl.value)
    documentPreviewUrl.value = ''
  }
})
</script>

<style scoped>
.space-y-6 > * + * {
  margin-top: 1.5rem;
}

.space-y-4 > * + * {
  margin-top: 1rem;
}

.space-y-2 > * + * {
  margin-top: 0.5rem;
}

.space-x-6 > * + * {
  margin-left: 1.5rem;
}

.space-x-2 > * + * {
  margin-left: 0.5rem;
}

.grid {
  display: grid;
}

.grid-cols-2 {
  grid-template-columns: repeat(2, minmax(0, 1fr));
}

.gap-4 {
  gap: 1rem;
}

.gap-6 {
  gap: 1.5rem;
}

.flex {
  display: flex;
}

.items-start {
  align-items: flex-start;
}

.items-center {
  align-items: center;
}

.justify-between {
  justify-content: space-between;
}

.justify-end {
  justify-content: flex-end;
}

.flex-1 {
  flex: 1 1 0%;
}

.text-xl {
  font-size: 1.25rem;
  line-height: 1.75rem;
}

.font-bold {
  font-weight: 700;
}

.font-semibold {
  font-weight: 600;
}

.text-blue-800 {
  color: rgb(30 64 175);
}

.bg-blue-50 {
  background-color: rgb(239 246 255);
}

.p-3 {
  padding: 0.75rem;
}

.rounded {
  border-radius: 0.25rem;
}

.mb-2 {
  margin-bottom: 0.5rem;
}

.mb-3 {
  margin-bottom: 0.75rem;
}

.mb-4 {
  margin-bottom: 1rem;
}

.mt-6 {
  margin-top: 1.5rem;
}

.text-gray-600 {
  color: rgb(75 85 99);
}

/* PDS Questionnaire Radio Button Styles */
.pds-radio {
  width: 18px;
  height: 18px;
  cursor: not-allowed;
  accent-color: #409eff;
}

.pds-radio:checked {
  accent-color: #409eff;
}

.pds-radio:not(:checked) {
  opacity: 0.5;
}

/* PDS Specific Styles */
.pds-container {
  background: white;
  padding: 20px;
}

.pds-layout {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 30px;
  margin-bottom: 30px;
}

.pds-left-column,
.pds-right-column {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.pds-section {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 15px;
}

.section-header {
  margin-bottom: 15px;
}

.section-title {
  font-size: 16px;
  font-weight: 600;
  color: #374151;
  margin: 0 0 8px 0;
}

.section-line {
  height: 3px;
  width: 100%;
  border-radius: 2px;
}

.green-line {
  background-color: #10b981;
}

.yellow-line {
  background-color: #f59e0b;
}

.red-line {
  background-color: #ef4444;
}

.name-section,
.other-info-section {
  margin-bottom: 20px;
}

.name-label,
.other-info-label {
  font-weight: 600;
  color: #374151;
  margin-bottom: 10px;
  display: block;
}

.name-fields,
.other-info-fields {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 15px;
}

.name-fields .el-form-item,
.other-info-fields .el-form-item {
  margin-bottom: 15px;
}

.pds-bottom-tabs {
  margin-top: 30px;
  border-top: 2px solid #e5e7eb;
  padding-top: 20px;
}

.pds-navigation-tabs {
  width: 100%;
}

.pds-navigation-tabs .el-tabs__header {
  margin: 0;
}

.pds-navigation-tabs .el-tabs__nav-wrap {
  background: #f9fafb;
  border-radius: 6px;
  padding: 5px;
}

.pds-navigation-tabs .el-tabs__item {
  padding: 8px 16px;
  font-size: 14px;
  font-weight: 500;
}

.pds-navigation-tabs .el-tabs__item.is-active {
  background: #3b82f6;
  color: white;
  border-radius: 4px;
}

/* Form styling */
.pds-section .el-form-item {
  margin-bottom: 12px;
}

.pds-section .el-form-item__label {
  font-weight: 500;
  color: #374151;
}

.pds-section .el-input__inner,
.pds-section .el-select .el-input__inner {
  border: 1px solid #d1d5db;
  border-radius: 4px;
  padding: 8px 12px;
}

.pds-section .el-input__inner:focus,
.pds-section .el-select .el-input__inner:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
}

/* Checkbox and Radio styling */
.pds-section .el-checkbox__label,
.pds-section .el-radio__label {
  font-weight: 500;
  color: #374151;
}

.pds-section .el-radio-group {
  display: flex;
  gap: 20px;
}

/* Address sections full width */
.address-sections-full-width {
  width: 100%;
  margin-top: 20px;
}

.address-sections {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
  width: 100%;
}

.address-section-left,
.address-section-right {
  margin: 0;
}

.dual-citizenship-full-width {
  width: 100%;
  margin-top: 20px;
}

/* Tab content styling */
.tab-content {
  padding: 20px;
  background: white;
  border-radius: 8px;
  margin-top: 15px;
}

.tab-title {
  font-size: 18px;
  font-weight: 600;
  color: #374151;
  margin-bottom: 20px;
  padding-bottom: 10px;
  border-bottom: 2px solid #e5e7eb;
}
.ref-email {
  display: block;
  width: 100%;
  white-space: normal;
  overflow: hidden;
  word-break: break-word;
  text-align: right;
  line-height: 1.2;
}

</style>
