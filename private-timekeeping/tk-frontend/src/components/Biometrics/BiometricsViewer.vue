<template>
  <div>
    <el-table 
      :data="rows" 
      v-loading="loading" 
      border 
      style="width: 100%"
      max-height="600"
    >
      <el-table-column prop="id" label="ID" width="80" />
      <el-table-column prop="name" label="Employee Name" min-width="200" />
      <el-table-column prop="date" label="Date" width="120">
        <template #default="{ row }">
          {{ formatDate(row.date) }}
        </template>
      </el-table-column>
      <el-table-column prop="day" label="Day" width="100" />
      <el-table-column prop="am_in" label="AM In" width="100">
        <template #default="{ row }">
          {{ formatTime(row.am_in) }}
        </template>
      </el-table-column>
      <el-table-column prop="am_out" label="AM Out" width="100">
        <template #default="{ row }">
          {{ formatTime(row.am_out) }}
        </template>
      </el-table-column>
      <el-table-column prop="pm_in" label="PM In" width="100">
        <template #default="{ row }">
          {{ formatTime(row.pm_in) }}
        </template>
      </el-table-column>
      <el-table-column prop="pm_out" label="PM Out" width="100">
        <template #default="{ row }">
          {{ formatTime(row.pm_out) }}
        </template>
      </el-table-column>
      <el-table-column prop="total_hours" label="Total Hours" width="120">
        <template #default="{ row }">
          {{ formatNumber(row.total_hours) }}
        </template>
      </el-table-column>
      <el-table-column prop="overtime" label="Overtime" width="120">
        <template #default="{ row }">
          {{ formatNumber(row.overtime) }}
        </template>
      </el-table-column>
      <el-table-column prop="late" label="Late" width="100">
        <template #default="{ row }">
          {{ formatNumber(row.late) }}
        </template>
      </el-table-column>
      <el-table-column prop="undertime" label="Undertime" width="120">
        <template #default="{ row }">
          {{ formatNumber(row.undertime) }}
        </template>
      </el-table-column>
    </el-table>

    <!-- Summary Statistics -->
    <div class="mt-4" v-if="rows.length > 0">
      <el-row :gutter="16">
        <el-col :span="6">
          <el-card class="summary-card">
            <div class="summary-item">
              <div class="summary-label">Total Records</div>
              <div class="summary-value">{{ rows.length }}</div>
            </div>
          </el-card>
        </el-col>
        <el-col :span="6">
          <el-card class="summary-card">
            <div class="summary-item">
              <div class="summary-label">Total Hours</div>
              <div class="summary-value">{{ formatNumber(totalHours) }}</div>
            </div>
          </el-card>
        </el-col>
        <el-col :span="6">
          <el-card class="summary-card">
            <div class="summary-item">
              <div class="summary-label">Total Overtime</div>
              <div class="summary-value">{{ formatNumber(totalOvertime) }}</div>
            </div>
          </el-card>
        </el-col>
        <el-col :span="6">
          <el-card class="summary-card">
            <div class="summary-item">
              <div class="summary-label">Total Late</div>
              <div class="summary-value">{{ formatNumber(totalLate) }}</div>
            </div>
          </el-card>
        </el-col>
      </el-row>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { formatTime } from '../../Composables/useTimeFormatting'

const props = defineProps({
  rows: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
})

// Computed properties for summary statistics
const totalHours = computed(() => {
  return props.rows.reduce((sum, row) => sum + (Number(row.total_hours) || 0), 0)
})

const totalOvertime = computed(() => {
  return props.rows.reduce((sum, row) => sum + (Number(row.overtime) || 0), 0)
})

const totalLate = computed(() => {
  return props.rows.reduce((sum, row) => sum + (Number(row.late) || 0), 0)
})

// Formatting functions
function formatDate(dateString) {
  if (!dateString) return ''
  try {
    const date = new Date(dateString)
    return date.toLocaleDateString()
  } catch {
    return dateString
  }
}

// formatTime provided by useTimeFormatting

function formatNumber(value) {
  if (value === null || value === undefined) return ''
  const num = Number(value)
  if (Number.isNaN(num)) return ''
  return num.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}
</script>

<style scoped>
.mt-4 { margin-top: 16px; }

.summary-card {
  text-align: center;
}

.summary-item {
  padding: 8px;
}

.summary-label {
  font-size: 12px;
  color: #666;
  margin-bottom: 4px;
}

.summary-value {
  font-size: 18px;
  font-weight: bold;
  color: #409eff;
}
</style>
