<template>
  <el-dialog v-model="visible" :title="title" width="95%" :close-on-click-modal="false" :close-on-press-escape="false">
    <div v-if="loading" class="text-center py-8">
      <el-icon class="is-loading"><Loading /></el-icon>
      <p class="mt-2">Loading form data...</p>
    </div>
    
    <div v-else>
      <!-- Period Selection -->
      <el-card shadow="never" class="mb-4">
        <el-row :gutter="16">
          <el-col :span="6">
            <el-form-item label="Month">
              <el-select 
                v-if="!props.monthId"
                v-model="form.month_id" 
                placeholder="Select Month"
                style="width: 100%"
                @change="onMonthYearChange"
              >
                <el-option 
                  v-for="month in months" 
                  :key="month.id" 
                  :label="month.name" 
                  :value="month.id"
                />
              </el-select>
              <el-input v-else v-model="form.month" readonly />
            </el-form-item>
          </el-col>
          <el-col :span="6">
            <el-form-item label="Year">
              <el-select 
                v-if="!props.yearId"
                v-model="form.year_id" 
                placeholder="Select Year"
                style="width: 100%"
                @change="onMonthYearChange"
              >
                <el-option 
                  v-for="year in years" 
                  :key="year" 
                  :label="year.toString()" 
                  :value="year"
                />
              </el-select>
              <el-input v-else v-model="form.year" readonly />
            </el-form-item>
          </el-col>
          <el-col :span="6">
            <el-form-item label="Date of Effectivity" required>
              <el-date-picker 
                v-model="form.date_of_effectivity" 
                type="date" 
                placeholder="mm/dd/yyyy" 
                style="width:100%" 
                format="MM/DD/YYYY"
                value-format="YYYY-MM-DD"
              />
            </el-form-item>
          </el-col>
        </el-row>
      </el-card>

      <!-- Search -->
      <div class="list-toolbar">
        <el-form-item label="Search in list:">
          <el-input v-model="searchQuery" placeholder="Search in employee list..." style="width: 300px" />
        </el-form-item>

        <div 
          v-if="isCreationMode" 
          class="selected-filter"
        >
          <span class="filter-label">Display:</span>
          <el-switch
            v-model="showSelectedOnly"
            :disabled="selectedEmployeeCount === 0 && !showSelectedOnly"
            inline-prompt
            active-text="Checked only"
            inactive-text="All employees"
            size="small"
            @change="handleSelectedFilterChange"
          />
          <small class="filter-hint">
            {{ showSelectedOnly ? 'Showing checked employees' : 'Showing all eligible employees' }}
          </small>
        </div>
      </div>

      <!-- Employee List -->
      <div class="employee-list-section">
        <h3>List of Employees for Step Increment</h3>
        
        <div v-if="employees.length === 0 && !loading" class="no-employees-message">
          <el-empty description="No employees found for this period">
            <template #description>
              <p>No employees are eligible for step increment in {{ form.month }} {{ form.year }}.</p>
              <p>This could mean:</p>
              <ul>
                <li>All eligible employees have already been processed</li>
                <li>No employees meet the 3-year service requirement</li>
                <li>No step increment data exists for this period</li>
              </ul>
            </template>
          </el-empty>
        </div>
        
        <el-table 
          v-else
          :data="filteredEmployees" 
          v-loading="loading" 
          border 
          stripe 
          style="width:100%" 
          max-height="400"
        >
          <el-table-column width="60" align="center">
            <template #header>
              <el-checkbox 
                v-model="selectAll" 
                @change="onSelectAllChange"
                :indeterminate="indeterminate"
              />
              Select
            </template>
            <template #default="{ row }">
              <el-checkbox 
                v-model="row.select" 
                @change="onEmployeeSelectChange"
              />
            </template>
          </el-table-column>
          
          <el-table-column prop="no" label="No." width="80" align="center">
            <template #default="{ $index }">{{ $index + 1 }}</template>
          </el-table-column>
          
          <el-table-column prop="employee_no" label="Employee No." width="120" />
          <el-table-column prop="name" label="Employee Name" min-width="200" />
          <el-table-column prop="years_in_service" label="Year of Service" width="150" />
          <el-table-column prop="department" label="Department" min-width="150" />
          <el-table-column prop="position" label="Position" min-width="150" />
          
          <!-- Current Salary Information -->
          <el-table-column label="Current Salary Grade" width="150">
            <template #default="{ row }">
              {{ row.salary_grade || 'N/A' }}
            </template>
          </el-table-column>
          
          <el-table-column label="Current Salary Step" width="150">
            <template #default="{ row }">
              {{ row.salary_step || 'N/A' }}
            </template>
          </el-table-column>
          
          <el-table-column label="Current Salary" width="120">
            <template #default="{ row }">
              ₱{{ Number(row.salary || 0).toLocaleString() }}
            </template>
          </el-table-column>
          
          <!-- New Salary Information (Step Increment keeps same grade) -->
          <el-table-column label="New Salary Grade" width="150">
            <template #default="{ row }">
              <span class="text-gray-600">{{ row.salary_grade || 'N/A' }}</span>
              <small class="block text-xs text-gray-400">(Same Grade)</small>
            </template>
          </el-table-column>
          
          <el-table-column label="New Salary Step" width="150">
            <template #default="{ row }">
              <div class="new-step-display">
                <div class="text-gray-600 font-semibold">Step {{ row.new_salary_step_id || '—' }}</div>
                <small class="block text-xs text-gray-400">Current Step + 1</small>
              </div>
            </template>
          </el-table-column>
          
          <el-table-column label="New Salary" width="120">
            <template #default="{ row }">
              <el-input-number 
                v-model="row.new_salary" 
                placeholder="0.00"
                size="small"
                :min="0"
                :precision="2"
                :step="100"
                style="width: 100%"
                controls-position="right"
                :disabled="true"
                title="Salary is automatically calculated from salary schedule"
              />
            </template>
          </el-table-column>
          
          <!-- Actions -->
          <el-table-column label="Actions" width="80" fixed="right">
            <template #default="{ row, $index }">
              <el-button 
                type="danger" 
                size="small" 
                :icon="Delete" 
                circle 
                @click="removeEmployeeFromList($index)"
                title="Remove from list"
              />
            </template>
          </el-table-column>
        </el-table>
      </div>
    </div>
    
    <template #footer>
      <div class="dialog-footer">
        <el-button @click="visible=false">Cancel</el-button>
        <el-button 
          v-if="!isApproved && !isRejected" 
          type="primary" 
          :loading="saving" 
          @click="onSave"
        >
          Save Step Increment
        </el-button>
        <el-button 
          v-if="!isApproved && !isRejected" 
          type="success" 
          :loading="saving" 
          @click="onForward"
        >
          Forward to Approver
        </el-button>
      </div>
    </template>
  </el-dialog>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue'
