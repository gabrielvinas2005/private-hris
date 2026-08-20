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
            :label="period.period_description || `${period.start_date} to ${period.end_date}`"
            :value="period.id"
          />
        </el-select>

        <!-- Download DTR Button -->
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
              {{ row.day_type_label || 'Regular' }}
            </span>
          </template>
        </el-table-column>

        <!-- Punches (AM / PM) -->
        <el-table-column label="Punches (AM / PM)" min-width="220">
          <template #default="{ row }">
            <div class="text-xs space-y-0.5">
              <span class="text-slate-700 font-medium block">
                AM: {{ row.am_in || '--:--' }} – {{ row.am_out || '--:--' }}
              </span>
              <span class="text-slate-700 font-medium block">
                PM: {{ row.pm_in || '--:--' }} – {{ row.pm_out || '--:--' }}
              </span>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="Worked Hrs" width="110">
          <template #default="{ row }">
            <span class="text-xs font-bold text-slate-900">
              {{ row.hours_worked ? Number(row.hours_worked).toFixed(2) : '0.00' }} hrs
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
        if ((row.work_hours || 0) > 0 || row.am_in || row.pm_in) daysPresent++
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
    async loadPayrollPeriods() {
      try {
        const { dtrApiService } = await import('../../services/apiService.js')
        const data = await dtrApiService.getDTRApplicationPayrollPeriods(this.employeeId)
        this.payrollPeriods = Array.isArray(data) ? data : (data.payroll_periods || data.data || [])
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
        const records = response.dtr_records || response.data || response || []
        this.tableRows = Array.isArray(records) ? records : []
      } catch (err) {
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
    }
  }
}
</script>
