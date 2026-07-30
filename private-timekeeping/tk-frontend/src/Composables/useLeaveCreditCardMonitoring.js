// Composable for Leave Credit Card Monitoring endpoints
// Usage: in services/api.js -> export const leaveCreditCardService = useLeaveCreditCardMonitoring(api)

export function useLeaveCreditCardMonitoring(api) {
  const base = {
    list: '/leave-credit-card',
    leaveTypes: '/leave-credit-card/leave-types',
    bulkPreview: '/leave-credit-card/bulk-preview',
    details: (id) => `/leave-credit-card/${id}/details`,
    loadYear: (id, year) => `/leave-credit-card/${id}/${year}/load`
  }

  const baseError = 'Request failed'
  const withPrefix = (prefix, err) => `${prefix}: ${err && err.message ? err.message : baseError}`

  const listEmployees = async (params = {}) => {
    try {
      const res = await api.get(base.list, { params })
      return res?.data || res
    } catch (err) {
      throw new Error(withPrefix('Failed to load employees', err))
    }
  }

  const getLeaveTypes = async () => {
    try {
      const res = await api.get(base.leaveTypes)
      // ApiResponse wraps as: { success: true, message: "...", data: [...] }
      // Axios wraps as: { data: { success: true, message: "...", data: [...] }, ... }
      // So we need: res.data.data (the inner data array)
      const payload = res?.data || res
      // If payload has a data property and it's an array, return it
      // Otherwise, if payload itself is an array, return it
      if (Array.isArray(payload?.data)) {
        return payload.data
      } else if (Array.isArray(payload)) {
        return payload
      }
      // Fallback: return payload as-is
      return payload
    } catch (err) {
      throw new Error(withPrefix('Failed to load leave types', err))
    }
  }

  const loadDetails = async (id, params = {}) => {
    try {
      const res = await api.get(base.details(id), { params })

      // ApiResponse wraps data in res.data, so payload is the actual data object
      const payload = res?.data ?? res
      
      // Extract data from the response structure
      // Backend returns: { data: {...}, leave_credit_cards: [...], beginning_balances: [...], leave_type: {...} }
      const tableData = Array.isArray(payload?.leave_credit_cards) 
        ? payload.leave_credit_cards 
        : (Array.isArray(payload?.data?.leave_credit_cards) ? payload.data.leave_credit_cards : [])
      
      // Extract header (employee data)
      // Backend returns data as an array from ->get(), so it's payload.data[0]
      let headerData = null
      if (Array.isArray(payload?.data) && payload.data.length > 0) {
        headerData = payload.data[0]
      } else if (payload?.data && typeof payload.data === 'object' && !Array.isArray(payload.data)) {
        // If data is an object, it might be the employee object directly
        headerData = payload.data
      }
      
      // Extract beginning balances
      const beginningBalancesData = Array.isArray(payload?.beginning_balances)
        ? payload.beginning_balances
        : (Array.isArray(payload?.data?.beginning_balances) ? payload.data.beginning_balances : [])
      
      // Extract leave type info
      const leaveTypeInfo = payload?.leave_type || payload?.data?.leave_type
      
      const timeData = Array.isArray(payload?.time_data)
        ? payload.time_data
        : (Array.isArray(payload?.data?.time_data) ? payload.data.time_data : [])

      const result = {
        header: headerData,
        table: tableData,
        beginningBalances: beginningBalancesData,
        beginning_balances: beginningBalancesData, // Also include with underscore for compatibility
        leaveType: leaveTypeInfo,
        time_data: timeData
      }
      
      return result
    } catch (err) {
      throw new Error(withPrefix('Failed to load leave credit card details', err))
    }
  }

  const loadYear = async (id, year) => {
    try {
      const res = await api.get(base.loadYear(id, year))
      const payload = res?.data ?? res

      const result = {
        leave_credit_cards: Array.isArray(payload?.data?.data?.leave_credit_cards) ? payload.data.data.leave_credit_cards : (Array.isArray(payload?.data?.leave_credit_cards) ? payload.data.leave_credit_cards : (Array.isArray(payload?.leave_credit_cards) ? payload.leave_credit_cards : [])),
        beginning_balances: Array.isArray(payload?.data?.data?.beginning_balances) ? payload.data.data.beginning_balances : (Array.isArray(payload?.data?.beginning_balances) ? payload.data.beginning_balances : (Array.isArray(payload?.beginning_balances) ? payload.beginning_balances : []))
      }

      return result
    } catch (err) {
      throw new Error(withPrefix('Failed to load leave credit card table', err))
    }
  }

  const bulkPreview = async (payload = {}) => {
    try {
      const res = await api.post(base.bulkPreview, payload)
      const wrapped = res?.data ?? res
      return wrapped?.rows || []
    } catch (err) {
      throw new Error(withPrefix('Failed to load bulk leave credit preview', err))
    }
  }

  return { listEmployees, getLeaveTypes, loadDetails, loadYear, bulkPreview }
}

export default useLeaveCreditCardMonitoring


