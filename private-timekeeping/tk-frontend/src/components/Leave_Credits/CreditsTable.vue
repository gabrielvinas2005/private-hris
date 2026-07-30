<template>
  <div class="table-with-loading">
<el-table ref="table" :data="tableData" stripe border height="520" style="width: 100%" row-key="id" @sort-change="onSortChange">
      <el-table-column label="#" width="60" align="center">
        <template #default="{ $index }">
          {{ serverPagination ? (currentPage - 1) * perPage + $index + 1 : $index + 1 }}
        </template>
      </el-table-column>
      <el-table-column prop="employee_no" label="Emp No" width="120" sortable="custom">
        <template #default="{ row }">
          <Employee_Data_Populate :employee="row" field="empNo" />
        </template>
      </el-table-column>
      <el-table-column prop="name" label="Employee" min-width="260" sortable="custom">
        <template #default="{ row }">
          <div class="emp">
            <Employee_Data_Populate :employee="row" field="photo" />
            <Employee_Data_Populate :employee="row" field="namePosition" />
          </div>
        </template>
      </el-table-column>
      <el-table-column prop="department" label="Department" min-width="220" sortable="custom">
        <template #default="{ row }">
          <Employee_Data_Populate :employee="row" field="department" />
        </template>
      </el-table-column>
      <el-table-column prop="employment_type_name" label="Employment Status" width="200" sortable="custom">
        <template #default="{ row }">
          <Employee_Data_Populate :employee="row" field="employmentStatus" />
        </template>
      </el-table-column>
      <el-table-column label="Actions" width="120" align="center" fixed="right">
        <template #default="{ row }">
          <el-tooltip content="View" placement="top" popper-class="tt-warning">
            <el-button size="small" type="warning" circle plain @click="showHistory(row)">
              <el-icon><View /></el-icon>
            </el-button>
          </el-tooltip>
        </template>
      </el-table-column>
    </el-table>

    <TableLoadingOverlay :loading="loading" text="Loading leave credits..." />

    <Pagination
      :pagination="paginationData"
      :per-page-options="[10, 25, 50, 100]"
      @page-change="onPageChange"
      @per-page-change="onPerPageChange"
    />

    <!-- History Dialog (Floating Form) -->
    <el-dialog
      v-model="historyDialogVisible"
      :title="historyDialogTitle"
      width="600px"
    >
      <div v-if="lastUpdatedInfo" class="last-updated-info">
        <el-icon><Clock /></el-icon>
        <span>{{ lastUpdatedInfo }}</span>
      </div>
      <el-table :data="historyCredits" size="small" border>
        <el-table-column prop="leave_type_name" label="Leave Type" />
        <el-table-column prop="credits" label="Credits" width="180" align="center">
          <template #default="{ row }">
            <el-input-number
              v-model="row.credits"
              :min="0"
              :step="0.5"
              controls-position="right"
              size="small"
              style="width: 150px"
            />
          </template>
        </el-table-column>
      </el-table>
      <div class="dialog-actions">
        <el-button @click="historyDialogVisible = false">Close</el-button>
        <el-button
          type="primary"
          :loading="saving"
          @click="saveAllCredits"
        >
          Save All
        </el-button>
      </div>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { View, Clock } from '@element-plus/icons-vue'
import Employee_Data_Populate from '@/components/Reusable_Components/Employee_Data_Populate.vue'
import Pagination from '@/components/Reusable_Components/Pagination.vue'
import TableLoadingOverlay from '@/components/Reusable_Components/TableLoadingOverlay.vue'
import { useSortingLogic } from '@/Composables/Sorting_Logic'

const props = defineProps({
  data: { type: Array, default: () => [] },
  leaveTypes: { type: Array, default: () => [] },
  search: { type: String, default: '' },
  loading: { type: Boolean, default: false },
  /** Server-side pagination: total count from API */
  total: { type: Number, default: undefined },
  /** Server-side pagination: current page (1-based) */
  currentPage: { type: Number, default: undefined },
  /** Server-side pagination: page size */
  perPage: { type: Number, default: undefined }
})
const emit = defineEmits(['save', 'cancel', 'page-change', 'per-page-change'])

const rows = ref([])
const saving = ref(false)
const historyDialogVisible = ref(false)
const historyEmployee = ref(null)
const historyCredits = ref([])
const originalCreditsMap = ref(new Map())
const { sortPriority, sortOrders, onSortChange, sortArray } = useSortingLogic()

const serverPagination = computed(() =>
  props.total !== undefined && props.currentPage !== undefined && props.perPage !== undefined
)
const internalPage = ref(1)
const internalPerPage = ref(10)
const currentPage = computed(() => (serverPagination.value ? props.currentPage : internalPage.value))
const perPage = computed(() => (serverPagination.value ? props.perPage : internalPerPage.value))

watch(() => props.data, (val) => {
  rows.value = (val || []).map(v => ({ ...v }))
}, { immediate: true })

const filteredForClient = computed(() => {
  const list = rows.value
  const s = (props.search || '').toLowerCase().trim()
  return !s ? list : list.filter(r => `${r.name || ''} ${r.employee_no || ''}`.toLowerCase().includes(s))
})

