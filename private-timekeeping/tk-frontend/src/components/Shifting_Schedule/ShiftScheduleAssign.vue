<template>
  <el-dialog 
    v-model="model" 
    title="Assign Employees" 
    width="90%" 
    :close-on-click-modal="false"
    destroy-on-close
    align-center
  >
    <div class="assign-form">
      <div class="panel-layout">
        <section class="panel assign-panel">
          <header class="panel-header">
            <div>
              <h3>Assign Employees</h3>
              <p>Select employees on the left to add them to this schedule.</p>
            </div>
          </header>

          <div class="toolbar-row">
            <EmployeeSearchbar
              v-model="filters.search"
              :loading="loading"
              :placeholder="'Search employees to assign...'"
              :show-reload-button="false"
              @search="handleSearch"
              @reload="loadUnassignedEmployees"
            />

            <Filters
              v-model="filters"
              :filters="availableFilters"
              :loading="loading"
              @filter-change="handleFiltersChange"
            />
          </div>

          <el-table 
            :data="paginatedData" 
            border 
            stripe 
            v-loading="loading" 
            @selection-change="onSel"
            @sort-change="onAssignSortChange"
            height="400"
          >
            <el-table-column type="selection" width="48" />
            <el-table-column type="index" label="#" width="60" :index="getRowIndex" />
            <el-table-column prop="employee_no" label="Emp No" width="120" sortable="custom">
              <template #default="{ row }">
                <EmployeeDataPopulate :employee="row" field="empNo" />
              </template>
            </el-table-column>
            <el-table-column prop="name" label="Employee" min-width="280" sortable="custom">
              <template #default="{ row }">
                <div class="emp">
                  <EmployeeDataPopulate :employee="row" field="photo" />
                  <EmployeeDataPopulate :employee="row" field="namePosition" />
                </div>
              </template>
            </el-table-column>
            <el-table-column prop="department" label="Department" min-width="200" sortable="custom">
              <template #default="{ row }">
                <EmployeeDataPopulate :employee="row" field="department" />
              </template>
            </el-table-column>
          </el-table>

          <Pagination
            :pagination="paginationData"
            :per-page-options="[10, 25, 50, 100]"
            @page-change="onPageChange"
            @per-page-change="onPerPageChange"
          />
        </section>

        <section class="panel remove-panel">
          <header class="panel-header">
            <div>
              <h3>Assigned Employees</h3>
              <p>Employees currently tagged to this schedule.</p>
            </div>
            <el-button 
              type="danger" 
              size="small" 
              plain 
              :disabled="assignedSelectedIds.length === 0"
              :loading="assignedWorking"
              @click="removeAssignedEmployees"
            >
              Remove Selected ({{ assignedSelectedIds.length }})
            </el-button>
          </header>

          <div class="toolbar-row">
            <EmployeeSearchbar
              v-model="assignedFilters.search"
              :loading="assignedLoading || assignedWorking"
              :placeholder="'Search assigned employees...'"
              :show-reload-button="false"
              @search="handleAssignedSearch"
              @reload="loadAssignedEmployees"
            />

            <Filters
              v-model="assignedFilters"
              :filters="availableFilters"
              :loading="assignedLoading || assignedWorking"
              @filter-change="handleAssignedFiltersChange"
            />
            <div class="flex-spacer"></div>
            <PreviewExport
              :html-content="assignedReportHtml"
              :title="headerName ? `${headerName} - Assigned Employees` : 'Assigned Employees Report'"
              :filename="computedFilename"
              :on-excel="handleAssignedExportExcel"
              :on-pdf="handleAssignedExportPDF"
              :on-word="handleAssignedExportWord"
              :loading="assignedLoading || assignedWorking || htmlLoading"
            />
          </div>

          <el-table 
            :data="assignedPaginatedData" 
            border 
            stripe 
            v-loading="assignedLoading || assignedWorking"
            @selection-change="onAssignedSelection"
            @sort-change="onAssignedSortChange"
            height="400"
          >
            <el-table-column type="selection" width="48" />
            <el-table-column type="index" label="#" width="60" :index="getAssignedRowIndex" />
            <el-table-column prop="employee_no" label="Emp No" width="120" sortable="custom">
              <template #default="{ row }">
                <EmployeeDataPopulate :employee="row" field="empNo" />
              </template>
            </el-table-column>
            <el-table-column prop="name" label="Employee" min-width="280" sortable="custom">
              <template #default="{ row }">
                <div class="emp">
                  <EmployeeDataPopulate :employee="row" field="photo" />
                  <EmployeeDataPopulate :employee="row" field="namePosition" />
                </div>
              </template>
            </el-table-column>
            <el-table-column prop="department" label="Department" min-width="200" sortable="custom">
              <template #default="{ row }">
                <EmployeeDataPopulate :employee="row" field="department" />
              </template>
            </el-table-column>
          </el-table>

          <Pagination
            :pagination="assignedPaginationData"
            :per-page-options="[10, 25, 50, 100]"
            @page-change="onAssignedPageChange"
            @per-page-change="onAssignedPerPageChange"
          />
        </section>
      </div>
    </div>

    <!-- Dialog Footer -->
    <template #footer>
      <div class="dialog-footer">
        <el-button @click="model = false">Cancel</el-button>
        <el-button 
          type="primary" 
          :loading="saving" 
          :disabled="selectedIds.length === 0 || !props.headerId"
          @click="onAssign"
        >
          Assign Selected ({{ selectedIds.length }})
        </el-button>
      </div>
    </template>
  </el-dialog>
