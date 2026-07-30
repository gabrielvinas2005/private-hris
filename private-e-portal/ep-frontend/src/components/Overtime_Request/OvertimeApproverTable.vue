<template>
  <el-table :data="overtimeRecords" size="small" border stripe>
    <el-table-column prop="employee_name" label="Name" min-width="160" />
    <el-table-column label="Date Filed" min-width="120">
      <template #default="{ row }">{{ formatDate(row.created_at) }}</template>
    </el-table-column>
    <el-table-column label="Overtime Date" min-width="120">
      <template #default="{ row }">{{ formatDate(row.date) }}</template>
    </el-table-column>
    <el-table-column label="Time From" min-width="110">
      <template #default="{ row }">{{ formatTime(row.date_time_from) }}</template>
    </el-table-column>
    <el-table-column label="Time To" min-width="110">
      <template #default="{ row }">{{ formatTime(row.date_time_to) }}</template>
    </el-table-column>
    <el-table-column prop="total_hours" label="Hours" min-width="80" />
    <el-table-column prop="remarks" label="Remarks" min-width="180" show-overflow-tooltip />
    <el-table-column prop="overtime_type_name" label="OT Type" min-width="120" />
    <el-table-column label="Attachment" min-width="180" align="center">
      <template #default="{ row }">
        <div v-if="row.attachment_name">
          <a :href="getAttachmentDownloadUrl(row)" target="_blank" class="text-blue-600 hover:text-blue-800 text-xs">
            {{ row.attachment_name }}
          </a>
        </div>
        <div v-else-if="row.attachments && row.attachments.length">
          <div v-for="attachment in row.attachments" :key="attachment.id" class="mb-1">
            <a :href="attachment.download_url" target="_blank" class="text-blue-600 hover:text-blue-800 text-xs">
              {{ attachment.attachment_name }}
            </a>
          </div>
        </div>
      </template>
    </el-table-column>
    <el-table-column v-if="showActions" label="Actions" width="300" align="center">
      <template #default="{ row }">
        <div class="flex items-center justify-center gap-2">
          <el-button 
            size="small" 
            type="success" 
            plain
            @click="$emit('approve-overtime', row)"
            class="flex items-center gap-1"
          >
            <el-icon><Check /></el-icon>
            Approve
          </el-button>
          <el-button 
            size="small" 
            type="warning" 
            plain
            @click="$emit('disapprove-overtime', row)"
            class="flex items-center gap-1"
          >
            <el-icon><Close /></el-icon>
            Disapprove
          </el-button>
          <el-button
            v-if="canCancel(row.date)"
            size="small"
            type="danger"
            plain
            @click="$emit('cancel-overtime', row)"
            class="flex items-center gap-1"
          >
            <el-icon><CircleClose /></el-icon>
            Cancel
          </el-button>
          <el-button 
            v-else 
            size="small" 
            disabled
            class="flex items-center gap-1"
          >
            <el-icon><CircleClose /></el-icon>
            Cancel
          </el-button>
        </div>
      </template>
    </el-table-column>
  </el-table>
</template>

<script>
export default {
  name: 'OvertimeApproverTable',
  props: {
    overtimeRecords: {
      type: Array,
      default: () => []
    },
    showActions: {
      type: Boolean,
      default: true
    }
  },
  methods: {
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
    },
    canCancel(overtimeDate) {
      const today = new Date()
      const overtimeDateObj = new Date(overtimeDate)
      return overtimeDateObj >= today
    },
    getAttachmentDownloadUrl(row) {
      // Use the centralized API service for consistent URL building
      return `/api/overtime-applications/attachments/${row.attachment_id}/download`
    }
  }
}
</script> 