<template>
  <div class="chart">
    <div class="bar" v-for="d in data" :key="d.day" :style="{ height: d.value + 'px' }" :title="d.day + ': ' + d.value" />
  </div>
</template>

<script setup>
const props = defineProps({
  data: { type: Array, default: () => [] }
})
</script>

<style scoped>
.chart { display: grid; grid-auto-flow: column; align-items: end; gap: 8px; height: 160px; background: #fff; border: 1px dashed #e5e7eb; border-radius: 12px; padding: 10px; }
.bar { width: 24px; background: linear-gradient(180deg,#60a5fa,#3b82f6); border-radius: 6px; }
</style>