</template>

<script setup>
import { computed, ref, watch, onMounted } from 'vue'
import { shiftScheduleService, api as rawApi } from '../../services/api'
import { ElMessage, ElMessageBox } from 'element-plus'
import EmployeeDataPopulate from '../Reusable_Components/Employee_Data_Populate.vue'
import EmployeeSearchbar from '../Reusable_Components/EmployeeSearchbar.vue'
import Filters from '../Reusable_Components/Filters.vue'
import Pagination from '../Reusable_Components/Pagination.vue'
import PreviewExport from '../Reusable_Components/Preview&Export.vue'
import { useFilterLogic } from '@/Composables/Filter_Logic'
import { useSortingLogic } from '@/Composables/Sorting_Logic'
import { useBackendReportExport } from '../../Composables/useBackendReportExport'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  headerId: { type: [Number, String], required: true },
})
const emit = defineEmits(['update:modelValue', 'updated'])

const model = computed({ get: () => props.modelValue, set: v => emit('update:modelValue', v) })
const loading = ref(false)
const assignedLoading = ref(false)
const assignedWorking = ref(false)
const saving = ref(false)
const rows = ref([])
const assignedRows = ref([])
const selectedIds = ref([])
const assignedSelectedIds = ref([])
const headerName = ref('')
const filters = ref({ 
  search: '', 
  departmentId: null, 
  positionId: null 
})
const assignedFilters = ref({ 
  search: '', 
  departmentId: null, 
  positionId: null 
})

const { exportToExcel, exportToWord, getHtmlPreview, htmlLoading } = useBackendReportExport()
const assignedReportHtml = ref('')

function sanitizeFilename(name) {
  return (name || '').replace(/[^a-zA-Z0-9]/g, '_') || 'shift_schedule_employees'
}

const computedFilename = computed(() => 
  `shift_schedule_employees_${sanitizeFilename(headerName.value)}_${new Date().toISOString().slice(0, 10)}`
)

// Reference data for filters
const departmentsRef = ref([])
const positionsRef = ref([])

// Pagination state
const currentPage = ref(1)
const perPage = ref(10)
const assignedCurrentPage = ref(1)
const assignedPerPage = ref(10)

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
    key: 'positionId',
    label: 'Position',
    options: positionsRef.value.filter(pos => pos.active !== false),
    valueKey: 'id',
    labelKey: 'name'
  }
])

const { filterEmployees } = useFilterLogic()
const { onSortChange: onAssignSortChange, sortArray: sortAssignableArray } = useSortingLogic()
const { onSortChange: onAssignedSortChange, sortArray: sortAssignedArray } = useSortingLogic()

// Filtered employees based on search and filters
const filteredEmployees = computed(() => filterEmployees(rows.value, filters.value))
const sortedEmployees = computed(() => sortAssignableArray(filteredEmployees.value))
const paginatedData = computed(() => {
  const start = (currentPage.value - 1) * perPage.value
  const end = start + perPage.value
  return sortedEmployees.value.slice(start, end)
})
const paginationData = computed(() => {
  const total = sortedEmployees.value.length
  const from = total === 0 ? 0 : (currentPage.value - 1) * perPage.value + 1
  const to = Math.min(currentPage.value * perPage.value, total)
  return {
    current_page: currentPage.value,
    per_page: perPage.value,
    total,
    from,
    to
  }
})

