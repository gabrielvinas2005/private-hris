<template>
  <MainLayout>
    <div class="overview-container">
      
      <!-- GREETING BANNER -->
      <div class="greeting">
        <div class="greeting-left">
          <div class="greeting-eyebrow">{{ greetingTime }}</div>
          <div class="greeting-name display">{{ userData.name || 'Employee' }}</div>
          <div class="greeting-meta">
            {{ userRoleTitle }} · {{ userDepartment }} · Employee ID <span class="mono">{{ userEmployeeNo }}</span>
          </div>
        </div>
        <div class="greeting-stats">
          <div class="g-stat">
            <div class="v mono">{{ dashboardData.leave_balance?.total_balance ?? 14 }}</div>
            <div class="l">Days off left</div>
          </div>
          <div class="g-stat">
            <div class="v mono">{{ payslipSummary ? formatCurrency(payslipSummary.net_pay) : '₱42.6K' }}</div>
            <div class="l">Last payslip</div>
          </div>
          <div class="g-stat">
            <div class="v mono">96%</div>
            <div class="l">Attendance rate</div>
          </div>
        </div>
      </div>

      <!-- WARNING / COMPLIANCE BANNERS -->
      <!-- Missed Log Warning Banner -->
      <div v-if="hasMissedLog" class="banner-alert danger-banner">
        <div class="banner-left">
          <div class="banner-icon danger-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
          </div>
          <div>
            <span class="banner-tag danger-tag">Action Required — DTR Alert</span>
            <h3 class="banner-title display">Missed Log Warning Detected</h3>
            <p class="banner-desc">You have an unclosed attendance record from your previous shift (missing clock out). File a DTR Correction now to keep your daily time records complete.</p>
          </div>
        </div>
        <button @click="fileMissedLogCorrection" class="btn-alert danger-btn">
          <span>1-Click File Correction</span>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
        </button>
      </div>

      <!-- Work Suspension Banner -->
      <div v-if="isWorkSuspended" class="banner-alert info-banner">
        <div class="banner-left">
          <div class="banner-icon info-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m0 0v-5a2 2 0 012-2h2a2 2 0 012 2v5m-6 0h6"/></svg>
          </div>
          <div>
            <div class="banner-tag-row">
              <span class="banner-tag info-tag">Official Notice</span>
              <span v-if="workSuspensionWithPay" class="pill-pay">WITH PAY</span>
            </div>
            <h3 class="banner-title display">Work Suspended Today</h3>
            <p class="banner-desc">{{ workSuspensionReason || 'Work has been officially suspended today. Attendance logging is optional.' }}</p>
          </div>
        </div>
        <button @click="navigateToModule('time-attendance')" class="btn-alert info-btn">
          <span>View Attendance Details</span>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
        </button>
      </div>

      <!-- Schedule Warning Banner -->
      <div v-if="scheduleWarning || isLateForClockin" class="banner-alert warning-banner">
        <div class="banner-left">
          <div class="banner-icon warning-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
          </div>
          <div>
            <span class="banner-tag warning-tag">Attendance Alert — Schedule Compliance</span>
            <h3 class="banner-title display">Unlogged Shift / Schedule Alert</h3>
            <p class="banner-desc">{{ scheduleWarning || 'You have not logged in according to your assigned shift schedule today. Please record your clock-in immediately.' }}</p>
          </div>
        </div>
        <button @click="navigateToModule('time-attendance')" class="btn-alert warning-btn">
          <span>Clock In / View DTR</span>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
        </button>
      </div>

      <!-- Notifications Banner -->
      <div v-if="hasNotifications" class="banner-alert notice-banner">
        <div class="banner-left">
          <div class="banner-icon notice-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
          </div>
          <div>
            <span class="banner-tag notice-tag">Updates Pending</span>
            <h3 class="banner-title display">You have updates waiting</h3>
            <ul class="banner-list">
              <li v-if="dashboardData.pending_requests?.total">
                {{ dashboardData.pending_requests.total }} transaction(s) pending approval
              </li>
              <li v-if="contractExpiringSoon">
                Your contract/probation period ends {{ formatDate(contractExpiringSoon) }}
              </li>
              <li v-if="recentlyResolvedCount">
                {{ recentlyResolvedCount }} transaction(s) recently resolved
              </li>
            </ul>
          </div>
        </div>
      </div>

      <!-- SECTION LABEL -->
      <p class="section-label display">
        My Overview <span class="n mono">4</span>
      </p>

      <!-- KPI ROW (4 CARDS) -->
      <div class="kpi-row">
        <!-- KPI 1: Leave Balance -->
        <div class="kpi-card" @click="navigateToModule('leaves')">
          <div class="kpi-top">
            <span class="kpi-label">Leave Balance</span>
            <span class="kpi-icon" style="background:var(--accent-soft);">
              <svg viewBox="0 0 24 24" fill="none" stroke="var(--accent-ink)" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
            </span>
          </div>
          <div class="kpi-value mono">
            {{ dashboardData.leave_balance?.total_balance ?? 14 }}<span class="unit">days</span>
          </div>
          <div class="kpi-foot">
            <b>{{ leaveVacationBalance }}</b> vacation · <b>{{ leaveSickBalance }}</b> sick
          </div>
        </div>

        <!-- KPI 2: Next Payslip -->
        <div class="kpi-card" @click="navigateToModule('payslip')">
          <div class="kpi-top">
            <span class="kpi-label">Next Payslip</span>
            <span class="kpi-icon" style="background:var(--success-soft);">
              <svg viewBox="0 0 24 24" fill="none" stroke="var(--success)" stroke-width="2"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            </span>
          </div>
          <div class="kpi-value mono">
            {{ payslipSummary?.period_label || 'Aug 30' }}
          </div>
          <div class="kpi-foot">
            Est. <b>{{ payslipSummary ? formatCurrency(payslipSummary.net_pay) : '₱43.1K' }}</b> net pay
          </div>
        </div>

        <!-- KPI 3: Attendance (MTD) -->
        <div class="kpi-card" @click="navigateToModule('time-attendance')">
          <div class="kpi-top">
            <span class="kpi-label">Attendance (MTD)</span>
            <span class="kpi-icon" style="background:var(--violet-soft);">
              <svg viewBox="0 0 24 24" fill="none" stroke="var(--violet)" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>
            </span>
          </div>
          <div class="kpi-value mono">
            96<span class="unit">%</span>
          </div>
          <div class="kpi-foot"><b>1</b> late arrival this month</div>
        </div>

        <!-- KPI 4: My Requests -->
        <div class="kpi-card" @click="navigateToModule('leaves')">
          <div class="kpi-top">
            <span class="kpi-label">My Requests</span>
            <span class="kpi-icon" style="background:var(--warning-soft);">
              <svg viewBox="0 0 24 24" fill="none" stroke="var(--warning)" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
            </span>
          </div>
          <div class="kpi-value mono">
            {{ dashboardData.pending_requests?.total ?? 2 }}<span class="unit">pending</span>
          </div>
          <div class="kpi-foot">
            <b>{{ dashboardData.pending_requests?.leaves ?? 1 }}</b> leave · <b>{{ dashboardData.pending_requests?.overtime ?? 1 }}</b> overtime
          </div>
        </div>
      </div>

      <!-- MAIN GRID LAYOUT -->
      <div class="grid-layout">
        
        <!-- LEFT COLUMN -->
        <div>
          <!-- CHARTS ROW -->
          <div class="charts-row2">
            <!-- Leave Balance by Type Card -->
            <div class="card">
              <div class="card-head">
                <div class="card-title display">
                  Leave Balance by Type
                  <span class="sub">Entitlement used this year</span>
                </div>
              </div>
              <div class="lv-row">
                <div class="lbl">Vacation</div>
                <div class="track"><div class="fill" style="width:66%;background:var(--accent);"></div></div>
                <div class="val mono">10 / 15</div>
              </div>
              <div class="lv-row">
                <div class="lbl">Sick</div>
                <div class="track"><div class="fill" style="width:40%;background:var(--success);"></div></div>
                <div class="val mono">4 / 10</div>
              </div>
              <div class="lv-row">
                <div class="lbl">Emergency</div>
                <div class="track"><div class="fill" style="width:0%;background:var(--warning);"></div></div>
                <div class="val mono">3 / 3</div>
              </div>
              <div class="lv-row">
                <div class="lbl">Bereavement</div>
                <div class="track"><div class="fill" style="width:0%;background:var(--violet);"></div></div>
                <div class="val mono">5 / 5</div>
              </div>
            </div>

            <!-- Attendance Trend Card -->
            <div class="card">
              <div class="card-head">
                <div class="card-title display">
                  Attendance Trend
                  <span class="sub">Last 6 months</span>
                </div>
              </div>
              <div class="chart-wrap">
                <canvas id="chartAttendanceCanvas"></canvas>
              </div>
            </div>
          </div>

          <!-- REQUEST TRACKER -->
          <p class="section-label display">
            My Requests <span class="n mono">{{ dashboardData.pending_requests?.total ?? 2 }} pending</span>
          </p>

          <div class="card" style="margin-bottom:24px;">
            <div class="card-head">
              <div class="card-title display">Request Tracker</div>
              <button @click="navigateToModule('leaves')" class="link-btn">
                File new request 
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
              </button>
            </div>

            <div class="track-row">
              <div class="track-icon" style="background:var(--accent-soft);">
                <svg viewBox="0 0 24 24" fill="none" stroke="var(--accent-ink)" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
              </div>
              <div class="track-body">
                <div class="track-title">Vacation Leave</div>
                <div class="track-meta">Aug 24–26 · 3 days · Submitted Aug 18</div>
              </div>
              <span class="status-chip pending">Pending</span>
            </div>

            <div class="track-row">
              <div class="track-icon" style="background:var(--warning-soft);">
                <svg viewBox="0 0 24 24" fill="none" stroke="var(--warning)" stroke-width="2"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
              </div>
              <div class="track-body">
                <div class="track-title">Travel Reimbursement / Official Business</div>
                <div class="track-meta">Client visit · ₱1,850 · Submitted Aug 15</div>
              </div>
              <span class="status-chip pending">Pending</span>
            </div>

            <div class="track-row">
              <div class="track-icon" style="background:var(--success-soft);">
                <svg viewBox="0 0 24 24" fill="none" stroke="var(--success)" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
              </div>
              <div class="track-body">
                <div class="track-title">Sick Leave</div>
                <div class="track-meta">Aug 20 · 1 day · Submitted Aug 20</div>
              </div>
              <span class="status-chip approved">Approved</span>
            </div>

            <div class="track-row">
              <div class="track-icon" style="background:var(--danger-soft);">
                <svg viewBox="0 0 24 24" fill="none" stroke="var(--danger)" stroke-width="2"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
              </div>
              <div class="track-body">
                <div class="track-title">Equipment Reimbursement</div>
                <div class="track-meta">Home office chair · ₱6,200 · Submitted Aug 5</div>
              </div>
              <span class="status-chip rejected">Rejected</span>
            </div>
          </div>
        </div>

        <!-- RIGHT RAIL -->
        <div>
          <!-- QUICK ACTIONS CARD -->
          <div class="card" style="margin-bottom:16px;">
            <div class="card-head">
              <div class="card-title display">Quick Actions</div>
            </div>
            <div class="qa-grid">
              <div class="qa" @click="navigateToModule('leaves')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                <span>File Leave</span>
              </div>
              <div class="qa" @click="navigateToModule('overtime-scheduling')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
                <span>Request OT</span>
              </div>
              <div class="qa" @click="navigateToModule('payslip')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                <span>View Payslip</span>
              </div>
              <div class="qa" @click="navigateTo201File()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 21v-1a8 8 0 0 1 16 0v1"/></svg>
                <span>Edit Profile</span>
              </div>
            </div>
          </div>

          <!-- COMPANY FEED CARD -->
          <div class="card" style="margin-bottom:16px;">
            <div class="card-head">
              <div class="card-title display">Company Feed</div>
              <button 
                v-if="hasHrmAccess || userData?.is_admin || hasAnyAdminAccess()" 
                @click="openAnnouncementModal"
                class="create-ann-btn"
              >
                + Create
              </button>
            </div>
            
            <AnnouncementList v-if="announcements.length" :announcements="announcements" />
            <div v-else class="feed-list">
              <div class="feed-item">
                <span class="feed-tag">Announcement</span>
                <div class="feed-title">Updated hybrid work policy takes effect Sept 1</div>
                <div class="feed-meta">Posted by People Ops · 2 days ago</div>
              </div>
              <div class="feed-item">
                <span class="feed-tag">Recognition</span>
                <div class="feed-title">You were kudos'd by Nathan Ong</div>
                <div class="feed-meta">"Great work on the design system!" · Yesterday</div>
              </div>
              <div class="feed-item">
                <span class="feed-tag">Reminder</span>
                <div class="feed-title">Benefits open enrollment closes Aug 31</div>
                <div class="feed-meta">People Ops · 3 days ago</div>
              </div>
            </div>
          </div>

          <!-- UPCOMING CARD -->
          <div class="card">
            <div class="card-head">
              <div class="card-title display">Upcoming</div>
            </div>
            <div class="upcoming-row">
              <div class="date-box">
                <div class="d mono">28</div>
                <div class="m">Aug</div>
              </div>
              <div>
                <div class="upcoming-title">Ninoy Aquino Day</div>
                <div class="upcoming-meta">Regular holiday · Office closed</div>
              </div>
            </div>
            <div class="upcoming-row">
              <div class="date-box">
                <div class="d mono">30</div>
                <div class="m">Aug</div>
              </div>
              <div>
                <div class="upcoming-title">Payday</div>
                <div class="upcoming-meta">Payslip available same day</div>
              </div>
            </div>
            <div class="upcoming-row">
              <div class="date-box">
                <div class="d mono">01</div>
                <div class="m">Sep</div>
              </div>
              <div>
                <div class="upcoming-title">Hybrid policy effective</div>
                <div class="upcoming-meta">3 days in-office minimum</div>
              </div>
            </div>
          </div>
        </div>

      </div>

      <!-- ADMINISTRATIVE ACCESS SECTION -->
      <div v-if="hasAnyAdminAccess()" class="admin-section">
        <h2 class="section-label display">Administrative Access</h2>
        <div class="admin-grid">
          <!-- 201 Files Card -->
          <div v-if="hasHrmAccess" @click="navigateToHRModule()" class="admin-card purple-admin">
            <div class="admin-icon bg-purple">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </div>
            <div>
              <h3 class="admin-title display">201 Files</h3>
              <p class="admin-sub">HR Module - 201 Files</p>
            </div>
          </div>

          <!-- Control Panel Card -->
          <div v-if="hasCpmAccess" @click="navigateToControlPanel()" class="admin-card red-admin">
            <div class="admin-icon bg-red">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </div>
            <div>
              <h3 class="admin-title display">Control Panel</h3>
              <p class="admin-sub">System Administration</p>
            </div>
          </div>

          <!-- Payroll Module Card -->
          <div v-if="hasHrpAccess" @click="navigateToPayrollModule()" class="admin-card yellow-admin">
            <div class="admin-icon bg-yellow">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/></svg>
            </div>
            <div>
              <h3 class="admin-title display">Payroll Module</h3>
              <p class="admin-sub">Payroll Management</p>
            </div>
          </div>

          <!-- Timekeeping Module Card -->
          <div v-if="hasHrtAccess" @click="navigateToTimekeeping()" class="admin-card green-admin">
            <div class="admin-icon bg-green">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
              <h3 class="admin-title display">Timekeeping</h3>
              <p class="admin-sub">Time & Attendance</p>
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- CREATE ANNOUNCEMENT DIALOG -->
    <el-dialog
      v-model="showAnnouncementModal"
      title="Create Announcement"
      width="580px"
      :close-on-click-modal="false"
    >
      <el-form label-position="top">
        <el-form-item label="Target Audience">
          <el-radio-group v-model="announcementForm.isGlobal">
            <el-radio :label="true">Global (All Employees)</el-radio>
            <el-radio :label="false">Specific Employee</el-radio>
          </el-radio-group>
        </el-form-item>

        <el-form-item v-if="!announcementForm.isGlobal" label="Select Employee">
          <el-select
            v-model="announcementForm.employee_id"
            placeholder="Search employee..."
            filterable
            class="w-full"
          >
            <el-option
              v-for="emp in announcementEmployees"
              :key="emp.id"
              :label="emp.name"
              :value="emp.id"
            />
          </el-select>
        </el-form-item>

        <el-form-item label="Title">
          <el-input v-model="announcementForm.title" placeholder="Announcement title..." />
        </el-form-item>

        <el-form-item label="Content">
          <el-input
            v-model="announcementForm.content"
            type="textarea"
            :rows="5"
            placeholder="Write announcement body..."
          />
        </el-form-item>
      </el-form>

      <template #footer>
        <div class="flex justify-end gap-2">
          <el-button @click="showAnnouncementModal = false">Cancel</el-button>
          <el-button
            type="primary"
            :loading="submittingAnnouncement"
            @click="submitDashboardAnnouncement"
            style="background-color: var(--accent); border-color: var(--accent);"
          >
            Publish Announcement
          </el-button>
        </div>
      </template>
    </el-dialog>
  </MainLayout>
