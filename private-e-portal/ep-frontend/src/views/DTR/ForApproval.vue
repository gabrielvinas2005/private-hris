<template>
  <MainLayout :breadcrumbs="breadcrumbs">
    <div class="max-w-7xl mx-auto">
      <div class="mb-6">
        <h1 class="text-3xl font-bold text-slate-900 mb-2">DTR For Approval</h1>
        <p class="text-slate-600">Review and approve daily time record correction requests from your team</p>
      </div>

      <div v-if="loading" class="flex justify-center items-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
        <span class="ml-3 text-slate-600">Loading requests...</span>
      </div>

      <div v-else-if="!isApprover" class="bg-amber-50 border border-amber-200 rounded-lg p-6">
        <p class="text-amber-900">
          You are not configured as a DTR approver. Approvers are assigned in HR using
          <strong>approver_type: Daily Time Record (id 8)</strong>.
        </p>
      </div>

      <div v-else>
        <el-card shadow="never">
          <el-tabs v-model="activeTab" class="dtr-approval-tabs">
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
            <el-table-column label="Request Date" width="140">
              <template #default="{ row }">
                {{ formatDate(row.request_date) }}
              </template>
            </el-table-column>
            <el-table-column label="Status" width="150">
              <template #default="{ row }">
                <el-tag :type="statusTagType(row)" size="small">
                  {{ row.status_label || 'Pending' }}
                </el-tag>
              </template>
            </el-table-column>
            <el-table-column label="Actions" width="140" fixed="right" align="center">
              <template #default="{ row }">
                <div class="flex items-center justify-center gap-1">
                  <el-tooltip content="Time Logs" placement="top">
                    <el-button type="primary" link :icon="Clock" @click="openTimeLogs(row)" />
                  </el-tooltip>
                  <el-tooltip content="Review" placement="top">
                    <el-button type="primary" link :icon="View" @click="openReview(row)" />
                  </el-tooltip>
                  <el-tooltip v-if="activeTab === 'pending' && isPending(row)" content="Approve" placement="top">
                    <el-button type="success" link :icon="Check" @click="processRequest(row, 2)" />
                  </el-tooltip>
                  <el-tooltip v-if="activeTab === 'pending' && isPending(row)" content="Return" placement="top">
                    <el-button type="danger" link :icon="Close" @click="processRequest(row, 3)" />
                  </el-tooltip>
                  <el-tooltip v-if="activeTab === 'approved'" content="Approved DTR" placement="top">
                    <el-button type="success" link :icon="Document" @click="openApprovedDtr(row)" />
                  </el-tooltip>
                </div>
              </template>
            </el-table-column>
          </el-table>
        </el-card>
      </div>

      <el-dialog v-model="reviewVisible" title="DTR Request Details" width="90%" top="5vh">
        <div v-if="reviewData">
          <div class="mb-4 grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
            <div><span class="text-slate-500">Employee:</span> {{ reviewHeader?.name }}</div>
            <div><span class="text-slate-500">Department:</span> {{ reviewHeader?.department }}</div>
            <div><span class="text-slate-500">Request Date:</span> {{ formatDate(reviewHeader?.request_date) }}</div>
            <div>
              <span class="text-slate-500">Request Status:</span>
              <el-tag :type="statusTagType({ status_label: reviewData.request_status_label })" size="small" class="ml-1">
                {{ reviewData.request_status_label || 'Pending' }}
              </el-tag>
            </div>
          </div>

          <div
            v-if="reviewData.application_attachment"
            class="mb-4 p-3 bg-slate-50 rounded-lg flex items-center justify-between gap-3 flex-wrap"
          >
            <div class="text-sm">
              <span class="text-slate-500">Payroll Period:</span>
              {{ reviewData.application_attachment.payroll_period || '—' }}
            </div>
            <el-button
              type="primary"
              link
              size="small"
              @click="previewApplicationAttachment(reviewData.application_attachment.request_id, reviewData.application_attachment.attachment_name)"
            >
              {{ reviewData.application_attachment.attachment_name }}
            </el-button>
          </div>

          <el-alert
            v-if="reviewData.application_attachment && !reviewData.has_pending_corrections && !reviewData.has_approved_corrections"
            class="mb-4"
            type="info"
            :closable="false"
            show-icon
            title="This request was submitted with a DTR attachment only."
            description="Approving the request records your approval in the workflow. To correct time in/out in the system, use the Time Logs button, edit the entries, and save them there."
          />

          <template v-if="reviewData.has_pending_corrections">
            <h4 class="font-semibold text-slate-900 mb-2">Proposed Time Log Corrections</h4>
            <el-table :data="reviewData.time_data || []" size="small" max-height="240" class="mb-4">
              <el-table-column prop="date" label="Date" width="120" />
              <el-table-column prop="am_in" label="AM In" width="90" />
              <el-table-column prop="am_out" label="AM Out" width="90" />
              <el-table-column prop="pm_in" label="PM In" width="90" />
              <el-table-column prop="pm_out" label="PM Out" width="90" />
            </el-table>
          </template>

          <template v-if="reviewData.has_approved_corrections">
            <h4 class="font-semibold text-slate-900 mb-2">Saved Time Log Corrections</h4>
            <el-table :data="reviewData.time_data_request || []" size="small" max-height="240">
              <el-table-column prop="date" label="Date" width="120" />
              <el-table-column prop="payroll_period" label="Payroll Period" min-width="200" />
              <el-table-column prop="am_in" label="AM In" width="90" />
              <el-table-column prop="pm_out" label="PM Out" width="90" />
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
          <el-button @click="openTimeLogs(reviewHeader)" v-if="reviewHeader">Time Logs</el-button>
          <el-button
            v-if="reviewData?.has_approved_dtr && reviewHeader?.id"
            type="success"
            @click="openApprovedDtr(reviewHeader)"
          >
            View Approved DTR
          </el-button>
          <el-button @click="reviewVisible = false">Close</el-button>
        </template>
      </el-dialog>

      <DTRAttachmentPreviewDialog ref="attachmentPreviewRef" />
      <DTRApproverTimeLogsDialog ref="timeLogsDialogRef" @saved="loadRequests" />
    </div>
  </MainLayout>
