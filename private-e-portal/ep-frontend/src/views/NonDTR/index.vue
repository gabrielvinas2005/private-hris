<template>
  <MainLayout :breadcrumbs="breadcrumbs">
    <div v-if="accessLoaded && hasAccess" class="cos-page max-w-5xl mx-auto">
      <div class="page-header">
        <div>
          <h1 class="page-title">COS Accomplishment Report</h1>
          <p class="page-subtitle">Select a payroll period and file a report for each cutoff half.</p>
        </div>
        <button
          v-if="isDivisionHead"
          type="button"
          class="text-btn"
          @click="goToDivisionHeadReview"
        >
          Division Head Review
        </button>
      </div>

      <!-- Period list -->
      <div v-if="viewMode === 'list'" class="data-panel" v-loading="loading">
        <div v-if="payrollGroups.length === 0 && !loading" class="empty-state">
          <p>No COS payroll periods are available yet.</p>
        </div>

        <table v-else class="data-table">
          <thead>
            <tr>
              <th>Payroll Period</th>
              <th>Cutoffs</th>
              <th>Reports Filed</th>
              <th class="col-action"></th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="group in payrollGroups"
              :key="group.group_key"
              class="data-row"
              @click="openPayrollGroup(group)"
            >
              <td>
                <span class="cell-primary">{{ group.label }}</span>
              </td>
              <td>{{ group.halves.length }}</td>
              <td>{{ countReportsInGroup(group) }} / {{ group.halves.length }}</td>
              <td class="col-action">
                <span class="row-link">Open</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Period detail -->
      <div v-else v-loading="loading">
        <nav class="breadcrumb">
          <button type="button" class="breadcrumb-link" @click="backToPeriodList">Payroll Periods</button>
          <span class="breadcrumb-sep">/</span>
          <span class="breadcrumb-current">{{ selectedGroup?.label }}</span>
        </nav>

        <div class="detail-summary">
          <p>{{ countReportsInGroup(selectedGroup) }} of {{ selectedGroup?.halves?.length || 0 }} report(s) filed</p>
        </div>

        <div class="data-panel">
          <table class="data-table">
            <thead>
              <tr>
                <th>Half</th>
                <th>Date Range</th>
                <th>Status</th>
                <th>Summary</th>
                <th class="col-action">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="half in selectedGroup?.halves || []" :key="half.id">
                <td>
                  <span class="cell-primary">{{ half.half_label }}</span>
                  <span v-if="half.is_active" class="meta-note">Active</span>
                </td>
                <td class="cell-muted">
                  {{ half.date_range_label || formatDateRange(half.attendance_start_date, half.attendance_end_date) }}
                </td>
                <td>
                  <span class="status-text">
                    {{ half.accomplishment_report ? formatStatus(half.accomplishment_report.status) : 'Not filed' }}
                  </span>
                </td>
                <td class="cell-muted">
                  <template v-if="half.accomplishment_report">
                    {{ half.accomplishment_report.entries_count }} day(s)
                  </template>
                  <template v-else>—</template>
                </td>
                <td class="col-action">
                  <div class="action-group" @click.stop>
                    <button
                      v-if="!half.accomplishment_report && canSubmitReport"
                      type="button"
                      class="text-btn"
                      @click="showCreateDialog(half)"
                    >
                      Create
                    </button>
                    <template v-else-if="half.accomplishment_report">
                      <button type="button" class="text-btn" @click="viewReport(half.accomplishment_report)">View</button>
                      <button type="button" class="text-btn" @click="printReport(half.accomplishment_report)">Print</button>
                      <button
                        v-if="half.accomplishment_report.status !== 'approved'"
                        type="button"
                        class="text-btn"
                        @click="editReport(half.accomplishment_report)"
                      >
                        Edit
                      </button>
                      <button
                        v-if="half.accomplishment_report.status === 'approved'"
                        type="button"
                        class="text-btn"
                        @click="openAttachmentDialog(half.accomplishment_report)"
                      >
                        {{ half.accomplishment_report.attachments_count > 0 ? 'Edit' : 'Upload' }}
                      </button>
                      <button
                        v-if="half.accomplishment_report.status !== 'approved'"
                        type="button"
                        class="text-btn text-btn-danger"
                        @click="deleteReport(half.accomplishment_report)"
                      >
                        Delete
                      </button>
                    </template>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Create/Edit Dialog -->
      <el-dialog
        v-model="dialogVisible"
        width="720px"
        class="report-dialog"
        :show-close="true"
        :close-on-click-modal="false"
      >
        <template #header>
          <div class="dialog-header">
            <h2 class="dialog-title">{{ isEditMode ? 'Edit Accomplishment Report' : 'New Accomplishment Report' }}</h2>
            <p v-if="activeHalf" class="dialog-subtitle">
              {{ activeHalf.half_label }} · {{ activeHalf.date_range_label || formatDateRange(activeHalf.attendance_start_date, activeHalf.attendance_end_date) }}
            </p>
          </div>
        </template>

        <el-form
          ref="formRef"
          :model="form"
          :rules="rules"
          label-position="right"
          label-width="150px"
          size="small"
          class="report-form"
          v-loading="submitting"
        >
          <section class="form-section">
            <h3 class="section-heading">Report Information</h3>
            <el-form-item label="Report Period" prop="period">
              <el-date-picker
                v-model="form.period"
                type="daterange"
                range-separator="to"
                start-placeholder="Start"
                end-placeholder="End"
                format="YYYY-MM-DD"
                value-format="YYYY-MM-DD"
                :disabled="!!form.payroll_period_id"
                style="width: 100%"
              />
            </el-form-item>
          </section>

          <section class="form-section">
            <h3 class="section-heading">Tasks & Responsibilities</h3>
            <el-form-item label="Main Tasks" prop="task_1">
              <el-input
                v-model="form.task_1"
                type="textarea"
                :rows="2"
                placeholder="Main tasks and responsibilities"
              />
            </el-form-item>
            <el-form-item label="Secondary Tasks">
              <el-input v-model="form.task_2" placeholder="Optional" />
            </el-form-item>
            <el-form-item label="Additional Tasks">
              <el-input v-model="form.task_3" placeholder="Optional" />
            </el-form-item>
          </section>

          <section class="form-section">
            <div class="section-heading-row">
              <h3 class="section-heading">Daily Work Entries</h3>
              <button type="button" class="text-btn" @click="addEntry">+ Add day</button>
            </div>

            <el-collapse v-model="expandedEntries" class="entry-collapse">
              <el-collapse-item
                v-for="(entry, index) in form.entries"
                :key="index"
                :name="index"
              >
                <template #title>
                  <span class="entry-collapse-title">
                    Day {{ index + 1 }}
                    <span v-if="entry.work_date" class="entry-collapse-date">— {{ formatDate(entry.work_date) }}</span>
                  </span>
                </template>

                <div class="entry-fields">
                  <el-row :gutter="12">
                    <el-col :span="12">
                      <el-form-item
                        label="Work Date"
                        :prop="'entries.' + index + '.work_date'"
                        :rules="[{ required: true, message: 'Required', trigger: 'blur' }]"
                        label-width="100px"
                      >
                        <el-date-picker
                          v-model="entry.work_date"
                          type="date"
                          placeholder="Date"
                          format="YYYY-MM-DD"
                          value-format="YYYY-MM-DD"
                          style="width: 100%"
                        />
                      </el-form-item>
                    </el-col>
                    <el-col :span="24">
                      <el-form-item label="Location" label-width="100px">
                        <el-input v-model="entry.location" placeholder="Work location" />
                      </el-form-item>
                    </el-col>
                    <el-col :span="24">
                      <el-form-item
                        label="Accomplishments"
                        :prop="'entries.' + index + '.accomplishments'"
                        :rules="[{ required: true, message: 'Required', trigger: 'blur' }]"
                        label-width="100px"
                      >
                        <el-input
                          v-model="entry.accomplishments"
                          type="textarea"
                          :rows="2"
                          placeholder="What was accomplished"
                        />
                      </el-form-item>
                    </el-col>
                    <el-col :span="24">
                      <el-form-item label="Output" label-width="100px">
                        <el-input
                          v-model="entry.output_description"
                          type="textarea"
                          :rows="2"
                          placeholder="Output or deliverables"
                        />
                      </el-form-item>
                    </el-col>
                  </el-row>
                  <div v-if="form.entries.length > 1" class="entry-remove">
                    <button type="button" class="text-btn text-btn-danger" @click="removeEntry(index)">Remove this day</button>
                  </div>
                </div>
              </el-collapse-item>
            </el-collapse>
          </section>
        </el-form>

        <template #footer>
          <div class="dialog-footer">
            <el-button size="small" @click="dialogVisible = false">Cancel</el-button>
            <el-button
              v-if="canSaveDraft"
              size="small"
              @click="saveDraft"
              :loading="savingDraft"
            >
              Save Draft
            </el-button>
            <el-button
              v-if="canSubmitFromForm"
              size="small"
              type="primary"
              @click="submitForm"
              :loading="submitting"
            >
              Submit Report
            </el-button>
            <el-button
              v-else-if="isEditMode"
              size="small"
              type="primary"
              @click="savePendingChanges"
              :loading="submitting"
            >
              Save Changes
            </el-button>
          </div>
        </template>
      </el-dialog>

      <!-- View Dialog -->
      <el-dialog
        v-model="viewDialogVisible"
        title="Report Details"
        width="720px"
        class="report-dialog"
      >
        <div v-if="selectedReport" v-loading="loadingDetail" class="view-report">
          <table class="meta-table">
            <tbody>
              <tr><th>Employee</th><td>{{ selectedReport.employee_name }}</td></tr>
              <tr><th>Status</th><td>{{ formatStatus(selectedReport.status) }}</td></tr>
              <tr><th>Period</th><td>{{ formatDateRange(selectedReport.period_from, selectedReport.period_to) }}</td></tr>
              <tr><th>Submitted</th><td>{{ formatDateTime(selectedReport.created_at) }}</td></tr>
              <tr v-if="selectedReport.approved_by_name"><th>Approved By</th><td>{{ selectedReport.approved_by_name }}</td></tr>
            </tbody>
          </table>

          <section v-if="selectedReport.task_1 || selectedReport.task_2 || selectedReport.task_3" class="view-section">
            <h3 class="section-heading">Tasks</h3>
            <dl class="detail-list">
              <template v-if="selectedReport.task_1">
                <dt>Main</dt><dd>{{ selectedReport.task_1 }}</dd>
              </template>
              <template v-if="selectedReport.task_2">
                <dt>Secondary</dt><dd>{{ selectedReport.task_2 }}</dd>
              </template>
              <template v-if="selectedReport.task_3">
                <dt>Additional</dt><dd>{{ selectedReport.task_3 }}</dd>
              </template>
            </dl>
          </section>

          <section class="view-section">
            <h3 class="section-heading">Daily Entries</h3>
            <table class="data-table data-table-compact">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Location</th>
                  <th>Accomplishments</th>
                  <th>Output</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(entry, index) in selectedReport.entries" :key="index">
                  <td>{{ formatDate(entry.work_date) }}</td>
                  <td>{{ entry.location || '—' }}</td>
                  <td>{{ entry.accomplishments }}</td>
                  <td>{{ entry.output_description || '—' }}</td>
                </tr>
              </tbody>
            </table>
          </section>

          <section v-if="selectedReport.attachments?.length" class="view-section">
            <h3 class="section-heading">Approved Report Attachments</h3>
            <ul class="plain-list">
              <li v-for="attachment in selectedReport.attachments" :key="attachment.id">
                {{ attachment.file_name }}
              </li>
            </ul>
          </section>
        </div>
        <template #footer>
          <div class="dialog-footer">
            <el-button size="small" @click="viewDialogVisible = false">Close</el-button>
            <el-button
              v-if="selectedReport?.status === 'approved'"
              size="small"
              type="warning"
              @click="openAttachmentDialogFromView"
            >
              Upload Attachment
            </el-button>
            <el-button size="small" type="primary" @click="printReport(selectedReport)">Print Report</el-button>
          </div>
        </template>
      </el-dialog>

      <!-- Approved Report Attachment Dialog -->
      <el-dialog
        v-model="attachmentDialogVisible"
        title="Upload Approved Report"
        width="560px"
        class="report-dialog"
        @closed="resetAttachmentDialog"
      >
        <p v-if="attachmentReport" class="dialog-subtitle mb-4">
          Upload the signed or stamped approved report for this cutoff half.
        </p>
        <div v-loading="attachmentDialogLoading">
          <div v-if="attachmentList.length" class="attachment-list mb-4">
            <span
              v-for="attachment in attachmentList"
              :key="attachment.id"
              class="attachment-item"
            >
              {{ attachment.file_name }}
              <button type="button" class="attachment-remove" @click="deleteApprovedAttachment(attachment.id)">×</button>
            </span>
          </div>
          <el-upload
            ref="attachmentUploadRef"
            :auto-upload="false"
            :on-change="handleAttachmentFileChange"
            :on-remove="handleAttachmentFileRemove"
            :file-list="attachmentFileList"
            multiple
            :limit="10"
          >
            <button type="button" class="text-btn">Choose files</button>
            <template #tip>
              <p class="upload-hint">PDF, JPG, PNG, DOC, DOCX — up to 10 files, 10MB each</p>
            </template>
          </el-upload>
        </div>
        <template #footer>
          <div class="dialog-footer">
            <el-button size="small" @click="attachmentDialogVisible = false">Cancel</el-button>
            <el-button
              size="small"
              type="primary"
              :loading="attachmentUploading"
              :disabled="attachmentFilesToUpload.length === 0"
              @click="saveApprovedAttachments"
            >
              Upload
            </el-button>
          </div>
        </template>
      </el-dialog>

      <!-- Print Preview Dialog -->
      <el-dialog
        v-model="previewVisible"
        title="Accomplishment Report Preview"
        width="80%"
        :close-on-click-modal="false"
        @close="closePreview"
      >
        <div v-if="previewLoading" class="preview-loading">
          <span class="preview-loading-text">Generating PDF...</span>
        </div>
        <div v-else class="preview-frame-wrap">
          <iframe :src="previewUrl" ref="previewFrame" class="preview-frame"></iframe>
        </div>
        <template #footer>
          <div class="dialog-footer preview-footer">
            <el-button size="small" @click="downloadPreview" :disabled="!previewUrl">Download</el-button>
            <el-button size="small" type="primary" @click="printPreview" :disabled="!previewUrl">Print</el-button>
            <el-button size="small" @click="closePreview">Close</el-button>
          </div>
        </template>
      </el-dialog>
    </div>
    <div v-else-if="!accessLoaded" class="p-6 text-center text-slate-600">
      Loading access...
    </div>
    <div v-else class="p-6 text-center text-slate-600">
      COS Accomplishment Report is not available. It will appear when a payroll period for COS is created.
    </div>
  </MainLayout>
