<template>
  <el-table :data="dtrRecords" border stripe size="default" class="w-full">
    <el-table-column label="Payroll Interval" min-width="160">
      <template #default="{ row }">{{ formatPayrollInterval(row) }}</template>
    </el-table-column>
    <el-table-column label="Cut-off" prop="cut_off" min-width="120" />
    <el-table-column label="Attendance Start" min-width="150">
      <template #default="{ row }">{{ formatDate(row.attendance_start_date) }}</template>
    </el-table-column>
    <el-table-column label="Attendance End" min-width="150">
      <template #default="{ row }">{{ formatDate(row.attendance_end_date) }}</template>
    </el-table-column>
    <el-table-column label="DTR" width="110" align="center">
      <template #default="{ row }">
        <el-button size="small" type="warning" plain :disabled="!row.employee_id || !row.payroll_period_id" @click="$emit('view-dtr', row)">DTR</el-button>
      </template>
    </el-table-column>
    <el-table-column label="Print" width="110" align="center">
      <template #default="{ row }">
        <el-button size="small" plain :disabled="!row.employee_id || !row.payroll_period_id" @click="$emit('print-dtr', row)">
          <el-icon><Printer /></el-icon>
        </el-button>
      </template>
    </el-table-column>
  </el-table>
</template>

<script>
export default {
  name: 'DTRTable',
  props: {
    dtrRecords: {
      type: Array,
      default: () => []
    }
  },
  methods: {
    formatPayrollInterval(row) {
      const interval = String(row?.payroll_interval || '').trim()
      const dateSource = row?.attendance_start_date || row?.attendance_end_date

      if (/^monthly$/i.test(interval) && dateSource) {
        const monthLabel = this.formatMonthYear(dateSource)
        if (monthLabel) return monthLabel
      }

      return interval || 'N/A'
    },
    formatMonthYear(dateString) {
      if (!dateString) return ''

      try {
        const date = new Date(dateString)
        if (isNaN(date.getTime())) return ''

        return date.toLocaleDateString('en-US', {
          month: 'long',
          year: 'numeric'
        })
      } catch {
        return ''
      }
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
    }
  }
}
</script> 