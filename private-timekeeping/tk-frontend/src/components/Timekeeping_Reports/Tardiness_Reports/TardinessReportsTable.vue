<template>
  <div class="tardiness-reports-table table-with-loading">
    <!-- Table -->
    <el-table
      :data="displayRows"
      stripe
      @sort-change="onSortChange"
      height="520"
    >
      <el-table-column label="#" type="index" width="60" />
      <el-table-column prop="employee_no" label="Emp No" width="120" sortable="custom">
        <template #default="{ row }">
          <EmployeeDataPopulate :employee="row" field="empNo" />
        </template>
      </el-table-column>
      <el-table-column label="Employee" min-width="260" prop="name" sortable="custom">
        <template #default="{ row }">
          <div class="emp" @click="$emit('select', row)" style="cursor: pointer;">
            <EmployeeDataPopulate :employee="row" field="photo" />
            <div>
              <EmployeeDataPopulate :employee="row" field="namePosition" />
            </div>
          </div>
        </template>
      </el-table-column>
      <el-table-column prop="displayDepartment" label="Department" min-width="220" sortable="custom">
        <template #default="{ row }">
          <EmployeeDataPopulate :employee="row" :department="row.displayDepartment" field="department" />
        </template>
      </el-table-column>
      <el-table-column label="Actions" width="120" align="center" fixed="right">
        <template #default="{ row }">
          <el-tooltip content="View" placement="top" popper-class="tt-warning">
            <el-button size="small" type="warning" circle plain @click="$emit('select', row)">
              <el-icon><View /></el-icon>
            </el-button>
          </el-tooltip>
        </template>
      </el-table-column>
    </el-table>

    <TableLoadingOverlay :loading="loading" text="Loading tardiness reports..." />

    <Pagination 
      :pagination="pagination"
      @page-change="onPageChange"
      @per-page-change="onPerPageChange"
    />
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { View } from '@element-plus/icons-vue'
import EmployeeDataPopulate from '../../Reusable_Components/Employee_Data_Populate.vue'
import { useSortingLogic } from '../../../Composables/Sorting_Logic.js'
import { formatNameFromParts, formatNameFromString } from '../../../Composables/useNameFormatter'
import Pagination from '../../Reusable_Components/Pagination.vue'
import TableLoadingOverlay from '../../Reusable_Components/TableLoadingOverlay.vue'

const props = defineProps({
  items: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  pagination: { type: Object, default: () => ({}) }
})

const emit = defineEmits(['select','page-change','per-page-change'])

const normalizedRows = computed(() => (props.items || []).map(r => {
  const firstName =
    r.first_name ?? r.firstname ?? r.firstName ??
    r.employee_first_name ?? r.employee_firstname ?? r.fname ??
    r.employee?.first_name ?? r.employee?.firstname ?? ''
  const middleName =
    r.middle_name ?? r.middlename ?? r.middleName ??
    r.employee_middle_name ?? r.employee_middlename ?? r.mname ??
    r.employee?.middle_name ?? r.employee?.middlename ?? ''
  const lastName =
    r.last_name ?? r.lastname ?? r.lastName ??
    r.employee_last_name ?? r.employee_lastname ?? r.lname ??
    r.employee?.last_name ?? r.employee?.lastname ?? ''
  const rawName = r.name ?? r.employee_name ?? r.full_name ?? r.employee?.name ?? ''
  const formattedName = formatNameFromParts({ firstName, middleName, lastName }) || formatNameFromString(rawName)
  const rawNameTrimmed = String(rawName ?? '').trim()

  return {
    ...r,
    first_name: r.first_name ?? firstName,
    middle_name: r.middle_name ?? middleName,
    last_name: r.last_name ?? lastName,
    // If formatting fails for any reason, fall back to backend-provided name.
    name: formattedName || rawNameTrimmed || '',
    displayDepartment: r.department ?? r.department_name ?? r.departmentName ?? r.dept_name ?? r.deptName ?? ''
  }
}))

const { onSortChange, sortArray } = useSortingLogic()

const displayRows = computed(() => sortArray(normalizedRows.value))

const onPageChange = (page) => emit('page-change', page)
const onPerPageChange = (perPage) => emit('per-page-change', perPage)
</script>

<style scoped>
.emp { display: flex; align-items: center; gap: 10px; }
.name { font-weight: 600; }
.muted { color: #6b7280; font-size: 12px; }
/* pagination handled by reusable component */
.table-with-loading { position: relative; }
</style>
