<template>
  <el-dialog
    v-model="visibleModel"
    :show-close="true"
    width="460px"
    class="!rounded-3xl border-0 overflow-hidden shadow-2xl lunch-modal"
    align-center
  >
    <div class="p-6 text-center space-y-4">
      <!-- Icon Container -->
      <div
        :class="iconContainerClass"
        class="w-16 h-16 mx-auto rounded-2xl flex items-center justify-center shadow-inner transition-transform duration-300 transform hover:scale-105"
      >
        <!-- 10 Mins Before Lunch or 10 Mins Before End (Clock/Timer Icon) -->
        <svg v-if="alertType === '10_before_lunch' || alertType === '10_before_end'" class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>

        <!-- Lunch Start (Utensils / Food Icon) -->
        <svg v-else-if="alertType === 'lunch_start'" class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>

        <!-- Lunch Ended / Work Resume (Briefcase / Check Icon) -->
        <svg v-else class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
      </div>

      <!-- Badge / Header Tag -->
      <div class="inline-block">
        <span
          :class="badgeClass"
          class="px-3 py-1 rounded-full text-[11px] font-extrabold uppercase tracking-widest border"
        >
          {{ alertBadgeText }}
        </span>
      </div>

      <!-- Title & Message -->
      <div>
        <h3 class="text-xl font-black text-slate-900 tracking-tight">
          {{ alertTitle }}
        </h3>
        <p class="text-xs text-slate-600 font-medium leading-relaxed mt-2 px-2">
          {{ alertMessage }}
        </p>
      </div>

      <!-- Setup Note Pill -->
      <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/80 text-[11px] text-slate-600 flex items-center justify-center gap-2">
        <span class="w-2 h-2 rounded-full" :class="setupType === 'wfh' ? 'bg-indigo-500' : 'bg-emerald-500'"></span>
        <span class="font-semibold">{{ setupNote }}</span>
      </div>

      <!-- Action Button -->
      <div class="pt-2">
        <button
          @click="visibleModel = false"
          :class="buttonClass"
          class="w-full py-3 rounded-xl font-bold text-xs shadow-md hover:shadow-lg transition-all duration-200"
        >
          Got it, Thank You!
        </button>
      </div>
    </div>
  </el-dialog>
</template>

<script>
export default {
  name: 'LunchBreakModal',
  props: {
    visible: { type: Boolean, default: false },
    alertType: { type: String, default: 'lunch_start' }, // 10_before_lunch, lunch_start, 10_before_end, lunch_end
    setupType: { type: String, default: 'on_site' } // on_site or wfh
  },
  emits: ['update:visible'],
  computed: {
    visibleModel: {
      get() { return this.visible },
      set(val) { this.$emit('update:visible', val) }
    },
    alertBadgeText() {
      switch (this.alertType) {
        case '10_before_lunch': return 'Upcoming Break (11:50 AM)'
        case 'lunch_start': return 'Lunch Break (12:00 PM)'
        case '10_before_end': return 'Ending Soon (12:50 PM)'
        case 'lunch_end': return 'Afternoon Shift (1:00 PM)'
        default: return 'Lunch Break Alert'
      }
    },
    alertTitle() {
      switch (this.alertType) {
        case '10_before_lunch': return '10 Minutes Before Lunch Time'
        case 'lunch_start': return 'It\'s Lunch Time! (12:00 PM)'
        case '10_before_end': return '10 Minutes Before Lunch Ends'
        case 'lunch_end': return 'Lunch Time Ended — Afternoon Shift'
        default: return 'Lunch Break Notification'
      }
    },
    alertMessage() {
      if (this.alertType === '10_before_lunch') {
        return 'Lunch break starts at 12:00 PM in 10 minutes. Prepare to wrap up your morning tasks and take a break!'
      }
      if (this.alertType === 'lunch_start') {
        if (this.setupType === 'on_site') {
          return 'Lunch break has officially started! Since you are on-site, your AM OUT (12:00 PM) and PM IN (1:00 PM) are automatically recorded for you.'
        }
        return 'Lunch break has officially started! Enjoy your meal. Don\'t forget to punch AM OUT on your Web Clock.'
      }
      if (this.alertType === '10_before_end') {
        return 'Lunch break ends at 1:00 PM in 10 minutes. Please prepare to head back to your workstation for the afternoon session.'
      }
      if (this.alertType === 'lunch_end') {
        if (this.setupType === 'on_site') {
          return 'Lunch break is over and your afternoon shift has begun! Your PM IN (1:00 PM) is automatically credited.'
        }
        return 'Lunch break is over and your afternoon shift has begun! Remember to punch PM IN on your Web Clock.'
      }
      return 'Lunch break notification.'
    },
    setupNote() {
      if (this.setupType === 'on_site') {
        return 'On-Site Setup: Lunch AM OUT (12:00 PM) & PM IN (1:00 PM) are auto-recorded.'
      }
      return 'WFH Setup: Use Web Clock to record AM OUT & PM IN.'
    },
    iconContainerClass() {
      switch (this.alertType) {
        case '10_before_lunch': return 'bg-amber-100 text-amber-600 border border-amber-200'
        case 'lunch_start': return 'bg-emerald-100 text-emerald-600 border border-emerald-200'
        case '10_before_end': return 'bg-orange-100 text-orange-600 border border-orange-200'
        case 'lunch_end': return 'bg-indigo-100 text-indigo-600 border border-indigo-200'
        default: return 'bg-slate-100 text-slate-600'
      }
    },
    badgeClass() {
      switch (this.alertType) {
        case '10_before_lunch': return 'bg-amber-50 text-amber-700 border-amber-200'
        case 'lunch_start': return 'bg-emerald-50 text-emerald-700 border-emerald-200'
        case '10_before_end': return 'bg-orange-50 text-orange-700 border-orange-200'
        case 'lunch_end': return 'bg-indigo-50 text-indigo-700 border-indigo-200'
        default: return 'bg-slate-50 text-slate-700 border-slate-200'
      }
    },
    buttonClass() {
      switch (this.alertType) {
        case '10_before_lunch': return 'bg-amber-600 text-white hover:bg-amber-700'
        case 'lunch_start': return 'bg-emerald-600 text-white hover:bg-emerald-700'
        case '10_before_end': return 'bg-orange-600 text-white hover:bg-orange-700'
        case 'lunch_end': return 'bg-indigo-600 text-white hover:bg-indigo-700'
        default: return 'bg-slate-900 text-white hover:bg-slate-800'
      }
    }
  }
}
</script>

<style scoped>
.lunch-modal :deep(.el-dialog__header) {
  display: none !important;
}
.lunch-modal :deep(.el-dialog__body) {
  padding: 0 !important;
}
</style>
