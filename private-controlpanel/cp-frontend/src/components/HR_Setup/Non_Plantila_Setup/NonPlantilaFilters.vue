<template>
  <div class="plantilla-filters">
    <el-card shadow="never">
      <div class="filters-content">
        <div class="search-section">
          <el-input
            v-model="local.search"
            placeholder="Search by position, or department..."
            clearable
            @input="$emit('search', local.search)"
            style="width: 100%; max-width: 400px"
          >
            <template #prefix>
              <el-icon><Search /></el-icon>
            </template>
          </el-input>
        </div>

        <div class="filter-section">
          <el-select v-model="local.status" placeholder="Filter by status" clearable style="width: 150px" @change="emitFilters">
            <el-option label="Active" value="Active" />
            <el-option label="Inactive" value="Inactive" />
          </el-select>

          <el-select v-model="local.department" placeholder="Filter by department" clearable style="width: 200px" @change="emitFilters">
            <el-option v-for="d in departments" :key="d.id" :label="d.name" :value="d.name" />
          </el-select>
        </div>

        <div class="actions-section">
          <el-button type="primary" @click="$emit('add')">+ Add Non-Plantilla</el-button>
        </div>
      </div>
    </el-card>
  </div>
</template>

<script setup>
import { reactive } from 'vue'
import { Search } from '@element-plus/icons-vue'

const props = defineProps({
  departments: { type: Array, default: () => [] }
})
const emit = defineEmits(['search', 'filter', 'add'])

const local = reactive({ search: '', status: '', department: '' })

function emitFilters() {
  emit('filter', { status: local.status, department: local.department })
}

</script>

<style scoped>
.plantilla-filters { margin-bottom: 20px; }
.filters-content { display: flex; align-items: center; gap: 20px; flex-wrap: wrap; }
.search-section { flex: 1; min-width: 200px; }
.filter-section { display: flex; gap: 10px; flex-wrap: wrap; }
.actions-section { display: flex; gap: 10px; }
:deep(.el-card__body) { padding: 20px; }
</style>


