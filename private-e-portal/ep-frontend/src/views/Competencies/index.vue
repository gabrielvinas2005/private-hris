<template>
  <MainLayout :breadcrumbs="breadcrumbs">
    <div class="max-w-7xl mx-auto">
      <!-- Header Section -->
      <div class="mb-6">
        <h1 class="text-3xl font-bold text-slate-900 mb-2">Competencies Setup</h1>
        <p class="text-slate-600">Manage competency configurations and settings</p>
      </div>

      <!-- Add Competency Button -->
      <div class="flex justify-end mb-6">
        <button 
          @click="addCompetency"
          class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors duration-200"
        >
          Add Competency
        </button>
      </div>

      <!-- Competencies Table -->
      <div class="bg-white rounded-lg shadow-sm border border-slate-200">
        <div class="p-6">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
              <thead class="bg-slate-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                    Name
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                    Active
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-slate-200">
                <tr v-if="competencies.length === 0">
                  <td colspan="3" class="px-6 py-4 text-center text-slate-500">
                    No competencies found
                  </td>
                </tr>
                <tr v-for="competency in competencies" :key="competency.id" class="hover:bg-slate-50">
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                    {{ competency.name }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                    <span 
                      :class="[
                        'px-2 py-1 text-xs font-medium rounded-full',
                        competency.active 
                          ? 'bg-green-100 text-green-800' 
                          : 'bg-red-100 text-red-800'
                      ]"
                    >
                      {{ competency.active ? 'YES' : 'NO' }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                    <button 
                      @click="editCompetency(competency)"
                      class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors duration-200"
                    >
                      Edit
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </MainLayout>
</template>

<script>
import MainLayout from '../../layout/MainLayout.vue'
import { mockApiService } from '../../services/mockData.js'

export default {
  name: 'CompetenciesView',
  components: { MainLayout },
  data() {
    return {
      breadcrumbs: [
        { name: 'Dashboard', path: '/dashboard' },
        { name: 'Competencies Setup', path: '/competencies' }
      ],
      competencies: []
    }
  },
  async mounted() {
    await this.loadCompetencies()
  },
  methods: {
    async loadCompetencies() {
      try {
        const response = await mockApiService.getCompetencies()
        this.competencies = response.data.competencies
      } catch (error) {
        console.error('Error loading competencies:', error)
        this.$toast.error('Failed to load competencies')
      }
    },
    addCompetency() {
      this.$router.push('/competencies/add')
    },
    editCompetency(competency) {
      this.$router.push(`/competencies/edit/${competency.id}`)
    }
  }
}
</script> 