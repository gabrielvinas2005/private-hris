<template>
  <div class="bg-white rounded-xl shadow-sm border">
    <!-- Header -->
    <div class="p-6 border-b border-gray-200">
      <div class="flex items-center justify-between">
        <h3 class="font-light">Recent Activities</h3>
        <button
          v-if="showViewAll && activities.length > 0"
          @click="$emit('view-all')"
          class="inline-flex items-center gap-1 text-md text-blue-600 hover:text-blue-800 font-medium"
        >
          View All
          <ArrowRight :size="18" />
        </button>
      </div>
    </div>

    <!-- Activities List -->
    <div class="divide-y divide-gray-200">
      <!-- Loading State -->
      <div v-if="loading" class="p-6">
        <div
          v-for="i in 3"
          :key="i"
          class="flex items-center space-x-4 mb-4 last:mb-0"
        >
          <div class="h-10 w-10 bg-gray-200 rounded-full animate-pulse"></div>
          <div class="flex-1 space-y-2">
            <div class="h-4 bg-gray-200 rounded animate-pulse w-3/4"></div>
            <div class="h-3 bg-gray-200 rounded animate-pulse w-1/2"></div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else-if="activities.length === 0" class="p-12 text-center">
        <div
          class="mx-auto h-12 w-12 text-gray-400 flex items-center justify-center"
        >
          <History :size="48" />
        </div>
        <h3 class="mt-2 font-light">No recent activities</h3>
        <p class="mt-1 font-light">
          Your activities will appear here when you start using the system.
        </p>
      </div>

      <!-- Activity Items -->
      <div v-else>
        <div
          v-for="activity in displayedActivities"
          :key="activity.id"
          class="p-6 hover:bg-gray-100 transition-colors duration-150 cursor-pointer"
          @click="$emit('activity-click', activity)"
        >
          <div class="flex items-start space-x-4">
            <!-- Activity Icon -->
            <div
              :class="[
                activity.iconBg || 'bg-gray-100',
                'flex-shrink-0 flex items-center justify-center h-10 w-10 rounded-md',
              ]"
            >
              <component
                :is="getIconComponent(activity.icon || 'fas fa-bell')"
                :size="16"
                :class="activity.iconColor || 'text-gray-500'"
              />
            </div>

            <!-- Activity Content -->
            <div class="flex-1 min-w-0">
              <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-gray-900 truncate">
                  {{ activity.title }}
                </p>
                <div class="flex items-center ml-2 flex-shrink-0">
                  <!-- Status Badge -->
                  <span
                    v-if="activity.status"
                    :class="getStatusClass(activity.status)"
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                  >
                    {{ activity.status }}
                  </span>
                  <!-- Date -->
                  <span class="text-xs text-gray-500 ml-2">
                    {{ formatDate(activity.date) }}
                  </span>
                </div>
              </div>

              <p class="mt-1 text-sm text-gray-600 line-clamp-2">
                {{ activity.description }}
              </p>

              <!-- Additional metadata -->
              <div
                v-if="activity.metadata"
                class="mt-2 flex items-center space-x-4 text-xs text-gray-500"
              >
                <span
                  v-if="activity.metadata.type"
                  class="inline-flex items-center"
                >
                  <Tag :size="14" class="mr-1" />
                  {{ activity.metadata.type }}
                </span>
                <span
                  v-if="activity.metadata.location"
                  class="inline-flex items-center"
                >
                  <MapPin :size="14" class="mr-1" />
                  {{ activity.metadata.location }}
                </span>
                <span
                  v-if="activity.metadata.department"
                  class="inline-flex items-center"
                >
                  <Building :size="14" class="mr-1" />
                  {{ activity.metadata.department }}
                </span>
              </div>
            </div>

            <!-- Action indicator -->
            <div class="flex-shrink-0 flex items-center">
              <ArrowRight :size="14" class="text-gray-400" />
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from "vue";
import {
  History,
  Bell,
  Tag,
  MapPin,
  Building,
  ArrowRight,
  Clock,
  CheckCircle,
  XCircle,
  Star,
  Calendar,
  Search,
  User,
  Briefcase,
  FileText,
  Video,
} from "lucide-vue-next";

