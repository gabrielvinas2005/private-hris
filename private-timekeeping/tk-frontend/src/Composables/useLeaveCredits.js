// Composable that wires Leave Credits endpoints to a provided api client
// Backend routes reference:
// GET  /leave-credits
// POST /leave-credits
// GET  /leave-types
// GET  /employment-types
// GET  /global/employees-leave-credits/{leave_type_id}/{user_id}
// GET  /global/employees-with-leave-credits/{user_id}

export function useLeaveCredits(api) {
  const withPrefix = (prefix, err) => `${prefix}: ${err && err.message ? err.message : 'Request failed'}`

  const loadLeaveTypes = async () => {
    try {
      const res = await api.get('/leave-credits')
      return res?.data ?? res
    } catch (err) {
      throw new Error(withPrefix('Failed to load leave types', err))
    }
  }

  const loadAllLeaveTypes = async () => {
    try {
      const res = await api.get('/leave-types')
      return res?.data ?? res
    } catch (err) {
      throw new Error(withPrefix('Failed to load leave types', err))
    }
  }

  const loadEmploymentTypes = async () => {
    try {
      const res = await api.get('/employment-types')
      return res?.data ?? res
    } catch (err) {
      throw new Error(withPrefix('Failed to load employment types', err))
    }
  }

  const loadEmployeesForLeaveType = async (leaveTypeId, userId) => {
    try {
      const res = await api.get(`/global/employees-leave-credits/${leaveTypeId}/${userId}`)
      return res?.data ?? res
    } catch (err) {
      throw new Error(withPrefix('Failed to load employees for leave credits', err))
    }
  }

  const loadAllEmployeesWithCredits = async (userId) => {
    try {
      // Try the new endpoint first
      const res = await api.get(`/global/employees-with-leave-credits/${userId}`)
      return res?.data ?? res
    } catch (err) {
      console.warn('New endpoint failed, trying fallback:', err.message)
      // Fallback: try to get employees from existing endpoint
      try {
        const res = await api.get(`/global/employees-leave-credits/3/${userId}`) // Try with Sick Leave first
        return res?.data ?? res
      } catch (fallbackErr) {
        throw new Error(withPrefix('Failed to load employees with leave credits', fallbackErr))
      }
    }
  }

  const listAllLeaveCredits = async (filters = {}) => {
    try {
      // Build query parameters from filters
      const params = new URLSearchParams()
      if (filters.search) params.append('search', filters.search)
      if (filters.departmentId) params.append('department_id', filters.departmentId)
      if (filters.employmentTypeId) params.append('employment_type_id', filters.employmentTypeId)
      if (filters.onlyActive !== undefined) params.append('only_active', filters.onlyActive)
      if (filters.includeAllEmployees) params.append('include_all_employees', '1')
      if (filters.export) params.append('export', '1')
      if (!filters.export && filters.page != null) params.append('page', String(filters.page))
      if (!filters.export && filters.per_page != null) params.append('per_page', String(filters.per_page))

      const queryString = params.toString()
      const url = '/leave-credits/all' + (queryString ? '?' + queryString : '')
      const res = await api.get(url)
      // Backend returns: export mode -> data = array; paginated mode -> data = { data, total, current_page, per_page, last_page }
      return res?.data ?? res
    } catch (err) {
      throw new Error(withPrefix('Failed to list leave credits', err))
    }
  }

  const saveCreditsBulk = async (leaveTypeId, employeeIds, creditsArray) => {
    try {
      const payload = { leave_type_id: leaveTypeId, id: employeeIds, credits: creditsArray }
      const res = await api.post('/leave-credits', payload)
      return res?.data ?? res
    } catch (err) {
      throw new Error(withPrefix('Failed to save leave credits', err))
    }
  }

  // Leave Beginning Balances
  const listBeginningBalances = async (filters = {}) => {
    try {
      const params = new URLSearchParams()
      if (filters.employee_id) params.append('employee_id', filters.employee_id)
      if (filters.leave_type_id) params.append('leave_type_id', filters.leave_type_id)
      if (filters.year) params.append('year', filters.year)
      if (filters.month_id) params.append('month_id', filters.month_id)
      
      const queryString = params.toString()
      const url = '/leave-beginning-balances' + (queryString ? '?' + queryString : '')
      const res = await api.get(url)
      const payload = res?.data ?? res
      return Array.isArray(payload?.data) ? payload.data : (Array.isArray(payload) ? payload : [])
    } catch (err) {
      throw new Error(withPrefix('Failed to list beginning balances', err))
    }
  }

  const saveBeginningBalance = async (balanceData) => {
    try {
      const res = await api.post('/leave-beginning-balances', balanceData)
      return res?.data ?? res
    } catch (err) {
      throw new Error(withPrefix('Failed to save beginning balance', err))
    }
  }

  const saveBeginningBalancesBulk = async (balances) => {
    try {
      const res = await api.post('/leave-beginning-balances/bulk', { balances })
      return res?.data ?? res
    } catch (err) {
      throw new Error(withPrefix('Failed to save beginning balances', err))
    }
  }

  const deleteBeginningBalance = async (id) => {
    try {
      const res = await api.delete(`/leave-beginning-balances/${id}`)
      return res?.data ?? res
    } catch (err) {
      throw new Error(withPrefix('Failed to delete beginning balance', err))
    }
  }

  return {
    loadLeaveTypes,
    loadAllLeaveTypes,
    loadEmploymentTypes,
    loadEmployeesForLeaveType,
    loadAllEmployeesWithCredits,
    listAllLeaveCredits,
    saveCreditsBulk,
    listBeginningBalances,
    saveBeginningBalance,
    saveBeginningBalancesBulk,
    deleteBeginningBalance,
  }
}

export default useLeaveCredits
