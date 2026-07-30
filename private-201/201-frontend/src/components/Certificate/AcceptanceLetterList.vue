<template>
  <PageScaffold title="Acceptance of New Hires" subtitle="Generate acceptance letters for new hires">
    <div class="bg-white rounded-lg shadow mb-6">
      <div class="p-6">
        <el-form ref="formRef" :model="formData" :rules="rules" label-width="180px">
          <el-form-item label="Applicants" prop="applicants">
            <el-select 
              v-model="formData.applicants" 
              placeholder="Select applicants" 
              filterable 
              clearable 
              multiple
              collapse-tags
              collapse-tags-tooltip
              class="w-full"
              @change="onApplicantsChange"
              :loading="loading"
            >
              <el-option 
                v-for="app in applicants" 
                :key="app.id" 
                :label="formatApplicantLabel(app)" 
                :value="app.id"
              >
                <div class="flex flex-col">
                  <span class="font-medium">{{ formatApplicantLabel(app) }}</span>
                  <span class="text-xs text-gray-500">{{ app.position_name }} - {{ app.plantilla_code }}</span>
                </div>
              </el-option>
            </el-select>
          </el-form-item>

          <!-- Selected Applicants Display -->
          <div v-if="selectedApplicants.length > 0" class="mb-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
            <div class="text-sm font-medium text-gray-600 mb-2">
              Selected Applicants ({{ selectedApplicants.length }}):
            </div>
            <div class="space-y-2">
              <div v-for="app in selectedApplicants" :key="app.id" class="text-sm border-b border-gray-200 pb-2 last:border-0">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                  <div><span class="font-medium text-gray-600">Name:</span> <span class="ml-2">{{ app.full_name }}</span></div>
                  <div><span class="font-medium text-gray-600">Position:</span> <span class="ml-2">{{ app.position_name || 'N/A' }}</span></div>
                  <div><span class="font-medium text-gray-600">Plantilla:</span> <span class="ml-2">{{ app.plantilla_code || 'N/A' }}</span></div>
                  <div><span class="font-medium text-gray-600">Applicant No:</span> <span class="ml-2">{{ app.applicant_no || 'N/A' }}</span></div>
                </div>
              </div>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <el-form-item label="Signatory" prop="signatory_id">
              <el-select 
                v-model="formData.signatory_id" 
                placeholder="Select signatory" 
                filterable 
                clearable 
                class="w-full"
                @change="onSignatoryChange"
                :loading="loading"
              >
                <el-option 
                  v-for="sig in signatories" 
                  :key="sig.id" 
                  :label="sig.name" 
                  :value="sig.id"
                >
                  <div class="flex flex-col">
                    <span class="font-medium">{{ sig.name }}</span>
                    <span class="text-xs text-gray-500">{{ sig.position_name || 'N/A' }}</span>
                  </div>
                </el-option>
              </el-select>
            </el-form-item>
            <el-form-item label="Position" prop="position">
              <el-input v-model="formData.position" placeholder="Signatory position (auto-filled)" disabled />
            </el-form-item>
            <el-form-item label="Submit Date" prop="submit_date">
              <el-date-picker
                v-model="formData.submit_date"
                type="datetime"
                placeholder="Select submit date and time"
                class="w-full"
                format="YYYY-MM-DD HH:mm"
                value-format="YYYY-MM-DD HH:mm"
              />
            </el-form-item>
            <el-form-item label="Notify Date" prop="notify_date">
              <el-date-picker
                v-model="formData.notify_date"
                type="date"
                placeholder="Select notify date"
                class="w-full"
                format="YYYY-MM-DD"
                value-format="YYYY-MM-DD"
              />
            </el-form-item>
            <el-form-item label="For Discussion" prop="for_discussion_id">
              <el-select 
                v-model="formData.for_discussion_id" 
                placeholder="Select department" 
                filterable 
                clearable 
                class="w-full"
                :loading="loading"
              >
                <el-option 
                  v-for="dept in departments" 
                  :key="dept.id" 
                  :label="dept.name" 
                  :value="dept.id"
                >
                  <div class="flex flex-col">
                    <span class="font-medium">{{ dept.name }}</span>
                    <span v-if="dept.code" class="text-xs text-gray-500">{{ dept.code }}</span>
                  </div>
                </el-option>
              </el-select>
            </el-form-item>
          </div>
          <div class="flex justify-end gap-3 mt-2">
            <el-button @click="reset">Reset</el-button>
            <el-button type="success" :loading="generateLoading" @click="onSendEmail">
              <el-icon class="mr-1"><Message /></el-icon>
              Send Email
            </el-button>
            <el-button type="primary" :loading="generateLoading" @click="onPreview">Preview</el-button>
          </div>
        </el-form>
      </div>
    </div>

    <CertificatePreviewModal
      v-if="showPreview"
      :visible="showPreview"
      :pdf-url="pdfUrl"
      :employee-name="selectedApplicants.length > 0 ? `${selectedApplicants.length} Applicant${selectedApplicants.length > 1 ? 's' : ''}` : 'Unknown'"
      :certificate-type="'Acceptance of New Hires'"
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
import { ElMessage, ElMessageBox } from 'element-plus'
import { Message } from '@element-plus/icons-vue'
import PageScaffold from '../PageScaffold.vue'
import CertificatePreviewModal from './CertificatePreviewModal.vue'
import { useAcceptanceLetter } from '../../composable/useAcceptanceLetter.js'

