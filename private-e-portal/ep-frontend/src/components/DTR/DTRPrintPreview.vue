<template>
  <div class="dtr-print">
    <div class="mb-4">
      <h3 class="text-lg font-semibold text-slate-900">Daily Time Record</h3>
      <div class="text-sm text-slate-600">Payroll Interval: {{ summary.payroll_interval || 'N/A' }} · Cut-off: {{ summary.cut_off || 'N/A' }}</div>
      <div class="text-sm text-slate-600">Attendance: {{ formatDate(summary.attendance_start_date) }} - {{ formatDate(summary.attendance_end_date) }}</div>
    </div>

    <el-table :data="rows" border stripe size="small" class="w-full">
      <el-table-column prop="date" label="Date" min-width="110">
        <template #default="{ row }">{{ formatDate(row.date) }}</template>
      </el-table-column>
      <el-table-column prop="time_in" label="Time In" min-width="100" />
      <el-table-column prop="time_out" label="Time Out" min-width="100" />
      <el-table-column prop="late" label="Late" min-width="80" />
      <el-table-column prop="undertime" label="Undertime" min-width="110" />
      <el-table-column prop="overtime" label="Overtime" min-width="100" />
      <el-table-column prop="remarks" label="Remarks" min-width="160" />
    </el-table>

    <div v-if="rows.length === 0" class="text-center text-slate-500 py-6">No time entries available.</div>
  </div>
</template>

<script>
export default {
  name: 'DTRPrintPreview',
  props: {
    dtrData: {
      type: Object,
      default: () => ({})
    }
  },
  computed: {
    payload() {
      // Normalize possible response shapes
      return this.dtrData?.data || this.dtrData || {}
    },
    summary() {
      const src = this.payload.employee_info || this.payload.summary || this.payload
      return src || {}
    },
    rows() {
      // Common keys: records, dtr, details, entries
      const data = this.payload.records || this.payload.dtr || this.payload.details || this.payload.entries || []
      if (Array.isArray(data)) return data
      return []
    }
  },
  methods: {
    formatDate(d) {
      if (!d) return 'N/A'
      const dt = new Date(d)
      if (isNaN(dt.getTime())) return d
      return dt.toLocaleDateString('en-US', { month: '2-digit', day: '2-digit', year: 'numeric' })
    }
  }
}
</script>

<style scoped>
.dtr-print {
  page-break-inside: avoid;
}
</style>


