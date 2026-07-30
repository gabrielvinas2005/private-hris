import { ref } from 'vue'
import { ElMessage } from 'element-plus'
import { nosiApi } from '@/services/api'

export function useNOSI() {
    const loading = ref(false)
    const employees = ref([])
    const generateLoading = ref(false)

    // Fetch all employees with approved step increments
    const fetchEmployees = async () => {
        try {
            loading.value = true
            const res = await nosiApi.getEmployees()

            // Handle different response structures
            let data = res.data
            if (data && typeof data === 'object') {
                // If it's a successful API response with data property
                if (data.data && typeof data.data === 'object') {
                    // If it's paginated (has data.data array)
                    if (data.data.data && Array.isArray(data.data.data)) {
                        data = data.data.data
                    }
                    // If it's just a data object with array inside
                    else if (Array.isArray(data.data)) {
                        data = data.data
                    }
                    // If it's a paginated object, get the data property
                    else if (data.data.data) {
                        data = data.data.data
                    }
                    else {
                        data = data.data
                    }
                }
                // If data itself is an array
                else if (Array.isArray(data)) {
                    data = data
                }
            }

            // Ensure we always have an array
            employees.value = Array.isArray(data) ? data : []

            console.log('NOSI API Response:', res.data)
            console.log('NOSI employees loaded:', employees.value.length, employees.value)
            return employees.value
        } catch (e) {
            ElMessage.error('Failed to load employees: ' + (e.response?.data?.message || e.message))
            console.error('Fetch error:', e)
            employees.value = [] // Ensure we have an empty array on error
            throw e
        } finally {
            loading.value = false
        }
    }

    // Generate NOSI PDF for preview
    const generateNOSIPreview = async (formData) => {
        try {
            generateLoading.value = true
            const response = await nosiApi.generatePDF(formData)

            // Create blob URL for preview
            const blob = new Blob([response.data], { type: 'application/pdf' })
            const pdfUrl = window.URL.createObjectURL(blob)

            return {
                pdfUrl,
                blob,
                filename: `notice_of_salary_step_${formData.employee}_${new Date().toISOString().split('T')[0]}.pdf`,
                employeeName: formData.employeeName || 'Employee'
            }
        } catch (e) {
            ElMessage.error('Failed to generate NOSI PDF: ' + (e.response?.data?.message || e.message))
            console.error('Generate NOSI error:', e)
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

    return {
        loading,
        employees,
        generateLoading,
        fetchEmployees,
        generateNOSIPreview,
        downloadPDFFromBlob
    }
}
