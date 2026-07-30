<template>
  <div class="table-with-loading">
    <el-table
      :data="filtered || []"
      :row-key="row => row.id"
      border
      stripe
      size="small"
      :default-sort="{ prop: 'name', order: 'ascending' }"
    >
      <template #empty>
        <el-empty v-if="!loading" description="No fixed schedules found" />
      </template>
      <el-table-column prop="name" label="Schedule Name" min-width="240" show-overflow-tooltip sortable :sort-orders="['ascending','descending']">
        <template #default="{ row }">
          <el-link type="primary" @click="$emit('view', row)">{{ row.name }}</el-link>
        </template>
      </el-table-column>
      <el-table-column label="Actions" width="200" fixed="right" align="center">
        <template #default="{ row }">
          <el-tooltip content="View" placement="top" popper-class="tt-warning">
            <el-button size="small" type="warning" circle plain @click="$emit('view', row)">
              <el-icon><IconView /></el-icon>
            </el-button>
          </el-tooltip>
          <el-tooltip content="Edit" placement="top" popper-class="tt-primary">
            <el-button size="small" type="primary" circle plain class="ml-1" @click="$emit('edit', row)">
              <el-icon><IconEdit /></el-icon>
            </el-button>
          </el-tooltip>
          <el-tooltip content="Assigned Employee" placement="top" popper-class="tt-info">
            <el-button size="small" type="info" circle plain class="ml-1" @click="$emit('assigned', row)">
              <el-icon><IconUser /></el-icon>
            </el-button>
          </el-tooltip>
          <el-tooltip content="Delete" placement="top" popper-class="tt-danger">
            <el-button size="small" type="danger" circle plain class="ml-1" @click="$emit('delete', row)">
              <el-icon><IconDelete /></el-icon>
            </el-button>
          </el-tooltip>
        </template>
      </el-table-column>
    </el-table>
    <TableLoadingOverlay :loading="loading" text="Loading fixed schedules..." />
  </div>
  
</template>

<script setup>
import { computed } from 'vue'
import { View as IconView, Edit as IconEdit, Delete as IconDelete, UserFilled as IconUser } from '@element-plus/icons-vue'
import TableLoadingOverlay from '../Reusable_Components/TableLoadingOverlay.vue'

const props = defineProps({
  items: { type: Array, default: () => [] },
  query: { type: String, default: '' },
  loading: { type: Boolean, default: false },
})

function toBoolish(v) {
  return v === true || v === 1 || v === '1';
}

const filtered = computed(() => {
  const q = props.query.trim().toLowerCase()
  if (!q) return props.items
  return props.items.filter(i => String(i.id).includes(q) || (i.name || '').toLowerCase().includes(q))
})
</script>

<style scoped>
.ml-1 { margin-left: 4px; }
.table-with-loading {
  position: relative;
}
</style>


