<template>
  <PageScaffold
    title="Payroll Income and Deduction"
    subtitle="Set per-employee amounts for incomes and deductions"
    :breadcrumbs="[
      { label: 'Home', to: '/' },
      { label: 'Payroll Income and Deduction' },
    ]"
  >
    <IncomeDeductionPanel />
  </PageScaffold>
</template>

<script setup>
import PageScaffold from "../../components/PageScaffold.vue";
import IncomeDeductionPanel from "../../components/Income_and_Deduction/IncomeDeductionPanel.vue";
</script>

<style scoped>
.mb-3 {
  margin-bottom: 12px;
}
</style>
