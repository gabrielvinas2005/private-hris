<script setup>
import { onMounted, ref } from 'vue'
import MainLayout from './Layout/MainLayout.vue'
import { useAuth } from '@/Composables/useAuth'
import SharedAuthLoading from './components/SharedAuthLoading.vue'
import AuthOptions from './components/AuthOptions.vue'

// Composables
const { isAuthenticated, initAuth } = useAuth()
const isAuthenticating = ref(false)
const authProgress = ref(0)

// Lifecycle
onMounted(() => {
  // Show authentication loading if coming from E-Portal
  const urlParams = new URLSearchParams(window.location.search)
  const redirectFrom = urlParams.get('redirect_from')
  
  if (redirectFrom === 'e_portal') {
    isAuthenticating.value = true
    
    // Simulate authentication progress
    const progressInterval = setInterval(() => {
      if (authProgress.value < 90) {
        authProgress.value += Math.random() * 20
      }
    }, 200)
    
    // Initialize auth state
    initAuth()
    
    // Complete authentication process
    setTimeout(() => {
      authProgress.value = 100
      setTimeout(() => {
        isAuthenticating.value = false
        clearInterval(progressInterval)
      }, 500)
    }, 1500)
  } else {
    // Normal initialization for direct access
    initAuth()
  }
})
</script>

<template>
  <div>
    <!-- Shared Authentication Loading -->
    <SharedAuthLoading 
      :isLoading="isAuthenticating"
      title="Connecting to Timekeeping Module..."
      message="Please wait while we securely authenticate you with the Timekeeping Module."
      subtitle="This ensures seamless access across all systems"
      :progress="authProgress"
    />
    
    <!-- Main Application -->
    <MainLayout v-if="isAuthenticated && !isAuthenticating">
      <template #header>Timekeeping Module</template>
      <RouterView />
    </MainLayout>
    
    <!-- Login Required -->
    <div v-else-if="!isAuthenticating" class="login-required">
      <div class="login-message">
        <h2>Please login to continue</h2>
        <p>Authentication is required to access the application.</p>
        <AuthOptions />
      </div>
    </div>
  </div>
</template>

<style scoped>
.login-required {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 0.5rem;
  box-sizing: border-box;
}

.login-message {
  text-align: center;
  color: white;
  padding: 1rem;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 10px;
  backdrop-filter: blur(10px);
  max-width: 100%;
  box-sizing: border-box;
}

.login-message h2 {
  margin-bottom: 0.5rem;
  font-size: 1.5rem;
}

.login-message p {
  font-size: 0.9rem;
  opacity: 0.9;
  margin-bottom: 0;
}
</style>
