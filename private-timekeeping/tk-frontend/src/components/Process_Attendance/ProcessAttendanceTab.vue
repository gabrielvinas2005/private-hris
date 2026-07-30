<template>
  <div>
    <ProcessAttendanceForm
      :type="type"
      :show-save-all="hasProcessed"
      :save-all-loading="saveAllLoading"
      @processed="handleProcessed"
      @update:payrollPeriodId="(v) => payrollPeriodId = v"
      @update:precedingPayrollPeriodId="(v) => precedingPayrollPeriodId = v"
      @employees-loaded="handleEmployeesLoaded"
      @view-processed="handleViewProcessed"
      @save-all="onSaveAll"
    />

    <!-- Overview metrics (visible only after processing) -->
    <div v-if="hasProcessed && rows.length" class="overview-row mb-4">
      <button
        class="overview-card success clickable"
        type="button"
        title="No absences that affect pay (absent on rest day, leave, holiday, or OB is not counted). No late or undertime."
        @click="showList('completeNoTardy')"
      >
        <div class="count">{{ completeNoTardyCount }}</div>
        <div class="label">Employees with Complete Attendance</div>
      </button>
      <button class="overview-card warning clickable" type="button" @click="showList('completeButTardy')">
        <div class="count">{{ completeButTardyCount }}</div>
        <div class="label">Employees with Complete Attendance but Tardy</div>
      </button>
      <button class="overview-card danger clickable" type="button" @click="showList('absences')">
        <div class="count">{{ absencesCount }}</div>
        <div class="label">Employees with Absences</div>
      </button>
    </div>

    <!-- Dialog listing employees by category -->
    <el-dialog v-model="showEmployeesDialog" :title="employeesDialogTitle" width="560px">
      <ul v-if="employeesDialogItems.length" class="names-list">
        <li v-for="emp in employeesDialogItems" :key="emp.employee_id || emp.id">
          <EmployeeDataPopulate :employee="emp" field="namePosition" />
        </li>
      </ul>
      <el-empty v-else description="No employees found." />
      <template #footer>
        <span class="dialog-footer">
          <el-button type="primary" @click="showEmployeesDialog = false">Close</el-button>
        </span>
      </template>
    </el-dialog>

    <!-- Search and Filters -->
    <div v-if="rows.length" class="mb-4">
      <div class="toolbar-row">
        <EmployeeSearchbar
          v-model="filters.search"
          :loading="loading"
          :placeholder="'Search employees...'"
          :show-reload-button="false"
          @search="handleSearch"
        />

        <!-- Filters -->
        <Filters
          v-model="filters"
          :filters="availableFilters"
          :loading="loading"
          @filter-change="handleFiltersChange"
        />

        <div class="flex-spacer"></div>
        <PreviewExport
          v-if="false"
          :html-content="reportHtmlContent"
          :title="'Process Attendance Report'"
          :filename="'process_attendance_report'"
          :on-excel="handleExcelExport"
          :on-pdf="handlePdfExport"
          :on-word="handleWordExport"
          :loading="loading"
        />
      </div>
    </div>

    <template v-if="filteredRows.length">
      <ProcessAttendanceTable
        ref="processAttendanceTableRef"
        :rows="filteredRows"
        :loading="loading"
        :pagination="paginationData"
        :days-present-map="daysPresentMap"
        :payroll-period-id="payrollPeriodId"
        :preceding-payroll-period-id="precedingPayrollPeriodId"
        @view="onView"
        @reprocess="onReprocess"
        @reprocess-completed="onReprocessCompleted"
        @offset="onOffset"
        @offset-details="onOffsetDetails"
        @cancel-offset="onCancelOffset"
        @cancel-offset-details="onCancelOffsetDetails"
        @report="onReport"
        @edit-times="onEditTimes"
        @apply-offset="handleShowOffsetModal"
      />

      <!-- Pagination -->
      <Pagination
        :pagination="paginationData"
        :per-page-options="[10, 25, 50, 100]"
        @page-change="onPageChange"
        @per-page-change="onPerPageChange"
      />
    </template>
    <el-empty v-else description="No attendance data to process" />

    <!-- Offset Modal -->
    <ProcessAttendanceOffsetModal
      v-model="showOffsetModal"
      :payroll-period-id="payrollPeriodId"
      :employee-row="selectedEmployeeForOffset"
      @offset-applied="handleOffsetApplied"
    />
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import ProcessAttendanceForm from './ProcessAttendanceForm.vue'
import ProcessAttendanceTable from './ProcessAttendanceTable.vue'
import ProcessAttendanceOffsetModal from './ProcessAttendanceOffsetModal.vue'
import EmployeeDataPopulate from '../Reusable_Components/Employee_Data_Populate.vue'
import EmployeeSearchbar from '../Reusable_Components/EmployeeSearchbar.vue'
import Filters from '../Reusable_Components/Filters.vue'
import Pagination from '../Reusable_Components/Pagination.vue'
import PreviewExport from '../Reusable_Components/Preview&Export.vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import api, { processAttendanceService } from '../../services/api'
import { useFilterLogic } from '../../Composables/Filter_Logic'
import { useUnifiedReport } from '../../Composables/useUnifiedReport'
import { useExport } from '../../Composables/useExport'
import { useReportGenerator } from '../../Composables/useReportGenerator'
import { getReportSystemLabel } from '../../Composables/useCompany.js'
import { usePDFPreview } from '../../Composables/usePDFPreview'
import { formatEmployeeName } from '../../Composables/useNameFormatter'

