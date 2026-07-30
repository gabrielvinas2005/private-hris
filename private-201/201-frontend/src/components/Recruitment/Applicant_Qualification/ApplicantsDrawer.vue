<template>
  <el-drawer :model-value="visible" @close="$emit('update:visible', false)" size="90%" title="Applicant Qualification">
    <template #default>
      <!-- Position Details -->
      <el-card shadow="never" class="mb-3">
        <template #header>
          <strong>Position Details</strong>
        </template>
        <el-form label-width="140px" class="grid grid-cols-2 gap-3">
          <el-form-item label="Code"><el-input :model-value="position.code || position.id" disabled /></el-form-item>
          <el-form-item label="Unit"><el-input :model-value="position.unit || 'Plantilla Unit'" disabled /></el-form-item>
          <el-form-item label="Position"><el-input :model-value="position.position || position.name" disabled /></el-form-item>
          <el-form-item label="Publication From"><el-input :model-value="position.publication_from" disabled /></el-form-item>
          <el-form-item label="Salary Grade"><el-input :model-value="position.grade || position.salary_grade" disabled /></el-form-item>
          <el-form-item label="Publication To"><el-input :model-value="position.publication_to" disabled /></el-form-item>
          <el-form-item label="Salary Step" v-if="position.step || position.salary_step"><el-input :model-value="position.step || position.salary_step" disabled /></el-form-item>
          <el-form-item label="Department" class="col-span-2"><el-input :model-value="position.department" disabled /></el-form-item>
        </el-form>

        <el-tabs>
          <el-tab-pane label="Educational Attainment">
            <template v-if="(position.educations && position.educations.length) || position.education || position.academic_level || position.program">
              <el-table :data="position.educations || []" size="small" v-if="position.educations && position.educations.length">
                <el-table-column prop="academic_level" label="Academic Level" />
                <el-table-column prop="program" label="Program" />
              </el-table>
              <div class="grid grid-cols-2 gap-3" v-else>
                <el-form-item label="Academic Level" v-if="position.education || position.academic_level"><el-input :model-value="position.education || position.academic_level" disabled /></el-form-item>
                <el-form-item label="Program" v-if="position.program"><el-input :model-value="position.program" disabled /></el-form-item>
              </div>
            </template>
            <div v-else class="text-gray-500">No educational data available</div>
          </el-tab-pane>
          <el-tab-pane label="Work Experience">
            <template v-if="(position.employments && position.employments.length) || position.experience">
              <el-table :data="position.employments || []" size="small" v-if="position.employments && position.employments.length">
                <el-table-column prop="position" label="Position" />
                <el-table-column prop="years" label="Years" width="100" />
              </el-table>
              <el-input v-else type="textarea" :rows="2" :model-value="position.experience" disabled />
            </template>
            <span v-else class="text-gray-500">No work experience data</span>
          </el-tab-pane>
          <el-tab-pane label="Eligibility">
            <template v-if="(position.examinations && position.examinations.length) || position.eligibility">
              <el-table :data="position.examinations || []" size="small" v-if="position.examinations && position.examinations.length">
                <el-table-column prop="name" label="Eligibility" />
              </el-table>
              <el-input v-else type="textarea" :rows="2" :model-value="position.eligibility" disabled />
            </template>
            <span v-else class="text-gray-500">No eligibility data</span>
          </el-tab-pane>
          <el-tab-pane label="Trainings">
            <template v-if="(position.trainings_arr && position.trainings_arr.length) || position.training">
              <el-table :data="position.trainings_arr || []" size="small" v-if="position.trainings_arr && position.trainings_arr.length">
                <el-table-column prop="name" label="Training" />
              </el-table>
              <el-input v-else type="textarea" :rows="2" :model-value="position.training" disabled />
            </template>
            <span v-else class="text-gray-500">No trainings data</span>
          </el-tab-pane>
          <el-tab-pane label="Competencies">
            <template v-if="(position.competencies_arr && position.competencies_arr.length) || position.competencies || position.qualification_text">
              <el-table :data="position.competencies_arr || []" size="small" v-if="position.competencies_arr && position.competencies_arr.length">
                <el-table-column prop="name" label="Competency" />
                <el-table-column prop="subcompetency_name" label="Sub-competency" />
                <el-table-column prop="level" label="Level" width="100" />
              </el-table>
              <el-input v-else type="textarea" :rows="2" :model-value="position.competencies || position.qualification_text" disabled />
            </template>
            <span v-else class="text-gray-500">No competencies data</span>
          </el-tab-pane>
        </el-tabs>
      </el-card>

      <!-- Applicant List -->
      <el-card shadow="never">
        <template #header>
          <div class="flex justify-between items-center">
            <strong>Applicant List</strong>
            <div>
              <el-button size="small" @click="$emit('refresh')">Refresh</el-button>
            </div>
          </div>
        </template>

        <el-table :data="tableData" v-loading="loading" size="small" stripe>
          <el-table-column label="Photo" width="100" align="center">
            <template #default="{ row }">
              <el-avatar 
                :size="48" 
                :src="photoSrc(row.photo)" 
                shape="square"
              >
                <el-icon><User /></el-icon>
              </el-avatar>
            </template>
          </el-table-column>
          <el-table-column prop="first_name" label="First Name" width="140" />
          <el-table-column prop="middle_name" label="Middle Name" width="140" />
          <el-table-column prop="last_name" label="Last Name" width="140" />
          <el-table-column prop="address" label="Address" />
          <el-table-column prop="gender" label="Gender" width="120" />
          <el-table-column prop="birth_date" label="Birth Date" width="120" />
          <el-table-column prop="age" label="Age" width="80" />
          <el-table-column prop="mobile_no" label="Mobile No." width="140" />
          <el-table-column prop="email" label="Email" width="200" />
          <el-table-column label="EETE Rating" width="120">
            <template #default="{ row }">
              <el-tag v-if="row.education_rating !== null && row.experience_rating !== null && row.training_rating !== null && row.eligibility_rating !== null" 
                      :type="getEeteStatus(row).type" size="small">
                {{ getEeteStatus(row).text }}
              </el-tag>
              <span v-else class="text-gray-400">Not Rated</span>
            </template>
          </el-table-column>
          <el-table-column label="Status" width="120">
            <template #default="{ row }">
              <el-tag v-if="row.status_id !== null && row.status_id !== undefined || row.reviewed_status_id !== null && row.reviewed_status_id !== undefined" 
                      :type="getStatusType(Number((row.status_id !== null && row.status_id !== undefined) ? row.status_id : row.reviewed_status_id))" 
                      size="small">
                {{ getStatusText(Number((row.status_id !== null && row.status_id !== undefined) ? row.status_id : row.reviewed_status_id)) }}
              </el-tag>
              <span v-else class="text-gray-400">Pending</span>
            </template>
          </el-table-column>
          <el-table-column label="Attachment" width="140">
            <template #default="{ row }">
              <el-button 
                v-if="row.attachment_id && row.attachment_name" 
                type="primary" 
                link
                size="small"
                @click="openAttachmentModal(row)"
              >
                {{ row.attachment_name }}
              </el-button>
              <span v-else class="text-gray-400">N/A</span>
            </template>
          </el-table-column>
        <el-table-column prop="application_date" label="Date Applied" width="120" />
        <el-table-column label="Actions" width="200">
          <template #default="{ row }">
            <el-button size="small" type="primary" @click="$emit('view-detail', row)">Details</el-button>
          </template>
        </el-table-column>
        </el-table>
      </el-card>
    </template>
  </el-drawer>

  <!-- Attachment Preview Modal -->
  <el-dialog
    v-model="showAttachmentModal"
    :title="`Attachment: ${currentAttachment?.attachment_name || 'Preview'}`"
    width="90%"
    :close-on-click-modal="false"
    destroy-on-close
  >
    <div v-if="attachmentError" style="text-align: center; padding: 40px;">
      <el-icon :size="64" style="color: #f56c6c; margin-bottom: 20px;">
        <Warning />
      </el-icon>
      <p style="color: #606266; margin-bottom: 20px;">
        Failed to load attachment preview.
      </p>
      <el-button type="primary" @click="downloadAttachment">
        Download File Instead
      </el-button>
    </div>
    <div v-else-if="attachmentLoading" style="text-align: center; padding: 40px;">
      <el-icon class="is-loading" :size="48" style="color: #409eff;">
        <Loading />
      </el-icon>
      <p style="margin-top: 20px; color: #909399;">Loading preview...</p>
    </div>
    <div v-else-if="attachmentPreviewUrl" style="text-align: center; min-height: 500px; background: #f5f5f5;">
      <!-- PDF Preview -->
      <iframe
        v-if="attachmentFileType === 'pdf'"
        :src="attachmentPreviewUrl"
        style="width: 100%; height: 70vh; border: 1px solid #ddd; background: white;"
        frameborder="0"
        @load="console.log('PDF iframe loaded')"
        @error="() => { console.error('PDF iframe error'); attachmentError = true }"
      />
      <!-- Image Preview -->
      <img
        v-else-if="attachmentFileType === 'image'"
        :src="attachmentPreviewUrl"
        style="max-width: 100%; max-height: 70vh; object-fit: contain; background: white; padding: 10px;"
        alt="Attachment Preview"
        @load="console.log('Image loaded')"
        @error="(e) => { console.error('Image load error:', e); attachmentError = true }"
      />
      <!-- Other file types - show download link -->
      <div v-else style="padding: 40px;">
        <el-icon :size="64" style="color: #909399; margin-bottom: 20px;">
          <Document />
        </el-icon>
        <p style="color: #606266; margin-bottom: 20px;">
          This file type cannot be previewed in the browser.
        </p>
        <el-button type="primary" @click="downloadAttachment">
          Download File
        </el-button>
      </div>
    </div>
    <div v-else style="text-align: center; padding: 40px;">
      <p style="color: #909399;">No preview available</p>
    </div>
    <template #footer>
      <el-button @click="showAttachmentModal = false">Close</el-button>
      <el-button 
        v-if="currentAttachment?.id" 
        type="primary" 
        :loading="downloadLoading"
        @click="downloadAttachment"
      >
        Download
      </el-button>
    </template>
  </el-dialog>
