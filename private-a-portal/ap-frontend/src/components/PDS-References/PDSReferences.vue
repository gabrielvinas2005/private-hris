<template>
  <div class="pds-references">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">
      Character References
    </h3>

    <div class="space-y-6">
      <!-- References Section -->
      <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
        <div
          class="bg-gradient-to-r from-indigo-50 to-purple-50 px-6 py-4 border-b border-gray-200 rounded-t-lg"
        >
          <div class="flex justify-between items-center">
            <div>
              <h4
                class="text-lg font-semibold text-gray-900 inline-flex items-center mb-1"
              >
                <Users :size="20" class="mr-2 text-indigo-600" /> Character
                References
              </h4>
              <p class="text-sm text-gray-600">
                Provide at least 1 character reference who can vouch for your
                character and work ethic. You may add up to 3.
              </p>
            </div>
            <button
              @click="addReference"
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
              Add Reference
            </button>
          </div>
        </div>

        <div class="p-6">
          <div
            v-if="loading && referenceData.length === 0"
            class="text-center py-8 text-gray-500"
          >
            <div
              class="inline-flex items-center justify-center w-12 h-12 mb-4 bg-blue-100 rounded-full"
            >
              <div
                class="w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"
              ></div>
            </div>
            <p>Loading references...</p>
          </div>

          <div
            v-else-if="referenceData.length === 0"
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
            <p>
              No character references added yet. Click "Add Reference" to get
              started.
            </p>
          </div>

          <div v-else class="space-y-6">
            <div
              v-for="(reference, index) in referenceData"
              :key="reference.reference_id || reference.id || index"
              class="bg-gray-50 rounded-lg p-6 border border-gray-200"
            >
              <div class="flex justify-between items-start mb-4">
                <div class="flex items-center gap-3">
                  <div
                    class="w-8 h-8 bg-teal-100 rounded-full flex items-center justify-center"
                  >
                    <span class="text-teal-600 font-semibold text-sm">{{
                      index + 1
                    }}</span>
                  </div>
                  <h5 class="text-lg font-medium text-gray-800">
                    {{ reference.name || `Reference ${index + 1}` }}
                  </h5>
                </div>
                <button
                  @click="removeReference(index)"
                  class="text-red-600 hover:text-red-800 transition-colors duration-200 p-1 rounded-full hover:bg-red-50"
                  title="Remove reference"
                >
                  <svg
                    class="w-5 h-5"
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

              <!-- Name -->
              <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2"
                  >Full Name *</label
                >
                <input
                  v-model="reference.name"
                  @blur="validateReference(index)"
                  @input="clearReferenceError(index)"
                  type="text"
                  :class="[
                    'w-full px-3 py-2 border rounded-md text-sm focus:outline-none focus:ring-2',
                    referenceErrors[index]?.duplicate 
                      ? 'border-red-500 focus:ring-red-500 focus:border-red-500' 
                      : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500'
                  ]"
                  placeholder="e.g., Dr. Juan Santos Cruz"
                  required
                />
                <p 
                  v-if="referenceErrors[index]?.duplicate" 
                  class="mt-1 text-sm text-red-600"
                >
                  <i class="fas fa-exclamation-circle mr-1"></i>
                  This reference already exists. Please enter a different person.
                </p>
              </div>

              <!-- Address -->
              <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2"
                  >Complete Address</label
                >
                <textarea
                  v-model="reference.address"
                  rows="3"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  placeholder="e.g., 123 Main Street, Barangay San Jose, Quezon City, Metro Manila 1100"
                ></textarea>
              </div>

              <!-- Occupation and Contact Info -->
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2"
                    >Occupation/Position *</label
                  >
                  <input
                    v-model="reference.occupation"
                    type="text"
                    @blur="markTouched(index, 'occupation')"
                    @input="validateRequired(index, 'occupation', 'Occupation/Position is required')"
                    :class="[
                      'w-full px-3 py-2 border rounded-md text-sm focus:outline-none focus:ring-2',
                      referenceErrors[index]?.occupation
                        ? 'border-red-500 focus:ring-red-500 focus:border-red-500'
                        : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500',
                    ]"
                    placeholder="e.g., School Principal"
                    required
                  />
                  <p
                    v-if="referenceErrors[index]?.occupation"
                    class="mt-1 text-sm text-red-600"
                  >
                    <i class="fas fa-exclamation-circle mr-1"></i>
                    {{ referenceErrors[index]?.occupation }}
                  </p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2"
                    >Contact Number *</label
                  >
                  <input
                    v-model="reference.contactNumber"
                    @input="validateContactNumber(index, $event)"
                    @blur="validateContactNumber(index, $event)"
                    type="tel"
                    maxlength="11"
                    :class="[
                      'w-full px-3 py-2 border rounded-md text-sm focus:outline-none focus:ring-2',
                      referenceErrors[index]?.contactNumber 
                        ? 'border-red-500 focus:ring-red-500 focus:border-red-500' 
                        : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500'
                    ]"
                    placeholder="e.g., 09123456789"
                    required
                  />
                  <p 
                    v-if="referenceErrors[index]?.contactNumber" 
                    class="mt-1 text-sm text-red-600"
                  >
                    <i class="fas fa-exclamation-circle mr-1"></i>
                    {{ referenceErrors[index]?.contactNumber }}
                  </p>
                  <p v-else class="mt-1 text-xs text-gray-500">
                    Enter 11 digits only (numbers only)
                  </p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2"
                    >Email Address *</label
                  >
                  <input
                    v-model="reference.email"
                    @blur="markTouched(index, 'email'); validateEmail(index)"
                    @input="validateEmail(index)"
                    type="email"
                    :class="[
                      'w-full px-3 py-2 border rounded-md text-sm focus:outline-none focus:ring-2',
                      referenceErrors[index]?.email
                        ? 'border-red-500 focus:ring-red-500 focus:border-red-500'
                        : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500',
                    ]"
                    placeholder="e.g., juan.santos@email.com"
                    required
                  />
                  <p
                    v-if="referenceErrors[index]?.email"
                    class="mt-1 text-sm text-red-600"
                  >
                    <i class="fas fa-exclamation-circle mr-1"></i>
                    {{ referenceErrors[index]?.email }}
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- References Summary -->
        <div
          v-if="referenceData.length > 0"
          class="bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 rounded-xl border border-blue-200 shadow-sm p-6"
        >
          <div class="flex items-center gap-2 mb-4">
            <div
              class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center"
            >
              <svg
                class="w-6 h-6 text-blue-600"
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
            </div>
            <h5 class="text-lg font-semibold text-gray-800">
              References Summary
            </h5>
          </div>

          <div
            class="grid grid-cols-1 md:grid-cols-3 gap-4 max-w-4xl mx-auto"
          >
            <!-- Total References Card -->
            <div
              class="w-full bg-white rounded-lg p-4 shadow-sm border border-gray-100 hover:shadow-md transition-shadow"
            >
              <div class="flex items-center justify-between mb-2">
                <div
                  class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center"
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
                      d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                    ></path>
                  </svg>
                </div>
              </div>
              <div class="text-3xl font-bold text-gray-900 mb-1">
                {{ referenceData.length }}
              </div>
              <div class="text-sm text-gray-600 font-medium">
                Total References
              </div>
            </div>

            <!-- Complete Profiles Card -->
            <div
              class="w-full bg-white rounded-lg p-4 shadow-sm border border-gray-100 hover:shadow-md transition-shadow"
            >
              <div class="flex items-center justify-between mb-2">
                <div
                  class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center"
                >
                  <svg
                    class="w-5 h-5 text-green-600"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                    ></path>
                  </svg>
                </div>
              </div>
              <div class="text-3xl font-bold text-gray-900 mb-1">
                {{ completedReferences }}
              </div>
              <div class="text-sm text-gray-600 font-medium">
                Complete Profiles
              </div>
              <div class="text-xs text-gray-500 mt-1">
                {{ completionPercentage }}% complete
              </div>
            </div>

            <!-- Personal References Card -->
            <div
              class="w-full bg-white rounded-lg p-4 shadow-sm border border-gray-100 hover:shadow-md transition-shadow"
            >
              <div class="flex items-center justify-between mb-2">
                <div
                  class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center"
                >
                  <svg
                    class="w-5 h-5 text-purple-600"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                    ></path>
                  </svg>
                </div>
              </div>
              <div class="text-3xl font-bold text-gray-900 mb-1">
                {{ personalReferences }}
              </div>
              <div class="text-sm text-gray-600 font-medium">Personal</div>
            </div>
          </div>

          <!-- Additional Stats Row -->
          <div
            v-if="incompleteReferences > 0 || referenceData.length < 3"
            class="mt-4 pt-4 border-t border-gray-200"
          >
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div
                v-if="incompleteReferences > 0"
                class="flex items-center gap-3 text-sm bg-yellow-50 rounded-lg p-3 border border-yellow-200"
              >
                <div
                  class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center flex-shrink-0"
                >
                  <svg
                    class="w-4 h-4 text-yellow-600"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                    ></path>
                  </svg>
                </div>
                <div>
                  <div class="text-yellow-800 font-medium">
                    {{ incompleteReferences }} Incomplete
                    {{ incompleteReferences === 1 ? "Profile" : "Profiles" }}
                  </div>
                  <div class="text-yellow-600 text-xs">
                    Please complete all required fields
                  </div>
                </div>
              </div>
              <div
                v-if="referenceData.length < 3"
                class="flex items-center gap-3 text-sm bg-blue-50 rounded-lg p-3 border border-blue-200"
              >
                <div
                  class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0"
                >
                  <svg
                    class="w-4 h-4 text-blue-600"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                    ></path>
                  </svg>
                </div>
                <div>
                  <div class="text-blue-800 font-medium">
                    Recommended: {{ 3 - referenceData.length }} more
                    {{
                      3 - referenceData.length === 1
                        ? "reference"
                        : "references"
                    }}
                  </div>
                  <div class="text-blue-600 text-xs">
                    At least 3 references are recommended
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Important Notes -->
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
          <div class="flex items-start gap-3">
            <svg
              class="w-5 h-5 text-yellow-600 mt-0.5 flex-shrink-0"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
              ></path>
            </svg>
            <div>
              <h5 class="text-sm font-medium text-yellow-800 mb-1">
                Important Notes:
              </h5>
              <ul class="text-sm text-yellow-700 space-y-1">
                <li>
                  • Please ensure you have permission from your references
                  before listing them
                </li>
                <li>• Provide accurate and up-to-date contact information</li>
                <li>
                  • Choose references who know you well and can speak positively
                  about your character
                </li>
                <li>• Avoid listing family members as character references</li>
              </ul>
            </div>
          </div>
        </div>
      </div>

      <!-- Save Button -->
      <div class="flex justify-end">
        <button
          @click="saveReferences"
          :disabled="loading || !isFormValid"
          class="bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed text-white px-6 py-2 rounded-md font-medium transition-colors duration-200 flex items-center gap-2"
        >
          <svg
            v-if="loading"
            class="w-4 h-4 animate-spin"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
            ></path>
          </svg>
          {{ loading ? "Saving..." : "Save References" }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, computed, ref, onMounted } from "vue";
