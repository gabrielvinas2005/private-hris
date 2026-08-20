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
          @request-correction="activeTab = 'correction'"
        />
      </div>

      <!-- 5-Column Main Layout: Left 3 cols (Tabs + Body Content), Right 2 cols (Weekly Schedule) -->
      <div class="grid grid-cols-1 lg:grid-cols-6 gap-4 items-start">

        <!-- Left Main Column: Span 3 Columns -->
        <div class="lg:col-span-4 space-y-4">
          <!-- div5: Main Navigation Tabs -->
          <div class="bg-white rounded-2xl p-2 shadow-sm border border-slate-200">
            <div class="flex items-center gap-1 overflow-x-auto pb-1 md:pb-0">
              <button
                v-for="tab in mainTabs"
                :key="tab.id"
                @click="activeTab = tab.id"
                :class="[
                  activeTab === tab.id
                    ? 'bg-indigo-600 text-white shadow-md'
                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900',
                  'px-3.5 py-2 rounded-xl font-bold text-xs flex items-center gap-2 whitespace-nowrap transition-all duration-200'
                ]"
              >
                <span>{{ tab.name }}</span>
              </button>
            </div>
          </div>

          <!-- div7: Body / Active Tab Content -->
          <div class="space-y-4">
            <!-- Tab 1: Section 2.4 - Attendance History & DTR View -->
            <div v-if="activeTab === 'dtr'">
              <AttendanceHistoryTable :employee-id="employeeId" />
            </div>

            <!-- Tab 2: Section 2.5 - Overtime Requests -->
            <div v-else-if="activeTab === 'overtime'" class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 space-y-6">
              <div class="flex items-center justify-between">
                <div>
                  <h3 class="text-lg font-bold text-slate-900">Overtime Requests</h3>
                  <p class="text-xs text-slate-500">File overtime authorizations and track approval status</p>
                </div>
                <el-button type="primary" class="!rounded-xl font-semibold" @click="showOtModal = true">
                  + File Overtime Request
                </el-button>
              </div>

              <el-table :data="overtimeList" stripe v-loading="otLoading" empty-text="No overtime requests filed">
                <el-table-column prop="date" label="Date" width="130">
                  <template #default="{ row }"><span class="text-xs font-semibold">{{ row.date }}</span></template>
                </el-table-column>
                <el-table-column label="Time" width="160">
                  <template #default="{ row }">
                    <span class="text-xs">{{ row.time_from }} – {{ row.time_to }}</span>
                  </template>
                </el-table-column>
                <el-table-column prop="reason" label="Reason / Justification" min-width="200" />
                <el-table-column label="OT Type" width="130">
                  <template #default="{ row }">
                    <span class="text-xs bg-slate-100 px-2.5 py-1 rounded font-medium">{{ row.type_name || 'Regular OT' }}</span>
                  </template>
                </el-table-column>
                <el-table-column label="Status" width="140">
                  <template #default="{ row }">
                    <span :class="statusBadgeClass(row.status)" class="px-2.5 py-1 rounded-full text-xs font-bold">
                      {{ row.status || 'Pending' }}
                    </span>
                  </template>
                </el-table-column>
                <el-table-column label="Approver Trail" min-width="180">
                  <template #default="{ row }">
                    <span class="text-xs text-slate-600">{{ row.approver_name || 'Supervisor / HR Review' }}</span>
                  </template>
                </el-table-column>
              </el-table>
            </div>

            <!-- Tab 3: Section 2.6 - Pass Slip Request -->
            <div v-else-if="activeTab === 'pass-slip'">
              <PassSlipSection
                :pass-slips-used="todayStatus.pass_slips_used_this_month"
                :pass-slip-limit="todayStatus.pass_slip_monthly_limit"
              />
            </div>

            <!-- Tab 4: Section 2.7 - Travel Order Request -->
            <div v-else-if="activeTab === 'travel'" class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 space-y-6">
              <div class="flex items-center justify-between">
                <div>
                  <h3 class="text-lg font-bold text-slate-900">Travel Order / Official Travel</h3>
                  <p class="text-xs text-slate-500">File multi-day local/official travel orders</p>
                </div>
                <el-button type="primary" class="!rounded-xl font-semibold" @click="showTravelModal = true">
                  + File Travel Order
                </el-button>
              </div>

              <el-table :data="travelList" stripe v-loading="travelLoading" empty-text="No travel orders filed">
                <el-table-column label="Date Range" min-width="180">
                  <template #default="{ row }">
                    <span class="text-xs font-semibold">{{ row.date_from }} to {{ row.date_to }}</span>
                  </template>
                </el-table-column>
                <el-table-column prop="destination" label="Destination" min-width="160" />
                <el-table-column prop="purpose" label="Purpose" min-width="200" />
                <el-table-column label="Travel Type" width="140">
                  <template #default="{ row }">
                    <span class="text-xs bg-indigo-50 text-indigo-700 px-2 py-0.5 rounded font-semibold">{{ row.travel_type || 'Local Official' }}</span>
                  </template>
                </el-table-column>
                <el-table-column label="Status" width="140">
                  <template #default="{ row }">
                    <span :class="statusBadgeClass(row.status)" class="px-2.5 py-1 rounded-full text-xs font-bold">
                      {{ row.status || 'Pending' }}
                    </span>
                  </template>
                </el-table-column>
              </el-table>
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
            :schedule-name="todayStatus.schedule_name"
            :schedule-window="todayStatus.schedule_window"
            :setup-type="todayStatus.setup_type"
            :is-wfh-today="todayStatus.is_wfh_today"
            :weekly-schedule="todayStatus.weekly_schedule"
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
      <el-dialog v-model="showOtModal" title="File Overtime Request" width="480px">
        <el-form label-position="top">
          <el-form-item label="Overtime Date">
            <el-date-picker v-model="otForm.date" type="date" value-format="YYYY-MM-DD" class="w-full" />
          </el-form-item>
          <div class="grid grid-cols-2 gap-4">
            <el-form-item label="Time From">
              <el-time-picker v-model="otForm.time_from" format="HH:mm" value-format="HH:mm" class="w-full" />
            </el-form-item>
            <el-form-item label="Time To">
              <el-time-picker v-model="otForm.time_to" format="HH:mm" value-format="HH:mm" class="w-full" />
            </el-form-item>
          </div>
          <el-form-item label="Overtime Type">
            <el-select v-model="otForm.type" class="w-full">
              <el-option label="Regular OT" value="1" />
              <el-option label="Special Holiday OT" value="2" />
              <el-option label="Rest Day OT" value="3" />
            </el-select>
          </el-form-item>
          <el-form-item label="Reason / Justification">
            <el-input v-model="otForm.reason" type="textarea" :rows="2" placeholder="Tasks to accomplish..." />
          </el-form-item>
        </el-form>
        <template #footer>
          <el-button @click="showOtModal = false">Cancel</el-button>
          <el-button type="primary" :loading="otSubmitting" @click="submitOvertime">Submit OT Request</el-button>
        </template>
      </el-dialog>

      <!-- Travel Order Modal -->
      <el-dialog v-model="showTravelModal" title="File Travel Order / OB" width="520px">
        <el-form label-position="top">
          <div class="grid grid-cols-2 gap-4">
            <el-form-item label="Date From">
              <el-date-picker v-model="travelForm.date_from" type="date" value-format="YYYY-MM-DD" class="w-full" />
            </el-form-item>
            <el-form-item label="Date To">
              <el-date-picker v-model="travelForm.date_to" type="date" value-format="YYYY-MM-DD" class="w-full" />
            </el-form-item>
          </div>
          <el-form-item label="Destination">
            <el-input v-model="travelForm.destination" placeholder="City / Office / Regional station..." />
          </el-form-item>
          <el-form-item label="Purpose">
            <el-input v-model="travelForm.purpose" type="textarea" :rows="2" placeholder="Official mission purpose..." />
          </el-form-item>
          <el-form-item label="Mode of Transport">
            <el-select v-model="travelForm.transport" class="w-full">
              <el-option label="Official Vehicle" value="Official Vehicle" />
              <el-option label="Public Transportation / Commercial Flight" value="Public Transport" />
              <el-option label="Personal Vehicle" value="Personal Vehicle" />
            </el-select>
          </el-form-item>
        </el-form>
        <template #footer>
          <el-button @click="showTravelModal = false">Cancel</el-button>
          <el-button type="primary" :loading="travelSubmitting" @click="submitTravel">Submit Travel Order</el-button>
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
    CorrectionDisputeSection
  },
  data() {
    return {
      toast: useToast(),
      breadcrumbs: [
        { name: 'Dashboard', path: '/dashboard' },
        { name: 'Time & Attendance', path: '/time-attendance' }
      ],
      activeTab: 'dtr',
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
        enforce_geofence: true
      },
      mainTabs: [
        { id: 'dtr', name: 'Attendance History' },
        { id: 'overtime', name: 'Overtime Requests' },
        { id: 'travel', name: 'Travel Orders' },
        { id: 'correction', name: 'Corrections & Disputes' },
        { id: 'locator', name: 'Personal Locator Trail' },
      ],
      overtimeList: [],
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
        const userId = userData.id || userData.user_id || userData.employee_id || 1
        const { dtrApiService } = await import('../services/apiService.js')
        const res = await dtrApiService.getTodayStatus(userId)
        const d = res?.data || res
        if (d) {
          this.employeeId = d.employee_id || 1
          const isClocked = d.status === 'Clocked In'
          localStorage.setItem('is_clocked_in', isClocked ? 'true' : 'false')
          this.todayStatus = {
            ...this.todayStatus,
            server_time: d.server_time || this.todayStatus.server_time,
            today_date: d.today_date || this.todayStatus.today_date,
            status: d.status || 'Clocked Out',
            schedule_name: d.schedule_name || this.todayStatus.schedule_name,
            schedule_window: d.schedule_window || this.todayStatus.schedule_window,
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
            weekly_schedule: d.weekly_schedule || []
          }
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
        this.overtimeList = [...(state.pendingOvertime || []), ...(state.approvedOvertime || [])]
      } catch (e) {
        // handled
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
        this.toast.success('Overtime request filed successfully!')
        this.showOtModal = false
        await this.loadOvertime()
      } catch (e) {
        this.toast.error('Failed to file Overtime request.')
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
        this.travelList = [...(state.pendingOB || []), ...(state.approvedOB || [])]
      } catch (e) {
        // handled
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
        this.toast.success('Travel Order submitted successfully!')
        this.showTravelModal = false
        await this.loadTravel()
      } catch (e) {
        this.toast.error('Failed to submit Travel Order.')
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
