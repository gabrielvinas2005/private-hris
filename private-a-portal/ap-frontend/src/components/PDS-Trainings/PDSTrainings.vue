<template>
  <div class="pds-trainings">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">
      Learning and Development (L&D) Interventions/Training Programs Attended
    </h3>

    <div class="space-y-6">
      <!-- Training Entries Section -->
      <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
        <div
          class="bg-gradient-to-r from-indigo-50 to-purple-50 px-6 py-4 border-b border-gray-200 rounded-t-lg"
        >
          <div class="flex justify-between items-center">
            <h4
              class="text-lg font-semibold text-gray-900 inline-flex items-center"
            >
              <GraduationCap :size="20" class="mr-2 text-indigo-600" /> Training
              Records
            </h4>
            <button
              @click="addTraining"
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
              Add Training
            </button>
          </div>
        </div>

        <div class="p-6">
          <div
            v-if="loading && trainingData.length === 0"
            class="text-center py-8 text-gray-500"
          >
            <div
              class="inline-flex items-center justify-center w-12 h-12 mb-4 bg-blue-100 rounded-full"
            >
              <div
                class="w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"
              ></div>
            </div>
            <p>Loading training records...</p>
          </div>

          <div
            v-else-if="trainingData.length === 0"
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
                d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
              ></path>
            </svg>
            <p>
              No training records added yet. Click "Add Training" to get
              started.
            </p>
          </div>

          <div v-else class="space-y-6">
            <div
              v-for="(training, index) in trainingData"
              :key="training.training_id || training.id"
              class="bg-gray-50 rounded-lg p-6 border border-gray-200"
            >
              <div class="flex justify-between items-start mb-4">
                <div class="flex items-center gap-3">
                  <div
                    class="w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center"
                  >
                    <span class="text-indigo-600 font-semibold text-sm">{{
                      index + 1
                    }}</span>
                  </div>
                  <h5 class="text-lg font-medium text-gray-800">
                    {{ training.seminar || "Training Record" }}
                  </h5>
                </div>
                <button
                  @click="removeTraining(index)"
                  class="text-red-600 hover:text-red-800 transition-colors duration-200 p-1 rounded-full hover:bg-red-50"
                  title="Remove training record"
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

              <!-- Training/Seminar Title -->
              <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2"
                  >Training/Seminar/Conference Title *</label
                >
                <input
                  v-model="training.seminar"
                  type="text"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  placeholder="e.g., Leadership Development Program, Data Analysis Workshop"
                  required
                />
              </div>

              <!-- Training Period and Hours -->
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2"
                    >From Date *</label
                  >
                  <input
                    v-model="training.dateFrom"
                    type="date"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    @change="calculateHours(training)"
                    required
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2"
                    >To Date *</label
                  >
                  <input
                    v-model="training.dateTo"
                    type="date"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    @change="calculateHours(training)"
                    :min="training.dateFrom"
                    required
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2"
                    >Number of Hours *</label
                  >
                  <div class="relative">
                    <input
                      v-model.number="training.hours"
                      type="number"
                      step="0.5"
                      min="0.5"
                      class="w-full px-3 py-2 pr-12 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                      placeholder="8"
                      required
                    />
                    <span class="absolute right-3 top-2 text-xs text-gray-400"
                      >hours</span
                    >
                  </div>
                </div>
              </div>

              <!-- Training Type -->
              <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2"
                  >Type of LD (Learning & Development)</label
                >
                <select
                  v-model.number="training.learningId"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                >
                  <option :value="0">Select Training Type</option>
                  <option
                    v-for="option in learningOptions"
                    :key="option.id"
                    :value="option.id"
                  >
                    {{ option.name }}
                  </option>
                </select>
              </div>

              <!-- Sponsored By -->
              <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2"
                  >Sponsored By/Conducted By *</label
                >
                <input
                  v-model="training.sponsoredBy"
                  type="text"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  placeholder="e.g., Department of Education, CSC, Private Institution"
                  required
                />
              </div>

              <!-- Skills Development/Competencies field removed per UX decision -->
            </div>
          </div>
        </div>

        <!-- Training Summary -->
        <div
          v-if="trainingData.length > 0"
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
                  d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                ></path>
              </svg>
            </div>
            <h5 class="text-lg font-semibold text-gray-800">
              Training Summary
            </h5>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Total Trainings Card -->
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
                      d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
                    ></path>
                  </svg>
                </div>
              </div>
              <div class="text-3xl font-bold text-gray-900 mb-1">
                {{ trainingData.length }}
              </div>
              <div class="text-sm text-gray-600 font-medium">
                Total Trainings
              </div>
            </div>

            <!-- Total Hours Card -->
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
                      d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                    ></path>
                  </svg>
                </div>
              </div>
              <div class="text-3xl font-bold text-gray-900 mb-1">
                {{ formatNumber(totalHours) }}
              </div>
              <div class="text-sm text-gray-600 font-medium">Total Hours</div>
            </div>

            <!-- Average Hours Card -->
            <div
              class="bg-white rounded-lg p-4 shadow-sm border border-gray-100 hover:shadow-md transition-shadow"
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
                      d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                    ></path>
                  </svg>
                </div>
              </div>
              <div class="text-3xl font-bold text-gray-900 mb-1">
                {{ formatNumber(averageHours) }}
              </div>
              <div class="text-sm text-gray-600 font-medium">Average Hours</div>
            </div>

            <!-- Trainings This Year Card -->
            <div
              class="bg-white rounded-lg p-4 shadow-sm border border-gray-100 hover:shadow-md transition-shadow"
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
                      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                    ></path>
                  </svg>
                </div>
              </div>
              <div class="text-3xl font-bold text-gray-900 mb-1">
                {{ trainingsThisYear }}
              </div>
              <div class="text-sm text-gray-600 font-medium">This Year</div>
            </div>
          </div>

          <!-- Additional Stats Row -->
          <div
            v-if="mostRecentTraining || longestTraining"
            class="mt-4 pt-4 border-t border-gray-200"
          >
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div
                v-if="mostRecentTraining"
                class="flex items-center gap-3 text-sm"
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
                      d="M13 10V3L4 14h7v7l9-11h-7z"
                    ></path>
                  </svg>
                </div>
                <div>
                  <div class="text-gray-500 text-xs">Most Recent</div>
                  <div class="text-gray-800 font-medium">
                    {{ mostRecentTraining.seminar }}
                  </div>
                  <div class="text-gray-500 text-xs">
                    {{ formatDate(mostRecentTraining.dateTo) }}
                  </div>
                </div>
              </div>
              <div
                v-if="longestTraining"
                class="flex items-center gap-3 text-sm"
              >
                <div
                  class="w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center flex-shrink-0"
                >
                  <svg
                    class="w-4 h-4 text-indigo-600"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"
                    ></path>
                  </svg>
                </div>
                <div>
                  <div class="text-gray-500 text-xs">Longest Training</div>
                  <div class="text-gray-800 font-medium">
                    {{ longestTraining.seminar }}
                  </div>
                  <div class="text-gray-500 text-xs">
                    {{ formatNumber(longestTraining.hours) }} hours
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Save Button -->
      <div class="flex justify-end">
        <button
          @click="saveTraining"
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
          {{ loading ? "Saving..." : "Save Training Information" }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, computed, ref, onMounted } from "vue";
