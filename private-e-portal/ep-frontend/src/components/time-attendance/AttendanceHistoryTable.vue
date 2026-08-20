<template>
  <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
    <!-- Filters Header Bar -->
    <div class="p-5 bg-slate-50/80 border-b border-slate-200 flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div class="flex items-center gap-3">
        <div class="p-2 bg-purple-50 text-purple-600 rounded-xl">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <div>
          <h3 class="text-base font-bold text-slate-900">Attendance History & DTR</h3>
          <p class="text-xs text-slate-500 font-medium">Filter by payroll cutoff period and export official DTR PDF</p>
        </div>
      </div>

      <div class="flex items-center gap-3 flex-wrap">
        <!-- Payroll Cutoff Selector -->
        <el-select
          v-model="selectedPayrollPeriod"
          placeholder="Select Payroll Period"
          style="width: 260px"
          @change="fetchPeriodDTR"
        >
          <el-option
            v-for="period in payrollPeriods"
            :key="period.id"
            :label="formatPeriodLabel(period)"
            :value="period.id"
          />
        </el-select>

        <!-- Action Buttons: Preview & Download DTR PDF -->
        <div class="flex items-center gap-2">
          <el-button
            type="info"
            plain
            :loading="previewingPdf"
            :disabled="!selectedPayrollPeriod"
            @click="previewDTR"
            class="!rounded-xl font-semibold shadow-sm"
          >
            <template #icon>
              <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
              </svg>
            </template>
            Preview DTR
          </el-button>

          <el-button
            type="primary"
            :loading="exportingPdf"
            :disabled="!selectedPayrollPeriod"
            @click="downloadDTR"
            class="!rounded-xl font-semibold shadow-sm"
          >
            <template #icon>
              <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
              </svg>
            </template>
            Download DTR PDF
          </el-button>
        </div>
      </div>
    </div>

    <!-- Attendance Data Table -->
    <div class="overflow-x-auto">
      <el-table
        :data="tableRows"
        v-loading="loading"
        stripe
        row-class-name="dtr-row-custom"
        empty-text="No attendance records found for this period"
      >
        <!-- Date -->
        <el-table-column prop="date" label="Date" width="130">
          <template #default="{ row }">
            <div class="font-semibold text-slate-900 text-xs">
              {{ row.date }}
            </div>
            <div class="text-[10px] text-slate-400 font-sans uppercase">
              {{ row.day_name }}
            </div>
          </template>
        </el-table-column>

        <!-- Day Type -->
        <el-table-column label="Day Type" width="130">
          <template #default="{ row }">
            <span :class="dayTypeBadgeClass(row)" class="px-2.5 py-1 rounded-full text-[11px] font-bold inline-block">
              {{ row.day_type_label || (row.is_holiday ? 'Holiday' : (row.is_restday ? 'Rest Day' : (row.is_ob ? 'Official Travel' : 'Regular'))) }}
            </span>
          </template>
        </el-table-column>

        <!-- Punches (AM / PM) -->
        <el-table-column label="Punches (AM / PM)" min-width="220">
          <template #default="{ row }">
            <div class="text-xs space-y-0.5">
              <span class="text-slate-700 font-medium block">
                AM: {{ formatPunchTime(row.am_in) }} – {{ formatPunchTime(row.am_out) }}
              </span>
              <span class="text-slate-700 font-medium block">
                PM: {{ formatPunchTime(row.pm_in) }} – {{ formatPunchTime(row.pm_out) }}
              </span>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="Worked Hours" min-width="130">
          <template #default="{ row }">
            <span class="text-xs font-bold text-slate-900">
              {{ formatWorkHours(row.hours_worked || row.work_hours) }}
            </span>
          </template>
        </el-table-column>

        <!-- Late (mins) -->
        <el-table-column label="Late" width="95">
          <template #default="{ row }">
            <span v-if="row.late > 0" class="text-amber-700 font-bold text-xs bg-amber-50 px-2 py-0.5 rounded border border-amber-200">
              {{ row.late }}m
            </span>
            <span v-else class="text-slate-300 text-xs">—</span>
          </template>
        </el-table-column>
        <el-table-column label="Undertime" width="105">
          <template #default="{ row }">
            <span v-if="row.undertime > 0" class="text-orange-700 font-bold text-xs bg-orange-50 px-2 py-0.5 rounded border border-orange-200">
              {{ row.undertime }}m
            </span>
            <span v-else class="text-slate-300 text-xs">—</span>
          </template>
        </el-table-column>

        <!-- Remarks -->
        <el-table-column label="Remarks" min-width="150">
          <template #default="{ row }">
            <span v-if="row.remarks" class="text-xs text-slate-600 bg-slate-100 px-2 py-1 rounded">
              {{ row.remarks }}
            </span>
            <span v-else class="text-slate-400 text-xs">—</span>
          </template>
        </el-table-column>
      </el-table>
    </div>

    <!-- Running Totals Summary Footer Card -->
    <div class="p-5 bg-slate-900 text-white grid grid-cols-2 md:grid-cols-5 gap-4">
      <!-- Stat Card 1 -->
      <div class="bg-slate-800/80 rounded-xl p-3.5 border border-slate-700">
        <span class="text-[11px] text-slate-400 font-semibold block uppercase">Present Days</span>
        <span class="text-xl font-black text-emerald-400">{{ runningTotals.daysPresent }}</span>
      </div>
      <!-- Stat Card 2 -->
      <div class="bg-slate-800/80 rounded-xl p-3.5 border border-slate-700">
        <span class="text-[11px] text-slate-400 font-semibold block uppercase">Total Late</span>
        <span class="text-xl font-black text-amber-400">{{ runningTotals.totalLate }} mins</span>
      </div>
      <!-- Stat Card 3 -->
      <div class="bg-slate-800/80 rounded-xl p-3.5 border border-slate-700">
        <span class="text-[11px] text-slate-400 font-semibold block uppercase">Total Undertime</span>
        <span class="text-xl font-black text-orange-400">{{ runningTotals.totalUndertime }} mins</span>
      </div>
      <!-- Stat Card 4 -->
      <div class="bg-slate-800/80 rounded-xl p-3.5 border border-slate-700">
        <span class="text-[11px] text-slate-400 font-semibold block uppercase">Absences</span>
        <span class="text-xl font-black text-rose-400">{{ runningTotals.totalAbsences }}</span>
      </div>
      <!-- Stat Card 5 -->
      <div class="bg-slate-800/80 rounded-xl p-3.5 border border-slate-700">
        <span class="text-[11px] text-slate-400 font-semibold block uppercase">Approved OT</span>
        <span class="text-xl font-black text-purple-400">{{ runningTotals.totalOT }} hrs</span>
      </div>
    </div>

    <!-- DTR PDF Preview Dialog -->
    <el-dialog
      v-model="pdfPreviewVisible"
      title="Daily Time Record (DTR) PDF Preview"
      width="900px"
      top="5vh"
      destroy-on-close
      @closed="cleanupPdfPreview"
    >
      <div v-loading="previewingPdf" class="w-full h-[70vh] bg-slate-100 rounded-xl overflow-hidden flex items-center justify-center border border-slate-200">
        <iframe
          v-if="pdfPreviewUrl"
          :src="pdfPreviewUrl"
          class="w-full h-full"
          frameborder="0"
        />
        <div v-else class="text-slate-400 text-sm font-medium">
          Generating PDF preview...
        </div>
      </div>

      <template #footer>
        <div class="flex items-center justify-between">
          <span class="text-xs text-slate-500 font-medium">
            Official HRIS DTR Document
          </span>
          <div class="flex items-center gap-2">
            <el-button @click="pdfPreviewVisible = false">Close</el-button>
            <el-button type="primary" :loading="exportingPdf" @click="downloadDTR">
              Download PDF
            </el-button>
          </div>
        </div>
      </template>
    </el-dialog>
  </div>
