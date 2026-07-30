<template>
  <PageScaffold title="Certificate of Completion" subtitle="Generate certificate of completion for interns">
    <div class="bg-white rounded-lg shadow mb-6">
      <div class="p-6">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-semibold">Active Students</h3>
          <el-button type="primary" :icon="Refresh" @click="loadStudents" :loading="loading">Refresh</el-button>
        </div>

        <el-table
          :data="students"
          v-loading="loading"
          stripe
          border
          style="width: 100%"
          @selection-change="handleSelectionChange"
        >
          <el-table-column type="selection" width="55" />
          <el-table-column prop="school_name" label="School Name" min-width="200" />
          <el-table-column prop="first_name" label="First Name" min-width="140" />
          <el-table-column prop="middle_name" label="Middle Name" min-width="140">
            <template #default="{ row }">
              <span class="text-gray-400">{{ row.middle_name || '-' }}</span>
            </template>
          </el-table-column>
          <el-table-column prop="last_name" label="Last Name" min-width="140" />
          <el-table-column label="Full Name" min-width="200">
            <template #default="{ row }">
              <span class="font-medium">
                {{ [row.first_name, row.middle_name, row.last_name].filter(Boolean).join(' ') }}
              </span>
            </template>
          </el-table-column>
          <el-table-column prop="school_officer" label="School Officer" min-width="180" />
          <el-table-column prop="date_of_start" label="Start Date" width="150">
            <template #default="{ row }">
              {{ formatDate(row.date_of_start) }}
            </template>
          </el-table-column>
        </el-table>

        <el-empty
          v-if="!loading && students.length === 0"
          description="No active students found"
        />
      </div>
    </div>

    <!-- Certificate Form -->
    <div class="bg-white rounded-lg shadow mb-6">
      <div class="p-6">
        <el-form ref="formRef" :model="formData" :rules="rules" label-width="180px">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <el-form-item label="Student title" prop="student_title">
              <el-radio-group v-model="formData.student_title">
                <el-radio label="mr">Mr.</el-radio>
                <el-radio label="ms">Ms.</el-radio>
              </el-radio-group>
              <div class="text-xs text-gray-500 mt-1 ml-4">Used for all selected students on this certificate run.</div>
            </el-form-item>
            <el-form-item label="Completion Date" prop="completion_date">
              <el-date-picker
                v-model="formData.completion_date"
                type="date"
                placeholder="Select completion date"
                class="w-full"
              />
            </el-form-item>
            <el-form-item label="Hours" prop="hours">
              <el-input-number
                v-model="formData.hours"
                :min="1"
                :max="1000"
                placeholder="Enter hours"
                class="w-full"
              />
            </el-form-item>
            <el-form-item label="Course" prop="course">
              <el-input v-model="formData.course" placeholder="e.g., Bachelor of Science in Psychology" />
            </el-form-item>
            <el-form-item label="Unit/Division" prop="unit">
              <el-input
                v-model="formData.unit"
                type="textarea"
                :rows="2"
                placeholder="Enter unit/division"
              />
            </el-form-item>
            <el-form-item label="Signatory" prop="signatory">
              <el-input v-model="formData.signatory" placeholder="Enter signatory name" />
            </el-form-item>
            <el-form-item label="Position" prop="position">
              <el-input v-model="formData.position" placeholder="Enter signatory position" />
            </el-form-item>
          </div>

          <div class="flex justify-end gap-3 mt-6">
            <el-button @click="reset">Reset</el-button>
            <el-button
              type="primary"
              :loading="generateLoading"
              :disabled="selectedStudents.length === 0"
              @click="generateCertificate"
            >
              Generate Certificate
            </el-button>
          </div>
        </el-form>
      </div>
    </div>

    <!-- Preview Section -->
    <div v-if="showPreview" class="mt-6 bg-white rounded-lg shadow p-4">
      <div class="flex items-center justify-between mb-3">
        <div>
          <div class="text-base font-semibold">Certificate of Completion Preview</div>
          <div class="text-xs text-gray-500">Preview below reflects your latest inputs</div>
        </div>
        <div class="flex items-center gap-2">
          <span class="text-sm text-gray-600">Download as:</span>
          <el-button type="danger" size="small" @click="downloadPdf">PDF</el-button>
          <el-button type="primary" size="small" @click="handleDownloadWord" :loading="generateLoading">Word</el-button>
        </div>
      </div>

      <div class="border rounded overflow-hidden" style="height:75vh;">
        <iframe
          v-if="pdfUrl"
          :src="pdfUrl"
          class="w-full h-full border-0"
        ></iframe>
        <div v-else class="p-6 text-center text-gray-500">No preview available</div>
      </div>
    </div>
  </PageScaffold>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Refresh } from '@element-plus/icons-vue'
