<template>
  <div class="layout" :class="{ collapsed }">
    <aside class="sidebar" aria-label="Main navigation">
      <div class="brand-row">
        <img v-if="showLogo && companyLogoSrc" class="brand-logo" :src="companyLogoSrc" :alt="companyName" @error="onLogoError" />
        <div class="brand-text">
          <h4 class="brand-title" :title="companyName">{{ companyName }}</h4>
          <div class="brand-subtitle">HR Module</div>
        </div>
        <el-tooltip :content="collapsed ? 'Expand menu' : 'Collapse menu'" placement="right" :show-after="400">
          <button
            type="button"
            class="collapse-btn"
            :aria-expanded="!collapsed"
            aria-controls="sidebar-nav"
            @click="collapsed = !collapsed"
          >
            <el-icon :size="18">
              <Fold v-if="!collapsed" />
              <Expand v-else />
            </el-icon>
          </button>
        </el-tooltip>
      </div>
      <nav id="sidebar-nav" class="menu">
        <div class="menu-section">
          <p v-if="!collapsed" class="menu-section-label menu-section-label--first">General</p>
          <RouterLink to="/hr" class="menu-item">
            <el-icon><Menu /></el-icon>
            <span class="label">HR Module</span>
          </RouterLink>
        <RouterLink v-if="canAccessMenu('Employee Records')" to="/employee-records" class="menu-item">
          <el-icon><User /></el-icon>
          <span class="label">Employee Records</span>
        </RouterLink>
        <RouterLink v-if="canAccessMenu('Employee Assignments')" to="/employee-assignments" class="menu-item">
          <el-icon><Timer /></el-icon>
          <span class="label">Employee Assignments</span>
        </RouterLink>
        <RouterLink v-if="canAccessMenu('Off-Boarding')" to="/off-boarding" class="menu-item">
          <el-icon><Timer /></el-icon>
          <span class="label">Off-Boarding</span>
        </RouterLink>
        <RouterLink v-if="canAccessMenu('Step Increment')" to="/step-increment" class="menu-item">
          <el-icon><Timer /></el-icon>
          <span class="label">Step Increment</span>
        </RouterLink>
        <RouterLink v-if="canAccessMenu('Step Increment Approval')" to="/step-increment-approval" class="menu-item">
          <el-icon><Timer /></el-icon>
          <span class="label">Step Increment Approval</span>
        </RouterLink>
        <RouterLink v-if="canAccessMenu('Salary Adjustment')" to="/salary-adjustment" class="menu-item">
          <el-icon><Timer /></el-icon>
          <span class="label">Salary Adjustment</span>
        </RouterLink>
        <RouterLink v-if="canAccessMenu('IPCR')" to="/ipcr" class="menu-item">
          <el-icon><Timer /></el-icon>
          <span class="label">IPCR</span>
        </RouterLink>
        <RouterLink v-if="canAccessMenu('OPCR')" to="/opcr" class="menu-item">
          <el-icon><Document /></el-icon>
          <span class="label">OPCR</span>
        </RouterLink>
        <RouterLink v-if="canAccessMenu('DPCR')" to="/dpcr" class="menu-item">
          <el-icon><Timer /></el-icon>
          <span class="label">DPCR</span>
        </RouterLink>
        <RouterLink v-if="canAccessMenu('Update 201 Schedule')" to="/update-201-schedule" class="menu-item">
          <el-icon><Timer /></el-icon>
          <span class="label">Update 201 Schedule</span>
        </RouterLink>
        <RouterLink v-if="canAccessMenu('Export Employee Data')" to="/export-employee-data" class="menu-item">
          <el-icon><Timer /></el-icon>
          <span class="label">Export Employee Data</span>
        </RouterLink>
        <RouterLink v-if="canAccessMenu('Vacant Position Posting')" to="/vacant-position-posting" class="menu-item">
          <el-icon><Timer /></el-icon>
          <span class="label">Vacant Position Posting</span>
        </RouterLink>
        <RouterLink v-if="canAccessMenu('Length Of Service')" to="/length-of-service" class="menu-item">
          <el-icon><Timer /></el-icon>
          <span class="label">Length Of Service</span>
        </RouterLink>
        </div>

        <div
          v-if="showHrReportsNav"
          class="menu-section"
        >
          <p v-if="!collapsed" class="menu-section-label">HR reports</p>
          <details class="submenu" :open="true">
          <summary class="submenu-summary" :class="{ active: isHrActive }" @click.prevent="toggleHr">
            <div class="menu-item" role="button" aria-expanded="openHr">
              <el-icon><Setting /></el-icon>
              <span class="label">HR Reports</span>
            </div>
            <span v-if="!collapsed" class="chevron" :class="{ open: openHr }">▾</span>
          </summary>
          <el-collapse-transition>
            <div v-show="!collapsed && openHr" class="submenu-items">
              <RouterLink v-if="canAccessMenu('Personal Data Sheet') || canAccessMenu('HR Reports')" to="/hr-reports/personal-data-sheet" class="submenu-item"><el-icon><OfficeBuilding /></el-icon><span class="label">Personal Data Sheet</span></RouterLink>
              <RouterLink v-if="canAccessMenu('NOSI') || canAccessMenu('HR Reports')" to="/hr-reports/nosi" class="submenu-item"><el-icon><OfficeBuilding /></el-icon><span class="label">NOSI</span></RouterLink>
              <RouterLink v-if="canAccessMenu('NOSA') || canAccessMenu('HR Reports')" to="/hr-reports/nosa" class="submenu-item"><el-icon><OfficeBuilding /></el-icon><span class="label">NOSA</span></RouterLink>
              <!-- <RouterLink v-if="canAccessMenu('Terminal Leave Endorsement') || canAccessMenu('HR Reports')" to="/hr-reports/terminal-leave-endorsement" class="submenu-item"><el-icon><OfficeBuilding /></el-icon><span class="label">Terminal Leave Endorsement</span></RouterLink> -->
              <!-- <RouterLink to="/hr-reports/travel-abroad-endorsement" class="submenu-item"><el-icon><Collection /></el-icon><span class="label">Assumption to Duty</span></RouterLink> -->
              <RouterLink v-if="canAccessMenu('Plantilla Report') || canAccessMenu('HR Reports')" to="/hr-reports/plantilla-report" class="submenu-item"><el-icon><Medal /></el-icon><span class="label">Plantilla Report</span></RouterLink>
              <RouterLink v-if="canAccessMenu('Request for Publication') || canAccessMenu('HR Reports')" to="/hr-reports/request-for-publicaiton" class="submenu-item"><el-icon><Medal /></el-icon><span class="label">Request for Publication</span></RouterLink>
              <RouterLink v-if="canAccessMenu('Birthday Summary') || canAccessMenu('HR Reports')" to="/hr-reports/birthday-summary" class="submenu-item"><el-icon><Medal /></el-icon><span class="label">Birthday Summary</span></RouterLink>
             <RouterLink v-if="canAccessMenu('List of New Hires and Promotions') || canAccessMenu('HR Reports')" to="/hr-reports/newly-hired-and-promoted" class="submenu-item"><el-icon><Medal /></el-icon><span class="label">List of New Hires and Promotions</span></RouterLink>
            </div> 
          </el-collapse-transition>
        </details>
        </div> 

        <div
          v-if="showCertificatesNav"
          class="menu-section"
        >
          <p v-if="!collapsed" class="menu-section-label">Certificates</p>
          <details class="submenu" :open="true">
          <summary class="submenu-summary" :class="{ active: route.path.startsWith('/timekeeping-setup') }" @click.prevent="openTk = !openTk">
            <div class="menu-item" role="button" aria-expanded="openTk">
              <el-icon><Timer /></el-icon>
              <span class="label">Certificates</span>
            </div>
            <span v-if="!collapsed" class="chevron" :class="{ open: openTk }">▾</span>
          </summary>
          <el-collapse-transition>
            <div v-show="!collapsed && openTk" class="submenu-items">
              <RouterLink v-if="canAccessMenu('Employee Certificate') || canAccessMenu('Certificates')" to="/certificates/employee" class="submenu-item"><el-icon><Collection /></el-icon><span class="label">Employee Certificate</span></RouterLink>
              <RouterLink v-if="canAccessMenu('COS Certificate') || canAccessMenu('Certificates')" to="/certificates/cos-certificate" class="submenu-item"><el-icon><Collection /></el-icon><span class="label">COS Certificate</span></RouterLink>
              <RouterLink v-if="canAccessMenu('COS Contract') || canAccessMenu('Certificates')" to="/certificates/cos-contract" class="submenu-item"><el-icon><Collection /></el-icon><span class="label">COS Contract</span></RouterLink>
              <RouterLink v-if="canAccessMenu('Employee Certificate Of Compensation') || canAccessMenu('Certificates')" to="/certificates/compensation" class="submenu-item"><el-icon><Collection /></el-icon><span class="label">Employee Certificate Of Compensation</span></RouterLink>
              <RouterLink v-if="canAccessMenu('Medical Certificate') || canAccessMenu('Certificates')" to="/certificates/medical" class="submenu-item"><el-icon><Collection /></el-icon><span class="label">Medical Certificate</span></RouterLink>
              <RouterLink v-if="canAccessMenu('Service Record') || canAccessMenu('Certificates')" to="/certificates/service-record" class="submenu-item"><el-icon><Collection /></el-icon><span class="label">Service Record</span></RouterLink>
              <RouterLink v-if="canAccessMenu('Acceptance of Resignation') || canAccessMenu('Certificates')" to="/certificates/acceptance-resignation" class="submenu-item"><el-icon><Tickets /></el-icon><span class="label">Acceptance of Resignation</span></RouterLink>
              <RouterLink v-if="canAccessMenu('Acceptance of Retirement') || canAccessMenu('Certificates')" to="/certificates/acceptance-of-retirement" class="submenu-item"><el-icon><User /></el-icon><span class="label">Acceptance of Retirement</span></RouterLink>
              <RouterLink v-if="canAccessMenu('Certificate of Last Day of Service') || canAccessMenu('Certificates')" to="/certificates/last-day-service" class="submenu-item"><el-icon><Timer /></el-icon><span class="label">Certificate of Last Day of Service</span></RouterLink>
              <RouterLink v-if="canAccessMenu('No Pending Certificates') || canAccessMenu('Certificates')" to="/certificates/no-pending" class="submenu-item"><el-icon><Postcard /></el-icon><span class="label">No Pending Certificates</span></RouterLink>
              <RouterLink v-if="canAccessMenu('LBP ATM Request Certificate') || canAccessMenu('Certificates')" to="/certificates/atm-request" class="submenu-item"><el-icon><User /></el-icon><span class="label">LBP ATM Request Certificate</span></RouterLink>
              <RouterLink v-if="canAccessMenu('Certificate of Appearance') || canAccessMenu('Certificates')" to="/certificates/appearance" class="submenu-item"><el-icon><User /></el-icon><span class="label">Certificate of Appearance</span></RouterLink>
              <RouterLink v-if="canAccessMenu('Clearance Certificate') || canAccessMenu('Certificates')" to="/certificates/clearance-certificate" class="submenu-item"><el-icon><User /></el-icon><span class="label">Clearance Certificate</span></RouterLink>
                <!-- <RouterLink to="/certificates/ojt" class="submenu-item"><el-icon><User /></el-icon><span class="label">OJT Certificate</span></RouterLink> -->
              <RouterLink v-if="canAccessMenu('Transfer of Leave Credit') || canAccessMenu('Certificates')" to="/certificates/transfer-of-leave-credit" class="submenu-item"><el-icon><User /></el-icon><span class="label">Transfer of Leave Credit</span></RouterLink>
              <RouterLink v-if="canAccessMenu('Acceptance Letter Intern') || canAccessMenu('Certificates')" to="/certificates/acceptance-letter-intern" class="submenu-item"><el-icon><User /></el-icon><span class="label">Acceptance Letter Intern</span></RouterLink>
              <RouterLink v-if="canAccessMenu('Certificate of Completion') || canAccessMenu('Certificates')" to="/certificates/cert-of-completion" class="submenu-item"><el-icon><User /></el-icon><span class="label">Certificate of Completion</span></RouterLink>
              <RouterLink v-if="canAccessMenu('Certificate of Last Salary') || canAccessMenu('Certificates')" to="/certificates/cert-of-last-salary" class="submenu-item"><el-icon><User /></el-icon><span class="label">Certificate of Last Salary</span></RouterLink>
              <RouterLink v-if="canAccessMenu('Certificate of Salary Deduction') || canAccessMenu('Certificates')" to="/certificates/cert-of-salary-deduction" class="submenu-item"><el-icon><User /></el-icon><span class="label">Certificate of Salary Deduction</span></RouterLink>
              <RouterLink v-if="canAccessMenu('Certificate of Rendered Service') || canAccessMenu('Certificates')" to="/certificates/cert-of-rendered-service" class="submenu-item"><el-icon><User /></el-icon><span class="label">Certificate of Rendered Service</span></RouterLink>
                
            </div>
          </el-collapse-transition>
        </details>
        </div>

        <div
          v-if="showRecruitmentNav"
          class="menu-section"
        >
          <p v-if="!collapsed" class="menu-section-label">Hiring</p>
          <details class="submenu" :open="true">
          <summary class="submenu-summary" :class="{ active: route.path.startsWith('/recruitment') }" @click.prevent="openRecruitment = !openRecruitment">
            <div class="menu-item" role="button" aria-expanded="openRecruitment">
              <el-icon><Timer /></el-icon>
              <span class="label">Recruitment</span>
            </div>
            <span v-if="!collapsed" class="chevron" :class="{ open: openRecruitment }">▾</span>
          </summary>
          <el-collapse-transition>
            <div v-show="!collapsed && openRecruitment" class="submenu-items">
              <RouterLink v-if="canAccessMenu('Applicant Qualification') || canAccessMenu('Recruitment')" to="/recruitment/applicant-qualification" class="submenu-item"><el-icon><Collection /></el-icon><span class="label">Applicant Qualification</span></RouterLink>
              <RouterLink v-if="canAccessMenu('Applicant Shortlisting') || canAccessMenu('Recruitment')" to="/recruitment/applicant-shortlisting" class="submenu-item"><el-icon><Collection /></el-icon><span class="label">Applicant Shortlisting</span></RouterLink>
              <RouterLink v-if="canAccessMenu('Applicant Progress') || canAccessMenu('Recruitment')" to="/recruitment/applicants-records" class="submenu-item"><el-icon><Collection /></el-icon><span class="label">Applicant Progress</span></RouterLink>
              <RouterLink v-if="canAccessMenu('Examination Setup') || canAccessMenu('Recruitment')" to="/recruitment/examination" class="submenu-item"><el-icon><Collection /></el-icon><span class="label">Examination Setup</span></RouterLink>
              <RouterLink v-if="canAccessMenu('Interview Setup') || canAccessMenu('Recruitment')" to="/recruitment/panel-interview-setup" class="submenu-item"><el-icon><Collection /></el-icon><span class="label">Interview Setup</span></RouterLink>
              <RouterLink v-if="canAccessMenu('HRMPSB Deliberation') || canAccessMenu('Recruitment')" to="/recruitment/hrdd-perf-review" class="submenu-item"><el-icon><Collection /></el-icon><span class="label">HRMPSB Deliberation</span></RouterLink>
              <RouterLink v-if="canAccessMenu('Administrator Selection') || canAccessMenu('Recruitment')" to="/recruitment/administrator-selection" class="submenu-item"><el-icon><Collection /></el-icon><span class="label">Administrator Selection</span></RouterLink>
            </div>
          </el-collapse-transition>
        </details>
        </div>

        <div
          v-if="showRecruitmentReportsNav"
          class="menu-section"
        >
          <p v-if="!collapsed" class="menu-section-label">Reports</p>
          <details class="submenu" :open="true">
          <summary class="submenu-summary" :class="{ active: route.path.startsWith('/recruitment-reports') }" @click.prevent="openPayroll = !openPayroll">
            <div class="menu-item" role="button" aria-expanded="openPayroll">
              <el-icon><Money /></el-icon>
              <span class="label">Recruitment Reports</span>
            </div>
            <span v-if="!collapsed" class="chevron" :class="{ open: openPayroll }">▾</span>
          </summary>
          <el-collapse-transition>
            <div v-show="!collapsed && openPayroll" class="submenu-items">
              <RouterLink v-if="canAccessMenu('Appointment Certificate') || canAccessMenu('Recruitment Reports')" to="/recruitment-reports/appointment-cert" class="submenu-item"><el-icon><Collection /></el-icon><span class="label">Appointment Certificate</span></RouterLink>
              <RouterLink v-if="canAccessMenu('Assumption of Duty') || canAccessMenu('Recruitment Reports')" to="/recruitment-reports/assumption-of-duty" class="submenu-item"><el-icon><Collection /></el-icon><span class="label">Assumption of Duty</span></RouterLink>
              <RouterLink v-if="canAccessMenu('Oath of Office') || canAccessMenu('Recruitment Reports')" to="/recruitment-reports/oath-of-office" class="submenu-item"><el-icon><Collection /></el-icon><span class="label">Oath of Office</span></RouterLink>
              <RouterLink v-if="canAccessMenu('Acceptance Letter') || canAccessMenu('Recruitment Reports')" to="/recruitment-reports/acceptance-letter" class="submenu-item"><el-icon><Collection /></el-icon><span class="label">Acceptance Letter</span></RouterLink>
              <RouterLink v-if="canAccessMenu('Work Experience Sheet') || canAccessMenu('Recruitment Reports')" to="/recruitment-reports/work-experience-sheet" class="submenu-item"><el-icon><Collection /></el-icon><span class="label">Work Experience Sheet</span></RouterLink>
              <RouterLink v-if="canAccessMenu('Position Description') || canAccessMenu('Recruitment Reports')" to="/recruitment-reports/position-description" class="submenu-item"><el-icon><Collection /></el-icon><span class="label">Position Description </span></RouterLink>
              
            </div>
          </el-collapse-transition>


        </details>
        </div>
      </nav>
    </aside>
    <section class="content">
      <header class="content-header">
        <slot name="header">HR Module</slot>
      </header>
      <main>
        <slot/>
      </main>
    </section>
  </div>
