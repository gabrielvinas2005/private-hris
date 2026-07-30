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
        v-if="visible.adjectival_rating"
        label="Adjectival Rating" 
        min-width="240"
        align="left"
        header-align="center"
      >
        <template #default="{ row }">
          <el-input v-model="row.adjectival_rating" placeholder="Enter rating" />
        </template>
      </el-table-column>
      <el-table-column 
        v-if="visible.from"
        label="From" 
        width="140"
        align="center"
        header-align="center"
      >
        <template #default="{ row }">
          <el-input v-model.number="row.numerical_rating1" placeholder="0" />
        </template>
      </el-table-column>
      <el-table-column 
        v-if="visible.to"
        label="To" 
        width="140"
        align="center"
        header-align="center"
      >
        <template #default="{ row }">
          <el-input v-model.number="row.numerical_rating2" placeholder="0" />
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
      adjectival_rating: true,
      from: true,
      to: true,
      actions: true
    })
  }
})
defineEmits(['delete'])

// Expose method to get all filtered data
const getFilteredData = () => {
  return props.items || []
}

defineExpose({ getFilteredData })
</script>


