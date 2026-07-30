<template>
  <el-dialog
    :model-value="visible"
    @update:model-value="handleClose"
    :title="`Plantilla Report - ${reportType}`"
    width="90%"
    :before-close="handleClose"
    class="plantilla-preview-modal"
  >
    <template #header>
      <div class="flex items-center justify-between">
        <h3 class="text-lg font-medium text-gray-900">
          Plantilla Report - {{ reportType }}
        </h3>
        <div class="flex space-x-2">
          <el-button
            type="primary"
            size="small"
            @click="handlePrint"
            :loading="loading"
          >
            <el-icon class="mr-1"><Printer /></el-icon>
            Print
          </el-button>
        </div>
      </div>
    </template>

    <div class="preview-container">
      <div v-if="loading" class="loading-container">
        <div class="custom-spinner"></div>
        <p class="loading-text">Generating Plantilla Report...</p>
      </div>
      
      <div v-else-if="error" class="error-container">
        <el-icon class="error-icon"><Warning /></el-icon>
        <p class="error-text">Failed to load Plantilla Report preview</p>
        <el-button type="primary" @click="$emit('retry')">Retry</el-button>
      </div>
      
      <iframe
        v-else-if="pdfUrl"
        :src="pdfUrl"
        class="pdf-iframe"
        frameborder="0"
      ></iframe>
      
      <div v-else class="no-content">
        <el-icon class="no-content-icon"><DocumentRemove /></el-icon>
        <p>No Plantilla Report to preview</p>
      </div>
    </div>

    <template #footer>
      <div class="dialog-footer">
        <el-button @click="handleClose">Close</el-button>
        <el-button
          type="success"
          @click="handleDownload"
          :loading="loading"
        >
          <el-icon class="mr-1"><Download /></el-icon>
          Download PDF
        </el-button>
      </div>
    </template>
  </el-dialog>
</template>

<script setup>
import { ref, watch } from 'vue'
import { Download, Printer, Warning, DocumentRemove } from '@element-plus/icons-vue'

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
  reportType: {
    type: String,
    default: 'Report'
  },
  loading: {
    type: Boolean,
    default: false
  }
})

// Emits
const emit = defineEmits(['close', 'download', 'print', 'retry'])

// Local state
const error = ref(false)

// Methods
const handleClose = () => {
  emit('close')
}

const handleDownload = () => {
  emit('download')
}

const handlePrint = () => {
  if (props.pdfUrl) {
    const printWindow = window.open(props.pdfUrl, '_blank')
    if (printWindow) {
      printWindow.onload = () => {
        printWindow.print()
      }
    }
  }
  emit('print')
}

// Watch for PDF URL changes
watch(() => props.pdfUrl, (newUrl) => {
  if (newUrl) {
    error.value = false
  }
})
</script>

<style scoped>
.plantilla-preview-modal {
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
