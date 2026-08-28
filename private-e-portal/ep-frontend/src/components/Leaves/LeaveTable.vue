<template>
  <div>
    <!-- Delete Confirmation Modal -->
    <el-dialog :model-value="showDeleteModal" title="Delete Confirmation" width="420px" @close="cancelDelete">
      <el-alert
        type="warning"
        :closable="true"
        show-icon
        class="mb-3"
        title="Are you sure you want to delete this leave application? This action cannot be undone."
      />
      <template #footer>
        <el-button @click="cancelDelete">Cancel</el-button>
        <el-button type="danger" @click="confirmDelete">Delete</el-button>
      </template>
    </el-dialog>

  <div class="overflow-x-auto">
      <el-table :data="leaves" size="default" border stripe>
        <el-table-column v-if="isApprover" label="Profile" width="70" align="center">
          <template #default>
            <div class="w-9 h-9 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center mx-auto">
              <el-icon class="text-slate-500">
                <UserFilled />
              </el-icon>
            </div>
          </template>
        </el-table-column>
        <el-table-column v-if="isApprover" prop="name" label="Employee" min-width="140" />
        <el-table-column prop="leave_type" label="Leave" min-width="120" />
        <el-table-column v-if="isApprover" prop="balance" label="Balance" width="90" />
        <el-table-column label="Day Type" width="110">
          <template #default="{ row }">
            <span>{{ getDayTypeDisplay(row) }}</span>
          </template>
        </el-table-column>
        <el-table-column label="Date Filed" width="130">
          <template #default="{ row }">
            <span>{{ formatDate(row.created_at) }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="date_covered" label="Date" min-width="150" />
        <el-table-column label="Reason" min-width="180" show-overflow-tooltip>
          <template #default="{ row }">
            <span :title="getReasonDisplay(row)">{{ getReasonDisplay(row) }}</span>
          </template>
        </el-table-column>
        <el-table-column v-if="showApproverInfo" min-width="180">
          <template #header>
            {{ getApproverColumnLabel() }}
          </template>
          <template #default="{ row }">
            <div v-if="row.approver_1" class="mb-1"><span class="font-semibold">Level 1:</span> {{ row.approver_1 }}</div>
            <div v-if="row.approver_2" class="mb-1"><span class="font-semibold">Level 2:</span> {{ row.approver_2 }}</div>
            <div v-if="row.approver_3"><span class="font-semibold">Level 3:</span> {{ row.approver_3 }}</div>
          </template>
        </el-table-column>
        <el-table-column v-if="showApproverInfo" min-width="180">
          <template #header>
            {{ getDateColumnLabel() }}
          </template>
          <template #default="{ row }">
            <div v-if="row.processed_date" class="mb-1"><span class="font-semibold">Level 1:</span> {{ formatDate(row.processed_date) }}</div>
            <div v-if="row.processed_date_2" class="mb-1"><span class="font-semibold">Level 2:</span> {{ formatDate(row.processed_date_2) }}</div>
            <div v-if="row.processed_date_3"><span class="font-semibold">Level 3:</span> {{ formatDate(row.processed_date_3) }}</div>
          </template>
        </el-table-column>
        <el-table-column v-if="showCancelledInfo" label="Cancelled By" min-width="160">
          <template #default="{ row }">
            <div v-if="row.cancelled_by_name" class="mb-1"><span class="font-semibold">Level 1:</span> {{ row.cancelled_by_name }}</div>
            <div v-if="row.cancelled_by_name_2" class="mb-1"><span class="font-semibold">Level 2:</span> {{ row.cancelled_by_name_2 }}</div>
            <div v-if="row.cancelled_by_name_3"><span class="font-semibold">Level 3:</span> {{ row.cancelled_by_name_3 }}</div>
          </template>
        </el-table-column>
        <el-table-column v-if="showCancelledInfo" label="Date Cancelled" min-width="180">
          <template #default="{ row }">
            <div v-if="row.canceled_date" class="mb-1"><span class="font-semibold">Level 1:</span> {{ formatDate(row.canceled_date) }}</div>
            <div v-if="row.canceled_date_2" class="mb-1"><span class="font-semibold">Level 2:</span> {{ formatDate(row.canceled_date_2) }}</div>
            <div v-if="row.canceled_date_3"><span class="font-semibold">Level 3:</span> {{ formatDate(row.canceled_date_3) }}</div>
          </template>
        </el-table-column>
        <el-table-column v-if="showCancelledInfo" label="Cancelled Remarks" min-width="200">
          <template #default="{ row }">
            <div v-if="row.canceled_remarks" class="mb-1"><span class="font-semibold">Level 1:</span> {{ row.canceled_remarks }}</div>
            <div v-if="row.canceled_remarks_2" class="mb-1"><span class="font-semibold">Level 2:</span> {{ row.canceled_remarks_2 }}</div>
            <div v-if="row.canceled_remarks_3"><span class="font-semibold">Level 3:</span> {{ row.canceled_remarks_3 }}</div>
          </template>
        </el-table-column>
        <el-table-column label="Remarks" min-width="240" show-overflow-tooltip>
          <template #default="{ row }">
            <template v-if="isLeaveCancelled(row)">
              <span>{{ row.canceled_remarks || '' }}</span>
            </template>
            <template v-else>
              <div class="mb-1"><span class="font-semibold">Level 1:</span> {{ getLevelRemark(row, 1) }}</div>
              <div class="mb-1"><span class="font-semibold">Level 2:</span> {{ getLevelRemark(row, 2) }}</div>
              <div><span class="font-semibold">Level 3:</span> {{ getLevelRemark(row, 3) }}</div>
            </template>
          </template>
        </el-table-column>
        <el-table-column label="Actions" min-width="320" align="center" fixed="right">
          <template #default="{ row }">
            <div class="flex items-center justify-center gap-3 flex-wrap">
            <!-- Actions for Approval Section (Approve, Disapprove, Print only) -->
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
            <!-- Actions for Regular Leave List (no self-approval actions) -->
            <template v-else>
              <el-button v-if="canEdit(row)" size="small" type="success" circle plain @click="$emit('edit', row)">
                <el-icon><Edit /></el-icon>
              </el-button>
              <el-button v-if="canDelete(row)" size="small" type="danger" circle plain @click="showDeleteConfirmation(row)">
                <el-icon><Delete /></el-icon>
              </el-button>
              <el-button v-if="canCancel(row)" size="small" type="danger" plain @click="$emit('cancel', row)">Cancel</el-button>
              <el-button v-if="isApprover" size="small" type="primary" circle plain @click="$emit('details', row)">
                <el-icon><View /></el-icon>
              </el-button>
              <el-button size="small" type="primary" circle plain @click="$emit('print', row)">
                <el-icon><Printer /></el-icon>
              </el-button>
              <el-button v-if="showCancelledInfo && row.attachment_name" size="small" type="success" plain @click="$emit('download', row)">Download</el-button>
            </template>
            </div>
          </template>
        </el-table-column>
      </el-table>
            </div>

    <!-- Print Modal -->
    <PrintModal
      :show="isPrintModalVisible"
      :leave="selectedPrintLeave"
      @close="closePrintModal"
    />

  <!-- Edit Modal -->
  <el-dialog :model-value="showEditModal" title="Edit Leave" width="640px" @close="showEditModal = false">
    <el-form label-position="top">
      <el-form-item label="Reason">
        <el-input v-model="editLeaveData.reason" type="textarea" :rows="4" />
      </el-form-item>
      <el-form-item label="Day Type">
        <el-select v-model="editLeaveData.day_type" disabled>
          <el-option label="Whole Day" value="Whole Day" />
          <el-option label="Half Day" value="Half Day" />
        </el-select>
      </el-form-item>
      <el-form-item label="Date">
        <el-input :model-value="editLeaveData.date_covered" disabled />
      </el-form-item>
    </el-form>
    <template #footer>
      <el-button @click="showEditModal = false">Cancel</el-button>
      <el-button type="primary" @click="$emit('edit', editLeaveData); showEditModal = false">Save</el-button>
    </template>
  </el-dialog>
  </div>
</template>

<script>
import { 
  UserFilled, 
  Delete, 
  Edit, 
  Check, 
  Remove, 
  View, 
  Printer 
} from '@element-plus/icons-vue'
import PrintModal from './PrintModal.vue'

export default {
  name: 'LeaveTable',
  components: {
    PrintModal,
    UserFilled,
    Delete,
    Edit,
    Check,
    Remove,
    View,
    Printer
  },
  props: {
    leaves: {
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
    statusFilter: {
      type: String,
      default: ''
    }
  },

  data() {
    return {
      showDeleteModal: false,
      selectedLeave: null,
      isPrintModalVisible: false,
      selectedPrintLeave: null
    }
  },
  computed: {
    showApproverInfo() {
      // Always show the "Approved By" / "Processed By" columns
      // for every leave type in all sections.
      return true
    },
    showCancelledInfo() {
      // Show cancelled info for cancelled leaves - only if explicitly cancelled
      return this.leaves.some(leave => 
        leave.is_cancel === true || leave.is_cancel === 1 || 
        leave.is_cancel_2 === true || leave.is_cancel_2 === 1 ||
        leave.is_cancel_3 === true || leave.is_cancel_3 === 1
      )
    }
  },
  methods: {
    getDayTypeDisplay(leave) {
      const otherPurposeId = String(leave.other_purpose_id || '')
      if (otherPurposeId === '1' || otherPurposeId === '2') return ''
      return String(leave.day_type || '')
    },
    getReasonDisplay(leave) {
      const leaveTypeId = String(leave.leave_type_id || '')
      const rawReason = String(leave.reason || '').trim()
      const otherPurposeId = String(leave.other_purpose_id || '')
      const monetizationAmount = String(leave.monetization_amount || '').trim()
      const vacationId = String(leave.incase_vacation_leave_id || '')
      const sickId = String(leave.incase_sick_leave_id || '')
      const vacationSpecify = String(leave.incase_vacation_leave_specify || '').trim()
      const sickSpecify = String(leave.incase_sick_leave_specify || '').trim()
      const specialWomenSpecify = String(leave.incase_special_leave_specify || '').trim()
      const studyId = String(leave.incase_study_leave_id || '')

      const withOptionalSpecify = (label, specify) => (specify ? `${label} (${specify})` : label)
      const derivedReason =
        (vacationId === '1' && withOptionalSpecify('Within the Philippines', vacationSpecify)) ||
        (vacationId === '2' && withOptionalSpecify('Abroad', vacationSpecify)) ||
        (sickId === '1' && withOptionalSpecify('In Hospital', sickSpecify)) ||
        (sickId === '2' && withOptionalSpecify('Out Patient', sickSpecify)) ||
        (studyId === '1' && "Completion of Master's Degree") ||
        (studyId === '2' && 'BAR/Board Examination Review') ||
        specialWomenSpecify

      if (otherPurposeId === '1') {
        return withOptionalSpecify('Monetization of Leave Credits', monetizationAmount)
      }
      if (otherPurposeId === '2') {
        return 'Terminal Leave'
      }

      if (leaveTypeId === '1' || leaveTypeId === '3') {
        if (vacationId === '1') return withOptionalSpecify('Within the Philippines', vacationSpecify)
        if (vacationId === '2') return withOptionalSpecify('Abroad', vacationSpecify)
        return rawReason
      }

      if (leaveTypeId === '2') {
        if (sickId === '1') return withOptionalSpecify('In Hospital', sickSpecify)
        if (sickId === '2') return withOptionalSpecify('Out Patient', sickSpecify)
        return rawReason
      }

      if (leaveTypeId === '25') {
        return specialWomenSpecify
      }

      if (leaveTypeId === '5') {
        if (studyId === '1') return "Completion of Master's Degree"
        if (studyId === '2') return 'BAR/Board Examination Review'
        return rawReason
      }

      if (leaveTypeId === '13') {
        return derivedReason || rawReason
      }

      return rawReason
    },
    getLevelRemark(leave, level) {
      const approvedKeys = {
        1: 'approved_remarks',
        2: 'approved_2_remarks',
        3: 'approved_3_remarks'
      }
      const disapprovedKeys = {
        1: 'disapproved_remarks',
        2: 'disapproved_2_remarks',
        3: 'disapproved_3_remarks'
      }
      const approvedFlagKeys = {
        1: 'approved',
        2: 'approved_2',
        3: 'approved_3'
      }
      const disapprovedFlagKeys = {
        1: 'disapproved',
        2: 'disapproved_2',
        3: 'disapproved_3'
      }

      const approvedRemark = String(leave[approvedKeys[level]] || '').trim()
      const disapprovedRemark = String(leave[disapprovedKeys[level]] || '').trim()
      const isApproved = this.toBool(leave[approvedFlagKeys[level]])
      const isDisapproved = this.toBool(leave[disapprovedFlagKeys[level]])

      if (isDisapproved || disapprovedRemark) return disapprovedRemark || '-'
      if (isApproved || approvedRemark) return approvedRemark || '-'
      return ''
    },
    statusText(leave) {
      if (this.isApprovalSection && leave.approver_level_id) {
        return this.levelStatusText(leave)
      }
      return this.overallStatusText(leave)
    },
    statusTagType(leave) {
      const s = this.statusText(leave)
      if (s === 'Approved') return 'success'
      if (s === 'Disapproved') return 'warning'
      if (s === 'Cancelled') return 'danger'
      if (s === 'Expired') return 'warning'
      return 'info'
    },
    getColumnCount() {
      let count = 4 // Leave, Day Type, Date, Reason
      if (this.isApprover) count += 2 // Photo, Employee
      if (this.isApprover) count += 1 // Balance
      if (this.showApproverInfo) count += 2 // Approved By, Date Approved
      if (this.showCancelledInfo) count += 3 // Cancelled By, Date Cancelled, Cancelled Remarks
      count += 1 // Actions
      return count
    },
    formatDate(dateString) {
      if (!dateString) return ''
      const date = new Date(dateString)
      return date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric'
      })
    },
    canEdit(leave) {
      if (this.overallStatusText(leave) !== 'Pending') return false
      // For normal employees, once any approver level has already approved,
      // the application should no longer be editable.
      if (!this.isApprover) {
        const hasAnyApproval = this.toBool(leave.approved) || this.toBool(leave.approved_2) || this.toBool(leave.approved_3)
        if (hasAnyApproval) return false
      }
      return true
    },
    canDelete(leave) {
      if (this.isLeaveExpired(leave)) return false
      if (!this.isApprover) {
        const hasAnyApproval = this.toBool(leave.approved) || this.toBool(leave.approved_2) || this.toBool(leave.approved_3)
        if (hasAnyApproval) return false
      }
      const isCancelled = leave.is_cancel === true || leave.is_cancel === 1 || 
                          leave.is_cancel_2 === true || leave.is_cancel_2 === 1 ||
                          leave.is_cancel_3 === true || leave.is_cancel_3 === 1
      const isApproved = leave.approved === true || leave.approved === 1 ||
                         leave.approved_2 === true || leave.approved_2 === 1 ||
                         leave.approved_3 === true || leave.approved_3 === 1
      const isDisapproved = leave.disapproved === true || leave.disapproved === 1 ||
                             leave.disapproved_2 === true || leave.disapproved_2 === 1 ||
                             leave.disapproved_3 === true || leave.disapproved_3 === 1
      return !isApproved && !isDisapproved && !isCancelled
    },
    canApprove(leave) {
      if (!this.isApprover) return false
      if (this.isLeaveExpired(leave)) return false
      if (this.isLeaveCancelled(leave)) return false
      if (this.isApprovalSection && leave.approver_level_id) {
        const level = Number(leave.approver_level_id) || 1
        return !this.isLevelApproved(leave, level) && !this.isLevelDisapproved(leave, level)
      }
      return !this.isFullyApproved(leave) && !this.isFullyDisapproved(leave)
    },
    canDisapprove(leave) {
      if (!this.isApprover) return false
      if (this.isLeaveExpired(leave)) return false
      if (this.isLeaveCancelled(leave)) return false
      if (this.isApprovalSection && leave.approver_level_id) {
        const level = Number(leave.approver_level_id) || 1
        return !this.isLevelApproved(leave, level) && !this.isLevelDisapproved(leave, level)
      }
      return !this.isFullyApproved(leave) && !this.isFullyDisapproved(leave)
    },
    canCancel(leave) {
      const currentFilter = String(this.statusFilter || '').toLowerCase()
      if (['disapproved', 'cancelled', 'expired'].includes(currentFilter)) return false
      if (!this.isApprover) {
        const hasAnyApproval = this.toBool(leave.approved) || this.toBool(leave.approved_2) || this.toBool(leave.approved_3)
        // Applicant side: before any approval, only Delete should be available.
        if (!hasAnyApproval) return false
      }

      // For approved tab/filter: non-Sick Leave can only be cancelled
      // before the leave's end date. Sick Leave (id=2) is exempt.
      if (currentFilter === 'approved' && String(leave.leave_type_id || '') !== '2') {
        if (!leave.date_to) return false
        const today = new Date()
        today.setHours(0, 0, 0, 0)

        const s = String(leave.date_to)
        let dateTo
        if (/^\d{4}-\d{2}-\d{2}$/.test(s)) {
          dateTo = new Date(s + 'T00:00:00')
        } else {
          dateTo = new Date(leave.date_to)
        }
        if (Number.isNaN(dateTo.getTime())) return false

        // Show cancel only while current date is still before date_to.
        if (today >= dateTo) return false
      }

      if (this.isLeaveExpired(leave)) return false
      // Do not show Cancel button if leave is already cancelled
      if (this.isLeaveCancelled(leave)) {
        return false
      }

      // Allow cancel for all leave types while pending or partially approved,
      // regardless of whether the leave dates are in the past, present, or future.
      return true
    },
    showDeleteConfirmation(leave) {
      this.selectedLeave = leave
      this.showDeleteModal = true
    },
    confirmDelete() {
      if (this.selectedLeave) {
        this.$emit('delete', this.selectedLeave)
      }
      this.showDeleteModal = false
      this.selectedLeave = null
    },
    cancelDelete() {
      this.showDeleteModal = false
      this.selectedLeave = null
    },
    closePrintModal() {
      this.isPrintModalVisible = false
      this.selectedPrintLeave = null
    },
    toBool(val) {
      return val === true || val === 1 || val === '1' || val === 'true'
    },
    isLeaveExpired(leave) {
      // Expired = not fully approved, date_from already passed, and not cancelled/disapproved.
      // Exempt: Sick Leave (leave_types.id = 2).
      if (String(leave.leave_type_id) === '2') return false
      if (!leave.date_from) return false

      const today = new Date()
      today.setHours(0, 0, 0, 0)

      const s = String(leave.date_from)
      let dateFrom
      if (/^\d{4}-\d{2}-\d{2}$/.test(s)) {
        dateFrom = new Date(s + 'T00:00:00')
      } else {
        dateFrom = new Date(leave.date_from)
      }
      if (!dateFrom || isNaN(dateFrom.getTime())) return false
      dateFrom.setHours(0, 0, 0, 0)

      if (dateFrom >= today) return false
      if (this.isLeaveCancelled(leave)) return false
      if (this.isFullyDisapproved(leave)) return false
      if (this.isFullyApproved(leave)) return false

      return true
    },
    isLeaveCancelled(leave) {
      return this.toBool(leave.is_cancel) || this.toBool(leave.is_cancel_2) || this.toBool(leave.is_cancel_3)
    },
    hasLevel(leave, level) {
      // Check configuration flags sent from backend, not approver names
      // Note: System only supports 3 approval levels in leave_headers table
      if (level === 3) return this.toBool(leave.has_approver_level_3)
      if (level === 2) return this.toBool(leave.has_approver_level_2)
      return true // Level 1 always exists
    },
    getHighestRequiredLevel(leave) {
      // Returns the actual highest level that has an approver configured
      // System only tracks up to 3 approval levels
      if (this.hasLevel(leave, 3)) return 3
      if (this.hasLevel(leave, 2)) return 2
      return 1
    },
    isLevelApproved(leave, level) {
      if (level === 3) return this.toBool(leave.approved_3)
      if (level === 2) return this.toBool(leave.approved_2)
      return this.toBool(leave.approved)
    },
    isLevelDisapproved(leave, level) {
      if (level === 3) return this.toBool(leave.disapproved_3)
      if (level === 2) return this.toBool(leave.disapproved_2)
      return this.toBool(leave.disapproved)
    },
    isFullyApproved(leave) {
      // A leave is fully approved only when ALL required levels have approved
      const highestLevel = this.getHighestRequiredLevel(leave)
      
      // Check if ALL levels up to the highest required have approved
      for (let level = 1; level <= highestLevel; level++) {
        if (!this.isLevelApproved(leave, level)) {
          return false
        }
      }
      return true
    },
    isFullyDisapproved(leave) {
      return this.toBool(leave.disapproved) || this.toBool(leave.disapproved_2) || this.toBool(leave.disapproved_3)
    },
    overallStatusText(leave) {
      if (this.isLeaveCancelled(leave)) return 'Cancelled'
      if (this.isLeaveExpired(leave)) return 'Expired'
      if (this.isFullyDisapproved(leave)) return 'Disapproved'
      if (this.isFullyApproved(leave)) return 'Approved'
      return 'Pending'
    },
    levelStatusText(leave) {
      const level = Number(leave.approver_level_id) || 1
      if (this.isLeaveCancelled(leave)) return 'Cancelled'
      if (this.isLeaveExpired(leave)) return 'Expired'
      if (this.isLevelDisapproved(leave, level)) return 'Disapproved'
      if (this.isLevelApproved(leave, level)) return 'Approved'
      return 'Pending'
    },
    getApproverColumnLabel() {
      // Check if any leaves in the list are disapproved
      const hasDisapproved = this.leaves.some(leave => 
        this.toBool(leave.disapproved) || 
        this.toBool(leave.disapproved_2) || 
        this.toBool(leave.disapproved_3)
      )
      
      // Check if any leaves are approved
      const hasApproved = this.leaves.some(leave => 
        this.toBool(leave.approved) || 
        this.toBool(leave.approved_2) || 
        this.toBool(leave.approved_3)
      )
      
      if (hasDisapproved && !hasApproved) return 'Disapproved By'
      if (hasApproved && !hasDisapproved) return 'Approved By'
      if (hasApproved && hasDisapproved) return 'Processed By'
      return 'Processed By'
    },
    getDateColumnLabel() {
      // Check if any leaves in the list are disapproved
      const hasDisapproved = this.leaves.some(leave => 
        this.toBool(leave.disapproved) || 
        this.toBool(leave.disapproved_2) || 
        this.toBool(leave.disapproved_3)
      )
      
      // Check if any leaves are approved
      const hasApproved = this.leaves.some(leave => 
        this.toBool(leave.approved) || 
        this.toBool(leave.approved_2) || 
        this.toBool(leave.approved_3)
      )
      
      if (hasDisapproved && !hasApproved) return 'Date Disapproved'
      if (hasApproved && !hasDisapproved) return 'Date Approved'
      if (hasApproved && hasDisapproved) return 'Date Processed'
      return 'Date Processed'
    }
  }
}
</script> 