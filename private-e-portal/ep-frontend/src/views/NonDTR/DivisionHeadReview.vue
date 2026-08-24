<template>
  <MainLayout :breadcrumbs="breadcrumbs">
    <div v-if="accessLoaded && hasAccess" class="max-w-7xl mx-auto space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-slate-900">COS Accomplishment Reports - Division Head Review</h1>
          <p class="text-slate-600 mt-1">
            Review and approve accomplishment reports from Contract of Service employees in your division.
          </p>
        </div>
      </div>

      <!-- Reports List -->
      <el-card shadow="hover" v-loading="loading">
        <template #header>
          <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold">Pending Reports</h2>
            <el-tag v-if="reports.length > 0">{{ reports.length }} Report(s)</el-tag>
          </div>
        </template>

        <div v-if="reports.length === 0" class="text-center py-10 text-slate-500">
          <el-empty description="No accomplishment reports to review">
            <p class="text-sm text-slate-600">
              Reports from COS employees in your division will appear here.
            </p>
          </el-empty>
        </div>

        <div v-else class="space-y-4">
          <el-card
            v-for="report in reports"
            :key="report.id"
            class="cursor-pointer hover:shadow-md transition-shadow"
            @click="reviewReport(report)"
          >
            <div class="flex items-start justify-between">
              <div class="flex-1">
                <div class="flex items-center gap-3 mb-2">
                  <h3 class="text-lg font-semibold text-slate-900">
                    {{ report.employee_name }}
                  </h3>
                  <el-tag :type="getStatusType(report.status)">
                    {{ report.status.toUpperCase() }}
                  </el-tag>
                </div>
                <p class="text-sm text-slate-600 mb-2">
                  Period: {{ formatDateRange(report.period_from, report.period_to) }}
                </p>
                <p class="text-slate-600 text-sm mb-3">{{ truncateText(report.task_1, 150) }}</p>
                <div class="flex items-center gap-4 text-xs text-slate-500">
                  <span class="flex items-center gap-1">
                    <el-icon><Calendar /></el-icon>
                    {{ report.entries_count }} day(s)
                  </span>
                  <span class="flex items-center gap-1">
                    <el-icon><Paperclip /></el-icon>
                    {{ report.attachments_count }} file(s)
                  </span>
                  <span class="text-xs text-slate-400">
                    Submitted: {{ formatDate(report.created_at) }}
                  </span>
                </div>
              </div>
              <div class="flex gap-2">
                <el-button 
                  size="small" 
                  type="primary"
                  @click.stop="reviewReport(report)"
                  :icon="View"
                >
                  Review
                </el-button>
              </div>
            </div>
          </el-card>
        </div>
      </el-card>

      <!-- Review Dialog -->
      <el-dialog 
        v-model="reviewDialogVisible" 
        title="Review Accomplishment Report"
        width="90%"
        :close-on-click-modal="false"
      >
        <div v-if="selectedReport" v-loading="loadingDetail">
          <!-- Report Header -->
          <el-descriptions :column="2" border class="mb-4">
            <el-descriptions-item label="Employee">
              {{ selectedReport.employee_name }}
            </el-descriptions-item>
            <el-descriptions-item label="Status">
              <el-tag :type="getStatusType(selectedReport.status)">
                {{ selectedReport.status.toUpperCase() }}
              </el-tag>
            </el-descriptions-item>
            <el-descriptions-item label="Period">
              {{ formatDateRange(selectedReport.period_from, selectedReport.period_to) }}
            </el-descriptions-item>
            <el-descriptions-item label="Submitted">
              {{ formatDateTime(selectedReport.created_at) }}
            </el-descriptions-item>
          </el-descriptions>

          <!-- Tasks -->
          <el-divider>Tasks & Responsibilities</el-divider>
          <div class="space-y-3 mb-4">
            <div v-if="selectedReport.task_1">
              <h4 class="font-semibold text-slate-700 mb-1">Main Tasks:</h4>
              <p class="text-slate-600">{{ selectedReport.task_1 }}</p>
            </div>
            <div v-if="selectedReport.task_2">
              <h4 class="font-semibold text-slate-700 mb-1">Secondary Tasks:</h4>
              <p class="text-slate-600">{{ selectedReport.task_2 }}</p>
            </div>
            <div v-if="selectedReport.task_3">
              <h4 class="font-semibold text-slate-700 mb-1">Additional Tasks:</h4>
              <p class="text-slate-600">{{ selectedReport.task_3 }}</p>
            </div>
          </div>

          <!-- Daily Entries -->
          <el-divider>Daily Work Entries</el-divider>
          <div class="space-y-3 mb-4">
            <el-card
              v-for="(entry, index) in selectedReport.entries"
              :key="entry.id"
            >
              <div class="flex items-start">
                <div class="flex-1">
                  <div class="flex items-center gap-3 mb-2">
                    <h5 class="font-semibold text-slate-700">{{ formatDate(entry.work_date) }}</h5>
                  </div>
                  <div class="space-y-2 text-sm">
                    <div v-if="entry.location" class="flex items-center gap-4">
                      <span><strong>Location:</strong> {{ entry.location }}</span>
                    </div>
                    <div>
                      <strong>Accomplishments:</strong>
                      <p class="text-slate-600 mt-1">{{ entry.accomplishments }}</p>
                    </div>
                    <div v-if="entry.output_description">
                      <strong>Output:</strong>
                      <p class="text-slate-600 mt-1">{{ entry.output_description }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </el-card>
          </div>

          <!-- Attachments -->
          <el-divider>Attachments</el-divider>
          <div class="mb-4">
            <div v-if="selectedReport.attachments && selectedReport.attachments.length > 0">
              <el-space wrap>
                <el-tag 
                  v-for="attachment in selectedReport.attachments" 
                  :key="attachment.id"
                  type="info"
                  size="large"
                  class="cursor-pointer hover:bg-blue-100 transition-colors"
                  @click="openPreview(attachment)"
                >
                  <el-icon><Paperclip /></el-icon>
                  {{ attachment.file_name }}
                  <span class="text-xs text-slate-400 ml-2">
                    ({{ formatFileSize(attachment.file_size) }})
                  </span>
                </el-tag>
              </el-space>
              <p class="text-sm text-slate-500 mt-2">
                {{ selectedReport.attachments.length }} file(s) attached • Click to preview/download
              </p>
            </div>
            <div v-else class="text-slate-500 text-sm">
              No attachments
            </div>
          </div>

          <!-- Approval Form -->
          <el-divider>Approval Decision</el-divider>
          <el-form :model="approvalForm" label-position="top">
            <el-form-item label="Payroll Approval">
              <el-checkbox 
                v-model="approvalForm.for_payroll" 
                size="large"
              >
                <span class="font-medium">✓ Approve entire report for payroll processing</span>
              </el-checkbox>
              <p class="text-sm text-slate-500 mt-2">
                This will mark the entire accomplishment report as ready for payroll.
              </p>
            </el-form-item>

            <el-form-item label="Report Status">
              <el-radio-group v-model="approvalForm.status">
                <el-radio-button label="approved">Approve Report</el-radio-button>
                <el-radio-button label="rejected">Reject Report</el-radio-button>
                <el-radio-button label="pending">Keep Pending</el-radio-button>
              </el-radio-group>
            </el-form-item>

            <el-form-item label="Remarks (Optional)">
              <el-input
                v-model="approvalForm.remarks"
                type="textarea"
                :rows="3"
                placeholder="Add any comments or feedback for the employee"
              />
            </el-form-item>
          </el-form>
        </div>

        <template #footer>
          <div class="flex justify-end gap-2">
            <el-button @click="reviewDialogVisible = false">Cancel</el-button>
            <el-button 
              type="primary" 
              @click="submitApproval" 
              :loading="submitting"
              :icon="Check"
            >
              Submit Decision
            </el-button>
          </div>
        </template>
      </el-dialog>

      <!-- Attachment Preview Dialog -->
      <el-dialog 
        v-model="previewDialogVisible" 
        :title="previewAttachment?.file_name || 'File Preview'"
        width="80%"
        :close-on-click-modal="true"
      >
        <div v-if="previewAttachment" class="text-center">
          <!-- Image Preview -->
          <div v-if="isImageFile(previewAttachment.file_name)" class="mb-4">
            <img 
              :src="getAttachmentUrl()" 
              :alt="previewAttachment.file_name"
              class="max-w-full max-h-[70vh] mx-auto"
              style="object-fit: contain;"
            />
          </div>
          
          <!-- PDF Preview -->
          <div v-else-if="isPDFFile(previewAttachment.file_name)" class="mb-4">
            <iframe 
              :src="getAttachmentUrl()"
              class="w-full"
              style="height: 70vh; border: 1px solid #ddd;"
            ></iframe>
          </div>
          
          <!-- Other Files - Show Info -->
          <div v-else class="py-10">
            <el-icon :size="64" class="text-slate-400 mb-4"><Document /></el-icon>
            <p class="text-lg font-semibold mb-2">{{ previewAttachment.file_name }}</p>
            <p class="text-slate-500 mb-4">{{ formatFileSize(previewAttachment.file_size) }}</p>
            <p class="text-sm text-slate-600 mb-4">
              Preview not available for this file type. Click download to view the file.
            </p>
          </div>

          <!-- File Info -->
          <div class="mt-4 text-sm text-slate-600">
            <p><strong>Uploaded by:</strong> {{ previewAttachment.uploaded_by_name || 'N/A' }}</p>
            <p><strong>Uploaded at:</strong> {{ formatDateTime(previewAttachment.created_at) }}</p>
          </div>
        </div>

        <template #footer>
          <div class="flex justify-between items-center">
            <el-button @click="previewDialogVisible = false">Close</el-button>
            <el-button 
              type="primary" 
              @click="downloadAttachment(previewAttachment)"
              :icon="Download"
            >
              Download File
            </el-button>
          </div>
        </template>
      </el-dialog>
    </div>
    <div v-else-if="!accessLoaded" class="p-6 text-center text-slate-600">
      Loading access...
    </div>
    <div v-else class="p-6 text-center text-slate-600">
      COS accomplishment review is not available. It will appear when a payroll period for COS is created.
    </div>
  </MainLayout>
</template>

<script>
import MainLayout from '../../layout/MainLayout.vue'
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Calendar, Paperclip, View, Check, Close, Document, Download } from '@element-plus/icons-vue'
import api from '../../services/api'

