export function useCOCMonitoring(api) {
  return {
    async fetchDetails(monthId = null, year = null) {
      // Build query parameters
      const params = new URLSearchParams()
      if (monthId) params.append('month_id', monthId)
      if (year) params.append('year', year)
      
      const queryString = params.toString()
      const url = '/coc-details' + (queryString ? '?' + queryString : '')
      
      // Prefer richer endpoint that returns summary + details
      const res = await api.get(url)
      // Support both ApiResponse { data: { coc_details, summary } } and plain array
      const payload = res && res.data ? res.data : res
      if (Array.isArray(payload)) {
        return { coc_details: payload, summary: null }
      }
      return {
        coc_details: payload?.coc_details || [],
        summary: payload?.summary || null,
      }
    },
    async fetchMonths() {
      // Try backend reference; gracefully fallback to static list if not available
      try {
        const res = await api.get('/months')
        const payload = res && res.data ? res.data : res
        if (Array.isArray(payload)) return payload
        if (Array.isArray(payload?.data)) return payload.data
        if (Array.isArray(payload?.months)) return payload.months
      } catch (_) {
        // ignore not found or network errors and fallback
      }
      return [
        { id: 1, abbrv: 'Jan', name: 'January' },
        { id: 2, abbrv: 'Feb', name: 'February' },
        { id: 3, abbrv: 'Mar', name: 'March' },
        { id: 4, abbrv: 'Apr', name: 'April' },
        { id: 5, abbrv: 'May', name: 'May' },
        { id: 6, abbrv: 'Jun', name: 'June' },
        { id: 7, abbrv: 'Jul', name: 'July' },
        { id: 8, abbrv: 'Aug', name: 'August' },
        { id: 9, abbrv: 'Sep', name: 'September' },
        { id: 10, abbrv: 'Oct', name: 'October' },
        { id: 11, abbrv: 'Nov', name: 'November' },
        { id: 12, abbrv: 'Dec', name: 'December' },
      ]
    },
  }
}





