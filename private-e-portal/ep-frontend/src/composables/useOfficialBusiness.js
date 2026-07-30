import { ref, reactive, computed } from 'vue'
import { useToast } from 'vue-toastification'
import ApiService from '../services/api.js'

export function useOfficialBusiness() {
  const toast = useToast()
  
  // Reactive state
  const state = reactive({
    activeEmployeeTab: 'pending',
    activeApproverTab: 'pending',
    allowed: true,
    isSupervisor: false,
    pendingOB: [],
    approvedOB: [],
    disapprovedOB: [],
    cancelledOB: [],
    pendingApprovals: [],
    approvedByApprover: [],
    disapprovedByApprover: [],
    cancelledByApprover: [],
    search: '',
    statusFilter: ''
  })

  // UI state
  const uiState = reactive({
    showFormModal: false,
    showCancelModal: false,
    showRequestPickupModal: false,
    showPrintModal: false,
    showDetailDrawer: false,
    selectedOB: null,
    selectedOBForDetail: null,
    formType: 'official',
    previewUrl: ''
  })

  // Tab configurations
  const employeeTabs = [
    { id: 'pending', name: 'Pending' },
    { id: 'approved', name: 'Approved' },
    { id: 'disapproved', name: 'Disapproved' },
    { id: 'cancelled', name: 'Cancelled' }
  ]

  const approverTabs = [
    { id: 'pending', name: 'Pending' },
    { id: 'approved', name: 'Approved' },
    { id: 'disapproved', name: 'Disapproved' },
    { id: 'cancelled', name: 'Cancelled' }
  ]

  // Computed properties
  const filteredOB = computed(() => {
    let base = state.pendingOB
    const s = (state.statusFilter || '').toLowerCase()
    if (s === 'approved') base = state.approvedOB
    else if (s === 'disapproved') base = state.disapprovedOB
    else if (s === 'cancelled') base = state.cancelledOB
    else base = state.pendingOB

    if (state.search && state.search.trim()) {
      const q = state.search.trim().toLowerCase()
      base = base.filter(ob => String(ob.client || '').toLowerCase().includes(q) || String(ob.purpose || '').toLowerCase().includes(q))
    }
    return base
  })

  // Utility functions
  const getCurrentUserId = () => {
    const raw = localStorage.getItem('user_data')
    try { return raw ? JSON.parse(raw).id : null } catch { return null }
  }

  const getFormType = (obType) => {
    // Convert to number to handle both string and integer values
    const type = parseInt(obType)
    switch (type) {
      case 1: return 'official'
      case 2: return 'travel_authority'
      case 3: return 'travel_order'
      default: return 'official'
    }
  }

  // API methods
  const loadOBData = async () => {
    try {
      const userId = getCurrentUserId()
      if (!userId) throw new Error('No authenticated user')
      
      const res = await ApiService.getOfficialBusiness(userId)
      if (!res.success) throw new Error(res.message || 'Failed to load')
      
      const d = res.data

      state.allowed = !!d.allowed
      // employee tabs
      state.pendingOB = d.PendingOB || []
      state.approvedOB = d.ApprovedOB || []
      state.disapprovedOB = d.DisapprovedOB || []
      state.cancelledOB = d.CancelledOB || []

      // approver view
      state.pendingApprovals = d.ForapprovalEmployeeOB || []
      state.approvedByApprover = d.ApprovedEmployeeOB || []
      state.disapprovedByApprover = d.DisapprovedEmployeeOB || []
      state.cancelledByApprover = d.CancelledEmployeeOB || []
      state.isSupervisor = state.pendingApprovals.length > 0 || state.approvedByApprover.length > 0 || state.disapprovedByApprover.length > 0
    } catch (error) {
      console.error('Error loading OB data:', error)
      toast.error('Failed to load OB data')
    }
  }

  const deleteOB = async (ob) => {
    try {
      await ApiService.deleteOfficialBusiness(ob.id)
      toast.success('OB application deleted successfully')
      await loadOBData()
    } catch (error) {
      console.error('Error deleting OB:', error)
      toast.error('Failed to delete OB application')
    }
  }

  const approveOB = async (ob) => {
    try {
      await ApiService.approveOfficialBusiness(ob.id, 'Approved via portal')
      toast.success('OB application approved successfully')
      await loadOBData()
    } catch (error) {
      console.error('Error approving OB:', error)
      toast.error('Failed to approve OB application')
    }
  }

  const disapproveOB = async (ob) => {
    try {
      await ApiService.disapproveOfficialBusiness(ob.id, 'Disapproved via portal')
      toast.success('OB application disapproved')
      await loadOBData()
    } catch (error) {
      console.error('Error disapproving OB:', error)
      toast.error('Failed to disapprove OB application')
    }
  }

  const handleCancelSubmit = async (data) => {
    try {
      await ApiService.cancelOfficialBusiness(uiState.selectedOB.id, data?.remarks || 'Cancelled')
      toast.success('OB application cancelled successfully')
      closeCancelModal()
      await loadOBData()
    } catch (error) {
      console.error('Error cancelling OB:', error)
      toast.error('Failed to cancel OB application')
    }
  }

  const handleRequestPickupSubmit = async (formData) => {
    try {
      // Convert plain object to FormData
      const data = new FormData()
      
      // Add all form fields
      Object.keys(formData).forEach(key => {
        if (key === 'requested_by_display') return // display-only field
        const value = formData[key]
        // Skip empty strings so backend can treat them as nulls
        if (value === '' || value === null || value === undefined) return
        data.append(key, value)
      })
      
      // Add required fields for official business application
      const raw = localStorage.getItem('user_data')
      const userId = raw ? JSON.parse(raw).id : null
      if (userId) data.append('employee_id', userId)
      
      // Set ob_type to 4 (Request for Pick-up)
      data.append('ob_type', '4')
      data.append('official_business_id', '0') // New record
      
      // Call the API
      const response = await ApiService.storeOfficialBusiness(data)
      
      if (response.success) {
        toast.success('Request for pickup submitted successfully')
        closeRequestPickupModal()
        await loadOBData() // Reload the OB list
      } else {
        throw new Error(response.message || 'Failed to submit request for pickup')
      }
    } catch (error) {
      console.error('Error submitting request pickup:', error)
      toast.error(error.message || 'Failed to submit request for pickup')
    }
  }

  const handleOBSubmit = async (formData) => {
    try {
      // Ensure formData is actually a FormData object
      if (!(formData instanceof FormData)) {
        console.error('Invalid formData received:', formData)
        toast.error('Invalid form data received')
        return
      }

      const raw = localStorage.getItem('user_data')
      const userId = raw ? JSON.parse(raw).id : null
      if (userId) formData.append('employee_id', userId)
      // Controller expects 'official_business_id' for upsert
      formData.append('official_business_id', uiState.selectedOB ? uiState.selectedOB.id : 0)
      
      if (uiState.formType === 'travel_authority') {
        await ApiService.storeUnofficialBusiness(formData)
      } else {
        await ApiService.storeOfficialBusiness(formData)
      }
      
      // Show success toast
      toast.success(uiState.selectedOB ? 'OB application updated successfully' : 'OB application created successfully')
      
      closeFormModal()
      await loadOBData()
    } catch (error) {
      console.error('Error submitting OB:', error)

      // Extract a clean message (avoid showing raw JSON)
      let rawMessage = error?.message || ''
      let displayMessage = 'Failed to submit OB application.'

      if (rawMessage) {
        // If our ApiService wrapped the message like: "HTTP error! status: 400 - ..."
        const parts = rawMessage.split(' - ')
        const lastPart = parts[parts.length - 1] || ''

        // Try to parse JSON payload: {"error":"..."} or {"message":"..."}
        let parsed = null
        try {
          parsed = JSON.parse(lastPart)
        } catch (_) {
          // not JSON, use as plain text
        }

        if (parsed && (parsed.error || parsed.message)) {
          displayMessage = parsed.error || parsed.message
        } else if (lastPart) {
          displayMessage = lastPart
        } else {
          displayMessage = rawMessage
        }
      }

      toast.error(`Failed to submit OB application: ${displayMessage}`)
    }
  }

  const printOB = async (ob) => {
    const obType = parseInt(ob.ob_type)
    // For ALL Travel Authority (ob_type = 2), use the backend TA reports
    // (personal vs annex F decided by type_id inside the backend).
    const unofficial = obType === 2
    // Show inline preview below table
    uiState.selectedOB = ob
    uiState.showPrintModal = true
    // Fetch PDF blob URL and assign to previewUrl
    try {
      let url
      if (obType === 2) {
        // Travel Authority (personal / annex F handled in backend)
        url = await ApiService.printOfficialBusiness(ob.id, true)
      } else if (obType === 3) {
        // Travel Order (Annex A)
        url = await ApiService.printTravelOrder(ob.id)
      } else if (obType === 4) {
        // Request for Pick-up
        url = await ApiService.printRequestPickup(ob.id)
      } else {
        // Regular Official Business
        url = await ApiService.printOfficialBusiness(ob.id, false)
      }
      uiState.previewUrl = url
    } catch (error) {
      toast.error('Failed to load print preview')
    }
  }

  // UI actions
  const addOfficialBusiness = () => {
    uiState.selectedOB = null
    uiState.formType = 'official'
    uiState.showFormModal = true
  }

  const addTravelAuthority = () => {
    uiState.selectedOB = null
    uiState.formType = 'travel_authority'
    uiState.showFormModal = true
  }

  const addTravelOrder = () => {
    uiState.selectedOB = null
    uiState.formType = 'travel_order'
    uiState.showFormModal = true
  }

  const editOB = (ob) => {
    uiState.selectedOB = ob
    uiState.formType = getFormType(ob.ob_type)
    uiState.showFormModal = true
  }

  const cancelOB = (ob) => {
    uiState.selectedOB = ob
    uiState.showCancelModal = true
  }

  const closeFormModal = () => {
    uiState.showFormModal = false
    uiState.selectedOB = null
  }

  const closeCancelModal = () => {
    uiState.showCancelModal = false
    uiState.selectedOB = null
  }

  const openRequestPickupModal = () => {
    uiState.showRequestPickupModal = true
  }

  const closeRequestPickupModal = () => {
    uiState.showRequestPickupModal = false
  }

  const closePreview = () => {
    uiState.showPrintModal = false
    if (uiState.previewUrl) {
      URL.revokeObjectURL(uiState.previewUrl)
      uiState.previewUrl = ''
    }
  }

  const viewOBDetail = (ob) => {
    uiState.selectedOBForDetail = ob
    uiState.showDetailDrawer = true
  }

  const closeDetailDrawer = () => {
    uiState.showDetailDrawer = false
    uiState.selectedOBForDetail = null
  }

  const downloadPdf = () => {
    if (!uiState.previewUrl) return
    const a = document.createElement('a')
    a.href = uiState.previewUrl
    a.download = `ob-${uiState.selectedOB?.id || 'document'}.pdf`
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
  }

  const downloadWord = async () => {
    if (!uiState.selectedOB) return
    const obType = parseInt(uiState.selectedOB.ob_type)
    const typeId = parseInt(uiState.selectedOB.type_id || 0)
    const branch = uiState.selectedOB.branch || ''
    
    // Word download is available for:
    // - Request for Pick-up (ob_type = 4)
    // - Travel Authority Annex F (ob_type = 2, type_id = 2)
    // - Travel Authority Personal (ob_type = 2, type_id = 1)
    // - Travel Order LDSD (ob_type = 3, and (branch contains LDSD or type_id = 2))
    // - Pass Slip / Official Business (ob_type = 1 or 5)
    if (obType === 4) {
      try {
        await ApiService.downloadRequestPickupWord(uiState.selectedOB.id)
      } catch (error) {
        console.error('Failed to download Word document:', error)
        toast.error('Failed to download Word document')
      }
    } else if (obType === 2 && typeId === 2) {
      // Travel Authority Annex F
      try {
        await ApiService.downloadTravelAuthorityWord(uiState.selectedOB.id)
      } catch (error) {
        console.error('Failed to download Travel Authority Word document:', error)
        toast.error('Failed to download Travel Authority Word document')
      }
    } else if (obType === 2 && typeId === 1) {
      // Travel Authority Personal
      try {
        await ApiService.downloadTravelAuthorityPersonalWord(uiState.selectedOB.id)
      } catch (error) {
        console.error('Failed to download Travel Authority Personal Word document:', error)
        toast.error('Failed to download Travel Authority Personal Word document')
      }
    } else if (obType === 3 && (typeId === 2 || branch.includes('Learning and Development Support Division') || branch.includes('LDSD'))) {
      // Travel Order LDSD
      try {
        await ApiService.downloadTravelOrderLDSDWord(uiState.selectedOB.id)
      } catch (error) {
        console.error('Failed to download Travel Order LDSD Word document:', error)
        toast.error('Failed to download Travel Order LDSD Word document')
      }
    } else if (obType === 1 || obType === 5) {
      // Pass Slip / Official Business
      try {
        await ApiService.downloadOfficialBusinessWord(uiState.selectedOB.id)
      } catch (error) {
        console.error('Failed to download Pass Slip Word document:', error)
        toast.error('Failed to download Pass Slip Word document')
      }
    } else {
      toast.error('Word download is not available for this type of transaction.')
      return
    }
  }

  const downloadExcel = async () => {
    if (!uiState.selectedOB) return
    const obType = parseInt(uiState.selectedOB.ob_type)
    // Excel download is available for:
    // - Request for Pick-up (ob_type = 4)
    // - Pass Slip / Official Business (ob_type = 1 or 5)
    if (obType === 4) {
      try {
        await ApiService.downloadRequestPickupExcel(uiState.selectedOB.id)
      } catch (error) {
        console.error('Failed to download Excel document:', error)
        toast.error('Failed to download Excel document')
      }
    } else if (obType === 1 || obType === 5) {
      // Pass Slip / Official Business
      try {
        await ApiService.downloadOfficialBusinessExcel(uiState.selectedOB.id)
      } catch (error) {
        console.error('Failed to download Pass Slip Excel document:', error)
        toast.error('Failed to download Pass Slip Excel document')
      }
    } else {
      toast.error('Excel download is not available for this type of transaction.')
      return
    }
  }

  return {
    // State
    state,
    uiState,
    employeeTabs,
    approverTabs,
    
    // Computed
    filteredOB,
    
    // Methods
    loadOBData,
    deleteOB,
    approveOB,
    disapproveOB,
    handleCancelSubmit,
    handleOBSubmit,
    handleRequestPickupSubmit,
    printOB,
    
    // UI Actions
    addOfficialBusiness,
    addTravelAuthority,
    addTravelOrder,
    editOB,
    cancelOB,
    closeFormModal,
    closeCancelModal,
    openRequestPickupModal,
    closeRequestPickupModal,
    closePreview,
    viewOBDetail,
    closeDetailDrawer,
    
    // Utilities
    getCurrentUserId,
    getFormType,

    // Downloads
    downloadPdf,
    downloadWord,
    downloadExcel
  }
}