const props = defineProps({
  /** 'regular' | 'cos' — forwarded to ProcessAttendanceForm */
  type: { type: String, default: null },
  /** Unique localStorage key so each tab persists its own state */
  storageKey: { type: String, default: 'process_attendance_state' }
})

const rows = ref([])
const loading = ref(false)
const saveAllLoading = ref(false)
const payrollPeriodId = ref(null)
/** Mirrors ProcessAttendanceForm preceding period; passed to reprocess API when set. */
const precedingPayrollPeriodId = ref(null)
const hasProcessed = ref(false)
const daysPresentMap = ref({})
const showOffsetModal = ref(false)
const selectedEmployeeForOffset = ref(null)
const BACKEND_PAGE_SIZE = 1000

// Composables
const {
  generateAttendanceReport,
  getPreviewContent
} = useUnifiedReport()
const { exportToCSV } = useExport()
const { generateTableHTML } = useReportGenerator()
const { downloadAttendanceReportDOCX } = usePDFPreview()

// Ref to access table component methods
const processAttendanceTableRef = ref(null)

// Search and filter state
const filters = ref({
  search: '',
  departmentId: null,
  positionId: null,
  employmentTypeId: null
})

// Pagination state
const currentPage = ref(1)
const perPage = ref(10)

// Reference data for filters
const departmentsRef = ref([])
const positionsRef = ref([])

// Available filters configuration
const availableFilters = computed(() => [
  {
    key: 'departmentId',
    label: 'Department',
    options: departmentsRef.value.filter(dept => dept.active !== false),
    valueKey: 'id',
    labelKey: 'name'
  }
])

// Use filter logic
const { filterEmployees } = useFilterLogic()

const filteredRows = computed(() => filterEmployees(rows.value, filters.value, {
  onlyEmpNoAndDepartment: true,
  searchFields: ['employee_no', 'name']
}))

const paginationData = computed(() => {
  const total = filteredRows.value.length
  const from = total === 0 ? 0 : (currentPage.value - 1) * perPage.value + 1
  const to = Math.min(currentPage.value * perPage.value, total)
  return {
    current_page: currentPage.value,
    per_page: perPage.value,
    total: total,
    from: from,
    to: to
  }
})

