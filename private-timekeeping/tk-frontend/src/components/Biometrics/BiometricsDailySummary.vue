<template>
  <div>
    <!-- Search and Filters Container -->
    <SearchFiltersContainer
      :search-value="filtersValue.search"
      @update:search-value="val => { filtersValue.search = val }"
      :filters-value="filtersValue"
      @update:filters-value="val => { filtersValue = val }"
      :search-placeholder="'Search by access no, name, department, or position...'"
      :search-loading="loading"
      :filters="availableFilters"
      :filters-loading="filtersLoading"
      :show-search-section="true"
      :show-filters-section="true"
      :show-export-section="false"
      @search="handleSearch"
      @filters-change="handleFiltersChange"
    />

    <div class="table-with-loading">
      <el-table 
        :data="paginatedRows" 
        border 
        style="width: 100%"
        max-height="700"
        stripe
        @sort-change="onSortChange"
        :row-key="getRowKey"
        :row-class-name="getRowClassName"
      >
      <el-table-column 
        prop="usercode" 
        label="Access No" 
        width="120" 
        fixed="left"
        sortable="custom"
      >
        <template #default="{ row }">
          {{ row.usercode }}
        </template>
      </el-table-column>
      <el-table-column 
        prop="employee_name" 
        label="Employee" 
        min-width="250" 
        fixed="left"
        sortable="custom"
      >
        <template #default="{ row }">
          <div style="display: flex; align-items: center; gap: 12px;">
            <!-- Photo -->
            <Employee_Data_Populate
              :employee="{
                employee_no: row.usercode,
                name: row.employee_name,
                photo: row.photo,
                position: row.position
              }"
              field="photo"
              :employee_no="row.usercode"
              :name="row.employee_name"
              :photo="row.photo"
            />
            <!-- Employee Info (Name and Position) -->
            <Employee_Data_Populate
              :employee="{
                employee_no: row.usercode,
                name: row.employee_name,
                position: row.position
              }"
              field="namePosition"
              :employee_no="row.usercode"
              :name="row.employee_name"
              :position="row.position"
            />
          </div>
        </template>
      </el-table-column>
      <el-table-column 
        prop="department" 
        label="Department" 
        min-width="200" 
        fixed="left"
        sortable="custom"
      >
        <template #default="{ row }">
          {{ row.department || '-' }}
        </template>
      </el-table-column>
      <el-table-column prop="am_in" label="AM In" width="120" align="center">
        <template #default="{ row }">
          <span :class="{ 
            'no-record': !row.am_in || row.am_in === '-' || row.am_in === null,
            'violation': row.is_late_am_in || row.has_missing_am_in
          }">
            {{ formatTimeValue(row.am_in) }}
          </span>
        </template>
      </el-table-column>
      <el-table-column prop="pm_out" label="PM Out" width="120" align="center">
        <template #default="{ row }">
          <span :class="{ 
            'no-record': !row.pm_out || row.pm_out === '-' || row.pm_out === null,
            'violation': row.is_early_pm_out || row.has_missing_pm_out
          }">
            {{ formatTimeValue(row.pm_out) }}
          </span>
        </template>
      </el-table-column>
      </el-table>

      <TableLoadingOverlay :loading="loading" text="Loading biometrics summary..." />
    </div>

    <!-- Pagination -->
    <Pagination
      v-if="sortedRows.length > 0"
      :pagination="pagination"
      :per-page-options="[10, 25, 50, 100]"
      @page-change="handlePageChange"
      @per-page-change="handlePerPageChange"
    />

    <!-- Summary Statistics -->
    <div class="mt-4" v-if="sortedRows.length > 0">
      <el-row :gutter="16">
        <el-col :span="6">
          <el-card class="summary-card">
            <div class="summary-item">
              <div class="summary-label">Total Employees</div>
              <div class="summary-value">{{ sortedRows.length }}</div>
            </div>
          </el-card>
        </el-col>
        <el-col :span="6">
          <el-card class="summary-card">
            <div class="summary-item">
              <div class="summary-label">Employees with Records</div>
              <div class="summary-value">{{ employeesWithRecords }}</div>
            </div>
          </el-card>
        </el-col>
        <el-col :span="6">
          <el-card class="summary-card">
            <div class="summary-item">
              <div class="summary-label">Employees without Records</div>
              <div class="summary-value">{{ employeesWithoutRecords }}</div>
            </div>
          </el-card>
        </el-col>
        <el-col :span="6">
          <el-card class="summary-card">
            <div class="summary-item">
              <div class="summary-label">Last Updated</div>
              <div class="summary-value" style="font-size: 14px;">{{ lastUpdated ? formatDateTime(lastUpdated) : 'N/A' }}</div>
            </div>
          </el-card>
        </el-col>
      </el-row>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch, onMounted } from 'vue'
