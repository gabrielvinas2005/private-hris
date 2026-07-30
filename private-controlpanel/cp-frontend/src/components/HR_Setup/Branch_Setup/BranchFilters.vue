<template>
  <div class="branch-filters">
    <el-card class="filters-card" shadow="never">
      <div class="filters-content">
        <div class="search-section">
          <el-input
            v-model="searchTerm"
            placeholder="Search branches by name, code, or head..."
            clearable
            :prefix-icon="Search"
            @input="handleSearch"
            @clear="handleSearch"
            class="search-input"
          />
        </div>

        <div class="filter-section">
          <el-select
            v-model="typeFilter"
            placeholder="Filter by type"
            clearable
            @change="handleTypeFilter"
            class="type-filter"
          >
            <el-option label="All Branches" value="" />
            <el-option label="Main Branches" value="main" />
            <el-option label="Regular Branches" value="regular" />
          </el-select>

          <el-button
            type="default"
            :icon="Refresh"
            @click="handleReset"
            class="reset-button"
          >
            Reset Filters
          </el-button>
        </div>
      </div>

      <!-- Active Filters Display -->
      <div v-if="hasActiveFilters" class="active-filters">
        <span class="filters-label">Active Filters:</span>
        <div class="filter-tags">
          <el-tag
            v-if="searchTerm"
            closable
            @close="clearSearch"
            type="primary"
            size="small"
          >
            Search: "{{ searchTerm }}"
          </el-tag>
          <el-tag
            v-if="typeFilter"
            closable
            @close="clearTypeFilter"
            type="success"
            size="small"
          >
            Type: {{ getTypeLabel(typeFilter) }}
          </el-tag>
        </div>
      </div>
    </el-card>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { Search, Refresh } from '@element-plus/icons-vue'

// Props
const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({})
  }
})

// Emits
const emit = defineEmits(['update:modelValue', 'change'])

// Reactive state
const searchTerm = ref('')
const typeFilter = ref('')

// Computed
const hasActiveFilters = computed(() => {
  return searchTerm.value || typeFilter.value
})

// Methods
function handleSearch() {
  updateFilters()
}

function handleTypeFilter() {
  updateFilters()
}

function handleReset() {
  searchTerm.value = ''
  typeFilter.value = ''
  updateFilters()
}

function clearSearch() {
  searchTerm.value = ''
  updateFilters()
}

function clearTypeFilter() {
  typeFilter.value = ''
  updateFilters()
}

function updateFilters() {
  const filters = {
    search: searchTerm.value,
    type: typeFilter.value
  }
  
  emit('update:modelValue', filters)
  emit('change', filters)
}

function getTypeLabel(type) {
  const labels = {
    main: 'Main Branches',
    regular: 'Regular Branches'
  }
  return labels[type] || type
}

// Watch for prop changes
watch(() => props.modelValue, (newValue) => {
  if (newValue) {
    searchTerm.value = newValue.search || ''
    typeFilter.value = newValue.type || ''
  }
}, { immediate: true })
</script>

<style scoped>
.branch-filters {
  margin-bottom: 20px;
}

.filters-card {
  border-radius: 12px;
  border: 1px solid #e4e7ed;
}

.filters-content {
  display: flex;
  gap: 16px;
  align-items: center;
  flex-wrap: wrap;
}

.search-section {
  flex: 1;
  min-width: 300px;
}

.search-input {
  width: 100%;
}

.filter-section {
  display: flex;
  gap: 12px;
  align-items: center;
  flex-wrap: wrap;
}

.type-filter {
  width: 180px;
}

.reset-button {
  white-space: nowrap;
}

.active-filters {
  margin-top: 16px;
  padding-top: 16px;
  border-top: 1px solid #e4e7ed;
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}

.filters-label {
  font-size: 14px;
  font-weight: 500;
  color: #606266;
  white-space: nowrap;
}

.filter-tags {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

:deep(.el-card__body) {
  padding: 20px 24px;
}

:deep(.el-input__wrapper) {
  border-radius: 8px;
}

:deep(.el-select .el-input__wrapper) {
  border-radius: 8px;
}

/* Responsive Design */
@media (max-width: 768px) {
  .filters-content {
    flex-direction: column;
    align-items: stretch;
  }
  
  .search-section {
    min-width: auto;
  }
  
  .filter-section {
    justify-content: space-between;
  }
  
  .type-filter {
    flex: 1;
    min-width: 150px;
  }
  
  .active-filters {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .filter-tags {
    width: 100%;
  }
}
</style>
