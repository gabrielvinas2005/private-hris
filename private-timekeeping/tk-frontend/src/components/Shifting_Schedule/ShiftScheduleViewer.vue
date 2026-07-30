<template>
  <el-dialog v-model="model" :title="undefined" width="900px" destroy-on-close align-center :close-on-click-modal="false" class="shift-schedule-dialog">
    <template #header>
      <div class="dialog-header">
        <div class="dialog-title">
          View Shifting Schedule
          <el-tag size="small" effect="plain" type="info" class="ml-3">Details</el-tag>
        </div>
        <div class="dialog-subtitle">Assigned employees for this shift schedule.</div>
      </div>
    </template>

    <div class="summary">
      <el-descriptions :column="4" border>
        <el-descriptions-item label="Schedule Name" :span="4">{{ header.name }}</el-descriptions-item>
        <el-descriptions-item label="Date From" :span="2">{{ fmt(header.date_from) }}</el-descriptions-item>
        <el-descriptions-item label="To" :span="2">{{ fmt(header.date_to) }}</el-descriptions-item>
        <el-descriptions-item label="Employees Assigned" :span="4">
          <span>{{ employees.length }}</span>
        </el-descriptions-item>
      </el-descriptions>
    </div>

    <!-- Night Differential Option -->
    <div class="night-diff-option" style="margin-bottom: 12px;">
      <el-checkbox v-model="showNightDiff" size="large" disabled>
        Night Differential
      </el-checkbox>
    </div>

    <!-- Added: full schedule table -->
    <div style="overflow-x: auto; width: 100%;">
      <el-table :data="filteredRows" border stripe size="small" v-loading="loading" :header-cell-style="{ background: '#f8fafc', color: '#334155', fontWeight: 600 }" :cell-style="{ padding: '6px 8px' }">
        <el-table-column prop="shift_date" label="Date" width="160">
          <template #default="{ row }">{{ fmt(row.shift_date) }}</template>
        </el-table-column>
        <el-table-column label="WFH" width="72" align="center">
          <template #default="{ row }">{{ row.is_wfh ? 'Yes' : 'No' }}</template>
        </el-table-column>
        <el-table-column label="Times" align="center">
          <el-table-column label="AM - In" width="110" align="center">
            <template #default="{ row }">{{ formatTime(row.am_in) }}</template>
          </el-table-column>
          <el-table-column label="AM - Out" width="110" align="center">
            <template #default="{ row }">{{ formatTime(row.am_out) }}</template>
          </el-table-column>
          <el-table-column label="Break - In" width="110" align="center">
            <template #default="{ row }">{{ formatTime(row.break_in) }}</template>
          </el-table-column>
          <el-table-column label="Break - Out" width="110" align="center">
            <template #default="{ row }">{{ formatTime(row.break_out) }}</template>
          </el-table-column>
          <el-table-column label="PM - In" width="110" align="center">
            <template #default="{ row }">{{ formatTime(row.pm_in) }}</template>
          </el-table-column>
          <el-table-column label="PM - Out" width="110" align="center">
            <template #default="{ row }">{{ formatTime(row.pm_out) }}</template>
          </el-table-column>
        </el-table-column>
        <el-table-column v-if="showNightDiff" label="Night Diff" align="center">
          <el-table-column label="Start" width="110" align="center">
            <template #default="{ row }">{{ formatTime(row.nd_start) }}</template>
          </el-table-column>
          <el-table-column label="End" width="110" align="center">
            <template #default="{ row }">{{ formatTime(row.nd_end) }}</template>
          </el-table-column>
          <el-table-column label="Rate" width="110" align="center">
            <template #default="{ row }">{{ row.nd_rate ?? '' }}</template>
          </el-table-column>
        </el-table-column>
        <el-table-column label="Other" align="center">
          <el-table-column label="Grace (min)" width="120" align="center">
            <template #default="{ row }">{{ formatGracePeriod(row.grace_period) }}</template>
          </el-table-column>
          <el-table-column label="Flexi (hrs)" width="120" align="center">
            <template #default="{ row }">{{ formatHours(row.flexi_hours) }}</template>
          </el-table-column>
          <el-table-column label="Work Hours" width="120" align="center">
            <template #default="{ row }">{{ formatHours(row.work_hours) }}</template>
          </el-table-column>
        </el-table-column>
      </el-table>
    </div>

    <template #footer>
      <div class="footer-actions">
        <PreviewExport
          :html-content="reportHtmlContent"
          :title="'Shift Schedule Viewer Report'"
          :filename="computedFilename"
          :on-excel="handleExportExcel"
          :on-pdf="handleExportPDF"
          :on-word="handleExportWord"
          :loading="loading || htmlLoading"
        />
        <el-button @click="emit('update:modelValue', false)">Close</el-button>
      </div>
    </template>
  </el-dialog>
