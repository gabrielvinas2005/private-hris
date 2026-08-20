<template>
  <div class="announcement-list">
    <div v-if="title" class="mb-4">
      <h3 class="text-lg font-semibold text-gray-900">{{ title }}</h3>
      <p v-if="subtitle" class="text-sm text-gray-600 mt-1">{{ subtitle }}</p>
    </div>

    <div v-if="displayItems.length > 0" class="notifications-container">
      <div 
        v-for="(a, index) in displayItems" 
        :key="index" 
        class="notification-item"
      >
        <div class="notification-bell">
          <svg class="bell-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM11 19a7 7 0 01-7-7v-3a4 4 0 014-4h6a4 4 0 014 4v3a7 7 0 01-7 7z"></path>
          </svg>
        </div>
        <div class="notification-content">
          <div class="notification-header">
            <h4 class="notification-title">
              {{ a.Title || a.title || 'Untitled' }}
            </h4>
            <span v-if="a.employee_id" class="notification-badge personal">Personal</span>
            <span v-else class="notification-badge global">Global</span>
          </div>
          <div class="notification-body">
            <p v-if="a.Event || a.content" class="notification-text">
              {{ a.Event || a.content }}
            </p>
            <div v-else class="notification-placeholder">No additional content</div>
          </div>
          <div v-if="showDates && (a.Creted_at || a.created_at)" class="notification-time">
            {{ formatDate(a.Creted_at || a.created_at) }}
          </div>
        </div>
      </div>
    </div>

    <div v-else class="empty-notifications">
      <div class="empty-bell">🔔</div>
      <p class="empty-text">{{ emptyText }}</p>
    </div>
  </div>
</template>

<script>
export default {
  name: 'AnnouncementList',
  props: {
    items: {
      type: Array,
      default: () => []
    },
    announcements: {
      type: Array,
      default: () => []
    },
    limit: {
      type: Number,
      default: 0
    },
    showDates: {
      type: Boolean,
      default: true
    },
    emptyText: {
      type: String,
      default: 'No announcements available'
    },
    title: {
      type: String,
      default: ''
    },
    subtitle: {
      type: String,
      default: ''
    }
  },
  computed: {
    itemList() {
      if (Array.isArray(this.items) && this.items.length > 0) return this.items
      if (Array.isArray(this.announcements) && this.announcements.length > 0) return this.announcements
      return []
    },
    displayItems() {
      return this.limit > 0 ? this.itemList.slice(0, this.limit) : this.itemList
    }
  },
  methods: {
    formatDate(dateString) {
      if (!dateString) return ''
      
      try {
        const date = new Date(dateString)
        if (isNaN(date.getTime())) return ''
        
        return date.toLocaleDateString('en-US', {
          year: 'numeric',
          month: 'short',
          day: 'numeric'
        })
      } catch (error) {
        return ''
      }
    }
  }
}
</script>

<style scoped>
.announcement-list {
  width: 100%;
}

.notifications-container {
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
  overflow: hidden;
}

.notification-item {
  display: flex;
  align-items: flex-start;
  padding: 20px;
  border-bottom: 1px solid #f3f4f6;
  transition: all 0.2s ease;
  position: relative;
}

.notification-item:last-child {
  border-bottom: none;
}

.notification-item:hover {
  background-color: #f9fafb;
}

.notification-bell {
  flex-shrink: 0;
  width: 48px;
  height: 48px;
  background: linear-gradient(135deg, #3b82f6, #1d4ed8);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 16px;
  box-shadow: 0 2px 4px rgba(59, 130, 246, 0.3);
}

.bell-icon {
  width: 24px;
  height: 24px;
  color: white;
}

.notification-content {
  flex: 1;
  min-width: 0;
}

.notification-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  margin-bottom: 8px;
}

.notification-title {
  font-size: 1rem;
  font-weight: 600;
  color: #111827;
  line-height: 1.4;
  margin: 0;
  flex: 1;
  margin-right: 12px;
}

.notification-badge {
  display: inline-block;
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  flex-shrink: 0;
}

.notification-badge.personal {
  background-color: #dbeafe;
  color: #1e40af;
}

.notification-badge.global {
  background-color: #dcfce7;
  color: #166534;
}

.notification-body {
  margin-bottom: 12px;
}

.notification-text {
  color: #4b5563;
  font-size: 0.875rem;
  line-height: 1.5;
  margin: 0;
}

.notification-placeholder {
  color: #9ca3af;
  font-size: 0.875rem;
  font-style: italic;
}

.notification-time {
  color: #6b7280;
  font-size: 0.75rem;
  font-weight: 500;
}

.empty-notifications {
  text-align: center;
  padding: 60px 20px;
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.empty-bell {
  font-size: 4rem;
  margin-bottom: 16px;
  opacity: 0.5;
}

.empty-text {
  color: #6b7280;
  font-size: 0.875rem;
  margin: 0;
}

/* Animation for new notifications */
.notification-item {
  animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateX(-20px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

/* Responsive design */
@media (max-width: 640px) {
  .notification-item {
    padding: 16px;
  }
  
  .notification-bell {
    width: 40px;
    height: 40px;
    margin-right: 12px;
  }
  
  .bell-icon {
    width: 20px;
    height: 20px;
  }
  
  .notification-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
  }
  
  .notification-title {
    margin-right: 0;
  }
}
</style>


