<template>
  <el-dialog
    :model-value="visible"
    @update:model-value="handleClose"
    :title="`${certificateType} - ${employeeName}`"
    width="90%"
    :before-close="handleClose"
    class="certificate-preview-modal"
  >
    <template #header>
      <div class="flex items-center justify-between">
        <h3 class="text-lg font-medium text-gray-900">
          {{ certificateType }} - {{ employeeName }}
        </h3>
        <div class="flex items-center space-x-2">
          <span class="text-sm text-gray-600 mr-2">Download as:</span>
          <el-button
            v-if="showPdfButton"
            type="danger"
            size="small"
            @click="handleDownloadPdf"
            :loading="loading"
          >
            <el-icon class="mr-1"><Printer /></el-icon>
            PDF
          </el-button>
          <el-button
            v-if="showWordButton"
            type="primary"
            size="small"
            @click="handleDownloadWord"
            :loading="loading"
          >
            <el-icon class="mr-1"><Document /></el-icon>
            Word
          </el-button>
          <el-button
            v-if="showExcelButton"
            type="success"
            size="small"
            @click="handleDownloadExcel"
            :loading="loading"
          >
            <el-icon class="mr-1"><Document /></el-icon>
            Excel
          </el-button>
          <el-button
            v-if="showCsvButton"
            type="info"
            size="small"
            @click="handleDownloadCsv"
            :loading="loading"
          >
            <el-icon class="mr-1"><Document /></el-icon>
            CSV
          </el-button>
        </div>
      </div>
    </template>

    <!-- Editable Purpose Text Field -->
    <div v-if="showPurposeEdit" class="mb-4 p-4 bg-gray-50 rounded-lg">
      <el-form label-width="120px">
        <el-form-item label="Purpose Text">
          <el-input 
            :model-value="purposeText"
            @update:model-value="handlePurposeTextChange"
            type="textarea"
            :rows="3"
            placeholder="Enter purpose text (e.g., as a confirmation of her employment with the Center and as a requirement for her personal travel abroad)"
            @blur="handleUpdatePurpose"
            :disabled="loading"
          />
        </el-form-item>
      </el-form>
      <div class="text-xs text-gray-500 mt-2 flex items-center gap-1">
        <el-icon><InfoFilled /></el-icon>
        <span>Changes will automatically update the preview {{ loading ? '(Updating...)' : '' }}</span>
      </div>
    </div>

    <div class="preview-container">
      <div v-if="loading" class="loading-container">
        <div class="custom-spinner"></div>
        <p class="loading-text">Generating {{ certificateType }}...</p>
      </div>
      
      <div v-else-if="error" class="error-container">
        <el-icon class="error-icon"><Warning /></el-icon>
        <p class="error-text">Failed to load {{ certificateType }} preview</p>
        <el-button type="primary" @click="$emit('retry')">Retry</el-button>
      </div>
      
      <iframe
        v-else-if="pdfUrl || wordUrl"
        :src="previewType === 'pdf' ? pdfUrl : wordUrl"
        :key="previewKey"
        class="pdf-iframe"
        frameborder="0"
      ></iframe>
      
      <div v-else class="no-content">
        <el-icon class="no-content-icon"><DocumentRemove /></el-icon>
        <p>No {{ certificateType }} to preview</p>
      </div>
    </div>

    <template #footer>
      <div class="dialog-footer">
        <el-button @click="handleClose">Close</el-button>
      </div>
    </template>
  </el-dialog>
</template>

<script setup>
import { ref, watch } from 'vue'
import { Download, Printer, Warning, DocumentRemove, Document, InfoFilled } from '@element-plus/icons-vue'

// Props
const props = defineProps({
  visible: {
    type: Boolean,
    default: false
  },
  pdfUrl: {
    type: String,
    default: ''
  },
  wordUrl: {
    type: String,
    default: ''
  },
  previewType: {
    type: String,
    default: 'pdf'
  },
  employeeName: {
    type: String,
    default: 'Unknown Employee'
  },
  certificateType: {
    type: String,
    default: 'Certificate'
  },
  loading: {
    type: Boolean,
    default: false
  },
  previewKey: {
    type: Number,
    default: 0
  },
  showPurposeEdit: {
    type: Boolean,
    default: false
  },
  purposeText: {
    type: String,
    default: ''
  },
  showWordButton: {
    type: Boolean,
    default: true
  },
  showExcelButton: {
    type: Boolean,
    default: true
  },
  showCsvButton: {
    type: Boolean,
    default: false
  },
  showPdfButton: {
    type: Boolean,
    default: true
  }
})

// Emits
const emit = defineEmits(['close', 'download', 'downloadWord', 'downloadExcel', 'downloadCsv', 'print', 'retry', 'update-purpose', 'purpose-text-change'])

// Methods
const handlePurposeTextChange = (value) => {
  emit('purpose-text-change', value)
}

const handleUpdatePurpose = () => {
  emit('update-purpose')
}

// Local state
const error = ref(false)

// Methods
const handleClose = () => {
  emit('close')
}

const handleDownloadPdf = () => {
  emit('download')
}

const handleDownloadWord = () => {
  emit('downloadWord')
}

const handleDownloadExcel = () => {
  emit('downloadExcel')
}

const handleDownloadCsv = () => {
  emit('downloadCsv')
}

const handlePrint = () => {
  const url = props.previewType === 'pdf' ? props.pdfUrl : props.wordUrl
  if (url) {
    const printWindow = window.open(url, '_blank')
    if (printWindow) {
      printWindow.onload = () => {
        printWindow.print()
      }
    }
  }
  emit('print')
}

// Watch for URL changes
watch(() => [props.pdfUrl, props.wordUrl], ([newPdfUrl, newWordUrl]) => {
  if (newPdfUrl || newWordUrl) {
    error.value = false
  }
})
</script>

<style scoped>
.certificate-preview-modal {
  --el-dialog-content-font-size: 14px;
}

.preview-container {
  height: 70vh;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  background-color: #f5f5f5;
  border-radius: 8px;
  overflow: hidden;
}

.pdf-iframe {
  width: 100%;
  height: 100%;
  border: none;
  background: white;
}

.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100%;
  color: #666;
}

.custom-spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #e4e7ed;
  border-top: 4px solid #409eff;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 16px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.loading-text {
  font-size: 14px;
  color: #666;
  margin: 0;
}

.error-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100%;
  color: #f56c6c;
}

.error-icon {
  font-size: 48px;
  margin-bottom: 16px;
}

.error-text {
  font-size: 16px;
  margin-bottom: 16px;
  color: #666;
}

.no-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100%;
  color: #999;
}

.no-content-icon {
  font-size: 48px;
  margin-bottom: 16px;
}

.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
}

:deep(.el-dialog__header) {
  padding: 16px 20px;
  border-bottom: 1px solid #ebeef5;
}

:deep(.el-dialog__body) {
  padding: 20px;
}

:deep(.el-dialog__footer) {
  padding: 16px 20px;
  border-top: 1px solid #ebeef5;
}
</style>