// Overview metrics
function hasAnyAbsence(row) {
  if (!row) return false
  const totalAbsent = parseFloat(row.total_absent) || 0
  const totalAbsentOffset = parseFloat(row.total_absent_offset) || 0
  const hasAnyAbsentRecord = parseInt(row.has_any_absent_record || 0) > 0
  return totalAbsent > 0.001 || totalAbsentOffset > 0.001 || hasAnyAbsentRecord
}

function hasAnyTardy(row) {
  if (!row) return false
  const totalLate = parseFloat(row.total_late) || 0
  const totalLateOffset = parseFloat(row.total_late_offset) || 0
  const totalUndertime = parseFloat(row.total_undertime) || 0
  const totalUndertimeOffset = parseFloat(row.total_undertime_offset) || 0
  const hasLate = totalLate > 0.001 || totalLateOffset > 0.001
  const hasUndertime = totalUndertime > 0.001 || totalUndertimeOffset > 0.001
  const hasTardinessFromValues = hasLate || hasUndertime
  const hasLateRecord = parseInt(row.has_any_late_record || 0) > 0
  const hasUndertimeRecord = parseInt(row.has_any_undertime_record || 0) > 0
  const hasLateOffsetRecord = parseInt(row.has_any_late_offset_record || 0) > 0
  const hasUndertimeOffsetRecord = parseInt(row.has_any_undertime_offset_record || 0) > 0
  const hasTardinessFromFlags = hasLateRecord || hasUndertimeRecord || hasLateOffsetRecord || hasUndertimeOffsetRecord
  return hasTardinessFromValues || hasTardinessFromFlags
}

const employeesCompleteNoTardy = computed(() =>
  rows.value.filter(r => !hasAnyAbsence(r) && !hasAnyTardy(r))
)

const employeesCompleteButTardy = computed(() =>
  rows.value.filter(r => !hasAnyAbsence(r) && hasAnyTardy(r))
)

const employeesWithAbsences = computed(() =>
  rows.value.filter(r => hasAnyAbsence(r))
)

const completeNoTardyCount = computed(() => employeesCompleteNoTardy.value.length)
const completeButTardyCount = computed(() => employeesCompleteButTardy.value.length)
const absencesCount = computed(() => employeesWithAbsences.value.length)

// Dialog support
const showEmployeesDialog = ref(false)
const employeesDialogTitle = ref('')
const employeesDialogItems = ref([])

function showList(kind) {
  const sortKey = (emp) => {
    const formatted = formatEmployeeName(emp, emp?.name || '')
    return (formatted || '').trim().toUpperCase()
  }
  const sortItemsAlphabetically = (items) =>
    [...(items || [])].sort((a, b) => {
      const byName = sortKey(a).localeCompare(sortKey(b), undefined, { sensitivity: 'base' })
      if (byName !== 0) return byName
      const aId = a?.employee_no ?? a?.id ?? 0
      const bId = b?.employee_no ?? b?.id ?? 0
      return String(aId).localeCompare(String(bId), undefined, { sensitivity: 'base' })
    })

  if (kind === 'completeNoTardy') {
    employeesDialogTitle.value = 'Employees with Complete Attendance'
    employeesDialogItems.value = sortItemsAlphabetically(employeesCompleteNoTardy.value)
  } else if (kind === 'completeButTardy') {
    employeesDialogTitle.value = 'Employees with Complete Attendance but Tardy'
    employeesDialogItems.value = sortItemsAlphabetically(employeesCompleteButTardy.value)
  } else if (kind === 'absences') {
    employeesDialogTitle.value = 'Employees with Absences'
    employeesDialogItems.value = sortItemsAlphabetically(employeesWithAbsences.value)
  }
  showEmployeesDialog.value = true
}

function formatCurrency(amount) {
  try {
    return new Intl.NumberFormat(undefined, { style: 'currency', currency: 'PHP', currencyDisplay: 'narrowSymbol' }).format(amount || 0)
  } catch (_) {
    return `₱${Number(amount || 0).toLocaleString()}`
  }
}

