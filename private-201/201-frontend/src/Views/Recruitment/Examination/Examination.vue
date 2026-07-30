<template>
  <PageScaffold title="Examination">
    <ExaminationList
      :exams="exams"
      :schedules="schedules"
      :processing-id="processingId"
      @add="onAddExam"
      @edit="onEditExam"
      @delete="onDeleteExam"
      @add-schedule="onAddSchedule"
      @edit-schedule="onEditSchedule"
      @details="onDetails"
      @tag="onTag"
      @post="(row) => onProcessSchedule(row, 1)"
      @unpost="(row) => onProcessSchedule(row, 0)"
      @delete-schedule="onDeleteSchedule"
    />

    <ExaminationForm
      v-model:visible="showExamForm"
      :model-value="examForm"
      :categories="examCategories"
      :exam-types="examTypes"
      :saving="saving"
      @save="saveExamForm"
    />

    <ScheduleForm
      v-model:visible="showScheduleForm"
      :model-value="scheduleForm"
      :exams="exams"
      :saving="saving"
      @save="saveScheduleForm"
    />
  </PageScaffold>
  
  <ScheduleDetailsDialog
    v-model:visible="showDetails"
    :schedule="selectedSchedule"
    :exam-details="examDetails"
    :examinees="examinees"
    :exams="exams"
    :saving="saving"
    @save="saveScheduleForm"
    @delete="onDeleteSchedule"
    @tag="onTag(selectedSchedule)"
    @remove="removeExaminee"
  />

  <TagApplicantsDialog
    v-model:visible="showTag"
    :schedule-id="Number(selectedSchedule?.id || 0)"
    :applicants="applicants"
    :saving="saving"
    @save="saveTaggedApplicants"
  />

</template>

<script setup>
import PageScaffold from '../../../components/PageScaffold.vue'
import ExaminationList from '../../../components/Recruitment/Examination/ExaminationList.vue'
import ExaminationForm from '../../../components/Recruitment/Examination/ExaminationForm.vue'
import ScheduleForm from '../../../components/Recruitment/Examination/ScheduleForm.vue'
import ScheduleDetailsDialog from '../../../components/Recruitment/Examination/ScheduleDetailsDialog.vue'
import TagApplicantsDialog from '../../../components/Recruitment/Examination/TagApplicantsDialog.vue'
import { useExamination } from '../../../composables/useExamination.js'
import { onMounted, ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'

const {
  exams,
  schedules,
  examForm,
  scheduleForm,
  examCategories,
  examTypes,
  examDetails,
  applicants,
  examinees,
  fetchExams,
  loadExamForm,
  saveExam,
  deleteExam,
  fetchSchedules,
  loadScheduleForm,
  saveSchedule,
  processSchedule,
  deleteSchedule
} = useExamination()

// dialogs
const showDetails = ref(false)
const showTag = ref(false)
const selectedSchedule = ref(null)
// Using refs from composable for shared state across dialogs

const showExamForm = ref(false)
const showScheduleForm = ref(false)
const saving = ref(false)
const processingId = ref(null)
let currentExamId = 0
let currentScheduleId = 0

onMounted(async () => {
  await fetchExams()
  await fetchSchedules()
})

const onAddExam = async () => {
  currentExamId = 0
  await loadExamForm(0)
  showExamForm.value = true
}

const onEditExam = async (row) => {
  currentExamId = row.id
  await loadExamForm(row.id)
  showExamForm.value = true
}

const saveExamForm = async (form) => {
  try {
    saving.value = true
    const id = await saveExam(currentExamId, form)
    showExamForm.value = false
    await fetchExams()
  } finally {
    saving.value = false
  }
}

const onDeleteExam = async (row) => {
  try {
    await ElMessageBox.confirm(
      `Delete exam setup "${row.exam_set}"? This cannot be undone.`,
      'Confirm Delete',
      { type: 'warning', confirmButtonText: 'Delete', cancelButtonText: 'Cancel' }
    )
    await deleteExam(row.id)
    ElMessage.success('Examination setup deleted.')
    await fetchExams()
  } catch (e) {
    if (e === 'cancel' || e?.toString?.() === 'cancel') return
    const msg = e?.response?.data?.message || 'Failed to delete examination setup.'
    ElMessage.error(msg)
  }
}

const onAddSchedule = async () => {
  currentScheduleId = 0
  await loadScheduleForm(0)
  showScheduleForm.value = true
}

const onEditSchedule = async (row) => {
  currentScheduleId = row.id
  await loadScheduleForm(row.id)
  showScheduleForm.value = true
}

// details dialog
const onDetails = async (row) => {
  currentScheduleId = row.id
  selectedSchedule.value = row
  // load detail bundle for this schedule id
  await loadScheduleForm(row.id)
  showDetails.value = true
}

// tag applicants
const onTag = async (row) => {
  selectedSchedule.value = row
  await loadScheduleForm(row.id)
  showTag.value = true
}

const saveTaggedApplicants = async ({ ids, select }) => {
  await useExamination().addExamineesToSchedule(selectedSchedule.value.id, ids, select)
  showTag.value = false
  await loadScheduleForm(selectedSchedule.value.id)
  examinees.value = useExamination().examinees.value
}

const removeExaminee = async (row) => {
  await useExamination().deleteExaminee(row.id)
  await loadScheduleForm(selectedSchedule.value.id)
  examinees.value = useExamination().examinees.value
}

const saveScheduleForm = async (form) => {
  try {
    saving.value = true
    const scheduleId =
      Number(form?.id) || Number(currentScheduleId) || Number(selectedSchedule.value?.id) || 0
    await saveSchedule(scheduleId, form)
    showScheduleForm.value = false
    await fetchSchedules()
    if (showDetails.value && scheduleId) {
      selectedSchedule.value = schedules.value.find((s) => Number(s.id) === scheduleId) || selectedSchedule.value
      await loadScheduleForm(scheduleId)
    }
  } finally {
    saving.value = false
  }
}

const onDeleteSchedule = async (row) => {
  if (!row?.id) return
  try {
    await ElMessageBox.confirm(
      `Delete examination schedule for "${row.exam_set || 'this exam'}"? Tagged examinees and answers will be removed. This cannot be undone.`,
      'Delete Examination Schedule',
      { type: 'warning', confirmButtonText: 'Delete', cancelButtonText: 'Cancel' }
    )
    await deleteSchedule(row.id)
    ElMessage.success('Examination schedule deleted.')
    if (Number(selectedSchedule.value?.id) === Number(row.id)) {
      showDetails.value = false
      selectedSchedule.value = null
    }
    await fetchSchedules()
  } catch (e) {
    if (e === 'cancel' || e?.toString?.() === 'cancel') return
    const msg = e?.response?.data?.message || 'Failed to delete examination schedule.'
    ElMessage.error(msg)
  }
}

const onProcessSchedule = async (row, typeId) => {
  processingId.value = row.id
  try {
    await processSchedule(row.id, typeId)
    ElMessage.success(
      typeId === 1
        ? 'Examination schedule posted successfully.'
        : 'Examination schedule unposted successfully.'
    )
    await fetchSchedules()
  } catch (e) {
    const msg = e?.response?.data?.message || 'Failed to update examination schedule status.'
    ElMessage.error(msg)
  } finally {
    processingId.value = null
  }
}
</script>

<style scoped>
</style>


