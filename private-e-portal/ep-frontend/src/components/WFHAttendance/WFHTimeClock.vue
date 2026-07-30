<template>
  <div class="w-full">
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
              <el-tag type="info" size="large">{{ currentDate }}</el-tag>
            </div>
          </template>
          <div class="text-center py-8">
            <div class="text-5xl font-bold text-blue-600 mb-4">{{ currentTime }}</div>
            <div class="text-lg text-gray-500 mb-2">Current Time</div>
            <div class="text-sm text-gray-400">
              <span v-if="isFriday && hasApprovedWFH" class="text-green-600 font-medium">
                ✓ WFH Available Today (Approved Application)
              </span>
              <span v-else-if="isFriday && !hasApprovedWFH" class="text-orange-600 font-medium">
                ⚠ WFH is available on Fridays only if you have an approved WFH application.
              </span>
              <span v-else class="text-orange-600 font-medium">
                ⚠ WFH Time Clock is only available on Fridays.
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
              <span class="text-lg font-medium">Status & Actions</span>
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
          <el-tag v-if="todayRecord.work_hours > 0" type="success" size="large">
            {{ todayRecord.work_hours }} hours
          </el-tag>
        </div>
      </template>
      <el-descriptions :column="4" border>
        <el-descriptions-item label="Time In">
          <el-tag v-if="todayRecord.am_in" type="primary">{{ todayRecord.am_in }}</el-tag>
          <el-tag v-else type="info">--:--</el-tag>
        </el-descriptions-item>
        <el-descriptions-item label="Break In">
          <el-tag v-if="todayRecord.break_in" type="warning">{{ todayRecord.break_in }}</el-tag>
          <el-tag v-else type="info">--:--</el-tag>
        </el-descriptions-item>
        <el-descriptions-item label="Break Out">
          <el-tag v-if="todayRecord.break_out" type="warning">{{ todayRecord.break_out }}</el-tag>
          <el-tag v-else type="info">--:--</el-tag>
        </el-descriptions-item>
        <el-descriptions-item label="Time Out">
          <el-tag v-if="todayRecord.pm_out" type="danger">{{ todayRecord.pm_out }}</el-tag>
          <el-tag v-else type="info">--:--</el-tag>
        </el-descriptions-item>
      </el-descriptions>
    </el-card>

    <!-- WFH Details Dialog -->
    <el-dialog
      v-model="showWFHModal"
      title="WFH Details"
      width="400px"
      :close-on-click-modal="false"
    >
      <el-form :model="wfhDetails" label-width="80px">
        <el-form-item label="WFH Reason">
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
  Coffee
} from '@element-plus/icons-vue'

