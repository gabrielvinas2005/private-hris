import { ref } from 'vue'
import { ElMessage } from 'element-plus'
import { personalDataSheetApi } from '@/services/api'

export function usePersonalDataSheet() {
    const loading = ref(false)
    const employees = ref([])
    const downloadLoading = ref(false)
    const generateLoading = ref(false)

    // Fetch all employees for PDS selection
    const fetchEmployees = async () => {
        try {
            loading.value = true
            const res = await personalDataSheetApi.getEmployees()
            employees.value = res.data.data || res.data || []
            return employees.value
        } catch (e) {
            ElMessage.error('Failed to load employees: ' + (e.response?.data?.message || e.message))
            console.error('Fetch error:', e)
            throw e
        } finally {
            loading.value = false
        }
    }

    // Generate PDS PDF for preview
    const generatePDFPreview = async (employeeId, employeeName) => {
        try {
            generateLoading.value = true
            const response = await personalDataSheetApi.generatePDF(employeeId)

            // Create blob URL for preview
            const blob = new Blob([response.data], { type: 'application/pdf' })
            const pdfUrl = window.URL.createObjectURL(blob)

            return {
                pdfUrl,
                blob,
                filename: `personal_data_sheet_${employeeId}_${new Date().toISOString().split('T')[0]}.pdf`,
                employeeName
            }
        } catch (e) {
            ElMessage.error('Failed to generate PDF: ' + (e.response?.data?.message || e.message))
            console.error('Generate PDF error:', e)
            throw e
        } finally {
            generateLoading.value = false
        }
    }

    // Download PDF from blob
    const downloadPDFFromBlob = (blob, filename) => {
        const url = window.URL.createObjectURL(blob)
        const link = document.createElement('a')
        link.href = url
        link.download = filename
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
        window.URL.revokeObjectURL(url)
    }

    // Download existing PDS PDF
    const downloadPDF = async (employeeId, employeeName) => {
        try {
            downloadLoading.value = true
            const response = await personalDataSheetApi.downloadPDF(employeeId)

            // Create blob and download
            const blob = new Blob([response.data], { type: 'application/pdf' })
            const url = window.URL.createObjectURL(blob)
            const link = document.createElement('a')
            link.href = url
            link.download = `${employeeName.toUpperCase()} - PDSFile.pdf`
            document.body.appendChild(link)
            link.click()
            document.body.removeChild(link)
            window.URL.revokeObjectURL(url)

            ElMessage.success(`Personal Data Sheet for ${employeeName} downloaded successfully`)
        } catch (e) {
            ElMessage.error('Failed to download PDF: ' + (e.response?.data?.message || e.message))
            console.error('Download PDF error:', e)
            throw e
        } finally {
            downloadLoading.value = false
        }
    }

    return {
        loading,
        employees,
        downloadLoading,
        generateLoading,
        fetchEmployees,
        generatePDFPreview,
        downloadPDFFromBlob,
        downloadPDF
    }
}
