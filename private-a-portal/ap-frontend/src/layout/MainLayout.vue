<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-slate-100">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b border-slate-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
          <!-- Logo and Title -->
          <div class="flex items-center space-x-4">
            <div class="flex items-center space-x-3">
              <img v-if="logoImage" :src="logoImage" :alt="companyName" class="w-8 h-8" />
              <div class="h-8 w-px bg-slate-300"></div>
            </div>
            <div>
              <h1 class="text-lg font-semibold text-slate-900">Applicant Portal</h1>
              <p class="text-xs text-slate-500 font-medium">{{ companyName }}</p>
            </div>
          </div>

          <!-- User Menu -->
          <div class="flex items-center space-x-4">
            <div class="text-right">
              <p class="text-sm font-medium text-slate-900">{{ userData.name }}</p>
              <p class="text-xs text-slate-500">{{ userData.email }}</p>
            </div>
            <div class="h-6 w-px bg-slate-300"></div>
            <button
              @click="logout"
              class="flex items-center space-x-2 px-3 py-2 text-sm text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-md transition-all duration-200"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
              </svg>
              <span class="font-medium">Sign Out</span>
            </button>
          </div>
        </div>
      </div>
    </header>

    <!-- Breadcrumb Navigation (Outside Header) -->
    <div v-if="breadcrumbs.length > 0" class="bg-white border-b border-slate-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
        <Breadcrumb :breadcrumbs="breadcrumbs" />
      </div>
    </div>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <slot />
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
      userData: {
        name: 'Administrator',
        email: 'asu.admin@gmail.com'
      }
    }
  },
  async mounted() {
    const company = await fetchCompanyPublic()
    this.companyName = company?.name?.trim() || 'Company'
    this.logoImage = getCompanyLogo(company)
    // Get user data from localStorage
    const storedUserData = localStorage.getItem('user_data')
    if (storedUserData) {
      this.userData = JSON.parse(storedUserData)
    }
  },
  methods: {
    logout() {
      // Clear stored data
      localStorage.removeItem('auth_token')
      localStorage.removeItem('user_data')

      // Redirect to login
      this.$router.push('/login')
    }
  }
}
</script>