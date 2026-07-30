<template>
  <div class="schedule-list">
    <!-- Metrics Cards -->
    <el-row :gutter="16" class="mb-6">
      <el-col :span="6">
        <el-card shadow="hover" class="metric-card">
          <el-statistic title="Total Schedules" :value="schedules.length" />
          <template #suffix>
            <el-icon class="metric-icon total"><Calendar /></el-icon>
          </template>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover" class="metric-card">
          <el-statistic title="Active Schedules" :value="activeSchedulesCount" />
          <template #suffix>
            <el-icon class="metric-icon active"><CircleCheck /></el-icon>
          </template>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover" class="metric-card">
          <el-statistic title="Current Month" :value="currentMonthSchedules" />
          <template #suffix>
            <el-icon class="metric-icon current"><Clock /></el-icon>
          </template>
        </el-card>
      </el-col>
      <el-col :span="6">
        <el-card shadow="hover" class="metric-card">
          <el-statistic title="Upcoming" :value="upcomingSchedules" />
          <template #suffix>
            <el-icon class="metric-icon upcoming"><TrendCharts /></el-icon>
          </template>
        </el-card>
      </el-col>
    </el-row>

    <!-- Actions Toolbar -->
    <el-card shadow="never" class="mb-4">
      <el-row :gutter="16" align="middle">
        <el-col :span="8">
          <el-input
            v-model="searchQuery"
            placeholder="Search schedules..."
            :prefix-icon="Search"
            clearable
          />
        </el-col>
        <el-col :span="16" class="text-right">
          <div class="action-buttons">
            <el-dropdown @command="onColumnVisibilityChange">
              <el-button :icon="Setting" title="Column Visibility">
                Column Visibility
                <el-icon class="el-icon--right"><ArrowDown /></el-icon>
              </el-button>
              <template #dropdown>
                <el-dropdown-menu>
                  <el-dropdown-item 
                    v-for="column in availableColumns" 
                    :key="column.key"
                    :command="column.key"
                  >
                    <el-checkbox 
                      :model-value="visibleColumns.includes(column.key)"
                      @change="toggleColumn(column.key)"
                    >
                      {{ column.label }}
                    </el-checkbox>
                  </el-dropdown-item>
                </el-dropdown-menu>
              </template>
            </el-dropdown>
            <el-button :icon="Refresh" @click="onRefresh" :loading="loading">
              Refresh
            </el-button>
            <el-button type="primary" :icon="Plus" @click="onAdd">
              Add Schedule
            </el-button>
          </div>
        </el-col>
      </el-row>
    </el-card>

    <!-- Schedules Table -->
    <el-card shadow="never" class="table-card">
      <template #header>
        <div class="flex justify-between items-center">
          <span class="font-medium">Update 201 Schedules</span>
          <el-tag type="info">{{ filteredSchedules.length }} schedules</el-tag>
        </div>
      </template>

      <el-table 
        v-loading="loading"
        :data="filteredSchedules" 
        border 
        stripe
        :height="tableHeight"
        style="width: 100%"
      >
        <el-table-column 
          v-if="visibleColumns.includes('id')"
          prop="id" 
          label="ID" 
          width="80" 
          align="center"
        >
          <template #default="{ row }">
            <el-tag size="small" type="info">#{{ row.id }}</el-tag>
          </template>
        </el-table-column>

        <el-table-column 
          v-if="visibleColumns.includes('date_from')"
          prop="date_from" 
          label="Start Date" 
          min-width="180"
        >
          <template #default="{ row }">
            <div class="flex items-center">
              <el-icon class="mr-2 text-green-500"><Calendar /></el-icon>
              <span>{{ formatDate(row.date_from) }}</span>
            </div>
          </template>
        </el-table-column>

        <el-table-column 
          v-if="visibleColumns.includes('date_to')"
          prop="date_to" 
          label="End Date" 
          min-width="180"
        >
          <template #default="{ row }">
            <div class="flex items-center">
              <el-icon class="mr-2 text-red-500"><Calendar /></el-icon>
              <span>{{ formatDate(row.date_to) }}</span>
            </div>
          </template>
        </el-table-column>

        <el-table-column 
          v-if="visibleColumns.includes('duration')"
          label="Duration" 
          width="120"
        >
          <template #default="{ row }">
            <el-tag :type="getDurationTagType(row)" size="small">
              {{ calculateDuration(row) }} days
            </el-tag>
          </template>
        </el-table-column>

        <el-table-column 
          v-if="visibleColumns.includes('status')"
          label="Status" 
          width="120"
        >
          <template #default="{ row }">
            <el-tag :type="getStatusType(row)" size="small">
              {{ getStatusText(row) }}
            </el-tag>
          </template>
        </el-table-column>

        <el-table-column 
          v-if="visibleColumns.includes('progress')"
          label="Progress" 
          min-width="220"
        >
          <template #default="{ row }">
            <div class="progress-container">
              <el-progress 
                :percentage="calculateProgress(row)" 
                :color="getProgressColor(row)"
                :show-text="false"
                :stroke-width="8"
              />
              <span class="progress-text">{{ calculateProgress(row) }}%</span>
            </div>
          </template>
        </el-table-column>

        <el-table-column label="Actions" width="240" fixed="right">
          <template #default="{ row }">
            <div class="action-buttons">
              <el-button 
                type="primary" 
                size="small" 
                :icon="View"
                @click="onView(row)"
                title="View Details"
              />
              <el-button 
                type="success" 
                size="small" 
                :icon="Edit"
                @click="onEdit(row)"
                title="Edit Schedule"
              />
              <el-button 
                type="info" 
                size="small" 
                :icon="User"
                @click="onViewEmployees(row)"
                title="View Employees"
              />
              <el-button 
                type="danger" 
                size="small" 
                :icon="Delete"
                @click="onDelete(row)"
                title="Delete Schedule"
              />
            </div>
          </template>
        </el-table-column>
      </el-table>

      <!-- Empty State -->
      <el-empty 
        v-if="!loading && filteredSchedules.length === 0"
        description="No schedules found"
      >
        <el-button type="primary" @click="onAdd">Create First Schedule</el-button>
      </el-empty>
    </el-card>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { 
  Search, Refresh, Plus, View, Edit, Delete, Calendar, CircleCheck, 
  Clock, TrendCharts, Printer, Download, Document, Setting, ArrowDown, User
} from '@element-plus/icons-vue'

