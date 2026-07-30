<template>
  <el-table :data="items" border style="width: 100%">
    <el-table-column 
      v-if="visible.position" 
      prop="position" 
      label="Position" 
      min-width="220"
      align="left"
      header-align="center"
    />
    <el-table-column 
      v-if="visible.department" 
      prop="department" 
      label="Department" 
      min-width="160"
      align="left"
      header-align="center"
    />
    <el-table-column 
      v-if="visible.salary" 
      prop="salary" 
      label="Salary" 
      width="120"
      align="center"
      header-align="center"
    />
    <el-table-column 
      v-if="visible.vacant" 
      prop="vacant" 
      label="Vacant" 
      width="100"
      align="center"
      header-align="center"
    />
    <el-table-column 
      v-if="visible.status" 
      prop="status" 
      label="Status" 
      width="120"
      align="center"
      header-align="center"
    >
      <template #default="{ row }">
        <el-tag :type="row.status === 'Active' ? 'success' : 'info'">{{ row.status }}</el-tag>
      </template>
    </el-table-column>
    <el-table-column 
      v-if="visible.actions" 
      label="Actions" 
      width="160"
      align="center"
      header-align="center"
    >
      <template #default="{ row }">
        <el-button size="small" @click="$emit('edit', row)">Edit</el-button>
        <el-button size="small" type="danger" @click="$emit('delete', row)">Delete</el-button>
      </template>
    </el-table-column>
  </el-table>
</template>

<script setup>
import { defineExpose } from 'vue'

const props = defineProps({
  items: { type: Array, default: () => [] },
  visible: {
    type: Object,
    default: () => ({ position: true, department: true, salary: true, vacant: true, status: true, actions: true })
  }
})
defineEmits(['edit', 'delete'])

// Expose method to get all filtered data
const getFilteredData = () => {
  return props.items || []
}

defineExpose({ getFilteredData })
</script>


