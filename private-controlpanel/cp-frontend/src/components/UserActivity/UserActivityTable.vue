<template>
  <div class="user-activity-table">
    <div v-if="loading" class="loading-placeholder">
      <el-skeleton :rows="5" animated />
    </div>
    <div v-else-if="apiError" class="error-message">
      <el-alert
        title="API Error"
        type="warning"
        description="Unable to fetch user activity data from server. Showing sample data for demonstration."
        show-icon
        :closable="false"
      />
    </div>
    <el-table 
      v-else 
      :data="filteredActivities" 
      border 
      style="width:100%" 
      :max-height="filteredActivities.length > 10 ? '400px' : 'auto'"
      v-loading="tableLoading"
      @sort-change="handleSortChange"
    >
      <el-table-column v-if="columnVisibility.serial" label="#" width="60" align="center">
        <template #default="{ $index }">{{ $index + 1 }}</template>
      </el-table-column>

      <el-table-column v-if="columnVisibility.photo" label="Photo" width="80" align="center">
        <template #default="{ row }">
          <el-avatar :size="32" :src="row.photo">
            {{ getUserInitials(row.name) }}
          </el-avatar>
        </template>
      </el-table-column>

      <el-table-column v-if="columnVisibility.name" prop="name" label="User" min-width="150" sortable>
        <template #default="{ row }">
          <div class="user-cell">
            <el-avatar :size="24" :src="row.photo" class="user-avatar">
              {{ getUserInitials(row.name) }}
            </el-avatar>
            <span class="user-name">{{ row.name }}</span>
          </div>
        </template>
      </el-table-column>
      
      <el-table-column v-if="columnVisibility.module" prop="module" label="Module" min-width="120" sortable>
        <template #default="{ row }">
          <el-tag type="info" size="small">{{ row.module }}</el-tag>
        </template>
      </el-table-column>

      <el-table-column v-if="columnVisibility.menu" prop="menu" label="Menu" min-width="150" sortable>
        <template #default="{ row }">
          <el-tag type="success" size="small">{{ row.menu }}</el-tag>
        </template>
      </el-table-column>

      <el-table-column v-if="columnVisibility.activity" prop="activity" label="Activity" min-width="100" sortable>
        <template #default="{ row }">
          <el-tag :type="getActivityTagType(row.activity)" size="small">
            {{ row.activity }}
          </el-tag>
        </template>
      </el-table-column>

      <el-table-column v-if="columnVisibility.description" prop="description" label="Description" min-width="200" sortable>
        <template #default="{ row }">
          <span class="description-text">{{ row.description }}</span>
        </template>
      </el-table-column>

      <el-table-column v-if="columnVisibility.created_at" prop="created_at" label="Date & Time" min-width="150" sortable>
        <template #default="{ row }">
          <div class="datetime-cell">
            <div class="date">{{ formatDate(row.created_at) }}</div>
            <div class="time">{{ formatTime(row.created_at) }}</div>
          </div>
        </template>
      </el-table-column>
    </el-table>
    
    <div v-if="!loading && filteredActivities.length === 0" class="no-data">
      <el-empty description="No user activities found" />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  activities: {
    type: Array,
    default: () => []
  },
  loading: {
    type: Boolean,
    default: false
  },
  apiError: {
    type: Boolean,
    default: false
  },
  tableLoading: {
    type: Boolean,
    default: false
  },
  search: {
    type: String,
    default: ''
  },
  columnVisibility: {
    type: Object,
    default: () => ({
      serial: true,
      photo: true,
      name: true,
      module: true,
      menu: true,
      activity: true,
      description: true,
      created_at: true
    })
  }
})

const emit = defineEmits(['sort-change'])

const filteredActivities = computed(() => {
  if (!props.search) return props.activities
  
  return props.activities.filter(activity => {
    const searchLower = props.search.toLowerCase()
    const name = activity.name?.toLowerCase() || ''
    const module = activity.module?.toLowerCase() || ''
    const menu = activity.menu?.toLowerCase() || ''
    const activityType = activity.activity?.toLowerCase() || ''
    const description = activity.description?.toLowerCase() || ''
    
    return name.includes(searchLower) || 
           module.includes(searchLower) || 
           menu.includes(searchLower) || 
           activityType.includes(searchLower) || 
           description.includes(searchLower)
  })
})

function getUserInitials(name) {
  if (!name) return 'U'
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

function getActivityTagType(activity) {
  const typeMap = {
    'Create': 'success',
    'Update': 'warning',
    'Delete': 'danger',
    'Login': 'info',
    'Logout': 'info',
    'View': 'primary'
  }
  return typeMap[activity] || 'primary'
}

function formatDate(dateString) {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', { 
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

function formatTime(dateString) {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleTimeString('en-US', { 
    hour: '2-digit', 
    minute: '2-digit',
    hour12: true 
  })
}

function handleSortChange(sortInfo) {
  emit('sort-change', sortInfo)
}
</script>

<style scoped>
.user-activity-table {
  width: 100%;
}

.loading-placeholder {
  padding: 20px;
}

.error-message {
  margin-bottom: 16px;
}

.no-data {
  padding: 40px;
  text-align: center;
}

.user-cell {
  display: flex;
  align-items: center;
  gap: 8px;
}

.user-avatar {
  flex-shrink: 0;
}

.user-name {
  font-weight: 500;
  color: #303133;
}

.description-text {
  color: #606266;
  font-size: 14px;
  line-height: 1.4;
}

.datetime-cell {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.date {
  font-weight: 500;
  color: #303133;
  font-size: 13px;
}

.time {
  color: #909399;
  font-size: 12px;
}
</style>