function getDaysPresent(row) {
  if (!row) return 0
  const id = row.employee_id || row.id
  const base = daysPresentMap.value?.[String(id)] ?? 0
  const adjusted = parseFloat(row.is_adjusted_count ?? 0) || 0
  return base + adjusted
}

function handleProcessed() {
  hasProcessed.value = true
  rows.value = []
  daysPresentMap.value = {}
  loadData()
  persistState()
}

async function handleViewProcessed(data) {
  rows.value = []
  daysPresentMap.value = {}
  hasProcessed.value = true
  handleEmployeesLoaded(data.employees)
  daysPresentMap.value = data.daysPresent || {}
  persistState()
}

function handleEmployeesLoaded(response) {
  const departmentById = new Map((departmentsRef.value || []).map((d) => [String(d.id), d]))
  const departmentByName = new Map((departmentsRef.value || []).map((d) => [String(d.name || '').trim().toLowerCase(), d]))
  const normalizeRow = (row) => {
    const deptId = row?.department_id != null ? String(row.department_id) : ''
    const deptById = deptId ? departmentById.get(deptId) : null
    const deptByName = departmentByName.get(String(row?.department || '').trim().toLowerCase())
    const resolvedCode = row?.department_code || deptById?.code || deptByName?.code || ''
    return { ...row, department_code: resolvedCode }
  }

  if (response && response.data && response.pagination) {
    rows.value = Array.isArray(response.data) ? response.data.map(normalizeRow) : []
    currentPage.value = 1
  } else {
    rows.value = Array.isArray(response) ? response.map(normalizeRow) : []
    currentPage.value = 1
  }
  persistState()
}

function handleSearch(searchValue) {
  filters.value.search = searchValue
  currentPage.value = 1
}

function handleFiltersChange(newFilters) {
  filters.value = { ...newFilters }
  currentPage.value = 1
}

function onPageChange(page) { currentPage.value = page }
function onPerPageChange(newPerPage) { perPage.value = newPerPage; currentPage.value = 1 }

// Report HTML content for preview
const reportHtmlContent = computed(() => {
  if (!filteredRows.value.length) return ''
  const columns = [
    { key: 'employee_no', label: 'Employee No', align: 'center' },
    { key: 'name', label: 'Employee Name', align: 'left' },
    { key: 'department', label: 'Department', align: 'left' },
    { key: 'employment_type', label: 'Employment Type', align: 'left' },
    { key: 'days_present', label: 'Days Present', align: 'center' }
  ]
  const tableData = filteredRows.value.map(row => ({
    employee_no: row.employee_no || '',
    name: formatEmployeeName(row, row.name || ''),
    department: row.department || '',
    employment_type: row.employment_type || '',
    days_present: getDaysPresent(row)
  }))
  return generateTableHTML(tableData, columns, {
    title: 'Process Attendance Report',
    subtitle: `Payroll Period: ${payrollPeriodId.value || 'N/A'}`,
    showRowNumbers: true,
    zebraStripes: true
  })
})

async function handleExcelExport() {
  try {
    const filename = `process_attendance_${new Date().toISOString().slice(0, 10)}.csv`
    const exportRows = filteredRows.value.map(row => ({
      employee_no: row.employee_no || '',
      employee_name: formatEmployeeName(row, row.name || ''),
      department: row.department || '',
      employment_type: row.employment_type || '',
      days_present: getDaysPresent(row),
      total_amount: Number(parseFloat(row.total_amount || 0).toFixed(2)),
      status: row.is_processed ? 'Processed' : 'Pending'
    }))
    exportToCSV(exportRows, filename)
    ElMessage.success('Export downloaded successfully')
  } catch (error) {
    console.error('Excel export error:', error)
    ElMessage.error('Failed to export file')
  }
}

function handlePdfExport() {
  ElMessage.info('PDF export is available in the preview modal')
}

