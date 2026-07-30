import { ref, computed } from 'vue'
import { terminalLeaveEndorsementApi } from '../services/api.js'
import { ElMessage } from 'element-plus'

export function useTerminalLeaveEndorsement() {
    // State
    const loading = ref(false)
    const generateLoading = ref(false)
    const employees = ref([])
    const endorsementData = ref(null)

    // Computed
    const totalEmployees = computed(() => {
        return Array.isArray(employees.value) ? employees.value.length : 0
    })

    // Methods
    const fetchEmployees = async () => {
        loading.value = true
        try {
            const response = await terminalLeaveEndorsementApi.getEmployees()
            console.log('Terminal Leave Endorsement API response:', response.data)

            if (response.data && response.data.data) {
                employees.value = Array.isArray(response.data.data) ? response.data.data : []
                console.log('Terminal Leave Endorsement employees loaded:', employees.value.length)
            } else {
                employees.value = []
                console.warn('No employees data found in response')
            }
        } catch (error) {
            console.error('Failed to fetch employees for terminal leave endorsement:', error)
            ElMessage.error('Failed to load employees for terminal leave endorsement')
            employees.value = []
        } finally {
            loading.value = false
        }
    }

    const fetchEndorsementData = async (employeeId) => {
        if (!employeeId) return null

        try {
            const response = await terminalLeaveEndorsementApi.getEndorsementData(employeeId)
            console.log('Endorsement data response:', response.data)

            if (response.data && response.data.data) {
                endorsementData.value = response.data.data
                return response.data.data
            }
        } catch (error) {
            console.error('Failed to fetch endorsement data:', error)
            ElMessage.error('Failed to load endorsement data')
        }
        return null
    }

    const generateTLEPreview = async (formData) => {
        generateLoading.value = true
        try {
            const response = await terminalLeaveEndorsementApi.generatePDF(formData)

            // Create blob URL for preview
            const blob = new Blob([response.data], { type: 'application/pdf' })
            const url = URL.createObjectURL(blob)

            return url
        } catch (error) {
            console.error('Failed to generate Terminal Leave Endorsement preview:', error)
            ElMessage.error('Failed to generate Terminal Leave Endorsement preview')
            throw error
        } finally {
            generateLoading.value = false
        }
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

    return {
        // State
        loading,
        generateLoading,
        employees,
        endorsementData,

        // Computed
        totalEmployees,

        // Methods
        fetchEmployees,
        fetchEndorsementData,
        generateTLEPreview,
        downloadPDFFromBlob
    }
}
