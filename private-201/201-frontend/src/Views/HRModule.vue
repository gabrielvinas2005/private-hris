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
import { authApi, accessRightsApi } from '@/services/api'

const search = ref('')
const modules = [
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

const accessLoaded = ref(false)
const allowedMenuNameSet = ref(new Set())

const normalizeMenuName = (s) => String(s ?? '').toLowerCase().replace(/[^a-z0-9]/g, '')

const canAccessMenu = (menuName) => {
  if (!accessLoaded.value) return false
  return allowedMenuNameSet.value.has(normalizeMenuName(menuName))
}

const filteredModules = computed(() => {
  const q = search.value.trim().toLowerCase()
  const textFiltered = !q ? modules : modules.filter((m) => m.title.toLowerCase().includes(q))
  return textFiltered.filter((m) => canAccessMenu(m.title))
})

onMounted(async () => {
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
.grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 16px; }
.module-card { height: 200px; display: grid; grid-template-rows: auto 1fr auto; }
.header { display: grid; grid-auto-flow: column; align-items: center; gap: 8px; font-weight: 600; }
.desc { color: #6b7280; margin: 0; }
.actions { display: grid; justify-content: end; }
.mb-4 { margin-bottom: 16px; }
</style>
