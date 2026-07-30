<template>
  <el-dialog
    title="Overtime Cancellation"
    :model-value="true"
    width="520px"
    :close-on-click-modal="false"
    :close-on-press-escape="false"
    @close="$emit('close')"
  >
    <el-alert v-if="errorMessage" :title="errorMessage" type="error" show-icon class="mb-4" />

    <div class="text-center mb-4">
      <h5 class="text-base font-medium text-slate-900">Are you sure you want to cancel?</h5>
    </div>

    <el-form label-position="top" @submit.prevent>
      <el-form-item label="Write reason for cancellation" required>
        <el-input v-model="form.reason" type="textarea" :rows="4" placeholder="Reason" />
      </el-form-item>

      <el-form-item label="Upload File" required>
        <input
          ref="fileInput"
          type="file"
          @change="handleFileUpload"
          accept=".jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx,.pdf"
        />
        <p class="text-xs text-slate-600 mt-1">
          <i>Accepted: JPG, PNG, PDF, DOC, XLS (max 25MB)</i>
        </p>
      </el-form-item>

      <div v-if="form.attachment" class="p-3 bg-slate-50 rounded mb-2 flex items-center justify-between">
        <span class="text-sm text-slate-700">{{ form.attachment.name }}</span>
        <el-button type="danger" text size="small" @click="removeFile">Remove</el-button>
      </div>
    </el-form>

    <template #footer>
      <span class="dialog-footer">
        <el-button @click="$emit('close')">Close</el-button>
        <el-button type="primary" :loading="isSubmitting" @click="handleSubmit">
          {{ isSubmitting ? 'Submitting...' : 'Submit' }}
        </el-button>
      </span>
    </template>
  </el-dialog>
</template>

<script>
import { mockApiService } from '../../services/mockData.js'

export default {
  name: 'CancelOvertimeModal',
  data() {
    return {
      form: {
        reason: '',
        attachment: null
      },
      errorMessage: '',
      isSubmitting: false
    }
  },
  methods: {
    handleFileUpload(event) {
      const file = event.target.files[0]
      
      if (file) {
        // Validate file type
        const allowedTypes = [
          'image/jpeg',
          'image/jpg',
          'image/png',
          'application/pdf',
          'application/msword',
          'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
          'application/vnd.ms-excel',
          'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ]
        
        if (!allowedTypes.includes(file.type)) {
          this.errorMessage = `Invalid file type: ${file.name}`
          event.target.value = ''
          return
        }

        // Validate file size (25MB)
        if (file.size > 25 * 1024 * 1024) {
          this.errorMessage = `File too large: ${file.name}`
          event.target.value = ''
          return
        }

        this.form.attachment = file
        this.errorMessage = ''
      }
      
      event.target.value = ''
    },
    removeFile() {
      this.form.attachment = null
    },
    async handleSubmit() {
      if (!this.form.attachment) {
        this.errorMessage = 'Please upload a file'
        return
      }

      this.isSubmitting = true
      this.errorMessage = ''

      try {
        const formData = new FormData()
        formData.append('reason', this.form.reason)
        formData.append('attachment', this.form.attachment)

        await mockApiService.cancelOvertimeApplication(formData)
        this.$emit('cancelled')
      } catch (error) {
        console.error('Error cancelling overtime application:', error)
        this.errorMessage = 'Failed to cancel overtime application'
      } finally {
        this.isSubmitting = false
      }
    }
  }
}
</script> 