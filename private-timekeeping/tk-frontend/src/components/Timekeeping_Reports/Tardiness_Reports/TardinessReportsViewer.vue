<template>
  <el-dialog
    v-model="visible"
    :title="headerTitle"
    class="tardiness-viewer-dialog"
    width="725px"
    destroy-on-close
    align-center
    append-to-body
    :show-close="true"
    :close-on-click-modal="true"
    :close-on-press-escape="true"
  >
    <div v-if="header" class="mb-3 employee-header">
      <EmployeeDataPopulate :employee="header" field="photo" />
      <EmployeeDataPopulate :employee="header" />
    </div>
    <div class="controls">
      <div class="date-filter">
        <span class="muted">Date From:</span>
        <el-date-picker
          v-model="dateFromModel"
          type="date"
          size="small"
          class="date-picker"
          placeholder="Select start date"
          format="YYYY-MM-DD"
          value-format="YYYY-MM-DD"
          @change="onDateRangeChange"
        />
      </div>
      <div class="date-filter">
        <span class="muted">Date To:</span>
        <el-date-picker
          v-model="dateToModel"
          type="date"
          size="small"
          class="date-picker"
          placeholder="Select end date"
          format="YYYY-MM-DD"
          value-format="YYYY-MM-DD"
          @change="onDateRangeChange"
        />
      </div>
    </div>
    <!-- Show message when no date filters are set -->
    <div v-if="!dateFromModel && !dateToModel" class="select-date-message">
      <div class="message-content">
        <el-icon class="message-icon"><Calendar /></el-icon>
        <h3>Select Date First</h3>
        <p>Please select a date range using the "Date From" and "Date To" filters above to view the tardiness data.</p>
      </div>
    </div>
    
    <!-- Show table when date filters are set -->
    <el-table v-else :data="filteredRows" height="520" stripe class="tardiness-table" v-loading="loading">
      <!-- Date Column -->
      <el-table-column prop="date" label="Date" width="200" align="center">
        <template #default="{ row }">
          <span class="date-cell">{{ formatDate(row.date) }}</span>
        </template>
      </el-table-column>

      <!-- Am In - hide for Absences Report (only Date and Absent) -->
      <el-table-column
        v-if="reportType !== 'Absences'"
        prop="am_in"
        label="Am In"
        width="100"
        align="center"
      >
        <template #default="{ row }">
          <span v-if="!row.isPlaceholder" class="time-cell">{{ formatTimeValue(row.am_in, row) }}</span>
          <span v-else class="no-data-cell">-</span>
        </template>
      </el-table-column>

      <!-- Pm Out - hide for Absences Report -->
      <el-table-column
        v-if="reportType !== 'Absences'"
        prop="pm_out"
        label="Pm Out"
        width="100"
        align="center"
      >
        <template #default="{ row }">
          <span v-if="!row.isPlaceholder" class="time-cell">{{ formatTimeValue(row.pm_out, row) }}</span>
          <span v-else class="no-data-cell">-</span>
        </template>
      </el-table-column>

      <!-- Late - Only show for Late Report (Tardiness) or Combined Report: hrs/mins in red -->
      <el-table-column 
        v-if="reportType === 'Tardiness' || reportType === 'Combined'"
        prop="late" 
        label="Late" 
        width="100" 
        align="center"
      >
        <template #default="{ row }">
          <div v-if="!row.isPlaceholder">
            <span v-if="getAttendanceStatus(row) === 'absent'" class="no-data-cell">-</span>
            <span v-else-if="row.remarks && row.remarks.toLowerCase().includes('rest day')" class="no-data-cell">-</span>
            <template v-else>
              <span v-if="formatLateUndertimeHrsMins(row.late || 0) === '-'" class="no-data-cell">-</span>
              <span v-else class="duration-red">{{ formatLateUndertimeHrsMins(row.late || 0) }}</span>
            </template>
          </div>
          <span v-else class="no-data-cell">-</span>
        </template>
      </el-table-column>

      <!-- Undertime - Only show for Undertime Report or Combined Report -->
      <el-table-column 
        v-if="reportType === 'Undertime' || reportType === 'Combined'"
        prop="undertime" 
        label="Undertime" 
        width="100" 
        align="center"
      >
        <template #default="{ row }">
          <div v-if="!row.isPlaceholder">
            <span v-if="getAttendanceStatus(row) === 'absent'" class="no-data-cell">-</span>
            <span v-else-if="row.remarks && row.remarks.toLowerCase().includes('rest day')" class="no-data-cell">-</span>
            <template v-else>
              <span v-if="formatLateUndertimeHrsMins(row.undertime || 0) === '-'" class="no-data-cell">-</span>
              <span v-else class="duration-red">{{ formatLateUndertimeHrsMins(row.undertime || 0) }}</span>
            </template>
          </div>
          <span v-else class="no-data-cell">-</span>
        </template>
      </el-table-column>

      <!-- Absent - Only show for Absences Report or Combined Report -->
      <el-table-column 
        v-if="reportType === 'Absences' || reportType === 'Combined'"
        prop="absent" 
        label="Absent" 
        width="100" 
        align="center"
      >
        <template #default="{ row }">
          <div v-if="getAbsenceValues(row).absent > 0" class="absent-check-cell">
            <span v-if="reportType === 'Absences'" class="absent-yes-text">Yes</span>
            <el-icon v-else class="absent-check-icon"><Check /></el-icon>
          </div>
          <div v-else-if="getAbsenceValues(row).absentOffset > 0" class="absent-offset-cell">
            <el-icon class="absent-icon"><Warning /></el-icon>
            <span class="absent-text">
              Offset Applied ({{ formatLateTime(getAbsenceValues(row).absentOffset) }})
            </span>
          </div>
          <span v-else class="no-data-cell">-</span>
        </template>
      </el-table-column>
    </el-table>

    <template #footer>
      <div class="footer-actions">
        <div class="footer-left">
          <el-button @click="visible = false">Close</el-button>
        </div>
        <div class="footer-right">
          <!-- Late Report Preview & Export -->
          <PreviewExport
            v-if="reportType === 'Tardiness'"
            :html-content="reportHtmlContent"
            :title="previewTitle"
            :filename="`late_report_${employeeFileName}`"
            :loading="loading"
            :on-excel="handleExportExcel"
            :on-pdf="handleExportPdf"
            :on-word="handleExportWord"
          />
          
          <!-- Undertime Report Preview & Export -->
          <PreviewExport
            v-if="reportType === 'Undertime'"
            :html-content="reportHtmlContent"
            :title="previewTitle"
            :filename="`undertime_report_${employeeFileName}`"
            :loading="loading"
            :on-excel="handleExportExcel"
            :on-pdf="handleExportPdf"
            :on-word="handleExportWord"
          />
          
          <!-- Absences Report Preview & Export -->
          <PreviewExport
            v-if="reportType === 'Absences'"
            :html-content="reportHtmlContent"
            :title="previewTitle"
            :filename="`absences_report_${employeeFileName}`"
            :loading="loading"
            :on-excel="handleExportExcel"
            :on-pdf="handleExportPdf"
            :on-word="handleExportWord"
          />
          
          <!-- Combined Report Preview & Export -->
          <PreviewExport
            v-if="reportType === 'Combined'"
            :html-content="reportHtmlContent"
            :title="previewTitle"
            :filename="`combined_tardiness_report_${employeeFileName}`"
            :loading="loading"
            :on-excel="handleExportExcel"
            :on-pdf="handleExportPdf"
            :on-word="handleExportWord"
          />
        </div>
      </div>
    </template>
  </el-dialog>

