// Composable for Leave Monitoring endpoints
// Usage: in services/api.js -> export const leaveMonitoringService = useLeaveMonitoring(api)

export function useLeaveMonitoring(api) {
  const base = {
    monitoring: '/leave-approvals',
    process: (id, processId, remarks) => `/leaves/${id}/process/${processId}/${encodeURIComponent(remarks || '-')}`,
    attachments: (id) => `/leave-attachments/${id}`,
    attachmentDownload: (id) => `/leave-attachment-download/${id}`
  }
  const baseError = 'Request failed'
  const withPrefix = (prefix, err) => `${prefix}: ${err && err.message ? err.message : baseError}`

  const loadApprovals = async () => {
    try {
      const res = await api.get(base.monitoring)
      return res?.data ?? res
    } catch (err) {
      throw new Error(withPrefix('Failed to load leave approvals', err))
    }
  }

  const process = async (id, processId, remarks = '') => {
    try {
      const res = await api.get(base.process(id, processId, remarks))
      return res?.data ?? res
    } catch (err) {
      throw new Error(withPrefix('Failed to process leave', err))
    }
  }

  const approve = (id, remarks = '') => process(id, 1, remarks)
  const disapprove = (id, remarks = '') => process(id, 2, remarks)
  const cancel = (id, remarks = '') => process(id, 4, remarks)

  const loadAttachments = async (id) => {
    try {
      const res = await api.get(base.attachments(id))
      return res?.data ?? res
    } catch (err) {
      throw new Error(withPrefix('Failed to load attachments', err))
    }
  }

  const getAttachmentDownloadUrl = (id) => `${base.attachmentDownload(id)}`

  return { loadApprovals, process, approve, disapprove, cancel, loadAttachments, getAttachmentDownloadUrl }
}

export default useLeaveMonitoring


