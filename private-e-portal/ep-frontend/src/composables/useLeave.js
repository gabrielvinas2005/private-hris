import { ref, reactive, computed } from 'vue'
import { useToast } from 'vue-toastification'
import ApiService from '../services/api.js'

export function useLeave() {
  const toast = useToast()
  
  // Reactive state
  const state = reactive({
    activeTab: 'pending',
    activeApproverTab: 'pending',
    allowed: true,
    isApprover: false,
    leaveBalances: [],
    pendingLeaves: [],
    approvedLeaves: [],
    disapprovedLeaves: [],
    cancelledLeaves: [],
    expiredLeaves: [],
    pendingApprovals: [],
    approvedByApprover: [],
    disapprovedByApprover: [],
    cancelledByApprover: [],
    expiredByApprover: [],
    leaveTypes: [],
    employeeId: null,
    search: '',
    statusFilter: ''
  })

  // UI state
  const uiState = reactive({
    showAddModal: false,
    showVacationPromptModal: false,
    showSickPromptModal: false,
    showSpecialLeaveForWomenPromptModal: false,
    showStudyLeavePromptModal: false,
    showEdit: false,
    showCancelModal: false,
    showApproveModal: false,
    showDisapproveModal: false,
    isTableLoading: false,
    selectedLeave: null,
    printBlobUrl: '',
    printBlob: null,
    addSubmitting: false,
    editSubmitting: false,
    approveSubmitting: false,
    disapproveSubmitting: false
  })

  // Form data
  const formData = reactive({
    addForm: { 
      leave_type_id: '', 
      day_type_id: '', 
      date_from: '', 
      date_to: '', 
      reason: '',
      employee_id: '',
      incase_vacation_leave_id: '',
      incase_vacation_leave_specify: '',
      incase_sick_leave_id: '',
      incase_sick_leave_specify: '',
      incase_special_leave_specify: '',
      incase_study_leave_id: '',
      other_purpose_id: '',
      monetization: false,
      terminal_leave: false,
      monetization_amount: '',
      commutation_id: '',
      emergency_leave: false,
      is_advance_filing: false
    },
    editForm: { 
      leave_type_id: '', 
      day_type_id: '', 
      date_from: '', 
      date_to: '', 
      reason: '',
      employee_id: '',
      incase_vacation_leave_id: '',
      incase_vacation_leave_specify: '',
      incase_sick_leave_id: '',
      incase_sick_leave_specify: '',
      incase_special_leave_specify: '',
      incase_study_leave_id: '',
      other_purpose_id: '',
      monetization: false,
      terminal_leave: false,
      monetization_amount: '',
      commutation_id: '',
      is_advance_filing: false
    },
    addAttachments: []
  })

  // Tab configurations
  const tabs = [
    { id: 'pending', name: 'Pending' },
    { id: 'approved', name: 'Approved' },
    { id: 'disapproved', name: 'Disapproved' },
    { id: 'cancelled', name: 'Cancelled' },
    { id: 'expired', name: 'Expired' }
  ]

  const approverTabs = [
    { id: 'pending', name: 'Pending' },
    { id: 'approved', name: 'Approved' },
    { id: 'disapproved', name: 'Disapproved' },
    { id: 'cancelled', name: 'Cancelled' },
    { id: 'expired', name: 'Expired' }
  ]

  // Computed properties
  const filteredLeaves = computed(() => {
    let base = []
    const status = (state.statusFilter || '').toLowerCase()
    if (status === 'approved') base = state.approvedLeaves
    else if (status === 'disapproved') base = state.disapprovedLeaves
    else if (status === 'cancelled') base = state.cancelledLeaves
    else if (status === 'expired') base = state.expiredLeaves
    else base = state.pendingLeaves

    if (state.search && state.search.trim().length > 0) {
      const q = state.search.trim().toLowerCase()
      base = base.filter(l =>
        String(l.leave_type || '').toLowerCase().includes(q) ||
        String(l.reason || '').toLowerCase().includes(q)
      )
    }
    return base
  })

  // Helper function to get leave balance by leave type name
  const getLeaveBalance = (leaveTypeName) => {
    const balance = state.leaveBalances.find(b => b.type === leaveTypeName)
    return balance ? balance.balance : 0
  }

  const isLwopLeaveType = (leaveType) => {
    if (!leaveType) return false
    if (String(leaveType.id) === '13') return true
    return String(leaveType.name || '').toLowerCase().includes('lwop')
  }

  // Check if a leave type has 0 credits
  const hasZeroCredits = (leaveType) => {
    // LWOP is allowed even when credits are 0.
    if (isLwopLeaveType(leaveType)) return false
    const balance = getLeaveBalance(leaveType.name)
    return balance <= 0
  }

  // Utility functions
  const getCurrentUserId = () => {
    const raw = localStorage.getItem('user_data')
    try { return raw ? JSON.parse(raw).id : null } catch { return null }
  }

  const formatCredits = (value) => {
    const n = Number(value)
    if (!isFinite(n) || n <= 0) return '0.000'
    return n.toFixed(3)
  }

  // API methods
  const loadLeaveTypes = async () => {
    try {
      const res = await ApiService.getLeaveForm(0, 0)
      if (res && res.success) {
        state.leaveTypes = res.data?.leave_types || []
      }
    } catch (e) {
    }
  }

  const loadLeaveData = async () => {
    uiState.isTableLoading = true
    try {
      const userId = getCurrentUserId()
      if (!userId) throw new Error('No authenticated user')
      
      const res = await ApiService.getLeaveDashboard(userId)
      if (!res.success) throw new Error(res.message || 'Failed to load')
      
      const d = res.data
      state.allowed = !!(d && d.permissions && d.permissions.can_approve === 1)
      state.leaveBalances = (d.leave_balances || []).map(b => ({
        type: b.type,
        balance: b && b.balance != null ? Number(b.balance) : 0
      }))
      
      // Cache employee id from payload if present
      const emp = d.employee_data && d.employee_data[0]
      if (emp && emp.id) state.employeeId = emp.id

      // Fallback: if API returns no balances, query leave-credits endpoint
      if (!state.leaveBalances || state.leaveBalances.length === 0) {
        try {
          const lc = await ApiService.getLeaveCredits()
          if (lc && lc.success && Array.isArray(lc.data)) {
            state.leaveBalances = lc.data.map(t => ({ type: t.name, balance: 0 }))
          }
        } catch (_) {}
      }

      const toBool = (val) => val === true || val === 1 || val === '1' || val === 'true'
      
      // Check if approval levels are CONFIGURED (not if approvers have approved)
      // Note: System only supports 3 approval levels in leave_headers table
      const hasLevel2 = (leave) => toBool(leave.has_approver_level_2)
      const hasLevel3 = (leave) => toBool(leave.has_approver_level_3)
      
      const isLeaveCancelled = (leave) => toBool(leave.is_cancel) || toBool(leave.is_cancel_2) || toBool(leave.is_cancel_3)
      const isLeaveDisapproved = (leave) => toBool(leave.disapproved) || toBool(leave.disapproved_2) || toBool(leave.disapproved_3)
      const today = new Date()
      today.setHours(0, 0, 0, 0)

      const parseDateOnly = (value) => {
        if (!value) return null
        const s = String(value)
        // Common backend format: YYYY-MM-DD
        if (/^\d{4}-\d{2}-\d{2}$/.test(s)) {
          return new Date(s + 'T00:00:00')
        }
        const d = new Date(value)
        return isNaN(d.getTime()) ? null : d
      }
      
      const isLeaveApproved = (leave) => {
        // A leave is approved only when ALL required levels have approved
        // System only tracks up to 3 approval levels
        const highestLevel = hasLevel3(leave) ? 3 : hasLevel2(leave) ? 2 : 1
        
        // Check if all levels up to the highest required have approved
        if (highestLevel >= 1 && !toBool(leave.approved)) return false
        if (highestLevel >= 2 && !toBool(leave.approved_2)) return false
        if (highestLevel >= 3 && !toBool(leave.approved_3)) return false
        
        return true
      }
      const levelApproved = (leave, level) => {
        if (level === 3) return toBool(leave.approved_3)
        if (level === 2) return toBool(leave.approved_2)
        return toBool(leave.approved)
      }
      const levelDisapproved = (leave, level) => {
        if (level === 3) return toBool(leave.disapproved_3)
        if (level === 2) return toBool(leave.disapproved_2)
        return toBool(leave.disapproved)
      }

      // Expired = not fully approved, date_from already passed, and not cancelled/disapproved.
      // Exempt: Sick Leave (leave_types.id = 2).
      const isLeaveExpired = (leave) => {
        if (String(leave.leave_type_id) === '2') return false

        const dateFrom = parseDateOnly(leave.date_from)
        if (!dateFrom) return false
        dateFrom.setHours(0, 0, 0, 0)

        // "Already passed the system date"
        if (dateFrom >= today) return false

        if (isLeaveCancelled(leave)) return false
        if (isLeaveDisapproved(leave)) return false
        if (isLeaveApproved(leave)) return false

        return true
      }

      const allLeaves = (d.leaves || []).map(l => ({
        ...l,
        approved: toBool(l.approved),
        approved_2: toBool(l.approved_2),
        approved_3: toBool(l.approved_3),
        disapproved: toBool(l.disapproved),
        disapproved_2: toBool(l.disapproved_2),
        disapproved_3: toBool(l.disapproved_3),
        is_cancel: toBool(l.is_cancel),
        is_cancel_2: toBool(l.is_cancel_2),
        is_cancel_3: toBool(l.is_cancel_3),
        has_approver_level_2: toBool(l.has_approver_level_2),
        has_approver_level_3: toBool(l.has_approver_level_3)
      }))

      state.pendingLeaves = allLeaves.filter(l =>
        !isLeaveApproved(l) &&
        !isLeaveDisapproved(l) &&
        !isLeaveCancelled(l) &&
        !isLeaveExpired(l)
      )
      state.approvedLeaves = allLeaves.filter(l => isLeaveApproved(l) && !isLeaveCancelled(l))
      state.disapprovedLeaves = allLeaves.filter(l => isLeaveDisapproved(l) && !isLeaveCancelled(l))
      state.cancelledLeaves = allLeaves.filter(l => isLeaveCancelled(l))
      state.expiredLeaves = allLeaves.filter(l => isLeaveExpired(l))

      const approvals = (d.leave_for_approvals || []).map(l => {
        // Normalize boolean values - handle various formats from backend
        const normalizeBool = (val) => {
          if (val === true || val === 1 || val === '1' || val === 'true') return true
          if (val === false || val === 0 || val === '0' || val === 'false' || val === null || val === undefined) return false
          return Boolean(val)
        }
        
        return {
          ...l,
          approved: normalizeBool(l.approved),
          approved_2: normalizeBool(l.approved_2),
          approved_3: normalizeBool(l.approved_3),
          disapproved: normalizeBool(l.disapproved),
          disapproved_2: normalizeBool(l.disapproved_2),
          disapproved_3: normalizeBool(l.disapproved_3),
          is_cancel: normalizeBool(l.is_cancel),
          is_cancel_2: normalizeBool(l.is_cancel_2),
          is_cancel_3: normalizeBool(l.is_cancel_3),
          has_approver_level_2: normalizeBool(l.has_approver_level_2),
          has_approver_level_3: normalizeBool(l.has_approver_level_3)
        }
      })

      // Categorize approvals - check status based on approver_level_id for each leave
      const categorizeApproval = (leave) => {
        const level = Number(leave.approver_level_id) || 1
        
        if (leave.is_cancel || leave.is_cancel_2 || leave.is_cancel_3) return 'cancelled'
        if (isLeaveExpired(leave)) return 'expired'
        if (levelDisapproved(leave, level)) return 'disapproved'
        if (levelApproved(leave, level)) return 'approved'
        return 'pending'
      }

      state.pendingApprovals = approvals.filter(l => categorizeApproval(l) === 'pending')
      state.approvedByApprover = approvals.filter(l => categorizeApproval(l) === 'approved')
      state.disapprovedByApprover = approvals.filter(l => categorizeApproval(l) === 'disapproved')
      state.cancelledByApprover = approvals.filter(l => categorizeApproval(l) === 'cancelled')
      state.expiredByApprover = approvals.filter(l => categorizeApproval(l) === 'expired')
      state.isApprover = approvals.length > 0
    } catch (error) {
      toast.error('Failed to load leave data')
    } finally {
      uiState.isTableLoading = false
    }
  }

  // Helper function to count working days (weekdays only, excluding weekends)
  const countWorkingDays = (startDate, endDate) => {
    const start = new Date(startDate)
    const end = new Date(endDate)
    let count = 0
    const current = new Date(start)
    
    while (current <= end) {
      const dayOfWeek = current.getDay()
      // 0 = Sunday, 6 = Saturday, exclude weekends
      if (dayOfWeek !== 0 && dayOfWeek !== 6) {
        count++
      }
      current.setDate(current.getDate() + 1)
    }
    
    return count
  }

  const normalizeDate = (value) => {
    if (value instanceof Date) {
      return new Date(value.getFullYear(), value.getMonth(), value.getDate())
    }

    const date = new Date(`${value}T00:00:00`)
    date.setHours(0, 0, 0, 0)
    date.setMinutes(0, 0)
    date.setSeconds(0, 0)
    date.setMilliseconds(0)
    return date
  }

  const isWeekday = (date) => {
    const dayOfWeek = date.getDay()
    return dayOfWeek !== 0 && dayOfWeek !== 6
  }

  // Add N working days forward from startDate (startDate itself is not counted).
  const addWorkingDaysForward = (startDate, workingDays) => {
    const current = normalizeDate(startDate)
    let counted = 0

    while (counted < workingDays) {
      current.setDate(current.getDate() + 1)
      if (isWeekday(current)) {
        counted++
      }
    }

    return current
  }

  const getMinDateForVacationLeave = (emergencyLeave = false) => {
    const today = normalizeDate(new Date())
    return emergencyLeave ? today : addWorkingDaysForward(today, 5)
  }

  const validateVacationLeaveDates = () => {
    const dateFromStr = formData.addForm.date_from
    const dateToStr = formData.addForm.date_to

    if (!dateFromStr || !dateToStr) {
      return true
    }

    const dateFrom = normalizeDate(dateFromStr)
    const dateTo = normalizeDate(dateToStr)
    const minDate = getMinDateForVacationLeave(!!formData.addForm.emergency_leave)
    const minDateStr = minDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })

    if (dateFrom < minDate || dateTo < minDate) {
      if (formData.addForm.emergency_leave) {
        toast.error('Leave dates cannot be in the past.')
      } else {
        toast.error(`Vacation leave and special privilege leave must be filed at least 5 working days in advance. The earliest date you can select is ${minDateStr}.`)
      }
      return false
    }

    return true
  }

  const clearInvalidVacationLeaveDates = () => {
    if (!requiresVacationAdvanceNotice(formData.addForm.leave_type_id)) return

    const minDate = getMinDateForVacationLeave(!!formData.addForm.emergency_leave)
    const dateFrom = formData.addForm.date_from ? normalizeDate(formData.addForm.date_from) : null
    const dateTo = formData.addForm.date_to ? normalizeDate(formData.addForm.date_to) : null

    if (dateFrom && dateFrom < minDate) {
      formData.addForm.date_from = ''
    }
    if (dateTo && dateTo < minDate) {
      formData.addForm.date_to = ''
    }
  }

  const toggleEmergencyLeave = (checked) => {
    formData.addForm.emergency_leave = !!checked
    clearInvalidVacationLeaveDates()
  }

  const clearInvalidAdvanceSickLeaveDates = () => {
    const today = new Date()
    today.setHours(0, 0, 0, 0)
    const dateFrom = formData.addForm.date_from ? normalizeDate(formData.addForm.date_from) : null
    const dateTo = formData.addForm.date_to ? normalizeDate(formData.addForm.date_to) : null

    if (dateFrom && dateFrom < today) {
      formData.addForm.date_from = ''
    }
    if (dateTo && dateTo < today) {
      formData.addForm.date_to = ''
    }
  }

  const clearInvalidStandardSickLeaveDates = () => {
    const today = new Date()
    today.setHours(0, 0, 0, 0)
    const dateFrom = formData.addForm.date_from ? normalizeDate(formData.addForm.date_from) : null
    const dateTo = formData.addForm.date_to ? normalizeDate(formData.addForm.date_to) : null

    if (dateFrom && dateFrom > today) {
      formData.addForm.date_from = ''
    }
    if (dateTo && dateTo > today) {
      formData.addForm.date_to = ''
    }
  }

  const toggleAdvanceSickLeave = (checked) => {
    formData.addForm.is_advance_filing = !!checked
    if (checked) {
      clearInvalidAdvanceSickLeaveDates()
    } else {
      clearInvalidStandardSickLeaveDates()
    }
  }

  // Helper function to get the date that is 7 working days ago from today
  // This calculates the earliest date that can be selected (7 working days in the past, NOT including today)
  // "1 week in the past" means we start counting from yesterday and go back 7 working days
  const getMaxPastDateForSickLeave = () => {
    const today = new Date()
    today.setHours(0, 0, 0, 0)
    let current = new Date(today)
    let workingDaysCount = 0
    
    // Start from yesterday (not including today)
    current.setDate(current.getDate() - 1)
    
    // Go back until we've counted exactly 7 working days
    while (workingDaysCount < 7) {
      const dayOfWeek = current.getDay()
      // 0 = Sunday, 6 = Saturday, exclude weekends
      if (dayOfWeek !== 0 && dayOfWeek !== 6) {
        workingDaysCount++
      }
      // If we haven't reached 7 working days yet, go back one more day
      if (workingDaysCount < 7) {
        current.setDate(current.getDate() - 1)
      }
    }
    
    return current
  }

  // Function to get disabled-date function for sick leave date picker
  // This prevents selecting invalid dates (future dates or dates more than 7 working days in the past)
  const getSickLeaveDisabledDate = () => {
    const today = new Date()
    today.setHours(0, 0, 0, 0)
    today.setMinutes(0, 0)
    today.setSeconds(0, 0)
    today.setMilliseconds(0)
    
    const maxPastDate = getMaxPastDateForSickLeave()
    maxPastDate.setHours(0, 0, 0, 0)
    maxPastDate.setMinutes(0, 0)
    maxPastDate.setSeconds(0, 0)
    maxPastDate.setMilliseconds(0)
    
    return (time) => {
      const date = new Date(time)
      date.setHours(0, 0, 0, 0)
      date.setMinutes(0, 0)
      date.setSeconds(0, 0)
      date.setMilliseconds(0)
      
      // Disable future dates
      if (date > today) {
        return true
      }
      
      // Disable dates more than 7 working days in the past
      if (date < maxPastDate) {
        return true
      }
      
      return false
    }
  }

  // Advance sick leave: allow today and future dates (scheduled consultation, operation, etc.)
  const getAdvanceSickLeaveDisabledDate = () => {
    const today = new Date()
    today.setHours(0, 0, 0, 0)
    today.setMinutes(0, 0)
    today.setSeconds(0, 0)
    today.setMilliseconds(0)

    return (time) => {
      const date = new Date(time)
      date.setHours(0, 0, 0, 0)
      date.setMinutes(0, 0)
      date.setSeconds(0, 0)
      date.setMilliseconds(0)
      return date < today
    }
  }

  // Helper to determine if a given leave type id represents a Sick Leave.
  // This is based on the current, dynamic leave type list from the backend,
  // so it does NOT rely on hard-coded IDs. As long as the name still contains
  // the word "sick" (e.g. "Sick Leave"), behavior stays correct even if
  // admins change IDs or re-order leave types.
  const isSickLeave = (leaveTypeId) => {
    if (!leaveTypeId) return false
    const lt = state.leaveTypes.find(t => String(t.id) === String(leaveTypeId))
    if (!lt || !lt.name) return false
    return String(lt.name).toLowerCase().includes('sick')
  }

  const isVacationOrSpecialLeave = (leaveTypeId) => {
    const id = String(leaveTypeId || '')
    return id === '1' || id === '3'
  }

  const requiresVacationAdvanceNotice = (leaveTypeId) => {
    if (!leaveTypeId) return false
    const lt = state.leaveTypes.find(t => String(t.id) === String(leaveTypeId))
    if (!lt?.name) return false
    const name = String(lt.name).toLowerCase()
    return name.includes('vacation') || name.includes('special privilege')
  }

  const allowsEmergencyLeave = (leaveTypeId) => {
    if (!leaveTypeId) return false
    const lt = state.leaveTypes.find(t => String(t.id) === String(leaveTypeId))
    if (!lt) return false
    const flag = lt.is_el
    return flag === 1 || flag === '1' || flag === true
  }

  const showEmergencyLeaveCheckbox = computed(() => allowsEmergencyLeave(formData.addForm.leave_type_id))

  const addLeaveDisabledDate = computed(() => {
    const leaveTypeId = formData.addForm.leave_type_id
    const emergencyLeave = !!formData.addForm.emergency_leave

    if (isSickLeave(leaveTypeId)) {
      if (formData.addForm.is_advance_filing) {
        return getAdvanceSickLeaveDisabledDate()
      }
      return getSickLeaveDisabledDate()
    }

    if (requiresVacationAdvanceNotice(leaveTypeId)) {
      const minDate = getMinDateForVacationLeave(emergencyLeave)
      return (time) => {
        const date = normalizeDate(time)
        return date.getTime() < minDate.getTime()
      }
    }

    return undefined
  })

  const isSickLeaveTypeById = (leaveTypeId) => String(leaveTypeId || '') === '2'
  const isSpecialLeaveBenefitsForWomen = (leaveTypeId) => String(leaveTypeId || '') === '25'
  const isStudyLeaveTypeById = (leaveTypeId) => String(leaveTypeId || '') === '5'
  const isLwopTypeById = (leaveTypeId) => String(leaveTypeId || '') === '13'
  const hasOtherPurposeSelected = () => ['1', '2'].includes(String(formData.addForm.other_purpose_id || ''))
  const hasEditOtherPurposeSelected = () => ['1', '2'].includes(String(formData.editForm.other_purpose_id || ''))

  const toggleVacationLeaveIncase = (id, checked) => {
    if (checked) {
      formData.addForm.incase_vacation_leave_id = String(id)
      return
    }

    if (formData.addForm.incase_vacation_leave_id === String(id)) {
      formData.addForm.incase_vacation_leave_id = ''
    }
  }

  const toggleSickLeaveIncase = (id, checked) => {
    if (checked) {
      formData.addForm.incase_sick_leave_id = String(id)
      return
    }

    if (formData.addForm.incase_sick_leave_id === String(id)) {
      formData.addForm.incase_sick_leave_id = ''
    }
  }

  const toggleStudyLeaveIncase = (id, checked) => {
    if (checked) {
      formData.addForm.incase_study_leave_id = String(id)
      return
    }

    if (formData.addForm.incase_study_leave_id === String(id)) {
      formData.addForm.incase_study_leave_id = ''
    }
  }

  const toggleOtherPurpose = (id, checked) => {
    if (!checked) {
      if (formData.addForm.other_purpose_id === String(id)) {
        formData.addForm.other_purpose_id = ''
        formData.addForm.monetization = false
        formData.addForm.terminal_leave = false
        formData.addForm.monetization_amount = ''
        formData.addForm.commutation_id = ''
        handleAddLeaveTypeChange(formData.addForm.leave_type_id)
      }
      return
    }

    formData.addForm.other_purpose_id = String(id)
    formData.addForm.monetization = String(id) === '1'
    formData.addForm.terminal_leave = String(id) === '2'
    if (String(id) !== '1') {
      formData.addForm.monetization_amount = ''
    }
    if (String(id) !== '2') {
      formData.addForm.commutation_id = ''
    }

    uiState.showVacationPromptModal = false
    uiState.showSickPromptModal = false
    uiState.showSpecialLeaveForWomenPromptModal = false
    uiState.showStudyLeavePromptModal = false
  }

  const toggleCommutation = (id, checked) => {
    if (checked) {
      formData.addForm.commutation_id = String(id)
      return
    }

    if (formData.addForm.commutation_id === String(id)) {
      formData.addForm.commutation_id = ''
    }
  }

  const toggleEditVacationLeaveIncase = (id, checked) => {
    if (checked) {
      formData.editForm.incase_vacation_leave_id = String(id)
      return
    }

    if (formData.editForm.incase_vacation_leave_id === String(id)) {
      formData.editForm.incase_vacation_leave_id = ''
    }
  }

  const toggleEditSickLeaveIncase = (id, checked) => {
    if (checked) {
      formData.editForm.incase_sick_leave_id = String(id)
      return
    }

    if (formData.editForm.incase_sick_leave_id === String(id)) {
      formData.editForm.incase_sick_leave_id = ''
    }
  }

  const toggleEditStudyLeaveIncase = (id, checked) => {
    if (checked) {
      formData.editForm.incase_study_leave_id = String(id)
      return
    }

    if (formData.editForm.incase_study_leave_id === String(id)) {
      formData.editForm.incase_study_leave_id = ''
    }
  }

  const toggleEditOtherPurpose = (id, checked) => {
    if (!checked) {
      if (formData.editForm.other_purpose_id === String(id)) {
        formData.editForm.other_purpose_id = ''
        formData.editForm.monetization = false
        formData.editForm.terminal_leave = false
        formData.editForm.monetization_amount = ''
        formData.editForm.commutation_id = ''
      }
      return
    }

    formData.editForm.other_purpose_id = String(id)
    formData.editForm.monetization = String(id) === '1'
    formData.editForm.terminal_leave = String(id) === '2'
    if (String(id) !== '1') {
      formData.editForm.monetization_amount = ''
    }
    if (String(id) !== '2') {
      formData.editForm.commutation_id = ''
    }
  }

  const toggleEditCommutation = (id, checked) => {
    if (checked) {
      formData.editForm.commutation_id = String(id)
      return
    }

    if (formData.editForm.commutation_id === String(id)) {
      formData.editForm.commutation_id = ''
    }
  }

  const handleAddLeaveTypeChange = (leaveTypeId) => {
    if (isLwopTypeById(leaveTypeId)) {
      uiState.showVacationPromptModal = true
      uiState.showSickPromptModal = true
      uiState.showSpecialLeaveForWomenPromptModal = true
      uiState.showStudyLeavePromptModal = true
      formData.addForm.other_purpose_id = ''
      formData.addForm.monetization = false
      formData.addForm.terminal_leave = false
      formData.addForm.monetization_amount = ''
      formData.addForm.commutation_id = ''
      return
    }

    if (hasOtherPurposeSelected()) {
      uiState.showVacationPromptModal = false
      uiState.showSickPromptModal = false
      uiState.showSpecialLeaveForWomenPromptModal = false
      uiState.showStudyLeavePromptModal = false
      return
    }

    uiState.showVacationPromptModal = !isSickLeave(leaveTypeId)
    uiState.showSickPromptModal = isSickLeaveTypeById(leaveTypeId)
    uiState.showSpecialLeaveForWomenPromptModal = isSpecialLeaveBenefitsForWomen(leaveTypeId)
    uiState.showStudyLeavePromptModal = isStudyLeaveTypeById(leaveTypeId)

    if (!uiState.showVacationPromptModal) {
      formData.addForm.incase_vacation_leave_id = ''
      formData.addForm.incase_vacation_leave_specify = ''
    }

    if (!allowsEmergencyLeave(leaveTypeId)) {
      formData.addForm.emergency_leave = false
    }

    if (requiresVacationAdvanceNotice(leaveTypeId)) {
      clearInvalidVacationLeaveDates()
    }

    if (!uiState.showSickPromptModal && !isSickLeave(leaveTypeId)) {
      formData.addForm.incase_sick_leave_id = ''
      formData.addForm.incase_sick_leave_specify = ''
      formData.addForm.is_advance_filing = false
    }

    if (!isSickLeave(leaveTypeId)) {
      formData.addForm.is_advance_filing = false
    }

    if (!uiState.showSpecialLeaveForWomenPromptModal) {
      formData.addForm.incase_special_leave_specify = ''
    }

    if (!uiState.showStudyLeavePromptModal) {
      formData.addForm.incase_study_leave_id = ''
    }
  }

  const submitAddLeave = async () => {
    const maxAvailmentToastMessage = 'Failed to Apply Leave. Maximum leave availment for this year has been reached. You are not allowed to apply this leave.'
    const otherPurposeSelected = hasOtherPurposeSelected()
    if (
      !formData.addForm.leave_type_id ||
      (!otherPurposeSelected && !formData.addForm.day_type_id) ||
      (!otherPurposeSelected && !formData.addForm.date_from) ||
      (!otherPurposeSelected && !formData.addForm.date_to)
    ) {
      toast.error('Please complete all required fields')
      return
    }
    if (formData.addAttachments.length === 0) { 
      toast.error('Please upload at least one file')
      return 
    }
    
    // Check if selected leave type has credits
    const selectedLeaveType = state.leaveTypes.find(lt => String(lt.id) === formData.addForm.leave_type_id)
    if (selectedLeaveType && hasZeroCredits(selectedLeaveType)) {
      toast.error(`You cannot apply for ${selectedLeaveType.name} because you have no available credits.`)
      return
    }

    if (!otherPurposeSelected && isVacationOrSpecialLeave(formData.addForm.leave_type_id) && !formData.addForm.incase_vacation_leave_id) {
      toast.error('Please select where you are spending your leave.')
      return
    }

    if (!otherPurposeSelected && isSickLeaveTypeById(formData.addForm.leave_type_id) && !formData.addForm.incase_sick_leave_id) {
      toast.error('Please select sick leave type.')
      return
    }

    if (
      !otherPurposeSelected &&
      (isSickLeave(formData.addForm.leave_type_id) || formData.addForm.incase_sick_leave_id) &&
      !String(formData.addForm.incase_sick_leave_specify || '').trim()
    ) {
      toast.error('Please specify illness.')
      return
    }

    if (!otherPurposeSelected && isStudyLeaveTypeById(formData.addForm.leave_type_id) && !formData.addForm.incase_study_leave_id) {
      toast.error('Please select study leave type.')
      return
    }

    if (!otherPurposeSelected && requiresVacationAdvanceNotice(formData.addForm.leave_type_id) && !validateVacationLeaveDates()) {
      return
    }

    if (formData.addForm.emergency_leave && !allowsEmergencyLeave(formData.addForm.leave_type_id)) {
      toast.error('Emergency leave is not allowed for the selected leave type.')
      return
    }

    if (formData.addForm.other_purpose_id === '1' && !String(formData.addForm.monetization_amount || '').trim()) {
      toast.error('Please enter monetization amount.')
      return
    }
    if (formData.addForm.other_purpose_id === '2' && !String(formData.addForm.commutation_id || '').trim()) {
      toast.error('Please select commutation option.')
      return
    }
    
    // Advance sick leave: today or future dates only
    if (!otherPurposeSelected && isSickLeave(formData.addForm.leave_type_id) && formData.addForm.is_advance_filing) {
      const today = new Date()
      today.setHours(0, 0, 0, 0)
      const dateFrom = formData.addForm.date_from ? normalizeDate(formData.addForm.date_from) : null
      const dateTo = formData.addForm.date_to ? normalizeDate(formData.addForm.date_to) : null

      if (!dateFrom || !dateTo) {
        toast.error('Please select both start and end dates for advance sick leave.')
        return
      }
      if (dateFrom < today || dateTo < today) {
        toast.error('Advance sick leave must be filed for today or a future date (e.g. scheduled consultation or operation).')
        return
      }
      if (dateTo < dateFrom) {
        toast.error('End date cannot be before start date. Please select a valid date range.')
        return
      }
    }

    // Validate sick leave date range (can be filed up to 1 week past, counting only working days)
    if (!otherPurposeSelected && isSickLeave(formData.addForm.leave_type_id) && !formData.addForm.is_advance_filing) {
      try {
        // Use actual system date - ensure we're not using any demo/manipulated date
        const today = new Date()
        today.setHours(0, 0, 0, 0)
        today.setMinutes(0, 0)
        today.setSeconds(0, 0)
        today.setMilliseconds(0)
        
        // Parse dates and handle invalid dates
        const dateFromStr = formData.addForm.date_from
        const dateToStr = formData.addForm.date_to
        
        if (!dateFromStr || !dateToStr) {
          toast.error('Please select both start and end dates for sick leave.')
          return
        }
        
        const dateFrom = new Date(dateFromStr + 'T00:00:00') // Add time to avoid timezone issues
        const dateTo = new Date(dateToStr + 'T00:00:00')
        
        // Check if dates are valid
        if (isNaN(dateFrom.getTime()) || isNaN(dateTo.getTime())) {
          toast.error('Invalid date format. Please select valid dates.')
          return
        }
        
        dateFrom.setHours(0, 0, 0, 0)
        dateTo.setHours(0, 0, 0, 0)
        
        // Check if date_to is before date_from
        if (dateTo < dateFrom) {
          toast.error('End date cannot be before start date. Please select a valid date range.')
          return
        }
        
        // Check if date_from is in the future
        if (dateFrom > today) {
          const todayStr = today.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
          toast.error(`Sick leave cannot be filed for future dates. Today is ${todayStr}. You can only file sick leave for today or up to 1 week in the past (counting only working days).`)
          return
        }
        
        // Check if date_to is in the future
        if (dateTo > today) {
          const todayStr = today.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
          toast.error(`Sick leave end date cannot be in the future. Today is ${todayStr}. You can only file sick leave for today or up to 1 week in the past (counting only working days).`)
          return
        }
        
        // Calculate the earliest allowed date (7 working days in the past, including today if it's a weekday)
        const maxPastDate = getMaxPastDateForSickLeave()
        maxPastDate.setHours(0, 0, 0, 0)
        maxPastDate.setMinutes(0, 0)
        maxPastDate.setSeconds(0, 0)
        maxPastDate.setMilliseconds(0)
        
        // Check if date_from is more than 7 working days in the past
        // Use getTime() for accurate comparison
        if (dateFrom.getTime() < maxPastDate.getTime()) {
          const maxPastDateStr = maxPastDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
          const todayStr = today.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
          const selectedDateStr = dateFrom.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
          toast.error(`Sick leave can only be filed up to 1 week (7 working days) in the past from today. Today is ${todayStr}, the earliest date you can select is ${maxPastDateStr}, but you selected ${selectedDateStr}. Please select a date within the allowed range.`)
          return
        }
        
        // Check if the date range spans more than 7 working days
        const workingDaysInRange = countWorkingDays(dateFrom, dateTo)
        if (workingDaysInRange > 7) {
          toast.error(`Sick leave can only be filed for a maximum of 7 working days. Your selected date range (${dateFromStr} to ${dateToStr}) spans ${workingDaysInRange} working days. Please adjust your date range.`)
          return
        }
        
        // Additional check: ensure date_to is not more than 7 working days in the past
        if (dateTo.getTime() < maxPastDate.getTime()) {
          const maxPastDateStr = maxPastDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
          const todayStr = today.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
          const selectedDateStr = dateTo.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
          toast.error(`Sick leave end date cannot be more than 1 week (7 working days) in the past. Today is ${todayStr}, the earliest date you can select is ${maxPastDateStr}, but you selected ${selectedDateStr}. Please adjust your end date.`)
          return
        }
      } catch (error) {
        console.error('Sick leave validation error:', error)
        toast.error('Error validating sick leave dates. Please check your date selections and try again.')
        return
      }
    }
    
    uiState.addSubmitting = true
    try {
      // Ensure we have employee id
      if (!state.employeeId) {
        try {
          const formRes = await ApiService.getLeaveForm(0, 0)
          const emp = formRes?.data?.employee_data && formRes.data.employee_data[0]
          if (emp && emp.id) state.employeeId = emp.id
        } catch (_) {}
      }
      
      const formDataObj = new FormData()
      formDataObj.append('leave_type_id', formData.addForm.leave_type_id)
      if (!otherPurposeSelected || formData.addForm.day_type_id) {
        formDataObj.append('day_type_id', formData.addForm.day_type_id || '')
      }
      if (!otherPurposeSelected || formData.addForm.date_from) {
        formDataObj.append('date_from', formData.addForm.date_from || '')
      }
      if (!otherPurposeSelected || formData.addForm.date_to) {
        formDataObj.append('date_to', formData.addForm.date_to || '')
      }
      const reason = String(formData.addForm.reason || '').trim()
      formDataObj.append('reason', reason)
      if (formData.addForm.incase_vacation_leave_id) {
        formDataObj.append('incase_vacation_leave_id', formData.addForm.incase_vacation_leave_id)
      }
      const vacationLeaveSpecify = String(formData.addForm.incase_vacation_leave_specify || '').trim()
      if (vacationLeaveSpecify) {
        formDataObj.append('incase_vacation_leave_specify', vacationLeaveSpecify)
      }
      if (formData.addForm.incase_sick_leave_id) {
        formDataObj.append('incase_sick_leave_id', formData.addForm.incase_sick_leave_id)
      }
      const sickLeaveSpecify = String(formData.addForm.incase_sick_leave_specify || '').trim()
      if (sickLeaveSpecify) {
        formDataObj.append('incase_sick_leave_specify', sickLeaveSpecify)
      }
      const specialLeaveSpecify = String(formData.addForm.incase_special_leave_specify || '').trim()
      if (specialLeaveSpecify) {
        formDataObj.append('incase_special_leave_specify', specialLeaveSpecify)
      }
      if (formData.addForm.incase_study_leave_id) {
        formDataObj.append('incase_study_leave_id', formData.addForm.incase_study_leave_id)
      }
      if (formData.addForm.other_purpose_id) {
        formDataObj.append('other_purpose_id', formData.addForm.other_purpose_id)
      }
      if (formData.addForm.commutation_id) {
        formDataObj.append('commutation_id', formData.addForm.commutation_id)
      }
      formDataObj.append('monetization', formData.addForm.monetization ? '1' : '0')
      formDataObj.append('terminal_leave', formData.addForm.terminal_leave ? '1' : '0')
      if (String(formData.addForm.monetization_amount || '').trim()) {
        formDataObj.append('monetization_amount', String(formData.addForm.monetization_amount).trim())
      }
      if (state.employeeId) {
        formDataObj.append('employee_id', String(state.employeeId))
      }
      if (formData.addForm.emergency_leave) {
        formDataObj.append('is_emergency_leave', '1')
      }
      if (formData.addForm.is_advance_filing) {
        formDataObj.append('is_advance_filing', '1')
      }
      formData.addAttachments.forEach(f => formDataObj.append('attachments[]', f))
      
      const res = await ApiService.submitLeave(0, formDataObj)
      if (!res || res.success !== true) {
        // Check for specific error messages and provide user-friendly feedback
        const errorMsg = res?.message || res?.error || res?.data?.message || res?.data?.error || 'Failed to submit leave'
        if (String(errorMsg).toLowerCase().includes('maximum leave availment for this year has been reached')) {
          toast.error(maxAvailmentToastMessage)
          return
        }
        if (typeof errorMsg === 'string' && errorMsg.trim()) {
          toast.error(errorMsg)
          return
        }
        
        // Handle sick leave validation errors from backend
        if (leaveTypeId === 3) {
          // Check if dates are in the future (backend validation might have caught it)
          try {
            const dateFrom = new Date(formData.addForm.date_from)
            const dateTo = new Date(formData.addForm.date_to)
            const today = new Date()
            today.setHours(0, 0, 0, 0)
            dateFrom.setHours(0, 0, 0, 0)
            dateTo.setHours(0, 0, 0, 0)
            
            if (dateFrom > today || dateTo > today) {
              throw new Error('Sick leave cannot be filed for future dates. You can only file sick leave for today or up to 1 week in the past (counting only working days).')
            }
            
            // Check if dates are too far in the past
            const maxPastDate = getMaxPastDateForSickLeave()
            maxPastDate.setHours(0, 0, 0, 0)
            
            if (dateFrom < maxPastDate || dateTo < maxPastDate) {
              const maxPastDateStr = maxPastDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
              throw new Error(`Sick leave can only be filed up to 1 week (7 working days) in the past from today. The earliest date you can select is ${maxPastDateStr}. Please select dates within the allowed range.`)
            }
            
            // Check if date range is too long
            const workingDaysInRange = countWorkingDays(dateFrom, dateTo)
            if (workingDaysInRange > 7) {
              throw new Error(`Sick leave can only be filed for a maximum of 7 working days. Your selected date range spans ${workingDaysInRange} working days. Please adjust your date range.`)
            }
            
            // Generic error for other validation issues
            if (errorMsg.includes('Invalid Leave Application') || errorMsg.includes('Invalid')) {
              throw new Error('Invalid leave application. Please check your dates and ensure they are within the allowed range for sick leave (today or up to 1 week in the past, counting only working days).')
            }
          } catch (validationError) {
            // If it's already our custom error, throw it; otherwise use the backend error
            if (validationError.message && !validationError.message.includes('Invalid Leave Application')) {
              throw validationError
            }
          }
        }
        
        throw new Error(errorMsg)
      }
      
      toast.success('Leave application submitted successfully')
      closeAddLeave()
      await loadLeaveData()
    } catch (err) {
      // Parse error message to extract user-friendly message
      let errorMessage = 'Failed to submit leave'
      const rawBackendMessage =
        err?.response?.data?.message ||
        err?.response?.data?.error ||
        err?.data?.message ||
        err?.data?.error ||
        err?.message ||
        ''
      if (String(rawBackendMessage).toLowerCase().includes('maximum leave availment for this year has been reached')) {
        toast.error(maxAvailmentToastMessage)
        return
      }
      
      if (err?.message) {
        const message = err.message
        
        // Check if error contains JSON (e.g., '{"error":"..."}' or '{"message":"..."}')
        const jsonMatch = message.match(/\{[\s\S]*"(error|message)"[\s\S]*\}/)
        if (jsonMatch) {
          try {
            const errorObj = JSON.parse(jsonMatch[0])
            const backendError = errorObj.error || errorObj.message || ''
            
            // For sick leave, provide specific validation error messages
            if (leaveTypeId === 3 && (backendError.includes('Invalid Leave Application') || backendError.includes('Invalid'))) {
              // Re-validate dates to provide specific error message
              try {
                const dateFrom = new Date(formData.addForm.date_from + 'T00:00:00')
                const dateTo = new Date(formData.addForm.date_to + 'T00:00:00')
                const today = new Date()
                today.setHours(0, 0, 0, 0)
                dateFrom.setHours(0, 0, 0, 0)
                dateTo.setHours(0, 0, 0, 0)
                
                if (dateFrom > today || dateTo > today) {
                  errorMessage = 'Sick leave cannot be filed for future dates. You can only file sick leave for today or up to 1 week in the past (counting only working days).'
                } else {
                  const maxPastDate = getMaxPastDateForSickLeave()
                  maxPastDate.setHours(0, 0, 0, 0)
                  
                  if (dateFrom < maxPastDate || dateTo < maxPastDate) {
                    const maxPastDateStr = maxPastDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
                    errorMessage = `Sick leave can only be filed up to 1 week (7 working days) in the past from today. The earliest date you can select is ${maxPastDateStr}. Please select dates within the allowed range.`
                  } else {
                    const workingDaysInRange = countWorkingDays(dateFrom, dateTo)
                    if (workingDaysInRange > 7) {
                      errorMessage = `Sick leave can only be filed for a maximum of 7 working days. Your selected date range spans ${workingDaysInRange} working days. Please adjust your date range.`
                    } else {
                      errorMessage = 'Invalid leave application. Please check your dates and ensure they are within the allowed range for sick leave (today or up to 1 week in the past, counting only working days).'
                    }
                  }
                }
              } catch (validationErr) {
                errorMessage = 'Invalid leave application. Please check your dates and ensure they are within the allowed range for sick leave (today or up to 1 week in the past, counting only working days).'
              }
            } else {
              // For other errors, use the backend error message if available
              errorMessage = backendError || errorMessage
            }
          } catch (parseErr) {
            // If JSON parsing fails, try to extract error message from the string
            const errorMatch = message.match(/"error"\s*:\s*"([^"]+)"/)
            if (errorMatch) {
              errorMessage = errorMatch[1]
            } else {
              // Remove JSON from message and show clean error
              errorMessage = message.replace(/\{[\s\S]*\}/, '').trim() || errorMessage
            }
          }
        } else {
          // If no JSON, check if it's a generic HTTP error for sick leave
          if (leaveTypeId === 3 && message.includes('500') && message.includes('Invalid')) {
            errorMessage = 'Invalid leave application. Please check your dates and ensure they are within the allowed range for sick leave (today or up to 1 week in the past, counting only working days).'
          } else {
            // Clean up HTTP error messages
            errorMessage = message.replace(/HTTP error!\s*status:\s*\d+\s*-?\s*/i, '').trim() || errorMessage
          }
        }
      }
      
      toast.error(errorMessage)
    } finally {
      uiState.addSubmitting = false
    }
  }

  const submitEdit = async () => {
    if (!uiState.selectedLeave) {
      toast.error('No leave selected for editing')
      return
    }

    const otherPurposeSelected = hasEditOtherPurposeSelected()
    if (
      !formData.editForm.leave_type_id ||
      (!otherPurposeSelected && !formData.editForm.day_type_id) ||
      (!otherPurposeSelected && !formData.editForm.date_from) ||
      (!otherPurposeSelected && !formData.editForm.date_to)
    ) {
      toast.error('Please complete all required fields')
      return
    }

    if (!otherPurposeSelected && isVacationOrSpecialLeave(formData.editForm.leave_type_id) && !formData.editForm.incase_vacation_leave_id) {
      toast.error('Please select where you are spending your leave.')
      return
    }

    if (!otherPurposeSelected && isSickLeaveTypeById(formData.editForm.leave_type_id) && !formData.editForm.incase_sick_leave_id) {
      toast.error('Please select sick leave type.')
      return
    }

    if (
      !otherPurposeSelected &&
      (isSickLeave(formData.editForm.leave_type_id) || formData.editForm.incase_sick_leave_id) &&
      !String(formData.editForm.incase_sick_leave_specify || '').trim()
    ) {
      toast.error('Please specify illness.')
      return
    }

    if (!otherPurposeSelected && isStudyLeaveTypeById(formData.editForm.leave_type_id) && !formData.editForm.incase_study_leave_id) {
      toast.error('Please select study leave type.')
      return
    }

    if (formData.editForm.other_purpose_id === '1' && !String(formData.editForm.monetization_amount || '').trim()) {
      toast.error('Please enter leave credits.')
      return
    }
    if (formData.editForm.other_purpose_id === '2' && !String(formData.editForm.commutation_id || '').trim()) {
      toast.error('Please select commutation option.')
      return
    }

    if (!otherPurposeSelected && isSickLeave(formData.editForm.leave_type_id) && formData.editForm.is_advance_filing) {
      const today = new Date()
      today.setHours(0, 0, 0, 0)
      const dateFrom = formData.editForm.date_from ? normalizeDate(formData.editForm.date_from) : null
      const dateTo = formData.editForm.date_to ? normalizeDate(formData.editForm.date_to) : null

      if (!dateFrom || !dateTo) {
        toast.error('Please select both start and end dates for advance sick leave.')
        return
      }
      if (dateFrom < today || dateTo < today) {
        toast.error('Advance sick leave must be filed for today or a future date (e.g. scheduled consultation or operation).')
        return
      }
      if (dateTo < dateFrom) {
        toast.error('End date cannot be before start date. Please select a valid date range.')
        return
      }
    }
    
    uiState.editSubmitting = true
    try {
      // Ensure employee id is always included
      if (!formData.editForm.employee_id) {
        const fallbackId = uiState.selectedLeave.employee_id || state.employeeId
        if (fallbackId) {
          formData.editForm.employee_id = String(fallbackId)
        }
      }
      
      const formDataObj = new FormData()
      Object.keys(formData.editForm).forEach(k => {
        if (otherPurposeSelected && ['day_type_id', 'date_from', 'date_to'].includes(k)) return
        formDataObj.append(k, formData.editForm[k])
      })
      
      const id = uiState.selectedLeave.id
      if (!id) throw new Error('Unable to identify leave to update')
      
      const res = await ApiService.submitLeave(id, formDataObj)
      if (!res || res.success !== true) throw new Error(res?.message || 'Failed to update leave')
      
      toast.success('Leave updated successfully')
      closeEditModal()
      await loadLeaveData()
    } catch (e) {
      toast.error(e?.message || 'Failed to update leave')
    } finally {
      uiState.editSubmitting = false
    }
  }

  const deleteLeave = async (leave) => {
    try {
      await ApiService.deleteLeave(leave.id)
      toast.success('Leave deleted successfully')
      await loadLeaveData()
    } catch (error) {
      toast.error('Failed to delete leave')
    }
  }

  const openApproveModal = (leave) => {
    uiState.selectedLeave = leave
    uiState.showApproveModal = true
  }

  const closeApproveModal = () => {
    uiState.showApproveModal = false
    uiState.selectedLeave = null
  }

  const approveLeave = async (remarks = '') => {
    if (!uiState.selectedLeave) return
    
    uiState.approveSubmitting = true
    try {
      const remark = remarks.trim()
      await ApiService.processLeave(uiState.selectedLeave.id, 1, remark)
      toast.success('Leave approved successfully')
      closeApproveModal()
      await loadLeaveData()
      // Switch to approved tab to show the updated leave
      state.activeApproverTab = 'approved'
    } catch (error) {
      toast.error(error?.message || 'Failed to approve leave')
    } finally {
      uiState.approveSubmitting = false
    }
  }

  const openDisapproveModal = (leave) => {
    uiState.selectedLeave = leave
    uiState.showDisapproveModal = true
  }

  const closeDisapproveModal = () => {
    uiState.showDisapproveModal = false
    uiState.selectedLeave = null
  }

  const disapproveLeave = async (remarks = '') => {
    if (!uiState.selectedLeave) return
    
    uiState.disapproveSubmitting = true
    try {
      const remark = remarks.trim()
      await ApiService.processLeave(uiState.selectedLeave.id, 2, remark)
      toast.success('Leave disapproved successfully')
      closeDisapproveModal()
      await loadLeaveData()
      // Switch to disapproved tab to show the updated leave
      state.activeApproverTab = 'disapproved'
    } catch (error) {
      toast.error(error?.message || 'Failed to disapprove leave')
    } finally {
      uiState.disapproveSubmitting = false
    }
  }

  const handleCancelLeave = async (formData) => {
    if (!uiState.selectedLeave) return
    
    try {
      // Extract reason from FormData
      const reason = formData.get('reason') || 'Cancelled'
      
      // First, upload the cancellation attachment if provided
      if (formData.has('attachment')) {
        await ApiService.uploadLeaveCancelAttachment(uiState.selectedLeave.id, formData)
      }
      
      // Then, process the cancellation with remarks
      await ApiService.processLeave(uiState.selectedLeave.id, 4, reason)
      toast.success('Leave cancelled successfully')
      closeCancelModal()
      await loadLeaveData()
    } catch (error) {
      console.error('Error cancelling leave:', error)
      toast.error(error?.message || 'Failed to cancel leave')
    }
  }

  const downloadAttachment = (leave) => {
    ApiService.downloadLeaveAttachment(leave.id)
  }

  const showInlinePrint = async (leave) => {
    try {
      uiState.printBlobUrl = ''
      uiState.printBlob = await ApiService.printLeave(leave.id)
      uiState.printBlobUrl = URL.createObjectURL(uiState.printBlob)
    } catch (e) {
      toast.error('Failed to load print preview')
    }
  }

  const closeInlinePrint = () => {
    if (uiState.printBlobUrl) URL.revokeObjectURL(uiState.printBlobUrl)
    uiState.printBlobUrl = ''
    uiState.printBlob = null
  }

  const downloadInlinePrint = () => {
    if (!uiState.printBlob) return
    const url = URL.createObjectURL(uiState.printBlob)
    const a = document.createElement('a')
    a.href = url
    a.download = 'leave_application.pdf'
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    setTimeout(() => URL.revokeObjectURL(url), 1000)
  }

  // UI actions
  const openAddLeave = async () => {
    await loadLeaveTypes()
    uiState.showAddModal = true
  }
  const closeAddLeave = () => { 
    uiState.showAddModal = false
    uiState.showVacationPromptModal = false
    uiState.showSickPromptModal = false
    uiState.showSpecialLeaveForWomenPromptModal = false
    uiState.showStudyLeavePromptModal = false
    formData.addForm = {
      leave_type_id: '',
      day_type_id: '',
      date_from: '',
      date_to: '',
      reason: '',
      employee_id: '',
      incase_vacation_leave_id: '',
      incase_vacation_leave_specify: '',
      incase_sick_leave_id: '',
      incase_sick_leave_specify: '',
      incase_special_leave_specify: '',
      incase_study_leave_id: '',
      other_purpose_id: '',
      monetization: false,
      terminal_leave: false,
      monetization_amount: '',
      commutation_id: '',
      emergency_leave: false,
      is_advance_filing: false
    }
    formData.addAttachments = []
  }

  const openEditModal = (leave) => {
    uiState.selectedLeave = leave
    formData.editForm = {
      leave_type_id: String(leave.leave_type_id || ''),
      day_type_id: String(leave.day_type_id || (leave.day_type === 'Half Day' ? '2' : '1')),
      date_from: (leave.date_from ? String(leave.date_from).substring(0,10) : ''),
      date_to: (leave.date_to ? String(leave.date_to).substring(0,10) : ''),
      reason: leave.reason || '',
      employee_id: String(leave.employee_id || state.employeeId || ''),
      incase_vacation_leave_id: String(leave.incase_vacation_leave_id || ''),
      incase_vacation_leave_specify: leave.incase_vacation_leave_specify || '',
      incase_sick_leave_id: String(leave.incase_sick_leave_id || ''),
      incase_sick_leave_specify: leave.incase_sick_leave_specify || '',
      incase_special_leave_specify: leave.incase_special_leave_specify || '',
      incase_study_leave_id: String(leave.incase_study_leave_id || ''),
      other_purpose_id: String(leave.other_purpose_id || ''),
      monetization: String(leave.other_purpose_id || '') === '1',
      terminal_leave: String(leave.other_purpose_id || '') === '2',
      monetization_amount: leave.monetization_amount != null ? String(leave.monetization_amount) : '',
      commutation_id: String(leave.commutation_id || ''),
      is_advance_filing: !!(leave.is_advance_filing === true || leave.is_advance_filing === 1 || leave.is_advance_filing === '1')
    }
    uiState.showEdit = true
  }

  const closeEditModal = () => { 
    uiState.showEdit = false 
    uiState.selectedLeave = null
    formData.editForm = {
      leave_type_id: '',
      day_type_id: '',
      date_from: '',
      date_to: '',
      reason: '',
      employee_id: '',
      incase_vacation_leave_id: '',
      incase_vacation_leave_specify: '',
      incase_sick_leave_id: '',
      incase_sick_leave_specify: '',
      incase_special_leave_specify: '',
      incase_study_leave_id: '',
      other_purpose_id: '',
      monetization: false,
      terminal_leave: false,
      monetization_amount: '',
      commutation_id: '',
      is_advance_filing: false
    }
  }

  const cancelLeave = (leave) => {
    uiState.selectedLeave = leave
    uiState.showCancelModal = true
  }

  const closeCancelModal = () => {
    uiState.showCancelModal = false
    uiState.selectedLeave = null
  }

  const onAddFilesChange = (e) => {
    const files = Array.from(e.target.files)
    files.forEach(file => {
      const allowed = ['image/jpeg','image/jpg','image/png','application/vnd.ms-excel','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet','application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document','application/pdf']
      if (!allowed.includes(file.type)) return
      if (file.size > 25 * 1024 * 1024) return
      if (formData.addAttachments.length >= 5) return
      formData.addAttachments.push(file)
    })
    e.target.value = ''
  }

  const removeAddFile = (i) => { formData.addAttachments.splice(i, 1) }

  const switchTab = (tabId) => { state.activeTab = tabId }

  const editLeave = (leave) => {
    // This would navigate to edit page
  }

  const viewLeaveDetails = (leave) => {
    // This would navigate to details page
  }

    return {
      // State
      state,
      uiState,
      formData,
      tabs,
      approverTabs,
      
      // Computed
      filteredLeaves,
      showEmergencyLeaveCheckbox,
      addLeaveDisabledDate,
      
      // Helper functions
      getLeaveBalance,
      hasZeroCredits,
      getSickLeaveDisabledDate,
      isSickLeave,
      isVacationOrSpecialLeave,
      requiresVacationAdvanceNotice,
      allowsEmergencyLeave,
      toggleEmergencyLeave,
      toggleAdvanceSickLeave,
      
      // Methods
    loadLeaveData,
    loadLeaveTypes,
    submitAddLeave,
    submitEdit,
    deleteLeave,
    approveLeave,
    disapproveLeave,
    openApproveModal,
    closeApproveModal,
    openDisapproveModal,
    closeDisapproveModal,
    handleCancelLeave,
    downloadAttachment,
    showInlinePrint,
    closeInlinePrint,
    downloadInlinePrint,
    
    // UI Actions
    openAddLeave,
    closeAddLeave,
    handleAddLeaveTypeChange,
    toggleVacationLeaveIncase,
    toggleEmergencyLeave,
    toggleSickLeaveIncase,
    toggleStudyLeaveIncase,
    toggleOtherPurpose,
    toggleCommutation,
    toggleEditVacationLeaveIncase,
    toggleEditSickLeaveIncase,
    toggleEditStudyLeaveIncase,
    toggleEditOtherPurpose,
    toggleEditCommutation,
    openEditModal,
    closeEditModal,
    cancelLeave,
    closeCancelModal,
    onAddFilesChange,
    removeAddFile,
    switchTab,
    editLeave,
    viewLeaveDetails,
    
    // Utilities
    formatCredits,
    getCurrentUserId
  }
}

