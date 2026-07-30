<script setup>
import { onMounted, ref, watch, computed } from 'vue'
import { useAuth } from './composables/useAuth.js'
import SharedAuthLoading from './components/SharedAuthLoading.vue'
import LoginPopup from './components/LoginPopup.vue'
import { ElMessage } from 'element-plus'

const { isAuthenticated, initializeAuth, checkSharedAuth, login } = useAuth()
const isInitializing = ref(true)
const isAuthenticating = ref(false)
const authProgress = ref(0)
const authError = ref(null)
const showLoginPopup = ref(false)

// Check if in development mode
const isDevelopment = computed(() => {
  const isDev = import.meta.env.DEV || import.meta.env.MODE === 'development'
  console.log('Development mode check:', {
    DEV: import.meta.env.DEV,
    MODE: import.meta.env.MODE,
    isDev
  })
  return isDev
})

// Initialize authentication on app start
onMounted(async () => {
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
      const authSuccess = await initializeAuth()

      if (authSuccess && isAuthenticated.value) {
        // Authentication confirmed
        authProgress.value = 100
        await new Promise(resolve => setTimeout(resolve, 500))
        isAuthenticating.value = false
        isInitializing.value = false
        clearInterval(progressInterval)
      } else {
        // Authentication failed
        authError.value = 'Authentication failed. Please try again.'
        clearInterval(progressInterval)
        isInitializing.value = false
        // Keep loading state - show error
        setTimeout(() => {
          ElMessage.error('Authentication failed. Please login again.')
        }, 1000)
      }
    } catch (error) {
      console.error('Authentication error:', error)
      authError.value = error.message || 'Authentication failed'
      clearInterval(progressInterval)
      isInitializing.value = false
      // Keep loading state on error
    }
  } else {
    // Normal initialization for direct access
    try {
      await initializeAuth()
    } catch (error) {
      console.error('Auth initialization error:', error)
    }
    
    isInitializing.value = false
    
    // In development mode, show login popup if not authenticated
    // Use a small delay to ensure reactive state is fully updated
    await new Promise(resolve => setTimeout(resolve, 300))
    
    const shouldShowPopup = isDevelopment.value && !isAuthenticated.value
    console.log('Dev login popup check:', {
      isDevelopment: isDevelopment.value,
      isAuthenticated: isAuthenticated.value,
      shouldShowPopup,
      hasToken: !!localStorage.getItem('auth_token'),
      hasUser: !!localStorage.getItem('user_data')
    })
    
    if (shouldShowPopup) {
      console.log('✅ Showing dev login popup')
      showLoginPopup.value = true
    }
  }
})

// Watch for authentication changes - show popup in dev if user logs out
watch(isAuthenticated, (authenticated) => {
  console.log('Auth state changed:', authenticated, 'Dev mode:', isDevelopment.value)
  if (isDevelopment.value && !authenticated && !isInitializing.value && !isAuthenticating.value) {
    console.log('Showing popup due to auth state change')
    // Small delay to ensure UI is ready
    setTimeout(() => {
      showLoginPopup.value = true
    }, 100)
  } else if (authenticated) {
    showLoginPopup.value = false
  }
}, { immediate: true })

// Watch for when login-required screen appears - auto-show popup in dev
watch([isInitializing, isAuthenticating], ([init, auth]) => {
  if (isDevelopment.value && !init && !auth && !isAuthenticated.value) {
    console.log('Login-required screen shown, auto-showing popup in dev mode')
    setTimeout(() => {
      if (!isAuthenticated.value) {
        showLoginPopup.value = true
      }
    }, 500)
  }
})

// Handle login success from popup
function handleLoginSuccess(loginData) {
  // The login data is already stored in localStorage by LoginPopup
  // Just update the auth state and close popup
  if (loginData && loginData.token) {
    // Refresh auth state
    initializeAuth().then(() => {
      showLoginPopup.value = false
    })
  } else {
    showLoginPopup.value = false
  }
}
</script>

<template>
  <!-- Shared Authentication Loading -->
  <SharedAuthLoading 
    v-if="isAuthenticating"
    :isLoading="isAuthenticating"
    :title="authError ? 'Authentication Failed' : 'Connecting to Control Panel...'"
    :message="authError || 'Please wait while we securely authenticate you with the Control Panel module.'"
    subtitle="This ensures seamless access across all systems"
    :progress="authProgress"
  />
  
  <!-- Normal Loading Screen -->
  <div v-if="isInitializing && !isAuthenticating" class="loading-screen">
    <div class="loading-spinner"></div>
    <p>Initializing...</p>
  </div>
  
  <!-- Main Application -->
  <RouterView v-else-if="isAuthenticated && !isAuthenticating" />
  
  <!-- Login Required -->
  <div v-else-if="!isAuthenticating" class="login-required" v-show="true">
    <div class="login-message">
      <h2>Please login to continue</h2>
      <p>Authentication is required to access the application.</p>
      <p v-if="!isDevelopment">If you came from E-Portal, please try refreshing the page.</p>
      <p v-else>Click the login button below to access the application.</p>
      <el-button 
        v-if="isDevelopment" 
        @click="showLoginPopup = true" 
        type="primary" 
        size="large"
        style="margin-top: 20px;"
      >
        Login
      </el-button>
    </div>
  </div>
  
  <!-- Login Popup - Auto-shown in dev mode when not authenticated -->
  <LoginPopup 
    v-model="showLoginPopup"
    @login-success="handleLoginSuccess"
  />
</template>

<style scoped>
.loading-screen {
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.loading-spinner {
  width: 40px;
  height: 40px;
  border: 4px solid rgba(255, 255, 255, 0.3);
  border-top: 4px solid white;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 1rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.login-required {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.login-message {
  text-align: center;
  color: white;
  padding: 2rem;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 10px;
  backdrop-filter: blur(10px);
}

.login-message h2 {
  margin-bottom: 1rem;
  font-size: 2rem;
}

.login-message p {
  font-size: 1.1rem;
  opacity: 0.9;
  margin-bottom: 0.5rem;
}
</style>