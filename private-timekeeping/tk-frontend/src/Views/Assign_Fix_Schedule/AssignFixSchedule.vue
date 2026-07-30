<template>
  <PageScaffold
    title="Assign Fix Schedule"
    subtitle="Assign fixed work schedules to employees and manage assignments"
    :breadcrumbs="[{ label: 'Timekeeping Module', to: '/' }, { label: 'Assign Fix Schedule' }]"
  >
    <!-- Schedule select and assign button -->
    <div class="mb-3 flex items-center gap-2">
      <el-select v-model="selectedScheduleId" placeholder="Select schedule" filterable style="min-width: 320px">
        <el-option v-for="s in schedules" :key="s.id" :label="s.name" :value="s.id" />
      </el-select>
      <div class="flex-1"></div>
      <el-button type="primary" :disabled="!selectedScheduleId || selectedIds.length===0" :loading="saving" @click="onAssign">Assign Selected</el-button>
    </div>
    
    <!-- Unified Employee Search/Filter Section -->
    <SearchFiltersContainer
      :searchValue="filters.search"
      @update:searchValue="val => filters.search = val"
      :filtersValue="filters"
      @update:filtersValue="val => Object.assign(filters, val)"
      :filters="searchFilters"
      :search-placeholder="'Search employees to assign schedule...'"
      :filters-loading="loading"
      :search-loading="loading"
      :show-advanced-filters="true"
      :show-year-filter="false"
      :auto-fetch="true"
      :show-export-section="false"
    />

    <!-- Employee Table -->
    <AssignFixEmployeeTable
      :items="filteredEmployees"
      v-model="selectedIds"
      :loading="loading"
      :has-loaded="hasLoadedEmployees"
    />

    <!-- Disclaimer Footer -->
    <div class="disclaimer-footer">
      <el-alert
        title="Note"
        type="info"
        :closable="false"
        show-icon
        class="disclaimer-alert"
      >
        <template #default>
          <span class="disclaimer-text">
            Active employees not showing in the search results are already assigned to a schedule.
          </span>
        </template>
      </el-alert>
    </div>
  </PageScaffold>
</template>

<script setup>
import PageScaffold from '../../components/Reusable_Components/PageScaffold.vue'
import AssignFixEmployeeTable from '../../components/Assign_Fix_Schedule/AssignFixEmployeeTable.vue'
import SearchFiltersContainer from '../../components/Reusable_Components/SearchFiltersContainer.vue'
import { useFilterLogic } from '@/Composables/Filter_Logic'
import { assignFixScheduleService, fixScheduleService, api as rawApi } from '../../services/api'
import { ElMessage } from 'element-plus'
import { computed, onMounted, ref, watch } from 'vue'
import { useExport } from '../../Composables/useExport'
import { useUnifiedReport } from '../../Composables/useUnifiedReport'

const schedules = ref([])
const employees = ref([])
const rawEmployeesRef = ref([])
const selectedScheduleId = ref(null)
const selectedIds = ref([])
const loading = ref(false)
const saving = ref(false)
const hasLoadedEmployees = ref(false)
const assignedSet = ref(new Set())
// Unified filter values
const filters = ref({ search: '', departmentId: null, positionId: null, employmentTypeId: null })
const departmentsRef = ref([])
const positionsRef = ref([])
const employmentTypesRef = ref([])

// SEARCH FILTERS for SearchFiltersContainer
const searchFilters = [
  { key: 'departmentId', label: 'Department' },
  { key: 'positionId', label: 'Position' },
  { key: 'employmentTypeId', label: 'Employment Type' }
]

const { filterEmployees } = useFilterLogic()
const filteredEmployees = computed(() => filterEmployees(employees.value, filters.value))

function normalizeEmployee(e, depMap, posMap, empTypeMap) {
  const employeeNo = e.employee_no ?? e.emp_no ?? e.empno ?? e.employeeNo ?? ''
  const departmentId = e.department_id ?? e.departmentId ?? e.dept_id ?? e.deptId
  const departmentName = e.department_name ?? e.departmentName ?? e.dept_name ?? e.deptName ?? e.department
  const positionId = e.position_id ?? e.positionId ?? e.job_position_id ?? e.jobPositionId
  const positionName = e.position_name ?? e.positionName ?? e.job_title ?? e.jobTitle ?? e.position
  const employmentTypeId = e.employment_type_id ?? e.employmentTypeId ?? e.employment_type
  const employmentTypeName = e.employment_type_name ?? e.employmentTypeName ?? e.employmentStatus
  return {
    ...e,
    employee_no: String(employeeNo || ''),
    department_id: Number(departmentId ?? 0) || null,
    department: String(
      departmentName || depMap.get(Number(departmentId)) || ''
    ),
    position: String(
      positionName || posMap.get(Number(positionId)) || ''
    ),
    employment_type_id: Number(employmentTypeId ?? 0) || null,
    employment_type: String(
      employmentTypeName || empTypeMap.get(Number(employmentTypeId)) || ''
    ),
  }
}

