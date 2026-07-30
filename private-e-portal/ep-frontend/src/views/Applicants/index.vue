<template>
  <MainLayout :breadcrumbs="breadcrumbs">
    <div class="w-full h-full max-w-[1400px] mx-auto px-2 md:px-4">
      <!-- Header Section -->
      <div class="mb-6">
        <h1 class="text-2xl md:text-3xl font-bold text-slate-900 mb-2">Applicant Monitoring</h1>
        <p class="text-slate-600 text-sm md:text-base">Monitor and manage job applicants</p>
      </div>

      <!-- Main Content with Tabs -->
      <el-card shadow="never" body-style="padding: 0;">
        <el-tabs v-model="activeTab" type="border-card" class="applicant-tabs">
          <!-- Vacancies Tab -->
          <el-tab-pane label="Vacancies" name="vacancies">
            <div class="p-6">
              <div v-if="loading" class="text-center py-8">
                <el-icon class="is-loading"><Loading /></el-icon>
                <p class="text-slate-600 mt-2">Loading vacancies...</p>
              </div>
              
              <div v-else-if="error" class="text-center py-8">
                <p class="text-red-600">{{ error }}</p>
                <el-button type="primary" @click="loadVacancies" class="mt-4">Retry</el-button>
              </div>

              <div v-else>
                <div class="mb-4">
                  <el-input
                    v-model="searchQuery"
                    placeholder="Search vacancies..."
                    clearable
                    style="width: 300px;"
                    size="default"
                  >
                    <template #prefix>
                      <el-icon><Search /></el-icon>
                    </template>
                  </el-input>
                </div>

                <el-table
                  :data="filteredVacancies"
                  stripe
                  style="width: 100%"
                  v-loading="loading"
                >
                  <el-table-column prop="position" label="Position" min-width="200" />
                  <el-table-column prop="code" label="Code" width="120" />
                  <el-table-column prop="applicant_count" label="Applicants" width="100" align="center" />
                  <el-table-column label="Actions" width="150" align="center">
                    <template #default="scope">
                      <el-button
                        type="primary"
                        size="small"
                        @click="viewApplicants(scope.row.id)"
                      >
                        View Applicants
                      </el-button>
                    </template>
                  </el-table-column>
                </el-table>
              </div>
            </div>
          </el-tab-pane>

          <!-- Applicants Tab -->
          <el-tab-pane label="Applicants" name="applicants" v-if="selectedPlantillaId">
            <div class="p-6">
              <div class="mb-4 flex items-center justify-between">
                <div>
                  <h3 class="text-lg font-semibold text-slate-900">
                    Applicants for: {{ selectedPositionName }}
                  </h3>
                </div>
                <el-button @click="goBackToVacancies">Back to Vacancies</el-button>
              </div>

              <div v-if="loadingApplicants" class="text-center py-8">
                <el-icon class="is-loading"><Loading /></el-icon>
                <p class="text-slate-600 mt-2">Loading applicants...</p>
              </div>

              <div v-else-if="applicantError" class="text-center py-8">
                <p class="text-red-600">{{ applicantError }}</p>
                <el-button type="primary" @click="loadApplicants" class="mt-4">Retry</el-button>
              </div>

              <div v-else>
                <div class="mb-4 flex items-center gap-3 flex-wrap">
                  <el-input
                    v-model="applicantSearchQuery"
                    placeholder="Search applicants..."
                    clearable
                    style="width: 300px;"
                    size="default"
                  >
                    <template #prefix>
                      <el-icon><Search /></el-icon>
                    </template>
                  </el-input>
                  <el-select
                    v-model="applicantStatusFilter"
                    placeholder="Filter by status"
                    clearable
                    style="width: 200px;"
                    size="default"
                  >
                    <el-option label="All Applications" value="all" />
                    <el-option label="Pending Applications" value="pending" />
                    <el-option label="Proceed Next Step" value="proceed" />
                    <el-option label="Not Qualified" value="not qualified" />
                    <el-option label="Will Not Proceed" value="will not proceed" />
                    <el-option label="For Hiring" value="for hiring" />
                  </el-select>
                  <div v-if="pendingApplicantsCount > 0" class="ml-auto">
                    <el-tag type="warning" size="large">
                      {{ pendingApplicantsCount }} Pending Application{{ pendingApplicantsCount !== 1 ? 's' : '' }}
                    </el-tag>
                  </div>
                </div>

                <el-table
                  :data="filteredApplicants"
                  stripe
                  style="width: 100%"
                  v-loading="loadingApplicants"
                  :row-class-name="getRowClassName"
                >
                  <el-table-column prop="name" label="Name" min-width="200">
                    <template #default="scope">
                      <div class="flex items-center gap-2">
                        <span>{{ scope.row.name }}</span>
                        <el-tag v-if="isPending(scope.row.status)" type="warning" size="small">
                          Pending
                        </el-tag>
                      </div>
                    </template>
                  </el-table-column>
                  <el-table-column prop="date_applied" label="Date Applied" width="150">
                    <template #default="scope">
                      {{ formatDate(scope.row.date_applied) }}
                    </template>
                  </el-table-column>
                  <el-table-column prop="status" label="Status" width="150">
                    <template #default="scope">
                      <el-tag :type="getStatusType(scope.row.status)">
                        {{ scope.row.status }}
                      </el-tag>
                    </template>
                  </el-table-column>
                  <el-table-column label="Actions" width="180" align="center">
                    <template #default="scope">
                      <el-button
                        type="primary"
                        size="small"
                        @click="viewApplicantDetails(scope.row.id)"
                      >
                        View Details
                      </el-button>
                    </template>
                  </el-table-column>
                </el-table>

                <!-- Pagination -->
                <div class="mt-4 flex justify-center" v-if="applicantPagination">
                  <el-pagination
                    v-model:current-page="applicantPagination.current_page"
                    :page-size="applicantPagination.per_page"
                    :total="applicantPagination.total"
                    layout="prev, pager, next"
                    @current-change="handleApplicantPageChange"
                  />
                </div>
              </div>
            </div>
          </el-tab-pane>
        </el-tabs>
      </el-card>

      <!-- Applicant Details Dialog -->
      <el-dialog
        v-model="showApplicantDetails"
        title="Applicant Details"
        width="80%"
        :close-on-click-modal="false"
      >
        <div v-if="loadingDetails" class="text-center py-8">
          <el-icon class="is-loading"><Loading /></el-icon>
          <p class="text-slate-600 mt-2">Loading applicant details...</p>
        </div>
        <div v-else-if="applicantDetails">
          <el-tabs v-model="activeDetailTab" type="border-card">
            <!-- Basic Information Tab -->
            <el-tab-pane label="Basic Information" name="basic">
              <el-descriptions :column="2" border>
                <el-descriptions-item label="Name">
                  {{ applicantDetails.name || 'N/A' }}
                </el-descriptions-item>
                <el-descriptions-item label="Date Applied">
                  {{ formatDate(applicantDetails.date_applied) }}
                </el-descriptions-item>
                <el-descriptions-item label="Status">
                  <el-tag :type="getStatusType(applicantDetails.status)">
                    {{ applicantDetails.status }}
                  </el-tag>
                </el-descriptions-item>
                <el-descriptions-item label="Position Applied">
                  {{ selectedPositionName }}
                </el-descriptions-item>
                <el-descriptions-item label="Email" v-if="applicantDetails.email">
                  {{ applicantDetails.email }}
                </el-descriptions-item>
                <el-descriptions-item label="Mobile No" v-if="applicantDetails.mobile_no">
                  {{ applicantDetails.mobile_no }}
                </el-descriptions-item>
                <el-descriptions-item label="Address" v-if="applicantDetails.address" :span="2">
                  {{ applicantDetails.address }}
                </el-descriptions-item>
              </el-descriptions>
            </el-tab-pane>

            <!-- Attachments Tab -->
            <el-tab-pane label="Attachments" name="attachments">
              <div v-if="applicantDetails.attachments && applicantDetails.attachments.length > 0">
                <el-table :data="applicantDetails.attachments" style="width: 100%">
                  <el-table-column prop="attachment_name" label="File Name" />
                  <el-table-column prop="created_at" label="Uploaded Date">
                    <template #default="{ row }">
                      {{ formatDate(row.created_at) }}
                    </template>
                  </el-table-column>
                  <el-table-column label="Actions" width="120">
                    <template #default="{ row }">
                      <el-button 
                        type="primary" 
                        size="small" 
                        @click="viewAttachment(row)"
                      >
                        View
                      </el-button>
                    </template>
                  </el-table-column>
                </el-table>
              </div>
              <el-empty v-else description="No attachments found" />
            </el-tab-pane>

            <!-- PDS Tab -->
            <el-tab-pane label="PDS" name="pds">
              <div v-if="applicantDetails.pds_data && applicantDetails.pds_data.personal_info">
                <el-collapse v-model="activePdsSection">
                  <!-- Personal Information -->
                  <el-collapse-item title="Personal Information" name="personal">
                    <el-descriptions :column="2" border>
                      <el-descriptions-item label="Name">
                        {{ getFullName(applicantDetails.pds_data.personal_info) }}
                      </el-descriptions-item>
                      <el-descriptions-item label="Employee No">
                        {{ applicantDetails.pds_data.personal_info.employee_no || 'N/A' }}
                      </el-descriptions-item>
                      <el-descriptions-item label="Birth Date">
                        {{ formatDate(applicantDetails.pds_data.personal_info.birthdate) }}
                      </el-descriptions-item>
                      <el-descriptions-item label="Age">
                        {{ applicantDetails.pds_data.personal_info.age || 'N/A' }}
                      </el-descriptions-item>
                      <el-descriptions-item label="Gender">
                        {{ applicantDetails.pds_data.personal_info.gender || 'N/A' }}
                      </el-descriptions-item>
                      <el-descriptions-item label="Civil Status">
                        {{ applicantDetails.pds_data.personal_info.civil_status || 'N/A' }}
                      </el-descriptions-item>
                      <el-descriptions-item label="Citizenship">
                        {{ applicantDetails.pds_data.personal_info.citizenship || 'N/A' }}
                      </el-descriptions-item>
                      <el-descriptions-item label="Religion">
                        {{ applicantDetails.pds_data.personal_info.religion || 'N/A' }}
                      </el-descriptions-item>
                      <el-descriptions-item label="Email">
                        {{ applicantDetails.pds_data.personal_info.email || 'N/A' }}
                      </el-descriptions-item>
                      <el-descriptions-item label="Mobile No">
                        {{ applicantDetails.pds_data.personal_info.mobile_no || 'N/A' }}
                      </el-descriptions-item>
                      <el-descriptions-item label="Residential Address" :span="2">
                        {{ getFullAddress(applicantDetails.pds_data.personal_info, 'ra') }}
                      </el-descriptions-item>
                      <el-descriptions-item label="Permanent Address" :span="2">
                        {{ getFullAddress(applicantDetails.pds_data.personal_info, 'pa') }}
                      </el-descriptions-item>
                    </el-descriptions>
                  </el-collapse-item>

                  <!-- Family -->
                  <el-collapse-item title="Family" name="family">
                    <div class="mb-4">
                      <h4 class="mb-2">Father</h4>
                      <el-descriptions :column="2" border>
                        <el-descriptions-item label="Name">
                          {{ getFamilyMemberName(applicantDetails.pds_data.family.father) }}
                        </el-descriptions-item>
                      </el-descriptions>
                    </div>
                    <div class="mb-4">
                      <h4 class="mb-2">Mother</h4>
                      <el-descriptions :column="2" border>
                        <el-descriptions-item label="Name">
                          {{ getFamilyMemberName(applicantDetails.pds_data.family.mother) }}
                        </el-descriptions-item>
                      </el-descriptions>
                    </div>
                    <div class="mb-4" v-if="applicantDetails.pds_data.family.spouse">
                      <h4 class="mb-2">Spouse</h4>
                      <el-descriptions :column="2" border>
                        <el-descriptions-item label="Name">
                          {{ getFamilyMemberName(applicantDetails.pds_data.family.spouse) }}
                        </el-descriptions-item>
                        <el-descriptions-item label="Occupation" v-if="applicantDetails.pds_data.family.spouse.occupation">
                          {{ applicantDetails.pds_data.family.spouse.occupation }}
                        </el-descriptions-item>
                        <el-descriptions-item label="Employer" v-if="applicantDetails.pds_data.family.spouse.employer">
                          {{ applicantDetails.pds_data.family.spouse.employer }}
                        </el-descriptions-item>
                      </el-descriptions>
                    </div>
                    <div v-if="applicantDetails.pds_data.family.children && applicantDetails.pds_data.family.children.length > 0">
                      <h4 class="mb-2">Children</h4>
                      <el-table :data="applicantDetails.pds_data.family.children" style="width: 100%">
                        <el-table-column prop="child_name" label="Name" />
                        <el-table-column prop="child_birthdate" label="Birth Date">
                          <template #default="{ row }">
                            {{ formatDate(row.child_birthdate) }}
                          </template>
                        </el-table-column>
                      </el-table>
                    </div>
                  </el-collapse-item>

                  <!-- Education -->
                  <el-collapse-item title="Education" name="education">
                    <el-table v-if="applicantDetails.pds_data.education && applicantDetails.pds_data.education.length > 0" :data="applicantDetails.pds_data.education" style="width: 100%">
                      <el-table-column prop="school_name" label="School Name" />
                      <el-table-column prop="academic_level" label="Level" />
                      <el-table-column prop="program" label="Program" />
                      <el-table-column prop="graduated_year" label="Year Graduated" />
                    </el-table>
                    <el-empty v-else description="No education records" />
                  </el-collapse-item>

                  <!-- Employment -->
                  <el-collapse-item title="Employment Records" name="employment">
                    <el-table v-if="applicantDetails.pds_data.employment && applicantDetails.pds_data.employment.length > 0" :data="applicantDetails.pds_data.employment" style="width: 100%">
                      <el-table-column prop="Office_name" label="Company/Office" />
                      <el-table-column prop="Position" label="Position" />
                      <el-table-column prop="Work_start_date" label="Start Date">
                        <template #default="{ row }">
                          {{ formatDate(row.Work_start_date) }}
                        </template>
                      </el-table-column>
                      <el-table-column prop="Work_end_date" label="End Date">
                        <template #default="{ row }">
                          {{ formatDate(row.Work_end_date) }}
                        </template>
                      </el-table-column>
                      <el-table-column prop="Duration" label="Duration" />
                      <el-table-column prop="Office_Address" label="Office Address" />
                      <el-table-column prop="Immediate_supervisor" label="Immediate Supervisor" />
                    </el-table>
                    <el-empty v-else description="No employment records" />
                  </el-collapse-item>

                  <!-- Examinations/Eligibilities -->
                  <el-collapse-item title="Examinations/Eligibilities" name="examinations">
                    <el-table v-if="applicantDetails.pds_data.examinations && applicantDetails.pds_data.examinations.length > 0" :data="applicantDetails.pds_data.examinations" style="width: 100%">
                      <el-table-column prop="eligibility" label="Eligibility" />
                      <el-table-column prop="exam_rating" label="Rating" />
                      <el-table-column prop="exam_date" label="Exam Date">
                        <template #default="{ row }">
                          {{ formatDate(row.exam_date) }}
                        </template>
                      </el-table-column>
                      <el-table-column prop="place_of_exam" label="Place of Exam" />
                    </el-table>
                    <el-empty v-else description="No examination records" />
                  </el-collapse-item>

                  <!-- Trainings -->
                  <el-collapse-item title="Trainings" name="trainings">
                    <el-table v-if="applicantDetails.pds_data.trainings && applicantDetails.pds_data.trainings.length > 0" :data="applicantDetails.pds_data.trainings" style="width: 100%">
                      <el-table-column prop="training" label="Training" />
                      <el-table-column prop="training_from" label="From">
                        <template #default="{ row }">
                          {{ formatDate(row.training_from) }}
                        </template>
                      </el-table-column>
                      <el-table-column prop="training_to" label="To">
                        <template #default="{ row }">
                          {{ formatDate(row.training_to) }}
                        </template>
                      </el-table-column>
                      <el-table-column prop="hours" label="Hours" />
                    </el-table>
                    <el-empty v-else description="No training records" />
                  </el-collapse-item>

                  <!-- References -->
                  <el-collapse-item title="References" name="references">
                    <el-table v-if="applicantDetails.pds_data.references && applicantDetails.pds_data.references.length > 0" :data="applicantDetails.pds_data.references" style="width: 100%">
                      <el-table-column prop="ref_name" label="Name" />
                      <el-table-column prop="ref_address" label="Address" />
                      <el-table-column prop="ref_occupation" label="Occupation" />
                      <el-table-column prop="ref_contact_no" label="Contact No" />
                      <el-table-column prop="ref_email" label="Email" />
                    </el-table>
                    <el-empty v-else description="No references" />
                  </el-collapse-item>
                </el-collapse>
              </div>
              <el-empty v-else description="No PDS data available. Applicant may not have been converted to employee record yet." />
            </el-tab-pane>

            <!-- Examination Results Tab -->
            <el-tab-pane label="Examinations" name="exam">
              <div v-if="applicantDetails.examination_schedules && applicantDetails.examination_schedules.length > 0">
                <el-table :data="applicantDetails.examination_schedules" style="width: 100%">
                  <el-table-column prop="exam_set" label="Exam" min-width="220" />
                  <el-table-column prop="category" label="Category" min-width="180" />
                  <el-table-column label="Schedule" min-width="240">
                    <template #default="{ row }">
                      {{ formatDate(row.exam_date_from) }} {{ formatTime(row.exam_time_from) }}
                      <span v-if="row.exam_date_to || row.exam_time_to">
                        - {{ formatDate(row.exam_date_to) }} {{ formatTime(row.exam_time_to) }}
                      </span>
                    </template>
                  </el-table-column>
                  <el-table-column label="Status" width="120">
                    <template #default="{ row }">
                      <el-tag :type="getExamStatusType(row.status)">
                        {{ row.status || 'N/A' }}
                      </el-tag>
                    </template>
                  </el-table-column>
                  <el-table-column label="Rating" width="110">
                    <template #default="{ row }">
                      <span v-if="row.exam_rating !== null && row.exam_rating !== undefined">
                        {{ Number(row.exam_rating).toFixed(2) }}
                      </span>
                      <span v-else>-</span>
                    </template>
                  </el-table-column>
                  <el-table-column prop="passing_criteria" label="Passing %" width="110" />
                  <el-table-column label="Score" width="130">
                    <template #default="{ row }">
                      <span v-if="row.total_items !== null && row.total_items !== undefined && row.total_score !== null && row.total_score !== undefined">
                        {{ row.total_score }} / {{ row.total_items }}
                      </span>
                      <span v-else>-</span>
                    </template>
                  </el-table-column>
                </el-table>
              </div>
              <el-empty v-else description="No examinations taken" />
            </el-tab-pane>

            <!-- Interview Schedule & Rating Tab -->
            <el-tab-pane label="Interview Schedule & Rating" name="interview">
              <div v-if="applicantDetails.interview_schedules && applicantDetails.interview_schedules.length > 0">
                <el-table :data="applicantDetails.interview_schedules" style="width: 100%">
                  <el-table-column type="expand">
                    <template #default="{ row }">
                      <div class="p-2">
                        <h4 class="mb-2">Panel Attachments</h4>
                        <el-table
                          v-if="getPanelAttachmentsForInterview(row.id).length > 0"
                          :data="getPanelAttachmentsForInterview(row.id)"
                          style="width: 100%"
                          size="small"
                        >
                          <el-table-column prop="panel_name" label="Panelist" min-width="200" />
                          <el-table-column prop="original_name" label="File Name" min-width="260" />
                          <el-table-column prop="created_at" label="Uploaded At" width="190">
                            <template #default="{ row: att }">
                              {{ formatDateTime(att.created_at) }}
                            </template>
                          </el-table-column>
                          <el-table-column label="Action" width="120" align="center">
                            <template #default="{ row: att }">
                              <el-button type="primary" text size="small" @click="viewPanelAttachment(att)">
                                View
                              </el-button>
                            </template>
                          </el-table-column>
                        </el-table>
                        <el-empty v-else description="No panel attachments" />
                      </div>
                    </template>
                  </el-table-column>

                  <el-table-column prop="level" label="Level" width="140" />
                  <el-table-column prop="panel_group" label="Panel Group" min-width="160" />
                  <el-table-column prop="interview_location" label="Location" min-width="180" />
                  <el-table-column label="Schedule" min-width="220">
                    <template #default="{ row }">
                      {{ formatDate(row.start_date) }} {{ formatTime(row.start_time) }}
                      <span v-if="row.end_date || row.end_time">
                        - {{ formatDate(row.end_date) }} {{ formatTime(row.end_time) }}
                      </span>
                    </template>
                  </el-table-column>
                  <el-table-column label="Status" width="120">
                    <template #default="{ row }">
                      <el-tag :type="getInterviewStatusType(row.status)">
                        {{ row.status || 'N/A' }}
                      </el-tag>
                    </template>
                  </el-table-column>
                </el-table>
              </div>
              <el-empty v-else description="No interview schedules" />
            </el-tab-pane>
          </el-tabs>
        </div>
        <template #footer>
          <el-button @click="showApplicantDetails = false">Close</el-button>
        </template>
      </el-dialog>

      <!-- Attachment Preview Dialog -->
      <el-dialog
        v-model="showAttachmentPreview"
        title="Attachment Preview"
        width="90%"
        :close-on-click-modal="false"
      >
        <div v-if="selectedAttachment">
          <div class="mb-4">
            <p><strong>File Name:</strong> {{ selectedAttachment.attachment_name }}</p>
            <p><strong>Uploaded:</strong> {{ formatDate(selectedAttachment.created_at) }}</p>
          </div>
          <div class="attachment-preview">
            <div v-if="attachmentPreviewLoading" class="text-center py-8">
              <el-icon class="is-loading"><Loading /></el-icon>
              <p class="text-slate-600 mt-2">Loading preview...</p>
            </div>
            <div v-else-if="attachmentPreviewError" class="text-center py-8">
              <p class="text-red-600">{{ attachmentPreviewError }}</p>
            </div>
            <!-- PDF and other documents -->
            <iframe 
              v-else-if="isPreviewableFile(selectedAttachment.attachment_name) && attachmentPreviewUrl"
              :src="attachmentPreviewUrl" 
              style="width: 100%; height: 70vh; border: 1px solid #ddd;"
              frameborder="0"
            ></iframe>
            <!-- Images -->
            <img 
              v-else-if="isImageFile(selectedAttachment.attachment_name) && attachmentPreviewUrl"
              :src="attachmentPreviewUrl" 
              style="max-width: 100%; max-height: 70vh; display: block; margin: 0 auto;"
              alt="Attachment preview"
            />
            <!-- Other file types - show download option -->
            <div v-else class="text-center py-8">
              <el-icon :size="64" class="text-gray-400"><Document /></el-icon>
              <p class="mt-4 text-gray-600">Preview not available for this file type</p>
              <p class="text-sm text-gray-500">{{ selectedAttachment.attachment_name }}</p>
            </div>
          </div>
        </div>
        <template #footer>
          <el-button @click="closeAttachmentPreview">Close</el-button>
          <el-button type="primary" @click="openAttachmentInNewTab">
            {{ isPreviewableFile(selectedAttachment?.attachment_name) || isImageFile(selectedAttachment?.attachment_name) ? 'Open in New Tab' : 'Download' }}
          </el-button>
        </template>
      </el-dialog>
    </div>
  </MainLayout>