</template>


<script setup>
import { computed, reactive, ref, watch, nextTick } from 'vue'
import { shiftScheduleService } from '../../services/api'
import PreviewExport from '../Reusable_Components/Preview&Export.vue'
import { ElMessage } from 'element-plus'
import { formatTime as formatTimeAMPM } from '../../Composables/useTimeFormatting'
import { useBackendReportExport } from '../../Composables/useBackendReportExport'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  viewId: { type: [Number, String], default: 0 },
})
const emit = defineEmits(['update:modelValue'])

const model = computed({ get: () => props.modelValue, set: v => emit('update:modelValue', v) })
const loading = ref(false)
const header = reactive({ id: 0, name: '', date_from: '', date_to: '' })
const employees = ref([])
const rows = ref([])
// Read-only flag: true when all rows have no specific ND schedule (nd_start/end null and nd_rate = 0)
const showNightDiff = ref(false)

const { exportToExcel, exportToWord, getHtmlPreview, htmlLoading } = useBackendReportExport()
const reportHtmlContent = ref('')

function sanitizeFilename(name) {
  return (name || '').replace(/[^a-zA-Z0-9]/g, '_') || 'shift_schedule_viewer'
}

const computedFilename = computed(() => 
  `shift_schedule_viewer_${sanitizeFilename(header.name)}_${new Date().toISOString().slice(0, 10)}`
)

function fmt(d) {
  if (!d) return ''
  const dt = new Date(d)
  if (isNaN(dt)) return d
  const months = ['January','February','March','April','May','June','July','August','September','October','November','December']
  return `${months[dt.getMonth()]} ${dt.getDate()}, ${dt.getFullYear()}`
}

watch(model, async (open) => { if (open) await load(props.viewId); else reset() })
watch(() => props.viewId, async (id) => { if (model.value) await load(id) })
watch([rows, () => header.name, () => header.date_from, () => header.date_to], async () => { 
  if (model.value) await loadHtmlPreview() 
}, { deep: true })

function reset() {
  header.id = 0; header.name = ''; header.date_from = ''; header.date_to = ''
  employees.value = []
  rows.value = []
  showNightDiff.value = false
}

async function load(id) {
  if (!id) return
  loading.value = true
  try {
    const res = await shiftScheduleService.form(Number(id))
    const payload = res?.data ?? res

    // Header can be returned as an array (shift_schedules) or single object (shift_schedule)
    const headerArray = Array.isArray(payload?.shift_schedules) ? payload.shift_schedules : null
    const headerObj = payload?.shift_schedule || (headerArray && headerArray[0]) || {}
    const h = headerObj || { id: 0, name: '', date_from: '', date_to: '' }

    header.id = Number(h.id) || 0
    header.name = h.name || ''
    header.date_from = h.date_from || ''
    header.date_to = h.date_to || ''

    // Employees list (optional)
    employees.value = Array.isArray(payload?.employees) ? payload.employees : []

    // Details can be under shift_schedules_details (array) or details (array)
    const details = Array.isArray(payload?.shift_schedules_details)
      ? payload.shift_schedules_details
      : (Array.isArray(payload?.details) ? payload.details : [])

    rows.value = details.map(d => ({
      id: d.id ?? 0,
      shift_date: d.shift_date,
      is_wfh: d.is_wfh === true || d.is_wfh === 1 || d.is_wfh === '1',
      am_in: d.am_in,
      am_out: d.am_out,
      break_in: d.break_in,
      break_out: d.break_out,
      pm_in: d.pm_in,
      pm_out: d.pm_out,
      nd_start: d.nd_start,
      nd_end: d.nd_end,
      nd_rate: d.nd_rate,
      grace_period: d.grace_period ?? 0,
      flexi_hours: d.flexi_hours ?? 0,
      work_hours: d.work_hours ?? 8,
    }))
    
    // Night Differential flag (read-only): checked when there IS data in nd_start, nd_end, or nd_rate
    showNightDiff.value = rows.value.length > 0 && rows.value.some(row => {
      const hasNdStart = row.nd_start !== null && row.nd_start !== undefined && String(row.nd_start).trim() !== ''
      const hasNdEnd = row.nd_end !== null && row.nd_end !== undefined && String(row.nd_end).trim() !== ''
      const rate = Number(row.nd_rate || 0)
      return hasNdStart || hasNdEnd || rate > 0
    })
  } finally {
    loading.value = false
  }
  await loadHtmlPreview()
}