</template>

<script>
import Chart from 'chart.js/auto'
import MainLayout from '../layout/MainLayout.vue'
import AnnouncementList from '../components/Announcement/AnnouncementList.vue'
import { fetchCompanyPublic } from '../services/companyPublic.js'

export default {
  name: 'Dashboard',
  components: { MainLayout, AnnouncementList },
  data() {
    return {
      company: {
        name: '',
        address: '',
        email: '',
        telephone_no: '',
        mobile_no: '',
        logo_data_url: null
      },
      userData: {
        id: null,
        name: '',
        email: '',
        position: '',
        department: '',
        employee_no: ''
      },
      menuAccessLoaded: false,
      canViewEmployeeRecord: false,
      canViewLeave: false,
      canViewOvertime: false,
      canViewPayslip: false,
      canViewDtr: false,
      canViewTravelOrder: false,
      canViewWfh: false,
      canViewDocumentRequest: false,
      currentDate: '',
      currentTime: '',
      dashboardData: {
        leave_balance: { total_balance: 0, leave_types: [] },
        pending_requests: { total: 0, leaves: 0, overtime: 0, official_business: 0, document_requests: 0 },
        work_hours: { hours_today: 0, status: 'Hours today' },
        overtime_hours: 0,
        recent_activity: [],
        payslip_summary: null,
        contract_expiry: null,
        recently_resolved_count: 0
      },
      announcements: [],
      loading: false,
      hasHrmAccess: false,
      hasHrpAccess: false,
      hasHrtAccess: false,
      hasCpmAccess: false,
      hasMissedLog: false,
      isLateForClockin: false,
      scheduleWarning: null,
      isWorkSuspended: false,
      workSuspensionReason: null,
      workSuspensionWithPay: false,
      showAnnouncementModal: false,
      announcementForm: {
        title: '',
        content: '',
        isGlobal: true,
        employee_id: null
      },
      announcementEmployees: [],
      submittingAnnouncement: false,
      attendanceChart: null
    }
  },
  computed: {
    greetingTime() {
      const hour = new Date().getHours()
      if (hour < 12) return 'Good morning'
      if (hour < 18) return 'Good afternoon'
      return 'Good evening'
    },
    userRoleTitle() {
      return (
        this.userData.position ||
        this.userData.designation ||
        this.dashboardData?.employee_info?.position ||
        (this.userData.role && this.userData.role !== 'Employee' ? this.userData.role : '') ||
        'Employee'
      )
    },
    userDepartment() {
      return (
        this.userData.department ||
        this.dashboardData?.employee_info?.department ||
        'General Department'
      )
    },
    userEmployeeNo() {
      return this.userData.employee_no || (this.userData.id ? `EMP-${String(this.userData.id).padStart(4, '0')}` : 'EMP-02481')
    },
    leaveVacationBalance() {
      const types = this.dashboardData.leave_balance?.leave_types || []
      const found = types.find(t => String(t.name).toLowerCase().includes('vacation'))
      return found ? found.balance : 10
    },
    leaveSickBalance() {
      const types = this.dashboardData.leave_balance?.leave_types || []
      const found = types.find(t => String(t.name).toLowerCase().includes('sick'))
      return found ? found.balance : 4
    },
    companyPortalSubtitle() {
      return this.company.name
        ? `${this.company.name} — Employee Portal`
        : 'Employee Portal'
    },
    hasAnyQuickAction() {
      return (
        this.canViewLeave ||
        this.canViewOvertime ||
        this.canViewTravelOrder ||
        this.canViewWfh ||
        this.canViewDocumentRequest ||
        this.canViewEmployeeRecord
      )
    },
    payslipSummary() {
      return this.dashboardData?.payslip_summary || null
    },
    contractExpiringSoon() {
      const expiry = this.dashboardData?.contract_expiry
      if (!expiry) return null
      const days = (new Date(expiry) - new Date()) / (1000 * 60 * 60 * 24)
      return days >= 0 && days <= 30 ? expiry : null
    },
    recentlyResolvedCount() {
      return this.dashboardData?.recently_resolved_count || 0
    },
    hasNotifications() {
      return Boolean(
        (this.dashboardData.pending_requests?.total || 0) > 0 ||
        this.contractExpiringSoon ||
        this.recentlyResolvedCount > 0
      )
    }
  },
  mounted() {
    fetchCompanyPublic().then((data) => {
      this.company = data
    })

    const storedUserData = localStorage.getItem('user_data')
    if (storedUserData) {
      try {
        this.userData = JSON.parse(storedUserData)
      } catch (e) {}
      const roleStr = String(this.userData?.role || this.userData?.user_role || '').toLowerCase()
      const isAdmin = this.toBool(this.userData?.is_admin) || 
        this.userData?.user_type_id === 1 || 
        ['admin', 'hr', 'administrator', 'hr_admin'].includes(roleStr)

      this.hasHrmAccess = this.toBool(this.userData?.with_hrm_access) || isAdmin
      this.hasHrpAccess = this.toBool(this.userData?.with_hrp_access) || isAdmin
      this.hasHrtAccess = this.toBool(this.userData?.with_hrt_access) || isAdmin
      this.hasCpmAccess = this.toBool(this.userData?.with_cpm_access) || isAdmin
      this.loadMenuAccess()
      this.loadDashboardData()
      this.loadAnnouncements()
    }

    this.updateDateTime()
    setInterval(this.updateDateTime, 1000)

    this._notificationPoll = setInterval(() => {
      if (this.userData?.id) this.loadDashboardData()
    }, 60000)

    this.$nextTick(() => {
      this.initAttendanceChart()
    })
  },
  beforeUnmount() {
    if (this._notificationPoll) clearInterval(this._notificationPoll)
    if (this.attendanceChart) this.attendanceChart.destroy()
  },
  methods: {
    initAttendanceChart() {
      const canvas = document.getElementById('chartAttendanceCanvas')
      if (!canvas) return
      if (this.attendanceChart) this.attendanceChart.destroy()
      Chart.defaults.font.family = "'Plus Jakarta Sans', sans-serif"
      this.attendanceChart = new Chart(canvas, {
        type: 'line',
        data: {
          labels: ['Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug'],
          datasets: [{
            data: [98, 95, 97, 94, 96, 96],
            borderColor: '#3457D5',
            backgroundColor: 'rgba(52, 87, 213, 0.08)',
            fill: true,
            tension: 0.35,
            pointRadius: 0,
            borderWidth: 2.4
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { display: false } },
          scales: {
            x: { grid: { display: false }, border: { display: false } },
            y: { grid: { color: '#EEF0F5' }, border: { display: false }, ticks: { callback: v => v + '%' }, min: 85, max: 100 }
          }
        }
      })
    },

    hasAnyAdminAccess() {
      return this.hasHrmAccess || this.hasHrpAccess || this.hasHrtAccess || this.hasCpmAccess
    },

    toBool(val) {
      if (typeof val === 'string') {
        const v = val.trim().toLowerCase()
        return v === '1' || v === 'true' || v === 'yes' || v === 'y'
      }
      return !!val
    },

    formatCurrency(value) {
      const num = Number(value || 0)
      return num.toLocaleString('en-PH', { style: 'currency', currency: 'PHP' })
    },

    applyMenuPermissions(menus) {
      if (!Array.isArray(menus)) return
      const normalize = (s) =>
        String(s ?? '')
          .trim()
          .toLowerCase()
          .replace(/\s+/g, ' ')
      const allowed = new Set()
      for (const m of menus) {
        if (m?.menu_key) allowed.add(normalize(m.menu_key))
        if (m?.menu) allowed.add(normalize(m.menu))
      }
      const has = (label) => allowed.has(normalize(label))

      this.canViewEmployeeRecord =
        has('My Profile & Records') ||
        has('employee_record') ||
        has('Employee Records')
      this.canViewLeave =
        has('Leave & Time Management') ||
        has('leave_management') ||
        has('leave-time')
      this.canViewOvertime =
        has('Overtime Management') ||
        has('overtime_management') ||
        has('overtime-monitoring')
      this.canViewPayslip = has('Payslip') || has('payslip')
      this.canViewDtr =
        has('Daily Time Record') || has('dtr') || has('daily time record')
      this.canViewTravelOrder =
        has('Travel Order') || has('travel_order') || has('official business') || has('ob')
      this.canViewWfh =
        has('WFH Application') || has('wfh') || has('wfh_application') || has('work from home')
      this.canViewDocumentRequest =
        has('Document Requests') || has('document_request') || has('document-requests')
    },

    async loadMenuAccess() {
      try {
        const cachedAccess = localStorage.getItem('user_tab_access')
        if (cachedAccess) {
          const parsed = JSON.parse(cachedAccess)
          const menus = Array.isArray(parsed?.menus) ? parsed.menus : []
          if (menus.length > 0) {
            this.applyMenuPermissions(menus)
            this.menuAccessLoaded = true
          }
        }
      } catch (e) {}

      try {
        const ApiService = (await import('../services/api.js')).default
        await ApiService.initSanctum()
        const res = await ApiService.getUserTabAccess()
        const menus = res?.data?.menus
        if (Array.isArray(menus)) {
          localStorage.setItem('user_tab_access', JSON.stringify(res.data))
          this.applyMenuPermissions(menus)
        }
      } catch (e) {
        if (!this.menuAccessLoaded) {
          this.canViewEmployeeRecord = false
          this.canViewLeave = false
          this.canViewOvertime = false
          this.canViewPayslip = false
          this.canViewDtr = false
          this.canViewTravelOrder = false
          this.canViewWfh = false
          this.canViewDocumentRequest = false
        }
      } finally {
        this.menuAccessLoaded = true
      }
    },

    navigateToModule(module) {
      this.$router.push(`/${module}`)
    },

    getUserId() {
      if (!this.userData || !this.userData.id) {
        console.error('User data not available or missing ID')
        return null
      }
      return this.userData.id
    },

    navigateTo201File() {
      const userId = this.getUserId()
      if (userId) {
        this.$router.push(`/201-file/${userId}`)
      } else {
        console.error('User ID not available')
      }
    },

    async loadDashboardData() {
      const userId = this.getUserId()
      if (!userId) return

      this.loading = true
      try {
        const ApiService = (await import('../services/api.js')).default
        await ApiService.initSanctum()
        const response = await ApiService.getDashboardData(userId)

        if (response && response.success) {
          this.dashboardData = { ...this.dashboardData, ...response.data }
          if (response.data?.employee_info) {
            if (response.data.employee_info.position && !this.userData.position) {
              this.userData.position = response.data.employee_info.position
            }
            if (response.data.employee_info.department && !this.userData.department) {
              this.userData.department = response.data.employee_info.department
            }
          }
        }

        try {
          const { dtrApiService } = await import('../services/apiService.js')
          const dtrRes = await dtrApiService.getTodayStatus(userId)
          const statusData = dtrRes?.data || dtrRes
          if (statusData) {
            this.hasMissedLog = Boolean(statusData.is_missed_log || statusData.status === 'Missed Log')
            this.isLateForClockin = Boolean(statusData.is_late_for_clockin)
            this.scheduleWarning = statusData.schedule_warning || null
            this.isWorkSuspended = Boolean(statusData.is_work_suspended || statusData.status === 'Work Suspended')
            this.workSuspensionReason = statusData.work_suspension_reason || null
            this.workSuspensionWithPay = Boolean(statusData.work_suspension_with_pay)
          }
        } catch (_) {}
      } catch (error) {
        console.error('Dashboard data loading failed:', error)
      } finally {
        this.loading = false
      }
    },

    fileMissedLogCorrection() {
      this.$router.push('/time-attendance')
    },

    updateDateTime() {
      const now = new Date()
      this.currentDate = now.toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      })
      this.currentTime = now.toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit'
      })
    },

    formatDate(value) {
      if (!value) return ''
      try {
        const d = new Date(value)
        return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
      } catch {
        return String(value)
      }
    },

    async loadAnnouncements() {
      try {
        const ApiService = (await import('../services/api.js')).default
        const response = await ApiService.getAnnouncements()
        if (response && response.success) {
          this.announcements = response.data || []
        }
      } catch (error) {
        console.error('Error loading announcements:', error)
      }
    },

    openAnnouncementModal() {
      this.announcementForm = { title: '', content: '', isGlobal: true, employee_id: null }
      this.showAnnouncementModal = true
      this.loadAnnouncementEmployees()
    },

    async loadAnnouncementEmployees() {
      try {
        const ApiService = (await import('../services/api.js')).default
        const res = await ApiService.getAnnouncementEmployees()
        if (res && res.data) {
          this.announcementEmployees = res.data
        }
      } catch (e) {}
    },

    async submitDashboardAnnouncement() {
      if (!this.announcementForm.title || !this.announcementForm.content) {
        this.$message ? this.$message.warning('Please enter title and content') : alert('Please enter title and content')
        return
      }
      this.submittingAnnouncement = true
      try {
        const ApiService = (await import('../services/api.js')).default
        const payload = {
          title: this.announcementForm.title,
          content: this.announcementForm.content,
          employee_id: this.announcementForm.isGlobal ? null : this.announcementForm.employee_id
        }
        const res = await ApiService.createAnnouncement(payload)
        if (res?.success || res?.id || res?.data) {
          if (this.$message) this.$message.success('Announcement published successfully!')
          this.showAnnouncementModal = false
          await this.loadAnnouncements()
        }
      } catch (e) {
        if (this.$message) this.$message.error(e?.message || 'Failed to publish announcement')
      } finally {
        this.submittingAnnouncement = false
      }
    },

    navigateToControlPanel() {
      let user = this.userData || JSON.parse(localStorage.getItem('user_data') || '{}')
      if (user && user.email) {
        const authToken = localStorage.getItem('auth_token')
        const employeeNo = user.employee_no || user.id || 'admin'
        const controlPanelUrl = import.meta.env.VITE_CONTROL_PANEL_URL || 'http://192.168.0.126:8081'
        window.open(`${controlPanelUrl}/?employee_no=${employeeNo}&email=${user.email}&auth_token=${encodeURIComponent(authToken || '')}&redirect_from=e_portal`, '_blank')
      }
    },

    navigateToPayrollModule() {
      let user = this.userData || JSON.parse(localStorage.getItem('user_data') || '{}')
      if (user && user.email) {
        const authToken = localStorage.getItem('auth_token')
        const employeeNo = user.employee_no || user.id || 'admin'
        const payrollModuleUrl = import.meta.env.VITE_PAYROLL_MODULE_URL || 'http://192.168.0.126:8083'
        window.open(`${payrollModuleUrl}/?employee_no=${employeeNo}&email=${user.email}&auth_token=${encodeURIComponent(authToken || '')}&redirect_from=e_portal`, '_blank')
      }
    },

    navigateToTimekeeping() {
      let user = this.userData || JSON.parse(localStorage.getItem('user_data') || '{}')
      if (user && user.email) {
        const authToken = localStorage.getItem('auth_token')
        const employeeNo = user.employee_no || user.id || 'admin'
        const timekeepingUrl = import.meta.env.VITE_TIMEKEEPING_MODULE_URL || 'http://192.168.0.126:8085'
        window.open(`${timekeepingUrl}/?employee_no=${employeeNo}&email=${user.email}&auth_token=${encodeURIComponent(authToken || '')}&redirect_from=e_portal`, '_blank')
      }
    },

    navigateToHRModule() {
      let user = this.userData || JSON.parse(localStorage.getItem('user_data') || '{}')
      if (user && user.email) {
        const authToken = localStorage.getItem('auth_token')
        const employeeNo = user.employee_no || user.id || 'admin'
        const hrModuleUrl = import.meta.env.VITE_HR_MODULE_URL || 'http://192.168.0.126:8082'
        window.open(`${hrModuleUrl}/?employee_no=${employeeNo}&email=${user.email}&auth_token=${encodeURIComponent(authToken || '')}&redirect_from=e_portal`, '_blank')
      }
    }
  }
}
</script>

