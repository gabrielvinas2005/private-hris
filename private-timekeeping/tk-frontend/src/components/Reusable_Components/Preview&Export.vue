<template>
  <div class="preview-export-container">
    <el-button 
      type="primary" 
      size="default" 
      class="preview-btn"
      @click="openPreview"
      :loading="loading"
    >
      <el-icon><View /></el-icon>
      <span>Preview & Export</span>
    </el-button>

    <!-- PDF Preview Modal -->
    <el-dialog
      v-model="showPreview"
      :title="title"
      width="90%"
      :close-on-click-modal="false"
      destroy-on-close
      align-center
      append-to-body
      class="pdf-preview-modal"
      @close="handleClose"
    >
      <template #header>
        <div class="dialog-header">
          <div class="dialog-title">
            {{ title }}
            <el-tag size="small" effect="plain" type="info" class="ml-3">{{ exportMode === 'word' ? 'Word Preview' : 'PDF Preview' }}</el-tag>
          </div>
          <div class="dialog-subtitle">Review your document before downloading.</div>
        </div>
      </template>

      <div class="preview-container">
        <!-- Loading State -->
        <div v-if="modalLoading || isInitializing" class="loading-container">
          <div class="loading-content">
            <div class="loading-spinner"></div>
            <p class="loading-text">Generating PDF preview...</p>
            <p class="loading-subtext">This may take a few moments</p>
          </div>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="error-container">
          <div class="error-icon">⚠️</div>
          <h3 class="error-title">Preview Generation Failed</h3>
          <p class="error-message">{{ error }}</p>
          <el-button type="primary" @click="retryPreview">
            <el-icon><Refresh /></el-icon>
            Try Again
          </el-button>
        </div>

        <!-- PDF Preview -->
        <div v-else-if="pdfUrl || props.pdfUrl" class="pdf-container">

          <!-- PDF Content Area -->
          <div class="pdf-content-area">
            <!-- Thumbnail Sidebar -->
            <div class="thumbnail-sidebar">
              <div class="thumbnail-container">
                <div class="thumbnail-preview">
                  <iframe
                    :src="props.pdfUrl || pdfUrl"
                    width="150"
                    height="200"
                    style="border: 1px solid #ddd; border-radius: 4px; pointer-events: none;"
                  ></iframe>
                </div>
                <div class="thumbnail-label">Page 1</div>
              </div>
            </div>

            <!-- Main PDF Viewer -->
            <div class="main-viewer">
              <div class="pdf-viewer-container" :style="{ transform: `scale(${zoom}) rotate(${rotation}deg)` }">
                <iframe
                  :src="(props.pdfUrl || pdfUrl) + '#toolbar=1&navpanes=0&scrollbar=1'"
                  type="application/pdf"
                  class="pdf-iframe"
                  :style="{ width: '100%', height: '100%' }"
                  @load="handleIframeLoad"
                  @error="handleIframeError"
                ></iframe>
              </div>

              <!-- Fallback for browsers that don't support PDF viewing -->
              <div v-if="showFallback && !iframeLoaded" class="fallback-message">
                <div class="fallback-content">
                  <el-icon class="fallback-icon"><Warning /></el-icon>
                  <p class="fallback-title">Can't see the preview?</p>
                  <p class="fallback-text">
                    Your browser might not support inline PDF viewing. You can still download the PDF.
                  </p>
                  <el-button type="primary" @click="downloadPDFDirect">
                    <el-icon><Download /></el-icon>
                    Download PDF
                  </el-button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- No Content State -->
        <div v-else class="no-content-container">
          <div class="no-content-icon">📄</div>
          <h3 class="no-content-title">No PDF Available</h3>
          <p class="no-content-text">Generate a PDF preview to see the document here.</p>
        </div>
      </div>

      <template #footer>
        <div class="dialog-footer">
          <div class="footer-left">
            <el-button @click="handleClose">Close</el-button>
          </div>
          <div class="footer-right">
            <span class="download-label">Download as:</span>
            <el-button
              class="export-btn export-pdf"
              @click="downloadPDFDirect"
              :disabled="(!pdfUrl && !props.pdfUrl) || modalLoading"
            >
              <el-icon class="export-icon"><Download /></el-icon>
              <span>PDF</span>
            </el-button>
            <el-button
              class="export-btn export-docx"
              @click="handleWordExport"
              :disabled="modalLoading"
            >
              <el-icon class="export-icon"><DocumentCopy /></el-icon>
              <span>DOCX</span>
            </el-button>
            <el-button
              v-if="!hideExcel"
              class="export-btn export-excel"
              @click="handleExcelExport"
              :disabled="modalLoading"
            >
              <el-icon class="export-icon"><Document /></el-icon>
              <span>EXCEL</span>
            </el-button>
          </div>
        </div>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, computed, watch, onUnmounted } from 'vue'
