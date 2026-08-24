<template>
  <MainLayout :breadcrumbs="breadcrumbs">
    <div class="max-w-7xl mx-auto">
      <!-- Header Section -->
      <div class="mb-8">
        <h2 class="text-2xl font-bold text-slate-900 mb-2 text-center">Leave & Time Management</h2>
        <p class="text-slate-600 text-center">Manage your leave requests and work attendance</p>
      </div>

      <!-- Module Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 max-w-6xl mx-auto">
        
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

        <!-- OB Application -->
        <button
          @click="navigateToModule('ob-application')"
          class="group bg-white rounded-xl shadow-sm border border-slate-200 p-8 hover:shadow-lg transition-all duration-300 transform hover:scale-105"
        >
          <div class="text-center">
            <div class="w-16 h-16 bg-orange-50 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-orange-100 transition-colors duration-200">
              <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
              </svg>
            </div>
            <h4 class="text-lg font-semibold text-slate-900 mb-2">OB Application</h4>
            <p class="text-sm text-slate-600">Submit official business applications</p>
          </div>
        </button>

        <!-- DTR -->
        <button
          @click="navigateToModule('dtr')"
          class="group bg-white rounded-xl shadow-sm border border-slate-200 p-8 hover:shadow-lg transition-all duration-300 transform hover:scale-105"
        >
          <div class="text-center">
            <div class="w-16 h-16 bg-purple-50 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-purple-100 transition-colors duration-200">
              <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
            <h4 class="text-lg font-semibold text-slate-900 mb-2">DTR</h4>
            <p class="text-sm text-slate-600">Daily Time Record management</p>
          </div>
        </button>

        <!-- DTR Application -->
        <button
          @click="navigateToModule('dtr-application')"
          class="group bg-white rounded-xl shadow-sm border border-slate-200 p-8 hover:shadow-lg transition-all duration-300 transform hover:scale-105"
        >
          <div class="text-center">
            <div class="w-16 h-16 bg-sky-50 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-sky-100 transition-colors duration-200">
              <svg class="w-8 h-8 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
              </svg>
            </div>
            <h4 class="text-lg font-semibold text-slate-900 mb-2">DTR Application</h4>
            <p class="text-sm text-slate-600">Submit time log corrections for approval</p>
          </div>
        </button>

        <!-- DTR For Approval (supervisors only) -->
        <button
          v-if="isDtrApprover"
          @click="navigateToModule('dtr-for-approval')"
          class="group bg-white rounded-xl shadow-sm border border-slate-200 p-8 hover:shadow-lg transition-all duration-300 transform hover:scale-105"
        >
          <div class="text-center">
            <div class="w-16 h-16 bg-violet-50 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-violet-100 transition-colors duration-200">
              <svg class="w-8 h-8 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
              </svg>
            </div>
            <h4 class="text-lg font-semibold text-slate-900 mb-2">DTR For Approval</h4>
            <p class="text-sm text-slate-600">Review team DTR correction requests</p>
          </div>
        </button>

        <!-- Accomplishment Application (COS employees only) -->
        <button
          v-if="canSubmitAccomplishment"
          @click="navigateToModule('accomplishment-application')"
          class="group bg-white rounded-xl shadow-sm border border-slate-200 p-8 hover:shadow-lg transition-all duration-300 transform hover:scale-105"
        >
          <div class="text-center">
            <div class="w-16 h-16 bg-indigo-50 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-indigo-100 transition-colors duration-200">
              <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
              </svg>
            </div>
            <h4 class="text-lg font-semibold text-slate-900 mb-2">Accomplishment Application</h4>
            <p class="text-sm text-slate-600">Submit and track COS accomplishment reports</p>
          </div>
        </button>

        <!-- Accomplishment For Approval (approvers only) -->
        <button
          v-if="isAccomplishmentApprover"
          @click="navigateToModule('accomplishment-for-approval')"
          class="group bg-white rounded-xl shadow-sm border border-slate-200 p-8 hover:shadow-lg transition-all duration-300 transform hover:scale-105"
        >
          <div class="text-center">
            <div class="w-16 h-16 bg-fuchsia-50 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-fuchsia-100 transition-colors duration-200">
              <svg class="w-8 h-8 text-fuchsia-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
              </svg>
            </div>
            <h4 class="text-lg font-semibold text-slate-900 mb-2">Accomplishment For Approval</h4>
            <p class="text-sm text-slate-600">Review team COS accomplishment reports</p>
          </div>
        </button>

        <!-- Service Rendered Application (COS employees only) -->
        <button
          v-if="isCosEmployee"
          @click="navigateToModule('service-rendered-application')"
          class="group bg-white rounded-xl shadow-sm border border-slate-200 p-8 hover:shadow-lg transition-all duration-300 transform hover:scale-105"
        >
          <div class="text-center">
            <div class="w-16 h-16 bg-rose-50 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-rose-100 transition-colors duration-200">
              <svg class="w-8 h-8 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
              </svg>
            </div>
            <h4 class="text-lg font-semibold text-slate-900 mb-2">Service Rendered Application</h4>
            <p class="text-sm text-slate-600">Submit certificate of rendered service for approval</p>
          </div>
        </button>

        <!-- Rendered Service For Approval (approvers only) -->
        <button
          v-if="isRenderedServiceApprover"
          @click="navigateToModule('service-rendered-for-approval')"
          class="group bg-white rounded-xl shadow-sm border border-slate-200 p-8 hover:shadow-lg transition-all duration-300 transform hover:scale-105"
        >
          <div class="text-center">
            <div class="w-16 h-16 bg-orange-50 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-orange-100 transition-colors duration-200">
              <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
              </svg>
            </div>
            <h4 class="text-lg font-semibold text-slate-900 mb-2">Rendered Service For Approval</h4>
            <p class="text-sm text-slate-600">Review certificate of rendered service requests</p>
          </div>
        </button>

        <!-- WFH Attendance -->
        <button
          @click="navigateToModule('wfh-attendance')"
          class="group bg-white rounded-xl shadow-sm border border-slate-200 p-8 hover:shadow-lg transition-all duration-300 transform hover:scale-105"
        >
          <div class="text-center">
            <div class="w-16 h-16 bg-teal-50 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-teal-100 transition-colors duration-200">
              <svg class="w-8 h-8 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
              </svg>
            </div>
            <h4 class="text-lg font-semibold text-slate-900 mb-2">WFH Attendance</h4>
            <p class="text-sm text-slate-600">Work from home attendance tracking</p>
          </div>
        </button>
      </div>

      <!-- Quick Stats -->
      <!-- <div class="mt-12 grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-slate-600 mb-1">Available Leave</p>
              <p class="text-2xl font-bold text-slate-900">15.5</p>
              <p class="text-xs text-green-600 font-medium mt-1">Days remaining</p>
            </div>
            <div class="p-3 bg-green-50 rounded-lg">
              <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-slate-600 mb-1">Pending Requests</p>
              <p class="text-2xl font-bold text-slate-900">2</p>
              <p class="text-xs text-orange-600 font-medium mt-1">Awaiting approval</p>
            </div>
            <div class="p-3 bg-orange-50 rounded-lg">
              <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-slate-600 mb-1">Work Hours Today</p>
              <p class="text-2xl font-bold text-slate-900">8.5</p>
              <p class="text-xs text-blue-600 font-medium mt-1">Hours logged</p>
            </div>
            <div class="p-3 bg-blue-50 rounded-lg">
              <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-slate-600 mb-1">WFH Days</p>
              <p class="text-2xl font-bold text-slate-900">3</p>
              <p class="text-xs text-teal-600 font-medium mt-1">This month</p>
            </div>
            <div class="p-3 bg-teal-50 rounded-lg">
              <svg class="w-6 h-6 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
              </svg>
            </div>
          </div>
        </div>
      </div> -->
    </div>
  </MainLayout>
