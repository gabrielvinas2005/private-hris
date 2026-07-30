<template>
  <PageScaffold 
    title="Employee Certificate"
    subtitle="Generate employee certificates with salary and employment information"
  >
    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <el-icon class="text-blue-600 text-2xl"><User /></el-icon>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Total Employees</p>
            <p class="text-2xl font-semibold text-gray-900">{{ totalEmployeesCount }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <el-icon class="text-green-600 text-2xl"><Document /></el-icon>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Certificates Ready</p>
            <p class="text-2xl font-semibold text-gray-900">{{ certificatesReady }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <el-icon class="text-yellow-600 text-2xl"><Money /></el-icon>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Avg Annual Salary</p>
            <p class="text-2xl font-semibold text-gray-900">₱{{ formatCurrency(averageAnnualSalaryCount) }}</p>
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

    <!-- Employee Selection and Form -->
    <div class="bg-white rounded-lg shadow mb-6">
      <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">Employee Selection & Certificate Details</h3>
        <p class="text-sm text-gray-500 mt-1">Select an employee and provide the required signatory information</p>
      </div>

      <div class="p-6">
        <el-form 
          ref="certificateFormRef" 
          :model="formData" 
          :rules="formRules" 
          label-width="180px"
          class="certificate-form"
        >
          <!-- Employee Selection -->
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <el-form-item label="Employee:" prop="employee" class="lg:col-span-2">
              <el-select
                v-model="formData.employee"
                placeholder="Select employee for certificate generation"
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

          <!-- Employee Certificate Data Display -->
          <div v-if="selectedEmployeeData" class="mb-6">
            <div class="bg-blue-50 rounded-lg p-4">
              <h4 class="text-md font-medium text-blue-800 mb-3">Certificate Information</h4>
              
              <!-- Salary Details -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                  <span class="text-sm font-medium text-blue-600">Monthly Salary:</span>
                  <p class="text-sm text-blue-900">₱{{ formatCurrency(selectedEmployeeData.salary_details?.monthly_salary) }}</p>
                </div>
                <div>
                  <span class="text-sm font-medium text-blue-600">Annual Salary:</span>
                  <p class="text-sm text-blue-900">₱{{ selectedEmployeeData.salary_details?.annual_salary }}</p>
                </div>
                <div class="md:col-span-2">
                  <span class="text-sm font-medium text-blue-600">Annual Salary (In Words):</span>
                  <p class="text-sm text-blue-900 capitalize">{{ selectedEmployeeData.salary_details?.salary_in_words }}</p>
                </div>
              </div>

              <!-- Additional Compensation -->
              <div v-if="selectedEmployeeData.compensation_data?.incomes && selectedEmployeeData.compensation_data.incomes.length > 0" class="mt-4">
                <h5 class="text-sm font-medium text-blue-800 mb-2">Additional Compensation</h5>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                  <div v-for="income in selectedEmployeeData.compensation_data.incomes" :key="income.name" class="flex justify-between">
                    <span class="text-sm text-blue-700">{{ income.name }}:</span>
                    <span class="text-sm text-blue-900">₱{{ formatCurrency(income.amount) }}</span>
                  </div>
                </div>
                
                <!-- Summary -->
                <div class="mt-3 pt-3 border-t border-blue-200">
                  <div class="flex justify-between">
                    <span class="text-sm font-medium text-blue-700">Total Additional Income:</span>
                    <span class="text-sm font-semibold text-blue-900">₱{{ formatCurrency(selectedEmployeeData.summary?.total_incomes) }}</span>
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
              Preview Certificate
            </el-button>
          </div>
        </el-form>
      </div>
    </div>

    <!-- Employee Certificate Preview Modal -->
    <CertificatePreviewModal
      v-if="showPreviewModal"
      :visible="showPreviewModal"
      :pdf-url="previewPdfUrl"
      :employee-name="selectedEmployee?.name || 'Unknown'"
      :certificate-type="'Employee Certificate'"
      :loading="generateLoading || updateLoading"
      :preview-key="previewKey"
      :show-purpose-edit="true"
      :purpose-text="previewFormData.purpose_text"
      @close="handleClosePreview"
      @download="handleDownloadPdf"
      @downloadWord="handleDownloadWord"
      @downloadExcel="handleDownloadExcel"
      @update-purpose="updatePreview"
      @purpose-text-change="(value) => { previewFormData.purpose_text = value }"
    />
  </PageScaffold>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { View, User, Document, Money, Printer } from '@element-plus/icons-vue'
import PageScaffold from '../PageScaffold.vue'
import CertificatePreviewModal from './CertificatePreviewModal.vue'
import { useEmployeeCertificate } from '../../composable/useEmployeeCertificate.js'

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
  selectedEmployeeData,
  totalEmployees,
  certificatesReady,
  averageAnnualSalary,
  fetchEmployeeData,
  generateCertificatePreview,
  downloadWord,
  downloadExcelPlaceholder,
  downloadPDFFromBlob,
  formatCurrency,
  formatDate
} = useEmployeeCertificate()

// Form data and validation
const certificateFormRef = ref()
const formData = ref({
  employee: '',
  signatory: 'MARIA ANTONIETTE S. ZOILO',
  position: 'Administrative Officer V',
  purpose_text: '' // Will be set based on employee gender
})
const previewFormData = ref({
  purpose_text: ''
})
const updateLoading = ref(false)
const previewKey = ref(0)

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

const totalEmployeesCount = computed(() => {
  return Array.isArray(props.employees) ? props.employees.length : 0
})

const averageAnnualSalaryCount = computed(() => {
  if (!Array.isArray(props.employees) || props.employees.length === 0) return 0
  const total = props.employees.reduce((sum, emp) => sum + (emp.salary * 12 || 0), 0)
  return Math.round(total / props.employees.length)
})

const isFormValid = computed(() => {
  return formData.value.employee && 
         formData.value.signatory.trim() && 
         formData.value.position.trim()
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
      await fetchEmployeeData(employeeId)
      // Set default purpose text based on gender if available
      if (selectedEmployeeData.value?.gender_id) {
        const pronoun = selectedEmployeeData.value.gender_id === 1 ? 'her' : 'his'
        const defaultPurpose = `as a confirmation of ${pronoun} employment with the Center and as a requirement for ${pronoun} personal travel abroad`
        formData.value.purpose_text = defaultPurpose
        previewFormData.value.purpose_text = defaultPurpose
      }
    } catch (error) {
      console.error('Failed to fetch employee certificate data:', error)
    }
  } else {
    selectedEmployeeData.value = null
    formData.value.purpose_text = ''
    previewFormData.value.purpose_text = ''
  }
}

