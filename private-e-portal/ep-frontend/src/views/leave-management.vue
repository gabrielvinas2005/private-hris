<template>
  <MainLayout :breadcrumbs="breadcrumbs">
    <div class="max-w-7xl mx-auto">
      <!-- Header Section -->
      <div class="mb-8">
        <h2 class="text-2xl font-bold text-slate-900 mb-2 text-center">Leave Management</h2>
        <p class="text-slate-600 text-center">Submit and manage your leave requests and monetization</p>
      </div>

      <!-- Module Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-4xl mx-auto">
        <!-- Leave Application -->
        <button
          @click="navigateToModule('leaves')"
          class="group bg-white rounded-xl shadow-sm border border-slate-200 p-8 hover:shadow-lg transition-all duration-300 transform hover:scale-105"
        >
          <div class="text-center">
            <div class="w-16 h-16 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-blue-100 transition-colors duration-200">
              <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
              </svg>
            </div>
            <h4 class="text-lg font-semibold text-slate-900 mb-2">Leave Application</h4>
            <p class="text-sm text-slate-600">Submit and manage your leave applications</p>
          </div>
        </button>

        <!-- Leave Monetization -->
        <button
          @click="navigateToModule('leave-monetization')"
          class="group bg-white rounded-xl shadow-sm border border-slate-200 p-8 hover:shadow-lg transition-all duration-300 transform hover:scale-105"
        >
          <div class="text-center">
            <div class="w-16 h-16 bg-green-50 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-green-100 transition-colors duration-200">
              <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
              </svg>
            </div>
            <h4 class="text-lg font-semibold text-slate-900 mb-2">Leave Monetization</h4>
            <p class="text-sm text-slate-600">Convert leave credits to cash</p>
          </div>
        </button>
      </div>
    </div>
  </MainLayout>
</template>

<script>
import MainLayout from '../layout/MainLayout.vue'

export default {
  name: 'LeaveManagement',
  components: { MainLayout },
  data() {
    return {
      breadcrumbs: [
        { name: 'Dashboard', path: '/dashboard' },
        { name: 'Leave Management', path: '/leave-management' }
      ]
    }
  },
  methods: {
    navigateToModule(module) {
      switch (module) {
        case 'leaves':
          this.$router.push('/leaves')
          break
        case 'leave-monetization':
          this.$router.push('/leave-monetization')
          break
        default:
          break
      }
    }
  }
}
</script>
