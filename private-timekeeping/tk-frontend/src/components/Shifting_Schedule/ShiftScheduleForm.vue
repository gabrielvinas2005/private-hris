<template>
  <el-dialog v-model="model" :title="undefined" width="920px" destroy-on-close align-center :close-on-click-modal="false">
    <template #header>
      <div class="dialog-header">
        <div class="dialog-title">
          {{ formTitle }}
          <el-tag size="small" effect="plain" type="info" class="ml-3">Shifting Schedule</el-tag>
        </div>
        <div class="dialog-subtitle">Define header info and per-date time details.</div>
      </div>
    </template>
    <el-form label-width="120px" size="small" class="compact-form">
      <el-form-item label="Schedule Name" required>
        <el-input size="small" v-model="header.name" placeholder="e.g. Shift A" clearable />
      </el-form-item>
      <el-form-item label="Date Range" required>
        <div class="date-row">
          <el-date-picker class="date-input" size="small" v-model="range" type="daterange" range-separator="to" start-placeholder="Start date" end-placeholder="End date" format="MMMM D, YYYY" value-format="YYYY-MM-DD" />
        </div>
      </el-form-item>
      <el-divider>Dates</el-divider>
      <div class="table-actions mb-2">
        <div class="left-actions">
          <el-button size="small" @click="copyFirstToAll" :disabled="!rows.length">Copy first date's time schedule to rest of the days</el-button>
        </div>
        <div class="right-actions">
          <el-switch v-model="showND" active-text="Show Night Differential" />
        </div>
      </div>
      <el-table class="shift-schedule-table" :data="rows" border stripe size="small" :header-cell-style="{ background: '#f8fafc', color: '#334155', fontWeight: 600 }" :cell-style="{ padding: '6px 8px' }">
        <el-table-column prop="shift_date" label="Date" width="220">
          <template #default="{ row }">{{ fmtDate(row.shift_date) }}</template>
        </el-table-column>
        <el-table-column label="Restday" width="90" align="center">
          <template #default="{ row }"><el-checkbox v-model="row.is_restday" @change="onRestdayToggle(row)" /></template>
        </el-table-column>
        <el-table-column label="WFH" width="72" align="center">
          <template #default="{ row }">
            <template v-if="!row.is_restday">
              <el-switch v-model="row.is_wfh" />
            </template>
          </template>
        </el-table-column>
        <el-table-column label="Times" align="center">
          <el-table-column label="AM - In" width="100" align="center">
            <template #default="{ row }">
              <template v-if="!row.is_restday">
                <el-time-picker size="small" v-model="row.am_in" placeholder="--:--" format="hh:mm A" value-format="HH:mm" @change="() => recalcWorkHours(row)" clearable />
              </template>
            </template>
          </el-table-column>
          <el-table-column label="PM - Out" width="100" align="center">
            <template #default="{ row }">
              <template v-if="!row.is_restday">
                <el-time-picker size="small" v-model="row.pm_out" placeholder="--:--" format="hh:mm A" value-format="HH:mm" @change="() => recalcWorkHours(row)" clearable />
              </template>
            </template>
          </el-table-column>
        </el-table-column>
        <el-table-column v-if="showND" label="Night Differential" align="center">
          <el-table-column label="With ND" width="90" align="center">
            <template #default="{ row }">
              <template v-if="!row.is_restday">
                <el-switch v-model="row.with_nd" />
              </template>
            </template>
          </el-table-column>
          <el-table-column label="Start" width="85" align="center">
            <template #default="{ row }">
              <template v-if="!row.is_restday">
                <el-time-picker size="small" v-model="row.nd_start" placeholder="--:--" format="hh:mm A" value-format="HH:mm" :disabled="!row.with_nd" clearable />
              </template>
            </template>
          </el-table-column>
          <el-table-column label="End" width="85" align="center">
            <template #default="{ row }">
              <template v-if="!row.is_restday">
                <el-time-picker size="small" v-model="row.nd_end" placeholder="--:--" format="hh:mm A" value-format="HH:mm" :disabled="!row.with_nd" clearable />
              </template>
            </template>
          </el-table-column>
          <el-table-column label="Rate" width="85" align="center">
            <template #default="{ row }">
              <template v-if="!row.is_restday">
                <el-input size="small" v-model.number="row.nd_rate" type="number" step="0.01" :disabled="!row.with_nd" clearable />
              </template>
            </template>
          </el-table-column>
        </el-table-column>
        <el-table-column label="Other" align="center">
          <el-table-column label="Grace (min)" width="96" align="center">
            <template #default="{ row }">
              <el-input
                size="small"
                v-model.number="row.grace_period"
                type="number"
                min="0"
                step="1"
                :disabled="Number(row.flexi_hours || 0) > 0"
                @change="() => syncFlexGrace(row, 'grace')"
              />
            </template>
          </el-table-column>
          <el-table-column label="Flexi (hrs)" width="96" align="center">
            <template #default="{ row }">
              <el-input
                size="small"
                v-model.number="row.flexi_hours"
                type="number"
                min="0"
                step="0.5"
                :disabled="Number(row.grace_period || 0) > 0"
                @change="() => syncFlexGrace(row, 'flexi')"
              />
            </template>
          </el-table-column>
          <el-table-column label="Work Hours" width="96" align="center">
            <template #default="{ row }"><el-input size="small" v-model.number="row.work_hours" type="number" min="0" step="0.01" readonly /></template>
          </el-table-column>
        </el-table-column>
      </el-table>
    </el-form>
    <template #footer>
      <div class="footer-actions">
        <el-button @click="emit('update:modelValue', false)">Cancel</el-button>
        <el-button type="primary" :loading="saving" @click="onSave">Save</el-button>
      </div>
    </template>
  </el-dialog>
  
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { shiftScheduleService } from '../../services/api'
import { notify } from '../../services/notify'
// Removed report/export utilities

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  editId: { type: [Number, String], default: 0 },
})
const emit = defineEmits(['update:modelValue', 'saved'])

