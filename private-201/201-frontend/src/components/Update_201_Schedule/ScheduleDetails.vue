<template>
  <el-drawer
    v-model="visible"
    :title="title"
    size="50%"
    direction="rtl"
    :close-on-click-modal="false"
  >
    <div v-loading="loading" class="schedule-details">
      <div v-if="schedule">
        <!-- Schedule Information -->
        <el-card shadow="never" class="mb-4">
          <template #header>
            <div class="flex items-center">
              <el-icon class="mr-2"><Calendar /></el-icon>
              <span>Schedule Information</span>
            </div>
          </template>
          
          <div class="detail-grid">
            <div class="detail-item">
              <label class="detail-label">Schedule ID:</label>
              <el-tag size="small" type="info">#{{ schedule.id }}</el-tag>
            </div>
            
            <div class="detail-item">
              <label class="detail-label">Start Date:</label>
              <span class="detail-value">{{ formatDate(schedule.date_from) }}</span>
            </div>
            
            <div class="detail-item">
              <label class="detail-label">End Date:</label>
              <span class="detail-value">{{ formatDate(schedule.date_to) }}</span>
            </div>
            
            <div class="detail-item">
              <label class="detail-label">Duration:</label>
              <el-tag :type="durationTagType" size="small">
                {{ duration }} days
              </el-tag>
            </div>
            
            <div class="detail-item">
              <label class="detail-label">Status:</label>
              <el-tag :type="statusType" size="small">
                {{ statusText }}
              </el-tag>
            </div>
          </div>
        </el-card>

        <!-- Progress Information -->
        <el-card shadow="never" class="mb-4">
          <template #header>
            <div class="flex items-center">
              <el-icon class="mr-2"><TrendCharts /></el-icon>
              <span>Progress Information</span>
            </div>
          </template>
          
          <div class="progress-section">
            <div class="progress-header mb-4">
              <h4>Schedule Progress</h4>
              <span class="progress-percentage">{{ progress }}%</span>
            </div>
            
            <el-progress 
              :percentage="progress" 
              :color="progressColor"
              :stroke-width="12"
              class="mb-4"
            />
            
            <el-row :gutter="16">
              <el-col :span="8">
                <div class="progress-stat">
                  <div class="stat-label">Days Elapsed</div>
                  <div class="stat-value">{{ daysElapsed }}</div>
                </div>
              </el-col>
              <el-col :span="8">
                <div class="progress-stat">
                  <div class="stat-label">Days Remaining</div>
                  <div class="stat-value">{{ daysRemaining }}</div>
                </div>
              </el-col>
              <el-col :span="8">
                <div class="progress-stat">
                  <div class="stat-label">Total Days</div>
                  <div class="stat-value">{{ duration }}</div>
                </div>
              </el-col>
            </el-row>
          </div>
        </el-card>

        <!-- Timeline -->
        <el-card shadow="never" class="mb-4">
          <template #header>
            <div class="flex items-center">
              <el-icon class="mr-2"><Clock /></el-icon>
              <span>Timeline</span>
            </div>
          </template>
          
          <el-timeline>
            <el-timeline-item
              timestamp="Start Date"
              :type="getTimelineType('start')"
            >
              <div class="timeline-content">
                <h4>Schedule Started</h4>
                <p>{{ formatDate(schedule.date_from) }}</p>
                <el-tag v-if="isStarted" type="success" size="small">Completed</el-tag>
                <el-tag v-else type="info" size="small">Pending</el-tag>
              </div>
            </el-timeline-item>
            
            <el-timeline-item
              v-if="isActive"
              timestamp="Current"
              type="primary"
            >
              <div class="timeline-content">
                <h4>In Progress</h4>
                <p>{{ formatDate(new Date()) }}</p>
                <el-tag type="warning" size="small">Active</el-tag>
              </div>
            </el-timeline-item>
            
            <el-timeline-item
              timestamp="End Date"
              :type="getTimelineType('end')"
            >
              <div class="timeline-content">
                <h4>Schedule Completion</h4>
                <p>{{ formatDate(schedule.date_to) }}</p>
                <el-tag v-if="isCompleted" type="success" size="small">Completed</el-tag>
                <el-tag v-else-if="isActive" type="warning" size="small">In Progress</el-tag>
                <el-tag v-else type="info" size="small">Upcoming</el-tag>
              </div>
            </el-timeline-item>
          </el-timeline>
        </el-card>

        <!-- Actions -->
        <el-card shadow="never">
          <template #header>
            <div class="flex items-center">
              <el-icon class="mr-2"><Setting /></el-icon>
              <span>Actions</span>
            </div>
          </template>
          
          <div class="actions-grid">
            <el-button 
              type="primary" 
              :icon="Edit" 
              @click="onEdit"
              :disabled="isCompleted"
            >
              Edit Schedule
            </el-button>
            
            
            <el-button 
              type="warning" 
              :icon="Refresh" 
              @click="onRefresh"
            >
              Refresh Data
            </el-button>
          </div>
        </el-card>
      </div>
    </div>

    <!-- Action Buttons -->
    <template #footer>
      <div class="drawer-footer">
        <el-button @click="visible = false">Close</el-button>
        <el-button 
          type="primary" 
          :icon="Edit" 
          @click="onEdit"
          :disabled="isCompleted"
        >
          Edit Schedule
        </el-button>
      </div>
    </template>
  </el-drawer>