import { ElMessage } from 'element-plus'
import {
  View,
  Document,
  Download,
  ZoomIn,
  ZoomOut,
  RefreshRight,
  MoreFilled,
  DocumentCopy,
  Refresh,
  Warning
} from '@element-plus/icons-vue'
import { usePDFPreview } from '../../Composables/usePDFPreview'
import { primaryCompany } from '../../Composables/useCompany.js'

const props = defineProps({
  // Content for preview
  htmlContent: { type: String, default: '' },
  template: { type: String, default: null },
  templateData: { type: Object, default: null },
  pdfUrl: { type: String, default: null }, // Direct PDF URL (for backend-generated PDFs)
  title: { type: String, default: 'Document Preview' },
  filename: { type: String, default: 'document' },
  orientation: { type: String, default: 'portrait' },
  exportMode: { type: String, default: 'pdf' },
  withHeaderFooter: { type: Boolean, default: true },
  hideExcel: { type: Boolean, default: false },
  
  // Export handlers
  onExcel: { type: Function, default: null },
  onPdf: { type: Function, default: null },
  onWord: { type: Function, default: null },
  
  // Loading state
  loading: { type: Boolean, default: false }
})



const emit = defineEmits(['excel', 'pdf', 'word', 'preview-generated', 'preview-error'])

// PDF Preview composable
const {
  loading: modalLoading,
  pdfUrl,
  error,
  blob,
  filename: generatedFilename,
  generatePDFPreview,
  downloadPDF: downloadPDFFromComposable,
  downloadDOCX: downloadDOCXFromComposable,
  cleanup,
  reset
} = usePDFPreview()

// Local state
const showPreview = ref(false)
const zoom = ref(1)
const rotation = ref(0)
const iframeLoaded = ref(false)
const showFallback = ref(false)
const isInitializing = ref(false)

// Watch for direct PDF URL prop
watch(
  () => props.pdfUrl,
  (newUrl) => {
    if (newUrl && showPreview.value) {
      // If direct PDF URL is provided, use it directly
      isInitializing.value = false
      iframeLoaded.value = false
      showFallback.value = false
    }
  },
  { immediate: true }
)

// Single watcher to handle visibility and auto-generate (prevents double triggers)
watch(
  () => showPreview.value,
  (isVisible) => {
    if (isVisible) {
      // If direct PDF URL is provided, skip generation
      if (props.pdfUrl) {
        isInitializing.value = false
        return
      }
      
      // Set initializing state immediately to prevent "No PDF Available" flash
      isInitializing.value = true
      // Add a small delay to ensure the modal is fully rendered
      setTimeout(() => {
        generatePreview()
      }, 100)
    } else {
      reset()
      isInitializing.value = false
    }
  },
  { immediate: true }
)

/**
 * Open the preview modal
 */
function openPreview() {
  showPreview.value = true
}

/**
 * Generate PDF preview
 */
const generatePreview = async () => {
  try {
    reset()
    iframeLoaded.value = false // Reset iframe loaded state
    showFallback.value = false // Reset fallback visibility
    isInitializing.value = true // Set initializing state
    
    const options = {
      paperSize: 'A4',
      orientation: props.orientation,
      filename: props.filename || generatedFilename.value,
      withHeaderFooter: props.withHeaderFooter,
      department: primaryCompany.value?.name?.trim() || 'Company Name',
      title: props.title || 'Report'
    }

    if (props.template && props.templateData) {
      await generatePDFPreview(props.template, { ...options, template: props.template, data: props.templateData })
    } else {
      await generatePDFPreview(props.htmlContent, options)
    }

    emit('preview-generated', {
      pdfUrl: pdfUrl.value,
      blob: blob.value,
      filename: generatedFilename.value
    })
    
    // Clear initializing state once PDF is generated
    isInitializing.value = false
    
    // Start auto-hide timer for fallback message
    autoHideFallback()
  } catch (e) {
    isInitializing.value = false // Clear initializing state on error
    emit('preview-error', e)
  }
}

/**
 * Handle iframe load event
 */
const handleIframeLoad = () => {
  // Give the PDF a moment to render, then hide the fallback
  setTimeout(() => {
    iframeLoaded.value = true
    showFallback.value = false // Hide fallback when iframe loads
  }, 1000)
}

/**
 * Handle iframe error event
 */
const handleIframeError = () => {
  iframeLoaded.value = false
  // Show fallback if iframe errors
  showFallback.value = true
}

/**
 * Auto-hide fallback after a reasonable time
 */
