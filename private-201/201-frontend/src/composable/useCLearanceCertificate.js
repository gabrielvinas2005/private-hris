import { ref } from 'vue'
import api from '../services/api'

export function useClearanceCertificate() {
  const loading = ref(false)
  const generateLoading = ref(false)
  const employees = ref([])

  const fetchEmployees = async () => {
    loading.value = true
    try {
      const response = await api.get('/employees')
      employees.value = response.data.data || []
    } catch (error) {
      console.error('Error fetching employees:', error)
    } finally {
      loading.value = false
    }
  }


  const generateClearancePdf = async (formData) => {
    generateLoading.value = true
    try {
      const response = await api.post('/clearance-certificates/print', formData, {
        responseType: 'blob'
      })
      return URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }))
    } catch (error) {
      console.error('Error generating PDF:', error)
      throw error
    } finally {
      generateLoading.value = false
    }
  }

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
    generateLoading,
    employees,
    fetchEmployees,
    generateClearancePdf,
    downloadPDFFromBlob
  }
}