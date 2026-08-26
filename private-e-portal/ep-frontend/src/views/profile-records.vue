<template>
  <MainLayout :breadcrumbs="breadcrumbs">
    <h2 class="text-2xl font-bold text-slate-900 mb-8 text-center">My Profile & Records</h2>
    <IconGrid :items="items" />
  </MainLayout>
</template>

<script>
import MainLayout from '../layout/MainLayout.vue'
import IconGrid from '../components/IconGrid.vue'
import { FileIcon, CompetencyIcon } from '../assets/icons/index.js'

export default {
  name: 'ProfileRecords',
  components: { MainLayout, IconGrid },
  data() {
    return {
      breadcrumbs: [
        { name: 'Dashboard', path: '/dashboard' },
        { name: 'My Profile & Records', path: '/profile-records' }
      ],
      items: [
        {
          icon: FileIcon,
          label: 'My 201 File',
          description: 'View and update your 201 file',
          bgClass: 'bg-blue-50',
          hoverBgClass: 'group-hover:bg-blue-100',
          iconColor: 'text-blue-600',
          onClick: () => this.$router.push('/201-file')
        },
        {
          icon: CompetencyIcon,
          label: 'My Competencies',
          description: 'View your competencies',
          bgClass: 'bg-green-50',
          hoverBgClass: 'group-hover:bg-green-100',
          iconColor: 'text-green-600',
          onClick: () => this.$router.push('/competencies/my-competencies')
        }
      ]
    }
  }
}
</script>