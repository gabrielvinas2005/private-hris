<template>
  <el-dialog 
    v-model="visible" 
    :title="`HRDD Rating & Documents - ${applicantData?.name || ''}`"
    width="80%"
    top="5vh"
    destroy-on-close
  >
    <div v-if="loading" class="flex justify-center items-center h-64">
      <el-icon class="is-loading">
        <Loading />
      </el-icon>
    </div>
    
    <div v-else-if="hrddData" class="hrdd-container">
      <!-- HRDD Rating Section -->
      <div class="section">
        <h3 class="section-title">HRMPSB Deliberation Rating</h3>
        <div class="rating-display">
          <div class="rating-value" :class="getRatingClass(hrddData.rating)">
            {{ hrddData.rating || 0 }}%
          </div>
          <div class="rating-label">Overall Performance Rating</div>
        </div>
      </div>

      <!-- Background Investigation Documents -->
      <div class="section">
        <h3 class="section-title">HRMPSB Deliberation Documents</h3>
        <div v-if="hrddData.bi_documents?.length > 0" class="documents-grid">
          <div 
            v-for="doc in hrddData.bi_documents" 
            :key="doc.id"
            class="document-item"
          >
            <div class="document-info">
              <div class="document-name">{{ doc.bi_document }}</div>
              <div class="document-date">{{ formatDate(doc.created_at) }}</div>
            </div>
            <div class="document-actions">
              <el-button 
                size="small" 
                type="primary" 
                @click="previewDocument(doc.id, 1)"
                :loading="downloading === doc.id"
              >
                Preview
              </el-button>
            </div>
          </div>
        </div>
        <el-empty v-else description="No background investigation documents available" />
      </div>

    </div>

    <template #footer>
      <div class="dialog-footer">
        <el-button @click="visible = false">Close</el-button>
        <el-button type="primary" @click="refreshData" :loading="loading">Refresh</el-button>
      </div>
    </template>
  </el-dialog>

  <!-- Document Preview Modal -->
  <el-dialog
    v-model="previewVisible"
    :title="previewDocumentName || 'Document Preview'"
    width="90%"
    top="5vh"
    destroy-on-close
  >
    <div v-if="previewLoading" class="flex justify-center items-center h-96">
      <el-icon class="is-loading" size="48">
        <Loading />
      </el-icon>
    </div>
    <div v-else-if="previewUrl" class="preview-container">
      <!-- PDF Preview -->
      <iframe
        v-if="isPdfPreview"
        :src="previewUrl"
        class="preview-iframe"
        frameborder="0"
      />
      <!-- Image Preview -->
      <img
        v-else-if="isImagePreview"
        :src="previewUrl"
        class="preview-image"
        alt="Document preview"
      />
      <!-- Other file types - show message -->
      <div v-else class="preview-unsupported">
        <el-empty description="Preview not available for this file type. Please download to view." />
      </div>
    </div>
    <template #footer>
      <div class="dialog-footer">
        <el-button @click="previewVisible = false">Close</el-button>
        <el-button v-if="previewUrl && !isPdfPreview && !isImagePreview" type="primary" @click="downloadPreview">Download</el-button>
      </div>
    </template>
  </el-dialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { adminSelectApi } from '@/services/api'
import { ElMessage } from 'element-plus'
import { Loading } from '@element-plus/icons-vue'

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
const downloading = ref(null)
const hrddData = ref(null)
const previewVisible = ref(false)
const previewLoading = ref(false)
const previewUrl = ref(null)
const previewDocumentName = ref('')
const previewBlob = ref(null)
const previewFileType = ref('')

// Watch for dialog open and fetch HRDD data
watch(visible, (newVal) => {
  if (newVal && props.applicantData) {
    fetchHRDDData()
  }
})

const fetchHRDDData = async () => {
  if (!props.applicantData?.applicant_id) return
  
  loading.value = true
  try {
    const { data } = await adminSelectApi.hrrdRating(props.applicantData.applicant_id)
    hrddData.value = data.data
  } catch (error) {
    console.error('Error fetching HRDD data:', error)
    ElMessage.error('Failed to load HRDD data')
  } finally {
    loading.value = false
  }
}

const refreshData = () => {
  fetchHRDDData()
}

