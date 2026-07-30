<template>
  <div class="card" :style="{ background: gradient }">
    <div class="icon">
      <slot name="icon" />
    </div>
    <div class="title">{{ title }}</div>
    <div class="meta">
      <div class="meta-item">⏱ 24h 31minute</div>
      <div class="meta-item">⭐ 4.5 rates</div>
    </div>
    <div class="actions">
      <button class="select">Select</button>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  title: { type: String, required: true },
  gradient: { type: String, default: 'linear-gradient(135deg,#f43f5e,#f97316)' }
})
</script>

<style scoped>
.card {
  border-radius: 16px;
  color: #fff;
  padding: 28px 24px 16px 24px;
  display: grid;
  align-content: space-between;
  min-height: 210px;
  box-shadow: 0 8px 24px rgba(0,0,0,.08);
}
.icon { height: 64px; width: 64px; border-radius: 9999px; background: rgba(255,255,255,.2); display: grid; place-items: center; margin: 0 auto 12px; }
.title { text-align: center; font-weight: 600; }
.meta { margin-top: 18px; border-top: 1px dashed rgba(255,255,255,.4); display: grid; grid-auto-flow: column; justify-content: space-between; padding-top: 10px; font-size: 12px; opacity: .9; }
.actions { display: grid; justify-content: center; margin-top: 12px; }
.select { background: rgba(255,255,255,.15); border: 1px solid rgba(255,255,255,.35); color: #fff; padding: 8px 16px; border-radius: 9999px; cursor: pointer; }
.select:hover { background: rgba(255,255,255,.25); }
</style>


