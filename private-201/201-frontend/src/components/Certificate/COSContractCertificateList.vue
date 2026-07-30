<template>
  <PageScaffold title="COS Contract" subtitle="Generate 4-page Contract of Service for COS employees">
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

          <el-form-item label="Signatory" prop="signatory_id">
            <el-select
              v-model="formData.signatory_id"
              placeholder="Select signatory"
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

          <el-form-item label="Witness 1" prop="witness1_id">
            <el-select
              v-model="formData.witness1_id"
              placeholder="Select witness 1"
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

          <el-form-item label="Witness 2" prop="witness2_id">
            <el-select
              v-model="formData.witness2_id"
              placeholder="Select witness 2"
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

          <el-form-item label="Notary City" prop="notary_city">
            <el-input
              v-model="formData.notary_city"
              placeholder="e.g. Makati"
              clearable
              class="w-full"
            />
          </el-form-item>

          <el-form-item label="Item 5 Functions" required>
            <div class="functions-editor w-full">
              <p class="functions-help">
                List the duties under item 5 of the contract. These appear as letters a, b, c, and so on in the report.
              </p>
              <div
                v-for="(item, index) in contractFunctions"
                :key="index"
                class="function-row"
              >
                <span class="function-label">{{ getFunctionLetter(index) }}.</span>
                <el-input
                  v-model="contractFunctions[index]"
                  type="textarea"
                  :rows="2"
                  placeholder="Enter function description"
                />
                <el-button
                  type="danger"
                  link
                  :disabled="contractFunctions.length <= 1"
                  @click="removeFunction(index)"
                >
                  Remove
                </el-button>
              </div>
              <div class="function-actions">
                <el-button size="small" @click="addFunction">Add Function</el-button>
                <el-button size="small" @click="resetFunctions">Reset to Default</el-button>
              </div>
            </div>
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
            {{ selectedName }} - COS Contract
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
          <p class="loading-text">Generating COS Contract...</p>
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
import { cosContractCertificateApi } from '../../services/api'
import { ElMessage } from 'element-plus'

const loading = ref(false)
const signatoryLoading = ref(false)
const generateLoading = ref(false)
const employees = ref([])
const signatories = ref([])

const formRef = ref()
const formData = ref({
  employee_id: null,
  signatory_id: null,
  witness1_id: null,
  witness2_id: null,
  notary_city: ''
})

const DEFAULT_CONTRACT_FUNCTIONS = [
  'Facilitate documentary requirements needed by the LDSD.',
  'Ensure timely and accurate recording and routing of document.',
  'Coordinate with the Section Heads on the instruction given by the DC.',
  'Maintain complete and updated files and records of the LDSD.',
  'Prepare minutes or highlights of the meetings.',
  'Assist in coordinating with other DTI agencies on the scheduling and implementation of requested training activities specifically in the regions.',
  'Coordinate/facilitate/undertake the secretariat services in the preparation for and conducts of training and ensures that the physical facilities are in order at all times.',
  'Organize, maintain and update databank of information, research and training materials on trade and industry, and other subjects relevant to the program of the division.',
  'Prepares and submit training reports and consolidates data bank of reports/documents pertaining to each training.',
  'Perform other related functions as may be assigned from time to time.'
]

const contractFunctions = ref([...DEFAULT_CONTRACT_FUNCTIONS])

