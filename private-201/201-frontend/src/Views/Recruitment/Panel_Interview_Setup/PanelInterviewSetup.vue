<template>
  <PageScaffold title="Applicant Interview Setup">
    <el-tabs v-model="activeTab" class="mb-4">
      <el-tab-pane label="Interview Setup" name="active">
        <PanelInterviewList
          :items="activeInterviews"
          :show-process-actions="true"
          :processing-id="processingId"
          @add="onAdd"
          @edit="onEdit"
          @assign="onAssign"
          @post="(row) => onProcess(row, 1)"
          @unpost="(row) => onProcess(row, 0)"
          @done="(row) => onProcess(row, 2)"
        />
      </el-tab-pane>

      <el-tab-pane label="Expired Interviews" name="expired">
        <PanelInterviewList
          :items="expiredInterviews"
          :show-process-actions="false"
          @view="onViewInterview"
          @delete="onDeleteExpiredInterview"
        />
      </el-tab-pane>

      <el-tab-pane label="Done Interviews" name="done">
        <PanelInterviewList
          :items="doneInterviews"
          :show-process-actions="false"
          @view="onViewInterview"
          @delete="onDeleteInterview"
        />
      </el-tab-pane>
    </el-tabs>

    <PanelInterviewForm
      v-model:visible="showForm"
      :model-value="form"
      :levels="levels"
      :saving="saving"
      @save="onSave"
    />

    <AssignmentsDialog
      v-model:visible="showAssign"
      :interview-id="currentId"
      :panels="panels"
      :applicants="applicants"
      :panel-select="panelSelect"
      :applicant-select="applicantSelect"
      :saving="saving"
      :read-only="assignReadOnly"
      :interview-summary="assignInterviewSummary"
      :panel-rating-breakdown="panelRatingBreakdown"
      :panel-review-attachments="panelReviewAttachments"
      @save-panels="onSavePanels"
      @save-applicants="onSaveApplicants"
      @remove-panel="onRemovePanel"
      @remove-applicant="onRemoveApplicant"
    />
  </PageScaffold>
</template>

<script setup>
import PageScaffold from '../../../components/PageScaffold.vue'
import PanelInterviewList from '../../../components/Recruitment/Panel_interview_setup/PanelInterviewList.vue'
import PanelInterviewForm from '../../../components/Recruitment/Panel_interview_setup/PanelInterviewForm.vue'
import AssignmentsDialog from '../../../components/Recruitment/Panel_interview_setup/AssignmentsDialog.vue'
import { usePanelInterview } from '../../../composables/usePanelInterview.js'
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'

const {
  interviews,
  form,
  levels,
  panels,
  applicants,
  panelSelect,
  applicantSelect,
  interviewLevelName,
  panelRatingBreakdown,
  panelReviewAttachments,
  fetchList,
  loadForm,
  resetForm,
  saveHeader,
  addPanels,
  addApplicants,
  deletePanel,
  deleteApplicant,
  deleteInterview,
  processInterview
} = usePanelInterview()

const showForm = ref(false)
const showAssign = ref(false)
const saving = ref(false)
const processingId = ref(null)
const activeTab = ref('active')
const assignReadOnly = ref(false)
let currentId = 0

// Re-evaluate expired tab while the page is open (auto-detect when end date/time passes)
const expiryCheckNow = ref(Date.now())
let expiryCheckTimer = null

onMounted(() => {
  fetchList()
  expiryCheckTimer = setInterval(() => {
    expiryCheckNow.value = Date.now()
  }, 60_000)
})

onUnmounted(() => {
  if (expiryCheckTimer) clearInterval(expiryCheckTimer)
})

const isPostedInterview = (row) => {
  if (!row) return false
  const v = row.posted
  return v === true || v === 1 || v === '1'
}

const isDoneFlag = (row) => {
  if (!row) return false
  const done = row.is_done
  return done === 1 || done === true || done === '1'
}

const parseScheduleEnd = (row) => {
  const endDate = row?.end_date
  if (!endDate) return null
  const timeMatch = String(row?.end_time || '').match(/^(\d{2}):(\d{2})/)
  const timePart = timeMatch ? `${timeMatch[1]}:${timeMatch[2]}` : '23:59'
  const parsed = new Date(`${endDate}T${timePart}:00`)
  return Number.isNaN(parsed.getTime()) ? null : parsed
}

const isSchedulePeriodPassed = (row, nowMs = expiryCheckNow.value) => {
  const end = parseScheduleEnd(row)
  return end ? end.getTime() < nowMs : false
}

/** Unposted schedules past end date/time — server flag + client auto-detection. */
const isExpiredInterview = (row) => {
  if (!row || isDoneFlag(row) || isPostedInterview(row)) return false
  const expired = row.is_expired
  if (expired === 1 || expired === true || expired === '1') return true
  return isSchedulePeriodPassed(row)
}

const isDoneInterview = (row) => {
  if (!row) return false
  if (isExpiredInterview(row)) return false
  // Newly created interviews have no applicants -> must show in Interview Setup (active), not Done
  const total = Number(row.applicant_count ?? 0)
  if (total === 0) return false
  // Backend is_done: 1 when all non-cancelled applicants are completed
  const done = row.is_done
  if (done !== undefined && done !== null) {
    const isDone = done === 1 || done === true || done === '1'
    return isDone
  }
  const pending = Number(row.pending_applicant_count ?? 0)
  return total > 0 && pending === 0
}

