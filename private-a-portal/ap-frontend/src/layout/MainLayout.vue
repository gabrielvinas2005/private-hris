<template>
  <div class="min-h-screen bg-[#F3F5FA] text-slate-900 font-sans antialiased flex flex-col">
    <!-- Header Shell -->
    <header class="sticky top-0 z-30 px-4 py-3 border-b border-slate-200/70 shadow-sm bg-white/90 backdrop-blur sm:px-6">
      <div class="max-w-7xl mx-auto flex items-center justify-between">
        <!-- Logo and System Info -->
        <div class="flex items-center space-x-3 sm:space-x-4">
          <div v-if="logoImage" class="flex items-center justify-center overflow-hidden w-10 h-10 rounded-xl bg-slate-100 border border-slate-200/70">
            <img :src="logoImage" :alt="companyName" class="object-contain w-full h-full" />
          </div>
          <div v-else class="flex items-center justify-center w-10 h-10 rounded-xl bg-gradient-to-br from-[#4A6CFB] to-[#22308F] text-white font-bold text-base tracking-tight shadow-md shadow-[#3B5EFF]/25">
            AP
          </div>

          <div class="h-8 w-px bg-slate-200/70 hidden sm:block"></div>

          <div>
            <h1 class="text-base sm:text-lg font-bold tracking-tight text-slate-900 leading-tight">Applicant Portal</h1>
            <p class="text-xs font-semibold text-slate-400 truncate uppercase tracking-wider">{{ companyName }}</p>
          </div>
        </div>

        <!-- User Controls & Sign Out -->
        <div class="flex items-center space-x-3 sm:space-x-4">
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

          <!-- User Profile Badge -->
          <div class="flex items-center px-3 py-1.5 border rounded-2xl space-x-2.5 bg-slate-50/80 border-slate-200/70">
            <div class="flex items-center justify-center w-8 h-8 rounded-full bg-gradient-to-br from-[#4A6CFB] to-[#22308F] text-white font-bold text-xs shadow-sm shadow-[#3B5EFF]/30">
              {{ userInitials }}
            </div>
            <div class="text-left hidden md:block">
              <p class="text-xs font-semibold leading-tight truncate max-w-[140px] text-slate-800">{{ userData.name }}</p>
              <p class="text-[10px] font-medium leading-tight truncate max-w-[140px] text-slate-500">{{ userData.email }}</p>
            </div>
          </div>

          <button
            @click="logout"
            class="flex items-center px-3 py-2 space-x-2 text-sm font-medium text-slate-600 transition-all duration-200 rounded-xl hover:text-red-600 hover:bg-red-50"
          >
            <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
            </svg>
            <span class="hidden sm:inline">Sign Out</span>
          </button>
        </div>
      </div>
    </header>

    <!-- Breadcrumb Navigation -->
    <div v-if="breadcrumbs.length > 0" class="bg-white/80 border-b border-slate-200/70 backdrop-blur">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2.5">
        <Breadcrumb :breadcrumbs="breadcrumbs" />
      </div>
    </div>

    <!-- Main Container Content -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
      <div class="p-4 sm:p-6 lg:p-8 bg-white border border-slate-200/70 shadow-sm rounded-2xl">
        <slot />
      </div>
    </main>
  </div>
</template>

<script>
import Breadcrumb from '../components/Breadcrumb.vue'
import { fetchCompanyPublic, getCompanyPublic, getCompanyLogo } from '../services/companyPublic'

export default {
  name: 'MainLayout',
  components: { Breadcrumb },
  props: {
    breadcrumbs: {
      type: Array,
      default: () => []
    }
  },
  data() {
    return {
      logoImage: null,
      companyName: getCompanyPublic().name?.trim() || 'Company',
      isDarkMode: localStorage.getItem('theme') === 'dark',
      userData: {
        name: 'Applicant',
        email: 'applicant@example.com'
      }
    }
  },
  computed: {
    userInitials() {
      const name = this.userData.name || ''
      if (!name) return 'AP'
      const parts = name.split(' ').filter(Boolean)
      if (parts.length >= 2) return (parts[0][0] + parts[1][0]).toUpperCase()
      return name.substring(0, 2).toUpperCase()
    }
  },
  async mounted() {
    if (this.isDarkMode) {
      document.documentElement.classList.add('dark')
    }
    const company = await fetchCompanyPublic()
    this.companyName = company?.name?.trim() || 'Company'
    this.logoImage = getCompanyLogo(company)
    const storedUserData = localStorage.getItem('user_data')
    if (storedUserData) {
      try {
        this.userData = JSON.parse(storedUserData)
      } catch (e) {}
    }
  },
  methods: {
    toggleTheme() {
      this.isDarkMode = !this.isDarkMode
      localStorage.setItem('theme', this.isDarkMode ? 'dark' : 'light')
      if (this.isDarkMode) {
        document.documentElement.classList.add('dark')
      } else {
        document.documentElement.classList.remove('dark')
      }
    },
    logout() {
      localStorage.removeItem('auth_token')
      localStorage.removeItem('user_data')
      this.$router.push('/login')
    }
  }
}
</script>