<template>
  <MainLayout :breadcrumbs="breadcrumbs">
    <div class="max-w-7xl mx-auto">
      <!-- Header Section -->
      <div class="mb-6">
        <h1 class="text-3xl font-bold text-slate-900 mb-2">Add Competency</h1>
        <p class="text-slate-600">Create a new competency with sub-competencies</p>
      </div>

      <!-- Form -->
      <div class="bg-white rounded-lg shadow-sm border border-slate-200">
        <div class="p-6">
          <form @submit.prevent="saveCompetency">
            <!-- Competency Name -->
            <div class="mb-6">
              <label class="block text-sm font-medium text-slate-700 mb-2">
                Name
              </label>
              <input 
                v-model="form.name"
                type="text"
                class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Competency Name"
                required
              >
            </div>

            <!-- Sub-Competencies Table -->
            <div class="mb-6">
              <h3 class="text-lg font-semibold text-slate-900 mb-4">Sub-Competencies</h3>
              <div class="bg-green-50 rounded-lg border border-green-200">
                <div class="p-4">
                  <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-green-200">
                      <thead class="bg-green-100">
                        <tr>
                          <th class="px-4 py-3 text-left text-xs font-medium text-green-800 uppercase tracking-wider">
                            Code
                          </th>
                          <th class="px-4 py-3 text-left text-xs font-medium text-green-800 uppercase tracking-wider">
                            Name
                          </th>
                          <th class="px-4 py-3 text-left text-xs font-medium text-green-800 uppercase tracking-wider">
                            Rubric Description
                          </th>
                          <th class="px-4 py-3 text-left text-xs font-medium text-green-800 uppercase tracking-wider">
                            Actions
                          </th>
                        </tr>
                      </thead>
                      <tbody class="bg-green-50 divide-y divide-green-200">
                        <tr v-if="subCompetencies.length === 0">
                          <td colspan="4" class="px-4 py-4 text-center text-green-600">
                            No sub-competencies added yet
                          </td>
                        </tr>
                        <tr v-for="(subComp, index) in subCompetencies" :key="index" class="hover:bg-green-100">
                          <td class="px-4 py-4">
                            <input 
                              v-model="subComp.code"
                              type="text"
                              class="w-full px-2 py-1 border border-green-300 rounded focus:outline-none focus:ring-1 focus:ring-green-500"
                              placeholder="Code"
                            >
                          </td>
                          <td class="px-4 py-4">
                            <input 
                              v-model="subComp.name"
                              type="text"
                              class="w-full px-2 py-1 border border-green-300 rounded focus:outline-none focus:ring-1 focus:ring-green-500"
                              placeholder="Name"
                            >
                          </td>
                          <td class="px-4 py-4">
                            <input 
                              v-model="subComp.description"
                              type="text"
                              class="w-full px-2 py-1 border border-green-300 rounded focus:outline-none focus:ring-1 focus:ring-green-500"
                              placeholder="Description"
                            >
                          </td>
                          <td class="px-4 py-4">
                            <button 
                              @click="removeSubCompetency(index)"
                              type="button"
                              class="text-red-600 hover:text-red-800"
                            >
                              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                              </svg>
                            </button>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                  
                  <!-- Add Sub-Competency Button -->
                  <div class="mt-4">
                    <button 
                      @click="addSubCompetency"
                      type="button"
                      class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors duration-200"
                    >
                      <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                      </svg>
                      Add Sub-Competency
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Active Status -->
            <div class="mb-6">
              <label class="flex items-center">
                <input 
                  v-model="form.active"
                  type="checkbox"
                  class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                >
                <span class="ml-2 text-sm text-slate-700">Set as Active</span>
              </label>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end">
              <button 
                type="submit"
                class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200"
                :disabled="isSubmitting"
              >
                {{ isSubmitting ? 'Saving...' : 'Save Competency' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </MainLayout>
</template>

<script>
import MainLayout from '../../layout/MainLayout.vue'
import { mockApiService } from '../../services/mockData.js'

export default {
  name: 'AddCompetencyView',
  components: { MainLayout },
  data() {
    return {
      breadcrumbs: [
        { name: 'Dashboard', path: '/dashboard' },
        { name: 'Competencies Setup', path: '/competencies' },
        { name: 'Add Competency', path: '/competencies/add' }
      ],
      form: {
        name: '',
        active: false
      },
      subCompetencies: [],
      isSubmitting: false
    }
  },
  methods: {
    addSubCompetency() {
      this.subCompetencies.push({
        code: '',
        name: '',
        description: ''
      })
    },
    removeSubCompetency(index) {
      this.subCompetencies.splice(index, 1)
    },
    async saveCompetency() {
      this.isSubmitting = true
      
      try {
        const competencyData = {
          name: this.form.name,
          active: this.form.active,
          subCompetencies: this.subCompetencies
        }
        
        await mockApiService.createCompetency(competencyData)
        this.$toast.success('Competency created successfully')
        this.$router.push('/competencies')
      } catch (error) {
        console.error('Error creating competency:', error)
        this.$toast.error('Failed to create competency')
      } finally {
        this.isSubmitting = false
      }
    }
  }
}
</script> 