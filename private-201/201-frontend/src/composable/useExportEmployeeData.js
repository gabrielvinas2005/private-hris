import { ref } from 'vue'
import { ElMessage } from 'element-plus'
import { exportEmployeeDataApi } from '@/services/api'

export function useExportEmployeeData() {
    const loading = ref(false)
    const employeeData = ref([])
    const exportProgress = ref(0)

    const fetchEmployeeData = async (filters = {}) => {
        try {
            loading.value = true
            const res = await exportEmployeeDataApi.getEmployeeData()
            employeeData.value = res.data.data || res.data || []

            // Debug: Log the first employee to check if decryption is working
            if (employeeData.value.length > 0) {
                console.log('Sample employee data:', employeeData.value[0])
                console.log('Employee name field:', employeeData.value[0].name)
                console.log('Employee email field:', employeeData.value[0].email)
                console.log('All employee fields:', Object.keys(employeeData.value[0]))
            }

            return employeeData.value
        } catch (e) {
            ElMessage.error('Failed to load employee data: ' + (e.response?.data?.message || e.message))
            console.error('Fetch error:', e)
            throw e
        } finally {
            loading.value = false
        }
    }

    const exportToCSV = async (data, filename = 'employee_data.csv') => {
        try {
            if (!data || data.length === 0) {
                ElMessage.warning('No data to export')
                return
            }

            // Convert data to CSV format
            const headers = Object.keys(data[0])

            const normalizeBoolean = (value) => {
                // Only convert if the runtime type is boolean
                if (typeof value === 'boolean') {
                    return value ? 'Y' : 'N'
                }
                return value
            }

            const csvContent = [
                headers.join(','), // Header row
                ...data.map(row =>
                    headers.map(header => {
                        let raw = row[header]
                        let value = raw ?? ''

                        value = normalizeBoolean(value)

                        if (typeof value === 'string') {
                            value = value.trim()
                        }

                        // Escape commas and quotes in values
                        return `"${String(value).replace(/"/g, '""')}"`
                    }).join(',')
                )
            ].join('\n')

            // Create and download file
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
            const link = document.createElement('a')
            const url = URL.createObjectURL(blob)
            link.setAttribute('href', url)
            link.setAttribute('download', filename)
            link.style.visibility = 'hidden'
            document.body.appendChild(link)
            link.click()
            document.body.removeChild(link)

            ElMessage.success('CSV export completed successfully')
        } catch (error) {
            console.error('CSV export failed:', error)
            ElMessage.error('Failed to export CSV file')
            throw error
        }
    }

    const exportToExcel = async (_data, filename = 'employee_data.xlsx') => {
        try {
            const response = await exportEmployeeDataApi.exportData('xlsx')
            const contentType = response.headers['content-type'] || 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            const blob = new Blob([response.data], { type: contentType })
            const url = URL.createObjectURL(blob)
            const link = document.createElement('a')
            link.href = url
            link.download = filename || 'employee_data.xlsx'
            document.body.appendChild(link)
            link.click()
            document.body.removeChild(link)
            URL.revokeObjectURL(url)
            ElMessage.success('Excel export completed successfully')
        } catch (error) {
            console.error('Excel export failed:', error)
            ElMessage.error('Failed to export Excel file')
            throw error
        }
    }

    const exportToPDF = async (data, filename = 'employee_data.pdf') => {
        try {
            if (!data || data.length === 0) {
                ElMessage.warning('No data to export')
                return
            }

            // Generate HTML content for PDF
            const pdfContent = generatePDFContent(data)

            // Open print window
            const printWindow = window.open('', '_blank')
            if (!printWindow) {
                ElMessage.error('Please allow popups to export PDF')
                return
            }

            printWindow.document.write(pdfContent)
            printWindow.document.close()

            // Wait for content to load, then show print dialog (user can save as PDF)
            printWindow.onload = () => {
                setTimeout(() => {
                    printWindow.print()
                    // Don't close immediately - let user choose to save as PDF
                    ElMessage.success('Use your browser\'s "Save as PDF" option in the print dialog')
                }, 250)
            }
        } catch (error) {
            console.error('PDF export failed:', error)
            ElMessage.error('Failed to export PDF file')
            throw error
        }
    }

    const generatePDFContent = (data) => {
        const currentDate = new Date().toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        })

        // Get headers from first data object
        let headers = data.length > 0 ? Object.keys(data[0]) : []
        // Mirror backend Excel: drop internal id if present
        headers = headers.filter((h) => h !== 'id')
        // Preferred order: Name, Photo, Employee No, Access No
        const preferredOrder = ['name', 'photo', 'employee_no', 'access_no']
        const orderedHeaders = []
        preferredOrder.forEach((key) => {
            if (headers.includes(key)) orderedHeaders.push(key)
        })
        headers.forEach((h) => {
            if (!orderedHeaders.includes(h)) orderedHeaders.push(h)
        })
        headers = orderedHeaders

        const escapeHtml = (value) => {
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;')
        }

        const resolvePhotoSrc = (value) => {
            if (!value) return ''
            if (typeof value !== 'string') return ''

            const trimmed = value.trim()
            if (!trimmed) return ''

            // Already a data URL
            if (trimmed.startsWith('data:')) return trimmed

            // Absolute URL
            if (/^https?:\/\//i.test(trimmed)) return trimmed

            // Base64-ish payload (common in this codebase)
            if (/^[A-Za-z0-9+/=]+$/.test(trimmed) && trimmed.length > 40) {
                return `data:image/jpeg;base64,${trimmed}`
            }

            // Relative/absolute path from backend
            const apiBase = import.meta.env.VITE_API_URL || ''
            const serverBase = apiBase.replace(/\/api\/?$/i, '')
            const cleanPath = trimmed.startsWith('/') ? trimmed : `/${trimmed}`
            return serverBase ? `${serverBase}${cleanPath}` : cleanPath
        }

        const booleanFields = new Set([
            'is_dual_citizent',
            'by_birth',
            'by_naturalization',
            'is_shifting',
            'is_plantilla',
            'is_employee',
            'is_teaching',
            'active',
            'is_hold'
        ])

        const normalizeBoolean = (header, value) => {
            if (!booleanFields.has(header)) return value
            if (typeof value === 'boolean') {
                return value ? 'Y' : 'N'
            }
            if (value === 1 || value === 0) {
                return value === 1 ? 'Y' : 'N'
            }
            if (typeof value === 'string') {
                const trimmed = value.trim()
                if (trimmed === '1') return 'Y'
                if (trimmed === '0') return 'N'
            }
            return value
        }

        let tableRows = ''
        data.forEach((row, index) => {
            tableRows += '<tr>'
            tableRows += `<td style="border: 1px solid #ddd; padding: 8px; text-align: center;">${index + 1}</td>`
            headers.forEach(header => {
                const value = row[header] ?? ''
                // Format the value based on type
                let displayValue = value
                if (typeof value === 'number') {
                    displayValue = value.toLocaleString()
                } else if (value instanceof Date) {
                    displayValue = new Date(value).toLocaleDateString()
                }
                displayValue = normalizeBoolean(header, displayValue)

                // Render photo/image fields as <img> instead of raw text
                const isPhotoField = /photo|avatar|image/i.test(String(header))
                if (isPhotoField) {
                    const src = resolvePhotoSrc(displayValue)
                    tableRows += `<td style="border: 1px solid #ddd; padding: 8px; text-align: center;">
                        ${src ? `<img src="${escapeHtml(src)}" alt="Photo" style="max-height: 60px; max-width: 80px; object-fit: cover; border-radius: 4px;" />` : ''}
                    </td>`
                } else {
                    tableRows += `<td style="border: 1px solid #ddd; padding: 8px;">${escapeHtml(displayValue)}</td>`
                }
            })
            tableRows += '</tr>'
        })

        // Create header row
        let headerRow = '<tr>'
        headerRow += '<th style="border: 1px solid #ddd; padding: 10px; text-align: center; background-color: #4a5568; color: white;">#</th>'
        headers.forEach(header => {
            const headerLabel = header.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
            headerRow += `<th style="border: 1px solid #ddd; padding: 10px; background-color: #4a5568; color: white;">${headerLabel}</th>`
        })
        headerRow += '</tr>'

        return `
            <!DOCTYPE html>
            <html>
            <head>
                <title>Employee Data Export</title>
                <style>
                    @media print {
                        @page {
                            margin: 1cm;
                            size: A4 landscape;
                        }
                    }
                    body {
                        font-family: Arial, sans-serif;
                        margin: 20px;
                        color: #333;
                    }
                    h1 {
                        text-align: center;
                        color: #333;
                        margin-bottom: 10px;
                    }
                    .report-info {
                        text-align: center;
                        color: #666;
                        margin-bottom: 20px;
                    }
                    table {
                        width: 100%;
                        border-collapse: collapse;
                        margin-top: 20px;
                        font-size: 11px;
                    }
                    th {
                        background-color: #4a5568;
                        color: white;
                        border: 1px solid #ddd;
                        padding: 10px 8px;
                        text-align: left;
                        font-weight: bold;
                    }
                    td {
                        border: 1px solid #ddd;
                        padding: 8px;
                    }
                    img {
                        display: inline-block;
                    }
                    tr:nth-child(even) {
                        background-color: #f9f9f9;
                    }
                </style>
            </head>
            <body>
                <h1>Employee Data Export</h1>
                <div class="report-info">
                    <p>Generated on: ${currentDate}</p>
                    <p>Total Records: ${data.length}</p>
                </div>
                <table>
                    <thead>
                        ${headerRow}
                    </thead>
                    <tbody>
                        ${tableRows}
                    </tbody>
                </table>
            </body>
            </html>
        `
    }

    const getPdfPreviewUrl = (data) => {
        if (!data || data.length === 0) {
            return ''
        }
        const html = generatePDFContent(data)
        const blob = new Blob([html], { type: 'text/html;charset=utf-8;' })
        return URL.createObjectURL(blob)
    }

    const exportData = async (format, data, filename) => {
        try {
            exportProgress.value = 0
            loading.value = true

            // Simulate progress
            const progressInterval = setInterval(() => {
                if (exportProgress.value < 90) {
                    exportProgress.value += 10
                }
            }, 100)

            switch (format.toLowerCase()) {
                case 'csv':
                    await exportToCSV(data, filename || 'employee_data.csv')
                    break
                case 'xlsx':
                case 'excel':
                    await exportToExcel(data, filename || 'employee_data.xlsx')
                    break
                case 'pdf':
                    await exportToPDF(data, filename || 'employee_data.pdf')
                    break
                default:
                    throw new Error('Unsupported export format')
            }

            clearInterval(progressInterval)
            exportProgress.value = 100

            // Reset progress after a delay
            setTimeout(() => {
                exportProgress.value = 0
            }, 2000)

        } catch (error) {
            exportProgress.value = 0
            throw error
        } finally {
            loading.value = false
        }
    }

    const getFilteredData = (data, filters = {}) => {
        if (!filters || Object.keys(filters).length === 0) {
            return data
        }

        return data.filter(employee => {
            return Object.entries(filters).every(([key, value]) => {
                if (!value) return true // Skip empty filters

                const employeeValue = employee[key]
                if (typeof employeeValue === 'string') {
                    return employeeValue.toLowerCase().includes(value.toLowerCase())
                }
                return employeeValue === value
            })
        })
    }

    return {
        loading,
        employeeData,
        exportProgress,
        fetchEmployeeData,
        exportData,
        exportToCSV,
        exportToExcel,
        exportToPDF,
        getFilteredData,
        getPdfPreviewUrl
    }
}