const props = defineProps({
  schedules: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false }
})

const emit = defineEmits([
  'refresh', 'add', 'view', 'edit', 'delete', 'print', 'excel', 'pdf', 'viewEmployees'
])

// Reactive data
const searchQuery = ref('')
const tableHeight = ref('calc(100vh - 400px)')

// Column visibility
const availableColumns = ref([
  { key: 'id', label: 'ID' },
  { key: 'date_from', label: 'Start Date' },
  { key: 'date_to', label: 'End Date' },
  { key: 'duration', label: 'Duration' },
  { key: 'status', label: 'Status' },
  { key: 'progress', label: 'Progress' }
])

const visibleColumns = ref(['id', 'date_from', 'date_to', 'duration', 'status', 'progress'])

// Computed properties
const filteredSchedules = computed(() => {
  if (!searchQuery.value) return props.schedules
  
  const query = searchQuery.value.toLowerCase()
  return props.schedules.filter(schedule => 
    schedule.id?.toString().includes(query) ||
    schedule.date_from?.toLowerCase().includes(query) ||
    schedule.date_to?.toLowerCase().includes(query)
  )
})

const activeSchedulesCount = computed(() => {
  const now = new Date()
  return props.schedules.filter(schedule => {
    const startDate = new Date(schedule.date_from)
    const endDate = new Date(schedule.date_to)
    return now >= startDate && now <= endDate
  }).length
})

const currentMonthSchedules = computed(() => {
  const now = new Date()
  const currentMonth = now.getMonth()
  const currentYear = now.getFullYear()
  
  return props.schedules.filter(schedule => {
    const startDate = new Date(schedule.date_from)
    const endDate = new Date(schedule.date_to)
    
    return (startDate.getMonth() === currentMonth && startDate.getFullYear() === currentYear) ||
           (endDate.getMonth() === currentMonth && endDate.getFullYear() === currentYear) ||
           (startDate <= now && endDate >= now)
  }).length
})

