<template>
  <el-dialog
    v-model="internalVisible"
    :title="dialogTitle"
    width="1200px"
    class="edit-times-dialog"
    :close-on-click-modal="false"
    align-center
  >
    <div v-if="employee" class="edit-times-container table-with-loading">
      <el-alert
        type="info"
        :closable="false"
        show-icon
        class="mb-2"
      >
        Edited attendance should be reprocessed to update the attendance calculations.
      </el-alert>

      <div class="periods-grid">
        <!-- Preceding Period records (from previous payroll period) -->
        <div v-if="precedingRecords.length" class="period-panel period-panel-preceding">
          <h4 style="margin: 4px 0 8px; font-weight: 600;">Preceding Period</h4>
          <el-table :data="precedingRecords" border stripe size="small" height="520">
            <el-table-column prop="date" label="Date" width="120" align="center">
              <template #default="{ row }">
                {{ formatDate(row.date) }}
              </template>
            </el-table-column>
            <el-table-column label="AM In" width="110" align="center">
              <template #default="{ row }">
                <el-time-picker
                  v-model="row.am_in"
                  placeholder="--:--"
                  format="hh:mm A"
                  value-format="HH:mm"
                  clearable
                />
              </template>
            </el-table-column>
            <el-table-column label="PM Out" width="110" align="center">
              <template #default="{ row }">
                <el-time-picker
                  v-model="row.pm_out"
                  placeholder="--:--"
                  format="hh:mm A"
                  value-format="HH:mm"
                  clearable
                />
              </template>
            </el-table-column>
          </el-table>
        </div>

        <!-- Current Period records -->
        <div class="period-panel">
          <h4 style="margin: 4px 0 8px; font-weight: 600;">Current Period</h4>
          <el-table :data="currentRecords" border stripe size="small" height="520">
            <el-table-column prop="date" label="Date" width="120" align="center">
              <template #default="{ row }">
                {{ formatDate(row.date) }}
              </template>
            </el-table-column>
            <el-table-column label="AM In" width="110" align="center">
              <template #default="{ row }">
                <el-time-picker
                  v-model="row.am_in"
                  placeholder="--:--"
                  format="hh:mm A"
                  value-format="HH:mm"
                  clearable
                />
              </template>
            </el-table-column>
            <el-table-column label="PM Out" width="110" align="center">
              <template #default="{ row }">
                <el-time-picker
                  v-model="row.pm_out"
                  placeholder="--:--"
                  format="hh:mm A"
                  value-format="HH:mm"
                  clearable
                />
              </template>
            </el-table-column>
            <el-table-column label="Remarks" min-width="220">
              <template #default="{ row }">
                <el-input
                  v-model="row.remarks"
                  type="textarea"
                  :autosize="{ minRows: 1, maxRows: 3 }"
                  maxlength="255"
                  show-word-limit
                  placeholder="Enter remarks"
                />
              </template>
            </el-table-column>
          </el-table>
        </div>
      </div>

      <TableLoadingOverlay :loading="savingEdits" text="Saving time edits..." />
      <TableLoadingOverlay :loading="loadingEditData" text="Loading time entries..." />
    </div>

    <template #footer>
      <div class="dialog-footer">
        <el-button @click="handleCancel">Cancel</el-button>
        <el-button type="primary" :loading="savingEdits" @click="handleSave">
          Save Changes
        </el-button>
      </div>
    </template>
  </el-dialog>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { ElMessage } from 'element-plus'
import TableLoadingOverlay from '../Reusable_Components/TableLoadingOverlay.vue'
import { processAttendanceService } from '../../services/api'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  employee: { type: Object, default: null },
  payrollPeriodId: { type: Number, required: true }
})

const emit = defineEmits(['update:modelValue', 'save'])

const loadingEditData = ref(false)
const savingEdits = ref(false)
const editableRecords = ref([])
const adjustmentDateKeys = ref(new Set())

