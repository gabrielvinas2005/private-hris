import { reactive, computed } from 'vue'
import { useToast } from 'vue-toastification'
import ApiService from '../services/api.js'

export const VL_MONETIZATION_MIN_BALANCE = 15
export const VL_MONETIZATION_MIN_RETAIN = 5

export const getVacationLeaveBalance = (balances = []) => {
  const item = balances.find((b) => {
    const typeId = Number(b.leave_type_id)
    const typeName = String(b.type || '').toLowerCase()
    return typeId === 16 || typeName.includes('vacation')
  })
  return item ? Number(item.balance) || 0 : 0
}

export const maxVlCreditsToMonetize = (vlBalance) =>
  Math.max(0, (Number(vlBalance) || 0) - VL_MONETIZATION_MIN_RETAIN)

export const isVlEligibleForMonetization = (vlBalance) =>
  (Number(vlBalance) || 0) >= VL_MONETIZATION_MIN_BALANCE

export const getRequiredApprovalLevels = (record, fallback = 1) => {
  const levels = Number(record?.required_approval_levels)
  return levels > 0 ? levels : fallback
}

export const isMonetizationFullyApproved = (record, fallbackLevels = 1) => {
  if (!record) return false
  if (record.disapprove_1 || record.disapprove_2 || record.disapprove_3) return false
  if (record.is_fully_approved) return true

  const maxLevels = Math.min(getRequiredApprovalLevels(record, fallbackLevels), 2)
  if (maxLevels === 1) {
    return !!record.approve_1
  }

  for (let level = 1; level <= maxLevels; level++) {
    if (!record[`approve_${level}`]) return false
  }
  return true
}

