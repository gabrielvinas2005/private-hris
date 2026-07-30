<template>
  <Teleport to="body">
    <div
      v-if="interview && interview.id"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-[9999] p-4"
      @click.self="close"
    >
      <div
        class="bg-white rounded-xl shadow-2xl max-w-2xl w-full overflow-hidden"
      >
      <!-- Header -->
      <header class="px-6 py-4 bg-gradient-to-br from-indigo-600 to-indigo-800 text-white">
        <div class="flex items-start justify-between">
          <div class="flex-1">
            <h2 class="text-xl font-semibold mb-1">Interview Details</h2>
          </div>
          <button
            class="text-white/80 hover:text-white transition-colors"
            @click="close"
          >
            <i class="fas fa-times text-xl"></i>
          </button>
        </div>
      </header>

      <!-- Content -->
      <section class="p-6 space-y-4">
        <!-- Panel Group & Level -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Panel Group</p>
            <p class="text-sm font-medium text-gray-900">{{ interview.panel_group || "N/A" }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Level</p>
            <p class="text-sm font-medium text-gray-900">{{ interview.level || "N/A" }}</p>
          </div>
        </div>

        <!-- Location -->
        <div>
          <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Interview Location</p>
          <p class="text-sm font-medium text-gray-900">{{ interview.interview_location || "N/A" }}</p>
        </div>

        <!-- Date & Time -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Date</p>
            <p class="text-sm font-medium text-gray-900">{{ formatDateRange() }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Time</p>
            <p class="text-sm font-medium text-gray-900">{{ formatTimeRange() }}</p>
          </div>
        </div>

        <!-- Status -->
        <div>
          <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Status</p>
          <span
            :class="getStatusClass(interview.status)"
            class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
          >
            <i :class="getStatusIcon(interview.status)" class="mr-1"></i>
            {{ formatStatus(interview.status) }}
          </span>
        </div>

        <!-- Description -->
        <div v-if="interview.description" class="pt-2 border-t">
          <div class="flex items-start gap-2 mb-2">
            <i class="fas fa-info-circle text-indigo-600 mt-0.5"></i>
            <p class="text-xs text-gray-500 uppercase tracking-wide">Description</p>
          </div>
          <p class="text-sm text-gray-700 leading-relaxed whitespace-pre-wrap">{{ interview.description }}</p>
        </div>

        <!-- Action Buttons -->
        <div class="flex gap-3 pt-4 border-t">
          <button
            v-if="hasMeetingLink && interview.status === 'Active'"
            @click="handleJoinMeeting"
            class="flex-1 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors inline-flex items-center justify-center"
          >
            <i class="fas fa-video mr-2"></i>
            Join Meeting
          </button>
          <button
            class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors"
            @click="close"
          >
            Close
          </button>
        </div>
      </section>
    </div>
    </div>
  </Teleport>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  interview: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(['close', 'join-meeting']);

const close = () => {
  emit('close');
};

const handleJoinMeeting = () => {
  emit('join-meeting', props.interview);
};

// Extract URL from description
const extractUrlFromText = (text) => {
  if (!text) return null;
  const urlRegex = /(https?:\/\/[^\s]+|(?:zoom\.us|teams\.microsoft\.com|meet\.google\.com|webex\.com)[^\s]*)/gi;
  const matches = text.match(urlRegex);
  if (matches && matches.length > 0) {
    let url = matches[0];
    if (!url.startsWith('http://') && !url.startsWith('https://')) {
      url = 'https://' + url;
    }
    return url;
  }
  return null;
};

const hasMeetingLink = computed(() => {
  return extractUrlFromText(props.interview?.description) !== null;
});

const formatDateRange = () => {
  if (!props.interview?.start_date) return 'N/A';
  const start = new Date(props.interview.start_date);
  const end = props.interview.end_date ? new Date(props.interview.end_date) : start;
  
  const startStr = start.toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  });
  
  if (props.interview.end_date && start.getTime() !== end.getTime()) {
    const endStr = end.toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric',
      year: 'numeric',
    });
    return `${startStr} - ${endStr}`;
  }
  
  return startStr;
};

const formatTimeRange = () => {
  if (!props.interview?.start_time) return 'N/A';
  
  const formatTime = (timeStr) => {
    if (!timeStr) return '';
    const parts = timeStr.split(':');
    const hours = parseInt(parts[0]);
    const minutes = parts[1];
    const ampm = hours >= 12 ? 'PM' : 'AM';
    const displayHours = hours % 12 || 12;
    return `${displayHours}:${minutes} ${ampm}`;
  };
  
  const start = formatTime(props.interview.start_time);
  const end = props.interview.end_time ? formatTime(props.interview.end_time) : '';
  
  return end && props.interview.start_time !== props.interview.end_time 
    ? `${start} - ${end}` 
    : start;
};

const formatStatus = (status) => {
  const statusMap = {
    Active: 'Active',
    Pending: 'Pending',
    Completed: 'Completed',
    Expired: 'Expired',
    Cancelled: 'Cancelled',
  };
  return statusMap[status] || status || 'Unknown';
};

const getStatusClass = (status) => {
  const statusClasses = {
    Active: 'bg-blue-100 text-blue-800',
    Pending: 'bg-yellow-100 text-yellow-800',
    Completed: 'bg-green-100 text-green-800',
    Expired: 'bg-gray-100 text-gray-800',
    Cancelled: 'bg-red-100 text-red-800',
  };
  return statusClasses[status] || 'bg-gray-100 text-gray-800';
};

const getStatusIcon = (status) => {
  const statusIcons = {
    Active: 'fas fa-play-circle',
    Pending: 'fas fa-clock',
    Completed: 'fas fa-check-circle',
    Expired: 'fas fa-times-circle',
    Cancelled: 'fas fa-ban',
  };
  return statusIcons[status] || 'fas fa-question-circle';
};
</script>