import { ElNotification, ElMessageBox } from "element-plus";
import { ApiService } from "@/services/api.js";
import { useAppNotification } from "@/composables/useAppNotification.js";
import { GraduationCap } from "lucide-vue-next";

const props = defineProps({
  info: {
    type: Object,
    default: () => ({}),
  },
});

const emit = defineEmits(["saved"]);

const notify = useAppNotification();

const trainingData = reactive([]);
const learningOptions = ref([]); // Store learnings dropdown options
const loading = ref(false);
const error = ref(null);
const employeeId = ref(null);
let nextTrainingId = 1;

const addTraining = () => {
  trainingData.push({
    id: nextTrainingId++,
    training_id: null,
    seminar: "",
    dateFrom: "",
    dateTo: "",
    hours: "",
    learningId: 0, // Changed from trainingType to learningId (integer)
    sponsoredBy: "",
    skillsDevelopment: "",
  });
};

const removeTraining = async (index) => {
  try {
    await ElMessageBox.confirm(
      "Are you sure you want to remove this training record?",
      "Remove Training",
      {
        confirmButtonText: "Remove",
        cancelButtonText: "Cancel",
        type: "warning",
      }
    );
  } catch {
    return;
  }

  const training = trainingData[index];
  const trainingId = training.training_id;

  // Remove from UI immediately for better UX
  trainingData.splice(index, 1);
  ElNotification({
    title: "Success",
    message: "Training record removed successfully!",
    type: "success",
    duration: 2000,
    position: "top-right",
  });

  // If it's an existing training, delete from backend (type_id 6 for trainings)
  if (trainingId) {
    try {
      await ApiService.destroyPDS(6, trainingId);
    } catch (err) {
      const status = err.response?.status;

      if (status && status >= 400 && status < 500) {
        trainingData.splice(index, 0, training);
        ElNotification({
          title: "Error",
          message:
            "Failed to delete training record from server. Please try again.",
          type: "error",
          duration: 5000,
          position: "top-right",
        });
      } else if (status && status >= 500) {
        console.error("Server error during deletion:", err);
      } else {
        console.warn("Unexpected response format during deletion:", err);
      }
    }
  }
};

