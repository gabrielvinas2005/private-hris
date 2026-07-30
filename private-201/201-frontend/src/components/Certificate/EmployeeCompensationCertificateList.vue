<template>
  <PageScaffold 
    title="Employee Certificate of Compensation"
    subtitle="Generate compensation certificate with bonuses and incomes"
  >
    <div class="bg-white rounded-lg shadow mb-6">
      <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">Employee Selection & Certificate Details</h3>
        <p class="text-sm text-gray-500 mt-1">Select an employee and provide the required signatory information</p>
      </div>

      <div class="p-6">
        <el-form 
          ref="formRef" 
          :model="formData" 
          :rules="rules" 
          label-width="180px"
        >
          <el-form-item label="Employee:" prop="employee">
            <el-select v-model="formData.employee" placeholder="Select employee" filterable clearable class="w-full" @change="onEmployeeChange">
              <el-option v-for="emp in employees" :key="emp.id" :label="emp.name" :value="emp.id" />
            </el-select>
          </el-form-item>

          <div v-if="selectedEmployeeData" class="mb-6">
            <div class="bg-gray-50 rounded-lg p-4">
              <h4 class="text-md font-medium text-gray-800 mb-3">Salary & Summary</h4>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                  <div class="text-sm text-gray-600">Monthly Salary</div>
                  <div class="text-sm text-gray-900">₱{{ formatCurrency(selectedEmployeeData.salary_details?.monthly_salary) }}</div>
                </div>
                <div>
                  <div class="text-sm text-gray-600">Annual Salary</div>
                  <div class="text-sm text-gray-900">₱{{ formatCurrency(selectedEmployeeData.salary_details?.annual_salary) }}</div>
                </div>
                <div>
                  <div class="text-sm text-gray-600">Total Incomes</div>
                  <div class="text-sm text-gray-900">₱{{ formatCurrency(selectedEmployeeData.summary?.total_incomes) }}</div>
                </div>
              </div>

              <div v-if="selectedEmployeeData.compensation_data" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <div class="text-sm font-medium text-gray-700 mb-2">Incomes</div>
                  <div v-for="inc in selectedEmployeeData.compensation_data.incomes" :key="inc.item" class="flex justify-between text-sm">
                    <span>{{ inc.item || inc.type }}</span>
                    <span>₱{{ formatCurrency(inc.amount) }}</span>
                  </div>
                </div>
                <div>
                  <div class="text-sm font-medium text-gray-700 mb-2">Bonuses & Cash Gift</div>
                  <div class="flex justify-between text-sm"><span>13th Month Pay</span><span>₱{{ formatCurrency(totalYearend) }}</span></div>
                  <div class="flex justify-between text-sm"><span>14th Month Pay</span><span>₱{{ formatCurrency(totalMidyear) }}</span></div>
                  <div class="flex justify-between text-sm"><span>Cash Gift</span><span>₱{{ formatCurrency(totalCashGift) }}</span></div>
                </div>
              </div>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <el-form-item label="Signatory Name:" prop="signatory">
              <el-input v-model="formData.signatory" placeholder="Enter signatory name" />
            </el-form-item>
            <el-form-item label="Signatory Position:" prop="position">
              <el-input v-model="formData.position" placeholder="Enter signatory position" />
            </el-form-item>
          </div>

          <div class="flex justify-end gap-3 mt-6">
            <el-button @click="resetForm">Reset</el-button>
            <el-button type="primary" :loading="generateLoading" :disabled="!isFormValid" @click="onPreview">
              <el-icon class="mr-2"><View /></el-icon>
              Preview Certificate
            </el-button>
          </div>
        </el-form>
      </div>
    </div>

    <CertificatePreviewModal
      v-if="showPreview"
      :visible="showPreview"
      :pdf-url="pdfUrl"
      :employee-name="employeeName"
      :certificate-type="'Compensation Certificate'"
      :loading="generateLoading"
      @close="closePreview"
      @download="downloadPdf"
      @downloadWord="handleDownloadWord"
    />
  </PageScaffold>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { View } from '@element-plus/icons-vue'
import PageScaffold from '../PageScaffold.vue'
import CertificatePreviewModal from './CertificatePreviewModal.vue'
import { useEmployeeCertificateCompensation } from '../../composable/useEmployeeCertificateCompensation.js'

const {
  loading,
  generateLoading,
  employees,
  selectedEmployeeData,
  fetchEmployees,
  fetchCreateForm,
  fetchEmployeeData,
  generateCertificatePreview,
  downloadWord,
  downloadPDFFromBlob,
  formatCurrency
} = useEmployeeCertificateCompensation()

const formRef = ref()
const formData = ref({ employee: '', signatory: '', position: '' })
const rules = {
  employee: [{ required: true, message: 'Please select employee', trigger: 'change' }],
  signatory: [{ required: true, message: 'Enter signatory name', trigger: 'blur' }],
  position: [{ required: true, message: 'Enter signatory position', trigger: 'blur' }]
}

const showPreview = ref(false)
const pdfUrl = ref('')

const employeeName = computed(() => {
  const emp = employees.value.find(e => e.id === formData.value.employee)
  return emp?.name || 'Unknown'
})

const totalYearend = computed(() => (selectedEmployeeData.value?.compensation_data?.yearend_bonus || []).reduce((s, i) => s + (i.amount || 0), 0))
const totalMidyear = computed(() => (selectedEmployeeData.value?.compensation_data?.midyear_bonus || []).reduce((s, i) => s + (i.amount || 0), 0))
const totalCashGift = computed(() => (selectedEmployeeData.value?.compensation_data?.cash_gift || []).reduce((s, i) => s + (i.amount || 0), 0))

const isFormValid = computed(() => !!formData.value.employee && formData.value.signatory.trim() && formData.value.position.trim())

const onEmployeeChange = async (id) => {
  if (id) await fetchEmployeeData(id)
}

const resetForm = () => {
  formData.value = { employee: '', signatory: '', position: '' }
  selectedEmployeeData.value = null
  formRef.value?.resetFields()
}

const onPreview = async () => {
  await formRef.value?.validate()
  const url = await generateCertificatePreview({
    employee: formData.value.employee,
    signatory: formData.value.signatory,
    position: formData.value.position
  })
  pdfUrl.value = url
  showPreview.value = true
}

const closePreview = () => {
  showPreview.value = false
  if (pdfUrl.value) URL.revokeObjectURL(pdfUrl.value)
}

const downloadPdf = () => {
  if (!pdfUrl.value) return
  fetch(pdfUrl.value).then(r => r.blob()).then(blob => {
    downloadPDFFromBlob(blob, `compensation_certificate_${employeeName.value.replace(/\s+/g, '_')}.pdf`)
  })
}

const handleDownloadWord = async () => {
  try {
    const formPayload = {
      employee: formData.value.employee,
      signatory: formData.value.signatory,
      position: formData.value.position
    }
    const blob = await downloadWord(formPayload)
    if (!blob) return
    const fileName = `compensation_certificate_${employeeName.value.replace(/\s+/g, '_')}_${new Date().toISOString().split('T')[0]}.docx`
    downloadPDFFromBlob(blob, fileName)
  } catch (error) {
    console.error('Word download failed:', error)
  }
}

onMounted(async () => {
  await Promise.all([fetchEmployees(), fetchCreateForm()])
})
</script>

<style scoped>
.w-full { width: 100%; }
</style>


