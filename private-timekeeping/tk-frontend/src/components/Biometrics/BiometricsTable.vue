<template>
  <div>
    <el-table 
      :data="rows" 
      v-loading="loading" 
      border 
      style="width: 100%"
      @selection-change="handleSelectionChange"
      ref="tableRef"
    >
      <el-table-column type="selection" width="55" />
      <el-table-column prop="id" label="ID" width="80" />
      <el-table-column prop="name" label="Employee Name" min-width="200" />
      <el-table-column prop="position" label="Position" min-width="150" />
      <el-table-column prop="employee_no" label="Employee No" width="120" />
      <el-table-column prop="access_no" label="Access No" width="120" />
    </el-table>

    <div class="mt-4" v-if="selectedEmployees.length > 0">
      <el-alert 
        :title="`${selectedEmployees.length} employee(s) selected`"
        type="info"
        :closable="false"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  rows: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
})

const emit = defineEmits(['selection-change'])

const tableRef = ref()
const selectedEmployees = ref([])

const handleSelectionChange = (selection) => {
  selectedEmployees.value = selection
  emit('selection-change', selection)
}

// Watch for external changes to selection
watch(() => props.rows, () => {
  // Clear selection when data changes
  if (tableRef.value) {
    tableRef.value.clearSelection()
  }
}, { deep: true })

// Expose methods for parent component
defineExpose({
  clearSelection: () => {
    if (tableRef.value) {
      tableRef.value.clearSelection()
    }
  },
  toggleRowSelection: (row, selected) => {
    if (tableRef.value) {
      tableRef.value.toggleRowSelection(row, selected)
    }
  }
})
</script>

<style scoped>
.mt-4 { margin-top: 16px; }
</style>