const rules = {
  employee_id: [{ required: true, message: 'Please select COS employee', trigger: 'change' }],
  signatory_id: [{ required: true, message: 'Please select signatory', trigger: 'change' }],
  witness1_id: [{ required: true, message: 'Please select witness 1', trigger: 'change' }],
  witness2_id: [{ required: true, message: 'Please select witness 2', trigger: 'change' }]
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
  let contractLabel = ''
  if (emp.contract?.Start_date || emp.contract?.End_date) {
    const start = emp.contract.Start_date || '—'
    const end = emp.contract.End_date || '—'
    contractLabel = ` • ${start} to ${end}`
  }
  return `${base}${pos}${contractLabel}`
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

const getFunctionLetter = (index) => String.fromCharCode(97 + index)

const addFunction = () => {
  contractFunctions.value.push('')
}

const removeFunction = (index) => {
  if (contractFunctions.value.length <= 1) return
  contractFunctions.value.splice(index, 1)
}

const resetFunctions = () => {
  contractFunctions.value = [...DEFAULT_CONTRACT_FUNCTIONS]
}

const getSanitizedFunctions = () =>
  contractFunctions.value.map(item => String(item || '').trim()).filter(Boolean)

const buildGeneratePayload = () => ({
  employee_id: formData.value.employee_id,
  signatory_id: formData.value.signatory_id,
  witness1_id: formData.value.witness1_id,
  witness2_id: formData.value.witness2_id,
  notary_city: String(formData.value.notary_city || '').trim(),
  contract_functions: getSanitizedFunctions()
})

const validateFunctions = () => {
  if (getSanitizedFunctions().length === 0) {
    ElMessage.error('Please add at least one function under item 5.')
    return false
  }
  return true
}

const fetchEmployees = async () => {
  loading.value = true
  try {
    const { data } = await cosContractCertificateApi.getEmployees()
    employees.value = data?.data || []
  } catch (e) {
    console.error(e)
    ElMessage.error('Failed to load COS contract employees')
  } finally {
    loading.value = false
  }
}

const fetchSignatories = async () => {
  signatoryLoading.value = true
  try {
    const { data } = await cosContractCertificateApi.getSignatories()
    signatories.value = data?.data || []
  } catch (e) {
    console.error(e)
    ElMessage.error('Failed to load COS contract signatories')
  } finally {
    signatoryLoading.value = false
  }
}

const onPreview = async () => {
  if (!formRef.value) return
  try {
    await formRef.value.validate()
    if (!validateFunctions()) return
    generateLoading.value = true

    const response = await cosContractCertificateApi.generatePDF(buildGeneratePayload())
    const blob = new Blob([response.data], { type: 'application/pdf' })

    if (pdfUrl.value) {
      URL.revokeObjectURL(pdfUrl.value)
    }
    pdfUrl.value = URL.createObjectURL(blob)
    showPreview.value = true
  } catch (error) {
    console.error('Preview error:', error)
    const message = error?.response?.data?.message || 'Failed to generate preview'
    ElMessage.error(message)
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
    link.download = `cos_contract_certificate_${safeName}_${new Date().toISOString().split('T')[0]}.pdf`
    link.click()
    URL.revokeObjectURL(link.href)
  })
}

const downloadWord = async () => {
  if (!formRef.value) return
  try {
    await formRef.value.validate()
    if (!validateFunctions()) return
    generateLoading.value = true

    const response = await cosContractCertificateApi.generateWord(buildGeneratePayload())
    const blob = new Blob([response.data], { type: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' })

    const safeName = selectedName.value.replace(/\s+/g, '_')
    const link = document.createElement('a')
    link.href = URL.createObjectURL(blob)
    link.download = `cos_contract_certificate_${safeName}_${new Date().toISOString().split('T')[0]}.docx`
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
  formData.value = { employee_id: null, signatory_id: null, witness1_id: null, witness2_id: null, notary_city: '' }
  resetFunctions()
  formRef.value?.resetFields()
  closePreview()
}

onMounted(async () => {
  await Promise.all([fetchEmployees(), fetchSignatories()])
})
</script>

<style scoped>
.w-full { width: 100%; }

.functions-editor {
  border: 1px solid #ebeef5;
  border-radius: 8px;
  padding: 12px;
  background: #fafafa;
}

.functions-help {
  margin: 0 0 12px;
  font-size: 12px;
  color: #909399;
}

.function-row {
  display: grid;
  grid-template-columns: 28px 1fr auto;
  gap: 8px;
  align-items: start;
  margin-bottom: 10px;
}

.function-label {
  font-weight: 600;
  line-height: 32px;
  color: #303133;
}

.function-actions {
  display: flex;
  gap: 8px;
  margin-top: 4px;
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