const assignedFilteredEmployees = computed(() => filterEmployees(assignedRows.value, assignedFilters.value))
const assignedSortedEmployees = computed(() => sortAssignedArray(assignedFilteredEmployees.value))
const assignedPaginatedData = computed(() => {
  const start = (assignedCurrentPage.value - 1) * assignedPerPage.value
  const end = start + assignedPerPage.value
  return assignedSortedEmployees.value.slice(start, end)
})
const assignedPaginationData = computed(() => {
  const total = assignedSortedEmployees.value.length
  const from = total === 0 ? 0 : (assignedCurrentPage.value - 1) * assignedPerPage.value + 1
  const to = Math.min(assignedCurrentPage.value * assignedPerPage.value, total)
  return {
    current_page: assignedCurrentPage.value,
    per_page: assignedPerPage.value,
    total,
    from,
    to
  }
})

watch(model, (open) => { 
  if (open) {
    resetFilters()
    resetAssignedFilters()
    refreshLists()
  } else {
    selectedIds.value = []
    assignedSelectedIds.value = []
    headerName.value = ''
    assignedReportHtml.value = ''
  }
})

watch(() => props.headerId, () => {
  if (model.value) refreshLists()
  else {
    assignedRows.value = []
    headerName.value = ''
    assignedReportHtml.value = ''
  }
})

watch([assignedFilteredEmployees, headerName], () => {
  if (model.value) loadAssignedReportHtml()
}, { deep: true })

// Reset filters when dialog opens
function resetFilters() {
  filters.value = { 
    search: '', 
    departmentId: null, 
    positionId: null 
  }
  currentPage.value = 1
  selectedIds.value = []
}

function resetAssignedFilters() {
  assignedFilters.value = { 
    search: '', 
    departmentId: null, 
    positionId: null 
  }
  assignedCurrentPage.value = 1
  assignedSelectedIds.value = []
}

async function refreshLists() {
  await Promise.all([loadUnassignedEmployees(), loadAssignedEmployees()])
}

async function loadUnassignedEmployees() {
  loading.value = true
  try {
    const employees = await shiftScheduleService.loadUnassignedEmployees()
    rows.value = normalizeEmployees(employees)
  } catch (err) {
    ElMessage.error(err?.message || 'Failed to load employees')
  } finally {
    loading.value = false
  }
}

async function loadAssignedEmployees() {
  const headerId = Number(props.headerId)
  if (!headerId) {
    assignedRows.value = []
    headerName.value = ''
    return
  }
  assignedLoading.value = true
  try {
    const res = await shiftScheduleService.form(headerId)
    const payload = res?.data ?? res
    const schedule = payload?.shift_schedules?.[0] || payload?.shift_schedule || {}
    headerName.value = schedule?.name || ''
    assignedRows.value = normalizeEmployees(payload.employees || [])
    await loadAssignedReportHtml()
  } catch (err) {
    ElMessage.error(err?.message || 'Failed to load assigned employees')
  } finally {
    assignedLoading.value = false
  }
}

// Normalize employee data to ensure consistent field names
function normalizeEmployees(employees) {
  return (employees || []).map(emp => ({
    ...emp,
    employee_no: emp.employee_no || emp.emp_no || '',
    department: emp.department || emp.department_name || '',
    position: emp.position || emp.position_name || '',
    employment_type: emp.employment_type || emp.employment_type_name || '',
    department_id: emp.department_id || null,
    position_id: emp.position_id || null,
    employment_type_id: emp.employment_type_id || null
  }))
}

function onSel(selection) {
  selectedIds.value = selection.map(s => s.id)
}

// Search handler
function handleSearch(searchValue) {
  filters.value.search = searchValue
  currentPage.value = 1 // Reset to first page when searching
}

// Filter change handler
function handleFiltersChange(newFilters) {
  filters.value = {
    ...filters.value,
    ...newFilters
  }
  currentPage.value = 1 // Reset to first page when filtering
}

// Pagination handlers
function onPageChange(page) {
  currentPage.value = page
}

function onPerPageChange(newPerPage) {
  perPage.value = newPerPage
  currentPage.value = 1 // Reset to first page when changing per page
}

function handleAssignedSearch(searchValue) {
  assignedFilters.value.search = searchValue
  assignedCurrentPage.value = 1
}

function handleAssignedFiltersChange(newFilters) {
  assignedFilters.value = {
    ...assignedFilters.value,
    ...newFilters
  }
  assignedCurrentPage.value = 1
}

