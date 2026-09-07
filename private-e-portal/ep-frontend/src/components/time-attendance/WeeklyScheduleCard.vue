<template>
  <div class="bg-white rounded-2xl p-4 sm:p-5 shadow-sm border border-slate-200 h-full flex flex-col justify-between">
    <div>
      <!-- Header with Week Selector -->
      <div class="flex items-center justify-between mb-2.5 pb-2.5 border-b border-slate-100">
        <div class="flex items-center gap-2">
          <div class="p-1.5 bg-indigo-50 text-indigo-600 rounded-xl">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
          </div>
          <div>
            <h3 class="text-sm font-bold text-slate-900">Weekly Schedule</h3>
            <p class="text-[11px] text-slate-500 font-medium">{{ weekRangeText }}</p>
          </div>
        </div>

        <!-- Week Selector Controls -->
        <div class="flex items-center gap-0.5 bg-slate-100/90 p-0.5 rounded-xl border border-slate-200/70">
          <button
            @click="navigateWeek(-1)"
            type="button"
            title="Previous Week"
            class="w-6 h-6 flex items-center justify-center rounded-lg text-slate-600 hover:bg-white hover:text-indigo-600 transition-all"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
          </button>
          
          <button
            @click="resetToCurrentWeek"
            type="button"
            :title="weekOffset !== 0 ? 'Click to reset to current week' : 'Current Week'"
            :class="[
              weekOffset === 0 ? 'bg-indigo-600 text-white font-bold' : 'text-slate-600 hover:bg-white hover:text-indigo-600',
              'text-[10px] px-2 py-0.5 rounded-lg transition-all font-semibold cursor-pointer'
            ]"
          >
            {{ weekLabelText }}
          </button>

          <button
            @click="navigateWeek(1)"
            type="button"
            title="Next Week"
            class="w-6 h-6 flex items-center justify-center rounded-lg text-slate-600 hover:bg-white hover:text-indigo-600 transition-all"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Days Strip (Bright / Light Mode UI) -->
      <div class="space-y-1">
        <div
          v-for="day in weekDays"
          :key="day.dateStr"
          :class="[
            day.isToday
              ? 'bg-gradient-to-r from-indigo-50/90 via-sky-50/80 to-indigo-50/90 text-slate-900 border-2 border-indigo-500/60 shadow-sm ring-2 ring-indigo-200/50'
              : 'bg-white text-slate-700 border border-slate-100 hover:bg-slate-50/80 hover:border-slate-200',
            'p-1.5 px-2.5 rounded-xl flex items-center justify-between transition-all duration-150'
          ]"
        >
          <!-- Left: Day Badge & Date -->
          <div class="flex items-center gap-2">
            <div
              :class="[
                day.isToday ? 'bg-indigo-600 text-white shadow-xs' : 'bg-slate-100 text-slate-700',
                'w-7 h-7 rounded-lg flex flex-col items-center justify-center text-center leading-none flex-shrink-0'
              ]"
            >
              <span class="text-[8px] font-bold uppercase">{{ day.dayShort }}</span>
              <span class="text-[11px] font-black mt-0.5">{{ day.dayNum }}</span>
            </div>
            <div>
              <div class="flex items-center gap-1">
                <span class="text-xs font-bold" :class="day.isToday ? 'text-indigo-950 font-black' : 'text-slate-800'">
                  {{ day.dayName }}
                </span>
                <span v-if="day.isToday" class="text-[8px] bg-emerald-500 text-white px-1.5 py-0.2 rounded-md font-extrabold tracking-wider uppercase">
                  TODAY
                </span>
              </div>
              <span
                v-if="isWfhOrOnSite(day)"
                class="text-[10px] block"
                :class="day.isToday ? 'text-indigo-700 font-semibold' : 'text-slate-500'"
              >
                {{ day.timeWindow }}
              </span>
            </div>
          </div>

          <!-- Right: Setup Mode Badge -->
          <div>
            <span
              v-if="day.dayTypeLabel === 'Work Suspended' || day.dayTypeLabel === 'Suspended'"
              class="text-[10.5px] px-2.5 py-1 rounded-lg font-extrabold bg-purple-100 text-purple-900 border border-purple-300 shadow-xs"
            >
              Suspended
            </span>
            <span
              v-else-if="day.dayTypeLabel === 'Holiday'"
              class="text-[10.5px] px-2.5 py-1 rounded-lg font-bold bg-blue-100 text-blue-800 border border-blue-200 shadow-xs"
            >
              Holiday
            </span>
            <span
              v-else-if="day.dayTypeLabel === 'On Leave' || day.dayTypeLabel === 'Leave'"
              class="text-[10.5px] px-2.5 py-1 rounded-lg font-bold bg-sky-100 text-sky-800 border border-sky-200 shadow-xs"
            >
              On Leave
            </span>
            <span
              v-else-if="day.dayTypeLabel === 'Official Business' || day.dayTypeLabel === 'OB'"
              class="text-[10.5px] px-2.5 py-1 rounded-lg font-bold bg-teal-100 text-teal-800 border border-teal-200 shadow-xs"
            >
              OB
            </span>
            <span
              v-else-if="day.dayTypeLabel === 'Work From Home' || day.dayTypeLabel === 'WFH'"
              class="text-[10.5px] px-2.5 py-1 rounded-lg font-bold bg-indigo-100 text-indigo-800 border border-indigo-200 shadow-xs"
            >
              WFH
            </span>
            <span
              v-else-if="day.dayTypeLabel === 'Rest Day'"
              class="text-[10.5px] px-2.5 py-1 rounded-lg font-bold bg-slate-200 text-slate-700 border border-slate-300 shadow-xs"
            >
              Rest Day
            </span>
            <span
              v-else
              class="text-[10.5px] px-2.5 py-1 rounded-lg font-bold bg-emerald-100 text-emerald-800 border border-emerald-200 shadow-xs"
            >
              On-Site
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Footer Summary -->
    <div class="mt-2.5 pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
      <span class="font-medium">{{ scheduledSummary.summaryText }}</span>
      <span class="font-bold text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded-md border border-indigo-100">{{ scheduledSummary.totalHours }} hrs total</span>
    </div>
  </div>
