<template>
  <MainLayout :breadcrumbs="breadcrumbs">
    <div class="max-w-7xl mx-auto">
      <!-- Header Section -->
      <div class="mb-6">
        <h1 class="text-3xl font-bold text-slate-900 mb-2">Employee Competencies</h1>
        <p class="text-slate-600">Manage employee competency assessments</p>
      </div>

      <!-- Skeleton Loader -->
      <div v-if="loading" class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
        <el-skeleton animated :rows="8" />
      </div>

      <!-- Employees Table -->
      <div v-else class="bg-white rounded-lg shadow-sm border border-slate-200">
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
                        alt="Employee Photo" 
                        class="h-10 w-10 rounded-full object-cover"
                      />
                    </div>
                  </td>
                  <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-slate-900">
                    {{ employee.employee_no }}
                  </td>
                  <td class="px-4 py-4 whitespace-nowrap text-sm text-slate-900 font-medium">
                    {{ employee.name }}
                  </td>
                  <td class="px-4 py-4 whitespace-nowrap text-sm text-slate-500">
                    {{ employee.employment_type || '—' }}
                  </td>
                  <td class="px-4 py-4 whitespace-nowrap text-sm text-slate-500">
                    {{ employee.plantilla_code || '—' }}
                  </td>
                  <td class="px-4 py-4 whitespace-nowrap text-sm text-slate-500">
                    {{ employee.position || '—' }}
                  </td>
                  <td class="px-4 py-4 whitespace-nowrap text-sm text-slate-500">
                    {{ employee.branch || '—' }}
                  </td>
                  <td class="px-4 py-4 whitespace-nowrap text-sm text-slate-500">
                    {{ employee.department || '—' }}
                  </td>
                  <td class="px-4 py-4 whitespace-nowrap text-sm text-slate-500">
                    {{ employee.salary_grade_id || '—' }}
                  </td>
                  <td class="px-4 py-4 whitespace-nowrap text-sm font-medium">
                    <button
                      @click="manageCompetency(employee)"
                      class="text-blue-600 hover:text-blue-900 transition-colors duration-200"
                    >
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
import ApiService from '../../services/api.js'

export default {
  name: 'EmployeeCompetenciesView',
  components: { MainLayout },
  data() {
    return {
      breadcrumbs: [
        { name: 'Dashboard', path: '/dashboard' },
        { name: 'Employee Competencies', path: '/competencies/employees' }
      ],
      employees: [],
      loading: true
    }
  },
  async mounted() {
    await this.loadEmployees()
  },
  methods: {
    async loadEmployees() {
      this.loading = true
      try {
        const response = await ApiService.getEmployeeCompetencies()
        const data = response?.data || response
        this.employees = Array.isArray(data) ? data : (Array.isArray(data?.employees) ? data.employees : [])
      } catch (error) {
        console.error('Error loading employees:', error)
      } finally {
        this.loading = false
      }
    },
    manageCompetency(employee) {
      this.$router.push(`/competencies/employees/${employee.id}/details`)
    }
  }
}
</script>
