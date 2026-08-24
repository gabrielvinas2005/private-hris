<script setup>
/**
 * MainLayout.vue — shared portal shell (sidebar + header + content frame)
 * ---------------------------------------------------------------------
 * Drop this into any module (HR, Payroll, Timekeeping, Control Panel, ...)
 * and pass that module's own nav items / admin items / user data as props.
 * All permission logic (hasMenuAccess, hasCpmAccess, etc.) stays in the
 * PARENT app — this component only reads a plain `visible: boolean` per
 * item, so it never needs to know each module's access-control rules.
 *
 * See ./navigation.example.js for sample nav arrays per module and
 * ./MainLayout.README.md for full integration instructions.
 */
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRoute } from 'vue-router'

const props = defineProps({
  // Branding
  companyName: { type: String, default: 'Company' },
  companyLogo: { type: String, default: null },
  logoFallbackText: { type: String, default: 'HR' },
  systemLabel: { type: String, default: 'Employee System' },
  version: { type: String, default: 'v1.0.0' },

  // Theme
  dark: { type: Boolean, default: false },

  // Page header (usually derived from route.meta in the parent)
  pageTitle: { type: String, default: '' },
  pageDescription: { type: String, default: '' },

  // Navigation — see navigation.example.js for the item shape
  navItems: { type: Array, default: () => [] },
  adminItems: { type: Array, default: () => [] },
  adminSectionLabel: { type: String, default: 'Administrative Access' },

  // User
  userData: { type: Object, default: () => ({ name: '', email: '' }) },
  userAvatarPhoto: { type: String, default: null },
  userInitials: { type: String, default: '' },

  // Notifications
  notifications: { type: Array, default: () => [] },
  unreadCount: { type: Number, default: 0 },
  showArchived: { type: Boolean, default: false },

  // Sidebar persistence — pass a unique key per module if you DON'T want
  // collapse-state shared across modules that live on the same domain.
  collapsedStorageKey: { type: String, default: 'portal-sidebar-collapsed' },
})

const emit = defineEmits([
  'logout',
  'refresh-user-data',
  'notification-click',
  'mark-all-read',
  'archive-notification',
  'archive-all-read',
  'toggle-archived',
])

const route = useRoute()

/* ---------------------------- Sidebar collapse --------------------------- */
const isSidebarCollapsed = ref(localStorage.getItem(props.collapsedStorageKey) === '1')
function toggleSidebar() {
  isSidebarCollapsed.value = !isSidebarCollapsed.value
  localStorage.setItem(props.collapsedStorageKey, isSidebarCollapsed.value ? '1' : '0')
}

/* ----------------------------- Theme Switcher ---------------------------- */
const isDarkMode = ref(localStorage.getItem('theme') === 'dark')
function toggleTheme() {
  isDarkMode.value = !isDarkMode.value
  localStorage.setItem('theme', isDarkMode.value ? 'dark' : 'light')
  if (isDarkMode.value) {
    document.documentElement.classList.add('dark')
  } else {
    document.documentElement.classList.remove('dark')
  }
}
onMounted(() => {
  if (isDarkMode.value) {
    document.documentElement.classList.add('dark')
  }
})

/* -------------------------------- Active nav ------------------------------ */
function isActive(item) {
  const path = route.path
  const matches = path === item.to || path.startsWith(item.to + '/')
  if (!matches) return false
  if (item.exactExclude?.some((p) => path === p || path.startsWith(p + '/'))) return false
  return true
}

/* ------------------------------ Notifications ----------------------------- */
const showNotifications = ref(false)
const notificationRoot = ref(null)

function toggleNotifications() {
  showNotifications.value = !showNotifications.value
}
function handleClickOutside(e) {
  if (notificationRoot.value && !notificationRoot.value.contains(e.target)) {
    showNotifications.value = false
  }
}
onMounted(() => document.addEventListener('click', handleClickOutside))
onBeforeUnmount(() => document.removeEventListener('click', handleClickOutside))

function notificationIconClass(type) {
  return type === 'retirement_reminder'
    ? 'bg-amber-100 text-amber-600'
    : 'bg-[#3B5EFF]/10 text-[#3B5EFF]'
}
function formatNotificationDate(dateStr) {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  return Number.isNaN(d.getTime()) ? '' : d.toLocaleString()
}

