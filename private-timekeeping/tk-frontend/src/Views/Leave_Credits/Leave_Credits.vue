<template>
  <PageScaffold
    title="Leave Credits Management"
    subtitle="Track and manage employee leave credits and balances"
    :breadcrumbs="[{ label: 'Timekeeping Module', to: '/' }, { label: 'Leave Credits' }]"
  >
    <SearchFiltersContainer
      :searchValue="filters.search"
      @update:searchValue="val => { filters.search = val }"
      :filtersValue="filters"
      @update:filtersValue="val => Object.assign(filters, val)"
      :filters="availableFilters"
      :search-placeholder="'Search employee (name or employee number)...'"
      :filters-loading="filtersLoading"
      :search-loading="loading"
      :auto-fetch="false"
      :html-content="reportHtmlContent"
      :preview-title="'Leave Credits Report'"
      :filename="'leave_credits_report'"
      :loading="loading || htmlLoading"
      @search="handleSearch"
      @filters-change="handleFiltersChange"
      @excel="handleExcelExport"
      @pdf="handlePdfExport"
      @word="handleWordExport"
    />

    <el-alert v-if="error" :title="error" type="error" show-icon class="mb-3" />

    <CreditsTable
      v-if="loading || totalEmployees > 0 || employees.length > 0"
      :data="employees"
      :leave-types="leaveTypes"
      :total="totalEmployees"
      :current-page="currentPage"
      :per-page="perPage"
      :loading="loading"
      @save="handleSave"
      @page-change="onPageChange"
      @per-page-change="onPerPageChange"
      @cancel="reload"
    />
    <el-empty v-else-if="!loading" description="No employees found with leave credits" />
  </PageScaffold>
</template>

<script setup>
import { ref, onMounted, computed, watch, onBeforeUnmount } from 'vue'
import PageScaffold from '@/components/Reusable_Components/PageScaffold.vue'
import SearchFiltersContainer from '@/components/Reusable_Components/SearchFiltersContainer.vue'
import CreditsTable from '@/components/Leave_Credits/CreditsTable.vue'
import { leaveCreditsService, api as rawApi } from '@/services/api'
import { useAuth } from '@/Composables/useAuth'
import { useBackendReportExport } from '@/Composables/useBackendReportExport'
// Removed useFilterLogic since we're doing server-side filtering now
import { ElMessage } from 'element-plus'

const { getCurrentUser } = useAuth()

const leaveTypes = ref([])
const employees = ref([])
const totalEmployees = ref(0)
const currentPage = ref(1)
const perPage = ref(25)
const filters = ref({ 
  search: '', 
  departmentId: null, 
  employmentTypeId: null 
})
const loading = ref(false)
const filtersLoading = ref(false)
const error = ref('')
let currentUserId = null

// Reference data for filters
const departmentsRef = ref([])
const employmentTypesRef = ref([])

// Available filters configuration
const availableFilters = computed(() => [
  {
    key: 'departmentId',
    label: 'Department',
    options: departmentsRef.value.filter(dept => dept.active !== false),
    valueKey: 'id',
    labelKey: 'name'
  },
  {
    key: 'employmentTypeId',
    label: 'Employment Type',
    options: employmentTypesRef.value.filter(emp => emp.active !== false),
    valueKey: 'id',
    labelKey: 'name'
  }
])

const isActiveLeaveType = (type) => Number(type?.active ?? 1) === 1
const filterActiveLeaveTypes = (list) => (list || []).filter(isActiveLeaveType)
/** LWOP (13) is not selectable in leave credit UIs */
const filterSelectableLeaveTypes = (list) =>
  filterActiveLeaveTypes(list).filter((t) => Number(t?.id) !== 13)

const applyLeaveTypeDefaults = (list) => {
  if (!Array.isArray(list)) return []
  if (!leaveTypes.value.length) return list

  return list.map(employee => {
    const creditMap = new Map(
      (employee.leave_credits || []).map(credit => [credit.leave_type_id, credit])
    )

    const completeCredits = leaveTypes.value.map(type => {
      if (creditMap.has(type.id)) {
        return creditMap.get(type.id)
      }

      return {
        id: null,
        employee_id: employee.id,
        leave_type_id: type.id,
        leave_type_name: type.name,
        credits: 0,
        created_at: null,
        updated_at: null,
        is_virtual: true
      }
    })

    return { ...employee, leave_credits: completeCredits }
  })
}

const loadLeaveTypes = async () => {
  try {
    const types = await leaveCreditsService.loadAllLeaveTypes()
    const source = Array.isArray(types) ? types : (types?.leave_types || [])
    leaveTypes.value = filterSelectableLeaveTypes(source)
  } catch (e) {
    console.error('Error loading leave types:', e)
    leaveTypes.value = []
  }
}



