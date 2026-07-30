<template>
  <PageScaffold title="Certificate of Last Day of Service" subtitle="Generate certificate for off-boarded employees">
    <div class="bg-white rounded-lg shadow mb-6">
      <div class="p-6">
        <el-form ref="formRef" :model="formData" :rules="rules" label-width="160px">
          <el-form-item label="Employee" prop="employee">
            <el-select v-model="formData.employee" placeholder="Select off-boarded employee" filterable clearable class="w-full">
              <el-option v-for="emp in employees" :key="emp.offboard_id || emp.id" :label="`${emp.name} - ${emp.nature || ''}`" :value="emp.offboard_id || emp.id" />
            </el-select>
          </el-form-item>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <el-form-item label="Signatory">
              <el-input v-model="formData.signatory" placeholder="Optional signatory name" />
            </el-form-item>
            <el-form-item label="Position">
              <el-input v-model="formData.position" placeholder="Optional signatory position" />
            </el-form-item>
          </div>
          <div class="flex justify-end gap-3 mt-2">
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
      :employee-name="selectedName"
      :certificate-type="'Last Day of Service'"
      :loading="generateLoading"
      :show-excel-button="false"
      @close="closePreview"
      @download="downloadPdf"
      @downloadWord="handleDownloadWord"
    />
  </PageScaffold>
  </template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import PageScaffold from '../PageScaffold.vue'
import CertificatePreviewModal from './CertificatePreviewModal.vue'
import { useLastDayOfServiceCertificate } from '../../composable/useLastDayOfServiceCertificate.js'

const { loading, generateLoading, employees, fetchEmployees, generateLastDayPdf, downloadWord, downloadPDFFromBlob } = useLastDayOfServiceCertificate()

const formRef = ref()
const formData = ref({ employee: '', signatory: '', position: '' })
const rules = { employee: [{ required: true, message: 'Please select employee', trigger: 'change' }] }

const showPreview = ref(false)
const pdfUrl = ref('')

const selectedName = computed(() => {
  const item = employees.value.find(e => (e.offboard_id || e.id) === formData.value.employee)
  return item?.name || 'Unknown'
})

const onPreview = async () => {
  await formRef.value?.validate()
  const url = await generateLastDayPdf({ ...formData.value })
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
    downloadPDFFromBlob(blob, `last_day_of_service_${selectedName.value.replace(/\s+/g, '_')}.pdf`)
  })
}

const handleDownloadWord = async () => {
  try {
    const blob = await downloadWord({ ...formData.value })
    if (!blob) return
    const fileName = `last_day_of_service_${selectedName.value.replace(/\s+/g, '_')}_${new Date().toISOString().split('T')[0]}.docx`
    downloadPDFFromBlob(blob, fileName)
  } catch (error) {
    console.error('Word download failed:', error)
  }
}

const reset = () => {
  formData.value = { employee: '', signatory: '', position: '' }
  formRef.value?.resetFields()
}

onMounted(async () => {
  await fetchEmployees()
})
</script>

<style scoped>
.w-full { width: 100%; }
</style>


