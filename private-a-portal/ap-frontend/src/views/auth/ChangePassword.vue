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
          Change Your Password
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600">
          For security purposes, please create a new password
        </p>
      </div>

      <form class="mt-8 space-y-6" @submit.prevent="handleChangePassword">
        <div class="space-y-4">
          <div>
            <label
              for="current_password"
              class="block text-sm font-medium text-gray-700 mb-2"
            >
              Current Password
            </label>
            <input
              id="current_password"
              v-model="form.current_password"
              name="current_password"
              type="password"
              required
              class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
              placeholder="Enter your current password"
            />
          </div>

          <div>
            <label
              for="new_password"
              class="block text-sm font-medium text-gray-700 mb-2"
            >
              New Password
            </label>
            <input
              id="new_password"
              v-model="form.new_password"
              name="new_password"
              type="password"
              required
              class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
              placeholder="Enter your new password"
            />
            <div class="mt-2">
              <div class="text-xs text-gray-500">Password requirements:</div>
              <ul class="text-xs text-gray-500 mt-1 space-y-1">
                <li
                  :class="
                    passwordValidation.minLength
                      ? 'text-green-600'
                      : 'text-red-500'
                  "
                >
                  <i
                    :class="
                      passwordValidation.minLength
                        ? 'fas fa-check'
                        : 'fas fa-times'
                    "
                  ></i>
                  At least 8 characters
                </li>
                <li
                  :class="
                    passwordValidation.hasUppercase
                      ? 'text-green-600'
                      : 'text-red-500'
                  "
                >
                  <i
                    :class="
                      passwordValidation.hasUppercase
                        ? 'fas fa-check'
                        : 'fas fa-times'
                    "
                  ></i>
                  One uppercase letter
                </li>
                <li
                  :class="
                    passwordValidation.hasLowercase
                      ? 'text-green-600'
                      : 'text-red-500'
                  "
                >
                  <i
                    :class="
                      passwordValidation.hasLowercase
                        ? 'fas fa-check'
                        : 'fas fa-times'
                    "
                  ></i>
                  One lowercase letter
                </li>
                <li
                  :class="
                    passwordValidation.hasNumber
                      ? 'text-green-600'
                      : 'text-red-500'
                  "
                >
                  <i
                    :class="
                      passwordValidation.hasNumber
                        ? 'fas fa-check'
                        : 'fas fa-times'
                    "
                  ></i>
                  One number
                </li>
              </ul>
            </div>
          </div>

          <div>
            <label
              for="confirm_password"
              class="block text-sm font-medium text-gray-700 mb-2"
            >
              Confirm New Password
            </label>
            <input
              id="confirm_password"
              v-model="form.confirm_password"
              name="confirm_password"
              type="password"
              required
              class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
              placeholder="Confirm your new password"
            />
            <div
              v-if="form.confirm_password && !passwordsMatch"
              class="mt-1 text-xs text-red-500"
            >
              <i class="fas fa-times mr-1"></i>
              Passwords do not match
            </div>
            <div
              v-if="form.confirm_password && passwordsMatch"
              class="mt-1 text-xs text-green-600"
            >
              <i class="fas fa-check mr-1"></i>
              Passwords match
            </div>
          </div>
        </div>

        <div>
          <button
            type="submit"
            :disabled="isLoading || !isFormValid"
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
            {{ isLoading ? "Changing Password..." : "Change Password" }}
          </button>
        </div>

        <!-- Security Note -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
          <div class="flex">
            <div class="flex-shrink-0">
              <i class="fas fa-info-circle text-blue-400"></i>
            </div>
            <div class="ml-3">
              <h3 class="text-sm font-medium text-blue-800">Security Notice</h3>
              <div class="mt-2 text-sm text-blue-700">
                <p>
                  After changing your password successfully, you will be
                  automatically redirected. For first-time setup, you'll go to
                  the dashboard. For security updates, you'll need to log in
                  again.
                </p>
              </div>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import { ApiService } from "../../services/api.js";
import { ElNotification } from "element-plus";

export default {
  name: "ChangePassword",
  data() {
    return {
      form: {
        current_password: "",
        new_password: "",
        confirm_password: "",
      },
      isLoading: false,
    };
  },
  computed: {
    passwordValidation() {
      const password = this.form.new_password;
      return {
        minLength: password.length >= 8,
        hasUppercase: /[A-Z]/.test(password),
        hasLowercase: /[a-z]/.test(password),
        hasNumber: /\d/.test(password),
      };
    },
    isPasswordValid() {
      return Object.values(this.passwordValidation).every((valid) => valid);
    },
    passwordsMatch() {
      return (
        this.form.new_password &&
        this.form.confirm_password &&
        this.form.new_password === this.form.confirm_password
      );
    },
    isFormValid() {
      return (
        this.form.current_password &&
        this.isPasswordValid &&
        this.passwordsMatch
      );
    },
  },
  methods: {
    async handleChangePassword() {
      this.isLoading = true;

      try {
        const response = await ApiService.changePassword({
          current_password: this.form.current_password,
          new_password: this.form.new_password,
          new_password_confirmation: this.form.confirm_password,
        });

        if (response.data && response.data.success) {
          const responseData = response.data.data;
          const isFirstTime = responseData.is_first_time;

          console.log("Password change response:", response.data);
          console.log("Is first time:", isFirstTime);

          ElNotification({
            title: "Password changed",
            message: responseData.message || "Password changed successfully!",
            type: "success",
            duration: 3000,
            position: "top-right",
          });

          // Update user data in localStorage to reflect password change
          const userData = JSON.parse(
            localStorage.getItem("user_data") || "{}"
          );
          userData.has_change_password = true;
          localStorage.setItem("user_data", JSON.stringify(userData));

          // Clear form
          this.form = {
            current_password: "",
            new_password: "",
            confirm_password: "",
          };

          setTimeout(() => {
            if (isFirstTime) {
              console.log("Redirecting to dashboard for first-time user");
              // First-time password change - go to dashboard
              this.$router.push("/dashboard");
            } else {
              console.log("Redirecting to login for regular password change");
              // Regular password change - logout and go to login
              localStorage.removeItem("auth_token");
              localStorage.removeItem("user_data");
              this.$router.push("/login");
            }
          }, 2000);
        } else {
          throw new Error("Failed to change password");
        }
      } catch (error) {
        console.error("Change password error:", error);
        let message =
          error.response?.data?.message ||
          "Failed to change password. Please try again.";
        if (error.response?.data?.data?.errors) {
          // Handle validation errors
          const errors = error.response.data.data.errors;
          message = Object.values(errors).flat().join(", ");
        }
        ElNotification({
          title: "Change password failed",
          message,
          type: "error",
          duration: 5000,
          position: "top-right",
        });
      } finally {
        this.isLoading = false;
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

<style scoped>
.fa-check {
  color: #10b981;
}
.fa-times {
  color: #ef4444;
}
</style>
