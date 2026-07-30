<template>
  <!-- Modal Backdrop -->
  <div
    class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
  >
    <!-- Modal Container -->
    <div
      class="bg-white rounded-lg shadow-xl max-w-7xl w-full mx-4 max-h-[95vh] overflow-y-auto"
    >
      <!-- Modal Header -->
      <div
        class="flex items-center justify-between p-4 border-b bg-white sticky top-0 z-10"
      >
        <h2 class="text-xl font-semibold text-gray-900">
          Edit Family Information
        </h2>
        <button
          @click="$emit('close')"
          class="text-gray-400 hover:text-gray-600 transition-colors"
        >
          <i class="fas fa-times text-xl"></i>
        </button>
      </div>

      <!-- Modal Content -->
      <div class="px-4 py-6 bg-gray-50">
        <div class="space-y-6">
          <!-- Parents Section -->
          <div class="bg-white rounded-lg shadow-sm border p-6">
            <h5 class="text-md font-medium text-gray-800 mb-4 border-b pb-2">
              Parents
            </h5>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
              <!-- Father -->
              <div class="space-y-4">
                <h5
                  class="text-sm font-medium text-gray-700 bg-blue-50 px-3 py-2 rounded"
                >
                  Father
                </h5>
                <div class="grid grid-cols-2 gap-4">
                  <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1"
                      >Prefix</label
                    >
                    <select
                      v-model="familyData.father.prefix"
                      class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                      <option value="">Select Prefix</option>
                      <option
                        v-for="prefix in prefixOptions"
                        :key="prefix.id"
                        :value="prefix.id"
                      >
                        {{ prefix.name }}
                      </option>
                    </select>
                  </div>
                  <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1"
                      >Suffix</label
                    >
                    <select
                      v-model="familyData.father.suffix"
                      class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                      <option value="">Select Suffix</option>
                      <option
                        v-for="suffix in suffixOptions"
                        :key="suffix.id"
                        :value="suffix.id"
                      >
                        {{ suffix.name }}
                      </option>
                    </select>
                  </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1"
                      >First Name</label
                    >
                    <input
                      v-model="familyData.father.firstName"
                      type="text"
                      class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                      placeholder="First name"
                    />
                  </div>
                  <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1"
                      >Middle Name</label
                    >
                    <input
                      v-model="familyData.father.middleName"
                      type="text"
                      class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                      placeholder="Middle name"
                    />
                  </div>
                  <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1"
                      >Last Name</label
                    >
                    <input
                      v-model="familyData.father.lastName"
                      type="text"
                      class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                      placeholder="Last name"
                    />
                  </div>
                </div>
              </div>

              <!-- Mother -->
              <div class="space-y-4">
                <h5
                  class="text-sm font-medium text-gray-700 bg-pink-50 px-3 py-2 rounded"
                >
                  Mother
                </h5>
                <div class="grid grid-cols-2 gap-4">
                  <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1"
                      >Prefix</label
                    >
                    <select
                      v-model="familyData.mother.prefix"
                      class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                      <option value="">Select Prefix</option>
                      <option
                        v-for="prefix in prefixOptions"
                        :key="prefix.id"
                        :value="prefix.id"
                      >
                        {{ prefix.name }}
                      </option>
                    </select>
                  </div>
                  <!-- <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1"
                      >Suffix</label
                    >
                    <select
                      v-model="familyData.mother.suffix"
                      class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                      <option value="">Select Suffix</option>
                      <option
                        v-for="suffix in suffixOptions"
                        :key="suffix.id"
                        :value="suffix.id"
                      >
                        {{ suffix.name }}
                      </option>
                    </select>
                  </div> -->
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1"
                      >First Name</label
                    >
                    <input
                      v-model="familyData.mother.firstName"
                      type="text"
                      class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                      placeholder="First name"
                    />
                  </div>
                  <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1"
                      >Middle Name</label
                    >
                    <input
                      v-model="familyData.mother.middleName"
                      type="text"
                      class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                      placeholder="Middle name"
                    />
                  </div>
                  <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1"
                      >Last Name</label
                    >
                    <input
                      v-model="familyData.mother.lastName"
                      type="text"
                      class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                      placeholder="Last name"
                    />
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Spouse Section -->
          <div class="bg-white rounded-lg shadow-sm border p-6">
            <h4 class="text-md font-medium text-gray-800 mb-4 border-b pb-2">
              Spouse
            </h4>

            <div class="space-y-4">
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-medium text-gray-500 mb-1"
                    >Prefix</label
                  >
                  <select
                    v-model="familyData.spouse.prefix"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                  >
                    <option value="">Select Prefix</option>
                    <option
                      v-for="prefix in prefixOptions"
                      :key="prefix.id"
                      :value="prefix.id"
                    >
                      {{ prefix.name }}
                    </option>
                  </select>
                </div>
                <div>
                  <label class="block text-xs font-medium text-gray-500 mb-1"
                    >Suffix</label
                  >
                  <select
                    v-model="familyData.spouse.suffix"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                  >
                    <option value="">Select Suffix</option>
                    <option
                      v-for="suffix in suffixOptions"
                      :key="suffix.id"
                      :value="suffix.id"
                    >
                      {{ suffix.name }}
                    </option>
                  </select>
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                  <label class="block text-xs font-medium text-gray-500 mb-1"
                    >First Name</label
                  >
                  <input
                    v-model="familyData.spouse.firstName"
                    type="text"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="First name"
                  />
                </div>
                <div>
                  <label class="block text-xs font-medium text-gray-500 mb-1"
                    >Middle Name</label
                  >
                  <input
                    v-model="familyData.spouse.middleName"
                    type="text"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Middle name"
                  />
                </div>
                <div>
                  <label class="block text-xs font-medium text-gray-500 mb-1"
                    >Last Name</label
                  >
                  <input
                    v-model="familyData.spouse.lastName"
                    type="text"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Last name"
                  />
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-medium text-gray-500 mb-1"
                    >Occupation</label
                  >
                  <input
                    v-model="familyData.spouse.occupation"
                    type="text"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Occupation"
                  />
                </div>
                <div>
                  <label class="block text-xs font-medium text-gray-500 mb-1"
                    >Employer</label
                  >
                  <input
                    v-model="familyData.spouse.employer"
                    type="text"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Employer/Company"
                  />
                </div>
              </div>

              <div>
                <label class="block text-xs font-medium text-gray-500 mb-1"
                  >Business Address</label
                >
                <textarea
                  v-model="familyData.spouse.businessAddress"
                  rows="2"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                  placeholder="Complete business address (if applicable)"
                ></textarea>
              </div>
            </div>
          </div>

          <!-- Children Section -->
          <div class="bg-white rounded-lg shadow-sm border p-6">
            <div class="flex justify-between items-center mb-4 border-b pb-2">
              <h4 class="text-md font-medium text-gray-800">Children</h4>
              <button
                @click="addChild"
                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200 flex items-center gap-2"
              >
                <svg
                  class="w-4 h-4"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 4v16m8-8H4"
                  ></path>
                </svg>
                Add Child
              </button>
            </div>

            <div
              v-if="familyData.children.length === 0"
              class="text-center py-8 text-gray-500"
            >
              <svg
                class="w-12 h-12 mx-auto mb-4 text-gray-300"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                ></path>
              </svg>
              <p>No children added yet. Click "Add Child" to get started.</p>
            </div>

            <div v-else class="space-y-4">
              <div
                v-for="(child, index) in familyData.children"
                :key="index"
                class="bg-gray-50 rounded-lg p-4 border border-gray-200"
              >
                <div class="flex justify-between items-start mb-3">
                  <h5 class="text-sm font-medium text-gray-700">
                    Child {{ index + 1 }}
                  </h5>
                  <button
                    @click="removeChild(index)"
                    class="text-red-600 hover:text-red-800 transition-colors duration-200"
                    title="Remove child"
                  >
                    <svg
                      class="w-4 h-4"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M6 18L18 6M6 6l12 12"
                      ></path>
                    </svg>
                  </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                  <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1"
                      >First Name</label
                    >
                    <input
                      v-model="child.firstName"
                      type="text"
                      class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                      placeholder="First name"
                    />
                  </div>
                  <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1"
                      >Middle Name</label
                    >
                    <input
                      v-model="child.middleName"
                      type="text"
                      class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                      placeholder="Middle name"
                    />
                  </div>
                  <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1"
                      >Last Name</label
                    >
                    <input
                      v-model="child.lastName"
                      type="text"
                      class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                      placeholder="Last name"
                    />
                  </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1"
                      >Gender</label
                    >
                    <select
                      v-model="child.gender"
                      class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                      <option value="0">Select gender</option>
                      <option
                        v-for="gender in genderOptions"
                        :key="gender.id"
                        :value="gender.id"
                      >
                        {{ gender.name }}
                      </option>
                    </select>
                  </div>
                  <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1"
                      >Birth Date</label
                    >
                    <input
                      v-model="child.birthdate"
                      type="date"
                      class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal Footer -->
      <div
        class="flex items-center justify-end gap-3 px-6 py-4 border-t bg-gray-50 sticky bottom-0"
      >
        <button
          @click="$emit('close')"
          :disabled="saving"
          class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          Cancel
        </button>
        <button
          @click="saveFamilyInfo"
          :disabled="saving"
          class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
        >
          <div
            v-if="saving"
            class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"
          ></div>
          {{ saving ? "Saving..." : "Save Family Information" }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { watch, onMounted, ref } from "vue";
import { useFamilyInfo } from "@/composables/useFamilyInfo.js";
import { ApiService } from "@/services/api.js";

const props = defineProps({
  info: {
    type: Object,
    default: () => ({}),
  },
  employeeId: {
    type: [Number, String],
    default: null,
  },
});

const emit = defineEmits(["close", "save"]);

const {
  saving,
  prefixOptions,
  suffixOptions,
  familyData,
  addChild,
  removeChild,
  fetchPrefixSuffixOptions,
  initializeFromProps,
  saveFamilyInfo: saveFamilyInfoComposable,
} = useFamilyInfo();

const genderOptions = ref([]);

// Initialize data from props
watch(
  () => props.info,
  (newInfo) => {
    if (newInfo && Object.keys(newInfo).length > 0) {
      initializeFromProps(newInfo);
    }
  },
  { immediate: true, deep: true }
);

// Load dropdown data when component mounts
onMounted(async () => {
  await fetchPrefixSuffixOptions();
  
  // Fetch gender options
  try {
    const gendersRes = await ApiService.getGenders();
    if (gendersRes.data && gendersRes.data.data && gendersRes.data.data.genders) {
      genderOptions.value = gendersRes.data.data.genders.map(item => ({
        id: item.id,
        name: item.name,
        value: item.id
      }));
    }
  } catch (err) {
    console.error("Error loading genders:", err);
  }
  
  // After dropdowns are loaded, ensure prefix/suffix values are properly set
  if (props.info && Object.keys(props.info).length > 0) {
    initializeFromProps(props.info);
  }
});

const saveFamilyInfo = async () => {
  try {
    await saveFamilyInfoComposable(props.employeeId);
    // Emit save event with updated data
    emit("save", { ...familyData });
    emit("close");
  } catch (error) {
    // Error is already handled in the composable
    console.error("Error saving family information:", error);
  }
};
</script>

<style scoped>
/* Loading animation */
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