const { loading, generateLoading, applicants, signatories, departments, fetchApplicants, fetchSignatories, fetchDepartments, generateAcceptancePdf, generateAcceptanceWord, downloadPDFFromBlob, downloadWordFromBlob, sendEmail } = useAcceptanceLetter()

const formRef = ref()
const formData = ref({
  applicants: [],
  signatory_id: '',
  position: '',
  submit_date: '',
  notify_date: '',
  for_discussion_id: ''
})

const rules = {
  applicants: [{ required: true, message: 'Please select at least one applicant', trigger: 'change', type: 'array', min: 1 }],
  signatory_id: [{ required: true, message: 'Please select signatory', trigger: 'change' }],
  submit_date: [{ required: true, message: 'Submit date is required', trigger: 'change' }],
  notify_date: [{ required: true, message: 'Notify date is required', trigger: 'change' }],
  for_discussion_id: [{ required: true, message: 'Please select department for discussion', trigger: 'change' }]
}

const showPreview = ref(false)
const pdfUrl = ref('')

const selectedApplicants = computed(() => {
  if (!formData.value.applicants || formData.value.applicants.length === 0) return []
  return applicants.value.filter(a => formData.value.applicants.includes(a.id))
})

const formatApplicantLabel = (applicant) => {
  return applicant.full_name || `${applicant.first_name} ${applicant.middle_name || ''} ${applicant.last_name}`.trim()
}

const onApplicantsChange = (applicantIds) => {
  // This is handled by the computed property selectedApplicants
}

const onSignatoryChange = (signatoryId) => {
  if (!signatoryId) {
    formData.value.position = ''
    return
  }
  
  const signatory = signatories.value.find(s => s.id === signatoryId)
  if (signatory) {
    formData.value.position = signatory.position_name || ''
  }
}

const onPreview = async () => {
  if (!formRef.value) return
  
  try {
    await formRef.value.validate()
    
    if (!formData.value.applicants || formData.value.applicants.length === 0) {
      ElMessage.error('Please select at least one applicant.')
      return
    }

    if (!formData.value.signatory_id) {
      ElMessage.error('Please select a signatory.')
      return
    }

    // Build array of all applicant details for preview
    const applicantsData = selectedApplicants.value.map(app => ({
      applicant_id: app.id,
      applicant_detail_id: app.applicant_detail_id
    }))

    const payload = {
      applicants: applicantsData,
      signatory_id: formData.value.signatory_id,
      submit_date: formData.value.submit_date,
      notify_date: formData.value.notify_date,
      for_discussion_id: formData.value.for_discussion_id,
      preview: true // Add preview flag
    }

    const url = await generateAcceptancePdf(payload)
    pdfUrl.value = url
    showPreview.value = true
  } catch (error) {
    console.error('Validation or preview error:', error)
  }
}

const closePreview = () => {
  showPreview.value = false
  if (pdfUrl.value) {
    URL.revokeObjectURL(pdfUrl.value)
    pdfUrl.value = ''
  }
}

const downloadPdf = async () => {
  try {
    if (!formData.value.applicants || formData.value.applicants.length === 0) {
      ElMessage.error('Please select at least one applicant.')
      return
    }

    if (!formData.value.signatory_id) {
      ElMessage.error('Please select a signatory.')
      return
    }

    // For each applicant, generate and download a separate PDF file
    // Generate all files first, then trigger downloads simultaneously
    const filePromises = selectedApplicants.value.map(async (app) => {
      try {
        // Build payload for single applicant
        const payload = {
          applicants: [{
            applicant_id: app.id,
            applicant_detail_id: app.applicant_detail_id
          }],
          signatory_id: formData.value.signatory_id,
          submit_date: formData.value.submit_date,
          notify_date: formData.value.notify_date,
          for_discussion_id: formData.value.for_discussion_id
        }

        // Generate PDF and get blob directly (not URL)
        const blob = await generateAcceptancePdf(payload, true)
        if (!blob) return null
        
        const fileName = `acceptance_letter_${app.applicant_no || app.id}_${new Date().toISOString().split('T')[0]}.pdf`
        
        return { blob, fileName }
      } catch (error) {
        console.error(`Failed to generate PDF for applicant ${app.id}:`, error)
        ElMessage.error(`Failed to generate PDF for ${app.full_name || 'applicant'}`)
        return null
      }
    })

    // Wait for all files to be generated
    const files = await Promise.all(filePromises)
    
    // Filter out any failed generations and trigger all downloads simultaneously
    const validFiles = files.filter(f => f !== null)
    
    // Trigger all downloads at once (browsers handle multiple downloads from same user action)
    validFiles.forEach(({ blob, fileName }) => {
      downloadPDFFromBlob(blob, fileName)
    })

    if (validFiles.length === 0) {
      ElMessage.error('Failed to generate any PDF documents')
    } else if (validFiles.length < selectedApplicants.value.length) {
      ElMessage.warning(`Generated ${validFiles.length} out of ${selectedApplicants.value.length} PDF documents`)
    }
  } catch (error) {
    console.error('PDF download failed:', error)
    ElMessage.error('Failed to generate acceptance letter PDF documents')
  }
}

