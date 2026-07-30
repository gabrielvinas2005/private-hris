// Composable for Leave Taken Monitoring endpoints
// Usage: in services/api.js -> export const leaveTakenMonitoringService = useLeaveTakenMonitoring(api)

export function useLeaveTakenMonitoring(api) {
  const base = {
    list: '/leave-taken-monitoring',
    load: (id) => `/leave-taken/${id}`
  }

  const baseError = 'Request failed'
  const withPrefix = (prefix, err) => `${prefix}: ${err && err.message ? err.message : baseError}`

  const listEmployees = async (params = {}) => {
    try {
      const res = await api.get(base.list, { params })
      // Support both array response and paginated structure
      if (Array.isArray(res)) {
        const page = Number(params.page) || 1
        const perPage = Number(params.per_page) || 10
        const total = res.length
        const start = (page - 1) * perPage
        const end = start + perPage
        const sliced = res.slice(start, end)
        return { data: sliced, pagination: {
          current_page: page,
          per_page: perPage,
          total,
          last_page: Math.max(1, Math.ceil(total / perPage)),
          has_more_pages: end < total,
          from: total ? start + 1 : 0,
          to: Math.min(end, total)
        } }
      }
      const page = Number(params.page) || 1
      const perPage = Number(params.per_page) || 10
      
      // Handle new backend response structure: { data: { data: [...], pagination: {...} } }
      let dataArray = []
      let paginationData = null
      
      if (res?.data && typeof res.data === 'object' && 'data' in res.data) {
        // New structure: backend returns { data: [...], pagination: {...} }
        dataArray = Array.isArray(res.data.data) ? res.data.data : []
        paginationData = res.data.pagination
      } else if (Array.isArray(res?.data)) {
        // Old structure: backend returns array directly
        dataArray = res.data
      }
      
      const hasPagination = Boolean(paginationData) || [
        'current_page','per_page','total','last_page','from','to'
      ].some(k => k in (res || {}))
      
      if (hasPagination) {
        return {
          data: dataArray,
          pagination: paginationData || {
            current_page: Number(res?.current_page) || page,
            per_page: Number(res?.per_page) || perPage,
            total: Number(res?.total) || dataArray.length,
            last_page: Number(res?.last_page) || Math.max(1, Math.ceil((Number(res?.total) || dataArray.length) / (Number(res?.per_page) || perPage))),
            has_more_pages: Boolean(res?.has_more_pages) || ((page * perPage) < (Number(res?.total) || dataArray.length)),
            from: Number(res?.from) || (dataArray.length ? ((page - 1) * perPage + 1) : 0),
            to: Number(res?.to) || Math.min(page * perPage, (Number(res?.total) || dataArray.length))
          }
        }
      }
      // Fallback: object without pagination, slice client-side
      {
        const total = dataArray.length
        const start = (page - 1) * perPage
        const end = start + perPage
        const sliced = dataArray.slice(start, end)
        return {
          data: sliced,
          pagination: {
            current_page: page,
            per_page: perPage,
            total,
            last_page: Math.max(1, Math.ceil(total / perPage)),
            has_more_pages: end < total,
            from: total ? start + 1 : 0,
            to: Math.min(end, total)
          }
        }
      }
    } catch (err) {
      throw new Error(withPrefix('Failed to load employees', err))
    }
  }

  const loadEmployee = async (id, year) => {
    try {
      const query = year ? `?year=${encodeURIComponent(year)}` : ''
      const res = await api.get(`${base.load(id)}${query}`)
      // Expected: { data: [...], leaves: [...] } wrapped in successResponse
      const payload = res?.data ?? res
      return {
        header: Array.isArray(payload?.data) && payload.data.length ? payload.data[0] : null,
        leaves: Array.isArray(payload?.leaves) ? payload.leaves : []
      }
    } catch (err) {
      throw new Error(withPrefix('Failed to load leave taken details', err))
    }
  }

  return { listEmployees, loadEmployee }
}

export default useLeaveTakenMonitoring


