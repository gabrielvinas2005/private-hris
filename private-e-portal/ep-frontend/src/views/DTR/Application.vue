<template>
  <MainLayout :breadcrumbs="breadcrumbs">
    <div class="max-w-7xl mx-auto">
      <div class="mb-6">
        <h1 class="text-3xl font-bold text-slate-900 mb-2">DTR Application</h1>
        <p class="text-slate-600">Submit daily time record corrections for approval</p>
      </div>

      <div v-if="!allowed" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
        <p class="text-red-800">
          <span class="font-semibold">WARNING:</span> DTR approver is not set up for your account. Please contact HRD.
        </p>
      </div>

      <el-card class="mb-4" shadow="never">
        <div class="flex items-center gap-4 flex-wrap">
          <el-input
            v-model="search"
            placeholder="Search by date or status"
            clearable
            style="width: 280px"
          />
          <el-select v-model="statusFilter" placeholder="Filter by status" clearable style="width: 180px">
            <el-option label="Pending" value="Pending" />
            <el-option label="Pending Level 2" value="Pending Level 2" />
            <el-option label="Approved" value="Approved" />
            <el-option label="Returned" value="Returned" />
            <el-option label="Disapproved" value="Disapproved" />
          </el-select>
          <div class="ml-auto">
            <el-button type="primary" :disabled="!allowed || !employeeId" @click="openCreateDialog">
              + New DTR Application
            </el-button>
          </div>
        </div>
      </el-card>

      <el-card shadow="never" v-loading="loading">
        <el-table :data="filteredApplications" stripe empty-text="No DTR applications yet">
          <el-table-column prop="id" label="Ref #" width="80" />
          <el-table-column label="Request Date" width="140">
            <template #default="{ row }">{{ formatDate(row.request_date) }}</template>
          </el-table-column>
          <el-table-column prop="payroll_period" label="Payroll Period" min-width="200">
            <template #default="{ row }">{{ row.payroll_period || '—' }}</template>
          </el-table-column>
          <el-table-column label="Attachment" width="140">
            <template #default="{ row }">
              <el-button
                v-if="row.attachment_name"
                type="primary"
                link
                size="small"
                @click="previewApplicationAttachment(row.id, row.attachment_name)"
              >
                {{ row.attachment_name }}
              </el-button>
              <span v-else class="text-slate-400">—</span>
            </template>
          </el-table-column>
          <el-table-column prop="entries_count" label="Entries" width="90" align="center" />
          <el-table-column label="Status" width="150">
            <template #default="{ row }">
              <el-tag :type="statusTagType(row.status_label)" size="small">{{ row.status_label }}</el-tag>
            </template>
          </el-table-column>
          <el-table-column label="Actions" width="220" fixed="right">
            <template #default="{ row }">
              <el-button type="primary" link size="small" @click="viewApplication(row)">View</el-button>
              <el-button
                v-if="row.can_edit"
                type="warning"
                link
                size="small"
                @click="openEditDialog(row)"
              >
                Edit
              </el-button>
              <el-button
                v-if="row.status_label === 'Approved' && row.has_approved_dtr"
                type="success"
                link
                size="small"
                @click="openApprovedDtr(row)"
              >
                Approved DTR
              </el-button>
            </template>
          </el-table-column>
        </el-table>
      </el-card>

      <!-- New / Edit Application Dialog -->
      <el-dialog
        v-model="applicationDialogVisible"
        :title="applicationDialogMode === 'edit' ? 'Edit DTR Application' : 'New DTR Application'"
        width="560px"
        @closed="resetApplicationForm"
      >
        <el-form label-position="top">
          <el-form-item label="Payroll Period" required>
            <el-select
              v-model="applicationForm.payrollPeriodId"
              placeholder="Select payroll period"
              filterable
              class="w-full"
              :loading="loadingPeriods"
            >
              <el-option
                v-for="period in payrollPeriods"
                :key="period.id"
                :label="period.name"
                :value="period.id"
              />
            </el-select>
          </el-form-item>

          <el-form-item
            :label="applicationDialogMode === 'edit' ? 'Replace DTR Attachment' : 'DTR Attachment'"
            :required="applicationDialogMode !== 'edit' || !editingApplication?.attachment_name"
          >
            <p
              v-if="applicationDialogMode === 'edit' && editingApplication?.attachment_name"
              class="text-sm text-slate-600 mb-2"
            >
              Current file:
              <el-button
                type="primary"
                link
                size="small"
                class="align-baseline"
                @click="previewApplicationAttachment(editingApplication.id, editingApplication.attachment_name)"
              >
                {{ editingApplication.attachment_name }}
              </el-button>
            </p>
            <el-upload
              ref="uploadRef"
              :auto-upload="false"
              :limit="1"
              :on-change="handleFileChange"
              :on-remove="handleFileRemove"
              :on-preview="handleUploadPreview"
              :file-list="fileList"
              accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx"
            >
              <el-button type="primary" plain>{{ applicationDialogMode === 'edit' ? 'Choose new file' : 'Choose file' }}</el-button>
              <template #tip>
                <p class="text-xs text-slate-500 mt-1">
                  PDF, JPG, PNG, DOC, DOCX, XLS, XLSX — max 10MB
                  <span v-if="applicationDialogMode === 'edit' && editingApplication?.attachment_name">
                    (leave empty to keep the current file)
                  </span>
                </p>
              </template>
            </el-upload>
          </el-form-item>
        </el-form>

        <template #footer>
          <el-button @click="applicationDialogVisible = false">Cancel</el-button>
          <el-button type="primary" :loading="submitting" @click="submitApplicationForm">
            {{ applicationDialogMode === 'edit' ? 'Save Changes' : 'Submit' }}
          </el-button>
        </template>
      </el-dialog>

      <!-- Detail Dialog -->
      <el-dialog v-model="detailVisible" title="DTR Application Details" width="80%" top="5vh">
        <div v-if="detailLoading" class="py-8 text-center text-slate-500">Loading...</div>
        <div v-else-if="detailData">
          <div class="mb-4 grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
            <div><span class="text-slate-500">Employee:</span> {{ detailHeader?.name }}</div>
            <div><span class="text-slate-500">Request Date:</span> {{ formatDate(detailHeader?.request_date) }}</div>
            <div>
              <span class="text-slate-500">Status:</span>
              <el-tag :type="statusTagType(selectedApplication?.status_label)" size="small" class="ml-1">
                {{ selectedApplication?.status_label }}
              </el-tag>
            </div>
          </div>

          <div
            v-if="detailData.application_attachment"
            class="mb-4 p-3 bg-slate-50 rounded-lg flex items-center justify-between gap-3"
          >
            <div class="text-sm">
              <span class="text-slate-500">Payroll Period:</span>
              {{ detailData.application_attachment.payroll_period || '—' }}
            </div>
            <el-button
              type="primary"
              link
              size="small"
              @click="previewApplicationAttachment(detailData.application_attachment.request_id, detailData.application_attachment.attachment_name)"
            >
              {{ detailData.application_attachment.attachment_name }}
            </el-button>
          </div>

          <el-alert
            v-if="detailData.application_attachment && !detailData.has_pending_corrections && !detailData.has_approved_corrections"
            class="mb-4"
            type="info"
            :closable="false"
            show-icon
            title="Submitted with a DTR attachment."
            description="No time log corrections were entered in the system for this request."
          />

          <template v-if="detailData.has_pending_corrections">
            <h4 class="font-semibold mb-2">Proposed Time Log Corrections</h4>
            <el-table :data="detailData.time_data || []" size="small" max-height="220" class="mb-4">
              <el-table-column prop="date" label="Date" width="120" />
              <el-table-column prop="am_in" label="AM In" width="90" />
              <el-table-column prop="am_out" label="AM Out" width="90" />
              <el-table-column prop="pm_in" label="PM In" width="90" />
              <el-table-column prop="pm_out" label="PM Out" width="90" />
            </el-table>
          </template>

          <template v-if="detailData.has_approved_corrections">
            <h4 class="font-semibold mb-2">Saved Time Log Corrections</h4>
            <el-table :data="detailData.time_data_request || []" size="small" max-height="220">
              <el-table-column prop="date" label="Date" width="120" />
              <el-table-column prop="payroll_period" label="Payroll Period" min-width="180" />
              <el-table-column label="Attachment" width="120">
                <template #default="{ row }">
                  <el-button
                    v-if="row.attachment_name"
                    type="primary"
                    link
                    size="small"
                    @click="previewTimeDataAttachment(row.id, row.attachment_name)"
                  >
                    {{ row.attachment_name }}
                  </el-button>
                  <span v-else class="text-slate-400">—</span>
                </template>
              </el-table-column>
            </el-table>
          </template>
        </div>
        <template #footer>
          <el-button @click="detailVisible = false">Close</el-button>
          <el-button
            v-if="selectedApplication?.can_edit"
            type="warning"
            @click="openEditDialogFromDetail"
          >
            Edit
          </el-button>
          <el-button
            v-if="selectedApplication?.status_label === 'Approved' && selectedApplication?.has_approved_dtr"
            type="success"
            @click="openApprovedDtr(selectedApplication)"
          >
            View Approved DTR
          </el-button>
        </template>
      </el-dialog>

      <DTRAttachmentPreviewDialog ref="attachmentPreviewRef" />
    </div>
  </MainLayout>
