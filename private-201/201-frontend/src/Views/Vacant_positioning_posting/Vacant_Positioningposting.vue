<template>
  <VacantPositionList
    :vacant-positions="vacantPositions"
    :loading="loading"
    :processing-loading="processingLoading"
    :on-view="onView"
    :on-approve="onApprove"
    :on-disapprove="onDisapprove"
    :on-cancel="onCancel"
  />

  <VacantPositionDetails
    v-model="showDetails"
    :details="selectedPositionDetails"
    :loading="detailsLoading"
    :processing-loading="processingLoading"
    :on-approve="onApproveFromDetails"
    :on-disapprove="onDisapproveFromDetails"
    :on-cancel="onCancelFromDetails"
    @close="onCloseDetails"
  />
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { useVacantPosition } from '@/composable/useVacantPosition'
import VacantPositionList from '@/components/Vacant_positioning_posting/VacantPositionList.vue'
import VacantPositionDetails from '@/components/Vacant_positioning_posting/VacantPositionDetails.vue'

// Composables
const {
  loading,
  vacantPositions,
  positionDetails,
  processingLoading,
  fetchVacantPositions,
  fetchVacantPositionDetails,
  approvePosition,
  disapprovePosition,
  cancelPosition
} = useVacantPosition()

// Reactive data
const showDetails = ref(false)
const selectedPositionDetails = ref(null)
const detailsLoading = ref(false)
const selectedPositionId = ref(null)

const getDetailsProcessType = () =>
  selectedPositionDetails.value?.plantilla?.is_plantilla === false ? 'non_plantilla' : 'plantilla'

// Methods
const onView = async (position) => {
  try {
    selectedPositionId.value = position.id
    detailsLoading.value = true
    showDetails.value = true
    
    const details = await fetchVacantPositionDetails(position.id, position.is_plantilla ? 'plantilla' : 'non_plantilla')
    selectedPositionDetails.value = details
  } catch (error) {
    console.error('Failed to load position details:', error)
    showDetails.value = false
  } finally {
    detailsLoading.value = false
  }
}

const onApprove = async (position) => {
  try {
    await ElMessageBox.confirm(
      `Are you sure you want to approve the position "${position.position}"?`,
      'Approve Position',
      {
        confirmButtonText: 'Yes, Approve',
        cancelButtonText: 'Cancel',
        type: 'warning',
      }
    )
    
    await approvePosition(position.id, position.is_plantilla ? 'plantilla' : 'non_plantilla')
  } catch (error) {
    if (error !== 'cancel') {
      console.error('Failed to approve position:', error)
    }
  }
}

const onDisapprove = async (position) => {
  try {
    await ElMessageBox.confirm(
      `Are you sure you want to disapprove the position "${position.position}"?`,
      'Disapprove Position',
      {
        confirmButtonText: 'Yes, Disapprove',
        cancelButtonText: 'Cancel',
        type: 'warning',
      }
    )
    
    await disapprovePosition(position.id, position.is_plantilla ? 'plantilla' : 'non_plantilla')
  } catch (error) {
    if (error !== 'cancel') {
      console.error('Failed to disapprove position:', error)
    }
  }
}

const onCancel = async (position) => {
  try {
    await ElMessageBox.confirm(
      `Are you sure you want to cancel the position "${position.position}"?`,
      'Cancel Position',
      {
        confirmButtonText: 'Yes, Cancel',
        cancelButtonText: 'No',
        type: 'warning',
      }
    )
    
    await cancelPosition(position.id, position.is_plantilla ? 'plantilla' : 'non_plantilla')
  } catch (error) {
    if (error !== 'cancel') {
      console.error('Failed to cancel position:', error)
    }
  }
}

// Actions from details drawer
const onApproveFromDetails = async () => {
  if (!selectedPositionId.value) return
  
  try {
    await ElMessageBox.confirm(
      'Are you sure you want to approve this position?',
      'Approve Position',
      {
        confirmButtonText: 'Yes, Approve',
        cancelButtonText: 'Cancel',
        type: 'warning',
      }
    )
    
    const type = getDetailsProcessType()
    await approvePosition(selectedPositionId.value, type)
    
    // Reload the details to reflect the updated status
    if (showDetails.value && selectedPositionId.value) {
      detailsLoading.value = true
      try {
        const details = await fetchVacantPositionDetails(selectedPositionId.value, type)
        selectedPositionDetails.value = details
      } catch (error) {
        console.error('Failed to reload position details:', error)
      } finally {
        detailsLoading.value = false
      }
    }
  } catch (error) {
    if (error !== 'cancel') {
      console.error('Failed to approve position:', error)
    }
  }
}

const onDisapproveFromDetails = async () => {
  if (!selectedPositionId.value) return
  
  try {
    await ElMessageBox.confirm(
      'Are you sure you want to disapprove this position?',
      'Disapprove Position',
      {
        confirmButtonText: 'Yes, Disapprove',
        cancelButtonText: 'Cancel',
        type: 'warning',
      }
    )
    
    const type = getDetailsProcessType()
    await disapprovePosition(selectedPositionId.value, type)
    
    // Reload the details to reflect the updated status
    if (showDetails.value && selectedPositionId.value) {
      detailsLoading.value = true
      try {
        const details = await fetchVacantPositionDetails(selectedPositionId.value, type)
        selectedPositionDetails.value = details
      } catch (error) {
        console.error('Failed to reload position details:', error)
      } finally {
        detailsLoading.value = false
      }
    }
  } catch (error) {
    if (error !== 'cancel') {
      console.error('Failed to disapprove position:', error)
    }
  }
}

const onCancelFromDetails = async () => {
  if (!selectedPositionId.value) return
  
  try {
    await ElMessageBox.confirm(
      'Are you sure you want to cancel this position?',
      'Cancel Position',
      {
        confirmButtonText: 'Yes, Cancel',
        cancelButtonText: 'No',
        type: 'warning',
      }
    )
    
    const type = getDetailsProcessType()
    await cancelPosition(selectedPositionId.value, type)
    
    // Reload the details to reflect the updated status
    if (showDetails.value && selectedPositionId.value) {
      detailsLoading.value = true
      try {
        const details = await fetchVacantPositionDetails(selectedPositionId.value, type)
        selectedPositionDetails.value = details
      } catch (error) {
        console.error('Failed to reload position details:', error)
      } finally {
        detailsLoading.value = false
      }
    }
  } catch (error) {
    if (error !== 'cancel') {
      console.error('Failed to cancel position:', error)
    }
  }
}

const onCloseDetails = () => {
  showDetails.value = false
  selectedPositionDetails.value = null
  selectedPositionId.value = null
}


// Initialize
onMounted(async () => {
  try {
    await fetchVacantPositions()
  } catch (error) {
    console.error('Failed to fetch vacant positions:', error)
  }
})
</script>

<style scoped>
</style>

