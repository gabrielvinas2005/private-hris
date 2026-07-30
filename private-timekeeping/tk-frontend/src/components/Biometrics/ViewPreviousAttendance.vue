<template>
  <el-dialog
    v-model="dialogVisible"
    title="View Previous Attendance"
    width="90%"
    :close-on-click-modal="false"
    destroy-on-close
    class="previous-attendance-dialog"
    @close="handleClose"
  >
    <template #header>
      <div class="dialog-header-content">
        <div class="dialog-title-section">
          <h3>View Previous Attendance</h3>
          <el-form :inline="true" class="date-selector-form">
            <el-form-item label="Summary Date">
              <el-date-picker
                v-model="form.selectedDate"
                type="date"
                value-format="YYYY-MM-DD"
                placeholder="Select date"
                :disabled="loading"
                style="width: 200px;"
                @change="handleDateChange"
              />
            </el-form-item>
            <el-form-item>
              <el-button
                type="primary"
                @click="loadRecords"
                :loading="loading"
              >
                <el-icon><Refresh /></el-icon>
                Load Records
              </el-button>
            </el-form-item>
          </el-form>
        </div>
      </div>
    </template>

    <div class="time-data-container">
      <!-- Loading State -->
      <div v-if="loading" class="loading-state">
        <el-icon class="is-loading"><Loading /></el-icon>
        <span>Loading attendance records...</span>
      </div>

      <!-- Empty State -->
      <div v-else-if="records.length === 0 && hasLoaded" class="empty-state">
        <el-empty description="No attendance records found for the selected date" />
      </div>

      <!-- Time Data Table -->
      <el-table
        v-else-if="records.length > 0"
        :data="records"
        v-loading="loading"
        border
        stripe
        style="width: 100%"
        max-height="600"
      >
        <el-table-column type="index" label="#" width="60" align="center" fixed="left" />
        <el-table-column prop="employee_no" label="Employee No" width="120" fixed="left" />
        <el-table-column prop="employee_name" label="Employee Name" min-width="200" fixed="left" />
        <el-table-column prop="department" label="Department" min-width="150" />
        <el-table-column prop="position" label="Position" min-width="150" />
        <el-table-column prop="am_in" label="AM In" width="120" align="center">
          <template #default="{ row }">
            {{ formatTimeValue(row.am_in) }}
          </template>
        </el-table-column>
        <el-table-column prop="pm_out" label="PM Out" width="120" align="center">
          <template #default="{ row }">
            {{ formatTimeValue(row.pm_out) }}
          </template>
        </el-table-column>
        <el-table-column prop="work_hours" label="Work Hours" width="150" align="center">
          <template #default="{ row }">
            {{ formatDayFractionHuman(row.work_hours || 0) }}
          </template>
        </el-table-column>
        <el-table-column prop="late" label="Late" width="120" align="center">
          <template #default="{ row }">
            {{ formatDayFractionHuman(row.late || 0) }}
          </template>
        </el-table-column>
        <el-table-column prop="undertime" label="Undertime" width="130" align="center">
          <template #default="{ row }">
            {{ formatDayFractionHuman(row.undertime || 0) }}
          </template>
        </el-table-column>
        <el-table-column prop="absent" label="Absent" width="100" align="center">
          <template #default="{ row }">
            <el-tag v-if="parseFloat(row.absent || 0) === 1.00" type="warning" size="small">Yes</el-tag>
            <span v-else>No</span>
          </template>
        </el-table-column>
        <el-table-column prop="remarks" label="Remarks" min-width="200" show-overflow-tooltip />
      </el-table>
    </div>
    
    <template #footer>
      <div class="dialog-footer">
        <div class="footer-left">
          <PreviewExport
            :html-content="reportHtmlContent"
            :title="`Previous Attendance - ${formatDate(form.selectedDate)}`"
            :filename="`previous_attendance_${form.selectedDate || getCurrentDate()}`"
            :orientation="'landscape'"
            :on-excel="handleExcelExport"
            :on-word="handleWordExport"
            :loading="loading"
          />
        </div>
        <div class="footer-right">
          <el-button @click="handleClose">Close</el-button>
        </div>
      </div>
    </template>
  </el-dialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { ElMessage } from 'element-plus'
import { Refresh, Loading } from '@element-plus/icons-vue'
import PreviewExport from '../Reusable_Components/Preview&Export.vue'
import { api } from '../../services/api.js'
import { formatTime } from '../../Composables/useTimeFormatting.js'
import { formatDayFractionHuman } from '../../Composables/useDayFractionConversion.js'

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  },
  initialDate: {
    type: String,
    default: null
  }
})

const emit = defineEmits(['update:modelValue', 'close'])

// Get current date helper
function getCurrentDate() {
  return new Date().toISOString().split('T')[0]
}

// Format date helper
const formatDate = (dateString) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  if (Number.isNaN(date.getTime())) {
    return dateString
  }
  return date.toLocaleDateString('en-US', { 
    year: 'numeric', 
    month: 'long', 
    day: 'numeric' 
  })
}

// Dialog visibility
const dialogVisible = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

