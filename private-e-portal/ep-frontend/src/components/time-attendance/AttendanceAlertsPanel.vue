<template>
  <div v-if="visibleAlerts.length > 0" class="space-y-3 mb-6">
    <div
      v-for="alert in visibleAlerts"
      :key="alert.id"
      :class="alertCardClass(alert.type)"
      class="p-4 rounded-xl border shadow-sm flex items-start justify-between gap-4 transition-all duration-200"
    >
      <div class="flex items-start gap-3">
        <div :class="iconBgClass(alert.type)" class="p-2 rounded-lg mt-0.5 flex-shrink-0">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="alertIconPath(alert.type)" />
          </svg>
        </div>
        <div>
          <h4 class="text-xs uppercase font-bold tracking-wider" :class="titleTextClass(alert.type)">
            {{ alert.title }}
          </h4>
          <p class="text-sm font-semibold mt-0.5 text-slate-800">
            {{ alert.message }}
          </p>
        </div>
      </div>

      <div class="flex items-center gap-2 flex-shrink-0">
        <button
          v-if="alert.actionLabel"
          @click="$emit('alert-action', alert)"
          class="text-xs font-bold px-3 py-1.5 rounded-lg bg-white shadow-sm border border-slate-200 hover:bg-slate-50 transition-colors flex-shrink-0 cursor-pointer"
        >
          {{ alert.actionLabel }}
        </button>
        <button
          @click="dismissAlert(alert.id)"
          :class="closeBtnClass(alert.type)"
          class="p-1.5 rounded-lg transition-colors cursor-pointer"
          title="Dismiss alert"
          aria-label="Dismiss alert"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'AttendanceAlertsPanel',
  props: {
    isClockedIn: { type: Boolean, default: false },
    isMissedLogToday: { type: Boolean, default: false },
    isOvertimeApproved: { type: Boolean, default: false },
    isClockedInOver8Hours: { type: Boolean, default: false },
    passSlipsUsed: { type: Number, default: 0 },
    passSlipLimit: { type: Number, default: 4 },
    lunchAlertType: { type: String, default: null }, // 10_before_lunch, lunch_start, 10_before_end, lunch_end
    setupType: { type: String, default: 'on_site' }
  },
  emits: ['alert-action', 'alert-dismiss'],
  data() {
    return {
      dismissedAlertIds: []
    }
  },
  computed: {
    alerts() {
      const list = []

      // 1. Real-Time Lunch Break Banners (Only if employee is clocked in / active today)
      if (this.isClockedIn && this.lunchAlertType) {
        if (this.lunchAlertType === '10_before_lunch') {
          list.push({
            id: 'lunch-10-before',
            type: 'warning',
            title: 'Lunch Break Ahead (10 Mins)',
            message: 'Lunch break starts at 12:00 PM in 10 minutes. Prepare to wrap up your morning tasks!',
            actionLabel: 'View Details',
            action: 'lunch_popup'
          })
        } else if (this.lunchAlertType === 'lunch_start') {
          list.push({
            id: 'lunch-start',
            type: 'info',
            title: 'Lunch Break Started (12:00 PM)',
            message: this.setupType === 'on_site'
              ? 'Lunch break has officially started! (On-Site setup: AM OUT 12:00 PM & PM IN 1:00 PM are auto-recorded for you).'
              : 'Lunch break has officially started! Enjoy your meal. Don\'t forget to punch AM OUT on your Web Clock.',
            actionLabel: 'View Details',
            action: 'lunch_popup'
          })
        } else if (this.lunchAlertType === '10_before_end') {
          list.push({
            id: 'lunch-10-end',
            type: 'warning',
            title: 'Lunch Break Ending Soon (10 Mins)',
            message: 'Lunch break ends at 1:00 PM in 10 minutes. Please head back to your workstation for the afternoon shift.',
            actionLabel: 'View Details',
            action: 'lunch_popup'
          })
        } else if (this.lunchAlertType === 'lunch_end') {
          list.push({
            id: 'lunch-end',
            type: 'info',
            title: 'Lunch Break Ended — Afternoon Shift (1:00 PM)',
            message: this.setupType === 'on_site'
              ? 'Lunch break is over and afternoon shift has begun! Your PM IN (1:00 PM) punch is automatically credited.'
              : 'Lunch break is over! Welcome back. Remember to punch PM IN on your Web Clock.',
            actionLabel: 'View Details',
            action: 'lunch_popup'
          })
        }
      }

      if (this.isMissedLogToday) {
        list.push({
          id: 'missed-log',
          type: 'warning',
          title: 'Unresolved Attendance Exception',
          message: "You have an unresolved missed log or missing punch slot — submit a correction request to avoid undertime deduction.",
          actionLabel: 'Submit Correction',
          action: 'correction'
        })
      }

      if (this.isClockedInOver8Hours) {
        list.push({
          id: 'clockout-nudge',
          type: 'info',
          title: 'Safety & Compliance Nudge',
          message: "Reminder: You've been clocked in for over 8 rendered hours today. Remember to clock out when wrapping up.",
          actionLabel: 'Clock Out Now',
          action: 'clockout'
        })
      }

      if (this.passSlipsUsed >= this.passSlipLimit) {
        list.push({
          id: 'passslip-limit',
          type: 'danger',
          title: 'Pass Slip Quota Alert',
          message: `Monthly pass slip usage limit reached (${this.passSlipsUsed} of ${this.passSlipLimit} used). Additional pass slips require special division chief approval.`,
          actionLabel: 'View Details',
          action: 'passslip'
        })
      }

      return list
    },
    visibleAlerts() {
      return this.alerts.filter(alert => !this.dismissedAlertIds.includes(alert.id))
    }
  },
  methods: {
    dismissAlert(alertId) {
      if (!this.dismissedAlertIds.includes(alertId)) {
        this.dismissedAlertIds.push(alertId)
      }
      this.$emit('alert-dismiss', alertId)
    },
    alertCardClass(type) {
      switch (type) {
        case 'warning': return 'bg-amber-50/90 border-amber-200'
        case 'danger': return 'bg-rose-50/90 border-rose-200'
        case 'info': default: return 'bg-indigo-50/90 border-indigo-200'
      }
    },
    iconBgClass(type) {
      switch (type) {
        case 'warning': return 'bg-amber-100 text-amber-700'
        case 'danger': return 'bg-rose-100 text-rose-700'
        case 'info': default: return 'bg-indigo-100 text-indigo-700'
      }
    },
    titleTextClass(type) {
      switch (type) {
        case 'warning': return 'text-amber-800'
        case 'danger': return 'text-rose-800'
        case 'info': default: return 'text-indigo-800'
      }
    },
    closeBtnClass(type) {
      switch (type) {
        case 'warning': return 'text-amber-600 hover:text-amber-900 hover:bg-amber-100/60'
        case 'danger': return 'text-rose-600 hover:text-rose-900 hover:bg-rose-100/60'
        case 'info': default: return 'text-indigo-600 hover:text-indigo-900 hover:bg-indigo-100/60'
      }
    },
    alertIconPath(type) {
      switch (type) {
        case 'warning': return 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'
        case 'danger': return 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'
        case 'info': default: return 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'
      }
    }
  }
}
</script>
