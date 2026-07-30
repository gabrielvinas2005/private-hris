<template>
  <MainLayout :breadcrumbs="breadcrumbs">
    <div class="max-w-7xl mx-auto">
      <div class="mb-6">
        <h1 class="text-3xl font-bold text-slate-900 mb-2">Accomplishment Application</h1>
        <p class="text-slate-600">Submit and track COS accomplishment reports for approval</p>
      </div>

      <div v-if="!isCosEmployee" class="mb-6 p-4 bg-amber-50 border border-amber-200 rounded-lg">
        <p class="text-amber-900">
          This module is only available to <strong>Contract of Service (COS)</strong> employees.
        </p>
      </div>

      <div v-else-if="!allowed" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
        <p class="text-red-800">
          <span class="font-semibold">WARNING:</span> No approver is configured for Accomplishment Report applications. Please contact HRD.
        </p>
      </div>

      <template v-else>
        <el-card class="mb-4" shadow="never">
          <div class="flex items-center gap-4 flex-wrap">
            <el-input
              v-model="search"
              placeholder="Search by period or status"
              clearable
              style="width: 280px"
            />
            <el-select v-model="statusFilter" placeholder="Filter by status" clearable style="width: 180px">
              <el-option label="Draft" value="Draft" />
              <el-option label="Pending" value="Pending" />
              <el-option label="Pending Level 2" value="Pending Level 2" />
              <el-option label="Pending Level 3" value="Pending Level 3" />
              <el-option label="Approved" value="Approved" />
              <el-option label="Returned" value="Returned" />
            </el-select>
            <div class="ml-auto">
              <el-button type="primary" @click="goToSubmit">+ New Accomplishment Report</el-button>
            </div>
          </div>
        </el-card>

        <el-card shadow="never" v-loading="loading">
          <el-table :data="filteredApplications" stripe empty-text="No accomplishment applications yet">
            <el-table-column prop="id" label="Ref #" width="80" />
            <el-table-column label="Submitted" width="130">
              <template #default="{ row }">{{ formatDate(row.request_date) }}</template>
            </el-table-column>
            <el-table-column label="Report Period" min-width="180">
              <template #default="{ row }">
                {{ formatDate(row.period_from) }} – {{ formatDate(row.period_to) }}
              </template>
            </el-table-column>
            <el-table-column prop="payroll_period" label="Payroll Period" min-width="180">
              <template #default="{ row }">{{ row.payroll_period || '—' }}</template>
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
                  v-if="row.status_label === 'Approved' && row.has_approved_report"
                  type="success"
                  link
                  size="small"
                  @click="openApprovedReport(row)"
                >
                  View Approved Report
                </el-button>
                <el-button
                  v-if="['Draft', 'Pending', 'Returned'].includes(row.status_label)"
                  type="primary"
                  link
                  size="small"
                  @click="editApplication(row)"
                >
                  Edit
                </el-button>
              </template>
            </el-table-column>
          </el-table>
        </el-card>
      </template>

      <el-dialog v-model="detailVisible" title="Accomplishment Report Details" width="85%" top="5vh">
        <div v-if="detailLoading" class="py-8 text-center text-slate-500">Loading...</div>
        <div v-else-if="detailData">
          <div class="mb-4 grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
            <div><span class="text-slate-500">Period:</span> {{ formatDate(detailData.task?.period_from) }} – {{ formatDate(detailData.task?.period_to) }}</div>
            <div><span class="text-slate-500">Payroll Period:</span> {{ detailData.task?.payroll_period || '—' }}</div>
            <div>
              <span class="text-slate-500">Status:</span>
              <el-tag :type="statusTagType(detailData.request_status_label)" size="small" class="ml-1">
                {{ detailData.request_status_label }}
              </el-tag>
            </div>
          </div>

          <div class="mb-4 text-sm">
            <span class="text-slate-500">Main Task:</span> {{ detailData.task?.task_1 || '—' }}
          </div>

          <h4 class="font-semibold mb-2">Daily Accomplishments</h4>
          <el-table :data="detailData.entries || []" size="small" max-height="320" class="mb-4">
            <el-table-column prop="work_date" label="Date" width="120" />
            <el-table-column prop="accomplishments" label="Accomplishments" min-width="220" />
            <el-table-column prop="output_description" label="Output" min-width="160" />
            <el-table-column prop="location" label="Location" width="120" />
            <el-table-column prop="hours_worked" label="Hours" width="80" align="center" />
          </el-table>

          <div v-if="(detailData.attachments || []).length" class="text-sm">
            <span class="text-slate-500">Attachments:</span>
            <span class="ml-2">{{ detailData.attachments.length }} file(s)</span>
          </div>
        </div>
        <template #footer>
          <el-button @click="detailVisible = false">Close</el-button>
          <el-button
            v-if="detailData?.has_approved_report"
            type="success"
            @click="openApprovedReport({ id: detailData?.task?.id })"
          >
            View Approved Report
          </el-button>
        </template>
      </el-dialog>

      <AccomplishmentReportPreviewDialog ref="reportPreviewRef" />
    </div>
  </MainLayout>