</template>

<script>
import MainLayout from '../../layout/MainLayout.vue'
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import api from '../../services/api'
export default {
  name: 'NonDTRReview',
  components: { MainLayout },
  setup() {
    const router = useRouter()
    const breadcrumbs = [
      { name: 'Dashboard', path: '/dashboard' },
      { name: 'Time and Attendance', path: '/time-attendance' },
      { name: 'COS Accomplishment Report', path: '/review-dtr' }
    ]

    const hasAccess = ref(false)
    const accessLoaded = ref(false)
    const canSubmitReport = ref(false)
    const viewMode = ref('list')
    const payrollGroups = ref([])
    const selectedGroup = ref(null)
    const activeHalf = ref(null)

    const expandedEntries = ref([0])

    const loading = ref(false)
    const submitting = ref(false)
    const savingDraft = ref(false)
    const loadingDetail = ref(false)
    const dialogVisible = ref(false)
    const viewDialogVisible = ref(false)
    const isEditMode = ref(false)
    const isDivisionHead = ref(false)
    const selectedReport = ref(null)
    const editingReportStatus = ref(null)
    const formRef = ref(null)
    const previewVisible = ref(false)
    const previewLoading = ref(false)
    const previewUrl = ref('')
    const previewFrame = ref(null)
    const previewReportId = ref(null)

    const attachmentDialogVisible = ref(false)
    const attachmentDialogLoading = ref(false)
    const attachmentUploading = ref(false)
    const attachmentReport = ref(null)
    const attachmentList = ref([])
    const attachmentUploadRef = ref(null)
    const attachmentFileList = ref([])
    const attachmentFilesToUpload = ref([])

    const form = reactive({
      payroll_period_id: null,
      period: [],
      task_1: '',
      task_2: '',
      task_3: '',
      entries: [createEmptyEntry()]
    })

    const rules = {
      period: [
        { required: true, message: 'Report period is required', trigger: 'change' }
      ],
      task_1: [
        { required: true, message: 'Main tasks are required', trigger: 'blur' }
      ]
    }

    const canSaveDraft = computed(() => {
      if (!isEditMode.value) return true
      return ['draft', 'rejected'].includes(editingReportStatus.value)
    })

    const canSubmitFromForm = computed(() => {
      if (!isEditMode.value) return true
      return ['draft', 'rejected'].includes(editingReportStatus.value)
    })

    function createEmptyEntry() {
      return {
        work_date: '',
        accomplishments: '',
        output_description: '',
        location: '',
        payroll_schedule_header_id: null
      }
    }

    function addEntry() {
      form.entries.push(createEmptyEntry())
      expandedEntries.value = [form.entries.length - 1]
    }

    function removeEntry(index) {
      if (form.entries.length > 1) {
        form.entries.splice(index, 1)
        expandedEntries.value = [Math.max(0, index - 1)]
      }
    }

    async function loadPayrollPeriods() {
      loading.value = true
      try {
        const response = await api.getEmployeeNonDTRPayrollPeriods()
        payrollGroups.value = response.data || []

        if (selectedGroup.value) {
          const refreshed = payrollGroups.value.find(
            (group) => group.group_key === selectedGroup.value.group_key
          )
          selectedGroup.value = refreshed || null
          if (!selectedGroup.value) {
            viewMode.value = 'list'
          }
        }
      } catch (error) {
        ElMessage.error('Failed to load COS payroll periods')
        console.error('Error loading payroll periods:', error)
      } finally {
        loading.value = false
      }
    }

    function openPayrollGroup(group) {
      selectedGroup.value = group
      viewMode.value = 'detail'
    }

    function backToPeriodList() {
      viewMode.value = 'list'
      selectedGroup.value = null
    }

    function countReportsInGroup(group) {
      if (!group) return 0
      return (group.halves || []).filter((half) => half.accomplishment_report).length
    }

    function goToDivisionHeadReview() {
      router.push('/division-head-non-dtr')
    }

    async function loadPortalAccess() {
      try {
        const response = await api.checkEmployeeNonDTRAccess()
        if (response?.success) {
          hasAccess.value = !!response.data?.has_access
          canSubmitReport.value = !!response.data?.can_submit_report
          isDivisionHead.value = !!response.data?.can_review_reports
        } else {
          hasAccess.value = false
        }
      } catch (error) {
        hasAccess.value = false
        console.error('Error checking COS accomplishment access:', error)
      } finally {
        accessLoaded.value = true
      }
    }

    async function showCreateDialog(half) {
      if (!half?.id) return

      isEditMode.value = false
      activeHalf.value = half
      resetForm()
      form.payroll_period_id = half.id

      try {
        const response = await api.getEmployeeNonDTRFormData(half.id)
        const period = response.data?.payroll_period
        if (period?.attendance_start_date && period?.attendance_end_date) {
          form.period = [period.attendance_start_date, period.attendance_end_date]
        }
        const scheduleHeaderId = response.data?.payroll_schedule_header_id
        if (scheduleHeaderId) {
          form.entries.forEach((entry) => {
            entry.payroll_schedule_header_id = scheduleHeaderId
          })
        }
      } catch (error) {
        ElMessage.error('Failed to load COS payroll period details')
        return
      }
      dialogVisible.value = true
    }

    function resetForm() {
      form.payroll_period_id = null
      form.period = []
      form.task_1 = ''
      form.task_2 = ''
      form.task_3 = ''
      form.entries = [createEmptyEntry()]
      expandedEntries.value = [0]
      editingReportStatus.value = null
      delete form.id
      if (formRef.value) {
        formRef.value.clearValidate()
      }
    }

    async function editReport(report) {
      isEditMode.value = true
      loadingDetail.value = true
      activeHalf.value = findHalfForReport(report)
      try {
        const response = await api.getEmployeeNonDTR(report.id)
        const data = response.data
        
        form.payroll_period_id = data.payroll_period_id || activeHalf.value?.id || null
        form.period = [data.period_from, data.period_to]
        form.task_1 = data.task_1 || ''
        form.task_2 = data.task_2 || ''
        form.task_3 = data.task_3 || ''
        form.entries = data.entries && data.entries.length > 0 
          ? data.entries.map(e => ({
              work_date: e.work_date,
              accomplishments: e.accomplishments || '',
              output_description: e.output_description || '',
              location: e.location || '',
              payroll_schedule_header_id: e.payroll_schedule_header_id || null
            }))
          : [createEmptyEntry()]

        form.id = report.id
        editingReportStatus.value = data.status || report.status || null
        dialogVisible.value = true
      } catch (error) {
        ElMessage.error('Failed to load report details')
        console.error('Error loading report:', error)
      } finally {
        loadingDetail.value = false
      }
    }

    async function viewReport(report) {
      loadingDetail.value = true
      viewDialogVisible.value = true
      try {
        const response = await api.getEmployeeNonDTR(report.id)
        selectedReport.value = response.data
      } catch (error) {
        ElMessage.error('Failed to load report details')
        console.error('Error loading report:', error)
        viewDialogVisible.value = false
      } finally {
        loadingDetail.value = false
      }
    }

    function buildPayload(saveAsDraft = false) {
      const payload = {
        period_from: form.period[0],
        period_to: form.period[1],
        task_1: form.task_1,
        task_2: form.task_2,
        task_3: form.task_3,
        entries: form.entries,
        save_as_draft: saveAsDraft
      }

      if (form.payroll_period_id) {
        payload.payroll_period_id = form.payroll_period_id
      }

      return payload
    }

    async function persistTaskData(saveAsDraft) {
      const payload = buildPayload(saveAsDraft)

      if (isEditMode.value) {
        await api.updateEmployeeNonDTR(form.id, payload)
        return form.id
      }

      const response = await api.createEmployeeNonDTR(payload)
      const taskId = response.data?.id
      if (!taskId) {
        throw new Error('Report was saved but no report id was returned')
      }

      isEditMode.value = true
      form.id = taskId
      editingReportStatus.value = 'draft'
      return taskId
    }

    async function saveDraft() {
      if (!form.period?.length) {
        ElMessage.error('Report period is required')
        return
      }

      savingDraft.value = true
      try {
        await persistTaskData(true)
        ElMessage.success('Draft saved successfully')
        dialogVisible.value = false
        activeHalf.value = null
        await loadPayrollPeriods()
      } catch (error) {
        ElMessage.error('Failed to save draft')
        console.error('Error saving draft:', error)
      } finally {
        savingDraft.value = false
      }
    }

    async function savePendingChanges() {
      if (!formRef.value) return

      try {
        await formRef.value.validate()
      } catch (error) {
        ElMessage.error('Please fill in all required fields')
        return
      }

      submitting.value = true
      try {
        await persistTaskData(false)
        ElMessage.success('Accomplishment report updated successfully')
        dialogVisible.value = false
        activeHalf.value = null
        await loadPayrollPeriods()
      } catch (error) {
        ElMessage.error('Failed to update report')
        console.error('Error updating report:', error)
      } finally {
        submitting.value = false
      }
    }

    async function submitForm() {
      if (!formRef.value) return

      try {
        await formRef.value.validate()
      } catch (error) {
        ElMessage.error('Please fill in all required fields')
        return
      }

      submitting.value = true
      try {
        const taskId = await persistTaskData(true)

        const submitPayload = buildPayload(false)
        delete submitPayload.save_as_draft
        await api.submitEmployeeNonDTR(taskId, submitPayload)

        ElMessage.success('Accomplishment report submitted successfully')
        dialogVisible.value = false
        activeHalf.value = null
        await loadPayrollPeriods()
        await printReport({ id: taskId })
      } catch (error) {
        ElMessage.error('Failed to submit report')
        console.error('Error submitting report:', error)
      } finally {
        submitting.value = false
      }
    }

    async function deleteReport(report) {
      try {
        await ElMessageBox.confirm(
          'Are you sure you want to delete this accomplishment report?',
          'Confirm Delete',
          {
            confirmButtonText: 'Delete',
            cancelButtonText: 'Cancel',
            type: 'warning',
          }
        )

        await api.deleteEmployeeNonDTR(report.id)
        ElMessage.success('Report deleted successfully')
        loadPayrollPeriods()
      } catch (error) {
        if (error !== 'cancel') {
          ElMessage.error('Failed to delete report')
          console.error('Error deleting report:', error)
        }
      }
    }

    async function printReport(report) {
      const id = report?.id
      if (!id) return

      try {
        previewReportId.value = id
        previewVisible.value = true
        previewLoading.value = true

        const blob = await api.printAccomplishmentReport(id)
        if (!blob) {
          previewLoading.value = false
          ElMessage.error('Failed to generate accomplishment report')
          return
        }

        if (previewUrl.value) {
          URL.revokeObjectURL(previewUrl.value)
        }
        previewUrl.value = URL.createObjectURL(blob)
        previewLoading.value = false
      } catch (error) {
        previewLoading.value = false
        ElMessage.error('Failed to generate accomplishment report')
        console.error('Error printing report:', error)
      }
    }

    function downloadPreview() {
      if (!previewUrl.value) return
      const a = document.createElement('a')
      a.href = previewUrl.value
      a.download = `accomplishment_report_${previewReportId.value || 'preview'}.pdf`
      document.body.appendChild(a)
      a.click()
      document.body.removeChild(a)
    }

    function printPreview() {
      if (!previewFrame.value) return
      try {
        previewFrame.value.contentWindow?.focus()
        previewFrame.value.contentWindow?.print()
      } catch (error) {
        // ignore
      }
    }

    function closePreview() {
      previewVisible.value = false
      previewLoading.value = false
      previewReportId.value = null
      if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value)
        previewUrl.value = ''
      }
    }

    async function openAttachmentDialog(report) {
      if (!report?.id || report.status !== 'approved') return
      attachmentReport.value = report
      attachmentDialogVisible.value = true
      attachmentDialogLoading.value = true
      try {
        const response = await api.getEmployeeNonDTR(report.id)
        attachmentList.value = response.data?.attachments || []
      } catch (error) {
        ElMessage.error('Failed to load attachments')
        attachmentDialogVisible.value = false
        console.error('Error loading attachments:', error)
      } finally {
        attachmentDialogLoading.value = false
      }
    }

    function openAttachmentDialogFromView() {
      if (!selectedReport.value) return
      viewDialogVisible.value = false
      openAttachmentDialog(selectedReport.value)
    }

    function resetAttachmentDialog() {
      attachmentReport.value = null
      attachmentList.value = []
      attachmentFileList.value = []
      attachmentFilesToUpload.value = []
    }

    function handleAttachmentFileChange(file, uploadFileList) {
      const maxSize = 10 * 1024 * 1024
      if (file.size > maxSize) {
        ElMessage.error(`File ${file.name} is too large. Maximum size is 10MB.`)
        uploadFileList.pop()
        return
      }
      attachmentFileList.value = uploadFileList
      attachmentFilesToUpload.value = uploadFileList.map(f => f.raw)
    }

    function handleAttachmentFileRemove(file, uploadFileList) {
      attachmentFileList.value = uploadFileList
      attachmentFilesToUpload.value = uploadFileList.map(f => f.raw)
    }

    async function saveApprovedAttachments() {
      if (!attachmentReport.value?.id || attachmentFilesToUpload.value.length === 0) return

      attachmentUploading.value = true
      let uploadedCount = 0
      try {
        for (const file of attachmentFilesToUpload.value) {
          const formData = new FormData()
          formData.append('file', file)
          formData.append('non_dtr_task_id', attachmentReport.value.id)
          await api.uploadNonDTRAttachment(formData)
          uploadedCount += 1
        }

        ElMessage.success(`${uploadedCount} file(s) uploaded successfully`)
        attachmentFileList.value = []
        attachmentFilesToUpload.value = []
        const response = await api.getEmployeeNonDTR(attachmentReport.value.id)
        attachmentList.value = response.data?.attachments || []
        await loadPayrollPeriods()
      } catch (error) {
        ElMessage.error(error?.response?.data?.message || 'Failed to upload attachment')
        console.error('Error uploading attachment:', error)
      } finally {
        attachmentUploading.value = false
      }
    }

    async function deleteApprovedAttachment(attachmentId) {
      try {
        await ElMessageBox.confirm(
          'Remove this attachment?',
          'Delete Attachment',
          {
            confirmButtonText: 'Delete',
            cancelButtonText: 'Cancel',
            type: 'warning'
          }
        )

        await api.deleteNonDTRAttachment(attachmentId)
        ElMessage.success('Attachment deleted successfully')
        attachmentList.value = attachmentList.value.filter(a => a.id !== attachmentId)
        await loadPayrollPeriods()
      } catch (error) {
        if (error !== 'cancel') {
          ElMessage.error(error?.response?.data?.message || 'Failed to delete attachment')
          console.error('Error deleting attachment:', error)
        }
      }
    }

    function formatStatus(status) {
      if (!status) return '—'
      if (status === 'draft') return 'Draft'
      if (status === 'rejected') return 'Returned'
      return status.charAt(0).toUpperCase() + status.slice(1)
    }

    function formatDateRange(from, to) {
      if (!from || !to) return 'N/A'
      return `${formatDate(from)} - ${formatDate(to)}`
    }

    function formatDate(date) {
      if (!date) return 'N/A'
      return new Date(date).toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric' 
      })
    }

    function formatDateTime(datetime) {
      if (!datetime) return 'N/A'
      return new Date(datetime).toLocaleString('en-US', { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      })
    }

    function truncateText(text, maxLength) {
      if (!text) return ''
      return text.length > maxLength ? text.substring(0, maxLength) + '...' : text
    }

    function findHalfForReport(report) {
      if (!report) return null
      for (const group of payrollGroups.value) {
        for (const half of group.halves || []) {
          if (half.accomplishment_report?.id === report.id) {
            return half
          }
        }
      }
      return null
    }

    onMounted(async () => {
      await loadPortalAccess()
      if (!hasAccess.value) return
      await loadPayrollPeriods()
    })

    return {
      breadcrumbs,
      hasAccess,
      accessLoaded,
      canSubmitReport,
      viewMode,
      payrollGroups,
      selectedGroup,
      activeHalf,
      expandedEntries,
      loading,
      submitting,
      savingDraft,
      loadingDetail,
      dialogVisible,
      viewDialogVisible,
      isEditMode,
      isDivisionHead,
      selectedReport,
      formRef,
      attachmentDialogVisible,
      attachmentDialogLoading,
      attachmentUploading,
      attachmentReport,
      attachmentList,
      attachmentUploadRef,
      attachmentFileList,
      attachmentFilesToUpload,
      canSaveDraft,
      canSubmitFromForm,
      previewVisible,
      previewLoading,
      previewUrl,
      previewFrame,
      form,
      rules,
      addEntry,
      removeEntry,
      openPayrollGroup,
      backToPeriodList,
      countReportsInGroup,
      showCreateDialog,
      editReport,
      viewReport,
      saveDraft,
      savePendingChanges,
      printReport,
      downloadPreview,
      printPreview,
      closePreview,
      submitForm,
      deleteReport,
      openAttachmentDialog,
      openAttachmentDialogFromView,
      resetAttachmentDialog,
      saveApprovedAttachments,
      deleteApprovedAttachment,
      handleAttachmentFileChange,
      handleAttachmentFileRemove,
      goToDivisionHeadReview,
      formatStatus,
      formatDateRange,
      formatDate,
      formatDateTime,
      truncateText
    }
  }
}
</script>

