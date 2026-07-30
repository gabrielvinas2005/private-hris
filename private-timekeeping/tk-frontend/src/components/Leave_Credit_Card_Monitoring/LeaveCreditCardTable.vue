<template>
  <div class="table-with-loading">
    <el-table :data="displayRows" height="520" stripe @sort-change="onSortChange">
      <el-table-column label="#" type="index" width="60" />
      <el-table-column prop="employee_no" label="Emp No" width="120" sortable="custom">
        <template #default="{ row }">
          <EmployeeDataPopulate :employee="row" field="empNo" />
        </template>
      </el-table-column>
      <el-table-column label="Employee" min-width="260" prop="name" sortable="custom">
        <template #default="{ row }">
          <div class="emp" @click="onView(row)" style="cursor: pointer;">
            <EmployeeDataPopulate :employee="row" field="photo" />
            <div>
              <EmployeeDataPopulate :employee="row" field="namePosition" />
            </div>
          </div>
        </template>
      </el-table-column>
      <el-table-column prop="department" label="Department" min-width="220" sortable="custom">
        <template #default="{ row }">
          <EmployeeDataPopulate :employee="row" field="department" />
        </template>
      </el-table-column>
      <el-table-column label="Actions" width="120" align="center" fixed="right">
        <template #default="{ row }">
          <el-tooltip content="View" placement="top" popper-class="tt-warning">
            <el-button size="small" type="warning" circle plain @click="onView(row)">
              <el-icon><View /></el-icon>
            </el-button>
          </el-tooltip>
        </template>
      </el-table-column>
    </el-table>

    <TableLoadingOverlay :loading="loading" text="Loading leave credit cards..." />

    <!-- Pagination Controls -->
    <Pagination 
      :pagination="pagination"
      @page-change="onPageChange"
      @per-page-change="onPerPageChange"
    />
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { View } from '@element-plus/icons-vue'
import EmployeeDataPopulate from '../Reusable_Components/Employee_Data_Populate.vue'
import { useSortingLogic } from '@/Composables/Sorting_Logic.js'
import { useFilterLogic } from '@/Composables/Filter_Logic'
import Pagination from '../Reusable_Components/Pagination.vue'
import TableLoadingOverlay from '../Reusable_Components/TableLoadingOverlay.vue'

const props = defineProps({
  items: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  pagination: { type: Object, default: () => ({}) }
})
const emit = defineEmits(['view', 'page-change', 'per-page-change'])

// Sorting
const { onSortChange, sortArray } = useSortingLogic()

// Since we're using server-side pagination, we don't need client-side filtering
// But we can still apply sorting to the items
const displayRows = computed(() => sortArray(props.items))

const onView = (employee) => emit('view', employee)
const onPageChange = (page) => emit('page-change', page)
const onPerPageChange = (perPage) => emit('per-page-change', perPage)
</script>

<style scoped>
.emp { display: flex; align-items: center; gap: 10px; }
.table-with-loading { position: relative; }

/* pagination handled by reusable component */
</style>