// Only show dates within header date range
const filteredRows = computed(() => {
  if (!header.date_from || !header.date_to) return rows.value.filter(r => !isAllTimeFieldsNull(r))
  const from = new Date(header.date_from)
  const to = new Date(header.date_to)
  if (isNaN(from) || isNaN(to)) return rows.value.filter(r => !isAllTimeFieldsNull(r))
  const fromTime = from.getTime()
  const toTime = to.getTime()
  return rows.value.filter(r => {
    const d = new Date(r.shift_date)
    if (isNaN(d)) return false
    const t = d.getTime()
    // Exclude if all major time fields are null
    if (isAllTimeFieldsNull(r)) return false
    return t >= fromTime && t <= toTime
  })
})

// Use centralized AM/PM formatter
const formatTime = (value) => formatTimeAMPM(value)

function formatTimeRange(start, end) {
  const startTime = formatTime(start)
  const endTime = formatTime(end)
  if (!startTime && !endTime) return ''
  if (!startTime) return endTime
  if (!endTime) return startTime
  return `${startTime} - ${endTime}`
}

// Helper functions for formatting time values
function formatGracePeriod(value) {
  if (!value || value === 0) return 0
  return Math.round(Number(value))
}

function formatHours(value) {
  if (!value || value === 0) return '0 hrs'
  const numValue = Number(value)
  const hours = Math.floor(numValue)
  const minutes = Math.round((numValue - hours) * 60)
  
  if (hours === 0 && minutes === 0) return '0 hrs'
  if (hours === 0) return `${minutes} mins`
  if (minutes === 0) return `${hours} hr${hours === 1 ? '' : 's'}`
  return `${hours} hr${hours === 1 ? '' : 's'} ${minutes} mins`
}

// Helper functions for export formatting
function formatGracePeriodForExport(value) {
  if (!value || value === 0) return 0
  return Math.round(Number(value))
}

function formatHoursForExport(value) {
  if (!value || value === 0) return '0 hrs'
  const numValue = Number(value)
  const hours = Math.floor(numValue)
  const minutes = Math.round((numValue - hours) * 60)
  
  if (hours === 0 && minutes === 0) return '0 hrs'
  if (hours === 0) return `${minutes} mins`
  if (minutes === 0) return `${hours} hr${hours === 1 ? '' : 's'}`
  return `${hours} hr${hours === 1 ? '' : 's'} ${minutes} mins`
}

async function loadHtmlPreview() {
  if (!rows.value || !rows.value.length) {
    reportHtmlContent.value = ''
    return
  }
  reportHtmlContent.value = await getHtmlPreview('shift_schedule_viewer', {
    schedule_name: header.name || '',
    date_from: header.date_from || '',
    date_to: header.date_to || '',
    schedule_data: filteredRows.value || []
  })
}

async function handleExportExcel() {
  await exportToExcel('shift_schedule_viewer', {
    schedule_name: header.name || '',
    date_from: header.date_from || '',
    date_to: header.date_to || '',
    schedule_data: filteredRows.value || []
  }, computedFilename.value)
}

async function handleExportWord() {
  await exportToWord('shift_schedule_viewer', {
    schedule_name: header.name || '',
    date_from: header.date_from || '',
    date_to: header.date_to || '',
    schedule_data: filteredRows.value || []
  }, computedFilename.value)
}

async function handleExportPDF() {
  const html = await getHtmlPreview('shift_schedule_viewer', {
    schedule_name: header.name || '',
    date_from: header.date_from || '',
    date_to: header.date_to || '',
    schedule_data: filteredRows.value || []
  })
  if (!html) return
  
  const { useReportGenerator } = await import('../../Composables/useReportGenerator')
  const { exportToPDF } = useReportGenerator()
  await exportToPDF(html, computedFilename.value)
}

// Helper to check if all major time fields are null/empty
function isAllTimeFieldsNull(row) {
  const fields = ["am_in","am_out","break_in","break_out","pm_in","pm_out"]
  return fields.every(k => row[k] === null || row[k] === undefined || String(row[k]).trim() === '')
}

</script>

<style scoped>
.ml-3 { margin-left: 12px; }
.dialog-header { display: flex; flex-direction: column; }
.dialog-title { font-weight: 700; font-size: 16px; color: #0f172a; display: flex; align-items: center; }
.dialog-subtitle { color: #64748b; font-size: 12px; margin-top: 2px; }
.footer-actions { display: flex; justify-content: flex-end; width: 100%; gap: 8px; }
.summary { margin-bottom: 12px; }
.row-between { display: flex; align-items: center; justify-content: space-between; width: 100%; }
</style>

<style>
.shift-schedule-dialog .el-dialog {
  max-height: 90vh;
  display: flex;
  flex-direction: column;
}

.shift-schedule-dialog .el-dialog__body {
  flex: 1;
  overflow-y: auto;
  max-height: calc(90vh - 120px);
}
</style>


