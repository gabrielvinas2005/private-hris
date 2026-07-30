// Lightweight export helpers usable across the project
// Excel: CSV; Word: HTML-based .doc; PDF: window.print() flow

function downloadBlob(blob, filename) {
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = filename
  document.body.appendChild(a)
  a.click()
  document.body.removeChild(a)
  URL.revokeObjectURL(url)
}

export function useExport() {
  function exportToCSV(rows, filename = 'export.csv') {
    if (!Array.isArray(rows) || rows.length === 0) {
      rows = []
    }
    const headers = rows.length > 0 ? Object.keys(rows[0]) : []
    const escape = (val) => {
      if (val === null || val === undefined) return ''
      const s = String(val)
      if (s.includes('"') || s.includes(',') || s.includes('\n')) {
        return '"' + s.replace(/"/g, '""') + '"'
      }
      return s
    }
    const lines = []
    if (headers.length) lines.push(headers.join(','))
    rows.forEach(r => {
      lines.push(headers.map(h => escape(r[h])).join(','))
    })
    const blob = new Blob([lines.join('\n')], { type: 'text/csv;charset=utf-8;' })
    downloadBlob(blob, filename)
  }

  function exportToWordFromHTML(html, filename = 'export.doc') {
    const header = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40"><head><meta charset="utf-8"></head><body>'
    const footer = '</body></html>'
    const blob = new Blob([header + html + footer], { type: 'application/msword' })
    downloadBlob(blob, filename)
  }

  function exportToPDFViaPrint(html, filename = 'report', options = {}) {
    const {
      withHeaderFooter = true,
      department = 'Department Name',
      printedBy = 'System',
      title = 'Report'
    } = options

    // Use centralized PDF API with header/footer support
    fetch('/api/reports/pdf', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        html: html,
        paper_size: 'A4',
        orientation: 'portrait',
        filename: filename,
        with_header_footer: withHeaderFooter,
        department: department,
        printed_by: printedBy,
        title: title
      })
    }).then(async (res) => {
      if (!res.ok) throw new Error('PDF generation failed')
      const blob = await res.blob()
      const url = URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = `${filename}.pdf`
      document.body.appendChild(a)
      a.click()
      a.remove()
      URL.revokeObjectURL(url)
    }).catch(err => {
      console.error('PDF generation error:', err)
      // Fallback to browser print if API fails
      const printWindow = window.open('', '_blank')
      if (!printWindow) return
      printWindow.document.open()
      printWindow.document.write(`
        <!doctype html>
        <html>
          <head>
            <meta charset="utf-8">
            <title></title>
            <style>
              @page {
                margin: 0.5in;
                size: A4;
              }
              @media print {
                @page {
                  margin: 0.5in;
                  size: A4;
                }
                body {
                  margin: 0;
                  padding: 0;
                }
                /* Hide browser-generated headers and footers */
                @page {
                  @top-left { content: ""; }
                  @top-center { content: ""; }
                  @top-right { content: ""; }
                  @bottom-left { content: ""; }
                  @bottom-center { content: ""; }
                  @bottom-right { content: ""; }
                }
              }
              body {
                margin: 0;
                padding: 0;
                font-family: Arial, sans-serif;
              }
            </style>
          </head>
          <body>${html}</body>
        </html>
      `) 
      printWindow.document.close()
      printWindow.focus()
      setTimeout(() => { printWindow.print(); printWindow.close() }, 300)
    })
  }

  function previewReport(html, options = {}) {
    const {
      title = 'Report Preview',
      filename = 'report',
      downloadFormat = 'pdf'
    } = options


    // This function will be used to trigger the preview dialog
    // The actual preview will be handled by the ReportPreview component
    return {
      html,
      title,
      filename,
      downloadFormat
    }
  }

  return { exportToCSV, exportToWordFromHTML, exportToPDFViaPrint, previewReport }
}

export default useExport





