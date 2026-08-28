<template>
  <div class="space-y-5">
    <!-- Header & Metrics Card -->
    <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200/80 space-y-6">
      <div class="flex items-center justify-between flex-wrap gap-4">
        <div class="flex items-center gap-3.5">
          <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center text-white shadow-md shadow-emerald-500/20">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
            </svg>
          </div>
          <div>
            <h3 class="text-xl font-bold text-slate-900 tracking-tight">Work From Home (WFH) Applications</h3>
            <p class="text-xs font-medium text-slate-500">File remote work authorizations, track approval status, and submit deliverables</p>
          </div>
        </div>

        <el-button 
          type="primary" 
          class="!rounded-xl font-semibold !px-5 !py-2.5 !bg-emerald-600 hover:!bg-emerald-700 !border-emerald-600 shadow-md shadow-emerald-600/20 hover:shadow-emerald-600/35 hover:-translate-y-0.5 transition-all duration-200" 
          @click="showFormModal = true"
        >
          <span class="text-sm">+ Apply for WFH</span>
        </el-button>
      </div>

      <!-- WFH Metric Overview Cards -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-3.5 pt-2">
        <div class="p-4 bg-gradient-to-br from-slate-50 to-white rounded-2xl border border-slate-200/80 shadow-xs text-center">
          <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Applications</div>
          <div class="text-2xl font-black text-slate-800   mt-1">{{ filteredApplications.length }}</div>
        </div>

        <div class="p-4 bg-gradient-to-br from-amber-50/50 to-white rounded-2xl border border-amber-200/60 shadow-xs text-center">
          <div class="text-xs font-bold text-amber-600 uppercase tracking-wider">Pending Review</div>
          <div class="text-2xl font-black text-amber-600   mt-1">
            {{ filteredApplications.filter(a => getStatus(a) === 'pending').length }}
          </div>
        </div>

        <div class="p-4 bg-gradient-to-br from-emerald-50/50 to-white rounded-2xl border border-emerald-200/60 shadow-xs text-center">
          <div class="text-xs font-bold text-emerald-600 uppercase tracking-wider">Approved Requests</div>
          <div class="text-2xl font-black text-emerald-600   mt-1">
            {{ filteredApplications.filter(a => getStatus(a) === 'approved').length }}
          </div>
        </div>

        <div class="p-4 bg-gradient-to-br from-rose-50/50 to-white rounded-2xl border border-rose-200/60 shadow-xs text-center">
          <div class="text-xs font-bold text-rose-600 uppercase tracking-wider">Disapproved / Cancelled</div>
          <div class="text-2xl font-black text-rose-600   mt-1">
            {{ filteredApplications.filter(a => ['disapproved', 'cancelled'].includes(getStatus(a))).length }}
          </div>
        </div>
      </div>

      <!-- Filters Toolbar -->
      <div class="flex items-center gap-3 flex-wrap pt-4 border-t border-slate-100">
        <el-input 
          v-model="search" 
          size="default" 
          placeholder="Search by reason or employee..." 
          clearable 
          style="width: 280px;"
          class="!rounded-xl"
        >
          <template #prefix>
            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
          </template>
        </el-input>

        <div class="flex items-center bg-slate-100 p-1 rounded-xl gap-1 border border-slate-200/60">
          <button 
            v-for="st in [
              { label: 'All', val: '' },
              { label: 'Pending', val: 'pending' },
              { label: 'Approved', val: 'approved' },
              { label: 'Disapproved', val: 'disapproved' },
              { label: 'Cancelled', val: 'cancelled' }
            ]" 
            :key="st.val"
            @click="statusFilter = st.val"
            class="px-3.5 py-1.5 text-xs font-semibold rounded-lg transition-all duration-200"
            :class="statusFilter === st.val ? 'bg-white text-slate-900 shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900'"
          >
            {{ st.label }}
          </button>
        </div>
      </div>
    </div>

    <!-- Skeleton Loading State -->
    <div v-if="loading" class="bg-white rounded-3xl p-6 border border-slate-200/80 space-y-4">
      <el-skeleton :rows="5" animated />
    </div>

    <!-- My WFH Applications Table Card -->
    <div v-else class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200/80 space-y-4">
      <div class="flex items-center justify-between">
        <h4 class="text-sm font-bold text-slate-800">My Remote Work Applications</h4>
        <span class="text-xs text-slate-500 font-medium">Total: {{ filteredApplications.length }}</span>
      </div>

      <el-table 
        :data="filteredApplications" 
        stripe
        style="width: 100%"
        empty-text="No WFH applications found"
      >
        <el-table-column prop="id" label="ID" width="75">
          <template #default="{ row }">
            <span class="text-xs   font-bold text-slate-500">#{{ row.id }}</span>
          </template>
        </el-table-column>
        <el-table-column label="Dates Covered" min-width="180">
          <template #default="{ row }">
            <div class="flex items-center gap-1.5">
              <span class="text-xs font-bold text-slate-800  ">{{ formatDate(row.start_date) }}</span>
              <span class="text-slate-400 text-xs">to</span>
              <span class="text-xs font-bold text-slate-800  ">{{ formatDate(row.end_date) }}</span>
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="reason" label="Reason / Justification" min-width="240" show-overflow-tooltip />
        <el-table-column label="Status" width="140">
          <template #default="{ row }">
            <span :class="getStatusBadgeClass(row)" class="px-3 py-1 rounded-full text-xs font-bold inline-block">
              {{ getStatusText(row) }}
            </span>
          </template>
        </el-table-column>
        <el-table-column label="Actions" width="160" fixed="right">
          <template #default="{ row }">
            <div class="flex items-center gap-2">
              <el-button type="primary" link size="small" @click="viewApplication(row)">
                View Details
              </el-button>
              <el-button v-if="canCancel(row)" type="danger" link size="small" @click="cancelApplication(row)">
                Cancel
              </el-button>
            </div>
          </template>
        </el-table-column>
      </el-table>
    </div>

    <!-- Approver Section if user is an approver -->
    <div v-if="isApprover && approverApplications.length > 0" class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200/80 space-y-4">
      <div class="flex items-center justify-between">
        <h4 class="text-sm font-bold text-slate-800">WFH Requests Requiring Your Approval</h4>
        <span class="text-xs font-bold text-amber-600 bg-amber-50 px-3 py-1 rounded-full border border-amber-200">
          {{ approverApplications.length }} Pending Approval
        </span>
      </div>

      <el-table 
        :data="approverApplications" 
        stripe
        style="width: 100%"
      >
        <el-table-column prop="id" label="ID" width="70" />
        <el-table-column prop="employee_name" label="Employee" width="180" />
        <el-table-column prop="start_date" label="Start Date" width="120">
          <template #default="{ row }">{{ formatDate(row.start_date) }}</template>
        </el-table-column>
        <el-table-column prop="end_date" label="End Date" width="120">
          <template #default="{ row }">{{ formatDate(row.end_date) }}</template>
        </el-table-column>
        <el-table-column prop="reason" label="Reason" min-width="200" show-overflow-tooltip />
        <el-table-column label="Actions" width="180" fixed="right">
          <template #default="{ row }">
            <div class="flex items-center gap-2">
              <el-button type="success" size="small" class="!rounded-lg" @click="approveApplication(row)">
                Approve
              </el-button>
              <el-button type="danger" size="small" class="!rounded-lg" @click="disapproveApplication(row)">
                Disapprove
              </el-button>
            </div>
          </template>
        </el-table-column>
      </el-table>
    </div>

    <!-- Apply WFH Form Modal -->
    <el-dialog
      v-model="showFormModal"
      width="620px"
      class="!rounded-3xl overflow-hidden shadow-2xl"
      @close="resetForm"
      :show-close="true"
    >
      <template #header>
        <div class="flex items-center gap-3.5 pb-3 border-b border-slate-100">
          <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-700 flex items-center justify-center text-white shadow-md shadow-emerald-500/25">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
          </div>
          <div>
            <h3 class="text-lg font-bold text-slate-900 leading-tight">Apply for Work From Home</h3>
            <p class="text-xs font-medium text-slate-500">File remote work authorization & specify planned deliverables</p>
          </div>
        </div>
      </template>

      <el-form :model="form" :rules="rules" ref="formRef" label-position="top" size="default" class="pt-2 space-y-2">
        <el-row :gutter="16">
          <el-col :span="12">
            <el-form-item prop="start_date">
              <template #label>
                <span class="text-xs font-bold text-slate-700">Start Date <span class="text-rose-500">*</span></span>
              </template>
              <el-date-picker
                v-model="form.start_date"
                type="date"
                placeholder="Select start date"
                format="YYYY-MM-DD"
                value-format="YYYY-MM-DD"
                style="width: 100%"
                class="!rounded-xl"
              />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item prop="end_date">
              <template #label>
                <span class="text-xs font-bold text-slate-700">End Date <span class="text-rose-500">*</span></span>
              </template>
              <el-date-picker
                v-model="form.end_date"
                type="date"
                placeholder="Select end date"
                format="YYYY-MM-DD"
                value-format="YYYY-MM-DD"
                style="width: 100%"
                class="!rounded-xl"
              />
            </el-form-item>
          </el-col>
        </el-row>

        <el-form-item prop="reason">
          <template #label>
            <span class="text-xs font-bold text-slate-700">Reason / Work Deliverables Plan <span class="text-rose-500">*</span></span>
          </template>
          <el-input
            v-model="form.reason"
            type="textarea"
            :rows="4"
            placeholder="Detailed reason and planned work deliverables for WFH..."
            maxlength="255"
            show-word-limit
            class="!rounded-xl"
          />
        </el-form-item>

        <el-form-item>
          <template #label>
            <span class="text-xs font-bold text-slate-700">Attachment / Reference Document <span class="text-slate-400 font-normal">(Optional)</span></span>
          </template>
          <div class="w-full border-2 border-dashed border-slate-200 hover:border-emerald-400 bg-slate-50/50 hover:bg-emerald-50/20 transition-all rounded-2xl p-4 text-center cursor-pointer group relative">
            <el-input
              v-model="form.attachment"
              placeholder="Paste attachment URL or reference document link..."
              class="!rounded-xl"
            />
          </div>
        </el-form-item>
      </el-form>
      <template #footer>
        <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
          <el-button class="!rounded-xl !px-5 font-semibold" @click="showFormModal = false">Cancel</el-button>
          <el-button 
            type="primary" 
            class="!rounded-xl font-bold !px-6 !py-2.5 !bg-gradient-to-r !from-emerald-600 !to-teal-600 hover:!from-emerald-700 hover:!to-teal-700 !border-emerald-600 shadow-md shadow-emerald-500/25 hover:shadow-emerald-500/40 hover:-translate-y-0.5 transition-all duration-200" 
            @click="submitForm" 
            :loading="submitting"
          >
            Submit WFH Application
          </el-button>
        </div>
      </template>
    </el-dialog>

    <!-- Detail View Modal -->
    <el-dialog
      v-model="showDetailModal"
      title="WFH Application Details"
      width="600px"
      class="rounded-3xl"
    >
      <div v-if="selectedApplication" class="space-y-4">
        <el-descriptions :column="2" border>
          <el-descriptions-item label="Employee">{{ selectedApplication.employee_name }}</el-descriptions-item>
          <el-descriptions-item label="Status">
            <span :class="getStatusBadgeClass(selectedApplication)" class="px-3 py-1 rounded-full text-xs font-bold">
              {{ getStatusText(selectedApplication) }}
            </span>
          </el-descriptions-item>
          <el-descriptions-item label="Start Date">{{ formatDate(selectedApplication.start_date) }}</el-descriptions-item>
          <el-descriptions-item label="End Date">{{ formatDate(selectedApplication.end_date) }}</el-descriptions-item>
          <el-descriptions-item label="Reason" :span="2">
            {{ selectedApplication.reason }}
          </el-descriptions-item>
        </el-descriptions>
      </div>
    </el-dialog>

    <!-- Disapprove Modal -->
    <el-dialog
      v-model="showDisapproveModal"
      title="Disapprove WFH Request"
      width="480px"
      class="rounded-3xl"
    >
      <el-form :model="disapproveForm" :rules="disapproveRules" ref="disapproveFormRef" label-position="top">
        <el-form-item label="Disapproval Reason" prop="disapproved_reason">
          <el-input
            v-model="disapproveForm.disapproved_reason"
            type="textarea"
            :rows="3"
            placeholder="Please specify why this WFH request is being disapproved..."
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <div class="flex justify-end gap-2">
          <el-button class="!rounded-xl" @click="showDisapproveModal = false">Cancel</el-button>
          <el-button type="danger" class="!rounded-xl font-semibold" @click="submitDisapprove" :loading="submitting">
            Confirm Disapproval
          </el-button>
        </div>
      </template>
    </el-dialog>
  </div>
