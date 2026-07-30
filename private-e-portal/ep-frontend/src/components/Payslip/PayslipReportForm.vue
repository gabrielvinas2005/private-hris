<template>
  <form @submit.prevent="handlePreview">
    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-2">Payroll Interval <span class="text-red-500">*</span></label>
          <select v-model="form.payroll_interval_id" class="w-full px-3 py-2 border border-slate-300 rounded-md" required>
            <option value="" disabled>Select Payroll Interval</option>
            <option v-for="interval in intervals" :key="interval.id" :value="interval.id">{{ interval.name }}</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-2">Payroll Period <span class="text-red-500">*</span></label>
          <select v-model="form.payroll_period_id" class="w-full px-3 py-2 border border-slate-300 rounded-md" required>
            <option value="" disabled>Select Payroll Period</option>
            <option v-for="period in periods" :key="period.id" :value="period.id">{{ period.name }}</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-2">Office</label>
          <select v-model="form.department_id" class="w-full px-3 py-2 border border-slate-300 rounded-md">
            <option value="">All Departments</option>
            <option v-for="department in departments" :key="department.id" :value="department.id">{{ department.name }}</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-2">Employee</label>
          <select v-model="form.employee_id" class="w-full px-3 py-2 border border-slate-300 rounded-md">
            <option value="">All Employees</option>
            <option v-for="employee in employees" :key="employee.id" :value="employee.id">{{ employee.name }}</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-2">Certified Correct</label>
          <input v-model="form.certified_correct" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-md" placeholder="e.g. ATTY. FARIDA D. ROMILLO-MATEO" />
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-2">Position/Designation</label>
          <input v-model="form.position" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-md" placeholder="e.g. Municipal Accountant" />
        </div>
      </div>
      <div class="flex justify-end mt-6">
        <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors duration-200">
          Preview Report
        </button>
      </div>
    </div>
  </form>
</template>

<script>
export default {
  name: 'PayslipReportForm',
  props: {
    intervals: {
      type: Array,
      default: () => []
    },
    departments: {
      type: Array,
      default: () => []
    },
    employees: {
      type: Array,
      default: () => []
    }
  },
  data() {
    return {
      form: {
        payroll_interval_id: '',
        payroll_period_id: '',
        department_id: '',
        employee_id: '',
        certified_correct: 'ATTY. FARIDA D. ROMILLO-MATEO',
        position: 'Municipal Accountant'
      },
      periods: []
    }
  },
  watch: {
    payroll_interval_id(newVal) {
      this.loadPeriods(newVal)
    }
  },
  methods: {
    handlePreview() {
      this.$emit('preview-report', { ...this.form })
    },
    async loadPeriods(intervalId) {
      // Simulate API call for periods
      this.periods = [
        { id: 1, name: 'Jan 1-15, 2024' },
        { id: 2, name: 'Jan 16-31, 2024' }
      ]
    }
  }
}
</script>