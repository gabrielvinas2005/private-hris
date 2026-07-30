import { ref } from 'vue'
import { certOfLastSalaryApi } from '../services/api.js'
import { ElMessage } from 'element-plus'

export function useCertOfLastSalary() {
    const loading = ref(false)
    const generateLoading = ref(false)
    const employees = ref([])
    const employeeDetails = ref(null)

    const fetchEmployees = async () => {
        loading.value = true
        try {
            const res = await certOfLastSalaryApi.getEmployees()
            employees.value = res.data?.data || []
            return employees.value
        } catch (e) {
            const errorMessage = e?.response?.data?.message || e?.response?.data?.error || 'Failed to fetch employees'
            ElMessage.error(errorMessage)
            throw e
        } finally {
            loading.value = false
        }
    }

    const fetchEmployeeDetails = async (id) => {
        loading.value = true
        try {
            const res = await certOfLastSalaryApi.getEmployeeDetails(id)
            employeeDetails.value = res.data?.data || null
            return employeeDetails.value
        } catch (e) {
            const errorMessage = e?.response?.data?.message || e?.response?.data?.error || 'Failed to fetch employee details'
            ElMessage.error(errorMessage)
            throw e
        } finally {
            loading.value = false
        }
    }

    const generateCertificate = async (form) => {
        generateLoading.value = true
        try {
            const res = await certOfLastSalaryApi.generatePDF(form)
            const blob = new Blob([res.data], { type: 'application/pdf' })
            return URL.createObjectURL(blob)
        } catch (e) {
            try {
                if (e?.response?.data instanceof Blob) {
                    const text = await e.response.data.text()
                    ElMessage.error(text.slice(0, 300) || 'Server error while generating certificate of last salary')
                } else {
                    ElMessage.error('Failed to generate certificate of last salary')
                }
            } catch {
                ElMessage.error('Failed to generate certificate of last salary')
            }
            throw e
        } finally {
            generateLoading.value = false
        }
    }

    const downloadPDFFromBlob = (blob, filename) => {
        const url = URL.createObjectURL(blob)
        const a = document.createElement('a')
        a.href = url
        a.download = filename
        document.body.appendChild(a)
        a.click()
        document.body.removeChild(a)
        URL.revokeObjectURL(url)
    }

    return {
        loading,
        generateLoading,
        employees,
        employeeDetails,
        fetchEmployees,
        fetchEmployeeDetails,
        generateCertificate,
        downloadPDFFromBlob
    }
}

