<template>
  <div class="table-container">
    <el-table
      :data="items"
      v-loading="loading"
      stripe
      border
      style="width: 100%"
      empty-text="No semester ratings found"
    >
      <!-- Serial Number Column -->
      <el-table-column
        v-if="visible.serial"
        label="#"
        width="60"
        align="center"
      >
        <template #default="{ $index }">
          {{ $index + 1 }}
        </template>
      </el-table-column>

      <!-- Name Column -->
      <el-table-column
        v-if="visible.name"
        prop="name"
        label="Name"
        min-width="200"
        show-overflow-tooltip
      />

      <!-- Active Status Column -->
      <el-table-column
        v-if="visible.active"
        label="Status"
        width="100"
        align="center"
      >
        <template #default="{ row }">
          <el-tag
            :type="row.active ? 'success' : 'danger'"
            size="small"
          >
            {{ row.active ? 'Active' : 'Inactive' }}
          </el-tag>
        </template>
      </el-table-column>

      <!-- Actions Column -->
      <el-table-column
        v-if="visible.actions"
        label="Actions"
        width="150"
        align="center"
        fixed="right"
      >
        <template #default="{ row }">
          <el-button
            type="primary"
            size="small"
            @click="handleEdit(row)"
            :icon="Edit"
          >
            Edit
          </el-button>
          <el-button
            type="danger"
            size="small"
            @click="handleDelete(row)"
            :icon="Delete"
          >
            Delete
          </el-button>
        </template>
      </el-table-column>
    </el-table>
  </div>
</template>

<script setup>
import { Edit, Delete } from '@element-plus/icons-vue'

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
      name: true,
      active: true,
      actions: true
    })
  }
})

const emit = defineEmits(['edit', 'delete'])

function handleEdit(row) {
  emit('edit', row)
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
}

:deep(.el-button + .el-button) {
  margin-left: 8px;
}
</style>
