// Composable for Overtime Monitoring
export function useOTMonitoring(api) {
  const base = {
    list: () => api.get('/overtime-approvals'),
    types: () => api.get('/overtime-types'),
  }

  const approve = (payload) => api.post('/overtime-applications/approve', payload)
  const disapprove = (id, remarks = '-') => api.get(`/overtime-applications/${id}/disapprove/${encodeURIComponent(remarks)}`)
  const cancel = (id, emp_id, remarks = '-') => api.get(`/overtime-applications/${id}/cancel/${emp_id}/${encodeURIComponent(remarks)}`)
  const getOvertimeApplication = (id) => api.get(`/overtime-applications/${id}`)
  const convertToCTO = (id) => api.post(`/overtime-applications/${id}/convert-to-cto`)
  const revertCTO = (id) => api.post(`/overtime-applications/${id}/revert-cto`)

  return {
    fetchMonitoring: base.list,
    fetchTypes: base.types,
    approve,
    disapprove,
    cancel,
    getOvertimeApplication,
    convertToCTO,
    revertCTO,
  }
}