</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { Warning, CircleCheck, Calendar, Check, Close } from '@element-plus/icons-vue'
import EmployeeDataPopulate from '../../Reusable_Components/Employee_Data_Populate.vue'
import PreviewExport from '../../Reusable_Components/Preview&Export.vue'
import { ElMessage } from 'element-plus'
import { useBackendReportExport } from '../../../Composables/useBackendReportExport'
import { dayFractionToMinutes } from '@/Composables/useDayFractionConversion'
import { formatEmployeeName } from '../../../Composables/useNameFormatter'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  header: { type: Object, default: null },
  rows: { type: Array, default: () => [] },
  dateFrom: { type: String, default: '' },
  dateTo: { type: String, default: '' },
  reportType: { type: String, default: 'Tardiness' }, // 'Tardiness', 'Undertime', or 'Absences'
  loading: { type: Boolean, default: false }
})
const emit = defineEmits(['update:modelValue','change-date-range'])

const model = computed({
  get: () => props.modelValue,
  set: v => emit('update:modelValue', v)
})

const visible = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

const headerTitle = computed(() => {
  let reportTypeName = 'Late Report' // Changed from 'Tardiness Report' to match dropdown label
  if (props.reportType === 'Undertime') reportTypeName = 'Undertime Report'
  else if (props.reportType === 'Absences') reportTypeName = 'Absences Report'
  else if (props.reportType === 'Combined') reportTypeName = 'Late, Undertime and Absences Report'
  
  return props.header ? `${reportTypeName} - ${employeeName.value}` : reportTypeName
})

