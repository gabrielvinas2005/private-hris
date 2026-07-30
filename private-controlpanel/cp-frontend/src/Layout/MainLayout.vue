<template>
  <div class="layout" :class="{ collapsed }">
    <aside class="sidebar">
      <div class="brand-row">
        <div class="brand-section">
          <div class="brand-logo" :title="collapsed ? 'Expand' : ''" :class="{ clickable: collapsed }" @click="collapsed && (collapsed = false)">
            <img v-if="showLogo && companyLogoSrc" :src="companyLogoSrc" :alt="companyName" class="brand-img" @error="onLogoError" />
          </div>
          <transition name="slide-fade">
            <div v-if="!collapsed" class="brand-text">
              <h4 class="brand" :title="companyName">{{ companyName }}</h4>
              <span class="brand-subtitle">Control Panel</span>
            </div>
          </transition>
        </div>
        <button v-if="!collapsed" class="collapse-btn" @click="collapsed = !collapsed" :title="'Collapse'">⟨⟩</button>
      </div>

      <transition-group name="slide-fade" tag="nav" class="menu">
        <RouterLink to="/" class="menu-item" key="cp">
          <el-icon><Menu /></el-icon>
          <span class="label">Control Panel</span>
        </RouterLink>
        <RouterLink to="/users" class="menu-item" key="users">
          <el-icon><User /></el-icon>
          <span class="label">User List</span>
        </RouterLink>
        <RouterLink to="/activity" class="menu-item" key="activity">
          <el-icon><Timer /></el-icon>
          <span class="label">User Activities</span>
        </RouterLink>
        <details class="submenu" :open="true" key="hr">
          <summary class="submenu-summary" :class="{ active: isHrActive }" @click.prevent="toggleHr">
            <div class="menu-item" role="button" aria-expanded="openHr">
              <el-icon><Setting /></el-icon>
              <span class="label">HR Setup</span>
            </div>
            <span v-if="!collapsed" class="chevron" :class="{ open: openHr }">▾</span>
          </summary>
          <el-collapse-transition>
            <div v-show="!collapsed && openHr" class="submenu-items">
              <RouterLink to="/hr-setup/company" class="submenu-item"><el-icon><OfficeBuilding /></el-icon><span class="label">Company</span></RouterLink>
              <RouterLink to="/hr-setup/branch" class="submenu-item"><el-icon><OfficeBuilding /></el-icon><span class="label">Branch</span></RouterLink>
              <RouterLink to="/hr-setup/office" class="submenu-item"><el-icon><OfficeBuilding /></el-icon><span class="label">Department</span></RouterLink>
              <RouterLink to="/hr-setup/division" class="submenu-item"><el-icon><Collection /></el-icon><span class="label">Division</span></RouterLink>
              <RouterLink to="/hr-setup/section" class="submenu-item"><el-icon><Collection /></el-icon><span class="label">Section</span></RouterLink>
              <RouterLink to="/hr-setup/eligibility" class="submenu-item"><el-icon><Medal /></el-icon><span class="label">Eligibility</span></RouterLink>
              <RouterLink to="/hr-setup/employment-type" class="submenu-item"><el-icon><Tickets /></el-icon><span class="label">Employment Type</span></RouterLink>
              <RouterLink to="/hr-setup/specialization" class="submenu-item"><el-icon><Collection /></el-icon><span class="label">Specialization</span></RouterLink>
              <RouterLink to="/hr-setup/position" class="submenu-item"><el-icon><Postcard /></el-icon><span class="label">Position</span></RouterLink>
              <RouterLink to="/hr-setup/plantila" class="submenu-item"><el-icon><Collection /></el-icon><span class="label">Plantilla</span></RouterLink>
              <RouterLink to="/hr-setup/non-plantila" class="submenu-item"><el-icon><Collection /></el-icon><span class="label">Non-Plantilla</span></RouterLink>
              <RouterLink to="/hr-setup/promotion-types" class="submenu-item"><el-icon><Collection /></el-icon><span class="label">Promotion Types</span></RouterLink>
              <RouterLink to="/hr-setup/off-boarding-types" class="submenu-item"><el-icon><Remove /></el-icon><span class="label">Off Boarding Types</span></RouterLink>
              <RouterLink to="/hr-setup/ipcr-ratings" class="submenu-item"><el-icon><Star /></el-icon><span class="label">IPCR Ratings</span></RouterLink>
              <RouterLink to="/hr-setup/document-no" class="submenu-item"><el-icon><Document /></el-icon><span class="label">Document No.</span></RouterLink>
              <RouterLink to="/hr-setup/document-type" class="submenu-item"><el-icon><Document /></el-icon><span class="label">Document Type</span></RouterLink>
              <RouterLink to="/hr-setup/semester-rating" class="submenu-item"><el-icon><Collection /></el-icon><span class="label">Semester Rating</span></RouterLink>
              <RouterLink to="/hr-setup/competencies" class="submenu-item"><el-icon><Collection /></el-icon><span class="label">Competencies</span></RouterLink>
              <RouterLink to="/hr-setup/eete-rating" class="submenu-item"><el-icon><TrendCharts /></el-icon><span class="label">EETE Rating</span></RouterLink>
              <RouterLink to="/hr-setup/exam-category" class="submenu-item"><el-icon><Notebook /></el-icon><span class="label">Exam Category</span></RouterLink>
              <RouterLink to="/hr-setup/pmt" class="submenu-item"><el-icon><UserFilled /></el-icon><span class="label">PMT</span></RouterLink>
              <RouterLink to="/hr-setup/downloadable-docs" class="submenu-item"><el-icon><Document /></el-icon><span class="label">Downloadable Docs</span></RouterLink>
              <RouterLink to="/hr-setup/interview-setup" class="submenu-item"><el-icon><Document /></el-icon><span class="label">Interview Setup</span></RouterLink>
              <RouterLink to="/hr-setup/applicant-documents" class="submenu-item"><el-icon><Document /></el-icon><span class="label">Applicant Docs</span></RouterLink>
            </div>
          </el-collapse-transition>
        </details>
        <details class="submenu" :open="true" key="timekeeping">
          <summary class="submenu-summary" :class="{ active: route.path.startsWith('/timekeeping-setup') }" @click.prevent="openTk = !openTk">
            <div class="menu-item" role="button" aria-expanded="openTk">
              <el-icon><Timer /></el-icon>
              <span class="label">Time Keeping Setup</span>
            </div>
            <span v-if="!collapsed" class="chevron" :class="{ open: openTk }">▾</span>
          </summary>
          <el-collapse-transition>
            <div v-show="!collapsed && openTk" class="submenu-items">
              <RouterLink to="/timekeeping-setup/overtime-types" class="submenu-item"><el-icon><Collection /></el-icon><span class="label">Overtime Types</span></RouterLink>
              <RouterLink to="/timekeeping-setup/holiday-types" class="submenu-item"><el-icon><Collection /></el-icon><span class="label">Holiday Types</span></RouterLink>
              <RouterLink to="/timekeeping-setup/holidays" class="submenu-item"><el-icon><Collection /></el-icon><span class="label">Holidays</span></RouterLink>
              <RouterLink to="/timekeeping-setup/leave-types" class="submenu-item"><el-icon><Collection /></el-icon><span class="label">Leave Types</span></RouterLink>
              <RouterLink to="/timekeeping-setup/official-business-types" class="submenu-item"><el-icon><Tickets /></el-icon><span class="label">Official Business Types</span></RouterLink>
              <RouterLink to="/timekeeping-setup/time-keeping" class="submenu-item"><el-icon><Timer /></el-icon><span class="label">Time Keeping Setup</span></RouterLink>
              <RouterLink to="/timekeeping-setup/approvers" class="submenu-item"><el-icon><User /></el-icon><span class="label">Approvers Setup</span></RouterLink>
            </div>
          </el-collapse-transition>
        </details>
       
        <details class="submenu" :open="true" key="payroll">
          <summary class="submenu-summary" :class="{ active: route.path.startsWith('/payroll-setup') }" @click.prevent="openPayroll = !openPayroll">
            <div class="menu-item" role="button" aria-expanded="openPayroll">
              <el-icon><Money /></el-icon>
              <span class="label">Payroll Setup</span>
            </div>
            <span v-if="!collapsed" class="chevron" :class="{ open: openPayroll }">▾</span>
          </summary>
          <el-collapse-transition>
            <div v-show="!collapsed && openPayroll" class="submenu-items">
              <RouterLink to="/payroll-setup/salary-schedule-setup" class="submenu-item"><el-icon><Collection /></el-icon><span class="label">Salary Schedule Setup</span></RouterLink>
              <RouterLink to="/payroll-setup/tax-table-setup" class="submenu-item"><el-icon><Collection /></el-icon><span class="label">Tax Table Setup</span></RouterLink>
              <RouterLink to="/payroll-setup/HDMF Table Setup" class="submenu-item"><el-icon><Collection /></el-icon><span class="label">HDMF Table Setup</span></RouterLink>
              <RouterLink to="/payroll-setup/Philhealth Table Setup" class="submenu-item"><el-icon><Collection /></el-icon><span class="label">Philhealth Table Setup</span></RouterLink>
              <RouterLink to="/payroll-setup/GSIS Table Setup" class="submenu-item"><el-icon><Tickets /></el-icon><span class="label">GSIS Table Setup</span></RouterLink>
              <RouterLink to="/payroll-setup/Salary Step Setup" class="submenu-item"><el-icon><Timer /></el-icon><span class="label">Salary Step Setup</span></RouterLink>
              <RouterLink to="/payroll-setup/salary-grade-setup" class="submenu-item"><el-icon><Postcard /></el-icon><span class="label">Salary Grade Setup</span></RouterLink>
              <RouterLink to="/payroll-setup/income-setup" class="submenu-item"><el-icon><User /></el-icon><span class="label">Income Setup</span></RouterLink>
              <RouterLink to="/payroll-setup/deduction-setup" class="submenu-item"><el-icon><User /></el-icon><span class="label">Deduction Setup</span></RouterLink>
              <RouterLink to="/payroll-setup/deduction-priority-setup" class="submenu-item"><el-icon><User /></el-icon><span class="label">Deduction Priority Setup</span></RouterLink>
              <RouterLink to="/payroll-setup/payroll-interval-setup" class="submenu-item"><el-icon><User /></el-icon><span class="label">Payroll Interval Setup</span></RouterLink>
              <RouterLink to="/payroll-setup/payroll-cutoff-setup" class="submenu-item"><el-icon><User /></el-icon><span class="label">Payroll Cut-off Setup</span></RouterLink>
              <RouterLink to="/payroll-setup/loyalty-award-setup" class="submenu-item"><el-icon><User /></el-icon><span class="label">Loyalty Award Setup</span></RouterLink>
              <RouterLink to="/payroll-setup/uniform-and-clothing-allowance-setup" class="submenu-item"><el-icon><User /></el-icon><span class="label">Uniform and Clothing Allowance Setup</span></RouterLink>
              <RouterLink to="/payroll-setup/rata-positions-setup" class="submenu-item"><el-icon><User /></el-icon><span class="label">RATA Positions Setup</span></RouterLink>
              <RouterLink to="/payroll-setup/rata-table-setup" class="submenu-item"><el-icon><User /></el-icon><span class="label">RATA Table Setup</span></RouterLink>
              <RouterLink to="/payroll-setup/hazard-pay-setup" class="submenu-item"><el-icon><User /></el-icon><span class="label">Hazard Pay Setup</span></RouterLink>
              <RouterLink to="/payroll-setup/overtime-tax-table-setup" class="submenu-item"><el-icon><User /></el-icon><span class="label">Overtime Tax Table Setup</span></RouterLink>
              <RouterLink to="/payroll-setup/mid-year-bonus-table-setup" class="submenu-item"><el-icon><User /></el-icon><span class="label">Mid Year Bonus Table Setup</span></RouterLink>
              <RouterLink to="/payroll-setup/year-end-bonus-table-setup" class="submenu-item"><el-icon><User /></el-icon><span class="label">Year End Bonus Table Setup</span></RouterLink>
              <RouterLink to="/payroll-setup/cash-gift-table-setup" class="submenu-item"><el-icon><User /></el-icon><span class="label">Cash Gift Table Setup</span></RouterLink>
              <RouterLink to="/payroll-setup/monetization-setup" class="submenu-item"><el-icon><User /></el-icon><span class="label">Monetization Setup</span></RouterLink>
            </div>
          </el-collapse-transition>


        </details>
      </transition-group>
      
    </aside>
    <section class="content">
      <header class="content-header">
        <slot name="header">Control Panel</slot>
      </header>
      <main>
        <slot />
      </main>
    </section>
  </div>
