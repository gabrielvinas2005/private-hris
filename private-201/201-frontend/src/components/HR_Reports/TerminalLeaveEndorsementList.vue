<template>
  <PageScaffold 
    title="Terminal Leave Endorsement"
    subtitle="Generate terminal leave endorsement documents for employees"
  >

    <!-- Employee Selection and Form -->
    <div class="bg-white rounded-lg shadow mb-6">
      <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">Employee Selection & Document Details</h3>
        <p class="text-sm text-gray-500 mt-1">Select an employee and provide the required signatory information</p>
      </div>

      <div class="p-6">
        <el-form 
          ref="tleFormRef" 
          :model="formData" 
          :rules="formRules" 
          label-width="180px"
          class="tle-form"
        >
          <!-- Employee Selection -->
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <el-form-item label="Employee:" prop="employee" class="lg:col-span-2">
              <el-select
                v-model="formData.employee"
                placeholder="Select employee for terminal leave endorsement"
                filterable
                remote
                :remote-method="filterEmployees"
                :loading="loading"
                clearable
                class="w-full"
                @change="handleEmployeeChange"
              >
                <el-option
                  v-for="employee in filteredEmployees"
                  :key="employee.id"
                  :label="employee.name"
                  :value="employee.id"
                />
              </el-select>
            </el-form-item>
          </div>

          <!-- Selected Employee Details -->
          <div v-if="selectedEmployee" class="mb-6">
            <div class="bg-gray-50 rounded-lg p-4">
              <h4 class="text-md font-medium text-gray-800 mb-3">Selected Employee Details</h4>
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                  <span class="text-sm font-medium text-gray-600">Name:</span>
                  <p class="text-sm text-gray-900">{{ selectedEmployee.name }}</p>
                </div>
                <div>
                  <span class="text-sm font-medium text-gray-600">Employee No:</span>
                  <p class="text-sm text-gray-900">{{ selectedEmployee.employee_no }}</p>
                </div>
                <div>
                  <span class="text-sm font-medium text-gray-600">Position:</span>
                  <p class="text-sm text-gray-900">{{ selectedEmployee.position || 'N/A' }}</p>
                </div>
                <div>
                  <span class="text-sm font-medium text-gray-600">Department:</span>
                  <p class="text-sm text-gray-900">{{ selectedEmployee.department || 'N/A' }}</p>
                </div>
                <div>
                  <span class="text-sm font-medium text-gray-600">Branch:</span>
                  <p class="text-sm text-gray-900">{{ selectedEmployee.branch || 'N/A' }}</p>
                </div>
                <div>
                  <span class="text-sm font-medium text-gray-600">Date Hired:</span>
                  <p class="text-sm text-gray-900">{{ formatDate(selectedEmployee.date_hired) }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Endorsement Data Display -->
          <div v-if="endorsementData" class="mb-6">
            <div class="bg-blue-50 rounded-lg p-4">
              <h4 class="text-md font-medium text-blue-800 mb-3">Endorsement Information</h4>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <span class="text-sm font-medium text-blue-600">Monthly Salary:</span>
                  <p class="text-sm text-blue-900">₱{{ formatCurrency(endorsementData.employees?.[0]?.salary) }}</p>
                </div>
                <div>
                  <span class="text-sm font-medium text-blue-600">Annual Salary:</span>
                  <p class="text-sm text-blue-900">₱{{ endorsementData.annual_salary }}</p>
                </div>
                <div class="md:col-span-2">
                  <span class="text-sm font-medium text-blue-600">Annual Salary (In Words):</span>
                  <p class="text-sm text-blue-900 capitalize">{{ endorsementData.salary_word }}</p>
                </div>
              </div>

              <!-- Additional Income -->
              <div v-if="endorsementData.incomes && endorsementData.incomes.length > 0" class="mt-4">
                <h5 class="text-sm font-medium text-blue-800 mb-2">Additional Income</h5>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                  <div v-for="income in endorsementData.incomes" :key="income.name" class="flex justify-between">
                    <span class="text-sm text-blue-700">{{ income.name }}:</span>
                    <span class="text-sm text-blue-900">₱{{ formatCurrency(income.amount) }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Signatory Information -->
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <el-form-item label="Signatory Name:" prop="signatory">
              <el-input
                v-model="formData.signatory"
                placeholder="Enter signatory name"
              />
            </el-form-item>

            <el-form-item label="Signatory Position:" prop="position">
              <el-input
                v-model="formData.position"
                placeholder="Enter signatory position"
              />
            </el-form-item>
          </div>

          <!-- Action Buttons -->
          <div class="flex justify-end space-x-4 mt-6">
            <el-button @click="resetForm">Reset</el-button>
            <el-button 
              type="primary" 
              @click="handlePreview"
              :loading="generateLoading"
              :disabled="!isFormValid"
            >
              <el-icon class="mr-2"><View /></el-icon>
              Preview Document
            </el-button>
          </div>
        </el-form>
      </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <el-icon class="text-blue-600 text-2xl"><User /></el-icon>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Total Employees</p>
            <p class="text-2xl font-semibold text-gray-900">{{ totalEmployees }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <el-icon class="text-green-600 text-2xl"><Document /></el-icon>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Active Employees</p>
            <p class="text-2xl font-semibold text-gray-900">{{ activeEmployees }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <el-icon class="text-yellow-600 text-2xl"><Files /></el-icon>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Documents Ready</p>
            <p class="text-2xl font-semibold text-gray-900">{{ selectedEmployee ? 1 : 0 }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <el-icon class="text-purple-600 text-2xl"><Printer /></el-icon>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Ready to Print</p>
            <p class="text-2xl font-semibold text-gray-900">{{ isFormValid ? 1 : 0 }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Terminal Leave Endorsement Preview Modal -->
    <TLEPreviewModal
      v-if="showPreviewModal"
      :visible="showPreviewModal"
      :pdf-url="previewPdfUrl"
      :employee-name="selectedEmployee?.name || 'Unknown'"
      :loading="generateLoading"
      @close="handleClosePreview"
      @download="handleDownload"
    />
  </PageScaffold>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { View, User, Document, Files, Printer } from '@element-plus/icons-vue'
import PageScaffold from '../PageScaffold.vue'
import TLEPreviewModal from './TLEPreviewModal.vue'
import { useTerminalLeaveEndorsement } from '../../composable/useTerminalLeaveEndorsement.js'

// Props
const props = defineProps({
  employees: {
    type: Array,
    default: () => []
  },
  loading: {
    type: Boolean,
    default: false
  }
})

// Emits
const emit = defineEmits(['preview', 'download'])

// Composable
const { 
  generateLoading, 
  endorsementData,
  fetchEndorsementData,
  generateTLEPreview,
  downloadPDFFromBlob
} = useTerminalLeaveEndorsement()

// Form data and validation
const tleFormRef = ref()
const formData = ref({
  employee: '',
  signatory: '',
  position: ''
})

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

// Local state
const filteredEmployees = ref([])
const showPreviewModal = ref(false)
const previewPdfUrl = ref('')

// Computed
const selectedEmployee = computed(() => {
  if (!formData.value.employee || !Array.isArray(props.employees)) return null
  return props.employees.find(emp => emp.id === formData.value.employee)
})

const isFormValid = computed(() => {
  return formData.value.employee && 
         formData.value.signatory.trim() && 
         formData.value.position.trim()
})

// Statistics computed properties
const totalEmployees = computed(() => {
  return Array.isArray(props.employees) ? props.employees.length : 0
})

const activeEmployees = computed(() => {
  if (!Array.isArray(props.employees)) return 0
  // Filter active employees (assuming active field exists, otherwise use all employees)
  return props.employees.filter(emp => emp.active !== false && emp.active !== 0 && emp.active !== '0').length
})

// Methods
const filterEmployees = (query) => {
  if (!query) {
    filteredEmployees.value = props.employees
    return
  }
  filteredEmployees.value = props.employees.filter(employee =>
    employee.name.toLowerCase().includes(query.toLowerCase()) ||
    employee.employee_no.toLowerCase().includes(query.toLowerCase())
  )
}

const handleEmployeeChange = async (employeeId) => {
  if (employeeId) {
    try {
      await fetchEndorsementData(employeeId)
    } catch (error) {
      console.error('Failed to fetch endorsement data:', error)
    }
  }
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const formatCurrency = (amount) => {
  if (!amount) return '0.00'
  return new Intl.NumberFormat('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(amount)
}

const resetForm = () => {
  formData.value = {
    employee: '',
    signatory: '',
    position: ''
  }
  endorsementData.value = null
  tleFormRef.value?.resetFields()
}

const handlePreview = async () => {
  try {
    await tleFormRef.value?.validate()
    
    const formPayload = {
      employee: formData.value.employee,
      signatory: formData.value.signatory,
      position: formData.value.position
    }
    
    const pdfUrl = await generateTLEPreview(formPayload)
    previewPdfUrl.value = pdfUrl
    showPreviewModal.value = true
    
    emit('preview', formPayload)
  } catch (error) {
    console.error('Form validation failed:', error)
    ElMessage.error('Please fill in all required fields')
  }
}

const handleClosePreview = () => {
  showPreviewModal.value = false
  if (previewPdfUrl.value) {
    URL.revokeObjectURL(previewPdfUrl.value)
    previewPdfUrl.value = ''
  }
}

const handleDownload = () => {
  if (previewPdfUrl.value) {
    const fileName = `terminal_leave_endorsement_${selectedEmployee.value?.name?.replace(/\s+/g, '_')}_${new Date().toISOString().split('T')[0]}.pdf`
    
    // Convert blob URL to blob and download
    fetch(previewPdfUrl.value)
      .then(res => res.blob())
      .then(blob => {
        downloadPDFFromBlob(blob, fileName)
        emit('download', { employee: selectedEmployee.value, fileName })
      })
      .catch(error => {
        console.error('Download failed:', error)
        ElMessage.error('Failed to download PDF')
      })
  }
}

// Watchers
watch(() => props.employees, (newEmployees) => {
  filteredEmployees.value = newEmployees
}, { immediate: true })

// Lifecycle
onMounted(() => {
  filteredEmployees.value = props.employees
})
</script>

<style scoped>
.tle-form .el-form-item {
  margin-bottom: 20px;
}

.tle-form .el-select,
.tle-form .el-input {
  width: 100%;
}
</style>
