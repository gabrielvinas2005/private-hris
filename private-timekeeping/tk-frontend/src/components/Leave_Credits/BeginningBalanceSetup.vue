<template>
  <el-dialog
    v-model="visible"
    title="Set Beginning Balances"
    width="1000px"
    destroy-on-close
    align-center
    append-to-body
    :close-on-click-modal="false"
  >
    <el-form :model="form" label-width="150px" label-position="left">
      <el-form-item label="Start Month">
        <el-select v-model="form.month_id" placeholder="Select month" style="width: 200px">
          <el-option
            v-for="month in months"
            :key="month.id"
            :label="month.name"
            :value="month.id"
          />
        </el-select>
      </el-form-item>

      <el-form-item label="Start Year">
        <el-select v-model="form.year" placeholder="Select year" style="width: 200px">
          <el-option
            v-for="year in availableYears"
            :key="year"
            :label="String(year)"
            :value="year"
          />
        </el-select>
      </el-form-item>

      <el-form-item label="Leave Type">
        <el-select v-model="selectedLeaveTypeId" placeholder="Select leave type" style="width: 300px">
          <el-option
            v-for="leaveType in activeLeaveTypes"
            :key="leaveType.id"
            :label="leaveType.name"
            :value="leaveType.id"
          />
        </el-select>
      </el-form-item>

      <el-form-item label="Filter Employees">
        <el-input
          v-model="employeeSearch"
          placeholder="Search by name or employee number..."
          style="width: 300px"
          clearable
          @input="filterEmployees"
        />
      </el-form-item>
    </el-form>

    <el-divider />

    <div v-if="form.month_id && form.year" class="balance-table-container">
      <el-table
        :data="paginatedEmployeesList"
        border
        stripe
        max-height="400"
        v-loading="loading"
      >
        <el-table-column type="index" label="#" width="55" />
        <el-table-column prop="employee_no" label="Emp No" width="80">
          <template #default="{ row }">
            <Employee_Data_Populate :employee="row" field="empNo" />
          </template>
        </el-table-column>
        <el-table-column prop="name" label="Employee" min-width="260">
          <template #default="{ row }">
            <div class="emp">
              <Employee_Data_Populate :employee="row" field="photo" />
              <Employee_Data_Populate :employee="row" field="namePosition" />
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="department" label="Department" min-width="220">
          <template #default="{ row }">
            <Employee_Data_Populate :employee="row" field="department" />
          </template>
        </el-table-column>
        <el-table-column prop="employment_type_name" label="Employment Status" width="200">
          <template #default="{ row }">
            <Employee_Data_Populate :employee="row" field="employmentStatus" />
          </template>
        </el-table-column>
        <el-table-column
          v-for="leaveType in displayedLeaveTypes"
          :key="leaveType.id"
          :label="leaveType.name"
          width="150"
        >
          <template #default="{ row }">
            <el-input-number
              v-model="row.balances[leaveType.id]"
              :min="0"
              :step="0.01"
              :precision="2"
              controls-position="right"
              style="width: 100%"
              size="small"
            />
          </template>
        </el-table-column>
      </el-table>

      <Pagination
        :pagination="paginationData"
        :per-page-options="[10, 25, 50, 100]"
        @page-change="onPageChange"
        @per-page-change="onPerPageChange"
      />
    </div>

    <el-alert
      v-if="error"
      :title="error"
      type="error"
      show-icon
      :closable="false"
      class="mb-3"
    />

    <template #footer>
      <el-button @click="handleClose">Cancel</el-button>
      <el-button
        type="primary"
        :loading="saving"
        :disabled="!form.month_id || !form.year || !selectedLeaveTypeId || !hasEmployees"
        @click="handleSave"
      >
        Save Beginning Balances
      </el-button>
    </template>
  </el-dialog>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { leaveCreditsService } from '@/services/api'
import { ElMessage } from 'element-plus'
import Employee_Data_Populate from '@/components/Reusable_Components/Employee_Data_Populate.vue'
import Pagination from '@/components/Reusable_Components/Pagination.vue'
import { useSortingLogic } from '@/Composables/Sorting_Logic.js'

