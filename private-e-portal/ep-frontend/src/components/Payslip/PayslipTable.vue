<template>
  <div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-slate-200">
      <thead class="bg-slate-50">
        <tr>
          <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Payroll Interval</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Cut-off</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Payroll Start</th>
          <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Payroll End</th>
          <th class="px-6 py-3 text-center text-xs font-medium text-slate-500 uppercase tracking-wider">Payslip</th>
          <th class="px-6 py-3 text-center text-xs font-medium text-slate-500 uppercase tracking-wider">Print</th>
        </tr>
      </thead>
      <tbody class="bg-white divide-y divide-slate-200">
        <tr v-for="payslip in payslipRecords" :key="payslip.id" class="hover:bg-slate-50">
          <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">{{ payslip.payroll_interval }}</td>
          <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">{{ payslip.cut_off }}</td>
          <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">{{ formatDate(payslip.payroll_start_date) }}</td>
          <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">{{ formatDate(payslip.payroll_end_date) }}</td>
          <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900 text-center">
            <button
              @click="$emit('view-payslip', payslip)"
              class="px-3 py-1 bg-yellow-500 hover:bg-yellow-600 text-white text-xs font-medium rounded transition-colors duration-200"
            >
              Payslip
            </button>
          </td>
          <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900 text-center">
            <button
              @click="$emit('print-payslip', payslip)"
              class="px-3 py-1 bg-blue-500 hover:bg-blue-600 text-white text-xs font-medium rounded transition-colors duration-200"
            >
              Print
            </button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script>
export default {
  name: 'PayslipTable',
  props: {
    payslipRecords: {
      type: Array,
      default: () => []
    }
  },
  methods: {
    formatDate(dateString) {
      if (!dateString) return ''
      const date = new Date(dateString)
      return date.toLocaleDateString('en-US', {
        month: '2-digit',
        day: '2-digit',
        year: 'numeric'
      })
    }
  }
}
</script>