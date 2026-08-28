<template>
  <MainLayout :breadcrumbs="breadcrumbs">
    <div class="w-full h-full max-w-[1400px] mx-auto">
      <WFHSection />
    </div>
  </MainLayout>
</template>

<script>
import MainLayout from '../../layout/MainLayout.vue'
import WFHSection from '../../components/time-attendance/WFHSection.vue'

export default {
  name: 'WFHApplicationView',
  components: {
    MainLayout,
    WFHSection
  },
  data() {
    return {
      breadcrumbs: [
        { name: 'Dashboard', path: '/dashboard' },
        { name: 'Time & Attendance', path: '/time-attendance' },
        { name: 'WFH Application', path: '/wfh-application' }
      ]
    }
  }
}
</script>
