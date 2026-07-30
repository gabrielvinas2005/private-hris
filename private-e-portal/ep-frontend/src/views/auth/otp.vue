<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 p-4">
    <div class="w-full max-w-md bg-white rounded-xl shadow p-6 space-y-4">
      <h1 class="text-xl font-semibold text-gray-800 text-center">Verify Your Email</h1>
      <p class="text-sm text-gray-600 text-center">Enter the 6-digit code sent to your email to proceed with password change.</p>

      <div v-if="message" class="p-3 rounded bg-green-50 text-green-700 text-sm">{{ message }}</div>
      <div v-if="error" class="p-3 rounded bg-red-50 text-red-700 text-sm">{{ error }}</div>

      <form @submit.prevent="submitOtp" class="space-y-4">
        <input
          v-model="otp"
          maxlength="6"
          inputmode="numeric"
          placeholder="Enter 6-digit OTP"
          :disabled="loading || verified"
          class="w-full border rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:outline-none disabled:bg-gray-100"
        />
        <button :disabled="loading || verified" type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white rounded-lg py-3 disabled:opacity-50">
          {{ verified ? 'Verified' : (loading ? 'Verifying...' : 'Verify') }}
        </button>
      </form>

      <button @click="resend" :disabled="resending" class="w-full text-sm text-blue-600 hover:underline disabled:opacity-50">Resend code</button>
    </div>
  </div>
</template>

<script>
import apiService from '@/services/api'

export default {
  name: 'OtpVerification',
  data() {
    return { otp: '', loading: false, resending: false, error: '', message: '', verified: false }
  },
  methods: {
    async submitOtp() {
      if (this.verified) return
      this.error = ''
      this.message = ''
      const cleanOtp = (this.otp || '').toString().replace(/\D/g, '').trim()
      if (!cleanOtp || cleanOtp.length !== 6) {
        this.error = 'Please enter the 6-digit code.'
        return
      }
      this.loading = true
      try {
        // Get user email from localStorage
        const userData = localStorage.getItem('user_data')
        const user = userData ? JSON.parse(userData) : null
        
        if (!user || !user.email) {
          this.error = 'User data not found. Please login again.'
          return
        }

        // Use centralized API service with sanitized OTP
        const response = await apiService.request('/verify-otp', {
          method: 'POST',
          body: JSON.stringify({
            email: user.email,
            otp_code: cleanOtp
          })
        })

        if (response && response.success) {
          this.message = 'OTP verified successfully! Redirecting to password change...'
          this.verified = true
          
          // Store temporary token for password change
          if (response.data && response.data.temp_token) {
            localStorage.setItem('temp_token', response.data.temp_token)
          }
          
          // Update user data
          if (response.data && response.data.user) {
            localStorage.setItem('user_data', JSON.stringify(response.data.user))
          }
          
          // Redirect to password change page immediately
          this.$router.push('/change-password')
        } else {
          this.error = response?.message || 'OTP verification failed.'
        }
      } catch (e) {
        if (this.verified) return
        console.error('OTP verification error:', e)
        this.error = e?.response?.data?.message || e?.response?.data?.error || 'Invalid OTP. Please try again.'
      } finally {
        this.loading = false
      }
    },
    async resend() {
      this.error = ''
      this.message = ''
      this.resending = true
      try {
        const response = await apiService.request('/resend-otp', { method: 'POST' })
        if (response && response.success) {
          this.message = response.message || 'A new code was sent to your email.'
        } else {
          this.error = response?.message || 'Could not resend the code.'
        }
      } catch (e) {
        this.error = e?.message || 'Could not resend the code. Please try signing in again.'
      } finally {
        this.resending = false
      }
    }
  }
}
</script>

<style scoped>
</style>


