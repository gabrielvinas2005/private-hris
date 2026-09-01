<script setup>
// Removed: GSIS remittance is a public-sector report not applicable to Private HRIS.
import { onMounted } from "vue";
import { useRouter } from "vue-router";
const router = useRouter();
onMounted(() => router.replace("/"));
</script>
<template><div /></template>
