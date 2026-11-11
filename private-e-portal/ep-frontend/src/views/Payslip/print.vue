<template>
  <div>
    <div v-if="loading" class="p-8 max-w-4xl mx-auto">
      <el-skeleton animated :rows="10" />
    </div>
    <PayslipPrint v-else-if="payslip" :payslip="payslip" :company="company" :logo1="logo1" :logo2="logo2" :incomes="incomes" :deductions="deductions" :signatory="signatory" />
    <div v-else class="p-8 text-center text-slate-500">
      Payslip print data unavailable
    </div>
  </div>
</template>

<script>
import PayslipPrint from '../../components/Payslip/PayslipPrint.vue'
import ApiService from '../../services/api.js'

export default {
  name: 'PayslipPrintView',
  components: { PayslipPrint },
  data() {
    return {
      payslip: null,
      company: null,
      logo1: '',
      logo2: '',
      incomes: [],
      deductions: [],
      signatory: null,
      loading: true
    }
  },
  async mounted() {
    const employeeId = this.$route.params.employeeId
    const payslipId = this.$route.params.payslipId
    await this.loadPayslipPrint(employeeId, payslipId)
    if (this.payslip) {
      this.$nextTick(() => window.print())
    }
  },
  methods: {
    async loadPayslipPrint(employeeId, payslipId) {
      this.loading = true
      try {
        const response = await ApiService.getPayslipDetails(employeeId, payslipId)
        const data = response?.data || response
        if (data) {
          this.payslip = data.payslip || data
          this.company = data.company || null
          this.logo1 = data.logo1 || ''
          this.logo2 = data.logo2 || ''
          this.incomes = data.incomes || []
          this.deductions = data.deductions || []
          this.signatory = data.signatory || null
        }
      } catch (error) {
        console.error('Error loading payslip print:', error)
        if (this.$toast) this.$toast.error('Failed to load payslip print')
      } finally {
        this.loading = false
      }
    }
  }
}
</script>
