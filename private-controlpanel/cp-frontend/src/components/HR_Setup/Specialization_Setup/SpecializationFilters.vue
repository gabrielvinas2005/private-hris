<template>
  <div class="specialization-filters">
    <el-card shadow="never">
      <div class="filters-content">
        <div class="search-section">
          <el-input
            v-model="searchTerm"
            placeholder="Search specializations by name..."
            clearable
            @input="handleSearch"
            @clear="handleSearch"
            style="width: 100%; max-width: 400px"
          >
            <template #prefix>
              <el-icon><Search /></el-icon>
            </template>
          </el-input>
        </div>

        <div class="filter-section">
          <el-select
            v-model="statusFilter"
            placeholder="Filter by status"
            clearable
            @change="handleFilterChange"
            style="width: 150px"
          >
            <el-option label="All Status" value="" />
            <el-option label="Active" value="active" />
            <el-option label="Inactive" value="inactive" />
          </el-select>
        </div>

        <div class="actions-section">
          <el-button
            type="primary"
            @click="$emit('add')"
            :loading="loading"
          >
            <el-icon><Plus /></el-icon>
            Add Specialization
          </el-button>
        </div>
      </div>
    </el-card>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { Search, Plus } from '@element-plus/icons-vue'

// Props
const props = defineProps({
  loading: {
    type: Boolean,
    default: false
  }
})

// Emits
const emit = defineEmits(['search', 'filter', 'add'])

// State
const searchTerm = ref('')
const statusFilter = ref('')

// Methods
const handleSearch = () => {
  emit('search', searchTerm.value)
}

const handleFilterChange = () => {
  const combinedFilter = {
    status: statusFilter.value
  }
  emit('filter', combinedFilter)
}

const clearFilters = () => {
  searchTerm.value = ''
  statusFilter.value = ''
  handleSearch()
  handleFilterChange()
}

// Watch for changes and emit combined filter
watch(statusFilter, () => {
  handleFilterChange()
})

// Expose methods
defineExpose({
  clearFilters
})
</script>

<style scoped>
.specialization-filters {
  margin-bottom: 20px;
}

.filters-content {
  display: flex;
  align-items: center;
  gap: 20px;
  flex-wrap: wrap;
}

.search-section {
  flex: 1;
  min-width: 200px;
}

.filter-section {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.actions-section {
  display: flex;
  gap: 10px;
}

:deep(.el-card__body) {
  padding: 20px;
}

@media (max-width: 768px) {
  .filters-content {
    flex-direction: column;
    align-items: stretch;
    gap: 15px;
  }

  .search-section {
    min-width: auto;
  }

  .filter-section {
    justify-content: space-between;
  }

  .actions-section {
    justify-content: center;
  }

  :deep(.el-card__body) {
    padding: 15px;
  }
}

@media (max-width: 480px) {
  .filter-section {
    flex-direction: column;
    gap: 10px;
  }

  .filter-section .el-select {
    width: 100% !important;
  }
}
</style>
