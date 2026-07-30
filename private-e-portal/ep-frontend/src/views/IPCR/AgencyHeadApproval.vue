<template>
  <MainLayout :breadcrumbs="breadcrumbs">
    <div class="max-w-7xl mx-auto">
      <div class="mb-6">
        <h1 class="text-3xl font-bold text-slate-900 mb-2">IPCR Head of Agency Approval</h1>
        <p class="text-slate-600">
          Review IPCR records after supervisor calibration and before HR recalibration
        </p>
      </div>

      <div v-if="loading" class="flex justify-center items-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
        <span class="ml-3 text-slate-600">Loading IPCR approvals...</span>
      </div>

      <div v-else-if="!isAgencyHead" class="bg-amber-50 border border-amber-200 rounded-lg p-6">
        <p class="text-amber-900">
          You are not configured as the Head of Agency. This role is assigned via
          <strong>branches.branch_head_id</strong> on the main branch.
        </p>
      </div>

      <div v-else>
        <el-card shadow="never">
          <el-tabs v-model="activeTab" class="ipcr-agency-head-tabs">
            <el-tab-pane :label="tabLabel('Pending', pendingRecords.length)" name="pending" />
            <el-tab-pane :label="tabLabel('Approved', approvedRecords.length)" name="approved" />
          </el-tabs>

          <el-table
            :data="currentRecords"
            stripe
            v-loading="tableLoading"
            :empty-text="emptyTableText"
            class="mt-2"
          >
            <el-table-column prop="employee_name" label="Employee" min-width="200" />
            <el-table-column prop="period" label="Period" min-width="180" />
            <el-table-column label="Status" width="220">
              <template #default="{ row }">
                <el-tag :type="statusTagType(row)" size="small">
                  {{ formatStatus(row.recalibration_status) }}
                </el-tag>
              </template>
            </el-table-column>
            <el-table-column label="Last Updated" width="150">
              <template #default="{ row }">{{ formatDate(row.updated_at) }}</template>
            </el-table-column>
            <el-table-column label="Actions" width="140" fixed="right" align="center">
              <template #default="{ row }">
                <el-button type="primary" link :icon="View" @click="openReview(row)">
                  Review
                </el-button>
              </template>
            </el-table-column>
          </el-table>
        </el-card>
      </div>

      <el-dialog
        v-model="reviewVisible"
        title="IPCR Review — Head of Agency"
        width="92%"
        top="4vh"
        :close-on-click-modal="false"
      >
        <div v-if="reviewLoading" class="py-12 text-center text-slate-500">Loading IPCR details...</div>
        <div v-else-if="reviewData">
          <div class="mb-4 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
            <div><span class="text-slate-500">Employee:</span> {{ reviewData.employee || '—' }}</div>
            <div><span class="text-slate-500">Period:</span> {{ formatPeriod(reviewData.period) }}</div>
            <div>
              <span class="text-slate-500">Status:</span>
              <el-tag :type="statusTagType(reviewData)" size="small" class="ml-1">
                {{ formatStatus(reviewData.recalibration_status) }}
              </el-tag>
            </div>
          </div>

          <div class="mb-4 text-sm">
            <span class="text-slate-500">Division/Section:</span> {{ reviewData.division || '—' }}
          </div>

          <h4 class="font-semibold text-slate-900 mb-1">Performance Ratings</h4>
          <p class="text-xs text-slate-500 mb-2">
            Combined Q/E/T = (Employee + Supervisor) ÷ 2. Final A is the average of the combined Q, E, and T (filled values only).
          </p>
          <el-table :data="reviewOutputs" border size="small" max-height="420" class="mb-4 ratings-review-table">
            <el-table-column prop="output" label="Output" min-width="140" show-overflow-tooltip fixed="left" />
            <el-table-column prop="accomplishment" label="Accomplishment" min-width="120" show-overflow-tooltip />
            <el-table-column label="Employee (Self)" align="center">
              <el-table-column label="Q" width="52" align="center">
                <template #default="{ row }">{{ formatRating(row.q) }}</template>
              </el-table-column>
              <el-table-column label="E" width="52" align="center">
                <template #default="{ row }">{{ formatRating(row.e) }}</template>
              </el-table-column>
              <el-table-column label="T" width="52" align="center">
                <template #default="{ row }">{{ formatRating(row.t) }}</template>
              </el-table-column>
            </el-table-column>
            <el-table-column label="Supervisor (Recal.)" align="center">
              <el-table-column label="Q" width="52" align="center">
                <template #default="{ row }">{{ formatRating(row.sup_q) }}</template>
              </el-table-column>
              <el-table-column label="E" width="52" align="center">
                <template #default="{ row }">{{ formatRating(row.sup_e) }}</template>
              </el-table-column>
              <el-table-column label="T" width="52" align="center">
                <template #default="{ row }">{{ formatRating(row.sup_t) }}</template>
              </el-table-column>
            </el-table-column>
            <el-table-column label="Combined Average" align="center">
              <el-table-column label="Q" width="58" align="center">
                <template #default="{ row }">{{ formatAverage(row.avg_q) }}</template>
              </el-table-column>
              <el-table-column label="E" width="58" align="center">
                <template #default="{ row }">{{ formatAverage(row.avg_e) }}</template>
              </el-table-column>
              <el-table-column label="T" width="58" align="center">
                <template #default="{ row }">{{ formatAverage(row.avg_t) }}</template>
              </el-table-column>
              <el-table-column label="A" width="58" align="center">
                <template #default="{ row }">
                  <span class="font-semibold text-blue-700">{{ formatAverage(row.avg_a) }}</span>
                </template>
              </el-table-column>
            </el-table-column>
          </el-table>

          <div v-if="isPendingReview" class="mb-2">
            <label class="block text-sm font-medium text-slate-700 mb-1">Remarks (optional)</label>
            <el-input
              v-model="remarks"
              type="textarea"
              :rows="3"
              placeholder="Add remarks when approving or returning this IPCR"
            />
          </div>
        </div>

        <template #footer>
          <el-button @click="reviewVisible = false">Close</el-button>
          <template v-if="isPendingReview">
            <el-button type="danger" :loading="processing" @click="processApproval('return')">
              Return to Employee
            </el-button>
            <el-button type="success" :loading="processing" @click="processApproval('approve')">
              Approve
            </el-button>
          </template>
        </template>
      </el-dialog>
    </div>
  </MainLayout>
