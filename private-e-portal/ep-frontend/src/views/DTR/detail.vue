<template>
  <MainLayout :breadcrumbs="breadcrumbs">
    <div class="max-w-7xl mx-auto">
      <!-- Header Section -->
      <div class="mb-6">
        <h1 class="text-3xl font-bold text-slate-900 mb-2">Employee Daily Time Record</h1>
        <p class="text-slate-600">View detailed time records and attendance</p>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center items-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
        <span class="ml-3 text-slate-600">Loading DTR details...</span>
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
            <h3 class="text-sm font-medium text-red-800">Error loading DTR details</h3>
            <p class="mt-1 text-sm text-red-700">{{ error }}</p>
            <button 
              @click="loadDTRDetail"
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
        <el-card class="mb-6" shadow="never">
          <template #header>
            <div class="flex items-center justify-between">
              <h3 class="text-lg font-semibold text-slate-900">DTR Information</h3>
              <div class="text-sm text-slate-600">Period: {{ formatDate(periodInfo.attendance_start_date) }} - {{ formatDate(periodInfo.attendance_end_date) }}</div>
            </div>
          </template>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
              <!-- Employee Photo and Name -->
              <div class="text-center">
                <div class="relative w-24 h-24 mx-auto mb-4">
                  <!-- Show actual photo if available -->
                  <img 
                    v-if="employeeInfo.photo"
                    :src="`data:image/jpeg;base64,${employeeInfo.photo}`"
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
              <div>
                <el-descriptions :column="1" size="small" border>
                  <el-descriptions-item label="Employee No.">{{ employeeInfo.employee_no }}</el-descriptions-item>
                  <el-descriptions-item label="Employment Type">{{ employeeInfo.employment_type }}</el-descriptions-item>
                </el-descriptions>
              </div>

              <div>
                <el-descriptions :column="1" size="small" border>
                  <el-descriptions-item label="Department">{{ employeeInfo.department }}</el-descriptions-item>
                  <el-descriptions-item label="Position">{{ employeeInfo.position }}</el-descriptions-item>
                </el-descriptions>
              </div>
            </div>
            <div class="mt-6 p-4 bg-slate-50 rounded-lg">
              <div class="flex items-center gap-3 flex-wrap">
                <el-date-picker
                  v-model="fromDate"
                  type="date"
                  placeholder="Attendance From"
                  format="MM/DD/YYYY"
                  value-format="YYYY-MM-DD"
                  style="width: 240px"
                />
                <el-date-picker
                  v-model="toDate"
                  type="date"
                  placeholder="Attendance To"
                  format="MM/DD/YYYY"
                  value-format="YYYY-MM-DD"
                  style="width: 240px"
                />
                <div class="ml-auto">
                  <el-button type="primary" @click="openLogs">View Logs</el-button>
                </div>
              </div>
              <el-alert
                class="mt-3"
                type="info"
                :closable="false"
                show-icon
                title="Please attach file proving your time logs to include on your request before saving."
              />
            </div>
        </el-card>

        <!-- DTR Records Table -->
        <el-card shadow="never">
          <template #header>
            <h3 class="text-lg font-semibold text-slate-900">Daily Time Records</h3>
          </template>
            
            <!-- Empty State -->
            <div v-if="dtrRecords.length === 0" class="text-center py-8">
              <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
              </svg>
              <h3 class="mt-2 text-sm font-medium text-slate-900">No DTR records found</h3>
              <p class="mt-1 text-sm text-slate-500">No time records available for this period.</p>
            </div>
            
            <!-- DTR Detail Table -->
            <DTRDetailTable v-else :dtr-records="dtrRecords" :totals="totals" />
        </el-card>

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
import DTRDetailTable from '../../components/DTR/DTRDetailTable.vue'
import { dtrApiService } from '../../services/apiService.js'

export default {
  name: 'DTRDetailView',
  components: { MainLayout, DTRDetailTable },
  data() {
    return {
      breadcrumbs: [
        { name: 'Dashboard', path: '/dashboard' },
        { name: 'Leave & Time Management', path: '/leave-time' },
        { name: 'DTR List', path: '/dtr' },
        { name: 'Employee Daily Time Record', path: '/dtr/detail' }
      ],
      loading: true,
      error: null,
      employeeInfo: {
        name: '',
        employee_no: '',
        employment_type: '',
        department: '',
        position: '',
        photo: null
      },
      periodInfo: {
        attendance_start_date: '',
        attendance_end_date: ''
      },
      dtrRecords: [],
      totals: {
        ot: 0,
        late: 0,
        undertime: 0,
        leave: 0,
        absent: 0,
        work_hours: 0
      },
      fromDate: '',
      toDate: ''
    }
  },
  async mounted() {
    await this.loadDTRDetail()
  },
  methods: {
    openLogs() {
      if (this.$route.params?.employeeId) {
        this.$router.push(`/dtr/time-logs/${this.$route.params.employeeId}`)
      }
    },
    async loadDTRDetail() {
      try {
        this.loading = true
        this.error = null
        
        const employeeId = this.$route.params.employeeId
        const payrollPeriodId = this.$route.params.dtrId
        
        if (!employeeId || !payrollPeriodId) {
          throw new Error('Employee ID and Payroll Period ID are required')
        }
        
        const response = await dtrApiService.getDTRDetail(employeeId, payrollPeriodId)
        
        if (response.success) {
          const data = response.data
          
          // Extract employee info from the first record
          if (data.daily_time_records && data.daily_time_records.length > 0) {
            const firstRecord = data.daily_time_records[0]
            this.employeeInfo = {
              name: firstRecord.name || '',
              employee_no: firstRecord.employee_no || '',
              employment_type: firstRecord.employment_type || '',
              department: firstRecord.department || '',
              position: firstRecord.position || '',
              photo: firstRecord.photo || null
            }
            
            this.periodInfo = {
              attendance_start_date: firstRecord.attendance_start_date || '',
              attendance_end_date: firstRecord.attendance_end_date || ''
            }
          }
          
          this.dtrRecords = data.daily_time_records || []
          this.totals = data.totals && data.totals.length > 0 ? data.totals[0] : {
            ot: 0,
            late: 0,
            undertime: 0,
            leave: 0,
            absent: 0,
            work_hours: 0
          }
        } else {
          throw new Error(response.message || 'Failed to load DTR details')
        }
      } catch (error) {
        console.error('Error loading DTR detail:', error)
        this.error = error.response?.data?.message || error.message || 'Failed to load DTR details'
        
        // Show toast notification
        if (this.$toast) {
          this.$toast.error(this.error)
        }
      } finally {
        this.loading = false
      }
    },
    
    formatDate(dateString) {
      if (!dateString) return 'N/A'
      
      try {
        const date = new Date(dateString)
        
        // Check if date is valid
        if (isNaN(date.getTime())) {
          return 'N/A'
        }
        
        return date.toLocaleDateString('en-US', {
          month: '2-digit',
          day: '2-digit',
          year: 'numeric'
        })
      } catch (error) {
        console.error('Error formatting date:', error)
        return 'N/A'
      }
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
    }
  }
}
</script> 