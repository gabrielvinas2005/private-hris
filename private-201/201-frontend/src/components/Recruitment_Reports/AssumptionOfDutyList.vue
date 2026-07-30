<template>
  <PageScaffold title="Assumption of Duty" subtitle="Generate assumption of duty certificates for employees">
    <div class="bg-white rounded-lg shadow mb-6">
      <div class="p-6">
        <el-form ref="formRef" :model="formData" :rules="rules" label-width="200px">
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
                v-for="emp in selectableEmployees"
                :key="emp.id"
                :label="emp.name || 'Unknown'"
                :value="emp.id"
              />
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

          <!-- Assessed Information -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <el-form-item label="Assessed Date" prop="assested_date">
              <el-date-picker 
                v-model="formData.assested_date" 
                type="date" 
                placeholder="Select assessed date" 
                class="w-full" 
              />
            </el-form-item>
            <el-form-item label="Assessed Signatory" prop="assested_signatory">
              <el-input v-model="formData.assested_signatory" placeholder="Enter assessed signatory" />
            </el-form-item>
            <el-form-item label="Assessed Position" prop="assested_position">
              <el-input v-model="formData.assested_position" placeholder="Enter assessed position" />
            </el-form-item>
          </div>

          <div class="flex justify-end gap-3 mt-6">
            <el-button @click="reset">Reset</el-button>
            <el-button type="success" :loading="generateLoading" @click="onSendEmail">
              <el-icon class="mr-1"><Message /></el-icon>
              Send Email
            </el-button>
            <el-button type="primary" :loading="generateLoading" @click="onPreview">
              Preview
            </el-button>
          </div>
        </el-form>
      </div>
    </div>
 
    <!-- Inline PDF Preview -->
    <div v-if="showPreview" class="bg-white rounded-lg shadow p-4">
      <div class="flex items-center justify-between mb-3">
        <div>
          <h3 class="text-sm font-semibold">Assumption of Duty Preview</h3>
          <p class="text-xs text-gray-500">{{ selectedName }}</p>
        </div>
        <div class="flex items-center gap-2">
          <span class="text-sm text-gray-600">Download as:</span>
          <el-button type="danger" size="small" @click="downloadPdf">PDF</el-button>
          <el-button type="primary" size="small" @click="downloadDocx">WORD</el-button>
          <el-button size="small" @click="closePreview">
            <el-icon><Close /></el-icon>
            Close
          </el-button>
        </div>
      </div>
      <iframe :src="pdfUrl" style="width: 100%; height: 720px; border: 1px solid #e5e7eb; background: #f8fafc;"></iframe>
    </div>
  </PageScaffold>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import PageScaffold from '../PageScaffold.vue'
import { Close, Message } from '@element-plus/icons-vue'
import { useAssumptionOfDuty } from '../../composable/useAssumptionOfDuty.js'
import { assumptionOfDutyApi } from '../../services/api'

const {
  loading,
  generateLoading,
  employees,
  formData,
  rules,
  fetchEmployees,
  generateAssumptionPdf,
  downloadPDFFromBlob,
  resetForm,
  sendEmail
} = useAssumptionOfDuty()

const formRef = ref(null)
const showPreview = ref(false)
const pdfUrl = ref('')

// Computed properties
const selectableEmployees = computed(() => {
  return (employees.value || []).filter((emp) => {
    if (!emp || !emp.id) return false
    // Applicants use negative IDs; require a resolved applied position.
    if (Number(emp.id) < 0) return !!(emp.position && String(emp.position).trim())
    return true
  })
})

const selectedName = computed(() => {
  const selectedEmployee = selectableEmployees.value.find(emp => emp.id === formData.employee)
  return selectedEmployee ? selectedEmployee.name : ''
})

// Methods
const onPreview = async () => {
  if (!formRef.value) return
  
  try {
    await formRef.value.validate()
    
    const response = await generateAssumptionPdf(formData)
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
    link.download = `assumption_of_duty_${formData.employee}_${new Date().toISOString().split('T')[0]}.pdf`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
  }
}

const downloadDocx = async () => {
  try {
    const form = new FormData()
    form.append('employee', formData.employee)
    form.append('signatory', formData.signatory)
    form.append('position', formData.position)
    form.append('assested_date', formData.assested_date || '')
    form.append('assested_signatory', formData.assested_signatory || '')
    form.append('assested_position', formData.assested_position || '')
    const res = await assumptionOfDutyApi.downloadDocx(form)
    if (res?.data instanceof Blob) {
      const url = URL.createObjectURL(res.data)
      const a = document.createElement('a')
      a.href = url
      a.download = `assumption_of_duty_${formData.employee}_${new Date().toISOString().slice(0,10)}.docx`
      document.body.appendChild(a)
      a.click()
      document.body.removeChild(a)
      URL.revokeObjectURL(url)
    }
  } catch (e) {
    console.error('DOCX download failed', e)
    ElMessage.error('Failed to download DOCX')
  }
}

const onSendEmail = async () => {
  if (!formRef.value) return

  await formRef.value.validate()

  if (!formData.employee) {
    ElMessage.warning('Please select an employee')
    return
  }

  const confirmed = await ElMessageBox.confirm(
    `Send assumption of duty certificate to ${selectedName.value || 'selected employee'}?`,
    'Confirm Email Sending',
    {
      confirmButtonText: 'Send Email',
      cancelButtonText: 'Cancel',
      type: 'info'
    }
  ).catch(() => false)

  if (!confirmed) return
  await sendEmail({ ...formData })
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
