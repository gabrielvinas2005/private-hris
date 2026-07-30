<template>
  <MainLayout :breadcrumbs="breadcrumbs">
    <div class="max-w-7xl mx-auto">
      <!-- Header Section -->
      <div class="mb-6">
        <h1 class="text-3xl font-bold text-slate-900 mb-2">PAYROLL PAYMENT SLIP</h1>
        <p class="text-slate-600">Generate and preview payroll payment slip reports</p>
      </div>

      <PayslipReportForm
        :intervals="intervals"
        :departments="departments"
        :employees="employees"
        @preview-report="previewReport"
      />

      <!-- Footer -->
      <div class="mt-6 text-center">
        <p class="text-sm text-slate-600">PAYROLL PAYMENT SLIP</p>
      </div>
    </div>
  </MainLayout>
</template>

<script>
import MainLayout from '../../layout/MainLayout.vue'
import PayslipReportForm from '../../components/Payslip/PayslipReportForm.vue'
import { mockApiService } from '../../services/mockData.js'

export default {
  name: 'PayslipReportListView',
  components: { MainLayout, PayslipReportForm },
  data() {
    return {
      breadcrumbs: [
        { name: 'Dashboard', path: '/dashboard' },
        { name: 'Payslip Report', path: '/payslip/report' }
      ],
      intervals: [],
      departments: [],
      employees: []
    }
  },
  async mounted() {
    await this.loadReportData()
  },
  methods: {
    async loadReportData() {
      try {
        const response = await mockApiService.getPayslipReportFilters()
        const data = response.data
        this.intervals = data.intervals
        this.departments = data.departments
        this.employees = data.employees
      } catch (error) {
        console.error('Error loading report filters:', error)
        this.$toast.error('Failed to load report filters')
      }
    },
    previewReport(form) {
      // Open preview in new tab (simulate report preview)
      window.open('/payslip/report/preview', '_blank')
    }
  }
}
</script>