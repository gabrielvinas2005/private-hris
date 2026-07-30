<template>
  <PageScaffold title="Administrator Selection">
    <AdministratorPositions 
      :items="positions" 
      :loading="loading"
      @select="onSelectPosition" 
    />
    
    <!-- Show applicants when a position is selected -->
    <div v-if="selectedPositionId && applicants.length > 0" class="mt-6">
      <AdministratorApplicants 
        :items="applicants" 
        :loading="loading"
        :position="selectedPosition"
        @view-pds="onViewPDS"
        @view-exam="onViewExam"
        @view-hrdd="onViewHRDD"
        @appoint="onAppoint"
        @close="onCloseApplicants"
      />
    </div>
    
    <!-- Show message when position is selected but no applicants -->
    <div v-else-if="selectedPositionId && applicants.length === 0 && !loading" class="mt-6">
      <el-card shadow="never">
        <el-empty description="No applicants found for this position" />
      </el-card>
    </div>

    <!-- PDS Modal -->
    <ApplicantPDSModal 
      v-model="pdsModalVisible"
      :applicant-data="selectedApplicant"
    />

    <!-- Exam Modal -->
    <ApplicantExamModal 
      v-model="examModalVisible"
      :applicant-data="selectedApplicant"
    />

    <!-- HRDD Modal -->
    <ApplicantHRDDModal 
      v-model="hrddModalVisible"
      :applicant-data="selectedApplicant"
    />
  </PageScaffold>
</template>

<script setup>
import PageScaffold from '../../../components/PageScaffold.vue'
import AdministratorPositions from '../../../components/Recruitment/Administrator_Selection/AdministratorPositions.vue'
import AdministratorApplicants from '../../../components/Recruitment/Administrator_Selection/AdministratorApplicants.vue'
import ApplicantPDSModal from '../../../components/Recruitment/Administrator_Selection/ApplicantPDSModal.vue'
import ApplicantExamModal from '../../../components/Recruitment/Administrator_Selection/ApplicantExamModal.vue'
import ApplicantHRDDModal from '../../../components/Recruitment/Administrator_Selection/ApplicantHRDDModal.vue'
import { useAdministratorSelection } from '../../../composables/useAdministratorSelection.js'
import { onMounted, ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'

const {
  positions,
  applicants,
  selectedPositionId,
  loading,
  fetchPositions,
  fetchApplicants,
  refetchApplicants,
  appointApplicant
} = useAdministratorSelection()

// Modal states
const pdsModalVisible = ref(false)
const examModalVisible = ref(false)
const hrddModalVisible = ref(false)
const selectedApplicant = ref(null)
const selectedPosition = ref(null)

onMounted(fetchPositions)

const onSelectPosition = async (row) => {
  selectedPosition.value = row
  const type = row.is_plantilla === false || row.is_plantilla === 0 ? 'non_plantilla' : 'plantilla'
  await fetchApplicants(row.id, type)
}

const onCloseApplicants = () => {
  // clear applicants and hide section
  selectedPositionId.value = 0
}

const onViewPDS = (applicant) => {
  selectedApplicant.value = applicant
  pdsModalVisible.value = true
}

const onViewExam = (applicant) => {
  selectedApplicant.value = applicant
  examModalVisible.value = true
}

const onViewHRDD = (applicant) => {
  selectedApplicant.value = applicant
  hrddModalVisible.value = true
}

const onAppoint = async (applicant) => {
  try {
    await ElMessageBox.confirm(
      `Are you sure you want to appoint ${applicant.name}? This action cannot be undone.`,
      'Confirm Appointment',
      {
        confirmButtonText: 'Yes, Appoint',
        cancelButtonText: 'Cancel',
        type: 'warning',
      }
    )

    await appointApplicant(applicant.applicant_id)
    ElMessage.success(`${applicant.name} has been successfully appointed!`)

    await refetchApplicants()
  } catch (error) {
    if (error !== 'cancel') {
      console.error('Appointment error:', error)
      ElMessage.error('Failed to appoint applicant')
    }
  }
}
</script>

<style scoped>
.mt-6 {
  margin-top: 1.5rem;
}
</style>
