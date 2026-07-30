<template>
  <PageScaffold
    title="Update 201 Schedule"
    subtitle="Manage employee 201 file update schedules"
  >
    <ScheduleList
      :schedules="schedules"
      :loading="loading"
      @refresh="reload"
      @add="onAdd"
      @view="onView"
      @edit="onEdit"
      @delete="onDelete"
      @print="onPrint"
      @excel="onExcel"
      @pdf="onPDF"
      @viewEmployees="onViewEmployees"
    />

    <ScheduleForm
      v-model="showForm"
      :schedule-data="selectedSchedule"
      :loading="formLoading"
      @save="onSave"
    />

    <ScheduleDetails
      v-model="showDetails"
      :schedule="selectedSchedule"
      :loading="detailsLoading"
      @edit="onEditFromDetails"
      @export="onExportDetails"
      @refresh="onRefreshDetails"
    />

    <EmployeeListModal
      v-model="showEmployeeList"
      :schedule-id="selectedSchedule?.id"
      @review="onReviewEmployee"
    />
  </PageScaffold>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import PageScaffold from '@/components/PageScaffold.vue'
import ScheduleList from '@/components/Update_201_Schedule/ScheduleList.vue'
import ScheduleForm from '@/components/Update_201_Schedule/ScheduleForm.vue'
import ScheduleDetails from '@/components/Update_201_Schedule/ScheduleDetails.vue'
import EmployeeListModal from '@/components/Update_201_Schedule/EmployeeListModal.vue'
import { useUpdate201Schedule } from '@/composable/useUpdate201Schedule'
import { ElMessage, ElMessageBox } from 'element-plus'

const { 
  loading, 
  schedules, 
  fetchSchedules,
  saveSchedule,
  deleteSchedule
} = useUpdate201Schedule()

// Component state
const showForm = ref(false)
const showDetails = ref(false)
const showEmployeeList = ref(false)
const selectedSchedule = ref(null)
const formLoading = ref(false)
const detailsLoading = ref(false)

// Methods
const reload = async () => {
  try {
    await fetchSchedules()
  } catch (error) {
    console.error('Failed to reload schedules:', error)
  }
}

const onAdd = async () => {
  try {
    formLoading.value = true
    selectedSchedule.value = null
    showForm.value = true
  } catch (error) {
    console.error('Failed to prepare add form:', error)
    ElMessage.error('Failed to prepare add form')
  } finally {
    formLoading.value = false
  }
}

const onEdit = async (schedule) => {
  try {
    formLoading.value = true
    selectedSchedule.value = schedule
    showForm.value = true
  } catch (error) {
    console.error('Failed to prepare edit form:', error)
    ElMessage.error('Failed to prepare edit form')
  } finally {
    formLoading.value = false
  }
}

const onView = async (schedule) => {
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

const onDelete = async (schedule) => {
  try {
    await ElMessageBox.confirm(
      `Are you sure you want to delete schedule #${schedule.id}? This action cannot be undone.`,
      'Confirm Deletion',
      {
        confirmButtonText: 'Delete',
        cancelButtonText: 'Cancel',
        type: 'warning'
      }
    )
    
    await deleteSchedule(schedule.id)
    await reload()
  } catch (error) {
    if (error !== 'cancel') {
      console.error('Delete failed:', error)
    }
  }
}

const onSave = async (scheduleData) => {
  try {
    await saveSchedule(scheduleData.id, scheduleData)
    showForm.value = false
    await reload()
  } catch (error) {
    console.error('Save failed:', error)
  }
}

const onEditFromDetails = (schedule) => {
  showDetails.value = false
  onEdit(schedule)
}

const onExportDetails = (schedule) => {
  ElMessage.info(`Exporting details for schedule #${schedule.id}`)
  // TODO: Implement export functionality
}

const onRefreshDetails = async () => {
  await reload()
}

const onPrint = () => {
  ElMessage.info('Print functionality - to be implemented')
  // TODO: Implement print functionality
}

const onExcel = () => {
  ElMessage.info('Excel export functionality - to be implemented')
  // TODO: Implement Excel export functionality
}

const onPDF = () => {
  ElMessage.info('PDF export functionality - to be implemented')
  // TODO: Implement PDF export functionality
}

const onViewEmployees = (schedule) => {
  selectedSchedule.value = schedule
  showEmployeeList.value = true
}

const onReviewEmployee = (employee) => {
  // TODO: Navigate to review page or open review dialog
  ElMessage.info(`Review employee ${employee.name} - to be implemented`)
  // You can navigate to the review page: router.push(`/review-201-updates/${employee.request_id}/review`)
}

// Initialize
onMounted(() => {
  reload()
})
</script>

<style scoped>
/* Add any custom styles here */
</style>

