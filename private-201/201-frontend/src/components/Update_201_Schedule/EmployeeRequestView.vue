<template>
  <el-dialog
    v-model="visible"
    :title="`Employee Request Review - ${employeeName}`"
    width="95%"
    :close-on-click-modal="false"
    top="3vh"
    class="employee-request-view"
  >
    <div v-loading="loading">
      <div v-if="reviewData">
        <el-tabs v-model="activeTab" type="border-card">
          <!-- Basic Information -->
          <el-tab-pane label="Basic Information" name="basic">
            <el-card shadow="never">
              <el-descriptions :column="2" border>
                <el-descriptions-item label="Employee No.">
                  {{ employeeInfo?.employee_no || 'N/A' }}
                </el-descriptions-item>
                <el-descriptions-item label="Access No.">
                  {{ employeeInfo?.access_no || 'N/A' }}
                </el-descriptions-item>
                <el-descriptions-item label="Name">
                  {{ getFullName(employeeInfo) }}
                </el-descriptions-item>
                <el-descriptions-item label="Birthdate">
                  {{ formatDate(employeeInfo?.birthdate) }}
                </el-descriptions-item>
                <el-descriptions-item label="Birth Place">
                  {{ employeeInfo?.birth_place || 'N/A' }}
                </el-descriptions-item>
                <el-descriptions-item label="Age">
                  {{ employeeInfo?.age || 'N/A' }}
                </el-descriptions-item>
                <el-descriptions-item label="Gender">
                  {{ getLookupValue('genders', employeeInfo?.gender_id) }}
                </el-descriptions-item>
                <el-descriptions-item label="Civil Status">
                  {{ getLookupValue('civil_status', employeeInfo?.civil_status_id) }}
                </el-descriptions-item>
                <el-descriptions-item label="Citizenship">
                  {{ getLookupValue('citizenships', employeeInfo?.citizenship_id) }}
                </el-descriptions-item>
                <el-descriptions-item label="Religion">
                  {{ getLookupValue('religions', employeeInfo?.religion_id) }}
                </el-descriptions-item>
                <el-descriptions-item label="Height">
                  {{ employeeInfo?.height || 'N/A' }} cm
                </el-descriptions-item>
                <el-descriptions-item label="Weight">
                  {{ employeeInfo?.weight || 'N/A' }} kg
                </el-descriptions-item>
                <el-descriptions-item label="Blood Type">
                  {{ getLookupValue('blood_types', employeeInfo?.blood_type_id) }}
                </el-descriptions-item>
              </el-descriptions>
            </el-card>
          </el-tab-pane>

          <!-- Contact Information -->
          <el-tab-pane label="Contact Information" name="contact">
            <el-card shadow="never">
              <el-descriptions :column="2" border>
                <el-descriptions-item label="Email">
                  {{ employeeInfo?.email || 'N/A' }}
                </el-descriptions-item>
                <el-descriptions-item label="Mobile No.">
                  {{ employeeInfo?.mobile_no || 'N/A' }}
                </el-descriptions-item>
                <el-descriptions-item label="Telephone No.">
                  {{ employeeInfo?.telephone_no || 'N/A' }}
                </el-descriptions-item>
                <el-descriptions-item label="Residential Address">
                  {{ getFullAddress('ra') }}
                </el-descriptions-item>
                <el-descriptions-item label="Permanent Address">
                  {{ getFullAddress('pa') }}
                </el-descriptions-item>
              </el-descriptions>
            </el-card>
          </el-tab-pane>

          <!-- Family Information -->
          <el-tab-pane label="Family Information" name="family">
            <el-card shadow="never">
              <el-descriptions :column="2" border>
                <el-descriptions-item label="Father's Name">
                  {{ getFullName(employeeInfo, 'father') }}
                </el-descriptions-item>
                <el-descriptions-item label="Mother's Name">
                  {{ getFullName(employeeInfo, 'mother') }}
                </el-descriptions-item>
                <el-descriptions-item label="Spouse's Name">
                  {{ getFullName(employeeInfo, 'spouse') }}
                </el-descriptions-item>
                <el-descriptions-item label="Spouse Occupation">
                  {{ employeeInfo?.spouse_occupation || 'N/A' }}
                </el-descriptions-item>
                <el-descriptions-item label="Spouse Employer">
                  {{ employeeInfo?.spouse_employer || 'N/A' }}
                </el-descriptions-item>
                <el-descriptions-item label="Spouse Business Address">
                  {{ employeeInfo?.spouse_business_address || 'N/A' }}
                </el-descriptions-item>
              </el-descriptions>

              <!-- Children -->
              <div v-if="reviewData.children && reviewData.children.length > 0" class="mt-4">
                <h4 class="mb-3">Children</h4>
                <el-table :data="reviewData.children" border size="small">
                  <el-table-column prop="child_name" label="Name" min-width="150" />
                  <el-table-column prop="child_middlename" label="Middle Name" min-width="150" />
                  <el-table-column prop="child_lastname" label="Last Name" min-width="150" />
                  <el-table-column prop="child_birthdate" label="Birthdate" width="150">
                    <template #default="{ row }">
                      {{ formatDate(row.child_birthdate) }}
                    </template>
                  </el-table-column>
                </el-table>
              </div>
            </el-card>
          </el-tab-pane>

          <!-- Education -->
          <el-tab-pane label="Education" name="education">
            <el-card shadow="never">
              <el-table 
                v-if="reviewData.educations && reviewData.educations.length > 0"
                :data="reviewData.educations" 
                border
              >
                <el-table-column prop="school_name" label="School Name" min-width="200" />
                <el-table-column prop="program" label="Program" min-width="200" />
                <el-table-column label="Level" width="150">
                  <template #default="{ row }">
                    {{ getLookupValue('eligibilities', row.academic_level_id) }}
                  </template>
                </el-table-column>
                <el-table-column prop="from" label="From" width="100" />
                <el-table-column prop="to" label="To" width="100" />
                <el-table-column prop="graduated_year" label="Year Graduated" width="150" />
                <el-table-column prop="units_earned" label="Units Earned" width="120" />
                <el-table-column prop="honors" label="Honors" min-width="150" />
              </el-table>
              <el-empty v-else description="No education records" />
            </el-card>
          </el-tab-pane>

          <!-- Service Records -->
          <el-tab-pane label="Service Records" name="service">
            <el-card shadow="never">
              <el-table 
                v-if="reviewData.service_records && reviewData.service_records.length > 0"
                :data="reviewData.service_records" 
                border
              >
                <el-table-column prop="designation" label="Designation" min-width="200" />
                <el-table-column prop="start_date" label="Start Date" width="120">
                  <template #default="{ row }">
                    {{ formatDate(row.start_date) }}
                  </template>
                </el-table-column>
                <el-table-column prop="end_date" label="End Date" width="120">
                  <template #default="{ row }">
                    {{ formatDate(row.end_date) }}
                  </template>
                </el-table-column>
                <el-table-column prop="employment_type" label="Employment Type" width="150" />
                <el-table-column prop="annual_salary" label="Annual Salary" width="150" />
                <el-table-column prop="place_of_assignment" label="Place of Assignment" min-width="200" />
              </el-table>
              <el-empty v-else description="No service records" />
            </el-card>
          </el-tab-pane>

          <!-- Employment Records -->
          <el-tab-pane label="Employment Records" name="employment">
            <el-card shadow="never">
              <el-table 
                v-if="reviewData.employments && reviewData.employments.length > 0"
                :data="reviewData.employments" 
                border
              >
                <el-table-column prop="work_company" label="Company" min-width="200" />
                <el-table-column prop="position" label="Position" min-width="200" />
                <el-table-column prop="work_start_date" label="Start Date" width="120">
                  <template #default="{ row }">
                    {{ formatDate(row.work_start_date) }}
                  </template>
                </el-table-column>
                <el-table-column prop="work_end_date" label="End Date" width="120">
                  <template #default="{ row }">
                    {{ formatDate(row.work_end_date) }}
                  </template>
                </el-table-column>
                <el-table-column prop="monthly_salary" label="Monthly Salary" width="150" />
                <el-table-column prop="status_of_appointment" label="Status" min-width="150" />
              </el-table>
              <el-empty v-else description="No employment records" />
            </el-card>
          </el-tab-pane>

          <!-- Examinations -->
          <el-tab-pane label="Examinations" name="examinations">
            <el-card shadow="never">
              <el-table 
                v-if="reviewData.examinations && reviewData.examinations.length > 0"
                :data="reviewData.examinations" 
                border
              >
                <el-table-column label="Eligibility" min-width="200">
                  <template #default="{ row }">
                    {{ getLookupValue('eligibilities', row.eligibility_id) }}
                  </template>
                </el-table-column>
                <el-table-column prop="exam_rating" label="Rating" width="120" />
                <el-table-column prop="exam_date" label="Exam Date" width="120">
                  <template #default="{ row }">
                    {{ formatDate(row.exam_date) }}
                  </template>
                </el-table-column>
                <el-table-column prop="place_of_exam" label="Place of Exam" min-width="200" />
                <el-table-column prop="license_number" label="License Number" width="150" />
                <el-table-column prop="date_released" label="Date Released" width="120">
                  <template #default="{ row }">
                    {{ formatDate(row.date_released) }}
                  </template>
                </el-table-column>
              </el-table>
              <el-empty v-else description="No examination records" />
            </el-card>
          </el-tab-pane>

          <!-- Trainings -->
          <el-tab-pane label="Trainings" name="trainings">
            <el-card shadow="never">
              <el-table 
                v-if="reviewData.trainings && reviewData.trainings.length > 0"
                :data="reviewData.trainings" 
                border
              >
                <el-table-column prop="training" label="Training" min-width="250" />
                <el-table-column prop="training_from" label="From" width="120">
                  <template #default="{ row }">
                    {{ formatDate(row.training_from) }}
                  </template>
                </el-table-column>
                <el-table-column prop="training_to" label="To" width="120">
                  <template #default="{ row }">
                    {{ formatDate(row.training_to) }}
                  </template>
                </el-table-column>
                <el-table-column prop="hours" label="Hours" width="100" />
                <el-table-column prop="sponsored_by" label="Sponsored By" min-width="200" />
                <el-table-column label="Learning" width="150">
                  <template #default="{ row }">
                    {{ getLookupValue('learnings', row.learning_id) }}
                  </template>
                </el-table-column>
              </el-table>
              <el-empty v-else description="No training records" />
            </el-card>
          </el-tab-pane>

          <!-- Other Information -->
          <el-tab-pane label="Other Information" name="other">
            <el-row :gutter="16">
              <!-- Organizations -->
              <el-col :span="12">
                <el-card shadow="never" class="mb-4">
                  <template #header>
                    <h4>Organizations</h4>
                  </template>
                  <el-table 
                    v-if="reviewData.organizations && reviewData.organizations.length > 0"
                    :data="reviewData.organizations" 
                    border
                    size="small"
                  >
                    <el-table-column prop="organization" label="Organization" />
                    <el-table-column prop="org_from" label="From" width="100" />
                    <el-table-column prop="org_to" label="To" width="100" />
                  </el-table>
                  <el-empty v-else description="No organizations" />
                </el-card>
              </el-col>

              <!-- Skills -->
              <el-col :span="12">
                <el-card shadow="never" class="mb-4">
                  <template #header>
                    <h4>Skills</h4>
                  </template>
                  <el-table 
                    v-if="reviewData.skills && reviewData.skills.length > 0"
                    :data="reviewData.skills" 
                    border
                    size="small"
                  >
                    <el-table-column prop="skill" label="Skill" />
                  </el-table>
                  <el-empty v-else description="No skills" />
                </el-card>
              </el-col>

              <!-- Recognitions -->
              <el-col :span="12">
                <el-card shadow="never" class="mb-4">
                  <template #header>
                    <h4>Recognitions</h4>
                  </template>
                  <el-table 
                    v-if="reviewData.recognitions && reviewData.recognitions.length > 0"
                    :data="reviewData.recognitions" 
                    border
                    size="small"
                  >
                    <el-table-column prop="recognation" label="Recognition" />
                  </el-table>
                  <el-empty v-else description="No recognitions" />
                </el-card>
              </el-col>

              <!-- Memberships -->
              <el-col :span="12">
                <el-card shadow="never" class="mb-4">
                  <template #header>
                    <h4>Memberships</h4>
                  </template>
                  <el-table 
                    v-if="reviewData.memberships && reviewData.memberships.length > 0"
                    :data="reviewData.memberships" 
                    border
                    size="small"
                  >
                    <el-table-column prop="membership" label="Membership" />
                  </el-table>
                  <el-empty v-else description="No memberships" />
                </el-card>
              </el-col>

              <!-- References -->
              <el-col :span="24">
                <el-card shadow="never">
                  <template #header>
                    <h4>References</h4>
                  </template>
                  <el-table 
                    v-if="reviewData.references && reviewData.references.length > 0"
                    :data="reviewData.references" 
                    border
                  >
                    <el-table-column prop="ref_name" label="Name" min-width="200" />
                    <el-table-column prop="ref_address" label="Address" min-width="200" />
                    <el-table-column prop="ref_occupation" label="Occupation" min-width="150" />
                    <el-table-column prop="ref_contact_no" label="Contact No." width="150" />
                    <el-table-column prop="ref_email" label="Email" min-width="200" />
                  </el-table>
                  <el-empty v-else description="No references" />
                </el-card>
              </el-col>
            </el-row>
          </el-tab-pane>
        </el-tabs>
      </div>
      <el-empty v-else description="No data available" />
    </div>

    <template #footer>
      <div class="dialog-footer">
        <el-button @click="visible = false">Close</el-button>
      </div>
    </template>
  </el-dialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { ElMessage } from 'element-plus'
