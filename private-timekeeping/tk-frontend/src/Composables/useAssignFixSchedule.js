// Composable that wires Assign Fix Schedule endpoints to a provided api client
// Backend routes reference:
// GET  /assign-schedules            -> index (returns { fix_schedules, employees })
// GET  /assign-schedules/employees  -> employees (returns { employees }) - optimized endpoint for loading employees separately
// POST /assign-schedules            -> store (expects { fix_schedule_id, employee_id: [] })

export function useAssignFixSchedule(api) {
  const base = '/assign-schedules'
  const withPrefix = (prefix, err) => `${prefix}: ${err && err.message ? err.message : 'Request failed'}`

  const load = async () => {
    try {
      const res = await api.get(base)
      return res?.data ?? res
    } catch (err) {
      throw new Error(withPrefix('Failed to load assign fix schedule data', err))
    }
  }

  const loadEmployees = async () => {
    try {
      const res = await api.get(`${base}/employees`)
      // Handle different response structures from Laravel API
      // Laravel typically returns: { data: { employees: [...] }, message: "...", success: true }
      // But the response might also be directly: { employees: [...] }
      if (res?.data) {
        // If res.data exists, return it (it should contain employees)
        return res.data
      }
      // Otherwise, return the entire response (it might be the data itself)
      return res
    } catch (err) {
      console.error('Error in loadEmployees:', err)
      throw new Error(withPrefix('Failed to load employees', err))
    }
  }

  const assign = async (fixScheduleId, employeeIds) => {
    try {
      const payload = { fix_schedule_id: fixScheduleId, employee_id: employeeIds }
      const res = await api.post(base, payload)
      return res?.data ?? res
    } catch (err) {
      throw new Error(withPrefix('Failed to assign employees to fix schedule', err))
    }
  }

  return { load, loadEmployees, assign }
}

export default useAssignFixSchedule


