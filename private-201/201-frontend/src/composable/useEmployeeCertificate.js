import { ref, computed } from 'vue'
import { employeeCertificateApi } from '../services/api.js'
import { ElMessage } from 'element-plus'

export function useEmployeeCertificate() {
    // State
    const loading = ref(false)
    const generateLoading = ref(false)
    const employees = ref([])
    const selectedEmployeeData = ref(null)
    const formFields = ref({})

    // Computed
    const totalEmployees = computed(() => {
        return Array.isArray(employees.value) ? employees.value.length : 0
    })

    const certificatesReady = computed(() => {
        return selectedEmployeeData.value ? 1 : 0
    })

    const averageAnnualSalary = computed(() => {
        if (!Array.isArray(employees.value) || employees.value.length === 0) return 0
        const total = employees.value.reduce((sum, emp) => sum + (emp.salary * 12 || 0), 0)
        return Math.round(total / employees.value.length)
    })

    // Methods
    const fetchEmployees = async () => {
        loading.value = true
        try {
            console.log('Fetching employees for certificates...')
            const response = await employeeCertificateApi.getEmployees()
            console.log('Employee Certificate API response:', response)

            if (response.data && response.data.data) {
                employees.value = Array.isArray(response.data.data) ? response.data.data : []
                console.log('Certificate employees loaded:', employees.value.length)
            } else if (response.data && Array.isArray(response.data)) {
                employees.value = response.data
                console.log('Certificate employees loaded (direct):', employees.value.length)
            } else {
                employees.value = []
                console.warn('No employees data found in response')
            }
        } catch (error) {
            console.error('Failed to fetch employees for certificates:', error)
            ElMessage.error('Failed to load employees for certificates')
            employees.value = []
        } finally {
            loading.value = false
        }
    }

    const fetchCreateForm = async () => {
        try {
            console.log('Fetching certificate create form...')
            const response = await employeeCertificateApi.getCreateForm()
            console.log('Create form response:', response.data)

            if (response.data && response.data.data) {
                formFields.value = response.data.data.fields || {}
                // Also update employees if provided
                if (response.data.data.employees) {
                    employees.value = response.data.data.employees
                }
            }
        } catch (error) {
            console.error('Failed to fetch create form:', error)
            ElMessage.error('Failed to load form structure')
        }
    }

    const fetchEmployeeData = async (employeeId) => {
        if (!employeeId) return null

        try {
            console.log('Fetching employee certificate data for ID:', employeeId)
            const response = await employeeCertificateApi.getEmployeeData(employeeId)
            console.log('Employee data response:', response.data)

            if (response.data && response.data.data) {
                selectedEmployeeData.value = response.data.data
                return response.data.data
            }
        } catch (error) {
            console.error('Failed to fetch employee certificate data:', error)
            ElMessage.error('Failed to load employee certificate data')
        }
        return null
    }

    const generateCertificatePreview = async (formData) => {
        generateLoading.value = true
        try {
            console.log('Generating certificate preview with data:', formData)
            const response = await employeeCertificateApi.generatePDF(formData)

            // Create blob URL for preview
            const blob = new Blob([response.data], { type: 'application/pdf' })
            const url = URL.createObjectURL(blob)

            return url
        } catch (error) {
            console.error('Failed to generate Employee Certificate preview:', error)
            ElMessage.error('Failed to generate Employee Certificate preview')
            throw error
        } finally {
            generateLoading.value = false
        }
    }

    const downloadWord = async (formData) => {
        try {
            const response = await employeeCertificateApi.generateWord(formData)
            const blob = new Blob([response.data], { type: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' })
            return blob
        } catch (error) {
            console.error('Failed to generate Employee Certificate Word:', error)
            ElMessage.error('Failed to generate Employee Certificate Word')
            throw error
        }
    }

    const downloadExcelPlaceholder = async () => {
        ElMessage.warning('Excel export is not yet available for Employee Certificates')
        return null
    }

    const downloadPDFFromBlob = (blob, filename) => {
        const url = URL.createObjectURL(blob)
        const link = document.createElement('a')
        link.href = url
        link.download = filename
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
        URL.revokeObjectURL(url)
    }

    // Utility methods
    const formatCurrency = (amount) => {
        if (!amount) return '0.00'
        return new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(amount)
    }

    const formatDate = (dateString) => {
        if (!dateString) return 'N/A'
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        })
    }

    return {
        // State
        loading,
        generateLoading,
        employees,
        selectedEmployeeData,
        formFields,

        // Computed
        totalEmployees,
        certificatesReady,
        averageAnnualSalary,

        // Methods
        fetchEmployees,
        fetchCreateForm,
        fetchEmployeeData,
        generateCertificatePreview,
        downloadWord,
        downloadExcelPlaceholder,
        downloadPDFFromBlob,

        // Utilities
        formatCurrency,
        formatDate
    }
}