</template>

<script>
import MainLayout from '@/layout/MainLayout.vue'
import DTRAttachmentPreviewDialog from '@/components/DTR/DTRAttachmentPreviewDialog.vue'
import { dtrApiService } from '@/services/apiService.js'
import { ElMessage } from 'element-plus'

export default {
  name: 'DTRApplication',
  components: { MainLayout, DTRAttachmentPreviewDialog },
  data() {
    return {
      loading: true,
      allowed: false,
      employeeId: null,
      applications: [],
      search: '',
      statusFilter: '',
      applicationDialogVisible: false,
      applicationDialogMode: 'create',
      editingApplication: null,
      loadingPeriods: false,
      submitting: false,
      payrollPeriods: [],
      applicationForm: {
        payrollPeriodId: null,
        attachmentFile: null
      },
      fileList: [],
      detailVisible: false,
      detailLoading: false,
      detailData: null,
      detailHeader: null,
      selectedApplication: null,
      breadcrumbs: [
        { name: 'Dashboard', path: '/dashboard' },
        { name: 'Time and Attendance', path: '/time-attendance' },
        { name: 'DTR Application', path: '/dtr/application' }
      ]
    }
  },
  computed: {
    currentUserId() {
      try {
        return JSON.parse(localStorage.getItem('user_data') || '{}').id || null
      } catch {
        return null
      }
    },
    filteredApplications() {
      let rows = this.applications
      if (this.statusFilter) {
        rows = rows.filter(r => r.status_label === this.statusFilter)
      }
      if (this.search) {
        const q = this.search.toLowerCase()
        rows = rows.filter(r =>
          String(r.status_label || '').toLowerCase().includes(q) ||
          String(r.request_date || '').toLowerCase().includes(q) ||
          String(r.payroll_period || '').toLowerCase().includes(q)
        )
      }
      return rows
    }
  },
  async mounted() {
    await this.loadApplications()
  },
  methods: {
    formatDate(value) {
      if (!value) return '—'
      const d = new Date(value)
      return Number.isNaN(d.getTime()) ? value : d.toLocaleDateString()
    },
    statusTagType(label) {
      if (label === 'Approved') return 'success'
      if (label === 'Returned' || label === 'Disapproved') return 'danger'
      if (label === 'Pending Level 2') return 'warning'
      return 'info'
    },
    async loadApplications() {
      if (!this.currentUserId) {
        this.loading = false
        return
      }
      try {
        this.loading = true
        const response = await dtrApiService.getDTRApplications(this.currentUserId)
        const payload = response?.data?.data ?? response?.data ?? response
        this.allowed = !!(payload?.permissions?.can_approve ?? payload?.allowed)
        this.employeeId = payload?.employee_id
        this.applications = payload?.applications || []
      } catch (error) {
        ElMessage.error(error?.response?.data?.message || 'Failed to load DTR applications')
      } finally {
        this.loading = false
      }
    },
    async openCreateDialog() {
      if (!this.employeeId) return
      this.applicationDialogMode = 'create'
      this.editingApplication = null
      this.applicationDialogVisible = true
      await this.loadPayrollPeriods()
    },
    async openEditDialog(row) {
      if (!this.employeeId || !row?.can_edit) return
      this.applicationDialogMode = 'edit'
      this.editingApplication = row
      this.applicationForm = {
        payrollPeriodId: row.payroll_period_id > 0 ? row.payroll_period_id : null,
        attachmentFile: null
      }
      this.fileList = []
      this.applicationDialogVisible = true
      await this.loadPayrollPeriods()
    },
    openEditDialogFromDetail() {
      if (!this.selectedApplication) return
      this.detailVisible = false
      this.openEditDialog(this.selectedApplication)
    },
    async loadPayrollPeriods() {
      try {
        this.loadingPeriods = true
        const response = await dtrApiService.getDTRApplicationPayrollPeriods(this.employeeId)
        const payload = response.data || response
        this.payrollPeriods = payload.payroll_periods || []
      } catch (error) {
        ElMessage.error(error?.response?.data?.message || 'Failed to load payroll periods')
      } finally {
        this.loadingPeriods = false
      }
    },
    handleFileChange(file) {
      this.applicationForm.attachmentFile = file.raw
      this.fileList = [file]
    },
    handleFileRemove() {
      this.applicationForm.attachmentFile = null
      this.fileList = []
    },
    handleUploadPreview(file) {
      if (file?.raw) {
        this.$refs.attachmentPreviewRef?.openLocalFile(file.raw, file.name)
      }
    },
    resetApplicationForm() {
      this.applicationForm = { payrollPeriodId: null, attachmentFile: null }
      this.fileList = []
      this.applicationDialogMode = 'create'
      this.editingApplication = null
    },
    async submitApplicationForm() {
      if (!this.applicationForm.payrollPeriodId) {
        ElMessage.warning('Please select a payroll period')
        return
      }

      const isEdit = this.applicationDialogMode === 'edit'
      const hasExistingAttachment = !!this.editingApplication?.attachment_name

      if (!isEdit && !this.applicationForm.attachmentFile) {
        ElMessage.warning('Please upload a DTR attachment')
        return
      }
      if (isEdit && !this.applicationForm.attachmentFile && !hasExistingAttachment) {
        ElMessage.warning('Please upload a DTR attachment')
        return
      }

      const formData = new FormData()
      formData.append('payroll_period_id', this.applicationForm.payrollPeriodId)
      if (this.applicationForm.attachmentFile) {
        formData.append('dtr_attachment', this.applicationForm.attachmentFile)
      }

      try {
        this.submitting = true
        let response
        if (isEdit) {
          response = await dtrApiService.updateDTRApplication(this.editingApplication.id, formData)
        } else {
          response = await dtrApiService.submitDTRApplication(this.employeeId, formData)
        }
        const payload = response.data || response
        ElMessage.success(
          payload.message || response.message || (isEdit ? 'DTR application updated' : 'DTR application submitted')
        )
        this.applicationDialogVisible = false
        await this.loadApplications()
      } catch (error) {
        ElMessage.error(
          error?.response?.data?.message ||
            (isEdit ? 'Failed to update DTR application' : 'Failed to submit DTR application')
        )
      } finally {
        this.submitting = false
      }
    },
    async viewApplication(row) {
      this.selectedApplication = row
      this.detailVisible = true
      this.detailLoading = true
      try {
        const response = await dtrApiService.reviewDTRRequest(row.id)
        const payload = response.data || response
        this.detailData = payload
        this.detailHeader = payload.daily_time_records?.[0] || null
      } catch (error) {
        ElMessage.error('Failed to load application details')
        this.detailVisible = false
      } finally {
        this.detailLoading = false
      }
    },
    previewApplicationAttachment(requestId, filename) {
      this.$refs.attachmentPreviewRef?.openApplication(requestId, filename)
    },
    previewTimeDataAttachment(timeDataId, filename) {
      this.$refs.attachmentPreviewRef?.openTimeData(timeDataId, filename)
    },
    openApprovedDtr(row) {
      if (!row?.id) return
      this.$refs.attachmentPreviewRef?.openApprovedDtr(row.id, `approved_dtr_${row.id}.pdf`)
    },
  }
}
</script>
