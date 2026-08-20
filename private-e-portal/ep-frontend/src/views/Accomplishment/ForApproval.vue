<template>
  <MainLayout :breadcrumbs="breadcrumbs">
    <div class="max-w-7xl mx-auto">
      <div class="mb-6">
        <h1 class="text-3xl font-bold text-slate-900 mb-2">Accomplishment For Approval</h1>
        <p class="text-slate-600">Review and approve COS accomplishment reports from your team</p>
      </div>

      <div v-if="loading" class="flex justify-center items-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
        <span class="ml-3 text-slate-600">Loading requests...</span>
      </div>

      <div v-else-if="!isApprover" class="bg-amber-50 border border-amber-200 rounded-lg p-6">
        <p class="text-amber-900">
          You are not configured as an Accomplishment Report approver. Approvers are assigned in HR using
          <strong>approver_type: Accomplishment Report</strong>.
        </p>
      </div>

      <div v-else>
        <el-card shadow="never">
          <el-tabs v-model="activeTab" class="accomplishment-approval-tabs">
            <el-tab-pane :label="tabLabel('Pending', pendingRequests.length)" name="pending" />
            <el-tab-pane :label="tabLabel('Approved', approvedRequests.length)" name="approved" />
            <el-tab-pane :label="tabLabel('Returned', returnedRequests.length)" name="returned" />
          </el-tabs>

          <el-table
            :data="currentRequests"
            stripe
            v-loading="tableLoading"
            :empty-text="emptyTableText"
            class="mt-2"
          >
            <el-table-column prop="name" label="Employee" min-width="180" />
            <el-table-column prop="department" label="Department" min-width="140" />
            <el-table-column prop="position" label="Position" min-width="140" />
            <el-table-column label="Report Period" min-width="180">
              <template #default="{ row }">
                {{ formatDate(row.period_from) }} – {{ formatDate(row.period_to) }}
              </template>
            </el-table-column>
            <el-table-column prop="payroll_period" label="Payroll Period" min-width="160">
              <template #default="{ row }">{{ row.payroll_period || '—' }}</template>
            </el-table-column>
            <el-table-column label="Status" width="150">
              <template #default="{ row }">
                <el-tag :type="statusTagType(row)" size="small">
                  {{ row.status_label || 'Pending' }}
                </el-tag>
              </template>
            </el-table-column>
            <el-table-column label="Actions" width="120" fixed="right" align="center">
              <template #default="{ row }">
                <div class="flex items-center justify-center gap-1">
                  <el-tooltip content="Review" placement="top">
                    <el-button type="primary" link :icon="View" @click="openReview(row)" />
                  </el-tooltip>
                  <el-tooltip v-if="activeTab === 'pending' && isPending(row)" content="Approve" placement="top">
                    <el-button type="success" link :icon="Check" @click="processRequest(row, 2)" />
                  </el-tooltip>
                  <el-tooltip v-if="activeTab === 'pending' && isPending(row)" content="Return" placement="top">
                    <el-button type="danger" link :icon="Close" @click="processRequest(row, 3)" />
                  </el-tooltip>
                </div>
              </template>
            </el-table-column>
          </el-table>
        </el-card>
      </div>

      <el-dialog v-model="reviewVisible" title="Accomplishment Report Details" width="90%" top="5vh">
        <div v-if="reviewLoading" class="py-12 text-center text-slate-500">Loading report details...</div>
        <div v-else-if="reviewData">
          <div class="mb-4 grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
            <div><span class="text-slate-500">Employee:</span> {{ reviewData.task?.name }}</div>
            <div><span class="text-slate-500">Department:</span> {{ reviewData.task?.department || '—' }}</div>
            <div>
              <span class="text-slate-500">Period:</span>
              {{ formatDate(reviewData.task?.period_from) }} – {{ formatDate(reviewData.task?.period_to) }}
            </div>
            <div>
              <span class="text-slate-500">Status:</span>
              <el-tag :type="statusTagType({ status_label: reviewData.request_status_label })" size="small" class="ml-1">
                {{ reviewData.request_status_label || 'Pending' }}
              </el-tag>
            </div>
          </div>

          <div class="mb-4 text-sm grid grid-cols-1 md:grid-cols-2 gap-3">
            <div><span class="text-slate-500">Payroll Period:</span> {{ reviewData.task?.payroll_period || '—' }}</div>
            <div><span class="text-slate-500">Position:</span> {{ reviewData.task?.position || '—' }}</div>
          </div>

          <h4 class="font-semibold text-slate-900 mb-2">Tasks & Responsibilities</h4>
          <div class="mb-4 text-sm space-y-2 rounded-lg border border-slate-200 bg-slate-50 p-4">
            <div>
              <span class="text-slate-500">Main Tasks:</span>
              <p class="mt-1 whitespace-pre-wrap text-slate-800">{{ reviewData.task?.task_1 || '—' }}</p>
            </div>
            <div v-if="reviewData.task?.task_2">
              <span class="text-slate-500">Secondary Tasks:</span>
              <p class="mt-1 whitespace-pre-wrap text-slate-800">{{ reviewData.task.task_2 }}</p>
            </div>
            <div v-if="reviewData.task?.task_3">
              <span class="text-slate-500">Additional Tasks:</span>
              <p class="mt-1 whitespace-pre-wrap text-slate-800">{{ reviewData.task.task_3 }}</p>
            </div>
          </div>

          <h4 class="font-semibold text-slate-900 mb-2">Daily Accomplishments</h4>
          <el-table :data="reviewData.entries || []" size="small" max-height="280" class="mb-4">
            <el-table-column label="Date" width="120">
              <template #default="{ row }">{{ formatDate(row.work_date) }}</template>
            </el-table-column>
            <el-table-column prop="accomplishments" label="Accomplishments" min-width="220" />
            <el-table-column prop="output_description" label="Output" min-width="160" />
            <el-table-column prop="location" label="Location" width="120" />
            <el-table-column prop="hours_worked" label="Hours" width="80" align="center" />
          </el-table>

          <h4 class="font-semibold text-slate-900 mb-2">
            Attachments
            <span class="text-sm font-normal text-slate-500">({{ reviewAttachments.length }})</span>
          </h4>
          <div v-if="reviewAttachments.length" class="mb-2">
            <el-table :data="reviewAttachments" size="small">
              <el-table-column type="index" label="#" width="50" />
              <el-table-column prop="file_name" label="File Name" min-width="220" />
              <el-table-column label="Size" width="110">
                <template #default="{ row }">{{ formatFileSize(row.file_size) }}</template>
              </el-table-column>
              <el-table-column label="Uploaded" width="130">
                <template #default="{ row }">{{ formatDate(row.created_at) }}</template>
              </el-table-column>
              <el-table-column label="Actions" width="120" align="center">
                <template #default="{ row }">
                  <el-button type="primary" link size="small" @click="previewAttachment(row)">
                    Preview
                  </el-button>
                </template>
              </el-table-column>
            </el-table>
          </div>
          <p v-else class="mb-4 text-sm text-slate-500">No attachments uploaded.</p>
        </div>
        <template #footer>
          <el-button @click="reviewVisible = false">Close</el-button>
        </template>
      </el-dialog>

      <NonDTRAttachmentPreviewDialog ref="attachmentPreviewRef" />
    </div>
  </MainLayout>
