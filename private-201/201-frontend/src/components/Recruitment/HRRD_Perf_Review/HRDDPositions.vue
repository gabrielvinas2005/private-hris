<template>
  <el-card shadow="never">
    <template #header>
      <div class="flex items-center justify-between">
        <span class="font-bold">For Deliberations</span>
        <div class="flex items-center gap-2">
          <el-dropdown trigger="click">
            <el-button size="small">Column visibility</el-button>
            <template #dropdown>
              <el-dropdown-menu>
                <el-dropdown-item v-for="col in columns" :key="col.prop">
                  <el-checkbox v-model="col.show">{{ col.label }}</el-checkbox>
                </el-dropdown-item>
              </el-dropdown-menu>
            </template>
          </el-dropdown>
          <el-input v-model="search" placeholder="Search" size="small" style="width: 260px" />
        </div>
      </div>
    </template>

    <el-table :data="filtered" size="small" stripe>
      <el-table-column v-if="columnsMap['position'].show" prop="position" label="Job Title" />
      <el-table-column v-if="columnsMap['grade'].show" prop="grade" label="Salary Grade" width="140" />
      <el-table-column v-if="columnsMap['code'].show" prop="code" label="Item Code" width="120" />
      <el-table-column v-if="columnsMap['publication'].show" label="Publication Period" width="260">
        <template #default="{ row }">
          <div>From: {{ row.publication_from }}</div>
          <div>To: {{ row.publication_to }}</div>
        </template>
      </el-table-column>
      <el-table-column v-if="columnsMap['department'].show" prop="department" label="Area of Assignment" />
      <el-table-column v-if="columnsMap['total'].show" prop="total" label="Total Applicant" width="140" />
      <el-table-column label="View Applicant" width="140">
        <template #default="{ row }">
          <el-button size="small" type="primary" @click="$emit('select', row)">View</el-button>
        </template>
      </el-table-column>
    </el-table>
  </el-card>
  
</template>

<script setup>
import { computed, ref } from 'vue'

const props = defineProps({
  items: { type: Array, default: () => [] }
})
defineEmits(['select'])

const search = ref('')
const filtered = computed(() => {
  const q = search.value.toLowerCase().trim()
  if (!q) return props.items
  return props.items.filter((r) => [r.code, r.position, r.department].some(x => String(x).toLowerCase().includes(q)))
})

// Column visibility
const columns = ref([
  { prop: 'position', label: 'Job Title', show: true },
  { prop: 'grade', label: 'Salary Grade', show: true },
  { prop: 'code', label: 'Item Code', show: true },
  { prop: 'publication', label: 'Publication Period', show: true },
  { prop: 'department', label: 'Area of Assignment', show: true },
  { prop: 'total', label: 'Total Applicant', show: true }
])
const columnsMap = computed(() => Object.fromEntries(columns.value.map(c => [c.prop, c])))
</script>

<style scoped>
.flex { display: flex; }
.items-center { align-items: center; }
.justify-between { justify-content: space-between; }
.font-bold { font-weight: 700; }
</style>