function onAssignedPageChange(page) {
  assignedCurrentPage.value = page
}

function onAssignedPerPageChange(newPerPage) {
  assignedPerPage.value = newPerPage
  assignedCurrentPage.value = 1
}

function onAssignedSelection(selection) {
  assignedSelectedIds.value = selection.map(s => s.id)
}

async function onAssign() {
  if (!selectedIds.value.length) return
  saving.value = true
  try {
    await shiftScheduleService.addEmployees(Number(props.headerId), selectedIds.value)
    ElMessage.success('Employees assigned successfully')
    emit('updated')
    await refreshLists()
    selectedIds.value = []
  } catch (err) {
    ElMessage.error(err?.message || 'Failed to assign employees')
  } finally {
    saving.value = false
  }
}

function getRowIndex(index) {
  return (currentPage.value - 1) * perPage.value + index + 1
}

function getAssignedRowIndex(index) {
  return (assignedCurrentPage.value - 1) * assignedPerPage.value + index + 1
}

async function removeAssignedEmployees() {
  if (!assignedSelectedIds.value.length) return
  try {
    await ElMessageBox.confirm(
      `Remove ${assignedSelectedIds.value.length} selected employee(s) from this schedule?`,
      'Confirm Removal',
      { type: 'warning' }
    )
  } catch (_) {
    return
  }

  assignedWorking.value = true
  try {
    const headerId = Number(props.headerId)
    await Promise.all(assignedSelectedIds.value.map(id => shiftScheduleService.removeEmployee(headerId, id)))
    ElMessage.success('Selected employees removed')
    assignedSelectedIds.value = []
    await refreshLists()
    emit('updated')
  } catch (err) {
    ElMessage.error(err?.message || 'Failed to remove employees')
  } finally {
    assignedWorking.value = false
  }
}

async function loadAssignedReportHtml() {
  if (!assignedFilteredEmployees.value || !assignedFilteredEmployees.value.length) {
    assignedReportHtml.value = ''
    return
  }
  assignedReportHtml.value = await getHtmlPreview('shift_schedule_employees', {
    schedule_name: headerName.value || '',
    employees: assignedFilteredEmployees.value || []
  })
}

async function handleAssignedExportExcel() {
  await exportToExcel('shift_schedule_employees', {
    schedule_name: headerName.value || '',
    employees: assignedFilteredEmployees.value || []
  }, computedFilename.value)
}

async function handleAssignedExportWord() {
  await exportToWord('shift_schedule_employees', {
    schedule_name: headerName.value || '',
    employees: assignedFilteredEmployees.value || []
  }, computedFilename.value)
}

async function handleAssignedExportPDF() {
  const html = await getHtmlPreview('shift_schedule_employees', {
    schedule_name: headerName.value || '',
    employees: assignedFilteredEmployees.value || []
  })
  if (!html) return

  const { useReportGenerator } = await import('../../Composables/useReportGenerator')
  const { exportToPDF } = useReportGenerator()
  await exportToPDF(html, computedFilename.value)
}

onMounted(async () => {
  try {
    const [depRes, posRes] = await Promise.all([
      rawApi.get('/departments').catch(() => []),
      rawApi.get('/positions').catch(() => [])
    ])
    
    departmentsRef.value = Array.isArray(depRes) ? depRes : (depRes?.data || depRes?.departments || [])
    positionsRef.value = Array.isArray(posRes) ? posRes : (posRes?.data || posRes?.positions || [])
  } catch (err) {
    ElMessage.error(err?.message || 'Failed to load filter data')
  }
})

</script>

<style scoped>
.assign-form {
  min-height: 500px;
}

.panel-layout {
  display: flex;
  gap: 16px;
  flex-wrap: wrap;
}

.panel {
  flex: 1;
  min-width: 320px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 16px;
  background: #fff;
  box-sizing: border-box;
}

.panel-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 12px;
  margin-bottom: 12px;
}

.panel-header h3 {
  margin: 0;
  font-weight: 600;
  font-size: 16px;
  color: #0f172a;
}

.panel-header p {
  margin: 2px 0 0;
  font-size: 12px;
  color: #64748b;
}

.emp { 
  display: flex; 
  align-items: center; 
  gap: 10px; 
}

.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
}
.toolbar-row { display: flex; align-items: center; gap: 12px; flex-wrap: nowrap; }
.flex-spacer { flex: 1; }
@media (max-width: 768px) { .toolbar-row { flex-wrap: wrap; } }
</style>