</template>

<script>
import ApiService, { API_ROUTES } from '../../services/api.js'
import { ElMessage, ElMessageBox } from 'element-plus'

export default {
  name: 'WFHSection',
  data() {
    return {
      applications: [],
      loading: false,
      isApprover: false,
      currentEmployeeId: null,
      search: '',
      statusFilter: '',
      showFormModal: false,
      showDetailModal: false,
      showDisapproveModal: false,
      selectedApplication: null,
      submitting: false,
      form: {
        id: null,
        start_date: '',
        end_date: '',
        reason: '',
        attachment: ''
      },
      disapproveForm: {
        id: null,
        disapproved_reason: ''
      },
      rules: {
        start_date: [{ required: true, message: 'Please select start date', trigger: 'change' }],
        end_date: [{ required: true, message: 'Please select end date', trigger: 'change' }],
        reason: [{ required: true, message: 'Please enter reason', trigger: 'blur' }]
      },
      disapproveRules: {
        disapproved_reason: [{ required: true, message: 'Please enter reason', trigger: 'blur' }]
      }
    }
  },
  computed: {
    filteredApplications() {
      let base = this.applications
      if (this.currentEmployeeId) {
        base = base.filter(app => app.employee_id === this.currentEmployeeId)
      }
      let filtered = base
      if (this.search) {
        const searchLower = this.search.toLowerCase()
        filtered = filtered.filter(app =>
          app.reason?.toLowerCase().includes(searchLower) ||
          app.employee_name?.toLowerCase().includes(searchLower)
        )
      }
      if (this.statusFilter) {
        filtered = filtered.filter(app => this.getStatus(app) === this.statusFilter)
      }
      return filtered
    },
    approverApplications() {
      if (!this.isApprover) return []
      return this.applications.filter(app => {
        const isOwn = this.currentEmployeeId && app.employee_id === this.currentEmployeeId
        return !isOwn && app.is_pending_for_current_approver === true
      })
    }
  },
  mounted() {
    this.loadApplications()
  },
  methods: {
    async loadApplications() {
      this.loading = true
      try {
        const response = await ApiService.request(API_ROUTES.wfhApplication.list)
        if (response.success) {
          if (response.data && response.data.applications) {
            this.applications = response.data.applications || []
            this.isApprover = !!(response.data.permissions && response.data.permissions.is_approver === 1)
            this.currentEmployeeId = response.data.current_employee_id || null
          } else {
            this.applications = response.data || []
          }
        }
      } catch (error) {
        console.error('Error loading WFH applications:', error)
      } finally {
        this.loading = false
      }
    },
    formatDate(date) {
      if (!date) return '-'
      return date
    },
    getStatus(app) {
      if (app.cancelled) return 'cancelled'
      if (app.disapproved || app.disapproved_2 || app.disapproved_3) return 'disapproved'
      if (app.approved_3 || app.approved_2 || app.approved) return 'approved'
      return 'pending'
    },
    getStatusText(app) {
      const status = this.getStatus(app)
      const map = { pending: 'Pending', approved: 'Approved', disapproved: 'Disapproved', cancelled: 'Cancelled' }
      return map[status] || 'Pending'
    },
    getStatusBadgeClass(app) {
      const status = this.getStatus(app)
      if (status === 'approved') return 'bg-emerald-500/10 text-emerald-700 border border-emerald-500/20'
      if (status === 'disapproved') return 'bg-rose-500/10 text-rose-700 border border-rose-500/20'
      if (status === 'cancelled') return 'bg-slate-500/10 text-slate-600 border border-slate-500/20'
      return 'bg-amber-500/10 text-amber-700 border border-amber-500/20'
    },
    canCancel(app) {
      return !app.cancelled && !app.approved_3
    },
    viewApplication(app) {
      this.selectedApplication = app
      this.showDetailModal = true
    },
    async cancelApplication(app) {
      try {
        await ElMessageBox.confirm('Are you sure you want to cancel this WFH application?', 'Confirm Cancellation', {
          confirmButtonText: 'Yes, Cancel Application',
          cancelButtonText: 'Back',
          type: 'warning'
        })
        const res = await ApiService.request(API_ROUTES.wfhApplication.cancel(app.id), {
          method: 'POST',
          body: JSON.stringify({ cancelled_reason: 'User cancelled' })
        })
        if (res.success) {
          ElMessage.success('Application cancelled')
          this.loadApplications()
        }
      } catch (_) {}
    },
    async approveApplication(app) {
      try {
        const res = await ApiService.request(API_ROUTES.wfhApplication.approve(app.id), { method: 'POST' })
        if (res.success) {
          ElMessage.success('WFH application approved')
          this.loadApplications()
        }
      } catch (err) {
        ElMessage.error('Failed to approve application')
      }
    },
    disapproveApplication(app) {
      this.selectedApplication = app
      this.disapproveForm.id = app.id
      this.disapproveForm.disapproved_reason = ''
      this.showDisapproveModal = true
    },
    async submitDisapprove() {
      this.$refs.disapproveFormRef.validate(async (valid) => {
        if (!valid) return
        this.submitting = true
        try {
          const res = await ApiService.request(API_ROUTES.wfhApplication.disapprove(this.disapproveForm.id), {
            method: 'POST',
            body: JSON.stringify({ disapproved_reason: this.disapproveForm.disapproved_reason })
          })
          if (res.success) {
            ElMessage.success('WFH application disapproved')
            this.showDisapproveModal = false
            this.loadApplications()
          }
        } catch (err) {
          ElMessage.error('Failed to disapprove application')
        } finally {
          this.submitting = false
        }
      })
    },
    async submitForm() {
      this.$refs.formRef.validate(async (valid) => {
        if (!valid) return
        this.submitting = true
        try {
          const res = await ApiService.request(API_ROUTES.wfhApplication.store, {
            method: 'POST',
            body: JSON.stringify(this.form)
          })
          if (res.success) {
            ElMessage.success('WFH application submitted successfully!')
            this.showFormModal = false
            this.resetForm()
            this.loadApplications()
          } else {
            ElMessage.error(res.message || 'Failed to submit WFH application')
          }
        } catch (err) {
          ElMessage.error('Failed to submit application')
        } finally {
          this.submitting = false
        }
      })
    },
    resetForm() {
      this.form = { id: null, start_date: '', end_date: '', reason: '', attachment: '' }
      if (this.$refs.formRef) this.$refs.formRef.resetFields()
    }
  }
}
</script>
