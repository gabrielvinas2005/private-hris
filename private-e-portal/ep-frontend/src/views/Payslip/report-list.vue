<template>
  <MainLayout :breadcrumbs="breadcrumbs">
    <div class="max-w-7xl mx-auto">
      <!-- Header Section -->
      <div class="mb-6">
        <h1 class="text-3xl font-bold text-slate-900 mb-2">PAYROLL PAYMENT SLIP</h1>
        <p class="text-slate-600">Generate and preview payroll payment slip reports</p>
      </div>

      <!-- Skeleton Loader -->
      <div v-if="loading" class="bg-white rounded-xl shadow-sm border border-slate-200 p-8">
        <el-skeleton animated :rows="6" />
      </div>

      <PayslipReportForm
        v-else
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
import ApiService from '../../services/api.js'

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
      employees: [],
      loading: true
    }
  },
  async mounted() {
    await this.loadReportData()
  },
  methods: {
    async loadReportData() {
      this.loading = true
      try {
        const [userDataRaw] = [localStorage.getItem('user_data')]
        const userData = userDataRaw ? JSON.parse(userDataRaw) : {}
        const userId = userData.id || userData.employee_id
        if (userId) {
          const res = await ApiService.getPayslipList(userId)
          const list = res?.data || res || []
          if (Array.isArray(list)) {
            this.intervals = [...new Set(list.map(p => p.payroll_interval).filter(Boolean))].map(name => ({ id: name, name }))
          }
        }
      } catch (error) {
        console.error('Error loading report filters:', error)
      } finally {
        this.loading = false
      }
    },
    previewReport(form) {
      window.open('/payslip/report/preview', '_blank')
    }
  }
}
</script>