</template>

<script>
import MainLayout from '@/layout/MainLayout.vue'
import NonDTRAttachmentPreviewDialog from '@/components/Accomplishment/NonDTRAttachmentPreviewDialog.vue'
import { accomplishmentApiService } from '@/services/apiService.js'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Check, Close, View } from '@element-plus/icons-vue'

export default {
  name: 'AccomplishmentForApproval',
  components: { MainLayout, NonDTRAttachmentPreviewDialog },
  data() {
    return {
      View,
      Check,
      Close,
      loading: true,
      tableLoading: false,
      isApprover: false,
      activeTab: 'pending',
      pendingRequests: [],
      approvedRequests: [],
      returnedRequests: [],
      reviewVisible: false,
      reviewLoading: false,
      reviewData: null,
      breadcrumbs: [
        { name: 'Dashboard', path: '/dashboard' },
        { name: 'Time and Attendance', path: '/time-attendance' },
        { name: 'Accomplishment For Approval', path: '/accomplishment/for-approval' }
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
    currentRequests() {
      if (this.activeTab === 'approved') return this.approvedRequests
      if (this.activeTab === 'returned') return this.returnedRequests
      return this.pendingRequests
    },
    emptyTableText() {
      if (this.activeTab === 'approved') return 'No approved accomplishment reports'
      if (this.activeTab === 'returned') return 'No returned accomplishment reports'
      return 'No pending accomplishment reports'
    },
    reviewAttachments() {
      return this.reviewData?.attachments || []
    }
  },
  async mounted() {
    await this.loadRequests()
  },
  methods: {
    tabLabel(name, count) {
      return count > 0 ? `${name} (${count})` : name
    },
    formatDate(value) {
      if (!value) return '—'
      const d = new Date(value)
      return Number.isNaN(d.getTime()) ? value : d.toLocaleDateString()
    },
    formatFileSize(bytes) {
      const size = Number(bytes)
      if (!size || Number.isNaN(size)) return '—'
      if (size < 1024) return `${size} B`
      if (size < 1024 * 1024) return `${(size / 1024).toFixed(1)} KB`
      return `${(size / (1024 * 1024)).toFixed(1)} MB`
    },
    isPending(row) {
      if (row?.is_pending === true || row?.is_pending === 1) return true
      const label = row?.status_label || ''
      return label === 'Pending' || label.startsWith('Pending Level')
    },
    statusTagType(row) {
      const label = row?.status_label || 'Pending'
      if (label === 'Approved') return 'success'
      if (label === 'Returned') return 'danger'
      return 'warning'
    },
    async loadRequests() {
      if (!this.currentUserId) {
        this.loading = false
        return
      }
      try {
        this.loading = true
        const response = await accomplishmentApiService.loadRequests(this.currentUserId)
        const payload = response?.data?.data ?? response?.data ?? response
        this.isApprover = Boolean(payload?.supervisor_id ?? payload?.is_approver)
        this.pendingRequests = (payload?.accomplishment_pending ?? []).filter(r => r.id)
        this.approvedRequests = (payload?.accomplishment_approved ?? []).filter(r => r.id)
        this.returnedRequests = (payload?.accomplishment_returned ?? []).filter(r => r.id)
      } catch (error) {
        ElMessage.error(error?.response?.data?.message || 'Failed to load accomplishment requests')
      } finally {
        this.loading = false
      }
    },
    async openReview(row) {
      try {
        this.reviewVisible = true
        this.reviewLoading = true
        this.reviewData = null
        const response = await accomplishmentApiService.reviewRequest(row.id)
        const payload = response?.data ?? response
        this.reviewData = payload?.data ?? payload
      } catch (error) {
        this.reviewVisible = false
        ElMessage.error(error?.response?.data?.message || 'Failed to load report details')
      } finally {
        this.reviewLoading = false
      }
    },
    previewAttachment(attachment) {
      if (!attachment?.id) return
      this.$refs.attachmentPreviewRef?.open(attachment.id, attachment.file_name)
    },
    async processRequest(row, typeId) {
      const action = typeId === 2 ? 'approve' : 'return'
      try {
        await ElMessageBox.confirm(
          `Are you sure you want to ${action} this accomplishment report?`,
          'Confirm',
          { type: typeId === 2 ? 'success' : 'warning' }
        )
        this.tableLoading = true
        await accomplishmentApiService.approveRequest(row.id, typeId)
        ElMessage.success(`Report ${action === 'approve' ? 'approved' : 'returned'} successfully`)
        this.reviewVisible = false
        await this.loadRequests()
      } catch (error) {
        if (error !== 'cancel') {
          ElMessage.error(error?.response?.data?.message || `Failed to ${action} report`)
        }
      } finally {
        this.tableLoading = false
      }
    }
  }
}
</script>

<style scoped>
.accomplishment-approval-tabs :deep(.el-tabs__header) {
  margin-bottom: 0;
}
</style>
