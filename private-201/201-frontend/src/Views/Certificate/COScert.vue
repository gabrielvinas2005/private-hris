<template>
  <PageScaffold title="COS Certificate" subtitle="Generate certificate for COS employees">
    <div class="bg-white rounded-lg shadow mb-6">
      <div class="p-6">
        <el-form ref="formRef" :model="formData" :rules="rules" label-width="150px">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <el-form-item label="Employee (COS)" prop="employee_id" class="md:col-span-2">
              <el-select
                v-model="formData.employee_id"
                placeholder="Select COS employee"
                filterable
                class="w-full"
                :loading="loading"
                @change="onEmployeeChange"
              >
                <el-option
                  v-for="emp in employees"
                  :key="emp.id"
                  :label="emp.full_name"
                  :value="emp.id"
                >
                  <div class="font-medium">{{ emp.full_name }}</div>
                  <div v-if="emp.position" class="text-xs text-gray-500">{{ emp.position }}</div>
                </el-option>
              </el-select>
            </el-form-item>

            <div class="signatory-divider md:col-span-2">-----------------Signatory-----------------------</div>

            <el-form-item label="Signatory" prop="signatory">
              <el-input
                v-model="formData.signatory"
                placeholder="Enter signatory name"
                class="w-full"
              />
            </el-form-item>

            <el-form-item label="Position" prop="position">
              <el-input
                v-model="formData.position"
                placeholder="Enter signatory position"
                class="w-full"
              />
            </el-form-item>
          </div>
        </el-form>

        <div class="flex justify-end gap-3 mt-4">
          <el-button @click="reset">Reset</el-button>
          <el-button
            type="primary"
            :loading="generateLoading"
            :disabled="!formData.employee_id"
            @click="generate"
          >
            Generate Report
          </el-button>
        </div>
      </div>
    </div>

    <div v-if="showPreview" class="mt-6 bg-white rounded-lg shadow p-4">
      <div class="flex items-center justify-between mb-3">
        <div>
          <div class="text-base font-semibold">Certificate Preview</div>
          <div class="text-xs text-gray-500">COS Certificate Preview - Edit fields below to update the preview</div>
        </div>
        <div class="flex items-center gap-2">
          <span class="text-sm text-gray-600">Download as:</span>
          <el-button type="danger" size="small" @click="downloadPdf">PDF</el-button>
          <el-button type="primary" size="small" :loading="wordLoading" @click="downloadWord">Word</el-button>
        </div>
      </div>
      
      <!-- Editable Fields -->
      <div class="mb-4 p-4 bg-gray-50 rounded-lg">
        <el-form :model="previewFormData" label-width="120px">
          <el-form-item label="Purpose Text">
            <el-input 
              v-model="previewFormData.purpose_text" 
              type="textarea"
              :rows="3"
              placeholder="Enter purpose text (e.g., as a confirmation of her engagement with the Center and as a requirement for her personal travel abroad)"
              @blur="updatePreview"
              :disabled="updateLoading"
            />
          </el-form-item>
        </el-form>
        <div class="text-xs text-gray-500 mt-2 flex items-center gap-1">
          <el-icon><InfoFilled /></el-icon>
          <span>Changes will automatically update the preview {{ updateLoading ? '(Updating...)' : '' }}</span>
        </div>
      </div>

      <div class="border rounded overflow-hidden" style="height:75vh;">
        <iframe v-if="pdfUrl" :src="pdfUrl" :key="previewKey" class="w-full h-full border-0"></iframe>
        <div v-else class="p-6 text-center text-gray-500">No preview available</div>
      </div>
    </div>
  </PageScaffold>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import PageScaffold from '../../components/PageScaffold.vue'
import { cosCertApi } from '../../services/api'
import { ElMessage } from 'element-plus'
import { InfoFilled } from '@element-plus/icons-vue'

const loading = ref(false)
const generateLoading = ref(false)
const updateLoading = ref(false)
const wordLoading = ref(false)
const employees = ref([])
const formRef = ref()
const formData = ref({
  employee_id: null,
  signatory: 'Ma. Fe J. Avila',
  position: 'OIC-Executive Director'
})

const previewFormData = ref({
  purpose_text: '' // Will be set by backend based on employee gender
})
const pdfUrl = ref('')
const showPreview = ref(false)
const previewKey = ref(0) // Used to force iframe refresh

const rules = {
  employee_id: [{ required: true, message: 'Employee is required', trigger: 'change' }],
  signatory: [{ required: true, message: 'Signatory is required', trigger: 'blur' }],
  position: [{ required: true, message: 'Position is required', trigger: 'blur' }]
}