<style scoped>
.cos-page {
  color: #1e293b;
}

.page-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.page-title {
  font-size: 1.5rem;
  font-weight: 600;
  color: #0f172a;
}

.page-subtitle {
  margin-top: 0.25rem;
  font-size: 0.875rem;
  color: #64748b;
}

.data-panel {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 4px;
  overflow: hidden;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.875rem;
}

.data-table th,
.data-table td {
  padding: 0.75rem 1rem;
  text-align: left;
  border-bottom: 1px solid #e2e8f0;
  vertical-align: top;
}

.data-table th {
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.03em;
  color: #64748b;
  background: #f8fafc;
}

.data-table tbody tr:last-child td {
  border-bottom: none;
}

.data-row {
  cursor: pointer;
}

.data-row:hover td {
  background: #f8fafc;
}

.data-table-compact th,
.data-table-compact td {
  padding: 0.5rem 0.75rem;
  font-size: 0.8125rem;
}

.col-action {
  width: 1%;
  white-space: nowrap;
  text-align: right;
}

.cell-primary {
  display: block;
  font-weight: 500;
  color: #0f172a;
}

.cell-muted {
  color: #64748b;
}

.meta-note {
  display: block;
  margin-top: 0.125rem;
  font-size: 0.75rem;
  color: #94a3b8;
}

