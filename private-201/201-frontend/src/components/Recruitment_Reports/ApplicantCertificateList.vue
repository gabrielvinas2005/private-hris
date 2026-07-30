<template>
  <PageScaffold title="Applicant Certificate" subtitle="Generate appointment certificates for applicants">
    <div class="bg-white rounded-lg shadow mb-6">
      <div class="p-6">
        <el-form ref="formRef" :model="formData" :rules="rules" label-width="200px">
          <el-form-item label="Appointee" prop="employee">
            <el-select v-model="formData.employee" placeholder="Select employee or promotion" filterable clearable class="w-full">
              <el-option
                v-for="emp in selectableEmployees"
                :key="emp.id"
                :label="employeeAppointmentDisplayName(emp)"
                :value="emp.id"
              />
            </el-select>
          </el-form-item>
          <el-form-item label="Nature of appointment" prop="nature" required>
            <el-input
              v-model="formData.nature"
              placeholder="e.g. Original, Promotion"
              clearable
              class="w-full"
            />
          </el-form-item>
          <!-- Signatory block -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <el-form-item label="Specify Report Signatory (Name)" prop="signatory">
              <el-input v-model="formData.signatory" placeholder="Enter signatory name" />
            </el-form-item>
            <!-- <el-form-item label="Position" prop="position">
              <el-input v-model="formData.position" placeholder="Enter signatory position" />
            </el-form-item> -->
            <el-form-item label="Vice">
              <el-input v-model="formData.vice" placeholder="Enter vice" />
            </el-form-item>
            <el-form-item label="Who">
              <el-input v-model="formData.who" placeholder="Enter who" />
            </el-form-item>
          </div>

          <!-- Note and Date -->
          <el-form-item label="Note">
            <el-input v-model="formData.note" placeholder="Enter note" />
          </el-form-item>
          <el-form-item label="Date">
            <el-date-picker v-model="formData.cs_date" type="date" placeholder="Select date" class="w-full" />
          </el-form-item>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <el-form-item label="CSC Resolution No.">
              <el-input v-model="formData.cs_resolution_no" placeholder="Enter CSC resolution number" />
            </el-form-item>
            <el-form-item label="Series">
              <el-input v-model="formData.cs_resolution_series" placeholder="Enter series year" />
            </el-form-item>
          </div>

          <!-- HRMO / HRMPSB -->
          <el-form-item label="Highest Ranking HRMO">
            <el-input v-model="formData.hrmo" placeholder="Enter HRMO" />
          </el-form-item>
          <el-form-item label="Chairperson, HRMPSB/Placement Committee">
            <el-input v-model="formData.hrmpsb" placeholder="Enter Chairperson/Committee" />
          </el-form-item>
          
          <!-- Publish/Posted fields -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <el-form-item label="Publish at">
              <el-input v-model="formData.publish_at" placeholder="Enter publish at" />
            </el-form-item>
            <el-form-item label="Publish From">
              <el-input v-model="formData.publish_from" placeholder="Enter publish from" />
            </el-form-item>
            <el-form-item label="Publish To">
              <el-input v-model="formData.publish_to" placeholder="Enter publish to" />
            </el-form-item>
            <el-form-item label="Posted at">
              <el-input v-model="formData.posted_at" placeholder="Enter posted at" />
            </el-form-item>
            <el-form-item label="Posted From">
              <el-input v-model="formData.posted_from" placeholder="Enter posted from" />
            </el-form-item>
            <el-form-item label="Posted To">
              <el-input v-model="formData.posted_to" placeholder="Enter posted to" />
            </el-form-item>
          </div>

          <!-- Started/Deliberation dates -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <el-form-item label="Started On">
              <el-date-picker v-model="formData.started_on" type="date" placeholder="Select start date" class="w-full" />
            </el-form-item>
            <el-form-item label="Deliberation On">
              <el-date-picker v-model="formData.deliberation_on" type="date" placeholder="Select date" class="w-full" />
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
      :employee-name="selectedName"
      :certificate-type="'Applicant Certificate'"
      :loading="generateLoading"
      :show-word-button="true"
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
import CertificatePreviewModal from '../Certificate/CertificatePreviewModal.vue'
import { useApplicantCertificate } from '../../composable/useApplicantCertificate.js'

