import { ref, reactive } from 'vue'
import { ElMessage } from 'element-plus'
import { oathOfOfficeApi } from '../services/api.js'

export function useOathOfOffice() {
    const loading = ref(false)
    const generateLoading = ref(false)
    const employees = ref([])

    // Form data
    const formData = reactive({
        employee: null,
        signatory: '',
        position: ''
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
        ]
    }

    // Fetch employees for oath of office
    const fetchEmployees = async () => {
        try {
            loading.value = true
            const response = await oathOfOfficeApi.getEmployees()

            console.log('Oath of Office API Response:', response.data)

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

    // Generate Oath of Office PDF
    const generateOathPdf = async (data) => {
        try {
            generateLoading.value = true
            const response = await oathOfOfficeApi.generatePDF(data)

            console.log('PDF Response Data Type:', typeof response.data)
            console.log('Is Blob:', response.data instanceof Blob)

            // Return the blob directly
            return response.data
        } catch (error) {
            console.error('Error generating oath of office PDF:', error)

            // Try to decode error message from blob response
            if (error.response?.data instanceof Blob) {
                try {
                    const errorText = await error.response.data.text()
                    const errorData = JSON.parse(errorText)
                    ElMessage.error(errorData.message || 'Failed to generate oath of office PDF')
                } catch (parseError) {
                    ElMessage.error('Failed to generate oath of office PDF')
                }
            } else {
                ElMessage.error(error.response?.data?.message || 'Failed to generate oath of office PDF')
            }
            throw error
        } finally {
            generateLoading.value = false
        }
    }

    // Generate Oath of Office Word Document
    const downloadWord = async (data) => {
        try {
            generateLoading.value = true
            const response = await oathOfOfficeApi.generateWord(data)

            // Return the blob directly
            return response.data
        } catch (error) {
            console.error('Error generating oath of office Word:', error)

            // Try to decode error message from blob response
            if (error.response?.data instanceof Blob) {
                try {
                    const errorText = await error.response.data.text()
                    const errorData = JSON.parse(errorText)
                    ElMessage.error(errorData.message || 'Failed to generate oath of office Word')
                } catch (parseError) {
                    ElMessage.error('Failed to generate oath of office Word')
                }
            } else {
                ElMessage.error(error.response?.data?.message || 'Failed to generate oath of office Word')
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
        link.download = filename || 'oath_of_office.pdf'
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
            position: ''
        })
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
        generateOathPdf,
        downloadWord,
        downloadPDFFromBlob,
        resetForm
    }
}