</template>

<script setup>
import { defineProps, defineEmits, computed, ref, watch } from 'vue'
import { Document, Loading, Warning, User } from '@element-plus/icons-vue'
import { ElMessage } from 'element-plus'
import api from '@/services/api'

defineEmits(['update:visible', 'refresh', 'view-detail', 'process', 'zip'])

const props = defineProps({
  visible: { type: Boolean, default: false },
  loading: { type: Boolean, default: false },
  position: { type: Object, default: () => ({}) },
  applicants: { type: [Array, Object], default: () => [] }
})

const tableData = computed(() => {
  const val = props.applicants
  if (Array.isArray(val)) return val
  if (val && Array.isArray(val.data)) return val.data
  return []
})

// Photo src: backend may return base64 (with or without leading slash), data URL, or file path
const photoSrc = (photo) => {
  if (!photo) return ''
  if (typeof photo !== 'string') return ''
  if (photo.startsWith('data:')) return photo
  if (photo.startsWith('http')) return photo
  // Backend sometimes returns base64 with leading slash (e.g. "/9j/4AAQ..."); using that as URL causes 431
  if (photo.startsWith('/') && /^\/[A-Za-z0-9+/=]+$/.test(photo) && photo.length > 20) return `data:image/jpeg;base64,${photo}`
  if (photo.startsWith('/')) return photo // real path e.g. /storage/...
  return `data:image/jpeg;base64,${photo}`
}

