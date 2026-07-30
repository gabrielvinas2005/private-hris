<script setup>
import MainLayout from "./Layout/MainLayout.vue";
import { useAuth } from "./Composables/useAuth";
import { onMounted, ref } from "vue";
import SharedAuthLoading from "./components/SharedAuthLoading.vue";
import LoginModal from "./components/loginModal.vue";

const isEPortalRedirect =
  new URLSearchParams(window.location.search).get("redirect_from") ===
  "e_portal";

const { isAuthenticated, initAuth } = useAuth();
const isAuthenticating = ref(isEPortalRedirect);
const authProgress = ref(0);
const showLoginModal = ref(false);

onMounted(async () => {
  if (isEPortalRedirect) {
    isAuthenticating.value = true;

    const progressInterval = setInterval(() => {
      if (authProgress.value < 90) {
        authProgress.value += Math.random() * 20;
      }
    }, 200);

    await initAuth();

    authProgress.value = 100;
    setTimeout(() => {
      isAuthenticating.value = false;
      clearInterval(progressInterval);

      if (!isAuthenticated.value) {
        showLoginModal.value = true;
      }
    }, 500);
    return;
  }

  await initAuth();

  if (!isAuthenticated.value) {
    showLoginModal.value = true;
  }
});

const handleLoginSuccess = () => {
  showLoginModal.value = false;
};
</script>

<template>
  <div>
    <!-- Shared Authentication Loading -->
    <SharedAuthLoading
      :isLoading="isAuthenticating"
      title="Connecting to Payroll Module..."
      message="Please wait while we securely authenticate you with the Payroll Module."
      subtitle="This ensures seamless access across all systems"
      :progress="authProgress"
    />

    <!-- Main Application -->
    <MainLayout v-if="isAuthenticated && !isAuthenticating">
      <template #header>Payroll Module</template>
      <RouterView />
    </MainLayout>

    <!-- Login Required -->
    <div v-else-if="!isAuthenticating" class="login-required">
      <div class="login-message">
        <h2>Please login to continue</h2>
        <p>Authentication is required to access the application.</p>
      </div>
    </div>

    <!-- Login Modal -->
    <LoginModal v-model="showLoginModal" @login-success="handleLoginSuccess" />
  </div>
</template>

<style scoped>
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
}
</style>
