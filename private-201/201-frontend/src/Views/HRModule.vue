<template>
  <div class="hr-dashboard-container">
    
    

    <!-- Main Content Body -->
    <div class="hr-content">

      <!-- PAGE 1: OVERVIEW                                            -->
      <section v-if="activeTab === 'overview'" class="page active">
        <p class="section-label">Key Workforce Metrics <span class="n">4</span></p>

        <div class="kpi-row">
          <div class="kpi-card">
            <div class="kpi-top">
              <span class="kpi-label">Total Headcount</span>
              <span class="kpi-icon" style="background:var(--accent-soft);">
                <svg viewBox="0 0 24 24" fill="none" stroke="var(--accent-ink)" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
              </span>
            </div>
            <div class="kpi-value">{{ kpis.total_headcount }}<span class="unit">active</span></div>
            <div class="kpi-trend up">
              <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M18 15l-6-6-6 6"/></svg>
              +{{ kpis.last_month_hires }} <span class="ctx">vs last month</span>
            </div>
          </div>

          <div class="kpi-card">
            <div class="kpi-top">
              <span class="kpi-label">Turnover Rate (YTD)</span>
              <span class="kpi-icon" style="background:var(--danger-soft);">
                <svg viewBox="0 0 24 24" fill="none" stroke="var(--danger)" stroke-width="2"><path d="M16 17l5-5-5-5M21 12H9M13 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h8"/></svg>
              </span>
            </div>
            <div class="kpi-value">{{ kpis.turnover_rate }}<span class="unit">%</span></div>
            <div class="kpi-trend down">
              <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M6 9l6 6 6-6"/></svg>
              −1.1pt <span class="ctx">avg tenure 3.4 yrs</span>
            </div>
          </div>

          <div class="kpi-card">
            <div class="kpi-top">
              <span class="kpi-label">Absenteeism (Today)</span>
              <span class="kpi-icon" style="background:var(--warning-soft);">
                <svg viewBox="0 0 24 24" fill="none" stroke="var(--warning)" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
              </span>
            </div>
            <div class="kpi-value">{{ attendanceToday.unplanned_absence }}<span class="unit">absent</span></div>
            <div class="kpi-trend up">
              <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M18 15l-6-6-6 6"/></svg>
              {{ pendingRequests.length }} requests pending
            </div>
          </div>

          <div class="kpi-card">
            <div class="kpi-top">
              <span class="kpi-label">Payroll Spend (MTD)</span>
              <span class="kpi-icon" style="background:var(--success-soft);">
                <svg viewBox="0 0 24 24" fill="none" stroke="var(--success)" stroke-width="2"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
              </span>
            </div>
            <div class="kpi-value">₱{{ kpis.payroll_spend }}<span class="unit">M</span></div>
            <div class="kpi-trend up">
              <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M6 9l6 6 6-6"/></svg>
              97% <span class="ctx">of monthly budget</span>
            </div>
          </div>
        </div>

        <div class="grid-layout">
          <!-- LEFT COLUMN -->
          <div>
            <div class="charts-row">
              <div class="card">
                <div class="card-head">
                  <div class="card-title">Headcount by Department<span class="sub">{{ kpis.total_headcount }} active employees</span></div>
                  <span class="pill blue">Active only</span>
                </div>
                <div class="chart-wrap"><canvas id="chartHeadcount"></canvas></div>
              </div>

              <div class="card">
                <div class="card-head">
                  <div class="card-title">Attendance Today<span class="sub">All locations</span></div>
                </div>
                <div class="chart-wrap"><canvas id="chartAttendance"></canvas></div>
                <div class="legend-row">
                  <div class="legend-item"><span class="legend-dot" style="background:var(--success);"></span>Present · {{ attendanceToday.present }}</div>
                  <div class="legend-item"><span class="legend-dot" style="background:var(--accent);"></span>On leave · {{ attendanceToday.on_leave }}</div>
                  <div class="legend-item"><span class="legend-dot" style="background:var(--danger);"></span>Absent · {{ attendanceToday.unplanned_absence }}</div>
                </div>
              </div>
            </div>

            <div class="charts-row2">
              <div class="card">
                <div class="card-head">
                  <div class="card-title">Turnover &amp; Retention<span class="sub">Voluntary vs. involuntary, trailing 6 months</span></div>
                </div>
                <div class="chart-wrap" style="height:160px;"><canvas id="chartTurnover"></canvas></div>
                <div class="stat-inline">
                  <div><div class="v">3.4 yrs</div><div class="l">Avg. tenure</div></div>
                  <div><div class="v">92%</div><div class="l">12-mo retention</div></div>
                </div>
              </div>

              <div class="card">
                <div class="card-head">
                  <div class="card-title">Labor Cost — Budget vs. Actual<span class="sub">Current fiscal period</span></div>
                </div>
                <div class="chart-wrap" style="height:160px;"><canvas id="chartBudget"></canvas></div>
                <div class="stat-inline">
                  <div><div class="v">₱6.4M</div><div class="l">Budgeted</div></div>
                  <div><div class="v" style="color:var(--warning);">₱{{ kpis.payroll_spend }}M</div><div class="l">Actual spend</div></div>
                </div>
              </div>
            </div>

            <p class="section-label">Action Items &amp; Approvals <span class="n">{{ pendingRequests.length }} pending</span></p>
            <div class="charts-row2" style="align-items:start;">
              <div class="card action-block">
                <div class="card-head"><div class="card-title">Pending Requests</div><span class="pill blue">{{ pendingRequests.length }} pending</span></div>

                <div v-for="req in filteredRequests" :key="req.id" class="req-row">
                  <div class="req-avatar">{{ req.initials }}</div>
                  <div class="req-body">
                    <div class="req-name">{{ req.name }}</div>
                    <div class="req-meta">
                      <span class="req-type" :class="req.typeClass">{{ req.typeLabel }}</span>
                      {{ req.detail }}
                    </div>
                  </div>
                  <div class="req-actions">
                    <button class="btn-round approve" @click="approveRequest(req)" title="Approve">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M20 6L9 17l-5-5"/></svg>
                    </button>
                    <button class="btn-round reject" @click="rejectRequest(req)" title="Reject">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M18 6L6 18M6 6l12 12"/></svg>
                    </button>
                  </div>
                </div>
                <div v-if="filteredRequests.length === 0" class="empty-state">
                  No pending requests matching filter.
                </div>
              </div>

              <div>
                <div class="card action-block">
                  <div class="card-head"><div class="card-title">Compliance Alerts</div><span class="pill" style="background:var(--danger-soft);color:var(--danger);">Urgent</span></div>
                  <div class="alert-row">
                    <span class="alert-dot high"></span>
                    <div><div class="alert-title">{{ complianceAlerts.expiring_contracts }} COS contracts expiring soon</div><div class="alert-meta">Review offboarding or renewal</div></div>
                  </div>
                  <div class="alert-row">
                    <span class="alert-dot med"></span>
                    <div><div class="alert-title">{{ complianceAlerts.pending_step_increments }} step increments pending review</div><div class="alert-meta">HR approval queue</div></div>
                  </div>
                </div>

                <div class="card action-block">
                  <div class="card-head"><div class="card-title">Onboarding in Progress</div><span class="pill blue">{{ onboardingPipeline.length }} active</span></div>
                  <div v-for="item in onboardingPipeline" :key="item.name" class="onboard-row">
                    <div class="onboard-top"><span class="nm">{{ item.name }}</span><span class="pct">{{ item.pct }}%</span></div>
                    <div class="bar-track"><div class="bar-fill" :style="{ width: item.pct + '%' }"></div></div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- RIGHT RAIL -->
          <div>
            <div class="card rail-card">
              <div class="card-head"><div class="card-title">201 Module Shortcuts</div></div>
              <div class="shortcut-grid">
                <div
                  v-for="mod in availableModuleShortcuts"
                  :key="mod.path"
                  class="shortcut"
                  @click="$router.push(mod.path)"
                >
                  <component :is="mod.icon" class="w-4 h-4 text-[#3457D5]" />
                  <span>{{ mod.title }}</span>
                </div>
              </div>
            </div>

            <div class="card rail-card">
              <div class="card-head flex items-center justify-between">
                <div class="card-title">Company Feed</div>
                <button 
                  @click="openCreateAnnouncementModal"
                  class="text-xs bg-[#3457D5] hover:bg-[#2540A8] text-white px-2.5 py-1 rounded-md transition-colors flex items-center gap-1 font-medium cursor-pointer"
                >
                  <span>+ Post</span>
                </button>
              </div>
              <div v-if="announcements.length > 0">
                <div v-for="ann in announcements.slice(0, 5)" :key="ann.id" class="feed-item">
                  <span class="feed-tag">{{ ann.employee_id ? 'Personal' : 'Announcement' }}</span>
                  <div class="feed-title font-semibold text-slate-800 text-xs mt-1">{{ ann.Title || ann.title }}</div>
                  <div class="feed-meta text-[11px] text-slate-500 mt-0.5 line-clamp-2">{{ ann.content || ann.Event }}</div>
                </div>
              </div>
              <div v-else>
                <div class="feed-item">
                  <span class="feed-tag">Announcement</span>
                  <div class="feed-title">Updated hybrid work policy takes effect Sept 1</div>
                  <div class="feed-meta">Posted by People Ops · 2 days ago</div>
                </div>
                <div class="feed-item">
                  <span class="feed-tag">Recognition</span>
                  <div class="feed-title">🏅 Team Engineering hit Q3 delivery milestone</div>
                  <div class="feed-meta">18 kudos · Yesterday</div>
                </div>
              </div>
            </div>

            <div class="card rail-card">
              <div class="card-head"><div class="card-title">Directory Search</div></div>
              <div class="dir-input">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                <input type="text" v-model="dirSearch" placeholder="Name, ext., or email">
              </div>
              <div v-for="person in filteredDirectory" :key="person.name" class="dir-result">
                <div class="req-avatar">{{ person.initials }}</div>
                <div>
                  <div class="nm">{{ person.name }}</div>
                  <div class="ext">ext. {{ person.ext }} · {{ person.email }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

    <!-- Create Announcement Modal Dialog -->
    <el-dialog
      v-model="announcementModalVisible"
      title="Publish New Announcement"
      width="540px"
      destroy-on-close
    >
      <el-form label-position="top">
        <el-form-item label="Announcement Audience">
          <el-radio-group v-model="announcementForm.isGlobal">
            <el-radio :label="true">Global (All Employees)</el-radio>
            <el-radio :label="false">Specific Employee</el-radio>
          </el-radio-group>
        </el-form-item>

        <el-form-item v-if="!announcementForm.isGlobal" label="Select Target Employee" required>
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

        <el-form-item label="Title" required>
          <el-input v-model="announcementForm.title" placeholder="e.g. Q3 Town Hall Briefing & HR Updates" />
        </el-form-item>

        <el-form-item label="Content" required>
          <el-input
            v-model="announcementForm.content"
            type="textarea"
            :rows="5"
            placeholder="Write announcement details here..."
          />
        </el-form-item>
      </el-form>

      <template #footer>
        <div class="flex justify-end gap-2">
          <el-button @click="announcementModalVisible = false">Cancel</el-button>
          <el-button
            type="primary"
            :loading="submittingAnnouncement"
            @click="submitAnnouncement"
            style="background-color: #3457D5; border-color: #3457D5;"
          >
            Publish Announcement
          </el-button>
        </div>
      </template>
    </el-dialog>

    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import { User, Timer, Setting, Money, Document } from '@element-plus/icons-vue'
import { authApi, accessRightsApi, hrOverviewApi, employeeApi, announcementApi } from '@/services/api'
import { ElMessage } from 'element-plus'

const globalSearch = ref('')
const moduleSearch = ref('')
const dirSearch = ref('')
const activeTab = ref('overview')
const activeEmpFilter = ref('all')

// Announcements State
const announcements = ref([])
const announcementModalVisible = ref(false)
const submittingAnnouncement = ref(false)
const announcementEmployees = ref([])
const announcementForm = ref({ title: '', content: '', isGlobal: true, employee_id: null })

const openCreateAnnouncementModal = async () => {
  announcementModalVisible.value = true
  announcementForm.value = { title: '', content: '', isGlobal: true, employee_id: null }
  try {
    const empRes = await announcementApi.getAnnouncementEmployees()
    const list = empRes?.data?.data || empRes?.data || []
    if (Array.isArray(list)) {
      announcementEmployees.value = list
    }
  } catch (e) {
    console.warn('Employees fetch for announcement error:', e)
  }
}

const submitAnnouncement = async () => {
  if (!announcementForm.value.title || !announcementForm.value.content) {
    ElMessage.warning('Please enter announcement title and content')
    return
  }
  submittingAnnouncement.value = true
  try {
    const payload = {
      title: announcementForm.value.title,
      content: announcementForm.value.content,
      employee_id: announcementForm.value.isGlobal ? null : announcementForm.value.employee_id
    }
    const res = await announcementApi.createAnnouncement(payload)
    if (res?.data?.success || res?.status === 200 || res?.data?.id) {
      ElMessage.success('Announcement published successfully')
      announcementModalVisible.value = false
      fetchAnnouncements()
    } else {
      ElMessage.error(res?.data?.message || 'Failed to create announcement')
    }
  } catch (e) {
    ElMessage.error(e?.response?.data?.message || e?.message || 'Failed to create announcement')
  } finally {
    submittingAnnouncement.value = false
  }
}

const fetchAnnouncements = async () => {
  try {
    const annRes = await announcementApi.getAnnouncements()
    const list = annRes?.data?.data || annRes?.data || []
    if (Array.isArray(list)) {
      announcements.value = list
    }
  } catch (e) {
    console.warn('Announcements fetch error:', e)
  }
}

// Real Backend Dashboard State
const kpis = ref({
  total_headcount: 486,
  last_month_hires: 12,
  turnover_rate: 8.4,
  payroll_spend: 6.2
})

const attendanceToday = ref({
  present: 432,
  total: 486,
  on_leave: 39,
  unplanned_absence: 15
})

const complianceAlerts = ref({
  expiring_contracts: 3,
  pending_step_increments: 5
})

const onboardingPipeline = ref([
  { name: 'Samantha Mendoza', pct: 85 },
  { name: 'Carlos Guttierez', pct: 60 },
  { name: 'Rhea Villareal', pct: 40 },
  { name: 'Dave Navarro', pct: 25 },
])

const deptChartLabels = ref(['Operations', 'Engineering', 'Sales', 'Finance', 'Design', 'People Ops'])
const deptChartData = ref([142, 118, 84, 52, 45, 45])

const pagesMeta = {
  overview: { title: 'Overview', sub: 'Company-wide workforce snapshot — as of today' },
  employees: { title: 'Employees', sub: 'Single source of truth for all HR modules' },
  attendance: { title: 'Attendance & Leave', sub: 'Daily attendance, absence, and time-off requests' },
  payroll: { title: 'Payroll & Labor Cost', sub: 'Compensation spend vs. budget, current period' },
  recruitment: { title: 'Recruitment', sub: 'Applicant pipeline across open requisitions' },
  modules: { title: '201 HR Modules', sub: 'Access-gated 201 File management tools' }
}

const currentPageMeta = computed(() => pagesMeta[activeTab.value] || pagesMeta.overview)

const navTabs = [
  { id: 'overview', label: 'Overview', icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg>` },
  { id: 'employees', label: 'Employees', icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>` },
  { id: 'attendance', label: 'Attendance & Leave', icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>` },
  { id: 'payroll', label: 'Payroll', icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>` },
  { id: 'recruitment', label: 'Recruitment', icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4"><path d="M20 6L9 17l-5-5"/></svg>` },
  { id: 'modules', label: '201 Modules', icon: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4"><path d="M4 6h16M4 12h16M4 18h16"/></svg>` }
]

// 201 Modules list
const allModules = [
  { title: 'Employee Records', path: '/employee-records', icon: User, description: 'Manage employee profiles and records.' },
  { title: 'Employee Assignments', path: '/employee-assignments', icon: User, description: 'Assign employees to posts and projects.' },
  { title: 'Off-Boarding', path: '/off-boarding', icon: User, description: 'Handle employee off-boarding process.' },
  { title: 'Step Increment', path: '/step-increment', icon: Setting, description: 'Manage step increments.' },
  { title: 'Step Increment Approval', path: '/step-increment-approval', icon: Setting, description: 'Approve step increments.' },
  { title: 'Salary Adjustment', path: '/salary-adjustment', icon: Money, description: 'Configure salary adjustments.' },
  { title: 'IPCR', path: '/ipcr', icon: Setting, description: 'Individual Performance Commitment Review.' },
  { title: 'Update 201 Schedule', path: '/update-201-schedule', icon: Timer, description: 'Update employee 201 schedule.' },
  { title: 'Export Employee Data', path: '/export-employee-data', icon: Setting, description: 'Export data for reporting.' },
  { title: 'Vacant Position Posting', path: '/vacant-position-posting', icon: Setting, description: 'Manage and post vacancies.' },
  { title: 'Length Of Service', path: '/length-of-service', icon: Timer, description: 'Compute and view length of service.' },
]

// Access Control State
const accessLoaded = ref(false)
const allowedMenuNameSet = ref(new Set())
const normalizeMenuName = (s) => String(s ?? '').toLowerCase().replace(/[^a-z0-9]/g, '')

const canAccessMenu = (menuName) => {
  if (!accessLoaded.value) return false
  return allowedMenuNameSet.value.has(normalizeMenuName(menuName))
}

const filtered201Modules = computed(() => {
  const q = moduleSearch.value.trim().toLowerCase()
  const textFiltered = !q ? allModules : allModules.filter((m) => m.title.toLowerCase().includes(q))
  return textFiltered.filter((m) => canAccessMenu(m.title))
})

const availableModuleShortcuts = computed(() => {
  return allModules.filter((m) => canAccessMenu(m.title)).slice(0, 6)
})

// Interactive Pending Requests State
const pendingRequests = ref([
  { id: 1, name: 'Jomari Dela Cruz', initials: 'JD', typeLabel: 'LEAVE', typeClass: 'leave', detail: 'Vacation · Aug 24–26' },
  { id: 2, name: 'Maricel Santos', initials: 'MS', typeLabel: 'OVERTIME', typeClass: 'ot', detail: '4.5 hrs · Aug 18' },
  { id: 3, name: 'Angelo Reyes', initials: 'AR', typeLabel: 'EXPENSE', typeClass: 'expense', detail: 'Client travel · ₱4,120' },
  { id: 4, name: 'Kim Panganiban', initials: 'KP', typeLabel: 'LEAVE', typeClass: 'leave', detail: 'Sick · Aug 20' },
])

const filteredRequests = computed(() => {
  const q = globalSearch.value.trim().toLowerCase()
  if (!q) return pendingRequests.value
  return pendingRequests.value.filter(r => r.name.toLowerCase().includes(q) || r.detail.toLowerCase().includes(q))
})

const approveRequest = (req) => {
  pendingRequests.value = pendingRequests.value.filter(r => r.id !== req.id)
  ElMessage.success(`Approved ${req.typeLabel} request for ${req.name}`)
}

const rejectRequest = (req) => {
  pendingRequests.value = pendingRequests.value.filter(r => r.id !== req.id)
  ElMessage.info(`Rejected request for ${req.name}`)
}

// Directory Search State
const directoryList = ref([
  { name: 'Maricel Santos', initials: 'MS', ext: '2141', email: 'm.santos@company.com' },
  { name: 'Angelo Reyes', initials: 'AR', ext: '2298', email: 'a.reyes@company.com' },
  { name: 'Kim Panganiban', initials: 'KP', ext: '2064', email: 'k.panganiban@company.com' },
  { name: 'Jomari Dela Cruz', initials: 'JD', ext: '2412', email: 'j.delacruz@company.com' }
])

const filteredDirectory = computed(() => {
  const q = dirSearch.value.trim().toLowerCase()
  if (!q) return directoryList.value
  return directoryList.value.filter(d => d.name.toLowerCase().includes(q) || (d.ext && d.ext.includes(q)) || (d.email && d.email.toLowerCase().includes(q)))
})

// Employees Table State
const employeeFilters = [
  { id: 'all', label: 'All' },
  { id: 'active', label: 'Active' },
  { id: 'probation', label: 'Probationary' },
  { id: 'leave', label: 'On Leave' }
]

const employeesList = ref([])

const filteredEmployees = computed(() => {
  let list = employeesList.value
  if (activeEmpFilter.value === 'active') list = list.filter(e => e.status === 'Active')
  else if (activeEmpFilter.value === 'probation') list = list.filter(e => e.status === 'Probation')
  else if (activeEmpFilter.value === 'leave') list = list.filter(e => e.status === 'On Leave')

  const q = globalSearch.value.trim().toLowerCase()
  if (q) {
    list = list.filter(e => e.name.toLowerCase().includes(q) || (e.dept && e.dept.toLowerCase().includes(q)) || (e.position && e.position.toLowerCase().includes(q)))
  }
  return list
})

const handleNotificationClick = () => {
  ElMessage.info(`You have ${complianceAlerts.value.expiring_contracts + complianceAlerts.value.pending_step_increments} compliance alerts and ${pendingRequests.value.length} pending requests.`)
}

const switchTab = (tabId) => {
  activeTab.value = tabId
  if (tabId === 'overview') {
    nextTick(() => {
      initCharts()
    })
  }
}

// Chart.js initialization logic
let chartHeadcountInst = null
let chartAttendanceInst = null
let chartTurnoverInst = null
let chartBudgetInst = null

const loadGoogleFonts = () => {
  if (!document.getElementById('hr-dashboard-fonts')) {
    const link = document.createElement('link')
    link.id = 'hr-dashboard-fonts'
    link.rel = 'stylesheet'
    link.href = 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:ital,wght@0,300..800;1,300..800&display=swap'
    document.head.appendChild(link)
  }
}

const loadChartJs = () => {
  return new Promise((resolve) => {
    if (window.Chart) {
      resolve()
      return
    }
    const cdnSources = [
      'https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js',
      'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.4/chart.umd.min.js',
      'https://unpkg.com/chart.js@4.4.4/dist/chart.umd.min.js'
    ]
    let index = 0
    const tryNext = () => {
      if (index >= cdnSources.length) {
        resolve()
        return
      }
      const script = document.createElement('script')
      script.src = cdnSources[index++]
      script.onload = () => resolve()
      script.onerror = () => tryNext()
      document.head.appendChild(script)
    }
    tryNext()
  })
}

const drawCustomFallbackCharts = () => {
  // 1. Headcount by Department
  const elHc = document.getElementById('chartHeadcount')
  if (elHc && elHc.getContext) {
    const ctx = elHc.getContext('2d')
    const width = (elHc.width = elHc.parentElement?.clientWidth || 400)
    const height = (elHc.height = elHc.parentElement?.clientHeight || 200)
    ctx.clearRect(0, 0, width, height)

    const labels = deptChartLabels.value.length ? deptChartLabels.value : ['Operations', 'Engineering', 'Sales', 'Finance', 'Design', 'People Ops']
    const data = deptChartData.value.length ? deptChartData.value : [24, 30, 15, 8, 5, 4]
    const maxVal = Math.max(...data, 1)

    const paddingLeft = 35, paddingBottom = 30, paddingTop = 20, paddingRight = 15
    const chartW = width - paddingLeft - paddingRight
    const chartH = height - paddingTop - paddingBottom
    const gap = chartW / labels.length
    const barWidth = Math.min(26, gap - 10)

    ctx.strokeStyle = '#EEF0F5'
    ctx.lineWidth = 1
    for (let i = 0; i <= 4; i++) {
      const y = paddingTop + (chartH / 4) * i
      ctx.beginPath()
      ctx.moveTo(paddingLeft, y)
      ctx.lineTo(width - paddingRight, y)
      ctx.stroke()
    }

    labels.forEach((lbl, idx) => {
      const val = data[idx] || 0
      const barH = (val / maxVal) * chartH
      const x = paddingLeft + idx * gap + (gap - barWidth) / 2
      const y = paddingTop + (chartH - barH)

      ctx.fillStyle = '#3457D5'
      if (ctx.roundRect) {
        ctx.beginPath()
        ctx.roundRect(x, y, barWidth, barH, [6, 6, 0, 0])
        ctx.fill()
      } else {
        ctx.fillRect(x, y, barWidth, barH)
      }

      ctx.fillStyle = '#475569'
      ctx.font = '10px sans-serif'
      ctx.textAlign = 'center'
      ctx.fillText(val, x + barWidth / 2, Math.max(y - 4, 12))

      ctx.fillStyle = '#64748B'
      ctx.font = '10px sans-serif'
      ctx.fillText(lbl.length > 7 ? lbl.substring(0, 6) + '…' : lbl, x + barWidth / 2, height - 8)
    })
  }

  // 2. Attendance Today
  const elAtt = document.getElementById('chartAttendance')
  if (elAtt && elAtt.getContext) {
    const ctx = elAtt.getContext('2d')
    const width = (elAtt.width = elAtt.parentElement?.clientWidth || 300)
    const height = (elAtt.height = elAtt.parentElement?.clientHeight || 200)
    ctx.clearRect(0, 0, width, height)

    const present = attendanceToday.value.present || 1
    const onLeave = attendanceToday.value.on_leave || 39
    const absent = attendanceToday.value.unplanned_absence || 15
    const total = present + onLeave + absent
    const slices = [
      { val: present, color: '#16A34A' },
      { val: onLeave, color: '#3457D5' },
      { val: absent, color: '#DC2626' }
    ]

    const centerX = width / 2
    const centerY = height / 2 - 5
    const outerRadius = Math.min(centerX, centerY) - 15
    const innerRadius = outerRadius * 0.72

    let startAngle = -Math.PI / 2
    slices.forEach(slice => {
      if (slice.val <= 0) return
      const sliceAngle = (slice.val / total) * (Math.PI * 2)
      ctx.beginPath()
      ctx.arc(centerX, centerY, outerRadius, startAngle, startAngle + sliceAngle)
      ctx.arc(centerX, centerY, innerRadius, startAngle + sliceAngle, startAngle, true)
      ctx.closePath()
      ctx.fillStyle = slice.color
      ctx.fill()
      startAngle += sliceAngle
    })

    ctx.fillStyle = '#0F172A'
    ctx.font = 'bold 16px sans-serif'
    ctx.textAlign = 'center'
    ctx.textBaseline = 'middle'
    ctx.fillText(total, centerX, centerY - 6)

    ctx.fillStyle = '#64748B'
    ctx.font = '10px sans-serif'
    ctx.fillText('Total', centerX, centerY + 12)
  }

  // 3. Turnover & Retention
  const elTurn = document.getElementById('chartTurnover')
  if (elTurn && elTurn.getContext) {
    const ctx = elTurn.getContext('2d')
    const width = (elTurn.width = elTurn.parentElement?.clientWidth || 400)
    const height = (elTurn.height = elTurn.parentElement?.clientHeight || 160)
    ctx.clearRect(0, 0, width, height)

    const labels = ['Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug']
    const volData = [5.1, 5.6, 6.0, 6.4, 6.1, 6.0]
    const involData = [1.8, 2.0, 2.2, 2.6, 2.4, 2.4]

    const paddingLeft = 30, paddingBottom = 22, paddingTop = 12, paddingRight = 15
    const chartW = width - paddingLeft - paddingRight
    const chartH = height - paddingTop - paddingBottom

    ctx.strokeStyle = '#EEF0F5'
    ctx.lineWidth = 1
    for (let i = 0; i <= 3; i++) {
      const y = paddingTop + (chartH / 3) * i
      ctx.beginPath()
      ctx.moveTo(paddingLeft, y)
      ctx.lineTo(width - paddingRight, y)
      ctx.stroke()
    }

    const drawLine = (data, color, fillColor) => {
      const gap = chartW / (data.length - 1)
      ctx.beginPath()
      data.forEach((val, i) => {
        const x = paddingLeft + i * gap
        const y = paddingTop + chartH - (val / 8) * chartH
        if (i === 0) ctx.moveTo(x, y)
        else ctx.lineTo(x, y)
      })

      ctx.strokeStyle = color
      ctx.lineWidth = 2.2
      ctx.stroke()

      ctx.lineTo(paddingLeft + (data.length - 1) * gap, paddingTop + chartH)
      ctx.lineTo(paddingLeft, paddingTop + chartH)
      ctx.closePath()
      ctx.fillStyle = fillColor
      ctx.fill()
    }

    drawLine(volData, '#3457D5', 'rgba(52,87,213,0.08)')
    drawLine(involData, '#D97706', 'rgba(217,119,6,0.06)')

    labels.forEach((lbl, i) => {
      const x = paddingLeft + i * (chartW / (labels.length - 1))
      ctx.fillStyle = '#64748B'
      ctx.font = '10px sans-serif'
      ctx.textAlign = 'center'
      ctx.fillText(lbl, x, height - 4)
    })
  }

  // 4. Labor Cost — Budget vs Actual
  const elBud = document.getElementById('chartBudget')
  if (elBud && elBud.getContext) {
    const ctx = elBud.getContext('2d')
    const width = (elBud.width = elBud.parentElement?.clientWidth || 400)
    const height = (elBud.height = elBud.parentElement?.clientHeight || 160)
    ctx.clearRect(0, 0, width, height)

    const labels = ['Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug']
    const budgetData = [6.0, 6.1, 6.2, 6.3, 6.3, 6.4]
    const actualData = [5.8, 6.0, 6.3, 6.5, 6.2, parseFloat(kpis.value.payroll_spend) || 3.24]

    const paddingLeft = 35, paddingBottom = 22, paddingTop = 12, paddingRight = 15
    const chartW = width - paddingLeft - paddingRight
    const chartH = height - paddingTop - paddingBottom
    const groupW = chartW / labels.length
    const barW = Math.min(12, groupW / 2.5)

    ctx.strokeStyle = '#EEF0F5'
    ctx.lineWidth = 1
    for (let i = 0; i <= 3; i++) {
      const y = paddingTop + (chartH / 3) * i
      ctx.beginPath()
      ctx.moveTo(paddingLeft, y)
      ctx.lineTo(width - paddingRight, y)
      ctx.stroke()
    }

    labels.forEach((lbl, i) => {
      const bVal = budgetData[i]
      const aVal = actualData[i]

      const bH = (bVal / 7) * chartH
      const aH = (aVal / 7) * chartH

      const groupX = paddingLeft + i * groupW + (groupW - (barW * 2 + 4)) / 2

      ctx.fillStyle = '#E9EDFC'
      if (ctx.roundRect) {
        ctx.beginPath()
        ctx.roundRect(groupX, paddingTop + (chartH - bH), barW, bH, [4, 4, 0, 0])
        ctx.fill()
      } else {
        ctx.fillRect(groupX, paddingTop + (chartH - bH), barW, bH)
      }

      ctx.fillStyle = '#3457D5'
      if (ctx.roundRect) {
        ctx.beginPath()
        ctx.roundRect(groupX + barW + 3, paddingTop + (chartH - aH), barW, aH, [4, 4, 0, 0])
        ctx.fill()
      } else {
        ctx.fillRect(groupX + barW + 3, paddingTop + (chartH - aH), barW, aH)
      }

      ctx.fillStyle = '#64748B'
      ctx.font = '10px sans-serif'
      ctx.textAlign = 'center'
      ctx.fillText(lbl, groupX + barW + 1, height - 4)
    })
  }
}

const initCharts = () => {
  if (!window.Chart) {
    drawCustomFallbackCharts()
    return
  }

  window.Chart.defaults.font.family = "'Plus Jakarta Sans', 'Inter', sans-serif"
  window.Chart.defaults.font.size = 11
  window.Chart.defaults.color = '#68708A'

  if (chartHeadcountInst) chartHeadcountInst.destroy()
  if (chartAttendanceInst) chartAttendanceInst.destroy()
  if (chartTurnoverInst) chartTurnoverInst.destroy()
  if (chartBudgetInst) chartBudgetInst.destroy()

  const elHc = document.getElementById('chartHeadcount')
  if (elHc) {
    chartHeadcountInst = new window.Chart(elHc, {
      type: 'bar',
      data: {
        labels: deptChartLabels.value,
        datasets: [{
          data: deptChartData.value,
          backgroundColor: '#3457D5',
          borderRadius: 6,
          maxBarThickness: 26,
        }]
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          x: { grid: { display: false }, border: { display: false } },
          y: { grid: { color: '#EEF0F5' }, border: { display: false } }
        }
      }
    })
  }

  const elAtt = document.getElementById('chartAttendance')
  if (elAtt) {
    chartAttendanceInst = new window.Chart(elAtt, {
      type: 'doughnut',
      data: {
        labels: ['Present', 'On leave', 'Absent'],
        datasets: [{
          data: [attendanceToday.value.present, attendanceToday.value.on_leave, attendanceToday.value.unplanned_absence],
          backgroundColor: ['#16A34A', '#3457D5', '#DC2626'],
          borderWidth: 0,
        }]
      },
      options: {
        responsive: true, maintainAspectRatio: false, cutout: '72%',
        plugins: { legend: { display: false } }
      }
    })
  }

  const elTurn = document.getElementById('chartTurnover')
  if (elTurn) {
    chartTurnoverInst = new window.Chart(elTurn, {
      type: 'line',
      data: {
        labels: ['Mar','Apr','May','Jun','Jul','Aug'],
        datasets: [
          { label: 'Voluntary', data: [5.1,5.6,6.0,6.4,6.1,6.0], borderColor: '#3457D5', backgroundColor: 'rgba(52,87,213,.08)', fill: true, tension: .35, pointRadius: 0, borderWidth: 2.2 },
          { label: 'Involuntary', data: [1.8,2.0,2.2,2.6,2.4,2.4], borderColor: '#D97706', backgroundColor: 'rgba(217,119,6,.06)', fill: true, tension: .35, pointRadius: 0, borderWidth: 2.2 },
        ]
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          x: { grid: { display: false }, border: { display: false } },
          y: { grid: { color: '#EEF0F5' }, border: { display: false }, ticks: { callback: v => v + '%' } }
        }
      }
    })
  }

  const elBud = document.getElementById('chartBudget')
  if (elBud) {
    chartBudgetInst = new window.Chart(elBud, {
      type: 'bar',
      data: {
        labels: ['Mar','Apr','May','Jun','Jul','Aug'],
        datasets: [
          { label: 'Budget', data: [6.0,6.1,6.2,6.3,6.3,6.4], backgroundColor: '#E9EDFC', borderRadius: 5, maxBarThickness: 16 },
          { label: 'Actual', data: [5.8,6.0,6.3,6.5,6.2,kpis.value.payroll_spend], backgroundColor: '#3457D5', borderRadius: 5, maxBarThickness: 16 },
        ]
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          x: { grid: { display: false }, border: { display: false } },
          y: { grid: { color: '#EEF0F5' }, border: { display: false }, ticks: { callback: v => '₱' + v + 'M' } }
        }
      }
    })
  }
}

