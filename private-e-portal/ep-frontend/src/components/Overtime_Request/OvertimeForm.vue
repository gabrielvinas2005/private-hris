<template>
  <el-dialog
    :model-value="true"
    :title="dialogTitle"
    width="720px"
    @close="$emit('close')"
    append-to-body
  >

    <!-- Error Alert -->
    <el-alert v-if="errorMessage" :title="errorMessage" type="error" show-icon class="mb-4" />

    <el-form @submit.prevent="handleSubmit">
      <div class="space-y-4">

            <!-- Employee ID (Hidden) -->
            <input type="hidden" v-model="form.employee_id" />

            <!-- Overtime Type -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="md:col-span-1">
                <label class="block text-sm font-medium text-slate-700 mb-2">Type:</label>
              </div>
              <div class="md:col-span-2">
                <select
                  v-model="form.overtime_type_id"
                  class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  required
                >
                  <option value="">Select Overtime Type</option>
                  <option
                    v-for="type in overtimeTypes"
                    :key="type.id"
                    :value="type.id"
                  >
                    {{ type.name }}
                  </option>
                </select>
              </div>
            </div>

            <!-- Date -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="md:col-span-1">
                <label class="block text-sm font-medium text-slate-700 mb-2">Date:</label>
              </div>
              <div class="md:col-span-2">
                <input
                  v-model="form.date"
                  type="date"
                  class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  required
                />
              </div>
            </div>

            <!-- Time From -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="md:col-span-1">
                <label class="block text-sm font-medium text-slate-700 mb-2">Time From:</label>
              </div>
              <div class="md:col-span-2">
                <input
                  v-model="form.date_time_from"
                  type="time"
                  class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  @change="calculateTotalHours"
                  required
                />
              </div>
            </div>

            <!-- Time To -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="md:col-span-1">
                <label class="block text-sm font-medium text-slate-700 mb-2">Time To:</label>
              </div>
              <div class="md:col-span-2">
                <input
                  v-model="form.date_time_to"
                  type="time"
                  class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  @change="calculateTotalHours"
                  required
                />
              </div>
            </div>

            <!-- Total Hours -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="md:col-span-1">
                <label class="block text-sm font-medium text-slate-700 mb-2">Total Hours:</label>
              </div>
              <div class="md:col-span-2">
                <input
                  v-model="form.total_hours"
                  type="number"
                  step="0.25"
                  class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-slate-50"
                  readonly
                />
              </div>
            </div>

            <!-- Payment Type -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="md:col-span-1">
                <label class="block text-sm font-medium text-slate-700 mb-2">Payment Type:</label>
              </div>
              <div class="md:col-span-2">
                <div class="flex space-x-6">
                  <label class="flex items-center">
                    <input
                      v-model="form.selectRadio"
                      type="radio"
                      value="1"
                      class="mr-2"
                    />
                    <span class="text-sm text-slate-700">Payroll</span>
                  </label>
                  <label v-if="serviceCredit > 0" class="flex items-center">
                    <input
                      v-model="form.selectRadio"
                      type="radio"
                      value="2"
                      class="mr-2"
                    />
                    <span class="text-sm text-slate-700">COC credits</span>
                  </label>
                </div>
              </div>
            </div>

            <!-- Remarks -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="md:col-span-1">
                <label class="block text-sm font-medium text-slate-700 mb-2">Remarks:</label>
              </div>
              <div class="md:col-span-2">
                <textarea
                  v-model="form.remarks"
                  rows="3"
                  class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
                  required
                ></textarea>
              </div>
            </div>

            <!-- Attachment -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="md:col-span-1">
                <label class="block text-sm font-medium text-slate-700 mb-2">
                  Attachment: <span class="text-red-500">*</span>
                </label>
              </div>
              <div class="md:col-span-2">
                <input
                  ref="fileInput"
                  type="file"
                  @change="handleFileUpload"
                  accept=".jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx,.pdf"
                  multiple
                  class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
                <p class="text-xs text-slate-600 mt-1">
                  <i>Accepted Files: image (.jpg, .jpeg, .png), Excel (.xls, .xlsx), Word File (.doc, .docx) and PDF (.pdf) only. Maximum of 5 files to upload. File Maximum of 25MB.</i>
                </p>
                <!-- File Status Indicator -->
                <div v-if="form.attachments.length > 0" class="mt-2 p-2 bg-green-50 border border-green-200 rounded-md">
                  <p class="text-sm text-green-700">
                    ✓ {{ form.attachments.length }} file(s) selected and ready for upload
                  </p>
                </div>
              </div>
            </div>

            <!-- File List -->
            <div v-if="form.attachments.length > 0" class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="md:col-span-1">
                <label class="block text-sm font-medium text-slate-700 mb-2">Uploaded Files:</label>
              </div>
              <div class="md:col-span-2">
                <div class="border border-slate-200 rounded-lg p-4">
                  <div v-for="(file, index) in form.attachments" :key="index" class="flex items-center justify-between py-2">
                    <span class="text-sm text-slate-700">{{ file.name }}</span>
                    <el-button
                      type="danger"
                      link
                      size="small"
                      @click="removeFile(index)"
                    >
                      <el-icon><Close /></el-icon>
                    </el-button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Existing Attachments -->
            <div v-if="form.existing_attachments.length > 0" class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="md:col-span-1">
                <label class="block text-sm font-medium text-slate-700 mb-2">Existing Attachments:</label>
              </div>
              <div class="md:col-span-2">
                <div class="border border-slate-200 rounded-lg p-4 space-y-2">
                  <div
                    v-for="attachment in form.existing_attachments"
                    :key="attachment.id"
                    class="flex items-center justify-between"
                  >
                    <span class="text-sm text-slate-700 truncate pr-2">{{ attachment.attachment_name }}</span>
                    <div class="flex items-center gap-2">
                      <el-button size="small" link type="primary" @click="downloadExistingAttachment(attachment)">
                        <el-icon><Download /></el-icon>
                        Download
                      </el-button>
                      <el-button
                        size="small"
                        link
                        type="danger"
                        @click="removeExistingAttachment(attachment)"
                      >
                        <el-icon><Close /></el-icon>
                        Remove
                      </el-button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
      </div>
    </el-form>

    <template #footer>
      <div class="flex justify-end gap-2">
        <el-button @click="handleClose">Close</el-button>
        <el-button 
          type="primary" 
          :loading="isSubmitting" 
          @click="handleSubmit"
        >
          {{ isSubmitting ? 'Saving...' : 'Save' }}
        </el-button>
      </div>
    </template>
  </el-dialog>
