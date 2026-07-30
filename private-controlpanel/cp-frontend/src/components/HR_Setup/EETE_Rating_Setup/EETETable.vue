<template>
  <div class="table-container">
    <el-table
      :data="items"
      v-loading="loading"
      stripe
      border
      style="width: 100%"
      empty-text="No EETE ratings found"
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

      <!-- Education Rating Column -->
      <el-table-column
        v-if="visible.education"
        prop="education_rating"
        label="Education"
        width="100"
        align="center"
        header-align="center"
      >
        <template #default="{ row }">
          <el-tag type="primary" size="small">
            {{ formatPercent(row.education_rating) }}
          </el-tag>
        </template>
      </el-table-column>

      <!-- Experience Rating Column -->
      <el-table-column
        v-if="visible.experience"
        prop="experience_rating"
        label="Experience"
        width="100"
        align="center"
        header-align="center"
      >
        <template #default="{ row }">
          <el-tag type="success" size="small">
            {{ formatPercent(row.experience_rating) }}
          </el-tag>
        </template>
      </el-table-column>

      <!-- Training Rating Column -->
      <el-table-column
        v-if="visible.training"
        prop="training_rating"
        label="Training"
        width="100"
        align="center"
        header-align="center"
      >
        <template #default="{ row }">
          <el-tag type="warning" size="small">
            {{ formatPercent(row.training_rating) }}
          </el-tag>
        </template>
      </el-table-column>

      <!-- Eligibility Rating Column -->
      <el-table-column
        v-if="visible.eligibility"
        prop="eligibility_rating"
        label="Eligibility"
        width="100"
        align="center"
        header-align="center"
      >
        <template #default="{ row }">
          <el-tag type="info" size="small">
            {{ formatPercent(row.eligibility_rating) }}
          </el-tag>
        </template>
      </el-table-column>

      <!-- Actions Column -->
      <el-table-column
        v-if="visible.actions"
        label="Actions"
        align="center"
        header-align="center"
        show-overflow-tooltip
      >
        <template #default="{ row }">
          <div class="actions-container">
            <el-button
              type="primary"
              size="small"
              circle
              @click="handleEdit(row)"
              :icon="Edit"
            />
            <el-button
              type="danger"
              size="small"
              circle
              @click="handleDelete(row)"
              :icon="Delete"
            />
          </div>
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
      education: true,
      experience: true,
      training: true,
      eligibility: true,
      total: true,
      average: true,
      actions: true
    })
  }
})

const emit = defineEmits(['edit', 'delete'])

const formatPercent = (value) => {
  const num = Number(value)
  if (!Number.isFinite(num)) return '0.00%'
  return `${num.toFixed(2)}%`
}

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

:deep(.el-button + .el-button) {
  margin-left: 8px;
}

.actions-container {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  opacity: 1;
  transition: opacity 0.2s ease;
}
</style>
