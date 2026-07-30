<template>
  <PageScaffold
    title="Process Attendance"
    subtitle="Process and manage employee attendance records and calculations"
    :breadcrumbs="[{ label: 'Timekeeping Module', to: '/' }, { label: 'Process Attendance' }]"
  >
    <el-tabs v-model="activeTab" class="process-attendance-tabs" type="border-card">
      <!-- ─── Regular Tab ─── -->
      <el-tab-pane label="Regular" name="regular">
        <ProcessAttendanceTab type="regular" storage-key="process_attendance_state_regular" />
      </el-tab-pane>

      <!-- ─── COS Tab ─── -->
      <el-tab-pane label="COS" name="cos">
        <ProcessAttendanceTab type="cos" storage-key="process_attendance_state_cos" />
      </el-tab-pane>
    </el-tabs>
  </PageScaffold>
</template>

<script setup>
import { ref } from 'vue'
import PageScaffold from '../../components/Reusable_Components/PageScaffold.vue'
import ProcessAttendanceTab from '../../components/Process_Attendance/ProcessAttendanceTab.vue'

const activeTab = ref('regular')
</script>

<style scoped>
.process-attendance-tabs {
  border-radius: 8px;
  overflow: hidden;
}

/* Give the tab pane some breathing room */
:deep(.el-tab-pane) {
  padding: 16px 0 0;
}
</style>