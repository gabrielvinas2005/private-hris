<template>
  <PageScaffold title="Service Record" subtitle="Generate employee service record">
    <div class="bg-white rounded-lg shadow">
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
      :certificate-type="'Service Record'"
      :loading="generateLoading"
      :show-word-button="false"
      @close="closePreview"
      @download="downloadPdf"
      @downloadExcel="handleDownloadExcel"
    />
  </PageScaffold>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import PageScaffold from '../PageScaffold.vue'
import CertificatePreviewModal from './CertificatePreviewModal.vue'
import { useServiceRecordCertificate } from '../../composable/useServiceRecordCertificate.js'

const { loading, generateLoading, employees, fetchEmployees, generateServiceRecord, downloadExcel, downloadPDFFromBlob } = useServiceRecordCertificate()

const formRef = ref()
const formData = ref({ employee: '' })
const rules = { employee: [{ required: true, message: 'Please select employee', trigger: 'change' }] }

const showPreview = ref(false)
const pdfUrl = ref('')

const selectedEmployeeName = computed(() => employees.value.find(e => e.id === formData.value.employee)?.name || 'Unknown')

const onPreview = async () => {
  await formRef.value?.validate()
  const url = await generateServiceRecord({ employee: formData.value.employee })
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
    downloadPDFFromBlob(blob, `service_record_${selectedEmployeeName.value.replace(/\s+/g, '_')}.pdf`)
  })
}

const handleDownloadExcel = async () => {
  try {
    const blob = await downloadExcel({ 
      employee: formData.value.employee,
      signatory: '',
      position: ''
    })
    if (!blob) return
    const fileName = `service_record_${selectedEmployeeName.value.replace(/\s+/g, '_')}_${new Date().toISOString().split('T')[0]}.xlsx`
    downloadPDFFromBlob(blob, fileName)
  } catch (error) {
    console.error('Excel download failed:', error)
  }
}

const reset = () => {
  formData.value.employee = ''
  formRef.value?.resetFields()
}

onMounted(async () => {
  await fetchEmployees()
})
</script>

<style scoped>
.w-full { width: 100%; }
</style>


