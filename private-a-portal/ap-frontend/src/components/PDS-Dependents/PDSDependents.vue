<template>
  <div class="pds-dependents">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Dependents</h3>

    <div class="space-y-6">
      <!-- Dependents Section -->
      <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
        <div
          class="bg-gradient-to-r from-indigo-50 to-purple-50 px-6 py-4 border-b border-gray-200 rounded-t-lg"
        >
          <div class="flex justify-between items-center">
            <div>
              <h4
                class="text-lg font-semibold text-gray-900 inline-flex items-center mb-1"
              >
                <Users :size="20" class="mr-2 text-indigo-600" /> Family
                Dependents
              </h4>
              <p class="text-sm text-gray-600">
                List all your dependents for whom you are financially
                responsible.
              </p>
            </div>
            <button
              @click="addDependent"
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
              Add Dependent
            </button>
          </div>
        </div>

        <div class="p-6">
          <div
            v-if="loading && dependentsData.length === 0"
            class="text-center py-8 text-gray-500"
          >
            <div
              class="inline-flex items-center justify-center w-12 h-12 mb-4 bg-blue-100 rounded-full"
            >
              <div
                class="w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"
              ></div>
            </div>
            <p>Loading dependents...</p>
          </div>

          <div
            v-else-if="dependentsData.length === 0"
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
              No dependents added yet. Click "Add Dependent" to get started.
            </p>
          </div>

          <div v-else class="space-y-4">
            <div
              v-for="(dependent, index) in dependentsData"
              :key="dependent.id || dependent.dependent_id || index"
              class="bg-gray-50 rounded-lg p-6 border border-gray-200"
            >
              <div class="flex justify-between items-start mb-4">
                <div class="flex items-center gap-3">
                  <div
                    class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center"
                  >
                    <span class="text-purple-600 font-semibold text-sm">{{
                      index + 1
                    }}</span>
                  </div>
                  <h5 class="text-lg font-medium text-gray-800">
                    {{ dependent.name || `Dependent ${index + 1}` }}
                  </h5>
                </div>
                <button
                  @click="removeDependent(index)"
                  class="text-red-600 hover:text-red-800 transition-colors duration-200 p-1 rounded-full hover:bg-red-50"
                  title="Remove dependent"
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

              <!-- Dependent Information -->
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Name -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2"
                    >Full Name *</label
                  >
                  <input
                    v-model="dependent.name"
                    type="text"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    placeholder="e.g., Maria Santos Cruz"
                    required
                  />
                </div>

                <!-- Relationship -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2"
                    >Relationship *</label
                  >
                  <select
                    v-model="dependent.relationship"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    required
                  >
                    <option value="">Select Relationship</option>
                    <optgroup label="Spouse">
                      <option value="Spouse">Spouse</option>
                    </optgroup>
                    <optgroup label="Children">
                      <option value="Son">Son</option>
                      <option value="Daughter">Daughter</option>
                      <option value="Stepson">Stepson</option>
                      <option value="Stepdaughter">Stepdaughter</option>
                      <option value="Adopted Son">Adopted Son</option>
                      <option value="Adopted Daughter">Adopted Daughter</option>
                    </optgroup>
                    <optgroup label="Parents">
                      <option value="Father">Father</option>
                      <option value="Mother">Mother</option>
                      <option value="Stepfather">Stepfather</option>
                      <option value="Stepmother">Stepmother</option>
                      <option value="Father-in-law">Father-in-law</option>
                      <option value="Mother-in-law">Mother-in-law</option>
                    </optgroup>
                    <optgroup label="Siblings">
                      <option value="Brother">Brother</option>
                      <option value="Sister">Sister</option>
                      <option value="Half-brother">Half-brother</option>
                      <option value="Half-sister">Half-sister</option>
                    </optgroup>
                    <optgroup label="Other Relatives">
                      <option value="Grandchild">Grandchild</option>
                      <option value="Grandparent">Grandparent</option>
                      <option value="Nephew">Nephew</option>
                      <option value="Niece">Niece</option>
                      <option value="Uncle">Uncle</option>
                      <option value="Aunt">Aunt</option>
                      <option value="Cousin">Cousin</option>
                      <option value="Other">Other</option>
                    </optgroup>
                  </select>
                </div>

                <!-- Course Sought -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2"
                    >Course Sought/Current Course</label
                  >
                  <input
                    v-model="dependent.courseSought"
                    type="text"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    placeholder="e.g., Bachelor of Science in Computer Science"
                  />
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Dependents Summary -->
        <div
          v-if="dependentsData.length > 0"
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
                  d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
                ></path>
              </svg>
            </div>
            <h5 class="text-lg font-semibold text-gray-800">
              Dependents Summary
            </h5>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Total Dependents Card -->
            <div
              class="bg-white rounded-lg p-4 shadow-sm border border-gray-100 hover:shadow-md transition-shadow"
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
                      d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
                    ></path>
                  </svg>
                </div>
              </div>
              <div class="text-3xl font-bold text-gray-900 mb-1">
                {{ dependentsData.length }}
              </div>
              <div class="text-sm text-gray-600 font-medium">
                Total Dependents
              </div>
            </div>

            <!-- Children Card -->
            <div
              class="bg-white rounded-lg p-4 shadow-sm border border-gray-100 hover:shadow-md transition-shadow"
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
                {{ childrenCount }}
              </div>
              <div class="text-sm text-gray-600 font-medium">Children</div>
            </div>

            <!-- Students Card -->
            <div
              class="bg-white rounded-lg p-4 shadow-sm border border-gray-100 hover:shadow-md transition-shadow"
            >
              <div class="flex items-center justify-between mb-2">
                <div
                  class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center"
                >
                  <svg
                    class="w-5 h-5 text-indigo-600"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
                    ></path>
                  </svg>
                </div>
              </div>
              <div class="text-3xl font-bold text-gray-900 mb-1">
                {{ studentsCount }}
              </div>
              <div class="text-sm text-gray-600 font-medium">Students</div>
            </div>

            <!-- Spouse Card -->
            <div
              class="bg-white rounded-lg p-4 shadow-sm border border-gray-100 hover:shadow-md transition-shadow"
            >
              <div class="flex items-center justify-between mb-2">
                <div
                  class="w-10 h-10 bg-pink-100 rounded-lg flex items-center justify-center"
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
                      d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"
                    ></path>
                  </svg>
                </div>
              </div>
              <div class="text-3xl font-bold text-gray-900 mb-1">
                {{ spouseCount }}
              </div>
              <div class="text-sm text-gray-600 font-medium">Spouse</div>
            </div>
          </div>

          <!-- Additional Stats Row -->
          <div
            v-if="incompleteDependents > 0"
            class="mt-4 pt-4 border-t border-gray-200"
          >
            <div
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
                  {{ incompleteDependents }} Incomplete
                  {{ incompleteDependents === 1 ? "Profile" : "Profiles" }}
                </div>
                <div class="text-yellow-600 text-xs">
                  Please complete all required fields for all dependents
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Information Note -->
      <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex items-start gap-3">
          <svg
            class="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0"
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
            <h5 class="text-sm font-medium text-blue-800 mb-1">
              Information Notes:
            </h5>
            <ul class="text-sm text-blue-700 space-y-1">
              <li>
                • Include only dependents for whom you are financially
                responsible
              </li>
              <li>
                • For students, specify their current course or degree program
              </li>
              <li>
                • Update this information when your dependent's status changes
              </li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Save Button -->
      <div class="flex justify-end">
        <button
          @click="saveDependents"
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
          {{ loading ? "Saving..." : "Save Dependents Information" }}
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

