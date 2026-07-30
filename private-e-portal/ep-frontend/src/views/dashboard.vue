  <template>
    <MainLayout>
      <!-- Welcome Section -->
      <div class="mb-6">
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-lg p-6 text-white shadow-sm">
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-2xl font-bold mb-1">Welcome, {{ userData.name }}</h1>
              <p class="text-blue-100">{{ companyPortalSubtitle }}</p>
            </div>
            <div class="flex items-center space-x-4">
              <div class="bg-white/20 rounded-lg p-3">
                <p class="text-xs text-blue-100 mb-1">Current Date</p>
                <p class="text-sm font-semibold">{{ currentDate }}</p>
              </div>
              <div class="bg-white/20 rounded-lg p-3">
                <p class="text-xs text-blue-100 mb-1">Current Time</p>
                <p class="text-sm font-semibold">{{ currentTime }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Administrative Access Section (Only if user has at least one admin module) -->
      <div v-if="hasAnyAdminAccess()" class="mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Administrative Access</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          
          <!-- HR Module Card -->
          <div 
            v-if="hasHrmAccess"
            @click="navigateToHRModule()"
            class="group bg-white rounded-lg border border-gray-200 p-4 hover:shadow-md hover:border-purple-300 transition-all duration-200 cursor-pointer"
          >
            <div class="flex items-center space-x-3">
              <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center group-hover:bg-purple-200 transition-colors duration-200">
                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
              </div>
              <div>
                <h3 class="font-semibold text-gray-900">201 Files</h3>
                <p class="text-gray-600 text-sm">HR Module - 201 Files</p>
              </div>
            </div>
          </div>

          <!-- Control Panel Card -->
          <div 
            v-if="hasCpmAccess"
            @click="navigateToControlPanel()"
            class="group bg-white rounded-lg border border-gray-200 p-4 hover:shadow-md hover:border-red-300 transition-all duration-200 cursor-pointer"
          >
            <div class="flex items-center space-x-3">
              <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center group-hover:bg-red-200 transition-colors duration-200">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
              </div>
              <div>
                <h3 class="font-semibold text-gray-900">Control Panel</h3>
                <p class="text-gray-600 text-sm">System Administration</p>
              </div>
            </div>
          </div>

          <!-- Payroll Module Card -->
          <div 
            v-if="hasHrpAccess"
            @click="navigateToPayrollModule()"
            class="group bg-white rounded-lg border border-gray-200 p-4 hover:shadow-md hover:border-yellow-300 transition-all duration-200 cursor-pointer"
          >
            <div class="flex items-center space-x-3">
              <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center group-hover:bg-yellow-200 transition-colors duration-200">
                <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                </svg>
              </div>
              <div>
                <h3 class="font-semibold text-gray-900">Payroll Module</h3>
                <p class="text-gray-600 text-sm">Payroll Management</p>
              </div>
            </div>
          </div>

          <!-- Timekeeping Module Card -->
          <div 
            v-if="hasHrtAccess"
            @click="navigateToTimekeeping()"
            class="group bg-white rounded-lg border border-gray-200 p-4 hover:shadow-md hover:border-green-300 transition-all duration-200 cursor-pointer"
          >
            <div class="flex items-center space-x-3">
              <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center group-hover:bg-green-200 transition-colors duration-200">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
              </div>
              <div>
                <h3 class="font-semibold text-gray-900">Timekeeping</h3>
                <p class="text-gray-600 text-sm">Time & Attendance</p>
              </div>
            </div>
          </div>

        </div>
      </div>

      <!-- Information Panels -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Quick Actions Panel (only if user has at least one allowed shortcut) -->
        <div
          v-if="menuAccessLoaded && hasAnyQuickAction"
          class="bg-white rounded-lg border border-gray-200 p-4 shadow-sm"
        >
          <div class="flex items-center justify-between mb-3">
            <h3 class="font-semibold text-gray-900">Quick Actions</h3>
            <div class="w-6 h-6 bg-blue-100 rounded flex items-center justify-center">
              <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
              </svg>
            </div>
          </div>
          <div class="space-y-2">
            <div 
              v-if="canViewLeave"
              @click="navigateToModule('leave-time')"
              class="flex items-center space-x-3 p-2 bg-gray-50 rounded-lg hover:bg-blue-50 transition-colors duration-200 cursor-pointer group"
            >
              <div class="w-8 h-8 bg-blue-100 rounded flex items-center justify-center group-hover:bg-blue-200 transition-colors duration-200">
                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM11 19a7 7 0 01-7-7v-3a4 4 0 014-4h6a4 4 0 014 4v3a7 7 0 01-7 7z"></path>
                </svg>
              </div>
              <div class="flex-1">
                <p class="text-gray-900 font-medium text-sm">Submit Leave Request</p>
                <p class="text-gray-600 text-xs">Apply for time off</p>
              </div>
            </div>
            
            <div 
              v-if="canViewOvertime"
              @click="navigateToModule('overtime-scheduling')"
              class="flex items-center space-x-3 p-2 bg-gray-50 rounded-lg hover:bg-green-50 transition-colors duration-200 cursor-pointer group"
            >
              <div class="w-8 h-8 bg-green-100 rounded flex items-center justify-center group-hover:bg-green-200 transition-colors duration-200">
                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
              </div>
              <div class="flex-1">
                <p class="text-gray-900 font-medium text-sm">Overtime Application</p>
                <p class="text-gray-600 text-xs">Request additional hours</p>
              </div>
            </div>
            
            <div 
              @click="navigateTo201File()"
              v-if="canViewEmployeeRecord"
              class="flex items-center space-x-3 p-2 bg-gray-50 rounded-lg hover:bg-purple-50 transition-colors duration-200 cursor-pointer group"
            >
              <div class="w-8 h-8 bg-purple-100 rounded flex items-center justify-center group-hover:bg-purple-200 transition-colors duration-200">
                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
              </div>
              <div class="flex-1">
                <p class="text-gray-900 font-medium text-sm">Update Profile</p>
                <p class="text-gray-600 text-xs">Edit personal information</p>
              </div>
            </div>
          </div>
        </div>
      </div>
  </MainLayout>
</template>

<script>
import MainLayout from '../layout/MainLayout.vue'
import AnnouncementList from '../components/Announcement/AnnouncementList.vue'
import { fetchCompanyPublic } from '../services/companyPublic.js'

export default {
  name: 'Dashboard',
  components: { MainLayout, AnnouncementList },
  data() {
    return {
      company: {
        name: '',
        address: '',
        email: '',
        telephone_no: '',
        mobile_no: '',
        logo_data_url: null
      },
      userData: {
        id: null,
        name: '',
        email: ''
      },
      menuAccessLoaded: false,
      canViewEmployeeRecord: false,
      canViewLeave: false,
      canViewOvertime: false,
      canViewPayslip: false,
      canViewDtr: false,
      canViewSaln: false,
      currentDate: '',
      currentTime: '',
      dashboardData: {
        leave_balance: { total_balance: 0, leave_types: [] },
        pending_requests: { total: 0, leaves: 0, overtime: 0, official_business: 0 },
        work_hours: { hours_today: 0, status: 'Hours today' },
        overtime_hours: 0,
        recent_activity: []
      },
      announcements: [],
      loading: false,
      hasHrmAccess: false,
      hasHrpAccess: false,
      hasHrtAccess: false,
      hasCpmAccess: false
    }
  },
  computed: {
    companyPortalSubtitle() {
      return this.company.name
        ? `${this.company.name} — Employee Portal`
        : 'Employee Portal'
    },
    hasAnyQuickAction() {
      return (
        this.canViewLeave ||
        this.canViewOvertime ||
        this.canViewEmployeeRecord
      )
    }
  },
  mounted() {
    fetchCompanyPublic().then((data) => {
      this.company = data
    })

    // Get user data from localStorage
      const storedUserData = localStorage.getItem('user_data')
      if (storedUserData) {
        this.userData = JSON.parse(storedUserData)
        const isAdmin = this.toBool(this.userData?.is_admin)
        this.hasHrmAccess = this.toBool(this.userData?.with_hrm_access) || isAdmin
        this.hasHrpAccess = this.toBool(this.userData?.with_hrp_access) || isAdmin
        this.hasHrtAccess = this.toBool(this.userData?.with_hrt_access) || isAdmin
        this.hasCpmAccess = this.toBool(this.userData?.with_cpm_access) || isAdmin
      this.loadMenuAccess()
      // Load dashboard data after getting user data
      this.loadDashboardData()
      this.loadAnnouncements()
    }

    // Set current date and time
    this.updateDateTime()
    setInterval(this.updateDateTime, 1000)
  },
  methods: {
    hasAnyAdminAccess() {
      return this.hasHrmAccess || this.hasHrpAccess || this.hasHrtAccess || this.hasCpmAccess
    },

    toBool(val) {
      if (typeof val === 'string') {
        const v = val.trim().toLowerCase()
        return v === '1' || v === 'true' || v === 'yes' || v === 'y'
      }
      return !!val
    },

    async loadMenuAccess() {
      this.menuAccessLoaded = false
      this.canViewEmployeeRecord = false
      this.canViewLeave = false
      this.canViewOvertime = false
      this.canViewPayslip = false
      this.canViewDtr = false
      this.canViewSaln = false
      try {
        const ApiService = (await import('../services/api.js')).default
        await ApiService.initSanctum()
        const res = await ApiService.getUserTabAccess()
        const menus = res?.data?.menus
        if (Array.isArray(menus)) {
          // Same normalization as MainLayout.vue `hasMenuAccess` (tab / submodule access)
          const normalize = (s) =>
            String(s ?? '')
              .trim()
              .toLowerCase()
              .replace(/\s+/g, ' ')
          const allowed = new Set()
          for (const m of menus) {
            if (m?.menu_key) allowed.add(normalize(m.menu_key))
            if (m?.menu) allowed.add(normalize(m.menu))
          }
          const has = (label) => allowed.has(normalize(label))

          this.canViewEmployeeRecord =
            has('My Profile & Records') ||
            has('employee_record') ||
            has('Employee Records')
          this.canViewLeave =
            has('Leave & Time Management') ||
            has('leave_management') ||
            has('leave-time')
          this.canViewOvertime =
            has('Overtime Management') ||
            has('overtime_management') ||
            has('overtime-monitoring')
          this.canViewPayslip = has('Payslip') || has('payslip')
          this.canViewDtr =
            has('Daily Time Record') || has('dtr') || has('daily time record')
          this.canViewSaln = has('SALN') || has('saln')
        }
      } catch (e) {
        // On error, keep disabled (hide restricted cards).
        this.canViewEmployeeRecord = false
        this.canViewLeave = false
        this.canViewOvertime = false
        this.canViewPayslip = false
        this.canViewDtr = false
        this.canViewSaln = false
      } finally {
        this.menuAccessLoaded = true
      }
    },

    navigateToModule(module) {
      // This will navigate to sub-pages with iconized sub-modules
              // Navigating to module

      // When you create the sub-pages, you can use:
      this.$router.push(`/${module}`)
    },
    
    // Get user ID for API calls
    getUserId() {
      if (!this.userData || !this.userData.id) {
        console.error('User data not available or missing ID')
        return null
      }
      return this.userData.id
    },
    
    // Navigate to 201 file with user ID
    navigateTo201File() {
      const userId = this.getUserId()
      if (userId) {
        // Navigating to 201 file
        this.$router.push(`/201-file/${userId}`)
      } else {
        console.error('User ID not available')
        // You could show an error message or redirect to login
      }
    },
    
    // Load dashboard data from API
    async loadDashboardData() {
      const userId = this.getUserId()
      if (!userId) {
        console.error('User ID not available - user may not be properly logged in')
        return
      }

      this.loading = true
      try {
        const ApiService = (await import('../services/api.js')).default
        await ApiService.initSanctum()

        const response = await ApiService.getDashboardData(userId)
        
        if (response && response.success) {
          this.dashboardData = response.data
        } else {
          console.error('Failed to load dashboard data:', response?.message || 'Unknown error')
          // Show user-friendly error message
          this.$toast?.error('Failed to load dashboard data. Please try refreshing the page.')
        }
      } catch (error) {
       console.error('Dashboar data loading failed:', error)
       if (error.reponse?.status === 419)
            this.$toast?.error('Session expired. Please login again.')
      } 
    },

    // Example method to call 201 file API with user ID
    async load201FileData() {
      const userId = this.getUserId()
      if (userId) {
        try {
          // Import your API service
          const ApiService = (await import('../services/api.js')).default
          const response = await ApiService.getEmployee201File(userId)
          // 201 File data loaded
          return response
        } catch (error) {
          console.error('Failed to load 201 file data:', error)
        }
      }
    },
    
    updateDateTime() {
      const now = new Date()
      this.currentDate = now.toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      })
      this.currentTime = now.toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit'
      })
    },

    formatDate(value) {
      if (!value) return ''
      try {
        const d = new Date(value)
        return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
      } catch {
        return String(value)
      }
    },

    submitSupportTicket() {
      // Handle support ticket submission
      this.$message.success('Support ticket submitted successfully!')
    },

    async loadAnnouncements() {
      try {
        const ApiService = (await import('../services/api.js')).default
        const response = await ApiService.getAnnouncements()
        
        if (response && response.success) {
          this.announcements = response.data || []
        } else {
          console.error('Failed to load announcements:', response?.message || 'Unknown error')
        }
      } catch (error) {
        console.error('Error loading announcements:', error)
      }
    },

    // Administrative Access Navigation Methods (mirror MainLayout.vue)
    navigateToControlPanel() {
      console.log('=== Control Panel Navigation Started ===')

      let user = this.userData
      if (!user) {
        const storedUser = localStorage.getItem('user_data')
        if (storedUser) {
          try {
            user = JSON.parse(storedUser)
          } catch (error) {
            console.error('Error parsing user data:', error)
          }
        }
      }

      if (user && user.email) {
        const authToken = localStorage.getItem('auth_token')
        const employeeNo = user.employee_no || user.id || 'admin'
        const controlPanelUrl = import.meta.env.VITE_CONTROL_PANEL_URL || 'http://192.168.0.126:8081'
        const url = `${controlPanelUrl}/?employee_no=${employeeNo}&email=${user.email}&auth_token=${encodeURIComponent(authToken || '')}&redirect_from=e_portal`
        console.log('Opening URL with auth:', url)
        window.open(url, '_blank')
      } else {
        console.log('No user data available, opening without auth')
        const controlPanelUrl = import.meta.env.VITE_CONTROL_PANEL_URL || 'http://192.168.0.126:8081'
        window.open(`${controlPanelUrl}/`, '_blank')
      }

      console.log('=== Control Panel Navigation Ended ===')
    },

    navigateToPayrollModule() {
      console.log('=== Payroll Module Navigation Started ===')

      let user = this.userData
      if (!user) {
        const storedUser = localStorage.getItem('user_data')
        if (storedUser) {
          user = JSON.parse(storedUser)
        }
      }

      if (user && user.email) {
        const authToken = localStorage.getItem('auth_token')
        const employeeNo = user.employee_no || user.id || 'admin'
        const payrollModuleUrl = import.meta.env.VITE_PAYROLL_MODULE_URL || 'http://192.168.0.126:8083'
        const url = `${payrollModuleUrl}/?employee_no=${employeeNo}&email=${user.email}&auth_token=${encodeURIComponent(authToken || '')}&redirect_from=e_portal`
        window.open(url, '_blank')
      } else {
        const payrollModuleUrl = import.meta.env.VITE_PAYROLL_MODULE_URL || 'http://192.168.0.126:8083'
        window.open(`${payrollModuleUrl}/`, '_blank')
      }

      console.log('=== Payroll Module Navigation Ended ===')
    },

    navigateToTimekeeping() {
      console.log('=== Timekeeping Navigation Started ===')

      let user = this.userData
      if (!user) {
        const storedUser = localStorage.getItem('user_data')
        if (storedUser) {
          try {
            user = JSON.parse(storedUser)
          } catch (error) {
            console.error('Error parsing user data:', error)
          }
        }
      }

      if (user && user.email) {
        const authToken = localStorage.getItem('auth_token')
        const employeeNo = user.employee_no || user.id || 'admin'
        const timekeepingUrl = import.meta.env.VITE_TIMEKEEPING_MODULE_URL || 'http://192.168.0.126:8085'
        const url = `${timekeepingUrl}/?employee_no=${employeeNo}&email=${user.email}&auth_token=${encodeURIComponent(authToken || '')}&redirect_from=e_portal`
        window.open(url, '_blank')
      } else {
        const timekeepingUrl = import.meta.env.VITE_TIMEKEEPING_MODULE_URL || 'http://192.168.0.126:8085'
        window.open(`${timekeepingUrl}/`, '_blank')
      }

      console.log('=== Timekeeping Navigation Ended ===')
    },

    navigateToHRModule() {
      console.log('=== HR Module Navigation Started ===')

      let user = this.userData
      if (!user) {
        const storedUser = localStorage.getItem('user_data')
        if (storedUser) {
          user = JSON.parse(storedUser)
        }
      }

      if (user && user.email) {
        const authToken = localStorage.getItem('auth_token')
        const employeeNo = user.employee_no || user.id || 'admin'
        const hrModuleUrl = import.meta.env.VITE_HR_MODULE_URL || 'http://192.168.0.126:8082'
        const url = `${hrModuleUrl}/?employee_no=${employeeNo}&email=${user.email}&auth_token=${encodeURIComponent(authToken || '')}&redirect_from=e_portal`
        window.open(url, '_blank')
      } else {
        const hrModuleUrl = import.meta.env.VITE_HR_MODULE_URL || 'http://192.168.0.126:8082'
        window.open(`${hrModuleUrl}/`, '_blank')
      }

      console.log('=== HR Module Navigation Ended ===')
    }
  }
}
</script>

