import { ref, reactive } from 'vue'
import { useToast } from 'vue-toastification'
import ApiService from '../services/api.js'

export function usePayslip() {
  const toast = useToast()
  
  // Reactive state
  const state = reactive({
    loading: false,
    error: null,
    payslips: [],
    printLoading: false,
    printError: null,
    printing: false,
    downloading: false
  })

  // UI state
  const uiState = reactive({
    showDetailModal: false,
    showPrintPreview: false,
    selectedPayslip: null,
    selectedPayrollId: null,
    pdfUrl: null,
    pdfBlob: null
  })

  // Utility functions
  const getCurrentUserId = () => {
    const raw = localStorage.getItem('user_data')
    try { 
      const userData = raw ? JSON.parse(raw) : null
      return userData ? userData.id : null 
    } catch (error) { 
      return null 
    }
  }

  // API methods
  const loadPayslipData = async () => {
    try {
      state.loading = true
      state.error = null
      
      const userId = getCurrentUserId()
      
      if (!userId) throw new Error('No authenticated user')
      
      // Check authentication status first
      const authCheck = await ApiService.checkAuth()
      
      if (!authCheck) {
        throw new Error('User not authenticated. Please login again.')
      }
      
      const response = await ApiService.getPayslipList(userId)
      
      if (!response.success) throw new Error(response.message || 'Failed to load payslip data')
      
      state.payslips = response.data || []
    } catch (error) {
      state.error = error.message || 'Failed to load payslip data'
      toast.error('Failed to load payslip data')
      
      // If authentication failed, redirect to login
      if (error.message.includes('not authenticated') || error.message.includes('Authentication required')) {
        window.location.href = '/login'
      }
    } finally {
      state.loading = false
    }
  }

  // UI actions
  const viewPayslip = (payslip) => {
    uiState.selectedPayslip = payslip
    uiState.selectedPayrollId = payslip.id
    uiState.showDetailModal = true
  }

  const printPayslip = async (payslip) => {
    
    uiState.selectedPayslip = payslip
    uiState.selectedPayrollId = payslip.id
    uiState.showPrintPreview = true
    state.printLoading = true
    state.printError = null
    uiState.pdfUrl = null
    
    try {
      const blob = await ApiService.printPayslip(payslip.employee_id, payslip.id)
      uiState.pdfBlob = blob
      uiState.pdfUrl = URL.createObjectURL(blob)
      state.printLoading = false
    } catch (error) {
      state.printError = error.message || 'Failed to generate PDF'
      state.printLoading = false
    }
  }

  const closeDetailModal = () => {
    uiState.showDetailModal = false
    uiState.selectedPayslip = null
    uiState.selectedPayrollId = null
  }

  const closePrintPreview = () => {
    uiState.showPrintPreview = false
    uiState.selectedPayslip = null
    uiState.selectedPayrollId = null
    state.printLoading = false
    state.printError = null
    state.printing = false
    state.downloading = false
    
    // Clean up PDF URL
    if (uiState.pdfUrl) {
      URL.revokeObjectURL(uiState.pdfUrl)
      uiState.pdfUrl = null
      uiState.pdfBlob = null
    }
  }

  const printPDF = () => {
    if (uiState.pdfBlob) {
      state.printing = true
      
      const printWindow = window.open('', '_blank')
      const objectUrl = URL.createObjectURL(uiState.pdfBlob)
      
      printWindow.document.write(`
        <html>
          <head>
            <title>Payslip - ${uiState.selectedPayslip?.payroll_interval || 'Print'}</title>
          </head>
          <body style="margin: 0; padding: 0;">
            <embed src="${objectUrl}" type="application/pdf" width="100%" height="100%" />
          </body>
        </html>
      `)
      
      printWindow.document.close()
      printWindow.focus()
      
      // Clean up after printing
      setTimeout(() => {
        URL.revokeObjectURL(objectUrl)
        state.printing = false
      }, 1000)
    }
  }

  const downloadPDF = () => {
    if (uiState.pdfBlob) {
      state.downloading = true
      
      const link = document.createElement('a')
      link.href = URL.createObjectURL(uiState.pdfBlob)
      link.download = `payslip-${uiState.selectedPayslip?.payroll_interval || 'document'}.pdf`
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
      
      // Clean up URL
      setTimeout(() => {
        URL.revokeObjectURL(link.href)
        state.downloading = false
      }, 100)
    }
  }

  const formatDate = (dateString) => {
    if (!dateString) return 'Not available'
    const date = new Date(dateString)
    if (isNaN(date.getTime())) return 'Invalid date'
    return date.toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    })
  }

  return {
    // State
    state,
    uiState,
    
    // Methods
    loadPayslipData,
    viewPayslip,
    printPayslip,
    closeDetailModal,
    closePrintPreview,
    printPDF,
    downloadPDF,
    formatDate,
    
    // Utilities
    getCurrentUserId
  }
}
