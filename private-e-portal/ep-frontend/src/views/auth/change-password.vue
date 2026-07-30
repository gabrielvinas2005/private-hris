<template>
  <div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
      <div v-if="logoImage" class="flex justify-center">
        <img :src="logoImage" alt="Company Logo" class="h-12 w-auto" />
      </div>
      <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
        Change Your Password
      </h2>
      <p class="mt-2 text-center text-sm text-gray-600">
        For security reasons, you must change your password before accessing the system.
      </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
      <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
        <form @submit.prevent="handleChangePassword" class="space-y-6">
          <!-- Current Password -->
          <div>
            <label for="current_password" class="block text-sm font-medium text-gray-700">
              Current Password
            </label>
            <div class="mt-1 relative">
              <input
                id="current_password"
                v-model="form.current_password"
                :type="showCurrentPassword ? 'text' : 'password'"
                required
                class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                placeholder="Enter your current password"
              />
              <button
                type="button"
                @click="showCurrentPassword = !showCurrentPassword"
                class="absolute inset-y-0 right-0 pr-3 flex items-center"
              >
                <svg
                  v-if="!showCurrentPassword"
                  class="h-5 w-5 text-gray-400"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                  />
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                  />
                </svg>
                <svg
                  v-else
                  class="h-5 w-5 text-gray-400"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"
                  />
                </svg>
              </button>
            </div>
          </div>

          <!-- New Password -->
          <div>
            <label for="new_password" class="block text-sm font-medium text-gray-700">
              New Password
            </label>
            <div class="mt-1 relative">
              <input
                id="new_password"
                v-model="form.new_password"
                :type="showNewPassword ? 'text' : 'password'"
                required
                minlength="10"
                maxlength="128"
                autocomplete="new-password"
                class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                placeholder="Enter your new password"
              />
              <button
                type="button"
                @click="showNewPassword = !showNewPassword"
                class="absolute inset-y-0 right-0 pr-3 flex items-center"
              >
                <svg
                  v-if="!showNewPassword"
                  class="h-5 w-5 text-gray-400"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                  />
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                  />
                </svg>
                <svg
                  v-else
                  class="h-5 w-5 text-gray-400"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"
                  />
                </svg>
              </button>
            </div>
            <ul class="mt-2 space-y-1 text-xs text-gray-600">
              <li :class="ruleClass(hasMinLength)">At least 10 characters</li>
              <li :class="ruleClass(hasUppercase)">One uppercase letter</li>
              <li :class="ruleClass(hasLowercase)">One lowercase letter</li>
              <li :class="ruleClass(hasNumber)">One number</li>
              <li :class="ruleClass(hasSpecial)">One special character (e.g. !@#$%)</li>
            </ul>
          </div>

          <!-- Confirm New Password -->
          <div>
            <label for="new_password_confirmation" class="block text-sm font-medium text-gray-700">
              Confirm New Password
            </label>
            <div class="mt-1 relative">
              <input
                id="new_password_confirmation"
                v-model="form.new_password_confirmation"
                :type="showConfirmPassword ? 'text' : 'password'"
                required
                minlength="10"
                maxlength="128"
                autocomplete="new-password"
                class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                placeholder="Confirm your new password"
              />
              <button
                type="button"
                @click="showConfirmPassword = !showConfirmPassword"
                class="absolute inset-y-0 right-0 pr-3 flex items-center"
              >
                <svg
                  v-if="!showConfirmPassword"
                  class="h-5 w-5 text-gray-400"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                  />
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                  />
                </svg>
                <svg
                  v-else
                  class="h-5 w-5 text-gray-400"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"
                  />
                </svg>
              </button>
            </div>
          </div>

          <!-- Error Message -->
          <div v-if="errorMessage" class="rounded-md bg-red-50 p-4">
            <div class="flex">
              <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
              </div>
              <div class="ml-3">
                <p class="text-sm text-red-800">{{ errorMessage }}</p>
              </div>
            </div>
          </div>

          <!-- Success Message -->
          <div v-if="successMessage" class="rounded-md bg-green-50 p-4">
            <div class="flex">
              <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
              </div>
              <div class="ml-3">
                <p class="text-sm text-green-800">{{ successMessage }}</p>
              </div>
            </div>
          </div>

          <!-- Submit Button -->
          <div>
            <button
              type="submit"
              :disabled="isLoading || !isFormValid"
              class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <span v-if="isLoading" class="absolute left-0 inset-y-0 flex items-center pl-3">
                <svg class="animate-spin h-5 w-5 text-blue-500" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
              </span>
              {{ isLoading ? 'Changing Password...' : 'Change Password' }}
        </button>
          </div>
      </form>
      </div>
    </div>
  </div>
</template>

<script>
import apiService from '@/services/api'
import { fetchCompanyPublic, getCompanyLogo } from '../../services/companyPublic.js'

const PASSWORD_MIN_LENGTH = 10

export default {
  name: 'ChangePassword',
  data() {
    return {
      logoImage: null,
      form: {
        current_password: '',
        new_password: '',
        new_password_confirmation: ''
      },
      showCurrentPassword: false,
      showNewPassword: false,
      showConfirmPassword: false,
      isLoading: false,
      errorMessage: '',
      successMessage: ''
    }
  },
  async mounted() {
    const company = await fetchCompanyPublic()
    this.logoImage = getCompanyLogo(company)
  },
  computed: {
    newPasswordPlain() {
      return (this.form.new_password || '').toString()
    },
    hasMinLength() {
      return this.newPasswordPlain.length >= PASSWORD_MIN_LENGTH
    },
    hasUppercase() {
      return /[A-Z]/.test(this.newPasswordPlain)
    },
    hasLowercase() {
      return /[a-z]/.test(this.newPasswordPlain)
    },
    hasNumber() {
      return /\d/.test(this.newPasswordPlain)
    },
    hasSpecial() {
      return /[^A-Za-z0-9]/.test(this.newPasswordPlain)
    },
    newPasswordMeetsPolicy() {
      return (
        this.hasMinLength &&
        this.hasUppercase &&
        this.hasLowercase &&
        this.hasNumber &&
        this.hasSpecial
      )
    },
    isFormValid() {
      return (
        this.form.current_password.length > 0 &&
        this.newPasswordMeetsPolicy &&
        this.form.new_password_confirmation.length >= PASSWORD_MIN_LENGTH &&
        this.form.new_password === this.form.new_password_confirmation
      )
    }
  },
  methods: {
    ruleClass(ok) {
      return ok ? 'text-green-700' : 'text-gray-500'
    },
    async handleChangePassword() {
      // Clear previous messages
      this.errorMessage = ''
      this.successMessage = ''
      
      // Validate passwords match
      if (this.form.new_password !== this.form.new_password_confirmation) {
        this.errorMessage = 'New passwords do not match.'
        return
      }

      if (!this.newPasswordMeetsPolicy) {
        this.errorMessage =
          'Your new password must be at least 10 characters and include uppercase, lowercase, a number, and a special character.'
        return
      }
      
      this.isLoading = true
      
      try {
        // Get temporary token from localStorage for authentication
        const tempToken = localStorage.getItem('temp_token')
        const authToken = localStorage.getItem('auth_token')
        
        const response = await apiService.request('/change-password', {
          method: 'POST',
          body: JSON.stringify(this.form),
          headers: {
            'Authorization': `Bearer ${tempToken || authToken}`
          }
        })
        
        if (response && response.success) {
          this.successMessage = 'Password changed successfully! Redirecting to dashboard...'
          
          // Update user data in localStorage
          if (response.data && response.data.user) {
            localStorage.setItem('user_data', JSON.stringify(response.data.user))
          }
          
          // Clean up temporary token and generate new session
          localStorage.removeItem('temp_token')
          
          // Get new authentication token for the updated user session
          if (response.data && response.data.token) {
            localStorage.setItem('auth_token', response.data.token)
          }
          
          // Redirect to dashboard after a short delay
        setTimeout(() => {
          this.$router.push('/dashboard')
          }, 2000)
        } else {
          this.errorMessage = response?.message || 'Failed to change password. Please try again.'
        }
        
      } catch (error) {
        console.error('Change password error:', error)

        // apiService throws plain Error with message; show it directly
        this.errorMessage = error?.message || 'Failed to change password. Please check your current password and try again.'
      } finally {
        this.isLoading = false
      }
    }
  }
}
</script>
