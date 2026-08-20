<template>
  <div class="flex min-h-screen bg-[#F3F5FA] text-slate-900 font-sans antialiased">
    <!-- Sidebar Navigation -->
    <aside :class="[
      collapsed ? 'w-20' : 'w-64 sm:w-72',
      'bg-white text-slate-700 border-slate-200/70 shadow-xl shadow-slate-200/50',
      'h-screen sticky top-0 z-40 flex flex-col transition-all duration-300 ease-in-out border-r overflow-y-auto'
    ]">
      <!-- Sidebar Header -->
      <div class="px-6 py-8 border-b border-slate-200/70">
        <div class="flex flex-col items-center text-center space-y-3">
          <div v-if="showLogo && companyLogoSrc" class="flex items-center justify-center overflow-hidden w-12 h-12 rounded-2xl bg-slate-100 border border-slate-200/70">
            <img :src="companyLogoSrc" :alt="companyName" class="object-contain w-full h-full" @error="onLogoError" />
          </div>
          <div v-else class="flex items-center justify-center w-12 h-12 rounded-2xl bg-gradient-to-br from-[#4A6CFB] to-[#22308F] text-white font-bold text-lg tracking-tight shadow-lg shadow-[#3B5EFF]/30">
            TK
          </div>
          <div v-if="!collapsed" class="min-w-0 w-full">
            <h1 class="text-base font-bold tracking-tight text-slate-900 truncate">{{ companyName }}</h1>
            <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 truncate mt-0.5">Timekeeping Module</p>
          </div>
        </div>
      </div>

      <!-- Navigation Menu -->
      <nav class="flex-1 px-3.5 py-5 space-y-1 overflow-y-auto sidebar-scroll">
        <!-- Dashboard Link -->
        <RouterLink
          to="/"
          class="flex items-center py-2.5 space-x-3 text-[13.5px] font-medium transition-all duration-200 rounded-xl group"
          :class="[
            collapsed ? 'justify-center px-0' : 'px-3.5',
            route.path === '/'
              ? 'bg-gradient-to-r from-[#3B5EFF] to-[#2946D9] text-white font-semibold shadow-lg shadow-[#3B5EFF]/25'
              : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'
          ]"
        >
          <el-icon class="text-lg flex-shrink-0"><Grid /></el-icon>
          <span v-if="!collapsed">Timekeeping</span>
        </RouterLink>

        <!-- Menu Links -->
        <RouterLink
          v-if="can('Fix Schedule')"
          to="/fix-schedule"
          class="flex items-center py-2.5 space-x-3 text-[13.5px] font-medium transition-all duration-200 rounded-xl group"
          :class="[
            collapsed ? 'justify-center px-0' : 'px-3.5',
            route.path.startsWith('/fix-schedule')
              ? 'bg-gradient-to-r from-[#3B5EFF] to-[#2946D9] text-white font-semibold shadow-lg shadow-[#3B5EFF]/25'
              : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'
          ]"
        >
          <el-icon class="text-lg flex-shrink-0"><Timer /></el-icon>
          <span v-if="!collapsed">Fix Schedule</span>
        </RouterLink>

        <RouterLink
          v-if="can('Shifting Schedule')"
          to="/shifting-schedule"
          class="flex items-center py-2.5 space-x-3 text-[13.5px] font-medium transition-all duration-200 rounded-xl group"
          :class="[
            collapsed ? 'justify-center px-0' : 'px-3.5',
            route.path.startsWith('/shifting-schedule')
              ? 'bg-gradient-to-r from-[#3B5EFF] to-[#2946D9] text-white font-semibold shadow-lg shadow-[#3B5EFF]/25'
              : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'
          ]"
        >
          <el-icon class="text-lg flex-shrink-0"><User /></el-icon>
          <span v-if="!collapsed">Shifting Schedule</span>
        </RouterLink>

        <RouterLink
          v-if="can('Assign Fix Schedule')"
          to="/assign-fix-schedule"
          class="flex items-center py-2.5 space-x-3 text-[13.5px] font-medium transition-all duration-200 rounded-xl group"
          :class="[
            collapsed ? 'justify-center px-0' : 'px-3.5',
            route.path.startsWith('/assign-fix-schedule')
              ? 'bg-gradient-to-r from-[#3B5EFF] to-[#2946D9] text-white font-semibold shadow-lg shadow-[#3B5EFF]/25'
              : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'
          ]"
        >
          <el-icon class="text-lg flex-shrink-0"><Timer /></el-icon>
          <span v-if="!collapsed">Assign Fix Schedule</span>
        </RouterLink>

        <RouterLink
          v-if="can('Leave Credits')"
          to="/leave-credits"
          class="flex items-center py-2.5 space-x-3 text-[13.5px] font-medium transition-all duration-200 rounded-xl group"
          :class="[
            collapsed ? 'justify-center px-0' : 'px-3.5',
            route.path.startsWith('/leave-credits')
              ? 'bg-gradient-to-r from-[#3B5EFF] to-[#2946D9] text-white font-semibold shadow-lg shadow-[#3B5EFF]/25'
              : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'
          ]"
        >
          <el-icon class="text-lg flex-shrink-0"><Timer /></el-icon>
          <span v-if="!collapsed">Leave Credits</span>
        </RouterLink>

        <RouterLink
          v-if="can('Leave Credit Monitoring')"
          to="/leave-credit-monitoring"
          class="flex items-center py-2.5 space-x-3 text-[13.5px] font-medium transition-all duration-200 rounded-xl group"
          :class="[
            collapsed ? 'justify-center px-0' : 'px-3.5',
            route.path.startsWith('/leave-credit-monitoring')
              ? 'bg-gradient-to-r from-[#3B5EFF] to-[#2946D9] text-white font-semibold shadow-lg shadow-[#3B5EFF]/25'
              : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'
          ]"
        >
          <el-icon class="text-lg flex-shrink-0"><Monitor /></el-icon>
          <span v-if="!collapsed">Leave Credit Monitoring</span>
        </RouterLink>

        <RouterLink
          v-if="can('Leave Monitoring')"
          to="/leave-monitoring"
          class="flex items-center py-2.5 space-x-3 text-[13.5px] font-medium transition-all duration-200 rounded-xl group"
          :class="[
            collapsed ? 'justify-center px-0' : 'px-3.5',
            route.path.startsWith('/leave-monitoring')
              ? 'bg-gradient-to-r from-[#3B5EFF] to-[#2946D9] text-white font-semibold shadow-lg shadow-[#3B5EFF]/25'
              : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'
          ]"
        >
          <el-icon class="text-lg flex-shrink-0"><Monitor /></el-icon>
          <span v-if="!collapsed">Leave Monitoring</span>
        </RouterLink>

        <RouterLink
          v-if="can('OB Monitoring')"
          to="/ob-monitoring"
          class="flex items-center py-2.5 space-x-3 text-[13.5px] font-medium transition-all duration-200 rounded-xl group"
          :class="[
            collapsed ? 'justify-center px-0' : 'px-3.5',
            route.path.startsWith('/ob-monitoring')
              ? 'bg-gradient-to-r from-[#3B5EFF] to-[#2946D9] text-white font-semibold shadow-lg shadow-[#3B5EFF]/25'
              : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'
          ]"
        >
          <el-icon class="text-lg flex-shrink-0"><Monitor /></el-icon>
          <span v-if="!collapsed">OB Monitoring</span>
        </RouterLink>

        <RouterLink
          v-if="can('Pass Slip Monitoring')"
          to="/pass-slip-monitoring"
          class="flex items-center py-2.5 space-x-3 text-[13.5px] font-medium transition-all duration-200 rounded-xl group"
          :class="[
            collapsed ? 'justify-center px-0' : 'px-3.5',
            route.path.startsWith('/pass-slip-monitoring')
              ? 'bg-gradient-to-r from-[#3B5EFF] to-[#2946D9] text-white font-semibold shadow-lg shadow-[#3B5EFF]/25'
              : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'
          ]"
        >
          <el-icon class="text-lg flex-shrink-0"><Monitor /></el-icon>
          <span v-if="!collapsed">Pass Slip Monitoring</span>
        </RouterLink>

        <RouterLink
          v-if="can('OT Monitoring')"
          to="/ot-monitoring"
          class="flex items-center py-2.5 space-x-3 text-[13.5px] font-medium transition-all duration-200 rounded-xl group"
          :class="[
            collapsed ? 'justify-center px-0' : 'px-3.5',
            route.path.startsWith('/ot-monitoring')
              ? 'bg-gradient-to-r from-[#3B5EFF] to-[#2946D9] text-white font-semibold shadow-lg shadow-[#3B5EFF]/25'
              : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'
          ]"
        >
          <el-icon class="text-lg flex-shrink-0"><Monitor /></el-icon>
          <span v-if="!collapsed">OT Monitoring</span>
        </RouterLink>

        <RouterLink
          v-if="can('COC Monitoring')"
          to="/coc-monitoring"
          class="flex items-center py-2.5 space-x-3 text-[13.5px] font-medium transition-all duration-200 rounded-xl group"
          :class="[
            collapsed ? 'justify-center px-0' : 'px-3.5',
            route.path.startsWith('/coc-monitoring')
              ? 'bg-gradient-to-r from-[#3B5EFF] to-[#2946D9] text-white font-semibold shadow-lg shadow-[#3B5EFF]/25'
              : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'
          ]"
        >
          <el-icon class="text-lg flex-shrink-0"><Monitor /></el-icon>
          <span v-if="!collapsed">COC Monitoring</span>
        </RouterLink>

        <RouterLink
          v-if="can('Work Suspension')"
          to="/work-suspension"
          class="flex items-center py-2.5 space-x-3 text-[13.5px] font-medium transition-all duration-200 rounded-xl group"
          :class="[
            collapsed ? 'justify-center px-0' : 'px-3.5',
            route.path.startsWith('/work-suspension')
              ? 'bg-gradient-to-r from-[#3B5EFF] to-[#2946D9] text-white font-semibold shadow-lg shadow-[#3B5EFF]/25'
              : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'
          ]"
        >
          <el-icon class="text-lg flex-shrink-0"><Remove /></el-icon>
          <span v-if="!collapsed">Work Suspension</span>
        </RouterLink>

        <RouterLink
          v-if="can('Biometrics Data')"
          to="/biometrics-data"
          class="flex items-center py-2.5 space-x-3 text-[13.5px] font-medium transition-all duration-200 rounded-xl group"
          :class="[
            collapsed ? 'justify-center px-0' : 'px-3.5',
            route.path.startsWith('/biometrics-data')
              ? 'bg-gradient-to-r from-[#3B5EFF] to-[#2946D9] text-white font-semibold shadow-lg shadow-[#3B5EFF]/25'
              : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'
          ]"
        >
          <el-icon class="text-lg flex-shrink-0"><Timer /></el-icon>
          <span v-if="!collapsed">Biometrics Data</span>
        </RouterLink>

        <RouterLink
          v-if="can('Process Attendance')"
          to="/process-attendance"
          class="flex items-center py-2.5 space-x-3 text-[13.5px] font-medium transition-all duration-200 rounded-xl group"
          :class="[
            collapsed ? 'justify-center px-0' : 'px-3.5',
            route.path.startsWith('/process-attendance')
              ? 'bg-gradient-to-r from-[#3B5EFF] to-[#2946D9] text-white font-semibold shadow-lg shadow-[#3B5EFF]/25'
              : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'
          ]"
        >
          <el-icon class="text-lg flex-shrink-0"><Timer /></el-icon>
          <span v-if="!collapsed">Process Attendance</span>
        </RouterLink>

        <!-- Reports Accordion -->
        <div v-if="hasReports" class="pt-1">
          <button
            @click="toggleReports"
            class="w-full flex items-center justify-between py-2.5 space-x-3 text-[13.5px] font-medium transition-all duration-200 rounded-xl group"
            :class="[
              collapsed ? 'justify-center px-0' : 'px-3.5',
              isReportsActive ? 'text-[#3B5EFF] bg-[#3B5EFF]/[0.07] font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'
            ]"
          >
            <div class="flex items-center space-x-3 min-w-0">
              <el-icon class="text-lg flex-shrink-0"><Setting /></el-icon>
              <span v-if="!collapsed" class="truncate">Time Keeping Reports</span>
            </div>
            <svg v-if="!collapsed" class="w-4 h-4 transition-transform duration-200 flex-shrink-0" :class="{ 'rotate-180': openReports }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </button>

          <el-collapse-transition>
            <div v-show="!collapsed && openReports" class="pl-3 pr-1 mt-1 space-y-1 border-l-2 border-slate-200/70 ml-4">
              <RouterLink
                v-for="item in reportItems"
                :key="item.path"
                :to="item.path"
                class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all"
                :class="[route.path === item.path ? 'bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100']"
              >
                <el-icon><OfficeBuilding /></el-icon>
                <span>{{ formatReportLabel(item.name) }}</span>
              </RouterLink>
            </div>
          </el-collapse-transition>
        </div>
      </nav>

      <!-- Sidebar Footer -->
      <div class="p-4 border-t border-slate-200/70 bg-slate-50/80">
        <div class="flex items-center" :class="collapsed ? 'justify-center' : 'justify-between'">
          <button 
            @click="collapsed = !collapsed" 
            class="p-2 transition rounded-xl text-slate-500 hover:text-slate-900 hover:bg-slate-200/60"
            :aria-label="collapsed ? 'Expand sidebar' : 'Collapse sidebar'"
          >
            <svg v-if="!collapsed" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </button>
          <div v-if="!collapsed" class="text-right">
            <p class="text-xs font-medium text-slate-600">{{ companyName }}</p>
            <p class="text-[11px] text-slate-400">v1.0.0</p>
          </div>
        </div>
      </div>
    </aside>

    <!-- Main Content Area -->
    <div class="flex flex-col flex-1 min-w-0">
      <!-- Top Header -->
      <header class="sticky top-0 z-30 px-4 py-3 border-b border-slate-200/70 shadow-sm bg-white/90 backdrop-blur sm:px-6">
        <div class="mt-2 mb-2 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
          <div>
            <h2 class="text-xl font-bold tracking-tight text-slate-900">
              <slot name="header">Timekeeping</slot>
            </h2>
            <p class="mt-0.5 text-sm text-slate-500">Attendance Processing, Shifts & Leave Monitoring</p>
          </div>

          <!-- Right Side Controls -->
          <div class="flex flex-wrap items-center gap-3 lg:justify-end">
            <!-- Theme Switcher Button -->
            <button
              @click="toggleTheme"
              class="p-2 text-slate-500 transition-all duration-200 rounded-xl hover:text-slate-900 hover:bg-slate-100 dark:text-slate-400 dark:hover:text-white dark:hover:bg-white/10"
              :title="isDarkMode ? 'Switch to Light Mode' : 'Switch to Dark Mode'"
              :aria-label="isDarkMode ? 'Switch to Light Mode' : 'Switch to Dark Mode'"
            >
              <svg v-if="!isDarkMode" class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
              </svg>
              <svg v-else class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
              </svg>
            </button>

            <!-- Portal Quick Link -->
            <a
              href="http://localhost:5171"
              class="inline-flex items-center px-3.5 py-2 text-xs font-semibold rounded-xl transition-all space-x-2 bg-slate-100 hover:bg-slate-200 text-slate-700"
            >
              <svg class="w-4 h-4 text-[#3B5EFF]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
              </svg>
              <span>Employee Portal</span>
            </a>
          </div>
        </div>
      </header>

      <!-- Main Slot Content -->
      <main class="flex-1 p-4 sm:p-6 lg:p-8 space-y-6 overflow-y-auto bg-[#F3F5FA]">
        <div class="p-4 bg-white border border-slate-200/70 shadow-sm rounded-2xl sm:p-6">
          <slot />
        </div>
      </main>
    </div>
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
    if (!path && key.includes('tardiness')) {
      items.push({ name: 'tardiness reports', path: '/tardiness-reports' })
    }
    if (key.includes('time keeping reports') || key === 'time keeping reports') {
      hasTimeKeepingReports = true
    }
  }
  if (hasTimeKeepingReports) {
    items.push({ name: 'tardiness reports', path: '/tardiness-reports' })
  }
  const seen = new Set()
  return items.filter(i => (seen.has(i.path) ? false : (seen.add(i.path), true)))
})

