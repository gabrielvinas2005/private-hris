<template>
  <div class="mb-8">
    <!-- Mobile dropdown for small screens -->
    <div class="sm:hidden">
      <select
        :value="activeTab"
        @change="$emit('tab-change', $event.target.value)"
        class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 rounded-md"
      >
        <option v-for="tab in tabs" :key="tab.id" :value="tab.id">
          {{ tab.name }}
        </option>
      </select>
    </div>

    <!-- Desktop tab navigation with left-aligned multi-row -->
    <div class="hidden sm:block" id="tab-navigation">
      <nav class="border-b border-gray-200" aria-label="Tabs">
        <!-- Multi-row flex container - LEFT ALIGNED -->
        <div
          class="flex flex-wrap items-start justify-start gap-x-4 gap-y-2 pb-2"
        >
          <button
            v-for="tab in tabs"
            :key="tab.id"
            @click="$emit('tab-change', tab.id)"
            :class="[
              'group inline-flex items-center py-2 px-3 border-b-2 font-medium text-sm transition-colors duration-200 whitespace-nowrap',
              activeTab === tab.id
                ? 'border-blue-500 text-blue-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
            ]"
            :aria-current="activeTab === tab.id ? 'page' : undefined"
          >
            <component
              :is="getIconComponent(tab.icon)"
              :size="16"
              :class="[
                'mr-2 transition-colors duration-200',
                activeTab === tab.id
                  ? 'text-blue-500'
                  : 'text-gray-400 group-hover:text-gray-500',
              ]"
            />
            <span class="text-xs sm:text-sm">{{ tab.name }}</span>

            <!-- Optional badge for notification count (red circle, like header) -->
            <span
              v-if="tab.badge && tab.badge > 0"
              class="ml-1 inline-flex items-center justify-center h-4 w-4 bg-red-500 text-white text-[10px] rounded-full"
            >
              {{ tab.badge }}
            </span>
          </button>
        </div>
      </nav>
    </div>

    <!-- Alternative: Pills with left-aligned wrapping -->
    <div v-if="variant === 'pills'" class="flex flex-wrap justify-start gap-2">
      <button
        v-for="tab in tabs"
        :key="tab.id"
        @click="$emit('tab-change', tab.id)"
        :class="[
          'inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium transition-all duration-200 whitespace-nowrap',
          activeTab === tab.id
            ? 'bg-blue-600 text-white shadow-sm'
            : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100 bg-white border border-gray-200',
        ]"
      >
        <component :is="getIconComponent(tab.icon)" :size="14" class="mr-1" />
        {{ tab.name }}
        <span
          v-if="tab.badge && tab.badge > 0"
          :class="[
            'ml-1 inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium',
            activeTab === tab.id
              ? 'bg-blue-500 text-white'
              : 'bg-gray-200 text-gray-800',
          ]"
        >
          {{ tab.badge }}
        </span>
      </button>
    </div>

    <!-- Compact grid version -->
    <div
      v-if="variant === 'compact'"
      class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 lg:grid-cols-6 gap-2 justify-items-start"
    >
      <button
        v-for="tab in tabs"
        :key="tab.id"
        @click="$emit('tab-change', tab.id)"
        :class="[
          'flex flex-col items-center p-2 rounded-lg text-xs font-medium transition-all duration-200 border w-full',
          activeTab === tab.id
            ? 'bg-blue-50 text-blue-700 border-blue-200'
            : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50 border-gray-200',
        ]"
      >
        <div class="relative">
          <component :is="getIconComponent(tab.icon)" :size="20" class="mb-1" />
          <span
            v-if="tab.badge && tab.badge > 0"
            class="absolute -top-1 -right-1 h-4 w-4 bg-red-500 text-white text-xs rounded-full flex items-center justify-center"
          >
            {{ tab.badge }}
          </span>
        </div>
        <span class="text-center leading-tight">{{ tab.name }}</span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from "vue";
import {
  Home,
  FileText,
  User,
  Users,
  GraduationCap,
  Briefcase,
  Heart,
  Award,
  Settings,
  ClipboardCheck,
  Video,
} from "lucide-vue-next";

// Props
const props = defineProps({
  tabs: {
    type: Array,
    required: true,
    default: () => [],
  },
  activeTab: {
    type: String,
    required: true,
  },
  variant: {
    type: String,
    default: "underline", // 'underline', 'pills', 'compact'
    validator: (value) => ["underline", "pills", "compact"].includes(value),
  },
});

// Emits
const emit = defineEmits(["tab-change"]);

// Icon mapping for FontAwesome to Lucide
const getIconComponent = (iconName) => {
  const iconMap = {
    "fas fa-home": Home,
    "fas fa-file-alt": FileText,
    "fas fa-user": User,
    "fas fa-users": Users,
    "fas fa-user-friends": Users,
    "fas fa-graduation-cap": GraduationCap,
    "fas fa-briefcase": Briefcase,
    "fas fa-hands-helping": Heart, // Using Heart as closest match
    "fas fa-award": Award,
    "fas fa-cogs": Settings,
    "fas fa-cog": Settings,
    "fas fa-clipboard-check": ClipboardCheck,
    "fas fa-video": Video,
    default: FileText,
  };
  return iconMap[iconName] || iconMap.default;
};

// Computed properties
const currentTab = computed(() =>
  props.tabs.find((tab) => tab.id === props.activeTab)
);
</script>

<style scoped>
/* Custom scrollbar for mobile */
.overflow-x-auto::-webkit-scrollbar {
  height: 4px;
}

.overflow-x-auto::-webkit-scrollbar-track {
  background: #f1f1f1;
}

.overflow-x-auto::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 2px;
}

.overflow-x-auto::-webkit-scrollbar-thumb:hover {
  background: #a1a1a1;
}

/* Focus styles for accessibility */
button:focus {
  outline: none !important;
  box-shadow: none !important;
}

#tab-navigation {
  margin-left: 15%;
  margin-right: 15%;
}

/* Left-aligned flex wrapping */
.flex-wrap {
  flex-wrap: wrap;
  align-content: flex-start; /* Ensures rows start at the top-left */
}

/* Remove centering, ensure left alignment */
.justify-start {
  justify-content: flex-start !important;
}

.items-start {
  align-items: flex-start !important;
}

/* Ensure consistent spacing */
.gap-x-4 {
  column-gap: 1rem;
}

.gap-y-2 {
  row-gap: 0.5rem;
}

/* Animation for tab transitions */
button {
  transform: translateY(0);
  transition: all 0.2s ease;
}

/* REPLACE THIS SECTION ↓ */
button:hover {
  transform: none !important; /* ← CHANGE THIS */
  box-shadow: none !important; /* ← ADD THIS */
  outline: none !important; /* ← ADD THIS */
  background-color: transparent !important; /* ← ADD THIS */
}

button:active {
  transform: translateY(0);
}

/* Custom alignment for multi-row tabs */
.flex-wrap > button {
  margin-bottom: 0.5rem;
}
</style>