const calculateHours = (training) => {
  if (training.dateFrom && training.dateTo) {
    const fromDate = new Date(training.dateFrom);
    const toDate = new Date(training.dateTo);

    if (toDate >= fromDate) {
      const diffTime = Math.abs(toDate - fromDate);
      const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1; // Include start date

      // Estimate 8 hours per day for training
      const estimatedHours = diffDays * 8;

      // Only auto-fill if hours is empty
      if (!training.hours) {
        training.hours = estimatedHours;
      }
    }
  }
};

const isFormValid = computed(() => {
  if (trainingData.length === 0) return false;

  return trainingData.every(
    (training) =>
      training.seminar &&
      training.dateFrom &&
      training.dateTo &&
      training.hours &&
      training.sponsoredBy
  );
});

const totalHours = computed(() => {
  const total = trainingData.reduce((sum, training) => {
    // Convert hours to number, default to 0 if invalid
    const hours = parseFloat(training.hours) || 0;
    return sum + hours;
  }, 0);

  // Round to 2 decimal places and format
  return Math.round(total * 100) / 100;
});

const averageHours = computed(() => {
  if (trainingData.length === 0 || totalHours.value === 0) return 0;

  const average = totalHours.value / trainingData.length;
  // Round to 2 decimal places
  return Math.round(average * 100) / 100;
});

const trainingsThisYear = computed(() => {
  const currentYear = new Date().getFullYear();
  return trainingData.filter((training) => {
    if (!training.dateFrom) return false;
    const trainingYear = new Date(training.dateFrom).getFullYear();
    return trainingYear === currentYear;
  }).length;
});

const mostRecentTraining = computed(() => {
  if (trainingData.length === 0) return null;

  const trainingsWithDates = trainingData
    .filter((t) => t.dateTo)
    .map((t) => ({
      ...t,
      dateObj: new Date(t.dateTo),
    }))
    .sort((a, b) => b.dateObj - a.dateObj);

  return trainingsWithDates.length > 0 ? trainingsWithDates[0] : null;
});

const longestTraining = computed(() => {
  if (trainingData.length === 0) return null;

  const trainingsWithHours = trainingData
    .filter((t) => t.hours && parseFloat(t.hours) > 0)
    .map((t) => ({
      ...t,
      hoursNum: parseFloat(t.hours) || 0,
    }))
    .sort((a, b) => b.hoursNum - a.hoursNum);

  return trainingsWithHours.length > 0 ? trainingsWithHours[0] : null;
});

const formatNumber = (num) => {
  if (!num && num !== 0) return "0";
  return num.toLocaleString("en-US", {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  });
};

const formatDate = (dateString) => {
  if (!dateString) return "";
  const date = new Date(dateString);
  if (isNaN(date.getTime())) return dateString;
  return date.toLocaleDateString("en-US", {
    year: "numeric",
    month: "short",
    day: "numeric",
  });
};

