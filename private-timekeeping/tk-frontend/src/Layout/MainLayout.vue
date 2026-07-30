<template>
  <div class="layout" :class="{ collapsed }">
    <aside class="sidebar">
      <div class="brand-row">
        <img
          v-if="showLogo && companyLogoSrc"
          class="brand-logo"
          :src="companyLogoSrc"
          :alt="companyName"
          @error="onLogoError"
        />
        <div class="brand-text" style="padding-left: 10px;">
          <h4 class="brand-title" :title="companyName">{{ companyName }}</h4>
          <div class="brand-subtitle">Timekeeping Module</div>
        </div>
        <button class="collapse-btn" @click="collapsed = !collapsed" :title="collapsed ? 'Expand' : 'Collapse'">⟨⟩</button>
      </div>
      <RouterLink to="/" class="menu-item" :class="{ active: $route.path === '/' }">
          <el-icon><Grid /></el-icon>
          <span class="label">Timekeeping</span>
        </RouterLink>
      <nav class="menu">
        <RouterLink v-if="can('Fix Schedule')" to="/fix-schedule" class="menu-item">
          <el-icon><Timer /></el-icon>
          <span class="label">Fix Schedule</span>
        </RouterLink>
        <RouterLink v-if="can('Shifting Schedule')" to="/shifting-schedule" class="menu-item">
          <el-icon><User /></el-icon>
          <span class="label">Shifting Schedule</span>
        </RouterLink>
        <RouterLink v-if="can('Assign Fix Schedule')" to="/assign-fix-schedule" class="menu-item">
          <el-icon><Timer /></el-icon>
          <span class="label">Assign Fix Schedule</span>
        </RouterLink>
        <RouterLink v-if="can('Leave Credits')" to="/leave-credits" class="menu-item">
          <el-icon><Timer /></el-icon>
          <span class="label">Leave Credits</span>
        </RouterLink>
        <!-- Hidden - redundant with Leave Credit Card Monitoring
        <RouterLink to="/leave-taken-monitoring" class="menu-item">
          <el-icon><Timer /></el-icon>
          <span class="label">Leave Taken Monitoring</span>
        </RouterLink>
        -->
        <RouterLink v-if="can('Leave Credit Monitoring')" to="/leave-credit-monitoring" class="menu-item">
          <el-icon><Monitor /></el-icon>
          <span class="label">Leave Credit Monitoring</span>
        </RouterLink>
        <RouterLink v-if="can('Leave Monitoring')" to="/leave-monitoring" class="menu-item">
          <el-icon><Monitor /></el-icon>
          <span class="label">Leave Monitoring</span>
        </RouterLink>
        <RouterLink v-if="can('OB Monitoring')" to="/ob-monitoring" class="menu-item">
          <el-icon><Monitor /></el-icon>
          <span class="label">OB Monitoring</span>
        </RouterLink>
        <RouterLink v-if="can('Pass Slip Monitoring')" to="/pass-slip-monitoring" class="menu-item">
          <el-icon><Monitor /></el-icon>
          <span class="label">Pass Slip Monitoring</span>
        </RouterLink>
        <RouterLink v-if="can('OT Monitoring')" to="/ot-monitoring" class="menu-item">
          <el-icon><Monitor /></el-icon>
          <span class="label">OT Monitoring</span>
        </RouterLink>
        <RouterLink v-if="can('COC Monitoring')" to="/coc-monitoring" class="menu-item">
          <el-icon><Monitor /></el-icon>
          <span class="label">COC Monitoring</span>
        </RouterLink>
        <RouterLink v-if="can('Work Suspension')" to="/work-suspension" class="menu-item">
          <el-icon><Remove /></el-icon>
          <span class="label">Work Suspension</span>
        </RouterLink>
        <RouterLink v-if="can('Biometrics Data')" to="/biometrics-data" class="menu-item">
          <el-icon><Timer /></el-icon>
          <span class="label">Biometrics Data</span>
        </RouterLink>
        <RouterLink v-if="can('Process Attendance')" to="/process-attendance" class="menu-item">
          <el-icon><Timer /></el-icon>
          <span class="label">Process Attendance</span>
        </RouterLink>
        
        <details v-if="hasReports" class="submenu" :open="true">
          <summary class="submenu-summary" :class="{ active: isReportsActive }" @click.prevent="toggleReports">
            <div class="menu-item" role="button" aria-expanded="openReports">
              <el-icon><Setting /></el-icon>
              <span class="label">Time Keeping Reports</span>
            </div>
            <span v-if="!collapsed" class="chevron" :class="{ open: openReports }">▾</span>
          </summary>
          <el-collapse-transition>
            <div v-show="!collapsed && openReports" class="submenu-items">
              <RouterLink v-for="item in reportItems" :key="item.path" :to="item.path" class="submenu-item">
                <el-icon><OfficeBuilding /></el-icon>
                <span class="label">{{ formatReportLabel(item.name) }}</span>
              </RouterLink>
            </div>
          </el-collapse-transition>
        </details>
       
      
      </nav>
    </aside>
    <section class="content">
      <header class="content-header">
        <div class="header-content">
          <slot name="header">Timekeeping</slot>
        </div>
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
import { Grid, Menu, User, Timer, Setting, OfficeBuilding, Collection, Medal, Tickets, Postcard, Remove, Star, Document, TrendCharts, Notebook, Money, Monitor } from '@element-plus/icons-vue'
import { authApi } from '@/services/api'
import { useAuth } from '@/Composables/useAuth'
import { useCompany } from '@/Composables/useCompany.js'

