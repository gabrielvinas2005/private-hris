<template>
  <div class="overflow-hidden bg-white border shadow-sm rounded-xl">
    <!-- Header -->
    <div
      class="px-6 py-4 border-b border-gray-200 rounded-t-lg bg-gradient-to-r from-indigo-50 to-purple-50"
    >
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h3
            class="inline-flex items-center text-lg font-semibold text-gray-900"
          >
            <ClipboardCheck :size="20" class="mr-2 text-indigo-600" /> Scheduled
            Examinations
          </h3>
          <p class="mt-1 text-sm text-gray-500">
            View and manage your scheduled examinations
          </p>
        </div>
        <div class="flex mt-4 space-x-3 sm:mt-0">
          <button
            @click="refreshData"
            class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 transition-colors border border-gray-300 rounded-lg hover:bg-gray-50"
          >
            <RefreshCw
              :size="16"
              class="mr-2"
              :class="{ 'animate-spin': loading }"
            />
            Refresh
          </button>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="p-6">
      <div class="space-y-4 animate-pulse">
        <div v-for="i in 3" :key="i" class="flex space-x-4">
          <div class="w-1/4 h-4 bg-gray-200 rounded"></div>
          <div class="w-1/4 h-4 bg-gray-200 rounded"></div>
          <div class="w-1/4 h-4 bg-gray-200 rounded"></div>
          <div class="w-1/4 h-4 bg-gray-200 rounded"></div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else-if="examinations.length === 0" class="p-12 text-center">
      <div
        class="flex items-center justify-center w-16 h-16 mx-auto mb-4 text-gray-400"
      >
        <ClipboardCheck :size="64" />
      </div>
      <h3 class="mb-2 text-lg font-medium text-gray-900">
        No examinations scheduled
      </h3>
      <p class="text-sm text-gray-500">
        You don't have any scheduled examinations at this time.
      </p>
    </div>

    <!-- Examinations Table -->
    <div v-else class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th
              scope="col"
              class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase"
            >
              Exam Set
            </th>
            <th
              scope="col"
              class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase"
            >
              Schedule
            </th>
            <th
              scope="col"
              class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase"
            >
              Duration
            </th>
            <th
              scope="col"
              class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase"
            >
              Status
            </th>
            <th
              scope="col"
              class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase"
            >
              Actions
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr
            v-for="exam in examinations"
            :key="exam.id"
            class="transition-colors duration-150 hover:bg-gray-50"
          >
            <!-- Exam Set -->
            <td class="px-6 py-4 whitespace-nowrap">
              <div>
                <div class="text-sm font-medium text-gray-900">
                  {{ exam.exam_set || "N/A" }}
                </div>
                <div class="text-sm text-gray-500">
                  Passing: {{ exam.passing_criteria || "N/A" }}
                </div>
              </div>
            </td>

            <!-- Schedule -->
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm text-gray-900">
                <div class="flex items-center mb-1">
                  <i class="mr-2 text-gray-400 fas fa-calendar-alt"></i>
                  <span>{{
                    formatDateRange(exam.exam_date_from, exam.exam_date_to)
                  }}</span>
                </div>
                <div class="flex items-center text-gray-500">
                  <i class="mr-2 text-gray-400 fas fa-clock"></i>
                  <span>{{
                    formatTimeRange(exam.exam_time_from, exam.exam_time_to)
                  }}</span>
                </div>
              </div>
            </td>

            <!-- Duration -->
            <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">
              {{ exam.exam_duration || "N/A" }} minutes
            </td>

            <!-- Status -->
            <td class="px-6 py-4 whitespace-nowrap">
              <span
                :class="getStatusClass(exam.status)"
                class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
              >
                <i :class="getStatusIcon(exam.status)" class="mr-1"></i>
                {{ formatStatus(exam.status) }}
              </span>
            </td>

            <!-- Actions -->
            <td
              class="px-6 py-4 text-sm font-medium whitespace-nowrap"
              @click.stop
            >
              <div class="flex items-center space-x-2">
                <button
                  v-if="exam.status === 'Active' || exam.status === 'Pending'"
                  @click="
                    isPsychExam(exam)
                      ? $emit('submit-exam', exam)
                      : $emit('take-exam', exam)
                  "
                  class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded-lg hover:bg-blue-700 transition-colors"
                  :title="isPsychExam(exam) ? 'Submit' : 'Take Exam'"
                >
                  <i
                    :class="isPsychExam(exam) ? 'fas fa-paper-plane mr-1' : 'fas fa-play mr-1'"
                  ></i>
                  {{ isPsychExam(exam) ? "Submit" : "Take Exam" }}
                </button>
                <button
                  v-if="exam.status === 'Completed' && !isPsychExam(exam)"
                  @click="$emit('view-results', exam)"
                  class="inline-flex items-center px-3 py-1.5 bg-green-600 text-white text-xs font-medium rounded-lg hover:bg-green-700 transition-colors"
                  title="View Results"
                >
                  <i class="mr-1 fas fa-eye"></i>
                  View Results
                </button>
                <button
                  @click="$emit('view-details', exam)"
                  class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-gray-700 text-xs font-medium rounded-lg hover:bg-gray-50 transition-colors"
                  title="View Details"
                >
                  <i class="mr-1 fas fa-info-circle"></i>
                  Details
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { defineProps, defineEmits } from "vue";
import { ClipboardCheck, RefreshCw } from "lucide-vue-next";