const previewDocument = async (docId, typeId) => {
  downloading.value = docId
  previewLoading.value = true
  previewVisible.value = false
  
  try {
    // Find document name
    const doc = hrddData.value?.bi_documents?.find(d => d.id === docId)
    previewDocumentName.value = doc?.bi_document || 'Document Preview'
    
    // Fetch the document as blob - docId is the document ID, not applicant ID
    // The API route expects: /administrator-selection/{documentId}/download/{typeId}
    const response = await adminSelectApi.download(docId, typeId)
    
    // Handle blob response
    let blob
    if (response.data instanceof Blob) {
      blob = response.data
    } else if (response instanceof Blob) {
      blob = response
    } else {
      blob = new Blob([response.data || response])
    }
    
    // Check if blob is actually an error JSON response
    if (blob.type === 'application/json') {
      const text = await blob.text()
      try {
        const errorData = JSON.parse(text)
        ElMessage.error(errorData.message || 'Failed to preview document')
        return
      } catch (e) {
        // Not JSON, continue with preview
      }
    }
    
    // Store blob and file type
    previewBlob.value = blob
    previewFileType.value = blob.type || ''
    
    // Create object URL for preview
    previewUrl.value = window.URL.createObjectURL(blob)
    
    // Show preview modal
    previewVisible.value = true
    
  } catch (error) {
    console.error('Preview error:', error)
    ElMessage.error('Failed to preview document')
  } finally {
    downloading.value = null
    previewLoading.value = false
  }
}

const isPdfPreview = computed(() => {
  return previewFileType.value.includes('pdf') || previewFileType.value === 'application/pdf'
})

const isImagePreview = computed(() => {
  return previewFileType.value.includes('image')
})

const downloadPreview = () => {
  if (!previewBlob.value || !previewDocumentName.value) return
  
  const url = window.URL.createObjectURL(previewBlob.value)
  const link = document.createElement('a')
  link.href = url
  link.download = previewDocumentName.value
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  window.URL.revokeObjectURL(url)
  
  ElMessage.success('Document downloaded successfully')
}

// Clean up blob URL when modal closes
watch(previewVisible, (newVal) => {
  if (!newVal && previewUrl.value) {
    window.URL.revokeObjectURL(previewUrl.value)
    previewUrl.value = null
    previewBlob.value = null
    previewFileType.value = ''
    previewDocumentName.value = ''
  }
})

// Helper functions
const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString()
}

const getRatingClass = (rating) => {
  if (!rating) return 'rating-poor'
  if (rating >= 80) return 'rating-excellent'
  if (rating >= 60) return 'rating-good'
  return 'rating-poor'
}
</script>

<style scoped>
.hrdd-container {
  max-height: 70vh;
  overflow-y: auto;
}

.section {
  margin-bottom: 24px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 16px;
}

.section-title {
  font-size: 16px;
  font-weight: 600;
  margin-bottom: 16px;
  color: #374151;
  border-bottom: 2px solid #3b82f6;
  padding-bottom: 8px;
}

.rating-display {
  text-align: center;
  padding: 20px;
  background: #f9fafb;
  border-radius: 8px;
}

.rating-value {
  font-size: 48px;
  font-weight: 700;
  margin-bottom: 8px;
}

.rating-excellent {
  color: #16a34a;
}

.rating-good {
  color: #ca8a04;
}

.rating-poor {
  color: #dc2626;
}

.rating-label {
  font-size: 16px;
  color: #6b7280;
  font-weight: 500;
}

.documents-grid {
  display: grid;
  gap: 12px;
}

.document-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  background: #f9fafb;
}

.document-info {
  flex: 1;
}

.document-name {
  font-weight: 500;
  color: #111827;
  margin-bottom: 4px;
}

.document-date {
  font-size: 12px;
  color: #6b7280;
}

.document-actions {
  margin-left: 12px;
}

.selected-files {
  margin-top: 16px;
  padding: 16px;
  background: #f9fafb;
  border-radius: 6px;
}

.selected-files h4 {
  margin-bottom: 12px;
  color: #374151;
}

.file-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 0;
  border-bottom: 1px solid #e5e7eb;
}

.file-item:last-child {
  border-bottom: none;
}

.preview-container {
  width: 100%;
  height: 70vh;
  display: flex;
  justify-content: center;
  align-items: center;
  background: #f5f5f5;
}

.preview-iframe {
  width: 100%;
  height: 100%;
  border: none;
}

.preview-image {
  max-width: 100%;
  max-height: 100%;
  object-fit: contain;
}

.preview-unsupported {
  width: 100%;
  height: 100%;
  display: flex;
  justify-content: center;
  align-items: center;
}

.flex {
  display: flex;
}

.justify-center {
  justify-content: center;
}

.items-center {
  align-items: center;
}

.h-96 {
  height: 24rem;
}
</style>