import { employeeRequestApi } from '@/services/api'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  requestId: { type: Number, default: null }
})

const emit = defineEmits(['update:modelValue'])

const visible = computed({
  get: () => props.modelValue,
  set: (val) => emit('update:modelValue', val)
})

const loading = ref(false)
const reviewData = ref(null)
const activeTab = ref('basic')

const employeeInfo = computed(() => {
  return reviewData.value?.employee_info?.[0] || null
})

const employeeName = computed(() => {
  if (!employeeInfo.value) return 'Unknown'
  return `${employeeInfo.value.first_name || ''} ${employeeInfo.value.middle_name || ''} ${employeeInfo.value.last_name || ''}`.trim()
})

const fetchReviewData = async () => {
  if (!props.requestId) return
  
  try {
    loading.value = true
    const res = await employeeRequestApi.getReviewData(props.requestId)
    reviewData.value = res.data.data || res.data || null
  } catch (error) {
    ElMessage.error('Failed to load review data: ' + (error.response?.data?.message || error.message))
    console.error('Fetch review data error:', error)
  } finally {
    loading.value = false
  }
}

const getLookupValue = (tableName, id) => {
  if (!id || !reviewData.value) return 'N/A'
  const table = reviewData.value[tableName]
  if (!table || !Array.isArray(table)) return 'N/A'
  const item = table.find(item => item.id === id)
  return item?.name || item?.label || 'N/A'
}

