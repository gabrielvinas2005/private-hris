<template>
  <div class="dashboard">
    <el-input v-model="search" placeholder="Search modules..." clearable class="mb-4" />
    <div class="grid">
      <el-card v-for="mod in filteredModules" :key="mod.path" shadow="hover" class="module-card">
        <template #header>
          <div class="header">
            <el-icon :size="22"><component :is="mod.icon" /></el-icon>
            <span>{{ mod.title }}</span>
          </div>
        </template>
        <p class="desc">{{ mod.description }}</p>
        <div class="actions">
          <el-button type="primary" @click="$router.push(mod.path)">Select</el-button>
        </div>
      </el-card>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { User, Timer, Setting, Money } from '@element-plus/icons-vue'
import { authApi } from '@/services/api'
import { useAuth } from '@/Composables/useAuth'

const search = ref('')
const modules = [
  { title: 'Fix Schedule', path: '/fix-schedule', icon: User, description: 'Configure and manage fixed work schedules for employees.' },
  { title: 'Shifting Schedule', path: '/shifting-schedule', icon: User, description: 'Set up and manage rotating shift schedules and assignments.' },
  { title: 'Assign Fix Schedule', path: '/assign-fix-schedule', icon: User, description: 'Assign fixed work schedules to employees and manage assignments.' },
  { title: 'Leave Credits', path: '/leave-credits', icon: Setting, description: 'Track and manage employee leave credits and balances.' },
  { title: 'Leave Monitoring', path: '/leave-monitoring', icon: Setting, description: 'Monitor leave applications, approvals, and leave status tracking.' },
  // Hidden - redundant with Leave Credit Card Monitoring
  // { title: 'Leave Taken Monitoring', path: '/leave-taken-monitoring', icon: Money, description: 'Track and analyze leave taken by employees over time.' },
  { title: 'Leave Credit Monitoring', path: '/leave-credit-monitoring', icon: Money, description: 'Monitor and manage employee leave credit balances and accruals.' },
  { title: 'OB Monitoring', path: '/ob-monitoring', icon: Setting, description: 'Track and monitor official business activities and approvals.' },
  { title: 'Pass Slip Monitoring', path: '/pass-slip-monitoring', icon: Setting, description: 'Monitor pass slip requests, approvals, and status.' },
  { title: 'OT Monitoring', path: '/ot-monitoring', icon: Timer, description: 'Monitor overtime hours, approvals, and overtime management.' },
  { title: 'COC Monitoring', path: '/coc-monitoring', icon: Setting, description: 'Track Certificate of Completion monitoring and compliance.' },
  { title: 'Work Suspension', path: '/work-suspension', icon: Setting, description: 'Manage work suspension records and related documentation.' },
  { title: 'Biometrics Data', path: '/biometrics-data', icon: Timer, description: 'Manage biometric time tracking data and device integration.' },
  { title: 'Process Attendance', path: '/process-attendance', icon: Timer, description: 'Process and manage employee attendance records and calculations.' },
  { title: 'Tardiness Reports', path: '/tardiness-reports', icon: Timer, description: 'Generate and analyze tardiness reports and attendance patterns.' },
]

const allowed = ref(new Set())

function can(name) {
  return allowed.value.has(String(name).trim().toLowerCase())
}

onMounted(async () => {
  // 1) Synchronously load cached list for instant render with no flash
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
      // 2) Fetch latest then update cache
      const res = await authApi.getAccessRights(current.id, { visible_only: 1 })
      const payload = res?.data ?? res
      const tkMenus = payload?.hrp_menu || []
      const names = tkMenus.map(m => m.menu).filter(Boolean).map(n => String(n).trim().toLowerCase())
      allowed.value = new Set(names)
      try { localStorage.setItem('tk_allowed_menus', JSON.stringify({ userId: current.id, names })) } catch (_) {}
    }
  } catch (_) {
    if (!cacheRaw) {
      allowed.value = new Set()
    }
  }
})

const filteredModules = computed(() => {
  const q = search.value.trim().toLowerCase()
  const list = modules.filter(m => can(m.title))
  if (!q) return list
  return list.filter(m => m.title.toLowerCase().includes(q))
})
</script>

<style scoped>
.grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 16px; }
.module-card { height: 200px; display: grid; grid-template-rows: auto 1fr auto; }
.header { display: grid; grid-auto-flow: column; align-items: center; gap: 8px; font-weight: 600; }
.desc { color: #6b7280; margin: 0; }
.actions { display: grid; justify-content: end; }
.mb-4 { margin-bottom: 16px; }
</style>
