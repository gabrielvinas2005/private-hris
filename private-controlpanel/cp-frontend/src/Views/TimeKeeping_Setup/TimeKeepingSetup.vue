<template>
  <MainLayout>
    <template #header>
      <div class="title">Time Keeping Setup</div>
    </template>

    <section class="grid">
      <RouterLink v-for="i in items" :key="i.to" :to="i.to" class="link-reset">
        <MiniServiceCard :title="i.label" :gradient="i.gradient" :description="i.desc" />
      </RouterLink>
    </section>
  </MainLayout>
</template>

<script setup>
import MainLayout from '../../Layout/MainLayout.vue'
import MiniServiceCard from '../../components/MiniServiceCard.vue'

const items = [
  { to: '/timekeeping-setup/overtime-types', label: 'Overtime Types Setup', gradient: 'linear-gradient(135deg,#ef4444,#f97316)', desc: 'Define OT categories' },
  { to: '/timekeeping-setup/holiday-types', label: 'Holiday Types Setup', gradient: 'linear-gradient(135deg,#3b82f6,#22d3ee)', desc: 'Regular/Special types' },
  { to: '/timekeeping-setup/holidays', label: 'Holidays Setup', gradient: 'linear-gradient(135deg,#10b981,#06b6d4)', desc: 'Calendar of holidays' },
  { to: '/timekeeping-setup/leave-types', label: 'Leave Types Setup', gradient: 'linear-gradient(135deg,#f59e0b,#f97316)', desc: 'Sick, vacation, etc.' },
  { to: '/timekeeping-setup/official-business-types', label: 'Official Business Types', gradient: 'linear-gradient(135deg,#7c3aed,#4f46e5)', desc: 'OB categories' },
  { to: '/timekeeping-setup/time-keeping', label: 'Logging Options', gradient: 'linear-gradient(135deg,#60a5fa,#22d3ee)', desc: 'Time in & clocking options' },
  { to: '/timekeeping-setup/approvers', label: 'Approver Setup', gradient: 'linear-gradient(135deg,#a855f7,#ec4899)', desc: 'Approval routes' }
]
</script>

<style scoped>
.title { font-weight: 600; }
.grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 16px; }
.link-reset { text-decoration: none; }
</style>


