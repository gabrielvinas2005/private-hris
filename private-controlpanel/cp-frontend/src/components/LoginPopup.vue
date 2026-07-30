<template>
  <el-dialog
    v-model="visible"
    title="Login Required"
    width="400px"
    :close-on-click-modal="false"
    :close-on-press-escape="false"
    :show-close="false"
  >
    <el-form
      ref="loginFormRef"
      :model="loginForm"
      :rules="loginRules"
      label-width="80px"
      @submit.prevent="handleLogin"
    >
      <el-form-item label="Email" prop="email">
        <el-input
          v-model="loginForm.email"
          type="email"
          placeholder="Enter your email"
          :prefix-icon="User"
        />
      </el-form-item>
      
      <el-form-item label="Password" prop="password">
        <el-input
          v-model="loginForm.password"
          type="password"
          placeholder="Enter your password"
          :prefix-icon="Lock"
          show-password
          @keyup.enter="handleLogin"
        />
      </el-form-item>
    </el-form>

    <!-- Dev Login Section - Only shown in development -->
    <div v-if="isDevelopment" class="dev-login-section">
      <el-divider>
        <span class="divider-text">Development Mode</span>
      </el-divider>
      <div class="dev-login-options">
        <el-button 
          @click="handleDevLogin" 
          type="success" 
          :loading="devLoading"
          plain
          size="small"
        >
          <el-icon><UserFilled /></el-icon>
          Quick Dev Login
        </el-button>
        <el-button 
          @click="showDevEmailInput = !showDevEmailInput" 
          type="info" 
          plain
          size="small"
        >
          <el-icon><Setting /></el-icon>
          Custom Email
        </el-button>
      </div>
      <div v-if="showDevEmailInput" class="dev-email-input">
        <el-input
          v-model="devEmail"
          placeholder="Enter email for dev login"
          size="small"
          style="margin-top: 8px;"
        >
          <template #append>
            <el-button @click="handleDevLoginWithEmail" :loading="devLoading" size="small">
              Login
            </el-button>
          </template>
        </el-input>
      </div>
    </div>

    <template #footer>
      <div class="dialog-footer">
        <el-button @click="handleLogin" type="primary" :loading="loading">
          Login
        </el-button>
      </div>
    </template>
  </el-dialog>
</template>

<script setup>
import { ref, reactive, onMounted, watch, computed } from 'vue'
import { ElMessage } from 'element-plus'
import { User, Lock, UserFilled, Setting } from '@element-plus/icons-vue'
import apiService from '../Services/api.js'

// Props
const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  }
})

// Emits
const emit = defineEmits(['update:modelValue', 'login-success'])

// Reactive data
const visible = ref(props.modelValue)
const loading = ref(false)
const devLoading = ref(false)
const loginFormRef = ref()
const showDevEmailInput = ref(false)
const devEmail = ref('')

// Check if in development mode
const isDevelopment = computed(() => import.meta.env.DEV || import.meta.env.MODE === 'development')

// Form data
const loginForm = reactive({
  email: '',
  password: ''
})

// Form validation rules
const loginRules = {
  email: [
    { required: true, message: 'Please enter your email', trigger: 'blur' },
    { type: 'email', message: 'Please enter a valid email', trigger: 'blur' }
  ],
  password: [
    { required: true, message: 'Please enter your password', trigger: 'blur' },
    { min: 6, message: 'Password must be at least 6 characters', trigger: 'blur' }
  ]
}

// Watch for prop changes
watch(() => props.modelValue, (newVal) => {
  console.log('LoginPopup: modelValue changed to', newVal)
  visible.value = newVal
}, { immediate: true })

// Watch for visibility changes
watch(visible, (newVal) => {
  console.log('LoginPopup: visible changed to', newVal)
  emit('update:modelValue', newVal)
})

// Handle login
async function handleLogin() {
  if (!loginFormRef.value) return
  
  try {
    // Validate form
    await loginFormRef.value.validate()
    
    loading.value = true
    
    // Call login API
    console.log('Attempting login with:', loginForm)
    const response = await apiService.login(loginForm)
    console.log('Login response:', response)
    
    if (response.success) {
      // Store token and user data
      localStorage.setItem('auth_token', response.data.token)
      localStorage.setItem('user_data', JSON.stringify(response.data.user))
      
      ElMessage.success('Login successful!')
      
      // Emit success event
      emit('login-success', response.data)
      
      // Close dialog
      visible.value = false
      
      // Reset form
      resetForm()
    } else {
      ElMessage.error(response.message || 'Login failed')
    }
  } catch (error) {
    console.error('Login error:', error)
    ElMessage.error(error.message || 'Login failed. Please try again.')
  } finally {
    loading.value = false
  }
}

// Reset form
function resetForm() {
  loginForm.email = ''
  loginForm.password = ''
  if (loginFormRef.value) {
    loginFormRef.value.clearValidate()
  }
}

// Handle dev login
async function handleDevLogin() {
  await performDevLogin()
}

// Handle dev login with custom email
async function handleDevLoginWithEmail() {
  if (!devEmail.value || !devEmail.value.includes('@')) {
    ElMessage.warning('Please enter a valid email address')
    return
  }
  await performDevLogin(devEmail.value)
}

// Perform dev login
async function performDevLogin(email = null) {
  try {
    devLoading.value = true
    
    const response = await apiService.devLogin(email)
    
    if (response.success) {
      // Store token and user data
      localStorage.setItem('auth_token', response.data.token)
      localStorage.setItem('user_data', JSON.stringify(response.data.user))
      
      ElMessage.success('Dev login successful!')
      
      // Emit success event
      emit('login-success', response.data)
      
      // Close dialog
      visible.value = false
      
      // Reset form
      resetForm()
      showDevEmailInput.value = false
      devEmail.value = ''
    } else {
      ElMessage.error(response.message || 'Dev login failed')
    }
  } catch (error) {
    console.error('Dev login error:', error)
    ElMessage.error(error.message || 'Dev login failed. Please try again.')
  } finally {
    devLoading.value = false
  }
}

// Set default credentials for development
onMounted(() => {
  if (import.meta.env.DEV) {
    loginForm.email = 'admin@example.com'
    loginForm.password = 'password'
  }
})
</script>

<style scoped>
.dialog-footer {
  text-align: right;
}

.el-form {
  padding: 20px 0;
}

.el-form-item {
  margin-bottom: 20px;
}

.dev-login-section {
  margin-top: 20px;
  padding-top: 20px;
  border-top: 1px solid #ebeef5;
}

.divider-text {
  font-size: 12px;
  color: #909399;
  font-weight: 500;
}

.dev-login-options {
  display: flex;
  gap: 8px;
  justify-content: center;
  flex-wrap: wrap;
}

.dev-email-input {
  margin-top: 12px;
}

.dev-login-options .el-button {
  flex: 1;
  min-width: 120px;
}
</style>
