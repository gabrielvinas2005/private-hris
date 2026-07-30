<template>
  <el-table
    :data="overtimeRecords"
    size="small"
    border
    stripe
    @selection-change="onSelectionChange"
  >
    <el-table-column
      v-if="enableSelection"
      type="selection"
      width="45"
    />
    <el-table-column label="Date Filed" min-width="120">
      <template #default="{ row }">
        {{ formatDate(row.created_at) }}
      </template>
    </el-table-column>
    <el-table-column label="Overtime Date" min-width="120">
      <template #default="{ row }">
        {{ formatDate(row.date) }}
      </template>
    </el-table-column>
    <el-table-column label="Time From" min-width="110">
      <template #default="{ row }">
        {{ formatTime(row.date_time_from) }}
      </template>
    </el-table-column>
    <el-table-column label="Time To" min-width="110">
      <template #default="{ row }">
        {{ formatTime(row.date_time_to) }}
      </template>
    </el-table-column>
    <el-table-column prop="total_hours" label="Hours" min-width="80" />
    <el-table-column prop="remarks" label="Remarks" min-width="160" show-overflow-tooltip />
    <el-table-column prop="overtime_type_name" label="OT Type" min-width="120" />
    <el-table-column label="Approvers" min-width="260">
      <template #default="{ row }">
        <div class="text-xs text-slate-700 space-y-0.5">
          <div v-if="row.approver_1" class="flex items-center gap-1.5 flex-wrap">
            <span class="font-semibold">Level 1:</span>
            <span>{{ row.approver_1 }}</span>
            <span :class="approverStatusClass(row, 1)" class="shrink-0">
              {{ getApproverStatus(row, 1) }}
            </span>
          </div>
          <div v-if="row.has_approver_level_2 && row.approver_2" class="flex items-center gap-1.5 flex-wrap">
            <span class="font-semibold">Level 2:</span>
            <span>{{ row.approver_2 }}</span>
            <span :class="approverStatusClass(row, 2)" class="shrink-0">
              {{ getApproverStatus(row, 2) }}
            </span>
          </div>
          <div v-if="row.has_approver_level_3 && row.approver_3" class="flex items-center gap-1.5 flex-wrap">
            <span class="font-semibold">Level 3:</span>
            <span>{{ row.approver_3 }}</span>
            <span :class="approverStatusClass(row, 3)" class="shrink-0">
              {{ getApproverStatus(row, 3) }}
            </span>
          </div>
          <div
            v-if="!row.approver_1 && !row.has_approver_level_2 && !row.has_approver_level_3"
            class="text-slate-400"
          >
            No approver setup
          </div>
        </div>
      </template>
    </el-table-column>
    <el-table-column label="Actions" width="220" align="center">
      <template #default="{ row }">
        <div class="flex items-center justify-center gap-2">
          <el-button
            v-if="!isApprover"
            size="small"
            type="primary"
            plain
            @click="$emit('edit-overtime', row)"
            class="flex items-center gap-1"
          >
            <el-icon><Edit /></el-icon>
            Edit
          </el-button>
          <el-button
            v-if="!isApprover && !isFullyApprovedRow(row)"
            size="small"
            type="warning"
            plain
            @click="$emit('cancel-overtime', row)"
            class="flex items-center gap-1"
          >
            <el-icon><Close /></el-icon>
            Cancel
          </el-button>
          <el-button
            v-if="!isApprover && isFullyApprovedRow(row)"
            size="small"
            type="danger"
            plain
            @click="$emit('delete-overtime', row)"
            class="flex items-center gap-1"
          >
            <el-icon><Delete /></el-icon>
            Delete
          </el-button>
          <el-button
            v-if="isApprover"
            size="small"
            type="info"
            plain
            @click="$emit('view-overtime', row)"
            class="flex items-center gap-1"
          >
            <el-icon><View /></el-icon>
            View
          </el-button>
        </div>
      </template>
    </el-table-column>
  </el-table>
</template>

<script>
export default {
  name: 'OvertimeTable',
  props: {
    overtimeRecords: {
      type: Array,
      default: () => []
    },
    enableSelection: {
      type: Boolean,
      default: false
    },
    isApprover: {
      type: Boolean,
      default: false
    }
  },
  methods: {
    isFullyApprovedRow(row) {
      if (!row) return false
      const approved = !!row.approved
      const disapproved = !!row.disapproved
      const disapproved2 = !!row.disapproved_2
      const disapproved3 = !!row.disapproved_3
      const approved2 = !!row.approved_2
      const approved3 = !!row.approved_3
      return approved && !disapproved && !disapproved2 && !disapproved3 &&
        (approved2 || (!approved2 && !disapproved2)) &&
        (approved3 || (!approved3 && !disapproved3))
    },
    getApproverStatus(row, level) {
      if (row.is_cancel) return 'Cancelled'
      if (level === 1) {
        if (row.approved) return 'Approved'
        if (row.disapproved) return 'Disapproved'
        return 'Pending'
      }
      if (level === 2) {
        if (row.approved_2) return 'Approved'
        if (row.disapproved_2) return 'Disapproved'
        return 'Pending'
      }
      if (level === 3) {
        if (row.approved_3) return 'Approved'
        if (row.disapproved_3) return 'Disapproved'
        return 'Pending'
      }
      return 'Pending'
    },
    approverStatusClass(row, level) {
      const status = this.getApproverStatus(row, level)
      const base = 'text-xs font-medium px-1.5 py-0.5 rounded'
      if (status === 'Approved') return `${base} bg-green-100 text-green-800`
      if (status === 'Disapproved') return `${base} bg-red-100 text-red-800`
      if (status === 'Cancelled') return `${base} bg-slate-200 text-slate-600`
      return `${base} bg-amber-50 text-amber-800`
    },
    onSelectionChange(selection) {
      this.$emit('selection-change', selection)
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
    formatTime(timeString) {
      if (!timeString) return ''
      
      try {
        // Handle the specific format from backend: "Y/m/d H:i:s" (e.g., "2025/08/29 18:00:00")
        if (timeString.includes(' ') && timeString.includes('/')) {
          // Extract time part from datetime string
          const timeMatch = timeString.match(/(\d{1,2}):(\d{2}):(\d{2})$/)
          if (timeMatch) {
            const hours = parseInt(timeMatch[1])
            const minutes = timeMatch[2]
            const ampm = hours >= 12 ? 'PM' : 'AM'
            const displayHours = hours > 12 ? hours - 12 : (hours === 0 ? 12 : hours)
            return `${displayHours}:${minutes} ${ampm}`
          }
        }
        
        // Handle other datetime formats
        if (timeString.includes(' ') || timeString.includes('T')) {
          const date = new Date(timeString)
          if (!isNaN(date.getTime())) {
            return date.toLocaleTimeString('en-US', {
              hour: 'numeric',
              minute: '2-digit',
              hour12: true
            })
          }
        }
        
        // Handle time-only strings (HH:MM:SS)
        const timeMatch = timeString.match(/^(\d{1,2}):(\d{2}):(\d{2})$/)
        if (timeMatch) {
          const hours = parseInt(timeMatch[1])
          const minutes = timeMatch[2]
          const ampm = hours >= 12 ? 'PM' : 'AM'
          const displayHours = hours > 12 ? hours - 12 : (hours === 0 ? 12 : hours)
          return `${displayHours}:${minutes} ${ampm}`
        }
        
        // If all else fails, return the original string
        return timeString
      } catch (error) {
        console.error('Error formatting time:', error, timeString)
        return timeString
      }
    }
  }
}
</script> 