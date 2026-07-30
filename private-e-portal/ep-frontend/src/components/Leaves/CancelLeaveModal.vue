<template>
  <div v-if="show" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
      <div class="text-center">
        <h3 class="text-lg font-semibold text-slate-900 mb-4">Leave Cancellation</h3>
        <p class="text-slate-600 mb-6">
          Are you sure you want to cancel this leave application?
        </p>
        
        <form @submit.prevent="handleSubmit">
          <div class="mb-4">
            <label class="block text-sm font-medium text-slate-700 mb-2">
              Reason for cancellation: <span class="text-red-500">*</span>
            </label>
            <textarea 
              v-model="form.reason"
              rows="4" 
              class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
              placeholder="Please provide a reason for cancellation"
              required
            ></textarea>
          </div>
          
          <div class="mb-6">
            <label class="block text-sm font-medium text-slate-700 mb-2">
              Upload File: <span class="text-red-500">*</span>
            </label>
            <input 
              ref="fileInput"
              type="file"
              @change="handleFileChange"
              accept=".jpg,.jpeg,.png,.xls,.xlsx,.doc,.docx,.pdf"
              class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              required
            >
            <p class="text-xs text-slate-500 mt-1">
              Accepted Files: Image (jpg, jpeg, png), Excel (.xls, .xlsx), Word File (.doc, .docx) and PDF (.pdf) only. 
              File Maximum of 25MB.
            </p>
          </div>
          
          <div class="flex justify-end space-x-3">
            <button 
              type="button"
              @click="$emit('close')"
              class="px-4 py-2 text-slate-600 border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors duration-200"
            >
              Close
            </button>
            <button 
              type="submit"
              :disabled="isSubmitting"
              class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {{ isSubmitting ? 'Submitting...' : 'Submit' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'CancelLeaveModal',
  props: {
    show: {
      type: Boolean,
      default: false
    },
    leave: {
      type: Object,
      default: () => ({})
    }
  },
  data() {
    return {
      form: {
        reason: '',
        attachment: null
      },
      isSubmitting: false
    }
  },
  methods: {
    handleFileChange(event) {
      const file = event.target.files[0]
      if (file) {
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
        
        if (!allowedTypes.includes(file.type)) {
          this.$toast.error('Invalid file type. Please select a valid file.')
          event.target.value = ''
          return
        }
        
        // Validate file size (25MB = 25 * 1024 * 1024 bytes)
        if (file.size > 25 * 1024 * 1024) {
          this.$toast.error('File size too large. Maximum size is 25MB.')
          event.target.value = ''
          return
        }
        
        this.form.attachment = file
      }
    },
    async handleSubmit() {
      if (!this.form.reason.trim()) {
        this.$toast.error('Please provide a reason for cancellation')
        return
      }
      
      if (!this.form.attachment) {
        this.$toast.error('Please upload a file')
        return
      }
      
      this.isSubmitting = true
      
      try {
        const formData = new FormData()
        formData.append('reason', this.form.reason)
        formData.append('attachment', this.form.attachment)
        
        await this.$emit('submit', formData)
        
        // Reset form
        this.form.reason = ''
        this.form.attachment = null
        if (this.$refs.fileInput) {
          this.$refs.fileInput.value = ''
        }
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