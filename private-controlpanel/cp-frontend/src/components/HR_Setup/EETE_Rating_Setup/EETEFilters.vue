<template>
  <div class="filters-container">
    <el-row :gutter="20" class="filters-row">
      <!-- Search Input -->
      <el-col :span="8">
        <el-input
          v-model="searchTerm"
          placeholder="Search EETE ratings..."
          clearable
          @input="handleSearch"
        >
          <template #prefix>
            <el-icon><Search /></el-icon>
          </template>
        </el-input>
      </el-col>

      <!-- Rating Range Filter -->
      <el-col :span="6">
        <el-select
          v-model="ratingFilter"
          placeholder="Filter by rating range"
          clearable
          @change="handleRatingFilter"
        >
          <el-option label="All" value="" />
          <el-option label="0-25" value="0-25" />
          <el-option label="26-50" value="26-50" />
          <el-option label="51-75" value="51-75" />
          <el-option label="76-100" value="76-100" />
        </el-select>
      </el-col>

      <!-- Add Button -->
      <el-col :span="6">
        <el-button
          type="primary"
          @click="handleAdd"
          :icon="Plus"
        >
          Add EETE Rating
        </el-button>
      </el-col>

      <!-- Save Changes Button (conditional) -->
      <el-col :span="4" v-if="showSave">
        <el-button
          type="success"
          @click="handleSave"
          :loading="saving"
          :icon="Check"
        >
          Save Changes
        </el-button>
      </el-col>
    </el-row>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { Search, Plus, Check } from '@element-plus/icons-vue'

const props = defineProps({
  showSave: {
    type: Boolean,
    default: false
  },
  saving: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['search', 'rating-filter', 'add', 'save'])

const searchTerm = ref('')
const ratingFilter = ref('')

function handleSearch() {
  emit('search', searchTerm.value)
}

function handleRatingFilter() {
  emit('rating-filter', ratingFilter.value)
}

function handleAdd() {
  emit('add')
}

function handleSave() {
  emit('save')
}
</script>

<style scoped>
.filters-container {
  background: #fff;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  margin-bottom: 20px;
}

.filters-row {
  align-items: center;
}

.el-input,
.el-select {
  width: 100%;
}
</style>