const autoHideFallback = () => {
  // Wait 2 seconds before showing fallback (give iframe time to load)
  setTimeout(() => {
    if (!iframeLoaded.value && pdfUrl.value) {
      // If still not loaded after 2 seconds, show fallback
      showFallback.value = true
    }
  }, 2000)
  
  // If the iframe hasn't loaded after 5 seconds, assume it's working or hide fallback
  setTimeout(() => {
    if (!iframeLoaded.value && (pdfUrl.value || props.pdfUrl)) {
      iframeLoaded.value = true
      showFallback.value = false
    }
  }, 5000)
}

/**
 * Retry preview generation
 */
const retryPreview = () => {
  isInitializing.value = true
  generatePreview()
}

/**
 * Download PDF (for generated PDFs)
 */
const downloadPDF = () => {
  downloadPDFFromComposable(props.filename || generatedFilename.value)
}

/**
 * Download PDF directly from URL (for backend-generated PDFs)
 */
const downloadPDFDirect = () => {
  if (props.pdfUrl) {
    // Download from direct URL
    const link = document.createElement('a')
    link.href = props.pdfUrl
    link.download = `${props.filename || 'document'}.pdf`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    ElMessage.success('PDF downloaded successfully')
  } else {
    // Fallback to regular download
    downloadPDF()
  }
}

/**
 * Handle Excel Export
 */
const handleExcelExport = () => {
  if (props.onExcel) {
    props.onExcel()
  } else {
    ElMessage.warning('Excel export function not provided')
  }
}

/**
 * Handle Word Export
 */
const handleWordExport = () => {
  if (props.onWord) {
    props.onWord()
  } else {
    ElMessage.warning('Word export function not provided')
  }
}

/**
 * Download DOCX (legacy method - kept for backward compatibility)
 */
const downloadDOCX = () => {
  if (props.htmlContent) {
    downloadDOCXFromComposable(props.htmlContent, props.filename || generatedFilename.value)
  } else {
    ElMessage.warning('No HTML content available for DOCX conversion')
  }
}

/**
 * Zoom controls
 */
const zoomIn = () => {
  zoom.value = Math.min(zoom.value + 0.1, 2)
}

const zoomOut = () => {
  zoom.value = Math.max(zoom.value - 0.1, 0.5)
}

/**
 * Rotate PDF
 */
const rotatePDF = () => {
  rotation.value = (rotation.value + 90) % 360
}

/**
 * Handle dialog close
 */
const handleClose = () => {
  cleanup()
  showPreview.value = false
  iframeLoaded.value = false
  showFallback.value = false
  isInitializing.value = false
}

// Cleanup on component unmount
onUnmounted(() => {
  cleanup()
})

// Expose methods for parent component
defineExpose({
  openPreview
})
</script>

<style scoped>
.preview-export-container {
  display: contents;
}

.preview-btn {
  min-width: 140px;
  height: 32px !important;
  display: flex;
  align-items: center;
  gap: 6px;
  flex-shrink: 0;
  padding: 0 15px;
  box-sizing: border-box;
  border: 1px solid transparent;
}

/* Responsive adjustments */
@media (max-width: 1366px) {
  .preview-btn {
    min-width: 120px;
    font-size: 13px;
  }
}

@media (max-width: 768px) {
  .preview-btn {
    width: 100%;
    justify-content: center;
    min-width: auto;
  }
}

.pdf-preview-modal {
  max-height: 95vh;
}

.dialog-header {
  display: flex;
  flex-direction: column;
}

.dialog-title {
  font-weight: 700;
  font-size: 16px;
  color: #0f172a;
  display: flex;
  align-items: center;
}

.dialog-subtitle {
  color: #64748b;
  font-size: 12px;
  margin-top: 2px;
}

.ml-3 {
  margin-left: 12px;
}

.preview-container {
  height: 70vh;
  display: flex;
  flex-direction: column;
}

/* Loading State */
.loading-container {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100%;
  position: relative;
}

.loading-content {
  text-align: center;
}

.loading-spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #e5e7eb;
  border-top: 4px solid #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 16px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.loading-text {
  font-size: 16px;
  font-weight: 600;
  color: #374151;
  margin: 0 0 8px 0;
}

.loading-subtext {
  font-size: 14px;
  color: #6b7280;
  margin: 0;
}

/* Error State */
.error-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100%;
  text-align: center;
}

.error-icon {
  font-size: 48px;
  margin-bottom: 16px;
}

.error-title {
  font-size: 18px;
  font-weight: 600;
  color: #dc2626;
  margin: 0 0 8px 0;
}

.error-message {
  font-size: 14px;
  color: #6b7280;
  margin: 0 0 24px 0;
  max-width: 400px;
}

/* PDF Container */
.pdf-container {
  display: flex;
  flex-direction: column;
  height: 100%;
}

