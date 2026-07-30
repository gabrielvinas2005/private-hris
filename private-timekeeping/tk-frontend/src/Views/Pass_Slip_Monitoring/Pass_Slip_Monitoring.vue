<template>
  <PageScaffold
    title="Pass Slip Monitoring"
    subtitle="Track and monitor pass slip requests and approvals"
    :breadcrumbs="[{ label: 'Timekeeping Module', to: '/' }, { label: 'Pass Slip Monitoring' }]"
  >
    <Overview
      :items="overviewItems"
      :clickable-labels="['Active']"
      @item-click="onOverviewItemClick"
    />

    <SearchFiltersContainer
      :searchValue="filters.search"
      @update:searchValue="val => { filters.search = val }"
      :filtersValue="filters"
      @update:filtersValue="val => Object.assign(filters, val)"
      :filters="availableFilters"
      :search-placeholder="'Search employee or department...'"
      :filters-loading="loading"
      :search-loading="loading"
      :auto-fetch="true"
      :show-export-section="false"
      @search="handleSearch"
      @filters-change="handleFiltersChange"
    />

    <el-tabs v-model="activeTab" @tab-change="onTabChange">
      <el-tab-pane label="For Approval" name="for_approval" />
      <el-tab-pane label="Approved" name="approved" />
      <el-tab-pane label="Disapproved" name="disapproved" />
      <el-tab-pane label="Cancelled" name="cancelled" />
      <el-tab-pane label="Expired" name="expired" />
    </el-tabs>

    <PassSlipTable
      v-if="loading || paginatedData.length > 0"
      :rows="paginatedData"
      :loading="loading"
      :hideActions="false"
      :activeTab="activeTab"
      @view="viewRow"
    />

    <Pagination
      v-if="loading || paginatedData.length > 0"
      :pagination="paginationData"
      :per-page-options="[10, 25, 50, 100]"
      @page-change="onPageChange"
      @per-page-change="onPerPageChange"
    />

    <PassSlipViewer v-model="viewerOpen" :record="viewedRecord" />

    <el-dialog
      v-model="activeDialogOpen"
      title="Active pass slips – Time In"
      width="520px"
      destroy-on-close
      class="active-pass-slips-dialog"
    >
      <p class="active-dialog-desc">Employees currently out on pass slip and their expected Time In.</p>
      <el-table v-if="activeRows.length > 0" :data="activeRows" border size="small" max-height="400">
        <el-table-column type="index" label="#" width="50" />
        <el-table-column label="Employee" min-width="200">
          <template #default="{ row }">
            {{ formatEmployeeName(row) || row.employee_no }}
          </template>
        </el-table-column>
        <el-table-column label="Time In" width="120">
          <template #default="{ row }">
            {{ formatTimeIn(row.time_in) }}
          </template>
        </el-table-column>
      </el-table>
      <el-empty v-else description="No active pass slips" />
      <template #footer>
        <el-button @click="activeDialogOpen = false">Close</el-button>
      </template>
    </el-dialog>

    <el-empty v-if="!loading && paginatedData.length === 0" description="No pass slip records found" />
  </PageScaffold>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import PageScaffold from '../../components/Reusable_Components/PageScaffold.vue'
import PassSlipTable from '../../components/Pass_Slip_Monitoring/PassSlipTable.vue'
import SearchFiltersContainer from '../../components/Reusable_Components/SearchFiltersContainer.vue'
import Pagination from '../../components/Reusable_Components/Pagination.vue'
import { passSlipMonitoringService } from '../../services/api'
import { useFilterLogic } from '../../Composables/Filter_Logic.js'
import { ElMessage } from 'element-plus'
import { Clock, CircleCheck, CloseBold, RemoveFilled, Calendar, Timer } from '@element-plus/icons-vue'
import Overview from '../../components/Reusable_Components/Overview.vue'
import PassSlipViewer from '../../components/Pass_Slip_Monitoring/PassSlipViewer.vue'
import { formatEmployeeName } from '../../Composables/useNameFormatter'

const route = useRoute()
const router = useRouter()

