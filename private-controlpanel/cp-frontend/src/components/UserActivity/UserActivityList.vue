<template>
  <div class="activity-list">
    <div v-if="loading" class="loading-placeholder">
      <el-skeleton :rows="3" animated />
    </div>
    <div v-else-if="apiError" class="error-message">
      <el-alert
        title="API Error"
        type="warning"
        description="Unable to fetch recent activities from server. Showing sample data for demonstration."
        show-icon
        :closable="false"
      />
    </div>
    <el-timeline v-else>
      <el-timeline-item
        v-for="activity in recentActivities"
        :key="activity.id"
        :timestamp="formatTime(activity.created_at)"
        :type="getActivityType(activity.activity)"
      >
        <div class="item">
          <div class="user-info">
            <el-avatar :size="24" :src="activity.photo" class="user-avatar">
              {{ getUserInitials(activity.name) }}
            </el-avatar>
            <strong class="user-name">{{ activity.name }}</strong>
          </div>
          <div class="activity-details">
            <span class="activity-text">{{ activity.activity }}</span>
            <span class="activity-description">{{ activity.description }}</span>
          </div>
          <div class="activity-meta">
            <span class="module">{{ activity.module }}</span>
            <span class="menu">{{ activity.menu }}</span>
          </div>
        </div>
      </el-timeline-item>
    </el-timeline>
    
    <div v-if="!loading && recentActivities.length === 0" class="no-data">
      <el-empty description="No recent activities found" />
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
  }
})

const recentActivities = computed(() => {
  return props.activities.slice(0, 5) // Show only recent 5 activities
})

function formatTime(dateString) {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleTimeString('en-US', { 
    hour: '2-digit', 
    minute: '2-digit',
    hour12: true 
  })
}

function getActivityType(activity) {
  const typeMap = {
    'Create': 'success',
    'Update': 'warning',
    'Delete': 'danger',
    'Login': 'info',
    'Logout': 'info'
  }
  return typeMap[activity] || 'primary'
}

function getUserInitials(name) {
  if (!name) return 'U'
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}
</script>

<style scoped>
.activity-list {
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

.item {
  padding: 8px 0;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 4px;
}

.user-avatar {
  flex-shrink: 0;
}

.user-name {
  color: #409eff;
  font-size: 14px;
}

.activity-details {
  margin-bottom: 4px;
}

.activity-text {
  font-weight: 600;
  color: #303133;
  margin-right: 8px;
}

.activity-description {
  color: #606266;
  font-size: 13px;
}

.activity-meta {
  display: flex;
  gap: 8px;
  font-size: 12px;
}

.module {
  background: #f0f9ff;
  color: #0369a1;
  padding: 2px 6px;
  border-radius: 4px;
}

.menu {
  background: #f0fdf4;
  color: #166534;
  padding: 2px 6px;
  border-radius: 4px;
}
</style>
