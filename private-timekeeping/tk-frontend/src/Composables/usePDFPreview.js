import { ref, computed } from 'vue'
import { ElMessage } from 'element-plus'
import { apiUrl } from '../config/api'

/**
 * Composable for sophisticated PDF preview functionality
 * Based on HR module's PDF preview system
 */
export function usePDFPreview() {
  const loading = ref(false)
  const pdfUrl = ref(null)
  const error = ref(null)
  const blob = ref(null)
  const filename = ref('')

  /**
   * Generate PDF preview from HTML content
   */
  const generatePDFPreview = async (html, options = {}) => {
    try {
      loading.value = true
      error.value = null

      const {
        paperSize = 'A4',
        orientation = 'portrait',
        filename: customFilename = `report_${new Date().toISOString().split('T')[0]}`,
        template = null,
        data = null,
        withHeaderFooter = false,
        department = 'Department Name',
        printedBy = 'System',
        title = 'Report'
      } = options

      let response

      if (template && data) {
        // Generate from template and data
        const requestBody = {
          template,
          data,
          paper_size: paperSize,
          orientation,
          filename: customFilename,
          with_header_footer: withHeaderFooter,
          department,
          printed_by: printedBy,
          title
        }
        
        console.log('PDF Preview Request:', {
          url: apiUrl('/reports/pdf/preview/template'),
          method: 'POST',
          body: requestBody
        })
        
        response = await fetch(apiUrl('/reports/pdf/preview/template'), {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/pdf',
          },
          credentials: 'include',
          body: JSON.stringify(requestBody)
        })
      } else {
        // Generate from HTML content
        const requestBody = {
          html,
          paper_size: paperSize,
          orientation,
          filename: customFilename,
          with_header_footer: withHeaderFooter,
          department,
          printed_by: printedBy,
          title
        }
        
        response = await fetch(apiUrl('/reports/pdf/preview'), {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/pdf',
          },
          credentials: 'include',
          body: JSON.stringify(requestBody)
        })
      }

      if (!response.ok) {
        const errorText = await response.text()
        console.error('PDF generation failed:', {
          status: response.status,
          statusText: response.statusText,
          error: errorText,
          url: response.url
        })
        throw new Error(`PDF generation failed: ${response.status} - ${errorText}`)
      }

      const pdfBlob = await response.blob()
      
      if (pdfBlob.size === 0) {
        throw new Error('Generated PDF is empty')
      }

      // Create blob URL for preview
      const blobUrl = window.URL.createObjectURL(pdfBlob)
      
      blob.value = pdfBlob
      pdfUrl.value = blobUrl
      filename.value = customFilename

      return {
        pdfUrl: blobUrl,
        blob: pdfBlob,
        filename: customFilename
      }
    } catch (e) {
      error.value = e.message
      ElMessage.error(`PDF preview generation failed: ${e.message}`)
      throw e
    } finally {
      loading.value = false
    }
  }

  /**
   * Generate PDF preview with header and footer
   */
  const generatePDFPreviewWithHeaderFooter = async (html, options = {}) => {
    return await generatePDFPreview(html, {
      ...options,
      withHeaderFooter: true
    })
  }

  /**
   * Download PDF from blob
   */
  const downloadPDF = (customFilename = null) => {
    if (!blob.value) {
      ElMessage.error('No PDF available for download')
      return
    }

    try {
      const url = window.URL.createObjectURL(blob.value)
      const link = document.createElement('a')
      link.href = url
      link.download = customFilename || `${filename.value}.pdf`
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
      window.URL.revokeObjectURL(url)
      
      ElMessage.success('PDF downloaded successfully')
    } catch (e) {
      ElMessage.error(`Download failed: ${e.message}`)
    }
  }

  /**
   * Download DOCX using structured data approach
   */
  const downloadDOCX = async (reportData, customFilename = null) => {
    try {
      if (!reportData || !reportData.report_type) {
        ElMessage.error('Invalid report data provided')
        return
      }

      const name = customFilename || filename.value || `${reportData.report_type}_${new Date().toISOString().split('T')[0]}`

      const requestBody = {
        report_type: reportData.report_type,
        data: reportData.data || {},
        filename: name,
        paper_size: reportData.paper_size || 'A4',
        orientation: reportData.orientation || 'portrait'
      }

      const response = await fetch(apiUrl('/reports/docx'), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(requestBody)
      })

      if (!response.ok) {
        const errorText = await response.text()
        throw new Error(`DOCX generation failed: ${response.status} - ${errorText}`)
      }

      const docxBlob = await response.blob()
      if (docxBlob.size === 0) {
        throw new Error('Generated DOCX is empty')
      }

      const url = window.URL.createObjectURL(docxBlob)
      const link = document.createElement('a')
      link.href = url
      link.download = `${name}.docx`
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
      window.URL.revokeObjectURL(url)

      ElMessage.success('DOCX downloaded successfully')
    } catch (e) {
      ElMessage.error(`DOCX download failed: ${e.message}`)
      throw e
    }
  }

  /**
   * Download DOCX for specific report types
   */
  const downloadAttendanceReportDOCX = async (attendanceData, customFilename = null) => {
    const reportData = {
      report_type: 'attendance_report',
      data: attendanceData,
      paper_size: 'A4',
      orientation: 'portrait'
    }
    return await downloadDOCX(reportData, customFilename)
  }

  const downloadShiftScheduleReportDOCX = async (scheduleData, customFilename = null) => {
    const reportData = {
      report_type: 'shift_schedule_report',
      data: scheduleData,
      paper_size: 'A4',
      orientation: 'landscape'
    }
    return await downloadDOCX(reportData, customFilename)
  }

  const downloadAssignedEmployeesReportDOCX = async (employeeData, customFilename = null) => {
    const reportData = {
      report_type: 'assigned_employees_report',
      data: employeeData,
      paper_size: 'A4',
      orientation: 'portrait'
    }
    return await downloadDOCX(reportData, customFilename)
  }

  const downloadShiftingScheduleDetailsReportDOCX = async (scheduleDetailsData, customFilename = null) => {
    const reportData = {
      report_type: 'shifting_schedule_details_report',
      data: scheduleDetailsData,
      paper_size: 'A4',
      orientation: 'landscape'
    }
    return await downloadDOCX(reportData, customFilename)
  }

  /**
   * Print PDF
   */
  const printPDF = () => {
    if (!pdfUrl.value) {
      ElMessage.error('No PDF available for printing')
      return
    }

    try {
      const printWindow = window.open(pdfUrl.value, '_blank')
      if (printWindow) {
        printWindow.onload = () => {
          printWindow.print()
        }
      } else {
        ElMessage.error('Unable to open print window. Please check your browser settings.')
      }
    } catch (e) {
      ElMessage.error(`Print failed: ${e.message}`)
    }
  }

  /**
   * Clean up blob URL to prevent memory leaks
   */
  const cleanup = () => {
    if (pdfUrl.value && pdfUrl.value.startsWith('blob:')) {
      window.URL.revokeObjectURL(pdfUrl.value)
    }
    pdfUrl.value = null
    blob.value = null
    filename.value = ''
    error.value = null
  }

  /**
   * Reset all state
   */
  const reset = () => {
    cleanup()
    loading.value = false
  }

  return {
    // State
    loading: computed(() => loading.value),
    pdfUrl: computed(() => pdfUrl.value),
    error: computed(() => error.value),
    blob: computed(() => blob.value),
    filename: computed(() => filename.value),
    
    // Methods
    generatePDFPreview,
    generatePDFPreviewWithHeaderFooter,
    downloadPDF,
    downloadDOCX,
    downloadAttendanceReportDOCX,
    downloadShiftScheduleReportDOCX,
    downloadAssignedEmployeesReportDOCX,
    downloadShiftingScheduleDetailsReportDOCX,
    printPDF,
    cleanup,
    reset
  }
}
