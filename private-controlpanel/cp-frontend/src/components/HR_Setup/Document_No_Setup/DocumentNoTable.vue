<template>
  <el-card shadow="never">
    <el-table :data="items" border style="width: 100%">
      <el-table-column 
        v-if="visible.serial"
        type="index" 
        :index="serialStart"
        label="#" 
        width="60" 
        align="center"
        header-align="center"
      />
      <el-table-column 
        v-if="visible.name"
        label="Name" 
        min-width="220"
        align="left"
        header-align="center"
      >
        <template #default="{ row }"><el-input v-model="row.name" placeholder="Enter name" /></template>
      </el-table-column>
      <el-table-column 
        v-if="visible.rd_doc_no"
        label="RD Doc No." 
        min-width="160"
        align="left"
        header-align="center"
      >
        <template #default="{ row }"><el-input v-model="row.rd_document_number" placeholder="RD Doc No." /></template>
      </el-table-column>
      <el-table-column 
        v-if="visible.rd_revision"
        label="RD Revision" 
        width="140"
        align="center"
        header-align="center"
      >
        <template #default="{ row }"><el-input v-model="row.rd_revision" placeholder="RD Rev" /></template>
      </el-table-column>
      <el-table-column 
        v-if="visible.co_doc_no"
        label="CO Doc No." 
        min-width="160"
        align="left"
        header-align="center"
      >
        <template #default="{ row }"><el-input v-model="row.co_document_number" placeholder="CO Doc No." /></template>
      </el-table-column>
      <el-table-column 
        v-if="visible.co_revision"
        label="CO Revision" 
        width="140"
        align="center"
        header-align="center"
      >
        <template #default="{ row }"><el-input v-model="row.co_revision" placeholder="CO Rev" /></template>
      </el-table-column>
      <el-table-column 
        v-if="visible.actions"
        label="Actions" 
        width="140"
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
  serialStart: { type: Number, default: 1 },
  visible: {
    type: Object,
    default: () => ({
      serial: true,
      name: true,
      rd_doc_no: true,
      rd_revision: true,
      co_doc_no: true,
      co_revision: true,
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


