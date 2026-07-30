<template>
  <div v-if="showDevAuth" class="dev-auth">
    <el-alert
      title="Development Mode"
      type="info"
      :closable="false"
      show-icon
    >
      <template #default>
        <p>Running in development mode. Choose authentication method:</p>
        <div class="auth-buttons">
          <el-button @click="usePublicRoutes" type="success">
            Use Public Routes (Recommended)
          </el-button>
          <el-button @click="useMockAuth" type="warning">
            Use Mock Authentication
          </el-button>
        </div>
      </template>
    </el-alert>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import mockAuth from '../Services/mockAuth.js'

const showDevAuth = ref(false)

onMounted(() => {
  // Only show in development mode
  if (import.meta.env.DEV) {
    showDevAuth.value = true
  }
})

function usePublicRoutes() {
  ElMessage.success('Using public routes - no authentication required!')
  showDevAuth.value = false
}

async function useMockAuth() {
  try {
    await mockAuth.autoLogin()
    ElMessage.success('Mock authentication enabled!')
    showDevAuth.value = false
  } catch (error) {
    ElMessage.error('Failed to enable mock authentication')
  }
}
</script>

<style scoped>
.dev-auth {
  position: fixed;
  top: 20px;
  right: 20px;
  z-index: 9999;
  max-width: 400px;
}

.auth-buttons {
  margin-top: 12px;
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.auth-buttons .el-button {
  flex: 1;
  min-width: 120px;
}
</style>