const loading = ref(false)
const activeTab = ref(route.query.tab || 'for_approval')
const pendingRows = ref([])
const approvedRows = ref([])
const disapprovedRows = ref([])
const cancelledRows = ref([])
const expiredRows = ref([])
const activeRows = ref([])
const activeDialogOpen = ref(false)
const viewerOpen = ref(false)
const viewedRecord = ref(null)

const filters = ref({
  search: '',
  positionId: null,
  departmentId: null,
  month: null,
  year: null,
})

const currentPage = ref(1)
const perPage = ref(10)

const departmentsRef = ref([])
const positionsRef = ref([])

const { filterEmployees } = useFilterLogic()

const displayedRows = computed(() => {
  switch (activeTab.value) {
    case 'approved':
      return approvedRows.value
    case 'disapproved':
      return disapprovedRows.value
    case 'cancelled':
      return cancelledRows.value
    case 'expired':
      return expiredRows.value
    default:
      return pendingRows.value
  }
})

function normalizePassSlips(rows) {
  return (rows || []).map(row => ({
    ...row,
    name: formatEmployeeName(row),
  }))
}

function toDate(value) {
  if (!value) return null
  const parsed = new Date(value)
  return Number.isNaN(parsed.getTime()) ? null : parsed
}

const availableFilters = computed(() => [
  {
    key: 'positionId',
    label: 'Position',
    valueKey: 'id',
    labelKey: 'name',
    options: positionsRef.value.filter(p => p?.active !== false),
  },
  {
    key: 'departmentId',
    label: 'Department',
    valueKey: 'id',
    labelKey: 'name',
    options: departmentsRef.value.filter(d => d?.active !== false),
  },
  {
    key: 'month',
    label: 'Month',
    valueKey: 'value',
    labelKey: 'label',
    options: [
      { value: null, label: 'All Months' },
      { value: 1, label: 'January' },
      { value: 2, label: 'February' },
      { value: 3, label: 'March' },
      { value: 4, label: 'April' },
      { value: 5, label: 'May' },
      { value: 6, label: 'June' },
      { value: 7, label: 'July' },
      { value: 8, label: 'August' },
      { value: 9, label: 'September' },
      { value: 10, label: 'October' },
      { value: 11, label: 'November' },
      { value: 12, label: 'December' },
    ],
  },
  {
    key: 'year',
    label: 'Year',
    valueKey: 'value',
    labelKey: 'label',
    options: (() => {
      const currentYear = new Date().getFullYear()
      const years = []
      for (let y = currentYear; y >= currentYear - 5; y--) {
        years.push({ value: y, label: String(y) })
      }
      return years
    })(),
  },
])

const filteredRows = computed(() => {
  const normalized = normalizePassSlips(displayedRows.value)
  const { month, year, ...otherFilters } = filters.value
  let filtered = filterEmployees(normalized, otherFilters, { searchFields: ['employee_no', 'name'] })

  const selectedMonth = month
  const selectedYear = year
  const hasMonthFilter = selectedMonth !== null && selectedMonth !== undefined
  const hasYearFilter = selectedYear !== null && selectedYear !== undefined

  if (hasMonthFilter || hasYearFilter) {
    filtered = filtered.filter((row) => {
      // Pass slips are date-based; use `date` as primary filter basis.
      const dateFrom = toDate(row.date)
      const dateTo = dateFrom
      if (!dateFrom) return false

      if (hasYearFilter && !hasMonthFilter) {
        const yearStart = new Date(selectedYear, 0, 1)
        const yearEnd = new Date(selectedYear, 11, 31, 23, 59, 59)
        return dateFrom <= yearEnd && dateTo >= yearStart
      }

      if (hasMonthFilter && hasYearFilter) {
        const monthStart = new Date(selectedYear, selectedMonth - 1, 1)
        const monthEnd = new Date(selectedYear, selectedMonth, 0, 23, 59, 59)
        return dateFrom <= monthEnd && dateTo >= monthStart
      }

      if (hasMonthFilter && !hasYearFilter) {
        return (dateFrom.getMonth() + 1) === Number(selectedMonth)
      }

      return true
    })
  }

  return filtered
})

