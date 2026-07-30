<template>
  <SignatoriesGridSection
    :signatories="signatories"
    :employee-options="employeeOptions"
    :sections="sections"
    :min-height="200"
  />
</template>

<script setup>
import SignatoriesGridSection from "../shared/SignatoriesGridSection.vue";

defineProps({
  signatories: { type: Object, required: true },
  employeeOptions: { type: Array, default: () => [] },
});

const sections = [
  {
    index: 1,
    title: "I CERTIFY on my oath that the above Payroll is Correct and that the services have been duly rendered as stated.",
  },
  {
    index: 2,
    title: "APPROVED, payable for appropriation for P __________",
  },
];
</script>
