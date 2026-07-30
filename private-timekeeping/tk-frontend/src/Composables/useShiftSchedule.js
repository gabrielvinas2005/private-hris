// Composable that wires Shifting Schedule endpoints to a provided api client
// Backend routes reference:
// GET    /shift-schedules                     -> list
// GET    /shift-schedules/{id}/add            -> form (id=0 for new)
// POST   /shift-schedules/{id}                -> save (id=0 create, >0 update)
// GET    /shift-schedules/unassigned-employees-> loadUnassignedEmployees
// POST   /shift-schedules/{id}/employees      -> addEmployees
// DELETE /shift-schedules/{header_id}/employees/{id} -> removeEmployees

export function useShiftSchedule(api) {
  const base = '/shift-schedules'
  const withPrefix = (prefix, err) => `${prefix}: ${err && err.message ? err.message : 'Request failed'}`

  const list = async () => {
    try {
      const res = await api.get(`${base}`)
      return res?.data ?? res
    } catch (err) {
      throw new Error(withPrefix('Failed to load shift schedules', err))
    }
  }

  const form = async (id = 0) => {
    try {
      const res = await api.get(`${base}/${id}/add`)
      return res?.data ?? res
    } catch (err) {
      throw new Error(withPrefix('Failed to load shift schedule form', err))
    }
  }

  const save = async (id, payload) => {
    try {
      const res = await api.post(`${base}/${id}`, payload)
      return res?.data ?? res
    } catch (err) {
      throw new Error(withPrefix('Failed to save shift schedule', err))
    }
  }

  const loadUnassignedEmployees = async () => {
    try {
      const res = await api.get(`${base}/unassigned-employees`)
      return res?.data ?? res
    } catch (err) {
      throw new Error(withPrefix('Failed to load unassigned employees', err))
    }
  }

  const addEmployees = async (id, employeeIds) => {
    try {
      // Backend expects employee_id[] in payload
      const payload = { employee_id: employeeIds }
      const res = await api.post(`${base}/${id}/employees`, payload)
      return res?.data ?? res
    } catch (err) {
      throw new Error(withPrefix('Failed to add employees to schedule', err))
    }
  }

  const removeEmployee = async (headerId, employeeId) => {
    try {
      const res = await api.delete(`${base}/${headerId}/employees/${employeeId}`)
      return res?.data ?? res
    } catch (err) {
      throw new Error(withPrefix('Failed to remove employee from schedule', err))
    }
  }

  const destroy = async (id) => {
    try {
      // Prefer official DELETE endpoint which triggers controller->destroy()
      const res = await api.delete(`${base}/${id}`)
      return res?.data ?? res
    } catch (err) {
      // Some deployments may expose GET /{id}/delete; try as fallback
      try {
        const res = await api.get(`${base}/${id}/delete`)
        return res?.data ?? res
      } catch (_) {
        throw new Error(withPrefix('Failed to delete shift schedule', err))
      }
    }
  }

  return { list, form, save, loadUnassignedEmployees, addEmployees, removeEmployee, destroy }
}

export default useShiftSchedule


