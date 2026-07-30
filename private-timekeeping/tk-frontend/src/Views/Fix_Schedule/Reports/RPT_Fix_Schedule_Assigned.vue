<template>
  <PreviewExport 
    :html-content="reportHtmlContent"
    :title="'Assigned Employees Report'"
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
import { useReportGenerator } from '../../../Composables/useReportGenerator'
import { useExport } from '../../../Composables/useExport.js'

const props = defineProps({
  items: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  scheduleName: { type: String, default: '' },
})

const { generateTableHTML } = useReportGenerator()
const { exportToCSV } = useExport()
const { generateGenericTableReport } = useUnifiedReport()

function sanitizeFilename(name) {
  return (name || '').replace(/[^a-zA-Z0-9]/g, '_') || 'schedule'
}

const computedFilename = computed(() => `assigned_employees_${sanitizeFilename(props.scheduleName)}`)

function formatEmployeeForReport(employee) {
  return {
    'Employee No': employee.employee_no || '',
    'Name': employee.name || '',
    'Position': employee.position || '',
    'Department': employee.department || '',
    'Employment Type': employee.employment_type || ''
  }
}

const columns = [
  { key: 'Employee No', label: 'Employee No', align: 'left' },
  { key: 'Name', label: 'Employee Name', align: 'left' },
  { key: 'Position', label: 'Position', align: 'left' },
  { key: 'Department', label: 'Department', align: 'left' },
  { key: 'Employment Type', label: 'Employment Type', align: 'left' }
]

const reportTitle = computed(() => `Assigned Employees for ${props.scheduleName || ''}`)

const reportHtmlContent = computed(() => {
  if (!props.items || !props.items.length) return ''
  const data = props.items.map(formatEmployeeForReport)
  // Use simple table HTML (headers/footers handled by container components where applicable)
  const table = generateTableHTML(data, columns, { title: '', subtitle: '', showHeader: false, showFooter: false })
  const safeTitle = reportTitle.value.replace(/</g, '&lt;').replace(/>/g, '&gt;')
  return `<h2 style="font-family:Arial;margin:0 0 8px; text-align: center; font-weight: bold;">${safeTitle}</h2>` + table
})

async function onExportExcel() {
  try {
    const { API_BASE_URL } = await import('../../../config/api')
    const reportData = {
      report_type: 'fix_schedule_assigned',
      data: {
        schedule_name: props.scheduleName || '',
        employees: props.items || []
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

async function onExportWord() {
  try {
    const { API_BASE_URL } = await import('../../../config/api')
    const reportData = {
      report_type: 'fix_schedule_assigned',
      data: {
        schedule_name: props.scheduleName || '',
        employees: props.items || []
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

async function onExportPDF() {
  const data = (props.items || []).map(formatEmployeeForReport)
  const { exportToPDF } = generateGenericTableReport(data, columns, 'Assigned Employees Report')
  await exportToPDF(computedFilename.value)
}
</script>

<style scoped>
</style>


