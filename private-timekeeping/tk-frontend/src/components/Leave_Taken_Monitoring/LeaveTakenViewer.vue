<template>
  <el-dialog
    v-model="model"
    :title="headerTitle"
    width="720px"
    destroy-on-close
    align-center
    append-to-body
    :show-close="true"
    :close-on-click-modal="true"
    :close-on-press-escape="true"
  >
    <div v-if="header" class="mb-3 employee-header">
      <EmployeeDataPopulate :employee="header" field="photo" />
      <EmployeeDataPopulate :employee="header" />
    </div>
    <div class="controls">
      <span class="muted">Year:</span>
      <el-select v-model="yearModel" size="small" class="year-select" @change="onYearChange">
        <el-option v-for="y in years" :key="y" :label="String(y)" :value="y" />
      </el-select>
    </div>
    <el-table :data="rows" height="520" stripe v-loading="loading">
      <el-table-column prop="leave_types" label="Leave Type" min-width="220" />
      <el-table-column prop="leave_taken" label="Taken" width="120">
        <template #default="{ row }">{{ formatNumber(row.leave_taken) }}</template>
      </el-table-column>
      <el-table-column prop="leave_balance" label="Balance" width="120">
        <template #default="{ row }">{{ formatNumber(row.leave_balance) }}</template>
      </el-table-column>
    </el-table>

    <template #footer>
      <div class="footer-actions">
        <el-button @click="model = false">Close</el-button>
      </div>
    </template>
  </el-dialog>
</template>

<script setup>
import { computed, ref } from 'vue'
import EmployeeDataPopulate from '../Reusable_Components/Employee_Data_Populate.vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  header: { type: Object, default: null },
  rows: { type: Array, default: () => [] },
  year: { type: Number, default: new Date().getFullYear() },
  loading: { type: Boolean, default: false }
})
const emit = defineEmits(['update:modelValue','change-year'])

const model = computed({
  get: () => props.modelValue,
  set: v => emit('update:modelValue', v)
})

const headerTitle = computed(() => props.header?.name ? `Leave Taken - ${props.header.name}` : 'Leave Taken')

const formatNumber = (v) => {
  const n = Number(v || 0)
  return n.toLocaleString(undefined, { minimumFractionDigits: 3, maximumFractionDigits: 3 })
}

const years = Array.from({ length: 6 }, (_, i) => new Date().getFullYear() - i)
const yearModel = ref(props.year)
const onYearChange = (y) => emit('change-year', y)
</script>

<style scoped>
.employee-header { display: flex; align-items: center; gap: 12px; }
.mb-3 { margin-bottom: 12px; }
.font-semibold { font-weight: 600; }
.text-sm { font-size: 14px; }
.text-gray-600 { color: #4b5563; }
.controls { display: flex; align-items: center; gap: 8px; margin-bottom: 10px; }
.muted { color: #6b7280; font-size: 12px; }
.year-select { width: 120px; }
.footer-actions { text-align: right; }
/* Tighten dialog inner spacing to remove extra white space at the bottom */
:deep(.el-dialog__body) { padding-bottom: 0 !important; }
:deep(.el-dialog__footer) { padding-top: 8px !important; padding-bottom: 10px !important; }
</style>


