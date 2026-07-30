<template>
  <el-dialog
    v-model="visible"
    title="Login to HR System"
    width="400px"
    :close-on-click-modal="false"
    :close-on-press-escape="false"
    :show-close="false"
    :modal="true"
    center
  >
    <el-form
      ref="formRef"
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

      <el-form-item>
        <el-checkbox v-model="loginForm.remember">Remember me</el-checkbox>
      </el-form-item>
    </el-form>

    <template #footer>
      <div class="dialog-footer">
        <el-button
          type="primary"
          @click="handleLogin"
          :loading="loading"
          style="width: 100%"
        >
          <el-icon v-if="!loading"><Right /></el-icon>
          Login
        </el-button>
      </div>
    </template>
  </el-dialog>
</template>

<script setup>
import { ref, reactive, computed } from "vue";
import { ElMessage } from "element-plus";
import { User, Lock, Right } from "@element-plus/icons-vue";
import { authApi } from "@/services/api";

// Props
const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
});

// Emits
const emit = defineEmits(["update:modelValue", "login-success"]);

// Reactive data
const formRef = ref();
const loading = ref(false);

const visible = computed({
  get: () => props.modelValue,
  set: (value) => emit("update:modelValue", value),
});

const loginForm = reactive({
  email: "",
  password: "",
  remember: false,
});

// Validation rules
const loginRules = {
  email: [
    { required: true, message: "Email is required", trigger: "blur" },
    { type: "email", message: "Please enter a valid email", trigger: "blur" },
  ],
  password: [
    { required: true, message: "Password is required", trigger: "blur" },
    {
      min: 6,
      message: "Password must be at least 6 characters",
      trigger: "blur",
    },
  ],
};

// Methods
const handleLogin = async () => {
  try {
    await formRef.value.validate();
    loading.value = true;

    // Call actual backend login API
    const response = await authApi.login({
      email: loginForm.email,
      password: loginForm.password,
    });

    // Store the actual token from backend response
    // Handle different response formats from backend
    const responseData = response.data.data || response.data;
    const { token, user } = responseData;

    if (!token) {
      throw new Error("No token received from server");
    }

    // For Laravel Sanctum tokens, they typically don't have expiration
    // But we'll set a reasonable expiration time (24 hours)
    const tokenData = {
      token: token,
      expiresAt: Date.now() + 24 * 60 * 60 * 1000, // 24 hours from now
    };
    localStorage.setItem("auth_token", JSON.stringify(tokenData));

    // Store user info if remember is checked
    if (loginForm.remember) {
      localStorage.setItem("user_email", loginForm.email);
    } else {
      localStorage.removeItem("user_email");
    }

    ElMessage.success("Login successful!");
    emit("login-success", { user, token });
    visible.value = false;

    // Reset form
    resetForm();
  } catch (error) {
    console.error("Login failed:", error);
    const errorMessage =
      error.response?.data?.message ||
      "Login failed. Please check your credentials.";
    ElMessage.error(errorMessage);
  } finally {
    loading.value = false;
  }
};

// Reset form
const resetForm = () => {
  loginForm.email = "";
  loginForm.password = "";
  loginForm.remember = false;
  formRef.value?.resetFields();
};

// Load remembered email on mount
const loadRememberedEmail = () => {
  const rememberedEmail = localStorage.getItem("user_email");
  if (rememberedEmail) {
    loginForm.email = rememberedEmail;
    loginForm.remember = true;
  }
};

// Check if user is already logged in
const checkExistingAuth = () => {
  const token = localStorage.getItem("auth_token");
  if (token) {
    // User is already logged in
    visible.value = false;
    emit("login-success", { token });
  }
};

// Initialize
loadRememberedEmail();
checkExistingAuth();
</script>

<style scoped>
.dialog-footer {
  display: flex;
  justify-content: center;
}

:deep(.el-dialog__header) {
  text-align: center;
  padding: 20px 20px 10px;
}

:deep(.el-dialog__title) {
  font-size: 20px;
  font-weight: 600;
  color: #303133;
}

:deep(.el-form-item__label) {
  font-weight: 500;
}

:deep(.el-input__wrapper) {
  border-radius: 8px;
}

:deep(.el-button) {
  border-radius: 8px;
  height: 40px;
  font-weight: 500;
}
</style>
