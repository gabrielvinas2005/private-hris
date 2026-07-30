import { ref, computed } from 'vue'
import { ElMessage } from 'element-plus'
import { usePDFPreview } from './usePDFPreview'
import { useExport } from './useExport'
import { getReportSystemLabel, getReportOrganizationName } from './useCompany.js'

/**
 * Unified Report Generation Composable
 * Provides a standardized way to generate reports with consistent header, footer, and formatting
 */
export function useReportGenerator() {
  const { exportToCSV } = useExport()
  const { generatePDFPreview, downloadPDF, downloadDOCX, printPDF, cleanup, reset } = usePDFPreview()

  // Report configuration defaults
  const defaultConfig = {
    paperSize: 'A4',
    orientation: 'portrait',
    departmentName: 'Department Of Trade and Industry',
    organizationName: '',
    printedBy: 'System User',
    showPageNumbers: true
  }

  /**
   * Generate complete report HTML with header, body, and footer
   */
  const generateReportHTML = (reportData, config = {}) => {
    const finalConfig = {
      ...defaultConfig,
      organizationName: getReportOrganizationName(),
      ...config
    }
    
    // Ensure reportData has default values
    const safeReportData = reportData || {}
    
    // Generate report body only (no custom header/footer - use universal system)
    const reportBodyHTML = generateReportBody(safeReportData, finalConfig)
    
    return reportBodyHTML
  }

  /**
   * Generate report body content
   */
  const generateReportBody = (reportData, config) => {
    const { title, subtitle, content, tableData, columns } = reportData
    
    let bodyHTML = ''
    
    // Report title and subtitle
    if (title) {
      bodyHTML += `
        <div style="text-align: center; margin-bottom: 20px;">
          <h1 style="margin: 0; font-size: 24px; color: #333;">${title}</h1>
          ${subtitle ? `<h2 style="margin: 10px 0; font-size: 18px; color: #666;">${subtitle}</h2>` : ''}
        </div>
      `
    }
    
    // Custom content
    if (content) {
      bodyHTML += `<div style="margin-bottom: 20px;">${content}</div>`
    }
    
    // Table data
    if (tableData && columns) {
      bodyHTML += generateTableHTML(tableData, columns, config)
    }
    
    return bodyHTML
  }

  /**
   * Generate table HTML
   */
  const generateTableHTML = (data, columns, config = {}) => {
    if (!data || !Array.isArray(data) || data.length === 0) {
      return '<p style="text-align: center; color: #666; font-style: italic;">No data available</p>'
    }

    const headersHTML = columns.map(col => 
      `<th style="border: 1px solid #ddd; padding: 12px; text-align: ${col.align || 'left'}; font-weight: bold; background-color: #f5f5f5;">${col.label}</th>`
    ).join('')

    const rowsHTML = data.map((row, index) => {
      const cellsHTML = columns.map(col => {
        const value = row[col.key] || ''
        const cellStyle = `border: 1px solid #ddd; padding: 8px; text-align: ${col.align || 'left'};`
        const rowStyle = config.zebraStripes && index % 2 === 1 ? 'background-color: #f9f9f9;' : ''
        return `<td style="${cellStyle} ${rowStyle}">${value}</td>`
      }).join('')
      
      return `<tr>${cellsHTML}</tr>`
    }).join('')

    return `
      <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
        <thead>
          <tr>${headersHTML}</tr>
        </thead>
        <tbody>
          ${rowsHTML}
        </tbody>
      </table>
    `
  }

  /**
   * Export to Excel (CSV)
   */
  const exportToExcel = (data, columns, filename, config = {}) => {
    try {
      if (!data || !Array.isArray(data) || data.length === 0) {
        ElMessage.warning('No data available for export')
        return
      }

      const exportData = data.map(row => {
        const exportRow = {}
        columns.forEach(col => {
          exportRow[col.label] = row[col.key] || ''
        })
        return exportRow
      })

      const finalFilename = filename || `report_${new Date().toISOString().slice(0, 10)}.csv`
      exportToCSV(exportData, finalFilename)
      ElMessage.success('Excel file exported successfully')
    } catch (error) {
      console.error('Excel export error:', error)
      ElMessage.error('Failed to export Excel file')
    }
  }

  /**
   * Export to PDF
   */
  const exportToPDF = async (reportData, filename, config = {}) => {
    try {
      const finalConfig = { ...defaultConfig, ...config }
      const html = generateReportHTML(reportData, finalConfig)
      
      const options = {
        paperSize: finalConfig.paperSize,
        orientation: finalConfig.orientation,
        filename: filename || `report_${new Date().toISOString().slice(0, 10)}`
      }

      await generatePDFPreview(html, options)
      ElMessage.success('PDF generated successfully')
    } catch (error) {
      console.error('PDF export error:', error)
      ElMessage.error('Failed to export PDF file')
    }
  }

  /**
   * Export to Word (DOCX) - Using HTML to Word conversion
   */
  const exportToWord = async (reportData, filename, config = {}) => {
    try {
      const finalConfig = { ...defaultConfig, ...config }
      
      // Handle both template structure and direct data structure
      const tableData = reportData.tableData || []
      const columns = reportData.columns || []
      const title = reportData.title || 'Report'
      const subtitle = reportData.subtitle || ''
      
      // Debug logging
      console.log('Word Export - Report Data:', reportData)
      console.log('Word Export - Table Data:', tableData)
      console.log('Word Export - Columns:', columns)
      console.log('Word Export - Title:', title)
      
      // Generate HTML content for Word export
      const htmlContent = generateReportHTML(reportData, finalConfig)
      
      const finalFilename = filename || `report_${new Date().toISOString().slice(0, 10)}.doc`
      console.log('Word Export - Using HTML to Word conversion with filename:', finalFilename)
      
      // Use the working HTML to Word conversion method
      const { exportToWordFromHTML } = useExport()
      exportToWordFromHTML(htmlContent, finalFilename)
      ElMessage.success('Word document exported successfully')
      
    } catch (error) {
      console.error('Word export error:', error)
      ElMessage.error('Failed to export Word document')
    }
  }

  /**
   * Generate report for preview
   */
  const generateReportForPreview = (reportData, config = {}) => {
    const finalConfig = { ...defaultConfig, ...config }
    return generateReportHTML(reportData, finalConfig)
  }

  /**
   * Create report configuration for specific report types
   */
  const createReportConfig = (type, customConfig = {}) => {
    const systemLabel = getReportSystemLabel()
    const configs = {
      shift_schedule: {
        paperSize: 'A4',
        orientation: 'landscape',
        additionalInfo: `Shift Schedule Report - ${systemLabel}`
      },
      employee_details: {
        paperSize: 'A4',
        orientation: 'portrait',
        additionalInfo: `Employee Details Report - ${systemLabel}`
      },
      attendance: {
        paperSize: 'A4',
        orientation: 'portrait',
        additionalInfo: `Attendance Report - ${systemLabel}`
      },
      generic: {
        paperSize: 'A4',
        orientation: 'portrait',
        additionalInfo: `Report - ${systemLabel}`
      }
    }

    return { ...defaultConfig, ...configs[type], ...customConfig }
  }

  return {
    // Core functions
    generateReportHTML,
    generateReportForPreview,
    generateTableHTML,
    createReportConfig,
    
    // Export functions
    exportToExcel,
    exportToPDF,
    exportToWord,
    
    // PDF preview functions (re-exported from usePDFPreview)
    generatePDFPreview,
    downloadPDF,
    printPDF,
    cleanup,
    reset,
    
    // Default configuration
    defaultConfig
  }
}

export default useReportGenerator
