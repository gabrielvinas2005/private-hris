<template>
  <div
    class="min-h-screen bg-gray-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8"
  >
    <div class="max-w-md w-full space-y-8">
      <div>
        <div class="flex justify-center">
          <div
            class="h-16 w-16 bg-blue-600 rounded-full flex items-center justify-center"
          >
            <span class="text-white font-bold text-xl">WTI</span>
          </div>
        </div>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
          Verify Your Account
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600">
          Enter the 6-digit code sent to your email
        </p>
      </div>
      <form class="mt-8 space-y-6" @submit.prevent="handleVerifyOTP">
        <div>
          <label for="otp" class="block text-sm font-medium text-gray-700 mb-2">
            Verification Code
          </label>
          <input
            id="otp"
            v-model="otpCode"
            name="otp"
            type="text"
            maxlength="6"
            pattern="[0-9]{6}"
            required
            class="appearance-none relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm text-center text-2xl font-mono tracking-widest"
            placeholder="000000"
          />
          <p class="mt-2 text-sm text-gray-500">
            Please check your email for the verification code
          </p>
        </div>

        <div>
          <button
            type="submit"
            :disabled="isLoading || otpCode.length !== 6"
            class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
          >
            <span
              v-if="isLoading"
              class="absolute left-0 inset-y-0 flex items-center pl-3"
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
            </span>
            {{ isLoading ? "Verifying..." : "Verify Code" }}
          </button>
        </div>

        <div class="text-center">
          <button
            type="button"
            @click="resendOTP"
            :disabled="isResending"
            class="font-medium text-blue-600 hover:text-blue-500 disabled:opacity-50"
          >
            {{ isResending ? "Resending..." : "Resend Code" }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import { ApiService } from "../../services/api.js";
import { ElNotification } from "element-plus";

export default {
  name: "VerifyOTP",
  data() {
    return {
      otpCode: "",
      isLoading: false,
      isResending: false,
    };
  },
  methods: {
    async handleVerifyOTP() {
      this.isLoading = true;

      try {
        const response = await ApiService.verifyOTP(this.otpCode);

        if (
          response.data &&
          response.data.data &&
          response.data.data.verified
        ) {
          ElNotification({
            title: "OTP verified",
            message:
              "Verification successful! Redirecting to change your password.",
            type: "success",
            duration: 3000,
            position: "top-right",
          });

          setTimeout(() => {
            // Always redirect to change password after OTP verification
            this.$router.push("/change-password");
          }, 1500);
        } else {
          throw new Error("Invalid verification code");
        }
      } catch (error) {
        console.error("OTP verification error:", error);
        const message =
          error.response?.data?.message ||
          "Invalid verification code. Please try again.";
        ElNotification({
          title: "Verification failed",
          message,
          type: "error",
          duration: 5000,
          position: "top-right",
        });
      } finally {
        this.isLoading = false;
      }
    },

    async resendOTP() {
      this.isResending = true;

      try {
        const response = await ApiService.resendOTP();

        if (response.data && response.data.data && response.data.data.resent) {
          this.otpCode = ""; // Clear the input field
          ElNotification({
            title: "Code resent",
            message: "Verification code resent! Please check your email.",
            type: "success",
            duration: 4000,
            position: "top-right",
          });
        } else {
          throw new Error("Failed to resend code");
        }
      } catch (error) {
        console.error("Resend OTP error:", error);
        const message =
          error.response?.data?.message ||
          "Failed to resend verification code.";
        ElNotification({
          title: "Resend failed",
          message,
          type: "error",
          duration: 5000,
          position: "top-right",
        });
      } finally {
        this.isResending = false;
      }
    },
  },
  mounted() {
    // Check if user has auth token, if not redirect to login
    const token = localStorage.getItem("auth_token");
    if (!token) {
      this.$router.push("/login");
    }
  },
};
</script>
