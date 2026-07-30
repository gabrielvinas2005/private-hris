<template>
  <div class="plantilla-filters">
    <el-card shadow="never">
      <div class="filters-content">
        <div class="search-section">
          <el-input v-model="search" placeholder="Search by name..." clearable @input="$emit('search', search)" style="width: 100%; max-width: 400px">
            <template #prefix><el-icon><Search /></el-icon></template>
          </el-input>
        </div>
        <div class="filter-section">
          <el-select v-model="status" placeholder="Filter by status" clearable style="width: 150px" @change="$emit('filter', status)">
            <el-option label="Active" value="Active" />
            <el-option label="Inactive" value="Inactive" />
          </el-select>
        </div>
        <div class="actions-section">
          <el-button type="primary" @click="$emit('add')">+ Add Document Type</el-button>
          <el-button v-if="props.showSave" type="success" :loading="props.saving" @click="$emit('save')">Save Changes</el-button>
        </div>
      </div>
    </el-card>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { Search } from '@element-plus/icons-vue'
const props = defineProps({ showSave: { type: Boolean, default: false }, saving: { type: Boolean, default: false } })
const search = ref('')
const status = ref('')
</script>

<style scoped>
.plantilla-filters { margin-bottom: 20px; }
.filters-content { display: flex; align-items: center; gap: 20px; flex-wrap: wrap; }
.search-section { flex: 1; min-width: 200px; }
.filter-section { display: flex; gap: 10px; flex-wrap: wrap; }
.actions-section { display: flex; gap: 10px; }
:deep(.el-card__body) { padding: 20px; }
</style>


