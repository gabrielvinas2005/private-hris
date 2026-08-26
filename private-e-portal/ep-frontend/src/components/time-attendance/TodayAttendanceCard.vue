<template>
  <div class="bg-white rounded-2xl p-4 sm:p-5 shadow-sm border border-slate-200 h-full flex flex-col justify-between">
    <div class="flex items-center justify-between mb-3 pb-2.5 border-b border-slate-100">
      <div class="flex items-center gap-2.5">
        <div class="p-1.5 bg-indigo-50 text-indigo-600 rounded-xl">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
        </div>
        <div>
          <h3 class="text-sm font-bold text-slate-900">Today's Attendance Card</h3>
          <p class="text-[11px] text-slate-500 font-medium">Real-time punch records and calculated hours</p>
        </div>
      </div>

      <!-- Computed Live Hours counter -->
      <div class="text-right">
        <span class="text-[11px] text-slate-400 font-medium block">Worked Hours</span>
        <span class="text-lg font-black text-indigo-600 tabular-nums">
          {{ displayHours }}
        </span>
      </div>
    </div>

    <!-- Work Suspension Alert Banner -->
    <div
      v-if="isWorkSuspended"
      class="mb-3 p-2.5 px-3.5 rounded-xl border bg-indigo-50 border-indigo-200 text-indigo-900 flex items-center justify-between text-xs font-semibold shadow-xs"
    >
      <div class="flex items-center gap-2">
        <svg class="w-4 h-4 text-indigo-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m0 0v-5a2 2 0 012-2h2a2 2 0 012 2v5m-6 0h6" />
        </svg>
        <div>
          <span class="font-bold">Work Suspended Today</span>
          <span v-if="workSuspensionReason" class="font-normal block text-[11px] text-indigo-700">{{ workSuspensionReason }}</span>
        </div>
      </div>
      <span v-if="workSuspensionWithPay" class="px-2 py-0.5 text-[10px] font-extrabold bg-emerald-100 text-emerald-800 rounded-full">WITH PAY</span>
    </div>

    <!-- Schedule Warning / Late Clock-In Alert Banner -->
    <div
      v-if="!isWorkSuspended && (scheduleWarning || isLateForClockin) && !isLateNoticeDismissed"
      class="mb-3 p-2.5 px-3.5 rounded-xl border bg-rose-50 border-rose-200 text-rose-800 flex items-center justify-between text-xs font-semibold shadow-xs"
    >
      <div class="flex items-center gap-2">
        <svg class="w-4 h-4 text-rose-600 flex-shrink-0 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
        <span>{{ scheduleWarning || 'Schedule Warning: You have not logged in according to your schedule today!' }}</span>
      </div>
      <button
        @click="dismissLateNotice"
        class="text-rose-400 hover:text-rose-700 hover:bg-rose-100 p-1 rounded-lg transition-colors ml-2 cursor-pointer flex-shrink-0"
        title="Dismiss notice"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>

    <!-- 4 Punch Time Cells -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-2.5 mb-3">
      <!-- AM IN -->
      <div class="p-2.5 px-3 rounded-xl border bg-slate-50 border-slate-200/80 flex flex-col justify-between">
        <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">AM IN</span>
        <div class="mt-1 flex items-center justify-between">
          <span class="text-base font-bold" :class="amIn ? 'text-slate-900' : 'text-slate-400'">
            {{ amIn || '-- : --' }}
          </span>
          <span v-if="amIn" class="w-2 h-2 rounded-full bg-emerald-500"></span>
        </div>
      </div>

      <!-- AM OUT -->
      <div class="p-2.5 px-3 rounded-xl border bg-slate-50 border-slate-200/80 flex flex-col justify-between">
        <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">AM OUT</span>
        <div class="mt-1 flex items-center justify-between">
          <span class="text-base font-bold" :class="amOut ? 'text-slate-900' : 'text-slate-400'">
            {{ amOut || '-- : --' }}
          </span>
          <span v-if="amOut" class="w-2 h-2 rounded-full bg-blue-500"></span>
        </div>
      </div>

      <!-- PM IN -->
      <div class="p-2.5 px-3 rounded-xl border bg-slate-50 border-slate-200/80 flex flex-col justify-between">
        <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">PM IN</span>
        <div class="mt-1 flex items-center justify-between">
          <span class="text-base font-bold" :class="pmIn ? 'text-slate-900' : 'text-slate-400'">
            {{ pmIn || '-- : --' }}
          </span>
          <span v-if="pmIn" class="w-2 h-2 rounded-full bg-emerald-500"></span>
        </div>
      </div>

      <!-- PM OUT -->
      <div class="p-2.5 px-3 rounded-xl border bg-slate-50 border-slate-200/80 flex flex-col justify-between">
        <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">PM OUT</span>
        <div class="mt-1 flex items-center justify-between">
          <span class="text-base font-bold" :class="pmOut ? 'text-slate-900' : 'text-slate-400'">
            {{ pmOut || '-- : --' }}
          </span>
          <span v-if="pmOut" class="w-2 h-2 rounded-full bg-blue-500"></span>
        </div>
      </div>
    </div>

    <!-- Flags & Correction Trigger Bar -->
    <div class="flex flex-wrap items-center justify-between gap-2.5 pt-2.5 border-t border-slate-100">
      <div class="flex items-center gap-1.5">
        <span class="text-[11px] font-semibold text-slate-500">Flags Today:</span>
        <span v-if="isWorkSuspended" class="text-[11px] bg-purple-100 text-purple-900 font-extrabold px-2.5 py-0.5 rounded-full border border-purple-300 shadow-2xs inline-flex items-center gap-1">
          <svg class="w-3 h-3 text-purple-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m0 0v-5a2 2 0 012-2h2a2 2 0 012 2v5m-6 0h6" />
          </svg>
          <span>Work Suspended</span>
        </span>
        <span v-else-if="!isLate && !isUndertime && !isMissedLog" class="text-[11px] bg-emerald-50 text-emerald-700 font-semibold px-2 py-0.5 rounded-full border border-emerald-200">
          No Exceptions
        </span>
        <span v-if="isLate" class="text-[11px] bg-amber-50 text-amber-700 font-bold px-2 py-0.5 rounded-full border border-amber-200">
          Late
        </span>
        <span v-if="isUndertime" class="text-[11px] bg-orange-50 text-orange-700 font-bold px-2 py-0.5 rounded-full border border-orange-200">
          Undertime
        </span>
        <span v-if="isMissedLog" class="text-[11px] bg-rose-50 text-rose-700 font-bold px-2 py-0.5 rounded-full border border-rose-200">
          Missed Log
        </span>
      </div>

      <!-- Request Correction Shortcut -->
      <button
        @click="$emit('request-correction')"
        class="text-[11px] font-bold text-indigo-600 hover:text-indigo-800 hover:underline flex items-center gap-1"
      >
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
        </svg>
        Request Correction
      </button>
    </div>
  </div>
