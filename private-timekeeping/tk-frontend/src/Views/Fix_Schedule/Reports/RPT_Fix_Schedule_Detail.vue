<template>
  <PreviewExport 
    :html-content="reportHtmlContent"
    :title="reportTitle"
    :filename="'fix_schedule_detail'"
    :on-excel="onExportExcel"
    :on-pdf="onExportPDF"
    :on-word="onExportWord"
    :loading="loading"
  />
  
</template>

<script setup>
import { computed } from 'vue'
import PreviewExport from '../../../components/Reusable_Components/Preview&Export.vue'
import { useUnifiedReport } from '../../../Composables/useUnifiedReport'
import { formatHours, formatTime } from '../../../Composables/useTimeFormatting'

const props = defineProps({
  schedule: { type: Object, default: () => ({ name: '', no_late: false, no_undertime: false, is_complete_attendance: false }) },
  days: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
})

const { generateGenericTableReport } = useUnifiedReport()

function toBool(v) { return v === true || v === 1 || v === '1' }

// formatTime provided by useTimeFormatting

const reportTitle = computed(() => {
  const name = props.schedule?.name || ''
  const safe = String(name).replace(/</g, '&lt;').replace(/>/g, '&gt;')
  return `Fix Schedule Details for ${safe}`
})

const titleHtml = computed(() => {
  return `<h2 style="font-family:Arial;margin:0 0 8px; text-align: center; font-weight: bold;">${reportTitle.value}</h2>`
})

const flagsHtml = computed(() => {
  const nl = toBool(props.schedule?.no_late)
  const nu = toBool(props.schedule?.no_undertime)
  const ca = toBool(props.schedule?.is_complete_attendance)
  const checkbox = (label, checked) => {
    const attr = checked ? 'checked' : ''
    return `<label style="display:inline-flex;align-items:center;gap:6px;margin:0 8px;font-family:Arial;font-size:12px;color:#334155;">
      <input type="checkbox" disabled ${attr} style="width:14px;height:14px" />
      <span>${label}</span>
    </label>`
  }
  let content = checkbox('No Late', nl) + checkbox('No Undertime', nu) + checkbox('Complete Attendance', ca)
  return `<div style="text-align:center;margin:6px 0 10px;">${content}</div>`
})

const hasAnyND = computed(() => {
  const rows = Array.isArray(props.days) ? props.days : []
  return rows.some(r => toBool(r?.with_nd))
})

const hasAnyGrace = computed(() => {
  const rows = Array.isArray(props.days) ? props.days : []
  return rows.some(r => Number(r?.grace_period || 0) > 0)
})

const hasAnyFlexi = computed(() => {
  const rows = Array.isArray(props.days) ? props.days : []
  return rows.some(r => Number(r?.flexi_hours || 0) > 0)
})

function buildTableHtml(rows) {
  const includeND = hasAnyND.value
  const baseHeaders = ['Day','AM - In','AM - Out','Break - In','Break - Out','PM - In','PM - Out']
  const ndHeaders = includeND ? ['With ND','ND Start','ND End','ND Rate'] : []
  const graceHeaders = hasAnyGrace.value ? ['Grace (min)'] : []
  const flexiHeaders = hasAnyFlexi.value ? ['Flexi (hrs)'] : []
  const tailHeaders = [...graceHeaders, ...flexiHeaders, 'Work Hours']
  const headers = [...baseHeaders, ...ndHeaders, ...tailHeaders]

  const th = headers.map(h => `<th style="text-align:center;padding:6px 8px;border:1px solid #e5e7eb;background:#f1f5f9;color:#0f172a;font-weight:700;">${h}</th>`).join('')

  const trs = rows.map(r => {
    const isRestday = toBool(r.is_restday)
    const dayName = (r.name || '') + (isRestday ? ' (Rest Day)' : '')
    const cells = [
      dayName,
      isRestday ? '' : formatTime(r.am_in),
      isRestday ? '' : formatTime(r.am_out),
      isRestday ? '' : formatTime(r.break_in),
      isRestday ? '' : formatTime(r.break_out),
      isRestday ? '' : formatTime(r.pm_in),
      isRestday ? '' : formatTime(r.pm_out),
    ]
    if (includeND) {
      cells.push(
        isRestday ? '' : (toBool(r.with_nd) ? 'Yes' : 'No'),
        isRestday ? '' : formatTime(r.nd_start),
        isRestday ? '' : formatTime(r.nd_end),
        isRestday ? '' : (r.nd_rate ?? '')
      )
    }
    if (hasAnyGrace.value) cells.push(isRestday ? '' : (r.grace_period ?? ''))
    if (hasAnyFlexi.value) cells.push(isRestday ? '' : (r.flexi_hours ?? ''))
    cells.push(isRestday ? '' : formatHours(r.work_hours ?? ''))
    return `<tr>${cells.map(v => `<td style=\"padding:6px 8px;border:1px solid #e5e7eb;text-align:center;\">${v}</td>`).join('')}</tr>`
  }).join('')

  return `<table style="border-collapse:collapse;width:100%;font-family:Arial;font-size:12px;">` +
         `<thead><tr>${th}</tr></thead><tbody>${trs}</tbody></table>`
}

