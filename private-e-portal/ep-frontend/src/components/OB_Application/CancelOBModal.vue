<template>
  <el-dialog
    :model-value="true"
    title="Official Business Cancellation"
    width="500px"
    @close="$emit('close')"
    append-to-body
  >
    <!-- Confirmation Message -->
    <div class="text-center mb-6">
      <h4 class="text-lg font-medium text-slate-900 mb-2">
        Are you sure you want to cancel this OB application?
      </h4>
      <p class="text-sm text-slate-600">
        This action cannot be undone.
      </p>
    </div>

    <el-form @submit.prevent="handleSubmit">
      <!-- Reason -->
      <el-form-item label="Write reason for cancellation" required>
        <el-input 
          v-model="form.reason"
          type="textarea"
          :rows="4"
          placeholder="Enter reason for cancellation"
          required
        />
      </el-form-item>

      <!-- File Upload -->
      <el-form-item label="Upload File" required>
        <el-upload
          ref="fileInput"
          :auto-upload="false"
          :on-change="handleFileChange"
          :before-upload="beforeUpload"
          :show-file-list="false"
          accept=".jpg,.jpeg,.png,.xls,.xlsx,.doc,.docx,.pdf"
        >
          <el-button type="primary" plain>
            <el-icon><Upload /></el-icon>
            Choose File
          </el-button>
        </el-upload>
        <div class="text-xs text-slate-500 mt-1">
          Accepted Files: Excel (.xls, .xlsx), Word File (.doc, .docx) and PDF (.pdf) only. 
          File Maximum of 25MB.
        </div>
      </el-form-item>

      <!-- File Preview -->
      <el-form-item v-if="selectedFile">
        <div class="flex items-center justify-between p-2 bg-slate-50 rounded border">
          <span class="text-sm text-slate-700">{{ selectedFile.name }}</span>
          <el-button 
            type="danger" 
            link 
            @click="removeFile"
          >
            <el-icon><Close /></el-icon>
          </el-button>
        </div>
      </el-form-item>
    </el-form>

    <template #footer>
      <div class="flex justify-end gap-2">
        <el-button @click="$emit('close')">Close</el-button>
        <el-button 
          type="danger" 
          :loading="isSubmitting" 
          @click="handleSubmit"
        >
          {{ isSubmitting ? 'Submitting...' : 'Submit' }}
        </el-button>
      </div>
    </template>
  </el-dialog>
</template>

<script>
export default {
  name: 'CancelOBModal',
  props: {
    obApplication: {
      type: Object,
      default: null
    }
  },
  data() {
    return {
      isSubmitting: false,
      form: {
        reason: ''
      },
      selectedFile: null
    }
  },
  methods: {
    handleFileChange(file, fileList) {
      if (file.raw) {
        // Validate file type
        const allowedTypes = [
          'image/jpeg',
          'image/jpg', 
          'image/png',
          'application/vnd.ms-excel',
          'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
          'application/msword',
          'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
          'application/pdf'
        ]
        
        if (!allowedTypes.includes(file.raw.type)) {
          this.$toast.error(`Invalid file type: ${file.name}`)
          return false
        }
        
        // Validate file size (25MB)
        if (file.raw.size > 25 * 1024 * 1024) {
          this.$toast.error(`File too large: ${file.name}`)
          return false
        }
        
        this.selectedFile = file.raw
      }
      return false // Prevent auto upload
    },
    beforeUpload(file) {
      // This will be called before upload, but we handle validation in handleFileChange
      return false // Prevent auto upload
    },
    removeFile() {
      this.selectedFile = null
    },
    async handleSubmit() {
      if (!this.form.reason.trim()) {
        this.$toast.error('Please provide a reason for cancellation')
        return
      }
      
      if (!this.selectedFile) {
        this.$toast.error('Please upload a file')
        return
      }
      
      this.isSubmitting = true
      
      try {
        const formData = new FormData()
        formData.append('reason', this.form.reason)
        formData.append('attachment', this.selectedFile)
        
        this.$emit('submit', formData)
      } catch (error) {
        console.error('Error submitting cancellation:', error)
        this.$toast.error('Failed to submit cancellation')
      } finally {
        this.isSubmitting = false
      }
    }
  }
}
</script> 