</template>

<script>
import MainLayout from '@/layout/MainLayout.vue'
import DTRAttachmentPreviewDialog from '@/components/DTR/DTRAttachmentPreviewDialog.vue'
import DTRApproverTimeLogsDialog from '@/components/DTR/DTRApproverTimeLogsDialog.vue'
import { dtrApiService } from '@/services/apiService.js'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Check, Clock, Close, Document, View } from '@element-plus/icons-vue'

export default {
  name: 'DTRForApproval',
  components: {
    MainLayout,
    DTRAttachmentPreviewDialog,
    DTRApproverTimeLogsDialog,
    Clock,
    View,
    Check,
    Close
  },
  data() {
    return {
      Clock,
      View,
      Check,
      Close,
      Document,
      loading: true,
      tableLoading: false,
      isApprover: false,
      activeTab: 'pending',
      pendingRequests: [],
      approvedRequests: [],
      returnedRequests: [],
      reviewVisible: false,
      reviewData: null,
      reviewHeader: null,
      breadcrumbs: [
        { name: 'Dashboard', path: '/dashboard' },
        { name: 'Time and Attendance', path: '/time-attendance' },
        { name: 'DTR For Approval', path: '/dtr/for-approval' }
      ]
    }
  },
  computed: {
    currentUserId() {
      try {
        const userData = JSON.parse(localStorage.getItem('user_data') || '{}')
        return userData.id || null
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
      if (this.activeTab === 'approved') return 'No approved DTR requests'
      if (this.activeTab === 'returned') return 'No returned DTR requests'
      return 'No pending DTR requests'
    }
  },
  async mounted() {
    await this.loadRequests()
  },
  methods: {
    unwrapApiPayload(response) {
      if (!response || typeof response !== 'object') return response
      if (response.success === false) {
        throw new Error(response.message || 'Request failed')
      }
      return response.data ?? response
    },
    tabLabel(name, count) {
      return count > 0 ? `${name} (${count})` : name
    },
    formatDate(value) {
      if (!value) return '—'
      const d = new Date(value)
      return Number.isNaN(d.getTime()) ? value : d.toLocaleDateString()
    },
    isPending(row) {
      if (row?.is_pending === true || row?.is_pending === 1) return true
      const label = row?.status_label || ''
      return label === 'Pending' || label.startsWith('Pending Level')
    },
    statusTagType(row) {
      const label = row?.status_label || 'Pending'
      if (label === 'Approved') return 'success'
      if (label === 'Returned' || label === 'Disapproved') return 'danger'
      return 'warning'
    },
    async loadRequests() {
      if (!this.currentUserId) {
        this.loading = false
        return
      }
      try {
        this.loading = true
        const response = await dtrApiService.loadDTRRequests(this.currentUserId)
        const payload = response?.data?.data ?? response?.data ?? response
        this.isApprover = Boolean(payload?.supervisor_id ?? payload?.is_approver)
        this.pendingRequests = (payload?.dtr_pending ?? payload?.dtr_for_approvals ?? payload?.records ?? []).filter(r => r.id)
        this.approvedRequests = (payload?.dtr_approved ?? []).filter(r => r.id)
        this.returnedRequests = (payload?.dtr_returned ?? []).filter(r => r.id)
      } catch (error) {
        ElMessage.error(error?.response?.data?.message || 'Failed to load DTR requests')
      } finally {
        this.loading = false
      }
    },
    openTimeLogs(row) {
      if (!row?.id) return
      this.reviewVisible = false
      this.$refs.timeLogsDialogRef?.open(row)
    },
    async openReview(row) {
      try {
        this.tableLoading = true
        const response = await dtrApiService.reviewDTRRequest(row.id)
        const payload = this.unwrapApiPayload(response)
        this.reviewData = payload
        this.reviewHeader = payload?.daily_time_records?.[0] || row
        this.reviewVisible = true
      } catch (error) {
        ElMessage.error(error?.response?.data?.message || error?.message || 'Failed to load request details')
      } finally {
        this.tableLoading = false
      }
    },
    async processRequest(row, typeId) {
      const action = typeId === 2 ? 'approve' : 'return'
      try {
        await ElMessageBox.confirm(
          `Are you sure you want to ${action} this DTR request?`,
          'Confirm',
          { type: typeId === 2 ? 'success' : 'warning' }
        )
        this.tableLoading = true
        await dtrApiService.approveDTRRequest(row.id, typeId)
        ElMessage.success(`Request ${action === 'approve' ? 'approved' : 'returned'} successfully`)
        await this.loadRequests()
      } catch (error) {
        if (error !== 'cancel') {
          ElMessage.error(error?.response?.data?.message || `Failed to ${action} request`)
        }
      } finally {
        this.tableLoading = false
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
    }
  }
}
</script>

<style scoped>
.dtr-approval-tabs :deep(.el-tabs__header) {
  margin-bottom: 0;
}
</style>
