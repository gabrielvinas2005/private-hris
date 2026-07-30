import { ref, computed } from 'vue'
import { employeeMedicalCertificateApi } from '../services/api.js'
import { ElMessage } from 'element-plus'

export function useEmployeeMedicalCertificate() {
    const loading = ref(false)
    const generateLoading = ref(false)
    const employees = ref([])
    const employeeDetails = ref(null)

    const totalEmployees = computed(() => Array.isArray(employees.value) ? employees.value.length : 0)

    const fetchEmployees = async () => {
        loading.value = true
        try {
            const res = await employeeMedicalCertificateApi.getEmployees()
            employees.value = res.data?.data ?? []
        } catch (e) {
            console.error(e)
            ElMessage.error('Failed to load employees for medical certificate')
            employees.value = []
        } finally {
            loading.value = false
        }
    }

    const fetchCreateForm = async () => {
        try {
            const res = await employeeMedicalCertificateApi.getCreateForm()
            if (res.data?.data?.employees) employees.value = res.data.data.employees
        } catch (e) {
            console.error(e)
        }
    }

    const fetchEmployeeData = async (employeeId) => {
        try {
            const res = await employeeMedicalCertificateApi.getEmployeeData(employeeId)
            employeeDetails.value = res.data?.data ?? null
        } catch (e) {
            console.error(e)
            ElMessage.error('Failed to load employee details')
        }
    }

    const generateMedicalCertificate = async (form) => {
        generateLoading.value = true
        try {
            const res = await employeeMedicalCertificateApi.generatePDF(form)
            const blob = new Blob([res.data], { type: 'application/pdf' })
            return URL.createObjectURL(blob)
        } catch (e) {
            try {
                if (e?.response?.data instanceof Blob) {
                    const text = await e.response.data.text()
                    ElMessage.error(text.slice(0, 300) || 'Server error while generating medical certificate')
                } else {
                    ElMessage.error('Failed to generate medical certificate')
                }
            } catch {
                ElMessage.error('Failed to generate medical certificate')
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
        totalEmployees,
        fetchEmployees,
        fetchCreateForm,
        fetchEmployeeData,
        generateMedicalCertificate,
        downloadPDFFromBlob
    }
}


