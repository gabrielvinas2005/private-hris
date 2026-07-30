import { ref, computed } from 'vue'
import { useReportGenerator } from './useReportGenerator'
import { useReportConfig } from './useReportConfig'
import { formatTime as formatTimeAMPM } from './useTimeFormatting'

/**
 * Unified Report Composable
 * Combines all report functionality into a single, easy-to-use interface
 */
export function useUnifiedReport() {
  const { 
    generateReportHTML, 
    generateReportForPreview,
    exportToExcel, 
    exportToPDF, 
    exportToWord,
    generatePDFPreview,
    downloadPDF,
    printPDF,
    cleanup,
    reset
  } = useReportGenerator()

  const { getReportConfig, createCustomConfig } = useReportConfig()

  // ============================================================================
  // Utility Functions
  // ============================================================================
  
  /**
   * Time formatting utility - use centralized formatter
   */
  const formatTime = (value) => formatTimeAMPM(value)

  /**
   * Date formatting utility
   */
  const formatDate = (dateString) => {
    if (!dateString) return 'N/A'
    const date = new Date(dateString)
    if (isNaN(date)) return 'N/A'
    const months = ['January', 'February', 'March', 'April', 'May', 'June', 
                   'July', 'August', 'September', 'October', 'November', 'December']
    return `${months[date.getMonth()]} ${date.getDate()}, ${date.getFullYear()}`
  }

  /**
   * Hours formatting utility
   */
  const formatHours = (value) => {
    if (!value || value === 0) return '0 hrs'
    const numValue = Number(value)
    const hours = Math.floor(numValue)
    const minutes = Math.round((numValue - hours) * 60)
    
    if (hours === 0 && minutes === 0) return '0 hrs'
    if (hours === 0) return `${minutes} mins`
    if (minutes === 0) return `${hours} hr${hours === 1 ? '' : 's'}`
    return `${hours} hr${hours === 1 ? '' : 's'} ${minutes} mins`
  }

  /**
   * Grace period formatting utility
   */
  const formatGracePeriod = (value) => {
    if (!value || value === 0) return 0
    return Math.round(Number(value))
  }

  // ============================================================================
  // Template Creation Functions
  // ============================================================================

  /**
   * Shift Schedule List Template
   */
  const createShiftScheduleListTemplate = (data) => {
    const columns = [
      { key: 'name', label: 'Schedule Name', align: 'left' },
      { key: 'date_from', label: 'Date From', align: 'center' },
      { key: 'date_to', label: 'Date To', align: 'center' }
    ]

    const tableData = data.map(item => ({
      name: item.name || '',
      date_from: formatDate(item.date_from),
      date_to: formatDate(item.date_to)
    }))

    return {
      title: 'Shift Schedule Report',
      subtitle: '', // Removed Schedule Period subtitle
      tableData,
      columns,
      reportType: 'shift_schedule_list'
    }
  }

  /**
   * Shift Schedule Viewer Template
   */
  const createShiftScheduleViewerTemplate = (data, scheduleName, dateFrom, dateTo) => {
    const columns = [
      { key: 'shift_date', label: 'Date', align: 'left' },
      { key: 'am_in', label: 'AM In', align: 'center' },
      { key: 'am_out', label: 'AM Out', align: 'center' },
      { key: 'break_in', label: 'Break In', align: 'center' },
      { key: 'break_out', label: 'Break Out', align: 'center' },
      { key: 'pm_in', label: 'PM In', align: 'center' },
      { key: 'pm_out', label: 'PM Out', align: 'center' },
      { key: 'grace_period', label: 'Grace Period', align: 'center' },
      { key: 'flexi_hours', label: 'Flexi Hours', align: 'center' },
      { key: 'work_hours', label: 'Work Hours', align: 'center' }
    ]

    const tableData = data.map(row => ({
      shift_date: formatDate(row.shift_date),
      am_in: formatTime(row.am_in),
      am_out: formatTime(row.am_out),
      break_in: formatTime(row.break_in),
      break_out: formatTime(row.break_out),
      pm_in: formatTime(row.pm_in),
      pm_out: formatTime(row.pm_out),
      grace_period: formatGracePeriod(row.grace_period),
      flexi_hours: formatHours(row.flexi_hours),
      work_hours: formatHours(row.work_hours)
    }))

    return {
      title: 'Shift Schedule Viewer Report',
      subtitle: scheduleName,
      tableData,
      columns,
      reportType: 'shift_schedule_viewer'
    }
  }

  /**
   * Employee Details Template
   */
  const createEmployeeDetailsTemplate = (data, scheduleName) => {
    const columns = [
      { key: 'employee_no', label: 'Employee No', align: 'left' },
      { key: 'name', label: 'Name', align: 'left' },
      { key: 'position', label: 'Position', align: 'left' },
      { key: 'department', label: 'Department', align: 'left' }
    ]

    const tableData = data.map(emp => ({
      employee_no: emp.employee_no || '',
      name: emp.name || '',
      position: emp.position || '',
      department: emp.department || ''
    }))

    return {
      title: 'Assigned Employees Report',
      subtitle: scheduleName || 'Shift Schedule',
      tableData,
      columns,
      reportType: 'shift_schedule_employee_details'
    }
  }

  /**
   * Attendance Report Template
   */
  const createAttendanceReportTemplate = (data, period) => {
    const columns = [
      { key: 'employee_no', label: 'Employee No', align: 'left' },
      { key: 'name', label: 'Name', align: 'left' },
      { key: 'date', label: 'Date', align: 'center' },
      { key: 'time_in', label: 'Time In', align: 'center' },
      { key: 'time_out', label: 'Time Out', align: 'center' },
      { key: 'hours_worked', label: 'Hours Worked', align: 'center' },
      { key: 'status', label: 'Status', align: 'center' }
    ]

    const tableData = data.map(record => ({
      employee_no: record.employee_no || '',
      name: record.name || '',
      date: formatDate(record.date),
      time_in: formatTime(record.time_in),
      time_out: formatTime(record.time_out),
      hours_worked: formatHours(record.hours_worked),
      status: record.status || ''
    }))

    return {
      title: 'Attendance Report',
      subtitle: period || 'Attendance Records',
      tableData,
      columns,
      reportType: 'attendance_report'
    }
  }

  /**
   * Generic Table Template
   */
  const createGenericTableTemplate = (data, columns, title, subtitle = '') => {
    return {
      title,
      subtitle,
      tableData: data,
      columns,
      reportType: 'generic'
    }
  }

  /**
   * Custom Content Template
   */
  const createCustomContentTemplate = (content, title, subtitle = '') => {
    return {
      title,
      subtitle,
      content,
      reportType: 'generic'
    }
  }

  // State for preview
  const previewOpen = ref(false)
  const previewTitle = ref('Report Preview')
  const previewFilename = ref('report')
  const previewFormat = ref('pdf')

  /**
   * Generate and export shift schedule list report
   */
  const generateShiftScheduleListReport = (data, options = {}) => {
    const config = getReportConfig('shift_schedule_list', options.config)
    const template = createShiftScheduleListTemplate(data)
    
    return {
      config,
      template,
      exportToExcel: () => exportToExcel(template.tableData, template.columns, options.filename, config),
      exportToPDF: () => exportToPDF(template, options.filename, config),
      exportToWord: () => exportToWord(template, options.filename, config),
      generatePreview: () => generateReportForPreview(template, config)
    }
  }

  /**
   * Generate and export shift schedule viewer report
   */
  const generateShiftScheduleViewerReport = (data, scheduleName, dateFrom, dateTo, options = {}) => {
    const config = getReportConfig('shift_schedule_viewer', options.config)
    const template = createShiftScheduleViewerTemplate(data, scheduleName, dateFrom, dateTo)
    
    return {
      config,
      template,
      exportToExcel: () => exportToExcel(template.tableData, template.columns, options.filename, config),
      exportToPDF: () => exportToPDF(template, options.filename, config),
      exportToWord: () => exportToWord(template, options.filename, config),
      generatePreview: () => generateReportForPreview(template, config)
    }
  }

  /**
   * Generate and export employee details report
   */
  const generateEmployeeDetailsReport = (data, scheduleName, options = {}) => {
    const config = getReportConfig('shift_schedule_employee_details', options.config)
    const template = createEmployeeDetailsTemplate(data, scheduleName)
    
    return {
      config,
      template,
      exportToExcel: () => exportToExcel(template.tableData, template.columns, options.filename, config),
      exportToPDF: () => exportToPDF(template, options.filename, config),
      exportToWord: () => exportToWord(template, options.filename, config),
      generatePreview: () => generateReportForPreview(template, config)
    }
  }

  /**
   * Generate and export attendance report
   */
  const generateAttendanceReport = (data, period, options = {}) => {
    const config = getReportConfig('attendance_report', options.config)
    const template = createAttendanceReportTemplate(data, period)
    
    return {
      config,
      template,
      exportToExcel: () => exportToExcel(template.tableData, template.columns, options.filename, config),
      exportToPDF: () => exportToPDF(template, options.filename, config),
      exportToWord: () => exportToWord(template, options.filename, config),
      generatePreview: () => generateReportForPreview(template, config)
    }
  }

  /**
   * Generate and export generic table report
   */
  const generateGenericTableReport = (data, columns, title, subtitle = '', options = {}) => {
    const config = createCustomConfig(options.config)
    const template = createGenericTableTemplate(data, columns, title, subtitle)
    
    return {
      config,
      template,
      exportToExcel: () => exportToExcel(template.tableData, template.columns, options.filename, config),
      exportToPDF: () => exportToPDF(template, options.filename, config),
      exportToWord: () => exportToWord(template, options.filename, config),
      generatePreview: () => generateReportForPreview(template, config)
    }
  }

  /**
   * Generate and export custom content report
   */
  const generateCustomContentReport = (content, title, subtitle = '', options = {}) => {
    const config = createCustomConfig(options.config)
    const template = createCustomContentTemplate(content, title, subtitle)
    
    return {
      config,
      template,
      exportToPDF: () => exportToPDF(template, options.filename, config),
      exportToWord: () => exportToWord(template, options.filename, config),
      generatePreview: () => generateReportForPreview(template, config)
    }
  }

  /**
   * Open preview modal
   */
  const openPreview = (template, config, format = 'pdf') => {
    previewTitle.value = template.title
    previewFilename.value = template.reportType || 'report'
    previewFormat.value = format
    previewOpen.value = true
  }

  /**
   * Close preview modal
   */
  const closePreview = () => {
    previewOpen.value = false
    cleanup()
  }

  /**
   * Get preview HTML content
   */
  const getPreviewContent = (template, config) => {
    return generateReportForPreview(template, config)
  }

  return {
    // State
    previewOpen: computed(() => previewOpen.value),
    previewTitle: computed(() => previewTitle.value),
    previewFilename: computed(() => previewFilename.value),
    previewFormat: computed(() => previewFormat.value),

    // Report generators
    generateShiftScheduleListReport,
    generateShiftScheduleViewerReport,
    generateEmployeeDetailsReport,
    generateAttendanceReport,
    generateGenericTableReport,
    generateCustomContentReport,

    // Preview functions
    openPreview,
    closePreview,
    getPreviewContent,

    // Direct export functions
    exportToExcel,
    exportToPDF,
    exportToWord,

    // PDF preview functions
    generatePDFPreview,
    downloadPDF,
    printPDF,
    cleanup,
    reset,

    // Configuration functions
    getReportConfig,
    createCustomConfig,

    // Utility functions (exposed for external use if needed)
    formatTime,
    formatDate,
    formatHours,
    formatGracePeriod
  }
}

export default useUnifiedReport