import { ElNotification, ElMessageBox } from "element-plus";
import ApiService from "@/services/api";
import { useAppNotification } from "@/composables/useAppNotification.js";
import { Users } from "lucide-vue-next";

const props = defineProps({
  info: {
    type: Object,
    default: () => ({}),
  },
});

const emit = defineEmits(["saved"]);

const notify = useAppNotification();

const referenceData = reactive([]);
const loading = ref(false);
const error = ref(null);
const employeeId = ref(null);
let nextReferenceId = 1;
const referenceErrors = reactive({});
const touched = reactive({});

const loadReferencesData = async () => {
  loading.value = true;
  error.value = null;

  try {
    // Fetch data from API first
    const response = await ApiService.getApplicantPage();

    if (response.data && response.data.data) {
      const data = response.data.data;

      // Capture employee id from API response (this is the correct source)
      employeeId.value =
        data.employee_info?.[0]?.id ??
        props.info?.employee_info?.[0]?.id ??
        props.info?.employee_id ??
        props.info?.id ??
        null;

      // Load references data
      referenceData.length = 0; // Clear existing data
      if (data.references && Array.isArray(data.references)) {
        data.references.forEach((reference) => {
          referenceData.push({
            id: nextReferenceId++,
            reference_id: reference.reference_id || null,
            name: reference.ref_name || "",
            address: reference.ref_address || "",
            occupation: reference.ref_occupation || "",
            contactNumber: reference.ref_contact_no || "",
            email: reference.ref_email || "",
            relationship: "", // Not in database
          });
        });
      }
    }
  } catch (err) {
    console.error("Error loading references data:", err);
    error.value =
      err.response?.data?.message || "Failed to load references data";
    notify.error("Load failed", error.value, { duration: 5000 });
  } finally {
    loading.value = false;
  }
};

