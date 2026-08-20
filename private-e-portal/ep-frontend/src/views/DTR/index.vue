<template>
  <MainLayout :breadcrumbs="breadcrumbs">
    <div class="max-w-7xl mx-auto">
      <!-- Header Section -->
      <div class="mb-6">
        <h1 class="text-3xl font-bold text-slate-900 mb-2">Daily Time Record List</h1>
        <p class="text-slate-600">Manage your daily time records and attendance</p>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center items-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
        <span class="ml-3 text-slate-600">Loading DTR data...</span>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-6 mb-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
            </svg>
          </div>
          <div class="ml-3">
            <h3 class="text-sm font-medium text-red-800">Error loading DTR data</h3>
            <p class="mt-1 text-sm text-red-700">{{ error }}</p>
            <button 
              @click="loadDTRData"
              class="mt-2 text-sm text-red-800 hover:text-red-900 underline"
            >
              Try again
            </button>
          </div>
        </div>
      </div>

      <!-- Content -->
      <div v-else>
        <!-- Employee Info Card -->
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 mb-6">
          <div class="p-6">
            <div v-if="employeeInfo.employee_id == 0" class="text-center py-8">
              <div class="mb-4">
                <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
              </div>
              <h5 class="text-lg font-medium text-slate-900 mb-2">Employee Record Not Linked</h5>
              <p class="text-slate-600 mb-4">Your user account is not currently linked to an employee record in the system.</p>
              <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-left">
                <h6 class="text-sm font-medium text-blue-900 mb-2">What this means:</h6>
                <ul class="text-sm text-blue-800 space-y-1">
                  <li>• You cannot view or manage DTR records</li>
                  <li>• You need to contact your system administrator</li>
                  <li>• Your user account needs to be linked to an employee record</li>
                </ul>
              </div>
              <div class="mt-4 text-xs text-slate-500">
                <p>User ID: {{ currentUserId }}</p>
                <p>Employee No: {{ currentEmployeeNo || 'Not set' }}</p>
                <p>User Name: {{ currentUserData?.name || 'Not available' }}</p>
                <p>User Email: {{ currentUserData?.email || 'Not available' }}</p>
                <p>Check browser console for detailed debug information.</p>
              </div>
            </div>
            
            <div v-else class="grid grid-cols-1 md:grid-cols-3 gap-6">
                             <!-- Employee Photo and Name -->
               <div class="text-center">
                 <div class="relative w-24 h-24 mx-auto mb-4">
                   <!-- Show actual photo if available -->
                    <img 
                      v-if="employeeInfo.photo"
                      :src="getEmployeePhoto(employeeInfo.photo)"
                      @error="handlePhotoError"
                     alt="Employee profile picture"
                     class="w-24 h-24 rounded-full object-cover border-2 border-slate-200"
                   >
                   
                   <!-- Show default avatar with initials when no photo -->
                   <div v-else class="w-24 h-24 rounded-full bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center border-2 border-slate-200 shadow-sm">
                     <span class="text-white font-bold text-xl">{{ getEmployeeInitials(employeeInfo.name) }}</span>
                   </div>
                 </div>
                 <h5 class="text-lg font-semibold text-slate-900">{{ employeeInfo.name }}</h5>
               </div>

              <!-- Employee Details -->
              <div class="space-y-4">
                <div>
                  <label class="block text-sm font-medium text-slate-600 mb-1">Employee No.</label>
                  <h5 class="text-lg font-semibold text-slate-900">{{ employeeInfo.employee_no }}</h5>
                </div>
                <div>
                  <label class="block text-sm font-medium text-slate-600 mb-1">Employment Type</label>
                  <h5 class="text-lg font-semibold text-slate-900">{{ employeeInfo.employment_type }}</h5>
                </div>
              </div>

              <div class="space-y-4">
                <div>
                  <label class="block text-sm font-medium text-slate-600 mb-1">Department</label>
                  <h5 class="text-lg font-semibold text-slate-900">{{ employeeInfo.department }}</h5>
                </div>
                <div>
                  <label class="block text-sm font-medium text-slate-600 mb-1">Position</label>
                  <h5 class="text-lg font-semibold text-slate-900">{{ employeeInfo.position }}</h5>
                </div>
              </div>
            </div>

            <!-- Action Button -->
            <div v-if="employeeInfo.employee_id != 0" class="mt-6">
              <el-button type="primary" @click="viewTimeLogs">View Time Logs</el-button>
            </div>
          </div>
        </div>

        <!-- DTR List -->
        <div v-if="employeeInfo.employee_id != 0" class="bg-white rounded-lg shadow-sm border border-slate-200">
          <div class="p-6">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg font-semibold text-slate-900">Processed Attendance</h3>
              <div class="flex items-center gap-2">
                <el-input v-model="search" placeholder="Search by cut-off, interval, or date" size="small" style="width: 280px" />
              </div>
            </div>
            
            <!-- Empty State -->
            <div v-if="dtrRecords.length === 0" class="text-center py-8">
              <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
              </svg>
              <h3 class="mt-2 text-sm font-medium text-slate-900">No DTR records found</h3>
              <p class="mt-1 text-sm text-slate-500">No processed attendance records available for this employee.</p>
            </div>
            
            <!-- DTR Table -->
            <DTRTable 
              v-else
              :dtr-records="filteredDtr"
              @view-dtr="viewDTR"
              @print-dtr="showPrintPreview"
            />

            <div v-if="showPrint" class="mt-6">
              <el-card shadow="never">
                <div class="flex items-center justify-between mb-3">
                  <div class="text-base font-semibold">DTR Print Preview</div>
                  <div class="flex items-center gap-2">
                    <el-button size="small" type="primary" @click="downloadDTR">Download</el-button>
                    <el-button size="small" @click="closePrint"><el-icon><Close /></el-icon></el-button>
                  </div>
                </div>
                <div v-if="previewUrl" class="border rounded overflow-hidden" style="height: 680px; max-width: 100%;">
                  <iframe :src="previewUrl" class="w-full h-full" style="max-width: 100%;"></iframe>
                </div>
                <div v-else class="text-center text-gray-500 py-10">Loading preview...</div>
              </el-card>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="mt-6 text-center">
          <p class="text-sm text-slate-600">List of all Daily Time Record by name.</p>
        </div>
      </div>
    </div>
  </MainLayout>