// Fetch live backend metrics
const fetchLiveDashboardData = async () => {
  try {
    const res = await hrOverviewApi.getOverview()
    const data = res?.data?.data
    if (data) {
      if (data.kpis) kpis.value = data.kpis
      if (data.attendance_today) attendanceToday.value = data.attendance_today
      if (data.compliance_alerts) complianceAlerts.value = data.compliance_alerts
      if (data.onboarding_pipeline) onboardingPipeline.value = data.onboarding_pipeline
      if (data.pending_requests && data.pending_requests.length > 0) {
        pendingRequests.value = data.pending_requests
      }
      if (data.headcount_by_department && data.headcount_by_department.length > 0) {
        deptChartLabels.value = data.headcount_by_department.map(d => d.department)
        deptChartData.value = data.headcount_by_department.map(d => d.total)
      }
    }
  } catch (e) {
    console.warn('Backend overview endpoint unreachable, using standard fallbacks:', e?.message)
  }

  // Fetch live announcements
  await fetchAnnouncements()

  // Also fetch live employee list for directory search
  try {
    const empRes = await employeeApi.getEmployees()
    const rawList = empRes?.data?.data ?? empRes?.data ?? []
    if (Array.isArray(rawList) && rawList.length > 0) {
      directoryList.value = rawList.slice(0, 10).map((emp, i) => {
        const fullName = `${emp.first_name || ''} ${emp.last_name || ''}`.trim() || `Employee ${emp.id}`
        const parts = fullName.split(' ')
        const initials = parts.length > 1 ? (parts[0][0] + parts[parts.length - 1][0]).toUpperCase() : fullName.substring(0, 2).toUpperCase()
        return {
          name: fullName,
          initials,
          ext: String(2000 + i),
          email: emp.email || `${fullName.toLowerCase().replace(/\s+/g, '.')}@company.com`
        }
      })
    }
  } catch (e) {
    console.warn('Employees API not accessible for directory search:', e?.message)
  }
}