const addReference = () => {
  referenceData.push({
    id: nextReferenceId++,
    reference_id: null,
    name: "",
    address: "",
    occupation: "",
    contactNumber: "",
    email: "",
    relationship: "",
  });
};

const removeReference = async (index) => {
  try {
    await ElMessageBox.confirm(
      "Are you sure you want to remove this reference?",
      "Remove Reference",
      {
        confirmButtonText: "Remove",
        cancelButtonText: "Cancel",
        type: "warning",
      }
    );
  } catch {
    return;
  }

  const reference = referenceData[index];
  const referenceId = reference.reference_id;

  // Remove from UI immediately for better UX
  referenceData.splice(index, 1);
  ElNotification({
    title: "Success",
    message: "Reference removed successfully!",
    type: "success",
    duration: 3000,
    position: "top-right",
  });

  // Only call API if this is an existing reference (has reference_id)
  if (referenceId) {
    try {
      await ApiService.destroyPDS(11, referenceId); // type_id 11 for references
    } catch (err) {
      // If deletion fails, restore the reference
      referenceData.splice(index, 0, reference);
      const errorMessage =
        err.response?.data?.message || "Failed to delete reference";
      ElNotification({
        title: "Error",
        message: errorMessage,
        type: "error",
        duration: 5000,
        position: "top-right",
      });
      console.error("Error deleting reference:", err);
    }
  }
};

