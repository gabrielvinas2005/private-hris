<template>
  <PageScaffold title="COS Non-Disclosure Agreement" subtitle="Generate NDA for COS employees">
    <div class="bg-white rounded-lg shadow mb-6">
      <div class="p-6">
        <el-form ref="formRef" :model="formData" :rules="rules" label-width="160px">
          <el-form-item label="Employee (COS)" prop="employee_id">
            <el-select
              v-model="formData.employee_id"
              placeholder="Select COS employee"
              filterable
              clearable
              class="w-full"
              :loading="loading"
            >
              <el-option
                v-for="emp in employees"
                :key="emp.id"
                :label="formatEmployeeLabel(emp)"
                :value="emp.id"
              >
                <div class="flex flex-col">
                  <span class="font-medium">{{ emp.full_name }}</span>
                  <span class="text-xs text-gray-500">
                    {{ emp.position_name || 'No position' }} • {{ formatAddress(emp.permanent_address) }}
                  </span>
                </div>
              </el-option>
            </el-select>
          </el-form-item>

          <el-form-item label="Witnessed by: " prop="signatory_id">
            <el-select
              v-model="formData.signatory_id"
              placeholder="Select witness"
              filterable
              clearable
              class="w-full"
              :loading="signatoryLoading"
            >
              <el-option
                v-for="emp in signatories"
                :key="emp.id"
                :label="formatSignatoryLabel(emp)"
                :value="emp.id"
              >
                <div class="flex flex-col">
                  <span class="font-medium">{{ emp.full_name }}</span>
                  <span class="text-xs text-gray-500">
                    {{ emp.position_name || 'No position' }}
                  </span>
                </div>
              </el-option>
            </el-select>
          </el-form-item>

          <div class="flex justify-end gap-3 mt-2">
            <el-button @click="reset">Reset</el-button>
            <el-button type="primary" :loading="generateLoading" @click="onPreview">
              Preview
            </el-button>
          </div>
        </el-form>
      </div>
    </div>

    <div v-if="showPreview" class="bg-white rounded-lg shadow">
      <div class="p-6 border-b border-gray-200">
        <div class="flex justify-between items-center">
          <h3 class="text-lg font-semibold text-gray-900">
            {{ selectedName }} - COS Non-Disclosure Agreement
          </h3>
          <div class="flex items-center gap-3">
            <span class="text-sm text-gray-600">Download as:</span>
            <el-button
              type="danger"
              size="small"
              @click="downloadPdf"
              :loading="generateLoading"
            >
              PDF
            </el-button>
            <el-button
              type="primary"
              size="small"
              @click="downloadWord"
              :loading="generateLoading"
            >
              Word
            </el-button>
            <el-button
              size="small"
              @click="closePreview"
            >
              Close
            </el-button>
          </div>
        </div>
      </div>
      <div class="preview-container">
        <div v-if="generateLoading" class="loading-container">
          <div class="custom-spinner"></div>
          <p class="loading-text">Generating COS Non-Disclosure Agreement...</p>
        </div>
        <iframe
          v-else-if="pdfUrl"
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
import PageScaffold from '../PageScaffold.vue'
import { cosNDAApi } from '../../services/api'
import { ElMessage } from 'element-plus'

const loading = ref(false)
const signatoryLoading = ref(false)
const generateLoading = ref(false)
const employees = ref([])
const signatories = ref([])

const formRef = ref()
const formData = ref({
  employee_id: null,
  signatory_id: null
})

const rules = {
  employee_id: [{ required: true, message: 'Please select COS employee', trigger: 'change' }],
  signatory_id: [{ required: true, message: 'Please select signatory', trigger: 'change' }]
}

const showPreview = ref(false)
const pdfUrl = ref('')

const selectedEmployee = computed(() =>
  employees.value.find(e => e.id === formData.value.employee_id) || null
)

const selectedName = computed(() => selectedEmployee.value?.full_name || 'Unknown')

const formatEmployeeLabel = (emp) => {
  const base = emp.full_name || `Employee #${emp.employee_no || emp.id}`
  const pos = emp.position_name ? ` - ${emp.position_name}` : ''
  return `${base}${pos}`
}

const formatAddress = (addr) => {
  if (!addr) return 'No permanent address'
  return addr.replace(/\s+/g, ' ').trim()
}

const formatSignatoryLabel = (emp) => {
  const base = emp.full_name || `Employee #${emp.employee_no || emp.id}`
  const pos = emp.position_name ? ` - ${emp.position_name}` : ''
  return `${base}${pos}`
}

const fetchEmployees = async () => {
  loading.value = true
  try {
    const { data } = await cosNDAApi.getEmployees()
    employees.value = data?.data || []
  } catch (e) {
    console.error(e)
    ElMessage.error('Failed to load COS NDA employees')
  } finally {
    loading.value = false
  }
}

const fetchSignatories = async () => {
  signatoryLoading.value = true
  try {
    const { data } = await cosNDAApi.getSignatories()
    signatories.value = data?.data || []
  } catch (e) {
    console.error(e)
    ElMessage.error('Failed to load COS NDA signatories')
  } finally {
    signatoryLoading.value = false
  }
}

const onPreview = async () => {
  if (!formRef.value) return
  try {
    await formRef.value.validate()
    generateLoading.value = true

    const response = await cosNDAApi.generatePDF({
      employee_id: formData.value.employee_id,
      signatory_id: formData.value.signatory_id
    })
    const blob = new Blob([response.data], { type: 'application/pdf' })

    if (pdfUrl.value) {
      URL.revokeObjectURL(pdfUrl.value)
    }
    pdfUrl.value = URL.createObjectURL(blob)
    showPreview.value = true
  } catch (error) {
    console.error('Preview error:', error)
    ElMessage.error('Failed to generate preview')
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

const downloadPdf = () => {
  if (!pdfUrl.value) return
  fetch(pdfUrl.value).then(r => r.blob()).then(blob => {
    const safeName = selectedName.value.replace(/\s+/g, '_')
    const link = document.createElement('a')
    link.href = URL.createObjectURL(blob)
    link.download = `cos_non_disclosure_agreement_${safeName}_${new Date().toISOString().split('T')[0]}.pdf`
    link.click()
    URL.revokeObjectURL(link.href)
  })
}

const downloadWord = async () => {
  if (!formRef.value) return
  try {
    await formRef.value.validate()
    generateLoading.value = true

    const response = await cosNDAApi.generateWord({
      employee_id: formData.value.employee_id,
      signatory_id: formData.value.signatory_id
    })
    const blob = new Blob([response.data], { type: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' })
    const safeName = selectedName.value.replace(/\s+/g, '_')
    const link = document.createElement('a')
    link.href = URL.createObjectURL(blob)
    link.download = `cos_non_disclosure_agreement_${safeName}_${new Date().toISOString().split('T')[0]}.docx`
    link.click()
    URL.revokeObjectURL(link.href)
  } catch (error) {
    console.error('Word download error:', error)
    ElMessage.error('Failed to generate Word document')
  } finally {
    generateLoading.value = false
  }
}

const reset = () => {
  formData.value = { employee_id: null, signatory_id: null }
  formRef.value?.resetFields()
  closePreview()
}

onMounted(async () => {
  await Promise.all([fetchEmployees(), fetchSignatories()])
})
</script>

<style scoped>
.w-full { width: 100%; }

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
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
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