const { primaryCompany, fetchCompanies, getLogoUrl } = useCompany()
const showLogo = ref(true)
const companyName = computed(() => primaryCompany.value?.name?.trim() || 'HRMP')
const companyLogoSrc = computed(() => getLogoUrl(primaryCompany.value))

function onLogoError() {
  showLogo.value = false
}

watch(primaryCompany, () => {
  showLogo.value = true
})

// Component state
const route = useRoute()
const openReports = ref(false)
const collapsed = ref(false)
const isReportsActive = computed(() => route.path.startsWith('/tardiness-reports'))
const allowed = ref(new Set())

function can(name) {
  return allowed.value.has(String(name).trim().toLowerCase())
}
function canAny(names = []) {
  return names.some(n => can(n))
}
const hasReports = computed(() => {
  for (const n of allowed.value) {
    const s = String(n)
    if (s.includes('report') || s.includes('tardiness')) return true
  }
  return canAny(['Time Keeping Reports', 'Tardiness Reports', 'Tardiness Report'])
})

// Map report menu names to routes (keys must be lowercase)
const reportRouteMap = {
  'tardiness reports': '/tardiness-reports',
  'tardiness report': '/tardiness-reports',
}

const reportItems = computed(() => {
  const items = []
  let hasTimeKeepingReports = false
  for (const name of allowed.value) {
    const key = String(name).toLowerCase()
    const path = reportRouteMap[key]
    if (path) {
      items.push({ name: key, path })
    }
    // Heuristic: if any allowed item mentions 'tardiness', expose the tardiness reports tab
    if (!path && key.includes('tardiness')) {
      items.push({ name: 'tardiness reports', path: '/tardiness-reports' })
    }
    if (key.includes('time keeping reports') || key === 'time keeping reports') {
      hasTimeKeepingReports = true
    }
  }
  // If user has generic "Time Keeping Reports" access, show Tardiness Reports by default
  if (hasTimeKeepingReports) {
    items.push({ name: 'tardiness reports', path: '/tardiness-reports' })
  }
  // de-duplicate by path
  const seen = new Set()
  return items.filter(i => (seen.has(i.path) ? false : (seen.add(i.path), true)))
})

