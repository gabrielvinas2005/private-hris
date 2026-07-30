<template>
  <PageScaffold title="Certificate of Rendered Service" subtitle="Generate certificate for COS employees">
    <div class="bg-white rounded-lg shadow mb-6">
      <div class="p-6">
        <el-form ref="formRef" :model="formData" :rules="rules" label-width="150px">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <el-form-item label="Employee (COS)" prop="employee_id">
              <el-select
                v-model="formData.employee_id"
                placeholder="Select COS employee"
                filterable
                class="w-full"
                :loading="loading"
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

            <el-form-item label="Covering Period" prop="cover_range">
              <el-date-picker
                v-model="formData.cover_range"
                type="daterange"
                range-separator="to"
                start-placeholder="Start date"
                end-placeholder="End date"
                class="w-full"
              />
            </el-form-item>
          </div>

          <div class="signatories-divider">----------------------------signatories-----------------------</div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <el-form-item label="Noted By" prop="noted_by">
              <el-input v-model="formData.noted_by" placeholder="Name (Noted By)" @blur="updatePreviewIfOpen" />
            </el-form-item>
            <el-form-item label="Noted By Position" prop="noted_by_position">
              <el-input v-model="formData.noted_by_position" placeholder="Position (Noted By)" @blur="updatePreviewIfOpen" />
            </el-form-item>
            <el-form-item label="Approved By" prop="approved_by">
              <el-input v-model="formData.approved_by" placeholder="Name (Approved By)" @blur="updatePreviewIfOpen" />
            </el-form-item>
            <el-form-item label="Approved By Position" prop="approved_by_position">
              <el-input v-model="formData.approved_by_position" placeholder="Position (Approved By)" @blur="updatePreviewIfOpen" />
            </el-form-item>
          </div>
        </el-form>

        <div class="flex justify-end gap-3 mt-4">
          <el-button @click="reset">Reset</el-button>
          <el-button
            type="primary"
            :loading="generateLoading"
            :disabled="!formData.employee_id || !formData.cover_range"
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
          <div class="text-base font-semibold">Report Preview</div>
          <div class="text-xs text-gray-500">Covering period preview</div>
        </div>
        <div class="flex gap-2">
          <el-button type="success" size="small" @click="downloadPdf">Download PDF</el-button>
        </div>
      </div>
      <div class="border rounded overflow-hidden" style="height:75vh;" v-loading="updateLoading">
        <iframe v-if="pdfUrl" :src="pdfUrl" :key="previewKey" class="w-full h-full border-0"></iframe>
        <div v-else class="p-6 text-center text-gray-500">No preview available</div>
      </div>
    </div>
  </PageScaffold>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import PageScaffold from '../../components/PageScaffold.vue'
import { useRenderedService } from '../../composable/useRenderedService'
import { ElMessage } from 'element-plus'

const { loading, generateLoading, employees, fetchEmployees, generateCertificate, downloadPDFFromBlob } = useRenderedService()

const formRef = ref()
const formData = ref({
  employee_id: null,
  cover_range: null,
  noted_by: 'Jocelle P. Mamaril',
  noted_by_position: 'Planning Officer IV/OIC-SMD',
  approved_by: 'Ma. Fe J. Avila',
  approved_by_position: 'OIC-Executive Director'
})
const pdfUrl = ref('')
const showPreview = ref(false)
const previewKey = ref(0)
const updateLoading = ref(false)

const rules = {
  employee_id: [{ required: true, message: 'Employee is required', trigger: 'change' }],
  cover_range: [{ required: true, message: 'Covering period is required', trigger: 'change' }],
  noted_by: [{ required: true, message: 'Noted By name is required', trigger: 'blur' }],
  noted_by_position: [{ required: true, message: 'Noted By position is required', trigger: 'blur' }],
  approved_by: [{ required: true, message: 'Approved By name is required', trigger: 'blur' }],
  approved_by_position: [{ required: true, message: 'Approved By position is required', trigger: 'blur' }]
}

const buildPayload = () => ({
  employee_id: formData.value.employee_id,
  cover_start: formData.value.cover_range?.[0]
    ? new Date(formData.value.cover_range[0]).toISOString().split('T')[0]
    : null,
  cover_end: formData.value.cover_range?.[1]
    ? new Date(formData.value.cover_range[1]).toISOString().split('T')[0]
    : null,
  noted_by: formData.value.noted_by,
  noted_by_position: formData.value.noted_by_position,
  approved_by: formData.value.approved_by,
  approved_by_position: formData.value.approved_by_position
})

const reset = () => {
  formData.value = {
    employee_id: null,
    cover_range: null,
    noted_by: 'Jocelle P. Mamaril',
    noted_by_position: 'Planning Officer IV/OIC-SMD',
    approved_by: 'Ma. Fe J. Avila',
    approved_by_position: 'OIC-Executive Director'
  }
  formRef.value?.resetFields()
  pdfUrl.value = ''
  showPreview.value = false
}

const generate = async () => {
  await formRef.value?.validate()
  if (pdfUrl.value) {
    URL.revokeObjectURL(pdfUrl.value)
  }
  generateLoading.value = true
  try {
    const url = await generateCertificate(buildPayload())
    pdfUrl.value = url
    previewKey.value++
    showPreview.value = true
    ElMessage.success('Report generated')
  } catch (e) {
    // handled above
  } finally {
    generateLoading.value = false
  }
}

const updatePreviewIfOpen = async () => {
  if (!showPreview.value) return
  await formRef.value?.validate().catch(() => {})
  updateLoading.value = true
  try {
    if (pdfUrl.value) {
      URL.revokeObjectURL(pdfUrl.value)
    }
    const url = await generateCertificate(buildPayload(), { manageLoading: false })
    pdfUrl.value = url
    previewKey.value++
  } catch (e) {
    // handled above
  } finally {
    updateLoading.value = false
  }
}

const downloadPdf = () => {
  if (!pdfUrl.value) return
  fetch(pdfUrl.value).then(r => r.blob()).then(blob => {
    const filename = `rendered_service_certificate_${new Date().toISOString().split('T')[0]}.pdf`
    downloadPDFFromBlob(blob, filename)
  })
}

onMounted(async () => {
  await fetchEmployees()
})
</script>

<style scoped>
.w-full { width: 100%; }

.signatories-divider {
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