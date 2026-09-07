<template>
  <MainLayout>
    <template #header>
      <div class="page-header"><div class="title">GSIS Table Setup</div></div>
    </template>
    <el-card shadow="never">
      <el-result
        icon="info"
        title="Module Decommissioned"
        sub-title="GSIS management has been removed from Payroll Setup for Private HRIS."
      />
    </el-card>
  </MainLayout>
</template>

<script setup>
import MainLayout from '../../../Layout/MainLayout.vue'
</script>

<style scoped>
.page-header { display: flex; align-items: center; }
.title { font-weight: 600; font-size: 18px; }
</style>