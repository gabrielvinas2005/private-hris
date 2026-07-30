<template>
  <div class="filters-container">
    <el-row :gutter="20" class="filters-row">
      <!-- Search Input -->
      <el-col :span="8">
        <el-input
          v-model="searchTerm"
          placeholder="Search competencies..."
          clearable
          @input="handleSearch"
        >
          <template #prefix>
            <el-icon><Search /></el-icon>
          </template>
        </el-input>
      </el-col>

      <!-- Status Filter -->
      <el-col :span="6">
        <el-select
          v-model="statusFilter"
          placeholder="Filter by status"
          clearable
          @change="handleStatusFilter"
        >
          <el-option label="All" value="" />
          <el-option label="Active" value="active" />
          <el-option label="Inactive" value="inactive" />
        </el-select>
      </el-col>

      <!-- Add Button -->
      <el-col :span="6">
        <el-button
          type="primary"
          @click="handleAdd"
          :icon="Plus"
        >
          Add Competency
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

const emit = defineEmits(['search', 'status-filter', 'add', 'save'])

const searchTerm = ref('')
const statusFilter = ref('')

function handleSearch() {
  emit('search', searchTerm.value)
}

function handleStatusFilter() {
  emit('status-filter', statusFilter.value)
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
