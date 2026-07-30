<template>
  <div class="auth-options">
    <div class="auth-section">
      <h3>Regular Login (E-Portal Integration)</h3>
      <p class="shared-description">
        Login through E-Portal integration with shared authentication parameters
      </p>
      <div class="shared-auth-info">
        <p><strong>How it works:</strong></p>
        <ul>
          <li>Access the application through E-Portal</li>
          <li>E-Portal passes authentication parameters via URL</li>
          <li>SharedAuth middleware automatically logs you in</li>
          <li>No manual credentials required</li>
        </ul>
      </div>
      <div class="shared-auth-status">
        <p><strong>Current Status:</strong></p>
        <p v-if="hasSharedAuthParams" class="status-success">
          ✅ Shared auth parameters detected - automatic login available
        </p>
        <p v-else class="status-info">
          ℹ️ No shared auth parameters - access through E-Portal required
        </p>
      </div>
    </div>

    <div class="divider">
      <span>OR</span>
    </div>

    <div class="auth-section">
      <h3>Development Login</h3>
      <p class="dev-description">
        Manual login for development using admin credentials
      </p>
      <form @submit.prevent="handleDevLogin" class="login-form">
        <div class="form-group">
          <label for="dev-email">Email:</label>
          <input 
            id="dev-email"
            v-model="devCredentials.email" 
            type="email" 
            placeholder="Enter admin email"
            required
          />
        </div>
        <div class="form-group">
          <label for="dev-password">Password:</label>
          <div class="password-input-wrapper">
            <input 
              id="dev-password"
              v-model="devCredentials.password" 
              :type="showPassword ? 'text' : 'password'" 
              placeholder="Enter admin password"
              required
            />
            <button 
              type="button" 
              class="password-toggle-btn"
              @click="togglePasswordVisibility"
              :title="showPassword ? 'Hide password' : 'Show password'"
            >
              <span class="eye-icon" :class="{ 'eye-slash': showPassword }">
                {{ showPassword ? '🙈' : '👁️' }}
              </span>
            </button>
          </div>
        </div>
        <button type="submit" :disabled="loading || !devAuthAvailable" class="dev-login-btn">
          {{ loading ? 'Logging in...' : 'Dev Login' }}
        </button>
      </form>
      <p v-if="!devAuthAvailable" class="dev-unavailable">
        Dev authentication is only available in development mode
      </p>
    </div>

    <div v-if="isAuthenticated" class="logout-section">
      <div class="user-info">
        <h4>Logged in as:</h4>
        <p><strong>{{ user?.name }}</strong> ({{ user?.email }})</p>
        <p v-if="user?.is_admin" class="admin-badge">Administrator</p>
        <p v-if="isSharedAuth" class="shared-badge">E-Portal Integration</p>
      </div>
      <button @click="handleLogout" :disabled="loading" class="logout-btn">
        {{ loading ? 'Logging out...' : 'Logout' }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useAuth } from '@/Composables/useAuth'
import { devAuthService } from '@/services/devAuth'

// Composables
const { 
  user, 
  loading, 
  isAuthenticated, 
  login, 
  devLogin,
  devLogout, 
  clearAuth 
} = useAuth()

// Reactive data
const devCredentials = ref({
  email: '',
  password: ''
})

const showPassword = ref(false)

// Computed properties
const devAuthAvailable = computed(() => {
  return devAuthService.shouldUseDevAuth()
})

const hasSharedAuthParams = computed(() => {
  const urlParams = new URLSearchParams(window.location.search);
  const employeeNo = urlParams.get('employee_no');
  const email = urlParams.get('email');
  const authToken = urlParams.get('auth_token');
  const redirectFrom = urlParams.get('redirect_from');
  
  return !!(employeeNo && email && authToken && redirectFrom === 'e_portal');
})

const isSharedAuth = computed(() => {
  // Check if current authentication is from shared auth (E-Portal)
  const tokenData = localStorage.getItem('auth_token');
  if (!tokenData) return false;
  
  try {
    const parsed = JSON.parse(tokenData);
    // If it's a regular auth token (not dev auth), it's likely from shared auth
    return !devAuthService.isDevAuthValid();
  } catch (error) {
    return false;
  }
})

// Methods
const togglePasswordVisibility = () => {
  showPassword.value = !showPassword.value
}

const handleDevLogin = async () => {
  try {
    await devLogin(devCredentials.value)
  } catch (error) {
    // Error handling is done in the useAuth composable
  }
}

const handleLogout = async () => {
  try {
    // Try dev logout first, then regular logout
    if (devAuthService.isDevAuthValid()) {
      await devLogout()
    } else {
      await clearAuth()
    }
  } catch (error) {
    // Fallback to clear auth
    clearAuth()
  }
}
</script>

<style scoped>
.auth-options {
  max-width: 500px;
  margin: 0 auto;
  padding: 0.5rem;
}