/** Fetch one page of employees with leave credits (server-side pagination + search). */
const loadEmployeesPage = async (page = 1, pageSize = 25) => {
  if (!currentUserId) {
    try {
      const me = await getCurrentUser()
      currentUserId = me?.id
    } catch (e) {
      console.error('Error getting current user:', e)
      return
    }
  }

  try {
    const filterParams = {
      search: filters.value.search,
      departmentId: filters.value.departmentId,
      employmentTypeId: filters.value.employmentTypeId,
      onlyActive: true,
      includeAllEmployees: true, // show all employees with leave credits (including 0)
      page,
      per_page: pageSize
    }
    const response = await leaveCreditsService.listAllLeaveCredits(filterParams)
    const isPaginated = response && typeof response === 'object' && !Array.isArray(response) && 'data' in response
    const rows = isPaginated ? (response.data ?? []) : (Array.isArray(response) ? response : [])
    const total = isPaginated ? (response.total ?? 0) : rows.length
    const lastPage = isPaginated ? (response.last_page ?? 1) : 1

    const map = new Map()
    const activeLeaveTypeIds = new Set((leaveTypes.value || []).map(type => type.id))
    for (const r of rows) {
      if (!map.has(r.employee_id)) {
        map.set(r.employee_id, {
          id: r.employee_id,
          employee_no: r.employee_no,
          name: r.name,
          first_name: r.first_name ?? null,
          middle_name: r.middle_name ?? null,
          last_name: r.last_name ?? null,
          photo: r.photo ?? null,
          position: r.position_name || r.position_id,
          department: r.department_name || r.department_id,
          department_id: r.department_id,
          department_name: r.department_name,
          employment_type_id: r.employment_type_id,
          employment_type_name: r.employment_type_name,
          leave_credits: []
        })
      }
      if (activeLeaveTypeIds.size && !activeLeaveTypeIds.has(r.leave_type_id)) continue
      map.get(r.employee_id).leave_credits.push({
        id: r.id,
        employee_id: r.employee_id,
        leave_type_id: r.leave_type_id,
        leave_type_name: r.leave_type_name || (leaveTypes.value.find(t => t.id === r.leave_type_id)?.name) || '',
        credits: Number(r.credits || 0),
        created_at: r.created_at,
        updated_at: r.updated_at,
      })
    }
    const groupedEmployees = Array.from(map.values())
    employees.value = applyLeaveTypeDefaults(groupedEmployees)
    totalEmployees.value = total
    currentPage.value = Math.min(page, Math.max(1, lastPage))
    perPage.value = pageSize
  } catch (e) {
    console.error('Error loading employees with credits:', e)
    employees.value = []
    totalEmployees.value = 0
  }
}

/** Fetch full list (for export only). */
const loadAllEmployeesForExport = async () => {
  const filterParams = {
    search: filters.value.search,
    departmentId: filters.value.departmentId,
    employmentTypeId: filters.value.employmentTypeId,
    onlyActive: true,
    includeAllEmployees: true,
    export: true
  }
  const response = await leaveCreditsService.listAllLeaveCredits(filterParams)
  const rows = Array.isArray(response) ? response : (response?.data ?? [])
  if (!Array.isArray(rows) || rows.length === 0) return []
  const map = new Map()
  const activeLeaveTypeIds = new Set((leaveTypes.value || []).map(type => type.id))
  for (const r of rows) {
    if (!map.has(r.employee_id)) {
      map.set(r.employee_id, {
        id: r.employee_id,
        employee_no: r.employee_no,
        name: r.name,
        first_name: r.first_name ?? null,
        middle_name: r.middle_name ?? null,
        last_name: r.last_name ?? null,
        photo: r.photo ?? null,
        position: r.position_name || r.position_id,
        department: r.department_name || r.department_id,
        department_id: r.department_id,
        department_name: r.department_name,
        employment_type_id: r.employment_type_id,
        employment_type_name: r.employment_type_name,
        leave_credits: []
      })
    }
    if (activeLeaveTypeIds.size && !activeLeaveTypeIds.has(r.leave_type_id)) continue
    map.get(r.employee_id).leave_credits.push({
      id: r.id,
      employee_id: r.employee_id,
      leave_type_id: r.leave_type_id,
      leave_type_name: r.leave_type_name || (leaveTypes.value.find(t => t.id === r.leave_type_id)?.name) || '',
      credits: Number(r.credits || 0),
      created_at: r.created_at,
      updated_at: r.updated_at,
    })
  }
  return applyLeaveTypeDefaults(Array.from(map.values()))
}

const onPageChange = (page) => {
  loading.value = true
  loadEmployeesPage(page, perPage.value).finally(() => { loading.value = false })
}

const onPerPageChange = (newPerPage) => {
  loading.value = true
  perPage.value = newPerPage
  loadEmployeesPage(1, newPerPage).finally(() => { loading.value = false })
}