onMounted(async () => {
  fetchCompanies()
  try {
    const { getCurrentUser } = useAuth()
    const current = await getCurrentUser()
    if (current?.id) {
      // 1) Try cached permissions first for instant display
      const cacheRaw = localStorage.getItem('tk_allowed_menus')
      if (cacheRaw) {
        try {
          const cache = JSON.parse(cacheRaw)
          if (String(cache.userId) === String(current.id) && Array.isArray(cache.names)) {
            allowed.value = new Set(cache.names)
            // Do not return; continue to refresh in background to pick up new grants
          }
        } catch (_) {}
      }
      // 2) Fetch latest then cache (also updates UI if changed)
      const res = await authApi.getAccessRights(current.id, { visible_only: 1 })
      const payload = res?.data ?? res
      const tkMenus = payload?.hrp_menu || []
      const names = tkMenus.map(m => m.menu).filter(Boolean).map(n => String(n).trim().toLowerCase())
      allowed.value = new Set(names)
      try { localStorage.setItem('tk_allowed_menus', JSON.stringify({ userId: current.id, names })) } catch (_) {}
    }
  } catch (e) {
    // fallback: show nothing rather than everything
    allowed.value = new Set()
  }
})

// Methods
function toggleReports() { 
  openReports.value = !openReports.value 
}

// toggle via reactive route; no explicit handler needed here
watchEffect(() => { openReports.value = isReportsActive.value })

function formatReportLabel(name) {
  // Title case and normalize spacing
  return String(name).replace(/\s+/g, ' ').trim().replace(/\b\w/g, c => c.toUpperCase())
}
</script>

<style scoped>
.layout {
  display: grid;
  grid-template-columns: 260px 1fr;
  min-height: 100vh;
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
  display: flex;
  flex-direction: column;
}
.brand { margin: 0 0 1rem 0; }
.brand-row { display: grid; grid-template-columns: auto 1fr auto; align-items: center; gap: 10px; margin-bottom: 12px; }
.brand-logo { height: 72px; width: auto; min-width: 40px; flex-shrink: 0; border-radius: 10px; object-fit: contain; }
.brand-text { line-height: 1.1; }
.brand-title { font-weight: 700; color: #0f172a; }
.brand-subtitle { color: #94a3b8; font-size: 12px; }
.collapse-btn { border: 1px solid #e5e7eb; background: #fff; border-radius: 8px; cursor: pointer; padding: 2px 6px; }
.menu {
  display: grid;
  gap: 0.25rem;
}
.menu-item {
  color: #334155;
  text-decoration: none;
  padding: 0.5rem 0.75rem;
  border-radius: 8px;
  display: grid;
  grid-template-columns: 20px 1fr;
  align-items: center;
  column-gap: 8px;
}
.menu-item.router-link-active {
  background: #eef2f7;
  font-weight: 600;
}
.submenu { margin-top: 4px; }
.menu-item:hover { background: #f8fafc; }
.submenu summary { list-style: none; }
.submenu-summary { list-style: none; display: grid; grid-template-columns: 1fr auto; align-items: center; }
.submenu-summary.active { background: #eef2f7; border-radius: 8px; padding: 4px 6px; }
.submenu { contain: layout paint; }
.chevron { color: #94a3b8; transition: transform .15s ease; }
.chevron.open { transform: rotate(180deg); }
.submenu-summary .menu-item { display: grid; grid-auto-flow: column; align-items: center; gap: 8px; white-space: nowrap; }
.submenu-items { display: grid; gap: 2px; margin-left: 10px; padding-left: 12px; border-left: 1px dashed #e6e6e6; }
.submenu-item { color: #64748b; text-decoration: none; padding: 4px 6px; border-radius: 6px; display: grid; grid-template-columns: 20px 1fr; align-items: start; column-gap: 8px; }
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

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

main { padding: 1.5rem; }
.layout.collapsed .label { display: none; }
.layout.collapsed .submenu-items { display: none; }
.layout.collapsed .brand-text { display: none; }

.slide-fade-enter-active, .slide-fade-leave-active { transition: all .25s ease; }
.slide-fade-enter-from, .slide-fade-leave-to { opacity: 0; transform: translateY(-6px); }
</style>