const props = defineProps({
  examinations: {
    type: Array,
    default: () => [],
  },
  loading: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits([
  "take-exam",
  "submit-exam",
  "view-results",
  "view-details",
  "refresh",
]);

const refreshData = () => {
  emit("refresh");
};

const formatDateRange = (dateFrom, dateTo) => {
  if (!dateFrom) return "N/A";
  const from = new Date(dateFrom);
  const to = dateTo ? new Date(dateTo) : from;

  const fromStr = from.toLocaleDateString("en-US", {
    month: "short",
    day: "numeric",
    year: "numeric",
  });

  if (dateTo && from.getTime() !== to.getTime()) {
    const toStr = to.toLocaleDateString("en-US", {
      month: "short",
      day: "numeric",
      year: "numeric",
    });
    return `${fromStr} - ${toStr}`;
  }

  return fromStr;
};

const formatTimeRange = (timeFrom, timeTo) => {
  if (!timeFrom) return "N/A";

  const formatTime = (timeStr) => {
    if (!timeStr) return "";
    // Handle both "HH:MM:SS" and "HH:MM" formats
    const parts = timeStr.split(":");
    const hours = parseInt(parts[0]);
    const minutes = parts[1];
    const ampm = hours >= 12 ? "PM" : "AM";
    const displayHours = hours % 12 || 12;
    return `${displayHours}:${minutes} ${ampm}`;
  };

  const from = formatTime(timeFrom);
  const to = timeTo ? formatTime(timeTo) : "";

  return to ? `${from} - ${to}` : from;
};

const formatStatus = (status) => {
  const statusMap = {
    Active: "Active",
    Pending: "Pending",
    Completed: "Completed",
    Expired: "Expired",
  };
  return statusMap[status] || status || "Unknown";
};

const isPsychExam = (exam) => {
  const haystack =
    `${exam?.exam_set || ""} ${exam?.exam_instruction || ""}`.toLowerCase();
  return /psych|psychometric/.test(haystack);
};

const getStatusClass = (status) => {
  const statusClasses = {
    Active: "bg-blue-100 text-blue-800",
    Pending: "bg-yellow-100 text-yellow-800",
    Completed: "bg-green-100 text-green-800",
    Expired: "bg-gray-100 text-gray-800",
  };
  return statusClasses[status] || "bg-gray-100 text-gray-800";
};

const getStatusIcon = (status) => {
  const statusIcons = {
    Active: "fas fa-play-circle",
    Pending: "fas fa-clock",
    Completed: "fas fa-check-circle",
    Expired: "fas fa-times-circle",
  };
  return statusIcons[status] || "fas fa-question-circle";
};
</script>
