<template>
  <header class="bg-gray-70 shadow-sm border-b">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between items-center h-16">
        <!-- Left side - Logo and title -->
        <div class="flex items-center">
          <div v-if="logo" class="h-10 w-10 mr-3">
            <img
              :src="logo"
              :alt="companyName"
              class="h-10 w-10 rounded-full object-cover bg-white"
            />
          </div>
          <div>
            <h1 class="font-light text-lg">Applicant Portal</h1>
            <p class="text-sm text-gray-500">
              Welcome back, {{ user.name || "Applicant" }}
            </p>
          </div>
        </div>

        <!-- Right side - User info and actions -->
        <div class="flex items-center space-x-4">
          <!-- User profile info -->
          <div class="text-right hidden sm:block">
            <p class="text-sm font-medium text-gray-900">{{ user.name }}</p>
            <p class="text-xs text-gray-500">
              {{ user.applicantNo || user.email }}
            </p>
          </div>

          <!-- Notifications (optional) -->
          <div class="relative">
            <button
              class="relative p-2 text-gray-400 hover:text-gray-500"
              @click="toggleNotifications"
            >
              <Bell :size="20" />
              <span
                v-if="notificationCount > 0"
                class="absolute top-1 -right-1 h-4 w-4 bg-red-500 text-white text-xs rounded-full flex items-center justify-center"
              >
                {{ notificationCount }}
              </span>
            </button>
            <div
              v-if="showNotifications"
              class="absolute right-0 mt-2 w-72 bg-white rounded-lg shadow-lg border border-gray-100 z-50"
            >
              <div class="px-4 py-3 border-b">
                <div class="flex items-center justify-between">
                  <p class="text-sm font-semibold text-gray-800">
                    Notifications
                  </p>
                  <span class="text-xs text-gray-500">
                    {{ notifications.length }} new
                  </span>
                </div>
              </div>
              <div class="max-h-64 overflow-y-auto divide-y">
                <div
                  v-if="notifications.length === 0"
                  class="px-4 py-3 text-sm text-gray-500"
                >
                  You're all caught up.
                </div>
                <button
                  v-for="(item, idx) in notifications"
                  :key="idx"
                  class="w-full text-left px-4 py-3 hover:bg-gray-50 transition-colors"
                  @click.stop="handleNotificationClick(item)"
                >
                  <p class="text-sm font-medium text-gray-800">
                    {{ item.title }}
                  </p>
                  <p class="text-xs text-gray-500">{{ item.subtitle }}</p>
                </button>
              </div>
            </div>
          </div>

          <!-- Dropdown menu for mobile -->
          <div class="relative">
            <button
              @click="showDropdown = !showDropdown"
              class="flex items-center text-gray-700 hover:text-gray-900 sm:hidden"
            >
              <Menu :size="20" />
            </button>

            <!-- Dropdown menu -->
            <div
              v-if="showDropdown"
              class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50"
            >
              <div class="px-4 py-2 text-sm text-gray-700 border-b">
                <div class="font-medium">{{ user.name }}</div>
                <div class="text-gray-500">{{ user.email }}</div>
              </div>
              <a
                href="#"
                class="inline-flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
              >
                <User :size="16" class="mr-2" />Profile
              </a>
              <a
                href="#"
                class="inline-flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
              >
                <Settings :size="16" class="mr-2" />Settings
              </a>
              <button
                @click="handleLogout"
                class="inline-flex items-center w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
              >
                <LogOut :size="16" class="mr-2" />Logout
              </button>
            </div>
          </div>

          <!-- Desktop logout button -->
          <button
            @click="handleLogout"
            class="hidden sm:inline-flex items-center bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors"
          >
            <LogOut :size="16" class="mr-2" />Logout
          </button>
        </div>
      </div>
    </div>
  </header>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch } from "vue";
import { Bell, Menu, User, Settings, LogOut } from "lucide-vue-next";
import { useCompanyBranding } from "@/composables/useCompanyBranding.js";

const { companyName, companyLogo, loadCompany } = useCompanyBranding();
const logo = companyLogo;

// Props
const props = defineProps({
  user: {
    type: Object,
    required: true,
    default: () => ({
      name: "Guest User",
      email: "",
      applicantNo: "",
    }),
  },
  notifications: {
    type: Array,
    required: false,
    default: () => [],
  },
  notificationBadge: {
    type: Number,
    required: false,
    default: 0,
  },
});

// Emits
const emit = defineEmits([
  "logout",
  "profile-click",
  "settings-click",
  "notification-click",
]);

// Reactive data
const showDropdown = ref(false);
const showNotifications = ref(false);
const notificationCount = ref(0);

// Methods
const handleLogout = () => {
  showDropdown.value = false;
  emit("logout");
};

const handleProfileClick = () => {
  showDropdown.value = false;
  emit("profile-click");
};

const handleSettingsClick = () => {
  showDropdown.value = false;
  emit("settings-click");
};

const toggleNotifications = () => {
  showNotifications.value = !showNotifications.value;
};

const handleNotificationClick = (item) => {
  showNotifications.value = false;
  emit("notification-click", item);
};

// Close dropdown when clicking outside
const closeDropdown = (event) => {
  if (!event.target.closest(".relative")) {
    showDropdown.value = false;
    showNotifications.value = false;
  }
};

onMounted(() => {
  loadCompany();
  document.addEventListener("click", closeDropdown);
  notificationCount.value =
    props.notificationBadge || props.notifications.length;
});

watch(
  () => props.notificationBadge,
  (newVal) => {
    if (typeof newVal === "number") {
      notificationCount.value = newVal;
    }
  },
);

watch(
  () => props.notifications,
  (newVal) => {
    // Fallback for older usage without notificationBadge
    if (!props.notificationBadge && Array.isArray(newVal)) {
      notificationCount.value = newVal.length;
    }
  },
  { deep: true },
);

onUnmounted(() => {
  document.removeEventListener("click", closeDropdown);
});
</script>

<style scoped>
/* Add any specific styles here if needed */
.relative {
  position: relative;
}
</style>
