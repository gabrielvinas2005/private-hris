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
  { index: 1, title: "First Signatory:" },
  { index: 2, title: "Second Signatory:" },
  { index: 3, title: "Third Signatory:" },
  { index: 4, title: "Fourth Signatory:" },
  { index: 5, title: "Fifth Signatory:", span: 12, offset: 6 },
];
</script>