</template>

<script>
import MainLayout from '../layout/MainLayout.vue'

export default {
  name: 'LeaveTimeManagement',
  components: { MainLayout },
  data() {
    return {
      breadcrumbs: [
        { name: 'Dashboard', path: '/dashboard' },
        { name: 'Leave & Time Management', path: '/leave-time' }
      ],
      isCosAccomplishmentAvailable: false,
      isCosEmployee: false,
      canSubmitAccomplishment: false,
      isDtrApprover: false,
      isAccomplishmentApprover: false,
      isRenderedServiceApprover: false
    }
  },
  async mounted() {
    this.$router.replace('/leave-management')
  },
  methods: {
    async loadDtrApproverAccess() {
      try {
        const userData = JSON.parse(localStorage.getItem('user_data') || '{}')
        if (!userData.id) return
        const { dtrApiService } = await import('../services/apiService.js')
        const response = await dtrApiService.checkDtrApproverAccess(userData.id)
        const payload = response?.data?.data ?? response?.data ?? response
        this.isDtrApprover = Boolean(payload?.supervisor_id ?? payload?.is_approver)
      } catch (error) {
        console.warn('DTR approver access check failed:', error?.response?.data?.message || error?.message)
        this.isDtrApprover = false
      }
    },
    async loadCosAccomplishmentAccess() {
      try {
        const ApiService = (await import('../services/api.js')).default
        const response = await ApiService.checkEmployeeNonDTRAccess()
        if (response?.success) {
          this.isCosAccomplishmentAvailable = !!response.data?.has_access
          this.isCosEmployee = !!response.data?.is_cos_employee
          this.canSubmitAccomplishment = !!response.data?.can_submit_report
        }
      } catch (error) {
        this.isCosAccomplishmentAvailable = false
        this.isCosEmployee = false
        this.canSubmitAccomplishment = false
      }
    },
    async loadAccomplishmentApproverAccess() {
      try {
        const userData = JSON.parse(localStorage.getItem('user_data') || '{}')
        if (!userData.id) return
        const { accomplishmentApiService } = await import('../services/apiService.js')
        const response = await accomplishmentApiService.checkApproverAccess(userData.id)
        const payload = response?.data?.data ?? response?.data ?? response
        this.isAccomplishmentApprover = Boolean(payload?.supervisor_id ?? payload?.is_approver)
      } catch (error) {
        this.isAccomplishmentApprover = false
      }
    },
    async loadRenderedServiceApproverAccess() {
      try {
        const userData = JSON.parse(localStorage.getItem('user_data') || '{}')
        if (!userData.id) return
        const { serviceRenderedApiService } = await import('../services/apiService.js')
        const response = await serviceRenderedApiService.checkApproverAccess(userData.id)
        const payload = response?.data?.data ?? response?.data ?? response
        this.isRenderedServiceApprover = Boolean(payload?.supervisor_id ?? payload?.is_approver)
      } catch (error) {
        this.isRenderedServiceApprover = false
      }
    },
    navigateToModule(module) {
              // Navigating to module
      
      // Navigate to the appropriate module
      switch (module) {
        case 'leaves':
          this.$router.push('/leaves')
          break
        case 'leave-monetization':
          this.$router.push('/leave-monetization')
          break
        case 'ob-application':
          this.$router.push('/ob-application')
          break
        case 'dtr':
          this.$router.push('/dtr')
          break
        case 'dtr-application':
          this.$router.push('/dtr/application')
          break
        case 'dtr-for-approval':
          this.$router.push('/dtr/for-approval')
          break
        case 'accomplishment-application':
          this.$router.push('/accomplishment/application')
          break
        case 'accomplishment-for-approval':
          this.$router.push('/accomplishment/for-approval')
          break
        case 'service-rendered-application':
          this.$router.push('/service-rendered/application')
          break
        case 'service-rendered-for-approval':
          this.$router.push('/service-rendered/for-approval')
          break
        case 'review-dtr':
          this.$router.push('/review-dtr')
          break
        case 'wfh-attendance':
          this.$router.push('/wfh-attendance')
          break
        default:
          // Module not implemented yet
      }
    }
  }
}
</script>