<template>
  <MainLayout :breadcrumbs="breadcrumbs">
    <div class="max-w-7xl mx-auto">
      <!-- Header Section -->
      <div class="mb-6">
        <h1 class="text-3xl font-bold text-slate-900 mb-2">Employee Competencies</h1>
        <p class="text-slate-600">Manage employee competency assessments</p>
      </div>

      <!-- Employees Table -->
      <div class="bg-white rounded-lg shadow-sm border border-slate-200">
        <div class="p-6">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
              <thead class="bg-slate-50">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                    Photo
                  </th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                    Employee No.
                  </th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                    Name
                  </th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                    Employment Type
                  </th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                    Item Code
                  </th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                    Position
                  </th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                    Branch
                  </th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                    Department
                  </th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                    Salary Grade
                  </th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-slate-200">
                <tr v-if="employees.length === 0">
                  <td colspan="10" class="px-4 py-4 text-center text-slate-500">
                    No employees found
                  </td>
                </tr>
                <tr v-for="employee in employees" :key="employee.id" class="hover:bg-slate-50">
                  <td class="px-4 py-4 whitespace-nowrap">
                    <div class="flex justify-center">
                      <img 
                        :src="employee.photo ? `data:image/jpeg;base64,${employee.photo}` : '/src/assets/img/profile.png'"
                        @error="$event.target.src = '/src/assets/img/profile.png'"
                        class="w-8 h-8 rounded-full"
                        alt="Employee Photo"
                      >
                    </div>
                  </td>
                  <td class="px-4 py-4 whitespace-nowrap text-sm text-slate-900">
                    {{ employee.employee_no }}
                  </td>
                  <td class="px-4 py-4 whitespace-nowrap text-sm text-slate-900">
                    {{ employee.name }}
                  </td>
                  <td class="px-4 py-4 whitespace-nowrap text-sm text-slate-900">
                    {{ employee.employment_type }}
                  </td>
                  <td class="px-4 py-4 whitespace-nowrap text-sm text-slate-900">
                    {{ employee.plantilla_code }}
                  </td>
                  <td class="px-4 py-4 whitespace-nowrap text-sm text-slate-900">
                    {{ employee.position }}
                  </td>
                  <td class="px-4 py-4 whitespace-nowrap text-sm text-slate-900">
                    {{ employee.branch }}
                  </td>
                  <td class="px-4 py-4 whitespace-nowrap text-sm text-slate-900">
                    {{ employee.department }}
                  </td>
                  <td class="px-4 py-4 whitespace-nowrap text-sm text-slate-900">
                    {{ employee.salary_grade_id }}
                  </td>
                  <td class="px-4 py-4 whitespace-nowrap text-sm text-slate-900">
                    <button 
                      @click="manageCompetency(employee)"
                      class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors duration-200 text-xs"
                    >
                      <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                      </svg>
                      Manage Competency
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
  name: 'EmployeeCompetenciesView',
  components: { MainLayout },
  data() {
    return {
      breadcrumbs: [
        { name: 'Dashboard', path: '/dashboard' },
        { name: 'Employee Competencies', path: '/competencies/employees' }
      ],
      employees: []
    }
  },
  async mounted() {
    await this.loadEmployees()
  },
  methods: {
    async loadEmployees() {
      try {
        const response = await mockApiService.getEmployeeCompetencies()
        this.employees = response.data.employees
      } catch (error) {
        console.error('Error loading employees:', error)
        this.$toast.error('Failed to load employees')
      }
    },
    manageCompetency(employee) {
      this.$router.push(`/competencies/employees/${employee.id}/details`)
    }
  }
}
</script> 