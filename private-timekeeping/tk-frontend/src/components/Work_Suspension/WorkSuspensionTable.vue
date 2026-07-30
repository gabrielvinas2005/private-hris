<template>
  <div class="table-with-loading">
  <el-table :data="rows" border style="width: 100%">
    <el-table-column type="index" label="#" width="60" />
    <el-table-column label="Date From" min-width="100" show-overflow-tooltip>
      <template #default="{ row }">
        {{ formatDateTime(row.date_from, row.time_from) }}
      </template>
    </el-table-column>
    <el-table-column label="Date To" min-width="100" show-overflow-tooltip>
      <template #default="{ row }">
        {{ formatDateTime(row.date_to, row.time_to) }}
      </template>
    </el-table-column>
    <!-- <el-table-column label="Duration" width="100" align="center">
      <template #default="{ row }">
        {{ calculateDuration(row.date_from, row.date_to) }}
      </template>
    </el-table-column> -->
    <el-table-column prop="reason" label="Reason" min-width="260" show-overflow-tooltip />
    <el-table-column v-if="!hideActions" label="Actions" width="120" fixed="right" align="center">
      <template #default="{ row }">
        <ReusableButtons
          :row="row"
          :show-view="false"
          :show-edit="true"
          :show-delete="true"
          @edit="$emit('edit', $event)"
          @delete="$emit('delete', $event)"
        />
      </template>
    </el-table-column>
  </el-table>
  <TableLoadingOverlay :loading="loading" text="Loading work suspensions..." />
  </div>
</template>

<script setup>
import ReusableButtons from '../Reusable_Components/Reusable_Buttons.vue'
import TableLoadingOverlay from '../Reusable_Components/TableLoadingOverlay.vue'

defineProps({
  rows: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  hideActions: { type: Boolean, default: false },
})
defineEmits(['edit', 'delete'])

function formatDate(date) {
  if (!date) return '-'
  const d = new Date(date)
  if (isNaN(d.getTime())) return '-'
  return d.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })
}

function calculateDuration(dateFrom, dateTo) {
  if (!dateFrom || !dateTo) return '-'
  const from = new Date(dateFrom)
  const to = new Date(dateTo)
  if (isNaN(from.getTime()) || isNaN(to.getTime())) return '-'
  const timeDiff = to.getTime() - from.getTime()
  const diffDays = Math.floor(timeDiff / (1000 * 60 * 60 * 24)) + 1
  if (diffDays <= 0) return '-'
  return diffDays === 1 ? '1 day' : `${diffDays} days`
}

function formatTime(timeValue) {
  if (!timeValue) return ''
  const raw = String(timeValue).trim()
  const match = raw.match(/^(\d{1,2}):(\d{2})(?::\d{2})?$/)
  if (!match) return ''

  let hours = Number(match[1])
  const minutes = match[2]
  if (isNaN(hours)) return ''

  const meridiem = hours >= 12 ? 'PM' : 'AM'
  hours = hours % 12
  if (hours === 0) hours = 12
  return `${String(hours).padStart(2, '0')}:${minutes} ${meridiem}`
}

function formatDateTime(dateValue, timeValue) {
  const dateText = formatDate(dateValue)
  const timeText = formatTime(timeValue)
  if (dateText === '-') return '-'
  if (!timeText) return dateText
  return `${dateText} - ${timeText}`
}
</script>

<style scoped>
.table-with-loading { position: relative; }
</style>
