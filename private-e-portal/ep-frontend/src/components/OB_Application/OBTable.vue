<template>
  <div class="overflow-x-auto">
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
              Are you sure you want to delete this official business application? This action cannot be undone.
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
      :data="obApplications" 
      border 
      stripe 
      size="default" 
      class="w-full"
      :cell-style="{ padding: '12px 16px' }"
      :header-cell-style="{ padding: '16px', fontWeight: '600', backgroundColor: '#f8fafc' }"
    >
      <el-table-column v-if="isApprover" label="Name" prop="name" width="200">
        <template #default="{ row }">
          {{ row.name || `${row.last_name}, ${row.first_name}` }}
        </template>
      </el-table-column>
      <el-table-column label="OB Type" width="150">
        <template #default="{ row }">
          <el-tag :type="getOBTypeTagType(row.ob_type)" effect="light" size="small">{{ getOBTypeName(row.ob_type) }}</el-tag>
        </template>
      </el-table-column>
      <el-table-column label="Type" width="200">
        <template #default="{ row }">
          {{ logRowType(row) }}
          <span v-if="parseInt(row.ob_type) === 2 && row.ta_type_name">{{ row.ta_type_name }}</span>
          <span v-else-if="parseInt(row.ob_type) === 3 && row.to_type_name">{{ row.to_type_name }}</span>
          <span v-else class="text-slate-400">-</span>
        </template>
      </el-table-column>
      <el-table-column label="Purpose" prop="purpose" min-width="300" />
      <el-table-column label="Actions" width="280" align="center">
        <template #default="{ row }">
          <div class="flex items-center justify-center gap-3">
            <!-- View Detail Button (non-approver) -->
            <el-button
              v-if="!isApprover"
              size="small"
              type="info"
              plain
              @click="$emit('view-detail', row)"
            >
              <el-icon><View /></el-icon>
              View
            </el-button>
            
            <!-- Print Button (non-approver) -->
            <el-button v-if="!isApprover" size="small" plain @click="$emit('print', row)">
              <el-icon><Printer /></el-icon>
            </el-button>
            
            <!-- Employee Actions -->
            <div v-if="!isApprover" class="flex items-center gap-3">
              <el-button size="small" type="primary" plain @click="$emit('edit', row)">
                <el-icon><Edit /></el-icon>
              </el-button>
              <el-button size="small" type="danger" plain @click="showDeleteConfirmation(row)">
                <el-icon><Delete /></el-icon>
              </el-button>
            </div>
            
            <!-- Approver Actions -->
            <div v-else class="flex items-center justify-center gap-3 flex-wrap">
              <template v-if="allowApproverDecision">
                <el-button size="small" type="success" circle plain @click="$emit('approve', row)">
                  <el-icon><Check /></el-icon>
                </el-button>
                <el-button size="small" type="warning" circle plain @click="$emit('disapprove', row)">
                  <el-icon><Remove /></el-icon>
                </el-button>
              </template>
              <el-button size="small" type="info" circle plain @click="$emit('view-detail', row)">
                <el-icon><View /></el-icon>
              </el-button>
            </div>
          </div>
        </template>
      </el-table-column>
    </el-table>
  </div>
</template>

<script>
import { View, Printer, Edit, Delete, Check, Remove } from '@element-plus/icons-vue'

export default {
  name: 'OBTable',
  components: {
    View,
    Printer,
    Edit,
    Delete,
    Check,
    Remove
  },
  props: {
    obApplications: {
      type: Array,
      default: () => []
    },
    isApprover: {
      type: Boolean,
      default: false
    },
    allowApproverDecision: {
      type: Boolean,
      default: false
    }
  },
  data() {
    return {
      showDeleteModal: false,
      selectedOB: null
    }
  },
  mounted() {},
  watch: {
    obApplications: {
      handler() {},
      deep: true
    }
  },
  computed: {
    showActions() {
      // Always show actions for regular users (non-approvers)
      // For approvers, show actions for pending items
      if (!this.isApprover) {
        return true
      }
      return this.obApplications.some(item => 
        item.approve_1 === false || 
        item.approve_2 === false
      )
    },
    showAttachments() {
      // Show attachments column for pending approvals
      return this.isApprover && this.obApplications.some(item => 
        item.approve_1 === false || 
        item.approve_2 === false
      )
    },
    hasTravelAuthorityType() {
      // Show Travel Authority Type column if any row has ob_type 2 or 3
      return this.obApplications.some(item => item.ob_type === 2 || item.ob_type === 3)
    }
  },
  methods: {
    logRowType(row) {
      return ''
    },
    formatDate(dateString) {
      if (!dateString) return ''
      const date = new Date(dateString)
      return date.toLocaleDateString('en-US', {
        month: '2-digit',
        day: '2-digit',
        year: 'numeric'
      })
    },
    formatDateTime(dateString) {
      if (!dateString) return ''
      const date = new Date(dateString)
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
      // Convert to number to handle both string and integer values
      const type = parseInt(obType)
      switch (type) {
        case 1: return 'Official Business'
        case 2: return 'Travel Authority'
        case 3: return 'Travel Order'
        case 4: return 'Request for Pick-up'
        default: return 'Official Business'
      }
    },
    getOBTypeClass(obType) {
      // Convert to number to handle both string and integer values
      const type = parseInt(obType)
      switch (type) {
        case 1: return 'bg-blue-100 text-blue-800'
        case 2: return 'bg-green-100 text-green-800'
        case 3: return 'bg-purple-100 text-purple-800'
        case 4: return 'bg-orange-100 text-orange-800'
        default: return 'bg-blue-100 text-blue-800'
      }
    },
    getOBTypeTagType(obType) {
      const type = parseInt(obType)
      switch (type) {
        case 1: return 'info'    // Official Business
        case 2: return 'success' // Travel Authority
        case 3: return 'warning' // Travel Order
        case 4: return 'warning' // Request for Pick-up
        default: return 'info'
      }
    },
    canCancel(ob) {
      // Can cancel if the end date hasn't passed
      if (!ob.date_time_to) return false
      const endDate = new Date(ob.date_time_to)
      const now = new Date()
      return endDate > now
    },
    showDeleteConfirmation(ob) {
      this.selectedOB = ob
      this.showDeleteModal = true
    },
    confirmDelete() {
      this.$emit('delete', this.selectedOB)
      this.showDeleteModal = false
      this.selectedOB = null
    },
    cancelDelete() {
      this.showDeleteModal = false
      this.selectedOB = null
    }
  }
}
</script> 