// Helper: passed = score >= 50 or legacy 1
const isPassed = (rating) => rating != null && (Number(rating) >= 50 || Number(rating) === 1)

// Helper function to determine EETE status (numeric 0–100 or legacy 0/1)
// Rule: if ANY category is marked "Failed", overall EETE rating should be "Failed".
const getEeteStatus = (row) => {
  const toNumber = (value) => {
    if (value === null || value === undefined || value === '') return null
    const num = Number(value)
    return Number.isNaN(num) ? null : num
  }

  const ratings = [
    toNumber(row.education_rating),
    toNumber(row.experience_rating),
    toNumber(row.training_rating),
    toNumber(row.eligibility_rating)
  ]
  const passedCount = ratings.filter(r => isPassed(r)).length
  const totalCount = ratings.length

  const anyFailed = ratings.some((r) => r !== null && !isPassed(r))

  if (anyFailed) {
    return { type: 'danger', text: 'Failed' }
  }

  if (passedCount === totalCount && totalCount > 0) {
    return { type: 'success', text: 'Passed' }
  }
  if (passedCount > 0) {
    return { type: 'warning', text: 'Partial' }
  }
  return { type: 'danger', text: 'Failed' }
}

// Helper function to get status type for styling
const getStatusType = (statusId) => {
  switch (statusId) {
    case 2: return 'success'  // Qualified
    case 3: return 'danger'   // Not Qualified
    case 4: return 'info'     // For Reference
    default: return 'warning' // Under Review/Pending
  }
}

// Helper function to get status text
const getStatusText = (statusId) => {
  switch (statusId) {
    case 2: return 'Qualified'
    case 3: return 'Not Qualified'
    case 4: return 'For Reference'
    case 1: return 'Under Review'
    default: return 'Pending'
  }
}

// Attachment modal state
const showAttachmentModal = ref(false)
const currentAttachment = ref(null)
const attachmentPreviewUrl = ref('')
const attachmentFileType = ref('')
const attachmentLoading = ref(false)
const attachmentError = ref(false)