const columnLabel = computed(() => {
  const label = props.reportType === 'Undertime' ? 'Undertime' : 'Late'
  return label
})


const isRestDayValue = (value) => {
  if (value === true || value === false) return value === true
  if (typeof value === 'number') return value === 1
  if (value === null || value === undefined) return false
  const normalized = String(value).trim().toLowerCase()
  return normalized === '1' || normalized === 'true' || normalized === 'yes'
}

const isRestDayRow = (row) => {
  if (!row) return false
  if (isRestDayValue(row.is_restday)) return true
  const remarks = String(row.remarks || '').toLowerCase()
  return remarks.includes('rest day')
}

const convertDayFractionToDuration = (value) => {
  const fraction = Number(value) || 0
  const totalMinutes = dayFractionToMinutes(fraction)
  const hours = Math.floor(totalMinutes / 60)
  const minutes = totalMinutes % 60
  return { hours, minutes, totalMinutes }
}

const getAbsenceValues = (row) => {
  return {
    absent: Number(row.absent || 0),
    absentOffset: Number(row.absent_offset || 0)
  }
}

// Generate rows for all dates in selected range, with placeholders for missing data
// Filter rows based on reportType: Late, Undertime, or Absences
const sortRowsByDate = (rows = []) => {
  return [...rows].sort((a, b) => {
    const aDate = a?.date ? new Date(a.date) : null
    const bDate = b?.date ? new Date(b.date) : null
    if (aDate && bDate) return aDate - bDate
    if (aDate) return -1
    if (bDate) return 1
    return 0
  })
}

const filteredRows = computed(() => {
  const currentRows = Array.isArray(props.rows) ? props.rows : []
  const filteredByRestday = currentRows.filter(row => !isRestDayRow(row))
  let filteredByType = filteredByRestday
  
  if (props.reportType === 'Tardiness') {
    // Late Report: only show rows where late > 0
    filteredByType = filteredByType.filter(row => {
      if (row.isPlaceholder) return false
      const late = parseFloat(row.late || 0)
      return late > 0
    })
  } else if (props.reportType === 'Undertime') {
    // Undertime Report: only show rows where undertime > 0
    filteredByType = filteredByType.filter(row => {
      if (row.isPlaceholder) return false
      const undertime = parseFloat(row.undertime || 0)
      return undertime > 0
    })
  } else if (props.reportType === 'Absences') {
    // Absences Report: show rows with absent or absent_offset values
    filteredByType = filteredByType.filter(row => {
      if (row.isPlaceholder) return false
      const absent = parseFloat(row.absent || 0)
      const absentOffset = parseFloat(row.absent_offset || 0)
      return absent > 0 || absent === 1 || absent === 1.0 || absentOffset > 0
    })
  } else if (props.reportType === 'Combined') {
    // Combined Report: show all rows (no filtering)
    filteredByType = filteredByType
  }
  
  if (!dateFromModel.value && !dateToModel.value) {
    return sortRowsByDate(filteredByType)
  }
  
  // Get the date range
  const fromDate = dateFromModel.value ? new Date(dateFromModel.value) : null
  const toDate = dateToModel.value ? new Date(dateToModel.value) : null
  
  if (!fromDate && !toDate) {
    return sortRowsByDate(filteredByType)
  }
  
  // For specific report types (Late, Undertime, Absences), only return actual data rows within date range
  // Don't generate placeholder rows for dates without records
  if (props.reportType !== 'Combined') {
    // Filter by date range for specific report types
    const withinRange = filteredByType.filter(row => {
      if (!row.date) return false
      const rowDate = new Date(row.date)
      rowDate.setHours(0, 0, 0, 0)
      
      if (fromDate && toDate) {
        const start = new Date(fromDate)
        start.setHours(0, 0, 0, 0)
        const end = new Date(toDate)
        end.setHours(23, 59, 59, 999)
        return rowDate >= start && rowDate <= end
      } else if (fromDate) {
        const start = new Date(fromDate)
        start.setHours(0, 0, 0, 0)
        return rowDate >= start
      } else if (toDate) {
        const end = new Date(toDate)
        end.setHours(23, 59, 59, 999)
        return rowDate <= end
      }
      
      return true
    })
    return sortRowsByDate(withinRange)
  }
  
  // For Combined report, generate all dates in range with placeholders for missing dates
  // Combined report: only show records within range, skip placeholders
  const combinedInRange = filteredByType.filter(row => {
    if (!row.date || row.isPlaceholder) return false
    const rowDate = new Date(row.date)
    rowDate.setHours(0, 0, 0, 0)
    
    if (fromDate && toDate) {
      const start = new Date(fromDate)
      start.setHours(0, 0, 0, 0)
      const end = new Date(toDate)
      end.setHours(23, 59, 59, 999)
      return rowDate >= start && rowDate <= end
    } else if (fromDate) {
      const start = new Date(fromDate)
      start.setHours(0, 0, 0, 0)
      return rowDate >= start
    } else if (toDate) {
      const end = new Date(toDate)
      end.setHours(23, 59, 59, 999)
      return rowDate <= end
    }
    
    return true
  })
  
  return sortRowsByDate(combinedInRange)
})

