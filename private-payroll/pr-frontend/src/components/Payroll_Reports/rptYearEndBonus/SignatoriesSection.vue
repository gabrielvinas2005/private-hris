<template>
  <SignatoriesGridSection
    :signatories="signatories"
    :employee-options="employeeOptions"
    :sections="sections"
    :min-height="120"
  />
</template>

<script setup>
import SignatoriesGridSection from "../shared/SignatoriesGridSection.vue";

defineProps({
  signatories: { type: Object, required: true },
  employeeOptions: { type: Array, default: () => [] },
});

const sections = [
  { index: 1, title: "Specify Report Signatory 1:" },
  { index: 2, title: "Specify Report Signatory 2:" },
  { index: 3, title: "Specify Report Signatory 3:" },
  { index: 4, title: "Specify Report Signatory 4:" },
  { index: 5, title: "Specify Report Signatory 5:", span: 12, offset: 6 },
];
</script>
