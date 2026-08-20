<template>
  <div class="space-y-6">
    <!-- Header with Monthly Usage Quota Indicator -->
    <div class="bg-gradient-to-r from-sky-900 to-indigo-900 text-white rounded-2xl p-6 shadow-md flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div>
        <div class="flex items-center gap-2 text-xs uppercase tracking-wider text-sky-300 font-bold mb-1">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 002 2h14a2 2 0 002-2V7a2 2 0 00-2-2H5z" />
          </svg>
          Pass Slip & OB Usage
        </div>
        <h3 class="text-xl font-bold">Pass Slip / Official Business Request</h3>
        <p class="text-xs text-sky-200 mt-1">Submit short-duration official leave or personal pass slips</p>
      </div>

      <!-- Quota Card -->
      <div class="bg-white/10 backdrop-blur-md rounded-xl p-3.5 px-5 border border-white/20 flex items-center gap-4">
        <div class="p-2.5 bg-sky-500/20 text-sky-200 rounded-lg">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
          </svg>
        </div>
        <div>
          <span class="text-[11px] text-sky-200 font-semibold block uppercase">Monthly Usage Limit</span>
          <span class="text-lg font-black text-white">{{ passSlipsUsed }} of {{ passSlipLimit }} used</span>
          <span class="text-[10px] text-sky-300 block">({{ Math.max(0, passSlipLimit - passSlipsUsed) }} remaining this month)</span>
        </div>
      </div>
    </div>

    <!-- Actions & Filter Bar -->
    <div class="bg-white rounded-2xl p-4 border border-slate-200 flex items-center justify-between gap-4">
      <div class="flex items-center gap-2">
        <span class="text-xs font-semibold text-slate-500">Filter Status:</span>
        <el-select v-model="statusFilter" size="small" placeholder="All Statuses" style="width: 140px">
          <el-option label="All" value="" />
          <el-option label="Pending" value="Pending" />
          <el-option label="Approved" value="Approved" />
          <el-option label="Disapproved" value="Disapproved" />
        </el-select>
      </div>

      <el-button type="primary" class="!rounded-xl font-semibold" @click="openFormModal">
        + File New Pass Slip
      </el-button>
    </div>

    <!-- Requests Table -->
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
      <el-table :data="filteredPassSlips" stripe v-loading="loading" empty-text="No pass slips recorded">
        <el-table-column prop="date" label="Date" width="130">
          <template #default="{ row }">
            <span class="text-xs font-semibold text-slate-800">{{ row.date }}</span>
          </template>
        </el-table-column>
        <el-table-column label="Time Out - In" min-width="150">
          <template #default="{ row }">
            <span class="text-xs font-medium text-slate-800">
              {{ row.time_out }} – {{ row.time_in }}
            </span>
          </template>
        </el-table-column>
        <el-table-column prop="purpose" label="Purpose" min-width="200" />
        <el-table-column prop="destination" label="Destination" min-width="160">
          <template #default="{ row }">{{ row.destination || '—' }}</template>
        </el-table-column>
        <el-table-column label="Status" width="140">
          <template #default="{ row }">
            <span :class="statusBadgeClass(row.status)" class="px-2.5 py-1 rounded-full text-xs font-bold">
              {{ row.status || 'Pending' }}
            </span>
          </template>
        </el-table-column>
        <el-table-column label="Approver Trail" min-width="180">
          <template #default="{ row }">
            <span class="text-xs text-slate-600">{{ row.approver_name || 'Immediate Supervisor' }}</span>
          </template>
        </el-table-column>
      </el-table>
    </div>

    <!-- New Pass Slip Dialog -->
    <el-dialog v-model="formVisible" title="File New Pass Slip Request" width="480px">
      <el-form label-position="top">
        <el-form-item label="Date">
          <el-date-picker v-model="form.date" type="date" value-format="YYYY-MM-DD" placeholder="Select date" class="w-full" />
        </el-form-item>
        <div class="grid grid-cols-2 gap-4">
          <el-form-item label="Time Out">
            <el-time-picker v-model="form.time_out" format="HH:mm" value-format="HH:mm" placeholder="Time out" class="w-full" />
          </el-form-item>
          <el-form-item label="Expected Time Back">
            <el-time-picker v-model="form.time_in" format="HH:mm" value-format="HH:mm" placeholder="Expected time back" class="w-full" />
          </el-form-item>
        </div>
        <el-form-item label="Purpose / Justification">
          <el-input v-model="form.purpose" type="textarea" :rows="2" placeholder="Reason for pass slip..." />
        </el-form-item>
        <el-form-item label="Destination">
          <el-input v-model="form.destination" placeholder="Destination address/office..." />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="formVisible = false">Cancel</el-button>
        <el-button type="primary" :loading="submitting" @click="submitPassSlip">Submit Request</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script>
import { useToast } from 'vue-toastification'
import { usePassSlip } from '../../composables/usePassSlip.js'

export default {
  name: 'PassSlipSection',
  props: {
    passSlipsUsed: { type: Number, default: 0 },
    passSlipLimit: { type: Number, default: 4 }
  },
  setup() {
    const { passSlips, fetchPassSlips, savePassSlip } = usePassSlip()
    return { passSlips, fetchPassSlips, savePassSlip }
  },
  data() {
    return {
      toast: useToast(),
      loading: false,
      statusFilter: '',
      formVisible: false,
      submitting: false,
      form: {
        date: new Date().toISOString().slice(0, 10),
        time_out: '10:00',
        time_in: '11:30',
        purpose: '',
        destination: ''
      }
    }
  },
  computed: {
    filteredPassSlips() {
      if (!this.statusFilter) return this.passSlips
      return this.passSlips.filter(p => (p.status || '').toLowerCase() === this.statusFilter.toLowerCase())
    }
  },
  async mounted() {
    this.loading = true
    try {
      await this.fetchPassSlips()
    } catch (e) {
      // handled
    } finally {
      this.loading = false
    }
  },
  methods: {
    statusBadgeClass(status) {
      switch ((status || '').toLowerCase()) {
        case 'approved': return 'bg-emerald-100 text-emerald-800'
        case 'disapproved': return 'bg-rose-100 text-rose-800'
        default: return 'bg-amber-100 text-amber-800'
      }
    },
    openFormModal() {
      if (this.passSlipsUsed >= this.passSlipLimit) {
        this.toast.warning('Pass slip monthly quota reached. Further filings require supervisor override.')
      }
      this.formVisible = true
    },
    async submitPassSlip() {
      if (!this.form.date || !this.form.purpose) {
        this.toast.error('Please fill in required fields.')
        return
      }
      this.submitting = true
      try {
        await this.savePassSlip(this.form)
        this.toast.success('Pass Slip filed successfully!')
        this.formVisible = false
        await this.fetchPassSlips()
      } catch (err) {
        this.toast.error('Failed to submit Pass Slip.')
      } finally {
        this.submitting = false
      }
    }
  }
}
</script>