export default {
  name: 'DivisionHeadNonDTRReview',
  components: { MainLayout },
  setup() {
    const breadcrumbs = [
      { name: 'Dashboard', path: '/dashboard' },
      { name: 'Time and Attendance', path: '/time-attendance' },
      { name: 'COS Reports Review', path: '/division-head-non-dtr' }
    ]

    const hasAccess = ref(false)
    const accessLoaded = ref(false)
    const loading = ref(false)
    const submitting = ref(false)
    const loadingDetail = ref(false)
    const reviewDialogVisible = ref(false)
    const previewDialogVisible = ref(false)
    const reports = ref([])
    const selectedReport = ref(null)
    const previewAttachment = ref(null)
    const previewUrl = ref('')

    const approvalForm = reactive({
      for_payroll: false,
      status: 'approved',
      remarks: ''
    })

    async function loadPortalAccess() {
      try {
        const response = await api.checkEmployeeNonDTRAccess()
        if (response?.success) {
          hasAccess.value = !!response.data?.can_review_reports
        } else {
          hasAccess.value = false
        }
      } catch (error) {
        hasAccess.value = false
        console.error('Error checking COS review access:', error)
      } finally {
        accessLoaded.value = true
      }
    }

    async function loadReports() {
      loading.value = true
      try {
        const response = await api.getDivisionHeadNonDTRList()
        reports.value = response.data || []
      } catch (error) {
        ElMessage.error('Failed to load accomplishment reports')
        console.error('Error loading reports:', error)
      } finally {
        loading.value = false
      }
    }

    async function reviewReport(report) {
      loadingDetail.value = true
      reviewDialogVisible.value = true
      try {
        const response = await api.getEmployeeNonDTR(report.id)
        selectedReport.value = response.data
        
        // Initialize approval form
        approvalForm.for_payroll = response.data.for_payroll || false
        approvalForm.status = response.data.status || 'pending'
        approvalForm.remarks = response.data.remarks || ''
      } catch (error) {
        ElMessage.error('Failed to load report details')
        console.error('Error loading report:', error)
        reviewDialogVisible.value = false
      } finally {
        loadingDetail.value = false
      }
    }

    async function submitApproval() {
      try {
        await ElMessageBox.confirm(
          `Are you sure you want to ${approvalForm.status} this report?`,
          'Confirm Decision',
          {
            confirmButtonText: 'Yes, Submit',
            cancelButtonText: 'Cancel',
            type: 'warning',
          }
        )

        submitting.value = true

        const payload = {
          for_payroll: approvalForm.for_payroll,
          status: approvalForm.status,
          remarks: approvalForm.remarks
        }

        await api.approveDivisionHeadNonDTR(selectedReport.value.id, payload)
        
        ElMessage.success('Report decision submitted successfully')
        reviewDialogVisible.value = false
        loadReports()
      } catch (error) {
        if (error !== 'cancel') {
          ElMessage.error('Failed to submit decision')
          console.error('Error submitting approval:', error)
        }
      } finally {
        submitting.value = false
      }
    }

    function getStatusType(status) {
      const types = {
        'pending': 'warning',
        'approved': 'success',
        'rejected': 'danger'
      }
      return types[status] || 'info'
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

    function formatFileSize(bytes) {
      if (!bytes || bytes === 0) return '0 Bytes'
      const k = 1024
      const sizes = ['Bytes', 'KB', 'MB', 'GB']
      const i = Math.floor(Math.log(bytes) / Math.log(k))
      return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i]
    }

    function isImageFile(filename) {
      if (!filename) return false
      const ext = filename.split('.').pop().toLowerCase()
      return ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'].includes(ext)
    }

    function isPDFFile(filename) {
      if (!filename) return false
      return filename.toLowerCase().endsWith('.pdf')
    }

    function getAttachmentUrl() {
      // For preview we use a blob URL generated on the client
      return previewUrl.value
    }

    async function openPreview(attachment) {
      try {
        const token = localStorage.getItem('auth_token') || localStorage.getItem('token')
        if (!token) {
          ElMessage.error('Authentication token not found. Please login again.')
          return
        }

        loadingDetail.value = true
        previewAttachment.value = attachment
        previewDialogVisible.value = true

        const url = `http://localhost:8000/api/employee-non-dtr/attachment/${attachment.id}/download`

        const response = await fetch(url, {
          headers: {
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/octet-stream'
          }
        })

        if (!response.ok) {
          const errorText = await response.text()
          console.error('Preview fetch error response:', errorText)
          throw new Error('Failed to load attachment for preview')
        }

        const blob = await response.blob()
        if (previewUrl.value) {
          URL.revokeObjectURL(previewUrl.value)
        }
        previewUrl.value = URL.createObjectURL(blob)
      } catch (error) {
        ElMessage.error('Failed to load attachment preview')
        console.error('Error loading preview:', error)
        previewDialogVisible.value = false
      } finally {
        loadingDetail.value = false
      }
    }

    async function downloadAttachment(attachment) {
      try {
        const token = localStorage.getItem('token') || localStorage.getItem('auth_token')
        
        if (!token) {
          ElMessage.error('Authentication token not found. Please login again.')
          return
        }
        
        const url = `http://localhost:8000/api/employee-non-dtr/attachment/${attachment.id}/download`
        
        const response = await fetch(url, {
          headers: {
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/octet-stream'
          }
        })
        
        if (!response.ok) {
          const errorText = await response.text()
          console.error('Download error response:', errorText)
          throw new Error('Download failed')
        }
        
        const blob = await response.blob()
        const downloadUrl = window.URL.createObjectURL(blob)
        const link = document.createElement('a')
        link.href = downloadUrl
        link.download = attachment.file_name
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
        window.URL.revokeObjectURL(downloadUrl)
        
        ElMessage.success('Download completed')
      } catch (error) {
        ElMessage.error('Failed to download file')
        console.error('Download error:', error)
      }
    }

    onMounted(async () => {
      await loadPortalAccess()
      if (!hasAccess.value) return
      await loadReports()
    })

    return {
      breadcrumbs,
      hasAccess,
      accessLoaded,
      loading,
      submitting,
      loadingDetail,
      reviewDialogVisible,
      previewDialogVisible,
      reports,
      selectedReport,
      previewAttachment,
      previewUrl,
      approvalForm,
      Calendar,
      Paperclip,
      View,
      Check,
      Close,
      Document,
      Download,
      loadReports,
      reviewReport,
      submitApproval,
      getStatusType,
      formatDateRange,
      formatDate,
      formatDateTime,
      truncateText,
      formatFileSize,
      isImageFile,
      isPDFFile,
      getAttachmentUrl,
      openPreview,
      downloadAttachment
    }
  }
}
</script>

<style scoped>
.space-y-6 > * + * {
  margin-top: 1.5rem;
}

.space-y-4 > * + * {
  margin-top: 1rem;
}

.space-y-3 > * + * {
  margin-top: 0.75rem;
}

.space-y-2 > * + * {
  margin-top: 0.5rem;
}
</style>