// Get file type from extension
const getFileType = (fileName) => {
  if (!fileName) return 'other'
  const ext = fileName.split('.').pop()?.toLowerCase()
  if (ext === 'pdf') return 'pdf'
  if (['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'].includes(ext)) return 'image'
  return 'other'
}

// Open attachment modal
const openAttachmentModal = async (row) => {
  if (!row.attachment_name) return

  const rawId = row.attachment_id
  const attachmentId = rawId != null ? Number(rawId) : NaN
  if (Number.isNaN(attachmentId) || attachmentId <= 0) {
    ElMessage.error('Invalid attachment ID. Cannot load preview.')
    return
  }

  currentAttachment.value = {
    id: attachmentId,
    name: row.attachment_name,
    path: row.attachment_path
  }
  
  attachmentFileType.value = getFileType(row.attachment_name)
  attachmentPreviewUrl.value = ''
  attachmentLoading.value = true
  attachmentError.value = false
  showAttachmentModal.value = true
  
  try {
    // Fetch the file as a blob for preview (required for authenticated endpoints)
    if (attachmentFileType.value === 'pdf' || attachmentFileType.value === 'image') {
      const response = await api.get(`/applicant-resume/${attachmentId}?inline=1`, {
        responseType: 'blob',
        headers: { Accept: '*/*' }
      })
      
      console.log('Response status:', response.status)
      console.log('Response data:', response.data)
      console.log('Is Blob:', response.data instanceof Blob)
      
      if (response.data instanceof Blob) {
        console.log('Blob size:', response.data.size, 'bytes')
        console.log('Blob type:', response.data.type)
        
        if (response.data.size > 0) {
          // Create object URL for preview
          const blobUrl = URL.createObjectURL(response.data)
          console.log('Created blob URL:', blobUrl)
          attachmentPreviewUrl.value = blobUrl
        } else {
          throw new Error('File is empty')
        }
      } else {
        // If response is not a blob, try to convert it
        console.warn('Response is not a blob, attempting conversion')
        const blob = new Blob([response.data], { 
          type: response.headers['content-type'] || 'application/octet-stream' 
        })
        attachmentPreviewUrl.value = URL.createObjectURL(blob)
      }
    } else {
      // For other file types, we can't preview
      attachmentPreviewUrl.value = null
    }
  } catch (error) {
    attachmentError.value = true
    let errorMessage = error.message || 'Failed to load attachment preview'
    const data = error.response?.data
    if (data) {
      if (typeof data === 'string') {
        try {
          const parsed = JSON.parse(data)
          if (parsed?.message) errorMessage = parsed.message
        } catch (_) { /* ignore */ }
      } else if (data?.message) {
        errorMessage = data.message
      } else if (data instanceof Blob) {
        try {
          const text = await data.text()
          const parsed = JSON.parse(text)
          if (parsed?.message) errorMessage = parsed.message
        } catch (_) { /* ignore */ }
      }
    }
    ElMessage.error(errorMessage)
  } finally {
    attachmentLoading.value = false
  }
}

// Download attachment (uses API with auth so it works from any origin)
const downloadLoading = ref(false)
const downloadAttachment = async () => {
  if (!currentAttachment.value?.id || !currentAttachment.value?.name) return
  try {
    downloadLoading.value = true
    const res = await api.get(`/applicant-resume/${currentAttachment.value.id}`, {
      responseType: 'blob',
      headers: { Accept: '*/*' }
    })
    const blob = res.data
    if (!(blob instanceof Blob) || blob.size === 0) {
      ElMessage.error('Download failed: empty or invalid file')
      return
    }
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = currentAttachment.value.name || 'attachment'
    a.click()
    URL.revokeObjectURL(url)
    ElMessage.success('Download started')
  } catch (err) {
    const data = err.response?.data
    let msg = err.message || 'Download failed'
    if (data instanceof Blob) {
      try {
        const text = await data.text()
        const parsed = JSON.parse(text)
        if (parsed?.message) msg = parsed.message
      } catch (_) { /* ignore */ }
    } else if (data?.message) {
      msg = data.message
    }
    ElMessage.error(msg)
  } finally {
    downloadLoading.value = false
  }
}

// Clean up object URL when modal closes
watch(showAttachmentModal, (isOpen) => {
  if (!isOpen && attachmentPreviewUrl.value && attachmentPreviewUrl.value.startsWith('blob:')) {
    URL.revokeObjectURL(attachmentPreviewUrl.value)
    attachmentPreviewUrl.value = ''
    attachmentError.value = false
  }
})
</script>

<style scoped>
</style>