// Form state
const form = ref({
  selectedDate: props.initialDate || getCurrentDate()
})

// Data state
const loading = ref(false)
const records = ref([])
const hasLoaded = ref(false)

// Watch for dialog open to load data
watch(dialogVisible, (isOpen) => {
  if (isOpen) {
    form.value.selectedDate = props.initialDate || getCurrentDate()
    // Load time data when dialog opens
    loadRecords()
  } else {
    // Reset when dialog closes
    records.value = []
    hasLoaded.value = false
  }
})

// Watch for initialDate prop changes
watch(() => props.initialDate, (newDate) => {
  if (newDate && dialogVisible.value) {
    form.value.selectedDate = newDate
  }
})

// Handle date change
const handleDateChange = () => {
  // Reset records when date changes
  records.value = []
  hasLoaded.value = false
}

// Load time_data records for selected date
const loadRecords = async () => {
  if (!form.value.selectedDate) {
    ElMessage.warning('Please select a date')
    return
  }

  try {
    loading.value = true
    hasLoaded.value = false
    
    const date = form.value.selectedDate
    
    if (!date) {
      ElMessage.warning('Please select a date')
      loading.value = false
      return
    }
    
    // Call API to get time_data records for the date
    const res = await api.get('/process-attendance/time-data-by-date', { date })
    
    let newRecords = []
    if (res && res.data) {
      newRecords = Array.isArray(res.data) ? res.data : []
      
      // Map time_data records to include employee information
      newRecords = newRecords.map(record => ({
        id: record.id,
        employee_id: record.employee_id,
        employee_no: record.employee_no || record.employee_id?.toString() || '-',
        employee_name: record.employee_name || '-',
        department: record.department || '-',
        position: record.position || '-',
        date: record.date,
        am_in: record.am_in || null,
        am_out: record.am_out || null,
        break_in: record.break_in || null,
        break_out: record.break_out || null,
        pm_in: record.pm_in || null,
        pm_out: record.pm_out || null,
        work_hours: record.work_hours || 0,
        late: record.late || 0,
        undertime: record.undertime || 0,
        absent: record.absent || 0,
        is_wfh: record.is_wfh || false,
        wfh_reason: record.wfh_reason || '',
        wfh_location: record.wfh_location || '',
        remarks: record.remarks || ''
      }))
    }
    
    records.value = newRecords
    hasLoaded.value = true
    
    if (newRecords.length === 0) {
      ElMessage.info(`No attendance records found for ${formatDate(date)}`)
    } else {
      ElMessage.success(`Loaded ${newRecords.length} record(s) for ${formatDate(date)}`)
    }
  } catch (error) {
    console.error('Failed to load time data records:', error)
    
    let errorMessage = ''
    if (error.message) {
      errorMessage = error.message
    } else if (typeof error === 'string') {
      errorMessage = error
    } else if (error.toString) {
      errorMessage = error.toString()
    }
    
    if (error.response && error.response.data && error.response.data.message) {
      errorMessage = error.response.data.message
    }
    
    ElMessage.error('Failed to load time data records: ' + errorMessage)
    records.value = []
    hasLoaded.value = true
  } finally {
    loading.value = false
  }
}

// Format time value for display
const formatTimeValue = (value) => {
  if (!value || value === '-' || value === null || value === '') {
    return '-'
  }
  
  // Use formatTime to get the formatted time with AM/PM
  const formatted = formatTime(value)
  
  if (!formatted || formatted === '') {
    return '-'
  }
  
  // Remove periods from A.M./P.M. to make AM/PM
  let result = formatted.replace(/A\.M\./gi, 'AM').replace(/P\.M\./gi, 'PM')
  
  // Remove leading zero from single-digit hours (04:32 -> 4:32)
  result = result.replace(/^0(\d):(\d{2})\s+(AM|PM)$/i, '$1:$2 $3')
  
  return result
}