</template>

<script>
export default {
  name: 'WeeklyScheduleCard',
  props: {
    employeeId: { type: [Number, String], default: null },
    scheduleName: { type: String, default: 'Fixed Schedule' },
    scheduleWindow: { type: String, default: '08:00 AM - 05:00 PM' },
    setupType: { type: String, default: 'on_site' },
    isWfhToday: { type: Boolean, default: false },
    weeklySchedule: { type: Array, default: () => [] },
    workCancellations: { type: Array, default: () => [] },
    dtrRecords: { type: Array, default: () => [] }
  },
  data() {
    return {
      weekDays: [],
      weekRangeText: '',
      weekOffset: 0,
      weeklyTimeLogs: []
    }
  },
  computed: {
    weekLabelText() {
      if (this.weekOffset === 0) return 'This Week'
      if (this.weekOffset < 0) return 'Previous Week'
      return 'Next Week'
    },
    scheduledSummary() {
      if (!this.weekDays || this.weekDays.length === 0) {
        return { summaryText: '0 Work Days Scheduled', totalHours: '0.0' }
      }

      let activeWorkDays = 0
      let wfhCount = 0
      let onSiteCount = 0
      let leaveCount = 0
      let holidayCount = 0
      let obCount = 0
      let suspendedCount = 0
      let totalHoursNum = 0

      this.weekDays.forEach(d => {
        const label = (d.dayTypeLabel || '').toLowerCase().trim()
        const isRest = d.isRestDay || label === 'rest day'
        const isSuspended = d.isWorkSuspended || label === 'work suspended' || label === 'suspended'
        const isHoliday = label === 'holiday'
        const isLeave = label === 'on leave' || label === 'leave'
        const isOb = label === 'official business' || label === 'ob'
        const isWfh = label === 'work from home' || label === 'wfh' || d.isWfh

        if (isSuspended) {
          suspendedCount++
        } else if (!isRest) {
          activeWorkDays++

          if (isWfh) {
            wfhCount++
          } else if (isLeave) {
            leaveCount++
          } else if (isHoliday) {
            holidayCount++
          } else if (isOb) {
            obCount++
          } else {
            onSiteCount++
          }

          // 8 hrs standard per active work day shift
          totalHoursNum += 8
        }
      })

      const totalHours = totalHoursNum.toFixed(1)
      let summaryText = `${activeWorkDays} Work ${activeWorkDays === 1 ? 'Day' : 'Days'} Scheduled`

      const details = []
      if (wfhCount > 0) details.push(`${wfhCount} WFH`)
      if (onSiteCount > 0 && (wfhCount > 0 || leaveCount > 0 || holidayCount > 0 || obCount > 0)) details.push(`${onSiteCount} On-Site`)
      if (leaveCount > 0) details.push(`${leaveCount} Leave`)
      if (holidayCount > 0) details.push(`${holidayCount} Holiday`)
      if (obCount > 0) details.push(`${obCount} OB`)
      if (suspendedCount > 0) details.push(`${suspendedCount} Suspended`)

      if (details.length > 0) {
        summaryText += ` (${details.join(', ')})`
      }

      return { summaryText, totalHours }
    }
  },
  watch: {
    employeeId() {
      this.buildWeekDays()
    },
    setupType() {
      this.buildWeekDays()
    },
    isWfhToday() {
      this.buildWeekDays()
    },
    dtrRecords: {
      deep: true,
      handler() {
        this.buildWeekDays()
      }
    },
    weeklySchedule: {
      deep: true,
      handler() {
        this.buildWeekDays()
      }
    },
    workCancellations: {
      deep: true,
      handler() {
        this.buildWeekDays()
      }
    }
  },
  mounted() {
    this.buildWeekDays()
  },
  methods: {
    isWfhOrOnSite(day) {
      if (!day || !day.dayTypeLabel) return true
      const label = String(day.dayTypeLabel).toLowerCase().trim()
      return label === 'on-site' || label === 'onsite' || label === 'work from home' || label === 'wfh' || label === 'office'
    },
    navigateWeek(offset) {
      this.weekOffset += offset
      this.buildWeekDays()
    },
    resetToCurrentWeek() {
      this.weekOffset = 0
      this.buildWeekDays()
    },
    async buildWeekDays() {
      const now = new Date()
      const currentDayOfWeek = now.getDay() // 0 = Sun, 1 = Mon, ...
      
      // Calculate Monday of target week based on weekOffset
      const distanceToMonday = (currentDayOfWeek + 6) % 7
      const monday = new Date(now)
      monday.setDate(now.getDate() - distanceToMonday + (this.weekOffset * 7))

      const sunday = new Date(monday)
      sunday.setDate(monday.getDate() + 6)

      const optionsMonth = { month: 'short', day: 'numeric' }
      this.weekRangeText = `${monday.toLocaleDateString('en-US', optionsMonth)} - ${sunday.toLocaleDateString('en-US', optionsMonth)}, ${sunday.getFullYear()}`

      const mondayStr = `${monday.getFullYear()}-${String(monday.getMonth() + 1).padStart(2, '0')}-${String(monday.getDate()).padStart(2, '0')}`
      const sundayStr = `${sunday.getFullYear()}-${String(sunday.getMonth() + 1).padStart(2, '0')}-${String(sunday.getDate()).padStart(2, '0')}`

      // Fetch accurate week time logs for the displayed week date range regardless of selected payroll period
      if (this.employeeId) {
        try {
          const { dtrApiService } = await import('../../services/apiService.js')
          const logsData = await dtrApiService.getTimeLogs(this.employeeId, mondayStr, sundayStr, { skipBioSync: true })
          this.weeklyTimeLogs = Array.isArray(logsData) ? logsData : (logsData?.data || [])
        } catch (err) {
          console.warn('Failed to fetch weekly logs:', err)
        }
      }

      const dayNames = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']
      const dayShorts = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']

      const daysArr = []
      for (let i = 0; i < 7; i++) {
        const d = new Date(monday)
        d.setDate(monday.getDate() + i)

        // Format YYYY-MM-DD in local time
        const year = d.getFullYear()
        const month = String(d.getMonth() + 1).padStart(2, '0')
        const dateDay = String(d.getDate()).padStart(2, '0')
        const dateStr = `${year}-${month}-${dateDay}`

        const isToday = d.toDateString() === now.toDateString()
        const dayId = i + 1 // 1 = Monday ... 7 = Sunday

        // Priority 1: Match from fetched weeklyTimeLogs for this specific displayed week
        let dtrMatch = (this.weeklyTimeLogs || []).find(r => r.date && r.date.slice(0, 10) === dateStr)

        // Priority 2: Fall back to dtrRecords prop
        if (!dtrMatch) {
          dtrMatch = (this.dtrRecords || []).find(r => r.date && r.date.slice(0, 10) === dateStr)
        }

        let dayTypeLabel = 'On-Site'
        let isRestDay = (i === 5 || i === 6)
        let isWorkSuspended = false
        let workSuspensionReason = ''
        let isWfh = false
        let timeWindow = isRestDay ? 'Rest Day' : (this.scheduleWindow || '08:00 AM - 05:00 PM')

        if (dtrMatch) {
          if (dtrMatch.day_type_label) {
            dayTypeLabel = dtrMatch.day_type_label
          } else if (dtrMatch.is_work_suspended || (dtrMatch.remarks && (dtrMatch.remarks.toLowerCase().includes('suspended') || dtrMatch.remarks.toLowerCase().includes('cancellation')))) {
            dayTypeLabel = 'Work Suspended'
          } else if (Number(dtrMatch.is_holiday) === 1 || dtrMatch.is_holiday === true || dtrMatch.day_type === 'holiday') {
            dayTypeLabel = 'Holiday'
          } else if (dtrMatch.leave || dtrMatch.is_leave) {
            dayTypeLabel = 'On Leave'
          } else if (Number(dtrMatch.is_ob) === 1 || dtrMatch.is_ob === true) {
            dayTypeLabel = 'Official Business'
          } else if (Number(dtrMatch.is_wfh) === 1 || dtrMatch.is_wfh === true || (dtrMatch.remarks && dtrMatch.remarks.toLowerCase().includes('wfh'))) {
            dayTypeLabel = 'Work From Home'
          } else if (Number(dtrMatch.is_restday) === 1 || dtrMatch.is_restday === true) {
            dayTypeLabel = 'Rest Day'
          } else {
            dayTypeLabel = 'On-Site'
          }

          isRestDay = dayTypeLabel === 'Rest Day'
          isWorkSuspended = dayTypeLabel === 'Work Suspended' || dayTypeLabel === 'Suspended'
          isWfh = dayTypeLabel === 'Work From Home' || dayTypeLabel === 'WFH'

          timeWindow = dtrMatch.time_window || (isRestDay ? 'Rest Day' : (isWorkSuspended ? 'Suspended' : (this.scheduleWindow || '08:00 AM - 05:00 PM')))
        } else {
          // Priority 3: Fall back to workCancellations, weeklySchedule setup, or todayStatus
          const currentMs = new Date(year, d.getMonth(), d.getDate()).setHours(0, 0, 0, 0)
          const wcMatch = (this.workCancellations || []).find(wc => {
            if (!wc.date_from || !wc.date_to) return false
            try {
              const fromStr = String(wc.date_from).substring(0, 10)
              const toStr = String(wc.date_to).substring(0, 10)
              const fromMs = new Date(`${fromStr}T00:00:00`).setHours(0, 0, 0, 0)
              const toMs = new Date(`${toStr}T23:59:59`).setHours(23, 59, 59, 999)
              return currentMs >= fromMs && currentMs <= toMs
            } catch (_) {
              return false
            }
          })

          const detail = (this.weeklySchedule || []).find(s => Number(s.day_id) === dayId)

          if (wcMatch) {
            dayTypeLabel = 'Work Suspended'
            isWorkSuspended = true
            workSuspensionReason = wcMatch.reason || 'Work Suspended'
            timeWindow = `Suspended (${workSuspensionReason})`
          } else if (detail) {
            isRestDay = !!detail.is_restday
            isWfh = !!detail.is_wfh
            dayTypeLabel = isRestDay ? 'Rest Day' : (isWfh ? 'Work From Home' : 'On-Site')
            timeWindow = detail.time_window || (isRestDay ? 'Rest Day' : (this.scheduleWindow || '08:00 AM - 05:00 PM'))
          } else if (isToday) {
            isWfh = this.setupType === 'wfh' || this.isWfhToday
            dayTypeLabel = isWfh ? 'Work From Home' : 'On-Site'
          }
        }

        daysArr.push({
          dateStr,
          dayName: dayNames[i],
          dayShort: dayShorts[i],
          dayNum: d.getDate(),
          isToday,
          isRestDay,
          isWorkSuspended,
          workSuspensionReason,
          timeWindow,
          isWfh,
          dayTypeLabel
        })
      }

      this.weekDays = daysArr
    }
  }
}
</script>
