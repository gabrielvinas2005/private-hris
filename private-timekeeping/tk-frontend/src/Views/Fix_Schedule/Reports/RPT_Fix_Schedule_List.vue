<template>
  <PreviewExport
    :html-content="reportHtmlContent"
    :title="'Fix Schedule Report'"
    :filename="'fix_schedule_report'"
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
  items: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
})

const { generateGenericTableReport, getPreviewContent } = useUnifiedReport()

function toReportRows(items) {
  return (items || []).map(item => ({
    'Schedule Name': item.name || '',
    'Description': item.description || '',
    'Created At': item.created_at || '',
    'Updated At': item.updated_at || ''
  }))
}

const columns = [
  { key: 'Schedule Name', label: 'Schedule Name', align: 'left' },
  { key: 'Description', label: 'Description', align: 'left' },
  { key: 'Created At', label: 'Created At', align: 'left' },
  { key: 'Updated At', label: 'Updated At', align: 'left' }
]

const reportHtmlContent = computed(() => {
  if (!props.items || !Array.isArray(props.items) || !props.items.length) return ''
  const data = toReportRows(props.items)
  const report = generateGenericTableReport(data, columns, '')
  const body = getPreviewContent(report.template, report.config)
  const safeTitle = 'Fix Schedule Report'
  const titleHtml = `<h2 style="font-family:Arial;margin:0 0 8px; text-align: center; font-weight: bold;">${safeTitle}</h2>`
  return titleHtml + body
})

async function onExportExcel() {
  try {
    const { API_BASE_URL } = await import('../../../config/api')
    const reportData = {
      report_type: 'fix_schedule_list',
      data: {
        schedules: props.items || []
      },
      filename: 'fix_schedule_report'
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
    link.download = 'fix_schedule_report.xlsx'
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
      report_type: 'fix_schedule_list',
      data: {
        schedules: props.items || []
      },
      filename: 'fix_schedule_report'
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
    link.download = 'fix_schedule_report.docx'
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
  const report = generateGenericTableReport(toReportRows(props.items), columns, 'Fix Schedule Report')
  await report.exportToPDF('fix-schedules')
}
</script>

<style scoped>
</style>


