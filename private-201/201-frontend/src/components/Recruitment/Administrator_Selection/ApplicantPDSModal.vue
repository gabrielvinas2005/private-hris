<template>
  <el-dialog 
    v-model="visible" 
    :title="`Personal Data Sheet - ${applicantData?.name || ''}`"
    width="95%"
    top="3vh"
    destroy-on-close
    class="pds-dialog"
  >
    <div v-if="loading" class="loading-container">
      <el-icon class="is-loading loading-icon">
        <Loading />
      </el-icon>
      <p class="loading-text">Loading Personal Data Sheet...</p>
    </div>
    
    <div v-else-if="pdsData" class="pds-container">
      <el-form label-position="top" size="small">
        <el-row :gutter="20" class="mb-4">
          <!-- Left sidebar (summary) -->
          <el-col :span="6">
            <el-card shadow="never" class="summary-card">
              <div class="avatar-box">
                <el-avatar :size="96">
                  <el-icon :size="60"><User /></el-icon>
                </el-avatar>
              </div>
              <el-divider />
              <div class="section-title">Applicant No.</div>
              <el-input
                :model-value="pdsData.employee_info?.[0]?.employee_no || ''"
                disabled
                placeholder="Applicant Number"
              />
              <div class="section-title mt-2">Full Name</div>
              <el-input
                :model-value="getFullName(pdsData.employee_info?.[0]) || ''"
                disabled
                placeholder="Full Name"
              />
            </el-card>

            <el-card shadow="never" class="mt-2">
              <div class="section-heading">Contact Information</div>
              <el-form-item label="Email Address">
                <el-input :model-value="pdsData.employee_info?.[0]?.email || ''" disabled />
              </el-form-item>
              <el-form-item label="Mobile Number">
                <el-input :model-value="pdsData.employee_info?.[0]?.mobile_no || ''" disabled />
              </el-form-item>
            </el-card>
          </el-col>

          <!-- Right content -->
          <el-col :span="18">
            <!-- Personal Information Section -->
            <el-card shadow="never" class="section-card">
              <div class="section-heading">Personal Information</div>
              <el-row :gutter="16">
                <el-col :span="8">
                  <el-form-item label="Birth Date">
                    <el-input
                      :model-value="formatDate(pdsData.employee_info?.[0]?.birthdate) || ''"
                      disabled
                    />
                  </el-form-item>
                </el-col>
                <el-col :span="8">
                  <el-form-item label="Gender">
                    <el-input
                      :model-value="pdsData.employee_info?.[0]?.gender || ''"
                      disabled
                    />
                  </el-form-item>
                </el-col>
                <el-col :span="8">
                  <el-form-item label="Civil Status">
                    <el-input
                      :model-value="getCivilStatus(pdsData.employee_info?.[0]?.civil_status_id) || ''"
                      disabled
                    />
                  </el-form-item>
                </el-col>
              </el-row>
              <el-row :gutter="16">
                <el-col :span="12">
                  <el-form-item label="Citizenship">
                    <el-input
                      :model-value="getCitizenship(pdsData.employee_info?.[0]?.citizenship_id) || ''"
                      disabled
                    />
                  </el-form-item>
                </el-col>
              </el-row>
            </el-card>

            <!-- Address Section -->
            <el-card shadow="never" class="section-card mt-2">
              <div class="section-heading">Address</div>
              <el-form-item label="Present Address">
                <el-input
                  :model-value="getAddress(pdsData.employee_info?.[0], 'pa') || ''"
                  type="textarea"
                  :rows="2"
                  disabled
                />
              </el-form-item>
              <el-form-item label="Permanent Address">
                <el-input
                  :model-value="getAddress(pdsData.employee_info?.[0], 'ra') || ''"
                  type="textarea"
                  :rows="2"
                  disabled
                />
              </el-form-item>
            </el-card>

            <!-- Family Information Section -->
            <el-card shadow="never" class="section-card mt-2">
              <div class="section-heading">Family Information</div>
              <el-row :gutter="16">
                <el-col :span="12">
                  <el-form-item label="Father's Name">
                    <el-input
                      :model-value="getFatherName(pdsData.employee_info?.[0]) || ''"
                      disabled
                    />
                  </el-form-item>
                </el-col>
                <el-col :span="12">
                  <el-form-item label="Mother's Name">
                    <el-input
                      :model-value="getMotherName(pdsData.employee_info?.[0]) || ''"
                      disabled
                    />
                  </el-form-item>
                </el-col>
              </el-row>
              <el-form-item label="Spouse's Name">
                <el-input
                  :model-value="getSpouseName(pdsData.employee_info?.[0]) || ''"
                  disabled
                />
              </el-form-item>
            </el-card>
          </el-col>
        </el-row>

        <!-- Table sections, full width -->
        <el-card shadow="never" class="section-card">
          <div class="section-heading">Educational Background</div>
          <el-table
            :data="pdsData.educations || []"
            size="default"
            border
            stripe
            empty-text="No educational background recorded"
            class="data-table"
          >
            <el-table-column prop="school_name" label="School/Institution" min-width="200" />
            <el-table-column label="Degree/Course" min-width="180" show-overflow-tooltip>
              <template #default="{ row }">
                {{ row.program ?? row.degree ?? '' }}
              </template>
            </el-table-column>
            <el-table-column prop="graduated_year" label="Year Graduated" width="140" align="center" />
            <el-table-column label="Honors/Awards" min-width="150" show-overflow-tooltip>
              <template #default="{ row }">
                {{ row.honors ?? row.honors_received ?? '' }}
              </template>
            </el-table-column>
          </el-table>
        </el-card>

        <el-card shadow="never" class="section-card">
          <div class="section-heading">Work Experience</div>
          <el-table
            :data="pdsData.employments || []"
            size="default"
            border
            stripe
            empty-text="No work experience recorded"
            class="data-table"
          >
            <el-table-column prop="position" label="Position" min-width="180" />
            <el-table-column prop="company" label="Company/Agency" min-width="200" />
            <el-table-column prop="date_from" label="From" width="120" align="center" />
            <el-table-column prop="date_to" label="To" width="120" align="center" />
            <el-table-column prop="salary" label="Salary" width="140" align="right">
              <template #default="{ row }">
                {{ row.salary ? formatCurrency(row.salary) : 'N/A' }}
              </template>
            </el-table-column>
          </el-table>
        </el-card>

        <el-card shadow="never" class="section-card">
          <div class="section-heading">Training Programs</div>
          <el-table
            :data="pdsData.trainings || []"
            size="default"
            border
            stripe
            empty-text="No training programs recorded"
            class="data-table"
          >
            <el-table-column prop="training_title" label="Training Title" min-width="220" />
            <el-table-column prop="training_institution" label="Institution" min-width="200" />
            <el-table-column prop="training_date_from" label="From" width="120" align="center" />
            <el-table-column prop="training_date_to" label="To" width="120" align="center" />
            <el-table-column prop="training_hours" label="Hours" width="100" align="center" />
          </el-table>
        </el-card>

        <el-card shadow="never" class="section-card">
          <div class="section-heading">Eligibility</div>
          <el-table
            :data="pdsData.examinations || []"
            size="default"
            border
            stripe
            empty-text="No eligibility examinations recorded"
            class="data-table"
          >
            <el-table-column prop="examination_title" label="Examination" min-width="220" />
            <el-table-column prop="rating" label="Rating" width="120" align="center" />
            <el-table-column prop="examination_date" label="Date" width="140" align="center">
              <template #default="{ row }">
                {{ formatDate(row.examination_date) || 'N/A' }}
              </template>
            </el-table-column>
            <el-table-column prop="examination_place" label="Place" min-width="200" />
          </el-table>
        </el-card>

        <el-card shadow="never" class="section-card">
          <div class="section-heading">Uploaded Documents</div>
          <p class="text-xs text-gray-500 mb-2">
            PDS attachments linked to the applicant record, plus files uploaded with the application (e.g. resume).
          </p>

          <div class="subsection-title">PDS / employee documents</div>
          <el-table
            :data="pdsData.documents || []"
            size="default"
            border
            stripe
            empty-text="No PDS documents on file"
            class="data-table mb-4"
          >
            <el-table-column prop="document_type" label="Type" min-width="160" show-overflow-tooltip />
            <el-table-column prop="attachment_name" label="File name" min-width="220" show-overflow-tooltip />
            <el-table-column prop="description" label="Description" min-width="180" show-overflow-tooltip />
            <el-table-column label="Actions" width="180" align="center" fixed="right">
              <template #default="{ row }">
                <template v-if="row.employee_document_id">
                  <el-button type="primary" link size="small" @click="openEmployeeDocumentPreview(row)">
                    Preview
                  </el-button>
                  <el-button type="primary" link size="small" @click="downloadEmployeeDocument(row.employee_document_id, row.attachment_name)">
                    Download
                  </el-button>
                </template>
                <span v-else class="text-gray-400">—</span>
              </template>
            </el-table-column>
          </el-table>

          <div class="subsection-title">Application uploads</div>
          <el-table
            :data="pdsData.applicant_attachments || []"
            size="default"
            border
            stripe
            empty-text="No applicant uploads on file"
            class="data-table"
          >
            <el-table-column prop="attachment_name" label="File name" min-width="260" show-overflow-tooltip />
            <el-table-column prop="file_type" label="Type" width="140" show-overflow-tooltip />
            <el-table-column label="Size" width="100" align="right">
              <template #default="{ row }">
                {{ formatFileSize(row.file_size) }}
              </template>
            </el-table-column>
            <el-table-column label="Uploaded" width="140" align="center">
              <template #default="{ row }">
                {{ formatDate(row.created_at) || '—' }}
              </template>
            </el-table-column>
            <el-table-column label="Actions" width="180" align="center" fixed="right">
              <template #default="{ row }">
                <template v-if="row.id">
                  <el-button type="primary" link size="small" @click="openApplicantAttachmentPreview(row)">
                    Preview
                  </el-button>
                  <el-button type="primary" link size="small" @click="downloadApplicantAttachment(row.id, row.attachment_name)">
                    Download
                  </el-button>
                </template>
                <span v-else class="text-gray-400">—</span>
              </template>
            </el-table-column>
          </el-table>
        </el-card>
      </el-form>
    </div>

    <template #footer>
      <div class="dialog-footer">
        <el-button @click="visible = false" size="default">Close</el-button>
      </div>
    </template>
  </el-dialog>

  <!-- Document preview (PDS uploads & applicant attachments) -->
  <el-dialog
    v-model="showDocumentModal"
    :title="`Document: ${currentDocument?.attachment_name || 'Preview'}`"
    width="90%"
    top="4vh"
    append-to-body
    :close-on-click-modal="false"
    destroy-on-close
    class="doc-preview-dialog"
    @closed="onDocumentPreviewClosed"
  >
    <div v-if="documentError" class="doc-preview-error">
      <el-icon :size="64" class="doc-preview-error-icon">
        <Warning />
      </el-icon>
      <p class="doc-preview-error-text">
        Failed to load document preview. The file may be missing or in a format that cannot be shown here.
      </p>
      <el-button type="primary" @click="downloadFromPreviewModal">Download file</el-button>
    </div>
    <div v-else-if="documentLoading" class="doc-preview-loading">
      <el-icon class="is-loading" :size="48" style="color: #409eff;">
        <Loading />
      </el-icon>
      <p class="doc-preview-loading-text">Loading preview…</p>
    </div>
    <div v-else-if="documentPreviewUrl && documentFileType === 'pdf'" class="doc-preview-body">
      <iframe
        :src="documentPreviewUrl"
        class="doc-preview-frame"
        title="Document preview"
      />
    </div>
    <div v-else-if="documentPreviewUrl && documentFileType === 'image'" class="doc-preview-body">
      <img
        :src="documentPreviewUrl"
        class="doc-preview-img"
        alt="Document preview"
      />
    </div>
    <div v-else-if="documentFileType === 'excel'" class="doc-preview-fallback">
      <el-icon :size="64" style="color: #67c23a; margin-bottom: 16px;">
        <Document />
      </el-icon>
      <p class="doc-preview-fallback-title">Spreadsheet</p>
      <p class="doc-preview-fallback-hint">Excel files cannot be previewed in the browser. Use Download to open the file.</p>
      <el-button type="success" @click="downloadFromPreviewModal">
        <el-icon class="mr-1"><Download /></el-icon>
        Download
      </el-button>
    </div>
    <div v-else-if="documentFileType === 'word'" class="doc-preview-fallback">
      <el-icon :size="64" style="color: #409eff; margin-bottom: 16px;">
        <Document />
      </el-icon>
      <p class="doc-preview-fallback-title">Word document</p>
      <p class="doc-preview-fallback-hint">Word files cannot be previewed in the browser. Use Download to open the file.</p>
      <el-button type="primary" @click="downloadFromPreviewModal">
        <el-icon class="mr-1"><Download /></el-icon>
        Download
      </el-button>
    </div>
    <div v-else class="doc-preview-fallback">
      <el-icon :size="64" style="color: #909399; margin-bottom: 16px;">
        <Document />
      </el-icon>
      <p class="doc-preview-fallback-title">Preview not available</p>
      <p class="doc-preview-fallback-hint">This file type cannot be shown inline. Use Download to open it.</p>
      <el-button type="primary" @click="downloadFromPreviewModal">Download</el-button>
    </div>
    <template #footer>
      <el-button @click="showDocumentModal = false">Close</el-button>
      <el-button
        v-if="currentDocument && (documentFileType === 'pdf' || documentFileType === 'image') && documentPreviewUrl"
        type="primary"
        @click="downloadFromPreviewModal"
      >
        Download
      </el-button>
    </template>
  </el-dialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import api, { adminSelectApi } from '@/services/api'
