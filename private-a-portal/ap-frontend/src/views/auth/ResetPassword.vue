<template>
  <div
    class="min-h-screen bg-gradient-to-br from-blue-600 via-blue-700 to-blue-800 flex items-center justify-center px-4"
  >
    <div
      class="w-full max-w-md bg-white rounded-3xl shadow-2xl p-8 text-gray-900"
    >
      <h1 class="text-2xl font-semibold mb-2 text-center">
        Set a new password
      </h1>
      <p class="text-sm text-gray-600 mb-6 text-center">
        Enter your new password below. This link will expire after a short
        time, for your security.
      </p>

      <div v-if="!token || !email" class="text-center text-red-600">
        The reset link is invalid or incomplete. Please request a new password
        reset email.
      </div>

      <form v-else class="space-y-4" @submit.prevent="handleSubmit">
        <div>
          <label class="block text-gray-700 mb-2">Email Address</label>
          <input
            :value="email"
            type="email"
            disabled
            class="w-full px-4 py-3 border border-gray-300 rounded-xl bg-gray-100 text-gray-500"
          />
        </div>

        <div>
          <label class="block text-gray-700 mb-2">New Password</label>
          <input
            v-model="password"
            type="password"
            required
            placeholder="Enter new password"
            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent transition-all"
          />
        </div>

        <div>
          <label class="block text-gray-700 mb-2">Confirm New Password</label>
          <input
            v-model="passwordConfirmation"
            type="password"
            required
            placeholder="Confirm new password"
            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent transition-all"
          />
        </div>

        <button
          type="submit"
          :disabled="isSubmitting"
          class="w-full py-3 mt-2 bg-gradient-to-r from-blue-600 to-blue-800 text-white rounded-xl hover:shadow-lg transition-all disabled:opacity-50"
        >
          <span v-if="isSubmitting">Updating password...</span>
          <span v-else>Update password</span>
        </button>

        <button
          type="button"
          class="w-full py-2 mt-2 text-sm text-blue-600 hover:text-blue-700 hover:underline"
          @click="goToLogin"
        >
          Back to login
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import { ApiService } from "@/services/api.js";
import { ElNotification } from "element-plus";

const route = useRoute();
const router = useRouter();

const token = ref("");
const email = ref("");
const password = ref("");
const passwordConfirmation = ref("");
const isSubmitting = ref(false);

onMounted(() => {
  token.value = route.query.token || "";
  email.value = route.query.email || "";
});

const handleSubmit = async () => {
  if (!password.value || !passwordConfirmation.value) {
    return;
  }

  if (password.value !== passwordConfirmation.value) {
    ElNotification({
      title: "Password mismatch",
      message: "The password confirmation does not match.",
      type: "error",
      duration: 5000,
      position: "top-right",
    });
    return;
  }

  if (isSubmitting.value) {
    return;
  }

  isSubmitting.value = true;

  try {
    await ApiService.resetPassword({
      token: token.value,
      email: email.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value,
    });

    ElNotification({
      title: "Password updated",
      message: "Your password has been reset successfully. You can now sign in.",
      type: "success",
      duration: 6000,
      position: "top-right",
    });

    router.push("/login");
  } catch (error) {
    console.error("Reset password error:", error);
    const message =
      error.response?.data?.message ||
      "We were unable to reset your password. The reset link may have expired.";
    ElNotification({
      title: "Reset failed",
      message,
      type: "error",
      duration: 6000,
      position: "top-right",
    });
  } finally {
    isSubmitting.value = false;
  }
};

const goToLogin = () => {
  router.push("/login");
};
</script>