async function handleWordExport() {
  try {
    const attendanceData = {
      company_name: getReportSystemLabel(),
      date_range: `Process Attendance — Payroll Period ID: ${payrollPeriodId.value || 'N/A'} (Hours Worked column = days present)`,
      summary: {
        total_employees: filteredRows.value.length,
        total_days_present: filteredRows.value.reduce((sum, row) => sum + (getDaysPresent(row) || 0), 0),
        average_days_present: filteredRows.value.length > 0
          ? (filteredRows.value.reduce((sum, row) => sum + (getDaysPresent(row) || 0), 0) / filteredRows.value.length).toFixed(2) : 0
      },
      employees: filteredRows.value.map(row => ({
        employee_id: row.employee_no || String(row.employee_id || ''),
        name: formatEmployeeName(row, row.name || ''),
        department: row.department || '',
        date: '—',
        time_in: '—',
        time_out: '—',
        hours_worked: String(getDaysPresent(row)),
        status: row.is_processed ? 'Processed' : 'Pending'
      })),
      generated_by: 'System User'
    }
    const filename = `process_attendance_${new Date().toISOString().slice(0, 10)}`
    await downloadAttendanceReportDOCX(attendanceData, filename)
    ElMessage.success('DOCX report downloaded successfully')
  } catch (error) {
    console.error('DOCX export error:', error)
    ElMessage.error('Failed to generate DOCX report')
  }
}

async function loadReferenceData() {
  try {
    const [departmentsRes, positionsRes] = await Promise.all([
      api.get('/departments'),
      api.get('/positions')
    ])
    departmentsRef.value = departmentsRes?.data ?? departmentsRes ?? []
    positionsRef.value = positionsRes?.data ?? positionsRes ?? []
  } catch (e) {
    console.error('Failed to load reference data:', e)
  }
}

watch(payrollPeriodId, (newPeriodId, oldPeriodId) => {
  if (oldPeriodId === null) return
  if (newPeriodId !== oldPeriodId) {
    rows.value = []
    daysPresentMap.value = {}
    hasProcessed.value = false
    currentPage.value = 1
  }
})

onMounted(() => {
  loadReferenceData()
  restoreState()
})

async function loadData() {
  if (!payrollPeriodId.value) return
  loading.value = true
  try {
    const [res, daysPresent] = await Promise.all([
      processAttendanceService.getEmployeeAttendanceData(payrollPeriodId.value, { page: 1, per_page: BACKEND_PAGE_SIZE }),
      processAttendanceService.getDaysPresentSummary(payrollPeriodId.value)
    ])
    handleEmployeesLoaded(res)
    daysPresentMap.value = daysPresent || {}
    hasProcessed.value = true
    persistState()
  } catch (e) {
    ElMessage.error(e.message || 'Failed to load data')
  } finally {
    loading.value = false
  }
}

async function refreshEmployeeData(employeeId) {
  if (!payrollPeriodId.value || !employeeId) return
  try {
    const [res, daysPresent] = await Promise.all([
      processAttendanceService.getEmployeeAttendanceData(payrollPeriodId.value, { page: 1, per_page: BACKEND_PAGE_SIZE }),
      processAttendanceService.getDaysPresentSummary(payrollPeriodId.value)
    ])
    handleEmployeesLoaded(res)
    daysPresentMap.value = daysPresent || {}
    hasProcessed.value = true
    persistState()
  } catch (e) {
    console.error('Failed to refresh employee data:', e)
    await loadData()
  }
}

async function onView(row) {
  if (!payrollPeriodId.value) return
  try {
    await processAttendanceService.view(row.id ?? row.employee_id ?? 0, payrollPeriodId.value)
  } catch (e) {
    ElMessage.error(e.message || 'Failed to load details')
  }
}