</template>

<script>
import MainLayout from '@/layout/MainLayout.vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { View } from '@element-plus/icons-vue'

export default {
  name: 'IPCRAgencyHeadApproval',
  components: { MainLayout },
  data() {
    return {
      View,
      loading: true,
      tableLoading: false,
      processing: false,
      isAgencyHead: false,
      activeTab: 'pending',
      pendingRecords: [],
      approvedRecords: [],
      reviewVisible: false,
      reviewLoading: false,
      reviewData: null,
      reviewOutputs: [],
      reviewRecord: null,
      remarks: '',
      breadcrumbs: [
        { name: 'IPCR', path: '/ipcr' },
        { name: 'Head of Agency Approval', path: '/ipcr/agency-head-approval' }
      ]
    }
  },
  computed: {
    currentRecords() {
      return this.activeTab === 'approved' ? this.approvedRecords : this.pendingRecords
    },
    emptyTableText() {
      return this.activeTab === 'approved'
        ? 'No approved IPCR records'
        : 'No IPCR records awaiting Head of Agency approval'
    },
    isPendingReview() {
      return this.reviewData?.recalibration_status === 'supervisor_recalibrated'
    }
  },
  async mounted() {
    await this.loadRecords()
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
    formatPeriod(period) {
      if (!period) return '—'
      if (Array.isArray(period) && period.length >= 2) {
        return `${this.formatDate(period[0])} – ${this.formatDate(period[1])}`
      }
      if (typeof period === 'string') return period
      return '—'
    },
    formatStatus(status) {
      const labels = {
        self_assessment: 'Submitted for Review',
        supervisor_recalibrated: 'Pending Head of Agency Approval',
        agency_head_approved: 'Agency Head Approved',
        hr_recalibrated: 'HR Recalibrated',
        pmt_recalibrated: 'PMT Recalibrated'
      }
      return labels[status] || String(status || '').replace(/_/g, ' ')
    },
    statusTagType(row) {
      const status = row?.recalibration_status
      if (status === 'supervisor_recalibrated') return 'warning'
      if (status === 'agency_head_approved') return 'success'
      if (status === 'hr_recalibrated' || status === 'pmt_recalibrated') return 'info'
      return 'info'
    },
    formatRating(value) {
      const n = Number(value)
      if (!Number.isFinite(n) || n < 2 || n > 5) return '—'
      return n
    },
    formatAverage(value) {
      if (value === null || value === undefined || value === '') return '—'
      const n = Number(value)
      if (!Number.isFinite(n) || n < 2 || n > 5) return '—'
      return Number.isInteger(n) ? String(n) : n.toFixed(2)
    },
    parseRating(value) {
      const n = Number(value)
      if (!Number.isFinite(n) || n < 2 || n > 5) return null
      return n
    },
    /** (Employee + Supervisor) / 2 when both ratings are filled */
    pairAverage(empVal, supVal) {
      const emp = this.parseRating(empVal)
      const sup = this.parseRating(supVal)
      if (emp === null || sup === null) return null
      return Math.round(((emp + sup) / 2) * 100) / 100
    },
    /** Final A from combined Q, E, T — averages only filled dimensions */
    computeCombinedAverage(avgQ, avgE, avgT) {
      const values = [avgQ, avgE, avgT].filter(v => v !== null && Number.isFinite(v))
      if (!values.length) return null
      const avg = values.reduce((sum, v) => sum + v, 0) / values.length
      return Math.round(Math.max(2, Math.min(5, avg)) * 100) / 100
    },
    mapReviewOutputs(outputs) {
      return (outputs || []).map((o) => {
        const sup = o.supervisor_recalibration || {}
        const avgQ = this.pairAverage(o.q, sup.q)
        const avgE = this.pairAverage(o.e, sup.e)
        const avgT = this.pairAverage(o.t, sup.t)
        const avgA = this.computeCombinedAverage(avgQ, avgE, avgT)
        return {
          output: o.output || '',
          accomplishment: o.accomplishment || '',
          q: o.q,
          e: o.e,
          t: o.t,
          sup_q: sup.q,
          sup_e: sup.e,
          sup_t: sup.t,
          avg_q: avgQ,
          avg_e: avgE,
          avg_t: avgT,
          avg_a: avgA
        }
      })
    },
    async loadRecords() {
      try {
        this.loading = true
        const ApiService = (await import('@/services/api.js')).default
        const response = await ApiService.getIPCRAgencyHeadApprovals()
        if (response?.success) {
          this.isAgencyHead = !!response.data?.is_agency_head
          this.pendingRecords = response.data?.pending || []
          this.approvedRecords = response.data?.approved || []
        } else {
          ElMessage.error(response?.message || 'Failed to load IPCR approvals')
        }
      } catch (error) {
        console.error(error)
        ElMessage.error('Failed to load IPCR approvals')
      } finally {
        this.loading = false
      }
    },
    async openReview(record) {
      this.reviewRecord = record
      this.reviewVisible = true
      this.reviewLoading = true
      this.reviewData = null
      this.reviewOutputs = []
      this.remarks = record.agency_head_approval_remarks || ''

      try {
        const ApiService = (await import('@/services/api.js')).default
        const response = await ApiService.getEmployeeIPCR(record.id)
        if (response?.success) {
          this.reviewData = response.data
          this.reviewOutputs = this.mapReviewOutputs(response.data.outputs)
        } else {
          ElMessage.error(response?.message || 'Failed to load IPCR details')
          this.reviewVisible = false
        }
      } catch (error) {
        console.error(error)
        ElMessage.error('Failed to load IPCR details')
        this.reviewVisible = false
      } finally {
        this.reviewLoading = false
      }
    },
    async processApproval(action) {
      if (!this.reviewRecord?.id) return

      const confirmMessage = action === 'approve'
        ? 'Approve this IPCR and forward it to HR for recalibration?'
        : 'Return this IPCR to the employee for revision?'

      try {
        await ElMessageBox.confirm(confirmMessage, 'Confirm Action', {
          confirmButtonText: action === 'approve' ? 'Approve' : 'Return',
          cancelButtonText: 'Cancel',
          type: action === 'approve' ? 'success' : 'warning'
        })

        this.processing = true
        const ApiService = (await import('@/services/api.js')).default
        const response = await ApiService.processIPCRAgencyHeadApproval(this.reviewRecord.id, {
          action,
          remarks: this.remarks
        })

        if (response?.success) {
          ElMessage.success(response.message || 'Action completed successfully')
          this.reviewVisible = false
          await this.loadRecords()
        } else {
          ElMessage.error(response?.message || 'Failed to process approval')
        }
      } catch (error) {
        if (error !== 'cancel') {
          console.error(error)
          ElMessage.error('Failed to process approval')
        }
      } finally {
        this.processing = false
      }
    }
  }
}
</script>

<style scoped>
.ipcr-agency-head-tabs :deep(.el-tabs__header) {
  margin-bottom: 0;
}
.ratings-review-table :deep(.el-table__header th) {
  background-color: #f8fafc;
}
</style>