const formatDate = (dateString) => {
  if (!dateString) return '-'
  const date = new Date(dateString)
  // Ensure single line display with proper formatting
  return date.toLocaleDateString('en-US', {
    month: 'short',
    day: '2-digit',
    year: 'numeric'
  }).replace(/\s+/g, ' ')
}

// Format a single time field (am_in or pm_out) for display
const formatTimeValue = (timeString, row) => {
  if (!row || row.isPlaceholder) return '-'
  if (row.remarks && row.remarks.toLowerCase().includes('rest day')) return '-'
  if (!timeString) return '-'
  const str = String(timeString)
  const parts = str.split(':')
  const hour = parseInt(parts[0], 10)
  const minutes = parts[1] != null ? parts[1].padStart(2, '0') : '00'
  const ampm = hour >= 12 ? 'PM' : 'AM'
  const displayHour = hour === 0 ? 12 : hour > 12 ? hour - 12 : hour
  return `${displayHour}:${minutes} ${ampm}`
}

const getTimeValue = (row) => {
  return props.reportType === 'Undertime' ? row.undertime : row.late
}

const getAttendanceStatus = (row) => {
  if (row.isPlaceholder) return 'no-record'
  
  // Check for rest days first - show dash for rest days
  if (row.remarks && row.remarks.toLowerCase().includes('rest day')) {
    return 'rest-day'
  }
  
  // Check if there's any record in time fields or work_hours
  const hasTimeRecords = row.am_in || row.am_out || row.break_in || row.break_out || row.pm_in || row.pm_out
  const hasWorkHours = row.work_hours && row.work_hours > 0 && row.work_hours !== '0.00'
  
  // If there's any time record or work hours, it's PRESENT
  if (hasTimeRecords || hasWorkHours) {
    return 'present'
  }
  
  // If no time records and no work hours, check absent fields
  const absentValue = row.absent === 1 || row.absent === '1' || row.absent === 1.00 || row.absent === '1.00'
  const hasAbsentOffset = Number(row.absent_offset || 0) > 0
  
  if (absentValue || hasAbsentOffset) {
    return 'absent'
  } else {
    return 'no-record'
  }
}

const formatLateTime = (lateValue) => {
  const { hours, minutes, totalMinutes } = convertDayFractionToDuration(lateValue)
  if (totalMinutes <= 0) return props.reportType === 'Undertime' ? 'Complete Hours' : 'On Time'
  
  if (hours > 0 && minutes > 0) {
    return `${hours} hrs ${minutes} mins`
  } else if (hours > 0) {
    return `${hours} hrs`
  } else {
    return `${minutes} mins`
  }
}

