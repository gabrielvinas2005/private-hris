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
          style="width: 240px"
          @change="fetchPeriodDTR"
        >
          <el-option
            v-for="period in payrollPeriods"
            :key="period.id"
            :label="formatPeriodLabel(period)"
            :value="period.id"
          />
        </el-select>

        <!-- Column Visibility Toggle -->
        <el-popover placement="bottom-end" width="220" trigger="click">
          <template #reference>
            <el-button type="default" plain class="!rounded-xl font-semibold shadow-sm">
              <template #icon>
                <svg class="w-4 h-4 mr-1 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
              </template>
              Columns
            </el-button>
          </template>
          <div class="space-y-2 p-1">
            <div class="font-bold text-xs text-slate-700 border-b border-slate-100 pb-1.5 mb-2">Column Visibility</div>
            <label class="flex items-center gap-2 text-xs text-slate-700 cursor-pointer hover:bg-slate-50 p-1 rounded">
              <input type="checkbox" v-model="visibleColumns.date" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" />
              <span>Date</span>
            </label>
            <label class="flex items-center gap-2 text-xs text-slate-700 cursor-pointer hover:bg-slate-50 p-1 rounded">
              <input type="checkbox" v-model="visibleColumns.dayType" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" />
              <span>Day Type</span>
            </label>
            <label class="flex items-center gap-2 text-xs text-slate-700 cursor-pointer hover:bg-slate-50 p-1 rounded">
              <input type="checkbox" v-model="visibleColumns.punches" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" />
              <span>Punches (AM/PM)</span>
            </label>
            <label class="flex items-center gap-2 text-xs text-slate-700 cursor-pointer hover:bg-slate-50 p-1 rounded">
              <input type="checkbox" v-model="visibleColumns.workedHours" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" />
              <span>Worked Hours</span>
            </label>
            <label class="flex items-center gap-2 text-xs text-slate-700 cursor-pointer hover:bg-slate-50 p-1 rounded">
              <input type="checkbox" v-model="visibleColumns.late" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" />
              <span>Late</span>
            </label>
            <label class="flex items-center gap-2 text-xs text-slate-700 cursor-pointer hover:bg-slate-50 p-1 rounded">
              <input type="checkbox" v-model="visibleColumns.undertime" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" />
              <span>Undertime</span>
            </label>
            <label class="flex items-center gap-2 text-xs text-slate-700 cursor-pointer hover:bg-slate-50 p-1 rounded">
              <input type="checkbox" v-model="visibleColumns.remarks" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" />
              <span>Remarks</span>
            </label>
          </div>
        </el-popover>

        <!-- Action Buttons: Refresh, Preview & Download DTR PDF -->
        <div class="flex items-center gap-2">
          <el-button
            type="default"
            plain
            :loading="loading"
            @click="fetchPeriodDTR"
            class="!rounded-xl font-semibold shadow-sm"
            title="Refresh Attendance History"
          >
            <template #icon>
              <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
            </template>
            Refresh
          </el-button>

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

    <!-- Week Navigation Bar (Top of Table) -->
    <div class="px-5 py-3 bg-slate-100/70 border-b border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3">
      <div class="flex items-center gap-2">
        <span class="text-xs font-bold text-slate-700">View Mode:</span>
        <div class="inline-flex p-0.5 bg-slate-200/80 rounded-xl border border-slate-300/60">
          <button
            @click="viewMode = 'weekly'"
            type="button"
            :class="[
              viewMode === 'weekly' ? 'bg-indigo-600 text-white font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900',
              'px-2.5 py-1 text-xs rounded-lg transition-all cursor-pointer font-semibold'
            ]"
          >
            Weekly View
          </button>
          <button
            @click="viewMode = 'all'"
            type="button"
            :class="[
              viewMode === 'all' ? 'bg-indigo-600 text-white font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900',
              'px-2.5 py-1 text-xs rounded-lg transition-all cursor-pointer font-semibold'
            ]"
          >
            All Cutoff Records
          </button>
        </div>
      </div>

      <!-- Week Selector Navigation Buttons (< This Week > or < August 16-22 >) -->
      <div v-if="viewMode === 'weekly'" class="flex items-center gap-1.5 bg-white p-1 rounded-xl border border-slate-300/80 shadow-xs">
        <button
          @click="navigateWeek(-1)"
          type="button"
          title="Previous Week"
          class="w-7 h-7 flex items-center justify-center rounded-lg text-slate-700 hover:bg-indigo-50 hover:text-indigo-600 transition-all font-bold cursor-pointer"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
          </svg>
        </button>

        <button
          @click="resetToCurrentWeek"
          type="button"
          :title="weekOffset !== 0 ? 'Click to reset to This Week' : 'Current Week'"
          :class="[
            weekOffset === 0 ? 'bg-indigo-600 text-white font-bold shadow-xs' : 'bg-slate-100 text-slate-800 hover:bg-indigo-50 hover:text-indigo-600 font-bold',
            'px-3.5 py-1 rounded-lg text-xs transition-all flex items-center gap-1.5 cursor-pointer'
          ]"
        >
          <span>{{ currentWeekLabel }}</span>
        </button>

        <button
          @click="navigateWeek(1)"
          type="button"
          title="Next Week"
          class="w-7 h-7 flex items-center justify-center rounded-lg text-slate-700 hover:bg-indigo-50 hover:text-indigo-600 transition-all font-bold cursor-pointer"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
          </svg>
        </button>
      </div>

      <div class="text-xs text-slate-500 font-medium">
        Showing <span class="font-bold text-slate-800">{{ displayTableRows.length }}</span> record(s)
      </div>
    </div>

    <!-- Attendance Data Table with Side Padding -->
    <div class="p-4 sm:p-5 bg-slate-50/40 ">
      <el-table
        :data="displayTableRows"
        v-loading="loading"
        stripe
        border
        fit
        style="width: 100%"
        :row-class-name="getRowClassName"
        :span-method="arraySpanMethod"
        empty-text="No attendance records found for this period"
      >
        <!-- Date -->
        <el-table-column v-if="visibleColumns.date" prop="date" label="Date" min-width="125" resizable>
          <template #default="{ row }">
            <div class="flex items-center gap-1.5 flex-wrap">
              <span class="font-semibold text-slate-900 text-xs">{{ row.date }}</span>
              <span v-if="isTodayRow(row)" class="px-1.5 py-0.5 rounded text-[9px] font-black bg-amber-500 text-white uppercase tracking-wider shadow-sm">
                Today
              </span>
            </div>
            <div class="text-[10px] text-slate-400 font-sans uppercase font-medium">
              {{ row.day_name }}
            </div>
          </template>
        </el-table-column>

        <!-- Day Type -->
        <el-table-column v-if="visibleColumns.dayType" min-width="180" resizable>
          <template #header>
            <div class="flex items-center justify-between gap-1.5 w-full">
              <span>Day Type</span>
              <el-select
                v-model="selectedDayType"
                placeholder="All"
                size="small"
                style="width: 110px"
                clearable
                @change="currentPage = 1"
                @click.stop
              >
                <el-option value="All" label="All" />
                <el-option value="On-Site" label="On-Site" />
                <el-option value="Work From Home" label="WFH" />
                <el-option value="On Leave" label="On Leave" />
                <el-option value="Official Business" label="OB" />
                <el-option value="Rest Day" label="Rest Day" />
                <el-option value="Holiday" label="Holiday" />
                <el-option value="Work Suspended" label="Suspended" />
              </el-select>
            </div>
          </template>
          <template #default="{ row }">
            <div v-if="isRestday(row) && !hasWorkPunches(row)" class="py-1 px-3 bg-slate-100/90 text-slate-600 rounded-lg border border-slate-200/80 text-xs font-bold flex items-center justify-center gap-2 tracking-wide">
              <span :class="dayTypeBadgeClass(row)" class="px-2.5 py-0.5 rounded-full text-[11px] font-bold inline-block">
                {{ getDayTypeLabel(row) }}
              </span>
              <span class="text-slate-400 font-normal">•</span>
              <span class="text-slate-500 uppercase text-[11px] font-semibold">No Scheduled Shift</span>
            </div>
            <span v-else :class="dayTypeBadgeClass(row)" class="px-2.5 py-1 rounded-full text-[11px] font-bold inline-block">
              {{ getDayTypeLabel(row) }}
            </span>
          </template>
        </el-table-column>

        <!-- Punches (AM / PM) -->
        <el-table-column v-if="visibleColumns.punches" label="Punches (AM / PM)" min-width="210" resizable>
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

        <!-- Worked Hours -->
        <el-table-column v-if="visibleColumns.workedHours" label="Worked Hours" min-width="130" resizable>
          <template #default="{ row }">
            <span class="text-xs font-bold text-slate-900">
              {{ formatWorkHours(row.hours_worked || row.work_hours) }}
            </span>
          </template>
        </el-table-column>

        <!-- Late (mins) -->
        <el-table-column v-if="visibleColumns.late" label="Late" min-width="90" resizable>
          <template #default="{ row }">
            <span v-if="row.late > 0" class="text-amber-700 font-bold text-xs bg-amber-50 px-2 py-0.5 rounded border border-amber-200">
              {{ row.late }}m
            </span>
            <span v-else class="text-slate-300 text-xs">—</span>
          </template>
        </el-table-column>

        <!-- Undertime -->
        <el-table-column v-if="visibleColumns.undertime" label="Undertime" min-width="100" resizable>
          <template #default="{ row }">
            <span v-if="row.undertime > 0" class="text-orange-700 font-bold text-xs bg-orange-50 px-2 py-0.5 rounded border border-orange-200">
              {{ row.undertime }}m
            </span>
            <span v-else class="text-slate-300 text-xs">—</span>
          </template>
        </el-table-column>

        <!-- Remarks -->
        <el-table-column v-if="visibleColumns.remarks" label="Remarks" min-width="160" resizable>
          <template #default="{ row }">
            <span v-if="row.remarks" class="text-xs text-slate-600 bg-slate-100 px-2 py-1 rounded">
              {{ row.remarks }}
            </span>
            <span v-else class="text-slate-400 text-xs">—</span>
          </template>
        </el-table-column>
      </el-table>
    </div>

    <!-- Pagination & Footer Summary Control Bar -->
    <div class="px-5 py-3 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3">
      <div v-if="viewMode === 'weekly'" class="flex items-center gap-2 text-xs text-slate-600 font-medium">
        <span class="p-1 bg-indigo-50 text-indigo-700 rounded-md font-bold">Weekly View</span>
        <span class="text-slate-500">
          Displaying week period <strong class="text-slate-800">{{ currentWeekLabel }}</strong> ({{ displayTableRows.length }} day records)
        </span>
      </div>

      <div v-else class="flex items-center gap-2 text-xs text-slate-600 font-medium">
        <span>Rows per page:</span>
        <el-select
          v-model="pageSize"
          size="small"
          style="width: 85px"
          @change="currentPage = 1"
        >
          <el-option :value="5" label="5" />
          <el-option :value="10" label="10" />
          <el-option :value="20" label="20" />
          <el-option :value="50" label="50" />
          <el-option :value="filteredTableRows.length || 999" label="All" />
        </el-select>
        <span class="text-slate-400 font-normal ml-1">
          Showing {{ filteredTableRows.length > 0 ? (currentPage - 1) * pageSize + 1 : 0 }}–{{ Math.min(currentPage * pageSize, filteredTableRows.length) }} of {{ filteredTableRows.length }} records
        </span>
      </div>

      <el-pagination
        v-if="viewMode === 'all' && filteredTableRows.length > pageSize"
        v-model:current-page="currentPage"
        :page-size="pageSize"
        :total="filteredTableRows.length"
        layout="prev, pager, next"
        background
        size="small"
      />
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
    employeeId: { type: [Number, String], default: null }
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
      selectedDayType: 'All',
      visibleColumns: {
        date: true,
        dayType: true,
        punches: true,
        workedHours: true,
        late: true,
        undertime: true,
        remarks: true
      },
      payrollPeriods: [],
      tableRows: [],
      currentPage: 1,
      pageSize: 10,
      pageSizeOptions: [5, 10, 20, 50],
      weekOffset: 0,
      viewMode: 'weekly'
    }
  },
  computed: {
    currentWeekLabel() {
      if (this.weekOffset === 0) {
        return 'This Week'
      }
      const { monday, sunday } = this.getWeekRange(this.weekOffset)
      const monMonth = monday.toLocaleString('en-US', { month: 'long' })
      const sunMonth = sunday.toLocaleString('en-US', { month: 'long' })
      const monDate = monday.getDate()
      const sunDate = sunday.getDate()

      if (monMonth === sunMonth) {
        return `${monMonth} ${monDate}-${sunDate}`
      } else {
        return `${monMonth} ${monDate} - ${sunMonth} ${sunDate}`
      }
    },
    filteredTableRows() {
      if (!this.selectedDayType || this.selectedDayType === 'All') {
        return this.tableRows
      }
      return this.tableRows.filter(row => {
        const label = this.getDayTypeLabel(row)
        return label === this.selectedDayType
      })
    },
    displayTableRows() {
      const rows = this.filteredTableRows
      if (this.viewMode !== 'weekly') {
        if (!this.pageSize || this.pageSize >= rows.length) {
          return rows
        }
        const start = (this.currentPage - 1) * this.pageSize
        const end = start + this.pageSize
        return rows.slice(start, end)
      }

      const { monday, sunday } = this.getWeekRange(this.weekOffset)
      const monMs = monday.getTime()
      const sunMs = sunday.getTime()

      return rows.filter(r => {
        if (!r.date) return false
        const d = new Date(r.date)
        if (isNaN(d.getTime())) return false
        const time = d.getTime()
        return time >= monMs && time <= sunMs
      })
    },
    paginatedTableRows() {
      return this.displayTableRows
    },
    runningTotals() {
      let daysPresent = 0
      let totalLate = 0
      let totalUndertime = 0
      let totalAbsences = 0
      let totalOT = 0

      this.filteredTableRows.forEach(row => {
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
    this.boundFetchDTR = () => {
      this.fetchPeriodDTR()
    }
    window.addEventListener('dtr-updated', this.boundFetchDTR)
    window.addEventListener('focus', this.boundFetchDTR)
    this.refreshTimer = setInterval(() => {
      if (this.selectedPayrollPeriod) {
        this.fetchPeriodDTR()
      }
    }, 25000)
  },
  beforeUnmount() {
    if (this.boundFetchDTR) {
      window.removeEventListener('dtr-updated', this.boundFetchDTR)
      window.removeEventListener('focus', this.boundFetchDTR)
    }
    if (this.refreshTimer) {
      clearInterval(this.refreshTimer)
    }
    this.cleanupPdfPreview()
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
      if (!this.employeeId) {
        this.payrollPeriods = []
        this.tableRows = []
        return
      }
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
        this.autoAdjustWeekOffsetToPeriod()
      } catch (err) {
        console.error('Fetch DTR error:', err)
        this.toast.error('Failed to load DTR history for selected period.')
      } finally {
        this.loading = false
      }
    },
    getWeekRange(offset = 0) {
      const now = new Date()
      const currentDay = now.getDay()
      const distanceToMonday = (currentDay + 6) % 7
      const monday = new Date(now)
      monday.setDate(now.getDate() - distanceToMonday + (offset * 7))
      monday.setHours(0, 0, 0, 0)

      const sunday = new Date(monday)
      sunday.setDate(monday.getDate() + 6)
      sunday.setHours(23, 59, 59, 999)

      return { monday, sunday }
    },
    navigateWeek(offset) {
      this.weekOffset += offset
    },
    resetToCurrentWeek() {
      this.weekOffset = 0
    },
    autoAdjustWeekOffsetToPeriod() {
      if (!this.tableRows || this.tableRows.length === 0) return
      const { monday, sunday } = this.getWeekRange(this.weekOffset)
      const monMs = monday.getTime()
      const sunMs = sunday.getTime()
      const hasCurrentMatch = this.tableRows.some(r => {
        if (!r.date) return false
        const d = new Date(r.date)
        return !isNaN(d.getTime()) && d.getTime() >= monMs && d.getTime() <= sunMs
      })

      if (!hasCurrentMatch && this.tableRows[0]?.date) {
        const firstDate = new Date(this.tableRows[0].date)
        if (!isNaN(firstDate.getTime())) {
          const now = new Date()
          const currentDay = now.getDay()
          const distToMon = (currentDay + 6) % 7
          const currentMon = new Date(now)
          currentMon.setDate(now.getDate() - distToMon)
          currentMon.setHours(0, 0, 0, 0)

          const diffMs = firstDate.getTime() - currentMon.getTime()
          const diffWeeks = Math.floor(diffMs / (1000 * 60 * 60 * 24 * 7))
          this.weekOffset = diffWeeks
        }
      }
    },
    isHoliday(row) {
      if (!row) return false
      if (row.day_type_label) return row.day_type_label === 'Holiday'
      return Number(row.is_holiday) === 1 || row.is_holiday === true
    },
    isRestday(row) {
      if (!row) return false
      if (row.day_type_label) return row.day_type_label === 'Rest Day'
      return Number(row.is_restday) === 1 || row.is_restday === true || row.rest_day === true
    },
    isOb(row) {
      if (!row) return false
      if (row.day_type_label) return row.day_type_label === 'Official Business' || row.day_type_label === 'OB'
      return Number(row.is_ob) === 1 || row.is_ob === true
    },
    isWfh(row) {
      if (!row) return false
      if (row.day_type_label) return row.day_type_label === 'Work From Home' || row.day_type_label === 'WFH'
      return Number(row.is_wfh) === 1 || row.is_wfh === true || (row.remarks && row.remarks.toLowerCase().includes('wfh'))
    },
    getDayTypeLabel(row) {
      if (!row) return 'On-Site'
      if (row.day_type_label) return row.day_type_label
      if (row.is_work_suspended || (row.remarks && (row.remarks.toLowerCase().includes('suspended') || row.remarks.toLowerCase().includes('cancellation')))) {
        return 'Work Suspended'
      }
      if (this.isHoliday(row)) return 'Holiday'
      if (row.leave || row.is_leave) return 'On Leave'
      if (this.isWfh(row)) return 'Work From Home'
      if (this.isOb(row)) return 'Official Business'
      if (this.isRestday(row)) return 'Rest Day'
      return 'On-Site'
    },
    dayTypeBadgeClass(row) {
      const label = this.getDayTypeLabel(row)
      if (label === 'Work Suspended' || label === 'Suspended') return 'bg-purple-100 text-purple-900 border border-purple-300 font-bold'
      if (label === 'Holiday') return 'bg-blue-100 text-blue-800 border border-blue-200 font-bold'
      if (label === 'On Leave') return 'bg-sky-100 text-sky-800 border border-sky-200 font-bold'
      if (label === 'Work From Home' || label === 'WFH') return 'bg-indigo-100 text-indigo-800 border border-indigo-200 font-bold'
      if (label === 'Official Business' || label === 'OB') return 'bg-teal-100 text-teal-800 border border-teal-200 font-bold'
      if (label === 'Rest Day') return 'bg-slate-200 text-slate-700 border border-slate-300 font-medium'
      return 'bg-emerald-100 text-emerald-800 border border-emerald-200 font-semibold'
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
    },
    hasWorkPunches(row) {
      if (!row) return false
      return !!(row.am_in || row.pm_in || (row.hours_worked || row.work_hours || 0) > 0)
    },
    arraySpanMethod({ row, columnIndex }) {
      if (this.isRestday(row) && !this.hasWorkPunches(row)) {
        const activeColsCount = Object.values(this.visibleColumns).filter(Boolean).length
        const dayTypeIdx = this.visibleColumns.date ? 1 : 0
        if (this.visibleColumns.dayType && columnIndex === dayTypeIdx) {
          return [1, Math.max(1, activeColsCount - dayTypeIdx)]
        } else if (columnIndex > dayTypeIdx) {
          return [0, 0]
        }
      }
      return [1, 1]
    },
    isTodayRow(row) {
      if (!row || !row.date) return false
      const todayStr = new Date().toISOString().slice(0, 10)
      return row.date === todayStr
    },
    getRowClassName({ row }) {
      if (!row || !row.date) return 'dtr-row-custom'
      
      const todayStr = new Date().toISOString().slice(0, 10)
      const isToday = row.date === todayStr
      
      const dName = (row.day_name || '').toUpperCase()
      const dayOfWeek = new Date(row.date).getDay()
      const isWeekend = dayOfWeek === 0 || dayOfWeek === 6 || dName.includes('SAT') || dName.includes('SUN') || this.isRestday(row)

      if (isToday) {
        return 'dtr-row-custom dtr-row-today'
      }
      if (isWeekend) {
        return 'dtr-row-custom dtr-row-weekend'
      }
      return 'dtr-row-custom'
    }
  }
}
</script>

<style scoped>
:deep(.el-table .dtr-row-today) {
  background-color: #fefce8 !important;
}
:deep(.el-table .dtr-row-today td.el-table__cell) {
  background-color: #fefce8 !important;
}
:deep(.el-table .dtr-row-today .el-table__cell:first-child) {
  border-left: 4px solid #f59e0b !important;
}

:deep(.el-table .dtr-row-weekend) {
  background-color: #f8fafc !important;
}
:deep(.el-table .dtr-row-weekend td.el-table__cell) {
  background-color: #f1f5f9 !important;
  color: #64748b !important;
}
</style>