// Icon mapping for dynamic icons
const getIconComponent = (iconName) => {
  const iconMap = {
    "fas fa-bell": Bell,
    "fas fa-history": History,
    "fas fa-clock": Clock,
    "fas fa-check": CheckCircle,
    "fas fa-check-circle": CheckCircle,
    "fas fa-times": XCircle,
    "fas fa-star": Star,
    "fas fa-search": Search,
    "fas fa-user": User,
    "fas fa-briefcase": Briefcase,
    "fas fa-calendar": Calendar,
    "fas fa-calendar-check": Calendar, // Exam scheduled - Calendar icon
    "fas fa-file-alt": FileText, // Application submitted - File icon
    "fas fa-user-tie": Video, // Interview - Camera/Video icon
    default: Bell,
  };
  return iconMap[iconName] || iconMap.default;
};

// Props
const props = defineProps({
  activities: {
    type: Array,
    required: true,
    default: () => [],
  },
  loading: {
    type: Boolean,
    default: false,
  },
  maxItems: {
    type: Number,
    default: 10,
  },
  showViewAll: {
    type: Boolean,
    default: true,
  },
});

// Emits
const emit = defineEmits(["activity-click", "view-all"]);

// Computed properties
const displayedActivities = computed(() => {
  return props.activities.slice(0, props.maxItems);
});

// Utility functions
const getTimeAgo = (date) => {
  if (!date) return "";

  const now = new Date();
  const activityDate = new Date(date);
  const diffTime = Math.abs(now - activityDate);
  const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));
  const diffHours = Math.floor(diffTime / (1000 * 60 * 60));
  const diffMinutes = Math.floor(diffTime / (1000 * 60));

  if (diffDays > 7) {
    return activityDate.toLocaleDateString();
  } else if (diffDays > 0) {
    return `${diffDays} day${diffDays > 1 ? "s" : ""} ago`;
  } else if (diffHours > 0) {
    return `${diffHours} hour${diffHours > 1 ? "s" : ""} ago`;
  } else if (diffMinutes > 0) {
    return `${diffMinutes} minute${diffMinutes > 1 ? "s" : ""} ago`;
  } else {
    return "Just now";
  }
};

const getStatusClass = (status) => {
  const normalized = String(status || "")
    .toLowerCase()
    .trim()
    .replace(/\s+/g, "-");

  const statusClasses = {
    // Generic buckets used by this component
    completed: "bg-green-100 text-green-800",
    pending: "bg-yellow-100 text-yellow-800",
    "in-progress": "bg-blue-100 text-blue-800",
    failed: "bg-red-100 text-red-800",
    cancelled: "bg-gray-100 text-gray-800",

    // Application stage labels we may receive
    shortlisted: "bg-green-100 text-green-800",
    rejected: "bg-red-100 text-red-800",
    hired: "bg-green-100 text-green-800",
    accepted: "bg-green-100 text-green-800",
    "under-review": "bg-blue-100 text-blue-800",
    "for-hiring": "bg-blue-100 text-blue-800",
    withdrawn: "bg-gray-100 text-gray-800",
    expired: "bg-gray-100 text-gray-800",
    "not-qualified": "bg-red-100 text-red-800",
    "will-not-proceed": "bg-red-100 text-red-800",
  };

  return statusClasses[normalized] || "bg-gray-100 text-gray-800";
};

const formatDate = (date) => {
  if (!date) return "";
  const activityDate = new Date(date);
  return activityDate.toLocaleDateString("en-US", {
    month: "short",
    day: "numeric",
    year: "numeric",
  });
};
</script>

<style scoped>
/* Line clamp utility for truncating text */
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  line-clamp: 2;
  overflow: hidden;
}

/* Custom scrollbar if needed */
.overflow-y-auto::-webkit-scrollbar {
  width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
  background: #f1f1f1;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
  background: #a1a1a1;
}
</style>