.status-text {
  font-size: 0.8125rem;
  color: #475569;
}

.row-link {
  font-size: 0.8125rem;
  color: #475569;
  text-decoration: underline;
}

.action-group {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-end;
  gap: 0.5rem 0.75rem;
}

.text-btn {
  padding: 0;
  border: none;
  background: none;
  font-size: 0.8125rem;
  color: #334155;
  text-decoration: underline;
  cursor: pointer;
}

.text-btn:hover {
  color: #0f172a;
}

.text-btn-danger {
  color: #64748b;
}

.breadcrumb {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.75rem;
  font-size: 0.8125rem;
}

.breadcrumb-link {
  padding: 0;
  border: none;
  background: none;
  color: #64748b;
  text-decoration: underline;
  cursor: pointer;
}

.breadcrumb-sep {
  color: #cbd5e1;
}

.breadcrumb-current {
  color: #0f172a;
}

.detail-summary {
  margin-bottom: 1rem;
  font-size: 0.875rem;
  color: #64748b;
}

.empty-state {
  padding: 3rem 1rem;
  text-align: center;
  font-size: 0.875rem;
  color: #64748b;
}

.dialog-header {
  padding-right: 1.5rem;
}

.dialog-title {
  font-size: 1rem;
  font-weight: 600;
  color: #0f172a;
}

