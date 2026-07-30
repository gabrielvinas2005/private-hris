<template>
  <PageScaffold title="ATM Request Certificate" subtitle="Generate ATM request certificates for employees">
    <div class="bg-white rounded-lg shadow mb-6">
      <div class="p-6">
        <el-form ref="formRef" :model="formData" :rules="rules" label-width="180px">
          <el-form-item label="Employee" prop="employee">
            <el-select
              v-model="formData.employee"
              placeholder="Select employee"
              filterable
              clearable
              class="w-full"
              :loading="loading"
            >
              <el-option
                v-for="emp in employees"
                :key="emp.id"
                :label="emp.name"
                :value="emp.id"
              />
            </el-select>
          </el-form-item>

          <el-form-item label="Recipient Name" prop="recipient_name">
            <el-input
              v-model="formData.recipient_name"
              placeholder="MS. ESTRELITA S. GERONIMO"
              clearable
            />
            <p class="field-hint">Salutation in the letter is generated automatically (e.g. Dear Ms. Geronimo:).</p>
          </el-form-item>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <el-form-item label="Signatory" prop="signatory">
              <el-input v-model="formData.signatory" placeholder="Enter signatory name" clearable />
            </el-form-item>
            <el-form-item label="Position" prop="position">
              <el-input v-model="formData.position" placeholder="Enter signatory position" clearable />
            </el-form-item>
          </div>

          <div class="flex justify-end gap-3 mt-2">
            <el-button @click="reset">Reset</el-button>
            <el-button type="primary" :loading="generateLoading" @click="onPreview">Preview</el-button>
          </div>
        </el-form>
      </div>
    </div>

    <div v-if="showPreview" class="bg-white rounded-lg shadow">
      <div class="p-6 border-b border-gray-200">
        <div class="flex justify-between items-center flex-wrap gap-3">
          <h3 class="text-lg font-semibold text-gray-900">
            {{ selectedName }} - ATM Request Certificate
          </h3>
          <div class="flex items-center gap-3">
            <span class="text-sm text-gray-600">Download as:</span>
            <el-button type="danger" size="small" @click="downloadPdf" :loading="generateLoading">
              <el-icon class="mr-1"><Download /></el-icon>
              PDF
            </el-button>
            <el-button type="primary" size="small" @click="handleDownloadWord" :loading="generateLoading">
              <el-icon class="mr-1"><Document /></el-icon>
              Word
            </el-button>
            <el-button size="small" @click="closePreview">Close</el-button>
          </div>
        </div>
      </div>

      <div class="preview-container">
        <div v-if="generateLoading" class="loading-container">
          <div class="custom-spinner"></div>
          <p class="loading-text">Generating ATM Request Certificate...</p>
        </div>
        <iframe
          v-else-if="pdfUrl"
          :key="previewKey"
          :src="pdfUrl"
          class="pdf-iframe"
          frameborder="0"
        ></iframe>
        <div v-else class="no-content">
          <p>No preview available</p>
        </div>
      </div>
    </div>
  </PageScaffold>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Download, Document } from '@element-plus/icons-vue'
import PageScaffold from '../PageScaffold.vue'
import { useATMRequestCertificate } from '../../composable/useATMRequestCertificate.js'
import {
  DEFAULT_ATM_RECIPIENT_NAME,
  DEFAULT_ATM_SIGNATORY,
  DEFAULT_ATM_SIGNATORY_POSITION,
} from '../../utils/atmRequestCertificate.js'

const { loading, generateLoading, employees, fetchEmployees, generateATMPdf, downloadWord, downloadPDFFromBlob } =
  useATMRequestCertificate()

const formRef = ref()
const formData = ref({
  employee: '',
  recipient_name: DEFAULT_ATM_RECIPIENT_NAME,
  signatory: DEFAULT_ATM_SIGNATORY,
  position: DEFAULT_ATM_SIGNATORY_POSITION,
})
const previewKey = ref(0)

const rules = {
  employee: [{ required: true, message: 'Please select employee', trigger: 'change' }],
  recipient_name: [{ required: true, message: 'Recipient name is required', trigger: 'blur' }],
  signatory: [{ required: true, message: 'Signatory is required', trigger: 'blur' }],
  position: [{ required: true, message: 'Position is required', trigger: 'blur' }],
}

const showPreview = ref(false)
const pdfUrl = ref('')
const selectedName = computed(
  () => employees.value.find((e) => e.id === formData.value.employee)?.name || 'Unknown'
)

const buildPayload = () => ({
  employee: formData.value.employee,
  recipient_name: formData.value.recipient_name,
  signatory: formData.value.signatory,
  position: formData.value.position,
})

const generatePDF = async () => {
  const url = await generateATMPdf(buildPayload())
  if (pdfUrl.value) {
    URL.revokeObjectURL(pdfUrl.value)
  }
  pdfUrl.value = url
  previewKey.value++
}

const onPreview = async () => {
  await formRef.value?.validate()
  await generatePDF()
  showPreview.value = true
}

const closePreview = () => {
  showPreview.value = false
  if (pdfUrl.value) {
    URL.revokeObjectURL(pdfUrl.value)
    pdfUrl.value = ''
  }
}

const downloadPdf = () => {
  if (!pdfUrl.value) return
  fetch(pdfUrl.value)
    .then((r) => r.blob())
    .then((blob) => {
      downloadPDFFromBlob(blob, `atm_request_${selectedName.value.replace(/\s+/g, '_')}.pdf`)
    })
}

const handleDownloadWord = async () => {
  try {
    const blob = await downloadWord(buildPayload())
    if (!blob) return
    const fileName = `atm_request_certificate_${selectedName.value.replace(/\s+/g, '_')}_${new Date().toISOString().split('T')[0]}.docx`
    downloadPDFFromBlob(blob, fileName)
  } catch (error) {
    console.error('Word download failed:', error)
  }
}

const reset = () => {
  formData.value = {
    employee: '',
    recipient_name: DEFAULT_ATM_RECIPIENT_NAME,
    signatory: DEFAULT_ATM_SIGNATORY,
    position: DEFAULT_ATM_SIGNATORY_POSITION,
  }
  formRef.value?.resetFields()
  closePreview()
}

onMounted(async () => {
  await fetchEmployees()
})
</script>

<style scoped>
.w-full {
  width: 100%;
}

.field-hint {
  margin: 0.25rem 0 0;
  font-size: 0.75rem;
  color: #6b7280;
}

.preview-container {
  height: 75vh;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  background-color: #f5f5f5;
  overflow: hidden;
}

.pdf-iframe {
  width: 100%;
  height: 100%;
  border: none;
}

.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100%;
}

.custom-spinner {
  width: 50px;
  height: 50px;
  border: 4px solid #f3f3f3;
  border-top: 4px solid #409eff;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
}

.loading-text {
  margin-top: 20px;
  color: #666;
  font-size: 14px;
}

.no-content {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100%;
  color: #999;
  font-size: 14px;
}
</style>
