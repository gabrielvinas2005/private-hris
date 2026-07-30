<template>
  <MainLayout :breadcrumbs="breadcrumbs">
    <div class="w-full">
      <!-- Page Header -->
      <el-page-header @back="goBack" class="mb-6">
        <template #content>
          <div class="flex items-center">
            <el-icon class="mr-2"><HomeFilled /></el-icon>
            <span class="text-lg font-medium">Work From Home Attendance</span>
          </div>
        </template>
      </el-page-header>

      <!-- Loading State -->
      <el-skeleton v-if="loading" :rows="8" animated />

      <!-- Error State -->
      <el-result
        v-else-if="error"
        icon="error"
        title="Error Loading Information"
        :sub-title="error"
      >
        <template #extra>
          <el-button type="primary" @click="loadEmployeeInfo">Try Again</el-button>
        </template>
      </el-result>

      <!-- Main Content -->
      <div v-else>
        <!-- WFH Time Clock -->
        <WFHTimeClock 
          :employee-id="employeeInfo.employee_id"
          @status-updated="handleStatusUpdate"
        />
      </div>
    </div>
  </MainLayout>
</template>

<script>
import MainLayout from '../../layout/MainLayout.vue'
import WFHTimeClock from '../../components/WFHAttendance/WFHTimeClock.vue'
import { wfhAttendanceApiService } from '../../services/apiService.js'
import { HomeFilled } from '@element-plus/icons-vue'

export default {
  name: 'WFHAttendanceView',
  components: { 
    MainLayout, 
    WFHTimeClock,
    HomeFilled
  },
  data() {
    return {
      breadcrumbs: [
        { name: 'Dashboard', path: '/dashboard' },
        { name: 'Leave & Time Management', path: '/leave-time' },
        { name: 'WFH Attendance', path: '/wfh-attendance' }
      ],
      loading: true,
      error: null,
      employeeInfo: {
        employee_id: null,
        name: '',
        employee_no: '',
        employment_type: '',
        department: '',
        position: '',
        photo: null
      },
      currentDate: ''
    }
  },
  async mounted() {
    this.updateCurrentDate()
    setInterval(this.updateCurrentDate, 1000)
    await this.loadEmployeeInfo()
  },
  methods: {
    getCurrentUserId() {
      // Try to get user ID from localStorage first
      const raw = localStorage.getItem('user_data')
      if (raw) {
        try { 
          const userData = JSON.parse(raw)
          if (userData && userData.id) {
            return userData.id
          }
        } catch (error) {
        }
      }
      
      // Fallback: try to get from route params
      const routeUserId = this.$route.params.userId
      if (routeUserId) {
        return routeUserId
      }
      
      // Final fallback
      return null
    },

    updateCurrentDate() {
      const now = new Date()
      this.currentDate = now.toLocaleDateString('en-US', { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
      })
    },

    async loadEmployeeInfo() {
      try {
        this.loading = true
        this.error = null
        
        const userId = this.getCurrentUserId()
        
        
        if (!userId) {
          throw new Error('User ID is required - please log in again')
        }
        
        const response = await wfhAttendanceApiService.getEmployeeInfo(userId)
        
        if (response.success) {
          this.employeeInfo = response.data.employee_info
        } else {
          throw new Error(response.message || 'Failed to load employee information')
        }
      } catch (error) {
        console.error('Error loading employee info:', error)
        this.error = error.response?.data?.message || error.message || 'Failed to load employee information'
        
        if (this.$toast) {
          this.$toast.error(this.error)
        }
      } finally {
        this.loading = false
      }
    },

    handleStatusUpdate() {
      // Handle status updates from the time clock component
    },

    goBack() {
      this.$router.push('/dashboard')
    }
  }
}
</script>
