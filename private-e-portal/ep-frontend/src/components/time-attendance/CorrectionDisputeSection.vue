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

    <!-- Applications Table -->
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
      <el-table :data="applications" stripe v-loading="loading" empty-text="No correction applications filed">
        <el-table-column prop="id" label="Ref #" width="90" />
        <el-table-column prop="request_date" label="Date Filed" width="130">
          <template #default="{ row }">
            <span class="text-xs">{{ row.request_date }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="payroll_period" label="Payroll Period" min-width="180" />
        <el-table-column prop="entries_count" label="Entries" width="90" align="center" />
        <el-table-column label="Status" width="150">
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
      </el-table>
    </div>

    <!-- File Correction Modal -->
    <el-dialog v-model="dialogVisible" title="New DTR Correction Application" width="560px">
      <el-form label-position="top">
        <el-form-item label="Target Payroll Period">
          <el-select v-model="form.payroll_period_id" class="w-full" placeholder="Select period">
            <el-option
              v-for="p in payrollPeriods"
              :key="p.id"
              :label="p.period_description || `${p.start_date} to ${p.end_date}`"
              :value="p.id"
            />
          </el-select>
        </el-form-item>

        <el-form-item label="Target Date">
          <el-date-picker v-model="form.target_date" type="date" value-format="YYYY-MM-DD" class="w-full" />
        </el-form-item>

        <div class="grid grid-cols-2 gap-4">
          <el-form-item label="Field in Question">
            <el-select v-model="form.field_type" class="w-full">
              <el-option label="AM In" value="am_in" />
              <el-option label="AM Out" value="am_out" />
              <el-option label="PM In" value="pm_in" />
              <el-option label="PM Out" value="pm_out" />
              <el-option label="Missed Log" value="missed_log" />
              <el-option label="Tardiness Flag" value="tardiness" />
            </el-select>
          </el-form-item>
          <el-form-item label="Claimed Correct Time">
            <el-time-picker v-model="form.claimed_time" format="HH:mm" value-format="HH:mm" class="w-full" />
          </el-form-item>
        </div>

        <el-form-item label="Supporting Reason / Justification">
          <el-input v-model="form.reason" type="textarea" :rows="3" placeholder="Explain the reason for this correction..." />
        </el-form-item>

        <el-form-item label="Supporting Document (Optional File Upload)">
          <input type="file" @change="handleFileChange" class="text-xs text-slate-600" />
        </el-form-item>
      </el-form>

      <template #footer>
        <el-button @click="dialogVisible = false">Cancel</el-button>
        <el-button type="primary" :loading="submitting" @click="submitApplication">Submit Application</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script>
import { useToast } from 'vue-toastification'

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
      dialogVisible: false,
      applications: [],
      payrollPeriods: [],
      selectedFile: null,
      form: {
        payroll_period_id: null,
        target_date: new Date().toISOString().slice(0, 10),
        field_type: 'am_in',
        claimed_time: '08:00',
        reason: ''
      }
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
        const userId = userData.id || userData.user_id || this.employeeId || 1
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
        case 'disapproved': case 'rejected': return 'bg-rose-100 text-rose-800'
        default: return 'bg-amber-100 text-amber-800'
      }
    },
    async submitApplication() {
      if (!this.form.payroll_period_id || !this.form.reason) {
        this.toast.error('Please fill in required fields.')
        return
      }

      this.submitting = true
      try {
        const formData = new FormData()
        formData.append('payroll_period_id', this.form.payroll_period_id)
        formData.append('reason', this.form.reason)
        formData.append('target_date', this.form.target_date)
        formData.append('field_type', this.form.field_type)
        formData.append('claimed_time', this.form.claimed_time)
        if (this.selectedFile) {
          formData.append('attachment', this.selectedFile)
        }

        const { dtrApiService } = await import('../../services/apiService.js')
        await dtrApiService.submitDTRApplication(this.employeeId, formData)

        this.toast.success('DTR Correction application submitted!')
        this.dialogVisible = false
        await this.loadApplications()
      } catch (err) {
        this.toast.error('Failed to submit application.')
      } finally {
        this.submitting = false
      }
    }
  }
}
</script>