const { sortArray } = useSortingLogic()

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  employees: { type: Array, default: () => [] },
  leaveTypes: { type: Array, default: () => [] }
})

const emit = defineEmits(['update:modelValue', 'saved'])

const visible = computed({
  get: () => props.modelValue,
  set: (val) => emit('update:modelValue', val)
})

const form = ref({
  month_id: null,
  year: new Date().getFullYear()
})

const months = [
  { id: 1, name: 'January' },
  { id: 2, name: 'February' },
  { id: 3, name: 'March' },
  { id: 4, name: 'April' },
  { id: 5, name: 'May' },
  { id: 6, name: 'June' },
  { id: 7, name: 'July' },
  { id: 8, name: 'August' },
  { id: 9, name: 'September' },
  { id: 10, name: 'October' },
  { id: 11, name: 'November' },
  { id: 12, name: 'December' }
]

const availableYears = computed(() => {
  const currentYear = new Date().getFullYear()
  const years = []
  for (let i = currentYear - 10; i <= currentYear + 5; i++) {
    years.push(i)
  }
  return years
})

const employeeSearch = ref('')
const selectedLeaveTypeId = ref(null)
const employeesList = ref([])
const loading = ref(false)
const saving = ref(false)
const error = ref('')

// Pagination
const currentPage = ref(1)
const perPage = ref(10)

// Initialize employees list with balance structure
const initializeEmployees = () => {
  employeesList.value = props.employees.map(emp => ({
    id: emp.id,
    employee_no: emp.employee_no,
    name: emp.name,
    first_name: emp.first_name ?? null,
    middle_name: emp.middle_name ?? null,
    last_name: emp.last_name ?? null,
    position: emp.position,
    department: emp.department,
    department_name: emp.department_name,
    employment_type_name: emp.employment_type_name,
    photo: emp.photo,
    balances: {}
  }))
  
  // Initialize balances for each leave type
  // Set to 0 for all employees (handles null credits)
  props.leaveTypes.forEach(lt => {
    employeesList.value.forEach(emp => {
      // Always set to 0 initially, even if employee has null credits
      emp.balances[lt.id] = 0
    })
  })
}

// Load existing beginning balances from database
const loadExistingBalances = async () => {
  if (!form.value.month_id || !form.value.year || !selectedLeaveTypeId.value) {
    return
  }

  try {
    loading.value = true
    const filters = {
      month_id: form.value.month_id,
      year: form.value.year,
      leave_type_id: selectedLeaveTypeId.value
    }
    
    const existingBalances = await leaveCreditsService.listBeginningBalances(filters)
    
    // Update employee balances with existing data
    if (existingBalances && existingBalances.length > 0) {
      existingBalances.forEach(balance => {
        const employee = employeesList.value.find(emp => emp.id === balance.employee_id)
        if (employee) {
          // Set balance, defaulting null/undefined to 0
          const balanceAmount = balance.balance_amount
          employee.balances[balance.leave_type_id] = (balanceAmount === null || balanceAmount === undefined) ? 0 : Number(balanceAmount) || 0
        }
      })
    }
    
    // Ensure all employees have balances set to 0 if they don't have existing balances
    // This handles employees with null credits
    employeesList.value.forEach(emp => {
      if (emp.balances[selectedLeaveTypeId.value] === null || emp.balances[selectedLeaveTypeId.value] === undefined) {
        emp.balances[selectedLeaveTypeId.value] = 0
      }
    })
  } catch (e) {
    ElMessage.warning('Failed to load existing balances')
  } finally {
    loading.value = false
  }
}

const activeLeaveTypes = computed(() => {
  return props.leaveTypes.filter(lt => {
    // Filter to only show active leave types (active = 1)
    if (lt.active === undefined || lt.active === null) return false
    return lt.active === 1 || lt.active === true || lt.active === '1'
  })
})

const displayedLeaveTypes = computed(() => {
  if (!selectedLeaveTypeId.value) {
    return []
  }
  return activeLeaveTypes.value.filter(lt => lt.id === selectedLeaveTypeId.value)
})

const filteredEmployeesList = computed(() => {
  let list
  if (!employeeSearch.value) {
    list = employeesList.value
  } else {
    const search = employeeSearch.value.toLowerCase()
    list = employeesList.value.filter(
      (emp) =>
        emp.name?.toLowerCase().includes(search) ||
        emp.employee_no?.toLowerCase().includes(search)
    )
  }
  return sortArray(list)
})