<style scoped>
/* CSS VARIABLES FOR WORKSPACE DESIGN SYSTEM */
.overview-container {
  --bg: #F3F5F8;
  --surface: #FFFFFF;
  --ink: #12172B;
  --ink-muted: #68708A;
  --ink-faint: #9CA3B8;
  --border: #E5E8F0;
  --navy: #0E1526;
  --navy-light: #1A2340;
  --navy-lighter: #2A3560;
  --accent: #3457D5;
  --accent-ink: #2540A8;
  --accent-soft: #E9EDFC;
  --success: #16A34A;
  --success-soft: #E3F8EA;
  --warning: #D97706;
  --warning-soft: #FEF3DD;
  --danger: #DC2626;
  --danger-soft: #FCE8E7;
  --violet: #7C4FE0;
  --violet-soft: #F0E9FC;
  --radius: 14px;
  --shadow: 0 1px 2px rgba(18,23,43,0.04), 0 8px 24px -12px rgba(18,23,43,0.10);

  font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  color: var(--ink);
  padding: 4px 4px 40px;
}

/* TYPOGRAPHY */
.mono {
  font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  font-feature-settings: "tnum";
  font-variant-numeric: tabular-nums;
  font-weight: 600;
}
.display {
  font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

/* GREETING BANNER */
.greeting {
  background: linear-gradient(120deg, var(--navy) 0%, #1D2A52 100%);
  border-radius: 16px;
  padding: 24px 28px;
  color: #fff;
  margin-bottom: 22px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
  position: relative;
  overflow: hidden;
}
.greeting::after {
  content: '';
  position: absolute;
  right: -40px;
  top: -60px;
  width: 220px;
  height: 220px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(108,134,238,.35), transparent 70%);
  pointer-events: none;
}
.greeting-left {
  position: relative;
  z-index: 1;
}
.greeting-eyebrow {
  font-size: 12px;
  color: #9AA4CE;
  font-weight: 600;
  letter-spacing: .03em;
  margin-bottom: 6px;
}
.greeting-name {
  font-size: 24px;
  font-weight: 700;
  line-height: 1.2;
}
.greeting-meta {
  font-size: 13px;
  color: #B7BEDB;
  margin-top: 6px;
}
.greeting-stats {
  display: flex;
  gap: 28px;
  position: relative;
  z-index: 1;
}
.g-stat .v {
  font-size: 22px;
  font-weight: 700;
}
.g-stat .l {
  font-size: 11px;
  color: #9AA4CE;
  margin-top: 2px;
}

/* WARNING & COMPLIANCE BANNERS */
.banner-alert {
  border-radius: var(--radius);
  padding: 16px 20px;
  margin-bottom: 20px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  box-shadow: var(--shadow);
}
.banner-left {
  display: flex;
  align-items: flex-start;
  gap: 14px;
}
.banner-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.banner-icon svg {
  width: 20px;
  height: 20px;
}
.banner-tag {
  font-size: 10.5px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .05em;
  margin-bottom: 2px;
  display: inline-block;
}
.banner-tag-row {
  display: flex;
  align-items: center;
  gap: 8px;
}
.pill-pay {
  font-size: 9.5px;
  font-weight: 800;
  background: var(--success-soft);
  color: var(--success);
  padding: 1px 7px;
  border-radius: 20px;
}
.banner-title {
  font-size: 15px;
  font-weight: 700;
  color: var(--ink);
  margin: 0;
}
.banner-desc {
  font-size: 12.5px;
  color: var(--ink-muted);
  margin-top: 2px;
  max-width: 620px;
}
.banner-list {
  font-size: 12.5px;
  color: var(--ink-muted);
  margin-top: 4px;
  padding-left: 16px;
}
.btn-alert {
  font-family: inherit;
  font-size: 12px;
  font-weight: 700;
  padding: 9px 16px;
  border-radius: 9px;
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 6px;
  flex-shrink: 0;
  transition: all .15s ease;
}
.btn-alert svg {
  width: 14px;
  height: 14px;
}

/* Banner themes */
.danger-banner { background: var(--danger-soft); border: 1px solid rgba(220,38,38,0.2); }
.danger-icon { background: rgba(220,38,38,0.15); color: var(--danger); }
.danger-tag { color: var(--danger); }
.danger-btn { background: var(--danger); color: #fff; }
.danger-btn:hover { background: #B91C1C; }

.info-banner { background: var(--accent-soft); border: 1px solid rgba(52,87,213,0.2); }
.info-icon { background: rgba(52,87,213,0.15); color: var(--accent-ink); }
.info-tag { color: var(--accent-ink); }
.info-btn { background: var(--accent); color: #fff; }
.info-btn:hover { background: var(--accent-ink); }

.warning-banner { background: var(--warning-soft); border: 1px solid rgba(217,119,6,0.2); }
.warning-icon { background: rgba(217,119,6,0.15); font-size: 18px; }
.warning-tag { color: var(--warning); }
.warning-btn { background: var(--warning); color: #fff; }
.warning-btn:hover { background: #B45309; }

.notice-banner { background: #FFFBEB; border: 1px solid rgba(217,119,6,0.2); }
.notice-icon { background: rgba(217,119,6,0.15); color: var(--warning); }
.notice-tag { color: var(--warning); }

/* SECTION LABEL */
.section-label {
  font-size: 14px;
  font-weight: 700;
  color: var(--ink);
  display: flex;
  align-items: center;
  gap: 8px;
  margin: 0 0 14px;
}
.section-label .n {
  font-size: 11px;
  color: var(--ink-faint);
  border: 1px solid var(--border);
  padding: 1px 7px;
  border-radius: 20px;
  font-weight: 600;
}

/* KPI ROW */
.kpi-row {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  margin-bottom: 24px;
}
.kpi-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 18px 20px 16px;
  box-shadow: var(--shadow);
  cursor: pointer;
  transition: transform .15s ease, box-shadow .15s ease;
}
.kpi-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(18,23,43,0.08);
}
.kpi-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 10px;
}
.kpi-label {
  font-size: 12px;
  color: var(--ink-muted);
  font-weight: 500;
}
.kpi-icon {
  width: 28px;
  height: 28px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.kpi-icon svg {
  width: 15px;
  height: 15px;
}
.kpi-value {
  font-size: 24px;
  font-weight: 700;
  letter-spacing: -.01em;
  display: flex;
  align-items: baseline;
  gap: 6px;
  color: var(--ink);
}
.kpi-value .unit {
  font-size: 12.5px;
  color: var(--ink-faint);
  font-weight: 500;
}
.kpi-foot {
  margin-top: 8px;
  font-size: 11.5px;
  color: var(--ink-muted);
}
.kpi-foot b {
  color: var(--ink);
  font-weight: 600;
}

/* CARDS & GRID */
.grid-layout {
  display: grid;
  grid-template-columns: 1fr 320px;
  gap: 22px;
  align-items: start;
}
.card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  padding: 20px 22px;
}
.card-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 16px;
}
.card-title {
  font-size: 15px;
  font-weight: 700;
  color: var(--ink);
}
.card-title .sub {
  display: block;
  font-family: inherit;
  font-size: 11.5px;
  font-weight: 400;
  color: var(--ink-muted);
  margin-top: 2px;
}

.charts-row2 {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
  margin-bottom: 24px;
}
.chart-wrap {
  height: 170px;
  position: relative;
}

/* LEAVE BALANCE BARS */
.lv-row {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 9px 0;
}
.lv-row .lbl {
  width: 95px;
  font-size: 12px;
  color: var(--ink-muted);
  flex: none;
}
.lv-row .track {
  flex: 1;
  height: 8px;
  background: var(--bg);
  border-radius: 20px;
  overflow: hidden;
}
.lv-row .fill {
  height: 100%;
  border-radius: 20px;
}
.lv-row .val {
  width: 64px;
  text-align: right;
  font-size: 11.5px;
  font-weight: 600;
  flex: none;
  color: var(--ink);
}

/* REQUEST TRACKER */
.link-btn {
  font-size: 12px;
  font-weight: 700;
  color: var(--accent-ink);
  display: flex;
  align-items: center;
  gap: 4px;
  background: none;
  border: none;
  cursor: pointer;
}
.link-btn svg {
  width: 13px;
  height: 13px;
}
.track-row {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 4px;
  border-bottom: 1px solid var(--border);
}
.track-row:last-child {
  border-bottom: none;
}
.track-icon {
  width: 34px;
  height: 34px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex: none;
}
.track-icon svg {
  width: 16px;
  height: 16px;
}
.track-body {
  flex: 1;
  min-width: 0;
}
.track-title {
  font-size: 13px;
  font-weight: 600;
  color: var(--ink);
}
.track-meta {
  font-size: 11.5px;
  color: var(--ink-muted);
  margin-top: 2px;
}
.status-chip {
  font-size: 10.5px;
  font-weight: 700;
  padding: 3px 10px;
  border-radius: 20px;
  flex: none;
}
.status-chip.pending { background: var(--warning-soft); color: var(--warning); }
.status-chip.approved { background: var(--success-soft); color: var(--success); }
.status-chip.rejected { background: var(--danger-soft); color: var(--danger); }

/* QUICK ACTIONS */
.qa-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
}
.qa {
  border: 1px solid var(--border);
  border-radius: 11px;
  padding: 12px;
  display: flex;
  flex-direction: column;
  gap: 8px;
  background: var(--bg);
  cursor: pointer;
  transition: all .15s ease;
}
.qa:hover {
  background: var(--accent-soft);
  border-color: rgba(52,87,213,0.3);
  transform: translateY(-1px);
}
.qa svg {
  width: 18px;
  height: 18px;
  color: var(--accent);
}
.qa span {
  font-size: 12px;
  font-weight: 600;
  line-height: 1.25;
  color: var(--ink);
}

/* FEED / ANNOUNCEMENTS */
.create-ann-btn {
  font-size: 11px;
  font-weight: 700;
  background: var(--accent-soft);
  color: var(--accent-ink);
  border: none;
  padding: 3px 10px;
  border-radius: 6px;
  cursor: pointer;
}
.create-ann-btn:hover {
  background: var(--accent);
  color: #fff;
}
.feed-item {
  padding: 11px 2px;
  border-bottom: 1px solid var(--border);
}
.feed-item:last-child {
  border-bottom: none;
}
.feed-tag {
  font-size: 9.5px;
  font-weight: 800;
  letter-spacing: .03em;
  text-transform: uppercase;
  color: var(--accent-ink);
  margin-bottom: 3px;
  display: inline-block;
}
.feed-title {
  font-size: 12.5px;
  font-weight: 600;
  line-height: 1.35;
  color: var(--ink);
}
.feed-meta {
  font-size: 11px;
  color: var(--ink-faint);
  margin-top: 3px;
}

/* UPCOMING EVENTS */
.upcoming-row {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 2px;
  border-bottom: 1px solid var(--border);
}
.upcoming-row:last-child {
  border-bottom: none;
}
.date-box {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: var(--accent-soft);
  color: var(--accent-ink);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  flex: none;
}
.date-box .d {
  font-size: 14px;
  font-weight: 700;
  line-height: 1;
}
.date-box .m {
  font-size: 8.5px;
  font-weight: 700;
  text-transform: uppercase;
}
.upcoming-title {
  font-size: 12.5px;
  font-weight: 600;
  color: var(--ink);
}
.upcoming-meta {
  font-size: 11px;
  color: var(--ink-faint);
}

/* ADMIN SECTION */
.admin-section {
  margin-top: 32px;
}
.admin-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
}
.admin-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 16px 18px;
  box-shadow: var(--shadow);
  display: flex;
  align-items: center;
  gap: 14px;
  cursor: pointer;
  transition: transform .15s ease, border-color .15s ease;
}
.admin-card:hover {
  transform: translateY(-2px);
}
.purple-admin:hover { border-color: rgba(124,79,224,0.4); }
.red-admin:hover { border-color: rgba(220,38,38,0.4); }
.yellow-admin:hover { border-color: rgba(217,119,6,0.4); }
.green-admin:hover { border-color: rgba(22,163,74,0.4); }

.admin-icon {
  width: 42px;
  height: 42px;
  border-radius: 11px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.admin-icon svg {
  width: 20px;
  height: 20px;
}
.bg-purple { background: var(--violet-soft); color: var(--violet); }
.bg-red { background: var(--danger-soft); color: var(--danger); }
.bg-yellow { background: var(--warning-soft); color: var(--warning); }
.bg-green { background: var(--success-soft); color: var(--success); }

.admin-title {
  font-size: 14px;
  font-weight: 700;
  color: var(--ink);
}
.admin-sub {
  font-size: 11.5px;
  color: var(--ink-muted);
  margin-top: 1px;
}

/* RESPONSIVE DESIGN */
@media (max-width: 1200px) {
  .grid-layout { grid-template-columns: 1fr; }
  .kpi-row { grid-template-columns: repeat(2, 1fr); }
  .admin-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 768px) {
  .kpi-row { grid-template-columns: 1fr; }
  .charts-row2 { grid-template-columns: 1fr; }
  .admin-grid { grid-template-columns: 1fr; }
  .greeting { flex-direction: column; align-items: flex-start; gap: 16px; }
  .greeting-stats { flex-wrap: wrap; gap: 18px; }
}
</style>