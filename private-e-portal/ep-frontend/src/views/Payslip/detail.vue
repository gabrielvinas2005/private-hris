<template>
  <MainLayout :breadcrumbs="breadcrumbs">
    <div class="max-w-7xl mx-auto">
      <!-- Header Section -->
      <div class="mb-6">
        <h1 class="text-3xl font-bold text-slate-900 mb-2">Employee Payslip</h1>
        <p class="text-slate-600">View detailed payslip information</p>
      </div>

      <!-- Skeleton Loader -->
      <div v-if="loading" class="bg-white rounded-xl shadow-sm border border-slate-200 p-8">
        <el-skeleton animated :rows="10" />
      </div>

      <PayslipDetailCard v-else-if="payslip" :payslip="payslip" />

      <div v-else class="bg-white rounded-xl shadow-sm border border-slate-200 p-8 text-center text-slate-500">
        No payslip details available for this record.
      </div>

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
import ApiService from '../../services/api.js'

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
      payslip: null,
      loading: true
    }
  },
  async mounted() {
    const employeeId = this.$route.params.employeeId
    const payslipId = this.$route.params.payslipId
    await this.loadPayslipDetail(employeeId, payslipId)
  },
  methods: {
    async loadPayslipDetail(employeeId, payslipId) {
      this.loading = true
      try {
        const response = await ApiService.getPayslipDetails(employeeId, payslipId)
        this.payslip = response?.data || response
      } catch (error) {
        console.error('Error loading payslip detail:', error)
        if (this.$toast) this.$toast.error('Failed to load payslip detail')
      } finally {
        this.loading = false
      }
    }
  }
}
</script>