const reportHtmlContent = computed(() => {
  const rows = Array.isArray(props.days) ? props.days : []
  const table = buildTableHtml(rows)
  return titleHtml.value + flagsHtml.value + table
})

async function onExportExcel() {
  try {
    const { API_BASE_URL } = await import('../../../config/api')
    const reportData = {
      report_type: 'fix_schedule_detail',
      data: {
        schedule_name: props.schedule?.name || '',
        flags: {
          no_late: toBool(props.schedule?.no_late),
          no_undertime: toBool(props.schedule?.no_undertime),
          is_complete_attendance: toBool(props.schedule?.is_complete_attendance)
        },
        days: Array.isArray(props.days) ? props.days : []
      },
      filename: 'fix_schedule_detail'
    }
    
    // Get auth token
    const rawAuthToken = localStorage.getItem('auth_token')
    const rawDevToken = localStorage.getItem('dev_auth_token')
    let token = null
    if (rawAuthToken) {
      try {
        const parsed = JSON.parse(rawAuthToken)
        token = parsed?.token || rawAuthToken
      } catch (_) {
        token = rawAuthToken
      }
    } else if (rawDevToken) {
      try {
        const parsed = JSON.parse(rawDevToken)
        token = parsed?.token || rawDevToken
      } catch (_) {
        token = rawDevToken
      }
    }
    
    const response = await fetch(`${API_BASE_URL}/reports/excel`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ...(token ? { 'Authorization': `Bearer ${token}` } : {})
      },
      credentials: 'include',
      body: JSON.stringify(reportData)
    })
    
    if (!response.ok) {
      throw new Error('Failed to generate Excel report')
    }
    
    const blob = await response.blob()
    const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
    link.href = url
    link.download = 'fix_schedule_detail.xlsx'
    document.body.appendChild(link)
  link.click()
    document.body.removeChild(link)
    URL.revokeObjectURL(url)
  } catch (err) {
    console.error('Excel export failed:', err)
    const { ElMessage } = await import('element-plus')
    ElMessage.error('Failed to export Excel report')
  }
}

async function onExportWord() {
  try {
    const { API_BASE_URL } = await import('../../../config/api')
    const reportData = {
      report_type: 'fix_schedule_detail',
      data: {
        schedule_name: props.schedule?.name || '',
        flags: {
          no_late: toBool(props.schedule?.no_late),
          no_undertime: toBool(props.schedule?.no_undertime),
          is_complete_attendance: toBool(props.schedule?.is_complete_attendance)
        },
        days: Array.isArray(props.days) ? props.days : []
      },
      filename: 'fix_schedule_detail'
    }
    
    // Get auth token
    const rawAuthToken = localStorage.getItem('auth_token')
    const rawDevToken = localStorage.getItem('dev_auth_token')
    let token = null
    if (rawAuthToken) {
      try {
        const parsed = JSON.parse(rawAuthToken)
        token = parsed?.token || rawAuthToken
      } catch (_) {
        token = rawAuthToken
      }
    } else if (rawDevToken) {
      try {
        const parsed = JSON.parse(rawDevToken)
        token = parsed?.token || rawDevToken
      } catch (_) {
        token = rawDevToken
      }
    }
    
    const response = await fetch(`${API_BASE_URL}/reports/docx`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ...(token ? { 'Authorization': `Bearer ${token}` } : {})
      },
      credentials: 'include',
      body: JSON.stringify(reportData)
    })
    
    if (!response.ok) {
      throw new Error('Failed to generate Word report')
    }
    
    const blob = await response.blob()
    const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
    link.href = url
    link.download = 'fix_schedule_detail.docx'
    document.body.appendChild(link)
  link.click()
    document.body.removeChild(link)
    URL.revokeObjectURL(url)
  } catch (err) {
    console.error('Word export failed:', err)
    const { ElMessage } = await import('element-plus')
    ElMessage.error('Failed to export Word report')
  }
}

async function onExportPDF() {
  // Allow PreviewExport to generate PDF from provided HTML content
}
</script>

<style scoped>
</style>


