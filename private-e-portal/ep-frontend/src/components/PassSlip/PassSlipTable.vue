<template>
  <div>
    <!-- Delete Confirmation Modal -->
    <el-dialog :model-value="showDeleteModal" title="Delete Confirmation" width="420px" @close="cancelDelete">
      <el-alert
        type="warning"
        :closable="false"
        show-icon
        class="mb-3"
        title="Are you sure you want to delete this pass slip? This action cannot be undone."
      />
      <template #footer>
        <el-button @click="cancelDelete">Cancel</el-button>
        <el-button type="danger" @click="confirmDelete">Delete</el-button>
      </template>
    </el-dialog>

    <div class="overflow-x-auto">
      <el-table :data="passSlips" size="default" border stripe>
        <!-- Employee column - only for approvers viewing others' pass slips -->
        <el-table-column v-if="isApprover || isApprovalSection" label="Profile" width="70" align="center">
          <template #default>
            <div class="w-9 h-9 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center mx-auto">
              <el-icon class="text-slate-500">
                <UserFilled />
              </el-icon>
            </div>
          </template>
        </el-table-column>
        <el-table-column v-if="isApprover || isApprovalSection" prop="employee_name" label="Employee" min-width="140" />
        
        <!-- Date -->
        <el-table-column prop="date" label="Date" width="120">
          <template #default="{ row }">
            {{ formatDate(row.date) }}
          </template>
        </el-table-column>
        
        <!-- Time Out -->
        <el-table-column prop="time_out" label="Time Out" width="100" />
        
        <!-- Time In -->
        <el-table-column prop="time_in" label="Time In" width="100" />
        
        <!-- Destination -->
        <el-table-column prop="destination" label="Destination" min-width="150" show-overflow-tooltip />
        
        <!-- Purpose -->
        <el-table-column prop="purpose" label="Purpose" min-width="180" show-overflow-tooltip />
        
        <!-- Approver Info - only show when viewing approved/disapproved pass slips -->
        <el-table-column v-if="showApproverInfo" label="Approved By" min-width="150">
          <template #default="{ row }">
            {{ row.approved_by_name || '-' }}
          </template>
        </el-table-column>
        <el-table-column v-if="showApproverInfo" label="Division Chief" min-width="150">
          <template #default="{ row }">
            {{ row.division_chief || '-' }}
          </template>
        </el-table-column>
        
        <!-- Status -->
        <el-table-column label="Status" width="120" align="center">
          <template #default="{ row }">
            <el-tag :type="statusTagType(row.status)" effect="light">
              {{ statusText(row.status) }}
            </el-tag>
          </template>
        </el-table-column>
        
        <!-- Actions -->
        <el-table-column label="Actions" min-width="240" align="center" fixed="right">
          <template #default="{ row }">
            <div class="flex items-center justify-center gap-3 flex-wrap">
              <!-- Actions for Approval Section -->
              <template v-if="isApprovalSection">
                <el-button v-if="canApprove(row)" size="small" type="success" circle plain @click="$emit('approve', row)">
                  <el-icon><Check /></el-icon>
                </el-button>
                <el-button v-if="canDisapprove(row)" size="small" type="warning" circle plain @click="$emit('disapprove', row)">
                  <el-icon><Remove /></el-icon>
                </el-button>
                <el-button size="small" type="primary" circle plain @click="$emit('print', row)">
                  <el-icon><Printer /></el-icon>
                </el-button>
              </template>
              <!-- Actions for Regular Pass Slip List -->
              <template v-else>
                <el-button v-if="canEdit(row)" size="small" type="success" circle plain @click="$emit('edit', row)">
                  <el-icon><Edit /></el-icon>
                </el-button>
                <el-button v-if="canDelete(row)" size="small" type="danger" circle plain @click="showDeleteConfirmation(row)">
                  <el-icon><Delete /></el-icon>
                </el-button>
                <el-button size="small" type="primary" circle plain @click="$emit('print', row)">
                  <el-icon><Printer /></el-icon>
                </el-button>
              </template>
            </div>
          </template>
        </el-table-column>
      </el-table>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { UserFilled, Check, Remove, Edit, Delete, Printer } from '@element-plus/icons-vue'

const props = defineProps({
  passSlips: {
    type: Array,
    default: () => []
  },
  isApprover: {
    type: Boolean,
    default: false
  },
  isApprovalSection: {
    type: Boolean,
    default: false
  },
  showApproverInfo: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['edit', 'delete', 'approve', 'disapprove', 'print'])

// Delete confirmation
const showDeleteModal = ref(false)
const passSlipToDelete = ref(null)

const showDeleteConfirmation = (passSlip) => {
  passSlipToDelete.value = passSlip
  showDeleteModal.value = true
}

const confirmDelete = () => {
  if (passSlipToDelete.value?.id) {
    emit('delete', passSlipToDelete.value.id)
  }
  showDeleteModal.value = false
  passSlipToDelete.value = null
}

const cancelDelete = () => {
  showDeleteModal.value = false
  passSlipToDelete.value = null
}

// Format date
const formatDate = (date) => {
  if (!date) return '-'
  const d = new Date(date)
  return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}

// Status helpers
const statusTagType = (status) => {
  const types = {
    pending: 'warning',
    approved: 'success',
    disapproved: 'danger'
  }
  return types[status] || 'info'
}

const statusText = (status) => {
  const texts = {
    pending: 'Pending',
    approved: 'Approved',
    disapproved: 'Disapproved'
  }
  return texts[status] || status
}

// Permission helpers
const canEdit = (passSlip) => {
  return passSlip.status === 'pending'
}

const canDelete = (passSlip) => {
  return passSlip.status !== 'approved'
}

const canApprove = (passSlip) => {
  return passSlip.status === 'pending'
}

const canDisapprove = (passSlip) => {
  return passSlip.status === 'pending'
}
</script>
