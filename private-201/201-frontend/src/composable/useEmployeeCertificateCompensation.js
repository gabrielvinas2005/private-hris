import { ref, computed } from 'vue'
import { employeeCompensationCertificateApi } from '../services/api.js'
import { ElMessage } from 'element-plus'

export function useEmployeeCertificateCompensation() {
    const loading = ref(false)
    const generateLoading = ref(false)
    const employees = ref([])
    const selectedEmployeeData = ref(null)
    const formFields = ref({})

    const totalEmployees = computed(() => Array.isArray(employees.value) ? employees.value.length : 0)

    const fetchEmployees = async () => {
        loading.value = true
        try {
            const res = await employeeCompensationCertificateApi.getEmployees()
            if (res.data?.data) employees.value = res.data.data
            else if (Array.isArray(res.data)) employees.value = res.data
            else employees.value = []
        } catch (e) {
            console.error(e)
            ElMessage.error('Failed to load employees for compensation certificate')
            employees.value = []
        } finally {
            loading.value = false
        }
    }

    const fetchCreateForm = async () => {
        try {
            const res = await employeeCompensationCertificateApi.getCreateForm()
            if (res.data?.data) {
                formFields.value = res.data.data.fields || {}
                if (res.data.data.employees) employees.value = res.data.data.employees
            }
        } catch (e) {
            console.error(e)
        }
    }

    const fetchEmployeeData = async (employeeId) => {
        try {
            const res = await employeeCompensationCertificateApi.getEmployeeData(employeeId)
            if (res.data?.data) selectedEmployeeData.value = res.data.data
        } catch (e) {
            console.error(e)
            ElMessage.error('Failed to load compensation data')
        }
    }

    const generateCertificatePreview = async (form) => {
        generateLoading.value = true
        try {
            const res = await employeeCompensationCertificateApi.generatePDF(form)
            const blob = new Blob([res.data], { type: 'application/pdf' })
            return URL.createObjectURL(blob)
        } catch (e) {
            try {
                // Attempt to decode backend error body for easier debugging
                if (e?.response?.data instanceof Blob) {
                    const text = await e.response.data.text()
                    console.error('Server error (decoded):', text)
                    ElMessage.error(text?.slice(0, 300) || 'Server error while generating certificate')
                } else {
                    console.error(e)
                    ElMessage.error(e?.response?.data?.message || 'Failed to generate compensation certificate')
                }
            } catch (inner) {
                console.error('Failed to decode error body', inner)
                ElMessage.error('Failed to generate compensation certificate')
            }
            throw e
        } finally {
            generateLoading.value = false
        }
    }

    const downloadWord = async (formData) => {
        try {
            const response = await employeeCompensationCertificateApi.generateWord(formData)
            const blob = new Blob([response.data], { type: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' })
            return blob
        } catch (error) {
            console.error('Failed to generate Compensation Certificate Word:', error)
            ElMessage.error('Failed to generate Compensation Certificate Word')
            throw error
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

    const formatCurrency = (val) => new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(val || 0)

    return {
        // state
        loading,
        generateLoading,
        employees,
        selectedEmployeeData,
        formFields,
        // computed
        totalEmployees,
        // methods
        fetchEmployees,
        fetchCreateForm,
        fetchEmployeeData,
        generateCertificatePreview,
        downloadWord,
        downloadPDFFromBlob,
        // utils
        formatCurrency
    }
}


