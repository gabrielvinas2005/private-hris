<template>
  <MainLayout :breadcrumbs="breadcrumbs">
    <div class="max-w-7xl mx-auto">
      <!-- Header Section -->
      <div class="mb-6">
        <h1 class="text-3xl font-bold text-slate-900 mb-2">My Competencies</h1>
        <p class="text-slate-600">View and manage your competency assessments</p>
      </div>

      <!-- Loading State (Skeleton Loader) -->
      <div v-if="loading" class="bg-white rounded-lg shadow-sm border border-slate-200 p-8">
        <el-skeleton animated :rows="8" />
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
import ApiService from '../../services/api.js'
import { swrCache } from '../../composables/useCachedApi.js'

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
    async loadMyCompetencies(forceRefresh = false) {
      try {
        const userData = JSON.parse(localStorage.getItem('user_data') || '{}')
        const employeeId = userData.employee_id || userData.id
        if (!employeeId) {
          this.loading = false
          return
        }

        const cacheKey = `my_competencies_${employeeId}`
        if (forceRefresh) {
          swrCache.mutate(cacheKey)
        }

        await swrCache.swrExecute({
          key: cacheKey,
          ttlMs: 10 * 60 * 1000, // 10 minutes cache
          fetcher: async () => {
            const response = await ApiService.getEmployeeCompetencyDetails(employeeId)
            return response?.data || response
          },
          onLoading: (isLoading) => {
            this.loading = isLoading
          },
          onData: (d) => {
            if (d) {
              this.employee = d.employee || userData
              this.competencies = Array.isArray(d.competencies) ? d.competencies : (Array.isArray(d) ? d : [])
              this.subCompetencies = Array.isArray(d.subCompetencies) ? d.subCompetencies : []
            }
          },
          onError: (error) => {
            console.error('Error loading competencies:', error)
          }
        })
      } catch (error) {
        console.error('Error loading competencies:', error)
      }
    }
  }
}
</script>
