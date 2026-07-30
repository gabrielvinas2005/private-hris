<template>
  <el-dialog
    v-model="visible"
    title="Attachment Preview"
    width="90%"
    top="5vh"
    destroy-on-close
    @closed="handleClosed"
  >
    <div v-if="filename" class="mb-3 text-sm text-slate-600">
      <span class="font-medium text-slate-800">{{ filename }}</span>
    </div>

    <div v-if="loading" class="py-16 text-center text-slate-500">Loading preview...</div>
    <div v-else-if="error" class="py-16 text-center text-red-600">{{ error }}</div>

    <iframe
      v-else-if="isPreviewableFile(filename) && previewUrl"
      :src="previewUrl"
      class="w-full border border-slate-200 rounded"
      style="height: 70vh"
      frameborder="0"
    />

    <img
      v-else-if="isImageFile(filename) && previewUrl"
      :src="previewUrl"
      :alt="filename"
      class="mx-auto block max-w-full rounded"
      style="max-height: 70vh"
    />

    <div v-else class="py-16 text-center text-slate-500">
      <p>Preview is not available for this file type.</p>
      <p class="text-sm mt-1">Use Download to open the file on your device.</p>
    </div>

    <template #footer>
      <el-button @click="visible = false">Close</el-button>
      <el-button v-if="previewUrl && canPreviewFile(filename)" @click="openInNewTab">
        Open in New Tab
      </el-button>
      <el-button type="primary" :disabled="!currentBlob" @click="downloadFile">Download</el-button>
    </template>
  </el-dialog>
</template>

<script>
import { accomplishmentApiService } from '@/services/apiService.js'
import {
  canPreviewFile,
  downloadBlob,
  isImageFile,
  isPreviewableFile
} from '@/utils/filePreview.js'

export default {
  name: 'NonDTRAttachmentPreviewDialog',
  data() {
    return {
      visible: false,
      loading: false,
      error: null,
      previewUrl: null,
      currentBlob: null,
      filename: ''
    }
  },
  methods: {
    isPreviewableFile,
    isImageFile,
    canPreviewFile,
    async open(attachmentId, filename = '') {
      this.visible = true
      this.filename = filename || 'attachment'
      this.error = null
      this.loading = true
      this.revokePreviewUrl()
      this.currentBlob = null

      try {
        const blob = await accomplishmentApiService.downloadAttachment(attachmentId)
        this.currentBlob = blob
        if (canPreviewFile(this.filename)) {
          this.previewUrl = URL.createObjectURL(blob)
        }
      } catch (e) {
        this.error = e?.response?.data?.message || e?.message || 'Failed to load attachment preview'
      } finally {
        this.loading = false
      }
    },
    downloadFile() {
      if (!this.currentBlob) return
      downloadBlob(this.currentBlob, this.filename)
    },
    openInNewTab() {
      if (!this.previewUrl) return
      window.open(this.previewUrl, '_blank', 'noopener,noreferrer')
    },
    revokePreviewUrl() {
      if (this.previewUrl) {
        URL.revokeObjectURL(this.previewUrl)
        this.previewUrl = null
      }
    },
    handleClosed() {
      this.revokePreviewUrl()
      this.currentBlob = null
      this.error = null
      this.loading = false
      this.filename = ''
    }
  },
  beforeUnmount() {
    this.revokePreviewUrl()
  }
}
</script>
