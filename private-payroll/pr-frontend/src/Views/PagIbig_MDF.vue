<script setup>
// Removed: Pag-IBIG MDF form filling not used in Private HRIS.
// Pag-IBIG contributions are tracked via HDMF Premium module.
import { onMounted } from "vue";
import { useRouter } from "vue-router";
const router = useRouter();
onMounted(() => router.replace("/"));
</script>
<template><div /></template>
