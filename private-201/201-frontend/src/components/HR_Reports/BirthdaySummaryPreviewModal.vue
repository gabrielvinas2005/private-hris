<template>
  <el-dialog
    v-model="visible"
    title="Birthday Summary Report Preview"
    width="90%"
    :before-close="handleClose"
    class="birthday-preview-modal"
  >
    <template #header>
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-3">
          <el-icon class="text-blue-600 text-xl"><Document /></el-icon>
          <div>
            <h3 class="text-lg font-semibold text-gray-900">Birthday Summary Report Preview</h3>
            <p class="text-sm text-gray-500">Preview the birthday summary report before downloading</p>
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
        <el-icon class="is-loading text-4xl text-blue-600"><Loading /></el-icon>
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
          height="700px"
          style="border: 1px solid #e5e7eb; border-radius: 8px;"
          title="Birthday Summary Report Preview"
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
import { ref, computed, watch } from 'vue'
import { 
  Document, Download, Printer, Warning, DocumentDelete, Loading
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
    default: 'birthday_summary.pdf'
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

const emit = defineEmits(['update:modelValue', 'close'])

const visible = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

const downloadLoading = ref(false)

// Watch for pdfBlob changes to create URL
const currentPdfUrl = computed(() => {
  if (props.pdfUrl) {
    return props.pdfUrl
  }
  if (props.pdfBlob) {
    return window.URL.createObjectURL(props.pdfBlob)
  }
  return ''
})

// Clean up blob URL when component is destroyed
watch(() => props.modelValue, (newVal) => {
  if (!newVal && currentPdfUrl.value && currentPdfUrl.value.startsWith('blob:')) {
    window.URL.revokeObjectURL(currentPdfUrl.value)
  }
})

const handleClose = () => {
  visible.value = false
  emit('close')
}

const handleDownload = async () => {
  if (props.onDownload) {
    downloadLoading.value = true
    try {
      await props.onDownload(props.pdfBlob, props.filename)
    } finally {
      downloadLoading.value = false
    }
  } else if (props.pdfBlob) {
    downloadLoading.value = true
    try {
      const url = window.URL.createObjectURL(props.pdfBlob)
      const link = document.createElement('a')
      link.href = url
      link.download = props.filename
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
      window.URL.revokeObjectURL(url)
    } finally {
      downloadLoading.value = false
    }
  }
}

const handlePrint = () => {
  if (currentPdfUrl.value) {
    const printWindow = window.open(currentPdfUrl.value, '_blank')
    if (printWindow) {
      printWindow.onload = () => {
        printWindow.print()
      }
    }
  }
}
</script>

<style scoped>
.pdf-container {
  min-height: 400px;
}

.pdf-viewer {
  width: 100%;
}

.birthday-preview-modal :deep(.el-dialog__body) {
  padding: 20px;
}
</style>
