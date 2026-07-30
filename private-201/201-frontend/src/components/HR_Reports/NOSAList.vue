<template>
  <PageScaffold 
    title="Salary Adjustment (NOSA)"
    subtitle="Generate NOSA documents for plantilla employees with salary adjustments"
  >

    <!-- Employee Selection and Form -->
    <div class="bg-white rounded-lg shadow mb-6">
      <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">Employee Selection & Document Details</h3>
        <p class="text-sm text-gray-500 mt-1">Select salary schedule, employee and fill in the required information</p>
      </div>

      <div class="p-6">
        <el-form 
          ref="nosaFormRef" 
          :model="formData" 
          :rules="formRules" 
          label-width="200px"
          class="nosa-form"
        >
          <!-- Salary Schedule Selection -->
          <div class="mb-6">
            <el-form-item label="Salary Schedule:" prop="salary_schedule_id">
              <el-select
                v-model="formData.salary_schedule_id"
                placeholder="Select salary schedule"
                filterable
                class="w-full"
                @change="handleSalaryScheduleChange"
              >
                <el-option
                  v-for="schedule in salarySchedules"
                  :key="schedule.id"
                  :label="schedule.name"
                  :value="schedule.id"
                />
              </el-select>
            </el-form-item>
          </div>

          <!-- Employee Selection -->
          <div class="mb-6">
            <el-form-item label="Employee:" prop="employee">
              <el-select
                v-model="formData.employee"
                placeholder="Select employee"
                filterable
                class="w-full"
                :loading="loading"
                :disabled="!formData.salary_schedule_id"
                @change="handleEmployeeChange"
              >
                <el-option
                  v-for="emp in filteredEmployees"
                  :key="emp.id"
                  :label="emp.name"
                  :value="emp.id"
                >
                  <div class="flex items-center justify-between">
                    <span>{{ emp.name }}</span>
                    <span class="text-sm text-gray-500">{{ emp.department }}</span>
                  </div>
                </el-option>
              </el-select>
            </el-form-item>
          </div>

          <!-- Selected Employee Details -->
          <div v-if="selectedEmployee" class="bg-gray-50 rounded-lg p-4 mb-6">
            <h4 class="font-medium text-gray-900 mb-3">Selected Employee Details</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
              <div>
                <span class="font-medium text-gray-600">Employee No:</span>
                <span class="ml-2">{{ selectedEmployee.employee_no }}</span>
              </div>
              <div>
                <span class="font-medium text-gray-600">Position:</span>
                <span class="ml-2">{{ selectedEmployee.position }}</span>
              </div>
              <div>
                <span class="font-medium text-gray-600">Department:</span>
                <span class="ml-2">{{ selectedEmployee.department }}</span>
              </div>
              <div>
                <span class="font-medium text-gray-600">Employment Type:</span>
                <span class="ml-2">{{ selectedEmployee.employment_type }}</span>
              </div>
              <div>
                <span class="font-medium text-gray-600">Branch:</span>
                <span class="ml-2">{{ selectedEmployee.branch || 'N/A' }}</span>
              </div>
            </div>
          </div>

          <!-- Specify Report Signatory -->
          <div class="border-t pt-6 mb-6">
            <h4 class="font-medium text-gray-900 mb-4">Specify Report Signatory:</h4>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
              <el-form-item label="Signatory Name:" prop="signatory">
                <el-input
                  v-model="formData.signatory"
                  placeholder="Enter signatory name"
                  clearable
                />
              </el-form-item>

              <el-form-item label="Signatory Position:" prop="position">
                <el-input
                  v-model="formData.position"
                  placeholder="Enter signatory position"
                  clearable
                />
              </el-form-item>
            </div>
          </div>

          <!-- Administrator -->
          <div class="border-t pt-6 mb-6">
            <h4 class="font-medium text-gray-900 mb-4">Administrator:</h4>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
              <el-form-item label="Recommending Approval Name:" prop="signatory_admin">
                <el-input
                  v-model="formData.signatory_admin"
                  placeholder="Enter recommending approval name"
                  clearable
                />
              </el-form-item>

              <el-form-item label="Recommending Approval Position:" prop="position_admin">
                <el-input
                  v-model="formData.position_admin"
                  placeholder="Enter recommending approval position"
                  clearable
                />
              </el-form-item>
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="border-t pt-6">
            <div class="flex items-center justify-between">
              <div class="text-sm text-gray-500">
                <span v-if="selectedEmployee && formData.salary_schedule_id">
                  Ready to generate NOSA for {{ selectedEmployee.name }}
                </span>
                <span v-else-if="!formData.salary_schedule_id">
                  Please select a salary schedule first
                </span>
                <span v-else>
                  Please select an employee to continue
                </span>
              </div>
              <div class="space-x-3">
                <el-button @click="resetForm">
                  <el-icon><Refresh /></el-icon>
                  Reset Form
                </el-button>
                <el-button
                  type="primary"
                  :loading="generateLoading"
                  :disabled="!formData.employee || !formData.salary_schedule_id"
                  @click="handlePreviewNOSA"
                >
                  <el-icon><View /></el-icon>
                  Preview NOSA
                </el-button>
              </div>
            </div>
          </div>
        </el-form>
      </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="text-center">
          <div class="text-3xl font-bold text-blue-600 mb-2">{{ totalEmployees }}</div>
          <div class="text-sm font-medium text-gray-500 uppercase tracking-wide">TOTAL EMPLOYEES</div>
          <div class="text-xs text-gray-400 mt-1">Plantilla employees</div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="text-center">
          <div class="text-3xl font-bold text-green-600 mb-2">{{ totalSchedules }}</div>
          <div class="text-sm font-medium text-gray-500 uppercase tracking-wide">SALARY SCHEDULES</div>
          <div class="text-xs text-gray-400 mt-1">Available schedules</div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="text-center">
          <div class="text-3xl font-bold text-purple-600 mb-2">{{ selectedScheduleName }}</div>
          <div class="text-sm font-medium text-gray-500 uppercase tracking-wide">SELECTED SCHEDULE</div>
          <div class="text-xs text-gray-400 mt-1">Current selection</div>
        </div>
      </div>
    </div>

    <!-- NOSA Preview Modal -->
    <NOSAPreviewModal
      v-model="showPreviewModal"
      :pdf-url="previewData.pdfUrl"
      :pdf-blob="previewData.blob"
      :filename="previewData.filename"
      :employee-name="previewData.employeeName"
      :loading="generateLoading"
      :on-download="handleDownloadFromModal"
      @close="handleClosePreview"
    />
  </PageScaffold>