const paginatedEmployeesList = computed(() => {
  const start = (currentPage.value - 1) * perPage.value
  const end = start + perPage.value
  return filteredEmployeesList.value.slice(start, end)
})

const paginationData = computed(() => {
  const total = filteredEmployeesList.value.length
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

const hasEmployees = computed(() => {
  return filteredEmployeesList.value.length > 0
})

const filterEmployees = () => {
  // Filtering is handled by computed property
  // Reset to first page when filtering
  currentPage.value = 1
}

const onPageChange = (page) => {
  currentPage.value = page
}

const onPerPageChange = (newPerPage) => {
  perPage.value = newPerPage
  currentPage.value = 1 // Reset to first page when changing per page
}

const handleSave = async () => {
  if (!form.value.month_id || !form.value.year) {
    ElMessage.warning('Please select month and year')
    return
  }

  if (!hasEmployees.value) {
    ElMessage.warning('No employees to save')
    return
  }

  try {
    saving.value = true
    error.value = ''

    // Prepare balances array - only save for selected leave type
    // Include all employees (even with 0 balances) for batch insert
    const balances = []
    const leaveTypesToSave = activeLeaveTypes.value.filter(lt => lt.id === selectedLeaveTypeId.value)
    
    filteredEmployeesList.value.forEach(emp => {
      leaveTypesToSave.forEach(lt => {
        // Get balance amount, defaulting null/undefined to 0
        let balanceAmount = emp.balances[lt.id]
        if (balanceAmount === null || balanceAmount === undefined) {
          balanceAmount = 0
        }
        balanceAmount = Number(balanceAmount) || 0
        
        // Include all balances (including 0) for batch insert
        // This allows the backend to set beginning balances to 0 for employees with null credits
        balances.push({
          employee_id: emp.id,
          leave_type_id: lt.id,
          month_id: form.value.month_id,
          year: form.value.year,
          balance_amount: balanceAmount
        })
      })
    })

    if (balances.length === 0) {
      ElMessage.warning('No employees to save balances for.')
      return
    }

    await leaveCreditsService.saveBeginningBalancesBulk(balances)
    ElMessage.success(`Successfully saved ${balances.length} beginning balance(s)`)
    emit('saved')
    handleClose()
  } catch (e) {
    error.value = e?.message || 'Failed to save beginning balances'
    ElMessage.error(error.value)
  } finally {
    saving.value = false
  }
}

const handleClose = () => {
  visible.value = false
  form.value = {
    month_id: null,
    year: new Date().getFullYear()
  }
  employeeSearch.value = ''
  selectedLeaveTypeId.value = null
  currentPage.value = 1
  perPage.value = 10
  error.value = ''
}

// Watch for employees prop changes
watch(() => props.employees, () => {
  if (props.employees.length > 0) {
    initializeEmployees()
  }
}, { immediate: true, deep: true })

// Watch for leave types prop changes
watch(() => props.leaveTypes, () => {
  if (props.leaveTypes.length > 0 && employeesList.value.length > 0) {
    initializeEmployees()
  }
}, { immediate: true, deep: true })

// Watch for form changes to load existing balances
watch([() => form.value.month_id, () => form.value.year, () => selectedLeaveTypeId.value], () => {
  if (form.value.month_id && form.value.year && selectedLeaveTypeId.value && employeesList.value.length > 0) {
    // Reset balances to 0 first (handles null/undefined credits)
    props.leaveTypes.forEach(lt => {
      employeesList.value.forEach(emp => {
        // Set to 0 if null, undefined, or not set
        if (emp.balances[lt.id] === null || emp.balances[lt.id] === undefined) {
          emp.balances[lt.id] = 0
        }
      })
    })
    // Then load existing balances
    loadExistingBalances()
  }
}, { deep: true })
</script>

<style scoped>
.balance-table-container {
  margin-top: 20px;
}

.mb-3 {
  margin-bottom: 12px;
}

.emp {
  display: flex;
  align-items: center;
  gap: 10px;
}
</style>

