<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-blue-50">
    <nav class="bg-white/80 backdrop-blur-md shadow-sm sticky top-0 z-40">
      <div class="max-w-7xl mx-auto px-6 py-4">
        <div class="flex items-center justify-between">
          <button
            class="flex items-center gap-2 text-blue-600 hover:text-blue-700 transition-colors"
            @click="handleNavigate('/')"
          >
            <ArrowLeft class="w-5 h-5" />
            <span class="cursor-pointer hover:underline">Back to Home</span>
          </button>
          <div class="flex items-center gap-2">
            <span class="text-gray-600">Already have an account?</span>
            <button
              class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
              @click="handleNavigate('/login')"
            >
              Sign In
            </button>
          </div>
        </div>
      </div>
    </nav>

    <div class="max-w-4xl mx-auto px-6 py-12">
      <div class="flex justify-center mb-6 text-blue-600">
        <User class="w-12 h-12" />
      </div>
      <div class="text-center mb-12">
        <h1 class="text-4xl text-gray-900 mb-4">Create Your Account</h1>
        <p class="text-xl text-gray-600">
          Join {{ shortName }} and start your career journey
        </p>
      </div>

      <div class="bg-white rounded-2xl shadow-xl p-8 md:p-12">
        <form @submit.prevent="handleSubmit">
          <div class="space-y-6">
            <div>
              <h2 class="text-2xl text-gray-900 mb-2">Personal Information</h2>
              <p class="text-gray-600">Let's start with your basic details</p>
              <p class="text-sm text-gray-500 mt-2">
                A temporary password will be emailed after you submit your
                registration.
              </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-gray-700 mb-2">
                  First Name <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="formData.firstName"
                  type="text"
                  required
                  class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent transition-all"
                  placeholder="Enter First Name"
                />
              </div>
              <div>
                <label class="block text-gray-700 mb-2">
                  Middle Name
                  <span class="text-gray-400 text-xs">(optional)</span>
                </label>
                <input
                  v-model="formData.middleName"
                  type="text"
                  class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent transition-all"
                  placeholder="Enter Middle Name"
                />
              </div>
              <div>
                <label class="block text-gray-700 mb-2">
                  Last Name <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="formData.lastName"
                  type="text"
                  required
                  class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent transition-all"
                  placeholder="Enter Last Name"
                />
              </div>
              <div>
                <label class="block text-gray-700 mb-2">
                  Email Address <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="formData.email"
                  type="email"
                  required
                  class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent transition-all"
                  placeholder="Enter Email Address"
                />
              </div>
              <div>
                <label class="block text-gray-700 mb-2">
                  Phone Number <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                  <div
                    class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-600 font-medium pointer-events-none"
                  >
                    +63
                  </div>
                  <input
                    v-model="formData.phone"
                    @input="validatePhone"
                    @blur="validatePhone"
                    type="tel"
                    maxlength="11"
                    required
                    :class="[
                      'w-full pl-12 pr-4 py-3 border rounded-xl focus:outline-none focus:ring-2 transition-all',
                      phoneError
                        ? 'border-red-500 focus:ring-red-500 focus:border-red-500'
                        : 'border-gray-300 focus:ring-blue-600 focus:border-transparent',
                    ]"
                    placeholder="Enter Phone Number"
                  />
                </div>
                <p v-if="phoneError" class="text-red-500 text-sm mt-1">
                  <i class="fas fa-exclamation-circle mr-1"></i>
                  {{ phoneError }}
                </p>
              </div>
              <div>
                <label class="block text-gray-700 mb-2">
                  Date of Birth <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="formData.dateOfBirth"
                  type="date"
                  required
                  @blur="validateAge()"
                  class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent transition-all"
                />
                <p v-if="ageError" class="text-red-500 text-sm mt-1">
                  You must be at least 18 years old to register.
                </p>
              </div>
              <!-- <div>
                <label class="block text-gray-700 mb-2">
                  City <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="formData.city"
                  type="text"
                  required
                  class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent transition-all"
                  placeholder="Manila"
                />
              </div> -->
            </div>

            <div>
              <label class="block text-gray-700 mb-2"
                >Resume <span class="text-red-500">*</span></label
              >
              <input
                @change="handleResumeChange"
                type="file"
                accept=".pdf,.doc,.docx"
                required
                :class="[
                  'w-full px-4 py-3 border rounded-xl focus:outline-none focus:ring-2 transition-all',
                  resumeError
                    ? 'border-red-500 focus:ring-red-500 focus:border-red-500'
                    : 'border-gray-300 focus:ring-blue-600 focus:border-transparent',
                ]"
              />
              <p v-if="resumeError" class="text-red-500 text-sm mt-1">
                <i class="fas fa-exclamation-circle mr-1"></i>
                {{ resumeError }}
              </p>
              <p v-else class="text-gray-500 text-xs mt-1">
                Maximum file size: 2MB. Accepted formats: PDF, DOC, DOCX
              </p>
            </div>
          </div>

          <div class="flex justify-end mt-8 pt-8 border-t">
            <button
              type="submit"
              :disabled="isSubmitting"
              class="px-8 py-3 bg-gradient-to-r from-blue-500 to-blue-800 text-white font-bold rounded-xl transition-all duration-150 hover:shadow-xl hover:-translate-y-0.5 hover:from-blue-600 hover:to-blue-900 disabled:opacity-60 disabled:cursor-not-allowed flex items-center gap-2"
            >
              <svg
                v-if="isSubmitting"
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
              {{
                isSubmitting
                  ? "Processing Registration..."
                  : "Complete Registration"
              }}
            </button>
          </div>

          <div
            v-if="isSubmitting"
            class="mt-4 p-4 rounded-xl bg-blue-50 border border-blue-200 text-blue-700 text-sm flex items-center gap-2"
          >
            <svg
              class="animate-spin h-4 w-4 text-blue-600"
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
            <span
              >Please wait while we process your registration. This may take a
              few moments...</span
            >
          </div>
          <div
            v-if="submitError"
            class="mt-4 p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm"
          >
            {{ submitError }}
          </div>
        </form>
      </div>

      <p class="text-center text-gray-600 mt-8">
        Need help?
        <a
          href="mailto:support@privatehris.com"
          class="text-blue-600 hover:text-blue-700"
          >support@privatehris.com</a
        >
      </p>
    </div>
  </div>
</template>

<script setup>
import { ArrowLeft, User } from "@element-plus/icons-vue";
import { useRegistration } from "@/composables/useRegistration.js";
import { useCompanyBranding } from "@/composables/useCompanyBranding.js";
import { onMounted } from "vue";

const { shortName, loadCompany } = useCompanyBranding();

onMounted(() => {
  loadCompany();
});

const {
  formData,
  isSubmitting,
  submitError,
  ageError,
  phoneError,
  resumeError,
  computedAge,
  validateAge,
  validatePhone,
  handleNavigate,
  handleResumeChange,
  handleSubmit,
} = useRegistration();
</script>

<style scoped>
.transition-colors {
  transition: all 0.2s ease;
}
</style>
