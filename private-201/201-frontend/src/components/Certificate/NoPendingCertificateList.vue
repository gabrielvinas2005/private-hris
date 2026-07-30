<template>

  <PageScaffold title="No Pending Certificate" subtitle="Generate certificate for employees with no pending transactions">

    <div class="bg-white rounded-lg shadow mb-6">

      <div class="p-6">

        <el-form ref="formRef" :model="formData" :rules="rules" label-width="160px">

          <el-form-item label="Employee" prop="employee">

            <el-select v-model="formData.employee" placeholder="Select employee" filterable clearable class="w-full" @change="handleEmployeeChange">

              <el-option v-for="emp in employees" :key="emp.id" :label="emp.name" :value="emp.id" />

            </el-select>

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



    <CertificatePreviewModal

      v-if="showPreview"

      :visible="showPreview"

      :pdf-url="pdfUrl"

      :employee-name="selectedName"

      :certificate-type="'No Pending Certificate'"

      :loading="generateLoading || updateLoading"

      :preview-key="previewKey"

      :show-purpose-edit="true"

      :purpose-text="previewFormData.purpose_text"

      @close="closePreview"

      @download="downloadPdf"

      @downloadWord="handleDownloadWord"

      @downloadExcel="handleDownloadExcel"

      @update-purpose="updatePreview"

      @purpose-text-change="(value) => { previewFormData.purpose_text = value }"

    />

  </PageScaffold>

</template>



<script setup>

import { ref, computed, onMounted } from 'vue'

import { ElMessage } from 'element-plus'

import PageScaffold from '../PageScaffold.vue'

import CertificatePreviewModal from './CertificatePreviewModal.vue'

import { useNoPendingCertificate } from '../../composable/useNoPendingCertificate.js'



const DEFAULT_PURPOSE_TEXT = 'in connection with the renewal of fidelity bond.'



const { loading, generateLoading, employees, fetchEmployees, generateNoPendingPdf, downloadWord, downloadExcel, downloadPDFFromBlob } = useNoPendingCertificate()



const formRef = ref()

const formData = ref({

  employee: '',

  signatory: 'MARIA ANTONIETTE S. ZOILO',

  position: 'Administrative Officer V',

  purpose_text: DEFAULT_PURPOSE_TEXT,

})

const previewFormData = ref({

  purpose_text: DEFAULT_PURPOSE_TEXT,

})

const updateLoading = ref(false)

const previewKey = ref(0)



const rules = {

  employee: [{ required: true, message: 'Please select employee', trigger: 'change' }],

  signatory: [{ required: true, message: 'Signatory is required', trigger: 'blur' }],

  position: [{ required: true, message: 'Position is required', trigger: 'blur' }],

}



const showPreview = ref(false)

const pdfUrl = ref('')

const selectedName = computed(() => employees.value.find(e => e.id === formData.value.employee)?.name || 'Unknown')



const buildFormPayload = (purposeText = null) => ({

  employee: formData.value.employee,

  signatory: formData.value.signatory,

  position: formData.value.position,

  purpose_text: purposeText ?? previewFormData.value.purpose_text ?? formData.value.purpose_text,

})



const generatePDF = async (purposeText = null, { manageLoading = true } = {}) => {

  const url = await generateNoPendingPdf(buildFormPayload(purposeText), { manageLoading })

  if (pdfUrl.value) {

    URL.revokeObjectURL(pdfUrl.value)

  }

  pdfUrl.value = url

  previewKey.value++

}



const handleEmployeeChange = () => {

  if (!formData.value.employee) {

    formData.value.purpose_text = DEFAULT_PURPOSE_TEXT

    previewFormData.value.purpose_text = DEFAULT_PURPOSE_TEXT

  }

}



const onPreview = async () => {

  await formRef.value?.validate()



  if (!formData.value.purpose_text) {

    formData.value.purpose_text = DEFAULT_PURPOSE_TEXT

  }

  previewFormData.value.purpose_text = formData.value.purpose_text



  await generatePDF()

  showPreview.value = true

}



const updatePreview = async () => {

  if (!showPreview.value || !formData.value.employee) return



  formData.value.purpose_text = previewFormData.value.purpose_text

  updateLoading.value = true

  try {

    await generatePDF(null, { manageLoading: false })

    ElMessage.success('Preview updated')

  } catch (error) {

    ElMessage.error('Failed to update preview')

  } finally {

    updateLoading.value = false

  }

}



const closePreview = () => {

  showPreview.value = false

  if (pdfUrl.value) URL.revokeObjectURL(pdfUrl.value)

  pdfUrl.value = ''

  previewFormData.value.purpose_text = formData.value.purpose_text || DEFAULT_PURPOSE_TEXT

}



const downloadPdf = () => {

  if (!pdfUrl.value) return

  fetch(pdfUrl.value).then(r => r.blob()).then(blob => {

    downloadPDFFromBlob(blob, `no_pending_certificate_${selectedName.value.replace(/\s+/g, '_')}.pdf`)

  })

}



const handleDownloadWord = async () => {

  try {

    const blob = await downloadWord(buildFormPayload())

    if (!blob) return

    const fileName = `no_pending_certificate_${selectedName.value.replace(/\s+/g, '_')}_${new Date().toISOString().split('T')[0]}.docx`

    downloadPDFFromBlob(blob, fileName)

  } catch (error) {

    console.error('Word download failed:', error)

  }

}



const handleDownloadExcel = async () => {

  try {

    const blob = await downloadExcel(buildFormPayload())

    if (!blob) return

    const fileName = `no_pending_certificate_${selectedName.value.replace(/\s+/g, '_')}_${new Date().toISOString().split('T')[0]}.xlsx`

    downloadPDFFromBlob(blob, fileName)

  } catch (error) {

    console.error('Excel download failed:', error)

  }

}



const reset = () => {

  formData.value = {

    employee: '',

    signatory: 'MARIA ANTONIETTE S. ZOILO',

    position: 'Administrative Officer V',

    purpose_text: DEFAULT_PURPOSE_TEXT,

  }

  previewFormData.value.purpose_text = DEFAULT_PURPOSE_TEXT

  formRef.value?.resetFields()

}



onMounted(async () => {

  await fetchEmployees()

})

</script>



<style scoped>

.w-full { width: 100%; }

</style>


