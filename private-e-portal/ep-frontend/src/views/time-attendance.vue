<template>
  <MainLayout :breadcrumbs="breadcrumbs">
    <div class="max-w-7xl mx-auto space-y-4">
      <!-- Section 2.1: Header Summary Bar, Alerts & Today's Punch Card (Full Width) -->
      <div class="space-y-4">
        <HeaderSummaryBar
          :server-time="todayStatus.server_time"
          :today-date="todayStatus.today_date"
          :current-status="todayStatus.status"
          :schedule-name="todayStatus.schedule_name"
          :schedule-window="todayStatus.schedule_window"
          :is-biometric-locked="todayStatus.has_biometric_today"
          :enable-web-clock="todayStatus.enable_web_clock"
          :setup-type="todayStatus.setup_type || 'on_site'"
          :logging-method="todayStatus.logging_method || 'Office Biometric Terminal'"
          :is-loading="isLoadingStatus"
          @open-clock="showClockModal = true"
        />

        <!-- Section 2.10: Notifications & Smart Alerts Panel -->
        <AttendanceAlertsPanel
          :is-clocked-in="todayStatus.status === 'Clocked In' || Boolean(todayStatus.am_in)"
          :is-missed-log-today="todayStatus.is_missed_log"
          :is-clocked-in-over8-hours="todayStatus.status === 'Clocked In' && todayStatus.work_hours > 8"
          :pass-slips-used="todayStatus.pass_slips_used_this_month"
          :pass-slip-limit="todayStatus.pass_slip_monthly_limit"
          :lunch-alert-type="lunchAlertType"
          :setup-type="todayStatus.setup_type"
          @alert-action="handleAlertAction"
        />

        <!-- Today's Attendance Punch Card (Full Width) -->
        <TodayAttendanceCard
          :am-in="todayStatus.am_in"
          :am-out="todayStatus.am_out"
          :pm-in="todayStatus.pm_in"
          :pm-out="todayStatus.pm_out"
          :work-hours="todayStatus.work_hours"
          :is-late="todayStatus.is_late"
          :is-undertime="todayStatus.is_undertime"
          :is-missed-log="todayStatus.is_missed_log"
          :schedule-warning="todayStatus.schedule_warning"
          :is-late-for-clockin="todayStatus.is_late_for_clockin"
          :is-work-suspended="todayStatus.is_work_suspended"
          :work-suspension-reason="todayStatus.work_suspension_reason"
          :work-suspension-with-pay="todayStatus.work_suspension_with_pay"
          :is-loading="isLoadingStatus"
          @request-correction="activeTab = 'correction'"
        />
      </div>

      <!-- Main Navigation Category Tabs (High Contrast Dark Bar) -->
      <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-2xl p-2.5 shadow-xl border border-slate-800">
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 md:pb-0 scrollbar-none">
          <button
            v-for="tab in mainTabs"
            :key="tab.id"
            @click="activeTab = tab.id"
            :class="[
              activeTab === tab.id
                ? 'bg-gradient-to-r from-indigo-500 to-indigo-600 text-white shadow-lg shadow-indigo-500/30 font-bold'
                : 'text-slate-300 hover:text-white hover:bg-slate-800/70 font-semibold',
              'px-4 py-2.5 rounded-xl text-xs flex items-center gap-2 whitespace-nowrap transition-all duration-200'
            ]"
          >
            <span>{{ tab.name }}</span>
          </button>
        </div>
      </div>

      <!-- 5-Column Main Layout: Left 4 cols (Body Content), Right 2 cols (Weekly Schedule) -->
      <div class="grid grid-cols-1 lg:grid-cols-6 gap-4 items-start">

        <!-- Left Main Column: Span 4 Columns -->
        <div class="lg:col-span-4 space-y-4">

          <!-- div7: Body / Active Tab Content -->
          <div class="space-y-4">
            <!-- Tab 1: Section 2.4 - Attendance History & DTR View -->
            <div v-if="activeTab === 'dtr'">
              <AttendanceHistoryTable
                ref="attendanceHistoryTable"
                :employee-id="employeeId"
                @records-updated="rows => dtrRecords = rows"
              />
            </div>

            <!-- Tab: Leave Applications -->
            <div v-else-if="activeTab === 'leave'">
              <LeaveSection />
            </div>

            <!-- Tab: Work From Home Applications -->
            <div v-else-if="activeTab === 'wfh'">
              <WFHSection />
            </div>

            <!-- Tab 2: Section 2.5 - Overtime Requests -->
            <div v-else-if="activeTab === 'overtime'">
              <OvertimeSection 
                :overtime-list="overtimeList" 
                :ot-types-list="otTypesList" 
                :loading="otLoading" 
                @open-modal="showOtModal = true" 
              />
            </div>

            <!-- Tab 3: Section 2.6 - Pass Slip Request -->
            <div v-else-if="activeTab === 'pass-slip'">
              <PassSlipSection
                :pass-slips-used="todayStatus.pass_slips_used_this_month"
                :pass-slip-limit="todayStatus.pass_slip_monthly_limit"
              />
            </div>

            <!-- Tab 4: Section 2.7 - Travel Order Request -->
            <div v-else-if="activeTab === 'travel'">
              <TravelSection 
                :travel-list="travelList" 
                :loading="travelLoading" 
                @open-modal="showTravelModal = true" 
              />
            </div>

            <!-- Tab 5: Section 2.8 - Correction / Dispute Request -->
            <div v-else-if="activeTab === 'correction'">
              <CorrectionDisputeSection :employee-id="employeeId" />
            </div>

            <!-- Tab 6: Section 2.9 - Personal Locator Log -->
            <div v-else-if="activeTab === 'locator'" class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 space-y-4">
              <div>
                <h3 class="text-lg font-bold text-slate-900">Personal Locator & Status Trail</h3>
                <p class="text-xs text-slate-500">Log of your own status changes and device punch origins</p>
              </div>

              <div class="space-y-3 pt-2">
                <div
                  v-for="(log, idx) in locatorLogs"
                  :key="idx"
                  class="p-4 bg-slate-50 rounded-xl border border-slate-200 flex items-center justify-between"
                >
                  <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-slate-200 text-slate-700 flex items-center justify-center font-bold text-xs">
                      {{ idx + 1 }}
                    </div>
                    <div>
                      <span class="text-xs font-bold text-slate-900 block">{{ log.action }}</span>
                      <span class="text-[11px] text-slate-500">{{ log.time }}</span>
                    </div>
                  </div>

                  <div class="text-right">
                    <span class="text-xs font-semibold text-indigo-600 block">{{ log.source }}</span>
                    <span class="text-[10px] text-slate-400">{{ log.location }}</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Tab 7: Quick Module Navigation Links -->
            <div v-else-if="activeTab === 'modules'" class="grid grid-cols-1 md:grid-cols-3 gap-6">
              <button
                v-if="isDtrApprover"
                @click="navigateToModule('dtr-for-approval')"
                class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 text-left hover:shadow-md transition"
              >
                <h4 class="font-bold text-slate-900">DTR For Approval</h4>
                <p class="text-xs text-slate-500 mt-1">Review team DTR correction applications</p>
              </button>

              <button
                v-if="canSubmitAccomplishment"
                @click="navigateToModule('accomplishment-application')"
                class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 text-left hover:shadow-md transition"
              >
                <h4 class="font-bold text-slate-900">COS Accomplishments</h4>
                <p class="text-xs text-slate-500 mt-1">Submit COS accomplishment reports</p>
              </button>

              <button
                @click="navigateToModule('wfh-attendance')"
                class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 text-left hover:shadow-md transition"
              >
                <h4 class="font-bold text-slate-900">WFH Attendance</h4>
                <p class="text-xs text-slate-500 mt-1">Track work-from-home schedule & logs</p>
              </button>
            </div>
          </div>
        </div>

        <!-- Right Column: Span 2 Columns (div3: Calendar / Weekly Schedule) -->
        <div class="lg:col-span-2">
          <WeeklyScheduleCard
            :employee-id="employeeId"
            :schedule-name="todayStatus.schedule_name"
            :schedule-window="todayStatus.schedule_window"
            :setup-type="todayStatus.setup_type"
            :is-wfh-today="todayStatus.is_wfh_today"
            :weekly-schedule="todayStatus.weekly_schedule"
            :work-cancellations="todayStatus.work_cancellations"
            :dtr-records="dtrRecords"
          />
        </div>

      </div>

      <!-- Section 2.2: Clock Panel Modal -->
      <ClockPanelModal
        v-model:visible="showClockModal"
        :is-clocked-in="todayStatus.status === 'Clocked In'"
        :is-biometric-locked="todayStatus.has_biometric_today"
        :biometric-time="todayStatus.biometric_time"
        :require-selfie="todayStatus.require_selfie"
        :enforce-geofence="todayStatus.enforce_geofence"
        @punch-success="handlePunchSuccess"
      />

      <!-- Real-Time Lunch Break Milestone Modal -->
      <LunchBreakModal
        v-model:visible="showLunchModal"
        :alert-type="lunchAlertType || 'lunch_start'"
        :setup-type="todayStatus.setup_type || 'on_site'"
      />

      <!-- Overtime Request Modal -->
      <el-dialog 
        v-model="showOtModal" 
        width="540px" 
        class="!rounded-3xl overflow-hidden shadow-2xl"
        :show-close="true"
      >
        <template #header>
          <div class="flex items-center gap-3.5 pb-3 border-b border-slate-100">
            <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center text-white shadow-md shadow-amber-500/25">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div>
              <h3 class="text-lg font-bold text-slate-900 leading-tight">File Overtime Request</h3>
              <p class="text-xs font-medium text-slate-500">File overtime authorization & specify target hours</p>
            </div>
          </div>
        </template>

        <el-form label-position="top" class="pt-2 space-y-2">
          <el-form-item required>
            <template #label>
              <span class="text-xs font-bold text-slate-700">Overtime Date <span class="text-rose-500">*</span></span>
            </template>
            <el-date-picker v-model="otForm.date" type="date" value-format="YYYY-MM-DD" class="w-full !rounded-xl" />
          </el-form-item>

          <div class="grid grid-cols-2 gap-4">
            <el-form-item required>
              <template #label>
                <span class="text-xs font-bold text-slate-700">Time From <span class="text-rose-500">*</span></span>
              </template>
              <el-time-picker v-model="otForm.time_from" format="HH:mm" value-format="HH:mm" class="w-full !rounded-xl" />
            </el-form-item>

            <el-form-item required>
              <template #label>
                <span class="text-xs font-bold text-slate-700">Time To <span class="text-rose-500">*</span></span>
              </template>
              <el-time-picker v-model="otForm.time_to" format="HH:mm" value-format="HH:mm" class="w-full !rounded-xl" />
            </el-form-item>
          </div>

          <el-form-item required>
            <template #label>
              <span class="text-xs font-bold text-slate-700">Overtime Type <span class="text-rose-500">*</span></span>
            </template>
            <el-select v-model="otForm.type" class="w-full !rounded-xl">
              <el-option
                v-for="item in (otTypesList.length ? otTypesList : [{ id: '1', name: 'Regular OT' }, { id: '2', name: 'Special Holiday OT' }, { id: '3', name: 'Rest Day OT' }])"
                :key="item.id"
                :label="item.name"
                :value="String(item.id)"
              />
            </el-select>
          </el-form-item>

          <el-form-item required>
            <template #label>
              <span class="text-xs font-bold text-slate-700">Reason / Work Deliverables <span class="text-rose-500">*</span></span>
            </template>
            <el-input v-model="otForm.reason" type="textarea" :rows="3" placeholder="Tasks to accomplish during overtime..." class="!rounded-xl" />
          </el-form-item>
        </el-form>

        <template #footer>
          <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
            <el-button class="!rounded-xl !px-5 font-semibold" @click="showOtModal = false">Cancel</el-button>
            <el-button 
              type="primary" 
              class="!rounded-xl font-bold !px-6 !py-2.5 !bg-gradient-to-r !from-amber-600 !to-orange-600 hover:!from-amber-700 hover:!to-orange-700 !border-amber-600 shadow-md shadow-amber-500/25 hover:shadow-amber-500/40 hover:-translate-y-0.5 transition-all duration-200" 
              :loading="otSubmitting" 
              @click="submitOvertime"
            >
              Submit OT Request
            </el-button>
          </div>
        </template>
      </el-dialog>

      <!-- Travel Order Modal -->
      <el-dialog 
        v-model="showTravelModal" 
        width="560px" 
        class="!rounded-3xl overflow-hidden shadow-2xl"
        :show-close="true"
      >
        <template #header>
          <div class="flex items-center gap-3.5 pb-3 border-b border-slate-100">
            <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-indigo-600 to-blue-600 flex items-center justify-center text-white shadow-md shadow-indigo-600/25">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
              </svg>
            </div>
            <div>
              <h3 class="text-lg font-bold text-slate-900 leading-tight">File Travel Order / OB</h3>
              <p class="text-xs font-medium text-slate-500">File multi-day official travel itineraries & transport details</p>
            </div>
          </div>
        </template>

        <el-form label-position="top" class="pt-2 space-y-2">
          <div class="grid grid-cols-2 gap-4">
            <el-form-item required>
              <template #label>
                <span class="text-xs font-bold text-slate-700">Date From <span class="text-rose-500">*</span></span>
              </template>
              <el-date-picker v-model="travelForm.date_from" type="date" value-format="YYYY-MM-DD" class="w-full !rounded-xl" />
            </el-form-item>

            <el-form-item required>
              <template #label>
                <span class="text-xs font-bold text-slate-700">Date To <span class="text-rose-500">*</span></span>
              </template>
              <el-date-picker v-model="travelForm.date_to" type="date" value-format="YYYY-MM-DD" class="w-full !rounded-xl" />
            </el-form-item>
          </div>

          <el-form-item required>
            <template #label>
              <span class="text-xs font-bold text-slate-700">Destination <span class="text-rose-500">*</span></span>
            </template>
            <el-input v-model="travelForm.destination" placeholder="City / Office / Regional station..." class="!rounded-xl" />
          </el-form-item>

          <el-form-item required>
            <template #label>
              <span class="text-xs font-bold text-slate-700">Purpose / Official Mission <span class="text-rose-500">*</span></span>
            </template>
            <el-input v-model="travelForm.purpose" type="textarea" :rows="3" placeholder="Detailed mission purpose & objectives..." class="!rounded-xl" />
          </el-form-item>

          <el-form-item required>
            <template #label>
              <span class="text-xs font-bold text-slate-700">Mode of Transport <span class="text-rose-500">*</span></span>
            </template>
            <el-select v-model="travelForm.transport" class="w-full !rounded-xl">
              <el-option label="Official Vehicle" value="Official Vehicle" />
              <el-option label="Public Transportation / Commercial Flight" value="Public Transport" />
              <el-option label="Personal Vehicle" value="Personal Vehicle" />
            </el-select>
          </el-form-item>
        </el-form>

        <template #footer>
          <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
            <el-button class="!rounded-xl !px-5 font-semibold" @click="showTravelModal = false">Cancel</el-button>
            <el-button 
              type="primary" 
              class="!rounded-xl font-bold !px-6 !py-2.5 !bg-gradient-to-r !from-indigo-600 !to-blue-600 hover:!from-indigo-700 hover:!to-blue-700 !border-indigo-600 shadow-md shadow-indigo-500/25 hover:shadow-indigo-500/40 hover:-translate-y-0.5 transition-all duration-200" 
              :loading="travelSubmitting" 
              @click="submitTravel"
            >
              Submit Travel Order
            </el-button>
          </div>
        </template>
      </el-dialog>
    </div>
  </MainLayout>