const model = computed({
  get: () => props.modelValue,
  set: v => emit('update:modelValue', v),
})


const header = reactive({ id: 0, name: '', date_from: null, date_to: null })
const range = ref([])
const rows = ref([])
const saving = ref(false)
const formTitle = computed(() => (props.editId ? 'Edit Shifting Schedule' : 'New Shifting Schedule'))
const showND = ref(false)

function toBoolish(v) {
  return v === true || v === 1 || v === '1'
}

function isFridayShiftDate(iso) {
  if (!iso) return false
  const dt = new Date(`${iso}T12:00:00`)
  return !isNaN(dt.getTime()) && dt.getDay() === 5
}

watch(() => props.editId, async (id) => { if (model.value) await loadForm(id) })
watch(model, async (open) => { if (open) await loadForm(props.editId); else reset() })

function reset() {
  header.id = 0
  header.name = ''
  header.date_from = null
  header.date_to = null
  range.value = []
  rows.value = []
  showND.value = false
}

async function loadForm(id) {
  const res = await shiftScheduleService.form(Number(id || 0))
  const payload = res.data ?? res
  const h = (payload.shift_schedules?.[0]) || { id: 0, name: '', date_from: null, date_to: null }
  header.id = h.id || 0
  header.name = h.name || ''
  header.date_from = h.date_from || null
  header.date_to = h.date_to || null
  if (header.date_from && header.date_to) {
    range.value = [header.date_from, header.date_to]
  }
  const details = (payload.shift_schedules_details || [])
  rows.value = details.map(d => {
    const row = {
      id: d.id ?? 0,
      shift_date: d.shift_date,
      is_restday: false,
      is_wfh:
        d.is_wfh !== undefined && d.is_wfh !== null
          ? toBoolish(d.is_wfh)
          : isFridayShiftDate(d.shift_date),
      am_in: d.am_in,
      am_out: d.am_out,
      break_in: d.break_in,
      break_out: d.break_out,
      pm_in: d.pm_in,
      pm_out: d.pm_out,
      with_nd: d.with_nd === true || d.with_nd === 1 || d.with_nd === '1',
      nd_start: d.nd_start,
      nd_end: d.nd_end,
      nd_rate: d.nd_rate,
      grace_period: d.grace_period ?? 0,
      flexi_hours: d.flexi_hours ?? 0,
      work_hours: d.work_hours ?? 0,
    }
    recalcWorkHours(row)
    syncFlexGrace(row)
    return row
  })
  // if new schedule, generate date rows from range
  if (!id && header.id === 0 && range.value?.length === 2) {
    generateRowsFromRange()
  }
}

watch(range, () => { if (header.id === 0) generateRowsFromRange() })