</template>

<script>
import MainLayout from '@/layout/MainLayout.vue'
import AccomplishmentReportPreviewDialog from '@/components/Accomplishment/AccomplishmentReportPreviewDialog.vue'
import { accomplishmentApiService } from '@/services/apiService.js'
import { ElMessage } from 'element-plus'

export default {
  name: 'AccomplishmentApplication',
  components: { MainLayout, AccomplishmentReportPreviewDialog },
  data() {
    return {
      loading: true,
      allowed: false,
      isCosEmployee: false,
      employeeId: null,
      applications: [],
      search: '',
      statusFilter: '',
      detailVisible: false,
      detailLoading: false,
      detailData: null,
      breadcrumbs: [
        { name: 'Leave & Time', path: '/leave-time' },
        { name: 'Accomplishment Application', path: '/accomplishment/application' }
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
      const q = this.search.trim().toLowerCase()
      return this.applications.filter(row => {
        if (this.statusFilter && row.status_label !== this.statusFilter) return false
        if (!q) return true
        return [
          row.payroll_period,
          row.status_label,
          row.task_1,
          String(row.id)
        ].some(v => String(v || '').toLowerCase().includes(q))
      })
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
      if (label === 'Returned') return 'danger'
      if (label === 'Draft') return 'info'
      return 'warning'
    },
    async loadApplications() {
      if (!this.currentUserId) {
        this.loading = false
        return
      }
      try {
        this.loading = true
        const response = await accomplishmentApiService.getApplications(this.currentUserId)
        const payload = response?.data?.data ?? response?.data ?? response
        this.isCosEmployee = payload?.is_cos_employee === 1 || payload?.is_cos_employee === true
        this.allowed = Boolean(payload?.allowed)
        this.employeeId = payload?.employee_id ?? null
        this.applications = payload?.applications ?? []
      } catch (error) {
        if (error?.response?.status === 403 || error?.response?.status === 401) {
          this.isCosEmployee = false
        } else {
          ElMessage.error(error?.response?.data?.message || 'Failed to load accomplishment applications')
        }
      } finally {
        this.loading = false
      }
    },
    goToSubmit() {
      this.$router.push('/review-dtr')
    },
    editApplication(row) {
      this.$router.push({ path: '/review-dtr', query: { id: row.id } })
    },
    async viewApplication(row) {
      try {
        this.detailVisible = true
        this.detailLoading = true
        const response = await accomplishmentApiService.reviewRequest(row.id)
        this.detailData = response?.data ?? response
      } catch (error) {
        ElMessage.error(error?.response?.data?.message || 'Failed to load report details')
        this.detailVisible = false
      } finally {
        this.detailLoading = false
      }
    },
    openApprovedReport(row) {
      if (!row?.id) return
      this.$refs.reportPreviewRef?.openApprovedReport(row.id, `approved_accomplishment_report_${row.id}.pdf`)
    }
  }
}
</script>
