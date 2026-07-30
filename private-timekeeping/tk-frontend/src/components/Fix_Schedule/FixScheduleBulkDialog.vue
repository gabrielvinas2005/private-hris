<template>
  <el-dialog v-model="model" title="Set Schedule for Multiple Days" width="1100px" destroy-on-close align-center :close-on-click-modal="false">
    <div class="grid grid-cols-12 gap-4">
      <div class="col-span-4">
        <div class="flex items-center justify-between mb-2">
          <div class="font-semibold text-slate-700">Days</div>
          <el-checkbox v-model="selectAll" @change="toggleAll">Select All</el-checkbox>
        </div>
        <el-table :data="dayOptions" border stripe size="small" :height="360">
          <el-table-column prop="label" label="Day" />
          <el-table-column label="Select" width="90" align="center">
            <template #default="{ row }">
              <el-checkbox v-model="row.checked" />
            </template>
          </el-table-column>
        </el-table>
      </div>

      <div class="col-span-8">
        <el-form label-width="140px" size="small">
          <el-divider content-position="left">Times</el-divider>
          <div class="grid grid-cols-2 gap-3">
            <el-form-item label="AM - In">
              <el-time-picker v-model="form.am_in" placeholder="--:--" format="hh:mm A" value-format="HH:mm" @change="recalcWorkHours" clearable />
            </el-form-item>
            <!-- <el-form-item label="AM - Out">
              <el-time-picker v-model="form.am_out" placeholder="--:--" format="hh:mm A" value-format="HH:mm" @change="recalcWorkHours" clearable />
            </el-form-item> -->
            <!-- <el-form-item label="Break - In">
              <el-time-picker v-model="form.break_in" placeholder="--:--" format="hh:mm A" value-format="HH:mm" clearable />
            </el-form-item> -->
            <!-- <el-form-item label="Break - Out">
              <el-time-picker v-model="form.break_out" placeholder="--:--" format="hh:mm A" value-format="HH:mm" clearable />
            </el-form-item> -->
            <!-- <el-form-item label="PM - In">
              <el-time-picker v-model="form.pm_in" placeholder="--:--" format="hh:mm A" value-format="HH:mm" @change="recalcWorkHours" clearable />
            </el-form-item> -->
            <el-form-item label="PM - Out">
              <el-time-picker v-model="form.pm_out" placeholder="--:--" format="hh:mm A" value-format="HH:mm" @change="recalcWorkHours" clearable />
            </el-form-item>
          </div>

          <el-divider content-position="left">Work from home</el-divider>
          <div class="grid grid-cols-2 gap-3 items-center">
            <el-form-item label="WFH">
              <el-switch v-model="form.is_wfh" />
            </el-form-item>
          </div>

          <el-divider content-position="left">Night Differential</el-divider>
          <div class="grid grid-cols-2 gap-3 items-center">
            <el-form-item label="With Night Differential">
              <el-switch v-model="form.with_nd" />
            </el-form-item>
            <el-form-item label="ND Rate">
              <el-input v-model.number="form.nd_rate" type="number" step="0.01" placeholder="e.g. 0.10" />
            </el-form-item>
            <el-form-item label="ND Start">
              <el-time-picker v-model="form.nd_start" placeholder="--:--" format="hh:mm A" value-format="HH:mm" :disabled="!form.with_nd" clearable />
            </el-form-item>
            <el-form-item label="ND End">
              <el-time-picker v-model="form.nd_end" placeholder="--:--" format="hh:mm A" value-format="HH:mm" :disabled="!form.with_nd" clearable />
            </el-form-item>
          </div>

          <el-divider content-position="left">Other</el-divider>
          <div class="grid grid-cols-3 gap-3">
            <el-form-item label="Grace (min)">
              <el-input
                v-model.number="form.grace_period"
                type="number"
                min="0"
                placeholder="0"
                :disabled="Number(form.flexi_hours || 0) > 0"
                @change="() => syncFlexGrace('grace')"
              />
            </el-form-item>
            <el-form-item label="Flexi (hrs)">
              <el-input
                v-model.number="form.flexi_hours"
                type="number"
                min="0"
                step="0.5"
                placeholder="0"
                :disabled="Number(form.grace_period || 0) > 0"
                @change="() => syncFlexGrace('flexi')"
              />
            </el-form-item>
            <el-form-item label="Work Hours">
              <el-input v-model.number="form.work_hours" type="number" min="0" step="0.01" placeholder="Auto-calculated" readonly />
            </el-form-item>
          </div>
        </el-form>
      </div>
    </div>

    <template #footer>
      <div class="footer-actions">
        <el-button @click="onClear">Clear</el-button>
        <el-button @click="emit('update:modelValue', false)">Cancel</el-button>
        <el-button type="primary" @click="onSet">Set</el-button>
      </div>
    </template>
  </el-dialog>
  
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { notify } from '../../services/notify'