import { ElMessage } from 'element-plus'
import {
  Loading,
  User,
  Document,
  Download,
  Warning
} from '@element-plus/icons-vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  applicantData: { type: Object, default: null }
})

const emit = defineEmits(['update:modelValue'])

const visible = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

const loading = ref(false)
const pdsData = ref(null)

// Nested document preview
const showDocumentModal = ref(false)
const currentDocument = ref(null)
const documentPreviewUrl = ref('')
const documentFileType = ref('')
const documentLoading = ref(false)
const documentError = ref(false)

const revokePreviewBlob = () => {
  if (documentPreviewUrl.value && documentPreviewUrl.value.startsWith('blob:')) {
    URL.revokeObjectURL(documentPreviewUrl.value)
  }
  documentPreviewUrl.value = ''
}

const onDocumentPreviewClosed = () => {
  revokePreviewBlob()
  currentDocument.value = null
  documentFileType.value = ''
  documentError.value = false
  documentLoading.value = false
}

// Watch for dialog open and fetch PDS data
watch(visible, (newVal) => {
  if (newVal && props.applicantData) {
    fetchPDSData()
  } else if (!newVal) {
    showDocumentModal.value = false
    onDocumentPreviewClosed()
  }
})

const fetchPDSData = async () => {
  if (!props.applicantData?.applicant_id) return
  
  loading.value = true
  try {
    const { data } = await adminSelectApi.pds(props.applicantData.applicant_id)
    pdsData.value = data.data
  } catch (error) {
    console.error('Error fetching PDS data:', error)
    ElMessage.error('Failed to load PDS data')
  } finally {
    loading.value = false
  }
}

