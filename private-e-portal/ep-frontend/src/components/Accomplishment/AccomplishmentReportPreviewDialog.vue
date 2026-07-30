<template>
  <el-dialog
    v-model="visible"
    :title="dialogTitle"
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
  blobFromAttachmentPayload,
  canPreviewFile,
  downloadBlob,
  isPreviewableFile,
  resolveAttachmentPayload
} from '@/utils/filePreview.js'

export default {
  name: 'AccomplishmentReportPreviewDialog',
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
  computed: {
    dialogTitle() {
      return this.filename && String(this.filename).includes('approved_accomplishment')
        ? 'Approved Accomplishment Report'
        : 'Accomplishment Report'
    }
  },
  methods: {
    isPreviewableFile,
    canPreviewFile,
    async openApprovedReport(taskId, filename = 'approved_accomplishment_report.pdf') {
      await this.loadFromFetcher(
        () => accomplishmentApiService.downloadApprovedReport(taskId),
        filename
      )
    },
    async loadFromFetcher(fetcher, filename = '') {
      this.visible = true
      this.filename = filename || 'report.pdf'
      this.error = null
      this.loading = true
      this.revokePreviewUrl()
      this.currentBlob = null

      try {
        const response = await fetcher()
        const data = resolveAttachmentPayload(response)
        const blob = blobFromAttachmentPayload(response)
        this.currentBlob = blob
        if (!filename && data.filename) {
          this.filename = data.filename
        }
        if (canPreviewFile(this.filename)) {
          this.previewUrl = URL.createObjectURL(blob)
        }
      } catch (e) {
        const message = e?.response?.data?.message
          || (typeof e?.response?.data === 'string' ? e.response.data : null)
          || e?.message
          || 'Failed to load report preview'
        this.error = message
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
