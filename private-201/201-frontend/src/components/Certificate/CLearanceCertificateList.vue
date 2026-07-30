<template>
    <PageScaffold title="Clearance Certificate" subtitle="Generate clearance certificates for employees">
      <div class="bg-white rounded-lg shadow mb-6">
        <div class="p-6">
          <el-form ref="formRef" :model="formData" :rules="rules" label-width="160px">
            <el-form-item label="Employee" prop="employee">
              <el-select v-model="formData.employee" placeholder="Select employee" filterable clearable class="w-full">
                <el-option v-for="emp in employees" :key="emp.id" :label="emp.name" :value="emp.id" />
              </el-select>
            </el-form-item>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <el-form-item label="Signatory" prop="signatory">
                <el-input v-model="formData.signatory" placeholder="Enter signatory name" />
              </el-form-item>
              <el-form-item label="Position" prop="position">
                <el-input v-model="formData.position" placeholder="Enter signatory position" />
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
        :certificate-type="'Clearance Certificate'"
        :loading="generateLoading"
        @close="closePreview"
        @download="downloadPdf"
      />
    </PageScaffold>
  </template>
  
  <script setup>
  import { ref, computed, onMounted } from 'vue'
  import PageScaffold from '../PageScaffold.vue'
  import CertificatePreviewModal from './CertificatePreviewModal.vue'
  import { useClearanceCertificate } from '../../composable/useClearanceCertificate.js'
  
  const { loading, generateLoading, employees, fetchEmployees, generateClearancePdf, downloadPDFFromBlob } = useClearanceCertificate()
  
  const formRef = ref()
  const formData = ref({ employee: '', signatory: '', position: '' })
  const rules = {
    employee: [{ required: true, message: 'Please select employee', trigger: 'change' }],
    signatory: [{ required: true, message: 'Signatory is required', trigger: 'blur' }],
    position: [{ required: true, message: 'Position is required', trigger: 'blur' }]
  }
  
  const showPreview = ref(false)
  const pdfUrl = ref('')
  const selectedName = computed(() => employees.value.find(e => e.id === formData.value.employee)?.name || 'Unknown')
  
  const onPreview = async () => {
    await formRef.value?.validate()
    const url = await generateClearancePdf({ ...formData.value })
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
      downloadPDFFromBlob(blob, `clearance_certificate_${selectedName.value.replace(/\s+/g, '_')}.pdf`)
    })
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