// Return "X hrs Y mins" for Late/Undertime (red), or "-" when zero
const formatLateUndertimeHrsMins = (value) => {
  const { hours, minutes } = convertDayFractionToDuration(value)
  if (hours === 0 && minutes === 0) {
    return '-'
  }
  if (hours > 0 && minutes > 0) {
    return `${hours} hrs ${minutes} mins`
  }
  if (hours > 0) {
    return `${hours} hrs`
  }
  return `${minutes} mins`
}

const getStatusType = (row) => {
  if (row.is_holiday) return 'info'
  if (row.is_ob) return 'warning'
  if (row.is_ot) return 'primary'
  if (getTimeValue(row) > 0) return 'danger'
  if (row.work_hours > 0) return 'success'
  return 'info'
}

const getStatusText = (row) => {
  if (row.is_holiday) return 'Holiday'
  if (row.is_ob) return 'Official Business'
  if (row.is_ot) return 'Overtime'
  if (getTimeValue(row) > 0) return props.reportType === 'Undertime' ? 'Undertime' : 'Late'
  if (row.work_hours > 0) return 'Present'
  return 'Absent'
}

const dateFromModel = ref(props.dateFrom)
const dateToModel = ref(props.dateTo)

const onDateRangeChange = () => {
  emit('change-date-range', {
    dateFrom: dateFromModel.value,
    dateTo: dateToModel.value
  })
}

// Get report type name for title
const reportTypeName = computed(() => {
  switch (props.reportType) {
    case 'Tardiness': return 'Late Report'
    case 'Undertime': return 'Undertime Report'
    case 'Absences': return 'Absences Report'
    case 'Combined': return 'Late, Undertime and Absences Report'
    default: return 'Tardiness Report'
  }
})

// Get employee name for title and filename
const employeeName = computed(() => {
  if (!props.header) return 'Employee'
  return formatEmployeeName(props.header) || 'Employee'
})

const employeeFileName = computed(() => {
  if (!props.header) return 'employee'
  const name = formatEmployeeName(props.header) || 'employee'
  return name.toLowerCase().replace(/\s+/g, '_').replace(/[^a-z0-9_]/g, '')
})

// Preview title with employee name and date range
const previewTitle = computed(() => {
  let title = `${reportTypeName.value} - ${employeeName.value}`
  if (dateFromModel.value && dateToModel.value) {
    title += ` (${formatDateForTitle(dateFromModel.value)} - ${formatDateForTitle(dateToModel.value)})`
  } else if (dateFromModel.value) {
    title += ` (From ${formatDateForTitle(dateFromModel.value)})`
  } else if (dateToModel.value) {
    title += ` (To ${formatDateForTitle(dateToModel.value)})`
  }
  return title
})

// Format date for title
const formatDateForTitle = (dateString) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric'
  })
}

