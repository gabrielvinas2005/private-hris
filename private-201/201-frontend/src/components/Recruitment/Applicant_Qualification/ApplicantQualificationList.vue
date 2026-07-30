<template>
  <PageScaffold title="Applicant Qualification" subtitle="Review applicants by position">
    <div class="bg-white rounded-lg shadow p-4">
      <el-tabs v-model="activeTab">
        <el-tab-pane label="Plantilla" name="plantilla">
          <el-table :data="plantillaPositions" size="small" stripe>
            <el-table-column prop="code" label="Code" width="140" />
            <el-table-column prop="position" label="Position" />
            <el-table-column prop="department" label="Department" />
            <el-table-column prop="grade" label="Grade" width="100" />
            <el-table-column prop="step" label="Step" width="100" />
            <el-table-column prop="total" label="Applicants" width="120" />
            <el-table-column label="Actions" width="140">
              <template #default="{ row }">
                <el-button size="small" type="primary" @click="openApplicants(row, 1)">View</el-button>
              </template>
            </el-table-column>
          </el-table>
        </el-tab-pane>
        <el-tab-pane label="Non-Plantilla" name="non">
          <el-table :data="nonPlantillaPositions" size="small" stripe>
            <el-table-column prop="position" label="Position" />
            <el-table-column prop="department" label="Department" />
            <el-table-column prop="salary" label="Salary" width="120" />
            <el-table-column prop="status" label="Status" width="120" />
            <el-table-column prop="total" label="Applicants" width="120" />
            <el-table-column label="Actions" width="140">
              <template #default="{ row }">
                <el-button size="small" type="primary" @click="openApplicants(row, 0)">View</el-button>
              </template>
            </el-table-column>
          </el-table>
        </el-tab-pane>
      </el-tabs>
    </div>

    <ApplicantsDrawer
      v-model:visible="showApplicants"
      :loading="applicantsLoading"
      :position="selectedPosition"
      :applicants="applicants"
      @refresh="loadApplicants"
      @view-detail="openApplicantDetail"
      @process="processApplicant"
      @zip="onZip"
    />

    <ApplicantDetailModal
      v-model:visible="showApplicantDetail"
      :applicant-info="selectedApplicant"
      :saving="saving"
      @save-review="saveApplicantReview"
    />
  </PageScaffold>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import PageScaffold from '../../PageScaffold.vue'
import { useApplicantQualification } from '../../../composable/useApplicantQualification.js'
import ApplicantsDrawer from './ApplicantsDrawer.vue'
import ApplicantDetailModal from './ApplicantDetailModal.vue'

const activeTab = ref('plantilla')

const {
  loading,
  applicantsLoading,
  saving,
  plantillaPositions,
  nonPlantillaPositions,
  selectedPosition,
  applicants,
  showApplicants,
  fetchPositions,
  openApplicants,
  loadApplicants,
  processApplicant,
  submitRating,
  ratingForm
} = useApplicantQualification()

// New state for applicant detail modal
const showApplicantDetail = ref(false)
const selectedApplicant = ref(null)

const onZip = async () => {
  // Handled inside drawer; placeholder hook
}

const openApplicantDetail = (applicant) => {
  selectedApplicant.value = applicant
  showApplicantDetail.value = true
}

const saveApplicantReview = async (reviewData) => {
  try {
    saving.value = true
    
    // Update the rating form with the EETE data
    // Try multiple possible ID fields
    const applicantId = selectedApplicant.value?.id || 
                       selectedApplicant.value?.applicant_id || 
                       selectedApplicant.value?.applicantId ||
                       selectedApplicant.value?.ID
                       
    ratingForm.applicant_id = applicantId
    
    
    
    // Validate that we have a valid applicant ID
    if (!applicantId || applicantId === null || applicantId === undefined) {
      ElMessage.error('No applicant ID found. Please try again.')
      
      return
    }
    
    // Ensure applicant ID is a number (convert string to number if needed)
    const numericApplicantId = typeof applicantId === 'string' ? parseInt(applicantId, 10) : applicantId
    if (isNaN(numericApplicantId)) {
      ElMessage.error('Invalid applicant ID format. Please try again.')
      
      return
    }
    
    ratingForm.applicant_id = numericApplicantId
    
    // Map dropdown (passed / failed) to numeric ratings (100 / 0) for compatibility
    const toRatingFromFlag = (v) => {
      if (v === 'passed') return 100
      if (v === 'failed') return 0
      return 0
    }
    ratingForm.education_rating = toRatingFromFlag(reviewData.eeteRating.education)
    ratingForm.experience_rating = toRatingFromFlag(reviewData.eeteRating.experience)
    ratingForm.training_rating = toRatingFromFlag(reviewData.eeteRating.training)
    ratingForm.eligibility_rating = toRatingFromFlag(reviewData.eeteRating.eligibility)

    // Explicit pass/fail flags for each category
    ratingForm.is_education_passed = reviewData.eeteRating.education === 'passed' ? 1 : 0
    ratingForm.is_experience_passed = reviewData.eeteRating.experience === 'passed' ? 1 : 0
    ratingForm.is_training_passed = reviewData.eeteRating.training === 'passed' ? 1 : 0
    ratingForm.is_eligibility_passed = reviewData.eeteRating.eligibility === 'passed' ? 1 : 0
    ratingForm.interview = 0
    ratingForm.bonus = 0
    
    
    
    // Map status to reviewed_status_id (2 = Qualified, 4 = For Reference)
    const statusMapping = { qualified: 2, for_reference: 4 }
    const normalizedStatus = (reviewData.status && typeof reviewData.status === 'string')
      ? reviewData.status.toLowerCase().trim()
      : reviewData.status
    ratingForm.reviewed_status_id = statusMapping[normalizedStatus] ?? 2
    
    
    
    // Call the API to save the rating
    await submitRating()
    
    showApplicantDetail.value = false
    
    // Refresh the applicants list to show updated data
    await loadApplicants()
    
    const savedApplicant = applicants.value.find(app => app.id == numericApplicantId)
    
    if (savedApplicant) {
      // no-op; left for future use
    }
    
    // Force refresh the applicants drawer
    setTimeout(async () => {
      await loadApplicants()
    }, 1000)
    
  } catch (error) {
    console.error('Error saving review:', error)
  } finally {
    saving.value = false
  }
}

onMounted(fetchPositions)
</script>

<style scoped>
</style>