import Employee_Data_Populate from '../Reusable_Components/Employee_Data_Populate.vue'
import Pagination from '../Reusable_Components/Pagination.vue'
import SearchFiltersContainer from '../Reusable_Components/SearchFiltersContainer.vue'
import TableLoadingOverlay from '../Reusable_Components/TableLoadingOverlay.vue'
import { useSortingLogic } from '../../Composables/Sorting_Logic.js'
import { useFilterLogic } from '../../Composables/Filter_Logic.js'
import { formatTime } from '../../Composables/useTimeFormatting.js'
import { api } from '../../services/api.js'

const props = defineProps({
  rows: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  lastUpdated: { type: [Date, String], default: null }
})

// Search and filter state
const filtersValue = ref({
  search: '',
  departmentId: null,
  positionId: null
})

// Filter loading state
const filtersLoading = ref(false)

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
  },
  {
    key: 'positionId',
    label: 'Position',
    options: positionsRef.value.filter(pos => pos.active !== false),
    valueKey: 'id',
    labelKey: 'name'
  }
])

// Filter and sorting logic
const { filterEmployees, matchesSearch } = useFilterLogic()
const { sortPriority, sortOrders, onSortChange, sortArray } = useSortingLogic()

// Custom search function for biometrics data structure
const filterBiometricsRows = (rows, filters) => {
  const list = Array.isArray(rows) ? rows : []
  const f = filters || {}
  const search = f.search || ''
  
  return list.filter(row => {
    // Search in usercode, employee_name, department, position
    if (search) {
      const searchLower = search.toLowerCase().trim()
      const usercode = (row.usercode || '').toLowerCase()
      const employeeName = (row.employee_name || '').toLowerCase()
      const department = (row.department || row.department_name || '').toLowerCase()
      const position = (row.position || row.position_name || '').toLowerCase()
      const employeeNo = (row.employee_no || '').toLowerCase()
      
      const matches = usercode.includes(searchLower) ||
                     employeeName.includes(searchLower) ||
                     department.includes(searchLower) ||
                     position.includes(searchLower) ||
                     employeeNo.includes(searchLower)
      
      if (!matches) return false
    }
    
    // Filter by department
    if (f.departmentId !== null && f.departmentId !== undefined && f.departmentId !== '') {
      const departmentId = Number(f.departmentId)
      const rowDepartmentId = Number(row.department_id)
      if (!Number.isNaN(departmentId) && !Number.isNaN(rowDepartmentId)) {
        if (rowDepartmentId !== departmentId) return false
      }
    }
    
    // Filter by position
    if (f.positionId !== null && f.positionId !== undefined && f.positionId !== '') {
      const positionId = Number(f.positionId)
      const rowPositionId = Number(row.position_id)
      if (!Number.isNaN(positionId) && !Number.isNaN(rowPositionId)) {
        if (rowPositionId !== positionId) return false
      } else {
        // If position_id is not available, filter by position name as fallback
        const positionName = (row.position || row.position_name || '').toLowerCase()
        const selectedPosition = positionsRef.value.find(p => Number(p.id) === positionId)
        if (selectedPosition) {
          const selectedPositionName = (selectedPosition.name || '').toLowerCase()
          if (positionName !== selectedPositionName) return false
        } else {
          // If position not found in reference, exclude this row
          return false
        }
      }
    }
    
    return true
  })
}

// Filtered rows based on search and filters
const filteredRows = computed(() => {
  return filterBiometricsRows(props.rows, filtersValue.value)
})

// Computed sorted rows (after filtering)
const sortedRows = computed(() => {
  return sortArray(filteredRows.value)
})

// Pagination state
const currentPage = ref(1)
const perPage = ref(10)