// Generate HTML content for preview based on filtered rows
const reportHtmlContent = computed(() => {
  if (!filteredRows.value.length || !props.header) {
    return '<p style="padding: 20px; text-align: center; color: #909399;">No data available for report. Please select a date range.</p>'
  }
  
  let html = '<div style="padding: 20px; font-family: Arial, sans-serif;">'
  
  // Report Title
  html += '<div style="text-align: center; margin-bottom: 30px; page-break-inside: avoid;">'
  html += '<h2 style="margin: 0 0 10px 0; font-size: 20px; font-weight: bold; color: #303133;">'
  html += `${reportTypeName.value} - ${employeeName.value}`
  if (dateFromModel.value && dateToModel.value) {
    html += `</h2><p style="margin: 5px 0 0 0; font-size: 14px; color: #606266;">`
    html += `${formatDateForTitle(dateFromModel.value)} - ${formatDateForTitle(dateToModel.value)}`
    html += `</p>`
  } else if (dateFromModel.value) {
    html += `</h2><p style="margin: 5px 0 0 0; font-size: 14px; color: #606266;">From ${formatDateForTitle(dateFromModel.value)}</p>`
  } else if (dateToModel.value) {
    html += `</h2><p style="margin: 5px 0 0 0; font-size: 14px; color: #606266;">To ${formatDateForTitle(dateToModel.value)}</p>`
  } else {
    html += '</h2>'
  }
  html += '</div>'
  
  // Employee details
  html += '<div style="margin-bottom: 20px; padding: 15px; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0;">'
  html += `<div style="font-size: 14px; color: #303133; margin-bottom: 5px;"><strong>Employee No:</strong> ${props.header.employee_no || 'N/A'}</div>`
  if (props.header.position) {
    html += `<div style="font-size: 14px; color: #303133; margin-bottom: 5px;"><strong>Position:</strong> ${props.header.position}</div>`
  }
  if (props.header.department) {
    html += `<div style="font-size: 14px; color: #303133;"><strong>Department:</strong> ${props.header.department}</div>`
  }
  html += '</div>'
  
  // Table header
  html += '<table style="width: 100%; border-collapse: collapse; margin: 20px 0;">'
  html += '<thead><tr style="background-color: #f5f5f5;">'
  
  // Build headers: Absences Report = Date, Absent only (do not push Absent again); Late/Undertime = Date, Am In, Pm Out + one column; Combined = all six
  const isAbsencesOnly = props.reportType === 'Absences'
  const headers = isAbsencesOnly ? ['Date', 'Absent'] : ['Date', 'Am In', 'Pm Out']
  if (props.reportType === 'Tardiness' || props.reportType === 'Combined') {
    headers.push('Late')
  }
  if (props.reportType === 'Undertime' || props.reportType === 'Combined') {
    headers.push('Undertime')
  }
  if (props.reportType === 'Combined') {
    headers.push('Absent')
  }
  // Absences-only already has 'Absent' in headers; do not push again
  
  headers.forEach(header => {
    html += `<th style="border: 1px solid #ddd; padding: 10px; text-align: left; font-weight: 600;">${header}</th>`
  })
  html += '</tr></thead>'
  
  // Table body
  html += '<tbody>'
  filteredRows.value.forEach((row, index) => {
    if (row.isPlaceholder && props.reportType !== 'Combined') return
    
    html += '<tr>'
    
    // Date
    html += `<td style="border: 1px solid #ddd; padding: 8px;">${formatDateForReport(row.date)}</td>`
    
    // Am In, Pm Out (skip for Absences Report - only Date and Absent)
    if (!isAbsencesOnly) {
      html += `<td style="border: 1px solid #ddd; padding: 8px;">${formatTimeValueForReport(row.am_in, row)}</td>`
      html += `<td style="border: 1px solid #ddd; padding: 8px;">${formatTimeValueForReport(row.pm_out, row)}</td>`
    }
    
    // Late (if applicable): hrs/mins in red, "-" when zero
    if (props.reportType === 'Tardiness' || props.reportType === 'Combined') {
      if (getAttendanceStatus(row) === 'absent' || (row.remarks && row.remarks.toLowerCase().includes('rest day'))) {
        html += '<td style="border: 1px solid #ddd; padding: 8px;">-</td>'
      } else {
        const lateText = formatLateUndertimeHrsMins(Number(row.late || 0))
        html += lateText === '-'
          ? '<td style="border: 1px solid #ddd; padding: 8px;">-</td>'
          : `<td style="border: 1px solid #ddd; padding: 8px; color: #dc2626; font-weight: 700;">${lateText}</td>`
      }
    }
    
    // Undertime (if applicable): hrs/mins in red, "-" when zero
    if (props.reportType === 'Undertime' || props.reportType === 'Combined') {
      if (getAttendanceStatus(row) === 'absent' || (row.remarks && row.remarks.toLowerCase().includes('rest day'))) {
        html += '<td style="border: 1px solid #ddd; padding: 8px;">-</td>'
      } else {
        const undertimeText = formatLateUndertimeHrsMins(Number(row.undertime || 0))
        html += undertimeText === '-'
          ? '<td style="border: 1px solid #ddd; padding: 8px;">-</td>'
          : `<td style="border: 1px solid #ddd; padding: 8px; color: #dc2626; font-weight: 700;">${undertimeText}</td>`
      }
    }
    
    // Absent (if applicable): "Yes" in report (not ✓ so PDF doesn't show "?")
    if (props.reportType === 'Absences' || props.reportType === 'Combined') {
      const absenceVals = getAbsenceValues(row)
      if (absenceVals.absent > 0) {
        html += '<td style="border: 1px solid #ddd; padding: 8px; color: #dc2626; font-weight: 700;">Yes</td>'
      } else if (absenceVals.absentOffset > 0) {
        html += `<td style="border: 1px solid #ddd; padding: 8px; color: #b45309; font-weight: 600;">Offset Applied (${formatLateTimeForReport(absenceVals.absentOffset)})</td>`
      } else {
        html += '<td style="border: 1px solid #ddd; padding: 8px;">-</td>'
      }
    }
    
    html += '</tr>'
  })
  html += '</tbody>'
  html += '</table>'
  
  html += '</div>'
  
  return html
})

