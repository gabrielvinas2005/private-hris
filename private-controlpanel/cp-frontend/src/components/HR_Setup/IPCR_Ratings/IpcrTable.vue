<template>
  <el-card shadow="never">
    <el-table :data="items" border style="width: 100%">
      <el-table-column type="index" label="#" width="60" />
      <el-table-column v-if="visible?.department !== false" label="Department" min-width="220" prop="department" />
      <el-table-column v-if="visible?.division !== false" label="Division" min-width="180" prop="division" />
      <el-table-column v-if="visible?.semester !== false" label="Semester" width="140" prop="semester" />
      <el-table-column v-if="visible?.months !== false" label="Months" width="200">
        <template #default="{ row }">
          <span>{{ row.month_from }} - {{ row.month_to }}</span>
        </template>
      </el-table-column>
      <el-table-column v-if="visible?.actions !== false" label="Actions" width="160">
        <template #default="{ row }">
          <el-button size="small" type="primary" @click="$emit('edit', row)">Edit</el-button>
        </template>
      </el-table-column>
    </el-table>
  </el-card>
</template>

<script setup>
defineProps({ items: { type: Array, default: () => [] }, visible: { type: Object, default: () => ({ department: true, division: true, semester: true, months: true, actions: true }) } })
defineEmits(['edit'])
</script>