const loadTrainingData = async () => {
  try {
    loading.value = true;
    error.value = null;

    const response = await ApiService.getApplicantPage();

    if (response.data && response.data.data) {
      const data = response.data.data;

      // Capture employee id from payload or fallback to prop
      employeeId.value =
        data.employee_info?.[0]?.id ??
        props.info?.employee_info?.[0]?.id ??
        props.info?.employee_id ??
        props.info?.id ??
        null;

      // Populate learning dropdown options if available
      if (Array.isArray(data.learnings)) {
        learningOptions.value = data.learnings.map((l) => ({
          id: l.id ?? l.learning_id ?? 0,
          name: l.name ?? "",
        }));
      }

      if (data.trainings && Array.isArray(data.trainings)) {
        trainingData.splice(0, trainingData.length);

        data.trainings.forEach((training) => {
          trainingData.push({
            id: nextTrainingId++,
            training_id: training.training_id || null,
            seminar: training.training || "",
            dateFrom: training.training_from || "",
            dateTo: training.training_to || "",
            hours: training.hours ? parseFloat(training.hours) : "", // Convert to number when loading
            learningId: training.learning_id ?? 0, // Changed from trainingType to learningId
            sponsoredBy: training.sponsored_by || "",
            skillsDevelopment: training.skills_development || "",
          });
        });
      }
    }
  } catch (err) {
    console.error("Error loading training data:", err);
    error.value = err.response?.data?.message || "Failed to load training data";
  } finally {
    loading.value = false;
  }
};

const saveTraining = async () => {
  if (!isFormValid.value) {
    notify.error(
      "Validation",
      "Please fill in all required fields (Title, From Date, To Date, Hours, and Sponsored By)",
      { duration: 4000 },
    );
    return;
  }

  // Validate date ranges
  const invalidDates = trainingData.filter(
    (training) =>
      training.dateFrom &&
      training.dateTo &&
      new Date(training.dateTo) < new Date(training.dateFrom)
  );

  if (invalidDates.length > 0) {
    notify.error(
      "Validation",
      "To Date must be equal to or after From Date for all training records",
      {
        duration: 4000,
      }
    );
    return;
  }

  // Validate hours
  const invalidHours = trainingData.filter(
    (training) => training.hours && training.hours < 0.5
  );

  if (invalidHours.length > 0) {
    notify.error("Validation", "Training hours must be at least 0.5 hours", {
      duration: 4000,
    });
    return;
  }

  try {
    loading.value = true;

    // Ensure we have the correct employee ID
    const resolvedEmployeeId =
      employeeId.value ??
      props.info?.employee_info?.[0]?.id ??
      props.info?.employee_id ??
      props.info?.id ??
      null;

    if (!resolvedEmployeeId) {
      throw new Error("Employee information not found");
    }

    // Prepare trainings data for API - send each training individually
    const formData = new FormData();
    formData.append("section", "trainings");

    // Send trainings as JSON string in form data (backend will decode)
    const trainingsPayload = trainingData.map((training) => ({
      training_id: training.training_id || null,
      seminar: training.seminar || "",
      dateFrom: training.dateFrom || "",
      dateTo: training.dateTo || "",
      hours: training.hours || 0,
      learning_id: training.learningId || 0, // Changed from trainingType to learning_id (integer)
      sponsoredBy: training.sponsoredBy || "",
      // Use backend's expected snake_case field name
      skills_development: training.skillsDevelopment || "",
    }));

    formData.append("trainings", JSON.stringify(trainingsPayload));

    await ApiService.storePDS(resolvedEmployeeId, formData);

    notify.success("Saved", "Training information saved successfully!", {
      duration: 4000,
    });
    await loadTrainingData();
    emit("saved");
  } catch (err) {
    console.error("Error saving training data:", err);
    const msg =
      err.response?.data?.message ||
      (err.response?.data?.errors
        ? Object.values(err.response.data.errors).flat().join(" ")
        : null) ||
      err.message ||
      "Failed to save training information. Please try again.";
    notify.error("Save failed", msg, { duration: 6000 });
  } finally {
    loading.value = false;
  }
};

onMounted(async () => {
  await loadTrainingData();
  // Only add empty training if no data was loaded and not still loading
  // if (trainingData.length === 0 && !loading.value) {
  //   addTraining();
  // }
});
</script>

<style scoped>
/* Custom styles for better visual hierarchy */
.training-card {
  transition: all 0.2s ease-in-out;
}

.training-card:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
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

/* Select dropdown styling */
select {
  appearance: none;
  -webkit-appearance: none;
  -moz-appearance: none;
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

/* Summary card styling */
.training-summary {
  background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
}
</style>
