<template>
  <MainLayout :breadcrumbs="breadcrumbs">
    <h2 class="text-2xl font-bold text-slate-900 mb-8 text-center">Payroll & Compensation</h2>
    <IconGrid :items="items" />
  </MainLayout>
</template>

<script>
import MainLayout from '../layout/MainLayout.vue'
import IconGrid from '../components/IconGrid.vue'
import { PayslipIcon } from '../assets/icons/index.js'

export default {
  name: 'PayrollCompensation',
  components: { MainLayout, IconGrid },
  data() {
    return {
      breadcrumbs: [
        { name: 'Dashboard', path: '/dashboard' },
        { name: 'Payroll & Compensation', path: '/payroll-compensation' }
      ],
      items: [
        {
          icon: PayslipIcon,
          label: 'View My Payslip',
          description: 'Check your salary',
          bgClass: 'bg-purple-50',
          hoverBgClass: 'group-hover:bg-purple-100',
          iconColor: 'text-purple-600',
          onClick: () => this.$router.push('/payslip')
        }
      ]
    }
  }
}
</script>