.dialog-subtitle {
  margin-top: 0.25rem;
  font-size: 0.8125rem;
  color: #64748b;
}

.form-section {
  margin-bottom: 1.25rem;
  padding-bottom: 1.25rem;
  border-bottom: 1px solid #e2e8f0;
}

.form-section:last-child {
  margin-bottom: 0;
  padding-bottom: 0;
  border-bottom: none;
}

.section-heading {
  margin: 0 0 0.75rem;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: #64748b;
}

.section-heading-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 0.75rem;
}

.section-heading-row .section-heading {
  margin-bottom: 0;
}

.report-form :deep(.el-form-item) {
  margin-bottom: 0.75rem;
}

.report-form :deep(.el-form-item__label) {
  font-size: 0.8125rem;
  color: #475569;
}

.entry-collapse {
  border: 1px solid #e2e8f0;
  border-radius: 4px;
}

.entry-collapse :deep(.el-collapse-item__header) {
  height: auto;
  min-height: 40px;
  padding: 0 0.75rem;
  font-size: 0.8125rem;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
}

.entry-collapse :deep(.el-collapse-item__wrap) {
  border-bottom: none;
}

.entry-collapse-title {
  font-weight: 500;
  color: #334155;
}

.entry-collapse-date {
  font-weight: 400;
  color: #94a3b8;
}

