<template>
  <div>
    <el-table :data="timeLogs" border stripe size="default" class="w-full" :row-class-name="getRowClass">
      <el-table-column label="Date" min-width="160">
        <template #default="{ row }">
          {{ formatDate(row.date) }}
          <span v-if="isWeekend(row.date)" class="ml-2 text-rose-600 text-xs font-medium">({{ getDayName(row.date) }})</span>
        </template>
      </el-table-column>
      <el-table-column label="AM - In" min-width="110">
        <template #default="{ row }">
          {{ formatTime(row.am_in) }}
        </template>
      </el-table-column>
      <el-table-column label="AM - Out" min-width="110">
        <template #default="{ row }">
          {{ formatTime(row.am_out) }}
        </template>
      </el-table-column>
      <el-table-column label="Break - In" min-width="110">
        <template #default="{ row }">
          {{ formatTime(row.break_in) }}
        </template>
      </el-table-column>
      <el-table-column label="Break - Out" min-width="110">
        <template #default="{ row }">
          {{ formatTime(row.break_out) }}
        </template>
      </el-table-column>
      <el-table-column label="PM - In" min-width="110">
        <template #default="{ row }">
          {{ formatTime(row.pm_in) }}
        </template>
      </el-table-column>
      <el-table-column label="PM - Out" min-width="110">
        <template #default="{ row }">
          {{ formatTime(row.pm_out) }}
        </template>
      </el-table-column>
      <el-table-column label="Attachment" min-width="160">
        <template #default="{ row }">
          <span v-if="row.attachment_name" class="text-blue-600 text-xs">{{ row.attachment_name }}</span>
          <a v-else-if="row.attachment" :href="row.attachment.download_url" target="_blank" class="text-blue-600 text-xs">{{ row.attachment.name }}</a>
          <span v-else class="text-slate-400 text-xs">No attachment</span>
        </template>
      </el-table-column>
    </el-table>
  </div>
</template>

<script>
export default {
  name: 'TimeLogsTable',
  props: {
    timeLogs: {
      type: Array,
      default: () => []
    }
  },
  methods: {
    getRowClass({ row }) {
      try {
        const d = new Date(row.date)
        const day = d.getDay()
        if (day === 0 || day === 6) return 'is-weekend'
      } catch {}
      return ''
    },
    isWeekend(dateString) {
      try {
        const d = new Date(dateString)
        const day = d.getDay()
        return day === 0 || day === 6
      } catch { return false }
    },
    getDayName(dateString) {
      try {
        const d = new Date(dateString)
        const day = d.getDay()
        return day === 0 ? 'Sunday' : day === 6 ? 'Saturday' : ''
      } catch { return '' }
    },
    formatDate(dateString) {
      if (!dateString) return 'N/A'
      try {
        const date = new Date(dateString)
        if (isNaN(date.getTime())) return 'N/A'
        return date.toLocaleDateString('en-US', { month: '2-digit', day: '2-digit', year: 'numeric' })
      } catch { return 'N/A' }
    },
    formatTime(value) {
      if (!value) return '--:--'
      const str = String(value).trim()
      if (!str) return '--:--'

      const timeMatch = str.match(/(\d{1,2}):(\d{2})(?::\d{2})?/)
      if (timeMatch) {
        const hours = timeMatch[1].padStart(2, '0')
        return `${hours}:${timeMatch[2]}`
      }

      try {
        const date = new Date(str)
        if (!isNaN(date.getTime())) {
          return date.toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false
          })
        }
      } catch {}

      return str
    }
  }
}
</script>

<style scoped>
.is-weekend > td {
  background-color: #fff7ed;
}
</style>
