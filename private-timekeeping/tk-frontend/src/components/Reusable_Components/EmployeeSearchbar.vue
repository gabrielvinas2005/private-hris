<template>
  <div class="employee-searchbar">
    <div class="search-container">
      <el-input
        v-model="searchValue"
        :placeholder="placeholder"
        clearable
        class="search-input"
        :class="{ 
          'has-filters': hasActiveFilters
        }"
        @input="onSearchInput"
        @clear="onClear"
      >
        <template #prefix>
          <el-icon class="search-icon"><Search /></el-icon>
        </template>
      </el-input>
    </div>

    <!-- Advanced Filters Panel -->
    <el-collapse-transition>
      <div v-if="showFiltersPanel" class="filters-panel">
        <el-form :inline="true" class="filter-form">
          <el-form-item v-if="showYearFilter" label="Year">
            <el-select
              v-model="selectedYear"
              placeholder="Select Year"
              class="filter-select"
              @change="onFilterChange"
            >
              <el-option
                v-for="year in availableYears"
                :key="year"
                :label="String(year)"
                :value="year"
              />
            </el-select>
          </el-form-item>
        </el-form>
      </div>
    </el-collapse-transition>

    <!-- Active Filters Display -->
    <div v-if="hasActiveFilters" class="active-filters">
      <span class="filters-label">Active filters:</span>
      <div class="filter-tags">
        <el-tag
          v-if="selectedYear"
          closable
          size="small"
          @close="clearYear"
        >
          Year: {{ selectedYear }}
        </el-tag>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { Search } from '@element-plus/icons-vue'
import { debounce } from 'lodash-es'

const props = defineProps({
  modelValue: { type: String, default: '' },
  placeholder: { type: String, default: 'Search employee or position...' },
  loading: { type: Boolean, default: false },
  debounceMs: { type: Number, default: 300 },
  
  // Filter options
  availableYears: { type: Array, default: () => [] },
  
  // Filter visibility controls
  showAdvancedFilters: { type: Boolean, default: true },
  showYearFilter: { type: Boolean, default: false },
  
  // Initial filter values
  initialYear: { type: [String, Number], default: null }
})

const emit = defineEmits([
  'update:modelValue',
  'search',
  'filter-change'
])

// Reactive values
const searchValue = ref(props.modelValue)
const selectedYear = ref(props.initialYear)
const showFiltersPanel = ref(false)

// Computed properties
const hasActiveFilters = computed(() => {
  return selectedYear.value
})

const activeFilterCount = computed(() => {
  let count = 0
  if (selectedYear.value) count++
  return count
})

// Debounced search function
const debouncedSearch = debounce((value) => {
  emit('search', value)
}, props.debounceMs)

// Methods
const onSearchInput = (value) => {
  searchValue.value = value
  emit('update:modelValue', value)
  debouncedSearch(value)
}

const onClear = () => {
  searchValue.value = ''
  emit('update:modelValue', '')
  emit('search', '')
}

const onFilterChange = () => {
  const filters = {
    search: searchValue.value,
    year: selectedYear.value
  }
  emit('filter-change', filters)
}

const clearYear = () => {
  selectedYear.value = null
  onFilterChange()
}


// Watch for external changes
watch(() => props.modelValue, (newValue) => {
  if (newValue !== searchValue.value) {
    searchValue.value = newValue
  }
})

watch(() => props.initialYear, (newValue) => {
  selectedYear.value = newValue
})

onMounted(() => {
  // Initialize with current values
  searchValue.value = props.modelValue
  selectedYear.value = props.initialYear
})
</script>

<style scoped>
.employee-searchbar {
  margin-bottom: 0;
}

.search-container {
  display: flex;
  align-items: center;
  gap: 12px;
  width: 100%;
}

.search-input {
  flex: 1;
  max-width: none;
  width: 100%;
}

.search-input.has-filters {
  border-color: #409eff;
}

.search-icon {
  color: #909399;
}


.filters-panel {
  margin-top: 12px;
  padding: 16px;
  background: #f8f9fa;
  border-radius: 8px;
  border: 1px solid #e9ecef;
}

.filter-form {
  margin: 0;
}

.filter-select {
  min-width: 200px;
}

.filter-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
}

.clear-all {
  color: #f56c6c;
  font-weight: 500;
}

.active-filters {
  margin-top: 12px;
  padding: 12px;
  background: #ecf5ff;
  border: 1px solid #b3d8ff;
  border-radius: 6px;
  display: flex;
  align-items: center;
  gap: 12px;
}

.filters-label {
  font-size: 13px;
  color: #606266;
  font-weight: 500;
  white-space: nowrap;
}

.filter-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  flex: 1;
}


/* Responsive adjustments */
@media (max-width: 1366px) {
  .search-container {
    flex-direction: column;
    align-items: stretch;
    gap: 8px;
  }
  
}

@media (max-width: 768px) {
  .search-container {
    flex-direction: column;
    align-items: stretch;
  }
  
  .search-input {
    flex: 1 1 auto;
    max-width: none;
    width: 100%;
  }
  
  .filter-select {
    min-width: 150px;
  }
  
  .active-filters {
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
  }
}
</style>
