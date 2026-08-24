<template>
  <el-dialog
    :model-value="show"
    title="Payslip Details"
    width="80%"
    top="4vh"
    @close="closeModal"
  >

      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center items-center h-64">
        <div class="text-center">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
          <p class="text-slate-600">Loading payslip details...</p>
        </div>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="flex justify-center items-center h-64">
        <div class="text-center">
          <svg class="w-16 h-16 text-red-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
          </svg>
          <p class="text-red-600 text-lg font-medium mb-2">Failed to load payslip details</p>
          <p class="text-slate-600">{{ error }}</p>
        </div>
      </div>

      <!-- Payslip Content -->
      <div v-else-if="payslipData" class="space-y-6">
        <!-- Payroll Period Header -->
        <div class="bg-blue-50 p-4 rounded-lg">
          <h4 class="text-lg font-semibold text-blue-900">{{ payslipData.payroll_period }}</h4>
        </div>

        <!-- Employee Information -->
        <div v-if="payslipData.payrolls && payslipData.payrolls.length > 0" class="space-y-6">
          <div v-for="payroll in payslipData.payrolls" :key="payroll.employee_id" class="border rounded-lg p-6">
            <!-- Employee Header -->
            <div class="flex items-center space-x-4 mb-6">
              <img 
                :src="payroll.photo ? `data:image/jpeg;base64,${payroll.photo}` : '/src/assets/img/profile.png'"
                @error="$event.target.src = '/src/assets/img/profile.png'"
                class="w-16 h-16 rounded-full border-2 border-slate-200"
                alt="Employee Photo"
              />
              <div>
                <h5 class="text-xl font-semibold text-slate-900">{{ payroll.name }}</h5>
                <p class="text-slate-600">{{ payroll.employee_no }}</p>
                <p class="text-slate-600">{{ payroll.department }} • {{ payroll.position }}</p>
                <p class="text-slate-600">{{ payroll.employment_type }}</p>
              </div>
            </div>

            <!-- Earnings Section -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
              <div class="bg-green-50 p-4 rounded-lg">
                <h6 class="font-semibold text-green-800 mb-3">Earnings</h6>
                <div class="space-y-2">
                  <div class="flex justify-between">
                    <span class="text-slate-600">Salary:</span>
                    <span class="font-medium">₱{{ formatCurrency(payroll.salary) }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-slate-600">Overtime:</span>
                    <span class="font-medium">₱{{ formatCurrency(payroll.ot_pay) }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-slate-600">Night Differential:</span>
                    <span class="font-medium">₱{{ formatCurrency(payroll.nd_pay) }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-slate-600">Holiday:</span>
                    <span class="font-medium">₱{{ formatCurrency(payroll.holiday_pay) }}</span>
                  </div>
                  <div v-if="payslipData.incomes && payslipData.incomes.length > 0">
                    <div v-for="income in payslipData.incomes" :key="income.income" class="flex justify-between">
                      <span class="text-slate-600">{{ income.income }}:</span>
                      <span class="font-medium">₱{{ formatCurrency(income.amount) }}</span>
                    </div>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-slate-600">Late Amount:</span>
                    <span class="font-medium">₱{{ formatCurrency(payroll.late_amount) }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-slate-600">Undertime:</span>
                    <span class="font-medium">₱{{ formatCurrency(payroll.ut_amount) }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-slate-600">Absent:</span>
                    <span class="font-medium">₱{{ formatCurrency(payroll.absent_amount) }}</span>
                  </div>
                  <div class="border-t pt-2 mt-2">
                    <div class="flex justify-between font-semibold text-green-800">
                      <span>Total Income:</span>
                      <span>₱{{ formatCurrency(calculateTotalIncome(payroll)) }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Deductions Section -->
              <div class="bg-red-50 p-4 rounded-lg">
                <h6 class="font-semibold text-red-800 mb-3">Deductions</h6>
                <div class="space-y-2">
                  <div v-if="payroll.gsis" class="flex justify-between">
                    <span class="text-slate-600">GSIS:</span>
                    <span class="font-medium">₱{{ formatCurrency(payroll.gsis) }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-slate-600">SSS:</span>
                    <span class="font-medium">₱{{ formatCurrency(payroll.sss || payroll.gsis) }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-slate-600">Pag-ibig:</span>
                    <span class="font-medium">₱{{ formatCurrency(payroll.pagibig) }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-slate-600">PhilHealth:</span>
                    <span class="font-medium">₱{{ formatCurrency(payroll.philhealth) }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-slate-600">Tax:</span>
                    <span class="font-medium">₱{{ formatCurrency(payroll.tax) }}</span>
                  </div>
                  <div v-if="payslipData.deductions && payslipData.deductions.length > 0">
                    <div v-for="deduction in payslipData.deductions" :key="deduction.deduction" class="flex justify-between">
                      <span class="text-slate-600">{{ deduction.deduction }}:</span>
                      <span class="font-medium">₱{{ formatCurrency(deduction.amount) }}</span>
                    </div>
                  </div>
                  <div class="border-t pt-2 mt-2">
                    <div class="flex justify-between font-semibold text-red-800">
                      <span>Total Deductions:</span>
                      <span>₱{{ formatCurrency(calculateTotalDeductions(payroll)) }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Summary Section -->
            <div class="bg-slate-50 p-4 rounded-lg">
              <h6 class="font-semibold text-slate-800 mb-3">Summary</h6>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="text-center">
                  <p class="text-sm text-slate-600">Gross Pay</p>
                  <p class="text-lg font-semibold text-slate-900">₱{{ formatCurrency(payroll.gross_amount) }}</p>
                </div>
                <div class="text-center">
                  <p class="text-sm text-slate-600">Net Pay</p>
                  <p class="text-lg font-semibold text-green-600">₱{{ formatCurrency(payroll.net_pay) }}</p>
                </div>
                <div class="text-center">
                  <p class="text-sm text-slate-600">Tardiness</p>
                  <p class="text-lg font-semibold text-red-600">₱{{ formatCurrency(payroll.tardiness_amount) }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
  </el-dialog>
</template>

<script>
import ApiService from '../../services/api.js'

export default {
  name: 'PayslipDetailModal',
  props: {
    show: {
      type: Boolean,
      default: false
    },
    payslip: {
      type: Object,
      default: null
    },
    payrollId: {
      type: [String, Number],
      default: null
    }
  },
  data() {
    return {
      loading: false,
      error: null,
      payslipData: null
    }
  },
  mounted() {
    if (this.show) {
      this.loadPayslipDetails()
    }
  },
  watch: {
    show(newVal) {
      if (newVal && this.payslip && this.payrollId) {
        this.loadPayslipDetails()
      } else {
        this.cleanup()
      }
    }
  },
  methods: {
    async loadPayslipDetails() {
      if (!this.payslip || !this.payrollId) {
        return
      }
      
      this.loading = true
      this.error = null
      this.payslipData = null
      
      try {
        // Get employee ID from the payslip data
        const employeeId = this.payslip.employee_id
        
        const response = await ApiService.getPayslipDetails(employeeId, this.payrollId)
        
        if (!response.success) throw new Error(response.message || 'Failed to load payslip details')
        
        this.payslipData = response.data
        this.loading = false
      } catch (error) {
        this.error = error.message || 'Failed to load payslip details'
        this.loading = false
      }
    },
    
    closeModal() {
      this.$emit('close')
    },
    
    cleanup() {
      this.payslipData = null
      this.loading = false
      this.error = null
    },
    
    formatCurrency(amount) {
      if (!amount || amount === 0) return '0.00'
      return parseFloat(amount).toFixed(2)
    },
    
    calculateTotalIncome(payroll) {
      let total = parseFloat(payroll.salary || 0)
      total += parseFloat(payroll.ot_pay || 0)
      total += parseFloat(payroll.nd_pay || 0)
      total += parseFloat(payroll.holiday_pay || 0)
      
      // Add additional incomes
      if (this.payslipData && this.payslipData.incomes) {
        this.payslipData.incomes.forEach(income => {
          total += parseFloat(income.amount || 0)
        })
      }
      
      // Note: Late Amount, Undertime, and Absent are typically deductions, not income
      // But they're displayed in the earnings section for reference
      
      return total
    },
    
    calculateTotalDeductions(payroll) {
      let total = parseFloat(payroll.gsis || 0)
      total += parseFloat(payroll.sss || 0)
      total += parseFloat(payroll.pagibig || 0)
      total += parseFloat(payroll.philhealth || 0)
      total += parseFloat(payroll.tax || 0)
      
      // Add additional deductions
      if (this.payslipData && this.payslipData.deductions) {
        this.payslipData.deductions.forEach(deduction => {
          total += parseFloat(deduction.amount || 0)
        })
      }
      
      return total
    }
  },
  
  beforeUnmount() {
    this.cleanup()
  }
}
</script>
