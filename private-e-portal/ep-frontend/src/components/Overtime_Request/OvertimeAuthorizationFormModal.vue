<template>
  <el-dialog
    title="Overtime Authorization Request"
    :model-value="true"
    width="960px"
    :close-on-click-modal="false"
    :close-on-press-escape="false"
    @close="$emit('close')"
  >
    <p class="mb-3 text-sm text-slate-600">
      Select one or more pending overtime applications to include in the Overtime Authorization Request form.
    </p>

    <el-table
      :data="records"
      size="small"
      border
      stripe
      height="360"
      @selection-change="handleSelectionChange"
    >
      <el-table-column type="selection" width="45" />

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
      <el-table-column prop="remarks" label="Remarks" min-width="180" show-overflow-tooltip />
    </el-table>

    <template #footer>
      <span class="dialog-footer flex justify-between items-center w-full">
        <span class="text-xs text-slate-500">
          Selected: {{ selectedIds.length }}
        </span>
        <span>
          <el-button @click="$emit('close')">Close</el-button>
          <el-button type="primary" :disabled="selectedIds.length === 0" @click="handlePrint">
            Print Preview
          </el-button>
        </span>
      </span>
    </template>
  </el-dialog>
</template>

<script>
export default {
  name: 'OvertimeAuthorizationFormModal',
  props: {
    records: {
      type: Array,
      default: () => []
    }
  },
  data() {
    return {
      selectedIds: []
    }
  },
  methods: {
    handleSelectionChange(selection) {
      this.selectedIds = Array.isArray(selection) ? selection.map(item => item.id) : []
    },
    handlePrint() {
      this.$emit('print', this.selectedIds)
    },
    formatDate(dateString) {
      if (!dateString) return ''
      const d = new Date(dateString)
      if (Number.isNaN(d.getTime())) return ''
      return d.toLocaleDateString('en-US', { month: '2-digit', day: '2-digit', year: 'numeric' })
    },
    formatTime(timeString) {
      if (!timeString) return ''
      try {
        if (timeString.includes(' ') && timeString.includes('/')) {
          const match = timeString.match(/(\d{1,2}):(\d{2}):(\d{2})$/)
          if (match) {
            const hours = parseInt(match[1])
            const minutes = match[2]
            const ampm = hours >= 12 ? 'PM' : 'AM'
            const displayHours = hours > 12 ? hours - 12 : (hours === 0 ? 12 : hours)
            return `${displayHours}:${minutes} ${ampm}`
          }
        }
        const d = new Date(timeString)
        if (!Number.isNaN(d.getTime())) {
          return d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true })
        }
        const match = timeString.match(/^(\d{1,2}):(\d{2}):(\d{2})$/)
        if (match) {
          const hours = parseInt(match[1])
          const minutes = match[2]
          const ampm = hours >= 12 ? 'PM' : 'AM'
          const displayHours = hours > 12 ? hours - 12 : (hours === 0 ? 12 : hours)
          return `${displayHours}:${minutes} ${ampm}`
        }
        return timeString
      } catch {
        return timeString
      }
    }
  }
}
</script>