.pdf-viewer-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px 8px 0 0;
  margin-bottom: 0;
}

.viewer-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 600;
  color: #374151;
}

.viewer-controls {
  display: flex;
  align-items: center;
  gap: 12px;
}

.page-info {
  font-size: 14px;
  color: #6b7280;
  font-weight: 500;
}

.zoom-controls {
  display: flex;
  align-items: center;
  gap: 8px;
}

.zoom-level {
  font-size: 12px;
  color: #6b7280;
  min-width: 40px;
  text-align: center;
}

.pdf-content-area {
  display: flex;
  flex: 1;
  border: 1px solid #e2e8f0;
  border-top: none;
  border-radius: 0 0 8px 8px;
  overflow: hidden;
}

.thumbnail-sidebar {
  width: 180px;
  background: #f8fafc;
  border-right: 1px solid #e2e8f0;
  padding: 16px;
  overflow-y: auto;
}

.thumbnail-container {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.thumbnail-preview {
  margin-bottom: 8px;
}

.thumbnail-label {
  font-size: 12px;
  color: #6b7280;
  text-align: center;
}

.main-viewer {
  flex: 1;
  display: flex;
  flex-direction: column;
  position: relative;
}

.pdf-viewer-container {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  transform-origin: center center;
  transition: transform 0.3s ease;
  overflow: auto; /* ensure full document is scrollable */
  height: 100%;
}

.pdf-iframe {
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  background: white;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

/* Fallback Message */
.fallback-message {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  background: #fef3c7;
  border: 1px solid #f59e0b;
  border-radius: 8px;
  padding: 24px;
  text-align: center;
  max-width: 400px;
}

.fallback-icon {
  font-size: 32px;
  color: #f59e0b;
  margin-bottom: 12px;
}

.fallback-title {
  font-size: 16px;
  font-weight: 600;
  color: #92400e;
  margin: 0 0 8px 0;
}

.fallback-text {
  font-size: 14px;
  color: #92400e;
  margin: 0 0 16px 0;
}

/* No Content State */
.no-content-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100%;
  text-align: center;
}

.no-content-icon {
  font-size: 64px;
  margin-bottom: 16px;
}

.no-content-title {
  font-size: 18px;
  font-weight: 600;
  color: #374151;
  margin: 0 0 8px 0;
}

.no-content-text {
  font-size: 14px;
  color: #6b7280;
  margin: 0;
}

/* Dialog Footer */
.dialog-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 0 0 0;
}

.footer-left {
  display: flex;
  gap: 8px;
}

.footer-right {
  display: flex;
  gap: 12px;
  align-items: center;
}

.download-label {
  font-size: 14px;
  font-weight: 500;
  color: #4b5563;
}

/* Export Buttons Styling */
.export-btn {
  min-width: 140px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  font-weight: 500;
  font-size: 14px;
  border-radius: 8px;
  transition: all 0.3s ease;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  position: relative;
  overflow: hidden;
}

.export-btn::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
  transition: left 0.5s ease;
}

.export-btn:hover::before {
  left: 100%;
}

.export-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

.export-btn:active {
  transform: translateY(0);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.export-btn:disabled {
  transform: none;
  opacity: 0.6;
  cursor: not-allowed;
}

.export-btn:disabled:hover {
  transform: none;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.export-icon {
  font-size: 16px;
  transition: transform 0.3s ease;
}

.export-btn:hover .export-icon {
  transform: scale(1.1);
}

/* Excel Button */
.export-excel {
  background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
  border: none;
  color: #ffffff;
}

.export-excel:hover:not(:disabled) {
  background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
  box-shadow: 0 4px 12px rgba(34, 197, 94, 0.4);
}

/* PDF Button */
.export-pdf {
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
  border: none;
  color: #ffffff;
}

.export-pdf:hover:not(:disabled) {
  background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
  box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
}

/* DOCX Button */
.export-docx {
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
  border: none;
  color: #ffffff;
}

.export-docx:hover:not(:disabled) {
  background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
}

/* Responsive Design */
@media (max-width: 768px) {
  .pdf-content-area {
    flex-direction: column;
  }
  
  .thumbnail-sidebar {
    width: 100%;
    height: 120px;
    border-right: none;
    border-bottom: 1px solid #e2e8f0;
  }
  
  .thumbnail-container {
    flex-direction: row;
    justify-content: center;
    gap: 16px;
  }
  
  .viewer-controls {
    flex-wrap: wrap;
    gap: 8px;
  }
  
  .zoom-controls {
    order: -1;
    width: 100%;
    justify-content: center;
  }

  .footer-right {
    flex-direction: column;
    width: 100%;
    gap: 8px;
  }

  .export-btn {
    width: 100%;
    min-width: auto;
  }
}
</style>
