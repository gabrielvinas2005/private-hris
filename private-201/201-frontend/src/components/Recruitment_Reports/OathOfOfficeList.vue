<template>
  <PageScaffold title="Oath of Office" subtitle="Generate oath of office certificates for employees">
    <div class="bg-white rounded-lg shadow mb-6">
      <div class="p-6">
        <el-form ref="formRef" :model="formData" :rules="rules" label-width="200px">
          <el-form-item label="Applicant" prop="employee">
            <el-select 
              v-model="formData.employee" 
              placeholder="Select Applicant" 
              filterable 
              clearable 
              class="w-full"
              :loading="loading"
            >
              <template v-for="emp in employees" :key="emp?.id || emp">
                <el-option 
                  v-if="emp && emp.id"
                  :label="emp.name || 'Unknown'" 
                  :value="emp.id" 
                />
              </template>
            </el-select>
          </el-form-item>

          <!-- Signatory Information -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <el-form-item label="Signatory Name" prop="signatory">
              <el-input v-model="formData.signatory" placeholder="Enter signatory name" />
            </el-form-item>
            <el-form-item label="Signatory Position" prop="position">
              <el-input v-model="formData.position" placeholder="Enter signatory position" />
            </el-form-item>
          </div>

          <div class="flex justify-end gap-3 mt-6">
            <el-button @click="reset">Reset</el-button>
            <el-button type="primary" :loading="generateLoading" @click="onPreview">
              Preview
            </el-button>
          </div>
        </el-form>
      </div>
    </div>

    <!-- PDF Preview Modal -->
    <CertificatePreviewModal
      v-if="showPreview"
      :visible="showPreview"
      :pdf-url="pdfUrl"
      :employee-name="selectedName"
      :certificate-type="'Oath of Office'"
      :loading="generateLoading"
      :show-word-button="true"
      @close="closePreview"
      @download="downloadPdf"
      @downloadWord="handleDownloadWord"
    />
  </PageScaffold>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { ElMessage } from 'element-plus'
import PageScaffold from '../PageScaffold.vue'
import CertificatePreviewModal from '../Certificate/CertificatePreviewModal.vue'
import { useOathOfOffice } from '../../composable/useOathOfOffice.js'

const {
  loading,
  generateLoading,
  employees,
  formData,
  rules,
  fetchEmployees,
  generateOathPdf,
  downloadWord,
  downloadPDFFromBlob,
  resetForm
} = useOathOfOffice()

const formRef = ref(null)
const showPreview = ref(false)
const pdfUrl = ref('')

// Computed properties
const selectedName = computed(() => {
  const selectedEmployee = employees.value.find(emp => emp.id === formData.employee)
  return selectedEmployee ? selectedEmployee.name : ''
})

// Methods
const onPreview = async () => {
  if (!formRef.value) return
  
  try {
    await formRef.value.validate()
    
    const response = await generateOathPdf(formData)
    console.log('Component - Response type:', typeof response)
    console.log('Component - Is Blob:', response instanceof Blob)
    
    // If response is already a blob, use it directly
    let blob
    if (response instanceof Blob) {
      blob = response
    } else {
      blob = new Blob([response], { type: 'application/pdf' })
    }
    
    console.log('Component - Blob size:', blob.size)
    console.log('Component - Blob type:', blob.type)
    
    pdfUrl.value = URL.createObjectURL(blob)
    showPreview.value = true
  } catch (error) {
    console.error('Preview error:', error)
  }
}

const downloadPdf = () => {
  if (pdfUrl.value) {
    const link = document.createElement('a')
    link.href = pdfUrl.value
    link.download = `oath_of_office_${formData.employee}_${new Date().toISOString().split('T')[0]}.pdf`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
  }
}

const handleDownloadWord = async () => {
  if (!formData.employee) {
    ElMessage.warning('Please select an employee')
    return
  }

  try {
    generateLoading.value = true
    const blob = await downloadWord(formData)
    
    if (!blob) return
    
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    const fileName = `oath_of_office_${formData.employee}_${new Date().toISOString().split('T')[0]}.docx`
    link.href = url
    link.download = fileName
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
    ElMessage.success('Word document downloaded successfully')
  } catch (error) {
    console.error('Word download failed:', error)
    ElMessage.error('Failed to download Word document')
  } finally {
    generateLoading.value = false
  }
}

const closePreview = () => {
  showPreview.value = false
  if (pdfUrl.value) {
    URL.revokeObjectURL(pdfUrl.value)
    pdfUrl.value = ''
  }
}

const reset = () => {
  if (formRef.value) {
    formRef.value.resetFields()
  }
  resetForm()
}

// Lifecycle
onMounted(() => {
  fetchEmployees()
})
</script>

<style scoped>
.grid {
  display: grid;
}

.grid-cols-1 {
  grid-template-columns: repeat(1, minmax(0, 1fr));
}

.grid-cols-2 {
  grid-template-columns: repeat(2, minmax(0, 1fr));
}

.gap-4 {
  gap: 1rem;
}

.gap-3 {
  gap: 0.75rem;
}

@media (min-width: 768px) {
  .md\:grid-cols-2 {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}
</style>