const expiredInterviews = computed(() => {
  expiryCheckNow.value // depend on timer so list moves to Expired without refresh
  return (interviews.value || []).filter(isExpiredInterview)
})
const doneInterviews = computed(() => (interviews.value || []).filter(isDoneInterview))
const activeInterviews = computed(() => {
  expiryCheckNow.value
  return (interviews.value || []).filter((r) => !isDoneInterview(r) && !isExpiredInterview(r))
})

const assignInterviewSummary = computed(() => ({
  panel_group: form.value.panel_group,
  interview_location: form.value.interview_location,
  description: form.value.description,
  level_name: interviewLevelName.value,
  start_date: form.value.start_date,
  end_date: form.value.end_date,
  start_time: form.value.start_time,
  end_time: form.value.end_time
}))

const onAdd = async () => {
  currentId = 0
  assignReadOnly.value = false
  resetForm()
  await loadForm(0)
  showForm.value = true
}

const onEdit = async (row) => {
  currentId = row.id
  await loadForm(row.id)
  showForm.value = true
}

const onAssign = async (row) => {
  currentId = row.id
  assignReadOnly.value = false
  await loadForm(row.id)
  showAssign.value = true
}

const onViewInterview = async (row) => {
  currentId = row.id
  assignReadOnly.value = true
  await loadForm(row.id)
  showAssign.value = true
}

const onDeleteExpiredInterview = async (row) => {
  try {
    await ElMessageBox.confirm(
      'Are you sure you want to delete this expired interview schedule? This action cannot be undone.',
      'Delete Expired Interview',
      {
        type: 'warning',
        confirmButtonText: 'Delete',
        cancelButtonText: 'Cancel'
      }
    )

    await deleteInterview(row.id)
    ElMessage.success('Expired interview schedule deleted successfully.')
    await fetchList()
  } catch (e) {
    if (e !== 'cancel' && e !== 'close') {
      const msg = e?.response?.data?.message || 'Failed to delete interview schedule.'
      ElMessage.error(msg)
    }
  }
}

const onDeleteInterview = async (row) => {
  try {
    await ElMessageBox.confirm(
      'Are you sure you want to delete this done interview schedule? This action cannot be undone.',
      'Delete Interview',
      {
        type: 'warning',
        confirmButtonText: 'Delete',
        cancelButtonText: 'Cancel'
      }
    )

    await deleteInterview(row.id)
    ElMessage.success('Interview schedule deleted successfully.')
    await fetchList()
  } catch (e) {
    if (e !== 'cancel' && e !== 'close') {
      const msg = e?.response?.data?.message || 'Failed to delete interview schedule.'
      ElMessage.error(msg)
    }
  }
}

const onSave = async (payload) => {
  saving.value = true
  try {
    // Normalize payload to satisfy backend validation
    const body = {
      panel_group: (payload.panel_group || '').toString().trim(),
      interview_location: (payload.interview_location || '').toString().trim(),
      description: payload.description ?? null,
      panel_group_level: (payload.panel_group_level ?? '').toString(),
      start_date: payload.start_date || '',
      end_date: payload.end_date || '',
      start_time: (payload.start_time || '').toString().slice(0,5), // HH:mm
      end_time: (payload.end_time || '').toString().slice(0,5) // HH:mm
    }

    // Client-side validation mirroring backend rules
    if (!body.panel_group || !body.interview_location || !body.panel_group_level || !body.start_date || !body.end_date || !body.start_time || !body.end_time) {
      ElMessage.error('Please complete all required fields.')
      return
    }
    const startDate = new Date(body.start_date)
    const endDate = new Date(body.end_date)
    if (endDate.getTime() < startDate.getTime()) {
      ElMessage.error('End date must be on/after Start date.')
      return
    }
    if (endDate.getTime() === startDate.getTime()) {
      const [sh, sm] = body.start_time.split(':').map(Number)
      const [eh, em] = body.end_time.split(':').map(Number)
      if ((eh * 60 + em) <= (sh * 60 + sm)) {
        ElMessage.error('End time must be after Start time.')
        return
      }
    }
    const id = await saveHeader(currentId, body)
    currentId = id || currentId
    showForm.value = false
    await fetchList()
  } catch (e) {
    // Try to surface validation errors from backend
    const errors = e?.response?.data?.errors
    if (errors && typeof errors === 'object') {
      const first = Object.values(errors).flat()[0]
      ElMessage.error(first || 'Validation failed. Please complete required fields.')
    } else {
      const msg = e?.response?.data?.message || 'Validation failed. Please complete required fields.'
      ElMessage.error(msg)
    }
    // Debug payload for tracing
    // eslint-disable-next-line no-console
    console.log('PanelInterview save payload failed:', payload)
  } finally {
    saving.value = false
  }
}

const onSavePanels = async ({ ids, select }) => {
  await addPanels(currentId, ids, select)
  await loadForm(currentId)
}

const onSaveApplicants = async ({ ids, select }) => {
  await addApplicants(currentId, ids, select)
  await loadForm(currentId)
}

const onRemovePanel = async (row) => {
  await deletePanel(row.id)
  await loadForm(currentId)
}

const onRemoveApplicant = async (row) => {
  await deleteApplicant(row.id)
  await loadForm(currentId)
}

const onProcess = async (row, typeId) => {
  processingId.value = row.id
  try {
    await processInterview(row.id, typeId)
    ElMessage.success(
      typeId === 1
        ? 'Interview schedule posted successfully.'
        : typeId === 2
          ? 'Interview marked as done successfully.'
          : 'Interview schedule unposted successfully.'
    )
    await fetchList()
  } catch (e) {
    const msg = e?.response?.data?.message || 'Failed to update interview posting status.'
    ElMessage.error(msg)
  } finally {
    processingId.value = null
  }
}
</script>

<style scoped>
</style>


