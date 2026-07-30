import { ref, reactive } from 'vue'
import { useToast } from 'vue-toastification'
import ApiService from '../services/api.js'

export function useOvertime() {
  const toast = useToast()
  
  // Reactive state
  const state = reactive({
    employeeInfo: {
      id: 0,
      name: '',
      employee_no: ''
    },
    allowed: false,
    overtimeTaxCode: [],
    serviceCredit: 0,
    supervisorId: 0,
    overtimeTypes: [],
    pendingOvertime: [],
    approvedOvertime: [],
    disapprovedOvertime: [],
    cancelledOvertime: [],
    cocOvertime: [],
    forApprovalOvertime: [],
    supervisorApprovedOvertime: [],
    supervisorDisapprovedOvertime: [],
    supervisorCancelledOvertime: []
  })

  // UI state
  const uiState = reactive({
    activeEmployeeTab: 'pending',
    activeSupervisorTab: 'pending',
    showOvertimeForm: false,
    showCancelModal: false,
    showDeleteModal: false,
    deleteTarget: null,
    deleteTargetIsFullyApproved: false,
    deleteTargetIsPartiallyApproved: false,
    selectedOvertime: null,
    showOTFormModal: false,
    showOTPrintPreview: false,
    otPreviewUrl: '',
    otSelectedIds: [],
    showApproveModal: false,
    showDisapproveModal: false,
    approveSubmitting: false,
    disapproveSubmitting: false
  })

  // Tab configurations
  const employeeTabs = [
    { id: 'pending', name: 'Pending' },
    { id: 'approved', name: 'Approved' },
    { id: 'disapproved', name: 'Disapproved' },
    { id: 'cancelled', name: 'Cancelled' },
    { id: 'coc', name: 'COC Details' }
  ]

  const supervisorTabs = [
    { id: 'pending', name: 'Pending' },
    { id: 'approved', name: 'Approved' },
    { id: 'disapproved', name: 'Disapproved' },
    { id: 'cancelled', name: 'Cancelled' }
  ]

  // Helper functions
  const getCurrentUserId = () => {
    const raw = localStorage.getItem('user_data')
    try { 
      const userData = raw ? JSON.parse(raw) : null
      return userData ? userData.id : null 
    } catch (error) { 
      return null 
    }
  }

  // Main data loading function
  const loadOvertimeData = async () => {
    try {
      const userId = getCurrentUserId()
      if (!userId) {
        throw new Error('No authenticated user')
      }

      const response = await ApiService.getOvertimeData(userId)
      
      if (!response.success) {
        throw new Error(response.message || 'Failed to load overtime data')
      }

      const data = response.data
      
      // Map the API response to component data
      state.employeeInfo = data.info && data.info.length > 0 ? { id: Number(data.info[0].id) } : { id: 0 }
      state.allowed = data.allowed || false
      state.overtimeTaxCode = data.overtime_tax_code || []
      state.serviceCredit = data.Service_Credit || 0
      state.supervisorId = data.supervisor_id || 0
      state.overtimeTypes = data.LeaveType || []
      
      // Helper functions for status determination
      const toBool = (val) => val === true || val === 1 || val === '1' || val === 'true'
      const hasLevel2 = (ot) => toBool(ot.has_approver_level_2)
      const hasLevel3 = (ot) => toBool(ot.has_approver_level_3)
      
      const isOvertimeApproved = (ot) => {
        // An overtime is approved only when ALL required levels have approved
        const highestLevel = hasLevel3(ot) ? 3 : hasLevel2(ot) ? 2 : 1
        
        // Check if all levels up to the highest required have approved
        if (highestLevel >= 1 && !toBool(ot.approved)) return false
        if (highestLevel >= 2 && !toBool(ot.approved_2)) return false
        if (highestLevel >= 3 && !toBool(ot.approved_3)) return false
        
        return true
      }
      
      const isOvertimeDisapproved = (ot) => toBool(ot.disapproved) || toBool(ot.disapproved_2) || toBool(ot.disapproved_3)
      const isOvertimeCancelled = (ot) => toBool(ot.is_cancel)
      
      // Normalize and categorize employee overtime records
      const normalizeOvertime = (ot) => ({
        ...ot,
        approved: toBool(ot.approved),
        approved_2: toBool(ot.approved_2),
        approved_3: toBool(ot.approved_3),
        disapproved: toBool(ot.disapproved),
        disapproved_2: toBool(ot.disapproved_2),
        disapproved_3: toBool(ot.disapproved_3),
        is_cancel: toBool(ot.is_cancel),
        has_approver_level_2: toBool(ot.has_approver_level_2),
        has_approver_level_3: toBool(ot.has_approver_level_3)
      })
      
      // Merge all employee overtime lists and de-duplicate by id so a record
      // that appears in multiple backend buckets (e.g. pending + partially approved)
      // is only shown once in the UI.
      const mergedLists = []
        .concat(data.PendingOvertime || [])
        .concat(data.ApprovedOvertime || [])
        .concat(data.DisapprovedOvertime || [])
        .concat(data.CancelledOvertime || [])

      const overtimeById = {}
      mergedLists.forEach(raw => {
        if (!raw) return
        const id = Number(raw.id)
        if (!id) return
        // Later buckets (Approved/Disapproved/Cancelled) overwrite earlier ones for the same id
        overtimeById[id] = normalizeOvertime(raw)
      })

      const allOvertimes = Object.values(overtimeById)
      
      // Employee overtime records - recategorize based on actual approval status
      state.pendingOvertime = allOvertimes.filter(ot => !isOvertimeApproved(ot) && !isOvertimeDisapproved(ot) && !isOvertimeCancelled(ot))
      state.approvedOvertime = allOvertimes.filter(ot => isOvertimeApproved(ot) && !isOvertimeCancelled(ot))
      state.disapprovedOvertime = allOvertimes.filter(ot => isOvertimeDisapproved(ot) && !isOvertimeCancelled(ot))
      state.cancelledOvertime = allOvertimes.filter(ot => isOvertimeCancelled(ot))
      state.cocOvertime = (data.coc || []).map(normalizeOvertime)
      
      // Supervisor approval records
      state.forApprovalOvertime = (data.ForapprovalEmployeeOT || []).map(normalizeOvertime)
      state.supervisorApprovedOvertime = (data.ApprovedEmployeeOT || []).map(normalizeOvertime)
      state.supervisorDisapprovedOvertime = (data.DisapprovedEmployeeOT || []).map(normalizeOvertime)
      state.supervisorCancelledOvertime = (data.CancelEmployeeOT || []).map(normalizeOvertime)
    } catch (error) {
      toast.error('Failed to load overtime data')
    }
  }

  // CRUD operations
  const editOvertime = (overtime) => {
    uiState.selectedOvertime = overtime || null
    uiState.showOvertimeForm = true
  }

  const isFullyApproved = (row) => {
    if (!row) return false
    const approved = !!row.approved
    const disapproved = !!row.disapproved
    const disapproved2 = !!row.disapproved_2
    const disapproved3 = !!row.disapproved_3
    const approved2 = !!row.approved_2
    const approved3 = !!row.approved_3
    return approved && !disapproved && !disapproved2 && !disapproved3 &&
      (approved2 || (!approved2 && !disapproved2)) &&
      (approved3 || (!approved3 && !disapproved3))
  }

  const isPartiallyApproved = (row) => {
    if (!row) return false
    if (isFullyApproved(row)) return false
    const approved = !!row.approved
    const approved2 = !!row.approved_2
    const approved3 = !!row.approved_3
    const disapproved = !!row.disapproved
    const disapproved2 = !!row.disapproved_2
    const disapproved3 = !!row.disapproved_3
    const hasAnyApproval = approved || approved2 || approved3
    const hasAnyDisapproval = disapproved || disapproved2 || disapproved3
    return hasAnyApproval && !hasAnyDisapproval
  }

  const deleteOvertime = (overtime) => {
    uiState.deleteTarget = overtime
    uiState.deleteTargetIsFullyApproved = isFullyApproved(overtime)
    uiState.deleteTargetIsPartiallyApproved = isPartiallyApproved(overtime)
    uiState.showDeleteModal = true
  }

  const confirmDeleteOvertime = async () => {
    if (!uiState.deleteTarget) {
      uiState.showDeleteModal = false
      return
    }
    const target = uiState.deleteTarget
    const isFullyApprovedTarget = uiState.deleteTargetIsFullyApproved
    try {
      // Backend rule: only fully-approved applications can be deleted (destroy).
      // Everything else should be cancelled instead.
      if (!isFullyApprovedTarget) {
        // Backend route requires a non-empty {remarks} path segment.
        const remarks = (target.canceled_remarks || '').toString().trim() || '-'
        const response = await ApiService.cancelOvertimeApplication(
          target.id,
          target.employee_id,
          remarks
        )
        if (response && response.success) {
          toast.success('Overtime application cancelled successfully')
          await loadOvertimeData()
        } else {
          toast.error((response && response.message) || 'Failed to cancel overtime application')
        }
      } else {
        const response = await ApiService.deleteOvertimeApplication(target.id)
        if (response && response.success) {
          toast.success('Overtime application deleted successfully')
          await loadOvertimeData()
        } else {
          toast.error((response && response.message) || 'Failed to delete overtime application')
        }
      }
    } catch (error) {
      toast.error(!isFullyApprovedTarget ? 'Failed to cancel overtime application' : 'Failed to delete overtime application')
    } finally {
      uiState.showDeleteModal = false
      uiState.deleteTarget = null
      uiState.deleteTargetIsFullyApproved = false
      uiState.deleteTargetIsPartiallyApproved = false
    }
  }

  const viewOvertime = (overtime) => {
    // Handle view overtime details
  }

  const openApproveModal = (overtime) => {
    uiState.selectedOvertime = overtime
    uiState.showApproveModal = true
  }

  const closeApproveModal = () => {
    uiState.showApproveModal = false
    uiState.selectedOvertime = null
  }

  const openDisapproveModal = (overtime) => {
    uiState.selectedOvertime = overtime
    uiState.showDisapproveModal = true
  }

  const closeDisapproveModal = () => {
    uiState.showDisapproveModal = false
    uiState.selectedOvertime = null
  }

  const approveOvertime = async (data) => {
    try {
      uiState.approveSubmitting = true
      const response = await ApiService.approveOvertimeApplication(data.id, data.employee_id, data.remarks || '')
      if (response.success) {
        toast.success('Overtime application approved successfully')
        await loadOvertimeData()
        closeApproveModal()
      } else {
        toast.error(response.message || 'Failed to approve overtime application')
      }
    } catch (error) {
      toast.error('Failed to approve overtime application')
    } finally {
      uiState.approveSubmitting = false
    }
  }

  const disapproveOvertime = async (data) => {
    try {
      uiState.disapproveSubmitting = true
      const response = await ApiService.disapproveOvertimeApplication(data.id, data.remarks)
      if (response.success) {
        toast.success('Overtime application disapproved successfully')
        await loadOvertimeData()
        closeDisapproveModal()
      } else {
        toast.error(response.message || 'Failed to disapprove overtime application')
      }
    } catch (error) {
      toast.error('Failed to disapprove overtime application')
    } finally {
      uiState.disapproveSubmitting = false
    }
  }

  const cancelOvertime = (overtime) => {
    uiState.selectedOvertime = overtime
    uiState.showCancelModal = true
  }

  const handleOvertimeSaved = async (payload = {}) => {
    uiState.showOvertimeForm = false
    uiState.selectedOvertime = null
    await loadOvertimeData()
    const message = payload && payload.updated
      ? 'Overtime application updated successfully'
      : 'Overtime application saved successfully'
    toast.success(message)
  }

  const handleOvertimeCancelled = async () => {
    try {
      if (uiState.selectedOvertime) {
        // Backend route requires a non-empty {remarks} path segment.
        const remarks = (uiState.selectedOvertime.canceled_remarks || '').toString().trim() || '-'
        const response = await ApiService.cancelOvertimeApplication(
          uiState.selectedOvertime.id, 
          uiState.selectedOvertime.employee_id, 
          remarks
        )
        
        if (response.success) {
          toast.success('Overtime application cancelled successfully')
          uiState.showCancelModal = false
          uiState.selectedOvertime = null
          await loadOvertimeData()
        } else {
          toast.error(response.message || 'Failed to cancel overtime application')
        }
      }
    } catch (error) {
      toast.error('Failed to cancel overtime application')
    }
  }

  // UI helpers
  const setActiveEmployeeTab = (tabId) => {
    uiState.activeEmployeeTab = tabId
  }

  const setActiveSupervisorTab = (tabId) => {
    uiState.activeSupervisorTab = tabId
  }

  const showOvertimeForm = () => {
    uiState.selectedOvertime = null
    uiState.showOvertimeForm = true
  }

  const hideOvertimeForm = () => {
    uiState.showOvertimeForm = false
    uiState.selectedOvertime = null
  }

  const hideDeleteModal = () => {
    uiState.showDeleteModal = false
    uiState.deleteTarget = null
    uiState.deleteTargetIsFullyApproved = false
    uiState.deleteTargetIsPartiallyApproved = false
  }

  const hideCancelModal = () => {
    uiState.showCancelModal = false
    uiState.selectedOvertime = null
  }

  const openOTFormModal = () => {
    if (!state.pendingOvertime || state.pendingOvertime.length === 0) {
      toast.error('No pending overtime records available for the OT form.')
      return
    }
    uiState.showOTFormModal = true
  }

  const closeOTFormModal = () => {
    uiState.showOTFormModal = false
  }

  const printOTForm = async (ids) => {
    try {
      if (!ids || ids.length === 0) {
        toast.error('Please select at least one overtime record in the OT form modal.')
        return
      }

      uiState.otSelectedIds = ids.slice()
      const blob = await ApiService.getOvertimeAuthorizationForm(ids)

      // Revoke previous preview URL if any
      if (uiState.otPreviewUrl) {
        URL.revokeObjectURL(uiState.otPreviewUrl)
      }

      uiState.otPreviewUrl = URL.createObjectURL(blob)
      uiState.showOTPrintPreview = true
      uiState.showOTFormModal = false
    } catch (error) {
      toast.error('Failed to generate Overtime Authorization Request form.')
    }
  }

  const closeOTPrintPreview = () => {
    uiState.showOTPrintPreview = false
    if (uiState.otPreviewUrl) {
      URL.revokeObjectURL(uiState.otPreviewUrl)
      uiState.otPreviewUrl = ''
    }
  }

  const downloadOTPdf = () => {
    if (!uiState.otPreviewUrl) return
    const a = document.createElement('a')
    a.href = uiState.otPreviewUrl
    a.download = 'Overtime_Authorization_Request.pdf'
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
  }

  const downloadOTWord = async () => {
    try {
      if (!uiState.otSelectedIds || uiState.otSelectedIds.length === 0) {
        toast.error('Please generate the OT print preview first.')
        return
      }
      await ApiService.downloadOvertimeAuthorizationWord(uiState.otSelectedIds)
    } catch (error) {
      toast.error('Failed to download Overtime Authorization Request Word document.')
    }
  }

  const downloadOTExcel = async () => {
    try {
      if (!uiState.otSelectedIds || uiState.otSelectedIds.length === 0) {
        toast.error('Please generate the OT print preview first.')
        return
      }
      await ApiService.downloadOvertimeAuthorizationExcel(uiState.otSelectedIds)
    } catch (error) {
      toast.error('Failed to download Overtime Authorization Request Excel document.')
    }
  }

  return {
    // State
    state,
    uiState,
    employeeTabs,
    supervisorTabs,
    
    // Methods
    loadOvertimeData,
    editOvertime,
    deleteOvertime,
    confirmDeleteOvertime,
    viewOvertime,
    openApproveModal,
    closeApproveModal,
    openDisapproveModal,
    closeDisapproveModal,
    approveOvertime,
    disapproveOvertime,
    cancelOvertime,
    handleOvertimeSaved,
    handleOvertimeCancelled,
    
    // UI helpers
    setActiveEmployeeTab,
    setActiveSupervisorTab,
    showOvertimeForm,
    hideOvertimeForm,
    hideDeleteModal,
    hideCancelModal,
    openOTFormModal,
    closeOTFormModal,
    printOTForm,
    closeOTPrintPreview,
    downloadOTPdf,
    downloadOTWord,
    downloadOTExcel,
    
    // Computed helpers
    getCurrentUserId,
    isFullyApproved,
    isPartiallyApproved
  }
}