</template>

<script>
import MainLayout from '../layout/MainLayout.vue'
import HeaderSummaryBar from '../components/time-attendance/HeaderSummaryBar.vue'
import TodayAttendanceCard from '../components/time-attendance/TodayAttendanceCard.vue'
import WeeklyScheduleCard from '../components/time-attendance/WeeklyScheduleCard.vue'
import AttendanceAlertsPanel from '../components/time-attendance/AttendanceAlertsPanel.vue'
import ClockPanelModal from '../components/time-attendance/ClockPanelModal.vue'
import LunchBreakModal from '../components/time-attendance/LunchBreakModal.vue'
import AttendanceHistoryTable from '../components/time-attendance/AttendanceHistoryTable.vue'
import PassSlipSection from '../components/time-attendance/PassSlipSection.vue'
import CorrectionDisputeSection from '../components/time-attendance/CorrectionDisputeSection.vue'
import LeaveSection from '../components/time-attendance/LeaveSection.vue'
import WFHSection from '../components/time-attendance/WFHSection.vue'
import OvertimeSection from '../components/time-attendance/OvertimeSection.vue'
import TravelSection from '../components/time-attendance/TravelSection.vue'
import { useToast } from 'vue-toastification'

export default {
  name: 'TimeAttendanceHub',
  components: {
    MainLayout,
    HeaderSummaryBar,
    TodayAttendanceCard,
    WeeklyScheduleCard,
    AttendanceAlertsPanel,
    ClockPanelModal,
    LunchBreakModal,
    AttendanceHistoryTable,
    PassSlipSection,
    CorrectionDisputeSection,
    LeaveSection,
    WFHSection,
    OvertimeSection,
    TravelSection
  },
  data() {
    return {
      toast: useToast(),
      breadcrumbs: [
        { name: 'Dashboard', path: '/dashboard' },
        { name: 'Time & Attendance', path: '/time-attendance' }
      ],
      activeTab: 'dtr',
      dtrRecords: [],
      showClockModal: false,
      showLunchModal: false,
      lunchAlertType: null,
      lunchInterval: null,
      showOtModal: false,
      otSubmitting: false,
      showTravelModal: false,
      travelSubmitting: false,
      isLoadingStatus: true,
      todayStatus: {
        server_time: null,
        today_date: null,
        status: 'Clocked Out',
        schedule_name: 'Loading Schedule...',
        schedule_window: '',
        am_in: null,
        am_out: null,
        pm_in: null,
        pm_out: null,
        work_hours: 0,
        is_late: false,
        is_undertime: false,
        is_missed_log: false,
        has_biometric_today: false,
        biometric_time: null,
        pass_slips_used_this_month: 0,
        enable_web_clock: false,
        enable_biometric: true,
        require_selfie: true,
        enforce_geofence: true,
        is_work_suspended: false,
        work_suspension_reason: null,
        work_suspension_with_pay: false,
        work_cancellations: [],
        weekly_schedule: []
      },
      mainTabs: [
        { id: 'dtr', name: 'Attendance History' },
        { id: 'leave', name: 'Leave Applications' },
        { id: 'wfh', name: 'Work From Home' },
        { id: 'overtime', name: 'Overtime Requests' },
        { id: 'travel', name: 'Travel Orders' },
        { id: 'correction', name: 'Corrections & Disputes' },
        { id: 'locator', name: 'Personal Locator Trail' },
      ],
      overtimeList: [],
      otTypesList: [],
      otLoading: false,
      otForm: {
        date: new Date().toISOString().slice(0, 10),
        time_from: '17:00',
        time_to: '19:00',
        type: '1',
        reason: ''
      },
      travelList: [],
      travelLoading: false,
      travelForm: {
        date_from: new Date().toISOString().slice(0, 10),
        date_to: new Date().toISOString().slice(0, 10),
        destination: '',
        purpose: '',
        transport: 'Official Vehicle'
      },
      locatorLogs: [],
      isDtrApprover: false,
      canSubmitAccomplishment: false
    }
  },
  async mounted() {
    await this.fetchTodayStatus()
    await this.loadOvertime()
    await this.loadTravel()
    this.buildLocatorLogs()
    this.checkLunchTimeMilestones()
    this.lunchInterval = setInterval(this.checkLunchTimeMilestones, 10000)
  },
  beforeUnmount() {
    if (this.lunchInterval) {
      clearInterval(this.lunchInterval)
    }
  },
  methods: {
    checkLunchTimeMilestones() {
      // Disable lunch break alerts and popup modal if employee is not clocked in today
      const isClockedInToday = this.todayStatus.status === 'Clocked In' || Boolean(this.todayStatus.am_in)
      if (!isClockedInToday) {
        this.lunchAlertType = null
        this.showLunchModal = false
        return
      }

      const now = new Date()
      const hours = now.getHours()
      const mins = now.getMinutes()
      const todayDate = now.toISOString().slice(0, 10)

      let currentMilestone = null

      // Milestone 1: 11:50 AM to 11:59 AM (10 mins before lunch)
      if (hours === 11 && mins >= 50 && mins <= 59) {
        currentMilestone = '10_before_lunch'
      }
      // Milestone 2: 12:00 PM to 12:49 PM (Lunch break active)
      else if (hours === 12 && mins >= 0 && mins <= 49) {
        currentMilestone = 'lunch_start'
      }
      // Milestone 3: 12:50 PM to 12:59 PM (10 mins before lunch ends)
      else if (hours === 12 && mins >= 50 && mins <= 59) {
        currentMilestone = '10_before_end'
      }
      // Milestone 4: 1:00 PM to 1:10 PM (Lunch break ended)
      else if (hours === 13 && mins >= 0 && mins <= 10) {
        currentMilestone = 'lunch_end'
      }

      this.lunchAlertType = currentMilestone

      if (currentMilestone) {
        const dismissKey = `lunch_popup_dismissed_${currentMilestone}_${todayDate}`
        if (!sessionStorage.getItem(dismissKey)) {
          this.showLunchModal = true
          sessionStorage.setItem(dismissKey, 'true')
        }
      }
    },
    async fetchTodayStatus() {
      this.isLoadingStatus = true
      try {
        const userData = JSON.parse(localStorage.getItem('user_data') || '{}')
        const userId = userData.id || userData.user_id || userData.employee_id || null
        if (!userId) {
          this.isLoadingStatus = false
          return
        }
        const { dtrApiService } = await import('../services/apiService.js')
        const res = await dtrApiService.getTodayStatus(userId)
        const d = res?.data || res
        if (d) {
          this.employeeId = d.employee_id || null
          const isClocked = d.status === 'Clocked In'
          localStorage.setItem('is_clocked_in', isClocked ? 'true' : 'false')
          this.todayStatus = {
            ...this.todayStatus,
            server_time: d.server_time || this.todayStatus.server_time,
            today_date: d.today_date || this.todayStatus.today_date,
            status: d.status || 'Clocked Out',
            schedule_name: d.schedule_name || this.todayStatus.schedule_name,
            schedule_window: d.schedule_window || this.todayStatus.schedule_window,
            scheduled_start_time: d.scheduled_start_time || '08:00 AM',
            is_late_for_clockin: !!d.is_late_for_clockin,
            clockin_lateness_minutes: d.clockin_lateness_minutes || 0,
            schedule_warning: d.schedule_warning || null,
            am_in: d.am_in || null,
            am_out: d.am_out || null,
            pm_in: d.pm_in || null,
            pm_out: d.pm_out || null,
            work_hours: d.work_hours || 0,
            is_late: !!d.is_late,
            is_undertime: !!d.is_undertime,
            is_missed_log: !!d.is_missed_log,
            has_biometric_today: !!d.has_biometric_today,
            biometric_time: d.biometric_time || null,
            pass_slips_used_this_month: d.pass_slips_used_this_month || 0,
            enable_web_clock: d.enable_web_clock !== undefined ? d.enable_web_clock : false,
            enable_biometric: d.enable_biometric !== undefined ? d.enable_biometric : true,
            require_selfie: d.require_selfie !== undefined ? d.require_selfie : true,
            enforce_geofence: d.enforce_geofence !== undefined ? d.enforce_geofence : true,
            setup_type: d.setup_type || 'on_site',
            logging_method: d.logging_method || 'Office Biometric Terminal',
            is_wfh_today: !!d.is_wfh_today,
            setup_message: d.setup_message || '',
            is_work_suspended: Boolean(d.is_work_suspended || d.status === 'Work Suspended'),
            work_suspension_reason: d.work_suspension_reason || null,
            work_suspension_with_pay: Boolean(d.work_suspension_with_pay),
            work_cancellations: Array.isArray(d.work_cancellations) ? d.work_cancellations : [],
            weekly_schedule: d.weekly_schedule || []
          }
          this.checkLunchTimeMilestones()
        }
      } catch (err) {
        console.error('Failed to fetch today status:', err)
      } finally {
        this.isLoadingStatus = false
      }
    },
    handlePunchSuccess(updatedData) {
      if (updatedData) {
        this.todayStatus = { ...this.todayStatus, ...updatedData }
      }
      this.fetchTodayStatus()
      this.buildLocatorLogs()
      window.dispatchEvent(new CustomEvent('dtr-updated'))
      if (this.$refs.attendanceHistoryTable) {
        this.$refs.attendanceHistoryTable.fetchPeriodDTR()
      }
    },
    handleAlertAction(alert) {
      if (alert.action === 'correction') this.activeTab = 'correction'
      else if (alert.action === 'clockout') this.showClockModal = true
      else if (alert.action === 'passslip') this.activeTab = 'pass-slip'
      else if (alert.action === 'lunch_popup') this.showLunchModal = true
    },
    statusBadgeClass(status) {
      switch ((status || '').toLowerCase()) {
        case 'approved': return 'bg-emerald-100 text-emerald-800'
        case 'disapproved': return 'bg-rose-100 text-rose-800'
        default: return 'bg-amber-100 text-amber-800'
      }
    },
    async loadOvertime() {
      this.otLoading = true
      try {
        const { useOvertime } = await import('../composables/useOvertime.js')
        const { state, loadOvertimeData } = useOvertime()
        await loadOvertimeData()

        if (state.overtimeTypes && state.overtimeTypes.length) {
          this.otTypesList = state.overtimeTypes
          if (!this.otForm.type || !this.otTypesList.some(t => String(t.id) === String(this.otForm.type))) {
            this.otForm.type = String(this.otTypesList[0].id)
          }
        }

        const formatTime = (val) => {
          if (!val) return ''
          if (val.includes('T')) return val.split('T')[1].slice(0, 5)
          if (val.includes(' ')) return val.split(' ')[1].slice(0, 5)
          return val.slice(0, 5)
        }

        const formatStatus = (row) => {
          if (row.approved || row.approved_1 || row.approved_2 || row.approved_3) return 'Approved'
          if (row.disapproved || row.disapproved_1 || row.disapproved_2 || row.disapproved_3) return 'Disapproved'
          if (row.is_cancel) return 'Cancelled'
          return 'Pending'
        }

        const rawList = [...(state.pendingOvertime || []), ...(state.approvedOvertime || []), ...(state.disapprovedOvertime || [])]
        this.overtimeList = rawList.map(item => ({
          ...item,
          date: item.date ? item.date.split('T')[0].split(' ')[0] : '',
          time_from: formatTime(item.date_time_from) || item.time_from || '',
          time_to: formatTime(item.date_time_to) || item.time_to || '',
          reason: item.remarks || item.reason || '',
          type_name: item.overtime_type_name || item.type_name || 'Regular OT',
          status: item.status || formatStatus(item),
          approver_name: item.approver_1 || item.approver_name || 'Supervisor / HR Review'
        }))
      } catch (e) {
        console.error('Failed to load overtime list:', e)
      } finally {
        this.otLoading = false
      }
    },
    async submitOvertime() {
      if (!this.otForm.date || !this.otForm.reason) {
        this.toast.error('Please fill in required fields.')
        return
      }
      this.otSubmitting = true
      try {
        const raw = localStorage.getItem('user_data')
        const userData = raw ? JSON.parse(raw) : null
        const userId = userData ? userData.id : null

        const { default: ApiService } = await import('../services/api.js')
        const otDataRes = await ApiService.getOvertimeData(userId)
        const empId = otDataRes?.data?.emp_id || (otDataRes?.data?.info?.[0]?.id) || userId
        const defaultOtTypeId = otDataRes?.data?.LeaveType?.[0]?.id || 1

        const formData = new FormData()
        formData.append('overtime_id', '0')
        formData.append('employee_id', empId)
        formData.append('overtime_type_id', this.otForm.type || defaultOtTypeId)
        formData.append('date', this.otForm.date)
        formData.append('date_time_from', this.otForm.time_from || '17:00')
        formData.append('date_time_to', this.otForm.time_to || '19:00')

        let totalHours = 2
        if (this.otForm.time_from && this.otForm.time_to) {
          const f = new Date(`2000-01-01T${this.otForm.time_from}`)
          const t = new Date(`2000-01-01T${this.otForm.time_to}`)
          if (t > f) {
            totalHours = Math.round(((t - f) / (1000 * 60 * 60)) * 4) / 4
          }
        }
        formData.append('total_hours', totalHours)
        formData.append('selectRadio', '1')
        formData.append('remarks', this.otForm.reason)

        const res = await ApiService.addOvertimeApplication(formData)
        if (res && (res.success || res.data || !res.error)) {
          this.toast.success('Overtime request filed successfully!')
          this.showOtModal = false
          this.otForm = {
            date: new Date().toISOString().slice(0, 10),
            time_from: '17:00',
            time_to: '19:00',
            type: '1',
            reason: ''
          }
          await this.loadOvertime()
        } else {
          this.toast.error(res?.error || res?.message || 'Failed to file Overtime request.')
        }
      } catch (e) {
        console.error('Error submitting overtime:', e)
        const msg = e?.message ? e.message.split(' - ').pop() : 'Failed to file Overtime request.'
        this.toast.error(msg)
      } finally {
        this.otSubmitting = false
      }
    },
    async loadTravel() {
      this.travelLoading = true
      try {
        const { useOfficialBusiness } = await import('../composables/useOfficialBusiness.js')
        const { state, loadOBData } = useOfficialBusiness()
        await loadOBData()

        const formatDate = (val) => {
          if (!val) return ''
          return val.split('T')[0].split(' ')[0]
        }

        const formatStatus = (row) => {
          if (row.approved || row.approved_1 || row.approved_2 || row.approved_3) return 'Approved'
          if (row.disapproved || row.disapproved_1 || row.disapproved_2 || row.disapproved_3) return 'Disapproved'
          if (row.is_cancel) return 'Cancelled'
          return 'Pending'
        }

        const rawList = [...(state.pendingOB || []), ...(state.approvedOB || []), ...(state.disapprovedOB || [])]
        this.travelList = rawList.map(item => ({
          ...item,
          date_from: formatDate(item.date_time_from) || formatDate(item.date) || item.date_from || '',
          date_to: formatDate(item.date_time_to) || formatDate(item.date) || item.date_to || '',
          destination: item.client || item.destination || 'N/A',
          purpose: item.purpose || '',
          travel_type: item.to_type_name || item.ta_type_name || (item.ob_type === 3 ? 'Travel Order' : (item.ob_type === 2 ? 'Travel Auth' : 'Pass Slip')),
          status: item.status || formatStatus(item)
        }))
      } catch (e) {
        console.error('Failed to load travel list:', e)
      } finally {
        this.travelLoading = false
      }
    },
    async submitTravel() {
      if (!this.travelForm.destination || !this.travelForm.purpose) {
        this.toast.error('Please fill in required travel details.')
        return
      }
      this.travelSubmitting = true
      try {
        const raw = localStorage.getItem('user_data')
        const userData = raw ? JSON.parse(raw) : null
        const userId = userData ? userData.id : null

        const startDate = this.travelForm.date_from || new Date().toISOString().slice(0, 10)
        const endDate = this.travelForm.date_to || startDate

        const { default: ApiService } = await import('../services/api.js')
        const obDataRes = await ApiService.getOfficialBusiness(userId)
        const empId = obDataRes?.data?.emp_id || (obDataRes?.data?.info?.[0]?.id) || userId

        const formData = new FormData()
        formData.append('official_business_id', '0')
        formData.append('employee_id', empId)
        formData.append('ob_type', '3') // Travel Order
        formData.append('date', startDate)
        formData.append('date_time_from', `${startDate}T08:00`)
        formData.append('date_time_to', `${endDate}T17:00`)
        formData.append('client', this.travelForm.destination)
        formData.append('purpose', this.travelForm.purpose)
        formData.append('recommending_position', '')
        formData.append('approver', '')

        const res = await ApiService.storeOfficialBusiness(formData)
        if (res && res.success !== false) {
          this.toast.success('Travel Order submitted successfully!')
          this.showTravelModal = false
          this.travelForm = {
            date_from: new Date().toISOString().slice(0, 10),
            date_to: new Date().toISOString().slice(0, 10),
            destination: '',
            purpose: '',
            transport: 'Official Vehicle'
          }
          await this.loadTravel()
        } else {
          this.toast.error(res?.error || res?.message || 'Failed to submit Travel Order.')
        }
      } catch (e) {
        console.error('Error submitting travel order:', e)
        const msg = e?.message ? e.message.split(' - ').pop() : 'Failed to submit Travel Order.'
        this.toast.error(msg)
      } finally {
        this.travelSubmitting = false
      }
    },
    buildLocatorLogs() {
      const logs = []
      if (this.todayStatus.am_in) {
        logs.push({ action: 'AM Clock In', time: this.todayStatus.am_in, source: 'Web Clock Terminal', location: 'GPS Verified' })
      }
      if (this.todayStatus.am_out) {
        logs.push({ action: 'AM Clock Out', time: this.todayStatus.am_out, source: 'Web Clock Terminal', location: 'GPS Verified' })
      }
      if (this.todayStatus.pm_in) {
        logs.push({ action: 'PM Clock In', time: this.todayStatus.pm_in, source: 'Web Clock Terminal', location: 'GPS Verified' })
      }
      if (this.todayStatus.pm_out) {
        logs.push({ action: 'PM Clock Out', time: this.todayStatus.pm_out, source: 'Web Clock Terminal', location: 'GPS Verified' })
      }
      if (logs.length === 0) {
        logs.push({ action: 'No Punches Logged Today', time: '--:--', source: 'System', location: 'N/A' })
      }
      this.locatorLogs = logs
    },
    navigateToModule(mod) {
      if (mod === 'dtr-for-approval') this.$router.push('/dtr/for-approval')
      else if (mod === 'accomplishment-application') this.$router.push('/accomplishment/application')
      else if (mod === 'wfh-attendance') this.$router.push('/wfh-attendance')
    }
  }
}
</script>