// Helper functions for report formatting
const formatDateForReport = (dateString) => {
  if (!dateString) return '-'
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric'
  })
}

const formatTimeValueForReport = (timeString, row) => {
  if (!row || row.isPlaceholder) return '-'
  if (row.remarks && row.remarks.toLowerCase().includes('rest day')) return '-'
  if (!timeString) return '-'
  const str = String(timeString)
  const parts = str.split(':')
  const hour = parseInt(parts[0], 10)
  const minutes = parts[1] != null ? parts[1].padStart(2, '0') : '00'
  const ampm = hour >= 12 ? 'PM' : 'AM'
  const displayHour = hour === 0 ? 12 : hour > 12 ? hour - 12 : hour
  return `${displayHour}:${minutes} ${ampm}`
}

const formatLateTimeForReport = (fraction) => {
  const { hours, minutes, totalMinutes } = convertDayFractionToDuration(fraction)
  if (!totalMinutes || totalMinutes <= 0) return '0'
  if (hours > 0 && minutes > 0) {
    return `${hours} hrs ${minutes} mins`
  }
  if (hours > 0) {
    return `${hours} hrs`
  }
  return `${minutes} mins`
}

// Backend report export composable
const { exportToExcel, exportToWord } = useBackendReportExport()

// Get report type name for export
const getReportTypeForExport = () => {
  if (props.reportType === 'Tardiness') return 'late_report'
  if (props.reportType === 'Undertime') return 'undertime_report'
  if (props.reportType === 'Absences') return 'absences_report'
  if (props.reportType === 'Combined') return 'combined_tardiness_report'
  return 'late_report'
}

// Export handlers
const handleExportExcel = async () => {
  if (!filteredRows.value.length || !props.header) {
    ElMessage.warning('No data available for export')
    return
  }
  
  try {
    const exportData = {
      header: props.header,
      rows: filteredRows.value,
      date_from: dateFromModel.value || null,
      date_to: dateToModel.value || null
    }
    
    await exportToExcel(getReportTypeForExport(), exportData, previewTitle.value.replace(/\s+/g, '_').toLowerCase())
  } catch (err) {
    ElMessage.error('Failed to export Excel report')
  }
}

const handleExportPdf = () => {
  // PDF is handled by PreviewExport component via HTML content
  // This function is just a placeholder for the component
}

const handleExportWord = async () => {
  if (!filteredRows.value.length || !props.header) {
    ElMessage.warning('No data available for export')
    return
  }
  
  try {
    const exportData = {
      header: props.header,
      rows: filteredRows.value,
      date_from: dateFromModel.value || null,
      date_to: dateToModel.value || null
    }
    
    await exportToWord(getReportTypeForExport(), exportData, previewTitle.value.replace(/\s+/g, '_').toLowerCase())
  } catch (err) {
    ElMessage.error('Failed to export Word report')
  }
}
</script>

