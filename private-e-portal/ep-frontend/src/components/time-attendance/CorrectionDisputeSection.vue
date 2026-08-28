<template>
  <div class="space-y-6">
    <!-- Section Header -->
    <div class="bg-gradient-to-r from-amber-900 via-orange-950 to-slate-900 text-white rounded-2xl p-6 shadow-md flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div>
        <div class="flex items-center gap-2 text-xs uppercase tracking-wider text-amber-300 font-bold mb-1">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
          </svg>
          Audit & Exception Dispute
        </div>
        <h3 class="text-xl font-bold">Attendance Correction / Dispute Request</h3>
        <p class="text-xs text-amber-200 mt-1">Submit corrections for missed logs, tardiness flags, or punch errors</p>
      </div>

      <el-button type="warning" class="!rounded-xl font-bold shadow-md" @click="openDialog">
        + File Correction Request
      </el-button>
    </div>

    <!-- Rule Notice -->
    <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-600 flex items-start gap-3">
      <svg class="w-5 h-5 text-indigo-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      <div>
        <span class="font-bold text-slate-800">Compliance & Audit Trail:</span>
        Employees cannot modify attendance records directly. All submitted corrections route to HR/Supervisor for manual verification. Once approved, HR applies the correction while logging you as the request originator.
      </div>
    </div>

    <!-- Skeleton Loader when fetching data -->
    <div v-if="loading" class="bg-white rounded-2xl border border-slate-200 p-6 space-y-4 shadow-sm animate-pulse">
      <div class="flex items-center justify-between border-b border-slate-100 pb-3">
        <div class="h-4 bg-slate-200 rounded w-1/4"></div>
        <div class="h-4 bg-slate-200 rounded w-1/6"></div>
      </div>
      <div v-for="i in 5" :key="i" class="h-12 bg-slate-50/80 rounded-xl flex items-center justify-between px-4 space-x-4">
        <div class="h-4 bg-slate-200 rounded w-12"></div>
        <div class="h-4 bg-slate-200 rounded w-28"></div>
        <div class="h-4 bg-slate-200 rounded w-44"></div>
        <div class="h-4 bg-slate-200 rounded w-16"></div>
        <div class="h-4 bg-slate-200 rounded w-24"></div>
      </div>
    </div>

    <!-- Applications Table -->
    <div v-else class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
      <el-table
        :data="filteredApplications"
        stripe
        empty-text="No correction applications filed"
        :default-sort="{ prop: 'request_date', order: 'descending' }"
      >
        <el-table-column prop="id" label="Ref #" width="90" sortable />
        <el-table-column prop="request_date" label="Date & Time Filed" width="165" sortable>
          <template #default="{ row }">
            <div class="flex flex-col leading-tight">
              <span class="text-xs font-semibold text-slate-800">{{ formatDateOnly(row.request_date || row.created_at) }}</span>
              <span v-if="formatTimeOnly(row.request_date || row.created_at)" class="text-[11px] text-indigo-600 font-bold mt-0.5">
                {{ formatTimeOnly(row.request_date || row.created_at) }}
              </span>
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="payroll_period" label="Payroll Period" min-width="180" sortable />
        <el-table-column prop="entries_count" label="Entries" width="90" align="center" sortable />
        <el-table-column label="Status" width="140" sortable prop="status_label">
          <template #default="{ row }">
            <span :class="statusTagClass(row.status_label)" class="px-2.5 py-1 rounded-full text-xs font-bold">
              {{ row.status_label }}
            </span>
          </template>
        </el-table-column>
        <el-table-column label="HR / Approver Remarks" min-width="180">
          <template #default="{ row }">
            <span class="text-xs text-slate-600">{{ row.remarks || row.disapproved_reason || 'Under review' }}</span>
          </template>
        </el-table-column>
        <el-table-column label="Actions" width="170" align="center" fixed="right">
          <template #default="{ row }">
            <div class="flex items-center justify-center gap-1.5">
              <el-button size="small" type="primary" plain class="!px-2.5 !py-1 !text-[11px]" @click="openReviewModal(row)">
                Review
              </el-button>
              <el-button
                v-if="row.can_cancel || isPending(row)"
                size="small"
                type="danger"
                plain
                class="!px-2.5 !py-1 !text-[11px]"
                :loading="cancellingId === row.id"
                @click="confirmCancel(row)"
              >
                Cancel
              </el-button>
            </div>
          </template>
        </el-table-column>
      </el-table>
    </div>

    <!-- Review / Details Modal -->
    <el-dialog v-model="reviewModalVisible" title="Attendance Correction Review" width="580px" destroy-on-close>
      <div v-if="selectedRequest" class="space-y-4">
        <!-- Top Status Banner -->
        <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-between">
          <div>
            <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider block">Reference Number</span>
            <span class="text-base font-bold text-slate-900">#{{ selectedRequest.id }}</span>
          </div>
          <div>
            <span :class="statusTagClass(selectedRequest.status_label)" class="px-3 py-1 rounded-full text-xs font-bold">
              {{ selectedRequest.status_label }}
            </span>
          </div>
        </div>

        <!-- Key Information Grid -->
        <div class="grid grid-cols-2 gap-4">
          <div class="p-3 bg-white border border-slate-200 rounded-xl">
            <span class="text-[11px] text-slate-400 font-semibold block">Date & Time Filed</span>
            <span class="text-xs font-bold text-slate-800">{{ formatTimestamp(selectedRequest.request_date || selectedRequest.created_at) }}</span>
          </div>
          <div class="p-3 bg-white border border-slate-200 rounded-xl">
            <span class="text-[11px] text-slate-400 font-semibold block">Payroll Period</span>
            <span class="text-xs font-bold text-slate-800">{{ selectedRequest.payroll_period || 'N/A' }}</span>
          </div>
        </div>

        <!-- Correction Details Card -->
        <div class="p-4 bg-amber-50/50 border border-amber-200/80 rounded-xl space-y-2">
          <div class="flex items-center justify-between text-xs font-bold text-amber-900 border-b border-amber-200/60 pb-2">
            <span>Requested Adjustment</span>
            <span v-if="selectedRequest.target_date" class="text-amber-700">Target Date: {{ selectedRequest.target_date }}</span>
          </div>
          <div class="grid grid-cols-2 gap-2 text-xs pt-1">
            <div>
              <span class="text-slate-500 block text-[11px]">Reason / Justification:</span>
              <p class="font-medium text-slate-800 mt-0.5">{{ selectedRequest.reason || selectedRequest.remarks || 'No detailed reason provided.' }}</p>
            </div>
            <div>
              <span class="text-slate-500 block text-[11px]">Claimed Punch Time:</span>
              <div class="font-bold text-slate-900 space-y-0.5 mt-0.5">
                <template v-if="selectedRequest.am_in || selectedRequest.am_out || selectedRequest.pm_in || selectedRequest.pm_out">
                  <span v-if="selectedRequest.am_in" class="block text-indigo-700 font-extrabold">AM In: {{ selectedRequest.am_in }}</span>
                  <span v-if="selectedRequest.am_out" class="block text-indigo-700 font-extrabold">AM Out: {{ selectedRequest.am_out }}</span>
                  <span v-if="selectedRequest.pm_in" class="block text-indigo-700 font-extrabold">PM In: {{ selectedRequest.pm_in }}</span>
                  <span v-if="selectedRequest.pm_out" class="block text-indigo-700 font-extrabold">PM Out: {{ selectedRequest.pm_out }}</span>
                </template>
                <span v-else-if="selectedRequest.field_type && selectedRequest.claimed_time" class="block text-indigo-700 font-extrabold">
                  {{ formatFieldLabel(selectedRequest.field_type) }}: {{ selectedRequest.claimed_time }}
                </span>
                <span v-else-if="selectedRequest.claimed_time" class="block text-indigo-700 font-extrabold">
                  {{ selectedRequest.claimed_time }}
                </span>
                <span v-else class="text-slate-400 italic">No punch time specified</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Attachment Link -->
        <div v-if="selectedRequest.attachment_name" class="p-3 bg-indigo-50/60 border border-indigo-100 rounded-xl flex items-center justify-between text-xs">
          <div class="flex items-center gap-2 text-indigo-900">
            <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
            </svg>
            <span class="font-semibold truncate max-w-[280px]">{{ selectedRequest.attachment_name }}</span>
          </div>
          <el-button size="small" type="primary" link @click="downloadAttachment(selectedRequest)">
            Download File
          </el-button>
        </div>

        <!-- HR / Approver Remarks -->
        <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-1 text-xs">
          <span class="font-bold text-slate-700 block">Approver & Audit Remarks</span>
          <p class="text-slate-600">{{ selectedRequest.approver_remarks || selectedRequest.disapproved_reason || 'Pending review from designated HR / Supervisor approver.' }}</p>
        </div>
      </div>

      <template #footer>
        <div class="flex items-center justify-between w-full">
          <el-button
            v-if="selectedRequest && (selectedRequest.can_cancel || isPending(selectedRequest))"
            type="danger"
            :loading="cancellingId === selectedRequest.id"
            @click="confirmCancel(selectedRequest)"
          >
            Cancel Request
          </el-button>
          <div class="ml-auto">
            <el-button @click="reviewModalVisible = false">Close</el-button>
          </div>
        </div>
      </template>
    </el-dialog>

    <!-- File Correction Modal -->
    <el-dialog 
      v-model="dialogVisible" 
      width="600px" 
      class="!rounded-3xl overflow-hidden shadow-2xl"
      :show-close="true"
    >
      <template #header>
        <div class="flex items-center gap-3.5 pb-3 border-b border-slate-100">
          <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-violet-600 to-indigo-700 flex items-center justify-center text-white shadow-md shadow-violet-500/25">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
          </div>
          <div>
            <h3 class="text-lg font-bold text-slate-900 leading-tight">Submit DTR Correction Request</h3>
            <p class="text-xs font-medium text-slate-500">File adjustments for missed attendance logs or clocking errors</p>
          </div>
        </div>
      </template>

      <el-form label-position="top" class="pt-2 space-y-3">
        <div class="grid grid-cols-2 gap-4">
          <el-form-item required>
            <template #label>
              <span class="text-xs font-bold text-slate-700">Target Payroll Period <span class="text-rose-500">*</span></span>
            </template>
            <el-select v-model="form.payroll_period_id" class="w-full !rounded-xl" placeholder="Select period">
              <el-option
                v-for="p in payrollPeriods"
                :key="p.id"
                :label="p.period_description || p.name || `${p.attendance_start_date || p.start_date} to ${p.attendance_end_date || p.end_date}`"
                :value="p.id"
              />
            </el-select>
          </el-form-item>

          <el-form-item required>
            <template #label>
              <span class="text-xs font-bold text-slate-700">Target Date <span class="text-rose-500">*</span></span>
            </template>
            <el-date-picker v-model="form.target_date" type="date" value-format="YYYY-MM-DD" class="w-full !rounded-xl" />
          </el-form-item>
        </div>

        <!-- Punch Adjustments Container -->
        <div class="p-4 bg-slate-50/80 border border-slate-200/80 rounded-2xl space-y-3">
          <div class="flex items-center justify-between border-b border-slate-200/60 pb-2">
            <span class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
              <svg class="w-4 h-4 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              Claimed Punch Times to Correct
            </span>
            <span class="text-[11px] font-medium text-slate-500">Specify one or multiple punch logs</span>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-[11px] font-bold text-slate-700 mb-1">AM In</label>
              <el-time-picker v-model="form.am_in" format="HH:mm" value-format="HH:mm" placeholder="e.g. 08:00" class="w-full !rounded-xl" />
            </div>
            <div>
              <label class="block text-[11px] font-bold text-slate-700 mb-1">AM Out</label>
              <el-time-picker v-model="form.am_out" format="HH:mm" value-format="HH:mm" placeholder="e.g. 12:00" class="w-full !rounded-xl" />
            </div>
            <div>
              <label class="block text-[11px] font-bold text-slate-700 mb-1">PM In</label>
              <el-time-picker v-model="form.pm_in" format="HH:mm" value-format="HH:mm" placeholder="e.g. 13:00" class="w-full !rounded-xl" />
            </div>
            <div>
              <label class="block text-[11px] font-bold text-slate-700 mb-1">PM Out</label>
              <el-time-picker v-model="form.pm_out" format="HH:mm" value-format="HH:mm" placeholder="e.g. 17:00" class="w-full !rounded-xl" />
            </div>
          </div>
        </div>

        <el-form-item required>
          <template #label>
            <span class="text-xs font-bold text-slate-700">Supporting Reason / Justification <span class="text-rose-500">*</span></span>
          </template>
          <el-input v-model="form.reason" type="textarea" :rows="3" placeholder="Explain the reason for this correction..." class="!rounded-xl" />
        </el-form-item>

        <el-form-item>
          <template #label>
            <span class="text-xs font-bold text-slate-700">Supporting Document <span class="text-slate-400 font-normal">(Optional File Upload)</span></span>
          </template>
          <div class="w-full border-2 border-dashed border-slate-200 hover:border-violet-400 bg-slate-50/50 hover:bg-violet-50/20 transition-all rounded-2xl p-4 text-center cursor-pointer group relative">
            <input 
              type="file" 
              @change="handleFileChange" 
              class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" 
            />
            <div class="flex flex-col items-center justify-center gap-1.5">
              <div class="w-9 h-9 rounded-xl bg-violet-50 group-hover:bg-violet-100 text-violet-600 flex items-center justify-center transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                </svg>
              </div>
              <span class="text-xs font-semibold text-slate-700 group-hover:text-violet-600 transition-colors">
                {{ selectedFile ? selectedFile.name : 'Click or drag file to attach proof' }}
              </span>
              <span class="text-[11px] text-slate-400">Attach screenshot or email proof (Max 5MB)</span>
            </div>
          </div>
        </el-form-item>
      </el-form>

      <template #footer>
        <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
          <el-button class="!rounded-xl !px-5 font-semibold" @click="dialogVisible = false">Cancel</el-button>
          <el-button 
            type="primary" 
            class="!rounded-xl font-bold !px-6 !py-2.5 !bg-gradient-to-r !from-violet-600 !to-indigo-600 hover:!from-violet-700 hover:!to-indigo-700 !border-violet-600 shadow-md shadow-violet-500/25 hover:shadow-violet-500/40 hover:-translate-y-0.5 transition-all duration-200" 
            :loading="submitting" 
            @click="submitApplication"
          >
            Submit Application
          </el-button>
        </div>
      </template>
    </el-dialog>
  </div>
