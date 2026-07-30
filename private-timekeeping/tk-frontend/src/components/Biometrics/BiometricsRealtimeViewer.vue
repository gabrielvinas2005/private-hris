<template>
  <div>
    <el-table 
      :data="rows" 
      v-loading="loading" 
      border 
      style="width: 100%"
      max-height="700"
      :row-class-name="getRowClassName"
      highlight-current-row
    >
      <el-table-column prop="logid" label="Log ID" width="100" />
      <el-table-column prop="employee_no" label="Employee No" width="130" />
      <el-table-column prop="employee_name" label="Employee Name" min-width="200" />
      <el-table-column prop="date" label="Date" width="120">
        <template #default="{ row }">
          {{ formatDate(row.date) }}
        </template>
      </el-table-column>
      <el-table-column prop="time" label="Time" width="120">
        <template #default="{ row }">
          <span class="time-display">{{ formatTime(row.time) }}</span>
        </template>
      </el-table-column>
      <el-table-column prop="check_type_label" label="Type" width="130">
        <template #default="{ row }">
          <el-tag 
            :type="getTypeTagType(row.type)" 
            :effect="row.checktype === 1 || row.checktype === 3 ? 'dark' : 'plain'"
          >
            <el-icon v-if="row.type === 'In'" style="margin-right: 4px;">
              <ArrowRight />
            </el-icon>
            <el-icon v-else style="margin-right: 4px;">
              <ArrowLeft />
            </el-icon>
            {{ row.check_type_label }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column prop="checktime" label="Full DateTime" width="180">
        <template #default="{ row }">
          {{ formatDateTime(row.checktime) }}
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
              <div class="summary-label">Check Ins</div>
              <div class="summary-value check-in">{{ checkInCount }}</div>
            </div>
          </el-card>
        </el-col>
        <el-col :span="6">
          <el-card class="summary-card">
            <div class="summary-item">
              <div class="summary-label">Check Outs</div>
              <div class="summary-value check-out">{{ checkOutCount }}</div>
            </div>
          </el-card>
        </el-col>
        <el-col :span="6">
          <el-card class="summary-card">
            <div class="summary-item">
              <div class="summary-label">Unique Employees</div>
              <div class="summary-value">{{ uniqueEmployees }}</div>
            </div>
          </el-card>
        </el-col>
      </el-row>
    </div>

    <!-- Last Update Indicator -->
    <div v-if="lastUpdated" class="last-update">
      <el-text type="info" size="small">
        Last updated: {{ formatDateTime(lastUpdated) }}
      </el-text>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { ArrowRight, ArrowLeft } from '@element-plus/icons-vue'
import { formatTime } from '../../Composables/useTimeFormatting'

const props = defineProps({
  rows: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  lastUpdated: { type: [Date, String], default: null }
})

// Computed properties for summary statistics
const checkInCount = computed(() => {
  return props.rows.filter(row => row.type === 'In').length
})

const checkOutCount = computed(() => {
  return props.rows.filter(row => row.type === 'Out').length
})

const uniqueEmployees = computed(() => {
  const unique = new Set(props.rows.map(row => row.employee_id))
  return unique.size
})

// Get tag type based on check type
function getTypeTagType(type) {
  if (type === 'In') return 'success'
  if (type === 'Out') return 'warning'
  return 'info'
}

// Get row class name for styling
function getRowClassName({ row }) {
  if (row.type === 'In') {
    return 'row-check-in'
  } else if (row.type === 'Out') {
    return 'row-check-out'
  }
  return ''
}

// Formatting functions
function formatDate(dateString) {
  if (!dateString) return ''
  try {
    const date = new Date(dateString)
    return date.toLocaleDateString('en-US', { 
      year: 'numeric', 
      month: 'short', 
      day: 'numeric' 
    })
  } catch {
    return dateString
  }
}

function formatDateTime(dateTimeString) {
  if (!dateTimeString) return ''
  try {
    const date = new Date(dateTimeString)
    return date.toLocaleString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit',
      hour12: true
    })
  } catch {
    return dateTimeString
  }
}
</script>

<style scoped>
.mt-4 { 
  margin-top: 16px; 
}

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

.summary-value.check-in {
  color: #67c23a;
}

.summary-value.check-out {
  color: #e6a23c;
}

.time-display {
  font-weight: 600;
  font-family: 'Courier New', monospace;
  color: #303133;
}

.last-update {
  margin-top: 12px;
  text-align: right;
  padding: 8px;
  font-size: 12px;
}

:deep(.row-check-in) {
  background-color: #f0f9ff;
}

:deep(.row-check-in:hover > td) {
  background-color: #e0f2fe !important;
}

:deep(.row-check-out) {
  background-color: #fffbeb;
}

:deep(.row-check-out:hover > td) {
  background-color: #fef3c7 !important;
}
</style>

