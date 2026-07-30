<template>
  <MainLayout :breadcrumbs="breadcrumbs">
    <div class="max-w-7xl mx-auto">
      <!-- Header Section -->
      <div class="mb-6">
        <h1 class="text-3xl font-bold text-slate-900 mb-2">Employee Daily Time Record</h1>
        <p class="text-slate-600">Manage your time logs and attendance</p>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center items-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
        <span class="ml-3 text-slate-600">Loading employee information...</span>
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
            <h3 class="text-sm font-medium text-red-800">Error loading employee information</h3>
            <p class="mt-1 text-sm text-red-700">{{ error }}</p>
            <button 
              @click="loadEmployeeInfo"
              class="mt-2 text-sm text-red-800 hover:text-red-900 underline"
            >
              Try again
            </button>
          </div>
        </div>
      </div>

      <!-- Content -->
      <div v-else>
        <!-- DTR Information Card -->
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 mb-6">
          <div class="p-6">
            <h3 class="text-lg font-semibold text-slate-900 mb-4">DTR Information</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
              <!-- Employee Photo and Name -->
              <div class="text-center">
                <img 
                  :src="employeePhotoSrc"
                  @error="handleImageError"
                  alt="Employee profile picture"
                  class="w-24 h-24 rounded-full mx-auto mb-4 object-cover"
                >
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

            <!-- Date Range Selection -->
            <div class="mt-6 p-4 bg-slate-50 rounded-lg">
              <div class="flex items-center gap-3 flex-wrap">
                <div>
                  <label class="block text-sm font-medium text-slate-700 mb-2">Attendance From</label>
                  <el-date-picker
                    v-model="dateRange.attendance_from"
                    type="date"
                    placeholder="Select start date"
                    format="MM/DD/YYYY"
                    value-format="YYYY-MM-DD"
                    style="width: 220px"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-slate-700 mb-2">Attendance To</label>
                  <el-date-picker
                    v-model="dateRange.attendance_to"
                    type="date"
                    placeholder="Select end date"
                    format="MM/DD/YYYY"
                    value-format="YYYY-MM-DD"
                    style="width: 220px"
                  />
                </div>
                <div class="ml-auto">
                  <el-button type="primary" :loading="loadingLogs" @click="viewLogs">{{ loadingLogs ? 'Loading...' : 'View Logs' }}</el-button>
                </div>
              </div>
              
              <!-- Weekend Exclusion Checkbox -->
              <div class="mt-4">
                <el-checkbox 
                  v-model="excludeWeekends" 
                  class="text-slate-700"
                >
                  <span class="text-sm font-medium">Exclude weekends from attachment requirement</span>
                  <div class="text-xs text-slate-500 mt-1">Check this to skip file attachment validation for Saturday and Sunday</div>
                </el-checkbox>
              </div>
              
              <el-alert class="mt-3" type="info" :closable="false" show-icon title="Please attach file proving your time logs to include on your request before saving." />
            </div>
          </div>
        </div>

        <!-- Time Logs Form -->
        <div v-if="timeLogs.length > 0">
          <div class="bg-white rounded-lg shadow-sm border border-slate-200">
            <div class="p-6">
              <h3 class="text-lg font-semibold text-slate-900 mb-4">Time Logs</h3>
              
              <TimeLogsTable 
                :time-logs="timeLogs"
                @update-log="updateTimeLog"
              />
            </div>
            
            <!-- <div class="p-6 border-t border-slate-200"> -->
            <div class="p-6 border-t border-slate-200">
              <el-button 
                type="primary"
                :loading="isSubmitting"
                @click="saveDTRLogs"
                class="flex items-center gap-2"
              >
                Submit DTR Application
              </el-button>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-else-if="!loadingLogs && timeLogs.length === 0" class="bg-white rounded-lg shadow-sm border border-slate-200">
          <div class="p-6 text-center">
            <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-slate-900">No time logs found</h3>
            <p class="mt-1 text-sm text-slate-500">Select a date range and click "View Logs" to see your time records.</p>
          </div>
        </div>

        <!-- Footer -->
        <div class="mt-6 text-center">
          <p class="text-sm text-slate-600">Display Daily Time data.</p>
        </div>
      </div>
    </div>
  </MainLayout>
</template>

<script>
import MainLayout from '../../layout/MainLayout.vue'
import TimeLogsTable from '../../components/DTR/TimeLogsTable.vue'
import { dtrApiService } from '../../services/apiService.js'
import defaultProfileImage from '../../assets/img/employee_profile.png'