const upcomingSchedules = computed(() => {
  const now = new Date()
  return props.schedules.filter(schedule => {
    const startDate = new Date(schedule.date_from)
    return startDate > now
  }).length
})

// Methods
const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const calculateDuration = (schedule) => {
  if (!schedule.date_from || !schedule.date_to) return 0
  const startDate = new Date(schedule.date_from)
  const endDate = new Date(schedule.date_to)
  const diffTime = Math.abs(endDate - startDate)
  return Math.ceil(diffTime / (1000 * 60 * 60 * 24))
}

const getDurationTagType = (schedule) => {
  const duration = calculateDuration(schedule)
  if (duration <= 7) return 'success'
  if (duration <= 30) return 'warning'
  return 'danger'
}

const getStatusType = (schedule) => {
  const now = new Date()
  const startDate = new Date(schedule.date_from)
  const endDate = new Date(schedule.date_to)
  
  if (now < startDate) return 'info'      // Upcoming
  if (now > endDate) return 'success'     // Completed
  return 'warning'                        // Active
}

const getStatusText = (schedule) => {
  const now = new Date()
  const startDate = new Date(schedule.date_from)
  const endDate = new Date(schedule.date_to)
  
  if (now < startDate) return 'Upcoming'
  if (now > endDate) return 'Completed'
  return 'Active'
}

const calculateProgress = (schedule) => {
  if (!schedule.date_from || !schedule.date_to) return 0
  const now = new Date()
  const startDate = new Date(schedule.date_from)
  const endDate = new Date(schedule.date_to)
  if (Number.isNaN(startDate.getTime()) || Number.isNaN(endDate.getTime())) return 0

  if (now < startDate) return 0
  if (now > endDate) return 100

  const totalDuration = endDate - startDate
  if (totalDuration <= 0) return 100
  const elapsed = now - startDate
  return Math.round((elapsed / totalDuration) * 100)
}

const getProgressColor = (schedule) => {
  const progress = calculateProgress(schedule)
  if (!Number.isFinite(progress)) return '#909399'
  if (progress < 30) return '#67c23a'
  if (progress < 70) return '#e6a23c'
  return '#f56c6c'
}

const toggleColumn = (columnKey) => {
  const index = visibleColumns.value.indexOf(columnKey)
  if (index > -1) {
    if (visibleColumns.value.length > 1) {
      visibleColumns.value.splice(index, 1)
    }
  } else {
    visibleColumns.value.push(columnKey)
  }
}

const onColumnVisibilityChange = (command) => {
  toggleColumn(command)
}

const onRefresh = () => emit('refresh')
const onAdd = () => emit('add')
const onView = (row) => emit('view', row)
const onEdit = (row) => emit('edit', row)
const onDelete = (row) => emit('delete', row)
const onPrint = () => emit('print')
const onExcel = () => emit('excel')
const onPDF = () => emit('pdf')
const onViewEmployees = (row) => emit('viewEmployees', row)
</script>

<style scoped>
.schedule-list {
  padding: 0;
}

.metric-card {
  text-align: center;
}

.metric-card .el-statistic__content {
  font-size: 1.5rem;
  font-weight: bold;
}

.metric-icon {
  font-size: 1.5rem;
  margin-left: 8px;
}

.metric-icon.total { color: #3498db; }
.metric-icon.active { color: #27ae60; }
.metric-icon.current { color: #f39c12; }
.metric-icon.upcoming { color: #9b59b6; }

.action-buttons {
  display: flex;
  gap: 8px;
  align-items: center;
  flex-wrap: wrap;
}

.action-buttons .el-button {
  margin-left: 0;
}

.action-buttons .el-dropdown {
  margin-left: 0;
}

.progress-container {
  display: flex;
  align-items: center;
  gap: 8px;
}

.progress-text {
  font-size: 0.75rem;
  color: #606266;
  min-width: 35px;
}

.table-card {
  width: 100%;
}

.table-card .el-card__body {
  padding: 0;
}

.table-card .el-table {
  width: 100% !important;
}
</style>
