<template>
  <MainLayout :breadcrumbs="breadcrumbs">
    <h2 class="text-2xl font-bold text-slate-900 mb-8 text-center">Overtime & Scheduling</h2>
    <IconGrid :items="items" />
  </MainLayout>
</template>

<script>
import MainLayout from '../layout/MainLayout.vue'
import IconGrid from '../components/IconGrid.vue'
import { OTIcon } from '../assets/icons/index.js'

export default {
  name: 'OvertimeScheduling',
  components: { MainLayout, IconGrid },
  data() {
    return {
      breadcrumbs: [
        { name: 'Dashboard', path: '/dashboard' },
        { name: 'Overtime & Scheduling', path: '/overtime-scheduling' }
      ],
      items: [
        {
          icon: OTIcon,
          label: 'Request Overtime',
          description: 'Apply for extra hours',
          bgClass: 'bg-orange-50',
          hoverBgClass: 'group-hover:bg-orange-100',
          iconColor: 'text-orange-600',
          onClick: () => this.$router.push('/overtime-request')
        }
      ]
    }
  }
}
</script>