import { useStepIncrement } from '@/composable/useStepIncrement'
import { stepIncrementApi } from '@/services/api'
import { ElMessage } from 'element-plus'
import { Loading, Delete } from '@element-plus/icons-vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  monthId: { type: Number, default: 0 },
  yearId: { type: Number, default: 0 },
  month: { type: String, default: '' },
  year: { type: String, default: '' }
})
const emit = defineEmits(['update:modelValue','saved'])

const visible = computed({ get: () => props.modelValue, set: v => emit('update:modelValue', v) })
const title = computed(() => {
  if (props.month && props.year) {
    return `Step Increment - ${props.month} ${props.year}`
  } else {
    const selectedMonth = months.value.find(m => m.id === form.month_id)?.name || ''
    const selectedYear = form.year_id || ''
    return `Add Step Increment${selectedMonth && selectedYear ? ` - ${selectedMonth} ${selectedYear}` : ''}`
  }
})

const { loadEmployees, loadStepIncrementData, saveStepIncrement, forwardStepIncrement, loading } = useStepIncrement()
const employees = ref([])
const searchQuery = ref('')
const salaryGrades = ref([])
const stepIncrementData = ref([])

// Month and Year options for new step increments
const months = ref([
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
])

const currentYear = new Date().getFullYear()
const years = ref(Array.from({ length: 5 }, (_, i) => currentYear + i - 2)) // 2 years back to 2 years forward

const form = reactive({
  month: props.month,
  year: props.year,
  month_id: props.monthId,
  year_id: props.yearId,
  date_of_effectivity: ''
})