</template>

<script setup>
import { computed } from 'vue'
import { 
  Calendar, TrendCharts, Clock, Setting, Edit, Document, Refresh
} from '@element-plus/icons-vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  schedule: { type: Object, default: null },
  loading: { type: Boolean, default: false }
})

const emit = defineEmits(['update:modelValue', 'edit', 'export', 'refresh'])

// Computed properties
const visible = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

const title = computed(() => {
  return props.schedule?.id 
    ? `Schedule Details - #${props.schedule.id}`
    : 'Schedule Details'
})

const duration = computed(() => {
  if (!props.schedule?.date_from || !props.schedule?.date_to) return 0
  const startDate = new Date(props.schedule.date_from)
  const endDate = new Date(props.schedule.date_to)
  const diffTime = Math.abs(endDate - startDate)
  return Math.ceil(diffTime / (1000 * 60 * 60 * 24))
})

const durationTagType = computed(() => {
  const d = duration.value
  if (d <= 7) return 'success'
  if (d <= 30) return 'warning'
  return 'danger'
})

const statusText = computed(() => {
  if (!props.schedule) return 'Unknown'
  
  const now = new Date()
  const startDate = new Date(props.schedule.date_from)
  const endDate = new Date(props.schedule.date_to)
  
  if (now < startDate) return 'Upcoming'
  if (now > endDate) return 'Completed'
  return 'Active'
})

const statusType = computed(() => {
  const status = statusText.value
  if (status === 'Upcoming') return 'info'
  if (status === 'Completed') return 'success'
  if (status === 'Active') return 'warning'
  return 'info'
})

const progress = computed(() => {
  if (!props.schedule?.date_from || !props.schedule?.date_to) return 0
  
  const now = new Date()
  const startDate = new Date(props.schedule.date_from)
  const endDate = new Date(props.schedule.date_to)
  
  if (now < startDate) return 0
  if (now > endDate) return 100
  
  const totalDuration = endDate - startDate
  const elapsed = now - startDate
  return Math.round((elapsed / totalDuration) * 100)
})

const progressColor = computed(() => {
  const p = progress.value
  if (p < 30) return '#67c23a'
  if (p < 70) return '#e6a23c'
  return '#f56c6c'
})

const daysElapsed = computed(() => {
  if (!props.schedule?.date_from) return 0
  
  const now = new Date()
  const startDate = new Date(props.schedule.date_from)
  
  if (now < startDate) return 0
  
  const diffTime = now - startDate
  return Math.floor(diffTime / (1000 * 60 * 60 * 24))
})

const daysRemaining = computed(() => {
  if (!props.schedule?.date_to) return 0
  
  const now = new Date()
  const endDate = new Date(props.schedule.date_to)
  
  if (now > endDate) return 0
  
  const diffTime = endDate - now
  return Math.ceil(diffTime / (1000 * 60 * 60 * 24))
})

const isStarted = computed(() => {
  if (!props.schedule?.date_from) return false
  return new Date() >= new Date(props.schedule.date_from)
})

const isActive = computed(() => {
  return statusText.value === 'Active'
})

const isCompleted = computed(() => {
  return statusText.value === 'Completed'
})

// Methods
const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    weekday: 'long'
  })
}

const getTimelineType = (type) => {
  if (type === 'start') {
    return isStarted.value ? 'success' : 'info'
  }
  if (type === 'end') {
    return isCompleted.value ? 'success' : (isActive.value ? 'warning' : 'info')
  }
  return 'primary'
}

const onEdit = () => {
  emit('edit', props.schedule)
  visible.value = false
}

const onExport = () => {
  emit('export', props.schedule)
}

const onRefresh = () => {
  emit('refresh')
}
</script>

<style scoped>
.schedule-details {
  padding: 0 16px;
}

.detail-grid {
  display: grid;
  gap: 16px;
}

.detail-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 0;
  border-bottom: 1px solid #f0f0f0;
}

.detail-item:last-child {
  border-bottom: none;
}

.detail-label {
  font-weight: 500;
  color: #606266;
  min-width: 120px;
}

.detail-value {
  color: #303133;
  font-weight: 500;
}

.progress-section {
  padding: 16px 0;
}

.progress-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.progress-header h4 {
  margin: 0;
  color: #303133;
}

.progress-percentage {
  font-size: 1.5rem;
  font-weight: bold;
  color: #409eff;
}

.progress-stat {
  text-align: center;
  padding: 16px;
  background: #f8f9fa;
  border-radius: 8px;
}

.stat-label {
  font-size: 0.875rem;
  color: #606266;
  margin-bottom: 4px;
}

.stat-value {
  font-size: 1.5rem;
  font-weight: bold;
  color: #303133;
}

.timeline-content h4 {
  margin: 0 0 4px 0;
  color: #303133;
}

.timeline-content p {
  margin: 0 0 8px 0;
  color: #606266;
}

.actions-grid {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
}

.drawer-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  padding: 16px;
  border-top: 1px solid #e4e7ed;
}
</style>