const reload = async () => {
  try {
    error.value = ''
    loading.value = true
    await loadEmployeesPage(currentPage.value, perPage.value)
  } catch (e) {
    error.value = e?.message || 'Failed to load data'
  } finally {
    loading.value = false
  }
}

const { exportToExcel, exportToWord, getHtmlPreview, htmlLoading } = useBackendReportExport()
const reportHtmlContent = ref('')
let htmlPreviewTimer = null
let previewRequestId = 0

// Load HTML preview from backend (uses current page data)
async function loadHtmlPreview() {
  const employeesForReport = employees.value || []
  if (!employeesForReport.length) {
    reportHtmlContent.value = ''
    return
  }

  const requestId = ++previewRequestId
  const html = await getHtmlPreview('leave_credits_list', {
    employees: employeesForReport,
    leave_types: leaveTypes.value || []
  })

  if (requestId !== previewRequestId) return
  reportHtmlContent.value = html
}

const scheduleHtmlPreview = () => {
  if (htmlPreviewTimer) clearTimeout(htmlPreviewTimer)
  htmlPreviewTimer = setTimeout(() => {
    loadHtmlPreview()
  }, 300)
}

watch(employees, scheduleHtmlPreview)
watch(leaveTypes, scheduleHtmlPreview)

async function handleExcelExport() {
  try {
    loading.value = true
    const list = await loadAllEmployeesForExport()
    await exportToExcel('leave_credits_list', {
      employees: list,
      leave_types: leaveTypes.value || []
    }, 'leave_credits_report')
  } finally {
    loading.value = false
  }
}

async function handleWordExport() {
  try {
    loading.value = true
    const list = await loadAllEmployeesForExport()
    await exportToWord('leave_credits_list', {
      employees: list,
      leave_types: leaveTypes.value || []
    }, 'leave_credits_report')
  } finally {
    loading.value = false
  }
}

async function handlePdfExport() {
  try {
    loading.value = true
    const list = await loadAllEmployeesForExport()
    const html = await getHtmlPreview('leave_credits_list', {
      employees: list,
      leave_types: leaveTypes.value || []
    })
    if (!html) return
    const { useReportGenerator } = await import('../../Composables/useReportGenerator')
    const { exportToPDF } = useReportGenerator()
    await exportToPDF(html, 'leave_credits_report')
  } finally {
    loading.value = false
  }
}

const handleSearch = async (searchValue) => {
  filters.value.search = searchValue
  loading.value = true
  await loadEmployeesPage(1, perPage.value)
  loading.value = false
  scheduleHtmlPreview()
}

const handleFiltersChange = async (newFilters) => {
  filters.value = { ...filters.value, ...newFilters }
  loading.value = true
  await loadEmployeesPage(1, perPage.value)
  loading.value = false
  scheduleHtmlPreview()
}

// Load reference data for filters
const loadReferenceData = async () => {
  try {
    filtersLoading.value = true
    const [depRes, empRes] = await Promise.all([
      rawApi.get('/departments').catch(() => ({ data: [] })),
      rawApi.get('/employment-types').catch(() => ({ data: [] }))
    ])
    
    departmentsRef.value = Array.isArray(depRes) ? depRes : (depRes?.data || depRes?.departments || [])
    employmentTypesRef.value = Array.isArray(empRes) ? empRes : (empRes?.data || empRes?.employment_types || [])
  } catch (err) {
    console.error('Failed to load reference data:', err)
    ElMessage.error('Failed to load filter options')
  } finally {
    filtersLoading.value = false
  }
}

const handleSave = async (payload = {}) => {
  try {
    error.value = ''
    loading.value = true
    if (Array.isArray(payload.changes) && payload.changes.length) {
      for (const change of payload.changes) {
        await leaveCreditsService.saveCreditsBulk(
          change.leaveTypeId,
          [change.employeeId],
          [change.credits]
        )
      }
    } else {
      const employeeIds = payload.employeeIds || []
      const creditsArray = payload.creditsArray || []
      const leaveTypeId = payload.leaveTypeId
      await leaveCreditsService.saveCreditsBulk(leaveTypeId, employeeIds, creditsArray)
    }
    await loadEmployeesPage(currentPage.value, perPage.value)
    scheduleHtmlPreview()
  } catch (e) {
    error.value = e?.message || 'Failed to save leave credits'
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  try {
    error.value = ''
    filtersLoading.value = true
    loading.value = true
    await Promise.all([loadReferenceData(), loadLeaveTypes()])
    filtersLoading.value = false
    await loadEmployeesPage(1, perPage.value)
  } catch (e) {
    error.value = e?.message || 'Failed to load data'
  } finally {
    loading.value = false
  }
  scheduleHtmlPreview()
})

onBeforeUnmount(() => {
  if (htmlPreviewTimer) {
    clearTimeout(htmlPreviewTimer)
    htmlPreviewTimer = null
  }
})
</script>

<style scoped>
.mb-3 { margin-bottom: 12px; }
</style>