</template>

<script setup>
import { ref, computed, reactive } from 'vue'
import { ElMessage } from 'element-plus'
import { 
  InfoFilled, Refresh, View
} from '@element-plus/icons-vue'
import PageScaffold from '@/components/PageScaffold.vue'
import NOSAPreviewModal from './NOSAPreviewModal.vue'

const props = defineProps({
  employees: {
    type: Array,
    default: () => []
  },
  salarySchedules: {
    type: Array,
    default: () => []
  },
  loading: {
    type: Boolean,
    default: false
  },
  generateLoading: {
    type: Boolean,
    default: false
  },
  onPreviewNOSA: {
    type: Function,
    default: () => {}
  }
})

// Form references and data
const nosaFormRef = ref()
const formData = reactive({
  salary_schedule_id: '',
  employee: '',
  signatory: '',
  position: '',
  signatory_admin: '',
  position_admin: '',
  employeeName: ''
})

// Form validation rules
const formRules = {
  salary_schedule_id: [
    { required: true, message: 'Please select a salary schedule', trigger: 'change' }
  ],
  employee: [
    { required: true, message: 'Please select an employee', trigger: 'change' }
  ],
  signatory: [
    { required: true, message: 'Please enter signatory name', trigger: 'blur' }
  ],
  position: [
    { required: true, message: 'Please enter signatory position', trigger: 'blur' }
  ],
  signatory_admin: [
    { required: true, message: 'Please enter admin signatory name', trigger: 'blur' }
  ],
  position_admin: [
    { required: true, message: 'Please enter admin signatory position', trigger: 'blur' }
  ]
}

// Preview modal state
const showPreviewModal = ref(false)
const previewData = ref({
  pdfUrl: '',
  blob: null,
  filename: '',
  employeeName: ''
})

// Computed properties
const filteredEmployees = computed(() => {
  if (!formData.salary_schedule_id || !Array.isArray(props.employees)) return []
  // For NOSA, all plantilla employees are eligible regardless of salary schedule
  return props.employees
})

const selectedEmployee = computed(() => {
  if (!formData.employee || !Array.isArray(filteredEmployees.value)) return null
  return filteredEmployees.value.find(emp => emp.id === formData.employee)
})

const totalEmployees = computed(() => {
  return Array.isArray(props.employees) ? props.employees.length : 0
})

const totalSchedules = computed(() => {
  return Array.isArray(props.salarySchedules) ? props.salarySchedules.length : 0
})

const selectedScheduleName = computed(() => {
  if (!formData.salary_schedule_id || !Array.isArray(props.salarySchedules)) return 'None'
  const schedule = props.salarySchedules.find(s => s.id === formData.salary_schedule_id)
  return schedule ? schedule.name.substring(0, 20) + '...' : 'None'
})

// Methods
const handleSalaryScheduleChange = (scheduleId) => {
  // Reset employee selection when salary schedule changes
  formData.employee = ''
  formData.employeeName = ''
}

const handleEmployeeChange = (employeeId) => {
  if (!Array.isArray(filteredEmployees.value)) return
  const employee = filteredEmployees.value.find(emp => emp.id === employeeId)
  if (employee) {
    formData.employeeName = employee.name
  }
}

