<template>
  <div class="table-container">
    <el-table
      :data="items"
      v-loading="loading"
      stripe
      border
      style="width: 100%"
      empty-text="No exam categories found"
      :scroll-x="true"
    >
      <!-- Serial Number Column -->
      <el-table-column
        v-if="visible.serial"
        label="#"
        width="60"
        align="center"
        header-align="center"
      >
        <template #default="{ $index }">
          {{ $index + 1 }}
        </template>
      </el-table-column>

      <!-- Category Code Column -->
      <el-table-column
        v-if="visible.code"
        prop="category_code"
        label="Category Code"
        width="150"
        align="center"
        header-align="center"
      >
        <template #default="{ row }">
          <el-tag type="info" size="small">
            {{ row.category_code }}
          </el-tag>
        </template>
      </el-table-column>

      <!-- Name Column -->
      <el-table-column
        v-if="visible.name"
        prop="name"
        label="Category Name"
        min-width="200"
        align="left"
        header-align="center"
      >
        <template #default="{ row }">
          <span class="category-name">{{ row.name }}</span>
        </template>
      </el-table-column>

      <!-- Description Column -->
      <el-table-column
        v-if="visible.description"
        prop="description"
        label="Description"
        min-width="250"
        align="left"
        header-align="center"
        show-overflow-tooltip
      >
        <template #default="{ row }">
          <span class="category-description">{{ row.description || 'No description' }}</span>
        </template>
      </el-table-column>

      <!-- Actions Column -->
      <el-table-column
        v-if="visible.actions"
        label="Actions"
        align="center"
        header-align="center"
        show-overflow-tooltip
        fixed="right"
        width="240"
        min-width="220"
      >
        <template #default="{ row }">
          <div class="actions-container">
            <el-tooltip content="View Subcategories" placement="top">
              <div class="action-badge info" @click="handleViewSubcategories(row)">
                <el-icon><View /></el-icon>
              </div>
            </el-tooltip>
            <el-tooltip content="View Questions" placement="top">
              <div class="action-badge warning" @click="handleViewQuestions(row)">
                <el-icon><Files /></el-icon>
              </div>
            </el-tooltip>
            <el-tooltip content="Edit Category" placement="top">
              <div class="action-badge primary" @click="handleEdit(row)">
                <el-icon><Edit /></el-icon>
              </div>
            </el-tooltip>
            <el-tooltip content="Delete Category" placement="top">
              <div class="action-badge danger" @click="handleDelete(row)">
                <el-icon><Delete /></el-icon>
              </div>
            </el-tooltip>
          </div>
        </template>
      </el-table-column>
    </el-table>
  </div>
</template>

<script setup>
import { Edit, View, Files, Delete } from '@element-plus/icons-vue'

const props = defineProps({
  items: {
    type: Array,
    default: () => []
  },
  loading: {
    type: Boolean,
    default: false
  },
  visible: {
    type: Object,
    default: () => ({
      serial: true,
      code: true,
      name: true,
      description: true,
      actions: true
    })
  }
})

const emit = defineEmits(['edit', 'view-subcategories', 'view-questions', 'delete'])

function handleEdit(row) {
  emit('edit', row)
}

function handleViewSubcategories(row) {
  emit('view-subcategories', row)
}

function handleViewQuestions(row) {
  emit('view-questions', row)
}

function handleDelete(row) {
  emit('delete', row)
}

// Expose method to get all filtered data
const getFilteredData = () => {
  return props.items || []
}

defineExpose({ getFilteredData })
</script>

<style scoped>
.table-container {
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  overflow: hidden;
  overflow-x: auto;
}

:deep(.el-table) {
  border-radius: 8px;
}

:deep(.el-table th) {
  background-color: #f5f7fa;
  color: #606266;
  font-weight: 600;
}

:deep(.el-table td) {
  padding: 12px 0;
  text-align: center;
}

:deep(.el-table .el-table__cell) {
  text-align: center;
}

.actions-container {
  display: flex;
  flex-direction: row;
  align-items: center;
  justify-content: center;
  gap: 8px;
  width: 100%;
  height: 100%;
  box-sizing: border-box;
}

.action-badge {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.action-badge.info {
  background-color: #dcdfe6;
  color: #606266;
}

.action-badge.warning {
  background-color: #f8e3c5;
  color: #d58a00;
}

.action-badge.primary {
  background-color: #d6e4ff;
  color: #4080ff;
}

.action-badge.danger {
  background-color: #fde2e2;
  color: #f56c6c;
}

.action-badge:hover {
  transform: scale(1.05);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
}

.category-name {
  font-weight: 500;
  color: #303133;
}

.category-description {
  color: #606266;
  font-size: 13px;
}

:deep(.el-button) {
  border-radius: 6px;
  font-weight: 500;
}

:deep(.el-button--primary) {
  background-color: #409eff;
  border-color: #409eff;
}

:deep(.el-button--info) {
  background-color: #909399;
  border-color: #909399;
}

:deep(.el-button--warning) {
  background-color: #e6a23c;
  border-color: #e6a23c;
}
</style>