const resetForm = () => {
  formData.value = {
    employee: '',
    signatory: 'MARIA ANTONIETTE S. ZOILO',
    position: 'Administrative Officer V',
    purpose_text: ''
  }
  previewFormData.value.purpose_text = ''
  selectedEmployeeData.value = null
  certificateFormRef.value?.resetFields()
}

const generatePDF = async (purposeText = null) => {
  try {
    const formPayload = {
      employee: formData.value.employee,
      signatory: formData.value.signatory,
      position: formData.value.position,
      purpose_text: purposeText || previewFormData.value.purpose_text || formData.value.purpose_text
    }
    
    const pdfUrl = await generateCertificatePreview(formPayload)
    // Revoke old URL to prevent memory leaks
    if (previewPdfUrl.value) {
      URL.revokeObjectURL(previewPdfUrl.value)
    }
    previewPdfUrl.value = pdfUrl
    previewKey.value++ // Force iframe refresh
    return true
  } catch (error) {
    console.error('Failed to generate certificate:', error)
    throw error
  }
}

const handlePreview = async () => {
  try {
    await certificateFormRef.value?.validate()
    
    // Initialize purpose_text if empty
    if (!formData.value.purpose_text && selectedEmployeeData.value?.gender_id) {
      const pronoun = selectedEmployeeData.value.gender_id === 1 ? 'her' : 'his'
      formData.value.purpose_text = `as a confirmation of ${pronoun} employment with the Center and as a requirement for ${pronoun} personal travel abroad`
      previewFormData.value.purpose_text = formData.value.purpose_text
    }
    
    await generatePDF()
    showPreviewModal.value = true
    
    emit('preview', {
      employee: formData.value.employee,
      signatory: formData.value.signatory,
      position: formData.value.position,
      purpose_text: previewFormData.value.purpose_text || formData.value.purpose_text
    })
  } catch (error) {
    console.error('Form validation failed:', error)
    ElMessage.error('Please fill in all required fields')
  }
}

const updatePreview = async () => {
  if (!showPreviewModal.value || !formData.value.employee) return
  
  updateLoading.value = true
  try {
    await generatePDF()
    ElMessage.success('Preview updated')
  } catch (error) {
    ElMessage.error('Failed to update preview')
  } finally {
    updateLoading.value = false
  }
}

const handleClosePreview = () => {
  showPreviewModal.value = false
  if (previewPdfUrl.value) {
    URL.revokeObjectURL(previewPdfUrl.value)
    previewPdfUrl.value = ''
  }
  previewFormData.value.purpose_text = formData.value.purpose_text || ''
}

const handleDownloadPdf = () => {
  if (previewPdfUrl.value) {
    const fileName = `employee_certificate_${selectedEmployee.value?.name?.replace(/\s+/g, '_')}_${new Date().toISOString().split('T')[0]}.pdf`
    
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

const handleDownloadWord = async () => {
  try {
    const formPayload = {
      employee: formData.value.employee,
      signatory: formData.value.signatory,
      position: formData.value.position,
      purpose_text: previewFormData.value.purpose_text || formData.value.purpose_text
    }
    const blob = await downloadWord(formPayload)
    if (!blob) return
    const fileName = `employee_certificate_${selectedEmployee.value?.name?.replace(/\s+/g, '_')}_${new Date().toISOString().split('T')[0]}.docx`
    downloadPDFFromBlob(blob, fileName)
  } catch (error) {
    console.error('Word download failed:', error)
  }
}

const handleDownloadExcel = async () => {
  await downloadExcelPlaceholder()
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
.certificate-form .el-form-item {
  margin-bottom: 20px;
}

.certificate-form .el-select,
.certificate-form .el-input {
  width: 100%;
}
</style>
