<template>
  <div class="datetime-filter">
    <div class="datetime-filter-label">Date & Time:</div>
    <div class="datetime-inputs">
      <!-- Date Range -->
      <div class="date-range-group">
        <el-date-picker
          v-model="localValue.dateFrom"
          type="date"
          placeholder="From Date"
          format="YYYY-MM-DD"
          value-format="YYYY-MM-DD"
          class="date-picker"
          clearable
          @change="handleChange"
        />
        <span class="separator">to</span>
        <el-date-picker
          v-model="localValue.dateTo"
          type="date"
          placeholder="To Date"
          format="YYYY-MM-DD"
          value-format="YYYY-MM-DD"
          class="date-picker"
          clearable
          @change="handleChange"
        />
      </div>
      
      <!-- Time Range (optional) -->
      <div v-if="showTime" class="time-range-group">
        <el-time-picker
          v-model="localValue.timeFrom"
          placeholder="From Time"
          format="HH:mm"
          value-format="HH:mm"
          class="time-picker"
          clearable
          @change="handleChange"
        />
        <span class="separator">to</span>
        <el-time-picker
          v-model="localValue.timeTo"
          placeholder="To Time"
          format="HH:mm"
          value-format="HH:mm"
          class="time-picker"
          clearable
          @change="handleChange"
        />
      </div>
      
      <!-- Clear Button -->
      <el-button
        v-if="hasValue"
        text
        circle
        size="small"
        @click="clearFilter"
        class="clear-btn"
        title="Clear date/time filter"
      >
        <el-icon><Close /></el-icon>
      </el-button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { Close } from '@element-plus/icons-vue'

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({
      dateFrom: null,
      dateTo: null,
      timeFrom: null,
      timeTo: null
    })
  },
  showTime: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update:modelValue', 'change'])

const localValue = ref({
  dateFrom: props.modelValue?.dateFrom || null,
  dateTo: props.modelValue?.dateTo || null,
  timeFrom: props.modelValue?.timeFrom || null,
  timeTo: props.modelValue?.timeTo || null
})

const hasValue = computed(() => {
  return localValue.value.dateFrom || 
         localValue.value.dateTo || 
         localValue.value.timeFrom || 
         localValue.value.timeTo
})

const handleChange = () => {
  emit('update:modelValue', { ...localValue.value })
  emit('change', { ...localValue.value })
}

const clearFilter = () => {
  localValue.value = {
    dateFrom: null,
    dateTo: null,
    timeFrom: null,
    timeTo: null
  }
  handleChange()
}

// Watch for external changes
watch(() => props.modelValue, (newValue) => {
  if (newValue) {
    localValue.value = {
      dateFrom: newValue.dateFrom || null,
      dateTo: newValue.dateTo || null,
      timeFrom: newValue.timeFrom || null,
      timeTo: newValue.timeTo || null
    }
  }
}, { deep: true })
</script>

<style scoped>
.datetime-filter {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}

.datetime-filter-label {
  font-size: 14px;
  color: #606266;
  font-weight: 500;
  white-space: nowrap;
  flex-shrink: 0;
}

.datetime-inputs {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.date-range-group,
.time-range-group {
  display: flex;
  align-items: center;
  gap: 8px;
}

.date-picker {
  width: 140px;
}

.time-picker {
  width: 120px;
}

.separator {
  font-size: 12px;
  color: #606266;
  white-space: nowrap;
}

.clear-btn {
  color: #909399;
  padding: 4px;
}

.clear-btn:hover {
  color: #409eff;
}

/* Responsive adjustments */
@media (max-width: 1366px) {
  .date-picker {
    width: 130px;
  }
  .time-picker {
    width: 110px;
  }
}

@media (max-width: 768px) {
  .datetime-filter {
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
  }
  
  .datetime-inputs {
    width: 100%;
    flex-direction: column;
    align-items: stretch;
    gap: 8px;
  }
  
  .date-range-group,
  .time-range-group {
    width: 100%;
    flex-direction: column;
    align-items: stretch;
    gap: 8px;
  }
  
  .date-picker,
  .time-picker {
    width: 100%;
  }
  
  .separator {
    display: none;
  }
}

@media (max-width: 480px) {
  .datetime-filter-label {
    font-size: 13px;
  }
}
</style>

