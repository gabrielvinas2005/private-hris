import { ref } from 'vue'
import { ElMessage } from 'element-plus'
import { renderedServiceCertificateApi } from '../services/api'

export function useRenderedService() {
  const loading = ref(false)
  const generateLoading = ref(false)
  const employees = ref([])

  const fetchEmployees = async () => {
    loading.value = true
    try {
      const { data } = await renderedServiceCertificateApi.getEmployees()
      employees.value = data?.data || []
    } catch (error) {
      console.error('Failed to load employees for rendered service', error)
      ElMessage.error('Failed to load employees')
    } finally {
      loading.value = false
    }
  }

  const generateCertificate = async (payload, { manageLoading = true } = {}) => {
    if (manageLoading) {
      generateLoading.value = true
    }
    try {
      const response = await renderedServiceCertificateApi.generatePDF(payload)
      const blob = new Blob([response.data], { type: 'application/pdf' })
      return URL.createObjectURL(blob)
    } catch (error) {
      console.error('Failed to generate rendered service certificate', error)
      ElMessage.error('Failed to generate certificate')
      throw error
    } finally {
      if (manageLoading) {
        generateLoading.value = false
      }
    }
  }

  const downloadPDFFromBlob = (blob, filename) => {
    const link = document.createElement('a')
    link.href = URL.createObjectURL(blob)
    link.download = filename
    link.click()
    URL.revokeObjectURL(link.href)
  }

  return {
    loading,
    generateLoading,
    employees,
    fetchEmployees,
    generateCertificate,
    downloadPDFFromBlob
  }
}