</template>

<script setup>
import { ref, watchEffect, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { ElIcon, ElCollapseTransition } from 'element-plus'
import { Menu, User, Timer, Setting, OfficeBuilding, Collection, Medal, Tickets, Postcard, Remove, Star, Document, TrendCharts, Notebook, Money, UserFilled } from '@element-plus/icons-vue'
import { useCompany } from '../composables/useCompany.js'

const { primaryCompany, fetchCompanies, getLogoUrl } = useCompany()

const showLogo = ref(true)
const companyName = computed(() => primaryCompany.value?.name?.trim() || 'Control Panel')
const companyLogoSrc = computed(() => getLogoUrl(primaryCompany.value))

function onLogoError() {
  showLogo.value = false
}

watch(primaryCompany, () => {
  showLogo.value = true
})

onMounted(() => {
  fetchCompanies()
})

const route = useRoute()
const openHr = ref(false)
const openTk = ref(false)
const openPayroll = ref(false)
const collapsed = ref(false)
const isHrActive = computed(() => route.path.startsWith('/hr-setup'))
const isPayrollActive = computed(() => route.path.startsWith('/payroll-setup'))
function toggleHr() { openHr.value = !openHr.value }
function togglePayroll() { openPayroll.value = !openPayroll.value }
watchEffect(() => { openHr.value = isHrActive.value })
watchEffect(() => { openTk.value = route.path.startsWith('/timekeeping-setup') })
watchEffect(() => { openPayroll.value = isPayrollActive.value })
</script>

<style scoped>
.layout {
  display: grid;
  grid-template-columns: 260px 1fr;
  min-height: 100vh;
  transition: grid-template-columns .25s ease; /* animate open/close */
}
.layout.collapsed { grid-template-columns: 72px 1fr; }
.sidebar {
  background: #ffffff;
  border-right: 1px dashed #e6e6e6;
  padding: 1.25rem;
  position: sticky;
  top: 0;
  height: 100vh;
  overflow: auto;
  scrollbar-gutter: stable both-edges;
  transition: padding .25s ease; /* subtle padding animation */
}
.layout.collapsed .sidebar { padding: 0.75rem; }
.brand-row { 
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  margin-bottom: 1.5rem;
}
.brand-section {
  display: flex;
  align-items: center;
  gap: 10px;
  flex: 1;
  min-width: 0;
}
/* Align logo with other icons when collapsed */
.layout.collapsed .brand-row { justify-content: center; }
.layout.collapsed .brand-section { gap: 0; flex: none; }
/* Logo container controls the shape */
.brand-logo {
  flex-shrink: 0;
  width: 48px;
  height: 48px;
  border-radius: 10px;
  overflow: hidden;
  display: grid;
  place-items: center;
  box-shadow: 0 1px 4px rgba(0,0,0,.06);
  background: #f8fafc;
}
.brand-img { width: 100%; height: 100%; object-fit: contain; display: block; }
/* Collapsed: perfect circle */
.layout.collapsed .brand-logo { width: 40px; height: 40px; border-radius: 50%; }
.brand-logo.clickable { cursor: pointer; }
.brand-text {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
  flex: 1;
}
.brand {
  margin: 0;
  font-size: 13px;
  font-weight: 700;
  color: #1f2937;
  line-height: 1.3;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  word-break: break-word;
}
.brand-subtitle {
  font-size: 11px;
  color: #6b7280;
  font-weight: 500;
  line-height: 1;
  white-space: nowrap;
}
/* Buttons */
.collapse-btn { flex-shrink: 0; border: 1px solid #e5e7eb; background: #fff; border-radius: 8px; cursor: pointer; padding: 2px 6px; }
/* removed separate expand button; logo acts as expand */
.menu { display:grid; gap: .35rem; margin-top: .5rem; }
.layout.collapsed .menu { display:flex; flex-direction:column; align-items:center; gap:.35rem; margin-top: .25rem; }
.menu-item {
  color: #334155;
  text-decoration: none;
  padding: 0.5rem 0.75rem;
  border-radius: 8px;
  display: grid;
  grid-auto-flow: column;
  align-items: center;
  gap: 8px;
}
.layout.collapsed .menu-item {
  padding: 0.75rem;
  justify-content: center;
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
}
.layout.collapsed .menu-item .el-icon {
  font-size: 20px;
  margin: 0;
}
.menu-item.router-link-active {
  background: #eef2f7;
  font-weight: 600;
}
.submenu { margin-top: 4px; }
.layout.collapsed .submenu {
  display: flex;
  justify-content: center;
  align-items: center;
  margin-top: 4px;
}
.menu-item:hover { background: #f8fafc; }
.layout.collapsed .menu-item:hover { transform: scale(1.06); }
.submenu summary { list-style: none; }
.submenu-summary { list-style: none; display: grid; grid-template-columns: 1fr auto; align-items: center; }
.layout.collapsed .submenu-summary {
  display: flex;
  justify-content: center;
  align-items: center;
}
.submenu-summary.active { background: #eef2f7; border-radius: 8px; padding: 4px 6px; }
.submenu { contain: layout paint; }
.chevron { color: #94a3b8; transition: transform .15s ease; }
.chevron.open { transform: rotate(180deg); }
.submenu-summary .menu-item { display: grid; grid-auto-flow: column; align-items: center; gap: 8px; white-space: nowrap; }
.submenu-items { display: grid; gap: 2px; margin-left: 10px; padding-left: 10px; border-left: 1px dashed #e6e6e6; }
.submenu-item { color: #64748b; text-decoration: none; padding: 4px 6px; border-radius: 6px; display: grid; grid-auto-flow: column; align-items: center; gap: 8px; }
.layout.collapsed .submenu-items {
  display: none;
}
.layout.collapsed .submenu-summary .menu-item {
  padding: 0.75rem;
  justify-content: center;
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
}
.layout.collapsed .submenu-summary .menu-item .el-icon {
  font-size: 20px;
  margin: 0;
}
.layout.collapsed .chevron {
  display: none;
}
.submenu-item.router-link-active { background: #eef2f7; }
.content {
  background: #f8fafc;
  height: 100vh;
  overflow: auto;
  scrollbar-gutter: stable both-edges;
}
.content-header {
  padding: 1.25rem 1.5rem;
  border-bottom: 1px dashed #e6e6e6;
  background: #ffffff;
}
main { padding: 1.5rem; }
.layout.collapsed .label { display: none; }
.layout.collapsed .submenu-items { display: none; }
.slide-fade-enter-active, .slide-fade-leave-active { transition: all .25s ease; }
.slide-fade-enter-from, .slide-fade-leave-to { opacity: 0; transform: translateY(-6px); }
.menu-item, .submenu-item { transition: background .2s ease, transform .15s ease; }
</style>


