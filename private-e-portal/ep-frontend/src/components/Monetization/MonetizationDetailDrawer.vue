<template>
  <el-drawer
    v-model="visible"
    :title="`Leave Monetization Details - ${getTypeName(monetization?.type_id)}`"
    direction="rtl"
    size="50%"
    @close="$emit('close')"
  >
    <div v-if="monetization" class="px-6 py-4">
      <!-- Header Info -->
      <div class="mb-6">
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center gap-3">
            <el-tag :type="getTypeTagType(monetization.type_id)" effect="light" size="large">
              {{ getTypeName(monetization.type_id) }}
            </el-tag>
            <el-tag v-if="getStatusTagType(monetization)" :type="getStatusTagType(monetization)" effect="light">
              {{ getStatusText(monetization) }}
            </el-tag>
          </div>
          <div class="text-sm text-slate-500">
            Applied: {{ formatDate(monetization.created_at) }}
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
              <p class="text-slate-900">{{ monetization.name || 'N/A' }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Application Date</label>
              <p class="text-slate-900">{{ formatDate(monetization.created_at) }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Monetization Type</label>
              <p class="text-slate-900">{{ getTypeName(monetization.type_id) }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Status</label>
              <p class="text-slate-900">{{ getStatusText(monetization) }}</p>
            </div>
          </div>
        </div>

        <!-- Leave Credits Information -->
        <div class="bg-blue-50 rounded-lg p-4">
          <h3 class="text-lg font-semibold text-slate-900 mb-4">Leave Credits</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Available VL Credits</label>
              <p class="text-slate-900">{{ formatNumber(monetization.vl_credit, 3) }} days</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">VL to Monetize</label>
              <p class="text-slate-900">{{ formatNumber(monetization.vl_to_monetize || monetization.vl_credit, 3) }} days</p>
            </div>
            <div v-if="monetization.type_id == 2">
              <label class="block text-sm font-medium text-slate-700 mb-1">Available SL Credits</label>
              <p class="text-slate-900">{{ formatNumber(monetization.sl_credit, 3) }} days</p>
            </div>
            <div v-if="monetization.type_id == 2">
              <label class="block text-sm font-medium text-slate-700 mb-1">SL to Monetize</label>
              <p class="text-slate-900">{{ formatNumber(monetization.sl_to_monetize || monetization.sl_credit, 3) }} days</p>
            </div>
          </div>
        </div>

        <!-- Calculation Information -->
        <div class="bg-green-50 rounded-lg p-4">
          <h3 class="text-lg font-semibold text-slate-900 mb-4">Calculation</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Total Days</label>
              <p class="text-slate-900 font-semibold">{{ formatNumber(monetization.total_days, 3) }} days</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Amount</label>
              <p class="text-slate-900 font-semibold text-lg">₱{{ formatNumber(monetization.amount, 2) }}</p>
            </div>
          </div>
        </div>

        <!-- Approval Information -->
        <div class="bg-yellow-50 rounded-lg p-4">
          <h3 class="text-lg font-semibold text-slate-900 mb-4">Approval Information</h3>
          <div class="space-y-3">
            <div v-if="monetization.level_1_approver">
              <label class="block text-sm font-medium text-slate-700 mb-1">Level 1 Approver</label>
              <p class="text-slate-900">{{ monetization.level_1_approver }}</p>
              <p v-if="monetization.approve_date_1" class="text-sm text-slate-500">
                {{ monetization.approve_1 ? 'Approved' : monetization.disapprove_1 ? 'Disapproved' : 'Pending' }}: {{ formatDate(monetization.approve_date_1) }}
              </p>
            </div>
            <div v-if="monetization.level_2_approver">
              <label class="block text-sm font-medium text-slate-700 mb-1">Level 2 Approver</label>
              <p class="text-slate-900">{{ monetization.level_2_approver }}</p>
              <p v-if="monetization.approve_date_2" class="text-sm text-slate-500">
                {{ monetization.approve_2 ? 'Approved' : monetization.disapprove_2 ? 'Disapproved' : 'Pending' }}: {{ formatDate(monetization.approve_date_2) }}
              </p>
            </div>
          </div>
        </div>

        <!-- Attachments -->
        <div v-if="monetization.attachments && monetization.attachments.length" class="bg-indigo-50 rounded-lg p-4">
          <h3 class="text-lg font-semibold text-slate-900 mb-4">Attachments</h3>
          <div class="space-y-2">
            <div v-for="(attachment, index) in monetization.attachments" :key="index" class="flex items-center justify-between p-2 bg-white rounded border">
              <span class="text-slate-900">{{ index + 1 }}. {{ attachment.attachment || 'Attachment' }}</span>
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
          <el-button v-if="canEdit" type="warning" plain @click="$emit('edit', monetization)">
            <el-icon><Edit /></el-icon>
            Edit
          </el-button>
        </div>
      </div>
    </div>
  </el-drawer>
</template>

<script>
import { useToast } from 'vue-toastification'
import ApiService from '../../services/api.js'

export default {
  name: 'MonetizationDetailDrawer',
  props: {
    modelValue: {
      type: Boolean,
      default: false
    },
    monetization: {
      type: Object,
      default: null
    },
    isApprover: {
      type: Boolean,
      default: false
    },
    viewOnly: {
      type: Boolean,
      default: false
    }
  },
  emits: ['update:modelValue', 'close', 'edit'],
  setup() {
    const toast = useToast()
    return { toast }
  },
  computed: {
    visible: {
      get() {
        return this.modelValue
      },
      set(value) {
        this.$emit('update:modelValue', value)
      }
    },
    canEdit() {
      if (this.viewOnly || this.isApprover || !this.monetization) {
        return false
      }
      if (this.monetization.is_fully_approved) {
        return false
      }
      if (this.monetization.disapprove_1 || this.monetization.disapprove_2) {
        return false
      }
      return true
    }
  },
  methods: {
    formatNumber(value, decimals = 2) {
      if (value === null || value === undefined) return '0.000'
      return Number(value).toFixed(decimals)
    },
    formatDate(dateString) {
      if (!dateString) return 'N/A'
      const date = new Date(dateString)
      return date.toLocaleDateString('en-US', {
        month: 'long',
        day: 'numeric',
        year: 'numeric'
      })
    },
    getTypeName(typeId) {
      return typeId === 1 ? 'Regular Monetization' : 'Special Monetization'
    },
    getTypeTagType(typeId) {
      return typeId === 1 ? 'info' : 'warning'
    },
    getStatusText(monetization) {
      if (monetization.approve_1 && monetization.approve_2) return 'Approved'
      if (monetization.disapprove_1 || monetization.disapprove_2) return 'Disapproved'
      return 'Pending'
    },
    getStatusTagType(monetization) {
      if (monetization.approve_1 && monetization.approve_2) return 'success'
      if (monetization.disapprove_1 || monetization.disapprove_2) return 'danger'
      return 'warning'
    },
    async downloadAttachment(attachment) {
      if (attachment.id) {
        try {
          await ApiService.downloadLeaveMonetizationAttachment(attachment.id)
          this.toast.success('Attachment downloaded successfully')
        } catch (error) {
          console.error('Download failed:', error)
          this.toast.error('Failed to download attachment. Please try again.')
        }
      } else {
        this.toast.error('Invalid attachment')
      }
    }
  }
}
</script>