<style scoped>
.employee-header { display: flex; align-items: center; gap: 12px; }
.mb-3 { margin-bottom: 12px; }
.font-semibold { font-weight: 600; }
.text-sm { font-size: 14px; }
.text-gray-600 { color: #4b5563; }
.controls { display: flex; align-items: center; gap: 16px; margin-bottom: 10px; flex-wrap: wrap; }
.date-filter { display: flex; align-items: center; gap: 8px; }
.muted { color: #6b7280; font-size: 12px; }
.date-picker { width: 160px; }
.footer-actions { 
  display: flex;
  justify-content: space-between;
  align-items: center;
  width: 100%;
}
.footer-left {
  display: flex;
  gap: 8px;
}
.footer-right {
  display: flex;
  gap: 8px;
}

/* Select Date Message Styling */
.select-date-message {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 520px;
  background: #fafafa;
  border-radius: 8px;
  border: 2px dashed #d1d5db;
}

.message-content {
  text-align: center;
  max-width: 300px;
}

.message-icon {
  font-size: 48px;
  color: #6b7280;
  margin-bottom: 16px;
}

.message-content h3 {
  margin: 0 0 8px 0;
  font-size: 18px;
  font-weight: 600;
  color: #374151;
}

.message-content p {
  margin: 0;
  font-size: 14px;
  color: #6b7280;
  line-height: 1.5;
}

/* Table Styling */
.tardiness-table {
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

/* Date Column Styling */
.date-cell {
  font-weight: 600;
  color: #1f2937;
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  padding: 6px 12px;
  border-radius: 6px;
  font-size: 13px;
  letter-spacing: 0.025em;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Am In / Pm Out column styling */
.time-cell {
  font-weight: 600;
  color: #1f2937;
  background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
  padding: 6px 8px;
  border-radius: 6px;
  font-size: 12px;
  letter-spacing: 0.025em;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Late / Undertime: hrs mins in red */
.duration-red {
  color: #dc2626;
  font-weight: 700;
  font-size: 14px;
  letter-spacing: 0.025em;
}

/* Absent: red check only */
.absent-check-cell {
  display: flex;
  align-items: center;
  justify-content: center;
}

.absent-check-icon {
  color: #dc2626;
  font-size: 22px;
  font-weight: bold;
}

.absent-yes-text {
  color: #dc2626;
  font-weight: 700;
  font-size: 14px;
}

/* Present Cell Styling */
.present-cell {
  display: flex;
  align-items: center;
  gap: 6px;
  justify-content: center;
  padding: 8px 12px;
  border-radius: 8px;
  background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
  border: 2px solid #bbf7d0;
  box-shadow: 0 2px 4px rgba(34, 197, 94, 0.1);
}

.present-icon {
  color: #16a34a;
  font-size: 16px;
  font-weight: bold;
}

.present-text {
  color: #16a34a;
  font-weight: 600;
  font-size: 14px;
  letter-spacing: 0.025em;
}

/* Absent Cell Styling */
.absent-cell {
  display: flex;
  align-items: center;
  gap: 6px;
  justify-content: center;
  padding: 8px 12px;
  border-radius: 8px;
  background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
  border: 2px solid #fecaca;
  box-shadow: 0 2px 4px rgba(239, 68, 68, 0.1);
}

.absent-icon {
  color: #dc2626;
  font-size: 16px;
  font-weight: bold;
}

.absent-text {
  color: #dc2626;
  font-weight: 700;
  font-size: 14px;
  letter-spacing: 0.025em;
}

/* No Data Cell Styling */
.no-data-cell {
  color: #9ca3af;
  font-size: 13px;
  font-style: italic;
  padding: 6px 12px;
}
/* Table Header Styling */
:deep(.tardiness-table .el-table__header-wrapper th) {
  background: linear-gradient(135deg, #1f2937 0%, #374151 100%);
  color: white;
  font-weight: 700;
  font-size: 14px;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  padding: 16px 8px;
  border: none;
}

:deep(.tardiness-table .el-table__header-wrapper th .cell) {
  color: white;
  font-weight: 700;
}

/* Table Body Styling */
:deep(.tardiness-table .el-table__body-wrapper .el-table__row) {
  transition: all 0.2s ease;
}

:deep(.tardiness-table .el-table__body-wrapper .el-table__row:hover) {
  background-color: #f8fafc;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

:deep(.tardiness-table .el-table__body-wrapper .el-table__row td) {
  padding: 12px 8px;
  border: none;
  border-bottom: 1px solid #e5e7eb;
}

/* Alternating Row Colors */
:deep(.tardiness-table .el-table__body-wrapper .el-table__row--striped) {
  background-color: #fafafa;
}

:deep(.tardiness-table .el-table__body-wrapper .el-table__row--striped:hover) {
  background-color: #f1f5f9;
}

/* Dialog: fixed width so no extra white space to the right */
.tardiness-viewer-dialog :deep(.el-dialog) {
  width: 725px;
  max-width: 95vw;
}

:deep(.el-dialog__body) { 
  padding-bottom: 0 !important;
  overflow-x: hidden;
}

:deep(.el-dialog__footer) { 
  padding-top: 8px !important; 
  padding-bottom: 10px !important; 
}

/* Responsive Design */
@media (max-width: 768px) {
  .date-cell, .time-cell {
    padding: 4px 8px;
    font-size: 12px;
  }
}
</style>
