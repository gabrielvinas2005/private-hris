<template>
  <div class="card">
    <div class="top-section" :style="{ background: gradient }">
      <div class="icon">
        <slot name="icon">
          <el-icon :size="32">
            <component :is="icon" />
          </el-icon>
        </slot>
      </div>
      <div class="title">{{ title }}</div>
    </div>
    <div class="bottom-section">
      <div class="description">{{ description }}</div>
      <div class="actions">
        <button class="select">Select</button>
      </div>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  title: { type: String, required: true },
  description: { type: String, required: true },
  gradient: { type: String, default: 'linear-gradient(135deg,#f43f5e,#f97316)' },
  icon: { type: String, default: 'Menu' }
})
</script>

<style scoped>
.card {
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 8px 24px rgba(0,0,0,.08);
  display: grid;
  grid-template-rows: auto 1fr;
  min-height: 210px;
}

.top-section {
  color: #fff;
  padding: 28px 24px 16px 24px;
  display: grid;
  align-content: center;
  text-align: center;
}

.icon { 
  height: 64px; 
  width: 64px; 
  border-radius: 9999px; 
  background: rgba(255,255,255,.2); 
  display: grid; 
  place-items: center; 
  margin: 0 auto 12px; 
}

.default-icon {
  width: 32px;
  height: 32px;
  background: rgba(255,255,255,.3);
  border-radius: 50%;
}

.title { 
  text-align: center; 
  font-weight: 600; 
  font-size: 16px;
}

.bottom-section {
  background: #ffffff;
  padding: 16px 24px;
  display: grid;
  align-content: space-between;
  border-top: 1px dashed #e5e7eb;
}

.description {
  color: #64748b;
  font-size: 12px;
  text-align: center;
  line-height: 1.4;
  margin-bottom: 12px;
}

.actions { 
  display: grid; 
  justify-content: center; 
}

.select { 
  background: transparent; 
  border: 1px solid #d1d5db; 
  color: #374151; 
  padding: 8px 16px; 
  border-radius: 9999px; 
  cursor: pointer; 
  font-size: 12px;
  font-weight: 500;
  transition: all 0.2s ease;
}

.select:hover { 
  background: #f3f4f6; 
  border-color: #9ca3af;
}
</style>