// Helper functions to format data
const getFullName = (employee) => {
  if (!employee) return 'N/A'
  const parts = [employee.first_name, employee.middle_name, employee.last_name].filter(Boolean)
  return parts.join(' ') || 'N/A'
}

const getCivilStatus = (id) => {
  if (!pdsData.value?.civil_status || !id) return null
  const status = pdsData.value.civil_status.find(s => s.id === id)
  return status?.name || null
}

const getCitizenship = (id) => {
  if (!pdsData.value?.citizenships || !id) return null
  const citizenship = pdsData.value.citizenships.find(c => c.id === id)
  return citizenship?.name || null
}

const getAddress = (employee, type) => {
  if (!employee) return null
  const prefix = type === 'pa' ? 'pa_' : 'ra_'
  const parts = [
    employee[`${prefix}house_no`],
    employee[`${prefix}street`],
    employee[`${prefix}barangay`],
    employee[`${prefix}village`],
    employee[`${prefix}city`],
    employee[`${prefix}province`],
    employee[`${prefix}region`]
  ].filter(Boolean)
  return parts.join(', ') || null
}

const getFatherName = (employee) => {
  if (!employee) return null
  const parts = [
    employee.father_first_name,
    employee.father_middle_name,
    employee.father_last_name
  ].filter(Boolean)
  return parts.join(' ') || null
}

