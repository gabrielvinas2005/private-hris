<template>
  <el-drawer
    v-model="visible"
    :title="`Official Business Details - ${getOBTypeName(obApplication?.ob_type)}`"
    direction="rtl"
    size="50%"
    @close="$emit('close')"
  >
    <div v-if="obApplication" class="px-6 py-4">
      <!-- Header Info -->
      <div class="mb-6">
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center gap-3">
            <el-tag :type="getOBTypeTagType(obApplication.ob_type)" effect="light" size="large">
              {{ getOBTypeName(obApplication.ob_type) }}
            </el-tag>
            <el-tag v-if="getStatusTagType(obApplication)" :type="getStatusTagType(obApplication)" effect="light">
              {{ getStatusText(obApplication) }}
            </el-tag>
          </div>
          <div class="text-sm text-slate-500">
            Filed: {{ formatDate(obApplication.created_at) }}
          </div>
        </div>
      </div>

      <!-- Basic Information -->
      <div class="space-y-6">
        <div class="bg-slate-50 rounded-lg p-4">
          <h3 class="text-lg font-semibold text-slate-900 mb-4">Basic Information</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div v-if="isApprover">
              <label class="block text-sm font-medium text-slate-700 mb-1">Employee Name</label>
              <p class="text-slate-900">{{ obApplication.name || `${obApplication.last_name}, ${obApplication.first_name}` }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Date Filed</label>
              <p class="text-slate-900">{{ formatDate(obApplication.created_at) }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Departure Date and Time</label>
              <p class="text-slate-900">{{ formatDate(obApplication.date) }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Client/Organization</label>
              <p class="text-slate-900">{{ obApplication.client || 'N/A' }}</p>
            </div>
            <div v-if="obApplication.ob_type === 2">
              <label class="block text-sm font-medium text-slate-700 mb-1">Budget Officer</label>
              <p class="text-slate-900">{{ obApplication.recommending_approval || 'N/A' }}</p>
            </div>
          </div>
        </div>

        <!-- Date & Time Information -->
        <div class="bg-blue-50 rounded-lg p-4">
          <h3 class="text-lg font-semibold text-slate-900 mb-4">Date & Time Information</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">From</label>
              <p class="text-slate-900">{{ formatDateTime(obApplication.date_time_from) }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">To</label>
              <p class="text-slate-900">{{ formatDateTime(obApplication.date_time_to) }}</p>
            </div>
          </div>
        </div>

        <!-- Purpose -->
        <div class="bg-green-50 rounded-lg p-4">
          <h3 class="text-lg font-semibold text-slate-900 mb-4">Purpose</h3>
          <p class="text-slate-900">{{ obApplication.purpose || 'N/A' }}</p>
        </div>

        <!-- Travel Authority Specific Fields -->
        <div v-if="obApplication.ob_type === 2" class="bg-purple-50 rounded-lg p-4">
          <h3 class="text-lg font-semibold text-slate-900 mb-4">Travel Authority Details</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Travel Authority Type</label>
              <p class="text-slate-900">{{ obApplication.ta_type_name || 'N/A' }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Activity Organized/Sponsored by</label>
              <p class="text-slate-900">{{ obApplication.client || 'N/A' }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Fund Source</label>
              <p class="text-slate-900">{{ obApplication.funds || 'N/A' }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Budget Officer</label>
              <p class="text-slate-900">{{ obApplication.recommending_approval || 'N/A' }}</p>
            </div>
          </div>
        </div>

        <!-- Travel Order Specific Fields -->
        <div v-if="obApplication.ob_type === 3" class="bg-amber-50 rounded-lg p-4">
          <h3 class="text-lg font-semibold text-slate-900 mb-4">Travel Order Details</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Travel Order Type</label>
              <p class="text-slate-900">{{ obApplication.to_type_name || 'N/A' }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Client</label>
              <p class="text-slate-900">{{ obApplication.client || 'N/A' }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Budget Officer</label>
              <p class="text-slate-900">{{ obApplication.recommending_approval || 'N/A' }}</p>
            </div>
          </div>
        </div>

        <!-- Official Business Specific Fields -->
        <div v-if="obApplication.ob_type === 1" class="bg-orange-50 rounded-lg p-4">
          <h3 class="text-lg font-semibold text-slate-900 mb-4">Official Business Details</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Type</label>
              <p class="text-slate-900">{{ obApplication.ob_type === '1' ? 'Personal' : 'Official' }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Client</label>
              <p class="text-slate-900">{{ obApplication.client || 'N/A' }}</p>
            </div>
          </div>
        </div>

        <!-- Approval Information -->
        <div class="bg-yellow-50 rounded-lg p-4">
          <h3 class="text-lg font-semibold text-slate-900 mb-4">Approval Information</h3>
          <div class="space-y-3">
            <div v-for="level in approvalLevels" :key="level.id">
              <label class="block text-sm font-medium text-slate-700 mb-1">Level {{ level.id }} Approver</label>
              <p class="text-slate-900">{{ level.name }}</p>
              <p v-if="level.date" class="text-sm text-slate-500">
                {{ level.status }}: {{ level.date }}
              </p>
              <p v-else class="text-sm text-slate-500">
                {{ level.status }}
              </p>
            </div>
          </div>
        </div>

        <!-- Attachments -->
        <div v-if="obApplication.attachments && obApplication.attachments.length" class="bg-indigo-50 rounded-lg p-4">
          <h3 class="text-lg font-semibold text-slate-900 mb-4">Attachments</h3>
          <div class="space-y-2">
            <div v-for="attachment in obApplication.attachments" :key="attachment.id" class="flex items-center justify-between p-2 bg-white rounded border">
              <span class="text-slate-900">{{ attachment.attachment_name }}</span>
              <el-button size="small" type="primary" plain @click="downloadAttachment(attachment)">
                <el-icon><Download /></el-icon>
                Download
              </el-button>
            </div>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center justify-end gap-3 pt-6 border-t">
          <el-button @click="$emit('close')">Close</el-button>
          <el-button type="primary" plain @click="$emit('print', obApplication)">
            <el-icon><Printer /></el-icon>
            Print
          </el-button>
          <el-button v-if="!isApprover" type="warning" plain @click="$emit('edit', obApplication)">
            <el-icon><Edit /></el-icon>
            Edit
          </el-button>
        </div>
      </div>
    </div>
  </el-drawer>
</template>

<script>
export default {
  name: 'OBDetailDrawer',
  props: {
    modelValue: {
      type: Boolean,
      default: false
    },
    obApplication: {
      type: Object,
      default: null
    },
    isApprover: {
      type: Boolean,
      default: false
    }
  },
  emits: ['update:modelValue', 'close', 'print', 'edit'],
  computed: {
    visible: {
      get() {
        return this.modelValue
      },
      set(value) {
        this.$emit('update:modelValue', value)
      }
    },
    approvalLevels() {
      if (!this.obApplication) return []
      const levels = []
      const require2 = this.isTruthyFlag(this.obApplication.requires_second_level)
      const require3 = this.isTruthyFlag(this.obApplication.requires_third_level)
      const maxLevel = require3 ? 3 : (require2 ? 2 : 1)

      const config = [
        {
          id: 1,
          name: this.obApplication.approver_1 || this.obApplication.recommending_approval || 'Level 1 Approver',
          processedDate: this.obApplication.processed_date,
          approvedFlag: this.obApplication.approved,
          disapprovedFlag: this.obApplication.disapproved,
          disapprovedRemark: this.obApplication.disapproved_remark
        },
        {
          id: 2,
          name: this.obApplication.approver_2 || this.obApplication.approver || 'Level 2 Approver',
          processedDate: this.obApplication.processed_date_2,
          approvedFlag: this.obApplication.approved_2,
          disapprovedFlag: this.obApplication.disapproved_2,
          disapprovedRemark: this.obApplication.disapproved_2_remark
        },
        {
          id: 3,
          name: this.obApplication.approver_3 || 'Level 3 Approver',
          processedDate: this.obApplication.processed_date_3,
          approvedFlag: this.obApplication.approved_3,
          disapprovedFlag: this.obApplication.disapproved_3,
          disapprovedRemark: this.obApplication.disapproved_3_remark
        }
      ]

      config.forEach(level => {
        if (level.id > maxLevel) return

        if (!level.name || level.name.trim() === '') {
          level.name = `Level ${level.id} Approver`
        }

        const status = this.getLevelStatus(level.approvedFlag, level.disapprovedFlag)
        const date = level.processedDate ? this.formatDateTime(level.processedDate) : null
        const detail = status === 'Disapproved' && level.disapprovedRemark ? ` (${level.disapprovedRemark})` : ''

        levels.push({
          id: level.id,
          name: level.name,
          status: `${status}${detail}`,
          date
        })
      })

      return levels.filter(level => level.name && level.name.trim() !== '')
    }
  },
  methods: {
    getLevelStatus(approvedFlag, disapprovedFlag) {
      if (this.isTruthyFlag(disapprovedFlag)) return 'Disapproved'
      if (this.isTruthyFlag(approvedFlag)) return 'Approved'
      return 'Pending'
    },
    formatDate(dateString) {
      if (!dateString) return 'N/A'
      const date = new Date(dateString)
      return date.toLocaleDateString('en-US', {
        month: '2-digit',
        day: '2-digit',
        year: 'numeric'
      })
    },
    formatDateTime(dateString) {
      if (!dateString) return 'N/A'
      
      let date
      
      // Handle different date formats from backend
      if (typeof dateString === 'string') {
        // If it's in format "Y/m/d H:i:s" (from database), parse it manually to avoid timezone issues
        // Example: "2024/01/15 14:30:00" or "2024/1/15 14:30:00"
        const slashFormatMatch = dateString.match(/^(\d{4})\/(\d{1,2})\/(\d{1,2})\s+(\d{1,2}):(\d{1,2}):?(\d{0,2})/)
        if (slashFormatMatch) {
          // Format: "Y/m/d H:i:s" - parse as local time (no timezone conversion)
          const [, year, month, day, hour, minute, second] = slashFormatMatch
          date = new Date(
            parseInt(year),
            parseInt(month) - 1, // Month is 0-indexed
            parseInt(day),
            parseInt(hour),
            parseInt(minute),
            parseInt(second || 0)
          )
        } else if (dateString.includes('T')) {
          // ISO format with T
          if (dateString.includes('Z') || dateString.match(/[+-]\d{2}:\d{2}$/)) {
            // Has timezone info
            date = new Date(dateString)
          } else {
            // No timezone, treat as local time by appending Z and adjusting
            const tempDate = new Date(dateString + 'Z')
            const offset = tempDate.getTimezoneOffset()
            date = new Date(tempDate.getTime() - (offset * 60 * 1000))
          }
        } else {
          // Try standard parsing
          date = new Date(dateString)
        }
      } else {
        date = new Date(dateString)
      }
      
      // Check if date is valid
      if (isNaN(date.getTime())) {
        console.warn('Invalid date string:', dateString)
        return dateString // Return original if parsing failed
      }
      
      return date.toLocaleDateString('en-US', {
        month: '2-digit',
        day: '2-digit',
        year: 'numeric'
      }) + ' ' + date.toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: true
      })
    },
    getOBTypeName(obType) {
      const type = parseInt(obType)
      switch (type) {
        case 1: return 'Official Business'
        case 2: return 'Travel Authority'
        case 3: return 'Travel Order'
        default: return 'Official Business'
      }
    },
    getOBTypeTagType(obType) {
      const type = parseInt(obType)
      switch (type) {
        case 1: return 'info'
        case 2: return 'success'
        case 3: return 'warning'
        default: return 'info'
      }
    },
    getStatusText(ob) {
      if (this.isCancelled(ob)) return 'Cancelled'
      if (this.isFullyDisapproved(ob)) return 'Disapproved'
      if (this.isFullyApproved(ob)) return 'Approved'
      return 'Pending'
    },
    getStatusTagType(ob) {
      if (this.isCancelled(ob)) return 'danger'
      if (this.isFullyDisapproved(ob)) return 'warning'
      if (this.isFullyApproved(ob)) return 'success'
      return 'info'
    },
    isTruthyFlag(value) {
      if (value === true || value === 1) return true
      if (value === false || value === 0) return false
      if (typeof value === 'string') {
        const normalized = value.trim().toLowerCase()
        if (['1', 'true', 'yes', 'y'].includes(normalized)) return true
        if (['0', 'false', 'no', 'n', ''].includes(normalized)) return false
      }
      return false
    },
    hasLevel(ob, level) {
      const require2 = this.isTruthyFlag(ob.requires_second_level)
      const require3 = this.isTruthyFlag(ob.requires_third_level)
      if (level === 3) return require3
      if (level === 2) return require2 || require3
      return true
    },
    isCancelled(ob) {
      return [
        ob.is_cancel, ob.is_cancel_2, ob.is_cancel_3,
        ob.cancelled, ob.cancelled_2, ob.cancelled_3
      ].some(this.isTruthyFlag)
    },
    isFullyApproved(ob) {
      const level1Approved = this.isTruthyFlag(ob.approved)
      const level2Approved = this.isTruthyFlag(ob.approved_2)
      const level3Approved = this.isTruthyFlag(ob.approved_3)

      if (this.hasLevel(ob, 3)) {
        return level1Approved && level2Approved && level3Approved
      }

      if (this.hasLevel(ob, 2)) {
        return level1Approved && level2Approved
      }

      return level1Approved
    },
    isFullyDisapproved(ob) {
      return [
        ob.disapprove_1, ob.disapprove_2, ob.disapprove_3,
        ob.disapproved, ob.disapproved_2, ob.disapproved_3
      ].some(this.isTruthyFlag)
    },
    downloadAttachment(attachment) {
      if (attachment.download_url) {
        window.open(attachment.download_url, '_blank')
      }
    }
  }
}
</script>