const paginatedData = computed(() => {
  const start = (currentPage.value - 1) * perPage.value
  const end = start + perPage.value
  return filteredRows.value.slice(start, end)
})

const paginationData = computed(() => ({
  current_page: currentPage.value,
  per_page: perPage.value,
  total: filteredRows.value.length,
  from: filteredRows.value.length > 0 ? (currentPage.value - 1) * perPage.value + 1 : 0,
  to: Math.min(currentPage.value * perPage.value, filteredRows.value.length),
}))

const overviewItems = computed(() => [
  // `type` drives the Overview accent color (same as OB Monitoring)
  { label: 'For Approval', value: pendingRows.value.length, icon: Clock, type: 'pending' },
  { label: 'Approved', value: approvedRows.value.length, icon: CircleCheck, type: 'approved' },
  { label: 'Disapproved', value: disapprovedRows.value.length, icon: CloseBold, type: 'disapproved' },
  { label: 'Cancelled', value: cancelledRows.value.length, icon: RemoveFilled, type: 'cancelled' },
  { label: 'Expired', value: expiredRows.value.length, icon: Calendar, type: 'expired' },
  // Use the same blue accent as OB's "today" card
  { label: 'Active', value: activeRows.value.length, icon: Timer, type: 'today' },
])

async function loadData() {
  loading.value = true
  try {
    const [res, deptRes, posRes] = await Promise.all([
      passSlipMonitoringService.fetchMonitoring(),
      passSlipMonitoringService.api?.get('/departments') || Promise.resolve({ data: [] }),
      passSlipMonitoringService.api?.get('/positions') || Promise.resolve({ data: [] }),
    ])
    const data = res?.data ?? res
    pendingRows.value = data?.ForapprovalPassSlips || []
    approvedRows.value = data?.ApprovedPassSlips || []
    disapprovedRows.value = data?.DisapprovedPassSlips || []
    cancelledRows.value = data?.CancelledPassSlips || []
    expiredRows.value = data?.ExpiredPassSlips || []
    activeRows.value = data?.ActivePassSlips || []
    departmentsRef.value = deptRes?.data || []
    positionsRef.value = posRes?.data || []
  } catch (e) {
    ElMessage.error(e?.message || 'Failed to load pass slip monitoring')
  } finally {
    loading.value = false
  }
}

const handleSearch = (searchValue) => {
  filters.value.search = searchValue
  currentPage.value = 1
}

const handleFiltersChange = (newFilters) => {
  Object.assign(filters.value, newFilters)
  currentPage.value = 1
}

const onPageChange = (page) => {
  currentPage.value = page
}

const onPerPageChange = (newPerPage) => {
  perPage.value = newPerPage
  currentPage.value = 1
}

const onTabChange = (tabName) => {
  currentPage.value = 1
  router.push({ query: { ...route.query, tab: tabName || activeTab.value } })
}

function viewRow(row) {
  viewedRecord.value = { ...row, name: formatEmployeeName(row) }
  viewerOpen.value = true
}

function onOverviewItemClick(item) {
  if (item.label === 'Active') {
    activeDialogOpen.value = true
  }
}

function formatTimeIn(timeVal) {
  if (!timeVal) return '–'
  const s = String(timeVal)
  const part = s.split(':')
  if (part.length >= 2) {
    const h = parseInt(part[0], 10)
    const m = part[1].padStart(2, '0')
    const ampm = h >= 12 ? 'PM' : 'AM'
    const h12 = h % 12 || 12
    return `${h12}:${m} ${ampm}`
  }
  return s
}

const validTabs = ['for_approval', 'approved', 'disapproved', 'cancelled', 'expired']

watch(
  () => route.query.tab,
  (newTab) => {
    if (newTab && validTabs.includes(newTab) && activeTab.value !== newTab) {
      activeTab.value = newTab
      currentPage.value = 1
    }
  },
  { immediate: true }
)

onMounted(() => {
  const q = route.query.tab
  if (q && validTabs.includes(q)) {
    activeTab.value = q
  }
  loadData()
})
</script>

<style scoped>
.active-dialog-desc {
  margin: 0 0 12px 0;
  color: #606266;
  font-size: 13px;
}
</style>