export default {
  name: 'DTRTimeLogsView',
  components: { MainLayout, TimeLogsTable },
  data() {
    return {
      breadcrumbs: [
        { name: 'Dashboard', path: '/dashboard' },
        { name: 'Leave & Time Management', path: '/leave-time' },
        { name: 'DTR Application', path: '/dtr/application' },
        { name: 'New Application', path: '/dtr/time-logs' }
      ],
      loading: true,
      loadingLogs: false,
      error: null,
      employeeInfo: {
        name: '',
        employee_no: '',
        employment_type: '',
        department: '',
        position: '',
        photo: null
      },
      dateRange: {
        attendance_from: '',
        attendance_to: ''
      },
      timeLogs: [],
      isSubmitting: false,
      excludeWeekends: false,
      defaultProfileImage
    }
  },
  async mounted() {
    await this.loadEmployeeInfo()
  },
  computed: {
    employeePhotoSrc() {
      return this.employeeInfo.photo
        ? `data:image/jpeg;base64,${this.employeeInfo.photo}`
        : this.defaultProfileImage
    }
  },
  methods: {
    handleImageError(event) {
      event.target.src = this.defaultProfileImage
    },
    // Utility method to check if a date is weekend (Saturday or Sunday)
    isWeekend(dateString) {
      const date = new Date(dateString)
      const dayOfWeek = date.getDay()
      return dayOfWeek === 0 || dayOfWeek === 6 // Sunday = 0, Saturday = 6
    },
    
    // Get filtered time logs based on weekend exclusion
    getFilteredTimeLogs() {
      if (this.excludeWeekends) {
        return this.timeLogs.filter(log => !this.isWeekend(log.date))
      }
      return this.timeLogs
    },

    async loadEmployeeInfo() {
      try {
        this.loading = true
        this.error = null
        
        const employeeId = this.$route.params.employeeId
        
        if (!employeeId) {
          throw new Error('Employee ID is required')
        }
        
        const response = await dtrApiService.getEmployeeLogs(employeeId)
        
        if (response.success) {
          const data = response.data
          if (data && data.length > 0) {
            this.employeeInfo = {
              name: data[0].name || '',
              employee_no: data[0].employee_no || '',
              employment_type: data[0].employment_type || '',
              department: data[0].department || '',
              position: data[0].position || '',
              photo: data[0].photo || null
            }
          }
        } else {
          throw new Error(response.message || 'Failed to load employee information')
        }
      } catch (error) {
        console.error('Error loading employee info:', error)
        this.error = error.response?.data?.message || error.message || 'Failed to load employee information'
        
        // Show toast notification
        if (this.$toast) {
          this.$toast.error(this.error)
        }
      } finally {
        this.loading = false
      }
    },
    
    async viewLogs() {
      if (!this.dateRange.attendance_from || !this.dateRange.attendance_to) {
        if (this.$toast) {
          this.$toast.error('Please select both start and end dates')
        }
        return
      }

      try {
        this.loadingLogs = true
        
        const response = await dtrApiService.getTimeLogs(
          this.$route.params.employeeId,
          this.dateRange.attendance_from,
          this.dateRange.attendance_to
        )
        
        // Parse the JSON response if it's a string
        if (typeof response === 'string') {
          this.timeLogs = JSON.parse(response)
        } else {
          this.timeLogs = response || []
        }
      } catch (error) {
        console.error('Error loading time logs:', error)
        if (this.$toast) {
          this.$toast.error('Failed to load time logs')
        }
        this.timeLogs = []
      } finally {
        this.loadingLogs = false
      }
    },
    
    updateTimeLog(updatedLog) {
      const index = this.timeLogs.findIndex(log => log.id === updatedLog.id)
      if (index !== -1) {
        this.timeLogs[index] = { ...this.timeLogs[index], ...updatedLog }
      }
    },
    
    async saveDTRLogs() {
      this.isSubmitting = true
      
      try {
        // Create FormData for file uploads
        const formData = new FormData()
        
        // Add time logs data
        this.timeLogs.forEach((log, index) => {
          formData.append(`date[${index}]`, log.date)
          formData.append(`id[${index}]`, log.id)
          formData.append(`am_in[${index}]`, log.am_in || '')
          formData.append(`am_out[${index}]`, log.am_out || '')
          formData.append(`break_in[${index}]`, log.break_in || '')
          formData.append(`break_out[${index}]`, log.break_out || '')
          formData.append(`pm_in[${index}]`, log.pm_in || '')
          formData.append(`pm_out[${index}]`, log.pm_out || '')
          formData.append(`payroll_period_id[${index}]`, log.payroll_period_id || 0)
        })

        // Validate and add file attachments from model (not DOM)
        // Use filtered time logs based on weekend exclusion setting
        const logsToValidate = this.getFilteredTimeLogs()
        const hasAttachment = logsToValidate.some(l => l.attachment && l.attachment.file)
        if (!hasAttachment) {
          const message = this.excludeWeekends 
            ? 'Please attach at least one file for the weekday(s) to be reviewed (weekends are excluded).'
            : 'Please attach at least one file for the date(s) to be reviewed.'
          this.$toast && this.$toast.error(message)
          this.isSubmitting = false
          return
        }
        this.timeLogs.forEach((log, index) => {
          if (log.attachment && log.attachment.file) {
            formData.append(`attachment[${index}]`, log.attachment.file)
          }
        })
        
        const response = await dtrApiService.storeDTRLogs(this.$route.params.employeeId, formData)
        
        if (response.success) {
          if (this.$toast) {
            this.$toast.success(response.message || 'DTR application submitted successfully')
          }
          this.$router.push('/dtr/application')
        } else {
          throw new Error(response.message || 'Failed to save DTR logs')
        }
      } catch (error) {
        console.error('Error saving DTR logs:', error)
        if (this.$toast) {
          this.$toast.error(error.response?.data?.message || error.message || 'Failed to save DTR logs')
        }
      } finally {
        this.isSubmitting = false
      }
    }
  }
}
</script>

<style scoped>
/* Highlight weekend rows coming from TimeLogsTable via row-class-name='is-weekend' */
:deep(.el-table__row.is-weekend) > td {
  background-color: #fff7ed; /* amber-50 */
}
</style>