.entry-fields {
  padding: 0.75rem 0.75rem 0.25rem;
}

.entry-remove {
  padding-bottom: 0.5rem;
  text-align: right;
}

.attachment-list {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-bottom: 0.75rem;
}

.attachment-item {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.25rem 0.5rem;
  font-size: 0.8125rem;
  border: 1px solid #e2e8f0;
  border-radius: 3px;
  background: #f8fafc;
}

.attachment-remove {
  padding: 0;
  border: none;
  background: none;
  font-size: 1rem;
  line-height: 1;
  color: #94a3b8;
  cursor: pointer;
}

.upload-hint {
  margin-top: 0.25rem;
  font-size: 0.75rem;
  color: #94a3b8;
}

.upload-hint-warning {
  color: #b45309;
}

.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
}

.preview-loading {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 200px;
}

.preview-loading-text {
  color: #64748b;
  font-size: 0.875rem;
}

.preview-frame-wrap {
  height: 80vh;
}

.preview-frame {
  width: 100%;
  height: 100%;
  border: 0;
}

.preview-footer {
  width: 100%;
}

.report-dialog :deep(.el-dialog__header) {
  padding-bottom: 0.5rem;
  margin-right: 0;
}

.report-dialog :deep(.el-dialog__body) {
  padding-top: 0.5rem;
}

.view-section {
  margin-top: 1.25rem;
}

.meta-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.875rem;
}

.meta-table th,
.meta-table td {
  padding: 0.5rem 0;
  border-bottom: 1px solid #e2e8f0;
  text-align: left;
  vertical-align: top;
}

.meta-table th {
  width: 140px;
  font-weight: 500;
  color: #64748b;
}

.detail-list {
  margin: 0;
  font-size: 0.875rem;
}

.detail-list dt {
  margin-top: 0.5rem;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.03em;
  color: #94a3b8;
}

.detail-list dd {
  margin: 0.25rem 0 0;
  color: #334155;
}

.plain-list {
  margin: 0;
  padding: 0;
  list-style: none;
  font-size: 0.875rem;
}

.plain-list li {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 0.375rem 0;
  border-bottom: 1px solid #f1f5f9;
}
</style>