const notify = useAppNotification();

const dependentsData = reactive([]);
const loading = ref(false);
const error = ref(null);
const employeeId = ref(null);
let nextDependentId = 1;

const loadDependentsData = async () => {
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

      // Load dependents data
      dependentsData.length = 0; // Clear existing data
      if (data.dependents && Array.isArray(data.dependents)) {
        data.dependents.forEach((dependent) => {
          dependentsData.push({
            id: nextDependentId++,
            dependent_id: dependent.id || null,
            name: dependent.name || "",
            relationship: dependent.relationship || "",
            courseSought: dependent.course || "",
            age: "", // Not in database
            school: "", // Not in database
            status: "", // Not in database
          });
        });
      }
    }
  } catch (err) {
    console.error("Error loading dependents data:", err);
    error.value =
      err.response?.data?.message || "Failed to load dependents data";
    notify.error("Load failed", error.value, { duration: 5000 });
  } finally {
    loading.value = false;
  }
};

const addDependent = () => {
  dependentsData.push({
    id: nextDependentId++,
    dependent_id: null,
    name: "",
    relationship: "",
    courseSought: "",
    age: "",
    school: "",
    status: "",
  });
};

const removeDependent = async (index) => {
  try {
    await ElMessageBox.confirm(
      "Are you sure you want to remove this dependent?",
      "Remove Dependent",
      {
        confirmButtonText: "Remove",
        cancelButtonText: "Cancel",
        type: "warning",
      }
    );
  } catch {
    return;
  }

  const dependent = dependentsData[index];
  const dependentId = dependent.dependent_id;

  // Remove from UI immediately for better UX
  dependentsData.splice(index, 1);
  ElNotification({
    title: "Success",
    message: "Dependent removed successfully!",
    type: "success",
    duration: 3000,
    position: "top-right",
  });

  // Only call API if this is an existing dependent (has dependent_id)
  if (dependentId) {
    try {
      await ApiService.destroyPDS(12, dependentId); // type_id 12 for dependents
    } catch (err) {
      // If deletion fails, restore the dependent
      dependentsData.splice(index, 0, dependent);
      const errorMessage =
        err.response?.data?.message || "Failed to delete dependent";
      ElNotification({
        title: "Error",
        message: errorMessage,
        type: "error",
        duration: 5000,
        position: "top-right",
      });
      console.error("Error deleting dependent:", err);
    }
  }
};

