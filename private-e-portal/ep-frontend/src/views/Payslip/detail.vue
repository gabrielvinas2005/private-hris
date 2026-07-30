<template>
  <MainLayout :breadcrumbs="breadcrumbs">
    <div class="max-w-7xl mx-auto">
      <!-- Header Section -->
      <div class="mb-6">
        <h1 class="text-3xl font-bold text-slate-900 mb-2">Employee Payslip</h1>
        <p class="text-slate-600">View detailed payslip information</p>
      </div>

      <PayslipDetailCard :payslip="payslip" />

      <!-- Footer -->
      <div class="mt-6 text-center">
        <p class="text-sm text-slate-600">Display Daily Time data.</p>
      </div>
    </div>
  </MainLayout>
</template>

<script>
import MainLayout from '../../layout/MainLayout.vue'
import PayslipDetailCard from '../../components/Payslip/PayslipDetailCard.vue'
import { mockApiService } from '../../services/mockData.js'

export default {
  name: 'PayslipDetailView',
  components: { MainLayout, PayslipDetailCard },
  data() {
    return {
      breadcrumbs: [
        { name: 'Dashboard', path: '/dashboard' },
        { name: 'Payslip List', path: '/payslip' },
        { name: 'Employee Payslip', path: '/payslip/detail' }
      ],
      payslip: null
    }
  },
  async mounted() {
    const employeeId = this.$route.params.employeeId
    const payslipId = this.$route.params.payslipId
    await this.loadPayslipDetail(employeeId, payslipId)
  },
  methods: {
    async loadPayslipDetail(employeeId, payslipId) {
      try {
        const response = await mockApiService.getPayslipDetail(employeeId, payslipId)
        this.payslip = response.data
      } catch (error) {
        console.error('Error loading payslip detail:', error)
        this.$toast.error('Failed to load payslip detail')
      }
    }
  }
}
</script>