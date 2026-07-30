// Composable that wires Fix Schedule endpoints to a provided api client
// Usage: in services/api.js -> export const fixScheduleService = useFixSchedule(api)

export function useFixSchedule(api) {
  const base = '/fix-schedules'
  const baseError = 'Request failed'
  const withPrefix = (prefix, err) => `${prefix}: ${err && err.message ? err.message : baseError}`

  const list = async () => {
    try {
      const res = await api.get(`${base}`)
      return res?.data ?? res
    } catch (err) {
      throw new Error(withPrefix('Failed to load fix schedules', err))
    }
  }

  const form = async (id = 0) => {
    try {
      const res = await api.get(`${base}/${id}/add`)
      return res?.data ?? res
    } catch (err) {
      throw new Error(withPrefix('Failed to load fix schedule form', err))
    }
  }

  const save = async (id, payload) => {
    try {
      const res = await api.post(`${base}/${id}`, payload)
      return res?.data ?? res
    } catch (err) {
      throw new Error(withPrefix('Failed to save fix schedule', err))
    }
  }

  const destroy = async (id) => {
    try {
      // No dedicated delete route defined; using common convention if available in backend later
      // For now, try DELETE /fix-schedules/{id} if backend supports it
      const res = await api.delete(`${base}/${id}`)
      return res?.data ?? res
    } catch (err) {
      // Fallback to GET /fix-schedules/{id}/delete used by many legacy endpoints
      try {
        const res = await api.get(`${base}/${id}/delete`)
        return res?.data ?? res
      } catch (err2) {
        throw new Error(withPrefix('Failed to delete fix schedule', err2))
      }
    }
  }

  const getAssignedEmployees = async (id) => {
    try {
      const res = await api.get(`${base}/${id}/employees`)
      return res?.data ?? res
    } catch (err) {
      throw new Error(withPrefix('Failed to load assigned employees', err))
    }
  }

  const removeEmployeeFromSchedule = async (scheduleId, employeeId) => {
    try {
      const res = await api.delete(`${base}/${scheduleId}/employees/${employeeId}`)
      return res?.data ?? res
    } catch (err) {
      // Fallback to POST method if DELETE is not supported
      try {
        const res = await api.post(`${base}/${scheduleId}/employees/${employeeId}/remove`)
        return res?.data ?? res
      } catch (err2) {
        throw new Error(withPrefix('Failed to remove employee from schedule', err2))
      }
    }
  }

  return { list, form, save, destroy, getAssignedEmployees, removeEmployeeFromSchedule }
}

export default useFixSchedule