</template>

<script>
import { useToast } from 'vue-toastification'

export default {
  name: 'AttendanceHistoryTable',
  props: {
    employeeId: { type: [Number, String], required: true }
  },
  data() {
    return {
      toast: useToast(),
      loading: false,
      exportingPdf: false,
      previewingPdf: false,
      pdfPreviewVisible: false,
      pdfPreviewUrl: '',
      selectedPayrollPeriod: null,
      payrollPeriods: [],
      tableRows: []
    }
  },
  computed: {
    runningTotals() {
      let daysPresent = 0
      let totalLate = 0
      let totalUndertime = 0
      let totalAbsences = 0
      let totalOT = 0

      this.tableRows.forEach(row => {
        if ((row.hours_worked || row.work_hours || 0) > 0 || row.am_in || row.pm_in) daysPresent++
        totalLate += Number(row.late || 0)
        totalUndertime += Number(row.undertime || 0)
        if (row.absent || row.is_absent) totalAbsences++
        totalOT += Number(row.ot_hours || 0)
      })

      return { daysPresent, totalLate, totalUndertime, totalAbsences, totalOT }
    }
  },
  watch: {
    employeeId: {
      immediate: true,
      handler(newVal) {
        if (newVal) {
          this.loadPayrollPeriods()
        }
      }
    }
  },
  async mounted() {
    await this.loadPayrollPeriods()
  },
  methods: {
    formatWorkHours(val) {
      const num = Number(val)
      if (isNaN(num) || num <= 0) return '0 hrs 0 mins'
      const totalMinutes = Math.round(num * 60)
      const hrs = Math.floor(totalMinutes / 60)
      const mins = totalMinutes % 60
      if (hrs > 0 && mins > 0) return `${hrs} hrs ${mins} mins`
      if (hrs > 0) return `${hrs} hrs 0 mins`
      return `${mins} mins`
    },
    formatPeriodLabel(period) {
      if (!period) return ''
      if (period.period_description) return period.period_description
      if (period.name) return period.name
      const start = period.attendance_start_date || period.start_date
      const end = period.attendance_end_date || period.end_date
      if (start && end) return `${start} to ${end}`
      return `Payroll Period #${period.id}`
    },
    formatPunchTime(val) {
      if (!val || val === '--:--') return '--:--'
      if (val.length <= 8 && (val.includes('AM') || val.includes('PM'))) return val
      if (/^\d{2}:\d{2}(:\d{2})?$/.test(val)) {
        const parts = val.split(':')
        let hrs = parseInt(parts[0], 10)
        const mins = parts[1]
        const ampm = hrs >= 12 ? 'PM' : 'AM'
        hrs = hrs % 12 || 12
        return `${String(hrs).padStart(2, '0')}:${mins} ${ampm}`
      }
      try {
        const d = new Date(val)
        if (!isNaN(d.getTime())) {
          return d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true })
        }
      } catch (e) {}
      return val
    },
    async loadPayrollPeriods() {
      try {
        const { dtrApiService } = await import('../../services/apiService.js')
        const response = await dtrApiService.getDTRApplicationPayrollPeriods(this.employeeId)
        const raw = response?.data?.payroll_periods || response?.payroll_periods || (Array.isArray(response?.data) ? response.data : (Array.isArray(response) ? response : []))
        this.payrollPeriods = Array.isArray(raw) ? raw : []
        if (this.payrollPeriods.length > 0) {
          this.selectedPayrollPeriod = this.payrollPeriods[0].id
          await this.fetchPeriodDTR()
        }
      } catch (err) {
        console.error('Failed to load payroll periods:', err)
      }
    },
    async fetchPeriodDTR() {
      if (!this.selectedPayrollPeriod) return
      this.loading = true
      try {
        const { dtrApiService } = await import('../../services/apiService.js')
        const response = await dtrApiService.getDTRDetail(this.employeeId, this.selectedPayrollPeriod)
        const data = response?.data || response
        const records = data?.daily_time_records || data?.dtr_records || (Array.isArray(data) ? data : [])
        this.tableRows = Array.isArray(records) ? records.map(r => ({
          ...r,
          hours_worked: r.work_hours ?? r.hours_worked ?? 0,
          day_name: r.day_name || (r.date ? new Date(r.date).toLocaleDateString('en-US', { weekday: 'short' }) : '')
        })) : []
      } catch (err) {
        console.error('Fetch DTR error:', err)
        this.toast.error('Failed to load DTR history for selected period.')
      } finally {
        this.loading = false
      }
    },
    dayTypeBadgeClass(row) {
      if (row.is_holiday || row.holiday) return 'bg-blue-100 text-blue-800'
      if (row.is_restday || row.rest_day) return 'bg-indigo-100 text-indigo-800'
      if (row.leave || row.is_leave) return 'bg-sky-100 text-sky-800'
      if (row.is_ob) return 'bg-purple-100 text-purple-800'
      if (row.absent) return 'bg-rose-100 text-rose-800'
      return 'bg-emerald-100 text-emerald-800'
    },
    async downloadDTR() {
      if (!this.selectedPayrollPeriod) return
      this.exportingPdf = true
      try {
        const { dtrApiService } = await import('../../services/apiService.js')
        const blob = await dtrApiService.printDTR(this.employeeId, this.selectedPayrollPeriod)
        const url = window.URL.createObjectURL(new Blob([blob], { type: 'application/pdf' }))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', `DTR_${this.employeeId}_Period_${this.selectedPayrollPeriod}.pdf`)
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
        this.toast.success('DTR PDF exported successfully!')
      } catch (err) {
        this.toast.error('Failed to download DTR PDF.')
      } finally {
        this.exportingPdf = false
      }
    },
    async previewDTR() {
      if (!this.selectedPayrollPeriod) return
      this.previewingPdf = true
      try {
        const { dtrApiService } = await import('../../services/apiService.js')
        const blob = await dtrApiService.printDTR(this.employeeId, this.selectedPayrollPeriod)
        if (this.pdfPreviewUrl) {
          window.URL.revokeObjectURL(this.pdfPreviewUrl)
        }
        this.pdfPreviewUrl = window.URL.createObjectURL(new Blob([blob], { type: 'application/pdf' }))
        this.pdfPreviewVisible = true
      } catch (err) {
        console.error('Preview PDF error:', err)
        this.toast.error('Failed to generate DTR PDF preview.')
      } finally {
        this.previewingPdf = false
      }
    },
    cleanupPdfPreview() {
      if (this.pdfPreviewUrl) {
        window.URL.revokeObjectURL(this.pdfPreviewUrl)
        this.pdfPreviewUrl = ''
      }
    }
  }
}
</script>