function normalizeEmployees() {
  // Ensure reference data maps are created even if reference data loading failed
  const depMap = new Map((departmentsRef.value || []).map(d => [Number(d.id), String(d.name)]))
  const posMap = new Map((positionsRef.value || []).map(p => [Number(p.id), String(p.name)]))
  const empTypeMap = new Map((employmentTypesRef.value || []).map(e => [Number(e.id), String(e.name)]))
  
  const rawEmployees = Array.isArray(rawEmployeesRef.value) ? rawEmployeesRef.value : []
  employees.value = rawEmployees.map(e => normalizeEmployee(e, depMap, posMap, empTypeMap))
  
  // Log normalization result for debugging
  if (rawEmployees.length > 0 && employees.value.length === 0) {
    console.warn('Normalization resulted in empty array. Raw employees:', rawEmployees.length)
  }
}

// Load fix schedules separately for faster dropdown population
async function loadSchedules() {
  try {
    const response = await fixScheduleService.list()
    const schedulesData = Array.isArray(response) ? response : (response?.data || [])
    schedules.value = schedulesData
    if (!selectedScheduleId.value && schedules.value.length) {
      selectedScheduleId.value = schedules.value[0].id
    }
  } catch (err) {
    console.error('Failed to load schedules:', err)
    ElMessage.error('Failed to load fix schedules')
  }
}

// Load employees separately (this is the slower query)
async function loadEmployees() {
  loading.value = true
  try {
    const response = await assignFixScheduleService.loadEmployees()
    let employeesData = []
    if (Array.isArray(response)) {
      employeesData = response
    } else if (response?.employees) {
      employeesData = Array.isArray(response.employees) ? response.employees : []
    } else if (response?.data?.employees) {
      employeesData = Array.isArray(response.data.employees) ? response.data.employees : []
    } else if (response?.data && Array.isArray(response.data)) {
      employeesData = response.data
    }

    rawEmployeesRef.value = employeesData
    normalizeEmployees()

    if (employeesData.length === 0) {
      console.warn('No employees returned from API. This is expected if all employees already have schedules assigned.')
    } else {
      console.log(`Loaded ${employeesData.length} employees for schedule assignment`)
    }
  } catch (err) {
    console.error('Error loading employees:', err)
    ElMessage.error(err.message || 'Failed to load employees')
    rawEmployeesRef.value = []
    employees.value = []
  } finally {
    loading.value = false
    hasLoadedEmployees.value = true
  }
}

async function reload() {
  // Load schedules first (fast), then employees (slower)
  await loadSchedules()
  await loadEmployees()
}

// Composables
const { exportToCSV } = useExport()
const { generateGenericTableReport, getPreviewContent } = useUnifiedReport()

// Export functions - handle directly
function handleExcelExport() {
  try {
    const exportData = employees.value.map(employee => ({
      'Employee No': employee.employee_no || '',
      'Name': employee.name || '',
      'Position': employee.position || '',
      'Department': employee.department || '',
      'Employment Type': employee.employment_type || ''
    }))
    
    const filename = `all_employees_${new Date().toISOString().slice(0, 10)}.csv`
    exportToCSV(exportData, filename)
    ElMessage.success('Excel file exported successfully')
  } catch (error) {
    console.error('Export error:', error)
    ElMessage.error('Failed to export Excel file')
  }
}

function handlePdfExport() {
  // PDF export is handled by the PreviewExport component
  ElMessage.info('PDF export is available in the preview modal')
}

function handleWordExport() {
  try {
    const columns = [
      { key: 'Employee No', label: 'Employee No', align: 'left' },
      { key: 'Name', label: 'Employee Name', align: 'left' },
      { key: 'Position', label: 'Position', align: 'left' },
      { key: 'Department', label: 'Department', align: 'left' },
      { key: 'Employment Type', label: 'Employment Type', align: 'left' }
    ]
    
    const data = employees.value.map(employee => ({
      'Employee No': employee.employee_no || '',
      'Name': employee.name || '',
      'Position': employee.position || '',
      'Department': employee.department || '',
      'Employment Type': employee.employment_type || ''
    }))
    
    const html = generateTableHTML(data, columns, {
      title: 'All Employees Report',
      subtitle: `Total Records: ${employees.value.length}`,
      showRowNumbers: true,
      zebraStripes: true
    })
    
    // For Word export, we'll use the same HTML generation as PDF
    // The PreviewExport component will handle the actual Word export
    ElMessage.info('Word export is available in the preview modal')
  } catch (error) {
    console.error('Export error:', error)
    ElMessage.error('Failed to export Word document')
  }
}