onMounted(async () => {
  loadGoogleFonts()
  await loadChartJs()
  await fetchLiveDashboardData()
  nextTick(() => {
    initCharts()
  })
  window.addEventListener('resize', initCharts)

  // Load User Permissions
  try {
    const userResp = await authApi.getCurrentUser()
    const user = userResp?.data?.data?.user ?? userResp?.data?.user ?? userResp?.data
    const userId = user?.id
    if (!userId) return

    const withHrmAccess = Number(user?.with_hrm_access) === 1 || user?.with_hrm_access === true
    if (!withHrmAccess) {
      allowedMenuNameSet.value = new Set()
      accessLoaded.value = true
      return
    }

    const rightsResp = await accessRightsApi.get(userId)
    const rights = rightsResp?.data?.data ?? rightsResp?.data ?? {}

    const isEnabledStatus = (s) => {
      if (s === 1 || s === '1') return true
      if (s === true || s === 'true' || s === 'TRUE') return true
      return Number(s) === 1
    }

    const enabledMenus = [
      ...(rights?.hrm_menu || []),
      ...(rights?.hrt_menu || []),
      ...(rights?.hrp_menu || []),
      ...(rights?.cpm_menu || []),
      ...(rights?.ld_menu || []),
      ...(rights?.mig_menu || []),
    ].filter((m) => isEnabledStatus(m?.status))

    allowedMenuNameSet.value = new Set(enabledMenus.map((m) => normalizeMenuName(m?.menu)))
    accessLoaded.value = true
  } catch (e) {
    console.error('Failed to load access rights:', e?.response?.data || e?.message || e)
    accessLoaded.value = true
  }
})
</script>

