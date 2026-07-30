<template>
  <PageScaffold
    title="Tardiness Reports"
    subtitle="Generate and analyze tardiness reports and attendance patterns"
    :breadcrumbs="[{ label: 'Timekeeping Reports', to: '/' }, { label: 'Tardiness Reports' }]"
  >
    <el-form :inline="true" class="mb-3">
      <el-form-item label="Period">
        <el-date-picker type="daterange" placeholder="Select date range" />
      </el-form-item>
      <el-form-item label="Format">
        <el-select placeholder="Select format">
          <el-option label="PDF" value="pdf" />
          <el-option label="Excel" value="excel" />
        </el-select>
      </el-form-item>
      <el-form-item>
        <el-button type="primary">Generate Report</el-button>
      </el-form-item>
    </el-form>
    <el-empty description="No tardiness data available for reporting" />
  </PageScaffold>
</template>

<script setup>
import PageScaffold from '../../components/Reusable_Components/PageScaffold.vue'
</script>

<style scoped>
</style>

