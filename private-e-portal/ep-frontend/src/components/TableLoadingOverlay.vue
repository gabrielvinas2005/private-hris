<template>
  <transition name="table-loading-fade">
    <div v-if="loading" class="table-loading-overlay">
      <div class="table-skeleton-content">
        <div class="skeleton-header">
          <div class="skeleton-pill w-1/4"></div>
          <div class="skeleton-pill w-1/6"></div>
          <div class="skeleton-pill w-1/5"></div>
        </div>
        <div class="skeleton-rows">
          <div v-for="i in rows" :key="i" class="skeleton-row">
            <div class="skeleton-bar w-1/5"></div>
            <div class="skeleton-bar w-1/4"></div>
            <div class="skeleton-bar w-1/6"></div>
            <div class="skeleton-bar w-1/5"></div>
          </div>
        </div>
        <div v-if="text" class="table-loading-text">
          <span class="skeleton-dot"></span>
          {{ text }}
        </div>
      </div>
    </div>
  </transition>
</template>

<script setup>
defineProps({
  loading: { type: Boolean, default: false },
  text: {
    type: String,
    default: 'Loading...'
  },
  rows: {
    type: Number,
    default: 5
  }
})
</script>

<style scoped>
.table-loading-overlay {
  position: absolute;
  inset: 0;
  background-color: rgba(255, 255, 255, 0.95);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 20;
  padding: 1.5rem;
}

.table-skeleton-content {
  width: 100%;
  max-width: 900px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.skeleton-header {
  display: flex;
  gap: 12px;
  padding-bottom: 8px;
  border-bottom: 1px solid #e2e8f0;
}

.skeleton-rows {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.skeleton-row {
  display: flex;
  gap: 12px;
  align-items: center;
}

.skeleton-pill,
.skeleton-bar {
  height: 18px;
  border-radius: 6px;
  background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
  background-size: 200% 100%;
  animation: skeleton-shimmer 1.5s infinite;
}

.skeleton-pill {
  height: 22px;
  background-color: #e2e8f0;
}

.table-loading-text {
  font-size: 13px;
  color: #64748b;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  margin-top: 6px;
}

.skeleton-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background-color: #3b82f6;
  animation: pulse-dot 1.2s infinite ease-in-out;
}

@keyframes skeleton-shimmer {
  0% {
    background-position: 200% 0;
  }
  100% {
    background-position: -200% 0;
  }
}

@keyframes pulse-dot {
  0%, 100% {
    opacity: 0.4;
    transform: scale(0.8);
  }
  50% {
    opacity: 1;
    transform: scale(1.1);
  }
}

.table-loading-fade-enter-active,
.table-loading-fade-leave-active {
  transition: opacity 0.2s ease;
}

.table-loading-fade-enter-from,
.table-loading-fade-leave-to {
  opacity: 0;
}
</style>
