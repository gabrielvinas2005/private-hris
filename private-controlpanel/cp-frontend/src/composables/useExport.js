import { ElMessage } from 'element-plus'

/**
 * Reusable export utility for table data
 * @param {Object} config - Export configuration
 * @param {string} config.title - Title for the export (e.g., "Office Setup")
 * @param {Array} config.data - Array of data objects to export
 * @param {Array} config.columns - Array of column definitions { key, label, formatter? }
 * @param {Object} config.columnVisibility - Object with column visibility flags
 */
export function useExport() {
  function exportPrint({ title, data, columns, columnVisibility = {} }) {
    if (data.length === 0) {
      ElMessage.warning('No data to print')
      return
    }

    // Filter columns based on visibility
    const visibleColumns = columns.filter(col => columnVisibility[col.key] !== false)

    let html = `
      <!DOCTYPE html>
      <html>
      <head>
        <meta charset="UTF-8">
        <style>
          @page { 
            margin: 0.5cm; 
            size: A4 landscape;
            @top-left { content: ""; }
            @top-right { content: ""; }
            @bottom-left { content: ""; }
            @bottom-right { content: ""; }
          }
          body { font-family: Arial, sans-serif; margin: 0; padding: 10px; }
          h1 { text-align: center; margin: 0 0 15px 0; font-size: 18px; }
          table { width: 100%; border-collapse: collapse; margin-top: 10px; }
          th, td { border: 1px solid #ddd; padding: 6px; text-align: left; font-size: 11px; }
          th { background-color: #f2f2f2; font-weight: bold; }
          tr:nth-child(even) { background-color: #f9f9f9; }
          @media print {
            body { margin: 0; padding: 5px; }
            @page { 
              margin: 0.5cm; 
              @top-left { content: ""; }
              @top-right { content: ""; }
              @bottom-left { content: ""; }
              @bottom-right { content: ""; }
            }
          }
        </style>
      </head>
      <body>
        <h1>${title}</h1>
        <table>
          <thead>
            <tr>
              ${visibleColumns.map(col => `<th>${col.label}</th>`).join('')}
            </tr>
          </thead>
          <tbody>
            ${data.map(row => `
              <tr>
                ${visibleColumns.map(col => {
                  let value = ''
                  if (col.formatter) {
                    value = col.formatter(row)
                  } else {
                    value = row[col.key] || ''
                  }
                  return `<td>${String(value).replace(/</g, '&lt;').replace(/>/g, '&gt;')}</td>`
                }).join('')}
              </tr>
            `).join('')}
          </tbody>
        </table>
      </body>
      </html>
    `
    
    const printWindow = window.open('', '_blank')
    printWindow.document.write(html)
    printWindow.document.close()
    printWindow.focus()
    setTimeout(() => {
      printWindow.print()
      printWindow.close()
    }, 250)
  }

  function exportExcel({ title, data, columns, columnVisibility = {} }) {
    if (data.length === 0) {
      ElMessage.warning('No data to export')
      return
    }

    // Filter columns based on visibility
    const visibleColumns = columns.filter(col => columnVisibility[col.key] !== false)

    // Create CSV content
    const headers = visibleColumns.map(col => col.label).join(',')
    const rows = data.map(row => {
      return visibleColumns.map(col => {
        let value = ''
        if (col.formatter) {
          value = col.formatter(row)
        } else {
          value = row[col.key] || ''
        }
        // Escape quotes and wrap in quotes if contains comma or quote
        const stringValue = String(value)
        if (stringValue.includes(',') || stringValue.includes('"') || stringValue.includes('\n')) {
          return `"${stringValue.replace(/"/g, '""')}"`
        }
        return stringValue
      }).join(',')
    })

    const csvContent = [headers, ...rows].join('\n')
    
    // Add BOM for Excel UTF-8 support
    const BOM = '\uFEFF'
    const blob = new Blob([BOM + csvContent], { type: 'text/csv;charset=utf-8;' })
    const link = document.createElement('a')
    const url = URL.createObjectURL(blob)
    
    link.setAttribute('href', url)
    link.setAttribute('download', `${title.replace(/\s+/g, '_')}_${new Date().toISOString().split('T')[0]}.csv`)
    link.style.visibility = 'hidden'
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    
    ElMessage.success('Excel file downloaded successfully')
  }

  function exportPDF({ title, data, columns, columnVisibility = {} }) {
    if (data.length === 0) {
      ElMessage.warning('No data to export')
      return
    }

    // Filter columns based on visibility
    const visibleColumns = columns.filter(col => columnVisibility[col.key] !== false)

    let html = `
      <!DOCTYPE html>
      <html>
      <head>
        <meta charset="UTF-8">
        <style>
          @page { 
            margin: 0.5cm; 
            size: A4 landscape;
            @top-left { content: ""; }
            @top-right { content: ""; }
            @bottom-left { content: ""; }
            @bottom-right { content: ""; }
          }
          body { font-family: Arial, sans-serif; margin: 0; padding: 10px; }
          h1 { text-align: center; margin: 0 0 15px 0; font-size: 18px; color: #303133; }
          table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 11px; }
          th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
          th { background-color: #409eff; color: white; font-weight: bold; }
          tr:nth-child(even) { background-color: #f9f9f9; }
          @media print {
            body { margin: 0; padding: 5px; }
            @page { 
              margin: 0.5cm; 
              size: A4 landscape;
              @top-left { content: ""; }
              @top-right { content: ""; }
              @bottom-left { content: ""; }
              @bottom-right { content: ""; }
            }
          }
        </style>
      </head>
      <body>
        <h1>${title}</h1>
        <table>
          <thead>
            <tr>
              ${visibleColumns.map(col => `<th>${col.label}</th>`).join('')}
            </tr>
          </thead>
          <tbody>
            ${data.map(row => `
              <tr>
                ${visibleColumns.map(col => {
                  let value = ''
                  if (col.formatter) {
                    value = col.formatter(row)
                  } else {
                    value = row[col.key] || ''
                  }
                  return `<td>${String(value).replace(/</g, '&lt;').replace(/>/g, '&gt;')}</td>`
                }).join('')}
              </tr>
            `).join('')}
          </tbody>
        </table>
      </body>
      </html>
    `
    
    const printWindow = window.open('', '_blank')
    printWindow.document.write(html)
    printWindow.document.close()
    printWindow.focus()
    
    setTimeout(() => {
      printWindow.print()
    }, 250)
    
    ElMessage.success('PDF export ready. Use the print dialog to save as PDF.')
  }

  return {
    exportPrint,
    exportExcel,
    exportPDF
  }
}

