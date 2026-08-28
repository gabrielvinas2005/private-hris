<template>
  <div class="relative min-h-screen overflow-hidden bg-slate-950">
    <!-- Background -->
    <div
      class="absolute inset-0 bg-center bg-cover opacity-30"
      style="background-image: url('../assets/img/hr_bgb.png')"
    />
    <div class="absolute inset-0 bg-gradient-to-br from-slate-950/95 via-blue-950/80 to-slate-900/90" />

    <!-- Ambient glow -->
    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[600px] h-[600px] bg-blue-600/20 rounded-full blur-[120px] pointer-events-none" />
    <div class="absolute bottom-0 right-0 w-[400px] h-[400px] bg-cyan-500/10 rounded-full blur-[100px] pointer-events-none" />

    <!-- Content -->
    <div class="relative z-10 flex items-center justify-center min-h-screen px-4 py-10">
      <div class="w-full max-w-md">
        <div class="overflow-hidden border shadow-2xl bg-white/95 backdrop-blur-md rounded-2xl border-white/20">
          <!-- Brand header -->
          <div class="px-8 pt-8 pb-5 text-center bg-gradient-to-b from-blue-50/80 to-transparent">
            <div v-if="companyLogo" class="inline-flex items-center justify-center w-24 h-24 mb-4 overflow-hidden bg-white border border-slate-200 shadow-md rounded-2xl">
              <img
                :src="companyLogo"
                :alt="company.name ? `${company.name} logo` : 'Company logo'"
                class="object-contain w-full h-full p-2"
              />
            </div>
            <h1 class="text-xl font-bold tracking-tight text-slate-900">
              {{ company.name || 'Employee Portal' }}
            </h1>
            <p class="mt-1.5 text-sm font-medium text-blue-700">
              Sign in to continue
            </p>
          </div>

          <!-- Form -->
          <div class="px-8 py-6">
            <div v-if="errorMessage" class="p-3 mb-4 text-sm text-red-700 bg-red-50 border border-red-200 rounded-xl">
              {{ errorMessage }}
            </div>

            <div v-if="successMessage" class="p-3 mb-4 text-sm text-green-700 bg-green-50 border border-green-200 rounded-xl">
              {{ successMessage }}
            </div>

            <form @submit.prevent="handleLogin" class="space-y-4">
              <div>
                <label for="login-email" class="block mb-1.5 text-xs font-medium text-slate-600">Email Address</label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                  </div>
                  <input
                    id="login-email"
                    v-model="username"
                    type="email"
                    placeholder="you@company.com"
                    class="w-full py-2.5 pl-9 pr-4 text-sm transition-all border rounded-xl border-slate-200 bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
                    :disabled="isLoading"
                    required
                  />
                </div>
              </div>

              <div>
                <label for="login-password" class="block mb-1.5 text-xs font-medium text-slate-600">Password</label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                  </div>
                  <input
                    id="login-password"
                    v-model="password"
                    :type="showPassword ? 'text' : 'password'"
                    placeholder="Enter your password"
                    class="w-full py-2.5 pl-9 pr-10 text-sm transition-all border rounded-xl border-slate-200 bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
                    :disabled="isLoading"
                    required
                  />
                  <button
                    type="button"
                    @click="showPassword = !showPassword"
                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600"
                    :disabled="isLoading"
                  >
                    <svg v-if="showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21" />
                    </svg>
                    <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                  </button>
                </div>
              </div>

              <button
                type="submit"
                class="flex items-center justify-center w-full gap-2 px-6 py-3 text-sm font-semibold text-white transition-all duration-200 bg-blue-600 rounded-xl hover:bg-blue-700 hover:shadow-lg hover:shadow-blue-600/25 active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed disabled:active:scale-100"
                :disabled="isLoading"
              >
                <svg v-if="isLoading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                </svg>
                <span>{{ isLoading ? 'Signing In...' : 'Sign In' }}</span>
              </button>
            </form>

            <div class="flex justify-between mt-5 mb-10 text-xs">
              <a href="#" class="font-medium text-blue-600 transition-colors hover:text-blue-800">
                Forgot Password?
              </a>
              <a
                href="#"
                @click.prevent="goToHome"
                class="font-medium text-slate-500 transition-colors hover:text-slate-700"
              >
                Back to Home
              </a>
            </div>
          </div>

          <!-- Footer
          <div v-if="hasCompanyDetails" class="px-8 py-5 space-y-3 border-t border-slate-100 bg-slate-50/50">
            <div v-if="company.address" class="flex items-start gap-2.5">
              <div class="flex items-center justify-center flex-shrink-0 w-7 h-7 bg-blue-50 rounded-lg">
                <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
              </div>
              <p class="text-xs leading-relaxed text-slate-600">{{ company.address }}</p>
            </div>

            <div v-if="company.email || displayPhone" class="flex flex-wrap gap-2">
              <span v-if="displayPhone" class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs text-slate-600 bg-white border rounded-full border-slate-200">
                <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                </svg>
                {{ displayPhone }}
              </span>
              <span v-if="company.email" class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs text-slate-600 bg-white border rounded-full border-slate-200">
                <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                {{ company.email }}
              </span>
            </div>

            <div v-if="company.name && companyLogo" class="flex items-center justify-end gap-2 pt-1 text-xs text-slate-400">
              <span>Powered by {{ company.name }}</span>
              <img :src="companyLogo" :alt="company.name" class="object-contain w-7 h-7">
            </div>
          </div>
           -->
        </div>

        <p class="mt-5 text-xs text-center text-slate-500">
          Secure employee self-service portal
        </p>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import { fetchCompanyPublic, getCompanyLogo, getCompanyPhone } from '../../services/companyPublic.js'

