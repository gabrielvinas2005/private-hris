<template>
  <PageScaffold
    title="Step Increment Approval"
    subtitle="Review and approve pending step increment proposals"
  >
    <ApprovalList
      :step-increments="stepIncrements"
      :loading="loading"
      @refresh="reload"
      @view-details="onViewDetails"
      @approve="onApprove"
      @reject="onReject"
      @bulk-approve="onBulkApprove"
      @bulk-reject="onBulkReject"
    />

    <ApprovalDetails
      v-if="selectedStepIncrement"
      v-model="showDetails"
      :step-increment="selectedStepIncrement"
      :loading="detailsLoading"
      @approve="onApproveFromDetails"
      @reject="onRejectFromDetails"
    />
  </PageScaffold>
</template>

<script setup>
import { ref, onMounted, nextTick, watch } from 'vue'
import PageScaffold from '@/components/PageScaffold.vue'
import ApprovalList from '@/components/Step_Increment_approval/ApprovalList.vue'
import ApprovalDetails from '@/components/Step_Increment_approval/ApprovalDetails.vue'
import { useStepIncrementApproval } from '@/composable/useStepIncrementApproval'
import { ElMessage, ElNotification } from 'element-plus'

const { 
  loading, 
  stepIncrements, 
  approvalDetail, 
  fetchPendingApprovals,
  fetchApprovalDetails,
  approveStepIncrement,
  rejectStepIncrement
} = useStepIncrementApproval()

// Component state
const showDetails = ref(false)
const selectedStepIncrement = ref(null)
const detailsLoading = ref(false)

// Methods
const reload = async () => {
  try {
    await fetchPendingApprovals()
  } catch (error) {
    ElNotification({
      title: 'Reload Failed',
      message: 'Unable to refresh approval list. Please check your connection.',
      type: 'error',
      duration: 5000
    })
  }
}

const onViewDetails = async (stepIncrement) => {
  try {
    detailsLoading.value = true
    
    // Set the data first, then open the drawer
    // Make sure we're preserving all fields from step_increments table
    selectedStepIncrement.value = {
      ...stepIncrement,
      // Ensure these fields are explicitly set
      current_salary_grade_id: stepIncrement.current_salary_grade_id,
      current_salary_step_id: stepIncrement.current_salary_step_id,
      current_salary: stepIncrement.current_salary,
      new_salary_grade_id: stepIncrement.new_salary_grade_id,
      new_salary_step_id: stepIncrement.new_salary_step_id,
      new_salary: stepIncrement.new_salary,
      current_salary_grade: stepIncrement.current_salary_grade,
      current_salary_step: stepIncrement.current_salary_step,
      new_salary_grade: stepIncrement.new_salary_grade,
      new_salary_step: stepIncrement.new_salary_step
    }
    
    // Use nextTick to ensure the component is ready before opening
    await nextTick()
    showDetails.value = true
    
    // Optionally fetch more detailed information
    // await fetchApprovalDetails(stepIncrement.id)
    // selectedStepIncrement.value = approvalDetail.value || stepIncrement
  } catch (error) {
    ElNotification({
      title: 'Details Load Failed',
      message: 'Failed to load step increment details',
      type: 'error',
      duration: 3000
    })
  } finally {
    detailsLoading.value = false
  }
}

const onApprove = async (stepIncrement) => {
  try {
    await approveStepIncrement(stepIncrement.id)
    // Success notification handled in composable
  } catch (error) {
    // Error notification handled in composable
  }
}

const onReject = async (stepIncrement) => {
  try {
    await rejectStepIncrement(stepIncrement.id)
    // Success notification handled in composable
  } catch (error) {
    // Error notification handled in composable
  }
}

const onApproveFromDetails = async (stepIncrement) => {
  await onApprove(stepIncrement)
  showDetails.value = false
}

const onRejectFromDetails = async (stepIncrement) => {
  await onReject(stepIncrement)
  showDetails.value = false
}

// Watch for drawer close to clean up
watch(showDetails, (newValue) => {
  if (!newValue) {
    // Clean up when drawer closes
    setTimeout(() => {
      selectedStepIncrement.value = null
    }, 300) // Wait for drawer animation to complete
  }
})

const onBulkApprove = async (selectedItems) => {
  try {
    const promises = selectedItems.map(item => approveStepIncrement(item.id))
    await Promise.all(promises)
    
    ElNotification({
      title: 'Bulk Approval Complete',
      message: `${selectedItems.length} step increment(s) approved successfully`,
      type: 'success',
      duration: 3000
    })
  } catch (error) {
    ElNotification({
      title: 'Bulk Approval Failed',
      message: 'Some approvals failed. Please try again.',
      type: 'error',
      duration: 5000
    })
  }
}

const onBulkReject = async (selectedItems) => {
  try {
    const promises = selectedItems.map(item => rejectStepIncrement(item.id))
    await Promise.all(promises)
    
    ElNotification({
      title: 'Bulk Rejection Complete',
      message: `${selectedItems.length} step increment(s) rejected`,
      type: 'warning',
      duration: 3000
    })
  } catch (error) {
    ElNotification({
      title: 'Bulk Rejection Failed',
      message: 'Some rejections failed. Please try again.',
      type: 'error',
      duration: 5000
    })
  }
}

// Initialize
onMounted(() => {
  reload()
})
</script>

<style scoped>
/* Add any custom styles here */
</style>

