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
            :class="[
              weekOffset === 0 ? 'bg-indigo-600 text-white font-bold' : 'text-slate-600 hover:bg-white',
              'text-[10px] px-1.5 py-0.5 rounded-lg transition-all font-semibold'
            ]"
          >
            This Week
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
              <span class="text-[10px] block" :class="day.isToday ? 'text-indigo-700 font-semibold' : 'text-slate-500'">
                {{ day.timeWindow }}
              </span>
            </div>
          </div>

          <!-- Right: Setup Mode Badge -->
          <div>
            <span
              v-if="day.isWorkSuspended"
              class="text-[9px] px-1.5 py-0.5 rounded-md font-extrabold bg-purple-100 text-purple-900 border border-purple-300 shadow-xs"
            >
              Suspended
            </span>
            <span
              v-else-if="day.isRestDay"
              class="text-[9px] px-1.5 py-0.5 rounded-md font-medium bg-slate-100 text-slate-400 border border-slate-200/80"
            >
              Rest Day
            </span>
            <span
              v-else-if="day.isWfh"
              class="text-[9px] px-1.5 py-0.5 rounded-md font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/80"
            >
              WFH
            </span>
            <span
              v-else
              class="text-[9px] px-1.5 py-0.5 rounded-md font-semibold bg-slate-100 text-slate-600 border border-slate-200"
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
    scheduleName: { type: String, default: 'Fixed Schedule' },
    scheduleWindow: { type: String, default: '08:00 AM - 05:00 PM' },
    setupType: { type: String, default: 'on_site' },
    isWfhToday: { type: Boolean, default: false },
    weeklySchedule: { type: Array, default: () => [] },
    workCancellations: { type: Array, default: () => [] }
  },
  data() {
    return {
      weekDays: [],
      weekRangeText: '',
      weekOffset: 0
    }
  },
  computed: {
    scheduledSummary() {
      const activeWorkDays = this.weekDays.filter(d => !d.isRestDay && !d.isWorkSuspended).length
      const suspendedDays = this.weekDays.filter(d => !d.isRestDay && d.isWorkSuspended).length
      const totalHours = (activeWorkDays * 8).toFixed(1)
      let summaryText = `${activeWorkDays} Work Days Scheduled`
      if (suspendedDays > 0) {
        summaryText += ` (${suspendedDays} Suspended)`
      }
      return { summaryText, totalHours }
    }
  },
  watch: {
    setupType() {
      this.buildWeekDays()
    },
    isWfhToday() {
      this.buildWeekDays()
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
    navigateWeek(offset) {
      this.weekOffset += offset
      this.buildWeekDays()
    },
    resetToCurrentWeek() {
      this.weekOffset = 0
      this.buildWeekDays()
    },
    buildWeekDays() {
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

        const detail = (this.weeklySchedule || []).find(s => Number(s.day_id) === dayId)

        let isRestDay = (i === 5 || i === 6)
        let isWfh = false
        let timeWindow = isRestDay ? 'Rest Day' : (this.scheduleWindow || '08:00 AM - 05:00 PM')

        // Check for active Work Suspension / Cancellation on this date
        const currentMs = new Date(year, d.getMonth(), d.getDate()).setHours(0,0,0,0)

        const wcMatch = (this.workCancellations || []).find(wc => {
          if (!wc.date_from || !wc.date_to) return false
          try {
            const fromStr = String(wc.date_from).substring(0, 10)
            const toStr = String(wc.date_to).substring(0, 10)
            const fromMs = new Date(`${fromStr}T00:00:00`).setHours(0,0,0,0)
            const toMs = new Date(`${toStr}T23:59:59`).setHours(23,59,59,999)
            return currentMs >= fromMs && currentMs <= toMs
          } catch (_) {
            return false
          }
        })

        const isWorkSuspended = Boolean(wcMatch)
        const workSuspensionReason = wcMatch?.reason || 'Work Suspended'

        if (isWorkSuspended) {
          timeWindow = `Suspended (${workSuspensionReason})`
        } else if (detail) {
          isRestDay = !!detail.is_restday
          isWfh = !!detail.is_wfh
          timeWindow = detail.time_window || (isRestDay ? 'Rest Day' : '08:00 AM - 05:00 PM')
        } else if (isToday) {
          isWfh = this.setupType === 'wfh' || this.isWfhToday
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
          isWfh
        })
      }

      this.weekDays = daysArr
    }
  }
}
</script>