// Pagination object for Pagination component
const pagination = computed(() => {
  const total = sortedRows.value.length
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

// Paginated rows - slice the sorted rows based on current page and per page
const paginatedRows = computed(() => {
  const start = (currentPage.value - 1) * perPage.value
  const end = start + perPage.value
  return sortedRows.value.slice(start, end)
})

// Handle page change
const handlePageChange = (page) => {
  currentPage.value = page
}

// Handle per page change
const handlePerPageChange = (newPerPage) => {
  perPage.value = newPerPage
  // Reset to first page when per page changes
  currentPage.value = 1
}

// Handle search - sync search value to filters
const handleSearch = (value) => {
  filtersValue.value.search = value
  currentPage.value = 1 // Reset to first page when searching
}

// Handle filter changes
const handleFiltersChange = (newFilters) => {
  // Merge new filters while preserving search value
  filtersValue.value = {
    ...filtersValue.value,
    ...newFilters
  }
  currentPage.value = 1 // Reset to first page when filtering
}

// Reset to first page when rows change significantly or if current page is out of bounds
watch(() => sortedRows.value.length, (newLength, oldLength) => {
  // Only reset if current page would be out of bounds
  const totalPages = Math.ceil(newLength / perPage.value)
  if (currentPage.value > totalPages && totalPages > 0) {
    currentPage.value = 1
  }
  // If data was cleared (length became 0), reset to page 1
  if (newLength === 0 && oldLength > 0) {
    currentPage.value = 1
  }
})

// Load reference data for filters
onMounted(async () => {
  try {
    filtersLoading.value = true
    const [depRes, posRes] = await Promise.all([
      api.get('/departments').catch(() => ({ data: [] })),
      api.get('/positions').catch(() => ({ data: [] }))
    ])
    
    departmentsRef.value = Array.isArray(depRes) ? depRes : (depRes?.data || depRes?.departments || [])
    positionsRef.value = Array.isArray(posRes) ? posRes : (posRes?.data || posRes?.positions || [])
  } catch (err) {
    // Silently fail; filters will just show no options
  } finally {
    filtersLoading.value = false
  }
})

// Computed properties for summary statistics
const employeesWithRecords = computed(() => {
  return sortedRows.value.filter(row => {
    // Check if any attendance time exists (not null, not '-', not empty)
    const hasRecord = (time) => time && time !== '-' && time !== null && time !== ''
    return hasRecord(row.am_in) || hasRecord(row.am_out) || 
           hasRecord(row.break_in) || hasRecord(row.break_out) ||
           hasRecord(row.pm_in) || hasRecord(row.pm_out)
  }).length
})

const employeesWithoutRecords = computed(() => {
  return sortedRows.value.length - employeesWithRecords.value
})

// Get unique row key for efficient updates
const getRowKey = (row) => {
  return row.usercode || row.userid || Math.random().toString(36)
}

// Highlight rows with recent activity (within last 5 minutes from latest_checktime)
const getRowClassName = ({ row, rowIndex }) => {
  // If row has latest_checktime, check if it's recent
  if (row.latest_checktime) {
    try {
      const now = new Date()
      const checktime = new Date(row.latest_checktime)
      const diffMinutes = (now - checktime) / (1000 * 60)
      
      // Highlight rows with activity in the last 5 minutes
      if (diffMinutes <= 5) {
        return 'recent-activity-row'
      }
    } catch (err) {
      // Invalid date, ignore
    }
  }
  
  return ''
}

// Format time value to display as "10:50 AM" or "4:32 PM"
// Handles time strings like "10:50:00" or datetime strings like "2025-11-10 10:50:00.000"
const formatTimeValue = (value) => {
  if (!value || value === '-' || value === null || value === '') {
    return '-'
  }
  
  // Use formatTime to get the formatted time with AM/PM
  const formatted = formatTime(value)
  
  if (!formatted || formatted === '') {
    return '-'
  }
  
  // Remove periods from A.M./P.M. to make AM/PM
  let result = formatted.replace(/A\.M\./gi, 'AM').replace(/P\.M\./gi, 'PM')
  
  // Remove leading zero from single-digit hours (04:32 -> 4:32)
  // Match pattern: "0X:YY AM" or "0X:YY PM" at the start of the string
  result = result.replace(/^0(\d):(\d{2})\s+(AM|PM)$/i, '$1:$2 $3')
  
  return result
}

// Formatting functions
function formatDateTime(dateTimeString) {
  if (!dateTimeString) return ''
  try {
    const date = new Date(dateTimeString)
    return date.toLocaleString('en-US', {
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit',
      hour12: true
    })
  } catch {
    return dateTimeString
  }
}

// Expose filtered and sorted rows for parent component to use in reports
defineExpose({
  filteredRows,
  sortedRows
})
</script>

<style scoped>
.mt-4 { 
  margin-top: 16px; 
}

.summary-card {
  text-align: center;
}

.summary-item {
  padding: 8px;
}

.summary-label {
  font-size: 12px;
  color: #666;
  margin-bottom: 4px;
}

.summary-value {
  font-size: 18px;
  font-weight: bold;
  color: #409eff;
}

.no-record {
  color: #909399;
  font-style: italic;
}

.violation {
  color: #f56c6c;
  font-weight: 600;
}

.violation.no-record {
  color: #f56c6c;
  font-weight: 600;
  font-style: italic;
}

/* Highlight rows with recent activity (new inserts in time_data) */
:deep(.recent-activity-row) {
  background-color: #e6f7ff !important;
  border-left: 3px solid #1890ff;
}

:deep(.recent-activity-row:hover > td) {
  background-color: #bae7ff !important;
}
.table-with-loading {
  position: relative;
}
</style>