const isFormValid = computed(() => {
  if (dependentsData.length === 0) return true; // Allow saving with no dependents

  return dependentsData.every(
    (dependent) => dependent.name && dependent.relationship
  );
});

const childrenCount = computed(() => {
  const childRelationships = [
    "Son",
    "Daughter",
    "Stepson",
    "Stepdaughter",
    "Adopted Son",
    "Adopted Daughter",
  ];
  return dependentsData.filter((dependent) =>
    childRelationships.includes(dependent.relationship)
  ).length;
});

const studentsCount = computed(() => {
  return dependentsData.filter(
    (dependent) => dependent.status === "Student" || dependent.courseSought
  ).length;
});

const spouseCount = computed(() => {
  return dependentsData.filter(
    (dependent) => dependent.relationship === "Spouse"
  ).length;
});

const incompleteDependents = computed(() => {
  return dependentsData.filter(
    (dependent) => !dependent.name || !dependent.relationship
  ).length;
});

const saveDependents = async () => {
  if (!isFormValid.value) {
    notify.error(
      "Validation",
      "Please fill in the Name and Relationship for all dependents",
      { duration: 4000 },
    );
    return;
  }

  // Validate age values
  const invalidAges = dependentsData.filter(
    (dependent) =>
      dependent.age !== null &&
      dependent.age !== undefined &&
      (dependent.age < 0 || dependent.age > 120)
  );

  if (invalidAges.length > 0) {
    notify.error(
      "Validation",
      "Please enter valid ages (0-120) for all dependents",
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
    // Prepare dependents payload (only save fields that exist in database)
    const dependentsPayload = dependentsData.map((dep) => ({
      id: dep.dependent_id || null,
      name: dep.name || "",
      relationship: dep.relationship || "",
      courseSought: dep.courseSought || "",
      // Note: age, school, and status are not saved as they don't exist in the database
    }));

    // Prepare FormData
    const formData = new FormData();
    formData.append("section", "dependents");
    formData.append("dependents", JSON.stringify(dependentsPayload));

    // Save to backend
    const response = await ApiService.storePDS(employeeId.value, formData);

    if (response.data && response.data.success) {
      notify.success("Saved", "Dependents information saved successfully!", {
        duration: 4000,
      });

      // Reload data to get updated dependent_ids
      await loadDependentsData();
    } else {
      throw new Error(response.data?.message || "Failed to save dependents");
    }
  } catch (err) {
    console.error("Error saving dependents:", err);
    const msg =
      err.response?.data?.message ||
      (err.response?.data?.errors
        ? Object.values(err.response.data.errors).flat().join(" ")
        : null) ||
      err.message ||
      "Failed to save dependents.";
    error.value = msg;
    notify.error("Save failed", msg, { duration: 6000 });
  } finally {
    loading.value = false;
  }
};

// Load data when component is mounted
onMounted(() => {
  loadDependentsData();
});
</script>

<style scoped>
/* Custom styles for better visual hierarchy */
.dependent-card {
  transition: all 0.2s ease-in-out;
}

.dependent-card:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

/* Select dropdown styling */
select {
  -webkit-appearance: none;
  -moz-appearance: none;
  appearance: none;
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

/* Number input styling */
input[type="number"]::-webkit-outer-spin-button,
input[type="number"]::-webkit-inner-spin-button {
  appearance: none;
  -webkit-appearance: none;
  margin: 0;
}

input[type="number"] {
  appearance: textfield;
  -moz-appearance: textfield;
}
</style>
