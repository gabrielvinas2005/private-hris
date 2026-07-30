<template>
  <PageScaffold
    title="Salary Adjustment"
    subtitle="Process salary adjustments based on salary schedules"
  >
    <SalaryAdjustmentList
      :salary-schedules="salarySchedules"
      :loading="loading"
      :last-processed-date="lastProcessedDate"
      @refresh="reload"
      @process-adjustment="onProcessAdjustment"
      @view-schedule="onViewSchedule"
    />

    <ScheduleDetails
      v-model="showDetails"
      :schedule="selectedSchedule"
      :loading="detailsLoading"
      @process-schedule="onProcessFromDetails"
    />
  </PageScaffold>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import PageScaffold from '@/components/PageScaffold.vue'
import SalaryAdjustmentList from '@/components/Salary_Adjustment/SalaryAdjustmentList.vue'
import ScheduleDetails from '@/components/Salary_Adjustment/ScheduleDetails.vue'
import { useSalaryAdjustment } from '@/composable/useSalaryAdjustment'
import { ElMessage } from 'element-plus'

const { 
  loading, 
  salarySchedules,
  lastProcessedDate,
  fetchSalarySchedules,
  processSalaryAdjustment
} = useSalaryAdjustment()

// Component state
const showDetails = ref(false)
const selectedSchedule = ref(null)
const detailsLoading = ref(false)

// Methods
const reload = async () => {
  try {
    await fetchSalarySchedules()
  } catch (error) {
    console.error('Failed to reload salary schedules:', error)
  }
}

const onViewSchedule = async (schedule) => {
  try {
    detailsLoading.value = true
    selectedSchedule.value = schedule
    showDetails.value = true
  } catch (error) {
    console.error('Failed to load schedule details:', error)
    ElMessage.error('Failed to load schedule details')
  } finally {
    detailsLoading.value = false
  }
}

const onProcessAdjustment = async (salaryScheduleId) => {
  try {
    const result = await processSalaryAdjustment(salaryScheduleId)
    
    // Reload the schedules after processing to get updated last processed date
    await reload()
    
    return result
  } catch (error) {
    console.error('Processing failed:', error)
    throw error
  }
}

const onProcessFromDetails = async (schedule) => {
  try {
    await onProcessAdjustment(schedule.id)
    showDetails.value = false
  } catch (error) {
    console.error('Processing from details failed:', error)
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

