import { ref, reactive } from 'vue'
import { ElMessage } from 'element-plus'
import { assumptionOfDutyApi } from '../services/api.js'

export function useAssumptionOfDuty() {
    const loading = ref(false)
    const generateLoading = ref(false)
    const employees = ref([])

    // Form data
    const formData = reactive({
        employee: null,
        signatory: '',
        position: '',
        assested_date: null,
        assested_signatory: '',
        assested_position: ''
    })

    // Form validation rules
    const rules = {
        employee: [
            { required: true, message: 'Please select an employee', trigger: 'change' }
        ],
        signatory: [
            { required: true, message: 'Please enter signatory name', trigger: 'blur' }
        ],
        position: [
            { required: true, message: 'Please enter signatory position', trigger: 'blur' }
        ],
        assested_date: [
            { required: true, message: 'Please select assessed date', trigger: 'change' }
        ],
        assested_signatory: [
            { required: true, message: 'Please enter assessed signatory', trigger: 'blur' }
        ],
        assested_position: [
            { required: true, message: 'Please enter assessed position', trigger: 'blur' }
        ]
    }

    // Fetch employees for assumption of duty
    const fetchEmployees = async () => {
        try {
            loading.value = true
            const response = await assumptionOfDutyApi.getEmployees()

            console.log('Assumption of Duty API Response:', response.data)

            // Handle paginated response structure: response.data.data.data
            if (response.data && response.data.data && response.data.data.data) {
                employees.value = response.data.data.data || []
            } else if (response.data && response.data.data && Array.isArray(response.data.data)) {
                employees.value = response.data.data || []
            } else if (response.data && Array.isArray(response.data)) {
                employees.value = response.data
            } else {
                employees.value = []
            }
        } catch (error) {
            console.error('Error fetching employees:', error)
            ElMessage.error('Failed to fetch employees')
            employees.value = []
        } finally {
            loading.value = false
        }
    }

    // Generate Assumption of Duty PDF
    const generateAssumptionPdf = async (data) => {
        try {
            generateLoading.value = true
            const response = await assumptionOfDutyApi.generatePDF(data)

            console.log('PDF Response Data Type:', typeof response.data)
            console.log('Is Blob:', response.data instanceof Blob)

            // Return the blob directly
            return response.data
        } catch (error) {
            console.error('Error generating assumption of duty PDF:', error)

            // Try to decode error message from blob response
            if (error.response?.data instanceof Blob) {
                try {
                    const errorText = await error.response.data.text()
                    const errorData = JSON.parse(errorText)
                    ElMessage.error(errorData.message || 'Failed to generate assumption of duty PDF')
                } catch (parseError) {
                    ElMessage.error('Failed to generate assumption of duty PDF')
                }
            } else {
                ElMessage.error(error.response?.data?.message || 'Failed to generate assumption of duty PDF')
            }
            throw error
        } finally {
            generateLoading.value = false
        }
    }

    // Download PDF from blob
    const downloadPDFFromBlob = (blob, filename) => {
        const url = window.URL.createObjectURL(blob)
        const link = document.createElement('a')
        link.href = url
        link.download = filename || 'assumption_of_duty.pdf'
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
        window.URL.revokeObjectURL(url)
    }

    // Reset form
    const resetForm = () => {
        Object.assign(formData, {
            employee: null,
            signatory: '',
            position: '',
            assested_date: null,
            assested_signatory: '',
            assested_position: ''
        })
    }

    const sendEmail = async (data) => {
        try {
            generateLoading.value = true
            const response = await assumptionOfDutyApi.sendEmail(data)
            ElMessage.success(response.data?.message || 'Assumption of duty email sent successfully')
            return response.data?.data ?? null
        } catch (error) {
            console.error('Error sending assumption of duty email:', error)
            ElMessage.error(error.response?.data?.message || 'Failed to send assumption of duty email')
            throw error
        } finally {
            generateLoading.value = false
        }
    }

    return {
        // State
        loading,
        generateLoading,
        employees,
        formData,
        rules,

        // Methods
        fetchEmployees,
        generateAssumptionPdf,
        downloadPDFFromBlob,
        resetForm,
        sendEmail
    }
}
