<template>
  <el-dialog v-model="model" :title="headerTitle" width="1100px" destroy-on-close align-center>
    <div class="mb-3 flex items-center gap-2">
      <el-input v-model="query" placeholder="Search employee..." clearable style="max-width: 320px" />
      <div class="flex-1"></div>
      <el-button
        type="danger"
        :disabled="!selectedRows.length || loading"
        @click="removeSelected"
      >
        Remove {{ selectedRows.length ? `(${selectedRows.length})` : '' }}
      </el-button>
      <PreviewExport
        :html-content="reportHtmlContent"
        :title="'Assigned Employees Report'"
        :filename="computedFilename"
        :on-excel="handleExportExcel"
        :on-pdf="handleExportPDF"
        :on-word="handleExportWord"
        :loading="loading || htmlLoading"
      />
    </div>
    <el-table
      ref="tableRef"
      :data="sortedRows"
      row-key="id"
      border
      stripe
      height="480"
      v-loading="loading"
      @sort-change="onSortChange"
      @selection-change="handleSelectionChange"
    >
      <el-table-column type="selection" width="50" />
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
    </el-table>

    <!-- Report Preview Dialog -->
    <!-- ReportPreview removed -->
  </el-dialog>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { fixScheduleService } from '../../services/api'
import EmployeeDataPopulate from '../Reusable_Components/Employee_Data_Populate.vue'
import PreviewExport from '../Reusable_Components/Preview&Export.vue'
import { ElMessageBox } from 'element-plus'
import { notify } from '../../services/notify'
import { useSortingLogic } from '@/Composables/Sorting_Logic'
import { useBackendReportExport } from '../../Composables/useBackendReportExport'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  scheduleId: { type: [Number, String], default: 0 },
  scheduleName: { type: String, default: '' },
})
const emit = defineEmits(['update:modelValue', 'employee-removed'])

const model = computed({ get: () => props.modelValue, set: v => emit('update:modelValue', v) })
const items = ref([])
const query = ref('')
const loading = ref(false)
const tableRef = ref(null)
const selectedRows = ref([])

function handleSelectionChange(selection) {
  selectedRows.value = selection
}
const headerTitle = computed(() => `Assigned Employees for ${props.scheduleName || ''}`)

const { onSortChange, sortArray } = useSortingLogic()
const { exportToExcel, exportToWord, getHtmlPreview, htmlLoading } = useBackendReportExport()
const reportHtmlContent = ref('')

function sanitizeFilename(name) {
  return (name || '').replace(/[^a-zA-Z0-9]/g, '_') || 'schedule'
}

const computedFilename = computed(() => `assigned_employees_${sanitizeFilename(props.scheduleName)}`)

const filtered = computed(() => {
  const q = query.value.trim().toLowerCase()
  if (!q) return items.value
  return items.value.filter(e => (e.name || '').toLowerCase().includes(q))
})

const sortedRows = computed(() => sortArray(filtered.value))


watch(model, async (open) => {
  if (open) {
    await load()
  } else {
    selectedRows.value = []
    tableRef.value?.clearSelection?.()
  }
})
watch(() => props.scheduleId, async (id) => { if (model.value) await load() })
watch([items, () => props.scheduleName], async () => { if (model.value) await loadHtmlPreview() }, { deep: true })

async function load() {
  if (!props.scheduleId) return
  loading.value = true
  try {
    const res = await fixScheduleService.getAssignedEmployees(Number(props.scheduleId))
    items.value = res?.employees || res || []
  } catch (err) {
    // optional: toast
  } finally {
    loading.value = false
  }
  await loadHtmlPreview()
}

async function loadHtmlPreview() {
  if (!items.value || !items.value.length) {
    reportHtmlContent.value = ''
    return
  }
  reportHtmlContent.value = await getHtmlPreview('fix_schedule_assigned', {
    schedule_name: props.scheduleName || '',
    employees: items.value || []
  })
}

async function handleExportExcel() {
  await exportToExcel('fix_schedule_assigned', {
    schedule_name: props.scheduleName || '',
    employees: items.value || []
  }, computedFilename.value)
}

async function handleExportWord() {
  await exportToWord('fix_schedule_assigned', {
    schedule_name: props.scheduleName || '',
    employees: items.value || []
  }, computedFilename.value)
}

async function handleExportPDF() {
  const html = await getHtmlPreview('fix_schedule_assigned', {
    schedule_name: props.scheduleName || '',
    employees: items.value || []
  })
  if (!html) return
  
  const { useReportGenerator } = await import('../../Composables/useReportGenerator')
  const { exportToPDF } = useReportGenerator()
  await exportToPDF(html, computedFilename.value)
}


async function removeSelected() {
  const toRemove = selectedRows.value
  if (!toRemove.length) return
  const count = toRemove.length
  const names = toRemove.map(e => e.name).join(', ')
  const message = count === 1
    ? `Are you sure you want to remove ${names} from this schedule?`
    : `Are you sure you want to remove ${count} employees from this schedule?`
  try {
    await ElMessageBox.confirm(message, 'Remove Employees', {
      confirmButtonText: 'Remove',
      cancelButtonText: 'Cancel',
      type: 'warning',
    })
    loading.value = true
    const toastRef = notify.loading(`Removing ${count} employee(s)...`)
    let successCount = 0
    let failCount = 0
    for (const employee of toRemove) {
      try {
        await fixScheduleService.removeEmployeeFromSchedule(Number(props.scheduleId), Number(employee.id))
        successCount++
        emit('employee-removed', employee)
      } catch (_) {
        failCount++
      }
    }
    if (failCount === 0) {
      notify.success(`${successCount} employee(s) removed from schedule successfully`, { replace: toastRef })
    } else {
      notify.warning(`${successCount} removed, ${failCount} failed`, { replace: toastRef })
    }
    selectedRows.value = []
    tableRef.value?.clearSelection?.()
    await load()
  } catch (err) {
    if (err !== 'cancel') {
      notify.error('Failed to remove employees from schedule')
    }
  } finally {
    loading.value = false
  }
}

function getRowIndex(index) {
  return index + 1
}
</script>

<style scoped>
.mb-3 { margin-bottom: 12px; }
.flex { display: flex; }
.items-center { align-items: center; }
.gap-2 { gap: 8px; }
.flex-1 { flex: 1; }
.emp { display: flex; align-items: center; gap: 10px; }
</style>


