<template>
  <PageScaffold title="Certificate of Salary Deductions" subtitle="Generate salary deduction report for offboarded employees">
    <div class="bg-white rounded-lg shadow mb-6">
      <div class="p-6">
        <el-form ref="formRef" :model="formData" :rules="rules" label-width="150px">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <el-form-item label="Employee" prop="offboarding_id">
              <el-select
                v-model="formData.offboarding_id"
                placeholder="Select employee"
                filterable
                class="w-full"
                :loading="loading"
              >
                <el-option
                  v-for="emp in employees"
                  :key="emp.id"
                  :label="emp.full_name"
                  :value="emp.id"
                >
                  <div class="font-medium">{{ emp.full_name }}</div>
                  <div v-if="emp.separation_date" class="text-xs text-gray-500">
                    Separation: {{ formatDate(emp.separation_date) }}
                  </div>
                </el-option>
              </el-select>
            </el-form-item>

            <el-form-item label="Signatory" prop="signatory">
              <el-input v-model="formData.signatory" />
            </el-form-item>

            <el-form-item label="Position" prop="position">
              <el-input v-model="formData.position" />
            </el-form-item>
          </div>

          <div class="flex justify-end gap-3 mt-4">
            <el-button @click="reset">Reset</el-button>
            <el-button
              type="primary"
              :loading="generateLoading"
              :disabled="!formData.offboarding_id"
              @click="generate"
            >
              Generate Report
            </el-button>
          </div>
        </el-form>
      </div>
    </div>

    <div v-if="showPreview" class="mt-6 bg-white rounded-lg shadow p-4">
      <div class="flex items-center justify-between mb-3">
        <div>
          <div class="text-base font-semibold">Report Preview</div>
          <div class="text-xs text-gray-500">Last 3 months salary deductions</div>
        </div>
        <div class="flex items-center gap-3">
          <span class="text-sm text-gray-600">Download as:</span>
          <el-button type="danger" size="small" @click="downloadPdf" :loading="generateLoading">PDF</el-button>
          <el-button type="primary" size="small" @click="downloadWord" :loading="generateLoading">Word</el-button>
        </div>
      </div>
      <div class="border rounded overflow-hidden" style="height:75vh;">
        <iframe v-if="pdfUrl" :src="pdfUrl" class="w-full h-full border-0"></iframe>
        <div v-else class="p-6 text-center text-gray-500">No preview available</div>
      </div>
    </div>
  </PageScaffold>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import PageScaffold from '../../components/PageScaffold.vue'
import { useCertOfSalaryDeduction } from '../../composable/useCertOfSalaryDeduction'
import { salaryDeductionCertificateApi } from '../../services/api'

const { loading, generateLoading, employees, fetchEmployees, generateCertificate, downloadPDFFromBlob } = useCertOfSalaryDeduction()

const formRef = ref()
const pdfUrl = ref('')
const showPreview = ref(false)

const formData = ref({
  offboarding_id: null,
  signatory: 'MARIA ANTONIETTE S. ZOILO',
  position: 'Administrative Officer V'
})

const rules = {
  offboarding_id: [{ required: true, message: 'Employee is required', trigger: 'change' }],
  signatory: [{ required: true, message: 'Signatory is required', trigger: 'blur' }],
  position: [{ required: true, message: 'Position is required', trigger: 'blur' }]
}

const loadEmployees = async () => {
  await fetchEmployees()
}

const generate = async () => {
  await formRef.value?.validate()
  if (!formData.value.offboarding_id) {
    ElMessage.warning('Please select an employee')
    return
  }
  try {
    const url = await generateCertificate({
      offboarding_id: formData.value.offboarding_id,
      signatory: formData.value.signatory,
      position: formData.value.position
    })
    pdfUrl.value = url
    showPreview.value = true
    ElMessage.success('Report generated')
  } catch (e) {
    // handled in composable
  }
}

const downloadPdf = () => {
  if (!pdfUrl.value) return
  fetch(pdfUrl.value).then(r => r.blob()).then(blob => {
    const filename = `salary_deduction_certificate_${new Date().toISOString().split('T')[0]}.pdf`
    downloadPDFFromBlob(blob, filename)
  })
}

const downloadWord = async () => {
  if (!formRef.value) return
  try {
    await formRef.value.validate()
    generateLoading.value = true

    const response = await salaryDeductionCertificateApi.generateWord({
      offboarding_id: formData.value.offboarding_id,
      signatory: formData.value.signatory,
      position: formData.value.position
    })
    const blob = new Blob([response.data], { type: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' })
    const filename = `salary_deduction_certificate_${new Date().toISOString().split('T')[0]}.docx`
    const link = document.createElement('a')
    link.href = URL.createObjectURL(blob)
    link.download = filename
    link.click()
    URL.revokeObjectURL(link.href)
    ElMessage.success('Word document downloaded')
  } catch (error) {
    console.error('Word download error:', error)
    ElMessage.error('Failed to generate Word document')
  } finally {
    generateLoading.value = false
  }
}

const reset = () => {
  formData.value = {
    offboarding_id: null,
    signatory: 'MARIA ANTONIETTE S. ZOILO',
    position: 'Administrative Officer V'
  }
  pdfUrl.value = ''
  showPreview.value = false
  formRef.value?.resetFields()
}

const formatDate = (val) => {
  if (!val) return ''
  return new Date(val).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}

onMounted(async () => {
  await loadEmployees()
})
</script>

<style scoped>
.w-full { width: 100%; }
</style>