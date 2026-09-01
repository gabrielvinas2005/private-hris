<script setup>
// Removed: PhilHealth PMRF PDF form filling is not used in Private HRIS.
// PhilHealth contribution reporting is handled via rptPhilhealthRemittance.
import { onMounted } from "vue";
import { useRouter } from "vue-router";
const router = useRouter();
onMounted(() => router.replace("/"));
</script>
<template><div /></template>
