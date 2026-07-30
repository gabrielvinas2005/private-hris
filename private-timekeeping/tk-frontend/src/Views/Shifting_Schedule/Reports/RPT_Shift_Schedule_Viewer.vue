<template>
  <PreviewExport 
    :html-content="reportHtmlContent"
    :title="'Shift Schedule Viewer Report'"
    :filename="computedFilename"
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

const props = defineProps({
  data: { type: Array, default: () => [] },
  scheduleName: { type: String, default: '' },
  dateFrom: { type: String, default: '' },
  dateTo: { type: String, default: '' },
  loading: { type: Boolean, default: false },
})

const { generateShiftScheduleViewerReport } = useUnifiedReport()

function sanitizeFilename(name) {
  return (name || '').replace(/[^a-zA-Z0-9]/g, '_') || 'shift_schedule_viewer'
}

const computedFilename = computed(() => 
  `shift_schedule_viewer_${sanitizeFilename(props.scheduleName)}_${new Date().toISOString().slice(0, 10)}`
)

const reportHtmlContent = computed(() => {
  if (!props.data || !props.data.length) return ''
  
  const report = generateShiftScheduleViewerReport(
    props.data,
    props.scheduleName,
    props.dateFrom,
    props.dateTo
  )
  return report.generatePreview()
})

async function onExportExcel() {
  try {
    const { API_BASE_URL } = await import('../../../config/api')
    const reportData = {
      report_type: 'shift_schedule_viewer',
      data: {
        schedule_name: props.scheduleName || '',
        date_from: props.dateFrom || '',
        date_to: props.dateTo || '',
        schedule_data: props.data || []
      },
      filename: computedFilename.value
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
    link.download = `${computedFilename.value}.xlsx`
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

async function onExportPDF() {
  const report = generateShiftScheduleViewerReport(
    props.data,
    props.scheduleName,
    props.dateFrom,
    props.dateTo,
    { filename: computedFilename.value }
  )
  await report.exportToPDF()
}

async function onExportWord() {
  try {
    const { API_BASE_URL } = await import('../../../config/api')
    const reportData = {
      report_type: 'shift_schedule_viewer',
      data: {
        schedule_name: props.scheduleName || '',
        date_from: props.dateFrom || '',
        date_to: props.dateTo || '',
        schedule_data: props.data || []
      },
      filename: computedFilename.value
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
    link.download = `${computedFilename.value}.docx`
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
</script>

<style scoped>
</style>