export function useLeaveMonetization() {
  const toast = useToast()
  
  // Reactive state
  const state = reactive({
    activeEmployeeTab: 'pending',
    activeApproverTab: 'pending',
    allowed: true,
    isSupervisor: false,
    leaveBalances: [],
    vlMonetizationMinBalance: VL_MONETIZATION_MIN_BALANCE,
    vlMonetizationMinRetain: VL_MONETIZATION_MIN_RETAIN,
    requiredApprovalLevels: 1,
    pendingMonetizations: [],
    approvedMonetizations: [],
    disapprovedMonetizations: [],
    pendingApprovals: [],
    approvedByApprover: [],
    disapprovedByApprover: []
  })

  // UI state
  const uiState = reactive({
    showFormModal: false,
    showApproverModal: false,
    showDetailDrawer: false,
    detailViewOnly: false,
    selectedMonetization: null,
    selectedMonetizationForDetail: null
  })

  // Tab configurations
  const employeeTabs = [
    { id: 'pending', name: 'Pending' },
    { id: 'approved', name: 'Approved' },
    { id: 'disapproved', name: 'Disapproved' }
  ]

  const approverTabs = [
    { id: 'pending', name: 'Pending' },
    { id: 'approved', name: 'Approved' },
    { id: 'disapproved', name: 'Disapproved' }
  ]

  // Computed properties
  const pendingApprovalsCount = computed(() => {
    return Array.isArray(state.pendingApprovals) ? state.pendingApprovals.length : 0
  })

  const vlBalance = computed(() => getVacationLeaveBalance(state.leaveBalances))

  const maxVlToMonetize = computed(() => {
    const minRetain = state.vlMonetizationMinRetain || VL_MONETIZATION_MIN_RETAIN
    return Math.max(0, vlBalance.value - minRetain)
  })

  const canApplyMonetization = computed(() => {
    const minBalance = state.vlMonetizationMinBalance || VL_MONETIZATION_MIN_BALANCE
    return state.allowed && vlBalance.value >= minBalance
  })

  const requiredApprovalLevels = computed(() => state.requiredApprovalLevels || 1)

  // Utility functions
  const getCurrentUserId = () => {
    const raw = localStorage.getItem('user_data')
    try { return raw ? JSON.parse(raw).id : null } catch { return null }
  }

  const isTruthyFlag = (val) => {
    if (val === true || val === 1 || val === '1') return true
    if (typeof val === 'string') {
      const normalized = val.trim().toLowerCase()
      return normalized === '1' || normalized === 'true' || normalized === 'yes'
    }
    if (typeof val === 'number') return val === 1
    return false
  }

  const normalizeMonetizationRecord = (x) => ({
    ...x,
    type_id: Number(x.type_id) || 1,
    amount: Number(x.amount) || 0,
    total_days: Number(x.total_days) || 0,
    vl_credit: Number(x.vl_credit) || 0,
    sl_credit: Number(x.sl_credit) || 0,
    approve_1: isTruthyFlag(x.approve_1),
    approve_2: isTruthyFlag(x.approve_2),
    approve_3: isTruthyFlag(x.approve_3),
    disapprove_1: isTruthyFlag(x.disapprove_1),
    disapprove_2: isTruthyFlag(x.disapprove_2),
    disapprove_3: isTruthyFlag(x.disapprove_3),
    required_approval_levels: Number(x.required_approval_levels) || 1,
    requires_second_approval: !!x.requires_second_approval,
    is_fully_approved: !!x.is_fully_approved
  })

  const isRealMonetizationRow = (m) => m && Number(m.id) > 0

  const isDisapprovedMonetization = (m) => m.disapprove_1 || m.disapprove_2 || m.disapprove_3

  let requiresSecondApproval = false

  // API methods
  const loadMonetizationData = async () => {
    try {
      const userId = getCurrentUserId()
      if (!userId) throw new Error('No authenticated user')
      
      const res = await ApiService.getLeaveMonetization(userId)
      if (!res.success) throw new Error(res.message || 'Failed to load')
      
      const d = res.data
      state.requiredApprovalLevels = Number(d.required_approval_levels) || 1
      requiresSecondApproval = state.requiredApprovalLevels > 1

      // balances already in controller as array of {leave_type_id, type, balance}
      state.leaveBalances = (d.leave_balances || []).map(b => ({ 
        leave_type_id: b.leave_type_id || null,
        type: b.type || '', 
        balance: Number(b.balance) || 0 
      }))

      // employee lists - attachments are now loaded by backend
      const list = (d.leave_monetization || [])
        .map(normalizeMonetizationRecord)
        .filter(isRealMonetizationRow)
        .map(x => ({
          ...x,
          attachments: (x.attachments || []).map(att => ({
            ...att,
            download_url: `${import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000'}/api/leave-monetization-attachment-download/${att.id}`
          }))
        }))

      const isFullyApprovedMonetization = (m) =>
        isMonetizationFullyApproved(m, state.requiredApprovalLevels || 1)

      const isPendingMonetizationForEmployee = (m) =>
        !isFullyApprovedMonetization(m) && !isDisapprovedMonetization(m)

      state.pendingMonetizations = list.filter(isPendingMonetizationForEmployee)
      state.approvedMonetizations = list.filter(isFullyApprovedMonetization)
      state.disapprovedMonetizations = list.filter(isDisapprovedMonetization)

      // approvals for supervisors - attachments are now loaded by backend
      const approvals = (d.leave_for_approvals || [])
        .filter(isRealMonetizationRow)
        .map(x => ({
          ...normalizeMonetizationRecord(x),
          approver_level: Number(x.approver_level) || 1,
          attachments: (x.attachments || []).map(att => ({
            ...att,
            download_url: `${import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000'}/api/leave-monetization-attachment-download/${att.id}`
          }))
        }))
      
      state.isSupervisor = approvals.length > 0
      
      const approveFieldForLevel = (level) => {
        if (level === 2) return 'approve_2'
        if (level === 3) return 'approve_3'
        return 'approve_1'
      }

      const disapproveFieldForLevel = (level) => {
        if (level === 2) return 'disapprove_2'
        if (level === 3) return 'disapprove_3'
        return 'disapprove_1'
      }

      const isPendingForCurrentApprover = (record) => {
        const approveField = approveFieldForLevel(record.approver_level)
        const disapproveField = disapproveFieldForLevel(record.approver_level)
        return !record[approveField] && !record[disapproveField]
      }

      const isApprovedByCurrentApprover = (record) => {
        const approveField = approveFieldForLevel(record.approver_level)
        const disapproveField = disapproveFieldForLevel(record.approver_level)
        return record[approveField] && !record[disapproveField]
      }

      const isDisapprovedByCurrentApprover = (record) => {
        const disapproveField = disapproveFieldForLevel(record.approver_level)
        return record[disapproveField]
      }

      state.pendingApprovals = approvals.filter(isPendingForCurrentApprover)
      state.approvedByApprover = approvals.filter(isApprovedByCurrentApprover)
      state.disapprovedByApprover = approvals.filter(isDisapprovedByCurrentApprover)
      
      state.allowed = !!d.allowed

      const rules = d.vl_monetization_rules || {}
      state.vlMonetizationMinBalance = Number(rules.min_vl_balance_to_apply) || VL_MONETIZATION_MIN_BALANCE
      state.vlMonetizationMinRetain = Number(rules.min_vl_balance_to_retain) || VL_MONETIZATION_MIN_RETAIN
    } catch (error) {
      console.error('Error loading monetization data:', error)
      toast.error('Failed to load monetization data')
    }
  }

  const deleteMonetization = async (monetization) => {
    try {
      await ApiService.deleteLeaveMonetization(monetization.id)
      toast.success('Monetization deleted successfully')
      await loadMonetizationData()
    } catch (error) {
      console.error('Error deleting monetization:', error)
      toast.error('Failed to delete monetization')
    }
  }

  const approveMonetization = async (monetization) => {
    try {
      const res = await ApiService.processLeaveMonetization(monetization.id, 1, 'Approved via portal')
      if (res && res.success === false) {
        throw new Error(res.message || 'Failed to approve monetization')
      }
      toast.success('Monetization approved successfully')
      // Reload data to refresh all tabs
      await loadMonetizationData()
      // Switch to approved tab
      state.activeApproverTab = 'approved'
    } catch (error) {
      console.error('Error approving monetization:', error)
      const message = error?.response?.data?.message || error?.message || 'Failed to approve monetization'
      toast.error(message)
    }
  }

  const disapproveMonetization = async (monetization) => {
    try {
      await ApiService.processLeaveMonetization(monetization.id, 2, 'Disapproved via portal')
      toast.success('Monetization disapproved')
      // Reload data to refresh all tabs
      await loadMonetizationData()
      // Switch to disapproved tab
      state.activeApproverTab = 'disapproved'
    } catch (error) {
      console.error('Error disapproving monetization:', error)
      toast.error('Failed to disapprove monetization')
    }
  }

  const handleMonetizationSubmit = async (formData) => {
    try {
      // backend needs employee_id - send user_id and let backend map to employee_id
      const raw = localStorage.getItem('user_data')
      const userId = raw ? JSON.parse(raw).id : null
      if (userId) formData.append('employee_id', userId)

      const id = uiState.selectedMonetization ? uiState.selectedMonetization.id : 0
      await ApiService.storeLeaveMonetization(id, formData)
      toast.success(uiState.selectedMonetization ? 'Monetization updated successfully' : 'Monetization created successfully')
      closeFormModal()
      await loadMonetizationData()
      state.activeEmployeeTab = 'pending'
    } catch (error) {
      console.error('Error submitting monetization:', error)
      const message = error?.response?.data?.message || error?.message || 'Failed to submit monetization'
      toast.error(message)
    }
  }

  // UI actions
  const addMonetization = () => {
    if (!canApplyMonetization.value) {
      if (!state.allowed) {
        toast.error('Approver is not setup. Please contact HRD.')
      } else {
        toast.error(
          `You must have at least ${state.vlMonetizationMinBalance || VL_MONETIZATION_MIN_BALANCE} Vacation Leave credits to apply for monetization.`
        )
      }
      return
    }
    uiState.selectedMonetization = null
    uiState.showFormModal = true
  }

  const editMonetization = (monetization) => {
    uiState.selectedMonetization = monetization
    uiState.showFormModal = true
  }

  const closeFormModal = () => {
    uiState.showFormModal = false
    uiState.selectedMonetization = null
  }

  const openApproverModal = () => {
    uiState.showApproverModal = true
  }

  const closeApproverModal = () => {
    uiState.showApproverModal = false
  }

  const viewMonetizationDetail = (monetization, viewOnly = null) => {
    uiState.selectedMonetizationForDetail = monetization
    uiState.detailViewOnly = viewOnly ?? !!(
      monetization?.is_fully_approved ||
      monetization?.disapprove_1 ||
      monetization?.disapprove_2
    )
    uiState.showDetailDrawer = true
  }

  const closeDetailDrawer = () => {
    uiState.showDetailDrawer = false
    uiState.selectedMonetizationForDetail = null
    uiState.detailViewOnly = false
  }

  return {
    // State
    state,
    uiState,
    employeeTabs,
    approverTabs,
    
    // Computed
    pendingApprovalsCount,
    vlBalance,
    maxVlToMonetize,
    canApplyMonetization,
    requiredApprovalLevels,
    
    // Methods
    loadMonetizationData,
    deleteMonetization,
    approveMonetization,
    disapproveMonetization,
    handleMonetizationSubmit,
    
    // UI Actions
    addMonetization,
    editMonetization,
    closeFormModal,
    openApproverModal,
    closeApproverModal,
    viewMonetizationDetail,
    closeDetailDrawer,
    
    // Utilities
    getCurrentUserId
  }
}