function generateRowsFromRange() {
  const [from, to] = range.value || []
  if (!from || !to) { rows.value = []; return }
  const dates = enumerateDates(from, to)
  rows.value = dates.map(d => ({
    id: 0,
    shift_date: d,
    is_restday: false,
    is_wfh: isFridayShiftDate(d),
    am_in: null, am_out: null, break_in: null, break_out: null, pm_in: null, pm_out: null,
    with_nd: false,
    nd_start: null, nd_end: null, nd_rate: null,
    grace_period: 0, flexi_hours: 0, work_hours: 0,
  }))
}

function enumerateDates(from, to) {
  const start = new Date(from)
  const end = new Date(to)
  const out = []
  for (let d = new Date(start); d <= end; d.setDate(d.getDate() + 1)) {
    out.push(d.toISOString().slice(0, 10))
  }
  return out
}

function fmtDate(d) {
  if (!d) return ''
  const dt = new Date(d)
  if (isNaN(dt)) return d
  const months = ['January','February','March','April','May','June','July','August','September','October','November','December']
  const days = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday']
  return `${months[dt.getMonth()]} ${dt.getDate()}, ${dt.getFullYear()} (${days[dt.getDay()]})`
}

function syncFlexGrace(row, source = null) {
  const grace = Number(row.grace_period) || 0
  const flexi = Number(row.flexi_hours) || 0
  row.grace_period = grace
  row.flexi_hours = flexi

  if (source === 'grace') {
    if (grace > 0) row.flexi_hours = 0
  } else if (source === 'flexi') {
    if (flexi > 0) row.grace_period = 0
  } else if (grace > 0 && flexi > 0) {
    row.flexi_hours = 0
  }
}

function toTime(val) {
  if (val === undefined || val === null || val === '') return null
  return String(val).length === 5 ? `${val}:00` : String(val)
}

function toNum(val) {
  const n = Number(val)
  return Number.isFinite(n) ? n : null
}

// Helpers to compute work hours similar to Fix Schedule
function parseMinutes(value) {
  if (!value) return null
  const timeStr = String(value).split(':').slice(0, 2).join(':')
  const [hh, mm] = timeStr.split(':')
  const h = Number(hh), m = Number(mm)
  if (!Number.isFinite(h) || !Number.isFinite(m)) return null
  if (h < 0 || h > 23 || m < 0 || m > 59) return null
  return h * 60 + m
}
function diffHours(start, end) {
  const s = parseMinutes(start)
  const e = parseMinutes(end)
  if (s === null || e === null) return 0
  const minutes = e >= s ? (e - s) : (e + 24 * 60 - s)
  return minutes > 0 ? minutes / 60.0 : 0
}
function recalcWorkHours(row) {
  if (row.is_restday) {
    row.work_hours = 0
    return
  }
  const amComplete = !!row.am_in && !!row.am_out
  const pmComplete = !!row.pm_in && !!row.pm_out
  let total = 0
  if (amComplete && pmComplete) {
    total = diffHours(row.am_in, row.am_out) + diffHours(row.pm_in, row.pm_out)
  } else if (row.am_in && row.pm_out) {
    total = diffHours(row.am_in, row.pm_out)
    if (row.break_in && row.break_out) {
      total -= diffHours(row.break_in, row.break_out)
    }
    if (!Number.isFinite(total) || total < 0) total = 0
  } else {
    total =
      (amComplete ? diffHours(row.am_in, row.am_out) : 0) +
      (pmComplete ? diffHours(row.pm_in, row.pm_out) : 0)
  }
  if (!Number.isFinite(total) || total < 0) total = 0
  row.work_hours = Math.round(total * 100) / 100
}

function onRestdayToggle(row) {
  if (row.is_restday) {
    row.is_wfh = false
    row.with_nd = false
    row.nd_start = null
    row.nd_end = null
    row.nd_rate = null
    row.am_in = null
    row.am_out = null
    row.break_in = null
    row.break_out = null
    row.pm_in = null
    row.pm_out = null
    row.grace_period = 0
    row.flexi_hours = 0
    row.work_hours = 0
  }
}