<style scoped>
:root {
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
  --radius: 14px;
  --shadow: 0 1px 2px rgba(18,23,43,0.04), 0 8px 24px -12px rgba(18,23,43,0.10);
}

.hr-dashboard-container {
  font-family: 'Plus Jakarta Sans', 'Inter', sans-serif;
  color: #12172B;
  background: #F3F5F8;
  border-radius: 16px;
  padding: 0;
  margin: -16px;
}

.mono { font-family: 'Courier New', Courier, monospace; }
.display { font-family: 'Plus Jakarta Sans', sans-serif; }

/* Top Header Bar */
.hr-topbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 18px 24px;
  background: #FFFFFF;
  border-bottom: 1px solid #E5E8F0;
  gap: 16px;
  border-top-left-radius: 16px;
  border-top-right-radius: 16px;
}

.hr-topbar-title {
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-size: 19px;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 9px;
}

.hr-topbar-title .tag {
  font-family: 'Courier New', Courier, monospace;
  font-size: 10.5px;
  font-weight: 600;
  color: #2540A8;
  background: #E9EDFC;
  padding: 2px 7px;
  border-radius: 5px;
  letter-spacing: .02em;
}

.hr-topbar-sub {
  font-size: 12.5px;
  color: #68708A;
  margin-top: 2px;
}

