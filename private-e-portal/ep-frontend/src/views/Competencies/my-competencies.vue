<template>
  <MainLayout :breadcrumbs="breadcrumbs">
    <div class="max-w-7xl mx-auto">
      <!-- Header Section -->
      <div class="mb-6">
        <h1 class="text-3xl font-bold text-slate-900 mb-2">My Competencies</h1>
        <p class="text-slate-600">View and manage your competency assessments</p>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center items-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>

      <!-- Content -->
      <div v-else-if="employee && competencies.length > 0">
        <EmployeeCompetencyDetails 
          :employee="employee"
          :competencies="competencies"
          :subCompetencies="subCompetencies"
        />
      </div>

      <!-- No Data State -->
      <div v-else class="bg-white rounded-lg shadow-sm border border-slate-200 p-8">
        <div class="text-center">
          <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          <h3 class="mt-2 text-sm font-medium text-slate-900">No Competencies Found</h3>
          <p class="mt-1 text-sm text-slate-500">
            Your competencies have not been set up yet. Please contact your administrator.
          </p>
        </div>
      </div>
    </div>
  </MainLayout>
</template>

<script>
import MainLayout from '../../layout/MainLayout.vue'
import EmployeeCompetencyDetails from '../../components/Competencies/EmployeeCompetencyDetails.vue'
import { mockApiService } from '../../services/mockData.js'

export default {
  name: 'MyCompetenciesView',
  components: { MainLayout, EmployeeCompetencyDetails },
  data() {
    return {
      breadcrumbs: [
        { name: 'Dashboard', path: '/dashboard' },
        { name: 'My Profile & Records', path: '/profile-records' },
        { name: 'My Competencies', path: '/competencies/my-competencies' }
      ],
      loading: true,
      employee: null,
      competencies: [],
      subCompetencies: []
    }
  },
  async mounted() {
    await this.loadMyCompetencies()
  },
  methods: {
    async loadMyCompetencies() {
      try {
        this.loading = true
        // For demo purposes, we'll use employee ID 1 (current user)
        const employeeId = 1
        const response = await mockApiService.getEmployeeCompetency(employeeId)
        
        this.employee = response.data.employee
        this.competencies = response.data.competencies
        this.subCompetencies = response.data.subCompetencies
      } catch (error) {
        console.error('Error loading competencies:', error)
        this.$toast.error('Failed to load your competencies')
      } finally {
        this.loading = false
      }
    }
  }
}
</script> 