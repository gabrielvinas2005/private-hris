<template>
  <PayslipPrint :payslip="payslip" :company="company" :logo1="logo1" :logo2="logo2" :incomes="incomes" :deductions="deductions" :signatory="signatory" />
</template>

<script>
import PayslipPrint from '../../components/Payslip/PayslipPrint.vue'
import { mockApiService } from '../../services/mockData.js'

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
      signatory: null
    }
  },
  async mounted() {
    const employeeId = this.$route.params.employeeId
    const payslipId = this.$route.params.payslipId
    await this.loadPayslipPrint(employeeId, payslipId)
    this.$nextTick(() => window.print())
  },
  methods: {
    async loadPayslipPrint(employeeId, payslipId) {
      try {
        const response = await mockApiService.getPayslipPrint(employeeId, payslipId)
        const data = response.data
        this.payslip = data.payslip
        this.company = data.company
        this.logo1 = data.logo1
        this.logo2 = data.logo2
        this.incomes = data.incomes
        this.deductions = data.deductions
        this.signatory = data.signatory
      } catch (error) {
        console.error('Error loading payslip print:', error)
        this.$toast.error('Failed to load payslip print')
      }
    }
  }
}
</script>