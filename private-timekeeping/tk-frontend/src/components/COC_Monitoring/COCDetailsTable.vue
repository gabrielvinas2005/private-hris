<template>
  <el-card shadow="never" class="table-with-loading">
    <el-table
      :data="pagedRows"
      border
      size="small"
      height="400"
      stripe
      :header-cell-style="{ background: '#f8fafc', color: '#334155' }"
      class="w-full"
    >
      <template #empty>
        <el-empty v-if="!loading" description="No COC records found" />
      </template>
      <el-table-column type="index" label="#" width="60" :index="getRowIndex" />
      <el-table-column label="Emp No" width="120">
        <template #default="{ row }">
          <EmployeeDataPopulate :employee="row.employee" field="empNo" />
        </template>
      </el-table-column>
      <el-table-column label="Employee" min-width="280">
        <template #default="{ row }">
          <div class="emp">
            <EmployeeDataPopulate :employee="row.employee" field="photo" />
            <EmployeeDataPopulate :employee="row.employee" field="namePosition" />
          </div>
        </template>
      </el-table-column>
      <el-table-column label="Department" min-width="200">
        <template #default="{ row }">
          <EmployeeDataPopulate :employee="row.employee" field="department" />
        </template>
      </el-table-column>
      <el-table-column prop="months" label="Month" width="150" align="center">
        <template #default="{ row }">{{ row.months || '-' }}</template>
      </el-table-column>
      <el-table-column prop="total_hours" label="Cumulative (hrs)" width="120" align="right">
        <template #default="{ row }">{{ formatNumber(row.total_hours) }}</template>
      </el-table-column>
      <el-table-column prop="total_leave_days" label="Leave Used (days)" width="120" align="right">
        <template #default="{ row }">{{ formatNumber(row.total_leave_days, 3) }}</template>
      </el-table-column>
      <el-table-column prop="remaining_balance" label="Remaining (hrs)" width="120" align="right">
        <template #default="{ row }">{{ formatNumber(row.remaining_balance) }}</template>
      </el-table-column>
      <el-table-column prop="converted_days" label="Converted (days)" width="120" align="right">
        <template #default="{ row }">{{ formatNumber(row.converted_days, 3) }}</template>
      </el-table-column>
    </el-table>
    <TableLoadingOverlay :loading="loading" text="Loading COC records..." />
  </el-card>
</template>

<script setup>
import { defineProps, computed } from 'vue'
import EmployeeDataPopulate from '../Reusable_Components/Employee_Data_Populate.vue'
import TableLoadingOverlay from '../Reusable_Components/TableLoadingOverlay.vue'
import { minutesToDayFraction } from '@/Composables/useDayFractionConversion'

const props = defineProps({
  rows: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false }
})

// Group incoming rows by employee and build display rows
// Since rows are already filtered by month, we just need to show one row per employee
const grouped = computed(() => {
  const map = new Map()
  ;(props.rows || []).forEach(r => {
    if (!map.has(r.employee_id)) {
      const remainingHours = Number(r.remaining_balance ?? 0)
      const totalMinutes = Number.isFinite(remainingHours) ? Math.round(remainingHours * 60) : 0
      const convertedDays = minutesToDayFraction(totalMinutes)

      map.set(r.employee_id, {
        employee_id: r.employee_id,
        name: r.name,
        employee: {
          ...r,
          employee_no: r.employee_no || '',
          name: r.name || '',
          position: r.position || '',
          department: r.department || '',
          photo: r.photo || ''
        },
        months: r.months || '',
        total_hours: r.total_hours || 0,
        total_leave_days: r.total_leave_days ?? (r.total_leave_hours != null ? Number(r.total_leave_hours) / 8 : 0),
        remaining_balance: r.remaining_balance || 0,
        converted_days: convertedDays
      })
    }
  })
  return Array.from(map.values())
})

// Use grouped rows directly since pagination is handled by parent
const pagedRows = computed(() => grouped.value)

function getRowIndex(index) {
  return index + 1
}

function formatNumber(value, decimals = 2) {
  const num = Number(value)
  if (Number.isNaN(num)) return (0).toFixed(decimals)
  return num.toLocaleString(undefined, {
    minimumFractionDigits: decimals,
    maximumFractionDigits: decimals
  })
}
</script>

<style scoped>
.w-full { width: 100%; }
.emp { display: flex; align-items: center; gap: 10px; }
.table-with-loading { position: relative; }
/* pagination handled by reusable component */
</style>