const saving = ref(false)
const selectAll = ref(false)
const indeterminate = ref(false)

// Computed properties
const isCreationMode = computed(() => !props.monthId && !props.yearId)

const showSelectedOnly = ref(false)
const userOverrodeSelectedFilter = ref(false)

const isEmployeeSelected = (employee) => {
  if (!employee) return false
  if (isTruthy(employee.select)) return true
  return !!(employee.step_increment_id && Number(employee.step_increment_id) > 0)
}

const selectedEmployeeCount = computed(() => employees.value.filter(emp => isEmployeeSelected(emp)).length)

const filteredEmployees = computed(() => {
  let list = employees.value

  if (isCreationMode.value && showSelectedOnly.value) {
    list = list.filter(isEmployeeSelected)
  }

  if (!searchQuery.value) return list
  
  const query = searchQuery.value.toLowerCase()
  return list.filter(emp => 
    emp.name?.toLowerCase().includes(query) ||
    emp.employee_no?.toLowerCase().includes(query) ||
    emp.department?.toLowerCase().includes(query) ||
    emp.position?.toLowerCase().includes(query)
  )
})

watch(
  () => [isCreationMode.value, selectedEmployeeCount.value, props.modelValue],
  ([creating, selectedCount, isOpen]) => {
    if (!isOpen) {
      userOverrodeSelectedFilter.value = false
      showSelectedOnly.value = false
      return
    }

    if (!creating) {
      if (!userOverrodeSelectedFilter.value) {
        showSelectedOnly.value = false
      }
      return
    }

    if (userOverrodeSelectedFilter.value) return

    showSelectedOnly.value = selectedCount > 0
  },
  { immediate: true }
)

const handleSelectedFilterChange = (value) => {
  userOverrodeSelectedFilter.value = true
  showSelectedOnly.value = value
}

// Helper function to check if a value is truthy (handles boolean, integer, string, and SQL Server BIT type)
const isTruthy = (value) => {
  if (value === null || value === undefined || value === false || value === 0 || value === '0' || value === 'false') {
    return false
  }
  // Check for truthy values
  return value === true || value === 1 || value === '1' || String(value).toLowerCase() === 'true' || Number(value) === 1
}

const deriveNextStepId = (emp) => {
  const stored = Number(emp?.new_salary_step_id)
  if (!Number.isNaN(stored) && stored > 0) {
    return stored
  }

  const currentStep = Number(emp?.salary_step_id)
  if (!Number.isNaN(currentStep)) {
    return currentStep + 1
  }

  return null
}

const fetchRowSalaryFromSchedule = async (row, { force = false } = {}) => {
  if (!row || !row.employee_id) return

  const nextStepId = deriveNextStepId(row)
  row.new_salary_step_id = nextStepId

  if (!nextStepId) return

  const currentSalary = Number(row.salary || 0)
  const existingNewSalary = Number(row.new_salary || 0)

  if (!force && existingNewSalary > currentSalary) {
    return
  }

  try {
    const res = await stepIncrementApi.getEmployeeSalary(nextStepId, row.employee_id)
    const scheduleSalary = Number(res.data?.data?.salary || 0)
    if (scheduleSalary > 0) {
      row.new_salary = scheduleSalary
    }
  } catch (error) {
    console.error('Failed to fetch salary from schedule for employee:', row.employee_id, error)
    ElMessage.error('Failed to fetch salary from salary schedule. Please verify setup.')
  }
}

// Check if step increment is approved or rejected
const isApproved = computed(() => {
  // First check stepIncrementData (most reliable source - contains all step_increments for this period)
  if (stepIncrementData.value && stepIncrementData.value.length > 0) {
    // Check if any record in stepIncrementData is approved (they should all have the same status)
    const approved = stepIncrementData.value.some(data => isTruthy(data?.is_approved))
    if (approved) {
      return true
    }
  }
  
  // Fallback to checking employees array
  if (employees.value && employees.value.length > 0) {
    // Check the first employee with step_increment_id (all should have the same status for the period)
    const firstEmployee = employees.value.find(emp => emp.step_increment_id && emp.step_increment_id > 0)
    if (firstEmployee && isTruthy(firstEmployee.is_approved)) {
      return true
    }
  }
  
  return false
})

