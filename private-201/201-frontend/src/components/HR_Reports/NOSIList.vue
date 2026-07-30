<template>
  <PageScaffold 
    title="Salary Step Increment (NOSI)"
    subtitle="Generate NOSI documents for employees with approved step increments"
  >

    <!-- Employee Selection and Form -->
    <div class="bg-white rounded-lg shadow mb-6">
      <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">Employee Selection & Document Details</h3>
        <p class="text-sm text-gray-500 mt-1">Select an employee and fill in the required information</p>
      </div>

      <div class="p-6">
        <el-form 
          ref="nosiFormRef" 
          :model="formData" 
          :rules="formRules" 
          label-width="150px"
          class="nosi-form"
        >
          <!-- Employee Selection -->
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <el-form-item label="Employee" prop="employee" class="lg:col-span-2">
              <el-select
                v-model="formData.employee"
                placeholder="Select employee with approved step increment"
                filterable
                class="w-full"
                :loading="loading"
                @change="handleEmployeeChange"
              >
                <el-option
                  v-for="emp in (employees || [])"
                  :key="getEmployeeIdentifier(emp)"
                  :label="emp.name"
                  :value="getEmployeeIdentifier(emp)"
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
                <span class="font-medium text-gray-600">Current Salary:</span>
                <span class="ml-2">₱{{ formatCurrency(selectedEmployee.current_salary) }}</span>
              </div>
              <div>
                <span class="font-medium text-gray-600">New Salary:</span>
                <span class="ml-2">₱{{ formatCurrency(selectedEmployee.new_salary) }}</span>
              </div>
              <div>
                <span class="font-medium text-gray-600">Salary Increase:</span>
                <span class="ml-2 text-green-600 font-medium">₱{{ formatCurrency(selectedEmployee.new_salary - selectedEmployee.current_salary) }}</span>
              </div>
            </div>
          </div>

          <!-- Signatory Information -->
          <div class="border-t pt-6 mb-6">
            <h4 class="font-medium text-gray-900 mb-4">Signatory Information</h4>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
              <el-form-item label="Signatory Name" prop="signatory">
                <el-input
                  v-model="formData.signatory"
                  placeholder="Enter signatory name"
                  clearable
                />
              </el-form-item>

              <el-form-item label="Position" prop="position">
                <el-input
                  v-model="formData.position"
                  placeholder="Enter signatory position"
                  clearable
                />
              </el-form-item>
            </div>
          </div>

          <!-- Document Information -->
          <div class="border-t pt-6 mb-6">
            <h4 class="font-medium text-gray-900 mb-4">Document Information</h4>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
              <el-form-item label="Joint Number" prop="joint_no">
                <el-input
                  v-model="formData.joint_no"
                  placeholder="Enter joint number"
                  clearable
                />
              </el-form-item>

              <el-form-item label="Joint Date" prop="joint_date">
                <el-date-picker
                  v-model="formData.joint_date"
                  type="date"
                  placeholder="Select joint date"
                  class="w-full"
                  format="YYYY-MM-DD"
                  value-format="YYYY-MM-DD"
                />
              </el-form-item>

              <el-form-item label="Salary As Of" prop="salary_as_of">
                <el-date-picker
                  v-model="formData.salary_as_of"
                  type="date"
                  placeholder="Select salary effective date"
                  class="w-full"
                  format="YYYY-MM-DD"
                  value-format="YYYY-MM-DD"
                />
              </el-form-item>
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="border-t pt-6">
            <div class="flex items-center justify-between">
              <div class="text-sm text-gray-500">
                <span v-if="selectedEmployee">
                  Ready to generate NOSI for {{ selectedEmployee.name }}
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
                  :disabled="!formData.employee"
                  @click="handlePreviewNOSI"
                >
                  <el-icon><View /></el-icon>
                  Preview NOSI
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
          <div class="text-xs text-gray-400 mt-1">With approved step increments</div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="text-center">
          <div class="text-3xl font-bold text-green-600 mb-2">{{ averageIncrease }}</div>
          <div class="text-sm font-medium text-gray-500 uppercase tracking-wide">AVERAGE INCREASE</div>
          <div class="text-xs text-gray-400 mt-1">Salary step increment</div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="text-center">
          <div class="text-3xl font-bold text-purple-600 mb-2">{{ totalIncrease }}</div>
          <div class="text-sm font-medium text-gray-500 uppercase tracking-wide">TOTAL INCREASE</div>
          <div class="text-xs text-gray-400 mt-1">All approved increments</div>
        </div>
      </div>
    </div>

    <!-- NOSI Preview Modal -->
    <NOSIPreviewModal
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
import NOSIPreviewModal from './NOSIPreviewModal.vue'

const props = defineProps({
  employees: {
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
  onPreviewNOSI: {
    type: Function,
    default: () => {}
  }
})

// Form references and data
const nosiFormRef = ref()
const formData = reactive({
  employee: '',
  signatory: '',
  position: '',
  joint_no: '',
  joint_date: '',
  salary_as_of: '',
  employeeName: ''
})

// Form validation rules
const formRules = {
  employee: [
    { required: true, message: 'Please select an employee', trigger: 'change' }
  ],
  signatory: [
    { required: true, message: 'Please enter signatory name', trigger: 'blur' }
  ],
  position: [
    { required: true, message: 'Please enter signatory position', trigger: 'blur' }
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
const getEmployeeIdentifier = (employee) => {
  if (!employee) return null
  return employee.employee_id ?? employee.employeeId ?? employee.id
}

const selectedEmployee = computed(() => {
  if (!formData.employee || !Array.isArray(props.employees)) return null
  return props.employees.find(emp => getEmployeeIdentifier(emp) === formData.employee)
})

const totalEmployees = computed(() => {
  return Array.isArray(props.employees) ? props.employees.length : 0
})

const averageIncrease = computed(() => {
  if (!Array.isArray(props.employees) || props.employees.length === 0) return '₱0'
  const total = props.employees.reduce((sum, emp) => sum + ((emp.new_salary || 0) - (emp.current_salary || 0)), 0)
  const avg = total / props.employees.length
  return `₱${formatCurrency(avg)}`
})

const totalIncrease = computed(() => {
  if (!Array.isArray(props.employees)) return '₱0'
  const total = props.employees.reduce((sum, emp) => sum + ((emp.new_salary || 0) - (emp.current_salary || 0)), 0)
  return `₱${formatCurrency(total)}`
})

// Methods
const formatCurrency = (amount) => {
  if (!amount) return '0.00'
  return new Intl.NumberFormat('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(amount)
}

const handleEmployeeChange = (employeeId) => {
  if (!Array.isArray(props.employees)) return
  const employee = props.employees.find(emp => getEmployeeIdentifier(emp) === employeeId)
  if (employee) {
    formData.employeeName = employee.name
  }
}

const resetForm = () => {
  Object.assign(formData, {
    employee: '',
    signatory: '',
    position: '',
    joint_no: '',
    joint_date: '',
    salary_as_of: '',
    employeeName: ''
  })
  nosiFormRef.value?.clearValidate()
}

const handlePreviewNOSI = async () => {
  try {
    const valid = await nosiFormRef.value?.validate()
    if (!valid) return

    const result = await props.onPreviewNOSI(formData)
    if (result) {
      previewData.value = result
      showPreviewModal.value = true
    }
  } catch (error) {
    console.error('Preview NOSI error:', error)
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
.nosi-form :deep(.el-form-item__label) {
  font-weight: 500;
  color: #374151;
}

.nosi-form :deep(.el-select) {
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
  .lg\:col-span-2 {
    grid-column: span 2 / span 2;
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
