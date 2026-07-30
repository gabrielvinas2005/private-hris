<template>
  <SignatoriesGridSection
    :signatories="signatories"
    :employee-options="employeeOptions"
    :sections="sections"
  />
</template>

<script setup>
import SignatoriesGridSection from "../shared/SignatoriesGridSection.vue";

defineProps({
  signatories: { type: Object, required: true },
  employeeOptions: { type: Array, default: () => [] },
});

const sections = [
  { index: 1, title: "CERTIFIED: Services have been duly rendered as stated above." },
  { index: 2, title: "CERTIFIED:" },
  { index: 3, title: "CERTIFIED: Funds available." },
  { index: 4, title: "APPROVED FOR PAYMENT:" },
  {
    index: 5,
    title: "CERTIFIED: each employee whose name appears above has been paid the amount opposite his/her name.",
    span: 12,
    offset: 6,
  },
];
</script>
