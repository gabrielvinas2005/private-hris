<template>
  <el-dialog
    v-model="visible"
    :title="`Personal Data Sheet - ${employeeName}`"
    width="90%"
    :before-close="handleClose"
    class="pds-preview-modal"
  >
    <template #header>
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-3">
          <el-icon class="text-blue-600 text-xl"><Document /></el-icon>
          <div>
            <h3 class="text-lg font-semibold text-gray-900">Personal Data Sheet Preview</h3>
            <p class="text-sm text-gray-500">{{ employeeName }}</p>
          </div>
        </div>
        <div class="flex items-center space-x-2">
          <el-button
            type="primary"
            size="small"
            :loading="downloadLoading"
            @click="handleDownload"
          >
            <el-icon><Download /></el-icon>
            Download PDF
          </el-button>
          <el-button
            type="info"
            size="small"
            @click="handlePrint"
          >
            <el-icon><Printer /></el-icon>
            Print
          </el-button>
        </div>
      </div>
    </template>

    <!-- PDF Viewer Container -->
    <div class="pdf-container">
      <div v-if="loading" class="flex items-center justify-center h-96">
        <el-loading-directive />
        <div class="ml-3">
          <p class="text-gray-600">Generating PDF preview...</p>
          <p class="text-sm text-gray-500">Please wait while we prepare your document</p>
        </div>
      </div>
      
      <div v-else-if="pdfUrl" class="pdf-viewer">
        <!-- PDF Embed -->
        <iframe
          :src="pdfUrl"
          width="100%"
          height="600px"
          style="border: 1px solid #e5e7eb; border-radius: 8px;"
          title="Personal Data Sheet Preview"
        ></iframe>
        
        <!-- Fallback message for browsers that don't support PDF viewing -->
        <div class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
          <div class="flex items-start space-x-3">
            <el-icon class="text-yellow-600 text-lg mt-0.5"><Warning /></el-icon>
            <div>
              <p class="text-yellow-800 font-medium">Can't see the preview?</p>
              <p class="text-yellow-700 text-sm mt-1">
                Your browser might not support inline PDF viewing. You can still download the PDF using the button above.
              </p>
            </div>
          </div>
        </div>
      </div>

      <div v-else class="flex items-center justify-center h-96">
        <div class="text-center">
          <el-icon class="text-gray-400 text-4xl mb-4"><DocumentDelete /></el-icon>
          <p class="text-gray-600">No PDF available for preview</p>
          <p class="text-sm text-gray-500">Please try generating the document again</p>
        </div>
      </div>
    </div>

    <!-- Modal Footer -->
    <template #footer>
      <div class="flex items-center justify-between">
        <div class="text-sm text-gray-500">
          <span>Document generated: {{ new Date().toLocaleString() }}</span>
        </div>
        <div class="space-x-2">
          <el-button @click="handleClose">Close</el-button>
          <el-button
            type="primary"
            :loading="downloadLoading"
            @click="handleDownload"
          >
            <el-icon><Download /></el-icon>
            Download PDF
          </el-button>
        </div>
      </div>
    </template>
  </el-dialog>
</template>

<script setup>
import { ref, watch } from 'vue'
import { ElMessage } from 'element-plus'
import { 
  Document, Download, Printer, Warning, DocumentDelete
} from '@element-plus/icons-vue'

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  },
  pdfUrl: {
    type: String,
    default: ''
  },
  pdfBlob: {
    type: Blob,
    default: null
  },
  filename: {
    type: String,
    default: 'personal_data_sheet.pdf'
  },
  employeeName: {
    type: String,
    default: 'Employee'
  },
  loading: {
    type: Boolean,
    default: false
  },
  onDownload: {
    type: Function,
    default: () => {}
  }
})

const emit = defineEmits(['update:modelValue', 'close', 'download'])

const visible = ref(false)
const downloadLoading = ref(false)

// Watch for model value changes
watch(() => props.modelValue, (newVal) => {
  visible.value = newVal
}, { immediate: true })

watch(visible, (newVal) => {
  emit('update:modelValue', newVal)
})

