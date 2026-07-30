<template>
  <div
    class="min-h-screen bg-gradient-to-br from-blue-600 via-blue-700 to-blue-800 relative overflow-hidden text-white"
  >
    <div
      class="absolute top-0 left-0 w-96 h-96 bg-blue-500 rounded-full opacity-20 blur-3xl"
    ></div>
    <div
      class="absolute bottom-0 right-0 w-96 h-96 bg-blue-400 rounded-full opacity-20 blur-3xl"
    ></div>

    <nav class="relative z-10 bg-white/10 backdrop-blur-md">
      <div class="max-w-7xl mx-auto px-6 py-4">
        <div class="flex items-center justify-between">
          <button
            class="flex items-center gap-2 text-white hover:text-blue-100 transition-colors"
            @click="handleNavigate('/')"
          >
            <ArrowLeft class="w-5 h-5" />
            <span class="cursor-pointer hover:underline hover:font-bold"
              >Back to Home</span
            >
          </button>
          <div class="flex items-center gap-2 text-white">
            <span class="hidden sm:inline">Don't have an account?</span>
            <button
              class="px-6 py-2 bg-white text-blue-600 rounded-lg transition-colors cursor-pointer hover:bg-blue-400 hover:text-white"
              @click="handleNavigate('/registration')"
            >
              Register Now
            </button>
          </div>
        </div>
      </div>
    </nav>

    <div
      class="relative z-10 flex items-center justify-center px-6 py-12 min-h-[calc(100vh-80px)]"
    >
      <div class="w-full max-w-6xl grid lg:grid-cols-2 gap-12 items-center">
        <div class="hidden lg:block">
          <div class="flex items-center gap-3 mb-8">
            <div
              class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center"
            >
              <Briefcase class="w-8 h-8 text-white" />
            </div>
            <div>
              <div class="text-3xl">{{ companyName }}</div>
              <div class="text-blue-100">Applicant Portal</div>
            </div>
          </div>

          <h1 class="text-5xl mb-6 leading-tight">Welcome Back!</h1>
          <p class="text-xl text-blue-100 leading-relaxed mb-8">
            Sign in to access your applicant dashboard, track your applications,
            and discover new opportunities.
          </p>

          <div class="space-y-4">
            <div
              v-for="highlight in highlights"
              :key="highlight.title"
              class="flex items-start gap-3"
            >
              <div
                class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center flex-shrink-0 mt-1"
              >
                <span class="text-lg">✓</span>
              </div>
              <div>
                <div class="text-lg">{{ highlight.title }}</div>
                <div class="text-blue-100">{{ highlight.description }}</div>
              </div>
            </div>
          </div>
        </div>

        <div class="w-full max-w-md mx-auto">
          <div
            class="bg-white rounded-3xl shadow-2xl p-8 h-full flex flex-col justify-between text-gray-900"
          >
            <div class="text-center mb-8">
              <div
                v-if="logo"
                class="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg"
              >
                <img
                  :src="logo"
                  alt="Company Logo"
                  class="w-16 h-16 object-contain rounded-md"
                />
              </div>
              <h2 class="text-3xl text-gray-900 mb-2">Sign In</h2>
              <p class="text-gray-600">Access your applicant account</p>
            </div>

            <form class="space-y-6" @submit.prevent="handleLogin">
              <div>
                <label class="block text-gray-700 mb-2">Email Address</label>
                <div class="relative">
                  <div
                    class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none"
                  >
                    <Message class="h-5 w-5 text-gray-400" />
                  </div>
                  <input
                    v-model="form.email"
                    type="email"
                    required
                    placeholder="your.email@example.com"
                    class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent transition-all"
                  />
                </div>
              </div>

              <div>
                <label class="block text-gray-700 mb-2">Password</label>
                <div class="relative">
                  <div
                    class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none"
                  >
                    <Lock class="h-5 w-5 text-gray-400" />
                  </div>
                  <input
                    :type="showPassword ? 'text' : 'password'"
                    v-model="form.password"
                    required
                    placeholder="Enter your password"
                    class="w-full pl-12 pr-12 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent transition-all"
                  />
                  <button
                    type="button"
                    class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-gray-600"
                    @click="togglePassword"
                  >
                    <View v-if="showPassword" class="h-5 w-5" />
                    <Hide v-else class="h-5 w-5" />
                  </button>
                </div>
              </div>

              <div class="flex items-center justify-between">
                <label class="flex items-center gap-2 text-gray-700">
                  <input
                    v-model="form.rememberMe"
                    type="checkbox"
                    class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-600"
                  />
                  Remember me
                </label>
                <button
                  type="button"
                  class="text-blue-600 hover:text-blue-700 transition-colors cursor-pointer hover:underline font-bold"
                  @click="openForgotPassword"
                >
                  Forgot password?
                </button>
              </div>

              <button
                type="submit"
                :disabled="isLoading"
                class="w-full py-4 bg-gradient-to-r from-blue-600 to-blue-800 text-white rounded-xl hover:shadow-xl transition-all disabled:opacity-50"
              >
                <span
                  v-if="isLoading"
                  class="flex items-center justify-center gap-2"
                >
                  <svg
                    class="animate-spin h-5 w-5 text-white"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                  >
                    <circle
                      class="opacity-25"
                      cx="12"
                      cy="12"
                      r="10"
                      stroke="currentColor"
                      stroke-width="4"
                    ></circle>
                    <path
                      class="opacity-75"
                      fill="currentColor"
                      d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                    ></path>
                  </svg>
                  Signing in...
                </span>
                <span v-else>Sign In to Dashboard</span>
              </button>
            </form>

            <div class="mt-8 text-center">
              <p class="text-gray-600">
                New to {{ shortName }}?
                <button
                  class="text-blue-600 hover:text-blue-700 hover:underline font-bold"
                  @click="handleNavigate('/registration')"
                >
                  Create an account
                </button>
              </p>
            </div>

            <div class="mt-6">
              <div class="relative">
                <div class="absolute inset-0 flex items-center">
                  <div class="w-full border-t border-gray-300"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                  <span class="px-4 bg-white text-gray-500">Quick Access</span>
                </div>
              </div>
              <div class="mt-6 grid grid-cols-2 gap-3">
                <button
                  type="button"
                  class="px-4 py-2 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 transition-colors text-sm"
                  @click="handleNavigate('/positions')"
                >
                  Browse Jobs
                </button>
                <button
                  type="button"
                  class="px-4 py-2 bg-gray-50 text-gray-700 rounded-lg hover:bg-gray-100 transition-colors text-sm"
                  @click="handleNavigate('/')"
                >
                  Learn More
                </button>
              </div>
            </div>
          </div>

          <div
            v-if="showForgotPassword"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
          >
            <div
              class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 text-gray-900"
            >
              <h3 class="text-xl font-semibold mb-2">Reset your password</h3>
              <p class="text-sm text-gray-600 mb-4">
                Enter the email address associated with your account and we'll
                send you a link to reset your password.
              </p>
              <div class="mb-4">
                <label class="block text-gray-700 mb-2">Email Address</label>
                <input
                  v-model="forgotPasswordEmail"
                  type="email"
                  placeholder="your.email@example.com"
                  class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent transition-all"
                />
              </div>
              <div class="flex justify-end gap-3 mt-6">
                <button
                  type="button"
                  class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-100 transition-colors"
                  @click="closeForgotPassword"
                >
                  Cancel
                </button>
                <button
                  type="button"
                  class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50 transition-colors"
                  :disabled="isSendingReset"
                  @click="submitForgotPassword"
                >
                  <span v-if="isSendingReset">Sending...</span>
                  <span v-else>Send reset link</span>
                </button>
              </div>
            </div>
          </div>

          <p class="text-center text-white/80 mt-6 text-sm">
            Your information is secure and encrypted
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import {
  ArrowLeft,
  Message,
  Lock,
  View,
  Hide,
  Briefcase,
} from "@element-plus/icons-vue";
import { useCompanyBranding } from "@/composables/useCompanyBranding.js";
import { useLogin } from "@/composables/useLogin.js";
import { onMounted } from "vue";

const { companyName, companyLogo, shortName, loadCompany } = useCompanyBranding();
const logo = companyLogo;

onMounted(() => {
  loadCompany();
});

const {
  form,
  highlights,
  isLoading,
  showPassword,
  showForgotPassword,
  isSendingReset,
  forgotPasswordEmail,
  togglePassword,
  handleNavigate,
  handleLogin,
  openForgotPassword,
  closeForgotPassword,
  submitForgotPassword,
} = useLogin();
</script>