function buildSubmitPayload() {
  const p = {
    name: header.name,
    date_from: range.value?.[0] || header.date_from,
    date_to: range.value?.[1] || header.date_to,
    id: [],
    shift_date: [],
    am_in: [], am_out: [], break_in: [], break_out: [], pm_in: [], pm_out: [],
    nd_start: [], nd_end: [], nd_rate: [],
    with_nd: [],
    is_wfh: [],
    grace_period: [], flexi_hours: [], work_hours: [],
  }
  rows.value.forEach(r => {
    recalcWorkHours(r)
    syncFlexGrace(r)
    p.id.push(Number(r.id || 0))
    p.shift_date.push(r.shift_date)
    p.am_in.push(toTime(r.am_in))
    p.am_out.push(toTime(r.am_out))
    p.break_in.push(toTime(r.break_in))
    p.break_out.push(toTime(r.break_out))
    p.pm_in.push(toTime(r.pm_in))
    p.pm_out.push(toTime(r.pm_out))
    p.nd_start.push(toTime(r.nd_start))
    p.nd_end.push(toTime(r.nd_end))
    p.nd_rate.push(toNum(r.nd_rate))
    p.with_nd.push(r.with_nd ? 1 : 0)
    p.is_wfh.push(r.is_wfh ? 1 : 0)
    p.grace_period.push(toNum(r.grace_period))
    p.flexi_hours.push(toNum(r.flexi_hours))
    p.work_hours.push(toNum(r.work_hours))
  })
  return p
}

async function onSave() {
  saving.value = true
  try {
    if (!header.name) throw new Error('Schedule name is required')
    const id = Number(header.id || 0)
    if (id === 0) {
      if (!range.value || range.value.length !== 2) throw new Error('Date range is required')
      // Step 1: create header and dates (backend inserts NULL detail rows)
      const res = await shiftScheduleService.save(0, { name: header.name, date_from: range.value[0], date_to: range.value[1] })
      const newId = Number((res && res.id) || (res && res.data && res.data.id) || 0)
      if (!newId) throw new Error('Failed to create shift schedule')
      header.id = newId
      // Step 2: immediately upsert details with current rows
      const detailsPayload = buildSubmitPayload()
      await shiftScheduleService.save(newId, detailsPayload)
    } else {
      const payload = buildSubmitPayload()
      await shiftScheduleService.save(id, payload)
    }
    notify.success('Shift schedule saved successfully')
    emit('saved')
    emit('update:modelValue', false)
  } catch (err) {
    notify.error(err?.message || 'Failed to save shift schedule')
  } finally {
    saving.value = false
  }
}

async function onEmployeesUpdated() {
  try {
    const res = await shiftScheduleService.form(Number(header.id))
    const payload = res.data ?? res
    await emit('saved')
  } catch (_) {}
}

// Report/export code removed from form component

function copyFirstToAll() {
  if (!rows.value || rows.value.length === 0) return
  const first = rows.value[0]
  if (!first) return
  const clone = (v) => (v === undefined ? null : JSON.parse(JSON.stringify(v)))
  rows.value = rows.value.map((r, idx) => {
    if (idx === 0) return r
    const updated = {
      ...r,
      is_wfh: !!first.is_wfh,
      with_nd: !!first.with_nd,
      am_in: clone(first.am_in),
      am_out: clone(first.am_out),
      break_in: clone(first.break_in),
      break_out: clone(first.break_out),
      pm_in: clone(first.pm_in),
      pm_out: clone(first.pm_out),
      nd_start: clone(first.nd_start),
      nd_end: clone(first.nd_end),
      nd_rate: first.nd_rate,
      grace_period: first.grace_period,
      flexi_hours: first.flexi_hours,
      work_hours: first.work_hours,
    }
    recalcWorkHours(updated)
    const source = Number(updated.grace_period) > 0 ? 'grace' : Number(updated.flexi_hours) > 0 ? 'flexi' : null
    syncFlexGrace(updated, source)
    return updated
  })
}
</script>

<style scoped>
.ml-3 { margin-left: 12px; }
.compact-form :deep(.el-form-item) { margin-bottom: 8px; }
.date-row { display: flex; align-items: center; gap: 8px; width: 100%; }
.date-input { flex: 1; min-width: 0; }
.flex-1 { flex: 1; }
.remove-text-btn { font-weight: 600; }
.dialog-header { display: flex; flex-direction: column; }
.dialog-title { font-weight: 700; font-size: 16px; color: #0f172a; display: flex; align-items: center; }
.dialog-subtitle { color: #64748b; font-size: 12px; margin-top: 2px; }
.footer-actions { display: flex; justify-content: flex-end; width: 100%; gap: 8px; }
.table-actions {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}
.left-actions {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}
.right-actions {
  display: flex;
  align-items: center;
  gap: 8px;
}
.mb-2 { margin-bottom: 8px; }
.shift-schedule-table {
  width: 100%;
}
</style>