const resetForm = () => {
  Object.assign(formData, {
    salary_schedule_id: '',
    employee: '',
    signatory: '',
    position: '',
    signatory_admin: '',
    position_admin: '',
    employeeName: ''
  })
  nosaFormRef.value?.clearValidate()
}

const handlePreviewNOSA = async () => {
  try {
    const valid = await nosaFormRef.value?.validate()
    if (!valid) return

    const result = await props.onPreviewNOSA(formData)
    if (result) {
      previewData.value = result
      showPreviewModal.value = true
    }
  } catch (error) {
    console.error('Preview NOSA error:', error)
  }
}

// Preview modal handlers
const handleClosePreview = () => {
  showPreviewModal.value = false
  // Clean up blob URL
  if (previewData.value.pdfUrl && previewData.value.pdfUrl.startsWith('blob:')) {
    window.URL.revokeObjectURL(previewData.value.pdfUrl)
  }
  previewData.value = {
    pdfUrl: '',
    blob: null,
    filename: '',
    employeeName: ''
  }
}

const handleDownloadFromModal = (blob, filename) => {
  const url = window.URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = filename
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  window.URL.revokeObjectURL(url)
}
</script>

<style scoped>
/* Form Styles */
.nosa-form :deep(.el-form-item__label) {
  font-weight: 500;
  color: #374151;
}

.nosa-form :deep(.el-select) {
  width: 100%;
}

/* Grid Utilities */
.grid {
  display: grid;
}

.grid-cols-1 {
  grid-template-columns: repeat(1, minmax(0, 1fr));
}

@media (min-width: 768px) {
  .grid-cols-1.md\:grid-cols-2 {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
  .grid-cols-1.md\:grid-cols-3 {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}

@media (min-width: 1024px) {
  .grid-cols-1.lg\:grid-cols-2 {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
  .grid-cols-1.lg\:grid-cols-3 {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}

.gap-4 {
  gap: 1rem;
}

.gap-6 {
  gap: 1.5rem;
}

.mb-6 {
  margin-bottom: 1.5rem;
}

.mb-4 {
  margin-bottom: 1rem;
}

.mb-3 {
  margin-bottom: 0.75rem;
}

.mb-2 {
  margin-bottom: 0.5rem;
}

.mt-1 {
  margin-top: 0.25rem;
}

.pt-6 {
  padding-top: 1.5rem;
}

.p-6 {
  padding: 1.5rem;
}

.p-4 {
  padding: 1rem;
}

/* Utility Classes */
.shadow-sm {
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}

.border {
  border-width: 1px;
}

.border-t {
  border-top-width: 1px;
}

.border-b {
  border-bottom-width: 1px;
}

.border-gray-200 {
  border-color: rgb(229 231 235);
}

.border-blue-200 {
  border-color: rgb(191 219 254);
}

.rounded-lg {
  border-radius: 0.5rem;
}

.bg-white {
  background-color: rgb(255 255 255);
}

.bg-gray-50 {
  background-color: rgb(249 250 251);
}

.bg-blue-50 {
  background-color: rgb(239 246 255);
}

.text-center {
  text-align: center;
}

.text-lg {
  font-size: 1.125rem;
  line-height: 1.75rem;
}

.text-sm {
  font-size: 0.875rem;
  line-height: 1.25rem;
}

.text-xs {
  font-size: 0.75rem;
  line-height: 1rem;
}

.text-3xl {
  font-size: 1.875rem;
  line-height: 2.25rem;
}

.font-bold {
  font-weight: 700;
}

.font-medium {
  font-weight: 500;
}

.uppercase {
  text-transform: uppercase;
}

.tracking-wide {
  letter-spacing: 0.025em;
}

.text-gray-900 {
  color: rgb(17 24 39);
}

.text-gray-600 {
  color: rgb(75 85 99);
}

.text-gray-500 {
  color: rgb(107 114 128);
}

.text-gray-400 {
  color: rgb(156 163 175);
}

.text-blue-900 {
  color: rgb(30 58 138);
}

.text-blue-700 {
  color: rgb(29 78 216);
}

.text-blue-600 {
  color: rgb(37 99 235);
}

.text-green-600 {
  color: rgb(22 163 74);
}

.text-purple-600 {
  color: rgb(147 51 234);
}

.text-xl {
  font-size: 1.25rem;
  line-height: 1.75rem;
}

.flex {
  display: flex;
}

.items-center {
  align-items: center;
}

.items-start {
  align-items: flex-start;
}

.justify-between {
  justify-content: space-between;
}

.space-x-3 > * + * {
  margin-left: 0.75rem;
}

.space-x-2 > * + * {
  margin-left: 0.5rem;
}

.flex-shrink-0 {
  flex-shrink: 0;
}

.ml-2 {
  margin-left: 0.5rem;
}

.w-full {
  width: 100%;
}

.list-disc {
  list-style-type: disc;
}

.list-inside {
  list-style-position: inside;
}

.space-y-1 > * + * {
  margin-top: 0.25rem;
}
</style>