const hasVisibleAdminItems = computed(() => props.adminItems.some((i) => i.visible !== false))
</script>

<template>
  <div class="flex min-h-screen bg-[#F3F5FA] text-slate-900 font-sans antialiased">
    <!-- ============================ SIDEBAR ============================ -->
    <aside :class="[
      isSidebarCollapsed ? 'w-20' : 'w-64 sm:w-72',
      dark
        ? 'dark bg-[#0B1120] text-slate-300 border-white/[0.06] shadow-2xl'
        : 'bg-white text-slate-700 border-slate-200/70 shadow-xl shadow-slate-200/50',
      'h-screen sticky top-0 z-40 flex flex-col transition-all duration-300 ease-in-out border-r overflow-y-auto'
    ]">
      <!-- Sidebar Header -->
      <div :class="[dark ? 'border-white/[0.06]' : 'border-slate-200/70', 'px-6 py-8 border-b']">
        <div class="flex flex-col items-center text-center space-y-3">
          <slot name="logo">
            <div v-if="companyLogo" :class="[dark ? 'bg-white/10' : 'bg-slate-100 border border-slate-200/70', 'flex items-center justify-center overflow-hidden w-12 h-12 rounded-2xl']">
              <img :src="companyLogo" :alt="companyName" class="object-contain w-full h-full" />
            </div>
            <div v-else class="flex items-center justify-center w-12 h-12 rounded-2xl bg-gradient-to-br from-[#4A6CFB] to-[#22308F] text-white font-bold text-lg tracking-tight shadow-lg shadow-[#3B5EFF]/30">
              {{ logoFallbackText }}
            </div>
          </slot>
          <div v-if="!isSidebarCollapsed" class="min-w-0 w-full">
            <h1 :class="[dark ? 'text-white' : 'text-slate-900', 'text-base font-bold tracking-tight truncate']">{{ companyName }}</h1>
            <p :class="[dark ? 'text-slate-500' : 'text-slate-400', 'text-[11px] font-semibold uppercase tracking-wider truncate mt-0.5']">{{ systemLabel }}</p>
          </div>
        </div>
      </div>

      <!-- Nav items — data-driven, pass your module's own array via `nav-items` -->
      <nav class="flex-1 px-3.5 py-5 space-y-1 overflow-y-auto sidebar-scroll">
        <template v-for="item in navItems" :key="item.key ?? item.to">
          <router-link
            v-if="item.visible !== false"
            :to="item.to"
            class="flex items-center py-2.5 space-x-3 text-[13.5px] font-medium transition-all duration-200 rounded-xl group"
            :class="[
              isSidebarCollapsed ? 'justify-center px-0' : 'px-3.5',
              isActive(item)
                ? 'bg-gradient-to-r from-[#3B5EFF] to-[#2946D9] text-white font-semibold shadow-lg shadow-[#3B5EFF]/25'
                : (dark ? 'text-slate-400 hover:text-white hover:bg-white/[0.06]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100')
            ]"
          >
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" :viewBox="item.viewBox || '0 0 24 24'">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon"></path>
            </svg>
            <span v-if="!isSidebarCollapsed" class="flex items-center gap-1.5">
              {{ item.label }}
              <span v-if="item.badge" class="text-[#7C96FF]">({{ item.badge }})</span>
            </span>
          </router-link>
        </template>

        <!-- Admin section — items navigate programmatically (cross-app), so they use onClick, not router-link -->
        <div v-if="adminItems.length && hasVisibleAdminItems" :class="[dark ? 'border-white/[0.06]' : 'border-slate-200/70', 'pt-4 border-t mt-4']">
          <div v-if="!isSidebarCollapsed" :class="[dark ? 'text-slate-500' : 'text-slate-400', 'px-3.5 pt-1 pb-2 text-[10.5px] font-bold tracking-widest uppercase']">
            {{ adminSectionLabel }}
          </div>
          <a
            v-for="item in adminItems"
            v-show="item.visible !== false"
            :key="item.key"
            @click="item.onClick && item.onClick()"
            class="flex items-center py-2.5 space-x-3 text-[13.5px] font-medium transition-all duration-200 rounded-xl cursor-pointer group"
            :class="[
              isSidebarCollapsed ? 'justify-center px-0' : 'px-3.5',
              dark ? 'text-slate-400 hover:text-white hover:bg-white/[0.06]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'
            ]"
          >
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" :viewBox="item.viewBox || '0 0 24 24'">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon"></path>
            </svg>
            <span v-if="!isSidebarCollapsed">{{ item.label }}</span>
          </a>
        </div>
      </nav>

      <!-- Sidebar Footer -->
      <div :class="[dark ? 'border-white/[0.06] bg-[#0D1425]/80' : 'border-slate-200/70 bg-slate-50/80', 'p-4 border-t']">
        <div class="flex items-center" :class="isSidebarCollapsed ? 'justify-center' : 'justify-between'">
          <button
            @click="toggleSidebar"
            :class="[dark ? 'text-slate-400 hover:text-white hover:bg-white/10' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-200/60', 'p-2 transition rounded-xl']"
            :aria-label="isSidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'"
          >
            <svg v-if="!isSidebarCollapsed" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </button>
          <div v-if="!isSidebarCollapsed" class="text-right">
            <p :class="[dark ? 'text-slate-400' : 'text-slate-600', 'text-xs font-medium']">{{ companyName }}</p>
            <p :class="[dark ? 'text-slate-600' : 'text-slate-400', 'text-[11px]']">{{ version }}</p>
          </div>
        </div>
      </div>
    </aside>

    <!-- ========================= MAIN COLUMN ========================= -->
    <div class="flex flex-col flex-1 min-w-0">
      <header class="sticky top-0 z-30 px-4 py-3 border-b border-slate-200/70 shadow-sm bg-white/90 backdrop-blur sm:px-6">
        <div class="mt-2 mb-2 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
          <div>
            <h2 class="text-xl font-bold tracking-tight text-slate-900">{{ pageTitle }}</h2>
            <p v-if="pageDescription" class="mt-0.5 text-sm text-slate-500">{{ pageDescription }}</p>
          </div>

          <div class="flex flex-wrap items-center gap-3 lg:justify-end">
            <!-- Theme Switcher Button -->
            <button
              @click="toggleTheme"
              class="p-2 text-slate-500 transition-all duration-200 rounded-xl hover:text-slate-900 hover:bg-slate-100 dark:text-slate-400 dark:hover:text-white dark:hover:bg-white/10"
              :title="isDarkMode ? 'Switch to Light Mode' : 'Switch to Dark Mode'"
              :aria-label="isDarkMode ? 'Switch to Light Mode' : 'Switch to Dark Mode'"
            >
              <svg v-if="!isDarkMode" class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
              </svg>
              <svg v-else class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
              </svg>
            </button>

            <!-- Notifications -->
            <div class="relative" ref="notificationRoot">
              <button
                @click="toggleNotifications"
                class="relative p-2.5 text-slate-500 transition-all duration-200 rounded-xl hover:text-[#3B5EFF] hover:bg-[#3B5EFF]/[0.07]"
              >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM11 19a7 7 0 01-7-7v-3a4 4 0 014-4h6a4 4 0 014 4v3a7 7 0 01-7 7z"></path>
                </svg>
                <span v-if="unreadCount > 0" class="absolute flex items-center justify-center w-5 h-5 text-[10px] font-semibold text-white bg-red-500 rounded-full -top-1 -right-1 ring-2 ring-white">
                  {{ unreadCount > 9 ? '9+' : unreadCount }}
                </span>
              </button>

              <div v-if="showNotifications" class="absolute right-0 z-50 mt-3 overflow-hidden bg-white border border-slate-200 rounded-2xl shadow-2xl shadow-slate-300/40 w-[22rem] sm:w-96">
                <div class="p-4 border-b border-slate-100">
                  <div class="flex items-center justify-between mb-2">
                    <h3 class="text-lg font-bold tracking-tight text-slate-900">Notifications</h3>
                    <button @click="emit('mark-all-read')" class="text-sm font-medium text-[#3B5EFF] hover:text-[#2946D9]">
                      Mark all as read
                    </button>
                  </div>
                  <div class="flex items-center justify-between text-xs text-slate-500">
                    <button @click="emit('archive-all-read')" class="hover:text-slate-800">Archive all read</button>
                    <button @click="emit('toggle-archived')" class="hover:text-slate-800">
                      {{ showArchived ? 'Hide archived' : 'Show archived' }}
                    </button>
                  </div>
                </div>

                <div class="overflow-y-auto max-h-96">
                  <div v-if="notifications.length === 0" class="p-6 text-center text-sm text-slate-400">
                    No notifications
                  </div>
                  <div
                    v-for="notification in notifications"
                    :key="notification.id"
                    class="relative p-4 transition-colors duration-200 border-b border-slate-100 group hover:bg-slate-50"
                  >
                    <div class="flex items-start space-x-3">
                      <div class="flex-shrink-0" @click="emit('notification-click', notification)">
                        <div :class="notificationIconClass(notification.type)" class="flex items-center justify-center w-8 h-8 rounded-full cursor-pointer">
                          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path v-if="notification.type === 'retirement_reminder'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                          </svg>
                        </div>
                      </div>
                      <div class="flex-1 min-w-0 cursor-pointer" @click="emit('notification-click', notification)">
                        <p class="text-sm font-semibold text-slate-900">{{ notification.title || notification.Title || 'Untitled' }}</p>
                        <p class="mt-1 text-sm text-slate-600">{{ notification.message || notification.content || notification.Event }}</p>
                        <div class="flex items-center justify-between mt-2">
                          <span class="text-xs text-slate-400">{{ formatNotificationDate(notification.created_at) }}</span>
                          <div class="flex items-center space-x-2">
                            <span v-if="notification.data && notification.data.employee_id" class="px-2 py-0.5 text-[11px] font-medium text-[#2946D9] bg-[#3B5EFF]/10 rounded-full">Personal</span>
                            <span v-else class="px-2 py-0.5 text-[11px] font-medium text-emerald-700 bg-emerald-100 rounded-full">Global</span>
                            <span v-if="!notification.is_read" class="w-2 h-2 bg-red-500 rounded-full"></span>
                          </div>
                        </div>
                      </div>
                      <button
                        @click.stop="emit('archive-notification', notification.id)"
                        class="flex-shrink-0 p-1 text-slate-400 transition-colors rounded opacity-0 group-hover:opacity-100 hover:text-slate-600 hover:bg-slate-200"
                        title="Archive notification"
                      >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                        </svg>
                      </button>
                    </div>
                  </div>
                </div>

                <div class="p-4 bg-white border-t border-slate-200/70">
                  <slot name="notifications-footer">
                    <span class="block text-sm font-semibold text-center text-[#3B5EFF]">View all notifications</span>
                  </slot>
                </div>
              </div>
            </div>

            <!-- User menu -->
            <div class="flex items-center space-x-3">
              <div class="text-right">
                <p class="text-sm font-semibold text-slate-900">{{ userData.name }}</p>
                <p class="text-xs text-slate-500">{{ userData.email }}</p>
              </div>
              <img v-if="userAvatarPhoto" :src="userAvatarPhoto" alt="Profile" class="w-9 h-9 rounded-full object-cover shadow-sm border border-slate-200" />
              <div v-else class="flex items-center justify-center w-9 h-9 rounded-full bg-gradient-to-br from-[#4A6CFB] to-[#22308F] text-white font-bold text-xs shadow-sm shadow-[#3B5EFF]/30">
                {{ userInitials }}
              </div>
            </div>
            <div class="w-px h-6 bg-slate-200"></div>
            <button
              @click="emit('refresh-user-data')"
              class="flex items-center px-3 py-2 space-x-2 text-sm font-medium text-slate-600 transition-all duration-200 rounded-lg hover:text-slate-900 hover:bg-slate-100"
              title="Refresh Access Rights"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
              </svg>
              <span class="hidden sm:inline">Refresh</span>
            </button>
            <button
              @click="emit('logout')"
              class="flex items-center px-3 py-2 space-x-2 text-sm font-medium text-slate-600 transition-all duration-200 rounded-lg hover:text-red-600 hover:bg-red-50"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
              </svg>
              <span class="hidden sm:inline">Sign Out</span>
            </button>
          </div>
        </div>
      </header>

      <main class="flex-1 min-w-0 p-4 overflow-y-auto bg-[#F3F5FA] sm:p-6">
        <div class="p-4 bg-white border border-slate-200/70 shadow-sm rounded-2xl sm:p-6">
          <slot />
        </div>
      </main>
    </div>
  </div>
</template>