const isRejected = computed(() => {
  // First check stepIncrementData (most reliable source - contains all step_increments for this period)
  if (stepIncrementData.value && stepIncrementData.value.length > 0) {
    // Check if any record in stepIncrementData is rejected (they should all have the same status)
    const rejected = stepIncrementData.value.some(data => isTruthy(data?.is_disapproved))
    if (rejected) {
      return true
    }
  }
  
  // Fallback to checking employees array
  if (employees.value && employees.value.length > 0) {
    // Check the first employee with step_increment_id (all should have the same status for the period)
    const firstEmployee = employees.value.find(emp => emp.step_increment_id && emp.step_increment_id > 0)
    if (firstEmployee && isTruthy(firstEmployee.is_disapproved)) {
      return true
    }
  }
  
  return false
})

// Methods
const onMonthYearChange = async () => {
  // When month or year changes, load employees for that period
  if (form.month_id && form.year_id) {
    // Update form with selected month name
    const selectedMonth = months.value.find(m => m.id === form.month_id)
    if (selectedMonth) {
      form.month = selectedMonth.name
    }
    form.year = form.year_id.toString()
    
    // Load employees for the selected period
    await loadEmployeeData()
  } else {
    // Clear employees if month/year not fully selected
    employees.value = []
  }
}

const formatDateOnly = (value) => {
  if (!value) return ''
  if (typeof value === 'string') {
    return value.length > 10 ? value.slice(0, 10) : value
  }
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return ''
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

const loadEmployeeData = async () => {
  const monthId = props.monthId || form.month_id
  const yearId = props.yearId || form.year_id
  
  if (monthId && yearId) {
    try {
      // Use the edit route to load existing step increment data for this month/year
      const data = await loadStepIncrementData(monthId, yearId)
      
      // Load salary grades from employee data
      // Create a map to preserve grade IDs and names
      const gradeMap = new Map()
      data.employees?.forEach(emp => {
        if (emp.salary_grade && emp.salary_grade_id) {
          gradeMap.set(emp.salary_grade_id, emp.salary_grade)
        }
      })
      salaryGrades.value = Array.from(gradeMap.entries()).map(([id, name]) => ({
        id: Number(id),
        name: name
      })).sort((a, b) => a.id - b.id)
      
      // Set date of effectivity from step increment data
      stepIncrementData.value = data.step_increment_data || []
      if (stepIncrementData.value && stepIncrementData.value.length > 0) {
        form.date_of_effectivity = formatDateOnly(stepIncrementData.value[0].effectivity_date) || form.date_of_effectivity
      }
      
      // Filter to only show employees that were selected for this step increment
      // When viewing an existing step increment, only show employees with step_increment_id > 0
      const hasExistingData = stepIncrementData.value && stepIncrementData.value.length > 0
      const hasPersistedSelections = Array.isArray(data.employees)
        ? data.employees.some(emp => Number(emp.step_increment_id) > 0)
        : false

      const shouldShowOnlySelected = hasExistingData || hasPersistedSelections

      const filteredEmployees = shouldShowOnlySelected
        ? (data.employees || []).filter(emp => Number(emp.step_increment_id) > 0)
        : data.employees
      
      // Map employees with their selection status and initialize new salary fields
      employees.value = (filteredEmployees || []).map(emp => {
        // Use new_salary from API if available (from salary schedule)
        // For existing records, use saved new_salary
        // For new records, salary will be fetched from schedule when step is selected
        const calculatedNewSalary = emp.new_salary !== null && emp.new_salary !== undefined 
          ? Number(emp.new_salary) 
          : (emp.salary ? Number(emp.salary) : 0)
        
        const newStepId = emp.new_salary_step_id || (emp.salary_step_id ? Number(emp.salary_step_id) + 1 : null)
        
        return {
          ...emp,
          select: hasExistingData ? true : (emp.select || false), // Auto-select when viewing existing data
          // Only set default values if they don't already exist (for new employees)
          new_salary_grade_id: emp.new_salary_grade_id || emp.salary_grade_id,
          // Step increment moves to next step (use existing or default)
          new_salary_step_id: newStepId,
          new_salary: calculatedNewSalary
        }
      })
      
      await Promise.all(
        employees.value.map(emp => fetchRowSalaryFromSchedule(emp, { force: false }))
      )
      
      updateSelectAllState()
    } catch (error) {
      console.error('Failed to load step increment data:', error)
      // Fallback to loadEmployees if edit route fails (for new periods)
      try {
        const fallbackData = await loadEmployees(monthId, yearId)
        
        // Create salary grades from employee data
        const gradeMap = new Map()
        fallbackData.employees.forEach(emp => {
          if (emp.salary_grade && emp.salary_grade_id) {
            gradeMap.set(emp.salary_grade_id, emp.salary_grade)
          }
        })
        salaryGrades.value = Array.from(gradeMap.entries()).map(([id, name]) => ({
          id: Number(id),
          name: name
        })).sort((a, b) => a.id - b.id)
        
        employees.value = fallbackData.employees.map(emp => {
          // Use new_salary from API if available (absolute salary from salary schedule for new step)
          // Otherwise fall back to current salary (will be fetched from schedule when step is selected)
          const calculatedNewSalary = emp.new_salary !== null && emp.new_salary !== undefined 
            ? Number(emp.new_salary) 
            : (emp.salary ? Number(emp.salary) : 0)
          
          const newStepId = deriveNextStepId(emp)
          
          return {
            ...emp,
            select: Boolean(emp.select),
            // Step increment keeps same salary grade
            new_salary_grade_id: emp.salary_grade_id,
            // Step increment moves to next step
            new_salary_step_id: newStepId,
            new_salary: calculatedNewSalary
          }
        })
        
        await Promise.all(
          employees.value.map(emp => fetchRowSalaryFromSchedule(emp, { force: true }))
        )
        updateSelectAllState()
      } catch (fallbackError) {
        console.error('Fallback also failed:', fallbackError)
      }
    }
  }
}

const onSelectAllChange = (value) => {
  employees.value.forEach(emp => {
    emp.select = value
  })
  indeterminate.value = false
}

const onEmployeeSelectChange = () => {
  updateSelectAllState()
}

const updateSelectAllState = () => {
  const selectedCount = employees.value.filter(emp => emp.select).length
  const totalCount = employees.value.length
  
  selectAll.value = selectedCount === totalCount && totalCount > 0
  indeterminate.value = selectedCount > 0 && selectedCount < totalCount
}

const getSelectedEmployees = () => {
  return employees.value.filter(emp => emp.select)
}


const removeEmployeeFromList = (index) => {
  const employeeName = employees.value[index].name
  employees.value.splice(index, 1)
  updateSelectAllState()
  ElMessage.success(`Removed ${employeeName} from step increment list`)
}

const onSave = async () => {
  const selectedEmployees = getSelectedEmployees()
  
  if (selectedEmployees.length === 0) {
    ElMessage.warning('Please select at least one employee for step increment.')
    return
  }
  
  if (!form.date_of_effectivity) {
    ElMessage.warning('Please select the date of effectivity.')
    return
  }
  
  // Validate that all selected employees have new salary higher than current salary
  const invalidEmployees = selectedEmployees.filter(emp => {
    const currentSalary = Number(emp.salary || 0)
    const newSalary = Number(emp.new_salary || 0)
    return newSalary <= currentSalary
  })
  
  if (invalidEmployees.length > 0) {
    const names = invalidEmployees.map(emp => emp.name).join(', ')
    ElMessage.error(
      `Cannot save: The following employees have new salary that is not higher than current salary: ${names}. ` +
      `Please check the salary schedule setup or select a higher step.`
    )
    return
  }
  
  saving.value = true
  try {
    // Prepare payload for the existing API
    const payload = {
      month_id: form.month_id,
      year_id: form.year_id,
      date_of_effectivity: formatDateOnly(form.date_of_effectivity),
      employee_id: selectedEmployees.map(emp => emp.employee_id),
      step_increment_id: selectedEmployees.map(emp => emp.step_increment_id || 0),
      // Backend expects selected employee IDs in 'select' (used by in_array check)
      select: selectedEmployees.map(emp => emp.employee_id),
      // New salary information
      current_salary_grade_id: selectedEmployees.map(emp => emp.salary_grade_id),
      current_salary_step_id: selectedEmployees.map(emp => emp.salary_step_id),
      current_salary: selectedEmployees.map(emp => emp.salary),
      new_salary_grade_id: selectedEmployees.map(emp => emp.new_salary_grade_id),
      new_salary_step: selectedEmployees.map(emp => emp.new_salary_step_id),
      new_salary: selectedEmployees.map(emp => {
        // Ensure salary is sent as a number, not string
        const salary = emp.new_salary
        return salary !== null && salary !== undefined ? Number(salary) : null
      })
    }
    
    await saveStepIncrement(payload)
    emit('saved')
    visible.value = false
  } catch (error) {
    // Error handling is done in the composable
  } finally {
    saving.value = false
  }
}

const onForward = async () => {
  const selectedEmployees = getSelectedEmployees()
  
  if (selectedEmployees.length === 0) {
    ElMessage.warning('Please select at least one employee for step increment.')
    return
  }
  
  if (!form.date_of_effectivity) {
    ElMessage.warning('Please select the date of effectivity.')
    return
  }
  
  saving.value = true
  try {
    // First save the step increment
    const payload = {
      month_id: form.month_id,
      year_id: form.year_id,
      date_of_effectivity: formatDateOnly(form.date_of_effectivity),
      employee_id: selectedEmployees.map(emp => emp.employee_id),
      step_increment_id: selectedEmployees.map(emp => emp.step_increment_id || 0),
      // Backend expects selected employee IDs in 'select'
      select: selectedEmployees.map(emp => emp.employee_id),
      // New salary information
      current_salary_grade_id: selectedEmployees.map(emp => emp.salary_grade_id),
      current_salary_step_id: selectedEmployees.map(emp => emp.salary_step_id),
      current_salary: selectedEmployees.map(emp => emp.salary),
      new_salary_grade_id: selectedEmployees.map(emp => emp.new_salary_grade_id),
      new_salary_step: selectedEmployees.map(emp => emp.new_salary_step_id),
      new_salary: selectedEmployees.map(emp => {
        // Ensure salary is sent as a number, not string
        const salary = emp.new_salary
        return salary !== null && salary !== undefined ? Number(salary) : null
      })
    }
    
    await saveStepIncrement(payload)
    
    // Then forward to approver
    await forwardStepIncrement(form.month_id, form.year_id)
    
    emit('saved')
    visible.value = false
  } catch (error) {
    // Error handling is done in the composables
  } finally {
    saving.value = false
  }
}

// Watch for dialog visibility changes
watch(visible, (newValue) => {
  if (newValue) {
    form.month = props.month
    form.year = props.year
    form.month_id = props.monthId
    form.year_id = props.yearId
    form.date_of_effectivity = ''
    loadEmployeeData()
  } else {
    // Reset data when dialog closes
    stepIncrementData.value = []
    employees.value = []
  }
})
</script>

<style scoped>
.text-center { text-align: center; }
.py-8 { padding-top: 32px; padding-bottom: 32px; }
.mt-2 { margin-top: 8px; }
.mb-4 { margin-bottom: 16px; }

.employee-list-section {
  margin-top: 20px;
}

.employee-list-section h3 {
  margin-bottom: 16px;
  font-size: 16px;
  font-weight: 600;
  color: #303133;
}

.list-toolbar {
  display: flex;
  align-items: flex-end;
  gap: 16px;
  flex-wrap: wrap;
}

.selected-filter {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.selected-filter .filter-label {
  font-size: 12px;
  color: #606266;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.selected-filter .filter-hint {
  font-size: 12px;
  color: #909399;
}

.new-step-display {
  display: flex;
  flex-direction: column;
  line-height: 1.2;
}

.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
}

.el-form-item {
  margin-bottom: 16px;
}

.el-card {
  border: 1px solid #e4e7ed;
  border-radius: 8px;
}

.no-employees-message {
  margin: 20px 0;
  text-align: center;
}

.no-employees-message ul {
  text-align: left;
  display: inline-block;
  margin-top: 10px;
}

.no-employees-message li {
  margin-bottom: 5px;
  color: #666;
}
</style>