const { loading, generateLoading, employees, fetchEmployees, generateApplicantPdf, downloadPDFFromBlob, downloadWord, sendEmail } = useApplicantCertificate()

const formRef = ref()
const formData = ref({
  employee: '',
  nature: '',
  signatory: '',
  position: '',
  vice: '',
  who: '',
  note: '',
  cs_date: '',
  cs_resolution_no: '',
  cs_resolution_series: '',
  hrmo: '',
  hrmpsb: '',
  publish_at: '',
  publish_from: '',
  publish_to: '',
  posted_at: '',
  posted_from: '',
  posted_to: '',
  started_on: '',
  deliberation_on: ''
})
const rules = {
  employee: [{ required: true, message: 'Please select employee/promotion', trigger: 'change' }],
  nature: [
    {
      validator: (rule, value, callback) => {
        if (!value || String(value).trim() === '') {
          callback(new Error('Nature of appointment is required'))
        } else {
          callback()
        }
      },
      trigger: ['blur', 'change']
    }
  ],
  signatory: [{ required: true, message: 'Signatory is required', trigger: 'blur' }],
  position: [{ required: true, message: 'Position is required', trigger: 'blur' }]
}

const showPreview = ref(false)
const pdfUrl = ref('')

/** API `name` is "Person - Position - Type - Date"; show only the person in the select. */
const employeeAppointmentDisplayName = (emp) => {
  if (!emp?.name) return ''
  const sep = ' - '
  const i = emp.name.indexOf(sep)
  return i === -1 ? emp.name.trim() : emp.name.slice(0, i).trim()
}

const selectableEmployees = computed(() => {
  return (employees.value || []).filter((emp) => {
    if (!emp || !emp.id) return false
    // Applicant rows are negative IDs; require resolved position-applied.
    if (Number(emp.id) < 0) return !!(emp.position && String(emp.position).trim())
    return true
  })
})
const selectedName = computed(() => {
  const row = selectableEmployees.value.find((e) => e.id === formData.value.employee)
  return row ? employeeAppointmentDisplayName(row) : 'Unknown'
})

const onPreview = async () => {
  await formRef.value?.validate()
  const url = await generateApplicantPdf({ ...formData.value })
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
    downloadPDFFromBlob(blob, `applicant_certificate_${selectedName.value.replace(/\s+/g, '_')}.pdf`)
  })
}

const handleDownloadWord = async () => {
  try {
    await formRef.value?.validate()
  } catch {
    return
  }

  try {
    generateLoading.value = true
    const blob = await downloadWord(formData.value)
    
    if (!blob || blob.size === 0) {
      ElMessage.error('Empty document received')
      return
    }
    
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    const fileName = `appointment_certificate_${formData.value.employee}_${new Date().toISOString().split('T')[0]}.docx`
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

const onSendEmail = async () => {
  try {
    await formRef.value?.validate()
  } catch {
    return
  }

  if (!formData.value.employee) {
    ElMessage.warning('Please select an employee')
    return
  }

  const confirmed = await ElMessageBox.confirm(
    `Send appointment certificate to ${selectedName.value}?`,
    'Confirm Email Sending',
    {
      confirmButtonText: 'Send Email',
      cancelButtonText: 'Cancel',
      type: 'info'
    }
  ).catch(() => false)

  if (!confirmed) return
  await sendEmail({ ...formData.value })
}

const reset = () => {
  formData.value = {
    employee: '', nature: '', signatory: '', position: '', vice: '', who: '', note: '', cs_date: '', cs_resolution_no: '', cs_resolution_series: '',
    hrmo: '', hrmpsb: '', publish_at: '', publish_from: '', publish_to: '', posted_at: '', posted_from: '', posted_to: '', started_on: '', deliberation_on: ''
  }
  formRef.value?.resetFields()
}

onMounted(async () => {
  await fetchEmployees()
})
</script>

<style scoped>
.w-full { width: 100%; }
</style>


