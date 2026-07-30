<template>
  <el-table :data="dtrRecords" border stripe size="default" class="w-full" :summary-method="getSummaries" show-summary>
    <el-table-column label="Date" min-width="130">
      <template #default="{ row }">{{ formatDate(row.date) }}</template>
    </el-table-column>
    <el-table-column prop="am_in" label="AM - In" min-width="110">
      <template #default="{ row }">{{ formatTime(row.am_in) }}</template>
    </el-table-column>
    <el-table-column prop="am_out" label="AM - Out" min-width="110">
      <template #default="{ row }">{{ formatTime(row.am_out) }}</template>
    </el-table-column>
    <el-table-column prop="break_in" label="Break - In" min-width="120">
      <template #default="{ row }">{{ formatTime(row.break_in) }}</template>
    </el-table-column>
    <el-table-column prop="break_out" label="Break - Out" min-width="120">
      <template #default="{ row }">{{ formatTime(row.break_out) }}</template>
    </el-table-column>
    <el-table-column prop="pm_in" label="PM - In" min-width="110">
      <template #default="{ row }">{{ formatTime(row.pm_in) }}</template>
    </el-table-column>
    <el-table-column prop="pm_out" label="PM - Out" min-width="110">
      <template #default="{ row }">{{ formatTime(row.pm_out) }}</template>
    </el-table-column>
    <el-table-column label="OT" min-width="90">
      <template #default="{ row }">{{ formatNumber(row.ot_hours) }}</template>
    </el-table-column>
    <el-table-column label="Late" min-width="90">
      <template #default="{ row }">{{ formatNumber(row.late) }}</template>
    </el-table-column>
    <el-table-column label="Undertime" min-width="110">
      <template #default="{ row }">{{ formatNumber(row.undertime) }}</template>
    </el-table-column>
    <el-table-column label="Leave" min-width="90">
      <template #default="{ row }">{{ formatNumber(row.leave) }}</template>
    </el-table-column>
    <el-table-column label="Absent" min-width="100">
      <template #default="{ row }">{{ formatNumber(row.absent) }}</template>
    </el-table-column>
    <el-table-column label="Work Hours" min-width="120">
      <template #default="{ row }">{{ formatNumber(row.work_hours) }}</template>
    </el-table-column>
    <el-table-column prop="remarks" label="Remarks" min-width="200" />
  </el-table>
</template>

<script>
export default {
  name: 'DTRDetailTable',
  props: {
    dtrRecords: {
      type: Array,
      default: () => []
    },
    totals: {
      type: Object,
      default: () => ({
        ot: 0,
        late: 0,
        undertime: 0,
        leave: 0,
        absent: 0,
        work_hours: 0
      })
    }
  },
  methods: {
    getSummaries({ columns }) {
      const sums = []
      columns.forEach((column, index) => {
        if (index === 0) {
          sums[index] = 'Totals'
          return
        }
        const map = {
          'OT': this.totals.ot,
          'Late': this.totals.late,
          'Undertime': this.totals.undertime,
          'Leave': this.totals.leave,
          'Absent': this.totals.absent,
          'Work Hours': this.totals.work_hours
        }
        sums[index] = map[column.label] != null ? this.formatNumber(map[column.label]) : ''
      })
      return sums
    },
    formatDate(dateString) {
      if (!dateString) return 'N/A'
      
      try {
      const date = new Date(dateString)
        
        // Check if date is valid
        if (isNaN(date.getTime())) {
          return 'N/A'
        }
        
      return date.toLocaleDateString('en-US', {
        month: '2-digit',
        day: '2-digit',
        year: 'numeric'
      })
      } catch (error) {
        console.error('Error formatting date:', error)
        return 'N/A'
      }
    },
    
    formatTime(timeString) {
      if (!timeString) return '-'
      
      try {
        // If it's already a time string, return as is
        if (typeof timeString === 'string' && timeString.includes(':')) {
      return timeString
        }
        
        // If it's a date object, format it as time
        const date = new Date(timeString)
        if (!isNaN(date.getTime())) {
          return date.toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false
          })
        }
        
        return timeString
      } catch (error) {
        console.error('Error formatting time:', error)
        return timeString || '-'
      }
    },
    
    formatNumber(value) {
      if (value === null || value === undefined || value === '') {
        return '0.00'
      }
      
      try {
        const num = parseFloat(value)
        if (isNaN(num)) {
          return '0.00'
        }
        return num.toFixed(2)
      } catch (error) {
        console.error('Error formatting number:', error)
        return '0.00'
      }
    }
  }
}
</script> 