import { ref } from 'vue'
import { ElMessage } from 'element-plus'
import { salaryDeductionCertificateApi } from '../services/api'

export function useCertOfSalaryDeduction() {
  const loading = ref(false)
  const generateLoading = ref(false)
  const employees = ref([])

  const fetchEmployees = async () => {
    loading.value = true
    try {
      const { data } = await salaryDeductionCertificateApi.getEmployees()
      employees.value = data?.data || []
    } catch (error) {
      console.error('Failed to load employees for salary deduction certificate', error)
      ElMessage.error('Failed to load employees')
    } finally {
      loading.value = false
    }
  }

  const generateCertificate = async (payload) => {
    generateLoading.value = true
    try {
      const response = await salaryDeductionCertificateApi.generatePDF(payload)
      const blob = new Blob([response.data], { type: 'application/pdf' })
      return URL.createObjectURL(blob)
    } catch (error) {
      console.error('Failed to generate salary deduction certificate', error)
      ElMessage.error('Failed to generate certificate')
      throw error
    } finally {
      generateLoading.value = false
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
    downloadPDFFromBlob,
  }
}