const currentRecords = computed(() =>
  editableRecords.value.filter((r) => !r.payroll_period_id || r.payroll_period_id === props.payrollPeriodId)
)

const precedingRecords = computed(() =>
  editableRecords.value.filter((r) => {
    const isPreceding = r.payroll_period_id && r.payroll_period_id !== props.payrollPeriodId
    if (!isPreceding) return false

    // Backend already links relevant preceding rows using target_payroll_period_id.
    if (r.target_payroll_period_id && Number(r.target_payroll_period_id) !== Number(props.payrollPeriodId)) {
      return false
    }

    // If backend provided adjustment summary dates, only show those dates.
    if (adjustmentDateKeys.value.size > 0) {
      return adjustmentDateKeys.value.has(normalizeDateKey(r.date))
    }

    return true
  })
)

const internalVisible = computed({
  get() {
    return props.modelValue
  },
  set(val) {
    emit('update:modelValue', val)
  }
})

const dialogTitle = computed(() => {
  if (!props.employee) return 'Edit Times'
  const name = props.employee.name || props.employee.full_name || ''
  return name ? `Edit Times - ${name}` : 'Edit Times'
})

watch(
  () => ({ visible: props.modelValue, employee: props.employee, periodId: props.payrollPeriodId }),
  (state) => {
    if (!state.visible || !state.employee || !state.periodId) return
    loadEditableRecords()
  },
  { immediate: false, deep: true }
)

async function loadEditableRecords() {
  loadingEditData.value = true
  try {
    const employeeId = props.employee.employee_id ?? props.employee.id ?? 0
    const response = await processAttendanceService.view(employeeId, props.payrollPeriodId)
    const data = response?.data ?? response
    const summaryDates = Array.isArray(data?.adjustment_dates) ? data.adjustment_dates : []
    adjustmentDateKeys.value = new Set(summaryDates.map((d) => normalizeDateKey(d)).filter(Boolean))

    const records = Array.isArray(data?.edit_time_records)
      ? data.edit_time_records
      : Array.isArray(data?.daily_time_records)
        ? data.daily_time_records
        : []
    editableRecords.value = records.map((r) => ({
      id: r.time_data_id ?? r.adj_id ?? r.id,
      source: r.source || 'time_data',
      payroll_period_id: Number(r.payroll_period_id || props.payrollPeriodId),
      target_payroll_period_id: r.target_payroll_period_id ? Number(r.target_payroll_period_id) : null,
      date: r.date,
      am_in: toTimeValue(r.am_in),
      am_out: toTimeValue(r.am_out),
      break_in: toTimeValue(r.break_in),
      break_out: toTimeValue(r.break_out),
      pm_in: toTimeValue(r.pm_in),
      pm_out: toTimeValue(r.pm_out),
      remarks: r.remarks ?? '',
      is_edited_bool: Number(r.is_edited || 0),
      _original: {
        am_in: toTimeValue(r.am_in),
        am_out: toTimeValue(r.am_out),
        break_in: toTimeValue(r.break_in),
        break_out: toTimeValue(r.break_out),
        pm_in: toTimeValue(r.pm_in),
        pm_out: toTimeValue(r.pm_out),
        remarks: r.remarks ?? ''
      }
    }))
  } catch (e) {
    ElMessage.error(e?.message || 'Failed to load attendance records')
    editableRecords.value = []
    adjustmentDateKeys.value = new Set()
  } finally {
    loadingEditData.value = false
  }
}

function toTimeValue(val) {
  if (!val) return null
  const s = String(val)
  if (s.length >= 5) return s.slice(0, 5) // HH:mm
  return s
}

function normalizeDateKey(value) {
  if (!value) return ''
  const s = String(value).trim()
  // Fast path for backend dates like YYYY-MM-DD or YYYY-MM-DD HH:mm:ss
  if (/^\d{4}-\d{2}-\d{2}/.test(s)) return s.slice(0, 10)
  const d = new Date(s)
  if (Number.isNaN(d.getTime())) return s
  return d.toISOString().slice(0, 10)
}

