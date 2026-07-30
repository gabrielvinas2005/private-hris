// Composable for Pass Slip Monitoring
export function usePassSlipMonitoring(api) {
  return {
    fetchMonitoring: () => api.get('/pass-slip-monitoring'),
    api,
  }
}
