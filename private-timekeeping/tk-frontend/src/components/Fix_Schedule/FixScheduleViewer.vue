<template>
  <el-dialog v-model="model" :title="headerTitle" width="650" destroy-on-close align-center>
    <div class="mb-3 flex items-center justify-between">
      <div>
        <div class="text-slate-700 font-semibold">{{ header.name }}</div>
      </div>
      <PreviewExport
        :html-content="reportHtmlContent"
        :title="reportTitle"
        :filename="'fix_schedule_detail'"
        :on-excel="handleExportExcel"
        :on-pdf="handleExportPDF"
        :on-word="handleExportWord"
        :loading="loading || htmlLoading"
      />
    </div>

    <el-table :data="days" border stripe size="small" v-loading="loading">
      <el-table-column prop="name" label="Day" width="150">
        <template #default="{ row }">
          <span>{{ row.name }}</span>
          <span v-if="toBoolish(row.is_restday)" class="restday-indicator"> (Rest Day)</span>
        </template>
      </el-table-column>
      <el-table-column label="WFH" width="80" align="center">
        <template #default="{ row }">
          <span v-if="toBoolish(row.is_restday)"></span>
          <el-tag v-else :type="toBoolish(row.is_wfh) ? 'success' : 'info'" size="small">{{ toBoolish(row.is_wfh) ? 'Yes' : 'No' }}</el-tag>
        </template>
      </el-table-column>
      <el-table-column label="Times">
        <el-table-column label="AM - In" width="110" align="center">
          <template #default="{ row }">
            <span v-if="toBoolish(row.is_restday)"></span>
            <span v-else>{{ formatTime(row.am_in) }}</span>
          </template>
        </el-table-column>
        <!-- <el-table-column label="AM - Out" width="110" align="center">
          <template #default="{ row }">
            <span v-if="toBoolish(row.is_restday)"></span>
            <span v-else>{{ formatTime(row.am_out) }}</span>
          </template>
        </el-table-column> -->
        <!-- <el-table-column label="Break - In" width="110" align="center">
          <template #default="{ row }">
            <span v-if="toBoolish(row.is_restday)"></span>
            <span v-else>{{ formatTime(row.break_in) }}</span>
          </template>
        </el-table-column> -->
        <!-- <el-table-column label="Break - Out" width="110" align="center">
          <template #default="{ row }">
            <span v-if="toBoolish(row.is_restday)"></span>
            <span v-else>{{ formatTime(row.break_out) }}</span>
          </template>
        </el-table-column> -->
        <!-- <el-table-column label="PM - In" width="110" align="center">
          <template #default="{ row }">
            <span v-if="toBoolish(row.is_restday)"></span>
            <span v-else>{{ formatTime(row.pm_in) }}</span>
          </template>
        </el-table-column> -->
        <el-table-column label="PM - Out" width="110" align="center">
          <template #default="{ row }">
            <span v-if="toBoolish(row.is_restday)"></span>
            <span v-else>{{ formatTime(row.pm_out) }}</span>
          </template>
        </el-table-column>
      </el-table-column>
      <el-table-column v-if="hasAnyND" label="Night Diff">
        <el-table-column label="With ND" width="90" align="center">
          <template #default="{ row }">
            <span v-if="toBoolish(row.is_restday)"></span>
            <el-tag v-else :type="row.with_nd ? 'success' : 'info'" size="small">{{ row.with_nd ? 'Yes' : 'No' }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="Start" width="110" align="center">
          <template #default="{ row }">
            <span v-if="toBoolish(row.is_restday)"></span>
            <span v-else>{{ formatTime(row.nd_start) }}</span>
          </template>
        </el-table-column>
        <el-table-column label="End" width="110" align="center">
          <template #default="{ row }">
            <span v-if="toBoolish(row.is_restday)"></span>
            <span v-else>{{ formatTime(row.nd_end) }}</span>
          </template>
        </el-table-column>
        <el-table-column label="Rate" width="110" align="center">
          <template #default="{ row }">
            <span v-if="toBoolish(row.is_restday)"></span>
            <span v-else>{{ row.nd_rate }}</span>
          </template>
        </el-table-column>
      </el-table-column>
      <el-table-column label="Other">
        <el-table-column v-if="hasAnyGrace" label="Grace (min)" width="110" align="center">
          <template #default="{ row }">
            <span v-if="toBoolish(row.is_restday)"></span>
            <span v-else>{{ row.grace_period }}</span>
          </template>
        </el-table-column>
        <el-table-column v-if="hasAnyFlexi" label="Flexi (hrs)" width="110" align="center">
          <template #default="{ row }">
            <span v-if="toBoolish(row.is_restday)"></span>
            <span v-else>{{ row.flexi_hours }}</span>
          </template>
        </el-table-column>
        <el-table-column label="Work Hours" width="140" align="center">
          <template #default="{ row }">
            <span v-if="toBoolish(row.is_restday)"></span>
            <span v-else>{{ formatHours(row.work_hours) }}</span>
          </template>
        </el-table-column>
      </el-table-column>
    </el-table>
  </el-dialog>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { fixScheduleService } from '../../services/api'
import PreviewExport from '../Reusable_Components/Preview&Export.vue'
import { formatHours, formatTime } from '../../Composables/useTimeFormatting'
import { useBackendReportExport } from '../../Composables/useBackendReportExport'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  viewId: { type: [Number, String], default: 0 },
})
const emit = defineEmits(['update:modelValue'])

