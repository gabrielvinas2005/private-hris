<template>
  <div v-if="isLoading" class="shared-auth-loading-overlay">
    <div class="shared-auth-loading-container">
      <div class="loading-spinner">
        <div class="spinner"></div>
      </div>
      <div class="loading-content">
        <h3 class="loading-title">{{ title }}</h3>
        <p class="loading-message">{{ message }}</p>
        <div class="loading-progress">
          <div class="progress-bar" :style="{ width: progress + '%' }"></div>
        </div>
        <p class="loading-subtitle">{{ subtitle }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { defineProps } from 'vue'

const props = defineProps({
  isLoading: {
    type: Boolean,
    default: false
  },
  title: {
    type: String,
    default: 'Authenticating...'
  },
  message: {
    type: String,
    default: 'Please wait while we securely authenticate you with the HR Module (201 Files).'
  },
  subtitle: {
    type: String,
    default: 'This ensures seamless access across all systems'
  },
  progress: {
    type: Number,
    default: 0
  }
})
</script>

<style scoped>
.shared-auth-loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.8);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 9999;
  backdrop-filter: blur(4px);
}

.shared-auth-loading-container {
  background: white;
  border-radius: 16px;
  padding: 40px;
  text-align: center;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
  max-width: 400px;
  width: 90%;
  animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(-20px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

.loading-spinner {
  margin-bottom: 24px;
}

.spinner {
  width: 48px;
  height: 48px;
  border: 4px solid #e5e7eb;
  border-top: 4px solid #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.loading-content {
  color: #374151;
}

.loading-title {
  font-size: 20px;
  font-weight: 600;
  margin: 0 0 12px 0;
  color: #1f2937;
}

.loading-message {
  font-size: 14px;
  color: #6b7280;
  margin: 0 0 20px 0;
  line-height: 1.5;
}

.loading-progress {
  width: 100%;
  height: 4px;
  background: #e5e7eb;
  border-radius: 2px;
  overflow: hidden;
  margin-bottom: 16px;
}

.progress-bar {
  height: 100%;
  background: linear-gradient(90deg, #3b82f6, #1d4ed8);
  border-radius: 2px;
  transition: width 0.3s ease;
  animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.7; }
}

.loading-subtitle {
  font-size: 12px;
  color: #9ca3af;
  margin: 0;
  font-style: italic;
}

/* Responsive design */
@media (max-width: 480px) {
  .shared-auth-loading-container {
    padding: 24px;
    margin: 20px;
  }
  
  .loading-title {
    font-size: 18px;
  }
  
  .loading-message {
    font-size: 13px;
  }
}
</style>
