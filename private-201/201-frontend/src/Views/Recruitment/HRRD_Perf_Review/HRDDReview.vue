<template>
  <PageScaffold title="HRMPSB Deliberation">
    <HRDDPositions :items="positions" @select="onSelectPosition" />
    
    <!-- Show applicants when a position is selected -->
    <div v-if="selectedPositionId && applicants.length > 0" class="mt-6">
      <HRDDApplicants 
        :items="applicants" 
        @save="onSaveApplicants"
        @decision="onDecision"
        ref="applicantsRef"
      />
    </div>
    
    <!-- Show message when position is selected but no applicants -->
    <div v-else-if="selectedPositionId && applicants.length === 0" class="mt-6">
      <el-card shadow="never">
        <el-empty description="No applicants found for this position" />
      </el-card>
    </div>
  </PageScaffold>
 </template>

<script setup>
import PageScaffold from '../../../components/PageScaffold.vue'
import HRDDPositions from '../../../components/Recruitment/HRRD_Perf_Review/HRDDPositions.vue'
import HRDDApplicants from '../../../components/Recruitment/HRRD_Perf_Review/HRDDApplicants.vue'
import { useHRDDReview } from '../../../composables/useHRDDReview.js'
import { onMounted, ref } from 'vue'
import { ElMessage } from 'element-plus'

const { positions, applicants, selectedPositionId, fetchPositions, fetchApplicants, refetchApplicants, saveBatch } =
  useHRDDReview()
const applicantsRef = ref(null)

onMounted(fetchPositions)

const onSelectPosition = async (row) => {
  const type = row.is_plantilla === false || row.is_plantilla === 0 ? 'non_plantilla' : 'plantilla'
  await fetchApplicants(row.id, type)
}

const onDecision = async ({ applicantId, decision }) => {
  // Clicking "Accept" or "Reject" should immediately process the applicant.
  // We persist via the same batch endpoint but scoped to a single applicant.
  if (!selectedPositionId.value) return
  if (!applicantsRef.value) return

  if (decision !== 'accept' && decision !== 'reject') return

  try {
    const exposedData = applicantsRef.value
    const ratings = exposedData.ratings
    const attachments = exposedData.attachments
    const biAttachments = exposedData.biAttachments || {}

    // Find the applicant index to get the correct rating value.
    const idx = applicants.value.findIndex(a => Number(a.applicant_id) === Number(applicantId))
    const ratingVal = idx >= 0
      ? (ratings && typeof ratings === 'object' && 'value' in ratings ? (ratings.value?.[idx] ?? 0) : (ratings?.[idx] ?? 0))
      : 0

    const attachmentsForOne = attachments && attachments[applicantId]
      ? { [applicantId]: attachments[applicantId] }
      : {}
    
    const biAttachmentsForOne = biAttachments && biAttachments[applicantId]
      ? { [applicantId]: biAttachments[applicantId] }
      : {}

    await saveBatch(
      selectedPositionId.value,
      [Number(applicantId)],
      decision === 'accept' ? [String(applicantId)] : [], // accepted
      decision === 'reject' ? [Number(applicantId)] : [], // rejected
      [Number(ratingVal)], // ratings aligned with id[]
      attachmentsForOne,
      biAttachmentsForOne
    )

    if (decision === 'accept') {
      ElMessage.success('Applicant forwarded')
    } else {
      ElMessage.success('Applicant rejected')
    }
    await fetchApplicants(selectedPositionId.value)
  } catch (error) {
    if (decision === 'accept') {
      ElMessage.error('Failed to forward applicant')
    } else {
      ElMessage.error('Failed to reject applicant')
    }
    console.error('Decision error:', error)
  }
}

const onSaveApplicants = async () => {
  if (!applicantsRef.value) return
  
  const exposedData = applicantsRef.value
  const ratings = exposedData.ratings
  const selected = exposedData.selected
  const decisions = exposedData.decisions
  const attachments = exposedData.attachments
  const biAttachments = exposedData.biAttachments || {}
  const acceptedIds = Object.keys(selected).filter(id => selected[id]).map(id => Number(id))
  const rejectedIds = decisions
    ? Object.keys(decisions).filter(id => decisions[id] === 'reject').map(id => Number(id))
    : []
  
  // Debug logging
  console.log('onSaveApplicants called with:', {
    ratings,
    ratingsValue: ratings.value,
    ratingsType: typeof ratings,
    ratingsIsRef: ratings && typeof ratings === 'object' && 'value' in ratings,
    selected,
    attachments,
    applicants: applicants.value,
    applicantIds: applicants.value.map(a => a.applicant_id)
  })
  
  try {
    // Ensure we have valid data before proceeding
    const applicantIds = applicants.value?.map(a => a.applicant_id) || []
    // ratings is a ref, so we need to access .value, but add fallback
    let ratingsArray = []
    if (ratings && typeof ratings === 'object' && 'value' in ratings) {
      ratingsArray = ratings.value || []
    } else if (Array.isArray(ratings)) {
      ratingsArray = ratings
    } else {
      console.warn('Unexpected ratings format:', ratings)
      ratingsArray = []
    }
    
    console.log('Processed data:', {
      applicantIds,
      ratingsArray,
      acceptedIds,
      rejectedIds,
      attachments
    })
    
    if (applicantIds.length === 0) {
      ElMessage.warning('No applicants found to save')
      return
    }
    
    if (ratingsArray.length === 0) {
      ElMessage.warning('No ratings found to save')
      return
    }
    
    await saveBatch(
      selectedPositionId.value,
      applicantIds,
      acceptedIds,
      rejectedIds,
      ratingsArray,
      attachments || {},
      biAttachments || {}
    )
    ElMessage.success('Applicants saved successfully')
    await refetchApplicants()
  } catch (error) {
    ElMessage.error('Failed to save applicants')
    console.error('Save error:', error)
  }
}
</script>

<style scoped>
.mt-6 {
  margin-top: 1.5rem;
}
</style>


