<template>
  <div class="space-y-6">
    <!-- Employment Details -->
    <div>
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-slate-900">Employment Details</h3>
        <el-tag :type="getStatusBadgeType(localWorkInfo.employment_type)" size="large" effect="dark">
          {{ localWorkInfo.employment_type || 'Not Specified' }}
        </el-tag>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div>
          <label class="text-sm font-medium text-slate-500">Position Title</label>
          <p class="text-base font-semibold text-slate-900">{{ localWorkInfo.position || '-' }}</p>
        </div>
        <div>
          <label class="text-sm font-medium text-slate-500">Employee Rank / Level</label>
          <p class="text-base font-semibold text-slate-900">{{ localWorkInfo.rank_level || 'Rank and File' }}</p>
        </div>
        <div>
          <label class="text-sm font-medium text-slate-500">Department</label>
          <p class="text-base font-semibold text-slate-900">{{ localWorkInfo.department || '-' }}</p>
        </div>
        <div>
          <label class="text-sm font-medium text-slate-500">Date Hired</label>
          <p class="text-base font-semibold text-slate-900">{{ formatDate(localWorkInfo.date_hired) }}</p>
        </div>
        <div v-if="localWorkInfo.employment_type === 'Probationary'">
          <label class="text-sm font-medium text-amber-600">Probationary End Date (Max 6 Mos)</label>
          <p class="text-base font-semibold text-amber-700">{{ formatDate(localWorkInfo.probationary_end_date) }}</p>
        </div>
        <div v-if="localWorkInfo.date_regularized">
          <label class="text-sm font-medium text-emerald-600">Date Regularized</label>
          <p class="text-base font-semibold text-emerald-700">{{ formatDate(localWorkInfo.date_regularized) }}</p>
        </div>
        <div v-if="localWorkInfo.contract_end_date">
          <label class="text-sm font-medium text-slate-500">Contract Expiry Date</label>
          <p class="text-base font-semibold text-slate-900">{{ formatDate(localWorkInfo.contract_end_date) }}</p>
        </div>
      </div>
    </div>

    <hr class="border-slate-200">

    <!-- Statutory Mandates & Identifiers -->
    <div>
      <h3 class="text-lg font-semibold text-slate-900 mb-4">Philippine Statutory Numbers</h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
          <label class="text-xs font-semibold text-blue-700 uppercase">Social Security System (SSS)</label>
          <p class="text-base font-bold text-slate-900 mt-1">{{ localPayrollInfo.sss_no || 'Pending Submission' }}</p>
        </div>
        <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-3">
          <label class="text-xs font-semibold text-emerald-700 uppercase">PhilHealth Number</label>
          <p class="text-base font-bold text-slate-900 mt-1">{{ localPayrollInfo.philhealth_no || 'Pending Submission' }}</p>
        </div>
        <div class="bg-amber-50 border border-amber-200 rounded-lg p-3">
          <label class="text-xs font-semibold text-amber-700 uppercase">Pag-IBIG / HDMF MID</label>
          <p class="text-base font-bold text-slate-900 mt-1">{{ localPayrollInfo.pagibig_no || 'Pending Submission' }}</p>
        </div>
        <div class="bg-purple-50 border border-purple-200 rounded-lg p-3">
          <label class="text-xs font-semibold text-purple-700 uppercase">BIR TIN</label>
          <p class="text-base font-bold text-slate-900 mt-1">{{ localPayrollInfo.tin_no || 'Pending Submission' }}</p>
        </div>
      </div>
    </div>

    <hr class="border-slate-200">

    <!-- Compensation & Payroll Details -->
    <div>
      <h3 class="text-lg font-semibold text-slate-900 mb-4">Wage & Payroll Structure</h3>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div>
          <label class="text-sm font-medium text-slate-500">Pay Frequency</label>
          <p class="text-slate-900 font-medium">{{ localPayrollInfo.interval || 'Semi-Monthly' }}</p>
        </div>
        <div>
          <label class="text-sm font-medium text-slate-500">Payment Basis</label>
          <p class="text-slate-900 font-medium capitalize">{{ localPayrollInfo.rate_type }} Paid</p>
        </div>
        <div>
          <label class="text-sm font-medium text-slate-500">Tax Classification</label>
          <p class="text-slate-900 font-medium">
            <span v-if="localPayrollInfo.is_mwe" class="text-emerald-600 font-semibold">Minimum Wage Earner (MWE - Tax Exempt)</span>
            <span v-else>Regular Taxable Employee</span>
          </p>
        </div>
        <div>
          <label class="text-sm font-medium text-slate-500">Monthly Basic Salary</label>
          <p class="text-xl font-bold text-slate-900">{{ formatCurrency(localPayrollInfo.salary) }}</p>
        </div>
        <div v-if="localPayrollInfo.rate_type === 'daily' || localPayrollInfo.daily_rate > 0">
          <label class="text-sm font-medium text-slate-500">Daily Rate</label>
          <p class="text-xl font-bold text-slate-900">{{ formatCurrency(localPayrollInfo.daily_rate) }}</p>
        </div>
      </div>
    </div>

    <!-- Recurring Allowances and Company Loans -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
      <div class="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden">
        <div class="bg-slate-100 px-4 py-3 border-b border-slate-200 font-semibold text-slate-800">
          Recurring Incomes & De Minimis Benefits
        </div>
        <table class="min-w-full divide-y divide-slate-200 text-sm">
          <thead class="bg-slate-50">
            <tr>
              <th class="px-4 py-2 text-left font-medium text-slate-600">Item</th>
              <th class="px-4 py-2 text-right font-medium text-slate-600">Amount</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="inc in incomes" :key="inc.id">
              <td class="px-4 py-2 text-slate-800">{{ inc.name }}</td>
              <td class="px-4 py-2 text-right font-medium text-slate-900">{{ formatCurrency(inc.amount) }}</td>
            </tr>
            <tr v-if="incomes.length === 0">
              <td colspan="2" class="px-4 py-3 text-center text-slate-400">No recurring allowances configured</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden">
        <div class="bg-slate-100 px-4 py-3 border-b border-slate-200 font-semibold text-slate-800">
          Company & SSS/Pag-IBIG Loan Amortizations
        </div>
        <table class="min-w-full divide-y divide-slate-200 text-sm">
          <thead class="bg-slate-50">
            <tr>
              <th class="px-4 py-2 text-left font-medium text-slate-600">Loan Type</th>
              <th class="px-4 py-2 text-right font-medium text-slate-600">Principal</th>
              <th class="px-4 py-2 text-right font-medium text-slate-600">Amortization</th>
              <th class="px-4 py-2 text-right font-medium text-slate-600">Balance</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="loan in loans" :key="loan.id">
              <td class="px-4 py-2 text-slate-800">{{ loan.name }}</td>
              <td class="px-4 py-2 text-right">{{ formatCurrency(loan.loan_amount) }}</td>
              <td class="px-4 py-2 text-right">{{ formatCurrency(loan.payment) }}</td>
              <td class="px-4 py-2 text-right font-semibold text-red-600">{{ formatCurrency(loan.balance) }}</td>
            </tr>
            <tr v-if="loans.length === 0">
              <td colspan="4" class="px-4 py-3 text-center text-slate-400">No active loans</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'WorkInformation',
  props: {
    workInfo: { type: Object, default: () => ({}) },
    payrollInfo: { type: Object, default: () => ({}) },
    incomes: { type: Array, default: () => [] },
    loans: { type: Array, default: () => [] }
  },
  setup(props) {
    const localWorkInfo = props.workInfo
    const localPayrollInfo = props.payrollInfo

    const formatDate = (dateStr) => {
      if (!dateStr) return '-'
      return new Date(dateStr).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' })
    }

    const formatCurrency = (val) => {
      return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(val || 0)
    }

    const getStatusBadgeType = (type) => {
      if (!type) return 'info'
      if (type.toLowerCase().includes('regular')) return 'success'
      if (type.toLowerCase().includes('probationary')) return 'warning'
      if (type.toLowerCase().includes('project') || type.toLowerCase().includes('contract')) return 'primary'
      return 'info'
    }

    return { localWorkInfo, localPayrollInfo, formatDate, formatCurrency, getStatusBadgeType }
  }
}
</script>