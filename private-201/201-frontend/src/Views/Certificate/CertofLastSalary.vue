<template>
  <PageScaffold title="Certificate of Last Salary" subtitle="Generate certificate of last salary for offboarded employees">
    <div class="bg-white rounded-lg shadow mb-6">
      <div class="p-6">
        <el-form ref="formRef" :model="formData" :rules="rules" label-width="180px">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <el-form-item label="Employee" prop="offboarding_id">
              <el-select
                v-model="formData.offboarding_id"
                placeholder="Select employee"
                filterable
                class="w-full"
                @change="handleEmployeeChange"
                :loading="loading"
              >
                <el-option
                  v-for="emp in employees"
                  :key="emp.id"
                  :label="emp.full_name"
                  :value="emp.id"
                >
                  <div>
                    <div class="font-medium">{{ emp.full_name }}</div>
                    <div class="text-xs text-gray-500" v-if="emp.separation_date">
                      Separation: {{ formatDate(emp.separation_date) }}
                    </div>
                  </div>
                </el-option>
              </el-select>
            </el-form-item>
            <el-form-item label="Signatory" prop="signatory">
              <el-input v-model="formData.signatory" placeholder="Enter signatory name" />
            </el-form-item>
            <el-form-item label="Position" prop="position">
              <el-input v-model="formData.position" placeholder="Enter signatory position" />
            </el-form-item>
            <el-form-item label="Organization Name" prop="organization_name">
              <el-input v-model="formData.organization_name" placeholder="Enter organization name" />
            </el-form-item>
          </div>

          <!-- Optional Sections -->
          <el-divider content-position="left">Optional Information</el-divider>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <el-form-item label="Include Bonus Info">
              <el-switch v-model="formData.include_bonus_info" />
            </el-form-item>
            <el-form-item label="Bonus Year" v-if="formData.include_bonus_info" prop="bonus_year">
              <el-input v-model="formData.bonus_year" placeholder="e.g., 2024" />
            </el-form-item>
            <el-form-item label="Include Transfer Info">
              <el-switch v-model="formData.include_transfer_info" />
            </el-form-item>
            <el-form-item label="Transfer Organization" v-if="formData.include_transfer_info" prop="transfer_organization">
              <el-input v-model="formData.transfer_organization" placeholder="Enter transfer organization" />
            </el-form-item>
            <el-form-item label="Transfer Date" v-if="formData.include_transfer_info" prop="transfer_date">
              <el-date-picker
                v-model="formData.transfer_date"
                type="date"
                placeholder="Select transfer date"
                class="w-full"
              />
            </el-form-item>
          </div>

          <!-- Employee Details Display -->
          <div v-if="employeeDetails" class="mt-6 p-4 bg-gray-50 rounded-lg">
            <h3 class="text-lg font-semibold mb-4">Employee Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="text-sm text-gray-600">Full Name:</label>
                <p class="font-medium">{{ employeeDetails.full_name }}</p>
              </div>
              <div>
                <label class="text-sm text-gray-600">Net Pay:</label>
                <p class="font-medium">₱{{ formatCurrency(employeeDetails.net_pay) }}</p>
              </div>
              <div>
                <label class="text-sm text-gray-600">Separation Date:</label>
                <p class="font-medium">{{ formatDate(employeeDetails.separation_date) }}</p>
              </div>
              <div>
                <label class="text-sm text-gray-600">Payroll Date:</label>
                <p class="font-medium">{{ formatDate(employeeDetails.created_at) }}</p>
              </div>
            </div>
          </div>

          <div class="flex justify-end gap-3 mt-6">
            <el-button @click="reset">Reset</el-button>
            <el-button
              type="primary"
              :loading="generateLoading"
              :disabled="!formData.offboarding_id"
              @click="generateCertificate"
            >
              Generate Certificate
            </el-button>
          </div>
        </el-form>
      </div>
    </div>

    <!-- Preview Section -->
    <div v-if="showPreview" class="mt-6 bg-white rounded-lg shadow p-4">
      <div class="flex items-center justify-between mb-3">
        <div>
          <div class="text-base font-semibold">Certificate of Last Salary Preview</div>
          <div class="text-xs text-gray-500">Preview below reflects your latest inputs</div>
        </div>
        <div class="flex gap-2">
          <el-button type="success" size="small" @click="downloadPdf">Download PDF</el-button>
        </div>
      </div>

      <div class="border rounded overflow-hidden" style="height:75vh;">
        <iframe
          v-if="pdfUrl"
          :src="pdfUrl"
          class="w-full h-full border-0"
        ></iframe>
        <div v-else class="p-6 text-center text-gray-500">No preview available</div>
      </div>
    </div>
  </PageScaffold>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import PageScaffold from '../../components/PageScaffold.vue'