const getFullName = (person, prefix = '') => {
  if (!person) return 'N/A'
  const namePrefix = prefix ? `${prefix}_` : ''
  const firstName = person[`${namePrefix}first_name`] || ''
  const middleName = person[`${namePrefix}middle_name`] || ''
  const lastName = person[`${namePrefix}last_name`] || ''
  return `${firstName} ${middleName} ${lastName}`.trim() || 'N/A'
}

const getFullAddress = (type) => {
  if (!employeeInfo.value) return 'N/A'
  const prefix = type === 'ra' ? 'ra_' : 'pa_'
  const parts = [
    employeeInfo.value[`${prefix}house_no`],
    employeeInfo.value[`${prefix}street`],
    employeeInfo.value[`${prefix}barangay`],
    employeeInfo.value[`${prefix}village`]
  ].filter(Boolean)
  return parts.length > 0 ? parts.join(', ') : 'N/A'
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

watch(() => props.modelValue, (val) => {
  if (val && props.requestId) {
    fetchReviewData()
  }
})

watch(() => props.requestId, (val) => {
  if (val && props.modelValue) {
    fetchReviewData()
  }
})
</script>

<style scoped>
.employee-request-view {
  padding: 0;
}

.mt-4 {
  margin-top: 1rem;
}

.mb-3 {
  margin-bottom: 0.75rem;
}

.mb-4 {
  margin-bottom: 1rem;
}

.dialog-footer {
  text-align: right;
}

h4 {
  margin: 0 0 0.5rem 0;
  font-size: 1rem;
  font-weight: 600;
}
</style>
