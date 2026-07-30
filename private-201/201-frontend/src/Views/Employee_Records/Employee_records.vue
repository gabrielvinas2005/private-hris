<template>
  <PageScaffold
    title="Employee Records"
    subtitle="Manage employee profiles and personal information"
  >
    <!-- Employee List Component -->
    <EmployeeList
      ref="employeeListRef"
      :departments="departments"
      @view-employee="handleViewEmployee"
      @edit-employee="handleEditEmployee"
      @add-employee="handleAddEmployee"
    />

    <!-- Employee Form Dialog -->
    <EmployeeForm
      v-model="showForm"
      :employee-data="selectedEmployee"
      :form-options="formOptions"
      :related-data="relatedData"
      @saved="handleEmployeeSaved"
    />

    <!-- Employee Details Dialog -->
    <EmployeeDetails
      :key="detailsKey"
      v-model="showDetails"
      :employee-data="selectedEmployee"
      :form-options="formOptions"
      :related-data="relatedData"
      @edit-employee="handleEditEmployee"
    />
  </PageScaffold>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue'
import { ElMessage } from 'element-plus'
import PageScaffold from '../../components/PageScaffold.vue'
import EmployeeList from '../../components/Employee_Records/EmployeeList.vue'
import EmployeeForm from '../../components/Employee_Records/EmployeeForm.vue'
import EmployeeDetails from '../../components/Employee_Records/EmployeeDetails.vue'
import { useEmployee } from '../../composable/useEmployee'

// Composables
const { fetchEmployee, formData } = useEmployee()

// Reactive data
const showForm = ref(false)
const showDetails = ref(false)
const selectedEmployee = ref(null)
const detailsKey = ref(0) // Key to force component remount
const departments = ref([])
const employeeListRef = ref(null)
const formOptions = ref({
  prefixes: [],
  suffixes: [],
  genders: [],
  civil_status: [],
  citizenships: [],
  religions: [],
  blood_types: [],
  companies: [],
  departments: [],
  employment_types: [],
  positions: []
})
const relatedData = ref({})

// Methods
const handleViewEmployee = async (employee) => {
  try {
    // Fetch full employee data with form options
    const data = await fetchEmployee(employee.id)
    
    // Validate data before setting
    if (!data || !data.employee_info || !Array.isArray(data.employee_info) || data.employee_info.length === 0) {
      ElMessage.error('Employee data not found')
      return
    }
    
    // Set all data
    selectedEmployee.value = data.employee_info[0]
    formOptions.value = data.form_data || {}
    relatedData.value = data.related_data || {}
    
    // Increment key to force component remount and show dialog
    detailsKey.value++
    showDetails.value = true
  } catch (error) {
    console.error('Error loading employee details:', error)
    ElMessage.error('Failed to load employee details')
  }
}

const handleEditEmployee = async (employee) => {
  try {
    // Close details dialog if open
    showDetails.value = false
    
    // Fetch full employee data with form options
    const data = await fetchEmployee(employee.id)
    selectedEmployee.value = data.employee_info[0]
    formOptions.value = data.form_data
    relatedData.value = data.related_data || {}
    showForm.value = true
  } catch (error) {
    ElMessage.error('Failed to load employee data for editing')
  }
}

const handleAddEmployee = async () => {
  try {
    // Fetch form options for new employee
    const data = await fetchEmployee(0)
    console.log('Form options received:', {
      hasCities: !!data.form_data?.cities,
      citiesCount: data.form_data?.cities?.length || 0
    })
    formOptions.value = data.form_data
    relatedData.value = data.related_data || {}
    selectedEmployee.value = null
    showForm.value = true
  } catch (error) {
    console.error('Failed to load form options:', error)
    ElMessage.error('Failed to load form options')
  }
}

const handleEmployeeSaved = async () => {
  try {
    if (employeeListRef.value?.refreshData) {
      await employeeListRef.value.refreshData()
    }
    ElMessage.success('Employee saved successfully')
  } catch (error) {
    console.error('Failed to refresh employee list:', error)
    ElMessage.warning('Employee saved, but the list did not refresh automatically.')
  }
}

// Lifecycle
onMounted(async () => {
  try {
    // Load initial form options
    const data = await fetchEmployee(0)
    formOptions.value = data.form_data
    departments.value = data.form_data.departments || []
    relatedData.value = data.related_data || {}
  } catch (error) {
    console.error('Failed to load initial data:', error)
  }
})
</script>

<style scoped>
/* Add any specific styles here if needed */
</style>