import { useCertOfLastSalary } from '../../composable/useCertOfLastSalary.js'
import { useCompany } from '../../composable/useCompany.js'

const { primaryCompany, fetchCompanies } = useCompany()

const { loading, generateLoading, employees, employeeDetails, fetchEmployees, fetchEmployeeDetails, generateCertificate: generateCert, downloadPDFFromBlob } = useCertOfLastSalary()

const formRef = ref()
const showPreview = ref(false)
const pdfUrl = ref('')

const formData = ref({
  offboarding_id: null,
  signatory: 'EDUARDO A. PUYAOAN JR.',
  position: 'Chief Administrative Officer',
  organization_name: '',
  include_bonus_info: false,
  bonus_year: new Date().getFullYear().toString(),
  include_transfer_info: false,
  transfer_organization: '',
  transfer_date: null
})

const rules = {
  offboarding_id: [{ required: true, message: 'Employee is required', trigger: 'change' }],
  signatory: [{ required: true, message: 'Signatory is required', trigger: 'blur' }],
  position: [{ required: true, message: 'Position is required', trigger: 'blur' }]
}

const loadEmployees = async () => {
  try {
    await fetchEmployees()
  } catch (error) {
    console.error('Failed to load employees:', error)
  }
}

const handleEmployeeChange = async (offboardingId) => {
  if (offboardingId) {
    try {
      await fetchEmployeeDetails(offboardingId)
    } catch (error) {
      console.error('Failed to load employee details:', error)
    }
  } else {
    employeeDetails.value = null
  }
}

const generateCertificate = async () => {
  await formRef.value?.validate()

  if (!formData.value.offboarding_id) {
    ElMessage.warning('Please select an employee')
    return
  }

  try {
    const url = await generateCert({
      offboarding_id: formData.value.offboarding_id,
      signatory: formData.value.signatory,
      position: formData.value.position
    })
    
    pdfUrl.value = url
    showPreview.value = true
    
    ElMessage.success('Certificate generated successfully')
  } catch (error) {
    console.error('Failed to generate certificate:', error)
  }
}

const downloadPdf = () => {
  if (!pdfUrl.value) return
  fetch(pdfUrl.value).then(r => r.blob()).then(blob => {
    const filename = employeeDetails.value 
      ? `certificate_of_last_salary_${employeeDetails.value.full_name.replace(/\s+/g, '_')}.pdf`
      : `certificate_of_last_salary_${new Date().toISOString().split('T')[0]}.pdf`
    downloadPDFFromBlob(blob, filename)
  })
}

const reset = () => {
  formData.value = {
    offboarding_id: null,
    signatory: 'EDUARDO A. PUYAOAN JR.',
    position: 'Chief Administrative Officer',
    organization_name: defaultOrganizationName(),
    include_bonus_info: false,
    bonus_year: new Date().getFullYear().toString(),
    include_transfer_info: false,
    transfer_organization: '',
    transfer_date: null
  }
  formRef.value?.resetFields()
  employeeDetails.value = null
  showPreview.value = false
  pdfUrl.value = ''
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}

const formatCurrency = (amount) => {
  if (!amount) return '0.00'
  return new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(amount)
}

const defaultOrganizationName = () => primaryCompany.value?.name?.trim() || ''

onMounted(async () => {
  await fetchCompanies()
  formData.value.organization_name = defaultOrganizationName()
  await loadEmployees()
})
</script>

<style scoped>
.w-full { width: 100%; }
</style>