const getMotherName = (employee) => {
  if (!employee) return null
  const parts = [
    employee.mother_first_name,
    employee.mother_middle_name,
    employee.mother_last_name
  ].filter(Boolean)
  return parts.join(' ') || null
}

const getSpouseName = (employee) => {
  if (!employee) return null
  const parts = [
    employee.spouse_first_name,
    employee.spouse_middle_name,
    employee.spouse_last_name
  ].filter(Boolean)
  return parts.join(' ') || null
}

const formatDate = (date) => {
  if (!date) return null
  try {
    const d = new Date(date)
    return d.toLocaleDateString('en-US', { 
      year: 'numeric', 
      month: 'long', 
      day: 'numeric' 
    })
  } catch {
    return date
  }
}

const formatCurrency = (amount) => {
  if (!amount) return 'N/A'
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
    minimumFractionDigits: 2
  }).format(amount)
}

const sanitizeFilename = (name) => {
  if (!name || typeof name !== 'string') return 'document'
  return name.replace(/[^\w\s\-.]/g, '_').trim() || 'document'
}

const triggerBlobDownload = (blob, filename) => {
  const url = window.URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = sanitizeFilename(filename)
  document.body.appendChild(a)
  a.click()
  document.body.removeChild(a)
  window.URL.revokeObjectURL(url)
}