const model = computed({ get: () => props.modelValue, set: v => emit('update:modelValue', v) })
const header = reactive({ name: '', no_late: false, no_undertime: false, is_complete_attendance: false })
const days = ref([])
const loading = ref(false)
const headerTitle = computed(() => 'Fix Schedule Details')

const { exportToExcel, exportToWord, getHtmlPreview, htmlLoading } = useBackendReportExport()
const reportHtmlContent = ref('')
const reportTitle = computed(() => {
  const name = header.name || ''
  return `Fix Schedule Details for ${name}`
})

function toBoolish(v) { return v === true || v === 1 || v === '1' }

const hasAnyND = computed(() => (days.value || []).some(d => toBoolish(d.with_nd)))
const hasAnyGrace = computed(() => (days.value || []).some(d => Number(d.grace_period || 0) > 0))
const hasAnyFlexi = computed(() => (days.value || []).some(d => Number(d.flexi_hours || 0) > 0))

watch(() => props.viewId, async (id) => { if (model.value) await load(id) })
watch(model, async (open) => { if (open) await load(props.viewId) })
watch([() => header.name, days], async () => { if (model.value) await loadHtmlPreview() }, { deep: true })

async function load(id) {
  loading.value = true
  try {
    const res = await fixScheduleService.form(Number(id || 0))
    const payload = res.data ?? res
    const fs = (payload.fix_schedules?.[0]) || {}
    header.name = fs.name || ''
    header.no_late = toBoolish(fs.no_late)
    header.no_undertime = toBoolish(fs.no_undertime)
    header.is_complete_attendance = toBoolish(fs.is_complete_attendance)
    days.value = (payload.days || []).map(d => ({
      name: d.name,
      am_in: d.am_in, /* am_out: d.am_out,
      break_in: d.break_in, break_out: d.break_out,
      pm_in: d.pm_in, */ pm_out: d.pm_out,
      with_nd: toBoolish(d.with_nd), nd_start: d.nd_start, nd_end: d.nd_end, nd_rate: d.nd_rate,
      grace_period: d.grace_period, flexi_hours: d.flexi_hours, work_hours: d.work_hours,
      is_restday: toBoolish(d.is_restday),
      is_wfh: toBoolish(d.is_wfh),
    }))
  } finally {
    loading.value = false
  }
  await loadHtmlPreview()
}

async function loadHtmlPreview() {
  if (!days.value || !days.value.length) {
    reportHtmlContent.value = ''
    return
  }
  reportHtmlContent.value = await getHtmlPreview('fix_schedule_detail', {
    schedule_name: header.name || '',
    flags: {
      no_late: toBoolish(header.no_late),
      no_undertime: toBoolish(header.no_undertime),
      is_complete_attendance: toBoolish(header.is_complete_attendance)
    },
    days: days.value || []
  })
}

async function handleExportExcel() {
  await exportToExcel('fix_schedule_detail', {
    schedule_name: header.name || '',
    flags: {
      no_late: toBoolish(header.no_late),
      no_undertime: toBoolish(header.no_undertime),
      is_complete_attendance: toBoolish(header.is_complete_attendance)
    },
    days: days.value || []
  }, 'fix_schedule_detail')
}

async function handleExportWord() {
  await exportToWord('fix_schedule_detail', {
    schedule_name: header.name || '',
    flags: {
      no_late: toBoolish(header.no_late),
      no_undertime: toBoolish(header.no_undertime),
      is_complete_attendance: toBoolish(header.is_complete_attendance)
    },
    days: days.value || []
  }, 'fix_schedule_detail')
}

async function handleExportPDF() {
  const html = await getHtmlPreview('fix_schedule_detail', {
    schedule_name: header.name || '',
    flags: {
      no_late: toBoolish(header.no_late),
      no_undertime: toBoolish(header.no_undertime),
      is_complete_attendance: toBoolish(header.is_complete_attendance)
    },
    days: days.value || []
  })
  if (!html) return
  
  const { useReportGenerator } = await import('../../Composables/useReportGenerator')
  const { exportToPDF } = useReportGenerator()
  await exportToPDF(html, 'fix_schedule_detail')
}

// formatTime is now provided by useTimeFormatting
</script>

<style scoped>
.mb-3 { margin-bottom: 12px; }
.text-slate-700 { color: #334155; }
.font-semibold { font-weight: 600; }
.ml-2 { margin-left: 8px; }
.flag-row { display: flex; align-items: center; gap: 8px; margin-top: 4px; }
.restday-indicator { color: #dc2626; font-weight: 600; }
</style>