// Generate HTML content for Previous Attendance Report
const reportHtmlContent = computed(() => {
  if (!records.value.length) {
    return '<p style="padding: 2px; text-align: center; color: #909399;">No attendance data available.</p>'
  }
  
  let html = '<div style="padding: 2px; font-family: Arial, sans-serif;">'
  
  // Report Title
  html += '<div style="text-align: center; margin-bottom: 2px; page-break-inside: avoid;">'
  html += '<h2 style="margin: 0 0 1px 0; font-size: 20px; font-weight: bold; color: #303133;">'
  html += 'Daily Attendance Summary'
  html += '</h2>'
  html += '<div style="margin: 0 0 2px 0; font-size: 14px; color: #606266;">'
  html += formatDate(form.value.selectedDate)
  html += '</div>'
  html += '</div>'
  
  // Department Name (get from first row since filtered data is sorted by department)
  const departmentName = records.value.length > 0 && records.value[0].department ? records.value[0].department : 'All Departments'
  html += '<div style="margin-bottom: 8px; font-size: 12px; font-weight: 600; color: #303133;">'
  html += `Department Name: ${departmentName}`
  html += '</div>'
  
  // Table
  html += '<table style="width: 100%; border-collapse: collapse; margin-top: 5px; font-size: 11px;">'
  
  // Header row
  html += '<thead><tr style="background-color: #f5f5f5;">'
  const headers = ['#', 'Employee No', 'Employee Name', 'Position', 'AM In', 'PM Out', 'Work Hours', 'Late', 'Undertime', 'Absent', 'Remarks']
  headers.forEach(header => {
    html += `<th style="border: 1px solid #ddd; padding: 4px; text-align: ${header.includes('#') || header.includes('No') || header.includes('In') || header.includes('Out') || header.includes('Hours') || header.includes('Late') || header.includes('Undertime') || header.includes('Absent') ? 'center' : 'left'}; font-weight: bold; font-size: 10px;">${header}</th>`
  })
  html += '</tr></thead>'
  
  // Data rows
  html += '<tbody>'
  records.value.forEach((row, index) => {
    const bgColor = index % 2 === 0 ? '#ffffff' : '#f9f9f9'
    html += `<tr style="background-color: ${bgColor};">`
    
    html += `<td style="border: 1px solid #ddd; padding: 4px; text-align: center;">${index + 1}</td>`
    html += `<td style="border: 1px solid #ddd; padding: 4px; text-align: center;">${row.employee_no || '-'}</td>`
    html += `<td style="border: 1px solid #ddd; padding: 4px; text-align: left;">${row.employee_name || '-'}</td>`
    html += `<td style="border: 1px solid #ddd; padding: 4px; text-align: left;">${row.position || '-'}</td>`
    html += `<td style="border: 1px solid #ddd; padding: 4px; text-align: center;">${formatTimeValue(row.am_in)}</td>`
    html += `<td style="border: 1px solid #ddd; padding: 4px; text-align: center;">${formatTimeValue(row.pm_out)}</td>`
    html += `<td style="border: 1px solid #ddd; padding: 4px; text-align: center;">${formatDayFractionHuman(row.work_hours || 0)}</td>`
    html += `<td style="border: 1px solid #ddd; padding: 4px; text-align: center;">${formatDayFractionHuman(row.late || 0)}</td>`
    html += `<td style="border: 1px solid #ddd; padding: 4px; text-align: center;">${formatDayFractionHuman(row.undertime || 0)}</td>`
    html += `<td style="border: 1px solid #ddd; padding: 4px; text-align: center;">${parseFloat(row.absent || 0) === 1.00 ? 'Yes' : 'No'}</td>`
    html += `<td style="border: 1px solid #ddd; padding: 4px; text-align: left;">${row.remarks || '-'}</td>`
    
    html += '</tr>'
  })
  html += '</tbody>'
  
  html += '</table>'
  html += '</div>'
  
  return html
})

// Export handlers
async function handleExcelExport() {
  try {
    if (records.value.length === 0) {
      ElMessage.warning('No data to export')
      return
    }
    
    const { API_BASE_URL } = await import('../../config/api')
    const reportDate = form.value.selectedDate || getCurrentDate()
    const displayDate = formatDate(reportDate)
    
    const reportData = {
      report_type: 'previous_attendance_summary',
      data: {
        date: reportDate,
        date_display: displayDate,
        attendance_records: records.value || []
      },
      filename: `previous_attendance_${reportDate}`
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
    link.download = `previous_attendance_${reportDate}.xlsx`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    URL.revokeObjectURL(url)
    
    ElMessage.success('Excel file exported successfully')
  } catch (err) {
    console.error('Excel export failed:', err)
    ElMessage.error('Failed to export Excel file')
  }
}

async function handleWordExport() {
  try {
    if (records.value.length === 0) {
      ElMessage.warning('No data to export')
      return
    }
    
    const { API_BASE_URL } = await import('../../config/api')
    const reportDate = form.value.selectedDate || getCurrentDate()
    const displayDate = formatDate(reportDate)
    
    const reportData = {
      report_type: 'previous_attendance_summary',
      data: {
        date: reportDate,
        date_display: displayDate,
        attendance_records: records.value || []
      },
      filename: `previous_attendance_${reportDate}`
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
    link.download = `previous_attendance_${reportDate}.docx`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    URL.revokeObjectURL(url)
    
    ElMessage.success('Word document exported successfully')
  } catch (err) {
    console.error('Word export failed:', err)
    ElMessage.error('Failed to export Word document')
  }
}

// Handle dialog close
const handleClose = () => {
  dialogVisible.value = false
  emit('close')
}
</script>

<style scoped>
.previous-attendance-dialog {
  max-height: 95vh;
}

.dialog-header-content {
  width: 100%;
}

.dialog-title-section h3 {
  margin: 0 0 16px 0;
  font-size: 18px;
  font-weight: 600;
  color: #303133;
}

.date-selector-form {
  margin: 0;
}

.date-selector-form .el-form-item {
  margin-bottom: 0;
}

.time-data-container {
  min-height: 400px;
}

.loading-state {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  padding: 60px 20px;
  color: #606266;
  font-size: 14px;
}

.empty-state {
  padding: 60px 20px;
}

.dialog-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 8px;
}

.footer-left {
  display: flex;
  align-items: center;
}

.footer-right {
  display: flex;
  align-items: center;
}
</style>

