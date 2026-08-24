<template>
  <MainLayout>
    <template #header>
      <div class="title">Payroll Setup</div>
    </template>

    <div class="payroll-cards">
      <MiniServiceCard 
        v-for="item in payrollItems" 
        :key="item.to"
        :to="item.to"
        :title="item.label"
        :gradient="item.gradient"
        :description="item.desc"
      />
    </div>
  </MainLayout>
</template>

<script setup>
import MainLayout from '../../Layout/MainLayout.vue'
import MiniServiceCard from '../../components/MiniServiceCard.vue'


// TODO: Create your payrollItems array here
// Example structure (you'll fill this in):
// const payrollItems = [
//   { to: '/payroll-setup/salary-structure', label: 'Salary Structure', gradient: 'linear-gradient(135deg,#ef4444,#f97316)', desc: 'Define salary components' },
//   { to: '/payroll-setup/benefits', label: 'Benefits Setup', gradient: 'linear-gradient(135deg,#3b82f6,#22d3ee)', desc: 'Employee benefits configuration' },
//   // Add more items here...
// ]


const payrollItems =[
  { to: '/payroll-setup/salary-schedule-setup', label: 'Salary Schedule Setup', gradient: 'linear-gradient(135deg,#ef4444,#f97316)', desc: 'Define salary components' },
  { to: '/payroll-setup/tax-table-setup', label: 'Tax Table Setup', gradient: 'linear-gradient(135deg,#3b82f6,#22d3ee)', desc: 'Employee benefits configuration' },
  { to: '/payroll-setup/HDMF Table Setup', label: 'Income and Deductions', gradient: 'linear-gradient(135deg,#10b981,#06b6d4)', desc: 'Employee benefits configuration' },
  { to: '/payroll-setup/Philhealth Table Setup', label: 'Philhealth Table Setup', gradient: 'linear-gradient(135deg,#f59e0b,#f97316)', desc: 'Employee benefits configuration' },
  { to: '/payroll-setup/SSS Table Setup', label: 'SSS Table Setup', gradient: 'linear-gradient(135deg,#7c3aed,#4f46e5)', desc: 'Employee benefits configuration' },
  { to: '/payroll-setup/Salary Step Setup', label: 'Salary Step Setup', gradient: 'linear-gradient(135deg,#60a5fa,#22d3ee)', desc: 'Employee benefits configuration' },
{ to: '/payroll-setup/salary-grade-setup', label: 'Salary Grade Setup', gradient: 'linear-gradient(135deg,#0ea5e9,#22d3ee)', desc: 'Employee benefits configuration' },
{ to: '/payroll-setup/income-setup', label: 'Income Setup', gradient: 'linear-gradient(135deg,#a855f7,#ec4899)', desc: 'Employee benefits configuration' },
{ to: '/payroll-setup/deduction-setup', label: 'Deduction Setup', gradient: 'linear-gradient(135deg,#a855f7,#ec4899)', desc: 'Employee benefits configuration' },
{ to: '/payroll-setup/deduction-priority-setup', label: 'Deduction Priority Setup', gradient: 'linear-gradient(135deg,#a855f7,#ec4899)', desc: 'Employee benefits configuration' },
  { to: '/payroll-setup/payroll-interval-setup', label: 'Payroll Interval Setup', gradient: 'linear-gradient(135deg,#a855f7,#ec4899)', desc: 'Employee benefits configuration' },
  { to: '/payroll-setup/payroll-cutoff-setup', label: 'Payroll Cut-off Setup', gradient: 'linear-gradient(135deg,#a855f7,#ec4899)', desc: 'Employee benefits configuration' },
  { to: '/payroll-setup/loyalty-award-setup', label: 'Loyalty Award Setup', gradient: 'linear-gradient(135deg,#a855f7,#ec4899)', desc: 'Employee benefits configuration' },
  { to: '/payroll-setup/uniform-and-clothing-allowance-setup', label: 'Uniform and Clothing Allowance Setup', gradient: 'linear-gradient(135deg,#a855f7,#ec4899)', desc: 'Employee benefits configuration' },
  { to: '/payroll-setup/rata-positions-setup', label: 'RATA Positions Setup', gradient: 'linear-gradient(135deg,#a855f7,#ec4899)', desc: 'Employee benefits configuration' },
  { to: '/payroll-setup/rata-table-setup', label: 'Pending Deduction Setup', gradient: 'linear-gradient(135deg,#a855f7,#ec4899)', desc: 'Employee benefits configuration' },
  { to: '/payroll-setup/hazard-pay-setup', label: 'Hazard Pay Setup', gradient: 'linear-gradient(135deg,#a855f7,#ec4899)', desc: 'Employee benefits configuration' },
  { to: '/payroll-setup/overtime-tax-table-setup', label: 'Overtime Tax Table Setup', gradient: 'linear-gradient(135deg,#a855f7,#ec4899)', desc: 'Employee benefits configuration' },
  { to: '/payroll-setup/mid-year-bonus-table-setup', label: 'Mid Year Bonus Table Setup', gradient: 'linear-gradient(135deg,#a855f7,#ec4899)', desc: 'Employee benefits configuration' },
  { to: '/payroll-setup/year-end-bonus-table-setup', label: 'Year End Bonus Table Setup', gradient: 'linear-gradient(135deg,#a855f7,#ec4899)', desc: 'Employee benefits configuration' },
  { to: '/payroll-setup/cash-gift-table-setup', label: 'Cash Gift Table Setup', gradient: 'linear-gradient(135deg,#a855f7,#ec4899)', desc: 'Employee benefits configuration' },
  { to: '/payroll-setup/monetization-setup', label: 'Monetization Setup', gradient: 'linear-gradient(135deg,#a855f7,#ec4899)', desc: 'Employee benefits configuration' }



]
</script>

<style scoped>
.title { 
  font-weight: 600; 
}

/* TODO: Add styles for your payroll cards grid */
.payroll-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 20px;
}
</style>