</template>

<script>
import { ref, computed, onMounted, watch } from 'vue'
import { useToast } from 'vue-toastification'
import MainLayout from '../../layout/MainLayout.vue'
import ApiService from '../../services/api.js'
import { Search, Loading, Document } from '@element-plus/icons-vue'

export default {
  name: 'ApplicantMonitoring',
  components: {
    MainLayout,
    Search,
    Loading,
    Document
  },
  setup() {
    const toast = useToast()
    
    // State
    const activeTab = ref('vacancies')
    const loading = ref(false)
    const error = ref(null)
    const vacancies = ref([])
    const searchQuery = ref('')
    const selectedPlantillaId = ref(null)
    const selectedPositionName = ref('')
    
    // Applicants state
    const loadingApplicants = ref(false)
    const applicantError = ref(null)
    const applicants = ref([])
    const applicantSearchQuery = ref('')
    const applicantStatusFilter = ref('all') // Default to all to show all applicants
    const applicantPagination = ref(null)
    const showApplicantDetails = ref(false)
    const loadingDetails = ref(false)
    const applicantDetails = ref(null)
    const activeDetailTab = ref('basic')
    const activePdsSection = ref(['personal'])
    const showAttachmentPreview = ref(false)
    const selectedAttachment = ref(null)
    const attachmentPreviewUrl = ref('')
    const attachmentPreviewLoading = ref(false)
    const attachmentPreviewError = ref('')

    // Breadcrumbs
    const breadcrumbs = computed(() => [
      { name: 'Dashboard', path: '/dashboard' },
      { name: 'Applicant Monitoring', path: '/applicants' }
    ])

    // Filtered vacancies
    const filteredVacancies = computed(() => {
      if (!searchQuery.value) return vacancies.value
      const query = searchQuery.value.toLowerCase()
      return vacancies.value.filter(v => 
        (v.position && v.position.toLowerCase().includes(query)) ||
        (v.code && v.code.toLowerCase().includes(query))
      )
    })

    // Filtered applicants - default to pending only
    const filteredApplicants = computed(() => {
      if (!Array.isArray(applicants.value) || applicants.value.length === 0) {
        return []
      }
      
      let filtered = [...applicants.value]
      
      // Filter by status - but only if filter is set
      if (applicantStatusFilter.value === 'pending') {
        filtered = filtered.filter(a => {
          const status = (a.status || '').toLowerCase().trim()
          // Check for various pending status names
          return status.includes('new applicant') || 
                 status.includes('active') ||
                 status === 'new applicant' ||
                 status === 'active' ||
                 status.startsWith('new')
        })
      } else if (applicantStatusFilter.value && applicantStatusFilter.value !== 'all') {
        const filterStatus = applicantStatusFilter.value.toLowerCase()
        filtered = filtered.filter(a => {
          const status = (a.status || '').toLowerCase()
          return status.includes(filterStatus)
        })
      }
      
      // Filter by search query
      if (applicantSearchQuery.value) {
        const query = applicantSearchQuery.value.toLowerCase()
        filtered = filtered.filter(a => 
          (a.name && a.name.toLowerCase().includes(query)) ||
          (a.status && a.status.toLowerCase().includes(query))
        )
      }
      
      return filtered
    })

    // Pending applicants count
    const pendingApplicantsCount = computed(() => {
      return applicants.value.filter(a => {
        const status = (a.status || '').toLowerCase()
        return status.includes('new applicant') || status.includes('active')
      }).length
    })

    // Methods
    const loadVacancies = async () => {
      loading.value = true
      error.value = null
      try {
        const response = await ApiService.getApplicantVacancies()
        if (response && response.success) {
          // The API returns { vacancies: [...], applicant_count: [...] }
          if (response.data && Array.isArray(response.data.vacancies)) {
            vacancies.value = response.data.vacancies || []
          } else if (Array.isArray(response.data)) {
            // Fallback if response.data is directly an array
            vacancies.value = response.data
          } else {
            vacancies.value = []
          }
        } else {
          error.value = response?.message || 'Failed to load vacancies'
          vacancies.value = []
        }
      } catch (err) {
        error.value = err.message || 'Failed to load vacancies'
        vacancies.value = []
        toast.error('Failed to load vacancies')
      } finally {
        loading.value = false
      }
    }

    const viewApplicants = (plantillaId) => {
      const vacancy = vacancies.value.find(v => v.id === plantillaId)
      selectedPlantillaId.value = plantillaId
      selectedPositionName.value = vacancy?.position || 'Unknown Position'
      activeTab.value = 'applicants'
      loadApplicants()
    }

    const goBackToVacancies = () => {
      selectedPlantillaId.value = null
      selectedPositionName.value = ''
      applicants.value = []
      applicantPagination.value = null
      activeTab.value = 'vacancies'
    }

    const loadApplicants = async () => {
      if (!selectedPlantillaId.value) return
      
      loadingApplicants.value = true
      applicantError.value = null
      try {
        const response = await ApiService.getApplicantList(selectedPlantillaId.value)
        console.log('Applicant list response:', response)
        
        if (response && response.success) {
          // Handle paginated response
          if (response.data && response.data.data && Array.isArray(response.data.data)) {
            applicants.value = response.data.data || []
            applicantPagination.value = {
              current_page: response.data.current_page || 1,
              per_page: response.data.per_page || 20,
              total: response.data.total || 0
            }
          } 
          // Handle direct array response
          else if (Array.isArray(response.data)) {
            applicants.value = response.data || []
            applicantPagination.value = null
          }
          // Handle object with data property
          else if (response.data && Array.isArray(response.data)) {
            applicants.value = response.data
            applicantPagination.value = null
          }
          else {
            applicants.value = []
            applicantPagination.value = null
            console.warn('Unexpected response structure:', response)
          }
          
          console.log('Loaded applicants:', applicants.value.length)
        } else {
          applicantError.value = response?.message || 'Failed to load applicants'
          applicants.value = []
        }
      } catch (err) {
        applicantError.value = err.message || 'Failed to load applicants'
        applicants.value = []
        console.error('Error loading applicants:', err)
        toast.error('Failed to load applicants')
      } finally {
        loadingApplicants.value = false
      }
    }

    const handleApplicantPageChange = (page) => {
      if (applicantPagination.value) {
        applicantPagination.value.current_page = page
      }
      loadApplicants()
    }

    const viewApplicantDetails = async (applicantId) => {
      if (!selectedPlantillaId.value) return
      
      showApplicantDetails.value = true
      loadingDetails.value = true
      activeDetailTab.value = 'basic'
      try {
        const response = await ApiService.getApplicantInfo(applicantId, selectedPlantillaId.value)
        if (response && response.success) {
          applicantDetails.value = response.data || {}
        } else {
          toast.error(response?.message || 'Failed to load applicant details')
        }
      } catch (err) {
        toast.error('Failed to load applicant details')
      } finally {
        loadingDetails.value = false
      }
    }

    const revokeAttachmentPreviewUrl = () => {
      if (attachmentPreviewUrl.value) {
        URL.revokeObjectURL(attachmentPreviewUrl.value)
        attachmentPreviewUrl.value = ''
      }
    }

    const viewAttachment = async (attachment) => {
      selectedAttachment.value = attachment
      showAttachmentPreview.value = true

      attachmentPreviewError.value = ''
      attachmentPreviewLoading.value = true
      revokeAttachmentPreviewUrl()

      // Only fetch blob for previewable types (pdf/images). Others just show message.
      const name = attachment?.attachment_name || ''
      if (!isPreviewableFile(name) && !isImageFile(name)) {
        attachmentPreviewLoading.value = false
        return
      }

      try {
        const blob = await ApiService.getApplicantAttachmentBlob(attachment.id)
        attachmentPreviewUrl.value = URL.createObjectURL(blob)
      } catch (e) {
        attachmentPreviewError.value = e?.message || 'Failed to load attachment preview'
      } finally {
        attachmentPreviewLoading.value = false
      }
    }

    const getFullName = (person) => {
      if (!person) return 'N/A'
      const parts = [
        person.name_prefix,
        person.first_name,
        person.middle_name,
        person.last_name,
        person.name_suffix
      ].filter(Boolean)
      return parts.join(' ') || 'N/A'
    }

    const getFamilyMemberName = (member) => {
      if (!member) return 'N/A'
      const parts = [
        member.name_prefix,
        member.first_name,
        member.middle_name,
        member.last_name,
        member.name_suffix
      ].filter(Boolean)
      return parts.join(' ') || 'N/A'
    }

    const getFullAddress = (person, type) => {
      if (!person) return 'N/A'
      const prefix = type === 'ra' ? 'ra' : 'pa'
      const parts = [
        person[`${prefix}_house_no`],
        person[`${prefix}_street`],
        person[`${prefix}_barangay`],
        person[`${prefix}_city`],
        person[`${prefix}_province`],
        person[`${prefix}_region`]
      ].filter(Boolean)
      return parts.join(', ') || 'N/A'
    }

    const openAttachmentInNewTab = () => {
      if (selectedAttachment.value) {
        // Prefer blob URL (it includes auth-protected bytes already fetched)
        if (attachmentPreviewUrl.value) {
          window.open(attachmentPreviewUrl.value, '_blank')
          return
        }
        // Fallback: fetch then open (still works even if preview wasn't opened yet)
        viewAttachment(selectedAttachment.value).then(() => {
          if (attachmentPreviewUrl.value) {
            window.open(attachmentPreviewUrl.value, '_blank')
          }
        })
      }
    }

    const closeAttachmentPreview = () => {
      showAttachmentPreview.value = false
      selectedAttachment.value = null
      attachmentPreviewError.value = ''
      attachmentPreviewLoading.value = false
      revokeAttachmentPreviewUrl()
    }

    watch(showAttachmentPreview, (open) => {
      if (!open) {
        closeAttachmentPreview()
      }
    })

    const isPreviewableFile = (fileName) => {
      if (!fileName) return false
      const ext = fileName.split('.').pop()?.toLowerCase()
      return ['pdf', 'txt', 'html', 'htm'].includes(ext)
    }

    const isImageFile = (fileName) => {
      if (!fileName) return false
      const ext = fileName.split('.').pop()?.toLowerCase()
      return ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'].includes(ext)
    }

    const formatTime = (timeString) => {
      if (!timeString) return 'N/A'
      // SQL Server may return HH:MM:SS; keep HH:MM for display
      if (typeof timeString === 'string' && timeString.length >= 5) return timeString.slice(0, 5)
      return String(timeString)
    }

    const formatDateTime = (dateTimeString) => {
      if (!dateTimeString) return 'N/A'
      const d = new Date(dateTimeString)
      if (Number.isNaN(d.getTime())) return String(dateTimeString)
      return d.toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      })
    }

    const getInterviewStatusType = (status) => {
      const s = (status || '').toLowerCase()
      if (s.includes('completed')) return 'success'
      if (s.includes('active')) return 'success'
      if (s.includes('pending')) return 'warning'
      if (s.includes('expired')) return 'info'
      if (s.includes('cancel')) return 'danger'
      return 'info'
    }

    const getExamStatusType = (status) => {
      // Reuse same coloring rules as interview status
      return getInterviewStatusType(status)
    }

    const getPanelAttachmentsForInterview = (interviewId) => {
      const atts = applicantDetails.value?.interview_panel_attachments
      if (!Array.isArray(atts)) return []

      // Filter by interview first
      const filtered = atts.filter(a => String(a.interview_id) === String(interviewId))
      if (!filtered.length) return []

      // Group by panel (employee_id) and keep the latest attachment per panelist,
      // so Applicant Monitoring shows a single entry per panelist instead of one per file.
      const byPanel = new Map()
      for (const att of filtered) {
        const key = String(att.employee_id)
        const existing = byPanel.get(key)
        if (!existing) {
          byPanel.set(key, att)
        } else {
          const existingDate = existing.created_at ? new Date(existing.created_at) : null
          const currentDate = att.created_at ? new Date(att.created_at) : null
          if (existingDate && currentDate) {
            if (currentDate > existingDate) byPanel.set(key, att)
          } else if (currentDate && !existingDate) {
            byPanel.set(key, att)
          }
        }
      }

      return Array.from(byPanel.values())
    }

    const viewPanelAttachment = async (attachment) => {
      selectedAttachment.value = {
        id: attachment.id,
        attachment_name: attachment.original_name,
        created_at: attachment.created_at
      }
      showAttachmentPreview.value = true

      attachmentPreviewError.value = ''
      attachmentPreviewLoading.value = true
      revokeAttachmentPreviewUrl()

      try {
        const blob = await ApiService.previewPanelInterviewAttachment(attachment.id)
        attachmentPreviewUrl.value = URL.createObjectURL(blob)
      } catch (err) {
        attachmentPreviewError.value = err.message || 'Failed to load attachment preview'
      } finally {
        attachmentPreviewLoading.value = false
      }
    }

    const formatDate = (dateString) => {
      if (!dateString) return 'N/A'
      const date = new Date(dateString)
      return date.toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric' 
      })
    }

    const getStatusType = (status) => {
      if (!status) return 'info'
      const statusLower = status.toLowerCase()
      if (statusLower.includes('new applicant') || statusLower.includes('active')) return 'warning'
      if (statusLower.includes('proceed') || statusLower.includes('approved')) return 'success'
      if (statusLower.includes('not qualified') || statusLower.includes('disapproved')) return 'danger'
      if (statusLower.includes('will not proceed')) return 'warning'
      if (statusLower.includes('hiring')) return 'success'
      return 'info'
    }

    const isPending = (status) => {
      if (!status) return false
      const statusLower = status.toLowerCase()
      return statusLower.includes('new applicant') || statusLower.includes('active')
    }

    const getRowClassName = ({ row }) => {
      if (isPending(row.status)) {
        return 'pending-row'
      }
      return ''
    }

    onMounted(() => {
      loadVacancies()
    })

    return {
      activeTab,
      loading,
      error,
      vacancies,
      searchQuery,
      filteredVacancies,
      selectedPlantillaId,
      selectedPositionName,
      loadingApplicants,
      applicantError,
      applicants,
      applicantSearchQuery,
      applicantStatusFilter,
      filteredApplicants,
      pendingApplicantsCount,
      applicantPagination,
      showApplicantDetails,
      loadingDetails,
      applicantDetails,
      activeDetailTab,
      activePdsSection,
      showAttachmentPreview,
      selectedAttachment,
      attachmentPreviewUrl,
      attachmentPreviewLoading,
      attachmentPreviewError,
      viewAttachment,
      closeAttachmentPreview,
      getFullName,
      getFamilyMemberName,
      getFullAddress,
      openAttachmentInNewTab,
      isPreviewableFile,
      isImageFile,
      formatTime,
      formatDateTime,
      getInterviewStatusType,
      getExamStatusType,
      getPanelAttachmentsForInterview,
      viewPanelAttachment,
      breadcrumbs,
      loadVacancies,
      viewApplicants,
      goBackToVacancies,
      loadApplicants,
      handleApplicantPageChange,
      viewApplicantDetails,
      formatDate,
      getStatusType,
      isPending,
      getRowClassName
    }
  }
}
</script>

<style scoped>
.applicant-tabs {
  min-height: 500px;
}

:deep(.pending-row) {
  background-color: #fef3c7 !important;
}

:deep(.pending-row:hover) {
  background-color: #fde68a !important;
}
</style>
