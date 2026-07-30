<template>
  <div class="filters-container">
    <div class="filters-row">
      <div class="left">
        <el-input 
          v-model="searchQuery" 
          placeholder="Search by months or percentage..." 
          clearable 
          class="search-input"
          @input="handleSearch"
        />
      </div>
      <div class="actions">
        <el-button type="primary" @click="handleEdit">Edit Cash Gift Table</el-button>
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
  }
})

const emit = defineEmits(['search-change', 'edit'])

const searchQuery = ref(props.search)

function handleSearch() {
  emit('search-change', searchQuery.value)
}

function handleEdit() {
  emit('edit')
}

// Watch for prop changes
watch(() => props.search, (newSearch) => {
  searchQuery.value = newSearch
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

.actions {
  display: flex;
  gap: 8px;
}
</style>
