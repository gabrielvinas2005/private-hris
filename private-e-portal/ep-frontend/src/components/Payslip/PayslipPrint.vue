<template>
  <div class="print-container" style="font-family: 'Plus Jakarta Sans', 'Inter', sans-serif; font-size: 14px; width: 210mm; margin: 0 auto;">
    <div style="margin: 0px 130px 0px 130px;">
      <div style="text-align: center;">
        <span style="display: inline-block;">
          <img :src="logo1 ? `data:image/png;base64,${logo1}` : '/dist/img/employee_profile.png'" width="60" height="60" />
        </span>
        <span style="display: inline-block; margin-left: 25px; margin-right: 25px;">
          <p>Republic of the Philippines</p>
          <p>{{ company?.name }}</p>
        </span>
        <span style="display: inline-block;">
          <img :src="logo2 ? `data:image/png;base64,${logo2}` : '/dist/img/employee_profile.png'" width="60" height="60" />
        </span>
      </div>
      <div style="text-align: center">
        <h4 style="font-size: 18px; font-weight: bold;">PAYSLIP</h4>
      </div>
      <div>
        <p style="display: inline-block; width: 100px;">NAME:</p>
        <p style="display: inline-block; font-weight: bold;">{{ payslip?.name?.toUpperCase() }}</p>
      </div>
      <div>
        <p style="display: inline-block; width: 100px;">OFFICE:</p>
        <p style="display: inline-block;">{{ payslip?.department?.toUpperCase() }}</p>
      </div>
      <div>
        <p style="display: inline-block; width: 100px;">PERIOD:</p>
        <p style="display: inline-block;">{{ payslip?.payroll_period?.toUpperCase() }}</p>
      </div>
      <div>
        <p style="display: inline-block; width: 320px;">MONTHLY RATE:</p>
        <p style="display: inline-block; width: 100px; text-align: right;">{{ formatAmount(payslip?.salary) }}</p>
      </div>
      <div>
        <p style="display: inline-block; width: 320px;">HOLIDAY PAY:</p>
        <p style="display: inline-block; width: 100px; text-align: right;">{{ formatAmount(payslip?.holiday_pay) }}</p>
      </div>
      <div v-for="income in incomes" :key="income.income">
        <p style="display: inline-block; width: 320px;">{{ income.income?.toUpperCase() }}:</p>
        <p style="display: inline-block; width: 100px; text-align: right;">{{ formatAmount(income.amount) }}</p>
      </div>
      <div>
        <p style="display: inline-block; width: 320px;">TOTAL</p>
        <p style="display: inline-block; width: 100px; text-align: right; font-weight: bold; border-top: 1px solid black;">
          {{ formatAmount((payslip?.total_income || 0) + (payslip?.holiday_pay || 0) + (payslip?.salary || 0)) }}
        </p>
        <p style="display: inline-block; width: 320px;">DEDUCTIONS</p>
      </div>
      <div>
        <table style="width: 75%; border: none; margin-bottom: 20px; margin-left: 35px;">
          <tbody>
            <tr>
              <td style="width: 20px;">1</td>
              <td style="width: 100px">LATE/UT/LWOP</td>
              <td style="text-align: right; width: 100px;">
                {{ formatAmount((payslip?.tardiness_amount || 0) + (payslip?.lwop_amount || 0)) }}
              </td>
            </tr>
            <tr>
              <td style="width: 20px;">2</td>
              <td style="width: 100px">WTAX</td>
              <td style="text-align: right; width: 100px;">
                {{ formatAmount(payslip?.tax) }}</td>
            </tr>
            <tr>
              <td>3</td>
              <td>GSIS</td>
              <td style="text-align: right">{{ formatAmount(payslip?.gsis) }}</td>
            </tr>
            <tr>
              <td>4</td>
              <td>HDMF P/S</td>
              <td style="text-align: right">{{ formatAmount(payslip?.pagibig) }}</td>
            </tr>
            <tr>
              <td>5</td>
              <td>PHIC P/S</td>
              <td style="text-align: right">{{ formatAmount(payslip?.philhealth) }}</td>
            </tr>
            <tr v-for="(deduction, idx) in deductions" :key="deduction.deduction">
              <td>{{ 6 + idx }}</td>
              <td>{{ deduction.deduction?.toUpperCase() }}</td>
              <td style="text-align: right">{{ formatAmount(deduction.amount) }}</td>
            </tr>
            <tr>
              <td colspan="2" style="border-top: 1px solid black">Total Deduction: </td>
              <td style="text-align: right; border-top: 1px solid black;">
                {{ formatAmount(
                  (payslip?.total_deduction || 0) + (payslip?.tax || 0) + (payslip?.gsis || 0) + (payslip?.pagibig || 0) + (payslip?.philhealth || 0) + (payslip?.tardiness_amount || 0) + (payslip?.lwop_amount || 0) + (deductions?.reduce((sum, d) => sum + (d.amount || 0), 0) || 0)
                ) }}
              </td>
            </tr>
          </tbody>
          <tfoot style="border: 1px solid black">
            <tr>
              <td colspan="2">NET AMOUNT RECEIVED</td>
              <td style="font-weight: bold; text-align: right;">
                {{ formatAmount(payslip?.net_pay) }}
              </td>
            </tr>
          </tfoot>
        </table>
      </div>
      <div style="margin-top: 35px; width: 60%; margin-left: 30%;">
        <p>CERTIFIED CORRECT:</p>
        <div style="margin-top: 40px; text-align: center;">
          <p style="font-weight: bold;">
            {{ signatory?.name?.toUpperCase() || 'ATTY. FARIDA D. ROMILLO-MATEO' }}
          </p>
          <p>{{ signatory?.position || 'Municipal Accountant' }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'PayslipPrint',
  props: {
    payslip: Object,
    company: Object,
    logo1: String,
    logo2: String,
    incomes: Array,
    deductions: Array,
    signatory: Object
  },
  methods: {
    formatAmount(amount) {
      if (typeof amount !== 'number') return ''
      const value = amount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
      return `₱${value}`
    }
  }
}
</script>