const downloadEmployeeDocument = async (employeeDocumentId, attachmentName) => {
  if (!employeeDocumentId) return
  try {
    const res = await api.get(`/employee-documents/${employeeDocumentId}/download`, { responseType: 'blob' })
    triggerBlobDownload(res.data, attachmentName || `document_${employeeDocumentId}`)
    ElMessage.success('Download started')
  } catch (e) {
    console.error(e)
    ElMessage.error(e?.response?.data?.message || 'Failed to download document')
  }
}

const downloadApplicantAttachment = async (attachmentId, attachmentName) => {
  if (!attachmentId) return
  try {
    const res = await api.get(`/applicant-resume/${attachmentId}`, { responseType: 'blob' })
    triggerBlobDownload(res.data, attachmentName || `attachment_${attachmentId}`)
    ElMessage.success('Download started')
  } catch (e) {
    console.error(e)
    ElMessage.error(e?.response?.data?.message || 'Failed to download file')
  }
}

const formatFileSize = (bytes) => {
  const n = Number(bytes)
  if (!n || Number.isNaN(n) || n < 0) return '—'
  if (n < 1024) return `${n} B`
  if (n < 1024 * 1024) return `${(n / 1024).toFixed(1)} KB`
  return `${(n / (1024 * 1024)).toFixed(1)} MB`
}

const normalizeServerUrl = (url) => {
  if (!url) return ''
  try {
    const apiBaseUrl = import.meta.env.VITE_API_URL || 'http://localhost:8000'
    const serverBaseUrl = apiBaseUrl.replace(/\/api$/, '')
    if (/^https?:\/\//i.test(url)) {
      const parsed = new URL(url)
      if (serverBaseUrl && parsed.origin !== serverBaseUrl) {
        return `${serverBaseUrl}${parsed.pathname}${parsed.search}`
      }
      return url
    }
  } catch {
    /* ignore */
  }
  const apiBaseUrl = import.meta.env.VITE_API_URL || 'http://localhost:8000'
  const serverBaseUrl = apiBaseUrl.replace(/\/api$/, '')
  const cleanPath = url.startsWith('/') ? url : `/${url}`
  return serverBaseUrl ? `${serverBaseUrl}${cleanPath}` : cleanPath
}

const getFileType = (filename) => {
  if (!filename) return 'other'
  const ext = String(filename).split('.').pop()?.toLowerCase() || ''
  if (ext === 'pdf') return 'pdf'
  if (['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'].includes(ext)) return 'image'
  if (['xlsx', 'xls', 'csv'].includes(ext)) return 'excel'
  if (['docx', 'doc'].includes(ext)) return 'word'
  return 'other'
}