function normalizeTimeForCompare(value) {
  if (value === null || value === undefined) return null
  const s = String(value).trim()
  if (!s || s === '-:--' || s === '--:--') return null
  const hhmm = s.match(/^(\d{2}):(\d{2})$/)
  if (hhmm) return `${hhmm[1]}:${hhmm[2]}`
  const hhmmss = s.match(/^(\d{2}):(\d{2}):(\d{2})$/)
  if (hhmmss) return `${hhmmss[1]}:${hhmmss[2]}`
  return null
}

function toApiTime(value) {
  const hhmm = normalizeTimeForCompare(value)
  return hhmm ? `${hhmm}:00` : null
}

function buildChangedRecord(row) {
  const original = row._original || {}
  const changed = {
    id: row.id,
    date: row.date,
    source: row.source,
    target_payroll_period_id: row.target_payroll_period_id
  }

  let hasChanges = false
  const timeFields = ['am_in', 'am_out', 'break_in', 'break_out', 'pm_in', 'pm_out']
  for (const field of timeFields) {
    const before = normalizeTimeForCompare(original[field])
    const after = normalizeTimeForCompare(row[field])
    if (before !== after) {
      changed[field] = toApiTime(row[field])
      hasChanges = true
    }
  }

  const beforeRemarks = String(original.remarks ?? '')
  const afterRemarks = String(row.remarks ?? '')
  if (beforeRemarks !== afterRemarks) {
    changed.remarks = afterRemarks
    hasChanges = true
  }

  return hasChanges ? changed : null
}

function formatDate(value) {
  if (!value) return ''
  const d = new Date(value)
  if (Number.isNaN(d.getTime())) return value
  return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: '2-digit' })
}

function handleCancel() {
  internalVisible.value = false
}

async function handleSave() {
  if (!props.employee || !props.payrollPeriodId) return
  savingEdits.value = true
  try {
    // Group changed records by payroll_period_id so preceding and current periods can be
    // saved via separate editTimes calls on the backend.
    const batchesMap = new Map()
    for (const r of editableRecords.value) {
      const changedRecord = buildChangedRecord(r)
      if (!changedRecord) continue

      const periodId = Number(r.payroll_period_id || props.payrollPeriodId)
      if (!batchesMap.has(periodId)) {
        batchesMap.set(periodId, [])
      }
      batchesMap.get(periodId).push(changedRecord)
    }

    const payload = {
      employee_id: Number(props.employee.employee_id || props.employee.id),
      batches: Array.from(batchesMap.entries()).map(([periodId, records]) => ({
        payroll_period_id: Number(periodId),
        employee_id: Number(props.employee.employee_id || props.employee.id),
        records
      }))
    }

    if (!payload.batches.length) {
      ElMessage.info('No changes to save')
      return
    }

    for (const batch of payload.batches) {
      if (!batch || !batch.payroll_period_id || !batch.employee_id) continue
      await processAttendanceService.editTimes(batch)
    }

    ElMessage.success('Time edits saved')
    emit('save', { employee_id: payload.employee_id, persisted: true })
    internalVisible.value = false
  } catch (e) {
    ElMessage.error(e?.message || 'Failed to save changes')
  } finally {
    savingEdits.value = false
  }
}
</script>

<style scoped>
.table-with-loading {
  position: relative;
}

.periods-grid {
  display: grid;
  grid-template-columns: max-content minmax(0, 1fr);
  gap: 12px;
  align-items: start;
}

.period-panel {
  min-width: 0;
}

.period-panel-preceding {
  width: max-content;
}

:deep(.edit-times-dialog) {
  min-height: 1000px;
  display: flex;
  flex-direction: column;
}

:deep(.edit-times-dialog .el-dialog__body) {
  flex: 1;
  overflow-y: auto;
}
</style>

