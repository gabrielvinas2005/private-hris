<template>
    <transition name="table-loading-fade">
      <div v-if="loading" class="table-loading-overlay">
        <div class="table-loading-content">
          <el-icon class="table-loading-spinner">
            <Loading />
          </el-icon>
          <div class="table-loading-text">
            {{ text }}
          </div>
        </div>
      </div>
    </transition>
  </template>
  
  <script setup>
  import { Loading } from '@element-plus/icons-vue'
  
  defineProps({
    loading: { type: Boolean, default: false },
    text: {
      type: String,
      default: 'Loading...'
    }
  })
  </script>
  
  <style scoped>
  .table-loading-overlay {
    position: absolute;
    inset: 0;
    background-color: #ffffff;
    opacity: 0.95;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 20;
  }
  
  .table-loading-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    color: #606266;
  }
  
  .table-loading-spinner {
    font-size: 28px;
    color: #409EFF;
    animation: table-loading-spin 1s linear infinite;
  }
  
  .table-loading-text {
    font-size: 14px;
  }
  
  @keyframes table-loading-spin {
    0% {
      transform: rotate(0deg);
    }
    100% {
      transform: rotate(360deg);
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
  
  