<style scoped>
/* Hide scrollbars and devtools */
html, body, #app {
  overflow: hidden;
}

#__vconsole, #devtools, .devtools, [data-vconsole] {
  display: none !important;
}

/* Custom scrollbar hiding */
::-webkit-scrollbar {
  display: none;
}

* {
  -ms-overflow-style: none;
  scrollbar-width: none;
}

/* Service card styles */
.service-card {
  cursor: pointer;
  transition: all 0.3s ease;
}

.service-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

.service-icon {
  margin-bottom: 1rem;
}

.service-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 0.5rem;
}

.service-description {
  font-size: 0.875rem;
  color: #6b7280;
  line-height: 1.4;
}

/* Space between elements */
.space-y-3 > * + * {
  margin-top: 0.75rem;
}

/* Flex utilities */
.flex {
  display: flex;
}

.items-center {
  align-items: center;
}

.justify-between {
  justify-content: space-between;
}

.space-x-3 > * + * {
  margin-left: 0.75rem;
}

.flex-1 {
  flex: 1 1 0%;
}

/* Text utilities */
.text-sm {
  font-size: 0.875rem;
  line-height: 1.25rem;
}

.text-xs {
  font-size: 0.75rem;
  line-height: 1rem;
}

.text-lg {
  font-size: 1.125rem;
  line-height: 1.75rem;
}

.font-medium {
  font-weight: 500;
}

.font-semibold {
  font-weight: 600;
}

.text-gray-900 {
  color: #111827;
}

.text-gray-700 {
  color: #374151;
}

.text-gray-600 {
  color: #4b5563;
}

.text-gray-500 {
  color: #6b7280;
}

/* Padding utilities */
.p-3 {
  padding: 0.75rem;
}

.mb-4 {
  margin-bottom: 1rem;
}

.mb-8 {
  margin-bottom: 2rem;
}

.mt-4 {
  margin-top: 1rem;
}

/* Border radius */
.rounded-lg {
  border-radius: 0.5rem;
}

/* Hover effects */
.hover\:bg-gray-50:hover {
  background-color: #f9fafb;
}

/* Transitions */
.transition-colors {
  transition-property: color, background-color, border-color, text-decoration-color, fill, stroke;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 150ms;
}

.duration-200 {
  transition-duration: 200ms;
}

/* Cursor */
.cursor-pointer {
  cursor: pointer;
}

/* Width utilities */
.w-full {
  width: 100%;
}
</style> 