.search-box {
  flex: 1;
  max-width: 360px;
  display: flex;
  align-items: center;
  gap: 8px;
  background: #F3F5F8;
  border: 1px solid #E5E8F0;
  border-radius: 10px;
  padding: 8px 12px;
  color: #9CA3B8;
  font-size: 13px;
}

.search-box svg { width: 15px; height: 15px; flex: none; opacity: .6; }
.search-box input {
  border: none; background: transparent; outline: none;
  font-family: inherit; font-size: 13px; color: #12172B; width: 100%;
}

.kbd {
  font-family: 'Courier New', Courier, monospace; font-size: 10px;
  background: #FFFFFF; border: 1px solid #E5E8F0;
  padding: 1px 5px; border-radius: 4px; color: #9CA3B8;
}

.hr-topbar-right { display: flex; align-items: center; gap: 14px; flex: none; }
.icon-btn {
  width: 34px; height: 34px; border-radius: 9px;
  border: 1px solid #E5E8F0; background: #FFFFFF;
  display: flex; align-items: center; justify-content: center;
  position: relative; cursor: pointer;
}
.icon-btn svg { width: 16px; height: 16px; color: #68708A; }
.icon-btn .dot {
  position: absolute; top: 6px; right: 6px; width: 6px; height: 6px; border-radius: 50%;
  background: #DC2626; border: 1.5px solid #FFFFFF;
}

.avatar {
  width: 30px; height: 30px; border-radius: 9px;
  background: #2A3560;
  display: flex; align-items: center; justify-content: center;
  font-size: 11.5px; font-weight: 600; color: #DCE1F5;
  flex: none;
}

/* Nav Tabs Bar */
.nav-tabs-bar {
  display: flex;
  gap: 6px;
  padding: 12px 24px;
  background: #0E1526;
  border-bottom: 1px solid #1A2340;
  overflow-x: auto;
}

.tab-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 14px;
  border-radius: 9px;
  font-size: 13px;
  font-weight: 500;
  color: #AEB4CE;
  border: 1px solid transparent;
  background: transparent;
  cursor: pointer;
  white-space: nowrap;
  transition: all .15s ease;
}

