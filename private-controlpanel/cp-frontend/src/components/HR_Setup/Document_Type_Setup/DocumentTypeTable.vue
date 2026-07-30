<template>
  <el-card shadow="never">
    <el-table :data="items" border style="width: 100%">
      <el-table-column 
        v-if="visible.serial"
        type="index" 
        label="#" 
        width="60" 
        align="center"
        header-align="center"
      />
      <el-table-column 
        v-if="visible.name"
        label="Name" 
        min-width="300"
        align="left"
        header-align="center"
      >
        <template #default="{ row }">
          <el-input v-model="row.name" placeholder="Enter document type" />
        </template>
      </el-table-column>
      <el-table-column 
        v-if="visible.active"
        label="Active" 
        width="140"
        align="center"
        header-align="center"
      >
        <template #default="{ row }">
          <el-switch v-model="row.active" @change="$emit('toggle-active', row)" />
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
          <el-button size="small" type="danger" @click="$emit('delete', row)">Delete</el-button>
        </template>
      </el-table-column>
    </el-table>
  </el-card>
</template>

<script setup>
import { defineExpose } from 'vue'

const props = defineProps({ 
  items: { type: Array, default: () => [] },
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
defineEmits(['delete', 'toggle-active'])

// Expose method to get all filtered data
const getFilteredData = () => {
  return props.items || []
}

defineExpose({ getFilteredData })
</script>