// Generate HTML content for preview
const reportHtmlContent = computed(() => {
  if (!filteredEmployees.value.length) return ''
  
  // Generate a simple table HTML for preview
  const headers = ['Employee No', 'Name', 'Position', 'Department', 'Employment Type']
  const rows = filteredEmployees.value.map(emp => [
    emp.employee_no || '',
    emp.name || '',
    emp.position || '',
    emp.department || '',
    emp.employment_type || ''
  ])
  
  let html = '<table style="width: 100%; border-collapse: collapse; margin: 20px 0;">'
  
  // Header row
  html += '<thead><tr style="background-color: #f5f5f5;">'
  headers.forEach(header => {
    html += `<th style="border: 1px solid #ddd; padding: 8px; text-align: left;">${header}</th>`
  })
  html += '</tr></thead>'
  
  // Data rows
  html += '<tbody>'
  rows.forEach(row => {
    html += '<tr>'
    row.forEach(cell => {
      html += `<td style="border: 1px solid #ddd; padding: 8px;">${cell}</td>`
    })
    html += '</tr>'
  })
  html += '</tbody>'
  
  html += '</table>'
  
  return html
})

async function onAssign() {
  if (!selectedScheduleId.value || selectedIds.value.length === 0) return
  saving.value = true
  try {
    await assignFixScheduleService.assign(selectedScheduleId.value, selectedIds.value)
    ElMessage.success('Employees assigned successfully')
    await reload()
    await loadAssigned()
  } catch (err) {
    ElMessage.error(err.message || 'Failed to assign employees')
  } finally {
    saving.value = false
  }
}

watch(selectedScheduleId, async () => {
  await loadAssigned()
})

async function loadAssigned() {
  assignedSet.value = new Set()
  const sid = Number(selectedScheduleId.value || 0)
  if (!sid) return
  try {
    const res = await fixScheduleService.getAssignedEmployees(sid)
    const list = res?.employees || res || []
    assignedSet.value = new Set(list.map(e => e.id))
  } catch (_) {
    // ignore
  }
}

// Load reference tables (departments, positions, employment types)
async function loadReferenceData() {
  try {
    const [depRes, posRes, empRes] = await Promise.all([
      rawApi.get('/departments').catch(() => []),
      rawApi.get('/positions').catch(() => []),
      rawApi.get('/employment-types').catch(() => [])
    ])
    
    departmentsRef.value = Array.isArray(depRes) ? depRes : (depRes?.data || depRes?.departments || [])
    positionsRef.value = Array.isArray(posRes) ? posRes : (posRes?.data || posRes?.positions || [])
    employmentTypesRef.value = Array.isArray(empRes) ? empRes : (empRes?.data || empRes?.employment_types || [])
  } catch (err) {
    console.error('Failed to load reference data:', err)
    ElMessage.error('Failed to load reference data')
  }
}
watch([departmentsRef, positionsRef, employmentTypesRef], () => {
  if (rawEmployeesRef.value && rawEmployeesRef.value.length) {
    normalizeEmployees()
  }
})

onMounted(async () => {
  try {
    // Start all data loads in parallel so employees don't wait on schedules or reference data.
    const schedulesPromise = loadSchedules()
    const referencePromise = loadReferenceData()
    const employeesPromise = loadEmployees()

    await Promise.allSettled([schedulesPromise, referencePromise, employeesPromise])
  } catch (err) {
    console.error('Error during component initialization:', err)
    ElMessage.error('Failed to initialize page. Please refresh.')
    loading.value = false
  }
})
</script>

<style scoped>
.mb-3 { margin-bottom: 12px; }
.flex { display: flex; }
.items-center { align-items: center; }
.gap-2 { gap: 8px; }
.flex-1 { flex: 1; }

.disclaimer-footer {
  margin-top: 0px;
  padding-top: 0px;
}

.disclaimer-alert {
  border-left: 4px solid #409eff;
  background-color: #f0f9ff;
}

.disclaimer-text {
  font-size: 14px;
  color: #606266;
  font-weight: 500;
}
.toolbar-row {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: nowrap;
}
.flex-spacer { flex: 1; }
@media (max-width: 768px) {
  .toolbar-row { flex-wrap: wrap; }
}
</style>

