<template>
  <div class="space-y-6">
    <!-- Employee Profile Card -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
      <!-- Employee Info -->
      <div class="lg:col-span-1">
        <div class="bg-white rounded-lg border border-slate-200 p-6">
          <div class="text-center mb-4">
            <img 
              :src="employee.photo ? `data:image/jpeg;base64,${employee.photo}` : '/src/assets/img/profile.png'"
              @error="$event.target.src = '/src/assets/img/profile.png'"
              class="w-24 h-24 rounded-full mx-auto mb-3"
              alt="Employee Photo"
            >
            <h3 class="text-lg font-semibold text-slate-900">{{ employee.name }}</h3>
            <p class="text-sm text-slate-600">{{ employee.employee_no }}</p>
          </div>
          
          <div class="space-y-3">
            <div>
              <span class="text-xs font-medium text-slate-500">Employment Type:</span>
              <p class="text-sm text-slate-900">{{ employee.employment_type }}</p>
            </div>
            <div>
              <span class="text-xs font-medium text-slate-500">Department:</span>
              <p class="text-sm text-slate-900">{{ employee.department }}</p>
            </div>
            <div>
              <span class="text-xs font-medium text-slate-500">Item Code:</span>
              <p class="text-sm text-slate-900">{{ employee.plantilla_code }}</p>
            </div>
            <div>
              <span class="text-xs font-medium text-slate-500">Position:</span>
              <p class="text-sm text-slate-900">{{ employee.position }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Competency Details -->
      <div class="lg:col-span-3">
        <div class="bg-white rounded-lg border border-slate-200">
          <div class="p-6">
            <!-- Competency Tabs -->
            <div class="border-b border-slate-200 mb-6">
              <nav class="flex space-x-8">
                <button
                  v-for="competency in competencies"
                  :key="competency.id"
                  @click="activeCompetency = competency.id"
                  :class="[
                    'py-2 px-1 border-b-2 font-medium text-sm transition-colors duration-200',
                    activeCompetency === competency.id
                      ? 'border-blue-500 text-blue-600'
                      : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'
                  ]"
                >
                  {{ competency.name }}
                </button>
              </nav>
            </div>

            <!-- Competency Content -->
            <div v-if="activeCompetency" class="space-y-4">
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
                            Required Level
                          </th>
                          <th class="px-4 py-3 text-left text-xs font-medium text-green-800 uppercase tracking-wider">
                            Rubric Description
                          </th>
                          <th class="px-4 py-3 text-left text-xs font-medium text-green-800 uppercase tracking-wider">
                            Level Attained
                          </th>
                        </tr>
                      </thead>
                      <tbody class="bg-green-50 divide-y divide-green-200">
                        <tr v-if="currentSubCompetencies.length === 0">
                          <td colspan="5" class="px-4 py-4 text-center text-green-600">
                            No sub-competencies found for this competency
                          </td>
                        </tr>
                        <tr v-for="subComp in currentSubCompetencies" :key="subComp.id" class="hover:bg-green-100">
                          <td class="px-4 py-4 whitespace-nowrap text-sm text-slate-900">
                            {{ subComp.code }}
                          </td>
                          <td class="px-4 py-4 whitespace-nowrap text-sm text-slate-900">
                            {{ subComp.name }}
                          </td>
                          <td class="px-4 py-4 whitespace-nowrap text-sm text-slate-900">
                            {{ subComp.required_level }}
                          </td>
                          <td class="px-4 py-4 whitespace-nowrap text-sm text-slate-900">
                            {{ subComp.description }}
                          </td>
                          <td class="px-4 py-4 whitespace-nowrap text-sm text-slate-900">
                            {{ subComp.level_attained }}
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'EmployeeCompetencyDetails',
  props: {
    employee: {
      type: Object,
      default: () => ({})
    },
    competencies: {
      type: Array,
      default: () => []
    },
    subCompetencies: {
      type: Array,
      default: () => []
    }
  },
  data() {
    return {
      activeCompetency: null
    }
  },
  computed: {
    currentSubCompetencies() {
      if (!this.activeCompetency) return []
      return this.subCompetencies.filter(sub => sub.competency_id === this.activeCompetency)
    }
  },
  mounted() {
    if (this.competencies.length > 0) {
      this.activeCompetency = this.competencies[0].id
    }
  }
}
</script> 