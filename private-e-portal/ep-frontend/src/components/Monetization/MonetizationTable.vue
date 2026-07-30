<template>
  <div>
    <!-- Delete Confirmation Modal -->
    <div v-if="showDeleteModal" class="fixed inset-0 bg-slate-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
      <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
          <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
            <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
            </svg>
          </div>
          <h3 class="text-lg leading-6 font-medium text-slate-900 mt-4">Delete Confirmation</h3>
          <div class="mt-2 px-7 py-3">
            <p class="text-sm text-slate-500">
              Are you sure you want to delete this leave monetization? This action cannot be undone.
            </p>
          </div>
          <div class="items-center px-4 py-3">
            <button
              @click="confirmDelete"
              class="px-4 py-2 bg-red-500 text-white text-base font-medium rounded-md w-24 mr-2 hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-300"
            >
              Delete
            </button>
            <button
              @click="cancelDelete"
              class="px-4 py-2 bg-slate-500 text-white text-base font-medium rounded-md w-24 hover:bg-slate-600 focus:outline-none focus:ring-2 focus:ring-slate-300"
            >
              Cancel
            </button>
          </div>
        </div>
      </div>
    </div>

    <el-table 
      :data="monetizations" 
      border 
      stripe 
      size="default" 
      class="w-full"
      :cell-style="{ padding: '12px 16px' }"
      :header-cell-style="{ padding: '16px', fontWeight: '600', backgroundColor: '#f8fafc' }"
    >
      <el-table-column v-if="isApprover" label="Name" prop="name" min-width="180" />
      <el-table-column label="VL to Monetize" min-width="160">
        <template #default="{ row }">{{ formatNumber(row.vl_to_monetize || row.vl_credit, 3) }}</template>
      </el-table-column>
      <el-table-column label="SL to Monetize" min-width="160">
        <template #default="{ row }">{{ formatNumber(row.sl_to_monetize || row.sl_credit, 3) }}</template>
      </el-table-column>
      <el-table-column label="Total Days" min-width="140">
        <template #default="{ row }">{{ formatNumber(row.total_days, 3) }}</template>
      </el-table-column>
      <el-table-column label="Amount" min-width="160">
        <template #default="{ row }">₱{{ formatNumber(row.amount, 2) }}</template>
      </el-table-column>
      <el-table-column label="Type" min-width="140">
        <template #default="{ row }">
          <el-tag :type="Number(row.type_id) === 1 ? 'info' : 'warning'" effect="light" size="small">
            {{ Number(row.type_id) === 1 ? 'Regular' : 'Special' }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column label="Actions" min-width="240" align="center">
        <template #default="{ row }">
          <div class="flex items-center justify-center gap-3">
            <!-- View Detail Button (always shown) -->
            <el-button size="small" type="info" plain @click="$emit('view-detail', row)">
              <el-icon><View /></el-icon>
              View
            </el-button>
            
            <!-- Employee Actions (pending only) -->
            <div v-if="!isApprover && !viewOnly" class="flex items-center gap-3">
              <el-button size="small" type="primary" plain @click="$emit('edit', row)">
                <el-icon><Edit /></el-icon>
              </el-button>
              <el-button size="small" type="danger" plain @click="showDeleteConfirmation(row)">
                <el-icon><Delete /></el-icon>
              </el-button>
            </div>
            
            <!-- Approver Actions (pending only) -->
            <div v-else-if="isApprover && !viewOnly" class="flex items-center gap-3">
              <el-button size="small" type="success" plain @click="$emit('approve', row)">
                <el-icon><Check /></el-icon>
              </el-button>
              <el-button size="small" type="warning" plain @click="$emit('disapprove', row)">
                <el-icon><Close /></el-icon>
              </el-button>
            </div>
          </div>
        </template>
      </el-table-column>
    </el-table>
  </div>
</template>

<script>
import { useToast } from 'vue-toastification'
import ApiService from '../../services/api.js'

export default {
  name: 'MonetizationTable',
  props: {
    monetizations: {
      type: Array,
      default: () => []
    },
    isApprover: {
      type: Boolean,
      default: false
    },
    /** When true, only the View action is shown (e.g. Approved / Disapproved tabs). */
    viewOnly: {
      type: Boolean,
      default: false
    }
  },
  setup() {
    const toast = useToast()
    return { toast }
  },
  data() {
    return {
      showDeleteModal: false,
      selectedMonetization: null
    }
  },
  computed: {
    showActions() {
      // Show actions for pending items (not approved and not disapproved)
      return this.monetizations.some(item => {
        const isPending = !item.approve_1 && !item.disapprove_1 && !item.approve_2 && !item.disapprove_2
        return isPending || this.isApprover
      })
    }
  },
  methods: {
    formatNumber(value, decimals = 2) {
      if (value === null || value === undefined) return '0.000'
      return Number(value).toFixed(decimals)
    },
    formatDate(dateString) {
      if (!dateString) return ''
      const date = new Date(dateString)
      return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      })
    },
    async downloadAttachment(attachment) {
      if (attachment.id) {
        try {
          // Use the existing API service method which handles authentication properly
          await ApiService.downloadLeaveMonetizationAttachment(attachment.id)
          this.toast.success('Attachment downloaded successfully')
        } catch (error) {
          console.error('Download failed:', error)
          this.toast.error('Failed to download attachment. Please try again.')
        }
      } else {
        this.toast.error('Invalid attachment')
      }
    },
    showDeleteConfirmation(monetization) {
      this.selectedMonetization = monetization
      this.showDeleteModal = true
    },
    confirmDelete() {
      if (this.selectedMonetization) {
        this.$emit('delete', this.selectedMonetization)
      }
      this.showDeleteModal = false
      this.selectedMonetization = null
    },
    cancelDelete() {
      this.showDeleteModal = false
      this.selectedMonetization = null
    }
  }
}
</script> 