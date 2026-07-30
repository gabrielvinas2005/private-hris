<template>
  <div class="filters-container">
    <div class="filters-row">
      <div class="left">
        <el-input 
          v-model="searchQuery" 
          placeholder="Search by user, module, menu, activity, or description..." 
          clearable 
          class="search-input"
          @input="handleSearch"
        />
        <el-date-picker
          v-model="startDate"
          type="date"
          placeholder="From Date"
          format="YYYY-MM-DD"
          value-format="YYYY-MM-DD"
          @change="handleStartDateChange"
          class="date-picker"
        />
        <el-date-picker
          v-model="endDate"
          type="date"
          placeholder="To Date"
          format="YYYY-MM-DD"
          value-format="YYYY-MM-DD"
          @change="handleEndDateChange"
          class="date-picker"
        />
      </div>
      <div class="actions">
        <el-button @click="handleReset">Reset Filters</el-button>
        <el-button type="primary" @click="handleRefresh">Refresh</el-button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  search: {
    type: String,
    default: ''
  },
  startDate: {
    type: String,
    default: ''
  },
  endDate: {
    type: String,
    default: ''
  }
})

const emit = defineEmits(['search-change', 'date-range-change', 'reset', 'refresh'])

const searchQuery = ref(props.search)
const startDate = ref(props.startDate)
const endDate = ref(props.endDate)

function handleSearch() {
  emit('search-change', searchQuery.value)
}

function handleStartDateChange(date) {
  startDate.value = date
  emit('date-range-change', date, endDate.value)
}

function handleEndDateChange(date) {
  endDate.value = date
  emit('date-range-change', startDate.value, date)
}

function handleReset() {
  searchQuery.value = ''
  startDate.value = ''
  endDate.value = ''
  emit('reset')
}

function handleRefresh() {
  emit('refresh')
}

// Watch for prop changes
watch(() => props.search, (newSearch) => {
  searchQuery.value = newSearch
})

watch(() => props.startDate, (newStart) => {
  startDate.value = newStart
})

watch(() => props.endDate, (newEnd) => {
  endDate.value = newEnd
})
</script>

<style scoped>
.filters-container {
  width: 100%;
}

.filters-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
}

.left {
  display: flex;
  gap: 12px;
  align-items: center;
  flex: 1;
}

.search-input {
  width: 400px;
  min-width: 300px;
}

.date-picker {
  width: 140px;
}

.actions {
  display: flex;
  gap: 8px;
  flex-shrink: 0;
}

@media (max-width: 768px) {
  .filters-row {
    flex-direction: column;
    align-items: stretch;
  }
  
  .left {
    flex-direction: column;
    gap: 8px;
  }
  
  .search-input {
    width: 100%;
  }
  
  .date-picker {
    width: 48%;
  }
  
  .actions {
    justify-content: center;
  }
}
</style>