const mimeToPreviewKind = (mime) => {
  const m = (mime || '').toLowerCase()
  if (m.includes('pdf')) return 'pdf'
  if (m.startsWith('image/')) return 'image'
  if (m.includes('spreadsheet') || m.includes('excel') || m.includes('csv')) return 'excel'
  if (m.includes('word') || m.includes('msword')) return 'word'
  return 'other'
}

const downloadFromPreviewModal = async () => {
  if (!currentDocument.value) return
  if (currentDocument.value.kind === 'employee') {
    await downloadEmployeeDocument(
      currentDocument.value.employee_document_id,
      currentDocument.value.attachment_name
    )
  } else {
    await downloadApplicantAttachment(
      currentDocument.value.attachment_id,
      currentDocument.value.attachment_name
    )
  }
}

const openEmployeeDocumentPreview = async (row) => {
  if (!row?.employee_document_id || !row?.attachment_name) return

  const employeeId = row.employee_id ?? pdsData.value?.employee_info?.[0]?.id
  if (!employeeId) {
    ElMessage.error('Employee record not found for this document.')
    return
  }

  revokePreviewBlob()
  currentDocument.value = {
    kind: 'employee',
    employee_document_id: row.employee_document_id,
    attachment_name: row.attachment_name,
    path: row.path,
    employee_id: employeeId
  }
  documentFileType.value = getFileType(row.attachment_name)
  documentError.value = false
  documentPreviewUrl.value = ''
  showDocumentModal.value = true

  if (documentFileType.value === 'excel' || documentFileType.value === 'word') {
    documentLoading.value = false
    return
  }

  documentLoading.value = true
  try {
    const isZipped = row.path && String(row.path).includes('HRMS')
    if (isZipped && (documentFileType.value === 'pdf' || documentFileType.value === 'image')) {
      try {
        const previewResponse = await api.get(`/employee-documents/${row.employee_document_id}/preview`)
        const url = previewResponse.data?.data?.preview_url ?? previewResponse.data?.preview_url
        if (url) {
          documentPreviewUrl.value = normalizeServerUrl(url)
          return
        }
      } catch {
        /* fall through to blob download */
      }
    }

    const downloadResponse = await api.get(`/employee-documents/${row.employee_document_id}/download`, {
      responseType: 'blob'
    })
    const blob = downloadResponse.data
    if (!blob || blob.size === 0) throw new Error('Empty file')

    if (blob.type && blob.type.includes('json')) {
      const text = await blob.text()
      let msg = 'Failed to load document'
      try {
        const j = JSON.parse(text)
        msg = j.message || msg
      } catch {
        /* ignore */
      }
      throw new Error(msg)
    }

    documentPreviewUrl.value = URL.createObjectURL(blob)
  } catch (e) {
    console.error(e)
    documentError.value = true
    ElMessage.error(e?.message || e?.response?.data?.message || 'Failed to load preview')
  } finally {
    documentLoading.value = false
  }
}

const openApplicantAttachmentPreview = async (row) => {
  if (!row?.id || !row?.attachment_name) return

  revokePreviewBlob()
  currentDocument.value = {
    kind: 'applicant',
    attachment_id: row.id,
    attachment_name: row.attachment_name
  }
  documentFileType.value = getFileType(row.attachment_name)
  documentError.value = false
  documentPreviewUrl.value = ''
  showDocumentModal.value = true

  if (documentFileType.value === 'excel' || documentFileType.value === 'word') {
    documentLoading.value = false
    return
  }

  documentLoading.value = true
  try {
    const res = await api.get(`/applicant-resume/${row.id}`, {
      responseType: 'blob',
      params: { inline: true }
    })
    const blob = res.data
    if (!blob || blob.size === 0) throw new Error('Empty file')

    if (blob.type && blob.type.includes('json')) {
      const text = await blob.text()
      let msg = 'Failed to load file'
      try {
        const j = JSON.parse(text)
        msg = j.message || msg
      } catch {
        /* ignore */
      }
      throw new Error(msg)
    }

    const fromMime = mimeToPreviewKind(blob.type)
    if (fromMime === 'pdf' || fromMime === 'image') {
      documentFileType.value = fromMime
      documentPreviewUrl.value = URL.createObjectURL(blob)
      return
    }

    documentFileType.value = getFileType(row.attachment_name)
    if (documentFileType.value === 'pdf' || documentFileType.value === 'image') {
      documentPreviewUrl.value = URL.createObjectURL(blob)
      return
    }

    revokePreviewBlob()
    documentFileType.value = fromMime !== 'other' ? fromMime : getFileType(row.attachment_name)
  } catch (e) {
    console.error(e)
    documentError.value = true
    ElMessage.error(e?.message || e?.response?.data?.message || 'Failed to load preview')
  } finally {
    documentLoading.value = false
  }
}
</script>

