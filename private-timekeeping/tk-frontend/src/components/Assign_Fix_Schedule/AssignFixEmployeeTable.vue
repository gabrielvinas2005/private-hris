<template>
  <div class="table-with-loading">
    <el-table
      ref="tableRef"
      :data="paginatedData"
      border
      stripe
      height="400"
      @selection-change="onSelectionChange"
      :row-key="row => row.id"
      :default-sort="{ prop: 'name', order: 'ascending' }"
      @sort-change="onSortChange"
    >
      <template #empty>
        <el-empty v-if="hasLoaded && !loading" description="No employees found to assign" />
      </template>
      <el-table-column type="selection" width="48">
        <template #header>
          <el-checkbox
            v-model="isAllFilteredSelected"
            :indeterminate="isIndeterminate"
            @change="realHandleSelectAllFiltered"
            :disabled="loading || filteredItems.length === 0"
          />
        </template>
      </el-table-column>
      <el-table-column type="index" label="#" width="60" :index="getRowIndex" />
      <el-table-column prop="employee_no" label="Emp No" width="120" sortable="custom">
        <template #default="{ row }">
          <EmployeeDataPopulate :employee="row" field="empNo" />
        </template>
      </el-table-column>
      <el-table-column prop="name" label="Employee" min-width="280" sortable="custom">
        <template #default="{ row }">
          <div class="emp">
            <EmployeeDataPopulate :employee="row" field="photo" />
            <EmployeeDataPopulate :employee="row" field="namePosition" />
          </div>
        </template>
      </el-table-column>
      <el-table-column prop="department" label="Department" min-width="200" sortable="custom">
        <template #default="{ row }">
          <EmployeeDataPopulate :employee="row" field="department" />
        </template>
      </el-table-column>
      <el-table-column prop="employment_type" label="Employment Type" min-width="160" sortable="custom">
        <template #default="{ row }">
          <EmployeeDataPopulate :employee="row" field="employmentType" />
        </template>
      </el-table-column>
    </el-table>
    <TableLoadingOverlay :loading="loading" text="Loading employees to assign..." />

    <Pagination
      v-if="loading || displayed.length > 0"
      :pagination="paginationData"
      :per-page-options="[10, 25, 50, 100]"
      @page-change="onPageChange"
      @per-page-change="onPerPageChange"
    />
  </div>
</template>

<script setup>
import { computed, ref, watch, nextTick } from 'vue'
import EmployeeDataPopulate from '../Reusable_Components/Employee_Data_Populate.vue'
import Pagination from '../Reusable_Components/Pagination.vue'
import TableLoadingOverlay from '../Reusable_Components/TableLoadingOverlay.vue'
import { useSortingLogic } from '@/Composables/Sorting_Logic'
import { useFilteredSelection } from '@/Composables/useFilteredSelection'
import { ElTable } from 'element-plus'

const props = defineProps({
  items: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  hasLoaded: { type: Boolean, default: false },
  modelValue: { type: Array, default: () => [] }
})
const emit = defineEmits(['update:modelValue'])

const { createFilteredSelection } = useFilteredSelection()

const tableRef = ref(null)

const {
  filteredItems,
  isAllFilteredSelected,
  isIndeterminate,
  handleSelectAllFiltered
} = createFilteredSelection(
  () => props.items,
  () => ({}), // No additional filters needed
  () => props.modelValue,
  (items, filters) => items, // No additional filtering needed
  (newSelection) => emit('update:modelValue', newSelection)
)

function onSelectionChange(rows) {
  // This function is triggered per page by element-plus default behavior
  // But for global select all, we want the handleSelectAllFiltered to control all filtered rows
  const checkedIds = rows.map(r => r.id)
  const pageIds = paginatedData.value.map(r => r.id)
  // Remove current page ids from previous selection, then add what is checked this page
  const newSelection = [
    ...props.modelValue.filter(id => !pageIds.includes(id)),
    ...checkedIds
  ]
  emit('update:modelValue', Array.from(new Set(newSelection)))
}
// Patch: override handleSelectAllFiltered so it always selects all filtered IDs, not just current page
const realHandleSelectAllFiltered = (selected) => {
  const selection = props.modelValue
  const filteredIds = filteredItems.value.map(item => item.id)
  if (selected) {
    // Select all filtered, union with previous
    const newSelection = [...new Set([...selection, ...filteredIds])]
    emit('update:modelValue', newSelection)
  } else {
    // Remove all filtered
    const filteredIdSet = new Set(filteredIds)
    const newSelection = selection.filter(id => !filteredIdSet.has(id))
    emit('update:modelValue', newSelection)
  }
}

const { onSortChange, sortArray } = useSortingLogic()

const displayed = computed(() => sortArray(filteredItems.value))

const currentPage = ref(1)
const perPage = ref(10)

watch(() => props.items, () => {
  currentPage.value = 1
})

const paginatedData = computed(() => {
  const start = (currentPage.value - 1) * perPage.value
  const end = start + perPage.value
  return displayed.value.slice(start, end)
})

watch([paginatedData, () => props.modelValue], async () => {
  await nextTick()
  if (tableRef.value) {
    paginatedData.value.forEach(row => {
      tableRef.value.toggleRowSelection(row, props.modelValue.includes(row.id))
    })
  }
})

const paginationData = computed(() => {
  const total = displayed.value.length
  const from = total === 0 ? 0 : (currentPage.value - 1) * perPage.value + 1
  const to = Math.min(currentPage.value * perPage.value, total)
  return {
    current_page: currentPage.value,
    per_page: perPage.value,
    total: total,
    from: from,
    to: to
  }
})

function onPageChange(page) {
  currentPage.value = page
}

function onPerPageChange(newPerPage) {
  perPage.value = newPerPage
  currentPage.value = 1
}

function getRowIndex(index) {
  return (currentPage.value - 1) * perPage.value + index + 1
}

</script>

<style scoped>
.emp { display: flex; align-items: center; gap: 10px; }
.mb-3 { margin-bottom: 12px; }
.flex { display: flex; }
.items-center { align-items: center; }
.gap-2 { gap: 8px; }
.text-sm { font-size: 14px; }
.text-gray-500 { color: #6b7280; }
.table-with-loading {
  position: relative;
}
</style>


