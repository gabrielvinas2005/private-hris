// Composable for Official Business Monitoring
export function useOBMonitoring(api) {
  const base = {
    list: () => api.get('/official-business-approvals'),
    types: () => api.get('/official-business-types'),
  }

  const approve = (id, remarks = '-') => api.get(`/official-business-applications/${id}/approve/${encodeURIComponent(remarks)}`)
  const disapprove = (id, remarks = '-') => api.get(`/official-business-applications/${id}/disapprove/${encodeURIComponent(remarks)}`)
  const cancel = (id, remarks = '-') => api.get(`/official-business-applications/${id}/cancel/${encodeURIComponent(remarks)}`)

  return {
    fetchMonitoring: base.list,
    fetchTypes: base.types,
    approve,
    disapprove,
    cancel,
  }
}