export default {
  name: 'WFHTimeClock',
  components: {
    Clock,
    Plus,
    Minus,
    Timer,
    Document,
    Check,
    Lightning,
    Coffee
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
      wfhDetails: {
        reason: '',
        location: ''
      },
      mode: 'time_clock', // 'time_clock' or 'application'
      hasApprovedWFH: false
    }
  },
  computed: {
    isFriday() {
      const today = new Date()
      return today.getDay() === 5 // 5 = Friday
    },
    canTimeIn() {
      // Allow time-in when:
      // - It is Friday (WFH is mandatory), OR
      // - It is not Friday but there is an approved WFH application for today
      return this.status === 'not_started' && (this.isFriday || this.hasApprovedWFH)
    },
    canTimeOut() {
      return this.status === 'working' || this.status === 'on_break'
    },
    canBreakIn() {
      return this.status === 'working'
    },
    canBreakOut() {
      return this.status === 'on_break'
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
        hour12: false,
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
      })
      this.currentDate = now.toLocaleDateString('en-US', { 
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      })
    },


    async loadTodayStatus() {
      try {
        const { wfhAttendanceApiService } = await import('../../services/apiService.js')
        const response = await wfhAttendanceApiService.getTodayStatus(this.employeeId)
        if (response.success) {
          this.status = response.data.status
          this.hasApprovedWFH = !!response.data.has_approved_wfh
          // Always set todayRecord if we have record data, even if status is not_started
          if (response.data.record) {
            this.todayRecord = response.data.record
          } else if (this.status !== 'not_started') {
            // If we have a status but no record, it might be a newly created record
            // Reload after a short delay to get the updated record
            setTimeout(() => this.loadTodayStatus(), 500)
          }
        }
      } catch (error) {
        console.error('Error loading today status:', error)
      }
    },

    async timeIn() {
      this.showWFHModal = true
    },

    async confirmTimeIn() {
      try {
        this.loading = true
        const { wfhAttendanceApiService } = await import('../../services/apiService.js')
        const payload = {
          ...this.wfhDetails
        }
        const response = await wfhAttendanceApiService.timeIn(this.employeeId, payload)
        
        if (response.success) {
          this.showWFHModal = false
          this.wfhDetails = { reason: '', location: '' }
          
          // Immediately update status if we have record data from response
          if (response.data.record) {
            this.todayRecord = response.data.record
            this.status = this.determineStatusFromRecord(response.data.record)
          }
          
          // Also reload to ensure we have latest data
          await this.loadTodayStatus()
          this.$emit('status-updated')
          this.$toast?.success('Time in recorded successfully!')
        } else {
          this.$toast?.error(response.message || 'Failed to record time in')
        }
      } catch (error) {
        console.error('Error recording time in:', error)
        this.$toast?.error(error.response?.data?.message || 'Failed to record time in')
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
        console.error('Error recording time out:', error)
        this.$toast?.error(error.response?.data?.message || 'Failed to record time out')
      } finally {
        this.loading = false
      }
    },

    async breakIn() {
      try {
        this.loading = true
        const { wfhAttendanceApiService } = await import('../../services/apiService.js')
        // Include demo_date if in demo mode
        const payload = this.demoMode ? { demo_date: this.demoDate } : {}
        const response = await wfhAttendanceApiService.breakIn(this.employeeId, payload)
        
        if (response.success) {
          await this.loadTodayStatus()
          this.$emit('status-updated')
          this.$toast?.success('Break in recorded successfully!')
        } else {
          this.$toast?.error(response.message || 'Failed to record break in')
        }
      } catch (error) {
        console.error('Error recording break in:', error)
        this.$toast?.error(error.response?.data?.message || 'Failed to record break in')
      } finally {
        this.loading = false
      }
    },

    async breakOut() {
      try {
        this.loading = true
        const { wfhAttendanceApiService } = await import('../../services/apiService.js')
        // Include demo_date if in demo mode
        const payload = this.demoMode ? { demo_date: this.demoDate } : {}
        const response = await wfhAttendanceApiService.breakOut(this.employeeId, payload)
        
        if (response.success) {
          await this.loadTodayStatus()
          this.$emit('status-updated')
          this.$toast?.success('Break out recorded successfully!')
        } else {
          this.$toast?.error(response.message || 'Failed to record break out')
        }
      } catch (error) {
        console.error('Error recording break out:', error)
        this.$toast?.error(error.response?.data?.message || 'Failed to record break out')
      } finally {
        this.loading = false
      }
    },

    getStatusTitle() {
      const titles = {
        'not_started': 'Ready to Start',
        'working': 'Currently Working',
        'on_break': 'On Break',
        'completed': 'Work Completed'
      }
      return titles[this.status] || 'Unknown Status'
    },

    getStatusMessage() {
      const messages = {
        'not_started': this.hasApprovedWFH
          ? 'Your WFH application is approved. Please time in to start your work from home day.'
          : (this.isFriday
              ? 'WFH is mandatory today (Friday). Please coordinate with HR if your WFH setup is not yet configured.'
              : 'WFH time clock is only available outside Fridays when you have an approved WFH application.'),
        'working': 'You are currently working from home. Take a break when needed.',
        'on_break': 'You are on break. Click "Break Out" when ready to resume working from home.',
        'completed': 'Great job! You have completed your work from home day.'
      }
      return messages[this.status] || 'Status unknown'
    },

    getStatusIcon() {
      const icons = {
        'not_started': 'Plus',
        'working': 'Lightning',
        'on_break': 'Coffee',
        'completed': 'Check'
      }
      return icons[this.status] || icons['not_started']
    },

    getStatusIconStyle() {
      const styles = {
        'not_started': { backgroundColor: '#f5f5f5', color: '#666666' },
        'working': { backgroundColor: '#f6ffed', color: '#52c41a' },
        'on_break': { backgroundColor: '#fffbe6', color: '#faad14' },
        'completed': { backgroundColor: '#e6f7ff', color: '#1890ff' }
      }
      return styles[this.status] || styles['not_started']
    },

    getStatusAlertType() {
      const types = {
        'not_started': 'info',
        'working': 'success',
        'on_break': 'warning',
        'completed': 'success'
      }
      return types[this.status] || 'info'
    },

    determineStatusFromRecord(record) {
      if (!record || !record.am_in) {
        return 'not_started'
      }
      if (record.pm_out) {
        return 'completed'
      }
      if (record.break_in && !record.break_out) {
        return 'on_break'
      }
      if (record.am_in && !record.pm_out) {
        return 'working'
      }
      return 'partial'
    }
  }
}
</script>
