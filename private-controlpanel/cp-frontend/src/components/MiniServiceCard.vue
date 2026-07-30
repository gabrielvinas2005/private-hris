<template>
  <RouterLink :to="to" class="mini-link">
    <div class="mini">
      <div class="top" :style="{ background: gradient }">
        <div class="icon" />
        <div class="title">{{ title }}</div>
      </div>
      <div class="bottom">
        <div class="desc">{{ description }}</div>
      </div>
    </div>
  </RouterLink>
</template>

<script setup>
const props = defineProps({
  to: { type: String, required: true },
  title: { type: String, required: true },
  gradient: { type: String, default: 'linear-gradient(135deg,#60a5fa,#22d3ee)' },
  description: { type: String, default: 'Quick access' }
})
</script>

<style scoped>
.mini-link {
  text-decoration: none;
  color: inherit;
  display: block;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.mini-link:hover {
  transform: translateY(-2px);
}

.mini-link:hover .mini {
  box-shadow: 0 12px 24px rgba(0,0,0,.12);
}

.mini { 
  border-radius: 16px; 
  overflow: hidden; 
  box-shadow: 0 6px 18px rgba(0,0,0,.08);
  transition: box-shadow 0.2s ease;
}
.top { color: #fff; padding: 18px 16px; display: grid; justify-items: center; gap: 10px; min-height: 120px; }
.icon { height: 36px; width: 36px; border-radius: 9999px; background: rgba(255,255,255,.25); }
.title { font-weight: 600; text-align: center; }
.bottom { background: #ffffff; border-top: 1px dashed #e5e7eb; padding: 12px 14px; }
.desc { color: #64748b; font-size: 12px; text-align: center; }
</style>