</template>

<script>
import ApiService from '../../services/api.js'
import { Close, Download } from '@element-plus/icons-vue'

export default {
  name: 'OvertimeForm',
  components: {
    Close,
    Download
  },
  props: {
    overtimeTypes: {
      type: Array,
      default: () => []
    },
    serviceCredit: {
      type: Number,
      default: 0
    },
    employeeId: {
      type: Number,
      default: 0
    },
    overtime: {
      type: Object,
      default: null
    }
  },
  data() {
    return {
      form: {
        id: null,
        employee_id: this.employeeId,
        overtime_type_id: '',
        date: '',
        date_time_from: '',
        date_time_to: '',
        total_hours: 0,
        selectRadio: '1',
        remarks: '',
        attachments: [],
        existing_attachments: []
      },
      errorMessage: '',
      isSubmitting: false
    }
  },
  computed: {
    dialogTitle() {
      return this.form.id ? 'Edit Overtime Information' : 'Add Overtime Information'
    },
    totalAttachmentCount() {
      const newFiles = this.form.attachments ? this.form.attachments.length : 0
      const existingFiles = this.form.existing_attachments ? this.form.existing_attachments.length : 0
      return newFiles + existingFiles
    }
  },
  mounted() {
    this.resetForm()
    if (this.overtime && this.overtime.id) {
      this.populateForm(this.overtime)
    }
  },
  watch: {
    employeeId: {
      handler(newVal) {
        if (newVal && newVal !== this.form.employee_id && !this.form.id) {
          this.resetForm()
        }
      },
      immediate: true
    },
    overtime: {
      handler(newVal) {
        if (newVal && newVal.id) {
          this.populateForm(newVal)
        } else {
          this.resetForm()
        }
      },
      immediate: true
    }
  },
  methods: {
    formatSubmitError(error) {
      // ApiService.request throws Error("HTTP error! status: XXX - <backend message>")
      // Surface the backend message to the user when available.
      const raw = (error && error.message) ? String(error.message) : ''
      if (!raw) return 'Failed to save overtime application'
      const marker = ' - '
      const idx = raw.indexOf(marker)
      if (idx >= 0 && idx + marker.length < raw.length) {
        return raw.slice(idx + marker.length).trim() || 'Failed to save overtime application'
      }
      return raw
    },
    resetForm() {
      this.form = {
        id: null,
        employee_id: this.employeeId,
        overtime_type_id: '',
        date: '',
        date_time_from: '',
        date_time_to: '',
        total_hours: 0,
        selectRadio: '1',
        remarks: '',
        attachments: [],
        existing_attachments: []
      }
      this.errorMessage = ''
      this.isSubmitting = false
      if (this.$refs.fileInput) this.$refs.fileInput.value = ''
    },
    populateForm(overtime) {
      this.form.id = overtime.id || null
      this.form.employee_id = overtime.employee_id || this.employeeId
      this.form.overtime_type_id = overtime.overtime_type_id ? Number(overtime.overtime_type_id) : ''
      this.form.date = this.normalizeDateInput(overtime.date)
      this.form.date_time_from = this.extractTimeInput(overtime.date_time_from)
      this.form.date_time_to = this.extractTimeInput(overtime.date_time_to)
      this.form.total_hours = Number(overtime.total_hours) || 0
      const usesServiceCredits = overtime.service_credits === true || overtime.service_credits === 1 || overtime.service_credits === '1'
      this.form.selectRadio = usesServiceCredits ? '2' : '1'
      this.form.remarks = overtime.remarks || ''
      this.form.attachments = []
      this.form.existing_attachments = []
      if (this.$refs.fileInput) this.$refs.fileInput.value = ''
      if (overtime.id) {
        this.loadExistingAttachments(overtime.id)
      }
    },
    normalizeDateInput(value) {
      if (!value) return ''
      const cleaned = value.replace(/\//g, '-')
      const basicMatch = cleaned.match(/^(\d{4})-(\d{2})-(\d{2})/)
      if (basicMatch) {
        return `${basicMatch[1]}-${basicMatch[2]}-${basicMatch[3]}`
      }
      const [datePart] = cleaned.split('T')
      if (/^\d{4}-\d{2}-\d{2}$/.test(datePart)) {
        return datePart
      }
      return cleaned.slice(0, 10)
    },
    extractTimeInput(value) {
      if (!value) return ''
      const timeMatch = value.match(/(\d{1,2}):(\d{2})/)
      if (timeMatch) {
        return `${timeMatch[1].padStart(2, '0')}:${timeMatch[2]}`
      }
      const date = new Date(value)
      if (!isNaN(date.getTime())) {
        return date.toTimeString().slice(0, 5)
      }
      return value
    },
    calculateTotalHours() {
      if (this.form.date_time_from && this.form.date_time_to) {
        const fromTime = new Date(`2000-01-01T${this.form.date_time_from}`)
        const toTime = new Date(`2000-01-01T${this.form.date_time_to}`)
        
        if (toTime > fromTime) {
          const diffMs = toTime - fromTime
          const diffHours = diffMs / (1000 * 60 * 60)
          this.form.total_hours = Math.round(diffHours * 4) / 4 // Round to nearest 0.25
        } else {
          this.form.total_hours = 0
        }
      }
    },
    handleFileUpload(event) {
      const files = Array.from(event.target.files)
      
      // Validate number of files
      if (this.totalAttachmentCount + files.length > 5) {
        this.errorMessage = 'Maximum of 5 files allowed'
        event.target.value = ''
        return
      }

      // Validate each file
      for (const file of files) {
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

        // Add file to attachments array
        this.form.attachments.push(file)
      }

      // Clear error message and file input
      this.errorMessage = ''
      event.target.value = ''
      
      // attachments added
    },
    removeFile(index) {
      this.form.attachments.splice(index, 1)
      // Clear file input if no more attachments
      if (this.form.attachments.length === 0 && this.$refs.fileInput) {
        this.$refs.fileInput.value = ''
      }
    },
    async loadExistingAttachments(overtimeId) {
      if (!overtimeId) return
      try {
        const response = await ApiService.getOvertimeAttachments(overtimeId)
        if (response && response.success) {
          this.form.existing_attachments = Array.isArray(response.data) ? response.data : response
        }
      } catch (error) {
        this.errorMessage = 'Failed to load existing attachments'
      }
    },
    async removeExistingAttachment(attachment) {
      if (!attachment || !attachment.id) return
      try {
        const response = await ApiService.removeOvertimeAttachment(attachment.id)
        if (response && response.success) {
          this.form.existing_attachments = this.form.existing_attachments.filter(a => a.id !== attachment.id)
        } else {
          this.errorMessage = (response && response.message) || 'Failed to remove attachment'
        }
      } catch (error) {
        this.errorMessage = 'Failed to remove attachment'
      }
    },
    async downloadExistingAttachment(attachment) {
      if (!attachment || !attachment.id) return
      try {
        await ApiService.downloadOvertimeAttachment(attachment.id)
      } catch (error) {
        this.errorMessage = 'Failed to download attachment'
      }
    },
    handleClose() {
      this.resetForm()
      this.errorMessage = ''
      this.$emit('close')
    },
    async handleSubmit() {
      if (this.totalAttachmentCount === 0) {
        this.errorMessage = 'Please select one or more files'
        return
      }

      this.isSubmitting = true
      this.errorMessage = ''

      try {
        const formData = new FormData()
        formData.append('overtime_id', this.form.id || 0)
        formData.append('employee_id', this.form.employee_id)
        formData.append('overtime_type_id', this.form.overtime_type_id)
        formData.append('date', this.form.date)
        formData.append('date_time_from', this.form.date_time_from)
        formData.append('date_time_to', this.form.date_time_to)
        formData.append('total_hours', this.form.total_hours)
        formData.append('selectRadio', this.form.selectRadio)
        formData.append('remarks', this.form.remarks)

        this.form.attachments.forEach((file, index) => {
          formData.append(`attachments[${index}]`, file)
        })

        const response = this.form.id
          ? await ApiService.updateOvertimeApplication(this.form.id, formData)
          : await ApiService.addOvertimeApplication(formData)

        if (response && response.success) {
          if (this.$refs.fileInput) {
            this.$refs.fileInput.value = ''
          }
          const wasUpdate = Boolean(this.form.id)
          this.resetForm()
          this.$emit('saved', { success: true, updated: wasUpdate })
        } else {
          this.errorMessage = (response && response.message) || 'Failed to save overtime application'
        }
      } catch (error) {
        this.errorMessage = this.formatSubmitError(error)
      } finally {
        this.isSubmitting = false
      }
    }
  }
}
</script> 