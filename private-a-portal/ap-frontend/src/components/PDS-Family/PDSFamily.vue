<template>
  <div
    class="pds-family min-h-screen px-4 py-8 bg-gradient-to-br from-gray-50 to-gray-100"
  >
    <!-- Loading State -->
    <div v-if="loading" class="max-w-7xl mx-auto text-center py-20">
      <div
        class="inline-flex items-center justify-center w-16 h-16 mb-6 bg-blue-100 rounded-full"
      >
        <div
          class="w-10 h-10 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"
        ></div>
      </div>
      <p class="text-lg text-gray-600">Loading family information...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="max-w-7xl mx-auto text-center py-20">
      <div
        class="inline-flex items-center justify-center w-16 h-16 mb-6 bg-red-100 rounded-full"
      >
        <svg
          class="w-8 h-8 text-red-500"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
          />
        </svg>
      </div>
      <p class="mb-6 text-lg text-red-600">{{ error }}</p>
      <button
        @click="fetchFamilyInfo"
        class="px-6 py-3 text-white bg-blue-600 rounded-lg shadow-md hover:bg-blue-700 transition-colors"
      >
        Try Again
      </button>
    </div>

    <!-- Main Content -->
    <div v-else class="max-w-7xl mx-auto">
      <!-- Page Header -->
      <div
        class="mb-8 bg-white rounded-xl shadow-sm border border-gray-200 p-6"
      >
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-gray-900 mb-1">
              Family Information
            </h1>
            <p class="text-sm text-gray-500">
              Manage your family background details
            </p>
          </div>
          <div class="flex items-center gap-3">
            <button
              @click="fetchFamilyInfo"
              class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
            >
              <svg
                class="w-4 h-4 mr-2"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                />
              </svg>
              Refresh
            </button>
            <button
              @click="showModal = true"
              class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 shadow-sm transition-colors"
            >
              <svg
                class="w-4 h-4 mr-2"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                />
              </svg>
              Edit
            </button>
          </div>
        </div>
      </div>

      <div class="space-y-6">
        <!-- Parents Section -->
        <div
          class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden"
        >
          <div
            class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 border-b border-gray-200"
          >
            <h2 class="text-lg font-semibold text-gray-900 flex items-center">
              <svg
                class="w-5 h-5 mr-2 text-blue-600"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
                />
              </svg>
              Parents
            </h2>
          </div>

          <div class="p-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
              <!-- Father -->
              <div class="space-y-5">
                <div class="flex items-center pb-3 border-b-2 border-blue-500">
                  <div
                    class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-3"
                  >
                    <svg
                      class="w-5 h-5 text-blue-600"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                      />
                    </svg>
                  </div>
                  <h3 class="text-base font-semibold text-gray-800">Father</h3>
                </div>

                <div class="grid grid-cols-2 gap-4">
                  <div>
                    <label
                      class="block text-xs font-semibold text-gray-600 mb-2"
                      >Prefix</label
                    >
                    <div
                      class="px-4 py-3 bg-gray-50 rounded-lg border border-gray-200 text-sm text-gray-800"
                    >
                      {{ getPrefixName(familyData.father.prefix) }}
                    </div>
                  </div>
                  <div>
                    <label
                      class="block text-xs font-semibold text-gray-600 mb-2"
                      >Suffix</label
                    >
                    <div
                      class="px-4 py-3 bg-gray-50 rounded-lg border border-gray-200 text-sm text-gray-800"
                    >
                      {{ getSuffixName(familyData.father.suffix) }}
                    </div>
                  </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div>
                    <label
                      class="block text-xs font-semibold text-gray-600 mb-2"
                      >First Name</label
                    >
                    <div
                      class="px-4 py-3 bg-gray-50 rounded-lg border border-gray-200 text-sm text-gray-800 font-medium"
                    >
                      {{ familyData.father.firstName || "-" }}
                    </div>
                  </div>
                  <div>
                    <label
                      class="block text-xs font-semibold text-gray-600 mb-2"
                      >Middle Name</label
                    >
                    <div
                      class="px-4 py-3 bg-gray-50 rounded-lg border border-gray-200 text-sm text-gray-800"
                    >
                      {{ familyData.father.middleName || "-" }}
                    </div>
                  </div>
                  <div>
                    <label
                      class="block text-xs font-semibold text-gray-600 mb-2"
                      >Last Name</label
                    >
                    <div
                      class="px-4 py-3 bg-gray-50 rounded-lg border border-gray-200 text-sm text-gray-800 font-medium"
                    >
                      {{ familyData.father.lastName || "-" }}
                    </div>
                  </div>
                </div>
              </div>

              <!-- Mother -->
              <div class="space-y-5">
                <div class="flex items-center pb-3 border-b-2 border-pink-500">
                  <div
                    class="w-10 h-10 bg-pink-100 rounded-lg flex items-center justify-center mr-3"
                  >
                    <svg
                      class="w-5 h-5 text-pink-600"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                      />
                    </svg>
                  </div>
                  <h3 class="text-base font-semibold text-gray-800">Mother</h3>
                </div>

                <div class="grid grid-cols-2 gap-4">
                  <div>
                    <label
                      class="block text-xs font-semibold text-gray-600 mb-2"
                      >Prefix</label
                    >
                    <div
                      class="px-4 py-3 bg-gray-50 rounded-lg border border-gray-200 text-sm text-gray-800"
                    >
                      {{ getPrefixName(familyData.mother.prefix) }}
                    </div>
                  </div>
                  <div>
                    <label
                      class="block text-xs font-semibold text-gray-600 mb-2"
                      >Suffix</label
                    >
                    <div
                      class="px-4 py-3 bg-gray-50 rounded-lg border border-gray-200 text-sm text-gray-800"
                    >
                      {{ getSuffixName(familyData.mother.suffix) }}
                    </div>
                  </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div>
                    <label
                      class="block text-xs font-semibold text-gray-600 mb-2"
                      >First Name</label
                    >
                    <div
                      class="px-4 py-3 bg-gray-50 rounded-lg border border-gray-200 text-sm text-gray-800 font-medium"
                    >
                      {{ familyData.mother.firstName || "-" }}
                    </div>
                  </div>
                  <div>
                    <label
                      class="block text-xs font-semibold text-gray-600 mb-2"
                      >Middle Name</label
                    >
                    <div
                      class="px-4 py-3 bg-gray-50 rounded-lg border border-gray-200 text-sm text-gray-800"
                    >
                      {{ familyData.mother.middleName || "-" }}
                    </div>
                  </div>
                  <div>
                    <label
                      class="block text-xs font-semibold text-gray-600 mb-2"
                      >Last Name</label
                    >
                    <div
                      class="px-4 py-3 bg-gray-50 rounded-lg border border-gray-200 text-sm text-gray-800 font-medium"
                    >
                      {{ familyData.mother.lastName || "-" }}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Spouse Section -->
        <div
          class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden"
        >
          <div
            class="bg-gradient-to-r from-purple-50 to-pink-50 px-6 py-4 border-b border-gray-200"
          >
            <h2 class="text-lg font-semibold text-gray-900 flex items-center">
              <svg
                class="w-5 h-5 mr-2 text-purple-600"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"
                />
              </svg>
              Spouse
            </h2>
          </div>

          <div class="p-6 space-y-5">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-semibold text-gray-600 mb-2"
                  >Prefix</label
                >
                <div
                  class="px-4 py-3 bg-gray-50 rounded-lg border border-gray-200 text-sm text-gray-800"
                >
                  {{ getPrefixName(familyData.spouse.prefix) }}
                </div>
              </div>
              <div>
                <label class="block text-xs font-semibold text-gray-600 mb-2"
                  >Suffix</label
                >
                <div
                  class="px-4 py-3 bg-gray-50 rounded-lg border border-gray-200 text-sm text-gray-800"
                >
                  {{ getSuffixName(familyData.spouse.suffix) }}
                </div>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <label class="block text-xs font-semibold text-gray-600 mb-2"
                  >First Name</label
                >
                <div
                  class="px-4 py-3 bg-gray-50 rounded-lg border border-gray-200 text-sm text-gray-800 font-medium"
                >
                  {{ familyData.spouse.firstName || "-" }}
                </div>
              </div>
              <div>
                <label class="block text-xs font-semibold text-gray-600 mb-2"
                  >Middle Name</label
                >
                <div
                  class="px-4 py-3 bg-gray-50 rounded-lg border border-gray-200 text-sm text-gray-800"
                >
                  {{ familyData.spouse.middleName || "-" }}
                </div>
              </div>
              <div>
                <label class="block text-xs font-semibold text-gray-600 mb-2"
                  >Last Name</label
                >
                <div
                  class="px-4 py-3 bg-gray-50 rounded-lg border border-gray-200 text-sm text-gray-800 font-medium"
                >
                  {{ familyData.spouse.lastName || "-" }}
                </div>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-semibold text-gray-600 mb-2"
                  >Occupation</label
                >
                <div
                  class="px-4 py-3 bg-gray-50 rounded-lg border border-gray-200 text-sm text-gray-800"
                >
                  {{ familyData.spouse.occupation || "-" }}
                </div>
              </div>
              <div>
                <label class="block text-xs font-semibold text-gray-600 mb-2"
                  >Employer</label
                >
                <div
                  class="px-4 py-3 bg-gray-50 rounded-lg border border-gray-200 text-sm text-gray-800"
                >
                  {{ familyData.spouse.employer || "-" }}
                </div>
              </div>
            </div>

            <div>
              <label class="block text-xs font-semibold text-gray-600 mb-2"
                >Business Address</label
              >
              <div
                class="px-4 py-3 bg-gray-50 rounded-lg border border-gray-200 text-sm text-gray-800 min-h-[60px]"
              >
                {{ familyData.spouse.businessAddress || "-" }}
              </div>
            </div>
          </div>
        </div>

        <!-- Children Section -->
        <div
          class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden"
        >
          <div
            class="bg-gradient-to-r from-green-50 to-emerald-50 px-6 py-4 border-b border-gray-200"
          >
            <h2 class="text-lg font-semibold text-gray-900 flex items-center">
              <svg
                class="w-5 h-5 mr-2 text-green-600"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                />
              </svg>
              Children
            </h2>
          </div>

          <div class="p-6">
            <div
              v-if="familyData.children.length === 0"
              class="text-center py-12"
            >
              <div
                class="inline-flex items-center justify-center w-16 h-16 mb-4 bg-gray-100 rounded-full"
              >
                <svg
                  class="w-8 h-8 text-gray-400"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                  />
                </svg>
              </div>
              <p class="text-gray-500 text-sm">No children added yet</p>
            </div>

            <div v-else class="space-y-4">
              <div
                v-for="(child, index) in familyData.children"
                :key="index"
                class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-lg p-5 border border-gray-200"
              >
                <div class="flex justify-between items-center mb-4">
                  <div class="flex items-center">
                    <div
                      class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center mr-3"
                    >
                      <span class="text-xs font-bold text-green-600">{{
                        index + 1
                      }}</span>
                    </div>
                    <h3 class="text-sm font-semibold text-gray-700">
                      Child {{ index + 1 }}
                    </h3>
                  </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                  <div>
                    <label
                      class="block text-xs font-semibold text-gray-600 mb-2"
                      >First Name</label
                    >
                    <div
                      class="px-4 py-3 bg-white rounded-lg border border-gray-200 text-sm text-gray-800"
                    >
                      {{ child.firstName || "-" }}
                    </div>
                  </div>
                  <div>
                    <label
                      class="block text-xs font-semibold text-gray-600 mb-2"
                      >Middle Name</label
                    >
                    <div
                      class="px-4 py-3 bg-white rounded-lg border border-gray-200 text-sm text-gray-800"
                    >
                      {{ child.middleName || "-" }}
                    </div>
                  </div>
                  <div>
                    <label
                      class="block text-xs font-semibold text-gray-600 mb-2"
                      >Last Name</label
                    >
                    <div
                      class="px-4 py-3 bg-white rounded-lg border border-gray-200 text-sm text-gray-800"
                    >
                      {{ child.lastName || "-" }}
                    </div>
                  </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label
                      class="block text-xs font-semibold text-gray-600 mb-2"
                      >Gender</label
                    >
                    <div
                      class="px-4 py-3 bg-white rounded-lg border border-gray-200 text-sm text-gray-800"
                    >
                      {{ getGenderName(child.gender) }}
                    </div>
                  </div>
                  <div>
                    <label
                      class="block text-xs font-semibold text-gray-600 mb-2"
                      >Birth Date</label
                    >
                    <div
                      class="px-4 py-3 bg-white rounded-lg border border-gray-200 text-sm text-gray-800"
                    >
                      {{ child.birthdate || "-" }}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Family Modal -->
    <EditFamily
      v-if="showModal"
      :info="familyData"
      :employee-id="familyData.id"
      @close="showModal = false"
      @save="handleSave"
    />
  </div>
</template>

<script setup>
import { onMounted } from "vue";
import { useFamilyInfo } from "@/composables/useFamilyInfo.js";
import EditFamily from "./EditFamily.vue";

const {
  loading,
  error,
  showModal,
  familyData,
  getPrefixName,
  getSuffixName,
  getGenderName,
  fetchPrefixSuffixOptions,
  fetchFamilyInfo,
} = useFamilyInfo();

const handleSave = (updatedData) => {
  if (updatedData) {
    Object.assign(familyData, updatedData);
  }
  fetchFamilyInfo();
};

onMounted(async () => {
  // Ensure loading is true before starting any async operations
  loading.value = true;
  await fetchPrefixSuffixOptions();
  fetchFamilyInfo();
});
</script>

<style scoped>
@keyframes spin {
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1s linear infinite;
}
</style>
