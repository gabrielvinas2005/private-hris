<template>
  <div class="w-full">
    <!-- Schedule Mode Banner -->
    <el-alert
      v-if="workingMode"
      class="mb-4"
      :title="scheduleBannerTitle"
      :description="scheduleBannerDescription"
      :type="scheduleBannerType"
      show-icon
      :closable="false"
    >
      <template v-if="scheduleDetail" #default>
        <div class="mt-2 flex flex-wrap gap-3 text-sm">
          <span v-if="scheduleDetail.am_in">
            <strong>Shift Start:</strong> {{ scheduleDetail.am_in }}
          </span>
          <span v-if="scheduleDetail.pm_out">
            <strong>Shift End:</strong> {{ scheduleDetail.pm_out }}
          </span>
          <span v-if="scheduleDetail.grace_period > 0">
            <strong>Grace Period:</strong> {{ scheduleDetail.grace_period }} min
          </span>
          <span v-if="scheduleDetail.work_hours">
            <strong>Required Hours:</strong> {{ scheduleDetail.work_hours }}h
          </span>
        </div>
      </template>
    </el-alert>

    <!-- On-site Blocker: show info card instead of clock-in UI -->
    <el-result
      v-if="workingMode === 'onsite'"
      icon="warning"
      title="On-Site Attendance Required"
      sub-title="Your schedule today requires you to be at the office. Please use the company hardware attendance machine to log your time."
    >
      <template #extra>
        <el-tag type="warning" size="large" class="mr-2">
          <el-icon class="mr-1"><OfficeBuilding /></el-icon>
          Hardware Attendance Device
        </el-tag>
        <el-tag type="info" size="large">
          Your working mode can only be changed by HR / authorized personnel.
        </el-tag>
      </template>
    </el-result>

    <!-- Rest Day Blocker -->
    <el-result
      v-else-if="workingMode === 'rest_day'"
      icon="info"
      title="Rest Day"
      sub-title="Today is a scheduled rest day. No clock-in is required."
    />

    <!-- WFH / Unknown mode: show normal time clock -->
    <template v-else>
      <!-- Main Time Clock Layout -->
      <el-row :gutter="24" class="mb-6">
        <!-- Left Side: Time Clock -->
        <el-col :span="12">
          <el-card shadow="hover" class="h-full">
            <template #header>
              <div class="flex items-center justify-between">
                <div class="flex items-center">
                  <el-icon class="mr-2" :size="24"><Clock /></el-icon>
                  <span class="text-lg font-medium">Work From Home Time Clock</span>
                </div>
                <div class="flex items-center gap-2">
                  <el-tag
                    v-if="workingMode === 'wfh'"
                    type="success"
                    size="small"
                  >
                    <el-icon class="mr-1"><House /></el-icon> WFH Day
                  </el-tag>
                  <el-tag type="info" size="large">{{ currentDate }}</el-tag>
                </div>
              </div>
            </template>
            <div class="text-center py-8">
              <div class="text-5xl font-bold text-blue-600 mb-4">{{ currentTime }}</div>
              <div class="text-lg text-gray-500 mb-2">Current Time</div>
              <div class="text-sm mt-2">
                <span v-if="workingMode === 'wfh'" class="text-green-600 font-medium">
                  ✓ Your schedule allows WFH today. Use this portal to clock in.
                </span>
                <span v-else class="text-gray-500">
                  Schedule not configured. Contact HR if you need WFH access.
                </span>
              </div>
            </div>
          </el-card>
        </el-col>

        <!-- Right Side: Status and Actions -->
        <el-col :span="12">
          <el-card shadow="hover" class="h-full">
            <template #header>
              <div class="flex items-center">
                <el-icon class="mr-2" :size="24"><Timer /></el-icon>
                <span class="text-lg font-medium">Status &amp; Actions</span>
              </div>
            </template>

            <!-- Current Status Section -->
            <div class="mb-6">
              <div class="flex items-center mb-3">
                <el-icon class="mr-2" :size="20"><component :is="getStatusIcon()" /></el-icon>
                <span class="font-medium">Current Status</span>
              </div>
              <el-alert
                :title="getStatusTitle()"
                :description="getStatusMessage()"
                :type="getStatusAlertType()"
                show-icon
                :closable="false"
              />
            </div>

            <!-- Time Clock Actions Section -->
            <div>
              <div class="flex items-center mb-4">
                <el-icon class="mr-2" :size="20"><Timer /></el-icon>
                <span class="font-medium">Time Clock Actions</span>
              </div>
              <el-space direction="vertical" size="large" class="w-full">
                <el-row :gutter="12">
                  <el-col :span="12">
                    <el-button
                      @click="timeIn"
                      :disabled="!canTimeIn || loading"
                      :type="canTimeIn ? 'success' : 'info'"
                      size="large"
                      class="w-full"
                      :loading="loading"
                    >
                      <el-icon class="mr-2"><Plus /></el-icon>
                      Time In
                    </el-button>
                  </el-col>
                  <el-col :span="12">
                    <el-button
                      @click="timeOut"
                      :disabled="!canTimeOut || loading"
                      :type="canTimeOut ? 'danger' : 'info'"
                      size="large"
                      class="w-full"
                      :loading="loading"
                    >
                      <el-icon class="mr-2"><Minus /></el-icon>
                      Time Out
                    </el-button>
                  </el-col>
                </el-row>
                <el-row :gutter="12">
                  <el-col :span="12">
                    <el-button
                      @click="breakIn"
                      :disabled="!canBreakIn || loading"
                      :type="canBreakIn ? 'warning' : 'info'"
                      size="large"
                      class="w-full"
                      :loading="loading"
                    >
                      <el-icon class="mr-2"><Coffee /></el-icon>
                      Break In
                    </el-button>
                  </el-col>
                  <el-col :span="12">
                    <el-button
                      @click="breakOut"
                      :disabled="!canBreakOut || loading"
                      :type="canBreakOut ? 'primary' : 'info'"
                      size="large"
                      class="w-full"
                      :loading="loading"
                    >
                      <el-icon class="mr-2"><Coffee /></el-icon>
                      Break Out
                    </el-button>
                  </el-col>
                </el-row>
              </el-space>
            </div>
          </el-card>
        </el-col>
      </el-row>

      <!-- Today's Record -->
      <el-card v-if="todayRecord || status !== 'not_started'" class="mb-4" shadow="hover">
        <template #header>
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <el-icon class="mr-2"><Document /></el-icon>
              <span>Today's Record</span>
            </div>
            <el-tag v-if="todayRecord && todayRecord.work_hours > 0" type="success" size="large">
              {{ todayRecord.work_hours }} hours
            </el-tag>
          </div>
        </template>
        <el-descriptions :column="4" border>
          <el-descriptions-item label="Time In">
            <el-tag v-if="todayRecord && todayRecord.am_in" type="primary">{{ todayRecord.am_in }}</el-tag>
            <el-tag v-else type="info">--:--</el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="Break In">
            <el-tag v-if="todayRecord && todayRecord.break_in" type="warning">{{ todayRecord.break_in }}</el-tag>
            <el-tag v-else type="info">--:--</el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="Break Out">
            <el-tag v-if="todayRecord && todayRecord.break_out" type="warning">{{ todayRecord.break_out }}</el-tag>
            <el-tag v-else type="info">--:--</el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="Time Out">
            <el-tag v-if="todayRecord && todayRecord.pm_out" type="danger">{{ todayRecord.pm_out }}</el-tag>
            <el-tag v-else type="info">--:--</el-tag>
          </el-descriptions-item>
        </el-descriptions>
      </el-card>
    </template>

    <!-- WFH Details Dialog -->
    <el-dialog
      v-model="showWFHModal"
      title="WFH Clock-In Details"
      width="400px"
      :close-on-click-modal="false"
    >
      <el-form :model="wfhDetails" label-width="80px">
        <el-form-item label="Reason">
          <el-input
            v-model="wfhDetails.reason"
            placeholder="e.g., Health concerns, Family emergency"
            clearable
          />
        </el-form-item>
        <el-form-item label="Location">
          <el-input
            v-model="wfhDetails.location"
            placeholder="e.g., Home, Remote office"
            clearable
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="showWFHModal = false">Cancel</el-button>
        <el-button
          type="primary"
          @click="confirmTimeIn"
          :loading="loading"
        >
          Confirm Time In
        </el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script>
