<template>
  <div class="filters-container">
    <div class="filters-row">
      <div class="left">
        <el-input 
          v-model="searchQuery" 
          placeholder="Search by amount range or percentage..." 
          clearable 
          class="search-input"
          @input="handleSearch"
        />
        <el-select 
          v-model="selectedYear" 
          placeholder="Select Year" 
          @change="handleYearChange"
          class="year-select"
        >
          <el-option
            v-for="year in availableYears"
            :key="year"
            :label="year"
            :value="year"
          />
        </el-select>
      </div>
      <div class="actions">
        <el-button type="primary" @click="handleEdit">Edit Overtime Tax Table</el-button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'

const props = defineProps({
  search: {
    type: String,
    default: ''
  },
  currentYear: {
    type: Number,
    default: () => new Date().getFullYear()
  }
})

const emit = defineEmits(['search-change', 'year-change', 'edit'])

const searchQuery = ref(props.search)
const selectedYear = ref(props.currentYear)

// Generate available years (current year ± 5 years)
const availableYears = computed(() => {
  const currentYear = new Date().getFullYear()
  const years = []
  for (let i = currentYear - 5; i <= currentYear + 5; i++) {
    years.push(i)
  }
  return years
})

function handleSearch() {
  emit('search-change', searchQuery.value)
}

function handleYearChange(year) {
  emit('year-change', year)
}

function handleEdit() {
  emit('edit')
}

// Watch for prop changes
watch(() => props.search, (newSearch) => {
  searchQuery.value = newSearch
})

watch(() => props.currentYear, (newYear) => {
  selectedYear.value = newYear
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
}

.left {
  display: flex;
  gap: 10px;
  align-items: center;
}

.search-input {
  width: 380px;
}

.year-select {
  width: 120px;
}

.actions {
  display: flex;
  gap: 8px;
}
</style>
