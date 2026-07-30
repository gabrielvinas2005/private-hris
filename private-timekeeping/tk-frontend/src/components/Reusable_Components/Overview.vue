<template>
  <div class="overview-grid" :style="{ '--cols': items.length }">
    <el-card
      v-for="(item, idx) in items"
      :key="idx"
      class="ov-card"
      :class="[accentClass(item.type), { 'ov-clickable': isClickable(item) }]"
      shadow="hover"
      @click="isClickable(item) ? $emit('item-click', item, idx) : null"
    >
      <div class="ov-row">
        <el-icon class="ov-icon"><component :is="iconComponent(item.icon)" /></el-icon>
        <div class="ov-text">
          <div class="ov-num">{{ item.value }}</div>
          <div class="ov-label">{{ item.label }}</div>
        </div>
      </div>
    </el-card>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Clock, CircleCheck, CloseBold, RemoveFilled, Calendar, Warning, Timer } from '@element-plus/icons-vue'

defineEmits(['item-click'])

const props = defineProps({
  // items: [{ value: number|string, label: string, icon: 'Clock'|'CircleCheck'|'CloseBold'|'RemoveFilled'|'Calendar', type: 'pending'|'approved'|'disapproved'|'cancelled'|'today' }]
  items: { type: Array, default: () => [] },
  // When set, items with label in this array are clickable and emit item-click
  clickableLabels: { type: Array, default: () => [] }
})

const isClickable = (item) => props.clickableLabels.length > 0 && props.clickableLabels.includes(item.label)

const iconMap = { Clock, CircleCheck, CloseBold, RemoveFilled, Calendar, Warning, Timer }
const iconComponent = (name) => iconMap[name] || Clock

const accentClass = (type) => {
  switch (type) {
    case 'approved': return 'ov-approved'
    case 'disapproved': return 'ov-disapproved'
    case 'cancelled': return 'ov-cancelled'
    case 'expired': return 'ov-expired'
    case 'today': return 'ov-today'
    default: return 'ov-pending'
  }
}
</script>

<style scoped>
.overview-grid { display: grid; grid-template-columns: repeat(var(--cols, 6), 1fr); gap: 12px; margin-bottom: 16px; }
@media (max-width: 1400px) { .overview-grid { grid-template-columns: repeat(min(var(--cols, 6), 3), 1fr); } }
@media (max-width: 768px) { .overview-grid { grid-template-columns: repeat(min(var(--cols, 6), 2), 1fr); } }
@media (max-width: 480px) { .overview-grid { grid-template-columns: 1fr; } }
.ov-card { border-left: 4px solid #e5e7eb; min-height: 80px; }
:deep(.el-card__body) { padding: 16px; height: 100%; display: flex; align-items: center; }
.ov-row { display: flex; align-items: center; gap: 12px; width: 100%; }
.ov-icon { font-size: 24px; flex-shrink: 0; }
.ov-text { flex: 1; min-width: 0; }
.ov-text .ov-num { font-size: 24px; font-weight: 700; line-height: 1; margin-bottom: 2px; }
.ov-text .ov-label { color: #64748b; font-size: 13px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ov-card.ov-clickable { cursor: pointer; }
.ov-card.ov-clickable:hover { filter: brightness(0.98); }
.ov-pending { border-left-color: #f59e0b; }
.ov-approved { border-left-color: #10b981; }
.ov-disapproved { border-left-color: #ef4444; }
.ov-cancelled { border-left-color: #6b7280; }
.ov-expired { border-left-color: #a855f7; }
.ov-today { border-left-color: #3b82f6; }
</style>