import { ElMessage } from 'element-plus'
import PageScaffold from '../../components/PageScaffold.vue'
import { useCertOfCompletion } from '../../composable/useCertOfCompletion.js'

const { loading, generateLoading, students, fetchActiveStudents, generateCertificate: generateCert, downloadWord, downloadPDFFromBlob } = useCertOfCompletion()

const formRef = ref()
const selectedStudents = ref([])
const showPreview = ref(false)
const pdfUrl = ref('')

const formData = ref({
  student_title: 'mr',
  completion_date: '',
  hours: 300,
  course: '',
  unit: 'Human Resources Unit under the Administrative and Financial Management Division (AFMD)',
  signatory: 'EDUARDO A. PUYAOAN JR.',
  position: 'Chief Administrative Officer'
})

const rules = {
  student_title: [{ required: true, message: 'Please select Mr. or Ms.', trigger: 'change' }],
  completion_date: [{ required: true, message: 'Completion date is required', trigger: 'change' }],
  hours: [{ required: true, message: 'Hours is required', trigger: 'blur' }],
  signatory: [{ required: true, message: 'Signatory is required', trigger: 'blur' }],
  position: [{ required: true, message: 'Position is required', trigger: 'blur' }]
}

const loadStudents = async () => {
  try {
    await fetchActiveStudents()
  } catch (error) {
    console.error('Failed to load students:', error)
  }
}

const handleSelectionChange = (selection) => {
  selectedStudents.value = selection
}

const generateCertificate = async () => {
  await formRef.value?.validate()

  if (selectedStudents.value.length === 0) {
    ElMessage.warning('Please select at least one student')
    return
  }

  try {
    const studentIds = selectedStudents.value.map(s => s.id)
    const url = await generateCert({
      student_ids: studentIds,
      student_title: formData.value.student_title,
      completion_date: formData.value.completion_date,
      hours: formData.value.hours,
      course: formData.value.course,
      unit: formData.value.unit,
      signatory: formData.value.signatory,
      position: formData.value.position
    })
    
    pdfUrl.value = url
    showPreview.value = true
    
    // Refresh students list after generation (to reflect is_active = 0)
    await loadStudents()
    selectedStudents.value = []
    
    ElMessage.success('Certificate generated successfully. Selected students have been marked as inactive.')
  } catch (error) {
    console.error('Failed to generate certificate:', error)
  }
}

const downloadPdf = () => {
  if (!pdfUrl.value) return
  fetch(pdfUrl.value).then(r => r.blob()).then(blob => {
    downloadPDFFromBlob(blob, `certificate_of_completion_${new Date().toISOString().split('T')[0]}.pdf`)
  })
}

const handleDownloadWord = async () => {
  try {
    if (selectedStudents.value.length === 0) {
      ElMessage.warning('Please select at least one student')
      return
    }
    
    const studentIds = selectedStudents.value.map(s => s.id)
    const blob = await downloadWord({
      student_ids: studentIds,
      student_title: formData.value.student_title,
      completion_date: formData.value.completion_date,
      hours: formData.value.hours,
      course: formData.value.course,
      unit: formData.value.unit,
      signatory: formData.value.signatory,
      position: formData.value.position
    })
    if (!blob) return
    const fileName = `certificate_of_completion_${new Date().toISOString().split('T')[0]}.docx`
    downloadPDFFromBlob(blob, fileName)
  } catch (error) {
    console.error('Word download failed:', error)
  }
}

const reset = () => {
  formData.value = {
    student_title: 'mr',
    completion_date: '',
    hours: 300,
    course: '',
    unit: 'Human Resources Unit under the Administrative and Financial Management Division (AFMD)',
    signatory: 'EDUARDO A. PUYAOAN JR.',
    position: 'Chief Administrative Officer'
  }
  formRef.value?.resetFields()
  selectedStudents.value = []
  showPreview.value = false
  pdfUrl.value = ''
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}

onMounted(async () => {
  await loadStudents()
})
</script>

<style scoped>
.w-full { width: 100%; }
</style>