const tableData = computed(() => {
  const list = rows.value
  if (serverPagination.value) return sortArray(list)
  const start = (currentPage.value - 1) * perPage.value
  return sortArray(filteredForClient.value).slice(start, start + perPage.value)
})

const paginationData = computed(() => {
  const total = serverPagination.value ? props.total : filteredForClient.value.length
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

const getTotalCredits = (employee) => {
  const credits = employee.leave_credits || []
  return credits.reduce((total, credit) => total + (Number(credit.credits) || 0), 0).toFixed(2)
}

// Get last updated timestamp from leave_credits
const getLastUpdatedTimestamp = () => {
  if (!historyEmployee.value || !historyEmployee.value.leave_credits) return null
  
  const credits = historyEmployee.value.leave_credits
  if (credits.length === 0) return null
  
  // Find the most recent updated_at timestamp
  let latestDate = null
  credits.forEach(credit => {
    if (credit.updated_at) {
      const creditDate = new Date(credit.updated_at)
      if (!latestDate || creditDate > latestDate) {
        latestDate = creditDate
      }
    }
  })
  
  return latestDate
}

// Format timestamp for display
const formatLastUpdated = (date) => {
  if (!date) return null
  
  // Format as: YYYY-MM-DD HH:mm:ss.SSS
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  const hours = String(date.getHours()).padStart(2, '0')
  const minutes = String(date.getMinutes()).padStart(2, '0')
  const seconds = String(date.getSeconds()).padStart(2, '0')
  const milliseconds = String(date.getMilliseconds()).padStart(3, '0')
  
  return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}.${milliseconds}`
}

// Dialog title with employee name and last updated info
const historyDialogTitle = computed(() => {
  if (!historyEmployee.value) return 'Leave Credits History'
  
  const employeeName = historyEmployee.value.name || 'Unknown Employee'
  const lastUpdated = getLastUpdatedTimestamp()
  const formattedTimestamp = formatLastUpdated(lastUpdated)
  
  if (formattedTimestamp) {
    return `Leave Credits History of ${employeeName}`
  }
  
  return `Leave Credits History of ${employeeName}`
})

// Last updated info (kept for backward compatibility, but now also in title)
const lastUpdatedInfo = computed(() => {
  const lastUpdated = getLastUpdatedTimestamp()
  if (!lastUpdated) return null
  
  // Format the date in a more readable format for the info box
  const options = { 
    year: 'numeric', 
    month: 'long', 
    day: 'numeric', 
    hour: '2-digit', 
    minute: '2-digit',
    hour12: true
  }
  const formattedDate = lastUpdated.toLocaleDateString('en-US', options)
  
  return `Last updated: ${formattedDate}`
})

const showHistory = (employee) => {
  historyEmployee.value = employee
  prepareHistoryCredits(employee)
  historyDialogVisible.value = true
}

const prepareHistoryCredits = (employee) => {
  const credits = (employee?.leave_credits || []).map(credit => ({
    ...credit,
    credits: Number(credit.credits || 0)
  }))
  historyCredits.value = credits
  const map = new Map()
  credits.forEach(c => {
    map.set(c.leave_type_id, Number(c.credits))
  })
  originalCreditsMap.value = map
}

const creditsPayload = computed(() => {
  if (!historyEmployee.value) return []
  return historyCredits.value.map(credit => ({
    employeeId: historyEmployee.value.id,
    leaveTypeId: credit.leave_type_id,
    credits: Number(credit.credits || 0)
  }))
})

const saveAllCredits = () => {
  if (!creditsPayload.value.length) return
  try {
    saving.value = true
    emit('save', { changes: creditsPayload.value })
    historyDialogVisible.value = false
  } finally {
    saving.value = false
  }
}

const onPageChange = (page) => {
  if (serverPagination.value) emit('page-change', page)
  else internalPage.value = page
}

const onPerPageChange = (newPerPage) => {
  if (serverPagination.value) emit('per-page-change', newPerPage)
  else {
    internalPerPage.value = newPerPage
    internalPage.value = 1
  }
}

// onSortChange provided by composable
</script>

<style scoped>
.emp { display: flex; align-items: center; gap: 10px; }
.actions { margin-top: 12px; display: flex; gap: 8px; }
.table-with-loading { position: relative; }

.leave-credits-detail {
  padding: 16px;
  background-color: #f5f7fa;
  border-radius: 4px;
  margin: 8px 0;
}

.leave-credits-detail h4 {
  margin: 0 0 12px 0;
  color: #303133;
  font-size: 16px;
}

.no-expand-icon :deep(.el-table__expand-icon) {
  display: none;
}

.last-updated-info {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px;
  background-color: #f0f9ff;
  border-left: 3px solid #409eff;
  border-radius: 4px;
  margin-bottom: 16px;
  color: #606266;
  font-size: 14px;
}

.last-updated-info .el-icon {
  color: #409eff;
  font-size: 16px;
}

.dialog-actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  margin-top: 16px;
}
</style>