export default {
  name: 'Login',
  data() {
    return {
      company: {
        name: '',
        address: '',
        email: '',
        telephone_no: '',
        mobile_no: '',
        logo_data_url: null
      },
      username: '',
      password: '',
      showPassword: false,
      isLoading: false,
      errorMessage: '',
      successMessage: ''
    }
  },
  computed: {
    companyLogo() {
      return getCompanyLogo(this.company)
    },
    displayPhone() {
      return getCompanyPhone(this.company)
    },
    hasCompanyDetails() {
      return Boolean(
        this.company.name ||
        this.company.address ||
        this.displayPhone ||
        this.company.email
      )
    }
  },
  async mounted() {
    this.company = await fetchCompanyPublic()
    // Show session expiry message if redirected due to 5 hours of inactivity
    const reason = new URLSearchParams(window.location.search).get('reason')
    if (reason === 'inactivity' || reason === 'max_session') {
      this.errorMessage = 'Your session has expired due to 5 hours of inactivity. Please sign in again.'
    }
  },
  methods: {
    async handleLogin() {
      this.errorMessage = ''
      this.successMessage = ''

      if (!this.username || !this.password) {
        this.errorMessage = 'Please enter both username and password.'
        return
      }

      this.isLoading = true

      try {
        await axios.get('/sanctum/csrf-cookie', {
          baseURL: (import.meta.env.VITE_API_ORIGIN || 'http://localhost:8000'),
          withCredentials: true
        })

        const requestData = {
          email: this.username,
          password: this.password
        }

        const response = await axios.post('/login', requestData)

        this.successMessage = 'Login successful! Redirecting...'

        const payload = response?.data?.data || {}

        if (payload.user) {
          localStorage.removeItem('is_approver_user')
          localStorage.setItem('user_data', JSON.stringify(payload.user))
        }

        if (payload.token) {
          localStorage.setItem('auth_token', payload.token)
          localStorage.setItem('session_start_time', Date.now().toString())
          sessionStorage.setItem('ep_just_logged_in', 'true')
        }

        if (payload.temp_token) {
          localStorage.setItem('temp_token', payload.temp_token)
        }

        let nextPath = '/dashboard'
        if (payload.requires_otp || (payload.user && !payload.user.has_change_password)) {
          nextPath = '/verify-otp'
        } else if (payload.next) {
          nextPath = payload.next
        }

        setTimeout(() => {
          this.$router.push(nextPath)
        }, 800)
      } catch (error) {
        console.error('Login error:', error)

        if (error.response) {
          const status = error.response.status
          const data = error.response.data

          if (status === 401) {
            this.errorMessage = (data && data.message) ? data.message : 'Invalid username or password.'
          } else if (status === 422) {
            if (data.errors) {
              const errorMessages = Object.values(data.errors).flat()
              this.errorMessage = errorMessages.join(', ')
            } else {
              this.errorMessage = data.message || 'Please check your input.'
            }
          } else if (status >= 500) {
            this.errorMessage = 'Server error. Please try again later.'
          } else {
            this.errorMessage = data.message || 'Login failed. Please try again.'
          }
        } else if (error.code === 'ECONNABORTED') {
          this.errorMessage = 'The server took too long to respond. Check that the database server is running and reachable.'
        } else if (error.request) {
          this.errorMessage = 'Cannot connect to server. Please check if the backend is running.'
        } else {
          this.errorMessage = `Connection error: ${error.message}`
        }
      } finally {
        this.isLoading = false
      }
    },

    goToHome() {
      this.$router.push('/')
    }
  }
}
</script>

<style scoped>
::-webkit-scrollbar {
  display: none;
}

* {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>
