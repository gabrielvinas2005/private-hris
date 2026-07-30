<template>
  <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
    <!-- Header -->
    <div
      class="bg-gradient-to-r from-indigo-50 to-purple-50 px-6 py-4 border-b border-gray-200 rounded-t-lg"
    >
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h3
            class="text-lg font-semibold text-gray-900 inline-flex items-center"
          >
            <Video :size="20" class="mr-2 text-indigo-600" /> Scheduled
            Interviews
          </h3>
          <p class="mt-1 text-sm text-gray-500">
            View and manage your scheduled interviews
          </p>
        </div>
        <div class="mt-4 sm:mt-0 flex space-x-3">
          <button
            @click="refreshData"
            class="inline-flex items-center px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors"
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
      <div class="animate-pulse space-y-4">
        <div v-for="i in 3" :key="i" class="flex space-x-4">
          <div class="h-4 bg-gray-200 rounded w-1/4"></div>
          <div class="h-4 bg-gray-200 rounded w-1/4"></div>
          <div class="h-4 bg-gray-200 rounded w-1/4"></div>
          <div class="h-4 bg-gray-200 rounded w-1/4"></div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else-if="interviews.length === 0" class="p-12 text-center">
      <div
        class="mx-auto h-16 w-16 text-gray-400 mb-4 flex items-center justify-center"
      >
        <Video :size="64" />
      </div>
      <h3 class="text-lg font-medium text-gray-900 mb-2">
        No interviews scheduled
      </h3>
      <p class="text-sm text-gray-500">
        You don't have any scheduled interviews at this time.
      </p>
    </div>

    <!-- Interviews Table -->
    <div v-else class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th
              scope="col"
              class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
            >
              Panel Group / Level
            </th>
            <th
              scope="col"
              class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
            >
              Schedule
            </th>
            <th
              scope="col"
              class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
            >
              Location
            </th>
            <th
              scope="col"
              class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
            >
              Status
            </th>
            <th
              scope="col"
              class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
            >
              Actions
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr
            v-for="interview in interviews"
            :key="interview.id"
            class="hover:bg-gray-50 transition-colors duration-150"
          >
            <!-- Panel Group / Level -->
            <td class="px-6 py-4 whitespace-nowrap">
              <div>
                <div class="text-sm font-medium text-gray-900">
                  {{ interview.panel_group || "N/A" }}
                </div>
                <div class="text-xs text-gray-500">
                  Level: {{ interview.level || "N/A" }}
                </div>
              </div>
            </td>

            <!-- Schedule -->
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm text-gray-900">
                <div class="flex items-center mb-1">
                  <i class="fas fa-calendar-alt text-gray-400 mr-2"></i>
                  <span>{{
                    formatDateRange(interview.start_date, interview.end_date)
                  }}</span>
                </div>
                <div class="flex items-center text-gray-500">
                  <i class="fas fa-clock text-gray-400 mr-2"></i>
                  <span>{{
                    formatTimeRange(interview.start_time, interview.end_time)
                  }}</span>
                </div>
              </div>
            </td>

            <!-- Location -->
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm text-gray-900">
                <div class="flex items-center">
                  <i class="fas fa-map-marker-alt text-gray-400 mr-2"></i>
                  <span>{{ interview.interview_location || "N/A" }}</span>
                </div>
              </div>
            </td>

            <!-- Status -->
            <td class="px-6 py-4 whitespace-nowrap">
              <span
                :class="getStatusClass(interview.status)"
                class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
              >
                <i :class="getStatusIcon(interview.status)" class="mr-1"></i>
                {{ formatStatus(interview.status) }}
              </span>
            </td>

            <!-- Actions -->
            <td
              class="px-6 py-4 whitespace-nowrap text-sm font-medium"
            >
              <div class="flex items-center space-x-2">
                <button
                  v-if="interview.status === 'Active'"
                  @click.stop="$emit('join-interview', interview)"
                  class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded-lg hover:bg-blue-700 transition-colors"
                  title="Join Interview"
                >
                  <i class="fas fa-video mr-1"></i>
                  Join Interview
                </button>
                <button
                  @click.stop="handleDetailsClick(interview)"
                  class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-gray-700 text-xs font-medium rounded-lg hover:bg-gray-50 transition-colors cursor-pointer"
                  title="View Details"
                >
                  <i class="fas fa-info-circle mr-1"></i>
                  Details
                </button>
                <button
                  v-if="
                    interview.status === 'Pending' ||
                    interview.status === 'Active'
                  "
                  @click.stop="$emit('cancel-interview', interview)"
                  class="inline-flex items-center px-3 py-1.5 border border-red-300 text-red-700 text-xs font-medium rounded-lg hover:bg-red-50 transition-colors"
                  title="Cancel Interview"
                >
                  <i class="fas fa-times-circle mr-1"></i>
                  Cancel
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
import { Video, RefreshCw } from "lucide-vue-next";

const props = defineProps({
  interviews: {
    type: Array,
    default: () => [],
  },
  loading: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits([
  "join-interview",
  "view-details",
  "cancel-interview",
  "refresh",
]);

const refreshData = () => {
  emit("refresh");
};

const handleDetailsClick = (interview) => {
  emit("view-details", interview);
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
    Cancelled: "Cancelled",
  };
  return statusMap[status] || status || "Unknown";
};

const getStatusClass = (status) => {
  const statusClasses = {
    Active: "bg-blue-100 text-blue-800",
    Pending: "bg-yellow-100 text-yellow-800",
    Completed: "bg-green-100 text-green-800",
    Expired: "bg-gray-100 text-gray-800",
    Cancelled: "bg-red-100 text-red-800",
  };
  return statusClasses[status] || "bg-gray-100 text-gray-800";
};

const getStatusIcon = (status) => {
  const statusIcons = {
    Active: "fas fa-play-circle",
    Pending: "fas fa-clock",
    Completed: "fas fa-check-circle",
    Expired: "fas fa-times-circle",
    Cancelled: "fas fa-ban",
  };
  return statusIcons[status] || "fas fa-question-circle";
};
</script>