</template>

<script setup>
import { ref, watchEffect, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { ElIcon, ElCollapseTransition, ElTooltip } from 'element-plus'
import { Menu, User, Timer, Setting, OfficeBuilding, Collection, Medal, Tickets, Postcard, Remove, Star, Document, TrendCharts, Notebook, Money, Fold, Expand } from '@element-plus/icons-vue'
import { authApi, accessRightsApi } from '@/services/api'
import { useCompany } from '@/composable/useCompany.js'

const { primaryCompany, fetchCompanies, getLogoUrl } = useCompany()

const showLogo = ref(true)
const companyName = computed(() => primaryCompany.value?.name || 'HR Module')
const companyLogoSrc = computed(() => getLogoUrl(primaryCompany.value))

function onLogoError() {
  showLogo.value = false
}

watch(primaryCompany, () => {
  showLogo.value = true
})

const route = useRoute()

const openHr = ref(false)
const openTk = ref(false)
const openPayroll = ref(false)
const openRecruitment = ref(false)
const HRreport = ref(false)
const collapsed = ref(false)

// Access-controlled menus (hide sidebar items based on DB "access" table)
const accessLoaded = ref(false)
const allowedMenuNameSet = ref(new Set())
const ACCESS_CACHE_KEY = 'hr_module_sidebar_access_v1'
const SIDEBAR_COLLAPSED_KEY = 'hr_module_sidebar_collapsed_v1'

const normalizeMenuName = (s) => String(s ?? '').toLowerCase().replace(/[^a-z0-9]/g, '')

const readSidebarCache = () => {
  try {
    const raw = localStorage.getItem(ACCESS_CACHE_KEY)
    if (!raw) return null
    const parsed = JSON.parse(raw)
    if (!Array.isArray(parsed?.menus)) return null
    return parsed
  } catch {
    return null
  }
}

const writeSidebarCache = (userId, menus) => {
  try {
    localStorage.setItem(
      ACCESS_CACHE_KEY,
      JSON.stringify({
        userId: userId ?? null,
        menus: Array.from(menus || []),
        updatedAt: Date.now()
      })
    )
  } catch {
    // no-op
  }
}

const canAccessMenu = (menuName) => {
  // Wait for access to load before showing any permission-gated UI.
  if (!accessLoaded.value) return false
  return allowedMenuNameSet.value.has(normalizeMenuName(menuName))
}

const canShowAny = (menuLabels) => menuLabels.some((label) => canAccessMenu(label))

const hrReportsMenuLabels = [
  'Personal Data Sheet',
  'NOSI',
  'NOSA',
  'Terminal Leave Endorsement',
  'Plantilla Report',
  'Request for Publication',
  'Birthday Summary'
]

const recruitmentMenuLabels = [
  'Applicant Qualification',
  'Applicant Shortlisting',
  'Applicant Progress',
  'Examination Setup',
  'Interview Setup',
  'HRMPSB Deliberation',
  'Administrator Selection'
]

const certificatesMenuLabels = [
  'Employee Certificate',
  'COS Certificate',
  'COS Contract',
  'Employee Certificate Of Compensation',
  'Medical Certificate',
  'Service Record',
  'Acceptance of Resignation',
  'Acceptance of Retirement',
  'Certificate of Last Day of Service',
  'No Pending Certificates',
  'LBP ATM Request Certificate',
  'Certificate of Appearance',
  'Clearance Certificate',
  'Transfer of Leave Credit',
  'Acceptance Letter Intern',
  'Certificate of Completion',
  'Certificate of Last Salary',
  'Certificate of Salary Deduction',
  'Certificate of Rendered Service'
]

const recruitmentReportsMenuLabels = [
  'Appointment Certificate',
  'Assumption of Duty',
  'Oath of Office',
  'Acceptance Letter',
  'Work Experience Sheet',
  'Position Description'
]

const cachedSidebar = readSidebarCache()
if (cachedSidebar?.menus?.length) {
  allowedMenuNameSet.value = new Set(cachedSidebar.menus)
  accessLoaded.value = true
}
try {
  const collapsedCached = localStorage.getItem(SIDEBAR_COLLAPSED_KEY)
  if (collapsedCached !== null) {
    collapsed.value = collapsedCached === '1'
  }
} catch {
  // no-op
}

watchEffect(() => {
  try {
    localStorage.setItem(SIDEBAR_COLLAPSED_KEY, collapsed.value ? '1' : '0')
  } catch {
    // no-op
  }
})

onMounted(async () => {
  fetchCompanies()

  try {
    const userResp = await authApi.getCurrentUser()
    const user = userResp?.data?.data?.user ?? userResp?.data?.user ?? userResp?.data
    const userId = user?.id

    if (!userId) return

    const withHrmAccess = Number(user?.with_hrm_access) === 1 || user?.with_hrm_access === true
    if (!withHrmAccess) {
      // User can see HR module only when with_hrm_access = 1
      allowedMenuNameSet.value = new Set()
      accessLoaded.value = true
      writeSidebarCache(userId, [])
      return
    }

    const rightsResp = await accessRightsApi.get(userId)
    const rights = rightsResp?.data?.data ?? rightsResp?.data ?? {}

    // Build allowed menu set from ALL menu arrays returned by backend.
    // This ensures Certificates/Recruitment Reports (which may belong to other module groups)
    // also reflect the user's access.menu_id entries correctly.
    const isEnabledStatus = (s) => {
      if (s === 1 || s === '1') return true
      if (s === true || s === 'true' || s === 'TRUE') return true
      // MSSQL bit might come as '0'/'1' or boolean; any numeric-like truthy value:
      return Number(s) === 1
    }

    const enabledMenus = [
      ...(rights?.hrm_menu || []),
      ...(rights?.hrt_menu || []),
      ...(rights?.hrp_menu || []),
      ...(rights?.cpm_menu || []),
      ...(rights?.ld_menu || []),
    ].filter((m) => isEnabledStatus(m?.status))

    allowedMenuNameSet.value = new Set(enabledMenus.map((m) => normalizeMenuName(m?.menu)))
    accessLoaded.value = true
    writeSidebarCache(userId, allowedMenuNameSet.value)
  } catch (e) {
    // If access rights fail, default to showing everything (current behavior).
    console.error('Failed to load access rights:', e?.response?.data || e?.message || e)
    // Keep cached menus (if present) to avoid sidebar flicker on refresh.
    if (!cachedSidebar?.menus?.length) accessLoaded.value = true
  }
})

const isHrActive = computed(() => route.path.startsWith('/hr-reports'))
const isHRreportActive = computed(() => route.path.startsWith('/hr-reports'))
const isRecruitmentActive = computed(() => route.path.startsWith('/recruitment-reports'))
const isRecruitmentMenuActive = computed(() => route.path.startsWith('/recruitment'))

function toggleHr() { openHr.value = !openHr.value }

// toggle via reactive route; no explicit handler needed here
watchEffect(() => { openHr.value = isHrActive.value })
watchEffect(() => { openTk.value = route.path.startsWith('/certificates') })
watchEffect(() => { openPayroll.value = isRecruitmentActive.value })
watchEffect(() => { openRecruitment.value = isRecruitmentMenuActive.value })
watchEffect(() => { HRreport.value = isHRreportActive.value })

/** Section wrappers: same visibility rules as the submenu blocks they contain */
const showHrReportsNav = computed(
  () => canAccessMenu('HR Reports') || canShowAny(hrReportsMenuLabels)
)
const showCertificatesNav = computed(
  () => canAccessMenu('Certificates') || canShowAny(certificatesMenuLabels)
)
const showRecruitmentNav = computed(
  () => canAccessMenu('Recruitment') || canShowAny(recruitmentMenuLabels)
)
const showRecruitmentReportsNav = computed(
  () => canAccessMenu('Recruitment Reports') || canShowAny(recruitmentReportsMenuLabels)
)
</script>

<style scoped>
.layout {
  display: grid;
  grid-template-columns: var(--sidebar-w, 260px) minmax(0, 1fr);
  min-height: 100vh;
  transition: grid-template-columns 0.28s cubic-bezier(0.4, 0, 0.2, 1);
  will-change: grid-template-columns;
}
.layout.collapsed {
  --sidebar-w: 76px;
}
.sidebar {
  background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
  border-right: 1px solid #e2e8f0;
  box-shadow: 2px 0 12px rgba(15, 23, 42, 0.04);
  padding: 1.25rem 1rem;
  position: sticky;
  top: 0;
  height: 100vh;
  overflow-x: hidden;
  overflow-y: auto;
  scrollbar-gutter: stable both-edges;
  transition: padding 0.28s cubic-bezier(0.4, 0, 0.2, 1);
}
.layout.collapsed .sidebar {
  padding: 1rem 0.5rem;
}
.brand { margin: 0 0 1rem 0; }
.brand-row {
  display: grid;
  grid-template-columns: auto 1fr auto;
  align-items: center;
  gap: 10px;
  margin-bottom: 14px;
  padding-bottom: 14px;
  border-bottom: 1px solid #f1f5f9;
  transition: grid-template-columns 0.28s ease, gap 0.28s ease;
}
.brand-logo {
  height: 56px;
  width: auto;
  max-width: 100%;
  border-radius: 10px;
  object-fit: contain;
  flex-shrink: 0;
  transition: height 0.28s ease;
}
.brand-text {
  line-height: 1.15;
  min-width: 0;
  padding-left: 4px;
  overflow: hidden;
  transition: opacity 0.22s ease, max-width 0.28s ease;
}
.brand-title {
  font-weight: 700;
  color: #0f172a;
  margin: 0;
  font-size: 0.95rem;
  letter-spacing: 0.02em;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  word-break: break-word;
}
.brand-subtitle { color: #64748b; font-size: 12px; margin-top: 2px; }
.collapse-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border: 1px solid #e2e8f0;
  background: #fff;
  border-radius: 10px;
  cursor: pointer;
  color: #475569;
  flex-shrink: 0;
  transition: background 0.2s ease, border-color 0.2s ease, color 0.2s ease, transform 0.2s ease;
}
.collapse-btn:hover {
  background: #f1f5f9;
  border-color: #cbd5e1;
  color: #0f172a;
}
.collapse-btn:active {
  transform: scale(0.96);
}
.menu {
  display: grid;
  gap: 0.2rem;
}
.menu-section {
  display: contents;
}
.menu-section-label {
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: #94a3b8;
  margin: 0;
  padding: 14px 0.7rem 6px;
  line-height: 1.2;
  grid-column: 1 / -1;
  border-top: 1px solid #eef2f7;
}
.menu-section-label--first {
  padding-top: 2px;
  border-top: none;
}
.layout.collapsed .menu-section-label {
  display: none;
}
.menu-item {
  color: #334155;
  text-decoration: none;
  padding: 0.55rem 0.7rem;
  border-radius: 10px;
  display: grid;
  grid-template-columns: 22px 1fr;
  align-items: center;
  column-gap: 10px;
  font-size: 13px;
  transition: background 0.18s ease, padding 0.28s ease, grid-template-columns 0.28s ease;
}
.menu-item.router-link-active {
  background: #e8eef6;
  color: #0f172a;
  font-weight: 600;
  box-shadow: inset 0 0 0 1px rgba(59, 130, 246, 0.12);
}
.submenu {
  margin-top: 4px;
  contain: layout paint;
}
.menu-item:hover { background: #f1f5f9; }
.submenu summary { list-style: none; }
.submenu-summary {
  list-style: none;
  display: grid;
  grid-template-columns: 1fr auto;
  align-items: center;
  border-radius: 10px;
  transition: background 0.18s ease;
}
.submenu-summary:hover {
  background: #f8fafc;
}
.submenu-summary.active {
  background: #e8eef6;
  box-shadow: inset 0 0 0 1px rgba(59, 130, 246, 0.1);
  padding: 2px 4px;
}
.chevron {
  color: #94a3b8;
  transition: transform 0.22s cubic-bezier(0.4, 0, 0.2, 1);
  font-size: 12px;
  padding-right: 4px;
}
.chevron.open { transform: rotate(180deg); }
.submenu-summary .menu-item {
  display: grid;
  grid-template-columns: 22px 1fr;
  align-items: center;
  column-gap: 10px;
  white-space: nowrap;
  min-width: 0;
}
.submenu-items {
  display: grid;
  gap: 2px;
  margin-left: 8px;
  padding: 6px 0 6px 12px;
  border-left: 2px solid #e2e8f0;
}
.submenu-item {
  color: #64748b;
  text-decoration: none;
  padding: 6px 8px;
  border-radius: 8px;
  display: grid;
  grid-template-columns: 20px 1fr;
  align-items: start;
  column-gap: 8px;
  font-size: 12px;
  transition: background 0.18s ease, color 0.18s ease;
}
.submenu-item:hover {
  background: #f8fafc;
  color: #334155;
}
.submenu-item.router-link-active {
  background: #e8eef6;
  color: #0f172a;
  font-weight: 600;
}
.content {
  background: #f8fafc;
  height: 100vh;
  overflow: auto;
  scrollbar-gutter: stable both-edges;
  min-width: 0;
}
.content-header {
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid #e2e8f0;
  background: #ffffff;
}
main { padding: 1.5rem; min-width: 0; }

/* Collapsed: icon rail */
.layout.collapsed .brand-row {
  grid-template-columns: 1fr;
  justify-items: center;
  gap: 8px;
}
.layout.collapsed .brand-logo {
  height: 44px;
}
.layout.collapsed .brand-text {
  display: none;
}
.layout.collapsed .collapse-btn {
  width: 100%;
  max-width: 44px;
}
.layout.collapsed .label {
  display: none;
}
.layout.collapsed .submenu-items {
  display: none !important;
}
.layout.collapsed .menu-item,
.layout.collapsed .submenu-summary .menu-item {
  grid-template-columns: 1fr;
  justify-items: center;
  padding-left: 0.35rem;
  padding-right: 0.35rem;
}
.layout.collapsed .submenu-summary {
  grid-template-columns: 1fr;
  justify-items: center;
}
.layout.collapsed .chevron {
  display: none;
}
.layout.collapsed .menu-item :deep(.el-icon),
.layout.collapsed .submenu-summary .menu-item :deep(.el-icon) {
  margin: 0;
}

.slide-fade-enter-active,
.slide-fade-leave-active {
  transition: opacity 0.22s ease, transform 0.22s cubic-bezier(0.4, 0, 0.2, 1);
}
.slide-fade-enter-from,
.slide-fade-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}
</style>