</template>

<script>
import MainLayout from '../../layout/MainLayout.vue'
import DTRTable from '../../components/DTR/DTRTable.vue'
import { dtrApiService } from '../../services/apiService.js'

export default {
  name: 'DTRListView',
  components: { MainLayout, DTRTable },
  data() {
    return {
      breadcrumbs: [
        { name: 'Dashboard', path: '/dashboard' },
        { name: 'Time and Attendance', path: '/time-attendance' },
        { name: 'DTR List', path: '/dtr' }
      ],
      loading: true,
      error: null,
      employeeInfo: {
        employee_id: 0,
        name: '',
        employee_no: '',
        employment_type: '',
        department: '',
        position: '',
        photo: null
      },
      dtrRecords: [],
      search: '',
      showPrint: false,
      previewUrl: null,
      previewData: null
    }
  },
  computed: {
    currentUserId() {
      // Get user ID from localStorage user_data first, then route params, then fallback
      const userData = this.getFromLocalStorage('user_data')
      if (userData) {
        try {
          const parsedUserData = JSON.parse(userData)
          if (parsedUserData.id) {
    
            return parsedUserData.id
          }
        } catch (error) {
        }
      }
      
      // Fallback to route params or default
      const routeUserId = this.$route.params.userId
      if (routeUserId) {

        return routeUserId
      }
      
      return 1
    },
    currentEmployeeNo() {
      // Get employee number from user_data first
      const userData = this.getFromLocalStorage('user_data')
      if (userData) {
        try {
          const parsedUserData = JSON.parse(userData)
          if (parsedUserData.employee_no) {
    
            return parsedUserData.employee_no
          }
        } catch (error) {
          console.warn('Error parsing user_data for employee_no:', error)
        }
      }
      
      // Fallback to direct localStorage access
      return this.getFromLocalStorage('employee_no') || null
    },
    currentUserData() {
      const userData = this.getFromLocalStorage('user_data')
      if (userData) {
        try {
          return JSON.parse(userData)
        } catch (error) {
          return null
        }
      }
      return null
    },
    filteredDtr() {
      if (!this.search) return this.dtrRecords
      const q = this.search.toLowerCase().trim()
      if (!q) return this.dtrRecords
      
      return this.dtrRecords.filter(r => {
        // Search in cut_off
        const cutOff = String(r.cut_off || '').toLowerCase()
        if (cutOff.includes(q)) return true
        
        // Search in payroll interval / month label
        const payrollInterval = String(r.payroll_interval || '').toLowerCase()
        if (payrollInterval.includes(q)) return true
        const monthLabel = this.formatPayrollIntervalLabel(r).toLowerCase()
        if (monthLabel.includes(q)) return true
        
        // Search in attendance_start_date (formatted)
        if (r.attendance_start_date) {
          const startDate = this.formatDateForSearch(r.attendance_start_date)
          if (startDate.includes(q)) return true
        }
        
        // Search in attendance_end_date (formatted)
        if (r.attendance_end_date) {
          const endDate = this.formatDateForSearch(r.attendance_end_date)
          if (endDate.includes(q)) return true
        }
        
        return false
      })
    }
  },
  async mounted() {
    await this.loadDTRData()
  },
  methods: {
    getFromLocalStorage(key) {
      try {
        return localStorage.getItem(key)
      } catch (error) {
        return null
      }
    },
    
    async loadDTRData() {
      try {
        this.loading = true
        this.error = null
        
        // Get current user ID and employee number from computed properties
        const userId = this.currentUserId
        const employeeNo = this.currentEmployeeNo
        

        
        // Use smart method that prioritizes employee number
        const response = await dtrApiService.getDTRListSmart(userId, employeeNo)
        
        if (response.success) {
          // Extract employee info and DTR records from the response
          if (response.data && response.data.employee_info) {
            // Employee info is available
            this.employeeInfo = {
              employee_id: response.data.employee_info.employee_id || 0,
              name: response.data.employee_info.name || '',
              employee_no: response.data.employee_info.employee_no || '',
              employment_type: response.data.employee_info.employment_type || '',
              department: response.data.employee_info.department || '',
              position: response.data.employee_info.position || '',
              photo: response.data.employee_info.photo || null
            }
            this.dtrRecords = response.data.dtr_records || []
            
            // Debug info available if needed
          } else {
            // No employee info available (user not linked to employee)
            this.employeeInfo = {
              employee_id: 0,
              name: '',
              employee_no: '',
              employment_type: '',
              department: '',
              position: '',
              photo: null
            }
            this.dtrRecords = []
            
            // Debug info available for troubleshooting
          }
        } else {
          throw new Error(response.message || 'Failed to load DTR data')
        }
      } catch (error) {
        console.error('Error loading DTR data:', error)
        this.error = error.response?.data?.message || error.message || 'Failed to load DTR data'
        
        // Show toast notification
        if (this.$toast) {
          this.$toast.error(this.error)
        }
      } finally {
        this.loading = false
      }
    },
    
    viewTimeLogs() {
      if (this.employeeInfo.employee_id === 0) return
      // Navigate to Employee Daily Time Record (detail) for the latest period available
      const latest = Array.isArray(this.dtrRecords) && this.dtrRecords.length > 0 ? this.dtrRecords[0] : null
      if (latest && latest.payroll_period_id) {
        this.$router.push(`/dtr/view/${this.employeeInfo.employee_id}/${latest.payroll_period_id}`)
      } else {
        this.$toast && this.$toast.info('No processed attendance period to view yet.')
      }
    },
    
    viewDTR(dtr) {
      if (dtr.employee_id && dtr.payroll_period_id) {
        this.$router.push(`/dtr/view/${dtr.employee_id}/${dtr.payroll_period_id}`)
      }
    },
    
    async showPrintPreview(dtr) {
      if (!dtr.employee_id || !dtr.payroll_period_id) return
      try {
        this.showPrint = true
        this.previewData = dtr // Store DTR info for download
        this.previewUrl = null // Clear previous preview
        
        // Fetch PDF blob for preview
        const blob = await dtrApiService.printDTR(dtr.employee_id, dtr.payroll_period_id)
        this.previewUrl = URL.createObjectURL(blob)
      } catch (e) {
        console.error('DTR print preview failed:', e)
        this.$toast && this.$toast.error('Failed to load DTR preview')
        this.showPrint = false
        this.previewUrl = null
      }
    },
    closePrint() {
      this.showPrint = false
      if (this.previewUrl) {
        URL.revokeObjectURL(this.previewUrl)
        this.previewUrl = null
      }
      this.previewData = null
    },
    async downloadDTR() {
      if (!this.previewData || !this.previewData.employee_id || !this.previewData.payroll_period_id) {
        this.$toast && this.$toast.error('DTR data not available for download')
        return
      }
      
      try {
        // If we already have the preview URL, use it for download
        if (this.previewUrl) {
          const a = document.createElement('a')
          a.href = this.previewUrl
          a.download = `dtr_${this.previewData.employee_id}_${this.previewData.payroll_period_id}_${new Date().toISOString().split('T')[0]}.pdf`
          document.body.appendChild(a)
          a.click()
          document.body.removeChild(a)
          this.$toast && this.$toast.success('DTR PDF downloaded successfully')
        } else {
          // Otherwise fetch it
          const blob = await dtrApiService.printDTR(
            this.previewData.employee_id,
            this.previewData.payroll_period_id
          )
          const url = URL.createObjectURL(blob)
          const a = document.createElement('a')
          a.href = url
          a.download = `dtr_${this.previewData.employee_id}_${this.previewData.payroll_period_id}_${new Date().toISOString().split('T')[0]}.pdf`
          document.body.appendChild(a)
          a.click()
          document.body.removeChild(a)
          URL.revokeObjectURL(url)
          this.$toast && this.$toast.success('DTR PDF downloaded successfully')
        }
      } catch (error) {
        console.error('Error downloading DTR PDF:', error)
        this.$toast && this.$toast.error('Failed to download DTR PDF')
      }
    },

    getEmployeePhoto(photo) {
      if (photo) {
        if (typeof photo === 'string') {
          if (photo.startsWith('data:image/') || photo.startsWith('http://') || photo.startsWith('https://') || photo.startsWith('blob:')) {
            return photo
          }
          return `data:image/jpeg;base64,${photo}`
        }
      }
      return '/dist/img/employee_profile.png'
    },

    handlePhotoError(event) {
      event.target.src = '/dist/img/employee_profile.png'
    },

    getEmployeeInitials(name) {
      if (!name) return '?'
      
      const names = name.trim().split(' ')
      if (names.length === 1) {
        return names[0].charAt(0).toUpperCase()
      }
      
      return (names[0].charAt(0) + names[names.length - 1].charAt(0)).toUpperCase()
    },
    
    formatPayrollIntervalLabel(row) {
      const interval = String(row?.payroll_interval || '').trim()
      const dateSource = row?.attendance_start_date || row?.attendance_end_date

      if (/^monthly$/i.test(interval) && dateSource) {
        try {
          const date = new Date(dateSource)
          if (!isNaN(date.getTime())) {
            return date.toLocaleDateString('en-US', { month: 'long', year: 'numeric' })
          }
        } catch {}
      }

      return interval
    },

    formatDateForSearch(dateString) {
      if (!dateString) return ''
      
      try {
        const date = new Date(dateString)
        if (isNaN(date.getTime())) return ''
        
        // Format as MM/DD/YYYY for search
        const month = String(date.getMonth() + 1).padStart(2, '0')
        const day = String(date.getDate()).padStart(2, '0')
        const year = date.getFullYear()
        
        // Return multiple formats for better search
        return `${month}/${day}/${year} ${month}-${day}-${year} ${year}-${month}-${day}`.toLowerCase()
      } catch (error) {
        return ''
      }
    }
  }
}
</script> 