.auth-section {
  background: rgba(255, 255, 255, 0.1);
  border-radius: 10px;
  padding: 0.5rem;
  margin-bottom: 0.5rem;
  backdrop-filter: blur(10px);
}

.auth-section h3 {
  color: white;
  margin-bottom: 0.5rem;
  font-size: 0.8rem;
}

.login-form {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.form-group label {
  color: white;
  font-weight: 500;
}

.form-group input {
  padding: 0.5rem;
  border: 1px solid rgba(255, 255, 255, 0.3);
  border-radius: 6px;
  background: rgba(255, 255, 255, 0.1);
  color: white;
  font-size: 0.9rem;
}

.form-group input::placeholder {
  color: rgba(255, 255, 255, 0.7);
}

.form-group input:focus {
  outline: none;
  border-color: rgba(255, 255, 255, 0.6);
  background: rgba(255, 255, 255, 0.15);
}

.password-input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.password-input-wrapper input {
  width: 100%;
  padding-right: 2.5rem; /* Make space for the eye icon */
}

.password-toggle-btn {
  position: absolute;
  right: 0.5rem;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  cursor: pointer;
  padding: 0.25rem;
  border-radius: 4px;
  transition: background-color 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 2rem;
  height: 2rem;
}

.password-toggle-btn:hover {
  background: rgba(255, 255, 255, 0.1);
}

.password-toggle-btn:focus {
  outline: none;
  background: rgba(255, 255, 255, 0.15);
}

.eye-icon {
  font-size: 1rem;
  color: rgba(255, 255, 255, 0.7);
  transition: color 0.2s ease;
}

.password-toggle-btn:hover .eye-icon {
  color: rgba(255, 255, 255, 0.9);
}

.login-btn, .dev-login-btn, .logout-btn {
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 6px;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.login-btn {
  background: #4f46e5;
  color: white;
}

.login-btn:hover:not(:disabled) {
  background: #4338ca;
}

.dev-login-btn {
  background: #059669;
  color: white;
}

.dev-login-btn:hover:not(:disabled) {
  background: #047857;
}

.logout-btn {
  background: #dc2626;
  color: white;
}

.logout-btn:hover:not(:disabled) {
  background: #b91c1c;
}

.login-btn:disabled, .dev-login-btn:disabled, .logout-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.dev-description {
  color: rgba(255, 255, 255, 0.8);
  margin-bottom: 0.75rem;
  font-size: 0.85rem;
}

.shared-description {
  color: rgba(255, 255, 255, 0.8);
  margin-bottom: 0.75rem;
  font-size: 0.85rem;
}

.shared-auth-info {
  background: rgba(255, 255, 255, 0.05);
  border-radius: 6px;
  padding: 0.5rem;
  margin-bottom: 0.5rem;
}

.shared-auth-info p {
  color: rgba(255, 255, 255, 0.9);
  margin-bottom: 0.25rem;
  font-size: 0.8rem;
}

.shared-auth-info ul {
  color: rgba(255, 255, 255, 0.8);
  margin-left: 1rem;
  margin-bottom: 0;
  font-size: 0.75rem;
}

.shared-auth-info li {
  margin-bottom: 0.125rem;
}

.shared-auth-status {
  background: rgba(255, 255, 255, 0.05);
  border-radius: 6px;
  padding: 0.5rem;
}

.shared-auth-status p {
  color: rgba(255, 255, 255, 0.9);
  margin-bottom: 0.25rem;
  font-size: 0.8rem;
}

.status-success {
  color: #10b981 !important;
  font-weight: 500;
}

.status-info {
  color: #3b82f6 !important;
  font-weight: 500;
}

.shared-badge {
  background: #3b82f6;
  color: white;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.8rem;
  font-weight: 600;
  display: inline-block;
  margin-top: 0.25rem;
}

.dev-unavailable {
  color: rgba(255, 255, 255, 0.6);
  font-size: 0.8rem;
  font-style: italic;
}

.divider {
  text-align: center;
  margin: 0.75rem 0;
  position: relative;
}

.divider::before {
  content: '';
  position: absolute;
  top: 50%;
  left: 0;
  right: 0;
  height: 1px;
  background: rgba(255, 255, 255, 0.3);
}

.divider span {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 0.5rem 1rem;
  border-radius: 20px;
  font-weight: 600;
}

.logout-section {
  background: rgba(255, 255, 255, 0.1);
  border-radius: 10px;
  padding: 0.75rem;
  backdrop-filter: blur(10px);
}

.user-info {
  margin-bottom: 0.5rem;
}

.user-info h4 {
  color: white;
  margin-bottom: 0.25rem;
}

.user-info p {
  color: rgba(255, 255, 255, 0.9);
  margin-bottom: 0.25rem;
}

.admin-badge {
  background: #f59e0b;
  color: #1f2937;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.8rem;
  font-weight: 600;
  display: inline-block;
}
</style>
