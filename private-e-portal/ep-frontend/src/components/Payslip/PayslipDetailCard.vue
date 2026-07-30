<template>
  <div class="bg-white rounded-lg shadow-sm border border-slate-200">
    <div class="p-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <!-- Employee Info -->
        <div class="text-center md:col-span-1">
          <img
            :src="payslip.photo ? `data:image/jpeg;base64,${payslip.photo}` : '/dist/img/employee_profile.png'"
            @error="$event.target.src = '/dist/img/employee_profile.png'"
            alt="Employee profile picture"
            class="w-24 h-24 rounded-full mx-auto mb-4 object-cover"
          >
          <h4 class="text-lg font-semibold text-slate-900 mb-0">{{ payslip.name }}</h4>
          <p class="text-slate-600 mb-0">{{ payslip.employee_no }}</p>
          <div class="mt-2">
            <div class="text-xs text-slate-500">Release Date:</div>
            <div class="text-sm text-slate-900">
              <span v-if="payslip.release_date">{{ formatDate(payslip.release_date) }}</span>
              <span v-else class="text-slate-400 italic">Not available</span>
            </div>
            <div class="text-xs text-slate-500 mt-2">Department:</div>
            <div class="text-sm text-slate-900">{{ payslip.department }}</div>
            <div class="text-xs text-slate-500 mt-2">Position:</div>
            <div class="text-sm text-slate-900">{{ payslip.position }}</div>
          </div>
        </div>
        <!-- Payslip Info -->
        <div class="md:col-span-3">
          <div class="mb-4">
            <label class="block text-sm font-medium text-slate-600 mb-1">Payroll Period :</label>
            <h5 class="text-lg font-semibold text-slate-900">{{ payslip.payroll_period }}</h5>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Income -->
            <div>
              <div class="font-semibold text-slate-700 mb-2">Income</div>
              <div class="flex justify-between mb-1"><span>Salary:</span><span>{{ formatAmount(payslip.salary) }}</span></div>
              <div class="flex justify-between mb-1"><span>Overtime:</span><span>{{ formatAmount(payslip.ot_pay) }}</span></div>
              <div class="flex justify-between mb-1"><span>Night Differential:</span><span>{{ formatAmount(payslip.nd_pay) }}</span></div>
              <div class="flex justify-between mb-1"><span>Holiday:</span><span>{{ formatAmount(payslip.holiday_pay) }}</span></div>
              <div class="flex justify-between mb-1"><span>Other Income:</span><span>{{ formatAmount(payslip.total_income) }}</span></div>
            </div>
            <!-- Deductions -->
            <div>
              <div class="font-semibold text-slate-700 mb-2">Deductions</div>
              <div class="flex justify-between mb-1"><span>Late Amount:</span><span>{{ formatAmount(payslip.late_amount) }}</span></div>
              <div class="flex justify-between mb-1"><span>Undertime:</span><span>{{ formatAmount(payslip.ut_amount) }}</span></div>
              <div class="flex justify-between mb-1"><span>Absent:</span><span>{{ formatAmount(payslip.absent_amount) }}</span></div>
              <div class="flex justify-between mb-1"><span>GSIS:</span><span>{{ formatAmount(payslip.gsis) }}</span></div>
              <div class="flex justify-between mb-1"><span>SSS:</span><span>{{ formatAmount(payslip.sss) }}</span></div>
              <div class="flex justify-between mb-1"><span>Pag-ibig:</span><span>{{ formatAmount(payslip.pagibig) }}</span></div>
              <div class="flex justify-between mb-1"><span>PhilHealth:</span><span>{{ formatAmount(payslip.philhealth) }}</span></div>
              <div class="flex justify-between mb-1"><span>Tax:</span><span>{{ formatAmount(payslip.tax) }}</span></div>
              <div class="flex justify-between mb-1"><span>Other Deduction:</span><span>{{ formatAmount(payslip.total_deduction) }}</span></div>
            </div>
          </div>
          <!-- Gross/Net Pay -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
            <div>
              <div class="flex justify-between mb-1 font-bold text-slate-900"><span>Gross Pay:</span><span>{{ formatAmount(payslip.gross_amount) }}</span></div>
            </div>
            <div>
              <div class="flex justify-between mb-1 font-bold text-slate-900"><span>Net Pay:</span><span>{{ formatAmount(payslip.net_pay) }}</span></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'PayslipDetailCard',
  props: {
    payslip: {
      type: Object,
      required: true
    }
  },
  methods: {
    formatAmount(amount) {
      if (typeof amount !== 'number') return ''
      const value = amount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
      return `₱${value}`
    },
    formatDate(dateString) {
      if (!dateString) return ''
      const date = new Date(dateString)
      if (isNaN(date.getTime())) return ''
      return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      })
    }
  }
}
</script>