import {
  Clock,
  Plus,
  Minus,
  Timer,
  Document,
  Check,
  Lightning,
  Coffee,
  House,
  OfficeBuilding
} from '@element-plus/icons-vue'

export default {
  name: 'WFHTimeClock',
  components: {
    Clock, Plus, Minus, Timer, Document, Check, Lightning, Coffee, House, OfficeBuilding
  },
  props: {
    employeeId: {
      type: [String, Number],
      required: true
    }
  },
  emits: ['status-updated'],
  data() {
    return {
      loading: false,
      currentTime: '',
      currentDate: '',
      todayRecord: null,
      status: 'not_started',
      showWFHModal: false,
      wfhDetails: { reason: '', location: '' },
      // Schedule-driven fields from API
      workingMode: null,       // 'wfh' | 'onsite' | 'rest_day' | 'unknown' | null
      scheduleDetail: null,    // fix_schedules_details row
      hasApprovedWFH: false
    }
  },
  computed: {
    canTimeIn() {
      return this.status === 'not_started' && this.workingMode === 'wfh'
    },
    canTimeOut() {
      return this.status === 'working' || this.status === 'on_break'
    },
    canBreakIn() {
      return this.status === 'working'
    },
    canBreakOut() {
      return this.status === 'on_break'
    },

    scheduleBannerType() {
      const map = { wfh: 'success', onsite: 'warning', rest_day: 'info', unknown: 'info' }
      return map[this.workingMode] ?? 'info'
    },
    scheduleBannerTitle() {
      const map = {
        wfh:      'Work-From-Home Day',
        onsite:   'On-Site Day — Use Hardware Attendance Machine',
        rest_day: 'Rest Day',
        unknown:  'Schedule Not Configured'
      }
      return map[this.workingMode] ?? 'Loading Schedule...'
    },
    scheduleBannerDescription() {
      const map = {
        wfh:      'Your schedule for today is WFH. Use this portal to clock in and out.',
        onsite:   'Your schedule requires on-site presence. Clock in using the company attendance machine. This portal is locked for today.',
        rest_day: 'Today is a scheduled rest day. No attendance logging is required.',
        unknown:  'Your schedule has not been set up yet. Contact HR to configure your fix schedule.'
      }
      return map[this.workingMode] ?? ''
    }
  },
  mounted() {
    this.updateDateTime()
    setInterval(this.updateDateTime, 1000)
    this.loadTodayStatus()
  },
  methods: {
    updateDateTime() {
      const now = new Date()
      this.currentTime = now.toLocaleTimeString('en-US', {
        hour12: false, hour: '2-digit', minute: '2-digit', second: '2-digit'
      })
      this.currentDate = now.toLocaleDateString('en-US', {
        weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
      })
    },

    async loadTodayStatus() {
      try {
        const { wfhAttendanceApiService } = await import('../../services/apiService.js')
        const response = await wfhAttendanceApiService.getTodayStatus(this.employeeId)
        if (response.success) {
          this.status         = response.data.status
          this.hasApprovedWFH = !!response.data.has_approved_wfh
          this.workingMode    = response.data.working_mode ?? null
          this.scheduleDetail = response.data.schedule_detail ?? null

          if (response.data.record) {
            this.todayRecord = response.data.record
          } else if (this.status !== 'not_started') {
            setTimeout(() => this.loadTodayStatus(), 500)
          }
        }
      } catch (error) {
        console.error('Error loading today status:', error)
      }
    },

    timeIn() {
      this.showWFHModal = true
    },

    async confirmTimeIn() {
      try {
        this.loading = true
        const { wfhAttendanceApiService } = await import('../../services/apiService.js')
        const response = await wfhAttendanceApiService.timeIn(this.employeeId, {
          wfh_reason:   this.wfhDetails.reason,
          wfh_location: this.wfhDetails.location
        })

        if (response.success) {
          this.showWFHModal = false
          this.wfhDetails = { reason: '', location: '' }
          if (response.data.record) {
            this.todayRecord = response.data.record
            this.status = this.determineStatusFromRecord(response.data.record)
          }
          await this.loadTodayStatus()
          this.$emit('status-updated')
          this.$toast?.success('Time in recorded successfully!')
        } else {
          this.$toast?.error(response.message || 'Failed to record time in')
        }
      } catch (error) {
        const msg = error.response?.data?.message || error.message || 'Failed to record time in'
        this.$toast?.error(msg)
      } finally {
        this.loading = false
      }
    },

    async timeOut() {
      if (!confirm('Are you sure you want to time out?')) return
      try {
        this.loading = true
        const { wfhAttendanceApiService } = await import('../../services/apiService.js')
        const response = await wfhAttendanceApiService.timeOut(this.employeeId, {})
        if (response.success) {
          await this.loadTodayStatus()
          this.$emit('status-updated')
          this.$toast?.success('Time out recorded successfully!')
        } else {
          this.$toast?.error(response.message || 'Failed to record time out')
        }
      } catch (error) {
        this.$toast?.error(error.response?.data?.message || 'Failed to record time out')
      } finally {
        this.loading = false
      }
    },

    async breakIn() {
      try {
        this.loading = true
        const { wfhAttendanceApiService } = await import('../../services/apiService.js')
        const response = await wfhAttendanceApiService.breakIn(this.employeeId, {})
        if (response.success) {
          await this.loadTodayStatus()
          this.$emit('status-updated')
          this.$toast?.success('Break in recorded successfully!')
        } else {
          this.$toast?.error(response.message || 'Failed to record break in')
        }
      } catch (error) {
        this.$toast?.error(error.response?.data?.message || 'Failed to record break in')
      } finally {
        this.loading = false
      }
    },

    async breakOut() {
      try {
        this.loading = true
        const { wfhAttendanceApiService } = await import('../../services/apiService.js')
        const response = await wfhAttendanceApiService.breakOut(this.employeeId, {})
        if (response.success) {
          await this.loadTodayStatus()
          this.$emit('status-updated')
          this.$toast?.success('Break out recorded successfully!')
        } else {
          this.$toast?.error(response.message || 'Failed to record break out')
        }
      } catch (error) {
        this.$toast?.error(error.response?.data?.message || 'Failed to record break out')
      } finally {
        this.loading = false
      }
    },

    getStatusTitle() {
      return { not_started: 'Ready to Start', working: 'Currently Working', on_break: 'On Break', completed: 'Work Completed' }[this.status] ?? 'Unknown Status'
    },

    getStatusMessage() {
      const msgs = {
        not_started: this.workingMode === 'wfh'
          ? 'Your WFH schedule is active. Click Time In to begin.'
          : 'Portal clock-in is only available on WFH days.',
        working:   'You are currently working from home.',
        on_break:  'You are on break. Click "Break Out" when ready to resume.',
        completed: 'Great job! You have completed your work from home day.'
      }
      return msgs[this.status] ?? 'Status unknown'
    },

    getStatusIcon() {
      return { not_started: 'Plus', working: 'Lightning', on_break: 'Coffee', completed: 'Check' }[this.status] ?? 'Plus'
    },

    getStatusAlertType() {
      return { not_started: 'info', working: 'success', on_break: 'warning', completed: 'success' }[this.status] ?? 'info'
    },

    determineStatusFromRecord(record) {
      if (!record || !record.am_in) return 'not_started'
      if (record.pm_out) return 'completed'
      if (record.break_in && !record.break_out) return 'on_break'
      return 'working'
    }
  }
}
</script>