const isFormValid = computed(() => {
  if (referenceData.length === 0) return false;

  return referenceData.every(
    (reference) =>
      reference.name &&
      reference.occupation &&
      reference.contactNumber &&
      reference.email
  );
});

const completedReferences = computed(() => {
  return referenceData.filter(
    (reference) =>
      reference.name &&
      reference.occupation &&
      reference.contactNumber &&
      reference.email
  ).length;
});

const professionalReferences = computed(() => {
  const professionalTypes = [
    "Former Supervisor",
    "Current Supervisor",
    "Colleague",
    "Former Manager",
    "Professional Mentor",
    "Business Partner",
    "Former Professor",
    "School Administrator",
    "Academic Advisor",
    "Thesis Advisor",
  ];
  return referenceData.filter((reference) =>
    professionalTypes.includes(reference.relationship)
  ).length;
});

const personalReferences = computed(() => {
  return referenceData.length - professionalReferences.value;
});

const incompleteReferences = computed(() => {
  return referenceData.length - completedReferences.value;
});

const completionPercentage = computed(() => {
  if (referenceData.length === 0) return 0;
  return Math.round((completedReferences.value / referenceData.length) * 100);
});

const checkDuplicateReference = (currentIndex, name, contactNumber, email) => {
  if (!name || name.trim() === '') return null;
  
  const normalizedName = name.trim().toLowerCase();
  const normalizedContact = contactNumber?.trim().toLowerCase() || '';
  const normalizedEmail = email?.trim().toLowerCase() || '';
  
  // Check against all other references (excluding current one)
  const duplicate = referenceData.find((ref, index) => {
    if (index === currentIndex) return false; // Skip current reference
    
    const refName = (ref.name || '').trim().toLowerCase();
    const refContact = (ref.contactNumber || '').trim().toLowerCase();
    const refEmail = (ref.email || '').trim().toLowerCase();
    
    // Check by name (primary check)
    if (refName === normalizedName) {
      // If name matches, also check contact or email for stronger validation
      if (normalizedContact && refContact === normalizedContact) return true;
      if (normalizedEmail && refEmail === normalizedEmail) return true;
      // If name matches exactly, consider it duplicate
      return true;
    }
    
    // Also check by contact number if provided
    if (normalizedContact && refContact && refContact === normalizedContact) {
      return true;
    }
    
    // Also check by email if provided
    if (normalizedEmail && refEmail && refEmail === normalizedEmail) {
      return true;
    }
    
    return false;
  });
  
  return duplicate;
};

const validateReference = (index) => {
  const reference = referenceData[index];
  if (!reference) return;
  
  const duplicate = checkDuplicateReference(
    index,
    reference.name,
    reference.contactNumber,
    reference.email
  );
  
  if (duplicate) {
    referenceErrors[index] = {
      duplicate: true,
      message: `This reference already exists (${duplicate.name}). Please enter a different person.`
    };
  } else {
    if (referenceErrors[index]) {
      delete referenceErrors[index];
    }
  }
};

const clearReferenceError = (index) => {
  if (referenceErrors[index]) {
    delete referenceErrors[index];
  }
};