const fetchEmployees = async () => {
  loading.value = true
  try {
    const { data } = await cosCertApi.getEmployees()
    employees.value = data?.data || []
  } catch (error) {
    console.error('Failed to load COS employees', error)
    ElMessage.error('Failed to load employees')
  } finally {
    loading.value = false
  }
}

const onEmployeeChange = (employeeId) => {
  if (employeeId) {
    const selectedEmployee = employees.value.find(emp => emp.id === employeeId)
    if (selectedEmployee && selectedEmployee.gender_id) {
      const pronoun = selectedEmployee.gender_id === 1 ? 'her' : 'his'
      previewFormData.value.purpose_text = `as a confirmation of ${pronoun} engagement with the Center and as a requirement for ${pronoun} personal travel abroad`
    }
  } else {
    previewFormData.value.purpose_text = ''
  }
}

const generatePDF = async (purposeText = null) => {
  try {
    const response = await cosCertApi.generatePDF({
      employee_id: formData.value.employee_id,
      signatory: formData.value.signatory,
      position: formData.value.position,
      purpose_text: purposeText || previewFormData.value.purpose_text
    })
    const blob = new Blob([response.data], { type: 'application/pdf' })
    // Revoke old URL to prevent memory leaks
    if (pdfUrl.value) {
      URL.revokeObjectURL(pdfUrl.value)
    }
    pdfUrl.value = URL.createObjectURL(blob)
    previewKey.value++ // Force iframe refresh
    return true
  } catch (error) {
    console.error('Failed to generate COS certificate', error)
    throw error
  }
}

const generate = async () => {
  await formRef.value?.validate()
  generateLoading.value = true
  try {
    await generatePDF()
    showPreview.value = true
    ElMessage.success('Certificate generated successfully')
  } catch (error) {
    ElMessage.error('Failed to generate certificate')
  } finally {
    generateLoading.value = false
  }
}

const updatePreview = async () => {
  if (!showPreview.value || !formData.value.employee_id) return
  
  updateLoading.value = true
  try {
    await generatePDF()
    ElMessage.success('Preview updated')
  } catch (error) {
    ElMessage.error('Failed to update preview')
  } finally {
    updateLoading.value = false
  }
}

const downloadPdf = () => {
  if (!pdfUrl.value) return
  fetch(pdfUrl.value).then(r => r.blob()).then(blob => {
    const link = document.createElement('a')
    link.href = URL.createObjectURL(blob)
    link.download = `cos_certificate_${formData.value.employee_id}_${new Date().toISOString().split('T')[0]}.pdf`
    link.click()
    URL.revokeObjectURL(link.href)
  })
}

const downloadWord = async () => {
  if (!formData.value.employee_id) return
  wordLoading.value = true
  try {
    const response = await cosCertApi.generateWord({
      employee_id: formData.value.employee_id,
      signatory: formData.value.signatory,
      position: formData.value.position,
      purpose_text: previewFormData.value.purpose_text
    })
    const blob = new Blob([response.data], { type: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' })
    const link = document.createElement('a')
    link.href = URL.createObjectURL(blob)
    link.download = `cos_certificate_${formData.value.employee_id}_${new Date().toISOString().split('T')[0]}.docx`
    link.click()
    URL.revokeObjectURL(link.href)
  } catch (error) {
    console.error('Failed to download Word certificate', error)
    ElMessage.error('Failed to download Word certificate')
  } finally {
    wordLoading.value = false
  }
}

const reset = () => {
  formData.value = {
    employee_id: null,
    signatory: 'Ma. Fe J. Avila',
    position: 'OIC-Executive Director'
  }
  previewFormData.value = {
    purpose_text: '' // Will be set by backend based on employee gender
  }
  formRef.value?.resetFields()
  if (pdfUrl.value) {
    URL.revokeObjectURL(pdfUrl.value)
  }
  pdfUrl.value = ''
  showPreview.value = false
  previewKey.value = 0
}

onMounted(async () => {
  await fetchEmployees()
})
</script>

<style scoped>
.w-full { width: 100%; }

.signatory-divider {
  margin: 1.25rem 0 1rem;
  padding: 0.5rem 0;
  text-align: center;
  font-size: 0.875rem;
  font-weight: 600;
  color: #6b7280;
  letter-spacing: 0.02em;
  border-top: 1px solid #e5e7eb;
  border-bottom: 1px solid #e5e7eb;
}
</style>