</template>

<script>
export default {
  name: 'TodayAttendanceCard',
  props: {
    amIn: { type: String, default: null },
    amOut: { type: String, default: null },
    pmIn: { type: String, default: null },
    pmOut: { type: String, default: null },
    workHours: { type: [Number, String], default: 0 },
    isLate: { type: Boolean, default: false },
    isUndertime: { type: Boolean, default: false },
    isMissedLog: { type: Boolean, default: false },
    scheduleWarning: { type: String, default: null },
    isLateForClockin: { type: Boolean, default: false },
    isWorkSuspended: { type: Boolean, default: false },
    workSuspensionReason: { type: String, default: null },
    workSuspensionWithPay: { type: Boolean, default: false }
  },
  emits: ['request-correction'],
  data() {
    return {
      now: new Date(),
      timer: null,
      isLateNoticeDismissed: sessionStorage.getItem('late_clockin_notice_dismissed') === 'true'
    }
  },
  methods: {
    dismissLateNotice() {
      this.isLateNoticeDismissed = true
      sessionStorage.setItem('late_clockin_notice_dismissed', 'true')
    }
  },
  mounted() {
    this.timer = setInterval(() => {
      this.now = new Date()
    }, 10000)
  },
  beforeUnmount() {
    if (this.timer) clearInterval(this.timer)
  },
  computed: {
    displayHours() {
      let totalSeconds = 0

      const parseTimeToDate = (timeStr) => {
        if (!timeStr) return null
        const d = new Date(this.now)
        let hours = 0, minutes = 0, seconds = 0
        const isPM = /PM/i.test(timeStr)
        const isAM = /AM/i.test(timeStr)
        const clean = timeStr.replace(/(AM|PM)/i, '').trim()
        const parts = clean.split(':')
        if (parts.length >= 2) {
          hours = parseInt(parts[0], 10)
          minutes = parseInt(parts[1], 10)
          seconds = parts[2] ? parseInt(parts[2], 10) : 0
          if (isPM && hours < 12) hours += 12
          if (isAM && hours === 12) hours = 0
        }
        d.setHours(hours, minutes, seconds, 0)
        return d
      }

      const formatDuration = (secs) => {
        const totalMins = Math.max(0, Math.floor(secs / 60))
        const hrs = Math.floor(totalMins / 60)
        const mins = totalMins % 60
        if (hrs > 0 && mins > 0) {
          return `${hrs} ${hrs === 1 ? 'hr' : 'hrs'} ${mins} ${mins === 1 ? 'min' : 'mins'}`
        } else if (hrs > 0) {
          return `${hrs} ${hrs === 1 ? 'hr' : 'hrs'}`
        } else {
          return `${mins} ${mins === 1 ? 'min' : 'mins'}`
        }
      }

      const amInDate = parseTimeToDate(this.amIn)
      const amOutDate = parseTimeToDate(this.amOut)
      const pmInDate = parseTimeToDate(this.pmIn)
      const pmOutDate = parseTimeToDate(this.pmOut)

      if (amInDate) {
        let end = amOutDate
        if (!end && !pmInDate) {
          const noon = new Date(this.now)
          noon.setHours(12, 0, 0, 0)
          end = this.now > noon ? noon : this.now
        }
        if (end && end > amInDate) {
          totalSeconds += (end - amInDate) / 1000
        }
      }

      if (pmInDate) {
        let end = pmOutDate || this.now
        if (end && end > pmInDate) {
          totalSeconds += (end - pmInDate) / 1000
        }
      }

      if (totalSeconds > 0) {
        return formatDuration(totalSeconds)
      }

      const num = Number(this.workHours) || 0
      return formatDuration(num * 3600)
    }
  }
}
</script>