.tab-btn:hover { background: #1A2340; color: #FFFFFF; }
.tab-btn.active {
  background: #1A2340;
  color: #FFFFFF;
  border-color: rgba(108,134,238,.35);
  font-weight: 600;
}

.tab-icon { display: flex; align-items: center; }

/* Content Section */
.hr-content { padding: 24px; }

.page { display: none; }
.page.active { display: block; animation: fade .25s ease; }
@keyframes fade { from { opacity: 0; transform: translateY(4px); } to { opacity: 1; transform: none; } }

.section-label {
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-size: 13.5px; font-weight: 600;
  color: #12172B;
  display: flex; align-items: center; gap: 8px;
  margin: 0 0 12px;
}

.section-label .n {
  font-family: 'Courier New', Courier, monospace;
  font-size: 10.5px; color: #9CA3B8;
  border: 1px solid #E5E8F0;
  padding: 1px 6px; border-radius: 20px;
}

/* KPI Cards */
.kpi-row {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  margin-bottom: 24px;
}

.kpi-card {
  background: #FFFFFF;
  border: 1px solid #E5E8F0;
  border-radius: 14px;
  padding: 16px 18px 15px;
  box-shadow: 0 1px 2px rgba(18,23,43,0.04), 0 8px 24px -12px rgba(18,23,43,0.10);
}

.kpi-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
.kpi-label { font-size: 12px; color: #68708A; font-weight: 500; }
.kpi-icon {
  width: 26px; height: 26px; border-radius: 7px;
  display: flex; align-items: center; justify-content: center;
}
.kpi-icon svg { width: 14px; height: 14px; }

.kpi-value {
  font-family: 'Courier New', Courier, monospace;
  font-size: 26px; font-weight: 600; letter-spacing: -.01em;
  display: flex; align-items: baseline; gap: 6px;
}
.kpi-value .unit { font-size: 13px; color: #9CA3B8; font-weight: 500; }

.kpi-trend {
  margin-top: 8px; font-size: 11.5px; font-weight: 600;
  display: flex; align-items: center; gap: 4px;
}
.kpi-trend.up { color: #16A34A; }
.kpi-trend.down { color: #DC2626; }
.kpi-trend .ctx { color: #9CA3B8; font-weight: 500; margin-left: 2px; }

/* Grid Layout */
.grid-layout {
  display: grid;
  grid-template-columns: 1fr 300px;
  gap: 22px;
  align-items: start;
}

.card {
  background: #FFFFFF;
  border: 1px solid #E5E8F0;
  border-radius: 14px;
  box-shadow: 0 1px 2px rgba(18,23,43,0.04), 0 8px 24px -12px rgba(18,23,43,0.10);
  padding: 18px 20px 20px;
}

.card-head {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 14px;
}

.card-title { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 14.5px; font-weight: 600; }
.card-title .sub { display: block; font-family: 'Inter', sans-serif; font-size: 11.5px; font-weight: 400; color: #68708A; margin-top: 2px; }

.pill { font-size: 10.5px; font-weight: 600; padding: 3px 9px; border-radius: 20px; }
.pill.blue { background: #E9EDFC; color: #2540A8; }

.charts-row { display: grid; grid-template-columns: 1.3fr 1fr; gap: 16px; margin-bottom: 16px; }
.charts-row2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px; }
.chart-wrap { height: 190px; position: relative; }

.legend-row { display: flex; gap: 16px; margin-top: 10px; flex-wrap: wrap; }
.legend-item { display: flex; align-items: center; gap: 6px; font-size: 11.5px; color: #68708A; }
.legend-dot { width: 8px; height: 8px; border-radius: 2px; }

.stat-inline { display: flex; gap: 22px; margin-top: 14px; padding-top: 14px; border-top: 1px dashed #E5E8F0; }
.stat-inline div .v { font-family: 'Courier New', Courier, monospace; font-weight: 600; font-size: 17px; }
.stat-inline div .l { font-size: 11px; color: #68708A; margin-top: 1px; }

/* Action Block */
.action-block { margin-bottom: 16px; }
.req-row {
  display: flex; align-items: center; gap: 12px;
  padding: 11px 4px;
  border-bottom: 1px solid #E5E8F0;
}
.req-row:last-child { border-bottom: none; }

.req-avatar {
  width: 32px; height: 32px; border-radius: 9px; background: #E9EDFC; color: #2540A8;
  display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 11.5px; flex: none;
}

.req-body { flex: 1; min-width: 0; }
.req-name { font-size: 13px; font-weight: 600; }
.req-meta { font-size: 11.5px; color: #68708A; margin-top: 1px; }

.req-type { font-size: 10px; font-weight: 600; padding: 2px 7px; border-radius: 6px; margin-right: 6px; }
.req-type.leave { background: #E9EDFC; color: #2540A8; }
.req-type.ot { background: #FEF3DD; color: #D97706; }
.req-type.expense { background: #E3F8EA; color: #16A34A; }

.req-actions { display: flex; gap: 6px; flex: none; }
.btn-round {
  width: 28px; height: 28px; border-radius: 8px; border: 1px solid #E5E8F0;
  display: flex; align-items: center; justify-content: center; background: #FFFFFF; cursor: pointer;
  transition: all .15s ease;
}
.btn-round svg { width: 13px; height: 13px; }
.btn-round.approve svg { color: #16A34A; }
.btn-round.approve:hover { background: #E3F8EA; border-color: #16A34A; }
.btn-round.reject svg { color: #DC2626; }
.btn-round.reject:hover { background: #FCE8E7; border-color: #DC2626; }

.empty-state { padding: 12px; font-size: 12px; color: #9CA3B8; text-align: center; }

.alert-row {
  display: flex; align-items: flex-start; gap: 10px;
  padding: 10px 4px; border-bottom: 1px solid #E5E8F0;
}
.alert-row:last-child { border-bottom: none; }

.alert-dot { width: 7px; height: 7px; border-radius: 50%; margin-top: 5px; flex: none; }
.alert-dot.high { background: #DC2626; }
.alert-dot.med { background: #D97706; }
.alert-title { font-size: 12.5px; font-weight: 600; }
.alert-meta { font-size: 11px; color: #68708A; margin-top: 1px; }

.onboard-row { padding: 11px 2px; border-bottom: 1px solid #E5E8F0; }
.onboard-row:last-child { border-bottom: none; }
.onboard-top { display: flex; justify-content: space-between; font-size: 12.5px; margin-bottom: 6px; }
.onboard-top .nm { font-weight: 600; }
.onboard-top .pct { font-family: 'Courier New', Courier, monospace; color: #68708A; font-size: 11.5px; }

.bar-track { height: 6px; background: #F3F5F8; border-radius: 20px; overflow: hidden; }
.bar-fill { height: 100%; background: #3457D5; border-radius: 20px; }

/* Right Rail Shortcuts & Feed */
.rail-card { margin-bottom: 16px; }
.shortcut-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.shortcut {
  border: 1px solid #E5E8F0; border-radius: 10px; padding: 11px 10px;
  display: flex; flex-direction: column; gap: 7px;
  background: #F3F5F8; cursor: pointer; transition: background .15s ease;
}
.shortcut:hover { background: #E9EDFC; }
.shortcut span { font-size: 11.5px; font-weight: 600; line-height: 1.25; color: #12172B; }

.feed-item { padding: 11px 2px; border-bottom: 1px solid #E5E8F0; }
.feed-item:last-child { border-bottom: none; }
.feed-tag {
  font-size: 9.5px; font-weight: 700; letter-spacing: .03em; text-transform: uppercase;
  color: #2540A8; margin-bottom: 4px; display: inline-block;
}
.feed-title { font-size: 12.5px; font-weight: 600; line-height: 1.35; }
.feed-meta { font-size: 11px; color: #9CA3B8; margin-top: 3px; }

.dir-input {
  display: flex; align-items: center; gap: 8px;
  border: 1px solid #E5E8F0; border-radius: 10px; padding: 9px 11px; background: #F3F5F8;
  margin-bottom: 10px;
}
.dir-input svg { width: 14px; height: 14px; color: #9CA3B8; }
.dir-input input { border: none; background: transparent; outline: none; font-size: 12.5px; font-family: inherit; width: 100%; }

.dir-result { display: flex; align-items: center; gap: 9px; padding: 7px 2px; }
.dir-result .req-avatar { width: 26px; height: 26px; font-size: 10px; }
.dir-result .nm { font-size: 12px; font-weight: 600; }
.dir-result .ext { font-size: 10.5px; color: #68708A; font-family: 'Courier New', Courier, monospace; }

/* Table Styling */
.table-card { background: #FFFFFF; border: 1px solid #E5E8F0; border-radius: 14px; box-shadow: 0 1px 2px rgba(18,23,43,0.04), 0 8px 24px -12px rgba(18,23,43,0.10); overflow: hidden; }
table { width: 100%; border-collapse: collapse; font-size: 13px; }
thead th {
  text-align: left; font-size: 10.5px; text-transform: uppercase; letter-spacing: .04em;
  color: #9CA3B8; font-weight: 600; padding: 12px 16px; background: #F3F5F8;
  border-bottom: 1px solid #E5E8F0;
}
tbody td { padding: 12px 16px; border-bottom: 1px solid #E5E8F0; }
tbody tr:last-child td { border-bottom: none; }
tbody tr:hover { background: #F3F5F8; }
.name-cell { display: flex; align-items: center; gap: 10px; font-weight: 600; }
.status-chip { font-size: 10.5px; font-weight: 600; padding: 3px 9px; border-radius: 20px; }
.status-chip.active { background: #E3F8EA; color: #16A34A; }
.status-chip.probation { background: #FEF3DD; color: #D97706; }
.status-chip.leave { background: #E9EDFC; color: #2540A8; }

.toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; gap: 12px; }
.filter-chip {
  font-size: 12px; font-weight: 500; padding: 7px 13px; border-radius: 9px; border: 1px solid #E5E8F0;
  background: #FFFFFF; color: #68708A; cursor: pointer; transition: all .15s ease;
}
.filter-chip.on { background: #0E1526; color: #FFFFFF; border-color: #0E1526; }
.filter-row { display: flex; gap: 8px; }
.btn-primary {
  background: #3457D5; color: #FFFFFF; border: none; padding: 9px 16px; border-radius: 9px;
  font-size: 12.5px; font-weight: 600; display: flex; align-items: center; gap: 6px; cursor: pointer;
  transition: background .15s ease;
}
.btn-primary:hover { background: #2540A8; }
.btn-primary svg { width: 13px; height: 13px; }

/* Kanban Recruitment */
.kanban { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; }
.kanban-col-head {
  font-size: 12px; font-weight: 600; color: #68708A;
  display: flex; justify-content: space-between; padding: 0 4px 10px;
}
.kanban-col-head .n { font-family: 'Courier New', Courier, monospace; color: #9CA3B8; }
.kcard {
  background: #FFFFFF; border: 1px solid #E5E8F0; border-radius: 11px;
  padding: 12px 13px; margin-bottom: 10px; box-shadow: 0 1px 2px rgba(18,23,43,0.04), 0 8px 24px -12px rgba(18,23,43,0.10);
}
.kcard .role { font-size: 10.5px; color: #68708A; margin-bottom: 3px; }
.kcard .cand { font-size: 13px; font-weight: 600; margin-bottom: 8px; }
.kcard .meta { display: flex; justify-content: space-between; align-items: center; }
.kcard .score { font-family: 'Courier New', Courier, monospace; font-size: 11px; color: #2540A8; background: #E9EDFC; padding: 2px 7px; border-radius: 6px; }
.kcard .days { font-size: 10.5px; color: #9CA3B8; }

/* Calendar & Leave Grid */
.leave-summary { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 20px; }
.cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 6px; }
.cal-head { font-size: 10.5px; color: #9CA3B8; text-align: center; font-weight: 600; padding-bottom: 4px; }
.cal-cell {
  aspect-ratio: 1/0.72; border: 1px solid #E5E8F0; border-radius: 8px;
  padding: 6px; font-size: 11px; color: #9CA3B8; position: relative; background: #F3F5F8;
}
.cal-cell.today { border-color: #3457D5; background: #E9EDFC; color: #2540A8; font-weight: 700; }
.cal-cell .dots { position: absolute; bottom: 5px; left: 6px; display: flex; gap: 2px; }
.cal-cell .dots span { width: 5px; height: 5px; border-radius: 50%; }

/* Payroll Progress Bars */
.payroll-bar { display: flex; align-items: center; gap: 10px; padding: 9px 0; }
.payroll-bar .lbl { width: 110px; font-size: 12px; color: #68708A; flex: none; }
.payroll-bar .track { flex: 1; height: 9px; background: #F3F5F8; border-radius: 20px; overflow: hidden; position: relative; }
.payroll-bar .fill { height: 100%; border-radius: 20px; background: #3457D5; }
.payroll-bar .val { width: 76px; text-align: right; font-family: 'Courier New', Courier, monospace; font-size: 11.5px; font-weight: 600; flex: none; }

/* 201 Modules Grid */
.modules-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 16px; }
.module-card { height: 180px; display: flex; flex-direction: column; justify-content: space-between; border-radius: 12px; }
.card-header-inner { display: flex; align-items: center; gap: 8px; }
.desc { color: #68708A; font-size: 12.5px; margin: 8px 0; }
.actions { display: flex; justify-content: flex-end; }
.mb-4 { margin-bottom: 16px; }

@media (max-width: 1180px) {
  .grid-layout { grid-template-columns: 1fr; }
  .kpi-row { grid-template-columns: repeat(2, 1fr); }
  .charts-row, .charts-row2 { grid-template-columns: 1fr; }
  .kanban { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 760px) {
  .kpi-row { grid-template-columns: 1fr; }
  .kanban { grid-template-columns: 1fr; }
}
</style>
