<template>
  <div class="bg-gradient-to-r from-slate-900 via-slate-800 to-indigo-950 text-white rounded-2xl p-6 shadow-xl border border-slate-700/50 mb-6">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
      <!-- Left: Date, Time & Server Sync -->
      <div class="flex items-center gap-4">
        <div class="w-14 h-14 rounded-2xl bg-indigo-600/30 border border-indigo-400/30 flex items-center justify-center text-indigo-300 shadow-inner">
          <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <div>
          <div class="flex items-center gap-2 text-xs uppercase tracking-wider font-semibold text-indigo-300 mb-1">
            <span class="inline-block w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
            Server Synced Clock
          </div>
          <h2 class="text-3xl font-extrabold tracking-tight text-white">
            {{ currentTimeString }}
          </h2>
          <p class="text-sm text-slate-300 font-medium mt-0.5">
            {{ currentDateString }}
          </p>
        </div>
      </div>

      <!-- Center: Schedule Window & Assigned Setup -->
      <div class="bg-slate-800/80 backdrop-blur-md rounded-xl p-3.5 px-5 border border-slate-700/60 flex items-center gap-4">
        <div class="p-2 bg-slate-700/60 rounded-lg text-slate-300">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
        </div>
        <div v-if="isLoading" class="space-y-1.5 animate-pulse">
          <div class="h-3 w-24 bg-slate-700 rounded"></div>
          <div class="h-4 w-36 bg-slate-600 rounded"></div>
        </div>
        <div v-else>
          <div class="flex items-center gap-2">
            <span class="text-xs text-slate-400 font-medium">Today's Schedule</span>
            <span v-if="setupType === 'wfh'" class="text-[10px] bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 px-2 py-0.5 rounded-full font-bold">
              WFH Setup
            </span>
            <span v-else class="text-[10px] bg-slate-700 text-slate-300 border border-slate-600 px-2 py-0.5 rounded-full font-bold">
              On-Site Setup
            </span>
          </div>
          <span class="text-sm font-semibold text-slate-100 block mt-0.5">{{ scheduleName }}</span>
          <span class="text-xs text-indigo-300 block">{{ scheduleWindow }}</span>
        </div>
      </div>

      <!-- Right: Current Status Badge & Quick Clock Button -->
      <div class="flex items-center gap-4">
        <!-- Status Badge -->
        <div class="flex flex-col items-end">
          <span class="text-xs text-slate-400 font-medium mb-1">Current Status</span>
          <span :class="statusBadgeClass" class="px-3.5 py-1.5 rounded-full text-xs font-bold uppercase tracking-wide flex items-center gap-1.5 shadow-sm">
            <span :class="statusDotClass" class="w-2 h-2 rounded-full"></span>
            {{ currentStatus }}
          </span>
        </div>

        <!-- Quick Clock Button or Loading Skeleton -->
        <div v-if="isLoading" class="w-36 h-10 bg-slate-800 rounded-xl animate-pulse border border-slate-700"></div>
        <button
          v-else-if="enableWebClock"
          @click="$emit('open-clock')"
          :disabled="isBiometricLocked"
          :class="[
            isBiometricLocked 
              ? 'bg-slate-700 text-slate-400 cursor-not-allowed border-slate-600' 
              : isClockedIn 
                ? 'bg-rose-600 hover:bg-rose-500 text-white shadow-rose-900/40' 
                : 'bg-emerald-600 hover:bg-emerald-500 text-white shadow-emerald-900/40',
            'px-5 py-3 rounded-xl font-bold text-sm shadow-lg transition-all duration-200 flex items-center gap-2 transform active:scale-95'
          ]"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          {{ isBiometricLocked ? 'Biometric Recorded' : (isClockedIn ? 'Clock Out' : 'Clock In') }}
        </button>
        <div v-else class="px-4 py-2.5 rounded-xl text-xs font-semibold text-amber-300 bg-slate-800/90 border border-amber-500/30 flex flex-col items-start gap-0.5">
          <span class="flex items-center gap-1.5 font-bold">
            <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
            On-Site Biometric Assigned
          </span>
          <span class="text-[11px] text-slate-300">Use Office Biometric Hardware</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'HeaderSummaryBar',
  props: {
    serverTime: { type: String, default: null },
    todayDate: { type: String, default: null },
    currentStatus: { type: String, default: 'Clocked Out' },
    scheduleName: { type: String, default: 'Fixed Schedule' },
    scheduleWindow: { type: String, default: '08:00 AM - 05:00 PM' },
    isBiometricLocked: { type: Boolean, default: false },
    setupType: { type: String, default: 'on_site' },
    loggingMethod: { type: String, default: 'Office Biometric Terminal' },
    // Control Panel configurable: allow/disallow web clock punching
    enableWebClock: { type: Boolean, default: false },
    isLoading: { type: Boolean, default: false }
  },
  emits: ['open-clock'],
  data() {
    return {
      clockInterval: null,
      now: new Date()
    }
  },
  computed: {
    currentTimeString() {
      return this.now.toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: true
      })
    },
    currentDateString() {
      return this.now.toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      })
    },
    isClockedIn() {
      return this.currentStatus === 'Clocked In'
    },
    statusBadgeClass() {
      switch (this.currentStatus) {
        case 'Clocked In':
          return 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/40'
        case 'On Leave':
          return 'bg-blue-500/20 text-blue-300 border border-blue-500/40'
        case 'On Travel':
          return 'bg-purple-500/20 text-purple-300 border border-purple-500/40'
        case 'WFH':
          return 'bg-teal-500/20 text-teal-300 border border-teal-500/40'
        case 'Rest Day':
          return 'bg-indigo-500/20 text-indigo-300 border border-indigo-500/40'
        default:
          return 'bg-slate-700/50 text-slate-300 border border-slate-600'
      }
    },
    statusDotClass() {
      switch (this.currentStatus) {
        case 'Clocked In': return 'bg-emerald-400 animate-pulse'
        case 'On Leave': return 'bg-blue-400'
        case 'On Travel': return 'bg-purple-400'
        case 'WFH': return 'bg-teal-400'
        case 'Rest Day': return 'bg-indigo-400'
        default: return 'bg-slate-400'
      }
    }
  },
  mounted() {
    this.clockInterval = setInterval(() => {
      this.now = new Date()
    }, 1000)
  },
  beforeUnmount() {
    if (this.clockInterval) clearInterval(this.clockInterval)
  }
}
</script>