const markTouched = (index, field) => {
  if (!touched[index]) touched[index] = {};
  touched[index][field] = true;
};

const validateRequired = (index, field, message) => {
  const reference = referenceData[index];
  if (!reference) return;
  if (!touched[index]?.[field]) return;

  const value = (reference[field] ?? "").toString().trim();
  if (!value) {
    referenceErrors[index] = {
      ...referenceErrors[index],
      [field]: message,
    };
  } else if (referenceErrors[index]?.[field]) {
    const errors = { ...referenceErrors[index] };
    delete errors[field];
    if (Object.keys(errors).length === 0) {
      delete referenceErrors[index];
    } else {
      referenceErrors[index] = errors;
    }
  }
};

const validateEmail = (index) => {
  const reference = referenceData[index];
  if (!reference) return;
  if (!touched[index]?.email) return;

  const email = (reference.email ?? "").toString().trim();
  if (!email) {
    referenceErrors[index] = {
      ...referenceErrors[index],
      email: "Email is required",
    };
    return;
  }

  if (!isValidEmail(email)) {
    referenceErrors[index] = {
      ...referenceErrors[index],
      email: "Please enter a valid email address",
    };
    return;
  }

  if (referenceErrors[index]?.email) {
    const errors = { ...referenceErrors[index] };
    delete errors.email;
    if (Object.keys(errors).length === 0) {
      delete referenceErrors[index];
    } else {
      referenceErrors[index] = errors;
    }
  }
};

const validateContactNumber = (index, event) => {
  const reference = referenceData[index];
  if (!reference) return;
  
  let contactNumber = event.target.value;
  
  // Remove any non-numeric characters
  contactNumber = contactNumber.replace(/\D/g, '');
  
  // Update the model with cleaned value
  reference.contactNumber = contactNumber;
  
  // Validate length and format
  if (contactNumber.length === 0) {
    // Clear error if field is empty (required validation will handle it)
    if (referenceErrors[index]?.contactNumber) {
      const errors = { ...referenceErrors[index] };
      delete errors.contactNumber;
      if (Object.keys(errors).length === 0) {
        delete referenceErrors[index];
      } else {
        referenceErrors[index] = errors;
      }
    }
    return;
  }
  
  if (contactNumber.length !== 11) {
    referenceErrors[index] = {
      ...referenceErrors[index],
      contactNumber: 'Contact number must be exactly 11 digits'
    };
  } else if (!/^\d{11}$/.test(contactNumber)) {
    referenceErrors[index] = {
      ...referenceErrors[index],
      contactNumber: 'Contact number must contain only numbers'
    };
  } else {
    // Clear contact number error if valid
    if (referenceErrors[index]?.contactNumber) {
      const errors = { ...referenceErrors[index] };
      delete errors.contactNumber;
      if (Object.keys(errors).length === 0) {
        delete referenceErrors[index];
      } else {
        referenceErrors[index] = errors;
      }
    }
  }
};










