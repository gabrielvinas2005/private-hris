export function useBiometrics(api) {
  return {
    // Get list of employees with biometric access
    async fetchEmployees() {
      const res = await api.get('/biometrics')
      return {
        employees: res?.data?.employees || [],
        database2: res?.data?.database2 || '',
        configured: res?.data?.configured || false
      }
    },

    // Load biometric attendance data for selected employees
    async loadBiometricData(params) {
      const { attendance_from, attendance_to, select } = params
      
      if (!attendance_from || !attendance_to) {
        throw new Error('Attendance date range is required')
      }
      
      if (!select || select.length === 0) {
        throw new Error('Please select at least one employee')
      }

      const res = await api.get('/biometrics/load', {
        attendance_from,
        attendance_to,
        select
      })
      
      return res?.data || []
    },

    // Load biometric configuration
    async loadConfig() {
      const res = await api.get('/biometrics/setup')
      return res?.data || {}
    },


    // Load real-time biometric data
    async loadRealtimeData(params) {
      const { attendance_from, attendance_to, select } = params
      
      if (!attendance_from || !attendance_to) {
        throw new Error('Attendance date range is required')
      }
      
      if (!select || select.length === 0) {
        throw new Error('Please select at least one employee')
      }

      const res = await api.get('/biometrics/realtime', {
        attendance_from,
        attendance_to,
        select
      })
      
      return res?.data || []
    },

    // Load daily summary
    async loadDailySummary(date) {
      const res = await api.get('/biometrics/daily-summary', {
        date
      })
      
      // API returns { success: true, message: "...", data: [...] }
      // The api.get already parses the JSON response
      if (res && res.data) {
        // If data is an array, return it directly
        if (Array.isArray(res.data)) {
          return res.data
        }
      }
      
      return []
    }
  }
}