const props = defineProps({
  modelValue: { type: Boolean, default: false }
})
const emit = defineEmits(['update:modelValue', 'set'])

const model = computed({ get: () => props.modelValue, set: v => emit('update:modelValue', v) })

const dayOptions = ref([
  { value: 1, label: 'Monday', checked: false },
  { value: 2, label: 'Tuesday', checked: false },
  { value: 3, label: 'Wednesday', checked: false },
  { value: 4, label: 'Thursday', checked: false },
  { value: 5, label: 'Friday', checked: false },
  { value: 6, label: 'Saturday', checked: false },
  { value: 7, label: 'Sunday', checked: false },
])

const form = reactive({
  am_in: null,
  // am_out: null,
  // break_in: null,
  // break_out: null,
  // pm_in: null,
  pm_out: null,
  is_wfh: false,
  with_nd: false,
  nd_start: null,
  nd_end: null,
  nd_rate: null,
  grace_period: null,
  flexi_hours: null,
  work_hours: 0,
})

const selectAll = ref(false)

watch(model, (open) => {
  if (!open) return
  // reset selections/inputs when opened
  dayOptions.value.forEach(d => { d.checked = false })
  Object.assign(form, { am_in: null, /* am_out: null, break_in: null, break_out: null, pm_in: null, */ pm_out: null, is_wfh: false, with_nd: false, nd_start: null, nd_end: null, nd_rate: null, grace_period: null, flexi_hours: null, work_hours: 0 })
  selectAll.value = false
})

function onSet() {
  const selected = dayOptions.value.filter(d => d.checked).map(d => d.value)
  if (selected.length === 0) {
    notify.warning('Please select at least one day')
    return
  }
  emit('set', { days: selected, values: { ...form } })
  emit('update:modelValue', false)
}

function onClear() {
  Object.assign(form, { am_in: null, /* am_out: null, break_in: null, break_out: null, pm_in: null, */ pm_out: null, is_wfh: false, with_nd: false, nd_start: null, nd_end: null, nd_rate: null, grace_period: null, flexi_hours: null, work_hours: 0 })
  recalcWorkHours()
}

function toggleAll(val) {
  dayOptions.value.forEach(d => { d.checked = !!val })
}

// Parse HH:mm or HH:mm:ss into minutes since midnight
function parseMinutes(value) {
  if (!value) return null
  // Remove seconds if present (HH:mm:ss -> HH:mm)
  const timeStr = String(value).split(':').slice(0, 2).join(':')
  const [hh, mm] = timeStr.split(':')
  const h = Number(hh), m = Number(mm)
  if (!Number.isFinite(h) || !Number.isFinite(m)) return null
  if (h < 0 || h > 23 || m < 0 || m > 59) return null
  return h * 60 + m
}

// Compute hours between two times
function diffHours(start, end) {
  const s = parseMinutes(start)
  const e = parseMinutes(end)
  if (s === null || e === null) return 0
  // Handle same-day times (end >= start) or overnight (end < start)
  const minutes = e >= s ? (e - s) : (e + 24 * 60 - s)
  return minutes > 0 ? minutes / 60.0 : 0
}

// Recalculate work hours based on AM and PM times
function recalcWorkHours() {
  // Work hours = pm_out - am_in (simplified since am_out, breaks, and pm_in are commented out)
  // const am = diffHours(form.am_in, form.am_out)
  // const pm = diffHours(form.pm_in, form.pm_out)
  // let total = am + pm
  let total = diffHours(form.am_in, form.pm_out)
  if (!Number.isFinite(total) || total < 0) total = 0
  // Round to 2 decimal places to match backend
  form.work_hours = Math.round(total * 100) / 100
}

function syncFlexGrace(source) {
  const grace = Number(form.grace_period) || 0
  const flexi = Number(form.flexi_hours) || 0
  form.grace_period = grace
  form.flexi_hours = flexi

  if (source === 'grace') {
    if (grace > 0) form.flexi_hours = 0
  } else if (source === 'flexi') {
    if (flexi > 0) form.grace_period = 0
  } else if (grace > 0 && flexi > 0) {
    form.flexi_hours = 0
  }
}
</script>

<style scoped>
.footer-actions { display: flex; justify-content: flex-end; width: 100%; gap: 8px; }
</style>