async function onReprocess(row) {
  if (!payrollPeriodId.value) return
  try {
    await ElMessageBox.confirm(
      'Reprocessing will reset all initially set Offset Details. Are you sure you want to continue?',
      'Warning: Reset Offset Details',
      { confirmButtonText: 'Yes, Continue', cancelButtonText: 'No, Cancel', type: 'warning' }
    )
  } catch {
    if (processAttendanceTableRef.value) {
      processAttendanceTableRef.value.stopReprocessLoading(row)
    }
    return
  }
  try {
    await processAttendanceService.reprocess(row.employee_id || row.id, payrollPeriodId.value, {
      process_all_dates: true,
      ...(precedingPayrollPeriodId.value != null
        ? { preceding_payroll_period_id: Number(precedingPayrollPeriodId.value) }
        : {}),
    })
    const employeeName = formatEmployeeName(row)
    ElMessage.success(`${employeeName}'s attendance is reprocessed successfully`)
    const [res] = await Promise.all([
      processAttendanceService.getEmployeeAttendanceData(payrollPeriodId.value, { page: 1, per_page: BACKEND_PAGE_SIZE })
    ])
    const employees = res?.data ?? res
    const employeesArray = Array.isArray(employees) ? employees : []
    const updatedEmployee = employeesArray.find(emp => (emp.employee_id || emp.id) === (row.employee_id || row.id))
    if (updatedEmployee) {
      try {
        const savePayload = {
          payroll_period_id: payrollPeriodId.value,
          employee_id: updatedEmployee.employee_id || updatedEmployee.id,
          daily_rate: parseFloat(updatedEmployee.daily_rate || 0),
          days_covered: parseInt(updatedEmployee.days_covered || 0),
          total_late: parseFloat(updatedEmployee.total_late || 0),
          late_amount: parseFloat(updatedEmployee.late_amount || 0),
          total_undertime: parseFloat(updatedEmployee.total_undertime || 0),
          undertime_amount: parseFloat(updatedEmployee.undertime_amount || 0),
          total_absent: parseFloat(updatedEmployee.total_absent || 0),
          absent_amount: parseFloat(updatedEmployee.absent_amount || 0),
          total_amount: parseFloat(updatedEmployee.total_amount || 0),
          hours_worked: parseFloat(updatedEmployee.total_work_hours || 0),
          working_hours: parseFloat(updatedEmployee.total_work_hours || 0),
          work_hours: parseFloat(updatedEmployee.total_work_hours || 0),
          overtime_pay: parseFloat(updatedEmployee.ot_pay || 0)
        }
        const saveResponse = await processAttendanceService.save(savePayload)
        if (saveResponse && saveResponse.type === 'warning') {
          ElMessage.warning(`${employeeName}'s attendance was already saved`)
        } else {
          ElMessage.success(`${employeeName}'s attendance data saved successfully`)
        }
      } catch (saveError) {
        if (saveError.response?.data?.type === 'warning') {
          ElMessage.warning(`${employeeName}'s attendance was already saved`)
        } else {
          ElMessage.warning(`Reprocessed successfully, but failed to save: ${saveError?.message || 'Unknown error'}`)
        }
      }
    }
    await refreshEmployeeData(row.employee_id || row.id)
  } catch (e) {
    ElMessage.error(e.message || 'Failed to reprocess employee attendance')
  } finally {
    if (processAttendanceTableRef.value) {
      processAttendanceTableRef.value.stopReprocessLoading(row)
    }
  }
}

async function onReprocessCompleted(row) {
  if (!payrollPeriodId.value) return
  await refreshEmployeeData(row.employee_id || row.id)
}

async function onOffset(row) {
  if (!payrollPeriodId.value) return
  try {
    await processAttendanceService.offset({ id: row.id, payroll_period_id: payrollPeriodId.value, is_config: 1, late_config: 1, ut_config: 1, absent_config: 1 })
    ElMessage.success('Offset applied')
    loadData()
  } catch (e) {
    ElMessage.error(e.message || 'Failed to apply offset')
  }
}