onMounted(async () => {
  fetchCompanies()
  
  // 1) Synchronously load cached access rights to prevent sidebar flash on refresh
  const cacheRaw = localStorage.getItem('tk_allowed_menus')
  if (cacheRaw) {
    try {
      const cache = JSON.parse(cacheRaw)
      if (Array.isArray(cache.names) && cache.names.length > 0) {
        allowed.value = new Set(cache.names)
      }
    } catch (_) {}
  }

  try {
    const { getCurrentUser } = useAuth()
    const current = await getCurrentUser()
    if (current?.id) {
      const res = await authApi.getAccessRights(current.id, { visible_only: 1 })
      const payload = res?.data ?? res
      const tkMenus = payload?.hrp_menu || []
      const names = tkMenus.map(m => m.menu).filter(Boolean).map(n => String(n).trim().toLowerCase())
      allowed.value = new Set(names)
      try { localStorage.setItem('tk_allowed_menus', JSON.stringify({ userId: current.id, names })) } catch (_) {}
    }
  } catch (e) {
    if (!cacheRaw) {
      allowed.value = new Set()
    }
  }
})

function toggleReports() { 
  openReports.value = !openReports.value 
}

watchEffect(() => { openReports.value = isReportsActive.value })

function formatReportLabel(name) {
  return String(name).replace(/\s+/g, ' ').trim().replace(/\b\w/g, c => c.toUpperCase())
}

const isDarkMode = ref(localStorage.getItem('theme') === 'dark')
const toggleTheme = () => {
  isDarkMode.value = !isDarkMode.value
  localStorage.setItem('theme', isDarkMode.value ? 'dark' : 'light')
  if (isDarkMode.value) {
    document.documentElement.classList.add('dark')
  } else {
    document.documentElement.classList.remove('dark')
  }
}
onMounted(() => {
  if (isDarkMode.value) {
    document.documentElement.classList.add('dark')
  }
})
</script>

<style scoped>
.sidebar-scroll::-webkit-scrollbar {
  width: 4px;
}
.sidebar-scroll::-webkit-scrollbar-track {
  background: transparent;
}
.sidebar-scroll::-webkit-scrollbar-thumb {
  background: rgba(148, 163, 184, 0.2);
  border-radius: 4px;
}
.sidebar-scroll::-webkit-scrollbar-thumb:hover {
  background: rgba(148, 163, 184, 0.4);
}
</style>
