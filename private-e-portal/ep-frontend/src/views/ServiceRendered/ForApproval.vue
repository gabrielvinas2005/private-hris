<template>
  <MainLayout :breadcrumbs="breadcrumbs">
    <div class="max-w-7xl mx-auto">
      <div class="mb-6">
        <h1 class="text-3xl font-bold text-slate-900 mb-2">Rendered Service For Approval</h1>
        <p class="text-slate-600">Review and approve certificate of rendered service requests</p>
      </div>

      <div v-if="loading" class="flex justify-center items-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
        <span class="ml-3 text-slate-600">Loading requests...</span>
      </div>

      <div v-else-if="!isApprover" class="bg-amber-50 border border-amber-200 rounded-lg p-6">
        <p class="text-amber-900">
          You are not configured as a rendered service approver. Approvers are assigned in HR using
          <strong>approver_type: Rendered Service (id 10)</strong>.
        </p>
      </div>

      <div v-else>
        <el-card shadow="never">
          <el-tabs v-model="activeTab" class="approval-tabs">
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
            <el-table-column label="Covering Period" min-width="200">
              <template #default="{ row }">
                {{ formatDate(row.date_start) }} – {{ formatDate(row.date_end) }}
              </template>
            </el-table-column>
            <el-table-column label="Request Date" width="140">
              <template #default="{ row }">{{ formatDate(row.request_date) }}</template>
            </el-table-column>
            <el-table-column label="Status" width="150">
              <template #default="{ row }">
                <el-tag :type="statusTagType(row)" size="small">{{ row.status_label || 'Pending' }}</el-tag>
              </template>
            </el-table-column>
            <el-table-column label="Actions" width="180" fixed="right" align="center">
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
                  <el-tooltip v-if="row.has_certificate" content="Certificate" placement="top">
                    <el-button type="success" link :icon="Document" @click="openCertificate(row)" />
                  </el-tooltip>
                </div>
              </template>
            </el-table-column>
          </el-table>
        </el-card>
      </div>

      <el-dialog v-model="reviewVisible" title="Rendered Service Request Details" width="80%" top="5vh">
        <div v-if="reviewData">
          <div class="mb-4 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
            <div><span class="text-slate-500">Employee:</span> {{ reviewData.application?.name }}</div>
            <div><span class="text-slate-500">Department:</span> {{ reviewData.application?.department || '—' }}</div>
            <div><span class="text-slate-500">Position:</span> {{ reviewData.application?.position || '—' }}</div>
            <div>
              <span class="text-slate-500">Covering Period:</span>
              {{ formatDate(reviewData.application?.date_start) }} – {{ formatDate(reviewData.application?.date_end) }}
            </div>
            <div><span class="text-slate-500">Request Date:</span> {{ formatDate(reviewData.application?.request_date) }}</div>
            <div>
              <span class="text-slate-500">Status:</span>
              <el-tag :type="statusTagType({ status_label: reviewData.request_status_label })" size="small" class="ml-1">
                {{ reviewData.request_status_label }}
              </el-tag>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm mb-4">
            <div class="p-3 bg-slate-50 rounded-lg">
              <p class="font-medium text-slate-800 mb-1">Noted By</p>
              <p>{{ reviewData.application?.noted_by }}</p>
              <p class="text-slate-600">{{ reviewData.application?.noted_by_position }}</p>
            </div>
            <div class="p-3 bg-slate-50 rounded-lg">
              <p class="font-medium text-slate-800 mb-1">Approved By</p>
              <p>{{ reviewData.application?.approved_by }}</p>
              <p class="text-slate-600">{{ reviewData.application?.approved_by_position }}</p>
            </div>
          </div>
        </div>
        <template #footer>
          <el-button v-if="reviewHeader?.has_certificate" type="primary" @click="openCertificate(reviewHeader)">
            View Certificate
          </el-button>
          <el-button @click="reviewVisible = false">Close</el-button>
        </template>
      </el-dialog>

      <DTRAttachmentPreviewDialog ref="certificatePreviewRef" />
    </div>
  </MainLayout>
</template>

<script>
import MainLayout from '@/layout/MainLayout.vue'
import DTRAttachmentPreviewDialog from '@/components/DTR/DTRAttachmentPreviewDialog.vue'
import { serviceRenderedApiService } from '@/services/apiService.js'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Check, Close, Document, View } from '@element-plus/icons-vue'

export default {
  name: 'ServiceRenderedForApproval',
  components: { MainLayout, DTRAttachmentPreviewDialog },
  data() {
    return {
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
        { name: 'Leave & Time Management', path: '/leave-time' },
        { name: 'Rendered Service For Approval', path: '/service-rendered/for-approval' }
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
      if (this.activeTab === 'approved') return 'No approved requests'
      if (this.activeTab === 'returned') return 'No returned requests'
      return 'No pending requests'
    }
  },
  async mounted() {
    await this.loadRequests()
  },
  methods: {
    unwrapApiPayload(response) {
      if (!response || typeof response !== 'object') return response
      if (response.success === false) throw new Error(response.message || 'Request failed')
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
      this.tableLoading = true
      try {
        const payload = this.unwrapApiPayload(await serviceRenderedApiService.loadRequests(this.currentUserId))
        this.isApprover = Boolean(payload?.supervisor_id ?? payload?.is_approver)
        this.pendingRequests = payload?.rendered_service_pending || payload?.records || []
        this.approvedRequests = payload?.rendered_service_approved || []
        this.returnedRequests = payload?.rendered_service_returned || []
      } catch (error) {
        ElMessage.error(error?.message || 'Failed to load requests')
      } finally {
        this.loading = false
        this.tableLoading = false
      }
    },
    async openReview(row) {
      try {
        const payload = this.unwrapApiPayload(await serviceRenderedApiService.reviewRequest(row.id))
        this.reviewData = payload
        this.reviewHeader = { ...row, has_certificate: payload?.has_certificate }
        this.reviewVisible = true
      } catch (error) {
        ElMessage.error(error?.message || 'Failed to load request details')
      }
    },
    async processRequest(row, typeId) {
      const action = typeId === 2 ? 'approve' : 'return'
      try {
        await ElMessageBox.confirm(`Are you sure you want to ${action} this request?`, 'Confirm', { type: 'warning' })
        await serviceRenderedApiService.approveRequest(row.id, typeId)
        ElMessage.success(`Request ${action}d successfully`)
        await this.loadRequests()
      } catch (error) {
        if (error !== 'cancel') {
          ElMessage.error(error?.response?.data?.message || error?.message || `Failed to ${action} request`)
        }
      }
    },
    openCertificate(row) {
      if (!row?.id) {
        ElMessage.error('Certificate request not found')
        return
      }

      this.$refs.certificatePreviewRef?.openRenderedServiceCertificate(
        row.id,
        `rendered_service_certificate_${row.id}.pdf`
      )
    }
  }
}
</script>