// Methods
const handleClose = () => {
  visible.value = false
  emit('close')
  
  // Clean up blob URL to prevent memory leaks
  if (props.pdfUrl && props.pdfUrl.startsWith('blob:')) {
    window.URL.revokeObjectURL(props.pdfUrl)
  }
}

const handleDownload = async () => {
  if (!props.pdfBlob) {
    ElMessage.warning('No PDF available for download')
    return
  }

  try {
    downloadLoading.value = true
    await props.onDownload(props.pdfBlob, props.filename)
    ElMessage.success('PDF downloaded successfully')
  } catch (error) {
    ElMessage.error('Failed to download PDF')
    console.error('Download error:', error)
  } finally {
    downloadLoading.value = false
  }
}

const handlePrint = () => {
  if (props.pdfUrl) {
    // Open PDF in new window for printing
    const printWindow = window.open(props.pdfUrl, '_blank')
    if (printWindow) {
      printWindow.onload = () => {
        printWindow.print()
      }
    } else {
      ElMessage.warning('Please allow popups to enable printing')
    }
  } else {
    ElMessage.warning('No PDF available for printing')
  }
}
</script>

<style scoped>
.pds-preview-modal {
  --el-dialog-margin-top: 5vh;
}

.pds-preview-modal :deep(.el-dialog) {
  margin-top: var(--el-dialog-margin-top);
  margin-bottom: 5vh;
  height: calc(90vh - var(--el-dialog-margin-top));
  display: flex;
  flex-direction: column;
}

.pds-preview-modal :deep(.el-dialog__body) {
  flex: 1;
  padding: 0;
  overflow: hidden;
}

.pdf-container {
  height: 100%;
  padding: 20px;
  overflow-y: auto;
}

.pdf-viewer {
  height: 100%;
}

/* Loading styles */
.el-loading-directive {
  display: inline-block;
  width: 24px;
  height: 24px;
  border: 2px solid #f3f4f6;
  border-top: 2px solid #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .pds-preview-modal {
    --el-dialog-margin-top: 2vh;
  }
  
  .pds-preview-modal :deep(.el-dialog) {
    width: 95% !important;
    height: calc(96vh - var(--el-dialog-margin-top));
  }
  
  .pdf-viewer iframe {
    height: 400px;
  }
}

/* Utility classes */
.flex {
  display: flex;
}

.items-center {
  align-items: center;
}

.justify-between {
  justify-content: space-between;
}

.space-x-2 > * + * {
  margin-left: 0.5rem;
}

.space-x-3 > * + * {
  margin-left: 0.75rem;
}

.text-lg {
  font-size: 1.125rem;
  line-height: 1.75rem;
}

.font-semibold {
  font-weight: 600;
}

.text-gray-900 {
  color: rgb(17 24 39);
}

.text-sm {
  font-size: 0.875rem;
  line-height: 1.25rem;
}

.text-gray-500 {
  color: rgb(107 114 128);
}

.text-blue-600 {
  color: rgb(37 99 235);
}

.text-xl {
  font-size: 1.25rem;
  line-height: 1.75rem;
}

.h-96 {
  height: 24rem;
}

.mt-4 {
  margin-top: 1rem;
}

.p-4 {
  padding: 1rem;
}

.bg-yellow-50 {
  background-color: rgb(254 252 232);
}

.border {
  border-width: 1px;
}

.border-yellow-200 {
  border-color: rgb(254 240 138);
}

.rounded-lg {
  border-radius: 0.5rem;
}

.text-yellow-800 {
  color: rgb(133 77 14);
}

.font-medium {
  font-weight: 500;
}

.text-yellow-700 {
  color: rgb(161 98 7);
}

.mt-1 {
  margin-top: 0.25rem;
}

.text-yellow-600 {
  color: rgb(217 119 6);
}

.text-center {
  text-align: center;
}

.text-gray-400 {
  color: rgb(156 163 175);
}

.text-4xl {
  font-size: 2.25rem;
  line-height: 2.5rem;
}

.mb-4 {
  margin-bottom: 1rem;
}

.text-gray-600 {
  color: rgb(75 85 99);
}
</style>
