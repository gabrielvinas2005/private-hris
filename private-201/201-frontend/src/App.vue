<script setup>
import { onMounted, onBeforeUnmount, ref, nextTick } from 'vue'
import MainLayout from './Layout/MainLayout.vue'
import { useAuth } from './composable/useAuth'
import SharedAuthLoading from './components/SharedAuthLoading.vue'
import { ElMessage } from 'element-plus'
import LoginModal from './components/LoginModal.vue'

// Composables
const { initAuth } = useAuth()
const isAuthenticating = ref(false)
const isBootstrapping = ref(true)
const authProgress = ref(0)
const authError = ref(null)
const isAuthenticatedLocal = ref(false)
const enableDevLogin =
  (import.meta.env.DEV && import.meta.env.VITE_ENABLE_DEV_LOGIN !== 'false') ||
  import.meta.env.VITE_ENABLE_DEV_LOGIN === 'true'
const showLoginModal = ref(false)

const handleTokenExpired = () => {
  isAuthenticatedLocal.value = false
  openDevLoginModal()
}

const openDevLoginModal = async () => {
  if (!enableDevLogin) return
  await nextTick()
  showLoginModal.value = true
}

const finalizeAuthState = (isAuthenticated, options = {}) => {
  const { promptLogin = false } = options
  isAuthenticatedLocal.value = !!isAuthenticated

  if (!isAuthenticated && enableDevLogin && promptLogin) {
    openDevLoginModal()
  }
}

const runAuthInitialization = async () => {
  try {
    const authSuccess = await initAuth()
    finalizeAuthState(authSuccess)
    return authSuccess
  } catch (error) {
    finalizeAuthState(false)
    throw error
  }
}

const handleOpenLogin = () => {
  openDevLoginModal()
}

const handleDevLoginSuccess = async () => {
  showLoginModal.value = false
  try {
    const recheck = await initAuth()
    finalizeAuthState(recheck, { promptLogin: !recheck })
    if (!recheck) {
      ElMessage.warning('Login succeeded but verification failed. Please try again.')
    }
  } catch (error) {
    console.error('Post-login verification failed:', error)
    finalizeAuthState(false, { promptLogin: true })
    ElMessage.error('Unable to verify session after login.')
  }
}

// Lifecycle
onMounted(async () => {
  window.addEventListener('token-expired', handleTokenExpired)

  // Show authentication loading if coming from E-Portal
  const urlParams = new URLSearchParams(window.location.search)
  const redirectFrom = urlParams.get('redirect_from')
  
  if (redirectFrom === 'e_portal') {
    isAuthenticating.value = true
    authError.value = null
    
    // Progress simulation
    const progressInterval = setInterval(() => {
      if (authProgress.value < 90 && isAuthenticating.value) {
        authProgress.value += Math.random() * 10
      }
    }, 200)
    
    try {
      // Initialize and verify authentication
      const authSuccess = await runAuthInitialization()
      
      if (authSuccess) {
        // Authentication confirmed
        authProgress.value = 100
        await new Promise(resolve => setTimeout(resolve, 500))
        isAuthenticating.value = false
        isBootstrapping.value = false
        clearInterval(progressInterval)
      } else {
        // Authentication failed
        authError.value = 'Authentication failed. Please try again.'
        // Keep loading state - don't hide it
        clearInterval(progressInterval)
        // Optionally show error and redirect to login
        setTimeout(() => {
          // You can redirect to login page or show error message
          ElMessage.error('Authentication failed. Please login again.')
          // Keep loading visible until user manually closes or redirects
        }, 1000)
      }
    } catch (error) {
      console.error('Authentication error:', error)
      authError.value = error.message || 'Authentication failed'
      clearInterval(progressInterval)
      // Keep loading state on error
    }
  } else {
    // Normal initialization for direct access
    let authOk = false
    try {
      isAuthenticating.value = true
      authError.value = null
      authProgress.value = 30
      authOk = await runAuthInitialization()
      authProgress.value = 100
    } catch (error) {
      console.error('Auth initialization error:', error)
      authOk = false
    } finally {
      isAuthenticating.value = false
      isBootstrapping.value = false
      finalizeAuthState(authOk, { promptLogin: !authOk })
    }
  }
})

onBeforeUnmount(() => {
  window.removeEventListener('token-expired', handleTokenExpired)
})
</script>

<template>
  <div id="app">
    <!-- Shared Authentication Loading -->
    <SharedAuthLoading 
      v-if="isAuthenticating || isBootstrapping"
      :isLoading="isAuthenticating || isBootstrapping"
      :title="authError ? 'Authentication Failed' : 'Connecting to HR Module (201 Files)...'"
      :message="authError || 'Please wait while we securely authenticate you with the HR Module (201 Files).'"
      subtitle="This ensures seamless access across all systems"
      :progress="authProgress"
    />
    
    <!-- Main Application -->
    <MainLayout v-if="isAuthenticatedLocal && !isAuthenticating && !isBootstrapping">
      <template #header>Overview</template>
      <RouterView />
    </MainLayout>
    
    <!-- Login Required -->
    <div v-else-if="!isAuthenticating && !isBootstrapping" class="login-required">
      <div class="login-message">
        <h2>Please login to continue</h2>
        <p>Authentication is required to access the application.</p>
        <el-button
          v-if="enableDevLogin"
          type="primary"
          size="large"
          class="login-action-btn"
          @click="handleOpenLogin"
        >
          Login
        </el-button>
      </div>
    </div>

    <LoginModal
      v-if="enableDevLogin"
      v-model="showLoginModal"
      @login-success="handleDevLoginSuccess"
    />
  </div>
</template>

<style scoped>
#app {
  min-height: 100vh;
}

.login-required {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.login-message {
  text-align: center;
  color: white;
  padding: 2rem;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 1rem;
  backdrop-filter: blur(10px);
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
}

.login-message h2 {
  margin-bottom: 1rem;
  font-size: 2rem;
  font-weight: 600;
}

.login-message p {
  margin-bottom: 1rem;
  opacity: 0.9;
}

.login-action-btn {
  margin-top: 0.5rem;
  min-width: 140px;
}
</style>

