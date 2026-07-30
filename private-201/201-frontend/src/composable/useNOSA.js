import { ref } from 'vue'
import { ElMessage } from 'element-plus'
import { nosaApi } from '@/services/api'

export function useNOSA() {
    const loading = ref(false)
    const employees = ref([])
    const salarySchedules = ref([])
    const generateLoading = ref(false)

    // Fetch all plantilla employees and salary schedules
    const fetchEmployeesAndSchedules = async () => {
        try {
            loading.value = true
            const res = await nosaApi.getEmployeesAndSchedules()

            // Handle different response structures
            let responseData = res.data
            if (responseData && typeof responseData === 'object') {
                // If it's a successful API response with nested data property
                if (responseData.data && typeof responseData.data === 'object') {
                    // Extract employees from data.data
                    employees.value = Array.isArray(responseData.data.data) ? responseData.data.data : []
                    // Extract salary schedules from data.salary_schedules
                    salarySchedules.value = Array.isArray(responseData.data.salary_schedules) ? responseData.data.salary_schedules : []
                } else {
                    employees.value = []
                    salarySchedules.value = []
                }
            }

            console.log('NOSA API Response:', res.data)
            console.log('NOSA employees loaded:', employees.value.length, employees.value)
            console.log('NOSA salary schedules loaded:', salarySchedules.value.length, salarySchedules.value)

            return { employees: employees.value, salarySchedules: salarySchedules.value }
        } catch (e) {
            ElMessage.error('Failed to load data: ' + (e.response?.data?.message || e.message))
            console.error('Fetch error:', e)
            employees.value = [] // Ensure we have empty arrays on error
            salarySchedules.value = []
            throw e
        } finally {
            loading.value = false
        }
    }

    // Generate NOSA PDF for preview
    const generateNOSAPreview = async (formData) => {
        try {
            generateLoading.value = true
            const response = await nosaApi.generatePDF(formData)

            // Create blob URL for preview
            const blob = new Blob([response.data], { type: 'application/pdf' })
            const pdfUrl = window.URL.createObjectURL(blob)

            return {
                pdfUrl,
                blob,
                filename: `notice_of_salary_adjustment_${formData.employee}_${new Date().toISOString().split('T')[0]}.pdf`,
                employeeName: formData.employeeName || 'Employee'
            }
        } catch (e) {
            ElMessage.error('Failed to generate NOSA PDF: ' + (e.response?.data?.message || e.message))
            console.error('Generate NOSA error:', e)
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
        salarySchedules,
        generateLoading,
        fetchEmployeesAndSchedules,
        generateNOSAPreview,
        downloadPDFFromBlob
    }
}