</template>

<script>
import { useToast } from 'vue-toastification'
import { ElMessageBox } from 'element-plus'

export default {
  name: 'CorrectionDisputeSection',
  props: {
    employeeId: { type: [Number, String], required: true }
  },
  data() {
    return {
      toast: useToast(),
      loading: false,
      submitting: false,
      cancellingId: null,
      dialogVisible: false,
      reviewModalVisible: false,
      selectedRequest: null,
      applications: [],
      payrollPeriods: [],
      selectedFile: null,
      searchQuery: '',
      statusFilter: 'all',
      sortBy: 'date_desc',
      form: {
        payroll_period_id: null,
        target_date: new Date().toISOString().slice(0, 10),
        reason: '',
        am_in: '',
        am_out: '',
        pm_in: '',
        pm_out: ''
      }
    }
  },
  computed: {
    filteredApplications() {
      let list = [...this.applications]

      // 1. Status Filter
      if (this.statusFilter !== 'all') {
        list = list.filter(app => {
          const s = (app.status_label || app.status || '').toString().toLowerCase()
          if (this.statusFilter === 'pending') {
            return s.includes('pending') || s === '0' || s === 'for approval'
          }
          if (this.statusFilter === 'approved') {
            return s.includes('approved') && !s.includes('disapproved')
          }
          if (this.statusFilter === 'disapproved') {
            return s.includes('disapproved') || s.includes('rejected')
          }
          return true
        })
      }

      // 2. Search Query Filter
      if (this.searchQuery) {
        const q = this.searchQuery.toLowerCase().trim()
        list = list.filter(app => {
          const ref = String(app.id || '').toLowerCase()
          const date = String(app.request_date || '').toLowerCase()
          const period = String(app.payroll_period || '').toLowerCase()
          const remarks = String(app.remarks || app.disapproved_reason || '').toLowerCase()
          return ref.includes(q) || date.includes(q) || period.includes(q) || remarks.includes(q)
        })
      }

      // 3. Sorting (Default: Latest to Earliest by timestamp)
      list.sort((a, b) => {
        const parseTime = (item) => {
          const raw = item?.request_date || item?.created_at
          if (!raw) return Number(item?.id || 0)
          const str = raw.toString().trim()
          const d = new Date(str.includes('T') ? str : str.replace(' ', 'T'))
          return isNaN(d.getTime()) ? Number(item?.id || 0) : d.getTime()
        }

        const timeA = parseTime(a)
        const timeB = parseTime(b)

        if (this.sortBy === 'date_asc') {
          return timeA - timeB
        }
        if (this.sortBy === 'id_desc') {
          return Number(b.id || 0) - Number(a.id || 0)
        }
        if (this.sortBy === 'id_asc') {
          return Number(a.id || 0) - Number(b.id || 0)
        }
        if (this.sortBy === 'status') {
          return (a.status_label || '').localeCompare(b.status_label || '')
        }
        // Default (date_desc): Latest to Earliest
        return timeB - timeA
      })

      return list
    }
  },
  async mounted() {
    await this.loadApplications()
  },
  methods: {
    async loadApplications() {
      this.loading = true
      try {
        const userData = JSON.parse(localStorage.getItem('user_data') || '{}')
        const userId = userData.id || userData.user_id || this.employeeId || null
        if (!userId) {
          this.applications = []
          this.loading = false
          return
        }
        const { dtrApiService } = await import('../../services/apiService.js')
        const data = await dtrApiService.getDTRApplications(userId)
        const rawApps = data?.data?.applications || data?.applications || (Array.isArray(data?.data) ? data.data : (Array.isArray(data) ? data : []))
        this.applications = Array.isArray(rawApps) ? rawApps : []
      } catch (err) {
        console.error('Error loading applications:', err)
        this.applications = []
      } finally {
        this.loading = false
      }
    },
    formatFieldLabel(fieldType) {
      const labels = { am_in: 'AM In', am_out: 'AM Out', pm_in: 'PM In', pm_out: 'PM Out', break_in: 'Break In', break_out: 'Break Out' }
      return labels[fieldType] || fieldType
    },
    isPending(row) {
      if (!row) return false
      const s = (row.status_label || row.status || '').toString().toLowerCase()
      return s.includes('pending') || s === '0' || s === 'for approval'
    },
    openReviewModal(row) {
      this.selectedRequest = row
      this.reviewModalVisible = true
    },
    async downloadAttachment(row) {
      try {
        const { dtrApiService } = await import('../../services/apiService.js')
        await dtrApiService.downloadApplicationAttachment(row.id)
      } catch (err) {
        this.toast.error('Unable to download attachment.')
      }
    },
    async confirmCancel(row) {
      if (!row || !row.id) return
      try {
        await ElMessageBox.confirm(
          `Are you sure you want to cancel Attendance Correction Request #${row.id}? This action cannot be undone.`,
          'Confirm Cancellation',
          {
            confirmButtonText: 'Yes, Cancel Request',
            cancelButtonText: 'Keep Request',
            type: 'warning'
          }
        )

        this.cancellingId = row.id
        const { dtrApiService } = await import('../../services/apiService.js')
        await dtrApiService.cancelDTRApplication(row.id)

        this.toast.success(`Request #${row.id} has been cancelled.`)
        if (this.selectedRequest && this.selectedRequest.id === row.id) {
          this.reviewModalVisible = false
        }
        await this.loadApplications()
      } catch (err) {
        if (err !== 'cancel' && err !== 'close') {
          const errorMsg = err?.response?.data?.message || err?.message || 'Failed to cancel request.'
          this.toast.error(errorMsg)
        }
      } finally {
        this.cancellingId = null
      }
    },
    async openDialog() {
      this.dialogVisible = true
      try {
        const { dtrApiService } = await import('../../services/apiService.js')
        const periods = await dtrApiService.getDTRApplicationPayrollPeriods(this.employeeId)
        const rawPeriods = periods?.data?.payroll_periods || periods?.payroll_periods || (Array.isArray(periods?.data) ? periods.data : (Array.isArray(periods) ? periods : []))
        this.payrollPeriods = Array.isArray(rawPeriods) ? rawPeriods : []
        if (this.payrollPeriods.length > 0) {
          this.form.payroll_period_id = this.payrollPeriods[0].id
        }
      } catch (e) {
        this.payrollPeriods = []
      }
    },
    handleFileChange(e) {
      if (e.target.files && e.target.files[0]) {
        this.selectedFile = e.target.files[0]
      }
    },
    statusTagClass(status) {
      switch ((status || '').toLowerCase()) {
        case 'approved': return 'bg-emerald-100 text-emerald-800'
        case 'disapproved': case 'rejected': case 'returned': return 'bg-rose-100 text-rose-800'
        default: return 'bg-amber-100 text-amber-800'
      }
    },
    formatDateOnly(val) {
      if (!val) return '--'
      const str = val.toString().trim()
      const d = new Date(str.includes('T') ? str : str.replace(' ', 'T'))
      if (isNaN(d.getTime())) return str
      return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
    },
    formatTimeOnly(val) {
      if (!val) return ''
      const str = val.toString().trim()
      if (/^\d{4}-\d{2}-\d{2}$/.test(str)) return ''
      const d = new Date(str.includes('T') ? str : str.replace(' ', 'T'))
      if (isNaN(d.getTime())) return ''
      return d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true })
    },
    formatTimestamp(val) {
      if (!val) return 'N/A'
      const str = val.toString().trim()
      if (/^\d{4}-\d{2}-\d{2}$/.test(str)) {
        const [y, m, d] = str.split('-')
        const dateObj = new Date(y, m - 1, d)
        return dateObj.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
      }
      const d = new Date(str.includes('T') ? str : str.replace(' ', 'T'))
      if (isNaN(d.getTime())) return str
      const datePart = d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
      const timePart = d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true })
      return `${datePart} • ${timePart}`
    },
    async submitApplication() {
      if (!this.form.payroll_period_id || !this.form.reason) {
        this.toast.error('Please fill in required fields.')
        return
      }

      if (!this.form.am_in && !this.form.am_out && !this.form.pm_in && !this.form.pm_out) {
        this.toast.error('Please specify at least one claimed punch time to correct.')
        return
      }

      this.submitting = true
      try {
        const formData = new FormData()
        formData.append('payroll_period_id', this.form.payroll_period_id)
        formData.append('reason', this.form.reason)
        formData.append('target_date', this.form.target_date)

        const summaryParts = []
        if (this.form.am_in) {
          formData.append('am_in', this.form.am_in)
          summaryParts.push(`AMI:${this.form.am_in}`)
        }
        if (this.form.am_out) {
          formData.append('am_out', this.form.am_out)
          summaryParts.push(`AMO:${this.form.am_out}`)
        }
        if (this.form.pm_in) {
          formData.append('pm_in', this.form.pm_in)
          summaryParts.push(`PMI:${this.form.pm_in}`)
        }
        if (this.form.pm_out) {
          formData.append('pm_out', this.form.pm_out)
          summaryParts.push(`PMO:${this.form.pm_out}`)
        }

        formData.append('claimed_time', summaryParts.join(', '))
        formData.append('field_type', summaryParts.length > 1 ? 'multiple' : (this.form.am_in ? 'am_in' : this.form.am_out ? 'am_out' : this.form.pm_in ? 'pm_in' : 'pm_out'))

        if (this.selectedFile) {
          formData.append('dtr_attachment', this.selectedFile)
          formData.append('attachment', this.selectedFile)
        }

        const { dtrApiService } = await import('../../services/apiService.js')
        await dtrApiService.submitDTRApplication(this.employeeId, formData)

        this.toast.success('DTR Correction application submitted!')
        this.dialogVisible = false
        this.form.am_in = ''
        this.form.am_out = ''
        this.form.pm_in = ''
        this.form.pm_out = ''
        this.form.reason = ''
        await this.loadApplications()
      } catch (err) {
        const errorMsg = err?.response?.data?.message || err?.data?.message || err?.message || 'Failed to submit application.'
        this.toast.error(errorMsg)
      } finally {
        this.submitting = false
      }
    }
  }
}
</script>