<style scoped>
:deep(.pds-dialog .el-dialog__body) {
  padding: 20px;
  max-height: calc(90vh - 120px);
  overflow-y: auto;
}

.loading-container {
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  min-height: 400px;
  gap: 16px;
}

.loading-icon {
  font-size: 48px;
  color: #409eff;
}

.loading-text {
  color: #606266;
  font-size: 14px;
  margin: 0;
}

.pds-container {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.section-card {
  margin-bottom: 0;
  border-radius: 8px;
  transition: all 0.3s ease;
}

.section-card:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.card-header {
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 600;
}

.header-icon {
  font-size: 18px;
  color: #409eff;
}

.card-title {
  font-size: 16px;
  color: #303133;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20px;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.info-item.full-width {
  grid-column: 1 / -1;
}

.field-label {
  font-weight: 600;
  color: #606266;
  font-size: 13px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.field-value {
  color: #303133;
  font-size: 14px;
  padding: 8px 12px;
  background-color: #f5f7fa;
  border-radius: 4px;
  min-height: 20px;
  word-break: break-word;
}

.data-table {
  margin-top: 12px;
}

.subsection-title {
  font-size: 13px;
  font-weight: 600;
  color: #606266;
  margin: 12px 0 8px;
}

.text-gray-400 {
  color: #c0c4cc;
}

.text-gray-500 {
  color: #909399;
}

.text-xs {
  font-size: 12px;
}

.mb-2 {
  margin-bottom: 8px;
}

.mb-4 {
  margin-bottom: 16px;
}

.data-table :deep(.el-table__header) {
  background-color: #f5f7fa;
}

.data-table :deep(.el-table__header th) {
  background-color: #f5f7fa;
  color: #303133;
  font-weight: 600;
  font-size: 13px;
}

.data-table :deep(.el-table__body tr:hover > td) {
  background-color: #ecf5ff;
}

.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  padding: 12px 0;
}

.mr-2 {
  margin-right: 8px;
}

/* Scrollbar styling */
:deep(.el-dialog__body)::-webkit-scrollbar {
  width: 8px;
}

:deep(.el-dialog__body)::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 4px;
}

:deep(.el-dialog__body)::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 4px;
}

:deep(.el-dialog__body)::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}

/* Responsive design */
@media (max-width: 768px) {
  .info-grid {
    grid-template-columns: 1fr;
  }
  
  .info-item.full-width {
    grid-column: 1;
  }
}

:deep(.doc-preview-dialog .el-dialog__body) {
  padding: 16px 20px;
  min-height: 200px;
}

.doc-preview-error,
.doc-preview-loading,
.doc-preview-fallback {
  text-align: center;
  padding: 40px 24px;
  background: #f5f7fa;
  border-radius: 8px;
}

.doc-preview-error {
  background: #fef0f0;
  border: 1px solid #f56c6c;
}

.doc-preview-error-icon {
  color: #f56c6c;
  margin-bottom: 16px;
}

.doc-preview-error-text {
  color: #606266;
  margin-bottom: 20px;
}

.doc-preview-loading-text {
  margin-top: 16px;
  color: #909399;
}

.doc-preview-body {
  background: #f0f2f5;
  border-radius: 8px;
  min-height: 400px;
}

.doc-preview-frame {
  width: 100%;
  height: 70vh;
  border: none;
  background: #fff;
}

.doc-preview-img {
  max-width: 100%;
  max-height: 70vh;
  object-fit: contain;
}

.doc-preview-fallback-title {
  font-weight: 600;
  color: #303133;
  margin-bottom: 8px;
}

.doc-preview-fallback-hint {
  color: #909399;
  font-size: 14px;
  margin-bottom: 20px;
}

.mr-1 {
  margin-right: 4px;
}
</style>
