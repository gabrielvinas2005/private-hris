<template>
  <PageScaffold 
    title="Medical Certificate"
    subtitle="Generate employee medical certificate"
  >
    <div class="bg-white rounded-lg shadow mb-6">
      <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">Employee Selection</h3>
        <p class="text-sm text-gray-500 mt-1">Select an employee to generate certificate</p>
      </div>
      <div class="p-6">
        <el-form ref="formRef" :model="formData" :rules="rules" label-width="160px">
          <el-form-item label="Employee" prop="employee">
            <el-select v-model="formData.employee" placeholder="Select employee" filterable clearable class="w-full">
              <el-option v-for="emp in employees" :key="emp.id" :label="emp.name" :value="emp.id" />
            </el-select>
          </el-form-item>
          <div class="flex justify-end gap-3">
            <el-button @click="reset">Reset</el-button>
            <el-button type="primary" :loading="generateLoading" @click="onPreview">Preview</el-button>
          </div>
        </el-form>
      </div>
    </div>

    <CertificatePreviewModal
      v-if="showPreview"
      :visible="showPreview"
      :pdf-url="pdfUrl"
      :employee-name="selectedEmployeeName"
      :certificate-type="'Medical Certificate'"
      :loading="generateLoading"
      :show-word-button="false"
      :show-excel-button="false"
      @close="closePreview"
      @download="downloadPdf"
    />
  </PageScaffold>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import PageScaffold from '../PageScaffold.vue'
import CertificatePreviewModal from './CertificatePreviewModal.vue'
import { useEmployeeMedicalCertificate } from '../../composable/useEmployeeMedicalCertificate.js'

const {
  loading,
  generateLoading,
  employees,
  fetchEmployees,
  fetchCreateForm,
  generateMedicalCertificate,
  downloadPDFFromBlob
} = useEmployeeMedicalCertificate()

const formRef = ref()
const formData = ref({ employee: '' })
const rules = { employee: [{ required: true, message: 'Please select employee', trigger: 'change' }] }

const showPreview = ref(false)
const pdfUrl = ref('')

const selectedEmployeeName = computed(() => employees.value.find(e => e.id === formData.value.employee)?.name || 'Unknown')

const onPreview = async () => {
  await formRef.value?.validate()
  const url = await generateMedicalCertificate({ employee: formData.value.employee })
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
    downloadPDFFromBlob(blob, `medical_certificate_${selectedEmployeeName.value.replace(/\s+/g, '_')}.pdf`)
  })
}

const reset = () => {
  formData.value.employee = ''
  formRef.value?.resetFields()
}

onMounted(async () => {
  await Promise.all([fetchEmployees(), fetchCreateForm()])
})
</script>

<style scoped>
.w-full { width: 100%; }
</style>