const saveReferences = async () => {
  // Mark required fields as touched so errors show after attempting to save.
  referenceData.forEach((_, idx) => {
    markTouched(idx, "occupation");
    markTouched(idx, "email");
  });
  referenceData.forEach((_, idx) => {
    validateRequired(idx, "occupation", "Occupation/Position is required");
    validateEmail(idx);
  });

  if (!isFormValid.value) {
    notify.error(
      "Validation",
      "Please fill in all required fields (Name, Occupation, Contact Number, and Email) for all references",
      { duration: 4000 },
    );
    return;
  }

  // Check for duplicates before saving
  const duplicates = [];
  referenceData.forEach((ref, index) => {
    const duplicate = checkDuplicateReference(
      index,
      ref.name,
      ref.contactNumber,
      ref.email
    );
    if (duplicate) {
      duplicates.push({
        index: index + 1,
        name: ref.name || 'Reference ' + (index + 1)
      });
    }
  });
  
  if (duplicates.length > 0) {
    const duplicateList = duplicates.map(d => `Reference ${d.index}: ${d.name}`).join('\n');
    notify.error(
      "Validation",
      `Duplicate references detected:\n${duplicateList}\n\nPlease remove or modify duplicate entries before saving.`,
      { duration: 7000 },
    );
    return;
  }

  // Validate contact numbers (must be exactly 11 digits, numbers only)
  const invalidContactNumbers = [];
  referenceData.forEach((ref, index) => {
    const contactNumber = (ref.contactNumber || '').trim();
    if (!contactNumber) {
      invalidContactNumbers.push({
        index: index + 1,
        name: ref.name || 'Reference ' + (index + 1),
        error: 'Contact number is required'
      });
    } else if (!/^\d{11}$/.test(contactNumber)) {
      invalidContactNumbers.push({
        index: index + 1,
        name: ref.name || 'Reference ' + (index + 1),
        error: contactNumber.length !== 11 
          ? 'Contact number must be exactly 11 digits' 
          : 'Contact number must contain only numbers'
      });
    }
  });

  if (invalidContactNumbers.length > 0) {
    const invalidList = invalidContactNumbers.map(
      icn => `Reference ${icn.index} (${icn.name}): ${icn.error}`
    ).join('\n');
    notify.error(
      "Validation",
      `Invalid contact numbers:\n${invalidList}\n\nPlease fix all contact numbers before saving.`,
      { duration: 7000 },
    );
    return;
  }

  // Validate email format (required)
  const invalidEmails = referenceData.filter(
    (reference) => !reference.email || !isValidEmail(reference.email),
  );

  if (invalidEmails.length > 0) {
    notify.error(
      "Validation",
      "Please enter valid email addresses for all references",
      { duration: 4000 },
    );
    return;
  }

  // Get employee ID if not already set
  if (employeeId.value === null || employeeId.value === undefined) {
    try {
      const response = await ApiService.getApplicantPage();
      if (response.data && response.data.data) {
        employeeId.value =
          response.data.data.employee_info?.[0]?.id ??
          props.info?.employee_info?.[0]?.id ??
          props.info?.employee_id ??
          props.info?.id ??
          null;
      }
    } catch (err) {
      console.error("Error fetching employee ID:", err);
      notify.error("Error", "Failed to fetch employee information", {
        duration: 5000,
      });
      return;
    }
  }

  if (employeeId.value === null || employeeId.value === undefined) {
    notify.error(
      "Error",
      "Unable to determine employee ID. Please refresh the page and try again.",
      { duration: 5000 },
    );
    return;
  }

  loading.value = true;
  error.value = null;

  try {
    // Prepare references payload
    const referencesPayload = referenceData.map((ref) => ({
      reference_id: ref.reference_id || null,
      name: ref.name || "",
      address: ref.address || "",
      occupation: ref.occupation || "",
      contactNumber: ref.contactNumber || "",
      email: ref.email || "",
      // Note: relationship is not saved as it doesn't exist in the database
    }));

    // Prepare FormData
    const formData = new FormData();
    formData.append("section", "references");
    formData.append("references", JSON.stringify(referencesPayload));

    // Save to backend
    const response = await ApiService.storePDS(employeeId.value, formData);

    if (response.data && response.data.success) {
      notify.success("Saved", "References saved successfully!", {
        duration: 4000,
      });

      // Reload data to get updated reference_ids
      await loadReferencesData();
      emit("saved");
    } else {
      throw new Error(response.data?.message || "Failed to save references");
    }
  } catch (err) {
    console.error("Error saving references:", err);
    const msg =
      err.response?.data?.message ||
      (err.response?.data?.errors
        ? Object.values(err.response.data.errors).flat().join(" ")
        : null) ||
      err.message ||
      "Failed to save references.";
    error.value = msg;
    notify.error("Save failed", msg, { duration: 6000 });
  } finally {
    loading.value = false;
  }
};

const isValidEmail = (email) => {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return emailRegex.test(email);
};

// Load data when component is mounted
onMounted(() => {
  loadReferencesData();
});
</script>

<style scoped>
/* Custom styles for better visual hierarchy */
.reference-card {
  transition: all 0.2s ease-in-out;
}

.reference-card:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

/* Select dropdown styling */
select {
  background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
  background-position: right 0.5rem center;
  background-repeat: no-repeat;
  background-size: 1.5em 1.5em;
  padding-right: 2.5rem;
}

/* Optgroup styling */
optgroup {
  font-weight: bold;
  color: #374151;
}

optgroup option {
  font-weight: normal;
  padding-left: 1rem;
}

/* Textarea styling */
textarea {
  resize: vertical;
  min-height: 2.5rem;
}

/* Note: we avoid global :valid/:invalid border styling and instead apply
   field-level validation classes so new entries don't look "wrong" immediately. */
</style>