async function onCancelOffset(row) {
  if (!payrollPeriodId.value) return
  try {
    await processAttendanceService.cancelOffset(row.id ?? 0, payrollPeriodId.value)
    ElMessage.success('Offset canceled')
    loadData()
  } catch (e) {
    ElMessage.error(e.message || 'Failed to cancel offset')
  }
}

async function onOffsetDetails(row) {
  if (!payrollPeriodId.value) return
  try {
    await processAttendanceService.offsetDetails(row.id ?? 0, payrollPeriodId.value, {})
    ElMessage.success('Offset details saved')
  } catch (e) {
    ElMessage.error(e.message || 'Failed to save offset details')
  }
}

async function onCancelOffsetDetails(row) {
  if (!payrollPeriodId.value) return
  try {
    await processAttendanceService.cancelOffsetDetails(row.id ?? 0, payrollPeriodId.value)
    ElMessage.success('Offset details canceled')
  } catch (e) {
    ElMessage.error(e.message || 'Failed to cancel offset details')
  }
}

async function onReport(row) {
  if (!payrollPeriodId.value) return
  try {
    const blob = await processAttendanceService.report(row.employee_id ?? 0, payrollPeriodId.value)
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `process_attendance_report_${row.employee_id || 'all'}_${payrollPeriodId.value}.pdf`
    a.click()
    URL.revokeObjectURL(url)
  } catch (e) {
    ElMessage.error(e.message || 'Failed to download report')
  }
}

async function onSaveAll(formData) {
  if (!payrollPeriodId.value || !rows.value.length) return
  saveAllLoading.value = true
  try {
    const unprocessedEmployees = rows.value.filter(emp => !emp.is_processed)
    if (unprocessedEmployees.length === 0) {
      ElMessage.warning('All employees are already processed!')
      saveAllLoading.value = false
      return
    }
    const confirmed = await ElMessageBox.confirm(
      `This will save attendance data for ${unprocessedEmployees.length} unprocessed employees. Continue?`,
      'Save All Employees',
      { confirmButtonText: 'Save All', cancelButtonText: 'Cancel', type: 'warning' }
    )
    if (!confirmed) { saveAllLoading.value = false; return }
    let successCount = 0
    let warningCount = 0
    for (const employee of unprocessedEmployees) {
      try {
        const payload = {
          payroll_period_id: payrollPeriodId.value,
          employee_id: employee.employee_id || employee.id,
          daily_rate: parseFloat(employee.daily_rate || 0),
          days_covered: parseInt(employee.days_covered || 0),
          total_late: parseFloat(employee.total_late || 0),
          late_amount: parseFloat(employee.late_amount || 0),
          total_undertime: parseFloat(employee.total_undertime || 0),
          undertime_amount: parseFloat(employee.undertime_amount || 0),
          total_absent: parseFloat(employee.total_absent || 0),
          absent_amount: parseFloat(employee.absent_amount || 0),
          total_amount: parseFloat(employee.total_amount || 0),
          hours_worked: parseFloat(employee.total_work_hours || 0),
          working_hours: parseFloat(employee.total_work_hours || 0),
          work_hours: parseFloat(employee.total_work_hours || 0),
          overtime_pay: parseFloat(employee.ot_pay || 0)
        }
        const response = await processAttendanceService.save(payload)
        if (response && response.type === 'warning') { warningCount++ } else { successCount++ }
      } catch (e) {
        if (e.response?.data?.type === 'warning') { warningCount++ }
        else { console.error(`Failed to save employee ${employee.employee_id}:`, e) }
      }
    }
    if (successCount > 0) ElMessage.success(`Successfully saved ${successCount} employees`)
    if (warningCount > 0) ElMessage.warning(`${warningCount} employees were already processed`)
    await loadData()
  } catch (e) {
    if (e !== 'cancel') ElMessage.error(e.message || 'Failed to save all employees')
  } finally {
    saveAllLoading.value = false
  }
}