const handleDownloadWord = async () => {
  try {
    if (!formData.value.applicants || formData.value.applicants.length === 0) {
      ElMessage.error('Please select at least one applicant.')
      return
    }

    if (!formData.value.signatory_id) {
      ElMessage.error('Please select a signatory.')
      return
    }

    // For each applicant, generate and download a separate DOCX file
    // Generate all files first, then trigger downloads simultaneously
    const filePromises = selectedApplicants.value.map(async (app) => {
      try {
        // Build payload for single applicant
        const payload = {
          applicants: [{
            applicant_id: app.id,
            applicant_detail_id: app.applicant_detail_id
          }],
          signatory_id: formData.value.signatory_id,
          submit_date: formData.value.submit_date,
          notify_date: formData.value.notify_date,
          for_discussion_id: formData.value.for_discussion_id
        }

        const blob = await generateAcceptanceWord(payload)
        if (!blob) return null

        const fileName = `acceptance_letter_${app.applicant_no || app.id}_${new Date().toISOString().split('T')[0]}.docx`
        
        return { blob, fileName }
      } catch (error) {
        console.error(`Failed to generate Word for applicant ${app.id}:`, error)
        ElMessage.error(`Failed to generate Word for ${app.full_name || 'applicant'}`)
        return null
      }
    })

    // Wait for all files to be generated
    const files = await Promise.all(filePromises)
    
    // Filter out any failed generations and trigger all downloads simultaneously
    const validFiles = files.filter(f => f !== null)
    
    // Trigger all downloads at once (browsers handle multiple downloads from same user action)
    validFiles.forEach(({ blob, fileName }) => {
      downloadWordFromBlob(blob, fileName)
    })

    if (validFiles.length === 0) {
      ElMessage.error('Failed to generate any Word documents')
    } else if (validFiles.length < selectedApplicants.value.length) {
      ElMessage.warning(`Generated ${validFiles.length} out of ${selectedApplicants.value.length} Word documents`)
    }
  } catch (error) {
    console.error('Word download failed:', error)
    ElMessage.error('Failed to generate acceptance letter Word documents')
  }
}

const onSendEmail = async () => {
  if (!formRef.value) return
  
  try {
    await formRef.value.validate()
    
    if (!formData.value.applicants || formData.value.applicants.length === 0) {
      ElMessage.error('Please select at least one applicant.')
      return
    }

    if (!formData.value.signatory_id) {
      ElMessage.error('Please select a signatory.')
      return
    }

    // Confirm before sending
    const applicantNames = selectedApplicants.value.map(a => a.full_name).join(', ')
    const confirmed = await ElMessageBox.confirm(
      `Send acceptance letters via email to ${selectedApplicants.value.length} applicant(s)?\n\n${applicantNames}`,
      'Confirm Email Sending',
      {
        confirmButtonText: 'Send Emails',
        cancelButtonText: 'Cancel',
        type: 'info',
      }
    ).catch(() => false)

    if (!confirmed) return

    // Build array of all applicant details for email
    const applicantsData = selectedApplicants.value.map(app => ({
      applicant_id: app.id,
      applicant_detail_id: app.applicant_detail_id
    }))

    const payload = {
      applicants: applicantsData,
      signatory_id: formData.value.signatory_id,
      submit_date: formData.value.submit_date,
      notify_date: formData.value.notify_date,
      for_discussion_id: formData.value.for_discussion_id
    }

    await sendEmail(payload)
  } catch (error) {
    if (error !== 'cancel') {
      console.error('Email sending error:', error)
    }
  }
}

const reset = () => {
  formData.value = {
    applicants: [],
    signatory_id: '',
    position: '',
    submit_date: '',
    notify_date: '',
    for_discussion_id: ''
  }
  formRef.value?.resetFields()
  closePreview()
}

onMounted(async () => {
  await Promise.all([
    fetchApplicants(),
    fetchSignatories(),
    fetchDepartments()
  ])
})
</script>

<style scoped>
.w-full { width: 100%; }
</style>