async function onEditTimes(payload) {
  if (payload?.persisted && payload?.employee_id) {
    await refreshEmployeeData(payload.employee_id)
    return
  }
  if (!payrollPeriodId.value) return
  try {
    if (Array.isArray(payload?.batches)) {
      for (const batch of payload.batches) {
        if (!batch || !batch.payroll_period_id || !batch.employee_id) continue
        if (typeof processAttendanceService.editTimes === 'function') {
          await processAttendanceService.editTimes(batch)
        } else {
          await api.post('/process-attendance/edit-times', batch)
        }
      }
      ElMessage.success('Time edits saved')
      await refreshEmployeeData(payload.employee_id)
    } else {
      const single = payload
      if (!single || !single.payroll_period_id || !single.employee_id) return
      if (typeof processAttendanceService.editTimes === 'function') {
        await processAttendanceService.editTimes(single)
      } else {
        await api.post('/process-attendance/edit-times', single)
      }
      ElMessage.success('Time edits saved')
      await refreshEmployeeData(single.employee_id)
    }
  } catch (e) {
    ElMessage.error(e?.message || 'Failed to save time edits')
  }
}

function persistState() {
  try {
    const existingRaw = localStorage.getItem(props.storageKey)
    const existing = existingRaw ? JSON.parse(existingRaw) : {}
    const state = {
      ...existing,
      has_processed: !!hasProcessed.value,
      savedAt: Date.now()
    }
    localStorage.setItem(props.storageKey, JSON.stringify(state))
  } catch (_) { /* no-op */ }
}

function restoreState() {
  try {
    const raw = localStorage.getItem(props.storageKey)
    if (!raw) return
    const state = JSON.parse(raw)
    if (!state) return
    hasProcessed.value = !!state.has_processed
  } catch (_) { /* no-op */ }
}

function handleShowOffsetModal(row) {
  if (!payrollPeriodId.value) {
    ElMessage.warning('Please select a payroll period first')
    return
  }
  selectedEmployeeForOffset.value = row
  showOffsetModal.value = true
}

async function handleOffsetApplied() {
  await loadData()
  ElMessage.success('Offset data has been applied successfully')
}
</script>

<style scoped>
.toolbar-row {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 16px;
}

.flex-spacer { flex: 1; }
.mb-4 { margin-bottom: 16px; }

/* Overview styles */
.overview-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 16px;
  align-items: stretch;
}

.overview-card {
  background: #fff;
  border: 1px solid #ebeef5;
  border-left-width: 4px;
  border-radius: 12px;
  padding: 18px;
  box-shadow: 0 2px 4px rgba(15, 23, 42, 0.05);
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: 6px;
  min-height: 110px;
  transition: box-shadow 0.2s ease, transform 0.2s ease;
}

button.overview-card {
  width: 100%;
  text-align: left;
  border: 1px solid #ebeef5;
  border-left-width: 4px;
  background: #fff;
}

.overview-card.clickable {
  cursor: pointer;
  text-align: left;
}

.overview-card.clickable:hover {
  box-shadow: 0 6px 14px rgba(15, 23, 42, 0.08);
  transform: translateY(-2px);
}

.overview-card .count {
  font-size: 28px;
  font-weight: 700;
  color: #1f2937;
}

.overview-card .label {
  font-size: 13px;
  color: #64748b;
  font-weight: 500;
}

.overview-card.warning { border-left-color: #e6a23c; }
.overview-card.success { border-left-color: #67c23a; }
.overview-card.danger  { border-left-color: #f56c6c; }
.overview-card.info    { border-left-color: #409eff; }

.names-list {
  list-style: none;
  padding-left: 0;
  margin: 0;
  max-height: 52vh;
  overflow-y: auto;
}

.names-list li {
  padding: 8px 0;
  border-bottom: 1px solid #f2f3f5;
}

@media (max-width: 1200px) { .overview-row { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 640px)  { .overview-row { grid-template-columns: 1fr; } }
</style>
